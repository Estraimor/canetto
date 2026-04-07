<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo          = Conexion::conectar();
    $id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $unidad       = filter_input(INPUT_POST, 'unidad', FILTER_VALIDATE_INT);
    $stock_actual = isset($_POST['stock_actual']) ? (float)$_POST['stock_actual'] : 0.0;
    $stock_minimo = isset($_POST['stock_minimo']) ? (float)$_POST['stock_minimo'] : 0.0;
    $activo       = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

    if ($nombre === '' || !$unidad) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    if ($id) {
        $pdo->prepare("
            UPDATE packaging SET
                nombre = ?, descripcion = ?, unidad_medida_idunidad_medida = ?,
                stock_actual = ?, stock_minimo = ?, activo = ?, updated_at = NOW()
            WHERE idpackaging = ?
        ")->execute([$nombre, $descripcion ?: null, $unidad, $stock_actual, $stock_minimo, $activo, $id]);
        audit($pdo, 'editar', 'packaging', "Editó packaging: '{$nombre}' (ID: {$id}) | Stock: {$stock_actual} (mín: {$stock_minimo})");
    } else {
        $pdo->prepare("
            INSERT INTO packaging (nombre, descripcion, unidad_medida_idunidad_medida, stock_actual, stock_minimo, activo)
            VALUES (?,?,?,?,?,?)
        ")->execute([$nombre, $descripcion ?: null, $unidad, $stock_actual, $stock_minimo, $activo]);
        $newId = $pdo->lastInsertId();
        audit($pdo, 'crear', 'packaging', "Creó packaging: '{$nombre}' (ID: {$newId}) | Stock: {$stock_actual} (mín: {$stock_minimo})");
    }

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
