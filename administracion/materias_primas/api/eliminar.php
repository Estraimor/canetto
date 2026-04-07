<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

try {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        echo json_encode(['success' => false]);
        exit;
    }

    $pdo = Conexion::conectar();

    // Obtener nombre antes de desactivar
    $stmtNom = $pdo->prepare("SELECT nombre FROM materia_prima WHERE idmateria_prima = ?");
    $stmtNom->execute([$id]);
    $nombre = $stmtNom->fetchColumn() ?: "ID {$id}";

    // Verificar si está en uso en alguna receta
    $stmtUso = $pdo->prepare("
        SELECT r.nombre
        FROM receta_ingredientes ri
        JOIN recetas r ON r.idrecetas = ri.recetas_idrecetas
        WHERE ri.materia_prima_idmateria_prima = ?
        GROUP BY r.idrecetas, r.nombre
        ORDER BY r.nombre ASC
    ");
    $stmtUso->execute([$id]);
    $recetas = $stmtUso->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($recetas)) {
        echo json_encode([
            'success' => false,
            'en_uso'  => true,
            'recetas' => $recetas
        ]);
        exit;
    }

    $pdo->prepare("UPDATE materia_prima SET activo = 0, updated_at = NOW() WHERE idmateria_prima = ?")
        ->execute([$id]);

    audit($pdo, 'eliminar', 'materias_primas',
        "Desactivó materia prima: '{$nombre}' (ID: {$id})"
    );

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
