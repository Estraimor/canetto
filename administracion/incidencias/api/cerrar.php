<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();
    $id  = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['success' => false, 'message' => 'ID inválido']); exit; }

    $pdo->prepare("UPDATE incidencias SET estado='cerrada' WHERE id=?")->execute([$id]);
    audit($pdo, 'editar', 'incidencias', "Cerró incidencia #{$id}");
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
