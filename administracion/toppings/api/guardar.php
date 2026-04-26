<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }
header('Content-Type: application/json; charset=utf-8');

$data   = json_decode(file_get_contents('php://input'), true);
$id     = isset($data['idtoppings']) ? (int)$data['idtoppings'] : 0;
$nombre = trim($data['nombre'] ?? '');
$precio = isset($data['precio']) ? (float)$data['precio'] : 0;
$activo = isset($data['activo']) ? (int)$data['activo'] : 1;

if (!$nombre) { echo json_encode(['ok'=>false,'msg'=>'El nombre es obligatorio']); exit; }
if ($precio < 0) { echo json_encode(['ok'=>false,'msg'=>'El precio no puede ser negativo']); exit; }

$pdo = Conexion::conectar();

if ($id > 0) {
    $pdo->prepare("UPDATE toppings SET nombre=?, precio=?, activo=? WHERE idtoppings=?")->execute([$nombre,$precio,$activo,$id]);
    echo json_encode(['ok'=>true,'id'=>$id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO toppings (nombre,precio,activo) VALUES (?,?,?)");
    $stmt->execute([$nombre,$precio,$activo]);
    echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
}
