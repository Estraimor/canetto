<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }
header('Content-Type: application/json; charset=utf-8');

$idProducto = (int)($_GET['id'] ?? 0);
if (!$idProducto) { echo json_encode([]); exit; }

$pdo  = Conexion::conectar();
$rows = $pdo->prepare("SELECT toppings_idtoppings AS idtoppings FROM producto_toppings WHERE productos_idproductos=?");
$rows->execute([$idProducto]);
echo json_encode(array_column($rows->fetchAll(PDO::FETCH_ASSOC), 'idtoppings'));
