<?php
/**
 * config/cors.php
 * Incluir al inicio de cualquier API que reciba requests cross-subdomain.
 * Requiere que APP_BOOT esté definido.
 */
if (!defined('APP_BOOT')) { http_response_code(403); exit; }

$_corsAllowed = [
    'https://administracion.canettocookies.com',
    'https://tienda.canettocookies.com',
    'https://repartidor.canettocookies.com',
    'http://localhost',
    'http://127.0.0.1',
];

$_origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($_origin, $_corsAllowed, true)) {
    header('Access-Control-Allow-Origin: ' . $_origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Vary: Origin');
}

// Responder preflight OPTIONS inmediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

unset($_corsAllowed, $_origin);
