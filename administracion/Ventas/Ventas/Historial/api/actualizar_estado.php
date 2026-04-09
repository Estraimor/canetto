<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../../config/conexion.php';
require_once __DIR__ . '/../../../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

$input         = json_decode(file_get_contents('php://input'), true);
$id_venta      = intval($input['id_venta']      ?? 0);
$estado        = intval($input['estado']        ?? 0);
$repartidor_id = isset($input['repartidor_id']) && $input['repartidor_id'] ? intval($input['repartidor_id']) : null;
$via_uber      = !empty($input['via_uber']) ? 1 : 0;

if (!$id_venta || $estado < 1 || $estado > 4) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Obtener nombre del estado y estado anterior para auditoría
    $stmtEst = $pdo->prepare("SELECT nombre FROM estado_venta WHERE idestado_venta = ?");
    $stmtEst->execute([$estado]);
    $nombreEstado = $stmtEst->fetchColumn() ?: "Estado {$estado}";

    $stmtAnterior = $pdo->prepare("
        SELECT ev.nombre FROM ventas v
        INNER JOIN estado_venta ev ON ev.idestado_venta = v.estado_venta_idestado_venta
        WHERE v.idventas = ?
    ");
    $stmtAnterior->execute([$id_venta]);
    $estadoAnterior = $stmtAnterior->fetchColumn() ?: '?';

    // Obtener estado anterior numérico
    $stmtEstAnterior = $pdo->prepare("SELECT estado_venta_idestado_venta FROM ventas WHERE idventas = ?");
    $stmtEstAnterior->execute([$id_venta]);
    $estadoAnteriorId = (int)$stmtEstAnterior->fetchColumn();

    // Agregar columnas si no existen (idempotente)
    foreach ([
        "ALTER TABLE ventas ADD COLUMN tipo_entrega VARCHAR(10) NOT NULL DEFAULT 'retiro'",
        "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN direccion_entrega TEXT NULL",
        "ALTER TABLE ventas ADD COLUMN lat_entrega DECIMAL(10,8) NULL",
        "ALTER TABLE ventas ADD COLUMN lng_entrega DECIMAL(11,8) NULL",
        "ALTER TABLE ventas ADD COLUMN via_uber TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE ventas ADD COLUMN updated_at DATETIME NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    if ($estado === 3 && $repartidor_id) {
        // Repartidor propio asignado
        $stmt = $pdo->prepare(
            "UPDATE ventas SET estado_venta_idestado_venta = :estado,
             repartidor_idusuario = :rep, via_uber = 0, updated_at = NOW()
             WHERE idventas = :id"
        );
        $stmt->execute([':estado' => $estado, ':rep' => $repartidor_id, ':id' => $id_venta]);
    } elseif ($estado === 3 && $via_uber) {
        // Envío por Uber (sin repartidor propio)
        $stmt = $pdo->prepare(
            "UPDATE ventas SET estado_venta_idestado_venta = :estado,
             repartidor_idusuario = NULL, via_uber = 1, updated_at = NOW()
             WHERE idventas = :id"
        );
        $stmt->execute([':estado' => $estado, ':id' => $id_venta]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE ventas SET estado_venta_idestado_venta = :estado, updated_at = NOW()
             WHERE idventas = :id"
        );
        $stmt->execute([':estado' => $estado, ':id' => $id_venta]);
    }

    // Si pasa de Pendiente (1) a En Preparación (2), descontar stock HECHO
    if ($estadoAnteriorId === 1 && $estado === 2) {
        $stmtItems = $pdo->prepare("
            SELECT productos_idproductos, cantidad
            FROM detalle_ventas
            WHERE ventas_idventas = ?
        ");
        $stmtItems->execute([$id_venta]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $stmtStock = $pdo->prepare("
            UPDATE stock_productos
            SET stock_actual = GREATEST(0, stock_actual - :cantidad)
            WHERE productos_idproductos = :prod_id AND tipo_stock = 'HECHO'
        ");
        foreach ($items as $item) {
            $stmtStock->execute([
                ':cantidad' => $item['cantidad'],
                ':prod_id'  => $item['productos_idproductos'],
            ]);
        }
    }

    audit($pdo, 'editar', 'ventas',
        "Actualizó estado de venta #{$id_venta}: '{$estadoAnterior}' → '{$nombreEstado}'"
    );

    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
