<?php
$page = 'categorias';
$extra_css = '<link rel="stylesheet" href="css/categorias.css">';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION['usuario_id'];

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    header('Content-Type: application/json');
    $id = intval($_POST['id_categoria']);
    // Verificar que no tenga movimientos asociados
    $check = $conn->prepare("SELECT COUNT(*) as total FROM movimientos WHERE id_categoria = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $cant = $check->get_result()->fetch_assoc()['total'];
    if ($cant > 0) {
        echo json_encode(['ok' => false, 'error' => "No se puede eliminar: tiene $cant movimiento(s) asociado(s)."]);
        exit();
    }
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id_categoria = ?");
    $stmt->bind_param("i", $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

// ── EDITAR ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    header('Content-Type: application/json');
    $id     = intval($_POST['id_categoria']);
    $nombre = trim($_POST['nombre']);
    if (!$nombre) { echo json_encode(['ok' => false, 'error' => 'El nombre no puede estar vacío.']); exit(); }
    $stmt = $conn->prepare("UPDATE categorias SET nombre = ? WHERE id_categoria = ?");
    $stmt->bind_param("si", $nombre, $id);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

// ── CREAR ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    header('Content-Type: application/json');
    $nombre = trim($_POST['nombre']);
    $tipo   = $_POST['tipo'] === 'ingreso' ? 'ingreso' : 'gasto';
    if (!$nombre) { echo json_encode(['ok' => false, 'error' => 'El nombre no puede estar vacío.']); exit(); }
    // Verificar que no exista ya una con ese nombre y tipo
    $check = $conn->prepare("SELECT id_categoria FROM categorias WHERE nombre=? AND tipo=?");
    $check->bind_param("ss", $nombre, $tipo);
    $check->execute();
    if ($check->get_result()->num_rows > 0) { echo json_encode(['ok' => false, 'error' => 'Ya existe una categoría con ese nombre.']); exit(); }
    $stmt = $conn->prepare("INSERT INTO categorias (nombre, tipo, Icono_tipo) VALUES (?, ?, '')");
    $stmt->bind_param("ss", $nombre, $tipo);
    echo json_encode(['ok' => $stmt->execute()]);
    exit();
}

require_once 'includes/header.php';

$mes_actual = date('m');
$anio_actual = date('Y');

// Paleta de colores predefinida
$colores = ['#F97316', '#3B82F6', '#8B5CF6', '#EC4899', '#10B981', '#F59E0B', '#EF4444', '#14B8A6', '#CFF27C', '#EA73F5'];

// Obtener todas las categorías y sumar sus movimientos
$stmt = $conn->prepare("
    SELECT c.id_categoria, c.nombre, c.tipo,
           COUNT(m.id_movimiento) as cantidad, 
           SUM(m.monto) as total_monto
    FROM categorias c
    LEFT JOIN movimientos m ON c.id_categoria = m.id_categoria AND m.id_usuario = ? AND MONTH(m.fecha) = ? AND YEAR(m.fecha) = ?
    GROUP BY c.id_categoria
    ORDER BY total_monto DESC
");
$stmt->bind_param("iii", $user_id, $mes_actual, $anio_actual);
$stmt->execute();
$res = $stmt->get_result();

$gastos = [];
$ingresos = [];
$total_gastos = 0;
$total_ingresos = 0;
$color_index_g = 0;
$color_index_i = 0;

while ($row = $res->fetch_assoc()) {
    if ($row['tipo'] === 'gasto') {
        $row['color'] = $colores[$color_index_g % count($colores)];
        $gastos[] = $row;
        $total_gastos += $row['total_monto'] ?? 0;
        $color_index_g++;
    } else {
        $row['color'] = $colores[$color_index_i % count($colores)];
        $ingresos[] = $row;
        $total_ingresos += $row['total_monto'] ?? 0;
        $color_index_i++;
    }
}
?>

<!-- MODAL NUEVA CATEGORÍA -->
<div id="modalNueva" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:380px;margin:16px;display:flex;flex-direction:column;gap:14px;">
        <h3 style="margin:0;">🏷️ Nueva categoría</h3>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Nombre</label>
            <input type="text" id="nuevaNombre" placeholder="Ej: Transporte" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Tipo</label>
            <select id="nuevaTipo" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;background:#fff;">
                <option value="gasto">Gasto</option>
                <option value="ingreso">Ingreso</option>
            </select>
        </div>
        <p id="nuevaError" style="color:#EF4444;font-size:.85rem;margin:0;display:none;"></p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="cerrarNueva()" style="padding:10px 20px;border:1px solid #E2E8F0;border-radius:8px;background:#fff;cursor:pointer;font-size:.95rem;">Cancelar</button>
            <button onclick="guardarNueva()" style="padding:10px 20px;border:none;border-radius:8px;background:#084734;color:#CFF27C;cursor:pointer;font-size:.95rem;font-weight:600;">Crear</button>
        </div>
    </div>
</div>

<!-- MODAL EDITAR CATEGORÍA -->
<div id="modalEditar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:100%;max-width:380px;margin:16px;display:flex;flex-direction:column;gap:14px;">
        <h3 style="margin:0;">✏️ Editar categoría</h3>
        <input type="hidden" id="editId">
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:.85rem;font-weight:600;color:#64748b;">Nombre</label>
            <input type="text" id="editNombre" style="padding:10px 12px;border:1px solid #E2E8F0;border-radius:8px;font-size:1rem;">
        </div>
        <p id="editError" style="color:#EF4444;font-size:.85rem;margin:0;display:none;"></p>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="cerrarEditar()" style="padding:10px 20px;border:1px solid #E2E8F0;border-radius:8px;background:#fff;cursor:pointer;font-size:.95rem;">Cancelar</button>
            <button onclick="guardarEdicion()" style="padding:10px 20px;border:none;border-radius:8px;background:#084734;color:#CFF27C;cursor:pointer;font-size:.95rem;font-weight:600;">Guardar</button>
        </div>
    </div>
</div>

            <div class="page-header">
                <h2>🏷️ Categorías</h2>
                <button class="btn-nuevo" onclick="abrirNueva()">+ Nueva categoría</button>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('gastos', this)">Gastos</button>
                <button class="tab" onclick="switchTab('ingresos', this)">Ingresos</button>
            </div>

            <!-- Resumen distribución (Gastos) -->
            <div class="resumen-cats" id="resumenGastos">
                <h3>Distribución del mes (Gastos)</h3>
                <?php if ($total_gastos > 0): ?>
                    <?php foreach ($gastos as $gasto): 
                        if (!$gasto['total_monto']) continue;
                        $pct = round(($gasto['total_monto'] / $total_gastos) * 100);
                    ?>
                    <div class="resumen-row">
                        <span class="resumen-cat-nombre">🏷️ <?php echo htmlspecialchars($gasto['nombre']); ?></span>
                        <div class="resumen-barra">
                            <div class="resumen-barra-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $gasto['color']; ?>"></div>
                        </div>
                        <span class="resumen-pct"><?php echo $pct; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748B;">No hay gastos registrados este mes.</p>
                <?php endif; ?>
            </div>

            <!-- Resumen distribución (Ingresos, oculto por defecto) -->
            <div class="resumen-cats" id="resumenIngresos" style="display:none">
                <h3>Distribución del mes (Ingresos)</h3>
                <?php if ($total_ingresos > 0): ?>
                    <?php foreach ($ingresos as $ingreso): 
                        if (!$ingreso['total_monto']) continue;
                        $pct = round(($ingreso['total_monto'] / $total_ingresos) * 100);
                    ?>
                    <div class="resumen-row">
                        <span class="resumen-cat-nombre">🏷️ <?php echo htmlspecialchars($ingreso['nombre']); ?></span>
                        <div class="resumen-barra">
                            <div class="resumen-barra-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $ingreso['color']; ?>"></div>
                        </div>
                        <span class="resumen-pct"><?php echo $pct; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748B;">No hay ingresos registrados este mes.</p>
                <?php endif; ?>
            </div>

            <!-- Grid de categorías — GASTOS -->
            <div class="cat-grid" id="gridGastos">
                <?php foreach ($gastos as $gasto): 
                    $monto = $gasto['total_monto'] ?? 0;
                    $cant = $gasto['cantidad'] ?? 0;
                    $pct = $total_gastos > 0 ? round(($monto / $total_gastos) * 100) : 0;
                ?>
                <div class="cat-card" id="cat-<?= $gasto['id_categoria'] ?>">
                    <div class="cat-icon">🏷️</div>
                    <div class="cat-nombre"><?php echo htmlspecialchars($gasto['nombre']); ?></div>
                    <div class="cat-stats">
                        <span class="cat-monto">$<?php echo number_format($monto, 2, ',', '.'); ?></span>
                        <span class="cat-cant"><?php echo $cant; ?> movimiento<?php echo $cant != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $gasto['color']; ?>"></div>
                    </div>
                    <div class="cat-acciones">
                        <button onclick="abrirEditar(<?= $gasto['id_categoria'] ?>, '<?= addslashes(htmlspecialchars($gasto['nombre'])) ?>')">✏️ Editar</button>
                        <button class="btn-del" onclick="eliminar(<?= $gasto['id_categoria'] ?>, <?= $cant ?>, 'cat-<?= $gasto['id_categoria'] ?>')">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Grid de categorías — INGRESOS (oculto por defecto) -->
            <div class="cat-grid" id="gridIngresos" style="display:none">
                <?php foreach ($ingresos as $ingreso): 
                    $monto = $ingreso['total_monto'] ?? 0;
                    $cant = $ingreso['cantidad'] ?? 0;
                    $pct = $total_ingresos > 0 ? round(($monto / $total_ingresos) * 100) : 0;
                ?>
                <div class="cat-card" id="cat-<?= $ingreso['id_categoria'] ?>">
                    <div class="cat-icon">🏷️</div>
                    <div class="cat-nombre"><?php echo htmlspecialchars($ingreso['nombre']); ?></div>
                    <div class="cat-stats">
                        <span class="cat-monto">$<?php echo number_format($monto, 2, ',', '.'); ?></span>
                        <span class="cat-cant"><?php echo $cant; ?> movimiento<?php echo $cant != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $ingreso['color']; ?>"></div>
                    </div>
                    <div class="cat-acciones">
                        <button onclick="abrirEditar(<?= $ingreso['id_categoria'] ?>, '<?= addslashes(htmlspecialchars($ingreso['nombre'])) ?>')">✏️ Editar</button>
                        <button class="btn-del" onclick="eliminar(<?= $ingreso['id_categoria'] ?>, <?= $cant ?>, 'cat-<?= $ingreso['id_categoria'] ?>')">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </main>

<script>
function abrirNueva() {
    document.getElementById('nuevaNombre').value = '';
    document.getElementById('nuevaTipo').value = 'gasto';
    document.getElementById('nuevaError').style.display = 'none';
    document.getElementById('modalNueva').style.display = 'flex';
}
function cerrarNueva() {
    document.getElementById('modalNueva').style.display = 'none';
}
document.getElementById('modalNueva').addEventListener('click', function(e) {
    if (e.target === this) cerrarNueva();
});
function guardarNueva() {
    const nombre = document.getElementById('nuevaNombre').value.trim();
    const tipo   = document.getElementById('nuevaTipo').value;
    const errEl  = document.getElementById('nuevaError');
    if (!nombre) { errEl.textContent = 'El nombre no puede estar vacío.'; errEl.style.display = 'block'; return; }
    const fd = new FormData();
    fd.append('accion', 'crear');
    fd.append('nombre', nombre);
    fd.append('tipo', tipo);
    fetch('categorias.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else { errEl.textContent = data.error ?? 'Error al crear.'; errEl.style.display = 'block'; }
        })
        .catch(() => { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}

function switchTab(tab, btn) {
    document.getElementById('gridGastos').style.display    = tab === 'gastos' ? '' : 'none';
    document.getElementById('gridIngresos').style.display  = tab === 'ingresos' ? '' : 'none';
    document.getElementById('resumenGastos').style.display = tab === 'gastos' ? '' : 'none';
    document.getElementById('resumenIngresos').style.display = tab === 'ingresos' ? '' : 'none';
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
}

function abrirEditar(id, nombre) {
    document.getElementById('editId').value = id;
    document.getElementById('editNombre').value = nombre;
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
    const errEl  = document.getElementById('editError');
    if (!nombre) { errEl.textContent = 'El nombre no puede estar vacío.'; errEl.style.display = 'block'; return; }
    const fd = new FormData();
    fd.append('accion', 'editar');
    fd.append('id_categoria', document.getElementById('editId').value);
    fd.append('nombre', nombre);
    fetch('categorias.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) location.reload();
            else { errEl.textContent = data.error ?? 'Error al guardar.'; errEl.style.display = 'block'; }
        })
        .catch(() => { errEl.textContent = 'Error de conexión.'; errEl.style.display = 'block'; });
}

function eliminar(id, cantMovimientos, elementId) {
    if (cantMovimientos > 0) {
        alert('No se puede eliminar: esta categoría tiene ' + cantMovimientos + ' movimiento(s) asociado(s).');
        return;
    }
    if (!confirm('¿Eliminar esta categoría?')) return;
    const fd = new FormData();
    fd.append('accion', 'eliminar');
    fd.append('id_categoria', id);
    fetch('categorias.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) document.getElementById(elementId)?.remove();
            else alert(data.error ?? 'No se pudo eliminar.');
        })
        .catch(() => alert('Error de conexión.'));
}
</script>

<?php 
$extra_js = '<script src="js/categorias.js"></script>';
require_once 'includes/footer.php'; 
?>
