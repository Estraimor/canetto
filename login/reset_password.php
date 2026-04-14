<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$token = trim($_GET['token'] ?? '');

if (!$token) {
    header('Location: recuperar_password.php'); exit;
}

$pdo = Conexion::conectar();

// Verificar token
$stmt = $pdo->prepare("
    SELECT t.id, t.usuario_id, t.expires_at, t.used,
           u.nombre, u.apellido, u.email
    FROM password_reset_tokens t
    JOIN usuario u ON u.idusuario = t.usuario_id
    WHERE t.token = ? LIMIT 1
");
$stmt->execute([$token]);
$row = $stmt->fetch();

$tokenValido = $row && !$row['used'] && strtotime($row['expires_at']) > time();

$msg  = $_SESSION['reset_msg']  ?? null;
$tipo = $_SESSION['reset_tipo'] ?? 'err';
unset($_SESSION['reset_msg'], $_SESSION['reset_tipo']);

// Detectar URL de login según roles del usuario
$loginUrl = URL_LOGIN . '/login.php'; // default: admin
if ($row) {
    $rolesStmt = $pdo->prepare("
        SELECT r.nombre FROM roles r
        JOIN usuarios_roles ur ON ur.roles_idroles = r.idroles
        WHERE ur.usuario_idusuario = ?
    ");
    $rolesStmt->execute([$row['usuario_id']]);
    $rolesUsuario = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('repartidor', $rolesUsuario, true)) {
        $loginUrl = URL_REPARTIDOR . '/index.php';
    } elseif (!in_array('administrador', $rolesUsuario, true) && !in_array('admin', $rolesUsuario, true)) {
        $loginUrl = URL_LOGIN . '/login_clientes.php';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canetto | Nueva contraseña</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="login.css">
<style>
.lc-alert{padding:9px 13px;border-radius:8px;font-size:13px;margin-bottom:14px}
.lc-alert.err{background:#f9edf0;color:#c88e99}
.lc-alert.ok{background:#e8f5e9;color:#1d8348}
.back-link{display:block;text-align:center;font-size:13px;color:#888;margin-top:16px;text-decoration:none}
.back-link:hover{color:#c88e99}
.pass-toggle{position:relative}
.pass-toggle input{padding-right:42px}
.pass-toggle .toggle-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;color:#aaa;font-size:16px;padding:0}
</style>
</head>
<body>
<div class="login-container">

  <div class="logo">CANETTO</div>
  <div class="subtitle">Nueva contraseña</div>

  <?php if ($msg): ?>
    <div class="lc-alert <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <?php if (!$tokenValido): ?>
    <div class="lc-alert err">
      <?= $row ? 'El enlace ya fue usado o expiró.' : 'Enlace inválido.' ?>
      <br>Solicitá uno nuevo.
    </div>
    <a class="back-link" href="recuperar_password.php">← Solicitar nuevo enlace</a>

  <?php else: ?>

    <p style="font-size:14px;color:#666;margin-bottom:18px;">
      Hola <strong><?= htmlspecialchars($row['nombre']) ?></strong>,
      creá una nueva contraseña para tu cuenta.
    </p>

    <form action="reset_password_process.php" method="POST">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <div class="input-group">
        <label>Nueva contraseña <span style="font-size:11px;color:#aaa">(mín. 6 caracteres)</span></label>
        <div class="pass-toggle">
          <input type="password" name="password" id="pass1" required autocomplete="new-password" minlength="6">
          <button type="button" class="toggle-eye" onclick="togglePass('pass1',this)">👁</button>
        </div>
      </div>

      <div class="input-group">
        <label>Confirmar contraseña</label>
        <div class="pass-toggle">
          <input type="password" name="password_confirm" id="pass2" required autocomplete="new-password">
          <button type="button" class="toggle-eye" onclick="togglePass('pass2',this)">👁</button>
        </div>
      </div>

      <button type="submit" class="btn-login">Guardar nueva contraseña</button>
    </form>

    <a class="back-link" href="<?= htmlspecialchars($loginUrl) ?>">← Volver al inicio de sesión</a>

  <?php endif; ?>

</div>
<script>
function togglePass(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
</script>
</body>
</html>
