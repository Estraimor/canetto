<?php
// api/get_metodos_pago.php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }
header('Content-Type: application/json');

try {
    $pdo  = Conexion::conectar();
    $stmt = $pdo->query("SELECT idmetodo_pago, nombre FROM metodo_pago ORDER BY idmetodo_pago");
    echo json_encode($stmt->fetchAll());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
