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
