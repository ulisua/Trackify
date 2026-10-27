<?php
// includes/vista_movimientos_tipo.php
// Vista compartida por ingresos.php y gastos.php. Requiere $tipo_vista = 'ingreso' | 'gasto'
// y que header.php ya se haya incluido (usa $conn, $fecha_desde, $fecha_hasta, $periodo_actual).

$user_id = (int)$_SESSION['usuario_id'];
$es_ingreso = $tipo_vista === 'ingreso';
$config_vista = $es_ingreso
    ? ['titulo' => 'Ingresos', 'singular' => 'ingreso', 'icono' => 'iconos/ingreso/ventas.png', 'card' => 'ingreso-card', 'tabla' => 'tabla-ingresos', 'btn' => 'ingreso', 'signo' => '+', 'clase_monto' => 'positivo']
    : ['titulo' => 'Gastos', 'singular' => 'gasto', 'icono' => 'iconos/gasto/otrosgastos.png', 'card' => 'gasto-card', 'tabla' => 'tabla-gastos', 'btn' => 'gasto', 'signo' => '-', 'clase_monto' => 'negativo'];

// Totales del período actual
$stmt_totales = $conn->prepare("SELECT COALESCE(SUM(monto), 0) as total, COUNT(id_movimiento) as cantidad FROM movimientos WHERE id_usuario = ? AND tipo = ? AND fecha BETWEEN ? AND ?");
$stmt_totales->bind_param("isss", $user_id, $tipo_vista, $fecha_desde, $fecha_hasta);
$stmt_totales->execute();
$fila_totales = $stmt_totales->get_result()->fetch_assoc();
$total_periodo = (float)$fila_totales['total'];
$cantidad = (int)$fila_totales['cantidad'];
$promedio = $cantidad > 0 ? $total_periodo / $cantidad : 0;

// Movimientos del período actual
$stmt_lista = $conn->prepare("SELECT m.id_movimiento, m.fecha, m.descripcion, m.monto, c.nombre as categoria_nombre FROM movimientos m JOIN categorias c ON m.id_categoria = c.id_categoria WHERE m.id_usuario = ? AND m.tipo = ? AND m.fecha BETWEEN ? AND ? ORDER BY m.fecha DESC, m.id_movimiento DESC");
$stmt_lista->bind_param("isss", $user_id, $tipo_vista, $fecha_desde, $fecha_hasta);
$stmt_lista->execute();
$movimientos_vista = $stmt_lista->get_result()->fetch_all(MYSQLI_ASSOC);

$categorias_filtro = obtenerCategoriasVisibles($conn, $user_id)[$tipo_vista];

$etiquetas_periodo = ['semana' => 'de la semana', 'quincena' => 'de la quincena', 'mes' => 'del mes'];
$titulo_periodo = $etiquetas_periodo[$periodo_actual] ?? 'del período';

