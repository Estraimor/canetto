<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
header('Content-Type: application/json');

try {
    $idProducto = filter_input(INPUT_GET, 'producto', FILTER_VALIDATE_INT);
    if (!$idProducto) { echo json_encode([]); exit; }

    $pdo  = Conexion::conectar();
    $stmt = $pdo->prepare("
        SELECT
            pp.idproducto_packaging,
            pp.packaging_idpackaging,
            pp.cantidad,
            pk.nombre,
            um.abreviatura AS unidad
        FROM producto_packaging pp
        JOIN packaging pk ON pk.idpackaging = pp.packaging_idpackaging
        JOIN unidad_medida um ON um.idunidad_medida = pk.unidad_medida_idunidad_medida
        WHERE pp.productos_idproductos = ?
        ORDER BY pk.nombre ASC
    ");
    $stmt->execute([$idProducto]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
