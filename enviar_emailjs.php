<?php
// Envío de correos mediante la API HTTP de EmailJS (puerto 443).
// Alternativa a SMTP para servidores que bloquean ese puerto (por ejemplo Render).
// La usa includes/mailer.php cuando están definidas las variables EMAILJS_* en .env
// (o en Environment Variables de Render); si no, se sigue usando SMTP.
//
// La plantilla de EmailJS debe usar {{codigo}} en el cuerpo y {{destinatario}} en "To Email".
// Opcionales en la plantilla: {{nombre}}, {{asunto}}, {{titulo}}, {{introduccion}}.

require_once __DIR__ . '/env.php';

function emailjsConfigurado(): bool
{
    return env('EMAILJS_SERVICE_ID') && env('EMAILJS_TEMPLATE_ID') && env('EMAILJS_PUBLIC_KEY') && env('EMAILJS_PRIVATE_KEY');
}

// Devuelve true si EmailJS respondió 200; false en cualquier otro caso (el error se registra con error_log).
function enviar_emailjs(string $destinatario, string $codigo, array $extra = []): bool
{
    if (!emailjsConfigurado()) {
        error_log('EmailJS: faltan EMAILJS_SERVICE_ID / EMAILJS_TEMPLATE_ID / EMAILJS_PUBLIC_KEY / EMAILJS_PRIVATE_KEY');
        return false;
    }

    $payload = json_encode([
        'service_id'      => env('EMAILJS_SERVICE_ID'),
        'template_id'     => env('EMAILJS_TEMPLATE_ID'),
        'user_id'         => env('EMAILJS_PUBLIC_KEY'),
        'accessToken'     => env('EMAILJS_PRIVATE_KEY'),
        'template_params' => ['destinatario' => $destinatario, 'codigo' => $codigo] + $extra,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 20,
    ]);

    $response  = curl_exec($ch);
    $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($http_code === 200) {
        return true;
    }

    error_log("Error EmailJS HTTP $http_code: " . ($response !== false ? $response : $curl_err));
    return false;
}
