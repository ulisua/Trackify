<?php
$page = 'movimientos';
$extra_css = '<link rel="stylesheet" href="css/pages/movimientos.css">';
require_once 'conexion.php';
// Editar / eliminar usan el handler común (PRG con return_url)
require_once 'includes/movimientos_handler.php';
require_once 'includes/header.php';

$user_id = (int)$_SESSION['usuario_id'];

// ── FILTROS ───────────────────────────────────────────────────────────────────
$filtro_tipo      = in_array($_GET['tipo'] ?? '', ['ingreso', 'gasto'], true) ? $_GET['tipo'] : '';
$filtro_categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$filtro_desde     = fechaValida($_GET['desde'] ?? '') ?? '';
$filtro_hasta     = fechaValida($_GET['hasta'] ?? '') ?? '';

// Por defecto mostrar el período activo
if (!$filtro_desde && !$filtro_hasta) {
    $filtro_desde = $fecha_desde;
    $filtro_hasta = $fecha_hasta;
}
if (!$filtro_desde) $filtro_desde = '1970-01-01';
if (!$filtro_hasta) $filtro_hasta = date('Y-m-d');
if ($filtro_desde > $filtro_hasta) {
    [$filtro_desde, $filtro_hasta] = [$filtro_hasta, $filtro_desde];
}
$hay_filtros = $filtro_tipo || $filtro_categoria || $filtro_desde !== $fecha_desde || $filtro_hasta !== $fecha_hasta;

// ── TOTALES DEL RANGO ─────────────────────────────────────────────────────────
$stmt_tot = $conn->prepare("SELECT
    COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END), 0) as total_ingresos,
    COALESCE(SUM(CASE WHEN tipo='gasto'   THEN monto ELSE 0 END), 0) as total_gastos
    FROM movimientos WHERE id_usuario=? AND fecha BETWEEN ? AND ?");
$stmt_tot->bind_param("iss", $user_id, $filtro_desde, $filtro_hasta);
$stmt_tot->execute();
$totales = $stmt_tot->get_result()->fetch_assoc();
$total_ingresos = (float)$totales['total_ingresos'];
$total_gastos   = (float)$totales['total_gastos'];
$balance        = $total_ingresos - $total_gastos;

// ── CATEGORÍAS PARA EL FILTRO (visibles para el usuario) ──────────────────────
$categorias_filtro = obtenerCategoriasVisibles($conn, $user_id);

// ── QUERY PRINCIPAL CON FILTROS ───────────────────────────────────────────────
$where  = "m.id_usuario=? AND m.fecha BETWEEN ? AND ?";
$params = [$user_id, $filtro_desde, $filtro_hasta];
$types  = "iss";

if ($filtro_tipo) {
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

$etiquetas_periodo = ['semana' => 'Esta semana', 'quincena' => 'Esta quincena', 'mes' => 'Este mes'];
$subtitulo = ($hay_filtros ? 'Rango elegido' : ($etiquetas_periodo[$periodo_actual] ?? 'Período'))
    . ' · ' . date('d/m/Y', strtotime($filtro_desde)) . ' — ' . date('d/m/Y', strtotime($filtro_hasta));

function argsEditarMov($mov) {
    return implode(', ', [
        (int)$mov['id_movimiento'],
        jsAttr((float)$mov['monto']),
        jsAttr($mov['categoria_nombre']),
        jsAttr($mov['descripcion'] ?? ''),
        jsAttr($mov['fecha']),
        jsAttr($mov['tipo']),
    ]);
}

function badgeTipo($tipo) {
    return $tipo === 'ingreso'
        ? '<span class="badge-tipo badge-tipo-ingreso"><img src="iconos/generales/flechaarriba.png" alt="" class="icono-badge"> Ingreso</span>'
        : '<span class="badge-tipo badge-tipo-gasto"><img src="iconos/generales/flechaabajo.png" alt="" class="icono-badge"> Gasto</span>';
}

$return_url_mov = htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'movimientos.php', ENT_QUOTES);
?>

<div class="pagina-movimientos">

<div class="page-header">
    <div class="page-header-texto">
        <h2 class="titulo-con-icono"><img src="iconos/generales/listaultmov1.png" alt="" class="icono-titulo"> Movimientos</h2>
        <p class="page-subtitle"><?php echo htmlspecialchars($subtitulo); ?></p>
    </div>
    <div class="page-header-acciones">
        <button class="btn ingreso btn-header" onclick="abrirModal('ingreso')">+ Ingreso</button>
        <button class="btn gasto btn-header" onclick="abrirModal('gasto')">+ Gasto</button>
    </div>
</div>

<!-- RESUMEN -->
<section class="cards">
    <div class="card ingreso-card">
        <h4>Ingresos</h4>
        <p data-ars="<?= $total_ingresos ?>">$<?= number_format($total_ingresos, 2, ',', '.') ?></p>
    </div>
    <div class="card highlight">
        <h4>Balance</h4>
        <p data-ars="<?= $balance ?>">$<?= number_format($balance, 2, ',', '.') ?></p>
    </div>
    <div class="card gasto-card">
        <h4>Gastos</h4>
        <p data-ars="<?= $total_gastos ?>">$<?= number_format($total_gastos, 2, ',', '.') ?></p>
    </div>
</section>

