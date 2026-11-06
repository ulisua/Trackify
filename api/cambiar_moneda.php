<?php
// api/cambiar_moneda.php
// Guarda la moneda principal (ARS/USD) elegida en el selector del encabezado (js/moneda.js).
// Es el único lugar donde se cambia la moneda. El token CSRF llega en la cabecera X-CSRF-Token
// y lo valida conexion.php (includes/seguridad.php).

require_once dirname(__DIR__) . '/conexion.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderMoneda(int $http, array $datos): void
{
    http_response_code($http);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responderMoneda(405, ['ok' => false, 'error' => 'Método no permitido.']);
}
if (empty($_SESSION['usuario_id'])) {
    responderMoneda(401, ['ok' => false, 'error' => 'No autenticado.']);
}

$moneda = $_POST['moneda'] ?? '';
if (!in_array($moneda, ['ARS', 'USD'], true)) {
    responderMoneda(422, ['ok' => false, 'error' => 'Moneda inválida.']);
}

$user_id = (int)$_SESSION['usuario_id'];
$stmt = $conn->prepare("UPDATE usuarios SET moneda_principal = ? WHERE id_usuario = ?");
$stmt->bind_param("si", $moneda, $user_id);

if (!$stmt->execute()) {
    responderMoneda(500, ['ok' => false, 'error' => 'Error al guardar.']);
}
$_SESSION['moneda'] = $moneda;
responderMoneda(200, ['ok' => true]);
