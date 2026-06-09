<?php 
    session_start();
    session_destroy();
    $loginPath = dirname($_SERVER['PHP_SELF']);
    if ($loginPath === '/' || $loginPath === '\\' || $loginPath === '.') {
        $loginPath = '';
    }
    header("Location: {$loginPath}/login.php");

    exit ();
?>