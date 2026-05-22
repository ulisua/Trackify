# Plan de Rediseño Visual de Tarjetas de Categorías - Trekify

Este plan detalla el rediseño estético y técnico de las tarjetas de categorías (gastos e ingresos) del proyecto Trekify. Mantendremos la identidad visual oscura existente de la barra lateral (sidebar) y de navegación (navbar), y aplicaremos un diseño de tarjetas limpio, moderno y de alta legibilidad, utilizando paletas de colores pastel/suaves derivadas de la paleta base del proyecto.

## Paleta de Colores Base y Derivaciones

La paleta base del proyecto es:
- `#1E1B26` (Negro/Violeta oscuro)
- `#084734` (Verde oscuro)
- `#CFF27C` (Verde lima claro)
- `#EA73F5` (Rosa vibrante)
- `#700353` (Bordó/Violeta oscuro)

Derivaremos los siguientes colores suaves y armoniosos (fondos claros) y acentos oscuros de contraste:

1.  **Mint (`card-mint`)**:
    *   Fondo: `#edf7f5` (Verde menta muy claro, suave)
    *   Borde: `#d2eae5` (Menta intermedio)
    *   Acento (Barra/Botones): `#084734` (Verde oscuro base)
    *   Texto principal: `#052e22` (Tono muy oscuro de verde menta para contraste)
2.  **Lime (`card-lime`)**:
    *   Fondo: `#f7f8ea` (Lima pastel suave)
    *   Borde: `#e2e6c5` (Lima intermedio)
    *   Acento (Barra/Botones): `#6d801b` (Verde oliva/lima oscuro)
    *   Texto principal: `#2d350b` (Marrón verdoso muy oscuro)
3.  **Pink (`card-pink`)**:
    *   Fondo: `#fcf0fa` (Rosa pastel muy claro)
    *   Borde: `#ebd0e7` (Rosa intermedio)
    *   Acento (Barra/Botones): `#EA73F5` / `#700353` (Rosa acento / Bordó)
    *   Texto principal: `#470235` (Bordó muy oscuro para excelente contraste)
4.  **Lavender (`card-lavender`)**:
    *   Fondo: `#ece8f4` (Lavanda claro apagado)
    *   Borde: `#dad2e8` (Lavanda intermedio)
    *   Acento (Barra/Botones): `#5a3b75` (Violeta lavanda oscuro)
    *   Texto principal: `#241730` (Violeta extremadamente oscuro)
5.  **Grey (`card-grey`)**:
    *   Fondo: `#f0f2f1` (Gris verdoso muy claro y neutro)
    *   Borde: `#d8dedb` (Gris intermedio)
    *   Acento (Barra/Botones): `#334155` (Pizarra/Gris oscuro)
    *   Texto principal: `#1e293b` (Slate muy oscuro)
6.  **Violet (`card-violet`)**:
    *   Fondo: `#f4edf3` (Violeta apagado claro)
    *   Borde: `#ebd8e9` (Violeta intermedio)
    *   Acento (Barra/Botones): `#700353` (Bordó oscuro de la paleta)
    *   Texto principal: `#3b022c` (Bordó/negro)
7.  **Teal (`card-teal`)**:
    *   Fondo: `#eef7f6` (Verde petróleo claro/Celeste apagado)
    *   Borde: `#cee5e2` (Teal intermedio)
    *   Acento (Barra/Botones): `#0b4c4e` (Petróleo oscuro)
    *   Texto principal: `#052425` (Petróleo casi negro)

---

## Cambios Propuestos

### Componente CSS: [css/categorias.css](file:///c:/Users/C3/Desktop/trackify/css/categorias.css)

Reemplazaremos los estilos planos de las tarjetas de categorías con un sistema basado en variables CSS dinámicas por clase de color. 

