# Arquitectura General de Trackify 🚀

> **Documento maestro de arquitectura para desarrolladores e Inteligencias Artificiales (Antigravity / Copilot).**  
> Describe el estado **ACTUAL** del código fuente, sus patrones, flujo de datos, seguridad y dependencias críticas.

---

## 1. Visión General y Paradigma del Sistema

Trackify es una aplicación web de gestión financiera personal (ingresos, gastos, categorías personalizadas, metas de ahorro, conversión bimoneda ARS/USD y métricas analíticas).

* **Patrón Arquitectónico:** **Server-Side Rendered (SSR) Monolítico** con patrón **Page Controller / Front Controller Híbrido**. No utiliza frameworks PHP pesados (como Laravel o Symfony); cada archivo `.php` en la raíz actúa secuencialmente como enrutador, controlador y renderizador de vista.
* **Paradigma Frontend-Backend:** Multi-Page Application (MPA) con experiencia fluida (Zero-Build-Step). No requiere Webpack, Vite ni Node.js en producción ni desarrollo.
* **Integración de Capas:** Los archivos `.php` combinan lógica de servidor, HTML semántico, hidratación de variables PHP hacia JavaScript (`json_encode`) e invocación de estilos CSS modulares.

### Diagrama de Flujo de Solicitud (Page Controller)
```
Navegador (Cliente)
   │
   ├── [HTTP GET] ──> index.php / gastos.php / etc.
   │                    ├── Valida sesión ($_SESSION['usuario_id']) vía includes/header.php
   │                    ├── Consulta BD MySQL (mysqli con prepared statements)
   │                    ├── Procesa lógica y filtra períodos (includes/periodo.php)
   │                    ├── Hidrata variables hacia JS (inline JSON)
   │                    └── Renderiza HTML + CSS (styles.css + css/pages/...)
   │
   └── [HTTP POST] ──> includes/movimientos_handler.php / endpoints PHP
                        ├── Procesa transacción DML en MySQL
                        └── Patrón PRG (Post/Redirect/Get) con header("Location: ...")
```

---

## 2. Estructura de Directorios y Archivos Relevantes

