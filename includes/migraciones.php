<?php
// includes/migraciones.php
// Lleva cualquier base de Trackify (MySQL o PostgreSQL, de cualquier integrante y de cualquier
// versión anterior) al esquema unificado de trackify.sql / trackify_postgres.sql.
//
// En cada request se hace UNA sola consulta para ver si el esquema está al día. Solo si falta
// algo se ejecutan los cambios. Todas las migraciones son seguras de repetir.
//
// Para agregar una columna nueva:
//   1. Sumarla a COLUMNAS_ESPERADAS (en minúscula).
//   2. Agregar su ALTER en sqlColumnasNuevas() para los dos motores.

const COLUMNAS_ESPERADAS = [
    'usuarios'     => ['uuid', 'email_verificado', 'codigo_verificacion', 'codigo_expiracion', 'google_id', 'foto_perfil', 'moneda_principal', 'nombre_usuario'],
    'metas_ahorro' => ['descripcion', 'estado'],
    'categorias'   => ['icono', 'color', 'id_usuario'],
    'movimientos'  => ['moneda'],
];

function migrarEsquema(BaseDatos $db): void
{
    $estado = estadoEsquema($db);
    if (!esquemaAlDia($estado)) {
        aplicarMigraciones($db);
    }
    if ((int)$estado['categorias'] <= 5) {
        crearCategoriasPredeterminadas($db);
    }
}

function esquemaAlDia(array $estado): bool
{
    return (int)$estado['columnas'] === array_sum(array_map('count', COLUMNAS_ESPERADAS))
        && (int)$estado['icono_tipo_viejo'] === 0
        && $estado['color_nullable'] === 'YES'
        && (int)$estado['escala_metas'] === 2
        && (int)$estado['uuid_ok'] === 1
        && (int)$estado['uuid_unico'] > 0
        && (int)$estado['consejos_autoincremental'] === 1;
}

// Una sola consulta con todo lo que hay que revisar
function estadoEsquema(BaseDatos $db): array
{
    $condiciones = [];
    foreach (COLUMNAS_ESPERADAS as $tabla => $columnas) {
        $condiciones[] = "(table_name = '$tabla' AND LOWER(column_name) IN ('" . implode("','", $columnas) . "'))";
    }
    $pg = $db->esPostgres();
    $esquema = $pg ? 'current_schema()' : 'DATABASE()';
    $col = fn($tabla, $columna, $campo) =>
        "(SELECT $campo FROM information_schema.columns WHERE table_schema = $esquema AND table_name = '$tabla' AND column_name = '$columna')";

    $uuid_unico = $pg
        ? "(SELECT COUNT(*) FROM pg_indexes WHERE schemaname = current_schema() AND tablename = 'usuarios' AND indexdef LIKE 'CREATE UNIQUE INDEX%(uuid)')"
        : "(SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'uuid' AND non_unique = 0)";
    $consejos_ai = $pg
        ? "(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'consejos_ia' AND column_name = 'id_consejos' AND (column_default LIKE 'nextval%' OR is_identity = 'YES'))"
        : "(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'consejos_ia' AND column_name = 'id_consejos' AND extra LIKE '%auto_increment%')";
    $uuid_ok = "(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = $esquema AND table_name = 'usuarios' AND column_name = 'uuid' AND is_nullable = 'NO' AND character_maximum_length = 36 AND data_type IN ('char', 'character'))";

    return $db->fila(
        "SELECT
            (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = $esquema AND (" . implode(' OR ', $condiciones) . ")) AS columnas,
            " . $col('categorias', 'Icono_tipo', 'COUNT(*)') . " AS icono_tipo_viejo,
            " . $col('categorias', 'color', 'is_nullable') . " AS color_nullable,
            " . $col('metas_ahorro', 'monto_objetivo', 'numeric_scale') . " AS escala_metas,
            $uuid_ok AS uuid_ok,
            $uuid_unico AS uuid_unico,
            $consejos_ai AS consejos_autoincremental,
            (SELECT COUNT(*) FROM categorias) AS categorias"
    );
}

