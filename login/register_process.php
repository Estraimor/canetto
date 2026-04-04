<?php
declare(strict_types=1);
define('APP_BOOT', true);
session_start();
require_once __DIR__ . '/../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$nombre   = trim($_POST['nombre']   ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$celular  = trim($_POST['celular']  ?? '');
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
        INSERT INTO usuario (nombre, apellido, celular, usuario, password_hash, activo, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    $ins->execute([$nombre, $apellido ?: null, $celular, $celular, $hash]);
    $newId = (int)$pdo->lastInsertId();

    // Asignar rol Cliente
    $pdo->prepare("INSERT INTO usuarios_roles (usuario_idusuario, roles_idroles) VALUES (?, ?)")
        ->execute([$newId, $rolCliente]);

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
