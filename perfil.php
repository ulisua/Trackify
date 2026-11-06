<?php
$page = 'perfil';
$extra_css = '<link rel="stylesheet" href="css/pages/perfil.css">';
require_once 'conexion.php';

// Países disponibles en "Datos personales" (el valor guardado es el texto)
const PAISES_PERFIL = ['Argentina', 'Uruguay', 'Chile', 'Paraguay', 'Bolivia', 'Brasil', 'Perú', 'Colombia', 'México', 'España', 'Estados Unidos', 'Otro'];

// ── Guardar datos personales (PRG) ───────────────────────────────────────────
// La contraseña se cambia por separado, vía api/cambiar_password.php (ver js/perfil.js).
if (isset($_SESSION['usuario_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_type'] ?? '') === 'actualizar_datos') {
    $user_id = (int)$_SESSION['usuario_id'];

    $nuevo_nombre = trim($_POST['nombre'] ?? '');
    $apellido     = trim($_POST['apellido'] ?? '');
    $telefono     = trim($_POST['telefono'] ?? '');
    $pais         = trim($_POST['pais'] ?? '');
    $nueva_moneda = $_POST['moneda'] ?? 'ARS';

    if ($nuevo_nombre === '' || mb_strlen($nuevo_nombre) > 50) {
        flash('El nombre es obligatorio (máximo 50 caracteres).');
    } elseif (mb_strlen($apellido) > 50) {
        flash('El apellido puede tener hasta 50 caracteres.');
    } elseif ($telefono !== '' && !preg_match('/^\+?[0-9\s\-()]{6,30}$/', $telefono)) {
        flash('Revisá el teléfono: solo números, espacios, guiones, paréntesis y un + inicial.');
    } elseif ($pais !== '' && !in_array($pais, PAISES_PERFIL, true)) {
        flash('País inválido.');
    } elseif (!in_array($nueva_moneda, ['ARS', 'USD'], true)) {
        flash('Moneda inválida.');
    } else {
        // Los campos opcionales vacíos se guardan como NULL
        $apellido = $apellido !== '' ? $apellido : null;
        $telefono = $telefono !== '' ? $telefono : null;
        $pais     = $pais !== '' ? $pais : null;

        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ?, pais = ?, moneda_principal = ? WHERE id_usuario = ?");
        $stmt->bind_param("sssssi", $nuevo_nombre, $apellido, $telefono, $pais, $nueva_moneda, $user_id);
        $stmt->execute();
        $_SESSION['usuario_nombre'] = $nuevo_nombre;
        $_SESSION['moneda'] = $nueva_moneda;
        flash('Datos actualizados.', 'success');
    }

    header('Location: perfil.php');
    exit();
}

require_once 'includes/header.php';

$user_id = (int)$_SESSION['usuario_id'];
$query = $conn->prepare("SELECT nombre, apellido, email, telefono, pais, fecha_registro, clave, moneda_principal FROM usuarios WHERE id_usuario=?");
$query->bind_param("i", $user_id);
$query->execute();
$user_data = $query->get_result()->fetch_assoc() ?: [];

$nombre         = $user_data['nombre'] ?? 'Usuario';
$apellido       = $user_data['apellido'] ?? '';
$email          = $user_data['email'] ?? '';
$telefono       = $user_data['telefono'] ?? '';
$pais           = $user_data['pais'] ?? '';
$tiene_clave    = !empty($user_data['clave']); // las cuentas creadas con Google no tienen contraseña
$moneda_usuario = $user_data['moneda_principal'] ?? 'ARS';
$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$fecha_registro = isset($user_data['fecha_registro'])
    ? $meses[(int)date('n', strtotime($user_data['fecha_registro'])) - 1] . ' ' . date('Y', strtotime($user_data['fecha_registro']))
    : '-';