function argsEditarMovimiento($mov, $tipo) {
    return implode(', ', [
        (int)$mov['id_movimiento'],
        jsAttr((float)$mov['monto']),
        jsAttr($mov['categoria_nombre']),
        jsAttr($mov['descripcion'] ?? ''),
        jsAttr($mov['fecha']),
        jsAttr($tipo),
    ]);
}
?>

            <div class="page-header">
                <div class="page-header-texto">
                    <h2 class="titulo-con-icono"><img src="<?php echo $config_vista['icono']; ?>" alt="" class="icono-titulo"> <?php echo $config_vista['titulo']; ?></h2>
                    <p class="page-subtitle"><?php echo 'Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' — ' . date('d/m/Y', strtotime($fecha_hasta)); ?></p>
                </div>
            </div>

            <!-- RESUMEN -->
            <section class="cards">
                <div class="card <?php echo $config_vista['card']; ?>">
                    <h4>Total <?php echo $titulo_periodo; ?></h4>
                    <p data-ars="<?php echo $total_periodo; ?>">$<?php echo number_format($total_periodo, 2, ',', '.'); ?></p>
                </div>
                <div class="card">
                    <h4>Cantidad</h4>
                    <p><?php echo $cantidad; ?></p>
                </div>
                <div class="card">
                    <h4>Promedio</h4>
                    <p data-ars="<?php echo $promedio; ?>">$<?php echo number_format($promedio, 2, ',', '.'); ?></p>
                </div>
            </section>

            <!-- ACCIONES + FILTROS -->
            <div class="acciones-filtros-bar">
                <div class="acciones">
                    <button class="btn <?php echo $config_vista['btn']; ?> btn-nuevo-movimiento" onclick="abrirModal('<?php echo $tipo_vista; ?>')">+ Nuevo <?php echo $config_vista['singular']; ?></button>
                </div>
                <div class="filtros" data-filtros-movimientos>
                    <input type="text" id="filtroFecha" class="js-fecha-filtro" placeholder="Todas las fechas" aria-label="Filtrar por fecha" readonly>
                    <select id="filtroCategoria" aria-label="Filtrar por categoría">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias_filtro as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['nombre']); ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="btnVerTodos" class="btn-reset-filtros">Ver todos</button>
                </div>
            </div>

            <!-- TABLA (desktop) / CARDS (mobile) -->
            <section class="tabla-box">

                <table class="tabla tabla-desktop <?php echo $config_vista['tabla']; ?>">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Monto</th>
                            <th><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos_vista as $mov): ?>
                            <tr data-fecha="<?php echo htmlspecialchars($mov['fecha']); ?>" data-categoria="<?php echo htmlspecialchars($mov['categoria_nombre']); ?>">
                                <td><?php echo date('d/m/Y', strtotime($mov['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($mov['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($mov['categoria_nombre']); ?></td>
                                <td class="<?php echo $config_vista['clase_monto']; ?>"><span data-ars="<?php echo (float)$mov['monto']; ?>" data-prefijo="<?php echo $config_vista['signo']; ?>"><?php echo $config_vista['signo']; ?>$<?php echo number_format($mov['monto'], 2, ',', '.'); ?></span></td>
                                <td class="celda-acciones">
                                    <button type="button" class="edit" aria-label="Editar" onclick="abrirModalEditarMovimiento(<?php echo argsEditarMovimiento($mov, $tipo_vista); ?>)"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"></button>
                                    <form method="POST" action="" class="form-inline" onsubmit="confirmarEliminacion(event, '¿Eliminar este <?php echo $config_vista['singular']; ?>?');">
                                        <input type="hidden" name="form_type" value="eliminar_movimiento">
                                        <input type="hidden" name="id_movimiento" value="<?php echo (int)$mov['id_movimiento']; ?>">
                                        <button type="submit" class="delete" aria-label="Eliminar"><img src="iconos/generales/tachodebasura.png" alt="" class="icono-boton"></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="fila-vacia" data-sin-resultados <?php echo $movimientos_vista ? 'hidden' : ''; ?>>
                            <td colspan="5"><?php echo $movimientos_vista ? 'No hay resultados con esos filtros.' : 'No hay ' . strtolower($config_vista['titulo']) . ' registrados en este período.'; ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="movimiento-cards">
                    <?php foreach ($movimientos_vista as $mov): ?>
                        <div class="movimiento-card" data-fecha="<?php echo htmlspecialchars($mov['fecha']); ?>" data-categoria="<?php echo htmlspecialchars($mov['categoria_nombre']); ?>">
                            <div class="mc-top">
                                <span class="mc-desc"><?php echo htmlspecialchars($mov['descripcion']); ?></span>
                                <span class="mc-monto <?php echo $config_vista['clase_monto']; ?>" data-ars="<?php echo (float)$mov['monto']; ?>" data-prefijo="<?php echo $config_vista['signo']; ?>"><?php echo $config_vista['signo']; ?>$<?php echo number_format($mov['monto'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="mc-bottom">
                                <span class="mc-tag"><?php echo htmlspecialchars($mov['categoria_nombre']); ?></span>
                                <span class="mc-fecha"><?php echo date('d/m/Y', strtotime($mov['fecha'])); ?></span>
                                <div class="mc-acciones">
                                    <button type="button" class="edit" aria-label="Editar" onclick="abrirModalEditarMovimiento(<?php echo argsEditarMovimiento($mov, $tipo_vista); ?>)"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"></button>
                                    <form method="POST" action="" class="form-inline" onsubmit="confirmarEliminacion(event, '¿Eliminar este <?php echo $config_vista['singular']; ?>?');">
                                        <input type="hidden" name="form_type" value="eliminar_movimiento">
                                        <input type="hidden" name="id_movimiento" value="<?php echo (int)$mov['id_movimiento']; ?>">
                                        <button type="submit" class="delete" aria-label="Eliminar"><img src="iconos/generales/tachodebasura.png" alt="" class="icono-boton"></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="texto-vacio" data-sin-resultados <?php echo $movimientos_vista ? 'hidden' : ''; ?>><?php echo $movimientos_vista ? 'No hay resultados con esos filtros.' : 'No hay ' . strtolower($config_vista['titulo']) . ' registrados en este período.'; ?></div>
                </div>
            </section>
