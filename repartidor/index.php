<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/google_config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$repId          = $_SESSION['repartidor_id']     ?? null;
$repNombre      = $_SESSION['repartidor_nombre'] ?? '';
$googleClientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';

// Cargar sucursales reales de la BD
$sucursalesJS = [];
try {
    $pdoRep = Conexion::conectar();
    $stmtSuc = $pdoRep->query("SELECT idsucursal, nombre, direccion, latitud, longitud FROM sucursal WHERE activo = 1 ORDER BY idsucursal ASC");
    $sucursalesJS = $stmtSuc->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $sucursalesJS = []; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#0f172a">
<title>Canetto — Repartidor</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="repartidor.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<style>
/* ══ UBER-STYLE MAP MODE ══════════════════════════════════ */

/* Pantalla de mapa fullscreen */
#uberMapScreen{
  position:fixed;inset:0;z-index:500;display:none;flex-direction:column;
  background:#1a1a2e;
}
#uberMapScreen.active{display:flex}

/* Mapa ocupa todo el fondo */
#uberMapFull{
  position:absolute;inset:0;width:100%;height:100%;
}

/* Botón volver */
.uber-back{
  position:absolute;top:16px;left:16px;z-index:600;
  width:40px;height:40px;border-radius:50%;background:rgba(15,23,42,.85);
  border:none;color:#fff;font-size:16px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  backdrop-filter:blur(8px);box-shadow:0 2px 10px rgba(0,0,0,.4);
}

/* Ocultar controles automáticos del plugin leaflet-rotate */
.leaflet-bearing-control,
.leaflet-control-rotate { display: none !important; }

/* Sin transición CSS en el mapa — tiles, ruta y marcadores rotan en sync instantáneo.
   La suavidad viene del throttle rAF a 60fps, no de CSS animation. */
#uberMapFull .leaflet-map-pane,
#uberMapFull .leaflet-tile-pane,
#uberMapFull .leaflet-overlay-pane,
#uberMapFull .leaflet-marker-pane,
#uberMapFull .leaflet-shadow-pane {
  transition: none !important;
}

