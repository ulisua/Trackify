<?php
// includes/auth_layout.php
// Estructura común de las pantallas sin sesión (login, registro, verificación de email).

function authInicio($titulo) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
<?php include __DIR__ . '/tema_inicial.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo) ?> - Trackify</title>
<link rel="icon" href="favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="css/pages/auth.css">
<link rel="stylesheet" href="css/responsive.css">
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
