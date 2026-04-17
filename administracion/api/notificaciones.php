<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    // Crear tabla si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificaciones_admin (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        tipo        VARCHAR(40)  NOT NULL,
        titulo      VARCHAR(200) NOT NULL,
        descripcion VARCHAR(400) NULL,
        link        VARCHAR(500) NULL,
        referencia_id INT NULL,
        leida       TINYINT(1)   NOT NULL DEFAULT 0,
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Agregar columna origen a ventas si no existe (para el icono App/Admin)
    try { $pdo->exec("ALTER TABLE ventas ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT 'admin'"); } catch (Throwable $e) {}

    // ── Pedidos nuevos sin notificación (últimos 10 min) ─────────────────────
    $stmt = $pdo->query("
        SELECT v.idventas, v.total, v.fecha,
               COALESCE(v.origen,'admin') AS origen,
               COALESCE(u.nombre,'Consumidor') AS cliente_nombre,
               COALESCE(u.apellido,'')    AS cliente_apellido
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
    ");
    $nuevos = $stmt->fetchAll();

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

    // ── Stock bajo (una vez por día por producto) ─────────────────────────────
    $stockBajo = $pdo->query("
        SELECT p.nombre, sp.stock_actual, sp.stock_minimo
        FROM stock_productos sp
        JOIN productos p ON p.idproductos = sp.productos_idproductos
        WHERE sp.stock_actual <= sp.stock_minimo AND sp.tipo_stock = 'HECHO'
          AND NOT EXISTS (
              SELECT 1 FROM notificaciones_admin n
              WHERE n.tipo = 'stock_bajo'
                AND n.titulo LIKE CONCAT('%', p.nombre, '%')
                AND DATE(n.created_at) = CURDATE()
          )
        LIMIT 5
    ")->fetchAll();

    foreach ($stockBajo as $s) {
        $pdo->prepare("
            INSERT INTO notificaciones_admin (tipo, titulo, descripcion, link)
            VALUES ('stock_bajo', :titulo, :desc, :link)
        ")->execute([
            ':titulo' => "Stock bajo: {$s['nombre']}",
            ':desc'   => "Stock actual: {$s['stock_actual']} / Mínimo: {$s['stock_minimo']}",
            ':link'   => URL_ADMIN . '/stock/index.php',
        ]);
    }

    // Leer acción: marcar como leída
    if (!empty($_GET['marcar']) && intval($_GET['marcar'])) {
        $pdo->prepare("UPDATE notificaciones_admin SET leida=1 WHERE id=?")->execute([intval($_GET['marcar'])]);
        echo json_encode(['success' => true]); exit;
    }
    if (!empty($_GET['marcar_todas'])) {
        $pdo->exec("UPDATE notificaciones_admin SET leida=1 WHERE leida=0");
        echo json_encode(['success' => true]); exit;
    }

    // Devolver no leídas (últimas 50)
    $noLeidas = $pdo->query("
        SELECT id, tipo, titulo, descripcion, link, created_at
        FROM notificaciones_admin
        WHERE leida = 0
        ORDER BY created_at DESC
        LIMIT 50
    ")->fetchAll();

    $total_no_leidas = $pdo->query("SELECT COUNT(*) FROM notificaciones_admin WHERE leida=0")->fetchColumn();

    echo json_encode([
        'total'          => (int)$total_no_leidas,
        'notificaciones' => $noLeidas
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['total' => 0, 'notificaciones' => [], 'error' => $e->getMessage()]);
}
