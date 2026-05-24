<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo   = Conexion::conectar();
    $q     = trim($_GET['q'] ?? '');
    $limit = $q === '' ? 5 : 60;

    $sql = "
        SELECT DISTINCT
               u.idusuario AS id,
               TRIM(CONCAT(u.nombre, ' ', COALESCE(u.apellido,''))) AS nombre,
               u.celular
        FROM usuario u
        INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
        WHERE u.activo = 1
          AND u.celular IS NOT NULL
          AND u.celular != ''
          AND ur.roles_idroles = 2
    ";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (u.nombre LIKE :q OR u.apellido LIKE :q OR u.celular LIKE :q)";
        $params[':q'] = "%{$q}%";
    }
    $sql .= " ORDER BY u.nombre ASC LIMIT {$limit}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'clientes' => $rows, 'inicial' => $q === '']);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'clientes' => [], 'error' => $e->getMessage()]);
}
