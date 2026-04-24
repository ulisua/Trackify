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
    if(tipo === 'objetivo') return; // Puedes agregar lógica para objetivo luego
    
    const modal = document.getElementById('modal');
    if (!modal) return;
    
    const modalTitulo = document.getElementById('modalTitulo');
    const tipoMovimiento = document.getElementById('tipoMovimiento');
    
    tipoMovimiento.value = tipo;
    modalTitulo.innerText = tipo === 'ingreso' ? 'Agregar Ingreso' : 'Agregar Gasto';
    
    modal.classList.remove('hidden');
    // Desenfoca y oscurece el layout de fondo usando la clase del css
    const layout = document.querySelector('.layout');
    const navbar = document.querySelector('.navbar');
    if (layout) layout.classList.add('layout-blur');
    if (navbar) navbar.classList.add('layout-blur');
}

function cerrarModal() {
    const modal = document.getElementById('modal');
    if (!modal) return;
    modal.classList.add('hidden');
    
    // Quita el desenfoque
    const layout = document.querySelector('.layout');
    const navbar = document.querySelector('.navbar');
    if (layout) layout.classList.remove('layout-blur');
    if (navbar) navbar.classList.remove('layout-blur');
}
