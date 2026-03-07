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
<div class="main-wrapper">
    <div class="content-body">
        <!-- HEADER -->
        <div class="recetas-header">
            <div>
            <h1>Recetas</h1>
            <p>Gestión de formulaciones y composición de productos</p>
        </div>

        <a href="crear.php" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Nueva receta
        </a>
    </div>

    <?php if (count($recetas) > 0): ?>

        <div class="recetas-grid">

            <?php foreach ($recetas as $r): ?>

                <?php
                // Traer ingredientes de cada receta
                $stmtIng = $pdo->prepare("
                    SELECT mp.nombre, ri.cantidad, um.abreviatura
                    FROM receta_ingredientes ri
                    INNER JOIN materia_prima mp 
                        ON mp.idmateria_prima = ri.materia_prima_idmateria_prima
                    INNER JOIN unidad_medida um
                        ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
                    WHERE ri.recetas_idrecetas = ?
                ");
                $stmtIng->execute([$r['idrecetas']]);
                $ingredientes = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <div class="receta-card">

                    <div class="receta-main" onclick="toggleReceta(<?= $r['idrecetas'] ?>)">
                        <div class="receta-info">
                            <h3><?= htmlspecialchars($r['nombre']) ?></h3>
                            <span class="badge">
                                <?= $r['total_ingredientes'] ?> ingredientes
                            </span>
                        </div>

                        <div class="receta-actions">
                            <i class="fa-solid fa-chevron-down arrow" id="arrow-<?= $r['idrecetas'] ?>"></i>
                        </div>
                    </div>

                    <p class="receta-desc">
                        <?= htmlspecialchars($r['observacion'] ?? 'Sin observaciones registradas') ?>
                    </p>

                    <!-- SECCIÓN EXPANDIBLE -->
                    <div class="receta-expand" id="expand-<?= $r['idrecetas'] ?>">

                        <?php if (count($ingredientes) > 0): ?>

                            <div class="ingredientes-list">
                                <?php foreach ($ingredientes as $ing): ?>
                                    <div class="ingrediente-item">
                                        <span><?= htmlspecialchars($ing['nombre']) ?></span>
                                        <strong>
                                            <?= $ing['cantidad'] . ' ' . $ing['abreviatura'] ?>
                                        </strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php else: ?>
                            <div class="sin-ingredientes">
                                No tiene ingredientes cargados
                            </div>
                        <?php endif; ?>

                        <div class="expand-actions">
                            <a href="preparar.php?id=<?= $r['idrecetas'] ?>" class="btn-card primary">
                                <i class="fa-solid fa-flask"></i> Preparar
                            </a>

                            <a href="editar.php?id=<?= $r['idrecetas'] ?>" class="btn-card ghost">
                                <i class="fa-solid fa-pen"></i> Editar
                            </a>
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty-state">
            <i class="fa-solid fa-cookie-bite"></i>
            <h3>No hay recetas creadas</h3>
            <p>Comenzá creando tu primera formulación de producto.</p>
            <a href="crear.php" class="btn-primary">Crear receta</a>
        </div>

    <?php endif; ?>

</div>
</div>

<script>
function toggleReceta(id) {
    const expand = document.getElementById('expand-' + id);
    const arrow = document.getElementById('arrow-' + id);

    expand.classList.toggle('open');
    arrow.classList.toggle('rotate');
}
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>