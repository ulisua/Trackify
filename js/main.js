// Elementos globales del layout
let sidebar, toggle, overlay;

function initGlobals() {
    sidebar = document.getElementById('sidebar');
    toggle = document.getElementById('menuToggle');
    overlay = document.getElementById('sidebarOverlay');
}

function toggleMenu() {
    if(!sidebar) return;
    const isOpen = sidebar.classList.contains('open');
    isOpen ? cerrarMenu() : abrirMenu();
}

function abrirMenu() {
    if(sidebar) sidebar.classList.add('open');
    if(toggle) toggle.classList.add('open');
    if(overlay) overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function cerrarMenu() {
    if(sidebar) sidebar.classList.remove('open');
    if(toggle) toggle.classList.remove('open');
    if(overlay) overlay.classList.remove('visible');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        cerrarMenu();
        document.querySelectorAll('.selector-pill.abierto').forEach(p => p.classList.remove('abierto'));
        if(typeof cerrarModal === 'function') cerrarModal();
    }
});

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

function abrirModal(tipo) {
    if(tipo === 'objetivo') {
        const modalObj = document.getElementById('modalObjetivo');
        if (modalObj) {
            const form = modalObj.querySelector('form');
            if (form) {
                form.querySelectorAll('input[type="text"], .js-monto-visible').forEach(el => el.value = '');
                prepararFormularioMoneda(form, monedaPreferida());
            }
            modalObj.classList.remove('hidden');
        }
        return;
    }

    const modal = document.getElementById('modal');
    if (!modal) return;

    const montoVisible = document.getElementById('monto');
    if (montoVisible) montoVisible.value = '';
    prepararFormularioMoneda(modal.querySelector('form'), monedaPreferida());

    const modalTitulo = document.getElementById('modalTitulo');
    const tipoMovimiento = document.getElementById('tipoMovimiento');
    tipoMovimiento.value = tipo;
    modalTitulo.innerText = tipo === 'ingreso' ? 'Agregar Ingreso' : 'Agregar Gasto';

    // Populate custom categories based on 'tipo'
    const hiddenInput = document.getElementById('categoria');
    const trigger = document.getElementById('customCategoriaTrigger');
    const optionsContainer = document.getElementById('customCategoriaOptions');
    const wrapper = document.getElementById('customCategoriaWrapper');

    if (hiddenInput && trigger && optionsContainer && wrapper) {
        hiddenInput.value = '';
        trigger.innerText = 'Selecciona una categoría';
        optionsContainer.innerHTML = '';
        wrapper.classList.remove('open');

        let opciones = [];
        if (window.dbCategorias) {
            opciones = window.dbCategorias[tipo] || [];
        } else {
            opciones = tipo === 'ingreso' ? ["Sueldo", "Transferencia", "Préstamo recibido", "Otros ingresos"] : ["Alimentos", "Transporte", "Servicios", "Otros gastos"];
        }

        opciones.forEach(o => {
            const div = document.createElement('div');
            div.className = 'custom-option';
            div.innerText = o;
            div.addEventListener('click', () => {
                hiddenInput.value = o;
                trigger.innerText = o;
                wrapper.classList.remove('open');
                optionsContainer.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
                div.classList.add('selected');
                hiddenInput.dispatchEvent(new Event('change'));
            });
            optionsContainer.appendChild(div);
        });
    }

    const desc = document.getElementById('descripcion');
    if (desc) {
        desc.value = '';
        desc.dataset.sugerida = 'true';
    }

    modal.classList.remove('hidden');
}

function blurBackground(enable) {
    // El CSS del modal ya maneja backdrop-filter; función mantenida para compatibilidad
}

function cerrarModal() {
    const modales = ['modal', 'modalObjetivo', 'modalAhorro', 'modalEditarObjetivo', 'modalCategoria', 'modalEditarCategoria', 'modalEditarMovimiento', 'modalBorrarHistorial', 'modalEliminarCuenta'];
    modales.forEach(id => {
        const m = document.getElementById(id);
        if (m) m.classList.add('hidden');
    });
    blurBackground(false);
}

function abrirModalAhorro(id_meta) {
    const modal = document.getElementById('modalAhorro');
    if (modal) {
        const input = document.getElementById('ahorro_id_meta');
        if (input) input.value = id_meta;
        const monto = document.getElementById('monto_ahorro');
        if (monto) monto.value = '';
        prepararFormularioMoneda(modal.querySelector('form'), monedaPreferida());
        modal.classList.remove('hidden');
        blurBackground(true);
    }
}

function abrirModalEditarObj(id, nombre, desc, monto, fecha, estado) {
    const modal = document.getElementById('modalEditarObjetivo');
    if (modal) {
        document.getElementById('edit_id_meta').value = id;
        document.getElementById('edit_nombre_meta').value = nombre;
        document.getElementById('edit_desc_meta').value = desc;
        document.getElementById('edit_monto_objetivo').value = monto;
        prepararFormularioMoneda(modal.querySelector('form'), 'ARS');
        document.getElementById('edit_fecha_limite').value = fecha;
        document.getElementById('edit_estado').value = estado;
        modal.classList.remove('hidden');
        blurBackground(true);
    }
}

function abrirModalCategoria() {
    const modal = document.getElementById('modalCategoria');
    if (modal) {
        const iconoSelect = document.getElementById('icono_categoria');
        const colorInput = document.getElementById('color_categoria');
        if (iconoSelect) iconoSelect.value = iconoSelect.options[0]?.value || iconoSelect.value;
        if (colorInput) colorInput.value = '#EA73F5';
        modal.classList.remove('hidden');
        blurBackground(true);
    }
}

