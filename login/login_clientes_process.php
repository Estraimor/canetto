<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php'; // configura cookie_domain antes de session_start
if (session_status() === PHP_SESSION_NONE) session_start();

$esAjax   = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
$usuario  = trim($_POST['usuario']  ?? '');
$password = trim($_POST['password'] ?? '');

function errorLogin(string $msg, bool $esAjax): never {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'mensaje' => $msg]);
        exit;
    }
    $_SESSION['error_cliente'] = $msg;
    header('Location: login_clientes.php'); exit;
}

if ($usuario === '' || $password === '') {
    errorLogin('Completá todos los campos.', $esAjax);
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
    errorLogin('Celular o usuario no encontrado.', $esAjax);
}

if ((int)$user['activo'] !== 1) {
    errorLogin('Usuario inactivo.', $esAjax);
}

// Verificar contraseña
$hash     = $user['password_hash'];
$esValida = str_starts_with($hash, '$2y$')
    ? password_verify($password, $hash)
    : ($password === $hash);

if (!$esValida) {
    errorLogin('Contraseña incorrecta.', $esAjax);
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
    errorLogin('Esta área es solo para clientes.', $esAjax);
}

$rol = $rolCliente;

$_retorno = $_SESSION['login_retorno'] ?? '';
session_regenerate_id(true);

$_SESSION['usuario_id']              = $user['idusuario'];
$_SESSION['nombre']                  = $user['nombre'];
$_SESSION['apellido']                = $user['apellido'];
$_SESSION['rol']                     = 'cliente';
$_SESSION['rol_id']                  = $rol['idroles'] ?? null;
$_SESSION['tienda_cliente_id']       = $user['idusuario'];
$_SESSION['tienda_cliente_nombre']   = trim($user['nombre'] . ' ' . ($user['apellido'] ?? ''));

$destino = ($_retorno && str_starts_with($_retorno, URL_TIENDA)) ? $_retorno : URL_TIENDA . '/tienda.php';
unset($_SESSION['login_retorno']);

// Si viene por fetch (login con animación), devolver JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'ok'       => true,
        'nombre'   => $_SESSION['tienda_cliente_nombre'],
        'redirect' => $destino,
    ]);
    exit;
}
redirect($destino);
