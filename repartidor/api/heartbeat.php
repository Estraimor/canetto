<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId = $_SESSION['repartidor_id'] ?? null;
if (!$repId) { echo json_encode(['ok' => false]); exit; }

try {
    $pdo = Conexion::conectar();
    try { $pdo->exec("ALTER TABLE usuario ADD COLUMN session_at DATETIME NULL"); } catch (Throwable $e) {}
    $pdo->prepare("UPDATE usuario SET session_at = NOW() WHERE idusuario = ?")->execute([$repId]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false]);
}
