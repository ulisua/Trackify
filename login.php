<?php
session_start();
include 'conexion.php';
require_once 'env.php';

if(isset($_SESSION['usuario_id'])){
    header("Location: index.php");
    exit();
}

$google_client_id = getenv('GOOGLE_CLIENT_ID') ?: '936672406949-tqhis3m9sj5g9195pt06394toie25qfp.apps.googleusercontent.com';
$google_redirect_uri = 'http://localhost/trackify/auth/google/callback.php';
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

$mensaje = '';
if (isset($_GET['error']) && $_GET['error'] === 'google_cancelado') $mensaje = 'Cancelaste el inicio de sesión con Google.';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $query = $conn->prepare("SELECT * FROM usuarios WHERE email=?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if($result->num_rows === 1){
        $user = $result->fetch_assoc();

        if (isset($user['email_verificado']) && (int)$user['email_verificado'] === 0) {
            $_SESSION['verificar_id'] = $user['id_usuario'];
            $_SESSION['verificar_email'] = $user['email'];
            $_SESSION['verificar_nombre'] = $user['nombre'];
            header('Location: verificar_email.php');
            exit();
        }

        if(!empty($user['clave']) && password_verify($password, $user['clave'])){
            $_SESSION['usuario_id'] = $user['id_usuario'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            header("Location: index.php");
            exit();
        } else {
            $mensaje = "Contraseña incorrecta";
        }
    } else {
        $mensaje = "Usuario no encontrado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();
</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Trackify</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="css/pages/auth.css">
</head>
<body class="login-body">

<div class="glow-orb orb-1"></div>
<div class="glow-orb orb-2"></div>

<div class="auth-card">
    <div class="logo" style="text-align: center; margin-bottom: 20px;">
        <img src="logo.png" alt="Trackify Icon" style="height: 100px;">
    </div>
    <p class="subtitle">Controla tus finanzas fácilmente</p>

    <a href="<?php echo htmlspecialchars($google_url); ?>" class="btn btn-google" style="text-decoration:none;">
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
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Contraseña" required>
        </div>
        <button type="submit" class="btn btn-primary">Iniciar sesión</button>
    </form>

    <div class="footer-links">
        ¿Eres un usuario nuevo? <a href="registro.php">Crea tu cuenta</a>
    </div>
</div>

</body>
</html>