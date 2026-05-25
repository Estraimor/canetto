<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/tron.php';

$pdo = Conexion::conectar();

$totalProductos    = $pdo->query("SELECT COUNT(*) FROM productos WHERE tipo='producto'")->fetchColumn();
$totalMP           = $pdo->query("SELECT COUNT(*) FROM materia_prima")->fetchColumn();
$produccionHoy     = $pdo->query("SELECT COUNT(*) FROM produccion WHERE DATE(fecha)=CURDATE()")->fetchColumn();

// Stock de toppings — tabla: toppings + toppings_stock
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS toppings_stock (
        idtoppings_stock INT AUTO_INCREMENT PRIMARY KEY,
        toppings_idtoppings INT NOT NULL,
        stock_actual DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
        UNIQUE KEY uq_tp (toppings_idtoppings)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $stockToppings = $pdo->query("
        SELECT t.idtoppings, t.nombre, COALESCE(ts.stock_actual,0) AS stock_actual, COALESCE(ts.stock_minimo,0) AS stock_minimo
        FROM toppings t
        LEFT JOIN toppings_stock ts ON ts.toppings_idtoppings = t.idtoppings
        WHERE t.activo = 1
        ORDER BY COALESCE(ts.stock_actual,0) ASC LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $stockToppings = []; }

// Stock de packaging/cajas
try {
    $stockPackaging = $pdo->query("
        SELECT idpackaging, nombre, stock_actual, stock_minimo
        FROM packaging
        ORDER BY stock_actual ASC LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $stockPackaging = []; }

// Stock de materias primas — JOIN con unidad_medida para obtener abreviatura
try {
    $mpCols    = array_column($pdo->query("DESCRIBE materia_prima")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $colActual = in_array('stock_actual', $mpCols) ? 'stock_actual' : (in_array('cantidad', $mpCols) ? 'cantidad' : 'stock');
    $colMinimo = in_array('stock_minimo', $mpCols) ? 'stock_minimo' : (in_array('minimo', $mpCols)   ? 'minimo'   : '0');
    $hasUmJoin = in_array('unidad_medida_idunidad_medida', $mpCols);
    if ($hasUmJoin) {
        $stockMP = $pdo->query("
            SELECT mp.nombre, mp.$colActual AS stock_actual, mp.$colMinimo AS stock_minimo,
                   COALESCE(um.abreviatura, '') AS unidad
            FROM materia_prima mp
            LEFT JOIN unidad_medida um ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
            ORDER BY mp.$colActual ASC LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $colUnidad = in_array('unidad', $mpCols) ? 'unidad' : (in_array('unidad_medida', $mpCols) ? 'unidad_medida' : null);
        $selectU   = $colUnidad ? ", $colUnidad AS unidad" : ", '' AS unidad";
        $stockMP   = $pdo->query("
            SELECT nombre, $colActual AS stock_actual, $colMinimo AS stock_minimo $selectU
            FROM materia_prima ORDER BY $colActual ASC LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) { $stockMP = []; }

// Pedidos activos = ventas no entregadas ni canceladas (sin filtro de fecha)
try { $pedidosPendientes = $pdo->query("
    SELECT COUNT(*) FROM ventas
    WHERE estado_venta_idestado_venta NOT IN (4,6)
")->fetchColumn(); }
catch (Throwable $e) { $pedidosPendientes = 0; }

// KPIs de ventas
try {
    $ventasHoy = $pdo->query("
        SELECT COALESCE(SUM(total),0) AS ingresos, COUNT(*) AS cantidad
        FROM ventas WHERE estado_venta_idestado_venta != 6 AND DATE(fecha) = CURDATE()
    ")->fetch(PDO::FETCH_ASSOC);
    $ventasAyer = $pdo->query("
        SELECT COALESCE(SUM(total),0) AS ingresos
        FROM ventas WHERE estado_venta_idestado_venta = 4 AND DATE(fecha) = CURDATE() - INTERVAL 1 DAY
    ")->fetchColumn();
} catch (Throwable $e) { $ventasHoy = ['ingresos'=>0,'cantidad'=>0]; $ventasAyer = 0; }

$productosBajos = $pdo->query("
    SELECT p.nombre, sp.stock_actual, sp.stock_minimo, sp.tipo_stock
    FROM stock_productos sp
    INNER JOIN productos p ON p.idproductos = sp.productos_idproductos
    WHERE sp.stock_actual > 0 AND sp.stock_actual <= sp.stock_minimo
      AND sp.tipo_stock IN ('CONGELADO','HECHO')
")->fetchAll(PDO::FETCH_ASSOC);

$productosSinStock = $pdo->query("
    SELECT DISTINCT p.nombre, sp.tipo_stock
    FROM stock_productos sp
    INNER JOIN productos p ON p.idproductos = sp.productos_idproductos
    WHERE sp.stock_actual = 0 AND sp.tipo_stock IN ('HECHO','CONGELADO')
")->fetchAll(PDO::FETCH_ASSOC);

$stockPorProducto = $pdo->query("
    SELECT p.nombre, sp.tipo_stock, sp.stock_actual, sp.stock_minimo
    FROM stock_productos sp
    INNER JOIN productos p ON p.idproductos = sp.productos_idproductos
    ORDER BY p.nombre, sp.tipo_stock
")->fetchAll(PDO::FETCH_ASSOC);

$stockAgrupado = [];
foreach ($stockPorProducto as $row) {
    $nombre = $row['nombre'];
    $tipo   = strtolower($row['tipo_stock']);
    if (!isset($stockAgrupado[$nombre])) $stockAgrupado[$nombre] = [];
    $stockAgrupado[$nombre][$tipo] = $row;
}

$ultimasProducciones = $pdo->query("
    SELECT p.nombre, pr.cantidad, pr.fecha
    FROM produccion pr
    INNER JOIN productos p ON p.recetas_idrecetas = pr.recetas_idrecetas
    ORDER BY pr.fecha DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$prod7dias = $pdo->query("
    SELECT DATE(fecha) AS dia, COUNT(*) AS total
    FROM produccion
    WHERE fecha >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(fecha) ORDER BY dia ASC
")->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = []; $chartData = [];
$prod7map = array_column($prod7dias, 'total', 'dia');
$diaCorto = ['Sun'=>'Dom','Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb'];
for ($i = 6; $i >= 0; $i--) {
    $d             = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = ($diaCorto[date('D', strtotime($d))] ?? '') . ' ' . date('d', strtotime($d));
    $chartData[]   = (int)($prod7map[$d] ?? 0);
}

try {
    $auditoriasRecientes = $pdo->query("
        SELECT usuario_nombre, accion, modulo, descripcion, created_at
        FROM auditoria ORDER BY created_at DESC LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $auditoriasRecientes = []; }

try { $incidenciasAbiertas = $pdo->query("SELECT COUNT(*) FROM incidencias WHERE estado='abierta'")->fetchColumn(); }
catch (Throwable $e) { $incidenciasAbiertas = 0; }

// Materias primas con stock bajo o sin stock
try {
    $mpBajas = $pdo->query("
        SELECT nombre, stock_actual, stock_minimo
        FROM materia_prima
        WHERE stock_actual > 0 AND stock_actual <= stock_minimo
        ORDER BY stock_actual ASC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $mpBajas = []; }

try {
    $mpSinStock = $pdo->query("
        SELECT nombre FROM materia_prima WHERE stock_actual <= 0 LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $mpSinStock = []; }

$diasSemana = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes',
               'Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
$hoy        = new DateTime();
$diaNombre  = $diasSemana[$hoy->format('l')] ?? '';
$fechaHoy   = $hoy->format('d/m/Y');

$stockOk      = empty($productosBajos) && empty($productosSinStock);
$totalAlertas = count($productosSinStock) + count($productosBajos) + count($mpSinStock) + count($mpBajas);
$topbarStats  = "Hoy: {$produccionHoy} prod. · Stock: " . ($stockOk ? 'saludable ✓' : 'revisar ⚠');
$pageTitle    = "Dashboard — Canetto";

include '../panel/dashboard/layaut/nav.php';
?>
<link rel="stylesheet" href="dashboard.css">
<link rel="stylesheet" href="analitica/analitica.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* ── SELECTOR BAR ──────────────────────────────────────────────── */
.cv-bar {
    background: linear-gradient(135deg, #fffbfc 0%, #fff 70%);
    border-bottom: 1.5px solid #eedde2;
    padding: 0 36px;
    display: flex;
    align-items: center;
    gap: 14px;
    height: 58px;
    position: sticky;
    top: 0;
    z-index: 90;
    box-shadow: 0 3px 12px rgba(200,142,153,.1);
    transition: transform .28s ease, opacity .28s ease;
}
.cv-bar.cv-bar--hidden {
    transform: translateY(-100%);
    opacity: 0;
    pointer-events: none;
}
.cv-bar-label {
    font-family: inherit;
    font-size: .73rem;
    font-weight: 700;
    color: #c88e99;
    text-transform: uppercase;
    letter-spacing: .1em;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 7px;
}
.cv-select {
    font-family: inherit;
    font-size: .95rem;
    font-weight: 700;
    color: #111110;
    background: #fff;
    border: 2px solid #e0e0dd;
    border-radius: 10px;
    padding: 8px 44px 8px 16px;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='7' viewBox='0 0 11 7'%3E%3Cpath d='M1 1l4.5 4.5L10 1' stroke='%23c88e99' stroke-width='2' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    min-width: 260px;
    transition: border-color .2s, box-shadow .2s;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.cv-select:focus { outline: none; border-color: #c88e99; box-shadow: 0 0 0 3px rgba(200,142,153,.18); }
.cv-select:hover { border-color: #c88e99; }
.cv-badge {
    margin-left: auto;
    display: flex; align-items: center; gap: 8px;
    font-family: inherit;
    font-size: .8rem; color: #8a8a86;
}
.cv-badge-dot {
    width: 8px; height: 8px; border-radius: 50%; background: #c88e99;
    box-shadow: 0 0 0 2px rgba(200,142,153,.25);
    animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
    0%,100% { box-shadow: 0 0 0 2px rgba(200,142,153,.25); }
    50%      { box-shadow: 0 0 0 5px rgba(200,142,153,.08); }
}

/* ── KPI ANIMATION ─────────────────────────────────────────────── */
.db-kpi-val, .kpi-value {
    transition: color .3s;
}
.kpi-value.anim { color: #c88e99; }

/* ── SUB-FILTROS POR TARJETA ───────────────────────────────────── */
.sf-wrap {
    border-top: 1px dashed #ede8e5;
    padding: 8px 0 4px;
    margin-bottom: 6px;
}
.sf-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}
.sf-lbl {
    font-family: inherit;
    font-size: .7rem;
    font-weight: 600;
    color: #8a8a86;
    text-transform: uppercase;
    letter-spacing: .06em;
    white-space: nowrap;
    margin-right: 2px;
}
.sf-pill {
    font-family: inherit;
    font-size: .72rem;
    font-weight: 600;
    color: #4a4a47;
    background: #f5f4f1;
    border: 1px solid #e8e8e6;
    border-radius: 20px;
    padding: 3px 10px;
    cursor: pointer;
    transition: background .15s, border-color .15s, color .15s;
    white-space: nowrap;
}
.sf-pill:hover { background: #f0eaf0; border-color: #c88e99; color: #c88e99; }
.sf-pill.active { background: #c88e99; border-color: #c88e99; color: #fff; }
.sf-reset {
    font-family: inherit;
    font-size: .7rem;
    font-weight: 600;
    color: #c88e99;
    background: none;
    border: none;
    cursor: pointer;
    padding: 3px 6px;
    margin-left: 4px;
    opacity: .8;
    transition: opacity .15s;
}
.sf-reset:hover { opacity: 1; }
.sf-loading {
    font-size: .75rem;
    color: #8a8a86;
    padding: 6px 0;
    font-style: italic;
}

/* ── KPI CARDS NO CLICKEABLES ──────────────────────────────────── */
.kpi-card { cursor: default !important; }
.kpi-card:hover { transform: none !important; box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,.06)) !important; }
.kpi-arrow { display: none; }

/* ── COLORES KPI ────────────────────────────────────────────────── */
.kpi-blue   { border-top-color: #2563eb; border-left-color: transparent !important; }
.kpi-purple { border-top-color: #7c3aed; border-left-color: transparent !important; }
.kpi-ico-purple { background: #f5f3ff; color: #7c3aed; }

/* ── RETIRO/ENVÍO KPI CARDS ────────────────────────────────────── */
.kpi-card.kpi-retiro { border-left-color: #16a34a; }
.kpi-card.kpi-envio  { border-left-color: #2563eb; }
.kpi-ico-green { color: #16a34a; background: #dcfce7; }
.kpi-ico-blue  { color: #2563eb; background: #dbeafe; }

/* ── FILTROS DE ANALÍTICA ─────────────────────────────────────── */
.ana-filtros-wrap {
    display: flex;
    flex-direction: column;
    margin-bottom: 1.4rem;
    background: #fff;
    border: 1px solid #e8e7e4;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    overflow: hidden;
}
.ana-filtros {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding: 12px 18px;
}
.ana-filtros + .ana-filtros {
    border-top: 1px solid #f0eeeb;
    background: #fafaf9;
}
.flt-pills {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
    flex: 1;
}
.flt-pill {
    padding: 6px 14px;
    border-radius: 6px;
    border: 1.5px solid #e8e7e4;
    background: transparent;
    font-family: "Speedee", sans-serif;
    font-size: .8rem;
    font-weight: 600;
    color: #3a3a3a;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
    line-height: 1.35;
}
.flt-pill:hover  { border-color: #aaa; color: #0f0f0f; background: #f0eeeb; }
.flt-pill.active { background: #0f0f0f; color: #fff; border-color: #0f0f0f; }
.flt-mes-sel { display: flex; gap: 6px; margin-left: auto; }
.flt-rango { padding: 10px 18px; }
.flt-rango-label {
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #888;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 5px;
}
.ana-select-sm {
    padding: 6px 10px;
    border: 1.5px solid #e8e7e4;
    border-radius: 6px;
    font-family: "Speedee", sans-serif;
    font-size: .8rem;
    background: #f4f3f0;
    color: #0f0f0f;
    cursor: pointer;
    outline: none;
    transition: border-color .15s, background .15s;
}
.ana-select-sm:focus { border-color: #0f0f0f; background: #fff; }

/* flt-pill-fin es igual a flt-pill (mismos estilos) */
.flt-pill-fin {
    padding: 6px 14px;
    border-radius: 6px;
    border: 1.5px solid #e8e7e4;
    background: transparent;
    font-family: "Speedee", sans-serif;
    font-size: .8rem;
    font-weight: 600;
    color: #3a3a3a;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
    line-height: 1.35;
}
.flt-pill-fin:hover  { border-color: #aaa; color: #0f0f0f; background: #f0eeeb; }
.flt-pill-fin.active { background: #0f0f0f; color: #fff; border-color: #0f0f0f; }

/* Debe y Haber */
.dh-resumen {
    display: flex; gap: 1px; margin-bottom: 16px;
    border-radius: 8px; overflow: hidden; border: 1px solid #e8e7e4;
}
.dh-res-item {
    flex: 1; display: flex; flex-direction: column; gap: 4px;
    padding: 14px 18px; background: #fff;
}
.dh-res-item + .dh-res-item { border-left: 1px solid #e8e7e4; }
.dh-res-item span   { font-size: .63rem; text-transform: uppercase; letter-spacing: .07em; color: #888; font-weight: 700; }
.dh-res-item strong { font-size: 1.2rem; font-weight: 800; letter-spacing: -.02em; }
.dh-res-haber   strong { color: #16a34a; }
.dh-res-debe    strong { color: #c0392b; }
.dh-res-balance.pos strong { color: #16a34a; }
.dh-res-balance.neg strong { color: #c0392b; }
.dh-saldo     { font-weight: 700; white-space: nowrap; }
.dh-saldo.pos { color: #16a34a; }
.dh-saldo.neg { color: #c0392b; }
.dh-ingreso td { background: rgba(22,163,74,.025); }
.dh-costo   td { background: rgba(192,57,43,.025); }

/* ── ATAJOS RÁPIDOS MEJORADOS ─────────────────────────────── */
.db-qa--primary {
    background: #0f0f0f !important;
    color: #fff !important;
    border-color: #0f0f0f !important;
}
.db-qa--primary i, .db-qa--primary span { color: #fff !important; }
.db-qa--alert {
    border-color: #fecaca !important;
    background: #fff5f5 !important;
}
.db-qa--alert i { color: #dc2626 !important; }
.db-qa-badge {
    position: absolute;
    top: -4px; right: -4px;
    background: #dc2626;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    min-width: 16px; height: 16px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 4px;
    border: 2px solid #fff;
}
.db-qa { position: relative; }

/* ── ALERTAS ACCIONABLES ─────────────────────────────────── */
.db-alerta-seccion {
    display: block;
    text-decoration: none;
    padding: 12px 18px;
    border-bottom: 1px solid #f0eeeb;
    transition: background .15s;
    cursor: pointer;
}
.db-alerta-seccion:last-child { border-bottom: none; }
.db-alerta-seccion--red   { border-left: 3px solid #dc2626; }
.db-alerta-seccion--amber { border-left: 3px solid #d97706; }
.db-alerta-seccion--red:hover   { background: #fff5f5; }
.db-alerta-seccion--amber:hover { background: #fffbeb; }

.db-alerta-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: .83rem;
    color: #0f0f0f;
}
.db-alerta-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.db-alerta-dot--red   { background: #dc2626; }
.db-alerta-dot--amber { background: #d97706; }
.db-alerta-cta {
    margin-left: auto;
    font-size: .72rem;
    font-weight: 700;
    color: #888;
}
.db-alerta-seccion:hover .db-alerta-cta { color: #0f0f0f; }

.db-alerta-items { display: flex; flex-direction: column; gap: 4px; }
.db-alerta-item {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: .78rem;
    color: #3a3a3a;
    padding: 2px 0;
}
.db-alerta-item-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
.db-alerta-item-tag {
    margin-left: auto;
    font-size: .68rem;
    font-weight: 600;
    color: #888;
    background: #f4f3f0;
    padding: 1px 6px;
    border-radius: 4px;
}
</style>

<!-- SELECTOR DE VISTA -->
<div class="cv-bar">
    <span class="cv-bar-label">
        <i class="fa-solid fa-layer-group"></i>Vista
    </span>
    <select id="viewSelect" class="cv-select" onchange="toggleVista(this.value)">
        <option value="dashboard">Dashboard operacional</option>
        <option value="analitica">Analítica de ventas</option>
        <option value="finanzas">Finanzas</option>
    </select>
    <div class="cv-badge">
        <span class="cv-badge-dot"></span>
        <span id="cv-nombre">Dashboard</span>
    </div>
</div>

<!-- ═══════════════ VISTA: DASHBOARD ═══════════════ -->
<div id="view-dashboard">
<div class="db"><div class="db-main">

    <div class="db-page-header">
        <div class="db-header-left">
            <div class="db-header-eyebrow">
                <i class="fa-regular fa-calendar"></i>
                <?= strtoupper($diaNombre) ?>, <?= $fechaHoy ?>
            </div>
            <p class="db-subtitle">Vista de operaciones · Canetto</p>
        </div>
        <div class="db-header-right">
            <?php if ($incidenciasAbiertas > 0): ?>
            <a href="<?= URL_ADMIN ?>/incidencias/index.php" class="db-incidencia-badge">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= (int)$incidenciasAbiertas ?> incidencia<?= $incidenciasAbiertas > 1 ? 's' : '' ?> abierta<?= $incidenciasAbiertas > 1 ? 's' : '' ?>
            </a>
            <?php endif; ?>
            <div class="db-live-dot"><span></span> En vivo</div>
        </div>
    </div>

    <div class="db-quick-actions">
        <a href="<?= URL_ADMIN ?>/Ventas/Ventas/index.php"        class="db-qa db-qa--primary"><i class="fa-solid fa-cart-plus"></i><span>Nueva venta</span></a>
        <a href="<?= URL_ADMIN ?>/Ventas/Pedidos/index.php"       class="db-qa"><i class="fa-solid fa-clock"></i><span>Pedidos</span><?php if($pedidosPendientes > 0): ?><span class="db-qa-badge"><?= (int)$pedidosPendientes ?></span><?php endif; ?></a>
        <a href="<?= URL_ADMIN ?>/stock/index.php"                class="db-qa <?= $totalAlertas > 0 ? 'db-qa--alert' : '' ?>"><i class="fa-solid fa-boxes-stacked"></i><span>Stock</span><?php if($totalAlertas > 0): ?><span class="db-qa-badge"><?= $totalAlertas ?></span><?php endif; ?></a>
        <a href="<?= URL_ADMIN ?>/produccion/congelado/index.php" class="db-qa"><i class="fa-solid fa-snowflake"></i><span>Producción</span></a>
        <a href="<?= URL_ADMIN ?>/analitica/index.php"            class="db-qa"><i class="fa-solid fa-chart-line"></i><span>Analítica</span></a>
    </div>

    <?php
    $ingresosHoy  = (float)$ventasHoy['ingresos'];
    $cantidadHoy  = (int)$ventasHoy['cantidad'];
    $ingresosAyer = (float)$ventasAyer;
    $diffPct      = $ingresosAyer > 0 ? round(($ingresosHoy - $ingresosAyer) / $ingresosAyer * 100) : null;
    $alertasSinStock = count($productosSinStock);
    $alertasBajo     = count($productosBajos);
    ?>
    <div class="db-kpi-row">

        <!-- Ingresos hoy -->
        <div class="db-kpi">
            <div class="db-kpi-icon" style="background:#dcfce7;color:#16a34a"><i class="fa-solid fa-dollar-sign"></i></div>
            <div class="db-kpi-label">Ingresos hoy</div>
            <div class="db-kpi-val" style="font-size:1.5rem" data-val="<?= (int)$ingresosHoy ?>" data-money="1">$0</div>
            <div class="db-kpi-sub">
                <?= $cantidadHoy ?> pedido<?= $cantidadHoy !== 1 ? 's' : '' ?>
                <?php if ($diffPct !== null): ?>
                    · <span style="color:<?= $diffPct >= 0 ? '#16a34a' : '#dc2626' ?>;font-weight:700"><?= $diffPct >= 0 ? '+' : '' ?><?= $diffPct ?>% vs ayer</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pedidos activos -->
        <a href="<?= URL_ADMIN ?>/Ventas/Pedidos/index.php" class="db-kpi" style="text-decoration:none;color:inherit;cursor:pointer">
            <div class="db-kpi-icon" style="<?= $pedidosPendientes > 0 ? 'background:#fffbeb;color:#d97706' : 'background:#f4f3f0;color:#888' ?>"><i class="fa-solid fa-clock"></i></div>
            <div class="db-kpi-label">Pedidos activos</div>
            <div class="db-kpi-val" data-val="<?= (int)$pedidosPendientes ?>">0</div>
            <div class="db-kpi-sub"><?= $pedidosPendientes > 0 ? 'requieren atención' : 'sin pendientes' ?></div>
        </a>

        <!-- Alertas de stock -->
        <a href="<?= URL_ADMIN ?>/stock/index.php" class="db-kpi" style="text-decoration:none;color:inherit;cursor:pointer">
            <div class="db-kpi-icon" style="<?= $totalAlertas > 0 ? 'background:#fef2f2;color:#dc2626' : 'background:#dcfce7;color:#16a34a' ?>"><i class="fa-solid fa-<?= $totalAlertas > 0 ? 'triangle-exclamation' : 'check' ?>"></i></div>
            <div class="db-kpi-label">Alertas de stock</div>
            <div class="db-kpi-val" data-val="<?= $totalAlertas ?>">0</div>
            <div class="db-kpi-sub">
                <?php if ($totalAlertas === 0): ?>
                    todo en rango
                <?php else: ?>
                    <?php if ($alertasSinStock): ?><?= $alertasSinStock ?> sin stock<?php endif; ?>
                    <?php if ($alertasSinStock && $alertasBajo): ?> · <?php endif; ?>
                    <?php if ($alertasBajo): ?><?= $alertasBajo ?> bajo mínimo<?php endif; ?>
                <?php endif; ?>
            </div>
        </a>

    </div>

    <div class="db-layout">
        <div class="db-col-main">

            <!-- STOCK COMPACTO CON ACCIONES -->
            <div class="db-card" style="padding:0;overflow:hidden">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px 12px;border-bottom:1px solid var(--border,#e8e7e4)">
                    <span style="font-size:.88rem;font-weight:700;color:#0f0f0f">Stock de productos</span>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="display:flex;gap:5px">
                            <button class="sf-pill active" data-sf="todos"     onclick="sfStock(this)">Todos</button>
                            <button class="sf-pill" data-sf="sin-stock"        onclick="sfStock(this)" style="color:#dc2626;border-color:#fecaca">Sin stock</button>
                            <button class="sf-pill" data-sf="bajo"             onclick="sfStock(this)" style="color:#d97706;border-color:#fde68a">Bajo</button>
                            <button class="sf-pill" data-sf="ok"               onclick="sfStock(this)" style="color:#16a34a;border-color:#bbf7d0">OK</button>
                        </div>
                        <a href="<?= URL_ADMIN ?>/stock/index.php" style="font-size:.75rem;font-weight:600;color:var(--brand,#c88e99);text-decoration:none;white-space:nowrap">Ver todo →</a>
                    </div>
                </div>
                <div style="overflow-x:auto">
                <table id="dtStock" style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <thead>
                        <tr style="background:#fafaf9;border-bottom:1px solid #e8e7e4">
                            <th style="padding:9px 16px;text-align:left;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888">Producto</th>
                            <th style="padding:9px 12px;text-align:center;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#2563eb">Congelado</th>
                            <th style="padding:9px 12px;text-align:center;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#ea580c">Horneado</th>
                            <th style="padding:9px 16px;text-align:center;font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // Necesitamos idproductos y receta para las acciones
                    $stockConAcciones = $pdo->query("
                        SELECT p.idproductos, p.nombre, p.recetas_idrecetas,
                            COALESCE(MAX(CASE WHEN sp.tipo_stock='CONGELADO' THEN sp.stock_actual END),0) AS cong_actual,
                            COALESCE(MAX(CASE WHEN sp.tipo_stock='HECHO'     THEN sp.stock_actual END),0) AS hech_actual,
                            COALESCE(MAX(CASE WHEN sp.tipo_stock='CONGELADO' THEN sp.stock_minimo END),0) AS cong_min,
                            COALESCE(MAX(CASE WHEN sp.tipo_stock='HECHO'     THEN sp.stock_minimo END),0) AS hech_min
                        FROM productos p
                        LEFT JOIN stock_productos sp ON sp.productos_idproductos = p.idproductos
                        WHERE p.tipo = 'producto'
                        GROUP BY p.idproductos ORDER BY p.nombre ASC
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($stockConAcciones as $p):
                        $ca = (float)$p['cong_actual']; $cm = (float)$p['cong_min'];
                        $ha = (float)$p['hech_actual']; $hm = (float)$p['hech_min'];
                        $congSS   = $ca <= 0; $congBajo = !$congSS && $ca <= $cm;
                        $hechSS   = $ha <= 0; $hechBajo = !$hechSS && $ha <= $hm;
                        $sfVal    = ($congSS || $hechSS) ? 'sin-stock' : (($congBajo || $hechBajo) ? 'bajo' : 'ok');
                        $congColor = $congSS ? '#dc2626' : ($congBajo ? '#d97706' : '#16a34a');
                        $hechColor = $hechSS ? '#dc2626' : ($hechBajo ? '#d97706' : '#16a34a');
                    ?>
                    <tr data-sf="<?= $sfVal ?>" style="border-bottom:1px solid #f0eeeb;" onmouseover="this.style.background='#fafaf9'" onmouseout="this.style.background=''">
                        <td style="padding:10px 16px;font-weight:600;font-size:.84rem"><?= htmlspecialchars($p['nombre']) ?></td>

                        <!-- Congelado -->
                        <td style="padding:8px 12px;text-align:center">
                            <span style="font-weight:700;color:<?= $congColor ?>;font-size:.88rem"><?= number_format($ca,0) ?></span>
                            <span style="font-size:.68rem;color:#aaa;margin-left:3px">/ <?= (int)$cm ?></span>
                        </td>

                        <!-- Horneado -->
                        <td style="padding:8px 12px;text-align:center">
                            <span style="font-weight:700;color:<?= $hechColor ?>;font-size:.88rem"><?= number_format($ha,0) ?></span>
                            <span style="font-size:.68rem;color:#aaa;margin-left:3px">/ <?= (int)$hm ?></span>
                        </td>

                        <td style="padding:8px 16px;text-align:center">
                            <a href="<?= URL_ADMIN ?>/stock/index.php?open=<?= $p['idproductos'] ?>"
                               style="padding:5px 14px;border-radius:6px;border:1.5px solid #e8e7e4;background:#fff;color:#3a3a3a;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-block;transition:all .15s"
                               onmouseover="this.style.borderColor='#0f0f0f';this.style.color='#0f0f0f'"
                               onmouseout="this.style.borderColor='#e8e7e4';this.style.color='#3a3a3a'">
                                Accionar →
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>


            <?php
            // Helper para renderizar mini-tabla de stock
            // $linkAccionar: destino del botón "Accionar →", $campoId: campo ID para ?open=
            function miniStockWidget($titulo, $link, $items, $campoNombre, $campoActual, $campoMinimo, $campoUnidad = null, $linkAccionar = null, $campoId = null) {
                if (!$linkAccionar) $linkAccionar = $link;
                $sinStock = 0; $bajo = 0; $ok = 0;
                foreach ($items as $r) {
                    $a = (float)$r[$campoActual]; $m = (float)$r[$campoMinimo];
                    if ($a <= 0)      $sinStock++;
                    elseif ($a <= $m) $bajo++;
                    else              $ok++;
                }
                // ID único para los filtros de esta tabla
                static $widgetIdx = 0; $widgetIdx++;
                $tid = 'msw'.$widgetIdx;
            ?>
            <div class="db-card" style="padding:0;overflow:hidden;display:flex;flex-direction:column">
                <!-- Header -->
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px 10px;border-bottom:1px solid var(--border,#e8e7e4);flex-wrap:wrap;gap:6px;flex-shrink:0">
                    <span style="font-size:.82rem;font-weight:700;color:#0f0f0f"><?= $titulo ?></span>
                    <a href="<?= $link ?>" style="font-size:.75rem;font-weight:600;color:var(--brand,#c88e99);text-decoration:none;white-space:nowrap;margin-left:auto">Ver todo →</a>
                </div>
                <!-- Filtros -->
                <div style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:#fafaf9;border-bottom:1px solid #f0eeeb;flex-wrap:wrap;flex-shrink:0">
                    <button class="sf-pill active" data-sf="todos"     onclick="mswFiltrar('<?= $tid ?>',this)" style="font-size:.7rem;padding:3px 10px">Todos</button>
                    <button class="sf-pill" data-sf="sin-stock"        onclick="mswFiltrar('<?= $tid ?>',this)" style="font-size:.7rem;padding:3px 10px;color:#dc2626;border-color:#fecaca">Sin stock <?php if($sinStock):?><strong>(<?= $sinStock ?>)</strong><?php endif?></button>
                    <button class="sf-pill" data-sf="bajo"             onclick="mswFiltrar('<?= $tid ?>',this)" style="font-size:.7rem;padding:3px 10px;color:#d97706;border-color:#fde68a">Bajo <?php if($bajo):?><strong>(<?= $bajo ?>)</strong><?php endif?></button>
                    <button class="sf-pill" data-sf="ok"               onclick="mswFiltrar('<?= $tid ?>',this)" style="font-size:.7rem;padding:3px 10px;color:#16a34a;border-color:#bbf7d0">OK <?php if($ok):?><strong>(<?= $ok ?>)</strong><?php endif?></button>
                </div>
                <?php if (empty($items)): ?>
                    <p style="padding:14px 16px;font-size:.8rem;color:#888">Sin datos.</p>
                <?php else: ?>
                <div style="overflow:auto;flex:1">
                <table id="<?= $tid ?>" style="width:100%;border-collapse:collapse;font-size:.78rem">
                    <thead style="position:sticky;top:0;z-index:1">
                        <tr style="background:#fafaf9;border-bottom:1px solid #f0eeeb">
                            <th style="padding:5px 16px;text-align:left;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#aaa;background:#fafaf9">Nombre</th>
                            <th style="padding:5px 12px;text-align:right;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#aaa;background:#fafaf9">Stock</th>
                            <th style="padding:5px 12px;text-align:center;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#aaa;background:#fafaf9"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $r):
                        $a = (float)$r[$campoActual]; $m = (float)$r[$campoMinimo];
                        $u = $campoUnidad ? ($r[$campoUnidad] ?? '') : '';
                        if ($a <= 0)      { $color = '#dc2626'; $sfv = 'sin-stock'; }
                        elseif ($a <= $m) { $color = '#d97706'; $sfv = 'bajo'; }
                        else              { $color = '#16a34a'; $sfv = 'ok'; }
                        $ref = max($m * 2, $a, 1);
                        $pct = min(100, round($a / $ref * 100));
                    ?>
                    <tr data-sf="<?= $sfv ?>" style="border-bottom:1px solid #f0eeeb" onmouseover="this.style.background='#fafaf9'" onmouseout="this.style.background=''">
                        <td style="padding:5px 16px;font-weight:600;color:#0f0f0f;font-size:.76rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px"><?= htmlspecialchars($r[$campoNombre]) ?></td>
                        <td style="padding:5px 12px;text-align:right">
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                                <div style="width:40px;height:3px;border-radius:4px;background:#e8e7e4;overflow:hidden">
                                    <div style="height:100%;width:<?= $pct ?>%;border-radius:4px;background:<?= $color ?>"></div>
                                </div>
                                <span style="font-weight:700;color:<?= $color ?>;font-size:.76rem"><?= number_format($a, 0, ',', '.') ?><?= $u ? ' '.strtoupper($u) : '' ?></span>
                            </div>
                        </td>
                        <td style="padding:5px 12px;text-align:center">
                            <?php
                        $itemId  = ($campoId && isset($r[$campoId])) ? (int)$r[$campoId] : 0;
                        $href    = $itemId ? $linkAccionar.'?open='.$itemId : $linkAccionar;
                        ?>
                        <a href="<?= $href ?>"
                               style="padding:3px 8px;border-radius:4px;border:1.5px solid #e8e7e4;background:#fff;color:#3a3a3a;font-size:.65rem;font-weight:600;text-decoration:none;display:inline-block;transition:all .15s;white-space:nowrap"
                               onmouseover="this.style.borderColor='#0f0f0f';this.style.color='#0f0f0f'"
                               onmouseout="this.style.borderColor='#e8e7e4';this.style.color='#3a3a3a'">
                                Accionar →
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
            <?php } // fin miniStockWidget ?>

        </div>

        <div class="db-col-side">

            <!-- ALERTAS ACCIONABLES -->
            <div class="db-card" style="padding:0;overflow:hidden">
                <div class="db-card-title" style="padding:14px 18px 12px;border-bottom:1px solid var(--border,#e8e7e4);margin:0">
                    Alertas del sistema
                </div>

                <?php
                $totalAlertas = count($productosSinStock) + count($productosBajos) + count($mpSinStock) + count($mpBajas);
                if ($totalAlertas === 0): ?>
                    <div style="padding:20px 18px;display:flex;align-items:center;gap:10px;color:#16a34a;font-size:.84rem;font-weight:600">
                        <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;flex-shrink:0"></span>
                        Todo en orden — sin alertas activas
                    </div>
                <?php else: ?>

                    <?php if (!empty($productosSinStock)): ?>
                    <!-- SECCIÓN: Sin stock → link a stock -->
                    <a href="<?= URL_ADMIN ?>/stock/index.php" class="db-alerta-seccion db-alerta-seccion--red">
                        <div class="db-alerta-header">
                            <span class="db-alerta-dot db-alerta-dot--red"></span>
                            <strong><?= count($productosSinStock) ?> producto<?= count($productosSinStock)>1?'s':'' ?> sin stock</strong>
                            <span class="db-alerta-cta">Ir a stock →</span>
                        </div>
                        <div class="db-alerta-items">
                        <?php foreach ($productosSinStock as $ps): ?>
                            <div class="db-alerta-item">
                                <span class="db-alerta-item-dot" style="background:#dc2626"></span>
                                <?= htmlspecialchars($ps['nombre']) ?>
                                <span class="db-alerta-item-tag"><?= strtolower($ps['tipo_stock']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($productosBajos)): ?>
                    <!-- SECCIÓN: Bajo mínimo → link a stock -->
                    <a href="<?= URL_ADMIN ?>/stock/index.php" class="db-alerta-seccion db-alerta-seccion--amber">
                        <div class="db-alerta-header">
                            <span class="db-alerta-dot db-alerta-dot--amber"></span>
                            <strong><?= count($productosBajos) ?> bajo el mínimo</strong>
                            <span class="db-alerta-cta">Ir a stock →</span>
                        </div>
                        <div class="db-alerta-items">
                        <?php foreach ($productosBajos as $pb): ?>
                            <div class="db-alerta-item">
                                <span class="db-alerta-item-dot" style="background:#d97706"></span>
                                <?= htmlspecialchars($pb['nombre']) ?>
                                <span class="db-alerta-item-tag"><?= number_format($pb['stock_actual'],0) ?>/<?= number_format($pb['stock_minimo'],0) ?> uds</span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($mpSinStock)): ?>
                    <!-- SECCIÓN: MP sin stock → link a materias primas -->
                    <a href="<?= URL_ADMIN ?>/materias_primas/index.php" class="db-alerta-seccion db-alerta-seccion--red">
                        <div class="db-alerta-header">
                            <span class="db-alerta-dot db-alerta-dot--red"></span>
                            <strong><?= count($mpSinStock) ?> materia<?= count($mpSinStock)>1?'s':'' ?> prima sin stock</strong>
                            <span class="db-alerta-cta">Ver MP →</span>
                        </div>
                        <div class="db-alerta-items">
                        <?php foreach ($mpSinStock as $mp): ?>
                            <div class="db-alerta-item">
                                <span class="db-alerta-item-dot" style="background:#dc2626"></span>
                                <?= htmlspecialchars($mp['nombre']) ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($mpBajas)): ?>
                    <!-- SECCIÓN: MP baja → link a proveedores -->
                    <a href="<?= URL_ADMIN ?>/proveedor/index.php" class="db-alerta-seccion db-alerta-seccion--amber">
                        <div class="db-alerta-header">
                            <span class="db-alerta-dot db-alerta-dot--amber"></span>
                            <strong><?= count($mpBajas) ?> materia prima baja — comprar</strong>
                            <span class="db-alerta-cta">Ver proveedores →</span>
                        </div>
                        <div class="db-alerta-items">
                        <?php foreach ($mpBajas as $mp): ?>
                            <div class="db-alerta-item">
                                <span class="db-alerta-item-dot" style="background:#d97706"></span>
                                <?= htmlspecialchars($mp['nombre']) ?>
                                <span class="db-alerta-item-tag"><?= number_format($mp['stock_actual'],2) ?>/<?= number_format($mp['stock_minimo'],2) ?></span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </a>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

            <div class="db-card db-audit-card">
                <div class="db-card-title">
                    <i class="fa-solid fa-clipboard-list"></i>
                    Actividad reciente
                    <a href="<?= URL_ASSETS ?>/configuraciones/auditoria.php" class="db-card-link">Ver todo →</a>
                </div>
                <?php if (empty($auditoriasRecientes)): ?>
                    <p class="db-empty">Sin actividad registrada.</p>
                <?php else: ?>
                <div class="db-audit-list">
                <?php foreach ($auditoriasRecientes as $a):
                    $accionIconos = [
                        'crear'    => ['fa-plus-circle','audit-create'],
                        'editar'   => ['fa-pen','audit-edit'],
                        'eliminar' => ['fa-trash','audit-delete'],
                        'login'    => ['fa-right-to-bracket','audit-login'],
                        'logout'   => ['fa-right-from-bracket','audit-logout'],
                        'guardar'  => ['fa-floppy-disk','audit-edit'],
                        'producir' => ['fa-industry','audit-create'],
                    ];
                    $accionLower = strtolower($a['accion'] ?? '');
                    [$icono,$clase] = $accionIconos[$accionLower] ?? ['fa-circle-dot','audit-default'];
                    $hace = '';
                    if ($a['created_at']) {
                        $diff = time() - strtotime($a['created_at']);
                        if ($diff < 60)        $hace = 'hace ' . $diff . 's';
                        elseif ($diff < 3600)  $hace = 'hace ' . floor($diff/60) . 'min';
                        elseif ($diff < 86400) $hace = 'hace ' . floor($diff/3600) . 'h';
                        else                   $hace = date('d/m', strtotime($a['created_at']));
                    }
                ?>
                    <div class="db-audit-item">
                        <div class="db-audit-icon <?= $clase ?>"><i class="fa-solid <?= $icono ?>"></i></div>
                        <div class="db-audit-body">
                            <div class="db-audit-title">
                                <?= htmlspecialchars($a['usuario_nombre'] ?? 'Sistema') ?>
                                <span class="db-audit-accion"><?= htmlspecialchars($a['accion'] ?? '') ?></span>
                            </div>
                            <div class="db-audit-desc"><?= htmlspecialchars(mb_strimwidth($a['descripcion'] ?? $a['modulo'] ?? '', 0, 55, '…')) ?></div>
                        </div>
                        <div class="db-audit-time"><?= $hace ?></div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ── GRID STOCKS SECUNDARIOS ─────────────────────────────────── -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:18px;grid-auto-rows:460px;align-items:stretch">
        <?php miniStockWidget(
            'Stock de Toppings',
            URL_ADMIN.'/stock/toppings/index.php',
            $stockToppings, 'nombre', 'stock_actual', 'stock_minimo',
            null, null, 'idtoppings'
        ); ?>
        <?php miniStockWidget(
            'Packaging / Cajas',
            URL_ADMIN.'/packaging/index.php',
            $stockPackaging, 'nombre', 'stock_actual', 'stock_minimo',
            null, null, 'idpackaging'
        ); ?>
        <?php miniStockWidget(
            'Materias Primas',
            URL_ADMIN.'/materias_primas/index.php',
            $stockMP, 'nombre', 'stock_actual', 'stock_minimo', 'unidad',
            URL_ADMIN.'/proveedor/index.php'  // Accionar → va a proveedores (sin open)
        ); ?>
    </div>

</div></div>
</div><!-- /view-dashboard -->


<!-- ═══════════════ VISTA: ANALÍTICA ═══════════════ -->
<div id="view-analitica" style="display:none">
<div class="ana-wrap">

  <div class="ana-header">
    <div>
      <h1>Analítica de Ventas</h1>
      <p class="ana-subtitle">Ingresos, costos y beneficios — <span id="lblPeriodo">—</span></p>
    </div>
    <button class="ana-btn-refresh" onclick="cargar()">
      <i class="fa-solid fa-rotate-right"></i> Actualizar
    </button>
  </div>

  <!-- FILTROS GLOBALES -->
  <div class="ana-filtros-wrap">
    <div class="ana-filtros">
      <div class="flt-pills">
        <button class="flt-pill" data-modo="hoy"             onclick="setModo(this)">Hoy</button>
        <button class="flt-pill" data-modo="semana_actual"   onclick="setModo(this)">Esta semana</button>
        <button class="flt-pill" data-modo="semana_anterior" onclick="setModo(this)">Semana pasada</button>
        <button class="flt-pill active" data-modo="mes_actual" onclick="setModo(this)">Este mes</button>
        <button class="flt-pill" data-modo="mes_anterior"    onclick="setModo(this)">Mes anterior</button>
      </div>
      <div class="flt-mes-sel">
        <select id="mesSelect" class="ana-select-sm" onchange="setModoMes()">
          <option value="0">Todos los meses</option>
          <option value="1">Enero</option><option value="2">Febrero</option>
          <option value="3">Marzo</option><option value="4">Abril</option>
          <option value="5">Mayo</option><option value="6">Junio</option>
          <option value="7">Julio</option><option value="8">Agosto</option>
          <option value="9">Septiembre</option><option value="10">Octubre</option>
          <option value="11">Noviembre</option><option value="12">Diciembre</option>
        </select>
        <select id="anioSelect" class="ana-select-sm" onchange="onAnioChange()">
          <?php for($y=date('Y'); $y>=2023; $y--): ?>
          <option value="<?=$y?>" <?=$y==date('Y')?'selected':''?>><?=$y?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
    <div class="ana-filtros flt-rango">
      <span class="flt-rango-label"><i class="fa-regular fa-calendar-days"></i> Rango:</span>
      <input type="date" id="fltDesde" class="ana-select-sm" oninput="setModoRango()">
      <span style="color:var(--ink-soft);font-size:.8rem">→</span>
      <input type="date" id="fltHasta" class="ana-select-sm" oninput="setModoRango()">
      <button class="flt-pill" id="btnRangoReset" onclick="resetRango()" style="display:none;padding:5px 10px">✕</button>
      <span style="flex:1"></span>
      <span class="flt-rango-label"><i class="fa-regular fa-calendar"></i> Día exacto:</span>
      <input type="date" id="fltDia" class="ana-select-sm" oninput="setModoDia()">
      <button class="flt-pill" id="btnDiaReset" onclick="resetDia()" style="display:none;padding:5px 10px">✕</button>
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card kpi-blue">
      <div class="kpi-ico kpi-ico-blue"><i class="fa-solid fa-peso-sign"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Ingresos del período</div>
        <div class="kpi-value" id="k-ventas-periodo">—</div>
        <div class="kpi-sub" id="k-pedidos-periodo">—</div>
      </div>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-ico kpi-ico-purple"><i class="fa-solid fa-receipt"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Ticket promedio</div>
        <div class="kpi-value" id="k-ticket-prom">—</div>
        <div class="kpi-sub">Por pedido entregado</div>
      </div>
    </div>
    <div class="kpi-card" id="kpi-beneficio-card">
      <div class="kpi-ico" id="k-benef-ico"><i class="fa-solid fa-scale-balanced"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Beneficio estimado</div>
        <div class="kpi-value" id="k-beneficio">—</div>
        <div class="kpi-sub">Ingresos − Costos</div>
      </div>
    </div>
    <div class="kpi-card kpi-retiro">
      <div class="kpi-ico kpi-ico-green"><i class="fa-solid fa-store"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Retiros en local</div>
        <div class="kpi-value" id="k-retiro-cant">—</div>
        <div class="kpi-sub" id="k-retiro-total">—</div>
      </div>
    </div>
    <div class="kpi-card kpi-envio">
      <div class="kpi-ico kpi-ico-blue"><i class="fa-solid fa-motorcycle"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Pedidos con envío</div>
        <div class="kpi-value" id="k-envio-cant">—</div>
        <div class="kpi-sub" id="k-envio-total">—</div>
      </div>
    </div>
  </div>

  <!-- GRÁFICO INGRESOS + RESUMEN -->
  <div class="ana-row-2" style="margin-bottom:1.4rem">
    <div class="ana-section flex-1">
      <div class="ana-section-header">
        <h2>Ingresos por día</h2>
        <span class="chart-note">Solo ventas entregadas — clic en barra para ver detalle</span>
      </div>
      <div class="sf-wrap" id="sf-ingresos" style="display:none">
        <div class="sf-bar">
          <span class="sf-lbl">Sub-período:</span>
          <div class="sf-pills" id="sfp-ingresos"></div>
          <button class="sf-reset" id="sfr-ingresos" onclick="resetSubfiltro('ingresos')" style="display:none">← Todo el período</button>
        </div>
      </div>
      <div class="chart-wrap" id="chartIngresosWrap">
        <canvas id="chartIngresos"></canvas>
      </div>
    </div>
    <div class="ana-section" style="width:300px;flex-shrink:0">
      <div class="ana-section-header"><h2>Resumen del período</h2></div>
      <div class="chart-wrap" style="height:200px">
        <canvas id="chartResumen"></canvas>
      </div>
      <div class="resumen-totales" id="resumen-totales"></div>
    </div>
  </div>

  <!-- PRODUCTOS + MÉTODO DE PAGO -->
  <div class="ana-row-2" style="margin-bottom:1.4rem">
    <div class="ana-section flex-1">
      <div class="ana-section-header">
        <h2>Productos más vendidos</h2>
        <span class="chart-note">Clic en fila para ver detalle</span>
      </div>
      <div class="sf-wrap" id="sf-productos" style="display:none">
        <div class="sf-bar">
          <span class="sf-lbl">Sub-período:</span>
          <div class="sf-pills" id="sfp-productos"></div>
          <button class="sf-reset" id="sfr-productos" onclick="resetSubfiltro('productos')" style="display:none">← Todo el período</button>
        </div>
      </div>
      <div class="prod-filter-wrap">
        <i class="fa-solid fa-magnifying-glass" style="color:var(--ink-soft);font-size:.8rem"></i>
        <input type="text" id="prodFiltroInput" class="prod-filter-input"
          placeholder="Filtrar por nombre..." oninput="filtrarProductos(this.value)">
        <button id="prodFiltroReset" onclick="resetFiltroProducto()" style="display:none">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <table class="ana-table">
        <thead><tr><th>#</th><th>Producto</th><th>Unidades</th><th>Ingresos</th></tr></thead>
        <tbody id="tb-productos"><tr><td colspan="4" class="ana-loading">Cargando...</td></tr></tbody>
      </table>
      <div id="prod-filter-note" style="display:none;font-size:.75rem;color:var(--ink-soft);padding:8px 0 0;text-align:center"></div>
    </div>
    <div class="ana-section w-280">
      <div class="ana-section-header"><h2>Método de pago</h2></div>
      <div class="sf-wrap" id="sf-pago" style="display:none">
        <div class="sf-bar">
          <span class="sf-lbl">Sub-período:</span>
          <div class="sf-pills" id="sfp-pago"></div>
          <button class="sf-reset" id="sfr-pago" onclick="resetSubfiltro('pago')" style="display:none">← Todo el período</button>
        </div>
      </div>
      <div class="chart-wrap-sm"><canvas id="chartPago"></canvas></div>
      <div id="pago-lista" class="pago-lista"></div>
    </div>
  </div>

  <!-- HEATMAP -->
  <div class="ana-section" id="sec-heatmap">
    <div class="ana-section-header">
      <h2>Concentración de pedidos por día y hora</h2>
      <span class="chart-note">Pedidos entregados del período</span>
    </div>
    <div class="sf-wrap" id="sf-heatmap" style="display:none">
      <span class="sf-label">SUB-PERÍODO:</span>
      <div class="sf-pills" id="sfp-heatmap"></div>
      <button class="sf-reset" id="sfr-heatmap" onclick="resetSubfiltro('heatmap')" style="display:none">← Todo el período</button>
    </div>
    <div class="hm-wrap" id="hmWrap"><div class="ana-loading">Cargando...</div></div>
  </div>

</div>

<div class="ana-modal-overlay" id="anaModal" onclick="if(event.target===this)cerrarModal()">
  <div class="ana-modal">
    <div class="ana-modal-header">
      <h3 id="anaModalTitle">Detalle</h3>
      <button onclick="cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="ana-modal-body" id="anaModalBody"><div class="ana-loading">Cargando...</div></div>
  </div>
</div>
</div><!-- /view-analitica -->


<!-- ═══════════════ VISTA: FINANZAS ═══════════════ -->
<div id="view-finanzas" style="display:none">
<div class="ana-wrap">

  <div class="ana-header">
    <div>
      <h1>Finanzas</h1>
      <p class="ana-subtitle">Costos, beneficios y movimientos — <span id="fin-lblPeriodo">—</span></p>
    </div>
    <button class="ana-btn-refresh" onclick="cargarFinanzas()">
      <i class="fa-solid fa-rotate-right"></i> Actualizar
    </button>
  </div>

  <!-- FILTROS FINANZAS (mismo componente, IDs distintos) -->
  <div class="ana-filtros-wrap">
    <div class="ana-filtros">
      <div class="flt-pills">
        <button class="flt-pill-fin" data-modo="hoy"             onclick="setModoFin(this)">Hoy</button>
        <button class="flt-pill-fin" data-modo="semana_actual"   onclick="setModoFin(this)">Esta semana</button>
        <button class="flt-pill-fin" data-modo="semana_anterior" onclick="setModoFin(this)">Semana pasada</button>
        <button class="flt-pill-fin active" data-modo="mes_actual" onclick="setModoFin(this)">Este mes</button>
        <button class="flt-pill-fin" data-modo="mes_anterior"    onclick="setModoFin(this)">Mes anterior</button>
      </div>
      <div class="flt-mes-sel">
        <select id="fin-mesSelect" class="ana-select-sm" onchange="setModoMesFin()">
          <option value="0">Todos los meses</option>
          <option value="1">Enero</option><option value="2">Febrero</option>
          <option value="3">Marzo</option><option value="4">Abril</option>
          <option value="5">Mayo</option><option value="6">Junio</option>
          <option value="7">Julio</option><option value="8">Agosto</option>
          <option value="9">Septiembre</option><option value="10">Octubre</option>
          <option value="11">Noviembre</option><option value="12">Diciembre</option>
        </select>
        <select id="fin-anioSelect" class="ana-select-sm" onchange="onAnioChangeFin()">
          <?php for($y=date('Y'); $y>=2023; $y--): ?>
          <option value="<?=$y?>" <?=$y==date('Y')?'selected':''?>><?=$y?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
    <div class="ana-filtros flt-rango">
      <span class="flt-rango-label"><i class="fa-regular fa-calendar-days"></i> Rango:</span>
      <input type="date" id="fin-fltDesde" class="ana-select-sm" oninput="setModoRangoFin()">
      <span style="color:#888;font-size:.8rem">→</span>
      <input type="date" id="fin-fltHasta" class="ana-select-sm" oninput="setModoRangoFin()">
      <button class="flt-pill-fin" id="fin-btnRangoReset" onclick="resetRangoFin()" style="display:none;padding:5px 10px">✕</button>
      <span style="flex:1"></span>
      <span class="flt-rango-label"><i class="fa-regular fa-calendar"></i> Día exacto:</span>
      <input type="date" id="fin-fltDia" class="ana-select-sm" oninput="setModoDiaFin()">
      <button class="flt-pill-fin" id="fin-btnDiaReset" onclick="resetDiaFin()" style="display:none;padding:5px 10px">✕</button>
    </div>
  </div>

  <!-- KPIs FINANCIEROS -->
  <div class="kpi-grid" style="margin-bottom:1.4rem">
    <div class="kpi-card kpi-costo">
      <div class="kpi-ico kpi-ico-red"><i class="fa-solid fa-cart-shopping"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Inversión en materiales</div>
        <div class="kpi-value kpi-red" id="fin-k-costo">—</div>
        <div class="kpi-sub">Compras del período</div>
      </div>
    </div>
    <div class="kpi-card" style="border-left-color:#2563eb">
      <div class="kpi-ico" style="background:#eff6ff;color:#2563eb"><i class="fa-solid fa-arrow-trend-up"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Ingresos del período</div>
        <div class="kpi-value" id="fin-k-ingresos">—</div>
        <div class="kpi-sub" id="fin-k-ingresos-sub">—</div>
      </div>
    </div>
    <div class="kpi-card kpi-beneficio-card" id="fin-kpi-beneficio-card">
      <div class="kpi-ico" id="fin-k-benef-ico"><i class="fa-solid fa-scale-balanced"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Beneficio estimado</div>
        <div class="kpi-value" id="fin-k-beneficio">—</div>
        <div class="kpi-sub">Ingresos − Costos</div>
      </div>
    </div>
    <div class="kpi-card" style="border-left-color:#c88e99">
      <div class="kpi-ico" style="background:#f9edf0;color:#c88e99"><i class="fa-solid fa-percent"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Margen bruto</div>
        <div class="kpi-value" id="fin-k-margen">—</div>
        <div class="kpi-sub">Beneficio / Ingresos</div>
      </div>
    </div>
  </div>

  <!-- INVERSIÓN EN MATERIALES -->
  <div class="ana-section" id="fin-sec-materiales" style="margin-bottom:1.4rem">
    <div class="ana-section-header">
      <h2>Inversión en materiales</h2>
      <span id="fin-costo-badge" class="costo-badge">—</span>
    </div>
    <div class="mp-bars" id="fin-mp-bars"><div class="ana-loading">Cargando...</div></div>
  </div>

  <!-- DEBE Y HABER -->
  <div class="ana-section" id="fin-sec-debe-haber">
    <div class="ana-section-header">
      <h2>Debe y Haber</h2>
      <span class="chart-note">Movimientos del período ordenados por fecha</span>
    </div>
    <div class="dh-resumen" id="fin-dh-resumen"></div>
    <div style="overflow-x:auto">
      <table class="ana-table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Concepto</th>
            <th>Detalle</th>
            <th style="text-align:right;color:#c0392b">Debe (Costo)</th>
            <th style="text-align:right;color:#16a34a">Haber (Ingreso)</th>
            <th style="text-align:right">Saldo acum.</th>
          </tr>
        </thead>
        <tbody id="fin-tb-debe-haber">
          <tr><td colspan="6" class="ana-loading">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div><!-- /view-finanzas -->


<script>
// ── FORMATEADORES (declarados primero para que countUp los pueda usar) ──
var fmt  = function(n) { return '$' + parseFloat(n||0).toLocaleString('es-AR',{minimumFractionDigits:0,maximumFractionDigits:0}); };
var fmtK = function(n) {
    var v = Math.abs(parseFloat(n||0));
    if (v >= 1000000) return (parseFloat(n)/1000000).toFixed(1).replace('.',',')+' M';
    if (v >= 1000)    return (parseFloat(n)/1000).toFixed(0)+'k';
    return fmt(n);
};

// ── ANIMACIÓN DE NÚMEROS ──────────────────────────────────────────────
function countUp(el, target, isAmount, delay) {
    if (!el) return;
    delay = delay || 0;
    var finalText = isAmount ? fmt(target) : Math.round(target).toLocaleString('es-AR');
    setTimeout(function() {
        var dur = 700, start = Date.now();
        var tick = function() {
            var pct  = Math.min((Date.now() - start) / dur, 1);
            var ease = 1 - Math.pow(1 - pct, 3);
            var cur  = target * ease;
            el.textContent = isAmount
                ? '$' + Math.round(cur).toLocaleString('es-AR')
                : Math.round(cur).toLocaleString('es-AR');
            if (pct < 1) requestAnimationFrame(tick);
            else el.textContent = finalText;
        };
        requestAnimationFrame(tick);
    }, delay);
}

// ── DASHBOARD CHART ───────────────────────────────────────────────────
var chartProd = null;
function initChartProd() {
    if (chartProd) { chartProd.resize(); return; }
    // Animar KPIs del dashboard
    document.querySelectorAll('.db-kpi-val[data-val]').forEach(function(el, i) {
        var v    = parseFloat(el.dataset.val) || 0;
        var isMoney = el.dataset.money === '1';
        countUp(el, v, isMoney, i * 80);
    });
    var canvas = document.getElementById('chartProd');
    if (!canvas) return;
    chartProd = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Lotes',
                data: <?= json_encode($chartData) ?>,
                backgroundColor: 'rgba(200,142,153,0.12)',
                borderColor: '#c88e99',
                borderWidth: 2, borderRadius: 8, borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e1e1e',
                    titleFont: { family: 'Speedee', size: 12 },
                    bodyFont:  { family: 'Speedee', size: 13 },
                    callbacks: { label: function(c) { return ' ' + c.raw + ' lote' + (c.raw !== 1 ? 's' : ''); } }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false },
                     ticks: { font: { size: 11, family: 'Speedee' }, color: '#9e9e9a' } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 }, color: '#9e9e9a' },
                     grid: { color: 'rgba(0,0,0,.05)' }, border: { display: false } }
            }
        }
    });
}

var _DASH_BAJAR_DUR = 320;

function countDown(el, from, isAmount) {
    if (!el || from <= 0) { if (el) el.textContent = isAmount ? '$0' : '0'; return; }
    var dur = _DASH_BAJAR_DUR, start = Date.now();
    (function tick() {
        var pct  = Math.min((Date.now() - start) / dur, 1);
        var ease = pct * pct; // easeInQuad
        var cur  = from * (1 - ease);
        el.textContent = isAmount ? '$' + Math.round(cur).toLocaleString('es-AR') : Math.round(cur).toString();
        if (pct < 1) requestAnimationFrame(tick);
        else el.textContent = isAmount ? '$0' : '0';
    })();
}

// ── ANALÍTICA ─────────────────────────────────────────────────────────
var chartIngresos=null, chartResumen=null, chartPago=null;
var _modo='mes_actual', _lastData=null;
var _kpiDash = { hoy:0, semana:0, periodo:0, ticket:0, benef:0 };

function desactivarPills() { document.querySelectorAll('.flt-pill').forEach(function(b){b.classList.remove('active');}); }

function setModo(btn) {
    desactivarPills(); btn.classList.add('active');
    _modo = btn.dataset.modo;
    document.getElementById('mesSelect').value = '0';
    limpiarRangoUI(); cargar();
}
function setModoMes() {
    desactivarPills(); limpiarRangoUI();
    var mes = document.getElementById('mesSelect').value;
    _modo = (!mes || mes==='0') ? 'anio_completo' : 'mes_especifico';
    cargar();
}
function onAnioChange() {
    desactivarPills(); limpiarRangoUI();
    var mes = document.getElementById('mesSelect').value;
    _modo = (!mes || mes==='0') ? 'anio_completo' : 'mes_especifico';
    cargar();
}
function setModoRango() {
    var desde=document.getElementById('fltDesde').value, hasta=document.getElementById('fltHasta').value;
    if (!desde||!hasta) return;
    desactivarPills();
    document.getElementById('mesSelect').value='0';
    document.getElementById('fltDia').value='';
    document.getElementById('btnDiaReset').style.display='none';
    document.getElementById('btnRangoReset').style.display='';
    _modo='rango_custom'; cargar();
}
function setModoDia() {
    var dia=document.getElementById('fltDia').value;
    if (!dia) return;
    desactivarPills();
    document.getElementById('mesSelect').value='0';
    document.getElementById('fltDesde').value='';
    document.getElementById('fltHasta').value='';
    document.getElementById('btnRangoReset').style.display='none';
    document.getElementById('btnDiaReset').style.display='';
    _modo='dia_especifico'; cargar();
}
function resetRango() {
    document.getElementById('fltDesde').value='';
    document.getElementById('fltHasta').value='';
    document.getElementById('btnRangoReset').style.display='none';
    document.querySelector('.flt-pill[data-modo="mes_actual"]').classList.add('active');
    _modo='mes_actual'; cargar();
}
function resetDia() {
    document.getElementById('fltDia').value='';
    document.getElementById('btnDiaReset').style.display='none';
    document.querySelector('.flt-pill[data-modo="mes_actual"]').classList.add('active');
    _modo='mes_actual'; cargar();
}
function limpiarRangoUI() {
    ['fltDesde','fltHasta','fltDia'].forEach(function(id){document.getElementById(id).value='';});
    document.getElementById('btnRangoReset').style.display='none';
    document.getElementById('btnDiaReset').style.display='none';
}

async function cargar() {
    countDown(document.getElementById('k-ventas-periodo'), _kpiDash.periodo, true);
    countDown(document.getElementById('k-ticket-prom'),    _kpiDash.ticket || 0, true);
    countDown(document.getElementById('k-beneficio'),      Math.abs(_kpiDash.benef || 0), true);
    _kpiDash = { hoy:0, semana:0, periodo:0, ticket:0, benef:0 };
    // Resetear contadores de retiro/envío
    document.getElementById('k-retiro-cant').textContent  = '—';
    document.getElementById('k-retiro-total').textContent = '—';
    document.getElementById('k-envio-cant').textContent   = '—';
    document.getElementById('k-envio-total').textContent  = '—';

    var tbProd = document.getElementById('tb-productos');
    if (tbProd) tbProd.innerHTML='<tr><td colspan="4" class="ana-loading">Cargando...</td></tr>';

    var url = 'analitica/api/get_analitica.php?modo='+_modo;
    var anio=document.getElementById('anioSelect').value;
    var mes =document.getElementById('mesSelect').value;
    if (_modo==='mes_especifico'||_modo==='anio_completo') url+='&mes='+mes+'&anio='+anio;
    else if (_modo==='rango_custom') url+='&desde='+document.getElementById('fltDesde').value+'&hasta='+document.getElementById('fltHasta').value;
    else if (_modo==='dia_especifico') url+='&dia='+document.getElementById('fltDia').value;

    try {
        var res=await fetch(url), txt=await res.text();
        var data;
        try { data=JSON.parse(txt); } catch(e){ showToast('Error en el servidor','err'); return; }
        if (data.error){ showToast('Error: '+data.error,'err'); return; }
        _lastData=data;
        document.getElementById('lblPeriodo').textContent=data.periodo||'';
        renderKPIs(data.kpis, data.costos, data.retiro_envio||{});
        renderChartIngresos(data.labels, data.ingresos);
        renderChartResumen(data.kpis, data.costos);
        renderTopProductos(data.top_productos, true);
        renderPago(data.por_pago);
        renderHeatmap(data.heatmap||{});
        actualizarSubfiltros();
    } catch(e){ showToast('Error de conexión: '+e.message,'err'); }
}

// ── KPIs ──────────────────────────────────────────────────────────────
function renderKPIs(k, c, re) {
    var peds   = parseInt(k.pedidos_periodo)  || 0;
    var ing    = parseFloat(k.ventas_periodo) || 0;
    var costo  = parseFloat(c.costo_periodo)  || 0;
    var ticket = peds > 0 ? ing / peds : 0;
    var benef  = ing - costo;

    countUp(document.getElementById('k-ventas-periodo'), ing,    true, _DASH_BAJAR_DUR);
    countUp(document.getElementById('k-ticket-prom'),    ticket, true, _DASH_BAJAR_DUR);

    var elB  = document.getElementById('k-beneficio');
    var card = document.getElementById('kpi-beneficio-card');
    var ico  = document.getElementById('k-benef-ico');
    countUp(elB, Math.abs(benef), true, _DASH_BAJAR_DUR);
    setTimeout(function() {
        var d = _DASH_BAJAR_DUR;
        var entregados = parseInt(k.pedidos_entregados) || 0;
        document.getElementById('k-pedidos-periodo').innerHTML =
            '<strong>' + peds + '</strong> pedido'+(peds!==1?'s':'') +
            ' &nbsp;·&nbsp; <span style="color:#16a34a;font-weight:700">' +
            entregados + ' entregado'+(entregados!==1?'s':'') + '</span>';
        if (elB && card && ico) {
            if (benef>=0) {
                elB.style.color='#1a7a4a'; card.style.borderTopColor='#1a7a4a'; card.style.background='#f0faf4';
                ico.innerHTML='<i class="fa-solid fa-arrow-trend-up" style="color:#1a7a4a"></i>';
            } else {
                elB.style.color='#c0392b'; card.style.borderTopColor='#c0392b'; card.style.background='#fff8f7';
                ico.innerHTML='<i class="fa-solid fa-arrow-trend-down" style="color:#c0392b"></i>';
            }
        }
    }, _DASH_BAJAR_DUR);

    // Retiro / Envío del período seleccionado
    if (re && re.retiro !== undefined) {
        var r=re.retiro, env=re.envio||{cantidad:0,total:0};
        setTimeout(function() {
            document.getElementById('k-retiro-cant').textContent = r.cantidad + ' pedido'+(r.cantidad!=1?'s':'');
            document.getElementById('k-retiro-total').textContent = fmt(r.total);
            document.getElementById('k-envio-cant').textContent = env.cantidad + ' pedido'+(env.cantidad!=1?'s':'');
            document.getElementById('k-envio-total').textContent = fmt(env.total);
        }, _DASH_BAJAR_DUR);
    }

    _kpiDash = { hoy: 0, semana: 0, periodo: ing };
}

// ── GRÁFICO INGRESOS ─────────────────────────────────────────────────
function renderChartIngresos(labels, ingresos) {
    var wrap = document.getElementById('chartIngresosWrap');
    if (chartIngresos) { chartIngresos.destroy(); chartIngresos=null; }
    if (!ingresos.some(function(v){return v>0;})) {
        wrap.innerHTML='<div class="chart-empty">Sin ventas entregadas en el período</div>'; return;
    }
    if (!document.getElementById('chartIngresos')) wrap.innerHTML='<canvas id="chartIngresos"></canvas>';
    chartIngresos=new Chart(document.getElementById('chartIngresos').getContext('2d'),{
        type:'bar',
        data:{labels:labels,datasets:[{label:'Ingresos',data:ingresos,
            backgroundColor:'rgba(55,138,221,.18)',borderColor:'#378ADD',borderWidth:1.5,borderRadius:5}]},
        options:{responsive:true,maintainAspectRatio:false,
            onClick:function(_,els){if(els[0]) abrirDetalleDia(labels[els[0].index],ingresos[els[0].index]);},
            plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return ' '+fmt(c.parsed.y);}}}},
            scales:{x:{grid:{display:false},ticks:{maxTicksLimit:18,font:{size:10}}},
                    y:{grid:{color:'rgba(0,0,0,.04)'},ticks:{callback:function(v){return fmtK(v);},font:{size:10}},beginAtZero:true}}}
    });
}

// ── GRÁFICO RESUMEN ──────────────────────────────────────────────────
function renderChartResumen(k, c) {
    var ing=parseFloat(k.ventas_periodo), cost=parseFloat(c.costo_periodo), benef=ing-cost;
    document.getElementById('resumen-totales').innerHTML=
        '<div class="resumen-fila"><span class="rf-dot" style="background:#378ADD"></span><span>Ingresos</span><strong>'+fmt(ing)+'</strong></div>'+
        '<div class="resumen-fila"><span class="rf-dot" style="background:#c88e99"></span><span>Costos mat.</span><strong style="color:#c88e99">'+fmt(cost)+'</strong></div>'+
        '<div class="resumen-fila resumen-total"><span></span><span>Beneficio est.</span><strong style="color:'+(benef>=0?'#1a7a4a':'#c0392b')+'">'+fmt(benef)+'</strong></div>';
    if (chartResumen) chartResumen.destroy();
    chartResumen=new Chart(document.getElementById('chartResumen').getContext('2d'),{
        type:'bar',
        data:{labels:['Ingresos','Costos','Beneficio'],datasets:[{
            data:[ing,cost,Math.abs(benef)],
            backgroundColor:['rgba(55,138,221,.7)','rgba(200,142,153,.7)',benef>=0?'rgba(26,122,74,.7)':'rgba(192,57,43,.4)'],
            borderColor:['#378ADD','#c88e99',benef>=0?'#1a7a4a':'#c0392b'],borderWidth:1.5,borderRadius:5}]},
        options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return ' '+fmt(c.parsed.y);}}}},
            scales:{x:{grid:{display:false},ticks:{font:{size:11,weight:'600'}}},
                    y:{grid:{color:'rgba(0,0,0,.04)'},ticks:{callback:function(v){return fmtK(v);},font:{size:10}},beginAtZero:true}}}
    });
}

// ── TOP PRODUCTOS ─────────────────────────────────────────────────────
var _todosProductos=[];
function renderTopProductos(prods, guardar) {
    if (guardar) _todosProductos=prods;
    var tbody=document.getElementById('tb-productos');
    if (!prods||!prods.length) { tbody.innerHTML='<tr><td colspan="4" class="ana-loading">Sin ventas en el período</td></tr>'; return; }
    var maxU=Math.max.apply(null,prods.map(function(p){return parseFloat(p.unidades);}));
    tbody.innerHTML=prods.map(function(p,i){
        var rankCls = i===0?'rank-1':i===1?'rank-2':i===2?'rank-3':'';
        var w = Math.round(parseFloat(p.unidades)/maxU*100);
        return '<tr class="tr-clickable" onclick="abrirDetalleProducto(\''+p.nombre.replace(/'/g,"\\'")+'\')">'
            +'<td><span class="rank-badge '+rankCls+'">'+(i+1)+'</span></td>'
            +'<td><div class="prod-nombre">'+p.nombre+'</div>'
            +'<div class="prod-bar-wrap"><div class="prod-bar" style="width:'+w+'%"></div></div></td>'
            +'<td><strong>'+parseInt(p.unidades)+'</strong> u.</td>'
            +'<td>'+fmt(p.ingresos)+'</td></tr>';
    }).join('');
}

// ── MÉTODO DE PAGO ────────────────────────────────────────────────────
function renderPago(pagos) {
    if (chartPago) { chartPago.destroy(); chartPago=null; }
    if (!pagos||!pagos.length) {
        document.getElementById('pago-lista').innerHTML='<div class="ana-loading" style="padding:12px">Sin ventas entregadas</div>'; return;
    }
    var colors=['#378ADD','#1a7a4a','#e67e22','#8e44ad','#e74c3c','#16a085'];
    chartPago=new Chart(document.getElementById('chartPago').getContext('2d'),{
        type:'doughnut',
        data:{labels:pagos.map(function(p){return p.metodo;}),datasets:[{
            data:pagos.map(function(p){return parseFloat(p.total);}),
            backgroundColor:colors,borderWidth:2,borderColor:'#fff'}]},
        options:{responsive:true,maintainAspectRatio:false,cutout:'65%',
            plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return ' '+c.label+': '+fmt(c.parsed);}}}}}
    });
    document.getElementById('pago-lista').innerHTML=pagos.map(function(p,i){
        return '<div class="pago-item">'
            +'<span class="pago-dot" style="background:'+(colors[i]||'#ccc')+'"></span>'
            +'<span class="pago-label">'+p.metodo+'</span>'
            +'<span class="pago-val">'+fmt(p.total)+'</span>'
            +'<span class="pago-cnt">'+p.cantidad+' ped.</span></div>';
    }).join('');
}

// ── COSTOS MATERIALES ─────────────────────────────────────────────────
function renderCostosMP(mp, costos) {
    var badge=document.getElementById('costo-total-badge');
    if (badge) badge.textContent='Total período: '+fmt(costos.costo_periodo);
    var cont=document.getElementById('mp-bars');
    if (!cont) return;
    if (!mp||!mp.length) { cont.innerHTML='<div class="ana-loading">Sin compras en el período</div>'; return; }
    var max=Math.max.apply(null,mp.map(function(m){return parseFloat(m.total_invertido);}));
    cont.innerHTML=mp.map(function(m){
        var w=Math.round(parseFloat(m.total_invertido)/max*100);
        return '<div class="mp-row tr-clickable" onclick="abrirDetalleMaterial(\''+m.nombre.replace(/'/g,"\\'")+'\')">'
            +'<div class="mp-nombre" title="'+m.nombre+'">'+m.nombre+'</div>'
            +'<div class="mp-bar-wrap"><div class="mp-bar" style="width:'+w+'%"></div></div>'
            +'<div class="mp-val">'+fmt(m.total_invertido)+'</div>'
            +'<div class="mp-cnt">'+m.num_compras+' compra'+(m.num_compras!=1?'s':'')+'</div></div>';
    }).join('');
}

// ── HEATMAP ───────────────────────────────────────────────────────────
var HM_COLORS=['#e8edf2','#bdd7f0','#74b0e0','#2e80c4','#0d4e8a','#07305a'];
function hmNivel(val,max) {
    if (!val) return 0;
    if (val>=8) return 5; if (val>=5) return 4; if (val>=3) return 3; if (val>=2) return 2;
    if (max>=5&&val===max) return 2;
    return 1;
}
function jsDay2Dow(jsDay){ return (jsDay+6)%7; }
function renderHeatmap(heatmap, startDow, numDias) {
    if (startDow === undefined) startDow = 0;
    if (numDias  === undefined) numDias  = 7;
    var wrap=document.getElementById('hmWrap');
    var DIAS=['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
    var HORAS=Array.from({length:24},function(_,i){return String(i).padStart(2,'0');});
    // Construir orden de días rotado desde startDow
    var diasOrden=[];
    for(var i=0;i<numDias;i++) diasOrden.push((startDow+i)%7);
    var maxVal=0;
    for(var di=0;di<diasOrden.length;di++) for(var h=0;h<24;h++) maxVal=Math.max(maxVal,(heatmap[diasOrden[di]]&&heatmap[diasOrden[di]][h])||0);
    if (!maxVal) { wrap.innerHTML='<div class="ana-loading">Sin pedidos en el período</div>'; return; }
    var html='<div class="hm-grid"><div class="hm-label-corner"></div>';
    HORAS.forEach(function(h){ html+='<div class="hm-hour-lbl">'+h+'</div>'; });
    diasOrden.forEach(function(dd){
        html+='<div class="hm-day-lbl">'+DIAS[dd]+'</div>';
        for(var hh=0;hh<24;hh++){
            var val=(heatmap[dd]&&heatmap[dd][hh])||0;
            var niv=hmNivel(val,maxVal), col=HM_COLORS[niv];
            var tip=val?DIAS[dd]+' '+HORAS[hh]+':00 — '+val+' pedido'+(val!==1?'s':''):DIAS[dd]+' '+HORAS[hh]+':00 — sin pedidos';
            var bold=niv>=4?'border:2px solid rgba(255,255,255,.4);':'';
            html+='<div class="hm-cell" style="background:'+col+';'+bold+'" title="'+tip+'"></div>';
        }
    });
    html+='</div><div class="hm-legend">';
    ['Sin pedidos','Muy pocos','Pocos','Moderado','Muchos','Pico'].forEach(function(lbl,i){
        html+='<div class="hm-leg-item"><div class="hm-leg-box" style="background:'+HM_COLORS[i]+';'+(i>=4?'border:1.5px solid rgba(0,0,0,.1)':'')+'"></div><span>'+lbl+'</span></div>';
    });
    html+='</div>';
    wrap.innerHTML=html;
}

// ── MODALES ───────────────────────────────────────────────────────────
function abrirModal(titulo,html){
    document.getElementById('anaModalTitle').textContent=titulo;
    document.getElementById('anaModalBody').innerHTML=html;
    document.getElementById('anaModal').classList.add('open');
}
function cerrarModal(){ document.getElementById('anaModal').classList.remove('open'); }


function abrirDetalleDia(label,monto){
    abrirModal('Ingresos del '+label,
        '<div style="font-size:1.4rem;font-weight:700;margin-bottom:8px">'+fmt(monto)+'</div>'
        +'<p style="color:#8a8a86;font-size:.85rem">Ventas entregadas ese día</p>');
}

function abrirDetalleProducto(nombre){
    if (!_lastData) return;
    var p=(_lastData.top_productos||[]).find(function(x){return x.nombre===nombre;});
    if (!p) return;
    var prom=p.unidades>0?(parseFloat(p.ingresos)/parseFloat(p.unidades)).toFixed(0):0;
    abrirModal('Producto: '+nombre,
        '<table class="ana-table">'
        +'<tr><td>Unidades vendidas</td><td class="text-right"><strong>'+parseInt(p.unidades)+'</strong></td></tr>'
        +'<tr><td>Ingresos totales</td><td class="text-right"><strong>'+fmt(p.ingresos)+'</strong></td></tr>'
        +'<tr><td>Precio promedio</td><td class="text-right">'+fmt(prom)+'</td></tr>'
        +'</table>');
}

function abrirDetalleMaterial(nombre){
    if (!_lastData) return;
    var m=(_lastData.costos_mp||[]).find(function(x){return x.nombre===nombre;});
    if (!m) return;
    var prom=m.num_compras>0?(parseFloat(m.total_invertido)/parseFloat(m.num_compras)).toFixed(0):0;
    abrirModal('Material: '+nombre,
        '<table class="ana-table">'
        +'<tr><td>Total invertido</td><td class="text-right"><strong style="color:#c88e99">'+fmt(m.total_invertido)+'</strong></td></tr>'
        +'<tr><td>Cantidad comprada</td><td class="text-right">'+parseFloat(m.total_cantidad).toLocaleString('es-AR')+' unidades</td></tr>'
        +'<tr><td>Número de compras</td><td class="text-right">'+m.num_compras+'</td></tr>'
        +'<tr><td>Costo prom. por compra</td><td class="text-right">'+fmt(prom)+'</td></tr>'
        +'</table>');
}

// ── FILTRO DE PRODUCTO ────────────────────────────────────────────────
function filtrarProductos(q) {
    var reset=document.getElementById('prodFiltroReset'), note=document.getElementById('prod-filter-note');
    reset.style.display=q?'':'none';
    if (!q) { renderTopProductos(_todosProductos,false); note.style.display='none'; return; }
    var filtrados=_todosProductos.filter(function(p){return p.nombre.toLowerCase().includes(q.toLowerCase());});
    renderTopProductos(filtrados,false);
    note.style.display=filtrados.length<_todosProductos.length?'':'none';
    if (filtrados.length<_todosProductos.length) note.textContent='Mostrando '+filtrados.length+' de '+_todosProductos.length+' productos';
}
function resetFiltroProducto() {
    document.getElementById('prodFiltroInput').value='';
    document.getElementById('prodFiltroReset').style.display='none';
    document.getElementById('prod-filter-note').style.display='none';
    renderTopProductos(_todosProductos,false);
}
function scrollToSec(sel){ document.querySelector(sel)&&document.querySelector(sel).scrollIntoView({behavior:'smooth',block:'start'}); }
function showToast(msg,type){
    if (window.Swal) Swal.fire({icon:type==='err'?'error':'info',title:msg,timer:3000,showConfirmButton:false,toast:true,position:'top-end'});
    else alert(msg);
}

// ── SUB-FILTROS POR TARJETA ───────────────────────────────────────────
var CARDS = ['ingresos','productos','pago','heatmap'];

function _dateStr(d) {
    return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}

function _subDias(inicio, fin) {
    var ops=[], cur=new Date(inicio);
    var DN=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
    while (cur<=fin) {
        var ds=_dateStr(cur);
        ops.push({label: DN[cur.getDay()]+' '+String(cur.getDate()).padStart(2,'0')+'/'+String(cur.getMonth()+1).padStart(2,'0'), desde:ds, hasta:ds});
        cur.setDate(cur.getDate()+1);
    }
    return ops;
}
function _subSemanas(inicio, fin) {
    var ops=[], cur=new Date(inicio), sem=1;
    var f=function(d){return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0');};
    while (cur<=fin) {
        var sI=new Date(cur), sF=new Date(cur); sF.setDate(sF.getDate()+6);
        if (sF>fin) sF=new Date(fin);
        ops.push({label:'Sem '+sem+' ('+f(sI)+'–'+f(sF)+')', desde:_dateStr(sI), hasta:_dateStr(sF)});
        cur.setDate(cur.getDate()+7); sem++;
    }
    return ops;
}
function _subMeses(inicio, fin) {
    var ops=[], MN=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    var cur=new Date(inicio.getFullYear(), inicio.getMonth(), 1);
    while (cur<=fin) {
        var mI=new Date(cur), mF=new Date(cur.getFullYear(), cur.getMonth()+1, 0);
        if (mF>fin) mF=new Date(fin);
        ops.push({label: MN[cur.getMonth()]+' '+cur.getFullYear(), desde:_dateStr(mI), hasta:_dateStr(mF)});
        cur.setMonth(cur.getMonth()+1);
    }
    return ops;
}

function generarSubOpciones() {
    if (!_lastData) return [];
    var inicio   = new Date(_lastData.inicio+'T00:00:00');
    var fin      = new Date(_lastData.fin+'T00:00:00');
    var diffDays = Math.round((fin - inicio) / 86400000) + 1;

    if (diffDays <= 1) return []; // día exacto: sin sub-filtro

    // Semanas → días individuales
    if (_modo === 'semana_actual' || _modo === 'semana_anterior') return _subDias(inicio, fin);

    // Mes (cualquier variante) → semanas
    if (_modo === 'mes_actual' || _modo === 'mes_anterior' || _modo === 'mes_especifico') return _subSemanas(inicio, fin);

    // Año completo → meses
    if (_modo === 'anio_completo') return _subMeses(inicio, fin);

    // Rango custom → por tamaño
    if (diffDays <= 14)  return _subDias(inicio, fin);
    if (diffDays <= 62)  return _subSemanas(inicio, fin);
    return _subMeses(inicio, fin);
}

function actualizarSubfiltros() {
    var opciones = generarSubOpciones();
    CARDS.forEach(function(card) {
        var wrap=document.getElementById('sf-'+card);
        if (!wrap) return;
        if (!opciones.length) { wrap.style.display='none'; return; }
        wrap.style.display='';
        var container=document.getElementById('sfp-'+card);
        container.innerHTML=opciones.map(function(op){
            return '<button class="sf-pill" data-desde="'+op.desde+'" data-hasta="'+op.hasta
                +'" onclick="aplicarSubfiltro(\''+card+'\',this)">'+op.label+'</button>';
        }).join('');
        // Reset button hidden initially
        document.getElementById('sfr-'+card).style.display='none';
    });
}

async function aplicarSubfiltro(cardId, btn) {
    var container=document.getElementById('sfp-'+cardId);
    container.querySelectorAll('.sf-pill').forEach(function(b){b.classList.remove('active');});
    btn.classList.add('active');
    document.getElementById('sfr-'+cardId).style.display='';
    await cargarCard(cardId, btn.dataset.desde, btn.dataset.hasta);
}

function resetSubfiltro(cardId) {
    var container=document.getElementById('sfp-'+cardId);
    container.querySelectorAll('.sf-pill').forEach(function(b){b.classList.remove('active');});
    document.getElementById('sfr-'+cardId).style.display='none';
    if (!_lastData) return;
    switch(cardId) {
        case 'ingresos':   renderChartIngresos(_lastData.labels, _lastData.ingresos); break;
        case 'productos':  renderTopProductos(_lastData.top_productos, true); break;
        case 'pago':       renderPago(_lastData.por_pago); break;
        case 'materiales': renderCostosMP(_lastData.costos_mp, _lastData.costos); break;
        case 'heatmap':    renderHeatmap(_lastData.heatmap||{}); break;
    }
}

async function cargarCard(cardId, desde, hasta) {
    var params = desde===hasta
        ? 'modo=dia_especifico&dia='+desde
        : 'modo=rango_custom&desde='+desde+'&hasta='+hasta;
    try {
        var data=await fetch('analitica/api/get_analitica.php?'+params).then(function(r){return r.json();});
        switch(cardId) {
            case 'ingresos':   renderChartIngresos(data.labels, data.ingresos); break;
            case 'productos':  renderTopProductos(data.top_productos, false); break;
            case 'pago':       renderPago(data.por_pago); break;
            case 'materiales': renderCostosMP(data.costos_mp, data.costos); break;
            case 'heatmap': {
                var dI=new Date(desde+'T00:00:00'), dF=new Date(hasta+'T00:00:00');
                var startDow=jsDay2Dow(dI.getDay());
                var numDias=Math.min(Math.round((dF-dI)/86400000)+1, 7);
                renderHeatmap(data.heatmap||{}, startDow, numDias);
                break;
            }
        }
    } catch(e){ console.error('cargarCard error:', cardId, e); }
}

// ── TOGGLE DE VISTAS ─────────────────────────────────────────────────
var _anaLoaded=false;
var _finLoaded = false;

function toggleVista(v) {
    var vD = document.getElementById('view-dashboard');
    var vA = document.getElementById('view-analitica');
    var vF = document.getElementById('view-finanzas');
    var nombre = document.getElementById('cv-nombre');

    vD.style.display = 'none';
    vA.style.display = 'none';
    vF.style.display = 'none';

    if (v === 'dashboard') {
        vD.style.display = '';
        nombre.textContent = 'Dashboard';
        if (chartProd) setTimeout(function(){ chartProd.resize(); }, 50);
    } else if (v === 'analitica') {
        vA.style.display = '';
        nombre.textContent = 'Analítica de ventas';
        if (!_anaLoaded) { cargar(); _anaLoaded = true; }
    } else if (v === 'finanzas') {
        vF.style.display = '';
        nombre.textContent = 'Finanzas';
        if (!_finLoaded) { cargarFinanzas(); _finLoaded = true; }
    }
    localStorage.setItem('canetto_vista', v);
}

// Ocultar/mostrar cv-bar según dirección de scroll dentro de .main-content
(function() {
    var bar = null;
    var scroller = null;
    var lastY = 0;
    function onScroll() {
        if (!bar) bar = document.querySelector('.cv-bar');
        if (!bar) return;
        var y = scroller.scrollTop;
        if (y > lastY && y > 60) {
            bar.classList.add('cv-bar--hidden');
        } else {
            bar.classList.remove('cv-bar--hidden');
        }
        lastY = y;
    }
    document.addEventListener('DOMContentLoaded', function() {
        scroller = document.querySelector('.main-content');
        if (scroller) scroller.addEventListener('scroll', onScroll, { passive: true });
    });
})();

// ══════════════════════════════════════════════════════
//  VISTA FINANZAS
// ══════════════════════════════════════════════════════
var _finModo = 'mes_actual', _finLastData = null;

function cargarFinanzas() {
    // Resetear KPIs a estado de carga
    ['fin-k-costo','fin-k-ingresos','fin-k-beneficio'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) { el.textContent = '—'; el.style.color = ''; }
    });
    var elM = document.getElementById('fin-k-margen');
    if (elM) { elM.textContent = '—'; elM.style.color = ''; }
    var elIS = document.getElementById('fin-k-ingresos-sub');
    if (elIS) elIS.textContent = 'cargando...';
    var bars = document.getElementById('fin-mp-bars');
    if (bars) bars.innerHTML = '<div class="ana-loading">Cargando...</div>';
    var tb = document.getElementById('fin-tb-debe-haber');
    if (tb) tb.innerHTML = '<tr><td colspan="6" class="ana-loading">Cargando...</td></tr>';

    var params = buildParamsFin();
    fetch('analitica/api/get_analitica.php?' + params)
        .then(function(r){ return r.text(); })
        .then(function(txt){
            var data;
            try { data = JSON.parse(txt); } catch(e) {
                console.error('Finanzas JSON error:', txt.substring(0,200));
                ['fin-k-costo','fin-k-ingresos','fin-k-beneficio','fin-k-margen'].forEach(function(id){
                    var el = document.getElementById(id); if (el) el.textContent = 'Error';
                });
                return;
            }
            if (data.error) { console.error('Finanzas API error:', data.error); return; }
            _finLastData = data;
            renderFinKPIs(data);
            renderFinMateriales(data.costos_mp, data.costos);
            renderDebeHaber(data.debe_haber, data.kpis, data.costos);
            var lbl = document.getElementById('fin-lblPeriodo');
            if (lbl) lbl.textContent = data.periodo_label || data.periodo || '—';
        })
        .catch(function(e){
            console.error('Finanzas fetch error:', e);
            ['fin-k-costo','fin-k-ingresos','fin-k-beneficio','fin-k-margen'].forEach(function(id){
                var el = document.getElementById(id); if (el) el.textContent = 'Error';
            });
        });
}

function buildParamsFin() {
    var p = new URLSearchParams();
    p.set('modo', _finModo);
    p.set('mes',  document.getElementById('fin-mesSelect').value  || '0');
    p.set('anio', document.getElementById('fin-anioSelect').value || new Date().getFullYear());
    if (_finModo === 'rango_custom') {
        p.set('desde', document.getElementById('fin-fltDesde').value);
        p.set('hasta', document.getElementById('fin-fltHasta').value);
    }
    if (_finModo === 'dia_especifico') {
        p.set('dia', document.getElementById('fin-fltDia').value);
    }
    return p.toString();
}

function renderFinKPIs(data) {
    var k = data.kpis || {}, c = data.costos || {};
    var ingresos    = parseFloat(k.ventas_periodo   || 0);
    var costo       = parseFloat(c.costo_periodo    || 0);
    var pedidos     = parseInt(k.pedidos_periodo    || 0);
    var entregados  = parseInt(k.pedidos_entregados || 0);
    var beneficio   = ingresos - costo;
    var margen      = ingresos > 0 ? ((beneficio / ingresos) * 100).toFixed(1) : 0;

    var elC  = document.getElementById('fin-k-costo');
    var elI  = document.getElementById('fin-k-ingresos');
    var elB  = document.getElementById('fin-k-beneficio');
    var elM  = document.getElementById('fin-k-margen');
    var elIS = document.getElementById('fin-k-ingresos-sub');

    if (elC)  elC.textContent = fmt(costo);
    if (elI)  elI.textContent = fmt(ingresos);
    if (elIS) {
        var subTxt = pedidos + ' pedido' + (pedidos !== 1 ? 's' : '');
        if (entregados !== pedidos) subTxt += ' · <span style="color:#16a34a;font-weight:700">' + entregados + ' entregado' + (entregados !== 1 ? 's' : '') + '</span>';
        elIS.innerHTML = subTxt;
    }
    if (elB) {
        elB.textContent = fmt(beneficio);
        elB.style.color = beneficio >= 0 ? '#16a34a' : '#c0392b';
    }
    if (elM) {
        elM.textContent = margen + '%';
        elM.style.color = parseFloat(margen) >= 0 ? '#c88e99' : '#c0392b';
    }
    var card = document.getElementById('fin-kpi-beneficio-card');
    if (card) card.style.borderLeftColor = beneficio >= 0 ? '#16a34a' : '#c0392b';
}

function renderFinMateriales(mp, costos) {
    var badge = document.getElementById('fin-costo-badge');
    var bars  = document.getElementById('fin-mp-bars');
    if (!bars) return;
    if (badge) badge.textContent = 'Total período: ' + fmt((costos||{}).costo_periodo || 0);
    if (!mp || !mp.length) { bars.innerHTML = '<div class="ana-loading">Sin compras en el período</div>'; return; }
    var max = Math.max.apply(null, mp.map(function(m){ return parseFloat(m.total_invertido); }));
    bars.innerHTML = mp.map(function(m) {
        var pct = max > 0 ? (parseFloat(m.total_invertido) / max * 100).toFixed(1) : 0;
        return '<div class="mp-row" onclick="abrirDetalleMaterial(\'' + m.nombre.replace(/'/g,"\\'")+'\')"><span class="mp-nombre">' + m.nombre + '</span>'
            + '<div class="mp-bar-wrap"><div class="mp-bar" style="width:' + pct + '%"></div></div>'
            + '<span class="mp-val">' + fmt(m.total_invertido) + '</span>'
            + '<span class="mp-cnt">' + m.num_compras + ' compras</span></div>';
    }).join('');
}

function renderDebeHaber(dh, kpis, costos) {
    var tb     = document.getElementById('fin-tb-debe-haber');
    var resDiv = document.getElementById('fin-dh-resumen');
    if (!tb) return;

    var totalHaber = parseFloat((kpis||{}).ventas_periodo || 0);
    var totalDebe  = parseFloat((costos||{}).costo_periodo || 0);
    var saldoFinal = totalHaber - totalDebe;

    if (resDiv) {
        resDiv.className = 'dh-resumen';
        resDiv.innerHTML =
            '<div class="dh-res-item dh-res-haber"><span>Total ingresos</span><strong>' + fmt(totalHaber) + '</strong></div>'
          + '<div class="dh-res-item dh-res-debe"><span>Total costos</span><strong>' + fmt(totalDebe) + '</strong></div>'
          + '<div class="dh-res-item dh-res-balance ' + (saldoFinal>=0?'pos':'neg') + '"><span>Balance</span><strong>' + fmt(saldoFinal) + '</strong></div>';
    }

    if (!dh || !dh.length) { tb.innerHTML = '<tr><td colspan="6" class="ana-loading">Sin movimientos en el período</td></tr>'; return; }

    var saldo = 0;
    tb.innerHTML = dh.map(function(f) {
        var debe = parseFloat(f.debe || 0), haber = parseFloat(f.haber || 0);
        saldo += haber - debe;
        var cls = haber > 0 ? 'dh-ingreso' : 'dh-costo';
        var saldoCls = saldo >= 0 ? 'dh-saldo pos' : 'dh-saldo neg';
        return '<tr class="' + cls + '">'
            + '<td style="font-size:.78rem;color:#888;white-space:nowrap">' + (f.fecha || '') + '</td>'
            + '<td style="font-weight:600;font-size:.83rem">' + (f.concepto || '') + '</td>'
            + '<td style="font-size:.75rem;color:#888">' + (f.detalle || '') + '</td>'
            + '<td style="text-align:right;color:#c0392b;font-weight:600">' + (debe > 0 ? fmt(debe) : '') + '</td>'
            + '<td style="text-align:right;color:#16a34a;font-weight:600">' + (haber > 0 ? fmt(haber) : '') + '</td>'
            + '<td style="text-align:right"><span class="' + saldoCls + '">' + fmt(saldo) + '</span></td>'
            + '</tr>';
    }).join('');
}

// Filtros finanzas
function desactivarPillsFin() { document.querySelectorAll('.flt-pill-fin').forEach(function(b){ b.classList.remove('active'); }); }
function setModoFin(btn) {
    desactivarPillsFin(); btn.classList.add('active');
    _finModo = btn.dataset.modo;
    document.getElementById('fin-mesSelect').value = '0';
    limpiarRangoUIFin(); cargarFinanzas();
}
function setModoMesFin() {
    var mes = document.getElementById('fin-mesSelect').value;
    desactivarPillsFin(); limpiarRangoUIFin();
    _finModo = (!mes || mes === '0') ? 'anio_completo' : 'mes_especifico';
    cargarFinanzas();
}
function onAnioChangeFin() {
    var mes = document.getElementById('fin-mesSelect').value;
    desactivarPillsFin(); limpiarRangoUIFin();
    _finModo = (!mes || mes === '0') ? 'anio_completo' : 'mes_especifico';
    cargarFinanzas();
}
function setModoRangoFin() {
    var desde = document.getElementById('fin-fltDesde').value;
    var hasta = document.getElementById('fin-fltHasta').value;
    if (!desde || !hasta) return;
    desactivarPillsFin();
    document.getElementById('fin-mesSelect').value = '0';
    document.getElementById('fin-fltDia').value = '';
    document.getElementById('fin-btnDiaReset').style.display = 'none';
    document.getElementById('fin-btnRangoReset').style.display = '';
    _finModo = 'rango_custom'; cargarFinanzas();
}
function setModoDiaFin() {
    var dia = document.getElementById('fin-fltDia').value;
    if (!dia) return;
    desactivarPillsFin();
    document.getElementById('fin-mesSelect').value = '0';
    document.getElementById('fin-fltDesde').value = '';
    document.getElementById('fin-fltHasta').value = '';
    document.getElementById('fin-btnRangoReset').style.display = 'none';
    document.getElementById('fin-btnDiaReset').style.display = '';
    _finModo = 'dia_especifico'; cargarFinanzas();
}
function resetRangoFin() {
    document.getElementById('fin-fltDesde').value = '';
    document.getElementById('fin-fltHasta').value = '';
    document.getElementById('fin-btnRangoReset').style.display = 'none';
    document.querySelector('.flt-pill-fin[data-modo="mes_actual"]').classList.add('active');
    _finModo = 'mes_actual'; cargarFinanzas();
}
function resetDiaFin() {
    document.getElementById('fin-fltDia').value = '';
    document.getElementById('fin-btnDiaReset').style.display = 'none';
    document.querySelector('.flt-pill-fin[data-modo="mes_actual"]').classList.add('active');
    _finModo = 'mes_actual'; cargarFinanzas();
}
function limpiarRangoUIFin() {
    document.getElementById('fin-fltDesde').value = '';
    document.getElementById('fin-fltHasta').value = '';
    document.getElementById('fin-fltDia').value = '';
    document.getElementById('fin-btnRangoReset').style.display = 'none';
    document.getElementById('fin-btnDiaReset').style.display = 'none';
}

// Instancias DataTables
let dtStockMain = null;
const dtMsw = {};

// DOMContentLoaded: jQuery ya está cargado (footer lo carga sincrónicamente antes de este evento)
document.addEventListener('DOMContentLoaded', function() {
    // Toggle de vista
    var saved = localStorage.getItem('canetto_vista') || 'dashboard';
    var sel = document.getElementById('viewSelect');
    if (sel) sel.value = saved;
    toggleVista(saved);
    try { initChartProd(); } catch(e) {}

    // Config base DataTables
    var dtBase = {
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
        pageLength: 15,
        lengthMenu: [10,15,25,50],
        dom: "<'db-dt-top'lf>t<'db-dt-bottom'ip>",
    };

    // Stock principal
    if ($.fn.DataTable && document.getElementById('dtStock')) {
        dtStockMain = $('#dtStock').DataTable(Object.assign({}, dtBase, {
            order: [[0,'asc']],
            columnDefs: [
                { targets: 0, className: 'dt-left' },
                { targets: [1,2], orderable: false },
                { targets: 3, orderable: false, className: 'dt-center' },
            ],
        }));
    }

    // Mini widgets
    ['msw1','msw2','msw3'].forEach(function(id) {
        if (!document.getElementById(id)) return;
        dtMsw[id] = $('#'+id).DataTable(Object.assign({}, dtBase, {
            pageLength: 10,
            order: [[1,'asc']],
            columnDefs: [
                { targets: 0, className: 'dt-left' },
                { targets: 1, className: 'dt-right' },
                { targets: 2, orderable: false, className: 'dt-center' },
            ],
        }));
    });

    // Aplicar filtro inicial
    var sfBtn = document.querySelector('.sf-pill[data-sf="todos"]');
    if (sfBtn) sfStock(sfBtn);
});

// Filtros sf-pill — fuera de DOMContentLoaded para que los onclick del HTML puedan llamarlos
function sfStock(btn) {
    btn.closest('div').querySelectorAll('.sf-pill[data-sf]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    var filtro = btn.dataset.sf;
    if (dtStockMain) {
        $.fn.dataTable.ext.search.push(function(s, d, i) {
            if (s.nTable.id !== 'dtStock') return true;
            if (filtro === 'todos') return true;
            var tr = s.nTable.tBodies[0].rows[i];
            return tr && tr.dataset.sf === filtro;
        });
        dtStockMain.draw();
        $.fn.dataTable.ext.search.pop();
    } else {
        document.querySelectorAll('#dtStock tbody tr').forEach(tr => {
            tr.style.display = filtro === 'todos' || tr.dataset.sf === filtro ? '' : 'none';
        });
    }
}

function mswFiltrar(tableId, btn) {
    btn.closest('div').querySelectorAll('.sf-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    var filtro = btn.dataset.sf;
    if (dtMsw[tableId]) {
        $.fn.dataTable.ext.search.push(function(s, d, i) {
            if (s.nTable.id !== tableId) return true;
            if (filtro === 'todos') return true;
            var tr = s.nTable.tBodies[0].rows[i];
            return tr && tr.dataset.sf === filtro;
        });
        dtMsw[tableId].draw();
        $.fn.dataTable.ext.search.pop();
    } else {
        document.querySelectorAll('#'+tableId+' tbody tr').forEach(tr => {
            tr.style.display = filtro === 'todos' || tr.dataset.sf === filtro ? '' : 'none';
        });
    }
}

/* ── Producir desde dashboard ─────────────────────────── */
function dbProducir(productoId, recetaId, nombre) {
    Swal.fire({
        title: `Producir — ${nombre}`,
        input: 'number',
        inputLabel: 'Cantidad a producir (uds)',
        inputAttributes: { min: 1, step: 1 },
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#999',
        confirmButtonText: 'Producir',
        cancelButtonText: 'Cancelar',
        inputValidator: v => !v || v <= 0 ? 'Ingresá una cantidad válida' : null
    }).then(r => {
        if (!r.isConfirmed) return;
        const cant = parseFloat(r.value);
        Swal.fire({ title: 'Calculando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch('produccion/congelado/api/preview_receta.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ receta: recetaId, cantidad: cant })
        }).then(r=>r.json()).then(preview => {
            if (preview.status !== 'ok') { Swal.fire('Error', preview.mensaje||'Error', 'error'); return; }
            const filas = preview.ingredientes.map(i => {
                const c = i.faltante ? 'color:#dc2626;font-weight:700' : 'color:#16a34a';
                return `<tr><td style="text-align:left;padding:3px 8px">${i.faltante?'✗':'✓'} ${i.nombre}</td><td style="padding:3px 8px;${c}">${i.cantidad} ${i.unidad}</td><td style="padding:3px 8px;color:#888;font-size:.78rem">Stock: ${i.stock}</td></tr>`;
            }).join('');
            const puede = preview.puede_producir;
            Swal.fire({
                title: `¿Producir ${cant} uds?`,
                html: `<table style="width:100%;border-collapse:collapse;font-size:.82rem">${filas}</table>${!puede?'<p style="color:#dc2626;font-weight:600;margin-top:8px">Stock insuficiente en algunos ingredientes</p>':''}`,
                icon: puede ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: puede ? '#2563eb' : '#d97706',
                cancelButtonColor: '#999',
                confirmButtonText: 'Producir',
                cancelButtonText: 'Cancelar'
            }).then(r2 => {
                if (!r2.isConfirmed) return;
                fetch('produccion/congelado/api/producir.php', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ receta: recetaId, producto: productoId, cantidad: cant })
                }).then(r=>r.json()).then(r => {
                    if (r.status === 'ok') Swal.fire({title:'Producción realizada',text:r.mensaje,icon:'success',confirmButtonColor:'#c88e99'}).then(()=>location.reload());
                    else Swal.fire('Error', r.mensaje||'Error', 'error');
                });
            });
        }).catch(()=>Swal.fire('Error','No se pudo conectar','error'));
    });
}

/* ── Hornear desde dashboard ──────────────────────────── */
function dbHornear(productoId, disponible, nombre) {
    Swal.fire({
        title: `Hornear — ${nombre}`,
        html: `<p style="margin-bottom:10px;color:#666;font-size:.85rem">Disponible para hornear: <strong>${disponible} uds</strong></p>`,
        input: 'number',
        inputLabel: 'Cantidad a hornear',
        inputAttributes: { min: 1, step: 1, max: disponible },
        showCancelButton: true,
        confirmButtonColor: '#ea580c',
        cancelButtonColor: '#999',
        confirmButtonText: 'Hornear',
        cancelButtonText: 'Cancelar',
        inputValidator: v => {
            if (!v || v <= 0) return 'Ingresá una cantidad válida';
            if (parseFloat(v) > disponible) return `Máximo disponible: ${disponible} uds`;
        }
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch('produccion/horneado/api/procesar_horneado.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ producto_id: productoId, cantidad: parseFloat(r.value) })
        }).then(r=>r.json()).then(r => {
            if (r.status === 'ok') Swal.fire({title:'Horneado realizado',text:r.mensaje,icon:'success',confirmButtonColor:'#c88e99'}).then(()=>location.reload());
            else Swal.fire('Error', r.mensaje||'Error', 'error');
        });
    });
}
</script>

<?php include __DIR__ . '/../panel/dashboard/layaut/footer.php'; ?>
