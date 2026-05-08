<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo  = Conexion::conectar();
    $body = json_decode(file_get_contents('php://input'), true);

    $tipo      = $body['tipo']      ?? '';
    $refId     = (int)($body['referencia_id'] ?? 0);
    $cantidad  = (float)($body['cantidad'] ?? 0);
    $motivo    = $body['motivo']    ?? 'otro';
    $desc      = trim($body['descripcion'] ?? '');
    $unidad    = trim($body['unidad'] ?? '');
    $costo     = (float)($body['costo_estimado'] ?? 0);
    $userId    = $_SESSION['usuario_id'] ?? null;

    $tiposValidos  = ['producto', 'materia_prima', 'topping'];
    $motivosValidos = ['vencimiento', 'produccion', 'accidente', 'control_calidad', 'otro'];

    if (!in_array($tipo, $tiposValidos))   throw new InvalidArgumentException('Tipo inválido');
    if (!in_array($motivo, $motivosValidos)) throw new InvalidArgumentException('Motivo inválido');
    if ($refId <= 0)   throw new InvalidArgumentException('Referencia inválida');
    if ($cantidad <= 0) throw new InvalidArgumentException('La cantidad debe ser mayor a 0');

    $pdo->beginTransaction();

    // Insertar merma
    $stmt = $pdo->prepare("
        INSERT INTO mermas (tipo, referencia_id, cantidad, unidad, motivo, descripcion, costo_estimado, usuario_id, fecha)
        VALUES (:tipo, :ref, :qty, :unidad, :motivo, :desc, :costo, :uid, NOW())
    ");
    $stmt->execute([
        ':tipo'   => $tipo,
        ':ref'    => $refId,
        ':qty'    => $cantidad,
        ':unidad' => $unidad,
        ':motivo' => $motivo,
        ':desc'   => $desc,
        ':costo'  => $costo,
        ':uid'    => $userId,
    ]);
    $mermaId = $pdo->lastInsertId();

    // Descontar del stock correspondiente
    switch ($tipo) {
        case 'producto':
            // Descuenta de stock_productos (tipo HECHO y CONGELADO proporcional)
            $stocks = $pdo->prepare("SELECT id, tipo_stock, stock_actual FROM stock_productos WHERE productos_idproductos = ? AND stock_actual > 0 ORDER BY FIELD(tipo_stock,'HECHO','CONGELADO')");
            $stocks->execute([$refId]);
            $filas = $stocks->fetchAll();
            $restante = $cantidad;
            foreach ($filas as $fila) {
                if ($restante <= 0) break;
                $desc_este = min($restante, $fila['stock_actual']);
                $nuevo     = max(0, $fila['stock_actual'] - $desc_este);
                $pdo->prepare("UPDATE stock_productos SET stock_actual = ? WHERE id = ?")->execute([$nuevo, $fila['id']]);
                $restante -= $desc_este;
            }
            break;

        case 'materia_prima':
            // Descuenta de materia_prima.stock_actual
            $mp = $pdo->prepare("SELECT stock_actual FROM materia_prima WHERE idmateria_prima = ?");
            $mp->execute([$refId]);
            $row = $mp->fetch();
            if ($row) {
                $nuevo = max(0, $row['stock_actual'] - $cantidad);
                $pdo->prepare("UPDATE materia_prima SET stock_actual = ? WHERE idmateria_prima = ?")->execute([$nuevo, $refId]);
            }
            break;

        case 'topping':
            // Descuenta de toppings_stock
            $ts = $pdo->prepare("SELECT stock_actual FROM toppings_stock WHERE toppings_idtoppings = ?");
            $ts->execute([$refId]);
            $row = $ts->fetch();
            if ($row) {
                $nuevo = max(0, $row['stock_actual'] - $cantidad);
                $pdo->prepare("UPDATE toppings_stock SET stock_actual = ? WHERE toppings_idtoppings = ?")->execute([$nuevo, $refId]);
            }
            break;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id' => $mermaId]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
