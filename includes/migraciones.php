<?php
// includes/migraciones.php
// Lleva cualquier base PostgreSQL de Trackify (de cualquier integrante y de cualquier versión
// anterior) al esquema de trackify_postgres.sql.
//
// Se hace UNA sola consulta para ver si el esquema está al día (y se recuerda en la sesión unos
// minutos). Solo si falta algo se ejecutan los cambios. Todas las migraciones son seguras de repetir.
//
// Para agregar una columna nueva:
//   1. Sumarla a COLUMNAS_ESPERADAS (en minúscula).
//   2. Agregar su ALTER ... ADD COLUMN IF NOT EXISTS en SQL_COLUMNAS_NUEVAS.
//   3. Agregarla también en trackify_postgres.sql.

const COLUMNAS_ESPERADAS = [
    'usuarios'     => ['uuid', 'email_verificado', 'codigo_verificacion', 'codigo_expiracion', 'google_id', 'foto_perfil', 'moneda_principal', 'nombre_usuario'],
    'metas_ahorro' => ['descripcion', 'estado'],
    'categorias'   => ['icono', 'color', 'id_usuario'],
    'movimientos'  => ['moneda'],
];

const SQL_COLUMNAS_NUEVAS = [
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

const INDICE_MOVIMIENTOS_FECHA = 'idx_movimientos_usuario_fecha';

// Cada cuánto se vuelve a revisar el esquema dentro de una misma sesión
// (las consultas a information_schema son lentas para hacerlas en cada request).
const MIGRACION_REVISAR_CADA = 300; // segundos

function migrarEsquema(BaseDatos $db): void
{
    // La marca depende de la base: cambiar la base en .env fuerza a revisar
    $clave = 'esquema_ok_' . md5(env('DATABASE_URL', '') . '|' . env('DB_HOST', '') . '|' . env('DB_NAME', ''));
    if (session_status() === PHP_SESSION_ACTIVE && ($_SESSION[$clave] ?? 0) > time() - MIGRACION_REVISAR_CADA) {
        return;
    }

    $estado = estadoEsquema($db);
    if (!esquemaAlDia($estado)) {
        aplicarMigraciones($db);
    }
    if ((int)$estado['categorias'] <= 5) {
        crearCategoriasPredeterminadas($db);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[$clave] = time();
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
        && (int)$estado['consejos_autoincremental'] === 1
        && (int)$estado['indice_usuario_fecha'] > 0
        && (int)$estado['indice_usuario_viejo'] === 0;
}

// Una sola consulta con todo lo que hay que revisar
function estadoEsquema(BaseDatos $db): array
{
    $condiciones = [];
    foreach (COLUMNAS_ESPERADAS as $tabla => $columnas) {
        $condiciones[] = "(table_name = '$tabla' AND LOWER(column_name) IN ('" . implode("','", $columnas) . "'))";
    }
    $col = fn($tabla, $columna, $campo) =>
        "(SELECT $campo FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = '$tabla' AND column_name = '$columna')";
    $indice = fn($nombre) =>
        "(SELECT COUNT(*) FROM pg_indexes WHERE schemaname = current_schema() AND indexname = '$nombre')";

    return $db->fila(
        "SELECT
            (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = current_schema() AND (" . implode(' OR ', $condiciones) . ")) AS columnas,
            " . $col('categorias', 'Icono_tipo', 'COUNT(*)') . " AS icono_tipo_viejo,
            " . $col('categorias', 'color', 'is_nullable') . " AS color_nullable,
            " . $col('metas_ahorro', 'monto_objetivo', 'numeric_scale') . " AS escala_metas,
            (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'usuarios' AND column_name = 'uuid'
                AND is_nullable = 'NO' AND character_maximum_length = 36 AND data_type IN ('char', 'character')) AS uuid_ok,
            (SELECT COUNT(*) FROM pg_indexes WHERE schemaname = current_schema() AND tablename = 'usuarios' AND indexdef LIKE 'CREATE UNIQUE INDEX%(uuid)') AS uuid_unico,
            (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'consejos_ia' AND column_name = 'id_consejos'
                AND (column_default LIKE 'nextval%' OR is_identity = 'YES')) AS consejos_autoincremental,
            " . $indice(INDICE_MOVIMIENTOS_FECHA) . " AS indice_usuario_fecha,
            " . $indice('idx_movimientos_id_usuario') . " AS indice_usuario_viejo,
            (SELECT COUNT(*) FROM categorias) AS categorias"
    );
}

function aplicarMigraciones(BaseDatos $db): void
{
    // 1. Base de devulises: el ícono se llamaba "Icono_tipo" (NOT NULL) y el código usa "icono".
    //    Va primero para que el paso 2 no cree un "icono" vacío al lado.
    if ($db->columna('categorias', 'Icono_tipo') !== null) {
        if ($db->columna('categorias', 'icono') !== null) {
            $db->ejecutar("UPDATE categorias SET icono = \"Icono_tipo\" WHERE icono IS NULL OR icono = ''");
            $db->ejecutar('ALTER TABLE categorias DROP COLUMN "Icono_tipo"');
        } else {
            $db->ejecutar('ALTER TABLE categorias RENAME COLUMN "Icono_tipo" TO icono');
            $db->ejecutar('ALTER TABLE categorias ALTER COLUMN icono DROP NOT NULL');
        }
    }

    // 2. Columnas que se fueron agregando (solo agregan, nunca borran)
    foreach (SQL_COLUMNAS_NUEVAS as $sql) {
        $db->ejecutar($sql);
    }

    // 3. color era NOT NULL en la base de devulises; el código lo trata como opcional
    if (($db->columna('categorias', 'color')['is_nullable'] ?? 'YES') === 'NO') {
        $db->ejecutar('ALTER TABLE categorias ALTER COLUMN color DROP NOT NULL');
    }

    // 4. Metas sin centavos (NUMERIC(10,0)) -> NUMERIC(12,2)
    if ((int)($db->columna('metas_ahorro', 'monto_objetivo')['numeric_scale'] ?? 2) === 0) {
        $db->ejecutar('ALTER TABLE metas_ahorro ALTER COLUMN monto_objetivo TYPE NUMERIC(12,2), ALTER COLUMN monto_actual TYPE NUMERIC(12,2), ALTER COLUMN monto_actual SET DEFAULT 0');
    }

    // 5. uuid: se completa a los usuarios que no lo tienen y pasa a CHAR(36) NOT NULL UNIQUE
    foreach ($db->filas("SELECT id_usuario FROM usuarios WHERE uuid IS NULL OR uuid = '' OR CHAR_LENGTH(uuid) <> 36") as $u) {
        $db->ejecutar("UPDATE usuarios SET uuid = ? WHERE id_usuario = ?", [generarUuid(), $u['id_usuario']]);
    }
    $db->ejecutar('ALTER TABLE usuarios ALTER COLUMN uuid TYPE CHAR(36), ALTER COLUMN uuid SET NOT NULL');
    $db->ejecutar('CREATE UNIQUE INDEX IF NOT EXISTS usuarios_uuid_key ON usuarios (uuid)');

    // 6. consejos_ia.id_consejos sin autoincremento
    $default = $db->columna('consejos_ia', 'id_consejos')['column_default'] ?? '';
    if (!str_starts_with((string)$default, 'nextval')) {
        $db->ejecutar('CREATE SEQUENCE IF NOT EXISTS consejos_ia_id_consejos_seq OWNED BY consejos_ia.id_consejos');
        $db->ejecutar("ALTER TABLE consejos_ia ALTER COLUMN id_consejos SET DEFAULT nextval('consejos_ia_id_consejos_seq')");
        $db->valor("SELECT setval('consejos_ia_id_consejos_seq', COALESCE((SELECT MAX(id_consejos) FROM consejos_ia), 0) + 1, false)");
    }

    // 7. Índice compuesto para las consultas por usuario + rango de fechas (dashboard, listados, filtros).
    //    El índice viejo solo por id_usuario sobra: el compuesto empieza por id_usuario.
    $db->ejecutar('CREATE INDEX IF NOT EXISTS ' . INDICE_MOVIMIENTOS_FECHA . ' ON movimientos (id_usuario, fecha)');
    $db->ejecutar('DROP INDEX IF EXISTS idx_movimientos_id_usuario');

    // 8. Los usuarios que ya existían reciben un @usuario generado desde su email
    foreach ($db->filas("SELECT id_usuario, email FROM usuarios WHERE nombre_usuario IS NULL") as $u) {
        $db->ejecutar("UPDATE usuarios SET nombre_usuario = ? WHERE id_usuario = ?", [generarNombreUsuario($db, $u['email']), $u['id_usuario']]);
    }
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
