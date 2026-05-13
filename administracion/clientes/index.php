<?php
define('APP_BOOT', true);
header('Cache-Control: no-store, no-cache, must-revalidate');
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/tron.php';
$pageTitle = "Clientes";
include '../../panel/dashboard/layaut/nav.php';
?>
<link rel="stylesheet" href="clientes.css?v=<?=filemtime(__DIR__.'/clientes.css')?>">
<link  rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="cl-wrap">

  <a href="javascript:history.back()" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Volver
  </a>

  <!-- HEADER -->
  <div class="cl-header">
    <div>
      <h1>Clientes</h1>
      <p class="cl-subtitle">Totales, nuevos y recurrentes — <span id="lblPeriodo">cargando…</span></p>
    </div>
    <button class="cl-btn-refresh" onclick="cargar()">Actualizar</button>
  </div>

  <!-- BARRA DE FILTROS -->
  <div class="cl-filter-bar">

    <!-- Dropdown período -->
    <div class="cl-dropdown" id="ddPeriodo">
      <button class="cl-dropdown-btn" id="ddBtn" onclick="toggleDropdown()">
        <span id="ddLabel">Este mes</span>
        <span class="cl-caret"></span>
      </button>
      <div class="cl-dropdown-panel" id="ddPanel">

        <div>
          <div class="cl-dp-label">Período rápido</div>
          <div class="cl-dp-pills">
            <button class="cl-pill active" data-modo="mes_actual"      onclick="setModo(this,'Este mes')">Este mes</button>
            <button class="cl-pill"        data-modo="hoy"             onclick="setModo(this,'Hoy')">Hoy</button>
            <button class="cl-pill"        data-modo="semana_actual"   onclick="setModo(this,'Esta semana')">Esta semana</button>
            <button class="cl-pill"        data-modo="semana_anterior" onclick="setModo(this,'Sem. anterior')">Sem. anterior</button>
            <button class="cl-pill"        data-modo="mes_anterior"    onclick="setModo(this,'Mes anterior')">Mes anterior</button>
            <button class="cl-pill"        data-modo="anio_completo"   onclick="setModo(this,'Año completo')">Año completo</button>
          </div>
        </div>

        <div class="cl-dp-sep"></div>

        <div>
          <div class="cl-dp-label">Ir a mes específico</div>
          <div class="cl-dp-row">
            <select id="selMes" class="cl-select" onchange="setModoMes()">
              <option value="0">Año completo</option>
              <option value="1">Enero</option><option value="2">Febrero</option>
              <option value="3">Marzo</option><option value="4">Abril</option>
              <option value="5">Mayo</option><option value="6">Junio</option>
              <option value="7">Julio</option><option value="8">Agosto</option>
              <option value="9">Septiembre</option><option value="10">Octubre</option>
              <option value="11">Noviembre</option><option value="12">Diciembre</option>
            </select>
            <select id="selAnio" class="cl-select" onchange="setModoMes()">
              <?php for($y=date('Y'); $y>=2023; $y--): ?>
              <option value="<?=$y?>" <?=$y==date('Y')?'selected':''?>><?=$y?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>

        <div class="cl-dp-sep"></div>

        <div>
          <div class="cl-dp-label">Rango de fechas</div>
          <div class="cl-dp-row">
            <input type="date" id="fltDesde" class="cl-date-input" oninput="setRango()">
            <span class="cl-dp-range-sep">→</span>
            <input type="date" id="fltHasta" class="cl-date-input" oninput="setRango()">
            <button id="btnRangoReset" class="cl-pill" onclick="resetRango()" style="display:none">Limpiar</button>
          </div>
        </div>

      </div>
    </div>

    <!-- chip período activo -->
    <span class="cl-period-chip" id="periodoChip">Este mes</span>

  </div>

  <!-- KPI CARDS (5) -->
  <div class="cl-kpis" id="kpiGrid">
    <div class="cl-loading" style="grid-column:1/-1"><div class="cl-spinner"></div> Cargando…</div>
  </div>

  <!-- GRID PRINCIPAL -->
  <div class="cl-grid">

    <!-- TABLA -->
    <div class="cl-card">
      <div class="cl-card-header">
        <span class="cl-card-title">Detalle de clientes</span>
        <span id="badgeTotal" style="font-size:.75rem;color:var(--ink-soft)"></span>
      </div>
      <div class="cl-table-wrap">
        <table id="dtClientes" class="cl-dt-table" style="width:100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Cliente</th>
              <th>Pedidos</th>
              <th>Total gastado</th>
              <th>Última compra</th>
              <th>Tipo</th>
            </tr>
          </thead>
          <tbody id="tbodyClientes"></tbody>
        </table>
      </div>
    </div>

    <!-- PANEL LATERAL -->
    <div class="cl-side">

      <div class="cl-card">
        <div class="cl-card-header"><span class="cl-card-title">Composición</span></div>
        <div class="cl-donut-body">
          <canvas id="donutChart" width="90" height="90" style="max-width:90px;max-height:90px"></canvas>
          <div class="cl-donut-legend" id="donutLegend"></div>
        </div>
      </div>

      <div class="cl-card">
        <div class="cl-card-header"><span class="cl-card-title">Días más activos</span></div>
        <div id="topDiasList"><div class="cl-loading"><div class="cl-spinner"></div></div></div>
      </div>

      <div class="cl-card">
        <div class="cl-card-header"><span class="cl-card-title">Histórico total</span></div>
        <div id="globalList"><div class="cl-loading"><div class="cl-spinner"></div></div></div>
      </div>

    </div>
  </div>
