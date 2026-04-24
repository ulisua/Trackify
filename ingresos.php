<?php
$page = 'ingresos';
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

            <h2>💸 Ingresos</h2>

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

            <!-- FILTROS (visuales, funcionalidad futura) -->
            <section class="filtros">
                <input type="date">
                <select>
                    <option>Todos</option>
                    <option>Sueldo</option>
                    <option>Freelance</option>
                    <option>Inversiones</option>
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
                                <tr>
                                    <td><?php echo date('d/m', strtotime($ingreso['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($ingreso['descripcion']); ?></td>
                                    <td><?php echo htmlspecialchars($ingreso['categoria_nombre']); ?></td>
                                    <td class="positivo">+$<?php echo number_format($ingreso['monto'], 2, ',', '.'); ?></td>
                                    <td>
                                        <button class="edit">✏️</button>
                                        <button class="delete">🗑️</button>
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
                            <div class="movimiento-card">
                                <div class="mc-top">
                                    <span class="mc-desc"><?php echo htmlspecialchars($ingreso['descripcion']); ?></span>
                                    <span class="mc-monto positivo">+$<?php echo number_format($ingreso['monto'], 2, ',', '.'); ?></span>
                                </div>
                                <div class="mc-bottom">
                                    <span class="mc-tag"><?php echo htmlspecialchars($ingreso['categoria_nombre']); ?></span>
                                    <span class="mc-fecha"><?php echo date('d/m', strtotime($ingreso['fecha'])); ?></span>
                                    <div class="mc-acciones">
                                        <button class="edit">✏️</button>
                                        <button class="delete">🗑️</button>
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