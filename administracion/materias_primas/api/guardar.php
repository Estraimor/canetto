<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    $id              = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre          = trim($_POST['nombre'] ?? '');
    $unidad          = filter_input(INPUT_POST, 'unidad', FILTER_VALIDATE_INT);
    $stock_actual    = isset($_POST['stock_actual']) ? (float)$_POST['stock_actual'] : 0.0;
    $stock_minimo    = isset($_POST['stock_minimo']) ? (float)$_POST['stock_minimo'] : 0.0;
    $activo          = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
    $nota            = trim($_POST['nota'] ?? '');
    $peso_unitario_g = isset($_POST['peso_unitario_g']) && $_POST['peso_unitario_g'] !== ''
                        ? (float)$_POST['peso_unitario_g']
                        : null;

    if ($nombre === '' || !is_int($unidad) || $unidad <= 0) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Obtener nombre de unidad para auditoría
    $stmtUM = $pdo->prepare("SELECT abreviatura FROM unidad_medida WHERE idunidad_medida = ?");
    $stmtUM->execute([$unidad]);
    $abrevUM = $stmtUM->fetchColumn() ?: '';

    if ($id) {
        $pdo->prepare("
            UPDATE materia_prima SET
                nombre = ?, unidad_medida_idunidad_medida = ?,
                stock_actual = ?, stock_minimo = ?,
                activo = ?, nota = ?, peso_unitario_g = ?, updated_at = NOW()
            WHERE idmateria_prima = ?
        ")->execute([$nombre, $unidad, $stock_actual, $stock_minimo, $activo, $nota, $peso_unitario_g, $id]);

        $pesoLog = $peso_unitario_g !== null ? " | Peso unitario: {$peso_unitario_g} g/{$abrevUM}" : '';
        audit($pdo, 'editar', 'materias_primas',
            "Editó materia prima: '{$nombre}' (ID: {$id})" .
            " | Stock: {$stock_actual} {$abrevUM} (mín: {$stock_minimo})" .
            ($nota ? " | Nota: {$nota}" : '') .
            $pesoLog .
            " | Estado: " . ($activo ? 'activo' : 'inactivo')
        );
    } else {
        $pdo->prepare("
            INSERT INTO materia_prima
            (nombre, unidad_medida_idunidad_medida, stock_actual, stock_minimo, activo, nota, peso_unitario_g, created_at)
            VALUES (?,?,?,?,?,?,?,NOW())
        ")->execute([$nombre, $unidad, $stock_actual, $stock_minimo, $activo, $nota, $peso_unitario_g]);

        $newId = $pdo->lastInsertId();
        $pesoLog = $peso_unitario_g !== null ? " | Peso unitario: {$peso_unitario_g} g/{$abrevUM}" : '';
        audit($pdo, 'crear', 'materias_primas',
            "Creó materia prima: '{$nombre}' (ID: {$newId})" .
            " | Stock inicial: {$stock_actual} {$abrevUM} (mín: {$stock_minimo})" .
            ($nota ? " | Nota: {$nota}" : '') .
            $pesoLog
        );
    }

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