</div>

<script>
/* ── estado ──────────────────────────────── */
let modoActual = 'mes_actual';
let dtTable    = null;
let donutChart = null;

/* ── DataTable init ──────────────────────── */
$(function() {
  dtTable = $('#dtClientes').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
    pageLength: 15,
    lengthMenu: [10, 15, 25, 50],
    order: [[2, 'desc']],
    columnDefs: [
      { targets: 0, orderable: false, className: 'cl-rank', width: '32px' },
      { targets: 5, orderable: false },
    ],
    drawCallback() {
      let i = 0;
      this.api().rows({ search:'applied', order:'applied' }).nodes().each(row => {
        $('td:first', row).text(++i);
      });
    },
    dom: "<'cl-dt-top'lf>t<'cl-dt-bottom'ip>",
  });
  cargar();
});

/* ── dropdown ────────────────────────────── */
function toggleDropdown() {
  const btn   = document.getElementById('ddBtn');
  const panel = document.getElementById('ddPanel');
  const open  = panel.classList.toggle('open');
  btn.classList.toggle('open', open);
}
document.addEventListener('click', e => {
  const dd = document.getElementById('ddPeriodo');
  if (!dd.contains(e.target)) {
    document.getElementById('ddPanel').classList.remove('open');
    document.getElementById('ddBtn').classList.remove('open');
  }
});

function setModo(btn, label) {
  document.querySelectorAll('.cl-pill[data-modo]').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  modoActual = btn.dataset.modo;
  document.getElementById('fltDesde').value = '';
  document.getElementById('fltHasta').value = '';
  document.getElementById('btnRangoReset').style.display = 'none';
  cerrarDropdown();
  setChip(label);
  cargar();
}

function setModoMes() {
  document.querySelectorAll('.cl-pill[data-modo]').forEach(b => b.classList.remove('active'));
  const mes  = parseInt(document.getElementById('selMes').value);
  const anio = document.getElementById('selAnio').value;
  modoActual = mes === 0 ? 'anio_completo' : 'mes_especifico';
  document.getElementById('fltDesde').value = '';
  document.getElementById('fltHasta').value = '';
  document.getElementById('btnRangoReset').style.display = 'none';
  const meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
  setChip(mes === 0 ? `Año ${anio}` : `${meses[mes]} ${anio}`);
  cargar();
}

function setRango() {
  const desde = document.getElementById('fltDesde').value;
  const hasta = document.getElementById('fltHasta').value;
  if (!desde || !hasta) return;
  document.querySelectorAll('.cl-pill[data-modo]').forEach(b => b.classList.remove('active'));
  modoActual = 'rango_custom';
  document.getElementById('btnRangoReset').style.display = '';
  const f1 = new Date(desde+'T12:00:00').toLocaleDateString('es-AR',{day:'2-digit',month:'2-digit'});
  const f2 = new Date(hasta+'T12:00:00').toLocaleDateString('es-AR',{day:'2-digit',month:'2-digit'});
  setChip(`${f1} → ${f2}`);
  cargar();
}

function resetRango() {
  document.getElementById('fltDesde').value = '';
  document.getElementById('fltHasta').value = '';
  document.getElementById('btnRangoReset').style.display = 'none';
  const mesActualBtn = document.querySelector('.cl-pill[data-modo="mes_actual"]');
  setModo(mesActualBtn, 'Este mes');
}

