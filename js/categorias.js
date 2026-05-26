// Tabs
function switchTab(tipo, btn) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('gridGastos').style.display = tipo === 'gastos' ? 'grid' : 'none';
    document.getElementById('gridIngresos').style.display = tipo === 'ingresos' ? 'grid' : 'none';
}

// Modal Editar Categoría
function abrirModalEditarCategoria(id, nombre, tipo, icono, color) {
    const modal = document.getElementById('modalEditarCategoria');
    if (modal) {
        document.getElementById('edit_id_categoria').value = id;
        document.getElementById('edit_nombre_categoria').value = nombre;
        document.getElementById('edit_tipo_categoria').value = tipo;
        document.getElementById('edit_icono_categoria').value = icono;
        document.getElementById('edit_color_categoria').value = color || '#EA73F5';
        modal.classList.remove('hidden');
        if (typeof blurBackground === 'function') {
            blurBackground(true);
        }
    }
}
