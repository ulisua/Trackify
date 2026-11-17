=====================================================================
  TRACKIFY — README / MANUAL TÉCNICO
  Aplicación web de finanzas personales (PHP + PostgreSQL)
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
Backend ........ PHP 8.x (sin framework), PDO (includes/db.php)
Base de datos .. PostgreSQL 13+ (probado con 16). Hasta 11/2026 la
                 app usaba MySQL/MariaDB de XAMPP.
Servidor web ... Apache (XAMPP)
Frontend ....... HTML5, CSS3 modular, JavaScript vanilla (sin build)
Librerías JS ... Chart.js 4.6.1 (gráficos), SweetAlert2 11.26.25
                 (alertas), Flatpickr 4.6.13 (calendarios). Por CDN.
Librería PHP ... PHPMailer (incluida en /phpmailer)
Servicios ...... Google OAuth 2.0, EmailJS o SMTP (Mailtrap en desarrollo),
                 Groq API (IA), ExchangeRate-API y Frankfurter
                 (cotización del dólar)


---------------------------------------------------------------------
3. REQUISITOS
---------------------------------------------------------------------
- XAMPP con PHP 8.0 o superior (el código usa str_contains y match).
- Extensiones PHP habilitadas: pdo_pgsql, curl, openssl, mbstring.
  (XAMPP trae pdo_pgsql pero desactivada: ver paso 3 de la instalación.)
- PostgreSQL 13+ (instalador para Windows: postgresql.org/download;
  trae pgAdmin y psql). XAMPP no lo incluye.
- Conexión a internet para: CDN de librerías, cotización, IA, Google
  y envío de emails.
- Opcional: cuentas en Google Cloud (OAuth), EmailJS o Mailtrap (SMTP) y
  Groq (API key gratuita).


---------------------------------------------------------------------
4. INSTALACIÓN PASO A PASO (XAMPP, Windows)
---------------------------------------------------------------------
1) Clonar el repositorio dentro de htdocs:
     cd C:\xampp\htdocs
     git clone <url-del-repositorio> trackify
2) Instalar PostgreSQL (anotar la contraseña del usuario "postgres";
   puerto 5432).
3) Habilitar el driver en PHP: en C:\xampp\php\php.ini quitar el ";"
   de ";extension=pdo_pgsql". Iniciar (o reiniciar) Apache desde el
   panel de XAMPP. MySQL ya no hace falta.
4) Crear la base "trackify" e importar el esquema. Por consola:
     createdb -U postgres trackify
     psql -U postgres -d trackify -f trackify_postgres.sql
   o en pgAdmin: Create > Database "trackify", después Query Tool >
   abrir trackify_postgres.sql > Ejecutar.
   Trae el esquema completo y las categorías predeterminadas, sin
   usuarios.
5) Copiar .env.example como .env (en la raíz del proyecto) y
   completar los valores (ver sección 5), en especial DB_PASS.
   Si tenías datos en la base MySQL vieja, copiarlos con (MySQL de
   XAMPP encendido):
     C:\xampp\php\php.exe herramientas/migrar_mysql_a_postgres.php
   Ver 10_migracion_mysql_a_postgresql.txt.
6) Abrir http://localhost/trackify
   En la primera carga, includes/migraciones.php agrega lo que falte y
   crea las categorías predeterminadas (ver sección 6).
7) Registrarse. Si EmailJS/SMTP no está configurado, el email no llega:
   ver "Problemas frecuentes".


