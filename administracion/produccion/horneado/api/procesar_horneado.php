<?php
define('APP_BOOT', true);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../config/conexion.php';
require_once __DIR__ . '/../../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $pdo = Conexion::conectar();
    $pdo->beginTransaction();

    $input       = json_decode(file_get_contents("php://input"), true);
    $producto_id = (int)$input['producto_id'];
    $cantidad    = (float)$input['cantidad'];

    if ($producto_id <= 0) throw new Exception("Producto inválido");
    if ($cantidad <= 0)    throw new Exception("Cantidad inválida");

    // Obtener nombre del producto para auditoría
    $stmtNom = $pdo->prepare("SELECT nombre FROM productos WHERE idproductos = ?");
    $stmtNom->execute([$producto_id]);
    $nombreProducto = $stmtNom->fetchColumn() ?: "ID {$producto_id}";

    // Validar stock congelado
    $stmt = $pdo->prepare("
        SELECT stock_actual FROM stock_productos
        WHERE productos_idproductos = ? AND tipo_stock = 'CONGELADO' FOR UPDATE
    ");
    $stmt->execute([$producto_id]);
    $stockCongelado = $stmt->fetchColumn();

    if ($stockCongelado === false) throw new Exception("No existe stock congelado para este producto");
    if ($stockCongelado < $cantidad) throw new Exception("No hay suficiente stock congelado");

    // Asegurar stock HECHO
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_productos WHERE productos_idproductos = ? AND tipo_stock = 'HECHO'");
    $stmt->execute([$producto_id]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO stock_productos (productos_idproductos, tipo_stock, stock_actual, stock_minimo, activo) VALUES (?, 'HECHO', 0, 5, 1)")
            ->execute([$producto_id]);
    }

    // Obtener receta del producto
    $stmt = $pdo->prepare("SELECT recetas_idrecetas FROM productos WHERE idproductos = ?");
    $stmt->execute([$producto_id]);
    $receta_id = $stmt->fetchColumn();
    if (!$receta_id) throw new Exception("El producto no tiene receta asociada");

    // Restar congelado
    $pdo->prepare("UPDATE stock_productos SET stock_actual = stock_actual - ? WHERE productos_idproductos = ? AND tipo_stock = 'CONGELADO'")
        ->execute([$cantidad, $producto_id]);

    // Sumar hecho
    $pdo->prepare("UPDATE stock_productos SET stock_actual = stock_actual + ? WHERE productos_idproductos = ? AND tipo_stock = 'HECHO'")
        ->execute([$cantidad, $producto_id]);

    // Registrar producción
    $usuario = $_SESSION['usuario_id'] ?? 1;
    $pdo->prepare("INSERT INTO produccion (recetas_idrecetas, cantidad, fecha, usuario_idusuario, estado_produccion_idestado_produccion) VALUES (?, ?, NOW(), ?, 1)")
        ->execute([$receta_id, $cantidad, $usuario]);

    $pdo->commit();

    $stockRestante = $stockCongelado - $cantidad;
    audit($pdo, 'hornear', 'produccion',
        "Horneado: {$nombreProducto} x {$cantidad} u." .
        " | Congelado consumido: -{$cantidad} (resta: {$stockRestante})" .
        " | Hecho producido: +{$cantidad}"
    );

    echo json_encode(["status" => "ok", "mensaje" => "Horneado realizado correctamente"]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "mensaje" => $e->getMessage()]);
}
