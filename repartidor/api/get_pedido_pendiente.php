<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) {
    ob_end_clean();
    echo json_encode(['success' => false, 'pendiente' => null]); exit;
}

try {
    $pdo = Conexion::conectar();

    foreach ([
        "ALTER TABLE ventas ADD COLUMN repartidor_pendiente_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN uber_link VARCHAR(500) NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    $stmt = $pdo->prepare("
        SELECT
            v.idventas,
            v.total,
            v.direccion_entrega,
            v.lat_entrega,
            v.lng_entrega,
            COALESCE(v.costo_envio, 0) AS costo_envio,
            mp.nombre AS metodo_pago,
            u.nombre   AS cliente_nombre,
            u.apellido AS cliente_apellido,
            s.nombre    AS sucursal_nombre,
            s.latitud   AS sucursal_lat,
            s.longitud  AS sucursal_lng
        FROM ventas v
        LEFT JOIN usuario     u  ON u.idusuario     = v.usuario_idusuario
        LEFT JOIN metodo_pago mp ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago
        LEFT JOIN sucursal    s  ON s.idsucursal     = v.sucursal_retiro_idsucursal
        WHERE v.repartidor_pendiente_idusuario = :rep
          AND v.repartidor_idusuario IS NULL
          AND v.estado_venta_idestado_venta = 3
        LIMIT 1
    ");
    $stmt->execute([':rep' => $repId]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pedido) {
        $stmtD = $pdo->prepare("
            SELECT p.nombre, dv.cantidad
            FROM detalle_ventas dv
            JOIN productos p ON p.idproductos = dv.productos_idproductos
            WHERE dv.ventas_idventas = ?
        ");
        $stmtD->execute([$pedido['idventas']]);
        $prods = $stmtD->fetchAll(PDO::FETCH_ASSOC);
        $pedido['productos']      = implode(', ', array_map(fn($p) => $p['nombre'] . ' ×' . $p['cantidad'], $prods));
        $pedido['cliente_nombre'] = trim(($pedido['cliente_nombre'] ?: 'Cliente') . ' ' . ($pedido['cliente_apellido'] ?? ''));
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'pendiente' => $pedido ?: null], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'pendiente' => null, 'error' => $e->getMessage()]);
}
