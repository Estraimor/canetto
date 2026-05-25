<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId = $_SESSION['repartidor_id'] ?? null;
if ($repId) {
    try {
        $pdo = Conexion::conectar();
        $pdo->prepare("UPDATE usuario SET session_at = NULL WHERE idusuario = ?")->execute([$repId]);
    } catch (Throwable $e) {}
}

unset($_SESSION['repartidor_id'], $_SESSION['repartidor_nombre']);
echo json_encode(['success' => true]);
