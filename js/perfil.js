// ============== EDICIÓN DE SECCIONES (DATOS / SEGURIDAD) ==============
// Cada sección es un <form>. El botón "Editar" habilita los campos; al volver a
// pulsarlo ("Guardar") se envía el formulario al servidor (perfil.php).
const secciones = {
    datos: { btn: 'btnDatos', form: 'formDatos', editando: false },
    seg:   { btn: 'btnSeg',   form: 'formSeguridad', editando: false }
};

function toggleEditar(id) {
    const s = secciones[id];
    if (!s) return;
    const btn = document.getElementById(s.btn);
    const form = document.getElementById(s.form);
    if (!btn || !form) return;

    if (s.editando) {
        form.requestSubmit();
        return;
    }

    s.editando = true;
    form.querySelectorAll('[data-editable]').forEach(el => { el.disabled = false; });
    const primero = form.querySelector('[data-editable]');
    if (primero) primero.focus();
    btn.innerHTML = '<img src="iconos/generales/tick.png" alt="" class="icono-boton"> Guardar';
    btn.classList.add('guardando');
}

// Validación de la nueva contraseña antes de enviar (el servidor vuelve a validar)
function attachValidacionClave() {
    const form = document.getElementById('formSeguridad');
    if (!form) return;
    form.addEventListener('submit', (e) => {
        const nueva = document.getElementById('inputPassNueva');
        const conf = document.getElementById('inputPassConf');
        let error = '';
        if (nueva.value.length < 8) error = 'La nueva contraseña debe tener al menos 8 caracteres.';
        else if (nueva.value !== conf.value) error = 'La confirmación no coincide con la nueva contraseña.';
        if (error) {
            e.preventDefault();
            if (window.Swal) {
                Swal.fire({ icon: 'warning', title: 'Revisá la contraseña', text: error, buttonsStyling: false, customClass: { popup: 'swal-trackify', confirmButton: 'btn swal-btn-primary' } });
            } else {
                alert(error);
            }
        }
    });
}

// Avatar: inicial del nombre
function actualizarAvatar() {
    const nom = document.getElementById('inputNombre');
    const avatar = document.querySelector('.perfil-avatar-inicial');
    if (nom && nom.value && avatar) {
        avatar.textContent = nom.value.trim()[0].toUpperCase();
    }
}
const inputNom = document.getElementById('inputNombre');
if (inputNom) inputNom.addEventListener('input', actualizarAvatar);

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
            btn.innerHTML = '<img src="iconos/generales/tick.png" alt="" class="icono-boton"> Exportado';
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
    try {
        Object.keys(localStorage).forEach(key => {
            if (/^trackify/i.test(key)) {
                localStorage.removeItem(key);
            }
        });
    } catch (e) { /* almacenamiento no disponible */ }
}

// ============== MODALES DE CONFIRMACIÓN (ELIMINAR CUENTA / BORRAR HISTORIAL) ==============
// El botón de confirmar solo se habilita al escribir la frase exacta (el servidor también la exige).
function configurarModalConfirmacion({ modalId, inputId, btnId, frase }) {
    const modal = document.getElementById(modalId);
    const input = document.getElementById(inputId);
    const btn = document.getElementById(btnId);

    const validar = () => { if (btn && input) btn.disabled = input.value.trim() !== frase; };

    return {
        abrir() {
            if (!modal) return;
            if (input) input.value = '';
            validar();
            modal.classList.remove('hidden');
            setTimeout(() => input && input.focus(), 100);
        },
        cerrar() {
            if (modal) modal.classList.add('hidden');
        },
        enlazar(form, alEnviar) {
            if (input) input.addEventListener('input', validar);
            if (!form) return;
            form.addEventListener('submit', (e) => {
                if (!input || input.value.trim() !== frase) {
                    e.preventDefault();
                    return;
                }
                if (alEnviar) alEnviar();
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Eliminando...';
                }
            });
        }
    };
}

const modalEliminarCuenta = configurarModalConfirmacion({
    modalId: 'modalEliminarCuenta', inputId: 'inputConfirmacionEliminarCuenta', btnId: 'btnConfirmarEliminarCuenta', frase: 'ELIMINAR MI CUENTA'
});
const modalBorrarHistorial = configurarModalConfirmacion({
    modalId: 'modalBorrarHistorial', inputId: 'inputConfirmacionBorrar', btnId: 'btnConfirmarBorrar', frase: 'SI, ESTOY SEGURO'
});

function abrirModalEliminarCuenta() { modalEliminarCuenta.abrir(); }
function cerrarModalEliminarCuenta() { modalEliminarCuenta.cerrar(); }
function abrirModalBorrar() { modalBorrarHistorial.abrir(); }
function cerrarModalBorrar() { modalBorrarHistorial.cerrar(); }

window.abrirModalEliminarCuenta = abrirModalEliminarCuenta;
window.cerrarModalEliminarCuenta = cerrarModalEliminarCuenta;
window.abrirModalBorrar = abrirModalBorrar;
window.cerrarModalBorrar = cerrarModalBorrar;

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
        try { localStorage.setItem('theme', nuevoTema); } catch (e) { /* sin almacenamiento */ }
    });
}

function inicializarPerfil() {
    attachExportHandler();
    attachValidacionClave();
    inicializarControlTema();

    const btnEliminar = document.getElementById('btnEliminarCuenta');
    if (btnEliminar) btnEliminar.addEventListener('click', (e) => { e.preventDefault(); abrirModalEliminarCuenta(); });
    modalEliminarCuenta.enlazar(document.getElementById('formEliminarCuentaModal'), limpiarLocalStorageTrackify);

    const btnBorrar = document.getElementById('btnBorrarHistorial');
    if (btnBorrar) btnBorrar.addEventListener('click', (e) => { e.preventDefault(); abrirModalBorrar(); });
    modalBorrarHistorial.enlazar(document.getElementById('formBorrarHistorial'));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarPerfil);
} else {
    inicializarPerfil();
}
