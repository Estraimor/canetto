<?php
// config/conexion.php

declare(strict_types=1);

if (!defined('APP_BOOT')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

final class Conexion {

    public static function conectar(): PDO {

        // 🔥 PRIMERO seguridad (tron). SI FALLA, corta y nunca llega acá.
        require_once __DIR__ . '/tron.php';

        // Credenciales: ideal leerlas de env.php o variables de entorno
        require_once __DIR__ . '/env.php';

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
}