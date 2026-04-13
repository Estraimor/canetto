<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Capturar ID antes de destruir la sesión
$uid_logout = $_SESSION['tienda_cliente_id'] ?? null;

// Destruir toda la sesión completamente
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
<script>
// Limpiar carrito del usuario que cerró sesión
<?php if ($uid_logout): ?>
localStorage.removeItem('canetto_cart_<?= (int)$uid_logout ?>');
<?php else: ?>
// Limpiar cualquier clave de carrito que haya quedado
Object.keys(localStorage).filter(k => k.startsWith('canetto_cart_')).forEach(k => localStorage.removeItem(k));
<?php endif; ?>
window.location.replace('<?= base() ?>/login/login.php');
</script>
</body>
</html>
