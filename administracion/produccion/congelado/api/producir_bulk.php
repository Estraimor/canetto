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
        $recetaId   = (int)($item['receta']    ?? 0);
        $productoId = (int)($item['producto']  ?? 0);
        $cantidad   = (float)($item['cantidad'] ?? 0);

        if ($recetaId <= 0 || $productoId <= 0 || $cantidad <= 0) {
            $resultados[] = [
                'status'  => 'error',
                'nombre'  => "ID producto {$productoId}",
                'cantidad' => $cantidad,
                'mensaje' => 'Datos inválidos'
            ];
            continue;
        }

        try {
            // Nombre del producto
            $stmtNom = $pdo->prepare("SELECT nombre FROM productos WHERE idproductos = ? LIMIT 1");
            $stmtNom->execute([$productoId]);
            $nombreProducto = $stmtNom->fetchColumn() ?: "ID {$productoId}";

            // Receta base
            $stmtR = $pdo->prepare("SELECT nombre, cantidad_galletas FROM recetas WHERE idrecetas = ? LIMIT 1");
            $stmtR->execute([$recetaId]);
            $receta = $stmtR->fetch(PDO::FETCH_ASSOC);

            if (!$receta || (float)$receta['cantidad_galletas'] <= 0) {
                throw new Exception("Receta no encontrada o inválida");
            }

            $factor = $cantidad / (float)$receta['cantidad_galletas'];

            // Ingredientes
            $stmtI = $pdo->prepare("
                SELECT mp.idmateria_prima, mp.nombre, mp.stock_actual, ri.cantidad,
                       um.nombre AS unidad_nombre, um.abreviatura AS unidad_abreviatura
                FROM receta_ingredientes ri
                INNER JOIN materia_prima mp ON mp.idmateria_prima = ri.materia_prima_idmateria_prima
                LEFT JOIN unidad_medida um ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
                WHERE ri.recetas_idrecetas = ?
            ");
            $stmtI->execute([$recetaId]);
            $ingredientes = $stmtI->fetchAll(PDO::FETCH_ASSOC);

            if (empty($ingredientes)) {
                throw new Exception("La receta no tiene ingredientes");
            }

            // Calcular necesarios y verificar stock
            $faltantes = [];
            foreach ($ingredientes as &$i) {
                $unidadNombre = mb_strtolower(trim((string)($i['unidad_nombre'] ?? '')));
                $unidadAbr    = mb_strtolower(trim((string)($i['unidad_abreviatura'] ?? '')));
                $necesarioRaw = (float)$i['cantidad'] * $factor;
                $necesario    = ($unidadNombre === 'unidades' || $unidadAbr === 'u')
                    ? (float)round($necesarioRaw)
                    : round($necesarioRaw, 2);
                $i['necesario'] = $necesario;

                if ((float)$i['stock_actual'] < $necesario) {
                    $faltan      = round($necesario - (float)$i['stock_actual'], 2);
                    $faltantes[] = $i['nombre'] . " (faltan {$faltan})";
                }
            }
            unset($i);

            if (!empty($faltantes)) {
                $resultados[] = [
                    'status'  => 'error',
                    'nombre'  => $nombreProducto,
                    'cantidad' => $cantidad,
                    'mensaje' => 'Stock insuficiente: ' . implode(', ', $faltantes)
                ];
                continue;
            }

            // Ejecutar producción
            $pdo->beginTransaction();

            $pdo->prepare("INSERT INTO produccion (recetas_idrecetas, cantidad, fecha, usuario_idusuario, estado_produccion_idestado_produccion) VALUES (?, ?, NOW(), ?, 1)")
                ->execute([$recetaId, $cantidad, $usuario]);
            $produccionId = $pdo->lastInsertId();

            $ingredientesDesc = [];
            foreach ($ingredientes as $i) {
                $pdo->prepare("UPDATE materia_prima SET stock_actual = stock_actual - ? WHERE idmateria_prima = ?")
                    ->execute([$i['necesario'], $i['idmateria_prima']]);
                $ingredientesDesc[] = $i['nombre'] . ': -' . $i['necesario'];
            }

            // Stock congelado
            $stmtSt = $pdo->prepare("SELECT idstock_productos, stock_actual FROM stock_productos WHERE productos_idproductos = ? AND tipo_stock = 'CONGELADO' LIMIT 1");
            $stmtSt->execute([$productoId]);
            $stock = $stmtSt->fetch(PDO::FETCH_ASSOC);

            if (!$stock) {
                $pdo->prepare("INSERT INTO stock_productos (productos_idproductos, tipo_stock, stock_actual, stock_minimo, activo) VALUES (?, 'CONGELADO', 0, 0, 1)")
                    ->execute([$productoId]);
                $stmtSt->execute([$productoId]);
                $stock = $stmtSt->fetch(PDO::FETCH_ASSOC);
            }

            $stockAntes = (float)$stock['stock_actual'];
            $nuevoStock = $stockAntes + $cantidad;

            $pdo->prepare("UPDATE stock_productos SET stock_actual = ? WHERE idstock_productos = ?")
                ->execute([$nuevoStock, $stock['idstock_productos']]);

            $pdo->prepare("
                INSERT INTO stock_productos_movimientos
                (productos_idproductos, produccion_idproduccion, tipo_stock, tipo_movimiento, cantidad, stock_antes, stock_despues, motivo, fecha, usuario_idusuario)
                VALUES (?, ?, 'CONGELADO', 'ENTRADA', ?, ?, ?, 'Producción en lote', NOW(), ?)
            ")->execute([$productoId, $produccionId, $cantidad, $stockAntes, $nuevoStock, $usuario]);

            $pdo->commit();

            audit($pdo, 'producir', 'produccion',
                "Lote congelado: {$nombreProducto} x {$cantidad} u. | Stock: {$stockAntes} → {$nuevoStock} | " .
                implode(' | ', $ingredientesDesc)
            );

            $resultados[] = [
                'status'  => 'ok',
                'nombre'  => $nombreProducto,
                'cantidad' => $cantidad,
                'mensaje' => "Stock congelado actualizado: {$stockAntes} → {$nuevoStock}"
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
