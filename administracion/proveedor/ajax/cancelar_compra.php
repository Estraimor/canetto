<?php
define('APP_BOOT', true);
session_start();
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';

header('Content-Type: application/json');

$pdo        = Conexion::conectar();
$usuario_id = $_SESSION['usuario_id'] ?? null;
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data   = json_decode(file_get_contents('php://input'), true);
$id     = intval($data['id'] ?? 0);
$motivo = trim($data['motivo'] ?? '');

if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Obtener compra activa
    $stmt = $pdo->prepare("SELECT * FROM compra_materia_prima WHERE id = ? AND estado = 'activa'");
    $stmt->execute([$id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compra) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'msg' => 'La compra no existe o ya fue cancelada']);
        exit;
    }

    // Obtener nombre de materia prima para auditoría
    $stmtMP = $pdo->prepare("SELECT nombre FROM materia_prima WHERE idmateria_prima = ?");
    $stmtMP->execute([$compra['materia_prima_idmateria_prima']]);
    $nombreMateria = $stmtMP->fetchColumn() ?: "ID {$compra['materia_prima_idmateria_prima']}";

    // Restar stock
    $pdo->prepare("
        UPDATE materia_prima
        SET stock_actual = stock_actual - ?, updated_at = NOW()
        WHERE idmateria_prima = ?
    ")->execute([$compra['cantidad'], $compra['materia_prima_idmateria_prima']]);

    // Marcar como cancelada
    $pdo->prepare("
        UPDATE compra_materia_prima
        SET estado = 'cancelada', cancelado_at = NOW(),
            cancelado_motivo = ?, cancelado_por = ?
        WHERE id = ?
    ")->execute([$motivo !== '' ? $motivo : null, $usuario_id, $id]);

    $pdo->commit();

    audit($pdo, 'cancelar', 'compras',
        "Canceló compra #{$id}: {$nombreMateria} x {$compra['cantidad']} u." .
        ($motivo ? " | Motivo: {$motivo}" : ' | Sin motivo especificado') .
        " | Stock descontado: -{$compra['cantidad']}"
    );

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
