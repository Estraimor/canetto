<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$pdo  = Conexion::conectar();
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$modo = $body['modo'] ?? null; // 'aleatorio' | 'manual'
$ids  = $body['ids']  ?? [];   // array de idproductos en orden

// Agregar columna orden si no existe
try { $pdo->exec("ALTER TABLE productos ADD COLUMN orden INT NULL DEFAULT NULL"); } catch (Throwable $e) {}

// Crear tabla config si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS configuracion_tienda (
    clave VARCHAR(60) PRIMARY KEY,
    valor TEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Guardar modo
if ($modo === 'aleatorio' || $modo === 'manual') {
    $pdo->prepare("INSERT INTO configuracion_tienda (clave, valor) VALUES ('orden_productos', ?)
                   ON DUPLICATE KEY UPDATE valor=VALUES(valor), updated_at=NOW()")
        ->execute([$modo]);
}

// Guardar orden manual
if ($modo === 'manual' && !empty($ids)) {
    $stmt = $pdo->prepare("UPDATE productos SET orden = ? WHERE idproductos = ?");
    foreach ($ids as $pos => $idProducto) {
        $stmt->execute([$pos + 1, (int)$idProducto]);
    }
}

// Limpiar orden si se cambia a aleatorio
if ($modo === 'aleatorio') {
    $pdo->exec("UPDATE productos SET orden = NULL WHERE tipo = 'producto'");
}

echo json_encode(['ok' => true]);
