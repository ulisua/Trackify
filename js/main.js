// js/main.js
// Comportamiento común a todas las páginas con sesión (se carga desde includes/footer.php):
//   1. Token CSRF en formularios      5. Modales de la app (abrir / cerrar / precargar)
//   2. Alertas SweetAlert2 de marca   6. Selects con desplegable propio
//   3. Menú lateral (móvil)           7. Inicialización al cargar la página
//   4. Calendarios (Flatpickr)

// ============== 1. TOKEN CSRF ==============
// Se agrega a todos los formularios POST y queda disponible para fetch (cabecera X-CSRF-Token).
// El servidor lo exige en toda petición POST con sesión (includes/seguridad.php).
window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
document.querySelectorAll('form').forEach(form => {
    if ((form.getAttribute('method') || '').toUpperCase() !== 'POST' || form.querySelector('input[name="csrf_token"]')) return;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'csrf_token';
    input.value = window.csrfToken;
    form.appendChild(input);
});

// ============== 2. ALERTAS (SWEETALERT2 CON ESTÉTICA TRACKIFY) ==============
// Todas las alertas de la app pasan por estas funciones, así siempre usan las clases
// de css/components/forms.css (.swal-trackify, .swal-btn-*) y nunca el estilo por defecto.
const swalBase = {
    buttonsStyling: false,
    customClass: {
        popup: 'swal-trackify',
        confirmButton: 'btn swal-btn-primary',
        cancelButton: 'btn swal-btn-cancel'
    }
};

// Aviso con un solo botón. icon: 'success' | 'error' | 'warning' | 'info' | 'question'
function alertaTrackify(titulo, texto, icon = 'warning') {
    if (!window.Swal) { alert(texto || titulo); return Promise.resolve(); }
    return Swal.fire({ ...swalBase, icon, title: titulo, text: texto, confirmButtonText: 'Entendido' });
}

// Confirmación con Aceptar/Cancelar. Devuelve una Promise<boolean>.
function confirmarTrackify({ titulo = '¿Estás seguro?', texto = 'Esta acción no se puede deshacer.', confirmar = 'Eliminar', icon = 'warning', peligro = true } = {}) {
    if (!window.Swal) return Promise.resolve(confirm(texto));
    return Swal.fire({
        ...swalBase,
        title: titulo,
        text: texto,
        icon,
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: peligro,
        confirmButtonText: confirmar,
        cancelButtonText: 'Cancelar',
        customClass: { ...swalBase.customClass, confirmButton: peligro ? 'btn swal-btn-danger' : 'btn swal-btn-primary' }
    }).then(r => r.isConfirmed);
}

// Aviso breve abajo a la derecha (para "guardado", "eliminado", etc.)
function avisoTrackify(mensaje) {
    if (!window.Swal) return;
    Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: mensaje, showConfirmButton: false, timer: 2600, timerProgressBar: true, customClass: { popup: 'swal-toast-trackify' } });
}

// Uso en formularios: onsubmit="confirmarEliminacion(event, 'mensaje')"
function confirmarEliminacion(e, mensaje) {
    e.preventDefault();
    const form = e.target;
    confirmarTrackify({ texto: mensaje }).then(ok => { if (ok) form.submit(); });
}

function confirmarLogout() {
    confirmarTrackify({
        titulo: '¿Cerrar sesión?',
        texto: 'Tendrás que volver a ingresar tus credenciales.',
        confirmar: 'Salir',
        icon: 'question',
        peligro: false
    }).then(ok => { if (ok) window.location.href = 'logout.php'; });
}

window.alertaTrackify = alertaTrackify;
window.confirmarTrackify = confirmarTrackify;
window.avisoTrackify = avisoTrackify;

// ============== 3. MENÚ LATERAL (MÓVIL) ==============
let sidebar, toggle, overlay;

function toggleMenu() {
    if (!sidebar) return;
    sidebar.classList.contains('open') ? cerrarMenu() : abrirMenu();
}

function abrirMenu() {
    sidebar?.classList.add('open');
    toggle?.classList.add('open');
    overlay?.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function cerrarMenu() {
    sidebar?.classList.remove('open');
    toggle?.classList.remove('open');
    overlay?.classList.remove('visible');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    cerrarMenu();
    cerrarModal();
});

