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
        <p class="modal-ayuda">El aporte se registra como un gasto ("Ahorro para meta"). Si después eliminás el objetivo, lo ahorrado vuelve como ingreso.</p>

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

<footer class="footer">
    <p>Trackify © <?php echo date('Y'); ?></p>
</footer>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js"></script>
<!-- Flatpickr (su hoja de estilos se carga en el header) -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>

<?php if (!empty($_SESSION['flash'])): ?>
<script>
    // Mensaje de la última operación (patrón Post/Redirect/Get). Lo muestra js/main.js.
    window.trackifyFlash = <?php echo json_encode($_SESSION['flash'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php unset($_SESSION['flash']); endif; ?>

<script src="js/main.js?v=7"></script>
<script src="js/moneda.js?v=3"></script>
<?php if(isset($extra_js)) echo $extra_js; ?>

</body>
</html>
