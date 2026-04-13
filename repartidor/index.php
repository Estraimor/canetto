<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/google_config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$repId          = $_SESSION['repartidor_id']     ?? null;
$repNombre      = $_SESSION['repartidor_nombre'] ?? '';
$googleClientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#0f172a">
<title>Canetto — Repartidor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="repartidor.css">
<?php if ($googleClientId): ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>
<style>
.or-divider{display:flex;align-items:center;gap:10px;margin:16px 0 12px;color:#475569;font-size:12px}
.or-divider::before,.or-divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.12)}
.btn-google-rep{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:13px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;color:#3c4043;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;transition:.2s;margin-top:2px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.btn-google-rep:hover{border-color:#c88e99;box-shadow:0 2px 8px rgba(200,142,153,.2);transform:translateY(-1px)}
.btn-google-rep img{width:18px;height:18px}
</style>
</head>
<body>

<!-- ══════════════════════════════════════
     LOGIN
══════════════════════════════════════ -->
<div id="appLogin" class="app-screen <?= $repId ? 'hidden' : '' ?>">
  <div class="login-mesh">
    <div class="login-mesh-dot"></div>
  </div>
  <div class="login-dots"></div>

  <div class="login-wrap">
    <div class="login-card">
      <div class="login-brand">
        <div class="login-logo">
          <i class="fa-solid fa-motorcycle"></i>
        </div>
        <div class="login-title">Canetto</div>
        <div class="login-sub">Plataforma de repartidores</div>
      </div>

      <div id="loginAlert" class="alert" style="display:none">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span id="loginAlertMsg"></span>
      </div>

      <div class="form-field">
        <label><i class="fa-solid fa-mobile-screen"></i> Celular</label>
        <input type="tel" id="lCelular" placeholder="Ej: 1123456789" autocomplete="tel">
      </div>
      <div class="form-field">
        <label><i class="fa-solid fa-lock"></i> Contraseña</label>
        <div class="input-pw-wrap">
          <input type="password" id="lPassword" placeholder="••••••">
          <button type="button" class="btn-eye" onclick="togglePw('lPassword',this)">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>
      <button class="btn-primary" id="btnLogin" onclick="doLogin()">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar
      </button>

      <div style="text-align:right;margin-top:6px;margin-bottom:2px;">
        <a href="<?= base() ?>/login/recuperar_password.php"
           style="font-size:12px;color:#c88e99;text-decoration:none;">¿Olvidaste tu contraseña?</a>
      </div>

      <div class="or-divider"><span>o</span></div>

      <!-- Botón Google — se activa automáticamente cuando GOOGLE_CLIENT_ID esté configurado -->
      <div id="googleBtnWrap" style="display:none;justify-content:center"></div>
      <button id="btnGoogleFallback" class="btn-google-rep" type="button" onclick="initGooglePrompt()">
        <img src="https://developers.google.com/identity/images/g-logo.png" alt="G">
        Ingresar con Google
      </button>

    </div>
    <div class="login-badge">Canetto · v3.0</div>
  </div>
</div>

<!-- ══════════════════════════════════════
     DASHBOARD
══════════════════════════════════════ -->
<div id="appDash" class="app-screen <?= $repId ? '' : 'hidden' ?>">

  <!-- Header -->
  <div class="dash-header">
    <div class="dash-header-left">
      <div class="dash-avatar" id="dashAvatar"></div>
      <div>
        <div class="dash-title">Mis entregas</div>
        <div class="dash-sub" id="dashNombre"><?= htmlspecialchars($repNombre) ?></div>
      </div>
    </div>
    <button class="btn-logout" onclick="doLogout()" title="Cerrar sesión">
      <i class="fa-solid fa-right-from-bracket"></i>
    </button>
  </div>

  <!-- Tabs -->
  <div class="tab-nav">
    <button class="tab-btn active" onclick="switchTab('pedidos',this)">
      <i class="fa-solid fa-motorcycle"></i><span>Pedidos</span>
    </button>
    <button class="tab-btn" onclick="switchTab('historial',this)">
      <i class="fa-solid fa-clock-rotate-left"></i><span>Historial</span>
    </button>
    <button class="tab-btn" onclick="switchTab('perfil',this)">
      <i class="fa-solid fa-user-gear"></i><span>Perfil</span>
    </button>
    <button class="tab-btn" onclick="switchTab('soporte',this)">
      <i class="fa-solid fa-headset"></i><span>Soporte</span>
    </button>
  </div>

  <!-- Tab Pedidos -->
  <div id="tabPedidos" class="tab-content active">
    <div id="pedidosList" class="pedidos-list"></div>
    <button class="btn-refresh" onclick="cargarPedidos()">
      <i class="fa-solid fa-arrows-rotate"></i> Actualizar
    </button>
  </div>

  <!-- Tab Historial -->
  <div id="tabHistorial" class="tab-content">
    <div id="historialList" class="pedidos-list"></div>
  </div>

  <!-- Tab Perfil -->
  <div id="tabPerfil" class="tab-content">
    <div class="perfil-wrap">
      <div class="perfil-hero">
        <div class="perfil-avatar-big" id="perfilAvatar"></div>
        <div class="perfil-name"  id="perfilNombreDisplay"></div>
        <div class="perfil-tel">
          <i class="fa-solid fa-phone"></i>
          <span id="perfilTelDisplay"></span>
        </div>
        <div class="perfil-role-chip">Repartidor</div>
      </div>

      <div class="perfil-section">
        <div class="perfil-section-title">Editar información</div>
        <div class="perfil-card">
          <div id="perfilAlert"   class="alert"         style="display:none">
            <i class="fa-solid fa-circle-exclamation"></i><span id="perfilAlertMsg"></span>
          </div>
          <div id="perfilSuccess" class="alert-success" style="display:none">
            <i class="fa-solid fa-circle-check"></i><span>¡Perfil actualizado correctamente!</span>
          </div>

          <div class="form-field">
            <label><i class="fa-solid fa-user"></i> Nombre</label>
            <input type="text" id="pNombre" placeholder="Tu nombre">
          </div>
          <div class="form-field">
            <label><i class="fa-solid fa-user"></i> Apellido</label>
            <input type="text" id="pApellido" placeholder="Tu apellido">
          </div>
          <div class="form-field">
            <label><i class="fa-solid fa-mobile-screen"></i> Celular</label>
            <input type="tel" id="pCelular" placeholder="Ej: 1123456789">
          </div>
          <div class="form-field">
            <label><i class="fa-solid fa-envelope"></i> Email <span class="label-opt">(para recuperar contraseña)</span></label>
            <input type="email" id="pEmail" placeholder="tu@email.com">
          </div>
          <div class="form-field">
            <label><i class="fa-solid fa-id-card"></i> DNI</label>
            <input type="text" id="pDni" placeholder="Número de DNI">
          </div>
          <button class="btn-primary" id="btnGuardarPerfil" onclick="guardarPerfil()">
            <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
          </button>
          <div style="margin-top:14px;text-align:center;">
            <a href="<?= base() ?>/login/recuperar_password.php"
               style="font-size:13px;color:#c88e99;text-decoration:none;">
              <i class="fa-solid fa-lock"></i> Cambiar contraseña por email
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab Soporte -->
  <div id="tabSoporte" class="tab-content">
    <div class="perfil-wrap">

      <div class="perfil-section">
        <div class="perfil-section-title">Contacto &amp; Soporte</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px;">
          <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:16px;text-align:center;">
            <div style="font-size:24px;margin-bottom:5px;">📦</div>
            <div style="font-size:12px;font-weight:700;margin-bottom:3px;">Problema con pedido</div>
            <div style="font-size:11px;opacity:.6;line-height:1.4;">Dirección incorrecta, cliente no encontrado, etc.</div>
          </div>
          <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:16px;text-align:center;">
            <div style="font-size:24px;margin-bottom:5px;">⚙️</div>
            <div style="font-size:12px;font-weight:700;margin-bottom:3px;">Error en la app</div>
            <div style="font-size:11px;opacity:.6;line-height:1.4;">Algo no carga o funciona mal.</div>
          </div>
        </div>

        <div class="perfil-card">
          <div id="soporteAlert" class="alert" style="display:none">
            <i class="fa-solid fa-circle-exclamation"></i><span id="soporteAlertMsg"></span>
          </div>
          <div id="soporteSuccess" class="alert-success" style="display:none">
            <i class="fa-solid fa-circle-check"></i><span>¡Mensaje enviado! Te responderemos a la brevedad.</span>
          </div>

          <div class="form-field">
            <label><i class="fa-solid fa-tag"></i> Tipo de consulta</label>
            <select id="soporteTipo" style="width:100%;padding:12px 14px;
              border:1.5px solid rgba(255,255,255,.15);border-radius:10px;
              background:rgba(255,255,255,.08);color:inherit;font-family:inherit;font-size:14px;outline:none;">
              <option value="Problema con pedido">Problema con pedido</option>
              <option value="Error en la app">Error en la app</option>
              <option value="Consulta general">Consulta general</option>
              <option value="Otro">Otro</option>
            </select>
          </div>

          <div class="form-field">
            <label><i class="fa-solid fa-comment"></i> Detalle del problema</label>
            <textarea id="soporteDetalle" rows="4"
              placeholder="Describí el problema con el mayor detalle posible..."
              style="width:100%;padding:12px 14px;border:1.5px solid rgba(255,255,255,.15);
                border-radius:10px;background:rgba(255,255,255,.08);color:inherit;
                font-family:inherit;font-size:14px;resize:vertical;outline:none;box-sizing:border-box;"></textarea>
          </div>

          <button class="btn-primary" id="btnEnviarSoporte" onclick="enviarSoporte()">
            <i class="fa-solid fa-paper-plane"></i> Enviar mensaje
          </button>
        </div>
      </div>

      <div class="perfil-section">
        <div class="perfil-section-title">Contacto directo</div>
        <div class="perfil-card" style="display:flex;flex-direction:column;gap:14px;">
          <a href="mailto:soporte@canettocookies.com"
             style="display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(200,142,153,.2);
              display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">📧</div>
            <div>
              <div style="font-size:13px;font-weight:700;">Email de soporte</div>
              <div style="font-size:12px;opacity:.65;">soporte@canettocookies.com</div>
            </div>
          </a>
          <a href="https://wa.me/5491100000000" target="_blank"
             style="display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(37,211,102,.2);
              display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;">💬</div>
            <div>
              <div style="font-size:13px;font-weight:700;">WhatsApp de soporte</div>
              <div style="font-size:12px;opacity:.65;">Respuesta rápida para urgencias</div>
            </div>
          </a>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- ══════════════════════════════════════
     TEMPLATE: PEDIDO ACTIVO
══════════════════════════════════════ -->
<template id="tplPedido">
  <div class="pedido-card">
    <div class="pedido-accent"></div>
    <div class="pedido-body">
      <div class="pedido-head">
        <div class="pedido-num-wrap">
          <span class="pedido-badge badge-moving">EN CAMINO</span>
          <span class="pedido-num"></span>
        </div>
        <span class="pedido-total"></span>
      </div>
      <div class="pedido-row">
        <div class="pedido-row-icon icon-indigo"><i class="fa-solid fa-user"></i></div>
        <span class="pedido-nombre"></span>
      </div>
      <div class="pedido-row">
        <div class="pedido-row-icon icon-amber"><i class="fa-solid fa-location-dot"></i></div>
        <span class="pedido-dir-txt"></span>
      </div>
      <div class="pedido-row">
        <div class="pedido-row-icon icon-slate"><i class="fa-solid fa-box"></i></div>
        <span class="pedido-prods-txt"></span>
      </div>
      <div class="pedido-divider"></div>
      <div class="pedido-actions">
        <a class="btn-action btn-tel" href="#">
          <i class="fa-solid fa-phone"></i><span>Llamar</span>
        </a>
        <a class="btn-action btn-map" href="#" target="_blank">
          <i class="fa-solid fa-map-location-dot"></i><span>Mapa</span>
        </a>
        <button class="btn-action btn-entregar">
          <i class="fa-solid fa-circle-check"></i><span>Entregado</span>
        </button>
      </div>
    </div>
  </div>
</template>

<!-- ══════════════════════════════════════
     TEMPLATE: HISTORIAL
══════════════════════════════════════ -->
<template id="tplHistorial">
  <div class="hist-card">
    <div class="hist-card-header">
      <div class="hist-header-left">
        <div class="hist-check"><i class="fa-solid fa-check"></i></div>
        <div>
          <div class="hist-num"></div>
          <div class="hist-fecha"></div>
        </div>
      </div>
      <div class="hist-total"></div>
    </div>
    <div class="hist-rows">
      <div class="hist-row hist-row-cliente">
        <i class="fa-solid fa-user"></i>
        <span class="hist-cliente"></span>
      </div>
      <div class="hist-row hist-row-dir">
        <i class="fa-solid fa-location-dot"></i>
        <span class="hist-dir"></span>
      </div>
      <div class="hist-row hist-row-prods">
        <i class="fa-solid fa-box"></i>
        <span class="hist-prods"></span>
      </div>
    </div>
  </div>
</template>

<!-- ══════════════════════════════════════
     ANIMACIÓN ENTREGA CONFIRMADA
══════════════════════════════════════ -->
<div id="entregaOverlay" class="entrega-overlay hidden">
  <canvas id="confettiCanvas"></canvas>
  <div class="entrega-modal">
    <div class="entrega-modal-top">
      <div class="entrega-check-wrap">
        <svg class="entrega-svg" viewBox="0 0 80 80">
          <circle class="check-bg" cx="40" cy="40" r="38"/>
          <circle class="check-circle" cx="40" cy="40" r="35"/>
          <polyline class="check-mark" points="23,41 34,52 57,27"/>
        </svg>
      </div>
      <div class="entrega-titulo">¡Entregado!</div>
      <div class="entrega-chip" id="entregaChip">Pedido confirmado</div>
    </div>
    <div class="entrega-modal-body">
      <div class="entrega-sub" id="entregaSub">El pedido fue marcado como entregado exitosamente.</div>
      <button class="entrega-close" onclick="cerrarEntregaOverlay()">
        <i class="fa-solid fa-arrow-right"></i> Continuar
      </button>
    </div>
  </div>
</div>

<script>
/* ════════════════════════════════════════
   UTILIDADES
════════════════════════════════════════ */
const fmt      = n => '$' + parseFloat(n).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
const initials = n => (n||'?').trim().split(/\s+/).map(w=>w[0]).join('').substring(0,2).toUpperCase();
const fmtFecha = s => {
  if (!s) return '';
  const d = new Date(s.replace(' ','T'));
  return d.toLocaleDateString('es-AR',{day:'2-digit',month:'short',year:'numeric'})
       + ' · ' + d.toLocaleTimeString('es-AR',{hour:'2-digit',minute:'2-digit'});
};

function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const ico = btn.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text';     ico.className = 'fa-solid fa-eye-slash'; }
  else                         { inp.type = 'password'; ico.className = 'fa-solid fa-eye'; }
}

