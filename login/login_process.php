<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php'; // configura cookie_domain antes de session_start
if (session_status() === PHP_SESSION_NONE) session_start();

$esAjax   = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
$usuario  = trim($_POST['usuario']  ?? '');
$password = trim($_POST['password'] ?? '');

function errorLoginAdmin(string $msg, bool $esAjax): never {
    if ($esAjax) { header('Content-Type: application/json'); echo json_encode(['ok' => false, 'mensaje' => $msg]); exit; }
    $_SESSION['error'] = $msg;
    header('Location: login.php'); exit;
}

if ($usuario === '' || $password === '') {
    errorLoginAdmin('Completá todos los campos.', $esAjax);
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

if (!$user) { errorLoginAdmin('Usuario o celular no encontrado.', $esAjax); }
if ((int)$user['activo'] !== 1) { errorLoginAdmin('Usuario inactivo.', $esAjax); }

// Verificar contraseña (soporta bcrypt y texto plano legado)
$hash     = $user['password_hash'];
$esValida = str_starts_with($hash, '$2y$')
    ? password_verify($password, $hash)
    : ($password === $hash);

if (!$esValida) { errorLoginAdmin('Contraseña incorrecta.', $esAjax); }

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
    session_destroy();
    errorLoginAdmin('No tenés permiso para acceder al panel de administración.', $esAjax);
}

session_regenerate_id(true);

$_SESSION['usuario_id'] = $user['idusuario'];
$_SESSION['nombre']     = $user['nombre'];
$_SESSION['apellido']   = $user['apellido'];
$_SESSION['rol']        = strtolower($rolAdmin['nombre']);
$_SESSION['rol_id']     = $rolAdmin['idroles'];

if ($esAjax) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'nombre' => trim($user['nombre'] . ' ' . ($user['apellido'] ?? '')), 'redirect' => URL_ADMIN . '/index.php']);
    exit;
}
redirect(URL_ADMIN . '/index.php');
