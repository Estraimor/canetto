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

$input         = json_decode(file_get_contents('php://input'), true);
$id_venta      = intval($input['id_venta']      ?? 0);
$estado        = intval($input['estado']        ?? 0);
$repartidor_id = isset($input['repartidor_id']) && $input['repartidor_id'] ? intval($input['repartidor_id']) : null;
$via_uber      = !empty($input['via_uber']) ? 1 : 0;
$tipo_entrega  = in_array($input['tipo_entrega'] ?? '', ['envio', 'retiro']) ? $input['tipo_entrega'] : null;

if (!$id_venta || $estado < 1 || ($estado > 6 && $estado !== 7)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

try {
    $pdo = Conexion::conectar();

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

    $stmtEstAnterior = $pdo->prepare("SELECT estado_venta_idestado_venta FROM ventas WHERE idventas = ?");
    $stmtEstAnterior->execute([$id_venta]);
    $estadoAnteriorId = (int)$stmtEstAnterior->fetchColumn();

    foreach ([
        "ALTER TABLE ventas ADD COLUMN tipo_entrega VARCHAR(10) NOT NULL DEFAULT 'retiro'",
        "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN via_uber TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE ventas ADD COLUMN updated_at DATETIME NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    $teSet = $tipo_entrega ? ", tipo_entrega=:te" : "";

    if ($estado === 3 && $repartidor_id) {
        $stmt = $pdo->prepare(
            "UPDATE ventas SET estado_venta_idestado_venta=3,
             repartidor_idusuario=:rep, via_uber=0{$teSet}, updated_at=NOW()
             WHERE idventas=:id"
        );
        $params = [':rep' => $repartidor_id, ':id' => $id_venta];
        if ($tipo_entrega) $params[':te'] = $tipo_entrega;
        $stmt->execute($params);
    } elseif ($estado === 3 && $via_uber) {
        $stmt = $pdo->prepare(
            "UPDATE ventas SET estado_venta_idestado_venta=:estado,
             repartidor_idusuario=NULL, via_uber=1{$teSet}, updated_at=NOW()
             WHERE idventas=:id"
        );
        $params = [':estado' => $estado, ':id' => $id_venta];
        if ($tipo_entrega) $params[':te'] = $tipo_entrega;
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE ventas SET estado_venta_idestado_venta=:estado{$teSet}, updated_at=NOW()
             WHERE idventas=:id"
        );
        $params = [':estado' => $estado, ':id' => $id_venta];
        if ($tipo_entrega) $params[':te'] = $tipo_entrega;
        $stmt->execute($params);
    }

    // Pendiente(1) → En Preparación(2): descontar stock HECHO
    if ($estadoAnteriorId === 1 && $estado === 2) {
        $stmtItems = $pdo->prepare("SELECT productos_idproductos, cantidad FROM detalle_ventas WHERE ventas_idventas = ?");
        $stmtItems->execute([$id_venta]);
        $stmtStock = $pdo->prepare("UPDATE stock_productos SET stock_actual = GREATEST(0, stock_actual - :c) WHERE productos_idproductos = :p AND tipo_stock = 'HECHO'");
        foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $stmtStock->execute([':c' => $item['cantidad'], ':p' => $item['productos_idproductos']]);
        }
    }

    // En Preparación(2) → Pendiente(1): reponer stock HECHO
    if ($estadoAnteriorId === 2 && $estado === 1) {
        $stmtItems = $pdo->prepare("SELECT productos_idproductos, cantidad FROM detalle_ventas WHERE ventas_idventas = ?");
        $stmtItems->execute([$id_venta]);
        $stmtStock = $pdo->prepare("UPDATE stock_productos SET stock_actual = stock_actual + :c WHERE productos_idproductos = :p AND tipo_stock = 'HECHO'");
        foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $stmtStock->execute([':c' => $item['cantidad'], ':p' => $item['productos_idproductos']]);
        }
    }

    audit($pdo, 'editar', 'pedidos', "Estado pedido #{$id_venta}: '{$estadoAnterior}' → '{$nombreEstado}'");

    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
