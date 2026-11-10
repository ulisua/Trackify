=====================================================================
  TRACKIFY — README / MANUAL TÉCNICO
  Aplicación web de finanzas personales (PHP + MySQL)
  Versión documentada: commit 190451c (06/11/2026), rama devagus
=====================================================================

ÍNDICE
  1. Descripción
  2. Stack tecnológico
  3. Requisitos
  4. Instalación paso a paso (XAMPP)
  5. Configuración (.env)
  6. Base de datos y migraciones automáticas
  7. Arquitectura y flujo de una petición
  8. Módulos y archivos principales
  9. Endpoints (rutas que reciben POST / devuelven JSON)
 10. Sistema bimoneda ARS / USD
 11. Asistente de IA (Groq)
 12. Seguridad
 13. Frontend: CSS, JS y librerías
 14. Convenciones para modificar el código
 15. Ramas y flujo de trabajo con Git
 16. Problemas frecuentes (troubleshooting)
 17. Limitaciones conocidas y pendientes


---------------------------------------------------------------------
1. DESCRIPCIÓN
---------------------------------------------------------------------
Trackify permite registrar ingresos y gastos por categoría, ver
métricas y gráficos por período (semana / quincena / mes), ver los
montos en pesos o dólares, administrar metas de ahorro y consultar a
un asistente de IA que responde con los datos reales del usuario.


---------------------------------------------------------------------
2. STACK TECNOLÓGICO
---------------------------------------------------------------------
Backend ........ PHP 8.x (sin framework), extensión mysqli
Base de datos .. MySQL / MariaDB 10.4 (incluida en XAMPP)
Servidor web ... Apache (XAMPP)
Frontend ....... HTML5, CSS3 modular, JavaScript vanilla (sin build)
Librerías JS ... Chart.js 4.6.1 (gráficos), SweetAlert2 11.26.25
                 (alertas), Flatpickr 4.6.13 (calendarios). Por CDN.
Librería PHP ... PHPMailer (incluida en /phpmailer)
Servicios ...... Google OAuth 2.0, SMTP (Mailtrap en desarrollo),
                 Groq API (IA), ExchangeRate-API y Frankfurter
                 (cotización del dólar)


---------------------------------------------------------------------
3. REQUISITOS
---------------------------------------------------------------------
- XAMPP con PHP 8.0 o superior (el código usa str_contains y match).
- Extensiones PHP habilitadas: mysqli, curl, openssl, mbstring.
- MariaDB 10.0.2+ / MySQL 8.0.29+ (por "ADD COLUMN IF NOT EXISTS").
- Conexión a internet para: CDN de librerías, cotización, IA, Google
  y envío de emails.
- Opcional: cuentas en Google Cloud (OAuth), Mailtrap (SMTP) y
  Groq (API key gratuita).


---------------------------------------------------------------------
4. INSTALACIÓN PASO A PASO (XAMPP, Windows)
---------------------------------------------------------------------
1) Clonar el repositorio dentro de htdocs:
     cd C:\xampp\htdocs
     git clone <url-del-repositorio> trackify
2) Abrir el panel de XAMPP e iniciar Apache y MySQL.
3) Entrar a http://localhost/phpmyadmin y crear una base llamada
   "trackify" (cotejamiento utf8mb4_general_ci).
4) Importar trackify.sql en esa base (pestaña Importar).
   Nota: el volcado trae 2 usuarios de prueba; podés borrarlos.
5) Copiar .env.example como .env (en la raíz del proyecto) y
   completar los valores (ver sección 5).
6) Abrir http://localhost/trackify
   En la primera carga, conexion.php agrega las columnas que falten y
   crea las categorías predeterminadas (ver sección 6).
7) Registrarse. Si el SMTP no está configurado, el email no llega:
   ver "Problemas frecuentes".


---------------------------------------------------------------------
5. CONFIGURACIÓN (.env)
---------------------------------------------------------------------
El archivo .env NO se sube a Git (está en .gitignore). env.php lo lee
línea por línea y la función env('CLAVE', 'por_defecto') devuelve el
valor.

  APP_URL              URL base, sin barra final (se usa en los emails)
  DB_HOST / DB_USER /  Conexión a MySQL. Por defecto: localhost / root /
  DB_PASS / DB_NAME    (vacío) / trackify
  GOOGLE_CLIENT_ID     Credenciales OAuth de Google Cloud Console.
  GOOGLE_CLIENT_SECRET La redirect URI debe estar registrada allá.
  GOOGLE_REDIRECT_URI  Ej.: http://localhost/trackify/auth/google/callback.php
  SMTP_HOST/PORT/      Servidor de correo para los códigos de
  USER/PASS/FROM       verificación (Mailtrap en desarrollo).
  GROQ_API_KEY         API key de Groq (cada integrante usa la suya).
  GROQ_MODEL           Modelo de Groq. Por defecto: openai/gpt-oss-120b

