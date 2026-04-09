<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Categorías - Trackify</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* ===== CATEGORÍAS ===== */
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
            background: #084734;
            color: #CFF27C;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-nuevo:hover {
            opacity: 0.88;
            transform: translateY(-1px);
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 24px;
            background: #F1F5F9;
            padding: 5px;
            border-radius: 8px;
            width: fit-content;
        }

        .tab {
            padding: 9px 20px;
            border-radius: 6px;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 500;
            color: #64748B;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tab.active {
            background: white;
            color: #1E1B26;
            font-weight: 600;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        /* Grid de categorías */
        .cat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .cat-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 20px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .cat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            border-radius: 10px 0 0 10px;
        }

        .cat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.07);
        }

        .cat-icon {
            font-size: 1.8rem;
            line-height: 1;
        }

        .cat-nombre {
            font-weight: 600;
            color: #1E1B26;
            font-size: 1rem;
        }

        .cat-stats {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .cat-monto {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .cat-cant {
            font-size: 0.8rem;
            color: #94A3B8;
        }

        .cat-acciones {
            display: flex;
            gap: 6px;
            margin-top: 4px;
        }

        .cat-acciones button {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 0.8rem;
            cursor: pointer;
            font-family: inherit;
            color: #64748B;
            transition: all 0.15s;
        }

        .cat-acciones button:hover {
            background: #E2E8F0;
            color: #1E1B26;
        }

        .cat-acciones .btn-del:hover {
            background: #FFF1F2;
            border-color: #FECDD3;
            color: #BE123C;
        }

        /* Colores por categoría */
        .cat-comida::before {
            background: #F97316;
        }

        .cat-comida .cat-monto {
            color: #F97316;
        }

        .cat-transporte::before {
            background: #3B82F6;
        }

        .cat-transporte .cat-monto {
            color: #3B82F6;
        }

        .cat-servicios::before {
            background: #8B5CF6;
        }

        .cat-servicios .cat-monto {
            color: #8B5CF6;
        }

        .cat-entrete::before {
            background: #EC4899;
        }

        .cat-entrete .cat-monto {
            color: #EC4899;
        }

        .cat-salud::before {
            background: #10B981;
        }

        .cat-salud .cat-monto {
            color: #10B981;
        }

        .cat-trabajo::before {
            background: #CFF27C;
        }

        .cat-trabajo .cat-monto {
            color: #084734;
        }

        .cat-freelance::before {
            background: #EA73F5;
        }

        .cat-freelance .cat-monto {
            color: #9B0ABA;
        }

        .cat-otros::before {
            background: #94A3B8;
        }

        .cat-otros .cat-monto {
            color: #64748B;
        }

        /* Barra de progreso mini */
        .cat-barra {
            height: 4px;
            background: #F1F5F9;
            border-radius: 99px;
            overflow: hidden;
            margin-top: 4px;
        }

        .cat-barra-fill {
            height: 100%;
            border-radius: 99px;
        }

        /* Sección de resumen */
        .resumen-cats {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            padding: 22px 24px;
            margin-bottom: 28px;
        }

        .resumen-cats h3 {
            margin: 0 0 16px 0;
            font-size: 1rem;
            color: #64748B;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .resumen-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #F8FAFC;
            gap: 12px;
        }

        .resumen-row:last-child {
            border-bottom: none;
        }

        .resumen-cat-nombre {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            color: #1E1B26;
            font-weight: 500;
            min-width: 140px;
        }

        .resumen-barra {
            flex: 1;
            height: 8px;
            background: #F1F5F9;
            border-radius: 99px;
            overflow: hidden;
        }

        .resumen-barra-fill {
            height: 100%;
            border-radius: 99px;
        }

        .resumen-pct {
            font-size: 0.85rem;
            color: #94A3B8;
            min-width: 36px;
            text-align: right;
        }

        /* Modal nueva categoría */
        .modal-cat {
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

        .modal-cat.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .modal-cat-content {
            background: white;
            border-radius: 10px;
            padding: 28px 24px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .modal-cat-content h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.85rem;
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
            border-color: #084734;
            background: white;
            box-shadow: 0 0 0 3px rgba(8, 71, 52, 0.1);
        }

        .emoji-picker {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .emoji-opt {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            border: 2px solid #E2E8F0;
            background: white;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }

        .emoji-opt:hover,
        .emoji-opt.sel {
            border-color: #084734;
            background: #F0FDF4;
        }

        .modal-cat-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 4px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .cat-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            }

            .resumen-cat-nombre {
                min-width: 110px;
                font-size: 0.88rem;
            }
        }

        @media (max-width: 480px) {
            .cat-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .tabs {
                width: 100%;
            }

            .tab {
                flex: 1;
                text-align: center;
                padding: 9px 10px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-nuevo {
                width: 100%;
                text-align: center;
            }

            .resumen-barra {
                display: none;
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
                <a class="active">Categorías</a>
                <a href="objetivos.html">Objetivos</a>
                <a href="perfil.html">Perfil</a>
            </nav>
        </aside>

        <main class="content">

            <div class="page-header">
                <h2>🏷️ Categorías</h2>
                <button class="btn-nuevo" onclick="abrirModal()">+ Nueva categoría</button>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="switchTab('gastos', this)">Gastos</button>
                <button class="tab" onclick="switchTab('ingresos', this)">Ingresos</button>
            </div>

            <!-- Resumen distribución -->
            <div class="resumen-cats">
                <h3>Distribución del mes</h3>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">🍔 Comida</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:38%;background:#F97316"></div>
                    </div>
                    <span class="resumen-pct">38%</span>
                </div>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">🚌 Transporte</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:18%;background:#3B82F6"></div>
                    </div>
                    <span class="resumen-pct">18%</span>
                </div>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">💡 Servicios</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:25%;background:#8B5CF6"></div>
                    </div>
                    <span class="resumen-pct">25%</span>
                </div>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">🎬 Entretenimiento</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:10%;background:#EC4899"></div>
                    </div>
                    <span class="resumen-pct">10%</span>
                </div>
                <div class="resumen-row">
                    <span class="resumen-cat-nombre">🏥 Salud</span>
                    <div class="resumen-barra">
                        <div class="resumen-barra-fill" style="width:9%;background:#10B981"></div>
                    </div>
                    <span class="resumen-pct">9%</span>
                </div>
            </div>

            <!-- Grid de categorías — GASTOS -->
            <div class="cat-grid" id="gridGastos">
                <div class="cat-card cat-comida">
                    <div class="cat-icon">🍔</div>
                    <div class="cat-nombre">Comida</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$32.300</span>
                        <span class="cat-cant">14 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:38%;background:#F97316"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-transporte">
                    <div class="cat-icon">🚌</div>
                    <div class="cat-nombre">Transporte</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$15.200</span>
                        <span class="cat-cant">8 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:18%;background:#3B82F6"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-servicios">
                    <div class="cat-icon">💡</div>
                    <div class="cat-nombre">Servicios</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$21.000</span>
                        <span class="cat-cant">3 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:25%;background:#8B5CF6"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-entrete">
                    <div class="cat-icon">🎬</div>
                    <div class="cat-nombre">Entretenimiento</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$8.500</span>
                        <span class="cat-cant">5 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:10%;background:#EC4899"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-salud">
                    <div class="cat-icon">🏥</div>
                    <div class="cat-nombre">Salud</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$7.500</span>
                        <span class="cat-cant">2 movimientos</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:9%;background:#10B981"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-otros">
                    <div class="cat-icon">📦</div>
                    <div class="cat-nombre">Otros</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$500</span>
                        <span class="cat-cant">1 movimiento</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:1%;background:#94A3B8"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
            </div>

            <!-- Grid de categorías — INGRESOS (oculto por defecto) -->
            <div class="cat-grid" id="gridIngresos" style="display:none">
                <div class="cat-card cat-trabajo">
                    <div class="cat-icon">💼</div>
                    <div class="cat-nombre">Trabajo</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$80.000</span>
                        <span class="cat-cant">1 movimiento</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:67%;background:#CFF27C"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
                <div class="cat-card cat-freelance">
                    <div class="cat-icon">🖥️</div>
                    <div class="cat-nombre">Freelance</div>
                    <div class="cat-stats">
                        <span class="cat-monto">$40.000</span>
                        <span class="cat-cant">1 movimiento</span>
                    </div>
                    <div class="cat-barra">
                        <div class="cat-barra-fill" style="width:33%;background:#EA73F5"></div>
                    </div>
                    <div class="cat-acciones">
                        <button>✏️ Editar</button>
                        <button class="btn-del">🗑️</button>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <footer class="footer">
        <p>Trackify © 2026</p>
    </footer>

    <!-- MODAL NUEVA CATEGORÍA -->
    <div class="modal-cat hidden" id="modalCat">
        <div class="modal-cat-content">
            <h3>Nueva categoría</h3>
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" placeholder="Ej: Educación">
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select>
                    <option>Gasto</option>
                    <option>Ingreso</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ícono</label>
                <div class="emoji-picker">
                    <button class="emoji-opt sel" onclick="selEmoji(this)">📦</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">🍔</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">🚌</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">💡</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">🎬</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">🏥</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">💼</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">🖥️</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">📚</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">✈️</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">🏋️</button>
                    <button class="emoji-opt" onclick="selEmoji(this)">🐾</button>
                </div>
            </div>
            <div class="modal-cat-actions">
                <button class="modal-actions cancel"
                    style="padding:11px 20px;border-radius:6px;border:1px solid #CBD5E1;background:transparent;color:#64748B;cursor:pointer;font-family:inherit;"
                    onclick="cerrarModal()">Cancelar</button>
                <button class="btn-nuevo" onclick="cerrarModal()">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        // Sidebar
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('menuToggle');
        const overlay = document.getElementById('sidebarOverlay');
        function toggleMenu() { sidebar.classList.contains('open') ? cerrarMenu() : abrirMenu(); }
        function abrirMenu() { sidebar.classList.add('open'); toggle.classList.add('open'); overlay.classList.add('visible'); document.body.style.overflow = 'hidden'; }
        function cerrarMenu() { sidebar.classList.remove('open'); toggle.classList.remove('open'); overlay.classList.remove('visible'); document.body.style.overflow = ''; }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { cerrarMenu(); cerrarModal(); } });
        document.querySelectorAll('.sidebar a').forEach(l => l.addEventListener('click', () => { if (window.innerWidth <= 900) cerrarMenu(); }));

        // Tabs
        function switchTab(tipo, btn) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('gridGastos').style.display = tipo === 'gastos' ? 'grid' : 'none';
            document.getElementById('gridIngresos').style.display = tipo === 'ingresos' ? 'grid' : 'none';
        }

        // Modal
        function abrirModal() { document.getElementById('modalCat').classList.remove('hidden'); }
        function cerrarModal() { document.getElementById('modalCat').classList.add('hidden'); }
        document.getElementById('modalCat').addEventListener('click', function (e) { if (e.target === this) cerrarModal(); });

        // Emoji picker
        function selEmoji(btn) {
            document.querySelectorAll('.emoji-opt').forEach(b => b.classList.remove('sel'));
            btn.classList.add('sel');
        }
    </script>

</body>

</html>