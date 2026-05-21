<?php
$page = 'ingresos';
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
$stmt_totales = $conn->prepare("SELECT SUM(monto) as total, COUNT(id_movimiento) as cantidad FROM movimientos WHERE id_usuario = ? AND tipo = 'ingreso' AND MONTH(fecha) = ? AND YEAR(fecha) = ?");
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

// Consulta para la lista de todos los ingresos (ordenados por fecha descendente)
$stmt_lista = $conn->prepare("SELECT m.id_movimiento, m.fecha, m.descripcion, m.monto, c.nombre as categoria_nombre FROM movimientos m JOIN categorias c ON m.id_categoria = c.id_categoria WHERE m.id_usuario = ? AND m.tipo = 'ingreso' ORDER BY m.fecha DESC");
$stmt_lista->bind_param("i", $user_id);
$stmt_lista->execute();
$res_lista = $stmt_lista->get_result();
$ingresos = [];
while ($row = $res_lista->fetch_assoc()) {
    $ingresos[] = $row;
}
?>

            <h2 class="titulo-con-icono"><img src="iconos/ingreso/ventas.png" alt="Ingresos" class="icono-titulo"> Ingresos</h2>

            <!-- RESUMEN -->
            <section class="cards">
                <div class="card ingreso-card">
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

            <!-- ACCIONES -->
            <div class="acciones">
                <button class="btn ingreso" onclick="abrirModal('ingreso')">+ Nuevo ingreso</button>
            </div>

            <!-- FILTROS -->
            <section class="filtros">
                <input type="date" id="filtroFecha">
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
            </section>

            <!-- TABLA (desktop) / CARDS (mobile) -->
            <section class="tabla-box">

                <!-- Tabla para desktop -->
                <table class="tabla tabla-desktop">
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
                        <?php if (count($ingresos) > 0): ?>
                            <?php foreach ($ingresos as $ingreso): ?>
                                <tr data-fecha="<?php echo $ingreso['fecha']; ?>" data-categoria="<?php echo htmlspecialchars($ingreso['categoria_nombre']); ?>">
                                    <td><?php echo date('d/m', strtotime($ingreso['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($ingreso['descripcion']); ?></td>
                                    <td><?php echo htmlspecialchars($ingreso['categoria_nombre']); ?></td>
                                    <td class="positivo">+$<?php echo number_format($ingreso['monto'], 2, ',', '.'); ?></td>
                                    <td>
                                        <button class="edit" onclick="abrirModalEditarMovimiento(<?php echo $ingreso['id_movimiento']; ?>, <?php echo $ingreso['monto']; ?>, '<?php echo htmlspecialchars(addslashes($ingreso['categoria_nombre']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($ingreso['descripcion']), ENT_QUOTES); ?>', '<?php echo $ingreso['fecha']; ?>', 'ingreso')"><img src="iconos/generales/lapiz.png" alt="Editar" class="icono-boton"></button>
                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este ingreso?');">
                                            <input type="hidden" name="form_type" value="eliminar_movimiento">
                                            <input type="hidden" name="id_movimiento" value="<?php echo $ingreso['id_movimiento']; ?>">
                                            <button type="submit" class="delete"><img src="iconos/generales/tachodebasura.png" alt="Eliminar" class="icono-boton"></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">No hay ingresos registrados aún.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Cards para mobile -->
                <div class="movimiento-cards" id="movimientoCards">
                    <?php if (count($ingresos) > 0): ?>
                        <?php foreach ($ingresos as $ingreso): ?>
                            <div class="movimiento-card" data-fecha="<?php echo $ingreso['fecha']; ?>" data-categoria="<?php echo htmlspecialchars($ingreso['categoria_nombre']); ?>">
                                <div class="mc-top">
                                    <span class="mc-desc"><?php echo htmlspecialchars($ingreso['descripcion']); ?></span>
                                    <span class="mc-monto positivo">+$<?php echo number_format($ingreso['monto'], 2, ',', '.'); ?></span>
                                </div>
                                <div class="mc-bottom">
                                    <span class="mc-tag"><?php echo htmlspecialchars($ingreso['categoria_nombre']); ?></span>
                                    <span class="mc-fecha"><?php echo date('d/m', strtotime($ingreso['fecha'])); ?></span>
                                    <div class="mc-acciones">
                                        <button class="edit" onclick="abrirModalEditarMovimiento(<?php echo $ingreso['id_movimiento']; ?>, <?php echo $ingreso['monto']; ?>, '<?php echo htmlspecialchars(addslashes($ingreso['categoria_nombre']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($ingreso['descripcion']), ENT_QUOTES); ?>', '<?php echo $ingreso['fecha']; ?>', 'ingreso')"><img src="iconos/generales/lapiz.png" alt="Editar" class="icono-boton"></button>
                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este ingreso?');">
                                            <input type="hidden" name="form_type" value="eliminar_movimiento">
                                            <input type="hidden" name="id_movimiento" value="<?php echo $ingreso['id_movimiento']; ?>">
                                            <button type="submit" class="delete"><img src="iconos/generales/tachodebasura.png" alt="Eliminar" class="icono-boton"></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: #64748b;">No hay ingresos registrados aún.</div>
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