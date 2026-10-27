<?php
$page = 'dashboard';
require_once 'conexion.php';
require_once 'includes/movimientos_handler.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'includes/periodo.php';

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
    
    // Totales del período actual
    $stmt_ingresos = $conn->prepare("SELECT SUM(monto) as total FROM movimientos WHERE id_usuario = ? AND tipo = 'ingreso' AND fecha BETWEEN ? AND ?");
    $stmt_ingresos->bind_param("iss", $user_id, $fecha_desde, $fecha_hasta);
    $stmt_ingresos->execute();
    $res_ing = $stmt_ingresos->get_result();
    if ($row = $res_ing->fetch_assoc()) {
        $total_ingresos = $row['total'] ?? 0;
    }

    $stmt_gastos = $conn->prepare("SELECT SUM(monto) as total FROM movimientos WHERE id_usuario = ? AND tipo = 'gasto' AND fecha BETWEEN ? AND ?");
    $stmt_gastos->bind_param("iss", $user_id, $fecha_desde, $fecha_hasta);
    $stmt_gastos->execute();
    $res_gas = $stmt_gastos->get_result();
    if ($row = $res_gas->fetch_assoc()) {
        $total_gastos = $row['total'] ?? 0;
    }

    // ── Gráfico de torta: gastos por categoría en el período ──────────────────────────────
    $stmt_torta = $conn->prepare(
        "SELECT c.nombre, SUM(m.monto) as total
         FROM movimientos m
         JOIN categorias c ON m.id_categoria = c.id_categoria
         WHERE m.id_usuario = ? AND m.tipo = 'gasto' AND m.fecha BETWEEN ? AND ?
         GROUP BY c.id_categoria, c.nombre
         ORDER BY total DESC
         LIMIT 8"
    );
    $stmt_torta->bind_param("iss", $user_id, $fecha_desde, $fecha_hasta);
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
            SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
            SUM(CASE WHEN tipo = 'gasto'   THEN monto ELSE 0 END) as total_gastos
         FROM movimientos
         WHERE id_usuario = ?
           AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
         GROUP BY mes
         ORDER BY mes ASC"
    );
    $stmt_barras->bind_param("i", $user_id);
    $stmt_barras->execute();
    $res_barras = $stmt_barras->get_result();
    while ($row = $res_barras->fetch_assoc()) {
        // Etiqueta en español (DATE_FORMAT %b depende del idioma del servidor MySQL)
        $barras_labels[]   = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][(int)substr($row['mes'], 5, 2) - 1] . ' ' . substr($row['mes'], 0, 4);
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
$js_torta_data     = json_encode($torta_data);
$js_barras_ing     = json_encode($barras_ingresos);
$js_barras_gas     = json_encode($barras_gastos);

// ── Recomendaciones IA simuladas (fallback si no hay API) ───────────────────
$balance_texto = $balance >= 0 ? "positivo" : "negativo";
$pct_gasto = $total_ingresos > 0 ? round(($total_gastos / $total_ingresos) * 100) : 0;
$recomendaciones_fallback = [
    ["icon" => "<img src=\"iconos/generales/bombilla.png\" alt=\"Bombilla\" class=\"icono-inline\">", "titulo" => "Balance actual", "texto" => "Tu balance es <strong>$balance_texto</strong>. " . ($balance >= 0 ? "¡Vas por buen camino!" : "Revisá tus gastos esta semana."), "tipo" => "info"],
    ["icon" => "<img src=\"iconos/generales/graficos.png\" alt=\"Gráfico\" class=\"icono-inline\">", "titulo" => "Ratio de gasto", "texto" => "Estás gastando el <strong>{$pct_gasto}%</strong> de tus ingresos. " . ($pct_gasto > 80 ? "Está muy alto, tratá de reducirlo." : "Buen control financiero."), "tipo" => $pct_gasto > 80 ? "alerta" : "ok"],
    ["icon" => "<img src=\"iconos/generales/altbilleteconalas.png\" alt=\"Dinero\" class=\"icono-inline\">", "titulo" => "Consejo de ahorro", "texto" => "Intentá apartar al menos el <strong>20%</strong> de tus ingresos como ahorro antes de gastar.", "tipo" => "consejo"],
    ["icon" => "<img src=\"iconos/generales/dianaconflecha.png\" alt=\"Objetivo\" class=\"icono-inline\">", "titulo" => "Objetivos", "texto" => "Crear objetivos de ahorro te ayuda a mantener el foco. ¡Revisá tus metas en la sección de Objetivos!", "tipo" => "consejo"],
];

