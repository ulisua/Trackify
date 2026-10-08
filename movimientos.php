<?php
$page = 'movimientos';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION['usuario_id'];



// ── ELIMINAR (AJAX) ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    header('Content-Type: application/json');
    $id = intval($_POST['id_movimiento']);
    $check = $conn->prepare("SELECT id_movimiento FROM movimientos WHERE id_movimiento=? AND id_usuario=?");
    $check->bind_param("ii", $id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) { echo json_encode(['ok' => false]); exit(); }
    $stmt = $conn->prepare("DELETE FROM movimientos WHERE id_movimiento=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

require_once 'includes/header.php';

// ── FILTROS ───────────────────────────────────────────────────────────────────
$filtro_tipo      = $_GET['tipo'] ?? '';
$filtro_categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$filtro_desde     = $_GET['desde'] ?? '';
$filtro_hasta     = $_GET['hasta'] ?? '';

// Por defecto mostrar el período activo
if (!$filtro_desde && !$filtro_hasta) {
    $filtro_desde = $fecha_desde;
    $filtro_hasta = $fecha_hasta;
}

// ── TOTALES DEL PERÍODO ───────────────────────────────────────────────────────
$stmt_tot = $conn->prepare("SELECT
    COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END), 0) as total_ingresos,
    COALESCE(SUM(CASE WHEN tipo='gasto'   THEN monto ELSE 0 END), 0) as total_gastos,
    COUNT(*) as cantidad
    FROM movimientos WHERE id_usuario=? AND fecha BETWEEN ? AND ?");
$stmt_tot->bind_param("iss", $user_id, $filtro_desde, $filtro_hasta);
$stmt_tot->execute();
$totales = $stmt_tot->get_result()->fetch_assoc();
$total_ingresos = $totales['total_ingresos'];
$total_gastos   = $totales['total_gastos'];
$balance        = $total_ingresos - $total_gastos;
$cantidad_total = $totales['cantidad'];

// ── CATEGORÍAS PARA EL FILTRO ─────────────────────────────────────────────────
$stmt_cats = $conn->prepare("SELECT DISTINCT c.id_categoria, c.nombre, c.tipo FROM categorias c INNER JOIN movimientos m ON c.id_categoria = m.id_categoria WHERE m.id_usuario=? ORDER BY c.tipo, c.nombre");
$stmt_cats->bind_param("i", $user_id);
$stmt_cats->execute();
$categorias_filtro = $stmt_cats->get_result()->fetch_all(MYSQLI_ASSOC);

// ── QUERY PRINCIPAL CON FILTROS ───────────────────────────────────────────────
$where  = "m.id_usuario=? AND m.fecha BETWEEN ? AND ?";
$params = [$user_id, $filtro_desde, $filtro_hasta];
$types  = "iss";

if ($filtro_tipo && in_array($filtro_tipo, ['ingreso', 'gasto'])) {
    $where   .= " AND m.tipo=?";
    $params[] = $filtro_tipo;
    $types   .= "s";
}
if ($filtro_categoria > 0) {
    $where   .= " AND m.id_categoria=?";
    $params[] = $filtro_categoria;
    $types   .= "i";
}

