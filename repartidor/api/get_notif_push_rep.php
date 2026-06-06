<?php
/**
 * Llamado por el Service Worker del repartidor cuando recibe un push vacío.
 * Devuelve el contenido de la notificación pendiente.
 */
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json');

$h = trim($_GET['h'] ?? '');

$fallback = [
    'titulo' => '👋 ¿Seguís activo?',
    'cuerpo' => 'Confirmá que seguís en el turno. Tocá para abrir la app.',
    'url'    => '/',
];

if (!$h) { ob_end_clean(); echo json_encode($fallback); exit; }

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

    // Obtener usuario_id a partir del hash del endpoint
    $uid = $pdo->prepare("SELECT usuario_id FROM push_subscriptions WHERE endpoint_hash = ? AND activo = 1 LIMIT 1");
    $uid->execute([$h]);
    $userId = (int)$uid->fetchColumn();

    if (!$userId) { ob_end_clean(); echo json_encode($fallback); exit; }

    // Notificación más reciente de los últimos 2 minutos
    // (sin marcar como leída para que todos los dispositivos del usuario reciban el mismo contenido)
    $stmt = $pdo->prepare("
        SELECT titulo, cuerpo FROM notif_repartidores
        WHERE usuario_id = ?
          AND created_at >= NOW() - INTERVAL 120 SECOND
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode($row
        ? ['titulo' => $row['titulo'], 'cuerpo' => $row['cuerpo'], 'url' => '/canetto/repartidor/']
        : $fallback
    );

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode($fallback);
}
