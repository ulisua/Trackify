<?php
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nombre'] = 'Agus';
header('Location: index.php');
exit;
