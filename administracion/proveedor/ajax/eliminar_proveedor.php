<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

header('Content-Type: application/json');

// 🔥 CONEXIÓN (faltaba esto)
$pdo = Conexion::conectar();

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['idproveedor'] ?? 0);

if (!$id) {
    echo json_encode([
        'ok' => false,
        'msg' => 'ID inválido'
    ]);
    exit;
}

try {

    $stmt = $pdo->prepare("DELETE FROM proveedor WHERE idproveedor = ?");
    $stmt->execute([$id]);

    echo json_encode([
        'ok' => true
    ]);

} catch (PDOException $e) {

    // 🔥 opcional para debug real
    // echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);

    echo json_encode([
        'ok' => false,
        'msg' => 'No se puede eliminar: tiene registros asociados.'
    ]);
}