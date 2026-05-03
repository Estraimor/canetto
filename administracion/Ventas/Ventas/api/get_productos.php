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
                COALESCE(p.tipo, 'galletita') AS tipo,
                COALESCE(p.imagen, '') AS imagen,
                COALESCE(SUM(CASE WHEN UPPER(sp.tipo_stock) = 'CONGELADO' THEN sp.stock_actual ELSE 0 END), 0) AS stock_congelado,
                COALESCE(SUM(CASE WHEN UPPER(sp.tipo_stock) = 'HECHO'     THEN sp.stock_actual ELSE 0 END), 0) AS stock_hecho,
                COALESCE(SUM(sp.stock_actual), 0) AS stock,
                (SELECT COUNT(*) FROM producto_toppings pt WHERE pt.productos_idproductos = p.idproductos) AS tiene_toppings
            FROM productos p
            LEFT JOIN stock_productos sp
                   ON sp.productos_idproductos = p.idproductos
                  AND (sp.activo = 1 OR sp.activo IS NULL)
            WHERE p.activo = 1
            GROUP BY p.idproductos, p.nombre, p.precio, p.tipo, p.imagen
            ORDER BY CASE p.tipo WHEN 'box' THEN 0 ELSE 1 END, p.nombre ASC";

    $stmt = $pdo->query($sql);
    echo json_encode($stmt->fetchAll());

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