<!-- FILTROS -->
<form method="GET" class="filtros-movimientos">
    <label class="filtro-campo">
        <span class="filtro-etiqueta">Desde</span>
        <input type="text" name="desde" class="js-fecha-filtro" value="<?= htmlspecialchars($filtro_desde) ?>" readonly>
    </label>
    <label class="filtro-campo">
        <span class="filtro-etiqueta">Hasta</span>
        <input type="text" name="hasta" class="js-fecha-filtro" value="<?= htmlspecialchars($filtro_hasta) ?>" readonly>
    </label>
    <label class="filtro-campo">
        <span class="filtro-etiqueta">Tipo</span>
        <select name="tipo">
            <option value="">Todos los tipos</option>
            <option value="ingreso" <?= $filtro_tipo === 'ingreso' ? 'selected' : '' ?>>Ingresos</option>
            <option value="gasto"   <?= $filtro_tipo === 'gasto'   ? 'selected' : '' ?>>Gastos</option>
        </select>
    </label>
    <label class="filtro-campo">
        <span class="filtro-etiqueta">Categoría</span>
        <select name="categoria">
            <option value="0">Todas las categorías</option>
            <?php foreach (['ingreso' => 'Ingresos', 'gasto' => 'Gastos'] as $tipo_cat => $etiqueta_cat): ?>
                <optgroup label="<?= $etiqueta_cat ?>">
                    <?php foreach ($categorias_filtro[$tipo_cat] as $cat): ?>
                        <option value="<?= (int)$cat['id_categoria'] ?>" <?= $filtro_categoria === (int)$cat['id_categoria'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="filtro-botones">
        <button type="submit" class="btn btn-filtrar">Filtrar</button>
        <?php if ($hay_filtros): ?>
            <a href="movimientos.php" class="btn-reset-filtros">Limpiar</a>
        <?php endif; ?>
    </div>
</form>

<p class="movimientos-cantidad">
    <?= count($movimientos) ?> movimiento<?= count($movimientos) != 1 ? 's' : '' ?>
    <?php if ($filtro_tipo || $filtro_categoria): ?><span class="etiqueta-filtrado">filtrado</span><?php endif; ?>
</p>

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
                <th><span class="sr-only">Acciones</span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $mov): $signo = $mov['tipo'] === 'ingreso' ? '+' : '-'; ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($mov['fecha'])) ?></td>
                    <td><?= htmlspecialchars($mov['descripcion'] ?: 'Sin descripción') ?></td>
                    <td><?= htmlspecialchars($mov['categoria_nombre']) ?></td>
                    <td><?= badgeTipo($mov['tipo']) ?></td>
                    <td class="<?= $mov['tipo'] === 'ingreso' ? 'positivo' : 'negativo' ?>">
                        <span data-ars="<?= (float)$mov['monto'] ?>" data-prefijo="<?= $signo ?>"><?= $signo ?>$<?= number_format($mov['monto'], 2, ',', '.') ?></span>
                    </td>
                    <td class="celda-acciones">
                        <button type="button" class="edit" aria-label="Editar" onclick="abrirModalEditarMovimiento(<?= argsEditarMov($mov) ?>)"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"></button>
                        <form method="POST" action="movimientos.php" class="form-inline" onsubmit="confirmarEliminacion(event, '¿Eliminar este movimiento?');">
                            <input type="hidden" name="form_type" value="eliminar_movimiento">
                            <input type="hidden" name="id_movimiento" value="<?= (int)$mov['id_movimiento'] ?>">
                            <input type="hidden" name="return_url" value="<?= $return_url_mov ?>">
                            <button type="submit" class="delete" aria-label="Eliminar"><img src="iconos/generales/tachodebasura.png" alt="" class="icono-boton"></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$movimientos): ?>
                <tr class="fila-vacia">
                    <td colspan="6">No hay movimientos <?= ($filtro_tipo || $filtro_categoria) ? 'con esos filtros' : 'en este período' ?>.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- CARDS mobile -->
    <div class="movimiento-cards">
        <?php foreach ($movimientos as $mov): $signo = $mov['tipo'] === 'ingreso' ? '+' : '-'; ?>
            <div class="movimiento-card">
                <div class="mc-top">
                    <span class="mc-desc"><?= htmlspecialchars($mov['descripcion'] ?: 'Sin descripción') ?></span>
                    <span class="mc-monto <?= $mov['tipo'] === 'ingreso' ? 'positivo' : 'negativo' ?>" data-ars="<?= (float)$mov['monto'] ?>" data-prefijo="<?= $signo ?>"><?= $signo ?>$<?= number_format($mov['monto'], 2, ',', '.') ?></span>
                </div>
                <div class="mc-bottom">
                    <?= badgeTipo($mov['tipo']) ?>
                    <span class="mc-tag"><?= htmlspecialchars($mov['categoria_nombre']) ?></span>
                    <span class="mc-fecha"><?= date('d/m/Y', strtotime($mov['fecha'])) ?></span>
                    <div class="mc-acciones">
                        <button type="button" class="edit" aria-label="Editar" onclick="abrirModalEditarMovimiento(<?= argsEditarMov($mov) ?>)"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"></button>
                        <form method="POST" action="movimientos.php" class="form-inline" onsubmit="confirmarEliminacion(event, '¿Eliminar este movimiento?');">
                            <input type="hidden" name="form_type" value="eliminar_movimiento">
                            <input type="hidden" name="id_movimiento" value="<?= (int)$mov['id_movimiento'] ?>">
                            <input type="hidden" name="return_url" value="<?= $return_url_mov ?>">
                            <button type="submit" class="delete" aria-label="Eliminar"><img src="iconos/generales/tachodebasura.png" alt="" class="icono-boton"></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$movimientos): ?>
            <div class="texto-vacio">No hay movimientos <?= ($filtro_tipo || $filtro_categoria) ? 'con esos filtros' : 'en este período' ?>.</div>
        <?php endif; ?>
    </div>
</section>

</div>

<?php require_once 'includes/footer.php'; ?>
