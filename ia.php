<?php
$page = 'ia';
require_once 'conexion.php';

// ── API del asistente: POST JSON { pregunta, historial? } → { respuesta } o { error } ──
// La usan el chat de esta página y el chat flotante del dashboard (js/ia.js).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    function responderIA($datos, $codigo = 200) {
        http_response_code($codigo);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (!isset($_SESSION['usuario_id'])) {
        responderIA(['error' => 'Tu sesión venció. Volvé a iniciar sesión.'], 401);
    }
    $api_key = env('GROQ_API_KEY');
    if (!$api_key) {
        responderIA(['error' => 'El asistente IA no está configurado: falta GROQ_API_KEY en el archivo .env.'], 503);
    }

    $entrada  = json_decode(file_get_contents('php://input'), true) ?: [];
    $pregunta = trim((string)($entrada['pregunta'] ?? ''));
    if ($pregunta === '' || mb_strlen($pregunta) > 1000) {
        responderIA(['error' => 'Escribí una pregunta (máximo 1000 caracteres).'], 422);
    }

    // Límite simple por sesión: 1 consulta cada 2 segundos
    if (microtime(true) - ($_SESSION['ia_ultima_consulta'] ?? 0) < 2) {
        responderIA(['error' => 'Esperá un momento antes de volver a preguntar.'], 429);
    }
    $_SESSION['ia_ultima_consulta'] = microtime(true);

    $contexto = contextoFinancieroIA($conn, (int)$_SESSION['usuario_id']);
    $mensajes = [[
        'role' => 'system',
        'content' => "Sos el asistente financiero de Trackify, una app argentina de finanzas personales. "
            . "Respondé en español rioplatense, claro y breve (máximo 180 palabras), con consejos concretos y accionables. "
            . "Basate en los datos reales del usuario que figuran abajo; si un dato no está, decilo en lugar de inventarlo. "
            . "Los montos están en pesos argentinos (ARS). "
            . "Formato: texto plano en párrafos cortos o listas con guiones; podés usar **negrita** para destacar cifras. "
            . "No uses títulos con #, tablas, bloques de código ni emojis. "
            . "No des recomendaciones de inversión en activos específicos.\n\n"
            . "DATOS DEL USUARIO (al " . date('d/m/Y') . "):\n" . $contexto,
    ]];
    // Últimos turnos de la conversación, para que pueda responder repreguntas
    foreach (array_slice(is_array($entrada['historial'] ?? null) ? $entrada['historial'] : [], -6) as $turno) {
        $rol = ($turno['rol'] ?? '') === 'ia' ? 'assistant' : 'user';
        $texto = mb_substr(trim((string)($turno['texto'] ?? '')), 0, 1500);
        if ($texto !== '') $mensajes[] = ['role' => $rol, 'content' => $texto];
    }
    $mensajes[] = ['role' => 'user', 'content' => $pregunta];

    $modelo = env('GROQ_MODEL', 'openai/gpt-oss-120b');
    $pedido = [
        'model'       => $modelo,
        'messages'    => $mensajes,
        'temperature' => 0.4,
        'max_tokens'  => 1500,
    ];
    // Los modelos gpt-oss razonan antes de responder: con esfuerzo bajo contestan rápido
    // y no gastan los tokens en el razonamiento (si no, la respuesta puede llegar vacía)
    if (str_starts_with($modelo, 'openai/gpt-oss')) {
        $pedido['reasoning_effort'] = 'low';
    }

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 45,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $api_key],
        CURLOPT_POSTFIELDS     => json_encode($pedido, JSON_UNESCAPED_UNICODE),
    ]);
    $cuerpo = curl_exec($ch);
    $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error_curl = curl_error($ch);
    curl_close($ch);

    $datos = $cuerpo ? json_decode($cuerpo, true) : null;
    $respuesta = trim($datos['choices'][0]['message']['content'] ?? '');
    if ($respuesta === '') {
        error_log("Trackify: error de Groq (HTTP $estado) $error_curl " . mb_substr((string)$cuerpo, 0, 500));
        $codigo_groq = $datos['error']['code'] ?? '';
        responderIA(['error' => match (true) {
            $estado === 429                     => 'El asistente recibió muchas consultas. Probá de nuevo en un minuto.',
            $estado === 401                     => 'La GROQ_API_KEY del archivo .env no es válida.',
            $codigo_groq === 'model_not_found'  => "El modelo \"$modelo\" no está disponible en Groq. Cambiá GROQ_MODEL en .env.",
            default                             => 'No pude responder en este momento. Intentá de nuevo.',
        }], 502);
    }
    responderIA(['respuesta' => $respuesta]);
}

