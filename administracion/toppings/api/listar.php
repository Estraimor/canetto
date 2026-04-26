<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }
header('Content-Type: application/json; charset=utf-8');

$pdo = Conexion::conectar();

// Ensure tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS toppings (
    idtoppings INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT NOW()
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS producto_toppings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    productos_idproductos INT NOT NULL,
    toppings_idtoppings INT NOT NULL,
    UNIQUE KEY uq_pt (productos_idproductos, toppings_idtoppings)
)");

echo json_encode($pdo->query("SELECT * FROM toppings ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC));
