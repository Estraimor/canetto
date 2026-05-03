<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$input            = json_decode(file_get_contents('php://input'), true) ?: [];
$carrito          = $input['carrito']     ?? [];
$cliente          = $input['cliente']     ?? null;
$metodo_pago      = (int)($input['metodo_pago'] ?? 0);
$sucursal_id      = isset($input['sucursal_id']) && $input['sucursal_id'] ? (int)$input['sucursal_id'] : null;
$observacion      = trim($input['observacion'] ?? '');
$total            = (float)($input['total'] ?? 0);
$tipo_entrega     = in_array($input['tipo_entrega'] ?? '', ['retiro','envio']) ? $input['tipo_entrega'] : 'retiro';
$es_mp            = !empty($input['es_mp']);
$direccion_entrega= trim($input['direccion_entrega'] ?? '');
$lat_entrega      = isset($input['lat_entrega'])  && is_numeric($input['lat_entrega'])  ? (float)$input['lat_entrega']  : null;
$lng_entrega      = isset($input['lng_entrega'])  && is_numeric($input['lng_entrega'])  ? (float)$input['lng_entrega']  : null;
$costo_envio_cli  = isset($input['costo_envio'])  && is_numeric($input['costo_envio'])  ? (float)$input['costo_envio']  : 0.0;
$toppings_data    = is_array($input['toppings'] ?? null) ? $input['toppings'] : [];
$costo_toppings   = isset($input['costo_toppings']) && is_numeric($input['costo_toppings']) ? (float)$input['costo_toppings'] : 0.0;

/* ── Haversine ── */
function haversineKm(float $la1, float $lo1, float $la2, float $lo2): float {
    $R = 6371; $d = M_PI / 180;
    $a = sin(($la2-$la1)*$d/2)**2 + cos($la1*$d)*cos($la2*$d)*sin(($lo2-$lo1)*$d/2)**2;
    return 2 * $R * atan2(sqrt($a), sqrt(1-$a));
}

