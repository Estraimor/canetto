<?php
/**
 * audit.php — Helper centralizado de auditoría
 * Incluir en cualquier endpoint que haga INSERT / UPDATE / DELETE.
 * Silent fail: nunca interrumpe el flujo principal.
 */
if (!defined('APP_BOOT')) exit;

function audit(PDO $pdo, string $accion, string $modulo, string $descripcion): void
{
    try {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $uid     = $_SESSION['usuario_id'] ?? null;
        $unombre = 'Sistema';

        if ($uid) {
            $u = $pdo->prepare(
                "SELECT CONCAT(nombre, ' ', COALESCE(apellido,'')) FROM usuario WHERE idusuario = ?"
            );
            $u->execute([$uid]);
            $raw = $u->fetchColumn();
            if ($raw) $unombre = trim($raw);
        }

        $pdo->prepare(
            "INSERT INTO auditoria (usuario_id, usuario_nombre, accion, modulo, descripcion, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        )->execute([
            $uid,
            $unombre,
            $accion,
            $modulo,
            $descripcion,
            $_SERVER['REMOTE_ADDR'] ?? '—'
        ]);

    } catch (Throwable $e) {
        // Silencioso: la auditoría jamás bloquea la operación principal
    }
}
