<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';

$pdo = Conexion::conectar();

$totalProductos    = $pdo->query("SELECT COUNT(*) FROM productos WHERE tipo='producto'")->fetchColumn();
$totalMP           = $pdo->query("SELECT COUNT(*) FROM materia_prima")->fetchColumn();
$produccionHoy     = $pdo->query("SELECT COUNT(*) FROM produccion WHERE DATE(fecha)=CURDATE()")->fetchColumn();

try { $pedidosPendientes = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado IN ('pendiente','en_proceso')")->fetchColumn(); }
catch (Throwable $e) { $pedidosPendientes = 0; }

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

$diasSemana = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes',
               'Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado'];
$diaNombre  = $diasSemana[(new DateTime())->format('l')] ?? '';
$fechaHoy   = (new DateTime())->format('d/m/Y');

$stockOk     = empty($productosBajos) && empty($productosSinStock);
$topbarStats = "Hoy: {$produccionHoy} prod. · Stock: " . ($stockOk ? 'saludable ✓' : 'revisar ⚠');
$pageTitle   = "Dashboard — Canetto";

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
    top: 60px;
    z-index: 90;
    box-shadow: 0 3px 12px rgba(200,142,153,.1);
}
.cv-bar-label {
    font-family: 'Inter', sans-serif;
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
    font-family: 'Inter', sans-serif;
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
    font-family: 'Inter', sans-serif;
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
    font-family: 'Inter', sans-serif;
    font-size: .7rem;
    font-weight: 600;
    color: #8a8a86;
    text-transform: uppercase;
    letter-spacing: .06em;
    white-space: nowrap;
    margin-right: 2px;
}
.sf-pill {
    font-family: 'Inter', sans-serif;
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
    font-family: 'Inter', sans-serif;
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

/* ── RETIRO/ENVÍO KPI CARDS ────────────────────────────────────── */
.kpi-card.kpi-retiro { border-left-color: #16a34a; }
.kpi-card.kpi-envio  { border-left-color: #2563eb; }
.kpi-ico-green { color: #16a34a; background: #dcfce7; }
.kpi-ico-blue  { color: #2563eb; background: #dbeafe; }
</style>

<!-- SELECTOR DE VISTA -->
<div class="cv-bar">
    <span class="cv-bar-label">
        <i class="fa-solid fa-layer-group"></i>Vista
    </span>
    <select id="viewSelect" class="cv-select" onchange="toggleVista(this.value)">
        <option value="dashboard">Dashboard operacional</option>
        <option value="analitica">Analítica de ventas</option>
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
        <a href="<?= URL_ADMIN ?>/produccion/congelado/index.php" class="db-qa"><i class="fa-solid fa-snowflake"></i><span>Masa congelada</span></a>
        <a href="<?= URL_ADMIN ?>/produccion/horneado/index.php" class="db-qa"><i class="fa-solid fa-fire"></i><span>Horneado</span></a>
        <a href="<?= URL_ADMIN ?>/stock/index.php" class="db-qa"><i class="fa-solid fa-boxes-stacked"></i><span>Stock</span></a>
        <a href="<?= URL_ADMIN ?>/recetas/index.php" class="db-qa"><i class="fa-solid fa-book-open"></i><span>Recetas</span></a>
        <a href="<?= URL_ADMIN ?>/Ventas/Ventas/index.php" class="db-qa"><i class="fa-solid fa-cart-shopping"></i><span>Nueva venta</span></a>
        <a href="<?= URL_ADMIN ?>/materias_primas/index.php" class="db-qa"><i class="fa-solid fa-seedling"></i><span>Materias primas</span></a>
    </div>

    <div class="db-kpi-row">
        <div class="db-kpi">
            <div class="db-kpi-icon"><i class="fa-solid fa-cookie-bite"></i></div>
            <div class="db-kpi-label">Productos activos</div>
            <div class="db-kpi-val" data-val="<?= (int)$totalProductos ?>">0</div>
            <div class="db-kpi-sub">en sistema</div>
        </div>
        <div class="db-kpi">
            <div class="db-kpi-icon"><i class="fa-solid fa-calendar-day"></i></div>
            <div class="db-kpi-label">Producidas hoy</div>
            <div class="db-kpi-val" data-val="<?= (int)$produccionHoy ?>">0</div>
            <div class="db-kpi-sub"><?= $produccionHoy > 0 ? 'lotes registrados' : 'sin actividad aún' ?></div>
        </div>
        <div class="db-kpi">
            <div class="db-kpi-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="db-kpi-label">Pedidos activos</div>
            <div class="db-kpi-val" data-val="<?= (int)$pedidosPendientes ?>">0</div>
            <div class="db-kpi-sub">pendientes / en proceso</div>
        </div>
        <div class="db-kpi">
            <div class="db-kpi-icon"><i class="fa-solid fa-seedling"></i></div>
            <div class="db-kpi-label">Materias primas</div>
            <div class="db-kpi-val" data-val="<?= (int)$totalMP ?>">0</div>
            <div class="db-kpi-sub">registradas</div>
        </div>
    </div>

    <div class="db-layout">
        <div class="db-col-main">

            <div class="db-card">
                <div class="db-card-title">
                    <i class="fa-solid fa-chart-simple"></i>
                    Niveles de stock
                    <span class="db-card-badge"><?= count($stockAgrupado) ?> productos</span>
                </div>
                <?php if (empty($stockAgrupado)): ?>
                    <p class="db-empty">Sin datos de stock.</p>
                <?php else: ?>
                    <?php foreach ($stockAgrupado as $nombre => $tipos): ?>
                    <div class="db-producto-grupo">
                        <div class="db-producto-nombre"><?= htmlspecialchars($nombre) ?></div>
                        <div class="db-producto-tipos">
                        <?php foreach (['congelado'=>'Congelado','hecho'=>'Hecho'] as $tipoKey=>$tipoLabel):
                            if (!isset($tipos[$tipoKey])) continue;
                            $r = $tipos[$tipoKey];
                            $actual   = (float)$r['stock_actual'];
                            $minimo   = (float)$r['stock_minimo'];
                            $sinStock = $actual <= 0;
                            $bajMin   = !$sinStock && $actual <= $minimo;
                        ?>
                            <div class="db-stock-row">
                                <span class="db-stock-tipo"><?= $tipoLabel ?></span>
                                <span class="db-stock-nums"><?= number_format($actual,0) ?> uds · mín <?= number_format($minimo,0) ?></span>
                                <?php if ($sinStock): ?>
                                    <span class="db-stock-badge db-stock-badge--sinstock">Sin Stock</span>
                                <?php elseif ($bajMin): ?>
                                    <span class="db-stock-badge db-stock-badge--bajo">Bajo mínimo</span>
                                <?php else: ?>
                                    <span class="db-stock-badge db-stock-badge--ok">OK</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="db-chart-card">
                <div class="db-chart-head">
                    <div class="db-card-title" style="margin:0">
                        <i class="fa-solid fa-chart-bar"></i>
                        Producción — últimos 7 días
                    </div>
                    <span class="db-count">lotes por día</span>
                </div>
                <div class="db-chart-wrap">
                    <canvas id="chartProd" height="90"></canvas>
                </div>
            </div>

            <div class="db-table-card">
                <div class="db-table-head">
                    <span>Últimas producciones</span>
                    <span class="db-count"><?= count($ultimasProducciones) ?> registros</span>
                </div>
                <?php if (empty($ultimasProducciones)): ?>
                    <p class="db-empty db-empty--padded">Sin producciones registradas.</p>
                <?php else: ?>
                <table>
                    <thead><tr><th>Producto</th><th>Cantidad</th><th>Fecha</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($ultimasProducciones as $i => $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td class="db-mono"><?= number_format($p['cantidad'], 2) ?></td>
                            <td class="db-muted db-mono"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                            <td><?php if ($i === 0): ?><span class="db-pill db-pill--green">reciente</span><?php else: ?><span class="db-pill db-pill--gray">registrado</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>

        <div class="db-col-side">

            <div class="db-card">
                <div class="db-card-title"><i class="fa-solid fa-bell"></i> Alertas del sistema</div>
                <?php if (empty($productosBajos) && empty($productosSinStock)): ?>
                    <div class="db-alert db-alert--ok">
                        <span class="db-dot db-dot--ok"></span>Stock saludable — todo en rango
                    </div>
                <?php else: ?>
                    <?php if ($productosSinStock): ?>
                    <div class="db-alert db-alert--danger">
                        <span class="db-dot db-dot--danger"></span>
                        <?= count($productosSinStock) ?> producto<?= count($productosSinStock) > 1 ? 's' : '' ?> <strong>Sin Stock</strong>
                    </div>
                    <?php foreach ($productosSinStock as $ps): ?>
                        <div class="db-alert-detail">⛔ <?= htmlspecialchars($ps['nombre']) ?> (<?= strtolower($ps['tipo_stock']) ?>)</div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($productosBajos): ?>
                    <div class="db-alert db-alert--warn">
                        <span class="db-dot db-dot--warn"></span><?= count($productosBajos) ?> bajo mínimo
                    </div>
                    <?php foreach ($productosBajos as $pb): ?>
                        <div class="db-alert-detail">⚠ <?= htmlspecialchars($pb['nombre']) ?> — <?= number_format($pb['stock_actual'],0) ?>/<?= number_format($pb['stock_minimo'],0) ?> uds</div>
                    <?php endforeach; ?>
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
    <div class="kpi-card" onclick="abrirDetalle('hoy')" title="Ver detalle">
      <div class="kpi-ico"><i class="fa-regular fa-calendar-day"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Hoy</div>
        <div class="kpi-value" id="k-ventas-hoy">—</div>
        <div class="kpi-sub" id="k-pedidos-hoy">—</div>
      </div>
      <i class="fa-solid fa-chevron-right kpi-arrow"></i>
    </div>
    <div class="kpi-card" onclick="abrirDetalle('semana')" title="Ver detalle">
      <div class="kpi-ico"><i class="fa-regular fa-calendar-week"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Esta semana</div>
        <div class="kpi-value" id="k-ventas-semana">—</div>
        <div class="kpi-sub" id="k-pedidos-semana">—</div>
      </div>
      <i class="fa-solid fa-chevron-right kpi-arrow"></i>
    </div>
    <div class="kpi-card kpi-highlight" onclick="abrirDetalle('periodo')" title="Ver detalle">
      <div class="kpi-ico"><i class="fa-regular fa-calendar-range"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Período seleccionado</div>
        <div class="kpi-value" id="k-ventas-periodo">—</div>
        <div class="kpi-sub" id="k-pedidos-periodo">—</div>
      </div>
      <i class="fa-solid fa-chevron-right kpi-arrow"></i>
    </div>
    <div class="kpi-card kpi-costo" onclick="scrollToSec('#sec-materiales')">
      <div class="kpi-ico kpi-ico-red"><i class="fa-solid fa-cart-shopping"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Inversión materiales</div>
        <div class="kpi-value kpi-red" id="k-costo-periodo">—</div>
        <div class="kpi-sub">Compras del período</div>
      </div>
      <i class="fa-solid fa-chevron-right kpi-arrow"></i>
    </div>
    <div class="kpi-card kpi-beneficio-card" id="kpi-beneficio-card">
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

  <!-- INVERSIÓN EN MATERIALES -->
  <div class="ana-section" id="sec-materiales" style="margin-bottom:1.4rem">
    <div class="ana-section-header">
      <h2>Inversión en materiales</h2>
      <span id="costo-total-badge" class="costo-badge">—</span>
    </div>
    <div class="sf-wrap" id="sf-materiales" style="display:none">
      <div class="sf-bar">
        <span class="sf-lbl">Sub-período:</span>
        <div class="sf-pills" id="sfp-materiales"></div>
        <button class="sf-reset" id="sfr-materiales" onclick="resetSubfiltro('materiales')" style="display:none">← Todo el período</button>
      </div>
    </div>
    <div class="mp-bars" id="mp-bars"><div class="ana-loading">Cargando...</div></div>
  </div>

  <!-- HEATMAP -->
  <div class="ana-section" id="sec-heatmap">
    <div class="ana-section-header">
      <h2>Concentración de pedidos por día y hora</h2>
      <span class="chart-note">Pedidos entregados del período</span>
    </div>
    <div class="sf-wrap" id="sf-heatmap" style="display:none">
      <div class="sf-bar">
        <span class="sf-lbl">Sub-período:</span>
        <div class="sf-pills" id="sfp-heatmap"></div>
        <button class="sf-reset" id="sfr-heatmap" onclick="resetSubfiltro('heatmap')" style="display:none">← Todo el período</button>
      </div>
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
        var v = parseInt(el.dataset.val, 10) || 0;
        countUp(el, v, false, i * 80);
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
                    titleFont: { family: 'Inter', size: 12 },
                    bodyFont:  { family: 'Inter', size: 13 },
                    callbacks: { label: function(c) { return ' ' + c.raw + ' lote' + (c.raw !== 1 ? 's' : ''); } }
                }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false },
                     ticks: { font: { size: 11, family: 'Inter' }, color: '#9e9e9a' } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 }, color: '#9e9e9a' },
                     grid: { color: 'rgba(0,0,0,.05)' }, border: { display: false } }
            }
        }
    });
}

