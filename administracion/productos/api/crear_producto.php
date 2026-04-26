<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    $idProducto   = $_POST['idproducto'] ?? null;
    $nombre       = $_POST['nombre'] ?? null;
    $precio       = $_POST['precio'] ?? 0;
    $tipo         = $_POST['tipo'] ?? 'producto';
    $receta       = $_POST['recetas_idrecetas'] ?? null;
    $minCongelado = $_POST['min_congelado'] ?? 0;
    $minHecho     = $_POST['min_hecho'] ?? 0;
    $imagenActual = $_POST['imagen_actual'] ?? null; // imagen existente

    if (!$nombre) throw new Exception("Nombre obligatorio");

    // ── Procesar imagen ────────────────────────────────────────────────────
    $imgDir    = __DIR__ . '/../../../img/productos';
    if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);
    $imagenFinal = $imagenActual ?: null;

    if (!empty($_FILES['imagen']['name'])) {
        $file    = $_FILES['imagen'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) throw new Exception("Tipo de imagen no permitido");
        if ($file['size'] > 2 * 1024 * 1024) throw new Exception("La imagen supera 2 MB");
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception("Error al subir imagen");

        $newName = uniqid('prod_') . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $imgDir . '/' . $newName)) {
            throw new Exception("No se pudo guardar la imagen");
        }
        // Borrar imagen anterior si existe
        if ($imagenActual && file_exists($imgDir . '/' . $imagenActual)) {
            @unlink($imgDir . '/' . $imagenActual);
        }
        $imagenFinal = $newName;
    } elseif ($imagenActual === '') {
        // Quitar imagen explícitamente
        if ($imagenActual && file_exists($imgDir . '/' . $imagenActual)) {
            @unlink($imgDir . '/' . $imagenActual);
        }
        $imagenFinal = null;
    }

    $pdo->beginTransaction();

    if (!$idProducto) {
        // Crear producto nuevo
        $stmt = $pdo->prepare("
            INSERT INTO productos (nombre, precio, recetas_idrecetas, tipo, activo, imagen)
            VALUES (?,?,?,?,1,?)
        ");
        $stmt->execute([$nombre, $precio, $tipo === 'producto' ? $receta : null, $tipo, $imagenFinal]);
        $idProducto = $pdo->lastInsertId();

        if ($tipo === 'producto') {
            $pdo->prepare("
                INSERT INTO stock_productos
                (productos_idproductos, tipo_stock, stock_actual, stock_minimo)
                VALUES (?, 'CONGELADO', 0, ?), (?, 'HECHO', 0, ?)
            ")->execute([$idProducto, $minCongelado, $idProducto, $minHecho]);
        }

        $accion = 'crear';
        $desc   = "Creó producto: {$nombre}" .
                  " | Tipo: {$tipo}" .
                  " | Precio: $" . number_format((float)$precio, 2) .
                  ($tipo === 'producto' ? " | Stock mín. congelado: {$minCongelado} | Stock mín. hecho: {$minHecho}" : '');

    } else {
        // Actualizar producto existente
        $stmt = $pdo->prepare("
            UPDATE productos
            SET nombre = ?, precio = ?, recetas_idrecetas = ?, tipo = ?, imagen = ?
            WHERE idproductos = ?
        ");
        $stmt->execute([$nombre, $precio, $tipo === 'producto' ? $receta : null, $tipo, $imagenFinal, $idProducto]);

        $pdo->prepare("DELETE FROM box_productos WHERE producto_box = ?")->execute([$idProducto]);

        if ($tipo === 'producto') {
            $pdo->prepare("
                UPDATE stock_productos
                SET stock_minimo = CASE
                    WHEN tipo_stock = 'CONGELADO' THEN ?
                    WHEN tipo_stock = 'HECHO' THEN ?
                END
                WHERE productos_idproductos = ?
            ")->execute([$minCongelado, $minHecho, $idProducto]);
        }

        $accion = 'editar';
        $desc   = "Editó producto: {$nombre} (ID: {$idProducto})" .
                  " | Tipo: {$tipo}" .
                  " | Precio: $" . number_format((float)$precio, 2) .
                  ($tipo === 'producto' ? " | Stock mín. congelado: {$minCongelado} | Stock mín. hecho: {$minHecho}" : '');
    }

    // Box items
    if ($tipo === 'box') {
        $productos  = $_POST['box_producto'] ?? [];
        $cantidades = $_POST['box_cantidad'] ?? [];
        $stmtBox    = $pdo->prepare("INSERT INTO box_productos (producto_box, producto_item, cantidad) VALUES (?,?,?)");
        foreach ($productos as $i => $prod) {
            $cant = $cantidades[$i] ?? 1;
            if (!$prod || !$cant) continue;
            $stmtBox->execute([$idProducto, $prod, $cant]);
        }
    }

    $pdo->commit();

    audit($pdo, $accion, 'productos', $desc);

    echo json_encode(["status" => "ok", "id" => (int)$idProducto]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "mensaje" => $e->getMessage()]);
}
