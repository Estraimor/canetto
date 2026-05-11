<?php
define('APP_BOOT', true);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
require_once __DIR__ . '/../../config/conexion.php';
$pageTitle = "Analítica de Ventas";
include '../../panel/dashboard/layaut/nav.php';
?>
<link rel="stylesheet" href="analitica.css?v=<?=filemtime(__DIR__.'/analitica.css')?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="ana-wrap">

  <a href="javascript:history.back()" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Volver
  </a>

  <!-- HEADER -->
  <div class="ana-header">
    <div>
      <h1>Analítica de Ventas</h1>
      <p class="ana-subtitle">Ingresos, costos de producción y beneficios — <span id="lblPeriodo">—</span></p>
    </div>
    <button class="ana-btn-refresh" onclick="cargar()">
      <i class="fa-solid fa-rotate-right"></i> Actualizar
    </button>
  </div>

  <!-- FILTROS DE TIEMPO -->
  <div class="afb-card">

    <!-- Fila 1: pills + mes/año -->
    <div class="afb-row afb-row--top">
      <div class="afb-group">
        <span class="afb-label"><i class="fa-solid fa-bolt"></i> Período</span>
        <div class="afb-pills">
          <button class="afb-pill" data-modo="hoy"             onclick="setModo(this)">Hoy</button>
          <button class="afb-pill" data-modo="semana_actual"   onclick="setModo(this)">Esta semana</button>
          <button class="afb-pill" data-modo="semana_anterior" onclick="setModo(this)">Semana pasada</button>
          <button class="afb-pill active" data-modo="mes_actual" onclick="setModo(this)">Este mes</button>
          <button class="afb-pill" data-modo="mes_anterior"    onclick="irMesAnterior()"><i class="fa-solid fa-arrow-left" style="font-size:.65rem"></i> Mes anterior</button>
        </div>
      </div>
      <div class="afb-group afb-group--right">
        <span class="afb-label"><i class="fa-regular fa-calendar"></i> Ir a mes</span>
        <div class="afb-selects">
          <select id="mesSelect" class="afb-select" onchange="setModoMes()">
            <option value="0">Año completo</option>
            <option value="1">Enero</option><option value="2">Febrero</option>
            <option value="3">Marzo</option><option value="4">Abril</option>
            <option value="5">Mayo</option><option value="6">Junio</option>
            <option value="7">Julio</option><option value="8">Agosto</option>
            <option value="9">Septiembre</option><option value="10">Octubre</option>
            <option value="11">Noviembre</option><option value="12">Diciembre</option>
          </select>
          <select id="anioSelect" class="afb-select afb-select--sm" onchange="onAnioChange()">
            <?php for($y=date('Y'); $y>=2023; $y--): ?>
            <option value="<?=$y?>" <?=$y==date('Y')?'selected':''?>><?=$y?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Fila 2: rango + día exacto -->
    <div class="afb-row afb-row--bottom">
      <div class="afb-group">
        <span class="afb-label"><i class="fa-regular fa-calendar-days"></i> Rango de fechas</span>
        <div class="afb-inline">
          <input type="date" id="fltDesde" class="afb-date" oninput="setModoRango()">
          <span class="afb-arrow">→</span>
          <input type="date" id="fltHasta" class="afb-date" oninput="setModoRango()">
          <button class="afb-clear" id="btnRangoReset" onclick="resetRango()" style="display:none" title="Limpiar"><i class="fa-solid fa-xmark"></i> Limpiar</button>
        </div>
      </div>
      <div class="afb-vsep"></div>
      <div class="afb-group">
        <span class="afb-label"><i class="fa-regular fa-calendar-check"></i> Día exacto</span>
        <div class="afb-inline">
          <input type="date" id="fltDia" class="afb-date" oninput="setModoDia()">
          <button class="afb-clear" id="btnDiaReset" onclick="resetDia()" style="display:none" title="Limpiar"><i class="fa-solid fa-xmark"></i> Limpiar</button>
        </div>
      </div>
    </div>

  </div>

  <!-- KPIs -->
  <div class="kpi-grid">
    <div class="kpi-card kpi-blue">
      <div class="kpi-ico kpi-ico-blue"><i class="fa-solid fa-peso-sign"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Ingresos del período</div>
        <div class="kpi-value" id="k-ventas-periodo">—</div>
        <div class="kpi-sub" id="k-pedidos-periodo">—</div>
      </div>
    </div>
    <div class="kpi-card kpi-purple">
      <div class="kpi-ico kpi-ico-purple"><i class="fa-solid fa-receipt"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Ticket promedio</div>
        <div class="kpi-value" id="k-ticket-prom">—</div>
        <div class="kpi-sub">Por pedido entregado</div>
      </div>
    </div>
    <div class="kpi-card kpi-orange">
      <div class="kpi-ico kpi-ico-orange"><i class="fa-solid fa-cart-shopping"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Inversión materiales</div>
        <div class="kpi-value kpi-red" id="k-costo-periodo">—</div>
        <div class="kpi-sub">Compras del período</div>
      </div>
    </div>
    <div class="kpi-card" id="kpi-beneficio-card">
      <div class="kpi-ico" id="k-benef-ico"><i class="fa-solid fa-scale-balanced"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Beneficio estimado</div>
        <div class="kpi-value" id="k-beneficio">—</div>
        <div class="kpi-sub">Ingresos − Costos</div>
      </div>
    </div>
  </div>

  <!-- GRÁFICOS PRINCIPALES -->
  <div class="ana-row-2" style="margin-bottom:1.4rem">
    <div class="ana-section flex-1">
      <div class="ana-section-header">
        <h2>Ingresos por día</h2>
        <span class="chart-note">Solo ventas entregadas — clic en barra para ver detalle</span>
      </div>
      <div class="chart-wrap" id="chartIngresosWrap">
        <canvas id="chartIngresos"></canvas>
      </div>
    </div>
    <div class="ana-section" style="width:300px;flex-shrink:0">
      <div class="ana-section-header">
        <h2>Resumen del período</h2>
      </div>
      <div class="chart-wrap" style="height:200px">
        <canvas id="chartResumen"></canvas>
      </div>
      <div class="resumen-totales" id="resumen-totales"></div>
    </div>
  </div>

  <!-- SEGUNDA FILA -->
  <div class="ana-row-2" style="margin-bottom:1.4rem">
    <div class="ana-section flex-1">
      <div class="ana-section-header">
        <h2>Productos más vendidos</h2>
        <span class="chart-note">Clic en fila para ver detalle</span>
      </div>
      <div class="prod-filter-wrap">
        <i class="fa-solid fa-magnifying-glass" style="color:var(--ink-soft);font-size:.8rem"></i>
        <input type="text" id="prodFiltroInput" class="prod-filter-input"
          placeholder="Filtrar por nombre de producto..."
          oninput="filtrarProductos(this.value)">
        <button id="prodFiltroReset" onclick="resetFiltroProducto()" style="display:none">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <table class="ana-table">
        <thead><tr><th>#</th><th>Producto</th><th>Unidades</th><th>Ingresos</th></tr></thead>
        <tbody id="tb-productos">
          <tr><td colspan="4" class="ana-loading">Cargando...</td></tr>
        </tbody>
      </table>
      <div id="prod-filter-note" style="display:none;font-size:.75rem;color:var(--ink-soft);padding:8px 0 0;text-align:center"></div>
    </div>
    <div class="ana-section w-280">
      <div class="ana-section-header"><h2>Método de pago</h2></div>
      <div class="chart-wrap-sm"><canvas id="chartPago"></canvas></div>
      <div id="pago-lista" class="pago-lista"></div>
    </div>
  </div>

  <!-- INVERSIÓN EN MATERIALES -->
  <div class="ana-section" id="sec-materiales" style="margin-bottom:1.4rem">
    <div class="ana-section-header">
      <h2>Inversión en materiales</h2>
      <span id="costo-total-badge" class="costo-badge">—</span>
    </div>
    <div class="mp-bars" id="mp-bars">
      <div class="ana-loading">Cargando...</div>
    </div>
  </div>

  <!-- HEATMAP PEDIDOS POR DÍA Y HORA -->
  <div class="ana-section" id="sec-heatmap" style="margin-bottom:1.4rem">
    <div class="ana-section-header">
      <h2>Concentración de pedidos por día y hora</h2>
      <span class="chart-note">Pedidos entregados del período</span>
    </div>
    <div class="sf-wrap" id="sf-heatmap" style="display:none">
      <span class="sf-label">SUB-PERÍODO:</span>
      <div class="sf-pills" id="sfp-heatmap"></div>
      <button class="sf-reset" id="sfr-heatmap" onclick="resetSubfiltroHM()" style="display:none">← Todo el período</button>
    </div>
    <div class="hm-wrap" id="hmWrap">
      <div class="ana-loading">Cargando...</div>
    </div>
  </div>

  <!-- DEBE Y HABER -->
  <div class="ana-section" id="sec-debe-haber">
    <div class="ana-section-header">
      <h2>Debe y Haber</h2>
      <span class="chart-note">Movimientos del período ordenados por fecha</span>
    </div>
    <div class="dh-totales" id="dh-totales-top"></div>
    <div class="dh-table-wrap">
      <table class="ana-table dh-table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Concepto</th>
            <th>Detalle</th>
            <th class="text-right col-debe">Debe (Costo)</th>
            <th class="text-right col-haber">Haber (Ingreso)</th>
            <th class="text-right">Saldo acum.</th>
          </tr>
        </thead>
        <tbody id="tb-debe-haber">
          <tr><td colspan="6" class="ana-loading">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- MODAL DETALLE (para gráficos y tablas) -->
