<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'logout_redirect') {
    redirect('/login/logout.php?from=tienda');
}

header('Content-Type: application/json');
try { $pdo = Conexion::conectar(); } catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error de base de datos']); exit;
}

try {

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
                    'token'            => !$emailDestino ? $token : null,
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
        // Agregar columna email si no existe (idempotente)
        try { $pdo->exec("ALTER TABLE usuario ADD COLUMN email VARCHAR(180) NULL"); } catch (Throwable $e) {}
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

    case 'solicitar_cambio_celular':
        if (!isset($_SESSION['tienda_cliente_id'])) {
            echo json_encode(['success'=>false,'message'=>'No autenticado']); exit;
        }
        $uid  = (int)$_SESSION['tienda_cliente_id'];
        $row  = $pdo->prepare("SELECT nombre, email, celular FROM usuario WHERE idusuario=? AND activo=1");
        $row->execute([$uid]);
        $user = $row->fetch();
        if (!$user || !$user['email']) {
            echo json_encode(['success'=>false,'message'=>'Necesitás tener un email registrado para cambiar el celular.']); exit;
        }
        try { $pdo->exec("CREATE TABLE IF NOT EXISTS verificacion_token (id INT AUTO_INCREMENT PRIMARY KEY, token VARCHAR(128) NOT NULL UNIQUE, usuario_idusuario INT NOT NULL, datos_nuevos TEXT NULL, tipo VARCHAR(40) NOT NULL, expira DATETIME NOT NULL, created_at DATETIME DEFAULT NOW())"); } catch (Throwable $e) {}
        $pdo->prepare("DELETE FROM verificacion_token WHERE usuario_idusuario=? AND tipo='cambio_celular'")->execute([$uid]);
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("INSERT INTO verificacion_token (token, usuario_idusuario, datos_nuevos, tipo, expira) VALUES (?,?,NULL,'cambio_celular', DATE_ADD(NOW(), INTERVAL 1 HOUR))")
            ->execute([$token, $uid]);
        $link          = URL_TIENDA . '/cambiar-celular.php?token=' . $token;
        $nombre        = htmlspecialchars($user['nombre']);
        $celularActual = htmlspecialchars($user['celular'] ?? 'Sin número');
        $contenido = <<<HTML
<h2 style="margin:0 0 10px;font-size:22px;color:#2d2d2d;font-weight:700;">Hola, {$nombre}! 🍪</h2>
<p style="margin:0 0 20px;color:#555;font-size:15px;line-height:1.7;">
  Recibimos tu solicitud para cambiar el número de celular de tu cuenta en <strong>Canetto Cookies</strong>.
</p>
<div style="background:#fdf5f7;border-radius:14px;padding:18px 22px;margin:0 0 24px;border-left:4px solid #c88e99;">
  <div style="font-size:11px;color:#c88e99;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;font-weight:700;">Número actual</div>
  <div style="font-size:20px;font-weight:700;color:#333;">📱 {$celularActual}</div>
</div>
<p style="margin:0 0 6px;color:#555;font-size:15px;line-height:1.7;">
  Hacé clic en el botón para ingresar tu nuevo número:
</p>
<div style="text-align:center;margin:32px 0 28px;">
  <a href="{$link}"
     style="display:inline-block;background:linear-gradient(135deg,#c88e99 0%,#a46678 100%);
            color:#fff;text-decoration:none;font-weight:700;font-size:16px;
            padding:16px 42px;border-radius:50px;
            box-shadow:0 6px 20px rgba(164,102,120,.40);letter-spacing:.3px;">
    📱 Cambiar mi número
  </a>
</div>
<div style="background:#fff8e1;border-radius:10px;padding:12px 16px;margin-bottom:8px;">
  <p style="margin:0;font-size:13px;color:#b07d2a;text-align:center;">
    ⏳ Este enlace es válido por <strong>1 hora</strong> y se puede usar una sola vez.
  </p>
</div>
<p style="margin:16px 0 0;color:#bbb;font-size:12px;text-align:center;line-height:1.6;">
  Si no solicitaste este cambio, ignorá este email.<br>Tu número no será modificado.
</p>
HTML;
        $ok = enviarEmail($user['email'], $user['nombre'], '📱 Cambiar número de celular — Canetto', 'Cambiar celular', $contenido);
        echo json_encode(['success'=>$ok, 'message'=>$ok ? 'Enlace enviado' : 'Error al enviar el email']);
        break;

    case 'guardar_direccion':
        if (!isset($_SESSION['tienda_cliente_id'])) {
            echo json_encode(['success'=>false,'message'=>'No autenticado']); exit;
        }
        $uid      = (int)$_SESSION['tienda_cliente_id'];
        $apodo    = trim($_POST['apodo']    ?? '');
        $direccion= trim($_POST['direccion']?? '');
        $lat      = is_numeric($_POST['lat'] ?? '') ? (float)$_POST['lat'] : null;
        $lng      = is_numeric($_POST['lng'] ?? '') ? (float)$_POST['lng'] : null;
        if (!$apodo || !$direccion) { echo json_encode(['success'=>false,'message'=>'Datos incompletos']); exit; }
        $ins = $pdo->prepare("INSERT INTO direcciones_guardadas (usuario_idusuario, apodo, direccion, lat, lng) VALUES (?,?,?,?,?)");
        $ins->execute([$uid, $apodo, $direccion, $lat, $lng]);
        echo json_encode(['success'=>true, 'id'=>(int)$pdo->lastInsertId()]);
        break;

    case 'borrar_direccion':
        if (!isset($_SESSION['tienda_cliente_id'])) {
            echo json_encode(['success'=>false,'message'=>'No autenticado']); exit;
        }
        $uid = (int)$_SESSION['tienda_cliente_id'];
        $id  = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'ID inválido']); exit; }
        $pdo->prepare("DELETE FROM direcciones_guardadas WHERE id=? AND usuario_idusuario=?")->execute([$id, $uid]);
        echo json_encode(['success'=>true]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Acción no reconocida']);
}

} catch (Throwable $e) {
    $msg = $e->getMessage();
    // Detectar errores comunes de BD y mostrar mensajes amigables
    if (str_contains($msg, '1062') || str_contains($msg, 'Duplicate entry')) {
        if (str_contains($msg, 'email')) {
            $msg = 'Ese email ya está registrado en otra cuenta.';
        } elseif (str_contains($msg, 'celular')) {
            $msg = 'Ese número de celular ya está registrado en otra cuenta.';
        } else {
            $msg = 'Ese dato ya está registrado en otra cuenta.';
        }
    }
    echo json_encode(['success'=>false,'message'=>$msg]);
}
