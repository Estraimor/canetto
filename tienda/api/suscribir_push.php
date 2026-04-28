<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/web_push.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tienda_cliente_id'])) {
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']); exit;
}

$uid   = (int)$_SESSION['tienda_cliente_id'];
$input = json_decode(file_get_contents('php://input'), true);

$endpoint = $input['endpoint']                          ?? '';
$p256dh   = $input['keys']['p256dh']                   ?? '';
$authKey  = $input['keys']['auth']                      ?? '';
$activo   = isset($input['activo']) ? (int)$input['activo'] : 1;

if (!$endpoint || !$p256dh || !$authKey) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']); exit;
}

try {
    $pdo  = Conexion::conectar();
    push_ensure_tables($pdo);

    $epHash = hash('sha256', $endpoint);

    if ($activo) {
        // Insertar o actualizar suscripción
        $pdo->prepare("
            INSERT INTO push_subscriptions (usuario_id, endpoint, endpoint_hash, p256dh, auth_key, activo)
            VALUES (?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                usuario_id = VALUES(usuario_id),
                p256dh     = VALUES(p256dh),
                auth_key   = VALUES(auth_key),
                activo     = 1
        ")->execute([$uid, $endpoint, $epHash, $p256dh, $authKey]);
    } else {
        // Desactivar suscripción
        $pdo->prepare("UPDATE push_subscriptions SET activo=0 WHERE endpoint_hash=? AND usuario_id=?")
            ->execute([$epHash, $uid]);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
