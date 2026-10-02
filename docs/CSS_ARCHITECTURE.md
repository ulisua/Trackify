# Arquitectura CSS de Trackify 🎨

> **Documento técnico del sistema de estilos para desarrolladores e Inteligencias Artificiales (Antigravity / Copilot).**  
> Describe la organización modular en Vanilla CSS, tokens de diseño, sistema bitema (claro/oscuro), componentes reutilizables y reglas para no romper la consistencia visual.

---

## 1. Visión General y Principios de Diseño

* **Tecnología:** **Vanilla CSS puro** (ES6+ Custom Properties, CSS Grid, Flexbox). No se utilizan preprocesadores (SASS/LESS) ni frameworks utilitarios como Tailwind o Bootstrap.
* **Organización:** **Arquitectura Modular por Capas** (Inspirada en ITCSS / SMACSS).
* **Punto de Entrada Único:** `styles.css` en la raíz actúa exclusivamente como orquestador mediante directivas `@import`.
* **Cero Parpadeo (Anti-FOUC):** El tema visual activo se inyecta en el atributo `data-theme` del elemento `<html>` de forma síncrona en el `<head>`, previniendo cualquier destello blanco o desfasaje durante la carga.

---

## 2. Estructura de Archivos y Jerarquía de Carga

```
trackify/
├── styles.css                       # ENTRY POINT: Orquestador global con directivas @import
├── moneda.css                       # Estilos del selector de divisas y períodos en el navbar
└── css/
    ├── variables.css                # 1. Tokens inmutables de marca y fuentes
    ├── themes.css                   # 2. Variables contextuales de tema (Claro / Oscuro)
    ├── base.css                     # 3. Resets globales, scrollbars y animaciones @keyframes
    ├── layout.css                   # 4. Estructura de página: .layout, .sidebar, .grid, tablas
    ├── components/                  # 5. Componentes de UI reutilizables
    │   ├── navbar.css               #    Barra de navegación superior y logo
    │   ├── sidebar.css              #    Menú lateral colapsable y navegación activa
    │   ├── cards.css                #    Tarjetas genéricas, cajas .box y estados glow
    │   ├── forms.css                #    Inputs, custom select dropdowns, Flatpickr y modales
    │   ├── buttons.css              #    Botones de acción, variantes y hover states
    │   └── charts.css               #    Contenedores de gráficos Chart.js
    └── pages/                       # 6. Hojas de estilo específicas por página (carga diferida)
        ├── auth.css                 #    Login, Registro y Verificación (orbes de luz, tarjetas auth)
        ├── categorias.css           #    Grid de categorías, tabs y las 7 paletas pastel
        ├── dashboard.css            #    KPIs del dashboard, lista de movimientos y chat flotante
        ├── ia.css                   #    Vista completa de IA con Glassmorphism y orbes
        ├── objetivos.css            #    Metas de ahorro, barras .fill-* y tarjetas vencidas
        └── perfil.css               #    Formularios de perfil, avatar y zona de peligro
    └── responsive.css               # 7. Sistema responsive centralizado (escalado móvil compactado)
```

### Orden Estricto de Importación en `styles.css`

```css
/* ===== TRACKIFY MAIN STYLESHEET (ENTRY POINT) ===== */

/* 1. Variables de Marca y Sistema de Temas */
@import url('css/variables.css');
@import url('css/themes.css');

/* 2. Estilos de Base y Layout Estructural */
@import url('css/base.css');
@import url('css/layout.css');

/* 3. Componentes Reutilizables Comunes */
@import url('css/components/navbar.css');
@import url('css/components/sidebar.css');
@import url('css/components/cards.css');
@import url('css/components/forms.css');
@import url('css/components/buttons.css');
@import url('css/components/charts.css');

/* 4. Sistema Responsive Centralizado */
@import url('css/responsive.css');
```

> [!NOTE]
> Las hojas de `css/pages/` **NO se importan en `styles.css`**. Se inyectan bajo demanda en el `<head>` mediante la variable PHP `$extra_css` dentro de cada página específica (ej. `$extra_css = '<link rel="stylesheet" href="css/pages/dashboard.css">';`).

---

## 3. Tokens de Diseño y Variables Globales

