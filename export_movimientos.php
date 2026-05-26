<?php
require_once 'conexion.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = intval($_SESSION['usuario_id']);

// Obtener todos los movimientos del usuario
$stmt = $conn->prepare(
    "SELECT m.id_movimiento, m.tipo, m.monto, m.descripcion, m.fecha, c.nombre as categoria_nombre
     FROM movimientos m
     LEFT JOIN categorias c ON m.id_categoria = c.id_categoria
     WHERE m.id_usuario = ?
     ORDER BY m.fecha DESC, m.id_movimiento DESC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();

$movs = [];
while ($r = $res->fetch_assoc()) {
    $movs[] = [
        'id' => (int)$r['id_movimiento'],
        'tipo' => $r['tipo'] === 'ingreso' ? 'Ingreso' : 'Gasto',
        'categoria' => $r['categoria_nombre'] ?? 'Sin categoria',
        'monto' => (float)$r['monto'],
        'fecha' => $r['fecha'],
        'descripcion' => $r['descripcion'] ?? ''
    ];
}

echo json_encode($movs, JSON_UNESCAPED_UNICODE);
