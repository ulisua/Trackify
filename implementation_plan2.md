# Plan de Mejoras Integrales UX/UI en Trackify

Este plan detalla las múltiples mejoras estéticas y funcionales solicitadas en todo el proyecto, abarcando desde la gestión de objetivos hasta componentes globales como alertas, campos de fecha y el chat de IA.

## Cambios Propuestos

### 1. Sistema de Objetivos (`objetivos.php` y CSS)
- **Lógica de Vencimiento**: Se comparará la `fecha_limite` del objetivo con la fecha actual. Si está vencido y no ha sido logrado, se marcará como "Vencido".
- **Estado Visual "Vencido"**: Se aplicará una clase CSS `.vencido` que atenuará la tarjeta (desaturación, opacidad reducida y leve desenfoque). 
- **Restricción de Acciones**: En los objetivos vencidos, el botón "+ Agregar ahorro" y "Editar" estarán ocultos, dejando solo activo el botón "Eliminar".
- **Botón Eliminar Acentuado**: El botón de eliminar tendrá un acento rojo (`color: #dc2626; border-color: #fca5a5; background: #fef2f2;` en hover) para advertir visualmente.
- **Barras de Progreso Coloridas**: Usaremos la misma lógica de "clases dinámicas" que aplicamos en las categorías para que cada barra de progreso de objetivo tenga un color distintivo extraído de la paleta.

### 2. Alertas Personalizadas Globales (SweetAlert2)
- Reemplazaremos los molestos "alert()" y "confirm()" nativos del navegador por **SweetAlert2**.
- Se integrará vía CDN en `footer.php`.
- Se creará una función `confirmarEliminacion(evento, mensaje)` que detendrá el envío de los formularios de eliminación en todo el sitio (gastos, ingresos, categorías, objetivos, logout), mostrará una alerta modal estilizada con la identidad de Trackify (botones oscuros y verdes), y solo si se confirma, procederá a enviar el formulario.

### 3. Métricas del Dashboard (`index.php`)
- Actualmente las tarjetas pequeñas inferiores ("% gasto", "Mayor categoría", "Gasto mensual") tienen valores estáticos (`0%`, `-`, `$0`).
- Se escribirá la lógica PHP/JS para calcular e inyectar:
  - **% gasto**: (Total Gastos / Total Ingresos) * 100.
  - **Mayor categoría**: El nombre de la categoría en la que más se gastó.
  - **Gasto mensual**: El valor de la sumatoria de gastos del mes.

### 4. Efecto "Glow" Global
- Se aplicará una transición en `styles.css` para todas las tarjetas (clases `.card`, `.box`, `.obj-card`, `.cat-card`).
- Al hacer hover, además de la sutil elevación que ya tienen, emitirán un resplandor ("glow") tenue que acompañará la paleta (por ejemplo, una sombra suave de color verde lima o lila), dándole una sensación mucho más interactiva a toda la plataforma.

### 5. Date Picker (Selector de Fechas Personalizado)
- El selector nativo (`<input type="date">`) desentona con la web y varía según el navegador.
- Integraremos la librería **Flatpickr** (CDN) para todos los inputs de tipo fecha.
- Se le aplicará un estilo y tema CSS propio que concuerde con el aspecto premium y moderno de Trackify (fondo limpio, acentos de color, bordes redondeados).

### 6. Estética del Chat IA (`ia.php` y Chat Flotante)
- Se incorporará el efecto **Glassmorphism** (fondo translúcido con desenfoque, similar al login) a las cajas del chat.
- Específicamente: `background: rgba(30, 27, 38, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1);`.
- Los mensajes serán rediseñados con bordes más redondeados y transiciones suaves para eliminar el aspecto "simple" y darle una impronta premium e integrada.

### 7. Encabezados de Tabla Pastel (`gastos.php` e `ingresos.php`)
- En la tabla de movimientos (versión desktop), la fila de cabecera (`<thead> <tr>`) será coloreada de forma semántica.
- **Ingresos**: Fondo verde pastel muy claro (ej. `#f0fdf4`) y texto verde oscuro.
- **Gastos**: Fondo rojo/rosado pastel muy claro (ej. `#fff1f2`) y texto bordó oscuro.

## Plan de Verificación

### Verificación Manual
1. Crear un objetivo con fecha de vencimiento anterior al día de hoy y verificar que luzca atenuado, sin botón de agregar y con botón de eliminar rojo.
2. Hacer clic en "Eliminar" en cualquier componente y observar el modal de confirmación nuevo (SweetAlert2 estilizado).
3. Entrar a `index.php` y comprobar que las tres tarjetas de métricas secundarias muestren valores acordes al dinero cargado.
4. Pasar el ratón por encima de distintas tarjetas en todas las páginas para notar el glow sutil.
5. Hacer clic en cualquier campo de fecha y validar que se abra el calendario Flatpickr personalizado.
6. Abrir el chat IA y confirmar el diseño Glassmorphism.
7. Verificar los encabezados pastel en las tablas de la página de gastos y de ingresos.
