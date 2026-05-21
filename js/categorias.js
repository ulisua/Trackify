// Tabs
function switchTab(tipo, btn) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('gridGastos').style.display = tipo === 'gastos' ? 'grid' : 'none';
    document.getElementById('gridIngresos').style.display = tipo === 'ingresos' ? 'grid' : 'none';
}

// Modal Editar Categoría
function abrirModalEditarCategoria(id, nombre, tipo) {
    const modal = document.getElementById('modalEditarCategoria');
    if (modal) {
        document.getElementById('edit_id_categoria').value = id;
        document.getElementById('edit_nombre_categoria').value = nombre;
        document.getElementById('edit_tipo_categoria').value = tipo;
        modal.classList.remove('hidden');
    }
}
