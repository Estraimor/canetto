<?php

define('APP_BOOT', true);

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

try{

    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* ======================
       DATOS RECETA
    ====================== */

    $id = $_POST['id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');

    $masa_total = $_POST['masa_total'] ?? null;
    $unidad_medida = $_POST['unidad_medida'] ?? null;
    $cantidad_galletas = $_POST['cantidad_galletas'] ?? null;

    if(!$id){
        throw new Exception("ID de receta inválido");
    }

    /* ======================
       ACTUALIZAR RECETA
    ====================== */

    $stmt = $pdo->prepare("
        UPDATE recetas
        SET 
            nombre = ?,
            observacion = ?,
            masa_total = ?,
            unidad_medida_idunidad_medida = ?,
            cantidad_galletas = ?
        WHERE idrecetas = ?
    ");

    $stmt->execute([
        $nombre,
        $observacion,
        $masa_total,
        $unidad_medida,
        $cantidad_galletas,
        $id
    ]);



    /* ======================
       BORRAR INGREDIENTES
    ====================== */

    $stmt = $pdo->prepare("
        DELETE FROM receta_ingredientes
        WHERE recetas_idrecetas = ?
    ");

    $stmt->execute([$id]);



    /* ======================
       INSERTAR NUEVOS
    ====================== */

    $materias = $_POST['materia_prima'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $unidades = $_POST['unidad'] ?? [];

    if(!empty($materias)){

        $stmt = $pdo->prepare("
            INSERT INTO receta_ingredientes
            (
                recetas_idrecetas,
                materia_prima_idmateria_prima,
                cantidad,
                unidad_medida_idunidad_medida
            )
            VALUES (?,?,?,?)
        ");

        for($i=0; $i<count($materias); $i++){

            $materia = $materias[$i] ?? null;
            $cantidad = $cantidades[$i] ?? 0;
            $unidad = $unidades[$i] ?? null;

            if(!$materia) continue;

            $stmt->execute([
                $id,
                $materia,
                $cantidad,
                $unidad
            ]);
        }

    }


    /* ======================
       RESPUESTA
    ====================== */

    echo json_encode([
        "status" => "ok"
    ]);

}catch(Exception $e){

    echo json_encode([
        "status" => "error",
        "mensaje" => $e->getMessage()
    ]);

}