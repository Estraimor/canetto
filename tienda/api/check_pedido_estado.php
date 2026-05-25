<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$idVenta   = (int)($_GET['id']  ?? 0);
// Usar sesión primero; si no hay sesión, aceptar uid del GET (viene de mp_retorno.php)
$clienteId = (int)($_SESSION['tienda_cliente_id'] ?? $_GET['uid'] ?? 0);

if (!$idVenta) {
    echo json_encode(['ok' => false]);
    exit;
}

try {
    $pdo  = Conexion::conectar();

    // Si tenemos clienteId verificar que el pedido le pertenece
    if ($clienteId) {
        $stmt = $pdo->prepare("
            SELECT v.idventas, v.estado_venta_idestado_venta AS estado_id,
                   ev.nombre AS estado_nombre
            FROM ventas v
            LEFT JOIN estado_venta ev ON ev.idestado_venta = v.estado_venta_idestado_venta
            WHERE v.idventas = ? AND v.usuario_idusuario = ?
        ");
        $stmt->execute([$idVenta, $clienteId]);
    } else {
        // Sin sesión: solo retornar el estado (sin datos sensibles)
        $stmt = $pdo->prepare("
            SELECT v.idventas, v.estado_venta_idestado_venta AS estado_id,
                   ev.nombre AS estado_nombre
            FROM ventas v
            LEFT JOIN estado_venta ev ON ev.idestado_venta = v.estado_venta_idestado_venta
            WHERE v.idventas = ?
        ");
        $stmt->execute([$idVenta]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) { echo json_encode(['ok' => false]); exit; }

    echo json_encode([
        'ok'           => true,
        'estado_id'    => (int)$row['estado_id'],
        'estado_nombre'=> $row['estado_nombre'],
    ]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false]);
}
