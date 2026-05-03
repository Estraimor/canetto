<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    // Crear estado Cancelado si no existe
    $pdo->exec("INSERT IGNORE INTO estado_venta (idestado_venta, nombre) VALUES (6, 'Cancelado')");

    // Agregar columnas si no existen
    foreach ([
        "ALTER TABLE ventas ADD COLUMN tipo_entrega VARCHAR(10) NOT NULL DEFAULT 'retiro'",
        "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN direccion_entrega TEXT NULL",
        "ALTER TABLE ventas ADD COLUMN lat_entrega DECIMAL(10,8) NULL",
        "ALTER TABLE ventas ADD COLUMN lng_entrega DECIMAL(11,8) NULL",
        "ALTER TABLE ventas ADD COLUMN via_uber TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE ventas ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT 'pos'",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    $params = [];
    // Crear estado 7 si no existe
    $pdo->exec("INSERT IGNORE INTO estado_venta (idestado_venta, nombre) VALUES (7, 'Listo para retiro')");

    $where  = ['v.estado_venta_idestado_venta IN (1,2,3,5,7)'];

    if (!empty($_GET['estado'])) {
        $where  = ['v.estado_venta_idestado_venta = :estado'];
        $params[':estado'] = intval($_GET['estado']);
    }
    if (!empty($_GET['fecha'])) {
        $where[] = 'DATE(v.fecha) = :fecha';
        $params[':fecha'] = $_GET['fecha'];
    }
    if (!empty($_GET['origen'])) {
        $where[] = 'v.origen = :origen';
        $params[':origen'] = $_GET['origen'];
    }

    $where_sql = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT
            v.idventas,
            v.total,
            v.fecha,
            v.estado_venta_idestado_venta AS estado_id,
            COALESCE(v.tipo_entrega, 'retiro') AS tipo_entrega,
            v.direccion_entrega,
            COALESCE(v.via_uber, 0) AS via_uber,
            COALESCE(v.origen, 'pos') AS origen,
            COALESCE(v.toppings_json, '') AS toppings_json,

            u.nombre   AS cliente_nombre,
            u.apellido AS cliente_apellido,
            u.celular  AS cliente_telefono,
            u.email    AS cliente_email,

            mp.nombre AS metodo_pago,

            CONCAT(r.nombre, ' ', COALESCE(r.apellido,'')) AS repartidor_nombre,
            r.idusuario AS repartidor_idusuario

        FROM ventas v
        LEFT JOIN usuario u  ON u.idusuario  = v.usuario_idusuario
        LEFT JOIN metodo_pago mp ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago
        LEFT JOIN usuario r  ON r.idusuario  = v.repartidor_idusuario
        WHERE $where_sql
        ORDER BY v.idventas DESC
        LIMIT 300
    ");

    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $pedidos = [];
    $ids     = [];

    foreach ($rows as $row) {
        $row['cliente_nombre']    = $row['cliente_nombre']    ?: 'Consumidor';
        $row['cliente_apellido']  = $row['cliente_apellido']  ?: 'Final';
        $row['cliente_telefono']  = (string)($row['cliente_telefono'] ?? '');
        $row['cliente_email']     = $row['cliente_email']     ?? '';
        $row['repartidor_nombre'] = trim($row['repartidor_nombre'] ?? '') ?: null;
        $row['tipo_entrega']      = $row['tipo_entrega'] ?? 'retiro';
        $row['direccion_entrega'] = $row['direccion_entrega'] ?? '';
        $row['productos'] = [];
        $row['toppings']  = [];
        if (!empty($row['toppings_json'] ?? '')) {
            $tj = json_decode($row['toppings_json'], true);
            if (is_array($tj)) $row['toppings'] = $tj;
        }
        $pedidos[$row['idventas']] = $row;
        $ids[] = $row['idventas'];
    }

    if ($ids) {
        $ph      = implode(',', array_fill(0, count($ids), '?'));
        $stmtDet = $pdo->prepare("
            SELECT dv.ventas_idventas, p.nombre, dv.cantidad, dv.precio_unitario
            FROM detalle_ventas dv
            JOIN productos p ON p.idproductos = dv.productos_idproductos
            WHERE dv.ventas_idventas IN ($ph)
        ");
        $stmtDet->execute($ids);
        foreach ($stmtDet->fetchAll() as $d) {
            $pedidos[$d['ventas_idventas']]['productos'][] = [
                'nombre'          => $d['nombre'],
                'cantidad'        => $d['cantidad'],
                'precio_unitario' => $d['precio_unitario'],
            ];
        }
    }

    $hoy = date('Y-m-d');
    $stats = $pdo->prepare("
        SELECT
            SUM(estado_venta_idestado_venta = 1) AS pendiente,
            SUM(estado_venta_idestado_venta = 2) AS preparacion,
            SUM(estado_venta_idestado_venta = 3) AS repartidor,
            SUM(estado_venta_idestado_venta = 5) AS pend_pago,
            SUM(estado_venta_idestado_venta = 7) AS listo_retiro,
            COALESCE(SUM(total),0) AS total_hoy
        FROM ventas
        WHERE DATE(fecha) = :hoy AND estado_venta_idestado_venta IN (1,2,3,5,7)
    ");
    $stats->execute([':hoy' => $hoy]);
    $statsRow = $stats->fetch();

    $payload = json_encode(
        ['pedidos' => array_values($pedidos), 'stats' => $statsRow],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    ob_end_clean();
    echo $payload;

} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
