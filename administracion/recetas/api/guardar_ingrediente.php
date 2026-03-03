<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';

$pdo = Conexion::conectar();

$receta_id = (int)$_POST['receta_id'];
$materia = (int)$_POST['materia_prima'];
$cantidad = (float)$_POST['cantidad'];
$unidad = (int)$_POST['unidad'];

$stmt = $pdo->prepare("
    INSERT INTO receta_ingredientes
    (recetas_idrecetas, materia_prima_idmateria_prima, cantidad, unidad_medida_idunidad_medida, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

$stmt->execute([$receta_id, $materia, $cantidad, $unidad]);