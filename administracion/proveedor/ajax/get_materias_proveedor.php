<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
header('Content-Type: application/json');

$idproveedor = intval($_GET['idproveedor'] ?? 0);
if (!$idproveedor) { echo json_encode(['ok' => false, 'data' => []]); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT mp.idmateria_prima, mp.nombre, mp.stock_actual, mp.stock_minimo, mhp.costo
        FROM materia_prima mp
        INNER JOIN materia_prima_has_proveedor mhp
            ON mhp.materia_prima_idmateria_prima = mp.idmateria_prima
        WHERE mhp.proveedor_idproveedor = ?
        ORDER BY mp.nombre ASC
    ");
    $stmt->execute([$idproveedor]);
    echo json_encode(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
