<?php
$page = 'objetivos';
require_once 'conexion.php';

// Añadir columna descripcion a metas_ahorro si no existe
$conn->query("ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) DEFAULT NULL");

if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Procesar el guardado de objetivo
if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'objetivo') {
        $user_id = $_SESSION['usuario_id'];
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
        
        if($pct >= 100) {
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
            $fecha_str = 'Vence: ' . date('d/m/Y', strtotime($row['fecha_limite']));
            // Alternar colores o usar lila por defecto
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
            <div class="obj-acciones">
                <button class="btn-agregar">+ Agregar ahorro</button>
                <button class="btn-editar-obj">✏️ Editar</button>
                <button class="btn-eliminar-obj">🗑️ Eliminar</button>
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