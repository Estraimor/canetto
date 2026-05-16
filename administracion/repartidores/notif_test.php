<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/tron.php';
$pageTitle = 'Notificaciones Repartidores — Canetto';
include '../../panel/dashboard/layaut/nav.php';

// Cargar repartidores con estado de suscripción
try {
    $pdo  = Conexion::conectar();
    $reps = $pdo->query("
        SELECT u.idusuario, u.nombre, u.apellido, u.celular,
               u.ubicacion_at,
               (SELECT COUNT(*) FROM push_subscriptions ps
                WHERE ps.usuario_id = u.idusuario AND ps.activo = 1) AS tiene_push
        FROM usuario u
        INNER JOIN usuarios_roles ur ON ur.usuario_idusuario = u.idusuario
        INNER JOIN roles r ON r.idroles = ur.roles_idroles
        WHERE r.nombre = 'Repartidor' AND u.activo = 1
        ORDER BY u.nombre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { $reps = []; }
?>
<style>
.notif-wrap { max-width: 820px; margin: 0 auto; padding: 28px 20px; }
.notif-header { margin-bottom: 28px; }
.notif-header h1 { font-size: 22px; font-weight: 800; color: #111; margin-bottom: 4px; }
.notif-header p  { font-size: 13px; color: #888; }

.notif-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 28px; }
@media(max-width:600px){ .notif-grid{ grid-template-columns:1fr; } }

.rep-card {
  background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px;
  padding: 16px 18px; transition: border-color .2s;
}
.rep-card:hover { border-color: #c88e99; }
.rep-card-top { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.rep-avatar { width: 42px; height: 42px; border-radius: 50%; background: #f9edf0;
  color: #c88e99; font-weight: 800; font-size: 15px;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.rep-nombre { font-size: 14px; font-weight: 700; color: #111; }
.rep-cel    { font-size: 12px; color: #888; margin-top: 2px; }
.rep-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px;
  border-radius: 100px; font-size: 11px; font-weight: 700; }
.badge-green { background: #e8f5e9; color: #1d8348; }
.badge-red   { background: #fef2f2; color: #dc2626; }
.badge-gray  { background: #f3f4f6; color: #6b7280; }

.notif-msg-wrap { display: flex; flex-direction: column; gap: 6px; }
.notif-select {
  width: 100%; padding: 8px 10px; border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-family: inherit; font-size: 13px; background: #f9fafb; color: #111;
}
.btn-enviar {
  width: 100%; padding: 10px; background: #c88e99; color: #fff; border: none;
  border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer;
  font-family: inherit; transition: background .2s; display: flex;
  align-items: center; justify-content: center; gap: 6px;
}
.btn-enviar:hover { background: #a46678; }
.btn-enviar:disabled { background: #ccc; cursor: not-allowed; }
.rep-resultado { font-size: 12px; margin-top: 6px; padding: 6px 10px; border-radius: 7px;
  display: none; }
.rep-resultado.ok  { background: #e8f5e9; color: #1d8348; }
.rep-resultado.err { background: #fef2f2; color: #dc2626; }

.enviar-todos-wrap {
  background: #fff; border: 1.5px solid #e5e7eb; border-radius: 14px;
  padding: 20px; margin-bottom: 20px;
}
.enviar-todos-title { font-size: 14px; font-weight: 800; color: #111; margin-bottom: 14px; }
.btn-todos {
  padding: 11px 22px; background: #111; color: #fff; border: none;
  border-radius: 9px; font-size: 13px; font-weight: 700; cursor: pointer;
  font-family: inherit; transition: background .2s;
}
.btn-todos:hover { background: #333; }

.info-box { background: #f9edf0; border: 1px solid #f0cdd6; border-radius: 10px;
  padding: 12px 14px; font-size: 12px; color: #7a3d4d; line-height: 1.6; margin-bottom: 20px; }
</style>

<div class="notif-wrap">

  <div class="notif-header">
    <h1>🔔 Notificaciones a Repartidores</h1>
    <p>Enviá notificaciones push desde acá directamente al celular de cada repartidor</p>
  </div>

  <div class="info-box">
    💡 <strong>Cómo funciona:</strong> Al tocar "Enviar", el celular del repartidor recibe una notificación en la barra de Android aunque la pantalla esté apagada — siempre que hayan aceptado los permisos en la app.
    El badge <strong>🔔 Con push</strong> indica que tiene las notificaciones activadas.
  </div>

  <!-- Enviar a todos -->
  <div class="enviar-todos-wrap">
    <div class="enviar-todos-title">📢 Enviar a todos los repartidores activos</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      <select id="msgTodos" class="notif-select" style="flex:1;min-width:200px">
        <option value="test">🧪 Prueba de notificación</option>
        <option value="turno">🛵 Iniciá tu turno</option>
        <option value="activo">👋 ¿Seguís activo?</option>
        <option value="urgente">🚨 Llamanos urgente</option>
      </select>
      <button class="btn-todos" onclick="enviarATodos()">📢 Enviar a todos</button>
    </div>
    <div id="resultTodos" class="rep-resultado"></div>
  </div>

  <!-- Grilla de repartidores -->
  <div class="notif-grid">
    <?php foreach ($reps as $r):
      $ini   = strtoupper(substr($r['nombre'], 0, 1) . substr($r['apellido'] ?? '', 0, 1));
      $tieneP = (int)$r['tiene_push'] > 0;
      $online = $r['ubicacion_at'] && strtotime($r['ubicacion_at']) > time() - 600;
    ?>
    <div class="rep-card" id="card-<?= $r['idusuario'] ?>">
      <div class="rep-card-top">
        <div class="rep-avatar"><?= htmlspecialchars($ini) ?></div>
        <div>
          <div class="rep-nombre"><?= htmlspecialchars($r['nombre'] . ' ' . ($r['apellido'] ?? '')) ?></div>
          <div class="rep-cel"><?= htmlspecialchars($r['celular'] ?? '—') ?></div>
        </div>
      </div>
      <div class="rep-badges">
        <?php if ($tieneP): ?>
          <span class="badge badge-green">🔔 Con push</span>
        <?php else: ?>
          <span class="badge badge-red">🔕 Sin push</span>
        <?php endif; ?>
        <?php if ($online): ?>
          <span class="badge badge-green">🟢 Online</span>
        <?php else: ?>
          <span class="badge badge-gray">⚫ Offline</span>
        <?php endif; ?>
      </div>
      <div class="notif-msg-wrap">
        <select class="notif-select" id="msg-<?= $r['idusuario'] ?>">
          <option value="test">🧪 Prueba de notificación</option>
          <option value="turno">🛵 Iniciá tu turno</option>
          <option value="activo">👋 ¿Seguís activo?</option>
          <option value="urgente">🚨 Llamanos urgente</option>
        </select>
        <button class="btn-enviar" onclick="enviar(<?= $r['idusuario'] ?>)"
          <?= $tieneP ? '' : 'disabled title="Sin push activado"' ?>>
          <i class="fa-solid fa-paper-plane"></i>
          <?= $tieneP ? 'Enviar al celular' : 'Sin push activado' ?>
        </button>
        <div class="rep-resultado" id="res-<?= $r['idusuario'] ?>"></div>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($reps)): ?>
      <div style="grid-column:1/-1;text-align:center;color:#888;padding:40px">
        No hay repartidores activos registrados.
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
const MENSAJES = {
  test:    { titulo: '🧪 Prueba Canetto',       cuerpo: 'Las notificaciones están funcionando correctamente.' },
  turno:   { titulo: '🛵 Es tu turno',          cuerpo: 'Canetto: iniciá sesión en la app y comenzá tu turno.' },
  activo:  { titulo: '👋 ¿Seguís activo?',      cuerpo: 'Confirmá que seguís en el turno tocando aquí.' },
  urgente: { titulo: '🚨 Contacto urgente',      cuerpo: 'Comunicate con la administración de Canetto cuanto antes.' },
};

async function enviar(repId) {
  const sel    = document.getElementById('msg-' + repId);
  const res    = document.getElementById('res-' + repId);
  const btn    = sel.nextElementSibling;
  const tipo   = sel.value;
  const { titulo, cuerpo } = MENSAJES[tipo];

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';
  res.style.display = 'none';

  try {
    const data = await fetch('api/enviar_notif_rep.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ repartidor_id: repId, titulo, cuerpo }),
    }).then(r => r.json());

    res.style.display = 'block';
    if (data.ok) {
      res.className   = 'rep-resultado ok';
      res.textContent = '✅ Notificación enviada al celular';
    } else {
      res.className   = 'rep-resultado err';
      res.textContent = '❌ ' + (data.msg || 'No se pudo enviar');
    }
  } catch (e) {
    res.style.display = 'block';
    res.className   = 'rep-resultado err';
    res.textContent = '❌ Error de conexión';
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar al celular';
}

async function enviarATodos() {
  const tipo   = document.getElementById('msgTodos').value;
  const { titulo, cuerpo } = MENSAJES[tipo];
  const res    = document.getElementById('resultTodos');

  // Enviar a cada repartidor con push
  const ids = <?= json_encode(array_values(array_map(fn($r) => (int)$r['idusuario'], array_filter($reps, fn($r) => $r['tiene_push'] > 0)))) ?>;

  if (!ids.length) {
    res.style.display = 'block';
    res.className   = 'rep-resultado err';
    res.textContent = '❌ Ningún repartidor tiene push activado';
    return;
  }

  res.style.display = 'block';
  res.className   = 'rep-resultado';
  res.style.background = '#f3f4f6';
  res.textContent = '⏳ Enviando a ' + ids.length + ' repartidor(es)...';

  let ok = 0, err = 0;
  await Promise.all(ids.map(async id => {
    try {
      const d = await fetch('api/enviar_notif_rep.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ repartidor_id: id, titulo, cuerpo }),
      }).then(r => r.json());
      d.ok ? ok++ : err++;
    } catch { err++; }
  }));

  res.className   = ok > 0 ? 'rep-resultado ok' : 'rep-resultado err';
  res.textContent = ok > 0
    ? `✅ Enviado a ${ok} repartidor(es)` + (err ? ` · ${err} fallaron` : '')
    : `❌ Falló el envío a todos (${err})`;
}
</script>
<?php include '../../panel/dashboard/layaut/footer.php'; ?>