/* ════════════════════════════════════════
   SKELETON LOADER
════════════════════════════════════════ */
function skeletonPedidos() {
  const items = Array.from({length: 2}, () => `
    <div class="skeleton-card">
      <div style="flex:1">
        <div class="skeleton sk-bar sk-w40" style="margin-bottom:12px"></div>
        <div class="skeleton sk-h20 sk-w60" style="margin-bottom:14px"></div>
        <div class="skeleton sk-h14 sk-w80" style="margin-bottom:8px"></div>
        <div class="skeleton sk-h14 sk-w100" style="margin-bottom:8px"></div>
        <div class="skeleton sk-h14 sk-w60"></div>
      </div>
    </div>`).join('');
  return `<div class="pedidos-list" style="padding-bottom:20px">${items}</div>`;
}

/* ════════════════════════════════════════
   TABS
════════════════════════════════════════ */
function switchTab(name, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  const id = 'tab' + name[0].toUpperCase() + name.slice(1);
  document.getElementById(id).classList.add('active');
  if (name === 'historial') cargarHistorial();
  if (name === 'perfil')    cargarPerfil();
}

/* ════════════════════════════════════════
   LOGIN
════════════════════════════════════════ */
async function doLogin() {
  const celular  = document.getElementById('lCelular').value.trim();
  const password = document.getElementById('lPassword').value;
  const btn      = document.getElementById('btnLogin');
  const alertEl  = document.getElementById('loginAlert');

  if (!celular || !password) { showLoginAlert('Completá todos los campos'); return; }

  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ingresando...';
  alertEl.style.display = 'none';

  try {
    const res  = await fetch('api/login.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ celular, password }),
    });
    const data = await res.json();
    if (data.success) {
      const nombre = data.nombre;
      document.getElementById('dashNombre').textContent = nombre;
      document.getElementById('dashAvatar').textContent = initials(nombre);
      document.getElementById('appLogin').classList.add('hidden');
      document.getElementById('appDash').classList.remove('hidden');
      cargarPedidos();
      startAutoRefresh();
    } else {
      showLoginAlert(data.message || 'Datos incorrectos');
    }
  } catch (e) {
    showLoginAlert('Error de conexión');
  }
  btn.disabled  = false;
  btn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar';
}

