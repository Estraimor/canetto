<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo        = Conexion::conectar();
    $idProducto = filter_input(INPUT_POST, 'producto', FILTER_VALIDATE_INT);
    $items      = json_decode($_POST['items'] ?? '[]', true);

    if (!$idProducto) {
        echo json_encode(['success' => false, 'message' => 'Producto inválido']);
        exit;
    }

    $pdo->beginTransaction();

    // Reemplazar todas las asignaciones del producto
    $pdo->prepare("DELETE FROM producto_packaging WHERE productos_idproductos = ?")
        ->execute([$idProducto]);

    $stmt = $pdo->prepare("
        INSERT INTO producto_packaging (productos_idproductos, packaging_idpackaging, cantidad)
        VALUES (?, ?, ?)
    ");

    foreach ($items as $item) {
        $pkgId    = (int)($item['packaging_idpackaging'] ?? $item['packaging_id'] ?? 0);
        $cantidad = (float)($item['cantidad'] ?? 0);
        if ($pkgId > 0 && $cantidad > 0) {
            $stmt->execute([$idProducto, $pkgId, $cantidad]);
        }
    }

    $pdo->commit();

    // Nombre del producto para auditoría
    $nomProd = $pdo->prepare("SELECT nombre FROM productos WHERE idproductos = ?");
    $nomProd->execute([$idProducto]);
    $nomProd = $nomProd->fetchColumn() ?: "ID {$idProducto}";

    audit($pdo, 'editar', 'packaging', "Actualizó packaging del producto: '{$nomProd}' — " . count($items) . " item(s)");
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
