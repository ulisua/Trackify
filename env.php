<?php
// Carga el archivo .env (debe estar en la misma carpeta que este archivo)
$env_path = __DIR__ . '/.env';

if (!file_exists($env_path)) {
    error_log('env.php: no se encontró ' . $env_path);
    return;
}

$lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $i => $line) {
    if ($i === 0) $line = preg_replace('/^\xEF\xBB\xBF/', '', $line); // quita BOM de UTF-8
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$key, $value] = explode('=', $line, 2);
    $key   = trim($key);
    $value = trim(trim($value), "\"'");
    putenv("$key=$value");
    $_ENV[$key] = $value;
}
