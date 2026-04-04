<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id'])) { http_response_code(403); exit; }

header('Content-Type: application/json; charset=utf-8');
$data   = json_decode(file_get_contents('php://input'), true);
$pdo    = Conexion::conectar();

$id     = isset($data['idmetodo_pago']) ? (int)$data['idmetodo_pago'] : 0;
$nombre = trim($data['nombre'] ?? '');

if (!$nombre) { echo json_encode(['ok' => false, 'msg' => 'El nombre es requerido.']); exit; }

if ($id) {
    $stmt = $pdo->prepare("UPDATE metodo_pago SET nombre=? WHERE idmetodo_pago=?");
    $stmt->execute([$nombre, $id]);
    _audit($pdo, 'editar', 'metodos_pago', 'Editó método de pago: ' . $nombre);
} else {
    $stmt = $pdo->prepare("INSERT INTO metodo_pago (nombre) VALUES (?)");
    $stmt->execute([$nombre]);
    _audit($pdo, 'crear', 'metodos_pago', 'Creó método de pago: ' . $nombre);
}

echo json_encode(['ok' => true]);

function _audit($pdo, $accion, $modulo, $desc) {
    $uid   = $_SESSION['usuario_id'] ?? null;
    try {
        $u = $uid ? $pdo->prepare("SELECT CONCAT(nombre,' ',COALESCE(apellido,'')) FROM usuario WHERE idusuario=?") : null;
        $unombre = 'Sistema';
        if ($u) { $u->execute([$uid]); $unombre = trim($u->fetchColumn() ?: 'Sistema'); }
        $pdo->exec("CREATE TABLE IF NOT EXISTS auditoria (idauditoria INT AUTO_INCREMENT PRIMARY KEY,usuario_id INT,usuario_nombre VARCHAR(100),accion VARCHAR(100) NOT NULL,modulo VARCHAR(50),descripcion TEXT,ip VARCHAR(50),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $s = $pdo->prepare("INSERT INTO auditoria (usuario_id,usuario_nombre,accion,modulo,descripcion,ip) VALUES (?,?,?,?,?,?)");
        $s->execute([$uid, $unombre, $accion, $modulo, $desc, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
}
