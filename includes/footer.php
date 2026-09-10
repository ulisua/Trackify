    </main>
</div>

<?php require_once __DIR__ . '/categorias_meta.php'; ?>

<!-- MODAL -->
<div id="modal" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="includes/movimientos_handler.php" onsubmit="return convertirAntesDeGuardar()">
        <input type="hidden" id="tipoMovimiento" name="tipoMovimiento" value="">
        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'], ENT_QUOTES); ?>">
        <h3 id="modalTitulo">Nuevo</h3>
        
        <!-- Selector de moneda del movimiento -->
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:4px;">
            <input type="number" step="0.01" id="monto" placeholder="Monto" required style="flex:1;">
            <select id="monedaIngreso" style="padding:14px 12px;border:1px solid var(--border-color, #E2E8F0);border-radius:6px;font-size:1rem;font-family:inherit;background:var(--input-bg, #F8FAFC);color:var(--input-text, #1E1B26);outline:none;min-width:95px;">
                <option value="ARS">🇦🇷 ARS</option>
                <option value="USD">🇺🇸 USD</option>
            </select>
        </div>
        <!-- Monto ya convertido a ARS que se envía al servidor -->
        <input type="hidden" id="montoARS" name="monto">

        <div class="custom-select-wrapper" id="customCategoriaWrapper">
            <div class="custom-select-trigger" id="customCategoriaTrigger">Selecciona una categoría</div>
            <div class="custom-select-options" id="customCategoriaOptions"></div>
        </div>
        <input type="hidden" id="categoria" name="categoria" required>

        <input type="text" id="descripcion" name="descripcion" placeholder="Breve descripción" required>
        
        <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>

        <!-- Conversión estimada en tiempo real -->
        <p id="previewConversion" style="font-size:.85rem;color:var(--text-secondary, #64748b);margin:0 0 6px;min-height:18px;"></p>
        
        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL OBJETIVO -->
<div id="modalObjetivo" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="">
        <input type="hidden" name="form_type" value="objetivo">
        <h3>Ingresar objetivo</h3>
        
        <input type="text" id="nombre_meta" name="nombre_meta" placeholder="Nombre del objetivo" required>
        <input type="text" id="desc_meta" name="desc_meta" placeholder="Breve descripción" required>
        <input type="number" step="0.01" id="monto_objetivo" name="monto_objetivo" placeholder="Monto objetivo ($)" required>
        <input type="date" id="fecha_limite" name="fecha_limite" required>
        
        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL AGREGAR AHORRO -->
<div id="modalAhorro" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="objetivos.php">
        <input type="hidden" name="form_type" value="agregar_ahorro">
        <input type="hidden" id="ahorro_id_meta" name="id_meta" value="">
        <h3>Agregar ahorro</h3>
        
        <input type="number" step="0.01" id="monto_ahorro" name="monto_ahorro" placeholder="Monto a sumar ($)" required>
        
        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL EDITAR OBJETIVO -->
