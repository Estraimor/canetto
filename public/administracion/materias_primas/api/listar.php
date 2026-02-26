<?php
declare(strict_types=1);
define('APP_BOOT', true);

require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

try {

    $pdo = Conexion::conectar();

    $sql = "
        SELECT mp.*, um.abreviatura AS unidad
        FROM materia_prima mp
        LEFT JOIN unidad_medida um 
            ON mp.unidad_medida_idunidad_medida = um.idunidad_medida
        ORDER BY mp.nombre ASC
    ";

    $stmt = $pdo->query($sql);
    $data = [];

    while ($row = $stmt->fetch()) {

        $estado_html = '';

        if ((float)$row['stock_actual'] <= 0) {
            $estado_html = "<span class='status-critical'>Sin stock</span>";
        } elseif ((float)$row['stock_actual'] <= (float)$row['stock_minimo']) {
            $estado_html = "<span class='status-critical'>Crítico</span>";
        } elseif ((float)$row['stock_actual'] <= ((float)$row['stock_minimo'] * 1.3)) {
            $estado_html = "<span class='status-low'>Bajo</span>";
        } else {
            $estado_html = "<span class='status-ok'>OK</span>";
        }

        $row['estado_html'] = $estado_html;
        $data[] = $row;
    }

    echo json_encode($data);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}