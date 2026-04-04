<?php
declare(strict_types=1);
define('APP_BOOT', true);
session_start();
require_once __DIR__ . '/../config/conexion.php';

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

// Buscar rol del usuario
$rolStmt = $pdo->prepare("
    SELECT r.idroles, r.nombre
    FROM usuarios_roles ur
    JOIN roles r ON r.idroles = ur.roles_idroles
    WHERE ur.usuario_idusuario = ?
    LIMIT 1
");
$rolStmt->execute([$user['idusuario']]);
$rol = $rolStmt->fetch();

session_regenerate_id(true);

// Guardar datos comunes de sesión
$_SESSION['usuario_id'] = $user['idusuario'];
$_SESSION['nombre']     = $user['nombre'];
$_SESSION['apellido']   = $user['apellido'];
$_SESSION['rol']        = strtolower($rol['nombre'] ?? 'admin');
$_SESSION['rol_id']     = $rol['idroles'] ?? null;

// Redirigir según rol
if (strtolower($rol['nombre'] ?? '') === 'cliente') {
    // Cliente → tienda (también seteamos la clave de sesión de tienda)
    $_SESSION['tienda_cliente_id']     = $user['idusuario'];
    $_SESSION['tienda_cliente_nombre'] = trim($user['nombre'] . ' ' . ($user['apellido'] ?? ''));
    header('Location: /canetto/tienda/index.php'); exit;
} else {
    // Administrador u otro rol → panel admin
    header('Location: /canetto/administracion/index.php'); exit;
}