```
trackify/
├── api/
│   └── cambiar_moneda.php           # Endpoint AJAX: actualiza moneda principal del usuario
├── auth/
│   └── google/
│       └── callback.php             # OAuth 2.0 Google: canje de token y login/registro
├── css/
│   ├── base.css                     # Reset, animaciones clave y estilos base
│   ├── layout.css                   # Grid, flexbox layout, contenedores y tablas
│   ├── themes.css                   # Tokens de temas Claro/Oscuro ([data-theme])
│   ├── variables.css                # Paleta de colores de marca y tipografía
│   ├── components/                  # Componentes reutilizables (navbar, sidebar, cards, forms, buttons, charts)
│   └── pages/                       # Estilos específicos de cada vista (dashboard, auth, categorias, etc.)
├── fonts/                           # Tipografías empaquetadas (Creato Display, Nexa)
├── iconos/                          # Recursos gráficos PNG locales
│   ├── gasto/                       # Iconos específicos de categorías de gasto
│   ├── generales/                   # Iconos UI (flechas, tachos, lápiz, robots, alertas)
│   └── ingreso/                     # Iconos específicos de categorías de ingreso
├── includes/
│   ├── categorias_meta.php          # Metadatos (nombre -> icono/color) y helpers de categorías visibles por usuario
│   ├── footer.php                   # Pie global, modales centrales, token CSRF en formularios, mensajes flash, SweetAlert2, Flatpickr
│   ├── header.php                   # Cabecera global, control de sesión, selectores de moneda/período (navbar + menú móvil)
│   ├── mailer.php                   # Envío del código de verificación (PHPMailer, credenciales desde .env)
│   ├── movimientos_handler.php      # Controlador POST central: CRUD movimientos, borrar historial, eliminar cuenta
│   ├── periodo.php                  # Cálculo centralizado de fechas (semana, quincena, mes) y moneda
│   ├── seguridad.php                # CSRF, URL de retorno segura, validación de montos/fechas, jsAttr(), flash()
│   └── vista_movimientos_tipo.php   # Vista compartida por ingresos.php y gastos.php
├── js/
│   ├── utils/
│   │   └── exportCSV.js             # Utilidad modular: conversión a CSV con BOM UTF-8 y descarga Blob
│   ├── categorias.js                # Tabs e interacción de edición de categorías
│   ├── filtros_movimientos.js       # Filtro por fecha/categoría de ingresos.php y gastos.php
│   ├── ia.js                        # Chat del asistente (escapa HTML, fetch con CSRF, botón flotante ocultable)
│   ├── main.js                      # Controlador global de UI, modales, menú responsive y selects con desplegable propio (mejorarSelect)
│   ├── moneda.js                    # Conversor bimoneda (ExchangeRate API + Frankfurter + cache) y formularios con monto
│   └── perfil.js                    # Edición de datos/contraseña, exportación CSV, tema oscuro y confirmaciones críticas
├── phpmailer/                       # Librería PHPMailer (Exception.php, PHPMailer.php, SMTP.php)
├── categorias.php                   # Vista y CRUD de categorías
├── .env.example                     # Plantilla de configuración (copiar como .env, que NO se versiona)
├── conexion.php                     # Conexión MySQLi, carga de .env y seguridad, automigraciones y seeding inicial
├── env.php                          # Parser manual simple del archivo .env
├── export_movimientos.php           # Endpoint JSON autenticado para exportación de datos
├── gastos.php                       # Vista de gastos, filtros por fecha/categoría y métricas mensuales
├── ia.php                           # Vista de pantalla completa del asistente IA (Groq)
├── index.php                        # Dashboard principal: balance, KPIs, gráficos Chart.js y recomendaciones
├── ingresos.php                     # Ingresos del período (usa includes/vista_movimientos_tipo.php)
├── login.php                        # Pantalla de inicio de sesión (credenciales + botón Google OAuth)
├── logout.php                       # Cierre de sesión seguro y destrucción de cookies
├── moneda.css                       # Estilos de los selectores de moneda y período (navbar y menú móvil)
├── movimientos.php                  # Listado completo con filtros por rango, tipo y categoría
├── objetivos.php                    # Metas de ahorro: CRUD, aportes, progreso y vencimientos
├── perfil.php                       # Perfil: nombre, moneda, contraseña, tema oscuro y zona de peligro
├── reenviar_codigo.php              # Reenvío de código OTP de verificación de email vía PHPMailer
├── registro.php                     # Registro de usuarios con contraseña Bcrypt y envío de OTP
├── styles.css                       # Hoja de estilos raíz (entry point que importa módulos CSS)
├── trackify.sql                     # Script SQL principal de la base de datos
└── verificar_email.php              # Validación del código OTP de 6 dígitos
```

> [!NOTE]
> * La base de datos usa una única tabla normalizada de movimientos: `movimientos`.
> * `trackify.sql` es un volcado de referencia: las columnas agregadas después (ver auto-migraciones) se crean al iniciar la app.

---

## 3. Modelo de Datos y Base de Datos MySQL

* **Base de datos predeterminada:** `trackify` (definida en `conexion.php`).
* **Motor de conexión:** `mysqli` con charset `utf8mb4`.
* **Configuración de entorno:** `env.php` lee `.env` línea por línea y expone las variables vía `putenv()`.

### Tablas Principales

| Tabla | Columnas Clave | Propósito / Relación |
| :--- | :--- | :--- |
| **`usuarios`** | `id_usuario` (PK, AI), `nombre`, `email` (UNIQUE), `clave` (Bcrypt), `fecha_registro`, `uuid`, `email_verificado`, `codigo_verificacion`, `codigo_expiracion`, `google_id`, `Foto_perfil`, `moneda_principal` (ENUM 'ARS','USD') | Entidad central de autenticación y preferencia del usuario. |
| **`categorias`** | `id_categoria` (PK, AI), `nombre`, `tipo` (ENUM 'ingreso','gasto'), `icono`, `color`, `id_usuario` (NULL o id) | `id_usuario NULL` = categoría **predeterminada** (compartida, solo lectura). Con id = categoría **propia** de ese usuario (solo él la ve, edita y elimina). |
| **`movimientos`** | `id_movimiento` (PK, AI), `id_usuario` (FK), `id_categoria` (FK), `monto` (DECIMAL 12,2), `tipo` (ENUM 'ingreso','gasto'), `descripcion`, `fecha` (DATE), `es_hormiga` (TINYINT 1) | Registro transaccional central. **Siempre almacena montos convertidos a ARS**. |
| **`metas_ahorro`**| `id_meta` (PK, AI), `id_usuario` (FK), `nombre_meta`, `descripcion`, `monto_objetivo` (DECIMAL 12,2), `monto_actual` (DECIMAL 12,2), `fecha_limite` (DATE), `estado` (ENUM 'activo','inactivo','logrado') | Objetivos de ahorro individuales por usuario. Montos en ARS. |
| **`consejos_ia`** | `id_consejos` (PK, AI), `id_usuario` (FK), `mensaje` (TEXT), `fecha` (TIMESTAMP) | Tabla en esquema para histórico de consejos (reservada). |

