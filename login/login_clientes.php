<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/google_config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['usuario_id']) && ($_SESSION['rol'] ?? '') === 'cliente') {
    redirect(URL_TIENDA . '/index.php');
}

$error = $_SESSION['error_cliente'] ?? null;
unset($_SESSION['error_cliente']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canetto | Clientes</title>
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
/* ── badge cliente ── */
.cliente-badge{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#f9d6df,#fce8ed);border:none;color:#a0445e;font-size:13px;font-weight:700;letter-spacing:.3px;padding:7px 18px;border-radius:30px;margin-bottom:6px;box-shadow:0 2px 10px rgba(200,142,153,.35)}
</style>
</head>
<body>

<div class="login-container">

  <div class="logo">CANETTO</div>
  <div class="subtitle">Accedé a tu cuenta</div>

  <!-- ══ PANEL: Iniciar sesión ══ -->
  <div class="panel on" id="panelLogin">

    <?php if ($error): ?>
      <div class="lc-alert err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="login_clientes_process.php" method="POST">
      <div class="input-group">
        <label>Celular o usuario</label>
        <input type="text" name="usuario" required autocomplete="username" placeholder="Tu número de celular">
      </div>
      <div class="input-group">
        <label>Contraseña</label>
        <input type="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">Ingresar</button>
    </form>

    <div style="text-align:right;margin-top:-6px;margin-bottom:10px;">
      <a href="recuperar_password.php" style="font-size:12px;color:#c88e99;text-decoration:none;">¿Olvidaste tu contraseña?</a>
    </div>

    <div class="divider"><span>o continuar con</span></div>

    <button class="btn-google" type="button" id="btnGoogleTienda" onclick="iniciarGoogleTienda()">
      <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google">
      Ingresar con Google
    </button>
    <div id="googleAlertTienda" style="display:none;margin-top:10px;padding:9px 13px;border-radius:8px;font-size:13px"></div>
    <!-- Contenedor oculto para el botón renderizado por Google -->
    <div id="googleHiddenBtnTienda" style="position:fixed;bottom:-200px;left:-200px;opacity:0;width:1px;height:1px;overflow:hidden;"></div>

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
const GOOGLE_CLIENT_ID_TIENDA = <?= json_encode(GOOGLE_CLIENT_ID) ?>;

// Ocultar botón de Google si no hay Client ID configurado
if (!GOOGLE_CLIENT_ID_TIENDA) {
    const btn = document.getElementById('btnGoogleTienda');
    if (btn) btn.style.display = 'none';
}

function iniciarGoogleTienda() {
    if (!GOOGLE_CLIENT_ID_TIENDA) {
        showGoogleAlertTienda('Google Sign-In no está configurado en este servidor.', '#888'); return;
    }
    if (!window.google) {
        showGoogleAlertTienda('Cargando Google... intentá en un segundo.', '#888'); return;
    }

    const btnEl = document.getElementById('btnGoogleTienda');
    btnEl.disabled = true;
    btnEl.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="G" style="width:18px;height:18px"> Abriendo Google...';

    google.accounts.id.initialize({
        client_id:            GOOGLE_CLIENT_ID_TIENDA,
        callback:             handleGoogleTienda,
        ux_mode:              'popup',
        cancel_on_tap_outside: true,
    });

    // Renderizar el botón oficial de Google en el contenedor oculto y hacer click
    // Esto abre el popup completo con selector de cuentas de Google
    const container = document.getElementById('googleHiddenBtnTienda');
    container.innerHTML = '';
    google.accounts.id.renderButton(container, {
        type:  'standard',
        size:  'large',
        theme: 'outline',
        text:  'signin_with',
    });

    requestAnimationFrame(() => {
        const gBtn = container.querySelector('div[role=button], [jsname], iframe');
        if (gBtn) {
            gBtn.click();
        } else {
            // Fallback: si renderButton no está disponible, usar prompt
            google.accounts.id.prompt(notification => {
                if (notification.isSkippedMoment() || notification.isDismissedMoment()) {
                    btnEl.disabled = false;
                    btnEl.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="G" style="width:18px;height:18px"> Ingresar con Google';
                }
            });
        }
    });
}

async function handleGoogleTienda(response) {
    const btn = document.getElementById('btnGoogleTienda');
    btn.disabled = true;
    btn.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="G" style="width:18px;height:18px"> Verificando...';

    try {
        const data = await fetch('google_auth_tienda.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ credential: response.credential }),
        }).then(r => r.json());

        if (data.success) {
            showGoogleAlertTienda('¡Bienvenido, ' + data.nombre + '! Redirigiendo...', '#1d8348', '#e8f5e9');
            setTimeout(() => window.location.href = data.redirect, 900);
        } else {
            showGoogleAlertTienda(data.message || 'No se pudo ingresar.', '#c88e99', '#f9edf0');
            btn.disabled = false;
            btn.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="G" style="width:18px;height:18px"> Ingresar con Google';
        }
    } catch {
        showGoogleAlertTienda('Error de conexión.', '#c88e99', '#f9edf0');
        btn.disabled = false;
        btn.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="G" style="width:18px;height:18px"> Ingresar con Google';
    }
}

function resetBtnGoogleTienda() {
    const btn = document.getElementById('btnGoogleTienda');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<img src="https://developers.google.com/identity/images/g-logo.png" alt="G" style="width:18px;height:18px"> Ingresar con Google';
    }
}

function showGoogleAlertTienda(msg, color, bg) {
    const el = document.getElementById('googleAlertTienda');
    el.textContent = msg;
    el.style.color = color || '#333';
    el.style.background = bg || '#f5f5f5';
    el.style.display = 'block';
}

// Pre-inicializar GSI cuando carga la librería (sin One Tap automático)
window.addEventListener('load', () => {
    if (window.google && GOOGLE_CLIENT_ID_TIENDA) {
        google.accounts.id.initialize({
            client_id:            GOOGLE_CLIENT_ID_TIENDA,
            callback:             handleGoogleTienda,
            ux_mode:              'popup',
            cancel_on_tap_outside: true,
        });
    }
});
</script>
</body>
</html>
