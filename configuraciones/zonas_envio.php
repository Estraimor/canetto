<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Zonas de Envío";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

// Crear tabla de zonas
$pdo->exec("CREATE TABLE IF NOT EXISTS zonas_envio (
    id          INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200) NULL,
    lat_centro  DECIMAL(10,8) NOT NULL,
    lng_centro  DECIMAL(11,8) NOT NULL,
    radio_km    DECIMAL(5,2)  NOT NULL DEFAULT 5,
    precio      DECIMAL(10,2) NOT NULL DEFAULT 0,
    color       VARCHAR(10)   NOT NULL DEFAULT '#3b82f6',
    prioridad   INT           NOT NULL DEFAULT 50,
    activo      TINYINT(1)    NOT NULL DEFAULT 1,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed zonas Posadas / Garupa / Itambé (solo si está vacío)
$count = (int)$pdo->query("SELECT COUNT(*) FROM zonas_envio")->fetchColumn();
if ($count === 0) {
    $pdo->exec("INSERT INTO zonas_envio (nombre, descripcion, lat_centro, lng_centro, radio_km, precio, color, prioridad) VALUES
        ('Posadas Centro',      'Microcentro y barrios adyacentes',        -27.3667, -55.8964, 3.5,  4500,  '#22c55e', 100),
        ('Posadas Norte',       'Villa Cabello y alrededores',             -27.3350, -55.9080, 3.5,  6500,  '#86efac',  90),
        ('Posadas General',     'Resto del ejido urbano de Posadas',       -27.3700, -55.8950, 8.5,  8000,  '#3b82f6',  50),
        ('Itambé Miní / Prosol',' Barrios Prosol I, II e Itambé Miní',    -27.3950, -55.9400, 4.0,  10500, '#f59e0b',  80),
        ('Itambé Guazú',        'Itambé Guazú y zona oeste',              -27.4100, -55.9580, 5.0,  13000, '#f97316',  75),
        ('Garupa',              'Municipio de Garupa y alrededores',      -27.4820, -55.8370, 6.0,  15000, '#ef4444',  70),
        ('Zona Periférica',     'Áreas alejadas dentro del área de cobertura',-27.3700,-55.8950,20.0, 21000,'#9ca3af',  10)
    ");
}

$zonas = $pdo->query("SELECT * FROM zonas_envio ORDER BY prioridad DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="<?= URL_ASSETS ?>/configuraciones/cfg.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
.zona-layout{display:grid;grid-template-columns:380px 1fr;gap:20px;height:calc(100vh - 180px);min-height:500px}
.zona-panel{display:flex;flex-direction:column;gap:0;background:#fff;border:1px solid var(--border,#e8e7e4);border-radius:14px;overflow:hidden}
.zona-list{flex:1;overflow-y:auto}
.zona-item{display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:1px solid #f5f5f5;cursor:pointer;transition:.12s}
.zona-item:hover{background:#fafaf9}
.zona-item.active{background:#f9edf0;border-left:3px solid #c88e99}
.zona-dot{width:14px;height:14px;border-radius:50%;flex-shrink:0}
.zona-item-name{font-size:13px;font-weight:600;color:#111}
.zona-item-sub{font-size:11px;color:#888;margin-top:1px}
.zona-item-precio{margin-left:auto;font-size:13px;font-weight:700;color:#333;white-space:nowrap}
.zona-form{border-top:1px solid #eee;background:#fafaf9;padding:16px;flex-shrink:0}
.zona-form-title{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#888;margin-bottom:12px}
.zf-row{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px}
.zf-field{display:flex;flex-direction:column;gap:4px;margin-bottom:8px}
.zf-field label{font-size:11px;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.06em}
.zf-field input,.zf-field select{padding:8px 10px;border:1.5px solid #e5e5e2;border-radius:8px;font-size:13px;font-family:inherit;outline:none;transition:border .15s;box-sizing:border-box}
.zf-field input:focus{border-color:#c88e99}
.color-row{display:flex;gap:6px;flex-wrap:wrap}
.color-opt{width:28px;height:28px;border-radius:50%;cursor:pointer;border:2.5px solid transparent;transition:.12s}
.color-opt.sel,.color-opt:hover{border-color:#111;transform:scale(1.15)}
.btn-zona-row{display:flex;gap:8px;margin-top:10px}
.btn-zona-add{display:flex;align-items:center;gap:6px;padding:9px 14px;background:#111;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;transition:.15s}
.btn-zona-add:hover{background:#333}
.btn-zona-del{padding:9px 14px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;transition:.15s}
.btn-zona-del:hover{background:#fecaca}
.btn-zona-save{padding:9px 14px;background:#c88e99;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;transition:.15s;margin-left:auto}
.btn-zona-save:hover{background:#b57a8a}

/* Map */
.zona-map-wrap{position:relative;border-radius:14px;overflow:hidden;border:1px solid var(--border,#e8e7e4)}
#zonaMap{height:100%;width:100%}
.map-instruccion{position:absolute;top:10px;left:50%;transform:translateX(-50%);z-index:500;background:rgba(0,0,0,.7);color:#fff;font-size:12px;font-weight:600;padding:7px 14px;border-radius:20px;white-space:nowrap;pointer-events:none;display:none}
.map-instruccion.show{display:block}
.btn-set-center{position:absolute;bottom:14px;left:14px;z-index:500;display:flex;align-items:center;gap:7px;padding:9px 14px;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;color:#111;box-shadow:0 2px 8px rgba(0,0,0,.15);transition:.15s;font-family:inherit}
.btn-set-center.active{background:#111;color:#fff;border-color:#111}
.leyenda-map{position:absolute;top:10px;right:10px;z-index:500;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;min-width:160px;box-shadow:0 2px 8px rgba(0,0,0,.1)}
.leyenda-map h4{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#888;margin-bottom:8px}
.leyenda-row{display:flex;align-items:center;gap:7px;margin-bottom:5px;font-size:12px;font-weight:500;color:#333;cursor:pointer}
.leyenda-row:hover{color:#c88e99}
.leyenda-dot{width:11px;height:11px;border-radius:50%;flex-shrink:0}
.paraguay-note{position:absolute;bottom:14px;right:14px;z-index:500;background:rgba(239,68,68,.85);color:#fff;font-size:11px;font-weight:700;padding:6px 11px;border-radius:8px;pointer-events:none}
</style>

<div class="cfg-module" style="padding-bottom:20px">
  <div class="cfg-page-header">
    <div class="cfg-page-header__left">
      <a class="cfg-back" href="<?= URL_ASSETS ?>/configuraciones/index.php">
        <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
      </a>
      <div class="cfg-page-title">
        <span>Configuración</span>
        Zonas de envío — Posadas / Garupa / Itambé
      </div>
    </div>
    <button class="btn-primary" onclick="nuevaZona()">
      <i class="fa-solid fa-plus"></i> Nueva zona
    </button>
  </div>

  <div class="zona-layout">

    <!-- Panel izquierdo: lista + formulario -->
    <div class="zona-panel">
      <div class="zona-list" id="zonaList">
        <?php foreach ($zonas as $z): ?>
        <div class="zona-item" data-id="<?= $z['id'] ?>" onclick="seleccionarZona(<?= $z['id'] ?>)">
          <div class="zona-dot" style="background:<?= htmlspecialchars($z['color']) ?>"></div>
          <div>
            <div class="zona-item-name"><?= htmlspecialchars($z['nombre']) ?></div>
            <div class="zona-item-sub"><?= $z['radio_km'] ?> km radio · prior. <?= $z['prioridad'] ?></div>
          </div>
          <div class="zona-item-precio">$<?= number_format($z['precio'], 0, ',', '.') ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="zona-form" id="zonaForm" style="display:none">
        <div class="zona-form-title"><i class="fa-solid fa-pen"></i> Editar zona</div>
        <input type="hidden" id="zId">
        <div class="zf-field">
          <label>Nombre *</label>
          <input type="text" id="zNombre" placeholder="Ej: Posadas Centro">
        </div>
        <div class="zf-field">
          <label>Descripción</label>
          <input type="text" id="zDesc" placeholder="Barrios o referencia">
        </div>
        <div class="zf-row">
          <div class="zf-field">
            <label>Lat. centro</label>
            <input type="number" id="zLat" step="0.0001" placeholder="-27.3667">
          </div>
          <div class="zf-field">
            <label>Lng. centro</label>
            <input type="number" id="zLng" step="0.0001" placeholder="-55.8964">
          </div>
        </div>
        <div class="zf-row">
          <div class="zf-field">
            <label>Radio (km)</label>
            <input type="number" id="zRadio" step="0.5" min="0.5" max="50" placeholder="5">
          </div>
          <div class="zf-field">
            <label>Precio ($)</label>
            <input type="number" id="zPrecio" step="100" min="0" placeholder="7000">
          </div>
        </div>
        <div class="zf-row">
          <div class="zf-field">
            <label>Prioridad <span style="font-weight:400;color:#aaa">(mayor = primero)</span></label>
            <input type="number" id="zPrioridad" step="5" min="0" max="999" placeholder="50">
          </div>
        </div>
        <div class="zf-field">
          <label>Color</label>
          <div class="color-row" id="colorRow">
            <?php foreach (['#22c55e','#86efac','#3b82f6','#60a5fa','#f59e0b','#f97316','#ef4444','#8b5cf6','#ec4899','#9ca3af'] as $c): ?>
            <div class="color-opt" style="background:<?= $c ?>" data-color="<?= $c ?>" onclick="selColor('<?= $c ?>')"></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div id="zAlert" style="display:none;margin-bottom:8px;font-size:12px;color:#dc2626;padding:7px 10px;background:#fee2e2;border-radius:7px"></div>
        <div class="btn-zona-row">
          <button class="btn-zona-add" onclick="guardarZona()"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
          <button class="btn-zona-del" id="btnEliminar" onclick="eliminarZona()"><i class="fa-solid fa-trash"></i> Eliminar</button>
          <button class="btn-zona-save" onclick="cerrarForm()">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- Mapa -->
    <div class="zona-map-wrap">
      <div id="zonaMap"></div>
      <button class="btn-set-center" id="btnSetCenter" onclick="toggleSetCenter()">
        <i class="fa-solid fa-crosshairs"></i> Mover centro en mapa
      </button>
      <div class="map-instruccion" id="mapInstruccion">Clic en el mapa para mover el centro de la zona</div>
      <div class="paraguay-note"><i class="fa-solid fa-ban"></i> Paraguay excluido</div>

      <!-- Leyenda -->
      <div class="leyenda-map">
        <h4><i class="fa-solid fa-layer-group"></i> Zonas</h4>
        <div id="leyendaBody"></div>
        <div style="border-top:1px solid #f0f0f0;margin-top:6px;padding-top:6px;font-size:11px;color:#aaa">
          <i class="fa-solid fa-circle-info"></i> Mayor prioridad = se aplica primero
        </div>
      </div>
    </div>

  </div>
</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const ZONAS_PHP = <?= json_encode($zonas, JSON_UNESCAPED_UNICODE) ?>;
const BASE_URL  = '<?= URL_ASSETS ?>';

/* ══════════════════════════════════
   MAPA
══════════════════════════════════ */
const map = L.map('zonaMap', {
    center: [-27.38, -55.90],
    zoom: 12,
    attributionControl: false,
    zoomControl: true,
}).setView([-27.38, -55.90], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

// Línea del río Paraguay (referencia visual)
const rios = [
    [-27.285, -55.890], [-27.290, -55.875], [-27.295, -55.860],
    [-27.300, -55.850], [-27.308, -55.840], [-27.315, -55.835],
    [-27.320, -55.830], [-27.330, -55.825],
];
L.polyline(rios, { color: '#3b82f6', weight: 3, dashArray: '6 4', opacity: 0.6 }).addTo(map)
 .bindTooltip('Río Paraná — Límite con Paraguay', { permanent: false });

// Zona excluida Paraguay (área al norte del río)
L.polygon([
    [-27.28, -56.10], [-27.10, -56.10], [-27.10, -55.70], [-27.28, -55.70],
    [-27.30, -55.83], [-27.29, -55.88], [-27.286, -55.895],
], { color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.12, weight: 1.5, dashArray: '5 4' })
 .addTo(map).bindTooltip('⛔ Sin cobertura — Paraguay', { permanent: false });

let circulos = {};
let zonaActiva = null;
let modoSetCenter = false;

function renderCirculos() {
    Object.values(circulos).forEach(c => map.removeLayer(c.circle));
    circulos = {};
    const leyenda = document.getElementById('leyendaBody');
    leyenda.innerHTML = '';

    ZONAS_PHP.sort((a, b) => a.prioridad - b.prioridad).forEach(z => {
        if (!z.activo) return;
        const lat = parseFloat(z.lat_centro);
        const lng = parseFloat(z.lng_centro);
        const rad = parseFloat(z.radio_km) * 1000;
        const isActive = zonaActiva && zonaActiva.id == z.id;

        const c = L.circle([lat, lng], {
            radius: rad,
            color: z.color,
            fillColor: z.color,
            fillOpacity: isActive ? 0.35 : 0.15,
            weight: isActive ? 3 : 1.5,
        }).addTo(map);

        c.bindTooltip(`<b>${z.nombre}</b><br>$${Number(z.precio).toLocaleString('es-AR')}<br>${z.radio_km} km radio`, { sticky: true });
        c.on('click', () => seleccionarZona(z.id));
        circulos[z.id] = { circle: c, zona: z };

        // Leyenda
        const row = document.createElement('div');
        row.className = 'leyenda-row';
        row.innerHTML = `<div class="leyenda-dot" style="background:${z.color}"></div><span>${z.nombre}</span>`;
        row.onclick = () => seleccionarZona(z.id);
        leyenda.appendChild(row);
    });
}

// Click en mapa para mover centro de zona
map.on('click', function(e) {
    if (!modoSetCenter || !zonaActiva) return;
    const lat = e.latlng.lat.toFixed(6);
    const lng = e.latlng.lng.toFixed(6);

    // Advertencia Paraguay
    if (parseFloat(lat) > -27.285) {
        alert('⛔ Esa ubicación está en Paraguay o en el río. No es válida para delivery.');
        return;
    }

    document.getElementById('zLat').value = lat;
    document.getElementById('zLng').value = lng;

    // Actualizar círculo en tiempo real
    zonaActiva.lat_centro = lat;
    zonaActiva.lng_centro = lng;
    renderCirculos();
    seleccionarZona(zonaActiva.id);
});

/* ══════════════════════════════════
   SELECCIÓN Y FORMULARIO
══════════════════════════════════ */
function seleccionarZona(id) {
    const z = ZONAS_PHP.find(x => x.id == id);
    if (!z) return;
    zonaActiva = z;

    document.querySelectorAll('.zona-item').forEach(el => el.classList.toggle('active', el.dataset.id == id));

    document.getElementById('zId').value        = z.id;
    document.getElementById('zNombre').value    = z.nombre;
    document.getElementById('zDesc').value      = z.descripcion || '';
    document.getElementById('zLat').value       = z.lat_centro;
    document.getElementById('zLng').value       = z.lng_centro;
    document.getElementById('zRadio').value     = z.radio_km;
    document.getElementById('zPrecio').value    = z.precio;
    document.getElementById('zPrioridad').value = z.prioridad;
    document.getElementById('btnEliminar').style.display = '';
    selColor(z.color);
    document.getElementById('zonaForm').style.display = '';

    // Pan al círculo
    if (circulos[id]) {
        map.flyTo([parseFloat(z.lat_centro), parseFloat(z.lng_centro)], 13, { duration: 0.8 });
    }
    renderCirculos();
}

function cerrarForm() {
    zonaActiva = null;
    document.getElementById('zonaForm').style.display = 'none';
    document.querySelectorAll('.zona-item').forEach(el => el.classList.remove('active'));
    modoSetCenter = false;
    actualizarSetCenterBtn();
    renderCirculos();
}

function nuevaZona() {
    zonaActiva = { id: 'new', lat_centro: -27.3667, lng_centro: -55.8964, radio_km: 5, precio: 7000, color: '#3b82f6', prioridad: 50 };
    document.getElementById('zId').value        = 'new';
    document.getElementById('zNombre').value    = '';
    document.getElementById('zDesc').value      = '';
    document.getElementById('zLat').value       = -27.3667;
    document.getElementById('zLng').value       = -55.8964;
    document.getElementById('zRadio').value     = 5;
    document.getElementById('zPrecio').value    = '';
    document.getElementById('zPrioridad').value = 50;
    document.getElementById('btnEliminar').style.display = 'none';
    selColor('#3b82f6');
    document.getElementById('zonaForm').style.display = '';
    document.getElementById('zNombre').focus();
    document.querySelectorAll('.zona-item').forEach(el => el.classList.remove('active'));
}

let _colorSel = '#3b82f6';
function selColor(c) {
    _colorSel = c;
    document.querySelectorAll('.color-opt').forEach(el => el.classList.toggle('sel', el.dataset.color === c));
}

/* ══════════════════════════════════
   MODO SET CENTER
══════════════════════════════════ */
function toggleSetCenter() {
    if (!zonaActiva) { alert('Seleccioná una zona primero'); return; }
    modoSetCenter = !modoSetCenter;
    actualizarSetCenterBtn();
}
function actualizarSetCenterBtn() {
    const btn = document.getElementById('btnSetCenter');
    const ins = document.getElementById('mapInstruccion');
    btn.classList.toggle('active', modoSetCenter);
    ins.classList.toggle('show', modoSetCenter);
    map.getContainer().style.cursor = modoSetCenter ? 'crosshair' : '';
}

/* ══════════════════════════════════
   UPDATE CÍRCULO EN TIEMPO REAL
══════════════════════════════════ */
['zLat','zLng','zRadio'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        if (!zonaActiva) return;
        zonaActiva.lat_centro = document.getElementById('zLat').value;
        zonaActiva.lng_centro = document.getElementById('zLng').value;
        zonaActiva.radio_km   = document.getElementById('zRadio').value;
        renderCirculos();
    });
});

/* ══════════════════════════════════
   GUARDAR / ELIMINAR
══════════════════════════════════ */
async function guardarZona() {
    const id       = document.getElementById('zId').value;
    const nombre   = document.getElementById('zNombre').value.trim();
    const desc     = document.getElementById('zDesc').value.trim();
    const lat      = parseFloat(document.getElementById('zLat').value);
    const lng      = parseFloat(document.getElementById('zLng').value);
    const radio    = parseFloat(document.getElementById('zRadio').value);
    const precio   = parseFloat(document.getElementById('zPrecio').value);
    const prioridad= parseInt(document.getElementById('zPrioridad').value);

    if (!nombre)         { showZAlert('El nombre es obligatorio'); return; }
    if (!precio || precio <= 0) { showZAlert('El precio debe ser mayor a 0'); return; }
    if (!radio || radio <= 0)   { showZAlert('El radio debe ser mayor a 0'); return; }
    if (lat > -27.285)          { showZAlert('⛔ Las coordenadas caen en Paraguay. Ajustá el centro.'); return; }

    const payload = { id, nombre, descripcion: desc, lat_centro: lat, lng_centro: lng, radio_km: radio, precio, color: _colorSel, prioridad };

    try {
        const res  = await fetch('ajax/guardar_zona_envio.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();
        if (data.ok) {
            location.reload();
        } else {
            showZAlert(data.msg || 'Error al guardar');
        }
    } catch(e) { showZAlert('Error de conexión'); }
}

async function eliminarZona() {
    const id = document.getElementById('zId').value;
    if (!id || id === 'new') return;
    if (!confirm('¿Eliminar esta zona?')) return;
    try {
        const res  = await fetch('ajax/guardar_zona_envio.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ accion:'eliminar', id }) });
        const data = await res.json();
        if (data.ok) { location.reload(); }
        else showZAlert(data.msg || 'Error al eliminar');
    } catch(e) { showZAlert('Error de conexión'); }
}

function showZAlert(msg) {
    const el = document.getElementById('zAlert');
    el.style.display = 'block';
    el.textContent = msg;
    setTimeout(() => el.style.display = 'none', 4000);
}

/* ══════════════════════════════════
   INIT
══════════════════════════════════ */
renderCirculos();
</script>
