<?php
// Carga el archivo .env y define las variables como constantes
$env_path = __DIR__ . '/.env';

if (!file_exists($env_path)) return;

$lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue; // ignorar comentarios
    if (!strpos($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    putenv(trim($key) . '=' . trim($value));
}
?>
