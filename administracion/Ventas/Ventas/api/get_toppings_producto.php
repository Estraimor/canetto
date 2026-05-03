<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

$pid = intval($_GET['producto_id'] ?? 0);
if (!$pid) { echo json_encode([]); exit; }

try {
    $pdo = Conexion::conectar();

    // Toppings asignados al producto con su stock disponible
    $stmt = $pdo->prepare("
        SELECT t.idtoppings, t.nombre, t.precio,
               COALESCE(ts.stock_actual, 0) AS stock
        FROM producto_toppings pt
        JOIN toppings t ON t.idtoppings = pt.toppings_idtoppings
        LEFT JOIN (
            SELECT toppings_idtoppings, SUM(stock_actual) AS stock_actual
            FROM toppings_stock
            GROUP BY toppings_idtoppings
        ) ts ON ts.toppings_idtoppings = t.idtoppings
        WHERE pt.productos_idproductos = :pid
          AND t.activo = 1
        ORDER BY t.nombre ASC
    ");
    $stmt->execute([':pid' => $pid]);
    $rows = $stmt->fetchAll();

    // Si no hay tabla toppings_stock, devolver sin stock
    if (empty($rows)) {
        $stmt2 = $pdo->prepare("
            SELECT t.idtoppings, t.nombre, t.precio, 99 AS stock
            FROM producto_toppings pt
            JOIN toppings t ON t.idtoppings = pt.toppings_idtoppings
            WHERE pt.productos_idproductos = :pid AND t.activo = 1
            ORDER BY t.nombre ASC
        ");
        $stmt2->execute([':pid' => $pid]);
        $rows = $stmt2->fetchAll();
    }

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    // Fallback sin tabla toppings_stock
    try {
        $stmt = $pdo->prepare("
            SELECT t.idtoppings, t.nombre, t.precio, 99 AS stock
            FROM producto_toppings pt
            JOIN toppings t ON t.idtoppings = pt.toppings_idtoppings
            WHERE pt.productos_idproductos = :pid AND t.activo = 1
            ORDER BY t.nombre ASC
        ");
        $stmt->execute([':pid' => $pid]);
        echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e2) {
        echo json_encode([]);
    }
}