Sin SMTP no se pueden verificar cuentas nuevas por email; sin
GROQ_API_KEY el chat responde con un mensaje de "no configurado".


---------------------------------------------------------------------
6. BASE DE DATOS Y MIGRACIONES AUTOMÁTICAS
---------------------------------------------------------------------
Tablas: usuarios, movimientos, categorias, metas_ahorro, consejos_ia.
Diccionario completo: ver 02_tabla_base_de_datos.txt. Diagrama: ver
09_diagramas_uml_dfd_der.txt.

Regla de oro: movimientos.monto y los montos de metas_ahorro se
guardan SIEMPRE en pesos (ARS).

Migraciones automáticas (conexion.php):
- En cada request se hace UNA consulta a information_schema que cuenta
  si están todas las columnas esperadas ($columnas_esperadas).
- Si falta alguna, se ejecutan los "ALTER TABLE ... ADD COLUMN IF NOT
  EXISTS". Son solo ADITIVAS (nunca borran).
- Si metas_ahorro.monto_objetivo no tiene decimales, se amplía a
  DECIMAL(12,2) una sola vez.
- Si la tabla categorias tiene 5 filas o menos, se insertan las 27
  categorías predeterminadas (11 de ingreso, 16 de gasto) con su ícono
  y color (includes/categorias_meta.php).

Para agregar una columna nueva:
  1. Agregar el ALTER TABLE ... ADD COLUMN IF NOT EXISTS en la lista.
  2. Agregar el nombre de la columna en $columnas_esperadas.
  (Si no se hace el paso 2, la migración nunca se dispara.)


