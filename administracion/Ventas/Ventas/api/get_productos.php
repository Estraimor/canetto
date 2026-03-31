<?php
// api/get_productos.php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    // Traemos stock separado por tipo (CONGELADO / HECHO)
    $sql = "SELECT
                p.idproductos, p.nombre, p.precio,
                COALESCE(SUM(CASE WHEN UPPER(sp.tipo_stock) = 'CONGELADO' THEN sp.stock_actual ELSE 0 END), 0) AS stock_congelado,
                COALESCE(SUM(CASE WHEN UPPER(sp.tipo_stock) = 'HECHO'     THEN sp.stock_actual ELSE 0 END), 0) AS stock_hecho,
                COALESCE(SUM(sp.stock_actual), 0) AS stock
            FROM productos p
            LEFT JOIN stock_productos sp
                   ON sp.productos_idproductos = p.idproductos
                  AND (sp.activo = 1 OR sp.activo IS NULL)
            GROUP BY p.idproductos, p.nombre, p.precio
            ORDER BY p.nombre ASC";

    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll());

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
