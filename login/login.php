<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/google_config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['usuario_id'])) {
    $dest = ($_SESSION['rol'] ?? '') === 'cliente'
        ? URL_TIENDA . '/index.php'
        : URL_ADMIN  . '/index.php';
    redirect($dest);
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canetto | Acceder</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="login.css">
<script src="https://accounts.google.com/gsi/client" async defer></script>
<style>
/* ── panel toggle ── */
.panel{display:none}.panel.on{display:block}
/* ── alert ── */
.lc-alert{padding:9px 13px;border-radius:8px;font-size:13px;margin-bottom:14px;display:none}
.lc-alert.err{background:#f9edf0;color:#c88e99;display:block}
.lc-alert.ok{background:#e8f5e9;color:#1d8348;display:block}
/* ── register btn ── */
.btn-register{width:100%;padding:14px;background:#c88e99;color:#fff;border:none;border-radius:10px;font-weight:600;font-family:'Inter',sans-serif;font-size:14px;cursor:pointer;transition:.25s;margin-top:4px}
.btn-register:hover{background:#a46678;transform:translateY(-1px)}
.btn-register:disabled{background:#ccc;cursor:default;transform:none}
/* ── back link ── */
.lc-back{display:block;text-align:center;font-size:13px;color:#888;margin-top:14px;cursor:pointer;background:none;border:none;font-family:'Inter',sans-serif;width:100%}
.lc-back:hover{color:#111}
</style>
</head>
<body>

<!-- ══ Overlay éxito — pre-creado (fix position:fixed en flex body) ══ -->
<div id="gLoginSuccess" style="
    position:fixed;inset:0;z-index:99999;
    background:linear-gradient(150deg,rgba(255,246,249,0.97) 0%,rgba(255,255,255,0.97) 100%);
    backdrop-filter:blur(28px);-webkit-backdrop-filter:blur(28px);
    display:none;flex-direction:column;align-items:center;justify-content:center;
    opacity:0;transition:opacity .22s ease">
  <div id="gSuccessCard" style="text-align:center;transform:scale(.6) translateY(24px);opacity:0;
      transition:transform .5s cubic-bezier(.34,1.56,.64,1),opacity .3s ease;will-change:transform,opacity">
    <div style="position:relative;width:110px;height:110px;margin:0 auto 26px">
      <svg width="110" height="110" viewBox="0 0 110 110" style="position:absolute;inset:0">
        <circle cx="55" cy="55" r="50" fill="#fdf4f7"/>
        <circle id="gRing" cx="55" cy="55" r="50" fill="none" stroke="#c88e99" stroke-width="2.5"
                stroke-linecap="round" stroke-dasharray="314" stroke-dashoffset="314"
                transform="rotate(-90 55 55)"
                style="transition:stroke-dashoffset .7s cubic-bezier(.16,1,.3,1) .15s"/>
        <polyline id="gCheck" points="30,56 47,73 80,36" fill="none" stroke="#c88e99"
                  stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"
                  stroke-dasharray="76" stroke-dashoffset="76"
                  style="transition:stroke-dashoffset .4s cubic-bezier(.16,1,.3,1) .75s"/>
      </svg>
    </div>
    <div id="gSuccessName" style="font-size:26px;font-weight:800;color:#111;letter-spacing:-.5px;
        margin-bottom:8px;opacity:0;transform:translateY(12px);
        transition:opacity .4s ease .9s,transform .4s cubic-bezier(.16,1,.3,1) .9s"></div>
    <div id="gSuccessSub" style="font-size:14px;color:#bbb;font-weight:500;opacity:0;
        transform:translateY(8px);transition:opacity .35s ease 1.05s,transform .35s ease 1.05s">
      Sesión iniciada correctamente ✓
    </div>
  </div>
  <div id="gSuccessBrand" style="position:absolute;bottom:34px;font-size:11px;letter-spacing:5px;
       text-transform:uppercase;font-weight:700;color:#e0d0d4;
       opacity:0;transition:opacity .5s ease 1.1s">CANETTO</div>
</div>
<!-- ════════════════════════════════════════════════════════════ -->

<div class="login-container">

  <div class="logo">CANETTO</div>
  <div class="subtitle">Panel de Administración</div>

  <!-- ══ PANEL: Iniciar sesión ══ -->
  <div class="panel on" id="panelLogin">

    <?php if ($error): ?>
      <div class="lc-alert err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div id="loginAlertAdmin" style="display:none;background:#f9edf0;color:#c88e99;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px"></div>
    <form id="loginFormAdmin" onsubmit="doLoginNormalAdmin(event)" method="POST">
      <div class="input-group">
        <label>Usuario o celular</label>
        <input type="text" name="usuario" id="aUsuario" required autocomplete="username" placeholder="Tu usuario o número de celular">
      </div>
      <div class="input-group">
        <label>Contraseña</label>
        <input type="password" name="password" id="aPassword" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login" id="btnLoginAdmin">Ingresar</button>
    </form>

    <div style="text-align:center;margin-top:4px;margin-bottom:14px;">
      <a href="recuperar_password.php" style="font-size:14px;font-weight:600;color:#c88e99;text-decoration:none;display:inline-flex;align-items:center;gap:5px">
        <i class="fa-solid fa-lock-open" style="font-size:13px"></i> ¿Olvidaste tu contraseña?
      </a>
    </div>

    <div class="divider"><span>o continuar con</span></div>

    <button class="btn-google" type="button" id="btnGoogleAdmin" onclick="iniciarGoogleAdmin()">
      <span class="g-spinner"></span>
      <img class="g-logo" src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
      <span class="g-label">Ingresar con Google</span>
    </button>
    <div id="googleAlertAdmin" style="display:none;margin-top:10px;padding:9px 13px;border-radius:8px;font-size:13px"></div>
    <!-- Contenedor oculto para el botón renderizado por Google (necesario para el popup completo) -->
    <div id="googleHiddenBtnAdmin" style="position:fixed;bottom:-200px;left:-200px;opacity:0;width:1px;height:1px;overflow:hidden;"></div>

    <div class="register-link">
      ¿No tenés cuenta?
      <a href="#" onclick="showPanel('panelRegister');return false;">Registrate</a>
    </div>

  </div>

  <!-- ══ PANEL: Crear cuenta ══ -->
  <div class="panel" id="panelRegister">

    <div class="lc-alert" id="rAlert"></div>

    <div class="input-group">
      <label>Nombre *</label>
      <input type="text" id="rNom" placeholder="Tu nombre" autocomplete="given-name">
    </div>
    <div class="input-group">
      <label>Apellido</label>
      <input type="text" id="rApe" placeholder="Apellido" autocomplete="family-name">
    </div>
    <div class="input-group">
      <label>Celular * <span style="font-size:11px;color:#aaa">(será tu usuario para iniciar sesión)</span></label>
      <input type="tel" id="rCel" placeholder="Ej: 1123456789" autocomplete="tel">
    </div>
    <div class="input-group">
      <label>Email <span style="font-size:11px;color:#aaa">(para recuperar tu contraseña)</span></label>
      <input type="email" id="rEmail" placeholder="tu@email.com" autocomplete="email">
    </div>
    <div class="input-group">
      <label>Contraseña * <span style="font-size:11px;color:#aaa">(mín. 6 caracteres)</span></label>
      <input type="password" id="rPass" autocomplete="new-password">
    </div>

    <button class="btn-register" id="btnReg" onclick="doRegister()">Crear cuenta</button>

    <button class="lc-back" onclick="showPanel('panelLogin')">← Ya tengo cuenta</button>

  </div>

</div>

<script>
function showPanel(id) {
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('on'));
    document.getElementById(id).classList.add('on');
}

async function doRegister() {
    const n = document.getElementById('rNom').value.trim();
    const a = document.getElementById('rApe').value.trim();
    const c = document.getElementById('rCel').value.trim();
    const p = document.getElementById('rPass').value;
    const alert = document.getElementById('rAlert');

    if (!n || !c || !p) { setAlert(alert, 'Completá los campos obligatorios.', 'err'); return; }
    if (p.length < 6)   { setAlert(alert, 'La contraseña debe tener al menos 6 caracteres.', 'err'); return; }

    const btn = document.getElementById('btnReg');
    btn.disabled = true; btn.textContent = 'Creando cuenta...';

    try {
        const e = document.getElementById('rEmail').value.trim();
        const fd = new FormData();
        fd.append('nombre', n); fd.append('apellido', a);
        fd.append('celular', c); fd.append('email', e); fd.append('password', p);
        const d = await (await fetch('register_process.php', { method: 'POST', body: fd })).json();
        if (d.ok) {
            setAlert(alert, '¡Bienvenido, ' + d.nombre + '! Redirigiendo...', 'ok');
            setTimeout(() => window.location.href = '<?= URL_TIENDA ?>/index.php', 1000);
        } else {
            setAlert(alert, d.msg || 'Error al registrar.', 'err');
            btn.disabled = false; btn.textContent = 'Crear cuenta';
        }
    } catch {
        setAlert(alert, 'Error de conexión. Intentá de nuevo.', 'err');
        btn.disabled = false; btn.textContent = 'Crear cuenta';
    }
}

function setAlert(el, msg, type) {
    el.textContent = msg;
    el.className = 'lc-alert ' + type;
}

// ── Google Sign-In ──────────────────────────────────────────────────────────
const GOOGLE_CLIENT_ID_ADMIN = <?= json_encode(GOOGLE_CLIENT_ID) ?>;

// Ocultar botón de Google si no hay Client ID configurado
if (!GOOGLE_CLIENT_ID_ADMIN) {
    const btn = document.getElementById('btnGoogleAdmin');
    if (btn) btn.style.display = 'none';
}

function _gSetLoading(btn, label) {
    btn.disabled = true;
    btn.classList.add('g-loading');
    btn.classList.remove('g-success');
    btn.querySelector('.g-label').textContent = label;
}

function _gSetSuccess(btn) {
    btn.classList.remove('g-loading');
    btn.classList.add('g-success');
    btn.querySelector('.g-label').textContent = '¡Listo!';
}

function _gReset(btn) {
    btn.disabled = false;
    btn.classList.remove('g-loading', 'g-success');
    btn.querySelector('.g-label').textContent = 'Ingresar con Google';
}

// ── Login normal Admin (con animación) ─────────────────────────────────────
async function doLoginNormalAdmin(e) {
    e.preventDefault();
    const btn   = document.getElementById('btnLoginAdmin');
    const alert = document.getElementById('loginAlertAdmin');
    alert.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Ingresando...';
    try {
        const data = await fetch('login_process.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(document.getElementById('loginFormAdmin')),
        }).then(r => r.json());
        if (data.ok) {
            btn.textContent = '¡Listo!';
            _gShowOverlay(data.nombre);
            setTimeout(() => window.location.href = data.redirect, 1500);
        } else {
            alert.textContent = data.mensaje || 'Datos incorrectos';
            alert.style.display = 'block';
            btn.disabled = false; btn.textContent = 'Ingresar';
        }
    } catch {
        alert.textContent = 'Error de conexión. Intentá de nuevo.';
        alert.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Ingresar';
    }
}

function _gShowOverlay(nombre) {
    const overlay = document.getElementById('gLoginSuccess');
    const card    = document.getElementById('gSuccessCard');
    const ring    = document.getElementById('gRing');
    const check   = document.getElementById('gCheck');
    const nameEl  = document.getElementById('gSuccessName');
    const sub     = document.getElementById('gSuccessSub');
    const brand   = document.getElementById('gSuccessBrand');
    nameEl.textContent = '¡Hola, ' + nombre + '!';
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
        setTimeout(() => { ring.style.transition = 'stroke-dashoffset .7s cubic-bezier(.16,1,.3,1)'; ring.style.strokeDashoffset = '0'; }, 150);
        setTimeout(() => { check.style.transition = 'stroke-dashoffset .4s cubic-bezier(.16,1,.3,1)'; check.style.strokeDashoffset = '0'; }, 750);
        setTimeout(() => { nameEl.style.transition = 'opacity .4s ease, transform .4s cubic-bezier(.16,1,.3,1)'; nameEl.style.opacity = '1'; nameEl.style.transform = 'translateY(0)'; }, 900);
        setTimeout(() => { sub.style.transition = 'opacity .35s ease, transform .35s ease'; sub.style.opacity = '1'; sub.style.transform = 'translateY(0)'; brand.style.transition = 'opacity .5s ease'; brand.style.opacity = '1'; }, 1050);
    }));
}

function iniciarGoogleAdmin() {
    if (!GOOGLE_CLIENT_ID_ADMIN) {
        showGoogleAlertAdmin('Google Sign-In no está configurado en este servidor.', '#888'); return;
    }
    if (!window.google) {
        showGoogleAlertAdmin('Cargando Google... intentá en un segundo.', '#888'); return;
    }

    const btnEl = document.getElementById('btnGoogleAdmin');
    _gSetLoading(btnEl, 'Abriendo Google...');

    google.accounts.id.initialize({
        client_id:            GOOGLE_CLIENT_ID_ADMIN,
        callback:             handleGoogleAdmin,
        ux_mode:              'popup',
        cancel_on_tap_outside: true,
    });

    const container = document.getElementById('googleHiddenBtnAdmin');
    container.innerHTML = '';
    google.accounts.id.renderButton(container, {
        type: 'standard', size: 'large', theme: 'outline', text: 'signin_with',
    });

    let intentos = 0;
    const intentarClick = () => {
        const gBtn = container.querySelector('div[role=button], [jsname], iframe');
        if (gBtn) { gBtn.click(); }
        else if (intentos < 15) { intentos++; setTimeout(intentarClick, 100); }
        else { _gReset(btnEl); showGoogleAlertAdmin('No se pudo abrir Google. Intentá de nuevo.', '#c88e99', '#f9edf0'); }
    };
    setTimeout(intentarClick, 150);
}

async function handleGoogleAdmin(response) {
    const btn = document.getElementById('btnGoogleAdmin');
    _gSetLoading(btn, 'Verificando...');

    try {
        const data = await fetch('google_auth_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ credential: response.credential }),
        }).then(r => r.json());

        if (data.success) {
            _gSetSuccess(btn);
            _gShowOverlay(data.nombre);
            setTimeout(() => window.location.href = data.redirect, 1200);
        } else {
            showGoogleAlertAdmin(data.message || 'Acceso denegado.', '#c88e99', '#f9edf0');
            _gReset(btn);
        }
    } catch {
        showGoogleAlertAdmin('Error de conexión.', '#c88e99', '#f9edf0');
        _gReset(btn);
    }
}

function resetBtnGoogleAdmin() { _gReset(document.getElementById('btnGoogleAdmin')); }

function showGoogleAlertAdmin(msg, color, bg) {
    const el = document.getElementById('googleAlertAdmin');
    el.textContent = msg;
    el.style.color = color || '#333';
    el.style.background = bg || '#f5f5f5';
    el.style.display = 'block';
}

// Pre-inicializar GSI cuando carga la librería (sin mostrar One Tap automático)
window.addEventListener('load', () => {
    if (window.google && GOOGLE_CLIENT_ID_ADMIN) {
        google.accounts.id.initialize({
            client_id:            GOOGLE_CLIENT_ID_ADMIN,
            callback:             handleGoogleAdmin,
            ux_mode:              'popup',
            cancel_on_tap_outside: true,
        });
    }
});
</script>
</body>
</html>
