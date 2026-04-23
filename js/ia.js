// ===== TOGGLE CHAT FLOTANTE =====
function toggleChat() {
    const chat = document.getElementById("chatFlotante");
    chat.classList.toggle("oculto");
}

// ===== FORMATEO =====
function formatearRespuesta(texto) {
    texto = texto.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
    texto = texto.replace(/\n/g, "<br>");
    return texto;
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

    // loader UX PRO
    agregarMensaje("ia typing", "Escribiendo...");

    try {
        const res = await fetch("./ia.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ pregunta: pregunta })
        });

        eliminarUltimoMensaje();

        if (!res.ok) {
            agregarMensaje("ia", "Error (" + res.status + ")");
            return;
        }

        const text = await res.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Respuesta inválida:", text);
            agregarMensaje("ia", "Error en respuesta del servidor");
            return;
        }

        if (!data.respuesta) {
            agregarMensaje("ia", "Sin respuesta");
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