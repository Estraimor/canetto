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
// IDs de repartidores que ya rechazaron este pedido (para no volver a proponer)
$rechazados = array_map('intval', (array)($input['rechazados'] ?? []));

if (!$id_venta) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'ID inválido']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Columnas por si no existen aún
    foreach ([
        "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN repartidor_pendiente_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN via_uber TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE ventas ADD COLUMN uber_link VARCHAR(500) NULL",
        "ALTER TABLE ventas ADD COLUMN updated_at DATETIME NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    // Máximo de pedidos activos por repartidor (evitar sobrecarga)
    $MAX_PEDIDOS = 5;

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

    // Cláusula de exclusión de rechazados
    $excludeClause = '';
    $excludeParams = [];
    if ($rechazados) {
        $ph = implode(',', array_fill(0, count($rechazados), '?'));
        $excludeClause = "AND u.idusuario NOT IN ($ph)";
        $excludeParams = $rechazados;
    }

    $candidatos = [];

    if ($sucursal) {
        $lat = (float)$sucursal['latitud'];
        $lng = (float)$sucursal['longitud'];

        // Repartidores activos con ubicación reciente, dentro del radio,
        // con menos del máximo de pedidos activos, excluyendo rechazados.
        // Ordenados por: pedidos activos ASC (menos cargados primero), luego distancia ASC.
        $sql = "
            SELECT u.idusuario, u.nombre, u.apellido,
                   (6371 * ACOS(
                       LEAST(1, COS(RADIANS(:lat)) * COS(RADIANS(u.ubicacion_lat)) *
                       COS(RADIANS(u.ubicacion_lng) - RADIANS(:lng)) +
                       SIN(RADIANS(:lat2)) * SIN(RADIANS(u.ubicacion_lat)))
                   )) AS distancia_km,
                   (SELECT COUNT(*) FROM ventas v2
                    WHERE v2.repartidor_idusuario = u.idusuario
                      AND v2.estado_venta_idestado_venta = 3) AS pedidos_activos
            FROM usuario u
            INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
            INNER JOIN roles r ON r.idroles = ur.roles_idroles
            WHERE r.nombre = 'Repartidor'
              AND u.activo = 1
              AND u.ubicacion_lat IS NOT NULL
              AND u.ubicacion_at >= NOW() - INTERVAL 15 MINUTE
              AND u.idusuario NOT IN (
                  SELECT COALESCE(repartidor_pendiente_idusuario, 0) FROM ventas
                  WHERE repartidor_pendiente_idusuario IS NOT NULL
                    AND estado_venta_idestado_venta = 3
                    AND idventas != :id_venta
              )
              $excludeClause
            HAVING distancia_km <= :radio
               AND pedidos_activos < :max_pedidos
            ORDER BY pedidos_activos ASC, distancia_km ASC
        ";
        $params = [
            ':lat'        => $lat,
            ':lng'        => $lng,
            ':lat2'       => $lat,
            ':radio'      => $radioKm,
            ':max_pedidos'=> $MAX_PEDIDOS,
            ':id_venta'   => $id_venta,
        ];
        $stmt = $pdo->prepare($sql);
        // PDO no soporta mezcla named+positional → convertir rechazados a named
        foreach ($rechazados as $i => $rid) {
            $params[":exc{$i}"] = $rid;
        }
        // Re-hacer la cláusula de exclusión con named params
        if ($rechazados) {
            $phNamed = implode(',', array_map(fn($i) => ":exc{$i}", array_keys($rechazados)));
            $excludeClause = "AND u.idusuario NOT IN ($phNamed)";
            $sql = "
                SELECT u.idusuario, u.nombre, u.apellido,
                       (6371 * ACOS(
                           LEAST(1, COS(RADIANS(:lat)) * COS(RADIANS(u.ubicacion_lat)) *
                           COS(RADIANS(u.ubicacion_lng) - RADIANS(:lng)) +
                           SIN(RADIANS(:lat2)) * SIN(RADIANS(u.ubicacion_lat)))
                       )) AS distancia_km,
                       (SELECT COUNT(*) FROM ventas v2
                        WHERE v2.repartidor_idusuario = u.idusuario
                          AND v2.estado_venta_idestado_venta = 3) AS pedidos_activos
                FROM usuario u
                INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
                INNER JOIN roles r ON r.idroles = ur.roles_idroles
                WHERE r.nombre = 'Repartidor'
                  AND u.activo = 1
                  AND u.ubicacion_lat IS NOT NULL
                  AND u.ubicacion_at >= NOW() - INTERVAL 15 MINUTE
                  AND u.idusuario NOT IN (
                      SELECT COALESCE(repartidor_pendiente_idusuario, 0) FROM ventas
                      WHERE repartidor_pendiente_idusuario IS NOT NULL
                        AND estado_venta_idestado_venta = 3
                        AND idventas != :id_venta
                  )
                  $excludeClause
                HAVING distancia_km <= :radio
                   AND pedidos_activos < :max_pedidos
                ORDER BY pedidos_activos ASC, distancia_km ASC
            ";
            $stmt = $pdo->prepare($sql);
        }
        $stmt->execute($params);
        $candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fallback: cualquier repartidor activo sin importar ubicación ni carga
    if (empty($candidatos)) {
        $sql2 = "
            SELECT u.idusuario, u.nombre, u.apellido,
                   (SELECT COUNT(*) FROM ventas v2
                    WHERE v2.repartidor_idusuario = u.idusuario
                      AND v2.estado_venta_idestado_venta = 3) AS pedidos_activos
            FROM usuario u
            INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
            INNER JOIN roles r ON r.idroles = ur.roles_idroles
            WHERE r.nombre = 'Repartidor'
              AND u.activo = 1
              AND u.idusuario NOT IN (
                  SELECT COALESCE(repartidor_pendiente_idusuario, 0) FROM ventas
                  WHERE repartidor_pendiente_idusuario IS NOT NULL
                    AND estado_venta_idestado_venta = 3
                    AND idventas != :id_venta
              )
        ";
        $params2 = [':id_venta' => $id_venta];
        if ($rechazados) {
            $phNamed2 = implode(',', array_map(fn($i) => ":exc{$i}", array_keys($rechazados)));
            $sql2 .= " AND u.idusuario NOT IN ($phNamed2)";
            foreach ($rechazados as $i => $rid) {
                $params2[":exc{$i}"] = $rid;
            }
        }
        $sql2 .= " HAVING pedidos_activos < :max_pedidos ORDER BY pedidos_activos ASC, RAND() LIMIT 10";
        $params2[':max_pedidos'] = $MAX_PEDIDOS;
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute($params2);
        $candidatos = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($candidatos)) {
        ob_end_clean();
        echo json_encode([
            'success'        => false,
            'sin_repartidor' => true,
            'message'        => 'No hay repartidores disponibles',
        ]); exit;
    }

    // Usar el primero como referencia para el campo pendiente (flag de estado)
    $repId     = (int)$candidatos[0]['idusuario'];
    $repNombre = trim($candidatos[0]['nombre'] . ' ' . ($candidatos[0]['apellido'] ?? ''));

    // Marcar pedido como pendiente de aceptación
    $pdo->prepare("
        UPDATE ventas
        SET estado_venta_idestado_venta = 3,
            repartidor_pendiente_idusuario = :rep,
            repartidor_idusuario = NULL,
            via_uber = 0,
            updated_at = NOW()
        WHERE idventas = :id
    ")->execute([':rep' => $repId, ':id' => $id_venta]);

    audit($pdo, 'editar', 'pedidos',
        "Propuesta pedido #{$id_venta} → todos los repartidores disponibles (" . count($candidatos) . ")");

    // Notificación push a TODOS los candidatos disponibles
    require_once __DIR__ . '/../../../../config/web_push.php';
    foreach ($candidatos as $cand) {
        push_enviar_a_repartidor(
            $pdo, (int)$cand['idusuario'],
            '🔔 ¿Aceptás este pedido?',
            "El pedido #{$id_venta} está disponible. ¡El primero en aceptar se lo lleva!"
        );
    }

    ob_end_clean();
    echo json_encode([
        'success'       => true,
        'propuesta'     => true,
        'repartidor'    => 'repartidores',
        'repartidor_id' => $repId,
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
