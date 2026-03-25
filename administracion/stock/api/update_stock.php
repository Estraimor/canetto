<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';

$pdo = Conexion::conectar();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];
$congelado = $data['congelado'];
$hecho = $data['hecho'];
$minCongelado = $data['minCongelado'];
$minHecho = $data['minHecho'];

try {

    /* =========================
    CONGELADO
    ========================= */
    $stmt = $pdo->prepare("
        UPDATE stock_productos
        SET stock_actual = ?, stock_minimo = ?, updated_at = NOW()
        WHERE productos_idproductos = ?
        AND tipo_stock = 'CONGELADO'
    ");
    $stmt->execute([$congelado, $minCongelado, $id]);

    /* =========================
    HECHO
    ========================= */
    $stmt = $pdo->prepare("
        UPDATE stock_productos
        SET stock_actual = ?, stock_minimo = ?, updated_at = NOW()
        WHERE productos_idproductos = ?
        AND tipo_stock = 'HECHO'
    ");
    $stmt->execute([$hecho, $minHecho, $id]);

    echo json_encode(["ok" => true]);

} catch (Exception $e) {

    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ]);

}