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
<title>Registro - Trackify</title>
<style>
body { font-family: Arial; padding:50px; }
input { display:block; margin:10px 0; padding:10px; width:300px; }
button { padding:10px; width:320px; }
</style>
</head>
<body>

<h1>Crear cuenta</h1>

<?php if($mensaje) echo "<p style='color:red;'>$mensaje</p>"; ?>

<form method="POST">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Contraseña" required>
    <button type="submit">Registrarse</button>
</form>

<a href="login.php">Ya tengo cuenta</a>

</body>
</html>