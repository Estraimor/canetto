<?php
// config/tron.php — Guardián de sesión para el panel de administración

declare(strict_types=1);

if (!defined('APP_BOOT')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

// Depende de que conexion.php ya esté cargado (define redirect() y URL_LOGIN)
if (!function_exists('redirect') || !defined('URL_LOGIN')) {
    http_response_code(500);
    error_log('[Tron] tron.php cargado sin conexion.php previo.');
    exit('Error de configuración.');
}

// 1) La sesión ya fue iniciada por helpers.php — solo verificamos que esté activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Headers de seguridad (complementan al .htaccess)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// 3) Verificar autenticación
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    redirect(URL_LOGIN . '/login.php');
}

// 4) Verificar que tenga rol de administración
$rolesAdmin = ['admin', 'administrador', 'administracion'];
if (empty($_SESSION['rol']) || !in_array(strtolower($_SESSION['rol']), $rolesAdmin, true)) {
    session_destroy();
    http_response_code(403);
    redirect(URL_LOGIN . '/login.php');
}

// 5) Anti-hijacking: solo User-Agent (la IP se omite porque cambia en móviles con datos)
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (!isset($_SESSION['lock_ua'])) {
    $_SESSION['lock_ua'] = $ua;
}
if ($_SESSION['lock_ua'] !== $ua) {
    session_destroy();
    http_response_code(401);
    redirect(URL_LOGIN . '/login.php');
}

// 6) Timeout por inactividad (1 hora)
$max  = 3600;
$last = $_SESSION['last_seen'] ?? time();
if (time() - $last > $max) {
    session_destroy();
    redirect(URL_LOGIN . '/login.php?timeout=1');
}
$_SESSION['last_seen'] = time();
