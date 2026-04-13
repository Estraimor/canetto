<?php
/**
 * login/google_auth_admin.php
 * Verifica el credential de Google Sign-In para administradores.
 * El usuario debe existir con rol admin en la base de datos.
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

$googleId = $payload['sub']  ?? '';
$email    = strtolower(trim($payload['email'] ?? ''));
$avatar   = $payload['picture'] ?? null;

if (!$email || !$googleId) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No se obtuvo la información de Google']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Buscar usuario con rol admin por email o por vinculación Google previa
    $stmt = $pdo->prepare("
        SELECT u.*, r.idroles AS rol_id, r.nombre AS rol_nombre
        FROM usuario u
        INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
        INNER JOIN roles r ON r.idroles = ur.roles_idroles
        WHERE (
            LOWER(u.email) = :email
            OR EXISTS (
                SELECT 1 FROM usuario_auth ua
                WHERE ua.usuario_idusuario = u.idusuario
                  AND ua.provider = 'google'
                  AND ua.provider_id = :gid2
            )
        )
        AND u.activo = 1
        AND LOWER(r.nombre) IN ('admin','administrador','administracion')
        LIMIT 1
    ");
    $stmt->execute([':email' => $email, ':gid2' => $googleId]);
    $user = $stmt->fetch();

    if (!$user) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'No hay ningún administrador registrado con esta cuenta Google.',
        ]); exit;
    }

    $userId = (int)$user['idusuario'];

    // Vincular cuenta Google si aún no está vinculada
    $pdo->prepare("
        INSERT IGNORE INTO usuario_auth (usuario_idusuario, provider, provider_id, created_at)
        VALUES (?, 'google', ?, NOW())
    ")->execute([$userId, $googleId]);

    // Actualizar avatar si no tiene
    if (!$user['avatar'] && $avatar) {
        $pdo->prepare("UPDATE usuario SET avatar = ?, updated_at = NOW() WHERE idusuario = ?")->execute([$avatar, $userId]);
    }

    // ── Iniciar sesión admin ────────────────────────────────────────────────
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $userId;
    $_SESSION['nombre']     = $user['nombre'];
    $_SESSION['apellido']   = $user['apellido'];
    $_SESSION['rol']        = strtolower($user['rol_nombre']);
    $_SESSION['rol_id']     = $user['rol_id'];
    $_SESSION['last_seen']  = time();

    ob_end_clean();
    echo json_encode([
        'success'  => true,
        'nombre'   => trim($user['nombre'] . ' ' . ($user['apellido'] ?? '')),
        'redirect' => URL_ADMIN . '/index.php',
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