<div class="ana-modal-overlay" id="anaModal" onclick="if(event.target===this)cerrarModal()">
  <div class="ana-modal">
    <div class="ana-modal-header">
      <h3 id="anaModalTitle">Detalle</h3>
      <button onclick="cerrarModal()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="ana-modal-body" id="anaModalBody"></div>
  </div>
</div>

<script>
const fmt  = n => '$' + parseFloat(n||0).toLocaleString('es-AR',{minimumFractionDigits:0,maximumFractionDigits:0});
const fmtK = n => {
  const v=Math.abs(parseFloat(n||0));
  if(v>=1_000_000) return (parseFloat(n)/1_000_000).toFixed(1).replace('.',',')+' M';
  if(v>=1_000)     return (parseFloat(n)/1_000).toFixed(0)+'k';
  return fmt(n);
};
const MESES = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

let chartIngresos=null, chartResumen=null, chartPago=null;
let _modo='mes_actual', _lastData=null;
let _kpiVals = { ventas:0, ticket:0, costo:0, benef:0 };
let _animSeq  = 0;

const fmtSigned = n => {
  const v = Math.round(n);
  return v < 0 ? '-' + fmt(-v) : fmt(v);
};

function kpiAnimate(elId, from, to, dur, fmter, seq) {
  const el = document.getElementById(elId);
  if (!el) return;
  const t0 = performance.now();
  (function tick(now) {
    if (_animSeq !== seq) return;
    const p = Math.min((now - t0) / dur, 1);
    const e = 1 - Math.pow(1 - p, 3); // easeOutCubic
    el.textContent = fmter(from + (to - from) * e);
    if (p < 1) requestAnimationFrame(tick);
  })(t0);
}

