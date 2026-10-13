<?php
$page = 'perfil';
$titulo_pagina = 'Cambiar contraseña - Trackify';
$extra_css = '<link rel="stylesheet" href="css/pages/perfil.css">';
require_once 'conexion.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'includes/header.php'; // redirige al login si no hay sesión

$user_id = (int) $_SESSION['usuario_id'];

// Token CSRF: se genera una sola vez por sesión y se reutiliza
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$stmt = $conn->prepare('SELECT nombre, email, clave FROM usuarios WHERE id_usuario = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

$email       = $usuario['email'] ?? '';
$tiene_clave = !empty($usuario['clave']); // las cuentas de Google no tienen contraseña

function mascara_email(string $email): string
{
    [$local, $dominio] = array_pad(explode('@', $email, 2), 2, '');
    return mb_substr($local, 0, 2) . str_repeat('*', max(mb_strlen($local) - 2, 1)) . '@' . $dominio;
}
?>

<style>
    .pw-card { max-width: 560px; }
    .pw-intro { margin: 0; font-size: .92rem; line-height: 1.5; color: #64748B; }
    .pw-form { display: flex; flex-direction: column; gap: 16px; }
    .pw-form[hidden] { display: none; }
    .pw-oculto { position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; }
    .pw-volver { text-decoration: none; }

    .pw-wrap { position: relative; }
    .campo .pw-wrap input { width: 100%; box-sizing: border-box; padding-right: 84px; }
    .campo input.pw-codigo { text-align: center; letter-spacing: .45em; font-size: 1.3rem; font-weight: 700; }
    .pw-toggle {
        position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
        padding: 6px 10px; border: none; border-radius: 6px; background: transparent;
        font: inherit; font-size: .8rem; font-weight: 600; color: #64748B; cursor: pointer;
    }
    .pw-toggle:hover { color: #084734; }
    .pw-toggle:focus-visible, .pw-btn:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }

    .pw-fuerza { height: 6px; margin-top: 8px; border-radius: 99px; background: rgba(148, 163, 184, .25); overflow: hidden; }
    .pw-fuerza > span { display: block; width: 0; height: 100%; border-radius: 99px; transition: width .2s ease, background-color .2s ease; }
    .pw-fuerza-txt { min-height: 1.2em; margin-top: 4px; font-size: .8rem; color: #64748B; }

    .pw-reqs { display: flex; flex-direction: column; gap: 4px; margin: 0; padding: 0; list-style: none; font-size: .85rem; color: #64748B; }
    .pw-reqs li::before { content: '○'; display: inline-block; width: 1.4em; }
    .pw-reqs li.ok { color: #16A34A; }
    .pw-reqs li.ok::before { content: '✓'; }

    .pw-acciones { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .pw-btn {
        padding: 12px 20px; border: 1px solid #084734; border-radius: 6px; background: #084734; color: #CFF27C;
        font: inherit; font-size: .95rem; font-weight: 600; cursor: pointer; transition: opacity .15s;
    }
    .pw-btn:hover:not(:disabled) { opacity: .88; }
    .pw-btn:disabled { opacity: .55; cursor: not-allowed; }
    .pw-btn-sec { background: transparent; border-color: rgba(148, 163, 184, .55); color: inherit; }

    .pw-msg { padding: 11px 14px; border: 1px solid transparent; border-radius: 6px; font-size: .9rem; line-height: 1.4; }
    .pw-msg[hidden] { display: none; }
    .pw-msg.error { background: rgba(239, 68, 68, .12); border-color: rgba(239, 68, 68, .35); color: #DC2626; }
    .pw-msg.ok { background: rgba(22, 163, 74, .12); border-color: rgba(22, 163, 74, .35); color: #16A34A; }
</style>

            <div class="page-header">
                <h2 class="titulo-con-icono"><img src="iconos/generales/candado.png" alt="" class="icono-titulo"> <?= $tiene_clave ? 'Cambiar contraseña' : 'Crear contraseña' ?></h2>
            </div>

            <div class="seccion-card pw-card">
                <div class="seccion-header">
                    <h3>Tu contraseña</h3>
                    <a href="perfil.php" class="btn-editar-seccion pw-volver">Volver al perfil</a>
                </div>

                <div class="seccion-body">
                    <div class="pw-msg" id="pwMsg" role="alert" hidden></div>

<?php if ($tiene_clave): ?>
                    <p class="pw-intro">Por seguridad, primero ingresá tu contraseña actual.</p>

                    <form id="formCambiar" class="pw-form" novalidate>
                        <input type="text" class="pw-oculto" name="username" value="<?= htmlspecialchars($email) ?>" autocomplete="username" readonly tabindex="-1" aria-hidden="true">

                        <div class="campo">
                            <label for="pwActual">Contraseña actual</label>
                            <div class="pw-wrap">
                                <input type="password" id="pwActual" autocomplete="current-password">
                                <button type="button" class="pw-toggle" data-target="pwActual" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                            </div>
                        </div>

                        <div class="campo">
                            <label for="pwNueva">Contraseña nueva</label>
                            <div class="pw-wrap">
                                <input type="password" id="pwNueva" autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-target="pwNueva" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                            </div>
                            <div class="pw-fuerza" aria-hidden="true"><span id="pwFuerzaBarra"></span></div>
                            <div class="pw-fuerza-txt" id="pwFuerzaTxt" aria-live="polite"></div>
                        </div>

                        <div class="campo">
                            <label for="pwConf">Confirmar contraseña nueva</label>
                            <div class="pw-wrap">
                                <input type="password" id="pwConf" autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-target="pwConf" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                            </div>
                        </div>

                        <ul class="pw-reqs">
                            <li id="reqLargo">Al menos 8 caracteres</li>
                            <li id="reqDistinta">Distinta de la contraseña actual</li>
                            <li id="reqCoincide">Coincide con la confirmación</li>
                        </ul>

                        <div class="pw-acciones">
                            <button type="submit" class="pw-btn" id="btnGuardar">Guardar contraseña</button>
                        </div>
                    </form>

<?php else: ?>
                    <div id="pwPaso1">
                        <p class="pw-intro">Entraste con Google, así que tu cuenta todavía no tiene contraseña. Para crear una, primero confirmamos que sos vos con un código de 6 dígitos que vamos a enviar a <strong><?= htmlspecialchars(mascara_email($email)) ?></strong>.</p>
                        <div class="pw-acciones" style="margin-top:16px;">
                            <button type="button" class="pw-btn" id="btnEnviarCodigo">Enviar código</button>
                            <button type="button" class="pw-btn pw-btn-sec" id="btnYaTengoCodigo">Ya tengo un código</button>
                        </div>
                    </div>

                    <form id="formCrear" class="pw-form" hidden novalidate>
                        <input type="text" class="pw-oculto" name="username" value="<?= htmlspecialchars($email) ?>" autocomplete="username" readonly tabindex="-1" aria-hidden="true">

                        <div class="campo">
                            <label for="pwCodigo">Código de 6 dígitos</label>
                            <input type="text" id="pwCodigo" class="pw-codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000">
                        </div>

                        <div class="campo">
                            <label for="pwNueva">Contraseña nueva</label>
                            <div class="pw-wrap">
                                <input type="password" id="pwNueva" autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-target="pwNueva" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                            </div>
                            <div class="pw-fuerza" aria-hidden="true"><span id="pwFuerzaBarra"></span></div>
                            <div class="pw-fuerza-txt" id="pwFuerzaTxt" aria-live="polite"></div>
                        </div>

                        <div class="campo">
                            <label for="pwConf">Confirmar contraseña nueva</label>
                            <div class="pw-wrap">
                                <input type="password" id="pwConf" autocomplete="new-password">
                                <button type="button" class="pw-toggle" data-target="pwConf" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                            </div>
                        </div>

                        <ul class="pw-reqs">
                            <li id="reqLargo">Al menos 8 caracteres</li>
                            <li id="reqCoincide">Coincide con la confirmación</li>
                        </ul>

                        <div class="pw-acciones">
                            <button type="submit" class="pw-btn" id="btnCrear">Crear contraseña</button>
                            <button type="button" class="pw-btn pw-btn-sec" id="btnReenviar">Reenviar código</button>
                        </div>
                    </form>
<?php endif; ?>
                </div>
            </div>

        </main>

<script>
(() => {
    const CSRF = <?= json_encode($csrf) ?>;
    const API  = 'api/cambiar_password.php';
    const $    = (id) => document.getElementById(id);
    const msg  = $('pwMsg');

    function mostrar(tipo, texto) {
        msg.className = 'pw-msg ' + tipo;
        msg.textContent = texto;
        msg.hidden = false;
    }
    function ocultar() { msg.hidden = true; }

    async function enviar(datos, boton, textoCargando) {
        const fd = new FormData();
        fd.append('csrf', CSRF);
        Object.keys(datos).forEach((k) => fd.append(k, datos[k]));
        const etiqueta = boton.textContent;
        boton.disabled = true;
        boton.textContent = textoCargando;
        try {
            const res = await fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' });
            let data = null;
            try { data = await res.json(); } catch (e) { /* la respuesta no era JSON */ }
            return data || { ok: false, error: 'El servidor respondió algo inesperado. Intentá de nuevo.' };
        } catch (e) {
            return { ok: false, error: 'No pudimos conectarnos con el servidor. Revisá tu conexión.' };
        } finally {
            boton.disabled = false;
            boton.textContent = etiqueta;
        }
    }

    // ── Mostrar / ocultar contraseña ─────────────────────────────────────────
    document.querySelectorAll('.pw-toggle').forEach((b) => {
        b.addEventListener('click', () => {
            const input = $(b.dataset.target);
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            b.textContent = visible ? 'Mostrar' : 'Ocultar';
            b.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            b.setAttribute('aria-pressed', visible ? 'false' : 'true');
        });
    });

    // ── Fuerza y requisitos en vivo ──────────────────────────────────────────
    const actualInput = $('pwActual');           // solo existe si la cuenta ya tiene contraseña
    const nuevaInput  = $('pwNueva');
    const confInput   = $('pwConf');
    const barra       = $('pwFuerzaBarra');
    const fuerzaTxt   = $('pwFuerzaTxt');
    const NIVELES = ['Muy débil', 'Débil', 'Aceptable', 'Buena', 'Fuerte'];
    const COLORES = ['#DC2626', '#EA580C', '#CA8A04', '#65A30D', '#16A34A'];

    function puntaje(p) {
        let s = 0;
        if (p.length >= 8) s++;
        if (p.length >= 12) s++;
        if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
        if (/\d/.test(p)) s++;
        if (/[^A-Za-z0-9]/.test(p)) s++;
        return Math.min(s, 4);
    }
    function marcar(id, ok) {
        const li = $(id);
        if (li) li.classList.toggle('ok', ok);
    }
    function actualizar() {
        const n = nuevaInput.value;
        const c = confInput.value;
        const a = actualInput ? actualInput.value : '';
        if (n.length === 0) {
            barra.style.width = '0';
            fuerzaTxt.textContent = '';
        } else {
            const nivel = puntaje(n);
            barra.style.width = ((nivel + 1) * 20) + '%';
            barra.style.backgroundColor = COLORES[nivel];
            fuerzaTxt.textContent = 'Seguridad: ' + NIVELES[nivel].toLowerCase();
        }
        marcar('reqLargo', n.length >= 8);
        marcar('reqDistinta', n.length > 0 && (a === '' || n !== a));
        marcar('reqCoincide', n.length > 0 && n === c);
    }
    [actualInput, nuevaInput, confInput].forEach((el) => { if (el) el.addEventListener('input', actualizar); });

    // El servidor repite estas reglas: esto solo evita un viaje innecesario.
    function validarLocal(n, c, a) {
        const bytes = new TextEncoder().encode(n).length;
        if (n.length < 8) return 'La contraseña nueva debe tener al menos 8 caracteres.';
        if (bytes > 72) return 'La contraseña nueva no puede superar los 72 caracteres.';
        if (a && n === a) return 'La contraseña nueva debe ser distinta de la actual.';
        if (n !== c) return 'La confirmación no coincide con la contraseña nueva.';
        return null;
    }

    let terminado = false;
    function volverAlPerfil() {
        terminado = true;
        setTimeout(() => { window.location.href = 'perfil.php'; }, 2200);
    }

    // ── Cuentas con contraseña: cambiarla ────────────────────────────────────
    const formCambiar = $('formCambiar');
    if (formCambiar) {
        formCambiar.addEventListener('submit', async (e) => {
            e.preventDefault();
            ocultar();
            const actual = actualInput.value;
            const nueva  = nuevaInput.value;
            const conf   = confInput.value;
            if (!actual) return mostrar('error', 'Ingresá tu contraseña actual.');
            const error = validarLocal(nueva, conf, actual);
            if (error) return mostrar('error', error);

            const r = await enviar({ accion: 'cambiar', actual, nueva, confirmar: conf }, $('btnGuardar'), 'Guardando…');
            if (!r.ok) return mostrar('error', r.error || 'No pudimos guardar la contraseña.');

            formCambiar.reset();
            actualizar();
            $('btnGuardar').disabled = true;
            mostrar('ok', (r.mensaje || 'Contraseña guardada.') + ' Volvemos a tu perfil…');
            volverAlPerfil();
        });
    }

    // ── Cuentas de Google: crear la contraseña con código por email ──────────
    const formCrear = $('formCrear');
    if (formCrear) {
        const btnReenviar = $('btnReenviar');
        const codigoInput = $('pwCodigo');

        codigoInput.addEventListener('input', () => {
            codigoInput.value = codigoInput.value.replace(/\D/g, '');
        });

        function mostrarPaso2() {
            $('pwPaso1').hidden = true;
            formCrear.hidden = false;
            codigoInput.focus();
        }
        function iniciarEspera(segundos, etiqueta) {
            let s = segundos;
            btnReenviar.disabled = true;
            btnReenviar.textContent = etiqueta + ' (' + s + ' s)';
            const t = setInterval(() => {
                s--;
                if (terminado) { clearInterval(t); return; }
                if (s <= 0) {
                    clearInterval(t);
                    btnReenviar.disabled = false;
                    btnReenviar.textContent = etiqueta;
                } else {
                    btnReenviar.textContent = etiqueta + ' (' + s + ' s)';
                }
            }, 1000);
        }
        async function pedirCodigo(boton, textoCargando) {
            ocultar();
            const r = await enviar({ accion: 'enviar_codigo' }, boton, textoCargando);
            if (!r.ok) {
                mostrar('error', r.error || 'No pudimos enviar el código.');
                if (r.espera) { mostrarPaso2(); iniciarEspera(r.espera, 'Reenviar código'); }
                return;
            }
            mostrar('ok', r.mensaje);
            mostrarPaso2();
            iniciarEspera(r.espera || 60, 'Reenviar código');
        }

        $('btnEnviarCodigo').addEventListener('click', () => pedirCodigo($('btnEnviarCodigo'), 'Enviando…'));
        btnReenviar.addEventListener('click', () => pedirCodigo(btnReenviar, 'Enviando…'));
        $('btnYaTengoCodigo').addEventListener('click', () => { ocultar(); mostrarPaso2(); });

        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            ocultar();
            const codigo = codigoInput.value.trim();
            const nueva  = nuevaInput.value;
            const conf   = confInput.value;
            if (!/^\d{6}$/.test(codigo)) return mostrar('error', 'El código tiene 6 dígitos.');
            const error = validarLocal(nueva, conf, '');
            if (error) return mostrar('error', error);

            const r = await enviar({ accion: 'crear', codigo, nueva, confirmar: conf }, $('btnCrear'), 'Creando…');
            if (!r.ok) return mostrar('error', r.error || 'No pudimos crear la contraseña.');

            formCrear.reset();
            actualizar();
            $('btnCrear').disabled = true;
            btnReenviar.disabled = true;
            mostrar('ok', (r.mensaje || 'Contraseña creada.') + ' Volvemos a tu perfil…');
            volverAlPerfil();
        });
    }
})();
</script>

<?php require_once 'includes/footer.php'; ?>
