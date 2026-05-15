<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }

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

    // ── Procesar imágenes ────────────────────────────────────────────────────
    $imgDir    = __DIR__ . '/../../../img/productos';
    if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);
    $imagenFinal = $imagenActual ?: null;
    $imagenesNuevas = []; // archivos subidos en esta request

    // Múltiples imágenes nuevas
    if (!empty($_FILES['imagenes_nuevas']['name'][0])) {
        $files   = $_FILES['imagenes_nuevas'];
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $count   = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;
            if ($files['size'][$i] > 5 * 1024 * 1024) continue;
            $newName = uniqid('prod_') . '.' . $ext;
            if (move_uploaded_file($files['tmp_name'][$i], $imgDir . '/' . $newName)) {
                $imagenesNuevas[] = $newName;
                if (!$imagenFinal) $imagenFinal = $newName; // primera como principal
            }
        }
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

    // Guardar imágenes nuevas en productos_imagenes
    if (!empty($imagenesNuevas)) {
        $maxOrden = $pdo->prepare("SELECT COALESCE(MAX(orden),0) FROM productos_imagenes WHERE productos_idproductos=?");
        $maxOrden->execute([$idProducto]);
        $orden = (int)$maxOrden->fetchColumn();
        $stmtImg = $pdo->prepare("INSERT INTO productos_imagenes (productos_idproductos, archivo, orden) VALUES (?,?,?)");
        foreach ($imagenesNuevas as $archivo) {
            $stmtImg->execute([$idProducto, $archivo, ++$orden]);
        }
        // Sincronizar campo imagen con la primera imagen de la tabla
        $primera = $pdo->prepare("SELECT archivo FROM productos_imagenes WHERE productos_idproductos=? ORDER BY orden ASC, id ASC LIMIT 1");
        $primera->execute([$idProducto]);
        $pdo->prepare("UPDATE productos SET imagen=? WHERE idproductos=?")->execute([$primera->fetchColumn(), $idProducto]);
    }

    $pdo->commit();

    audit($pdo, $accion, 'productos', $desc);

    echo json_encode(["status" => "ok", "id" => (int)$idProducto]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "mensaje" => $e->getMessage()]);
}
