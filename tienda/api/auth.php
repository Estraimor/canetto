<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'logout_redirect') {
    redirect('/login/logout.php');
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

        // Verificar celular duplicado
        $chkCel = $pdo->prepare("SELECT idusuario FROM usuario WHERE celular = ?");
        $chkCel->execute([$celular]);
        if ($chkCel->fetch()) {
            echo json_encode(['success'=>false,'message'=>'Ese número ya tiene cuenta. Iniciá sesión.']); exit;
        }

        // Verificar DNI duplicado — si existe, ofrecer merge por email
        if ($dni) {
            $chkDni = $pdo->prepare("SELECT idusuario, email FROM usuario WHERE dni = ? AND activo = 1 LIMIT 1");
            $chkDni->execute([$dni]);
            $existing = $chkDni->fetch();
            if ($existing) {
                // Crear tabla de tokens si no existe
                $pdo->exec("CREATE TABLE IF NOT EXISTS verificacion_token (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    usuario_idusuario INT NOT NULL,
                    datos_nuevos TEXT NOT NULL,
                    tipo VARCHAR(30) DEFAULT 'merge_dni',
                    expira DATETIME NOT NULL,
                    usado TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                $token = bin2hex(random_bytes(32));
                $datos = json_encode([
                    'nombre'   => $nombre,
                    'apellido' => $apellido,
                    'celular'  => $celular,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                $pdo->prepare("INSERT INTO verificacion_token (token, usuario_idusuario, datos_nuevos, tipo, expira) VALUES (?,?,?,'merge_dni', DATE_ADD(NOW(), INTERVAL 24 HOUR))")
                    ->execute([$token, $existing['idusuario'], $datos]);

                $emailDestino = $existing['email'];
                $msgEmail = '';
                if ($emailDestino) {
                    $link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . base() . '/tienda/verificar_cuenta.php?token=' . $token;
                    $asunto = 'Confirmación de cuenta - Canetto';
                    $cuerpo = "Hola,\n\nAlguien quiere vincular una nueva cuenta con tu DNI ({$dni}).\n\nSi sos vos, hacé clic en el siguiente enlace para confirmar:\n\n{$link}\n\nEste enlace vence en 24 horas.\n\nSi no fuiste vos, ignorá este mensaje.\n\n— Canetto";
                    @mail($emailDestino, $asunto, $cuerpo, "From: noreply@canetto.com\r\nContent-Type: text/plain; charset=UTF-8");
                    $maskedEmail = preg_replace('/(.{2}).+(@.+)/', '$1***$2', $emailDestino);
                    $msgEmail = " Te enviamos un email a {$maskedEmail} para confirmar.";
                }

                echo json_encode([
                    'success'          => false,
                    'merge_required'   => true,
                    'has_email'        => !empty($emailDestino),
                    'token'            => !$emailDestino ? $token : null, // Si no hay email, dar token directo
                    'message'          => "Ya existe una cuenta con ese DNI.{$msgEmail}",
                ]);
                exit;
            }
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
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        echo json_encode(['success'=>true]);
        break;

    case 'update_profile':
        if (!isset($_SESSION['tienda_cliente_id'])) {
            echo json_encode(['success'=>false,'message'=>'No autenticado']); exit;
        }
        $uid      = (int)$_SESSION['tienda_cliente_id'];
        $nombre   = trim($_POST['nombre']   ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $dni      = trim($_POST['dni']      ?? '');
        $email    = trim($_POST['email']    ?? '');
        if (!$nombre) { echo json_encode(['success'=>false,'message'=>'El nombre es obligatorio']); exit; }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success'=>false,'message'=>'El email no es válido']); exit;
        }
        $upd = $pdo->prepare("UPDATE usuario SET nombre=?, apellido=?, dni=?, email=?, updated_at=NOW() WHERE idusuario=?");
        $upd->execute([$nombre, $apellido ?: null, $dni ?: null, $email ?: null, $uid]);
        $_SESSION['tienda_cliente_nombre'] = trim("$nombre $apellido");
        echo json_encode(['success'=>true]);
        break;

    case 'solicitar_reset':
        if (!isset($_SESSION['tienda_cliente_id'])) {
            echo json_encode(['success'=>false,'message'=>'No autenticado']); exit;
        }
        $uid  = (int)$_SESSION['tienda_cliente_id'];
        $row  = $pdo->prepare("SELECT nombre, apellido, email FROM usuario WHERE idusuario=? AND activo=1");
        $row->execute([$uid]);
        $user = $row->fetch();
        if (!$user || !$user['email']) {
            echo json_encode(['success'=>false,'message'=>'No tenés email registrado. Agregalo en "Mis datos" primero.']); exit;
        }
        // Crear tabla de tokens si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL,
            used TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Eliminar tokens anteriores
        $pdo->prepare("DELETE FROM password_reset_tokens WHERE usuario_id=?")->execute([$uid]);
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $pdo->prepare("INSERT INTO password_reset_tokens (usuario_id,token,expires_at) VALUES (?,?,?)")
            ->execute([$uid, $token, $expiresAt]);
        $link   = SITE_URL . '/login/reset_password.php?token=' . $token;
        $nombre = htmlspecialchars($user['nombre']);
        $contenido = <<<HTML
<h2 style="margin:0 0 8px;font-size:22px;color:#2d2d2d;font-weight:700;">Hola, {$nombre}! 🍪</h2>
<p style="margin:0 0 20px;color:#555;font-size:15px;line-height:1.7;">
  Recibimos tu solicitud para cambiar la contraseña de tu cuenta en <strong>Canetto Cookies</strong>.
</p>
<div style="text-align:center;margin:28px 0;">
  <a href="{$link}" style="display:inline-block;background:linear-gradient(135deg,#c88e99,#a46678);
     color:#fff;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;
     border-radius:50px;box-shadow:0 4px 16px rgba(200,142,153,.45);">🔑 Cambiar contraseña</a>
</div>
<p style="margin:0;color:#999;font-size:13px;text-align:center;">
  Este enlace expira en <strong>1 hora</strong>.
</p>
HTML;
        $ok = enviarEmail($user['email'], $user['nombre'], '🔑 Cambiá tu contraseña — Canetto', 'Cambiar contraseña', $contenido);
        echo json_encode(['success'=>$ok, 'message'=>$ok?'Email enviado':'Error al enviar el email']);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Acción no reconocida']);
}
