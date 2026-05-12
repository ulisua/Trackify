<?php
$page = 'dashboard';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

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
// Datos para gráficos
$torta_labels = [];
$torta_data   = [];
$barras_labels   = [];
$barras_ingresos = [];
$barras_gastos   = [];
$ultimos_movimientos = [];

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

    // ── Gráfico de torta: gastos por categoría ──────────────────────────────
    $stmt_torta = $conn->prepare(
        "SELECT c.nombre, SUM(m.monto) as total
         FROM movimientos m
         JOIN categorias c ON m.id_categoria = c.id_categoria
         WHERE m.id_usuario = ? AND m.tipo = 'gasto'
         GROUP BY c.id_categoria, c.nombre
         ORDER BY total DESC
         LIMIT 8"
    );
    $stmt_torta->bind_param("i", $user_id);
    $stmt_torta->execute();
    $res_torta = $stmt_torta->get_result();
    while ($row = $res_torta->fetch_assoc()) {
        $torta_labels[] = $row['nombre'];
        $torta_data[]   = (float)$row['total'];
    }

    // ── Gráfico de barras: ingresos vs gastos por mes (últimos 6 meses) ─────
    $stmt_barras = $conn->prepare(
        "SELECT
            DATE_FORMAT(fecha, '%Y-%m') as mes,
            DATE_FORMAT(fecha, '%b %Y') as mes_label,
            SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
            SUM(CASE WHEN tipo = 'gasto'   THEN monto ELSE 0 END) as total_gastos
         FROM movimientos
         WHERE id_usuario = ?
           AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY mes, mes_label
         ORDER BY mes ASC"
    );
    $stmt_barras->bind_param("i", $user_id);
    $stmt_barras->execute();
    $res_barras = $stmt_barras->get_result();
    while ($row = $res_barras->fetch_assoc()) {
        $barras_labels[]   = ucfirst($row['mes_label']);
        $barras_ingresos[] = (float)$row['total_ingresos'];
        $barras_gastos[]   = (float)$row['total_gastos'];
    }

    // ── Últimos 8 movimientos para la lista del dashboard ───────────────────
    $stmt_ultimos = $conn->prepare(
        "SELECT m.descripcion, m.monto, m.tipo, m.fecha, c.nombre as categoria
         FROM movimientos m
         JOIN categorias c ON m.id_categoria = c.id_categoria
         WHERE m.id_usuario = ?
         ORDER BY m.fecha DESC, m.id_movimiento DESC
         LIMIT 8"
    );
    $stmt_ultimos->bind_param("i", $user_id);
    $stmt_ultimos->execute();
    $res_ultimos = $stmt_ultimos->get_result();
    while ($row = $res_ultimos->fetch_assoc()) {
        $ultimos_movimientos[] = $row;
    }
}
$balance = $total_ingresos - $total_gastos;

// Codificar los datos para Chart.js
$js_torta_labels   = json_encode($torta_labels);
$js_torta_data     = json_encode($torta_data);
$js_barras_labels  = json_encode($barras_labels);
$js_barras_ing     = json_encode($barras_ingresos);
$js_barras_gas     = json_encode($barras_gastos);

// ── Recomendaciones IA simuladas (fallback si no hay API) ───────────────────
$balance_texto = $balance >= 0 ? "positivo" : "negativo";
$pct_gasto = $total_ingresos > 0 ? round(($total_gastos / $total_ingresos) * 100) : 0;
$recomendaciones_fallback = [
    ["icon" => "💡", "titulo" => "Balance actual", "texto" => "Tu balance es <strong>$balance_texto</strong>. " . ($balance >= 0 ? "¡Vas por buen camino!" : "Revisá tus gastos esta semana."), "tipo" => "info"],
    ["icon" => "📊", "titulo" => "Ratio de gasto", "texto" => "Estás gastando el <strong>{$pct_gasto}%</strong> de tus ingresos. " . ($pct_gasto > 80 ? "⚠️ Está muy alto, tratá de reducirlo." : "Buen control financiero."), "tipo" => $pct_gasto > 80 ? "alerta" : "ok"],
    ["icon" => "💰", "titulo" => "Consejo de ahorro", "texto" => "Intentá apartar al menos el <strong>20%</strong> de tus ingresos como ahorro antes de gastar.", "tipo" => "consejo"],
    ["icon" => "🎯", "titulo" => "Objetivos", "texto" => "Crear objetivos de ahorro te ayuda a mantener el foco. ¡Revisá tus metas en la sección de Objetivos!", "tipo" => "consejo"],
];