function aplicarMigraciones(BaseDatos $db): void
{
    $pg = $db->esPostgres();

    // 1. Base de devulises: el ícono se llamaba "Icono_tipo" (NOT NULL) y el código usa "icono".
    //    Va primero para que el paso 2 no cree un "icono" vacío al lado.
    if ($db->columna('categorias', 'Icono_tipo') !== null) {
        if ($db->columna('categorias', 'icono') !== null) {
            $db->ejecutar("UPDATE categorias SET icono = " . ($pg ? '"Icono_tipo"' : 'Icono_tipo') . " WHERE (icono IS NULL OR icono = '')");
            $db->ejecutar($pg ? 'ALTER TABLE categorias DROP COLUMN "Icono_tipo"' : 'ALTER TABLE categorias DROP COLUMN Icono_tipo');
        } elseif ($pg) {
            $db->ejecutar('ALTER TABLE categorias RENAME COLUMN "Icono_tipo" TO icono');
            $db->ejecutar('ALTER TABLE categorias ALTER COLUMN icono DROP NOT NULL');
        } else {
            $db->ejecutar('ALTER TABLE categorias CHANGE Icono_tipo icono VARCHAR(255) DEFAULT NULL');
        }
    }

    // 2. Columnas que se fueron agregando (solo agregan, nunca borran)
    foreach (sqlColumnasNuevas($pg) as $sql) {
        $db->ejecutar($sql);
    }

    // 3. color era NOT NULL en la base de devulises; el código lo trata como opcional
    if (($db->columna('categorias', 'color')['is_nullable'] ?? 'YES') === 'NO') {
        $db->ejecutar($pg ? 'ALTER TABLE categorias ALTER COLUMN color DROP NOT NULL'
                          : 'ALTER TABLE categorias MODIFY color VARCHAR(7) DEFAULT NULL');
    }

    // 4. Metas sin centavos (DECIMAL(10,0)) -> DECIMAL(12,2)
    if ((int)($db->columna('metas_ahorro', 'monto_objetivo')['numeric_scale'] ?? 2) === 0) {
        $db->ejecutar($pg
            ? 'ALTER TABLE metas_ahorro ALTER COLUMN monto_objetivo TYPE NUMERIC(12,2), ALTER COLUMN monto_actual TYPE NUMERIC(12,2), ALTER COLUMN monto_actual SET DEFAULT 0'
            : 'ALTER TABLE metas_ahorro MODIFY monto_objetivo DECIMAL(12,2) NOT NULL, MODIFY monto_actual DECIMAL(12,2) NOT NULL DEFAULT 0');
    }

    // 5. uuid: se completa a los usuarios que no lo tienen y pasa a CHAR(36) NOT NULL UNIQUE
    foreach ($db->filas("SELECT id_usuario FROM usuarios WHERE uuid IS NULL OR uuid = '' OR CHAR_LENGTH(uuid) <> 36") as $u) {
        $db->ejecutar("UPDATE usuarios SET uuid = ? WHERE id_usuario = ?", [generarUuid(), $u['id_usuario']]);
    }
    if ($pg) {
        $db->ejecutar('ALTER TABLE usuarios ALTER COLUMN uuid TYPE CHAR(36), ALTER COLUMN uuid SET NOT NULL');
        $db->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS usuarios_uuid_key ON usuarios (uuid)');
    } else {
        $db->ejecutar('ALTER TABLE usuarios MODIFY uuid CHAR(36) NOT NULL');
        $db->ejecutar('ALTER TABLE usuarios ADD UNIQUE INDEX IF NOT EXISTS uuid (uuid)');
    }

    // 6. consejos_ia.id_consejos sin autoincremento (en las dos bases de origen)
    if ($pg) {
        $default = $db->columna('consejos_ia', 'id_consejos')['column_default'] ?? '';
        if (!str_starts_with((string)$default, 'nextval')) {
            $db->ejecutar('CREATE SEQUENCE IF NOT EXISTS consejos_ia_id_consejos_seq OWNED BY consejos_ia.id_consejos');
            $db->ejecutar("ALTER TABLE consejos_ia ALTER COLUMN id_consejos SET DEFAULT nextval('consejos_ia_id_consejos_seq')");
            $db->valor("SELECT setval('consejos_ia_id_consejos_seq', COALESCE((SELECT MAX(id_consejos) FROM consejos_ia), 0) + 1, false)");
        }
    } else {
        $db->ejecutar('ALTER TABLE consejos_ia MODIFY id_consejos INT(11) NOT NULL AUTO_INCREMENT');
    }

    // 7. Los usuarios que ya existían reciben un @usuario generado desde su email
    foreach ($db->filas("SELECT id_usuario, email FROM usuarios WHERE nombre_usuario IS NULL") as $u) {
        $db->ejecutar("UPDATE usuarios SET nombre_usuario = ? WHERE id_usuario = ?", [generarNombreUsuario($db, $u['email']), $u['id_usuario']]);
    }
}

