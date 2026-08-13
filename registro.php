<?php
session_start();
include 'conexion.php';

$mensaje = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre   = trim($_POST['nombre']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Verificar si ya existe
    $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();

    if($check->get_result()->num_rows > 0){
        $mensaje = "El email ya está registrado.";
    } else {
        // Generar código de 6 dígitos y expiración de 15 minutos
        $codigo     = strval(rand(100000, 999999));
        $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // UUID
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $query = $conn->prepare("INSERT INTO usuarios (nombre, email, clave, fecha_registro, uuid, email_verificado, codigo_verificacion, codigo_expiracion, Foto_perfil) VALUES (?, ?, ?, NOW(), ?, 0, ?, ?, '')");
        $query->bind_param("ssssss", $nombre, $email, $password, $uuid, $codigo, $expiracion);

        if($query->execute()){
            $nuevo_id = $conn->insert_id;

            // Enviar email con PHPMailer
            require_once 'phpmailer/PHPMailer.php';
            require_once 'phpmailer/SMTP.php';
            require_once 'phpmailer/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'sandbox.smtp.mailtrap.io';
                $mail->SMTPAuth   = true;
                $mail->Username   = '6e724e2630cb1b';
                $mail->Password   = 'b4ad1521076737';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 2525;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('noreply@trackify.com', 'Trackify');
                $mail->addAddress($email, $nombre);
                $mail->Subject = 'Verificá tu cuenta de Trackify';
                $mail->isHTML(true);
                $mail->Body = "
                    <div style='font-family:Inter,sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f8fafc;border-radius:12px;'>
                        <img src='http://localhost/trackify/logo.png' alt='Trackify' style='height:60px;margin-bottom:24px;'>
                        <h2 style='color:#1E1B26;margin:0 0 8px;'>Verificá tu email</h2>
                        <p style='color:#64748b;margin:0 0 24px;'>Usá el siguiente código para completar tu registro. Vence en 15 minutos.</p>
                        <div style='background:#084734;color:#CFF27C;font-size:2rem;font-weight:700;letter-spacing:12px;text-align:center;padding:20px;border-radius:8px;margin-bottom:24px;'>
                            $codigo
                        </div>
                        <p style='color:#94a3b8;font-size:.85rem;margin:0;'>Si no creaste una cuenta en Trackify, ignorá este email.</p>
                    </div>
                ";

                $mail->send();

                // Guardar en sesión para la página de verificación
                $_SESSION['verificar_id']     = $nuevo_id;
                $_SESSION['verificar_email']  = $email;
                $_SESSION['verificar_nombre'] = $nombre;

                header("Location: verificar_email.php");
                exit();

            } catch (Exception $e) {
                $mensaje = "Error al enviar el email: " . $mail->ErrorInfo;
            }
        } else {
            $mensaje = "Error al registrar. Intentá de nuevo.";
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

    <?php if($mensaje) echo "<div class='error-msg'>".htmlspecialchars($mensaje)."</div>"; ?>

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
