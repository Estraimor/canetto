<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
$pageTitle = "Analítica de Ventas — Canetto";
include '../../panel/dashboard/layaut/nav.php';
?>

<link rel="stylesheet" href="analitica.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="ana-wrap">

  <!-- HEADER -->
  <div class="ana-header">
    <div>
      <h1>📊 Analítica de Ventas</h1>
      <p class="ana-subtitle">Ingresos, costos de producción y beneficios</p>
    </div>
    <div class="ana-controls">
      <select id="rangoSelect" class="ana-select" onchange="cargar()">
        <option value="7">Últimos 7 días</option>
        <option value="30" selected>Últimos 30 días</option>
        <option value="90">Últimos 3 meses</option>
        <option value="365">Último año</option>
      </select>
      <button class="ana-btn-refresh" onclick="cargar()">↺ Actualizar</button>
    </div>
  </div>

  <!-- NOTA EXPLICATIVA -->
  <div class="ana-nota">
    <span>ℹ️</span>
    <div>
      <strong>¿Cómo se calculan los números?</strong>
      Los <strong>ingresos</strong> son el total de ventas con estado <em>Entregado</em>.
      Los <strong>costos</strong> son las compras de materias primas registradas en Proveedores (por período de compra, no de producción).
      El <strong>beneficio estimado</strong> = Ingresos − Costos de compra del período.
      Para un cálculo exacto por producto, asociá el costo a cada receta desde el módulo de Recetas.
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpi-grid">

    <div class="kpi-card">
      <div class="kpi-icon">🗓️</div>
      <div class="kpi-body">
        <div class="kpi-label">Hoy</div>
        <div class="kpi-value" id="k-ventas-hoy">—</div>
        <div class="kpi-sub" id="k-pedidos-hoy">— pedidos</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon">📅</div>
      <div class="kpi-body">
        <div class="kpi-label">Esta semana</div>
        <div class="kpi-value" id="k-ventas-semana">—</div>
        <div class="kpi-sub" id="k-pedidos-semana">— pedidos</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon">📆</div>
      <div class="kpi-body">
        <div class="kpi-label">Este mes</div>
        <div class="kpi-value" id="k-ventas-mes">—</div>
        <div class="kpi-sub" id="k-pedidos-mes">— pedidos</div>
      </div>
    </div>

    <div class="kpi-card kpi-costo">
      <div class="kpi-icon">🛒</div>
      <div class="kpi-body">
        <div class="kpi-label">Inversión en materiales (mes)</div>
        <div class="kpi-value kpi-red" id="k-costo-mes">—</div>
        <div class="kpi-sub">Compras de materias primas</div>
      </div>
    </div>

    <div class="kpi-card kpi-beneficio-card" id="kpi-beneficio-card">
      <div class="kpi-icon" id="k-benef-icon">💰</div>
      <div class="kpi-body">
        <div class="kpi-label">Beneficio estimado (mes)</div>
        <div class="kpi-value" id="k-beneficio-mes">—</div>
        <div class="kpi-sub">Ingresos − Costos del mes</div>
      </div>
    </div>

  </div>

  <!-- GRÁFICOS SEPARADOS -->
  <div class="ana-row-2" style="margin-bottom:1.4rem">

    <div class="ana-section flex-1">
      <div class="ana-section-header">
        <h2>📈 Ingresos por día</h2>
        <span class="chart-note">Solo ventas entregadas</span>
      </div>
      <div class="chart-wrap">
        <canvas id="chartIngresos"></canvas>
      </div>
    </div>

    <div class="ana-section" style="width:320px;flex-shrink:0">
      <div class="ana-section-header">
        <h2>🧮 Inversión vs Ingreso</h2>
        <span class="chart-note">Acumulado del período</span>
      </div>
      <div class="chart-wrap" style="height:220px">
        <canvas id="chartResumen"></canvas>
      </div>
      <div class="resumen-totales" id="resumen-totales"></div>
    </div>

  </div>

  <!-- SEGUNDA FILA: top productos + pagos -->
  <div class="ana-row-2">

    <div class="ana-section flex-1">
      <div class="ana-section-header">
        <h2>🏆 Productos más vendidos</h2>
        <span class="chart-note">Por unidades vendidas</span>
      </div>
      <table class="ana-table">
        <thead><tr><th>#</th><th>Producto</th><th>Unidades</th><th>Ingresos</th></tr></thead>
        <tbody id="tb-productos">
          <tr><td colspan="4" class="ana-loading">⏳ Cargando...</td></tr>
        </tbody>
      </table>
    </div>

    <div class="ana-section w-280">
      <div class="ana-section-header"><h2>💳 Método de pago</h2></div>
      <div class="chart-wrap-sm">
        <canvas id="chartPago"></canvas>
      </div>
      <div id="pago-lista" class="pago-lista"></div>
    </div>

  </div>

  <!-- COSTOS MATERIAS PRIMAS -->
  <div class="ana-section">
    <div class="ana-section-header">
      <h2>🛒 Inversión por materia prima <span style="font-size:.75rem;font-weight:400;color:var(--ink-soft)">(total acumulado)</span></h2>
      <span id="costo-total-badge" class="costo-badge">—</span>
    </div>
    <div class="mp-bars" id="mp-bars">
      <div class="ana-loading">⏳ Cargando...</div>
    </div>
  </div>

  <!-- ORIGEN -->
  <div class="ana-section" id="origen-section" style="display:none">
    <div class="ana-section-header"><h2>🌐 Canal de venta</h2></div>
    <div id="origen-content" class="origen-grid"></div>
  </div>

