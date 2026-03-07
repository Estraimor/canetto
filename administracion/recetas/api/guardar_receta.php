<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';

$pdo = Conexion::conectar();

$nombre = trim($_POST['nombre']);
$observacion = trim($_POST['observacion']);

if ($nombre === '') {
    die("Nombre obligatorio");
}

$pdo->beginTransaction();

$stmt = $pdo->prepare("
    INSERT INTO recetas (nombre, observacion)
    VALUES (?, ?)
");

$stmt->execute([$nombre, $observacion ?: null]);

$receta_id = $pdo->lastInsertId();

$materias = $_POST['materia_prima'];
$cantidades = $_POST['cantidad'];
$unidades = $_POST['unidad'];

for ($i = 0; $i < count($materias); $i++) {

    if ($cantidades[$i] > 0) {

        $stmtIng = $pdo->prepare("
            INSERT INTO receta_ingredientes
            (recetas_idrecetas, materia_prima_idmateria_prima, cantidad, unidad_idunidad_medida)
            VALUES (?, ?, ?, ?)
        ");

        $stmtIng->execute([
            $receta_id,
            $materias[$i],
            $cantidades[$i],
            $unidades[$i]
        ]);
    }
}

$pdo->commit();

header("Location: ../index.php");
exit;