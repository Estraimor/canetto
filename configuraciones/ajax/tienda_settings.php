<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'No autorizado']); exit; }
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
if (empty($data)) { echo json_encode(['ok'=>false,'msg'=>'Sin datos']); exit; }

$allowed = ['min_cookies_pedido','max_cookies_pedido','mensaje_min_pedido','tienda_abierta','tienda_mensaje_cierre'];

try {
    $pdo = Conexion::conectar();
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion_tienda (
        clave VARCHAR(60) PRIMARY KEY,
        valor TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->prepare("INSERT INTO configuracion_tienda (clave, valor) VALUES (?,?)
                            ON DUPLICATE KEY UPDATE valor=VALUES(valor), updated_at=NOW()");

    foreach ($data as $clave => $valor) {
        if (!in_array($clave, $allowed)) continue;
        $stmt->execute([$clave, (string)$valor]);
    }

    audit($pdo, 'editar', 'configuracion_tienda', 'Actualizó configuración: ' . implode(', ', array_keys($data)));
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