/* Badge estado arriba al centro */
.uber-status-badge{
  position:absolute;top:16px;left:50%;transform:translateX(-50%);z-index:600;
  background:rgba(15,23,42,.85);color:#fff;padding:6px 16px;border-radius:20px;
  font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  backdrop-filter:blur(8px);white-space:nowrap;
}
.uber-status-badge.pickup{color:#f59e0b}
.uber-status-badge.delivery{color:#10b981}

/* Card inferior estilo Uber */
.uber-card{
  position:absolute;bottom:0;left:0;right:0;z-index:600;
  background:#0f172a;border-radius:24px 24px 0 0;
  padding:16px 20px 32px;
  box-shadow:0 -8px 32px rgba(0,0,0,.5);
  animation:slideUp .35s cubic-bezier(.32,.72,0,1);
}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}

.uber-card-handle{
  width:36px;height:4px;border-radius:2px;background:rgba(255,255,255,.2);
  margin:0 auto 16px;
}

/* Pedido activo en la card */
.uber-order-num{font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#c88e99;margin-bottom:6px}
.uber-order-name{font-size:20px;font-weight:800;color:#fff;margin-bottom:4px}
.uber-order-addr{font-size:13px;color:rgba(255,255,255,.65);display:flex;align-items:flex-start;gap:6px;margin-bottom:12px}
.uber-order-addr i{color:#f43f5e;margin-top:2px;flex-shrink:0}
.uber-order-prods{font-size:12px;color:rgba(255,255,255,.5);margin-bottom:14px;display:flex;align-items:flex-start;gap:6px}
.uber-order-prods i{color:rgba(255,255,255,.4);margin-top:2px;flex-shrink:0}

/* Puntos A→B visuales */
.uber-route-info{
  display:flex;gap:10px;margin-bottom:14px;
}
.uber-route-point{
  flex:1;background:rgba(255,255,255,.07);border-radius:12px;padding:10px 12px;
}
.uber-route-label{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;margin-bottom:3px}
.uber-route-label.pickup-lbl{color:#c88e99}
.uber-route-label.delivery-lbl{color:#c88e99}
.uber-route-val{font-size:12px;color:#fff;font-weight:600;line-height:1.3}

/* Botones acción */
.uber-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}
.uber-btn{
  display:flex;flex-direction:column;align-items:center;gap:4px;
  padding:10px 6px;border-radius:14px;border:none;cursor:pointer;
  font-family:inherit;font-size:11px;font-weight:700;transition:.15s;
}
.uber-btn i{font-size:18px}
.uber-btn-tel{background:rgba(200,142,153,.15);color:#c88e99;border:1px solid rgba(200,142,153,.3)}
.uber-btn-nav{background:rgba(200,142,153,.15);color:#c88e99;border:1px solid rgba(200,142,153,.3)}
.uber-btn-ok{background:#c88e99;color:#fff;font-size:14px;font-weight:800;grid-column:span 2;
  flex-direction:row;justify-content:center;gap:8px;padding:14px;border-radius:16px}
.uber-btn-ok:active{background:#a46678}

/* Badge cobro efectivo */
.uber-cobro{
  background:rgba(200,142,153,.12);border:1px solid rgba(200,142,153,.35);
  border-radius:10px;padding:10px 12px;margin-bottom:12px;
  display:flex;align-items:center;gap:8px;font-size:13px;color:#c88e99;
}
.uber-cobro i{flex-shrink:0}
.uber-cobro strong{color:#fff;font-size:15px}

/* Sin pedidos → mapa normal chico */
#repMapWrap{display:none}
#repMap{height:180px;border-radius:16px;overflow:hidden}

/* ── Navigation instruction banner ── */
.uber-nav-banner{
  position:absolute;top:64px;left:50%;transform:translateX(-50%);z-index:610;
  background:rgba(12,18,30,.96);border-radius:16px;padding:10px 16px;
  display:flex;align-items:center;gap:12px;min-width:220px;max-width:calc(100vw - 96px);
  backdrop-filter:blur(14px);box-shadow:0 6px 28px rgba(0,0,0,.55);
  border:1px solid rgba(255,255,255,.07);transition:opacity .3s;
}
.uber-nav-arrow{font-size:24px;color:#fff;flex-shrink:0;width:30px;text-align:center}
.uber-nav-info{flex:1;min-width:0}
.uber-nav-step{font-size:13px;font-weight:700;color:#fff;line-height:1.25;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.uber-nav-dist{font-size:11px;color:rgba(255,255,255,.5);margin-top:2px}

/* ── Phase badge colors ── */
.uber-status-badge.phase-pickup   {color:#fbbf24}
.uber-status-badge.phase-delivery {color:#10b981}

/* ── Pickup confirm button (amber) ── */
.uber-btn-pkg{
  background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;
  font-size:14px;font-weight:800;grid-column:span 2;flex-direction:row;
  justify-content:center;gap:8px;padding:14px;border-radius:16px;border:none;
  transition:transform .12s;
}
.uber-btn-pkg:active{transform:scale(.97)}

/* ── Multi-package queue ── */
.uber-pkg-queue{
  background:rgba(255,255,255,.05);border-radius:12px;
  padding:10px 12px;margin-bottom:10px;border:1px solid rgba(255,255,255,.06);
}
.uber-pkg-queue-title{
  font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
  color:rgba(255,255,255,.3);margin-bottom:8px;display:flex;align-items:center;gap:6px
}
.uber-pkg-row{
  display:flex;align-items:center;gap:10px;padding:7px 0;
  border-bottom:1px solid rgba(255,255,255,.05);font-size:12px;transition:opacity .2s;
}
.uber-pkg-row:last-child{border-bottom:none}
.uber-pkg-dot{
  width:24px;height:24px;border-radius:50%;display:flex;align-items:center;
  justify-content:center;font-size:10px;font-weight:800;flex-shrink:0;
}
.uber-pkg-row.pkg-current{color:#fff;font-weight:700}
.uber-pkg-row.pkg-current .uber-pkg-dot{background:#c88e99;color:#fff}
.uber-pkg-row.pkg-done{opacity:.4}
.uber-pkg-row.pkg-done .uber-pkg-dot{background:#10b981;color:#fff}
.uber-pkg-row.pkg-pending{color:rgba(255,255,255,.45)}
.uber-pkg-row.pkg-pending .uber-pkg-dot{background:rgba(255,255,255,.08);color:rgba(255,255,255,.3)}

/* ── Card body: smooth momentum scroll ── */
#uberCardBody{
  overflow-y:auto;-webkit-overflow-scrolling:touch;
  overscroll-behavior:contain;max-height:56vh;scrollbar-width:none;
}
#uberCardBody::-webkit-scrollbar{display:none}
</style>
<?php if ($googleClientId): ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>
<style>
/* ── Filtros historial ── */
.hist-filtro-btn{
  display:inline-flex;align-items:center;padding:7px 16px;
  border-radius:20px;border:1.5px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.06);color:rgba(255,255,255,.6);
  font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;
  white-space:nowrap;transition:.15s;flex-shrink:0;
}
.hist-filtro-btn:hover{border-color:#c88e99;color:#c88e99}
.hist-filtro-btn.active{background:#c88e99;border-color:#c88e99;color:#fff}

.or-divider{display:flex;align-items:center;gap:10px;margin:16px 0 12px;color:#475569;font-size:12px}
.or-divider::before,.or-divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.12)}
.btn-google-rep{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:13px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;color:#3c4043;font-size:14px;font-weight:500;font-family:inherit;cursor:pointer;transition:.2s;margin-top:2px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.btn-google-rep:hover{border-color:#c88e99;box-shadow:0 2px 8px rgba(200,142,153,.2);transform:translateY(-1px)}
.btn-google-rep img{width:18px;height:18px}

/* ── Banner Pendiente de Cobro ── */
.pedido-cobro-banner{display:flex;align-items:flex-start;gap:10px;background:#fef3c7;border:2px solid #f59e0b;border-radius:10px;padding:12px 14px;margin-bottom:12px}
.pedido-cobro-banner>i{color:#b45309;font-size:16px;margin-top:2px;flex-shrink:0}
.pedido-cobro-body{flex:1}
.pedido-cobro-title{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#92400e;margin-bottom:6px}
.pedido-cobro-rows{display:flex;flex-direction:column;gap:3px}
.pedido-cobro-row{display:flex;justify-content:space-between;font-size:13px;color:#78350f}
.cobro-total-row{font-weight:800;color:#1c1917;border-top:1.5px solid #d97706;margin-top:6px;padding-top:6px;font-size:15px}

/* ── Badge pedido urgente (≥20 min esperando) ── */
.pedido-urgente-badge{
  display:inline-flex;align-items:center;gap:3px;
  background:linear-gradient(135deg,#ef4444,#dc2626);
  color:#fff;font-size:10px;font-weight:800;letter-spacing:.05em;
  text-transform:uppercase;padding:3px 8px;border-radius:6px;
  animation:urgentePulse 1.4s ease-in-out infinite;
}
@keyframes urgentePulse{
  0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.5)}
  50%    {box-shadow:0 0 0 7px rgba(239,68,68,0)}
}
.uber-pkg-urgente{
  font-size:9px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;
  background:#ef4444;color:#fff;border-radius:4px;padding:1px 5px;margin-left:5px;flex-shrink:0;
  animation:urgentePulse 1.4s ease-in-out infinite;
}

/* ══ INCOMING ORDER OVERLAY ════════════════════════════════ */
#incomingOverlay{
  position:fixed;inset:0;z-index:9000;display:none;align-items:center;justify-content:center;
  background:rgba(10,14,26,.96);backdrop-filter:blur(16px);
  animation:incomingFadeIn .3s ease;
}
#incomingOverlay.visible{display:flex}
@keyframes incomingFadeIn{from{opacity:0}to{opacity:1}}

.incoming-pulse{
  position:absolute;inset:0;background:radial-gradient(circle at 50% 40%, rgba(200,142,153,.18) 0%, transparent 70%);
  animation:incomingPulse 1.5s ease-in-out infinite alternate;
}
@keyframes incomingPulse{from{opacity:.5}to{opacity:1}}

.incoming-card{
  position:relative;z-index:1;width:min(340px,92vw);
  background:rgba(15,23,42,.95);border:1px solid rgba(200,142,153,.25);
  border-radius:28px;padding:28px 24px 24px;text-align:center;
  box-shadow:0 24px 64px rgba(0,0,0,.7);
}
.incoming-icon-wrap{
  width:72px;height:72px;margin:0 auto 14px;border-radius:50%;
  background:rgba(200,142,153,.18);border:2px solid rgba(200,142,153,.35);
  display:flex;align-items:center;justify-content:center;font-size:32px;
  animation:incomingBounce .6s ease infinite alternate;
}
@keyframes incomingBounce{from{transform:scale(1)}to{transform:scale(1.08)}}

.incoming-headline{font-size:22px;font-weight:900;color:#fff;margin-bottom:4px}
.incoming-sub{font-size:12px;color:rgba(255,255,255,.5);margin-bottom:18px;text-transform:uppercase;letter-spacing:.08em}

/* Timer SVG ring */
.incoming-timer-wrap{position:relative;width:72px;height:72px;margin:0 auto 18px}
.incoming-timer-wrap svg{position:absolute;inset:0;transform:rotate(-90deg)}
#incomingTimerBg{stroke:rgba(255,255,255,.08)}
#incomingTimerRing{stroke:#c88e99;transition:stroke-dashoffset .9s linear,stroke .5s}
.incoming-secs{
  position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
  font-size:22px;font-weight:900;color:#fff;
}

/* Order info */
.incoming-order-info{
  background:rgba(255,255,255,.05);border-radius:14px;padding:14px 16px;margin-bottom:18px;text-align:left;
}
.incoming-order-row{display:flex;align-items:flex-start;gap:10px;padding:5px 0}
.incoming-order-row:not(:last-child){border-bottom:1px solid rgba(255,255,255,.06)}
.incoming-order-icon{color:#c88e99;width:18px;flex-shrink:0;margin-top:1px;text-align:center}
.incoming-order-text{font-size:13px;color:rgba(255,255,255,.85);line-height:1.4}
.incoming-order-label{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:1px}

/* Buttons */
.incoming-btns{display:grid;grid-template-columns:1fr 1.6fr;gap:10px}
.btn-incoming-rechazar{
  padding:14px 10px;border-radius:16px;border:1.5px solid rgba(255,255,255,.15);
  background:rgba(255,255,255,.06);color:rgba(255,255,255,.7);
  font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;transition:.15s;
}
.btn-incoming-rechazar:active{background:rgba(255,255,255,.12)}
.btn-incoming-aceptar{
  padding:14px 10px;border-radius:16px;border:none;
  background:linear-gradient(135deg,#10b981,#059669);color:#fff;
  font-size:16px;font-weight:900;font-family:inherit;cursor:pointer;
  box-shadow:0 6px 20px rgba(16,185,129,.4);transition:.15s;
}
.btn-incoming-aceptar:active{transform:scale(.97)}
</style>
</head>
<body>

<!-- ══ Overlay éxito login repartidor ══════════════════════════ -->
<div id="repLoginOverlay" style="
    display:none;position:fixed;inset:0;z-index:99999;
    background:linear-gradient(150deg,rgba(15,23,42,0.97) 0%,rgba(26,26,46,0.97) 100%);
    backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
    flex-direction:column;align-items:center;justify-content:center;
    opacity:0;transition:opacity .22s ease">
  <div id="repSuccessCard" style="text-align:center;transform:scale(.6) translateY(24px);opacity:0;
       transition:transform .5s cubic-bezier(.34,1.56,.64,1),opacity .3s ease;will-change:transform,opacity">
    <div style="position:relative;width:110px;height:110px;margin:0 auto 26px">
      <svg width="110" height="110" viewBox="0 0 110 110" style="position:absolute;inset:0">
        <circle cx="55" cy="55" r="50" fill="rgba(200,142,153,.15)"/>
        <circle id="repRing" cx="55" cy="55" r="50" fill="none" stroke="#c88e99" stroke-width="2.5"
                stroke-linecap="round" stroke-dasharray="314" stroke-dashoffset="314"
                transform="rotate(-90 55 55)"
                style="transition:stroke-dashoffset .7s cubic-bezier(.16,1,.3,1) .15s"/>
        <polyline id="repCheck" points="30,56 47,73 80,36" fill="none" stroke="#c88e99"
                  stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"
                  stroke-dasharray="76" stroke-dashoffset="76"
                  style="transition:stroke-dashoffset .4s cubic-bezier(.16,1,.3,1) .75s"/>
      </svg>
    </div>
    <div id="repSuccessName" style="font-size:26px;font-weight:800;color:#fff;letter-spacing:-.5px;
         margin-bottom:8px;opacity:0;transform:translateY(12px);
         transition:opacity .4s ease .9s,transform .4s cubic-bezier(.16,1,.3,1) .9s"></div>
    <div style="font-size:14px;color:#64748b;font-weight:500;opacity:0;transform:translateY(8px);
         transition:opacity .35s ease 1.05s,transform .35s ease 1.05s" id="repSuccessSub">
      ¡Listo para repartir! 🛵
    </div>
  </div>
  <div style="position:absolute;bottom:34px;font-size:11px;letter-spacing:5px;
       text-transform:uppercase;font-weight:700;color:#334155;
       opacity:0;transition:opacity .5s ease 1.1s" id="repSuccessBrand">CANETTO</div>
</div>
<!-- ════════════════════════════════════════════════════════════ -->

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

      <div style="text-align:center;margin-top:4px;margin-bottom:14px;">
        <a href="<?= URL_LOGIN ?>/recuperar_password.php"
           style="font-size:14px;font-weight:600;color:#c88e99;text-decoration:none;display:inline-flex;align-items:center;gap:5px">
          <i class="fa-solid fa-lock-open" style="font-size:13px"></i> ¿Olvidaste tu contraseña?
        </a>
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
     MAPA UBER FULLSCREEN
══════════════════════════════════════ -->
<div id="uberMapScreen">
  <div id="uberMapFull"></div>

  <button class="uber-back" onclick="cerrarUberMap()">
    <i class="fa-solid fa-arrow-left"></i>
  </button>
  <div class="uber-status-badge phase-pickup" id="uberStatusBadge">Yendo a retirar</div>

  <!-- Botones derecha: centrar + seguir -->
  <div style="position:absolute;right:14px;bottom:220px;z-index:600;display:flex;flex-direction:column;gap:10px">
    <!-- Centrar en la moto -->
    <button id="btnCentrar" onclick="centrarEnMoto()"
        style="width:44px;height:44px;border-radius:50%;background:rgba(15,23,42,.88);
               border:none;color:#fff;font-size:17px;cursor:pointer;
               display:flex;align-items:center;justify-content:center;
               backdrop-filter:blur(8px);box-shadow:0 2px 12px rgba(0,0,0,.45);
               transition:background .2s;-webkit-tap-highlight-color:transparent"
        title="Centrar en mi posición">
      <i class="fa-solid fa-location-crosshairs"></i>
    </button>
    <!-- Seguir automáticamente -->
    <button id="btnSeguir" onclick="toggleSeguir()"
        style="width:44px;height:44px;border-radius:50%;background:rgba(15,23,42,.88);
               border:2px solid transparent;color:#64748b;font-size:17px;cursor:pointer;
               display:flex;align-items:center;justify-content:center;
               backdrop-filter:blur(8px);box-shadow:0 2px 12px rgba(0,0,0,.45);
               transition:all .2s;-webkit-tap-highlight-color:transparent"
        title="Seguir moto automáticamente">
      <i class="fa-solid fa-location-arrow"></i>
    </button>
    <!-- Brújula: rotar mapa según donde mira el celular -->
    <button id="btnBrujula" onclick="toggleBrujula()"
        style="width:44px;height:44px;border-radius:50%;background:rgba(15,23,42,.88);
               border:2px solid transparent;color:#64748b;font-size:17px;cursor:pointer;
               display:flex;align-items:center;justify-content:center;
               backdrop-filter:blur(8px);box-shadow:0 2px 12px rgba(0,0,0,.45);
               transition:all .2s;-webkit-tap-highlight-color:transparent"
        title="Rotar mapa según brújula">
      <i class="fa-solid fa-compass"></i>
    </button>
  </div>

  <!-- Banner instrucción de navegación -->
  <div class="uber-nav-banner" id="uberNavBanner" style="display:none">
    <div class="uber-nav-arrow" id="uberNavArrow"><i class="fa-solid fa-arrow-up"></i></div>
    <div class="uber-nav-info">
      <div class="uber-nav-step" id="uberNavStep">Calculando ruta...</div>
      <div class="uber-nav-dist" id="uberNavDist"></div>
    </div>
  </div>

  <div class="uber-card" id="uberCard">
    <!-- Handle: tap o swipe para colapsar/expandir -->
    <div id="uberCardHandle" onclick="toggleUberCard()"
         style="cursor:pointer;padding:12px 0 10px;margin:-16px -20px 8px;display:flex;align-items:center;justify-content:center">
      <div class="uber-card-handle" style="margin:0"></div>
    </div>

    <!-- Contenido colapsable -->
    <div id="uberCardBody">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <div>
          <div class="uber-order-num" id="uberOrderNum">#—</div>
          <div class="uber-order-name" id="uberOrderName">—</div>
        </div>
        <button onclick="toggleUberCard()" style="background:rgba(255,255,255,.08);border:none;color:rgba(255,255,255,.5);border-radius:8px;padding:6px 10px;font-size:11px;cursor:pointer;font-family:inherit">
          <i class="fa-solid fa-chevron-down" id="uberCollapseIc"></i>
        </button>
      </div>

      <!-- Solo lo que se cobra -->
      <div class="uber-cobro" id="uberCobro" style="display:none">
        <i class="fa-solid fa-coins"></i>
        <div>Cobrar en efectivo: <strong id="uberCobroTotal">—</strong></div>
      </div>

      <div class="uber-route-info">
        <div class="uber-route-point">
          <div class="uber-route-label pickup-lbl"><i class="fa-solid fa-store"></i> Retiro</div>
          <div class="uber-route-val" id="uberPickup">Canetto</div>
        </div>
        <div class="uber-route-point">
          <div class="uber-route-label delivery-lbl"><i class="fa-solid fa-location-dot"></i> Entrega</div>
          <div class="uber-route-val" id="uberDelivery">—</div>
        </div>
      </div>

      <!-- Productos ocultos (solo internamente) -->
      <span id="uberProds" style="display:none">—</span>

      <!-- Cola multi-paquete (visible solo con 2+ pedidos) -->
      <div id="uberPkgQueue" style="display:none"></div>

      <!-- Botones FASE RETIRO (ir a buscar el paquete) -->
      <div class="uber-actions" id="uberActionsPickup">
        <button class="uber-btn uber-btn-tel" onclick="uberLlamar()">
          <i class="fa-solid fa-phone"></i>Llamar
        </button>
        <button class="uber-btn uber-btn-nav" onclick="uberNavegar()">
          <i class="fa-solid fa-diamond-turn-right"></i>Navegar
        </button>
        <button class="uber-btn uber-btn-pkg" onclick="confirmarPaquete()">
          <i class="fa-solid fa-box-open"></i> Ya tengo el paquete
        </button>
      </div>

      <!-- Botones FASE ENTREGA (ir al cliente) -->
      <div class="uber-actions" id="uberActionsDelivery" style="display:none">
        <button class="uber-btn uber-btn-tel" onclick="uberLlamar()">
          <i class="fa-solid fa-phone"></i>Llamar
        </button>
        <button class="uber-btn uber-btn-nav" onclick="uberNavegar()">
          <i class="fa-solid fa-diamond-turn-right"></i>Navegar
        </button>
        <button class="uber-btn uber-btn-ok" id="uberBtnOk" onclick="uberEntregar()">
          <i class="fa-solid fa-circle-check"></i> Marcar entregado
        </button>
      </div>
    </div>
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


  <!-- Tab Pedidos -->
  <div id="tabPedidos" class="tab-content active">

    <!-- Mapa de entregas -->
    <div id="repMapWrap">
      <div id="repMap"></div>
      <button class="map-ruta-btn" onclick="verRutaOptima()">
        <i class="fa-solid fa-route"></i> Ver ruta óptima en Maps
      </button>
    </div>

    <div id="pedidosList" class="pedidos-list"></div>
    <button class="btn-refresh" onclick="cargarPedidos()">
      <i class="fa-solid fa-arrows-rotate"></i> Actualizar
    </button>
  </div>

  <!-- Tab Historial -->
  <div id="tabHistorial" class="tab-content">
    <!-- Filtros -->
    <div id="histFiltros" style="display:flex;gap:8px;padding:14px 16px 4px;overflow-x:auto;scrollbar-width:none">
      <button class="hist-filtro-btn active" data-filtro="todo"   onclick="filtrarHistorial(this)">Todo</button>
      <button class="hist-filtro-btn"        data-filtro="hoy"    onclick="filtrarHistorial(this)">Hoy</button>
      <button class="hist-filtro-btn"        data-filtro="semana" onclick="filtrarHistorial(this)">Esta semana</button>
      <button class="hist-filtro-btn"        data-filtro="mes"    onclick="filtrarHistorial(this)">Este mes</button>
    </div>
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
            <a href="<?= URL_LOGIN ?>/recuperar_password.php"
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
          <a href="https://wa.me/5493765123808" target="_blank"
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

  <!-- Footer nav (fixed bottom) -->
  <nav class="tab-nav">
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
  </nav>

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
          <span class="pedido-urgente-badge" style="display:none">🔥 Demorado</span>
          <span class="pedido-num"></span>
        </div>
        <span class="pedido-total"></span>
      </div>
      <!-- Badge pago al cobrar (visible solo para efectivo+envío) -->
      <div class="pedido-cobro-banner" style="display:none">
        <i class="fa-solid fa-coins"></i>
        <div class="pedido-cobro-body">
          <div class="pedido-cobro-title">Pendiente de Cobro</div>
          <div class="pedido-cobro-rows">
            <div class="pedido-cobro-row">
              <span>Productos</span><span class="cobro-sub"></span>
            </div>
            <div class="pedido-cobro-row row-envio" style="display:none">
              <span><i class="fa-solid fa-motorcycle"></i> Envío</span><span class="cobro-envio"></span>
            </div>
            <div class="pedido-cobro-row cobro-total-row">
              <span>Total a cobrar</span><span class="cobro-total"></span>
            </div>
          </div>
        </div>
      </div>
      <div class="pedido-row">
        <div class="pedido-row-icon icon-indigo"><i class="fa-solid fa-user"></i></div>
        <span class="pedido-nombre"></span>
      </div>
      <div class="pedido-row pedido-row-sucursal" style="display:none">
        <div class="pedido-row-icon" style="background:rgba(200,142,153,.15);color:#c88e99"><i class="fa-solid fa-store"></i></div>
        <span class="pedido-suc-txt" style="font-weight:700;color:#c88e99"></span>
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
        <button class="btn-action btn-uber-map">
          <i class="fa-solid fa-map-location-dot"></i><span>Ver mapa</span>
        </button>
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
     PANTALLA PERMISOS OBLIGATORIOS
══════════════════════════════════════ -->
<div id="pantallaPermisos" style="display:none;position:fixed;inset:0;z-index:10000;
     background:#0f172a;flex-direction:column;align-items:center;justify-content:center;
     padding:28px 24px;overflow-y:auto">

  <!-- Brand -->
  <div style="text-align:center;margin-bottom:28px">
    <div style="font-size:38px;margin-bottom:10px">🛵</div>
    <div style="font-size:22px;font-weight:900;color:#fff;letter-spacing:-0.5px">Canetto Repartidor</div>
    <div style="font-size:13px;color:#64748b;margin-top:5px">Antes de continuar necesitamos estos permisos</div>
  </div>

  <!-- Item Ubicación -->
  <div id="permItem-ubicacion" style="width:100%;max-width:360px;background:#1e293b;border-radius:16px;
       padding:18px;margin-bottom:12px;border:2px solid rgba(255,255,255,.08);transition:border-color .2s">
    <div style="display:flex;align-items:center;gap:14px">
      <div style="width:48px;height:48px;border-radius:14px;background:rgba(59,130,246,.15);
           display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">📍</div>
      <div style="flex:1">
        <div style="font-size:15px;font-weight:800;color:#fff;margin-bottom:3px">Ubicación</div>
        <div style="font-size:12px;color:#64748b;line-height:1.4">Para asignarte pedidos cercanos y mostrarte en el mapa en tiempo real</div>
      </div>
      <div id="permBadge-ubicacion" style="font-size:20px;flex-shrink:0">⏳</div>
    </div>
    <button id="permBtn-ubicacion" onclick="activarUbicacionPerm()"
      style="width:100%;margin-top:14px;padding:12px;background:#3b82f6;color:#fff;border:none;
             border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
      📍 Activar ubicación
    </button>
    <div id="permMsg-ubicacion" style="display:none;margin-top:10px;font-size:12px;color:#f59e0b;
         background:rgba(245,158,11,.08);border-radius:8px;padding:8px 10px;line-height:1.5"></div>
  </div>

  <!-- Item Notificaciones -->
  <div id="permItem-notif" style="width:100%;max-width:360px;background:#1e293b;border-radius:16px;
       padding:18px;margin-bottom:24px;border:2px solid rgba(255,255,255,.08);transition:border-color .2s">
    <div style="display:flex;align-items:center;gap:14px">
      <div style="width:48px;height:48px;border-radius:14px;background:rgba(200,142,153,.15);
           display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">🔔</div>
      <div style="flex:1">
        <div style="font-size:15px;font-weight:800;color:#fff;margin-bottom:3px">Notificaciones</div>
        <div style="font-size:12px;color:#64748b;line-height:1.4">Para avisarte de nuevos pedidos y verificar que seguís activo en el turno</div>
      </div>
      <div id="permBadge-notif" style="font-size:20px;flex-shrink:0">⏳</div>
    </div>
    <button id="permBtn-notif" onclick="activarNotifPerm()"
      style="width:100%;margin-top:14px;padding:12px;background:#c88e99;color:#fff;border:none;
             border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
      🔔 Activar notificaciones
    </button>
    <div id="permMsg-notif" style="display:none;margin-top:10px;font-size:12px;color:#f59e0b;
         background:rgba(245,158,11,.08);border-radius:8px;padding:8px 10px;line-height:1.5"></div>
  </div>

  <!-- Botón continuar -->
  <button id="btnContinuarApp" onclick="continuarApp()" disabled
    style="width:100%;max-width:360px;padding:16px;background:#c88e99;color:#fff;border:none;
           border-radius:14px;font-size:16px;font-weight:800;cursor:pointer;font-family:inherit;
           opacity:.35;transition:opacity .2s">
    Continuar →
  </button>
  <div id="continuarHint" style="margin-top:10px;font-size:12px;color:#475569;text-align:center;max-width:300px">
    Activá los permisos para continuar
  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL CHECK ACTIVIDAD
══════════════════════════════════════ -->
<div id="modalActividad" style="display:none;position:fixed;inset:0;z-index:9997;
     background:rgba(0,0,0,.85);align-items:center;justify-content:center;padding:24px">
  <div style="background:#1e293b;border-radius:22px;padding:30px 24px;max-width:310px;width:100%;
              border:1px solid rgba(200,142,153,.5);text-align:center;animation:slideUp .25s ease">
    <div style="font-size:46px;margin-bottom:14px">👋</div>
    <div style="font-size:19px;font-weight:800;color:#fff;margin-bottom:8px">¿Seguís activo?</div>
    <div style="font-size:13px;color:#94a3b8;line-height:1.65;margin-bottom:22px">
      Tocá el botón para confirmar que seguís en la app.<br>
      Si no respondés, se cerrará tu sesión.
    </div>
    <!-- Barra de countdown -->
    <div style="background:rgba(255,255,255,.08);border-radius:100px;height:7px;margin-bottom:10px;overflow:hidden">
      <div id="actividadBar" style="height:100%;background:#c88e99;border-radius:100px;width:100%"></div>
    </div>
    <div style="font-size:12px;color:#64748b;margin-bottom:22px">
      Cierra sesión en <strong style="color:#f87171" id="actividadSecs">15</strong>s
    </div>
    <button onclick="confirmarActivo()"
      style="width:100%;padding:16px;background:linear-gradient(135deg,#c88e99,#a46678);color:#fff;border:none;
             border-radius:14px;font-size:16px;font-weight:800;cursor:pointer;font-family:inherit;
             box-shadow:0 4px 20px rgba(200,142,153,.35);active:transform:scale(.97)">
      ✅ Sigo activo
    </button>
  </div>
</div>

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
function heartbeat() {
  fetch('api/heartbeat.php').catch(() => {});
}
setInterval(heartbeat, 5 * 60 * 1000);

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
      // Mostrar animación y luego pasar al dashboard
      _repShowOverlay(nombre, () => {
        document.getElementById('dashNombre').textContent = nombre;
        document.getElementById('dashAvatar').textContent = initials(nombre);
        document.getElementById('appLogin').classList.add('hidden');
        document.getElementById('appDash').classList.remove('hidden');
        heartbeat();
        cargarPedidos();
        startAutoRefresh();
        registrarPushRepartidor();
        mostrarPantallaPermisos();
      });
    } else {
      showLoginAlert(data.message || 'Datos incorrectos');
      btn.disabled  = false;
      btn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar';
    }
  } catch (e) {
    showLoginAlert('Error de conexión');
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Ingresar';
  }
}

function _repShowOverlay(nombre, onDone) {
  const overlay = document.getElementById('repLoginOverlay');
  const card    = document.getElementById('repSuccessCard');
  const ring    = document.getElementById('repRing');
  const check   = document.getElementById('repCheck');
  const nameEl  = document.getElementById('repSuccessName');
  const sub     = document.getElementById('repSuccessSub');
  const brand   = document.getElementById('repSuccessBrand');

  nameEl.textContent = '¡Hola, ' + nombre.split(' ')[0] + '!';

  const noTrans = el => { el.style.transition = 'none'; };
  [ring, check, nameEl, sub, brand].forEach(noTrans);
  card.style.transition = 'none';
  ring.style.strokeDashoffset  = '314';
  check.style.strokeDashoffset = '76';
  nameEl.style.opacity = '0'; nameEl.style.transform = 'translateY(12px)';
  sub.style.opacity    = '0'; sub.style.transform    = 'translateY(8px)';
  brand.style.opacity  = '0';
  card.style.transform = 'scale(.6) translateY(24px)'; card.style.opacity = '0';
  overlay.style.opacity = '0'; overlay.style.display = 'flex';

  requestAnimationFrame(() => requestAnimationFrame(() => {
    overlay.style.transition = 'opacity .22s ease'; overlay.style.opacity = '1';
    card.style.transition = 'transform .5s cubic-bezier(.34,1.56,.64,1), opacity .3s ease';
    card.style.transform  = 'scale(1) translateY(0)'; card.style.opacity = '1';
    setTimeout(() => { ring.style.transition  = 'stroke-dashoffset .7s cubic-bezier(.16,1,.3,1)'; ring.style.strokeDashoffset  = '0'; }, 150);
    setTimeout(() => { check.style.transition = 'stroke-dashoffset .4s cubic-bezier(.16,1,.3,1)'; check.style.strokeDashoffset = '0'; }, 750);
    setTimeout(() => { nameEl.style.transition = 'opacity .4s ease, transform .4s cubic-bezier(.16,1,.3,1)'; nameEl.style.opacity = '1'; nameEl.style.transform = 'translateY(0)'; }, 900);
    setTimeout(() => {
      sub.style.transition   = 'opacity .35s ease, transform .35s ease';
      sub.style.opacity      = '1'; sub.style.transform = 'translateY(0)';
      brand.style.transition = 'opacity .5s ease'; brand.style.opacity = '1';
    }, 1050);
    // Ocultar overlay y continuar
    setTimeout(() => {
      overlay.style.opacity = '0';
      setTimeout(() => { overlay.style.display = 'none'; if (onDone) onDone(); }, 250);
    }, 1800);
  }));
}

function showLoginAlert(msg) {
  document.getElementById('loginAlertMsg').textContent = msg;
  const el = document.getElementById('loginAlert');
  el.style.display = 'flex';
}

async function doLogout() {
  await fetch('api/logout.php', { method: 'POST' });
  detenerSistemaActividad();
  clearAutoRefresh();
  // Limpiar fases de pedidos guardadas en localStorage
  Object.keys(localStorage)
    .filter(k => k.startsWith('uber_phase_'))
    .forEach(k => localStorage.removeItem(k));
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
      actualizarMapa([]);
      return;
    }

    actualizarMapa(data.pedidos);

    // Urgentes primero (por minutos de espera desc), luego el resto por id asc
    const pedidosOrdenados = [...data.pedidos].sort((a, b) => {
      if (a.es_urgente && !b.es_urgente) return -1;
      if (!a.es_urgente && b.es_urgente) return  1;
      if (a.es_urgente && b.es_urgente)  return (b.minutos_espera - a.minutos_espera);
      return a.idventas - b.idventas;
    });

    const tpl = document.getElementById('tplPedido');
    list.innerHTML = '';

    pedidosOrdenados.forEach((p, i) => {
      const clone = tpl.content.cloneNode(true);
      // Guardar datos del pedido para verRutaOptima
      const cardRoot = clone.querySelector('.pedido-card');
      if (cardRoot) cardRoot.dataset.pedido = JSON.stringify(p);
      clone.querySelector('.pedido-accent').style.background = ACCENT_COLORS[i % ACCENT_COLORS.length];
      clone.querySelector('.pedido-num').textContent       = '#' + p.idventas;
      clone.querySelector('.pedido-total').textContent     = fmt(p.total);
      clone.querySelector('.pedido-nombre').textContent    = p.cliente_nombre || 'Cliente';
      clone.querySelector('.pedido-dir-txt').textContent   = p.direccion_entrega || 'Sin dirección';
      clone.querySelector('.pedido-prods-txt').textContent = p.productos || '—';

      // Badge urgente si el pedido lleva 20+ minutos esperando
      if (p.es_urgente) {
        const urgBadge = clone.querySelector('.pedido-urgente-badge');
        if (urgBadge) {
          urgBadge.style.display = '';
          urgBadge.title = `Esperando ${p.minutos_espera} min`;
        }
      }

      // Sucursal de despacho (solo para envíos)
      if (p.sucursal_nombre && p.tipo_entrega === 'envio') {
        const rowSuc = clone.querySelector('.pedido-row-sucursal');
        if (rowSuc) {
          rowSuc.style.display = 'flex';
          rowSuc.querySelector('.pedido-suc-txt').textContent = 'Retirar en: ' + p.sucursal_nombre;
        }
      }

      // Banner de cobro según método de pago
      const metodoNombre = (p.metodo_pago || '').toLowerCase();
      const esEfectivo   = metodoNombre.includes('efectivo') || metodoNombre.includes('cash');
      const costoEnvio   = parseFloat(p.costo_envio || 0);
      const cobroBanner  = clone.querySelector('.pedido-cobro-banner');
      if (p.tipo_entrega === 'envio') {
        if (esEfectivo) {
          // Efectivo: cobrar productos + envío (total completo)
          cobroBanner.style.display = 'flex';
          const subtotal = costoEnvio > 0 ? p.total - costoEnvio : p.total;
          clone.querySelector('.cobro-sub').textContent   = fmt(subtotal);
          clone.querySelector('.cobro-total').textContent = fmt(p.total);
          if (costoEnvio > 0) {
            const rowEnvio = clone.querySelector('.row-envio');
            rowEnvio.style.display = 'flex';
            clone.querySelector('.cobro-envio').textContent = fmt(costoEnvio);
          }
        } else if (costoEnvio > 0) {
          // Otro método de pago: cobrar solo el envío
          cobroBanner.style.display = 'flex';
          clone.querySelector('.pedido-cobro-title').textContent = 'Cobrar envío';
          clone.querySelector('.cobro-sub').closest('.pedido-cobro-row').style.display = 'none';
          clone.querySelector('.cobro-total').textContent = fmt(costoEnvio);
          const rowEnvio = clone.querySelector('.row-envio');
          rowEnvio.style.display = 'none';
        }
      }

      const btnTel = clone.querySelector('.btn-tel');
      if (p.cliente_celular) {
        btnTel.href = 'tel:' + p.cliente_celular.replace(/\D/g,'');
      } else {
        btnTel.classList.add('disabled');
        btnTel.href = '#';
        btnTel.addEventListener('click', e => e.preventDefault());
      }

      // Botón mapa Uber — pasa todos los pedidos para modo multi
      const btnUberMap = clone.querySelector('.btn-uber-map');
      if (p.lat_entrega && p.lng_entrega || p.direccion_entrega) {
        btnUberMap.addEventListener('click', () => {
          const allP = Array.from(document.querySelectorAll('.pedido-card[data-pedido]'))
            .map(c => { try { return JSON.parse(c.dataset.pedido); } catch(e){} }).filter(Boolean);
          const multi = allP.length > 1 ? optimizarRuta(allP) : null;
          abrirUberMap(p, multi);
        });
      } else {
        btnUberMap.classList.add('disabled');
        btnUberMap.addEventListener('click', e => e.preventDefault());
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
      // Guardar fecha en el elemento raíz para filtrar después
      const card = clone.querySelector('.hist-card');
      if (card) card.dataset.fecha = (p.updated_at || p.fecha || '').substring(0, 10);
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
   FILTROS HISTORIAL
════════════════════════════════════════ */
function filtrarHistorial(btn) {
  document.querySelectorAll('.hist-filtro-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  const filtro = btn.dataset.filtro;
  const hoy    = new Date();
  const dHoy   = hoy.toISOString().substring(0, 10);

  const lunesDiff = (hoy.getDay() + 6) % 7; // 0=lun
  const lunes = new Date(hoy); lunes.setDate(hoy.getDate() - lunesDiff);
  const dLunes = lunes.toISOString().substring(0, 10);

  const primeroMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  const dMes = primeroMes.toISOString().substring(0, 10);

  let visible = 0;
  document.querySelectorAll('#historialList .hist-card').forEach(card => {
    const fecha = card.dataset.fecha || '';
    let show = true;
    if (filtro === 'hoy')    show = fecha === dHoy;
    if (filtro === 'semana') show = fecha >= dLunes;
    if (filtro === 'mes')    show = fecha >= dMes;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  // Mostrar mensaje si no hay resultados
  let emptyEl = document.getElementById('histFiltroEmpty');
  if (!emptyEl) {
    emptyEl = document.createElement('div');
    emptyEl.id = 'histFiltroEmpty';
    emptyEl.className = 'empty-state';
    emptyEl.innerHTML = `<div class="empty-state-icon slate"><i class="fa-solid fa-calendar-xmark"></i></div>
      <h3>Sin entregas</h3><p>No hay pedidos para este período.</p>`;
    document.getElementById('historialList').after(emptyEl);
  }
  emptyEl.style.display = visible === 0 ? '' : 'none';
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
      _repShowOverlay(nombre, () => {
        document.getElementById('dashNombre').textContent = nombre;
        document.getElementById('dashAvatar').textContent = initials(nombre);
        document.getElementById('appLogin').classList.add('hidden');
        document.getElementById('appDash').classList.remove('hidden');
        heartbeat();
        cargarPedidos();
        startAutoRefresh();
        registrarPushRepartidor();
        mostrarPantallaPermisos();
      });
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
  heartbeat();
  cargarPedidos();
  startAutoRefresh();
  // Registrar SW siempre al cargar (para que _swReg esté listo antes de necesitarlo)
  registrarPushRepartidor();
  mostrarPantallaPermisos();
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet-rotate@0.2.8/dist/leaflet-rotate-src.js"></script>
<script>
/* ════════════════════════════════════════
   MAPA UBER — FULLSCREEN
   Coordenadas de la tienda Canetto
════════════════════════════════════════ */
// Sucursales reales desde la BD (inyectadas por PHP)
const SUCURSALES = <?= json_encode(array_map(fn($s) => [
    'id'        => (int)$s['idsucursal'],
    'nombre'    => $s['nombre'],
    'direccion' => $s['direccion'] ?? '',
    'lat'       => $s['latitud']  !== null ? (float)$s['latitud']  : null,
    'lng'       => $s['longitud'] !== null ? (float)$s['longitud'] : null,
], $sucursalesJS), JSON_UNESCAPED_UNICODE) ?>;

// Sucursal principal (primera con coordenadas)
const _SUC0 = SUCURSALES.find(s => s.lat && s.lng) || SUCURSALES[0] || {};
const CANETTO_LAT    = _SUC0.lat    ?? -27.3621;
const CANETTO_LNG    = _SUC0.lng    ?? -55.9008;
const CANETTO_NOMBRE = _SUC0.nombre ?? 'Canetto Cookies';

let _uberMap      = null;
let _uberMarkers  = [];
let _uberRoute    = null;
let _uberDriver   = null;
let _uberPedido   = null;
let _uberWatcher  = null;
let _uberPhase    = 'pickup'; // 'pickup' | 'delivery'
let _seguirMoto   = false;    // seguimiento automático de la moto
let _brujulaMode  = false;    // rotar mapa según brújula/heading
let _lastHeading  = 0;        // último heading conocido (grados desde norte)
let _bearingRaf   = null;     // handle requestAnimationFrame para throttle
let _uberSteps    = [];
let _uberStepIdx  = 0;
let _uberMulti    = []; // todos los pedidos en modo multi-entrega
let _uberMultiIdx = 0;  // índice actual en la cola multi

function actualizarMapa(pedidos) {
  // Se llama desde cargarPedidos — no hace nada visible aquí en modo Uber
}

function iconDiv(html, size = 44) {
  return L.divIcon({ className: '', html, iconSize: [size, size], iconAnchor: [size/2, size/2], popupAnchor: [0, -size/2] });
}

function createDriverIcon(color) {
  return iconDiv(`<div style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;filter:drop-shadow(0 2px 8px rgba(0,0,0,.7))"><i class="fa-solid fa-motorcycle" style="font-size:26px;color:${color}"></i></div>`, 38);
}

function _setMapBtnStyle(btn, active, activeColor, activeShadow) {
  if (active) {
    btn.style.background  = activeColor;
    btn.style.borderColor = '#fff';
    btn.style.color       = '#fff';
    btn.style.boxShadow   = activeShadow;
  } else {
    btn.style.background  = 'rgba(15,23,42,.88)';
    btn.style.borderColor = 'transparent';
    btn.style.color       = '#64748b';
    btn.style.boxShadow   = '0 2px 12px rgba(0,0,0,.45)';
  }
}

function initUberMap() {
  if (_uberMap) return;
  _uberMap = L.map('uberMapFull', {
    zoomControl:        false,
    attributionControl: false,
    rotate:             true,   // leaflet-rotate plugin
    touchRotate:        true,   // rotar con dos dedos
    bearing:            0,
  });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
  }).addTo(_uberMap);
  // Sin botones de zoom — se usa pinch-to-zoom
}

async function abrirUberMap(pedido, multiPedidos) {
  _uberPedido   = pedido;
  _uberSteps    = [];
  _uberStepIdx  = 0;
  _uberMulti    = multiPedidos || [];
  _uberMultiIdx = multiPedidos ? multiPedidos.findIndex(p => p.idventas === pedido.idventas) : 0;

  // Restaurar fase: si ya tenía el paquete antes de cerrar el mapa, volver a delivery
  const faseGuardada = localStorage.getItem('uber_phase_' + pedido.idventas);
  _uberPhase = faseGuardada === 'delivery' ? 'delivery' : 'pickup';

  const screen = document.getElementById('uberMapScreen');
  screen.classList.add('active');
  document.body.style.overflow = 'hidden';

  if (_uberCardCollapsed) toggleUberCard();

  // Badge y botones según fase restaurada
  const badge = document.getElementById('uberStatusBadge');
  if (_uberPhase === 'delivery') {
    badge.className  = 'uber-status-badge phase-delivery';
    badge.textContent = 'En camino';
    document.getElementById('uberActionsPickup').style.display   = 'none';
    document.getElementById('uberActionsDelivery').style.display = '';
  } else {
    badge.className  = 'uber-status-badge phase-pickup';
    badge.textContent = 'Yendo a retirar';
    document.getElementById('uberActionsPickup').style.display   = '';
    document.getElementById('uberActionsDelivery').style.display = 'none';
  }
  document.getElementById('uberNavBanner').style.display = 'none';

  // Poblar card
  document.getElementById('uberOrderNum').textContent  = '#' + pedido.idventas;
  document.getElementById('uberOrderName').textContent = pedido.cliente_nombre || 'Cliente';
  document.getElementById('uberDelivery').textContent  = pedido.direccion_entrega || 'Sin dirección';
  document.getElementById('uberPickup').textContent    = pedido.sucursal_nombre  || 'Canetto';
  document.getElementById('uberProds').textContent     = pedido.productos || '—';
  document.getElementById('uberBtnOk').dataset.id      = pedido.idventas;

  // Opacidad teléfono según disponibilidad
  document.querySelectorAll('.uber-btn-tel').forEach(btn => {
    btn.style.opacity = pedido.cliente_celular ? '1' : '.4';
  });

  // Cola multi-paquete
  renderPkgQueue(_uberMulti, _uberMultiIdx);

  // Banner cobro según método de pago
  const cobro = document.getElementById('uberCobro');
  const uberCobroLbl = cobro.querySelector('div');
  const metodoNombre = (pedido.metodo_pago || '').toLowerCase();
  const esEfectivoU  = metodoNombre.includes('efectivo') || metodoNombre.includes('cash');
  const costoEnvioU  = parseFloat(pedido.costo_envio || 0);
  if (pedido.tipo_entrega === 'envio') {
    if (esEfectivoU) {
      cobro.style.display = 'flex';
      uberCobroLbl.innerHTML = 'Cobrar en efectivo: <strong id="uberCobroTotal">$' + parseFloat(pedido.total || 0).toLocaleString('es-AR') + '</strong>';
    } else if (costoEnvioU > 0) {
      cobro.style.display = 'flex';
      uberCobroLbl.innerHTML = 'Cobrar envío: <strong id="uberCobroTotal">$' + costoEnvioU.toLocaleString('es-AR') + '</strong>';
    } else {
      cobro.style.display = 'none';
    }
  } else {
    cobro.style.display = 'none';
  }

  // Init mapa
  initUberMap();
  setTimeout(() => {
    _uberMap.invalidateSize();

    // Dibujar ruta inmediatamente (desde la tienda si no hay GPS aún) — no esperar GPS
    dibujarRuta(pedido);

    if (!_uberDriver && navigator.geolocation) {
      // Obtener posición en paralelo: usar caché reciente o baja precisión para ser rápido
      navigator.geolocation.getCurrentPosition(pos => {
        const lat   = pos.coords.latitude;
        const lng   = pos.coords.longitude;
        const color = _uberPhase === 'pickup' ? '#f59e0b' : '#c88e99';
        if (_uberDriver) {
          _uberDriver.setLatLng([lat, lng]);
        } else {
          _uberDriver = L.marker([lat, lng], { icon: createDriverIcon(color), zIndexOffset: 1000 })
            .bindPopup('<strong>Tu posición</strong>').addTo(_uberMap);
        }
        dibujarRuta(pedido); // Actualizar ruta con posición real
      }, null, { enableHighAccuracy: false, timeout: 5000, maximumAge: 60000 });
    }
  }, 200);

  // Seguir posición del repartidor
  iniciarGeolocalizacion();
}

let _uberCardCollapsed = false;
function toggleUberCard() {
  _uberCardCollapsed = !_uberCardCollapsed;
  const body = document.getElementById('uberCardBody');
  const ic   = document.getElementById('uberCollapseIc');
  if (_uberCardCollapsed) {
    body.style.cssText = 'overflow:hidden;max-height:0;opacity:0;transition:max-height .3s ease,opacity .2s ease';
    ic.className = 'fa-solid fa-chevron-up';
  } else {
    body.style.cssText = 'overflow:visible;max-height:600px;opacity:1;transition:max-height .4s ease,opacity .25s ease';
    ic.className = 'fa-solid fa-chevron-down';
  }
}

// Drag-to-collapse en la uber-card
(function() {
  let startY = 0, startH = 0, dragging = false;
  const getCard = () => document.getElementById('uberCard');
  const getHandle = () => document.getElementById('uberCardHandle');

  function onTouchStart(e) {
    startY = e.touches[0].clientY;
    startH = getCard().getBoundingClientRect().height;
    dragging = true;
  }
  function onTouchMove(e) {
    if (!dragging) return;
    const dy = e.touches[0].clientY - startY;
    if (dy > 10) e.preventDefault(); // evita scroll mientras arrastra
  }
  function onTouchEnd(e) {
    if (!dragging) return;
    dragging = false;
    const dy = e.changedTouches[0].clientY - startY;
    if (dy > 60 && !_uberCardCollapsed)  toggleUberCard(); // swipe ↓ colapsa
    if (dy < -60 && _uberCardCollapsed)  toggleUberCard(); // swipe ↑ expande
  }

  document.addEventListener('DOMContentLoaded', () => {
    const h = document.getElementById('uberCardHandle');
    if (!h) return;
    h.addEventListener('touchstart', onTouchStart, { passive: true });
    h.addEventListener('touchmove',  onTouchMove,  { passive: false });
    h.addEventListener('touchend',   onTouchEnd,   { passive: true });
  });
})();

function cerrarUberMap() {
  document.getElementById('uberMapScreen').classList.remove('active');
  document.body.style.overflow = '';
  if (_uberWatcher !== null) {
    navigator.geolocation.clearWatch(_uberWatcher);
    _uberWatcher = null;
  }
  // Resetear controles al salir
  if (_seguirMoto)  toggleSeguir();
  if (_brujulaMode) toggleBrujula();
}

function uberLlamar() {
  if (_uberPedido?.cliente_celular)
    window.location.href = 'tel:' + _uberPedido.cliente_celular.replace(/\D/g, '');
}

function uberNavegar() {
  if (!_uberPedido) return;
  const p = _uberPedido;
  if (p.lat_entrega && p.lng_entrega) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${p.lat_entrega},${p.lng_entrega}&travelmode=driving`, '_blank');
  } else if (p.direccion_entrega) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(p.direccion_entrega)}&travelmode=driving`, '_blank');
  }
}

async function uberEntregar() {
  const btn = document.getElementById('uberBtnOk');
  const id  = parseInt(btn.dataset.id);
  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
  try {
    const res  = await fetch('api/marcar_entregado.php', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ id_venta: id }),
    });
    const data = await res.json();
    if (data.success) {
      // Limpiar fase guardada al entregar el pedido
      localStorage.removeItem('uber_phase_' + id);
      cerrarUberMap();
      mostrarEntregaAnimacion(id, _uberPedido?.cliente_nombre);
    } else {
      alert(data.message || 'No se pudo marcar como entregado');
      btn.disabled  = false;
      btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Marcar entregado';
    }
  } catch(e) {
    alert('Error de conexión');
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Marcar entregado';
  }
}

/* ════════════════════════════════════════
   FASE: confirmar que tiene el paquete
════════════════════════════════════════ */
function confirmarPaquete() {
  _uberPhase   = 'delivery';
  _uberSteps   = [];
  _uberStepIdx = 0;

  // Persistir fase para que al cerrar y abrir el mapa siga en delivery
  if (_uberPedido?.idventas) {
    localStorage.setItem('uber_phase_' + _uberPedido.idventas, 'delivery');
  }

  const badge = document.getElementById('uberStatusBadge');
  badge.className  = 'uber-status-badge phase-delivery';
  badge.textContent = 'En camino';

  document.getElementById('uberActionsPickup').style.display   = 'none';
  document.getElementById('uberActionsDelivery').style.display = '';
  document.getElementById('uberNavBanner').style.display        = 'none';

  dibujarRuta(_uberPedido);
}

/* ════════════════════════════════════════
   MULTI-PAQUETE
════════════════════════════════════════ */
function haversineM(lat1, lng1, lat2, lng2) {
  const R = 6371000, r = Math.PI / 180;
  const dLat = (lat2 - lat1) * r, dLng = (lng2 - lng1) * r;
  const a = Math.sin(dLat/2)**2 + Math.cos(lat1*r)*Math.cos(lat2*r)*Math.sin(dLng/2)**2;
  return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function getSucLatLng(pedido) {
  if (pedido.sucursal_lat && pedido.sucursal_lng)
    return { lat: parseFloat(pedido.sucursal_lat), lng: parseFloat(pedido.sucursal_lng) };
  return { lat: CANETTO_LAT, lng: CANETTO_LNG };
}

function optimizarRuta(pedidos) {
  function nearestNeighbor(list, start) {
    let cur = start;
    const rem = list.filter(p => p.lat_entrega && p.lng_entrega).map(p => ({...p}));
    const sin = list.filter(p => !p.lat_entrega || !p.lng_entrega);
    const ord = [];
    while (rem.length) {
      let minD = Infinity, nearest = null, ni = -1;
      rem.forEach((p, i) => {
        const d = haversineM(cur.lat, cur.lng, parseFloat(p.lat_entrega), parseFloat(p.lng_entrega));
        if (d < minD) { minD = d; nearest = p; ni = i; }
      });
      ord.push(nearest);
      cur = { lat: parseFloat(nearest.lat_entrega), lng: parseFloat(nearest.lng_entrega) };
      rem.splice(ni, 1);
    }
    return [...ord, ...sin];
  }

  const startPoint = pedidos.length ? getSucLatLng(pedidos[0]) : { lat: CANETTO_LAT, lng: CANETTO_LNG };
  const urgentes   = pedidos.filter(p => p.es_urgente);
  const normales   = pedidos.filter(p => !p.es_urgente);

  // Urgentes siempre primero (nearest-neighbor entre ellos), luego normales
  const ordU = nearestNeighbor(urgentes, startPoint);
  const lastU = ordU.filter(p => p.lat_entrega && p.lng_entrega).slice(-1)[0];
  const startN = lastU
    ? { lat: parseFloat(lastU.lat_entrega), lng: parseFloat(lastU.lng_entrega) }
    : startPoint;
  const ordN = nearestNeighbor(normales, startN);

  return [...ordU, ...ordN];
}

function renderPkgQueue(pedidos, currentIdx) {
  const el = document.getElementById('uberPkgQueue');
  if (!pedidos || pedidos.length <= 1) { el.style.display = 'none'; return; }
  el.style.display = '';
  el.innerHTML = `<div class="uber-pkg-queue">
    <div class="uber-pkg-queue-title"><i class="fa-solid fa-route"></i> Ruta óptima · ${pedidos.length} entregas</div>
    ${pedidos.map((p, i) => {
      const cls      = i < currentIdx ? 'pkg-done' : i === currentIdx ? 'pkg-current' : 'pkg-pending';
      const dot      = i < currentIdx ? '<i class="fa-solid fa-check" style="font-size:9px"></i>' : (i+1);
      const urgBadge = p.es_urgente ? `<span class="uber-pkg-urgente">🔥 DEMORADO</span>` : '';
      return `<div class="uber-pkg-row ${cls}">
        <div class="uber-pkg-dot">${dot}</div>
        <div style="flex:1;min-width:0;display:flex;align-items:center;gap:4px;flex-wrap:wrap">
          <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${p.cliente_nombre||'Cliente'}</span>
          ${urgBadge}
          <div style="width:100%;font-size:10px;opacity:.55">#${p.idventas} · ${p.direccion_entrega||'Sin dir.'}</div>
        </div>
      </div>`;
    }).join('')}
  </div>`;
}

/* ════════════════════════════════════════
   INSTRUCCIONES DE NAVEGACIÓN PASO A PASO
════════════════════════════════════════ */
const NAV_ICONS = {
  'sharp right':'turn-right','right':'turn-right','slight right':'turn-right',
  'straight':'arrow-up',
  'sharp left':'turn-left','left':'turn-left','slight left':'turn-left',
  'uturn':'rotate-left',
};
const NAV_LABELS = {
  'sharp right':'Girá bien a la derecha','right':'Girá a la derecha','slight right':'Mantené derecha',
  'straight':'Seguí recto',
  'sharp left':'Girá bien a la izquierda','left':'Girá a la izquierda','slight left':'Mantené izquierda',
  'uturn':'Doblá en U',
};

function fmtDistNav(m) {
  if (m < 50)   return '¡Llegaste!';
  if (m < 200)  return Math.round(m / 10) * 10 + ' m';
  if (m < 1000) return Math.round(m / 50) * 50 + ' m';
  return (m / 1000).toFixed(1) + ' km';
}

function updateNavBanner(lat, lng) {
  if (!_uberSteps.length) return;
  // Avanzar paso si el conductor está cerca del waypoint
  while (_uberStepIdx < _uberSteps.length - 1) {
    const [sLng, sLat] = _uberSteps[_uberStepIdx].maneuver.location;
    if (haversineM(lat, lng, sLat, sLng) < 40) _uberStepIdx++;
    else break;
  }
  const step = _uberSteps[_uberStepIdx];
  if (!step) return;
  const [sLng, sLat] = step.maneuver.location;
  const dist     = haversineM(lat, lng, sLat, sLng);
  const type     = step.maneuver.type || '';
  const modifier = step.maneuver.modifier || 'straight';
  let icon  = type === 'arrive' ? 'flag-checkered' : type === 'roundabout' ? 'rotate-right' : (NAV_ICONS[modifier] || 'arrow-up');
  let label = type === 'arrive' ? '¡Llegaste al destino!' : (NAV_LABELS[modifier] || 'Continuá');
  if (step.name) label += ` en ${step.name}`;

  document.getElementById('uberNavArrow').innerHTML = `<i class="fa-solid fa-${icon}"></i>`;
  document.getElementById('uberNavStep').textContent = label;
  document.getElementById('uberNavDist').textContent  = fmtDistNav(dist);
  document.getElementById('uberNavBanner').style.display = 'flex';
}

/* ════════════════════════════════════════
   DIBUJAR RUTA (phase-aware + steps OSRM)
════════════════════════════════════════ */
async function dibujarRuta(pedido) {
  _uberMarkers.forEach(m => _uberMap.removeLayer(m));
  _uberMarkers = [];
  if (_uberRoute) {
    if (Array.isArray(_uberRoute)) { _uberRoute.forEach(r => _uberMap.removeLayer(r)); }
    else _uberMap.removeLayer(_uberRoute);
    _uberRoute = null;
  }

  // Coordenadas de la sucursal del pedido (o fallback a la principal)
  const sucPedido = pedido.sucursal_lat && pedido.sucursal_lng
    ? { lat: parseFloat(pedido.sucursal_lat), lng: parseFloat(pedido.sucursal_lng),
        nombre: pedido.sucursal_nombre || CANETTO_NOMBRE, direccion: pedido.sucursal_direccion || '' }
    : { lat: CANETTO_LAT, lng: CANETTO_LNG, nombre: CANETTO_NOMBRE, direccion: '' };
  const tiendaLat = sucPedido.lat, tiendaLng = sucPedido.lng;
  const tiendaNombre = sucPedido.nombre;
  const destLat   = parseFloat(pedido.lat_entrega || 0);
  const destLng   = parseFloat(pedido.lng_entrega || 0);

  const isPickup   = _uberPhase === 'pickup';
  const routeColor = isPickup ? '#f59e0b' : '#c88e99';

  // Marcador tienda
  const mTienda = L.marker([tiendaLat, tiendaLng], { icon: iconDiv(
    `<div style="background:#f59e0b;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 3px 12px rgba(0,0,0,.5);font-size:20px">🏪</div>`, 44)
  }).bindPopup(`<strong>${tiendaNombre}</strong><br><small>${sucPedido.direccion||'Punto de retiro'}</small>`).addTo(_uberMap);
  _uberMarkers.push(mTienda);

  const bounds = [[tiendaLat, tiendaLng]];

  // En fase retiro: ruta driver→tienda. En entrega: ruta driver→cliente (o tienda→cliente si no hay pos)
  let fromLat = tiendaLat, fromLng = tiendaLng;
  if (_uberDriver) {
    const pos = _uberDriver.getLatLng();
    fromLat = pos.lat; fromLng = pos.lng;
  }

  if (isPickup) {
    // Solo marcador tienda, ruta desde posición actual → tienda
    try {
      const osrm  = await fetch(`https://router.project-osrm.org/route/v1/driving/${fromLng},${fromLat};${tiendaLng},${tiendaLat}?overview=full&geometries=geojson&steps=true`);
      const rData = await osrm.json();
      if (rData.routes?.length) {
        const route  = rData.routes[0];
        const coords = route.geometry.coordinates.map(c => [c[1], c[0]]);
        const poly1  = L.polyline(coords, { color: routeColor, weight: 5, opacity: .9, lineCap: 'round', lineJoin: 'round' }).addTo(_uberMap);
        const poly2  = L.polyline(coords, { color: '#fff', weight: 2, opacity: .35, dashArray: '6 12' }).addTo(_uberMap);
        _uberRoute   = [poly1, poly2];
        // Guardar steps para la navegación
        _uberSteps   = route.legs?.[0]?.steps || [];
        _uberStepIdx = 0;
        bounds.push(...coords.filter((_, i) => i % 8 === 0));
      }
    } catch(e) {
      _uberRoute = [L.polyline([[fromLat, fromLng], [tiendaLat, tiendaLng]], { color: routeColor, weight: 4, dashArray: '8 10' }).addTo(_uberMap)];
    }
    _uberMap.fitBounds(bounds, { padding: [60, 60], maxZoom: 16 });

  } else if (destLat && destLng) {
    // Fase entrega: marcador destino + ruta
    const mDest = L.marker([destLat, destLng], { icon: iconDiv(
      `<div style="background:#f43f5e;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 3px 12px rgba(0,0,0,.5);font-size:20px">📍</div>`, 44)
    }).bindPopup(`<strong>${pedido.cliente_nombre||'Cliente'}</strong><br><small>${pedido.direccion_entrega||''}</small>`).addTo(_uberMap);
    _uberMarkers.push(mDest);
    bounds.push([destLat, destLng]);

    try {
      const osrm  = await fetch(`https://router.project-osrm.org/route/v1/driving/${fromLng},${fromLat};${destLng},${destLat}?overview=full&geometries=geojson&steps=true`);
      const rData = await osrm.json();
      if (rData.routes?.length) {
        const route  = rData.routes[0];
        const coords = route.geometry.coordinates.map(c => [c[1], c[0]]);
        const poly1  = L.polyline(coords, { color: routeColor, weight: 5, opacity: .9, lineCap: 'round', lineJoin: 'round' }).addTo(_uberMap);
        const poly2  = L.polyline(coords, { color: '#fff', weight: 2, opacity: .35, dashArray: '6 12' }).addTo(_uberMap);
        _uberRoute   = [poly1, poly2];
        _uberSteps   = route.legs?.[0]?.steps || [];
        _uberStepIdx = 0;
        bounds.push(...coords.filter((_, i) => i % 8 === 0));
      }
    } catch(e) {
      _uberRoute = [L.polyline([[fromLat, fromLng], [destLat, destLng]], { color: routeColor, weight: 4, dashArray: '8 10' }).addTo(_uberMap)];
    }
    _uberMap.fitBounds(bounds, { padding: [60, 60], maxZoom: 16 });
  } else {
    _uberMap.setView([tiendaLat, tiendaLng], 14);
  }
}

function iniciarGeolocalizacion() {
  if (!navigator.geolocation) return;

  const updateDriver = pos => {
    const lat     = pos.coords.latitude;
    const lng     = pos.coords.longitude;
    const heading = pos.coords.heading; // GPS heading cuando se mueve (grados desde norte)

    // Usar heading GPS cuando hay movimiento real (speed > 0.5 m/s)
    if (heading !== null && !isNaN(heading) && (pos.coords.speed || 0) > 0.5) {
      _aplicarHeading(heading);
    }

    if (_uberDriver) _uberMap.removeLayer(_uberDriver);
    _uberDriver = L.marker([lat, lng], {
      icon: createDriverIcon(_uberPhase === 'pickup' ? '#f59e0b' : '#c88e99'),
      zIndexOffset: 1000,
    }).bindPopup('<strong>Tu posición</strong>').addTo(_uberMap);

    // Seguimiento automático
    if (_seguirMoto && _uberMap) {
      _uberMap.setView([lat, lng], _uberMap.getZoom(), { animate: true });
    }

    updateNavBanner(lat, lng);
  };

  // Al arrastrar el mapa manualmente → desactivar seguimiento
  _uberMap.on('dragstart', () => {
    if (_seguirMoto) toggleSeguir();
  });

  _uberWatcher = navigator.geolocation.watchPosition(updateDriver, () => {}, {
    enableHighAccuracy: true, maximumAge: 5000, timeout: 15000,
  });
}

/* Centrar el mapa en la posición actual de la moto */
function centrarEnMoto() {
  if (!_uberDriver || !_uberMap) return;
  const pos = _uberDriver.getLatLng();
  _uberMap.setView([pos.lat, pos.lng], 16, { animate: true });
}

function toggleSeguir() {
  _seguirMoto = !_seguirMoto;
  _setMapBtnStyle(document.getElementById('btnSeguir'), _seguirMoto, '#c88e99', '0 2px 16px rgba(200,142,153,.6)');
  if (_seguirMoto) centrarEnMoto();
}

function _aplicarHeading(deg) {
  if (!_uberMap || isNaN(deg)) return;
  _lastHeading = deg;
  if (!_brujulaMode) return;
  // Throttle: máximo 1 setBearing por frame de animación (~60fps)
  if (_bearingRaf) return;
  _bearingRaf = requestAnimationFrame(() => {
    _uberMap.setBearing(_lastHeading);
    _bearingRaf = null;
  });
}

function toggleBrujula() {
  _brujulaMode = !_brujulaMode;
  _setMapBtnStyle(document.getElementById('btnBrujula'), _brujulaMode, '#10b981', '0 2px 16px rgba(16,185,129,.5)');
  if (_brujulaMode) {
    if (_uberMap) _uberMap.setBearing(_lastHeading);
    if (typeof DeviceOrientationEvent !== 'undefined' &&
        typeof DeviceOrientationEvent.requestPermission === 'function') {
      DeviceOrientationEvent.requestPermission()
        .then(state => { if (state !== 'granted') toggleBrujula(); })
        .catch(() => toggleBrujula());
    }
  } else {
    if (_uberMap) _uberMap.setBearing(0);
  }
}

/* Listener de brújula del dispositivo */
window.addEventListener('deviceorientation', e => {
  if (!_brujulaMode) return;
  let heading;
  if (e.webkitCompassHeading !== undefined && e.webkitCompassHeading !== null) {
    // iOS: webkitCompassHeading ya es grados desde norte (0–360)
    heading = e.webkitCompassHeading;
  } else if (e.alpha !== null) {
    // Android: alpha es rotación Z, 0 = apunta al norte magnético
    heading = (360 - e.alpha) % 360;
  }
  _aplicarHeading(heading);
}, { passive: true });

/* ════════════════════════════════════════
   RUTA ÓPTIMA MULTI-PAQUETE
════════════════════════════════════════ */
function verRutaOptima() {
  // Recoger todos los pedidos actuales del DOM
  const cards = document.querySelectorAll('.pedido-card[data-pedido]');
  if (!cards.length) return;
  try {
    const pedidos = Array.from(cards).map(c => JSON.parse(c.dataset.pedido)).filter(Boolean);
    if (!pedidos.length) return;
    const ordenados = optimizarRuta(pedidos);
    // Abrir mapa con el primer pedido y la cola completa
    abrirUberMap(ordenados[0], ordenados);
  } catch(e) { console.warn('verRutaOptima:', e); }
}

/* ════════════════════════════════════════
   TRACKING DE UBICACIÓN (para el admin)
════════════════════════════════════════ */
let _trackingWatcher = null;
let _trackingTimer   = null;
let _lastSentAt      = 0;
const TRACKING_MIN_MS = 2000; // mínimo 2s entre envíos al servidor

function enviarUbicacion(lat, lng) {
  fetch('api/update_ubicacion.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ lat, lng }),
  }).catch(() => {});
}

function iniciarTracking() {
  if (!navigator.geolocation) return;
  detenerTracking();

  const onPos = pos => {
    const ahora = Date.now();
    if (ahora - _lastSentAt < TRACKING_MIN_MS) return;
    _lastSentAt = ahora;
    enviarUbicacion(pos.coords.latitude, pos.coords.longitude);
  };

  // Enviar posición inmediata al arrancar (sin esperar movimiento)
  navigator.geolocation.getCurrentPosition(
    pos => { _lastSentAt = Date.now(); enviarUbicacion(pos.coords.latitude, pos.coords.longitude); },
    () => {},
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 30000 }
  );

  // watchPosition: dispara automáticamente en cada cambio de posición
  _trackingWatcher = navigator.geolocation.watchPosition(
    onPos,
    () => {},
    { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 }
  );

  // Heartbeat cada 5 minutos para mantenerse activo aunque no haya movimiento
  _trackingTimer = setInterval(() => {
    navigator.geolocation.getCurrentPosition(
      pos => { _lastSentAt = Date.now(); enviarUbicacion(pos.coords.latitude, pos.coords.longitude); },
      () => {},
      { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
    );
  }, 5 * 60 * 1000);
}

function detenerTracking() {
  if (_trackingWatcher !== null) {
    navigator.geolocation.clearWatch(_trackingWatcher);
    _trackingWatcher = null;
  }
  if (_trackingTimer) { clearInterval(_trackingTimer); _trackingTimer = null; }
}


/* ════════════════════════════════════════
   PANTALLA DE PERMISOS OBLIGATORIOS
════════════════════════════════════════ */
let _permUbicOk  = false;
let _permNotifOk = false;

function _setBadge(tipo, estado) {
  // estado: 'ok' | 'err' | 'warn' | 'pending'
  const badge = document.getElementById('permBadge-' + tipo);
  const item  = document.getElementById('permItem-'  + tipo);
  const btn   = document.getElementById('permBtn-'   + tipo);
  const icons = { ok: '✅', err: '❌', warn: '⚠️', pending: '⏳' };
  const borders = { ok: '#10b981', err: '#f43f5e', warn: '#f59e0b', pending: 'rgba(255,255,255,.08)' };
  if (badge) badge.textContent = icons[estado] || '⏳';
  if (item)  item.style.borderColor = borders[estado] || 'rgba(255,255,255,.08)';
  if (estado === 'ok' && btn) { btn.style.display = 'none'; }
}

function _setMsg(tipo, msg) {
  const el = document.getElementById('permMsg-' + tipo);
  if (!el) return;
  if (msg) { el.textContent = msg; el.style.display = 'block'; }
  else      { el.style.display = 'none'; }
}

function _actualizarBotonContinuar() {
  const btn  = document.getElementById('btnContinuarApp');
  const hint = document.getElementById('continuarHint');
  // Puede continuar si al menos intentó los dos (ok o err/warn)
  const notifIntenada = _permNotifOk || Notification.permission !== 'default';
  const ubicIntenada  = _permUbicOk  || false;
  const listo = ubicIntenada && notifIntenada;
  if (btn) { btn.disabled = !listo; btn.style.opacity = listo ? '1' : '.35'; }
  if (hint) hint.textContent = listo
    ? (_permUbicOk && _permNotifOk ? '¡Todo listo! Tocá para entrar' : 'Podés continuar aunque un permiso esté limitado')
    : 'Activá los permisos para continuar';
}

async function mostrarPantallaPermisos() {
  const pantalla = document.getElementById('pantallaPermisos');
  if (!pantalla) return;

  // Si ya tiene los dos permisos, arrancar directo sin mostrar la pantalla
  const notifOk = 'Notification' in window && Notification.permission === 'granted';
  let ubicOk = false;
  if (navigator.permissions) {
    const geo = await navigator.permissions.query({ name: 'geolocation' }).catch(() => null);
    ubicOk = geo?.state === 'granted';
  }
  if (notifOk && ubicOk) {
    _permNotifOk = true;
    _permUbicOk  = true;
    iniciarTracking();
    iniciarSistemaActividad();
    return; // no mostrar pantalla
  }

  pantalla.style.display = 'flex';

  // Chequear estado actual de permisos sin pedirlos todavía
  if ('Notification' in window) {
    if (Notification.permission === 'granted') {
      _permNotifOk = true;
      _setBadge('notif', 'ok');
    } else if (Notification.permission === 'denied') {
      _setBadge('notif', 'err');
      _setMsg('notif', '🚨 Bloqueadas en Chrome. Tocá 🔒 en la barra → Permisos → Notificaciones → Permitir');
      document.getElementById('permBtn-notif').textContent = '⚙️ Ir a ajustes del navegador';
      document.getElementById('permBtn-notif').onclick = () => {
        _setMsg('notif', '1) Tocá 🔒 en la barra de URL\n2) Permisos → Notificaciones → Permitir\n3) Recargá la app');
      };
    }
  }

  if (navigator.permissions) {
    const geo = await navigator.permissions.query({ name: 'geolocation' }).catch(() => null);
    if (geo?.state === 'granted') {
      _permUbicOk = true;
      _setBadge('ubicacion', 'ok');
    } else if (geo?.state === 'denied') {
      _setBadge('ubicacion', 'err');
      _setMsg('ubicacion', '🚨 Bloqueada. Tocá 🔒 en la barra → Permisos → Ubicación → Permitir');
    }
  }

  _actualizarBotonContinuar();
}

async function activarUbicacionPerm() {
  _setBadge('ubicacion', 'pending');
  _setMsg('ubicacion', null);
  try {
    await new Promise((res, rej) =>
      navigator.geolocation.getCurrentPosition(res, rej, { timeout: 15000 }));
    _permUbicOk = true;
    _setBadge('ubicacion', 'ok');
  } catch (e) {
    _setBadge('ubicacion', 'err');
    _setMsg('ubicacion', '🚨 Permiso denegado. Tocá 🔒 en la barra → Permisos → Ubicación → Permitir, y recargá.');
  }
  _actualizarBotonContinuar();
}

async function activarNotifPerm() {
  if (!('Notification' in window)) {
    _setBadge('notif', 'err'); _setMsg('notif', 'Este navegador no soporta notificaciones.'); return;
  }
  if (Notification.permission === 'denied') {
    _setMsg('notif', '🚨 Bloqueadas. Tocá 🔒 → Permisos → Notificaciones → Permitir, y recargá.'); return;
  }
  _setBadge('notif', 'pending');
  const perm = await Notification.requestPermission();
  if (perm === 'granted') {
    _permNotifOk = true;
    _setBadge('notif', 'ok');
    await registrarPushRepartidor();
  } else if (perm === 'denied') {
    _setBadge('notif', 'err');
    _setMsg('notif', '🚨 Bloqueadas. Tocá 🔒 en la barra → Permisos → Notificaciones → Permitir, y recargá.');
  }
  _actualizarBotonContinuar();
}

function continuarApp() {
  const pantalla = document.getElementById('pantallaPermisos');
  if (pantalla) pantalla.style.display = 'none';
  if (_permUbicOk) iniciarTracking();
  iniciarSistemaActividad();
}

/* ════════════════════════════════════════
   PUSH NOTIFICATIONS — REPARTIDOR
════════════════════════════════════════ */
const VAPID_PUB_KEY = 'BOHfZtCMwcBtOqLU9HdwNrRfs-A7u434RmpJWg3hAnzJZITA2KefpNGhwbFSfl6MTTDJRdGIVFikdIGF4_CKHbk';

function _vapidToUint8(b64) {
  const pad  = '='.repeat((4 - b64.length % 4) % 4);
  const raw  = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
  return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

let _swReg = null; // referencia global al SW registrado

async function registrarPushRepartidor() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
  if (Notification.permission !== 'granted') return;
  try {
    // Reusar registro existente si ya está activo
    const existing = await navigator.serviceWorker.getRegistration('sw-rep.php').catch(() => null);
    _swReg = existing || await navigator.serviceWorker.register('sw-rep.php');
    await navigator.serviceWorker.ready;

    // Suscribirse al push con VAPID
    const sub = await _swReg.pushManager.subscribe({
      userVisibleOnly:      true,
      applicationServerKey: _vapidToUint8(VAPID_PUB_KEY),
    });

    // Guardar suscripción en el servidor
    const j = sub.toJSON();
    fetch('api/guardar_push_rep.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ endpoint: j.endpoint, p256dh: j.keys.p256dh, auth: j.keys.auth }),
    }).catch(() => {});
  } catch (e) {
    // sin push disponible, el popup in-app igual funciona
  }
}


/* ════════════════════════════════════════
   SISTEMA DE ACTIVIDAD
   30s sin tocar nada → popup vibración → 15s para responder → logout
   Cualquier toque/scroll/cambio de pantalla reinicia los 30s
════════════════════════════════════════ */
const _TEST_ACTIVIDAD = new URLSearchParams(location.search).has('_ta');
if (_TEST_ACTIVIDAD) {
  const b = document.getElementById('bannerTestMode');
  if (b) b.style.display = 'flex';
}
const INACTIVITY_MS   = _TEST_ACTIVIDAD ? 5000  : 2400000; // 5s en test, 40min real
const RESPONSE_MS     = _TEST_ACTIVIDAD ? 8000  : 15000; // 8s en test, 15s real

let _inactivTimer  = null;
let _responseTimer = null;
let _cuentaRaf     = null;
let _cuentaFin     = 0;
let _actSistActivo = false;

function _onUserActivity() {
  if (_actSistActivo) resetActividadTimer();
}

function iniciarSistemaActividad() {
  _actSistActivo = true;
  ['touchstart', 'touchend', 'click', 'scroll', 'keydown'].forEach(ev =>
    document.addEventListener(ev, _onUserActivity, { passive: true }));
  resetActividadTimer();
}

function detenerSistemaActividad() {
  _actSistActivo = false;
  clearTimeout(_inactivTimer);
  clearTimeout(_responseTimer);
  _ocultarModalActividad();
}

function resetActividadTimer() {
  clearTimeout(_inactivTimer);
  clearTimeout(_responseTimer);
  _ocultarModalActividad();
  if (_actSistActivo) {
    _inactivTimer = setTimeout(_dispararCheckActividad, INACTIVITY_MS);
  }
}

function _sonidoActividad() {
  try {
    let plays = 0;
    const audio = new Audio('sounds/akaza_theme.mp3');
    audio.volume = 0.85;
    audio.addEventListener('ended', () => {
      plays++;
      if (plays < 5) {
        audio.currentTime = 0;
        audio.play().catch(() => {});
      }
    });
    audio.play().catch(() => {});
  } catch (_) {}
}

function _dispararCheckActividad() {
  if (!_actSistActivo) return;
  // Sonido tipo Uber (2 tonos)
  _sonidoActividad();
  // Vibrar
  if (navigator.vibrate) navigator.vibrate([300, 100, 300]);
  // Notificación nativa de Android (barra de notificaciones)
  _mostrarNotifNativa();
  // Popup in-app
  document.getElementById('modalActividad').style.display = 'flex';
  _cuentaFin = Date.now() + RESPONSE_MS;
  if (_cuentaRaf) cancelAnimationFrame(_cuentaRaf);
  _tickCuenta();
  _responseTimer = setTimeout(_sesionExpirada, RESPONSE_MS);
}

async function _mostrarNotifNativa() {
  const opts = {
    body:               '⏱ Tocá aquí para confirmar que seguís en el turno. Si no respondés en 15 seg, se cierra la sesión.',
    icon:               '<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png',
    badge:              '<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png',
    vibrate:            [300, 100, 300],
    requireInteraction: true,
    tag:                'rep-actividad',
    renotify:           true,
  };
  try {
    if (_swReg) {
      await _swReg.showNotification('👋 ¿Seguís activo? — Canetto', opts);
    }
  } catch (_) {}
}

function _tickCuenta() {
  const rest = Math.max(0, _cuentaFin - Date.now());
  const pct  = rest / RESPONSE_MS * 100;
  const bar  = document.getElementById('actividadBar');
  const num  = document.getElementById('actividadSecs');
  if (bar) {
    bar.style.width = pct + '%';
    bar.style.background = pct > 50 ? '#c88e99' : pct > 25 ? '#f59e0b' : '#f43f5e';
  }
  if (num) num.textContent = Math.ceil(rest / 1000);
  if (rest > 0) _cuentaRaf = requestAnimationFrame(_tickCuenta);
}

function _ocultarModalActividad() {
  const m = document.getElementById('modalActividad');
  if (m) m.style.display = 'none';
  if (_cuentaRaf) { cancelAnimationFrame(_cuentaRaf); _cuentaRaf = null; }
}

function confirmarActivo() {
  clearTimeout(_responseTimer);
  // Cerrar la notificación de Android si está en la barra
  if (_swReg) {
    _swReg.getNotifications({ tag: 'rep-actividad' })
      .then(notifs => notifs.forEach(n => n.close()))
      .catch(() => {});
  }
  resetActividadTimer();
}

// Escuchar mensajes del SW
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.addEventListener('message', event => {
    if (event.data?.type === 'CONFIRMAR_ACTIVO') confirmarActivo();
    if (event.data?.type === 'PLAY_SOUND')       _sonidoActividad();
  });
}

function _sesionExpirada() {
  _ocultarModalActividad();
  detenerSistemaActividad();
  doLogout();
}
</script>

<!-- ══════════════════════════════════════
     INCOMING ORDER OVERLAY
══════════════════════════════════════ -->
<div id="incomingOverlay">
  <div class="incoming-pulse"></div>
  <div class="incoming-card">
    <div class="incoming-icon-wrap">🛵</div>
    <div class="incoming-headline">¡Nuevo pedido!</div>
    <div class="incoming-sub">Tenés que responder rápido</div>

    <!-- Countdown ring -->
    <div class="incoming-timer-wrap">
      <svg width="72" height="72" viewBox="0 0 72 72">
        <circle id="incomingTimerBg"   cx="36" cy="36" r="30" fill="none" stroke-width="5"/>
        <circle id="incomingTimerRing" cx="36" cy="36" r="30" fill="none" stroke-width="5"
                stroke-linecap="round"
                stroke-dasharray="188.5" stroke-dashoffset="0"/>
      </svg>
      <div class="incoming-secs" id="incomingSecs">30</div>
    </div>

    <!-- Info del pedido -->
    <div class="incoming-order-info" id="incomingOrderInfo">
      <div class="incoming-order-row">
        <div class="incoming-order-icon"><i class="fa-solid fa-hashtag"></i></div>
        <div class="incoming-order-text">
          <div class="incoming-order-label">Pedido</div>
          <span id="incomingNum">—</span>
        </div>
      </div>
      <div class="incoming-order-row">
        <div class="incoming-order-icon"><i class="fa-solid fa-user"></i></div>
        <div class="incoming-order-text">
          <div class="incoming-order-label">Cliente</div>
          <span id="incomingCliente">—</span>
        </div>
      </div>
      <div class="incoming-order-row">
        <div class="incoming-order-icon"><i class="fa-solid fa-location-dot"></i></div>
        <div class="incoming-order-text">
          <div class="incoming-order-label">Dirección</div>
          <span id="incomingDir">—</span>
        </div>
      </div>
      <div class="incoming-order-row">
        <div class="incoming-order-icon"><i class="fa-solid fa-box"></i></div>
        <div class="incoming-order-text">
          <div class="incoming-order-label">Productos</div>
          <span id="incomingProds">—</span>
        </div>
      </div>
      <div class="incoming-order-row">
        <div class="incoming-order-icon"><i class="fa-solid fa-dollar-sign"></i></div>
        <div class="incoming-order-text">
          <div class="incoming-order-label">Total</div>
          <strong id="incomingTotal" style="color:#c88e99;font-size:16px">—</strong>
        </div>
      </div>
    </div>

    <div class="incoming-btns">
      <button class="btn-incoming-rechazar" onclick="responderPedidoPendiente('rechazar')">
        ✕ Rechazar
      </button>
      <button class="btn-incoming-aceptar" onclick="responderPedidoPendiente('aceptar')">
        ✓ Aceptar
      </button>
    </div>
  </div>
</div>

<script>
/* ══ INCOMING ORDER POLLING & RESPONSE ════════════════════ */
let _incomingPollTimer  = null;
let _incomingCountTimer = null;
let _incomingCountRaf   = null;
let _incomingVentaId    = null;
let _incomingEndMs      = 0;
const INCOMING_SECS     = 30; // segundos para responder
const INCOMING_CIRCUM   = 2 * Math.PI * 30; // 188.5

function startIncomingPoll() {
  clearTimeout(_incomingPollTimer);
  _incomingPollTimer = setTimeout(_doPollIncoming, 5000);
}

async function _doPollIncoming() {
  try {
    const res  = await fetch('api/get_pedido_pendiente.php');
    const data = await res.json();
    if (_incomingVentaId) {
      // Overlay visible — si el pedido ya no existe (lo tomó otro), cerrarlo
      if (!data.pendiente || data.pendiente.idventas !== _incomingVentaId) {
        _cerrarIncomingTomado();
      } else {
        startIncomingPoll();
      }
    } else if (data.pendiente) {
      _mostrarIncoming(data.pendiente);
    } else {
      startIncomingPoll();
    }
  } catch (e) {
    startIncomingPoll();
  }
}

function _cerrarIncomingTomado() {
  cancelAnimationFrame(_incomingCountRaf);
  clearTimeout(_incomingPollTimer);
  _incomingVentaId = null;
  document.getElementById('incomingOverlay').classList.remove('visible');
  startIncomingPoll();
}

function _mostrarIncoming(pedido) {
  _incomingVentaId = pedido.idventas;

  // Rellenar info
  document.getElementById('incomingNum').textContent     = '#' + pedido.idventas;
  document.getElementById('incomingCliente').textContent = pedido.cliente_nombre || '—';
  document.getElementById('incomingDir').textContent     = pedido.direccion_entrega || 'Sin dirección';
  document.getElementById('incomingProds').textContent   = pedido.productos || '—';
  document.getElementById('incomingTotal').textContent   = '$' + parseFloat(pedido.total || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  // Mostrar overlay
  const overlay = document.getElementById('incomingOverlay');
  overlay.classList.add('visible');

  // Vibrar si el dispositivo lo soporta
  if (navigator.vibrate) navigator.vibrate([200, 100, 200, 100, 400]);

  // Iniciar countdown
  _incomingEndMs = Date.now() + INCOMING_SECS * 1000;
  _tickIncoming();
}

function _tickIncoming() {
  cancelAnimationFrame(_incomingCountRaf);
  const rest = Math.max(0, _incomingEndMs - Date.now());
  const secs = Math.ceil(rest / 1000);
  const pct  = rest / (INCOMING_SECS * 1000);
  const offset = INCOMING_CIRCUM * (1 - pct);

  const ring = document.getElementById('incomingTimerRing');
  const txt  = document.getElementById('incomingSecs');
  if (ring) {
    ring.style.strokeDashoffset = offset;
    ring.style.stroke = pct > .5 ? '#c88e99' : pct > .25 ? '#f59e0b' : '#f43f5e';
  }
  if (txt) txt.textContent = secs;

  if (rest > 0) {
    _incomingCountRaf = requestAnimationFrame(_tickIncoming);
  } else {
    // Tiempo agotado → auto-rechazar
    responderPedidoPendiente('rechazar');
  }
}

async function responderPedidoPendiente(accion) {
  cancelAnimationFrame(_incomingCountRaf);
  clearTimeout(_incomingPollTimer);

  const ventaId = _incomingVentaId;
  _incomingVentaId = null;

  // Ocultar overlay
  const overlay = document.getElementById('incomingOverlay');
  overlay.classList.remove('visible');

  if (!ventaId) { startIncomingPoll(); return; }

  try {
    await fetch('api/responder_pedido.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id_venta: ventaId, accion }),
    });
  } catch (e) { /* red caída, la propuesta expirará sola */ }

  if (accion === 'aceptar') {
    cargarPedidos();
    if (typeof switchTab === 'function') switchTab('pedidos', document.querySelector('.tab-btn'));
  }

  // Retomar el poll
  startIncomingPoll();
}

// Arrancar el poll cuando el repartidor esté logueado
document.addEventListener('DOMContentLoaded', () => {
  <?php if ($repId): ?>
  startIncomingPoll();
  <?php endif; ?>
});
</script>
</body>
</html>
