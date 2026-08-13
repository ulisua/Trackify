<?php
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Si no está logueado → login
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

// Nombre del usuario
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$pagina_actual = $page ?? '';
$titulo = $titulo_pagina ?? 'Trackify';

// Leer moneda preferida del usuario desde la BD (se cachea en sesión)
if (!isset($_SESSION['moneda'])) {
    require_once 'conexion.php';
    $stmt_m = $conn->prepare("SELECT moneda_principal FROM usuarios WHERE id_usuario = ?");
    $stmt_m->bind_param("i", $_SESSION['usuario_id']);
    $stmt_m->execute();
    $row_m = $stmt_m->get_result()->fetch_assoc();
    $_SESSION['moneda'] = $row_m['moneda_principal'] ?? 'ARS';
}
$moneda_actual = $_SESSION['moneda'];

// ── Período ───────────────────────────────────────────────────────────────────
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
        $dia = date('j');
        $fecha_desde = $dia <= 15 ? date('Y-m-01') : date('Y-m-16');
        $fecha_hasta = $dia <= 15 ? date('Y-m-15') : date('Y-m-t');
        break;
    default:
        $fecha_desde = date('Y-m-01');
        $fecha_hasta = date('Y-m-t');
        break;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <link rel="stylesheet" href="styles.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <?php if(isset($extra_css)) echo $extra_css; ?>
    <script src="js/moneda.js"></script>
</head>

<body>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="cerrarMenu()"></div>

<header class="navbar">
    <div class="logo">
        <img src="logo.png" alt="Trackify Icon" style="height: 60px;">
    </div>

    <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()" aria-label="Abrir menú">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- SELECTOR DE MONEDA -->
    <div class="moneda-selector">
        <select id="selectorMoneda" title="Cambiar moneda" data-moneda-actual="<?= $moneda_actual ?>">
            <option value="ARS" <?= $moneda_actual === 'ARS' ? 'selected' : '' ?>>🇦🇷 ARS</option>
            <option value="USD" <?= $moneda_actual === 'USD' ? 'selected' : '' ?>>🇺🇸 USD</option>
        </select>
        <span id="tipoCambioInfo" style="font-size:.75rem;color:#94a3b8;white-space:nowrap;"></span>
    </div>

    <!-- SELECTOR DE PERÍODO -->
    <div class="moneda-selector">
        <select id="selectorPeriodo" title="Cambiar período" onchange="cambiarPeriodo(this.value)">
            <option value="semana"   <?= $periodo_actual === 'semana'   ? 'selected' : '' ?>>📅 Semana</option>
            <option value="quincena" <?= $periodo_actual === 'quincena' ? 'selected' : '' ?>>📅 Quincena</option>
            <option value="mes"      <?= $periodo_actual === 'mes'      ? 'selected' : '' ?>>📅 Mes</option>
        </select>
    </div>

    <!-- ACA VA EL NOMBRE DINAMICO -->
    <div class="user">Hola, <span id="usuarioNombre" style="color: #EA73F5; font-weight: 600;"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
</header>

<div class="layout">

    <aside class="sidebar" id="sidebar">
        <nav>
            <a href="index.php" <?php echo ($pagina_actual == 'dashboard') ? 'class="active"' : ''; ?>>Dashboard</a>
            <a href="ingresos.php" <?php echo ($pagina_actual == 'ingresos') ? 'class="active"' : ''; ?>>Ingresos</a>
            <a href="ia.php" <?php echo ($pagina_actual == 'ia') ? 'class="active"' : ''; ?>>IA</a>
            <a href="gastos.php" <?php echo ($pagina_actual == 'gastos') ? 'class="active"' : ''; ?>>Gastos</a>
            <a href="categorias.php" <?php echo ($pagina_actual == 'categorias') ? 'class="active"' : ''; ?>>Categorías</a>
            <a href="objetivos.php" <?php echo ($pagina_actual == 'objetivos') ? 'class="active"' : ''; ?>>Objetivos</a>
            <a href="perfil.php" <?php echo ($pagina_actual == 'perfil') ? 'class="active"' : ''; ?>>Perfil</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </aside>

    <main class="content">
<script>
function cambiarPeriodo(periodo) {
    const url = new URL(window.location.href);
    url.searchParams.set('periodo', periodo);
    window.location.href = url.toString();
}
</script>
