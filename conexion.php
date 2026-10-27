<?php
require_once __DIR__ . '/env.php';

$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "trackify";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    error_log('Trackify: error de conexión a MySQL: ' . $e->getMessage());
    http_response_code(500);
    die("No se pudo conectar con la base de datos. Verificá que MySQL esté iniciado.");
}
$conn->set_charset('utf8mb4');

require_once __DIR__ . '/includes/categorias_meta.php';
require_once __DIR__ . '/includes/seguridad.php';

// --- MIGRACIONES AUTOMÁTICAS (solo aditivas) ---
$conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS uuid VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) NOT NULL DEFAULT 0");
$conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS codigo_verificacion VARCHAR(10) DEFAULT NULL");
$conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS codigo_expiracion DATETIME DEFAULT NULL");
$conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS Foto_perfil VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS moneda_principal ENUM('ARS','USD') NOT NULL DEFAULT 'ARS'");
$conn->query("ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS estado ENUM('activo', 'inactivo', 'logrado') DEFAULT 'activo'");
$conn->query("ALTER TABLE categorias ADD COLUMN IF NOT EXISTS icono VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE categorias ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT NULL");
// Categorías por usuario: NULL = categoría predeterminada del sistema (solo lectura),
// un id = categoría personal de ese usuario.
$conn->query("ALTER TABLE categorias ADD COLUMN IF NOT EXISTS id_usuario INT(11) DEFAULT NULL");
$conn->query("ALTER TABLE categorias ADD INDEX IF NOT EXISTS idx_categorias_usuario (id_usuario)");

// Los montos de las metas se guardaban sin centavos (DECIMAL(10,0)). Se amplía una sola vez.
$res_escala = $conn->query("SELECT NUMERIC_SCALE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metas_ahorro' AND COLUMN_NAME = 'monto_objetivo'");
if ($res_escala && ($fila_escala = $res_escala->fetch_assoc()) && (int)$fila_escala['NUMERIC_SCALE'] === 0) {
    $conn->query("ALTER TABLE metas_ahorro MODIFY monto_objetivo DECIMAL(12,2) NOT NULL, MODIFY monto_actual DECIMAL(12,2) NOT NULL DEFAULT 0");
}

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
