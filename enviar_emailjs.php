<?php
// Envío de correos mediante la API HTTP de EmailJS (puerto 443).
// Reemplaza a PHPMailer/SMTP, que Render bloquea.
//
// Variables de entorno requeridas (.env local o Environment Variables en Render):
//   EMAILJS_SERVICE_ID, EMAILJS_TEMPLATE_ID, EMAILJS_PUBLIC_KEY, EMAILJS_PRIVATE_KEY
//
// La plantilla de EmailJS debe usar {{codigo}} en el cuerpo y {{destinatario}} en "To Email".

require_once __DIR__ . '/env.php';

/**
 * Envía el código de verificación por EmailJS.
 *
 * @return bool true si EmailJS respondió 200; false en cualquier otro caso (el error se registra con error_log).
 */
function enviar_emailjs(string $destinatario, string $codigo): bool
{
    $service_id  = getenv('EMAILJS_SERVICE_ID');
    $template_id = getenv('EMAILJS_TEMPLATE_ID');
    $public_key  = getenv('EMAILJS_PUBLIC_KEY');
    $private_key = getenv('EMAILJS_PRIVATE_KEY');

    if (!$service_id || !$template_id || !$public_key || !$private_key) {
        error_log('EmailJS: faltan EMAILJS_SERVICE_ID / EMAILJS_TEMPLATE_ID / EMAILJS_PUBLIC_KEY / EMAILJS_PRIVATE_KEY');
        return false;
    }

    $payload = json_encode([
        'service_id'      => $service_id,
        'template_id'     => $template_id,
        'user_id'         => $public_key,
        'accessToken'     => $private_key,
        'template_params' => [
            'destinatario' => $destinatario,
            'codigo'       => $codigo,
        ],
    ]);

    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response  = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200) {
        return true;
    }

    error_log("Error EmailJS HTTP $http_code: " . ($response !== false ? $response : $curl_err));
    return false;
}
