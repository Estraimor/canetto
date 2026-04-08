<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
unset($_SESSION['repartidor_id'], $_SESSION['repartidor_nombre']);
echo json_encode(['success' => true]);
