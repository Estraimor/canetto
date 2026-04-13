<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$pdo = Conexion::conectar();


$rows = $pdo->query("SELECT idsucursal, nombre, direccion, ciudad, provincia, telefono, email, latitud, longitud, activo FROM sucursal ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
