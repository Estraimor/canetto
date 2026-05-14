<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->query("
        SELECT t.idtoppings, t.nombre, t.precio,
               COALESCE(ts.stock_actual, 0) AS stock
        FROM toppings t
        LEFT JOIN toppings_stock ts ON ts.toppings_idtoppings = t.idtoppings
        WHERE t.activo = 1
        ORDER BY t.nombre ASC
    ");

    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([]);
}
