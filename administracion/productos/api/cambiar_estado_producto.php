<?php

define('APP_BOOT', true);

require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

try {

    $pdo = Conexion::conectar();

    $data = json_decode(file_get_contents("php://input"), true);

    $id = $data['id'] ?? null;
    $activo = $data['activo'] ?? null;

    if (!$id) {
        echo json_encode([
            "status" => "error",
            "mensaje" => "ID inválido"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE productos
        SET activo = ?
        WHERE idproductos = ?
    ");

    $stmt->execute([$activo, $id]);

    echo json_encode([
        "status" => "ok"
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "mensaje" => $e->getMessage()
    ]);

}