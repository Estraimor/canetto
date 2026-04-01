<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

$data      = json_decode(file_get_contents('php://input'), true);
$idprov    = intval($data['idproveedor'] ?? 0);
$idmateria = intval($data['idmateria_prima'] ?? 0);

if (!$idprov || !$idmateria) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
    exit;
}

try {
    // 🔥 Crear conexión correctamente
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        DELETE FROM materia_prima_has_proveedor
        WHERE proveedor_idproveedor = ?
          AND materia_prima_idmateria_prima = ?
    ");

    $stmt->execute([$idprov, $idmateria]);

    echo json_encode([
        'ok' => true,
        'msg' => 'Asignación eliminada correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}