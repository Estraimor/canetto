<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM recetas WHERE idrecetas = ?");
$stmt->execute([$id]);
$receta = $stmt->fetch();

if (!$receta) {
    die("Receta no encontrada");
}

$materias = $pdo->query("SELECT idmateria_prima, nombre FROM materia_prima WHERE activo = 1 ORDER BY nombre")->fetchAll();
$unidades = $pdo->query("SELECT * FROM unidad_medida")->fetchAll();
?>

<link rel="stylesheet" href="receta.css">

<div class="content-body">

    <div class="mp-header">
        <div>
            <div class="mp-title"><?= htmlspecialchars($receta['nombre']) ?></div>
            <div class="mp-sub">Preparación de receta</div>
        </div>
    </div>

    <div class="form-card">

        <form id="formIngrediente">

            <input type="hidden" name="receta_id" value="<?= $id ?>">

            <div class="form-grid">

                <select name="materia_prima" required>
                    <option value="">Materia prima</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?= $m['idmateria_prima'] ?>">
                            <?= htmlspecialchars($m['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="number" step="0.01" name="cantidad" placeholder="Cantidad" required>

                <select name="unidad" required>
                    <option value="" disabled selected hidden>
                        Selecciona una Unidad de Medida
                    </option>

                    <?php foreach ($unidades as $u): ?>
                        <option value="<?= $u['idunidad_medida'] ?>">
                            <?= htmlspecialchars($u['abreviatura']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-mp">Agregar</button>

            </div>

        </form>

    </div>

    <div class="ingredientes-list" id="ingredientesList"></div>

</div>

<script>
document.getElementById("formIngrediente").addEventListener("submit", function(e){
    e.preventDefault();

    fetch("./api/guardar_ingrediente.php", {
        method: "POST",
        body: new FormData(this)
    })
    .then(r => r.text())
    .then(() => location.reload());
});
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>