$stmt = $conn->prepare("
    SELECT m.id_movimiento, m.fecha, m.descripcion, m.monto, m.tipo, c.nombre as categoria_nombre
    FROM movimientos m
    JOIN categorias c ON m.id_categoria = c.id_categoria
    WHERE $where
    ORDER BY m.fecha DESC, m.id_movimiento DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$movimientos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Params GET para mantener filtros en links
$query_params = http_build_query(array_filter([
    'tipo'      => $filtro_tipo,
    'categoria' => $filtro_categoria ?: null,
    'desde'     => $filtro_desde,
    'hasta'     => $filtro_hasta,
]));
?>

<h2>📋 Movimientos</h2>
<p style="color:#64748b;font-size:.85rem;margin:-8px 0 20px;">
    <?php
    $labels = ['semana' => 'Esta semana', 'quincena' => 'Esta quincena', 'mes' => 'Este mes'];
    echo ($labels[$periodo_actual] ?? 'Período') . ' · ' . date('d/m/Y', strtotime($filtro_desde)) . ' — ' . date('d/m/Y', strtotime($filtro_hasta));
    ?>
</p>

<!-- RESUMEN -->
<section class="cards">
    <div class="card ingreso-card">
        <h4>Ingresos</h4>
        <p data-ars="<?= $total_ingresos ?>">$<?= number_format($total_ingresos, 2, ',', '.') ?></p>
    </div>
    <div class="card highlight">
        <h4>Balance</h4>
        <p data-ars="<?= $balance ?>" style="color:<?= $balance >= 0 ? '#10B981' : '#EF4444' ?>">$<?= number_format($balance, 2, ',', '.') ?></p>
    </div>
    <div class="card gasto-card">
        <h4>Gastos</h4>
        <p data-ars="<?= $total_gastos ?>">$<?= number_format($total_gastos, 2, ',', '.') ?></p>
    </div>
</section>

<!-- FILTROS -->
<section class="filtros" style="margin-bottom:20px;">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input type="date" name="desde" value="<?= htmlspecialchars($filtro_desde) ?>" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:6px;font-size:.9rem;">
        <input type="date" name="hasta" value="<?= htmlspecialchars($filtro_hasta) ?>" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:6px;font-size:.9rem;">

        <select name="tipo" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:6px;font-size:.9rem;background:#fff;">
            <option value="">Todos los tipos</option>
            <option value="ingreso" <?= $filtro_tipo === 'ingreso' ? 'selected' : '' ?>>💰 Ingresos</option>
            <option value="gasto"   <?= $filtro_tipo === 'gasto'   ? 'selected' : '' ?>>💸 Gastos</option>
        </select>

        <select name="categoria" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:6px;font-size:.9rem;background:#fff;">
            <option value="0">Todas las categorías</option>
            <?php
            $tipo_actual = '';
            foreach ($categorias_filtro as $cat):
                if ($cat['tipo'] !== $tipo_actual):
                    if ($tipo_actual !== '') echo '</optgroup>';
                    echo '<optgroup label="' . ($cat['tipo'] === 'ingreso' ? '💰 Ingresos' : '💸 Gastos') . '">';
                    $tipo_actual = $cat['tipo'];
                endif;
            ?>
                <option value="<?= $cat['id_categoria'] ?>" <?= $filtro_categoria === $cat['id_categoria'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nombre']) ?>
                </option>
            <?php endforeach; ?>
            <?php if ($tipo_actual !== '') echo '</optgroup>'; ?>
        </select>

        <button type="submit" style="padding:10px 20px;background:#084734;color:#CFF27C;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:.9rem;">Filtrar</button>
        <?php if ($filtro_tipo || $filtro_categoria || ($filtro_desde !== $fecha_desde) || ($filtro_hasta !== $fecha_hasta)): ?>
            <a href="movimientos.php" style="padding:10px 14px;color:#64748b;text-decoration:none;font-size:.9rem;">✕ Limpiar</a>
        <?php endif; ?>
    </form>
</section>

<!-- INFO CANTIDAD -->
<?php if (count($movimientos) > 0): ?>
<p style="color:#64748b;font-size:.85rem;margin:0 0 12px;">
    <?= count($movimientos) ?> movimiento<?= count($movimientos) != 1 ? 's' : '' ?>
    <?= ($filtro_tipo || $filtro_categoria) ? '<span style="color:#F97316;">(filtrado)</span>' : '' ?>
</p>
<?php endif; ?>

<!-- TABLA desktop -->
<section class="tabla-box">
    <table class="tabla tabla-desktop">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Categoría</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($movimientos) > 0): ?>
                <?php foreach ($movimientos as $mov): ?>
                    <tr id="fila-<?= $mov['id_movimiento'] ?>">
                        <td><?= date('d/m/Y', strtotime($mov['fecha'])) ?></td>
                        <td><?= htmlspecialchars($mov['descripcion'] ?: 'Sin descripción') ?></td>
                        <td><?= htmlspecialchars($mov['categoria_nombre']) ?></td>
                        <td>
                            <span style="
                                padding:3px 10px;
                                border-radius:99px;
                                font-size:.78rem;
                                font-weight:600;
                                background:<?= $mov['tipo'] === 'ingreso' ? '#DCFCE7' : '#FEE2E2' ?>;
                                color:<?= $mov['tipo'] === 'ingreso' ? '#16A34A' : '#DC2626' ?>;">
                                <?= $mov['tipo'] === 'ingreso' ? '💰 Ingreso' : '💸 Gasto' ?>
                            </span>
                        </td>
                        <td class="<?= $mov['tipo'] === 'ingreso' ? 'positivo' : 'negativo' ?>">
                            <span data-ars="<?= $mov['monto'] ?>" data-prefijo="<?= $mov['tipo'] === 'ingreso' ? '+' : '-' ?>">
                                <?= $mov['tipo'] === 'ingreso' ? '+' : '-' ?>$<?= number_format($mov['monto'], 2, ',', '.') ?>
                            </span>
                        </td>
                        <td>
                            <button class="delete" title="Eliminar" onclick="eliminar(<?= $mov['id_movimiento'] ?>, 'fila-<?= $mov['id_movimiento'] ?>')">🗑️</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">
                        No hay movimientos <?= ($filtro_tipo || $filtro_categoria) ? 'con esos filtros' : 'en este período' ?>.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- CARDS mobile -->
    <div class="movimiento-cards">
        <?php if (count($movimientos) > 0): ?>
            <?php foreach ($movimientos as $mov): ?>
                <div class="movimiento-card" id="card-<?= $mov['id_movimiento'] ?>">
                    <div class="mc-top">
                        <span class="mc-desc"><?= htmlspecialchars($mov['descripcion'] ?: 'Sin descripción') ?></span>
                        <span class="mc-monto <?= $mov['tipo'] === 'ingreso' ? 'positivo' : 'negativo' ?>"
                              data-ars="<?= $mov['monto'] ?>"
                              data-prefijo="<?= $mov['tipo'] === 'ingreso' ? '+' : '-' ?>">
                            <?= $mov['tipo'] === 'ingreso' ? '+' : '-' ?>$<?= number_format($mov['monto'], 2, ',', '.') ?>
                        </span>
                    </div>
                    <div class="mc-bottom">
                        <span class="mc-tag"><?= htmlspecialchars($mov['categoria_nombre']) ?></span>
                        <span class="mc-fecha"><?= date('d/m/Y', strtotime($mov['fecha'])) ?></span>
                        <span style="
                            padding:2px 8px;
                            border-radius:99px;
                            font-size:.72rem;
                            font-weight:600;
                            background:<?= $mov['tipo'] === 'ingreso' ? '#DCFCE7' : '#FEE2E2' ?>;
                            color:<?= $mov['tipo'] === 'ingreso' ? '#16A34A' : '#DC2626' ?>;">
                            <?= $mov['tipo'] === 'ingreso' ? 'Ingreso' : 'Gasto' ?>
                        </span>
                        <div class="mc-acciones">
                            <button class="delete" onclick="eliminar(<?= $mov['id_movimiento'] ?>, 'card-<?= $mov['id_movimiento'] ?>')">🗑️</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:#94a3b8;">
                No hay movimientos <?= ($filtro_tipo || $filtro_categoria) ? 'con esos filtros' : 'en este período' ?>.
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function eliminar(id, elementId) {
    if (!confirm('¿Eliminar este movimiento?')) return;
    const fd = new FormData();
    fd.append('accion', 'eliminar');
    fd.append('id_movimiento', id);
    fetch('movimientos.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) document.getElementById(elementId)?.remove();
            else alert('No se pudo eliminar.');
        })
        .catch(() => alert('Error de conexión.'));
}
</script>

<?php require_once 'includes/footer.php'; ?>
