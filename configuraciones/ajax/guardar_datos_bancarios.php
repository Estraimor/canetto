<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

// Solo admins
$rolesPermitidos = ['admin', 'administrador', 'administracion'];
if (!isset($_SESSION['usuario_id']) || !in_array(strtolower($_SESSION['rol'] ?? ''), $rolesPermitidos, true)) {
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']); exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$accion   = $input['accion']   ?? '';
$pin      = trim($input['pin'] ?? '');
$titular  = trim($input['titular']      ?? '');
$banco    = trim($input['banco']        ?? '');
$cbu      = trim($input['cbu']          ?? '');
$alias    = trim($input['alias']        ?? '');
$instrucciones = trim($input['instrucciones'] ?? '');
$nuevo_pin     = trim($input['nuevo_pin']     ?? '');

try {
    $pdo = Conexion::conectar();
    $pdo->exec("CREATE TABLE IF NOT EXISTS datos_bancarios (
        id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        titular   VARCHAR(200) NOT NULL DEFAULT '',
        banco     VARCHAR(100) NOT NULL DEFAULT '',
        cbu       VARCHAR(22)  NOT NULL DEFAULT '',
        alias     VARCHAR(50)  NOT NULL DEFAULT '',
        instrucciones TEXT NULL,
        pin_hash  VARCHAR(255) NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $existing = $pdo->query("SELECT id, pin_hash FROM datos_bancarios ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    // Verificar PIN si ya existe uno configurado
    if ($existing && $existing['pin_hash']) {
        if (!$pin) {
            echo json_encode(['ok' => false, 'msg' => 'Se requiere el PIN de seguridad']); exit;
        }
        if (!password_verify($pin, $existing['pin_hash'])) {
            echo json_encode(['ok' => false, 'msg' => 'PIN incorrecto']); exit;
        }
    }

    if ($accion === 'verificar_pin') {
        echo json_encode(['ok' => true]); exit;
    }

    $pinHash = $existing['pin_hash'] ?? null;
    if ($nuevo_pin) {
        if (strlen($nuevo_pin) < 4) {
            echo json_encode(['ok' => false, 'msg' => 'El PIN debe tener al menos 4 dígitos']); exit;
        }
        $pinHash = password_hash($nuevo_pin, PASSWORD_DEFAULT);
    }

    if ($existing) {
        $pdo->prepare("UPDATE datos_bancarios SET titular=?, banco=?, cbu=?, alias=?, instrucciones=?, pin_hash=?, updated_at=NOW() WHERE id=?")
            ->execute([$titular, $banco, $cbu, $alias, $instrucciones ?: null, $pinHash, $existing['id']]);
    } else {
        if (!$nuevo_pin) {
            echo json_encode(['ok' => false, 'msg' => 'Creá un PIN de seguridad para proteger estos datos']); exit;
        }
        $pdo->prepare("INSERT INTO datos_bancarios (titular, banco, cbu, alias, instrucciones, pin_hash) VALUES (?,?,?,?,?,?)")
            ->execute([$titular, $banco, $cbu, $alias, $instrucciones ?: null, $pinHash]);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
