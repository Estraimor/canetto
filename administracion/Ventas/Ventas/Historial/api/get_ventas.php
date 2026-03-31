<?php
// Ventas/Historial/api/get_ventas.php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../../config/conexion.php';
header('Content-Type: application/json');

try {
    $pdo    = Conexion::conectar();
    $params = [];
    $where  = ['1=1'];

    // =========================
    // FILTROS
    // =========================
    if (!empty($_GET['estado'])) {
        $where[]  = 'v.estado_venta_idestado_venta = :estado';
        $params[':estado'] = intval($_GET['estado']);
    }

    if (!empty($_GET['fecha'])) {
        $where[]  = 'DATE(v.fecha) = :fecha';
        $params[':fecha'] = $_GET['fecha'];
    }

    $where_sql = implode(' AND ', $where);

    // =========================
    // QUERY PRINCIPAL
    // =========================
    $stmt = $pdo->prepare("
        SELECT 
            v.idventas, 
            v.total, 
            v.fecha,
            v.estado_venta_idestado_venta AS estado_id,

            u.nombre   AS cliente_nombre, 
            u.apellido AS cliente_apellido,
            u.celular  AS cliente_telefono, 
            u.email    AS cliente_email,

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

        WHERE $where_sql
        ORDER BY v.idventas DESC
        LIMIT 200
    ");

    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $ventas = [];
    $ids    = [];

    foreach ($rows as $row) {

        // 🔥 MANEJO CONSUMIDOR FINAL
        $row['cliente_nombre']     = $row['cliente_nombre']     ?: 'Consumidor';
        $row['cliente_apellido']   = $row['cliente_apellido']   ?: 'Final';
        $row['cliente_telefono']   = $row['cliente_telefono']   ?: '';
        $row['cliente_email']      = $row['cliente_email']      ?: '';
        $row['cliente_direccion']  = $row['cliente_direccion']  ?: '';

        $row['productos'] = [];

        $ventas[$row['idventas']] = $row;
        $ids[] = $row['idventas'];
    }

    // =========================
    // PRODUCTOS
    // =========================
    if ($ids) {

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmtDet = $pdo->prepare("
            SELECT 
                dv.ventas_idventas, 
                p.nombre, 
                dv.cantidad, 
                dv.precio_unitario
            FROM detalle_ventas dv
            JOIN productos p 
                ON p.idproductos = dv.productos_idproductos
            WHERE dv.ventas_idventas IN ($placeholders)
        ");

        $stmtDet->execute($ids);

        foreach ($stmtDet->fetchAll() as $d) {

            $ventas[$d['ventas_idventas']]['productos'][] = [
                'nombre'          => $d['nombre'],
                'cantidad'        => $d['cantidad'],
                'precio_unitario' => $d['precio_unitario'],
            ];
        }
    }

    // =========================
    // STATS
    // =========================
    $hoy = date('Y-m-d');

    $stmtStat = $pdo->prepare("
        SELECT
            SUM(estado_venta_idestado_venta = 1) AS pendiente,
            SUM(estado_venta_idestado_venta = 2) AS preparacion,
            SUM(estado_venta_idestado_venta = 3) AS repartidor,
            SUM(estado_venta_idestado_venta = 4) AS entregado,
            COALESCE(SUM(total),0) AS total_hoy
        FROM ventas
        WHERE DATE(created_at) = :hoy
    ");

    $stmtStat->execute([':hoy' => $hoy]);
    $stats = $stmtStat->fetch();

    echo json_encode([
        'ventas' => array_values($ventas),
        'stats'  => $stats
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'error' => $e->getMessage()
    ]);
}