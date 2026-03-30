<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Perfil - Trackify</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* ===== PERFIL ===== */
        .page-header {
            margin-bottom: 28px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        /* Layout de dos columnas */
        .perfil-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            align-items: start;
        }

        /* ---- Columna izquierda ---- */
        .perfil-sidebar-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            overflow: hidden;
            position: sticky;
            top: 80px;
        }

        .perfil-banner {
            height: 80px;
            background: linear-gradient(135deg, #1E1B26, #084734);
            position: relative;
        }

        .perfil-avatar-wrap {
            display: flex;
            justify-content: center;
            margin-top: -36px;
            margin-bottom: 12px;
            position: relative;
        }

        .perfil-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #CFF27C, #EA73F5);
            border: 4px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: #1E1B26;
            cursor: pointer;
            transition: opacity 0.2s;
            user-select: none;
        }

        .perfil-avatar:hover {
            opacity: 0.85;
        }

        .perfil-avatar-edit {
            position: absolute;
            bottom: 2px;
            right: calc(50% - 42px);
            background: #1E1B26;
            color: white;
            font-size: 0.6rem;
            padding: 2px 5px;
            border-radius: 99px;
        }

        .perfil-nombre-box {
            text-align: center;
            padding: 0 20px 20px;
        }

        .perfil-nombre-box strong {
            display: block;
            font-size: 1.1rem;
            color: #1E1B26;
            margin-bottom: 2px;
        }

        .perfil-nombre-box span {
            font-size: 0.85rem;
            color: #94A3B8;
        }

        .perfil-divider {
            height: 1px;
            background: #F1F5F9;
            margin: 0 20px;
        }

        /* Stats del usuario */
        .perfil-user-stats {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .perfil-user-stat {
            display: flex;
            justify-content: space-between;
            font-size: 0.88rem;
        }

        .perfil-user-stat span:first-child {
            color: #64748B;
        }

        .perfil-user-stat span:last-child {
            font-weight: 600;
            color: #1E1B26;
        }

        /* Botón cerrar sesión */
        .btn-logout {
            display: block;
            width: calc(100% - 40px);
            margin: 0 20px 20px;
            padding: 11px;
            background: #FFF1F2;
            color: #BE123C;
            border: 1px solid #FECDD3;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            text-align: center;
        }

        .btn-logout:hover {
            background: #FFE4E6;
        }

        /* ---- Columna derecha ---- */
        .perfil-secciones {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .seccion-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            overflow: hidden;
        }

        .seccion-header {
            padding: 18px 24px;
            border-bottom: 1px solid #F1F5F9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seccion-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #1E1B26;
        }

        .btn-editar-seccion {
            padding: 7px 14px;
            background: transparent;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748B;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-editar-seccion:hover {
            background: #F8FAFC;
            color: #1E1B26;
        }

        .btn-editar-seccion.guardando {
            background: #084734;
            color: #CFF27C;
            border-color: #084734;
        }

        .seccion-body {
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Campos del formulario */
        .campo-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .campo label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .campo input,
        .campo select {
            padding: 11px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            background: #F8FAFC;
            color: #1E1B26;
            outline: none;
            transition: all 0.2s;
        }

        .campo input:focus,
        .campo select:focus {
            border-color: #084734;
            background: white;
            box-shadow: 0 0 0 3px rgba(8, 71, 52, 0.1);
        }

        .campo input:disabled {
            background: #F8FAFC;
            color: #64748B;
            cursor: default;
        }

        /* Toggle switches */
        .toggle-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #F8FAFC;
        }

        .toggle-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .toggle-row:first-child {
            padding-top: 0;
        }

        .toggle-info strong {
            display: block;
            font-size: 0.95rem;
            color: #1E1B26;
            margin-bottom: 2px;
        }

        .toggle-info span {
            font-size: 0.82rem;
            color: #94A3B8;
        }

        .switch {
            position: relative;
            width: 44px;
            height: 24px;
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            inset: 0;
            background: #E2E8F0;
            border-radius: 99px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .slider::before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
        }

        .switch input:checked+.slider {
            background: #084734;
        }

        .switch input:checked+.slider::before {
            transform: translateX(20px);
        }

        /* Zona de peligro */
        .zona-peligro {
            border-color: #FECDD3;
        }

        .zona-peligro .seccion-header {
            background: #FFF1F2;
            border-bottom-color: #FECDD3;
        }

        .zona-peligro .seccion-header h3 {
            color: #BE123C;
        }

        .peligro-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #F8FAFC;
        }

        .peligro-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .peligro-row:first-child {
            padding-top: 0;
        }

        .peligro-info strong {
            display: block;
            font-size: 0.95rem;
            color: #1E1B26;
            margin-bottom: 2px;
        }

        .peligro-info span {
            font-size: 0.82rem;
            color: #94A3B8;
        }

        .btn-peligro {
            padding: 8px 16px;
            background: transparent;
            border: 1px solid #FECDD3;
            border-radius: 6px;
            color: #BE123C;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            white-space: nowrap;
            transition: all 0.15s;
        }

        .btn-peligro:hover {
            background: #FFF1F2;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .perfil-layout {
                grid-template-columns: 1fr;
            }

            .perfil-sidebar-card {
                position: static;
            }

            .perfil-user-stats {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 14px;
            }

            .perfil-user-stat {
                flex-direction: column;
                gap: 2px;
                min-width: 100px;
            }
        }

        @media (max-width: 540px) {
            .campo-row {
                grid-template-columns: 1fr;
            }

            .peligro-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .btn-peligro {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="cerrarMenu()"></div>

<body>

    <header class="navbar">
        <div class="logo">💰 Trackify</div>
        <button class="menu-toggle" id="menuToggle" onclick="toggleMenu()" aria-label="Abrir menú">
            <span></span><span></span><span></span>
        </button>
        <div class="user">Hola, <span id="usuarioNombre">Usuario</span></div>
    </header>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <nav>
                <a href="index.html">Dashboard</a>
                <a href="ingresos.html">Ingresos</a>
                <a href="ia.html">IA</a>
                <a href="gastos.html">Gastos</a>
                <a href="categorias.html">Categorías</a>
                <a href="objetivos.html">Objetivos</a>
                <a class="active">Perfil</a>
            </nav>
        </aside>

        <main class="content">

            <div class="page-header">
                <h2>👤 Mi perfil</h2>
            </div>

            <div class="perfil-layout">

                <!-- Columna izquierda -->
                <div>
                    <div class="perfil-sidebar-card">
                        <div class="perfil-banner"></div>
                        <div class="perfil-avatar-wrap">
                            <div class="perfil-avatar" title="Cambiar foto">U</div>
                            <span class="perfil-avatar-edit">✏️ editar</span>
                        </div>
                        <div class="perfil-nombre-box">
                            <strong id="nombreMostrado">Usuario</strong>
                            <span id="emailMostrado">usuario@email.com</span>
                        </div>
                        <div class="perfil-divider"></div>
                        <div class="perfil-user-stats">
                            <div class="perfil-user-stat">
                                <span>Miembro desde</span>
                                <span>Ene 2026</span>
                            </div>
                            <div class="perfil-user-stat">
                                <span>Movimientos</span>
                                <span>47</span>
                            </div>
                            <div class="perfil-user-stat">
                                <span>Objetivos activos</span>
                                <span>4</span>
                            </div>
                            <div class="perfil-user-stat">
                                <span>Moneda</span>
                                <span>ARS ($)</span>
                            </div>
                        </div>
                        <div class="perfil-divider"></div>
                        <div style="padding:16px 20px 0;">
                            <button class="btn-logout" onclick="confirmarLogout()">🚪 Cerrar sesión</button>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="perfil-secciones">

                    <!-- Datos personales -->
                    <div class="seccion-card">
                        <div class="seccion-header">
                            <h3>📋 Datos personales</h3>
                            <button class="btn-editar-seccion" id="btnDatos" onclick="toggleEditar('datos')">✏️
                                Editar</button>
                        </div>
                        <div class="seccion-body">
                            <div class="campo-row">
                                <div class="campo">
                                    <label>Nombre</label>
                                    <input type="text" id="inputNombre" value="Usuario" disabled>
                                </div>
                                <div class="campo">
                                    <label>Apellido</label>
                                    <input type="text" id="inputApellido" value="Apellido" disabled>
                                </div>
                            </div>
                            <div class="campo-row">
                                <div class="campo">
                                    <label>Email</label>
                                    <input type="email" id="inputEmail" value="usuario@email.com" disabled>
                                </div>
                                <div class="campo">
                                    <label>Teléfono</label>
                                    <input type="tel" id="inputTel" value="+54 11 1234-5678" disabled>
                                </div>
                            </div>
                            <div class="campo-row">
                                <div class="campo">
                                    <label>País</label>
                                    <select id="inputPais" disabled>
                                        <option selected>Argentina</option>
                                        <option>Uruguay</option>
                                        <option>Chile</option>
                                        <option>México</option>
                                        <option>España</option>
                                    </select>
                                </div>
                                <div class="campo">
                                    <label>Moneda</label>
                                    <select id="inputMoneda" disabled>
                                        <option selected>ARS — Peso argentino</option>
                                        <option>USD — Dólar</option>
                                        <option>EUR — Euro</option>
                                        <option>UYU — Peso uruguayo</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seguridad -->
                    <div class="seccion-card">
                        <div class="seccion-header">
                            <h3>🔐 Seguridad</h3>
                            <button class="btn-editar-seccion" id="btnSeg" onclick="toggleEditar('seg')">✏️
                                Editar</button>
                        </div>
                        <div class="seccion-body">
                            <div class="campo">
                                <label>Contraseña actual</label>
                                <input type="password" id="inputPassActual" value="••••••••" disabled>
                            </div>
                            <div class="campo-row">
                                <div class="campo">
                                    <label>Nueva contraseña</label>
                                    <input type="password" id="inputPassNueva" placeholder="••••••••" disabled>
                                </div>
                                <div class="campo">
                                    <label>Confirmar contraseña</label>
                                    <input type="password" id="inputPassConf" placeholder="••••••••" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preferencias -->
                    <div class="seccion-card">
                        <div class="seccion-header">
                            <h3>⚙️ Preferencias</h3>
                        </div>
                        <div class="seccion-body">
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <strong>Notificaciones de objetivos</strong>
                                    <span>Alertas cuando te acercás a tu meta</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <strong>Resumen mensual por email</strong>
                                    <span>Recibís un reporte el 1° de cada mes</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <strong>Sugerencias de la IA</strong>
                                    <span>La IA analiza tus patrones y sugiere mejoras</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <strong>Modo oscuro</strong>
                                    <span>Próximamente disponible</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" disabled>
                                    <span class="slider" style="opacity:0.4;cursor:default"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Zona de peligro -->
                    <div class="seccion-card zona-peligro">
                        <div class="seccion-header">
                            <h3>⚠️ Zona de peligro</h3>
                        </div>
                        <div class="seccion-body">
                            <div class="peligro-row">
                                <div class="peligro-info">
                                    <strong>Exportar mis datos</strong>
                                    <span>Descargá un .csv con todos tus movimientos</span>
                                </div>
                                <button class="btn-peligro">📥 Exportar</button>
                            </div>
                            <div class="peligro-row">
                                <div class="peligro-info">
                                    <strong>Borrar historial</strong>
                                    <span>Elimina todos los movimientos (no se puede deshacer)</span>
                                </div>
                                <button class="btn-peligro">🗑️ Borrar historial</button>
                            </div>
                            <div class="peligro-row">
                                <div class="peligro-info">
                                    <strong>Eliminar cuenta</strong>
                                    <span>Borra tu cuenta y todos tus datos permanentemente</span>
                                </div>
                                <button class="btn-peligro" style="border-color:#BE123C;background:#FFF1F2;">💀 Eliminar
                                    cuenta</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <footer class="footer">
        <p>Trackify © 2026</p>
    </footer>

    <script>
        // Sidebar
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('menuToggle');
        const overlay = document.getElementById('sidebarOverlay');
        function toggleMenu() { sidebar.classList.contains('open') ? cerrarMenu() : abrirMenu(); }
        function abrirMenu() { sidebar.classList.add('open'); toggle.classList.add('open'); overlay.classList.add('visible'); document.body.style.overflow = 'hidden'; }
        function cerrarMenu() { sidebar.classList.remove('open'); toggle.classList.remove('open'); overlay.classList.remove('visible'); document.body.style.overflow = ''; }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarMenu(); });
        document.querySelectorAll('.sidebar a').forEach(l => l.addEventListener('click', () => { if (window.innerWidth <= 900) cerrarMenu(); }));

        // Toggle editar secciones
        const secciones = {
            datos: {
                btn: 'btnDatos',
                campos: ['inputNombre', 'inputApellido', 'inputEmail', 'inputTel', 'inputPais', 'inputMoneda'],
                editando: false
            },
            seg: {
                btn: 'btnSeg',
                campos: ['inputPassActual', 'inputPassNueva', 'inputPassConf'],
                editando: false
            }
        };

        function toggleEditar(id) {
            const s = secciones[id];
            const btn = document.getElementById(s.btn);
            s.editando = !s.editando;

            s.campos.forEach(c => {
                const el = document.getElementById(c);
                if (el) el.disabled = !s.editando;
            });

            if (s.editando) {
                btn.textContent = '💾 Guardar';
                btn.classList.add('guardando');
            } else {
                btn.textContent = '✏️ Editar';
                btn.classList.remove('guardando');
                // Actualizar nombre y email mostrados
                const nom = document.getElementById('inputNombre');
                const ape = document.getElementById('inputApellido');
                const eml = document.getElementById('inputEmail');
                if (nom && ape) document.getElementById('nombreMostrado').textContent = nom.value + ' ' + ape.value;
                if (eml) document.getElementById('emailMostrado').textContent = eml.value;
            }
        }

        // Avatar: inicial del nombre
        function actualizarAvatar() {
            const nom = document.getElementById('inputNombre').value;
            document.querySelector('.perfil-avatar').textContent = nom ? nom[0].toUpperCase() : 'U';
        }
        document.getElementById('inputNombre').addEventListener('input', actualizarAvatar);

        // Logout
        function confirmarLogout() {
            if (confirm('¿Cerrar sesión?')) alert('Sesión cerrada. Redirigiendo al login...');
        }
    </script>

</body>

</html>