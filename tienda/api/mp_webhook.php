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
    unset($ch);

    if ($httpCode !== 200) exit;

    $pago = json_decode($resp, true);
    $status   = $pago['status']             ?? '';
    $extRef   = $pago['external_reference'] ?? '';

    // external_reference = 'canetto_123'
    if (!str_starts_with($extRef, 'canetto_')) exit;
    $pedidoId = (int)substr($extRef, 8);
    if (!$pedidoId) exit;

    // Mapear status MP → ID de estado_venta
    $estadoMap = [
        'approved'   => 1, // Pago confirmado → Pendiente (listo para preparar)
        'pending'    => 5, // Procesando → Pendiente de Pago
        'in_process' => 5,
        'rejected'   => 6, // Rechazado → Cancelado
        'cancelled'  => 6,
    ];
    $nuevoEstadoId = $estadoMap[$status] ?? null;
    if (!$nuevoEstadoId) exit;

    try {
        $pdo = Conexion::conectar();

        // Actualizar estado en ventas
        $pdo->prepare("UPDATE ventas SET estado_venta_idestado_venta = ?, updated_at = NOW() WHERE idventas = ?")
            ->execute([$nuevoEstadoId, $pedidoId]);

        // Guardar/actualizar todos los datos del pago en pagos_mercadopago
        $mpPaymentId  = (string)($pago['id']                   ?? $id);
        $metodoPago   = $pago['payment_method_id']             ?? null;
        $paymentType  = $pago['payment_type_id']               ?? null;
        $monto        = isset($pago['transaction_amount']) ? (float)$pago['transaction_amount'] : null;
        $fechaPago    = !empty($pago['date_approved'])
            ? date('Y-m-d H:i:s', strtotime($pago['date_approved']))
            : (!empty($pago['date_created']) ? date('Y-m-d H:i:s', strtotime($pago['date_created'])) : null);

        // Intentar actualizar la fila que ya existe (creada en mp_preference.php)
        $updated = $pdo->prepare("
            UPDATE pagos_mercadopago
            SET mp_payment_id    = ?,
                estado_mp        = ?,
                metodo_mp        = ?,
                payment_type     = ?,
                monto            = COALESCE(?, monto),
                fecha_pago       = ?,
                raw_response     = ?
            WHERE ventas_idventas = ?
              AND mp_payment_id IS NULL
            LIMIT 1
        ")->execute([$mpPaymentId, $status, $metodoPago, $paymentType, $monto, $fechaPago, $resp, $pedidoId]);

        // Si no existía fila previa (pagos directos sin preference), insertar
        $rowsUpdated = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();
        if ((int)$rowsUpdated === 0) {
            $pdo->prepare("
                INSERT INTO pagos_mercadopago
                    (ventas_idventas, mp_payment_id, estado_mp, metodo_mp, payment_type,
                     monto, fecha_pago, raw_response, external_reference, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([$pedidoId, $mpPaymentId, $status, $metodoPago, $paymentType,
                         $monto, $fechaPago, $resp, $extRef]);
        }

    } catch (Throwable $e) {
        error_log('[MP Webhook] ' . $e->getMessage());
    }
}
