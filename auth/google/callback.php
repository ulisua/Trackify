<?php
session_start();
require_once '../../conexion.php';
require_once '../../env.php';

define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/trackify/auth/google/callback.php');

// El parámetro state es obligatorio y debe coincidir con el generado en login.php (anti-CSRF)
$state_esperado = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']);
if ($state_esperado === '' || !hash_equals($state_esperado, (string)($_GET['state'] ?? ''))) {
    header('Location: ../../login.php?error=google_cancelado');
    exit();
}

if (isset($_GET['error'])) {
    header('Location: ../../login.php?error=google_cancelado');
    exit();
}

if (!isset($_GET['code'])) {
    header('Location: ../../login.php?error=sin_codigo');
    exit();
}

$token_url  = 'https://oauth2.googleapis.com/token';
$token_data = http_build_query([
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $token_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$token_response = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($token_response['access_token'])) {
    header('Location: ../../login.php?error=token_fallido');
    exit();
}

$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_response['access_token']]);
$user_info = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($user_info['id']) || !isset($user_info['email'])) {
    header('Location: ../../login.php?error=datos_google');
    exit();
}

// Solo se vincula/crea una cuenta con un email que Google confirma como verificado
if (empty($user_info['verified_email'])) {
    header('Location: ../../login.php?error=email_no_verificado');
    exit();
}

$google_id = $user_info['id'];
$email     = $user_info['email'];
$nombre    = mb_substr($user_info['name'] ?? explode('@', $email)[0], 0, 50);
$foto      = $user_info['picture'] ?? '';

function iniciarSesionGoogle($id, $nombre) {
    session_regenerate_id(true);
    $_SESSION['usuario_id']     = $id;
    $_SESSION['usuario_nombre'] = $nombre;
    header('Location: ../../index.php');
    exit();
}

$stmt = $conn->prepare("SELECT id_usuario, nombre FROM usuarios WHERE google_id = ?");
$stmt->bind_param("s", $google_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    iniciarSesionGoogle($user['id_usuario'], $user['nombre']);
}

$stmt = $conn->prepare("SELECT id_usuario, nombre FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    // Google verificó el email: se vincula la cuenta y se marca como verificada
    $stmt = $conn->prepare("UPDATE usuarios SET google_id = ?, Foto_perfil = ?, email_verificado = 1 WHERE id_usuario = ?");
    $stmt->bind_param("ssi", $google_id, $foto, $user['id_usuario']);
    $stmt->execute();
    iniciarSesionGoogle($user['id_usuario'], $user['nombre']);
}

$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    random_int(0, 0xffff), random_int(0, 0xffff),
    random_int(0, 0xffff),
    random_int(0, 0x0fff) | 0x4000,
    random_int(0, 0x3fff) | 0x8000,
    random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
);

// clave es NOT NULL: las cuentas de Google se crean sin contraseña ('')
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, Foto_perfil, email, clave, fecha_registro, uuid, google_id, email_verificado) VALUES (?, ?, ?, '', NOW(), ?, ?, 1)");
$stmt->bind_param("sssss", $nombre, $foto, $email, $uuid, $google_id);
$stmt->execute();

iniciarSesionGoogle($conn->insert_id, $nombre);
