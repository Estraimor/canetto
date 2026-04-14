<?php
/**
 * tienda/api/mp_webhook.php
 * Recibe notificaciones IPN/Webhook de MercadoPago y actualiza el estado del pedido.
 */
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/mp_config.php';

http_response_code(200); // Responder rápido para que MP no reintente

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$type = $body['type'] ?? ($_GET['topic'] ?? '');
$id   = $body['data']['id'] ?? ($_GET['id'] ?? '');

if (!$type || !$id) exit;

if ($type === 'payment') {
    // Consultar el pago en MP
    $ch = curl_init("https://api.mercadopago.com/v1/payments/{$id}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . MP_ACCESS_TOKEN],
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) exit;

    $pago = json_decode($resp, true);
    $status   = $pago['status']             ?? '';
    $extRef   = $pago['external_reference'] ?? '';

    // external_reference = 'canetto_123'
    if (!str_starts_with($extRef, 'canetto_')) exit;
    $pedidoId = (int)substr($extRef, 8);
    if (!$pedidoId) exit;

    // Mapear status MP → estado de la venta (ajustar IDs según la tabla estado_venta)
    $estadoMap = [
        'approved'    => 'pagado',
        'pending'     => 'pendiente_pago',
        'in_process'  => 'pendiente_pago',
        'rejected'    => 'cancelado',
        'cancelled'   => 'cancelado',
    ];
    $nuevoEstado = $estadoMap[$status] ?? null;
    if (!$nuevoEstado) exit;

    try {
        $pdo = Conexion::conectar();
        // Buscar el idestado por nombre
        $stmt = $pdo->prepare("SELECT idestado_venta FROM estado_venta WHERE nombre = ? LIMIT 1");
        $stmt->execute([$nuevoEstado]);
        $row = $stmt->fetch();
        if (!$row) exit;

        $pdo->prepare("UPDATE ventas SET estado_venta_idestado_venta = ?, updated_at = NOW() WHERE idventas = ?")
            ->execute([$row['idestado_venta'], $pedidoId]);

        // Guardar el payment_id de MP en la venta (columna opcional)
        try {
            $pdo->exec("ALTER TABLE ventas ADD COLUMN mp_payment_id VARCHAR(64) NULL");
        } catch (Throwable $e) {}
        $pdo->prepare("UPDATE ventas SET mp_payment_id = ? WHERE idventas = ?")
            ->execute([(string)$id, $pedidoId]);
    } catch (Throwable $e) {
        error_log('[MP Webhook] ' . $e->getMessage());
    }
}
