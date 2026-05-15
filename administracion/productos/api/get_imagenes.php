<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['imagenes' => []]); exit; }

try {
    $pdo  = Conexion::conectar();
    $stmt = $pdo->prepare("SELECT id, archivo, orden FROM productos_imagenes WHERE productos_idproductos = ? ORDER BY orden ASC, id ASC");
    $stmt->execute([$id]);
    echo json_encode(['imagenes' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(['imagenes' => []]);
}
