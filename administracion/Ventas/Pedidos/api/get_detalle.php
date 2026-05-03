<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'ID inválido']); exit; }

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT
            v.idventas, v.total, v.fecha,
            v.estado_venta_idestado_venta AS estado_id,
            COALESCE(v.tipo_entrega,'retiro') AS tipo_entrega,
            v.direccion_entrega,
            COALESCE(v.via_uber,0) AS via_uber,
            COALESCE(v.origen,'pos') AS origen,
            u.nombre   AS cliente_nombre,
            u.apellido AS cliente_apellido,
            u.email    AS cliente_email,
            u.celular  AS cliente_telefono,
            mp.nombre  AS metodo_pago,
            CONCAT(rep.nombre,' ',COALESCE(rep.apellido,'')) AS repartidor_nombre,
            rep.celular AS repartidor_celular
        FROM ventas v
        LEFT JOIN usuario u   ON u.idusuario   = v.usuario_idusuario
        LEFT JOIN metodo_pago mp ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago
        LEFT JOIN usuario rep ON rep.idusuario = v.repartidor_idusuario
        WHERE v.idventas = :id LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $venta = $stmt->fetch();

    if (!$venta) { echo json_encode(['error' => 'Pedido no encontrado']); exit; }

    $venta['cliente_nombre']    = $venta['cliente_nombre']    ?: 'Consumidor';
    $venta['cliente_apellido']  = $venta['cliente_apellido']  ?: 'Final';
    $venta['cliente_telefono']  = (string)($venta['cliente_telefono'] ?? '');
    $venta['cliente_email']     = $venta['cliente_email']     ?? '';
    $venta['repartidor_nombre'] = trim($venta['repartidor_nombre'] ?? '') ?: null;
    $venta['tipo_entrega']      = $venta['tipo_entrega'] ?? 'retiro';
    $venta['direccion_entrega'] = $venta['direccion_entrega'] ?? '';

    $stmtDet = $pdo->prepare("
        SELECT p.nombre, p.tipo, dv.cantidad, dv.precio_unitario,
               (SELECT GROUP_CONCAT(p2.nombre, ' x', bp.cantidad ORDER BY p2.nombre SEPARATOR ' · ')
                FROM box_productos bp
                JOIN productos p2 ON p2.idproductos = bp.producto_item
                WHERE bp.producto_box = p.idproductos
               ) AS contenido_box
        FROM detalle_ventas dv
        JOIN productos p ON p.idproductos = dv.productos_idproductos
        WHERE dv.ventas_idventas = :id
    ");
    $stmtDet->execute([':id' => $id]);
    $venta['productos'] = $stmtDet->fetchAll();

    // Toppings: formato flat [{id, nombre, precio}, ...]
    $stmtTop = $pdo->prepare("SELECT COALESCE(toppings_json,'') FROM ventas WHERE idventas=:id LIMIT 1");
    $stmtTop->execute([':id' => $id]);
    $tj = $stmtTop->fetchColumn();
    $toppingsList = [];
    if ($tj) {
        $parsed = json_decode($tj, true);
        if (is_array($parsed)) {
            foreach ($parsed as $t) {
                $nombre = $t['nombre'] ?? '';
                $precio = isset($t['precio']) ? (float)$t['precio'] : 0;
                if ($nombre) $toppingsList[] = ['nombre' => $nombre, 'precio' => $precio];
            }
        }
    }
    $venta['toppings'] = $toppingsList;

    ob_end_clean();
    echo json_encode($venta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
