<?php
// herramientas/migrar_mysql_a_postgres.php
// Copia todos los datos de una base MySQL/MariaDB vieja de Trackify a PostgreSQL.
// Se usa UNA vez por integrante, desde la consola (no desde el navegador):
//
//   C:\xampp\php\php.exe herramientas/migrar_mysql_a_postgres.php
//
// Origen (MySQL de XAMPP). Por defecto localhost / root / sin clave / base "trackify";
// se puede cambiar con opciones:  --host=  --puerto=  --usuario=  --clave=  --base=
// Destino (PostgreSQL): los datos DB_* (o DATABASE_URL) de .env, igual que la app.
//
// - El destino debe tener el esquema (trackify_postgres.sql importado) y no tener usuarios:
//   se vacía y se copian usuarios, categorías, metas, movimientos y consejos con sus mismos ids.
// - Acepta bases MySQL de cualquier versión del proyecto (Icono_tipo, Foto_perfil, sin @usuario...):
//   se copian las columnas que existen en los dos lados y al final se aplican las migraciones.
// - Todo va en una transacción: si algo falla, PostgreSQL queda como estaba.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Esta herramienta se ejecuta desde la consola.');
}

foreach (['pdo_mysql', 'pdo_pgsql'] as $ext) {
    if (!extension_loaded($ext)) {
        fwrite(STDERR, "Falta la extensión $ext: habilitá extension=$ext en php.ini.\n");
        exit(1);
    }
}

$raiz = dirname(__DIR__);
require_once $raiz . '/conexion.php'; // $conn = PostgreSQL de .env (y deja el esquema al día)

$opt = getopt('', ['host::', 'puerto::', 'usuario::', 'clave::', 'base::']);
$origen = [
    'host'    => $opt['host'] ?? 'localhost',
    'puerto'  => $opt['puerto'] ?? '3306',
    'usuario' => $opt['usuario'] ?? 'root',
    'clave'   => $opt['clave'] ?? '',
    'base'    => $opt['base'] ?? 'trackify',
];

try {
    $mysql = new PDO(
        "mysql:host={$origen['host']};port={$origen['puerto']};dbname={$origen['base']};charset=utf8mb4",
        $origen['usuario'], $origen['clave'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "No se pudo conectar a MySQL ({$origen['base']}): " . $e->getMessage() . "\n");
    exit(1);
}

// Orden de copia: primero las tablas a las que apuntan las claves foráneas
const TABLAS = [
    'usuarios'     => 'id_usuario',
    'categorias'   => 'id_categoria',
    'metas_ahorro' => 'id_meta',
    'movimientos'  => 'id_movimiento',
    'consejos_ia'  => 'id_consejos',
];
// Columnas que cambiaron de nombre entre versiones (origen en minúscula => destino)
const RENOMBRADAS = ['icono_tipo' => 'icono'];

$usuarios_destino = (int)$conn->valor('SELECT COUNT(*) FROM usuarios');
if ($usuarios_destino > 0 && !in_array('--forzar', $argv, true)) {
    fwrite(STDERR, "La base PostgreSQL ya tiene $usuarios_destino usuario(s). Para reemplazar todo su contenido\n"
        . "con los datos de MySQL, ejecutá de nuevo agregando --forzar\n");
    exit(1);
}

// Columnas del destino: nombre => [admite NULL, tipo, tiene valor por defecto]
function columnasDestino(BaseDatos $db, string $tabla): array
{
    $cols = [];
    foreach ($db->filas("SELECT column_name, is_nullable, data_type, column_default FROM information_schema.columns
                          WHERE table_schema = current_schema() AND table_name = ?", [$tabla]) as $c) {
        $cols[$c['column_name']] = [
            'nulo'    => $c['is_nullable'] === 'YES',
            'tipo'    => $c['data_type'],
            'defecto' => $c['column_default'] !== null,
        ];
    }
    return $cols;
}

$tablas_origen = array_map('strtolower', $mysql->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
$copiadas = [];

$conn->transaccion(function (BaseDatos $db) use ($mysql, $tablas_origen, &$copiadas) {
    $db->ejecutar('TRUNCATE ' . implode(', ', array_keys(TABLAS)) . ' RESTART IDENTITY CASCADE');

    foreach (TABLAS as $tabla => $pk) {
        $copiadas[$tabla] = 0;
        if (!in_array($tabla, $tablas_origen, true)) {
            continue;
        }
        $destino = columnasDestino($db, $tabla);
        $stmt = $mysql->query("SELECT * FROM `$tabla` ORDER BY `$pk`");

        while ($fila = $stmt->fetch()) {
            $fila = array_change_key_case($fila, CASE_LOWER);
            foreach (RENOMBRADAS as $viejo => $nuevo) {
                if (array_key_exists($viejo, $fila) && (($fila[$nuevo] ?? '') === '')) {
                    $fila[$nuevo] = $fila[$viejo];
                }
            }
            $valores = [];
            foreach (array_intersect_key($fila, $destino) as $c => $v) {
                // Fechas "cero" de MySQL no existen en PostgreSQL
                if (is_string($v) && str_starts_with($v, '0000-00-00')) {
                    $v = null;
                }
                if ($v === null && !$destino[$c]['nulo']) {
                    if ($destino[$c]['defecto']) {
                        continue; // se omite: toma el valor por defecto (p. ej. moneda 'ARS')
                    }
                    if (str_contains($destino[$c]['tipo'], 'char')) {
                        $v = ''; // p. ej. la clave de los usuarios que entraron con Google
                    }
                }
                $valores[$c] = $v;
            }
            $db->ejecutar('INSERT INTO ' . $tabla . ' (' . implode(', ', array_keys($valores)) . ') VALUES ('
                . implode(', ', array_fill(0, count($valores), '?')) . ')', array_values($valores));
            $copiadas[$tabla]++;
        }

        // Las secuencias siguen después del id más alto copiado
        $db->valor("SELECT setval(pg_get_serial_sequence('$tabla', '$pk'), COALESCE((SELECT MAX($pk) FROM $tabla), 0) + 1, false)");
    }

    // Completa lo que falte (uuid, @usuario, categorías predeterminadas...)
    aplicarMigraciones($db);
    crearCategoriasPredeterminadas($db);
});

echo "Migración completa de MySQL ({$origen['base']}) a PostgreSQL:\n";
foreach ($copiadas as $tabla => $n) {
    printf("  %-13s %d fila(s)\n", $tabla, $n);
}