</div>

<div id="toast" class="toast" style="display:none"></div>

<script>
const fmt  = n => '$' + parseFloat(n||0).toLocaleString('es-AR', {minimumFractionDigits:0, maximumFractionDigits:0});
const fmtK = n => {
  const v = Math.abs(parseFloat(n||0));
  if (v >= 1_000_000) return (parseFloat(n)/1_000_000).toFixed(1).replace('.',',') + 'M';
  if (v >= 1_000)     return (parseFloat(n)/1_000).toFixed(0) + 'k';
  return fmt(n);
};

let chartIngresos = null, chartResumen = null, chartPago = null;

function showToast(msg, type='') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'toast' + (type?' '+type:'');
  t.style.display = 'block';
  clearTimeout(t._t); t._t = setTimeout(() => t.style.display='none', 3500);
}

async function cargar() {
  const rango = document.getElementById('rangoSelect').value;
  document.getElementById('tb-productos').innerHTML = '<tr><td colspan="4" class="ana-loading">⏳ Cargando...</td></tr>';
  document.getElementById('mp-bars').innerHTML = '<div class="ana-loading">⏳ Cargando...</div>';

  try {
    const res  = await fetch('api/get_analitica.php?rango=' + rango);
    const data = await res.json();
    if (data.error) { showToast('Error: ' + data.error, 'error'); return; }
    renderKPIs(data.kpis, data.costos);
    renderChartIngresos(data.labels, data.ingresos);
    renderChartResumen(data.kpis, data.costos, rango);
    renderTopProductos(data.top_productos);
    renderPago(data.por_pago);
    renderCostosMP(data.costos_mp, data.costos);
    renderOrigen(data.por_origen);
  } catch(e) {
    showToast('Error de conexión', 'error');
    console.error(e);
  }
}