// ============== 4. CALENDARIOS (FLATPICKR) ==============
// Objetivos: hoy o futuro. Movimientos: hoy o pasado. Filtros: sin restricción.
function inicializarCalendarios() {
    if (!window.flatpickr) return;
    const pad = n => String(n).padStart(2, '0');
    const ahora = new Date();
    // Fecha local (toISOString usaría UTC y de noche en Argentina daría el día siguiente)
    const hoy = `${ahora.getFullYear()}-${pad(ahora.getMonth() + 1)}-${pad(ahora.getDate())}`;
    const base = { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', locale: 'es', disableMobile: true };

    const objetivos = document.querySelectorAll('#fecha_limite, #edit_fecha_limite');
    objetivos.forEach(input => {
        if (!input.placeholder) input.placeholder = 'Selecciona fecha límite';
        if (!input.value || input.value < hoy) input.value = hoy;
    });
    if (objetivos.length) flatpickr(objetivos, { ...base, minDate: hoy });

    const movimientos = document.querySelectorAll('#fecha, #edit_mov_fecha');
    movimientos.forEach(input => {
        if (!input.placeholder) input.placeholder = 'Selecciona una fecha';
        if (!input.value || input.value > hoy) input.value = hoy;
    });
    if (movimientos.length) flatpickr(movimientos, { ...base, maxDate: hoy });

    const filtros = document.querySelectorAll('.js-fecha-filtro');
    if (filtros.length) flatpickr(filtros, base);

    // Cerrar el calendario abierto al hacer scroll fuera de él
    window.addEventListener('scroll', (e) => {
        if (e.target.closest?.('.flatpickr-calendar')) return;
        document.querySelectorAll('input').forEach(input => {
            if (input._flatpickr?.isOpen) input._flatpickr.close();
        });
    }, true);
}

// ============== 5. MODALES ==============
// Deja un formulario con monto/moneda listo para cargar (ver js/moneda.js)
function prepararFormularioMoneda(form, monedaInicial) {
    if (!form) return;
    form.querySelectorAll('.js-monto-ars').forEach(el => el.value = '');
    form.querySelectorAll('.js-preview-conversion').forEach(el => el.textContent = '');
    const select = form.querySelector('.js-moneda-form');
    if (select && monedaInicial) select.value = monedaInicial;
}

function monedaPreferida() {
    return typeof window.monedaActual === 'function' ? window.monedaActual() : 'ARS';
}

// Con Flatpickr hay que usar setDate para que también se actualice el campo visible (d/m/Y)
function asignarFecha(input, fecha) {
    if (!input) return;
    if (input._flatpickr) input._flatpickr.setDate(fecha, false);
    else input.value = fecha;
}

function mostrarModal(id) {
    const modal = document.getElementById(id);
    modal?.classList.remove('hidden');
    return modal;
}

function cerrarModal() {
    document.querySelectorAll('.modal').forEach(m => m.classList.add('hidden'));
}

// tipo: 'ingreso' | 'gasto' | 'objetivo'
function abrirModal(tipo) {
    if (tipo === 'objetivo') {
        const form = document.querySelector('#modalObjetivo form');
        if (form) {
            form.querySelectorAll('input[type="text"], .js-monto-visible').forEach(el => el.value = '');
            prepararFormularioMoneda(form, monedaPreferida());
        }
        mostrarModal('modalObjetivo');
        return;
    }

    const modal = document.getElementById('modal');
    if (!modal) return;

    document.getElementById('monto').value = '';
    prepararFormularioMoneda(modal.querySelector('form'), monedaPreferida());
    document.getElementById('tipoMovimiento').value = tipo;
    document.getElementById('modalTitulo').innerText = tipo === 'ingreso' ? 'Agregar Ingreso' : 'Agregar Gasto';
    poblarSelectorCategoria('categoria', 'customCategoria', tipo, '');

    const desc = document.getElementById('descripcion');
    desc.value = '';
    desc.dataset.sugerida = 'true';

    modal.classList.remove('hidden');
}

function abrirModalAhorro(idMeta) {
    const modal = document.getElementById('modalAhorro');
    if (!modal) return;
    document.getElementById('ahorro_id_meta').value = idMeta;
    document.getElementById('monto_ahorro').value = '';
    prepararFormularioMoneda(modal.querySelector('form'), monedaPreferida());
    modal.classList.remove('hidden');
}

// El monto se precarga en ARS (moneda fija) para no alterarlo por redondeo
function abrirModalEditarObj(id, nombre, desc, monto, fecha, estado) {
    const modal = document.getElementById('modalEditarObjetivo');
    if (!modal) return;
    document.getElementById('edit_id_meta').value = id;
    document.getElementById('edit_nombre_meta').value = nombre;
    document.getElementById('edit_desc_meta').value = desc;
    document.getElementById('edit_monto_objetivo').value = monto;
    prepararFormularioMoneda(modal.querySelector('form'), 'ARS');
    asignarFecha(document.getElementById('edit_fecha_limite'), fecha);
    document.getElementById('edit_estado').value = estado;
    modal.classList.remove('hidden');
}

function abrirModalCategoria() {
    const modal = document.getElementById('modalCategoria');
    if (!modal) return;
    const icono = document.getElementById('icono_categoria');
    if (icono && icono.options.length) icono.selectedIndex = 0;
    document.getElementById('color_categoria').value = '#EA73F5';
    modal.classList.remove('hidden');
}

function abrirModalEditarMovimiento(id, monto, categoria, descripcion, fecha, tipo) {
    const modal = document.getElementById('modalEditarMovimiento');
    if (!modal) return;

    document.getElementById('edit_mov_id').value = id;
    document.getElementById('edit_mov_monto').value = monto;
    prepararFormularioMoneda(modal.querySelector('form'), 'ARS');
    document.getElementById('modalEditarMovimientoTitulo').textContent = tipo === 'ingreso' ? 'Editar ingreso' : 'Editar gasto';
    document.getElementById('edit_mov_descripcion').value = descripcion;
    asignarFecha(document.getElementById('edit_mov_fecha'), fecha);
    document.getElementById('edit_mov_tipo').value = tipo;
    poblarSelectorCategoria('edit_mov_categoria', 'customEditMovCategoria', tipo, categoria);

    modal.classList.remove('hidden');
}

// Selector de categoría propio (modales de alta y edición de movimientos).
// idInput: input oculto que se envía; prefijo: ids "<prefijo>Wrapper/Trigger/Options".
function poblarSelectorCategoria(idInput, prefijo, tipo, seleccionada) {
    const hiddenInput = document.getElementById(idInput);
    const trigger = document.getElementById(prefijo + 'Trigger');
    const optionsContainer = document.getElementById(prefijo + 'Options');
    const wrapper = document.getElementById(prefijo + 'Wrapper');
    if (!hiddenInput || !trigger || !optionsContainer || !wrapper) return;

    hiddenInput.value = seleccionada;
    trigger.innerText = seleccionada || 'Selecciona una categoría';
    optionsContainer.innerHTML = '';
    wrapper.classList.remove('open');

    (window.dbCategorias?.[tipo] || []).forEach(nombre => {
        const div = document.createElement('div');
        div.className = 'custom-option' + (nombre === seleccionada ? ' selected' : '');
        div.innerText = nombre;
        div.addEventListener('click', () => {
            hiddenInput.value = nombre;
            trigger.innerText = nombre;
            wrapper.classList.remove('open');
            optionsContainer.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
            div.classList.add('selected');
            hiddenInput.dispatchEvent(new Event('change'));
        });
        optionsContainer.appendChild(div);
    });
}

// Abre/cierra la lista de un selector de categoría y lo cierra al tocar afuera
function enlazarSelectorCategoria(prefijo) {
    const wrapper = document.getElementById(prefijo + 'Wrapper');
    const trigger = document.getElementById(prefijo + 'Trigger');
    if (!wrapper || !trigger) return;
    trigger.addEventListener('click', () => wrapper.classList.toggle('open'));
    document.addEventListener('click', (e) => { if (!wrapper.contains(e.target)) wrapper.classList.remove('open'); });
}

// Descripción sugerida al elegir categoría en el alta (solo si el usuario no escribió otra)
const descripcionesSugeridas = {
    "Sueldo": "Cobro de sueldo mensual",
    "Préstamo recibido": "Dinero recibido por préstamo",
    "Reintegro": "Reintegro de compra anterior",
    "Ventas": "Venta de producto o servicio",
    "Inversiones": "Ganancia por inversión",
    "Intereses": "Intereses bancarios",
    "Regalos": "Dinero recibido como regalo",
    "Devoluciones": "Devolución de dinero",
    "Freelance / trabajos extra": "Pago por trabajo freelance",
    "Becas / subsidios": "Cobro de beca o ayuda económica",
    "Otros ingresos": "Otro ingreso",
    "Alimentos": "Compra en supermercado",
    "Transporte": "Pago de transporte público",
    "Vivienda": "Alquiler o expensas",
    "Servicios": "Pago de luz, agua o gas",
    "Salud": "Consulta médica o medicamentos",
    "Educación": "Pago de cursos o estudios",
    "Entretenimiento": "Salidas o actividades recreativas",
    "Compras personales": "Compra personal",
    "Deudas": "Pago de deuda o cuota",
    "Impuestos": "Pago de impuestos",
    "Mascotas": "Gastos de mascota",
    "Suscripciones": "Suscripción mensual",
    "Regalos / donaciones": "Compra de regalo o donación",
    "Ropa": "Compra de ropa",
    "Tecnología": "Compra de tecnología",
    "Viajes": "Gastos de viaje",
    "Otros gastos": "Otro gasto"
};

function enlazarDescripcionSugerida() {
    const select = document.getElementById('categoria');
    const desc = document.getElementById('descripcion');
    if (!select || !desc) return;
    select.addEventListener('change', () => {
        if (desc.value.trim() === '' || desc.dataset.sugerida === 'true') {
            desc.value = descripcionesSugeridas[select.value] || '';
            desc.dataset.sugerida = 'true';
        }
    });
    desc.addEventListener('input', () => { desc.dataset.sugerida = desc.value.trim() === '' ? 'true' : 'false'; });
}

// ============== 6. SELECTS CON DESPLEGABLE PROPIO (ESTÉTICA TRACKIFY) ==============
// El popup nativo de <select> lo dibuja el sistema y no respeta los estilos (sobre todo
// en móvil). Cada <select> visible se reemplaza por un botón + lista con el estilo de la
// app. El <select> original queda oculto y sigue siendo la fuente de verdad: se envía con
// el formulario, se le dispara 'change' al elegir y cualquier asignación por código a
// .value / .selectedIndex / .disabled actualiza el botón.
// Variantes: 'pill' (selectores del header, oscuros) y 'form' (resto, según el tema).
// La lista se agrega a <body> con position: fixed para que no la recorten los modales
// ni las tarjetas con overflow.
const selectProto = Object.getOwnPropertyDescriptors(HTMLSelectElement.prototype);

function mejorarSelect(select) {
    if (select.dataset.mejorado || select.multiple) return;
    select.dataset.mejorado = '1';
    const variante = select.closest('.selector-header') ? 'pill' : 'form';

    const wrap = document.createElement('div');
    wrap.className = variante === 'pill' ? 'selector-pill' : 'select-trackify';
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = variante === 'pill' ? 'selector-pill-trigger' : 'select-trackify-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    const etiqueta = select.getAttribute('aria-label') || select.closest('label')?.querySelector('span')?.textContent || select.title || '';
    if (etiqueta) trigger.setAttribute('aria-label', etiqueta);

    const lista = document.createElement('ul');
    lista.className = 'select-lista select-lista-' + variante;
    lista.setAttribute('role', 'listbox');

    const refrescar = () => {
        const op = select.options[select.selectedIndex];
        trigger.textContent = op ? op.text : '';
        trigger.disabled = select.disabled;
        lista.querySelectorAll('[role="option"]').forEach(li => {
            const sel = li.dataset.valor === select.value;
            li.classList.toggle('seleccionada', sel);
            li.setAttribute('aria-selected', String(sel));
        });
    };

    const cerrar = () => {
        if (!lista.classList.contains('abierta')) return;
        lista.classList.remove('abierta');
        wrap.classList.remove('abierto');
        trigger.setAttribute('aria-expanded', 'false');
    };

    const ubicar = () => {
        const r = trigger.getBoundingClientRect();
        lista.style.minWidth = r.width + 'px';
        lista.style.left = r.left + 'px';
        const espacioAbajo = window.innerHeight - r.bottom;
        const alto = Math.min(lista.scrollHeight, 300);
        if (espacioAbajo < alto + 12 && r.top > espacioAbajo) {
            lista.style.top = '';
            lista.style.bottom = (window.innerHeight - r.top + 6) + 'px';
        } else {
            lista.style.bottom = '';
            lista.style.top = (r.bottom + 6) + 'px';
        }
        // Que no se salga por la derecha
        const ancho = lista.getBoundingClientRect().width;
        if (r.left + ancho > window.innerWidth - 8) {
            lista.style.left = Math.max(8, window.innerWidth - ancho - 8) + 'px';
        }
    };

    const elegir = (valor) => {
        cerrar();
        if (select.value === valor) return;
        select.value = valor;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        trigger.focus();
    };

    const agregarOpcion = (op) => {
        const li = document.createElement('li');
        li.setAttribute('role', 'option');
        li.dataset.valor = op.value;
        li.textContent = op.text;
        li.addEventListener('click', (e) => { e.stopPropagation(); elegir(op.value); });
        lista.appendChild(li);
    };

    Array.from(select.children).forEach(hijo => {
        if (hijo.tagName === 'OPTGROUP') {
            const grupo = document.createElement('li');
            grupo.className = 'select-grupo';
            grupo.setAttribute('role', 'presentation');
            grupo.textContent = hijo.label;
            lista.appendChild(grupo);
            Array.from(hijo.children).forEach(agregarOpcion);
        } else {
            agregarOpcion(hijo);
        }
    });

    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const abrir = !lista.classList.contains('abierta');
        document.querySelectorAll('.select-lista.abierta').forEach(l => l.dispatchEvent(new Event('cerrar')));
        if (!abrir) return;
        lista.classList.add('abierta');
        wrap.classList.add('abierto');
        trigger.setAttribute('aria-expanded', 'true');
        ubicar();
        lista.querySelector('.seleccionada')?.scrollIntoView({ block: 'nearest' });
    });

    // Teclado: flechas cambian la opción como en un select nativo
    trigger.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        e.preventDefault();
        const nuevo = select.selectedIndex + (e.key === 'ArrowDown' ? 1 : -1);
        if (nuevo >= 0 && nuevo < select.options.length) elegir(select.options[nuevo].value);
    });

    lista.addEventListener('cerrar', cerrar);
    document.addEventListener('click', cerrar);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(); });
    window.addEventListener('resize', cerrar);
    window.addEventListener('scroll', (e) => { if (!lista.contains(e.target)) cerrar(); }, true);

    // Asignaciones por código (abrir modales, sincronizar moneda, habilitar en perfil)
    ['value', 'selectedIndex', 'disabled'].forEach(prop => {
        Object.defineProperty(select, prop, {
            configurable: true,
            get() { return selectProto[prop].get.call(this); },
            set(v) { selectProto[prop].set.call(this, v); refrescar(); }
        });
    });
    select.addEventListener('change', refrescar);
    document.addEventListener('trackify:moneda', refrescar);

    select.after(wrap);
    wrap.appendChild(trigger);
    document.body.appendChild(lista);
    select.hidden = true;
    refrescar();
}

function mejorarSelects(raiz = document) {
    raiz.querySelectorAll('select').forEach(select => {
        // Los selects internos de librerías (calendario, SweetAlert2) quedan como están
        if (select.closest('.flatpickr-calendar, .swal2-container')) return;
        mejorarSelect(select);
    });
}

// ============== 7. INICIALIZACIÓN ==============
document.addEventListener('DOMContentLoaded', () => {
    sidebar = document.getElementById('sidebar');
    toggle = document.getElementById('menuToggle');
    overlay = document.getElementById('sidebarOverlay');

    mejorarSelects();
    inicializarCalendarios();
    enlazarSelectorCategoria('customCategoria');
    enlazarSelectorCategoria('customEditMovCategoria');
    enlazarDescripcionSugerida();

    // En móvil, elegir una sección cierra el menú
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', () => { if (window.innerWidth <= 900) cerrarMenu(); });
    });

    // Mensaje de la última operación (lo deja el servidor en window.trackifyFlash)
    const flash = window.trackifyFlash;
    if (flash) {
        if (flash.tipo === 'success') avisoTrackify(flash.mensaje);
        else alertaTrackify('No se pudo completar', flash.mensaje, 'error');
    }
});
