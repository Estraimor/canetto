<?php
ob_start();
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/google_config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

if (!defined('GOOGLE_CLIENT_ID') || GOOGLE_CLIENT_ID === '') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Google auth no configurado aún']); exit;
}

$input    = json_decode(file_get_contents('php://input'), true) ?: [];
$id_token = trim($input['credential'] ?? '');

if (!$id_token) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Token inválido']); exit;
}

// Verificar token con Google (tokeninfo endpoint)
$url      = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
$context  = stream_context_create(['http' => ['timeout' => 8]]);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No se pudo verificar con Google. Revisá tu conexión.']); exit;
}

$payload = json_decode($response, true);

// Verificar que el token sea para ESTA app
if (($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Token no válido para esta aplicación']); exit;
}

// Verificar que el email esté verificado por Google
if (($payload['email_verified'] ?? '') !== 'true') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Email de Google no verificado']); exit;
}

$email = strtolower(trim($payload['email'] ?? ''));
if (!$email) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No se obtuvo el email de Google']); exit;
}

try {
    $pdo = Conexion::conectar();

    // Buscar usuario con rol Repartidor y ese email
    $stmt = $pdo->prepare("
        SELECT u.idusuario, u.nombre, u.apellido
        FROM usuario u
        INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
        INNER JOIN roles r ON r.idroles = ur.roles_idroles
        WHERE LOWER(u.email) = :email
          AND u.activo = 1
          AND r.nombre = 'Repartidor'
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $rep = $stmt->fetch();

    if (!$rep) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => "No hay ningún repartidor registrado con el email {$email}",
        ]); exit;
    }

    $_SESSION['repartidor_id']     = $rep['idusuario'];
    $_SESSION['repartidor_nombre'] = trim($rep['nombre'] . ' ' . ($rep['apellido'] ?? ''));

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'nombre'  => $_SESSION['repartidor_nombre'],
        'id'      => $rep['idusuario'],
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
