<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
require_once __DIR__ . '/../../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$id_venta = intval($input['id_venta'] ?? 0);
if (!$id_venta) {
    ob_end_clean(); echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
}

try {
    $pdo = Conexion::conectar();

    $pdo->prepare("
        UPDATE ventas
        SET repartidor_idusuario = NULL,
            repartidor_pendiente_idusuario = NULL,
            via_uber = 0,
            uber_link = NULL,
            updated_at = NOW()
        WHERE idventas = :id
    ")->execute([':id' => $id_venta]);

    audit($pdo, 'editar', 'pedidos', "Pedido #{$id_venta}: repartidor liberado, vuelve a búsqueda automática");

    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
