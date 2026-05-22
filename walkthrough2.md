# Mejoras UX/UI Globales Implementadas

He finalizado la implementación de todas las mejoras estéticas y funcionales que solicitaste a lo largo del proyecto, logrando una experiencia de usuario más fluida, moderna y visualmente unificada.

## 1. Sistema de Objetivos
- **Lógica de Vencimiento:** Se agregó la verificación de fechas en `objetivos.php`. Si un objetivo llega a su límite de fecha sin ser logrado, ahora ingresa en un estado "Vencido".
- **Estado Visual:** Los objetivos vencidos adquieren un filtro gris y opacidad reducida (`grayscale`, `opacity`) que transmite de forma innegable su inactividad temporal.
- **Restricción de Botones:** En los vencidos ya no se permite el botón "+ Agregar ahorro" ni "Editar", quedando expuesto solamente un botón "Eliminar" resaltado con fondo rojo pastel y texto carmesí para alertar de la acción destructiva.
- **Colores Dinámicos:** Las barras de progreso ahora se generan de forma dinámica consumiendo la gama pastel de colores del proyecto (`mint`, `lime`, `pink`, `lavender`, `teal`, `violet`).

## 2. Alertas Personalizadas (SweetAlert2)
- Reemplacé definitivamente los antiguos diálogos `confirm()` del navegador nativo.
- Integré **SweetAlert2** de forma global en todo el proyecto.
- Se ha codificado una alerta estilo *Trackify* (con el verde oscuro `#084734` y bordes redondeados) para **confirmar eliminaciones** en: Objetivos, Categorías, Gastos e Ingresos, además de un diseño adaptado para confirmar el **Cierre de Sesión**.

## 3. Date Picker Premium (Flatpickr)
- Agregué y configuré globalmente la librería **Flatpickr**.
- Todos los `input type="date"` (como el de nuevo gasto, ingreso o fechas de vencimiento de objetivo) ahora invocan un calendario superpuesto altamente estético, reemplazando la pobre visualización del selector nativo del navegador.

## 4. Efecto "Glow" Dinámico
- Se codificó globalmente en el CSS una sutil emisión de sombra o *"glow"* para todas las tarjetas (cards, boxes, objetivos y categorías). 
- Al pasar el cursor, verás un destello tenue en color verde lima (`#CFF27C`) que reacciona maravillosamente con el fondo claro de la interfaz, dando una sensación vívida al uso general del dashboard.

## 5. Chat IA con Glassmorphism
- El diseño del Chat Inteligente en `ia.php` y el Chat Flotante sufrieron una remodelación. 
- Implementé **Glassmorphism**: Fondo en color `#1E1B26` con 60% de opacidad y `backdrop-filter: blur(16px)`. Esto fusiona los mensajes elegantemente con el contenido que haya detrás, sumado a una cabecera semitransparente verde oscura en la caja flotante.

## 6. Métricas Funcionales en el Dashboard
- La página principal `index.php` ahora toma la información real de la base de datos para mostrarte las 3 pequeñas tarjetas clave:
  - **% Gasto:** Relación entre ingresos y egresos.
  - **Mayor categoría:** Expone de manera dinámica en cuál rubro tu cuenta tiene el mayor déficit basado en el gráfico de torta.
  - **Gasto mensual:** La suma real de todos los gastos que ocurrieron dentro del mes en curso y año en curso (ej: Noviembre).

## 7. Tablas Semánticas
- Actualizamos los encabezados (`<thead>`) de las grillas de historial de movimientos.
- **Gastos**: Cabecera color rojo pastel con fuente bordó.
- **Ingresos**: Cabecera color verde pastel con fuente esmeralda oscuro.
