<?php
require_once 'conexion.php';
require_once 'includes/mailer.php';

if (!isset($_SESSION['verificar_id'])) {
    redirigir('login.php');
}

// Un reenvío por minuto como máximo (la espera solo corre si el envío anterior salió bien)
if (segundosParaReenviar() > 0) {
    redirigir('verificar_email.php?error=espera');
}

$_SESSION['verificar_auto_enviado'] = true;
$enviado = enviarNuevoCodigo($conn, 'Nuevo código de verificación - Trackify', 'Usá este nuevo código para verificar tu cuenta. Vence en ' . CODIGO_MINUTOS_VALIDEZ . ' minutos.');
redirigir('verificar_email.php?' . ($enviado ? 'reenviado=1' : 'error=envio'));