function cerrarDropdown() {
  document.getElementById('ddPanel').classList.remove('open');
  document.getElementById('ddBtn').classList.remove('open');
}

function setChip(label) {
  document.getElementById('ddLabel').textContent   = label;
  document.getElementById('periodoChip').textContent = label;
}

function buildParams() {
  const mes  = document.getElementById('selMes').value;
  const anio = document.getElementById('selAnio').value;
  let p = `modo=${modoActual}&mes=${mes}&anio=${anio}`;
  if (modoActual === 'rango_custom') {
    p += `&desde=${document.getElementById('fltDesde').value}&hasta=${document.getElementById('fltHasta').value}`;
  }
  return p;
}

/* ── carga ───────────────────────────────── */
async function cargar() {
  document.getElementById('kpiGrid').innerHTML    = '<div class="cl-loading" style="grid-column:1/-1"><div class="cl-spinner"></div> Cargando…</div>';
  document.getElementById('topDiasList').innerHTML = '<div class="cl-loading"><div class="cl-spinner"></div></div>';
  document.getElementById('globalList').innerHTML  = '<div class="cl-loading"><div class="cl-spinner"></div></div>';

  try {
    const res  = await fetch(`api/get_clientes.php?${buildParams()}`);
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    document.getElementById('lblPeriodo').textContent = data.periodo;

    renderKpis(data.kpis, data.global);
    renderTabla(data.clientes);
    renderDonut(data.kpis);
    renderTopDias(data.top_dias);
    renderGlobal(data.global);
  } catch(e) {
    document.getElementById('kpiGrid').innerHTML =
      `<div class="cl-loading" style="grid-column:1/-1;color:#c0392b">Error: ${e.message}</div>`;
  }
}

/* ── KPIs ────────────────────────────────── */
function renderKpis(kpis, global) {
  const recPct    = +kpis.total_clientes > 0 ? Math.round(+kpis.clientes_recurrentes / +kpis.total_clientes * 100) : 0;
  const newPct    = +kpis.total_clientes > 0 ? Math.round(+kpis.clientes_nuevos       / +kpis.total_clientes * 100) : 0;
  const avgTicket = +kpis.total_pedidos  > 0 ? Math.round(+kpis.ingresos_periodo      / +kpis.total_pedidos) : 0;

  document.getElementById('kpiGrid').innerHTML = `
    <div class="cl-kpi-card c-blue">
      <div class="cl-kpi-label">Total de clientes</div>
      <div class="cl-kpi-value">${fmt(kpis.total_clientes)}</div>
      <div class="cl-kpi-sub">Clientes únicos que hicieron al menos 1 pedido en este período</div>
    </div>
    <div class="cl-kpi-card c-teal">
      <div class="cl-kpi-label">Clientes nuevos</div>
      <div class="cl-kpi-value">${fmt(kpis.clientes_nuevos)}</div>
      <div class="cl-kpi-sub">Compraron por primera vez en este período (nunca antes habían pedido)</div>
      <span class="cl-kpi-tag teal">${newPct}% del total</span>
    </div>
    <div class="cl-kpi-card c-purple">
      <div class="cl-kpi-label">Clientes recurrentes</div>
      <div class="cl-kpi-value">${fmt(kpis.clientes_recurrentes)}</div>
      <div class="cl-kpi-sub">Hicieron 2 o más pedidos dentro de este mismo período</div>
      <span class="cl-kpi-tag purple">${recPct}% del total</span>
    </div>
    <div class="cl-kpi-card c-orange">
      <div class="cl-kpi-label">Pedidos en el período</div>
      <div class="cl-kpi-value">${fmt(kpis.total_pedidos)}</div>
      <div class="cl-kpi-sub">Total de pedidos realizados (sin contar cancelados)</div>
    </div>
    <div class="cl-kpi-card c-green">
      <div class="cl-kpi-label">Ticket promedio</div>
      <div class="cl-kpi-value">$${fmt(avgTicket)}</div>
      <div class="cl-kpi-sub">Monto promedio por pedido entregado en el período</div>
    </div>
  `;
}

