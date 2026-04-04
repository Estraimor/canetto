<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$pdo = Conexion::conectar();

$receta_id = (int)$_POST['receta_id'];
$materia   = (int)$_POST['materia_prima'];
$cantidad  = (float)$_POST['cantidad'];
$unidad    = (int)$_POST['unidad'];

$pdo->prepare("
    INSERT INTO receta_ingredientes
    (recetas_idrecetas, materia_prima_idmateria_prima, cantidad, unidad_medida_idunidad_medida, created_at)
    VALUES (?, ?, ?, ?, NOW())
")->execute([$receta_id, $materia, $cantidad, $unidad]);

// Obtener nombres para descripción rica
$stmtMP = $pdo->prepare("SELECT nombre FROM materia_prima WHERE idmateria_prima = ?");
$stmtMP->execute([$materia]);
$nombreMP = $stmtMP->fetchColumn() ?: "ID {$materia}";

$stmtUM = $pdo->prepare("SELECT abreviatura FROM unidad_medida WHERE idunidad_medida = ?");
$stmtUM->execute([$unidad]);
$abrevUM = $stmtUM->fetchColumn() ?: '';

$stmtRec = $pdo->prepare("SELECT nombre FROM recetas WHERE idrecetas = ?");
$stmtRec->execute([$receta_id]);
$nombreReceta = $stmtRec->fetchColumn() ?: "ID {$receta_id}";

audit($pdo, 'editar', 'recetas',
    "Agregó ingrediente a receta '{$nombreReceta}'" .
    " | Ingrediente: {$nombreMP} x {$cantidad} {$abrevUM}"
);

echo json_encode(["status" => "ok"]);
