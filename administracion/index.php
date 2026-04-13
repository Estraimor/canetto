<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';

$pdo = Conexion::conectar();

/* =========================
   KPIs
========================= */
$totalProductos    = $pdo->query("SELECT COUNT(*) FROM productos WHERE tipo='producto'")->fetchColumn();
$totalMP           = $pdo->query("SELECT COUNT(*) FROM materia_prima")->fetchColumn();
$totalProducciones = $pdo->query("SELECT COUNT(*) FROM produccion")->fetchColumn();
$produccionHoy     = $pdo->query("SELECT COUNT(*) FROM produccion WHERE DATE(fecha)=CURDATE()")->fetchColumn();

/* =========================
   ALERTAS
========================= */
$productosBajos = $pdo->query("
    SELECT p.nombre, sp.stock_actual, sp.stock_minimo
    FROM stock_productos sp
    INNER JOIN productos p ON p.idproductos = sp.productos_idproductos
    WHERE sp.stock_actual <= sp.stock_minimo AND sp.tipo_stock='CONGELADO'
")->fetchAll(PDO::FETCH_ASSOC);

$productosSinStock = $pdo->query("
    SELECT p.nombre FROM stock_productos sp
    INNER JOIN productos p ON p.idproductos = sp.productos_idproductos
    WHERE sp.stock_actual = 0
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   STOCK POR PRODUCTO
========================= */
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

/* =========================
   ÚLTIMAS PRODUCCIONES
========================= */
$ultimasProducciones = $pdo->query("
    SELECT p.nombre, pr.cantidad, pr.fecha
    FROM produccion pr
    INNER JOIN productos p ON p.recetas_idrecetas = pr.recetas_idrecetas
    ORDER BY pr.fecha DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   GRÁFICO 7 DÍAS
========================= */
$prod7dias = $pdo->query("
    SELECT DATE(fecha) AS dia, COUNT(*) AS total
    FROM produccion
    WHERE fecha >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(fecha)
    ORDER BY dia ASC
")->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = [];
$chartData   = [];
$prod7map    = array_column($prod7dias, 'total', 'dia');
$diaCorto    = ['Sun'=>'Dom','Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb'];
for ($i = 6; $i >= 0; $i--) {
    $d             = date('Y-m-d', strtotime("-{$i} days"));
    $key           = date('D', strtotime($d));
    $chartLabels[] = ($diaCorto[$key] ?? $key) . ' ' . date('d', strtotime($d));
    $chartData[]   = (int)($prod7map[$d] ?? 0);
}

/* =========================
   FECHA Y TOPBAR
========================= */
$diasSemana = ['Sunday'=>'Domingo','Monday'=>'Lunes','Tuesday'=>'Martes',
               'Wednesday'=>'Miércoles','Thursday'=>'Jueves',
               'Friday'=>'Viernes','Saturday'=>'Sábado'];
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

    <!-- ===== PAGE HEADER ===== -->
    <div class="db-page-header">
        <div class="db-header-left">
            <div class="db-header-eyebrow">
                <i class="fa-regular fa-calendar"></i>
                <?= $diaNombre ?>, <?= $fechaHoy ?>
            </div>
            <h1 class="db-title">Dashboard <em>general</em></h1>
            <p class="db-subtitle">Vista de operaciones · Canetto</p>
        </div>
    </div>

    <!-- ===== ACCESOS RÁPIDOS ===== -->
    <div class="db-quick-actions">
        <a href="<?= $baseUrl ?>/administracion/produccion/congelado/index.php" class="db-qa">
            <i class="fa-solid fa-snowflake"></i>
            <span>Masa congelada</span>
        </a>
        <a href="<?= $baseUrl ?>/administracion/produccion/horneado/index.php" class="db-qa">
            <i class="fa-solid fa-fire"></i>
            <span>Horneado</span>
        </a>
        <a href="<?= $baseUrl ?>/administracion/stock/index.php" class="db-qa">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Stock</span>
        </a>
        <a href="<?= $baseUrl ?>/administracion/recetas/index.php" class="db-qa">
            <i class="fa-solid fa-book-open"></i>
            <span>Recetas</span>
        </a>
        <a href="<?= $baseUrl ?>/administracion/ventas/ventas/index.php" class="db-qa">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Ventas</span>
        </a>
        <a href="<?= $baseUrl ?>/administracion/materias_primas/index.php" class="db-qa">
            <i class="fa-solid fa-seedling"></i>
            <span>Materias primas</span>
        </a>
    </div>

    <!-- ===== KPIs ===== -->
    <div class="db-kpi-row">

        <div class="db-kpi">
            <div class="db-kpi-icon db-kpi-icon--blue">
                <i class="fa-solid fa-cookie-bite"></i>
            </div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">Productos</div>
                <div class="db-kpi-val"><?= (int)$totalProductos ?></div>
                <div class="db-kpi-sub">activos en sistema</div>
            </div>
        </div>

        <div class="db-kpi">
            <div class="db-kpi-icon db-kpi-icon--green">
                <i class="fa-solid fa-seedling"></i>
            </div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">Materias primas</div>
                <div class="db-kpi-val"><?= (int)$totalMP ?></div>
                <div class="db-kpi-sub">registradas</div>
            </div>
        </div>

        <div class="db-kpi">
            <div class="db-kpi-icon db-kpi-icon--teal">
                <i class="fa-solid fa-industry"></i>
            </div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">Producciones totales</div>
                <div class="db-kpi-val"><?= (int)$totalProducciones ?></div>
                <div class="db-kpi-sub">históricas</div>
            </div>
        </div>

        <div class="db-kpi">
            <div class="db-kpi-icon db-kpi-icon--amber">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="db-kpi-body">
                <div class="db-kpi-label">Producidas hoy</div>
                <div class="db-kpi-val"><?= (int)$produccionHoy ?></div>
                <div class="db-kpi-sub"><?= $produccionHoy > 0 ? 'lotes registrados' : 'sin actividad aún' ?></div>
            </div>
        </div>

    </div>

    <!-- ===== STOCK + ALERTAS ===== -->
    <div class="db-grid2">

        <div class="db-card">
            <div class="db-card-title">
                <i class="fa-solid fa-chart-simple"></i>
                Niveles de stock
            </div>

            <?php if (empty($stockAgrupado)): ?>
                <p class="db-empty">Sin datos de stock.</p>
            <?php else: ?>
                <?php foreach ($stockAgrupado as $nombre => $tipos): ?>

                    <?php if (isset($tipos['congelado'])):
                        $r   = $tipos['congelado'];
                        $ref = max((float)$r['stock_minimo'] * 2, (float)$r['stock_actual'], 1);
                        $pct = min(round(((float)$r['stock_actual'] / $ref) * 100), 100);
                        $low = ((float)$r['stock_actual'] <= (float)$r['stock_minimo']);
                    ?>
                    <div class="db-stock-item">
                        <div class="db-stock-row">
                            <span class="db-stock-name">
                                <?= htmlspecialchars($nombre) ?>
                                <span class="db-stock-tipo">congelado</span>
                            </span>
                            <span class="db-stock-nums"><?= number_format($r['stock_actual'],0) ?> uds · mín <?= number_format($r['stock_minimo'],0) ?></span>
                        </div>
                        <div class="db-bar-track">
                            <div class="db-bar-fill db-bar--blue <?= $low ? 'db-bar--low' : '' ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <?php if ($low): ?><div class="db-stock-alert">Stock bajo mínimo (mín: <?= number_format($r['stock_minimo'],0) ?>)</div><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($tipos['hecho'])):
                        $r   = $tipos['hecho'];
                        $ref = max((float)$r['stock_minimo'] * 2, (float)$r['stock_actual'], 1);
                        $pct = min(round(((float)$r['stock_actual'] / $ref) * 100), 100);
                        $low = ((float)$r['stock_actual'] <= (float)$r['stock_minimo']);
                    ?>
                    <div class="db-stock-item">
                        <div class="db-stock-row">
                            <span class="db-stock-name">
                                <?= htmlspecialchars($nombre) ?>
                                <span class="db-stock-tipo">hecho</span>
                            </span>
                            <span class="db-stock-nums"><?= number_format($r['stock_actual'],0) ?> uds · mín <?= number_format($r['stock_minimo'],0) ?></span>
                        </div>
                        <div class="db-bar-track">
                            <div class="db-bar-fill db-bar--teal <?= $low ? 'db-bar--low' : '' ?>" style="width:<?= $pct ?>%"></div>
                        </div>
                        <?php if ($low): ?><div class="db-stock-alert">Stock bajo mínimo (mín: <?= number_format($r['stock_minimo'],0) ?>)</div><?php endif; ?>
                    </div>
                    <?php endif; ?>

                <?php endforeach; ?>

                <div class="db-legend">
                    <span class="db-legend-item"><span class="db-legend-dot db-legend-dot--blue"></span>Congelado</span>
                    <span class="db-legend-item"><span class="db-legend-dot db-legend-dot--teal"></span>Hecho</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="db-card">
            <div class="db-card-title">
                <i class="fa-solid fa-bell"></i>
                Alertas del sistema
            </div>

            <?php if (empty($productosBajos) && empty($productosSinStock)): ?>
                <div class="db-alert db-alert--ok">
                    <span class="db-dot db-dot--ok"></span>
                    Stock saludable — todo dentro de los rangos
                </div>
                <div class="db-alert db-alert--ok">
                    <span class="db-dot db-dot--ok"></span>
                    Todos los productos con stock disponible
                </div>
            <?php else: ?>
                <?php if ($productosBajos): ?>
                    <div class="db-alert db-alert--warn">
                        <span class="db-dot db-dot--warn"></span>
                        <?= count($productosBajos) ?> producto<?= count($productosBajos) > 1 ? 's' : '' ?> con stock bajo mínimo
                    </div>
                    <?php foreach ($productosBajos as $pb): ?>
                        <div class="db-alert-detail">
                            <?= htmlspecialchars($pb['nombre']) ?> — actual: <?= number_format($pb['stock_actual'],0) ?> / mín: <?= number_format($pb['stock_minimo'],0) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($productosSinStock): ?>
                    <div class="db-alert db-alert--danger">
                        <span class="db-dot db-dot--danger"></span>
                        <?= count($productosSinStock) ?> producto<?= count($productosSinStock) > 1 ? 's' : '' ?> sin stock
                    </div>
                    <?php foreach ($productosSinStock as $ps): ?>
                        <div class="db-alert-detail"><?= htmlspecialchars($ps['nombre']) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

            <div class="db-alert-note">
                Se alerta cuando el stock congelado cae por debajo del mínimo configurado por producto.
            </div>
        </div>

    </div>

    <!-- ===== GRÁFICO 7 DÍAS ===== -->
    <div class="db-chart-card">
        <div class="db-chart-head">
            <div class="db-card-title" style="margin:0">
                <i class="fa-solid fa-chart-bar"></i>
                Producción — últimos 7 días
            </div>
            <span class="db-count">lotes por día</span>
        </div>
        <div class="db-chart-wrap">
            <canvas id="chartProd" height="75"></canvas>
        </div>
    </div>

    <!-- ===== TABLA ÚLTIMAS PRODUCCIONES ===== -->
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
                                    <span class="db-pill db-pill--blue">registrado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
/* ---- Gráfico producción ---- */
new Chart(document.getElementById('chartProd'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Lotes',
            data: <?= json_encode($chartData) ?>,
            backgroundColor: 'rgba(55,138,221,0.10)',
            borderColor: '#378ADD',
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: c => `${c.raw} lote${c.raw !== 1 ? 's' : ''}` }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                border: { display: false },
                ticks: { font: { size: 11, family: 'DM Sans, sans-serif' }, color: '#9e9e9a' }
            },
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, font: { size: 11, family: 'DM Sans, sans-serif' }, color: '#9e9e9a' },
                grid: { color: 'rgba(0,0,0,.05)' },
                border: { display: false }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../panel/dashboard/layaut/footer.php'; ?>
