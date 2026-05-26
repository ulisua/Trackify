<?php
session_start();
require_once '../conexion.php';

define('GOOGLE_CLIENT_ID',     getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI',  'http://localhost/trackify/auth/google/callback.php');

// ── 1. Verificar state (protección CSRF) ──────────────────────────────────────
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die('Estado inválido. Posible ataque CSRF.');
}
unset($_SESSION['oauth_state']);

// ── 2. Verificar que Google no devolvió error ─────────────────────────────────
if (isset($_GET['error'])) {
    header('Location: ../login.php?error=google_cancelado');
    exit();
}

if (!isset($_GET['code'])) {
    header('Location: ../login.php?error=sin_codigo');
    exit();
}

// ── 3. Intercambiar código por access token ───────────────────────────────────
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
    header('Location: ../login.php?error=token_fallido');
    exit();
}

// ── 4. Obtener datos del usuario de Google ────────────────────────────────────
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_response['access_token']]);
$user_info = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($user_info['id']) || !isset($user_info['email'])) {
    header('Location: ../login.php?error=datos_google');
    exit();
}

$google_id = $user_info['id'];
$email     = $user_info['email'];
$nombre    = $user_info['name'] ?? explode('@', $email)[0];
$foto      = $user_info['picture'] ?? '';

// ── 5. Buscar usuario por google_id ──────────────────────────────────────────
$stmt = $conn->prepare("SELECT id_usuario, nombre FROM usuarios WHERE google_id = ?");
$stmt->bind_param("s", $google_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    // Ya existe con google_id → iniciar sesión
    $_SESSION['usuario_id']     = $user['id_usuario'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    header('Location: ../index.php');
    exit();
}

// ── 6. Buscar por email (cuenta existente sin Google vinculado) ───────────────
$stmt = $conn->prepare("SELECT id_usuario, nombre FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    // Vincular google_id a la cuenta existente
    $stmt = $conn->prepare("UPDATE usuarios SET google_id = ?, Foto_perfil = ? WHERE id_usuario = ?");
    $stmt->bind_param("ssi", $google_id, $foto, $user['id_usuario']);
    $stmt->execute();

    $_SESSION['usuario_id']     = $user['id_usuario'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    header('Location: ../index.php');
    exit();
}

// ── 7. Usuario nuevo → crear cuenta ──────────────────────────────────────────
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$stmt = $conn->prepare("INSERT INTO usuarios (nombre, Foto_perfil, email, clave, fecha_registro, uuid, google_id) VALUES (?, ?, ?, NULL, NOW(), ?, ?)");
$stmt->bind_param("sssss", $nombre, $foto, $email, $uuid, $google_id);
$stmt->execute();

$nuevo_id = $conn->insert_id;
$_SESSION['usuario_id']     = $nuevo_id;
$_SESSION['usuario_nombre'] = $nombre;
header('Location: ../index.php');
exit();
?>
