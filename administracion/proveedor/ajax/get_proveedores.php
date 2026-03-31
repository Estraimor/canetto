<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM proveedor ORDER BY nombre ASC");
    echo json_encode(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
