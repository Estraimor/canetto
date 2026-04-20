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
        (0,    3,   3500,  'Zona cercana (0–3 km)'),
        (3,    6,   5500,  'Zona media (3–6 km)'),
        (6,    10,  8000,  'Zona media-lejana (6–10 km)'),
        (10,   15,  11500, 'Zona lejana (10–15 km)'),
        (15,   25,  16000, 'Zona muy lejana (15–25 km)'),
        (25,   999, 22000, 'Zona extrema (+25 km)')
    ");
}

$tarifas = $pdo->query("SELECT * FROM tarifas_envio ORDER BY km_desde ASC")->fetchAll();
?>

<link rel="stylesheet" href="<?= URL_ASSETS ?>/configuraciones/cfg.css">
<style>
.tarifa-wrap { max-width: 720px; margin: 0 auto; }
.tarifa-info { display:flex;align-items:flex-start;gap:12px;background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:12px;padding:14px 16px;margin-bottom:24px;font-size:13px;color:#0c4a6e }
.tarifa-info i { color:#0ea5e9;flex-shrink:0;margin-top:1px }
.tarifa-table { width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--border,#e8e7e4);border-radius:12px;overflow:hidden }
.tarifa-table th { background:#f9f9f8;padding:10px 14px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#888;border-bottom:1px solid #eee }
.tarifa-table td { padding:12px 14px;border-bottom:1px solid #f5f5f5;font-size:14px }
.tarifa-table tr:last-child td { border-bottom:none }
.tarifa-table input[type=number] { width:100%;padding:8px 10px;border:1.5px solid #e5e5e2;border-radius:8px;font-size:14px;font-family:inherit;outline:none;transition:border .15s;box-sizing:border-box }
.tarifa-table input[type=number]:focus { border-color:#c88e99 }
.tarifa-table input[type=text] { width:100%;padding:8px 10px;border:1.5px solid #e5e5e2;border-radius:8px;font-size:13px;font-family:inherit;outline:none;transition:border .15s;box-sizing:border-box }
.tarifa-table input[type=text]:focus { border-color:#c88e99 }
.precio-formatted { font-weight:700;color:#111;font-size:15px }
.btn-row { display:flex;gap:10px;margin-top:20px }
.btn-add { display:flex;align-items:center;gap:7px;padding:10px 18px;background:#f5f4f1;border:1.5px solid #e5e5e2;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;color:#333;transition:.15s;font-family:inherit }
.btn-add:hover { background:#eee }
.btn-del { background:none;border:none;color:#dc2626;cursor:pointer;padding:6px;border-radius:6px;font-size:14px;transition:.15s }
.btn-del:hover { background:#fee2e2 }
.fuel-note { font-size:12px;color:#64748b;margin-top:6px;line-height:1.5 }
</style>

<div class="cfg-module">
  <div class="cfg-page-header">
    <div class="cfg-page-header__left">
      <a class="cfg-back" href="<?= URL_ASSETS ?>/configuraciones/index.php">
        <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
      </a>
      <div class="cfg-page-title">
        <span>Configuración</span>
        Tarifas de envío a domicilio
      </div>
    </div>
    <button class="btn-primary" onclick="guardar()">
      <i class="fa-solid fa-floppy-disk"></i> Guardar tarifas
    </button>
  </div>

  <div class="tarifa-wrap">

    <div class="tarifa-info">
      <i class="fa-solid fa-circle-info"></i>
      <div>
        <strong>Tarifas en pesos argentinos.</strong> El sistema calcula la distancia entre la sucursal y el domicilio del cliente (Haversine) y aplica el tramo correspondiente. Tené en cuenta nafta, desgaste del vehículo y tiempo del repartidor al configurar los precios.
        <div class="fuel-note">Referencia: nafta ~$1.600/L · consumo ~10L/100km · ida+vuelta = ~$320/km solo en combustible.</div>
      </div>
    </div>

    <table class="tarifa-table" id="tblTarifas">
      <thead>
        <tr>
          <th>Desde (km)</th>
          <th>Hasta (km)</th>
          <th>Precio ($)</th>
          <th>Descripción</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="bodyTarifas">
        <?php foreach ($tarifas as $t): ?>
        <tr data-id="<?= $t['id'] ?>">
          <td><input type="number" class="t-desde" value="<?= $t['km_desde'] ?>" min="0" step="0.5"></td>
          <td><input type="number" class="t-hasta" value="<?= $t['km_hasta'] === '999.0' ? '∞' : $t['km_hasta'] ?>" min="0" step="0.5" placeholder="999 = sin límite"></td>
          <td><input type="number" class="t-precio" value="<?= (int)$t['precio'] ?>" min="0" step="100"></td>
          <td><input type="text" class="t-desc" value="<?= htmlspecialchars($t['descripcion'] ?? '') ?>" placeholder="Ej: Zona centro"></td>
          <td><button class="btn-del" onclick="eliminarFila(this)" title="Eliminar"><i class="fa-solid fa-trash"></i></button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="btn-row">
      <button class="btn-add" onclick="agregarFila()">
        <i class="fa-solid fa-plus"></i> Agregar tramo
      </button>
    </div>

    <div id="alertMsg" style="display:none;margin-top:16px"></div>

  </div>
</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
<script>
function agregarFila() {
    const tbody = document.getElementById('bodyTarifas');
    const tr = document.createElement('tr');
    tr.dataset.id = 'new';
    tr.innerHTML = `
        <td><input type="number" class="t-desde" value="0" min="0" step="0.5"></td>
        <td><input type="number" class="t-hasta" value="5" min="0" step="0.5" placeholder="999 = sin límite"></td>
        <td><input type="number" class="t-precio" value="0" min="0" step="100"></td>
        <td><input type="text" class="t-desc" placeholder="Ej: Zona centro"></td>
        <td><button class="btn-del" onclick="eliminarFila(this)" title="Eliminar"><i class="fa-solid fa-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    tr.querySelector('.t-precio').focus();
}

function eliminarFila(btn) {
    if (!confirm('¿Eliminar este tramo?')) return;
    btn.closest('tr').remove();
}

async function guardar() {
    const rows = [...document.querySelectorAll('#bodyTarifas tr')];
    const tarifas = rows.map(tr => ({
        id:          tr.dataset.id,
        km_desde:    parseFloat(tr.querySelector('.t-desde').value) || 0,
        km_hasta:    parseFloat(tr.querySelector('.t-hasta').value) || 999,
        precio:      parseFloat(tr.querySelector('.t-precio').value) || 0,
        descripcion: tr.querySelector('.t-desc').value.trim(),
    }));

    for (const t of tarifas) {
        if (t.precio <= 0) { showAlert('Todos los precios deben ser mayores a 0', 'err'); return; }
        if (t.km_hasta <= t.km_desde) { showAlert('El "Hasta" debe ser mayor que el "Desde"', 'err'); return; }
    }

    const btn = document.querySelector('.cfg-page-header .btn-primary');
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
            showAlert('Tarifas guardadas correctamente', 'ok');
            if (data.reload) setTimeout(() => location.reload(), 1200);
        } else {
            showAlert(data.msg || 'No se pudo guardar', 'err');
        }
    } catch(e) {
        showAlert('Error de conexión', 'err');
    }
    btn.innerHTML = orig;
    btn.disabled = false;
}

function showAlert(msg, type) {
    const el = document.getElementById('alertMsg');
    el.style.display = 'block';
    el.className = type === 'ok' ? 'alert-success' : 'alert';
    el.innerHTML = (type === 'ok' ? '<i class="fa-solid fa-circle-check"></i> ' : '<i class="fa-solid fa-circle-exclamation"></i> ') + msg;
    setTimeout(() => { el.style.display = 'none'; }, 5000);
}
</script>
