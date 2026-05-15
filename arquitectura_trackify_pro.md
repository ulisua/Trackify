# Arquitectura y Documentación Técnica de Trackify 🚀 (Nivel Pro)

Este documento detalla la arquitectura, el modelo de datos, la gestión de estado y las consideraciones de seguridad del proyecto **Trackify**. Está orientado a desarrolladores, ingenieros de software y arquitectos.

---

## 1. Stack Tecnológico y Patrón Arquitectónico

Trackify está construido como una **aplicación monolítica Server-Side Rendered (SSR)** tradicional.

*   **Backend / Server**: PHP 8.x (Procedimental/Híbrido).
*   **Base de Datos**: MySQL / MariaDB (Relacional).
*   **Frontend**: HTML5 Semántico, CSS Vanilla (arquitectura basada en utilidades/componentes locales), Vanilla JavaScript (ES6+).
*   **Librerías Externas**: Chart.js (cargado vía CDN) para visualización de datos.

El proyecto no utiliza un framework MVC formal (como Laravel o Symfony), sino que implementa un patrón **Front Controller simplificado / Page Controller**, donde cada archivo `.php` en la raíz actúa como enrutador, controlador y vista de manera secuencial.

---

## 2. Modelo de Datos (Esquema Relacional)

La persistencia se maneja en la base de datos `usuarios` (definida en `trackify.sql`). El esquema está normalizado (mayormente 3NF) para evitar redundancias.

### Entidades Principales:

*   **`usuarios`**:
    *   `id_usuario` (INT, PK, AI)
    *   `nombre` (VARCHAR 50)
    *   `email` (VARCHAR 100, UNIQUE) - *Indexado para login rápido.*
    *   `clave` (VARCHAR 255) - *Almacena hashes Bcrypt generados por `password_hash()`.*
    *   `fecha_registro` (DATETIME)
*   **`categorias`**:
    *   `id_categoria` (INT, PK, AI)
    *   `nombre` (VARCHAR 50)
    *   `tipo` (ENUM: `'ingreso'`, `'gasto'`)
    *   *Nota:* Las categorías son globales, lo que permite consultas agregadas más rápidas, pero en el flujo de `index.php` se implementó la creación dinámica ("upsert") si el usuario ingresa una nueva.
*   **`movimientos`** (Tabla Transaccional Core):
    *   `id_movimiento` (INT, PK, AI)
    *   `id_usuario` (INT, FK) -> `usuarios(id_usuario)`
    *   `id_categoria` (INT, FK) -> `categorias(id_categoria)`
    *   `monto` (DECIMAL 12,2) - *Soporta montos altos con precisión decimal.*
    *   `tipo` (ENUM: `'ingreso'`, `'gasto'`) - *Redundancia desnormalizada intencional para evitar JOINs constantes con `categorias` al calcular el balance total.*
    *   `descripcion` (VARCHAR 255, NULL)
    *   `fecha` (DATE)
    *   `es_hormiga` (TINYINT 1, DEFAULT 0)
*   **`metas_ahorro`**:
    *   `id_meta` (INT, PK, AI)
    *   `id_usuario` (INT, FK) -> `usuarios(id_usuario)`
    *   `nombre_meta` (VARCHAR 100)
    *   `monto_objetivo` (DECIMAL 10,0)
    *   `monto_actual` (DECIMAL 10,0)
    *   `fecha_limite` (DATE)
    *   `descripcion` (VARCHAR 255, DEFAULT NULL) - *Añadido vía migración.*
    *   `estado` (ENUM: `'activo'`, `'inactivo'`, `'logrado'`, DEFAULT `'activo'`) - *Añadido vía migración.*
*   **`consejos_ia`**:
    *   `id_consejos` (INT, PK, AI)
    *   `id_usuario` (INT, FK) -> `usuarios(id_usuario)`
    *   `mensaje` (TEXT)
    *   `fecha` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

### Migraciones Dinámicas en Tiempo de Ejecución
El archivo `conexion.php` implementa un patrón de "auto-migración" y "seeding". Al instanciar la conexión `mysqli`, ejecuta consultas `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` para actualizar el esquema (ej. estados de metas) de forma silenciosa, y verifica el `COUNT(*)` de la tabla `categorias` para inyectar un array de categorías por defecto usando `INSERT IGNORE`.

---

## 3. Flujo de Control y Gestión de Estado

### Autenticación y Aislamiento de Tenancy
*   **Sesiones**: Todo el estado del usuario logueado se mantiene en la superglobal `$_SESSION['usuario_id']`.
*   **Protección de Rutas**: Los archivos revisan si `session_status() !== PHP_SESSION_ACTIVE` y validan la existencia de la sesión.
*   **Data Isolation (Tenancy)**: Todas las queries transaccionales (`SELECT`, `INSERT`, `UPDATE`, `DELETE`) en la capa de negocio incluyen mandatoriamente la cláusula `WHERE id_usuario = ?` inyectada vía `bind_param`. Esto previene fugas de datos (IDOR - Insecure Direct Object Reference).

