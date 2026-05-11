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

    switch ($modo) {
        case 'hoy':
            $inicio = $fin = date('Y-m-d');
            $label  = 'Hoy';
            break;
        case 'semana_actual':
            $inicio = date('Y-m-d', strtotime('monday this week'));
            $fin    = date('Y-m-d', strtotime('sunday this week'));
            $label  = 'Esta semana';
            break;
        case 'semana_anterior':
            $inicio = date('Y-m-d', strtotime('monday last week'));
            $fin    = date('Y-m-d', strtotime('sunday last week'));
            $label  = 'Semana pasada';
            break;
        case 'mes_anterior':
            $t      = mktime(0,0,0, date('n')-1, 1, date('Y'));
            $inicio = date('Y-m-01', $t);
            $fin    = date('Y-m-t',  $t);
            $label  = date('F Y', $t);
            break;
        case 'mes_especifico':
            $t      = mktime(0,0,0, $mes, 1, $anio);
            $inicio = date('Y-m-01', $t);
            $fin    = date('Y-m-t',  $t);
            $label  = date('F Y', $t);
            break;
        case 'anio_completo':
            $inicio = "{$anio}-01-01";
            $fin    = "{$anio}-12-31";
            $label  = "Año {$anio}";
            break;
        case 'rango_custom':
            $desde  = preg_replace('/[^0-9-]/', '', $_GET['desde'] ?? date('Y-m-01'));
            $hasta  = preg_replace('/[^0-9-]/', '', $_GET['hasta'] ?? date('Y-m-d'));
            $inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : date('Y-m-01');
            $fin    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : date('Y-m-d');
            if ($fin < $inicio) $fin = $inicio;
            $label  = date('d/m/Y', strtotime($inicio)).' al '.date('d/m/Y', strtotime($fin));
            break;
        case 'mes_actual':
        default:
            $inicio = date('Y-m-01');
            $fin    = date('Y-m-t');
            $label  = date('F Y');
            break;
    }

    // ── KPIs generales del período ────────────────────────────────
    $kpiStmt = $pdo->prepare("
        SELECT
            COUNT(*)                                                                  AS total_pedidos,
            COUNT(DISTINCT v.usuario_idusuario)                                       AS total_clientes,
            COUNT(DISTINCT CASE WHEN cnt.pedidos > 1 THEN v.usuario_idusuario END)    AS clientes_recurrentes,
            COUNT(DISTINCT CASE WHEN primera.primera_compra BETWEEN :ini3 AND :fin3 THEN v.usuario_idusuario END) AS clientes_nuevos,
            COALESCE(SUM(CASE WHEN v.estado_venta_idestado_venta=4 THEN v.total END),0) AS ingresos_periodo
        FROM ventas v
        LEFT JOIN (
            SELECT usuario_idusuario, COUNT(*) AS pedidos
            FROM ventas
            WHERE estado_venta_idestado_venta != 6
              AND DATE(fecha) BETWEEN :ini AND :fin
            GROUP BY usuario_idusuario
        ) cnt ON cnt.usuario_idusuario = v.usuario_idusuario
        LEFT JOIN (
            SELECT usuario_idusuario, MIN(DATE(fecha)) AS primera_compra
            FROM ventas
            WHERE estado_venta_idestado_venta != 6
            GROUP BY usuario_idusuario
        ) primera ON primera.usuario_idusuario = v.usuario_idusuario
        WHERE v.estado_venta_idestado_venta != 6
          AND DATE(v.fecha) BETWEEN :ini2 AND :fin2
    ");
    $kpiStmt->execute([':ini'=>$inicio,':fin'=>$fin,':ini2'=>$inicio,':fin2'=>$fin,':ini3'=>$inicio,':fin3'=>$fin]);
    $kpis = $kpiStmt->fetch(PDO::FETCH_ASSOC);

    // ── KPIs globales (all time) ──────────────────────────────────
    $globalStmt = $pdo->query("
        SELECT
            COUNT(DISTINCT usuario_idusuario)                                               AS clientes_totales_ever,
            COUNT(DISTINCT CASE WHEN pedidos_total > 1 THEN usuario_idusuario END)          AS recurrentes_ever
        FROM (
            SELECT usuario_idusuario, COUNT(*) AS pedidos_total
            FROM ventas
            WHERE estado_venta_idestado_venta != 6
            GROUP BY usuario_idusuario
        ) sub
    ");
    $global = $globalStmt->fetch(PDO::FETCH_ASSOC);

    // ── Lista de clientes del período ─────────────────────────────
    $listStmt = $pdo->prepare("
        SELECT
            u.idusuario,
            COALESCE(CONCAT(u.nombre,' ',COALESCE(u.apellido,'')), 'Consumidor Final') AS nombre,
            COALESCE(u.email,'')    AS email,
            COALESCE(u.celular,'') AS celular,
            COUNT(v.idventas)       AS pedidos,
            COALESCE(SUM(CASE WHEN v.estado_venta_idestado_venta=4 THEN v.total END),0) AS total_gastado,
            MAX(v.fecha)            AS ultima_compra,
            MIN(v.fecha)            AS primera_compra
        FROM ventas v
        LEFT JOIN usuario u ON u.idusuario = v.usuario_idusuario
        WHERE v.estado_venta_idestado_venta != 6
          AND DATE(v.fecha) BETWEEN :ini AND :fin
        GROUP BY v.usuario_idusuario, u.idusuario, u.nombre, u.apellido, u.email, u.celular
        ORDER BY pedidos DESC, total_gastado DESC
        LIMIT 200
    ");
    $listStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $clientes = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Top días con más clientes únicos ──────────────────────────
    $diasStmt = $pdo->prepare("
        SELECT DATE(fecha) AS dia, COUNT(DISTINCT usuario_idusuario) AS clientes_unicos, COUNT(*) AS pedidos
        FROM ventas
        WHERE estado_venta_idestado_venta != 6
          AND DATE(fecha) BETWEEN :ini AND :fin
        GROUP BY DATE(fecha)
        ORDER BY clientes_unicos DESC
        LIMIT 7
    ");
    $diasStmt->execute([':ini'=>$inicio,':fin'=>$fin]);
    $topDias = $diasStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'periodo'    => $label,
        'inicio'     => $inicio,
        'fin'        => $fin,
        'kpis'       => $kpis,
        'global'     => $global,
        'clientes'   => $clientes,
        'top_dias'   => $topDias,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
