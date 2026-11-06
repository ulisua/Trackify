// js/perfil.js
// Página de perfil: datos personales, contraseña, modo oscuro y zona de peligro.
const $id = (id) => document.getElementById(id);

// ============== DATOS PERSONALES ==============
// "Editar" habilita los campos; al volver a pulsarlo ("Guardar") se envía el formulario (perfil.php).
function inicializarDatosPersonales() {
    const btn = $id('btnDatos');
    const form = $id('formDatos');
    if (!btn || !form) return;

    let editando = false;
    btn.addEventListener('click', () => {
        if (editando) {
            form.requestSubmit();
            return;
        }
        editando = true;
        form.querySelectorAll('[data-editable]').forEach(el => { el.disabled = false; });
        form.querySelector('[data-editable]')?.focus();
        btn.innerHTML = '<img src="iconos/generales/tick.png" alt="" class="icono-boton"> Guardar';
        btn.classList.add('guardando');
    });

    // El avatar muestra la inicial del nombre mientras se escribe
    const nombre = $id('inputNombre');
    const avatar = document.querySelector('.perfil-avatar-inicial');
    nombre?.addEventListener('input', () => {
        const inicial = nombre.value.trim()[0];
        if (avatar && inicial) avatar.textContent = inicial.toUpperCase();
    });
}

// ============== SEGURIDAD: CAMBIAR / CREAR CONTRASEÑA ==============
// Habla con api/cambiar_password.php (el servidor repite todas las validaciones).
// - Cuenta con contraseña: actual + nueva + confirmación.
// - Cuenta de Google sin contraseña: código por email + nueva + confirmación.
const API_CLAVE = 'api/cambiar_password.php';
const NIVELES_CLAVE = ['Muy débil', 'Débil', 'Aceptable', 'Buena', 'Fuerte'];
// De magenta (débil) a verde (fuerte), en la paleta de la marca
const COLORES_CLAVE = ['#700353', '#EA73F5', '#C9A7F0', '#9BD15A', '#2E9E6A'];

