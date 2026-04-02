<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

// 🔥 CREAR CONEXIÓN (faltaba)
$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {

    $stmt = $pdo->query("
        SELECT
            c.id,
            p.nombre  AS proveedor_nombre,
            mp.nombre AS materia_nombre,
            c.cantidad,
            c.costo,
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
        ORDER BY c.created_at DESC
        LIMIT 200
    ");

    echo json_encode([
        'ok'   => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Exception $e) {

    // 🔥 para debug real (descomentar si necesitás)
    // echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
    
    echo json_encode([
        'ok'   => true,
        'data' => []
    ]);
}