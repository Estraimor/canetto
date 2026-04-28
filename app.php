<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Canetto — Cookies hechas con amor 🍪</title>
<meta name="description" content="Pedí tus cookies favoritas de Canetto. Envíos y retiro en sucursal.">

<!-- Open Graph para compartir en redes -->
<meta property="og:title"       content="Canetto — Cookies hechas con amor">
<meta property="og:description" content="Pedí online, seguí tu pedido y recibí en tu puerta.">
<meta property="og:image"       content="<?php
  define('APP_BOOT', true);
  require_once __DIR__ . '/config/conexion.php';
  echo URL_ASSETS;
?>/img/Logo_Canetto_Cookie.png">
<meta property="og:type" content="website">

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  min-height: 100dvh;
  background: linear-gradient(160deg, #1a0a0e 0%, #2d1018 40%, #1a0a0e 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 20px;
  position: relative;
  overflow: hidden;
}

/* Fondo decorativo */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background:
    radial-gradient(ellipse 80% 60% at 50% 0%, rgba(200,142,153,.25) 0%, transparent 60%),
    radial-gradient(ellipse 60% 40% at 80% 80%, rgba(200,142,153,.12) 0%, transparent 60%);
  pointer-events: none;
}

/* Burbujas decorativas */
.bubble {
  position: fixed;
  border-radius: 50%;
  background: rgba(200,142,153,.06);
  pointer-events: none;
}
.b1 { width: 300px; height: 300px; top: -80px; right: -60px; }
.b2 { width: 200px; height: 200px; bottom: 40px; left: -60px; }
.b3 { width: 120px; height: 120px; top: 50%; right: 10px; }

.card {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0;
}

/* Logo */
.logo-wrap {
  margin-bottom: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.logo-img {
  width: 80px;
  height: 80px;
  border-radius: 22px;
  background: rgba(255,255,255,.1);
  border: 2px solid rgba(255,255,255,.15);
  padding: 10px;
  backdrop-filter: blur(10px);
}
.brand-name {
  font-size: 28px;
  font-weight: 800;
  color: #fff;
  letter-spacing: -.5px;
}
.brand-tag {
  font-size: 14px;
  color: rgba(255,255,255,.5);
  margin-top: -6px;
}

/* Panel de botones */
.actions {
  width: 100%;
  background: rgba(255,255,255,.06);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 24px;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Botón principal */
.btn-main {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  border-radius: 16px;
  text-decoration: none;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  width: 100%;
  transition: transform .15s, box-shadow .15s, opacity .15s;
  font-family: inherit;
}
.btn-main:active { transform: scale(.97); }

.btn-web {
  background: #c88e99;
  color: #fff;
  box-shadow: 0 4px 24px rgba(200,142,153,.4);
}
.btn-web:hover { box-shadow: 0 6px 32px rgba(200,142,153,.55); transform: translateY(-1px); }

.btn-app {
  background: rgba(255,255,255,.1);
  color: rgba(255,255,255,.9);
  border: 1px solid rgba(255,255,255,.15);
}
.btn-app:hover { background: rgba(255,255,255,.15); }
.btn-app.disabled { opacity: .5; cursor: default; }

.btn-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}
.btn-web .btn-icon  { background: rgba(255,255,255,.25); }
.btn-app .btn-icon  { background: rgba(255,255,255,.12); }

.btn-text-wrap { display: flex; flex-direction: column; gap: 2px; text-align: left; }
.btn-subtitle   { font-size: 12px; font-weight: 500; opacity: .7; }

/* Divider */
.divider {
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgba(255,255,255,.2);
  font-size: 12px;
  font-weight: 600;
}
.divider::before, .divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: rgba(255,255,255,.12);
}

/* Badges de tiendas */
.store-row {
  display: flex;
  gap: 10px;
}
.store-badge {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 12px;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.1);
  color: rgba(255,255,255,.45);
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
  cursor: default;
  position: relative;
  overflow: hidden;
}
.store-badge::after {
  content: 'Próximamente';
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(20,10,12,.7);
  font-size: 10px;
  font-weight: 800;
  color: rgba(255,255,255,.4);
  opacity: 0;
  transition: opacity .15s;
}
.store-badge:hover::after { opacity: 1; }
.store-badge i { font-size: 18px; }

