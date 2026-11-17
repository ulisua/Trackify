<?php
// Conexión a PostgreSQL. Los datos se leen de .env (ver .env.example):
//   DATABASE_URL=postgresql://usuario:clave@host:5432/base   (la que da Render/Neon/Supabase), o bien
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS por separado.
//   DB_SSLMODE=require para bases en la nube (en local se deja vacío).
// Requiere extension=pdo_pgsql en php.ini.
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/includes/db.php';

$url_bd = parse_url((string)env('DATABASE_URL', '')) ?: [];
try {
    $conn = new BaseDatos(
        $url_bd['host'] ?? env('DB_HOST', 'localhost'),
        isset($url_bd['port']) ? (string)$url_bd['port'] : env('DB_PORT', '5432'),
        isset($url_bd['path']) ? ltrim($url_bd['path'], '/') : env('DB_NAME', 'trackify'),
        isset($url_bd['user']) ? urldecode($url_bd['user']) : env('DB_USER', 'postgres'),
        isset($url_bd['pass']) ? urldecode($url_bd['pass']) : env('DB_PASS', ''),
        env('DB_SSLMODE')
    );
} catch (PDOException $e) {
    error_log('Trackify: error de conexión a PostgreSQL: ' . $e->getMessage());
    http_response_code(500);
    die(extension_loaded('pdo_pgsql')
        ? 'No se pudo conectar con PostgreSQL. Verificá que el servidor esté iniciado y los datos DB_* de .env.'
        : 'Falta la extensión pdo_pgsql de PHP: habilitá extension=pdo_pgsql en php.ini y reiniciá Apache.');
}
unset($url_bd);

require_once __DIR__ . '/includes/categorias_meta.php';
require_once __DIR__ . '/includes/seguridad.php';
require_once __DIR__ . '/includes/assets.php';

// Migraciones automáticas: llevan cualquier base vieja al esquema actual (ver includes/migraciones.php)
require_once __DIR__ . '/includes/migraciones.php';
migrarEsquema($conn);
