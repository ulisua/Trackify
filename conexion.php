<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "usuarios";

$conn = new mysqli($servername, $username, $password, $dbname);

// --- MIGRACIONES AUTOMÁTICAS ---
$conn->query("ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS estado ENUM('activo', 'inactivo', 'logrado') DEFAULT 'activo'");

// Insertar categorías por defecto
$res = $conn->query("SELECT COUNT(*) as c FROM categorias");
if ($res) {
    $row = $res->fetch_assoc();
    if ($row['c'] <= 5) {
        $cats_ingreso = ["Sueldo", "Préstamo recibido", "Reintegro", "Ventas", "Inversiones", "Intereses", "Regalos", "Devoluciones", "Freelance / trabajos extra", "Becas / subsidios", "Otros ingresos"];
        $cats_gasto = ["Alimentos", "Transporte", "Vivienda", "Salud", "Educación", "Entretenimiento", "Compras personales", "Deudas", "Impuestos", "Mascotas", "Suscripciones", "Regalos / donaciones", "Ropa", "Tecnología", "Viajes", "Otros gastos"];
        
        $stmt_ins = $conn->prepare("INSERT IGNORE INTO categorias (nombre, tipo) VALUES (?, ?)");
        $tipo_ingreso = 'ingreso';
        foreach($cats_ingreso as $c) {
            $stmt_ins->bind_param("ss", $c, $tipo_ingreso);
            $stmt_ins->execute();
        }
        $tipo_gasto = 'gasto';
        foreach($cats_gasto as $c) {
            $stmt_ins->bind_param("ss", $c, $tipo_gasto);
            $stmt_ins->execute();
        }
    }
}
?>