<div id="modalEditarObjetivo" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="objetivos.php">
        <input type="hidden" name="form_type" value="editar_objetivo">
        <input type="hidden" id="edit_id_meta" name="id_meta" value="">
        <h3>Editar objetivo</h3>
        
        <input type="text" id="edit_nombre_meta" name="nombre_meta" placeholder="Nombre del objetivo" required>
        <input type="text" id="edit_desc_meta" name="desc_meta" placeholder="Breve descripción" required>
        <input type="number" step="0.01" id="edit_monto_objetivo" name="monto_objetivo" placeholder="Monto objetivo ($)" required>
        <input type="date" id="edit_fecha_limite" name="fecha_limite" required>
        <select id="edit_estado" name="estado" style="padding: 14px 16px; border: 1px solid #E2E8F0; border-radius: 6px; font-size: 1rem; font-family: inherit; outline: none; background: #F8FAFC; color: #1E1B26;">
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
        
        <input type="text" id="nombre_categoria" name="nombre_categoria" placeholder="Nombre de la categoría" required>
        <select id="tipo_categoria" name="tipo_categoria" style="padding: 14px 16px; border: 1px solid #E2E8F0; border-radius: 6px; font-size: 1rem; font-family: inherit; outline: none; background: #F8FAFC; color: #1E1B26;">
            <option value="gasto">Gasto</option>
            <option value="ingreso">Ingreso</option>
        </select>
        <select id="icono_categoria" name="icono" style="padding: 14px 16px; border: 1px solid #E2E8F0; border-radius: 6px; font-size: 1rem; font-family: inherit; outline: none; background: #F8FAFC; color: #1E1B26; margin-top: 12px;">
            <?php foreach (obtenerOpcionesIconoCategoria() as $ruta => $nombreIcono): ?>
                <option value="<?php echo htmlspecialchars($ruta); ?>"><?php echo htmlspecialchars($nombreIcono); ?></option>
            <?php endforeach; ?>
        </select>
        <label style="display:flex; flex-direction:column; gap:6px; font-size:0.85rem; color:#475569; margin-top:12px;">
            Color de acento
            <input type="color" id="color_categoria" name="color" value="#EA73F5" style="width:100%; height:44px; border:none; padding:0; background:#fff; border-radius:8px; cursor:pointer;">
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
        
        <input type="text" id="edit_nombre_categoria" name="nombre_categoria" placeholder="Nombre de la categoría" required>
        <select id="edit_tipo_categoria" name="tipo_categoria" style="padding: 14px 16px; border: 1px solid #E2E8F0; border-radius: 6px; font-size: 1rem; font-family: inherit; outline: none; background: #F8FAFC; color: #1E1B26;">
            <option value="gasto">Gasto</option>
            <option value="ingreso">Ingreso</option>
        </select>
        <select id="edit_icono_categoria" name="icono" style="padding: 14px 16px; border: 1px solid #E2E8F0; border-radius: 6px; font-size: 1rem; font-family: inherit; outline: none; background: #F8FAFC; color: #1E1B26; margin-top: 12px;">
            <?php foreach (obtenerOpcionesIconoCategoria() as $ruta => $nombreIcono): ?>
                <option value="<?php echo htmlspecialchars($ruta); ?>"><?php echo htmlspecialchars($nombreIcono); ?></option>
            <?php endforeach; ?>
        </select>
        <label style="display:flex; flex-direction:column; gap:6px; font-size:0.85rem; color:#475569; margin-top:12px;">
            Color de acento
            <input type="color" id="edit_color_categoria" name="color" value="#EA73F5" style="width:100%; height:44px; border:none; padding:0; background:#fff; border-radius:8px; cursor:pointer;">
        </label>
        
        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL EDITAR MOVIMIENTO -->
<div id="modalEditarMovimiento" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="">
        <input type="hidden" name="form_type" value="editar_movimiento">
        <input type="hidden" id="edit_mov_id" name="id_movimiento" value="">
        <input type="hidden" id="edit_mov_tipo" name="tipo" value="">
        <h3 id="modalEditarMovimientoTitulo">Editar</h3>
        
        <input type="number" step="0.01" id="edit_mov_monto" name="monto" placeholder="Monto ($)" required>
        
        <div class="custom-select-wrapper" id="customEditMovCategoriaWrapper">
            <div class="custom-select-trigger" id="customEditMovCategoriaTrigger">Selecciona una categoría</div>
            <div class="custom-select-options" id="customEditMovCategoriaOptions"></div>
        </div>
        <input type="hidden" id="edit_mov_categoria" name="categoria" required>

        <input type="text" id="edit_mov_descripcion" name="descripcion" placeholder="Breve descripción" required>
        
        <input type="date" id="edit_mov_fecha" name="fecha" required>
        
        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<!-- MODAL BORRAR HISTORIAL COMPLETO -->
<div id="modalBorrarHistorial" class="modal hidden">
    <div class="modal-content modal-confirmacion" style="max-width:480px;">
        <h3 style="color:#EA73F5; margin-bottom:16px;">Borrar todo el historial</h3>
        
        <div style="background:#FFF1F2; border-left:4px solid #BE123C; padding:12px; border-radius:6px; margin-bottom:20px;">
            <p style="margin:0; color:#BE123C; font-size:0.9rem; font-weight:500;">⚠️ Advertencia</p>
            <p style="margin:8px 0 0 0; color:#7F1D1D; font-size:0.85rem;">Esta acción eliminará <strong>TODOS</strong> tus movimientos (ingresos y gastos) permanentemente.</p>
            <p style="margin:4px 0 0 0; color:#7F1D1D; font-size:0.85rem;"><strong>No se puede deshacer.</strong></p>
        </div>
        
        <p style="color:#475569; font-size:0.9rem; margin-bottom:20px;">Para confirmar, escribí "SI, ESTOY SEGURO" en el campo de abajo:</p>
        
        <form id="formBorrarHistorial" method="POST" action="includes/movimientos_handler.php" style="display:flex; flex-direction:column; gap:16px;">
            <input type="hidden" name="form_type" value="borrar_historial_completo">
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'], ENT_QUOTES); ?>">
            
            <input 
                type="text" 
                id="inputConfirmacionBorrar" 
                name="confirmacion_borrar"
                placeholder="SI, ESTOY SEGURO" 
                style="padding:12px; border:2px solid #E2E8F0; border-radius:6px; font-size:0.95rem; font-family:inherit; color:#1E1B26; background:#F8FAFC; outline:none; transition:all 0.2s;"
                autocomplete="off"
                required
            >
            
            <div class="modal-actions" style="gap:12px;">
                <button type="button" class="btn cancel" onclick="cerrarModalBorrar()" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn" id="btnConfirmarBorrar" disabled style="flex:1; background:#BE123C; color:white; cursor:not-allowed; opacity:0.6;">Eliminar todo</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ELIMINAR CUENTA (IDÉNTICO EN ESTRUCTURA A BORRAR HISTORIAL) -->
