<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['verificar_id'])) {
    header("Location: login.php");
    exit();
}

const MAX_INTENTOS_CODIGO = 5;

$user_id = $_SESSION['verificar_id'];
$email   = $_SESSION['verificar_email'];
$nombre  = $_SESSION['verificar_nombre'];
$mensaje = '';
$aviso   = '';
$intentos = $_SESSION['verificar_intentos'] ?? 0;

if (isset($_GET['reenviado'])) {
    $aviso = 'Te enviamos un código nuevo.';
} elseif (isset($_GET['error']) && $_GET['error'] === 'envio') {
    $mensaje = 'No pudimos enviar el email. Intentá de nuevo en unos minutos.';
} elseif (isset($_GET['error']) && $_GET['error'] === 'espera') {
    $mensaje = 'Esperá un minuto antes de pedir otro código.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_ingresado = trim($_POST['codigo'] ?? '');

    $stmt = $conn->prepare("SELECT codigo_verificacion, codigo_expiracion FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $mensaje = "Usuario no encontrado.";
    } elseif ($intentos >= MAX_INTENTOS_CODIGO) {
        $mensaje = "Superaste la cantidad de intentos. Pedí un código nuevo.";
    } elseif (empty($user['codigo_expiracion']) || new DateTime() > new DateTime($user['codigo_expiracion'])) {
        $mensaje = "El código venció. Pedí uno nuevo.";
    } elseif (!hash_equals((string)$user['codigo_verificacion'], $codigo_ingresado)) {
        $_SESSION['verificar_intentos'] = ++$intentos;
        $restantes = MAX_INTENTOS_CODIGO - $intentos;
        $mensaje = $restantes > 0
            ? "Código incorrecto. Te quedan {$restantes} intento" . ($restantes === 1 ? '' : 's') . "."
            : "Código incorrecto. Superaste la cantidad de intentos: pedí un código nuevo.";
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET email_verificado=1, codigo_verificacion=NULL, codigo_expiracion=NULL WHERE id_usuario=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        session_regenerate_id(true);
        $_SESSION['usuario_id']     = $user_id;
        $_SESSION['usuario_nombre'] = $nombre;
        unset($_SESSION['verificar_id'], $_SESSION['verificar_email'], $_SESSION['verificar_nombre'], $_SESSION['verificar_intentos'], $_SESSION['verificar_ultimo_envio']);

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
<title>Verificar Email - Trackify</title>
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

    <h2 class="auth-titulo">Verificá tu email</h2>
    <p class="email-hint">Enviamos un código de 6 dígitos a</p>
    <p class="email-hint"><strong><?= htmlspecialchars($email) ?></strong></p>

    <?php if($mensaje): ?>
        <div class="error-msg"><?= htmlspecialchars($mensaje) ?></div>
    <?php elseif($aviso): ?>
        <div class="ok-msg"><?= htmlspecialchars($aviso) ?></div>
    <?php endif; ?>

    <form method="POST" id="formCodigo">
        <div class="codigo-inputs">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" maxlength="1" class="digit" inputmode="numeric" pattern="[0-9]" aria-label="Dígito <?= $i + 1 ?>" required>
            <?php endfor; ?>
        </div>
        <input type="hidden" name="codigo" id="codigoHidden">
        <button type="submit" class="btn btn-primary">Verificar</button>
    </form>

    <div class="temporizador">El código vence en <span id="timer">15:00</span></div>

    <div class="footer-links">
        ¿No recibiste el código? <a href="reenviar_codigo.php">Reenviar</a>
    </div>
</div>

<script>
const digits = document.querySelectorAll('.digit');

digits.forEach((input, i) => {
    input.addEventListener('input', () => {
        if (input.value && i < digits.length - 1) {
            digits[i + 1].focus();
        }
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !input.value && i > 0) {
            digits[i - 1].focus();
        }
    });
    input.addEventListener('keypress', e => {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    });
});

digits[0].addEventListener('paste', e => {
    const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
    if (paste.length === 6) {
        digits.forEach((input, i) => input.value = paste[i] || '');
        digits[5].focus();
        e.preventDefault();
    }
});

document.getElementById('formCodigo').addEventListener('submit', e => {
    const codigo = Array.from(digits).map(d => d.value).join('');
    if (codigo.length < 6) {
        e.preventDefault();
        return;
    }
    document.getElementById('codigoHidden').value = codigo;
});

let segundos = 15 * 60;
const timerEl = document.getElementById('timer');
const intervalo = setInterval(() => {
    segundos--;
    const m = Math.floor(segundos / 60).toString().padStart(2, '0');
    const s = (segundos % 60).toString().padStart(2, '0');
    timerEl.textContent = `${m}:${s}`;
    if (segundos <= 0) {
        clearInterval(intervalo);
        timerEl.textContent = '00:00';
        timerEl.classList.add('vencido');
    }
}, 1000);
</script>

</body>
</html>