$extra_css = '<link rel="stylesheet" href="css/pages/dashboard.css">';

require_once 'includes/header.php';

$etiquetas_periodo = ['semana' => 'Esta semana', 'quincena' => 'Esta quincena', 'mes' => 'Este mes'];
?>

<!-- BOTON FLOTANTE IA (el usuario puede ocultarlo con la cruz) -->
<div id="botonIAWrap" class="boton-ia-wrap">
    <button type="button" id="botonIA" class="boton-ia" onclick="toggleChat()" aria-label="Abrir asistente IA">
        <img src="iconos/generales/robot.png" alt="">
    </button>
    <button type="button" class="boton-ia-cerrar" onclick="ocultarBotonIA()" aria-label="Ocultar botón del asistente">&times;</button>
</div>

<!-- CHAT FLOTANTE -->
<div id="chatFlotante" class="chat-flotante oculto">
    <div class="chat-header">
        <span>Asistente IA</span>
        <button type="button" class="chat-cerrar" onclick="toggleChat()" aria-label="Cerrar chat"><img src="iconos/generales/flechaabajo.png" alt="" class="icono-boton"></button>
    </div>
    <div id="chat" class="chat"></div>
    <div class="input-container">
        <textarea id="pregunta" class="input-chat" placeholder="Escribí tu consulta..."></textarea>
        <button class="btn-enviar" onclick="preguntarIA()">Enviar</button>
    </div>
