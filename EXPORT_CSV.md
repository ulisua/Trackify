# Exportar CSV — Trackify

Este documento explica en profundidad la implementación de la funcionalidad "Exportar datos" (.csv) en Trackify. Está pensado para que cualquier persona (incluso sin experiencia previa) pueda entender qué hace cada parte, por qué se eligieron las soluciones y cómo modificar o extender el sistema.

---

## Resumen de alto nivel

- Se añadió un endpoint `export_movimientos.php` que devuelve los movimientos del usuario en formato JSON.
- Se añadió una utilidad cliente `js/utils/exportCSV.js` que convierte un array de objetos JavaScript a CSV, maneja el encoding UTF-8 (BOM) y descarga el archivo usando `Blob` y `URL.createObjectURL`.
- Se añadió un handler en `js/perfil.js` para el botón `#btnExportarDatos` que solicita los movimientos, genera el CSV y lanza la descarga automática.
- Se añadió retroalimentación visual simple al usuario (texto del botón mientras se exporta y confirmación breve).

---

## ¿Por qué esta arquitectura?

Requisitos clave del proyecto:
- No queremos lógica gigante dentro del botón.
- Necesitamos código reutilizable, modular y mantenible.
- Queremos control total del CSV (encoding, escapado) sin depender de librerías externas.

Decisiones:
1. Separar la obtención de datos (endpoint PHP) de la generación del CSV (cliente) permite:
   - Evitar pasar grandes blobs de CSV por la red si prefieres generar en servidor más tarde.
   - Mantener el código de conversión CSV en un util que puede usarse para otras exportaciones.
2. Implementar la conversión CSV cliente usando una función propia nos da control total sobre:
   - Cómo escapamos comillas y comas
   - Manejo de saltos de línea
   - Prevenir problemas de encoding agregando BOM
3. Descargar usando `Blob` + `URL.createObjectURL` es la técnica más compatible y simple sin librerías.

---

## Flujo completo (paso a paso)

1. Usuario visita `perfil.php` y hace click en "Exportar" (`#btnExportarDatos`).
2. `js/perfil.js` captura el evento y construye un nombre de archivo dinámico: `trackify_YYYY-MM-DD.csv`.
3. `perfil.js` invoca `TrackifyExportCSV.fetchAndExport('export_movimientos.php', filename, columns)`.
4. `fetchAndExport` hace `fetch('export_movimientos.php', { credentials: 'same-origin' })`:
   - Esto solicita al servidor la lista de movimientos del usuario (JSON).
   - `export_movimientos.php` verifica la sesión, consulta la tabla `movimientos` (JOIN con `categorias`) y devuelve `JSON_UNESCAPED_UNICODE`.
5. El cliente recibe el array de objetos y llama a `exportArrayToCSV`.
6. `exportArrayToCSV`:
   - Genera la primera línea (encabezados) con las claves o las columnas pasadas.
   - Para cada objeto, escapa valores con `escapeCSV` (dobla comillas internas y envuelve en comillas si es necesario).
   - Construye el texto CSV completo.
   - Prepara un `Blob` con tipo `text/csv;charset=utf-8;` y **pre-pend el BOM UTF-8** (0xEF,0xBB,0xBF) para mejorar compatibilidad con Excel y otros programas.
   - Crea un `ObjectURL` con `URL.createObjectURL(blob)`.
   - Crea un `a` temporal, le asigna `href` a ese URL y `download` con el nombre de archivo.
   - Simula `click()` en el `a` para iniciar la descarga.
   - Limpia el DOM y revoca el ObjectURL con `URL.revokeObjectURL(url)`.

---

## Por qué usamos `Blob` y `URL.createObjectURL`

- `Blob` representa datos binarios de manera eficiente en el navegador. Nos permite construir un archivo en memoria (puede ser texto, binario, imágenes, etc.).
- `URL.createObjectURL(blob)` devuelve una URL temporal que apunta al blob en memoria. Es una URL segura y rápida que el navegador puede usar para descargar el blob como si fuera un recurso remoto.
- Procedimiento alternativo sería usar `data:` URIs, pero no son eficientes para blobs grandes y pueden tener problemas de encoding o límites de tamaño en algunos navegadores.
- Revocar el object URL (`URL.revokeObjectURL`) libera recursos en el navegador.

---

## Encoding: cómo aseguramos UTF-8/Excel

- Problema: Excel en Windows históricamente no detecta correctamente UTF-8 y muestra caracteres acentuados como garabatos.
- Solución práctica: prependemos el BOM UTF-8 (bytes `EF BB BF`) al inicio del archivo CSV. Esto indica explícitamente al consumidor que el flujo es UTF-8.
- En `js/utils/exportCSV.js` se crea el `Uint8Array([0xEF,0xBB,0xBF])` y se incluye en el `Blob` antes del texto CSV.
- Además usamos `JSON_UNESCAPED_UNICODE` en el servidor para no escapar caracteres unicode en la respuesta JSON.

---

## Manejo de comas, comillas y saltos de línea (escapeCSV)