$extra_css = '';

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

        <!-- BOTONES -->
        <div class="acciones">
            <button class="btn ingreso" onclick="abrirModal('ingreso')">+ Ingreso</button>
            <button class="btn gasto" onclick="abrirModal('gasto')">+ Gasto</button>
            <button class="btn objetivo" onclick="abrirModal('objetivo')">+ Objetivo</button>
        </div>

        <!-- GRAFICOS -->
        <section class="graficos-container">
            <!-- Gráfico de torta: gastos por categoría -->
            <div class="grafico-card">
                <div class="grafico-card-header">
                    <span class="grafico-icono">🍕</span>
                    <div>
                        <h3 class="grafico-titulo">Gastos por Categoría</h3>
                        <p class="grafico-subtitulo">Distribución total de egresos</p>
                    </div>
                </div>
                <div class="grafico-wrap">
                    <canvas id="graficoTorta"></canvas>
                    <p class="grafico-empty" id="torta-empty" style="display:none;">Sin gastos registrados aún.</p>
                </div>
            </div>

            <!-- Gráfico de barras: ingresos vs gastos por mes -->
            <div class="grafico-card">
                <div class="grafico-card-header">
                    <span class="grafico-icono">📊</span>
                    <div>
                        <h3 class="grafico-titulo">Ingresos vs Gastos</h3>
                        <p class="grafico-subtitulo">Comparativa de los últimos 6 meses</p>
                    </div>
                </div>
                <div class="grafico-wrap">
                    <canvas id="graficoBarras"></canvas>
                    <p class="grafico-empty" id="barras-empty" style="display:none;">Sin movimientos en los últimos 6 meses.</p>
                </div>
            </div>
        </section>

        <!-- GRID: Movimientos + IA -->
        <section class="grid">

            <!-- MOVIMIENTOS -->
            <div class="box movimientos-card">
                <div class="movimientos-header">
                    <h3>📋 Últimos movimientos</h3>
                    <a href="ingresos.php" class="ver-mas-link">Ver todos →</a>
                </div>

                <?php if (empty($ultimos_movimientos)): ?>
                    <div class="movimientos-empty">
                        <span>🪙</span>
                        <p>Aún no registraste ningún movimiento.</p>
                    </div>
                <?php else: ?>
                    <ul class="movimientos-lista" id="movimientos">
                        <?php foreach ($ultimos_movimientos as $mv): ?>
                            <?php
                                $es_ingreso = $mv['tipo'] === 'ingreso';
                                $icono      = $es_ingreso ? '💰' : '💸';
                                $clase_tipo = $es_ingreso ? 'mov-ingreso' : 'mov-gasto';
                                $signo      = $es_ingreso ? '+' : '-';
                                $monto_fmt  = '$' . number_format($mv['monto'], 0, ',', '.');
                                $fecha_fmt  = date('d/m/Y', strtotime($mv['fecha']));
                            ?>
                            <li class="movimiento-item">
                                <span class="mov-icono <?php echo $clase_tipo; ?>"><?php echo $icono; ?></span>
                                <div class="mov-info">
                                    <span class="mov-desc"><?php echo htmlspecialchars($mv['descripcion']); ?></span>
                                    <span class="mov-cat"><?php echo htmlspecialchars($mv['categoria']); ?> · <?php echo $fecha_fmt; ?></span>
                                </div>
                                <span class="mov-monto <?php echo $clase_tipo; ?>"><?php echo $signo . $monto_fmt; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- RECOMENDACIONES IA -->
            <div class="box ia-card">
                <div class="ia-header">
                    <div class="ia-titulo-wrap">
                        <h3>🤖 Recomendaciones IA</h3>
                        <span class="ia-badge ia-badge-simulacion">Modo análisis</span>
                    </div>
                    <p class="ia-subtitulo">Análisis basado en tus finanzas actuales</p>
                </div>

                <ul class="ia-lista">
                    <?php foreach ($recomendaciones_fallback as $rec): ?>
                        <li class="ia-item ia-tipo-<?php echo $rec['tipo']; ?>">
                            <span class="ia-item-icono"><?php echo $rec['icon']; ?></span>
                            <div class="ia-item-body">
                                <strong class="ia-item-titulo"><?php echo $rec['titulo']; ?></strong>
                                <p class="ia-item-texto"><?php echo $rec['texto']; ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <a href="ia.php" class="ia-btn-chat">💬 Hablar con la IA →</a>
            </div>

        </section>

    </main>
