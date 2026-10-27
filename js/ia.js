// ===== TOGGLE CHAT FLOTANTE =====
function toggleChat() {
    const chat = document.getElementById("chatFlotante");
    chat.classList.toggle("oculto");
}

// ===== OCULTAR BOTÓN FLOTANTE =====
// Se oculta durante la sesión del navegador; el asistente sigue disponible en la página IA.
const CLAVE_BOTON_IA = 'trackify_boton_ia_oculto';

function ocultarBotonIA() {
    const wrap = document.getElementById("botonIAWrap");
    const chat = document.getElementById("chatFlotante");
    if (wrap) wrap.hidden = true;
    if (chat) chat.classList.add("oculto");
    try { sessionStorage.setItem(CLAVE_BOTON_IA, '1'); } catch (e) { /* sin almacenamiento */ }
}

(function restaurarBotonIA() {
    let oculto = false;
    try { oculto = sessionStorage.getItem(CLAVE_BOTON_IA) === '1'; } catch (e) { /* sin almacenamiento */ }
    const wrap = document.getElementById("botonIAWrap");
    if (wrap && oculto) wrap.hidden = true;
})();

// ===== FORMATEO =====
// Se escapa todo el HTML y luego se aplica un formato mínimo (**negrita** y saltos de línea)
function formatearRespuesta(texto) {
    const div = document.createElement("div");
    div.textContent = String(texto);
    let seguro = div.innerHTML;
    seguro = seguro.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
    seguro = seguro.replace(/\n/g, "<br>");
    return seguro;
}

// ===== AGREGAR MENSAJES =====
function agregarMensaje(tipo, contenido) {
    const chat = document.getElementById("chat");

    const mensaje = document.createElement("div");
    mensaje.classList.add("mensaje");

    // permite "ia typing"
    const tipos = tipo.split(" ");
    tipos.forEach(t => mensaje.classList.add(t));

    mensaje.innerHTML = formatearRespuesta(contenido);

    chat.appendChild(mensaje);
    chat.scrollTop = chat.scrollHeight;
}

// ===== ELIMINAR ULTIMO MENSAJE (LOADER) =====
function eliminarUltimoMensaje() {
    const chat = document.getElementById("chat");
    if (chat.lastChild) {
        chat.removeChild(chat.lastChild);
    }
}

// ===== FUNCION PRINCIPAL =====
window.preguntarIA = async function () {
    const input = document.getElementById("pregunta");
    const pregunta = input.value.trim();

    if (!pregunta) return;

    // mostrar pregunta
    agregarMensaje("usuario", pregunta);

    // limpiar input
    input.value = "";
    input.style.height = "auto";

    agregarMensaje("ia typing", "Escribiendo...");

    try {
        const res = await fetch("./ia.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-Token": window.csrfToken || ""
            },
            body: JSON.stringify({ pregunta: pregunta })
        });

        eliminarUltimoMensaje();

        let data = null;
        try {
            data = await res.json();
        } catch (e) {
            console.error("Respuesta inválida del asistente");
        }

        if (!res.ok || !data || !data.respuesta) {
            agregarMensaje("ia", (data && data.error) || "No pude responder en este momento. Intentá de nuevo.");
            return;
        }

        agregarMensaje("ia", data.respuesta);

    } catch (error) {
        console.error("Error fetch:", error);
        eliminarUltimoMensaje();
        agregarMensaje("ia", "Error de conexión");
    }
};

// ===== ENTER PARA ENVIAR =====
document.getElementById("pregunta").addEventListener("keydown", function(e) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        preguntarIA();
    }
});

// ===== AUTO-RESIZE TEXTAREA =====
const textarea = document.getElementById("pregunta");

textarea.addEventListener("input", function () {
    this.style.height = "auto";
    this.style.height = this.scrollHeight + "px";
});

