<?php
$page = 'perfil';
$extra_css = '<link rel="stylesheet" href="css/perfil.css">';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once 'includes/header.php';

$user_id = $_SESSION['usuario_id'];
$query = $conn->prepare("SELECT nombre, email, fecha_registro FROM usuarios WHERE id_usuario=?");
$query->bind_param("i", $user_id);
$query->execute();
$res = $query->get_result();
$user_data = $res->fetch_assoc();

$nombre = $user_data['nombre'] ?? 'Usuario';
$email = $user_data['email'] ?? 'usuario@email.com';
$fecha_registro = isset($user_data['fecha_registro']) ? date('M Y', strtotime($user_data['fecha_registro'])) : 'Ene 2026';

// Movimientos count
$query_mov = $conn->prepare("SELECT COUNT(*) as total_mov FROM movimientos WHERE id_usuario=?");
$query_mov->bind_param("i", $user_id);
$query_mov->execute();
$res_mov = $query_mov->get_result();
$mov_data = $res_mov->fetch_assoc();
$total_movimientos = $mov_data['total_mov'] ?? 0;

// Objetivos count
$query_obj = $conn->prepare("SELECT COUNT(*) as total_obj FROM metas_ahorro WHERE id_usuario=?");
$query_obj->bind_param("i", $user_id);
$query_obj->execute();
$res_obj = $query_obj->get_result();
$obj_data = $res_obj->fetch_assoc();
$total_objetivos = $obj_data['total_obj'] ?? 0;

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
                            <strong id="nombreMostrado"><?php echo htmlspecialchars($nombre); ?></strong>
                            <span id="emailMostrado"><?php echo htmlspecialchars($email); ?></span>
                        </div>
                        <div class="perfil-divider"></div>
                        <div class="perfil-user-stats">
                            <div class="perfil-user-stat">
                                <span>Miembro desde</span>
                                <span><?php echo $fecha_registro; ?></span>
                            </div>
                            <div class="perfil-user-stat">
                                <span>Movimientos</span>
                                <span><?php echo $total_movimientos; ?></span>
                            </div>
                            <div class="perfil-user-stat">
                                <span>Objetivos activos</span>
                                <span><?php echo $total_objetivos; ?></span>
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
                                    <input type="text" id="inputNombre" value="<?php echo htmlspecialchars($nombre); ?>" disabled>
                                </div>
                                <div class="campo">
                                    <label>Apellido</label>
                                    <input type="text" id="inputApellido" placeholder="Completame" disabled>
                                </div>
                            </div>
                            <div class="campo-row">
                                <div class="campo">
                                    <label>Email</label>
                                    <input type="email" id="inputEmail" value="<?php echo htmlspecialchars($email); ?>" disabled>
                                </div>
                                <div class="campo">
                                    <label>Teléfono</label>
                                    <input type="tel" id="inputTel" placeholder="Completame" disabled>
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