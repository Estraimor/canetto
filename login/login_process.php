<?php
declare(strict_types=1);

define('APP_BOOT', true);

session_start();

require_once __DIR__ . '/../config/conexion.php';

$usuario  = trim($_POST['usuario'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    $_SESSION['error'] = "Completa todos los campos.";
    header("Location: login.php");
    exit;
}

$pdo = Conexion::conectar();

$stmt = $pdo->prepare("
    SELECT * FROM usuario WHERE usuario = :usuario LIMIT 1
");

$stmt->execute(['usuario' => $usuario]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "Usuario no encontrado.";
    header("Location: login.php");
    exit;
}

if ((int)$user['activo'] !== 1) {
    $_SESSION['error'] = "Usuario inactivo.";
    header("Location: login.php");
    exit;
}

$hashGuardado = $user['password_hash'];
$esValida = str_starts_with($hashGuardado, '$2y$')
    ? password_verify($password, $hashGuardado)       // bcrypt
    : ($password === $hashGuardado);                  // legado texto plano

if (!$esValida) {
    $_SESSION['error'] = "Contraseña incorrecta.";
    header("Location: login.php");
    exit;
}

session_regenerate_id(true);

$_SESSION['usuario_id'] = $user['idusuario'];
$_SESSION['nombre']     = $user['nombre'];
$_SESSION['apellido']   = $user['apellido'];

header("Location: ../administracion/index.php");
exit;