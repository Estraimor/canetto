<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }
header('Content-Type: application/json; charset=utf-8');

$data       = json_decode(file_get_contents('php://input'), true);
$idProducto = (int)($data['productos_idproductos'] ?? 0);
$ids        = array_map('intval', $data['toppings'] ?? []);

if (!$idProducto) { echo json_encode(['ok'=>false,'msg'=>'Producto inválido']); exit; }

$pdo = Conexion::conectar();
$pdo->prepare("DELETE FROM producto_toppings WHERE productos_idproductos=?")->execute([$idProducto]);

foreach ($ids as $tid) {
    if ($tid > 0) {
        try {
            $pdo->prepare("INSERT INTO producto_toppings (productos_idproductos, toppings_idtoppings) VALUES (?,?)")->execute([$idProducto,$tid]);
        } catch (Throwable $e) {}
    }
}

echo json_encode(['ok'=>true]);