/* Alerta de app en progreso */
.app-notice {
  width: 100%;
  margin-top: 16px;
  padding: 12px 16px;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 14px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 12px;
  color: rgba(255,255,255,.4);
  line-height: 1.5;
}
.app-notice i { color: #c88e99; margin-top: 1px; flex-shrink: 0; }

/* Toast de redirección */
.redirect-toast {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%) translateY(-80px);
  background: #fff;
  color: #111;
  padding: 12px 20px;
  border-radius: 30px;
  font-size: 13px;
  font-weight: 700;
  box-shadow: 0 8px 32px rgba(0,0,0,.25);
  transition: transform .35s cubic-bezier(.34,1.56,.64,1);
  z-index: 100;
  white-space: nowrap;
}
.redirect-toast.show { transform: translateX(-50%) translateY(0); }

/* Footer */
.app-footer {
  margin-top: 28px;
  text-align: center;
  font-size: 11px;
  color: rgba(255,255,255,.2);
  line-height: 1.6;
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="bubble b1"></div>
<div class="bubble b2"></div>
<div class="bubble b3"></div>

<div id="redirectToast" class="redirect-toast">🍪 Abriendo Canetto...</div>

<div class="card">

  <!-- Logo y nombre -->
  <div class="logo-wrap">
    <img class="logo-img" src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto">
    <div class="brand-name">Canetto</div>
    <div class="brand-tag">Cookies hechas con amor</div>
  </div>

  <!-- Acciones -->
  <div class="actions">

    <!-- Ir a la tienda web (principal) -->
    <a href="<?= URL_ASSETS ?>/tienda/" class="btn-main btn-web" id="btnWeb">
      <div class="btn-icon">🛒</div>
      <div class="btn-text-wrap">
        <span>Ir a la tienda web</span>
        <span class="btn-subtitle">Pedí online desde el navegador</span>
      </div>
    </a>

    <div class="divider">o</div>

    <!-- Abrir app (deshabilitado hasta tener la app) -->
    <button class="btn-main btn-app disabled" id="btnApp" onclick="intentarAbrirApp()">
      <div class="btn-icon">📱</div>
      <div class="btn-text-wrap">
        <span>Abrir la aplicación</span>
        <span class="btn-subtitle">Próximamente disponible</span>
      </div>
    </button>

    <!-- Badges de tiendas -->
    <div class="store-row">
      <span class="store-badge">
        <i class="fa-brands fa-apple"></i>
        <span>App Store</span>
      </span>
      <span class="store-badge">
        <i class="fa-brands fa-google-play"></i>
        <span>Google Play</span>
      </span>
    </div>

  </div>

  <!-- Aviso app en desarrollo -->
  <div class="app-notice">
    <i class="fa-solid fa-circle-info"></i>
    <span>La app de Canetto está en desarrollo. Por ahora usá la <strong style="color:rgba(255,255,255,.6)">tienda web</strong> para hacer tus pedidos. ¡Pronto te avisamos!</span>
  </div>

  <div class="app-footer">
    Canetto © <?= date('Y') ?><br>
    Posadas, Misiones
  </div>

</div>

<script>
const APP_SCHEME = 'canetto://';
const WEB_URL    = '<?= URL_ASSETS ?>/tienda/';

// Cuando la app esté disponible, cambiar APP_ACTIVE a true
const APP_ACTIVE = false;

function intentarAbrirApp() {
  if (!APP_ACTIVE) return; // Sin app todavía

  const toast = document.getElementById('redirectToast');
  toast.classList.add('show');

  const ua        = navigator.userAgent;
  const isIOS     = /iphone|ipad|ipod/i.test(ua);
  const isAndroid = /android/i.test(ua);

  if (!isIOS && !isAndroid) {
    // Desktop: ir directo a la web
    setTimeout(() => { window.location.href = WEB_URL; }, 1200);
    return;
  }

  // Intentar abrir la app por deep link
  const start = Date.now();
  window.location.href = APP_SCHEME + 'open';

  // Si la app no está instalada, el usuario queda en la página
  // → redirigir a la tienda de apps
  setTimeout(() => {
    if (Date.now() - start < 2500) {
      toast.innerHTML = '📦 Descargá la app...';
      if (isIOS) {
        window.location.href = 'https://apps.apple.com/ar/app/canetto'; // URL a definir
      } else {
        window.location.href = 'https://play.google.com/store/apps/details?id=ar.canetto'; // URL a definir
      }
    }
  }, 1800);
}

// Detección automática: si el usuario viene de un deep link externo (shared URL)
// y tiene la app, intentar abrirla
(function() {
  if (!APP_ACTIVE) return;
  const ua = navigator.userAgent;
  if (/iphone|ipad|ipod|android/i.test(ua)) {
    const params = new URLSearchParams(window.location.search);
    if (params.get('open') === 'app') {
      intentarAbrirApp();
    }
  }
})();
</script>
</body>
</html>
