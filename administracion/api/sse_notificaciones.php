<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario_id'])) { http_response_code(401); exit; }

// CRÍTICO: liberar la sesión para no bloquear otros requests del mismo browser
session_write_close();

// Headers SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

// Deshabilitar límite de tiempo y output buffering
set_time_limit(30);
if (ob_get_level()) ob_end_clean();

$pdo        = Conexion::conectar();
$lastId     = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;
$maxLoops   = 60;  // máximo 60 iteraciones (~5 min) para evitar zombies
$loop       = 0;

function sendSSE(string $event, $data): void {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// Ping inicial para confirmar conexión
sendSSE('ping', ['ok' => true, 'ts' => time()]);

while (!connection_aborted() && $loop < $maxLoops) {
    $loop++;

    // Notificaciones no leídas
    $stmt = $pdo->prepare("
        SELECT id, tipo, titulo, descripcion, datos_json, link, created_at
        FROM notificaciones_admin
        WHERE leida = 0
        ORDER BY id DESC
        LIMIT 30
    ");
    $stmt->execute();
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total   = count($notifs);
    $maxIdNow = $notifs ? (int)$notifs[0]['id'] : 0;

    // Enviar estado de badge siempre
    sendSSE('badge', ['total' => $total]);

    // Enviar lista completa solo si cambió algo
    if ($maxIdNow > $lastId) {
        sendSSE('notificaciones', ['notificaciones' => $notifs, 'total' => $total]);

        // Toasts solo para pedidos nuevos muy recientes que no vimos antes
        foreach ($notifs as $n) {
            if ((int)$n['id'] > $lastId && $n['tipo'] === 'pedido_nuevo') {
                $age = time() - strtotime($n['created_at']);
                if ($age < 35) {
                    sendSSE('nuevo_pedido', $n);
                }
            }
        }

        $lastId = $maxIdNow;
    }

    // Ping de keepalive cada ciclo
    sendSSE('ping', ['ts' => time()]);

    sleep(5); // poll interno cada 5 segundos
}

// Decirle al cliente que reconecte
echo "retry: 3000\n\n";
if (ob_get_level()) ob_flush();
flush();
