<?php
// api/buscar_cliente.php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (!$q) { echo json_encode([]); exit; }

try {
    $pdo  = Conexion::conectar();
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare(
        "SELECT idusuario, nombre, apellido, dni, celular, email
         FROM usuario
         WHERE CAST(dni AS CHAR) LIKE :q1
            OR nombre   LIKE :q2
            OR apellido LIKE :q3
            OR celular  LIKE :q4
         LIMIT 8"
    );
    $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]);
    echo json_encode($stmt->fetchAll());

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
