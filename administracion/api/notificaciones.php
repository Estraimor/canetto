<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    // Crear tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificaciones_admin (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        tipo          VARCHAR(40)  NOT NULL,
        titulo        VARCHAR(200) NOT NULL,
        descripcion   VARCHAR(400) NULL,
        link          VARCHAR(500) NULL,
        referencia_id INT NULL,
        leida         TINYINT(1)   NOT NULL DEFAULT 0,
        created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try { $pdo->exec("ALTER TABLE ventas ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT 'admin'"); } catch (Throwable $e) {}

    // ── Leer acción antes de generar ─────────────────────────────────────────
    if (!empty($_GET['marcar']) && intval($_GET['marcar'])) {
        $pdo->prepare("UPDATE notificaciones_admin SET leida=1 WHERE id=?")->execute([intval($_GET['marcar'])]);
        echo json_encode(['success' => true]); exit;
    }
    if (!empty($_GET['marcar_todas'])) {
        $pdo->exec("UPDATE notificaciones_admin SET leida=1 WHERE leida=0");
        echo json_encode(['success' => true]); exit;
    }

    // ── ALERTAS VIVAS (siempre calculadas, no se guardan en DB) ──────────────
    // Así aparecen siempre mientras el stock esté mal, aunque se "marquen como leídas"
    $alertasVivas = [];

    // Productos Sin Stock
    $filas = $pdo->query("
        SELECT p.nombre, sp.tipo_stock
        FROM stock_productos sp
        JOIN productos p ON p.idproductos = sp.productos_idproductos
        WHERE sp.stock_actual = 0 AND sp.tipo_stock IN ('HECHO','CONGELADO')
        ORDER BY p.nombre
    ")->fetchAll();
    foreach ($filas as $s) {
        $alertasVivas[] = [
            'id'          => 'viva_sinstock_' . md5($s['nombre'] . $s['tipo_stock']),
            'tipo'        => 'sin_stock',
            'titulo'      => "⛔ Sin Stock: {$s['nombre']} ({$s['tipo_stock']})",
            'descripcion' => 'Requiere producción urgente',
            'link'        => URL_ADMIN . '/stock/index.php',
            'created_at'  => date('Y-m-d H:i:s'),
            'viva'        => true,
        ];
    }

    // Productos Stock bajo mínimo
    $filas = $pdo->query("
        SELECT p.nombre, sp.stock_actual, sp.stock_minimo, sp.tipo_stock
        FROM stock_productos sp
        JOIN productos p ON p.idproductos = sp.productos_idproductos
        WHERE sp.stock_actual > 0 AND sp.stock_actual <= sp.stock_minimo
          AND sp.tipo_stock IN ('HECHO','CONGELADO')
        ORDER BY p.nombre
    ")->fetchAll();
    foreach ($filas as $s) {
        $tipo = strtolower($s['tipo_stock']);
        $alertasVivas[] = [
            'id'          => 'viva_bajo_' . md5($s['nombre'] . $s['tipo_stock']),
            'tipo'        => 'stock_bajo',
            'titulo'      => "⚠️ Stock bajo: {$s['nombre']} ({$s['tipo_stock']})",
            'descripcion' => "Stock {$tipo}: {$s['stock_actual']} u. / Mín: {$s['stock_minimo']} u.",
            'link'        => URL_ADMIN . '/stock/index.php',
            'created_at'  => date('Y-m-d H:i:s'),
            'viva'        => true,
        ];
    }

    // Materias primas Sin Stock
    $filas = $pdo->query("
        SELECT mp.nombre, um.abreviatura AS unidad
        FROM materia_prima mp
        LEFT JOIN unidad_medida um ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
        WHERE mp.stock_actual <= 0 AND mp.activo = 1
        ORDER BY mp.nombre
    ")->fetchAll();
    foreach ($filas as $m) {
        $alertasVivas[] = [
            'id'          => 'viva_mpsin_' . md5($m['nombre']),
            'tipo'        => 'mp_sin_stock',
            'titulo'      => "⛔ MP Sin Stock: {$m['nombre']}",
            'descripcion' => 'Materia prima agotada — reabastecer antes de producir',
            'link'        => URL_ADMIN . '/materias_primas/index.php',
            'created_at'  => date('Y-m-d H:i:s'),
            'viva'        => true,
        ];
    }

    // Materias primas Stock bajo mínimo
    $filas = $pdo->query("
        SELECT mp.nombre, mp.stock_actual, mp.stock_minimo, um.abreviatura AS unidad
        FROM materia_prima mp
        LEFT JOIN unidad_medida um ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
        WHERE mp.stock_actual > 0 AND mp.stock_actual <= mp.stock_minimo AND mp.activo = 1
        ORDER BY mp.nombre
    ")->fetchAll();
    foreach ($filas as $m) {
        $unidad = $m['unidad'] ?? 'u.';
        $alertasVivas[] = [
            'id'          => 'viva_mpbajo_' . md5($m['nombre']),
            'tipo'        => 'mp_stock_bajo',
            'titulo'      => "⚠️ MP Baja: {$m['nombre']}",
            'descripcion' => "Stock: {$m['stock_actual']} {$unidad} / Mín: {$m['stock_minimo']} {$unidad}",
            'link'        => URL_ADMIN . '/materias_primas/index.php',
            'created_at'  => date('Y-m-d H:i:s'),
            'viva'        => true,
        ];
    }

    // Recetas con ingredientes sin stock
    $filas = $pdo->query("
        SELECT DISTINCT r.idrecetas, r.nombre AS receta, mp.nombre AS ingrediente
        FROM recetas r
        JOIN receta_ingredientes ri ON ri.recetas_idrecetas = r.idrecetas
        JOIN materia_prima mp ON mp.idmateria_prima = ri.materia_prima_idmateria_prima
        WHERE mp.stock_actual <= 0 AND mp.activo = 1
        ORDER BY r.nombre
        LIMIT 10
    ")->fetchAll();
    $vistas = [];
    foreach ($filas as $r) {
        if (in_array($r['idrecetas'], $vistas)) continue;
        $vistas[] = $r['idrecetas'];
        $alertasVivas[] = [
            'id'          => 'viva_receta_' . $r['idrecetas'],
            'tipo'        => 'receta_sin_mp',
            'titulo'      => "📋 Receta bloqueada: {$r['receta']}",
            'descripcion' => "Sin {$r['ingrediente']} — no se puede producir",
            'link'        => URL_ADMIN . '/materias_primas/index.php',
            'created_at'  => date('Y-m-d H:i:s'),
            'viva'        => true,
        ];
    }

    // ── 1. Pedidos nuevos sin notificación (últimos 10 min) ──────────────────
    $nuevos = $pdo->query("
        SELECT v.idventas, v.total, v.fecha,
               COALESCE(v.origen,'admin') AS origen,
               COALESCE(u.nombre,'Consumidor') AS cliente_nombre,
               COALESCE(u.apellido,'')          AS cliente_apellido
        FROM ventas v
        LEFT JOIN usuario u ON u.idusuario = v.usuario_idusuario
        WHERE v.estado_venta_idestado_venta IN (1,5)
          AND v.fecha >= NOW() - INTERVAL 10 MINUTE
          AND NOT EXISTS (
              SELECT 1 FROM notificaciones_admin n
              WHERE n.tipo = 'pedido_nuevo' AND n.referencia_id = v.idventas
          )
        ORDER BY v.fecha DESC
        LIMIT 10
    ")->fetchAll();

    foreach ($nuevos as $p) {
        $origen  = $p['origen'] === 'tienda' ? '📱 App' : '🖥 Admin';
        $cliente = trim($p['cliente_nombre'] . ' ' . $p['cliente_apellido']);
        $total   = '$' . number_format($p['total'], 2);
        $pdo->prepare("
            INSERT INTO notificaciones_admin (tipo, titulo, descripcion, link, referencia_id)
            VALUES ('pedido_nuevo', :titulo, :desc, :link, :ref)
        ")->execute([
            ':titulo' => "Nuevo pedido #{$p['idventas']}",
            ':desc'   => "{$origen} · {$cliente} · {$total}",
            ':link'   => URL_ADMIN . '/Ventas/Pedidos/index.php',
            ':ref'    => $p['idventas'],
        ]);
    }

    // ── Devolver: alertas vivas + pedidos no leídos ──────────────────────────
    $noLeidas = $pdo->query("
        SELECT id, tipo, titulo, descripcion, link, created_at
        FROM notificaciones_admin
        WHERE leida = 0
        ORDER BY created_at DESC
        LIMIT 20
    ")->fetchAll();

    // Alertas vivas primero, luego pedidos/eventos de DB
    $todas = array_merge($alertasVivas, $noLeidas);

    echo json_encode([
        'total'          => count($todas),
        'notificaciones' => $todas,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['total' => 0, 'notificaciones' => [], 'error' => $e->getMessage()]);
}
