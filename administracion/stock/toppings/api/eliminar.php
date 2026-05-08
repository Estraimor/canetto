<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
require_once __DIR__ . '/../../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$pdo  = Conexion::conectar();
$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);

if ($id <= 0) { echo json_encode(['ok' => false, 'msg' => 'ID inválido']); exit; }

try {
    $stmt = $pdo->prepare("SELECT nombre FROM toppings WHERE idtoppings = ?");
    $stmt->execute([$id]);
    $nombre = $stmt->fetchColumn() ?: "ID {$id}";

    $pdo->prepare("DELETE FROM toppings_stock WHERE toppings_idtoppings = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM toppings WHERE idtoppings = ?")->execute([$id]);

    audit($pdo, 'eliminar', 'toppings', "Eliminado topping: {$nombre}");

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
