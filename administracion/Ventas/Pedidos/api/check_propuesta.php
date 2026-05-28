<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$id_venta = intval($_GET['id_venta'] ?? 0);
if (!$id_venta) { ob_end_clean(); echo json_encode(['status' => 'error']); exit; }

try {
    $pdo = Conexion::conectar();

    $stmt = $pdo->prepare("
        SELECT
            v.repartidor_idusuario,
            v.repartidor_pendiente_idusuario,
            TRIM(CONCAT(r.nombre, ' ', COALESCE(r.apellido,'')))  AS repartidor_nombre,
            TRIM(CONCAT(rp.nombre,' ', COALESCE(rp.apellido,''))) AS pendiente_nombre
        FROM ventas v
        LEFT JOIN usuario r  ON r.idusuario  = v.repartidor_idusuario
        LEFT JOIN usuario rp ON rp.idusuario = v.repartidor_pendiente_idusuario
        WHERE v.idventas = ?
    ");
    $stmt->execute([$id_venta]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) { ob_end_clean(); echo json_encode(['status' => 'error']); exit; }

    if ($row['repartidor_idusuario']) {
        ob_end_clean();
        echo json_encode(['status' => 'aceptado', 'repartidor' => trim($row['repartidor_nombre'])]);
    } elseif ($row['repartidor_pendiente_idusuario']) {
        ob_end_clean();
        echo json_encode([
            'status'   => 'esperando',
            'repartidor' => trim($row['pendiente_nombre']),
            'pendiente_id' => (int)$row['repartidor_pendiente_idusuario'],
        ]);
    } else {
        // rechazado o liberado — listo para proponer al siguiente
        ob_end_clean();
        echo json_encode(['status' => 'libre']);
    }

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
