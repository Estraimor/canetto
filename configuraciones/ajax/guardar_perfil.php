<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = $_SESSION['usuario_id'] ?? null;
if (!$userId) { echo json_encode(['ok' => false, 'msg' => 'No autorizado']); exit; }

$data     = json_decode(file_get_contents('php://input'), true) ?? [];
$nombre   = trim($data['nombre']   ?? '');
$apellido = trim($data['apellido'] ?? '');
$email    = trim($data['email']    ?? '');
$celular  = trim($data['celular']  ?? '');
$password = $data['password'] ?? '';

if (!$nombre) { echo json_encode(['ok' => false, 'msg' => 'El nombre es obligatorio']); exit; }

try {
    $pdo = Conexion::conectar();

    if ($password !== '') {
        if (strlen($password) < 6) {
            echo json_encode(['ok' => false, 'msg' => 'La contraseña debe tener al menos 6 caracteres']); exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE usuario SET nombre=?, apellido=?, email=?, celular=?, password_hash=? WHERE idusuario=?")
            ->execute([$nombre, $apellido, $email ?: null, $celular ?: null, $hash, $userId]);
    } else {
        $pdo->prepare("UPDATE usuario SET nombre=?, apellido=?, email=?, celular=? WHERE idusuario=?")
            ->execute([$nombre, $apellido, $email ?: null, $celular ?: null, $userId]);
    }

    $_SESSION['nombre']   = $nombre;
    $_SESSION['apellido'] = $apellido;

    echo json_encode(['ok' => true, 'nombre' => $nombre, 'apellido' => $apellido]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar: ' . $e->getMessage()]);
}
