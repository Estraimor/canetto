<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$ids   = array_filter(array_map('intval', $input['ids'] ?? []), fn($v) => $v > 0);

if (empty($ids)) { echo json_encode(['ok'=>false]); exit; }

try {
    $pdo  = Conexion::conectar();
    $stmt = $pdo->prepare("UPDATE productos_imagenes SET orden = ? WHERE id = ?");
    foreach (array_values($ids) as $orden => $id) {
        $stmt->execute([$orden, $id]);
    }

    // Sincronizar campo imagen del producto con la primera (orden=0)
    $first = $pdo->prepare("
        SELECT pi.archivo, pi.productos_idproductos
        FROM productos_imagenes pi
        WHERE pi.id = ?
    ");
    $first->execute([$ids[array_key_first($ids)]]);
    $row = $first->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pdo->prepare("UPDATE productos SET imagen = ? WHERE idproductos = ?")
            ->execute([$row['archivo'], $row['productos_idproductos']]);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
