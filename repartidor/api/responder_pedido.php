<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) {
    ob_end_clean(); echo json_encode(['success' => false, 'message' => 'No autenticado']); exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
$idVenta = intval($input['id_venta'] ?? 0);
$accion  = $input['accion'] ?? ''; // 'aceptar' | 'rechazar'

if (!$idVenta || !in_array($accion, ['aceptar', 'rechazar'])) {
    ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Verificar que el pedido sigue pendiente (sin repartidor asignado aún)
    $stmt = $pdo->prepare("
        SELECT idventas FROM ventas
        WHERE idventas = :id
          AND repartidor_idusuario IS NULL
          AND repartidor_pendiente_idusuario IS NOT NULL
          AND estado_venta_idestado_venta = 3
    ");
    $stmt->execute([':id' => $idVenta]);
    if (!$stmt->fetch()) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Propuesta no válida o ya fue respondida']); exit;
    }

    if ($accion === 'aceptar') {
        // Cualquier repartidor puede aceptar — se asigna a quien acepta primero
        $upd = $pdo->prepare("
            UPDATE ventas
            SET repartidor_idusuario = :rep,
                repartidor_pendiente_idusuario = NULL,
                updated_at = NOW()
            WHERE idventas = :id
              AND repartidor_idusuario IS NULL
        ");
        $upd->execute([':rep' => $repId, ':id' => $idVenta]);

        if ($upd->rowCount() === 0) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'El pedido ya fue tomado por otro repartidor']); exit;
        }

        audit($pdo, 'editar', 'pedidos', "Repartidor #{$repId} aceptó pedido #{$idVenta}");

        // Notificar al cliente que su pedido está en camino
        $stmtUid = $pdo->prepare("SELECT usuario_idusuario FROM ventas WHERE idventas = ?");
        $stmtUid->execute([$idVenta]);
        $clienteUid = (int)$stmtUid->fetchColumn();
        if ($clienteUid) {
            require_once __DIR__ . '/../../config/web_push.php';
            push_enviar_a_usuario($pdo, $clienteUid, $idVenta, 3);
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'accion' => 'aceptado']);

    } else {
        // rechazar: el pedido sigue visible a los demás repartidores en simultáneo.
        // Solo actualizamos updated_at para que el orden del poll lo refleje.
        $upd = $pdo->prepare("
            UPDATE ventas
            SET updated_at = NOW()
            WHERE idventas = :id
              AND repartidor_idusuario IS NULL
        ");
        $upd->execute([':id' => $idVenta]);

        audit($pdo, 'editar', 'pedidos', "Repartidor #{$repId} rechazó pedido #{$idVenta}");

        ob_end_clean();
        echo json_encode(['success' => true, 'accion' => 'rechazado']);
    }

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
