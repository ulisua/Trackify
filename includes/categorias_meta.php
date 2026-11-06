<?php

function normalizarNombreCategoria($nombre) {
    $nombre = trim(mb_strtolower($nombre, 'UTF-8'));
    return str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü'], ['a', 'e', 'i', 'o', 'u', 'u'], $nombre);
}

// Ícono y color sugeridos según el nombre. Se busca primero entre las categorías del
// mismo tipo (así "Regalos" de ingreso no toma el ícono de "Regalos / donaciones").
function obtenerMetaCategoriaPorNombre($nombre, $tipo) {
    static $mapa = [
        'gasto' => [
            'alimentos'          => ['iconos/generales/pizza.png', '#EA73F5'],
            'comida'             => ['iconos/generales/pizza.png', '#EA73F5'],
            'transporte'         => ['iconos/gasto/transporte.png', '#084734'],
            'vivienda'           => ['iconos/gasto/vivienda.png', '#700353'],
            'salud'              => ['iconos/gasto/salud.png', '#084734'],
            'educacion'          => ['iconos/gasto/educacion.png', '#700353'],
            'entretenimiento'    => ['iconos/gasto/entretenimiento.png', '#EA73F5'],
            'ahorro'             => ['iconos/gasto/ahorro.png', '#CFF27C'],
            'impuesto'           => ['iconos/gasto/impuesto.png', '#700353'],
            'regalo'             => ['iconos/gasto/regalodonacion.png', '#CFF27C'],
            'mascota'            => ['iconos/gasto/mascotahuellagato.png', '#084734'],
            'suscripcion'        => ['iconos/gasto/suscripcion.png', '#700353'],
            'compras personales' => ['iconos/gasto/compraspersonales.png', '#EA73F5'],
            'deuda'              => ['iconos/gasto/deuda.png', '#700353'],
            'tecnologia'         => ['iconos/gasto/tecnologia1.png', '#700353'],
            'viaje'              => ['iconos/gasto/viajemaleta.png', '#084734'],
            'otros gastos'       => ['iconos/gasto/otrosgastos.png', '#334155'],
            'ropa'               => ['iconos/gasto/ropa.png', '#700353'],
            'servicio'           => ['iconos/gasto/servicio.png', '#700353'],
        ],
        'ingreso' => [
            'sueldo'         => ['iconos/ingreso/sueldo.png', '#084734'],
            'prestamo'       => ['iconos/ingreso/prestamo.png', '#CFF27C'],
            'reintegro'      => ['iconos/ingreso/reembolso.png', '#EA73F5'],
            'ventas'         => ['iconos/ingreso/ventas.png', '#CFF27C'],
            'inversiones'    => ['iconos/ingreso/inversiones1.png', '#084734'],
            'intereses'      => ['iconos/ingreso/intereses1.png', '#700353'],
            'regalo'         => ['iconos/ingreso/regalo.png', '#CFF27C'],
            'devoluciones'   => ['iconos/ingreso/devoluciones.png', '#EA73F5'],
            'freelance'      => ['iconos/ingreso/freelancetrabajoextra.png', '#700353'],
            'trabajos extra' => ['iconos/ingreso/freelancetrabajoextra.png', '#700353'],
            'beca'           => ['iconos/ingreso/becasubsidioayuda.png', '#084734'],
            'subsidio'       => ['iconos/ingreso/becasubsidioayuda.png', '#084734'],
            'otros ingresos' => ['iconos/ingreso/otrosdinero.png', '#334155'],
        ],
    ];

    $key = normalizarNombreCategoria($nombre);
    $otro = $tipo === 'ingreso' ? 'gasto' : 'ingreso';
    foreach ([$mapa[$tipo] ?? [], $mapa[$otro]] as $grupo) {
        foreach ($grupo as $busqueda => [$icono, $color]) {
            if (str_contains($key, $busqueda)) {
                return ['icono' => $icono, 'color' => $color, 'alt' => ucfirst($nombre)];
            }
        }
    }

    return $tipo === 'ingreso'
        ? ['icono' => 'iconos/ingreso/otrosdinero.png', 'color' => '#084734', 'alt' => ucfirst($nombre)]
        : ['icono' => 'iconos/gasto/otrosgastos.png', 'color' => '#700353', 'alt' => ucfirst($nombre)];
}

function actualizarCategoriaMetaSiFalta($conn, &$categoria) {
    if (empty($categoria['icono']) || empty($categoria['color'])) {
        $meta = obtenerMetaCategoriaPorNombre($categoria['nombre'], $categoria['tipo']);
        $categoria['icono'] = $categoria['icono'] ?: $meta['icono'];
        $categoria['color'] = $categoria['color'] ?: $meta['color'];

        $stmt = $conn->prepare("UPDATE categorias SET icono = ?, color = ? WHERE id_categoria = ?");
        if ($stmt) {
            $stmt->bind_param('ssi', $categoria['icono'], $categoria['color'], $categoria['id_categoria']);
            $stmt->execute();
        }
    }
}

