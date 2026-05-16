<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Test — Sistema de Actividad</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; padding: 24px; min-height: 100vh; }
  h1  { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 6px; }
  .sub { font-size: 13px; color: #64748b; margin-bottom: 28px; }
  .card {
    background: #1e293b; border: 1px solid rgba(255,255,255,.1);
    border-radius: 16px; padding: 20px; margin-bottom: 16px;
  }
  .card-title { font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .1em; color: #64748b; margin-bottom: 14px; }
  .row { display: flex; align-items: center; justify-content: space-between;
    font-size: 13px; color: #94a3b8; padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,.05); }
  .row:last-child { border-bottom: none; }
  .val { font-weight: 700; color: #fff; }
  .val.ok  { color: #10b981; }
  .val.warn { color: #f59e0b; }
  .val.err  { color: #f43f5e; }
  .btn {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 14px 18px; border: none; border-radius: 12px;
    font-family: inherit; font-size: 14px; font-weight: 700;
    cursor: pointer; margin-bottom: 10px; transition: transform .1s, opacity .15s;
  }
  .btn:active { transform: scale(.97); opacity: .85; }
  .btn-primary { background: #c88e99; color: #fff; }
  .btn-blue    { background: #3b82f6; color: #fff; }
  .btn-orange  { background: #f59e0b; color: #1c1917; }
  .btn-slate   { background: rgba(255,255,255,.08); color: #94a3b8; border: 1px solid rgba(255,255,255,.1); }
  .log {
    background: #0a0f1a; border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; padding: 12px; min-height: 120px;
    font-size: 11px; font-family: monospace; color: #64748b;
    max-height: 220px; overflow-y: auto;
  }
  .log-entry { padding: 2px 0; border-bottom: 1px solid rgba(255,255,255,.04); }
  .log-entry.ok   { color: #10b981; }
  .log-entry.warn { color: #f59e0b; }
  .log-entry.err  { color: #f43f5e; }
  .log-entry.info { color: #60a5fa; }
  #timer-display {
    font-size: 48px; font-weight: 900; color: #c88e99;
    text-align: center; padding: 10px 0; letter-spacing: -2px;
  }
  .timer-bar-wrap { background: rgba(255,255,255,.08); border-radius: 100px; height: 8px; overflow: hidden; margin: 6px 0 4px; }
  .timer-bar { height: 100%; background: #c88e99; border-radius: 100px; transition: width .1s linear; }
  .badge { display: inline-block; padding: 3px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; }
  .badge-green  { background: rgba(16,185,129,.15); color: #10b981; border: 1px solid rgba(16,185,129,.3); }
  .badge-red    { background: rgba(244,63,94,.15);  color: #f43f5e; border: 1px solid rgba(244,63,94,.3); }
  .badge-gray   { background: rgba(255,255,255,.07); color: #64748b; border: 1px solid rgba(255,255,255,.1); }
</style>
</head>
<body>

<h1>🧪 Test — Sistema de Actividad</h1>
<p class="sub">Prueba del popup "¿Seguís activo?" sin esperar 30 segundos</p>

<!-- Estado actual -->
<div class="card">
  <div class="card-title">Configuración (modo TEST)</div>
  <div class="row"><span>Inactividad hasta popup</span><span class="val warn">5 segundos</span></div>
  <div class="row"><span>Tiempo para responder</span><span class="val warn">8 segundos</span></div>
  <div class="row"><span>Configuración real (producción)</span><span class="val">30s / 15s</span></div>
  <div class="row">
    <span>Vibración disponible</span>
    <span class="val" id="vibOk">—</span>
  </div>
</div>

<!-- Countdown visual -->
<div class="card">
  <div class="card-title">Cuenta regresiva hasta el popup</div>
  <div id="timer-display">5.0</div>
  <div class="timer-bar-wrap"><div class="timer-bar" id="testTimerBar" style="width:100%"></div></div>
  <div style="text-align:center;font-size:11px;color:#64748b;margin-top:6px" id="testStatus">
    Cargando la app…
  </div>
</div>

<!-- Acciones -->
<div class="card">
  <div class="card-title">Acciones</div>
  <button class="btn btn-primary" onclick="abrirAppTest()">
    🚀 Abrir app en modo TEST (5s popup)
  </button>
  <button class="btn btn-orange" onclick="testVibracion()">
    📳 Probar vibración del celular
  </button>
  <button class="btn btn-blue" onclick="abrirAppReal()">
    📱 Abrir app normal (30s real)
  </button>
  <button class="btn btn-slate" onclick="limpiarLog()">
    🗑 Limpiar log
  </button>
</div>

<!-- Log -->
<div class="card">
  <div class="card-title">Log de eventos</div>
  <div class="log" id="log"></div>
</div>

<script>
const TIMER_SECS = 5;
let _countdown = null;
let _start = null;

function log(msg, tipo = '') {
  const d = document.getElementById('log');
  const t = new Date().toLocaleTimeString('es-AR', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
  const div = document.createElement('div');
  div.className = 'log-entry' + (tipo ? ' ' + tipo : '');
  div.textContent = `[${t}] ${msg}`;
  d.prepend(div);
}

function limpiarLog() {
  document.getElementById('log').innerHTML = '';
  log('Log limpiado', 'info');
}

function abrirAppTest() {
  log('🚀 Abriendo app en modo TEST (_ta=1)…', 'info');
  log('ℹ️  El popup aparecerá a los 5s de inactividad', 'info');
  window.open('index.php?_ta=1', '_blank');
}

function abrirAppReal() {
  log('📱 Abriendo app normal…', 'info');
  window.open('index.php', '_blank');
}

function testVibracion() {
  if (navigator.vibrate) {
    navigator.vibrate([400, 150, 400]);
    log('📳 Vibración disparada: 400ms · pausa · 400ms', 'ok');
  } else {
    log('❌ Vibración no disponible en este navegador/dispositivo', 'err');
  }
}

// Verificar vibración
document.getElementById('vibOk').textContent = navigator.vibrate ? '✅ Sí' : '❌ No';
document.getElementById('vibOk').className = 'val ' + (navigator.vibrate ? 'ok' : 'err');

// Animación del timer (solo visual, no conectada a la app)
function iniciarTimerVisual() {
  _start = Date.now();
  document.getElementById('testStatus').textContent = 'La app disparará el popup a los 5s de inactividad';

  const tick = () => {
    const elapsed = (Date.now() - _start) / 1000;
    const rest    = Math.max(0, TIMER_SECS - elapsed);
    const pct     = rest / TIMER_SECS * 100;

    document.getElementById('timer-display').textContent = rest.toFixed(1);
    const bar = document.getElementById('testTimerBar');
    bar.style.width = pct + '%';
    bar.style.background = pct > 60 ? '#c88e99' : pct > 30 ? '#f59e0b' : '#f43f5e';

    if (rest <= 0) {
      document.getElementById('timer-display').textContent = '💬';
      document.getElementById('testStatus').textContent = '→ Popup disparado en la app (si estás logueado)';
      setTimeout(iniciarTimerVisual, 2000); // reiniciar visual
      return;
    }
    _countdown = requestAnimationFrame(tick);
  };
  if (_countdown) cancelAnimationFrame(_countdown);
  tick();
}

log('✅ Página de test cargada', 'ok');
log('📱 Para probar: hacé login en la app y dejá el celular 5 segundos sin tocar', 'info');
log('📳 Vibración: ' + (navigator.vibrate ? 'disponible' : 'NO disponible'), navigator.vibrate ? 'ok' : 'warn');

iniciarTimerVisual();
</script>
</body>
</html>
