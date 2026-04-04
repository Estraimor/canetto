<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/audit.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

$pdo  = Conexion::conectar();
$data = json_decode(file_get_contents('php://input'), true);

$nombre          = trim($data['nombre']            ?? '');
$telefono        = trim($data['telefono']          ?? '');
$email           = trim($data['email']             ?? '');
$direccion       = trim($data['direccion']         ?? '');
$contacto_nombre = trim($data['contacto_nombre']   ?? '');
$contacto_tel    = trim($data['contacto_telefono'] ?? '');
$observaciones   = trim($data['observaciones']     ?? '');
$activo          = intval($data['activo']           ?? 1);
$idproveedor     = $data['idproveedor']             ?? null;

if (!$nombre) {
    echo json_encode(['ok' => false, 'msg' => 'El nombre es requerido']);
    exit;
}

try {
    if ($idproveedor) {
        $stmt = $pdo->prepare("
            UPDATE proveedor SET
                nombre=?, telefono=?, email=?, direccion=?,
                contacto_nombre=?, contacto_telefono=?,
                observaciones=?, activo=?, updated_at=NOW()
            WHERE idproveedor=?
        ");
        $stmt->execute([
            $nombre, $telefono ?: null, $email ?: null, $direccion ?: null,
            $contacto_nombre ?: null, $contacto_tel ?: null,
            $observaciones ?: null, $activo, $idproveedor
        ]);
        audit($pdo, 'editar', 'compras',
            "Editó proveedor: {$nombre}" .
            ($telefono  ? " | Tel: {$telefono}"   : '') .
            ($email     ? " | Email: {$email}"    : '') .
            ($direccion ? " | Dir: {$direccion}"  : '') .
            " | Estado: " . ($activo ? 'activo' : 'inactivo')
        );
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO proveedor
            (nombre, telefono, email, direccion, contacto_nombre, contacto_telefono,
             observaciones, activo, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())
        ");
        $stmt->execute([
            $nombre, $telefono ?: null, $email ?: null, $direccion ?: null,
            $contacto_nombre ?: null, $contacto_tel ?: null,
            $observaciones ?: null, $activo
        ]);
        audit($pdo, 'crear', 'compras',
            "Creó proveedor: {$nombre}" .
            ($telefono  ? " | Tel: {$telefono}"  : '') .
            ($email     ? " | Email: {$email}"   : '') .
            ($direccion ? " | Dir: {$direccion}" : '')
        );
    }

    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
