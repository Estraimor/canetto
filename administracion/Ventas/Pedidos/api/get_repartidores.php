<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');
try {
    $pdo  = Conexion::conectar();
    $rows = $pdo->query("
        SELECT u.idusuario AS idrepartidor, u.nombre, u.apellido, u.celular
        FROM usuario u
        INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
        INNER JOIN roles r ON r.idroles = ur.roles_idroles
        WHERE r.nombre = 'Repartidor' AND u.activo = 1
        ORDER BY u.nombre ASC
    ")->fetchAll();
    echo json_encode($rows);
} catch (Throwable $e) {
    echo json_encode([]);
}
