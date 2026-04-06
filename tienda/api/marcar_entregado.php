<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

if (!isset($_SESSION['tienda_cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']); exit;
}

$uid      = (int)$_SESSION['tienda_cliente_id'];
$id_venta = (int)($_POST['id_venta'] ?? 0);

if (!$id_venta) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Verificar que el pedido pertenece al cliente y está en estado 3 (En manos del Repartidor)
    $stmt = $pdo->prepare("
        SELECT v.idventas, v.estado_venta_idestado_venta
        FROM ventas v
        WHERE v.idventas = ? AND v.usuario_idusuario = ?
    ");
    $stmt->execute([$id_venta, $uid]);
    $venta = $stmt->fetch();

    if (!$venta) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']); exit;
    }

    if ((int)$venta['estado_venta_idestado_venta'] !== 3) {
        echo json_encode(['success' => false, 'message' => 'El pedido no está en camino todavía']); exit;
    }

    $upd = $pdo->prepare("
        UPDATE ventas SET estado_venta_idestado_venta = 4, updated_at = NOW()
        WHERE idventas = ?
    ");
    $upd->execute([$id_venta]);

    audit($pdo, 'editar', 'ventas',
        "Cliente confirmó recepción del pedido #{$id_venta} → Entregado"
    );

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
