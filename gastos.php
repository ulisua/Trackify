<?php
$page = 'gastos';
require_once 'conexion.php';
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

        <h2>💸 Gastos</h2>

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

        <!-- ACCIONES -->
        <div class="acciones">
            <button class="btn gasto" onclick="abrirModal('gasto')">+ Nuevo gasto</button>
        </div>

        <!-- FILTROS -->
        <section class="filtros">
            <input type="date">
            <select>
                <option>Todos</option>
                <option>Comida</option>
                <option>Transporte</option>
                <option>Servicios</option>
                <option>Entretenimiento</option>
                <option>Salud</option>
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
                <tbody id="tablaGastos">
                    <?php if (count($gastos) > 0): ?>
                        <?php foreach ($gastos as $gasto): ?>
                            <tr>
                                <td><?php echo date('d/m', strtotime($gasto['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($gasto['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($gasto['categoria_nombre']); ?></td>
                                <td class="negativo">-$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></td>
                                <td>
                                    <button class="edit">✏️</button>
                                    <button class="delete">🗑️</button>
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
                        <div class="movimiento-card">
                            <div class="mc-top">
                                <span class="mc-desc"><?php echo htmlspecialchars($gasto['descripcion']); ?></span>
                                <span class="mc-monto negativo">-$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="mc-bottom">
                                <span class="mc-tag"><?php echo htmlspecialchars($gasto['categoria_nombre']); ?></span>
                                <span class="mc-fecha"><?php echo date('d/m', strtotime($gasto['fecha'])); ?></span>
                                <div class="mc-acciones">
                                    <button class="edit">✏️</button>
                                    <button class="delete">🗑️</button>
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
