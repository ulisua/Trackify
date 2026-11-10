<?php
// Altas, ediciones y bajas de movimientos (también borrar historial y eliminar cuenta).
// Se incluye desde las páginas que muestran movimientos o se llama directo desde los modales.
require_once dirname(__DIR__) . '/conexion.php';

if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $conn->ejecutar("INSERT INTO movimientos (id_usuario, id_categoria, monto, tipo, descripcion, fecha) VALUES (?, ?, ?, ?, ?, ?)",
                [$user_id, $datos['id_categoria'], $datos['monto'], $tipo, $datos['descripcion'], $datos['fecha']]);
            flash(($tipo === 'ingreso' ? 'Ingreso' : 'Gasto') . ' guardado.', 'success');
        }

        // Redirigir a la página de origen
        header("Location: " . $return_url);
        exit();
    }

    // 2. Editar movimiento (el tipo se toma de la base, no del formulario)
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'editar_movimiento') {
        $id_movimiento = intval($_POST['id_movimiento'] ?? 0);

        $actual = $conn->fila("SELECT tipo FROM movimientos WHERE id_movimiento = ? AND id_usuario = ?", [$id_movimiento, $user_id]);

        if (!$actual) {
            flash('El movimiento no existe.');
        } elseif ($datos = $validarMovimiento($actual['tipo'])) {
            $conn->ejecutar("UPDATE movimientos SET id_categoria = ?, monto = ?, descripcion = ?, fecha = ? WHERE id_movimiento = ? AND id_usuario = ?",
                [$datos['id_categoria'], $datos['monto'], $datos['descripcion'], $datos['fecha'], $id_movimiento, $user_id]);
            flash('Movimiento actualizado.', 'success');
        }

        header("Location: " . $return_url);
        exit();
    }

    // 3. Eliminar movimiento
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'eliminar_movimiento') {
        $id_movimiento = intval($_POST['id_movimiento'] ?? 0);

        if ($conn->ejecutar("DELETE FROM movimientos WHERE id_movimiento = ? AND id_usuario = ?", [$id_movimiento, $user_id]) > 0) {
            flash('Movimiento eliminado.', 'success');
        }

        header("Location: " . $return_url);
        exit();
    }

    // 4. Borrar TODO el historial del usuario
    if(isset($_POST['form_type']) && $_POST['form_type'] === 'borrar_historial_completo') {
        $confirmacion = isset($_POST['confirmacion_borrar']) ? trim($_POST['confirmacion_borrar']) : '';

        if ($confirmacion === 'SI, ESTOY SEGURO') {
            $conn->ejecutar("DELETE FROM movimientos WHERE id_usuario = ?", [$user_id]);
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

        try {
            $conn->transaccion(function () use ($conn, $user_id) {
                foreach ([
                    "DELETE FROM movimientos WHERE id_usuario = ?",
                    "DELETE FROM metas_ahorro WHERE id_usuario = ?",
                    "DELETE FROM consejos_ia WHERE id_usuario = ?",
                    "DELETE FROM categorias WHERE id_usuario = ?",
                    "DELETE FROM usuarios WHERE id_usuario = ?",
                ] as $sql) {
                    $conn->ejecutar($sql, [$user_id]);
                }
            });
        } catch (PDOException $e) {
            error_log('Trackify: error al eliminar cuenta: ' . $e->getMessage());
            flash('No se pudo eliminar la cuenta. Intentá de nuevo.');
            header("Location: " . urlRetornoSegura($_SERVER['HTTP_REFERER'] ?? '', 'perfil.php'));
            exit();
        }

        cerrarSesion();
        redirigir('login.php');
    }
}
