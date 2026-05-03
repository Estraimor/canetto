<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
ob_end_clean();
header('Content-Type: application/json');

try {
    $pdo  = Conexion::conectar();
    $modo = $_GET['modo'] ?? 'mes_actual';
    $mes  = max(1, min(12, (int)($_GET['mes']  ?? date('n'))));
    $anio = max(2020, min(2099, (int)($_GET['anio'] ?? date('Y'))));

    // ── Rango de fechas según modo ────────────────────────────
    switch ($modo) {
        case 'semana_actual':
            $inicio = date('Y-m-d', strtotime('monday this week'));
            $fin    = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'semana_anterior':
            $inicio = date('Y-m-d', strtotime('monday last week'));
            $fin    = date('Y-m-d', strtotime('sunday last week'));
            break;
        case 'mes_anterior':
            $t      = mktime(0,0,0, date('n')-1, 1, date('Y'));
            $inicio = date('Y-m-01', $t);
            $fin    = date('Y-m-t',  $t);
            break;
        case 'mes_especifico':
            $t      = mktime(0,0,0, $mes, 1, $anio);
            $inicio = date('Y-m-01', $t);
            $fin    = date('Y-m-t',  $t);
            break;
        case 'anio_completo':
            $inicio = "{$anio}-01-01";
            $fin    = "{$anio}-12-31";
            break;
        case 'dia_especifico':
            $d      = preg_replace('/[^0-9-]/', '', $_GET['dia'] ?? date('Y-m-d'));
            $inicio = $fin = (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : date('Y-m-d'));
            break;
        case 'rango_custom':
            $desde  = preg_replace('/[^0-9-]/', '', $_GET['desde'] ?? date('Y-m-01'));
            $hasta  = preg_replace('/[^0-9-]/', '', $_GET['hasta'] ?? date('Y-m-d'));
            $inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : date('Y-m-01');
            $fin    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : date('Y-m-d');
            if ($fin < $inicio) $fin = $inicio;
            break;
        case 'hoy':
            $inicio = $fin = date('Y-m-d');
            break;
        case 'mes_actual':
        default:
            $inicio = date('Y-m-01');
            $fin    = date('Y-m-d');
            break;
    }

    $label_periodo = match($modo) {
        'semana_actual'   => 'Esta semana',
        'semana_anterior' => 'Semana pasada',
        'mes_anterior'    => date('F Y', mktime(0,0,0, date('n')-1, 1, date('Y'))),
        'mes_especifico'  => date('F Y', mktime(0,0,0, $mes, 1, $anio)),
        'anio_completo'   => "Todo el año {$anio}",
        'dia_especifico'  => date('d/m/Y', strtotime($inicio)),
        'rango_custom'    => date('d/m/Y', strtotime($inicio)).' al '.date('d/m/Y', strtotime($fin)),
        'hoy'             => 'Hoy',
        default           => date('F Y'),
    };

    // ── KPIs del período ─────────────────────────────────────
    $kpiStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total END), 0)           AS ventas_hoy,
            COUNT(CASE  WHEN DATE(fecha) = CURDATE() THEN 1 END)                          AS pedidos_hoy,
            COALESCE(SUM(CASE WHEN YEARWEEK(fecha,1)=YEARWEEK(CURDATE(),1) THEN total END), 0) AS ventas_semana,
            COUNT(CASE  WHEN YEARWEEK(fecha,1)=YEARWEEK(CURDATE(),1) THEN 1 END)          AS pedidos_semana,
            COALESCE(SUM(CASE WHEN DATE(fecha) BETWEEN :ini AND :fin THEN total END), 0)  AS ventas_periodo,
            COUNT(CASE  WHEN DATE(fecha) BETWEEN :ini2 AND :fin2 THEN 1 END)              AS pedidos_periodo,
            COALESCE(SUM(total), 0)                                                        AS ventas_total
        FROM ventas WHERE estado_venta_idestado_venta = 4
    ");
    $kpiStmt->execute([':ini'=>$inicio,':fin'=>$fin,':ini2'=>$inicio,':fin2'=>$fin]);
    $kpis = $kpiStmt->fetch();

    // ── Costos del período ────────────────────────────────────
    $costoStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE()
                THEN costo * COALESCE(cantidad_original,cantidad) END), 0)               AS costo_hoy,
            COALESCE(SUM(CASE WHEN YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)
                THEN costo * COALESCE(cantidad_original,cantidad) END), 0)               AS costo_semana,
            COALESCE(SUM(CASE WHEN DATE(created_at) BETWEEN :ini AND :fin
                THEN costo * COALESCE(cantidad_original,cantidad) END), 0)               AS costo_periodo,
            COALESCE(SUM(costo * COALESCE(cantidad_original,cantidad)), 0)               AS costo_total
        FROM compra_materia_prima WHERE estado = 'activa'
    ");
    $costoStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $costos = $costoStmt->fetch();

    // ── Ventas diarias en el período ─────────────────────────
    $ventasStmt = $pdo->prepare("
        SELECT DATE(fecha) AS dia, COALESCE(SUM(total),0) AS ingresos, COUNT(*) AS cantidad
        FROM ventas
        WHERE estado_venta_idestado_venta = 4
          AND DATE(fecha) BETWEEN :ini AND :fin
        GROUP BY DATE(fecha) ORDER BY dia ASC
    ");
    $ventasStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $ventasDiarias = $ventasStmt->fetchAll();

    // ── Costos diarios en el período ─────────────────────────
    $costoDStmt = $pdo->prepare("
        SELECT DATE(created_at) AS dia,
               COALESCE(SUM(costo * COALESCE(cantidad_original,cantidad)),0) AS costos
        FROM compra_materia_prima
        WHERE estado = 'activa' AND DATE(created_at) BETWEEN :ini AND :fin
        GROUP BY DATE(created_at) ORDER BY dia ASC
    ");
    $costoDStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $costosDiarios = array_column($costoDStmt->fetchAll(), 'costos', 'dia');

    // Construir rango completo de días
    $labels=$ingresos=$costoArr=$beneficio=[];
    $cur = new DateTime($inicio);
    $end = new DateTime($fin);
    while ($cur <= $end) {
        $d          = $cur->format('Y-m-d');
        $labels[]   = $cur->format('d/m');
        $ingresos[] = 0.0;
        $costoArr[] = (float)($costosDiarios[$d] ?? 0);
        $cur->modify('+1 day');
    }
    foreach ($ventasDiarias as $row) {
        $lbl = date('d/m', strtotime($row['dia']));
        $idx = array_search($lbl, $labels);
        if ($idx !== false) $ingresos[$idx] = (float)$row['ingresos'];
    }
    foreach ($labels as $i => $_) {
        $beneficio[] = round($ingresos[$i] - $costoArr[$i], 2);
    }

    // ── Top productos del período ─────────────────────────────
    $topStmt = $pdo->prepare("
        SELECT p.nombre,
               SUM(dv.cantidad)                      AS unidades,
               SUM(dv.cantidad*dv.precio_unitario)   AS ingresos
        FROM detalle_ventas dv
        JOIN ventas v   ON v.idventas = dv.ventas_idventas
        JOIN productos p ON p.idproductos = dv.productos_idproductos
        WHERE v.estado_venta_idestado_venta = 4
          AND DATE(v.fecha) BETWEEN :ini AND :fin
        GROUP BY p.idproductos, p.nombre
        ORDER BY unidades DESC LIMIT 10
    ");
    $topStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $topProductos = $topStmt->fetchAll();

    // ── Por método de pago del período ────────────────────────
    $pagoStmt = $pdo->prepare("
        SELECT mp.nombre AS metodo, COUNT(*) AS cantidad, COALESCE(SUM(v.total),0) AS total
        FROM ventas v
        JOIN metodo_pago mp ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago
        WHERE v.estado_venta_idestado_venta = 4
          AND DATE(v.fecha) BETWEEN :ini AND :fin
        GROUP BY mp.idmetodo_pago, mp.nombre ORDER BY total DESC
    ");
    $pagoStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $porPago = $pagoStmt->fetchAll();

    // ── Por origen (tienda vs POS) ────────────────────────────
    $porOrigen = [];
    try {
        $origStmt = $pdo->prepare("
            SELECT COALESCE(origen,'pos') AS origen, COUNT(*) AS cantidad, COALESCE(SUM(total),0) AS total
            FROM ventas
            WHERE estado_venta_idestado_venta = 4
              AND DATE(fecha) BETWEEN :ini AND :fin
            GROUP BY COALESCE(origen,'pos')
        ");
        $origStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
        $porOrigen = $origStmt->fetchAll();
    } catch (Throwable $e) {}

    // ── Retiro vs Envío ───────────────────────────────────────
    $retiroEnvio = ['retiro' => ['cantidad' => 0, 'total' => 0.0], 'envio' => ['cantidad' => 0, 'total' => 0.0]];
    try {
        $reStmt = $pdo->prepare("
            SELECT COALESCE(tipo_entrega,'retiro') AS tipo,
                   COUNT(*) AS cantidad,
                   COALESCE(SUM(total),0) AS total
            FROM ventas
            WHERE estado_venta_idestado_venta = 4
              AND DATE(fecha) BETWEEN :ini AND :fin
            GROUP BY COALESCE(tipo_entrega,'retiro')
        ");
        $reStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
        foreach ($reStmt->fetchAll() as $row) {
            $k = $row['tipo'] === 'envio' ? 'envio' : 'retiro';
            $retiroEnvio[$k] = ['cantidad' => (int)$row['cantidad'], 'total' => (float)$row['total']];
        }
    } catch (Throwable $e) {}

    // ── Costos por materia prima del período ──────────────────
    $mpStmt = $pdo->prepare("
        SELECT mp.nombre,
               SUM(c.costo * COALESCE(c.cantidad_original,c.cantidad)) AS total_invertido,
               SUM(COALESCE(c.cantidad_original,c.cantidad))            AS total_cantidad,
               COUNT(*)                                                  AS num_compras
        FROM compra_materia_prima c
        JOIN materia_prima mp ON mp.idmateria_prima = c.materia_prima_idmateria_prima
        WHERE c.estado = 'activa' AND DATE(c.created_at) BETWEEN :ini AND :fin
        GROUP BY mp.idmateria_prima, mp.nombre
        ORDER BY total_invertido DESC LIMIT 15
    ");
    $mpStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $costosMP = $mpStmt->fetchAll();

    // ── DEBE Y HABER del período ──────────────────────────────
    // Ingresos (ventas entregadas)
    $ventasHaber = $pdo->prepare("
        SELECT DATE(v.fecha) AS fecha,
               CONCAT('Venta #', v.idventas) AS concepto,
               COALESCE(CONCAT(u.nombre,' ',u.apellido),'Cliente') AS detalle,
               v.total AS monto,
               'ingreso' AS tipo
        FROM ventas v
        LEFT JOIN usuario u ON u.idusuario = v.usuario_idusuario
        WHERE v.estado_venta_idestado_venta = 4
          AND DATE(v.fecha) BETWEEN :ini AND :fin
        ORDER BY v.fecha ASC
    ");
    $ventasHaber->execute([':ini'=>$inicio,':fin'=>$fin]);
    $filas_haber = $ventasHaber->fetchAll();

    // Costos (compras de materiales)
    $comprasDebe = $pdo->prepare("
        SELECT DATE(c.created_at) AS fecha,
               CONCAT('Compra: ', mp.nombre) AS concepto,
               CONCAT(COALESCE(c.cantidad_original,c.cantidad),' ', COALESCE(c.unidad_compra,'unid.')) AS detalle,
               (c.costo * COALESCE(c.cantidad_original,c.cantidad)) AS monto,
               'costo' AS tipo
        FROM compra_materia_prima c
        JOIN materia_prima mp ON mp.idmateria_prima = c.materia_prima_idmateria_prima
        WHERE c.estado = 'activa' AND DATE(c.created_at) BETWEEN :ini AND :fin
        ORDER BY c.created_at ASC
    ");
    $comprasDebe->execute([':ini'=>$inicio,':fin'=>$fin]);
    $filas_debe = $comprasDebe->fetchAll();

    // Combinar y ordenar por fecha
    $debeHaber = array_merge($filas_haber, $filas_debe);
    usort($debeHaber, fn($a,$b) => strcmp($a['fecha'], $b['fecha']));

    // Calcular saldo acumulado
    $saldo = 0;
    foreach ($debeHaber as &$f) {
        $saldo += $f['tipo'] === 'ingreso' ? (float)$f['monto'] : -(float)$f['monto'];
        $f['saldo'] = round($saldo, 2);
    }
    unset($f);

    // ── Heatmap: pedidos por día de semana y hora ────────────
    $heatStmt = $pdo->prepare("
        SELECT WEEKDAY(fecha) AS dow, HOUR(fecha) AS hora, COUNT(*) AS cantidad
        FROM ventas
        WHERE estado_venta_idestado_venta = 4
          AND DATE(fecha) BETWEEN :ini AND :fin
        GROUP BY WEEKDAY(fecha), HOUR(fecha)
    ");
    $heatStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $heatRaw  = $heatStmt->fetchAll();
    // Convertir a matriz [dow][hora] => cantidad
    $heatmap  = [];
    foreach ($heatRaw as $h) {
        $heatmap[(int)$h['dow']][(int)$h['hora']] = (int)$h['cantidad'];
    }

    echo json_encode([
        'periodo'      => $label_periodo,
        'inicio'       => $inicio,
        'fin'          => $fin,
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
        'heatmap'      => $heatmap,
        'retiro_envio' => $retiroEnvio,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
