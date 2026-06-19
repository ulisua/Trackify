# Guía quirúrgica y completa: organización actual de CSS, cómo cambiar propiedades y qué clases controlan

Este documento describe, archivo por archivo, selector por selector, la estructura CSS actual del proyecto en c:\Users\C3\Desktop\trackify, las reglas de prioridad, ejemplos concretos de cambios y el código JS que controla el toggle de tema. Sigue cada paso exactamente donde indico.

## Índice
- Resumen de la organización
- Archivo maestro: styles.css — tokens y estructura (edítalo aquí)
- Módulos por página: css/auth.css (login)
- Módulos por función: css/objetivos.css (objetivos y badges)
- Componentes globales y utilidades (qué archivo y qué controlan)
- Reglas de prioridad y eliminación de inconsistencias
- Mapa de clases: listado quirúrgico de cada clase importante y qué controla
- Cambios prácticos (paso a paso) — ejemplos para editar
- main.js — lógica de toggle, persistencia y dónde colocar el código
- Checklist de verificación después de cambios
- Buenas prácticas y recomendaciones
- Problemas comunes y soluciones rápidas
- Acciones inmediatas recomendadas

---

### Resumen de la organización
- Raíz CSS principal: `c:\Users\C3\Desktop\trackify\styles.css`
- Carpetas de módulos: `c:\Users\C3\Desktop\trackify\css\` (auth.css, objetivos.css, navbar.css, etc.)
- JS principal de UI: `c:\Users\C3\Desktop\trackify\js\main.js`
- Convención: `styles.css` contiene tokens (variables) y componentes base; archivos en `css/` contienen layout y adaptaciones de página que deben usar variables desde `styles.css`.

---

## 1) Archivo maestro: styles.css — tokens, estructura y dónde cambiar todo
**Ruta:** `c:\Users\C3\Desktop\trackify\styles.css`

Propósito: definir variables (tokens) para colores, radios, sombras y reglas base; contener utilidades y componentes base (`.btn`, `.card`, inputs). TODO cambio global (colores, contraste, radios) editar solo aquí.

Estructura recomendada:
1. Reset / box-sizing
2. `:root` — variables por defecto (tema claro)
3. `.theme-dark` — variables para modo oscuro (solo overrides)
4. Tipografía global
5. Layout global (body, containers)
6. Componentes base (`.btn`, `.card`, `.input`)
7. Utilities (flex, gap, visually-hidden)
8. Overrides / helpers

Variables centrales (edítalas aquí, NO en los módulos):
- `--bg-body`, `--bg-surface`, `--bg-card`, `--glass`, `--glass-strong`
- `--text-primary`, `--text-muted`, `--accent`
- `--success`, `--danger`, `--warning`
- `--border`, `--radius`, `--shadow`

Ejemplo (colocar en `styles.css`):

```css
:root{
  --bg-body: #f6f7fb;
  --bg-surface: #ffffff;
  --bg-card: #ffffff;
  --glass: rgba(255,255,255,0.06);
  --glass-strong: rgba(0,0,0,0.04);
  --text-primary: #1f2937;
  --text-muted: #6b7280;
  --accent: #10b981;
  --success: #16a34a;
  --danger: #ef4444;
  --warning: #f59e0b;
  --border: rgba(15,23,42,0.06);
  --radius: 12px;
  --shadow: 0 6px 24px rgba(2,6,23,0.08);

  --badge-purple: #6b21a8;
  --badge-purple-contrast: #d8b4fe;
}