const _BAJAR_DUR = 320;

function animarKPIsBajar() {
  const seq = ++_animSeq;
  kpiAnimate('k-ventas-periodo', _kpiVals.ventas, 0, _BAJAR_DUR, fmt,       seq);
  kpiAnimate('k-ticket-prom',    _kpiVals.ticket,  0, _BAJAR_DUR, fmt,       seq);
  kpiAnimate('k-costo-periodo',  _kpiVals.costo,   0, _BAJAR_DUR, fmt,       seq);
  kpiAnimate('k-beneficio',      _kpiVals.benef,   0, _BAJAR_DUR, fmtSigned, seq);
  _kpiVals = { ventas:0, ticket:0, costo:0, benef:0 };
}

function animarKPIsSubir(ing, ticket, costo, benef) {
  // Capturamos el seq SIN incrementar para no cancelar la bajada en curso
  const capturedSeq = _animSeq;
  setTimeout(() => {
    // Si se llamó a cargar() de nuevo mientras esperábamos, descartamos
    if (_animSeq !== capturedSeq) return;
    const seq = ++_animSeq;
    kpiAnimate('k-ventas-periodo', 0, ing,    700, fmt,       seq);
    kpiAnimate('k-ticket-prom',    0, ticket,  700, fmt,       seq);
    kpiAnimate('k-costo-periodo',  0, costo,   700, fmt,       seq);
    kpiAnimate('k-beneficio',      0, benef,   700, fmtSigned, seq);
    _kpiVals = { ventas:ing, ticket, costo, benef };
  }, _BAJAR_DUR);
}

// ── FILTROS ───────────────────────────────────────────────────────────
function desactivarPills(){ document.querySelectorAll('.afb-pill').forEach(b=>b.classList.remove('active')); }

function setModo(btn){
  desactivarPills();
  btn.classList.add('active');
  _modo = btn.dataset.modo;
  document.getElementById('mesSelect').value = '0';
  limpiarRangoUI();
  cargar();
}

function irMesAnterior(){
  const now = new Date();
  let m = now.getMonth();     // getMonth() es 0-based → el valor coincide con el mes anterior en 1-based
  let y = now.getFullYear();
  if (m === 0) { m = 12; y--; } // Enero → ir a Diciembre del año anterior
  desactivarPills();
  limpiarRangoUI();
  document.getElementById('mesSelect').value  = m;
  document.getElementById('anioSelect').value = y;
  _modo = 'mes_especifico';
  cargar();
}

function setModoMes(){
  const mes  = document.getElementById('mesSelect').value;
  const anio = document.getElementById('anioSelect').value;
  desactivarPills();
  limpiarRangoUI();
  if (!mes || mes === '0') {
    _modo = 'anio_completo';
  } else {
    _modo = 'mes_especifico';
  }
  cargar();
}

function onAnioChange(){
  // Si hay mes seleccionado → recargar con ese mes/año
  // Si no hay → recargar año completo
  const mes = document.getElementById('mesSelect').value;
  desactivarPills();
  limpiarRangoUI();
  _modo = (!mes || mes === '0') ? 'anio_completo' : 'mes_especifico';
  cargar();
}

function setModoRango(){
  const desde = document.getElementById('fltDesde').value;
  const hasta = document.getElementById('fltHasta').value;
  if (!desde || !hasta) return;
  desactivarPills();
  document.getElementById('mesSelect').value = '0';
  document.getElementById('fltDia').value = '';
  document.getElementById('btnDiaReset').style.display = 'none';
  document.getElementById('btnRangoReset').style.display = '';
  _modo = 'rango_custom';
  cargar();
}

function setModoDia(){
  const dia = document.getElementById('fltDia').value;
  if (!dia) return;
  desactivarPills();
  document.getElementById('mesSelect').value = '0';
  document.getElementById('fltDesde').value = '';
  document.getElementById('fltHasta').value = '';
  document.getElementById('btnRangoReset').style.display = 'none';
  document.getElementById('btnDiaReset').style.display = '';
  _modo = 'dia_especifico';
  cargar();
}

function resetRango(){
  document.getElementById('fltDesde').value = '';
  document.getElementById('fltHasta').value = '';
  document.getElementById('btnRangoReset').style.display = 'none';
  document.querySelector('.afb-pill[data-modo="mes_actual"]').classList.add('active');
  _modo = 'mes_actual';
  cargar();
}

function resetDia(){
  document.getElementById('fltDia').value = '';
  document.getElementById('btnDiaReset').style.display = 'none';
  document.querySelector('.afb-pill[data-modo="mes_actual"]').classList.add('active');
  _modo = 'mes_actual';
  cargar();
}

function limpiarRangoUI(){
  document.getElementById('fltDesde').value = '';
  document.getElementById('fltHasta').value = '';
  document.getElementById('fltDia').value = '';
  document.getElementById('btnRangoReset').style.display = 'none';
  document.getElementById('btnDiaReset').style.display = 'none';
}

