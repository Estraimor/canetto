<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

// 🔥 conexión
$pdo = Conexion::conectar();

$data          = json_decode(file_get_contents('php://input'), true);
$idproveedor   = intval($data['idproveedor'] ?? 0);
$idmateria     = intval($data['idmateria_prima'] ?? 0);
$cantidad      = floatval($data['cantidad'] ?? 0);
$costo         = isset($data['costo']) && $data['costo'] !== null && $data['costo'] !== '' ? floatval($data['costo']) : null;
$observaciones = trim($data['observaciones'] ?? '');

if (!$idproveedor || !$idmateria || $cantidad <= 0) {
    echo json_encode([
        'ok'  => false,
        'msg' => 'Datos incompletos o inválidos'
    ]);
    exit;
}

try {

    $pdo->beginTransaction();

    // 1. Actualizar stock
    $stmt = $pdo->prepare("
        UPDATE materia_prima 
        SET stock_actual = stock_actual + ?, updated_at = NOW() 
        WHERE idmateria_prima = ?
    ");
    $stmt->execute([$cantidad, $idmateria]);

    // 🔥 validar que realmente exista la materia
    if ($stmt->rowCount() === 0) {
        throw new Exception('Materia prima no encontrada');
    }

    // 2. Obtener stock nuevo
    $stockNuevo = $pdo->prepare("
        SELECT stock_actual 
        FROM materia_prima 
        WHERE idmateria_prima = ?
    ");
    $stockNuevo->execute([$idmateria]);
    $nuevoStock = $stockNuevo->fetchColumn();

    // // 3. Actualizar costo (si viene)
    // if ($costo !== null) {

    //     $stmtCosto = $pdo->prepare("
    //         UPDATE materia_prima_has_proveedor 
    //         SET costo = ?, updated_at = NOW()
    //         WHERE materia_prima_idmateria_prima = ? 
    //         AND proveedor_idproveedor = ?
    //     ");
    //     $stmtCosto->execute([$costo, $idmateria, $idproveedor]);
    // }

    // 4. Historial (opcional)
    try {

        $stmtHist = $pdo->prepare("
            INSERT INTO compra_materia_prima
            (
                proveedor_idproveedor,
                materia_prima_idmateria_prima,
                cantidad,
                costo,
                stock_nuevo,
                observaciones,
                created_at
            )
            VALUES (?,?,?,?,?,?,NOW())
        ");

        $stmtHist->execute([
            $idproveedor,
            $idmateria,
            $cantidad,
            $costo,
            $nuevoStock,
            $observaciones ?: null
        ]);

    } catch (PDOException $ignored) {
        // tabla puede no existir
    }

    $pdo->commit();

    echo json_encode([
        'ok'          => true,
        'stock_nuevo' => $nuevoStock
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'ok'  => false,
        'msg' => $e->getMessage()
    ]);
}