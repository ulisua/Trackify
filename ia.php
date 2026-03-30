<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>IA - Trackify</title>
    <link rel="stylesheet" href="styles.css">
</head>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="cerrarMenu()"></div>

<body>

    <header class="navbar">
        <div class="logo">💰 Trackify</div>
        <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()" aria-label="Abrir menú">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="user">Hola, <span id="usuarioNombre">Usuario</span></div>
    </header>

    <div class="layout">

        <aside class="sidebar" id="sidebar">
            <nav>
                <a href="index.html">Dashboard</a>
                <a href="ingresos.html">Ingresos</a>
                <a class="active">IA</a>
                <a href="gastos.html">Gastos</a>
                <a href="categorias.html">Categorías</a>
                <a href="objetivos.html">Objetivos</a>
                <a href="perfil.html">Perfil</a>
            </nav>
        </aside>

        <main class="content">

            <h2>🤖 Preguntale a la IA</h2>

            <div class="chat-container">
                <div id="chat" class="chat"></div>
                <div class="input-container">
                    <textarea id="pregunta" class="input-chat"
                        placeholder="Escribí tu consulta financiera..."></textarea>
                    <button class="btn-enviar" onclick="preguntarIA()">Enviar</button>
                </div>
            </div>

        </main>
    </div>

    <footer class="footer">
        <p>Trackify © 2026</p>
    </footer>

    <script src="js/ia.js?v=2"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('menuToggle');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            sidebar.classList.contains('open') ? cerrarMenu() : abrirMenu();
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
        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarMenu(); });
        document.querySelectorAll('.sidebar a').forEach(l => l.addEventListener('click', () => { if (window.innerWidth <= 900) cerrarMenu(); }));
    </script>

</body>

</html>