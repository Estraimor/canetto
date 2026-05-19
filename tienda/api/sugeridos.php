<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

$id   = (int)($_GET['id']   ?? 0);
$tipo = trim($_GET['tipo']  ?? '');

if (!$id) { echo json_encode([]); exit; }

try {
    $pdo = Conexion::conectar();

    // ── Paso 1: productos frecuentemente comprados juntos ─────────────
    // Buscamos en detalle_ventas qué otros productos aparecen
    // en la misma venta que el producto actual.
    $stmt = $pdo->prepare("
        SELECT p.idproductos, p.nombre, p.precio, p.tipo, p.imagen,
               COUNT(*)  AS veces,
               CASE
                 WHEN p.tipo = 'box' THEN (
                   SELECT COALESCE(MIN(FLOOR(sp2.stock_actual / bp.cantidad)), 0)
                   FROM box_productos bp
                   JOIN stock_productos sp2
                     ON sp2.productos_idproductos = bp.producto_item
                    AND sp2.tipo_stock = 'HECHO'
                   WHERE bp.producto_box = p.idproductos
                 )
                 ELSE COALESCE(MAX(CASE WHEN sp.tipo_stock='HECHO' THEN sp.stock_actual END), 0)
               END AS stock
        FROM detalle_ventas dv1
        JOIN detalle_ventas dv2
          ON dv2.ventas_idventas = dv1.ventas_idventas
         AND dv2.productos_idproductos != dv1.productos_idproductos
        JOIN productos p
          ON p.idproductos = dv2.productos_idproductos AND p.activo = 1
        LEFT JOIN stock_productos sp
          ON sp.productos_idproductos = p.idproductos AND p.tipo != 'box'
        WHERE dv1.productos_idproductos = ?
        GROUP BY p.idproductos, p.nombre, p.precio, p.tipo, p.imagen
        HAVING stock > 0
        ORDER BY veces DESC
        LIMIT 3
    ");
    $stmt->execute([$id]);
    $sugeridos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── Paso 2: completar con populares si hay menos de 2 sugerencias ─
    if (count($sugeridos) < 2) {
        $excluir = array_merge([$id], array_column($sugeridos, 'idproductos'));
        $ph      = implode(',', array_fill(0, count($excluir), '?'));

        // Preferimos el mismo tipo; si es cookie → cookies primero
        $params  = $excluir;
        $tipoSQL = $tipo ? "AND p.tipo = ?" : "";
        if ($tipo) $params[] = $tipo;

        $need = 3 - count($sugeridos);
        $stmt2 = $pdo->prepare("
            SELECT p.idproductos, p.nombre, p.precio, p.tipo, p.imagen,
                   COUNT(dv.id) AS veces,
                   CASE
                     WHEN p.tipo = 'box' THEN (
                       SELECT COALESCE(MIN(FLOOR(sp2.stock_actual / bp.cantidad)), 0)
                       FROM box_productos bp
                       JOIN stock_productos sp2
                         ON sp2.productos_idproductos = bp.producto_item
                        AND sp2.tipo_stock = 'HECHO'
                       WHERE bp.producto_box = p.idproductos
                     )
                     ELSE COALESCE(MAX(CASE WHEN sp.tipo_stock='HECHO' THEN sp.stock_actual END), 0)
                   END AS stock
            FROM productos p
            LEFT JOIN detalle_ventas dv ON dv.productos_idproductos = p.idproductos
            LEFT JOIN stock_productos sp
              ON sp.productos_idproductos = p.idproductos AND p.tipo != 'box'
            WHERE p.activo = 1
              AND p.idproductos NOT IN ($ph)
              $tipoSQL
            GROUP BY p.idproductos, p.nombre, p.precio, p.tipo, p.imagen
            HAVING stock > 0
            ORDER BY veces DESC
            LIMIT $need
        ");
        $stmt2->execute($params);
        $sugeridos = array_merge($sugeridos, $stmt2->fetchAll(PDO::FETCH_ASSOC));
    }

    // Limpiar datos de salida
    foreach ($sugeridos as &$s) {
        $s['idproductos'] = (int)$s['idproductos'];
        $s['precio']      = (float)$s['precio'];
        $s['stock']       = (float)$s['stock'];
        unset($s['veces']);
    }

    echo json_encode($sugeridos, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([]);
}
