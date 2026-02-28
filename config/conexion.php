<?php
declare(strict_types=1);

if (!defined('APP_BOOT')) {
    exit;
}

require_once __DIR__ . '/env.php';

final class Conexion {

    public static function conectar(): PDO {

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
}