<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json');

session_start();

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents('php://input'), true);

$idusuario = isset($data['idusuario']) && intval($data['idusuario']) > 0
    ? intval($data['idusuario'])
    : null;

$nombre   = trim($data['nombre']   ?? '');
$apellido = trim($data['apellido'] ?? '');
$dni      = trim($data['dni']      ?? '');
$celular  = trim($data['celular']  ?? '');
$usuario  = trim($data['usuario']  ?? '');
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');
$activo   = intval($data['activo'] ?? 1);

/* ── Validaciones ── */
if (!$nombre) {
    echo json_encode(['ok' => false, 'msg' => 'El nombre es obligatorio.']);
    exit;
}

if (!$usuario) {
    echo json_encode(['ok' => false, 'msg' => 'El campo usuario (login) es obligatorio.']);
    exit;
}

if (!$idusuario && !$password) {
    echo json_encode(['ok' => false, 'msg' => 'La contraseña es obligatoria para usuarios nuevos.']);
    exit;
}

try {

    /* ── Unicidad del nombre de usuario ── */
    if ($idusuario) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE usuario = ? AND idusuario != ?");
        $chk->execute([$usuario, $idusuario]);
    } else {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE usuario = ?");
        $chk->execute([$usuario]);
    }

    if ($chk->fetchColumn() > 0) {
        echo json_encode(['ok' => false, 'msg' => 'El nombre de usuario "' . htmlspecialchars($usuario) . '" ya está en uso.']);
        exit;
    }

    /* ── UPDATE ── */
    if ($idusuario) {

        if ($password !== '') {
            // Actualizar con nueva contraseña
            $stmt = $pdo->prepare("
                UPDATE usuario SET
                    nombre        = ?,
                    apellido      = ?,
                    dni           = ?,
                    celular       = ?,
                    usuario       = ?,
                    email         = ?,
                    password_hash = ?,
                    activo        = ?
                WHERE idusuario = ?
            ");
            $stmt->execute([
                $nombre,
                $apellido  ?: null,
                $dni       ?: null,
                $celular   ?: null,
                $usuario,
                $email     ?: null,
                password_hash($password, PASSWORD_DEFAULT),
                $activo,
                $idusuario
            ]);
        } else {
            // Mantener contraseña actual
            $stmt = $pdo->prepare("
                UPDATE usuario SET
                    nombre   = ?,
                    apellido = ?,
                    dni      = ?,
                    celular  = ?,
                    usuario  = ?,
                    email    = ?,
                    activo   = ?
                WHERE idusuario = ?
            ");
            $stmt->execute([
                $nombre,
                $apellido ?: null,
                $dni      ?: null,
                $celular  ?: null,
                $usuario,
                $email    ?: null,
                $activo,
                $idusuario
            ]);
        }

    /* ── INSERT ── */
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO usuario
                (nombre, apellido, dni, celular, email, usuario, password_hash, activo, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $nombre,
            $apellido  ?: null,
            $dni       ?: null,
            $celular   ?: null,
            $email     ?: null,
            $usuario,
            password_hash($password, PASSWORD_DEFAULT),
            $activo
        ]);

    }

    echo json_encode(['ok' => true]);

} catch (PDOException $e) {

    echo json_encode(['ok' => false, 'msg' => 'Error en la base de datos: ' . $e->getMessage()]);

}
