<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$pdo = Conexion::conectar();

$pdo->exec("CREATE TABLE IF NOT EXISTS `sucursal` (
    `idsucursal` INT(11) NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `direccion` VARCHAR(200) DEFAULT NULL,
    `ciudad` VARCHAR(100) DEFAULT NULL,
    `provincia` VARCHAR(100) DEFAULT NULL,
    `telefono` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idsucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$rows = $pdo->query("SELECT idsucursal, nombre, direccion, ciudad, provincia, telefono, email, latitud, longitud, activo FROM sucursal ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
