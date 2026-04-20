<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

$rolesPermitidos = ['admin', 'administrador', 'administracion'];
if (!isset($_SESSION['usuario_id']) || !in_array(strtolower($_SESSION['rol'] ?? ''), $rolesPermitidos, true)) {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']); exit;
}

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$tarifas = $input['tarifas'] ?? [];

if (empty($tarifas)) {
    echo json_encode(['ok' => false, 'msg' => 'Sin tarifas']); exit;
}

try {
    $pdo = Conexion::conectar();
    $pdo->exec("CREATE TABLE IF NOT EXISTS tarifas_envio (
        id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        km_desde    DECIMAL(5,1) NOT NULL DEFAULT 0,
        km_hasta    DECIMAL(5,1) NOT NULL DEFAULT 5,
        precio      DECIMAL(10,2) NOT NULL DEFAULT 0,
        descripcion VARCHAR(100) NULL,
        activo      TINYINT(1) NOT NULL DEFAULT 1,
        updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->beginTransaction();

    // Obtener IDs actuales en DB
    $idsActuales = array_column($pdo->query("SELECT id FROM tarifas_envio")->fetchAll(PDO::FETCH_ASSOC), 'id');
    $idsEnviados = array_filter(array_column($tarifas, 'id'), fn($id) => $id !== 'new' && is_numeric($id));

    // Eliminar las que ya no están en el formulario
    foreach ($idsActuales as $idDb) {
        if (!in_array((string)$idDb, array_map('strval', $idsEnviados))) {
            $pdo->prepare("DELETE FROM tarifas_envio WHERE id=?")->execute([$idDb]);
        }
    }

    $stmtUpd = $pdo->prepare("UPDATE tarifas_envio SET km_desde=?, km_hasta=?, precio=?, descripcion=?, updated_at=NOW() WHERE id=?");
    $stmtIns = $pdo->prepare("INSERT INTO tarifas_envio (km_desde, km_hasta, precio, descripcion) VALUES (?,?,?,?)");

    foreach ($tarifas as $t) {
        $desde = max(0, (float)($t['km_desde'] ?? 0));
        $hasta = max($desde + 0.1, (float)($t['km_hasta'] ?? 999));
        $precio = max(0, (float)($t['precio'] ?? 0));
        $desc   = trim($t['descripcion'] ?? '');

        if ($t['id'] === 'new' || !is_numeric($t['id'])) {
            $stmtIns->execute([$desde, $hasta, $precio, $desc ?: null]);
        } else {
            $stmtUpd->execute([$desde, $hasta, $precio, $desc ?: null, (int)$t['id']]);
        }
    }

    $pdo->commit();
    echo json_encode(['ok' => true, 'reload' => true]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
