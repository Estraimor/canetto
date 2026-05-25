<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$userId = $_SESSION['usuario_id'] ?? null;
if (!$userId) { echo json_encode(['ok' => false, 'msg' => 'No autorizado']); exit; }

$file = $_FILES['avatar'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'msg' => 'No se recibió el archivo']); exit;
}

$maxBytes  = 2 * 1024 * 1024; // 2 MB
$mimeAllow = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$extMap    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

if ($file['size'] > $maxBytes) {
    echo json_encode(['ok' => false, 'msg' => 'El archivo supera los 2 MB']); exit;
}

$mime = mime_content_type($file['tmp_name']);

if (!in_array($mime, $mimeAllow, true)) {
    echo json_encode(['ok' => false, 'msg' => 'Solo se permiten imágenes JPG, PNG, WebP o GIF']); exit;
}

$dir = __DIR__ . '/../../img/avatars/';
if (!is_dir($dir)) mkdir($dir, 0775, true);

try {
    $pdo = Conexion::conectar();

    // Borrar avatar anterior si era un archivo local (no URL de Google)
    $old = $pdo->prepare("SELECT avatar FROM usuario WHERE idusuario = ?");
    $old->execute([$userId]);
    $oldPath = $old->fetchColumn();
    if ($oldPath && !str_starts_with($oldPath, 'http')) {
        $oldFile = __DIR__ . '/../../' . ltrim($oldPath, '/');
        if (file_exists($oldFile)) @unlink($oldFile);
    }

    $ext      = $extMap[$mime];
    $filename = 'avatar_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['ok' => false, 'msg' => 'Error al guardar el archivo']); exit;
    }

    $avatarPath = 'img/avatars/' . $filename;
    $pdo->prepare("UPDATE usuario SET avatar = ? WHERE idusuario = ?")->execute([$avatarPath, $userId]);
    $_SESSION['avatar'] = $avatarPath;

    echo json_encode(['ok' => true, 'avatar_url' => URL_ASSETS . '/' . $avatarPath . '?v=' . time()]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}
