<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario_id'])) { http_response_code(401); exit; }

// CRÍTICO: liberar la sesión para no bloquear otros requests del mismo browser
session_write_close();

// Headers SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');

// Tiempo suficiente para 60 loops × 5 s + margen
set_time_limit(360);
if (ob_get_level()) ob_end_clean();

$pdo        = Conexion::conectar();
$lastId     = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;
$maxLoops   = 60;  // máximo 60 iteraciones (~5 min) para evitar zombies
$loop       = 0;

function sendSSE(string $event, $data): void {
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

function insertarPedidosNuevos(PDO $pdo): void {
    $nuevos = $pdo->query("
        SELECT v.idventas, v.total, v.fecha,
               COALESCE(v.origen,'admin') AS origen,
               COALESCE(v.tipo_entrega,'retiro') AS tipo_entrega,
               COALESCE(v.toppings_json,'') AS toppings_json,
               COALESCE(u.nombre,'Consumidor') AS cliente_nombre,
               COALESCE(u.apellido,'')          AS cliente_apellido,
               COALESCE(mp.nombre,'—')           AS metodo_pago
        FROM ventas v
        LEFT JOIN usuario u      ON u.idusuario         = v.usuario_idusuario
        LEFT JOIN metodo_pago mp ON mp.idmetodo_pago     = v.metodo_pago_idmetodo_pago
        WHERE v.estado_venta_idestado_venta = 1
          AND v.fecha >= NOW() - INTERVAL 10 MINUTE
          AND NOT EXISTS (
              SELECT 1 FROM notificaciones_admin n
              WHERE n.tipo = 'pedido_nuevo' AND n.referencia_id = v.idventas
          )
        ORDER BY v.fecha DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($nuevos as $p) {
        $prods = $pdo->prepare("
            SELECT pr.nombre, dv.cantidad, dv.precio_unitario, pr.tipo,
                   (SELECT GROUP_CONCAT(p2.nombre ORDER BY p2.nombre SEPARATOR ', ')
                    FROM box_productos bp
                    JOIN productos p2 ON p2.idproductos = bp.producto_item
                    WHERE bp.producto_box = pr.idproductos
                   ) AS contenido_box
            FROM detalle_ventas dv
            JOIN productos pr ON pr.idproductos = dv.productos_idproductos
            WHERE dv.ventas_idventas = :id
        ");
        $prods->execute([':id' => $p['idventas']]);
        $productos = $prods->fetchAll(PDO::FETCH_ASSOC);

        $toppings = [];
        if ($p['toppings_json']) {
            $tj = json_decode($p['toppings_json'], true);
            if (is_array($tj)) {
                foreach ($tj as $item) {
                    if (is_string($item)) $toppings[] = $item;
                    elseif (is_array($item) && !empty($item['nombre'])) $toppings[] = $item['nombre'];
                }
            }
        }

        $origen  = $p['origen'] === 'tienda' ? '📱 App' : '🖥 Admin';
        $cliente = trim($p['cliente_nombre'] . ' ' . $p['cliente_apellido']);
        $entrega = $p['tipo_entrega'] === 'envio' ? '🛵 Envío' : '🏪 Retiro';

        $datosJson = json_encode([
            'cliente'   => $cliente,
            'origen'    => $origen,
            'entrega'   => $entrega,
            'metodo'    => $p['metodo_pago'],
            'total'     => $p['total'],
            'productos' => $productos,
            'toppings'  => array_unique($toppings),
        ], JSON_UNESCAPED_UNICODE);

        $pdo->prepare("
            INSERT INTO notificaciones_admin (tipo, titulo, descripcion, datos_json, link, referencia_id)
            VALUES ('pedido_nuevo', :titulo, :desc, :datos, :link, :ref)
        ")->execute([
            ':titulo' => "Nuevo pedido #{$p['idventas']}",
            ':desc'   => "{$origen} · {$cliente} · \${$p['total']}",
            ':datos'  => $datosJson,
            ':link'   => URL_ADMIN . '/Ventas/Pedidos/index.php',
            ':ref'    => $p['idventas'],
        ]);
    }
}

// Ping inicial para confirmar conexión
sendSSE('ping', ['ok' => true, 'ts' => time()]);

while (!connection_aborted() && $loop < $maxLoops) {
    $loop++;

    // Detectar e insertar pedidos nuevos en cada ciclo (antes de leer)
    insertarPedidosNuevos($pdo);

    // Notificaciones no leídas
    $stmt = $pdo->prepare("
        SELECT id, tipo, titulo, descripcion, datos_json, link, created_at
        FROM notificaciones_admin
        WHERE leida = 0
        ORDER BY id DESC
        LIMIT 30
    ");
    $stmt->execute();
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total   = count($notifs);
    $maxIdNow = $notifs ? (int)$notifs[0]['id'] : 0;

    // Enviar estado de badge siempre
    sendSSE('badge', ['total' => $total]);

    // Enviar lista completa solo si cambió algo
    if ($maxIdNow > $lastId) {
        sendSSE('notificaciones', ['notificaciones' => $notifs, 'total' => $total]);

        // Toasts solo para pedidos nuevos muy recientes que no vimos antes
        foreach ($notifs as $n) {
            if ((int)$n['id'] > $lastId && $n['tipo'] === 'pedido_nuevo') {
                $age = time() - strtotime($n['created_at']);
                if ($age < 35) {
                    sendSSE('nuevo_pedido', $n);
                }
            }
        }

        $lastId = $maxIdNow;
    }

    // Ping de keepalive cada ciclo
    sendSSE('ping', ['ts' => time()]);

    sleep(5); // poll interno cada 5 segundos
}

// Decirle al cliente que reconecte
echo "retry: 3000\n\n";
if (ob_get_level()) ob_flush();
flush();
