<?php
// config/env.php
declare(strict_types=1);

if (!defined('APP_BOOT')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'canetto');
define('DB_USER', 'root');
define('DB_PASS', '');