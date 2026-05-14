<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

header('Content-Type: application/json');

// 🔥 conexión (SIEMPRE)
$pdo = Conexion::conectar();

try {

    $stmt = $pdo->query("SELECT * FROM proveedor ORDER BY nombre ASC");

    echo json_encode([
        'ok'   => true,
        'data' => $stmt->fetchAll()
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'ok'  => false,
        'msg' => $e->getMessage()
    ]);
}