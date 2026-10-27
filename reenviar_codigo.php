<?php
session_start();
include 'conexion.php';
require_once 'includes/mailer.php';

if (!isset($_SESSION['verificar_id'])) {
    header("Location: login.php");
    exit();
}

// Un reenvío por minuto como máximo
if (time() - ($_SESSION['verificar_ultimo_envio'] ?? 0) < 60) {
    header("Location: verificar_email.php?error=espera");
    exit();
}

$user_id = $_SESSION['verificar_id'];
$email   = $_SESSION['verificar_email'];
$nombre  = $_SESSION['verificar_nombre'];

$codigo     = generarCodigoVerificacion();
$expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$stmt = $conn->prepare("UPDATE usuarios SET codigo_verificacion=?, codigo_expiracion=? WHERE id_usuario=?");
$stmt->bind_param("ssi", $codigo, $expiracion, $user_id);
$stmt->execute();

// Código nuevo: se reinicia el contador de intentos
$_SESSION['verificar_intentos'] = 0;
$_SESSION['verificar_ultimo_envio'] = time();

$enviado = enviarCodigoVerificacion($email, $nombre, $codigo, 'Nuevo código de verificación - Trackify', 'Usá este nuevo código para verificar tu cuenta. Vence en 15 minutos.');
header("Location: verificar_email.php?" . ($enviado ? 'reenviado=1' : 'error=envio'));
exit();
