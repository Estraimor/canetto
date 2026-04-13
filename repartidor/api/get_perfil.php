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

try {
    $pdo  = Conexion::conectar();
    $stmt = $pdo->prepare("SELECT nombre, apellido, celular, email, dni FROM usuario WHERE idusuario = ?");
    $stmt->execute([$repId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) { ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']); exit; }
    ob_end_clean();
    echo json_encode(['success' => true, 'perfil' => $u]);
} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
