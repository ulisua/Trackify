<?php
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Asegurar la conexión a la base de datos cuando este handler se llama directamente
if (!isset($conn)) {
    require_once dirname(__DIR__) . '/conexion.php';
}

if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    $user_id = $_SESSION['usuario_id'];
    $return_url = '';
    if (!empty($_POST['return_url'])) {
        $return_url = trim($_POST['return_url']);
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        $return_url = trim($_SERVER['HTTP_REFERER']);
    } else {
        $return_url = 'index.php';
    }

    // 1. Guardar nuevo movimiento
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

        // Redirigir a la página de origen
        header("Location: " . $return_url);
        exit();
    }
    
    // 2. Editar movimiento
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'editar_movimiento') {
        $id_movimiento = intval($_POST['id_movimiento']);
        $tipo = $_POST['tipo'];
        $monto = floatval($_POST['monto']);
        $categoria_nombre = $_POST['categoria'];
        $descripcion = $_POST['descripcion'];
        $fecha = $_POST['fecha'];

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

        // Actualizar el Movimiento en la base de datos
        $stmt_mov = $conn->prepare("UPDATE movimientos SET id_categoria = ?, monto = ?, descripcion = ?, fecha = ? WHERE id_movimiento = ? AND id_usuario = ?");
        $stmt_mov->bind_param("idssii", $id_categoria, $monto, $descripcion, $fecha, $id_movimiento, $user_id);
        $stmt_mov->execute();

        // Redirigir a la página de origen
        header("Location: " . $return_url);
        exit();
    }

    // 3. Eliminar movimiento
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'eliminar_movimiento') {
        $id_movimiento = intval($_POST['id_movimiento']);
        
        $stmt_del = $conn->prepare("DELETE FROM movimientos WHERE id_movimiento = ? AND id_usuario = ?");
        $stmt_del->bind_param("ii", $id_movimiento, $user_id);
        $stmt_del->execute();

        // Redirigir a la página de origen
        header("Location: " . $return_url);
        exit();
    }

    // 4. Borrar TODO el historial del usuario
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'borrar_historial_completo') {
        // Validación adicional: confirmar que el usuario realmente quiere eliminar
        $confirmacion = isset($_POST['confirmacion_borrar']) ? trim($_POST['confirmacion_borrar']) : '';
        
        if ($confirmacion === 'SI, ESTOY SEGURO') {
            // Eliminar todos los movimientos del usuario (validación: solo del usuario logueado)
            $stmt_del = $conn->prepare("DELETE FROM movimientos WHERE id_usuario = ?");
            $stmt_del->bind_param("i", $user_id);
            $result = $stmt_del->execute();
            
            if ($result) {
                // Redirigir a la página de origen o al dashboard si no hay origen
                header("Location: " . $return_url);
                exit();
            } else {
                // Si hay error, volver a la página anterior
                header("Location: " . $return_url);
                exit();
            }
        } else {
            // Confirmación no válida, volver sin hacer nada
            header("Location: " . $return_url);
            exit();
        }
    }
}
?>
