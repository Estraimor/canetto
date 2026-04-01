<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

// 🔥 FALTABA ESTO
$pdo = Conexion::conectar();

try {

    $stmt = $pdo->query("
        SELECT
            c.id,
            p.nombre  AS proveedor_nombre,
            mp.nombre AS materia_nombre,
            c.cantidad,
            c.stock_nuevo,
            c.created_at
        FROM compra_materia_prima c
        INNER JOIN proveedor p 
            ON p.idproveedor = c.proveedor_idproveedor
        INNER JOIN materia_prima mp 
            ON mp.idmateria_prima = c.materia_prima_idmateria_prima
        ORDER BY c.created_at DESC
        LIMIT 100
    ");

    echo json_encode([
        'ok'   => true,
        'data' => $stmt->fetchAll()
    ]);

} catch (PDOException $e) {

    // 🔥 para debug real (activar si algo falla)
    // echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);

    // fallback (como ya habías pensado)
    echo json_encode([
        'ok'   => true,
        'data' => []
    ]);
}