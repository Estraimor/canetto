<?php
define('APP_BOOT', true);
session_start();
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

$pdo        = Conexion::conectar();
$usuario_id = $_SESSION['usuario_id'] ?? null;

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

    // 1. Obtener stock anterior
    $stmtAntes = $pdo->prepare("SELECT stock_actual FROM materia_prima WHERE idmateria_prima = ?");
    $stmtAntes->execute([$idmateria]);
    $stockAnterior = $stmtAntes->fetchColumn();

    if ($stockAnterior === false) {
        throw new Exception('Materia prima no encontrada');
    }

    // 2. Actualizar stock
    $stmt = $pdo->prepare("
        UPDATE materia_prima
        SET stock_actual = stock_actual + ?, updated_at = NOW()
        WHERE idmateria_prima = ?
    ");
    $stmt->execute([$cantidad, $idmateria]);

    $nuevoStock = $stockAnterior + $cantidad;

    // 3. Actualizar costo en pivot (si viene)
    if ($costo !== null) {
        $stmtCosto = $pdo->prepare("
            UPDATE materia_prima_has_proveedor
            SET costo = ?, updated_at = NOW()
            WHERE materia_prima_idmateria_prima = ?
            AND proveedor_idproveedor = ?
        ");
        $stmtCosto->execute([$costo, $idmateria, $idproveedor]);
    }

    // 4. Historial
    try {

        $stmtHist = $pdo->prepare("
            INSERT INTO compra_materia_prima
            (
                proveedor_idproveedor,
                materia_prima_idmateria_prima,
                cantidad,
                costo,
                stock_anterior,
                stock_nuevo,
                observaciones,
                usuario_id,
                created_at
            )
            VALUES (?,?,?,?,?,?,?,?,NOW())
        ");

        $stmtHist->execute([
            $idproveedor,
            $idmateria,
            $cantidad,
            $costo,
            $stockAnterior,
            $nuevoStock,
            $observaciones ?: null,
            $usuario_id,
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