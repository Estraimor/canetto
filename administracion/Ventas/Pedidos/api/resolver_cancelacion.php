<?php
/**
 * Aprueba o rechaza una solicitud de cancelación enviada por el repartidor.
 * - Aprobar: cancela el pedido (estado 6), repone stock y libera al repartidor.
 * - Rechazar: limpia la solicitud y el pedido sigue su curso normal.
 */
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

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$id_venta = intval($input['id_venta'] ?? 0);
$accion   = $input['accion'] ?? ''; // 'aprobar' | 'rechazar'

if (!$id_venta || !in_array($accion, ['aprobar', 'rechazar'], true)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

try {
    $pdo = Conexion::conectar();

    foreach ([
        "ALTER TABLE ventas ADD COLUMN cancelacion_solicitada TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE ventas ADD COLUMN cancelacion_motivo VARCHAR(60) NULL",
        "ALTER TABLE ventas ADD COLUMN cancelacion_detalle TEXT NULL",
        "ALTER TABLE ventas ADD COLUMN cancelacion_solicitada_at DATETIME NULL",
        "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN repartidor_pendiente_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN updated_at DATETIME NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    $stmt = $pdo->prepare("
        SELECT v.cancelacion_solicitada, v.cancelacion_motivo, v.estado_venta_idestado_venta,
               v.repartidor_idusuario,
               TRIM(CONCAT(r.nombre,' ',COALESCE(r.apellido,''))) AS repartidor_nombre
        FROM ventas v
        LEFT JOIN usuario r ON r.idusuario = v.repartidor_idusuario
        WHERE v.idventas = ?
    ");
    $stmt->execute([$id_venta]);
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$venta || !$venta['cancelacion_solicitada']) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'No hay una solicitud de cancelación pendiente para este pedido']); exit;
    }

    $repId     = (int)($venta['repartidor_idusuario'] ?? 0);
    $repNombre = trim($venta['repartidor_nombre'] ?? '') ?: 'el repartidor';
    $motivo    = $venta['cancelacion_motivo'] ?? '—';

    require_once __DIR__ . '/../../../../config/web_push.php';

    if ($accion === 'aprobar') {
        $estadoAnteriorId = (int)$venta['estado_venta_idestado_venta'];

        $pdo->prepare("
            UPDATE ventas
            SET estado_venta_idestado_venta = 6,
                repartidor_idusuario = NULL,
                repartidor_pendiente_idusuario = NULL,
                cancelacion_solicitada = 0,
                updated_at = NOW()
            WHERE idventas = ?
        ")->execute([$id_venta]);

        // Reponer stock HECHO si ya se había descontado (2=En preparación, 3=En camino, 7=Listo para retiro)
        if (in_array($estadoAnteriorId, [2, 3, 7], true)) {
            $stmtItems = $pdo->prepare("SELECT productos_idproductos, cantidad FROM detalle_ventas WHERE ventas_idventas = ?");
            $stmtItems->execute([$id_venta]);
            $stmtStock = $pdo->prepare("UPDATE stock_productos SET stock_actual = stock_actual + :c WHERE productos_idproductos = :p AND tipo_stock = 'HECHO'");
            foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item) {
                $stmtStock->execute([':c' => $item['cantidad'], ':p' => $item['productos_idproductos']]);
            }
        }

        audit($pdo, 'editar', 'pedidos',
            "Aprobó la cancelación del pedido #{$id_venta} solicitada por {$repNombre} ({$motivo})"
        );

        // Notificar al cliente
        $stmtUid = $pdo->prepare("SELECT usuario_idusuario FROM ventas WHERE idventas = ?");
        $stmtUid->execute([$id_venta]);
        $clienteUid = (int)$stmtUid->fetchColumn();
        if ($clienteUid) {
            push_enviar_a_usuario($pdo, $clienteUid, $id_venta, 6);
        }

        // Notificar al repartidor
        if ($repId) {
            push_enviar_a_repartidor(
                $pdo, $repId,
                '✅ Cancelación aprobada',
                "El pedido #{$id_venta} fue cancelado. No necesitás continuar con la entrega."
            );
        }

        $resultado = 'aprobado';

    } else {
        $pdo->prepare("
            UPDATE ventas
            SET cancelacion_solicitada = 0,
                cancelacion_motivo = NULL,
                cancelacion_detalle = NULL,
                cancelacion_solicitada_at = NULL
            WHERE idventas = ?
        ")->execute([$id_venta]);

        audit($pdo, 'editar', 'pedidos',
            "Rechazó la solicitud de cancelación del pedido #{$id_venta} de {$repNombre} ({$motivo})"
        );

        // Notificar al repartidor
        if ($repId) {
            push_enviar_a_repartidor(
                $pdo, $repId,
                '❌ Cancelación rechazada',
                "Tu solicitud para el pedido #{$id_venta} fue rechazada. Por favor, continuá con la entrega."
            );
        }

        $resultado = 'rechazado';
    }

    // Marcar la notificación admin asociada como leída
    try {
        $pdo->prepare("
            UPDATE notificaciones_admin SET leida = 1
            WHERE tipo = 'cancelacion_pedido' AND referencia_id = ?
        ")->execute([$id_venta]);
    } catch (Throwable $e) {}

    ob_end_clean();
    echo json_encode(['success' => true, 'accion' => $resultado]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
