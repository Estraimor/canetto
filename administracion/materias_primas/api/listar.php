<?php
declare(strict_types=1);

define('APP_BOOT', true);
require_once '../../../config/conexion.php';

header('Content-Type: application/json');

try {

    $pdo = Conexion::conectar();

    $sql = "SELECT 
                mp.idmateria_prima,
                mp.nombre,
                mp.stock_actual,
                mp.stock_minimo,
                mp.activo,
                mp.nota,
                um.abreviatura AS unidad
            FROM materia_prima mp
            INNER JOIN unidad_medida um 
                ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
            ORDER BY mp.nombre ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {

        $estado_key = 'ok';
        $estado_label = 'OK';
        $class = 'status-ok';

        if ((float)$row['stock_actual'] <= 0) {
            $estado_key = 'nostock';
            $estado_label = 'Sin stock';
            $class = 'status-critical';
        }
        elseif ((float)$row['stock_actual'] <= (float)$row['stock_minimo']) {
            $estado_key = 'critical';
            $estado_label = 'Crítico';
            $class = 'status-critical';
        }
        elseif ((float)$row['stock_actual'] <= ((float)$row['stock_minimo'] * 1.3)) {
            $estado_key = 'low';
            $estado_label = 'Bajo';
            $class = 'status-low';
        }

        $row['estado']       = $estado_label;
        $row['estado_key']   = $estado_key;
        $row['estado_html']  = "<span class='{$class}'>{$estado_label}</span>";
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}