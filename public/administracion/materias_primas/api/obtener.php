<?php
declare(strict_types=1);
define('APP_BOOT', true);

require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

try {

    $pdo = Conexion::conectar();

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    $sql = "
        SELECT *
        FROM materia_prima
        WHERE idmateria_prima = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'No encontrado']);
        exit;
    }

    echo json_encode($row);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}