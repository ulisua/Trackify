<?php
$page = 'objetivos';
require_once 'conexion.php';

if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Procesar acciones de objetivos
if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    $user_id = $_SESSION['usuario_id'];
    $form_type = $_POST['form_type'];

    if($form_type === 'objetivo') {
        $nombre = $_POST['nombre_meta'];
        $desc = $_POST['desc_meta'];
        $monto = floatval($_POST['monto_objetivo']);
        $fecha = $_POST['fecha_limite'];
        $monto_actual = 0;

        $stmt_obj = $conn->prepare("INSERT INTO metas_ahorro (id_usuario, nombre_meta, descripcion, monto_objetivo, monto_actual, fecha_limite) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_obj->bind_param("issdds", $user_id, $nombre, $desc, $monto, $monto_actual, $fecha);
        $stmt_obj->execute();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif($form_type === 'agregar_ahorro') {
        $id_meta = intval($_POST['id_meta']);
        $monto_sumar = floatval($_POST['monto_ahorro']);
        
        $conn->query("UPDATE metas_ahorro SET monto_actual = monto_actual + $monto_sumar WHERE id_meta = $id_meta AND id_usuario = $user_id");
        
        $res = $conn->query("SELECT nombre_meta FROM metas_ahorro WHERE id_meta = $id_meta AND id_usuario = $user_id");
        $nombre_meta = ($res && $row = $res->fetch_assoc()) ? $row['nombre_meta'] : 'Ahorro';
        
        $cat_nombre = "Ahorro para meta";
        $stmt_cat = $conn->prepare("SELECT id_categoria FROM categorias WHERE nombre = ? AND tipo = 'gasto' LIMIT 1");
        $stmt_cat->bind_param("s", $cat_nombre);
        $stmt_cat->execute();
        $res_cat = $stmt_cat->get_result();
        
        if ($res_cat->num_rows > 0) {
            $id_categoria = $res_cat->fetch_assoc()['id_categoria'];
        } else {
            $conn->query("INSERT INTO categorias (nombre, tipo) VALUES ('$cat_nombre', 'gasto')");
            $id_categoria = $conn->insert_id;
        }
        
        $desc = "Aporte a: " . $nombre_meta;
        $fecha = date('Y-m-d');
        $stmt_mov = $conn->prepare("INSERT INTO movimientos (id_usuario, id_categoria, monto, tipo, descripcion, fecha) VALUES (?, ?, ?, 'gasto', ?, ?)");
        $stmt_mov->bind_param("iidss", $user_id, $id_categoria, $monto_sumar, $desc, $fecha);
        $stmt_mov->execute();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        
    } elseif($form_type === 'editar_objetivo') {
        $id_meta = intval($_POST['id_meta']);
        $nombre = $_POST['nombre_meta'];
        $desc = $_POST['desc_meta'];
        $monto = floatval($_POST['monto_objetivo']);
        $fecha = $_POST['fecha_limite'];
        $estado = $_POST['estado'];
        
        $stmt_upd = $conn->prepare("UPDATE metas_ahorro SET nombre_meta=?, descripcion=?, monto_objetivo=?, fecha_limite=?, estado=? WHERE id_meta=? AND id_usuario=?");
        $stmt_upd->bind_param("ssdssii", $nombre, $desc, $monto, $fecha, $estado, $id_meta, $user_id);
        $stmt_upd->execute();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif($form_type === 'eliminar_objetivo') {
        $id_meta = intval($_POST['id_meta']);
        $conn->query("DELETE FROM metas_ahorro WHERE id_meta = $id_meta AND id_usuario = $user_id");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// FETCH DATA
$activos = 0;
$logrados = 0;
$promedio = 0;
$total_progreso = 0;
$total_metas = 0;
$html_metas = '';

if(isset($_SESSION['usuario_id'])) {
    $user_id = $_SESSION['usuario_id'];
    $stmt = $conn->prepare("SELECT * FROM metas_ahorro WHERE id_usuario = ? ORDER BY fecha_limite ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $total_metas++;
        $pct = ($row['monto_objetivo'] > 0) ? ($row['monto_actual'] / $row['monto_objetivo']) * 100 : 0;
        if($pct > 100) $pct = 100;
        
        $total_progreso += $pct;
        
        $estado = $row['estado'] ?? 'activo';
        $fecha_str = 'Vence: ' . date('d/m/Y', strtotime($row['fecha_limite']));
        
        if($estado === 'inactivo') {
            $badge = '<span class="obj-badge badge-pausado" style="background:#cbd5e1; color:#334155; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Inactivo</span>';
            $clase = 'pausado';
            $color_fill = 'fill-rosa';
            $color_pct = 'pct-rosa';
            $fecha_str = 'Pausado';
        } elseif($pct >= 100 || $estado === 'logrado') {
            $logrados++;
            $badge = '<span class="obj-badge badge-logrado">Logrado</span>';
            $clase = 'completado';
            $fecha_str = 'Completado';
            $color_fill = 'fill-verde';
            $color_pct = 'pct-verde';
        } else {
            $activos++;
            $badge = '<span class="obj-badge badge-activo">Activo</span>';
            $clase = '';
            $color_fill = 'fill-lila';
            $color_pct = 'pct-lila';
        }
        
        $desc = htmlspecialchars($row['descripcion'] ?? '');
        $nombre = htmlspecialchars($row['nombre_meta']);
        $monto_act = number_format($row['monto_actual'], 0, ',', '.');
        $monto_obj = number_format($row['monto_objetivo'], 0, ',', '.');
        $pct_format = number_format($pct, 0);

        $html_metas .= '
        <div class="obj-card ' . $clase . '">
            <div class="obj-card-top">
                <div class="obj-info">
                    <div class="obj-nombre">
                        ' . $nombre . '
                        ' . $badge . '
                    </div>
                    <div class="obj-desc">' . $desc . '</div>
                </div>
                <div class="obj-montos">
                    <div class="obj-actual">$' . $monto_act . '</div>
                    <div class="obj-meta">de $' . $monto_obj . '</div>
                </div>
            </div>
            <div class="obj-progress-wrap">
                <div class="obj-progress-info">
                    <span class="obj-pct ' . $color_pct . '">' . $pct_format . '%</span>
                    <span class="obj-fecha">' . $fecha_str . '</span>
                </div>
                <div class="obj-barra">
                    <div class="obj-barra-fill ' . $color_fill . '" style="width:' . $pct_format . '%"></div>
                </div>
            </div>
            <div class="obj-acciones" style="display:flex; gap:10px;">
                <button class="btn-agregar" onclick="abrirModalAhorro('.$row['id_meta'].')">+ Agregar ahorro</button>
                <button class="btn-editar-obj" onclick="abrirModalEditarObj('.$row['id_meta'].', \''.htmlspecialchars($row['nombre_meta'], ENT_QUOTES).'\', \''.htmlspecialchars($row['descripcion'], ENT_QUOTES).'\', '.$row['monto_objetivo'].', \''.$row['fecha_limite'].'\', \''.$estado.'\')">✏️ Editar</button>
                <form method="POST" action="objetivos.php" style="margin:0;" onsubmit="return confirm(\'¿Eliminar este objetivo?\');">
                    <input type="hidden" name="form_type" value="eliminar_objetivo">
                    <input type="hidden" name="id_meta" value="'.$row['id_meta'].'">
                    <button type="submit" class="btn-eliminar-obj" style="cursor:pointer; background:transparent; border:1px solid #e2e8f0; border-radius:6px; padding:8px 12px; color:#64748B;">🗑️ Eliminar</button>
                </form>
            </div>
        </div>';
    }
    
    if($total_metas > 0) {
        $promedio = number_format($total_progreso / $total_metas, 0);
    }
}

$extra_css = '<link rel="stylesheet" href="css/objetivos.css">';
require_once 'includes/header.php';
?>

            <div class="page-header">
                <h2>🎯 Objetivos de ahorro</h2>
                <button class="btn-nuevo" onclick="abrirModal('objetivo')">+ Nuevo objetivo</button>
            </div>

            <!-- Stats -->
            <div class="obj-stats">
                <div class="obj-stat">
                    <span class="obj-stat-num"><?php echo $activos; ?></span>
                    <span class="obj-stat-label">Activos</span>
                </div>
                <div class="obj-stat">
                    <span class="obj-stat-num"><?php echo $logrados; ?></span>
                    <span class="obj-stat-label">Logrados</span>
                </div>
                <div class="obj-stat">
                    <span class="obj-stat-num"><?php echo $promedio; ?>%</span>
                    <span class="obj-stat-label">Promedio</span>
                </div>
            </div>

            <!-- Objetivos -->
            <div class="obj-lista">
                <?php 
                if ($html_metas != '') {
                    echo $html_metas; 
                } else {
                    echo '<p style="text-align:center; width:100%; color:#64748B; padding: 40px 0;">No tienes objetivos activos. ¡Crea uno nuevo para empezar a ahorrar!</p>';
                }
                ?>
            </div>

        </main>
<?php  
$extra_js = '<script src="js/objetivos.js"></script>';
require_once 'includes/footer.php'; 
?>