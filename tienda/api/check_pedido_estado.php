<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$idVenta   = (int)($_GET['id']  ?? 0);
$clienteId = (int)($_SESSION['tienda_cliente_id'] ?? $_GET['uid'] ?? 0);

if (!$idVenta) {
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $pdo = Conexion::conectar();

    // Leer estado de ventas
    if ($clienteId) {
        $stmt = $pdo->prepare("
            SELECT v.estado_venta_idestado_venta AS estado_id, ev.nombre AS estado_nombre
            FROM ventas v
            LEFT JOIN estado_venta ev ON ev.idestado_venta = v.estado_venta_idestado_venta
            WHERE v.idventas = ? AND v.usuario_idusuario = ?
        ");
        $stmt->execute([$idVenta, $clienteId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT v.estado_venta_idestado_venta AS estado_id, ev.nombre AS estado_nombre
            FROM ventas v
            LEFT JOIN estado_venta ev ON ev.idestado_venta = v.estado_venta_idestado_venta
            WHERE v.idventas = ?
        ");
        $stmt->execute([$idVenta]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['ok' => false]); exit; }

    $estadoId = (int)$row['estado_id'];

    // Si ventas aún dice "pendiente de pago" (5), revisar pagos_mercadopago —
    // el webhook puede haber llegado y actualizado esa tabla sin que ventas se haya
    // actualizado todavía (o viceversa).
    if ($estadoId === 5) {
        $mpRow = $pdo->prepare("
            SELECT estado_mp FROM pagos_mercadopago
            WHERE ventas_idventas = ?
            ORDER BY idpagos_mercadopago DESC
            LIMIT 1
        ");
        $mpRow->execute([$idVenta]);
        $mp = $mpRow->fetch(PDO::FETCH_ASSOC);

        if ($mp && $mp['estado_mp'] === 'approved') {
            // Sincronizar ventas por si el webhook no lo hizo
            $pdo->prepare("UPDATE ventas SET estado_venta_idestado_venta = 1, updated_at = NOW() WHERE idventas = ?")
                ->execute([$idVenta]);
            $estadoId = 1;
        } elseif ($mp && in_array($mp['estado_mp'], ['rejected', 'cancelled'], true)) {
            $pdo->prepare("UPDATE ventas SET estado_venta_idestado_venta = 6, updated_at = NOW() WHERE idventas = ?")
                ->execute([$idVenta]);
            $estadoId = 6;
        }
    }

    echo json_encode([
        'ok'           => true,
        'estado_id'    => $estadoId,
        'estado_nombre'=> $row['estado_nombre'],
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false]);
}
