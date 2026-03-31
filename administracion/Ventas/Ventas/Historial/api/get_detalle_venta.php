<?php
// Ventas/Historial/api/get_detalle_venta.php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../../config/conexion.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'ID inválido']); 
    exit;
}

try {
    $pdo = Conexion::conectar();

    // =========================
    // DATOS DE LA VENTA
    // =========================
    $stmt = $pdo->prepare("
        SELECT 
            v.idventas, 
            v.total, 
            v.fecha,
            v.estado_venta_idestado_venta AS estado_id,

            u.nombre   AS cliente_nombre, 
            u.apellido AS cliente_apellido,
            u.email    AS cliente_email, 
            u.celular  AS cliente_telefono,

            d.direccion_formateada AS cliente_direccion,

            mp.nombre AS metodo_pago

        FROM ventas v

        LEFT JOIN usuario u 
            ON u.idusuario = v.usuario_idusuario

        LEFT JOIN direccion d 
            ON d.usuario_idusuario = u.idusuario 
            AND d.principal = 1

        LEFT JOIN metodo_pago mp 
            ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago

        WHERE v.idventas = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $venta = $stmt->fetch();

    if (!$venta) {
        echo json_encode(['error' => 'Venta no encontrada']); 
        exit;
    }

    // =========================
    // LIMPIEZA (CONSUMIDOR FINAL)
    // =========================
    $venta['cliente_nombre']    = $venta['cliente_nombre']    ?: 'Consumidor';
    $venta['cliente_apellido']  = $venta['cliente_apellido']  ?: 'Final';
    $venta['cliente_email']     = $venta['cliente_email']     ?: '';
    $venta['cliente_telefono']  = $venta['cliente_telefono']  ?: '';
    $venta['cliente_direccion'] = $venta['cliente_direccion'] ?: '';

    // =========================
    // PRODUCTOS
    // =========================
    $stmtDet = $pdo->prepare("
        SELECT 
            p.nombre, 
            dv.cantidad, 
            dv.precio_unitario
        FROM detalle_ventas dv
        JOIN productos p 
            ON p.idproductos = dv.productos_idproductos
        WHERE dv.ventas_idventas = :id
    ");

    $stmtDet->execute([':id' => $id]);
    $venta['productos'] = $stmtDet->fetchAll();

    // =========================
    // RESPUESTA
    // =========================
    echo json_encode($venta);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'error' => $e->getMessage()
    ]);
}