# Plan de Correcciones Estéticas y UX

Basado en tu feedback sobre los últimos cambios, he preparado este plan de acción para atacar cada uno de los problemas mencionados (alertas, calendarios, glassmorphism, sombras y selects).

## 1. Alertas Estilizadas (SweetAlert2)
- Modificaremos la configuración global de las alertas en `footer.php` para que usen un tema completamente personalizado que encaje con la página (fondo oscuro `#1E1B26` o similar, textos claros, bordes definidos y botones personalizados).
- Eliminaremos el ícono por defecto genérico ("warning" naranja) y lo adaptaremos para que no desentone con la estética general de Trackify.

## 2. Textos en Inputs de Fechas (Datepicker)
- Agregaremos explícitamente el atributo `placeholder="Seleccionar fecha..."` a todos los inputs de tipo fecha que se encuentren vacíos (como en los filtros).
- Configuraremos Flatpickr para que respete y muestre correctamente este texto cuando no haya una fecha seleccionada.

## 3. UI del Calendario y Problema de Scroll
- **Diseño (Estética):** Inyectaremos en `styles.css` una serie de reglas (Theme Override) para la clase `.flatpickr-calendar`. Esto transformará el calendario blanco predeterminado en uno acorde a la identidad del sitio (bordes redondeados, tipografía `Inter`, colores verdes y dark para la cabecera y acentos en lima para el día seleccionado).
- **Scroll (Funcionalidad):** El calendario predeterminado de Flatpickr se adhiere al `body` (posición absoluta). Al scrollear, se "desprende" del input. Para solucionar esto en listas largas, podemos forzar a que Flatpickr se cierre al detectar un evento de scroll global o utilizar la opción de posicionamiento estricto si está dentro de un contenedor. Implementaremos la opción más limpia mediante eventos de scroll.

## 4. Glassmorphism en Chat IA
- El efecto "blur" necesita que haya un fondo variable para notarse (no se puede "desenfocar" un fondo gris plano). 
- Agregaremos orbes de luz ("glow-orbs", como los del login) detrás del contenedor principal en `ia.php`. De esta manera, el `backdrop-filter: blur(16px)` tendrá un fondo colorido sobre el cual actuar, creando el efecto de cristal opaco real.
- Aseguraremos que esto se aplique tanto en la ventana completa del chat como en el chat flotante.

## 5. Sombras (Glow) al rededor de Cajas
- Modificaremos la regla CSS global para `.card:hover`, `.box:hover`, etc.
- Aumentaremos la intensidad y el alcance del resplandor ("glow") para que sea muy evidente detrás de los bordes externos de la caja. 
- *Ajuste planeado:* `box-shadow: 0 0 25px rgba(207, 242, 124, 0.45) !important;` y un ligero cambio de color en el borde `border-color: rgba(207,242,124, 0.4)` al pasar el ratón.

## 6. Selectores Personalizados (Categorías)
- Las listas desplegables (`<select>`) nativas desentonan. Crearemos un pequeño script en `main.js` que detecte automáticamente cualquier `<select>` con una clase específica (ej. `.select-custom`) y genere el HTML del selector personalizado ("custom-select-wrapper") automáticamente.
- Reemplazaremos los filtros de `gastos.php` e `ingresos.php` para que usen esta nueva lógica, unificando todos los selectores de la página a la estética Trackify.

---

### ¿Estás de acuerdo con estas soluciones para proceder con los ajustes?
