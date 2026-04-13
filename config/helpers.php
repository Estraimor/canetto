<?php
/**
 * config/helpers.php
 * Funciones utilitarias compartidas por toda la app.
 */

if (!defined('APP_BOOT')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

// ── Detectar entorno ────────────────────────────────────────────────────────
$_h       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_isLocal = in_array($_h, ['localhost', '127.0.0.1'], true);

// ── Configurar cookie de sesión (antes de session_start) ───────────────────
if (session_status() === PHP_SESSION_NONE) {
    $_isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure',   $_isSecure ? '1' : '0');
    // Compartir sesión entre subdominios (app., repartidor., administracion.)
    ini_set('session.cookie_domain',   $_isLocal ? '' : '.canettocookies.com');
    unset($_isSecure);
}

// ── Constantes de URL por app ───────────────────────────────────────────────
// Local: todo vive bajo /canetto  |  Producción: cada app en su subdominio
define('URL_LOGIN',      $_isLocal ? 'http://localhost/canetto/login'               : 'https://canettocookies.com/login');
define('URL_TIENDA',     $_isLocal ? 'http://localhost/canetto/tienda'              : 'https://app.canettocookies.com');
define('URL_ADMIN',      $_isLocal ? 'http://localhost/canetto/administracion'      : 'https://administracion.canettocookies.com/administracion');
define('URL_REPARTIDOR', $_isLocal ? 'http://localhost/canetto/repartidor'          : 'https://repartidor.canettocookies.com');
// URL base para assets estáticos compartidos (img/, configuraciones/cfg.css, etc.)
define('URL_ASSETS',     $_isLocal ? 'http://localhost/canetto'                     : 'https://canettocookies.com');

// ── base(): prefijo de ruta para links/assets dentro del mismo app ──────────
// Local → '/canetto'  |  Producción → ''
function base(): string
{
    return defined('APP_BASE') ? APP_BASE : '';
}

/**
 * Redirige a una URL absoluta o ruta relativa.
 * Si la ruta comienza con 'http' se usa directamente (cross-app).
 * Si no, se antepone base() para rutas internas del mismo subdominio.
 */
function redirect(string $path): never
{
    if (str_starts_with($path, 'http')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . base() . $path);
    }
    exit;
}
