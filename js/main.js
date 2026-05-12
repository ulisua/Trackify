const sidebar = document.getElementById('sidebar');
const toggle = document.getElementById('menuToggle');
const overlay = document.getElementById('sidebarOverlay');

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

// Close sidebar on link click if mobile
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 900) cerrarMenu();
    });
});

function abrirModal(tipo) {
    if(tipo === 'objetivo') {
        const modalObj = document.getElementById('modalObjetivo');
        if (modalObj) {
            modalObj.classList.remove('hidden');
            // No se añade desenfoque extra al layout para evitar bloquear el formulario
        }
        return;
    }
    
    const modal = document.getElementById('modal');
    if (!modal) return;
    
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
            if (tipo === 'ingreso') {
                opciones = ["Sueldo", "Transferencia", "Préstamo recibido", "Otros ingresos"];
            } else {
                opciones = ["Alimentos", "Transporte", "Servicios", "Otros gastos"];
            }
        }
        
        opciones.forEach(o => {
            const div = document.createElement('div');
            div.className = 'custom-option';
            div.innerText = o;
            div.addEventListener('click', () => {
                hiddenInput.value = o;
                trigger.innerText = o;
                wrapper.classList.remove('open');
                
                // remove selected from others
                optionsContainer.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
                div.classList.add('selected');
                
                // Trigger change event manually for description logic
                hiddenInput.dispatchEvent(new Event('change'));
            });
            optionsContainer.appendChild(div);
        });
    }
    // reset description
    const desc = document.getElementById('descripcion');
    desc.value = '';
    desc.dataset.sugerida = 'true';
    
    
    modal.classList.remove('hidden');
    // No se añade desenfoque extra al layout para evitar bloquear el formulario
}

function blurBackground(enable) {
    // Ya no es necesario, el CSS backdrop-filter del modal maneja el desenfoque del fondo
}

function cerrarModal() {
    const modales = ['modal', 'modalObjetivo', 'modalAhorro', 'modalEditarObjetivo', 'modalCategoria'];
    modales.forEach(id => {
        const m = document.getElementById(id);
        if (m) m.classList.add('hidden');
    });
    blurBackground(false);
}

function abrirModalAhorro(id_meta) {
    const modal = document.getElementById('modalAhorro');
    if (modal) {
        document.getElementById('ahorro_id_meta').value = id_meta;
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
        document.getElementById('edit_fecha_limite').value = fecha;
        document.getElementById('edit_estado').value = estado;
        modal.classList.remove('hidden');
        blurBackground(true);
    }
}

function abrirModalCategoria() {
    const modal = document.getElementById('modalCategoria');
    if (modal) {
        modal.classList.remove('hidden');
        blurBackground(true);
    }
}

const descripcionesSugeridas = {
    // Ingresos
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
    // Gastos
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

document.addEventListener('DOMContentLoaded', () => {
    // Custom select toggle
    const wrapper = document.getElementById('customCategoriaWrapper');
    const trigger = document.getElementById('customCategoriaTrigger');
    if (wrapper && trigger) {
        trigger.addEventListener('click', () => {
            wrapper.classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });
    }

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
        desc.addEventListener('input', () => {
            if(desc.value.trim() !== '') {
                desc.dataset.sugerida = 'false';
            } else {
                desc.dataset.sugerida = 'true';
            }
        });
    }
});
