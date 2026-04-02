<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json');

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {

    $stmt = $pdo->query("
        SELECT
            idusuario,
            nombre,
            apellido,
            dni,
            celular,
            email,
            usuario,
            activo
        FROM usuario
        ORDER BY nombre ASC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        if ($row['activo'] == 1) {
            $row['estado_html'] = '<span class="badge-activo">Activo</span>';
        } else {
            $row['estado_html'] = '<span class="badge-inactivo">Inactivo</span>';
        }
    }
    unset($row);

    echo json_encode($rows);

} catch (Exception $e) {

    echo json_encode([]);

}