</div>

<?php
$extra_js = '
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ── Datos desde PHP (reales de la BD) ─────────────────────────────────────
const tortaLabels   = ' . $js_torta_labels . ';
const tortaData     = ' . $js_torta_data . ';
const barrasLabels  = ' . $js_barras_labels . ';
const barrasIng     = ' . $js_barras_ing . ';
const barrasGas     = ' . $js_barras_gas . ';

// ── Paleta de colores alineada con Trackify ────────────────────────────────
const paleta = [
    "#CFF27C",  // verde lima
    "#EA73F5",  // violeta/fucsia
    "#700353",  // fucsia oscuro
    "#084734",  // verde oscuro
    "#6EE7B7",  // menta
    "#A78BFA",  // lila
    "#F472B6",  // rosa
    "#34D399",  // esmeralda
];

const paletaBordes = paleta.map(c => c + "CC");

// ── Opciones comunes ───────────────────────────────────────────────────────
Chart.defaults.font.family = "Inter, sans-serif";
Chart.defaults.color = "#64748B";

// ── Gráfico de Torta ────────────────────────────────────────────────────────
const ctxTorta = document.getElementById("graficoTorta").getContext("2d");
if (tortaData.length === 0) {
    document.getElementById("torta-empty").style.display = "block";
    document.getElementById("graficoTorta").style.display = "none";
} else {
    new Chart(ctxTorta, {
        type: "doughnut",
        data: {
            labels: tortaLabels,
            datasets: [{
                data: tortaData,
                backgroundColor: paleta.slice(0, tortaLabels.length),
                borderColor: "#F8FAFC",
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: "60%",
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        padding: 14,
                        boxWidth: 12,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const val = ctx.parsed;
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = ((val / total) * 100).toFixed(1);
                            return ` $${val.toLocaleString("es-AR")} (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}

// ── Gráfico de Barras ───────────────────────────────────────────────────────
const ctxBarras = document.getElementById("graficoBarras").getContext("2d");
if (barrasLabels.length === 0) {
    document.getElementById("barras-empty").style.display = "block";
    document.getElementById("graficoBarras").style.display = "none";
} else {
    new Chart(ctxBarras, {
        type: "bar",
        data: {
            labels: barrasLabels,
            datasets: [
                {
                    label: "Ingresos",
                    data: barrasIng,
                    backgroundColor: "rgba(207, 242, 124, 0.85)",  // verde lima
                    borderColor: "#CFF27C",
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                },
                {
                    label: "Gastos",
                    data: barrasGas,
                    backgroundColor: "rgba(112, 3, 83, 0.85)",    // fucsia
                    borderColor: "#700353",
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: "bottom",
                    labels: { padding: 16, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` $${ctx.parsed.y.toLocaleString("es-AR")}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 12 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: "rgba(0,0,0,0.05)" },
                    ticks: {
                        font: { size: 11 },
                        callback: val => "$" + val.toLocaleString("es-AR")
                    }
                }
            }
        }
    });
}
</script>
<script src="js/ia.js?v=2"></script>
';
require_once 'includes/footer.php';
?>