<?php
session_start();
include 'conexion.php';
require_once 'includes/mailer.php';

$mensaje = '';
$nombre = '';
$email = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clave = $_POST['password'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 50) {
        $mensaje = "Ingresá tu nombre (máximo 50 caracteres).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $mensaje = "Ingresá un email válido.";
    } elseif (mb_strlen($clave) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();

        if($check->get_result()->num_rows > 0){
            $mensaje = "El email ya está registrado.";
        } else {
            $password = password_hash($clave, PASSWORD_DEFAULT);
            $codigo = generarCodigoVerificacion();
            $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff), random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
            );

            $query = $conn->prepare("INSERT INTO usuarios (nombre, email, clave, fecha_registro, uuid, email_verificado, codigo_verificacion, codigo_expiracion, Foto_perfil) VALUES (?, ?, ?, NOW(), ?, 0, ?, ?, '')");
            $query->bind_param("ssssss", $nombre, $email, $password, $uuid, $codigo, $expiracion);

            if($query->execute()){
                // Si el email falla, el usuario puede pedir otro código desde la pantalla de verificación
                $enviado = enviarCodigoVerificacion($email, $nombre, $codigo, 'Verificá tu cuenta de Trackify', 'Usá el siguiente código para completar tu registro. Vence en 15 minutos.');

                session_regenerate_id(true);
                $_SESSION['verificar_id'] = $conn->insert_id;
                $_SESSION['verificar_email'] = $email;
                $_SESSION['verificar_nombre'] = $nombre;
                $_SESSION['verificar_intentos'] = 0;
                $_SESSION['verificar_ultimo_envio'] = time();
                header('Location: verificar_email.php' . ($enviado ? '' : '?error=envio'));
                exit();
            } else {
                $mensaje = "Error al registrar. Intentá de nuevo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<script>
    (function() {
        let savedTheme = null;
        try { savedTheme = localStorage.getItem('theme'); } catch (e) {}
        document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
    })();
</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro - Trackify</title>
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
        <img src="logo.png" alt="Trackify Icon">
    </div>
    <p class="subtitle">Creá tu cuenta para comenzar</p>

    <?php if($mensaje) echo "<div class='error-msg'>" . htmlspecialchars($mensaje) . "</div>"; ?>

    <form method="POST">
        <div class="form-group">
            <input type="text" name="nombre" placeholder="Nombre completo" maxlength="50" value="<?php echo htmlspecialchars($nombre); ?>" autocomplete="name" required>
        </div>
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" maxlength="100" value="<?php echo htmlspecialchars($email); ?>" autocomplete="email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Contraseña (mínimo 8 caracteres)" minlength="8" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary">Registrarse</button>
    </form>

    <div class="footer-links">
        ¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a>
    </div>
</div>

</body>
</html>
