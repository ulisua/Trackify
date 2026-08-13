<!-- MODAL -->
<div id="modal" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="index.php" onsubmit="return convertirAntesDeGuardar()">
        <input type="hidden" id="tipoMovimiento" name="tipoMovimiento" value="">
        <h3 id="modalTitulo">Nuevo</h3>

        <!-- Selector de moneda del ingreso -->
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:4px;">
            <input type="number" step="0.01" id="monto" placeholder="Monto" required style="flex:1;">
            <select id="monedaIngreso" style="padding:14px 12px;border:1px solid #E2E8F0;border-radius:6px;font-size:1rem;font-family:inherit;background:#F8FAFC;outline:none;min-width:90px;">
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

        <!-- Muestra la conversión en tiempo real -->
        <p id="previewConversion" style="font-size:.85rem;color:#64748b;margin:0;min-height:20px;"></p>
        
        <div class="modal-actions">
            <button type="submit" class="btn btn-guardar-modal">Guardar</button>
            <button type="button" class="btn cancel" onclick="cerrarModal()">Cancelar</button>
        </div>
    </form>
</div>

<footer class="footer">
    <p>Trackify © 2026</p>
</footer>

<script src="js/main.js?v=4"></script>
<?php if(isset($extra_js)) echo $extra_js; ?>

<script>
// ── Conversión en el modal ────────────────────────────────────────────────────
const monedaSelect  = document.getElementById('monedaIngreso');
const montoInput    = document.getElementById('monto');
const montoARSInput = document.getElementById('montoARS');
const previewEl     = document.getElementById('previewConversion');

// Preseleccionar la moneda del usuario en el modal
const selectorGlobal = document.getElementById('selectorMoneda');
if (selectorGlobal && monedaSelect) {
    monedaSelect.value = selectorGlobal.value;
}

function actualizarPreview() {
    const monto  = parseFloat(montoInput.value) || 0;
    const moneda = monedaSelect.value;
    const tc     = parseFloat(localStorage.getItem('trackify_tc') || '0');

    if (!monto) { previewEl.textContent = ''; montoARSInput.value = ''; return; }

    if (moneda === 'USD') {
        if (tc > 0) {
            const ars = monto * tc;
            previewEl.textContent = `= $${ars.toLocaleString('es-AR', {minimumFractionDigits:2})} ARS`;
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

function convertirAntesDeGuardar() {
    const monto  = parseFloat(montoInput.value) || 0;
    const moneda = monedaSelect.value;
    const tc     = parseFloat(localStorage.getItem('trackify_tc') || '0');

    if (!monto || monto <= 0) { alert('Ingresá un monto válido.'); return false; }

    if (moneda === 'USD') {
        if (tc <= 0) { alert('No se pudo obtener el tipo de cambio. Intentá de nuevo.'); return false; }
        montoARSInput.value = (monto * tc).toFixed(2);
    } else {
        montoARSInput.value = monto.toFixed(2);
    }
    return true;
}

if (montoInput)   montoInput.addEventListener('input', actualizarPreview);
if (monedaSelect) monedaSelect.addEventListener('change', actualizarPreview);
</script>

</body>
</html>
