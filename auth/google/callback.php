<?php
require_once dirname(__DIR__, 2) . '/conexion.php';

// El parámetro state es obligatorio y debe coincidir con el generado en login.php (anti-CSRF)
$state_esperado = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']);
if ($state_esperado === '' || !hash_equals($state_esperado, (string)($_GET['state'] ?? '')) || isset($_GET['error'])) {
    redirigir('login.php?error=google_cancelado');
}
if (!isset($_GET['code'])) {
    redirigir('login.php?error=sin_codigo');
}

// GET o POST a Google; devuelve el JSON decodificado o null
function pedirGoogle($url, array $cabeceras, $cuerpo = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => $cabeceras,
    ]);
    if ($cuerpo !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $cuerpo);
    }
    $respuesta = curl_exec($ch);
    if ($respuesta === false) {
        error_log('Trackify: error de conexión con Google: ' . curl_error($ch));
    }
    curl_close($ch);
    return $respuesta === false ? null : json_decode($respuesta, true);
}

$token_response = pedirGoogle('https://oauth2.googleapis.com/token', ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
    'code'          => $_GET['code'],
    'client_id'     => env('GOOGLE_CLIENT_ID', ''),
    'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
    'redirect_uri'  => env('GOOGLE_REDIRECT_URI', 'http://localhost/trackify/auth/google/callback.php'),
    'grant_type'    => 'authorization_code',
]));

if (!isset($token_response['access_token'])) {
    error_log('Trackify: Google no devolvió access_token (¿falta GOOGLE_CLIENT_SECRET en .env?): ' . json_encode($token_response));
    redirigir('login.php?error=token_fallido');
}

$user_info = pedirGoogle('https://www.googleapis.com/oauth2/v2/userinfo', ['Authorization: Bearer ' . $token_response['access_token']]);

if (!isset($user_info['id'], $user_info['email'])) {
    redirigir('login.php?error=datos_google');
}
// Solo se vincula/crea una cuenta con un email que Google confirma como verificado
if (empty($user_info['verified_email'])) {
    redirigir('login.php?error=email_no_verificado');
}

$google_id = $user_info['id'];
$email     = $user_info['email'];
$nombre    = mb_substr($user_info['name'] ?? explode('@', $email)[0], 0, 50);
$foto      = $user_info['picture'] ?? '';

// Busca primero por google_id y después por email
$stmt = $conn->prepare("SELECT id_usuario, nombre, google_id FROM usuarios WHERE google_id = ? OR email = ? ORDER BY google_id = ? DESC LIMIT 1");
$stmt->bind_param("sss", $google_id, $email, $google_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    if ($user['google_id'] !== $google_id) {
        // Cuenta existente con ese email: Google lo verificó, se vincula y se marca como verificada
        $stmt = $conn->prepare("UPDATE usuarios SET google_id = ?, Foto_perfil = ?, email_verificado = 1 WHERE id_usuario = ?");
        $stmt->bind_param("ssi", $google_id, $foto, $user['id_usuario']);
        $stmt->execute();
    }
    iniciarSesionUsuario($user['id_usuario'], $user['nombre']);
    redirigir('index.php');
}

// clave es NOT NULL: las cuentas de Google se crean sin contraseña ('')
$uuid = generarUuid();
$nombre_usuario = generarNombreUsuario($conn, $email);
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, nombre_usuario, Foto_perfil, email, clave, fecha_registro, uuid, google_id, email_verificado) VALUES (?, ?, ?, ?, '', NOW(), ?, ?, 1)");
$stmt->bind_param("ssssss", $nombre, $nombre_usuario, $foto, $email, $uuid, $google_id);
$stmt->execute();

iniciarSesionUsuario($conn->insert_id, $nombre);
redirigir('index.php');