---------------------------------------------------------------------
7. ARQUITECTURA Y FLUJO DE UNA PETICIÓN
---------------------------------------------------------------------
Aplicación multipágina renderizada en el servidor (patrón "Page
Controller"): cada archivo .php de la raíz es a la vez ruta,
controlador y vista. Detalle en 08_arquitectura_general.txt.

Esqueleto de toda página:

  $page = 'gastos';                       // marca el menú activo
  require_once 'conexion.php';            // BD + .env + seguridad + migraciones
  require_once 'includes/movimientos_handler.php'; // (si procesa POST)
  require_once 'includes/header.php';     // sesión, <head>, navbar, sidebar
  ... consultas y HTML de la página ...
  require_once 'includes/footer.php';     // modales, librerías, main.js

GET  -> valida sesión -> consulta MySQL -> arma HTML -> navegador.
POST -> verifica CSRF -> valida datos -> INSERT/UPDATE/DELETE ->
        mensaje flash -> redirect (patrón Post/Redirect/Get).


---------------------------------------------------------------------
8. MÓDULOS Y ARCHIVOS PRINCIPALES
---------------------------------------------------------------------
Autenticación
  login.php ................ Login con email/contraseña + botón Google
  registro.php ............. Alta de usuario (bcrypt, uuid, @usuario)
  verificar_email.php ...... Valida el código OTP de 6 dígitos
  reenviar_codigo.php ...... Reenvía el código (máx. 1 por minuto)
  auth/google/callback.php . OAuth 2.0: canjea el code, crea/vincula
  logout.php ............... Cierra sesión y borra la cookie
  includes/mailer.php ...... Envío de códigos con PHPMailer

Finanzas
  index.php ................ Dashboard: KPIs, gráficos, recomendaciones
  movimientos.php .......... Listado completo con filtros
  ingresos.php / gastos.php  Vista por tipo (comparten
                             includes/vista_movimientos_tipo.php)
  includes/movimientos_handler.php  Alta, edición y baja de
                             movimientos, borrar historial, eliminar cuenta
  categorias.php ........... CRUD de categorías propias
  objetivos.php ............ Metas de ahorro: alta, aportes, edición,
                             baja con reintegro
  export_movimientos.php ... JSON de movimientos para exportar a CSV

Usuario
  perfil.php ............... Datos personales, contraseña, modo oscuro,
                             zona de peligro
  api/cambiar_password.php . Cambiar / crear contraseña
  api/cambiar_moneda.php ... Guarda la moneda preferida

IA
  ia.php ................... GET: página del chat. POST: API que
                             consulta a Groq
  js/ia.js ................. Chat (página completa y botón flotante)

Comunes
  conexion.php, env.php
  includes/header.php, footer.php, periodo.php, seguridad.php,
  categorias_meta.php, tema_inicial.php, auth_layout.php


---------------------------------------------------------------------
9. ENDPOINTS
---------------------------------------------------------------------
Todos requieren sesión iniciada y (si son POST) token CSRF: campo
csrf_token en formularios o cabecera X-CSRF-Token en fetch.

POST  (cualquier página con movimientos) form_type / tipoMovimiento
      -> includes/movimientos_handler.php. Redirige a return_url.
POST  categorias.php     crear / editar / eliminar categoría propia
POST  objetivos.php      crear / aportar / editar / eliminar meta
POST  perfil.php         actualizar nombre y @usuario
POST  api/cambiar_moneda.php    moneda=ARS|USD -> JSON
POST  api/cambiar_password.php  accion=cambiar|enviar_codigo|crear
                                -> { ok, mensaje } o { error }
GET   export_movimientos.php    -> [ {tipo, categoria, monto, fecha,
                                   descripcion}, ... ]
POST  ia.php   JSON { pregunta, historial? } -> { respuesta } / { error }
               Códigos: 401 sin sesión, 422 pregunta vacía o > 1000
               caracteres, 429 menos de 2 s entre preguntas, 503 sin
               API key.


---------------------------------------------------------------------
10. SISTEMA BIMONEDA ARS / USD
---------------------------------------------------------------------
- La BD guarda todo en ARS. La moneda elegida se guarda en
  usuarios.moneda_principal, $_SESSION['moneda'] y localStorage.
- js/moneda.js busca la cotización en ExchangeRate-API y, si falla, en
  Frankfurter. Se cachea 30 minutos en localStorage.
- Todo monto en pantalla lleva data-ars="12500.50"; convertirMontos()
  lo muestra en la moneda elegida.
- Formularios con monto (<form data-form-moneda>): el usuario escribe
  en ARS o USD; el JS convierte y envía SOLO el campo oculto en ARS. Si
  no hay cotización, no envía. El servidor vuelve a validar.


---------------------------------------------------------------------
11. ASISTENTE DE IA (GROQ)
---------------------------------------------------------------------
- js/ia.js hace POST a ia.php con { pregunta, historial }.
- ia.php arma un "contexto financiero" del usuario (totales de los
  últimos meses, gastos por categoría del mes, metas) con
  contextoFinancieroIA() y lo manda como mensaje de sistema a
  https://api.groq.com/openai/v1/chat/completions con cURL.
- Se envían como máximo los últimos 6 turnos de la conversación.
- Las "recomendaciones" del dashboard (index.php) NO usan la IA: son
  reglas fijas en PHP (balance negativo, gasto > 80 % del ingreso, etc.).


---------------------------------------------------------------------
12. SEGURIDAD
---------------------------------------------------------------------
- Contraseñas: password_hash / password_verify (bcrypt). Largo 8–72,
  no puede contener el nombre ni el email.
- SQL: siempre prepared statements (bind_param).
- Aislamiento: toda consulta filtra por id_usuario de la sesión.
- XSS: htmlspecialchars() en toda salida; jsAttr() para valores dentro
  de atributos onclick.
- CSRF: includes/seguridad.php rechaza con 403 cualquier POST
  autenticado sin token válido.
- Redirecciones: urlRetornoSegura() solo permite rutas del propio sitio.
- Validación de servidor: montoValido() (> 0 y con tope),
  fechaValida(), listas cerradas para tipo/estado.
- Verificación de email: código de 6 dígitos, vence en 15 minutos,
  máximo 5 intentos, reenvío cada 60 s.
- OAuth: parámetro state contra CSRF y exige email verificado de Google.
- Login: el mensaje de error no revela si el email existe.
- Secretos en .env, nunca en el código.
- Eliminar cuenta exige escribir "ELIMINAR MI CUENTA" (validado en el
  servidor) y borra todo en una transacción.


---------------------------------------------------------------------
13. FRONTEND: CSS, JS Y LIBRERÍAS
---------------------------------------------------------------------
CSS (styles.css importa todo):
  variables.css (paleta y tipografía) -> themes.css (claro/oscuro con
  [data-theme]) -> base.css -> layout.css -> components/*.css ->
  pages/*.css -> responsive.css (se carga al final; corte principal en
  900 px). Detalle: docs/CSS_ARCHITECTURE.md

JS:
  main.js ............ CSRF en formularios, alertas de marca
                       (alertaTrackify, confirmarTrackify, avisoTrackify,
                       confirmarEliminacion), menú móvil, Flatpickr,
                       modales, selects personalizados, mensajes flash
  moneda.js .......... cotización y conversión
  ia.js .............. chat
  perfil.js .......... perfil, contraseña, modo oscuro, zona de peligro
  categorias.js ...... pestañas y orden de tarjetas
  filtros_movimientos.js, utils/exportCSV.js
  Detalle: docs/JAVASCRIPT_ARCHITECTURE.md

Modo oscuro: includes/tema_inicial.php aplica el tema antes de pintar
la página (evita el "parpadeo" blanco).


---------------------------------------------------------------------
14. CONVENCIONES PARA MODIFICAR EL CÓDIGO
---------------------------------------------------------------------
1. Nunca guardar montos en USD en la base.
2. Todo SELECT/UPDATE/DELETE sobre datos de usuario lleva
   "WHERE id_usuario = ?".
3. Categorías del sistema (id_usuario NULL) son de solo lectura. Al
   borrar una categoría propia, sus movimientos pasan a "Otros
   gastos"/"Otros ingresos"; nunca se borran movimientos.
4. Modales usados en varias páginas -> includes/footer.php. Modales de
   una sola página -> en esa página. Siempre con la clase .modal.
5. No usar alert()/confirm() ni Swal.fire directo: usar las funciones
   de main.js.
6. Un fetch POST nuevo debe mandar la cabecera X-CSRF-Token.
7. Fechas: movimientos solo hasta hoy (maxDate), objetivos desde hoy
   (minDate).
8. Columna nueva en la BD -> migración en conexion.php (sección 6).


---------------------------------------------------------------------
15. RAMAS Y FLUJO DE TRABAJO CON GIT
---------------------------------------------------------------------
  main ............ versión inicial (registro y login, abril 2026)
  develop ......... integración del equipo
  devagus ......... rama de trabajo de Agustina Cecch (front + integración)
  devulises ....... rama de trabajo de Ulises Suarez (autenticación)
  fix, fusion-trackify ... ramas auxiliares históricas
Flujo: cada uno trabaja en su rama -> merge a develop -> main para
entregas.


---------------------------------------------------------------------
16. PROBLEMAS FRECUENTES
---------------------------------------------------------------------
"No se pudo conectar con la base de datos"
   -> MySQL no está iniciado en XAMPP o DB_NAME no coincide.
No llega el email de verificación
   -> Revisar SMTP_* en .env. Con Mailtrap los correos aparecen en la
      bandeja de Mailtrap, no en el correo real.
"Error al iniciar sesión con Google" / redirect_uri_mismatch
   -> GOOGLE_REDIRECT_URI debe ser idéntica a la registrada en Google
      Cloud Console.
El chat dice "falta GROQ_API_KEY" o "la clave no es válida"
   -> Completar o renovar la key en .env.
Los montos en USD no se pueden cargar
   -> No hay cotización (sin internet o APIs caídas).
Error 403 al enviar un formulario
   -> Token CSRF vencido (la sesión expiró). Recargar la página.
"Unknown column ..." después de un pull
   -> Recargar cualquier página: la migración automática agrega la
      columna. Si no, revisar que esté en $columnas_esperadas.


---------------------------------------------------------------------
17. LIMITACIONES CONOCIDAS Y PENDIENTES
---------------------------------------------------------------------
- Sin hosting: la app corre solo en local.
- consejos_ia y movimientos.es_hormiga no se usan todavía.
- trackify.sql no incluye las columnas agregadas por migración.
- Sin tests automatizados; las pruebas son manuales.
- El Docker mencionado en la bitácora (Sem 31) no está versionado en
  el repositorio.
- RESUMEN_EJECUTIVO.md menciona js/header-dropdown.js y otros .md que
  no existen en el repositorio.
