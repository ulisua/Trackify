# Arquitectura JavaScript de Trackify ⚡

> **Documento técnico del frontend para desarrolladores e Inteligencias Artificiales (Antigravity / Copilot).**  
> Describe los módulos de Vanilla JS, manipulación del DOM, hidratación de datos desde PHP, llamadas asíncronas (Fetch API), gestión de modales, sistema bimoneda reactivo y exportación de datos.

---

## 1. Visión General y Paradigma

* **Tecnología:** **Vanilla JavaScript puro (ES6+)**. No se utiliza React, Vue, jQuery ni ningún framework o bundler frontend.
* **Paradigma:** Orientado a eventos (Event-Driven) con manipulación directa del DOM y mutación reactiva de atributos `data-*`.
* **Zero-Build-Step:** Los scripts se sirven directamente al navegador sin necesidad de transpilación, minificación compleja ni compiladores.
* **Ubicación de Scripts:** Todos los scripts funcionales se encuentran en el directorio `js/`.


---

## 2. Estructura de Archivos y Responsabilidades

```
trackify/
├── js/
│   ├── main.js                      # 1. Global: CSRF, alertas SweetAlert2 de marca, menú móvil, Flatpickr, modales y selects propios
│   ├── moneda.js                    # 2. Motor reactivo de divisas (ExchangeRate API + Frankfurter + localStorage)
│   ├── ia.js                        # 3. Asistente IA: chat flotante, autosize de textarea y fetch
│   ├── perfil.js                    # 4. Perfil: datos personales, contraseña (api/cambiar_password.php), modo oscuro y zona de peligro
│   ├── categorias.js                # 5. Categorías: tabs (Gastos/Ingresos), orden de tarjetas y modal de edición
│   ├── filtros_movimientos.js       # 6. Filtros de ingresos.php / gastos.php
```

---

## 3. Integración Híbrida: PHP -> JavaScript (Hidratación)

Trackify no depende exclusivamente de endpoints REST para renderizar vistas. Para optimizar el tiempo de carga (TTFB), utiliza **hidratación de variables en línea** desde PHP hacia el contexto global de JavaScript:

### A. Catálogo Dinámico de Categorías (`window.dbCategorias`)
Generado en `includes/header.php` antes del contenido:
```php
<script>
    window.dbCategorias = <?php echo json_encode($dbCategorias); ?>;
</script>
```
Estructura en memoria del navegador:
```javascript
window.dbCategorias = {
    ingreso: ["Sueldo", "Ventas", "Freelance...", ...],
    gasto: ["Alimentos", "Transporte", "Vivienda", ...]
};
```
Utilizado por `js/main.js` para poblar dinámicamente las opciones del selector de categorías según se presione `+ Ingreso` o `+ Gasto`.

### B. Hidratación de Datos para Gráficos (Chart.js)
En `index.php`, las series de tiempo y distribuciones calculadas por MySQL se inyectan en variables globales de script antes de inicializar las instancias de `Chart`:
```php
const tortaLabels = <?php echo $js_torta_labels; ?>;
const tortaData   = <?php echo $js_torta_data; ?>;
const barrasLabels = <?php echo $js_barras_labels; ?>;
const barrasIngresos = <?php echo $js_barras_ing; ?>;
const barrasGastos = <?php echo $js_barras_gas; ?>;
```

---

## 4. Detalle de Módulos JavaScript

