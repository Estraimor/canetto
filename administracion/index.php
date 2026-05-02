<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';

$pdo = Conexion::conectar();

/* KPIs */
$totalProductos    = $pdo->query("SELECT COUNT(*) FROM productos WHERE tipo='producto'")->fetchColumn();
$totalMP           = $pdo->query("SELECT COUNT(*) FROM materia_prima")->fetchColumn();
$totalProducciones = $pdo->query("SELECT COUNT(*) FROM produccion")->fetchColumn();
$produccionHoy     = $pdo->query("SELECT COUNT(*) FROM produccion WHERE DATE(fecha)=CURDATE()")->fetchColumn();

/* Pedidos pendientes */
try {
    $pedidosPendientes = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado IN ('pendiente','en_proceso')")->fetchColumn();
} catch (Throwable $e) { $pedidosPendientes = 0; }

/* Alertas */
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

/* Stock */
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

/* Últimas producciones */
$ultimasProducciones = $pdo->query("
    SELECT p.nombre, pr.cantidad, pr.fecha
    FROM produccion pr
    INNER JOIN productos p ON p.recetas_idrecetas = pr.recetas_idrecetas
    ORDER BY pr.fecha DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* Gráfico 7 días */
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

/* Auditorías recientes */
try {
    $auditoriasRecientes = $pdo->query("
        SELECT usuario_nombre, accion, modulo, descripcion, created_at
        FROM auditoria ORDER BY created_at DESC LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $auditoriasRecientes = []; }

/* Incidencias abiertas */
try {
    $incidenciasAbiertas = $pdo->query("SELECT COUNT(*) FROM incidencias WHERE estado='abierta'")->fetchColumn();
} catch (Throwable $e) { $incidenciasAbiertas = 0; }

/* Header */
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

<div class="db">
<div class="db-main">

    <!-- ===== HEADER ===== -->
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

    <!-- ===== ACCESOS RÁPIDOS ===== -->
    <div class="db-quick-actions">
        <a href="<?= URL_ADMIN ?>/produccion/congelado/index.php" class="db-qa">
            <i class="fa-solid fa-snowflake"></i><span>Masa congelada</span>
        </a>
        <a href="<?= URL_ADMIN ?>/produccion/horneado/index.php" class="db-qa">
            <i class="fa-solid fa-fire"></i><span>Horneado</span>
        </a>
        <a href="<?= URL_ADMIN ?>/stock/index.php" class="db-qa">
            <i class="fa-solid fa-boxes-stacked"></i><span>Stock</span>
        </a>
        <a href="<?= URL_ADMIN ?>/recetas/index.php" class="db-qa">
            <i class="fa-solid fa-book-open"></i><span>Recetas</span>
        </a>
        <a href="<?= URL_ADMIN ?>/Ventas/Ventas/index.php" class="db-qa">
            <i class="fa-solid fa-cart-shopping"></i><span>Nueva venta</span>
        </a>
        <a href="<?= URL_ADMIN ?>/materias_primas/index.php" class="db-qa">
            <i class="fa-solid fa-seedling"></i><span>Materias primas</span>
        </a>
    </div>

    <!-- ===== KPIs ===== -->
    <div class="db-kpi-row">
        <div class="db-kpi">
            <div class="db-kpi-icon"><i class="fa-solid fa-cookie-bite"></i></div>
            <div class="db-kpi-label">Productos activos</div>
            <div class="db-kpi-val"><?= (int)$totalProductos ?></div>
            <div class="db-kpi-sub">en sistema</div>
        </div>
        <div class="db-kpi">
            <div class="db-kpi-icon"><i class="fa-solid fa-calendar-day"></i></div>
            <div class="db-kpi-label">Producidas hoy</div>
            <div class="db-kpi-val"><?= (int)$produccionHoy ?></div>
            <div class="db-kpi-sub"><?= $produccionHoy > 0 ? 'lotes registrados' : 'sin actividad aún' ?></div>
        </div>
        <div class="db-kpi">
            <div class="db-kpi-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="db-kpi-label">Pedidos activos</div>
            <div class="db-kpi-val"><?= (int)$pedidosPendientes ?></div>
            <div class="db-kpi-sub">pendientes / en proceso</div>
        </div>
        <div class="db-kpi">
            <div class="db-kpi-icon"><i class="fa-solid fa-seedling"></i></div>
            <div class="db-kpi-label">Materias primas</div>
            <div class="db-kpi-val"><?= (int)$totalMP ?></div>
            <div class="db-kpi-sub">registradas</div>
        </div>
    </div>

    <!-- ===== GRID PRINCIPAL ===== -->
    <div class="db-layout">

        <!-- columna izquierda -->
        <div class="db-col-main">

            <!-- Stock -->
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
                            $actual = (float)$r['stock_actual'];
                            $minimo = (float)$r['stock_minimo'];
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

            <!-- Gráfico -->
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

            <!-- Últimas producciones -->
            <div class="db-table-card">
                <div class="db-table-head">
                    <span>Últimas producciones</span>
                    <span class="db-count"><?= count($ultimasProducciones) ?> registros</span>
                </div>
                <?php if (empty($ultimasProducciones)): ?>
                    <p class="db-empty db-empty--padded">Sin producciones registradas.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ultimasProducciones as $i => $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td class="db-mono"><?= number_format($p['cantidad'], 2) ?></td>
                            <td class="db-muted db-mono"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                            <td>
                                <?php if ($i === 0): ?>
                                    <span class="db-pill db-pill--green">reciente</span>
                                <?php else: ?>
                                    <span class="db-pill db-pill--gray">registrado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div><!-- /col-main -->

        <!-- columna derecha -->
        <div class="db-col-side">

            <!-- Alertas -->
            <div class="db-card">
                <div class="db-card-title">
                    <i class="fa-solid fa-bell"></i>
                    Alertas del sistema
                </div>
                <?php if (empty($productosBajos) && empty($productosSinStock)): ?>
                    <div class="db-alert db-alert--ok">
                        <span class="db-dot db-dot--ok"></span>
                        Stock saludable — todo en rango
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
                        <span class="db-dot db-dot--warn"></span>
                        <?= count($productosBajos) ?> bajo mínimo
                    </div>
                    <?php foreach ($productosBajos as $pb): ?>
                        <div class="db-alert-detail">⚠ <?= htmlspecialchars($pb['nombre']) ?> — <?= number_format($pb['stock_actual'],0) ?>/<?= number_format($pb['stock_minimo'],0) ?> uds</div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Auditorías recientes -->
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
                        if ($diff < 60)       $hace = 'hace ' . $diff . 's';
                        elseif ($diff < 3600) $hace = 'hace ' . floor($diff/60) . 'min';
                        elseif ($diff < 86400)$hace = 'hace ' . floor($diff/3600) . 'h';
                        else                  $hace = date('d/m', strtotime($a['created_at']));
                    }
                ?>
                    <div class="db-audit-item">
                        <div class="db-audit-icon <?= $clase ?>">
                            <i class="fa-solid <?= $icono ?>"></i>
                        </div>
                        <div class="db-audit-body">
                            <div class="db-audit-title">
                                <?= htmlspecialchars($a['usuario_nombre'] ?? 'Sistema') ?>
                                <span class="db-audit-accion"><?= htmlspecialchars($a['accion'] ?? '') ?></span>
                            </div>
                            <div class="db-audit-desc">
                                <?= htmlspecialchars(mb_strimwidth($a['descripcion'] ?? $a['modulo'] ?? '', 0, 55, '…')) ?>
                            </div>
                        </div>
                        <div class="db-audit-time"><?= $hace ?></div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /col-side -->

    </div><!-- /layout -->

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartProd'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Lotes',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(200,142,153,0.12)',
            borderColor: '#c88e99',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
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
                callbacks: { label: c => ` ${c.raw} lote${c.raw !== 1 ? 's' : ''}` }
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
</script>

<?php include __DIR__ . '/../panel/dashboard/layaut/footer.php'; ?>
