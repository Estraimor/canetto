<?php
/**
 * API para leer y cambiar el estado de la tienda (abierta / cerrada)
 * GET  → devuelve estado actual
 * POST → cambia estado (requiere sesión admin)
 */
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$pdo = Conexion::conectar();

// Asegurar que la tabla existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion_tienda (
        clave   VARCHAR(60) PRIMARY KEY,
        valor   TEXT        NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $pdo->exec("INSERT IGNORE INTO configuracion_tienda (clave, valor) VALUES ('tienda_abierta','1')");
} catch (Throwable $e) {}

function getEstado(PDO $pdo): array {
    $row = $pdo->query("SELECT valor FROM configuracion_tienda WHERE clave='tienda_abierta'")->fetch();
    $abierta = ($row['valor'] ?? '1') === '1';
    $msg     = $pdo->query("SELECT valor FROM configuracion_tienda WHERE clave='tienda_mensaje_cierre'")->fetch();
    return [
        'abierta'  => $abierta,
        'mensaje'  => $msg['valor'] ?? 'La tienda está temporalmente cerrada. ¡Volvemos pronto!',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Solo admins
    $rolesPermitidos = ['admin','administrador','administracion'];
    if (!in_array(strtolower($_SESSION['rol'] ?? ''), $rolesPermitidos, true)) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'msg'=>'Sin permiso']);
        exit;
    }

    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $body['accion'] ?? '';

    if ($accion === 'toggle') {
        $actual = getEstado($pdo)['abierta'];
        $nuevo  = $actual ? '0' : '1';
        $pdo->prepare("INSERT INTO configuracion_tienda (clave,valor) VALUES ('tienda_abierta',?)
                       ON DUPLICATE KEY UPDATE valor=?, updated_at=NOW()")
            ->execute([$nuevo, $nuevo]);
    }

    if ($accion === 'mensaje' && isset($body['mensaje'])) {
        $msg = trim(substr($body['mensaje'], 0, 300));
        $pdo->prepare("INSERT INTO configuracion_tienda (clave,valor) VALUES ('tienda_mensaje_cierre',?)
                       ON DUPLICATE KEY UPDATE valor=?, updated_at=NOW()")
            ->execute([$msg, $msg]);
    }

    echo json_encode(['ok'=>true] + getEstado($pdo));
    exit;
}

// GET
echo json_encode(['ok'=>true] + getEstado($pdo));
