<?php
declare(strict_types=1);
define('APP_BOOT', true);
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/mailer.php';
header('Content-Type: application/json; charset=utf-8');

$nombre   = trim($_POST['nombre']   ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$celular  = trim($_POST['celular']  ?? '');
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';

if (!$nombre || !$celular || !$password) {
    echo json_encode(['ok' => false, 'msg' => 'Completá los campos obligatorios.']); exit;
}
if (strlen($password) < 6) {
    echo json_encode(['ok' => false, 'msg' => 'La contraseña debe tener al menos 6 caracteres.']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Verificar que el celular no exista
    $chk = $pdo->prepare("SELECT idusuario FROM usuario WHERE celular = ? LIMIT 1");
    $chk->execute([$celular]);
    if ($chk->fetch()) {
        echo json_encode(['ok' => false, 'msg' => 'Ese número ya tiene una cuenta. Iniciá sesión.']); exit;
    }

    // Obtener idroles de "Cliente"
    $rolStmt = $pdo->prepare("SELECT idroles FROM roles WHERE LOWER(nombre) = 'cliente' LIMIT 1");
    $rolStmt->execute();
    $rolCliente = $rolStmt->fetchColumn();
    if (!$rolCliente) {
        echo json_encode(['ok' => false, 'msg' => 'Rol Cliente no encontrado. Contactá al administrador.']); exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Crear usuario (usamos celular como campo "usuario" para que pueda iniciar sesión)
    $ins = $pdo->prepare("
        INSERT INTO usuario (nombre, apellido, celular, usuario, email, password_hash, activo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $ins->execute([$nombre, $apellido ?: null, $celular, $celular, $email ?: null, $hash]);
    $newId = (int)$pdo->lastInsertId();

    // Asignar rol Cliente
    $pdo->prepare("INSERT INTO usuarios_roles (usuario_idusuario, roles_idroles) VALUES (?, ?)")
        ->execute([$newId, $rolCliente]);

    // Email de bienvenida (solo si el cliente ingresó email)
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $nombreDisplay = htmlspecialchars(trim("$nombre $apellido"));
        $primerNombre  = htmlspecialchars($nombre);
        $urlTienda = URL_TIENDA;
        $contenido = <<<HTML
<h2 style="margin:0 0 8px;font-size:22px;color:#2d2d2d;font-weight:700;">
  ¡Bienvenida/o, {$primerNombre}! 🍪
</h2>
<p style="margin:0 0 18px;color:#555;font-size:15px;line-height:1.7;">
  Tu cuenta en <strong>Canetto Cookies</strong> fue creada exitosamente.<br>
  Ya podés explorar nuestros productos y hacer tus pedidos.
</p>
<div style="background:#faf3f5;border-radius:12px;padding:18px 20px;margin-bottom:20px;">
  <p style="margin:0 0 6px;font-size:13px;color:#999;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Tu acceso</p>
  <p style="margin:0;color:#555;font-size:14px;">📱 <strong>Celular:</strong> {$celular}</p>
</div>
<div style="text-align:center;margin:24px 0;">
  <a href="{$urlTienda}/index.php"
     style="display:inline-block;background:linear-gradient(135deg,#c88e99,#a46678);
            color:#fff;text-decoration:none;font-weight:700;font-size:15px;
            padding:14px 36px;border-radius:50px;letter-spacing:.5px;
            box-shadow:0 4px 16px rgba(200,142,153,.45);">
    🛍️ Ver productos
  </a>
</div>
<p style="margin:0;color:#999;font-size:13px;text-align:center;">
  ¿Tenés algún problema? Escribinos a
  <a href="mailto:soporte@canettocookies.com" style="color:#c88e99;">soporte@canettocookies.com</a>
</p>
HTML;
        enviarEmail($email, $nombre, '🍪 ¡Bienvenida/o a Canetto Cookies!', 'Bienvenida/o a Canetto', $contenido);
    }

    // Iniciar sesión directamente
    session_regenerate_id(true);
    $_SESSION['usuario_id']            = $newId;
    $_SESSION['nombre']                = $nombre;
    $_SESSION['apellido']              = $apellido;
    $_SESSION['rol']                   = 'cliente';
    $_SESSION['rol_id']                = $rolCliente;
    $_SESSION['tienda_cliente_id']     = $newId;
    $_SESSION['tienda_cliente_nombre'] = trim("$nombre $apellido");

    echo json_encode(['ok' => true, 'nombre' => $nombre]);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error al crear la cuenta: ' . $e->getMessage()]);
}
