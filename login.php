<?php
require_once 'conexion.php';
require_once 'includes/auth_layout.php';

if (isset($_SESSION['usuario_id'])) {
    redirigir('index.php');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$google_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => env('GOOGLE_CLIENT_ID', ''),
    'redirect_uri'  => env('GOOGLE_REDIRECT_URI', 'http://localhost/trackify/auth/google/callback.php'),
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
]);

$mensajes_error = [
    'google_cancelado'    => 'Cancelaste el inicio de sesión con Google.',
    'sin_codigo'          => 'Google no devolvió un código de acceso. Intentá de nuevo.',
    'token_fallido'       => 'No se pudo completar el inicio de sesión con Google.',
    'datos_google'        => 'No se pudieron obtener tus datos de Google.',
    'email_no_verificado' => 'Tu email de Google no está verificado.',
];
$mensaje = $mensajes_error[$_GET['error'] ?? ''] ?? '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // LOWER(): PostgreSQL distingue mayúsculas en las comparaciones (MySQL no)
    $user = $conn->fila("SELECT id_usuario, nombre, email, clave, email_verificado FROM usuarios WHERE LOWER(email) = ?", [$email]);

    // Mismo mensaje para email inexistente o contraseña incorrecta (no revela qué cuentas existen)
    if (!$user || empty($user['clave']) || !password_verify($password, $user['clave'])) {
        $mensaje = "Email o contraseña incorrectos.";
    } elseif ((int)$user['email_verificado'] === 0) {
        // Contraseña correcta pero email sin verificar: la pantalla de verificación envía el código
        require_once 'includes/mailer.php';
        iniciarVerificacionEmail($user['id_usuario'], $user['email'], $user['nombre']);
        redirigir('verificar_email.php');
    } else {
        iniciarSesionUsuario($user['id_usuario'], $user['nombre']);
        redirigir('index.php');
    }
}

authInicio('Login');
?>
    <p class="subtitle">Controlá tus finanzas fácilmente</p>

    <a href="<?= htmlspecialchars($google_url) ?>" class="btn btn-google">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continuar con Google
    </a>

    <div class="divider">O con email</div>

    <?php if ($mensaje): ?><div class="error-msg"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($email) ?>" autocomplete="email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Contraseña" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary">Iniciar sesión</button>
    </form>

    <div class="footer-links">
        ¿Sos un usuario nuevo? <a href="registro.php">Creá tu cuenta</a>
    </div>
<?php authFin(); ?>
