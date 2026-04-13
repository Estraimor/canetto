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

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$idVenta = (int)($input['id_venta'] ?? 0);
if (!$idVenta) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
}

try {
    $pdo = Conexion::conectar();

    $chk = $pdo->prepare("
        SELECT idventas FROM ventas
        WHERE idventas = ? AND repartidor_idusuario = ? AND estado_venta_idestado_venta = 3
    ");
    $chk->execute([$idVenta, $repId]);
    if (!$chk->fetch()) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado o ya entregado']); exit;
    }

    $pdo->prepare("UPDATE ventas SET estado_venta_idestado_venta = 4, updated_at = NOW() WHERE idventas = ?")
        ->execute([$idVenta]);

    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
