<?php
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$id   = (int)($data['id'] ?? 0);
if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

try {
    $pdo  = Conexion::conectar();
    $row  = $pdo->prepare("SELECT codigo FROM cupones WHERE id=?");
    $row->execute([$id]);
    $cupon = $row->fetch();
    if (!$cupon) { echo json_encode(['ok'=>false,'msg'=>'Cupón no encontrado']); exit; }

    // Desactivar en vez de eliminar para mantener historial
    $pdo->prepare("UPDATE cupones SET activo=0, updated_at=NOW() WHERE id=?")->execute([$id]);
    audit($pdo,'eliminar','cupones',"Desactivó cupón: {$cupon['codigo']} (ID:{$id})");
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