</div>

        <div class="page-header">
            <div class="page-header-texto">
                <h2 class="titulo-con-icono"><img src="iconos/generales/graficos.png" alt="" class="icono-titulo"> Dashboard</h2>
                <p class="page-subtitle"><?php echo ($etiquetas_periodo[$periodo_actual] ?? 'Período') . ' · ' . date('d/m/Y', strtotime($fecha_desde)) . ' — ' . date('d/m/Y', strtotime($fecha_hasta)); ?></p>
            </div>
        </div>

        <!-- CARDS PRINCIPALES -->
        <section class="cards cards-dashboard">
            <div class="card">
                <h4>Ingresos</h4>
                <p id="ingresos" data-ars="<?php echo (float)$total_ingresos; ?>">$<?php echo number_format($total_ingresos, 2, ',', '.'); ?></p>
            </div>
            <div class="card highlight">
                <h4>Balance</h4>
                <p id="balance" data-ars="<?php echo (float)$balance; ?>">$<?php echo number_format($balance, 2, ',', '.'); ?></p>
            </div>
            <div class="card">
                <h4>Gastos</h4>
                <p id="gastos" data-ars="<?php echo (float)$total_gastos; ?>">$<?php echo number_format($total_gastos, 2, ',', '.'); ?></p>
            </div>
        </section>

        <!-- CARDS SECUNDARIAS -->
        <section class="cards small cards-dashboard">
            <?php
                $nombre_cat_top = '-';
                if (!empty($torta_labels)) {
                    $nombre_cat_top = $torta_labels[0];
                }
            ?>
            <div class="card"><h4>% gasto</h4><p id="porcentaje"><?php echo $pct_gasto; ?>%</p></div>
            <div class="card"><h4>Mayor categoría</h4><p id="categoriaTop"><?php echo htmlspecialchars($nombre_cat_top); ?></p></div>
            <div class="card"><h4>Gasto mensual</h4><p id="gastoMensual" data-ars="<?php echo (float)$gasto_mensual; ?>">$<?php echo number_format($gasto_mensual, 2, ',', '.'); ?></p></div>
        </section>

        <!-- BOTONES (misma grilla que las tarjetas de arriba) -->
        <div class="dashboard-acciones">
            <button class="btn ingreso" onclick="abrirModal('ingreso')">+ Ingreso</button>
            <button class="btn gasto" onclick="abrirModal('gasto')">+ Gasto</button>
            <button class="btn objetivo" onclick="abrirModal('objetivo')">+ Objetivo</button>
        </div>

        <!-- GRAFICOS -->
        <section class="graficos-container">
            <!-- Gráfico de torta: gastos por categoría -->
            <div class="grafico-card">
                <div class="grafico-card-header">
                    <span class="grafico-icono"><img src="iconos/generales/graficotorta.png" alt="" class="icono-titulo"></span>
                    <div>
                        <h3 class="grafico-titulo">Gastos por Categoría</h3>
                        <p class="grafico-subtitulo">Distribución del período</p>
                    </div>
                </div>
                <div class="grafico-wrap">
                    <canvas id="graficoTorta" aria-label="Gráfico de gastos por categoría" role="img"></canvas>
                    <p class="grafico-empty" id="torta-empty" hidden>Sin gastos en este período.</p>
                </div>
            </div>

            <!-- Gráfico de barras: ingresos vs gastos por mes -->
            <div class="grafico-card">
                <div class="grafico-card-header">
                    <span class="grafico-icono"><img src="iconos/generales/graficobarra.png" alt="" class="icono-titulo"></span>
                    <div>
                        <h3 class="grafico-titulo">Ingresos vs Gastos</h3>
                        <p class="grafico-subtitulo">Comparativa de los últimos 6 meses</p>
                    </div>
                </div>
                <div class="grafico-wrap">
                    <canvas id="graficoBarras" aria-label="Gráfico de ingresos y gastos por mes" role="img"></canvas>
                    <p class="grafico-empty" id="barras-empty" hidden>Sin movimientos en los últimos 6 meses.</p>
                </div>
            </div>
        </section>

        <!-- GRID: Movimientos + IA -->
        <section class="grid">

            <!-- MOVIMIENTOS -->
            <div class="box movimientos-card">
                <div class="movimientos-header">
                    <h3 class="titulo-con-icono"><img src="iconos/generales/listaultmov1.png" alt="" class="icono-titulo"> Últimos movimientos</h3>
                    <a href="movimientos.php" class="ver-mas-link">Ver todos →</a>
                </div>

                <?php if (empty($ultimos_movimientos)): ?>
                    <div class="movimientos-empty">
                        <img src="iconos/generales/altbilleteconalas.png" alt="" class="movimientos-empty-icono">
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
                                $monto_fmt  = '$' . number_format($mv['monto'], 2, ',', '.');
                                $fecha_fmt  = date('d/m/Y', strtotime($mv['fecha']));
                            ?>
                            <li class="movimiento-item">
                                <span class="mov-icono <?php echo $clase_tipo; ?>"><?php echo $icono; ?></span>
                                <div class="mov-info">
                                    <span class="mov-desc"><?php echo htmlspecialchars($mv['descripcion']); ?></span>
                                    <span class="mov-cat"><?php echo htmlspecialchars($mv['categoria']); ?> · <?php echo $fecha_fmt; ?></span>
                                </div>
                                <span class="mov-monto <?php echo $clase_tipo; ?>" data-ars="<?php echo (float)$mv['monto']; ?>" data-prefijo="<?php echo $signo; ?>"><?php echo $signo . $monto_fmt; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- RECOMENDACIONES IA -->
            <div class="box ia-card">
                <div class="ia-header">
                    <div class="ia-titulo-wrap">
                        <h3 class="titulo-con-icono"><img src="iconos/generales/tick.png" alt="" class="icono-titulo"> Recomendaciones IA</h3>
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

                <a href="ia.php" class="ia-btn-chat"><img src="iconos/generales/robot.png" alt="" class="icono-boton"> Hablar con la IA →</a>
            </div>

        </section>

<?php
ob_start();
?>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.6.1/dist/chart.umd.min.js"></script>

