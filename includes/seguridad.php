<?php
// includes/seguridad.php
// Utilidades de seguridad compartidas: token CSRF, URL de retorno segura,
// validación de montos/fechas y escape de argumentos JS dentro de atributos HTML.
// Se incluye desde conexion.php, por lo que está disponible en todas las páginas.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Toda petición POST de un usuario autenticado debe traer el token,
// ya sea en el campo csrf_token (formularios) o en la cabecera X-CSRF-Token (fetch).
function verificarCsrfSiCorresponde() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_SESSION['usuario_id'])) {
        return;
    }
    $enviado = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($enviado) || $enviado === '' || !hash_equals(csrfToken(), $enviado)) {
        http_response_code(403);
        $esJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
            || isset($_SERVER['HTTP_X_CSRF_TOKEN']);
        if ($esJson) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Sesión vencida. Recargá la página.']);
        } else {
            echo 'Sesión vencida o solicitud inválida. Volvé atrás y recargá la página.';
        }
        exit();
    }
}

// Ruta base de la app (funciona en la raíz del servidor o en una subcarpeta como /trackify),
// también cuando el script que corre está dentro de /includes o /api.
function urlBaseApp() {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (in_array(basename($dir), ['includes', 'api'], true)) {
        $dir = str_replace('\\', '/', dirname($dir));
    }
    return rtrim($dir, '/');
}

// Solo acepta rutas relativas del propio sitio; cualquier URL externa vuelve al dashboard.
function urlRetornoSegura($url, $porDefecto = null) {
    $porDefecto = urlBaseApp() . '/' . ($porDefecto ?? 'index.php');
    $url = trim((string)$url);
    if ($url === '') {
        return $porDefecto;
    }

    $partes = parse_url($url);
    if ($partes === false) {
        return $porDefecto;
    }

    // Referer absoluto del mismo host: conservar solo ruta + query
    if (isset($partes['host'])) {
        $hostActual = $_SERVER['HTTP_HOST'] ?? '';
        $hostUrl = $partes['host'] . (isset($partes['port']) ? ':' . $partes['port'] : '');
        if ($hostActual === '' || strcasecmp($hostUrl, $hostActual) !== 0) {
            return $porDefecto;
        }
        $url = ($partes['path'] ?? '/') . (isset($partes['query']) ? '?' . $partes['query'] : '');
    } elseif (isset($partes['scheme'])) {
        return $porDefecto;
    }

    if (str_starts_with($url, '//') || str_contains($url, '\\') || preg_match('/[\r\n]/', $url)) {
        return $porDefecto;
    }
    return $url;
}

// Monto positivo con hasta 2 decimales. Devuelve float o null si es inválido.
function montoValido($valor, $maximo = 9999999999.99) {
    if (is_string($valor)) {
        $valor = trim(str_replace(',', '.', $valor));
    }
    if (!is_numeric($valor)) {
        return null;
    }
    $monto = round((float)$valor, 2);
    if (!is_finite($monto) || $monto <= 0 || $monto > $maximo) {
        return null;
    }
    return $monto;
}

// Fecha exacta en formato Y-m-d. Devuelve el string o null.
function fechaValida($valor) {
    if (!is_string($valor)) {
        return null;
    }
    $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
    return ($fecha && $fecha->format('Y-m-d') === $valor) ? $valor : null;
}

// Argumento JS seguro para usar dentro de onclick="fn(...)".
function jsAttr($valor) {
    return htmlspecialchars(
        json_encode($valor, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Mensajes de una sola lectura (Post/Redirect/Get) que footer.php muestra con SweetAlert2
function flash($mensaje, $tipo = 'error') {
    $_SESSION['flash'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
}

verificarCsrfSiCorresponde();
