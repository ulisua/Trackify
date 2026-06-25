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

function limpiarLocalStorageTrackify() {
    if (typeof localStorage === 'undefined') return;
    Object.keys(localStorage).forEach(key => {
        if (/^trackify/i.test(key)) {
            localStorage.removeItem(key);
        }
    });
}

function abrirConfirmacionEliminarCuenta() {
    Swal.fire({
        title: '¿Seguro que querés eliminar tu cuenta?',
        html: '<strong>Esta acción eliminará permanentemente todos tus datos</strong> y no se puede deshacer.',
        icon: 'warning',
        iconColor: '#EA73F5',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonColor: '#BE123C',
        cancelButtonColor: '#1E293B',
        confirmButtonText: 'Eliminar cuenta',
        cancelButtonText: 'Cancelar',
        buttonsStyling: false,
        width: 560,
        padding: '2rem 1.75rem 1.75rem',
        background: '#2c2c3e',
        color: '#F8FAFC',
        customClass: {
            popup: 'swal-trackify swal-trackify-danger',
            title: 'swal-trackify-title',
            htmlContainer: 'swal-trackify-copy',
            actions: 'swal-trackify-actions',
            confirmButton: 'btn swal-btn-danger',
            cancelButton: 'btn swal-btn-cancel'
        },
        focusCancel: true
    }).then((result) => {
        if (result.isConfirmed) {
            limpiarLocalStorageTrackify();
            const form = document.getElementById('formEliminarCuenta');
            const button = document.getElementById('btnEliminarCuenta');
            if (button) {
                button.disabled = true;
                button.style.opacity = '0.6';
                button.textContent = 'Eliminando...';
            }
            if (form) {
                form.submit();
            }
        }
    });
}

function attachEliminarCuentaHandler() {
    const btnEliminar = document.getElementById('btnEliminarCuenta');
    if (!btnEliminar) return;
    btnEliminar.addEventListener('click', function(e) {
        e.preventDefault();
        abrirConfirmacionEliminarCuenta();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        attachExportHandler();
        attachEliminarCuentaHandler();
    });
} else {
    attachExportHandler();
    attachEliminarCuentaHandler();
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

function inicializarControlTema() {
    const btnOscuro = document.getElementById('btnModoOscuro');
    if (!btnOscuro) return;

    // Sincronizar el estado del slider con el tema activo actual
    const temaActual = document.documentElement.getAttribute('data-theme') || 'light';
    btnOscuro.checked = (temaActual === 'dark');

    // Escuchar cambios en el slider
    btnOscuro.addEventListener('change', function() {
        const nuevoTema = btnOscuro.checked ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', nuevoTema);
        localStorage.setItem('theme', nuevoTema);
    });
}

// Attach cuando esté listo el DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        attachBorrarHistorialHandler();
        inicializarControlTema();
    });
} else {
    attachBorrarHistorialHandler();
    inicializarControlTema();
}