<?php
$page = 'gastos';
require_once 'conexion.php';
require_once 'includes/movimientos_handler.php';
require_once 'includes/header.php';

// Vista compartida con la página de ingresos (ver includes/vista_movimientos_tipo.php)
$tipo_vista = 'gasto';
require_once 'includes/vista_movimientos_tipo.php';

$extra_js = '<script src="js/filtros_movimientos.js"></script>';
require_once 'includes/footer.php';
