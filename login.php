<?php
session_start();
include 'conexion.php';
require_once 'env.php';

if(isset($_SESSION['usuario_id'])){
    header("Location: index.php");
    exit();
}

$google_client_id = getenv('GOOGLE_CLIENT_ID') ?: '936672406949-tqhis3m9sj5g9195pt06394toie25qfp.apps.googleusercontent.com';
$google_redirect_uri = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/trackify/auth/google/callback.php';
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$google_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $google_client_id,
    'redirect_uri' => $google_redirect_uri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
]);

$mensajes_error = [
    'google_cancelado' => 'Cancelaste el inicio de sesión con Google.',
    'sin_codigo'       => 'Google no devolvió un código de acceso. Intentá de nuevo.',
    'token_fallido'    => 'No se pudo completar el inicio de sesión con Google.',
    'datos_google'     => 'No se pudieron obtener tus datos de Google.',
    'email_no_verificado' => 'Tu email de Google no está verificado.',
];
$mensaje = $mensajes_error[$_GET['error'] ?? ''] ?? '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $query = $conn->prepare("SELECT * FROM usuarios WHERE email=?");
    $query->bind_param("s", $email);
    $query->execute();
    $user = $query->get_result()->fetch_assoc();

    // Mismo mensaje para email inexistente o contraseña incorrecta (no revela qué cuentas existen)
    if (!$user || empty($user['clave']) || !password_verify($password, $user['clave'])) {
        $mensaje = "Email o contraseña incorrectos.";
    } elseif ((int)($user['email_verificado'] ?? 0) === 0) {
        // Contraseña correcta pero email no verificado → generar y enviar código
        require_once 'includes/mailer.php';
        $codigo     = generarCodigoVerificacion();
        $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $stmt_cod = $conn->prepare("UPDATE usuarios SET codigo_verificacion=?, codigo_expiracion=? WHERE id_usuario=?");
        $stmt_cod->bind_param("ssi", $codigo, $expiracion, $user['id_usuario']);
        $stmt_cod->execute();

        // Si falla el envío igual redirige: el usuario puede usar "Reenviar"
        enviarCodigoVerificacion($user['email'], $user['nombre'], $codigo, 'Verificá tu cuenta de Trackify', 'Usá este código para completar tu acceso. Vence en 15 minutos.');

        session_regenerate_id(true);
        $_SESSION['verificar_id']     = $user['id_usuario'];
        $_SESSION['verificar_email']  = $user['email'];
        $_SESSION['verificar_nombre'] = $user['nombre'];
        $_SESSION['verificar_intentos'] = 0;
        $_SESSION['verificar_ultimo_envio'] = time();
        header('Location: verificar_email.php');
        exit();
    } else {
        // Todo OK → iniciar sesión con un id de sesión nuevo
        session_regenerate_id(true);
        $_SESSION['usuario_id']     = $user['id_usuario'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        header("Location: index.php");
        exit();
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
<title>Login - Trackify</title>
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
    <p class="subtitle">Controlá tus finanzas fácilmente</p>

    <a href="<?php echo htmlspecialchars($google_url); ?>" class="btn btn-google">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continuar con Google
    </a>

    <div class="divider">O con email</div>

    <?php if($mensaje) echo "<div class='error-msg'>" . htmlspecialchars($mensaje) . "</div>"; ?>

    <form method="POST">
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" autocomplete="email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Contraseña" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary">Iniciar sesión</button>
    </form>

    <div class="footer-links">
        ¿Sos un usuario nuevo? <a href="registro.php">Creá tu cuenta</a>
    </div>
</div>

</body>
</html>
