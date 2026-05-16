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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="repartidor.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
.btn-google-rep{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:13px;background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;color:#3c4043;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;transition:.2s;margin-top:2px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
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
      registrarPushRepartidor();
      mostrarPantallaPermisos();
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
  detenerSistemaActividad();
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
      actualizarMapa([]);
      return;
    }

    actualizarMapa(data.pedidos);

    const tpl = document.getElementById('tplPedido');
    list.innerHTML = '';

    data.pedidos.forEach((p, i) => {
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
      document.getElementById('dashNombre').textContent = nombre;
      document.getElementById('dashAvatar').textContent = initials(nombre);
      document.getElementById('appLogin').classList.add('hidden');
      document.getElementById('appDash').classList.remove('hidden');
      cargarPedidos();
      startAutoRefresh();
      registrarPushRepartidor();
      mostrarPantallaPermisos();
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
  // Registrar SW siempre al cargar (para que _swReg esté listo antes de necesitarlo)
  registrarPushRepartidor();
  mostrarPantallaPermisos();
}
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

function initUberMap() {
  if (_uberMap) return;
  _uberMap = L.map('uberMapFull', {
    zoomControl:       false,
    attributionControl: false,
  });
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 19,
  }).addTo(_uberMap);
  L.control.zoom({ position: 'bottomright' }).addTo(_uberMap);
}

async function abrirUberMap(pedido, multiPedidos) {
  _uberPedido   = pedido;
  _uberPhase    = 'pickup';
  _uberSteps    = [];
  _uberStepIdx  = 0;
  _uberMulti    = multiPedidos || [];
  _uberMultiIdx = multiPedidos ? multiPedidos.findIndex(p => p.idventas === pedido.idventas) : 0;

  const screen = document.getElementById('uberMapScreen');
  screen.classList.add('active');
  document.body.style.overflow = 'hidden';

  if (_uberCardCollapsed) toggleUberCard();

  // Badge fase retiro
  const badge = document.getElementById('uberStatusBadge');
  badge.className = 'uber-status-badge phase-pickup';
  badge.textContent = 'Yendo a retirar';

  // Mostrar botones de retiro
  document.getElementById('uberActionsPickup').style.display  = '';
  document.getElementById('uberActionsDelivery').style.display = 'none';
  document.getElementById('uberNavBanner').style.display       = 'none';

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
  setTimeout(() => { _uberMap.invalidateSize(); dibujarRuta(pedido); }, 200);

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
  // Partimos desde la sucursal del primer pedido (o la principal)
  let cur = pedidos.length ? getSucLatLng(pedidos[0]) : { lat: CANETTO_LAT, lng: CANETTO_LNG };
  const rem = pedidos.filter(p => p.lat_entrega && p.lng_entrega).map(p => ({...p}));
  const sin = pedidos.filter(p => !p.lat_entrega || !p.lng_entrega);
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

function renderPkgQueue(pedidos, currentIdx) {
  const el = document.getElementById('uberPkgQueue');
  if (!pedidos || pedidos.length <= 1) { el.style.display = 'none'; return; }
  el.style.display = '';
  el.innerHTML = `<div class="uber-pkg-queue">
    <div class="uber-pkg-queue-title"><i class="fa-solid fa-route"></i> Ruta óptima · ${pedidos.length} entregas</div>
    ${pedidos.map((p, i) => {
      const cls = i < currentIdx ? 'pkg-done' : i === currentIdx ? 'pkg-current' : 'pkg-pending';
      const dot = i < currentIdx ? '<i class="fa-solid fa-check" style="font-size:9px"></i>' : (i+1);
      return `<div class="uber-pkg-row ${cls}">
        <div class="uber-pkg-dot">${dot}</div>
        <div style="flex:1;min-width:0">
          <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${p.cliente_nombre||'Cliente'}</div>
          <div style="font-size:10px;opacity:.55">#${p.idventas} · ${p.direccion_entrega||'Sin dir.'}</div>
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
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const motoColor = _uberPhase === 'pickup' ? '#f59e0b' : '#c88e99';
    const iconDriver = iconDiv(`
      <div style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;filter:drop-shadow(0 2px 8px rgba(0,0,0,.7))">
        <i class="fa-solid fa-motorcycle" style="font-size:26px;color:${motoColor}"></i>
      </div>`, 38);
    if (_uberDriver) _uberMap.removeLayer(_uberDriver);
    _uberDriver = L.marker([lat, lng], { icon: iconDriver, zIndexOffset: 1000 })
      .bindPopup('<strong>Tu posición</strong>')
      .addTo(_uberMap);

    updateNavBanner(lat, lng);
  };

  _uberWatcher = navigator.geolocation.watchPosition(updateDriver, () => {}, {
    enableHighAccuracy: true, maximumAge: 5000, timeout: 15000,
  });
}

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

  // watchPosition: dispara automáticamente en cada cambio de posición
  _trackingWatcher = navigator.geolocation.watchPosition(
    onPos,
    () => {},
    { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 }
  );
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
    const existing = await navigator.serviceWorker.getRegistration('sw-rep.js').catch(() => null);
    _swReg = existing || await navigator.serviceWorker.register('sw-rep.js');
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
    const audio = new Audio('sounds/akaza_theme.mp3');
    audio.volume = 0.85;
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
    icon:               '/canetto/assets/img/Logo_Canetto_Cookie.png',
    badge:              '/canetto/assets/img/Logo_Canetto_Cookie.png',
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
</body>
</html>