.theme-dark{
  --bg-body: #0f0f12;
  --bg-surface: #121216;
  --bg-card: rgba(255,255,255,0.02);
  --glass: rgba(255,255,255,0.02);
  --glass-strong: rgba(255,255,255,0.03);
  --text-primary: #e6e6e9;
  --text-muted: #9aa0a6;
  --accent: #00d68f;
  --success: #1dd17d;
  --danger: #ff6b6b;
  --warning: #fbbf24;
  --border: rgba(255,255,255,0.06);
  --shadow: 0 6px 28px rgba(0,0,0,0.6);

  --badge-purple: #7c3aed;
  --badge-purple-contrast: #f3e8ff;
}
```

Qué editar para cambiar un aspecto global:
- Background de página: editar `--bg-body`
- Fondo de tarjetas: editar `--bg-card`
- Color principal / acciones: editar `--accent`
- Radios y sombras: `--radius` y `--shadow`
- Texto secundario: `--text-muted`

---

## 2) Módulo de autenticación: css/auth.css
**Ruta:** `c:\Users\C3\Desktop\trackify\css\auth.css`

Controla: layout de login/register (`.login-body`, `.login-card`, inputs, botones).

Reglas clave — reemplazar cualquier color hardcoded por variables:
- `.login-body { background: var(--bg-body); }`
- `.login-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); color: var(--text-primary); }`
- Inputs: `.input { background: transparent; color: var(--text-primary); border: 1px solid var(--border); }`

Consejo: para matiz exclusivo define variables nuevas en `styles.css` (ej. `--login-accent`).

Ejemplo (refactor recomendado para auth.css):

```css
.login-body{
  min-height:100vh;
  background: var(--bg-body);
  display:flex;
  align-items:center;
  justify-content:center;
  padding:2rem;
}
.login-card{
  width:420px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding:2rem;
  color: var(--text-primary);
}
.login-card .input{
  width:100%;
  padding:0.75rem 1rem;
  background: transparent;
  color: var(--text-primary);
  border:1px solid var(--border);
  border-radius:8px;
}
```

---

## 3) Módulo objetivos: css/objetivos.css
**Ruta:** `c:\Users\C3\Desktop\trackify\css\objetivos.css`

Controla: layout de objetivos, tarjetas (`.goal-card`), badges (`.badge`, `.badge--success`, etc.)

Clases importantes:
- `.goals-container`, `.goal-card`, `.badge`, `.badge--success`, `.badge--warning`, `.badge--purple`

Ejemplo recomendado:

```css
.goals-container{
  display:grid;
  grid-template-columns: repeat(auto-fill,minmax(280px,1fr));
  gap:1rem;
}
.goal-card{
  background: var(--bg-card);
  border:1px solid var(--border);
  border-radius: var(--radius);
  padding:1rem;
  box-shadow: var(--shadow);
  color: var(--text-primary);
}
.badge{
  display:inline-flex;
  align-items:center;
  gap:0.5rem;
  padding:0.35rem 0.65rem;
  border-radius:8px;
  background: var(--glass);
  color: var(--text-primary);
  border: 1px solid transparent;
  font-size:0.85rem;
}
.badge--success{
  color: var(--success);
  background: color-mix(in srgb, var(--success) 12%, transparent);
  border-color: color-mix(in srgb, var(--success) 18%, transparent);
}
.badge--warning{
  color: var(--warning);
  background: color-mix(in srgb, var(--warning) 10%, transparent);
  border-color: color-mix(in srgb, var(--warning) 18%, transparent);
}
.badge--purple{
  background: color-mix(in srgb, var(--badge-purple) 12%, transparent);
  color: var(--badge-purple-contrast);
  border: 1px solid color-mix(in srgb, var(--badge-purple) 18%, transparent);
}
```

---

## 4) Componentes globales y utilidades
Dónde editarlos: idealmente en `styles.css`. Si están en archivos separados, que sólo usen variables.

Lista y control:
- `.navbar` — background `var(--bg-surface)`, color `var(--text-primary)`, contenedor para `#theme-toggle`.
- `.btn`, `.btn--primary`, `.btn--ghost` — colores basados en `--accent` y `--border`.
- `.card` — `background: var(--bg-card)`, `border-radius: var(--radius)`, `box-shadow: var(--shadow)`.
- `.input` / `select` — `background: var(--glass)`, `color: var(--text-primary)`, `border: 1px solid var(--border)`.
- `.modal` / `.overlay` — overlay usa `rgba(...)` o variable `--overlay`; modal usa `var(--bg-surface)`.
- Utilities: `.flex`, `.items-center`, `.gap-*`, `.visually-hidden`, `.text-muted { color: var(--text-muted) }`.

---

## 5) Reglas de prioridad y eliminación de inconsistencias
- `styles.css` es la fuente de verdad.
- No usar hex directos en módulos; reemplazar por variables.
- Evitar `!important`.
- Variaciones locales deben ser variables en `styles.css` (ej. `--badge-purple`).

Proceso de limpieza: buscar en `c:\Users\C3\Desktop\trackify\css\` todos los hex y reemplazar por variables.

---

## 6) Mapa quirúrgico: clase → qué controla (resumen)
- `body` → background `--bg-body`, fuente y color base.
- `.theme-dark` → overrides de variables.
- `.navbar` → altura, fondo, color de links, contenedor de `#theme-toggle`.
- `#theme-toggle` → disparador del cambio de tema; aria-pressed true/false.
- `.card` → fondo, borde, radio, sombra.
- `.list-item` / `.movement-row` → filas de movimientos; `.amount` usa `--success`/`--danger`.
- `.badge` → chips; variantes `.badge--success` `.badge--warning` `.badge--purple`.
- `.input` `.select` → background, border, focus.
- `.modal` `.overlay` → overlay y modal container.
- `.helper-text` `.muted` → `--text-muted`.

