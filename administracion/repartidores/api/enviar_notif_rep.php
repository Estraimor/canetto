<?php
/**
 * Envía una push notification a un repartidor desde la administración.
 */
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/web_push.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$rolesPermitidos = ['admin', 'administrador', 'administracion'];
if (!in_array(strtolower($_SESSION['rol'] ?? ''), $rolesPermitidos, true)) {
    ob_end_clean(); echo json_encode(['ok' => false, 'msg' => 'No autorizado']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); echo json_encode(['ok' => false, 'msg' => 'Método no permitido']); exit;
}

$input      = json_decode(file_get_contents('php://input'), true);
$repId      = intval($input['repartidor_id'] ?? 0);
$titulo     = trim($input['titulo']          ?? '');
$cuerpo     = trim($input['cuerpo']          ?? '');

if (!$repId || !$titulo || !$cuerpo) {
    ob_end_clean(); echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Crear tabla si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notif_repartidores (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            titulo     VARCHAR(255) NOT NULL,
            cuerpo     TEXT NOT NULL,
            leida      TINYINT(1)  NOT NULL DEFAULT 0,
            created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uid_leida (usuario_id, leida)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Guardar la notificación para que el SW la consuma
    $pdo->prepare("
        INSERT INTO notif_repartidores (usuario_id, titulo, cuerpo)
        VALUES (?, ?, ?)
    ")->execute([$repId, $titulo, $cuerpo]);

    // Obtener suscripciones activas del repartidor
    $subs = $pdo->prepare("
        SELECT endpoint, endpoint_hash FROM push_subscriptions
        WHERE usuario_id = ? AND activo = 1
    ");
    $subs->execute([$repId]);
    $rows = $subs->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'msg' => 'Este repartidor no tiene notificaciones activadas en su celular']);
        exit;
    }

    $privPem = PUSH_VAPID_PRIVATE_PEM;
    $pubKey  = PUSH_VAPID_PUBLIC;
    $subject = PUSH_SUBJECT;
    $sent = 0;

    foreach ($rows as $sub) {
        try {
            $jwt = push_vapid_jwt($sub['endpoint'], $subject, $privPem);
            $ch  = curl_init($sub['endpoint']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: vapid t=' . $jwt . ',k=' . $pubKey,
                    'TTL: 3600',
                    'Content-Length: 0',
                ],
                CURLOPT_POSTFIELDS => '',
                CURLOPT_TIMEOUT    => 6,
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code === 410) {
                $pdo->prepare("UPDATE push_subscriptions SET activo = 0 WHERE endpoint_hash = ?")
                    ->execute([$sub['endpoint_hash']]);
            } else {
                $sent++;
            }
            curl_close($ch);
        } catch (Throwable $e) {}
    }

    ob_end_clean();
    echo json_encode(['ok' => true, 'enviadas' => $sent]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
