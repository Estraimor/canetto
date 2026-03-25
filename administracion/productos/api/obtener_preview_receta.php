<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

try {

    if(!isset($_GET["id"])){
        echo json_encode([]);
        exit;
    }

    $id = intval($_GET["id"]);

    $pdo = Conexion::conectar();

    $sql = "
        SELECT 
            mp.nombre AS ingrediente,
            ri.cantidad,
            um.abreviatura AS unidad

        FROM receta_ingredientes ri

        INNER JOIN materia_prima mp
            ON mp.idmateria_prima = ri.materia_prima_idmateria_prima

        LEFT JOIN unidad_medida um
            ON um.idunidad_medida = ri.unidad_medida_idunidad_medida

        WHERE ri.recetas_idrecetas = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch(Exception $e){

    echo json_encode([
        "status"=>"error",
        "mensaje"=>$e->getMessage()
    ]);

}