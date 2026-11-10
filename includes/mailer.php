<?php
// includes/mailer.php
// Código de verificación por email. Las credenciales SMTP se leen de .env
// (ver .env.example); nunca deben escribirse en el código.

require_once dirname(__DIR__) . '/env.php';
require_once dirname(__DIR__) . '/phpmailer/PHPMailer.php';
require_once dirname(__DIR__) . '/phpmailer/SMTP.php';
require_once dirname(__DIR__) . '/phpmailer/Exception.php';

const CODIGO_MINUTOS_VALIDEZ = 15;
const CODIGO_SEGUNDOS_ENTRE_ENVIOS = 60;

// Deja al usuario en la pantalla de verificación. El código lo envía verificar_email.php al abrirse.
function iniciarVerificacionEmail($id, $email, $nombre) {
    session_regenerate_id(true);
    $_SESSION['verificar_id']     = (int)$id;
    $_SESSION['verificar_email']  = $email;
    $_SESSION['verificar_nombre'] = $nombre;
    unset($_SESSION['verificar_intentos'], $_SESSION['verificar_ultimo_envio'], $_SESSION['verificar_auto_enviado']);
}

// Segundos que faltan para poder pedir otro código (0 = ya se puede)
function segundosParaReenviar() {
    $ultimo = $_SESSION['verificar_ultimo_envio'] ?? 0;
    return max(0, CODIGO_SEGUNDOS_ENTRE_ENVIOS - (time() - $ultimo));
}

// Genera un código nuevo para el usuario en verificación, lo guarda y lo envía.
// Solo si el envío sale bien se activa la espera para reenviar: si falla, se puede reintentar enseguida.
function enviarNuevoCodigo($conn, $asunto, $introduccion) {
    $codigo     = strval(random_int(100000, 999999));
    $expiracion = date('Y-m-d H:i:s', time() + CODIGO_MINUTOS_VALIDEZ * 60);
    $id         = (int)$_SESSION['verificar_id'];

    $conn->ejecutar("UPDATE usuarios SET codigo_verificacion = ?, codigo_expiracion = ? WHERE id_usuario = ?", [$codigo, $expiracion, $id]);

    // Código nuevo: se reinicia el contador de intentos
    $_SESSION['verificar_intentos'] = 0;

    if (!enviarEmailCodigo($_SESSION['verificar_email'], $_SESSION['verificar_nombre'], $codigo, $asunto, $introduccion)) {
        unset($_SESSION['verificar_ultimo_envio']);
        return false;
    }
    $_SESSION['verificar_ultimo_envio'] = time();
    return true;
}

// Devuelve true si el email se envió. Los errores se registran en el log de PHP, no se muestran.
// Lo usan la verificación de cuenta y la creación de contraseña (api/cambiar_password.php).
function enviarEmailCodigo($email, $nombre, $codigo, $asunto, $introduccion, $titulo = 'Verificá tu email') {
    $host    = env('SMTP_HOST');
    $usuario = env('SMTP_USER');
    $clave   = env('SMTP_PASS');
    if (!$host || !$usuario || !$clave) {
        error_log('Trackify: faltan SMTP_HOST / SMTP_USER / SMTP_PASS en .env');
        return false;
    }

    $appUrl     = rtrim(env('APP_URL', 'http://localhost/trackify'), '/');
    $codigoHtml = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
    $introHtml  = htmlspecialchars($introduccion, ENT_QUOTES, 'UTF-8');
    $tituloHtml = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $usuario;
        $mail->Password   = $clave;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)env('SMTP_PORT', 2525);
        $mail->Timeout    = 15; // que la página no quede colgada si el servidor SMTP no responde
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(env('SMTP_FROM', 'noreply@trackify.com'), 'Trackify');
        $mail->addAddress($email, $nombre);
        $mail->Subject = $asunto;
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family:Inter,sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f8fafc;border-radius:12px;'>
                <img src='{$appUrl}/logo.png' alt='Trackify' style='height:60px;margin-bottom:24px;'>
                <h2 style='color:#1E1B26;margin:0 0 8px;'>{$tituloHtml}</h2>
                <p style='color:#64748b;margin:0 0 24px;'>{$introHtml}</p>
                <div style='background:#084734;color:#CFF27C;font-size:2rem;font-weight:700;letter-spacing:12px;text-align:center;padding:20px;border-radius:8px;margin-bottom:24px;'>
                    {$codigoHtml}
                </div>
                <p style='color:#94a3b8;font-size:.85rem;margin:0;'>Si no fuiste vos, ignorá este email.</p>
            </div>
        ";
        $mail->AltBody = "{$introduccion}\n\nTu código: {$codigo}\n\nSi no fuiste vos, ignorá este email.";
        $mail->send();
        return true;
    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log('Trackify: error al enviar email: ' . $mail->ErrorInfo);
        return false;
    }
}
