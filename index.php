<?php
session_start();

// Si no está logueado → login
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

// Nombre del usuario
$nombreUsuario = $_SESSION['usuario_nombre'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trackify</title>
    <link rel="stylesheet" href="styles.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<!-- BOTON FLOTANTE IA -->
<div id="botonIA" class="boton-ia" onclick="toggleChat()">💬</div>

<!-- CHAT FLOTANTE -->
<div id="chatFlotante" class="chat-flotante oculto">
    <div class="chat-header">
        <span>Asistente IA</span>
        <span onclick="toggleChat()" style="cursor:pointer;">✖</span>
    </div>
    <div id="chat" class="chat"></div>
    <div class="input-container">
        <textarea id="pregunta" class="input-chat" placeholder="Escribí tu consulta..."></textarea>
        <button class="btn-enviar" onclick="preguntarIA()">Enviar</button>
    </div>
</div>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="cerrarMenu()"></div>

<header class="navbar">
    <div class="logo">💰 Trackify</div>

    <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()" aria-label="Abrir menú">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- ACA VA EL NOMBRE DINAMICO -->
    <div class="user">Hola, <span id="usuarioNombre"><?php echo $nombreUsuario; ?></span></div>
</header>

<div class="layout">

    <aside class="sidebar" id="sidebar">
        <nav>
            <a class="active">Dashboard</a>
            <a href="ingresos.php">Ingresos</a>
            <a href="ia.php">IA</a>
            <a>Gastos</a>
            <a>Categorías</a>
            <a>Objetivos</a>
            <a>Perfil</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </aside>

    <main class="content">

        <!-- CARDS PRINCIPALES -->
        <section class="cards">
            <div class="card">
                <h4>Ingresos</h4>
                <p id="ingresos">$0</p>
            </div>
            <div class="card highlight">
                <h4>Balance</h4>
                <p id="balance">$0</p>
            </div>
            <div class="card">
                <h4>Gastos</h4>
                <p id="gastos">$0</p>
            </div>
        </section>

        <!-- CARDS SECUNDARIAS -->
        <section class="cards small">
            <div class="card">% gasto <p id="porcentaje">0%</p></div>
            <div class="card">Mayor categoría <p id="categoriaTop">-</p></div>
            <div class="card">Gasto mensual <p id="gastoMensual">$0</p></div>
        </section>

        <!-- GRAFICOS -->
        <section class="graficos">
            <div class="grafico-box">
                <canvas id="graficoTorta"></canvas>
            </div>
            <div class="grafico-box">
                <canvas id="graficoBarras"></canvas>
            </div>
        </section>

        <!-- BOTONES -->
        <div class="acciones">
            <button class="btn ingreso" onclick="abrirModal('ingreso')">+ Ingreso</button>
            <button class="btn gasto" onclick="abrirModal('gasto')">+ Gasto</button>
            <button class="btn objetivo" onclick="abrirModal('objetivo')">+ Objetivo</button>
        </div>

        <!-- GRID -->
        <section class="grid">
            <div class="box">
                <h3>Movimientos</h3>
                <ul id="movimientos"></ul>
            </div>
            <div class="box">
                <h3>Recomendaciones IA</h3>
                <p id="recomendacion"></p>
            </div>
        </section>

    </main>
</div>

<!-- MODAL -->
<div id="modal" class="modal hidden">
    <div class="modal-content">
        <h3 id="modalTitulo"></h3>
        <input type="number" id="monto" placeholder="Monto">
        <input type="date" id="fecha">
        <input type="text" id="extra" placeholder="Descripción / Categoría">
        <div class="modal-actions">
            <button class="btn" onclick="guardar()">Guardar</button>
            <button class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<footer class="footer">
    <p>Trackify © 2026</p>
</footer>

<script src="js/ia.js?v=2"></script>

<!-- SCRIPT MENÚ -->
<script>
const sidebar = document.getElementById('sidebar');
const toggle = document.getElementById('menuToggle');
const overlay = document.getElementById('sidebarOverlay');

function toggleMenu() {
    const isOpen = sidebar.classList.contains('open');
    isOpen ? cerrarMenu() : abrirMenu();
}

function abrirMenu() {
    sidebar.classList.add('open');
    toggle.classList.add('open');
    overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
}

function cerrarMenu() {
    sidebar.classList.remove('open');
    toggle.classList.remove('open');
    overlay.classList.remove('visible');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') cerrarMenu();
});

document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 900) cerrarMenu();
    });
});
</script>

</body>
</html>