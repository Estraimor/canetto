<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No autenticado']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Agregar columna costo_envio si no existe (idempotente)
    try { $pdo->exec("ALTER TABLE ventas ADD COLUMN costo_envio DECIMAL(10,2) NOT NULL DEFAULT 0"); } catch (Throwable $e) {}

    $stmt = $pdo->prepare("
        SELECT
            v.idventas,
            v.total,
            v.fecha,
            v.estado_venta_idestado_venta AS estado_id,
            COALESCE(v.tipo_entrega, 'retiro')  AS tipo_entrega,
            COALESCE(v.costo_envio, 0)          AS costo_envio,
            v.direccion_entrega,
            v.lat_entrega,
            v.lng_entrega,
            mp.nombre  AS metodo_pago,
            u.nombre   AS cliente_nombre,
            u.apellido AS cliente_apellido,
            u.celular  AS cliente_celular
        FROM ventas v
        LEFT JOIN usuario     u  ON u.idusuario     = v.usuario_idusuario
        LEFT JOIN metodo_pago mp ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago
        WHERE v.repartidor_idusuario = :rep
          AND v.estado_venta_idestado_venta = 3
        ORDER BY v.idventas DESC
    ");
    $stmt->execute([':rep' => $repId]);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($ventas) {
        $ids   = array_column($ventas, 'idventas');
        $ph    = implode(',', array_fill(0, count($ids), '?'));
        $stmtD = $pdo->prepare("
            SELECT dv.ventas_idventas, p.nombre, dv.cantidad
            FROM detalle_ventas dv
            JOIN productos p ON p.idproductos = dv.productos_idproductos
            WHERE dv.ventas_idventas IN ($ph)
        ");
        $stmtD->execute($ids);
        $prods = [];
        foreach ($stmtD->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $prods[$d['ventas_idventas']][] = $d['nombre'] . ' ×' . $d['cantidad'];
        }
        foreach ($ventas as &$v) {
            $v['productos']      = implode(', ', $prods[$v['idventas']] ?? []);
            $v['cliente_nombre'] = trim(($v['cliente_nombre'] ?: 'Cliente') . ' ' . ($v['cliente_apellido'] ?? ''));
            $v['cliente_celular'] = (string)($v['cliente_celular'] ?? '');
        }
        unset($v);
    }

    ob_end_clean();
    echo json_encode(
        ['success' => true, 'pedidos' => $ventas ?: []],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
