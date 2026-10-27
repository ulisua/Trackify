// js/filtros_movimientos.js
// Filtro en el cliente por fecha y categoría para ingresos.php y gastos.php
// (filas de la tabla de escritorio y tarjetas de móvil con data-fecha / data-categoria).
document.addEventListener('DOMContentLoaded', () => {
    const filtroFecha = document.getElementById('filtroFecha');
    const filtroCategoria = document.getElementById('filtroCategoria');
    const btnVerTodos = document.getElementById('btnVerTodos');
    if (!filtroFecha || !filtroCategoria) return;

    function filtrar() {
        const fecha = filtroFecha.value.trim();
        const categoria = filtroCategoria.value.trim().toLowerCase();

        [['.tabla-desktop tbody tr[data-fecha]', '.tabla-desktop [data-sin-resultados]'],
         ['.movimiento-cards .movimiento-card[data-fecha]', '.movimiento-cards [data-sin-resultados]']].forEach(([selItems, selVacio]) => {
            const items = document.querySelectorAll(selItems);
            let visibles = 0;
            items.forEach(item => {
                const coincide = (!fecha || item.dataset.fecha === fecha)
                    && (!categoria || (item.dataset.categoria || '').toLowerCase() === categoria);
                item.hidden = !coincide;
                if (coincide) visibles++;
            });
            const vacio = document.querySelector(selVacio);
            if (vacio && items.length > 0) vacio.hidden = visibles > 0;
        });

        if (btnVerTodos) btnVerTodos.disabled = !fecha && !categoria;
    }

    function verTodos() {
        filtroCategoria.value = '';
        if (filtroFecha._flatpickr) filtroFecha._flatpickr.clear();
        filtroFecha.value = '';
        filtrar();
    }

    filtroFecha.addEventListener('change', filtrar);
    filtroCategoria.addEventListener('change', filtrar);
    if (btnVerTodos) btnVerTodos.addEventListener('click', verTodos);
    filtrar();
});
