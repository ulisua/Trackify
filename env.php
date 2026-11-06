<?php
// Carga .env (ver .env.example) en el entorno del proceso. Se ejecuta una sola vez.
if (!function_exists('env')) {
    $env_path = __DIR__ . '/.env';
    if (is_readable($env_path)) {
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents($env_path)); // BOM de Windows
        foreach (preg_split('/\r\n|\r|\n/', $contenido) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) continue;
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            // Quita comillas envolventes: CLAVE="valor" o CLAVE='valor'
            if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }
            putenv("$clave=$valor");
            $_ENV[$clave] = $valor;
        }
    }

    // Valor de configuración, o $porDefecto si no está definido o está vacío
    function env($clave, $porDefecto = null) {
        $valor = $_ENV[$clave] ?? getenv($clave);
        return ($valor === false || $valor === '') ? $porDefecto : $valor;
    }
}