// ── Visibilidad de categorías ────────────────────────────────────────────────
// Cada usuario ve las categorías predeterminadas (id_usuario IS NULL) y las propias.

function obtenerCategoriasVisibles($conn, $user_id) {
    $categorias = ['ingreso' => [], 'gasto' => []];
    $stmt = $conn->prepare("SELECT id_categoria, nombre, tipo, icono, color, id_usuario FROM categorias WHERE id_usuario IS NULL OR id_usuario = ? ORDER BY nombre ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (isset($categorias[$row['tipo']])) {
            $categorias[$row['tipo']][] = $row;
        }
    }
    return $categorias;
}

// Devuelve el id de una categoría visible para el usuario, o null si no existe.
function buscarCategoriaVisible($conn, $user_id, $nombre, $tipo) {
    $stmt = $conn->prepare("SELECT id_categoria FROM categorias WHERE nombre = ? AND tipo = ? AND (id_usuario IS NULL OR id_usuario = ?) ORDER BY id_usuario IS NULL DESC LIMIT 1");
    $stmt->bind_param("ssi", $nombre, $tipo, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (int)$row['id_categoria'] : null;
}

// Obtiene (o crea) una categoría predeterminada del sistema. Uso interno de la app.
function obtenerCategoriaSistema($conn, $nombre, $tipo) {
    $stmt = $conn->prepare("SELECT id_categoria FROM categorias WHERE nombre = ? AND tipo = ? AND id_usuario IS NULL LIMIT 1");
    $stmt->bind_param("ss", $nombre, $tipo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return (int)$row['id_categoria'];
    }
    $meta = obtenerMetaCategoriaPorNombre($nombre, $tipo);
    $stmt_ins = $conn->prepare("INSERT INTO categorias (nombre, tipo, icono, color) VALUES (?, ?, ?, ?)");
    $stmt_ins->bind_param("ssss", $nombre, $tipo, $meta['icono'], $meta['color']);
    $stmt_ins->execute();
    return (int)$stmt_ins->insert_id;
}

function iconoCategoriaValido($ruta) {
    return array_key_exists($ruta, obtenerOpcionesIconoCategoria());
}

function colorCategoriaValido($color) {
    return is_string($color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1;
}

function obtenerOpcionesIconoCategoria() {
    return [
        'iconos/generales/pizza.png' => 'Alimentos',
        'iconos/gasto/transporte.png' => 'Transporte',
        'iconos/gasto/vivienda.png' => 'Vivienda',
        'iconos/gasto/salud.png' => 'Salud',
        'iconos/gasto/educacion.png' => 'Educación',
        'iconos/gasto/entretenimiento.png' => 'Entretenimiento',
        'iconos/gasto/ahorro.png' => 'Ahorro',
        'iconos/gasto/impuesto.png' => 'Impuestos',
        'iconos/gasto/regalodonacion.png' => 'Regalos',
        'iconos/gasto/mascotahuellagato.png' => 'Mascotas',
        'iconos/gasto/suscripcion.png' => 'Suscripciones',
        'iconos/gasto/compraspersonales.png' => 'Compras',
        'iconos/gasto/deuda.png' => 'Deudas',
        'iconos/gasto/tecnologia1.png' => 'Tecnología',
        'iconos/gasto/viajemaleta.png' => 'Viajes',
        'iconos/gasto/ropa.png' => 'Ropa',
        'iconos/gasto/servicio.png' => 'Servicios',
        'iconos/gasto/otrosgastos.png' => 'Otros gastos',
        'iconos/ingreso/sueldo.png' => 'Sueldo',
        'iconos/ingreso/prestamo.png' => 'Préstamo',
        'iconos/ingreso/reembolso.png' => 'Reintegro',
        'iconos/ingreso/ventas.png' => 'Ventas',
        'iconos/ingreso/inversiones1.png' => 'Inversiones',
        'iconos/ingreso/intereses1.png' => 'Intereses',
        'iconos/ingreso/regalo.png' => 'Regalos',
        'iconos/ingreso/devoluciones.png' => 'Devoluciones',
        'iconos/ingreso/freelancetrabajoextra.png' => 'Freelance',
        'iconos/ingreso/becasubsidioayuda.png' => 'Becas / subsidios',
        'iconos/ingreso/otrosdinero.png' => 'Otros ingresos',
    ];
}
