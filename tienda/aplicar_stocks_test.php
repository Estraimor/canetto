<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pdo = Conexion::conectar();

$ids = $pdo->query("SELECT idtoppings FROM toppings WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
$total = count($ids);

foreach ($ids as $i => $id) {
    if ($i < intval($total * 0.4))      { $stock = 20; }   // bueno
    elseif ($i < intval($total * 0.65)) { $stock = 3; }    // bajo
    else                                 { $stock = 0; }    // sin stock

    $ex = $pdo->prepare("SELECT idtoppings_stock FROM toppings_stock WHERE toppings_idtoppings=?");
    $ex->execute([$id]);
    if ($ex->fetch()) {
        $pdo->prepare("UPDATE toppings_stock SET stock_actual=?, stock_minimo=5 WHERE toppings_idtoppings=?")->execute([$stock, $id]);
    } else {
        $pdo->prepare("INSERT INTO toppings_stock (toppings_idtoppings, stock_actual, stock_minimo) VALUES (?,?,5)")->execute([$id, $stock]);
    }
}
echo "OK: ".count($ids)." toppings actualizados.";
