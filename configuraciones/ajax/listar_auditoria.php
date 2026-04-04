<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$pdo = Conexion::conectar();

$pdo->exec("CREATE TABLE IF NOT EXISTS `auditoria` (
    `idauditoria` INT(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` INT(11) DEFAULT NULL,
    `usuario_nombre` VARCHAR(100) DEFAULT NULL,
    `accion` VARCHAR(100) NOT NULL,
    `modulo` VARCHAR(50) DEFAULT NULL,
    `descripcion` TEXT DEFAULT NULL,
    `ip` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idauditoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$rows = $pdo->query(
    "SELECT idauditoria, usuario_id, usuario_nombre, accion, modulo, descripcion, ip,
     DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
     FROM auditoria ORDER BY created_at DESC LIMIT 2000"
)->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
