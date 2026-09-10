<?php
$page = 'ingresos';
require_once 'conexion.php';
require_once 'includes/movimientos_handler.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'includes/periodo.php';
require_once 'includes/header.php';

$user_id = $_SESSION['usuario_id'];

// Variables para cálculos
$total_mes = 0;
$cantidad = 0;
$promedio = 0;

// Consulta para totales del período actual
$stmt_totales = $conn->prepare("SELECT SUM(monto) as total, COUNT(id_movimiento) as cantidad FROM movimientos WHERE id_usuario = ? AND tipo = 'ingreso' AND fecha BETWEEN ? AND ?");
$stmt_totales->bind_param("iss", $user_id, $fecha_desde, $fecha_hasta);
$stmt_totales->execute();
$res_totales = $stmt_totales->get_result();

if ($row = $res_totales->fetch_assoc()) {
    $total_mes = $row['total'] ?? 0;
    $cantidad = $row['cantidad'] ?? 0;
    if ($cantidad > 0) {
        $promedio = $total_mes / $cantidad;
    }
}

// Consulta para la lista de ingresos del período actual
$stmt_lista = $conn->prepare("SELECT m.id_movimiento, m.fecha, m.descripcion, m.monto, c.nombre as categoria_nombre FROM movimientos m JOIN categorias c ON m.id_categoria = c.id_categoria WHERE m.id_usuario = ? AND m.tipo = 'ingreso' AND m.fecha BETWEEN ? AND ? ORDER BY m.fecha DESC, m.id_movimiento DESC");
$stmt_lista->bind_param("iss", $user_id, $fecha_desde, $fecha_hasta);
$stmt_lista->execute();
$res_lista = $stmt_lista->get_result();
$ingresos = [];
while ($row = $res_lista->fetch_assoc()) {
    $ingresos[] = $row;
}

