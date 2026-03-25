<?php

define('APP_BOOT', true);

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {

    /* =========================
       DATOS RECETA
    ========================= */

    $nombre = trim($_POST['nombre'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');

    $masa_total = $_POST['masa_total'] ?? null;
    $cantidad_galletas = $_POST['cantidad_galletas'] ?? null;
    $unidad_medida_receta = $_POST['unidad_medida_receta'] ?? null;

    if ($nombre === '') {
        throw new Exception("El nombre de la receta es obligatorio");
    }


    /* =========================
       INGREDIENTES
    ========================= */

    $materias = $_POST['materia_prima'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $unidades = $_POST['unidad'] ?? [];

    if (!count($materias)) {
        throw new Exception("Debe agregar al menos una materia prima");
    }


    /* =========================
       INICIAR TRANSACCION
    ========================= */

    $pdo->beginTransaction();


    /* =========================
       INSERT RECETA
    ========================= */

    $sqlReceta = "
        INSERT INTO recetas
        (
            nombre,
            observacion,
            masa_total,
            cantidad_galletas,
            unidad_medida_idunidad_medida
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmtReceta = $pdo->prepare($sqlReceta);

    $stmtReceta->execute([
        $nombre,
        $observacion ?: null,
        $masa_total ?: null,
        $cantidad_galletas ?: null,
        $unidad_medida_receta ?: null
    ]);

    $receta_id = $pdo->lastInsertId();


    /* =========================
       INSERT INGREDIENTES
    ========================= */

    $sqlIngrediente = "
        INSERT INTO receta_ingredientes
        (
            recetas_idrecetas,
            materia_prima_idmateria_prima,
            cantidad,
            unidad_medida_idunidad_medida
        )
        VALUES (?, ?, ?, ?)
    ";

    $stmtIngrediente = $pdo->prepare($sqlIngrediente);

    for ($i = 0; $i < count($materias); $i++) {

        $materia = $materias[$i] ?? null;
        $cantidad = $cantidades[$i] ?? 0;
        $unidad = $unidades[$i] ?? null;

        if (!$materia || $cantidad <= 0) {
            continue;
        }

        $stmtIngrediente->execute([
            $receta_id,
            $materia,
            $cantidad,
            $unidad
        ]);
    }


    /* =========================
       CONFIRMAR TRANSACCION
    ========================= */

    $pdo->commit();


    /* =========================
       RESPUESTA
    ========================= */

    echo json_encode([
        "status"  => "ok",
        "mensaje" => "Receta creada correctamente"
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "status"  => "error",
        "mensaje" => $e->getMessage()
    ]);
}