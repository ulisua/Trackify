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

// Generar nuevo código y nueva expiración
$codigo     = strval(rand(100000, 999999));
$expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$stmt = $conn->prepare("UPDATE usuarios SET codigo_verificacion=?, codigo_expiracion=? WHERE id_usuario=?");
$stmt->bind_param("ssi", $codigo, $expiracion, $user_id);
$stmt->execute();

// Reenviar email
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth   = true;
    $mail->Username   = '6e724e2630cb1b';
    $mail->Password   = 'b4ad1521076737';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 2525;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('noreply@trackify.com', 'Trackify');
    $mail->addAddress($email, $nombre);
    $mail->Subject = 'Nuevo código de verificación - Trackify';
    $mail->isHTML(true);
    $mail->Body = "
        <div style='font-family:Inter,sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f8fafc;border-radius:12px;'>
            <img src='http://localhost/trackify/logo.png' alt='Trackify' style='height:60px;margin-bottom:24px;'>
            <h2 style='color:#1E1B26;margin:0 0 8px;'>Nuevo código de verificación</h2>
            <p style='color:#64748b;margin:0 0 24px;'>Tu código anterior venció. Usá este nuevo código. Vence en 15 minutos.</p>
            <div style='background:#084734;color:#CFF27C;font-size:2rem;font-weight:700;letter-spacing:12px;text-align:center;padding:20px;border-radius:8px;margin-bottom:24px;'>
                $codigo
            </div>
            <p style='color:#94a3b8;font-size:.85rem;margin:0;'>Si no creaste una cuenta en Trackify, ignorá este email.</p>
        </div>
    ";

    $mail->send();
    header("Location: verificar_email.php?reenviado=1");
    exit();

} catch (Exception $e) {
    header("Location: verificar_email.php?error=envio");
    exit();
}
?>
