<?php

define('APP_BOOT', true);

require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if(!$id){
    echo json_encode([]);
    exit;
}

try{

    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT 
            bp.producto_item AS producto_id,
            p.nombre AS producto,
            bp.cantidad
        FROM box_productos bp
        LEFT JOIN productos p
            ON p.idproductos = bp.producto_item
        WHERE bp.producto_box = ?
        ORDER BY p.nombre ASC
    ");

    $stmt->execute([$id]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

}catch(Exception $e){

    echo json_encode([]);

}