<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$email = trim($_POST['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reset_msg']  = 'Ingresá un email válido.';
    $_SESSION['reset_tipo'] = 'err';
    header('Location: recuperar_password.php'); exit;
}

$pdo = Conexion::conectar();

// Buscar usuario por email
$stmt = $pdo->prepare("SELECT idusuario, nombre, apellido, email FROM usuario WHERE email = ? AND activo = 1 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Por seguridad, siempre mostrar el mismo mensaje aunque el email no exista
if ($user) {
    // Eliminar tokens anteriores del usuario
    $pdo->prepare("DELETE FROM password_reset_tokens WHERE usuario_id = ?")->execute([$user['idusuario']]);

    $token     = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $pdo->prepare("INSERT INTO password_reset_tokens (usuario_id, token, expires_at) VALUES (?, ?, ?)")
        ->execute([$user['idusuario'], $token, $expiresAt]);

    $link   = SITE_URL . '/login/reset_password.php?token=' . $token;
    $nombre = htmlspecialchars($user['nombre'] . ' ' . ($user['apellido'] ?? ''));

    $contenido = <<<HTML
<h2 style="margin:0 0 8px;font-size:22px;color:#2d2d2d;font-weight:700;">
  Hola, {$nombre} 🍪
</h2>
<p style="margin:0 0 20px;color:#555;font-size:15px;line-height:1.7;">
  Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>Canetto Cookies</strong>.
  Si no realizaste esta solicitud, ignorá este email.
</p>
<div style="text-align:center;margin:28px 0;">
  <a href="{$link}"
     style="display:inline-block;background:linear-gradient(135deg,#c88e99,#a46678);
            color:#fff;text-decoration:none;font-weight:700;font-size:15px;
            padding:14px 36px;border-radius:50px;letter-spacing:.5px;
            box-shadow:0 4px 16px rgba(200,142,153,.45);">
    🔑 Restablecer contraseña
  </a>
</div>
<p style="margin:0;color:#999;font-size:13px;text-align:center;line-height:1.7;">
  Este enlace expira en <strong>1 hora</strong>.<br>
  Si el botón no funciona, copiá y pegá este enlace en tu navegador:<br>
  <a href="{$link}" style="color:#c88e99;word-break:break-all;">{$link}</a>
</p>
HTML;

    enviarEmail(
        $email,
        $user['nombre'],
        '🔑 Restablecer tu contraseña — Canetto Cookies',
        'Restablecer contraseña',
        $contenido
    );
}

$_SESSION['reset_msg']  = 'Si el email está registrado, recibirás un enlace en los próximos minutos. Revisá también la carpeta de spam.';
$_SESSION['reset_tipo'] = 'ok';
header('Location: recuperar_password.php'); exit;
