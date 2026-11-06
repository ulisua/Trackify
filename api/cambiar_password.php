<?php
// api/cambiar_password.php
// Endpoint JSON (lo usa js/perfil.js desde la sección "Seguridad" del perfil):
//   accion=cambiar        -> cambiar la contraseña (cuentas que ya tienen una)
//   accion=enviar_codigo  -> cuentas de Google sin contraseña: enviar código por email
//   accion=crear          -> cuentas de Google sin contraseña: crear la contraseña con el código
// El token CSRF llega en la cabecera X-CSRF-Token y lo valida conexion.php (includes/seguridad.php).

require_once dirname(__DIR__) . '/conexion.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const PW_MIN          = 8;   // largo mínimo
const PW_MAX_BYTES    = 72;  // límite real de bcrypt (PASSWORD_DEFAULT)
const CODIGO_INTENTOS = 5;   // intentos de código por sesión

function responder(int $http, array $datos): void
{
    http_response_code($http);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

function fallo(int $http, string $mensaje, array $extra = []): void
{
    responder($http, array_merge(['ok' => false, 'error' => $mensaje], $extra));
}

function post_texto(string $clave): string
{
    $valor = $_POST[$clave] ?? '';
    return is_string($valor) ? $valor : '';
}

// Reglas de la contraseña nueva. Devuelve el mensaje de error o null si está bien.
function validar_nueva(string $nueva, string $confirmar, string $nombre, string $email, ?string $actual): ?string
{
    if ($nueva !== $confirmar) {
        return 'La confirmación no coincide con la contraseña nueva.';
    }
    if (strlen($nueva) < PW_MIN) {
        return 'La contraseña nueva debe tener al menos ' . PW_MIN . ' caracteres.';
    }
    if (strlen($nueva) > PW_MAX_BYTES) {
        return 'La contraseña nueva no puede superar los ' . PW_MAX_BYTES . ' caracteres.';
    }
    if ($actual !== null && hash_equals($actual, $nueva)) {
        return 'La contraseña nueva debe ser distinta de la actual.';
    }

    $bajo  = mb_strtolower($nueva);
    $local = mb_strtolower(explode('@', $email)[0]);
    if (mb_strlen($local) >= 4 && str_contains($bajo, $local)) {
        return 'La contraseña no puede contener tu email.';
    }
    $nom = mb_strtolower(trim($nombre));
    if (mb_strlen($nom) >= 4 && str_contains($bajo, $nom)) {
        return 'La contraseña no puede contener tu nombre.';
    }
    return null;
}

function invalidar_codigo(mysqli $conn, int $user_id): void
{
    $stmt = $conn->prepare('UPDATE usuarios SET codigo_verificacion = NULL, codigo_expiracion = NULL WHERE id_usuario = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
}

// ── 1. Solo POST y solo con sesión iniciada ──────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    fallo(405, 'Método no permitido.');
}
if (empty($_SESSION['usuario_id'])) {
    fallo(401, 'Tu sesión expiró. Iniciá sesión de nuevo.');
}

$user_id = (int)$_SESSION['usuario_id'];
$accion  = post_texto('accion');

try {
    $stmt = $conn->prepare('SELECT nombre, email, clave, codigo_verificacion, codigo_expiracion FROM usuarios WHERE id_usuario = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    if (!$usuario) {
        fallo(404, 'No encontramos tu cuenta.');
    }
    $tiene_clave = !empty($usuario['clave']);

    // ── Cuentas con contraseña: cambiarla ────────────────────────────────────
    if ($accion === 'cambiar') {
        if (!$tiene_clave) {
            fallo(409, 'Tu cuenta todavía no tiene contraseña. Creala con el código que te enviamos por email.');
        }
        $actual    = post_texto('actual');
        $nueva     = post_texto('nueva');
        $confirmar = post_texto('confirmar');

        if ($actual === '') {
            fallo(422, 'Ingresá tu contraseña actual.');
        }
        if (!password_verify($actual, $usuario['clave'])) {
            fallo(422, 'La contraseña actual no es correcta.');
        }
        $error = validar_nueva($nueva, $confirmar, $usuario['nombre'], $usuario['email'], $actual);
        if ($error !== null) {
            fallo(422, $error);
        }

        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $upd  = $conn->prepare('UPDATE usuarios SET clave = ? WHERE id_usuario = ?');
        $upd->bind_param('si', $hash, $user_id);
        $upd->execute();

        // Id de sesión nuevo (se conservan los datos, incluido el token CSRF)
        session_regenerate_id(true);
        responder(200, ['ok' => true, 'mensaje' => 'Contraseña actualizada.']);
    }

    // ── Cuentas de Google sin contraseña: enviar código ──────────────────────
    if ($accion === 'enviar_codigo') {
        if ($tiene_clave) {
            fallo(409, 'Tu cuenta ya tiene contraseña. Para cambiarla usá tu contraseña actual.');
        }
        $espera = CODIGO_SEGUNDOS_ENTRE_ENVIOS - (time() - (int)($_SESSION['pw_codigo_enviado'] ?? 0));
        if ($espera > 0) {
            fallo(429, "Esperá $espera segundos para pedir otro código.", ['espera' => $espera]);
        }

        $codigo = (string)random_int(100000, 999999);
        $expira = date('Y-m-d H:i:s', time() + CODIGO_MINUTOS_VALIDEZ * 60);
        $upd = $conn->prepare('UPDATE usuarios SET codigo_verificacion = ?, codigo_expiracion = ? WHERE id_usuario = ?');
        $upd->bind_param('ssi', $codigo, $expira, $user_id);
        $upd->execute();

        $enviado = enviarEmailCodigo(
            $usuario['email'],
            $usuario['nombre'],
            $codigo,
            'Código para crear tu contraseña - Trackify',
            'Usá este código para crear la contraseña de tu cuenta. Vence en ' . CODIGO_MINUTOS_VALIDEZ . ' minutos.',
            'Creá tu contraseña'
        );
        if (!$enviado) {
            fallo(500, 'No pudimos enviar el código. Revisá la configuración del correo o intentá de nuevo en unos minutos.');
        }

        $_SESSION['pw_codigo_enviado']  = time();
        $_SESSION['pw_codigo_intentos'] = 0;
        responder(200, [
            'ok'      => true,
            'mensaje' => 'Te enviamos un código de 6 dígitos a ' . mascaraEmail($usuario['email']) . '.',
            'espera'  => CODIGO_SEGUNDOS_ENTRE_ENVIOS,
        ]);
    }

    // ── Cuentas de Google sin contraseña: crearla con el código ──────────────
    if ($accion === 'crear') {
        if ($tiene_clave) {
            fallo(409, 'Tu cuenta ya tiene contraseña. Para cambiarla usá tu contraseña actual.');
        }
        $codigo    = post_texto('codigo');
        $nueva     = post_texto('nueva');
        $confirmar = post_texto('confirmar');

        $guardado = (string)($usuario['codigo_verificacion'] ?? '');
        if ($guardado === '' || empty($usuario['codigo_expiracion'])) {
            fallo(422, 'Primero pedí un código por email.');
        }

        $intentos = (int)($_SESSION['pw_codigo_intentos'] ?? 0);
        if ($intentos >= CODIGO_INTENTOS) {
            invalidar_codigo($conn, $user_id);
            fallo(429, 'Superaste los ' . CODIGO_INTENTOS . ' intentos. Pedí un código nuevo.');
        }
        if (time() > strtotime($usuario['codigo_expiracion'])) {
            fallo(422, 'El código venció. Pedí uno nuevo.');
        }
        if (!preg_match('/^\d{6}$/', $codigo)) {
            fallo(422, 'El código tiene 6 dígitos.');
        }
        if (!hash_equals($guardado, $codigo)) {
            $_SESSION['pw_codigo_intentos'] = ++$intentos;
            $restan = CODIGO_INTENTOS - $intentos;
            if ($restan <= 0) {
                invalidar_codigo($conn, $user_id);
                fallo(429, 'Código incorrecto. Superaste los ' . CODIGO_INTENTOS . ' intentos: pedí un código nuevo.');
            }
            fallo(422, "Código incorrecto. Te quedan $restan " . ($restan === 1 ? 'intento.' : 'intentos.'));
        }

        $error = validar_nueva($nueva, $confirmar, $usuario['nombre'], $usuario['email'], null);
        if ($error !== null) {
            fallo(422, $error);
        }

        // El código demuestra que el email es del usuario, por eso queda verificado.
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $upd  = $conn->prepare("UPDATE usuarios SET clave = ?, email_verificado = 1, codigo_verificacion = NULL, codigo_expiracion = NULL WHERE id_usuario = ? AND (clave IS NULL OR clave = '')");
        $upd->bind_param('si', $hash, $user_id);
        $upd->execute();
        if ($upd->affected_rows !== 1) {
            fallo(409, 'No pudimos crear la contraseña. Recargá la página e intentá de nuevo.');
        }

        unset($_SESSION['pw_codigo_intentos'], $_SESSION['pw_codigo_enviado']);
        session_regenerate_id(true);
        responder(200, ['ok' => true, 'mensaje' => 'Contraseña creada. Ahora también podés entrar con tu email y contraseña.']);
    }

    fallo(400, 'Acción no válida.');

} catch (\Throwable $e) {
    error_log('Trackify: cambiar_password: ' . $e->getMessage());
    fallo(500, 'Ocurrió un error en el servidor. Intentá de nuevo más tarde.');
}
