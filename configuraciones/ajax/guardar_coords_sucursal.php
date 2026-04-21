<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'No autorizado']); exit; }

header('Content-Type: application/json; charset=utf-8');
$data = json_decode(file_get_contents('php://input'), true);

$id  = isset($data['idsucursal']) ? (int)$data['idsucursal'] : 0;
$lat = isset($data['latitud'])    ? (float)$data['latitud']  : null;
$lng = isset($data['longitud'])   ? (float)$data['longitud'] : null;

if (!$id || $lat === null || $lng === null) {
    echo json_encode(['ok'=>false, 'msg'=>'Datos incompletos']); exit;
}

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['ok'=>false, 'msg'=>'Coordenadas fuera de rango']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Ensure columns exist (idempotent)
    foreach (["ALTER TABLE sucursal ADD COLUMN latitud DECIMAL(10,7) NULL",
              "ALTER TABLE sucursal ADD COLUMN longitud DECIMAL(10,7) NULL"] as $sql) {
        try { $pdo->exec($sql); } catch (Throwable $e) {}
    }

    $stmt = $pdo->prepare("UPDATE sucursal SET latitud = ?, longitud = ? WHERE idsucursal = ?");
    $stmt->execute([$lat, $lng, $id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['ok'=>false, 'msg'=>'Sucursal no encontrada']); exit;
    }

    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
}
