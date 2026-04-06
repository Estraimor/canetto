<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo    = Conexion::conectar();
    $rango  = $_GET['rango'] ?? '30'; // días
    $rango  = in_array($rango, ['7','30','90','365']) ? (int)$rango : 30;

    // ── KPIs ─────────────────────────────────────────────────
    $kpis = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total END), 0)                             AS ventas_hoy,
            COUNT(CASE WHEN DATE(fecha) = CURDATE() THEN 1 END)                                            AS pedidos_hoy,
            COALESCE(SUM(CASE WHEN YEARWEEK(fecha,1)=YEARWEEK(CURDATE(),1) THEN total END), 0)             AS ventas_semana,
            COUNT(CASE WHEN YEARWEEK(fecha,1)=YEARWEEK(CURDATE(),1) THEN 1 END)                            AS pedidos_semana,
            COALESCE(SUM(CASE WHEN YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE()) THEN total END), 0) AS ventas_mes,
            COUNT(CASE WHEN YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE()) THEN 1 END)      AS pedidos_mes,
            COALESCE(SUM(total), 0)                                                                         AS ventas_total
        FROM ventas
        WHERE estado_venta_idestado_venta = 4
    ")->fetch();

    // ── Costos de compra ──────────────────────────────────────
    // Usa costo * cantidad_original (unidades de compra) cuando está disponible,
    // si no usa costo * cantidad (unidad base). Esto evita inflación por conversión de unidades.
    $costos = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE()
                THEN costo * COALESCE(cantidad_original, cantidad) END), 0)                                      AS costo_hoy,
            COALESCE(SUM(CASE WHEN YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)
                THEN costo * COALESCE(cantidad_original, cantidad) END), 0)                                      AS costo_semana,
            COALESCE(SUM(CASE WHEN YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())
                THEN costo * COALESCE(cantidad_original, cantidad) END), 0)                                      AS costo_mes,
            COALESCE(SUM(costo * COALESCE(cantidad_original, cantidad)), 0)                                      AS costo_total
        FROM compra_materia_prima
        WHERE estado = 'activa'
    ")->fetch();

    // ── Ventas diarias (rango) ────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT DATE(fecha) AS dia,
               COALESCE(SUM(total),0) AS ingresos,
               COUNT(*) AS cantidad
        FROM ventas
        WHERE estado_venta_idestado_venta = 4
          AND fecha >= CURDATE() - INTERVAL :r DAY
        GROUP BY DATE(fecha)
        ORDER BY dia ASC
    ");
    $stmt->execute([':r' => $rango]);
    $ventasDiarias = $stmt->fetchAll();

    // ── Costos diarios (rango) ────────────────────────────────
    $stmtC = $pdo->prepare("
        SELECT DATE(created_at) AS dia,
               COALESCE(SUM(costo * COALESCE(cantidad_original, cantidad)), 0) AS costos
        FROM compra_materia_prima
        WHERE estado = 'activa'
          AND created_at >= CURDATE() - INTERVAL :r DAY
        GROUP BY DATE(created_at)
        ORDER BY dia ASC
    ");
    $stmtC->execute([':r' => $rango]);
    $costosDiarios = array_column($stmtC->fetchAll(), 'costos', 'dia');

    // Construir labels del rango completo
    $labels   = [];
    $ingresos = [];
    $costoArr = [];
    $beneficio= [];
    for ($i = $rango - 1; $i >= 0; $i--) {
        $d          = date('Y-m-d', strtotime("-{$i} days"));
        $labels[]   = date('d/m', strtotime($d));
        $ingresos[] = 0;
        $costoArr[] = (float)($costosDiarios[$d] ?? 0);
    }
    foreach ($ventasDiarias as $row) {
        $idx = array_search(date('d/m', strtotime($row['dia'])), $labels);
        if ($idx !== false) $ingresos[$idx] = (float)$row['ingresos'];
    }
    foreach ($labels as $i => $_) {
        $beneficio[] = round($ingresos[$i] - $costoArr[$i], 2);
    }

    // ── Productos más vendidos ────────────────────────────────
    $topProductos = $pdo->query("
        SELECT p.nombre,
               SUM(dv.cantidad)                        AS unidades,
               SUM(dv.cantidad * dv.precio_unitario)   AS ingresos
        FROM detalle_ventas dv
        JOIN ventas v ON v.idventas = dv.ventas_idventas
        JOIN productos p ON p.idproductos = dv.productos_idproductos
        WHERE v.estado_venta_idestado_venta = 4
        GROUP BY p.idproductos, p.nombre
        ORDER BY unidades DESC
        LIMIT 10
    ")->fetchAll();

    // ── Ventas por método de pago ─────────────────────────────
    $porPago = $pdo->query("
        SELECT mp.nombre AS metodo,
               COUNT(*) AS cantidad,
               COALESCE(SUM(v.total),0) AS total
        FROM ventas v
        JOIN metodo_pago mp ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago
        WHERE v.estado_venta_idestado_venta = 4
        GROUP BY mp.idmetodo_pago, mp.nombre
        ORDER BY total DESC
    ")->fetchAll();

    // ── Ventas por origen (tienda vs POS) ────────────────────
    $porOrigen = [];
    try {
        $porOrigen = $pdo->query("
            SELECT COALESCE(origen,'pos') AS origen,
                   COUNT(*) AS cantidad,
                   COALESCE(SUM(total),0) AS total
            FROM ventas
            WHERE estado_venta_idestado_venta = 4
            GROUP BY COALESCE(origen,'pos')
        ")->fetchAll();
    } catch (Throwable $e) {}

    // ── Costos por materia prima (top 10) ─────────────────────
    $costosMP = $pdo->query("
        SELECT mp.nombre,
               SUM(c.costo * COALESCE(c.cantidad_original, c.cantidad)) AS total_invertido,
               SUM(COALESCE(c.cantidad_original, c.cantidad))            AS total_cantidad
        FROM compra_materia_prima c
        JOIN materia_prima mp ON mp.idmateria_prima = c.materia_prima_idmateria_prima
        WHERE c.estado = 'activa'
        GROUP BY mp.idmateria_prima, mp.nombre
        ORDER BY total_invertido DESC
        LIMIT 10
    ")->fetchAll();

    echo json_encode([
        'kpis'         => $kpis,
        'costos'       => $costos,
        'labels'       => $labels,
        'ingresos'     => $ingresos,
        'costos_arr'   => $costoArr,
        'beneficio'    => $beneficio,
        'top_productos'=> $topProductos,
        'por_pago'     => $porPago,
        'por_origen'   => $porOrigen,
        'costos_mp'    => $costosMP,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
