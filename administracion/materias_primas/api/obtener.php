<?php
declare(strict_types=1);

define('APP_BOOT', true);

require_once '../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

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