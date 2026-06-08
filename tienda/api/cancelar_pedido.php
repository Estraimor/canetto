<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

if (!isset($_SESSION['tienda_cliente_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No autorizado']); exit;
}

$uid      = (int)$_SESSION['tienda_cliente_id'];
$id_venta = (int)($_POST['id_venta'] ?? 0);

if (!$id_venta) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Verificar que el pedido pertenece al cliente y traer su estado y método de pago
    $stmt = $pdo->prepare("
        SELECT v.idventas, v.estado_venta_idestado_venta, mp.nombre AS metodo_pago
        FROM ventas v
        LEFT JOIN metodo_pago mp ON mp.idmetodo_pago = v.metodo_pago_idmetodo_pago
        WHERE v.idventas = ? AND v.usuario_idusuario = ?
    ");
    $stmt->execute([$id_venta, $uid]);
    $venta = $stmt->fetch();

    if (!$venta) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']); exit;
    }

    $estadoActual = (int)$venta['estado_venta_idestado_venta'];
    $metodoPago   = strtolower($venta['metodo_pago'] ?? '');
    $esEfectivo   = str_contains($metodoPago, 'efectivo') || str_contains($metodoPago, 'cash');

    // El cliente solo puede cancelar mientras el pedido no salió de la tienda
    if (!in_array($estadoActual, [1, 2, 7], true)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Este pedido ya no se puede cancelar desde acá. Contactanos por WhatsApp.']); exit;
    }

    // Si fue pagado con Mercado Pago, el dinero ya se procesó: no se puede autocancelar,
    // hay que coordinar el reembolso por WhatsApp (el front-end ya redirige a wa.me en ese caso).
    if (!$esEfectivo) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Este pedido fue pagado con Mercado Pago. Para cancelarlo, coordiná el reembolso con nosotros por WhatsApp.']); exit;
    }

    // Si ya estaba en preparación, el stock HECHO se había descontado: hay que reponerlo
    if ($estadoActual === 2) {
        $stmtItems = $pdo->prepare("SELECT productos_idproductos, cantidad FROM detalle_ventas WHERE ventas_idventas = ?");
        $stmtItems->execute([$id_venta]);
        $stmtStock = $pdo->prepare("UPDATE stock_productos SET stock_actual = stock_actual + :c WHERE productos_idproductos = :p AND tipo_stock = 'HECHO'");
        foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $stmtStock->execute([':c' => $item['cantidad'], ':p' => $item['productos_idproductos']]);
        }
    }

    $upd = $pdo->prepare("
        UPDATE ventas SET estado_venta_idestado_venta = 6, updated_at = NOW()
        WHERE idventas = ?
    ");
    $upd->execute([$id_venta]);

    audit($pdo, 'editar', 'ventas', "Cliente canceló su propio pedido #{$id_venta} (pago en efectivo)");

    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
