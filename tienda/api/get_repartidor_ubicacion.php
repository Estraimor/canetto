<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$clienteId = $_SESSION['tienda_cliente_id'] ?? null;
$idVenta   = (int)($_GET['id'] ?? 0);

if (!$clienteId || !$idVenta) {
    echo json_encode(['ok' => false]); exit;
}

try {
    $pdo  = Conexion::conectar();
    $stmt = $pdo->prepare("
        SELECT v.estado_venta_idestado_venta AS estado_id,
               v.lat_entrega, v.lng_entrega,
               u.ubicacion_lat  AS rep_lat,
               u.ubicacion_lng  AS rep_lng,
               u.ubicacion_at   AS rep_at
        FROM ventas v
        LEFT JOIN usuario u ON u.idusuario = v.repartidor_idusuario
        WHERE v.idventas = ? AND v.cliente_idcliente = ?
    ");
    $stmt->execute([$idVenta, $clienteId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) { echo json_encode(['ok' => false]); exit; }

    echo json_encode([
        'ok'        => true,
        'estado_id' => (int)$row['estado_id'],
        'rep_lat'   => $row['rep_lat'] ? (float)$row['rep_lat'] : null,
        'rep_lng'   => $row['rep_lng'] ? (float)$row['rep_lng'] : null,
        'rep_at'    => $row['rep_at'],
        'dest_lat'  => $row['lat_entrega'] ? (float)$row['lat_entrega'] : null,
        'dest_lng'  => $row['lng_entrega'] ? (float)$row['lng_entrega'] : null,
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false]);
}
