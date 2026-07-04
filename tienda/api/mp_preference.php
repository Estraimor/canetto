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

// ── Armar título con los productos reales ───────────────────────────────────
$nombresItems = [];
$totalUnidades = 0;
foreach ($items as $it) {
    $nombre = trim($it['nombre'] ?? '');
    $cant   = (int)($it['cantidad'] ?? 1);
    $totalUnidades += $cant;
    if ($nombre) {
        $nombresItems[] = $cant > 1 ? "{$nombre} ×{$cant}" : $nombre;
    }
}

// Título principal: nombre(s) del producto, máx 2 + "y más" si hay más
$titulo = 'Cookies Canetto';
if (!empty($nombresItems)) {
    if (count($nombresItems) === 1) {
        $titulo = $nombresItems[0];
    } elseif (count($nombresItems) === 2) {
        $titulo = $nombresItems[0] . ' y ' . $nombresItems[1];
    } else {
        $titulo = $nombresItems[0] . ', ' . $nombresItems[1] . ' y más';
    }
}
// MP limita el título a 256 caracteres
$titulo = mb_substr($titulo, 0, 256);

$descripcion = $totalUnidades > 0
    ? "{$totalUnidades} " . ($totalUnidades === 1 ? 'cookie artesanal' : 'cookies artesanales') . ' — Canetto Cookies'
    : 'Cookies artesanales — Canetto Cookies';

// ── Armar items para MP ──────────────────────────────────────────────────────
$mpItems = [];
$mpItems[] = [
    'id'          => 'pedido_' . $pedidoId,
    'title'       => $titulo,
    'description' => $descripcion,
    'quantity'    => 1,
    'unit_price'  => $totalAmt,
    'currency_id' => 'ARS',
    'picture_url' => 'https://canettocookies.com/img/canetto_logo.jpg',
];

// ── URLs de retorno ──────────────────────────────────────────────────────────
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isProd = !in_array($host, ['localhost', '127.0.0.1'], true);
$base   = $isProd ? 'https://tienda.canettocookies.com' : 'http://localhost/canetto/tienda';

$preference = [
    'items'              => $mpItems,
    'external_reference' => 'canetto_' . $pedidoId,
    'marketplace'        => 'NONE',
    'back_urls'          => [
        'success' => $base . '/mp_retorno.php?status=success&pedido=' . $pedidoId,
        'failure' => $base . '/mp_retorno.php?status=failure&pedido=' . $pedidoId,
        'pending' => $base . '/mp_retorno.php?status=pending&pedido=' . $pedidoId,
    ],
    'auto_return'          => 'approved',
    'statement_descriptor' => 'CANETTO COOKIES',
    'notification_url'     => $base . '/api/mp_webhook.php',
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
unset($ch);

$data = json_decode($response, true);

if ($httpCode === 201 && isset($data['init_point'])) {
    // Registrar en pagos_mercadopago el inicio del pago
    try {
        $pdo = Conexion::conectar();
        $pdo->prepare("
            INSERT INTO pagos_mercadopago
                (ventas_idventas, mp_preference_id, estado_mp, monto, external_reference, created_at)
            VALUES (?, ?, 'pending', ?, ?, NOW())
        ")->execute([$pedidoId, $data['id'], $totalAmt, 'canetto_' . $pedidoId]);
    } catch (Throwable $e) {
        error_log('[MP Preference] No se pudo guardar en pagos_mercadopago: ' . $e->getMessage());
    }

    echo json_encode([
        'success'        => true,
        'init_point'     => $data['init_point'],
        'preference_id'  => $data['id'],
    ]);
} else {
    error_log('[MP] Error ' . $httpCode . ': ' . ($data['message'] ?? $response));
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo iniciar el pago. Intentá nuevamente.',
    ]);
}
