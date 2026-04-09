<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Ingresos - Trackify</title>
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
                <a class="active">Ingresos</a>
                <a href="ia.html">IA</a>
                <a href="gastos.html">Gastos</a>
                <a href="categorias.html">Categorías</a>
                <a href="objetivos.html">Objetivos</a>
                <a href="perfil.html">Perfil</a>
            </nav>
        </aside>

        <main class="content">

            <h2>💸 Ingresos</h2>

            <!-- RESUMEN -->
            <section class="cards">
                <div class="card ingreso-card">
                    <h4>Total del mes</h4>
                    <p>$120.000</p>
                </div>
                <div class="card">
                    <h4>Cantidad</h4>
                    <p>8</p>
                </div>
                <div class="card">
                    <h4>Promedio</h4>
                    <p>$15.000</p>
                </div>
            </section>

            <!-- ACCIONES -->
            <div class="acciones">
                <button class="btn ingreso">+ Nuevo ingreso</button>
            </div>

            <!-- FILTROS -->
            <section class="filtros">
                <input type="date">
                <select>
                    <option>Todos</option>
                    <option>Sueldo</option>
                    <option>Freelance</option>
                    <option>Inversiones</option>
                </select>
            </section>

            <!-- TABLA (desktop) / CARDS (mobile) -->
            <section class="tabla-box">

                <!-- Tabla para desktop -->
                <table class="tabla tabla-desktop">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Monto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tablaIngresos">
                        <tr>
                            <td>15/03</td>
                            <td>Sueldo</td>
                            <td>Trabajo</td>
                            <td class="positivo">+$80.000</td>
                            <td>
                                <button class="edit">✏️</button>
                                <button class="delete">🗑️</button>
                            </td>
                        </tr>
                        <tr>
                            <td>10/03</td>
                            <td>Diseño web</td>
                            <td>Freelance</td>
                            <td class="positivo">+$40.000</td>
                            <td>
                                <button class="edit">✏️</button>
                                <button class="delete">🗑️</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Cards para mobile -->
                <div class="movimiento-cards" id="movimientoCards">
                    <div class="movimiento-card">
                        <div class="mc-top">
                            <span class="mc-desc">Sueldo</span>
                            <span class="mc-monto positivo">+$80.000</span>
                        </div>
                        <div class="mc-bottom">
                            <span class="mc-tag">Trabajo</span>
                            <span class="mc-fecha">15/03</span>
                            <div class="mc-acciones">
                                <button class="edit">✏️</button>
                                <button class="delete">🗑️</button>
                            </div>
                        </div>
                    </div>
                    <div class="movimiento-card">
                        <div class="mc-top">
                            <span class="mc-desc">Diseño web</span>
                            <span class="mc-monto positivo">+$40.000</span>
                        </div>
                        <div class="mc-bottom">
                            <span class="mc-tag">Freelance</span>
                            <span class="mc-fecha">10/03</span>
                            <div class="mc-acciones">
                                <button class="edit">✏️</button>
                                <button class="delete">🗑️</button>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

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