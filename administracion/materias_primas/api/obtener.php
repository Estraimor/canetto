<?php
declare(strict_types=1);

define('APP_BOOT', true);

require_once '../../../config/conexion.php';

header('Content-Type: application/json');

try {

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        echo json_encode([]);
        exit;
    }

    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT *
        FROM materia_prima
        WHERE idmateria_prima = ?
    ");

    $stmt->execute([$id]);

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($data ?: [], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}