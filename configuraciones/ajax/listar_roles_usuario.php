<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$pdo = Conexion::conectar();

$sql = "SELECT
    u.idusuario,
    u.nombre,
    u.apellido,
    u.usuario,
    u.activo,
    GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR '||') AS roles_nombres,
    GROUP_CONCAT(r.idroles ORDER BY r.idroles SEPARATOR ',')  AS roles_ids
FROM usuario u
LEFT JOIN usuarios_roles ur ON u.idusuario = ur.usuario_idusuario
LEFT JOIN roles r ON ur.roles_idroles = r.idroles
GROUP BY u.idusuario
ORDER BY u.nombre";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