function renderKPIs(k, c) {
  document.getElementById('k-ventas-hoy').textContent     = fmt(k.ventas_hoy);
  document.getElementById('k-pedidos-hoy').textContent    = k.pedidos_hoy + ' pedido' + (k.pedidos_hoy != 1 ? 's' : '');
  document.getElementById('k-ventas-semana').textContent  = fmt(k.ventas_semana);
  document.getElementById('k-pedidos-semana').textContent = k.pedidos_semana + ' pedido' + (k.pedidos_semana != 1 ? 's' : '');
  document.getElementById('k-ventas-mes').textContent     = fmt(k.ventas_mes);
  document.getElementById('k-pedidos-mes').textContent    = k.pedidos_mes + ' pedido' + (k.pedidos_mes != 1 ? 's' : '');
  document.getElementById('k-costo-mes').textContent      = fmt(c.costo_mes);

  const benefMes = parseFloat(k.ventas_mes) - parseFloat(c.costo_mes);
  const el = document.getElementById('k-beneficio-mes');
  el.textContent = fmt(benefMes);
  const card = document.getElementById('kpi-beneficio-card');
  document.getElementById('k-benef-icon').textContent = benefMes >= 0 ? '✅' : '⚠️';
  if (benefMes >= 0) {
    el.style.color = '#1a7a4a';
    card.style.borderLeftColor = '#1a7a4a';
    card.style.background = '#f0faf4';
  } else {
    el.style.color = '#c88e99';
    card.style.borderLeftColor = '#c88e99';
    card.style.background = '#fff8f7';
  }
}

function renderChartIngresos(labels, ingresos) {
  const ctx = document.getElementById('chartIngresos').getContext('2d');
  if (chartIngresos) chartIngresos.destroy();

  const hayDatos = ingresos.some(v => v > 0);
  if (!hayDatos) {
    ctx.canvas.parentElement.innerHTML =
      '<div class="chart-empty">📦 Sin ventas entregadas en el período seleccionado</div>';
    return;
  }

  chartIngresos = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Ingresos',
        data: ingresos,
        backgroundColor: 'rgba(55,138,221,.15)',
        borderColor: '#378ADD',
        borderWidth: 1.5,
        borderRadius: 5,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ' ' + fmt(ctx.parsed.y) } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { maxTicksLimit: 15, font: { size: 10 } } },
        y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { callback: v => fmtK(v), font: { size: 10 } }, beginAtZero: true }
      }
    }
  });
}

function renderChartResumen(k, c, rango) {
  // Usar los valores del período seleccionado
  let ing, cost, label;
  if (rango == 7)   { ing = parseFloat(k.ventas_semana); cost = parseFloat(c.costo_semana); label = 'Esta semana'; }
  else if (rango == 30 || rango == 90) { ing = parseFloat(k.ventas_mes); cost = parseFloat(c.costo_mes); label = 'Este mes'; }
  else              { ing = parseFloat(k.ventas_total);  cost = parseFloat(c.costo_total);  label = 'Total'; }

  const beneficio = ing - cost;

  document.getElementById('resumen-totales').innerHTML = `
    <div class="resumen-fila"><span class="rf-dot" style="background:#378ADD"></span><span>Ingresos</span><strong>${fmt(ing)}</strong></div>
    <div class="resumen-fila"><span class="rf-dot" style="background:#c88e99"></span><span>Costos mat.</span><strong style="color:#c88e99">${fmt(cost)}</strong></div>
    <div class="resumen-fila resumen-total"><span></span><span>Beneficio est.</span><strong style="color:${beneficio>=0?'#1a7a4a':'#c88e99'}">${fmt(beneficio)}</strong></div>
  `;

  const ctx = document.getElementById('chartResumen').getContext('2d');
  if (chartResumen) chartResumen.destroy();
  chartResumen = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Ingresos', 'Costos mat.', 'Beneficio'],
      datasets: [{
        data: [ing, cost, Math.max(0, beneficio)],
        backgroundColor: ['rgba(55,138,221,.7)', 'rgba(200,142,153,.7)', beneficio >= 0 ? 'rgba(26,122,74,.7)' : 'rgba(200,142,153,.3)'],
        borderColor:     ['#378ADD', '#c88e99', beneficio >= 0 ? '#1a7a4a' : '#c88e99'],
        borderWidth: 1.5, borderRadius: 5,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ' ' + fmt(ctx.parsed.y) } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
        y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { callback: v => fmtK(v), font: { size: 10 } }, beginAtZero: true }
      }
    }
  });
}

