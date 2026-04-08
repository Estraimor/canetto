<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json');
$pdo  = Conexion::conectar();
$rows = $pdo->query("
    SELECT u.idusuario, u.nombre, u.apellido, u.celular, u.email, u.activo, u.created_at
    FROM usuario u
    INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
    INNER JOIN roles r ON r.idroles = ur.roles_idroles
    WHERE r.nombre = 'Repartidor'
    ORDER BY u.nombre ASC
")->fetchAll();
echo json_encode($rows);
