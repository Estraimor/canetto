<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$pdo = Conexion::conectar();

$pdo->exec("CREATE TABLE IF NOT EXISTS `oferta` (
    `idoferta` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(200) NOT NULL,
    `descripcion` TEXT,
    `emoji` VARCHAR(10) DEFAULT '🎉',
    `tipo` VARCHAR(20) DEFAULT 'promo',
    `valor` DECIMAL(10,2) NULL,
    `imagen` VARCHAR(255) NULL,
    `activo` TINYINT DEFAULT 1,
    `fecha_inicio` DATE NULL,
    `fecha_fin` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { $pdo->exec("ALTER TABLE oferta ADD COLUMN imagen VARCHAR(255) NULL"); } catch (Throwable $e) {}

try { $pdo->exec("ALTER TABLE oferta ADD COLUMN productos_idproductos INT NULL"); } catch (Throwable $e) {}

$rows = $pdo->query("
    SELECT o.idoferta, o.titulo, o.descripcion, o.emoji, o.tipo, o.valor, o.imagen, o.activo,
           DATE_FORMAT(o.fecha_inicio,'%Y-%m-%d') AS fecha_inicio,
           DATE_FORMAT(o.fecha_fin,'%Y-%m-%d')   AS fecha_fin,
           o.productos_idproductos,
           p.nombre AS prod_nombre
    FROM oferta o
    LEFT JOIN productos p ON p.idproductos = o.productos_idproductos
    ORDER BY o.activo DESC, o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($rows);
