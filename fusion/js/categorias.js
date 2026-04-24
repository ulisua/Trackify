// Tabs
function switchTab(tipo, btn) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('gridGastos').style.display = tipo === 'gastos' ? 'grid' : 'none';
    document.getElementById('gridIngresos').style.display = tipo === 'ingresos' ? 'grid' : 'none';
}

// Modal
function abrirModal() { document.getElementById('modalCat').classList.remove('hidden'); }
function cerrarModal() { document.getElementById('modalCat').classList.add('hidden'); }
document.getElementById('modalCat').addEventListener('click', function (e) { if (e.target === this) cerrarModal(); });

// Emoji picker
function selEmoji(btn) {
    document.querySelectorAll('.emoji-opt').forEach(b => b.classList.remove('sel'));
    btn.classList.add('sel');
}
