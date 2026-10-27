<?php
$page = 'ingresos';
require_once 'conexion.php';
require_once 'includes/movimientos_handler.php';
require_once 'includes/header.php';

// Vista compartida con la página de gastos (ver includes/vista_movimientos_tipo.php)
$tipo_vista = 'ingreso';
require_once 'includes/vista_movimientos_tipo.php';

$extra_js = '<script src="js/filtros_movimientos.js"></script>';
require_once 'includes/footer.php';
