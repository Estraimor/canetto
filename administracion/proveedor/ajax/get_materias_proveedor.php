<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
header('Content-Type: application/json');

$pdo = Conexion::conectar();
$idproveedor = intval($_GET['idproveedor'] ?? 0);

if (!$idproveedor) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            mp.idmateria_prima,
            mp.nombre,
            mp.stock_actual,
            mp.stock_minimo,
            mp.unidad_medida_idunidad_medida AS unidad_id,
            um.nombre      AS unidad_nombre,
            um.abreviatura AS unidad_abrev,
            mhp.costo
        FROM materia_prima mp
        INNER JOIN materia_prima_has_proveedor mhp
            ON mhp.materia_prima_idmateria_prima = mp.idmateria_prima
            AND mhp.proveedor_idproveedor = ?
        LEFT JOIN unidad_medida um
            ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
        WHERE mp.activo = 1
        ORDER BY mp.nombre ASC
    ");
    $stmt->execute([$idproveedor]);

    echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
