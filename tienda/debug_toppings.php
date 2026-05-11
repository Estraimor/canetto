<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pdo = Conexion::conectar();
$rows = $pdo->query("
    SELECT t.idtoppings, t.nombre, t.precio,
           COALESCE(ts.stock_actual, -1) AS stock,
           COALESCE(ts.stock_minimo, 0)  AS stock_minimo
    FROM toppings t
    LEFT JOIN toppings_stock ts ON ts.toppings_idtoppings = t.idtoppings
    WHERE t.activo = 1
    ORDER BY t.nombre
")->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
