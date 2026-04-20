<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['tienda_cliente_id'])) {
    header('Location: login.php'); exit;
}

$pdo = Conexion::conectar();
$uid = (int)$_SESSION['tienda_cliente_id'];

$stmt = $pdo->prepare("SELECT nombre, apellido, celular, dni, email FROM usuario WHERE idusuario = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
if (!$user) { header('Location: api/auth.php?action=logout_redirect'); exit; }

try {
    $pedidosCount = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE idusuario = ?");
    $pedidosCount->execute([$uid]);
    $totalPedidos = (int)$pedidosCount->fetchColumn();
} catch (Throwable $e) { $totalPedidos = 0; }

$nombreCompleto = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''));
$iniciales = strtoupper(
    substr($user['nombre'] ?? '', 0, 1) . substr($user['apellido'] ?? '', 0, 1)
) ?: '?';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Mi Cuenta — Canetto</title>
<link rel="stylesheet" href="tienda.css">
</head>
<body class="has-bottom-nav" style="background:#f5f5f5">
<div id="page-wrap">

<header class="t-nav">
  <a href="index.php" class="t-brand">
    <div class="t-brand-icon">
      <img src="<?= URL_ASSETS ?>/img/canetto_logo.jpg" alt="Canetto" class="t-brand-logo">
    </div>
    <span class="t-brand-name">Canetto</span>
  </a>
</header>

<div class="cuenta-wrap">

  <!-- Hero del perfil -->
  <div class="perfil-hero">
    <div class="perfil-avatar"><?= htmlspecialchars($iniciales) ?></div>
    <div class="perfil-info">
      <div class="perfil-name"><?= htmlspecialchars($nombreCompleto) ?></div>
      <div class="perfil-phone"><?= htmlspecialchars($user['celular'] ?? '') ?></div>
      <div class="perfil-badge">
        <i class="fa-solid fa-bag-shopping"></i>
        <?= $totalPedidos ?> pedido<?= $totalPedidos !== 1 ? 's' : '' ?> realizados
      </div>
    </div>
  </div>

  <div id="alertGlobal" class="c-alert" style="margin:16px 16px 0"></div>

  <!-- Datos personales -->
  <div class="settings-group-label" style="padding-left:20px">Datos de la cuenta</div>
  <div class="settings-group">

    <div class="settings-row" onclick="toggleEdit('nombre')">
      <div class="settings-row-icon sri-gray"><i class="fa-solid fa-user"></i></div>
      <div class="settings-row-body">
        <div class="settings-row-title">Nombre y apellido</div>
        <div class="settings-row-sub"><?= htmlspecialchars($nombreCompleto) ?></div>
      </div>
      <i class="fa-solid fa-chevron-right"></i>
    </div>
    <div class="settings-input-wrap" id="edit-nombre">
      <input type="text" id="uNombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" placeholder="Nombre">
      <input type="text" id="uApellido" value="<?= htmlspecialchars($user['apellido'] ?? '') ?>" placeholder="Apellido">
      <button class="btn-save-sm" onclick="guardarDatos()">Guardar</button>
    </div>

    <div class="settings-row" onclick="toggleEdit('email')">
      <div class="settings-row-icon sri-gray"><i class="fa-solid fa-envelope"></i></div>
      <div class="settings-row-body">
        <div class="settings-row-title">Email</div>
        <div class="settings-row-sub"><?= $user['email'] ? htmlspecialchars($user['email']) : 'Sin email configurado' ?></div>
      </div>
      <i class="fa-solid fa-chevron-right"></i>
    </div>
    <div class="settings-input-wrap" id="edit-email">
      <input type="email" id="uEmail" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="tu@email.com">
      <button class="btn-save-sm" onclick="guardarDatos()">Guardar</button>
    </div>

    <div class="settings-row" onclick="toggleEdit('dni')">
      <div class="settings-row-icon sri-gray"><i class="fa-solid fa-id-card"></i></div>
      <div class="settings-row-body">
        <div class="settings-row-title">DNI</div>
        <div class="settings-row-sub"><?= $user['dni'] ? htmlspecialchars($user['dni']) : 'Sin DNI cargado' ?></div>
      </div>
      <i class="fa-solid fa-chevron-right"></i>
    </div>
    <div class="settings-input-wrap" id="edit-dni">
      <input type="text" id="uDni" value="<?= htmlspecialchars($user['dni'] ?? '') ?>" placeholder="Número de DNI">
      <button class="btn-save-sm" onclick="guardarDatos()">Guardar</button>
    </div>

    <div class="settings-row">
      <div class="settings-row-icon sri-gray"><i class="fa-solid fa-phone"></i></div>
      <div class="settings-row-body">
        <div class="settings-row-title">Celular</div>
        <div class="settings-row-sub"><?= htmlspecialchars($user['celular'] ?? '') ?></div>
      </div>
      <span class="settings-row-val">No editable</span>
    </div>

  </div>

  <!-- Seguridad -->
  <div class="settings-group-label" style="padding-left:20px">Seguridad</div>
  <div class="settings-group">
    <div class="settings-row" onclick="solicitarReset()">
      <div class="settings-row-icon sri-pink"><i class="fa-solid fa-lock"></i></div>
      <div class="settings-row-body">
        <div class="settings-row-title">Cambiar contraseña</div>
        <div class="settings-row-sub">Te enviamos un enlace seguro al email</div>
      </div>
      <i class="fa-solid fa-chevron-right"></i>
    </div>
  </div>

  <!-- Mis pedidos -->
  <div class="settings-group-label" style="padding-left:20px">Actividad</div>
  <div class="settings-group">
    <a href="mis-pedidos.php" class="settings-row" style="text-decoration:none">
      <div class="settings-row-icon sri-green"><i class="fa-solid fa-bag-shopping"></i></div>
      <div class="settings-row-body">
        <div class="settings-row-title">Mis pedidos</div>
        <div class="settings-row-sub"><?= $totalPedidos ?> pedido<?= $totalPedidos !== 1 ? 's' : '' ?> en total</div>
      </div>
      <i class="fa-solid fa-chevron-right"></i>
    </a>
  </div>

  <!-- Sesión -->
  <div class="settings-group-label" style="padding-left:20px">Sesión</div>
  <div class="settings-group">
    <div class="settings-row settings-row--danger" onclick="doLogout()">
      <div class="settings-row-icon sri-red"><i class="fa-solid fa-right-from-bracket"></i></div>
      <div class="settings-row-body">
        <div class="settings-row-title">Cerrar sesión</div>
      </div>
    </div>
  </div>

  <p style="text-align:center;font-size:11px;color:#ccc;padding:24px 0 8px">
    Canetto · v2.0 · <?= date('Y') ?>
  </p>

