<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../config/conexion.php';
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

/* =========================
   KPIs
========================= */

$totalProductos = $pdo->query("SELECT COUNT(*) FROM productos WHERE tipo='producto'")->fetchColumn();

$stockCongelado = $pdo->query("
    SELECT COALESCE(SUM(stock_actual),0)
    FROM stock_productos
    WHERE tipo_stock = 'CONGELADO'
")->fetchColumn();

$stockHecho = $pdo->query("
    SELECT COALESCE(SUM(stock_actual),0)
    FROM stock_productos
    WHERE tipo_stock = 'HECHO'
")->fetchColumn();

$totalMP = $pdo->query("SELECT COUNT(*) FROM materia_prima")->fetchColumn();

$totalProducciones = $pdo->query("SELECT COUNT(*) FROM produccion")->fetchColumn();

$produccionHoy = $pdo->query("
    SELECT COUNT(*) 
    FROM produccion 
    WHERE DATE(fecha) = CURDATE()
")->fetchColumn();

/* =========================
   ALERTAS
========================= */

$productosBajos = $pdo->query("
    SELECT p.nombre, sp.stock_actual, sp.stock_minimo
    FROM stock_productos sp
    INNER JOIN productos p ON p.idproductos = sp.productos_idproductos
    WHERE sp.stock_actual <= sp.stock_minimo
    AND sp.tipo_stock = 'CONGELADO'
")->fetchAll(PDO::FETCH_ASSOC);

$productosSinStock = $pdo->query("
    SELECT p.nombre
    FROM stock_productos sp
    INNER JOIN productos p ON p.idproductos = sp.productos_idproductos
    WHERE sp.stock_actual = 0
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   STOCK POR PRODUCTO (para barras)
========================= */

$stockPorProducto = $pdo->query("
    SELECT 
        p.nombre,
        sp.tipo_stock,
        sp.stock_actual,
        sp.stock_minimo
    FROM stock_productos sp
    INNER JOIN productos p ON p.idproductos = sp.productos_idproductos
    ORDER BY p.nombre, sp.tipo_stock
")->fetchAll(PDO::FETCH_ASSOC);

/* Agrupar por producto */
$stockAgrupado = [];
foreach ($stockPorProducto as $row) {
    $nombre = $row['nombre'];
    $tipo   = strtolower($row['tipo_stock']);
    if (!isset($stockAgrupado[$nombre])) {
        $stockAgrupado[$nombre] = [];
    }
    $stockAgrupado[$nombre][$tipo] = $row;
}

/* =========================
   ÚLTIMAS PRODUCCIONES
========================= */

$ultimasProducciones = $pdo->query("
    SELECT p.nombre, pr.cantidad, pr.fecha
    FROM produccion pr
    INNER JOIN productos p 
        ON p.recetas_idrecetas = pr.recetas_idrecetas
    ORDER BY pr.fecha DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* Fecha legible para el header */
$fechaHoy = (new DateTime())->format('d/m/Y');
$diasSemana = ['Sunday'=>'domingo','Monday'=>'lunes','Tuesday'=>'martes',
               'Wednesday'=>'miércoles','Thursday'=>'jueves',
               'Friday'=>'viernes','Saturday'=>'sábado'];
$diaNombre = $diasSemana[(new DateTime())->format('l')] ?? '';

?>
<!-- Las fuentes vienen del nav global -->
<link rel="stylesheet" href="dashboard.css">

<div class="db">
    <div class="db-main">

        <!-- PAGE HEADER -->
        <div class="db-page-header">
            <h1>Dashboard general</h1>
            <p><?= ucfirst($diaNombre) ?> <?= $fechaHoy ?></p>
        </div>

        <!-- KPIs -->
        <div class="db-kpi-row">

            <div class="db-kpi db-kpi--blue">
                <div class="db-kpi-label">Productos</div>
                <div class="db-kpi-val"><?= (int)$totalProductos ?></div>
                <div class="db-kpi-sub">activos en sistema</div>
            </div>

            <div class="db-kpi db-kpi--green">
                <div class="db-kpi-label">Materias primas</div>
                <div class="db-kpi-val"><?= (int)$totalMP ?></div>
                <div class="db-kpi-sub">registradas</div>
            </div>

            <div class="db-kpi db-kpi--teal">
                <div class="db-kpi-label">Producciones totales</div>
                <div class="db-kpi-val"><?= (int)$totalProducciones ?></div>
                <div class="db-kpi-sub">históricas</div>
            </div>

            <div class="db-kpi db-kpi--amber">
                <div class="db-kpi-label">Producidas hoy</div>
                <div class="db-kpi-val"><?= (int)$produccionHoy ?></div>
                <div class="db-kpi-sub"><?= $produccionHoy > 0 ? 'lotes registrados' : 'sin actividad aún' ?></div>
            </div>

        </div>

        <!-- STOCK + ALERTAS -->
        <div class="db-grid2">

            <!-- STOCK BARRAS -->
            <div class="db-card">
                <div class="db-card-title">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                        <rect x="1" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/>
                        <rect x="9" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/>
                        <rect x="1" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/>
                        <rect x="9" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    Niveles de stock
                </div>

                <?php if (empty($stockAgrupado)): ?>
                    <p class="db-empty">Sin datos de stock.</p>
                <?php else: ?>
                    <?php foreach ($stockAgrupado as $nombre => $tipos): ?>

                        <?php if (isset($tipos['congelado'])): 
                            $r   = $tipos['congelado'];
                            /* Sin stock_maximo: usamos stock_minimo*2 como tope visual.
                               Si stock_actual supera ese tope, la barra llega al 100%. */
                            $ref = max((float)$r['stock_minimo'] * 2, (float)$r['stock_actual'], 1);
                            $pct = min(round(((float)$r['stock_actual'] / $ref) * 100), 100);
                            $low = ((float)$r['stock_actual'] <= (float)$r['stock_minimo']);
                        ?>
                        <div class="db-stock-item">
                            <div class="db-stock-row">
                                <span class="db-stock-name"><?= htmlspecialchars($nombre) ?> <span class="db-stock-tipo">congelado</span></span>
                                <span class="db-stock-nums"><?= number_format($r['stock_actual'], 0) ?> uds · mín <?= number_format($r['stock_minimo'], 0) ?></span>
                            </div>
                            <div class="db-bar-track">
                                <div class="db-bar-fill db-bar--blue <?= $low ? 'db-bar--low' : '' ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <?php if ($low): ?>
                                <div class="db-stock-alert">Stock bajo mínimo (mín: <?= number_format($r['stock_minimo'], 0) ?>)</div>
                            <?php endif; ?>
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
                                <span class="db-stock-name"><?= htmlspecialchars($nombre) ?> <span class="db-stock-tipo">hecho</span></span>
                                <span class="db-stock-nums"><?= number_format($r['stock_actual'], 0) ?> uds · mín <?= number_format($r['stock_minimo'], 0) ?></span>
                            </div>
                            <div class="db-bar-track">
                                <div class="db-bar-fill db-bar--teal <?= $low ? 'db-bar--low' : '' ?>" style="width:<?= $pct ?>%"></div>
                            </div>
                            <?php if ($low): ?>
                                <div class="db-stock-alert">Stock bajo mínimo (mín: <?= number_format($r['stock_minimo'], 0) ?>)</div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    <?php endforeach; ?>

                    <div class="db-legend">
                        <span class="db-legend-item"><span class="db-legend-dot db-legend-dot--blue"></span>Congelado</span>
                        <span class="db-legend-item"><span class="db-legend-dot db-legend-dot--teal"></span>Hecho</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ALERTAS -->
            <div class="db-card">
                <div class="db-card-title">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                        <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M8 4.5v4l2.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
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
                                <?= htmlspecialchars($pb['nombre']) ?> — actual: <?= number_format($pb['stock_actual'], 0) ?> / mín: <?= number_format($pb['stock_minimo'], 0) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($productosSinStock): ?>
                        <div class="db-alert db-alert--danger">
                            <span class="db-dot db-dot--danger"></span>
                            <?= count($productosSinStock) ?> producto<?= count($productosSinStock) > 1 ? 's' : '' ?> sin stock
                        </div>
                        <?php foreach ($productosSinStock as $ps): ?>
                            <div class="db-alert-detail">
                                <?= htmlspecialchars($ps['nombre']) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="db-alert-note">
                    Se alerta cuando el stock congelado cae por debajo del mínimo configurado por producto.
                </div>
            </div>

        </div>

        <!-- TABLA ÚLTIMAS PRODUCCIONES -->
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

<?php include $_SERVER['DOCUMENT_ROOT'] . '/canetto/panel/dashboard/layaut/footer.php'; ?>
