<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canetto | Iniciar sesión</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="login.css">
</head>

<body>

<div class="login-container">

    <div class="logo">CANETTO</div>
    <div class="subtitle">Accedé a tu cuenta</div>

    <form action="../config/login_process.php" method="POST" id="loginForm">

        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="input-group">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn-login">Ingresar</button>

    </form>

    <div class="divider">
        <span>o</span>
    </div>

    <button class="btn-google" onclick="googleLogin()">
    <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
    Continuar con Google
</button>

    <div class="register-link">
        ¿No tenés cuenta? <a href="registro.php">Crear cuenta</a>
    </div>

</div>

<script>
function googleLogin() {
    alert("Acá conectamos Google OAuth después 🔥");
}
</script>

</body>
</html>
