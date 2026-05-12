<?php
$page = 'gastos';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION['usuario_id'];

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    header('Content-Type: application/json');
    $id = intval($_POST['id_movimiento']);
    $check = $conn->prepare("SELECT id_movimiento FROM movimientos WHERE id_movimiento=? AND id_usuario=? AND tipo='gasto'");
    $check->bind_param("ii", $id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) { echo json_encode(['ok' => false]); exit(); }
    $stmt = $conn->prepare("DELETE FROM movimientos WHERE id_movimiento=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

// ── EDITAR ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    header('Content-Type: application/json');
    $id     = intval($_POST['id_movimiento']);
    $desc   = trim($_POST['descripcion']) ?: 'Sin descripción';
    $monto  = floatval($_POST['monto']);
    $fecha  = $_POST['fecha'];
    $id_cat = intval($_POST['id_categoria']);
    if ($monto <= 0) { echo json_encode(['ok' => false, 'error' => 'Monto inválido']); exit(); }
    $check = $conn->prepare("SELECT id_movimiento FROM movimientos WHERE id_movimiento=? AND id_usuario=? AND tipo='gasto'");
    $check->bind_param("ii", $id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) { echo json_encode(['ok' => false]); exit(); }
    $stmt = $conn->prepare("UPDATE movimientos SET descripcion=?, monto=?, fecha=?, id_categoria=? WHERE id_movimiento=?");
    $stmt->bind_param("sdsii", $desc, $monto, $fecha, $id_cat, $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

require_once 'includes/header.php';

// Obtener el mes y año actual
$mes_actual = date('m');
$anio_actual = date('Y');

// Variables para cálculos
$total_mes = 0;
$cantidad = 0;
$promedio = 0;

// Consulta para totales del mes actual
$stmt_totales = $conn->prepare("SELECT SUM(monto) as total, COUNT(id_movimiento) as cantidad FROM movimientos WHERE id_usuario = ? AND tipo = 'gasto' AND MONTH(fecha) = ? AND YEAR(fecha) = ?");
$stmt_totales->bind_param("iii", $user_id, $mes_actual, $anio_actual);
$stmt_totales->execute();
$res_totales = $stmt_totales->get_result();

if ($row = $res_totales->fetch_assoc()) {
    $total_mes = $row['total'] ?? 0;
    $cantidad = $row['cantidad'] ?? 0;
    if ($cantidad > 0) {
        $promedio = $total_mes / $cantidad;
    }
}

// Consulta para la lista de todos los gastos (ahora también trae id_categoria)
$stmt_lista = $conn->prepare("SELECT m.id_movimiento, m.fecha, m.descripcion, m.monto, m.id_categoria, c.nombre as categoria_nombre FROM movimientos m JOIN categorias c ON m.id_categoria = c.id_categoria WHERE m.id_usuario = ? AND m.tipo = 'gasto' ORDER BY m.fecha DESC");
$stmt_lista->bind_param("i", $user_id);
$stmt_lista->execute();
$res_lista = $stmt_lista->get_result();
$gastos = [];
while ($row = $res_lista->fetch_assoc()) {
    $gastos[] = $row;
}

// Categorías de gasto para el select del modal de edición
$stmt_cats = $conn->prepare("SELECT id_categoria, nombre FROM categorias WHERE tipo='gasto' ORDER BY nombre");
$stmt_cats->execute();
$categorias = $stmt_cats->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- MODAL EDITAR -->
<div id="modalEditar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:420px;margin:16px;display:flex;flex-direction:column;gap:14px;">
        <h3 style="margin:0;">✏️ Editar gasto</h3>
        <input type="hidden" id="editId">
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Descripción</label>
            <input type="text" id="editDesc" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Monto</label>
            <input type="number" id="editMonto" min="0.01" step="0.01" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Fecha</label>
            <input type="date" id="editFecha" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Categoría</label>
            <select id="editCategoria" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;background:#fff;">
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <p id="editError" style="color:#EF4444;font-size:.85rem;margin:0;display:none;"></p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="cerrarEditar()" style="padding:10px 20px;border:1px solid #E2E8F0;border-radius:8px;background:#fff;cursor:pointer;font-size:.95rem;">Cancelar</button>
            <button onclick="guardarEdicion()" style="padding:10px 20px;border:none;border-radius:8px;background:#084734;color:#CFF27C;cursor:pointer;font-size:.95rem;font-weight:600;">Guardar</button>
        </div>
    </div>
</div>

        <h2>💸 Gastos</h2>

        <!-- RESUMEN -->
        <section class="cards">
            <div class="card gasto-card">
                <h4>Total del mes</h4>
                <p>$<?php echo number_format($total_mes, 2, ',', '.'); ?></p>
            </div>
            <div class="card">
                <h4>Cantidad</h4>
                <p><?php echo $cantidad; ?></p>
            </div>
            <div class="card">
                <h4>Promedio</h4>
                <p>$<?php echo number_format($promedio, 2, ',', '.'); ?></p>
            </div>
        </section>

        <!-- ACCIONES -->
        <div class="acciones">
            <button class="btn gasto" onclick="abrirModal('gasto')">+ Nuevo gasto</button>
        </div>

        <!-- FILTROS -->
        <section class="filtros">
            <input type="date">
            <select>
                <option>Todos</option>
                <option>Comida</option>
                <option>Transporte</option>
                <option>Servicios</option>
                <option>Entretenimiento</option>
                <option>Salud</option>
            </select>
        </section>

        <!-- TABLA (desktop) / CARDS (mobile) -->
        <section class="tabla-box">

            <!-- Tabla para desktop -->
            <table class="tabla tabla-desktop">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Categoría</th>
                        <th>Monto</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tablaGastos">
                    <?php if (count($gastos) > 0): ?>
                        <?php foreach ($gastos as $gasto): ?>
                            <tr id="fila-<?= $gasto['id_movimiento'] ?>">
                                <td><?php echo date('d/m', strtotime($gasto['fecha'])); ?></td>
                                <td><?php echo htmlspecialchars($gasto['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($gasto['categoria_nombre']); ?></td>
                                <td class="negativo">-$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></td>
                                <td>
                                    <button class="edit" onclick="abrirEditar(<?= $gasto['id_movimiento'] ?>, '<?= addslashes(htmlspecialchars($gasto['descripcion'])) ?>', <?= $gasto['monto'] ?>, '<?= $gasto['fecha'] ?>', <?= $gasto['id_categoria'] ?>)">✏️</button>
                                    <button class="delete" onclick="eliminar(<?= $gasto['id_movimiento'] ?>, 'fila-<?= $gasto['id_movimiento'] ?>')">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">No hay gastos registrados aún.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Cards para mobile -->
            <div class="movimiento-cards" id="movimientoCards">
                <?php if (count($gastos) > 0): ?>
                    <?php foreach ($gastos as $gasto): ?>
                        <div class="movimiento-card" id="card-<?= $gasto['id_movimiento'] ?>">
                            <div class="mc-top">
                                <span class="mc-desc"><?php echo htmlspecialchars($gasto['descripcion']); ?></span>
                                <span class="mc-monto negativo">-$<?php echo number_format($gasto['monto'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="mc-bottom">
                                <span class="mc-tag"><?php echo htmlspecialchars($gasto['categoria_nombre']); ?></span>
                                <span class="mc-fecha"><?php echo date('d/m', strtotime($gasto['fecha'])); ?></span>
                                <div class="mc-acciones">
                                    <button class="edit" onclick="abrirEditar(<?= $gasto['id_movimiento'] ?>, '<?= addslashes(htmlspecialchars($gasto['descripcion'])) ?>', <?= $gasto['monto'] ?>, '<?= $gasto['fecha'] ?>', <?= $gasto['id_categoria'] ?>)">✏️</button>
                                    <button class="delete" onclick="eliminar(<?= $gasto['id_movimiento'] ?>, 'card-<?= $gasto['id_movimiento'] ?>')">🗑️</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #64748b;">No hay gastos registrados aún.</div>
                <?php endif; ?>
            </div>

        </section>

<script>
function abrirEditar(id, desc, monto, fecha, idCat) {
    document.getElementById('editId').value = id;
    document.getElementById('editDesc').value = desc;
    document.getElementById('editMonto').value = monto;
    document.getElementById('editFecha').value = fecha;
    document.getElementById('editCategoria').value = idCat;
    document.getElementById('editError').style.display = 'none';
    document.getElementById('modalEditar').style.display = 'flex';
}
function cerrarEditar() {
    document.getElementById('modalEditar').style.display = 'none';
}
document.getElementById('modalEditar').addEventListener('click', function(e) {
    if (e.target === this) cerrarEditar();
});
function guardarEdicion() {
    const monto = parseFloat(document.getElementById('editMonto').value);
    const fecha = document.getElementById('editFecha').value;
    const errEl = document.getElementById('editError');
    if (!monto || monto <= 0) { errEl.textContent = 'El monto debe ser mayor a cero.'; errEl.style.display = 'block'; return; }
    if (!fecha) { errEl.textContent = 'La fecha es obligatoria.'; errEl.style.display = 'block'; return; }
    const fd = new FormData();
    fd.append('accion', 'editar');
    fd.append('id_movimiento', document.getElementById('editId').value);
    fd.append('descripcion', document.getElementById('editDesc').value.trim());
    fd.append('monto', monto);
    fd.append('fecha', fecha);
    fd.append('id_categoria', document.getElementById('editCategoria').value);
    fetch('gastos.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else { errEl.textContent = data.error ?? 'Error al guardar.'; errEl.style.display = 'block'; }
        })
        .catch(() => { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}
function eliminar(id, elementId) {
    if (!confirm('¿Eliminar este gasto?')) return;
    const fd = new FormData();
    fd.append('accion', 'eliminar');
    fd.append('id_movimiento', id);
    fetch('gastos.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) document.getElementById(elementId)?.remove();
            else alert('No se pudo eliminar.');
        })
        .catch(() => alert('Error de conexión.'));
}
</script>

<?php require_once 'includes/footer.php'; ?>
