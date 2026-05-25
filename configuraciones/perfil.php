<?php
define('APP_BOOT', true);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/conexion.php';

// Cargar datos actuales del usuario
$pdo    = Conexion::conectar();
$stmt   = $pdo->prepare("SELECT nombre, apellido, email, celular, usuario, avatar FROM usuario WHERE idusuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$me     = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['avatar'] = $me['avatar'] ?? null;

$iniciales = strtoupper(
    substr($me['nombre']   ?? '', 0, 1) .
    substr($me['apellido'] ?? '', 0, 1)
) ?: '?';

// El avatar puede ser URL completa (Google) o ruta local
$av = $me['avatar'] ?? null;
$avatarUrl = $av
    ? (str_starts_with($av, 'http') ? $av : URL_ASSETS . '/' . $av . '?v=' . time())
    : null;

$pageTitle = 'Mi perfil — Canetto';
include '../panel/dashboard/layaut/nav.php';
?>

<style>
.perfil-wrap {
  max-width: 720px;
  margin: 2.5rem auto;
  padding: 0 1.5rem 4rem;
}
.perfil-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.perfil-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}
.perfil-avatar {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--brand);
  box-shadow: 0 4px 20px rgba(200,142,153,.3);
}
.perfil-avatar-init {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: linear-gradient(135deg, #c88e99, #e07a8c);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  border: 3px solid var(--brand);
  box-shadow: 0 4px 20px rgba(200,142,153,.3);
  letter-spacing: .02em;
}
.perfil-avatar-btn {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--brand);
  border: 2px solid #fff;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 12px;
  transition: .15s;
  box-shadow: 0 2px 8px rgba(0,0,0,.2);
}
.perfil-avatar-btn:hover { transform: scale(1.1); background: #b07080; }
#avatarInput { display: none; }

.perfil-name { font-size: 1.5rem; font-weight: 800; color: var(--text); }
.perfil-user { font-size: .85rem; color: var(--text-soft, #888); margin-top: .2rem; }

.perfil-card {
  background: var(--bg-card, #fff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 1.5rem;
}
.perfil-card-title {
  font-size: .7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: var(--text-soft, #888);
  margin-bottom: 1.2rem;
  padding-bottom: .7rem;
  border-bottom: 1px solid var(--border, #e5e7eb);
}
.perfil-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
@media (max-width: 520px) { .perfil-grid { grid-template-columns: 1fr; } }

.form-label {
  display: block;
  font-size: .75rem;
  font-weight: 700;
  color: var(--text-soft, #888);
  margin-bottom: .35rem;
  text-transform: uppercase;
  letter-spacing: .05em;
}
.form-input {
  width: 100%;
  padding: .65rem .9rem;
  border: 1.5px solid var(--border, #e5e7eb);
  border-radius: 10px;
  font-size: .9rem;
  font-family: inherit;
  color: var(--text);
  background: var(--bg-page, #f9fafb);
  transition: border-color .15s, box-shadow .15s;
  outline: none;
}
.form-input:focus {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(200,142,153,.15);
  background: var(--bg-card, #fff);
}
.btn-save {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .7rem 1.8rem;
  background: var(--brand, #c88e99);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: .9rem;
  font-weight: 700;
  font-family: inherit;
  cursor: pointer;
  transition: .15s;
}
.btn-save:hover { background: #b07080; transform: translateY(-1px); }
.btn-save:active { transform: none; }

.perfil-msg {
  padding: .6rem 1rem;
  border-radius: 8px;
  font-size: .85rem;
  font-weight: 600;
  margin-top: 1rem;
  display: none;
}
.perfil-msg.ok  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.perfil-msg.err { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

.avatar-progress {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(0,0,0,.5);
  display: none;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.2rem;
}
</style>

<div class="perfil-wrap">

  <div class="perfil-header">
    <div class="perfil-avatar-wrap">
      <?php if ($avatarUrl): ?>
        <img src="<?= htmlspecialchars($avatarUrl) ?>" class="perfil-avatar" id="perfilAvatarImg" alt="Avatar">
      <?php else: ?>
        <div class="perfil-avatar-init" id="perfilAvatarInit"><?= htmlspecialchars($iniciales) ?></div>
      <?php endif; ?>
      <div class="avatar-progress" id="avatarProgress">
        <i class="fa-solid fa-spinner fa-spin"></i>
      </div>
      <label class="perfil-avatar-btn" for="avatarInput" title="Cambiar foto">
        <i class="fa-solid fa-camera"></i>
      </label>
      <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/webp,image/gif">
    </div>
    <div>
      <div class="perfil-name" id="perfilNombreDisplay">
        <?= htmlspecialchars(trim(($me['nombre'] ?? '') . ' ' . ($me['apellido'] ?? ''))) ?>
      </div>
      <div class="perfil-user">@<?= htmlspecialchars($me['usuario'] ?? '') ?></div>
    </div>
  </div>

  <!-- Datos personales -->
  <div class="perfil-card">
    <div class="perfil-card-title"><i class="fa-solid fa-user" style="margin-right:6px"></i>Datos personales</div>
    <div class="perfil-grid">
      <div>
        <label class="form-label">Nombre</label>
        <input class="form-input" id="pfNombre" value="<?= htmlspecialchars($me['nombre'] ?? '') ?>" placeholder="Nombre">
      </div>
      <div>
        <label class="form-label">Apellido</label>
        <input class="form-input" id="pfApellido" value="<?= htmlspecialchars($me['apellido'] ?? '') ?>" placeholder="Apellido">
      </div>
      <div>
        <label class="form-label">Email</label>
        <input class="form-input" id="pfEmail" type="email" value="<?= htmlspecialchars($me['email'] ?? '') ?>" placeholder="tu@email.com">
      </div>
      <div>
        <label class="form-label">Celular</label>
        <input class="form-input" id="pfCelular" value="<?= htmlspecialchars($me['celular'] ?? '') ?>" placeholder="Número de celular">
      </div>
    </div>
    <div style="margin-top:1.2rem">
      <button class="btn-save" onclick="guardarPerfil()">
        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
      </button>
      <div class="perfil-msg" id="msgDatos"></div>
    </div>
  </div>

  <!-- Cambiar contraseña -->
  <div class="perfil-card">
    <div class="perfil-card-title"><i class="fa-solid fa-lock" style="margin-right:6px"></i>Cambiar contraseña</div>
    <div class="perfil-grid">
      <div>
        <label class="form-label">Nueva contraseña</label>
        <input class="form-input" id="pfPass1" type="password" placeholder="Mínimo 6 caracteres">
      </div>
      <div>
        <label class="form-label">Repetir contraseña</label>
        <input class="form-input" id="pfPass2" type="password" placeholder="Repetir contraseña">
      </div>
    </div>
    <div style="margin-top:1.2rem">
      <button class="btn-save" onclick="guardarPassword()">
        <i class="fa-solid fa-key"></i> Cambiar contraseña
      </button>
      <div class="perfil-msg" id="msgPass"></div>
    </div>
  </div>

</div>

<script>
function showMsg(el, txt, tipo) {
  el.textContent = txt;
  el.className = 'perfil-msg ' + tipo;
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 4000);
}

async function guardarPerfil() {
  const nombre   = document.getElementById('pfNombre').value.trim();
  const apellido = document.getElementById('pfApellido').value.trim();
  const email    = document.getElementById('pfEmail').value.trim();
  const celular  = document.getElementById('pfCelular').value.trim();
  const msg      = document.getElementById('msgDatos');

  if (!nombre) { showMsg(msg, 'El nombre es obligatorio', 'err'); return; }

  try {
    const res  = await fetch('ajax/guardar_perfil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre, apellido, email, celular }),
    });
    const data = await res.json();
    if (data.ok) {
      showMsg(msg, 'Datos guardados correctamente', 'ok');
      document.getElementById('perfilNombreDisplay').textContent =
        (data.nombre + ' ' + (data.apellido || '')).trim();
      // Actualizar el nombre en el navbar
      const navName = document.getElementById('navUserName');
      if (navName) navName.textContent = (data.nombre + ' ' + (data.apellido || '')).trim();
    } else {
      showMsg(msg, data.msg || 'Error al guardar', 'err');
    }
  } catch(e) {
    showMsg(msg, 'Error de conexión', 'err');
  }
}

async function guardarPassword() {
  const p1  = document.getElementById('pfPass1').value;
  const p2  = document.getElementById('pfPass2').value;
  const msg = document.getElementById('msgPass');

  if (!p1) { showMsg(msg, 'Ingresá la nueva contraseña', 'err'); return; }
  if (p1.length < 6) { showMsg(msg, 'Mínimo 6 caracteres', 'err'); return; }
  if (p1 !== p2) { showMsg(msg, 'Las contraseñas no coinciden', 'err'); return; }

  const nombre   = document.getElementById('pfNombre').value.trim();
  const apellido = document.getElementById('pfApellido').value.trim();
  const email    = document.getElementById('pfEmail').value.trim();
  const celular  = document.getElementById('pfCelular').value.trim();

  try {
    const res  = await fetch('ajax/guardar_perfil.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre, apellido, email, celular, password: p1 }),
    });
    const data = await res.json();
    if (data.ok) {
      showMsg(msg, 'Contraseña actualizada', 'ok');
      document.getElementById('pfPass1').value = '';
      document.getElementById('pfPass2').value = '';
    } else {
      showMsg(msg, data.msg || 'Error', 'err');
    }
  } catch(e) {
    showMsg(msg, 'Error de conexión', 'err');
  }
}

// Upload avatar
document.getElementById('avatarInput').addEventListener('change', async function() {
  const file = this.files[0];
  if (!file) return;

  const progress = document.getElementById('avatarProgress');
  progress.style.display = 'flex';

  const fd = new FormData();
  fd.append('avatar', file);

  try {
    const res  = await fetch('ajax/subir_avatar.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      // Reemplazar avatar en la página
      const wrap = document.querySelector('.perfil-avatar-wrap');
      const init = document.getElementById('perfilAvatarInit');
      let   img  = document.getElementById('perfilAvatarImg');

      if (!img) {
        img = document.createElement('img');
        img.id        = 'perfilAvatarImg';
        img.className = 'perfil-avatar';
        img.alt       = 'Avatar';
        if (init) init.replaceWith(img);
        else wrap.insertBefore(img, wrap.firstChild);
      }
      img.src = data.avatar_url;

      // Actualizar avatar en el navbar
      const navAvatar = document.getElementById('navAvatar');
      if (navAvatar) {
        navAvatar.innerHTML = `<img src="${data.avatar_url}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid var(--brand)" alt="Avatar">`;
      }
    } else {
      alert(data.msg || 'Error al subir la imagen');
    }
  } catch(e) {
    alert('Error de conexión');
  } finally {
    progress.style.display = 'none';
    this.value = '';
  }
});
</script>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
