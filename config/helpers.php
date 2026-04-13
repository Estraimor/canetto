<?php
/**
 * config/helpers.php
 * Funciones utilitarias compartidas por toda la app.
 */

if (!defined('APP_BOOT')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

/**
 * Devuelve la URL base ('' en producción, '/canetto' en local).
 */
function base(): string
{
    static $base = null;
    if ($base === null) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = in_array($host, ['localhost', '127.0.0.1'], true) ? '/canetto' : '';
    }
    return $base;
}

/**
 * Redirige a una ruta relativa a la raíz del proyecto.
 * Ejemplo: redirect('/login/login.php')
 */
function redirect(string $path): never
{
    header('Location: ' . base() . $path);
    exit;
}
