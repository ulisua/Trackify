<?php
$page = 'gastos';
require_once 'conexion.php';
require_once 'includes/movimientos_handler.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'includes/header.php';

$user_id = $_SESSION['usuario_id'];

// Obtener el mes y año actual
$mes_actual = date('m');
$anio_actual = date('Y');

// Variables para cálculos
$total_mes = 0;
$cantidad = 0;
$promedio = 0;

// Consulta para totales del mes actual
$stmt_totales = $conn->prepare("SELECT SUM(monto) as total, COUNT(id_movimiento) as cantidad FROM movimientos WHERE id_usuario = ? AND tipo = 'gasto' AND MONTH(fecha) = ? AND YEAR(fecha) = ?");
$stmt_totales->bind_param("iii", $user_id, $mes_actual, $anio_actual);
$stmt_totales->execute();
$res_totales = $stmt_totales->get_result();

if ($row = $res_totales->fetch_assoc()) {
    $total_mes = $row['total'] ?? 0;
    $cantidad = $row['cantidad'] ?? 0;
    if ($cantidad > 0) {
        $promedio = $total_mes / $cantidad;
    }
}

// Consulta para la lista de todos los gastos (ordenados por fecha descendente)
$stmt_lista = $conn->prepare("SELECT m.id_movimiento, m.fecha, m.descripcion, m.monto, c.nombre as categoria_nombre FROM movimientos m JOIN categorias c ON m.id_categoria = c.id_categoria WHERE m.id_usuario = ? AND m.tipo = 'gasto' ORDER BY m.fecha DESC");
$stmt_lista->bind_param("i", $user_id);
$stmt_lista->execute();
$res_lista = $stmt_lista->get_result();
$gastos = [];
while ($row = $res_lista->fetch_assoc()) {
    $gastos[] = $row;
}
?>

        <h2 class="titulo-con-icono"><img src="iconos/gasto/otrosgastos.png" alt="Gastos" class="icono-titulo"> Gastos</h2>

        <!-- RESUMEN -->
        <section class="cards">
            <div class="card gasto-card">
                <h4>Total del mes</h4>
                <p>$<?php echo number_format($total_mes, 2, ',', '.'); ?></p>
            </div>
            <div class="card">
                <h4>Cantidad</h4>
                <p><?php echo $cantidad; ?></p>
            </div>
            <div class="card">
                <h4>Promedio</h4>
                <p>$<?php echo number_format($promedio, 2, ',', '.'); ?></p>
            </div>
        </section>

        <!-- ACCIONES + FILTROS en una sola línea -->
        <div class="acciones-filtros-bar">
            <div class="acciones">
                <button class="btn gasto" onclick="abrirModal('gasto')">+ Nuevo gasto</button>
            </div>
            <div class="filtros">
                <input type="date" id="filtroFecha">
                <select id="filtroCategoria">
                    <option value="Todos">Todas las categorías</option>
                    <?php
                    $stmt_cats = $conn->prepare("SELECT DISTINCT nombre FROM categorias WHERE tipo = 'gasto' ORDER BY nombre ASC");
                    $stmt_cats->execute();
                    $res_cats = $stmt_cats->get_result();
                    while ($row_cat = $res_cats->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row_cat['nombre']) . '">' . htmlspecialchars($row_cat['nombre']) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- TABLA (desktop) / CARDS (mobile) -->
        <section class="tabla-box">

            <!-- Tabla para desktop -->
            <table class="tabla tabla-desktop tabla-gastos">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Monto</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tablaGastos">
                    <?php if (count($gastos) > 0): ?>
                        <?php foreach ($gastos as $gasto): ?>
                            <tr data-fecha="<?php echo $gasto['fecha']; ?>" data-categoria="<?php echo htmlspecialchars($gasto['categoria_nombre']); ?>">
                                <td><?php echo date('d/m', strtotime($gasto['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($gasto['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($gasto['categoria_nombre']); ?></td>
                                <td class="negativo">-$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></td>
                                <td>
                                    <button class="edit" onclick="abrirModalEditarMovimiento(<?php echo $gasto['id_movimiento']; ?>, <?php echo $gasto['monto']; ?>, '<?php echo htmlspecialchars(addslashes($gasto['categoria_nombre']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($gasto['descripcion']), ENT_QUOTES); ?>', '<?php echo $gasto['fecha']; ?>', 'gasto')"><img src="iconos/generales/lapiz.png" alt="Editar" class="icono-boton"></button>
                                    <form method="POST" action="" style="display:inline;" onsubmit="confirmarEliminacion(event, '¿Estás seguro de que deseas eliminar este gasto?');">
                                        <input type="hidden" name="form_type" value="eliminar_movimiento">
                                        <input type="hidden" name="id_movimiento" value="<?php echo $gasto['id_movimiento']; ?>">
                                        <button type="submit" class="delete"><img src="iconos/generales/tachodebasura.png" alt="Eliminar" class="icono-boton"></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">No hay gastos registrados aún.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Cards para mobile -->
            <div class="movimiento-cards" id="movimientoCards">
                <?php if (count($gastos) > 0): ?>
                    <?php foreach ($gastos as $gasto): ?>
                        <div class="movimiento-card" data-fecha="<?php echo $gasto['fecha']; ?>" data-categoria="<?php echo htmlspecialchars($gasto['categoria_nombre']); ?>">
                            <div class="mc-top">
                                <span class="mc-desc"><?php echo htmlspecialchars($gasto['descripcion']); ?></span>
                                <span class="mc-monto negativo">-$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="mc-bottom">
                                <span class="mc-tag"><?php echo htmlspecialchars($gasto['categoria_nombre']); ?></span>
                                <span class="mc-fecha"><?php echo date('d/m', strtotime($gasto['fecha'])); ?></span>
                                <div class="mc-acciones">
                                    <button class="edit" onclick="abrirModalEditarMovimiento(<?php echo $gasto['id_movimiento']; ?>, <?php echo $gasto['monto']; ?>, '<?php echo htmlspecialchars(addslashes($gasto['categoria_nombre']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($gasto['descripcion']), ENT_QUOTES); ?>', '<?php echo $gasto['fecha']; ?>', 'gasto')"><img src="iconos/generales/lapiz.png" alt="Editar" class="icono-boton"></button>
                                    <form method="POST" action="" style="display:inline;" onsubmit="confirmarEliminacion(event, '¿Estás seguro de que deseas eliminar este gasto?');">
                                        <input type="hidden" name="form_type" value="eliminar_movimiento">
                                        <input type="hidden" name="id_movimiento" value="<?php echo $gasto['id_movimiento']; ?>">
                                        <button type="submit" class="delete"><img src="iconos/generales/tachodebasura.png" alt="Eliminar" class="icono-boton"></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #64748b;">No hay gastos registrados aún.</div>
                <?php endif; ?>
            </div>

        </section>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filtroFecha = document.getElementById('filtroFecha');
    const filtroCategoria = document.getElementById('filtroCategoria');
    
    function filtrar() {
        const fechaVal = filtroFecha.value;
        const catVal = filtroCategoria.value;
        
        const rows = document.querySelectorAll('.tabla-desktop tbody tr');
        rows.forEach(row => {
            if (row.cells.length === 1) return;
            const rFecha = row.getAttribute('data-fecha');
            const rCat = row.getAttribute('data-categoria');
            
            let matchFecha = !fechaVal || (rFecha === fechaVal);
            let matchCat = (catVal === 'Todos') || (rCat === catVal);
            
            if (matchFecha && matchCat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        const cards = document.querySelectorAll('.movimiento-cards .movimiento-card');
        cards.forEach(card => {
            const rFecha = card.getAttribute('data-fecha');
            const rCat = card.getAttribute('data-categoria');
            
            let matchFecha = !fechaVal || (rFecha === fechaVal);
            let matchCat = (catVal === 'Todos') || (rCat === catVal);
            
            if (matchFecha && matchCat) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    if (filtroFecha) filtroFecha.addEventListener('input', filtrar);
    if (filtroCategoria) filtroCategoria.addEventListener('change', filtrar);
});
</script>