<script>
// ── Datos desde PHP (siempre en ARS) ──────────────────────────────────────
const tortaLabels   = <?php echo json_encode($torta_labels, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
const tortaData     = <?php echo $js_torta_data; ?>;
const barrasLabels  = <?php echo json_encode($barras_labels, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
const barrasIng     = <?php echo $js_barras_ing; ?>;
const barrasGas     = <?php echo $js_barras_gas; ?>;

// ── Paleta de colores alineada con Trackify ────────────────────────────────
const paleta = ["#CFF27C", "#EA73F5", "#700353", "#084734", "#6EE7B7", "#A78BFA", "#F472B6", "#34D399"];

const graficos = { torta: null, barras: null };

// Montos compactos para los ejes: $1,2 M · $350 k · USD 900
function formatoCompacto(valor, moneda) {
    const prefijo = moneda === 'USD' ? 'USD ' : '$';
    return prefijo + new Intl.NumberFormat('es-AR', { notation: 'compact', maximumFractionDigits: 1 }).format(valor);
}

function convertirSerie(serie, moneda, tc) {
    return moneda === 'USD' && tc > 0 ? serie.map(v => v / tc) : serie.slice();
}

function dibujarGraficos(moneda, tc) {
    if (!window.Chart) return;
    const oscuro = document.documentElement.getAttribute('data-theme') === 'dark';
    const colorTexto = oscuro ? "#CBD5E1" : "#475569";
    const colorGrilla = oscuro ? "rgba(255, 255, 255, 0.10)" : "rgba(30, 27, 38, 0.08)";
    const formatoTooltip = v => ' ' + window.formatearMonto(moneda === 'USD' && tc > 0 ? v * tc : v, moneda, tc);

    Chart.defaults.font.family = "Inter, sans-serif";
    Chart.defaults.color = colorTexto;
    const esAngosto = window.innerWidth < 600;

    // ── Gráfico de Torta ──
    const canvasTorta = document.getElementById("graficoTorta");
    if (tortaData.length === 0) {
        document.getElementById("torta-empty").hidden = false;
        canvasTorta.hidden = true;
    } else {
        if (graficos.torta) graficos.torta.destroy();
        graficos.torta = new Chart(canvasTorta, {
            type: "doughnut",
            data: {
                labels: tortaLabels,
                datasets: [{
                    data: convertirSerie(tortaData, moneda, tc),
                    // En oscuro el bordó (#700353) se pierde contra el fondo: se usa una variante más clara
                    backgroundColor: paleta.slice(0, tortaLabels.length).map(c => oscuro && c === "#700353" ? "#C2389A" : c),
                    borderColor: oscuro ? "#1E1B26" : "#FFFFFF",
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "62%",
                layout: { padding: 4 },
                plugins: {
                    legend: {
                        position: esAngosto ? "bottom" : "right",
                        labels: { padding: 10, boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return `${formatoTooltip(ctx.parsed)} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // ── Gráfico de Barras ──
    const canvasBarras = document.getElementById("graficoBarras");
    if (barrasLabels.length === 0) {
        document.getElementById("barras-empty").hidden = false;
        canvasBarras.hidden = true;
    } else {
        if (graficos.barras) graficos.barras.destroy();
        graficos.barras = new Chart(canvasBarras, {
            type: "bar",
            data: {
                labels: barrasLabels,
                datasets: [
                    { label: "Ingresos", data: convertirSerie(barrasIng, moneda, tc), backgroundColor: "#CFF27C", borderRadius: 5, maxBarThickness: 28 },
                    { label: "Gastos", data: convertirSerie(barrasGas, moneda, tc), backgroundColor: oscuro ? "#EA73F5" : "#700353", borderRadius: 5, maxBarThickness: 28 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: { padding: 10, boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: { label: ctx => `${ctx.dataset.label}:${formatoTooltip(ctx.parsed.y)}` }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, maxRotation: 0, autoSkip: true }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: colorGrilla },
                        border: { display: false },
                        ticks: { font: { size: 10 }, maxTicksLimit: 5, callback: val => formatoCompacto(val, moneda) }
                    }
                }
            }
        });
    }
}

// Los gráficos se dibujan con la moneda elegida y se redibujan al cambiarla
document.addEventListener('trackify:moneda', e => dibujarGraficos(e.detail.moneda, e.detail.tc));
</script>
<script src="js/ia.js?v=4"></script>
<?php
$extra_js = ob_get_clean();
require_once 'includes/footer.php';
?>
