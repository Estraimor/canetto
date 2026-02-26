<?php
declare(strict_types=1);
define('APP_BOOT', true);

require_once __DIR__ . '/../../../../config/conexion.php';
header('Content-Type: application/json');

try {

    $pdo = Conexion::conectar();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre'] ?? '');
    $unidad = filter_input(INPUT_POST, 'unidad', FILTER_VALIDATE_INT);
    $stock_actual = (float)($_POST['stock_actual'] ?? 0);
    $stock_minimo = (float)($_POST['stock_minimo'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '' || !$unidad) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        exit;
    }

    if ($id) {

        $sql = "
            UPDATE materia_prima SET
                nombre = ?,
                unidad_medida_idunidad_medida = ?,
                stock_actual = ?,
                stock_minimo = ?,
                activo = ?,
                updated_at = NOW()
            WHERE idmateria_prima = ?
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre,
            $unidad,
            $stock_actual,
            $stock_minimo,
            $activo,
            $id
        ]);

    } else {

        $sql = "
            INSERT INTO materia_prima
            (nombre, unidad_medida_idunidad_medida, stock_actual, stock_minimo, activo, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre,
            $unidad,
            $stock_actual,
            $stock_minimo,
            $activo
        ]);
    }

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}