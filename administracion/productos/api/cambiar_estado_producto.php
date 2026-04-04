<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

try {
    $pdo  = Conexion::conectar();
    $data = json_decode(file_get_contents("php://input"), true);

    $id     = $data['id']     ?? null;
    $activo = $data['activo'] ?? null;

    if (!$id) {
        echo json_encode(["status" => "error", "mensaje" => "ID inválido"]);
        exit;
    }

    // Obtener nombre del producto para auditoría
    $stmtNom = $pdo->prepare("SELECT nombre FROM productos WHERE idproductos = ?");
    $stmtNom->execute([$id]);
    $nombreProducto = $stmtNom->fetchColumn() ?: "ID {$id}";

    $pdo->prepare("UPDATE productos SET activo = ? WHERE idproductos = ?")->execute([$activo, $id]);

    $estadoTexto = $activo ? 'activó' : 'desactivó';
    audit($pdo, 'editar', 'productos',
        ucfirst($estadoTexto) . " producto: {$nombreProducto} (ID: {$id})"
    );

    echo json_encode(["status" => "ok"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "mensaje" => $e->getMessage()]);
}
