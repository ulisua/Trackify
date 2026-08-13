// ── Trackify · Conversor de moneda ───────────────────────────────────────────
// Los montos en la BD siempre se guardan en ARS.
// La moneda preferida del usuario se guarda en la BD (usuarios.moneda_principal).

const TC_KEY    = 'trackify_tc';     // tipo de cambio cacheado en localStorage
const TC_TS_KEY = 'trackify_tc_ts';  // timestamp de la última consulta
const TC_TTL    = 30 * 60 * 1000;   // 30 minutos

// ── Obtener tipo de cambio oficial ARS/USD ────────────────────────────────────
async function obtenerTipoCambio() {
    const ahora   = Date.now();
    const ultimo  = parseInt(localStorage.getItem(TC_TS_KEY) || '0');
    const tcGuard = parseFloat(localStorage.getItem(TC_KEY) || '0');

    if (tcGuard > 0 && ahora - ultimo < TC_TTL) {
        return tcGuard;
    }

    // Intentar con exchangerate-api primero, luego fallback con frankfurter
    try {
        const res  = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
        if (!res.ok) throw new Error('API falló');
        const data = await res.json();
        const tc   = data.rates['ARS'];
        if (!tc) throw new Error('ARS no encontrado');
        localStorage.setItem(TC_KEY, tc);
        localStorage.setItem(TC_TS_KEY, ahora.toString());
        return tc;
    } catch (e) {
        // Fallback: frankfurter.app
        try {
            const res2  = await fetch('https://api.frankfurter.app/latest?from=USD&to=ARS');
            const data2 = await res2.json();
            const tc2   = data2.rates['ARS'];
            localStorage.setItem(TC_KEY, tc2);
            localStorage.setItem(TC_TS_KEY, ahora.toString());
            return tc2;
        } catch (e2) {
            // Si ambas fallan, usar el último guardado o fallback fijo
            return tcGuard > 0 ? tcGuard : 1000;
        }
    }
}

// ── Formatear monto según moneda ──────────────────────────────────────────────
function formatearMonto(ars, moneda, tc) {
    if (moneda === 'USD') {
        const usd = ars / tc;
        return 'USD ' + usd.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    return '$' + parseFloat(ars).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── Convertir todos los montos en la página ───────────────────────────────────
async function convertirMontos() {
    const selector = document.getElementById('selectorMoneda');
    const moneda   = selector ? selector.value : 'ARS';
    const tc       = await obtenerTipoCambio();

    document.querySelectorAll('[data-ars]').forEach(el => {
        const ars     = parseFloat(el.getAttribute('data-ars'));
        const prefijo = el.getAttribute('data-prefijo') || '';
        el.textContent = prefijo + formatearMonto(ars, moneda, tc);
    });

    // Mostrar tipo de cambio actual
    const tcEl = document.getElementById('tipoCambioInfo');
    if (tcEl) {
        tcEl.textContent = moneda === 'USD'
            ? `1 USD = $${parseFloat(tc).toLocaleString('es-AR', { maximumFractionDigits: 0 })} ARS`
            : '';
    }
}

// ── Cambiar moneda → guardar en BD ────────────────────────────────────────────
async function cambiarMoneda(nueva) {
    try {
        const fd = new FormData();
        fd.append('moneda', nueva);
        await fetch('api/cambiar_moneda.php', { method: 'POST', body: fd });
    } catch (e) {
        console.error('No se pudo guardar la moneda:', e);
    }
    convertirMontos();
}

// ── Inicializar al cargar ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    convertirMontos();

    const selector = document.getElementById('selectorMoneda');
    if (selector) {
        selector.addEventListener('change', () => cambiarMoneda(selector.value));
    }
});
