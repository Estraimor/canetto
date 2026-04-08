<?php
// Ventas/Historial/api/get_detalle_venta.php
ob_start();
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
    // Crear tabla direccion si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS direccion (
        iddireccion INT AUTO_INCREMENT PRIMARY KEY,
        usuario_idusuario INT NOT NULL,
        direccion_formateada VARCHAR(500),
        principal TINYINT(1) DEFAULT 0,
        lat DECIMAL(10,8) NULL,
        lng DECIMAL(11,8) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
            u.email    AS cliente_email,
            u.celular  AS cliente_telefono,

            d.direccion_formateada AS cliente_direccion,

            mp.nombre AS metodo_pago,

            CONCAT(rep.nombre, ' ', COALESCE(rep.apellido,'')) AS repartidor_nombre,
            rep.celular AS repartidor_celular

        FROM ventas v

        LEFT JOIN usuario u
            ON u.idusuario = v.usuario_idusuario

        LEFT JOIN direccion d
            ON d.usuario_idusuario = u.idusuario
            AND d.principal = 1

        LEFT JOIN metodo_pago mp
            ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago

        LEFT JOIN usuario rep
            ON rep.idusuario = v.repartidor_idusuario

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
    $venta['repartidor_nombre'] = trim($venta['repartidor_nombre'] ?? '') ?: null;
    $venta['repartidor_celular']= $venta['repartidor_celular'] ?? null;
    $venta['tipo_entrega']      = $venta['tipo_entrega'] ?? 'retiro';
    $venta['direccion_entrega'] = $venta['direccion_entrega'] ?? '';

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
    ob_end_clean();
    echo json_encode($venta);

} catch (Throwable $e) {

    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}