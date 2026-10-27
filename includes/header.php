<?php
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../conexion.php';

// Si no está logueado → login
if(!isset($_SESSION['usuario_id'])){
    $loginPath = dirname($_SERVER['PHP_SELF']);
    if ($loginPath === '/' || $loginPath === '\\') {
        $loginPath = '';
    }
    header("Location: {$loginPath}/login.php");
    exit();
}

// Nombre del usuario
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$pagina_actual = $page ?? '';
$titulo = $titulo_pagina ?? 'Trackify';

require_once __DIR__ . '/periodo.php';

// Categorías visibles para el usuario (predeterminadas + propias), agrupadas por tipo para JS
$dbCategorias = ['ingreso' => [], 'gasto' => []];
foreach (obtenerCategoriasVisibles($conn, (int)$_SESSION['usuario_id']) as $tipoCat => $listaCat) {
    $dbCategorias[$tipoCat] = array_column($listaCat, 'nombre');
}

// Selectores de moneda y período. Se renderizan dos veces: en el navbar (escritorio)
// y dentro del menú lateral (móvil). js/moneda.js los mantiene sincronizados.
function renderSelectoresHeader($moneda_actual, $periodo_actual, $conEtiquetas) {
    $monedas = ['ARS' => 'ARS', 'USD' => 'USD'];
    $periodos = ['semana' => 'Semana', 'quincena' => 'Quincena', 'mes' => 'Mes'];
    ?>
    <label class="selector-header">
        <?php if ($conEtiquetas): ?><span class="selector-header-label">Moneda</span><?php endif; ?>
        <select class="js-selector-moneda" title="Cambiar moneda" aria-label="Moneda">
            <?php foreach ($monedas as $valor => $texto): ?>
                <option value="<?= $valor ?>" <?= $moneda_actual === $valor ? 'selected' : '' ?>><?= $texto ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="selector-header">
        <?php if ($conEtiquetas): ?><span class="selector-header-label">Período</span><?php endif; ?>
        <select class="js-selector-periodo" title="Cambiar período" aria-label="Período" onchange="cambiarPeriodo(this.value)">
            <?php foreach ($periodos as $valor => $texto): ?>
                <option value="<?= $valor ?>" <?= $periodo_actual === $valor ? 'selected' : '' ?>><?= $texto ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <script>
        (function() {
            let savedTheme = null;
            try { savedTheme = localStorage.getItem('theme'); } catch (e) {}
            document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken()); ?>">
    <title><?php echo htmlspecialchars($titulo); ?></title>

    <!-- Librerías primero, para que los estilos de Trackify las sobrescriban -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="moneda.css">

    <script>
        window.dbCategorias = <?php echo json_encode($dbCategorias, JSON_HEX_TAG | JSON_HEX_AMP); ?>;

        function cambiarPeriodo(periodo) {
            const url = new URL(window.location.href);
            url.searchParams.set('periodo', periodo);
            window.location.href = url.toString();
        }
    </script>

    <?php if(isset($extra_css)) echo $extra_css; ?>
    <!-- El responsive va al final: sus reglas deben ganar sobre las de cada página -->
    <link rel="stylesheet" href="css/responsive.css">
</head>

<body>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="cerrarMenu()"></div>

<header class="navbar">
    <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()" aria-label="Abrir menú">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="logo">
        <a href="index.php" class="logo-link" aria-label="Trackify Inicio">
            <img src="logo.png" alt="Trackify Icon" class="logo-img">
        </a>
    </div>

    <div class="header-controls">
        <?php renderSelectoresHeader($moneda_actual, $periodo_actual, false); ?>
        <span id="tipoCambioInfo"></span>
    </div>

    <div class="user">Hola, <span id="usuarioNombre"><?php echo htmlspecialchars($nombreUsuario); ?></span></div>
</header>

<div class="layout">

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-controles">
            <p class="sidebar-saludo">Hola, <span><?php echo htmlspecialchars($nombreUsuario); ?></span></p>
            <?php renderSelectoresHeader($moneda_actual, $periodo_actual, true); ?>
        </div>
        <nav>
            <a href="index.php" <?php echo ($pagina_actual == 'dashboard') ? 'class="active"' : ''; ?>>Dashboard</a>
            <a href="movimientos.php" <?php echo ($pagina_actual == 'movimientos') ? 'class="active"' : ''; ?>>Movimientos</a>
            <a href="ingresos.php" <?php echo ($pagina_actual == 'ingresos') ? 'class="active"' : ''; ?>>Ingresos</a>
            <a href="gastos.php" <?php echo ($pagina_actual == 'gastos') ? 'class="active"' : ''; ?>>Gastos</a>
            <a href="categorias.php" <?php echo ($pagina_actual == 'categorias') ? 'class="active"' : ''; ?>>Categorías</a>
            <a href="objetivos.php" <?php echo ($pagina_actual == 'objetivos') ? 'class="active"' : ''; ?>>Objetivos</a>
            <a href="ia.php" <?php echo ($pagina_actual == 'ia') ? 'class="active"' : ''; ?>>IA</a>
            <a href="perfil.php" <?php echo ($pagina_actual == 'perfil') ? 'class="active"' : ''; ?>>Perfil</a>
            <a href="logout.php" onclick="event.preventDefault(); confirmarLogout();">Cerrar sesión</a>
        </nav>
    </aside>

    <main class="content">
