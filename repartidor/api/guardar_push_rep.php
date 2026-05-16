<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); echo json_encode(['ok' => false]); exit;
}

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) {
    ob_end_clean(); echo json_encode(['ok' => false, 'msg' => 'No autenticado']); exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$endpoint = trim($input['endpoint'] ?? '');
$p256dh   = trim($input['p256dh']   ?? '');
$auth     = trim($input['auth']     ?? '');

if (!$endpoint || !$p256dh || !$auth) {
    ob_end_clean(); echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']); exit;
}

try {
    $pdo  = Conexion::conectar();
    $hash = hash('sha256', $endpoint);

    // Reusar la tabla push_subscriptions existente (mismos usuarios)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id            INT          AUTO_INCREMENT PRIMARY KEY,
            usuario_id    INT          NOT NULL,
            endpoint      TEXT         NOT NULL,
            endpoint_hash CHAR(64)     NOT NULL,
            p256dh        VARCHAR(512) NOT NULL,
            auth_key      VARCHAR(255) NOT NULL,
            activo        TINYINT(1)   NOT NULL DEFAULT 1,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_ep (endpoint_hash),
            INDEX idx_uid (usuario_id, activo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Upsert: si ya existe el endpoint, actualizar; si no, insertar
    $pdo->prepare("
        INSERT INTO push_subscriptions (usuario_id, endpoint, endpoint_hash, p256dh, auth_key, activo)
        VALUES (:uid, :ep, :hash, :p256, :auth, 1)
        ON DUPLICATE KEY UPDATE
            usuario_id = VALUES(usuario_id),
            p256dh     = VALUES(p256dh),
            auth_key   = VALUES(auth_key),
            activo     = 1
    ")->execute([
        ':uid'  => (int)$repId,
        ':ep'   => $endpoint,
        ':hash' => $hash,
        ':p256' => $p256dh,
        ':auth' => $auth,
    ]);

    ob_end_clean();
    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