function inicializarSeguridad() {
    const btnSeg = $id('btnSeg');
    const panel = $id('pwPanel');
    const msg = $id('pwMsg');
    if (!btnSeg || !panel) return;

    const textoBoton = btnSeg.innerHTML;
    btnSeg.addEventListener('click', () => {
        const abrir = panel.hidden;
        panel.hidden = !abrir;
        btnSeg.setAttribute('aria-expanded', String(abrir));
        btnSeg.innerHTML = abrir ? 'Cancelar' : textoBoton;
        ocultarMensaje();
        if (abrir) panel.querySelector('input:not(.pw-oculto), button')?.focus();
    });

    function mostrarMensaje(tipo, texto) {
        msg.className = 'pw-msg ' + tipo;
        msg.textContent = texto;
        msg.hidden = false;
    }
    function ocultarMensaje() { msg.hidden = true; }

    async function enviar(datos, boton, textoCargando) {
        const fd = new FormData();
        Object.entries(datos).forEach(([k, v]) => fd.append(k, v));
        const etiqueta = boton.textContent;
        boton.disabled = true;
        boton.textContent = textoCargando;
        try {
            const res = await fetch(API_CLAVE, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-CSRF-Token': window.csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json().catch(() => null);
            return data || { ok: false, error: 'El servidor respondió algo inesperado. Intentá de nuevo.' };
        } catch (e) {
            return { ok: false, error: 'No pudimos conectarnos con el servidor. Revisá tu conexión.' };
        } finally {
            boton.disabled = false;
            boton.textContent = etiqueta;
        }
    }

    // Mostrar / ocultar lo escrito
    document.querySelectorAll('.pw-toggle').forEach((b) => {
        b.addEventListener('click', () => {
            const input = $id(b.dataset.target);
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            b.textContent = visible ? 'Mostrar' : 'Ocultar';
            b.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            b.setAttribute('aria-pressed', String(!visible));
        });
    });

    // Medidor de seguridad y requisitos en vivo
    const actualInput = $id('pwActual'); // solo existe si la cuenta ya tiene contraseña
    const nuevaInput = $id('pwNueva');
    const confInput = $id('pwConf');
    const barra = $id('pwFuerzaBarra');
    const fuerzaTxt = $id('pwFuerzaTxt');

    function puntaje(p) {
        let s = 0;
        if (p.length >= 8) s++;
        if (p.length >= 12) s++;
        if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
        if (/\d/.test(p)) s++;
        if (/[^A-Za-z0-9]/.test(p)) s++;
        return Math.min(s, 4);
    }
    function marcar(id, ok) { $id(id)?.classList.toggle('ok', ok); }
    function actualizarRequisitos() {
        const n = nuevaInput.value;
        const a = actualInput ? actualInput.value : '';
        if (!n) {
            barra.style.width = '0';
            fuerzaTxt.textContent = '';
        } else {
            const nivel = puntaje(n);
            barra.style.width = ((nivel + 1) * 20) + '%';
            barra.style.backgroundColor = COLORES_CLAVE[nivel];
            fuerzaTxt.textContent = 'Seguridad: ' + NIVELES_CLAVE[nivel].toLowerCase();
        }
        marcar('reqLargo', n.length >= 8);
        marcar('reqDistinta', n.length > 0 && (a === '' || n !== a));
        marcar('reqCoincide', n.length > 0 && n === confInput.value);
    }
    [actualInput, nuevaInput, confInput].forEach(el => el?.addEventListener('input', actualizarRequisitos));

    // Evita un viaje al servidor con errores obvios
    function validarLocal(n, c, a) {
        if (n.length < 8) return 'La contraseña nueva debe tener al menos 8 caracteres.';
        if (new TextEncoder().encode(n).length > 72) return 'La contraseña nueva no puede superar los 72 caracteres.';
        if (a && n === a) return 'La contraseña nueva debe ser distinta de la actual.';
        if (n !== c) return 'La confirmación no coincide con la contraseña nueva.';
        return null;
    }

    // ── Cuenta con contraseña: cambiarla ──
    const formCambiar = $id('formCambiar');
    formCambiar?.addEventListener('submit', async (e) => {
        e.preventDefault();
        ocultarMensaje();
        const actual = actualInput.value;
        const nueva = nuevaInput.value;
        const conf = confInput.value;
        if (!actual) return mostrarMensaje('error', 'Ingresá tu contraseña actual.');
        const error = validarLocal(nueva, conf, actual);
        if (error) return mostrarMensaje('error', error);

        const r = await enviar({ accion: 'cambiar', actual, nueva, confirmar: conf }, $id('btnGuardarClave'), 'Guardando…');
        if (!r.ok) return mostrarMensaje('error', r.error || 'No pudimos guardar la contraseña.');

        formCambiar.reset();
        actualizarRequisitos();
        btnSeg.click(); // cierra el panel
        avisoTrackify(r.mensaje || 'Contraseña actualizada.');
    });

    // ── Cuenta de Google: crear la contraseña con un código por email ──
    const formCrear = $id('formCrear');
    if (!formCrear) return;
    const btnReenviar = $id('btnReenviar');
    const codigoInput = $id('pwCodigo');
    let temporizador = null;

    codigoInput.addEventListener('input', () => { codigoInput.value = codigoInput.value.replace(/\D/g, ''); });

    function mostrarPaso2() {
        $id('pwPaso1').hidden = true;
        formCrear.hidden = false;
        codigoInput.focus();
    }
    function iniciarEspera(segundos) {
        clearInterval(temporizador);
        let s = segundos;
        const pintar = () => { btnReenviar.textContent = `Reenviar código (${s} s)`; };
        btnReenviar.disabled = true;
        pintar();
        temporizador = setInterval(() => {
            s--;
            if (s > 0) return pintar();
            clearInterval(temporizador);
            btnReenviar.disabled = false;
            btnReenviar.textContent = 'Reenviar código';
        }, 1000);
    }
    async function pedirCodigo(boton) {
        ocultarMensaje();
        const r = await enviar({ accion: 'enviar_codigo' }, boton, 'Enviando…');
        if (!r.ok) {
            mostrarMensaje('error', r.error || 'No pudimos enviar el código.');
            if (r.espera) { mostrarPaso2(); iniciarEspera(r.espera); }
            return;
        }
        mostrarMensaje('ok', r.mensaje);
        mostrarPaso2();
        iniciarEspera(r.espera || 60);
    }

    $id('btnEnviarCodigo').addEventListener('click', (e) => pedirCodigo(e.currentTarget));
    btnReenviar.addEventListener('click', () => pedirCodigo(btnReenviar));
    $id('btnYaTengoCodigo').addEventListener('click', () => { ocultarMensaje(); mostrarPaso2(); });

    formCrear.addEventListener('submit', async (e) => {
        e.preventDefault();
        ocultarMensaje();
        const codigo = codigoInput.value.trim();
        const nueva = nuevaInput.value;
        const conf = confInput.value;
        if (!/^\d{6}$/.test(codigo)) return mostrarMensaje('error', 'El código tiene 6 dígitos.');
        const error = validarLocal(nueva, conf, '');
        if (error) return mostrarMensaje('error', error);

        const r = await enviar({ accion: 'crear', codigo, nueva, confirmar: conf }, $id('btnCrearClave'), 'Creando…');
        if (!r.ok) return mostrarMensaje('error', r.error || 'No pudimos crear la contraseña.');

        // La sección pasa a "Cambiar contraseña": se recarga para mostrar el nuevo estado
        clearInterval(temporizador);
        formCrear.querySelectorAll('button, input').forEach(el => { el.disabled = true; });
        mostrarMensaje('ok', r.mensaje || 'Contraseña creada.');
        setTimeout(() => window.location.reload(), 2000);
    });
}

// ============== MODO OSCURO ==============
function inicializarControlTema() {
    const interruptor = $id('btnModoOscuro');
    if (!interruptor) return;
    interruptor.checked = document.documentElement.getAttribute('data-theme') === 'dark';
    interruptor.addEventListener('change', () => {
        const tema = interruptor.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', tema);
        try { localStorage.setItem('theme', tema); } catch (e) { /* sin almacenamiento */ }
    });
}

// ============== ZONA DE PELIGRO ==============
function inicializarExportar() {
    const btn = $id('btnExportarDatos');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = 'Exportando...';
        try {
            if (typeof window.TrackifyExportCSV?.fetchAndExport !== 'function') {
                throw new Error('No se cargó js/utils/exportCSV.js');
            }
            const hoy = new Date().toISOString().slice(0, 10);
            await window.TrackifyExportCSV.fetchAndExport('export_movimientos.php', `trackify_${hoy}.csv`, ['tipo', 'categoria', 'monto', 'fecha', 'descripcion']);
            btn.innerHTML = '<img src="iconos/generales/tick.png" alt="" class="icono-boton"> Exportado';
        } catch (err) {
            console.error(err);
            btn.textContent = 'Error al exportar';
        } finally {
            btn.disabled = false;
            setTimeout(() => { btn.innerHTML = original; }, 2500);
        }
    });
}

