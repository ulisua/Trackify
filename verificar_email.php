<?php
require_once 'conexion.php';
require_once 'includes/mailer.php';
require_once 'includes/auth_layout.php';

if (!isset($_SESSION['verificar_id'])) {
    redirigir('login.php');
}

const MAX_INTENTOS_CODIGO = 5;

$user_id  = (int)$_SESSION['verificar_id'];
$email    = $_SESSION['verificar_email'];
$nombre   = $_SESSION['verificar_nombre'];
$mensaje  = '';
$aviso    = '';
$intentos = $_SESSION['verificar_intentos'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_ingresado = trim($_POST['codigo'] ?? '');

    $user = $conn->fila("SELECT codigo_verificacion, codigo_expiracion FROM usuarios WHERE id_usuario = ?", [$user_id]);

    if (!$user) {
        $mensaje = "Usuario no encontrado.";
    } elseif ($intentos >= MAX_INTENTOS_CODIGO) {
        $mensaje = "Superaste la cantidad de intentos. Pedí un código nuevo.";
    } elseif (empty($user['codigo_expiracion']) || time() > strtotime($user['codigo_expiracion'])) {
        $mensaje = "El código venció. Pedí uno nuevo.";
    } elseif (!hash_equals((string)$user['codigo_verificacion'], $codigo_ingresado)) {
        $_SESSION['verificar_intentos'] = ++$intentos;
        $restantes = MAX_INTENTOS_CODIGO - $intentos;
        $mensaje = $restantes > 0
            ? "Código incorrecto. Te quedan {$restantes} intento" . ($restantes === 1 ? '' : 's') . "."
            : "Código incorrecto. Superaste la cantidad de intentos: pedí un código nuevo.";
    } else {
        $conn->ejecutar("UPDATE usuarios SET email_verificado = 1, codigo_verificacion = NULL, codigo_expiracion = NULL WHERE id_usuario = ?", [$user_id]);

        unset($_SESSION['verificar_id'], $_SESSION['verificar_email'], $_SESSION['verificar_nombre'],
              $_SESSION['verificar_intentos'], $_SESSION['verificar_ultimo_envio'], $_SESSION['verificar_auto_enviado']);
        iniciarSesionUsuario($user_id, $nombre);
        redirigir('index.php');
    }
} elseif (empty($_SESSION['verificar_auto_enviado'])) {
    // Primera vez que se abre la pantalla: el código se envía automáticamente.
    // Recargar no reenvía; para eso está "Reenviar".
    $_SESSION['verificar_auto_enviado'] = true;
    if (!enviarNuevoCodigo($conn, 'Verificá tu cuenta de Trackify', 'Usá este código para verificar tu cuenta. Vence en ' . CODIGO_MINUTOS_VALIDEZ . ' minutos.')) {
        $mensaje = 'No pudimos enviar el email. Tocá "Reenviar" para intentarlo de nuevo.';
    }
} elseif (isset($_GET['reenviado'])) {
    $aviso = 'Te enviamos un código nuevo.';
} elseif (($_GET['error'] ?? '') === 'envio') {
    $mensaje = 'No pudimos enviar el email. Revisá la configuración SMTP o intentá de nuevo.';
} elseif (($_GET['error'] ?? '') === 'espera') {
    $mensaje = 'Esperá unos segundos antes de pedir otro código.';
}

// Tiempo real que le queda al código vigente (para el contador)
$expiracion = $conn->valor("SELECT codigo_expiracion FROM usuarios WHERE id_usuario = ?", [$user_id]);
$segundos_restantes = $expiracion ? max(0, strtotime($expiracion) - time()) : 0;
$espera_reenvio = segundosParaReenviar();

