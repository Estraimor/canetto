<?php
/**
 * Configuración Web Push (VAPID).
 * Genera las claves ejecutando una sola vez: /tienda/generar_vapid.php
 * Luego pega aquí los valores y elimina ese script.
 */

// ── Reemplazá esto con los valores de generar_vapid.php ──────────
define('PUSH_VAPID_PUBLIC',  'PEGAR_AQUI_LA_CLAVE_PUBLICA_BASE64URL');

define('PUSH_VAPID_PRIVATE_PEM', <<<'EOT'
-----BEGIN EC PRIVATE KEY-----
PEGAR AQUI LA CLAVE PRIVADA PEM
-----END EC PRIVATE KEY-----
EOT
);
// ─────────────────────────────────────────────────────────────────

// Remitente (puede ser un mailto: o una URL del sitio)
define('PUSH_SUBJECT', 'mailto:admin@canetto.ar');

// Mensajes según estado del pedido
define('PUSH_MENSAJES', [
    1 => ['titulo' => '✅ Pedido recibido',          'cuerpo' => 'Recibimos tu pedido y lo estamos revisando.'],
    2 => ['titulo' => '🍪 Preparando tu pedido',     'cuerpo' => '¡Tu pedido está siendo preparado con amor!'],
    3 => ['titulo' => '🛵 Tu pedido está en camino', 'cuerpo' => 'El repartidor ya salió. ¡Pronto llegará!'],
    4 => ['titulo' => '🎉 ¡Pedido entregado!',       'cuerpo' => 'Tu pedido fue entregado. ¡Gracias por elegirnos!'],
    5 => ['titulo' => '💳 Pendiente de pago',         'cuerpo' => 'Tu pedido está pendiente de pago.'],
    6 => ['titulo' => '❌ Pedido cancelado',           'cuerpo' => 'Tu pedido fue cancelado. Contactanos si tenés dudas.'],
    7 => ['titulo' => '📦 Listo para retirar',        'cuerpo' => '¡Tu pedido está listo! Podés venir a buscarlo.'],
]);