// Al eliminar la cuenta se borran las preferencias guardadas en el navegador
function limpiarLocalStorageTrackify() {
    try {
        Object.keys(localStorage).forEach(key => { if (/^trackify/i.test(key)) localStorage.removeItem(key); });
    } catch (e) { /* almacenamiento no disponible */ }
}

// Modales de "Borrar historial" y "Eliminar cuenta" (en includes/footer.php): el botón de
// confirmar solo se habilita al escribir la frase exacta (el servidor también la exige).
function configurarModalConfirmacion({ modalId, inputId, btnId, formId, abrirId, frase, alEnviar }) {
    const modal = $id(modalId);
    const input = $id(inputId);
    const btn = $id(btnId);
    const form = $id(formId);
    if (!modal || !input || !btn || !form) return { cerrar() {} };

    const validar = () => { btn.disabled = input.value.trim() !== frase; };
    input.addEventListener('input', validar);

    $id(abrirId)?.addEventListener('click', () => {
        input.value = '';
        validar();
        modal.classList.remove('hidden');
        setTimeout(() => input.focus(), 100);
    });

    form.addEventListener('submit', (e) => {
        if (input.value.trim() !== frase) { e.preventDefault(); return; }
        alEnviar?.();
        btn.disabled = true;
        btn.textContent = 'Eliminando...';
    });

    return { cerrar() { modal.classList.add('hidden'); } };
}

let modalBorrarHistorial, modalEliminarCuenta;
// Usadas por los botones "Cancelar" de los modales
function cerrarModalBorrar() { modalBorrarHistorial?.cerrar(); }
function cerrarModalEliminarCuenta() { modalEliminarCuenta?.cerrar(); }

function inicializarZonaPeligro() {
    inicializarExportar();
    modalBorrarHistorial = configurarModalConfirmacion({
        modalId: 'modalBorrarHistorial', inputId: 'inputConfirmacionBorrar', btnId: 'btnConfirmarBorrar',
        formId: 'formBorrarHistorial', abrirId: 'btnBorrarHistorial', frase: 'SI, ESTOY SEGURO'
    });
    modalEliminarCuenta = configurarModalConfirmacion({
        modalId: 'modalEliminarCuenta', inputId: 'inputConfirmacionEliminarCuenta', btnId: 'btnConfirmarEliminarCuenta',
        formId: 'formEliminarCuentaModal', abrirId: 'btnEliminarCuenta', frase: 'ELIMINAR MI CUENTA',
        alEnviar: limpiarLocalStorageTrackify
    });
}

// El script se carga al final del <body>: el DOM ya está listo
inicializarDatosPersonales();
inicializarSeguridad();
inicializarControlTema();
inicializarZonaPeligro();
