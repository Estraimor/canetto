<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['idtoppings'] ?? 0);
if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

$pdo = Conexion::conectar();
$pdo->prepare("DELETE FROM producto_toppings WHERE toppings_idtoppings=?")->execute([$id]);
$pdo->prepare("DELETE FROM toppings WHERE idtoppings=?")->execute([$id]);
echo json_encode(['ok'=>true]);
