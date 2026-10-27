<?php

function normalizarNombreCategoria($nombre) {
    $nombre = trim(mb_strtolower($nombre, 'UTF-8'));
    $nombre = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü'], ['a', 'e', 'i', 'o', 'u', 'u'], $nombre);
    return $nombre;
}

function obtenerMetaCategoriaPorNombre($nombre, $tipo) {
    $key = normalizarNombreCategoria($nombre);

    $iconos = [
        'alimentos' => 'iconos/generales/pizza.png',
        'comida' => 'iconos/generales/pizza.png',
        'transporte' => 'iconos/gasto/transporte.png',
        'vivienda' => 'iconos/gasto/vivienda.png',
        'salud' => 'iconos/gasto/salud.png',
        'educacion' => 'iconos/gasto/educacion.png',
        'entretenimiento' => 'iconos/gasto/entretenimiento.png',
        'ahorro' => 'iconos/gasto/ahorro.png',
        'impuestos' => 'iconos/gasto/impuesto.png',
        'regalos' => 'iconos/gasto/regalodonacion.png',
        'mascota' => 'iconos/gasto/mascotahuellagato.png',
        'mascotas' => 'iconos/gasto/mascotahuellagato.png',
        'suscripciones' => 'iconos/gasto/suscripcion.png',
        'compras personales' => 'iconos/gasto/compraspersonales.png',
        'deudas' => 'iconos/gasto/deuda.png',
        'tecnologia' => 'iconos/gasto/tecnologia1.png',
        'viajes' => 'iconos/gasto/viajemaleta.png',
        'otros gastos' => 'iconos/gasto/otrosgastos.png',
        'ropa' => 'iconos/gasto/ropa.png',
        'servicio' => 'iconos/gasto/servicio.png',
        'sueldo' => 'iconos/ingreso/sueldo.png',
        'prestamo' => 'iconos/ingreso/prestamo.png',
        'préstamo' => 'iconos/ingreso/prestamo.png',
        'reintegro' => 'iconos/ingreso/reembolso.png',
        'ventas' => 'iconos/ingreso/ventas.png',
        'inversiones' => 'iconos/ingreso/inversiones1.png',
        'intereses' => 'iconos/ingreso/intereses1.png',
        'regalo' => 'iconos/ingreso/regalo.png',
        'devoluciones' => 'iconos/ingreso/devoluciones.png',
        'freelance' => 'iconos/ingreso/freelancetrabajoextra.png',
        'trabajos extra' => 'iconos/ingreso/freelancetrabajoextra.png',
        'becas' => 'iconos/ingreso/becasubsidioayuda.png',
        'subsidios' => 'iconos/ingreso/becasubsidioayuda.png',
        'otros ingresos' => 'iconos/ingreso/otrosdinero.png',
    ];

    $colores = [
        'alimentos' => '#EA73F5',
        'comida' => '#EA73F5',
        'transporte' => '#084734',
        'vivienda' => '#700353',
        'salud' => '#084734',
        'educacion' => '#700353',
        'entretenimiento' => '#EA73F5',
        'ahorro' => '#CFF27C',
        'impuestos' => '#700353',
        'regalos' => '#CFF27C',
        'mascotas' => '#084734',
        'suscripciones' => '#700353',
        'compras personales' => '#EA73F5',
        'deudas' => '#700353',
        'tecnologia' => '#700353',
        'viajes' => '#084734',
        'otros gastos' => '#334155',
        'sueldo' => '#084734',
        'prestamo' => '#CFF27C',
        'préstamo' => '#CFF27C',
        'reintegro' => '#EA73F5',
        'ventas' => '#CFF27C',
        'inversiones' => '#084734',
        'intereses' => '#700353',
        'regalo' => '#CFF27C',
        'devoluciones' => '#EA73F5',
        'freelance' => '#700353',
        'trabajos extra' => '#700353',
        'becas' => '#084734',
        'subsidios' => '#084734',
        'otros ingresos' => '#334155',
    ];

    $icono = null;
    foreach ($iconos as $busqueda => $ruta) {
        if ($busqueda === $key || str_contains($key, $busqueda)) {
            $icono = $ruta;
            break;
        }
    }

    if (!$icono) {
        $icono = $tipo === 'ingreso'
            ? 'iconos/ingreso/otrosdinero.png'
            : 'iconos/gasto/otrosgastos.png';
    }

    $color = null;
    foreach ($colores as $busqueda => $hex) {
        if ($busqueda === $key || str_contains($key, $busqueda)) {
            $color = $hex;
            break;
        }
    }

    if (!$color) {
        $color = $tipo === 'ingreso' ? '#084734' : '#700353';
    }

    return [
        'icono' => $icono,
        'color' => $color,
        'alt' => ucfirst($nombre),
    ];
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