### 1. Controlador Global (`js/main.js`)
Es el núcleo de la interfaz de usuario en todas las páginas autenticadas:
* **Menú Lateral Móvil:** Controla `toggleMenu()`, `abrirMenu()`, `cerrarMenu()` y escucha clics en el overlay `.sidebar-overlay`.
* **Cierre Universal con Tecla Escape:** Un listener global sobre `document` detecta `e.key === 'Escape'` y cierra el menú lateral y cualquier modal abierto mediante `cerrarModal()`.
* **Cierre de Modales:** `cerrarModal()` agrega `.hidden` a todo elemento `.modal` de la página; no hay una lista de IDs que mantener.
* **Apertura de Modales:**
  * `abrirModal('ingreso' | 'gasto')`: Configura títulos, asigna el input oculto `#tipoMovimiento` y genera las opciones del custom select usando `window.dbCategorias`.
  * `abrirModalAhorro(id_meta)`: Asigna el ID de la meta de ahorro y abre el formulario para inyectar dinero.
  * `abrirModalEditarObj(...)`: Precarga los datos de la meta y abre el modal de edición.
  * `abrirModalEditarMovimiento(...)`: Precarga los datos del movimiento a modificar.
* **Custom Select Dropdown:**
  Sustituye el `<select>` nativo para garantizar consistencia visual con la paleta de Trackify:
  * Abre y cierra con la clase `.open` sobre `.custom-select-wrapper`.
  * Cierra automáticamente al hacer clic fuera del contenedor.
* **Selects con desplegable propio (`mejorarSelect()`):** las listas abiertas se registran en el `Map` `listasAbiertas`. Un único juego de listeners globales (click, Escape, resize y scroll, estos dos últimos `passive`) las cierra a todas; antes cada select agregaba cuatro listeners propios a `document`/`window`.
* **Calendarios (`inicializarCalendarios()`):** guarda las instancias de Flatpickr que crea y, al hacer scroll fuera del calendario, cierra solo esas (antes recorría todos los `<input>` de la página en cada evento de scroll).
* **Descripciones Predictivas (`descripcionesSugeridas`):**
  Diccionario clave-valor que asocia cada categoría a una descripción habitual (ej. `"Sueldo"` -> `"Cobro de sueldo mensual"`, `"Alimentos"` -> `"Compra en supermercado"`).
  * Controlado por el atributo `dataset.sugerida`: Si el usuario escribe manualmente un texto propio, el sistema respeta su entrada y desactiva el autocompletado automático.

---

### 2. Conversor Bimoneda Reactivo (`js/moneda.js`)

Maneja toda la reactividad del cambio entre Pesos Argentinos (ARS) y Dólares Estadounidenses (USD):

```
Usuario cambia divisa (#selectorMoneda)
       │
       ▼
cambiarMoneda(nueva)
       ├── Guarda en localStorage ('trackify_moneda')
       ├── Notifica al backend: POST a api/cambiar_moneda.php
       └── Invoca convertirMontos()
             ├── Obtiene tipo de cambio (obtenerTipoCambio())
             │     ├── Cache local (TTL: 30 minutos)
             │     ├── API 1: ExchangeRate-API
             │     └── API 2 (Fallback): Frankfurter API
             ├── Recorre todos los elementos [data-ars]
             └── Formatea y muta textContent (ej. '$15.000,00' o 'USD 12,50')
```

#### Funciones Principales:
* **`obtenerTipoCambio()`:** Consulta la tasa de conversión actual de USD a ARS. Utiliza almacenamiento en `localStorage` con expiración de 30 minutos (`trackify_tc` y `trackify_tc_ts`) para no agotar cuotas de API externa. Cuenta con fallback automático entre proveedores.
* **`convertirMontos()`:** Busca en el DOM todo elemento con atributo `[data-ars]`. Calcula el valor correspondiente según la moneda elegida y formatea el resultado según la convención local argentina (`es-AR`).
* **`cambiarMoneda(nueva)`:** Sincroniza la preferencia local, envía una petición POST a `api/cambiar_moneda.php` para actualizar la base de datos del usuario y refresca los valores visibles en pantalla.
* **Formularios con monto (`inicializarFormularioMoneda(form)`, todo `<form data-form-moneda>`):**
  * `actualizarPreview()`: Al digitar un valor en `.js-monto-visible` habiendo elegido USD, calcula en tiempo real el valor estimado en ARS y lo expone en `.js-preview-conversion`.
  * En el `submit`: cancela el envío nativo, valida el monto, espera la cotización si es USD, completa el oculto `.js-monto-ars` y recién entonces envía. **La base de datos almacena siempre ARS**.
  * **Anti doble envío:** mientras se procesa, el formulario queda marcado con `data-enviando` y el botón Guardar pasa a "Guardando…" deshabilitado (un doble clic creaba el movimiento dos veces). Si el navegador restaura la página desde su caché al volver atrás (`pageshow` con `persisted`), el botón se rehabilita.

