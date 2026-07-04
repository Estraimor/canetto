<?php
/**
 * El repartidor solicita cancelar un pedido en camino, indicando el motivo.
 * No cancela directamente — queda pendiente de revisión por administración.
 */
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId     = $_SESSION['repartidor_id']     ?? null;
$repNombre = $_SESSION['repartidor_nombre'] ?? 'Repartidor';
if (!$repId) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No autenticado']); exit;
}

$input   = json_decode(file_get_contents('php://input'), true) ?: [];
$idVenta = (int)($input['id_venta'] ?? 0);
$motivo  = trim($input['motivo']  ?? '');
$detalle = trim($input['detalle'] ?? '');

$motivosValidos = [
    'El cliente no sale / no atiende',
    'No encuentro la dirección',
    'Zona insegura / barrio peligroso',
    'Lugar o personas sospechosas',
    'Calle cortada o inaccesible',
    'El cliente pidió cancelar',
    'Problema con el vehículo',
    'Otro motivo',
];

if (!$idVenta || !in_array($motivo, $motivosValidos, true)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
}

if ($motivo === 'Otro motivo' && $detalle === '') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Contanos brevemente el motivo']); exit;
}

try {
    $pdo = Conexion::conectar();

    foreach ([
        "ALTER TABLE ventas ADD COLUMN cancelacion_solicitada TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE ventas ADD COLUMN cancelacion_motivo VARCHAR(60) NULL",
        "ALTER TABLE ventas ADD COLUMN cancelacion_detalle TEXT NULL",
        "ALTER TABLE ventas ADD COLUMN cancelacion_solicitada_at DATETIME NULL",
    ] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

    $chk = $pdo->prepare("
        SELECT idventas FROM ventas
        WHERE idventas = ? AND repartidor_idusuario = ? AND estado_venta_idestado_venta = 3
    ");
    $chk->execute([$idVenta, $repId]);
    if (!$chk->fetch()) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado o ya no está en camino']); exit;
    }

    $pdo->prepare("
        UPDATE ventas
        SET cancelacion_solicitada = 1,
            cancelacion_motivo = :motivo,
            cancelacion_detalle = :detalle,
            cancelacion_solicitada_at = NOW()
        WHERE idventas = :id
    ")->execute([':motivo' => $motivo, ':detalle' => $detalle, ':id' => $idVenta]);

    // Notificar a administración
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificaciones_admin (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        tipo          VARCHAR(40)  NOT NULL,
        titulo        VARCHAR(200) NOT NULL,
        descripcion   VARCHAR(400) NULL,
        datos_json    TEXT         NULL,
        link          VARCHAR(500) NULL,
        referencia_id INT NULL,
        leida         TINYINT(1)   NOT NULL DEFAULT 0,
        created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $datosJson = json_encode([
        'repartidor' => $repNombre,
        'motivo'     => $motivo,
        'detalle'    => $detalle,
    ], JSON_UNESCAPED_UNICODE);

    $pdo->prepare("
        INSERT INTO notificaciones_admin (tipo, titulo, descripcion, datos_json, link, referencia_id)
        VALUES ('cancelacion_pedido', :titulo, :desc, :datos, :link, :ref)
    ")->execute([
        ':titulo' => "🚨 Solicitud de cancelación — Pedido #{$idVenta}",
        ':desc'   => "{$repNombre}: {$motivo}" . ($detalle ? " — {$detalle}" : ''),
        ':datos'  => $datosJson,
        ':link'   => URL_ADMIN . '/Ventas/Pedidos/index.php',
        ':ref'    => $idVenta,
    ]);

    audit($pdo, 'editar', 'pedidos',
        "Repartidor {$repNombre} solicitó cancelar el pedido #{$idVenta}: {$motivo}" . ($detalle ? " — {$detalle}" : '')
    );

    ob_end_clean();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
