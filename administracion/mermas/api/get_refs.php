<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['error'=>'No autorizado']); exit; }
header('Content-Type: application/json');

try {
    $pdo  = Conexion::conectar();
    $tipo = $_GET['tipo'] ?? 'producto';

    switch ($tipo) {
        case 'materia_prima':
            $rows = $pdo->query("SELECT idmateria_prima AS id, nombre, unidad_medida AS unidad FROM materia_prima ORDER BY nombre")->fetchAll();
            break;
        case 'topping':
            $rows = $pdo->query("SELECT idtoppings AS id, nombre, '' AS unidad FROM toppings ORDER BY nombre")->fetchAll();
            break;
        default:
            $rows = $pdo->query("SELECT idproductos AS id, nombre, '' AS unidad FROM productos WHERE tipo='producto' ORDER BY nombre")->fetchAll();
    }

    echo json_encode(['success' => true, 'items' => $rows]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
