<?php
// Ventas/Historial/api/actualizar_estado.php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../../config/conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$id_venta = intval($input['id_venta'] ?? 0);
$estado   = intval($input['estado']   ?? 0);

if (!$id_venta || $estado < 1 || $estado > 4) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

try {
    $pdo  = Conexion::conectar();
    $stmt = $pdo->prepare(
        "UPDATE ventas
         SET estado_venta_idestado_venta = :estado, updated_at = NOW()
         WHERE idventas = :id"
    );
    $stmt->execute([':estado' => $estado, ':id' => $id_venta]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
