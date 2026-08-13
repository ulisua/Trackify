<?php
session_start();
include 'conexion.php';

// Si no viene de un registro, redirigir
if (!isset($_SESSION['verificar_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['verificar_id'];
$email   = $_SESSION['verificar_email'];
$nombre  = $_SESSION['verificar_nombre'];
$mensaje = '';
$tipo    = ''; // 'error' o 'ok'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_ingresado = trim($_POST['codigo']);

    $stmt = $conn->prepare("SELECT codigo_verificacion, codigo_expiracion FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $mensaje = "Usuario no encontrado.";
        $tipo = 'error';
    } elseif (new DateTime() > new DateTime($user['codigo_expiracion'])) {
        $mensaje = "El código venció. Pedí uno nuevo.";
        $tipo = 'error';
    } elseif ($codigo_ingresado !== $user['codigo_verificacion']) {
        $mensaje = "Código incorrecto. Verificá tu email.";
        $tipo = 'error';
    } else {
        // Código correcto → verificar cuenta
        $stmt = $conn->prepare("UPDATE usuarios SET email_verificado=1, codigo_verificacion=NULL, codigo_expiracion=NULL WHERE id_usuario=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Iniciar sesión
        $_SESSION['usuario_id']     = $user_id;
        $_SESSION['usuario_nombre'] = $nombre;
        unset($_SESSION['verificar_id'], $_SESSION['verificar_email'], $_SESSION['verificar_nombre']);

        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verificar Email - Trackify</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/auth.css">
<style>
.codigo-inputs {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin: 24px 0;
}
.codigo-inputs input {
    width: 48px;
    height: 56px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    border: 2px solid #E2E8F0;
    border-radius: 8px;
    outline: none;
    transition: border-color .2s;
    font-family: Inter, sans-serif;
}
.codigo-inputs input:focus {
    border-color: #084734;
}
.email-hint {
    color: #64748b;
    font-size: .9rem;
    text-align: center;
    margin: 0 0 4px;
}
.email-hint strong {
    color: #1E1B26;
}
.reenviar-link {
    text-align: center;
    margin-top: 16px;
    font-size: .9rem;
    color: #64748b;
}
.reenviar-link a {
    color: #084734;
    font-weight: 600;
    text-decoration: none;
}
.reenviar-link a:hover {
    text-decoration: underline;
}
.temporizador {
    text-align: center;
    font-size: .85rem;
    color: #94a3b8;
    margin-top: 8px;
}
</style>
</head>
<body>

<div class="glow-orb orb-1"></div>
<div class="glow-orb orb-2"></div>

<div class="auth-card">
    <div class="logo" style="text-align: center; margin-bottom: 20px;">
        <img src="logo.png" alt="Trackify Icon" style="height: 80px;">
    </div>

    <h2 style="text-align:center;margin:0 0 8px;color:#1E1B26;">Verificá tu email</h2>
    <p class="email-hint">Enviamos un código de 6 dígitos a</p>
    <p class="email-hint"><strong><?= htmlspecialchars($email) ?></strong></p>

    <?php if($mensaje): ?>
        <div class="error-msg"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" id="formCodigo">
        <div class="codigo-inputs">
            <input type="text" maxlength="1" class="digit" inputmode="numeric" pattern="[0-9]" required>
            <input type="text" maxlength="1" class="digit" inputmode="numeric" pattern="[0-9]" required>
            <input type="text" maxlength="1" class="digit" inputmode="numeric" pattern="[0-9]" required>
            <input type="text" maxlength="1" class="digit" inputmode="numeric" pattern="[0-9]" required>
            <input type="text" maxlength="1" class="digit" inputmode="numeric" pattern="[0-9]" required>
            <input type="text" maxlength="1" class="digit" inputmode="numeric" pattern="[0-9]" required>
        </div>
        <input type="hidden" name="codigo" id="codigoHidden">
        <button type="submit" class="btn btn-primary">Verificar</button>
    </form>

    <div class="temporizador">El código vence en <span id="timer">15:00</span></div>

    <div class="reenviar-link">
        ¿No recibiste el código? <a href="reenviar_codigo.php">Reenviar</a>
    </div>
</div>

<script>
// ── Auto-avance entre inputs ──────────────────────────────────────────────────
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
    // Solo permitir números
    input.addEventListener('keypress', e => {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    });
});

// Pegar código completo de una vez
digits[0].addEventListener('paste', e => {
    const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
    if (paste.length === 6) {
        digits.forEach((input, i) => input.value = paste[i] || '');
        digits[5].focus();
        e.preventDefault();
    }
});

// Juntar los 6 dígitos en el input hidden antes de enviar
document.getElementById('formCodigo').addEventListener('submit', e => {
    const codigo = Array.from(digits).map(d => d.value).join('');
    if (codigo.length < 6) {
        e.preventDefault();
        return;
    }
    document.getElementById('codigoHidden').value = codigo;
});

// ── Temporizador 15 minutos ───────────────────────────────────────────────────
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
        timerEl.style.color = '#EF4444';
    }
}, 1000);
</script>

</body>
</html>
