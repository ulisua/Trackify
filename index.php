<?php
$page = 'dashboard';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Añadir columna descripcion a metas_ahorro si no existe (MariaDB 10.4 soporta IF NOT EXISTS)
$conn->query("ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) DEFAULT NULL");

// Procesar el guardado del movimiento u objetivo
if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['usuario_id'];

    if(isset($_POST['tipoMovimiento']) && $_POST['tipoMovimiento'] !== '') {
        $tipo = $_POST['tipoMovimiento']; // 'ingreso' o 'gasto'
        $monto = floatval($_POST['monto']);
        $categoria_nombre = $_POST['categoria'];
        $descripcion = $_POST['descripcion'];
        $fecha = $_POST['fecha'] ?? date('Y-m-d');

        // Buscar si existe la categoría para este tipo, si no, crearla
        $stmt_cat = $conn->prepare("SELECT id_categoria FROM categorias WHERE nombre = ? AND tipo = ? LIMIT 1");
        $stmt_cat->bind_param("ss", $categoria_nombre, $tipo);
        $stmt_cat->execute();
        $res_cat = $stmt_cat->get_result();
        
        if ($res_cat->num_rows > 0) {
            $row_cat = $res_cat->fetch_assoc();
            $id_categoria = $row_cat['id_categoria'];
        } else {
            $stmt_ins_cat = $conn->prepare("INSERT INTO categorias (nombre, tipo) VALUES (?, ?)");
            $stmt_ins_cat->bind_param("ss", $categoria_nombre, $tipo);
            $stmt_ins_cat->execute();
            $id_categoria = $stmt_ins_cat->insert_id;
        }

        // Insertar el Movimiento en la base de datos
        $stmt_mov = $conn->prepare("INSERT INTO movimientos (id_usuario, id_categoria, monto, tipo, descripcion, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_mov->bind_param("iidsss", $user_id, $id_categoria, $monto, $tipo, $descripcion, $fecha);
        $stmt_mov->execute();

        // Redirigir para limpiar el formulario y evitar re-envíos
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif(isset($_POST['form_type']) && $_POST['form_type'] === 'objetivo') {
        $nombre = $_POST['nombre_meta'];
        $desc = $_POST['desc_meta'];
        $monto = floatval($_POST['monto_objetivo']);
        $fecha = $_POST['fecha_limite'];
        $monto_actual = 0;

        $stmt_obj = $conn->prepare("INSERT INTO metas_ahorro (id_usuario, nombre_meta, descripcion, monto_objetivo, monto_actual, fecha_limite) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_obj->bind_param("issdds", $user_id, $nombre, $desc, $monto, $monto_actual, $fecha);
        $stmt_obj->execute();
        
        // Redirigir
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Calcular totales
$total_ingresos = 0;
$total_gastos = 0;
if(isset($_SESSION['usuario_id'])) {
    $user_id = $_SESSION['usuario_id'];
    
    $stmt_ingresos = $conn->prepare("SELECT SUM(monto) as total FROM movimientos WHERE id_usuario = ? AND tipo = 'ingreso'");
    $stmt_ingresos->bind_param("i", $user_id);
    $stmt_ingresos->execute();
    $res_ing = $stmt_ingresos->get_result();
    if ($row = $res_ing->fetch_assoc()) {
        $total_ingresos = $row['total'] ?? 0;
    }

    $stmt_gastos = $conn->prepare("SELECT SUM(monto) as total FROM movimientos WHERE id_usuario = ? AND tipo = 'gasto'");
    $stmt_gastos->bind_param("i", $user_id);
    $stmt_gastos->execute();
    $res_gas = $stmt_gastos->get_result();
    if ($row = $res_gas->fetch_assoc()) {
        $total_gastos = $row['total'] ?? 0;
    }
}
$balance = $total_ingresos - $total_gastos;

require_once 'includes/header.php';
?>

<!-- BOTON FLOTANTE IA -->
<div id="botonIA" class="boton-ia" onclick="toggleChat()">💬</div>

<!-- CHAT FLOTANTE -->
<div id="chatFlotante" class="chat-flotante oculto">
    <div class="chat-header">
        <span>Asistente IA</span>
        <span onclick="toggleChat()" style="cursor:pointer;">✖</span>
    </div>
    <div id="chat" class="chat"></div>
    <div class="input-container">
        <textarea id="pregunta" class="input-chat" placeholder="Escribí tu consulta..."></textarea>
        <button class="btn-enviar" onclick="preguntarIA()">Enviar</button>
    </div>
</div>

        <!-- CARDS PRINCIPALES -->
        <section class="cards">
            <div class="card">
                <h4>Ingresos</h4>
                <p id="ingresos">$<?php echo number_format($total_ingresos, 2, ',', '.'); ?></p>
            </div>
            <div class="card highlight">
                <h4>Balance</h4>
                <p id="balance">$<?php echo number_format($balance, 2, ',', '.'); ?></p>
            </div>
            <div class="card">
                <h4>Gastos</h4>
                <p id="gastos">$<?php echo number_format($total_gastos, 2, ',', '.'); ?></p>
            </div>
        </section>

        <!-- CARDS SECUNDARIAS -->
        <section class="cards small">
            <div class="card">% gasto <p id="porcentaje">0%</p></div>
            <div class="card">Mayor categoría <p id="categoriaTop">-</p></div>
            <div class="card">Gasto mensual <p id="gastoMensual">$0</p></div>
        </section>

        <!-- GRAFICOS -->
        <section class="graficos">
            <div class="grafico-box">
                <canvas id="graficoTorta"></canvas>
            </div>
            <div class="grafico-box">
                <canvas id="graficoBarras"></canvas>
            </div>
        </section>

        <!-- BOTONES -->
        <div class="acciones">
            <button class="btn ingreso" onclick="abrirModal('ingreso')">+ Ingreso</button>
            <button class="btn gasto" onclick="abrirModal('gasto')">+ Gasto</button>
            <button class="btn objetivo" onclick="abrirModal('objetivo')">+ Objetivo</button>
        </div>

        <!-- GRID -->
        <section class="grid">
            <div class="box">
                <h3>Movimientos</h3>
                <ul id="movimientos"></ul>
            </div>
            <div class="box">
                <h3>Recomendaciones IA</h3>
                <p id="recomendacion"></p>
            </div>
        </section>

    </main>
</div>

<?php 
$extra_js = '<script src="js/ia.js?v=2"></script>';
require_once 'includes/footer.php'; 
?>