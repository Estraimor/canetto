<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }
header('Content-Type: application/json');

try {
    $pdo  = Conexion::conectar();
    $body = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($body['id'] ?? 0);
    if ($id <= 0) throw new InvalidArgumentException('ID inválido');

    // Recuperar para revertir stock
    $merma = $pdo->prepare("SELECT * FROM mermas WHERE id = ?");
    $merma->execute([$id]);
    $m = $merma->fetch();
    if (!$m) throw new RuntimeException('Merma no encontrada');

    $pdo->beginTransaction();

    // Revertir stock
    switch ($m['tipo']) {
        case 'producto':
            $stocks = $pdo->prepare("SELECT id, tipo_stock, stock_actual FROM stock_productos WHERE productos_idproductos = ? ORDER BY FIELD(tipo_stock,'HECHO','CONGELADO')");
            $stocks->execute([$m['referencia_id']]);
            $filas    = $stocks->fetchAll();
            $restante = (float)$m['cantidad'];
            foreach ($filas as $fila) {
                if ($restante <= 0) break;
                $devolver = min($restante, (float)$m['cantidad']);
                $pdo->prepare("UPDATE stock_productos SET stock_actual = stock_actual + ? WHERE id = ?")->execute([$devolver, $fila['id']]);
                $restante -= $devolver;
            }
            break;
        case 'materia_prima':
            $pdo->prepare("UPDATE materia_prima SET stock_actual = stock_actual + ? WHERE idmateria_prima = ?")->execute([$m['cantidad'], $m['referencia_id']]);
            break;
        case 'topping':
            $pdo->prepare("UPDATE toppings_stock SET stock_actual = stock_actual + ? WHERE toppings_idtoppings = ?")->execute([$m['cantidad'], $m['referencia_id']]);
            break;
    }

    $pdo->prepare("DELETE FROM mermas WHERE id = ?")->execute([$id]);
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
