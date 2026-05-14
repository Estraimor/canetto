<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/tron.php';
$pageTitle = 'Mapa de repartidores — Canetto';
include '../../panel/dashboard/layaut/nav.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.rep-wrap {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 0;
  height: calc(100vh - 60px);
  overflow: hidden;
}

/* ── Panel lateral ── */
.rep-sidebar {
  background: #fff;
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.rep-sidebar-hd {
  padding: 20px 20px 14px;
  border-bottom: 1px solid #f0f0f0;
}
.rep-sidebar-title {
  font-size: 15px;
  font-weight: 800;
  color: #111;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}
.rep-sidebar-title i { color: #c88e99; }
.rep-sidebar-sub { font-size: 12px; color: #888; }

.rep-list { flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 8px; }

.rep-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 12px;
  border: 1.5px solid #e5e7eb;
  background: #fafafa;
  cursor: pointer;
  transition: .15s;
}
.rep-item:hover           { border-color: #c88e99; background: #fdf0f3; }
.rep-item.online          { border-color: #86efac; background: #f0fdf4; }
.rep-item.online:hover    { border-color: #4ade80; }

.rep-avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: #f3d4da; color: #9b3a52;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; font-weight: 800; flex-shrink: 0;
}
.rep-item.online .rep-avatar { background: #c88e99; color: #fff; }

.rep-info { flex: 1; min-width: 0; }
.rep-name  { font-size: 13px; font-weight: 700; color: #111; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rep-status { font-size: 11px; color: #999; display: flex; align-items: center; gap: 4px; margin-top: 2px; }
.rep-dot    { width: 7px; height: 7px; border-radius: 50%; background: #d1d5db; flex-shrink: 0; }
.rep-item.online .rep-dot    { background: #22c55e; }
.rep-item.online .rep-status { color: #16a34a; }

.rep-go-btn {
  background: #c88e99; color: #fff; border: none; border-radius: 8px;
  padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer;
  font-family: inherit; display: none;
}
.rep-item.online .rep-go-btn { display: block; }

/* ── Info actualización ── */
.rep-footer {
  padding: 12px 16px;
  border-top: 1px solid #f0f0f0;
  font-size: 11px;
  color: #aaa;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.rep-refresh-btn {
  background: none; border: 1px solid #e5e7eb; border-radius: 8px;
  padding: 4px 10px; font-size: 11px; color: #666; cursor: pointer;
  font-family: inherit; transition: .15s;
}
.rep-refresh-btn:hover { border-color: #c88e99; color: #c88e99; }

/* ── Sucursales section ── */
.suc-section {
  padding: 10px 12px;
  border-bottom: 1px solid #f0f0f0;
}
.suc-section-title {
  font-size: 10px; font-weight: 800; text-transform: uppercase;
  letter-spacing: .06em; color: #aaa;
  display: flex; align-items: center; gap: 5px; margin-bottom: 7px;
}
#sucSelect {
  width: 100%; padding: 7px 10px;
  border: 1.5px solid #e5e7eb; border-radius: 8px;
  font-size: 12px; font-weight: 600; color: #333;
  background: #fff; cursor: pointer;
  font-family: inherit;
}
#sucSelect:focus { outline: none; border-color: #c88e99; }
#sucSelect option.opt-inactiva { color: #9ca3af; }

/* ── Mapa ── */
#repAdminMap { width: 100%; height: 100%; }

/* ── Popup del mapa ── */
.rep-popup { font-family: system-ui, sans-serif; min-width: 160px; }
.rep-popup-name { font-size: 14px; font-weight: 800; color: #111; margin-bottom: 4px; }
.rep-popup-time { font-size: 11px; color: #888; }

/* ── Badge counter ── */
.rep-counter {
  background: #c88e99; color: #fff;
  border-radius: 20px; padding: 2px 9px;
  font-size: 11px; font-weight: 800;
  margin-left: auto;
}

@media (max-width: 768px) {
  .rep-wrap { grid-template-columns: 1fr; grid-template-rows: 240px 1fr; }
  .rep-sidebar { border-right: none; border-bottom: 1px solid #e5e7eb; }
  .rep-list { flex-direction: row; overflow-x: auto; overflow-y: hidden; padding: 8px 12px; }
  .rep-item { flex-shrink: 0; width: 200px; }
}
</style>

<div class="rep-wrap">

  <!-- Panel lateral -->
  <div class="rep-sidebar">
    <div class="rep-sidebar-hd">
      <div class="rep-sidebar-title">
        <i class="fa-solid fa-motorcycle"></i>
        Repartidores
        <span class="rep-counter" id="repCounter">0</span>
      </div>
      <div class="rep-sidebar-sub" id="repSubtitle">Cargando...</div>
    </div>

    <!-- Sucursales -->
    <div class="suc-section">
      <div class="suc-section-title">
        <i class="fa-solid fa-store" style="font-size:9px"></i>
        Sucursales
      </div>
      <select id="sucSelect" onchange="irASucursalSelect(this.value)">
        <option value="">— Ir a una sucursal —</option>
      </select>
    </div>

    <div class="rep-list" id="repList">
      <div style="padding:24px;text-align:center;color:#ccc;font-size:13px">
        <i class="fa-solid fa-spinner fa-spin" style="font-size:24px;margin-bottom:8px;display:block"></i>
        Cargando repartidores...
      </div>
    </div>
    <div class="rep-footer">
      <span id="repLastUpdate">—</span>
      <button class="rep-refresh-btn" onclick="cargarUbicaciones()">
        <i class="fa-solid fa-arrows-rotate"></i> Actualizar
      </button>
    </div>
  </div>

  <!-- Mapa -->
  <div id="repAdminMap"></div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const initials = n => (n||'?').trim().split(/\s+/).map(w=>w[0]).join('').substring(0,2).toUpperCase();

const map = L.map('repAdminMap', { zoomControl: true, attributionControl: false });
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
map.setView([-34.6037, -58.3816], 13);

// Sucursales
let _sucursales = {};

function iconTiendaColor(activa) {
  return L.divIcon({
    className: '',
    html: `<div style="background:${activa ? '#f59e0b' : '#9ca3af'};width:36px;height:36px;border-radius:50%;
             display:flex;align-items:center;justify-content:center;
             border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,.3);font-size:17px">🏪</div>`,
    iconSize: [36,36], iconAnchor: [18,18],
  });
}

async function cargarSucursales() {
  try {
    const data = await fetch('api/listar_sucursales.php').then(r => r.json());
    const sel = document.getElementById('sucSelect');
    const conCoords = data.filter(s => s.latitud && s.longitud);

    sel.innerHTML = '<option value="">— Ir a una sucursal —</option>' +
      data.map(s => {
        const inactiva = !parseInt(s.activo);
        const sinCoords = !s.latitud || !s.longitud;
        const label = s.nombre + (inactiva ? ' (inactiva)' : '') + (sinCoords ? ' — sin coords' : '');
        return `<option value="${s.idsucursal}" class="${inactiva ? 'opt-inactiva' : ''}"
                  style="${inactiva ? 'color:#9ca3af' : ''}"
                  ${sinCoords ? 'disabled' : ''}>${label}</option>`;
      }).join('');

    conCoords.forEach(s => {
      const lat = parseFloat(s.latitud);
      const lng = parseFloat(s.longitud);
      const activa = parseInt(s.activo);
      const marker = L.marker([lat, lng], { icon: iconTiendaColor(activa) })
        .bindPopup(`<strong>${s.nombre}</strong>` +
          (s.direccion ? `<br><span style="font-size:11px;color:#888">${s.direccion}</span>` : '') +
          (!activa ? `<br><span style="font-size:11px;color:#9ca3af;font-weight:700">Inactiva</span>` : ''))
        .addTo(map);
      _sucursales[s.idsucursal] = { marker, lat, lng };
    });

    // Centrar mapa en la primera sucursal activa con coords
    const primera = conCoords.find(s => parseInt(s.activo));
    if (primera) map.setView([parseFloat(primera.latitud), parseFloat(primera.longitud)], 13);

  } catch(e) {
    console.error('Error cargando sucursales', e);
  }
}

function irASucursalSelect(id) {
  if (!id) return;
  const s = _sucursales[parseInt(id)];
  if (!s) return;
  map.setView([s.lat, s.lng], 16);
  s.marker.openPopup();
}

// Icono repartidor
function iconRep() {
  return L.divIcon({
    className: '',
    html: `<div style="display:flex;align-items:center;justify-content:center;
             width:36px;height:36px;filter:drop-shadow(0 2px 6px rgba(0,0,0,.4))">
             <i class="fa-solid fa-motorcycle" style="font-size:24px;color:#c88e99"></i>
           </div>`,
    iconSize: [36,36], iconAnchor: [18,18], popupAnchor: [0,-18],
  });
}

let _markers = {};

function fmtAgo(dateStr) {
  if (!dateStr) return 'Sin datos';
  const diff = Math.floor((Date.now() - new Date(dateStr.replace(' ','T')).getTime()) / 1000);
  if (diff < 60)  return 'hace ' + diff + 's';
  if (diff < 3600) return 'hace ' + Math.floor(diff/60) + 'min';
  return 'hace ' + Math.floor(diff/3600) + 'h';
}

// ── Animación suave de marcador (estilo Uber) ────────────────────────────────
function animarMarcador(marker, toLat, toLng, ms = 1500) {
  const desde = marker.getLatLng();
  if (desde.lat === toLat && desde.lng === toLng) return;
  const inicio = performance.now();
  function step(now) {
    const t = Math.min(1, (now - inicio) / ms);
    const ease = t < 0.5 ? 2*t*t : -1+(4-2*t)*t; // ease-in-out
    marker.setLatLng([
      desde.lat + (toLat - desde.lat) * ease,
      desde.lng + (toLng - desde.lng) * ease,
    ]);
    if (t < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

// ── Procesar datos de ubicación (actualiza mapa + lista lateral) ─────────────
function procesarUbicaciones(data) {
  if (!data.ok) return;
  const activos = data.activos;
  const todos   = data.todos;

  document.getElementById('repCounter').textContent  = activos.length;
  document.getElementById('repSubtitle').textContent =
    activos.length === 0
      ? 'Ningún repartidor activo ahora'
      : activos.length + ' en línea · ' + (todos.length - activos.length) + ' sin ubicación';
  document.getElementById('repLastUpdate').textContent =
    'Actualizado ' + new Date().toLocaleTimeString('es-AR', { hour:'2-digit', minute:'2-digit', second:'2-digit' });

  const activosIds = new Set(activos.map(r => r.idusuario));

  activos.forEach(rep => {
    const lat    = parseFloat(rep.lat);
    const lng    = parseFloat(rep.lng);
    const nombre = rep.nombre + ' ' + (rep.apellido || '');
    const popup  = `<div class="rep-popup">
      <div class="rep-popup-name"><i class="fa-solid fa-motorcycle" style="color:#c88e99"></i> ${nombre.trim()}</div>
      <div class="rep-popup-time">Actualizado ${fmtAgo(rep.actualizado_at)}</div>
    </div>`;

    if (_markers[rep.idusuario]) {
      animarMarcador(_markers[rep.idusuario], lat, lng);
    } else {
      _markers[rep.idusuario] = L.marker([lat, lng], { icon: iconRep() }).addTo(map);
    }
    _markers[rep.idusuario].bindPopup(popup);
  });

  Object.keys(_markers).forEach(id => {
    if (!activosIds.has(parseInt(id))) {
      map.removeLayer(_markers[id]);
      delete _markers[id];
    }
  });

  const list = document.getElementById('repList');
  if (todos.length === 0) {
    list.innerHTML = `<div style="padding:24px;text-align:center;color:#ccc;font-size:13px">
      <i class="fa-solid fa-user-slash" style="font-size:24px;margin-bottom:8px;display:block"></i>
      No hay repartidores registrados
    </div>`;
    return;
  }

  list.innerHTML = '';
  todos.forEach(rep => {
    const isOnline = activosIds.has(rep.idusuario);
    const nombre   = (rep.nombre + ' ' + (rep.apellido || '')).trim();
    const ago      = rep.lat ? fmtAgo(rep.actualizado_at) : 'Sin ubicación';
    const item     = document.createElement('div');
    item.className = 'rep-item' + (isOnline ? ' online' : '');
    item.innerHTML = `
      <div class="rep-avatar">${initials(nombre)}</div>
      <div class="rep-info">
        <div class="rep-name">${nombre}</div>
        <div class="rep-status">
          <span class="rep-dot"></span>
          ${isOnline ? 'En línea · ' + ago : ago}
        </div>
      </div>
      <button class="rep-go-btn" title="Centrar en mapa">
        <i class="fa-solid fa-crosshairs"></i>
      </button>`;
    if (isOnline) {
      const centrar = () => {
        map.setView([parseFloat(rep.lat), parseFloat(rep.lng)], 16);
        _markers[rep.idusuario]?.openPopup();
      };
      item.querySelector('.rep-go-btn').addEventListener('click', e => { e.stopPropagation(); centrar(); });
      item.addEventListener('click', centrar);
    }
    list.appendChild(item);
  });
}

// ── Carga inicial (fallback HTTP) ────────────────────────────────────────────
async function cargarUbicaciones() {
  try {
    const data = await fetch('api/get_ubicaciones.php').then(r => r.json());
    procesarUbicaciones(data);
  } catch(e) { console.error('Error cargando ubicaciones', e); }
}

// ── SSE tiempo real ───────────────────────────────────────────────────────────
let _sse = null;

function conectarSSE() {
  if (_sse) { _sse.close(); _sse = null; }
  _sse = new EventSource('api/sse_ubicaciones.php', { withCredentials: true });

  _sse.addEventListener('ubicaciones', e => {
    try { procesarUbicaciones(JSON.parse(e.data)); } catch {}
  });

  _sse.onerror = () => {
    if (_sse.readyState === EventSource.CLOSED) {
      _sse = null;
      setTimeout(conectarSSE, 5000); // reconectar en 5s si se cae
    }
  };
}

// Cerrar SSE al salir de la página para no bloquear la navegación
window.addEventListener('pagehide', () => { if (_sse) { _sse.close(); _sse = null; } });

// Cargar al inicio
cargarSucursales();
cargarUbicaciones(); // carga inicial inmediata
conectarSSE();       // luego mantiene tiempo real por SSE
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>
