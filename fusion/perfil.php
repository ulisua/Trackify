<?php
$page = 'perfil';
$extra_css = '<link rel="stylesheet" href="css/perfil.css">';
require_once 'includes/header.php';
?>

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
<?php 
$extra_js = '<script src="js/perfil.js"></script>';
require_once 'includes/footer.php'; 
?>