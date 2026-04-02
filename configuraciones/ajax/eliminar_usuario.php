<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json');

session_start();

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['idusuario'] ?? 0);

if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido.']);
    exit;
}

/* ── Prevenir auto-eliminación ── */
$sesionId = intval($_SESSION['usuario_id'] ?? 0);
if ($sesionId && $id === $sesionId) {
    echo json_encode(['ok' => false, 'msg' => 'No podés eliminar tu propia cuenta.']);
    exit;
}

try {

    $stmt = $pdo->prepare("DELETE FROM usuario WHERE idusuario = ?");
    $stmt->execute([$id]);

    echo json_encode(['ok' => true]);

} catch (PDOException $e) {

    echo json_encode(['ok' => false, 'msg' => 'No se puede eliminar: tiene registros asociados.']);

}