---

## 7) Cambios prácticos paso a paso (ejemplos)
A) Cambiar fondo de tarjetas en modo oscuro:
- Editar `--bg-card` en `.theme-dark` dentro de `styles.css`.

B) Cambiar color de textos secundarios:
- Editar `--text-muted` en `:root` y `.theme-dark`.

C) Añadir badge morado:
- Definir `--badge-purple` y `--badge-purple-contrast` en `styles.css`, luego `.badge--purple` en `objetivos.css`.

---

## 8) main.js — toggle, persistencia y dónde colocarlo
**Ruta:** `c:\Users\C3\Desktop\trackify\js\main.js`

Responsabilidades:
- Aplicar tema al cargar.
- Insertar botón `#theme-toggle` si falta.
- Guardar preferencia en `localStorage.theme`.
- Exponer `applyTheme(mode)` y centralizar inicializaciones.

Bloque recomendado (pegar en `js/main.js` si no está):

```javascript
document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  const stored = localStorage.getItem('theme'); // 'dark' | 'light' | 'system' | null
  const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const initial = stored === 'system' ? (prefersDark ? 'dark' : 'light') : (stored || (prefersDark ? 'dark' : 'light'));

  function applyTheme(mode){
    const useDark = mode === 'dark' || (mode === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if(useDark) root.classList.add('theme-dark');
    else root.classList.remove('theme-dark');
    localStorage.setItem('theme', mode);
    const btn = document.querySelector('#theme-toggle');
    if(btn) btn.setAttribute('aria-pressed', useDark);
  }

  applyTheme(initial);

  let toggle = document.querySelector('#theme-toggle');
  if(!toggle){
    const nav = document.querySelector('.navbar') || document.body;
    toggle = document.createElement('button');
    toggle.id = 'theme-toggle';
    toggle.type = 'button';
    toggle.title = 'Cambiar tema';
    toggle.className = 'icon-btn';
    toggle.setAttribute('aria-pressed', root.classList.contains('theme-dark'));
    toggle.innerHTML = '<span class="visually-hidden">Tema</span>🌗';
    nav.prepend(toggle);
  }

  toggle.addEventListener('click', () => {
    const current = localStorage.getItem('theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    const next = current === 'dark' ? 'light' : 'dark';
    applyTheme(next);
  });

  if(window.matchMedia){
    const mql = window.matchMedia('(prefers-color-scheme: dark)');
    mql.addEventListener('change', () => {
      if(localStorage.getItem('theme') === 'system'){
        applyTheme('system');
      }
    });
  }

  // inicializaciones centralizadas (modales, selects, etc.) deben ir aquí.
});
```

---

## 9) Checklist de verificación después de cambios
- [ ] `styles.css` contiene variables en `:root` y `.theme-dark`.
- [ ] No hay hex directos en `css/`.
- [ ] `#theme-toggle` aparece y tiene `aria-pressed`.
- [ ] `localStorage.theme` guarda la preferencia.
- [ ] Inputs y selects tienen contraste adecuado.
- [ ] Badges y amounts usan variables semánticas.

> Nota: la verificación visual debe hacerse en navegador y por eso figura como paso manual.

---

## 10) Buenas prácticas
- Documentar variables en cabecera de `styles.css`.
- Evitar hex en módulos; si necesitas matiz, añadir variable.
- Evitar `!important`.
- Centralizar utilidades en `styles.css`.

---

## 11) Problemas comunes y soluciones rápidas
- Toggler no aparece → revisar que `main.js` se cargue después del DOM y que `.navbar` exista.
- Tarjeta blanca en oscuro → buscar `background: #fff` en CSS y reemplazar por `var(--bg-card)`.
- Variable no definida en `.theme-dark` → añadir override.

---

## 12) Acciones inmediatas recomendadas
1. Abrir `styles.css` y confirmar el bloque de variables; ajustar tonos.
2. Buscar hex en `css/` y reemplazar por variables.
3. Guardar/pegar el bloque `main.js` y recargar la app para verificar.
4. Ejecutar la checklist (verificación manual en navegador).

---

Fin del documento.  