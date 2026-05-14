<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$rolesPermitidos = ['admin', 'administrador', 'administracion'];
if (!in_array(strtolower($_SESSION['rol'] ?? ''), $rolesPermitidos, true)) {
    http_response_code(403); exit;
}

// Liberar el lock de sesión inmediatamente — sin esto el SSE bloquea
// cualquier otra request PHP del mismo usuario mientras corre.
session_write_close();

set_time_limit(30);
ignore_user_abort(false);
$startTime = time();
$maxLoops  = 12; // 12 × 2s = 24s máximo, luego el cliente reconecta
$loop      = 0;

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

// Vaciar cualquier buffer previo
while (ob_get_level()) ob_end_clean();

$pdo       = Conexion::conectar();
$lastHash  = '';

$sqlActivos = "
    SELECT u.idusuario, u.nombre, u.apellido,
           u.ubicacion_lat AS lat, u.ubicacion_lng AS lng,
           u.ubicacion_at  AS actualizado_at
    FROM usuario u
    INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
    INNER JOIN roles r ON r.idroles = ur.roles_idroles
    WHERE r.nombre = 'Repartidor'
      AND u.activo = 1
      AND u.ubicacion_lat IS NOT NULL
      AND u.ubicacion_at >= NOW() - INTERVAL 10 MINUTE
    ORDER BY u.nombre";

$sqlTodos = "
    SELECT u.idusuario, u.nombre, u.apellido,
           u.ubicacion_lat AS lat, u.ubicacion_lng AS lng,
           u.ubicacion_at  AS actualizado_at
    FROM usuario u
    INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
    INNER JOIN roles r ON r.idroles = ur.roles_idroles
    WHERE r.nombre = 'Repartidor' AND u.activo = 1
    ORDER BY u.nombre";

while (!connection_aborted() && (time() - $startTime) < 25 && $loop < $maxLoops) {
    $loop++;
    try {
        $activos = $pdo->query($sqlActivos)->fetchAll(PDO::FETCH_ASSOC);
        $todos   = $pdo->query($sqlTodos)->fetchAll(PDO::FETCH_ASSOC);
        $payload = json_encode(['ok' => true, 'activos' => $activos, 'todos' => $todos]);
        $hash    = md5($payload);

        if ($hash !== $lastHash) {
            echo "event: ubicaciones\n";
            echo "data: {$payload}\n\n";
            $lastHash = $hash;
            ob_flush();
            flush();
        } else {
            // Heartbeat para mantener la conexión viva
            echo ": ping\n\n";
            ob_flush();
            flush();
        }
    } catch (Throwable $e) {
        echo "event: error\ndata: {}\n\n";
        ob_flush();
        flush();
    }

    sleep(2);
}
