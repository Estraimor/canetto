<?php
declare(strict_types=1);
define('APP_BOOT', true);
require_once '../../../config/conexion.php';
require_once '../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

try {
    $pdo = Conexion::conectar();

    $pdo->exec("CREATE TABLE IF NOT EXISTS incidencias (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        area         VARCHAR(50)  NOT NULL,
        tipo         VARCHAR(80)  NOT NULL,
        descripcion  TEXT         NOT NULL,
        prioridad    VARCHAR(20)  NOT NULL DEFAULT 'alta',
        estado       VARCHAR(20)  NOT NULL DEFAULT 'abierta',
        usuario_id   INT          NULL,
        usuario_nombre VARCHAR(120) NULL,
        created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $area       = trim($_POST['area']        ?? '');
    $tipo       = trim($_POST['tipo']        ?? '');
    $descripcion= trim($_POST['descripcion'] ?? '');
    $prioridad  = in_array($_POST['prioridad'] ?? '', ['critica','alta','media']) ? $_POST['prioridad'] : 'alta';

    if (!$area || !$descripcion) {
        echo json_encode(['success' => false, 'message' => 'Área y descripción son requeridos']);
        exit;
    }

    $usuario_id     = $_SESSION['usuario_id']     ?? null;
    $usuario_nombre = ($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '');
    $usuario_nombre = trim($usuario_nombre) ?: 'Sistema';

    $pdo->prepare("
        INSERT INTO incidencias (area, tipo, descripcion, prioridad, usuario_id, usuario_nombre)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$area, $tipo ?: 'Incidencia de producción', $descripcion, $prioridad, $usuario_id, $usuario_nombre]);

    $id = $pdo->lastInsertId();

    // Generar notificación admin
    $pdo->prepare("
        INSERT INTO notificaciones_admin (tipo, titulo, descripcion, link)
        VALUES ('incidencia', :titulo, :desc, :link)
    ")->execute([
        ':titulo' => "🚨 Incidencia en {$area}: {$tipo}",
        ':desc'   => mb_substr($descripcion, 0, 200),
        ':link'   => URL_ADMIN . '/incidencias/index.php',
    ]);

    audit($pdo, 'crear', 'incidencias', "Nueva incidencia #{$id} en {$area} — {$tipo}");

    echo json_encode(['success' => true, 'id' => (int)$id]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