// ── CARGA PRINCIPAL ───────────────────────────────────────────────────
async function cargar(){
  animarKPIsBajar();
  document.getElementById('k-pedidos-periodo').textContent = '—';
  document.querySelectorAll('.ana-section').forEach(s => s.classList.add('ana-dimmed'));

  ['tb-productos','tb-debe-haber'].forEach(id=>{
    document.getElementById(id).innerHTML=`<tr><td colspan="6" class="ana-loading">Cargando...</td></tr>`;
  });
  document.getElementById('mp-bars').innerHTML='<div class="ana-loading">Cargando...</div>';

  let url=`api/get_analitica.php?modo=${_modo}`;
  const anio = document.getElementById('anioSelect').value;
  const mes  = document.getElementById('mesSelect').value;
  if(_modo==='mes_especifico' || _modo==='anio_completo'){
    url+=`&mes=${mes}&anio=${anio}`;
  } else if(_modo==='rango_custom'){
    url+=`&desde=${document.getElementById('fltDesde').value}&hasta=${document.getElementById('fltHasta').value}`;
  } else if(_modo==='dia_especifico'){
    url+=`&dia=${document.getElementById('fltDia').value}`;
  }

  try{
    const res  = await fetch(url);
    const txt  = await res.text();
    let data;
    try{ data=JSON.parse(txt); }
    catch(e){ console.error('Respuesta no-JSON:',txt); showToast('Error en el servidor — revisá los logs','err'); return; }
    if(data.error){ showToast('Error: '+data.error,'err'); return; }

    _lastData=data;
    document.getElementById('lblPeriodo').textContent = data.periodo||'';
    renderKPIs(data.kpis, data.costos);
    renderChartIngresos(data.labels, data.ingresos);
    renderChartResumen(data.kpis, data.costos);
    renderTopProductos(data.top_productos, true);
    renderPago(data.por_pago);
    renderCostosMP(data.costos_mp, data.costos);
    renderDebeHaber(data.debe_haber);
    renderHeatmap(data.heatmap || {});
    actualizarSubfiltrosHM();

    // Restaurar secciones con fade-in
    requestAnimationFrame(() => {
      document.querySelectorAll('.ana-section').forEach(s => s.classList.remove('ana-dimmed'));
    });
  }catch(e){
    console.error(e);
    showToast('Error de conexión: '+e.message,'err');
    document.querySelectorAll('.ana-section').forEach(s => s.classList.remove('ana-dimmed'));
  }
}

// ── KPIs ──────────────────────────────────────────────────────────────
function renderKPIs(k, c) {
  const peds   = parseInt(k.pedidos_periodo)  || 0;
  const ing    = parseFloat(k.ventas_periodo) || 0;
  const costo  = parseFloat(c.costo_periodo)  || 0;
  const ticket = peds > 0 ? ing / peds : 0;
  const benef  = ing - costo;

  // Aplicar colores/icono antes de animar
  const el   = document.getElementById('k-beneficio');
  const card = document.getElementById('kpi-beneficio-card');
  const ico  = document.getElementById('k-benef-ico');
  if (benef >= 0) {
    el.style.color = '#1a7a4a'; card.style.borderTopColor = '#1a7a4a'; card.style.background = '#f0faf4';
    ico.innerHTML  = '<i class="fa-solid fa-arrow-trend-up" style="color:#1a7a4a"></i>';
  } else {
    el.style.color = '#c0392b'; card.style.borderTopColor = '#c0392b'; card.style.background = '#fff8f7';
    ico.innerHTML  = '<i class="fa-solid fa-arrow-trend-down" style="color:#c0392b"></i>';
  }

  animarKPIsSubir(ing, ticket, costo, benef);
  const entregados = parseInt(k.pedidos_entregados) || 0;
  document.getElementById('k-pedidos-periodo').innerHTML =
    '<strong>' + peds + '</strong> pedido' + (peds !== 1 ? 's' : '') +
    ' &nbsp;·&nbsp; <span style="color:#16a34a;font-weight:700">' +
    entregados + ' entregado' + (entregados !== 1 ? 's' : '') + '</span>';
}

// ── GRÁFICO INGRESOS ─────────────────────────────────────────────────
function renderChartIngresos(labels,ingresos){
  const wrap = document.getElementById('chartIngresosWrap');
  if(chartIngresos){ chartIngresos.destroy(); chartIngresos=null; }
  if(!ingresos.some(v=>v>0)){
    wrap.innerHTML='<div class="chart-empty">Sin ventas entregadas en el período seleccionado</div>';
    return;
  }
  // Restaurar canvas si fue reemplazado por el empty state
  if(!document.getElementById('chartIngresos')){
    wrap.innerHTML='<canvas id="chartIngresos"></canvas>';
  }
  const ctx=document.getElementById('chartIngresos').getContext('2d');
  chartIngresos=new Chart(ctx,{
    type:'bar',
    data:{labels,datasets:[{
      label:'Ingresos',data:ingresos,
      backgroundColor:'rgba(55,138,221,.18)',borderColor:'#378ADD',
      borderWidth:1.5,borderRadius:5,
    }]},
    options:{
      responsive:true,maintainAspectRatio:false,
      animation:{ duration:750, easing:'easeOutCubic' },
      onClick:(_,els)=>{ if(els[0]) abrirDetalleDia(labels[els[0].index],ingresos[els[0].index]); },
      plugins:{
        legend:{display:false},
        tooltip:{callbacks:{label:c=>' '+fmt(c.parsed.y)}}
      },
      scales:{
        x:{grid:{display:false},ticks:{maxTicksLimit:18,font:{size:10}}},
        y:{grid:{color:'rgba(0,0,0,.04)'},ticks:{callback:v=>fmtK(v),font:{size:10}},beginAtZero:true}
      }
    }
  });
}

