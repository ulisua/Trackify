<?php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/assets.php';

if (!isset($_SESSION['usuario_id'])) {
    redirigir('login.php');
}

// Nombre del usuario
$nombreUsuario = $_SESSION['usuario_nombre'] ?? 'Usuario';
$pagina_actual = $page ?? '';
$titulo = $titulo_pagina ?? 'Trackify';

require_once __DIR__ . '/periodo.php';

// Modales que usa cada página: footer.php imprime solo esos (antes iban los 7 en todas).
$modales_pagina = [
    'dashboard'   => ['movimiento', 'objetivo'],
    'ingresos'    => ['movimiento', 'editar_movimiento'],
    'gastos'      => ['movimiento', 'editar_movimiento'],
    'movimientos' => ['editar_movimiento'],
    'objetivos'   => ['objetivo', 'ahorro', 'editar_objetivo'],
    'categorias'  => ['categoria', 'editar_categoria'],
][$pagina_actual] ?? [];

// Flatpickr (calendario) solo donde hay campos de fecha: modales con fecha o filtros por fecha
$usa_calendario = array_intersect($modales_pagina, ['movimiento', 'editar_movimiento', 'objetivo', 'editar_objetivo']) !== [];

// Categorías visibles (predeterminadas + propias) para el selector de los modales de movimiento
$dbCategorias = null;
if (array_intersect($modales_pagina, ['movimiento', 'editar_movimiento'])) {
    $dbCategorias = ['ingreso' => [], 'gasto' => []];
    foreach (obtenerCategoriasVisibles($conn, (int)$_SESSION['usuario_id']) as $tipoCat => $listaCat) {
        $dbCategorias[$tipoCat] = array_column($listaCat, 'nombre');
    }
}

// Menú lateral: página => [archivo, texto]
$secciones_menu = [
    'dashboard'   => ['index.php', 'Dashboard'],
    'movimientos' => ['movimientos.php', 'Movimientos'],
    'ingresos'    => ['ingresos.php', 'Ingresos'],
    'gastos'      => ['gastos.php', 'Gastos'],
    'categorias'  => ['categorias.php', 'Categorías'],
    'objetivos'   => ['objetivos.php', 'Objetivos'],
    'ia'          => ['ia.php', 'IA'],
    'perfil'      => ['perfil.php', 'Perfil'],
];

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
    <?php include __DIR__ . '/tema_inicial.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken()); ?>">
    <title><?php echo htmlspecialchars($titulo); ?></title>

    <!-- Librerías primero, para que los estilos de Trackify las sobrescriban -->
    <?php if ($usa_calendario): ?><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css"><?php endif; ?>
    <?php headComun(true); ?>

    <script>
        <?php if ($dbCategorias !== null): ?>window.dbCategorias = <?php echo json_encode($dbCategorias, JSON_HEX_TAG | JSON_HEX_AMP); ?>;<?php endif; ?>

        function cambiarPeriodo(periodo) {
            const url = new URL(window.location.href);
            url.searchParams.set('periodo', periodo);
            window.location.href = url.toString();
        }
    </script>

    <?php if(isset($extra_css)) echo $extra_css; ?>
    <!-- El responsive va al final: sus reglas deben ganar sobre las de cada página -->
    <?= cssLink('css/responsive.css') ?>
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
            <?php foreach ($secciones_menu as $clave => [$archivo, $texto]): ?>
                <a href="<?= $archivo ?>"<?= $pagina_actual === $clave ? ' class="active" aria-current="page"' : '' ?>><?= $texto ?></a>
            <?php endforeach; ?>
            <a href="logout.php" onclick="event.preventDefault(); confirmarLogout();">Cerrar sesión</a>
        </nav>
    </aside>

    <main class="content">