// Estadísticas de la tarjeta lateral (una sola consulta)
$query_stats = $conn->prepare("SELECT
    (SELECT COUNT(*) FROM movimientos WHERE id_usuario = ?) AS total_mov,
    (SELECT COUNT(*) FROM metas_ahorro WHERE id_usuario = ? AND estado = 'activo' AND fecha_limite >= CURDATE()) AS total_obj");
$query_stats->bind_param("ii", $user_id, $user_id);
$query_stats->execute();
$stats = $query_stats->get_result()->fetch_assoc();

$nombre_completo = trim($nombre . ' ' . $apellido);
$inicial = mb_strtoupper(mb_substr(trim($nombre) ?: 'U', 0, 1));

// Campos de contraseña nueva + confirmación (compartidos por "cambiar" y "crear")
function camposClaveNueva() {
    ?>
    <div class="campo">
        <label for="pwNueva">Contraseña nueva</label>
        <div class="pw-wrap">
            <input type="password" id="pwNueva" autocomplete="new-password">
            <button type="button" class="pw-toggle" data-target="pwNueva" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
        </div>
        <div class="pw-fuerza" aria-hidden="true"><span id="pwFuerzaBarra"></span></div>
        <div class="pw-fuerza-txt" id="pwFuerzaTxt" aria-live="polite"></div>
    </div>
    <div class="campo">
        <label for="pwConf">Confirmar contraseña nueva</label>
        <div class="pw-wrap">
            <input type="password" id="pwConf" autocomplete="new-password">
            <button type="button" class="pw-toggle" data-target="pwConf" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
        </div>
    </div>
    <?php
}
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
                            <strong id="nombreMostrado"><?php echo htmlspecialchars($nombre_completo); ?></strong>
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
                                <span><?php echo (int)$stats['total_mov']; ?></span>
                            </div>
                            <div class="perfil-user-stat">
                                <span>Objetivos activos</span>
                                <span><?php echo (int)$stats['total_obj']; ?></span>
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

                    <!-- Datos personales: "Editar" habilita los campos y pasa a "Guardar" -->
                    <form class="seccion-card" id="formDatos" method="POST" action="perfil.php">
                        <input type="hidden" name="form_type" value="actualizar_datos">
                        <div class="seccion-header">
                            <h3 class="titulo-con-icono"><img src="iconos/generales/usuario.png" alt="" class="icono-titulo"> Datos personales</h3>
                            <button type="button" class="btn-editar-seccion" id="btnDatos"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"> Editar</button>
                        </div>
                        <div class="seccion-body">
                            <div class="campo-row">
                                <div class="campo">
                                    <label for="inputNombre">Nombre</label>
                                    <input type="text" id="inputNombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" maxlength="50" autocomplete="given-name" required disabled data-editable>
                                </div>
                                <div class="campo">
                                    <label for="inputApellido">Apellido</label>
                                    <input type="text" id="inputApellido" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" maxlength="50" placeholder="Completame" autocomplete="family-name" disabled data-editable>
                                </div>
                            </div>
                            <div class="campo-row">
                                <div class="campo">
                                    <label for="inputEmail">Email</label>
                                    <input type="email" id="inputEmail" value="<?php echo htmlspecialchars($email); ?>" disabled>
                                    <span class="campo-ayuda">El email identifica tu cuenta y no se puede cambiar.</span>
                                </div>
                                <div class="campo">
                                    <label for="inputTel">Teléfono</label>
                                    <input type="tel" id="inputTel" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>" maxlength="30" placeholder="Completame" autocomplete="tel" disabled data-editable>
                                </div>
                            </div>
                            <div class="campo-row">
                                <div class="campo">
                                    <label for="inputPais">País</label>
                                    <select id="inputPais" name="pais" disabled data-editable>
                                        <option value="" <?php echo $pais === '' ? 'selected' : ''; ?>>Sin especificar</option>
                                        <?php foreach (PAISES_PERFIL as $opcion_pais): ?>
                                            <option value="<?php echo htmlspecialchars($opcion_pais); ?>" <?php echo $pais === $opcion_pais ? 'selected' : ''; ?>><?php echo htmlspecialchars($opcion_pais); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
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

                    <!-- Seguridad: cambiar la contraseña, o crearla si la cuenta es de Google -->
                    <div class="seccion-card" id="seccionSeguridad">
                        <div class="seccion-header">
                            <h3 class="titulo-con-icono"><img src="iconos/generales/candado.png" alt="" class="icono-titulo"> Seguridad</h3>
                            <button type="button" class="btn-editar-seccion" id="btnSeg" aria-expanded="false" aria-controls="pwPanel"><img src="iconos/generales/lapiz.png" alt="" class="icono-boton"> <?php echo $tiene_clave ? 'Cambiar contraseña' : 'Crear contraseña'; ?></button>
                        </div>
                        <div class="seccion-body">
                            <?php if ($tiene_clave): ?>
                                <p class="pw-intro">Tu cuenta tiene contraseña. Para cambiarla, primero te pedimos la actual.</p>
                            <?php else: ?>
                                <p class="pw-intro">Entraste con Google, así que tu cuenta todavía no tiene contraseña. Para crear una, confirmamos que sos vos con un código de 6 dígitos que enviamos a <strong><?php echo htmlspecialchars(mascaraEmail($email)); ?></strong>.</p>
                            <?php endif; ?>

                            <div class="pw-msg" id="pwMsg" role="alert" hidden></div>

                            <div id="pwPanel" class="pw-panel" hidden>
                            <?php if ($tiene_clave): ?>
                                <form id="formCambiar" class="pw-form" novalidate>
                                    <input type="text" class="pw-oculto" name="username" value="<?php echo htmlspecialchars($email); ?>" autocomplete="username" readonly tabindex="-1" aria-hidden="true">
                                    <div class="campo">
                                        <label for="pwActual">Contraseña actual</label>
                                        <div class="pw-wrap">
                                            <input type="password" id="pwActual" autocomplete="current-password">
                                            <button type="button" class="pw-toggle" data-target="pwActual" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                                        </div>
                                    </div>
                                    <?php camposClaveNueva(); ?>
                                    <ul class="pw-reqs">
                                        <li id="reqLargo">Al menos 8 caracteres</li>
                                        <li id="reqDistinta">Distinta de la contraseña actual</li>
                                        <li id="reqCoincide">Coincide con la confirmación</li>
                                    </ul>
                                    <div class="pw-acciones">
                                        <button type="submit" class="btn btn-guardar-modal" id="btnGuardarClave">Guardar contraseña</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div id="pwPaso1" class="pw-acciones">
                                    <button type="button" class="btn btn-guardar-modal" id="btnEnviarCodigo">Enviar código</button>
                                    <button type="button" class="btn cancel" id="btnYaTengoCodigo">Ya tengo un código</button>
                                </div>
                                <form id="formCrear" class="pw-form" hidden novalidate>
                                    <input type="text" class="pw-oculto" name="username" value="<?php echo htmlspecialchars($email); ?>" autocomplete="username" readonly tabindex="-1" aria-hidden="true">
                                    <div class="campo">
                                        <label for="pwCodigo">Código de 6 dígitos</label>
                                        <input type="text" id="pwCodigo" class="pw-codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000">
                                    </div>
                                    <?php camposClaveNueva(); ?>
                                    <ul class="pw-reqs">
                                        <li id="reqLargo">Al menos 8 caracteres</li>
                                        <li id="reqCoincide">Coincide con la confirmación</li>
                                    </ul>
                                    <div class="pw-acciones">
                                        <button type="submit" class="btn btn-guardar-modal" id="btnCrearClave">Crear contraseña</button>
                                        <button type="button" class="btn cancel" id="btnReenviar">Reenviar código</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>

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
                                    <input type="checkbox" id="btnModoOscuro" aria-label="Modo oscuro">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Zona de peligro (los modales de confirmación están en includes/footer.php) -->
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
                                <button id="btnExportarDatos" type="button" class="btn-peligro"><img src="iconos/generales/exportar.png" alt="" class="icono-boton"> Exportar</button>
                            </div>
                            <div class="peligro-row">
                                <div class="peligro-info">
                                    <strong>Borrar historial</strong>
                                    <span>Elimina todos los movimientos (no se puede deshacer)</span>
                                </div>
                                <button id="btnBorrarHistorial" type="button" class="btn-peligro"><img src="iconos/generales/flechaizquierda.png" alt="" class="icono-boton"> Borrar historial</button>
                            </div>
                            <div class="peligro-row">
                                <div class="peligro-info">
                                    <strong>Eliminar cuenta</strong>
                                    <span>Borra tu cuenta y todos tus datos permanentemente</span>
                                </div>
                                <button id="btnEliminarCuenta" type="button" class="btn-peligro btn-peligro-fuerte"><img src="iconos/generales/tachodebasura.png" alt="" class="icono-boton"> Eliminar cuenta</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

<!-- Modales de la zona de peligro (los maneja js/perfil.js) -->
<!-- MODAL BORRAR HISTORIAL COMPLETO -->
<div id="modalBorrarHistorial" class="modal hidden">
    <div class="modal-content modal-confirmacion">
        <h3 class="modal-peligro-titulo">Borrar todo el historial</h3>

        <div class="aviso-peligro">
            <p class="aviso-peligro-titulo"><img src="iconos/generales/triangulopeligro.png" alt="" class="icono-inline"> Advertencia</p>
            <p>Esta acción eliminará <strong>TODOS</strong> tus movimientos (ingresos y gastos) permanentemente.</p>
            <p><strong>No se puede deshacer.</strong></p>
        </div>

        <p class="modal-ayuda">Para confirmar, escribí "SI, ESTOY SEGURO" en el campo de abajo:</p>

        <form id="formBorrarHistorial" class="form-confirmacion" method="POST" action="includes/movimientos_handler.php">
            <input type="hidden" name="form_type" value="borrar_historial_completo">
            <input type="hidden" name="return_url" value="perfil.php">

            <input type="text" id="inputConfirmacionBorrar" name="confirmacion_borrar" class="input-confirmacion" placeholder="SI, ESTOY SEGURO" autocomplete="off" required>

            <div class="modal-actions">
                <button type="button" class="btn cancel" onclick="cerrarModalBorrar()">Cancelar</button>
                <button type="submit" class="btn btn-peligro-solido" id="btnConfirmarBorrar" disabled>Eliminar todo</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ELIMINAR CUENTA -->
<div id="modalEliminarCuenta" class="modal hidden">
    <div class="modal-content modal-confirmacion">
        <h3 class="modal-peligro-titulo">Eliminar cuenta</h3>

        <div class="aviso-peligro">
            <p class="aviso-peligro-titulo"><img src="iconos/generales/triangulopeligro.png" alt="" class="icono-inline"> Advertencia crítica</p>
            <p>Esta acción eliminará <strong>permanentemente tu cuenta</strong>, tus movimientos, objetivos y categorías propias.</p>
            <p><strong>No se puede deshacer.</strong></p>
        </div>

        <p class="modal-ayuda">Para confirmar, escribí "ELIMINAR MI CUENTA" en el campo de abajo:</p>

        <form id="formEliminarCuentaModal" class="form-confirmacion" method="POST" action="includes/movimientos_handler.php">
            <input type="hidden" name="form_type" value="eliminar_cuenta">

            <input type="text" id="inputConfirmacionEliminarCuenta" name="confirmacion_eliminar" class="input-confirmacion" placeholder="ELIMINAR MI CUENTA" autocomplete="off" required>

            <div class="modal-actions">
                <button type="button" class="btn cancel" onclick="cerrarModalEliminarCuenta()">Cancelar</button>
                <button type="submit" class="btn btn-peligro-solido" id="btnConfirmarEliminarCuenta" disabled>Eliminar cuenta</button>
            </div>
        </form>
    </div>
</div>

<?php
$extra_js = '<script src="js/utils/exportCSV.js"></script><script src="js/perfil.js?v=2"></script>';
require_once 'includes/footer.php';
