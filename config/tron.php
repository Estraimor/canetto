<?php
// config/tron.php

declare(strict_types=1);

// Bloquea acceso directo a tron.php (solo se carga desde el sistema)
if (!defined('APP_BOOT')) {
    http_response_code(403);
    exit('Acceso denegado.');
}

// 1) Iniciar sesión seguro
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    // Si usas https, activalo:
    // ini_set('session.cookie_secure', '1');
    session_start();
}

// 2) Headers básicos (reduce vectores comunes)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// 3) Control de autenticación
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header("Location: /canetto/login.php");
    exit;
}

// 4) Anti hijacking por IP + User-Agent (básico pero útil)
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (!isset($_SESSION['lock_ip'])) $_SESSION['lock_ip'] = $ip;
if (!isset($_SESSION['lock_ua'])) $_SESSION['lock_ua'] = $ua;

if ($_SESSION['lock_ip'] !== $ip || $_SESSION['lock_ua'] !== $ua) {
    session_destroy();
    http_response_code(401);
    header("Location: /canetto/login.php");
    exit;
}

// 5) Timeout por inactividad
$max = 1800; // 30 min
$last = $_SESSION['last_seen'] ?? time();

if ((time() - $last) > $max) {
    session_destroy();
    header("Location: /canetto/login.php?timeout=1");
    exit;
}
$_SESSION['last_seen'] = time();

// 6) Bloqueo de acceso por método si querés (opcional)
// if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
//     http_response_code(405); exit;
// }