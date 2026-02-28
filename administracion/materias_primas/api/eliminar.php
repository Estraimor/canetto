<?php
declare(strict_types=1);

define('APP_BOOT', true);

require_once '../../../config/conexion.php';

header('Content-Type: application/json');

try {

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        echo json_encode(['success' => false]);
        exit;
    }

    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        UPDATE materia_prima
        SET activo = 0,
            updated_at = NOW()
        WHERE idmateria_prima = ?
    ");

    $stmt->execute([$id]);

    echo json_encode(['success' => true]);

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}