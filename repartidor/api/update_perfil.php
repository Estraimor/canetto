<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No autenticado']); exit;
}

$data     = json_decode(file_get_contents('php://input'), true) ?: [];
$nombre   = trim($data['nombre']   ?? '');
$apellido = trim($data['apellido'] ?? '');
$celular  = trim($data['celular']  ?? '');
$email    = trim($data['email']    ?? '');
$dni      = trim($data['dni']      ?? '');

if (!$nombre) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'El nombre es requerido']); exit;
}

if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'El email no es válido']); exit;
}

try {
    $pdo = Conexion::conectar();

    $pdo->prepare("UPDATE usuario SET nombre = ?, apellido = ?, celular = ?, email = ?, dni = ?, updated_at = NOW() WHERE idusuario = ?")
        ->execute([$nombre, $apellido ?: null, $celular ?: null, $email ?: null, $dni ?: null, $repId]);

    $fullName = trim("$nombre $apellido");
    $_SESSION['repartidor_nombre'] = $fullName;
    ob_end_clean();
    echo json_encode(['success' => true, 'nombre' => $fullName]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
