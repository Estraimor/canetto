<?php
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();
    $pdo->exec("CREATE TABLE IF NOT EXISTS cupones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL UNIQUE,
        descripcion VARCHAR(200) NULL,
        tipo ENUM('porcentaje','fijo','envio_gratis') NOT NULL DEFAULT 'porcentaje',
        valor DECIMAL(10,2) NOT NULL DEFAULT 0,
        min_pedido DECIMAL(10,2) NOT NULL DEFAULT 0,
        max_usos INT NULL,
        usos_actuales INT NOT NULL DEFAULT 0,
        un_uso_por_usuario TINYINT(1) NOT NULL DEFAULT 1,
        fecha_inicio DATE NULL,
        fecha_fin DATE NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT NOW(),
        updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
    )");
    try { $pdo->exec("ALTER TABLE cupones MODIFY COLUMN tipo ENUM('porcentaje','fijo','envio_gratis') NOT NULL DEFAULT 'porcentaje'"); } catch (Throwable $e) {}
    $pdo->exec("CREATE TABLE IF NOT EXISTS cupones_usos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cupon_id INT NOT NULL,
        usuario_id INT NULL,
        venta_id INT NULL,
        usado_at DATETIME DEFAULT NOW()
    )");

    $cupones = $pdo->query("
        SELECT c.*,
               (SELECT COUNT(*) FROM cupones_usos cu WHERE cu.cupon_id = c.id) AS usos_reales,
               CASE
                   WHEN c.activo = 0 THEN 'inactivo'
                   WHEN c.fecha_inicio IS NOT NULL AND NOW() < CONCAT(c.fecha_inicio, ' 00:00:00') THEN 'pendiente'
                   WHEN c.fecha_fin   IS NOT NULL AND NOW() > CONCAT(c.fecha_fin,   ' 23:59:59') THEN 'vencido'
                   WHEN c.max_usos IS NOT NULL AND c.usos_actuales >= c.max_usos THEN 'agotado'
                   ELSE 'activo'
               END AS estado_real
        FROM cupones c
        ORDER BY c.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($cupones, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
