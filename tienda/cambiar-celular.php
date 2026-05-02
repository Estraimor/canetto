<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$token = trim($_GET['token'] ?? '');
$error = '';
$exito = false;
$uid   = null;
$nombreUsuario = '';
$emailUsuario  = '';
$pdo = null;

if (!$token) {
    $error = 'Enlace inválido.';
} else {
    try {
        $pdo  = Conexion::conectar();
        $stmt = $pdo->prepare("
            SELECT vt.usuario_idusuario, u.nombre, u.email
            FROM verificacion_token vt
            JOIN usuario u ON u.idusuario = vt.usuario_idusuario
            WHERE vt.token = ? AND vt.tipo = 'cambio_celular' AND vt.expira > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if (!$row) {
            $error = 'El enlace expiró o no es válido. Solicitá uno nuevo desde tu cuenta.';
        } else {
            $uid           = (int)$row['usuario_idusuario'];
            $nombreUsuario = $row['nombre'];
            $emailUsuario  = $row['email'];
        }
    } catch (Throwable $e) {
        $error = 'Error interno. Intentá nuevamente.';
    }
}

// Procesar el nuevo celular
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $uid && !$error && $pdo) {
    $nuevoCelular = trim($_POST['celular'] ?? '');
    if (!$nuevoCelular) {
        $error = 'Ingresá el nuevo número de celular.';
    } elseif (!preg_match('/^\+?[\d\s\-]{6,20}$/', $nuevoCelular)) {
        $error = 'El número ingresado no es válido.';
    } else {
        try {
            $chk = $pdo->prepare("SELECT idusuario FROM usuario WHERE celular = ? AND idusuario != ?");
            $chk->execute([$nuevoCelular, $uid]);
            if ($chk->fetch()) {
                $error = 'Ese número ya está registrado en otra cuenta.';
            } else {
                $pdo->prepare("UPDATE usuario SET celular = ?, updated_at = NOW() WHERE idusuario = ?")
                    ->execute([$nuevoCelular, $uid]);
                $pdo->prepare("DELETE FROM verificacion_token WHERE token = ?")
                    ->execute([$token]);
                $exito = true;
            }
        } catch (Throwable $e) {
            $error = 'No se pudo actualizar. Intentá nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Cambiar celular — Canetto</title>
<link rel="icon" type="image/png" href="https://canettocookies.com/img/Logo_Canetto_Cookie.png">
<style>
@font-face{font-family:"Speedee";src:url("https://canettocookies.com/assets/fonts/Speedee.ttf") format("truetype");font-weight:700;font-display:swap}
@font-face{font-family:"Speedee";src:url("https://canettocookies.com/assets/fonts/Speedee-Regular.otf") format("opentype");font-weight:400;font-display:swap}
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Speedee',sans-serif;
  min-height:100vh;
  background:linear-gradient(160deg,#fdf5f7 0%,#f0e8ea 100%);
  display:flex;align-items:center;justify-content:center;padding:24px 16px;
}
.card{
  background:#fff;border-radius:24px;
  box-shadow:0 12px 48px rgba(164,102,120,.15);
  max-width:420px;width:100%;overflow:hidden;
}
/* Header */
.card-header{
  background:linear-gradient(135deg,#c88e99 0%,#a46678 100%);
  padding:36px 32px 28px;text-align:center;position:relative;
}
.card-header::after{
  content:'';position:absolute;bottom:-20px;left:0;right:0;
  height:40px;background:#fff;border-radius:50% 50% 0 0 / 100% 100% 0 0;
}
.brand-logo{
  width:80px;height:80px;border-radius:50%;
  border:3px solid rgba(255,255,255,.5);
  display:block;margin:0 auto 14px;
  box-shadow:0 4px 16px rgba(0,0,0,.15);
}
.brand-name{
  font-family:'Speedee',sans-serif;
  font-size:20px;font-weight:700;letter-spacing:5px;
  color:#fff;text-transform:uppercase;
}
.brand-sub{
  color:rgba(255,255,255,.8);font-size:11px;
  letter-spacing:2px;margin-top:4px;text-transform:uppercase;
}
/* Body */
.card-body{padding:40px 32px 32px;}
.page-title{
  font-size:22px;font-weight:700;color:#1e293b;
  margin-bottom:6px;
}
.page-sub{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:28px;}

/* Form */
.fg{margin-bottom:18px;}
.fg label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;
  letter-spacing:.5px;color:#94a3b8;margin-bottom:8px;}
.fg input{
  width:100%;padding:14px 16px;border:2px solid #e8d5d9;border-radius:14px;
  font-size:16px;font-family:'Speedee',sans-serif;color:#1e293b;
  outline:none;transition:border-color .2s,box-shadow .2s;background:#fdf8f9;
}
.fg input:focus{border-color:#c88e99;box-shadow:0 0 0 4px rgba(200,142,153,.12);background:#fff;}
.fg input::placeholder{color:#c4a4ac;}

.btn-primary{
  width:100%;padding:15px;border:none;border-radius:14px;
  background:linear-gradient(135deg,#c88e99 0%,#a46678 100%);
  color:#fff;font-size:16px;font-weight:700;font-family:'Speedee',sans-serif;
  cursor:pointer;transition:opacity .18s,transform .15s;
  box-shadow:0 6px 20px rgba(164,102,120,.35);
  margin-top:4px;
}
.btn-primary:hover{opacity:.92;transform:translateY(-1px);}
.btn-primary:active{transform:translateY(0);}

.alert{
  padding:12px 16px;border-radius:12px;font-size:14px;
  margin-bottom:20px;line-height:1.5;
}
.alert-err{background:#fef2f2;color:#c0392b;border:1px solid #fecaca;}
.alert-ok {background:#f0fdf4;color:#2d8a4e;border:1px solid #bbf7d0;}

/* Éxito */
.success-wrap{text-align:center;padding:8px 0 12px;}
.success-ic{font-size:56px;margin-bottom:16px;display:block;}
.success-title{font-size:22px;font-weight:700;color:#2d8a4e;margin-bottom:8px;}
.success-sub{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:28px;}

.btn-sec{
  display:block;width:100%;padding:13px;border-radius:14px;
  background:#f1f5f9;color:#334155;text-decoration:none;text-align:center;
  font-size:15px;font-weight:600;font-family:'Speedee',sans-serif;
  transition:background .18s;margin-top:10px;
}
.btn-sec:hover{background:#e2e8f0;}

/* Error page */
.error-wrap{text-align:center;padding:8px 0 12px;}
.error-ic{font-size:52px;margin-bottom:14px;display:block;}
.error-title{font-size:20px;font-weight:700;color:#c0392b;margin-bottom:8px;}
.error-sub{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:24px;}
</style>
</head>
<body>
<div class="card">

  <!-- Header -->
  <div class="card-header">
    <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" class="brand-logo"
         onerror="this.style.display='none'">
    <div class="brand-name">Canetto</div>
    <div class="brand-sub">Cookies &amp; más</div>
  </div>

  <div class="card-body">

  <?php if ($exito): ?>
    <!-- ── ÉXITO ── -->
    <div class="success-wrap">
      <span class="success-ic">✅</span>
      <div class="success-title">¡Número actualizado!</div>
      <div class="success-sub">
        Tu número de celular fue cambiado correctamente.<br>
        Ya podés usar el nuevo número para ingresar a tu cuenta.
      </div>
      <a href="<?= URL_TIENDA ?>/mi-cuenta.php" class="btn-primary" style="display:block;text-decoration:none;text-align:center;">
        Ir a mi cuenta
      </a>
      <a href="<?= URL_TIENDA ?>/index.php" class="btn-sec">← Volver a la tienda</a>
    </div>

  <?php elseif ($error && !$uid): ?>
    <!-- ── ERROR: token inválido ── -->
    <div class="error-wrap">
      <span class="error-ic">⚠️</span>
      <div class="error-title">Enlace no válido</div>
      <div class="error-sub"><?= htmlspecialchars($error) ?></div>
      <a href="<?= URL_TIENDA ?>/mi-cuenta.php" class="btn-primary" style="display:block;text-decoration:none;text-align:center;">
        Ir a mi cuenta
      </a>
    </div>

  <?php else: ?>
    <!-- ── FORMULARIO ── -->
    <div class="page-title">📱 Cambiar celular</div>
    <div class="page-sub">
      Hola <strong><?= htmlspecialchars($nombreUsuario) ?></strong>, ingresá tu nuevo número de celular.
    </div>

    <?php if ($error): ?>
      <div class="alert alert-err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="cambiar-celular.php?token=<?= urlencode($token) ?>">
      <div class="fg">
        <label>Nuevo número de celular</label>
        <input
          type="tel"
          name="celular"
          placeholder="Ej: 1123456789"
          value="<?= htmlspecialchars($_POST['celular'] ?? '') ?>"
          autofocus
          required>
      </div>
      <button type="submit" class="btn-primary">Confirmar cambio →</button>
    </form>

    <a href="<?= URL_TIENDA ?>/mi-cuenta.php" class="btn-sec">← Cancelar</a>

  <?php endif; ?>

  </div>
</div>
</body>
</html>