---

### 4. Perfil y Operaciones Críticas (`js/perfil.js`)

* **Datos personales (`inicializarDatosPersonales()`):** "Editar" habilita nombre y nombre de usuario (el email no se edita; la moneda se cambia desde el encabezado); "Guardar" envía el formulario a `perfil.php` (PRG). La inicial del avatar se actualiza mientras se escribe el nombre.
* **Contraseña (`inicializarSeguridad()`):** panel desplegable que habla con `api/cambiar_password.php` (JSON, cabecera `X-CSRF-Token`). Cuentas con contraseña: actual + nueva + confirmación. Cuentas de Google: código de 6 dígitos por email + nueva + confirmación. Incluye medidor de seguridad y requisitos en vivo; el servidor repite todas las validaciones.
* **Exportación de Datos (`exportarMovimientosCSV()`):** pide `export_movimientos.php` (JSON) y descarga un CSV para Excel en español: separador `;`, montos con coma decimal (con punto, Excel leía `1500.5` como `15005`), campos con `;`, comillas o saltos de línea entre comillas, y BOM UTF-8 para los acentos. Antes vivía en `js/utils/exportCSV.js`, que solo usaba esta página.
* **Control del Modo Oscuro:**
  Función `inicializarControlTema()`: Vincula el switch deslizante `#btnModoOscuro` con `localStorage` y actualiza el atributo `data-theme` en el elemento raíz (`<html>`).
* **Seguridad y Doble Confirmación:**
  * **Borrado de Historial:** requiere escribir exactamente `"SI, ESTOY SEGURO"` para habilitar el botón (`configurarModalConfirmacion()`).
  * **Eliminación de Cuenta:** requiere escribir `"ELIMINAR MI CUENTA"`. Antes de enviar, `limpiarLocalStorageTrackify()` borra las preferencias guardadas en el navegador.

---

### 5. Asistente IA en el Frontend (`js/ia.js`)

Controla tanto el widget flotante (`#chatFlotante`) como la vista a pantalla completa (`ia.php`):
* **`toggleChat()`:** Muestra u oculta la caja flotante con la clase `.oculto`.
* **Auto-redimensionamiento de Textarea:** Ajusta la altura del `<textarea id="pregunta">` al escribir según su `scrollHeight`.
* **Envío con Teclado:** Escucha la tecla `Enter` (sin `Shift`) para disparar el mensaje directamente.
* **Formateo Ligero:** `formatearRespuesta(texto)` transforma negritas Markdown (`**texto**` -> `<strong>texto</strong>`) y saltos de línea (`\n` -> `<br>`).
* **Manejo Asíncrono de Respuestas (`window.preguntarIA`):**
  1. Inyecta la burbuja del usuario.
  2. Muestra un indicador de carga ("Escribiendo...").
  3. Ejecuta `fetch("./ia.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ pregunta }) })`.
  4. Remueve el indicador de carga y renderiza la respuesta.
  5. En caso de respuesta no JSON o falla de red, expone mensajes de error claros y controlados.

---

### 6. Categorías (`js/categorias.js`)