### Procesamiento de Formularios (Patrón PRG)
El sistema utiliza el patrón **PRG (Post/Redirect/Get)** rigurosamente. 
En `index.php`, cuando se recibe un `$_POST` para un movimiento o un objetivo:
1. Se valida el payload y se inserta en BD.
2. Se ejecuta un `header("Location: " . $_SERVER['PHP_SELF']); exit();`
*Esto previene la re-sumisión accidental de formularios mediante el botón "Atrás" del navegador o recargas de página.*

### Integración Backend a Frontend (Data Hydration)
Para enviar datos complejos (como las series temporales de ingresos y gastos) desde PHP hacia Vanilla JS (para renderizar Chart.js), se utiliza serialización en el mismo documento:
```php
$js_torta_labels  = json_encode($torta_labels);
$js_torta_data    = json_encode($torta_data);
```
Luego, en el bloque `<script>`, las constantes de JS se hidratan directamente:
```javascript
const tortaLabels = <?php echo $js_torta_labels; ?>;
```
Este enfoque evita peticiones XHR/Fetch adicionales a una API REST, reduciendo el TTFB (Time to First Byte) general, ya que la página se sirve completamente poblada en un solo RTT (Round Trip Time).

---

## 4. Análisis de Rendimiento (Performance)

### Optimizaciones Implementadas:
*   **Agrupaciones a Nivel SQL**: En lugar de traer todos los registros y sumarlos en PHP, el motor de BD hace el trabajo pesado: `SELECT SUM(monto) ...`.
*   **Cálculos Condicionales en SQL**: Para el gráfico de barras comparativo, se utilizan estructuras `CASE` combinadas con funciones de fecha (ej: `DATE_FORMAT`, `DATE_SUB`) para pivotar los datos directamente en MySQL:
    `SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END)`

### Cuellos de Botella Potenciales (Escalabilidad):
A medida que la tabla `movimientos` crezca para un usuario, el escaneo de los últimos 6 meses puede volverse costoso sin índices compuestos. 
**Recomendación de Índices faltantes**:
Debería crearse un índice compuesto en `movimientos (id_usuario, tipo, fecha)` para optimizar el dashboard, ya que casi todos los cálculos filtran por estos tres ejes simultáneamente.

---

## 5. Postura de Seguridad (Security Posture)

### Puntos Fuertes:
1.  **Prevención de SQL Injection**: Uso estricto del driver `mysqli` con sentencias preparadas (`prepare()`) y vinculación de parámetros (`bind_param()`) en el 100% de las transacciones DML. No hay concatenación cruda de variables en los strings SQL.
2.  **Prevención de XSS (Cross-Site Scripting)**: Las salidas dinámicas enviadas por el usuario (como la `descripcion` de un movimiento) se escapan en la vista usando `htmlspecialchars($mv['descripcion'])`.
3.  **Hashing de Contraseñas**: Se utiliza el algoritmo estándar de la industria (Bcrypt por defecto) a través de `password_hash()` y `password_verify()`.

### Vectores de Vulnerabilidad Abiertos:
1.  **CSRF (Cross-Site Request Forgery)**: Los formularios de creación de movimientos y metas no implementan tokens anti-CSRF (`<input type="hidden" name="csrf_token" ...>`). Un atacante podría forzar al usuario a enviar requests maliciosos si está autenticado en la sesión.
2.  **Rate Limiting**: El endpoint de `login.php` no cuenta con protección contra ataques de fuerza bruta (no se bloquea la IP ni se incrementan los delays tras N intentos fallidos).

---

## 6. Lógica de Negocio y Componentes Específicos

*   **Resolución Dinámica de Categorías (Upsert behavior)**:
    En `index.php`, al guardar un movimiento, si el usuario tipea una categoría, el sistema ejecuta un `SELECT` para buscar el `id_categoria` según el nombre y el tipo. Si no existe, realiza un `INSERT` en caliente para crearla y usa el `insert_id`.
*   **Motor de Recomendaciones "IA"**:
    Actualmente (`$recomendaciones_fallback`), es un motor heurístico basado en umbrales (Thresholds). Evalúa:
    *   *Signo del Balance*: (Ingresos - Gastos >= 0).
    *   *Burn Rate / Ratio de Gasto*: `(Gastos / Ingresos) * 100`. Dispara banderas rojas (alertas tipo warning) si el ratio supera el 80%.
*   **Metas de Ahorro**:
    Implementan una máquina de estados implícita. Las columnas `monto_objetivo` y `monto_actual` permiten calcular fracciones de completitud (`progress = (monto_actual / monto_objetivo) * 100`). El estado (`activo`, `logrado`, `inactivo`) habilita filtrado en las vistas de "objetivos completados".
