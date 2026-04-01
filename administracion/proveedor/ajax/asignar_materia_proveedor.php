<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

$data        = json_decode(file_get_contents('php://input'), true);
$idproveedor = intval($data['idproveedor'] ?? 0);
$idmateria   = intval($data['idmateria_prima'] ?? 0);

if (!$idproveedor || !$idmateria) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
    exit;
}

try {
    // 🔥 IMPORTANTE: crear conexión
    $pdo = Conexion::conectar();

    // 🔒 Opcional pero PRO: manejar errores como excepciones
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Verificar si ya existe la relación
    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM materia_prima_has_proveedor
        WHERE materia_prima_idmateria_prima = ?
          AND proveedor_idproveedor = ?
    ");
    $check->execute([$idmateria, $idproveedor]);

    if ($check->fetchColumn() > 0) {
        echo json_encode([
            'ok' => false,
            'msg' => 'Esta materia ya está asignada a ese proveedor'
        ]);
        exit;
    }

    // 2. Insertar relación
    $stmt = $pdo->prepare("
    INSERT INTO materia_prima_has_proveedor
    (materia_prima_idmateria_prima, proveedor_idproveedor, created_at, updated_at)
    VALUES (?, ?, NOW(), NOW())
");
$stmt->execute([$idmateria, $idproveedor]);
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}