<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$id = (int)($_POST['id'] ?? $_POST['idoferta'] ?? 0);

if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID inválido.']); exit; }

$pdo = Conexion::conectar();

// Get image filename before deleting
$row = $pdo->prepare("SELECT titulo, imagen FROM oferta WHERE idoferta=?");
$row->execute([$id]);
$oferta = $row->fetch();

if (!$oferta) { echo json_encode(['ok'=>false,'msg'=>'Oferta no encontrada.']); exit; }

$pdo->prepare("DELETE FROM oferta WHERE idoferta=?")->execute([$id]);

// Delete image file
if (!empty($oferta['imagen'])) {
    $imgPath = __DIR__ . '/../../img/ofertas/' . $oferta['imagen'];
    if (file_exists($imgPath)) @unlink($imgPath);
}

audit($pdo, 'eliminar', 'ofertas', 'Eliminó oferta: ' . $oferta['titulo']);

echo json_encode(['ok'=>true]);
