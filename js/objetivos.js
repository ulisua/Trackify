function abrirModal() { document.getElementById('modalObj').classList.remove('hidden'); }
function cerrarModal() { document.getElementById('modalObj').classList.add('hidden'); }
document.getElementById('modalObj').addEventListener('click', function (e) { if (e.target === this) cerrarModal(); });
