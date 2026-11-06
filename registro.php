<?php
require_once 'conexion.php';
require_once 'includes/mailer.php';
require_once 'includes/auth_layout.php';

if (isset($_SESSION['usuario_id'])) {
    redirigir('index.php');
}

$mensaje = '';
$nombre = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        if ($check->get_result()->num_rows > 0) {
            $mensaje = "El email ya está registrado.";
        } else {
            $password = password_hash($clave, PASSWORD_DEFAULT);
            $uuid = generarUuid();
            $nombre_usuario = generarNombreUsuario($conn, $email); // editable después desde el perfil

            $query = $conn->prepare("INSERT INTO usuarios (nombre, nombre_usuario, email, clave, fecha_registro, uuid, email_verificado, Foto_perfil) VALUES (?, ?, ?, ?, NOW(), ?, 0, '')");
            $query->bind_param("sssss", $nombre, $nombre_usuario, $email, $password, $uuid);

            if ($query->execute()) {
                // La pantalla de verificación genera y envía el código al abrirse
                iniciarVerificacionEmail($conn->insert_id, $email, $nombre);
                redirigir('verificar_email.php');
            }
            $mensaje = "Error al registrar. Intentá de nuevo.";
        }
    }
}

authInicio('Registro');
?>
    <p class="subtitle">Creá tu cuenta para comenzar</p>

    <?php if ($mensaje): ?><div class="error-msg"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <input type="text" name="nombre" placeholder="Nombre completo" maxlength="50" value="<?= htmlspecialchars($nombre) ?>" autocomplete="name" required>
        </div>
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" maxlength="100" value="<?= htmlspecialchars($email) ?>" autocomplete="email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Contraseña (mínimo 8 caracteres)" minlength="8" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary">Registrarse</button>
    </form>

    <div class="footer-links">
        ¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a>
    </div>
<?php authFin(); ?>
