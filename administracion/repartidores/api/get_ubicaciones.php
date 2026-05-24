<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$rolesPermitidos = ['admin', 'administrador', 'administracion'];
if (!in_array(strtolower($_SESSION['rol'] ?? ''), $rolesPermitidos, true)) {
    echo json_encode(['ok' => false, 'message' => 'No autorizado']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Agregar columnas si no existen aún
    foreach ([
        "ALTER TABLE usuario ADD COLUMN ubicacion_lat  DECIMAL(10,8) NULL",
        "ALTER TABLE usuario ADD COLUMN ubicacion_lng  DECIMAL(11,8) NULL",
        "ALTER TABLE usuario ADD COLUMN ubicacion_at   DATETIME NULL",
        "ALTER TABLE usuario ADD COLUMN session_at     DATETIME NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    // Repartidores con ubicación enviada en los últimos 10 minutos
    $rows = $pdo->query("
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
        ORDER BY u.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Todos los repartidores activos (para la lista lateral)
    $todos = $pdo->query("
        SELECT u.idusuario, u.nombre, u.apellido,
               u.ubicacion_lat AS lat, u.ubicacion_lng AS lng,
               u.ubicacion_at  AS actualizado_at,
               u.session_at
        FROM usuario u
        INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
        INNER JOIN roles r ON r.idroles = ur.roles_idroles
        WHERE r.nombre = 'Repartidor' AND u.activo = 1
        ORDER BY u.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'activos' => $rows, 'todos' => $todos]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Error de base de datos']);
}
