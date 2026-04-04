<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$data = json_decode(file_get_contents('php://input'), true);
$pdo  = Conexion::conectar();

$id = (int)($data['idroles'] ?? 0);
if (!$id) { echo json_encode(['ok' => false, 'msg' => 'ID inválido.']); exit; }

$nom = $pdo->prepare("SELECT nombre FROM roles WHERE idroles=?");
$nom->execute([$id]);
$nombre = $nom->fetchColumn();

// Quitar el rol de todos los usuarios antes de eliminar
$pdo->prepare("DELETE FROM usuarios_roles WHERE roles_idroles=?")->execute([$id]);
$pdo->prepare("DELETE FROM roles WHERE idroles=?")->execute([$id]);
_audit($pdo, 'eliminar', 'roles', 'Eliminó rol: ' . $nombre);
echo json_encode(['ok' => true]);

function _audit($pdo, $accion, $modulo, $desc) {
    $uid = $_SESSION['usuario_id'] ?? null;
    try {
        $u = $uid ? $pdo->prepare("SELECT CONCAT(nombre,' ',COALESCE(apellido,'')) FROM usuario WHERE idusuario=?") : null;
        $unombre = 'Sistema';
        if ($u) { $u->execute([$uid]); $unombre = trim($u->fetchColumn() ?: 'Sistema'); }
        $pdo->exec("CREATE TABLE IF NOT EXISTS auditoria (idauditoria INT AUTO_INCREMENT PRIMARY KEY,usuario_id INT,usuario_nombre VARCHAR(100),accion VARCHAR(100) NOT NULL,modulo VARCHAR(50),descripcion TEXT,ip VARCHAR(50),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $s = $pdo->prepare("INSERT INTO auditoria (usuario_id,usuario_nombre,accion,modulo,descripcion,ip) VALUES (?,?,?,?,?,?)");
        $s->execute([$uid, $unombre, $accion, $modulo, $desc, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
}
