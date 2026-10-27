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

// ── Selects con desplegable propio (estética Trackify) ──────────────────────
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

    const agregarOpcion = (op, contenedor) => {
        const li = document.createElement('li');
        li.setAttribute('role', 'option');
        li.dataset.valor = op.value;
        li.textContent = op.text;
        li.addEventListener('click', (e) => { e.stopPropagation(); elegir(op.value); });
        contenedor.appendChild(li);
    };

    Array.from(select.children).forEach(hijo => {
        if (hijo.tagName === 'OPTGROUP') {
            const grupo = document.createElement('li');
            grupo.className = 'select-grupo';
            grupo.setAttribute('role', 'presentation');
            grupo.textContent = hijo.label;
            lista.appendChild(grupo);
            Array.from(hijo.children).forEach(op => agregarOpcion(op, lista));
        } else {
            agregarOpcion(hijo, lista);
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

document.addEventListener('DOMContentLoaded', () => {
    initGlobals();
    mejorarSelects();

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
