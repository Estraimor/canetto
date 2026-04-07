<?php
declare(strict_types=1);
define('APP_BOOT', true);

ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../../config/conexion.php';
require_once __DIR__ . '/../../../../config/audit.php';

try {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = json_decode(file_get_contents('php://input'), true);
    $items = $input['items'] ?? [];

    if (empty($items) || !is_array($items)) {
        echo json_encode(['status' => 'error', 'mensaje' => 'No se recibieron ítems']);
        exit;
    }

    $usuario    = $_SESSION['usuario_id'] ?? 1;
    $resultados = [];

    foreach ($items as $item) {
        $productoId = (int)($item['producto_id'] ?? 0);
        $cantidad   = (float)($item['cantidad']   ?? 0);

        if ($productoId <= 0 || $cantidad <= 0) {
            $resultados[] = [
                'status'  => 'error',
                'nombre'  => "ID {$productoId}",
                'cantidad' => $cantidad,
                'mensaje' => 'Datos inválidos'
            ];
            continue;
        }

        try {
            $pdo->beginTransaction();

            // Nombre del producto
            $stmtNom = $pdo->prepare("SELECT nombre FROM productos WHERE idproductos = ?");
            $stmtNom->execute([$productoId]);
            $nombreProducto = $stmtNom->fetchColumn() ?: "ID {$productoId}";

            // Validar stock congelado
            $stmtSC = $pdo->prepare("SELECT stock_actual FROM stock_productos WHERE productos_idproductos = ? AND tipo_stock = 'CONGELADO' FOR UPDATE");
            $stmtSC->execute([$productoId]);
            $stockCongelado = $stmtSC->fetchColumn();

            if ($stockCongelado === false) {
                throw new Exception("No existe stock congelado para este producto");
            }
            if ($stockCongelado < $cantidad) {
                throw new Exception("Stock insuficiente (disponible: {$stockCongelado})");
            }

            // Asegurar stock HECHO
            $stmtH = $pdo->prepare("SELECT COUNT(*) FROM stock_productos WHERE productos_idproductos = ? AND tipo_stock = 'HECHO'");
            $stmtH->execute([$productoId]);
            if ($stmtH->fetchColumn() == 0) {
                $pdo->prepare("INSERT INTO stock_productos (productos_idproductos, tipo_stock, stock_actual, stock_minimo, activo) VALUES (?, 'HECHO', 0, 5, 1)")
                    ->execute([$productoId]);
            }

            // Obtener receta del producto
            $stmtRec = $pdo->prepare("SELECT recetas_idrecetas FROM productos WHERE idproductos = ?");
            $stmtRec->execute([$productoId]);
            $recetaId = $stmtRec->fetchColumn();
            if (!$recetaId) throw new Exception("El producto no tiene receta asociada");

            // Restar congelado, sumar hecho
            $pdo->prepare("UPDATE stock_productos SET stock_actual = stock_actual - ? WHERE productos_idproductos = ? AND tipo_stock = 'CONGELADO'")
                ->execute([$cantidad, $productoId]);
            $pdo->prepare("UPDATE stock_productos SET stock_actual = stock_actual + ? WHERE productos_idproductos = ? AND tipo_stock = 'HECHO'")
                ->execute([$cantidad, $productoId]);

            // Registrar producción
            $pdo->prepare("INSERT INTO produccion (recetas_idrecetas, cantidad, fecha, usuario_idusuario, estado_produccion_idestado_produccion) VALUES (?, ?, NOW(), ?, 1)")
                ->execute([$recetaId, $cantidad, $usuario]);

            $pdo->commit();

            $stockRestante = $stockCongelado - $cantidad;
            audit($pdo, 'hornear', 'produccion',
                "Lote horneado: {$nombreProducto} x {$cantidad} u." .
                " | Congelado consumido: -{$cantidad} (resta: {$stockRestante})" .
                " | Hecho producido: +{$cantidad}"
            );

            $resultados[] = [
                'status'  => 'ok',
                'nombre'  => $nombreProducto,
                'cantidad' => $cantidad,
                'mensaje' => "Horneado correctamente"
            ];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $resultados[] = [
                'status'  => 'error',
                'nombre'  => $nombreProducto ?? "ID {$productoId}",
                'cantidad' => $cantidad,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    $errores = array_filter($resultados, fn($r) => $r['status'] !== 'ok');
    echo json_encode([
        'status'     => count($errores) === 0 ? 'ok' : (count($errores) === count($resultados) ? 'error' : 'partial'),
        'resultados' => $resultados
    ]);

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'mensaje' => $e->getMessage(), 'resultados' => []]);
}
