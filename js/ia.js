// ===== TOGGLE CHAT FLOTANTE =====
function toggleChat() {
    const chat = document.getElementById("chatFlotante");
    const wrap = document.getElementById("botonIAWrap");
    const abierto = chat.classList.toggle("oculto") === false;
    if (wrap) wrap.classList.toggle("chat-abierto", abierto);
    // Fondo difuminado y oscurecido detrás del chat (solo existe en el dashboard)
    const fondo = document.getElementById("chatFondo");
    if (fondo) fondo.classList.toggle("visible", abierto);
    if (abierto) {
        const input = document.getElementById("pregunta");
        if (input) setTimeout(() => input.focus(), 50);
    }
}

// ===== MINIMIZAR BOTÓN FLOTANTE =====
// La cruz no lo elimina: lo deja como una pestaña pequeña pegada al borde derecho.
// El estado se recuerda durante la sesión del navegador.
const CLAVE_BOTON_IA = 'trackify_boton_ia_min';

function minimizarBotonIA() {
    const wrap = document.getElementById("botonIAWrap");
    const chat = document.getElementById("chatFlotante");
    if (wrap) wrap.classList.add("minimizado");
    if (wrap) wrap.classList.remove("chat-abierto");
    if (chat) chat.classList.add("oculto");
    const fondo = document.getElementById("chatFondo");
    if (fondo) fondo.classList.remove("visible");
    try { sessionStorage.setItem(CLAVE_BOTON_IA, '1'); } catch (e) { /* sin almacenamiento */ }
}

function restaurarBotonIA() {
    const wrap = document.getElementById("botonIAWrap");
    if (wrap) wrap.classList.remove("minimizado");
    try { sessionStorage.removeItem(CLAVE_BOTON_IA); } catch (e) { /* sin almacenamiento */ }
}

(function estadoInicialBotonIA() {
    let minimizado = false;
    try {
        minimizado = sessionStorage.getItem(CLAVE_BOTON_IA) === '1';
        sessionStorage.removeItem('trackify_boton_ia_oculto'); // clave de la versión anterior
    } catch (e) { /* sin almacenamiento */ }
    const wrap = document.getElementById("botonIAWrap");
    if (wrap && minimizado) {
        // Al cargar la página ya minimizado no se anima la transición
        wrap.classList.add("sin-transicion", "minimizado");
        requestAnimationFrame(() => requestAnimationFrame(() => wrap.classList.remove("sin-transicion")));
    }
})();

// ===== SUGERENCIAS (chat vacío) =====
const SUGERENCIAS_IA = [
    "¿En qué gasto más?",
    "¿Cómo puedo ahorrar más este mes?",
    "Haceme un resumen de mis finanzas",
    "¿Voy bien con mis objetivos?"
];

function mostrarSugerencias() {
    const chat = document.getElementById("chat");
    if (!chat || chat.children.length) return;
    const caja = document.createElement("div");
    caja.className = "chat-sugerencias";
    const titulo = document.createElement("p");
    titulo.className = "chat-sugerencias-titulo";
    titulo.textContent = "Probá preguntarme:";
    caja.appendChild(titulo);
    SUGERENCIAS_IA.forEach(texto => {
        const chip = document.createElement("button");
        chip.type = "button";
        chip.className = "chat-sugerencia";
        chip.textContent = texto;
        chip.addEventListener("click", () => {
            const input = document.getElementById("pregunta");
            input.value = texto;
            preguntarIA();
        });
        caja.appendChild(chip);
    });
    chat.appendChild(caja);
}

function quitarSugerencias() {
    document.querySelectorAll(".chat-sugerencias").forEach(el => el.remove());
}
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

    if (tipos.includes("typing")) {
        // Indicador animado de "escribiendo" (tres puntos)
        mensaje.setAttribute("aria-label", contenido);
        mensaje.innerHTML = "<span class=\"typing-dots\"><span></span><span></span><span></span></span>";
    } else {
        mensaje.innerHTML = formatearRespuesta(contenido);
    }

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
    quitarSugerencias();
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


// ===== ESTADO INICIAL =====
mostrarSugerencias();

// Escape cierra el chat flotante si está abierto
document.addEventListener("keydown", (e) => {
    const chat = document.getElementById("chatFlotante");
    if (e.key === "Escape" && chat && !chat.classList.contains("oculto")) toggleChat();
});
