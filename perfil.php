<?php
$page = 'perfil';
$extra_css = '<link rel="stylesheet" href="css/pages/perfil.css">';
require_once 'conexion.php';
if(session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ── Acciones del perfil (PRG) ────────────────────────────────────────────────
if (isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type'])) {
    $user_id = (int)$_SESSION['usuario_id'];

    if ($_POST['form_type'] === 'actualizar_datos') {
        $nuevo_nombre = trim($_POST['nombre'] ?? '');
        $nueva_moneda = $_POST['moneda'] ?? 'ARS';

        if ($nuevo_nombre === '' || mb_strlen($nuevo_nombre) > 50) {
            flash('El nombre es obligatorio (máximo 50 caracteres).');
        } elseif (!in_array($nueva_moneda, ['ARS', 'USD'], true)) {
            flash('Moneda inválida.');
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, moneda_principal = ? WHERE id_usuario = ?");
            $stmt->bind_param("ssi", $nuevo_nombre, $nueva_moneda, $user_id);
            $stmt->execute();
            $_SESSION['usuario_nombre'] = $nuevo_nombre;
            $_SESSION['moneda'] = $nueva_moneda;
            flash('Datos actualizados.', 'success');
        }
    } elseif ($_POST['form_type'] === 'cambiar_clave') {
        $actual = $_POST['clave_actual'] ?? '';
        $nueva = $_POST['clave_nueva'] ?? '';
        $confirmacion = $_POST['clave_confirmacion'] ?? '';

        $stmt = $conn->prepare("SELECT clave FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $hash_actual = $stmt->get_result()->fetch_assoc()['clave'] ?? '';

        // Las cuentas creadas con Google no tienen contraseña: pueden definir una sin la actual
        if ($hash_actual !== '' && !password_verify($actual, $hash_actual)) {
            flash('La contraseña actual no es correcta.');
        } elseif (mb_strlen($nueva) < 8) {
            flash('La nueva contraseña debe tener al menos 8 caracteres.');
        } elseif ($nueva !== $confirmacion) {
            flash('La confirmación no coincide con la nueva contraseña.');
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET clave = ? WHERE id_usuario = ?");
            $stmt->bind_param("si", $hash, $user_id);
            $stmt->execute();
            session_regenerate_id(true);
            flash('Contraseña actualizada.', 'success');
        }
    }

    header('Location: perfil.php');
    exit();
}

require_once 'includes/header.php';

$user_id = (int)$_SESSION['usuario_id'];
$query = $conn->prepare("SELECT nombre, email, fecha_registro, clave, moneda_principal, google_id FROM usuarios WHERE id_usuario=?");
$query->bind_param("i", $user_id);
$query->execute();
$user_data = $query->get_result()->fetch_assoc() ?: [];

$nombre = $user_data['nombre'] ?? 'Usuario';
$email = $user_data['email'] ?? '';
$tiene_clave = !empty($user_data['clave']);
$moneda_usuario = $user_data['moneda_principal'] ?? 'ARS';
$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$fecha_registro = isset($user_data['fecha_registro'])
    ? $meses[(int)date('n', strtotime($user_data['fecha_registro'])) - 1] . ' ' . date('Y', strtotime($user_data['fecha_registro']))
    : '-';

// Movimientos count
$query_mov = $conn->prepare("SELECT COUNT(*) as total_mov FROM movimientos WHERE id_usuario=?");
$query_mov->bind_param("i", $user_id);
$query_mov->execute();
$total_movimientos = $query_mov->get_result()->fetch_assoc()['total_mov'] ?? 0;

// Objetivos activos
$query_obj = $conn->prepare("SELECT COUNT(*) as total_obj FROM metas_ahorro WHERE id_usuario=? AND estado = 'activo' AND fecha_limite >= CURDATE()");
$query_obj->bind_param("i", $user_id);
$query_obj->execute();
$total_objetivos = $query_obj->get_result()->fetch_assoc()['total_obj'] ?? 0;

$inicial = mb_strtoupper(mb_substr(trim($nombre) ?: 'U', 0, 1));
?>

            <div class="page-header">
                <div class="page-header-texto">
                    <h2 class="titulo-con-icono"><img src="iconos/generales/usuario.png" alt="" class="icono-titulo"> Mi perfil</h2>
                    <p class="page-subtitle">Tus datos, seguridad y preferencias</p>
                </div>
            </div>

            <div class="perfil-layout">

                <!-- Columna izquierda -->
                <div>
                    <div class="perfil-sidebar-card">
                        <div class="perfil-banner"></div>
                        <div class="perfil-avatar-wrap">
                            <div class="perfil-avatar perfil-avatar-inicial"><?php echo htmlspecialchars($inicial); ?></div>
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
                                <span><?php echo (int)$total_movimientos; ?></span>
                            </div>
                            <div class="perfil-user-stat">
                                <span>Objetivos activos</span>
                                <span><?php echo (int)$total_objetivos; ?></span>
                            </div>
                            <div class="perfil-user-stat">
                                <span>Moneda</span>
                                <span><?php echo $moneda_usuario === 'USD' ? 'USD (US$)' : 'ARS ($)'; ?></span>
                            </div>
                        </div>
                        <div class="perfil-divider"></div>
                        <div class="perfil-logout-wrap">
                            <button class="btn-logout" onclick="confirmarLogout()"><img src="iconos/generales/puerta.png" alt="" class="icono-boton"> Cerrar sesión</button>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha -->
                <div class="perfil-secciones">

                    <!-- Datos personales -->
                    <form class="seccion-card" id="formDatos" method="POST" action="perfil.php">
                        <input type="hidden" name="form_type" value="actualizar_datos">
                        <div class="seccion-header">
                            <h3 class="titulo-con-icono"><img src="iconos/generales/usuario.png" alt="" class="icono-titulo"> Datos personales</h3>
                            <button type="button" class="btn-editar-seccion" id="btnDatos" onclick="toggleEditar('datos')"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"> Editar</button>
                        </div>
                        <div class="seccion-body">
                            <div class="campo-row">
                                <div class="campo">
                                    <label for="inputNombre">Nombre</label>
                                    <input type="text" id="inputNombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" maxlength="50" required disabled data-editable>
                                </div>
                                <div class="campo">
                                    <label for="inputEmail">Email</label>
                                    <input type="email" id="inputEmail" value="<?php echo htmlspecialchars($email); ?>" disabled>
                                    <span class="campo-ayuda">El email identifica tu cuenta y no se puede cambiar.</span>
                                </div>
                            </div>
                            <div class="campo-row">
                                <div class="campo">
                                    <label for="inputMoneda">Moneda principal</label>
                                    <select id="inputMoneda" name="moneda" disabled data-editable>
                                        <option value="ARS" <?php echo $moneda_usuario === 'ARS' ? 'selected' : ''; ?>>ARS — Peso argentino</option>
                                        <option value="USD" <?php echo $moneda_usuario === 'USD' ? 'selected' : ''; ?>>USD — Dólar estadounidense</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Seguridad -->
                    <form class="seccion-card" id="formSeguridad" method="POST" action="perfil.php">
                        <input type="hidden" name="form_type" value="cambiar_clave">
                        <div class="seccion-header">
                            <h3 class="titulo-con-icono"><img src="iconos/generales/candado.png" alt="" class="icono-titulo"> Seguridad</h3>
                            <button type="button" class="btn-editar-seccion" id="btnSeg" onclick="toggleEditar('seg')"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"> Editar</button>
                        </div>
                        <div class="seccion-body">
                            <?php if ($tiene_clave): ?>
                                <div class="campo">
                                    <label for="inputPassActual">Contraseña actual</label>
                                    <input type="password" id="inputPassActual" name="clave_actual" placeholder="••••••••" autocomplete="current-password" required disabled data-editable>
                                </div>
                            <?php else: ?>
                                <p class="campo-ayuda">Ingresaste con Google: podés definir una contraseña para entrar también con tu email.</p>
                            <?php endif; ?>
                            <div class="campo-row">
                                <div class="campo">
                                    <label for="inputPassNueva">Nueva contraseña</label>
                                    <input type="password" id="inputPassNueva" name="clave_nueva" placeholder="Mínimo 8 caracteres" minlength="8" autocomplete="new-password" required disabled data-editable>
                                </div>
                                <div class="campo">
                                    <label for="inputPassConf">Confirmar contraseña</label>
                                    <input type="password" id="inputPassConf" name="clave_confirmacion" placeholder="Repetila" minlength="8" autocomplete="new-password" required disabled data-editable>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Preferencias -->
                    <div class="seccion-card">
                        <div class="seccion-header">
                            <h3 class="titulo-con-icono"><img src="iconos/generales/tick.png" alt="" class="icono-titulo"> Preferencias</h3>
                        </div>
                        <div class="seccion-body">
                            <div class="toggle-row">
                                <div class="toggle-info">
                                    <strong>Modo oscuro</strong>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="btnModoOscuro">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Zona de peligro -->
                    <div class="seccion-card zona-peligro">
                        <div class="seccion-header">
                            <h3 class="titulo-con-icono"><img src="iconos/generales/triangulopeligro.png" alt="" class="icono-titulo"> Zona de peligro</h3>
                        </div>
                        <div class="seccion-body">
                            <div class="peligro-row">
                                <div class="peligro-info">
                                    <strong>Exportar mis datos</strong>
                                    <span>Descargá un .csv con todos tus movimientos</span>
                                </div>
                                <button id="btnExportarDatos" class="btn-peligro"><img src="iconos/generales/exportar.png" alt="Exportar" class="icono-boton"> Exportar</button>
                            </div>
                            <div class="peligro-row">
                                <div class="peligro-info">
                                    <strong>Borrar historial</strong>
                                    <span>Elimina todos los movimientos (no se puede deshacer)</span>
                                </div>
                                <button id="btnBorrarHistorial" type="button" class="btn-peligro"><img src="iconos/generales/flechaizquierda.png" alt="Eliminar" class="icono-boton"> Borrar historial</button>
                            </div>
                            <div class="peligro-row">
                                <div class="peligro-info">
                                    <strong>Eliminar cuenta</strong>
                                    <span>Borra tu cuenta y todos tus datos permanentemente</span>
                                </div>
                                <button id="btnEliminarCuenta" type="button" class="btn-peligro btn-peligro-fuerte"><img src="iconos/generales/tachodebasura.png" alt="Eliminar cuenta" class="icono-boton"> Eliminar
                                    cuenta</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

<?php 
$extra_js = '<script src="js/utils/exportCSV.js"></script><script src="js/perfil.js"></script>';
require_once 'includes/footer.php'; 
?>