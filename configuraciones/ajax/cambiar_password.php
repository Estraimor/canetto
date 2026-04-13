<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$data      = json_decode(file_get_contents('php://input'), true) ?: [];
$idusuario = intval($data['idusuario'] ?? 0);
$password  = trim($data['password'] ?? '');

if (!$idusuario || strlen($password) < 6) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Verificar que el usuario existe
    $chk = $pdo->prepare("SELECT nombre FROM usuario WHERE idusuario = ?");
    $chk->execute([$idusuario]);
    $usuario = $chk->fetchColumn();
    if (!$usuario) {
        echo json_encode(['ok' => false, 'msg' => 'Usuario no encontrado.']); exit;
    }

    $pdo->prepare("UPDATE usuario SET password_hash = ?, updated_at = NOW() WHERE idusuario = ?")
        ->execute([password_hash($password, PASSWORD_DEFAULT), $idusuario]);

    audit($pdo, 'editar', 'usuarios', "Contraseña cambiada para usuario: {$usuario} (ID #{$idusuario})");

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
