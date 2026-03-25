<?php

define('APP_BOOT', true);

require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

try {

    $pdo = Conexion::conectar();

    $stmt = $pdo->query("
        SELECT 
            p.idproductos,
            p.nombre,

            MAX(CASE 
                WHEN sp.tipo_stock = 'CONGELADO' 
                THEN sp.stock_minimo 
            END) AS min_congelado,

            MAX(CASE 
                WHEN sp.tipo_stock = 'HECHO' 
                THEN sp.stock_minimo 
            END) AS min_hecho

        FROM productos p

        LEFT JOIN stock_productos sp
            ON sp.productos_idproductos = p.idproductos

        WHERE p.activo = 1
        AND p.tipo = 'producto'

        GROUP BY p.idproductos, p.nombre

        ORDER BY p.nombre ASC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "mensaje" => $e->getMessage()
    ]);
}