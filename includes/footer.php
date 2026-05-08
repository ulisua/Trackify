<!-- MODAL -->
<div id="modal" class="modal hidden">
    <form class="modal-content modal-form-content" method="POST" action="index.php">
        <input type="hidden" id="tipoMovimiento" name="tipoMovimiento" value="">
        <h3 id="modalTitulo">Nuevo</h3>
        
        <input type="number" step="0.01" id="monto" name="monto" placeholder="Monto ($)" required>
        
        <div class="custom-select-wrapper" id="customCategoriaWrapper">
            <div class="custom-select-trigger" id="customCategoriaTrigger">Selecciona una categoría</div>
            <div class="custom-select-options" id="customCategoriaOptions"></div>
        </div>
        <input type="hidden" id="categoria" name="categoria" required>

        <input type="text" id="descripcion" name="descripcion" placeholder="Breve descripción" required>
        
        <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
        
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

<footer class="footer">
    <p>Trackify © 2026</p>
</footer>

<script src="js/main.js?v=4"></script>
<?php if(isset($extra_js)) echo $extra_js; ?>

</body>
</html>
