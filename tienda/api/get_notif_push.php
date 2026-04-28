<?php
/**
 * El Service Worker llama a este endpoint al recibir un push vacío.
 * Devuelve el contenido de la última notificación no leída para esa suscripción.
 */
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/web_push.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$epHash = preg_replace('/[^a-f0-9]/', '', $_GET['h'] ?? '');

if (!$epHash) {
    echo json_encode([
        'titulo' => 'Canetto 🍪',
        'cuerpo' => 'Tu pedido fue actualizado',
        'url'    => '/canetto/tienda/mis-pedidos.php',
    ]);
    exit;
}

try {
    $pdo = Conexion::conectar();
    push_ensure_tables($pdo);

    // Buscar usuario por hash del endpoint
    $stmt = $pdo->prepare("SELECT usuario_id FROM push_subscriptions WHERE endpoint_hash = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$epHash]);
    $uid  = (int)$stmt->fetchColumn();

    if (!$uid) {
        echo json_encode(['titulo' => 'Canetto 🍪', 'cuerpo' => 'Tu pedido fue actualizado', 'url' => '/canetto/tienda/mis-pedidos.php']);
        exit;
    }

    // Traer la notificación más reciente no leída
    $stmt2 = $pdo->prepare("
        SELECT id, titulo, cuerpo, url FROM push_notificaciones
        WHERE usuario_id = ? AND leida = 0
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt2->execute([$uid]);
    $notif = $stmt2->fetch(\PDO::FETCH_ASSOC);

    if ($notif) {
        // Marcar como leída
        $pdo->prepare("UPDATE push_notificaciones SET leida=1 WHERE id=?")->execute([$notif['id']]);
        echo json_encode([
            'titulo' => $notif['titulo'],
            'cuerpo' => $notif['cuerpo'],
            'url'    => $notif['url'],
        ]);
    } else {
        echo json_encode([
            'titulo' => 'Canetto 🍪',
            'cuerpo' => 'Tu pedido fue actualizado — tocá para ver el estado',
            'url'    => '/canetto/tienda/mis-pedidos.php',
        ]);
    }
} catch (Throwable $e) {
    echo json_encode(['titulo' => 'Canetto 🍪', 'cuerpo' => 'Tu pedido fue actualizado', 'url' => '/canetto/tienda/mis-pedidos.php']);
}
