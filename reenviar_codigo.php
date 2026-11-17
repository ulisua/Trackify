<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['verificar_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['verificar_id'];
$email   = $_SESSION['verificar_email'];
$nombre  = $_SESSION['verificar_nombre'];

$codigo     = strval(rand(100000, 999999));
$expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$stmt = $conn->prepare("UPDATE usuarios SET codigo_verificacion=?, codigo_expiracion=? WHERE id_usuario=?");
$stmt->bind_param("ssi", $codigo, $expiracion, $user_id);
$stmt->execute();

require_once 'enviar_emailjs.php';

if (enviar_emailjs($email, $codigo)) {
    header("Location: verificar_email.php?reenviado=1");
    exit();
} else {
    header("Location: verificar_email.php?error=envio");
    exit();
}
?>
