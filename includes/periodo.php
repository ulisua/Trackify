<?php
// includes/periodo.php
// Centraliza el cálculo de períodos y la inicialización de moneda para todo Trackify

if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../conexion.php';

// Inicializar moneda del usuario desde BD si está en sesión
if (isset($_SESSION['usuario_id']) && !isset($_SESSION['moneda'])) {
    if (isset($conn) && $conn instanceof mysqli) {
        $stmt_m = $conn->prepare("SELECT moneda_principal FROM usuarios WHERE id_usuario = ?");
        if ($stmt_m) {
            $stmt_m->bind_param("i", $_SESSION['usuario_id']);
            $stmt_m->execute();
            $res_m = $stmt_m->get_result();
            if ($row_m = $res_m->fetch_assoc()) {
                $_SESSION['moneda'] = $row_m['moneda_principal'] ?? 'ARS';
            }
        }
    }
}
$moneda_actual = $_SESSION['moneda'] ?? 'ARS';

// Validar y fijar período
$periodos_validos = ['semana', 'quincena', 'mes'];
if (isset($_GET['periodo']) && in_array($_GET['periodo'], $periodos_validos)) {
    $_SESSION['periodo'] = $_GET['periodo'];
}
$periodo_actual = $_SESSION['periodo'] ?? 'mes';

switch ($periodo_actual) {
    case 'semana':
        $fecha_desde = date('Y-m-d', strtotime('monday this week'));
        $fecha_hasta = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'quincena':
        $dia = intval(date('j'));
        $fecha_desde = $dia <= 15 ? date('Y-m-01') : date('Y-m-16');
        $fecha_hasta = $dia <= 15 ? date('Y-m-15') : date('Y-m-t');
        break;
    case 'mes':
    default:
        $fecha_desde = date('Y-m-01');
        $fecha_hasta = date('Y-m-t');
        break;
}
