<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id    = (int)($input['id'] ?? 0);
if (!$id) { echo json_encode(['ok'=>false]); exit; }

try {
    $pdo  = Conexion::conectar();
    // Obtener archivo antes de borrar
    $row  = $pdo->prepare("SELECT archivo, productos_idproductos FROM productos_imagenes WHERE id = ?");
    $row->execute([$id]);
    $img  = $row->fetch(PDO::FETCH_ASSOC);
    if (!$img) { echo json_encode(['ok'=>false,'msg'=>'No encontrada']); exit; }

    $pdo->prepare("DELETE FROM productos_imagenes WHERE id = ?")->execute([$id]);

    // Si ya no quedan imágenes en la tabla para ese producto, limpiar el campo imagen del producto
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM productos_imagenes WHERE productos_idproductos = ?");
    $cnt->execute([$img['productos_idproductos']]);
    if ((int)$cnt->fetchColumn() === 0) {
        $pdo->prepare("UPDATE productos SET imagen = NULL WHERE idproductos = ?")->execute([$img['productos_idproductos']]);
    } else {
        // Sincronizar campo imagen con la primera imagen restante
        $first = $pdo->prepare("SELECT archivo FROM productos_imagenes WHERE productos_idproductos = ? ORDER BY orden ASC, id ASC LIMIT 1");
        $first->execute([$img['productos_idproductos']]);
        $pdo->prepare("UPDATE productos SET imagen = ? WHERE idproductos = ?")->execute([$first->fetchColumn(), $img['productos_idproductos']]);
    }

    // Borrar archivo físico
    $filePath = __DIR__ . '/../../../img/productos/' . $img['archivo'];
    if (file_exists($filePath)) @unlink($filePath);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