### Tokens de Marca Inmutables (`css/variables.css`)
Son constantes de identidad visual que **no cambian** con el tema claro u oscuro:

```css
:root {
    --brand-dark: #1E1B26;          /* Fondo oscuro profundo / púrpura noche */
    --brand-green-dark: #084734;    /* Verde esmeralda profundo corporativo */
    --brand-green-light: #CFF27C;   /* Verde lima fluorescente / acento principal */
    --brand-pink: #EA73F5;          /* Rosa vibrante de acento secundario */
    --brand-purple: #700353;        /* Bordó / violeta oscuro */
    --font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
```

---

## 4. Sistema Bitema: Claro y Oscuro (`css/themes.css`)

El aspecto visual se conmuta mediante el atributo `data-theme` en la etiqueta `<html>`:

```html
<html lang="es" data-theme="dark">
```

### Variables Semánticas por Tema

| Token CSS | Modo Claro (`:root, [data-theme="light"]`) | Modo Oscuro (`[data-theme="dark"]`) | Uso Principal |
| :--- | :--- | :--- | :--- |
| `--bg-primary` / `--bg-body` | `#F8FAFC` (Slate ultra claro) | `#121016` (Noche profundo) | Fondo general de la página (`body`) |
| `--bg-secondary` / `--bg-hover` | `#F1F5F9` | `#1E1B26` | Fondos secundarios, hovers y tabs |
| `--card-bg` / `--bg-card` | `#ffffff` | `#1E1B26` | Fondo de tarjetas, cajas y modales |
| `--text-primary` | `#1E1B26` | `#F8FAFC` | Tipografía principal de alta legibilidad |
| `--text-secondary` | `#64748B` | `#94A3B8` | Etiquetas, fechas y textos de soporte |
| `--text-muted` | `#94A3B8` | `#64748B` | Textos deshabilitados y scrollbars |
| `--border-color` | `#E2E8F0` | `#383344` | Bordes de inputs, tarjetas y separadores |
| `--input-bg` | `#F8FAFC` | `#2A2635` | Fondo de campos de formulario y selects |
| `--input-text` | `#1E1B26` | `#F8FAFC` | Color del texto digitado en formularios |
| `--accent` | `#EA73F5` | `#EA73F5` | Rosa de énfasis |
| `--shadow-card` | `0 6px 16px rgba(0,0,0,0.04)` | `0 6px 16px rgba(0,0,0,0.2)` | Elevación natural de cajas |
| `--shadow-glow` | `rgba(207, 242, 124, 0.45)` | `rgba(207, 242, 124, 0.25)` | Resplandor lima al hacer hover sobre tarjetas |
| `--table-gastos-thead` | `#fff1f2` (Rojo pastel) | `rgba(197, 3, 145, 0.25)` | Cabecera semántica de tabla de gastos |
| `--table-ingresos-thead` | `#f0fdf4` (Verde pastel) | `rgba(8, 71, 52, 0.25)` | Cabecera semántica de tabla de ingresos |

### Mecanismo de Activación y Cero Parpadeo (Anti-FOUC)
1. **Script Síncrono en el `<head>` (`header.php`, `login.php`, `registro.php`):**
   ```javascript
   (function() {
       const savedTheme = localStorage.getItem('theme');
       document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
   })();
   ```
   Se ejecuta antes de que el motor del navegador procese el renderizado del DOM, eliminando cualquier destello blanco si el usuario prefiere modo oscuro.
2. **Control en Perfil (`perfil.php` + `js/perfil.js`):**
   El switch deslizante `#btnModoOscuro` escucha el evento `change`:
   ```javascript
   const nuevoTema = btnOscuro.checked ? 'dark' : 'light';
   document.documentElement.setAttribute('data-theme', nuevoTema);
   localStorage.setItem('theme', nuevoTema);
   ```
3. **Resaltado de Iconos en Modo Oscuro:**
   En `themes.css`, todas las imágenes e iconos PNG (excepto el logo institucional) reciben automáticamente un filtro de resplandor sutil para maximizar visibilidad sobre fondos oscuros:
   ```css
   [data-theme="dark"] img:not([alt="Trackify Icon"]) {
       filter: drop-shadow(0px 0px 4px rgba(228, 228, 228, 0.76));
   }
   ```

---

## 5. Sistema de Componentes Reutilizables

