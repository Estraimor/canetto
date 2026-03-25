<?php

define('APP_BOOT', true);

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$id = $_GET['id'] ?? null;

if(!$id){
    echo json_encode(["error"=>"ID inválido"]);
    exit;
}

/* =====================
RECETA
===================== */

$stmt = $pdo->prepare("SELECT * FROM recetas WHERE idrecetas = ?");
$stmt->execute([$id]);
$receta = $stmt->fetch(PDO::FETCH_ASSOC);

/* =====================
INGREDIENTES
===================== */

$stmt = $pdo->prepare("
SELECT
    ri.idreceta_ingredientes,
    ri.cantidad,
    ri.materia_prima_idmateria_prima AS idmateria_prima,
    ri.unidad_medida_idunidad_medida
FROM receta_ingredientes ri
WHERE ri.recetas_idrecetas = ?
");

$stmt->execute([$id]);
$ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =====================
MATERIAS PRIMAS
===================== */

$materias = $pdo->query("
SELECT idmateria_prima,nombre
FROM materia_prima
WHERE activo = 1
ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================
UNIDADES
===================== */

$unidades = $pdo->query("
SELECT idunidad_medida,abreviatura
FROM unidad_medida
ORDER BY abreviatura
")->fetchAll(PDO::FETCH_ASSOC);

/* =====================
RESPUESTA
===================== */

echo json_encode([
    "receta"=>$receta,
    "ingredientes"=>$ingredientes,
    "materias"=>$materias,
    "unidades"=>$unidades
]);