<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';

header('Content-Type: application/json');

session_start();

$pdo = Conexion::conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode(file_get_contents('php://input'), true);
$id   = intval($data['idusuario'] ?? 0);

if (!$id) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido.']);
    exit;
}

$sesionId = intval($_SESSION['usuario_id'] ?? 0);
if ($sesionId && $id === $sesionId) {
    echo json_encode(['ok' => false, 'msg' => 'No podés eliminar tu propia cuenta.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // ── 1. Ghost user: cuenta especial que hereda el historial ───────────────
    $ghost = $pdo->query(
        "SELECT idusuario FROM usuario WHERE usuario = '__deleted__' LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    if (!$ghost) {
        $pdo->exec("
            INSERT INTO usuario
                (nombre, apellido, usuario, email, celular, password_hash, activo)
            VALUES
                ('[Usuario]', '[Eliminado]', '__deleted__', NULL, NULL, '', 0)
        ");
        $ghostId = (int) $pdo->lastInsertId();
    } else {
        $ghostId = (int) $ghost['idusuario'];
    }

    // ── 2. Reasignar registros históricos al ghost (preserva el historial) ───
    // Tablas con usuario_idusuario NOT NULL — no admiten NULL, se reasignan
    $reasignar = [
        "UPDATE ventas                      SET usuario_idusuario = ? WHERE usuario_idusuario = ?",
        "UPDATE produccion                  SET usuario_idusuario = ? WHERE usuario_idusuario = ?",
        "UPDATE stock_productos_movimientos SET usuario_idusuario = ? WHERE usuario_idusuario = ?",
    ];
    foreach ($reasignar as $sql) {
        try { $pdo->prepare($sql)->execute([$ghostId, $id]); } catch (Throwable $e) {}
    }

    // Tablas con usuario_id que pueden ser nullable — intentar NULL, si falla reasignar
    $nullable = [
        ['tabla' => 'mermas',     'col' => 'usuario_id'],
        ['tabla' => 'incidencias','col' => 'usuario_id'],
    ];
    foreach ($nullable as $t) {
        try {
            $pdo->prepare("UPDATE {$t['tabla']} SET {$t['col']} = NULL WHERE {$t['col']} = ?")->execute([$id]);
        } catch (Throwable $e) {
            try {
                $pdo->prepare("UPDATE {$t['tabla']} SET {$t['col']} = ? WHERE {$t['col']} = ?")->execute([$ghostId, $id]);
            } catch (Throwable $e2) {}
        }
    }

    // Repartidor en ventas: simplemente desvincular
    try {
        $pdo->prepare("UPDATE ventas SET repartidor_idusuario = NULL WHERE repartidor_idusuario = ?")->execute([$id]);
    } catch (Throwable $e) {}

    // ── 3. Eliminar datos personales del usuario ─────────────────────────────
    $borrar = [
        "DELETE FROM usuarios_roles        WHERE usuario_idusuario = ?",
        "DELETE FROM usuario_auth          WHERE usuario_idusuario = ?",
        "DELETE FROM verificacion_token    WHERE usuario_idusuario = ?",
        "DELETE FROM direcciones_guardadas WHERE usuario_idusuario = ?",
        "DELETE FROM push_subscriptions    WHERE usuario_id = ?",
        "DELETE FROM push_notificaciones   WHERE usuario_id = ?",
        "DELETE FROM notif_repartidores    WHERE usuario_id = ?",
        "DELETE FROM password_reset_tokens WHERE usuario_id = ?",
        "DELETE FROM cupones_usos          WHERE usuario_id = ?",
    ];
    foreach ($borrar as $sql) {
        try { $pdo->prepare($sql)->execute([$id]); } catch (Throwable $e) {}
    }

    // ── 4. Eliminar el usuario ───────────────────────────────────────────────
    $pdo->prepare("DELETE FROM usuario WHERE idusuario = ?")->execute([$id]);

    $pdo->commit();

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['ok' => false, 'msg' => 'Error al eliminar: ' . $e->getMessage()]);
}
