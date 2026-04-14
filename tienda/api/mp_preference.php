<?php
/**
 * tienda/api/mp_preference.php
 * Crea una preferencia de pago en MercadoPago y devuelve el init_point.
 */
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/mp_config.php';
header('Content-Type: application/json');

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$pedidoId  = (int)($body['pedido_id'] ?? 0);
$items     = $body['items']           ?? [];
$totalAmt  = (float)($body['total']   ?? 0);

if (!$pedidoId || $totalAmt <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

// ── Armar items para MP ──────────────────────────────────────────────────────
$mpItems = [];
// Usamos un único ítem consolidado con el nombre de la marca
// para que en la pantalla de pago de MP aparezca "Canetto Cookies"
// y no el nombre interno de cada producto.
$mpItems[] = [
    'id'          => 'pedido_' . $pedidoId,
    'title'       => 'Canetto Cookies',
    'description' => 'Pedido #' . $pedidoId,
    'quantity'    => 1,
    'unit_price'  => $totalAmt,
    'currency_id' => 'ARS',
    'picture_url' => 'https://canettocookies.com/img/canetto_logo.jpg',
];

// ── URLs de retorno ──────────────────────────────────────────────────────────
// SITE_URL viene de config/mailer.php → pero mailer no se incluye aquí.
// Detectamos el entorno igual que helpers.php.
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isProd = !in_array($host, ['localhost', '127.0.0.1'], true);
$base   = $isProd ? 'https://canettocookies.com' : 'http://localhost/canetto';

$preference = [
    'items'              => $mpItems,
    'external_reference' => 'canetto_' . $pedidoId,
    'marketplace'        => 'NONE',
    'back_urls'          => [
        'success' => $base . '/tienda/mp_retorno.php?status=success&pedido=' . $pedidoId,
        'failure' => $base . '/tienda/mp_retorno.php?status=failure&pedido=' . $pedidoId,
        'pending' => $base . '/tienda/mp_retorno.php?status=pending&pedido=' . $pedidoId,
    ],
    'auto_return'          => 'approved',
    'statement_descriptor' => 'CANETTO COOKIES',   // aparece en el resumen de tarjeta
    'notification_url'     => $base . '/tienda/api/mp_webhook.php',
    'payment_methods'      => ['installments' => 1],
];

// ── Llamada a la API de MP ───────────────────────────────────────────────────
$ch = curl_init('https://api.mercadopago.com/checkout/preferences');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($preference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode === 201 && isset($data['init_point'])) {
    echo json_encode([
        'success'     => true,
        'init_point'  => $data['init_point'],
        'preference_id' => $data['id'],
    ]);
} else {
    error_log('[MP] Error ' . $httpCode . ': ' . ($data['message'] ?? $response));
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo iniciar el pago. Intentá nuevamente.',
    ]);
}
