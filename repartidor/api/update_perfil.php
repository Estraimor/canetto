<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) { echo json_encode(['success' => false, 'message' => 'No autenticado']); exit; }

$data     = json_decode(file_get_contents('php://input'), true) ?: [];
$nombre   = trim($data['nombre']   ?? '');
$apellido = trim($data['apellido'] ?? '');
$password = trim($data['password'] ?? '');

if (!$nombre) { echo json_encode(['success' => false, 'message' => 'El nombre es requerido']); exit; }

try {
    $pdo = Conexion::conectar();

    if ($password) {
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE usuario SET nombre = ?, apellido = ?, password = ? WHERE idusuario = ?")
            ->execute([$nombre, $apellido, $hash, $repId]);
    } else {
        $pdo->prepare("UPDATE usuario SET nombre = ?, apellido = ? WHERE idusuario = ?")
            ->execute([$nombre, $apellido, $repId]);
    }

    $fullName = trim("$nombre $apellido");
    $_SESSION['repartidor_nombre'] = $fullName;
    echo json_encode(['success' => true, 'nombre' => $fullName]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
