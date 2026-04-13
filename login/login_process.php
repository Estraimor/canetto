<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php'; // configura cookie_domain antes de session_start
if (session_status() === PHP_SESSION_NONE) session_start();

$usuario  = trim($_POST['usuario']  ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    $_SESSION['error'] = 'Completá todos los campos.';
    header('Location: login.php'); exit;
}

$pdo = Conexion::conectar();

// Buscar por campo "usuario" primero, luego por celular (para clientes)
$stmt = $pdo->prepare("SELECT * FROM usuario WHERE usuario = ? LIMIT 1");
$stmt->execute([$usuario]);
$user = $stmt->fetch();

if (!$user) {
    // Fallback: buscar por celular (clientes registrados desde la tienda)
    $stmt2 = $pdo->prepare("SELECT * FROM usuario WHERE celular = ? LIMIT 1");
    $stmt2->execute([$usuario]);
    $user = $stmt2->fetch();
}

if (!$user) {
    $_SESSION['error'] = 'Usuario o celular no encontrado.';
    header('Location: login.php'); exit;
}

if ((int)$user['activo'] !== 1) {
    $_SESSION['error'] = 'Usuario inactivo.';
    header('Location: login.php'); exit;
}

// Verificar contraseña (soporta bcrypt y texto plano legado)
$hash     = $user['password_hash'];
$esValida = str_starts_with($hash, '$2y$')
    ? password_verify($password, $hash)
    : ($password === $hash);

if (!$esValida) {
    $_SESSION['error'] = 'Contraseña incorrecta.';
    header('Location: login.php'); exit;
}

// Buscar TODOS los roles del usuario
$rolStmt = $pdo->prepare("
    SELECT r.idroles, r.nombre
    FROM usuarios_roles ur
    JOIN roles r ON r.idroles = ur.roles_idroles
    WHERE ur.usuario_idusuario = ?
");
$rolStmt->execute([$user['idusuario']]);
$roles = $rolStmt->fetchAll(PDO::FETCH_ASSOC);

// Este login es para administración — buscar rol admin entre todos los roles
$rolesAdmin = ['admin', 'administrador', 'administracion'];
$rolAdmin   = null;
foreach ($roles as $r) {
    if (in_array(strtolower($r['nombre']), $rolesAdmin, true)) { $rolAdmin = $r; break; }
}

if (!$rolAdmin) {
    // No tiene rol de admin → acceso denegado en este login
    session_destroy();
    $_SESSION['error'] = 'No tenés permiso para acceder al panel de administración.';
    redirect(URL_LOGIN . '/login.php');
}

session_regenerate_id(true);

$_SESSION['usuario_id'] = $user['idusuario'];
$_SESSION['nombre']     = $user['nombre'];
$_SESSION['apellido']   = $user['apellido'];
$_SESSION['rol']        = strtolower($rolAdmin['nombre']);
$_SESSION['rol_id']     = $rolAdmin['idroles'];

redirect(URL_ADMIN . '/index.php');