### A. Tarjetas y Cajas (`css/components/cards.css`)
* Clases unificadas: `.card`, `.box`, `.tabla-box`, `.obj-card`, `.perfil-sidebar-card`, `.seccion-card`, `.cat-card`, `.modal-confirmacion`.
* **Efecto Glow Interactivo:** Al pasar el cursor (`:hover`), las tarjetas se elevan levemente (`transform: translateY(-2px)`) y proyectan una sombra suave combinada con resplandor lima:
  `box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05), 0 0 20px var(--shadow-glow);`

### B. Botones (`css/components/buttons.css`)
* `.btn`: Base con tipografía `Inter`, centrado flex y radio de 6px.
* `.btn.ingreso`: Verde lima (`background: var(--brand-green-light); color: var(--brand-green-dark);`).
* `.btn.gasto`: Bordó profundo (`background: var(--brand-purple); color: #ffffff;`).
* `.btn-nuevo-movimiento`: Botón de acción con efecto de brillo deslizante animado mediante pseudo-elemento `::after`.
* `.btn-peligro`: Botón con bordes y tipografía en rojo carmesí para acciones destructivas.
* `.btn-google`: Botón blanco con sombra para autenticación federada en `auth.css`.

### C. Formularios, Modales y Selects Personalizados (`css/components/forms.css`)
* **Modales:** `#modal` y modales de confirmación usan `.modal` (overlay en posición fija con `backdrop-filter: blur(4px)`) y contenedor `.modal-content`. La visibilidad se controla alternando la clase `.hidden` (`display: none !important`).
* **Custom Select Dropdown:** Sobrescribe el elemento nativo `<select>`.
  * Contenedor: `.custom-select-wrapper`
  * Botón visible: `.custom-select-trigger` (inyecta flecha SVG en Base64)
  * Menú desplegable: `.custom-select-options` (absoluto con `z-index: 1001`)
  * Elemento: `.custom-option` y `.custom-option.selected`

### D. Badges de Estado
* `.badge-activo`: Fondo `--badge-activo-bg` y texto `--badge-activo-text`.
* `.badge-pausado`: Fondo `--badge-pausado-bg` y texto `--badge-pausado-text`.
* `.badge-logrado`: Fondo `--badge-logrado-bg` y texto `--badge-logrado-text`.

---

## 6. Sistema de Color Dinámico en Categorías y Objetivos

Para evitar colores saturados y planos ("chillones"), Trackify implementa **7 familias de colores pastel armónicas** derivadas de la identidad visual:

### Clases de Tarjetas de Categorías (`css/pages/categorias.css`)
Cada categoría recibe una clase rotativa que define variables internas (`--card-bg`, `--card-border`, `--card-accent`, `--card-text`, `--card-btn-bg`):

1. `.card-mint`: Fondo menta suave (`#edf7f5`) con acento verde oscuro (`#084734`).
2. `.card-lime`: Fondo lima suave (`#f7f8ea`) con acento oliva oscuro (`#6d801b`).
3. `.card-pink`: Fondo rosa pastel (`#fcf0fa`) con acento bordó/rosa (`#700353`).
4. `.card-lavender`: Fondo lavanda apagado (`#ece8f4`) con acento violeta (`#5a3b75`).
5. `.card-grey`: Fondo gris verdoso neutro (`#f0f2f1`) con acento pizarra (`#334155`).
6. `.card-violet`: Fondo violeta pastel (`#f4edf3`) con acento púrpura (`#700353`).
7. `.card-teal`: Fondo petróleo/celeste apagado (`#eef7f6`) con acento petróleo (`#0b4c4e`).

* **Acento por Movimiento (`.con-monto`):** Si la categoría tiene saldo registrado (`$monto > 0`), se inyecta un borde inferior acentuado (`border-bottom: 3px solid var(--card-accent)`) y un halo suave.
* **Adaptación a Modo Oscuro:** `css/pages/categorias.css` redefine cada `.card-*` bajo `[data-theme="dark"]` usando fondos oscuros (`#1e1b26`, `#252131`) y textos claros para mantener legibilidad perfecta.

