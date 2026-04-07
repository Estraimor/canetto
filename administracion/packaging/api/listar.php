<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
header('Content-Type: application/json');

try {
    $pdo  = Conexion::conectar();
    $data = $pdo->query("
        SELECT
            pk.idpackaging,
            pk.nombre,
            pk.descripcion,
            pk.stock_actual,
            pk.stock_minimo,
            pk.activo,
            pk.unidad_medida_idunidad_medida,
            um.abreviatura AS unidad
        FROM packaging pk
        JOIN unidad_medida um ON um.idunidad_medida = pk.unidad_medida_idunidad_medida
        ORDER BY pk.nombre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $s = (float)$row['stock_actual'];
        $m = (float)$row['stock_minimo'];

        if ($s <= 0) {
            $row['estado_key']  = 'nostock';
            $row['estado_html'] = "<span class='status-critical'>Sin stock</span>";
        } elseif ($s <= $m) {
            $row['estado_key']  = 'critical';
            $row['estado_html'] = "<span class='status-critical'>Crítico</span>";
        } elseif ($m > 0 && $s <= $m * 1.3) {
            $row['estado_key']  = 'low';
            $row['estado_html'] = "<span class='status-low'>Bajo</span>";
        } else {
            $row['estado_key']  = 'ok';
            $row['estado_html'] = "<span class='status-ok'>OK</span>";
        }
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