authInicio('Verificar email');
?>
    <h2 class="auth-titulo">Verificá tu email</h2>
    <p class="email-hint">Enviamos un código de 6 dígitos a</p>
    <p class="email-hint"><strong><?= htmlspecialchars($email) ?></strong></p>

    <?php if ($mensaje): ?>
        <div class="error-msg"><?= htmlspecialchars($mensaje) ?></div>
    <?php elseif ($aviso): ?>
        <div class="ok-msg"><?= htmlspecialchars($aviso) ?></div>
    <?php endif; ?>

    <form method="POST" id="formCodigo">
        <div class="codigo-inputs">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" class="digit" inputmode="numeric" pattern="[0-9]" autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>" aria-label="Dígito <?= $i + 1 ?>" required>
            <?php endfor; ?>
        </div>
        <input type="hidden" name="codigo" id="codigoHidden">
        <button type="submit" class="btn btn-primary">Verificar</button>
    </form>

    <div class="temporizador" id="temporizador" data-segundos="<?= $segundos_restantes ?>">
        <?php if ($segundos_restantes > 0): ?>
            El código vence en <span id="timer"><?= sprintf('%02d:%02d', intdiv($segundos_restantes, 60), $segundos_restantes % 60) ?></span>
        <?php else: ?>
            <span class="vencido">No hay un código vigente. Pedí uno nuevo.</span>
        <?php endif; ?>
    </div>

    <div class="footer-links">
        ¿No recibiste el código?
        <a href="reenviar_codigo.php" id="linkReenviar" data-espera="<?= $espera_reenvio ?>">Reenviar</a>
    </div>

<script>
const digits = document.querySelectorAll('.digit');

function repartirCodigo(texto, desde) {
    const numeros = texto.replace(/\D/g, '').slice(0, digits.length - desde);
    [...numeros].forEach((n, k) => digits[desde + k].value = n);
    digits[Math.min(desde + numeros.length, digits.length - 1)].focus();
}

digits.forEach((input, i) => {
    input.addEventListener('input', () => {
        // Autocompletado del sistema o tipeo rápido: varios dígitos en una casilla
        if (input.value.length > 1) return repartirCodigo(input.value, i);
        if (input.value && i < digits.length - 1) digits[i + 1].focus();
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && !input.value && i > 0) digits[i - 1].focus();
    });
    input.addEventListener('keypress', e => {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    });
    input.addEventListener('paste', e => {
        e.preventDefault();
        repartirCodigo((e.clipboardData || window.clipboardData).getData('text'), i);
    });
});
digits[0].focus();

document.getElementById('formCodigo').addEventListener('submit', e => {
    const codigo = Array.from(digits).map(d => d.value).join('');
    if (codigo.length < digits.length) {
        e.preventDefault();
        return;
    }
    document.getElementById('codigoHidden').value = codigo;
});

// Contador de vencimiento (arranca del tiempo real que le queda al código)
const timerEl = document.getElementById('timer');
let segundos = parseInt(document.getElementById('temporizador').dataset.segundos, 10) || 0;
if (timerEl) {
    const intervalo = setInterval(() => {
        segundos = Math.max(0, segundos - 1);
        timerEl.textContent = `${String(Math.floor(segundos / 60)).padStart(2, '0')}:${String(segundos % 60).padStart(2, '0')}`;
        if (segundos === 0) {
            clearInterval(intervalo);
            timerEl.classList.add('vencido');
        }
    }, 1000);
}

// "Reenviar" queda inactivo mientras corre la espera entre envíos
const linkReenviar = document.getElementById('linkReenviar');
let espera = parseInt(linkReenviar.dataset.espera, 10) || 0;
function actualizarReenviar() {
    const bloqueado = espera > 0;
    linkReenviar.textContent = bloqueado ? `Reenviar (${espera}s)` : 'Reenviar';
    linkReenviar.setAttribute('aria-disabled', String(bloqueado));
    linkReenviar.style.pointerEvents = bloqueado ? 'none' : '';
    linkReenviar.style.opacity = bloqueado ? '0.5' : '';
    if (bloqueado) { espera--; setTimeout(actualizarReenviar, 1000); }
}
actualizarReenviar();
linkReenviar.addEventListener('click', () => {
    linkReenviar.textContent = 'Enviando...';
    linkReenviar.style.pointerEvents = 'none';
});
</script>
<?php authFin(); ?>