</div>
</div><!-- /page-wrap -->

<nav class="bottom-nav">
  <a href="index.php" class="bn-item">
    <i class="fa-solid fa-house"></i>
    <span>Inicio</span>
  </a>
  <a href="mis-pedidos.php" class="bn-item">
    <i class="fa-solid fa-bag-shopping"></i>
    <span>Mis pedidos</span>
  </a>
  <a href="index.php#sucursales" class="bn-item">
    <i class="fa-solid fa-location-dot"></i>
    <span>Sucursales</span>
  </a>
  <a href="mi-cuenta.php" class="bn-item active">
    <i class="fa-solid fa-user"></i>
    <span>Mi cuenta</span>
  </a>
</nav>

<script>
function setAlert(msg, type) {
  const el = document.getElementById('alertGlobal');
  el.textContent = msg;
  el.className = 'c-alert on ' + type;
  el.style.display = 'block';
  setTimeout(() => { el.classList.remove('on'); el.style.display = ''; }, 4000);
}

function toggleEdit(key) {
  const all = document.querySelectorAll('.settings-input-wrap');
  all.forEach(el => { if (el.id !== 'edit-'+key) el.classList.remove('on'); });
  document.getElementById('edit-'+key)?.classList.toggle('on');
}

async function guardarDatos() {
  const nombre   = document.getElementById('uNombre')?.value.trim() || '';
  const apellido = document.getElementById('uApellido')?.value.trim() || '';
  const dni      = document.getElementById('uDni')?.value.trim() || '';
  const email    = document.getElementById('uEmail')?.value.trim() || '';
  if (!nombre) { setAlert('El nombre es obligatorio', 'err'); return; }
  const fd = new FormData();
  fd.append('action', 'update_profile');
  fd.append('nombre',   nombre);
  fd.append('apellido', apellido);
  fd.append('dni',      dni);
  fd.append('email',    email);
  try {
    const d = await (await fetch('api/auth.php', { method: 'POST', body: fd })).json();
    if (d.success) { setAlert('✓ Datos actualizados', 'ok'); setTimeout(() => location.reload(), 1200); }
    else setAlert(d.message || 'Error al guardar', 'err');
  } catch { setAlert('Error de conexión', 'err'); }
}

async function solicitarReset() {
  <?php if (!($user['email'] ?? '')): ?>
  setAlert('Primero guardá tu email', 'err'); return;
  <?php endif; ?>
  const fd = new FormData(); fd.append('action', 'solicitar_reset');
  try {
    const d = await (await fetch('api/auth.php', { method: 'POST', body: fd })).json();
    if (d.success) setAlert('✓ Revisá tu email para cambiar la contraseña', 'ok');
    else setAlert(d.message || 'No se pudo enviar', 'err');
  } catch { setAlert('Error de conexión', 'err'); }
}

function doLogout() {
  localStorage.removeItem('canetto_cart_<?= $uid ?>');
  window.location.href = 'api/auth.php?action=logout_redirect';
}
</script>
<script src="transitions.js"></script>
</body>
</html>
