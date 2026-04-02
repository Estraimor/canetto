<?php
define('APP_BOOT', true);
session_start();
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

$pdo        = Conexion::conectar();
$usuario_id = $_SESSION['usuario_id'] ?? null;

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

try {

    $pdo->beginTransaction();

    // Obtener compra cancelada
    $stmt = $pdo->prepare("
        SELECT * FROM compra_materia_prima
        WHERE id = ? AND estado = 'cancelada'
    ");
    $stmt->execute([$id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compra) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'La compra no existe o ya está activa']);
        exit;
    }

    // Sumar stock nuevamente
    $stmtStock = $pdo->prepare("
        UPDATE materia_prima
        SET stock_actual = stock_actual + ?, updated_at = NOW()
        WHERE idmateria_prima = ?
    ");
    $stmtStock->execute([
        $compra['cantidad'],
        $compra['materia_prima_idmateria_prima']
    ]);

    // Obtener nuevo stock para actualizar stock_nuevo
    $stmtSN = $pdo->prepare("SELECT stock_actual FROM materia_prima WHERE idmateria_prima = ?");
    $stmtSN->execute([$compra['materia_prima_idmateria_prima']]);
    $stockNuevo = $stmtSN->fetchColumn();

    // Reactivar compra
    $stmtReact = $pdo->prepare("
        UPDATE compra_materia_prima
        SET estado          = 'activa',
            stock_nuevo     = ?,
            cancelado_at    = NULL,
            cancelado_motivo= NULL,
            cancelado_por   = NULL
        WHERE id = ?
    ");
    $stmtReact->execute([$stockNuevo, $id]);

    $pdo->commit();

    echo json_encode(['ok' => true]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