1.  **Definición de variables CSS por clase**:
    Crearemos clases `.card-mint`, `.card-lime`, `.card-pink`, `.card-lavender`, `.card-grey`, `.card-violet`, `.card-teal` que definan:
    *   `--card-bg`
    *   `--card-border`
    *   `--card-accent`
    *   `--card-text`
    *   `--card-text-muted`
    *   `--card-btn-bg`
    *   `--card-btn-color`
    *   `--card-btn-border`
    *   `--card-btn-hover-bg`
    *   `--card-btn-hover-color`
    *   `--card-glow`

2.  **Rediseño de `.cat-card`**:
    *   Usar las variables CSS para definir el fondo, bordes y textos.
    *   Agregar bordes redondeados más premium (`border-radius: 12px` en lugar de `10px`).
    *   Eliminar el pseudo-elemento lateral izquierdo (`.cat-card::before`) que creaba una línea vertical tosca de color sólido saturado. En su lugar, el color completo de la tarjeta y sus bordes suaves darán la personalidad.
    *   Rediseñar los botones de acción (`.cat-acciones button` y `.btn-del`) para usar las variables `--card-btn-*` que combinen armoniosamente con la tarjeta correspondiente.
    *   Agregar sombra moderna muy suave y elevación hover fluida usando transiciones de `transform` y `box-shadow`.

3.  **Acento visual para categorías con dinero (`.con-monto`)**:
    *   Si la categoría tiene saldo acumulado mayor a cero (`monto > 0`), se agregará la clase `.con-monto` a la tarjeta.
    *   Esta clase aplicará una línea inferior más marcada (`border-bottom: 3px solid var(--card-accent)`) y un brillo/glow muy sutil (`box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05), 0 0 14px var(--card-glow)`).

4.  **Alineación de la barra de distribución (`.resumen-row` y `.cat-barra`)**:
    *   Hacer que las barras de progreso del grid de tarjetas y de la sección de resumen utilicen las variables CSS.
    *   Remover los estilos de color de fondo en línea (`background: <?php echo ... ?>`) en favor de clases CSS.

---

### Componente Backend: [categorias.php](file:///c:/Users/C3/Desktop/trackify/categorias.php)

1.  **Cambiar el array `$colores` por `$color_classes`**:
    Reemplazar el array de códigos hex tradicionales por las nuevas clases semánticas:
    ```php
    $color_classes = ['card-mint', 'card-lime', 'card-pink', 'card-lavender', 'card-grey', 'card-violet', 'card-teal'];
    ```
2.  **Asignar la clase de color**:
    Asignar `$row['color_class']` de forma cíclica tanto para gastos como para ingresos.
3.  **Agregar clases al HTML**:
    *   En las tarjetas `.cat-card`, añadir la clase `<?php echo $gasto['color_class']; ?>` y condicionalmente la clase `con-monto` si `$monto > 0`.
    *   En las filas de distribución `.resumen-row`, añadir la clase `<?php echo $gasto['color_class']; ?>`.
4.  **Remover los estilos `background: ...` inline** de las barras de progreso, ya que ahora serán coloreadas automáticamente por la clase padre usando CSS.

---

## Plan de Verificación

### Verificación Manual
1.  Ingresar a la sección de **Categorías** en el navegador.
2.  Observar las tarjetas en la pestaña de **Gastos** e **Ingresos**. Verificar que:
    *   Los fondos sean pasteles suaves y los textos de excelente contraste y legibilidad.
    *   Los botones e iconos estén bien combinados con el color de la tarjeta.
    *   Las categorías con dinero registrado muestren el acento visual inferior y el glow suave.
    *   Al pasar el mouse (hover) por encima, la tarjeta se eleve sutilmente y las sombras cambien de forma suave.
    *   El diseño se mantenga responsive (comportamiento en grid en tablet y mobile).
3.  Verificar que la sección de "Distribución del mes" en la parte superior refleje los colores de las categorías usando las barras de progreso en sintonía con las tarjetas.
