<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]); exit;
}

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) {
    echo json_encode(['ok' => false, 'message' => 'No autenticado']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$lat   = isset($input['lat']) ? (float)$input['lat'] : null;
$lng   = isset($input['lng']) ? (float)$input['lng'] : null;

if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    echo json_encode(['ok' => false, 'message' => 'Coordenadas inválidas']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Agregar columnas si no existen
    foreach ([
        "ALTER TABLE usuario ADD COLUMN ubicacion_lat  DECIMAL(10,8) NULL",
        "ALTER TABLE usuario ADD COLUMN ubicacion_lng  DECIMAL(11,8) NULL",
        "ALTER TABLE usuario ADD COLUMN ubicacion_at   DATETIME NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    $pdo->prepare("
        UPDATE usuario
        SET ubicacion_lat = ?, ubicacion_lng = ?, ubicacion_at = NOW()
        WHERE idusuario = ?
    ")->execute([$lat, $lng, $repId]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false]);
}