<div id="modalEliminarCuenta" class="modal hidden">
    <div class="modal-content modal-confirmacion" style="max-width:480px;">
        <h3 style="color:#BE123C; margin-bottom:16px;">Eliminar cuenta</h3>
        
        <div style="background:#FFF1F2; border-left:4px solid #BE123C; padding:12px; border-radius:6px; margin-bottom:20px;">
            <p style="margin:0; color:#BE123C; font-size:0.9rem; font-weight:500;">⚠️ Advertencia crítica</p>
            <p style="margin:8px 0 0 0; color:#7F1D1D; font-size:0.85rem;">Esta acción eliminará <strong>permanentemente tu cuenta</strong> y todos tus movimientos y objetivos.</p>
            <p style="margin:4px 0 0 0; color:#7F1D1D; font-size:0.85rem;"><strong>No se puede deshacer.</strong></p>
        </div>
        
        <p style="color:#475569; font-size:0.9rem; margin-bottom:20px;">Para confirmar, escribí "ELIMINAR MI CUENTA" en el campo de abajo:</p>
        
        <form id="formEliminarCuentaModal" method="POST" action="includes/movimientos_handler.php" style="display:flex; flex-direction:column; gap:16px;">
            <input type="hidden" name="form_type" value="eliminar_cuenta">
            <input type="hidden" name="return_url" value="login.php">
            
            <input 
                type="text" 
                id="inputConfirmacionEliminarCuenta" 
                name="confirmacion_eliminar"
                placeholder="ELIMINAR MI CUENTA" 
                style="padding:12px; border:2px solid #E2E8F0; border-radius:6px; font-size:0.95rem; font-family:inherit; color:#1E1B26; background:#F8FAFC; outline:none; transition:all 0.2s;"
                autocomplete="off"
                required
            >
            
            <div class="modal-actions" style="gap:12px;">
                <button type="button" class="btn cancel" onclick="cerrarModalEliminarCuenta()" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn" id="btnConfirmarEliminarCuenta" disabled style="flex:1; background:#BE123C; color:white; cursor:not-allowed; opacity:0.6;">Eliminar cuenta</button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">
    <p>Trackify © 2026</p>
</footer>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