function abrirModalEditarMovimiento(id, monto, categoria, descripcion, fecha, tipo) {
    const modal = document.getElementById('modalEditarMovimiento');
    if (!modal) return;

    document.getElementById('edit_mov_id').value = id;
    document.getElementById('edit_mov_monto').value = monto;
    prepararFormularioMoneda(modal.querySelector('form'), 'ARS');
    const titulo = document.getElementById('modalEditarMovimientoTitulo');
    if (titulo) titulo.textContent = tipo === 'ingreso' ? 'Editar ingreso' : 'Editar gasto';
    document.getElementById('edit_mov_descripcion').value = descripcion;
    document.getElementById('edit_mov_fecha').value = fecha;
    document.getElementById('edit_mov_tipo').value = tipo;

    const hiddenInput = document.getElementById('edit_mov_categoria');
    const trigger = document.getElementById('customEditMovCategoriaTrigger');
    const optionsContainer = document.getElementById('customEditMovCategoriaOptions');
    const wrapper = document.getElementById('customEditMovCategoriaWrapper');

    if (hiddenInput && trigger && optionsContainer && wrapper) {
        hiddenInput.value = categoria;
        trigger.innerText = categoria;
        optionsContainer.innerHTML = '';
        wrapper.classList.remove('open');

        let opciones = [];
        if (window.dbCategorias) {
            opciones = window.dbCategorias[tipo] || [];
        } else {
            opciones = tipo === 'ingreso' ? ["Sueldo", "Transferencia", "Préstamo recibido", "Otros ingresos"] : ["Alimentos", "Transporte", "Servicios", "Otros gastos"];
        }

        opciones.forEach(o => {
            const div = document.createElement('div');
            div.className = 'custom-option';
            if (o === categoria) div.classList.add('selected');
            div.innerText = o;
            div.addEventListener('click', () => {
                hiddenInput.value = o;
                trigger.innerText = o;
                wrapper.classList.remove('open');
                optionsContainer.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
                div.classList.add('selected');
            });
            optionsContainer.appendChild(div);
        });
    }

    modal.classList.remove('hidden');
}

const descripcionesSugeridas = {
    "Sueldo": "Cobro de sueldo mensual",
    "Transferencia": "Transferencia recibida de un tercero",
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

// ── Selectores de moneda/período con desplegable propio ─────────────────────
// El popup nativo de <select> lo dibuja el sistema (en móvil no respeta los estilos).
// Se muestra un botón + lista con la estética de Trackify; el <select> original queda
// oculto y sigue siendo la fuente de verdad (se le dispara 'change' al elegir).
function mejorarSelectoresHeader() {
    document.querySelectorAll('.selector-header select').forEach(select => {
        if (select.dataset.mejorado) return;
        select.dataset.mejorado = '1';

        const wrap = document.createElement('div');
        wrap.className = 'selector-pill';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'selector-pill-trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-label', select.getAttribute('aria-label') || '');
        const lista = document.createElement('ul');
        lista.className = 'selector-pill-opciones';
        lista.setAttribute('role', 'listbox');

        const refrescar = () => {
            trigger.textContent = select.options[select.selectedIndex]?.text || '';
            lista.querySelectorAll('li').forEach(li => li.classList.toggle('seleccionada', li.dataset.valor === select.value));
        };
        const cerrar = () => { wrap.classList.remove('abierto'); trigger.setAttribute('aria-expanded', 'false'); };

        Array.from(select.options).forEach(op => {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.dataset.valor = op.value;
            li.textContent = op.text;
            li.addEventListener('click', () => {
                cerrar();
                if (select.value === op.value) return;
                select.value = op.value;
                refrescar();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            lista.appendChild(li);
        });

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const abrir = !wrap.classList.contains('abierto');
            document.querySelectorAll('.selector-pill.abierto').forEach(p => p.classList.remove('abierto'));
            wrap.classList.toggle('abierto', abrir);
            trigger.setAttribute('aria-expanded', String(abrir));
        });
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) cerrar(); });
        // La otra copia del selector (navbar ↔ menú) cambia el valor sin evento: se resincroniza
        document.addEventListener('trackify:moneda', refrescar);

        select.after(wrap);
        wrap.append(trigger, lista);
        select.hidden = true;
        refrescar();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initGlobals();
    mejorarSelectoresHeader();

    // Sidebar link behavior (close on mobile)
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 900) cerrarMenu();
        });
    });

    // Custom select (crear opciones dinámicas cuando se abre el modal)
    const wrapper = document.getElementById('customCategoriaWrapper');
    const trigger = document.getElementById('customCategoriaTrigger');
    if (wrapper && trigger) {
        trigger.addEventListener('click', () => wrapper.classList.toggle('open'));
        document.addEventListener('click', (e) => { if (!wrapper.contains(e.target)) wrapper.classList.remove('open'); });
    }

    const editWrapper = document.getElementById('customEditMovCategoriaWrapper');
    const editTrigger = document.getElementById('customEditMovCategoriaTrigger');
    if (editWrapper && editTrigger) {
        editTrigger.addEventListener('click', () => editWrapper.classList.toggle('open'));
        document.addEventListener('click', (e) => { if (!editWrapper.contains(e.target)) editWrapper.classList.remove('open'); });
    }

    // Auto sugerir descripciones según categoría
    const select = document.getElementById('categoria');
    const desc = document.getElementById('descripcion');
    if(select && desc) {
        select.addEventListener('change', () => {
            if(desc.value.trim() === '' || desc.dataset.sugerida === 'true') {
                const sugerida = descripcionesSugeridas[select.value] || '';
                desc.value = sugerida;
                desc.dataset.sugerida = 'true';
            }
        });
        desc.addEventListener('input', () => { desc.dataset.sugerida = desc.value.trim() === '' ? 'true' : 'false'; });
    }
});