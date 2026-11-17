# Resumen ejecutivo — Trackify

Aplicación web de finanzas personales (PHP + MySQL/PostgreSQL, JavaScript y CSS sin frameworks ni build).
Registra ingresos y gastos, categorías propias, objetivos de ahorro, muestra montos en ARS o USD y tiene un asistente de IA.

Documentación técnica detallada:

| Documento | Qué cubre |
| :--- | :--- |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Estructura de archivos, base de datos, seguridad, flujo de cada página |
| [`docs/CSS_ARCHITECTURE.md`](docs/CSS_ARCHITECTURE.md) | Orden de carga de estilos, temas claro/oscuro, responsive, componentes |
| [`docs/JAVASCRIPT_ARCHITECTURE.md`](docs/JAVASCRIPT_ARCHITECTURE.md) | Módulos JS, modales, conversor de moneda, alertas |
| `documentacion/` | Entregables del proyecto (manual de usuario, DER, bitácora, migración a PostgreSQL) |

---

## Puesta en marcha

1. Copiar `.env.example` como `.env` y completar los datos (base, SMTP, Google, Groq).
2. Importar `trackify.sql` (MySQL/MariaDB, XAMPP) o `trackify_postgres.sql` (con `DB_DRIVER=pgsql`).
   Una base de una versión anterior no hace falta reimportarla: `conexion.php` la actualiza sola.
3. Abrir `http://localhost/trackify/login.php`.

---

## Últimos cambios (noviembre 2026)

### Corregido
- **Pantalla negra al registrar un movimiento.** Al guardar aparece el aviso "Ingreso/Gasto guardado"
  (toast de SweetAlert2). SweetAlert2 le pone al contenedor del toast la misma clase que al fondo de las
  alertas, y nuestro CSS forzaba ese fondo oscuro con blur (`!important`). En el celular y en modo oscuro
  tapaba toda la pantalla. Ahora el fondo solo se aplica a las alertas modales (`css/components/forms.css`).
- **Movimientos duplicados con doble clic.** El botón Guardar se deshabilita ("Guardando…") mientras se envía
  el formulario (`js/moneda.js`).

### Favicon
- Nuevos íconos en `favicons/` (16, 32, apple-touch 180, android 192 y 512) más `site.webmanifest` y
  `theme-color`. `favicon.ico` de la raíz reemplazado por la versión nueva.
- Se enlazan desde un solo lugar (`headComun()` en `includes/assets.php`) para todas las pantallas,
  con y sin sesión.

### Optimización
| Antes | Ahora |
| :--- | :--- |
| `styles.css` traía 12 hojas con `@import`, que se descargan una detrás de otra | Se enlazan en paralelo desde `CSS_BASE` (`includes/assets.php`); `styles.css` se eliminó |
| Versiones de caché a mano (`main.js?v=7`), fáciles de olvidar | `cssLink()` / `jsScript()` agregan `?v=<fecha de modificación>` automáticamente |
| Sin `preconnect` a Google Fonts ni a jsDelivr | `preconnect` en el `<head>`: la fuente y las librerías empiezan a bajar antes |
| Consulta a `information_schema` (lenta) en cada request para revisar el esquema | Se recuerda en la sesión 5 minutos |
| Cada select propio agregaba 4 listeners globales (click, Escape, resize, scroll) | Un solo juego de listeners compartido |
| Al hacer scroll se recorrían todos los `<input>` de la página buscando calendarios abiertos | Solo se revisan las instancias de Flatpickr creadas |

### Segunda ronda
- **Perfil:** los modales de "Borrar historial" / "Eliminar cuenta" se veían un instante al cargar. La
  animación de entrada del contenido le ganaba a la opacidad 0 del modal oculto; ahora excluye los modales.
- **Responsive más grande:** la escala mínima subió (cajas 0.6 → 0.8, textos 0.76 → 0.9). En los modales
  todos los campos y botones usan el mismo tamaño de letra.
- **Botones de alta más altos** solo en Ingresos, Gastos y Objetivos (`.btn-agregar-grande`).
- **Orbes del header y footer más suaves** en pantallas chicas.
- **Detalles corregidos:** texto largo pisando la flecha de los selectores, "Moneda" sola en una segunda fila
  en perfil, línea de 1px del menú lateral asomando en el borde, gráficos vacíos que dejaban cajas altas
  sin contenido, íconos pegados al texto en los botones de perfil.
- **Exportar CSV:** los montos salían con punto decimal (Excel en español leía `1500.5` como `15005`) y las
  descripciones con `;` desarmaban las columnas.
- **Menos carga por página:** cada página imprime solo sus modales; Flatpickr y la consulta de categorías
  se cargan solo donde hay campos de fecha / modales de movimiento; la consulta de categorías ya no se repite
  dentro del mismo request; `js/utils/exportCSV.js` se integró en `js/perfil.js`; CSS duplicado eliminado.

---

## Reglas para no romper nada

- Los montos se guardan **siempre en ARS**; la conversión desde USD la hace el formulario.
- Toda consulta filtra por `id_usuario = ?` y usa `$conn` con parámetros (sirve para MySQL y PostgreSQL).
- CSS/JS propio: siempre con `cssLink()` / `jsScript()`, nunca `<link>`/`<script>` a mano.
- Alertas: `alertaTrackify()`, `confirmarTrackify()`, `avisoTrackify()`; nunca `alert()` ni `Swal.fire` suelto.
- Media queries solo en `css/responsive.css`.
