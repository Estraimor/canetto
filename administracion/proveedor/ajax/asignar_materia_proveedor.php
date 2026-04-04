<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$data        = json_decode(file_get_contents('php://input'), true);
$idproveedor = intval($data['idproveedor'] ?? 0);
$idmateria   = intval($data['idmateria_prima'] ?? 0);

if (!$idproveedor || !$idmateria) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
    exit;
}

try {
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar si ya existe la relación
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM materia_prima_has_proveedor
        WHERE materia_prima_idmateria_prima = ? AND proveedor_idproveedor = ?
    ");
    $check->execute([$idmateria, $idproveedor]);

    if ($check->fetchColumn() > 0) {
        echo json_encode(['ok' => false, 'msg' => 'Esta materia ya está asignada a ese proveedor']);
        exit;
    }

    // Obtener nombres para auditoría
    $stmtMP = $pdo->prepare("SELECT nombre FROM materia_prima WHERE idmateria_prima = ?");
    $stmtMP->execute([$idmateria]);
    $nombreMateria = $stmtMP->fetchColumn() ?: "ID {$idmateria}";

    $stmtProv = $pdo->prepare("SELECT nombre FROM proveedor WHERE idproveedor = ?");
    $stmtProv->execute([$idproveedor]);
    $nombreProveedor = $stmtProv->fetchColumn() ?: "ID {$idproveedor}";

    // Insertar relación
    $pdo->prepare("
        INSERT INTO materia_prima_has_proveedor
        (materia_prima_idmateria_prima, proveedor_idproveedor, created_at, updated_at)
        VALUES (?, ?, NOW(), NOW())
    ")->execute([$idmateria, $idproveedor]);

    audit($pdo, 'asignar', 'compras',
        "Asignó materia prima '{$nombreMateria}' al proveedor '{$nombreProveedor}'"
    );

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
