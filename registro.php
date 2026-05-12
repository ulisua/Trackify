<?php
session_start();
include 'conexion.php';

$mensaje = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Verificar si ya existe
    $check = $conn->prepare("SELECT id_usuario, nombre, email, clave FROM usuarios WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $res = $check->get_result();

    if($res->num_rows > 0){
        $mensaje = "El email ya está registrado";
    } else {
        $query = $conn->prepare("INSERT INTO usuarios (Nombre, email, clave) VALUES (?, ?, ?)");
        $query->bind_param("sss", $nombre, $email, $password);

        if($query->execute()){
            $_SESSION['usuario_id'] = $conn->insert_id;
            $_SESSION['usuario_nombre'] = $nombre;

            header("Location: index.php");
            exit();
        } else {
            $mensaje = "Error al registrar";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro - Trackify</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/auth.css">
</head>
<body>

<div class="glow-orb orb-1"></div>
<div class="glow-orb orb-2"></div>

<div class="auth-card">
    <div class="logo" style="text-align: center; margin-bottom: 25px;">
        <img src="logo.png" alt="Trackify Icon" style="height: 100px;">
    </div>
    <p class="subtitle">Crea tu cuenta para comenzar</p>

    <?php if($mensaje) echo "<div class='error-msg'>$mensaje</div>"; ?>

    <form method="POST">
        <div class="form-group">
            <input type="text" name="nombre" placeholder="Nombre completo" required>
        </div>
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <input type="password" name="password" placeholder="Contraseña" required>
        </div>
        <button type="submit" class="btn btn-primary">Registrarse</button>
    </form>

    <div class="footer-links">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>
</div>

</body>
</html>