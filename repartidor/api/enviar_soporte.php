<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$repId     = $_SESSION['repartidor_id']     ?? null;
$repNombre = $_SESSION['repartidor_nombre'] ?? 'Repartidor';

$data    = json_decode(file_get_contents('php://input'), true) ?: [];
$tipo    = trim($data['tipo']    ?? 'Consulta general');
$detalle = trim($data['detalle'] ?? '');

if (!$detalle) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'El detalle es requerido']); exit;
}

$remitente = htmlspecialchars($repNombre);
$tipoSafe  = htmlspecialchars($tipo);
$detalleSafe = nl2br(htmlspecialchars($detalle));

$contenido = <<<HTML
<h2 style="margin:0 0 8px;font-size:20px;color:#2d2d2d;font-weight:700;">
  Nuevo mensaje de soporte — Repartidor
</h2>
<div style="background:#faf3f5;border-radius:10px;padding:16px 18px;margin-bottom:14px;">
  <p style="margin:0 0 4px;font-size:12px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Repartidor</p>
  <p style="margin:0;color:#444;font-size:14px;"><strong>{$remitente}</strong></p>
</div>
<div style="background:#faf3f5;border-radius:10px;padding:16px 18px;margin-bottom:14px;">
  <p style="margin:0 0 4px;font-size:12px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Tipo</p>
  <p style="margin:0;color:#444;font-size:14px;">{$tipoSafe}</p>
</div>
<div style="background:#faf3f5;border-radius:10px;padding:16px 18px;">
  <p style="margin:0 0 4px;font-size:12px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Detalle</p>
  <p style="margin:0;color:#444;font-size:14px;line-height:1.7;">{$detalleSafe}</p>
</div>
HTML;

$ok = enviarEmail(
    MAIL_SUPPORT,
    'Canetto Soporte',
    "[Soporte Repartidor] $tipo — $repNombre",
    'Soporte Repartidor',
    $contenido
);

ob_end_clean();
echo json_encode(['success' => $ok, 'message' => $ok ? 'Enviado' : 'Error al enviar email']);
