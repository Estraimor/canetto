<?php

define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

$pdo = Conexion::conectar();

try {

    /* =========================
       DATOS RECETA
    ========================= */

    $nombre = trim($_POST['nombre'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');

    $masa_total = $_POST['masa_total'] ?? null;
    $cantidad_galletas = $_POST['cantidad_galletas'] ?? null;

    if ($nombre === '') {
        throw new Exception("El nombre de la receta es obligatorio");
    }

    /* =========================
       ARRAYS INGREDIENTES
    ========================= */

    $materias = $_POST['materia_prima'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $unidades = $_POST['unidad'] ?? [];

    if (!count($materias)) {
        throw new Exception("Debe agregar al menos una materia prima");
    }

    /* =========================
       TRANSACCION
    ========================= */

    $pdo->beginTransaction();

    /* =========================
       INSERT RECETA
    ========================= */

    $sqlReceta = "
        INSERT INTO recetas
        (nombre, observacion, masa_total, cantidad_galletas)
        VALUES (?, ?, ?, ?)
    ";

    $stmtReceta = $pdo->prepare($sqlReceta);

    $stmtReceta->execute([
        $nombre,
        $observacion ?: null,
        $masa_total ?: null,
        $cantidad_galletas ?: null
    ]);

    $receta_id = $pdo->lastInsertId();


    /* =========================
       INSERT INGREDIENTES
    ========================= */

    $sqlIng = "
        INSERT INTO receta_ingredientes
        (
            recetas_idrecetas,
            materia_prima_idmateria_prima,
            cantidad,
            unidad_medida_idunidad_medida
        )
        VALUES (?, ?, ?, ?)
    ";

    $stmtIng = $pdo->prepare($sqlIng);

    for ($i = 0; $i < count($materias); $i++) {

        $materia = $materias[$i] ?? null;
        $cantidad = $cantidades[$i] ?? 0;
        $unidad = $unidades[$i] ?? null;

        if (!$materia || $cantidad <= 0) {
            continue;
        }

        $stmtIng->execute([
            $receta_id,
            $materia,
            $cantidad,
            $unidad
        ]);
    }

    /* =========================
       COMMIT
    ========================= */

    $pdo->commit();

    header("Location: index.php");
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "<h3>Error al guardar receta</h3>";
    echo "<p>" . $e->getMessage() . "</p>";

}