<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
header('Content-Type: application/json');

try {
    $pdo   = Conexion::conectar();
    $desde = preg_replace('/[^0-9-]/', '', $_GET['desde'] ?? date('Y-m-01'));
    $hasta = preg_replace('/[^0-9-]/', '', $_GET['hasta'] ?? date('Y-m-d'));
    $tipo  = $_GET['tipo'] ?? '';

    $where  = "WHERE m.fecha BETWEEN :desde AND :hasta + INTERVAL 1 DAY";
    $params = [':desde' => $desde, ':hasta' => $hasta];
    if ($tipo && in_array($tipo, ['producto','materia_prima','topping'])) {
        $where   .= " AND m.tipo = :tipo";
        $params[':tipo'] = $tipo;
    }

    $rows = $pdo->prepare("
        SELECT m.*,
               CASE m.tipo
                 WHEN 'producto'      THEN (SELECT nombre FROM productos      WHERE idproductos    = m.referencia_id)
                 WHEN 'materia_prima' THEN (SELECT nombre FROM materia_prima  WHERE idmateria_prima= m.referencia_id)
                 WHEN 'topping'       THEN (SELECT nombre FROM toppings        WHERE idtoppings     = m.referencia_id)
               END AS nombre_ref,
               CONCAT(u.nombre,' ',COALESCE(u.apellido,'')) AS usuario_nombre
        FROM mermas m
        LEFT JOIN usuario u ON u.idusuario = m.usuario_id
        $where
        ORDER BY m.fecha DESC
        LIMIT 300
    ");
    $rows->execute($params);
    $mermas = $rows->fetchAll();

    // Totales por tipo
    $totStmt = $pdo->prepare("
        SELECT tipo,
               COUNT(*) AS cantidad_registros,
               SUM(costo_estimado) AS costo_total
        FROM mermas m
        $where
        GROUP BY tipo
    ");
    $totStmt->execute($params);
    $totales = $totStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'mermas'  => $mermas,
        'totales' => $totales,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
