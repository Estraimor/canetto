<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$stmt = $pdo->query("
    SELECT r.idrecetas, r.nombre, r.observacion,
           COUNT(ri.idreceta_ingredientes) as total_ingredientes
    FROM recetas r
    LEFT JOIN receta_ingredientes ri 
        ON ri.recetas_idrecetas = r.idrecetas
    GROUP BY r.idrecetas
    ORDER BY r.nombre ASC
");

$recetas = $stmt->fetchAll();
?>

<link rel="stylesheet" href="receta.css">

<div class="content-body">

    <div class="mp-header">
        <div>
            <div class="mp-title">Recetas</div>
            <div class="mp-sub">Gestión de formulaciones de productos</div>
        </div>

        <button class="btn-mp">
            + Nueva receta
        </button>
    </div>

    <div class="recetas-grid">
        <?php foreach ($recetas as $r): ?>
            <div class="receta-card">
                <h3><?= htmlspecialchars($r['nombre']) ?></h3>
                <p><?= htmlspecialchars($r['observacion'] ?? 'Sin observaciones') ?></p>

                <div class="badge">
                    <?= $r['total_ingredientes'] ?> ingredientes
                </div>

                <div class="card-actions">
                    <a href="ver.php?id=<?= $r['idrecetas'] ?>">Ver</a>
                    <a href="editar.php?id=<?= $r['idrecetas'] ?>">Editar</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>