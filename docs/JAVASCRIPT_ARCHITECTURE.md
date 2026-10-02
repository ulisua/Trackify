# Arquitectura JavaScript de Trackify ⚡

> **Documento técnico del frontend para desarrolladores e Inteligencias Artificiales (Antigravity / Copilot).**  
> Describe los módulos de Vanilla JS, manipulación del DOM, hidratación de datos desde PHP, llamadas asíncronas (Fetch API), gestión de modales, sistema bimoneda reactivo y exportación de datos.

---

## 1. Visión General y Paradigma

* **Tecnología:** **Vanilla JavaScript puro (ES6+)**. No se utiliza React, Vue, jQuery ni ningún framework o bundler frontend.
* **Paradigma:** Orientado a eventos (Event-Driven) con manipulación directa del DOM y mutación reactiva de atributos `data-*`.
* **Zero-Build-Step:** Los scripts se sirven directamente al navegador sin necesidad de transpilación, minificación compleja ni compiladores.
* **Ubicación de Scripts:** Todos los scripts funcionales se encuentran en el directorio `js/`.

> [!WARNING]
> Existe un archivo `main.js` en la **raíz del proyecto** con un tamaño de 0 bytes (vestigio histórico). **NO LO UTILICES NI DEPENDAS DE ÉL**. El archivo maestro de control es [js/main.js](file:///c:/Users/C3/Desktop/trackify/js/main.js).

---

## 2. Estructura de Archivos y Responsabilidades

```
trackify/
├── js/
│   ├── main.js                      # 1. Controlador maestro: menú móvil, modales globales, custom selects y descripciones
│   ├── moneda.js                    # 2. Motor reactivo de divisas (ExchangeRate API + Frankfurter + localStorage)
│   ├── ia.js                        # 3. Asistente IA: chat flotante, autosize de textarea y fetch
│   ├── perfil.js                    # 4. Control de perfil: tabs de edición, exportación CSV, switch de tema oscuro y zona de peligro
│   ├── categorias.js                # 5. Gestión de categorías: cambio de tabs (Gastos/Ingresos) y modal de edición
│   ├── objetivos.js                 # 6. Módulo complementario de metas de ahorro
│   └── utils/
│       └── exportCSV.js             # 7. Utilidad modular de exportación CSV con BOM UTF-8 y descarga Blob
└── main.js                          # [LEGACY 0 BYTES] Inactivo, no tocar.
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
* **Pool Centralizado de Modales:**
  La función `cerrarModal()` oculta sistemáticamente cualquier modal abierto en la página:
  ```javascript
  const modales = [
      'modal', 'modalObjetivo', 'modalAhorro', 'modalEditarObjetivo',
      'modalCategoria', 'modalEditarCategoria', 'modalEditarMovimiento'
  ];
  ```
* **Apertura de Modales:**
  * `abrirModal('ingreso' | 'gasto')`: Configura títulos, asigna el input oculto `#tipoMovimiento` y genera las opciones del custom select usando `window.dbCategorias`.
  * `abrirModalAhorro(id_meta)`: Asigna el ID de la meta de ahorro y abre el formulario para inyectar dinero.
  * `abrirModalEditarObj(...)`: Precarga los datos de la meta y abre el modal de edición.
  * `abrirModalEditarMovimiento(...)`: Precarga los datos del movimiento a modificar.
* **Custom Select Dropdown:**
  Sustituye el `<select>` nativo para garantizar consistencia visual con la paleta de Trackify:
  * Abre y cierra con la clase `.open` sobre `.custom-select-wrapper`.
  * Cierra automáticamente al hacer clic fuera del contenedor.
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
* **Conversión en Vivo en el Modal de Movimiento (`includes/footer.php`):**
  * `actualizarPreview()`: Al digitar un valor en `#monto` habiendo elegido USD, calcula en tiempo real el valor estimado en ARS y lo expone en `#previewConversion`.
  * `convertirAntesDeGuardar()`: Antes de realizar el submit (`onsubmit`), asegura que el input oculto `name="monto"` reciba el valor final en ARS multiplicado por la cotización. **La base de datos almacena siempre ARS**.

---

### 3. Utilidad de Exportación CSV (`js/utils/exportCSV.js`)

Módulo cliente puro encapsulado bajo el namespace global `window.TrackifyExportCSV`:

* **`escapeCSV(value)`:** Cumple estrictamente con el estándar RFC 4180: duplica comillas internas (`""`) y envuelve entre comillas dobles los campos que incluyan comas, saltos de línea o comillas.
* **`objectArrayToCSV(rows, columns)`:** Convierte una matriz de objetos en texto CSV separado por punto y coma (`;`), óptimo para Microsoft Excel en español.
* **Encoding UTF-8 y BOM:**
  Para evitar que Excel en Windows rompa los caracteres con tildes o eñes, inyecta los 3 bytes del BOM UTF-8 (`0xEF, 0xBB, 0xBF`) al inicio del `Blob`:
  ```javascript
  const bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
  const blob = new Blob([bom, csv], { type: 'text/csv;charset=utf-8;' });
  ```
* **Descarga Automática Segura:** Crea un enlace temporal (`<a>`) en memoria con `URL.createObjectURL(blob)`, dispara el evento `.click()` y revoca la URL con `URL.revokeObjectURL(url)` para liberar memoria.
* **`fetchAndExport(url, filename, columns)`:** Realiza un `fetch` hacia el endpoint JSON (`export_movimientos.php`), parsea los registros y dispara la descarga en un solo paso.

---

### 4. Perfil y Operaciones Críticas (`js/perfil.js`)

* **Edición de Secciones:** Controla la alternancia entre modo lectura y modo edición en los campos del formulario de usuario (`toggleEditar('datos')` y `toggleEditar('seg')`).
* **Avatar Dinámico:** Escucha el evento `input` sobre el campo del nombre para actualizar en caliente la inicial del avatar (`actualizarAvatar()`).
* **Exportación de Datos:** Conecta el botón `#btnExportarDatos` con `window.TrackifyExportCSV.fetchAndExport()`.
* **Control del Modo Oscuro:**
  Función `inicializarControlTema()`: Vincula el switch deslizante `#btnModoOscuro` con `localStorage` y actualiza el atributo `data-theme` en el elemento raíz (`<html>`).
* **Seguridad y Doble Confirmación:**
  * **Borrado de Historial (`abrirModalBorrar()` / `validarConfirmacionBorrar()`):** Requiere que el usuario escriba exactamente `"SI, ESTOY SEGURO"` para habilitar el botón de envío.
  * **Eliminación de Cuenta (`abrirModalEliminarCuenta()` / `validarConfirmacionEliminarCuenta()`):** Requiere que el usuario digite `"ELIMINAR MI CUENTA"` o `"SI, ESTOY SEGURO"`. Antes de enviar el formulario oculto, ejecuta `limpiarLocalStorageTrackify()` para purgar claves residuales de sesión local.

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

### 6. Categorías y Metas de Ahorro (`js/categorias.js`, `js/objetivos.js`)

* **`switchTab(tipo, btn)` en `js/categorias.js`:** Alterna la visualización del grid entre `gridGastos` y `gridIngresos` sin recargar la página.
* **`abrirModalEditarCategoria(id, nombre, tipo, icono, color)`:** Puebla los inputs y el selector de emojis/iconos antes de desplegar el modal.
* **`js/objetivos.js`:** Mantenido como script modular complementario; sus handlers principales de modales se encuentran centralizados en `js/main.js` para evitar colisiones globales.

---

## 5. Alertas Globales con SweetAlert2 (`includes/footer.php`)

Para evitar diálogos bloqueantes nativos del navegador (`confirm()`), Trackify expone dos funciones globales integradas con SweetAlert2 y estilizadas con la paleta del proyecto:

1. **`confirmarEliminacion(e, mensaje)`:**
   Detiene el envío inicial (`e.preventDefault()`), despliega el modal oscuro con acentos de color Trackify y sólo envía el formulario (`form.submit()`) si el usuario hace clic en "Eliminar".
2. **`confirmarLogout()`:**
   Solicita confirmación antes de redirigir a `logout.php`.

---

## 6. Guía para Agregar Nuevo Código JavaScript

| Tipo de Funcionalidad | ¿Dónde debe agregarse? | Consideraciones Técnicas |
| :--- | :--- | :--- |
| **Interacción Global (Navbar, Menús, Modales comunes)** | `js/main.js` | Asegurarse de inicializar dentro de `DOMContentLoaded` o `initGlobals()`. No colisionar con IDs globales. |
| **Nueva Divisa o Cálculo Monetario** | `js/moneda.js` | Todo elemento que deba reaccionar debe incluir el atributo `data-ars="VALOR"`. |
| **Utilidad Reutilizable (Formateo, Exportación, Fechas)** | Crear en `js/utils/nombreUtil.js` | Exponer la utilidad en el objeto `window` bajo un namespace claro (ej. `window.TrackifyMiUtil`). |
| **Lógica Exclusiva de una Página (ej. Gráficos, Reportes)** | En su archivo `js/nombre_pagina.js` | Cargar el script al final de la página PHP mediante la variable `$extra_js` antes de incluir `footer.php`. |

---

## 7. Reglas Críticas para no Romper el JavaScript

1. **No sobrescribir `window.dbCategorias`:** Este objeto es inyectado por `header.php`. Si se modifica su estructura, los modales de creación de movimientos fallarán.
2. **Respetar la convención `[data-ars]`:** Nunca renderices un valor monetario estático sin el atributo `data-ars`, de lo contrario no responderá al selector bimoneda ARS/USD.
3. **No alterar la firma de `TrackifyExportCSV`:** El archivo `perfil.js` depende de `TrackifyExportCSV.fetchAndExport(url, filename, columns)`.
4. **No eliminar IDs de los modales:** Las funciones `cerrarModal()` y `Escape` asumen la existencia de los modales estándar definidos en `includes/footer.php`.
