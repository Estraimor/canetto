<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$token  = trim($_GET['token'] ?? '');
$ok     = false;
$error  = '';
$nombre = '';

if (!$token) {
    $error = 'Token inválido o faltante.';
} else {
    try {
        $pdo = Conexion::conectar();

        $stmt = $pdo->prepare("
            SELECT id, usuario_idusuario, datos_nuevos, expira, usado
            FROM verificacion_token
            WHERE token = ? AND tipo = 'merge_dni'
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            $error = 'El enlace no es válido.';
        } elseif ($row['usado']) {
            $error = 'Este enlace ya fue utilizado.';
        } elseif (new DateTime() > new DateTime($row['expira'])) {
            $error = 'El enlace ha vencido. Intentá registrarte nuevamente.';
        } else {
            $datos = json_decode($row['datos_nuevos'], true);
            $uid   = (int)$row['usuario_idusuario'];

            // Actualizar el usuario con los nuevos datos
            $pdo->prepare("
                UPDATE usuario
                SET nombre=?, apellido=?, celular=?, password_hash=?, updated_at=NOW()
                WHERE idusuario=?
            ")->execute([
                $datos['nombre'],
                $datos['apellido'] ?: null,
                $datos['celular'],
                $datos['password'],
                $uid,
            ]);

            // Marcar token como usado
            $pdo->prepare("UPDATE verificacion_token SET usado=1 WHERE id=?")->execute([$row['id']]);

            // Iniciar sesión automáticamente
            $u = $pdo->prepare("SELECT nombre, apellido FROM usuario WHERE idusuario=?");
            $u->execute([$uid]);
            $usr = $u->fetch();
            $_SESSION['tienda_cliente_id']     = $uid;
            $_SESSION['tienda_cliente_nombre'] = trim(($usr['nombre'] ?? '') . ' ' . ($usr['apellido'] ?? ''));
            $nombre = $_SESSION['tienda_cliente_nombre'];
            $ok = true;
        }
    } catch (Throwable $e) {
        $error = 'Error interno. Por favor intentá más tarde.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificación de cuenta — Canetto</title>
<link rel="stylesheet" href="tienda.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f4f8;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
.vf-card{background:white;border-radius:20px;padding:40px 36px;max-width:440px;width:90%;text-align:center;box-shadow:0 12px 40px rgba(0,0,0,.1)}
.vf-icon{font-size:56px;margin-bottom:16px}
.vf-title{font-size:22px;font-weight:800;color:#1e293b;margin-bottom:8px}
.vf-sub{font-size:15px;color:#64748b;margin-bottom:28px;line-height:1.6}
.vf-btn{display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border-radius:12px;font-weight:700;font-size:15px;text-decoration:none;transition:transform .18s,box-shadow .18s}
.vf-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(59,130,246,.4)}
.vf-btn-err{background:linear-gradient(135deg,#ef4444,#dc2626)}
</style>
</head>
<body>
<div class="vf-card">
  <?php if ($ok): ?>
    <div class="vf-icon">🎉</div>
    <div class="vf-title">¡Cuenta vinculada!</div>
    <div class="vf-sub">
      Tu cuenta fue actualizada correctamente, <strong><?= htmlspecialchars($nombre) ?></strong>.<br>
      Ya podés hacer pedidos con tu nueva sesión.
    </div>
    <a href="<?= URL_TIENDA ?>/index.php" class="vf-btn">Ir a la tienda →</a>
  <?php else: ?>
    <div class="vf-icon">❌</div>
    <div class="vf-title">Enlace inválido</div>
    <div class="vf-sub"><?= htmlspecialchars($error) ?></div>
    <a href="<?= URL_TIENDA ?>/index.php" class="vf-btn vf-btn-err">Volver a la tienda</a>
  <?php endif; ?>
</div>
</body>
</html>
