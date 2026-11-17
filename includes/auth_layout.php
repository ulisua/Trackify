<?php
// includes/auth_layout.php
// Estructura común de las pantallas sin sesión (login, registro, verificación de email).
require_once __DIR__ . '/assets.php';

function authInicio($titulo) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php include __DIR__ . '/tema_inicial.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo) ?> - Trackify</title>
<?php headComun(); ?>
<?= cssLink('css/pages/auth.css') ?>
<?= cssLink('css/responsive.css') ?>
</head>
<body class="login-body">

<div class="glow-orb orb-1"></div>
<div class="glow-orb orb-2"></div>

<div class="auth-card">
    <div class="auth-logo">
        <img src="logo.png" alt="Trackify">
    </div>
<?php }

function authFin() { ?>
</div>

</body>
</html>
<?php }
