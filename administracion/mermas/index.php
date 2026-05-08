<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
$pageTitle = "Mermas";
include '../../panel/dashboard/layaut/nav.php';
?>
<style>
/* ── Mermas Page ─────────────────────────── */
.mrm-wrap{padding:24px 28px;max-width:1200px}
.mrm-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.mrm-title{font-size:22px;font-weight:700;color:#1e293b}
.mrm-title i{color:#f59e0b;margin-right:8px}
.mrm-subtitle{font-size:13px;color:#64748b;margin-top:2px}

/* KPI Cards */
.mrm-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px}
.mrm-kpi{background:#fff;border-radius:14px;padding:16px 20px;box-shadow:0 1px 4px rgba(0,0,0,.07)}
.mrm-kpi-label{font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px}
.mrm-kpi-val{font-size:24px;font-weight:800;color:#1e293b}
.mrm-kpi-val.red{color:#ef4444}
.mrm-kpi-val.amber{color:#f59e0b}
.mrm-kpi-sub{font-size:11px;color:#94a3b8;margin-top:3px}

/* Panel layout */
.mrm-layout{display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start}
@media(max-width:900px){.mrm-layout{grid-template-columns:1fr}}

/* Formulario */
.mrm-form-card{background:#fff;border-radius:16px;padding:22px;box-shadow:0 1px 6px rgba(0,0,0,.08);position:sticky;top:20px}
.mrm-form-title{font-size:15px;font-weight:700;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.mrm-form-title i{color:#f59e0b}
.mrm-field{margin-bottom:14px}
.mrm-field label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px}
.mrm-field select,.mrm-field input,.mrm-field textarea{
  width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;
  font-size:13px;color:#1e293b;background:#fff;font-family:inherit;outline:none;
  transition:border-color .15s;box-sizing:border-box}
.mrm-field select:focus,.mrm-field input:focus,.mrm-field textarea:focus{border-color:#f59e0b}
.mrm-field textarea{resize:vertical;min-height:72px}
.mrm-row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.btn-registrar{width:100%;padding:12px;background:#f59e0b;color:#fff;border:none;border-radius:10px;
  font-size:14px;font-weight:700;cursor:pointer;transition:.2s;margin-top:4px}
.btn-registrar:hover{background:#d97706}
.btn-registrar:disabled{opacity:.6;cursor:not-allowed}
.mrm-alert{padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:12px;display:none;align-items:center;gap:8px}
.mrm-alert.err{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5}
.mrm-alert.ok{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}

/* Tabla */
.mrm-table-card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 1px 6px rgba(0,0,0,.08)}
.mrm-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:center}
.mrm-filters select,.mrm-filters input{padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;outline:none;font-family:inherit}
.mrm-filters select:focus,.mrm-filters input:focus{border-color:#f59e0b}
.btn-filtrar{padding:8px 16px;background:#1e293b;color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer}

/* Badge motivos */
.badge-motivo{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.bm-vencimiento{background:#fee2e2;color:#b91c1c}
.bm-produccion{background:#fef3c7;color:#92400e}
.bm-accidente{background:#ede9fe;color:#5b21b6}
.bm-control_calidad{background:#dbeafe;color:#1d4ed8}
.bm-otro{background:#f1f5f9;color:#475569}

/* Badge tipo */
.badge-tipo{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600}
.bt-producto{background:#fef9c3;color:#a16207}
.bt-materia_prima{background:#dcfce7;color:#166534}
.bt-topping{background:#ede9fe;color:#6d28d9}

.mrm-table{width:100%;border-collapse:collapse;font-size:13px}
.mrm-table th{padding:10px 12px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.06em;
  text-transform:uppercase;color:#94a3b8;border-bottom:2px solid #f1f5f9}
.mrm-table td{padding:11px 12px;border-bottom:1px solid #f8fafc;color:#1e293b;vertical-align:middle}
.mrm-table tr:last-child td{border-bottom:none}
.mrm-table tr:hover td{background:#fafbfc}
.btn-del{background:none;border:none;color:#ef4444;cursor:pointer;padding:4px 8px;border-radius:6px;font-size:13px;opacity:.7;transition:.15s}
.btn-del:hover{opacity:1;background:#fee2e2}
.mrm-empty{text-align:center;padding:40px;color:#94a3b8;font-size:14px}
</style>

<div class="mrm-wrap">
  <div class="mrm-header">
    <div>
      <div class="mrm-title"><i class="fa-solid fa-triangle-exclamation"></i> Mermas</div>
      <div class="mrm-subtitle">Registro de pérdidas de stock — productos, materias primas y toppings</div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="mrm-kpis" id="mrmKpis">
    <div class="mrm-kpi"><div class="mrm-kpi-label">Total registros</div><div class="mrm-kpi-val" id="kpiTotal">—</div><div class="mrm-kpi-sub">período filtrado</div></div>
    <div class="mrm-kpi"><div class="mrm-kpi-label">Costo estimado</div><div class="mrm-kpi-val red" id="kpiCosto">—</div><div class="mrm-kpi-sub">pérdida económica</div></div>
    <div class="mrm-kpi"><div class="mrm-kpi-label">Productos</div><div class="mrm-kpi-val amber" id="kpiProd">—</div><div class="mrm-kpi-sub">registros</div></div>
    <div class="mrm-kpi"><div class="mrm-kpi-label">Materias primas</div><div class="mrm-kpi-val amber" id="kpiMp">—</div><div class="mrm-kpi-sub">registros</div></div>
  </div>

  <div class="mrm-layout">

    <!-- FORMULARIO -->
    <div class="mrm-form-card">
      <div class="mrm-form-title"><i class="fa-solid fa-plus-circle"></i> Registrar merma</div>

      <div class="mrm-alert err" id="frmAlert"><i class="fa-solid fa-circle-exclamation"></i><span id="frmAlertMsg"></span></div>
      <div class="mrm-alert ok"  id="frmOk"><i class="fa-solid fa-circle-check"></i><span>Merma registrada y stock descontado.</span></div>

      <div class="mrm-field">
        <label>Tipo de ítem</label>
        <select id="fTipo" onchange="cargarRefs()">
          <option value="producto">Producto terminado</option>
          <option value="materia_prima">Materia prima</option>
          <option value="topping">Topping</option>
        </select>
      </div>

      <div class="mrm-field">
        <label>Ítem <span id="lblRef"></span></label>
        <select id="fRef"><option value="">Cargando...</option></select>
      </div>

      <div class="mrm-row2">
        <div class="mrm-field">
          <label>Cantidad perdida</label>
          <input type="number" id="fCantidad" min="0.001" step="0.001" placeholder="0.00">
        </div>
        <div class="mrm-field">
          <label>Unidad</label>
          <input type="text" id="fUnidad" placeholder="kg / u / docena">
        </div>
      </div>

      <div class="mrm-field">
        <label>Motivo</label>
        <select id="fMotivo">
          <option value="vencimiento">Vencimiento / caducidad</option>
          <option value="produccion">Error de producción</option>
          <option value="accidente">Accidente / caída</option>
          <option value="control_calidad">Control de calidad</option>
          <option value="otro">Otro</option>
        </select>
      </div>

      <div class="mrm-field">
        <label>Costo estimado de la pérdida ($)</label>
        <input type="number" id="fCosto" min="0" step="0.01" placeholder="0.00">
      </div>

      <div class="mrm-field">
        <label>Descripción / observaciones</label>
        <textarea id="fDesc" placeholder="Detallá qué pasó, dónde, cómo..."></textarea>
      </div>

      <button class="btn-registrar" id="btnRegistrar" onclick="registrarMerma()">
        <i class="fa-solid fa-floppy-disk"></i> Registrar merma
      </button>
    </div>

    <!-- LISTADO -->
    <div class="mrm-table-card">
      <div class="mrm-filters">
        <input type="date" id="fDesde" value="<?= date('Y-m-01') ?>">
        <input type="date" id="fHasta" value="<?= date('Y-m-d') ?>">
        <select id="fFiltroTipo">
          <option value="">Todos los tipos</option>
          <option value="producto">Productos</option>
          <option value="materia_prima">Materias primas</option>
          <option value="topping">Toppings</option>
        </select>
        <button class="btn-filtrar" onclick="cargarMermas()"><i class="fa-solid fa-filter"></i> Filtrar</button>
      </div>

      <div id="mrmTableWrap">
        <div class="mrm-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>
      </div>
    </div>

  </div>
</div>

<script>
const fmt = n => '$' + parseFloat(n||0).toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2});
const fmtFecha = s => {
  if (!s) return '';
  const d = new Date(s.replace(' ','T'));
  return d.toLocaleDateString('es-AR',{day:'2-digit',month:'2-digit',year:'numeric'})
       + ' ' + d.toLocaleTimeString('es-AR',{hour:'2-digit',minute:'2-digit'});
};

const MOTIVO_LABELS = {
  vencimiento:     'Vencimiento',
  produccion:      'Producción',
  accidente:       'Accidente',
  control_calidad: 'Control calidad',
  otro:            'Otro',
};

async function cargarRefs() {
  const tipo = document.getElementById('fTipo').value;
  const sel  = document.getElementById('fRef');
  sel.innerHTML = '<option value="">Cargando...</option>';

  const res  = await fetch(`api/get_refs.php?tipo=${tipo}`);
  const data = await res.json();
  sel.innerHTML = data.items.length
    ? data.items.map(it => `<option value="${it.id}" data-unidad="${it.unidad||''}">${it.nombre}</option>`).join('')
    : '<option value="">Sin ítems</option>';

  const unidad = sel.selectedOptions[0]?.dataset.unidad || '';
  document.getElementById('fUnidad').value = unidad;
  sel.addEventListener('change', () => {
    document.getElementById('fUnidad').value = sel.selectedOptions[0]?.dataset.unidad || '';
  });
}

async function registrarMerma() {
  const alertEl = document.getElementById('frmAlert');
  const okEl    = document.getElementById('frmOk');
  alertEl.style.display = 'none';
  okEl.style.display    = 'none';

  const tipo     = document.getElementById('fTipo').value;
  const refId    = parseInt(document.getElementById('fRef').value);
  const cantidad = parseFloat(document.getElementById('fCantidad').value);
  const unidad   = document.getElementById('fUnidad').value.trim();
  const motivo   = document.getElementById('fMotivo').value;
  const costo    = parseFloat(document.getElementById('fCosto').value || 0);
  const desc     = document.getElementById('fDesc').value.trim();

  if (!refId)      return showFrmAlert('Seleccioná un ítem');
  if (!cantidad || cantidad <= 0) return showFrmAlert('Ingresá una cantidad válida');

  const btn = document.getElementById('btnRegistrar');
  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';

  try {
    const res  = await fetch('api/registrar.php', {
      method: 'POST', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({tipo, referencia_id: refId, cantidad, unidad, motivo, descripcion: desc, costo_estimado: costo}),
    });
    const data = await res.json();
    if (data.success) {
      okEl.style.display = 'flex';
      document.getElementById('fCantidad').value = '';
      document.getElementById('fCosto').value    = '';
      document.getElementById('fDesc').value     = '';
      setTimeout(() => { okEl.style.display = 'none'; }, 3500);
      cargarMermas();
    } else {
      showFrmAlert(data.error || 'Error al registrar');
    }
  } catch(e) {
    showFrmAlert('Error de conexión');
  }
  btn.disabled  = false;
  btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Registrar merma';
}

function showFrmAlert(msg) {
  document.getElementById('frmAlertMsg').textContent = msg;
  const el = document.getElementById('frmAlert');
  el.style.display = 'flex';
}

async function cargarMermas() {
  const wrap  = document.getElementById('mrmTableWrap');
  const desde = document.getElementById('fDesde').value;
  const hasta = document.getElementById('fHasta').value;
  const tipo  = document.getElementById('fFiltroTipo').value;

  wrap.innerHTML = '<div class="mrm-empty"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';

  const res  = await fetch(`api/get_mermas.php?desde=${desde}&hasta=${hasta}&tipo=${tipo}`);
  const data = await res.json();

  // KPIs
  let totalReg = 0, totalCosto = 0, totProd = 0, totMp = 0;
  (data.totales || []).forEach(t => {
    totalReg   += parseInt(t.cantidad_registros);
    totalCosto += parseFloat(t.costo_total||0);
    if (t.tipo === 'producto')      totProd = parseInt(t.cantidad_registros);
    if (t.tipo === 'materia_prima') totMp   = parseInt(t.cantidad_registros);
  });
  document.getElementById('kpiTotal').textContent = totalReg;
  document.getElementById('kpiCosto').textContent = fmt(totalCosto);
  document.getElementById('kpiProd').textContent  = totProd;
  document.getElementById('kpiMp').textContent    = totMp;

  if (!data.mermas || !data.mermas.length) {
    wrap.innerHTML = '<div class="mrm-empty"><i class="fa-solid fa-box-open"></i><br>Sin registros en el período</div>';
    return;
  }

  wrap.innerHTML = `
    <table class="mrm-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Ítem</th>
          <th>Cantidad</th>
          <th>Motivo</th>
          <th>Costo</th>
          <th>Usuario</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        ${data.mermas.map(m => `
          <tr>
            <td style="white-space:nowrap;color:#64748b;font-size:12px">${fmtFecha(m.fecha)}</td>
            <td><span class="badge-tipo bt-${m.tipo}">${m.tipo.replace('_',' ')}</span></td>
            <td style="font-weight:600">${m.nombre_ref || '—'}</td>
            <td style="font-weight:700">${parseFloat(m.cantidad).toFixed(2)} <span style="color:#94a3b8;font-size:11px">${m.unidad||''}</span></td>
            <td><span class="badge-motivo bm-${m.motivo}">${MOTIVO_LABELS[m.motivo]||m.motivo}</span></td>
            <td style="color:#ef4444;font-weight:700">${m.costo_estimado > 0 ? fmt(m.costo_estimado) : '—'}</td>
            <td style="color:#64748b;font-size:12px">${(m.usuario_nombre||'').trim()||'—'}</td>
            <td>
              <button class="btn-del" onclick="eliminarMerma(${m.id}, this)" title="Revertir y eliminar">
                <i class="fa-solid fa-rotate-left"></i>
              </button>
            </td>
          </tr>
        `).join('')}
      </tbody>
    </table>`;
}

async function eliminarMerma(id, btn) {
  if (!confirm('¿Eliminar esta merma? El stock se revertirá automáticamente.')) return;
  btn.disabled = true;
  const res  = await fetch('api/eliminar.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id}),
  });
  const data = await res.json();
  if (data.success) {
    cargarMermas();
  } else {
    alert(data.error || 'Error al eliminar');
    btn.disabled = false;
  }
}

// Init
cargarRefs();
cargarMermas();
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>
