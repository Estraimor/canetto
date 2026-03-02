<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canetto | Iniciar sesión</title>

<!-- Fuente Inter -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="login.css">
</head>

<body>

<div class="login-container">

    <div class="logo">CANETTO</div>
    <div class="subtitle">Accedé a tu cuenta</div>

    <!-- Mensaje de error -->
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="error-msg">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="login_process.php" method="POST">

        <div class="input-group">
            <label>Usuario</label>
            <input type="text" name="usuario" required>
        </div>

        <div class="input-group">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn-login">
            Ingresar
        </button>

    </form>

    <!-- Divider -->
    <div class="divider">
        <span>o continuar con</span>
    </div>

    <!-- Google visual (sin conectar) -->
    <button class="btn-google">
        <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
        Ingresar con Google
    </button>

    <!-- Registro -->
    <div class="register-link">
        ¿No tenés cuenta?
        <a href="#">Registrate</a>
    </div>

</div>

</body>
</html>