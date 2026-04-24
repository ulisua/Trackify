<?php
$page = 'categorias';
$extra_css = '<link rel="stylesheet" href="css/categorias.css">';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'includes/header.php';

$user_id = $_SESSION['usuario_id'];
$mes_actual = date('m');
$anio_actual = date('Y');

// Paleta de colores predefinida
$colores = ['#F97316', '#3B82F6', '#8B5CF6', '#EC4899', '#10B981', '#F59E0B', '#EF4444', '#14B8A6', '#CFF27C', '#EA73F5'];

// Obtener todas las categorías y sumar sus movimientos
$stmt = $conn->prepare("
    SELECT c.id_categoria, c.nombre, c.tipo,
           COUNT(m.id_movimiento) as cantidad, 
           SUM(m.monto) as total_monto
    FROM categorias c
    LEFT JOIN movimientos m ON c.id_categoria = m.id_categoria AND m.id_usuario = ? AND MONTH(m.fecha) = ? AND YEAR(m.fecha) = ?
    GROUP BY c.id_categoria
    ORDER BY total_monto DESC
");
$stmt->bind_param("iii", $user_id, $mes_actual, $anio_actual);
$stmt->execute();
$res = $stmt->get_result();

$gastos = [];
$ingresos = [];
$total_gastos = 0;
$total_ingresos = 0;

$color_index_g = 0;
$color_index_i = 0;

while ($row = $res->fetch_assoc()) {
    if ($row['tipo'] === 'gasto') {
        $row['color'] = $colores[$color_index_g % count($colores)];
        $gastos[] = $row;
        $total_gastos += $row['total_monto'] ?? 0;
        $color_index_g++;
    } else {
        $row['color'] = $colores[$color_index_i % count($colores)];
        $ingresos[] = $row;
        $total_ingresos += $row['total_monto'] ?? 0;
        $color_index_i++;
    }
}
?>

            <div class="page-header">
                <h2>🏷️ Categorías</h2>
                <button class="btn-nuevo" onclick="abrirModal()">+ Nueva categoría</button>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('gastos', this)">Gastos</button>
                <button class="tab" onclick="switchTab('ingresos', this)">Ingresos</button>
            </div>

            <!-- Resumen distribución (Se muestra para gastos por defecto) -->
            <div class="resumen-cats" id="resumenGastos">
                <h3>Distribución del mes (Gastos)</h3>
                <?php if ($total_gastos > 0): ?>
                    <?php foreach ($gastos as $gasto): 
                        if (!$gasto['total_monto']) continue;
                        $pct = round(($gasto['total_monto'] / $total_gastos) * 100);
                    ?>
                    <div class="resumen-row">
                        <span class="resumen-cat-nombre">🏷️ <?php echo htmlspecialchars($gasto['nombre']); ?></span>
                        <div class="resumen-barra">
                            <div class="resumen-barra-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $gasto['color']; ?>"></div>
                        </div>
                        <span class="resumen-pct"><?php echo $pct; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748B;">No hay gastos registrados este mes.</p>
                <?php endif; ?>
            </div>

            <!-- Grid de categorías — GASTOS -->
            <div class="cat-grid" id="gridGastos">
                <?php foreach ($gastos as $gasto): 
                    $monto = $gasto['total_monto'] ?? 0;
                    $cant = $gasto['cantidad'] ?? 0;
                    $pct = $total_gastos > 0 ? round(($monto / $total_gastos) * 100) : 0;
                ?>
                <div class="cat-card">
                    <div class="cat-icon">🏷️</div>
                    <div class="cat-nombre"><?php echo htmlspecialchars($gasto['nombre']); ?></div>
                    <div class="cat-stats">
                        <span class="cat-monto">$<?php echo number_format($monto, 2, ',', '.'); ?></span>
                        <span class="cat-cant"><?php echo $cant; ?> movimiento<?php echo $cant != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $gasto['color']; ?>"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Grid de categorías — INGRESOS (oculto por defecto) -->
            <div class="cat-grid" id="gridIngresos" style="display:none">
                <?php foreach ($ingresos as $ingreso): 
                    $monto = $ingreso['total_monto'] ?? 0;
                    $cant = $ingreso['cantidad'] ?? 0;
                    $pct = $total_ingresos > 0 ? round(($monto / $total_ingresos) * 100) : 0;
                ?>
                <div class="cat-card">
                    <div class="cat-icon">🏷️</div>
                    <div class="cat-nombre"><?php echo htmlspecialchars($ingreso['nombre']); ?></div>
                    <div class="cat-stats">
                        <span class="cat-monto">$<?php echo number_format($monto, 2, ',', '.'); ?></span>
                        <span class="cat-cant"><?php echo $cant; ?> movimiento<?php echo $cant != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $ingreso['color']; ?>"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </main>
<?php 
$extra_js = '<script src="js/categorias.js"></script>';
require_once 'includes/footer.php'; 
?>