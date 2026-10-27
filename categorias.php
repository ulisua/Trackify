<?php
$page = 'categorias';
$extra_css = '<link rel="stylesheet" href="css/pages/categorias.css">';
require_once 'conexion.php';
require_once 'includes/categorias_meta.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Procesar acciones de categorías.
// Las categorías predeterminadas (id_usuario NULL) son de solo lectura: cada usuario
// solo puede crear, editar y eliminar sus propias categorías.
if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    $user_id = (int)$_SESSION['usuario_id'];
    $form_type = $_POST['form_type'];

    if($form_type === 'nueva_categoria' || $form_type === 'editar_categoria') {
        $nombre_cat = trim($_POST['nombre_categoria'] ?? '');
        $tipo_cat = $_POST['tipo_categoria'] ?? '';
        $icono_cat = trim($_POST['icono'] ?? '');
        $color_cat = trim($_POST['color'] ?? '');
        $id_categoria = intval($_POST['id_categoria'] ?? 0);

        if ($nombre_cat === '' || mb_strlen($nombre_cat) > 50 || !in_array($tipo_cat, ['ingreso', 'gasto'], true)) {
            flash('Revisá el nombre (máximo 50 caracteres) y el tipo de la categoría.');
            header("Location: categorias.php");
            exit();
        }

        $meta = obtenerMetaCategoriaPorNombre($nombre_cat, $tipo_cat);
        if (!iconoCategoriaValido($icono_cat)) {
            $icono_cat = $meta['icono'];
        }
        if (!colorCategoriaValido($color_cat)) {
            $color_cat = $meta['color'];
        }

        // No se permiten nombres repetidos dentro de las categorías visibles del mismo tipo
        $id_existente = buscarCategoriaVisible($conn, $user_id, $nombre_cat, $tipo_cat);
        if ($id_existente !== null && $id_existente !== $id_categoria) {
            flash('Ya existe una categoría de ' . $tipo_cat . ' con ese nombre.');
            header("Location: categorias.php");
            exit();
        }

        if ($form_type === 'nueva_categoria') {
            $stmt_ins_cat = $conn->prepare("INSERT INTO categorias (nombre, tipo, icono, color, id_usuario) VALUES (?, ?, ?, ?, ?)");
            $stmt_ins_cat->bind_param("ssssi", $nombre_cat, $tipo_cat, $icono_cat, $color_cat, $user_id);
            $stmt_ins_cat->execute();
            flash('Categoría creada.', 'success');
        } else {
            $stmt_actual = $conn->prepare("SELECT tipo FROM categorias WHERE id_categoria = ? AND id_usuario = ?");
            $stmt_actual->bind_param("ii", $id_categoria, $user_id);
            $stmt_actual->execute();
            $actual = $stmt_actual->get_result()->fetch_assoc();

            if (!$actual) {
                flash('Solo podés editar tus propias categorías. Las predeterminadas no se modifican.');
                header("Location: categorias.php");
                exit();
            }

            // Cambiar el tipo dejaría movimientos con un tipo distinto al de su categoría
            if ($actual['tipo'] !== $tipo_cat) {
                $stmt_uso = $conn->prepare("SELECT COUNT(*) AS c FROM movimientos WHERE id_categoria = ? AND id_usuario = ?");
                $stmt_uso->bind_param("ii", $id_categoria, $user_id);
                $stmt_uso->execute();
                if ((int)$stmt_uso->get_result()->fetch_assoc()['c'] > 0) {
                    flash('No se puede cambiar el tipo de una categoría que ya tiene movimientos.');
                    header("Location: categorias.php");
                    exit();
                }
            }

            $stmt_upd = $conn->prepare("UPDATE categorias SET nombre = ?, tipo = ?, icono = ?, color = ? WHERE id_categoria = ? AND id_usuario = ?");
            $stmt_upd->bind_param("ssssii", $nombre_cat, $tipo_cat, $icono_cat, $color_cat, $id_categoria, $user_id);
            $stmt_upd->execute();
            flash('Categoría actualizada.', 'success');
        }

        header("Location: categorias.php");
        exit();
    } elseif($form_type === 'eliminar_categoria') {
        $id_categoria = intval($_POST['id_categoria'] ?? 0);

        $stmt_cat = $conn->prepare("SELECT tipo FROM categorias WHERE id_categoria = ? AND id_usuario = ?");
        $stmt_cat->bind_param("ii", $id_categoria, $user_id);
        $stmt_cat->execute();
        $categoria = $stmt_cat->get_result()->fetch_assoc();

        if (!$categoria) {
            flash('Solo podés eliminar tus propias categorías.');
            header("Location: categorias.php");
            exit();
        }

        // Los movimientos no se pierden: pasan a "Otros gastos" / "Otros ingresos"
        $nombre_destino = $categoria['tipo'] === 'ingreso' ? 'Otros ingresos' : 'Otros gastos';
        $id_destino = obtenerCategoriaSistema($conn, $nombre_destino, $categoria['tipo']);

        $conn->begin_transaction();
        try {
            $stmt_mov = $conn->prepare("UPDATE movimientos SET id_categoria = ? WHERE id_categoria = ? AND id_usuario = ?");
            $stmt_mov->bind_param("iii", $id_destino, $id_categoria, $user_id);
            $stmt_mov->execute();

            $stmt_del_cat = $conn->prepare("DELETE FROM categorias WHERE id_categoria = ? AND id_usuario = ?");
            $stmt_del_cat->bind_param("ii", $id_categoria, $user_id);
            $stmt_del_cat->execute();
            $conn->commit();
            flash('Categoría eliminada. Sus movimientos pasaron a "' . $nombre_destino . '".', 'success');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('Trackify: error al eliminar categoría: ' . $e->getMessage());
            flash('No se pudo eliminar la categoría.');
        }

        header("Location: categorias.php");
        exit();
    }
}

