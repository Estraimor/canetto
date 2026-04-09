<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['tienda_cliente_id'])) {
    header('Location: login.php'); exit;
}

$pdo = Conexion::conectar();
$uid = (int)$_SESSION['tienda_cliente_id'];

$stmt = $pdo->prepare("SELECT nombre, apellido, celular, dni FROM usuario WHERE idusuario = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user) { header('Location: api/auth.php?action=logout_redirect'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Cuenta — Canetto</title>
<link rel="stylesheet" href="tienda.css">
<style>
.cuenta-wrap { padding: 16px 20px 40px; max-width: 500px; margin: 0 auto; }
.cuenta-section {
  background: #fff;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.cuenta-section h3 {
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 16px;
  color: #111;
  display: flex;
  align-items: center;
  gap: 8px;
}
.c-field { margin-bottom: 14px; }
.c-field label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 5px;
}
.c-field input {
  width: 100%;
  padding: 12px 14px;
  border: 1.5px solid #ebebeb;
  border-radius: 10px;
  font-size: 14px;
  font-family: inherit;
  color: #111;
  background: #fafafa;
  transition: border-color 0.2s;
  outline: none;
}
.c-field input:focus { border-color: var(--pk); background: #fff; }
.c-field input:disabled { color: #aaa; background: #f5f5f5; cursor: not-allowed; }
.btn-save {
  width: 100%;
  padding: 14px;
  background: #111;
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s;
  margin-top: 4px;
}
.btn-save:hover { background: var(--pk); }
.btn-save:disabled { background: #ccc; cursor: not-allowed; }
.btn-logout {
  width: 100%;
  padding: 14px;
  background: #f9edf0;
  color: #c88e99;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: background 0.2s;
  text-decoration: none;
  display: block;
  text-align: center;
}
.btn-logout:hover { background: #f5c6c6; }
.c-alert {
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
  margin-bottom: 14px;
  display: none;
}
.c-alert.on { display: block; }
.c-alert.ok { background: #e8f5e9; color: #1d8348; }
.c-alert.err { background: #f9edf0; color: #c88e99; }
.avatar-circle {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #c88e99, #9c27b0);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  margin: 0 auto 12px;
}
.user-greeting {
  text-align: center;
  margin-bottom: 20px;
}
.user-greeting strong { font-size: 18px; }
.user-greeting small { display: block; color: #888; font-size: 12px; }
</style>
</head>
<body class="has-bottom-nav">
<div id="page-wrap">

<header class="t-nav">
  <a href="index.php" class="t-brand">
    <div class="t-brand-icon">
      <img src="../img/canetto_logo.jpg" alt="Canetto" class="t-brand-logo">
    </div>
    <span class="t-brand-name">Canetto</span>
  </a>
</header>

<div class="page-hd">
  <a href="index.php" class="back-btn">←</a>
  <div>
    <div class="page-title">Mi cuenta</div>
    <div style="font-size:12px;color:#888">Gestioná tu perfil</div>
  </div>
</div>

<div class="cuenta-wrap">

  <div class="user-greeting">
    <div class="avatar-circle">👤</div>
    <strong><?= htmlspecialchars(trim($user['nombre'].' '.($user['apellido'] ?? ''))) ?></strong>
    <small><?= htmlspecialchars($user['celular'] ?? '') ?></small>
  </div>

  <!-- Datos personales -->
  <div class="cuenta-section">
    <h3>✏️ Mis datos</h3>
    <div id="alertDatos" class="c-alert"></div>
    <div class="c-field">
      <label>Nombre</label>
      <input type="text" id="uNombre" value="<?= htmlspecialchars($user['nombre']) ?>" placeholder="Nombre">
    </div>
    <div class="c-field">
      <label>Apellido</label>
      <input type="text" id="uApellido" value="<?= htmlspecialchars($user['apellido'] ?? '') ?>" placeholder="Apellido">
    </div>
    <div class="c-field">
      <label>Celular</label>
      <input type="tel" id="uCelular" value="<?= htmlspecialchars($user['celular'] ?? '') ?>" disabled title="El celular no se puede cambiar">
    </div>
    <button class="btn-save" id="btnDatos" onclick="guardarDatos()">Guardar cambios</button>
  </div>

  <!-- Cambiar contraseña -->
  <div class="cuenta-section">
    <h3>🔒 Cambiar contraseña</h3>
    <div id="alertPass" class="c-alert"></div>
    <div class="c-field">
      <label>Contraseña actual</label>
      <input type="password" id="pActual" placeholder="••••••••">
    </div>
    <div class="c-field">
      <label>Nueva contraseña</label>
      <input type="password" id="pNueva" placeholder="••••••••">
    </div>
    <div class="c-field">
      <label>Repetir nueva contraseña</label>
      <input type="password" id="pRepetir" placeholder="••••••••">
    </div>
    <button class="btn-save" id="btnPass" onclick="cambiarPass()">Cambiar contraseña</button>
  </div>

  <!-- Cerrar sesión -->
  <div class="cuenta-section">
    <h3>🚪 Sesión</h3>
    <a href="#" class="btn-logout" onclick="doLogout(event)">Cerrar sesión</a>
  </div>

</div>
</div><!-- /page-wrap -->

<nav class="bottom-nav">
  <a href="index.php" class="bn-item">
    <span class="bn-ic">🏠</span>
    <span>Inicio</span>
  </a>
  <a href="mis-pedidos.php" class="bn-item">
    <span class="bn-ic">📦</span>
    <span>Mis pedidos</span>
  </a>
  <a href="index.php#sucursales" class="bn-item">
    <span class="bn-ic">📍</span>
    <span>Sucursales</span>
  </a>
  <a href="mi-cuenta.php" class="bn-item active">
    <span class="bn-ic">👤</span>
    <span>Mi cuenta</span>
  </a>
</nav>

<script>
function setAlert(id, msg, type) {
  const el = document.getElementById(id);
  el.textContent = msg;
  el.className = 'c-alert on ' + type;
  setTimeout(() => el.classList.remove('on'), 4000);
}

async function guardarDatos() {
  const nombre = document.getElementById('uNombre').value.trim();
  const apellido = document.getElementById('uApellido').value.trim();
  if (!nombre) { setAlert('alertDatos', 'El nombre es obligatorio', 'err'); return; }
  const btn = document.getElementById('btnDatos');
  btn.disabled = true; btn.textContent = 'Guardando...';
  try {
    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('nombre', nombre);
    fd.append('apellido', apellido);
    const d = await (await fetch('api/auth.php', { method: 'POST', body: fd })).json();
    if (d.success) setAlert('alertDatos', '✅ Datos actualizados', 'ok');
    else setAlert('alertDatos', d.message || 'Error al guardar', 'err');
  } catch { setAlert('alertDatos', 'Error de conexión', 'err'); }
  btn.disabled = false; btn.textContent = 'Guardar cambios';
}

async function cambiarPass() {
  const actual  = document.getElementById('pActual').value;
  const nueva   = document.getElementById('pNueva').value;
  const repetir = document.getElementById('pRepetir').value;
  if (!actual || !nueva || !repetir) { setAlert('alertPass', 'Completá todos los campos', 'err'); return; }
  if (nueva.length < 6) { setAlert('alertPass', 'La contraseña debe tener al menos 6 caracteres', 'err'); return; }
  if (nueva !== repetir) { setAlert('alertPass', 'Las contraseñas no coinciden', 'err'); return; }
  const btn = document.getElementById('btnPass');
  btn.disabled = true; btn.textContent = 'Guardando...';
  try {
    const fd = new FormData();
    fd.append('action', 'change_password');
    fd.append('password_actual', actual);
    fd.append('password_nueva', nueva);
    const d = await (await fetch('api/auth.php', { method: 'POST', body: fd })).json();
    if (d.success) {
      setAlert('alertPass', '✅ Contraseña actualizada', 'ok');
      document.getElementById('pActual').value = '';
      document.getElementById('pNueva').value = '';
      document.getElementById('pRepetir').value = '';
    } else setAlert('alertPass', d.message || 'Error al cambiar', 'err');
  } catch { setAlert('alertPass', 'Error de conexión', 'err'); }
  btn.disabled = false; btn.textContent = 'Cambiar contraseña';
}

function doLogout(e) {
  e.preventDefault();
  // Limpiar el carrito de este usuario antes de cerrar sesión
  localStorage.removeItem('canetto_cart_<?= $uid ?>');
  window.location.href = 'api/auth.php?action=logout_redirect';
}
</script>
<script src="transitions.js"></script>
</body>
</html>