// ── GRÁFICO RESUMEN ──────────────────────────────────────────────────
function renderChartResumen(k,c){
  const ing=parseFloat(k.ventas_periodo), cost=parseFloat(c.costo_periodo);
  const benef=ing-cost;
  const colorBenef = benef>=0?'#1a7a4a':'#c0392b';
  document.getElementById('resumen-totales').innerHTML=`
    <div class="resumen-fila anim-fila" style="animation-delay:.05s"><span class="rf-dot" style="background:#378ADD"></span><span>Ingresos</span><strong>${fmt(ing)}</strong></div>
    <div class="resumen-fila anim-fila" style="animation-delay:.12s"><span class="rf-dot" style="background:#c88e99"></span><span>Costos mat.</span><strong style="color:#c88e99">${fmt(cost)}</strong></div>
    <div class="resumen-fila resumen-total anim-fila" style="animation-delay:.2s"><span></span><span>Beneficio est.</span><strong style="color:${colorBenef}">${fmt(benef)}</strong></div>`;
  if(chartResumen){chartResumen.destroy();}
  const ctx=document.getElementById('chartResumen').getContext('2d');
  chartResumen=new Chart(ctx,{
    type:'bar',
    data:{
      labels:['Ingresos','Costos','Beneficio'],
      datasets:[{
        data:[ing,cost,Math.abs(benef)],
        backgroundColor:['rgba(55,138,221,.7)','rgba(200,142,153,.7)',benef>=0?'rgba(26,122,74,.7)':'rgba(192,57,43,.4)'],
        borderColor:['#378ADD','#c88e99',benef>=0?'#1a7a4a':'#c0392b'],
        borderWidth:1.5,borderRadius:5,
      }]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      animation:{ duration:750, easing:'easeOutCubic' },
      plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' '+fmt(c.parsed.y)}}},
      scales:{
        x:{grid:{display:false},ticks:{font:{size:11,weight:'600'}}},
        y:{grid:{color:'rgba(0,0,0,.04)'},ticks:{callback:v=>fmtK(v),font:{size:10}},beginAtZero:true}
      }
    }
  });
}

// ── TOP PRODUCTOS ─────────────────────────────────────────────────────
function renderTopProductos(prods, guardar=false){
  if(guardar) _todosProductos = prods;
  const tbody=document.getElementById('tb-productos');
  if(!prods.length){
    tbody.innerHTML='<tr><td colspan="4" class="ana-loading">Sin ventas en el período</td></tr>';
    return;
  }
  const maxU=Math.max(...prods.map(p=>parseFloat(p.unidades)));
  tbody.innerHTML=prods.map((p,i)=>`
    <tr class="tr-clickable anim-fila" style="animation-delay:${i*55}ms" onclick="abrirDetalleProducto('${p.nombre.replace(/'/g,"\\'")}')">
      <td><span class="rank-badge ${i===0?'rank-1':i===1?'rank-2':i===2?'rank-3':''}">${i+1}</span></td>
      <td>
        <div class="prod-nombre">${p.nombre}</div>
        <div class="prod-bar-wrap"><div class="prod-bar" style="width:${Math.round(parseFloat(p.unidades)/maxU*100)}%"></div></div>
      </td>
      <td><strong>${parseInt(p.unidades)}</strong> u.</td>
      <td>${fmt(p.ingresos)}</td>
    </tr>`).join('');
}

// ── MÉTODO DE PAGO ────────────────────────────────────────────────────
function renderPago(pagos){
  if(chartPago){chartPago.destroy();chartPago=null;}
  if(!pagos.length){
    document.getElementById('pago-lista').innerHTML='<div class="ana-loading" style="padding:12px">Sin ventas entregadas</div>';
    return;
  }
  const colors=['#378ADD','#1a7a4a','#e67e22','#8e44ad','#e74c3c','#16a085'];
  const ctx=document.getElementById('chartPago').getContext('2d');
  chartPago=new Chart(ctx,{
    type:'doughnut',
    data:{labels:pagos.map(p=>p.metodo),datasets:[{data:pagos.map(p=>parseFloat(p.total)),backgroundColor:colors,borderWidth:2,borderColor:'#fff'}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'65%',
      animation:{ duration:700, easing:'easeOutCubic' },
      plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>' '+c.label+': '+fmt(c.parsed)}}}}
  });
  document.getElementById('pago-lista').innerHTML=pagos.map((p,i)=>`
    <div class="pago-item anim-fila" style="animation-delay:${i*60}ms">
      <span class="pago-dot" style="background:${colors[i]||'#ccc'}"></span>
      <span class="pago-label">${p.metodo}</span>
      <span class="pago-val">${fmt(p.total)}</span>
      <span class="pago-cnt">${p.cantidad} ped.</span>
    </div>`).join('');
}

