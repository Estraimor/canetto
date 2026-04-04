<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$pdo  = Conexion::conectar();
$data = json_decode(file_get_contents("php://input"), true);

$id           = $data['id'];
$congelado    = $data['congelado'];
$hecho        = $data['hecho'];
$minCongelado = $data['minCongelado'];
$minHecho     = $data['minHecho'];

try {
    // Obtener nombre del producto para auditoría
    $stmtNom = $pdo->prepare("SELECT nombre FROM productos WHERE idproductos = ?");
    $stmtNom->execute([$id]);
    $nombreProducto = $stmtNom->fetchColumn() ?: "ID {$id}";

    // Congelado
    $pdo->prepare("
        UPDATE stock_productos
        SET stock_actual = ?, stock_minimo = ?, updated_at = NOW()
        WHERE productos_idproductos = ? AND tipo_stock = 'CONGELADO'
    ")->execute([$congelado, $minCongelado, $id]);

    // Hecho
    $pdo->prepare("
        UPDATE stock_productos
        SET stock_actual = ?, stock_minimo = ?, updated_at = NOW()
        WHERE productos_idproductos = ? AND tipo_stock = 'HECHO'
    ")->execute([$hecho, $minHecho, $id]);

    audit($pdo, 'editar', 'stock',
        "Ajuste manual de stock: {$nombreProducto}" .
        " | Congelado: {$congelado} u. (mín: {$minCongelado})" .
        " | Hecho: {$hecho} u. (mín: {$minHecho})"
    );

    echo json_encode(["ok" => true]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