### Auto-Migraciones y Seeding en Tiempo de Ejecución (`conexion.php`)
Al inicializarse `conexion.php`, el sistema ejecuta silenciosamente sentencias `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` para garantizar que columnas añadidas en iteraciones recientes (`uuid`, `google_id`, `email_verificado`, `moneda_principal`, `icono`, `color`, `estado`, `categorias.id_usuario`, etc.) existan en la tabla. Las migraciones son **solo aditivas**; la ampliación de `metas_ahorro.monto_*` a `DECIMAL(12,2)` se ejecuta una única vez (verifica la escala antes).  
Además, si `SELECT COUNT(*) FROM categorias` devuelve `<= 5`, se ejecuta un **seeding automático** insertando 11 categorías de ingreso y 16 de gasto con metadatos asociados desde `includes/categorias_meta.php`.

---

## 4. Autenticación, Seguridad y Gestión de Sesión

### Aislamiento de Datos (Multi-Tenancy)
1. **Regla de Oro:** Todo script transaccional consulta `$_SESSION['usuario_id']`.
2. Todas las consultas DML a `movimientos`, `metas_ahorro` o `usuarios` **DEBEN** filtrar estrictamente con `WHERE id_usuario = ?` mediante consultas preparadas (`$stmt->bind_param("i", $user_id)`). Esto previene vulnerabilidades IDOR (Insecure Direct Object References).
3. Salidas dinámicas de texto (`descripcion`, `nombre`, etc.) se escapan en el HTML utilizando `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')` para neutralizar ataques XSS. Los valores que van como argumentos JS dentro de atributos (`onclick="fn(...)"`) se generan con `jsAttr($valor)` (JSON escapado), nunca concatenando comillas a mano.
4. **CSRF:** `includes/seguridad.php` (incluido por `conexion.php`) rechaza con 403 todo POST de un usuario autenticado que no traiga el token (`csrf_token` en formularios, inyectado automáticamente por `footer.php`; cabecera `X-CSRF-Token` en `fetch`, usando `window.csrfToken`).
5. **Redirecciones:** toda `return_url` pasa por `urlRetornoSegura()`, que solo admite rutas del propio sitio.
6. **Validación en servidor:** montos con `montoValido()` (> 0), fechas con `fechaValida()`, tipos y estados contra listas cerradas. La validación de JavaScript es solo de comodidad.
7. **Secretos:** credenciales SMTP y de Google solo en `.env` (ver `.env.example`).

### Flujo de Registro y Verificación por Email (OTP)
1. El usuario envía el formulario en `registro.php`.
2. Se genera un hash seguro de contraseña con `password_hash($pass, PASSWORD_DEFAULT)`.
3. Se genera un código OTP numérico de 6 dígitos (`random_int`) con vencimiento a 15 minutos (`codigo_expiracion`) y un identificador único `uuid`.
4. El registro se crea con `email_verificado = 0`.
5. Se despacha un correo HTML mediante **PHPMailer** (`includes/mailer.php`, credenciales SMTP en `.env`).
6. El usuario es redirigido a `verificar_email.php` (máximo 5 intentos por código). Al validar el código, se actualiza `email_verificado = 1`, se limpian los campos del código, se regenera el id de sesión y se inicia la sesión.
7. `reenviar_codigo.php` emite un nuevo código (como máximo uno por minuto) y reinicia los intentos.
8. En `login.php` la contraseña se verifica **antes** de mirar si el email está verificado; el mensaje de error no distingue entre email inexistente y contraseña incorrecta.

