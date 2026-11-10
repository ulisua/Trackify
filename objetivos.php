<?php
$page = 'objetivos';
require_once 'conexion.php';

// Procesar acciones de objetivos. Los montos llegan en ARS (ver js/moneda.js).
if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    $user_id = (int)$_SESSION['usuario_id'];
    $form_type = $_POST['form_type'];
    $hoy = date('Y-m-d');
    $volver = urlRetornoSegura($_POST['return_url'] ?? '', 'objetivos.php');

    if($form_type === 'objetivo' || $form_type === 'editar_objetivo') {
        $nombre = trim($_POST['nombre_meta'] ?? '');
        $desc = trim($_POST['desc_meta'] ?? '');
        $monto = montoValido($_POST['monto_objetivo'] ?? '');
        $fecha = fechaValida($_POST['fecha_limite'] ?? '');

        if ($nombre === '' || mb_strlen($nombre) > 100 || mb_strlen($desc) > 255) {
            flash('Revisá el nombre (máximo 100 caracteres) y la descripción del objetivo.');
        } elseif ($monto === null) {
            flash('El monto objetivo debe ser mayor a cero.');
        } elseif ($fecha === null || $fecha < $hoy) {
            flash('La fecha límite debe ser hoy o una fecha futura.');
        } elseif ($form_type === 'objetivo') {
            $monto_actual = 0;
            $conn->ejecutar("INSERT INTO metas_ahorro (id_usuario, nombre_meta, descripcion, monto_objetivo, monto_actual, fecha_limite) VALUES (?, ?, ?, ?, ?, ?)",
                [$user_id, $nombre, $desc, $monto, $monto_actual, $fecha]);
            flash('Objetivo creado.', 'success');
        } else {
            $id_meta = intval($_POST['id_meta'] ?? 0);
            $estado = $_POST['estado'] ?? 'activo';
            if (!in_array($estado, ['activo', 'inactivo', 'logrado'], true)) {
                $estado = 'activo';
            }
            $conn->ejecutar("UPDATE metas_ahorro SET nombre_meta=?, descripcion=?, monto_objetivo=?, fecha_limite=?, estado=? WHERE id_meta=? AND id_usuario=?",
                [$nombre, $desc, $monto, $fecha, $estado, $id_meta, $user_id]);
            flash('Objetivo actualizado.', 'success');
        }

        header("Location: " . ($form_type === 'objetivo' ? $volver : 'objetivos.php'));
        exit();
    } elseif($form_type === 'agregar_ahorro') {
        $id_meta = intval($_POST['id_meta'] ?? 0);
        $monto_sumar = montoValido($_POST['monto_ahorro'] ?? '');

        $meta = $conn->fila("SELECT nombre_meta FROM metas_ahorro WHERE id_meta = ? AND id_usuario = ?", [$id_meta, $user_id]);

        if (!$meta) {
            flash('El objetivo no existe.');
        } elseif ($monto_sumar === null) {
            flash('El aporte debe ser mayor a cero.');
        } else {
            // El aporte suma a la meta y queda registrado como gasto, en una sola transacción
            $id_categoria = obtenerCategoriaSistema($conn, 'Ahorro para meta', 'gasto');
            $desc = "Aporte a: " . $meta['nombre_meta'];
            try {
                $conn->transaccion(function () use ($conn, $monto_sumar, $id_meta, $user_id, $id_categoria, $desc, $hoy) {
                    $conn->ejecutar("UPDATE metas_ahorro SET monto_actual = monto_actual + ? WHERE id_meta = ? AND id_usuario = ?", [$monto_sumar, $id_meta, $user_id]);
                    $conn->ejecutar("INSERT INTO movimientos (id_usuario, id_categoria, monto, tipo, descripcion, fecha) VALUES (?, ?, ?, 'gasto', ?, ?)",
                        [$user_id, $id_categoria, $monto_sumar, $desc, $hoy]);
                });
                flash('Aporte registrado.', 'success');
            } catch (PDOException $e) {
                error_log('Trackify: error al registrar aporte: ' . $e->getMessage());
                flash('No se pudo registrar el aporte.');
            }
        }

        header("Location: objetivos.php");
        exit();
    } elseif($form_type === 'eliminar_objetivo') {
        // Cada aporte se registró como gasto ("Ahorro para meta"). Al eliminar el objetivo,
        // lo ahorrado vuelve al saldo como un ingreso ("Reintegro de meta"), en la misma transacción.
        $id_meta = intval($_POST['id_meta'] ?? 0);

        $meta = $conn->fila("SELECT nombre_meta, monto_actual FROM metas_ahorro WHERE id_meta = ? AND id_usuario = ?", [$id_meta, $user_id]);

        if (!$meta) {
            flash('El objetivo no existe.');
        } else {
            $reintegro = (float)$meta['monto_actual'];
            try {
                $conn->transaccion(function () use ($conn, $reintegro, $meta, $user_id, $hoy, $id_meta) {
                    if ($reintegro > 0) {
                        $id_categoria = obtenerCategoriaSistema($conn, 'Reintegro de meta', 'ingreso');
                        $conn->ejecutar("INSERT INTO movimientos (id_usuario, id_categoria, monto, tipo, descripcion, fecha) VALUES (?, ?, ?, 'ingreso', ?, ?)",
                            [$user_id, $id_categoria, $reintegro, 'Reintegro de: ' . $meta['nombre_meta'], $hoy]);
                    }
                    $conn->ejecutar("DELETE FROM metas_ahorro WHERE id_meta = ? AND id_usuario = ?", [$id_meta, $user_id]);
                });

                flash($reintegro > 0
                    ? 'Objetivo eliminado. Lo ahorrado ($' . number_format($reintegro, 2, ',', '.') . ') volvió como ingreso.'
                    : 'Objetivo eliminado.', 'success');
            } catch (PDOException $e) {
                error_log('Trackify: error al eliminar objetivo: ' . $e->getMessage());
                flash('No se pudo eliminar el objetivo.');
            }
        }

        header("Location: objetivos.php");
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
    $user_id = (int)$_SESSION['usuario_id'];
    $metas_usuario = $conn->filas("SELECT * FROM metas_ahorro WHERE id_usuario = ? ORDER BY fecha_limite ASC", [$user_id]);

    $color_classes = ['fill-mint', 'fill-lime', 'fill-pink', 'fill-lavender', 'fill-teal', 'fill-violet'];
    $color_idx = 0;

    $hoy = date('Y-m-d');

    foreach ($metas_usuario as $row) {
        $total_metas++;
        $pct = ($row['monto_objetivo'] > 0) ? ($row['monto_actual'] / $row['monto_objetivo']) * 100 : 0;
        if($pct > 100) $pct = 100;

        $total_progreso += $pct;

        $estado = $row['estado'] ?? 'activo';
        $fecha_limite = $row['fecha_limite'];
        $fecha_str = 'Vence: ' . date('d/m/Y', strtotime($fecha_limite));

        $es_vencido = false;

        if($estado === 'inactivo') {
            $badge = '<span class="obj-badge badge-pausado">Inactivo</span>';
            $clase = 'pausado';
            $color_fill = 'fill-gris';
            $color_pct = 'pct-gris';
            $fecha_str = 'Pausado';
        } elseif($pct >= 100 || $estado === 'logrado') {
            $logrados++;
            $badge = '<span class="obj-badge badge-logrado">Logrado</span>';
            $clase = 'completado';
            $fecha_str = 'Completado';
            $color_fill = 'fill-verde';
            $color_pct = 'pct-verde';
        } elseif ($fecha_limite < $hoy) {
            // Vencido
            $es_vencido = true;
            $badge = '<span class="obj-badge badge-vencido">Vencido</span>';
            $clase = 'vencido';
            $fecha_str = 'Venció: ' . date('d/m/Y', strtotime($fecha_limite));
            $color_fill = 'fill-gris';
            $color_pct = 'pct-gris';
        } else {
            $activos++;
            $badge = '<span class="obj-badge badge-activo">Activo</span>';
            $clase = '';
            // Color dinámico
            $color_fill = $color_classes[$color_idx % count($color_classes)];
            $color_pct = $color_fill . '-text'; // Usaremos esto para el color del texto del %
            $color_idx++;
        }

        $desc = htmlspecialchars($row['descripcion'] ?? '');
        $nombre = htmlspecialchars($row['nombre_meta']);
        $monto_act = number_format($row['monto_actual'], 0, ',', '.');
        $monto_obj = number_format($row['monto_objetivo'], 0, ',', '.');
        $pct_format = number_format($pct, 0);
        $id_meta = (int)$row['id_meta'];

        $aviso_eliminar = $row['monto_actual'] > 0
            ? 'Lo que ahorraste ($' . number_format($row['monto_actual'], 2, ',', '.') . ') vuelve a tu saldo como un ingreso.'
            : 'El objetivo no tiene ahorros, así que no se genera ningún movimiento.';
        $form_eliminar = '
                <form method="POST" action="objetivos.php" class="form-inline" onsubmit="confirmarEliminacion(event, ' . jsAttr($aviso_eliminar) . ');">
                    <input type="hidden" name="form_type" value="eliminar_objetivo">
                    <input type="hidden" name="id_meta" value="' . $id_meta . '">
                    <button type="submit" class="btn-eliminar-obj' . ($es_vencido ? ' btn-eliminar-vencido' : '') . '"><img src="iconos/generales/tachodebasura.png" alt="" class="icono-boton"> Eliminar</button>
                </form>';

        if ($es_vencido) {
            // Solo permitir eliminar si está vencido
            $acciones_html = $form_eliminar;
        } else {
            $args_editar = implode(', ', [
                $id_meta,
                jsAttr($row['nombre_meta']),
                jsAttr($row['descripcion'] ?? ''),
                jsAttr((float)$row['monto_objetivo']),
                jsAttr($row['fecha_limite']),
                jsAttr($estado),
            ]);
            $acciones_html = '
                <button class="btn-agregar" onclick="abrirModalAhorro(' . $id_meta . ')">+ Agregar ahorro</button>
                <button class="btn-editar-obj" onclick="abrirModalEditarObj(' . $args_editar . ')"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"> Editar</button>'
                . $form_eliminar;
        }

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
                    <div class="obj-actual" data-ars="' . (float)$row['monto_actual'] . '">$' . $monto_act . '</div>
                    <div class="obj-meta">de <span data-ars="' . (float)$row['monto_objetivo'] . '">$' . $monto_obj . '</span></div>
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
                ' . $acciones_html . '
            </div>
        </div>';
    }

    if($total_metas > 0) {
        $promedio = number_format($total_progreso / $total_metas, 0);
    }
}

$extra_css = '<link rel="stylesheet" href="css/pages/objetivos.css">';
require_once 'includes/header.php';
?>

            <div class="page-header">
                <div class="page-header-texto">
                    <h2 class="titulo-con-icono"><img src="iconos/generales/dianaconflecha.png" alt="" class="icono-titulo"> Objetivos de ahorro</h2>
                    <p class="page-subtitle">Seguí el progreso de tus metas</p>
                </div>
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
                    echo '<p class="texto-vacio">No tenés objetivos todavía. ¡Creá uno nuevo para empezar a ahorrar!</p>';
                }
                ?>
            </div>

<?php
require_once 'includes/footer.php';
?>
