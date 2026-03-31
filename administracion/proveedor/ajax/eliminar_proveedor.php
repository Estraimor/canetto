<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['idproveedor'] ?? 0);

if (!$id) { echo json_encode(['ok' => false, 'msg' => 'ID inválido']); exit; }

try {
    $pdo->prepare("DELETE FROM proveedor WHERE idproveedor=?")->execute([$id]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'No se puede eliminar: tiene registros asociados.']);
}
