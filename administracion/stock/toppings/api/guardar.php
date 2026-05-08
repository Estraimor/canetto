<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
require_once __DIR__ . '/../../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$pdo  = Conexion::conectar();
$data = json_decode(file_get_contents('php://input'), true);

$id       = (int)($data['id']       ?? 0);
$nombre   = trim($data['nombre']    ?? '');
$precio   = (float)($data['precio'] ?? 0);
$activo   = (int)($data['activo']   ?? 1);
$stock    = (float)($data['stock']  ?? 0);
$minimo   = (float)($data['minimo'] ?? 0);

if (!$nombre) { echo json_encode(['ok' => false, 'msg' => 'El nombre es obligatorio']); exit; }

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS toppings (
        idtoppings INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        precio DECIMAL(10,2) NOT NULL DEFAULT 0,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT NOW()
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS toppings_stock (
        idtoppings_stock INT AUTO_INCREMENT PRIMARY KEY,
        toppings_idtoppings INT NOT NULL,
        stock_actual DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uq_tp (toppings_idtoppings)
    )");

    if ($id > 0) {
        $pdo->prepare("UPDATE toppings SET nombre=?, precio=?, activo=? WHERE idtoppings=?")
            ->execute([$nombre, $precio, $activo, $id]);
        $accion = 'editar';
    } else {
        $pdo->prepare("INSERT INTO toppings (nombre, precio, activo) VALUES (?,?,?)")
            ->execute([$nombre, $precio, $activo]);
        $id = (int)$pdo->lastInsertId();
        $accion = 'crear';
    }

    $pdo->prepare("
        INSERT INTO toppings_stock (toppings_idtoppings, stock_actual, stock_minimo)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE stock_actual=VALUES(stock_actual), stock_minimo=VALUES(stock_minimo), updated_at=NOW()
    ")->execute([$id, $stock, $minimo]);

    audit($pdo, $accion, 'toppings', "{$accion} topping: {$nombre} | Stock: {$stock} (mín: {$minimo})");

    echo json_encode(['ok' => true, 'id' => $id]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
