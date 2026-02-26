<?php
declare(strict_types=1);
define('APP_BOOT', true);

require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

try {

    $pdo = Conexion::conectar();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    $sql = "DELETE FROM materia_prima WHERE idmateria_prima = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}