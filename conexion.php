<?php
// Conexión a la base de datos. El motor se elige en .env:
//   DB_DRIVER=mysql  (por defecto: MySQL/MariaDB de XAMPP)
//   DB_DRIVER=pgsql  (PostgreSQL; requiere extension=pdo_pgsql en php.ini)
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/includes/db.php';

$motor_bd = strtolower((string)env('DB_DRIVER', 'mysql')) === 'pgsql' ? 'pgsql' : 'mysql';
try {
    $conn = new BaseDatos(
        $motor_bd,
        env('DB_HOST', 'localhost'),
        env('DB_PORT'),
        env('DB_NAME', 'trackify'),
        env('DB_USER', $motor_bd === 'pgsql' ? 'postgres' : 'root'),
        env('DB_PASS', '')
    );
} catch (PDOException $e) {
    error_log('Trackify: error de conexión a la base de datos (' . $motor_bd . '): ' . $e->getMessage());
    http_response_code(500);
    die($motor_bd === 'pgsql'
        ? 'No se pudo conectar con PostgreSQL. Verificá que el servidor esté iniciado, los datos DB_* de .env y que la extensión pdo_pgsql esté habilitada en php.ini.'
        : 'No se pudo conectar con la base de datos. Verificá que MySQL esté iniciado.');
}
unset($motor_bd);

require_once __DIR__ . '/includes/categorias_meta.php';
require_once __DIR__ . '/includes/seguridad.php';

// Migraciones automáticas: llevan cualquier base vieja al esquema actual (ver includes/migraciones.php)
require_once __DIR__ . '/includes/migraciones.php';
migrarEsquema($conn);
