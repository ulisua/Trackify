<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Objetivos - Trackify</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* ===== OBJETIVOS ===== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .btn-nuevo {
            padding: 12px 22px;
            background: #EA73F5;
            color: #1E1B26;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .btn-nuevo:hover {
            opacity: 0.88;
            transform: translateY(-1px);
        }

        /* Stats rápidas */
        .obj-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .obj-stat {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 18px 20px;
            text-align: center;
        }

        .obj-stat-num {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1E1B26;
            display: block;
        }

        .obj-stat-label {
            font-size: 0.82rem;
            color: #94A3B8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Lista de objetivos */
        .obj-lista {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 32px;
        }

        .obj-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 22px 24px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .obj-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.07);
            transform: translateY(-1px);
        }

        .obj-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
            gap: 12px;
        }

        .obj-info {
            flex: 1;
            min-width: 0;
        }

        .obj-nombre {
            font-weight: 700;
            font-size: 1.05rem;
            color: #1E1B26;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .obj-badge {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .badge-activo {
            background: #DCFCE7;
            color: #16A34A;
        }

        .badge-pausado {
            background: #FEF9C3;
            color: #A16207;
        }

        .badge-logrado {
            background: #E0E7FF;
            color: #4338CA;
        }

        .obj-desc {
            font-size: 0.88rem;
            color: #94A3B8;
        }

        .obj-montos {
            text-align: right;
            white-space: nowrap;
        }

        .obj-actual {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1E1B26;
        }

        .obj-meta {
            font-size: 0.85rem;
            color: #94A3B8;
        }

        /* Barra de progreso */
        .obj-progress-wrap {
            margin-bottom: 14px;
        }

        .obj-progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 0.82rem;
        }

        .obj-pct {
            font-weight: 700;
        }

        .obj-fecha {
            color: #94A3B8;
        }

        .obj-barra {
            height: 10px;
            background: #F1F5F9;
            border-radius: 99px;
            overflow: hidden;
        }

        .obj-barra-fill {
            height: 100%;
            border-radius: 99px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .obj-barra-fill::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 6px;
            height: 100%;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 99px;
        }

        /* Colores por objetivo */
        .fill-verde {
            background: linear-gradient(90deg, #084734, #16A34A);
        }

        .fill-lila {
            background: linear-gradient(90deg, #7C3AED, #EA73F5);
        }

        .fill-naranja {
            background: linear-gradient(90deg, #EA580C, #F97316);
        }

        .fill-azul {
            background: linear-gradient(90deg, #1D4ED8, #60A5FA);
        }

        .fill-rosa {
            background: linear-gradient(90deg, #BE185D, #F472B6);
        }

        .pct-verde {
            color: #16A34A;
        }

        .pct-lila {
            color: #7C3AED;
        }

        .pct-naranja {
            color: #EA580C;
        }

        .pct-azul {
            color: #1D4ED8;
        }

        .pct-rosa {
            color: #BE185D;
        }

        /* Acciones del objetivo */
        .obj-acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-agregar {
            padding: 8px 16px;
            background: #F0FDF4;
            color: #16A34A;
            border: 1px solid #BBF7D0;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-agregar:hover {
            background: #DCFCE7;
        }

        .btn-editar-obj {
            padding: 8px 14px;
            background: #F8FAFC;
            color: #64748B;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-editar-obj:hover {
            background: #E2E8F0;
        }

        .btn-eliminar-obj {
            padding: 8px 14px;
            background: transparent;
            color: #94A3B8;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-eliminar-obj:hover {
            background: #FFF1F2;
            color: #BE123C;
            border-color: #FECDD3;
        }

        /* Objetivo completado */
        .obj-card.completado {
            border-color: #BBF7D0;
            background: linear-gradient(135deg, #F0FDF4, white);
        }

        .obj-card.completado::before {
            content: '✅ ¡Meta alcanzada!';
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #16A34A;
            background: #DCFCE7;
            padding: 3px 10px;
            border-radius: 99px;
        }

        /* Modal nuevo objetivo */
        .modal-obj {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 1;
            transition: opacity 0.3s;
        }

        .modal-obj.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-obj-content {
            background: white;
            border-radius: 12px;
            padding: 28px 24px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18);
            display: flex;
            flex-direction: column;
            gap: 16px;
            max-height: 90dvh;
            overflow-y: auto;
        }

        .modal-obj-content h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .form-group input,
        .form-group select {
            padding: 12px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            background: #F8FAFC;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #7C3AED;
            background: white;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .modal-obj-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .obj-stats {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 600px) {
            .obj-stats {
                grid-template-columns: 1fr 1fr 1fr;
                gap: 10px;
            }

            .obj-stat {
                padding: 14px 12px;
            }

            .obj-stat-num {
                font-size: 1.4rem;
            }

            .obj-card-top {
                flex-direction: column;
                gap: 8px;
            }

            .obj-montos {
                text-align: left;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-nuevo {
                width: 100%;
                text-align: center;
            }

            .obj-card.completado::before {
                position: static;
                display: inline-block;
                margin-bottom: 8px;
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
                <a class="active">Objetivos</a>
                <a href="perfil.html">Perfil</a>
            </nav>
        </aside>

        <main class="content">

            <div class="page-header">
                <h2>🎯 Objetivos de ahorro</h2>
                <button class="btn-nuevo" onclick="abrirModal()">+ Nuevo objetivo</button>
            </div>

            <!-- Stats -->
            <div class="obj-stats">
                <div class="obj-stat">
                    <span class="obj-stat-num">4</span>
                    <span class="obj-stat-label">Activos</span>
                </div>
                <div class="obj-stat">
                    <span class="obj-stat-num">1</span>
                    <span class="obj-stat-label">Logrados</span>
                </div>
                <div class="obj-stat">
                    <span class="obj-stat-num">$68%</span>
                    <span class="obj-stat-label">Promedio</span>
                </div>
            </div>

            <!-- Objetivos -->
            <div class="obj-lista">

                <!-- Completado -->
                <div class="obj-card completado">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                🏖️ Vacaciones en Brasil
                                <span class="obj-badge badge-logrado">Logrado</span>
                            </div>
                            <div class="obj-desc">Viaje de verano con ahorros</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$150.000</div>
                            <div class="obj-meta">de $150.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-verde">100%</span>
                            <span class="obj-fecha">Completado el 10/03</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-verde" style="width:100%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️ Eliminar</button>
                    </div>
                </div>

                <!-- Activo 1 -->
                <div class="obj-card">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                💻 Notebook nueva
                                <span class="obj-badge badge-activo">Activo</span>
                            </div>
                            <div class="obj-desc">Reemplazar la que tengo por una más potente</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$280.000</div>
                            <div class="obj-meta">de $500.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-lila">56%</span>
                            <span class="obj-fecha">Vence: 01/08/2026</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-lila" style="width:56%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-agregar">+ Agregar ahorro</button>
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️</button>
                    </div>
                </div>

                <!-- Activo 2 -->
                <div class="obj-card">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                🚗 Auto
                                <span class="obj-badge badge-activo">Activo</span>
                            </div>
                            <div class="obj-desc">Fondo inicial para el primer auto</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$1.200.000</div>
                            <div class="obj-meta">de $3.000.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-naranja">40%</span>
                            <span class="obj-fecha">Vence: 01/12/2027</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-naranja" style="width:40%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-agregar">+ Agregar ahorro</button>
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️</button>
                    </div>
                </div>

                <!-- Activo 3 -->
                <div class="obj-card">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                📚 Curso de programación
                                <span class="obj-badge badge-activo">Activo</span>
                            </div>
                            <div class="obj-desc">Bootcamp full-stack online</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$60.000</div>
                            <div class="obj-meta">de $80.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-azul">75%</span>
                            <span class="obj-fecha">Vence: 15/04/2026</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-azul" style="width:75%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-agregar">+ Agregar ahorro</button>
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️</button>
                    </div>
                </div>

                <!-- Pausado -->
                <div class="obj-card">
                    <div class="obj-card-top">
                        <div class="obj-info">
                            <div class="obj-nombre">
                                🏠 Departamento
                                <span class="obj-badge badge-pausado">Pausado</span>
                            </div>
                            <div class="obj-desc">Fondo para entrada de un depto propio</div>
                        </div>
                        <div class="obj-montos">
                            <div class="obj-actual">$500.000</div>
                            <div class="obj-meta">de $5.000.000</div>
                        </div>
                    </div>
                    <div class="obj-progress-wrap">
                        <div class="obj-progress-info">
                            <span class="obj-pct pct-rosa">10%</span>
                            <span class="obj-fecha">Sin vencimiento</span>
                        </div>
                        <div class="obj-barra">
                            <div class="obj-barra-fill fill-rosa" style="width:10%"></div>
                        </div>
                    </div>
                    <div class="obj-acciones">
                        <button class="btn-agregar">▶ Reanudar</button>
                        <button class="btn-editar-obj">✏️ Editar</button>
                        <button class="btn-eliminar-obj">🗑️</button>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <footer class="footer">
        <p>Trackify © 2026</p>
    </footer>

    <!-- MODAL NUEVO OBJETIVO -->
    <div class="modal-obj hidden" id="modalObj">
        <div class="modal-obj-content">
            <h3>🎯 Nuevo objetivo</h3>
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" placeholder="Ej: Notebook nueva">
            </div>
            <div class="form-group">
                <label>Descripción (opcional)</label>
                <input type="text" placeholder="Ej: Para el trabajo remoto">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Meta ($)</label>
                    <input type="number" placeholder="500000">
                </div>
                <div class="form-group">
                    <label>Ahorro inicial ($)</label>
                    <input type="number" placeholder="0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Fecha límite</label>
                    <input type="date">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select>
                        <option>Activo</option>
                        <option>Pausado</option>
                    </select>
                </div>
            </div>
            <div class="modal-obj-actions">
                <button
                    style="padding:11px 20px;border-radius:6px;border:1px solid #CBD5E1;background:transparent;color:#64748B;cursor:pointer;font-family:inherit;"
                    onclick="cerrarModal()">Cancelar</button>
                <button class="btn-nuevo" onclick="cerrarModal()">Guardar objetivo</button>
            </div>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('menuToggle');
        const overlay = document.getElementById('sidebarOverlay');
        function toggleMenu() { sidebar.classList.contains('open') ? cerrarMenu() : abrirMenu(); }
        function abrirMenu() { sidebar.classList.add('open'); toggle.classList.add('open'); overlay.classList.add('visible'); document.body.style.overflow = 'hidden'; }
        function cerrarMenu() { sidebar.classList.remove('open'); toggle.classList.remove('open'); overlay.classList.remove('visible'); document.body.style.overflow = ''; }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { cerrarMenu(); cerrarModal(); } });
        document.querySelectorAll('.sidebar a').forEach(l => l.addEventListener('click', () => { if (window.innerWidth <= 900) cerrarMenu(); }));

        function abrirModal() { document.getElementById('modalObj').classList.remove('hidden'); }
        function cerrarModal() { document.getElementById('modalObj').classList.add('hidden'); }
        document.getElementById('modalObj').addEventListener('click', function (e) { if (e.target === this) cerrarModal(); });
    </script>

</body>

</html>