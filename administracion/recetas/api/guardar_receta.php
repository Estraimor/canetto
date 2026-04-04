<?php
define('APP_BOOT', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $nombre               = trim($_POST['nombre'] ?? '');
    $observacion          = trim($_POST['observacion'] ?? '');
    $masa_total           = $_POST['masa_total'] ?? null;
    $cantidad_galletas    = $_POST['cantidad_galletas'] ?? null;
    $unidad_medida_receta = $_POST['unidad_medida_receta'] ?? null;

    if ($nombre === '') throw new Exception("El nombre de la receta es obligatorio");

    $materias   = $_POST['materia_prima'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $unidades   = $_POST['unidad'] ?? [];

    if (!count($materias)) throw new Exception("Debe agregar al menos una materia prima");

    $pdo->beginTransaction();

    $pdo->prepare("
        INSERT INTO recetas (nombre, observacion, masa_total, cantidad_galletas, unidad_medida_idunidad_medida)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$nombre, $observacion ?: null, $masa_total ?: null, $cantidad_galletas ?: null, $unidad_medida_receta ?: null]);

    $receta_id = $pdo->lastInsertId();

    $stmtIng   = $pdo->prepare("
        INSERT INTO receta_ingredientes
        (recetas_idrecetas, materia_prima_idmateria_prima, cantidad, unidad_medida_idunidad_medida)
        VALUES (?, ?, ?, ?)
    ");
    $ingContador = 0;
    for ($i = 0; $i < count($materias); $i++) {
        $materia  = $materias[$i]   ?? null;
        $cantidad = $cantidades[$i] ?? 0;
        $unidad   = $unidades[$i]   ?? null;
        if (!$materia || $cantidad <= 0) continue;
        $stmtIng->execute([$receta_id, $materia, $cantidad, $unidad]);
        $ingContador++;
    }

    $pdo->commit();

    audit($pdo, 'crear', 'recetas',
        "Creó receta: '{$nombre}' (ID: {$receta_id})" .
        " | Ingredientes: {$ingContador}" .
        ($cantidad_galletas ? " | Cantidad base: {$cantidad_galletas} u." : '') .
        ($masa_total        ? " | Masa total: {$masa_total}"              : '') .
        ($observacion       ? " | Obs: {$observacion}"                    : '')
    );

    echo json_encode(["status" => "ok", "mensaje" => "Receta creada correctamente"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "mensaje" => $e->getMessage()]);
}
