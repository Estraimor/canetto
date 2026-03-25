<?php
define('APP_BOOT', true);
header('Content-Type: application/json');

require_once __DIR__ . '/../../../../config/conexion.php';

try {

    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    $input = json_decode(file_get_contents("php://input"), true);

    $producto_id = (int)$input['producto_id'];
    $cantidad = (float)$input['cantidad'];

    if ($producto_id <= 0) {
        throw new Exception("Producto inválido");
    }

    if ($cantidad <= 0) {
        throw new Exception("Cantidad inválida");
    }

    /* =========================
    VALIDAR STOCK CONGELADO
    ========================= */
    $stmt = $pdo->prepare("
        SELECT stock_actual 
        FROM stock_productos
        WHERE productos_idproductos = ? 
        AND tipo_stock = 'CONGELADO'
        FOR UPDATE
    ");
    $stmt->execute([$producto_id]);
    $stock = $stmt->fetchColumn();

    if ($stock === false) {
        throw new Exception("No existe stock congelado para este producto");
    }

    if ($stock < $cantidad) {
        throw new Exception("No hay suficiente stock congelado");
    }

    /* =========================
    ASEGURAR STOCK HECHO
    ========================= */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM stock_productos
        WHERE productos_idproductos = ?
        AND tipo_stock = 'HECHO'
    ");
    $stmt->execute([$producto_id]);

    if ($stmt->fetchColumn() == 0) {

        $stmt = $pdo->prepare("
            INSERT INTO stock_productos 
            (productos_idproductos, tipo_stock, stock_actual, stock_minimo, activo)
            VALUES (?, 'HECHO', 0, 5, 1)
        ");
        $stmt->execute([$producto_id]);
    }

    /* =========================
    OBTENER RECETA DEL PRODUCTO
    ========================= */
    $stmt = $pdo->prepare("
        SELECT recetas_idrecetas 
        FROM productos 
        WHERE idproductos = ?
    ");
    $stmt->execute([$producto_id]);

    $receta_id = $stmt->fetchColumn();

    if (!$receta_id) {
        throw new Exception("El producto no tiene receta asociada");
    }

    /* =========================
    RESTAR CONGELADO
    ========================= */
    $stmt = $pdo->prepare("
        UPDATE stock_productos
        SET stock_actual = stock_actual - ?
        WHERE productos_idproductos = ?
        AND tipo_stock = 'CONGELADO'
    ");
    $stmt->execute([$cantidad, $producto_id]);

    /* =========================
    SUMAR HECHO
    ========================= */
    $stmt = $pdo->prepare("
        UPDATE stock_productos
        SET stock_actual = stock_actual + ?
        WHERE productos_idproductos = ?
        AND tipo_stock = 'HECHO'
    ");
    $stmt->execute([$cantidad, $producto_id]);

    /* =========================
    INSERT PRODUCCION
    ========================= */
    $stmt = $pdo->prepare("
        INSERT INTO produccion 
        (recetas_idrecetas, cantidad, fecha, usuario_idusuario, estado_produccion_idestado_produccion)
        VALUES (?, ?, NOW(), 1, 1)
    ");
    $stmt->execute([$receta_id, $cantidad]);

    $pdo->commit();

    echo json_encode([
        "status" => "ok",
        "mensaje" => "Horneado realizado correctamente"
    ]);

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "status" => "error",
        "mensaje" => $e->getMessage()
    ]);
}