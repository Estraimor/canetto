<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
header('Content-Type: application/json');

$pdo = Conexion::conectar();

// Agregar columnas nuevas si no existen (migración segura)
foreach ([
    "ALTER TABLE compra_materia_prima ADD COLUMN unidad_compra   VARCHAR(10)    NULL AFTER costo",
    "ALTER TABLE compra_materia_prima ADD COLUMN cantidad_original DECIMAL(10,3) NULL AFTER unidad_compra",
] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

try {
    $stmt = $pdo->query("
        SELECT
            c.id,
            p.nombre    AS proveedor_nombre,
            mp.nombre   AS materia_nombre,
            c.cantidad,
            c.costo,
            c.unidad_compra,
            c.cantidad_original,
            um.abreviatura AS unidad_base,
            c.stock_anterior,
            c.stock_nuevo,
            c.estado,
            c.cancelado_at,
            c.cancelado_motivo,
            c.created_at
        FROM compra_materia_prima c
        INNER JOIN proveedor p
            ON p.idproveedor = c.proveedor_idproveedor
        INNER JOIN materia_prima mp
            ON mp.idmateria_prima = c.materia_prima_idmateria_prima
        LEFT JOIN unidad_medida um
            ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
        ORDER BY c.created_at DESC
        LIMIT 200
    ");

    echo json_encode(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

} catch (Exception $e) {
    echo json_encode(['ok' => true, 'data' => []]);
}
