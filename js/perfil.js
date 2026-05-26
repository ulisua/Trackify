// Toggle editar secciones
const secciones = {
    datos: {
        btn: 'btnDatos',
        campos: ['inputNombre', 'inputApellido', 'inputEmail', 'inputTel', 'inputPais', 'inputMoneda'],
        editando: false
    },
    seg: {
        btn: 'btnSeg',
        campos: ['inputPassActual', 'inputPassNueva', 'inputPassConf'],
        editando: false
    }
};

function toggleEditar(id) {
    const s = secciones[id];
    const btn = document.getElementById(s.btn);
    s.editando = !s.editando;

    s.campos.forEach(c => {
        const el = document.getElementById(c);
        if (el) el.disabled = !s.editando;
    });

    if (s.editando) {
        btn.innerHTML = '<img src="ICONO_GUARDAR" alt="Guardar" class="icono-boton"> Guardar';
        btn.classList.add('guardando');
    } else {
        btn.innerHTML = '<img src="ICONO_EDITAR" alt="Editar" class="icono-boton"> Editar';
        btn.classList.remove('guardando');
        // Actualizar nombre y email mostrados
        const nom = document.getElementById('inputNombre');
        const ape = document.getElementById('inputApellido');
        const eml = document.getElementById('inputEmail');
        if (nom && ape) document.getElementById('nombreMostrado').textContent = nom.value + ' ' + ape.value;
        if (eml) document.getElementById('emailMostrado').textContent = eml.value;
    }
}

// Avatar: inicial del nombre
function actualizarAvatar() {
    const nom = document.getElementById('inputNombre');
    if(nom && nom.value) {
        document.querySelector('.perfil-avatar').textContent = nom.value[0].toUpperCase();
    }
}
const inputNom = document.getElementById('inputNombre');
if(inputNom) inputNom.addEventListener('input', actualizarAvatar);

// Exportar datos CSV — attach handler immediately or on DOMContentLoaded
function attachExportHandler() {
    const btn = document.getElementById('btnExportarDatos');
    if (!btn) return;
    if (btn._exportHandlerAttached) return;
    btn._exportHandlerAttached = true;
    btn.addEventListener('click', async function(e) {
        e.preventDefault();
        btn.disabled = true;
        const originalTxt = btn.innerHTML;
        btn.innerHTML = 'Exportando...';
        try {
            if (!window.TrackifyExportCSV || typeof window.TrackifyExportCSV.fetchAndExport !== 'function') {
                throw new Error('Export utility no cargada (TrackifyExportCSV)');
            }
            const hoy = new Date().toISOString().slice(0,10);
            const filename = `trackify_${hoy}.csv`;
            await window.TrackifyExportCSV.fetchAndExport('export_movimientos.php', filename, ['tipo','categoria','monto','fecha','descripcion']);
            // Replace emoji with project-approved tick image
            btn.innerHTML = '<img src="iconos/generales/tick.png" alt="Exportado" class="icono-boton">';
            setTimeout(() => btn.innerHTML = originalTxt, 2500);
        } catch (err) {
            console.error(err);
            btn.innerHTML = 'Error al exportar';
            setTimeout(() => btn.innerHTML = originalTxt, 3000);
        } finally {
            btn.disabled = false;
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachExportHandler);
} else {
    attachExportHandler();
}

// ============== BORRAR HISTORIAL COMPLETO ==============

// Abrir modal de confirmación para borrar historial
function abrirModalBorrar() {
    const modal = document.getElementById('modalBorrarHistorial');
    if (modal) {
        modal.classList.remove('hidden');
        // Limpiar input
        document.getElementById('inputConfirmacionBorrar').value = '';
        document.getElementById('btnConfirmarBorrar').disabled = true;
        // Enfocar en el input
        setTimeout(() => document.getElementById('inputConfirmacionBorrar').focus(), 100);
    }
}

// Cerrar modal de confirmación
function cerrarModalBorrar() {
    const modal = document.getElementById('modalBorrarHistorial');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Validar confirmación en tiempo real
function validarConfirmacionBorrar() {
    const input = document.getElementById('inputConfirmacionBorrar');
    const btn = document.getElementById('btnConfirmarBorrar');
    
    const valor = input.value.trim();
    const esValido = valor === 'SI, ESTOY SEGURO';
    
    // Habilitar/deshabilitar botón
    if (esValido) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    } else {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
    }
}

// Manejar submit del formulario de borrado
function attachBorrarHistorialHandler() {
    const btnBorrar = document.getElementById('btnBorrarHistorial');
    const inputConfirm = document.getElementById('inputConfirmacionBorrar');
    const form = document.getElementById('formBorrarHistorial');
    const btnConfirmDelete = document.getElementById('btnConfirmarBorrar');
    
    if (!btnBorrar || !form) return;
    
    // Listener para botón "Borrar historial"
    btnBorrar.addEventListener('click', function(e) {
        e.preventDefault();
        abrirModalBorrar();
    });
    
    // Listener en tiempo real para validar entrada
    if (inputConfirm) {
        inputConfirm.addEventListener('input', validarConfirmacionBorrar);
    }
    
    // Listener para submit del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validación final
        if (inputConfirm.value.trim() !== 'SI, ESTOY SEGURO') {
            console.warn('Confirmación inválida');
            return;
        }
        
        // Feedback visual
        btnConfirmDelete.disabled = true;
        btnConfirmDelete.innerHTML = '<img src="iconos/generales/cargando.png" alt="Borrando..." class="icono-boton" style="animation: spin 1s linear infinite;"> Eliminando...';
        
        // Enviar formulario
        form.submit();
    });
}

// Attach cuando esté listo el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachBorrarHistorialHandler);
} else {
    attachBorrarHistorialHandler();
}