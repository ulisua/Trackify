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
        btn.textContent = '💾 Guardar';
        btn.classList.add('guardando');
    } else {
        btn.textContent = '✏️ Editar';
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

// Logout
function confirmarLogout() {
    if (confirm('¿Cerrar sesión?')) {
        alert('Sesión cerrada. Redirigiendo al login...');
        window.location.href = 'logout.php';
    }
}
