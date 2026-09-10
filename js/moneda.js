// js/moneda.js
// Conversor de moneda reactivo para Trackify
// La base de datos almacena siempre los montos en ARS.
// moneda_principal es la preferencia de visualización del usuario (ARS o USD).

const TC_KEY    = 'trackify_tc';
const TC_TS_KEY = 'trackify_tc_ts';
const TC_TTL    = 30 * 60 * 1000; // 30 minutos

async function obtenerTipoCambio() {
    const ahora   = Date.now();
    const ultimo  = parseInt(localStorage.getItem(TC_TS_KEY) || '0', 10);
    const tcGuard = parseFloat(localStorage.getItem(TC_KEY) || '0');

    if (tcGuard > 0 && (ahora - ultimo) < TC_TTL) {
        return tcGuard;
    }

    // Intentar con ExchangeRate-API
    try {
        const res = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
        if (!res.ok) throw new Error('API 1 falló');
        const data = await res.json();
        const tc = data.rates && data.rates['ARS'];
        if (!tc) throw new Error('ARS no encontrado');
        localStorage.setItem(TC_KEY, tc.toString());
        localStorage.setItem(TC_TS_KEY, ahora.toString());
        return tc;
    } catch (e1) {
        // Fallback: Frankfurter
        try {
            const res2 = await fetch('https://api.frankfurter.app/latest?from=USD&to=ARS');
            if (!res2.ok) throw new Error('API 2 falló');
            const data2 = await res2.json();
            const tc2 = data2.rates && data2.rates['ARS'];
            if (!tc2) throw new Error('ARS no encontrado en fallback');
            localStorage.setItem(TC_KEY, tc2.toString());
            localStorage.setItem(TC_TS_KEY, ahora.toString());
            return tc2;
        } catch (e2) {
            // Fallback al valor cacheado o valor fijo
            return tcGuard > 0 ? tcGuard : 1000;
        }
    }
}

function formatearMonto(ars, moneda, tc) {
    if (isNaN(ars)) return '$0,00';
    if (moneda === 'USD') {
        const usd = tc > 0 ? (ars / tc) : 0;
        return 'USD ' + usd.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return '$' + parseFloat(ars).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function convertirMontos() {
    const selector = document.getElementById('selectorMoneda');
    const moneda = selector ? selector.value : (localStorage.getItem('trackify_moneda') || 'ARS');
    const tc = await obtenerTipoCambio();

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
}

async function cambiarMoneda(nueva) {
    if (!['ARS', 'USD'].includes(nueva)) return;
    localStorage.setItem('trackify_moneda', nueva);

    // Sincronizar selector del modal si existe
    const modalMoneda = document.getElementById('monedaIngreso');
    if (modalMoneda) modalMoneda.value = nueva;

    try {
        const fd = new FormData();
        fd.append('moneda', nueva);
        await fetch('api/cambiar_moneda.php', { method: 'POST', body: fd });
    } catch (e) {
        console.warn('No se pudo persistir la moneda en servidor:', e);
    }

    await convertirMontos();
}

window.convertirMontos = convertirMontos;
window.cambiarMoneda = cambiarMoneda;
window.obtenerTipoCambio = obtenerTipoCambio;

document.addEventListener('DOMContentLoaded', () => {
    convertirMontos();

    const selector = document.getElementById('selectorMoneda');
    if (selector) {
        selector.addEventListener('change', () => cambiarMoneda(selector.value));
    }
});