### Google Login (OAuth 2.0)
* **Archivo de inicio:** `login.php` genera una URL hacia `https://accounts.google.com/o/oauth2/v2/auth` con un parámetro anti-falsificación `state` (`$_SESSION['oauth_state'] = bin2hex(random_bytes(16))`).
* **Callback:** `auth/google/callback.php` recibe `code` y `state`:
  1. Valida el parámetro `state` (obligatorio, comparación con `hash_equals`).
  2. Solicita el token de acceso vía cURL contra `https://oauth2.googleapis.com/token`.
  3. Consulta el perfil en `https://www.googleapis.com/oauth2/v2/userinfo` y exige `verified_email`.
  4. Si el `google_id` ya existe, inicia sesión. Si el email coincide con un usuario existente, vincula `google_id` y `Foto_perfil` (y lo marca verificado). Si es nuevo, lo inserta con `clave = ''` (sin contraseña; puede definir una desde el perfil).
  5. `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` se leen de `.env`.

### Cierre de Sesión y Destrucción de Cuenta
* `logout.php`: Destruye variables de sesión con `session_destroy()`, borra la cookie de sesión y redirige a `login.php`.
* `eliminar_cuenta` (en `includes/movimientos_handler.php`): exige en el servidor la frase `ELIMINAR MI CUENTA`. En una transacción borra 1) `movimientos`, 2) `metas_ahorro`, 3) `consejos_ia`, 4) sus categorías propias, 5) `usuarios`; luego vacía la sesión, destruye cookies y redirige al login.

---

## 5. Procesamiento de Páginas, Renderizado e Includes Reutilizables

Cada página principal sigue esta secuencia estricta:

```php
<?php
$page = 'gastos';                         // 1. Identificador de página activa para el sidebar
$extra_css = '<link ...>';                 // 2. (Opcional) CSS específico de la página
require_once 'conexion.php';              // 3. Conexión a BD
require_once 'includes/movimientos_handler.php'; // 4. (Opcional) Si la página procesa formularios POST directos
require_once 'includes/header.php';       // 5. Inicia sesión, valida auth, renderiza <head>, navbar y <aside>

// 6. Lógica de negocio PHP (consultas SQL, preparación de arrays de datos)
?>

<!-- 7. HTML semántico del contenido de la página (<main class="content">) -->

<?php
$extra_js = '<script src="..."></script>'; // 8. (Opcional) Scripts específicos de la vista
require_once 'includes/footer.php';       // 9. Cierra contenedor, inyecta modales globales, SweetAlert2, Flatpickr, js/main.js
?>
```

### Componentes de Inclusión Reutilizados
* **`includes/header.php`**:
  * Ejecuta el script inline contra parpadeo de modo oscuro (**anti-FOUC**).
  * Inyecta `window.dbCategorias = {...}` con las categorías **visibles para el usuario** (predeterminadas + propias) agrupadas por tipo, y el token CSRF en `<meta name="csrf-token">`.
  * Renderiza la barra superior (`.navbar`), logo, botón toggle para móviles y saludo. Los selectores de moneda (`.js-selector-moneda`) y período (`.js-selector-periodo`) se renderizan dos veces: en el navbar (escritorio) y dentro del menú lateral (móvil, ≤900px).
  * Carga `css/responsive.css` al final del `<head>`, después de `$extra_css`.
  * Abre la estructura de layout (`.layout`) y el menú lateral (`.sidebar`) marcando la página activa con la clase `.active`.
* **`includes/periodo.php`**:
  * Centraliza el filtrado temporal en sesión (`semana`, `quincena`, `mes`), calculando `$fecha_desde` y `$fecha_hasta`.
  * Inicializa `$_SESSION['moneda']` desde `usuarios.moneda_principal`.
* **`includes/footer.php`**:
  * Contiene los modales globales del sistema: `#modal` (nuevo movimiento), `#modalObjetivo`, `#modalAhorro`, `#modalEditarObjetivo`, `#modalCategoria`, `#modalEditarCategoria`, `#modalEditarMovimiento`, `#modalBorrarHistorial`, `#modalEliminarCuenta`.
  * Carga e inicializa librerías externas (**SweetAlert2**, **Flatpickr** con localización al español).
  * Contiene las funciones globales de confirmación: `confirmarEliminacion(e, mensaje)` y `confirmarLogout()`.
  * Agrega el campo `csrf_token` a todos los formularios POST y muestra el mensaje flash de la última operación (`flash()` en PHP) con SweetAlert2.
  * Carga `js/main.js`, `js/moneda.js` y `$extra_js`. Las librerías externas están fijadas a versión exacta (SweetAlert2 11.26.25, Flatpickr 4.6.13, Chart.js 4.6.1 en el dashboard).
