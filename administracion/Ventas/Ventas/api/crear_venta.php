<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
require_once __DIR__ . '/../../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

$carrito     = $input['carrito']     ?? [];
$cliente     = $input['cliente']     ?? null;
$metodo_pago = intval($input['metodo_pago'] ?? 0);
$total       = floatval($input['total']     ?? 0);
$toppings_global = $input['toppings'] ?? []; // [{id, nombre, precio}]

if (!$carrito || !$cliente || !$metodo_pago) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Obtener nombre del método de pago para auditoría
    $stmtMP = $pdo->prepare("SELECT nombre FROM metodo_pago WHERE idmetodo_pago = ?");
    $stmtMP->execute([$metodo_pago]);
    $nombreMetodo = $stmtMP->fetchColumn() ?: "ID {$metodo_pago}";

    $pdo->beginTransaction();

    // 1. Resolver usuario
    $clienteNuevo = false;
    if (isset($cliente['id'])) {
        $idusuario    = intval($cliente['id']);
        $clienteNombre = trim(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? ''));
    } else {
        $clienteNuevo  = true;
        $clienteNombre = trim(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? ''));
        $pass_hash = password_hash(uniqid('', true), PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO usuario (nombre, apellido, dni, celular, password_hash, activo, created_at, updated_at)
             VALUES (:nombre, :apellido, :dni, :celular, :password, 1, NOW(), NOW())"
        );
        $stmt->execute([
            ':nombre'   => $cliente['nombre']   ?? '',
            ':apellido' => $cliente['apellido'] ?? '',
            ':dni'      => $cliente['dni']      ?: null,
            ':celular'  => $cliente['celular']  ?: null,
            ':password' => $pass_hash,
        ]);
        $idusuario = $pdo->lastInsertId();
    }

    // Agregar toppings al total (el front ya los incluye, pero re-validamos)
    try { $pdo->exec("ALTER TABLE ventas ADD COLUMN toppings_json TEXT NULL"); } catch (Throwable $e) {}
    try { $pdo->exec("ALTER TABLE ventas ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT 'pos'"); } catch (Throwable $e) {}

    $toppingsJson = !empty($toppings_global) ? json_encode($toppings_global, JSON_UNESCAPED_UNICODE) : null;

    // 2. Crear venta
    $stmt = $pdo->prepare(
        "INSERT INTO ventas (usuario_idusuario, total, estado_venta_idestado_venta,
                             metodo_pago_idmetodo_pago, toppings_json, origen, fecha, created_at, updated_at)
         VALUES (:usuario, :total, 2, :metodo, :toppings, 'pos', NOW(), NOW(), NOW())"
    );
    $stmt->execute([':usuario' => $idusuario, ':total' => $total, ':metodo' => $metodo_pago, ':toppings' => $toppingsJson]);
    $id_venta = $pdo->lastInsertId();

    // 3. Detalle de venta
    $stmtDet = $pdo->prepare(
        "INSERT INTO detalle_ventas (ventas_idventas, productos_idproductos, cantidad, precio_unitario)
         VALUES (:venta, :producto, :cantidad, :precio)"
    );
    $productosResumen = [];
    foreach ($carrito as $item) {
        $stmtDet->execute([
            ':venta'    => $id_venta,
            ':producto' => intval($item['id']),
            ':cantidad' => intval($item['cantidad']),
            ':precio'   => floatval($item['precio']),
        ]);
        $tops = !empty($item['toppings']) ? ' (+'.implode(', ', array_column($item['toppings'], 'nombre')).')' : '';
        $productosResumen[] = ($item['nombre'] ?? "Prod #{$item['id']}") . " x{$item['cantidad']}" . $tops;
    }

    // 4. Consumir packaging
    $stmtGetPkg = $pdo->prepare("
        SELECT pp.packaging_idpackaging, pp.cantidad AS cant_pkg
        FROM producto_packaging pp
        WHERE pp.productos_idproductos = ?
    ");
    $stmtDescPkg = $pdo->prepare("
        UPDATE packaging
        SET stock_actual = stock_actual - ?, updated_at = NOW()
        WHERE idpackaging = ?
    ");
    foreach ($carrito as $item) {
        $stmtGetPkg->execute([intval($item['id'])]);
        foreach ($stmtGetPkg->fetchAll(PDO::FETCH_ASSOC) as $pkg) {
            $consumo = $pkg['cant_pkg'] * intval($item['cantidad']);
            $stmtDescPkg->execute([$consumo, $pkg['packaging_idpackaging']]);
        }
    }

    $pdo->commit();

    audit($pdo, 'crear', 'ventas',
        "Nueva venta #{$id_venta}" .
        " | Cliente: {$clienteNombre}" . ($clienteNuevo ? ' (nuevo)' : '') .
        " | Productos: " . implode(', ', $productosResumen) .
        " | Total: $" . number_format($total, 2) .
        " | Método: {$nombreMetodo}"
    );

    echo json_encode(['success' => true, 'id_venta' => $id_venta]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