- Reglas usadas:
  - Si el campo contiene comas, comillas dobles o saltos de línea, lo rodeamos con comillas dobles.
  - Dentro de un campo entrecomillado, las comillas dobles se duplican (ej: `He said "Hi" -> "He said ""Hi""`).
- Esto es compatible con RFC 4180 y con la mayoría de aplicaciones que procesan CSV.

---

## Detalles del endpoint `export_movimientos.php`

- Ruta: `/export_movimientos.php`
- Requiere sesión activa (`$_SESSION['usuario_id']`). Si no hay sesión devuelve 401.
- Ejecuta una query sencilla (JOIN con `categorias`) y devuelve un array de objetos con claves:
  - `id` (entero)
  - `tipo` (string: 'Ingreso'|'Gasto')
  - `categoria` (string)
  - `monto` (float)
  - `fecha` (YYYY-MM-DD)
  - `descripcion` (string)
- Retorna con `Content-Type: application/json; charset=utf-8`.

---

## ¿Dónde modificar columnas exportadas?

- En `js/perfil.js` la llamada a `fetchAndExport` incluye un tercer argumento opcional `columns`.
- Esa lista define el orden y las claves a exportar: `['tipo','categoria','monto','fecha','descripcion']`.
- Si querés exportar más campos (por ejemplo `id`), agregalo al array y la utilidad lo incluirá.

---

## Cómo cambiar iconos / añadir campos relacionados (contexto extra)

La exportación CSV está separada del mapeo de iconos. Si querés incluir, por ejemplo, el `icono` o `color` de la categoría en la exportación:
1. Modificar `export_movimientos.php` para que incluya los campos adicionales desde la tabla `categorias` (ej: `c.icono AS categoria_icono, c.color AS categoria_color`).
2. En el cliente, añadir las nuevas claves en la lista `columns` cuando se llame a `fetchAndExport`.

Esto mantiene la lógica modular.

---

## Posibles mejoras

- Exportar directamente CSV desde el servidor (si prefieres que el server genere el CSV y lo envíe con headers `Content-Disposition: attachment; filename=...`). Esto evita pasar JSON y reconstruir CSV en cliente. Recomendado cuando datasets son muy grandes.
- Paginación / streaming: para listados inmensos, implementar streaming o compresión en el servidor.
- Soporte de formatos adicionales: XLSX (con librerías tipo SheetJS) si se necesita formato nativo de Excel (pero eso trae dependencia grande).
- Firma / logs de auditoría: registrar exportaciones realizadas por usuarios para auditoría.

---

## Archivos clave y breve descripción

- `export_movimientos.php`: endpoint JSON con movimientos del usuario.
- `js/utils/exportCSV.js`: util cliente con `exportArrayToCSV(rows, filename, columns)` y `fetchAndExport(url, filename, columns)`.
- `js/perfil.js`: handler del botón `#btnExportarDatos` que usa `TrackifyExportCSV.fetchAndExport`.
- `perfil.php`: plantilla que contiene el botón y carga los scripts.

---

## Ejemplo: añadir un nuevo campo a exportar

1. Editar `export_movimientos.php` para devolver `c.icono AS categoria_icono`.
2. Llamar en cliente con:

```js
await window.TrackifyExportCSV.fetchAndExport('export_movimientos.php', 'trackify.csv', ['tipo','categoria','categoria_icono','monto','fecha','descripcion']);
```

La utilidad mantendrá el orden indicado.

---

## Preguntas frecuentes técnicas (FAQ)

Q: ¿Por qué no mando CSV directamente desde el servidor? 
A: Se puede. La implementación actual escinde responsabilidades: el servidor sólo entrega datos JSON y el cliente decide el formato final. Esto ayuda en casos donde queramos ofrecer múltiples formatos (CSV, JSON, XLSX) sin cambiar el backend.

Q: ¿Qué pasa con usuarios en Excel que no ven acentos? 
A: El BOM UTF-8 ayuda a que Excel detecte UTF-8. Alternativamente podrías servir CSV en `Windows-1252` (latin1) pero eso rompe compatibilidad global y complica idiomas.

Q: ¿Es esto seguro? 
A: Sí, el endpoint utiliza la sesión (`$_SESSION['usuario_id']`) para devolver sólo los movimientos del usuario autenticado. Si necesitás controles adicionales, añadí autorizaciones o rate-limiting.

---

## Conclusión

La solución implementada es:
- modular (separación de concerns),
- robusta (manejo de encoding y escapes),
- escalable (puede añadir columnas o formatos sin tocar el botón),
- compatible con Excel/Hoja de cálculo gracias al BOM y al escapo RFC 4180.

Si querés, puedo ahora:
- implementar la versión server-side que devuelve CSV directo (para datasets grandes),
- añadir un modal / toast visual más sofisticado (SweetAlert2 ya está en el proyecto y puede usarse),
- añadir filtros en la exportación (rango de fechas, tipo, categoría) para exportar solo una porción de movimientos.


*** End Patch