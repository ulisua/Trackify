<?php
// includes/mailer.php
// Envío del código de verificación por email. Las credenciales SMTP se leen de .env
// (ver .env.example); nunca deben escribirse en el código.

require_once dirname(__DIR__) . '/env.php';
require_once dirname(__DIR__) . '/phpmailer/PHPMailer.php';
require_once dirname(__DIR__) . '/phpmailer/SMTP.php';
require_once dirname(__DIR__) . '/phpmailer/Exception.php';

function generarCodigoVerificacion() {
    return strval(random_int(100000, 999999));
}

// Devuelve true si el email se envió. Los errores se registran en el log, no se muestran.
function enviarCodigoVerificacion($email, $nombre, $codigo, $asunto, $introduccion) {
    $host = getenv('SMTP_HOST');
    $usuario = getenv('SMTP_USER');
    $clave = getenv('SMTP_PASS');
    if (!$host || !$usuario || !$clave) {
        error_log('Trackify: faltan SMTP_HOST / SMTP_USER / SMTP_PASS en .env');
        return false;
    }

    $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost/trackify', '/');
    $codigoHtml = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
    $introHtml = htmlspecialchars($introduccion, ENT_QUOTES, 'UTF-8');

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $usuario;
        $mail->Password   = $clave;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = (int)(getenv('SMTP_PORT') ?: 2525);
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(getenv('SMTP_FROM') ?: 'noreply@trackify.com', 'Trackify');
        $mail->addAddress($email, $nombre);
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family:Inter,sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f8fafc;border-radius:12px;'>
                <img src='{$appUrl}/logo.png' alt='Trackify' style='height:60px;margin-bottom:24px;'>
                <h2 style='color:#1E1B26;margin:0 0 8px;'>Verificá tu email</h2>
                <p style='color:#64748b;margin:0 0 24px;'>{$introHtml}</p>
                <div style='background:#084734;color:#CFF27C;font-size:2rem;font-weight:700;letter-spacing:12px;text-align:center;padding:20px;border-radius:8px;margin-bottom:24px;'>
                    {$codigoHtml}
                </div>
                <p style='color:#94a3b8;font-size:.85rem;margin:0;'>Si no fuiste vos, ignorá este email.</p>
            </div>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Trackify: error al enviar email: ' . $mail->ErrorInfo);
        return false;
    }
}
