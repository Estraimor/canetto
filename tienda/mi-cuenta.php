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

try {
    $stmtDirs = $pdo->prepare("SELECT id, apodo, direccion, lat, lng FROM direcciones_guardadas WHERE usuario_idusuario = ? ORDER BY id DESC");
    $stmtDirs->execute([$uid]);
    $misDir = $stmtDirs->fetchAll();
} catch (Throwable $e) { $misDir = []; }

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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
@media (min-width: 1024px) {
  body, body.has-bottom-nav { padding-bottom: 0 !important; }
  body { background: #f0eeec !important; }
  .bottom-nav { display: none !important; }
  body.has-bottom-nav .t-footer { display: block !important; }
  #page-wrap { padding-bottom: 0 !important; }
  .perfil-mini-stats { display: none !important; }
  #alertGlobal { display: none; }

  /* NAV */
  .t-nav { padding: 0 48px; box-shadow: 0 1px 0 #e8e8e8; }
  .t-btn-label { display: inline !important; }
  .t-btn { height: 40px; }
  .t-btn .t-cart-badge { display: none; }

  /* HERO — full width, contenido centrado adentro */
  .cuenta-wrap { max-width: none !important; padding: 0 !important; width: 100% !important; }

  .perfil-hero {
    flex-direction: row;
    align-items: center;
    gap: 32px;
    padding: 36px 48px;
    background: #1a0d11;
    border-radius: 0;
    margin: 0;
    width: 100%;
    box-sizing: border-box;
    min-height: 0;
  }
  .perfil-avatar {
    width: 64px; height: 64px; font-size: 24px; flex-shrink: 0;
    background: linear-gradient(135deg, #c88e99, #a46678);
    box-shadow: 0 0 0 3px rgba(200,142,153,.3);
    border-width: 0;
  }
  .perfil-info { flex: 1; min-width: 0; }
  .perfil-name  { font-size: 20px; font-weight: 700; }
  .perfil-phone { font-size: 13px; opacity: .6; margin-top: 2px; }
  .perfil-badge { font-size: 11px; margin-top: 5px; opacity: .5; }
  .perfil-hero-stats { display: flex; gap: 8px; flex-shrink: 0; }
  .perfil-stat-item {
    text-align: center; padding: 12px 20px;
    background: rgba(255,255,255,.07);
    border-radius: 12px; border: 1px solid rgba(255,255,255,.1);
    min-width: 80px;
  }
  .perfil-stat-val { font-size: 24px; font-weight: 800; color: #fff; line-height: 1; }
  .perfil-stat-lbl { font-size: 9px; color: rgba(255,255,255,.4); font-weight: 700;
    text-transform: uppercase; letter-spacing: .8px; margin-top: 4px; }

  /* CONTENIDO centrado */
  .cuenta-body {
    max-width: 960px;
    margin: 0 auto;
    padding: 32px 48px 64px;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 24px 32px;
    align-items: start;
    box-sizing: border-box;
    width: 100%;
  }
  .cuenta-col-item { display: flex; flex-direction: column; gap: 8px; }
  .settings-group {
    margin: 0; border-radius: 12px; overflow: hidden; background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 2px 8px rgba(0,0,0,.04);
  }
  .settings-group-label {
    padding: 0 0 6px !important; margin: 0 !important;
    font-size: 10px !important; font-weight: 800 !important;
    color: #aaa !important; text-transform: uppercase !important; letter-spacing: 1px !important;
  }
  .settings-row { padding: 13px 18px; }
  .settings-row-title { font-size: 14px; font-weight: 600; }
  .settings-row-sub { font-size: 12px; color: #94a3b8; }
  .settings-input-wrap { padding: 0 18px 14px; }
}

/* Modal de dirección */
#modalDirOverlay{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);
  z-index:9000;align-items:flex-end;justify-content:center;
}
#modalDirOverlay.on{ display:flex; }
#modalDir{
  background:#fff;border-radius:24px 24px 0 0;width:100%;
  max-width:560px;max-height:92vh;overflow-y:auto;
  padding:0 0 32px;animation:slideUp .3s ease;
}
@keyframes slideUp{ from{transform:translateY(100%)} to{transform:translateY(0)} }
.mdir-handle{
  width:40px;height:4px;background:#e2e8f0;
  border-radius:4px;margin:12px auto 0;
}
.mdir-title{
  font-size:18px;font-weight:700;color:#1e293b;
  padding:16px 20px 12px;
}
.mdir-body{ padding:0 20px; }
.mdir-fg{ margin-bottom:14px; }
.mdir-fg label{
  display:block;font-size:11px;font-weight:700;
  text-transform:uppercase;letter-spacing:.5px;
  color:#94a3b8;margin-bottom:7px;
}
.mdir-fg input{
  width:100%;padding:13px 14px;border:2px solid #e8d0d5;
  border-radius:12px;font-size:15px;font-family:inherit;
  color:#1e293b;outline:none;background:#fdf8f9;
  transition:border-color .2s,box-shadow .2s;
  box-sizing:border-box;
}
.mdir-fg input:focus{ border-color:#c88e99;box-shadow:0 0 0 4px rgba(200,142,153,.12);background:#fff; }

/* Autocomplete dropdown */
#acDropdown{
  position:absolute;left:0;right:0;top:100%;
  background:#fff;border:2px solid #e8d0d5;border-top:none;
  border-radius:0 0 12px 12px;z-index:10;
  max-height:200px;overflow-y:auto;
  box-shadow:0 8px 24px rgba(0,0,0,.12);
}
.ac-item{
  padding:11px 14px;font-size:13px;color:#334155;
  cursor:pointer;border-bottom:1px solid #f1e8ea;line-height:1.4;
  transition:background .15s;
}
.ac-item:last-child{ border-bottom:none; }
.ac-item:hover{ background:#fdf0f3; }
.ac-loading{ padding:12px 14px;font-size:13px;color:#94a3b8;text-align:center; }

.mdir-wrap-relative{ position:relative; }

/* Mapa */
#mapaDirWrap{
  border-radius:14px;overflow:hidden;
  border:2px solid #e8d0d5;margin-bottom:14px;
  display:none;
}
#mapaDirWrap.on{ display:block; }
#mapaDir{ height:220px;width:100%; }

/* Botones mapa */
.btn-geo-dir{
  width:100%;padding:11px;background:#f0f9ff;
  border:1.5px solid #bfdbfe;border-radius:11px;
  color:#1d4ed8;font-size:13px;font-weight:600;
  cursor:pointer;font-family:inherit;transition:background .18s;
  margin-bottom:10px;
}
.btn-geo-dir:hover{ background:#dbeafe; }
#geoDirStatus{ font-size:12px;color:#64748b;min-height:16px;margin-bottom:10px; }

.btn-guardar-dir{
  width:100%;padding:15px;border:none;border-radius:13px;
  background:linear-gradient(135deg,#c88e99,#a46678);
  color:#fff;font-size:16px;font-weight:700;
  cursor:pointer;font-family:inherit;
  box-shadow:0 4px 14px rgba(164,102,120,.35);
  transition:opacity .18s;
}
.btn-guardar-dir:hover{ opacity:.9; }
.btn-cancelar-dir{
  width:100%;padding:13px;border:none;border-radius:13px;
  background:#f1f5f9;color:#64748b;font-size:15px;
  font-weight:600;cursor:pointer;font-family:inherit;
  margin-top:8px;
}
</style>
</head>
<body class="has-bottom-nav" style="background:#f5f5f5">
<div id="page-wrap">

<header class="t-nav">
  <a href="index.php" class="t-brand">
    <div class="t-brand-icon">
      <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" class="t-brand-logo">
    </div>
    <span class="t-brand-name">Canetto</span>
  </a>
  <div class="t-actions" style="display:flex;align-items:center;gap:8px">
    <a href="mis-pedidos.php" class="t-btn" title="Mis pedidos" style="font-size:13px;font-weight:700;width:auto;padding:0 16px;border-radius:22px;gap:6px;display:flex;align-items:center">
      <i class="fa-solid fa-bag-shopping" style="font-size:14px"></i>
      <span class="t-btn-label" style="display:none">Mis pedidos</span>
    </a>
    <a href="index.php" class="t-btn" title="Tienda" style="font-size:13px;font-weight:700;width:auto;padding:0 16px;border-radius:22px;gap:6px;display:flex;align-items:center;background:#111;color:#fff">
      <i class="fa-solid fa-cart-shopping" style="font-size:14px"></i>
      <span class="t-btn-label" style="display:none">Tienda</span>
    </a>
  </div>
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
    <!-- Stats mobile -->
    <div class="perfil-mini-stats">
      <div class="perfil-mini-stat">
        <strong><?= $totalPedidos ?></strong>
        <span>Pedidos</span>
      </div>
      <div class="perfil-mini-stat">
        <strong><?= count($misDir) ?></strong>
        <span>Direcciones</span>
      </div>
    </div>
    <!-- Stats desktop -->
    <div class="perfil-hero-stats">
      <div class="perfil-stat-item">
        <div class="perfil-stat-val"><?= $totalPedidos ?></div>
        <div class="perfil-stat-lbl">Pedidos</div>
      </div>
      <div class="perfil-stat-item">
        <div class="perfil-stat-val"><?= count($misDir) ?></div>
        <div class="perfil-stat-lbl">Direcciones</div>
      </div>
    </div>
  </div>

  <div id="alertGlobal" class="c-alert" style="margin:16px 16px 0"></div>

  <!-- ══ BODY: 2 columnas en desktop ══ -->
  <div class="cuenta-body">

    <!-- ── Columna 1 ── -->
    <div class="cuenta-col-item">
      <div class="settings-group-label" style="padding-left:4px">Datos de la cuenta</div>
      <div class="settings-group">
        <div class="settings-row" onclick="toggleEdit('nombre')">
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

        <div class="settings-row" onclick="toggleEdit('celular')">
          <div class="settings-row-body">
            <div class="settings-row-title">Celular</div>
            <div class="settings-row-sub"><?= htmlspecialchars($user['celular'] ?? '') ?></div>
          </div>
          <i class="fa-solid fa-chevron-right"></i>
        </div>
        <div class="settings-input-wrap" id="edit-celular">
          <?php if ($user['email']): ?>
            <p style="margin:0 0 10px;font-size:13px;color:#666;line-height:1.6;">
              Te enviaremos un enlace a <strong><?= htmlspecialchars($user['email']) ?></strong> para confirmar el cambio.
            </p>
            <button class="btn-save-sm" onclick="solicitarCambioCelular()">Enviar enlace al email</button>
          <?php else: ?>
            <p style="margin:0;font-size:13px;color:#e74c3c;line-height:1.6;">
              Agregá un email primero para poder cambiar el celular.
            </p>
          <?php endif; ?>
        </div>
      </div><!-- /settings-group -->
    </div><!-- /col-1 -->

    <!-- ── Columna 2 ── -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- Seguridad -->
      <div class="cuenta-col-item">
        <div class="settings-group-label" style="padding-left:4px">Seguridad</div>
        <div class="settings-group">
          <div class="settings-row" onclick="solicitarReset()">
            <div class="settings-row-body">
              <div class="settings-row-title">Cambiar contraseña</div>
              <div class="settings-row-sub">Te enviamos un enlace seguro al email</div>
            </div>
            <i class="fa-solid fa-chevron-right"></i>
          </div>
        </div>
      </div>

      <!-- Actividad -->
      <div class="cuenta-col-item">
        <div class="settings-group-label" style="padding-left:4px">Actividad</div>
        <div class="settings-group">
          <a href="mis-pedidos.php" class="settings-row" style="text-decoration:none">
            <div class="settings-row-body">
              <div class="settings-row-title">Mis pedidos</div>
              <div class="settings-row-sub"><?= $totalPedidos ?> pedido<?= $totalPedidos !== 1 ? 's' : '' ?> realizados</div>
            </div>
            <i class="fa-solid fa-chevron-right"></i>
          </a>
        </div>
      </div>

      <!-- Direcciones -->
      <div class="cuenta-col-item">
        <div class="settings-group-label" style="padding-left:4px">Direcciones de envío</div>
        <div class="settings-group" id="dirGroup">
          <?php if (!empty($misDir)): foreach ($misDir as $d): ?>
          <div class="settings-row dir-row" data-id="<?= (int)$d['id'] ?>">
            <div class="settings-row-body">
              <div class="settings-row-title"><?= htmlspecialchars($d['apodo']) ?></div>
              <div class="settings-row-sub"><?= htmlspecialchars($d['direccion']) ?></div>
            </div>
            <button onclick="borrarDirPerfil(<?= (int)$d['id'] ?>,this)"
              style="background:none;border:none;color:#e74c3c;cursor:pointer;font-size:16px;padding:4px 8px;">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </div>
          <?php endforeach; else: ?>
          <div class="settings-row">
            <div class="settings-row-body" id="dirVacioMsg">
              <div class="settings-row-title" style="color:#94a3b8;">Sin direcciones guardadas</div>
              <div class="settings-row-sub">Guardá ubicaciones desde el checkout</div>
            </div>
          </div>
          <?php endif; ?>
          <div class="settings-row" onclick="agregarDireccionPerfil()" style="cursor:pointer">
            <div class="settings-row-body">
              <div class="settings-row-title" style="color:#3b82f6;">Agregar dirección</div>
              <div class="settings-row-sub">Casa, trabajo, etc.</div>
            </div>
            <i class="fa-solid fa-chevron-right" style="color:#3b82f6;"></i>
          </div>
        </div>
      </div>

      <!-- Sesión -->
      <div class="cuenta-col-item">
        <div class="settings-group-label" style="padding-left:4px">Sesión</div>
        <div class="settings-group">
          <div class="settings-row settings-row--danger" onclick="doLogout()">
            <div class="settings-row-body">
              <div class="settings-row-title">Cerrar sesión</div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /col-2 -->

  </div><!-- /cuenta-body -->

</div><!-- /cuenta-wrap -->
</div><!-- /page-wrap -->

<!-- ── MODAL AGREGAR DIRECCIÓN ───────────────────────── -->
<div id="modalDirOverlay" onclick="if(event.target===this)cerrarModalDir()">
  <div id="modalDir">
    <div class="mdir-handle"></div>
    <div class="mdir-title"><i class="fa-solid fa-location-dot" style="color:#c88e99;margin-right:8px"></i>Nueva dirección</div>
    <div class="mdir-body">

      <div class="mdir-fg">
        <label>Nombre (Casa, Trabajo, etc.)</label>
        <input type="text" id="dirApodo" placeholder="Ej: Casa, Trabajo, Casa de mamá..." maxlength="50">
      </div>

      <div class="mdir-fg">
        <label>Dirección</label>
        <div class="mdir-wrap-relative">
          <input type="text" id="dirTexto" placeholder="Ej: Corrientes 1234, Buenos Aires"
            autocomplete="off" oninput="onDirInput(this)">
          <div id="acDropdown" style="display:none"></div>
        </div>
      </div>

      <button class="btn-geo-dir" onclick="usarUbicacionDir()">
        <i class="fa-solid fa-location-crosshairs" style="margin-right:6px"></i>Usar mi ubicación actual
      </button>
      <div id="geoDirStatus"></div>

      <div id="mapaDirWrap">
        <div id="mapaDir"></div>
      </div>

      <input type="hidden" id="dirLat">
      <input type="hidden" id="dirLng">

      <button class="btn-guardar-dir" onclick="confirmarGuardarDir()">
        Guardar dirección
      </button>
      <button class="btn-cancelar-dir" onclick="cerrarModalDir()">Cancelar</button>
    </div>
  </div>
</div>

<footer class="t-footer">
  <div class="t-footer-brand">Canetto</div>
  <div class="t-footer-tag">Cookies hechas con amor</div>
</footer>

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
const swalOk  = (titulo, texto) => Swal.fire({
  icon: 'success', title: titulo, text: texto,
  confirmButtonColor: '#c88e99', confirmButtonText: 'Listo',
  borderRadius: '16px'
});
const swalErr = (titulo, texto) => Swal.fire({
  icon: 'error', title: titulo, text: texto,
  confirmButtonColor: '#c88e99', confirmButtonText: 'Entendido'
});
const swalInfo = (titulo, texto, html) => Swal.fire({
  icon: 'info', title: titulo, ...(html ? {html} : {text: texto}),
  confirmButtonColor: '#c88e99', confirmButtonText: 'Listo'
});

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
  if (!nombre) { swalErr('Falta el nombre', 'El nombre es obligatorio.'); return; }
  const fd = new FormData();
  fd.append('action', 'update_profile');
  fd.append('nombre',   nombre);
  fd.append('apellido', apellido);
  fd.append('dni',      dni);
  fd.append('email',    email);
  try {
    const d = await (await fetch('api/auth.php', { method: 'POST', body: fd })).json();
    if (d.success) {
      await swalOk('¡Guardado!', 'Tus datos fueron actualizados correctamente.');
      location.reload();
    } else {
      swalErr('No se pudo guardar', d.message || 'Intentá nuevamente.');
    }
  } catch { swalErr('Error de conexión', 'No se pudo conectar. Intentá nuevamente.'); }
}

async function solicitarReset() {
  <?php if (!($user['email'] ?? '')): ?>
  swalErr('Sin email', 'Primero guardá tu dirección de email para poder cambiar la contraseña.'); return;
  <?php endif; ?>
  const fd = new FormData(); fd.append('action', 'solicitar_reset');
  try {
    const d = await (await fetch('api/auth.php', { method: 'POST', body: fd })).json();
    if (d.success) {
      swalInfo('¡Email enviado! 📧', null,
        `Te mandamos un enlace a <strong><?= htmlspecialchars($user['email'] ?? '') ?></strong>.<br><br>
         Revisá tu bandeja de entrada y hacé clic en el enlace para cambiar tu contraseña.`);
    } else {
      swalErr('No se pudo enviar', d.message || 'Intentá nuevamente.');
    }
  } catch { swalErr('Error de conexión', 'No se pudo conectar. Intentá nuevamente.'); }
}

async function solicitarCambioCelular() {
  const fd = new FormData(); fd.append('action', 'solicitar_cambio_celular');
  try {
    const d = await (await fetch('api/auth.php', { method: 'POST', body: fd })).json();
    if (d.success) {
      swalInfo('¡Email enviado! 📱', null,
        `Te mandamos un enlace a <strong><?= htmlspecialchars($user['email'] ?? '') ?></strong>.<br><br>
         Hacé clic en el enlace para ingresar tu nuevo número de celular.<br>
         <small style="color:#aaa">El enlace vence en 1 hora.</small>`);
    } else {
      swalErr('No se pudo enviar', d.message || 'Intentá nuevamente.');
    }
  } catch { swalErr('Error de conexión', 'No se pudo conectar. Intentá nuevamente.'); }
}

async function agregarDireccionPerfil(){
  const {value:apodo} = await Swal.fire({
    title:'Nueva dirección',
    input:'text',
    inputLabel:'¿Cómo la llamás?',
    inputPlaceholder:'Ej: Casa, Trabajo, Casa de mamá...',
    inputAttributes:{maxlength:50},
    showCancelButton:true, cancelButtonText:'Cancelar',
    confirmButtonText:'Siguiente →', confirmButtonColor:'#c88e99',
    inputValidator:v=>!v&&'Escribí un nombre'
  });
  if(!apodo) return;
  const {value:dir} = await Swal.fire({
    title:'Dirección',
    input:'text',
    inputLabel:'Ingresá la dirección completa',
    inputPlaceholder:'Ej: Corrientes 1234, CABA',
    showCancelButton:true, cancelButtonText:'Cancelar',
    confirmButtonText:'Guardar', confirmButtonColor:'#c88e99',
    inputValidator:v=>!v&&'Ingresá la dirección'
  });
  if(!dir) return;
  const fd=new FormData();
  fd.append('action','guardar_direccion');
  fd.append('apodo',apodo);
  fd.append('direccion',dir);
  try{
    const d=await(await fetch('api/auth.php',{method:'POST',body:fd})).json();
    if(d.success){
      Swal.fire({icon:'success',title:'¡Guardada!',text:'Tu dirección fue agregada.',
        confirmButtonColor:'#c88e99',confirmButtonText:'Listo'}).then(()=>location.reload());
    } else Swal.fire({icon:'error',title:'Error',text:d.message||'No se pudo guardar',confirmButtonColor:'#c88e99'});
  }catch{ Swal.fire({icon:'error',title:'Error de conexión',confirmButtonColor:'#c88e99'}); }
}

async function borrarDirPerfil(id, btn){
  const {isConfirmed}=await Swal.fire({
    title:'¿Eliminar dirección?', icon:'question',
    showCancelButton:true, cancelButtonText:'No',
    confirmButtonText:'Sí, eliminar', confirmButtonColor:'#e74c3c'
  });
  if(!isConfirmed) return;
  const fd=new FormData();fd.append('action','borrar_direccion');fd.append('id',id);
  try{
    const d=await(await fetch('api/auth.php',{method:'POST',body:fd})).json();
    if(d.success){
      const row=btn.closest('.dir-row');
      if(row) row.remove();
      const group=document.getElementById('dirGroup');
      const rows=group?.querySelectorAll('.dir-row');
      if(!rows||rows.length===0){
        const msg=document.getElementById('dirVacioMsg');
        if(!msg){
          const placeholder=document.createElement('div');
          placeholder.className='settings-row';
          placeholder.innerHTML=`<div class="settings-row-icon sri-gray"><i class="fa-solid fa-location-dot"></i></div><div class="settings-row-body"><div class="settings-row-title" style="color:#94a3b8">Sin direcciones guardadas</div><div class="settings-row-sub">Guardá ubicaciones desde el checkout</div></div>`;
          group?.insertBefore(placeholder,group.firstChild);
        }
      }
    }
  }catch{}
}

function doLogout() {
  localStorage.removeItem('canetto_cart_<?= $uid ?>');
  window.location.href = 'api/auth.php?action=logout_redirect';
}
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ── Modal dirección ───────────────────────────────── */
let _mapaDir = null, _pinDir = null, _acTimer = null;

function abrirModalDir() {
  document.getElementById('dirApodo').value = '';
  document.getElementById('dirTexto').value = '';
  document.getElementById('dirLat').value   = '';
  document.getElementById('dirLng').value   = '';
  document.getElementById('acDropdown').style.display = 'none';
  document.getElementById('mapaDirWrap').classList.remove('on');
  document.getElementById('geoDirStatus').textContent = '';
  document.getElementById('modalDirOverlay').classList.add('on');
  if (_mapaDir) { _mapaDir.remove(); _mapaDir = null; _pinDir = null; }
}

function cerrarModalDir() {
  document.getElementById('modalDirOverlay').classList.remove('on');
}

// Reemplazar la función existente
window.agregarDireccionPerfil = abrirModalDir;

/* Autocomplete Nominatim */
function onDirInput(el) {
  clearTimeout(_acTimer);
  const q = el.value.trim();
  const dd = document.getElementById('acDropdown');
  if (q.length < 4) { dd.style.display = 'none'; return; }
  _acTimer = setTimeout(async () => {
    dd.innerHTML = '<div class="ac-loading">Buscando...</div>';
    dd.style.display = 'block';
    try {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=5&addressdetails=1&countrycodes=ar`,
        { headers: { 'Accept-Language': 'es' } }
      );
      const data = await res.json();
      if (!data.length) { dd.innerHTML = '<div class="ac-loading">Sin resultados</div>'; return; }
      dd.innerHTML = '';
      data.forEach(r => {
        const div = document.createElement('div');
        div.className = 'ac-item';
        div.textContent = r.display_name;
        div.addEventListener('click', () => seleccionarDir(parseFloat(r.lat), parseFloat(r.lon), r.display_name));
        dd.appendChild(div);
      });
    } catch { dd.innerHTML = '<div class="ac-loading">Error de búsqueda</div>'; }
  }, 400);
}

function seleccionarDir(lat, lng, nombre) {
  document.getElementById('dirTexto').value = nombre;
  document.getElementById('dirLat').value   = lat;
  document.getElementById('dirLng').value   = lng;
  document.getElementById('acDropdown').style.display = 'none';
  _initMapaDir(parseFloat(lat), parseFloat(lng));
}

/* Geolocalización */
function usarUbicacionDir() {
  const status = document.getElementById('geoDirStatus');
  if (!navigator.geolocation) { status.textContent = 'Tu navegador no soporta geolocalización.'; return; }
  status.textContent = 'Obteniendo ubicación...';
  navigator.geolocation.getCurrentPosition(
    pos => {
      const { latitude: lat, longitude: lng } = pos.coords;
      document.getElementById('dirLat').value = lat;
      document.getElementById('dirLng').value = lng;
      status.textContent = '';
      _initMapaDir(lat, lng);
      _geocodeInversoDir(lat, lng);
    },
    () => { status.textContent = 'No se pudo obtener la ubicación.'; }
  );
}

/* Leaflet */
function _initMapaDir(lat, lng) {
  const wrap = document.getElementById('mapaDirWrap');
  wrap.classList.add('on');
  if (_mapaDir) {
    _mapaDir.setView([lat, lng], 16);
    _pinDir.setLatLng([lat, lng]);
    return;
  }
  setTimeout(() => {
    _mapaDir = L.map('mapaDir').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap'
    }).addTo(_mapaDir);
    _pinDir = L.marker([lat, lng], { draggable: true }).addTo(_mapaDir);
    _pinDir.on('dragend', e => {
      const p = e.target.getLatLng();
      document.getElementById('dirLat').value = p.lat;
      document.getElementById('dirLng').value = p.lng;
      _geocodeInversoDir(p.lat, p.lng);
    });
    _mapaDir.on('click', e => {
      _pinDir.setLatLng(e.latlng);
      document.getElementById('dirLat').value = e.latlng.lat;
      document.getElementById('dirLng').value = e.latlng.lng;
      _geocodeInversoDir(e.latlng.lat, e.latlng.lng);
    });
  }, 50);
}

async function _geocodeInversoDir(lat, lng) {
  try {
    const r = await fetch(
      `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
      { headers: { 'Accept-Language': 'es' } }
    );
    const d = await r.json();
    if (d.display_name) document.getElementById('dirTexto').value = d.display_name;
  } catch {}
}

/* Guardar */
async function confirmarGuardarDir() {
  const apodo = document.getElementById('dirApodo').value.trim();
  const dir   = document.getElementById('dirTexto').value.trim();
  const lat   = document.getElementById('dirLat').value;
  const lng   = document.getElementById('dirLng').value;
  if (!apodo) { swalErr('Falta el nombre', 'Poné un nombre como "Casa" o "Trabajo".'); return; }
  if (!dir)   { swalErr('Falta la dirección', 'Ingresá o buscá una dirección.'); return; }
  const fd = new FormData();
  fd.append('action', 'guardar_direccion');
  fd.append('apodo', apodo);
  fd.append('direccion', dir);
  if (lat) fd.append('lat', lat);
  if (lng) fd.append('lng', lng);
  try {
    const d = await (await fetch('api/auth.php', { method: 'POST', body: fd })).json();
    if (d.success) {
      cerrarModalDir();
      await swalOk('¡Guardada!', 'Tu dirección fue agregada.');
      location.reload();
    } else {
      swalErr('No se pudo guardar', d.message || 'Intentá nuevamente.');
    }
  } catch { swalErr('Error de conexión', 'Intentá nuevamente.'); }
}
</script>
<script src="transitions.js"></script>
</body>
</html>