// ── COSTOS MATERIALES ─────────────────────────────────────────────────
function renderCostosMP(mp,costos){
  document.getElementById('costo-total-badge').textContent='Total período: '+fmt(costos.costo_periodo);
  const cont=document.getElementById('mp-bars');
  if(!mp.length){
    cont.innerHTML='<div class="ana-loading">Sin compras de materiales en el período</div>';
    return;
  }
  const max=Math.max(...mp.map(m=>parseFloat(m.total_invertido)));
  const targets = mp.map(m => Math.round(parseFloat(m.total_invertido)/max*100));
  cont.innerHTML=mp.map((m,i)=>`
    <div class="mp-row tr-clickable anim-fila" style="animation-delay:${i*60}ms" onclick="abrirDetalleMaterial('${m.nombre.replace(/'/g,"\\'")}')">
      <div class="mp-nombre" title="${m.nombre}">${m.nombre}</div>
      <div class="mp-bar-wrap"><div class="mp-bar" data-w="${targets[i]}" style="width:0%"></div></div>
      <div class="mp-val">${fmt(m.total_invertido)}</div>
      <div class="mp-cnt">${m.num_compras} compra${m.num_compras!=1?'s':''}</div>
    </div>`).join('');
  // Animar barras al ancho real después de un frame (CSS transition las lleva de 0 al target)
  requestAnimationFrame(() => requestAnimationFrame(() => {
    cont.querySelectorAll('.mp-bar[data-w]').forEach(b => { b.style.width = b.dataset.w + '%'; });
  }));
}

// ── DEBE Y HABER ─────────────────────────────────────────────────────
function renderDebeHaber(filas){
  const tbody=document.getElementById('tb-debe-haber');
  if(!filas.length){
    tbody.innerHTML='<tr><td colspan="6" class="ana-loading">Sin movimientos en el período</td></tr>';
    document.getElementById('dh-totales-top').innerHTML='';
    return;
  }
  let totalHaber=0,totalDebe=0;
  tbody.innerHTML=filas.map((f,i)=>{
    const esIngreso=f.tipo==='ingreso';
    if(esIngreso) totalHaber+=parseFloat(f.monto);
    else           totalDebe +=parseFloat(f.monto);
    const saldo=parseFloat(f.saldo);
    const fecha=new Date(f.fecha+'T00:00:00').toLocaleDateString('es-AR',{day:'2-digit',month:'2-digit',year:'2-digit'});
    return `<tr class="${esIngreso?'dh-ingreso':'dh-costo'} anim-fila" style="animation-delay:${Math.min(i*40,400)}ms">
      <td class="dh-fecha">${fecha}</td>
      <td class="dh-concepto">${f.concepto}</td>
      <td class="dh-detalle">${f.detalle||'—'}</td>
      <td class="text-right col-debe">${!esIngreso?fmt(f.monto):'—'}</td>
      <td class="text-right col-haber">${esIngreso?fmt(f.monto):'—'}</td>
      <td class="text-right dh-saldo ${saldo>=0?'pos':'neg'}">${fmt(saldo)}</td>
    </tr>`;
  }).join('');

  const balance=totalHaber-totalDebe;
  document.getElementById('dh-totales-top').innerHTML=`
    <div class="dh-resumen">
      <div class="dh-res-item dh-res-haber"><span>Total ingresos</span><strong>${fmt(totalHaber)}</strong></div>
      <div class="dh-res-item dh-res-debe"><span>Total costos</span><strong>${fmt(totalDebe)}</strong></div>
      <div class="dh-res-item dh-res-balance ${balance>=0?'pos':'neg'}"><span>Balance neto</span><strong>${fmt(balance)}</strong></div>
    </div>`;
}


// ── MODALES (gráfico de barras, productos, materiales) ────────────────
function abrirModal(titulo, html){
  document.getElementById('anaModalTitle').textContent = titulo;
  document.getElementById('anaModalBody').innerHTML   = html;
  document.getElementById('anaModal').classList.add('open');
}
function cerrarModal(){
  document.getElementById('anaModal').classList.remove('open');
}

function abrirDetalleDia(label, monto){
  if(!_lastData) return;
  const filas=(_lastData.debe_haber||[]).filter(f=>{
    const fd=new Date(f.fecha+'T00:00:00').toLocaleDateString('es-AR',{day:'2-digit',month:'2-digit',year:'2-digit'});
    return fd===label||(f.fecha&&f.fecha.endsWith('-'+label.split('/').reverse().join('-')));
  });
  const rows=filas.length
    ? filas.map(f=>`<tr class="${f.tipo==='ingreso'?'dh-ingreso':'dh-costo'}">
        <td>${f.concepto}</td><td>${f.detalle||'—'}</td>
        <td class="text-right">${f.tipo!=='ingreso'?fmt(f.monto):'—'}</td>
        <td class="text-right">${f.tipo==='ingreso'?fmt(f.monto):'—'}</td>
      </tr>`).join('')
    : '<tr><td colspan="4" class="ana-loading">Sin movimientos ese día</td></tr>';
  abrirModal(`Movimientos del ${label}`,`
    <table class="ana-table dh-table">
      <thead><tr><th>Concepto</th><th>Detalle</th><th>Debe</th><th>Haber</th></tr></thead>
      <tbody>${rows}</tbody>
    </table>
    <div style="margin-top:12px;font-weight:700;font-size:14px">Ingreso del día: ${fmt(monto)}</div>`);
}

