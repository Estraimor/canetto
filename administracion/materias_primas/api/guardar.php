<?php
declare(strict_types=1);

define('APP_BOOT', true);
require_once '../../../config/conexion.php';

header('Content-Type: application/json');

try {

    $pdo = Conexion::conectar();

    // =========================
    // Captura y validación
    // =========================
    $id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre       = trim($_POST['nombre'] ?? '');
    $unidad       = filter_input(INPUT_POST, 'unidad', FILTER_VALIDATE_INT);
    $stock_actual = isset($_POST['stock_actual']) ? (float) $_POST['stock_actual'] : 0.0;
    $stock_minimo = isset($_POST['stock_minimo']) ? (float) $_POST['stock_minimo'] : 0.0;
    $activo       = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;
    $nota         = trim($_POST['nota'] ?? '');

    if ($nombre === '' || !is_int($unidad) || $unidad <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos inválidos'
        ]);
        exit;
    }

    // =========================
    // UPDATE
    // =========================
    if ($id) {

        $sql = "UPDATE materia_prima SET
                    nombre = ?,
                    unidad_medida_idunidad_medida = ?,
                    stock_actual = ?,
                    stock_minimo = ?,
                    activo = ?,
                    nota = ?,
                    updated_at = NOW()
                WHERE idmateria_prima = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre,
            $unidad,
            $stock_actual,
            $stock_minimo,
            $activo,
            $nota,
            $id
        ]);

    } 
    // =========================
    // INSERT
    // =========================
    else {

        $sql = "INSERT INTO materia_prima
                (nombre, unidad_medida_idunidad_medida, stock_actual, stock_minimo, activo, nota, created_at)
                VALUES (?,?,?,?,?,?,NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre,
            $unidad,
            $stock_actual,
            $stock_minimo,
            $activo,
            $nota
        ]);
    }

    echo json_encode(['success' => true]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}