<script>
    // Inicializar Flatpickr separando Objetivos de Movimientos
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];

        // 1. Inputs de Objetivos (fecha_limite): PERMITE HOY Y FECHAS FUTURAS (bloquea pasadas)
        const objDateInputs = document.querySelectorAll("input[name='fecha_limite'], #fecha_limite, #edit_fecha_limite");
        objDateInputs.forEach(input => {
            input.min = today;
            input.removeAttribute('max');
            if (!input.placeholder) input.placeholder = "Selecciona fecha límite";
            if (!input.value || input.value < today) input.value = today;
        });

        if (objDateInputs.length > 0 && window.flatpickr) {
            flatpickr(objDateInputs, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                locale: "es",
                disableMobile: "true",
                minDate: today,
                maxDate: null,
                onChange: function(selectedDates, dateStr, instance) {
                    if (dateStr < today) {
                        instance.setDate(today, true);
                    }
                }
            });
        }

        // 2. Inputs de Movimientos (fecha): PERMITE PASADAS Y HOY (BLOQUEA FUTURAS)
        const movDateInputs = document.querySelectorAll("#fecha, #edit_mov_fecha");
        movDateInputs.forEach(input => {
            input.max = today;
            input.removeAttribute('min');
            if (!input.placeholder) input.placeholder = "Selecciona una fecha";
            if (!input.value) input.value = today;
            if (input.value > today) input.value = today;
        });

        if (movDateInputs.length > 0 && window.flatpickr) {
            flatpickr(movDateInputs, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                locale: "es",
                disableMobile: "true",
                maxDate: today,
                onChange: function(selectedDates, dateStr, instance) {
                    if (dateStr > today) {
                        instance.setDate(today, true);
                    }
                }
            });
        }

        // Cerrar calendario al hacer scroll en cualquier contenedor
        window.addEventListener('scroll', function() {
            document.querySelectorAll("input[type='date']").forEach(input => {
                if(input._flatpickr && input._flatpickr.isOpen) {
                    input._flatpickr.close();
                }
            });
        }, true);
    });

    // ── Conversión en el modal de nuevo movimiento ──────────────────────────────
    const monedaSelect  = document.getElementById('monedaIngreso');
    const montoInput    = document.getElementById('monto');
    const montoARSInput = document.getElementById('montoARS');
    const previewEl     = document.getElementById('previewConversion');

    // Preseleccionar la moneda del usuario en el modal
    const selectorGlobal = document.getElementById('selectorMoneda');
    if (selectorGlobal && monedaSelect) {
        monedaSelect.value = selectorGlobal.value;
    }

    async function actualizarPreview() {
        if (!montoInput || !monedaSelect || !previewEl || !montoARSInput) return;
        const monto  = parseFloat(montoInput.value) || 0;
        const moneda = monedaSelect.value;
        const tc     = window.obtenerTipoCambio ? await window.obtenerTipoCambio() : parseFloat(localStorage.getItem('trackify_tc') || '1000');

        if (!monto) {
            previewEl.textContent = '';
            montoARSInput.value = '';
            return;
        }

        if (moneda === 'USD') {
            if (tc > 0) {
                const ars = monto * tc;
                previewEl.textContent = `= $${ars.toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2})} ARS`;
                montoARSInput.value = ars.toFixed(2);
            } else {
                previewEl.textContent = 'Cargando tipo de cambio...';
                montoARSInput.value = '';
            }
        } else {
            previewEl.textContent = '';
            montoARSInput.value = monto.toFixed(2);
        }
    }

    async function convertirAntesDeGuardar() {
        if (!montoInput || !monedaSelect || !montoARSInput) return true;
        const monto  = parseFloat(montoInput.value) || 0;
        const moneda = monedaSelect.value;
        const tc     = window.obtenerTipoCambio ? await window.obtenerTipoCambio() : parseFloat(localStorage.getItem('trackify_tc') || '1000');

        if (!monto || monto <= 0) {
            alert('Ingresá un monto válido.');
            return false;
        }

        if (moneda === 'USD') {
            if (tc <= 0) {
                alert('No se pudo obtener el tipo de cambio. Intentá de nuevo.');
                return false;
            }
            montoARSInput.value = (monto * tc).toFixed(2);
        } else {
            montoARSInput.value = monto.toFixed(2);
        }
        return true;
    }

    if (montoInput)   montoInput.addEventListener('input', actualizarPreview);
    if (monedaSelect) monedaSelect.addEventListener('change', actualizarPreview);

    // Función global para confirmaciones con SweetAlert2
    function confirmarEliminacion(e, mensaje) {
        e.preventDefault();
        const form = e.target;
        Swal.fire({
            title: '¿Estás seguro?',
            text: mensaje || "Esta acción no se puede deshacer.",
            icon: 'warning',
            iconColor: '#EA73F5',
            showCancelButton: true,
            confirmButtonColor: '#700353',
            cancelButtonColor: '#1E1B26',
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            background: '#2c2c3e',
            color: '#F8FAFC',
            borderRadius: '12px',
            customClass: {
                popup: 'swal-trackify',
                confirmButton: 'btn swal-btn-danger',
                cancelButton: 'btn swal-btn-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    function confirmarLogout() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: "Tendrás que volver a ingresar tus credenciales.",
            icon: 'question',
            iconColor: '#CFF27C',
            showCancelButton: true,
            confirmButtonColor: '#084734',
            cancelButtonColor: '#1E1B26',
            confirmButtonText: 'Salir',
            cancelButtonText: 'Cancelar',
            background: '#2c2c3e',
            color: '#F8FAFC',
            customClass: {
                popup: 'swal-trackify',
                confirmButton: 'btn swal-btn-primary',
                cancelButton: 'btn swal-btn-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }
</script>

<script src="js/main.js?v=4"></script>
<script src="js/moneda.js"></script>
<?php if(isset($extra_js)) echo $extra_js; ?>

</body>
</html>
