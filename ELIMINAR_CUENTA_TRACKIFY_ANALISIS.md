# Análisis completo de "Eliminar cuenta" en Trackify

## 1. Arquitectura general del proyecto

Trackify es una aplicación web construida con:

- PHP para lógica de backend y acceso a base de datos.
- HTML para la estructura de las páginas.
- CSS para la presentación y estilo.
- JavaScript vanilla para interacciones, modales y confirmaciones en el navegador.

No se utiliza React, no hay componentes React, no hay `.jsx`, ni hooks.

El proyecto funciona con una arquitectura tradicional de servidor:

- El frontend renderiza páginas PHP completas.
- Los datos se recuperan de MySQL y se insertan/actualizan mediante formularios que envían POST.
- El JS solo maneja la interfaz, los modales y las confirmaciones.


## 2. Persistencia real de datos

El almacenamiento principal es la base de datos MySQL.

### Tablas clave detectadas

- `usuarios`
  - Contiene la información del usuario registrado.
  - Clave primaria: `id_usuario`.
- `movimientos`
  - Guarda ingresos y gastos.
  - Contiene `id_usuario`, `id_categoria`, `monto`, `tipo`, `descripcion`, `fecha`.
- `categorias`
  - Guarda categorías globales.
  - No están asociadas por usuario.
- `metas_ahorro`
  - Guarda objetivos de ahorro por usuario.

### Uso de LocalStorage

- Búsqueda de `localStorage` en el proyecto no encontró escrituras ni lecturas de datos.
- Por tanto, no hay flujo principal de datos en LocalStorage.
- Se agregó limpieza local de claves con prefijo `trackify` como medida de seguridad, pero el proyecto no depende de eso.

### Flujo de sesión

- `login.php` crea `$_SESSION['usuario_id']` y `$_SESSION['usuario_nombre']`.
- `includes/header.php` valida sesión y redirige al login si no existe usuario autenticado.
- `logout.php` destruye la sesión.


## 3. Archivos clave en el análisis

### Frontend / JS

- `js/main.js`
  - Controla el menú lateral y los modales de ingreso/gasto.
  - Usa `window.dbCategorias` para poblar categorías en los formularios.
- `js/perfil.js`
  - Controla la edición de perfil.
  - Controla exportación CSV.
  - Controla el modal de borrado de historial.
  - Aquí se agregó la lógica de `Eliminar cuenta`.
- `js/utils/exportCSV.js`
  - Genera y descarga CSV.

### Backend / PHP

- `includes/movimientos_handler.php`
  - Inserta, edita y elimina movimientos.
  - Borra todo el historial.
  - Aquí se agregó el handler de `eliminar_cuenta`.
- `perfil.php`
  - Renderiza la página de perfil y la zona de peligro.
  - Contiene el botón de eliminar cuenta.
- `includes/header.php`
  - Inicializa sesión y `window.dbCategorias`.
- `conexion.php`
  - Conecta a la DB.
  - Inserta categorías por defecto si no existen.
- `categorias.php`
  - Crea, edita y elimina categorías.
  - Importante: elimina movimientos vinculados a la categoría.


## 4. Variables globales y estructuras importantes

### `window.dbCategorias`

- Creada en `includes/header.php`.
- Es un objeto con dos propiedades: `ingreso` y `gasto`.
- Contiene arrays de nombres de categorías.
- Uso principal: `js/main.js` para mostrar las opciones en los selectores personalizados.
- Si se cambia o elimina, los formularios de movimiento pueden tener categorías incorrectas o vacías.

### `descripcionesSugeridas` en `js/main.js`

- Mapea nombre de categoría a una sugerencia de descripción.
- Se usa al seleccionar categorías en modales.
- No se relaciona directamente con la eliminación de cuenta.

### `secciones` en `js/perfil.js`

- Define campos de edición en el perfil.
- Usado solo para alternar edición de campos personales y de seguridad.

### Arrays PHP en `index.php`

- `$torta_labels`, `$torta_data`
- `$barras_labels`, `$barras_ingresos`, `$barras_gastos`
- `$ultimos_movimientos`
- Se usan para generar los gráficos y la lista de movimientos del dashboard.

### Arrays PHP en `categorias.php`

- `$gastos`, `$ingresos`
- Se usan para renderizar la lista de categorías y sus totales.


## 5. Funciones relevantes y su propósito

### JS: `abrirModal(tipo)`

- Abre el modal de ingreso o gasto.
- Carga categorías desde `window.dbCategorias` o fallback local.
- Reinicia la descripción.
- Se ejecuta con botones `+ Ingreso` y `+ Gasto`.

### JS: `attachExportHandler()`

- Asocia el botón `btnExportarDatos` a la exportación CSV.
- Llama a `window.TrackifyExportCSV.fetchAndExport(...)`.

### JS: `abrirModalBorrar()` / `validarConfirmacionBorrar()`

- Controlan el modal de borrado de historial completo.
- Son independientes de la nueva eliminación de cuenta.

### JS: `abrirConfirmacionEliminarCuenta()`

- Muestra un confirm dialog usando SweetAlert2.
- Es la nueva función agregada para el flujo de eliminar cuenta.
- Solo si el usuario confirma, envía el formulario oculto `formEliminarCuenta`.

### JS: `limpiarLocalStorageTrackify()`

- Elimina claves de LocalStorage que comienzan con `trackify`.
- Es una limpieza segura y localizada.
- No ejecuta `localStorage.clear()`.

