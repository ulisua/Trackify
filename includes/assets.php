<?php
// includes/assets.php
// Recursos estáticos comunes a todas las pantallas (con y sin sesión):
//   - asset(): agrega ?v=<fecha de modificación> para que el navegador cachee los archivos
//     y descargue la versión nueva apenas cambian (ya no hace falta subir "?v=7" a mano).
//   - headComun(): favicons, preconexiones a los CDN, fuente Inter y las hojas de estilo base.

const CSS_BASE = [
    'css/variables.css',
    'css/themes.css',
    'css/base.css',
    'css/layout.css',
    'css/components/navbar.css',
    'css/components/sidebar.css',
    'css/components/cards.css',
    'css/components/forms.css',
    'css/components/buttons.css',
    'css/components/charts.css',
    'css/components/chat.css',
    'css/components/moneda.css',
];

function asset(string $ruta): string
{
    $archivo = dirname(__DIR__) . '/' . $ruta;
    $version = is_file($archivo) ? filemtime($archivo) : 0;
    return htmlspecialchars($ruta . ($version ? '?v=' . $version : ''), ENT_QUOTES);
}

function cssLink(string $ruta): string
{
    return '<link rel="stylesheet" href="' . asset($ruta) . '">' . "\n";
}

function jsScript(string $ruta): string
{
    return '<script src="' . asset($ruta) . '"></script>' . "\n";
}

// $conCdn: true en las páginas con sesión (usan Flatpickr, SweetAlert2 y Chart.js de jsDelivr)
function headComun(bool $conCdn = false): void
{ ?>
    <link rel="icon" href="<?= asset('favicon.ico') ?>" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('favicons/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('favicons/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('favicons/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= asset('favicons/site.webmanifest') ?>">
    <meta name="theme-color" content="#084734">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if ($conCdn): ?><link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin><?php endif; ?>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php
    // Hojas base enlazadas en paralelo (antes styles.css las traía con @import, una detrás de otra)
    foreach (CSS_BASE as $css) {
        echo '    ' . cssLink($css);
    }
}
