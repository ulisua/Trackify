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
    $user_id = (int)$_SESSION['usuario_id'];
    $return_url = urlRetornoSegura($_POST['return_url'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));

    // Valida los datos comunes de alta/edición. Devuelve un array o null (y deja un mensaje flash).
    // El monto llega SIEMPRE en ARS: la conversión desde USD se hace en el formulario (js/moneda.js).
    $validarMovimiento = function ($tipo) use ($conn, $user_id) {
        if (!in_array($tipo, ['ingreso', 'gasto'], true)) {
            flash('Tipo de movimiento inválido.');
            return null;
        }
        $monto = montoValido($_POST['monto'] ?? '');
        if ($monto === null) {
            flash('Ingresá un monto mayor a cero.');
            return null;
        }
        $fecha = fechaValida($_POST['fecha'] ?? '') ?? date('Y-m-d');
        if ($fecha > date('Y-m-d')) {
            $fecha = date('Y-m-d');
        }
        $descripcion = trim($_POST['descripcion'] ?? '');
        if ($descripcion === '' || mb_strlen($descripcion) > 255) {
            flash('La descripción es obligatoria (máximo 255 caracteres).');
            return null;
        }
        $id_categoria = buscarCategoriaVisible($conn, $user_id, trim($_POST['categoria'] ?? ''), $tipo);
        if ($id_categoria === null) {
            flash('Elegí una categoría válida.');
            return null;
        }
        return ['monto' => $monto, 'fecha' => $fecha, 'descripcion' => $descripcion, 'id_categoria' => $id_categoria];
    };

    // 1. Guardar nuevo movimiento
    if(isset($_POST['tipoMovimiento']) && $_POST['tipoMovimiento'] !== '') {
        $tipo = $_POST['tipoMovimiento']; // 'ingreso' o 'gasto'
        $datos = $validarMovimiento($tipo);

        if ($datos) {
            $stmt_mov = $conn->prepare("INSERT INTO movimientos (id_usuario, id_categoria, monto, tipo, descripcion, fecha) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_mov->bind_param("iidsss", $user_id, $datos['id_categoria'], $datos['monto'], $tipo, $datos['descripcion'], $datos['fecha']);
            $stmt_mov->execute();
            flash(($tipo === 'ingreso' ? 'Ingreso' : 'Gasto') . ' guardado.', 'success');
        }

        // Redirigir a la página de origen
        header("Location: " . $return_url);
        exit();
    }

    // 2. Editar movimiento (el tipo se toma de la base, no del formulario)
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'editar_movimiento') {
        $id_movimiento = intval($_POST['id_movimiento'] ?? 0);

        $stmt_actual = $conn->prepare("SELECT tipo FROM movimientos WHERE id_movimiento = ? AND id_usuario = ?");
        $stmt_actual->bind_param("ii", $id_movimiento, $user_id);
        $stmt_actual->execute();
        $actual = $stmt_actual->get_result()->fetch_assoc();

        if (!$actual) {
            flash('El movimiento no existe.');
        } elseif ($datos = $validarMovimiento($actual['tipo'])) {
            $stmt_mov = $conn->prepare("UPDATE movimientos SET id_categoria = ?, monto = ?, descripcion = ?, fecha = ? WHERE id_movimiento = ? AND id_usuario = ?");
            $stmt_mov->bind_param("idssii", $datos['id_categoria'], $datos['monto'], $datos['descripcion'], $datos['fecha'], $id_movimiento, $user_id);
            $stmt_mov->execute();
            flash('Movimiento actualizado.', 'success');
        }

        header("Location: " . $return_url);
        exit();
    }

    // 3. Eliminar movimiento
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'eliminar_movimiento') {
        $id_movimiento = intval($_POST['id_movimiento'] ?? 0);

        $stmt_del = $conn->prepare("DELETE FROM movimientos WHERE id_movimiento = ? AND id_usuario = ?");
        $stmt_del->bind_param("ii", $id_movimiento, $user_id);
        $stmt_del->execute();
        if ($stmt_del->affected_rows > 0) {
            flash('Movimiento eliminado.', 'success');
        }

        header("Location: " . $return_url);
        exit();
    }

    // 4. Borrar TODO el historial del usuario
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'borrar_historial_completo') {
        $confirmacion = isset($_POST['confirmacion_borrar']) ? trim($_POST['confirmacion_borrar']) : '';

        if ($confirmacion === 'SI, ESTOY SEGURO') {
            $stmt_del = $conn->prepare("DELETE FROM movimientos WHERE id_usuario = ?");
            $stmt_del->bind_param("i", $user_id);
            $stmt_del->execute();
            flash('Se borró todo tu historial de movimientos.', 'success');
        } else {
            flash('Confirmación incorrecta: no se borró nada.');
        }
        header("Location: " . $return_url);
        exit();
    }

    // 5. Eliminar cuenta (requiere la frase de confirmación también en el servidor)
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'eliminar_cuenta') {
        $confirmacion = trim($_POST['confirmacion_eliminar'] ?? '');
        if ($confirmacion !== 'ELIMINAR MI CUENTA') {
            flash('Confirmación incorrecta: tu cuenta no se eliminó.');
            header("Location: " . urlRetornoSegura($_SERVER['HTTP_REFERER'] ?? '', 'perfil.php'));
            exit();
        }

        $conn->begin_transaction();
        try {
            foreach ([
                "DELETE FROM movimientos WHERE id_usuario = ?",
                "DELETE FROM metas_ahorro WHERE id_usuario = ?",
                "DELETE FROM consejos_ia WHERE id_usuario = ?",
                "DELETE FROM categorias WHERE id_usuario = ?",
                "DELETE FROM usuarios WHERE id_usuario = ?",
            ] as $sql) {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }
            $conn->commit();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            error_log('Trackify: error al eliminar cuenta: ' . $e->getMessage());
            flash('No se pudo eliminar la cuenta. Intentá de nuevo.');
            header("Location: " . urlRetornoSegura($_SERVER['HTTP_REFERER'] ?? '', 'perfil.php'));
            exit();
        }

        // Destruir sesión limpia y eliminar cookie de sesión si existe
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();

        $basePath = dirname(dirname($_SERVER['PHP_SELF']));
        if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
            $basePath = '';
        }
        header("Location: {$basePath}/login.php");
        exit();
    }
}
?>
