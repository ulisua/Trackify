# Prompt actualizado de contexto — Trackify

## Visión general
Trackify es una aplicación web de gestión financiera personal desarrollada con PHP, MySQL, HTML, CSS y JavaScript. El objetivo del proyecto es ayudar al usuario a controlar ingresos, gastos, categorías, objetivos de ahorro y análisis financiero, además de incorporar una capa de IA para ofrecer recomendaciones y respuestas contextualizadas.

## Stack actual
- Frontend: HTML, CSS, JavaScript (vanilla)
- Backend: PHP
- Base de datos: MySQL / MariaDB
- Servidor local: XAMPP / Apache
- Gráficos: Chart.js
- IA: integración con API de Groq (modelo LLaMA u otro disponible)
- Exportación: CSV para movimientos

## Arquitectura general
El proyecto sigue una arquitectura cliente-servidor sencilla:

1. El usuario interactúa con la interfaz web.
2. El frontend envía peticiones al backend mediante `fetch()` o formularios HTML.
3. PHP procesa la lógica, valida sesiones y consulta la base de datos.
4. El backend devuelve respuestas JSON o redirige a páginas dinámicas.
5. El frontend renderiza datos, gráficas y resultados de IA.

## Estructura principal del proyecto
- `index.php` → dashboard principal con resumen financiero, gráficos y recomendaciones.
- `ingresos.php` / `gastos.php` → vistas específicas para movimientos.
- `categorias.php` → gestión de categorías personalizadas con iconos y colores.
- `objetivos.php` → creación y seguimiento de metas de ahorro.
- `perfil.php` → vista y edición del perfil del usuario.
- `ia.php` → pantalla/chat de IA y posible endpoint de interacción.
- `export_movimientos.php` → exportación de movimientos en CSV.
- `conexion.php` → configuración y migraciones iniciales de la base de datos.
- `includes/` → componentes reutilizables (header, footer, handlers).
- `js/` → lógica frontend, gráficos y chat IA.
- `css/` → estilos visuales del sistema.

## Funcionalidades actuales
- Registro e inicio de sesión de usuarios.
- Gestión de sesiones y protección de rutas.
- CRUD de movimientos (ingresos/gastos).
- Gestión de categorías con metadatos visuales.
- Dashboard con KPIs financieros básicos.
- Gráficos de gastos por categoría y comparación ingresos/gastos por mes.
- Seguimiento de objetivos de ahorro.
- Perfil de usuario.
- Exportación CSV.
- Chat o asistente IA con respuestas financieras.
- Eliminación de historial y eliminación de cuenta (con validaciones).

## Modelo de datos relevante
El sistema usa varias tablas, entre ellas:
- `usuarios`
- `categorias`
- `movimientos`
- `metas_ahorro`
- `consejos_ia` (si aplica según la implementación actual)

La lógica del negocio se basa en que cada movimiento está relacionado con un usuario y una categoría, y que las consultas siempre deben respetar la pertenencia del usuario autenticado.

## IA y análisis financiero
La IA debe usarse para complementar la toma de decisiones del usuario, no para reemplazar el control financiero manual. El sistema puede analizar:
- balance general
- proporción de gastos sobre ingresos
- comportamientos por categoría
- tendencias mensuales
- metas de ahorro
- recomendaciones personalizadas con base en datos reales del usuario

El objetivo es que la IA no responda de forma genérica, sino con contexto financiero local y de la sesión real del usuario.

## Seguridad y buenas prácticas
- Uso de sesiones para aislar datos por usuario.
- Consultas preparadas (`prepare()`) para evitar SQL injection.
- Escapado de salida para evitar XSS.
- Manejo de rutas privadas y validación de acceso.
- Separación de claves sensibles mediante variables de entorno.
- Evitar exponer secretos dentro del código fuente.

## Nuevos horizontes / mejoras esperadas
Además de lo ya implementado, el proyecto puede evolucionar hacia:
- análisis más avanzado de patrones de gasto
- alertas automáticas sobre gastos inusuales
- sugerencias de presupuesto mensual
- clasificación inteligente de categorías
- predicciones simples de ahorro o riesgo financiero
- reportes semanales o mensuales exportables
- historial de consejos IA con mejor contexto
- mejor integración entre frontend, backend y modelo de IA
- mejoras de UX y responsividad
- soporte para múltiples monedas o presupuestos por categoría

## Instrucciones para el desarrollo continuo
Cuando se continúe trabajando en el proyecto, priorizar:
- integración real y segura entre frontend y backend
- uso de datos reales del usuario para IA
- claridad en la lógica de negocio
- escalabilidad y mantenibilidad del código
- buenas prácticas de seguridad y arquitectura
- respuestas prácticas, estructuradas y aplicables al código

## Contexto operativo recomendado para la IA
Trackify es una aplicación financiera personal con enfoque práctico, visual y útil. El asistente debe entender que:
- el usuario necesita claridad y control sobre su dinero
- el sistema está orientado a decisiones concretas y recomendaciones accionables
- la IA debe ofrecer ayuda financiera pero siempre basada en datos reales del usuario y en el contexto de la sesión
- las respuestas deben ser claras, útiles y no excesivamente técnicas si no es necesario

## Ejemplo de uso del chat IA
El frontend puede enviar una solicitud como esta:

```js
fetch("ia.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ pregunta: "¿Estoy gastando demasiado este mes?" })
})
```

La respuesta esperada debería ser un JSON con una propiedad como `respuesta` y, si es necesario, más metadata adicional para el frontend.
