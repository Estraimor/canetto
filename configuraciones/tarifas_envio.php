<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Tarifas de Envío";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$pdo->exec("CREATE TABLE IF NOT EXISTS tarifas_envio (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    km_desde    DECIMAL(5,1) NOT NULL DEFAULT 0,
    km_hasta    DECIMAL(5,1) NOT NULL DEFAULT 5,
    precio      DECIMAL(10,2) NOT NULL DEFAULT 0,
    descripcion VARCHAR(100) NULL,
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$count = (int)$pdo->query("SELECT COUNT(*) FROM tarifas_envio")->fetchColumn();
if ($count === 0) {
    $pdo->exec("INSERT INTO tarifas_envio (km_desde, km_hasta, precio, descripcion) VALUES
        (0,    3,   4500,  'Zona cercana (0–3 km)'),
        (3,    6,   7000,  'Zona media (3–6 km)'),
        (6,    10,  10500, 'Zona media-lejana (6–10 km)'),
        (10,   15,  15000, 'Zona lejana (10–15 km)'),
        (15,   25,  21000, 'Zona muy lejana (15–25 km)'),
        (25,   999, 29000, 'Zona extrema (+25 km)')
    ");
}

// Asegurar columna latitud/longitud en sucursal
try { $pdo->exec("ALTER TABLE sucursal ADD COLUMN latitud DECIMAL(10,8) NULL"); } catch (Throwable $e) {}
try { $pdo->exec("ALTER TABLE sucursal ADD COLUMN longitud DECIMAL(11,8) NULL"); } catch (Throwable $e) {}

$tarifas   = $pdo->query("SELECT * FROM tarifas_envio ORDER BY km_desde ASC")->fetchAll();
$sucursales = $pdo->query("SELECT idsucursal, nombre, direccion, ciudad, latitud, longitud FROM sucursal WHERE activo=1 ORDER BY nombre")->fetchAll();

// Sucursal seleccionada para el mapa (primera con coords o primera)
$sucSelected = null;
foreach ($sucursales as $s) {
    if ($s['latitud'] && $s['longitud']) { $sucSelected = $s; break; }
}
if (!$sucSelected && !empty($sucursales)) $sucSelected = $sucursales[0];

// Coordenadas del mapa: usar las de la sucursal o Posadas por defecto
$mapLat = $sucSelected ? ($sucSelected['latitud'] ?: -27.3621) : -27.3621;
$mapLng = $sucSelected ? ($sucSelected['longitud'] ?: -55.9007) : -55.9007;
?>

<link rel="stylesheet" href="<?= URL_ASSETS ?>/configuraciones/cfg.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
/* ── Layout ── */
.tarifa-layout {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 20px;
    align-items: start;
    height: calc(100vh - 80px);
    padding: 24px 28px;
    max-width: 1400px;
    margin: 0 auto;
    box-sizing: border-box;
}
@media (max-width: 1100px) {
    .tarifa-layout { grid-template-columns: 1fr; height: auto; }
}

/* ── Mapa ── */
.map-panel {
    background: #fff;
    border: 1px solid #e8e7e4;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 540px;
}
.map-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid #f0f0f0;
    gap: 12px;
    flex-shrink: 0;
}
.map-toolbar-title {
    font-size: 13px;
    font-weight: 700;
    color: #111;
    display: flex;
    align-items: center;
    gap: 8px;
}
.map-toolbar-title i { color: #c88e99; }

.suc-selector {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #555;
}
.suc-selector select {
    padding: 6px 10px;
    border: 1.5px solid #e5e5e2;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    cursor: pointer;
    transition: border .15s;
}
.suc-selector select:focus { border-color: #c88e99; }

#mapa-zonas { flex: 1; min-height: 440px; }

.map-legend {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    padding: 10px 18px;
    border-top: 1px solid #f0f0f0;
    flex-shrink: 0;
    background: #fafafa;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: #555;
    font-weight: 500;
}
.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    border: 1.5px solid rgba(0,0,0,.15);
}

.map-coords-info {
    font-size: 11px;
    color: #aaa;
    padding: 6px 18px;
    background: #fafafa;
    border-top: 1px solid #f0f0f0;
}

/* ── Panel derecho ── */
.tarifa-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
    height: 100%;
    overflow-y: auto;
}

.tarifa-card {
    background: #fff;
    border: 1px solid #e8e7e4;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}

.tarifa-card-header {
    padding: 14px 18px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.tarifa-card-title {
    font-size: 13px;
    font-weight: 700;
    color: #111;
    display: flex;
    align-items: center;
    gap: 7px;
}
.tarifa-card-title i { color: #c88e99; opacity: .8; }

.tarifa-info-box {
    padding: 12px 16px;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 10px;
    margin: 14px 18px 0;
    font-size: 12px;
    color: #0c4a6e;
    line-height: 1.55;
    display: flex;
    gap: 10px;
}
.tarifa-info-box i { color: #0ea5e9; flex-shrink: 0; margin-top: 1px; }

/* ── Tabla ── */
.tarifa-rows { padding: 12px 14px 8px; display: flex; flex-direction: column; gap: 6px; }

.tarifa-row {
    display: grid;
    grid-template-columns: 70px 70px 100px 1fr 32px;
    gap: 6px;
    align-items: center;
    padding: 8px 10px;
    border-radius: 9px;
    background: #fafafa;
    border: 1px solid transparent;
    transition: border .15s, background .15s;
}
.tarifa-row:hover { background: #f5f4f1; border-color: #ebebeb; }

.tarifa-row input {
    width: 100%;
    padding: 7px 9px;
    border: 1.5px solid #e5e5e2;
    border-radius: 7px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    transition: border .15s;
    background: #fff;
    box-sizing: border-box;
}
.tarifa-row input:focus { border-color: #c88e99; }

.zone-color-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    border: 1.5px solid rgba(0,0,0,.15);
    display: inline-block;
    margin-right: 4px;
}

.tarifa-row-header {
    display: grid;
    grid-template-columns: 70px 70px 100px 1fr 32px;
    gap: 6px;
    padding: 0 10px 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #aaa;
}

.btn-del {
    background: none; border: none; color: #dc2626;
    cursor: pointer; padding: 5px; border-radius: 6px;
    font-size: 12px; transition: background .15s;
    display: flex; align-items: center; justify-content: center;
}
.btn-del:hover { background: #fee2e2; }

.tarifa-actions {
    display: flex;
    gap: 10px;
    padding: 12px 14px;
    border-top: 1px solid #f0f0f0;
}

.btn-add {
    display: flex; align-items: center; gap: 7px;
    padding: 9px 16px;
    background: #f5f4f1; border: 1.5px solid #e5e5e2;
    border-radius: 9px; font-size: 13px; font-weight: 600;
    cursor: pointer; color: #333; transition: .15s; font-family: inherit;
}
.btn-add:hover { background: #ebebeb; }

/* ── Coords card ── */
.suc-coords-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding: 14px 18px;
}
.suc-coord-field label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #aaa;
    margin-bottom: 5px;
}
.suc-coord-field input {
    width: 100%;
    padding: 9px 11px;
    border: 1.5px solid #e5e5e2;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'DM Mono', monospace;
    outline: none;
    transition: border .15s;
}
.suc-coord-field input:focus { border-color: #c88e99; }

.btn-geolocate {
    display: flex; align-items: center; gap: 7px;
    margin: 0 18px 14px;
    padding: 9px 16px;
    background: #f0fdf4; border: 1.5px solid #bbf7d0;
    border-radius: 9px; font-size: 13px; font-weight: 600;
    cursor: pointer; color: #166534; transition: .15s; font-family: inherit;
    width: calc(100% - 36px);
}
.btn-geolocate:hover { background: #dcfce7; }

/* ── Page header simplificado ── */
.tarifa-page-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 28px 0;
    max-width: 1400px;
    margin: 0 auto;
}
.tarifa-page-hd h1 { font-size: 22px; font-weight: 700; color: #111; }
.tarifa-page-hd p  { font-size: 13px; color: #888; margin-top: 2px; }
</style>

<!-- Header -->
<div class="tarifa-page-hd">
  <div>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#aaa;margin-bottom:4px">
      <a href="<?= URL_ASSETS ?>/configuraciones/index.php" style="color:#aaa;text-decoration:none">Configuraciones</a> /
    </div>
    <h1>Tarifas de envío a domicilio</h1>
    <p>Zonas de cobertura y precios por distancia desde cada sucursal</p>
  </div>
  <button class="btn-primary" onclick="guardar()">
    <i class="fa-solid fa-floppy-disk"></i> Guardar tarifas
  </button>
</div>

<!-- Layout principal -->
<div class="tarifa-layout">

  <!-- ══ MAPA ══ -->
  <div class="map-panel">
    <div class="map-toolbar">
      <div class="map-toolbar-title">
        <i class="fa-solid fa-map-location-dot"></i>
        Zonas de cobertura visual
      </div>
      <div class="suc-selector">
        <i class="fa-solid fa-store" style="color:#c88e99;font-size:12px"></i>
        <select id="selSucursal" onchange="cambiarSucursal()">
          <?php foreach ($sucursales as $s): ?>
          <option value="<?= $s['idsucursal'] ?>"
                  data-lat="<?= (float)($s['latitud'] ?: 0) ?>"
                  data-lng="<?= (float)($s['longitud'] ?: 0) ?>"
                  data-nombre="<?= htmlspecialchars($s['nombre']) ?>"
                  <?= ($sucSelected && $sucSelected['idsucursal'] == $s['idsucursal']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['nombre']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div id="mapa-zonas"></div>

    <div class="map-legend" id="mapLegend"></div>
    <div class="map-coords-info" id="mapCoordsInfo">
      Clic en el mapa para reposicionar la sucursal seleccionada
    </div>
  </div>

  <!-- ══ PANEL DERECHO ══ -->
  <div class="tarifa-panel">

    <!-- Coordenadas de la sucursal -->
    <div class="tarifa-card">
      <div class="tarifa-card-header">
        <div class="tarifa-card-title">
          <i class="fa-solid fa-location-crosshairs"></i>
          Ubicación de la sucursal
        </div>
      </div>
      <div class="suc-coords-grid">
        <div class="suc-coord-field">
          <label>Latitud</label>
          <input type="text" id="inpLat" placeholder="-27.3621" value="<?= $mapLat !== -27.3621 ? $mapLat : '' ?>">
        </div>
        <div class="suc-coord-field">
          <label>Longitud</label>
          <input type="text" id="inpLng" placeholder="-55.9007" value="<?= $mapLng !== -55.9007 ? $mapLng : '' ?>">
        </div>
      </div>
      <button class="btn-geolocate" onclick="centrarMapa()">
        <i class="fa-solid fa-crosshairs"></i> Centrar mapa con estas coordenadas
      </button>
      <button class="btn-geolocate" style="background:#f0f4ff;border-color:#bfdbfe;color:#1d4ed8;margin-top:-4px" onclick="guardarCoordenadas()">
        <i class="fa-solid fa-floppy-disk"></i> Guardar ubicación de la sucursal
      </button>
    </div>

    <!-- Tabla de tarifas -->
    <div class="tarifa-card">
      <div class="tarifa-card-header">
        <div class="tarifa-card-title">
          <i class="fa-solid fa-circle-dollar-to-slot"></i>
          Tramos de precio
        </div>
      </div>

      <div class="tarifa-info-box">
        <i class="fa-solid fa-circle-info"></i>
        <div>El sistema usa la fórmula Haversine para calcular la distancia real y aplica el tramo correspondiente. Cada círculo en el mapa representa una zona.</div>
      </div>

      <div class="tarifa-rows">
        <div class="tarifa-row-header">
          <span>Desde km</span>
          <span>Hasta km</span>
          <span>Precio $</span>
          <span>Descripción</span>
          <span></span>
        </div>
        <div id="bodyTarifas">
        <?php foreach ($tarifas as $i => $t): ?>
          <div class="tarifa-row" data-id="<?= $t['id'] ?>">
            <input type="number" class="t-desde" value="<?= $t['km_desde'] ?>" min="0" step="0.5" onchange="actualizarMapa()">
            <input type="number" class="t-hasta" value="<?= (float)$t['km_hasta'] >= 999 ? 999 : $t['km_hasta'] ?>" min="0" step="0.5" placeholder="999" onchange="actualizarMapa()">
            <input type="number" class="t-precio" value="<?= (int)$t['precio'] ?>" min="0" step="100">
            <input type="text" class="t-desc" value="<?= htmlspecialchars($t['descripcion'] ?? '') ?>" placeholder="Descripción">
            <button class="btn-del" onclick="eliminarFila(this)" title="Eliminar">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        <?php endforeach; ?>
        </div>
      </div>

      <div class="tarifa-actions">
        <button class="btn-add" onclick="agregarFila()">
          <i class="fa-solid fa-plus"></i> Agregar tramo
        </button>
        <button class="btn-primary" onclick="guardar()" style="margin-left:auto">
          <i class="fa-solid fa-floppy-disk"></i> Guardar tarifas
        </button>
      </div>
    </div>

  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ── Datos iniciales ── */
let mapLat = <?= (float)$mapLat ?>;
let mapLng = <?= (float)$mapLng ?>;
const sucursalData = <?= json_encode(array_map(fn($s) => [
    'id'      => $s['idsucursal'],
    'nombre'  => $s['nombre'],
    'lat'     => (float)($s['latitud'] ?: 0),
    'lng'     => (float)($s['longitud'] ?: 0),
    'dir'     => $s['direccion'] ?? '',
], $sucursales)) ?>;

/* ── Colores de zonas ── */
const ZONE_COLORS = [
    '#22c55e', '#84cc16', '#eab308',
    '#f97316', '#ef4444', '#dc2626',
    '#991b1b', '#7f1d1d'
];

/* ── Init mapa ── */
const mapa = L.map('mapa-zonas', {
    center: [mapLat, mapLng],
    zoom: 11,
    zoomControl: true,
});

L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '© OpenStreetMap · © CARTO',
    maxZoom: 19,
}).addTo(mapa);

/* ── Marcador de sucursal ── */
const branchIcon = L.divIcon({
    html: `<div style="
        width:36px;height:36px;border-radius:50%;
        background:#c88e99;border:3px solid #fff;
        box-shadow:0 2px 10px rgba(0,0,0,.3);
        display:flex;align-items:center;justify-content:center;
        font-size:16px;color:#fff">
        <i class='fa-solid fa-store'></i>
    </div>`,
    className: '',
    iconSize: [36, 36],
    iconAnchor: [18, 18],
});

let branchMarker = L.marker([mapLat, mapLng], { icon: branchIcon, draggable: true }).addTo(mapa);
branchMarker.bindPopup('<strong>Sucursal</strong>').openPopup();

branchMarker.on('dragend', function(e) {
    const pos = e.target.getLatLng();
    mapLat = pos.lat;
    mapLng = pos.lng;
    document.getElementById('inpLat').value = pos.lat.toFixed(7);
    document.getElementById('inpLng').value = pos.lng.toFixed(7);
    actualizarMapa();
    document.getElementById('mapCoordsInfo').textContent =
        `📍 ${pos.lat.toFixed(5)}, ${pos.lng.toFixed(5)} — Guardá la ubicación para persistir el cambio`;
});

/* Clic en mapa mueve el marcador */
mapa.on('click', function(e) {
    mapLat = e.latlng.lat;
    mapLng = e.latlng.lng;
    branchMarker.setLatLng([mapLat, mapLng]);
    document.getElementById('inpLat').value = mapLat.toFixed(7);
    document.getElementById('inpLng').value = mapLng.toFixed(7);
    actualizarMapa();
    document.getElementById('mapCoordsInfo').textContent =
        `📍 ${mapLat.toFixed(5)}, ${mapLng.toFixed(5)} — Guardá la ubicación para persistir el cambio`;
});

/* ── Círculos ── */
let circulos = [];

function getTarifas() {
    return [...document.querySelectorAll('#bodyTarifas .tarifa-row')].map(row => ({
        desde:  parseFloat(row.querySelector('.t-desde').value) || 0,
        hasta:  parseFloat(row.querySelector('.t-hasta').value) || 999,
        precio: parseFloat(row.querySelector('.t-precio').value) || 0,
        desc:   row.querySelector('.t-desc').value.trim(),
    })).filter(t => t.hasta < 999).sort((a, b) => a.hasta - b.hasta);
}

function actualizarMapa() {
    circulos.forEach(c => mapa.removeLayer(c));
    circulos = [];
    const filas = getTarifas();
    const legend = document.getElementById('mapLegend');
    legend.innerHTML = '';

    filas.forEach((t, i) => {
        const color = ZONE_COLORS[i % ZONE_COLORS.length];
        const radio = t.hasta * 1000; // km → metros

        const circle = L.circle([mapLat, mapLng], {
            radius: radio,
            color:  color,
            weight: 2,
            fillColor: color,
            fillOpacity: 0.12,
            opacity: 0.7,
        }).addTo(mapa);

        circle.bindPopup(`
            <div style="font-family:sans-serif;min-width:160px">
                <div style="font-weight:700;margin-bottom:4px">${t.desc || 'Zona ' + (i+1)}</div>
                <div style="font-size:12px;color:#555">Hasta ${t.hasta} km</div>
                <div style="font-size:15px;font-weight:700;color:${color};margin-top:4px">
                    $${t.precio.toLocaleString('es-AR')}
                </div>
            </div>
        `);
        circulos.push(circle);

        // Leyenda
        const li = document.createElement('div');
        li.className = 'legend-item';
        li.innerHTML = `
            <span class="legend-dot" style="background:${color}"></span>
            <span>${t.desc || 'Zona ' + (i+1)} — $${t.precio.toLocaleString('es-AR')}</span>
        `;
        legend.appendChild(li);
    });

    if (filas.length > 0) {
        const maxKm = Math.max(...filas.map(t => t.hasta));
        try { mapa.fitBounds(L.circle([mapLat, mapLng], { radius: maxKm * 1000 }).getBounds(), { padding: [30, 30] }); } catch(e) {}
    }
}

actualizarMapa();

/* ── Cambiar sucursal en el selector ── */
function cambiarSucursal() {
    const opt = document.getElementById('selSucursal').selectedOptions[0];
    const lat = parseFloat(opt.dataset.lat) || 0;
    const lng = parseFloat(opt.dataset.lng) || 0;
    if (lat && lng) {
        mapLat = lat; mapLng = lng;
        mapa.setView([lat, lng], 12);
        branchMarker.setLatLng([lat, lng]);
        branchMarker.setPopupContent('<strong>' + opt.dataset.nombre + '</strong>').openPopup();
        document.getElementById('inpLat').value = lat.toFixed(7);
        document.getElementById('inpLng').value = lng.toFixed(7);
        actualizarMapa();
        document.getElementById('mapCoordsInfo').textContent =
            `Sucursal: ${opt.dataset.nombre} · ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    } else {
        document.getElementById('mapCoordsInfo').textContent =
            `⚠ Sin coordenadas cargadas — hacé clic en el mapa para posicionarla`;
    }
}

/* ── Centrar con inputs ── */
function centrarMapa() {
    const lat = parseFloat(document.getElementById('inpLat').value);
    const lng = parseFloat(document.getElementById('inpLng').value);
    if (isNaN(lat) || isNaN(lng)) {
        Swal.fire({ icon: 'warning', title: 'Coordenadas inválidas', text: 'Ingresá latitud y longitud válidas.', confirmButtonColor: '#0a0a0a' });
        return;
    }
    mapLat = lat; mapLng = lng;
    mapa.setView([lat, lng], 12);
    branchMarker.setLatLng([lat, lng]);
    actualizarMapa();
}

/* ── Guardar coordenadas ── */
async function guardarCoordenadas() {
    const lat = parseFloat(document.getElementById('inpLat').value);
    const lng = parseFloat(document.getElementById('inpLng').value);
    const suc = document.getElementById('selSucursal').value;
    if (isNaN(lat) || isNaN(lng) || !suc) {
        Swal.fire({ icon: 'warning', title: 'Datos incompletos', confirmButtonColor: '#0a0a0a' }); return;
    }
    try {
        const res = await fetch('ajax/guardar_coords_sucursal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idsucursal: suc, latitud: lat, longitud: lng })
        });
        const data = await res.json();
        if (data.ok) {
            Swal.fire({ icon: 'success', title: 'Ubicación guardada', timer: 1800, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.msg, confirmButtonColor: '#0a0a0a' });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonColor: '#0a0a0a' });
    }
}

/* ── Filas ── */
function agregarFila() {
    const body = document.getElementById('bodyTarifas');
    const div = document.createElement('div');
    div.className = 'tarifa-row';
    div.dataset.id = 'new';
    div.innerHTML = `
        <input type="number" class="t-desde" value="0" min="0" step="0.5" onchange="actualizarMapa()">
        <input type="number" class="t-hasta" value="5" min="0" step="0.5" placeholder="999" onchange="actualizarMapa()">
        <input type="number" class="t-precio" value="0" min="0" step="100">
        <input type="text" class="t-desc" placeholder="Descripción">
        <button class="btn-del" onclick="eliminarFila(this)"><i class="fa-solid fa-trash"></i></button>
    `;
    body.appendChild(div);
    div.querySelector('.t-precio').focus();
    actualizarMapa();
}

function eliminarFila(btn) {
    Swal.fire({
        title: '¿Eliminar tramo?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    }).then(r => {
        if (r.isConfirmed) { btn.closest('.tarifa-row').remove(); actualizarMapa(); }
    });
}

/* ── Guardar tarifas ── */
async function guardar() {
    const rows = [...document.querySelectorAll('#bodyTarifas .tarifa-row')];
    const tarifas = rows.map(tr => ({
        id:          tr.dataset.id,
        km_desde:    parseFloat(tr.querySelector('.t-desde').value) || 0,
        km_hasta:    parseFloat(tr.querySelector('.t-hasta').value) || 999,
        precio:      parseFloat(tr.querySelector('.t-precio').value) || 0,
        descripcion: tr.querySelector('.t-desc').value.trim(),
    }));

    for (const t of tarifas) {
        if (t.precio <= 0) {
            Swal.fire({ icon: 'warning', title: 'Precio inválido', text: 'Todos los precios deben ser mayores a 0.', confirmButtonColor: '#0a0a0a' }); return;
        }
        if (t.km_hasta <= t.km_desde) {
            Swal.fire({ icon: 'warning', title: 'Rango inválido', text: '"Hasta" debe ser mayor que "Desde".', confirmButtonColor: '#0a0a0a' }); return;
        }
    }

    const btn = document.querySelector('.tarifa-page-hd .btn-primary');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    try {
        const res = await fetch('ajax/guardar_tarifas_envio.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tarifas })
        });
        const data = await res.json();
        if (data.ok) {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Tarifas actualizadas.', confirmButtonColor: '#0a0a0a', timer: 2000, timerProgressBar: true });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.msg || 'No se pudo guardar.', confirmButtonColor: '#0a0a0a' });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonColor: '#0a0a0a' });
    }
    btn.innerHTML = orig;
    btn.disabled = false;
}
</script>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