---------------------------------------------------------------------
5. CONFIGURACIÓN (.env)
---------------------------------------------------------------------
El archivo .env NO se sube a Git (está en .gitignore). env.php lo lee
línea por línea y la función env('CLAVE', 'por_defecto') devuelve el
valor.

  APP_URL              URL base, sin barra final (se usa en los emails)
  DB_HOST / DB_PORT /  Conexión a PostgreSQL. Por defecto: localhost /
  DB_NAME / DB_USER /  5432 / trackify / postgres / (vacío)
  DB_PASS
  DATABASE_URL         Alternativa a los DB_*: la URL que dan Render,
                       Neon o Supabase (postgresql://usuario:clave@host:5432/base)
  DB_SSLMODE           "require" para bases en la nube; vacío en local
  GOOGLE_CLIENT_ID     Credenciales OAuth de Google Cloud Console.
  GOOGLE_CLIENT_SECRET La redirect URI debe estar registrada allá.
  GOOGLE_REDIRECT_URI  Ej.: http://localhost/trackify/auth/google/callback.php
  EMAILJS_SERVICE_ID / Envío de los códigos por la API de EmailJS (si
  _TEMPLATE_ID /       están las 4, se usa en lugar de SMTP; necesario
  _PUBLIC_KEY /        en Render, que bloquea SMTP).
  _PRIVATE_KEY
  SMTP_HOST/PORT/      Servidor de correo para los códigos de
  USER/PASS/FROM       verificación (Mailtrap en desarrollo).
  GROQ_API_KEY         API key de Groq (cada integrante usa la suya).
  GROQ_MODEL           Modelo de Groq. Por defecto: openai/gpt-oss-120b

Sin EmailJS ni SMTP no se pueden verificar cuentas nuevas por email; sin
GROQ_API_KEY el chat responde con un mensaje de "no configurado".


---------------------------------------------------------------------
6. BASE DE DATOS Y MIGRACIONES AUTOMÁTICAS
---------------------------------------------------------------------
Tablas: usuarios, movimientos, categorias, metas_ahorro, consejos_ia.
Diccionario completo: ver 02_tabla_base_de_datos.txt. Diagrama: ver
09_diagramas_uml_dfd_der.txt.

Regla de oro: movimientos.monto y los montos de metas_ahorro se
guardan SIEMPRE en pesos (ARS).

Acceso a datos (includes/db.php):
- Clase BaseDatos sobre PDO (driver pdo_pgsql). conexion.php la crea
  como $conn. Métodos: fila(), filas(), valor(), ejecutar(),
  insertar() (con RETURNING), transaccion().
- Reglas de SQL para PostgreSQL: ver docs/ARCHITECTURE.md sección 3.

Migraciones automáticas (includes/migraciones.php, llamado por conexion.php):
- Se hace UNA consulta a information_schema/pg_indexes que revisa si
  están todas las columnas e índices esperados (COLUMNAS_ESPERADAS).
  El resultado se recuerda en la sesión 5 minutos.
- Si falta alguna, se ejecutan los "ALTER TABLE ... ADD COLUMN IF NOT
  EXISTS". Son solo ADITIVAS (nunca borran).
- Si metas_ahorro.monto_objetivo no tiene decimales, se amplía a
  NUMERIC(12,2) una sola vez.
- Unificación con la base de devulises (se corrige una sola vez):
  categorias.Icono_tipo -> icono, color admite NULL, usuarios.uuid
  completo y CHAR(36) NOT NULL UNIQUE, consejos_ia con secuencia (autoincremento),
  movimientos.moneda. Por eso cualquier base vieja de cualquiera de los
  dos integrantes funciona sin reimportar.
- Con la base al día, la consulta inicial no dispara ningún ALTER.
- Si la tabla categorias tiene 5 filas o menos, se insertan las 27
  categorías predeterminadas (11 de ingreso, 16 de gasto) con su ícono
  y color (includes/categorias_meta.php).

Para agregar una columna nueva:
  1. Agregar el ALTER TABLE ... ADD COLUMN IF NOT EXISTS en
     SQL_COLUMNAS_NUEVAS.
  2. Agregar el nombre de la columna en COLUMNAS_ESPERADAS.
  3. Agregarla también en trackify_postgres.sql.
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

GET  -> valida sesión -> consulta la BD -> arma HTML -> navegador.
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
  conexion.php, env.php, includes/db.php, includes/migraciones.php
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
CSS (lista CSS_BASE en includes/assets.php; se enlazan en paralelo):
  variables.css (paleta y tipografía) -> themes.css (claro/oscuro con
  [data-theme]) -> base.css -> layout.css -> components/*.css ->
  pages/*.css -> responsive.css (se carga al final; corte principal en
  900 px). Todo CSS/JS propio se enlaza con cssLink() / jsScript(), que
  agregan ?v=<fecha de modificación> para el caché.
  Detalle: docs/CSS_ARCHITECTURE.md

JS:
  main.js ............ CSRF en formularios, alertas de marca
                       (alertaTrackify, confirmarTrackify, avisoTrackify,
                       confirmarEliminacion), menú móvil, Flatpickr,
                       modales, selects personalizados, mensajes flash
  moneda.js .......... cotización y conversión
  ia.js .............. chat
  perfil.js .......... perfil, contraseña, modo oscuro, exportación CSV,
                       zona de peligro
  categorias.js ...... pestañas y orden de tarjetas
  filtros_movimientos.js
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
8. Columna nueva en la BD -> migración en includes/migraciones.php
   (sección 6) y en trackify_postgres.sql.
9. Consultas siempre con $conn->fila()/filas()/... y parámetros "?".


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
   -> PostgreSQL no está iniciado (Servicios de Windows:
      "postgresql-x64-16"), o DB_* de .env no coincide.
"Falta la extensión pdo_pgsql" / "could not find driver"
   -> Falta habilitar extension=pdo_pgsql en php.ini y reiniciar Apache.
No llega el email de verificación
   -> Revisar EMAILJS_* o SMTP_* en .env y el log de PHP. Un
      "Error EmailJS HTTP 403 ... no Private Key was provided" significa
      que EMAILJS_PRIVATE_KEY no es la Private Key de esa cuenta
      (copiarla de nuevo en dashboard.emailjs.com > Account). Con Mailtrap los correos aparecen en la
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
"column ... does not exist" después de un pull
   -> Cerrar sesión y volver a entrar (la revisión del esquema se
      recuerda 5 minutos por sesión): la migración automática agrega la
      columna. Si no, revisar que esté en COLUMNAS_ESPERADAS.


---------------------------------------------------------------------
17. LIMITACIONES CONOCIDAS Y PENDIENTES
---------------------------------------------------------------------
- Sin hosting: la app corre solo en local.
- consejos_ia y movimientos.es_hormiga no se usan todavía.
- Sin tests automatizados; las pruebas son manuales.
- El Docker mencionado en la bitácora (Sem 31) no está versionado en
  el repositorio.
- RESUMEN_EJECUTIVO.md menciona js/header-dropdown.js y otros .md que
  no existen en el repositorio.