function sqlColumnasNuevas(bool $pg): array
{
    if ($pg) {
        return [
            "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS uuid CHAR(36)",
            "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificado SMALLINT NOT NULL DEFAULT 0",
            "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS codigo_verificacion VARCHAR(10)",
            "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS codigo_expiracion TIMESTAMP",
            "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS google_id VARCHAR(255)",
            "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS foto_perfil VARCHAR(255)",
            "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS moneda_principal VARCHAR(3) NOT NULL DEFAULT 'ARS'",
            "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS nombre_usuario VARCHAR(30)",
            "CREATE UNIQUE INDEX IF NOT EXISTS uq_usuarios_nombre_usuario ON usuarios (nombre_usuario)",
            "DO \$\$ BEGIN CREATE TYPE estado_meta AS ENUM ('activo', 'inactivo', 'logrado'); EXCEPTION WHEN duplicate_object THEN NULL; END \$\$",
            "ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255)",
            "ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS estado estado_meta DEFAULT 'activo'",
            "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS icono VARCHAR(255)",
            "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS color VARCHAR(7)",
            "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS id_usuario INTEGER",
            "CREATE INDEX IF NOT EXISTS idx_categorias_usuario ON categorias (id_usuario)",
            "ALTER TABLE movimientos ADD COLUMN IF NOT EXISTS moneda VARCHAR(3) NOT NULL DEFAULT 'ARS'",
        ];
    }
    return [
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS uuid VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS codigo_verificacion VARCHAR(10) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS codigo_expiracion DATETIME DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS Foto_perfil VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS moneda_principal ENUM('ARS','USD') NOT NULL DEFAULT 'ARS'",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS nombre_usuario VARCHAR(30) DEFAULT NULL",
        "ALTER TABLE usuarios ADD UNIQUE INDEX IF NOT EXISTS uq_usuarios_nombre_usuario (nombre_usuario)",
        "ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS estado ENUM('activo', 'inactivo', 'logrado') DEFAULT 'activo'",
        "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS icono VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT NULL",
        "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS id_usuario INT(11) DEFAULT NULL",
        "ALTER TABLE categorias ADD INDEX IF NOT EXISTS idx_categorias_usuario (id_usuario)",
        "ALTER TABLE movimientos ADD COLUMN IF NOT EXISTS moneda VARCHAR(3) NOT NULL DEFAULT 'ARS'",
    ];
}

// Categorías del sistema (base nueva o vacía). Solo inserta las que falten.
function crearCategoriasPredeterminadas(BaseDatos $db): void
{
    $predeterminadas = [
        'ingreso' => ["Sueldo", "Préstamo recibido", "Reintegro", "Ventas", "Inversiones", "Intereses", "Regalos", "Devoluciones", "Freelance / trabajos extra", "Becas / subsidios", "Otros ingresos"],
        'gasto'   => ["Alimentos", "Transporte", "Vivienda", "Salud", "Educación", "Entretenimiento", "Compras personales", "Deudas", "Impuestos", "Mascotas", "Suscripciones", "Regalos / donaciones", "Ropa", "Tecnología", "Viajes", "Otros gastos"],
    ];
    foreach ($predeterminadas as $tipo => $nombres) {
        foreach ($nombres as $nombre) {
            obtenerCategoriaSistema($db, $nombre, $tipo); // la crea si no existe
        }
    }
}
