<?php
$page = 'dashboard';
require_once 'conexion.php';
require_once 'includes/movimientos_handler.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Procesar el guardado del objetivo
if(isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['usuario_id'];

    if(isset($_POST['form_type']) && $_POST['form_type'] === 'objetivo') {
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
    
    // ── Gasto mensual actual ───────────────────────────────────────────────
    $gasto_mensual = 0;
    $stmt_mes = $conn->prepare(
        "SELECT SUM(monto) as total FROM movimientos 
         WHERE id_usuario = ? AND tipo = 'gasto' 
         AND MONTH(fecha) = MONTH(CURRENT_DATE()) 
         AND YEAR(fecha) = YEAR(CURRENT_DATE())"
    );
    $stmt_mes->bind_param("i", $user_id);
    $stmt_mes->execute();
    $res_mes = $stmt_mes->get_result();
    if ($row = $res_mes->fetch_assoc()) {
        $gasto_mensual = $row['total'] ?? 0;
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
    ["icon" => "<img src=\"iconos/generales/bombilla.png\" alt=\"Bombilla\" class=\"icono-inline\">", "titulo" => "Balance actual", "texto" => "Tu balance es <strong>$balance_texto</strong>. " . ($balance >= 0 ? "¡Vas por buen camino!" : "Revisá tus gastos esta semana."), "tipo" => "info"],
    ["icon" => "<img src=\"iconos/generales/graficos.png\" alt=\"Gráfico\" class=\"icono-inline\">", "titulo" => "Ratio de gasto", "texto" => "Estás gastando el <strong>{$pct_gasto}%</strong> de tus ingresos. " . ($pct_gasto > 80 ? "⚠️ Está muy alto, tratá de reducirlo." : "Buen control financiero."), "tipo" => $pct_gasto > 80 ? "alerta" : "ok"],
    ["icon" => "<img src=\"iconos/generales/altbilleteconalas.png\" alt=\"Dinero\" class=\"icono-inline\">", "titulo" => "Consejo de ahorro", "texto" => "Intentá apartar al menos el <strong>20%</strong> de tus ingresos como ahorro antes de gastar.", "tipo" => "consejo"],
    ["icon" => "<img src=\"iconos/generales/dianaconflecha.png\" alt=\"Objetivo\" class=\"icono-inline\">", "titulo" => "Objetivos", "texto" => "Crear objetivos de ahorro te ayuda a mantener el foco. ¡Revisá tus metas en la sección de Objetivos!", "tipo" => "consejo"],
];

$extra_css = '<link rel="stylesheet" href="css/pages/dashboard.css">';

require_once 'includes/header.php';
?>

<!-- BOTON FLOTANTE IA -->
<div id="botonIA" class="boton-ia" onclick="toggleChat()"><img src="iconos/generales/robot.png" alt="Chat" style="width: 24px; height: 24px; object-fit: contain;"></div>

<!-- CHAT FLOTANTE -->
<div id="chatFlotante" class="chat-flotante oculto">
    <div class="chat-header">
        <span>Asistente IA</span>
        <span onclick="toggleChat()" style="cursor:pointer;"><img src="iconos/generales/flechaabajo.png" alt="Cerrar" class="icono-boton"></span>
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
            <?php 
                $nombre_cat_top = '-';
                if (!empty($torta_labels)) {
                    $nombre_cat_top = $torta_labels[0];
                }
            ?>
            <div class="card">% gasto <p id="porcentaje"><?php echo $pct_gasto; ?>%</p></div>
            <div class="card">Mayor categoría <p id="categoriaTop"><?php echo htmlspecialchars($nombre_cat_top); ?></p></div>
            <div class="card">Gasto mensual <p id="gastoMensual">$<?php echo number_format($gasto_mensual, 0, ',', '.'); ?></p></div>
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
                    <span class="grafico-icono"><img src="iconos/generales/graficotorta.png" alt="Torta" class="icono-titulo"></span>
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
                    <span class="grafico-icono"><img src="iconos/generales/graficobarra.png" alt="Barras" class="icono-titulo"></span>
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
                    <h3 class="titulo-con-icono"><img src="iconos/generales/listaultmov1.png" alt="Movimientos" class="icono-titulo"> Últimos movimientos</h3>
                    <a href="ingresos.php" class="ver-mas-link">Ver todos →</a>
                </div>

                <?php if (empty($ultimos_movimientos)): ?>
                    <div class="movimientos-empty">
                        <span><img src="iconos/generales/altbilleteconalas.png" alt="Sin movimientos" style="width: 48px; height: 48px;"></span>
                        <p>Aún no registraste ningún movimiento.</p>
                    </div>
                <?php else: ?>
                    <ul class="movimientos-lista" id="movimientos">
                        <?php foreach ($ultimos_movimientos as $mv): ?>
                            <?php
                                $es_ingreso = $mv['tipo'] === 'ingreso';
                                $icono      = $es_ingreso ? '<img src="iconos/generales/altbilleteconalas.png" alt="Ingreso" class="icono-inline">' : '<img src="iconos/gasto/otrosgastos.png" alt="Gasto" class="icono-inline">';
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
                        <h3 class="titulo-con-icono"><img src="iconos/generales/tick.png" alt="Recomendaciones" class="icono-titulo"> Recomendaciones IA</h3>
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

                <a href="ia.php" class="ia-btn-chat"><img src="iconos/generales/robot.png" alt="Chat" class="icono-boton"> Hablar con la IA →</a>
            </div>

        </section>

<?php
ob_start();
?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ── Datos desde PHP (reales de la BD) ─────────────────────────────────────
const tortaLabels   = <?php echo $js_torta_labels; ?>;
const tortaData     = <?php echo $js_torta_data; ?>;
const barrasLabels  = <?php echo $js_barras_labels; ?>;
const barrasIng     = <?php echo $js_barras_ing; ?>;
const barrasGas     = <?php echo $js_barras_gas; ?>;

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
const isDarkTheme = document.documentElement.getAttribute('data-theme') === 'dark';
Chart.defaults.font.family = "Inter, sans-serif";
Chart.defaults.color = isDarkTheme ? "#94A3B8" : "#64748B";

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
                borderColor: isDarkTheme ? "#1E1B26" : "#F8FAFC",
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
                    grid: { color: isDarkTheme ? "rgba(255, 255, 255, 0.08)" : "rgba(0,0,0,0.05)" },
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
<?php
$extra_js = ob_get_clean();
require_once 'includes/footer.php';
?>