* **`includes/categorias_meta.php`**:
  * Función `obtenerMetaCategoriaPorNombre($nombre, $tipo)`: normaliza tildes y mayúsculas para emparejar con iconos PNG en `iconos/gasto/` o `iconos/ingreso/` y colores pastel por defecto.
* **`includes/movimientos_handler.php`**:
  * Handler POST central para:
    1. Guardar nuevo movimiento (`tipoMovimiento`).
    2. Editar movimiento (`form_type === 'editar_movimiento'`).
    3. Eliminar movimiento (`form_type === 'eliminar_movimiento'`).
    4. Borrar historial completo (`form_type === 'borrar_historial_completo'`).
    5. Eliminar cuenta del usuario (`form_type === 'eliminar_cuenta'`).
  * Utiliza el patrón **PRG (Post/Redirect/Get)**: tras ejecutar la operación SQL, redirige inmediatamente mediante `header("Location: " . $return_url)` para evitar reenvíos accidentales con F5.
  * Solo acepta categorías visibles para el usuario (ya no crea categorías nuevas a partir del nombre recibido) y, al editar, toma el tipo real del movimiento desde la base.
* **`includes/vista_movimientos_tipo.php`**: vista compartida de `ingresos.php` y `gastos.php` (totales del período, tabla/tarjetas, filtros). Cada página solo define `$tipo_vista`.

---

## 6. Frontend y Comunicación con Backend

Trackify utiliza una combinación de tres estrategias de comunicación:

1. **Hidratación Inline (Data Hydration PHP -> JS):**
   En `index.php`, los datos agregados para Chart.js se serializan directamente en el servidor:
   ```php
   $js_torta_labels = json_encode($torta_labels);
   $js_torta_data   = json_encode($torta_data);
   ```
   En la sección de scripts de la vista se inicializan constantes JavaScript (`const tortaLabels = <?php echo $js_torta_labels; ?>;`). Esto garantiza renderizado instantáneo de gráficos sin latencia de red.
2. **Formularios Estándar con Patrón PRG:**
   Modales y vistas envían formularios multipart/urlencoded mediante POST a `includes/movimientos_handler.php`, `categorias.php` u `objetivos.php`. El backend procesa y redirige con un código HTTP 302 hacia la URL de origen (`return_url`).
3. **Peticiones Asíncronas (AJAX / Fetch API):**
   * `api/cambiar_moneda.php`: Recibe `POST` con `FormData` (y cabecera `X-CSRF-Token`) para persistir la preferencia de moneda en BD.
   * `export_movimientos.php`: Endpoint autenticado (`GET`) que responde con array JSON formateado con `JSON_UNESCAPED_UNICODE` para consumo de `TrackifyExportCSV.fetchAndExport()`.
   * `ia.php`: `js/ia.js` realiza `fetch('./ia.php')` enviando payload JSON `{ pregunta: ... }` con cabecera `X-CSRF-Token`; espera `{ respuesta }`.

---

## 7. Sistema de Monedas (Bimoneda ARS / USD)

