<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
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
const CANETTO_LAT = -34.6037;
const CANETTO_LNG = -58.3816;

const initials = n => (n||'?').trim().split(/\s+/).map(w=>w[0]).join('').substring(0,2).toUpperCase();

const map = L.map('repAdminMap', { zoomControl: true, attributionControl: false });
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
map.setView([CANETTO_LAT, CANETTO_LNG], 13);

// Marcador de la tienda
const iconTienda = L.divIcon({
  className: '',
  html: `<div style="background:#f59e0b;width:36px;height:36px;border-radius:50%;
           display:flex;align-items:center;justify-content:center;
           border:3px solid #fff;box-shadow:0 2px 10px rgba(0,0,0,.3);font-size:17px">🏪</div>`,
  iconSize: [36,36], iconAnchor: [18,18],
});
L.marker([CANETTO_LAT, CANETTO_LNG], { icon: iconTienda })
  .bindPopup('<strong>Canetto Cookies</strong>')
  .addTo(map);

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

async function cargarUbicaciones() {
  try {
    const res  = await fetch('api/get_ubicaciones.php');
    const data = await res.json();
    if (!data.ok) return;

    const activos = data.activos;
    const todos   = data.todos;

    // Actualizar contador y subtítulo
    document.getElementById('repCounter').textContent  = activos.length;
    document.getElementById('repSubtitle').textContent =
      activos.length === 0
        ? 'Ningún repartidor activo ahora'
        : activos.length + ' en línea · ' + (todos.length - activos.length) + ' sin ubicación';

    // Actualizar hora
    document.getElementById('repLastUpdate').textContent =
      'Actualizado ' + new Date().toLocaleTimeString('es-AR', { hour:'2-digit', minute:'2-digit', second:'2-digit' });

    // IDs activos para saber cuáles tienen marker
    const activosIds = new Set(activos.map(r => r.idusuario));

    // Actualizar / agregar markers en el mapa
    activos.forEach(rep => {
      const lat = parseFloat(rep.lat);
      const lng = parseFloat(rep.lng);
      const nombre = rep.nombre + ' ' + (rep.apellido || '');

      if (_markers[rep.idusuario]) {
        _markers[rep.idusuario].setLatLng([lat, lng]);
      } else {
        _markers[rep.idusuario] = L.marker([lat, lng], { icon: iconRep() })
          .addTo(map);
      }

      _markers[rep.idusuario].bindPopup(`
        <div class="rep-popup">
          <div class="rep-popup-name"><i class="fa-solid fa-motorcycle" style="color:#c88e99"></i> ${nombre.trim()}</div>
          <div class="rep-popup-time">Actualizado ${fmtAgo(rep.actualizado_at)}</div>
        </div>
      `);
    });

    // Quitar markers de repartidores que ya no están activos
    Object.keys(_markers).forEach(id => {
      if (!activosIds.has(parseInt(id))) {
        map.removeLayer(_markers[id]);
        delete _markers[id];
      }
    });

    // Render lista lateral
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

      const item = document.createElement('div');
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
        </button>
      `;

      if (isOnline) {
        item.querySelector('.rep-go-btn').addEventListener('click', e => {
          e.stopPropagation();
          const lat = parseFloat(rep.lat);
          const lng = parseFloat(rep.lng);
          map.setView([lat, lng], 16);
          _markers[rep.idusuario]?.openPopup();
        });
        item.addEventListener('click', () => {
          const lat = parseFloat(rep.lat);
          const lng = parseFloat(rep.lng);
          map.setView([lat, lng], 16);
          _markers[rep.idusuario]?.openPopup();
        });
      }

      list.appendChild(item);
    });

  } catch(e) {
    console.error('Error cargando ubicaciones', e);
  }
}

// Cargar al inicio y cada 30 segundos
cargarUbicaciones();
setInterval(cargarUbicaciones, 30000);
</script>
