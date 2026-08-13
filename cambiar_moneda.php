<?php
require_once '../conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

$moneda = $_POST['moneda'] ?? '';
if (!in_array($moneda, ['ARS', 'USD'])) {
    echo json_encode(['ok' => false, 'error' => 'Moneda inválida']);
    exit();
}

$user_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("UPDATE usuarios SET moneda_principal = ? WHERE id_usuario = ?");
$stmt->bind_param("si", $moneda, $user_id);

if ($stmt->execute()) {
    $_SESSION['moneda'] = $moneda;
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar']);
}
?>
