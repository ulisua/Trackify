<?php
session_start();
include 'conexion.php'; // lo hace tu compañero

// Si ya está logueado, ir al dashboard
if(isset($_SESSION['usuario_id'])){
    header("Location: index.php");
    exit();
}

$mensaje = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = $conn->prepare("SELECT * FROM usuarios WHERE email=?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    if($result->num_rows === 1){
        $user = $result->fetch_assoc();

        if(password_verify($password, $user['password'])){
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];

            header("Location: index.php");
            exit();
        } else {
            $mensaje = "Contraseña incorrecta";
        }
    } else {
        $mensaje = "Usuario no encontrado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login - Trackify</title>
<style>
body { font-family: Arial; display:flex; height:100vh; margin:0; }
.left { width:50%; padding:50px; }
.right { width:50%; background:#f2f2f2; display:flex; align-items:center; justify-content:center; }
button { padding:10px; margin-top:10px; width:100%; }
input { display:block; margin-top:10px; padding:10px; width:100%; }
</style>
</head>
<body>

<div class="left">
    <h1>Bienvenido a Trackify</h1>
    <p>Controla tus finanzas fácilmente</p>

    <button>Continuar con Google</button>
    <p style="text-align:center;">O</p>

    <?php if($mensaje) echo "<p style='color:red;'>$mensaje</p>"; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Iniciar sesión</button>
    </form>

    <a href="registro.php">
        <button>¿Eres un usuario nuevo?</button>
    </a>
</div>

<div class="right">
    <h2>💰 Trackify</h2>
</div>

</body>
</html>