* **`switchTab(tipo, btn)`:** Alterna la visualización del grid entre `gridGastos` y `gridIngresos` sin recargar la página.
* **Orden de tarjetas (`#ordenCategorias`):** por monto, nombre o cantidad de movimientos, usando los atributos `data-monto`, `data-nombre` y `data-cant` de cada `.cat-card`. La elección se recuerda en `localStorage`.
* **`abrirModalEditarCategoria(id, nombre, tipo, icono, color)`:** Puebla los inputs antes de desplegar el modal.
* Los modales de objetivos (`abrirModalAhorro`, `abrirModalEditarObj`) están en `js/main.js`.

---

## 5. Alertas Globales con SweetAlert2 (`js/main.js`)

Nunca se usan `alert()`/`confirm()` nativos ni `Swal.fire` suelto: todo pasa por estas funciones, que aplican las clases `.swal-trackify` / `.swal-btn-*` / `.swal-toast-trackify` de `css/components/forms.css` (vidrio con orbes verde y magenta, íconos en la paleta de la marca):

1. **`alertaTrackify(titulo, texto, icon)`:** aviso con un botón ("Entendido").
2. **`confirmarTrackify({ titulo, texto, confirmar, icon, peligro })`:** devuelve `Promise<boolean>`. Con `peligro: true` (por defecto) el botón es bordó/magenta.
3. **`avisoTrackify(mensaje)`:** toast breve abajo a la derecha. Es el que aparece después de guardar/editar/eliminar (lo dispara el mensaje flash del servidor).

> [!WARNING]
> SweetAlert2 pone la clase `.swal2-backdrop-show` también en el contenedor de los toasts. El fondo oscuro de las alertas en `css/components/forms.css` está limitado con `body:not(.swal2-toast-shown)`: sin ese filtro el `!important` le ponía fondo oscuro con blur al toast y la pantalla quedaba negra cada vez que se registraba un movimiento.
4. **`confirmarEliminacion(e, mensaje)`:** para `onsubmit` de formularios de borrado; envía el formulario solo si se confirma.
5. **`confirmarLogout()`:** confirma antes de ir a `logout.php`.

---

## 6. Guía para Agregar Nuevo Código JavaScript

| Tipo de Funcionalidad | ¿Dónde debe agregarse? | Consideraciones Técnicas |
| :--- | :--- | :--- |
| **Interacción Global (Navbar, Menús, Modales comunes)** | `js/main.js` | Asegurarse de inicializar dentro de `DOMContentLoaded` o `initGlobals()`. No colisionar con IDs globales. |
| **Nueva Divisa o Cálculo Monetario** | `js/moneda.js` | Todo elemento que deba reaccionar debe incluir el atributo `data-ars="VALOR"`. |
| **Utilidad Reutilizable (Formateo, Exportación, Fechas)** | Crear en `js/utils/nombreUtil.js` | Exponer la utilidad en el objeto `window` bajo un namespace claro (ej. `window.TrackifyMiUtil`). |
| **Lógica Exclusiva de una Página (ej. Gráficos, Reportes)** | En su archivo `js/nombre_pagina.js` | Cargar el script con `$extra_js = jsScript('js/nombre_pagina.js');` antes de incluir `footer.php`. `jsScript()` (`includes/assets.php`) agrega `?v=<fecha de modificación>`: no escribas versiones a mano. |

---

## 7. Reglas Críticas para no Romper el JavaScript

1. **No sobrescribir `window.dbCategorias`:** Este objeto es inyectado por `header.php`. Si se modifica su estructura, los modales de creación de movimientos fallarán.
2. **Respetar la convención `[data-ars]`:** Nunca renderices un valor monetario estático sin el atributo `data-ars`, de lo contrario no responderá al selector bimoneda ARS/USD.
3. **Exportación CSV:** si cambian las columnas de `export_movimientos.php`, actualizar `COLUMNAS_CSV` en `js/perfil.js`.
4. **No eliminar IDs de los modales:** las funciones `abrirModal*()` de `js/main.js` buscan los IDs de los modales de `includes/footer.php`. `cerrarModal()` y Escape cierran cualquier `.modal`.
