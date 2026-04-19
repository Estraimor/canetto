<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$token   = trim($_POST['token']            ?? '');
$pass    = $_POST['password']              ?? '';
$confirm = $_POST['password_confirm']      ?? '';

if (!$token) { header('Location: recuperar_password.php'); exit; }

if (strlen($pass) < 6) {
    $_SESSION['reset_msg']  = 'La contraseña debe tener al menos 6 caracteres.';
    $_SESSION['reset_tipo'] = 'err';
    header('Location: reset_password.php?token=' . urlencode($token)); exit;
}

if ($pass !== $confirm) {
    $_SESSION['reset_msg']  = 'Las contraseñas no coinciden.';
    $_SESSION['reset_tipo'] = 'err';
    header('Location: reset_password.php?token=' . urlencode($token)); exit;
}

$pdo = Conexion::conectar();

$stmt = $pdo->prepare("
    SELECT id, usuario_id, expires_at, used FROM password_reset_tokens
    WHERE token = ? LIMIT 1
");
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row || $row['used'] || strtotime($row['expires_at']) <= time()) {
    $_SESSION['reset_msg']  = 'El enlace expiró o ya fue utilizado. Solicitá uno nuevo.';
    $_SESSION['reset_tipo'] = 'err';
    header('Location: recuperar_password.php'); exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);

// Actualizar contraseña y marcar token como usado
$pdo->prepare("UPDATE usuario SET password_hash = ?, updated_at = NOW() WHERE idusuario = ?")
    ->execute([$hash, $row['usuario_id']]);

$pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?")
    ->execute([$row['id']]);

$_SESSION['reset_msg']  = '✅ ¡Contraseña actualizada! Ya podés iniciar sesión con tu nueva contraseña.';
$_SESSION['reset_tipo'] = 'ok';

// Detectar a qué login redirigir según los roles del usuario
$rolesStmt = $pdo->prepare("
    SELECT r.nombre FROM roles r
    JOIN usuarios_roles ur ON ur.roles_idroles = r.idroles
    WHERE ur.usuario_idusuario = ?
");
$rolesStmt->execute([$row['usuario_id']]);
$rolesUsuario = $rolesStmt->fetchAll(PDO::FETCH_COLUMN);

if (in_array('administrador', $rolesUsuario, true) || in_array('admin', $rolesUsuario, true)) {
    header('Location: login.php'); exit;
} elseif (in_array('repartidor', $rolesUsuario, true)) {
    header('Location: ' . URL_REPARTIDOR . '/index.php'); exit;
} else {
    // Cliente de tienda
    header('Location: ' . URL_LOGIN . '/login_clientes.php'); exit;
}
