<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "usuarios";

$conn = new mysqli($servername, $username, $password, $dbname);
require_once __DIR__ . '/includes/categorias_meta.php';

// --- MIGRACIONES AUTOMÁTICAS ---
$conn->query("ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS estado ENUM('activo', 'inactivo', 'logrado') DEFAULT 'activo'");
$conn->query("ALTER TABLE categorias ADD COLUMN IF NOT EXISTS icono VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE categorias ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT NULL");

// Insertar categorías por defecto
$res = $conn->query("SELECT COUNT(*) as c FROM categorias");
if ($res) {
    $row = $res->fetch_assoc();
    if ($row['c'] <= 5) {
        $cats_ingreso = ["Sueldo", "Préstamo recibido", "Reintegro", "Ventas", "Inversiones", "Intereses", "Regalos", "Devoluciones", "Freelance / trabajos extra", "Becas / subsidios", "Otros ingresos"];
        $cats_gasto = ["Alimentos", "Transporte", "Vivienda", "Salud", "Educación", "Entretenimiento", "Compras personales", "Deudas", "Impuestos", "Mascotas", "Suscripciones", "Regalos / donaciones", "Ropa", "Tecnología", "Viajes", "Otros gastos"];
        
        $stmt_ins = $conn->prepare("INSERT IGNORE INTO categorias (nombre, tipo, icono, color) VALUES (?, ?, ?, ?)");
        $tipo_ingreso = 'ingreso';
        foreach ($cats_ingreso as $c) {
            $meta = obtenerMetaCategoriaPorNombre($c, $tipo_ingreso);
            $stmt_ins->bind_param("ssss", $c, $tipo_ingreso, $meta['icono'], $meta['color']);
            $stmt_ins->execute();
        }
        $tipo_gasto = 'gasto';
        foreach ($cats_gasto as $c) {
            $meta = obtenerMetaCategoriaPorNombre($c, $tipo_gasto);
            $stmt_ins->bind_param("ssss", $c, $tipo_gasto, $meta['icono'], $meta['color']);
            $stmt_ins->execute();
        }
    }
}
?>