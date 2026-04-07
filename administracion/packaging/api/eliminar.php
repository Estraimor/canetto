<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['success' => false]); exit; }

    $pdo = Conexion::conectar();

    $nombre = $pdo->prepare("SELECT nombre FROM packaging WHERE idpackaging = ?");
    $nombre->execute([$id]);
    $nombre = $nombre->fetchColumn() ?: "ID {$id}";

    // Verificar si está asignado a algún producto
    $stmtUso = $pdo->prepare("
        SELECT p.nombre
        FROM producto_packaging pp
        JOIN productos p ON p.idproductos = pp.productos_idproductos
        WHERE pp.packaging_idpackaging = ?
        ORDER BY p.nombre ASC
    ");
    $stmtUso->execute([$id]);
    $productos = $stmtUso->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($productos)) {
        echo json_encode(['success' => false, 'en_uso' => true, 'productos' => $productos]);
        exit;
    }

    $pdo->prepare("UPDATE packaging SET activo = 0, updated_at = NOW() WHERE idpackaging = ?")
        ->execute([$id]);

    audit($pdo, 'eliminar', 'packaging', "Desactivó packaging: '{$nombre}' (ID: {$id})");
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
