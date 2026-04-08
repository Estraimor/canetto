<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
header('Content-Type: application/json');

$pdo  = Conexion::conectar();
$data = json_decode(file_get_contents('php://input'), true) ?: [];

$id       = isset($data['idusuario']) && (int)$data['idusuario'] > 0 ? (int)$data['idusuario'] : null;
$nombre   = trim($data['nombre']   ?? '');
$apellido = trim($data['apellido'] ?? '');
$celular  = trim($data['celular']  ?? '');
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');
$activo   = isset($data['activo'])  ? (int)$data['activo'] : 1;

if (!$nombre)  { echo json_encode(['ok' => false, 'msg' => 'El nombre es obligatorio']); exit; }
if (!$celular) { echo json_encode(['ok' => false, 'msg' => 'El celular es obligatorio (se usa para ingresar a la app)']); exit; }
if (!$id && !$password) { echo json_encode(['ok' => false, 'msg' => 'La contraseña es obligatoria para nuevos repartidores']); exit; }
if ($password && strlen($password) < 6) { echo json_encode(['ok' => false, 'msg' => 'Contraseña de al menos 6 caracteres']); exit; }

try {
    // Obtener id del rol Repartidor
    $rolId = $pdo->query("SELECT idroles FROM roles WHERE nombre = 'Repartidor' LIMIT 1")->fetchColumn();
    if (!$rolId) {
        echo json_encode(['ok' => false, 'msg' => 'No existe el rol "Repartidor" en el sistema. Crealo primero en Configuraciones → Roles.']);
        exit;
    }

    $pdo->beginTransaction();

    if ($id) {
        // Verificar que el usuario existe y tiene rol repartidor
        if ($password) {
            $pdo->prepare("UPDATE usuario SET nombre=?, apellido=?, celular=?, email=?, password_hash=?, activo=?, updated_at=NOW() WHERE idusuario=?")
                ->execute([$nombre, $apellido ?: null, $celular, $email ?: null, password_hash($password, PASSWORD_DEFAULT), $activo, $id]);
        } else {
            $pdo->prepare("UPDATE usuario SET nombre=?, apellido=?, celular=?, email=?, activo=?, updated_at=NOW() WHERE idusuario=?")
                ->execute([$nombre, $apellido ?: null, $celular, $email ?: null, $activo, $id]);
        }
    } else {
        // Verificar celular duplicado
        $dup = $pdo->prepare("SELECT idusuario FROM usuario WHERE celular = ?");
        $dup->execute([$celular]);
        if ($dup->fetch()) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'msg' => 'Ese celular ya está registrado en el sistema']);
            exit;
        }

        // Crear usuario nuevo (sin campo "usuario" de login admin, solo acceso a la app)
        $pdo->prepare("INSERT INTO usuario (nombre, apellido, celular, email, password_hash, activo, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())")
            ->execute([$nombre, $apellido ?: null, $celular, $email ?: null, password_hash($password, PASSWORD_DEFAULT), $activo]);
        $id = (int)$pdo->lastInsertId();

        // Asignar rol Repartidor
        $pdo->prepare("INSERT IGNORE INTO usuarios_roles (usuario_idusuario, roles_idroles) VALUES (?,?)")
            ->execute([$id, $rolId]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