require_once 'includes/header.php';

$user_id = (int)$_SESSION['usuario_id'];

// Paleta de colores predefinida de clases CSS
$color_classes = ['card-mint', 'card-lime', 'card-pink', 'card-lavender', 'card-grey', 'card-violet', 'card-teal'];

// Categorías visibles (predeterminadas + propias) con los movimientos del período activo
$stmt = $conn->prepare("
    SELECT c.id_categoria, c.nombre, c.tipo, c.icono, c.color, c.id_usuario,
           COUNT(m.id_movimiento) as cantidad,
           SUM(m.monto) as total_monto
    FROM categorias c
    LEFT JOIN movimientos m ON c.id_categoria = m.id_categoria AND m.id_usuario = ? AND m.fecha BETWEEN ? AND ?
    WHERE c.id_usuario IS NULL OR c.id_usuario = ?
    GROUP BY c.id_categoria, c.nombre, c.tipo, c.icono, c.color, c.id_usuario
    ORDER BY IFNULL(SUM(m.monto), 0) DESC, c.nombre ASC
");
$stmt->bind_param("issi", $user_id, $fecha_desde, $fecha_hasta, $user_id);
$stmt->execute();
$res = $stmt->get_result();

$categorias_por_tipo = ['gasto' => [], 'ingreso' => []];
$totales_por_tipo = ['gasto' => 0, 'ingreso' => 0];

while ($row = $res->fetch_assoc()) {
    actualizarCategoriaMetaSiFalta($conn, $row);
    $tipo = $row['tipo'] === 'ingreso' ? 'ingreso' : 'gasto';
    $row['color_class'] = $color_classes[count($categorias_por_tipo[$tipo]) % count($color_classes)];
    $row['es_propia'] = $row['id_usuario'] !== null;
    $categorias_por_tipo[$tipo][] = $row;
    $totales_por_tipo[$tipo] += $row['total_monto'] ?? 0;
}

$etiquetas_periodo = ['semana' => 'de la semana', 'quincena' => 'de la quincena', 'mes' => 'del mes'];
$etiqueta_periodo = $etiquetas_periodo[$periodo_actual] ?? 'del período';

function renderResumenCategorias($categorias, $total, $titulo, $vacio) {
    ?>
    <h3><?php echo htmlspecialchars($titulo); ?></h3>
    <?php if ($total > 0): ?>
        <?php foreach ($categorias as $cat):
            if (!$cat['total_monto']) continue;
            $pct = round(($cat['total_monto'] / $total) * 100);
        ?>
        <div class="resumen-row <?php echo htmlspecialchars($cat['color_class']); ?>">
            <span class="resumen-cat-nombre"><img src="<?php echo htmlspecialchars($cat['icono']); ?>" alt="" class="icono-inline"> <?php echo htmlspecialchars($cat['nombre']); ?></span>
            <div class="resumen-barra">
                <div class="resumen-barra-fill" style="width:<?php echo $pct; ?>%; background: <?php echo htmlspecialchars($cat['color']); ?>;"></div>
            </div>
            <span class="resumen-pct"><?php echo $pct; ?>%</span>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="texto-vacio"><?php echo htmlspecialchars($vacio); ?></p>
    <?php endif;
}

function renderGridCategorias($categorias, $total, $id, $oculto) {
    ?>
    <div class="cat-grid" id="<?php echo $id; ?>"<?php echo $oculto ? ' hidden' : ''; ?>>
        <?php foreach ($categorias as $cat):
            $monto = $cat['total_monto'] ?? 0;
            $cant = $cat['cantidad'] ?? 0;
            $pct = $total > 0 ? round(($monto / $total) * 100) : 0;
        ?>
        <div class="cat-card <?php echo htmlspecialchars($cat['color_class']); ?> <?php echo $monto > 0 ? 'con-monto' : ''; ?>" style="--card-accent: <?php echo htmlspecialchars($cat['color']); ?>;">
            <div class="cat-icon">
                <img src="<?php echo htmlspecialchars($cat['icono']); ?>" alt="<?php echo htmlspecialchars($cat['nombre']); ?>">
            </div>
            <div class="cat-nombre"><?php echo htmlspecialchars($cat['nombre']); ?></div>
            <div class="cat-stats">
                <span class="cat-monto" data-ars="<?php echo (float)$monto; ?>">$<?php echo number_format($monto, 2, ',', '.'); ?></span>
                <span class="cat-cant"><?php echo $cant; ?> movimiento<?php echo $cant != 1 ? 's' : ''; ?></span>
            </div>
            <div class="cat-barra">
                <div class="cat-barra-fill" style="width:<?php echo $pct; ?>%;"></div>
            </div>
            <div class="cat-acciones">
                <?php if ($cat['es_propia']): ?>
                    <button type="button" onclick="abrirModalEditarCategoria(<?php echo (int)$cat['id_categoria']; ?>, <?php echo jsAttr($cat['nombre']); ?>, <?php echo jsAttr($cat['tipo']); ?>, <?php echo jsAttr($cat['icono']); ?>, <?php echo jsAttr($cat['color']); ?>)"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"> Editar</button>
                    <form method="POST" action="categorias.php" class="form-inline" onsubmit="confirmarEliminacion(event, 'Se eliminará la categoría. Sus movimientos no se borran: pasan a Otros gastos / Otros ingresos.');">
                        <input type="hidden" name="form_type" value="eliminar_categoria">
                        <input type="hidden" name="id_categoria" value="<?php echo (int)$cat['id_categoria']; ?>">
                        <button type="submit" class="btn-del" aria-label="Eliminar categoría"><img src="iconos/generales/tachodebasura.png" alt="" class="icono-boton"></button>
                    </form>
                <?php else: ?>
                    <span class="cat-badge-sistema" title="Las categorías predeterminadas son compartidas y no se pueden modificar">Predeterminada</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}
?>

            <div class="page-header">
                <div class="page-header-texto">
                    <h2 class="titulo-con-icono"><img src="iconos/generales/carpetaabierta.png" alt="" class="icono-titulo"> Categorías</h2>
                    <p class="page-subtitle">Predeterminadas y propias · montos <?php echo htmlspecialchars($etiqueta_periodo); ?></p>
                </div>
                <button class="btn-nuevo" onclick="abrirModalCategoria()">+ Nueva categoría</button>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('gastos', this)">Gastos</button>
                <button class="tab" onclick="switchTab('ingresos', this)">Ingresos</button>
            </div>

            <div class="resumen-cats" id="resumenGastos">
                <?php renderResumenCategorias($categorias_por_tipo['gasto'], $totales_por_tipo['gasto'], 'Distribución ' . $etiqueta_periodo . ' (Gastos)', 'No hay gastos registrados en este período.'); ?>
            </div>
            <div class="resumen-cats" id="resumenIngresos" hidden>
                <?php renderResumenCategorias($categorias_por_tipo['ingreso'], $totales_por_tipo['ingreso'], 'Distribución ' . $etiqueta_periodo . ' (Ingresos)', 'No hay ingresos registrados en este período.'); ?>
            </div>

            <?php renderGridCategorias($categorias_por_tipo['gasto'], $totales_por_tipo['gasto'], 'gridGastos', false); ?>
            <?php renderGridCategorias($categorias_por_tipo['ingreso'], $totales_por_tipo['ingreso'], 'gridIngresos', true); ?>

<?php
$extra_js = '<script src="js/categorias.js"></script>';
require_once 'includes/footer.php';