$titulo_periodo = $periodo_actual === 'mes' ? 'del mes' : ($periodo_actual === 'semana' ? 'de la semana' : 'de la quincena');
?>

            <h2 class="titulo-con-icono"><img src="iconos/ingreso/ventas.png" alt="Ingresos" class="icono-titulo"> Ingresos</h2>

            <!-- RESUMEN -->
            <section class="cards">
                <div class="card ingreso-card">
                    <h4>Total <?php echo $titulo_periodo; ?></h4>
                    <p id="totalIngresosPeriodo" data-ars="<?php echo $total_mes; ?>">$<?php echo number_format($total_mes, 2, ',', '.'); ?></p>
                </div>
                <div class="card">
                    <h4>Cantidad</h4>
                    <p><?php echo $cantidad; ?></p>
                </div>
                <div class="card">
                    <h4>Promedio</h4>
                    <p id="promedioIngresosPeriodo" data-ars="<?php echo $promedio; ?>">$<?php echo number_format($promedio, 2, ',', '.'); ?></p>
                </div>
            </section>

            <!-- ACCIONES + FILTROS en una sola línea -->
            <div class="acciones-filtros-bar">
                <div class="acciones">
                    <button class="btn ingreso btn-nuevo-movimiento" onclick="abrirModal('ingreso')">+ Nuevo ingreso</button>
                </div>
                <div class="filtros">
                    <div class="filtro-fecha-wrap" style="position:relative;display:flex;align-items:center;">
                        <input type="date" id="filtroFecha" placeholder="Todas las fechas" title="Filtrar por fecha">
                        <button type="button" id="btnLimpiarFecha" class="btn-limpiar-filtro" title="Quitar filtro de fecha" style="background:none;border:none;color:#94a3b8;font-size:1.1rem;cursor:pointer;padding:0 8px;margin-left:-32px;z-index:2;display:none;line-height:1;">✕</button>
                    </div>
                    <select id="filtroCategoria">
                        <option value="Todos">Todas las categorías</option>
                        <?php
                        $stmt_cats = $conn->prepare("SELECT DISTINCT nombre FROM categorias WHERE tipo = 'ingreso' ORDER BY nombre ASC");
                        $stmt_cats->execute();
                        $res_cats = $stmt_cats->get_result();
                        while ($row_cat = $res_cats->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row_cat['nombre']) . '">' . htmlspecialchars($row_cat['nombre']) . '</option>';
                        }
                        ?>
                    </select>
                    <button type="button" id="btnVerTodos" class="btn-reset-filtros" title="Quitar todos los filtros" style="background:transparent;border:1px solid var(--border-color,#e2e8f0);border-radius:6px;padding:8px 14px;color:var(--text-secondary,#64748B);cursor:pointer;font-size:0.875rem;font-weight:500;white-space:nowrap;">Ver todos</button>
                </div>
            </div>

            <!-- TABLA (desktop) / CARDS (mobile) -->
            <section class="tabla-box">

                <!-- Tabla para desktop -->
                <table class="tabla tabla-desktop tabla-ingresos">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Monto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tablaIngresos">
                        <tr id="filaSinResultados" style="display:none;">
                            <td colspan="5" style="text-align:center;padding:20px;color:#64748b;">No se encontraron ingresos con los filtros seleccionados.</td>
                        </tr>
                        <?php if (count($ingresos) > 0): ?>
                            <?php foreach ($ingresos as $ingreso): ?>
                                <tr data-fecha="<?php echo $ingreso['fecha']; ?>" data-categoria="<?php echo htmlspecialchars($ingreso['categoria_nombre']); ?>">
                                    <td><?php echo date('d/m', strtotime($ingreso['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($ingreso['descripcion']); ?></td>
                                    <td><?php echo htmlspecialchars($ingreso['categoria_nombre']); ?></td>
                                    <td class="positivo"><span data-ars="<?php echo $ingreso['monto']; ?>" data-prefijo="+">+$<?php echo number_format($ingreso['monto'], 2, ',', '.'); ?></span></td>
                                    <td>
                                        <button class="edit" onclick="abrirModalEditarMovimiento(<?php echo $ingreso['id_movimiento']; ?>, <?php echo $ingreso['monto']; ?>, '<?php echo htmlspecialchars(addslashes($ingreso['categoria_nombre']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($ingreso['descripcion']), ENT_QUOTES); ?>', '<?php echo $ingreso['fecha']; ?>', 'ingreso')"><img src="iconos/generales/lapiz.png" alt="Editar" class="icono-boton"></button>
                                        <form method="POST" action="" style="display:inline;" onsubmit="confirmarEliminacion(event, '¿Estás seguro de que deseas eliminar este ingreso?');">
                                            <input type="hidden" name="form_type" value="eliminar_movimiento">
                                            <input type="hidden" name="id_movimiento" value="<?php echo $ingreso['id_movimiento']; ?>">
                                            <button type="submit" class="delete"><img src="iconos/generales/tachodebasura.png" alt="Eliminar" class="icono-boton"></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">No hay ingresos registrados en este período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Cards para mobile -->
                <div class="movimiento-cards" id="movimientoCards">
                    <div id="cardSinResultados" style="display:none;text-align:center;padding:20px;color:#64748b;">No se encontraron ingresos con los filtros seleccionados.</div>
                    <?php if (count($ingresos) > 0): ?>
                        <?php foreach ($ingresos as $ingreso): ?>
                            <div class="movimiento-card" data-fecha="<?php echo $ingreso['fecha']; ?>" data-categoria="<?php echo htmlspecialchars($ingreso['categoria_nombre']); ?>">
                                <div class="mc-top">
                                    <span class="mc-desc"><?php echo htmlspecialchars($ingreso['descripcion']); ?></span>
                                    <span class="mc-monto positivo" data-ars="<?php echo $ingreso['monto']; ?>" data-prefijo="+">+$<?php echo number_format($ingreso['monto'], 2, ',', '.'); ?></span>
                                </div>
                                <div class="mc-bottom">
                                    <span class="mc-tag"><?php echo htmlspecialchars($ingreso['categoria_nombre']); ?></span>
                                    <span class="mc-fecha"><?php echo date('d/m', strtotime($ingreso['fecha'])); ?></span>
                                    <div class="mc-acciones">
                                        <button class="edit" onclick="abrirModalEditarMovimiento(<?php echo $ingreso['id_movimiento']; ?>, <?php echo $ingreso['monto']; ?>, '<?php echo htmlspecialchars(addslashes($ingreso['categoria_nombre']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($ingreso['descripcion']), ENT_QUOTES); ?>', '<?php echo $ingreso['fecha']; ?>', 'ingreso')"><img src="iconos/generales/lapiz.png" alt="Editar" class="icono-boton"></button>
                                        <form method="POST" action="" style="display:inline;" onsubmit="confirmarEliminacion(event, '¿Estás seguro de que deseas eliminar este ingreso?');">
                                            <input type="hidden" name="form_type" value="eliminar_movimiento">
                                            <input type="hidden" name="id_movimiento" value="<?php echo $ingreso['id_movimiento']; ?>">
                                            <button type="submit" class="delete"><img src="iconos/generales/tachodebasura.png" alt="Eliminar" class="icono-boton"></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #64748b;">No hay ingresos registrados en este período.</div>
                    <?php endif; ?>
                </div>

            </section>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filtroFecha = document.getElementById('filtroFecha');
    const filtroCategoria = document.getElementById('filtroCategoria');
    const btnLimpiarFecha = document.getElementById('btnLimpiarFecha');
    const btnVerTodos = document.getElementById('btnVerTodos');

    function filtrar() {
        const rawFecha = filtroFecha ? filtroFecha.value.trim() : '';
        const rawCat   = filtroCategoria ? filtroCategoria.value.trim() : 'Todos';

        const esTodasFechas = !rawFecha || rawFecha === '' || rawFecha === 'Todos' || rawFecha === 'Todas las fechas';
        const esTodasCategorias = !rawCat || rawCat === '' || rawCat === 'Todos' || rawCat === 'Todas las categorías';

        if (btnLimpiarFecha) {
            btnLimpiarFecha.style.display = rawFecha ? 'inline-block' : 'none';
        }

        let visibles = 0;
        document.querySelectorAll('.tabla-desktop tbody tr[data-fecha]').forEach(row => {
            const rFecha = row.getAttribute('data-fecha') || '';
            const rCat   = row.getAttribute('data-categoria') || '';

            const matchFecha = esTodasFechas || (rFecha === rawFecha);
            const matchCat   = esTodasCategorias || (rCat.toLowerCase() === rawCat.toLowerCase());

            const mostrar = matchFecha && matchCat;
            row.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });

        const filaVacia = document.getElementById('filaSinResultados');
        if (filaVacia) {
            const totalRegistros = document.querySelectorAll('.tabla-desktop tbody tr[data-fecha]').length;
            filaVacia.style.display = (visibles === 0 && totalRegistros > 0) ? '' : 'none';
        }

        let visiblesCards = 0;
        document.querySelectorAll('.movimiento-cards .movimiento-card[data-fecha]').forEach(card => {
            const rFecha = card.getAttribute('data-fecha') || '';
            const rCat   = card.getAttribute('data-categoria') || '';

            const matchFecha = esTodasFechas || (rFecha === rawFecha);
            const matchCat   = esTodasCategorias || (rCat.toLowerCase() === rawCat.toLowerCase());

            const mostrar = matchFecha && matchCat;
            card.style.display = mostrar ? '' : 'none';
            if (mostrar) visiblesCards++;
        });

        const cardVacia = document.getElementById('cardSinResultados');
        if (cardVacia) {
            const totalCards = document.querySelectorAll('.movimiento-cards .movimiento-card[data-fecha]').length;
            cardVacia.style.display = (visiblesCards === 0 && totalCards > 0) ? '' : 'none';
        }
    }

    function resetearFiltroFecha() {
        if (filtroFecha) {
            filtroFecha.value = '';
            if (filtroFecha._flatpickr) {
                filtroFecha._flatpickr.clear();
            }
            filtrar();
        }
    }

    function resetearTodosLosFiltros() {
        if (filtroCategoria) {
            filtroCategoria.value = 'Todos';
        }
        resetearFiltroFecha();
    }

    if (filtroFecha) {
        filtroFecha.addEventListener('input', filtrar);
        filtroFecha.addEventListener('change', filtrar);
    }

    if (filtroCategoria) {
        filtroCategoria.addEventListener('change', filtrar);
    }

    if (btnLimpiarFecha) {
        btnLimpiarFecha.addEventListener('click', resetearFiltroFecha);
    }

    if (btnVerTodos) {
        btnVerTodos.addEventListener('click', resetearTodosLosFiltros);
    }

    // Inicializar Flatpickr si está disponible pero sin preseleccionar fecha
    if (filtroFecha && window.flatpickr) {
        flatpickr(filtroFecha, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            locale: "es",
            allowInput: true,
            onChange: function(selectedDates, dateStr) {
                filtrar();
            }
        });
    }

    // Ejecutar filtro inicial limpio para mostrar todos
    filtrar();
});
</script>