<?php
// config/env.php
declare(strict_types=1);

if (!defined('APP_BOOT')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isProduccion = !in_array($host, ['localhost', '127.0.0.1'], true);

if ($isProduccion) {
    // ── PRODUCCIÓN (Hostinger) ──────────────────
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u966473590_canetto');   // <-- reemplazar con el nombre real en Hostinger
    define('DB_USER', 'u966473590_lucianogastonb');   // <-- reemplazar con el usuario real en Hostinger
    define('DB_PASS', 'Lucianobarros820012.'); // <-- reemplazar con la password real
    define('APP_BASE', '');                     // sin subfolder en producción
} else {
    // ── LOCAL (WAMP) ────────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'canetto');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('APP_BASE', '/canetto');             // subfolder local
}

define('MAPS_API_KEY', '');
