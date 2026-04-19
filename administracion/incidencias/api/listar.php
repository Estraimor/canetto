<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    $area   = $_GET['area']   ?? '';
    $estado = $_GET['estado'] ?? '';
    $limit  = max(1, min(200, (int)($_GET['limit'] ?? 50)));

    $where = ['1=1'];
    $params = [];
    if ($area)   { $where[] = 'area = ?';   $params[] = $area; }
    if ($estado) { $where[] = 'estado = ?'; $params[] = $estado; }

    $sql = "SELECT * FROM incidencias WHERE " . implode(' AND ', $where) .
           " ORDER BY created_at DESC LIMIT {$limit}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $total  = $pdo->query("SELECT COUNT(*) FROM incidencias WHERE estado='abierta'")->fetchColumn();
    $criticas = $pdo->query("SELECT COUNT(*) FROM incidencias WHERE estado='abierta' AND prioridad='critica'")->fetchColumn();

    echo json_encode([
        'success'         => true,
        'incidencias'     => $rows,
        'total_abiertas'  => (int)$total,
        'total_criticas'  => (int)$criticas,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
