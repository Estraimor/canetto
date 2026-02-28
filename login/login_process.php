<?php
declare(strict_types=1);

session_start();
define('APP_BOOT', true);

require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$usuario  = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if (!$usuario || !$password) {
    $_SESSION['error'] = "Completa todos los campos.";
    header("Location: /canetto/ggg.php");
    exit;
}

try {

    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT idusuario, nombre, apellido, password_hash, activo
        FROM usuario
        WHERE usuario = :usuario
        LIMIT 1
    ");

    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch();

    // ❌ Usuario no existe
    if (!$user) {
        sleep(1);
        throw new Exception("Credenciales inválidas.");
    }

    // ❌ Usuario inactivo
    if ((int)$user['activo'] !== 1) {
        throw new Exception("Usuario inactivo.");
    }

    // ❌ Verificación SIN HASH (texto plano)
    if ($password !== $user['password_hash']) {
        sleep(1);
        throw new Exception("Credenciales inválidas.");
    }

    // 🔐 Seguridad sesión
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $user['idusuario'];
    $_SESSION['nombre']     = $user['nombre'];
    $_SESSION['apellido']   = $user['apellido'];

    header("Location: /canetto/administracion/index.php");
    exit;

} catch (Throwable $e) {

    $_SESSION['error'] = $e->getMessage();
    header("Location: /canetto/administracion/index.php");
    exit;
}