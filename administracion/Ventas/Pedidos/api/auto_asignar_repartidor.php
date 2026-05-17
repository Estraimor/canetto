<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
require_once __DIR__ . '/../../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$id_venta = intval($input['id_venta'] ?? 0);

if (!$id_venta) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Columnas por si no existen aún
    foreach ([
        "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN via_uber TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE ventas ADD COLUMN updated_at DATETIME NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    // Radio = máximo de todas las zonas de cobertura configuradas (fallback 20 km)
    $radioKm = 20;
    try {
        $r = $pdo->query("SELECT MAX(radio_km) FROM zonas_envio")->fetchColumn();
        if ($r > 0) $radioKm = (float)$r;
    } catch (Throwable $e) {}

    // Sucursal activa con coordenadas
    $sucursal = $pdo->query("
        SELECT idsucursal, nombre, latitud, longitud
        FROM sucursal WHERE activo = 1 AND latitud IS NOT NULL
        ORDER BY idsucursal ASC LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    $candidatos = [];

    if ($sucursal) {
        $lat = (float)$sucursal['latitud'];
        $lng = (float)$sucursal['longitud'];

        // Repartidores activos, con ubicación en los últimos 15 min,
        // dentro del radio y sin pedidos en estado "En reparto"
        $stmt = $pdo->prepare("
            SELECT u.idusuario, u.nombre, u.apellido,
                   (6371 * ACOS(
                       LEAST(1, COS(RADIANS(:lat)) * COS(RADIANS(u.ubicacion_lat)) *
                       COS(RADIANS(u.ubicacion_lng) - RADIANS(:lng)) +
                       SIN(RADIANS(:lat2)) * SIN(RADIANS(u.ubicacion_lat)))
                   )) AS distancia_km
            FROM usuario u
            INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
            INNER JOIN roles r ON r.idroles = ur.roles_idroles
            WHERE r.nombre = 'Repartidor'
              AND u.activo = 1
              AND u.ubicacion_lat IS NOT NULL
              AND u.ubicacion_at >= NOW() - INTERVAL 15 MINUTE
              AND NOT EXISTS (
                  SELECT 1 FROM ventas v2
                  WHERE v2.repartidor_idusuario = u.idusuario
                    AND v2.estado_venta_idestado_venta = 3
              )
            HAVING distancia_km <= :radio
            ORDER BY RAND()
        ");
        $stmt->execute([':lat' => $lat, ':lng' => $lng, ':lat2' => $lat, ':radio' => $radioKm]);
        $candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fallback: cualquier repartidor activo sin pedidos activos (sin filtro de ubicación)
    if (empty($candidatos)) {
        $stmt2 = $pdo->prepare("
            SELECT u.idusuario, u.nombre, u.apellido
            FROM usuario u
            INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
            INNER JOIN roles r ON r.idroles = ur.roles_idroles
            WHERE r.nombre = 'Repartidor'
              AND u.activo = 1
              AND NOT EXISTS (
                  SELECT 1 FROM ventas v2
                  WHERE v2.repartidor_idusuario = u.idusuario
                    AND v2.estado_venta_idestado_venta = 3
              )
            ORDER BY RAND()
        ");
        $stmt2->execute();
        $candidatos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($candidatos)) {
        ob_end_clean();
        echo json_encode([
            'success'        => false,
            'sin_repartidor' => true,
            'message'        => 'No hay repartidores disponibles sin pedidos activos',
        ]); exit;
    }

    $elegido = $candidatos[0]; // ya aleatorizado con ORDER BY RAND()
    $repId   = (int)$elegido['idusuario'];
    $repNombre = trim($elegido['nombre'] . ' ' . ($elegido['apellido'] ?? ''));

    // Asignar y pasar a estado 3 (En reparto)
    $pdo->prepare("
        UPDATE ventas
        SET estado_venta_idestado_venta = 3,
            repartidor_idusuario = :rep,
            via_uber = 0,
            updated_at = NOW()
        WHERE idventas = :id
    ")->execute([':rep' => $repId, ':id' => $id_venta]);

    audit($pdo, 'editar', 'pedidos',
        "Auto-asignación pedido #{$id_venta}: repartidor → {$repNombre}");

    // Notificación push al cliente
    $stmtUid = $pdo->prepare("SELECT usuario_idusuario FROM ventas WHERE idventas = ?");
    $stmtUid->execute([$id_venta]);
    $clienteUid = (int)$stmtUid->fetchColumn();
    if ($clienteUid) {
        require_once __DIR__ . '/../../../../config/web_push.php';
        push_enviar_a_usuario($pdo, $clienteUid, $id_venta, 3);
    }

    ob_end_clean();
    echo json_encode([
        'success'       => true,
        'repartidor'    => $repNombre,
        'repartidor_id' => $repId,
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
