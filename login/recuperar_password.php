<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$msg  = $_SESSION['reset_msg']  ?? null;
$tipo = $_SESSION['reset_tipo'] ?? 'err';
unset($_SESSION['reset_msg'], $_SESSION['reset_tipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canetto | Recuperar contraseña</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="login.css">
<style>
.lc-alert{padding:9px 13px;border-radius:8px;font-size:13px;margin-bottom:14px}
.lc-alert.err{background:#f9edf0;color:#c88e99}
.lc-alert.ok{background:#e8f5e9;color:#1d8348}
.back-link{display:block;text-align:center;font-size:13px;color:#888;margin-top:16px;text-decoration:none}
.back-link:hover{color:#c88e99}
.info-box{background:#faf3f5;border:1px solid #f0dce1;border-radius:10px;padding:14px 16px;
          font-size:13px;color:#7a5260;margin-bottom:18px;line-height:1.6}
</style>
</head>
<body>
<div class="login-container">

  <div class="logo">CANETTO</div>
  <div class="subtitle">Recuperar contraseña</div>

  <?php if ($msg): ?>
    <div class="lc-alert <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="info-box">
    Ingresá tu <strong>email</strong> registrado y te enviaremos un enlace para crear una nueva contraseña.
  </div>

  <form action="recuperar_password_process.php" method="POST">
    <div class="input-group">
      <label>Email</label>
      <input type="email" name="email" required placeholder="tu@email.com" autocomplete="email">
    </div>
    <button type="submit" class="btn-login">Enviar enlace de recuperación</button>
  </form>

  <a class="back-link" href="login.php">← Volver al inicio de sesión</a>
</div>
</body>
</html>
