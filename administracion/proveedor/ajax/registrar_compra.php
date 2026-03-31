<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

$data          = json_decode(file_get_contents('php://input'), true);
$idproveedor   = intval($data['idproveedor'] ?? 0);
$idmateria     = intval($data['idmateria_prima'] ?? 0);
$cantidad      = floatval($data['cantidad'] ?? 0);
$costo         = isset($data['costo']) && $data['costo'] !== null && $data['costo'] !== '' ? floatval($data['costo']) : null;
$observaciones = trim($data['observaciones'] ?? '');

if (!$idproveedor || !$idmateria || $cantidad <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos o inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Actualizar stock
    $pdo->prepare("UPDATE materia_prima SET stock_actual = stock_actual + ?, updated_at = NOW() WHERE idmateria_prima = ?")
        ->execute([$cantidad, $idmateria]);

    // 2. Obtener stock nuevo para log
    $stockNuevo = $pdo->prepare("SELECT stock_actual FROM materia_prima WHERE idmateria_prima = ?");
    $stockNuevo->execute([$idmateria]);
    $nuevoStock = $stockNuevo->fetchColumn();

    // 3. Actualizar costo en tabla relación si se indicó
    if ($costo !== null) {
        $pdo->prepare("UPDATE materia_prima_has_proveedor SET costo=?, updated_at=NOW()
            WHERE materia_prima_idmateria_prima=? AND proveedor_idproveedor=?")
            ->execute([$costo, $idmateria, $idproveedor]);
    }

    // 4. Registrar en historial (tabla compra_materia_prima si existe, si no, lo omitimos)
    // Intentamos insertar en una tabla de historial; si no existe, ignoramos el error
    try {
        $pdo->prepare("INSERT INTO compra_materia_prima
            (proveedor_idproveedor, materia_prima_idmateria_prima, cantidad, costo, stock_nuevo, observaciones, created_at)
            VALUES (?,?,?,?,?,?,NOW())")
            ->execute([$idproveedor, $idmateria, $cantidad, $costo, $nuevoStock, $observaciones ?: null]);
    } catch (Exception $ignored) {
        // La tabla de historial puede no existir aún; el stock ya fue actualizado
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'stock_nuevo' => $nuevoStock]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
