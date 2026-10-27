// js/moneda.js
// Conversor de moneda reactivo para Trackify
// La base de datos almacena siempre los montos en ARS.
// moneda_principal es la preferencia de visualización del usuario (ARS o USD).

const TC_KEY    = 'trackify_tc';
const TC_TS_KEY = 'trackify_tc_ts';
const TC_TTL    = 30 * 60 * 1000; // 30 minutos

function leerStorage(clave) {
    try { return localStorage.getItem(clave); } catch (e) { return null; }
}

function guardarStorage(clave, valor) {
    try { localStorage.setItem(clave, valor); } catch (e) { /* almacenamiento no disponible */ }
}

// Devuelve la cotización USD→ARS, o 0 si no se pudo obtener ninguna.
// Nunca inventa un valor: guardar un monto con una cotización ficticia sería un error silencioso.
async function obtenerTipoCambio() {
    const ahora   = Date.now();
    const ultimo  = parseInt(leerStorage(TC_TS_KEY) || '0', 10);
    const tcGuard = parseFloat(leerStorage(TC_KEY) || '0');

    if (tcGuard > 0 && (ahora - ultimo) < TC_TTL) {
        return tcGuard;
    }

    const fuentes = [
        ['https://api.exchangerate-api.com/v4/latest/USD', data => data.rates && data.rates.ARS],
        ['https://api.frankfurter.app/latest?from=USD&to=ARS', data => data.rates && data.rates.ARS],
    ];

    for (const [url, extraer] of fuentes) {
        try {
            const res = await fetch(url);
            if (!res.ok) continue;
            const tc = parseFloat(extraer(await res.json()));
            if (tc > 0) {
                guardarStorage(TC_KEY, tc.toString());
                guardarStorage(TC_TS_KEY, ahora.toString());
                return tc;
            }
        } catch (e) { /* probar la siguiente fuente */ }
    }

    // Sin conexión con las APIs: usar la última cotización conocida aunque esté vencida
    return tcGuard > 0 ? tcGuard : 0;
}

function monedaActual() {
    const selector = document.querySelector('.js-selector-moneda');
    return selector ? selector.value : (leerStorage('trackify_moneda') || 'ARS');
}

function formatearMonto(ars, moneda, tc) {
    if (isNaN(ars)) return '$0,00';
    if (moneda === 'USD' && tc > 0) {
        return 'USD ' + (ars / tc).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return '$' + parseFloat(ars).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function convertirMontos() {
    let moneda = monedaActual();
    const tc = await obtenerTipoCambio();
    // Sin cotización no se puede mostrar USD: se muestran los pesos reales
    if (moneda === 'USD' && !(tc > 0)) moneda = 'ARS';

    document.querySelectorAll('[data-ars]').forEach(el => {
        const raw = el.getAttribute('data-ars');
        if (raw === null || raw === '') return;
        const ars = parseFloat(raw);
        if (isNaN(ars)) return;

        const prefijo = el.getAttribute('data-prefijo') || '';
        el.textContent = prefijo + formatearMonto(ars, moneda, tc);
    });

    const tcEl = document.getElementById('tipoCambioInfo');
    if (tcEl) {
        tcEl.textContent = moneda === 'USD' && tc > 0
            ? `1 USD = $${Math.round(tc).toLocaleString('es-AR')} ARS`
            : '';
    }

    document.dispatchEvent(new CustomEvent('trackify:moneda', { detail: { moneda, tc } }));
}

async function cambiarMoneda(nueva) {
    if (!['ARS', 'USD'].includes(nueva)) return;
    guardarStorage('trackify_moneda', nueva);

    document.querySelectorAll('.js-selector-moneda').forEach(s => { s.value = nueva; });
    document.querySelectorAll('.js-moneda-form').forEach(s => { s.value = nueva; s.dispatchEvent(new Event('change')); });

    try {
        const fd = new FormData();
        fd.append('moneda', nueva);
        await fetch('api/cambiar_moneda.php', { method: 'POST', body: fd, headers: { 'X-CSRF-Token': window.csrfToken || '' } });
    } catch (e) {
        console.warn('No se pudo persistir la moneda en servidor:', e);
    }

    await convertirMontos();
}

// ── Formularios con monto y selector de moneda ─────────────────────────────
// Estructura: <form data-form-moneda> con un input .js-monto-visible (lo que escribe
// el usuario), un select .js-moneda-form (ARS/USD), un input oculto .js-monto-ars
// (el que se envía al servidor, siempre en ARS) y un <p> .js-preview-conversion opcional.
function inicializarFormularioMoneda(form) {
    const visible = form.querySelector('.js-monto-visible');
    const select  = form.querySelector('.js-moneda-form');
    const oculto  = form.querySelector('.js-monto-ars');
    const preview = form.querySelector('.js-preview-conversion');
    if (!visible || !select || !oculto) return;

    async function actualizarPreview() {
        const monto = parseFloat(visible.value);
        if (!preview) return;
        if (select.value !== 'USD' || !(monto > 0)) {
            preview.textContent = '';
            return;
        }
        const tc = await obtenerTipoCambio();
        preview.textContent = tc > 0
            ? `= $${(monto * tc).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ARS`
            : 'No se pudo obtener la cotización del dólar.';
    }

    visible.addEventListener('input', actualizarPreview);
    select.addEventListener('change', actualizarPreview);

    form.addEventListener('submit', async (e) => {
        // La conversión es asíncrona: siempre se cancela el envío nativo y se reenvía al terminar
        e.preventDefault();
        const monto = parseFloat(visible.value);

        if (!(monto > 0)) {
            mostrarAvisoMonto('Ingresá un monto mayor a cero.');
            visible.focus();
            return;
        }

        if (select.value === 'USD') {
            const tc = await obtenerTipoCambio();
            if (!(tc > 0)) {
                mostrarAvisoMonto('No se pudo obtener la cotización del dólar. Cargá el monto en ARS o intentá más tarde.');
                return;
            }
            oculto.value = (monto * tc).toFixed(2);
        } else {
            oculto.value = monto.toFixed(2);
        }
        HTMLFormElement.prototype.submit.call(form);
    });
}

function mostrarAvisoMonto(texto) {
    if (window.Swal) {
        Swal.fire({ icon: 'warning', title: 'Revisá el monto', text: texto, customClass: { popup: 'swal-trackify', confirmButton: 'btn swal-btn-primary' }, buttonsStyling: false });
    } else {
        alert(texto);
    }
}

window.convertirMontos = convertirMontos;
window.cambiarMoneda = cambiarMoneda;
window.obtenerTipoCambio = obtenerTipoCambio;
window.formatearMonto = formatearMonto;
window.monedaActual = monedaActual;

document.addEventListener('DOMContentLoaded', () => {
    convertirMontos();

    document.querySelectorAll('.js-selector-moneda').forEach(selector => {
        selector.addEventListener('change', () => cambiarMoneda(selector.value));
    });

    // Los formularios de monto arrancan en la moneda elegida por el usuario
    const moneda = monedaActual();
    document.querySelectorAll('form[data-form-moneda]').forEach(form => {
        const select = form.querySelector('.js-moneda-form');
        if (select && !select.dataset.monedaFija) select.value = moneda;
        inicializarFormularioMoneda(form);
    });
});