/* ── Tabla ───────────────────────────────── */
function renderTabla(clientes) {
  dtTable.clear();
  if (!clientes.length) { dtTable.draw(); document.getElementById('badgeTotal').textContent = ''; return; }

  const maxPed = Math.max(...clientes.map(c => +c.pedidos));

  clientes.forEach((c, i) => {
    const esRec  = +c.pedidos > 1;
    const badge  = esRec
      ? `<span class="badge-rec">Recurrente</span>`
      : `<span class="badge-new">Nuevo</span>`;
    const pct    = maxPed > 0 ? Math.round(+c.pedidos / maxPed * 100) : 0;
    const fecha  = c.ultima_compra
      ? new Date(c.ultima_compra).toLocaleDateString('es-AR',{day:'2-digit',month:'2-digit',year:'numeric'})
      : '—';

    dtTable.row.add([
      i + 1,
      `<div class="cl-name-main">${esc(c.nombre)}</div>${c.celular ? `<div class="cl-name-sub">${esc(c.celular)}</div>` : ''}`,
      `<span style="font-weight:700">${c.pedidos}</span><div class="cl-bar"><div class="cl-bar-fill" style="width:${pct}%"></div></div>`,
      `<span data-order="${+c.total_gastado}" style="font-weight:600">$${fmt(Math.round(+c.total_gastado))}</span>`,
      fecha,
      badge,
    ]);
  });

  dtTable.draw();
  document.getElementById('badgeTotal').textContent = `${clientes.length} cliente${clientes.length !== 1 ? 's' : ''}`;
}

/* ── Donut ───────────────────────────────── */
function renderDonut(kpis) {
  const rec   = +kpis.clientes_recurrentes;
  const nuevo = Math.max(0, +kpis.total_clientes - rec);
  const ctx   = document.getElementById('donutChart').getContext('2d');
  if (donutChart) donutChart.destroy();

  donutChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Recurrentes','Nuevos'],
      datasets: [{ data: [rec, nuevo], backgroundColor: ['#7c3aed','#0d9488'], borderWidth: 0, hoverOffset: 4 }],
    },
    options: {
      cutout: '68%',
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ` ${c.label}: ${c.parsed}` } } },
    },
  });

  document.getElementById('donutLegend').innerHTML = `
    <div class="cl-donut-item">
      <span class="cl-donut-dot" style="background:#7c3aed"></span>
      <div><div class="cl-donut-val">${rec}</div><div class="cl-donut-lbl">Recurrentes</div></div>
    </div>
    <div class="cl-donut-item">
      <span class="cl-donut-dot" style="background:#0d9488"></span>
      <div><div class="cl-donut-val">${nuevo}</div><div class="cl-donut-lbl">Nuevos</div></div>
    </div>
  `;
}

/* ── Días ────────────────────────────────── */
function renderTopDias(dias) {
  if (!dias.length) { document.getElementById('topDiasList').innerHTML = '<div class="cl-empty">Sin datos</div>'; return; }
  const max  = Math.max(...dias.map(d => +d.clientes_unicos));
  let html = '';
  dias.forEach(d => {
    const fecha = new Date(d.dia+'T12:00:00').toLocaleDateString('es-AR',{weekday:'short',day:'numeric',month:'short'});
    const pct   = max > 0 ? Math.round(+d.clientes_unicos / max * 100) : 0;
    html += `
      <div class="cl-list-item">
        <div>
          <div class="cl-list-name">${fecha}</div>
          <div class="cl-list-meta">${d.pedidos} pedidos</div>
        </div>
        <div>
          <div class="cl-list-val">${d.clientes_unicos} clientes</div>
          <div class="cl-bar" style="width:70px"><div class="cl-bar-fill" style="width:${pct}%"></div></div>
        </div>
      </div>`;
  });
  document.getElementById('topDiasList').innerHTML = html;
}

/* ── Global ──────────────────────────────── */
function renderGlobal(global) {
  const pct = +global.clientes_totales_ever > 0
    ? Math.round(+global.recurrentes_ever / +global.clientes_totales_ever * 100) : 0;
  document.getElementById('globalList').innerHTML = `
    <div class="cl-list-item">
      <div><div class="cl-list-name">Total registrados</div><div class="cl-list-meta">Todos los tiempos</div></div>
      <div class="cl-list-val">${fmt(global.clientes_totales_ever)}</div>
    </div>
    <div class="cl-list-item">
      <div><div class="cl-list-name">Recurrentes históricos</div><div class="cl-list-meta">${pct}% del total</div></div>
      <div class="cl-list-val">${fmt(global.recurrentes_ever)}</div>
    </div>
  `;
}

/* ── utils ───────────────────────────────── */
function fmt(n) { return Number(n).toLocaleString('es-AR'); }
function esc(s) { const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>