* **Principio Fundamental:** **La base de datos almacena SIEMPRE todos los montos en Pesos Argentinos (ARS)**.
* **Preferencia de Usuario:** Guardada en la columna `usuarios.moneda_principal` y sincronizada en `$_SESSION['moneda']` y `localStorage.getItem('trackify_moneda')`.
* **Obtención de Cotización:** `js/moneda.js` consulta `https://api.exchangerate-api.com/v4/latest/USD` con fallback a `https://api.frankfurter.app/latest?from=USD&to=ARS`. El valor se almacena en `localStorage` con una vigencia (TTL) de 30 minutos.
* **Renderizado de Montos:** Las etiquetas HTML que muestran valores monetarios incorporan el atributo `data-ars="12500.50"` (y opcionalmente `data-prefijo="+"` o `data-prefijo="-"`). La función `convertirMontos()` formatea automáticamente el contenido en pantalla a ARS o USD según corresponda.
* **Formularios con monto (movimientos y objetivos):** todo `<form data-form-moneda>` tiene un input visible `.js-monto-visible`, un selector `.js-moneda-form` (ARS/USD), un input oculto `.js-monto-ars` (el único que se envía) y una vista previa `.js-preview-conversion`. `js/moneda.js` intercepta el `submit`, valida que el monto sea > 0, **espera** la cotización si la moneda es USD, completa el oculto en ARS y recién entonces envía. Si no hay cotización disponible no envía (nunca usa un valor inventado). El servidor vuelve a validar el monto.
* **Edición:** los modales de edición precargan el monto en ARS (selector en ARS) para no alterarlo por redondeo al convertir ida y vuelta.
* **Visualización:** todo monto en pantalla lleva `data-ars`; los gráficos del dashboard escuchan el evento `trackify:moneda` y se redibujan en la moneda elegida.

---

## 8. Asistente de IA y Recomendaciones

* **Estado Real del Código:**
  * En `index.php`, las **Recomendaciones IA** en el dashboard son generadas mediante un motor heurístico de reglas en PHP (`$recomendaciones_fallback`), evaluando el signo del balance y si el ratio de gasto supera el 80% de los ingresos.
  * Existe una interfaz de chat flotante (`#chatFlotante`; la cruz del botón lo minimiza a una pestaña en el borde, recordado durante la sesión del navegador) en el dashboard y una página completa en `ia.php`. Ambas usan `js/ia.js` (sugerencias de preguntas con el chat vacío, indicador animado de escritura), que envía `POST ./ia.php` con JSON `{ pregunta }` y la cabecera `X-CSRF-Token`, y espera `{ respuesta }` (o `{ error }`).
  * El chat se conecta a **Groq** con la clave `GROQ_API_KEY` de `.env` (ver `.env.example`).
  * *Pendiente de versionar:* el handler que llama a Groq no está commiteado en esta rama (el `ia.php` del repositorio solo contiene la vista). Debe subirse al repositorio para que el resto del equipo tenga el chat funcionando. Ese handler tiene que leer la clave con `getenv('GROQ_API_KEY')` y, como todo POST autenticado, pasa por la verificación CSRF de `includes/seguridad.php`.

---

## 9. Convenciones y Reglas Críticas para no Romper el Proyecto

1. **NO alterar la regla de moneda en BD:** Nunca guardes valores en USD directamente en la tabla `movimientos`. Si agregas un formulario nuevo de movimientos, asegúrate de enviar el monto convertido a ARS.
2. **Categorías predeterminadas vs. propias:** las de `id_usuario IS NULL` son compartidas y de solo lectura. Las consultas de categorías para un usuario usan `id_usuario IS NULL OR id_usuario = ?` (helpers en `includes/categorias_meta.php`). Al eliminar una categoría propia, sus movimientos pasan a "Otros gastos"/"Otros ingresos"; nunca se borran movimientos por borrar una categoría.
3. **Respetar el filtro `id_usuario`:** Cada `SELECT`, `UPDATE` o `DELETE` sobre movimientos, objetivos o categorías propias debe contener la cláusula `id_usuario = ?`.
4. **Respetar el orden de carga de scripts y modales:** Todo el HTML de modales se encuentra centralizado en `includes/footer.php`. Si se crea un nuevo modal, debe agregarse en `footer.php` e incluir su `id` en el array `modales` de `js/main.js` para que `cerrarModal()` funcione de manera universal. Si el modal tiene un monto, debe seguir la estructura `data-form-moneda` (ver sección 7).
4b. **POST nuevos:** cualquier formulario POST recibe el token CSRF automáticamente; un `fetch` POST debe enviar la cabecera `X-CSRF-Token: window.csrfToken`.
5. **No romper la compatibilidad de selectores:** Para selectores personalizados de categorías, se debe mantener la estructura `.custom-select-wrapper > .custom-select-trigger + .custom-select-options` que inicializa `js/main.js`.
6. **Validación de fechas en Flatpickr:** Los objetivos solo aceptan fechas presentes o futuras (`minDate: today`), mientras que los movimientos solo aceptan fechas pasadas o presentes (`maxDate: today`).
