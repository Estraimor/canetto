<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) { echo json_encode(['success' => false, 'message' => 'No autenticado']); exit; }

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT
            v.idventas,
            v.total,
            v.updated_at,
            v.fecha,
            v.direccion_entrega,
            v.lat_entrega,
            v.lng_entrega,
            u.nombre   AS cliente_nombre,
            u.apellido AS cliente_apellido,
            u.celular  AS cliente_celular
        FROM ventas v
        LEFT JOIN usuario u ON u.idusuario = v.usuario_idusuario
        WHERE v.repartidor_idusuario = ?
          AND v.estado_venta_idestado_venta = 4
        ORDER BY v.updated_at DESC
        LIMIT 50
    ");
    $stmt->execute([$repId]);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($ventas) {
        $ids  = array_column($ventas, 'idventas');
        $ph   = implode(',', array_fill(0, count($ids), '?'));
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
        }
        unset($v);
    }

    echo json_encode(['success' => true, 'pedidos' => $ventas ?: []]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
