<?php
$page = 'categorias';
$extra_css = '<link rel="stylesheet" href="css/pages/categorias.css">';
require_once 'conexion.php';
require_once 'includes/categorias_meta.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Procesar acciones de categorías
if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    $user_id = $_SESSION['usuario_id'];
    $form_type = $_POST['form_type'];
    
    if($form_type === 'nueva_categoria') {
        $nombre_cat = trim($_POST['nombre_categoria']);
        $tipo_cat = $_POST['tipo_categoria'];
        $icono_cat = trim($_POST['icono'] ?? '');
        $color_cat = trim($_POST['color'] ?? '');

        if (!$icono_cat || !$color_cat) {
            $meta = obtenerMetaCategoriaPorNombre($nombre_cat, $tipo_cat);
            $icono_cat = $icono_cat ?: $meta['icono'];
            $color_cat = $color_cat ?: $meta['color'];
        }
        
        $stmt_ins_cat = $conn->prepare("INSERT INTO categorias (nombre, tipo, icono, color) VALUES (?, ?, ?, ?)");
        $stmt_ins_cat->bind_param("ssss", $nombre_cat, $tipo_cat, $icono_cat, $color_cat);
        $stmt_ins_cat->execute();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif($form_type === 'editar_categoria') {
        $id_categoria = intval($_POST['id_categoria']);
        $nombre_cat = trim($_POST['nombre_categoria']);
        $tipo_cat = $_POST['tipo_categoria'];
        $icono_cat = trim($_POST['icono'] ?? '');
        $color_cat = trim($_POST['color'] ?? '');

        if (!$icono_cat || !$color_cat) {
            $meta = obtenerMetaCategoriaPorNombre($nombre_cat, $tipo_cat);
            $icono_cat = $icono_cat ?: $meta['icono'];
            $color_cat = $color_cat ?: $meta['color'];
        }
        
        $stmt_upd = $conn->prepare("UPDATE categorias SET nombre = ?, tipo = ?, icono = ?, color = ? WHERE id_categoria = ?");
        $stmt_upd->bind_param("ssssi", $nombre_cat, $tipo_cat, $icono_cat, $color_cat, $id_categoria);
        $stmt_upd->execute();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif($form_type === 'eliminar_categoria') {
        $id_categoria = intval($_POST['id_categoria']);
        
        // Primero eliminar los movimientos relacionados para evitar fallos de clave foránea
        $stmt_del_mov = $conn->prepare("DELETE FROM movimientos WHERE id_categoria = ?");
        $stmt_del_mov->bind_param("i", $id_categoria);
        $stmt_del_mov->execute();
        
        // Luego eliminar la categoría
        $stmt_del_cat = $conn->prepare("DELETE FROM categorias WHERE id_categoria = ?");
        $stmt_del_cat->bind_param("i", $id_categoria);
        $stmt_del_cat->execute();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

require_once 'includes/header.php';

$user_id = $_SESSION['usuario_id'];
$mes_actual = date('m');
$anio_actual = date('Y');

// Paleta de colores predefinida de clases CSS y acentos correspondientes
$color_classes = ['card-mint', 'card-lime', 'card-pink', 'card-lavender', 'card-grey', 'card-violet', 'card-teal'];
$color_hexes = ['#084734', '#6d801b', '#EA73F5', '#5a3b75', '#334155', '#700353', '#0b4c4e'];

// Obtener todas las categorías y sumar sus movimientos
$stmt = $conn->prepare("
    SELECT c.id_categoria, c.nombre, c.tipo, c.icono, c.color,
           COUNT(m.id_movimiento) as cantidad, 
           SUM(m.monto) as total_monto
    FROM categorias c
    LEFT JOIN movimientos m ON c.id_categoria = m.id_categoria AND m.id_usuario = ? AND MONTH(m.fecha) = ? AND YEAR(m.fecha) = ?
    GROUP BY c.id_categoria, c.nombre, c.tipo, c.icono, c.color
    ORDER BY IFNULL(SUM(m.monto), 0) DESC, c.nombre ASC
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
    actualizarCategoriaMetaSiFalta($conn, $row);
    if (empty($row['icono'])) {
        $meta = obtenerMetaCategoriaPorNombre($row['nombre'], $row['tipo']);
        $row['icono'] = $meta['icono'];
    }
    if (empty($row['color'])) {
        $meta = $meta ?? obtenerMetaCategoriaPorNombre($row['nombre'], $row['tipo']);
        $row['color'] = $meta['color'];
    }
    $row['icon_alt'] = $row['nombre'];

    if ($row['tipo'] === 'gasto') {
        $row['color_class'] = $color_classes[$color_index_g % count($color_classes)];
        $gastos[] = $row;
        $total_gastos += $row['total_monto'] ?? 0;
        $color_index_g++;
    } else {
        $row['color_class'] = $color_classes[$color_index_i % count($color_classes)];
        $ingresos[] = $row;
        $total_ingresos += $row['total_monto'] ?? 0;
        $color_index_i++;
    }
}
?>

            <div class="page-header">
                <h2 class="titulo-con-icono"><img src="iconos/generales/carpetaabierta.png" alt="Categorías" class="icono-titulo"> Categorías</h2>
                <button class="btn-nuevo" onclick="abrirModalCategoria()">+ Nueva categoría</button>
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
                    <div class="resumen-row <?php echo htmlspecialchars($gasto['color_class']); ?>">
                        <span class="resumen-cat-nombre"><img src="<?php echo htmlspecialchars($gasto['icono']); ?>" alt="<?php echo htmlspecialchars($gasto['icon_alt']); ?>" class="icono-inline"> <?php echo htmlspecialchars($gasto['nombre']); ?></span>
                        <div class="resumen-barra">
                            <div class="resumen-barra-fill" style="width:<?php echo $pct; ?>%; background: <?php echo htmlspecialchars($gasto['color']); ?>;"></div>
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
                <div class="cat-card <?php echo htmlspecialchars($gasto['color_class']); ?> <?php echo $monto > 0 ? 'con-monto' : ''; ?>" style="--card-accent: <?php echo htmlspecialchars($gasto['color']); ?>;">
                    <div class="cat-icon">
                        <img src="<?php echo htmlspecialchars($gasto['icono']); ?>" alt="<?php echo htmlspecialchars($gasto['icon_alt']); ?>">
                    </div>
                    <div class="cat-nombre"><?php echo htmlspecialchars($gasto['nombre']); ?></div>
                    <div class="cat-stats">
                        <span class="cat-monto">$<?php echo number_format($monto, 2, ',', '.'); ?></span>
                        <span class="cat-cant"><?php echo $cant; ?> movimiento<?php echo $cant != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:<?php echo $pct; ?>%;"></div>
                    </div>
                    <div class="cat-acciones">
                        <button onclick="abrirModalEditarCategoria(<?php echo $gasto['id_categoria']; ?>, '<?php echo htmlspecialchars(addslashes($gasto['nombre']), ENT_QUOTES); ?>', 'gasto', '<?php echo htmlspecialchars(addslashes($gasto['icono']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars($gasto['color']); ?>')"><img src="iconos/generales/lapiz.png" alt="Editar" class="icono-boton"> Editar</button>
                        <form method="POST" action="" style="display:inline;" onsubmit="confirmarEliminacion(event, '¿Estás seguro de que deseas eliminar esta categoría? Esto también borrará todos los movimientos asociados.');">
                            <input type="hidden" name="form_type" value="eliminar_categoria">
                            <input type="hidden" name="id_categoria" value="<?php echo $gasto['id_categoria']; ?>">
                            <button type="submit" class="btn-del"><img src="iconos/generales/tachodebasura.png" alt="Eliminar" class="icono-boton"></button>
                        </form>
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
                <div class="cat-card <?php echo htmlspecialchars($ingreso['color_class']); ?> <?php echo $monto > 0 ? 'con-monto' : ''; ?>" style="--card-accent: <?php echo htmlspecialchars($ingreso['color']); ?>;">
                    <div class="cat-icon">
                        <img src="<?php echo htmlspecialchars($ingreso['icono']); ?>" alt="<?php echo htmlspecialchars($ingreso['icon_alt']); ?>">
                    </div>
                    <div class="cat-nombre"><?php echo htmlspecialchars($ingreso['nombre']); ?></div>
                    <div class="cat-stats">
                        <span class="cat-monto">$<?php echo number_format($monto, 2, ',', '.'); ?></span>
                        <span class="cat-cant"><?php echo $cant; ?> movimiento<?php echo $cant != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:<?php echo $pct; ?>%;"></div>
                    </div>
                    <div class="cat-acciones">
                        <button onclick="abrirModalEditarCategoria(<?php echo $ingreso['id_categoria']; ?>, '<?php echo htmlspecialchars(addslashes($ingreso['nombre']), ENT_QUOTES); ?>', 'ingreso', '<?php echo htmlspecialchars(addslashes($ingreso['icono']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars($ingreso['color']); ?>')"><img src="iconos/generales/lapiz.png" alt="Editar" class="icono-boton"> Editar</button>
                        <form method="POST" action="" style="display:inline;" onsubmit="confirmarEliminacion(event, '¿Estás seguro de que deseas eliminar esta categoría? Esto también borrará todos los movimientos asociados.');">
                            <input type="hidden" name="form_type" value="eliminar_categoria">
                            <input type="hidden" name="id_categoria" value="<?php echo $ingreso['id_categoria']; ?>">
                            <button type="submit" class="btn-del"><img src="iconos/generales/tachodebasura.png" alt="Eliminar" class="icono-boton"></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

<?php 
$extra_js = '<script src="js/categorias.js"></script>';
require_once 'includes/footer.php'; 
?>