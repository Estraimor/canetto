<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

$pdo = Conexion::conectar();

// Migraciones seguras (idempotentes)
$pdo->exec("CREATE TABLE IF NOT EXISTS verificacion_token (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    usuario_idusuario INT NOT NULL,
    datos_nuevos TEXT NOT NULL,
    tipo VARCHAR(30) DEFAULT 'merge_dni',
    expira DATETIME NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS direccion (
    iddireccion INT AUTO_INCREMENT PRIMARY KEY,
    usuario_idusuario INT NOT NULL,
    direccion_formateada VARCHAR(500),
    principal TINYINT(1) DEFAULT 0,
    lat DECIMAL(10,8) NULL,
    lng DECIMAL(11,8) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach ([
    "ALTER TABLE ventas ADD COLUMN tipo_entrega VARCHAR(10) NOT NULL DEFAULT 'retiro'",
    "ALTER TABLE ventas ADD COLUMN repartidor_idusuario INT NULL",
    "ALTER TABLE ventas ADD COLUMN direccion_entrega TEXT NULL",
    "ALTER TABLE ventas ADD COLUMN lat_entrega DECIMAL(10,8) NULL",
    "ALTER TABLE ventas ADD COLUMN lng_entrega DECIMAL(11,8) NULL",
] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$celular  = trim($input['celular']  ?? '');
$password = $input['password'] ?? '';

if (!$celular || !$password) {
    echo json_encode(['success' => false, 'message' => 'Ingresá tu celular y contraseña']); exit;
}

// El repartidor es un usuario con el rol "Repartidor"
$stmt = $pdo->prepare("
    SELECT u.idusuario, u.nombre, u.apellido, u.password_hash
    FROM usuario u
    INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
    INNER JOIN roles r ON r.idroles = ur.roles_idroles
    WHERE u.celular = ? AND u.activo = 1 AND r.nombre = 'Repartidor'
    LIMIT 1
");
$stmt->execute([$celular]);
$rep = $stmt->fetch();

if (!$rep || !password_verify($password, $rep['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Celular o contraseña incorrectos']); exit;
}

$_SESSION['repartidor_id']     = $rep['idusuario'];
$_SESSION['repartidor_nombre'] = trim($rep['nombre'] . ' ' . ($rep['apellido'] ?? ''));

echo json_encode([
    'success' => true,
    'nombre'  => $_SESSION['repartidor_nombre'],
    'id'      => $rep['idusuario'],
]);