function showLoginAlert(msg) {
  document.getElementById('loginAlertMsg').textContent = msg;
  const el = document.getElementById('loginAlert');
  el.style.display = 'flex';
}

async function doLogout() {
  await fetch('api/logout.php', { method: 'POST' });
  clearAutoRefresh();
  document.getElementById('appDash').classList.add('hidden');
  document.getElementById('appLogin').classList.remove('hidden');
  document.getElementById('lPassword').value = '';
}

/* ════════════════════════════════════════
   AUTO-REFRESH
════════════════════════════════════════ */
let _refreshTimer = null;
function startAutoRefresh() { clearAutoRefresh(); _refreshTimer = setInterval(cargarPedidos, 60000); }
function clearAutoRefresh()  { if (_refreshTimer) { clearInterval(_refreshTimer); _refreshTimer = null; } }

/* ════════════════════════════════════════
   PEDIDOS ACTIVOS
════════════════════════════════════════ */
const ACCENT_COLORS = ['#6366f1','#8b5cf6','#0ea5e9','#f43f5e','#10b981','#f59e0b','#ec4899'];

async function cargarPedidos() {
  const wrap = document.getElementById('tabPedidos');
  const list = document.getElementById('pedidosList');

  // Skeleton solo la primera vez
  if (!list.querySelector('.pedido-card')) {
    list.innerHTML = skeletonPedidos();
  }

  try {
    const res  = await fetch('api/get_pedidos.php');
    const data = await res.json();

    if (!data.success) {
      if (data.message === 'No autenticado') {
        document.getElementById('appDash').classList.add('hidden');
        document.getElementById('appLogin').classList.remove('hidden');
        return;
      }
      list.innerHTML = `<div class="empty-state">
        <div class="empty-state-icon slate"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3>Error</h3><p>No se pudieron cargar los pedidos</p>
      </div>`;
      return;
    }

    if (!data.pedidos || data.pedidos.length === 0) {
      list.innerHTML = `<div class="empty-state">
        <div class="empty-state-icon green"><i class="fa-solid fa-check"></i></div>
        <h3>¡Todo al día!</h3>
        <p>No tenés pedidos pendientes</p>
      </div>`;
      return;
    }

    const tpl = document.getElementById('tplPedido');
    list.innerHTML = '';

    data.pedidos.forEach((p, i) => {
      const clone = tpl.content.cloneNode(true);
      clone.querySelector('.pedido-accent').style.background = ACCENT_COLORS[i % ACCENT_COLORS.length];
      clone.querySelector('.pedido-num').textContent       = '#' + p.idventas;
      clone.querySelector('.pedido-total').textContent     = fmt(p.total);
      clone.querySelector('.pedido-nombre').textContent    = p.cliente_nombre || 'Cliente';
      clone.querySelector('.pedido-dir-txt').textContent   = p.direccion_entrega || 'Sin dirección';
      clone.querySelector('.pedido-prods-txt').textContent = p.productos || '—';

      const btnTel = clone.querySelector('.btn-tel');
      if (p.cliente_celular) {
        btnTel.href = 'tel:' + p.cliente_celular.replace(/\D/g,'');
      } else {
        btnTel.classList.add('disabled');
        btnTel.href = '#';
        btnTel.addEventListener('click', e => e.preventDefault());
      }

      const btnMap = clone.querySelector('.btn-map');
      if (p.lat_entrega && p.lng_entrega) {
        btnMap.href = `https://www.google.com/maps/dir/?api=1&destination=${p.lat_entrega},${p.lng_entrega}`;
      } else if (p.direccion_entrega) {
        btnMap.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.direccion_entrega)}`;
      } else {
        btnMap.classList.add('disabled');
        btnMap.href = '#';
        btnMap.addEventListener('click', e => e.preventDefault());
      }

      clone.querySelector('.btn-entregar').addEventListener('click', function() {
        marcarEntregado(p.idventas, this, p.cliente_nombre);
      });

      list.appendChild(clone);
    });

    list.querySelectorAll('.pedido-card').forEach((card, i) => {
      card.style.animationDelay = (i * 70) + 'ms';
      card.classList.add('card-in');
    });

  } catch (e) {
    list.innerHTML = `<div class="empty-state">
      <div class="empty-state-icon slate"><i class="fa-solid fa-wifi"></i></div>
      <h3>Sin conexión</h3><p>${e.message}</p>
    </div>`;
  }
}

async function marcarEntregado(idVenta, btn, clienteNombre) {
  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Procesando...</span>';

  try {
    const res  = await fetch('api/marcar_entregado.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_venta: idVenta }),
    });
    const data = await res.json();
    if (data.success) {
      mostrarEntregaAnimacion(idVenta, clienteNombre);
    } else {
      alert(data.message || 'No se pudo marcar como entregado');
      btn.disabled  = false;
      btn.innerHTML = '<i class="fa-solid fa-circle-check"></i><span>Entregado</span>';
    }
  } catch (e) {
    alert('Error de conexión');
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i><span>Entregado</span>';
  }
}

/* ════════════════════════════════════════
   ANIMACIÓN ENTREGA
════════════════════════════════════════ */
let _autoCloseTimer = null;

function mostrarEntregaAnimacion(idVenta, clienteNombre) {
  const overlay = document.getElementById('entregaOverlay');
  document.getElementById('entregaChip').textContent = 'Pedido #' + idVenta;
  document.getElementById('entregaSub').textContent  =
    clienteNombre ? `Entrega para ${clienteNombre} confirmada exitosamente.`
                  : 'El pedido fue marcado como entregado exitosamente.';

  // Resetear animaciones SVG re-insertando el nodo
  const svg = overlay.querySelector('.entrega-svg');
  const svgClone = svg.cloneNode(true);
  svg.parentNode.replaceChild(svgClone, svg);

  overlay.classList.remove('hidden', 'fade-out');
  requestAnimationFrame(() => overlay.classList.add('show'));

  lanzarConfetti();
  _autoCloseTimer = setTimeout(cerrarEntregaOverlay, 4200);
}

function cerrarEntregaOverlay() {
  if (_autoCloseTimer) { clearTimeout(_autoCloseTimer); _autoCloseTimer = null; }
  const overlay = document.getElementById('entregaOverlay');
  overlay.classList.remove('show');
  overlay.classList.add('fade-out');
  setTimeout(() => {
    overlay.classList.add('hidden');
    overlay.classList.remove('fade-out');
    stopConfetti();
    cargarPedidos();
  }, 350);
}

/* — Confetti — */
let _confettiRaf = null;
const CONFETTI_COLORS = ['#6366f1','#8b5cf6','#10b981','#f43f5e','#f59e0b','#0ea5e9','#ec4899','#ffffff'];

function lanzarConfetti() {
  stopConfetti();
  const canvas = document.getElementById('confettiCanvas');
  const ctx    = canvas.getContext('2d');
  canvas.width  = window.innerWidth;
  canvas.height = window.innerHeight;

  const piezas = Array.from({length: 140}, (_, i) => ({
    x:     Math.random() * canvas.width,
    y:     -20 - Math.random() * canvas.height * .5,
    w:     5 + Math.random() * 9,
    h:     8 + Math.random() * 7,
    color: CONFETTI_COLORS[i % CONFETTI_COLORS.length],
    speed: 1.8 + Math.random() * 4.5,
    angle: Math.random() * Math.PI * 2,
    spin:  (Math.random() - .5) * .12,
    drift: (Math.random() - .5) * 1.8,
    opacity: .7 + Math.random() * .3,
  }));

  const draw = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    piezas.forEach(p => {
      p.y += p.speed; p.x += p.drift; p.angle += p.spin;
      if (p.y > canvas.height + 20) { p.y = -20; p.x = Math.random() * canvas.width; }
      ctx.save();
      ctx.globalAlpha = p.opacity;
      ctx.translate(p.x, p.y);
      ctx.rotate(p.angle);
      ctx.fillStyle = p.color;
      // Mezclar rectángulos y círculos
      if (p.w > 11) {
        ctx.beginPath();
        ctx.arc(0, 0, p.w / 2, 0, Math.PI * 2);
        ctx.fill();
      } else {
        ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
      }
      ctx.restore();
    });
    _confettiRaf = requestAnimationFrame(draw);
  };
  draw();
}

function stopConfetti() {
  if (_confettiRaf) { cancelAnimationFrame(_confettiRaf); _confettiRaf = null; }
  const canvas = document.getElementById('confettiCanvas');
  canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
}

/* ════════════════════════════════════════
   HISTORIAL
════════════════════════════════════════ */
async function cargarHistorial() {
  const list = document.getElementById('historialList');
  list.innerHTML = skeletonPedidos();

  try {
    const res  = await fetch('api/get_historial.php');
    const data = await res.json();

    if (!data.success || !data.pedidos || data.pedidos.length === 0) {
      list.innerHTML = `<div class="empty-state">
        <div class="empty-state-icon slate"><i class="fa-solid fa-box-open"></i></div>
        <h3>Sin historial</h3>
        <p>Aún no tenés entregas realizadas</p>
      </div>`;
      return;
    }

    const tpl = document.getElementById('tplHistorial');
    list.innerHTML = '';
    data.pedidos.forEach(p => {
      const clone = tpl.content.cloneNode(true);
      clone.querySelector('.hist-num').textContent     = '#' + p.idventas;
      clone.querySelector('.hist-total').textContent   = fmt(p.total);
      clone.querySelector('.hist-cliente').textContent = p.cliente_nombre || 'Cliente';
      clone.querySelector('.hist-fecha').textContent   = fmtFecha(p.updated_at);

      const dirEl = clone.querySelector('.hist-dir');
      if (p.direccion_entrega) {
        dirEl.textContent = p.direccion_entrega;
      } else {
        dirEl.closest('.hist-row').style.display = 'none';
      }

      const prodsEl = clone.querySelector('.hist-prods');
      if (p.productos) {
        prodsEl.textContent = p.productos;
      } else {
        prodsEl.closest('.hist-row').style.display = 'none';
      }

      list.appendChild(clone);
    });

    list.querySelectorAll('.hist-card').forEach((c, i) => {
      c.style.animationDelay = (i * 45) + 'ms';
      c.classList.add('card-in');
    });
  } catch(e) {
    list.innerHTML = `<div class="empty-state">
      <div class="empty-state-icon slate"><i class="fa-solid fa-wifi"></i></div>
      <h3>Sin conexión</h3><p>Verificá tu internet</p>
    </div>`;
  }
}

/* ════════════════════════════════════════
   PERFIL
════════════════════════════════════════ */
async function cargarPerfil() {
  try {
    const res  = await fetch('api/get_perfil.php');
    const data = await res.json();
    if (!data.success) return;
    const u = data.perfil;
    const nombre = (u.nombre + ' ' + (u.apellido || '')).trim();
    document.getElementById('perfilAvatar').textContent        = initials(nombre);
    document.getElementById('perfilNombreDisplay').textContent = nombre;
    document.getElementById('perfilTelDisplay').textContent    = u.celular || '';
    document.getElementById('pNombre').value   = u.nombre   || '';
    document.getElementById('pApellido').value = u.apellido || '';
    document.getElementById('pCelular').value  = u.celular  || '';
    document.getElementById('pEmail').value    = u.email    || '';
    document.getElementById('pDni').value      = u.dni      || '';
  } catch(e) {}
}

async function guardarPerfil() {
  const nombre   = document.getElementById('pNombre').value.trim();
  const apellido = document.getElementById('pApellido').value.trim();
  const celular  = document.getElementById('pCelular').value.trim();
  const email    = document.getElementById('pEmail').value.trim();
  const dni      = document.getElementById('pDni').value.trim();
  const alertEl  = document.getElementById('perfilAlert');
  const alertMsg = document.getElementById('perfilAlertMsg');
  const succEl   = document.getElementById('perfilSuccess');
  const btn      = document.getElementById('btnGuardarPerfil');

  alertEl.style.display = 'none';
  succEl.style.display  = 'none';

  const err = msg => { alertMsg.textContent = msg; alertEl.style.display = 'flex'; };

  if (!nombre) return err('El nombre es requerido');

  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

  try {
    const res  = await fetch('api/update_perfil.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre, apellido, celular, email, dni }),
    });
    const data = await res.json();
    if (data.success) {
      succEl.style.display = 'flex';
      const fn = data.nombre;
      document.getElementById('dashNombre').textContent           = fn;
      document.getElementById('dashAvatar').textContent           = initials(fn);
      document.getElementById('perfilAvatar').textContent         = initials(fn);
      document.getElementById('perfilNombreDisplay').textContent  = fn;
      document.getElementById('perfilTelDisplay').textContent     = celular;
      setTimeout(() => { succEl.style.display = 'none'; }, 3500);
    } else {
      err(data.message || 'Error al guardar');
    }
  } catch(e) {
    err('Error de conexión');
  }
  btn.disabled  = false;
  btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
}

/* ════════════════════════════════════════
   SOPORTE
════════════════════════════════════════ */
async function enviarSoporte() {
  const tipo    = document.getElementById('soporteTipo').value;
  const detalle = document.getElementById('soporteDetalle').value.trim();
  const alertEl = document.getElementById('soporteAlert');
  const okEl    = document.getElementById('soporteSuccess');
  const btn     = document.getElementById('btnEnviarSoporte');

  alertEl.style.display = 'none';
  okEl.style.display    = 'none';

  if (!detalle) {
    document.getElementById('soporteAlertMsg').textContent = 'Describí el problema antes de enviar.';
    alertEl.style.display = 'flex'; return;
  }

  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

  try {
    const res  = await fetch('api/enviar_soporte.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ tipo, detalle }),
    });
    const data = await res.json();
    if (data.success) {
      okEl.style.display = 'flex';
      document.getElementById('soporteDetalle').value = '';
    } else {
      document.getElementById('soporteAlertMsg').textContent = data.message || 'No se pudo enviar. Intentá de nuevo.';
      alertEl.style.display = 'flex';
    }
  } catch(e) {
    document.getElementById('soporteAlertMsg').textContent = 'Error de conexión.';
    alertEl.style.display = 'flex';
  }

  btn.disabled  = false;
  btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar mensaje';
}

/* ════════════════════════════════════════
   GOOGLE OAUTH
════════════════════════════════════════ */
const GOOGLE_CLIENT_ID = <?= json_encode($googleClientId) ?>;

async function handleGoogleLogin(response) {
  const alertEl = document.getElementById('loginAlert');
  alertEl.style.display = 'none';

  try {
    const res  = await fetch('api/google_auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ credential: response.credential }),
    });
    const data = await res.json();

    if (data.success) {
      const nombre = data.nombre;
      document.getElementById('dashNombre').textContent = nombre;
      document.getElementById('dashAvatar').textContent = initials(nombre);
      document.getElementById('appLogin').classList.add('hidden');
      document.getElementById('appDash').classList.remove('hidden');
      cargarPedidos();
      startAutoRefresh();
    } else {
      showLoginAlert(data.message || 'No se pudo ingresar con Google');
    }
  } catch (e) {
    showLoginAlert('Error de conexión con Google');
  }
}

function initGoogle() {
  if (!GOOGLE_CLIENT_ID || !window.google) return;
  google.accounts.id.initialize({
    client_id: GOOGLE_CLIENT_ID,
    callback:  handleGoogleLogin,
    auto_select: false,
  });
  // Ocultar botón fallback y mostrar el nativo de Google
  const wrap     = document.getElementById('googleBtnWrap');
  const fallback = document.getElementById('btnGoogleFallback');
  if (wrap && fallback) {
    fallback.style.display = 'none';
    wrap.style.display     = 'flex';
    google.accounts.id.renderButton(wrap, {
      type:  'standard',
      theme: 'outline',
      size:  'large',
      text:  'signin_with',
      shape: 'rectangular',
      width: 320,
    });
  }
}

function initGooglePrompt() {
  if (GOOGLE_CLIENT_ID && window.google) {
    google.accounts.id.prompt();
  }
  // Si no hay client ID configurado, el botón queda visible pero inactivo hasta que se configure
}

// Inicializar cuando el script de Google esté listo
if (GOOGLE_CLIENT_ID) {
  if (window.google) {
    initGoogle();
  } else {
    window.addEventListener('load', () => {
      let tries = 0;
      const check = setInterval(() => {
        if (window.google || tries++ > 20) { clearInterval(check); initGoogle(); }
      }, 200);
    });
  }
}

/* ════════════════════════════════════════
   INIT
════════════════════════════════════════ */
document.getElementById('lPassword')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') doLogin();
});

if (!document.getElementById('appDash').classList.contains('hidden')) {
  const n = document.getElementById('dashNombre').textContent;
  document.getElementById('dashAvatar').textContent = initials(n);
  cargarPedidos();
  startAutoRefresh();
}
</script>
</body>
</html>