### Clases de Barras de Progreso en Objetivos (`css/pages/objetivos.css`)
Las barras de progreso consumen clases semánticas equivalentes:
* `.fill-mint`, `.fill-lime`, `.fill-pink`, `.fill-lavender`, `.fill-teal`, `.fill-violet`.
* **Estado Vencido (`.vencido`):** Aplica `opacity: 0.6` y filtro en escala de grises a la tarjeta, ocultando botones de acción salvo el de eliminación en rojo pastel.

---

## 7. Personalizaciones de Librerías Externas

### Flatpickr (Selector de Fechas)
Sobrescrito en `css/components/forms.css`:
* `.flatpickr-calendar`: Bordes redondeados (`12px`), sombra de elevación y adaptación a `--card-bg` y `--border-color`.
* Selección activa (`.flatpickr-day.selected`): Fondo en verde de marca (`--brand-green-dark`) con texto en lima (`--brand-green-light`).
* Sincronizado automáticamente al cambiar el modo claro/oscuro.

### SweetAlert2 (Ventanas de Confirmación)
Sobrescrito con tema Trackify:
* Fondo oscuro: `#2c2c3e`
* Botones de confirmación estilizados con `.swal-btn-danger` (rojo bordó) y `.swal-btn-primary` (verde corporativo).

---

## 8. Guía Práctica para Desarrolladores e IA: ¿Dónde Modifico X?

| Si deseas modificar... | Archivo exacto a editar | Elemento / Selector a tocar |
| :--- | :--- | :--- |
| **Colores de la marca corporativa** | `css/variables.css` | `:root { --brand-* }` |
| **Fondo general o color de texto claro/oscuro** | `css/themes.css` | `--bg-primary`, `--text-primary` en `[data-theme="..."]` |
| **Estilo o ancho de la barra lateral** | `css/components/sidebar.css` | `.sidebar`, `.sidebar a` |
| **Barra de navegación superior o logo** | `css/components/navbar.css` | `.navbar`, `.navbar .logo` |
| **Diseño del selector ARS/USD del header** | `moneda.css` | `.moneda-selector select` |
| **Efectos de tarjetas (sombra, elevación, glow)** | `css/components/cards.css` | `.card:hover`, `.box:hover` |
| **Inputs, selects o modales de formularios** | `css/components/forms.css` | `.input`, `.modal-content`, `.custom-select-*` |
| **Aspecto de los botones (Nuevo ingreso/gasto)** | `css/components/buttons.css` | `.btn`, `.btn.ingreso`, `.btn.gasto` |
| **Dashboard (KPIs, lista de últimos movimientos)** | `css/pages/dashboard.css` | `.metricas-dashboard`, `.movimiento-item` |
| **Tarjetas de categorías o sus 7 colores** | `css/pages/categorias.css` | `.cat-card`, `.card-mint`, `.card-lime`, etc. |
| **Metas de ahorro o barras de porcentaje** | `css/pages/objetivos.css` | `.obj-card`, `.progress-bar`, `.fill-*` |
| **Pantalla de Login, Registro o Verificación** | `css/pages/auth.css` | `.auth-card`, `.login-body`, `.glow-orb` |
| **Chat de Inteligencia Artificial** | `css/pages/ia.css` | `.ia-chat-shell`, `.chat-container`, `.mensaje` |
| **Perfil del usuario y zona de peligro** | `css/pages/perfil.css` | `.perfil-layout`, `.zona-peligro`, `.btn-peligro` |

---

## 9. Reglas Críticas para no Volver a un CSS Monolítico

1. **PROHIBIDO escribir estilos masivos en `styles.css`:** Este archivo debe contener exclusivamente `@import`. Todo estilo nuevo debe colocarse en su componente correspondiente o en su página respectiva.
2. **NUNCA usar colores hexadecimales "hardcodeados" dentro de los componentes:** Utiliza siempre variables (`var(--bg-card)`, `var(--text-primary)`, `var(--border-color)`). De lo contrario, romperás el modo oscuro.
3. **No contaminar con estilos inline en PHP:** No escribas `style="background: #123456;"` en bucles de PHP. Utiliza clases CSS declaradas (como `.card-mint` o `.fill-teal`).
4. **Si creas una página nueva:** Crea su correspondiente archivo dentro de `css/pages/nombre_pagina.css` y cárgalo en el archivo PHP mediante la variable `$extra_css` antes de invocar `includes/header.php`.
