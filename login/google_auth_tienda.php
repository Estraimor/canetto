<?php
/**
 * login/google_auth_tienda.php
 * Verifica el credential de Google Sign-In para clientes.
 * Crea el usuario si no existe y vincula la cuenta Google.
 */
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/google_config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$id_token = trim($input['credential'] ?? '');

if (!$id_token) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Token inválido']); exit;
}

// ── Verificar token con Google ──────────────────────────────────────────────
$url      = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
$ctx      = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($url, false, $ctx);

if ($response === false) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No se pudo verificar con Google']); exit;
}

$payload = json_decode($response, true);

if (($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Token no válido para esta aplicación']); exit;
}

if (($payload['email_verified'] ?? '') !== 'true') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Email de Google no verificado']); exit;
}

$googleId   = $payload['sub']     ?? '';
$email      = strtolower(trim($payload['email']      ?? ''));
$nombre     = trim($payload['given_name']  ?? $payload['name'] ?? '');
$apellido   = trim($payload['family_name'] ?? '');
$avatar     = $payload['picture'] ?? null;

if (!$email || !$googleId) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No se obtuvo la información de Google']); exit;
}

try {
    $pdo = Conexion::conectar();

    // ── 1. Buscar en usuario_auth por provider_id ───────────────────────────
    $stmtAuth = $pdo->prepare("
        SELECT ua.usuario_idusuario
        FROM usuario_auth ua
        WHERE ua.provider = 'google' AND ua.provider_id = :gid
        LIMIT 1
    ");
    $stmtAuth->execute([':gid' => $googleId]);
    $authRow = $stmtAuth->fetch();

    if ($authRow) {
        // Usuario ya vinculado a Google
        $userId = (int)$authRow['usuario_idusuario'];
        $user   = $pdo->prepare("SELECT * FROM usuario WHERE idusuario = ? AND activo = 1 LIMIT 1");
        $user->execute([$userId]);
        $usuario = $user->fetch();

        if (!$usuario) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Tu cuenta está inactiva']); exit;
        }
    } else {
        // ── 2. Buscar por email ─────────────────────────────────────────────
        $stmtEmail = $pdo->prepare("SELECT * FROM usuario WHERE LOWER(email) = :em AND activo = 1 LIMIT 1");
        $stmtEmail->execute([':em' => $email]);
        $usuario = $stmtEmail->fetch();

        if ($usuario) {
            // Existe el usuario → vincular cuenta Google
            $userId = (int)$usuario['idusuario'];
            $pdo->prepare("
                INSERT INTO usuario_auth (usuario_idusuario, provider, provider_id, created_at)
                VALUES (?, 'google', ?, NOW())
                ON DUPLICATE KEY UPDATE provider_id = VALUES(provider_id)
            ")->execute([$userId, $googleId]);

            // Actualizar avatar si no tiene
            if (!$usuario['avatar'] && $avatar) {
                $pdo->prepare("UPDATE usuario SET avatar = ?, updated_at = NOW() WHERE idusuario = ?")->execute([$avatar, $userId]);
            }
        } else {
            // ── 3. Crear usuario nuevo ──────────────────────────────────────
            $pdo->prepare("
                INSERT INTO usuario (nombre, apellido, email, usuario, password_hash, avatar, activo, created_at, updated_at)
                VALUES (:nom, :ape, :email, :user, '', :avatar, 1, NOW(), NOW())
            ")->execute([
                ':nom'    => $nombre   ?: explode('@', $email)[0],
                ':ape'    => $apellido ?: null,
                ':email'  => $email,
                ':user'   => $email,   // email como usuario (único)
                ':avatar' => $avatar,
            ]);
            $userId = (int)$pdo->lastInsertId();

            // Asignar rol cliente
            $rolStmt = $pdo->prepare("SELECT idroles FROM roles WHERE LOWER(nombre) = 'cliente' LIMIT 1");
            $rolStmt->execute();
            $rol = $rolStmt->fetch();
            if ($rol) {
                $pdo->prepare("INSERT INTO usuarios_roles (usuario_idusuario, roles_idroles) VALUES (?, ?)")
                    ->execute([$userId, $rol['idroles']]);
            }

            // Vincular Google
            $pdo->prepare("
                INSERT INTO usuario_auth (usuario_idusuario, provider, provider_id, created_at)
                VALUES (?, 'google', ?, NOW())
            ")->execute([$userId, $googleId]);

            $usuario = $pdo->prepare("SELECT * FROM usuario WHERE idusuario = ? LIMIT 1");
            $usuario->execute([$userId]);
            $usuario = $usuario->fetch();
        }
    }

    // ── Iniciar sesión ──────────────────────────────────────────────────────
    session_regenerate_id(true);
    $_SESSION['usuario_id']            = $userId;
    $_SESSION['nombre']                = $usuario['nombre'] ?? $nombre;
    $_SESSION['apellido']              = $usuario['apellido'] ?? $apellido;
    $_SESSION['rol']                   = 'cliente';
    $_SESSION['tienda_cliente_id']     = $userId;
    $_SESSION['tienda_cliente_nombre'] = trim(($usuario['nombre'] ?? $nombre) . ' ' . ($usuario['apellido'] ?? $apellido));

    ob_end_clean();
    echo json_encode([
        'success'  => true,
        'nombre'   => $_SESSION['tienda_cliente_nombre'],
        'redirect' => URL_TIENDA . '/index.php',
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
