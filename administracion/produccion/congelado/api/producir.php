<?php
declare(strict_types=1);
define('APP_BOOT', true);

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../../config/conexion.php';
require_once __DIR__ . '/../../../../config/audit.php';

try {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = json_decode(file_get_contents("php://input"), true);

    $recetaId   = (int)($input['receta']    ?? 0);
    $productoId = (int)($input['producto']  ?? 0);
    $cantidad   = (float)($input['cantidad'] ?? 0);

    if ($recetaId <= 0 || $cantidad <= 0) throw new Exception("Datos inválidos");

    // Buscar producto si no viene
    if ($productoId <= 0) {
        $stmt = $pdo->prepare("SELECT idproductos FROM productos WHERE recetas_idrecetas = ? AND tipo = 'producto' LIMIT 1");
        $stmt->execute([$recetaId]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$producto) throw new Exception("No existe un producto asociado a esta receta");
        $productoId = (int)$producto['idproductos'];
    }

    // Receta base
    $stmt = $pdo->prepare("SELECT nombre, cantidad_galletas FROM recetas WHERE idrecetas = ? LIMIT 1");
    $stmt->execute([$recetaId]);
    $receta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$receta) throw new Exception("Receta no encontrada");
    if ((float)$receta['cantidad_galletas'] <= 0) throw new Exception("La receta tiene una cantidad base inválida");

    $nombreReceta = $receta['nombre'];
    $factor = $cantidad / (float)$receta['cantidad_galletas'];

    // Ingredientes + unidad medida
    $stmt = $pdo->prepare("
        SELECT mp.idmateria_prima, mp.nombre, mp.stock_actual, ri.cantidad,
               um.nombre AS unidad_nombre, um.abreviatura AS unidad_abreviatura
        FROM receta_ingredientes ri
        INNER JOIN materia_prima mp ON mp.idmateria_prima = ri.materia_prima_idmateria_prima
        LEFT JOIN unidad_medida um ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
        WHERE ri.recetas_idrecetas = ?
    ");
    $stmt->execute([$recetaId]);
    $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$ingredientes) throw new Exception("La receta no tiene ingredientes");

    function normalizarCantidadSegunUnidad(array $ingrediente, float $cantidadCalculada): float
    {
        $unidadNombre = mb_strtolower(trim((string)($ingrediente['unidad_nombre'] ?? '')));
        $unidadAbr    = mb_strtolower(trim((string)($ingrediente['unidad_abreviatura'] ?? '')));
        if ($unidadNombre === 'unidades' || $unidadAbr === 'u') return (float)round($cantidadCalculada);
        return round($cantidadCalculada, 2);
    }

    // Validar stock
    $faltantes = [];
    foreach ($ingredientes as &$i) {
        $necesario      = normalizarCantidadSegunUnidad($i, (float)$i['cantidad'] * $factor);
        $i['necesario'] = $necesario;
        if ((float)$i['stock_actual'] < $necesario) {
            $faltan      = $necesario - (float)$i['stock_actual'];
            $faltantes[] = $i['nombre'] . " (faltan {$faltan})";
        }
    }
    unset($i);

    if (!empty($faltantes)) {
        echo json_encode(["status" => "error", "mensaje" => "Stock insuficiente", "detalle" => $faltantes]);
        exit;
    }

    $pdo->beginTransaction();

    $usuario = $_SESSION['usuario_id'] ?? 1;

    // Insertar producción
    $pdo->prepare("INSERT INTO produccion (recetas_idrecetas, cantidad, fecha, usuario_idusuario, estado_produccion_idestado_produccion) VALUES (?, ?, NOW(), ?, 1)")
        ->execute([$recetaId, $cantidad, $usuario]);
    $produccionId = $pdo->lastInsertId();

    // Descontar materia prima
    $ingredientesDesc = [];
    foreach ($ingredientes as $i) {
        $pdo->prepare("UPDATE materia_prima SET stock_actual = stock_actual - ? WHERE idmateria_prima = ?")
            ->execute([$i['necesario'], $i['idmateria_prima']]);
        $ingredientesDesc[] = $i['nombre'] . ': -' . $i['necesario'] . ' ' . ($i['unidad_abreviatura'] ?? '');
    }

    // Stock congelado
    $stmt = $pdo->prepare("SELECT idstock_productos, stock_actual FROM stock_productos WHERE productos_idproductos = ? AND tipo_stock = 'CONGELADO' LIMIT 1");
    $stmt->execute([$productoId]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stock) {
        $pdo->prepare("INSERT INTO stock_productos (productos_idproductos, tipo_stock, stock_actual, stock_minimo, activo) VALUES (?, 'CONGELADO', 0, 0, 1)")
            ->execute([$productoId]);
        $stmt->execute([$productoId]);
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $stockAntes = (float)$stock['stock_actual'];
    $nuevoStock = $stockAntes + $cantidad;

    $pdo->prepare("UPDATE stock_productos SET stock_actual = ? WHERE idstock_productos = ?")
        ->execute([$nuevoStock, $stock['idstock_productos']]);

    // Movimiento de stock
    $pdo->prepare("
        INSERT INTO stock_productos_movimientos
        (productos_idproductos, produccion_idproduccion, tipo_stock, tipo_movimiento, cantidad, stock_antes, stock_despues, motivo, fecha, usuario_idusuario)
        VALUES (?, ?, 'CONGELADO', 'ENTRADA', ?, ?, ?, 'Producción', NOW(), ?)
    ")->execute([$productoId, $produccionId, $cantidad, $stockAntes, $nuevoStock, $usuario]);

    $pdo->commit();

    audit($pdo, 'producir', 'produccion',
        "Producción masa congelada: {$nombreReceta} x {$cantidad} u." .
        " | Stock congelado: {$stockAntes} → {$nuevoStock}" .
        " | Ingredientes consumidos: " . implode(' | ', $ingredientesDesc)
    );

    echo json_encode(["status" => "ok", "mensaje" => "Producción realizada correctamente"]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "mensaje" => $e->getMessage()]);
}