### JS: `attachEliminarCuentaHandler()`

- Asocia el click del botón `btnEliminarCuenta` a `abrirConfirmacionEliminarCuenta()`.
- Se ejecuta al cargarse la página.

### PHP: `includes/movimientos_handler.php`

- `form_type === 'borrar_historial_completo'`
  - Borra todos los movimientos del usuario.
  - Se mantiene intacta la lógica previa.
- `form_type === 'eliminar_cuenta'`
  - Borra movimientos del usuario.
  - Borra metas de ahorro del usuario.
  - Borra al usuario mismo.
  - Destruye la sesión y las cookies de sesión.
  - Redirige a `login.php`.


## 6. Análisis de “Eliminar cuenta” y lo que se implementó

### Qué hay que borrar

- `movimientos` del usuario actual.
- `metas_ahorro` del usuario actual.
- `usuarios` del usuario actual.

### Qué NO hay que borrar

- `categorias`
  - Son globales y compartidas.
  - No deben eliminarse porque romperían el catálogo de categorías.
- Tablas globales o datos del sistema.
- No se debe limpiar la DB completa con `DROP TABLE` ni `TRUNCATE`.

### Qué estados deben resetearse

- La sesión PHP del usuario.
- El estado de login del navegador.
- Cualquier dato de usuario en la sesión.
- La UI debe dejar de mostrar datos personales y pasar al login.

### Qué renderizados deben actualizarse

- No se necesita refrescar la página actual con datos nuevos.
- Al eliminar la cuenta, se redirige a `login.php`.
- Con la sesión destruida, no hay datos fantasmas ni acceso posterior.

### Confirmación obligatoria

- Se agregó un diálogo con SweetAlert2 en el estilo del proyecto.
- Texto exacto:
  - Título: `¿Seguro que querés eliminar tu cuenta?`
  - Texto: `Esta acción eliminará permanentemente todos tus datos y no se puede deshacer.`
- Botones:
  - `Cancelar`
  - `Eliminar definitivamente`

### Feedback visual

- Después de confirmar, el botón desaparece su icono y muestra `Eliminando...`.
- Se deshabilita el botón para evitar múltiples envíos.


## 7. Riesgos considerados

### Evitar datos fantasma

- La sesión se destruye y se redirige al login.
- Si el usuario vuelve atrás, ya no está autenticado.

### Evitar borrados excesivos

- No se usó `localStorage.clear()`.
- Solo se borran claves con prefijo `trackify` en LocalStorage.
- En la DB, solo se borra la información asociada al usuario específico.

### Errores posibles

- Si `Swal` no carga, el formulario oculto aún existe, pero el botón no tiene confirmación. En este proyecto, `SweetAlert2` ya está cargado en el footer.
- Si `includes/movimientos_handler.php` falla, el usuario no se redirige correctamente.
- Si hay reglas de integridad referencial adicionales en la DB, la eliminación de movimientos y metas debe suceder antes de borrar `usuarios`. Eso ya está cubierto.
- Si la redirección usa rutas relativas desde `includes/movimientos_handler.php`, el browser puede cargar `/includes/login.php` en lugar de la página real de inicio de sesión, rompiendo los enlaces relativos a `registro.php` y a otros recursos.

### Corrección de rutas de login

- Se ajustó `includes/header.php` para calcular dinámicamente la ruta base desde `$_SERVER['PHP_SELF']`.
- Se ajustó `includes/movimientos_handler.php` para redirigir a `../login.php` desde `includes/movimientos_handler.php` cuando sea necesario.
- Esto evita que el navegador termine en `localhost/includes/login.php` y garantiza que la página de login se cargue en la ubicación correcta.


## 8. Cambios exactos realizados

### `perfil.php`

- Se añadió `id="btnEliminarCuenta"` al botón de eliminar cuenta.
- Se añadió un formulario oculto `#formEliminarCuenta` con `form_type=eliminar_cuenta`.

### `js/perfil.js`

- Se agregó la función `limpiarLocalStorageTrackify()`.
- Se agregó la función `abrirConfirmacionEliminarCuenta()`.
- Se agregó la función `attachEliminarCuentaHandler()`.
- Se integró la carga del handler de eliminar cuenta en el DOMContentLoaded.

### `includes/movimientos_handler.php`

- Se agregó el bloque `form_type === 'eliminar_cuenta'`.
- Se borran en orden:
  1. movimientos
  2. metas_ahorro
  3. usuario
- Se destruye la sesión y la cookie de sesión.
- Se redirige a `login.php`.


## 9. Cómo probarlo

1. Iniciar sesión con un usuario existente.
2. Ir a `perfil.php`.
3. Hacer clic en `Eliminar cuenta`.
4. Comprobar que aparece la confirmación con el texto correcto.
5. Confirmar.
6. Verificar que se redirige a `login.php`.
7. Intentar volver al dashboard: no debe ser accesible sin login.
8. Comprobar la base de datos:
   - El usuario debe faltar en `usuarios`.
   - No deben quedar `movimientos` ni `metas_ahorro` asociadas.


## 10. Observaciones finales

- El flujo ahora está implementado de manera mínima y segura.
- No se cambió la arquitectura principal del proyecto.
- No se hizo ningún refactor innecesario.
- Se mantuvo la estética y el estilo del proyecto usando SweetAlert2 y clases existentes.

---

Este documento contiene el análisis completo y los cambios aplicados para la funcionalidad de "Eliminar cuenta" en Trackify.