<?php
require_once __DIR__ . '/env.php';

try {
    $conn = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER', 'root'), env('DB_PASS', ''), env('DB_NAME', 'trackify'));
} catch (mysqli_sql_exception $e) {
    error_log('Trackify: error de conexión a MySQL: ' . $e->getMessage());
    http_response_code(500);
    die("No se pudo conectar con la base de datos. Verificá que MySQL esté iniciado.");
}
$conn->set_charset('utf8mb4');

require_once __DIR__ . '/includes/categorias_meta.php';
require_once __DIR__ . '/includes/seguridad.php';

// --- MIGRACIONES AUTOMÁTICAS (solo aditivas) ---
// Antes corrían ~15 consultas en cada request. Ahora una sola consulta comprueba si el
// esquema ya está al día y las migraciones solo se ejecutan cuando falta algo.
// Al agregar una migración, sumá su columna a $columnas_esperadas.
$columnas_esperadas = [
    'usuarios'     => ['uuid', 'email_verificado', 'codigo_verificacion', 'codigo_expiracion', 'google_id', 'Foto_perfil', 'moneda_principal'],
    'metas_ahorro' => ['descripcion', 'estado'],
    'categorias'   => ['icono', 'color', 'id_usuario'],
];
$condiciones = [];
foreach ($columnas_esperadas as $tabla => $columnas) {
    $condiciones[] = "(TABLE_NAME = '$tabla' AND COLUMN_NAME IN ('" . implode("','", $columnas) . "'))";
}
$estado_esquema = $conn->query(
    "SELECT
        (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND (" . implode(' OR ', $condiciones) . ")) AS columnas,
        (SELECT NUMERIC_SCALE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'metas_ahorro' AND COLUMN_NAME = 'monto_objetivo') AS escala_metas,
        (SELECT COUNT(*) FROM categorias) AS categorias"
)->fetch_assoc();

if ((int)$estado_esquema['columnas'] < count($columnas_esperadas, COUNT_RECURSIVE) - count($columnas_esperadas)) {
    foreach ([
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS uuid VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS codigo_verificacion VARCHAR(10) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS codigo_expiracion DATETIME DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS Foto_perfil VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS moneda_principal ENUM('ARS','USD') NOT NULL DEFAULT 'ARS'",
        "ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE metas_ahorro ADD COLUMN IF NOT EXISTS estado ENUM('activo', 'inactivo', 'logrado') DEFAULT 'activo'",
        "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS icono VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS color VARCHAR(7) DEFAULT NULL",
        // Categorías por usuario: NULL = predeterminada del sistema (solo lectura), un id = categoría personal
        "ALTER TABLE categorias ADD COLUMN IF NOT EXISTS id_usuario INT(11) DEFAULT NULL",
        "ALTER TABLE categorias ADD INDEX IF NOT EXISTS idx_categorias_usuario (id_usuario)",
    ] as $sql) {
        $conn->query($sql);
    }
}

// Los montos de las metas se guardaban sin centavos (DECIMAL(10,0)). Se amplía una sola vez.
if ($estado_esquema['escala_metas'] !== null && (int)$estado_esquema['escala_metas'] === 0) {
    $conn->query("ALTER TABLE metas_ahorro MODIFY monto_objetivo DECIMAL(12,2) NOT NULL, MODIFY monto_actual DECIMAL(12,2) NOT NULL DEFAULT 0");
}

// Categorías predeterminadas (base de datos nueva o vacía)
if ((int)$estado_esquema['categorias'] <= 5) {
    $predeterminadas = [
        'ingreso' => ["Sueldo", "Préstamo recibido", "Reintegro", "Ventas", "Inversiones", "Intereses", "Regalos", "Devoluciones", "Freelance / trabajos extra", "Becas / subsidios", "Otros ingresos"],
        'gasto'   => ["Alimentos", "Transporte", "Vivienda", "Salud", "Educación", "Entretenimiento", "Compras personales", "Deudas", "Impuestos", "Mascotas", "Suscripciones", "Regalos / donaciones", "Ropa", "Tecnología", "Viajes", "Otros gastos"],
    ];
    $stmt_ins = $conn->prepare("INSERT IGNORE INTO categorias (nombre, tipo, icono, color) VALUES (?, ?, ?, ?)");
    foreach ($predeterminadas as $tipo_cat => $nombres) {
        foreach ($nombres as $nombre_cat) {
            $meta = obtenerMetaCategoriaPorNombre($nombre_cat, $tipo_cat);
            $stmt_ins->bind_param("ssss", $nombre_cat, $tipo_cat, $meta['icono'], $meta['color']);
            $stmt_ins->execute();
        }
    }
}
unset($columnas_esperadas, $condiciones, $estado_esquema, $predeterminadas, $stmt_ins, $meta, $tipo_cat, $nombres, $nombre_cat);
