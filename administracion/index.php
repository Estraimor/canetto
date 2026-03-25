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
ULTIMAS PRODUCCIONES
========================= */

$ultimasProducciones = $pdo->query("
    SELECT p.nombre, pr.cantidad, pr.fecha
    FROM produccion pr
    INNER JOIN productos p 
        ON p.recetas_idrecetas = pr.recetas_idrecetas
    ORDER BY pr.fecha DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>

<link rel="stylesheet" href="dashboard.css">

<div class="dashboard">

<h2>📊 Dashboard General</h2>

<!-- =========================
KPIs
========================= -->
<div class="cards">

    <div class="card blue">
        <h3>Productos</h3>
        <div class="value"><?= $totalProductos ?></div>
    </div>

    <div class="card green">
        <h3>Stock Congelado</h3>
        <div class="value"><?= $stockCongelado ?></div>
    </div>

    <div class="card orange">
        <h3>Stock Hecho</h3>
        <div class="value"><?= $stockHecho ?></div>
    </div>

    <div class="card blue">
        <h3>Materias Primas</h3>
        <div class="value"><?= $totalMP ?></div>
    </div>

    <div class="card green">
        <h3>Producciones</h3>
        <div class="value"><?= $totalProducciones ?></div>
    </div>

    <div class="card orange">
        <h3>Hoy</h3>
        <div class="value"><?= $produccionHoy ?></div>
    </div>

</div>

<!-- =========================
ALERTAS
========================= -->
<div class="section">

    <h3>⚠️ Alertas</h3>

    <?php if ($productosBajos): ?>
        <div class="alert warn">
            Productos con stock bajo: <?= count($productosBajos) ?>
        </div>
    <?php else: ?>
        <div class="alert ok">Stock saludable</div>
    <?php endif; ?>

    <?php if ($productosSinStock): ?>
        <div class="alert warn">
            Productos sin stock: <?= count($productosSinStock) ?>
        </div>
    <?php endif; ?>

</div>

<!-- =========================
ULTIMAS PRODUCCIONES
========================= -->
<div class="section">

    <h3>🧾 Últimas producciones</h3>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>

            <?php foreach ($ultimasProducciones as $p): ?>
                <tr>
                    <td><?= $p['nombre'] ?></td>
                    <td><?= $p['cantidad'] ?></td>
                    <td><?= $p['fecha'] ?></td>
                </tr>
            <?php endforeach; ?>

        </tbody>
    </table>

</div>

</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>