function abrirDetalleProducto(nombre){
  if(!_lastData) return;
  const p=(_lastData.top_productos||[]).find(x=>x.nombre===nombre);
  if(!p) return;
  const prom=p.unidades>0?(parseFloat(p.ingresos)/parseFloat(p.unidades)).toFixed(0):0;
  abrirModal(`Producto: ${nombre}`,`
    <table class="ana-table">
      <tr><td>Unidades vendidas</td><td class="text-right"><strong>${parseInt(p.unidades)}</strong></td></tr>
      <tr><td>Ingresos totales</td><td class="text-right"><strong>${fmt(p.ingresos)}</strong></td></tr>
      <tr><td>Precio promedio</td><td class="text-right">${fmt(prom)}</td></tr>
    </table>`);
}

function abrirDetalleMaterial(nombre){
  if(!_lastData) return;
  const m=(_lastData.costos_mp||[]).find(x=>x.nombre===nombre);
  if(!m) return;
  const prom=m.num_compras>0?(parseFloat(m.total_invertido)/parseFloat(m.num_compras)).toFixed(0):0;
  abrirModal(`Material: ${nombre}`,`
    <table class="ana-table">
      <tr><td>Total invertido</td><td class="text-right"><strong style="color:#c88e99">${fmt(m.total_invertido)}</strong></td></tr>
      <tr><td>Cantidad comprada</td><td class="text-right">${parseFloat(m.total_cantidad).toLocaleString('es-AR')} unidades</td></tr>
      <tr><td>Número de compras</td><td class="text-right">${m.num_compras}</td></tr>
      <tr><td>Costo promedio por compra</td><td class="text-right">${fmt(prom)}</td></tr>
    </table>`);
}

// ── HEATMAP con escala de 5 niveles ──────────────────────────────────
const HM_COLORS = [
  '#e8edf2', // 0 – sin pedidos
  '#bdd7f0', // 1 – muy pocos
  '#74b0e0', // 2 – pocos
  '#2e80c4', // 3 – moderado
  '#0d4e8a', // 4 – muchos
  '#07305a', // 5 – pico máximo
];

function hmNivel(val, max) {
  if (!val) return 0;
  // Absolute thresholds: level only rises with real volume
  if (val >= 8) return 5; // pico
  if (val >= 5) return 4;
  if (val >= 3) return 3;
  if (val >= 2) return 2;
  // For a single order, cap at level 1 unless max is also high
  // relative boost: if this cell is the clear top and max >= 5, allow +1
  if (max >= 5 && val === max) return 2;
  return 1;
}

// startDow: día de inicio del período en formato MySQL WEEKDAY (0=Lun … 6=Dom)
// Si no se pasa, empieza desde Lun (0) — vista completa del período
function renderHeatmap(heatmap, startDow = 0, numDias = 7) {
  const wrap = document.getElementById('hmWrap');
  const DIAS_BASE = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
  const HORAS = Array.from({length:24},(_,i)=>String(i).padStart(2,'0'));

  // Construir el orden de filas comenzando desde startDow, limitado a numDias
  const diasOrden = [];
  for (let i = 0; i < numDias; i++) diasOrden.push((startDow + i) % 7);

  let maxVal = 0;
  for (const d of diasOrden)
    for (let h = 0; h < 24; h++)
      maxVal = Math.max(maxVal, heatmap[d]?.[h] || 0);

  if (!maxVal) {
    wrap.innerHTML = '<div class="ana-loading">Sin pedidos en el período seleccionado</div>';
    return;
  }

  let html = `<div class="hm-grid">`;
  html += `<div class="hm-label-corner"></div>`;
  HORAS.forEach(h => { html += `<div class="hm-hour-lbl">${h}</div>`; });

  for (const d of diasOrden) {
    html += `<div class="hm-day-lbl">${DIAS_BASE[d]}</div>`;
    for (let h = 0; h < 24; h++) {
      const val  = heatmap[d]?.[h] || 0;
      const niv  = hmNivel(val, maxVal);
      const col  = HM_COLORS[niv];
      const tip  = val ? `${DIAS_BASE[d]} ${HORAS[h]}:00 — ${val} pedido${val!==1?'s':''}` : `${DIAS_BASE[d]} ${HORAS[h]}:00 — sin pedidos`;
      const bold = niv >= 4 ? 'border:2px solid rgba(255,255,255,.4);' : '';
      html += `<div class="hm-cell" style="background:${col};${bold}" title="${tip}"></div>`;
    }
  }
  html += `</div>`;

  // Leyenda con los 6 niveles reales
  const legItems = ['Sin pedidos','Muy pocos','Pocos','Moderado','Muchos','Pico'];
  html += `<div class="hm-legend">
    ${HM_COLORS.map((c,i)=>`
      <div class="hm-leg-item">
        <div class="hm-leg-box" style="background:${c};${i>=4?'border:1.5px solid rgba(0,0,0,.1)':''}"></div>
        <span>${legItems[i]}</span>
      </div>`).join('')}
  </div>`;

  wrap.innerHTML = html;
}

// ── FILTRO DE PRODUCTO ────────────────────────────────────────────────
let _todosProductos = [];

function filtrarProductos(q) {
  const reset = document.getElementById('prodFiltroReset');
  const note  = document.getElementById('prod-filter-note');
  reset.style.display = q ? '' : 'none';
  if (!q) { renderTopProductos(_todosProductos); note.style.display='none'; return; }
  const filtrados = _todosProductos.filter(p => p.nombre.toLowerCase().includes(q.toLowerCase()));
  renderTopProductos(filtrados);
  if (filtrados.length < _todosProductos.length) {
    note.style.display = '';
    note.textContent = `Mostrando ${filtrados.length} de ${_todosProductos.length} productos`;
  } else {
    note.style.display = 'none';
  }
}

