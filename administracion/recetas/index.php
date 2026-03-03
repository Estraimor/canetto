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

$recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="receta.css">

<div class="content-body">

    <!-- HEADER -->
    <div class="mp-header">
        <div>
            <div class="mp-title">Recetas</div>
            <div class="mp-sub">Gestión de formulaciones y composición de productos</div>
        </div>

        <button class="btn-mp" onclick="window.location='crear.php'">
            <i class="fa-solid fa-plus"></i> Nueva receta
        </button>
    </div>

    <!-- GRID -->
    <?php if (count($recetas) > 0): ?>
        <div class="recetas-grid">
            <?php foreach ($recetas as $r): ?>
                <div class="receta-card">

                    <div class="receta-header">
                        <h3><?= htmlspecialchars($r['nombre']) ?></h3>
                        <span class="badge">
                            <?= $r['total_ingredientes'] ?> ingredientes
                        </span>
                    </div>

                    <p class="receta-desc">
                        <?= htmlspecialchars($r['observacion'] ?? 'Sin observaciones registradas') ?>
                    </p>

                    <div class="card-actions">
                        <a class="btn-link" href="preparar.php?id=<?= $r['idrecetas'] ?>">
                            <i class="fa-solid fa-flask"></i> Preparar
                        </a>

                        <a class="btn-link muted" href="editar.php?id=<?= $r['idrecetas'] ?>">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>

        <!-- ESTADO VACÍO -->
        <div class="empty-state">
            <i class="fa-solid fa-cookie-bite"></i>
            <h3>No hay recetas creadas</h3>
            <p>Comenzá creando tu primera formulación de producto.</p>
            <button class="btn-mp" onclick="window.location='crear.php'">
                Crear receta
            </button>
        </div>

    <?php endif; ?>

</div>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>