if (empty($carrito) || !$metodo_pago) {
    echo json_encode(['success'=>false,'message'=>'Faltan datos obligatorios']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Add tienda columns to ventas if they don't exist yet (idempotent via try/catch)
    foreach ([
        "ALTER TABLE ventas ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT 'pos'",
        "ALTER TABLE ventas ADD COLUMN sucursal_retiro_idsucursal INT NULL",
        "ALTER TABLE ventas ADD COLUMN observacion_cliente TEXT NULL",
        "ALTER TABLE detalle_ventas ADD COLUMN precio_original DECIMAL(10,2) NULL",
        "ALTER TABLE detalle_ventas ADD COLUMN descuento_pct DECIMAL(5,2) NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }
    // Agregar columnas si no existen (antes de la transacción para evitar implicit commit de DDL)
    foreach ([
        "ALTER TABLE ventas ADD COLUMN toppings_json TEXT NULL",
        "ALTER TABLE ventas ADD COLUMN tipo_entrega VARCHAR(10) NOT NULL DEFAULT 'retiro'",
        "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
        "ALTER TABLE ventas ADD COLUMN direccion_entrega TEXT NULL",
        "ALTER TABLE ventas ADD COLUMN lat_entrega DECIMAL(10,8) NULL",
        "ALTER TABLE ventas ADD COLUMN lng_entrega DECIMAL(11,8) NULL",
        "ALTER TABLE ventas ADD COLUMN costo_envio DECIMAL(10,2) NOT NULL DEFAULT 0",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    /* ── Calcular costo de envío server-side ── */
    $costo_envio = 0.0;
    // El total ya viene con toppings incluidos desde el front — no sumar de nuevo

    if ($tipo_entrega === 'envio' && $lat_entrega !== null && $lng_entrega !== null) {
        $sucRow = $pdo->query("SELECT latitud, longitud FROM sucursal WHERE activo=1 AND latitud IS NOT NULL AND longitud IS NOT NULL ORDER BY idsucursal ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($sucRow) {
            $distKm  = haversineKm((float)$sucRow['latitud'], (float)$sucRow['longitud'], $lat_entrega, $lng_entrega);
            $tarRow  = $pdo->prepare("SELECT precio FROM tarifas_envio WHERE activo=1 AND km_desde<=? AND km_hasta>? ORDER BY km_desde ASC LIMIT 1");
            $tarRow->execute([$distKm, $distKm]);
            $tarifa  = $tarRow->fetchColumn();
            if ($tarifa === false) {
                // fallback: tramo más alto disponible
                $tarifa = $pdo->query("SELECT precio FROM tarifas_envio WHERE activo=1 ORDER BY km_hasta DESC LIMIT 1")->fetchColumn();
            }
            $costo_envio = $tarifa !== false ? (float)$tarifa : ($costo_envio_cli > 0 ? $costo_envio_cli : 29000.0);
        } else {
            $costo_envio = $costo_envio_cli > 0 ? $costo_envio_cli : 29000.0;
        }
        $total += $costo_envio;
    }

    $pdo->beginTransaction();

    // --- Resolver cliente ---
    $cNombre = 'Cliente';
    if (isset($_SESSION['tienda_cliente_id'])) {
        $idusuario = (int)$_SESSION['tienda_cliente_id'];
        $u = $pdo->prepare("SELECT CONCAT(nombre,' ',COALESCE(apellido,'')) FROM usuario WHERE idusuario=?");
        $u->execute([$idusuario]);
        $cNombre = trim($u->fetchColumn() ?: 'Cliente');
    } elseif ($cliente) {
        $nombre  = trim($cliente['nombre']  ?? '');
        $celular = trim($cliente['celular'] ?? '');
        if (!$nombre) throw new Exception("Nombre del cliente requerido");
        $existing = null;
        if ($celular) {
            $s = $pdo->prepare("SELECT idusuario FROM usuario WHERE celular=? AND activo=1");
            $s->execute([$celular]); $existing = $s->fetchColumn();
        }
        if ($existing) {
            $idusuario = (int)$existing;
        } else {
            $pass = password_hash(uniqid('g_',true), PASSWORD_DEFAULT);
            $ins  = $pdo->prepare("INSERT INTO usuario (nombre,apellido,celular,password_hash,activo,created_at,updated_at) VALUES (?,?,?,?,1,NOW(),NOW())");
            $ins->execute([$nombre, $cliente['apellido'] ?? null, $celular ?: null, $pass]);
            $idusuario = (int)$pdo->lastInsertId();
        }
        $cNombre = $nombre;
    } else {
        throw new Exception("Información del cliente requerida");
    }

    // --- Crear venta ---
    $pdo->prepare("
        INSERT INTO ventas
        (usuario_idusuario, total, estado_venta_idestado_venta,
         metodo_pago_idmetodo_pago, sucursal_retiro_idsucursal,
         observacion_cliente, tipo_entrega, direccion_entrega,
         lat_entrega, lng_entrega, costo_envio, toppings_json, origen, fecha, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'tienda',NOW(),NOW(),NOW())
    ")->execute([
        $idusuario, $total, $es_mp ? 5 : 1, $metodo_pago,
        $tipo_entrega === 'retiro' ? $sucursal_id : null,
        $observacion ?: null,
        $tipo_entrega,
        $direccion_entrega ?: null,
        $lat_entrega,
        $lng_entrega,
        $costo_envio,
        !empty($toppings_data) ? json_encode($toppings_data, JSON_UNESCAPED_UNICODE) : null,
    ]);
    $id_venta = (int)$pdo->lastInsertId();

    // --- Detalle ---
    $stmtD = $pdo->prepare("INSERT INTO detalle_ventas (ventas_idventas, productos_idproductos, cantidad, precio_unitario, precio_original, descuento_pct) VALUES (?,?,?,?,?,?)");
    $resumen = [];
    foreach ($carrito as $item) {
        $stmtD->execute([
            $id_venta,
            (int)$item['id'],
            (int)$item['cantidad'],
            (float)$item['precio'],
            isset($item['precio_original']) && $item['precio_original'] ? (float)$item['precio_original'] : null,
            isset($item['descuento_pct'])   && $item['descuento_pct']   ? (float)$item['descuento_pct']   : null,
        ]);
        $label = $item['nombre'] ?? "Prod #{$item['id']}";
        if (!empty($item['descuento_pct'])) $label .= " ({$item['descuento_pct']}% OFF)";
        $resumen[] = $label . " ×{$item['cantidad']}";
    }

    // --- Consumir packaging ---
    $stmtGetPkg = $pdo->prepare("
        SELECT pp.packaging_idpackaging, pp.cantidad AS cant_pkg, pk.nombre AS pkg_nombre
        FROM producto_packaging pp
        JOIN packaging pk ON pk.idpackaging = pp.packaging_idpackaging
        WHERE pp.productos_idproductos = ?
    ");
    $stmtDescPkg = $pdo->prepare("
        UPDATE packaging
        SET stock_actual = stock_actual - ?, updated_at = NOW()
        WHERE idpackaging = ?
    ");
    foreach ($carrito as $item) {
        $stmtGetPkg->execute([(int)$item['id']]);
        foreach ($stmtGetPkg->fetchAll(PDO::FETCH_ASSOC) as $pkg) {
            $consumo = $pkg['cant_pkg'] * (int)$item['cantidad'];
            $stmtDescPkg->execute([$consumo, $pkg['packaging_idpackaging']]);
        }
    }

    $pdo->commit();

    $sucNombre = '';
    if ($sucursal_id) {
        $sn = $pdo->prepare("SELECT nombre FROM sucursal WHERE idsucursal=?");
        $sn->execute([$sucursal_id]);
        $sucNombre = ' | Retiro: '.($sn->fetchColumn() ?: "Suc #{$sucursal_id}");
    }

    audit($pdo, 'crear', 'ventas',
        "Pedido online #{$id_venta} | Cliente: {$cNombre}" .
        " | ".implode(', ', $resumen).
        " | Total: $".number_format($total, 2) .
        $sucNombre
    );

    echo json_encode(['success'=>true, 'id_venta'=>$id_venta, 'costo_envio'=>$costo_envio, 'total'=>$total]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
