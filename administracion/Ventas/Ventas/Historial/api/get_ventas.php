<?php
// Ventas/Historial/api/get_ventas.php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../../config/conexion.php';
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    // Crear tabla direccion si no existe (fix del bug de JOIN fallido)
    $pdo->exec("CREATE TABLE IF NOT EXISTS direccion (
        iddireccion INT AUTO_INCREMENT PRIMARY KEY,
        usuario_idusuario INT NOT NULL,
        direccion_formateada VARCHAR(500),
        principal TINYINT(1) DEFAULT 0,
        lat DECIMAL(10,8) NULL,
        lng DECIMAL(11,8) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Agregar columnas de repartidor/entrega si no existen
    foreach ([
        "ALTER TABLE ventas ADD COLUMN tipo_entrega VARCHAR(10) NOT NULL DEFAULT 'retiro'",
        "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN direccion_entrega TEXT NULL",
        "ALTER TABLE ventas ADD COLUMN lat_entrega DECIMAL(10,8) NULL",
        "ALTER TABLE ventas ADD COLUMN lng_entrega DECIMAL(11,8) NULL",
        "ALTER TABLE ventas ADD COLUMN via_uber TINYINT(1) NOT NULL DEFAULT 0",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

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
            COALESCE(v.tipo_entrega, 'retiro') AS tipo_entrega,
            v.direccion_entrega,
            COALESCE(v.via_uber, 0) AS via_uber,

            u.nombre   AS cliente_nombre,
            u.apellido AS cliente_apellido,
            u.celular  AS cliente_telefono,
            u.email    AS cliente_email,

            d.direccion_formateada AS cliente_direccion,

            mp.nombre AS metodo_pago,

            CONCAT(r.nombre, ' ', COALESCE(r.apellido,'')) AS repartidor_nombre,
            r.idusuario AS repartidor_idusuario

        FROM ventas v

        LEFT JOIN usuario u
            ON u.idusuario = v.usuario_idusuario

        LEFT JOIN direccion d
            ON d.usuario_idusuario = u.idusuario
            AND d.principal = 1

        LEFT JOIN metodo_pago mp
            ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago

        LEFT JOIN usuario r
            ON r.idusuario = v.repartidor_idusuario

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
        $row['cliente_nombre']     = $row['cliente_nombre']    ?: 'Consumidor';
        $row['cliente_apellido']   = $row['cliente_apellido']  ?: 'Final';
        $row['cliente_telefono']   = $row['cliente_telefono']  ?: '';
        $row['cliente_email']      = $row['cliente_email']     ?: '';
        $row['cliente_direccion']  = $row['cliente_direccion'] ?: '';
        $row['repartidor_nombre']  = trim($row['repartidor_nombre'] ?? '') ?: null;
        $row['tipo_entrega']       = $row['tipo_entrega'] ?? 'retiro';
        $row['direccion_entrega']  = $row['direccion_entrega'] ?? '';

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

    ob_end_clean();
    echo json_encode([
        'ventas' => array_values($ventas),
        'stats'  => $stats
    ]);

} catch (Throwable $e) {

    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}