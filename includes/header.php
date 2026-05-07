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
