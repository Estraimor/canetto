<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

try {
    // 🔥 Crear conexión
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("
        SELECT
            mhp.materia_prima_idmateria_prima AS idmateria_prima,
            mhp.proveedor_idproveedor         AS idproveedor,
            p.nombre  AS proveedor_nombre,
            mp.nombre AS materia_nombre
        FROM materia_prima_has_proveedor mhp
        INNER JOIN proveedor p 
            ON p.idproveedor = mhp.proveedor_idproveedor
        INNER JOIN materia_prima mp 
            ON mp.idmateria_prima = mhp.materia_prima_idmateria_prima
        ORDER BY p.nombre ASC, mp.nombre ASC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}