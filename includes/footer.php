    </main>
</div>

<?php require_once __DIR__ . '/categorias_meta.php'; ?>
<?php $return_url_actual = htmlspecialchars($_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'], ENT_QUOTES); ?>

<!-- MODAL NUEVO MOVIMIENTO -->
<div id="modal" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="includes/movimientos_handler.php" data-form-moneda>
        <input type="hidden" id="tipoMovimiento" name="tipoMovimiento" value="">
        <input type="hidden" name="return_url" value="<?php echo $return_url_actual; ?>">
        <h3 id="modalTitulo">Nuevo</h3>

        <div class="monto-moneda">
            <input type="number" step="0.01" min="0.01" id="monto" class="js-monto-visible" placeholder="Monto" required>
            <select id="monedaIngreso" class="js-moneda-form" aria-label="Moneda del monto">
                <option value="ARS">ARS</option>
                <option value="USD">USD</option>
            </select>
        </div>
        <!-- Monto ya convertido a ARS que se envía al servidor -->
        <input type="hidden" id="montoARS" name="monto" class="js-monto-ars">
        <p id="previewConversion" class="preview-conversion js-preview-conversion"></p>

        <div class="custom-select-wrapper" id="customCategoriaWrapper">
            <div class="custom-select-trigger" id="customCategoriaTrigger">Selecciona una categoría</div>
            <div class="custom-select-options" id="customCategoriaOptions"></div>
        </div>
        <input type="hidden" id="categoria" name="categoria" required>

        <input type="text" id="descripcion" name="descripcion" placeholder="Breve descripción" maxlength="255" required>

        <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>

        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL OBJETIVO -->
<div id="modalObjetivo" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="objetivos.php" data-form-moneda>
        <input type="hidden" name="form_type" value="objetivo">
        <input type="hidden" name="return_url" value="<?php echo $return_url_actual; ?>">
        <h3>Nuevo objetivo</h3>

        <input type="text" id="nombre_meta" name="nombre_meta" placeholder="Nombre del objetivo" maxlength="100" required>
        <input type="text" id="desc_meta" name="desc_meta" placeholder="Breve descripción" maxlength="255" required>
        <div class="monto-moneda">
            <input type="number" step="0.01" min="0.01" id="monto_objetivo" class="js-monto-visible" placeholder="Monto objetivo" required>
            <select class="js-moneda-form" aria-label="Moneda del monto">
                <option value="ARS">ARS</option>
                <option value="USD">USD</option>
            </select>
        </div>
        <input type="hidden" name="monto_objetivo" class="js-monto-ars">
        <p class="preview-conversion js-preview-conversion"></p>
        <input type="date" id="fecha_limite" name="fecha_limite" required>

        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL AGREGAR AHORRO -->
<div id="modalAhorro" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="objetivos.php" data-form-moneda>
        <input type="hidden" name="form_type" value="agregar_ahorro">
        <input type="hidden" id="ahorro_id_meta" name="id_meta" value="">
        <h3>Agregar ahorro</h3>
        <p class="modal-ayuda">El aporte se registra también como un gasto de la categoría "Ahorro para meta".</p>

        <div class="monto-moneda">
            <input type="number" step="0.01" min="0.01" id="monto_ahorro" class="js-monto-visible" placeholder="Monto a sumar" required>
            <select class="js-moneda-form" aria-label="Moneda del monto">
                <option value="ARS">ARS</option>
                <option value="USD">USD</option>
            </select>
        </div>
        <input type="hidden" name="monto_ahorro" class="js-monto-ars">
        <p class="preview-conversion js-preview-conversion"></p>

        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL EDITAR OBJETIVO (el monto se precarga en ARS para no alterarlo por redondeo) -->
<div id="modalEditarObjetivo" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="objetivos.php" data-form-moneda>
        <input type="hidden" name="form_type" value="editar_objetivo">
        <input type="hidden" id="edit_id_meta" name="id_meta" value="">
        <h3>Editar objetivo</h3>

        <input type="text" id="edit_nombre_meta" name="nombre_meta" placeholder="Nombre del objetivo" maxlength="100" required>
        <input type="text" id="edit_desc_meta" name="desc_meta" placeholder="Breve descripción" maxlength="255" required>
        <div class="monto-moneda">
            <input type="number" step="0.01" min="0.01" id="edit_monto_objetivo" class="js-monto-visible" placeholder="Monto objetivo" required>
            <select class="js-moneda-form" data-moneda-fija="1" aria-label="Moneda del monto">
                <option value="ARS">ARS</option>
                <option value="USD">USD</option>
            </select>
        </div>
        <input type="hidden" name="monto_objetivo" class="js-monto-ars">
        <p class="preview-conversion js-preview-conversion"></p>
        <input type="date" id="edit_fecha_limite" name="fecha_limite" required>
        <select id="edit_estado" name="estado" aria-label="Estado">
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
            <option value="logrado">Logrado</option>
        </select>

        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL NUEVA CATEGORÍA -->
<div id="modalCategoria" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="categorias.php">
        <input type="hidden" name="form_type" value="nueva_categoria">
        <h3>Nueva categoría</h3>

        <input type="text" id="nombre_categoria" name="nombre_categoria" placeholder="Nombre de la categoría" maxlength="50" required>
        <select id="tipo_categoria" name="tipo_categoria" aria-label="Tipo">
            <option value="gasto">Gasto</option>
            <option value="ingreso">Ingreso</option>
        </select>
        <select id="icono_categoria" name="icono" aria-label="Ícono">
            <?php foreach (obtenerOpcionesIconoCategoria() as $ruta => $nombreIcono): ?>
                <option value="<?php echo htmlspecialchars($ruta); ?>"><?php echo htmlspecialchars($nombreIcono); ?></option>
            <?php endforeach; ?>
        </select>
        <label class="campo-color">
            Color de acento
            <input type="color" id="color_categoria" name="color" value="#EA73F5">
        </label>

        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL EDITAR CATEGORÍA -->
<div id="modalEditarCategoria" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="categorias.php">
        <input type="hidden" name="form_type" value="editar_categoria">
        <input type="hidden" id="edit_id_categoria" name="id_categoria" value="">
        <h3>Editar categoría</h3>

        <input type="text" id="edit_nombre_categoria" name="nombre_categoria" placeholder="Nombre de la categoría" maxlength="50" required>
        <select id="edit_tipo_categoria" name="tipo_categoria" aria-label="Tipo">
            <option value="gasto">Gasto</option>
            <option value="ingreso">Ingreso</option>
        </select>
        <select id="edit_icono_categoria" name="icono" aria-label="Ícono">
            <?php foreach (obtenerOpcionesIconoCategoria() as $ruta => $nombreIcono): ?>
                <option value="<?php echo htmlspecialchars($ruta); ?>"><?php echo htmlspecialchars($nombreIcono); ?></option>
            <?php endforeach; ?>
        </select>
        <label class="campo-color">
            Color de acento
            <input type="color" id="edit_color_categoria" name="color" value="#EA73F5">
        </label>

        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL EDITAR MOVIMIENTO (el monto se precarga en ARS para no alterarlo por redondeo) -->
<div id="modalEditarMovimiento" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="includes/movimientos_handler.php" data-form-moneda>
        <input type="hidden" name="form_type" value="editar_movimiento">
        <input type="hidden" name="return_url" value="<?php echo $return_url_actual; ?>">
        <input type="hidden" id="edit_mov_id" name="id_movimiento" value="">
        <input type="hidden" id="edit_mov_tipo" value="">
        <h3 id="modalEditarMovimientoTitulo">Editar movimiento</h3>

        <div class="monto-moneda">
            <input type="number" step="0.01" min="0.01" id="edit_mov_monto" class="js-monto-visible" placeholder="Monto" required>
            <select id="edit_mov_moneda" class="js-moneda-form" data-moneda-fija="1" aria-label="Moneda del monto">
                <option value="ARS">ARS</option>
                <option value="USD">USD</option>
            </select>
        </div>
        <input type="hidden" name="monto" class="js-monto-ars">
        <p class="preview-conversion js-preview-conversion"></p>

        <div class="custom-select-wrapper" id="customEditMovCategoriaWrapper">
            <div class="custom-select-trigger" id="customEditMovCategoriaTrigger">Selecciona una categoría</div>
            <div class="custom-select-options" id="customEditMovCategoriaOptions"></div>
        </div>
        <input type="hidden" id="edit_mov_categoria" name="categoria" required>

        <input type="text" id="edit_mov_descripcion" name="descripcion" placeholder="Breve descripción" maxlength="255" required>

        <input type="date" id="edit_mov_fecha" name="fecha" required>

        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

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
            <input type="hidden" name="return_url" value="<?php echo $return_url_actual; ?>">

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

<footer class="footer">
    <p>Trackify © <?php echo date('Y'); ?></p>
</footer>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js"></script>
<!-- Flatpickr (su hoja de estilos se carga en el header) -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>

<script>
    // Token CSRF: se agrega a todos los formularios POST y queda disponible para fetch
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    document.querySelectorAll('form').forEach(form => {
        if ((form.getAttribute('method') || '').toUpperCase() !== 'POST' || form.querySelector('input[name="csrf_token"]')) return;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = window.csrfToken;
        form.appendChild(input);
    });

    // Inicializar Flatpickr separando Objetivos de Movimientos
    document.addEventListener('DOMContentLoaded', function() {
        const pad = n => String(n).padStart(2, '0');
        const ahora = new Date();
        // Fecha local (toISOString usaría UTC y de noche en Argentina daría el día siguiente)
        const today = `${ahora.getFullYear()}-${pad(ahora.getMonth() + 1)}-${pad(ahora.getDate())}`;
        const opcionesBase = { dateFormat: "Y-m-d", altInput: true, altFormat: "d/m/Y", locale: "es", disableMobile: true };

        // 1. Inputs de Objetivos (fecha_limite): PERMITE HOY Y FECHAS FUTURAS (bloquea pasadas)
        const objDateInputs = document.querySelectorAll("#fecha_limite, #edit_fecha_limite");
        objDateInputs.forEach(input => {
            input.min = today;
            if (!input.placeholder) input.placeholder = "Selecciona fecha límite";
            if (!input.value || input.value < today) input.value = today;
        });
        if (objDateInputs.length > 0 && window.flatpickr) {
            flatpickr(objDateInputs, { ...opcionesBase, minDate: today });
        }

        // 2. Inputs de Movimientos (fecha): PERMITE PASADAS Y HOY (BLOQUEA FUTURAS)
        const movDateInputs = document.querySelectorAll("#fecha, #edit_mov_fecha");
        movDateInputs.forEach(input => {
            input.max = today;
            if (!input.placeholder) input.placeholder = "Selecciona una fecha";
            if (!input.value || input.value > today) input.value = today;
        });
        if (movDateInputs.length > 0 && window.flatpickr) {
            flatpickr(movDateInputs, { ...opcionesBase, maxDate: today });
        }

        // 3. Filtros de fecha (sin restricciones)
        const filtroDateInputs = document.querySelectorAll(".js-fecha-filtro");
        if (filtroDateInputs.length > 0 && window.flatpickr) {
            flatpickr(filtroDateInputs, { ...opcionesBase, allowInput: false });
        }

        // Cerrar calendario al hacer scroll en cualquier contenedor
        window.addEventListener('scroll', function(e) {
            if (e.target.closest && e.target.closest('.flatpickr-calendar')) return;
            document.querySelectorAll("input").forEach(input => {
                if(input._flatpickr && input._flatpickr.isOpen) {
                    input._flatpickr.close();
                }
            });
        }, true);
    });

    const swalBase = {
        buttonsStyling: false,
        customClass: {
            popup: 'swal-trackify',
            confirmButton: 'btn swal-btn-primary',
            cancelButton: 'btn swal-btn-cancel'
        }
    };

    // Función global para confirmaciones con SweetAlert2
    function confirmarEliminacion(e, mensaje) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            ...swalBase,
            title: '¿Estás seguro?',
            text: mensaje || "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            customClass: { ...swalBase.customClass, confirmButton: 'btn swal-btn-danger' }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    function confirmarLogout() {
        Swal.fire({
            ...swalBase,
            title: '¿Cerrar sesión?',
            text: "Tendrás que volver a ingresar tus credenciales.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Salir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }

    <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    // Mensaje de la última operación (patrón Post/Redirect/Get)
    document.addEventListener('DOMContentLoaded', () => {
        const flash = <?php echo json_encode($flash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
        if (flash.tipo === 'success') {
            // Aviso compacto abajo a la derecha (estilos en forms.css: .swal-toast-trackify)
            Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: flash.mensaje, showConfirmButton: false, timer: 2600, timerProgressBar: true, customClass: { popup: 'swal-toast-trackify' } });
        } else {
            Swal.fire({ ...swalBase, icon: 'error', title: 'No se pudo completar', text: flash.mensaje });
        }
    });
    <?php endif; ?>
</script>

<script src="js/main.js?v=5"></script>
<script src="js/moneda.js?v=2"></script>
<?php if(isset($extra_js)) echo $extra_js; ?>

</body>
</html>
