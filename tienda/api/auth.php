<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'logout_redirect') {
    unset($_SESSION['tienda_cliente_id'], $_SESSION['tienda_cliente_nombre']);
    header('Location: ../index.php'); exit;
}

header('Content-Type: application/json');
try { $pdo = Conexion::conectar(); } catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error de base de datos']); exit;
}

switch ($action) {

    case 'status':
        echo json_encode([
            'logged' => isset($_SESSION['tienda_cliente_id']),
            'nombre' => $_SESSION['tienda_cliente_nombre'] ?? null,
            'id'     => $_SESSION['tienda_cliente_id']     ?? null,
        ]);
        break;

    case 'login':
        $celular  = trim($_POST['celular']  ?? '');
        $password = $_POST['password'] ?? '';
        if (!$celular || !$password) {
            echo json_encode(['success'=>false,'message'=>'Datos incompletos']); exit;
        }
        $stmt = $pdo->prepare("SELECT idusuario, nombre, apellido, password_hash FROM usuario WHERE celular = ? AND activo = 1");
        $stmt->execute([$celular]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['success'=>false,'message'=>'Celular o contraseña incorrectos']); exit;
        }
        $_SESSION['tienda_cliente_id']     = $user['idusuario'];
        $_SESSION['tienda_cliente_nombre'] = trim($user['nombre'].' '.($user['apellido'] ?? ''));
        echo json_encode(['success'=>true,'nombre'=>$_SESSION['tienda_cliente_nombre'],'id'=>$user['idusuario']]);
        break;

    case 'register':
        $nombre   = trim($_POST['nombre']   ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $celular  = trim($_POST['celular']  ?? '');
        $password = $_POST['password'] ?? '';
        $dni      = trim($_POST['dni']  ?? '');
        if (!$nombre || !$celular || !$password) {
            echo json_encode(['success'=>false,'message'=>'Faltan datos obligatorios']); exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['success'=>false,'message'=>'La contraseña debe tener al menos 6 caracteres']); exit;
        }
        $chk = $pdo->prepare("SELECT idusuario FROM usuario WHERE celular = ?");
        $chk->execute([$celular]);
        if ($chk->fetch()) {
            echo json_encode(['success'=>false,'message'=>'Ese número ya tiene cuenta. Iniciá sesión.']); exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins  = $pdo->prepare("INSERT INTO usuario (nombre, apellido, celular, dni, password_hash, activo, created_at, updated_at) VALUES (?,?,?,?,?,1,NOW(),NOW())");
        $ins->execute([$nombre, $apellido ?: null, $celular, $dni ?: null, $hash]);
        $newId = $pdo->lastInsertId();
        $_SESSION['tienda_cliente_id']     = $newId;
        $_SESSION['tienda_cliente_nombre'] = trim("$nombre $apellido");
        echo json_encode(['success'=>true,'nombre'=>$_SESSION['tienda_cliente_nombre'],'id'=>$newId]);
        break;

    case 'logout':
        unset($_SESSION['tienda_cliente_id'], $_SESSION['tienda_cliente_nombre']);
        echo json_encode(['success'=>true]);
        break;

    case 'update_profile':
        if (!isset($_SESSION['tienda_cliente_id'])) {
            echo json_encode(['success'=>false,'message'=>'No autenticado']); exit;
        }
        $uid      = (int)$_SESSION['tienda_cliente_id'];
        $nombre   = trim($_POST['nombre']   ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        if (!$nombre) { echo json_encode(['success'=>false,'message'=>'El nombre es obligatorio']); exit; }
        $upd = $pdo->prepare("UPDATE usuario SET nombre=?, apellido=?, updated_at=NOW() WHERE idusuario=?");
        $upd->execute([$nombre, $apellido ?: null, $uid]);
        $_SESSION['tienda_cliente_nombre'] = trim("$nombre $apellido");
        echo json_encode(['success'=>true]);
        break;

    case 'change_password':
        if (!isset($_SESSION['tienda_cliente_id'])) {
            echo json_encode(['success'=>false,'message'=>'No autenticado']); exit;
        }
        $uid      = (int)$_SESSION['tienda_cliente_id'];
        $actual   = $_POST['password_actual'] ?? '';
        $nueva    = $_POST['password_nueva']  ?? '';
        if (!$actual || !$nueva) { echo json_encode(['success'=>false,'message'=>'Datos incompletos']); exit; }
        if (strlen($nueva) < 6)  { echo json_encode(['success'=>false,'message'=>'La contraseña debe tener al menos 6 caracteres']); exit; }
        $row = $pdo->prepare("SELECT password_hash FROM usuario WHERE idusuario=?");
        $row->execute([$uid]);
        $hash = $row->fetchColumn();
        if (!$hash || !password_verify($actual, $hash)) {
            echo json_encode(['success'=>false,'message'=>'La contraseña actual es incorrecta']); exit;
        }
        $newHash = password_hash($nueva, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE usuario SET password_hash=?, updated_at=NOW() WHERE idusuario=?")->execute([$newHash, $uid]);
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Acción no reconocida']);
}
