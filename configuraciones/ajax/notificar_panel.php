<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/web_push.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']); exit;
}

$idoferta = intval($_POST['idoferta'] ?? 0);
if (!$idoferta) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']); exit;
}

try {
    $pdo = Conexion::conectar();

    $panel = $pdo->prepare("SELECT titulo, descripcion, tipo_panel FROM oferta WHERE idoferta = ?");
    $panel->execute([$idoferta]);
    $p = $panel->fetch(\PDO::FETCH_ASSOC);

    if (!$p) {
        echo json_encode(['ok' => false, 'msg' => 'Panel no encontrado']); exit;
    }

    $titulo = '📢 Nuevo panel: ' . $p['titulo'];
    $cuerpo = $p['descripcion'] ?: ('¡Mirá las novedades en la tienda!');
    $url    = '/canetto/tienda/';

    $result = push_enviar_a_todos($pdo, $titulo, $cuerpo, $url);

    echo json_encode([
        'ok'     => true,
        'sent'   => $result['sent'],
        'failed' => $result['failed'],
    ]);
} catch (\Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
