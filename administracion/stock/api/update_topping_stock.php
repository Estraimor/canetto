<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

header('Content-Type: application/json');

$pdo  = Conexion::conectar();
$data = json_decode(file_get_contents("php://input"), true);

$id       = (int)($data['id']       ?? 0);
$stock    = (float)($data['stock']   ?? 0);
$minimo   = (float)($data['minimo']  ?? 0);

if ($id <= 0) { echo json_encode(["ok" => false, "error" => "ID inválido"]); exit; }

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS toppings_stock (
        idtoppings_stock INT AUTO_INCREMENT PRIMARY KEY,
        toppings_idtoppings INT NOT NULL,
        stock_actual DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
        UNIQUE KEY uq_tp (toppings_idtoppings)
    )");

    $stmtNom = $pdo->prepare("SELECT nombre FROM toppings WHERE idtoppings = ?");
    $stmtNom->execute([$id]);
    $nombre = $stmtNom->fetchColumn() ?: "ID {$id}";

    $pdo->prepare("
        INSERT INTO toppings_stock (toppings_idtoppings, stock_actual, stock_minimo)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE stock_actual = VALUES(stock_actual), stock_minimo = VALUES(stock_minimo), updated_at = NOW()
    ")->execute([$id, $stock, $minimo]);

    audit($pdo, 'editar', 'stock',
        "Ajuste stock topping: {$nombre} | Stock: {$stock} (mín: {$minimo})"
    );

    echo json_encode(["ok" => true]);

} catch (Exception $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()]);
}