// Resumen en texto de las finanzas del usuario para darle contexto al modelo
function contextoFinancieroIA($conn, $user_id) {
    $fmt = fn($n) => '$' . number_format((float)$n, 2, ',', '.');
    $lineas = [];

    // Totales de los últimos 3 meses
    $meses = $conn->filas("SELECT " . $conn->sqlMes('fecha') . " AS mes,
            SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) AS ingresos,
            SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) AS gastos
        FROM movimientos WHERE id_usuario = ? AND fecha >= ?
        GROUP BY mes ORDER BY mes DESC", [$user_id, inicioDeMes(2)]);
    if (!$meses) {
        $lineas[] = "- No registró movimientos en los últimos 3 meses.";
    }
    foreach ($meses as $m) {
        $lineas[] = "- {$m['mes']}: ingresos " . $fmt($m['ingresos']) . ", gastos " . $fmt($m['gastos']) . ", balance " . $fmt($m['ingresos'] - $m['gastos']);
    }

    // Gastos por categoría del mes actual
    $categorias = $conn->filas("SELECT c.nombre, SUM(m.monto) AS total FROM movimientos m
        JOIN categorias c ON c.id_categoria = m.id_categoria
        WHERE m.id_usuario = ? AND m.tipo = 'gasto' AND m.fecha >= ?
        GROUP BY c.id_categoria, c.nombre ORDER BY total DESC LIMIT 8", [$user_id, inicioDeMes(0)]);
    if ($categorias) {
        $lineas[] = "Gastos de este mes por categoría: " . implode('; ', array_map(fn($c) => $c['nombre'] . ' ' . $fmt($c['total']), $categorias));
    }

    // Objetivos de ahorro
    $metas = $conn->filas("SELECT nombre_meta, monto_objetivo, monto_actual, fecha_limite, estado FROM metas_ahorro WHERE id_usuario = ? ORDER BY fecha_limite LIMIT 10", [$user_id]);
    $lineas[] = $metas ? "Objetivos de ahorro:" : "No tiene objetivos de ahorro.";
    foreach ($metas as $m) {
        $pct = $m['monto_objetivo'] > 0 ? round($m['monto_actual'] / $m['monto_objetivo'] * 100) : 0;
        $lineas[] = "- {$m['nombre_meta']} ({$m['estado']}): " . $fmt($m['monto_actual']) . " de " . $fmt($m['monto_objetivo']) . " ({$pct}%), vence {$m['fecha_limite']}";
    }

    return implode("\n", $lineas);
}

$extra_css = '<link rel="stylesheet" href="css/pages/ia.css">';
require_once 'includes/header.php';
?>

            <h2 class="titulo-con-icono"><img src="iconos/generales/robot.png" alt="IA" class="icono-titulo"> Preguntale a la IA</h2>

            <div class="ia-chat-shell">
                <div class="chat-container">
                    <div class="glow-orb orb-1"></div>
                    <div class="glow-orb orb-2"></div>

                    <div class="chat-content">
                        <div id="chat" class="chat"></div>
                        <div class="input-container">
                            <textarea id="pregunta" class="input-chat"
                                placeholder="Escribí tu consulta financiera..."></textarea>
                            <button class="btn-enviar" onclick="preguntarIA()">Enviar</button>
                        </div>
                    </div>
                </div>
            </div>

<?php
$extra_js = '<script src="js/ia.js?v=7"></script>';
require_once 'includes/footer.php';
