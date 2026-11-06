// Tabs: alterna grilla y resumen entre gastos e ingresos
function switchTab(tipo, btn) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    const esGastos = tipo === 'gastos';
    document.getElementById('gridGastos').hidden = !esGastos;
    document.getElementById('gridIngresos').hidden = esGastos;
    const resumenGastos = document.getElementById('resumenGastos');
    const resumenIngresos = document.getElementById('resumenIngresos');
    if (resumenGastos) resumenGastos.hidden = !esGastos;
    if (resumenIngresos) resumenIngresos.hidden = esGastos;
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
    }
}

// Orden de las tarjetas (se recuerda en el navegador). El orden inicial lo da el servidor.
(function inicializarOrdenCategorias() {
    const select = document.getElementById('ordenCategorias');
    if (!select) return;

    const CLAVE = 'trackify_orden_categorias';
    const grids = [document.getElementById('gridGastos'), document.getElementById('gridIngresos')];
    const porNombre = (a, b) => a.nombre.localeCompare(b.nombre, 'es', { sensitivity: 'base' });
    const comparadores = {
        'monto-desc':  (a, b) => b.monto - a.monto,
        'monto-asc':   (a, b) => a.monto - b.monto,
        'nombre-asc':  porNombre,
        'nombre-desc': (a, b) => porNombre(b, a),
        'cant-desc':   (a, b) => b.cant - a.cant,
        'cant-asc':    (a, b) => a.cant - b.cant
    };

    function ordenar(criterio) {
        const comparar = comparadores[criterio] || comparadores['monto-desc'];
        grids.forEach(grid => {
            if (!grid) return;
            Array.from(grid.querySelectorAll(':scope > .cat-card'))
                .map(el => ({ el, nombre: el.dataset.nombre || '', monto: parseFloat(el.dataset.monto) || 0, cant: parseInt(el.dataset.cant, 10) || 0 }))
                .sort((a, b) => comparar(a, b) || porNombre(a, b)) // empate: por nombre
                .forEach(t => grid.appendChild(t.el));
        });
    }

    try {
        const guardado = localStorage.getItem(CLAVE);
        if (guardado && comparadores[guardado]) select.value = guardado;
    } catch (e) { /* sin localStorage: orden por defecto */ }

    select.addEventListener('change', () => {
        ordenar(select.value);
        try { localStorage.setItem(CLAVE, select.value); } catch (e) { /* ignorar */ }
    });

    ordenar(select.value);
})();
