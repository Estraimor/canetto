<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) { echo json_encode(['success' => false, 'message' => 'No autenticado']); exit; }

try {
    $pdo  = Conexion::conectar();
    $stmt = $pdo->prepare("SELECT nombre, apellido, celular, email FROM usuario WHERE idusuario = ?");
    $stmt->execute([$repId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) { echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']); exit; }
    echo json_encode(['success' => true, 'perfil' => $u]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
