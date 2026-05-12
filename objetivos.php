<?php
$page = 'objetivos';
$extra_css = '<link rel="stylesheet" href="css/objetivos.css">';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION['usuario_id'];

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    header('Content-Type: application/json');
    $id = intval($_POST['id_meta']);
    $check = $conn->prepare("SELECT id_meta FROM metas_ahorro WHERE id_meta=? AND id_usuario=?");
    $check->bind_param("ii", $id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) { echo json_encode(['ok' => false]); exit(); }
    $stmt = $conn->prepare("DELETE FROM metas_ahorro WHERE id_meta=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

// ── EDITAR ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    header('Content-Type: application/json');
    $id             = intval($_POST['id_meta']);
    $nombre         = trim($_POST['nombre_meta']);
    $monto_objetivo = floatval($_POST['monto_objetivo']);
    $fecha_limite   = $_POST['fecha_limite'];
    if (!$nombre || $monto_objetivo <= 0) { echo json_encode(['ok' => false, 'error' => 'Datos inválidos.']); exit(); }
    $check = $conn->prepare("SELECT id_meta FROM metas_ahorro WHERE id_meta=? AND id_usuario=?");
    $check->bind_param("ii", $id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) { echo json_encode(['ok' => false]); exit(); }
    $stmt = $conn->prepare("UPDATE metas_ahorro SET nombre_meta=?, monto_objetivo=?, fecha_limite=? WHERE id_meta=?");
    $stmt->bind_param("sdsi", $nombre, $monto_objetivo, $fecha_limite, $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

// ── AGREGAR AHORRO ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar_ahorro') {
    header('Content-Type: application/json');
    $id    = intval($_POST['id_meta']);
    $monto = floatval($_POST['monto']);
    if ($monto <= 0) { echo json_encode(['ok' => false, 'error' => 'El monto debe ser mayor a cero.']); exit(); }
    $check = $conn->prepare("SELECT id_meta, monto_objetivo, monto_actual FROM metas_ahorro WHERE id_meta=? AND id_usuario=?");
    $check->bind_param("ii", $id, $user_id);
    $check->execute();
    $meta = $check->get_result()->fetch_assoc();
    if (!$meta) { echo json_encode(['ok' => false]); exit(); }
    $nuevo_actual = min($meta['monto_actual'] + $monto, $meta['monto_objetivo']);
    $stmt = $conn->prepare("UPDATE metas_ahorro SET monto_actual=? WHERE id_meta=?");
    $stmt->bind_param("di", $nuevo_actual, $id);
    echo json_encode(['ok' => $stmt->execute(), 'nuevo_actual' => $nuevo_actual]);
    exit();
}

// ── CREAR ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    header('Content-Type: application/json');
    $nombre         = trim($_POST['nombre_meta']);
    $monto_objetivo = floatval($_POST['monto_objetivo']);
    $monto_actual   = floatval($_POST['monto_actual'] ?? 0);
    $fecha_limite   = $_POST['fecha_limite'] ?: null;
    if (!$nombre || $monto_objetivo <= 0) { echo json_encode(['ok' => false, 'error' => 'Datos inválidos.']); exit(); }
    $stmt = $conn->prepare("INSERT INTO metas_ahorro (id_usuario, nombre_meta, monto_objetivo, monto_actual, fecha_limite) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdds", $user_id, $nombre, $monto_objetivo, $monto_actual, $fecha_limite);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

require_once 'includes/header.php';

// ── LEER OBJETIVOS DE LA BD ───────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM metas_ahorro WHERE id_usuario=? ORDER BY fecha_limite ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$objetivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular estadísticas
$total_activos  = 0;
$total_logrados = 0;
$suma_pct       = 0;

foreach ($objetivos as $obj) {
    $pct = $obj['monto_objetivo'] > 0 ? ($obj['monto_actual'] / $obj['monto_objetivo']) * 100 : 0;
    $suma_pct += min($pct, 100);
    if ($pct >= 100) $total_logrados++;
    else $total_activos++;
}
$promedio_pct = count($objetivos) > 0 ? round($suma_pct / count($objetivos)) : 0;

// Colores para la barra de progreso según porcentaje
function colorPct($pct) {
    if ($pct >= 100) return ['pct-verde', 'fill-verde'];
    if ($pct >= 60)  return ['pct-azul',  'fill-azul'];
    if ($pct >= 30)  return ['pct-lila',  'fill-lila'];
    return ['pct-naranja', 'fill-naranja'];
}
?>

<!-- MODAL NUEVO OBJETIVO -->
<div id="modalNuevo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:420px;margin:16px;display:flex;flex-direction:column;gap:14px;">
        <h3 style="margin:0;">🎯 Nuevo objetivo</h3>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Nombre</label>
            <input type="text" id="nuevoNombre" placeholder="Ej: Notebook nueva" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Monto objetivo</label>
            <input type="number" id="nuevoMonto" min="1" step="1" placeholder="0" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Ya ahorré (opcional)</label>
            <input type="number" id="nuevoActual" min="0" step="1" placeholder="0" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Fecha límite (opcional)</label>
            <input type="date" id="nuevoFecha" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <p id="nuevoError" style="color:#EF4444;font-size:.85rem;margin:0;display:none;"></p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="cerrarNuevo()" style="padding:10px 20px;border:1px solid #E2E8F0;border-radius:8px;background:#fff;cursor:pointer;font-size:.95rem;">Cancelar</button>
            <button onclick="guardarNuevo()" style="padding:10px 20px;border:none;border-radius:8px;background:#084734;color:#CFF27C;cursor:pointer;font-size:.95rem;font-weight:600;">Crear</button>
        </div>
    </div>
</div>

<!-- MODAL EDITAR OBJETIVO -->
<div id="modalEditar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:420px;margin:16px;display:flex;flex-direction:column;gap:14px;">
        <h3 style="margin:0;">✏️ Editar objetivo</h3>
        <input type="hidden" id="editId">
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Nombre</label>
            <input type="text" id="editNombre" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Monto objetivo</label>
            <input type="number" id="editMonto" min="1" step="1" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Fecha límite</label>
            <input type="date" id="editFecha" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <p id="editError" style="color:#EF4444;font-size:.85rem;margin:0;display:none;"></p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="cerrarEditar()" style="padding:10px 20px;border:1px solid #E2E8F0;border-radius:8px;background:#fff;cursor:pointer;font-size:.95rem;">Cancelar</button>
            <button onclick="guardarEdicion()" style="padding:10px 20px;border:none;border-radius:8px;background:#084734;color:#CFF27C;cursor:pointer;font-size:.95rem;font-weight:600;">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL AGREGAR AHORRO -->
<div id="modalAhorro" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:380px;margin:16px;display:flex;flex-direction:column;gap:14px;">
        <h3 style="margin:0;">💰 Agregar ahorro</h3>
        <input type="hidden" id="ahorroId">
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Monto a agregar</label>
            <input type="number" id="ahorroMonto" min="1" step="1" placeholder="0" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <p id="ahorroError" style="color:#EF4444;font-size:.85rem;margin:0;display:none;"></p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="cerrarAhorro()" style="padding:10px 20px;border:1px solid #E2E8F0;border-radius:8px;background:#fff;cursor:pointer;font-size:.95rem;">Cancelar</button>
            <button onclick="guardarAhorro()" style="padding:10px 20px;border:none;border-radius:8px;background:#084734;color:#CFF27C;cursor:pointer;font-size:.95rem;font-weight:600;">Agregar</button>
        </div>
    </div>
</div>

            <div class="page-header">
                <h2>🎯 Objetivos de ahorro</h2>
                <button class="btn-nuevo" onclick="abrirNuevo()">+ Nuevo objetivo</button>
            </div>

            <!-- Stats (datos reales de la BD) -->
            <div class="obj-stats">
                <div class="obj-stat">
                    <span class="obj-stat-num"><?= $total_activos ?></span>
                    <span class="obj-stat-label">Activos</span>
                </div>
                <div class="obj-stat">
                    <span class="obj-stat-num"><?= $total_logrados ?></span>
                    <span class="obj-stat-label">Logrados</span>
                </div>
                <div class="obj-stat">
                    <span class="obj-stat-num"><?= $promedio_pct ?>%</span>
                    <span class="obj-stat-label">Promedio</span>
                </div>
            </div>

            <!-- Objetivos -->
            <div class="obj-lista">

                <?php if (count($objetivos) === 0): ?>
                    <p style="color:#64748b;text-align:center;padding:40px 0;">No tenés objetivos creados aún. ¡Creá uno!</p>
                <?php endif; ?>

                <?php foreach ($objetivos as $obj):
                    $pct = $obj['monto_objetivo'] > 0 ? min(round(($obj['monto_actual'] / $obj['monto_objetivo']) * 100), 100) : 0;
                    $logrado = $pct >= 100;
                    [$clase_pct, $clase_fill] = colorPct($pct);
                    $fecha_fmt = $obj['fecha_limite'] ? 'Vence: ' . date('d/m/Y', strtotime($obj['fecha_limite'])) : 'Sin vencimiento';
                ?>
                <div class="obj-card <?= $logrado ? 'completado' : '' ?>" id="obj-<?= $obj['id_meta'] ?>">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                🎯 <?= htmlspecialchars($obj['nombre_meta']) ?>
                                <?php if ($logrado): ?>
                                    <span class="obj-badge badge-logrado">Logrado</span>
                                <?php else: ?>
                                    <span class="obj-badge badge-activo">Activo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual" id="actual-<?= $obj['id_meta'] ?>">$<?= number_format($obj['monto_actual'], 0, ',', '.') ?></div>
                            <div class="obj-meta">de $<?= number_format($obj['monto_objetivo'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct <?= $clase_pct ?>" id="pct-<?= $obj['id_meta'] ?>"><?= $pct ?>%</span>
                            <span class="obj-fecha"><?= $logrado ? 'Completado ✓' : $fecha_fmt ?></span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill <?= $clase_fill ?>" id="barra-<?= $obj['id_meta'] ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <?php if (!$logrado): ?>
                            <button class="btn-agregar" onclick="abrirAhorro(<?= $obj['id_meta'] ?>)">+ Agregar ahorro</button>
                        <?php endif; ?>
                        <button class="btn-editar-obj" onclick="abrirEditar(<?= $obj['id_meta'] ?>, '<?= addslashes(htmlspecialchars($obj['nombre_meta'])) ?>', <?= $obj['monto_objetivo'] ?>, '<?= $obj['fecha_limite'] ?>')">✏️ Editar</button>
                        <button class="btn-eliminar-obj" onclick="eliminar(<?= $obj['id_meta'] ?>, 'obj-<?= $obj['id_meta'] ?>')">🗑️ Eliminar</button>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>

        </main>

<script>
// ── Modal nuevo objetivo ──────────────────────────────────────────────────────
function abrirNuevo() {
    document.getElementById('nuevoNombre').value  = '';
    document.getElementById('nuevoMonto').value   = '';
    document.getElementById('nuevoActual').value  = '';
    document.getElementById('nuevoFecha').value   = '';
    document.getElementById('nuevoError').style.display = 'none';
    document.getElementById('modalNuevo').style.display = 'flex';
}
function cerrarNuevo() {
    document.getElementById('modalNuevo').style.display = 'none';
}
document.getElementById('modalNuevo').addEventListener('click', function(e) {
    if (e.target === this) cerrarNuevo();
});
function guardarNuevo() {
    const nombre  = document.getElementById('nuevoNombre').value.trim();
    const monto   = parseFloat(document.getElementById('nuevoMonto').value);
    const actual  = parseFloat(document.getElementById('nuevoActual').value) || 0;
    const fecha   = document.getElementById('nuevoFecha').value;
    const errEl   = document.getElementById('nuevoError');
    if (!nombre) { errEl.textContent = 'El nombre es obligatorio.'; errEl.style.display = 'block'; return; }
    if (!monto || monto <= 0) { errEl.textContent = 'El monto debe ser mayor a cero.'; errEl.style.display = 'block'; return; }
    if (actual > monto) { errEl.textContent = 'Lo ya ahorrado no puede superar el objetivo.'; errEl.style.display = 'block'; return; }
    const fd = new FormData();
    fd.append('accion', 'crear');
    fd.append('nombre_meta', nombre);
    fd.append('monto_objetivo', monto);
    fd.append('monto_actual', actual);
    fd.append('fecha_limite', fecha);
    fetch('objetivos.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else { errEl.textContent = data.error ?? 'Error al crear.'; errEl.style.display = 'block'; }
        })
        .catch(() => { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}

// ── Modal editar ──────────────────────────────────────────────────────────────
function abrirEditar(id, nombre, monto, fecha) {
    document.getElementById('editId').value     = id;
    document.getElementById('editNombre').value = nombre;
    document.getElementById('editMonto').value  = monto;
    document.getElementById('editFecha').value  = fecha;
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
    const nombre = document.getElementById('editNombre').value.trim();
    const monto  = parseFloat(document.getElementById('editMonto').value);
    const fecha  = document.getElementById('editFecha').value;
    const errEl  = document.getElementById('editError');
    if (!nombre) { errEl.textContent = 'El nombre es obligatorio.'; errEl.style.display = 'block'; return; }
    if (!monto || monto <= 0) { errEl.textContent = 'El monto debe ser mayor a cero.'; errEl.style.display = 'block'; return; }
    const fd = new FormData();
    fd.append('accion', 'editar');
    fd.append('id_meta', document.getElementById('editId').value);
    fd.append('nombre_meta', nombre);
    fd.append('monto_objetivo', monto);
    fd.append('fecha_limite', fecha);
    fetch('objetivos.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else { errEl.textContent = data.error ?? 'Error al guardar.'; errEl.style.display = 'block'; }
        })
        .catch(() => { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}

// ── Modal agregar ahorro ──────────────────────────────────────────────────────
function abrirAhorro(id) {
    document.getElementById('ahorroId').value = id;
    document.getElementById('ahorroMonto').value = '';
    document.getElementById('ahorroError').style.display = 'none';
    document.getElementById('modalAhorro').style.display = 'flex';
}
function cerrarAhorro() {
    document.getElementById('modalAhorro').style.display = 'none';
}
document.getElementById('modalAhorro').addEventListener('click', function(e) {
    if (e.target === this) cerrarAhorro();
});
function guardarAhorro() {
    const monto = parseFloat(document.getElementById('ahorroMonto').value);
    const errEl = document.getElementById('ahorroError');
    if (!monto || monto <= 0) { errEl.textContent = 'El monto debe ser mayor a cero.'; errEl.style.display = 'block'; return; }
    const fd = new FormData();
    fd.append('accion', 'agregar_ahorro');
    fd.append('id_meta', document.getElementById('ahorroId').value);
    fd.append('monto', monto);
    fetch('objetivos.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else { errEl.textContent = data.error ?? 'Error al guardar.'; errEl.style.display = 'block'; }
        })
        .catch(() => { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}

// ── Eliminar ──────────────────────────────────────────────────────────────────
function eliminar(id, elementId) {
    if (!confirm('¿Eliminar este objetivo? Esta acción no se puede deshacer.')) return;
    const fd = new FormData();
    fd.append('accion', 'eliminar');
    fd.append('id_meta', id);
    fetch('objetivos.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) document.getElementById(elementId)?.remove();
            else alert('No se pudo eliminar.');
        })
        .catch(() => alert('Error de conexión.'));
}
</script>

<?php 
$extra_js = '<script src="js/objetivos.js"></script>';
require_once 'includes/footer.php'; 
?>
