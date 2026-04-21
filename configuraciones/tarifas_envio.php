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
/* ── Base ── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Page ── */
.tf-page { max-width: 1440px; margin: 0 auto; padding: 20px 28px 48px; }

/* ── Header ── */
.tf-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 20px;
    gap: 16px;
    flex-wrap: wrap;
}
.tf-breadcrumb {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #c0bdb9; margin-bottom: 6px;
}
.tf-breadcrumb a { color: #c0bdb9; text-decoration: none; transition: color .15s; }
.tf-breadcrumb a:hover { color: #888; }
.tf-title { font-size: 24px; font-weight: 800; color: #111; letter-spacing: -.4px; line-height: 1.2; }
.tf-subtitle { font-size: 13px; color: #aaa; margin-top: 3px; }

/* ── Layout ── */
.tf-layout {
    display: grid;
    grid-template-columns: 1fr 410px;
    gap: 18px;
    align-items: start;
}
@media (max-width: 1100px) { .tf-layout { grid-template-columns: 1fr; } }

/* ── Card base ── */
.tf-card {
    background: #fff;
    border: 1px solid #e9e8e5;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 4px 20px rgba(0,0,0,.05);
}

/* ── Map card: no clip para que Leaflet SVG no se corte ── */
.tf-map-card { overflow: visible; border-radius: 16px; }
.tf-map-clip { overflow: hidden; border-radius: 0; }

/* ── Map ── */
.tf-map-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-bottom: 1px solid #f2f1ee;
    background: #fff;
}
.tf-map-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; font-weight: 700; color: #222;
}
.tf-map-title i { color: #c88e99; }

.tf-suc-select {
    display: flex; align-items: center; gap: 7px;
    font-size: 12px; color: #888;
}
.tf-suc-select i { color: #c88e99; }
.tf-suc-select select {
    padding: 6px 10px;
    border: 1.5px solid #e5e5e2;
    border-radius: 8px;
    font-size: 12.5px; font-family: inherit; font-weight: 600;
    outline: none; cursor: pointer; color: #333;
    transition: border .15s; background: #fff;
}
.tf-suc-select select:focus { border-color: #c88e99; }

#mapa-zonas { width: 100%; height: 420px; display: block; }

.tf-map-legend {
    display: flex; gap: 4px 16px; flex-wrap: wrap;
    padding: 10px 18px;
    border-top: 1px solid #f2f1ee;
    background: #fafaf8;
}
.tf-legend-item {
    display: flex; align-items: center; gap: 6px;
    font-size: 11.5px; color: #555; font-weight: 500; white-space: nowrap;
}
.tf-legend-dot {
    width: 10px; height: 10px; border-radius: 50%;
    flex-shrink: 0; border: 1.5px solid rgba(0,0,0,.12);
}

/* ── Sidebar ── */
.tf-sidebar { display: flex; flex-direction: column; gap: 14px; }

/* ── Location card ── */
.tf-loc-body {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px 10px;
}
.tf-loc-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: #fdf2f5; border: 1px solid #f5d8e0;
    display: flex; align-items: center; justify-content: center;
    color: #c88e99; font-size: 15px; flex-shrink: 0;
}
.tf-loc-meta { flex: 1; min-width: 0; }
.tf-loc-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #c0bdb9; margin-bottom: 4px;
}
.tf-loc-coords { display: flex; align-items: center; gap: 5px; }
.tf-loc-val {
    flex: 1; font-size: 11.5px; font-family: 'DM Mono','Courier New',monospace;
    color: #444; background: #f5f5f3; border: 1px solid #ebebea;
    padding: 4px 8px; border-radius: 6px; text-align: center;
}
.tf-loc-sep { font-size: 10px; color: #d0cdc9; }

.tf-loc-actions { display: flex; gap: 6px; padding: 0 16px 12px; }
.tf-loc-btn {
    flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px;
    padding: 7px 10px; border-radius: 8px; font-size: 11.5px; font-weight: 600;
    cursor: pointer; font-family: inherit; transition: .15s; border: 1.5px solid;
}
.tf-loc-btn-green { background: #f0fdf4; border-color: #86efac; color: #15803d; }
.tf-loc-btn-green:hover { background: #dcfce7; }
.tf-loc-btn-blue  { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
.tf-loc-btn-blue:hover  { background: #dbeafe; }

/* ── Pricing card ── */
.tf-pricing-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 15px 18px; border-bottom: 1px solid #f2f1ee;
}
.tf-pricing-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 14px; font-weight: 700; color: #111;
}
.tf-pricing-title i { color: #c88e99; }
.tf-zone-count {
    font-size: 10.5px; font-weight: 700; background: #f3f4f6;
    color: #666; padding: 3px 9px; border-radius: 20px;
}

.tf-info-strip {
    display: flex; align-items: flex-start; gap: 8px;
    padding: 9px 16px; background: #f8fafc;
    border-bottom: 1px solid #eef0f2;
    font-size: 11.5px; color: #64748b; line-height: 1.5;
}
.tf-info-strip i { color: #94a3b8; margin-top: 1px; flex-shrink: 0; }

/* ── Stats strip ── */
.tf-stats {
    display: flex; gap: 6px; padding: 8px 16px;
    border-bottom: 1px solid #f2f1ee; flex-wrap: wrap; background: #fefefe;
}
.tf-stat {
    display: flex; align-items: center; gap: 5px;
    font-size: 11px; color: #777; background: #f4f4f2;
    padding: 3px 9px; border-radius: 20px; font-weight: 500;
}
.tf-stat b { color: #333; }

/* ── Table ── */
.tf-thead {
    display: grid;
    grid-template-columns: 10px 118px 108px 1fr 30px;
    gap: 6px; padding: 7px 16px 6px;
    background: #f9f9f7; border-bottom: 1px solid #efeeec;
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #c0bdb9;
}
.tf-tbody { padding: 8px 10px; display: flex; flex-direction: column; gap: 3px; }

.tarifa-row {
    display: grid;
    grid-template-columns: 10px 118px 108px 1fr 30px;
    gap: 6px; align-items: center;
    padding: 5px 6px 5px 8px;
    border-radius: 10px;
    border: 1.5px solid transparent;
    transition: all .12s;
}
.tarifa-row:hover { background: #fafaf7; border-color: #edece9; }

.tf-row-dot {
    width: 8px; height: 8px; border-radius: 50%;
    flex-shrink: 0; border: 1.5px solid rgba(0,0,0,.15);
}

/* km range inputs */
.tf-km-wrap { display: flex; align-items: center; gap: 3px; }
.tf-km-wrap input {
    width: 50px; padding: 6px 6px; text-align: center;
    border: 1.5px solid #e5e5e2; border-radius: 7px;
    font-size: 12px; font-family: inherit; outline: none;
    transition: border .15s; background: #fff;
}
.tf-km-wrap input:focus { border-color: #c88e99; }
.tf-km-sep { font-size: 10px; color: #d0cdc9; font-weight: 600; flex-shrink: 0; }

/* price input */
.tf-price-wrap { position: relative; }
.tf-price-sym {
    position: absolute; left: 8px; top: 50%; transform: translateY(-50%);
    font-size: 11px; color: #bbb; font-weight: 700; pointer-events: none;
}
.tf-price-wrap input {
    width: 100%; padding: 6px 8px 6px 20px;
    border: 1.5px solid #e5e5e2; border-radius: 7px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    outline: none; transition: all .15s; background: #fff; color: #111;
    box-sizing: border-box;
}
.tf-price-wrap input:focus { border-color: #16a34a; background: #f0fdf4; }

/* desc input */
.t-desc {
    width: 100%; padding: 6px 8px;
    border: 1.5px solid #e5e5e2; border-radius: 7px;
    font-size: 11.5px; font-family: inherit; outline: none;
    transition: border .15s; background: #fff; color: #666;
    box-sizing: border-box;
}
.t-desc:focus { border-color: #c88e99; }

.btn-del {
    background: none; border: none; color: #d1d5db;
    cursor: pointer; padding: 5px; border-radius: 6px;
    font-size: 11px; transition: all .15s;
    display: flex; align-items: center; justify-content: center;
}
.btn-del:hover { color: #dc2626; background: #fee2e2; }

/* ── Footer ── */
.tf-tfoot {
    display: flex; align-items: center; justify-content: space-between;
    padding: 11px 16px; border-top: 1px solid #f2f1ee;
    background: #fafaf8;
}
.tf-btn-add {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 14px; background: #fff;
    border: 1.5px solid #e0dedd; border-radius: 9px;
    font-size: 12px; font-weight: 600; cursor: pointer;
    color: #555; transition: .15s; font-family: inherit;
}
.tf-btn-add:hover { border-color: #c88e99; color: #c88e99; background: #fdf2f5; }

.tf-btn-save-main {
    display: flex; align-items: center; gap: 7px;
    padding: 9px 20px; background: #111; border: none;
    border-radius: 9px; font-size: 13px; font-weight: 700;
    cursor: pointer; color: #fff; transition: .15s;
    font-family: inherit; letter-spacing: -.1px;
}
.tf-btn-save-main:hover { background: #2d2d2d; }
.tf-btn-save-main:disabled { opacity: .5; cursor: not-allowed; }

/* ── Page save button ── */
.tf-page-save {
    display: flex; align-items: center; gap: 7px;
    padding: 10px 22px; background: #111; border: none;
    border-radius: 10px; font-size: 13px; font-weight: 700;
    cursor: pointer; color: #fff; transition: .15s;
    font-family: inherit; letter-spacing: -.1px; white-space: nowrap;
}
.tf-page-save:hover { background: #2d2d2d; }
.tf-page-save:disabled { opacity: .5; cursor: not-allowed; }
</style>

<div class="tf-page">

  <!-- ══ HEADER ══ -->
  <div class="tf-header">
    <div>
      <div class="tf-breadcrumb">
        <a href="<?= URL_ASSETS ?>/configuraciones/index.php">Configuraciones</a>
        <span style="margin:0 5px;opacity:.5">/</span> Tarifas de envío
      </div>
      <h1 class="tf-title">Tarifas de envío a domicilio</h1>
      <p class="tf-subtitle">Definí los precios por zona de cobertura desde cada sucursal</p>
    </div>
    <button class="tf-page-save" id="btnGuardarMain" onclick="guardar()">
      <i class="fa-solid fa-floppy-disk"></i> Guardar tarifas
    </button>
  </div>

  <!-- ══ LAYOUT ══ -->
  <div class="tf-layout">

    <!-- ── MAPA ── -->
    <div class="tf-card tf-map-card">
      <div class="tf-map-bar">
        <div class="tf-map-title">
          <i class="fa-solid fa-map-location-dot"></i>
          Zonas de cobertura visual
        </div>
        <div class="tf-suc-select">
          <i class="fa-solid fa-store" style="font-size:11px"></i>
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

      <div class="tf-map-clip">
        <div id="mapa-zonas"></div>
      </div>

      <div class="tf-map-legend" id="mapLegend"></div>
    </div>

    <!-- ── SIDEBAR ── -->
    <div class="tf-sidebar">

      <!-- Ubicación sucursal -->
      <div class="tf-card">
        <div class="tf-loc-body">
          <div class="tf-loc-icon"><i class="fa-solid fa-location-dot"></i></div>
          <div class="tf-loc-meta">
            <div class="tf-loc-label">Ubicación de la sucursal</div>
            <div class="tf-loc-coords">
              <span class="tf-loc-val" id="dispLat"><?= $mapLat !== -27.3621 ? $mapLat : '—' ?></span>
              <span class="tf-loc-sep">·</span>
              <span class="tf-loc-val" id="dispLng"><?= $mapLng !== -55.9007 ? $mapLng : '—' ?></span>
            </div>
          </div>
        </div>
        <input type="hidden" id="inpLat" value="<?= $mapLat !== -27.3621 ? $mapLat : '' ?>">
        <input type="hidden" id="inpLng" value="<?= $mapLng !== -55.9007 ? $mapLng : '' ?>">
        <div class="tf-loc-actions">
          <button class="tf-loc-btn tf-loc-btn-green" onclick="centrarMapa()">
            <i class="fa-solid fa-crosshairs"></i> Centrar mapa
          </button>
          <button class="tf-loc-btn tf-loc-btn-blue" onclick="guardarCoordenadas()">
            <i class="fa-solid fa-floppy-disk"></i> Guardar coords
          </button>
        </div>
      </div>

      <!-- Tabla de tarifas -->
      <div class="tf-card">
        <div class="tf-pricing-header">
          <div class="tf-pricing-title">
            <i class="fa-solid fa-tags"></i>
            Tramos de precio
          </div>
          <span class="tf-zone-count" id="badgeZonas">— zonas</span>
        </div>

        <div class="tf-info-strip">
          <i class="fa-solid fa-circle-info"></i>
          <span>Distancia calculada con Haversine. Cada círculo en el mapa corresponde a una zona de tarifa.</span>
        </div>

        <div class="tf-stats" id="statsPanel">
          <span class="tf-stat"><b id="statZonas">0</b>&nbsp;zonas activas</span>
          <span class="tf-stat">Cobertura hasta&nbsp;<b id="statKm">0</b>&nbsp;km</span>
          <span class="tf-stat">Desde&nbsp;<b id="statMin">$0</b></span>
        </div>

        <div class="tf-thead">
          <span></span>
          <span>Rango km</span>
          <span>Precio $</span>
          <span>Descripción</span>
          <span></span>
        </div>

        <div class="tf-tbody" id="bodyTarifas">
        <?php foreach ($tarifas as $i => $t): ?>
          <div class="tarifa-row" data-id="<?= $t['id'] ?>">
            <span class="tf-row-dot" style="background:<?= ['#22c55e','#84cc16','#eab308','#f97316','#ef4444','#dc2626','#991b1b','#7f1d1d'][$i % 8] ?>"></span>
            <div class="tf-km-wrap">
              <input type="number" class="t-desde" value="<?= $t['km_desde'] ?>" min="0" step="0.5" onchange="actualizarMapa()">
              <span class="tf-km-sep">→</span>
              <input type="number" class="t-hasta" value="<?= (float)$t['km_hasta'] >= 999 ? 999 : $t['km_hasta'] ?>" min="0" step="0.5" placeholder="∞" onchange="actualizarMapa()">
            </div>
            <div class="tf-price-wrap">
              <span class="tf-price-sym">$</span>
              <input type="number" class="t-precio" value="<?= (int)$t['precio'] ?>" min="0" step="100">
            </div>
            <input type="text" class="t-desc" value="<?= htmlspecialchars($t['descripcion'] ?? '') ?>" placeholder="Descripción">
            <button class="btn-del" onclick="eliminarFila(this)" title="Eliminar zona">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        <?php endforeach; ?>
        </div>

        <div class="tf-tfoot">
          <button class="tf-btn-add" onclick="agregarFila()">
            <i class="fa-solid fa-plus"></i> Nueva zona
          </button>
          <button class="tf-btn-save-main" id="btnGuardarCard" onclick="guardar()">
            <i class="fa-solid fa-floppy-disk"></i> Guardar
          </button>
        </div>
      </div>

    </div><!-- /sidebar -->
  </div><!-- /layout -->
</div><!-- /page -->

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

let branchMarker = L.marker([mapLat, mapLng], { icon: branchIcon, draggable: false }).addTo(mapa);
branchMarker.bindPopup('<strong>Sucursal</strong>').openPopup();

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
        const radio = t.hasta * 1000;

        const circle = L.circle([mapLat, mapLng], {
            radius: radio,
            color:  color,
            weight: 2,
            fillColor: color,
            fillOpacity: 0.13,
            opacity: 0.75,
        }).addTo(mapa);

        circle.bindPopup(`
            <div style="font-family:inherit;min-width:160px;padding:2px">
                <div style="font-weight:700;font-size:13px;margin-bottom:4px">${t.desc || 'Zona ' + (i+1)}</div>
                <div style="font-size:11px;color:#888;margin-bottom:2px">${t.desde} – ${t.hasta} km</div>
                <div style="font-size:18px;font-weight:800;color:${color}">$${t.precio.toLocaleString('es-AR')}</div>
            </div>
        `);
        circulos.push(circle);

        /* Actualiza dot en la fila de la tabla */
        const rows = document.querySelectorAll('#bodyTarifas .tarifa-row');
        if (rows[i]) {
            const dot = rows[i].querySelector('.tf-row-dot');
            if (dot) dot.style.background = color;
        }

        const li = document.createElement('div');
        li.className = 'tf-legend-item';
        li.innerHTML = `<span class="tf-legend-dot" style="background:${color}"></span><span>${t.desc || 'Zona '+(i+1)} — $${t.precio.toLocaleString('es-AR')}</span>`;
        legend.appendChild(li);
    });

    /* Stats */
    const n = filas.length;
    const maxKm = n ? Math.max(...filas.map(t => t.hasta)) : 0;
    const minP  = n ? Math.min(...filas.map(t => t.precio)) : 0;
    document.getElementById('badgeZonas').textContent = n + (n === 1 ? ' zona' : ' zonas');
    document.getElementById('statZonas').textContent  = n;
    document.getElementById('statKm').textContent     = maxKm < 999 ? maxKm : '+' + (maxKm - 1);
    document.getElementById('statMin').textContent    = '$' + minP.toLocaleString('es-AR');
}

/* Centrar vista solo en la carga inicial */
function fitMapToZones() {
    const filas = getTarifas();
    if (filas.length > 0) {
        const maxKm = Math.max(...filas.map(t => t.hasta));
        try { mapa.fitBounds(L.circle([mapLat, mapLng], { radius: maxKm * 1000 }).getBounds(), { padding: [30, 30] }); } catch(e) {}
    }
}

/* Leaflet necesita que el contenedor esté renderizado para calcular el ancho real */
actualizarMapa();
setTimeout(() => {
    mapa.invalidateSize();
    fitMapToZones();
}, 120);

window.addEventListener('resize', () => mapa.invalidateSize());

/* ── Cambiar sucursal en el selector ── */
function setCoordDisplay(lat, lng) {
    document.getElementById('inpLat').value  = lat || '';
    document.getElementById('inpLng').value  = lng || '';
    document.getElementById('dispLat').textContent = lat ? parseFloat(lat).toFixed(6) : '—';
    document.getElementById('dispLng').textContent = lng ? parseFloat(lng).toFixed(6) : '—';
}

function cambiarSucursal() {
    const opt = document.getElementById('selSucursal').selectedOptions[0];
    const lat = parseFloat(opt.dataset.lat) || 0;
    const lng = parseFloat(opt.dataset.lng) || 0;
    if (lat && lng) {
        mapLat = lat; mapLng = lng;
        branchMarker.setLatLng([lat, lng]);
        branchMarker.setPopupContent('<strong>' + opt.dataset.nombre + '</strong>').openPopup();
        setCoordDisplay(lat.toFixed(7), lng.toFixed(7));
        actualizarMapa();
        fitMapToZones();
    } else {
        Swal.fire({ toast: true, position: 'top-end', icon: 'warning',
            title: 'Sin coordenadas para esta sucursal', showConfirmButton: false, timer: 2000 });
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
    branchMarker.setLatLng([lat, lng]);
    actualizarMapa();
    fitMapToZones();
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
    const idx  = body.querySelectorAll('.tarifa-row').length;
    const color = ZONE_COLORS[idx % ZONE_COLORS.length];
    const div = document.createElement('div');
    div.className = 'tarifa-row';
    div.dataset.id = 'new';
    div.innerHTML = `
        <span class="tf-row-dot" style="background:${color}"></span>
        <div class="tf-km-wrap">
            <input type="number" class="t-desde" value="0" min="0" step="0.5" onchange="actualizarMapa()">
            <span class="tf-km-sep">→</span>
            <input type="number" class="t-hasta" value="5" min="0" step="0.5" placeholder="∞" onchange="actualizarMapa()">
        </div>
        <div class="tf-price-wrap">
            <span class="tf-price-sym">$</span>
            <input type="number" class="t-precio" value="0" min="0" step="100">
        </div>
        <input type="text" class="t-desc" placeholder="Descripción">
        <button class="btn-del" onclick="eliminarFila(this)" title="Eliminar">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    body.appendChild(div);
    div.querySelector('.t-precio').focus();
    actualizarMapa();
    Swal.fire({ toast: true, position: 'top-end', icon: 'success',
        title: 'Nueva zona agregada', showConfirmButton: false, timer: 1400 });
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

    const btns = [document.getElementById('btnGuardarMain'), document.getElementById('btnGuardarCard')];
    const orig = btns[0].innerHTML;
    btns.forEach(b => { b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...'; });

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
    btns.forEach(b => { b.innerHTML = orig; b.disabled = false; });
}
</script>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