// ── ANALÍTICA ─────────────────────────────────────────────────────────
var chartIngresos=null, chartResumen=null, chartPago=null;
var _modo='mes_actual', _lastData=null;

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
    ['tb-productos'].forEach(function(id){
        document.getElementById(id).innerHTML='<tr><td colspan="4" class="ana-loading">Cargando...</td></tr>';
    });
    document.getElementById('mp-bars').innerHTML='<div class="ana-loading">Cargando...</div>';

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
        renderCostosMP(data.costos_mp, data.costos);
        renderHeatmap(data.heatmap||{});
        actualizarSubfiltros();
    } catch(e){ showToast('Error de conexión: '+e.message,'err'); }
}

// ── KPIs ──────────────────────────────────────────────────────────────
function renderKPIs(k, c, re) {
    function animAmt(id, val) {
        var el=document.getElementById(id);
        countUp(el, parseFloat(val||0), true, 0);
    }
    function animCnt(id, val, suffix) {
        var el=document.getElementById(id);
        el.textContent = val + (suffix||'');
    }

    animAmt('k-ventas-hoy', k.ventas_hoy);
    animCnt('k-pedidos-hoy', k.pedidos_hoy, ' pedido'+(k.pedidos_hoy!=1?'s':''));
    animAmt('k-ventas-semana', k.ventas_semana);
    animCnt('k-pedidos-semana', k.pedidos_semana, ' pedido'+(k.pedidos_semana!=1?'s':''));
    animAmt('k-ventas-periodo', k.ventas_periodo);
    animCnt('k-pedidos-periodo', k.pedidos_periodo, ' pedido'+(k.pedidos_periodo!=1?'s':''));
    animAmt('k-costo-periodo', c.costo_periodo);

    var benef = parseFloat(k.ventas_periodo) - parseFloat(c.costo_periodo);
    var el=document.getElementById('k-beneficio');
    countUp(el, Math.abs(benef), true, 0);
    var card=document.getElementById('kpi-beneficio-card'), ico=document.getElementById('k-benef-ico');
    if (benef>=0) {
        el.style.color='#1a7a4a'; card.style.borderLeftColor='#1a7a4a'; card.style.background='#f0faf4';
        ico.innerHTML='<i class="fa-solid fa-arrow-trend-up" style="color:#1a7a4a"></i>';
    } else {
        el.style.color='#c0392b'; card.style.borderLeftColor='#c0392b'; card.style.background='#fff8f7';
        ico.innerHTML='<i class="fa-solid fa-arrow-trend-down" style="color:#c0392b"></i>';
    }

    // Retiro / Envío
    if (re && re.retiro !== undefined) {
        var r=re.retiro, env=re.envio||{cantidad:0,total:0};
        document.getElementById('k-retiro-cant').textContent = r.cantidad + ' pedido'+(r.cantidad!=1?'s':'');
        document.getElementById('k-retiro-total').textContent = fmt(r.total);
        document.getElementById('k-envio-cant').textContent = env.cantidad + ' pedido'+(env.cantidad!=1?'s':'');
        document.getElementById('k-envio-total').textContent = fmt(env.total);
    }
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
    document.getElementById('costo-total-badge').textContent='Total período: '+fmt(costos.costo_periodo);
    var cont=document.getElementById('mp-bars');
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
function renderHeatmap(heatmap) {
    var wrap=document.getElementById('hmWrap');
    var DIAS=['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
    var HORAS=Array.from({length:24},function(_,i){return String(i).padStart(2,'0');});
    var maxVal=0;
    for (var d=0;d<7;d++) for (var h=0;h<24;h++) maxVal=Math.max(maxVal,(heatmap[d]&&heatmap[d][h])||0);
    if (!maxVal) { wrap.innerHTML='<div class="ana-loading">Sin pedidos en el período</div>'; return; }
    var html='<div class="hm-grid"><div class="hm-label-corner"></div>';
    HORAS.forEach(function(h){ html+='<div class="hm-hour-lbl">'+h+'</div>'; });
    for (var dd=0;dd<7;dd++) {
        html+='<div class="hm-day-lbl">'+DIAS[dd]+'</div>';
        for (var hh=0;hh<24;hh++) {
            var val=(heatmap[dd]&&heatmap[dd][hh])||0;
            var niv=hmNivel(val,maxVal), col=HM_COLORS[niv];
            var tip=val?DIAS[dd]+' '+HORAS[hh]+':00 — '+val+' pedido'+(val!==1?'s':''):DIAS[dd]+' '+HORAS[hh]+':00 — sin pedidos';
            var bold=niv>=4?'border:2px solid rgba(255,255,255,.4);':'';
            html+='<div class="hm-cell" style="background:'+col+';'+bold+'" title="'+tip+'"></div>';
        }
    }
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

function abrirDetalle(tipo){
    if (!_lastData) return;
    var k=_lastData.kpis, c=_lastData.costos;
    var datos=tipo==='hoy'?{lbl:'Hoy',ventas:k.ventas_hoy,peds:k.pedidos_hoy,costo:c.costo_hoy}
        :tipo==='semana'?{lbl:'Esta semana',ventas:k.ventas_semana,peds:k.pedidos_semana,costo:c.costo_semana}
        :{lbl:'Período: '+(_lastData.periodo||''),ventas:k.ventas_periodo,peds:k.pedidos_periodo,costo:c.costo_periodo};
    var benef=parseFloat(datos.ventas)-parseFloat(datos.costo);
    abrirModal('Resumen — '+datos.lbl,
        '<table class="ana-table">'
        +'<tr><td>Ventas (entregadas)</td><td class="text-right"><strong>'+fmt(datos.ventas)+'</strong></td></tr>'
        +'<tr><td>Pedidos</td><td class="text-right">'+datos.peds+'</td></tr>'
        +'<tr><td>Inversión materiales</td><td class="text-right" style="color:#c88e99">'+fmt(datos.costo)+'</td></tr>'
        +'<tr><td><strong>Beneficio estimado</strong></td><td class="text-right"><strong style="color:'+(benef>=0?'#1a7a4a':'#c0392b')+'">'+fmt(benef)+'</strong></td></tr>'
        +'</table>');
}

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
var CARDS = ['ingresos','productos','pago','materiales','heatmap'];

function _dateStr(d) {
    return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
}

function generarSubOpciones() {
    if (!_lastData) return [];
    var inicio = new Date(_lastData.inicio+'T00:00:00');
    var fin    = new Date(_lastData.fin+'T00:00:00');
    var diffDays = Math.round((fin - inicio)/86400000) + 1;

    if (diffDays <= 1) return [];       // día exacto: sin sub-filtro

    if (diffDays <= 14) {               // semana o dos semanas: días individuales
        var ops=[], cur=new Date(inicio);
        var DIAS_CRT=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        while (cur<=fin) {
            var ds=_dateStr(cur);
            var d2=String(cur.getDate()).padStart(2,'0'), m2=String(cur.getMonth()+1).padStart(2,'0');
            ops.push({label: DIAS_CRT[cur.getDay()]+' '+d2+'/'+m2, desde:ds, hasta:ds});
            cur.setDate(cur.getDate()+1);
        }
        return ops;
    }

    if (diffDays <= 62) {               // mes o dos meses: semanas
        var ops=[], cur=new Date(inicio), sem=1;
        while (cur<=fin) {
            var semIni=new Date(cur);
            var semFin=new Date(cur); semFin.setDate(semFin.getDate()+6);
            if (semFin>fin) semFin=new Date(fin);
            var f=function(dd){return String(dd.getDate()).padStart(2,'0')+'/'+String(dd.getMonth()+1).padStart(2,'0');};
            ops.push({label:'Sem '+sem+' ('+f(semIni)+'–'+f(semFin)+')', desde:_dateStr(semIni), hasta:_dateStr(semFin)});
            cur.setDate(cur.getDate()+7); sem++;
        }
        return ops;
    }

    // año o rango largo: meses
    var ops=[], MESES_C=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    var cur=new Date(inicio.getFullYear(), inicio.getMonth(), 1);
    while (cur<=fin) {
        var mIni=new Date(cur);
        var mFin=new Date(cur.getFullYear(), cur.getMonth()+1, 0);
        if (mFin>fin) mFin=new Date(fin);
        ops.push({label: MESES_C[cur.getMonth()]+' '+cur.getFullYear(), desde:_dateStr(mIni), hasta:_dateStr(mFin)});
        cur.setMonth(cur.getMonth()+1);
    }
    return ops;
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
            case 'heatmap':    renderHeatmap(data.heatmap||{}); break;
        }
    } catch(e){ console.error('cargarCard error:', cardId, e); }
}

// ── TOGGLE DE VISTAS ─────────────────────────────────────────────────
var _anaLoaded=false;
function toggleVista(v) {
    var vD=document.getElementById('view-dashboard'), vA=document.getElementById('view-analitica');
    var nombre=document.getElementById('cv-nombre');
    if (v==='dashboard') {
        vD.style.display=''; vA.style.display='none';
        nombre.textContent='Dashboard';
        if (chartProd) setTimeout(function(){chartProd.resize();},50);
    } else {
        vD.style.display='none'; vA.style.display='';
        nombre.textContent='Analítica de ventas';
        if (!_anaLoaded) { cargar(); _anaLoaded=true; }
    }
    localStorage.setItem('canetto_vista',v);
}

document.addEventListener('DOMContentLoaded', function() {
    // Toggle de vista primero — no depende de nada externo
    var saved = localStorage.getItem('canetto_vista') || 'dashboard';
    var sel = document.getElementById('viewSelect');
    if (sel) sel.value = saved;
    toggleVista(saved);
    // Chart del dashboard — después, con guard por si falla
    try { initChartProd(); } catch(e) { console.error('Dashboard chart error:', e); }
});
</script>

<?php include __DIR__ . '/../panel/dashboard/layaut/footer.php'; ?>
