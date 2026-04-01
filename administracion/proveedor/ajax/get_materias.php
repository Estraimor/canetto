<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

header('Content-Type: application/json');

try {
    // 🔥 Crear conexión correctamente
    $pdo = Conexion::conectar();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("
        SELECT 
            idmateria_prima, 
            nombre, 
            stock_actual, 
            stock_minimo 
        FROM materia_prima 
        ORDER BY nombre ASC
    ");

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}