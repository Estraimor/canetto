<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';

$pdo = Conexion::conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre']);
    $observacion = trim($_POST['observacion']);

    if ($nombre !== '') {

        $stmt = $pdo->prepare("
            INSERT INTO recetas (nombre, observacion)
            VALUES (:nombre, :observacion)
        ");

        $stmt->execute([
            'nombre' => $nombre,
            'observacion' => $observacion ?: null
        ]);

        $id = $pdo->lastInsertId();

        // REDIRECCIÓN ANTES DE CUALQUIER HTML
        header("Location: preparar.php?id=$id");
        exit;
    }
}

/* RECIÉN ACÁ INCLUIMOS NAV */
include '../../panel/dashboard/layaut/nav.php';
?>

<link rel="stylesheet" href="receta.css">

<div class="content-body">

    <div class="form-card">

        <h2>Nueva Receta</h2>

        <form method="POST">

            <div class="form-group">
                <label>Nombre de la receta</label>
                <input type="text" name="nombre" required>
            </div>

            <div class="form-group">
                <label>Observación</label>
                <textarea name="observacion"></textarea>
            </div>

            <button class="btn-mp">Guardar y preparar</button>

        </form>

    </div>

</div>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>