function renderTopProductos(productos) {
  const tbody = document.getElementById('tb-productos');
  if (!productos.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="ana-loading">Sin ventas entregadas todavía</td></tr>';
    return;
  }
  const maxU = Math.max(...productos.map(p => parseFloat(p.unidades)));
  tbody.innerHTML = productos.map((p, i) => `
    <tr>
      <td><span class="rank-badge ${i===0?'rank-1':i===1?'rank-2':i===2?'rank-3':''}">${i+1}</span></td>
      <td>
        <div class="prod-nombre">${p.nombre}</div>
        <div class="prod-bar-wrap"><div class="prod-bar" style="width:${Math.round(parseFloat(p.unidades)/maxU*100)}%"></div></div>
      </td>
      <td><strong>${parseInt(p.unidades)}</strong> u.</td>
      <td>${fmt(p.ingresos)}</td>
    </tr>
  `).join('');
}

function renderPago(pagos) {
  const ctx = document.getElementById('chartPago').getContext('2d');
  if (chartPago) chartPago.destroy();
  if (!pagos.length) {
    document.getElementById('pago-lista').innerHTML = '<div class="ana-loading" style="padding:12px">Sin ventas entregadas</div>';
    return;
  }
  const colors = ['#378ADD','#1a7a4a','#e67e22','#8e44ad','#e74c3c','#16a085'];
  chartPago = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: pagos.map(p => p.metodo),
      datasets: [{ data: pagos.map(p => parseFloat(p.total)), backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + fmt(ctx.parsed) } }
      }
    }
  });

  document.getElementById('pago-lista').innerHTML = pagos.map((p, i) => `
    <div class="pago-item">
      <span class="pago-dot" style="background:${colors[i]||'#ccc'}"></span>
      <span class="pago-label">${p.metodo}</span>
      <span class="pago-val">${fmt(p.total)}</span>
      <span class="pago-cnt">${p.cantidad} ped.</span>
    </div>
  `).join('');
}

function renderCostosMP(mp, costos) {
  document.getElementById('costo-total-badge').textContent = 'Total invertido: ' + fmt(costos.costo_total);
  const container = document.getElementById('mp-bars');
  if (!mp.length) {
    container.innerHTML = '<div class="ana-loading">Sin compras de materias primas registradas aún</div>';
    return;
  }
  const max = Math.max(...mp.map(m => parseFloat(m.total_invertido)));
  container.innerHTML = mp.map(m => `
    <div class="mp-row">
      <div class="mp-nombre" title="${m.nombre}">${m.nombre}</div>
      <div class="mp-bar-wrap">
        <div class="mp-bar" style="width:${Math.round(parseFloat(m.total_invertido)/max*100)}%"></div>
      </div>
      <div class="mp-val">${fmt(m.total_invertido)}</div>
    </div>
  `).join('');
}

function renderOrigen(origenes) {
  const sec = document.getElementById('origen-section');
  if (!origenes || !origenes.length) { sec.style.display = 'none'; return; }
  sec.style.display = 'block';
  document.getElementById('origen-content').innerHTML = origenes.map(o => `
    <div class="origen-card">
      <div class="origen-ic">${o.origen === 'tienda' ? '🛍️' : '🖥️'}</div>
      <div class="origen-label">${o.origen === 'tienda' ? 'Tienda online' : 'POS / Local'}</div>
      <div class="origen-val">${fmt(o.total)}</div>
      <div class="origen-cnt">${o.cantidad} pedidos</div>
    </div>
  `).join('');
}

document.addEventListener('DOMContentLoaded', cargar);
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>
