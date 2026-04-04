<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$data = json_decode(file_get_contents('php://input'), true);
$pdo  = Conexion::conectar();

$usuario_id = (int)($data['usuario_id'] ?? 0);
$roles_ids  = array_map('intval', $data['roles_ids'] ?? []);

if (!$usuario_id) { echo json_encode(['ok' => false, 'msg' => 'Usuario inválido.']); exit; }

// Verificar que el usuario existe
$check = $pdo->prepare("SELECT nombre FROM usuario WHERE idusuario=?");
$check->execute([$usuario_id]);
$uNombre = $check->fetchColumn();
if (!$uNombre) { echo json_encode(['ok' => false, 'msg' => 'Usuario no encontrado.']); exit; }

// Reemplazar asignaciones
$pdo->beginTransaction();
try {
    $pdo->prepare("DELETE FROM usuarios_roles WHERE usuario_idusuario=?")->execute([$usuario_id]);
    if (!empty($roles_ids)) {
        $stmt = $pdo->prepare("INSERT INTO usuarios_roles (usuario_idusuario, roles_idroles) VALUES (?, ?)");
        foreach ($roles_ids as $rid) {
            $stmt->execute([$usuario_id, $rid]);
        }
    }
    $pdo->commit();
    _audit($pdo, 'editar', 'roles', 'Actualizó roles de usuario: ' . $uNombre . ' (' . count($roles_ids) . ' rol/es)');
    echo json_encode(['ok' => true]);
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar: ' . $e->getMessage()]);
}

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
