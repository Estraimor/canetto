<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php'; // configura cookie_domain antes de session_start
if (session_status() === PHP_SESSION_NONE) session_start();

$usuario  = trim($_POST['usuario']  ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    $_SESSION['error_cliente'] = 'Completá todos los campos.';
    header('Location: login_clientes.php'); exit;
}

$pdo = Conexion::conectar();

// Buscar por celular primero (forma natural para clientes), luego por campo usuario
$stmt = $pdo->prepare("SELECT * FROM usuario WHERE celular = ? LIMIT 1");
$stmt->execute([$usuario]);
$user = $stmt->fetch();

if (!$user) {
    $stmt2 = $pdo->prepare("SELECT * FROM usuario WHERE usuario = ? LIMIT 1");
    $stmt2->execute([$usuario]);
    $user = $stmt2->fetch();
}

if (!$user) {
    $_SESSION['error_cliente'] = 'Celular o usuario no encontrado.';
    header('Location: login_clientes.php'); exit;
}

if ((int)$user['activo'] !== 1) {
    $_SESSION['error_cliente'] = 'Usuario inactivo.';
    header('Location: login_clientes.php'); exit;
}

// Verificar contraseña
$hash     = $user['password_hash'];
$esValida = str_starts_with($hash, '$2y$')
    ? password_verify($password, $hash)
    : ($password === $hash);

if (!$esValida) {
    $_SESSION['error_cliente'] = 'Contraseña incorrecta.';
    header('Location: login_clientes.php'); exit;
}

// Verificar que tenga rol cliente entre TODOS sus roles
$rolStmt = $pdo->prepare("
    SELECT r.idroles, r.nombre
    FROM usuarios_roles ur
    JOIN roles r ON r.idroles = ur.roles_idroles
    WHERE ur.usuario_idusuario = ?
");
$rolStmt->execute([$user['idusuario']]);
$roles = $rolStmt->fetchAll(PDO::FETCH_ASSOC);

$rolesNombres = array_map(fn($r) => strtolower($r['nombre']), $roles);
$rolCliente   = null;
foreach ($roles as $r) {
    if (strtolower($r['nombre']) === 'cliente') { $rolCliente = $r; break; }
}

if (!$rolCliente) {
    $_SESSION['error_cliente'] = 'Esta área es solo para clientes.';
    header('Location: login_clientes.php'); exit;
}

$rol = $rolCliente;

session_regenerate_id(true);

$_SESSION['usuario_id']              = $user['idusuario'];
$_SESSION['nombre']                  = $user['nombre'];
$_SESSION['apellido']                = $user['apellido'];
$_SESSION['rol']                     = 'cliente';
$_SESSION['rol_id']                  = $rol['idroles'] ?? null;
$_SESSION['tienda_cliente_id']       = $user['idusuario'];
$_SESSION['tienda_cliente_nombre']   = trim($user['nombre'] . ' ' . ($user['apellido'] ?? ''));

redirect(URL_TIENDA . '/index.php');
