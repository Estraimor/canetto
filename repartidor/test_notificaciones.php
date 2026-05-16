<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="theme-color" content="#0f172a">
<title>Test Notificaciones — Canetto</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; padding: 20px; min-height: 100vh; }
h1  { font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 4px; }
.sub { font-size: 12px; color: #64748b; margin-bottom: 20px; }

.card { background: #1e293b; border: 1px solid rgba(255,255,255,.08); border-radius: 14px; padding: 16px; margin-bottom: 12px; }
.card-title { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #64748b; margin-bottom: 12px; }

.row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 13px; gap: 8px; }
.row:last-child { border-bottom: none; }
.row-label { color: #94a3b8; flex: 1; }

.badge { padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.ok    { background: rgba(16,185,129,.15); color: #10b981; border: 1px solid rgba(16,185,129,.3); }
.warn  { background: rgba(245,158,11,.15);  color: #f59e0b; border: 1px solid rgba(245,158,11,.3); }
.err   { background: rgba(244,63,94,.15);   color: #f43f5e; border: 1px solid rgba(244,63,94,.3); }
.gray  { background: rgba(255,255,255,.06); color: #64748b; border: 1px solid rgba(255,255,255,.08); }

.btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; border: none; border-radius: 12px; font-family: inherit; font-size: 14px; font-weight: 700; cursor: pointer; margin-bottom: 8px; transition: opacity .15s; }
.btn:active { opacity: .75; }
.btn:disabled { opacity: .4; cursor: not-allowed; }
.btn-pink   { background: #c88e99; color: #fff; }
.btn-blue   { background: #3b82f6; color: #fff; }
.btn-green  { background: #10b981; color: #fff; }
.btn-slate  { background: rgba(255,255,255,.08); color: #94a3b8; border: 1px solid rgba(255,255,255,.1); }
.btn-orange { background: #f59e0b; color: #111; }

.log { background: #0a0f1a; border: 1px solid rgba(255,255,255,.06); border-radius: 10px; padding: 12px; min-height: 100px; max-height: 260px; overflow-y: auto; }
.log-line { font-size: 11px; font-family: monospace; padding: 3px 0; border-bottom: 1px solid rgba(255,255,255,.04); }
.log-line:last-child { border-bottom: none; }
.log-line.ok   { color: #10b981; }
.log-line.warn { color: #f59e0b; }
.log-line.err  { color: #f43f5e; }
.log-line.info { color: #60a5fa; }
.log-line.gray { color: #475569; }

#countdown { font-size: 42px; font-weight: 900; color: #c88e99; text-align: center; padding: 8px 0; }
</style>
</head>
<body>

<h1>🔔 Test de Notificaciones</h1>
<p class="sub">Diagnóstico completo — Canetto Repartidor</p>

<!-- DIAGNÓSTICO -->
<div class="card">
  <div class="card-title">Estado del sistema</div>
  <div class="row"><span class="row-label">Service Workers</span><span class="badge gray" id="st-sw">—</span></div>
  <div class="row"><span class="row-label">Push API</span><span class="badge gray" id="st-push">—</span></div>
  <div class="row"><span class="row-label">Notification API</span><span class="badge gray" id="st-notif">—</span></div>
  <div class="row"><span class="row-label">Permiso actual</span><span class="badge gray" id="st-perm">—</span></div>
  <div class="row"><span class="row-label">SW registrado</span><span class="badge gray" id="st-swreg">—</span></div>
  <div class="row"><span class="row-label">Suscripción Push</span><span class="badge gray" id="st-sub">—</span></div>
  <div class="row"><span class="row-label">Vibración</span><span class="badge gray" id="st-vib">—</span></div>
</div>

<!-- PRUEBA REAL -->
<div class="card" style="border:2px solid #c88e99">
  <div class="card-title" style="color:#c88e99">⚡ Probar en la app real</div>
  <p style="font-size:13px;color:#94a3b8;margin-bottom:14px;line-height:1.5">
    Abre la app del repartidor en <strong style="color:#fff">modo test (5 segundos)</strong>.
    Logueate, no toques nada, y en 5s dispara el sonido + vibración + notificación en la barra + popup, exactamente igual que el caso real.
  </p>
  <button class="btn btn-pink" onclick="window.location.href='index.php?_ta=1'" style="font-size:16px;padding:16px">
    🚀 Abrir app y probar AHORA
  </button>
</div>

<!-- TEST INDIVIDUAL -->
<div class="card">
  <div class="card-title">Pruebas individuales</div>
  <button class="btn btn-pink" onclick="testNotificacionLocal()">
    🔔 Notificación local AHORA
  </button>
  <button class="btn btn-orange" onclick="testVibracion()">
    📳 Vibración + 🔊 Sonido Uber
  </button>
  <button class="btn btn-blue" id="btnPerm" onclick="pedirPermiso()">
    🔐 Pedir permiso de notificaciones
  </button>
  <button class="btn btn-green" id="btnSW" onclick="registrarSW()">
    ⚙️ Registrar Service Worker
  </button>
  <button class="btn btn-slate" onclick="limpiarLog()">
    🗑 Limpiar log
  </button>
</div>

<!-- SIMULACIÓN TIMER -->
<div class="card">
  <div class="card-title">Simular check de actividad (5 segundos)</div>
  <div id="countdown">—</div>
  <button class="btn btn-pink" id="btnSim" onclick="simularActividad()">⏱ Iniciar simulación</button>
</div>

<!-- LOG -->
<div class="card">
  <div class="card-title">Log de eventos</div>
  <div class="log" id="log"></div>
</div>

<script>
const VAPID_PUB_KEY = 'BOHfZtCMwcBtOqLU9HdwNrRfs-A7u434RmpJWg3hAnzJZITA2KefpNGhwbFSfl6MTTDJRdGIVFikdIGF4_CKHbk';
let _swReg = null;
let _simTimer = null;
let _simRaf   = null;

/* ── Helpers ── */
function log(msg, tipo = '') {
  const el  = document.getElementById('log');
  const t   = new Date().toLocaleTimeString('es-AR');
  const div = document.createElement('div');
  div.className = 'log-line ' + tipo;
  div.textContent = `[${t}] ${msg}`;
  el.prepend(div);
}

function setBadge(id, txt, tipo) {
  const el = document.getElementById(id);
  el.textContent = txt;
  el.className   = 'badge ' + tipo;
}

function limpiarLog() {
  document.getElementById('log').innerHTML = '';
  log('Log limpiado', 'gray');
}

/* ── Diagnóstico inicial ── */
async function diagnosticar() {
  // Service Workers
  const swOk = 'serviceWorker' in navigator;
  setBadge('st-sw', swOk ? '✅ Disponible' : '❌ No soportado', swOk ? 'ok' : 'err');

  // Push API
  const pushOk = 'PushManager' in window;
  setBadge('st-push', pushOk ? '✅ Disponible' : '❌ No soportado', pushOk ? 'ok' : 'err');

  // Notification API
  const notifOk = 'Notification' in window;
  setBadge('st-notif', notifOk ? '✅ Disponible' : '❌ No soportado', notifOk ? 'ok' : 'err');

  // Vibración
  const vibOk = 'vibrate' in navigator;
  setBadge('st-vib', vibOk ? '✅ Disponible' : '❌ No soportado', vibOk ? 'ok' : 'err');

  // Permiso actual
  if ('Notification' in window) {
    const p = Notification.permission;
    const cls = p === 'granted' ? 'ok' : p === 'denied' ? 'err' : 'warn';
    const lbl = p === 'granted' ? '✅ Concedido' : p === 'denied' ? '❌ Bloqueado' : '⚠️ Sin responder';
    setBadge('st-perm', lbl, cls);
  } else {
    setBadge('st-perm', '❌ No API', 'err');
  }

  // SW registrado
  if (swOk) {
    try {
      const regs = await navigator.serviceWorker.getRegistrations();
      const repSW = regs.find(r => r.scope.includes(location.origin));
      if (repSW) {
        _swReg = repSW;
        setBadge('st-swreg', '✅ Registrado', 'ok');
        log('SW encontrado: ' + repSW.scope, 'ok');
        await verificarSuscripcion(repSW);
      } else {
        setBadge('st-swreg', '⚠️ No registrado', 'warn');
        log('SW del repartidor no está registrado aún', 'warn');
      }
    } catch (e) {
      setBadge('st-swreg', '❌ Error', 'err');
      log('Error verificando SW: ' + e.message, 'err');
    }
  }
}

async function verificarSuscripcion(reg) {
  try {
    const sub = await reg.pushManager.getSubscription();
    if (sub) {
      setBadge('st-sub', '✅ Activa', 'ok');
      log('Suscripción push activa: ' + sub.endpoint.substring(0, 60) + '...', 'ok');
    } else {
      setBadge('st-sub', '⚠️ Sin suscripción', 'warn');
      log('No hay suscripción push — registrá el SW primero', 'warn');
    }
  } catch (e) {
    setBadge('st-sub', '❌ Error', 'err');
  }
}

/* ── Pedir permiso ── */
async function pedirPermiso() {
  if (!('Notification' in window)) { log('❌ Notification API no disponible', 'err'); return; }

  log('Pidiendo permiso de notificaciones...', 'info');
  const perm = await Notification.requestPermission();
  log('Resultado: ' + perm, perm === 'granted' ? 'ok' : 'err');

  const cls = perm === 'granted' ? 'ok' : perm === 'denied' ? 'err' : 'warn';
  const lbl = perm === 'granted' ? '✅ Concedido' : perm === 'denied' ? '❌ Bloqueado' : '⚠️ Sin responder';
  setBadge('st-perm', lbl, cls);

  if (perm === 'granted') {
    log('✅ Permiso concedido — ahora podés registrar el SW', 'ok');
    await registrarSW();
  } else if (perm === 'denied') {
    log('❌ BLOQUEADO — el usuario rechazó las notificaciones. Debe habilitarlas manualmente en Ajustes del navegador → Notificaciones', 'err');
  }
}

/* ── Registrar SW ── */
async function registrarSW() {
  if (!('serviceWorker' in navigator)) { log('❌ SW no soportado en este navegador', 'err'); return; }
  if (Notification.permission !== 'granted') {
    log('⚠️ Primero pedí el permiso de notificaciones', 'warn'); return;
  }

  log('Registrando Service Worker...', 'info');
  try {
    _swReg = await navigator.serviceWorker.register('sw-rep.js');
    await navigator.serviceWorker.ready;
    setBadge('st-swreg', '✅ Registrado', 'ok');
    log('✅ SW registrado correctamente (scope: ' + _swReg.scope + ')', 'ok');

    // Suscribir a push
    log('Suscribiendo a Web Push...', 'info');
    const sub = await _swReg.pushManager.subscribe({
      userVisibleOnly:      true,
      applicationServerKey: vapidToUint8(VAPID_PUB_KEY),
    });
    setBadge('st-sub', '✅ Activa', 'ok');
    log('✅ Suscripción push creada', 'ok');
    log('Endpoint: ' + sub.endpoint.substring(0, 70) + '...', 'gray');

    // Guardar en servidor
    const j = sub.toJSON();
    const res = await fetch('api/guardar_push_rep.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ endpoint: j.endpoint, p256dh: j.keys.p256dh, auth: j.keys.auth }),
    });
    const data = await res.json().catch(() => ({}));
    if (data.ok) {
      log('✅ Suscripción guardada en el servidor', 'ok');
    } else {
      log('⚠️ No se pudo guardar en servidor (puede ser porque no estás logueado): ' + (data.msg || ''), 'warn');
    }
  } catch (e) {
    setBadge('st-swreg', '❌ Error', 'err');
    log('❌ Error al registrar SW: ' + e.message, 'err');
    if (e.message.includes('scope')) {
      log('👆 Problema de scope — verificá que sw-rep.js esté en /canetto/repartidor/', 'warn');
    }
  }
}

/* ── Notificación inmediata ── */
async function testNotificacionLocal() {
  if (Notification.permission !== 'granted') {
    log('⚠️ Sin permiso de notificaciones — tocá "Pedir permiso" primero', 'warn'); return;
  }

  log('Disparando notificación...', 'info');
  try {
    const opts = {
      body:             'Si ves esto en la barra de Android, ¡está funcionando! 🎉',
      icon:             '/canetto/assets/img/Logo_Canetto_Cookie.png',
      badge:            '/canetto/assets/img/Logo_Canetto_Cookie.png',
      vibrate:          [400, 150, 400],
      requireInteraction: true,
      tag:              'test-canetto',
      renotify:         true,
    };

    if (_swReg) {
      await _swReg.showNotification('🔔 Test Canetto Repartidor', opts);
      log('✅ Notificación enviada via Service Worker', 'ok');
    } else {
      new Notification('🔔 Test Canetto Repartidor', opts);
      log('✅ Notificación enviada via Notification API (sin SW)', 'ok');
    }
  } catch (e) {
    log('❌ Error al mostrar notificación: ' + e.message, 'err');
  }
}

/* ── Vibración ── */
function testVibracion() {
  if (navigator.vibrate) {
    navigator.vibrate([300, 100, 300]);
    log('📳 Vibración disparada', 'ok');
  } else {
    log('❌ Vibración no disponible', 'err');
  }
  sonidoUber();
}

/* ── Sonido tipo Uber ── */
function sonidoUber() {
  try {
    const ctx  = new (window.AudioContext || window.webkitAudioContext)();
    const play = (freq, t, dur, vol = 0.35) => {
      const osc  = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain); gain.connect(ctx.destination);
      osc.type = 'sine'; osc.frequency.value = freq;
      gain.gain.setValueAtTime(0, t);
      gain.gain.linearRampToValueAtTime(vol, t + 0.01);
      gain.gain.exponentialRampToValueAtTime(0.001, t + dur);
      osc.start(t); osc.stop(t + dur + 0.05);
    };
    const t = ctx.currentTime;
    play(880,  t,        0.18);
    play(1320, t + 0.22, 0.25);
    log('🔊 Sonido tipo Uber disparado', 'ok');
  } catch (e) {
    log('❌ Error de audio: ' + e.message, 'err');
  }
}

/* ── Simulación timer ── */
function simularActividad() {
  const btn  = document.getElementById('btnSim');
  const disp = document.getElementById('countdown');

  if (_simTimer) {
    clearTimeout(_simTimer);
    if (_simRaf) cancelAnimationFrame(_simRaf);
    _simTimer = null; _simRaf = null;
    btn.textContent = '⏱ Iniciar simulación';
    disp.textContent = '—';
    log('Simulación cancelada', 'gray');
    return;
  }

  btn.textContent = '⏹ Cancelar';
  log('Simulación iniciada — notificación en 5 segundos...', 'info');

  const fin = Date.now() + 5000;
  const tick = () => {
    const rest = Math.max(0, fin - Date.now());
    disp.textContent = (rest / 1000).toFixed(1) + 's';
    if (rest > 0) { _simRaf = requestAnimationFrame(tick); return; }
    disp.textContent = '🔔';
    _simTimer = null; _simRaf = null;
    btn.textContent = '⏱ Iniciar simulación';
    log('⏰ Timer expiró → disparando notificación + vibración', 'info');
    testNotificacionLocal();
    if (navigator.vibrate) navigator.vibrate([400, 150, 400]);
  };
  _simRaf = requestAnimationFrame(tick);
  _simTimer = setTimeout(() => {}, 5100); // dummy para el flag
}

/* ── VAPID helper ── */
function vapidToUint8(b64) {
  const pad = '='.repeat((4 - b64.length % 4) % 4);
  const raw = atob((b64 + pad).replace(/-/g,'+').replace(/_/g,'/'));
  return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

/* ── Init ── */
log('Página cargada — ejecutando diagnóstico...', 'gray');
diagnosticar().then(() => {
  log('Diagnóstico completo', 'info');
  if (Notification.permission === 'denied') {
    log('🚨 BLOQUEADO — seguí los pasos de la pantalla para desbloquear', 'err');
    mostrarInstruccionesDesbloqueo();
  }
});

function mostrarInstruccionesDesbloqueo() {
  const card = document.createElement('div');
  card.style.cssText = 'background:#1e0a0a;border:2px solid #f43f5e;border-radius:14px;padding:18px;margin-bottom:12px;';
  card.innerHTML = `
    <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#f43f5e;margin-bottom:14px">
      🚨 Notificaciones bloqueadas — debés desbloquearlas manualmente
    </div>
    <div style="font-size:13px;color:#fca5a5;line-height:1.8;margin-bottom:16px">
      Chrome no permite que la app pida el permiso de nuevo porque fue bloqueado antes.
      Seguí estos pasos en el celular:
    </div>
    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px">
      ${[
        ['1', '🔒', 'Tocá el candado / ícono de info en la barra de la URL (arriba)'],
        ['2', '⚙️', 'Tocá <strong>"Configuración del sitio"</strong>'],
        ['3', '🔔', 'Tocá <strong>"Notificaciones"</strong>'],
        ['4', '✅', 'Cambiá de <strong>"Bloquear"</strong> a <strong>"Permitir"</strong>'],
        ['5', '🔄', 'Volvé a esta página y recargá'],
      ].map(([n, ico, txt]) => `
        <div style="display:flex;align-items:flex-start;gap:10px;background:rgba(255,255,255,.04);border-radius:8px;padding:10px">
          <div style="width:26px;height:26px;border-radius:50%;background:#f43f5e;color:#fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0">${n}</div>
          <div style="font-size:13px;color:#e2e8f0;line-height:1.4">${ico} ${txt}</div>
        </div>`).join('')}
    </div>
    <button onclick="location.reload()" style="width:100%;padding:13px;background:#f43f5e;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
      🔄 Recargar y verificar
    </button>
  `;
  // Insertar al principio del body, después del subtítulo
  const sub = document.querySelector('.sub');
  sub.after(card);
}
</script>
</body>
</html>