function resetFiltroProducto() {
  document.getElementById('prodFiltroInput').value = '';
  document.getElementById('prodFiltroReset').style.display = 'none';
  document.getElementById('prod-filter-note').style.display = 'none';
  renderTopProductos(_todosProductos);
}

function scrollTo(selector){
  document.querySelector(selector)?.scrollIntoView({behavior:'smooth',block:'start'});
}

function showToast(msg,type=''){
  // Usa SweetAlert si está disponible, si no alert
  if(window.Swal){
    Swal.fire({icon:type==='err'?'error':'info',title:msg,timer:3000,showConfirmButton:false,toast:true,position:'top-end'});
  } else {
    alert(msg);
  }
}

document.addEventListener('DOMContentLoaded', cargar);

// ── SUBFILTROS HEATMAP ────────────────────────────────────────────────
function _dateStr(d){ return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
function _subDias(inicio,fin){
  const ops=[],cur=new Date(inicio),DN=['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
  while(cur<=fin){
    const ds=_dateStr(cur);
    ops.push({label:DN[cur.getDay()]+' '+String(cur.getDate()).padStart(2,'0')+'/'+String(cur.getMonth()+1).padStart(2,'0'),desde:ds,hasta:ds});
    cur.setDate(cur.getDate()+1);
  }
  return ops;
}
function _subSemanas(inicio,fin){
  const ops=[],f=d=>String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0');
  let cur=new Date(inicio),sem=1;
  while(cur<=fin){
    const sI=new Date(cur),sF=new Date(cur); sF.setDate(sF.getDate()+6);
    if(sF>fin) sF.setTime(fin.getTime());
    ops.push({label:'Sem '+sem+' ('+f(sI)+'–'+f(sF)+')',desde:_dateStr(sI),hasta:_dateStr(sF)});
    cur.setDate(cur.getDate()+7); sem++;
  }
  return ops;
}
function _subMeses(inicio,fin){
  const ops=[],MN=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
  let cur=new Date(inicio.getFullYear(),inicio.getMonth(),1);
  while(cur<=fin){
    const mI=new Date(cur),mF=new Date(cur.getFullYear(),cur.getMonth()+1,0);
    if(mF>fin) mF.setTime(fin.getTime());
    ops.push({label:MN[cur.getMonth()]+' '+cur.getFullYear(),desde:_dateStr(mI),hasta:_dateStr(mF)});
    cur.setMonth(cur.getMonth()+1);
  }
  return ops;
}

function generarSubOpcionesHM(){
  if(!_lastData) return [];
  const inicio=new Date(_lastData.inicio+'T00:00:00');
  const fin   =new Date(_lastData.fin+'T00:00:00');
  const dias  =Math.round((fin-inicio)/86400000)+1;
  if(dias<=1) return [];
  if(_modo==='semana_actual'||_modo==='semana_anterior') return _subDias(inicio,fin);
  if(_modo==='mes_actual'||_modo==='mes_anterior'||_modo==='mes_especifico') return _subSemanas(inicio,fin);
  if(_modo==='anio_completo') return _subMeses(inicio,fin);
  if(dias<=14) return _subDias(inicio,fin);
  if(dias<=90) return _subSemanas(inicio,fin);
  return _subMeses(inicio,fin);
}

function actualizarSubfiltrosHM(){
  const wrap=document.getElementById('sf-heatmap');
  const container=document.getElementById('sfp-heatmap');
  const ops=generarSubOpcionesHM();
  if(!ops.length){ wrap.style.display='none'; return; }
  wrap.style.display='';
  container.innerHTML=ops.map(op=>
    `<button class="sf-pill" data-desde="${op.desde}" data-hasta="${op.hasta}" onclick="aplicarSubfiltroHM(this)">${op.label}</button>`
  ).join('');
  document.getElementById('sfr-heatmap').style.display='none';
}

// JS getDay(): 0=Dom,1=Lun...6=Sáb → MySQL WEEKDAY: 0=Lun...6=Dom
function jsDay2Dow(jsDay){ return (jsDay + 6) % 7; }

async function aplicarSubfiltroHM(btn){
  document.querySelectorAll('#sfp-heatmap .sf-pill').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('sfr-heatmap').style.display='';
  const desde=btn.dataset.desde, hasta=btn.dataset.hasta;
  const params=desde===hasta
    ?`modo=dia_especifico&dia=${desde}`
    :`modo=rango_custom&desde=${desde}&hasta=${hasta}`;
  try{
    const data=await fetch(`api/get_analitica.php?${params}`).then(r=>r.json());
    // Calcular día de inicio y cantidad de días del sub-período
    const dInicio = new Date(desde+'T00:00:00');
    const dFin    = new Date(hasta+'T00:00:00');
    const startDow = jsDay2Dow(dInicio.getDay());
    const numDias  = Math.round((dFin - dInicio) / 86400000) + 1;
    renderHeatmap(data.heatmap||{}, startDow, Math.min(numDias, 7));
  }catch(e){}
}

function resetSubfiltroHM(){
  document.querySelectorAll('#sfp-heatmap .sf-pill').forEach(b=>b.classList.remove('active'));
  document.getElementById('sfr-heatmap').style.display='none';
  if(_lastData) renderHeatmap(_lastData.heatmap||{});
}
</script>
<?php include '../../panel/dashboard/layaut/footer.php'; ?>
