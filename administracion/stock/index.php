<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
$pageTitle = "Stock de Productos";
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$rows = $pdo->query("
    SELECT
        p.idproductos,
        p.nombre,
        p.recetas_idrecetas,
        COALESCE(MAX(CASE WHEN sp.tipo_stock='CONGELADO' THEN sp.stock_actual  END),0) AS stock_congelado,
        COALESCE(MAX(CASE WHEN sp.tipo_stock='HECHO'     THEN sp.stock_actual  END),0) AS stock_hecho,
        COALESCE(MAX(CASE WHEN sp.tipo_stock='CONGELADO' THEN sp.stock_minimo  END),0) AS min_congelado,
        COALESCE(MAX(CASE WHEN sp.tipo_stock='HECHO'     THEN sp.stock_minimo  END),0) AS min_hecho
    FROM productos p
    LEFT JOIN stock_productos sp ON sp.productos_idproductos = p.idproductos
    WHERE p.tipo = 'producto'
    GROUP BY p.idproductos
    ORDER BY p.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$total    = count($rows);
$sinStock = 0; $bajo = 0; $ok = 0;
foreach ($rows as $r) {
    $ca = (float)$r['stock_congelado']; $cm = (float)$r['min_congelado'];
    $ha = (float)$r['stock_hecho'];     $hm = (float)$r['min_hecho'];

    // Misma lógica que las filas: empty si stock=0, bajo si 0<stock<=min, ok si stock>min
    $congEstado = ($ca <= 0) ? 'empty' : (($ca <= $cm) ? 'low' : 'ok');
    $hechEstado = ($ha <= 0) ? 'empty' : (($ha <= $hm) ? 'low' : 'ok');

    $peorEstado = ($congEstado === 'empty' || $hechEstado === 'empty') ? 'empty'
                : (($congEstado === 'low'   || $hechEstado === 'low')   ? 'low'
                : 'ok');

    if ($peorEstado === 'empty') $sinStock++;
    elseif ($peorEstado === 'low') $bajo++;
    else $ok++;
}
?>
<link rel="stylesheet" href="stock.css?v=<?=filemtime(__DIR__.'/stock.css')?>">

<div class="sk-wrap">

  <a href="javascript:history.back()" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Volver
  </a>

  <!-- HEADER -->
  <div class="sk-header">
    <div>
      <h1>Stock de Productos</h1>
      <p class="sk-subtitle">Niveles de stock congelado y horneado — actualizado en tiempo real</p>
    </div>
    <button class="sk-btn primary" onclick="location.reload()">Actualizar</button>
  </div>

  <!-- KPI CARDS -->
  <div class="sk-kpis">
    <div class="sk-kpi c-blue active" data-filter="todos" onclick="filtrar(this)">
      <div class="sk-kpi-label">Total productos</div>
      <div class="sk-kpi-value"><?= $total ?></div>
      <div class="sk-kpi-sub">en el sistema</div>
    </div>
    <div class="sk-kpi c-red" data-filter="sin-stock" onclick="filtrar(this)">
      <div class="sk-kpi-label">Sin stock</div>
      <div class="sk-kpi-value"><?= $sinStock ?></div>
      <div class="sk-kpi-sub">congelado y horneado en 0</div>
    </div>
    <div class="sk-kpi c-amber" data-filter="bajo" onclick="filtrar(this)">
      <div class="sk-kpi-label">Stock bajo</div>
      <div class="sk-kpi-value"><?= $bajo ?></div>
      <div class="sk-kpi-sub">por debajo del mínimo</div>
    </div>
    <div class="sk-kpi c-green" data-filter="ok" onclick="filtrar(this)">
      <div class="sk-kpi-label">Stock OK</div>
      <div class="sk-kpi-value"><?= $ok ?></div>
      <div class="sk-kpi-sub">sobre el mínimo</div>
    </div>
  </div>

  <!-- TOOLBAR -->
  <div class="sk-toolbar">
    <input type="text" class="sk-search" id="skSearch" placeholder="Buscar producto…" oninput="buscar()">
    <div class="sk-filter-group">
      <button class="sk-chip active" data-filter="todos"     onclick="filtrar(this)">Todos</button>
      <button class="sk-chip c-red"  data-filter="sin-stock" onclick="filtrar(this)">Sin stock</button>
      <button class="sk-chip c-amber"data-filter="bajo"      onclick="filtrar(this)">Stock bajo</button>
      <button class="sk-chip c-green"data-filter="ok"        onclick="filtrar(this)">OK</button>
    </div>
    <span class="sk-ml-auto sk-count" id="skCount"></span>
  </div>

  <!-- TABLA -->
  <div class="sk-card">
    <table class="sk-table" id="skTable">
      <thead>
        <tr>
          <th>Producto</th>
          <th>Tipo</th>
          <th>Stock actual</th>
          <th>Mínimo</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody id="skBody">
        <?php
        foreach ($rows as $p):
          $tipos = [
            ['tipo'=>'CONGELADO','stock'=>$p['stock_congelado'],'min'=>$p['min_congelado']],
            ['tipo'=>'HECHO',    'stock'=>$p['stock_hecho'],    'min'=>$p['min_hecho']],
          ];
          foreach ($tipos as $idx => $t):
            $stockNum = (float)$t['stock'];
            $minNum   = (float)$t['min'];
            if ($stockNum == 0)           $estado = 'empty';
            elseif ($stockNum <= $minNum) $estado = 'low';
            else                          $estado = 'ok';

            $ref  = max($minNum * 2, $stockNum, 1);
            $pct  = min(100, round($stockNum / $ref * 100));

            $estadoLabel = $estado === 'empty' ? 'Sin stock' : ($estado === 'low' ? 'Bajo' : 'OK');
            $tipoClass   = $t['tipo'] === 'CONGELADO' ? 'cong' : 'hech';
            $tipoLabel   = $t['tipo'] === 'CONGELADO' ? 'Congelado' : 'Horneado';
            $filterVal   = $estado === 'empty' ? 'sin-stock' : ($estado === 'low' ? 'bajo' : 'ok');
        ?>
        <tr class="sk-row"
            data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>"
            data-filter="<?= $filterVal ?>"
            data-id="<?= $p['idproductos'] ?>"
            data-nombre-display="<?= htmlspecialchars($p['nombre']) ?>"
            data-congelado="<?= $p['stock_congelado'] ?>"
            data-hecho="<?= $p['stock_hecho'] ?>"
            data-mincongelado="<?= $p['min_congelado'] ?>"
            data-minhecho="<?= $p['min_hecho'] ?>"
            data-receta="<?= (int)$p['recetas_idrecetas'] ?>"
            onclick="abrirModal(this)">
          <td class="sk-prod-name"><?= $idx === 0 ? htmlspecialchars($p['nombre']) : '' ?></td>
          <td><span class="sk-tipo <?= $tipoClass ?>"><?= $tipoLabel ?></span></td>
          <td>
            <div class="sk-stock-cell">
              <span class="sk-stock-num"><?= number_format($stockNum, 0) ?></span>
              <div class="sk-stock-bar">
                <div class="sk-stock-bar-fill <?= $estado ?>" style="width:<?= $pct ?>%"></div>
              </div>
              <span class="sk-min-label">mín <?= (int)$minNum ?></span>
            </div>
          </td>
          <td style="color:var(--ink-soft);font-size:.8rem"><?= (int)$minNum ?> uds</td>
          <td><span class="sk-badge <?= $estado ?>"><?= $estadoLabel ?></span></td>
        </tr>
        <?php endforeach; endforeach; ?>
      </tbody>
    </table>
    <div class="sk-empty" id="skEmpty" style="display:none">No hay productos que coincidan.</div>
  </div>

</div>

<!-- MODAL -->
<div id="skOverlay" class="sk-overlay" onclick="if(event.target===this)cerrarModal()">
  <div class="sk-modal">
    <div class="sk-modal-head">
      <span class="sk-modal-title" id="mNombre">—</span>
      <button class="sk-modal-close" onclick="cerrarModal()">✕</button>
    </div>
    <div class="sk-modal-body">
      <div class="sk-modal-col">
        <div class="sk-modal-col-title">Congelado</div>
        <div class="sk-field"><label>Stock actual</label><input type="number" id="mCongelado" min="0" step="0.01"></div>
        <div class="sk-field"><label>Stock mínimo</label><input type="number" id="mMinCong" min="0" step="0.01"></div>
        <hr class="sk-field-sep">
        <div class="sk-field">
          <label>Producir cantidad</label>
          <div class="sk-action-row">
            <div class="sk-field"><input type="number" id="mCantProducir" min="1" step="1" placeholder="0"></div>
            <button class="sk-btn blue" onclick="producirCongelado()">Producir</button>
          </div>
        </div>
      </div>
      <div class="sk-modal-col">
        <div class="sk-modal-col-title">Horneado</div>
        <div class="sk-field"><label>Stock actual</label><input type="number" id="mHecho" min="0" step="0.01"></div>
        <div class="sk-field"><label>Stock mínimo</label><input type="number" id="mMinHecho" min="0" step="0.01"></div>
        <hr class="sk-field-sep">
        <p class="sk-avail-label">Disponible para hornear: <strong id="mDisp">—</strong> uds</p>
        <div class="sk-field">
          <label>Hornear cantidad</label>
          <div class="sk-action-row">
            <div class="sk-field"><input type="number" id="mCantHornear" min="1" step="1" placeholder="0"></div>
            <button class="sk-btn orange" onclick="hornearProducto()">Hornear</button>
          </div>
        </div>
      </div>
    </div>
    <div class="sk-modal-foot">
      <button class="sk-btn ghost" onclick="cerrarModal()">Cancelar</button>
      <button class="sk-btn primary" onclick="guardarStock()">Guardar ajuste</button>
    </div>
  </div>
</div>

<script>
let productoActual = null;
let productoReceta = null;
let filtroActivo   = 'todos';
let busquedaActiva = '';

function filtrar(el) {
  filtroActivo = el.dataset.filter;
  document.querySelectorAll('.sk-chip').forEach(c => c.classList.remove('active'));
  document.querySelectorAll(`.sk-chip[data-filter="${filtroActivo}"]`).forEach(c => c.classList.add('active'));
  document.querySelectorAll('.sk-kpi').forEach(c => c.classList.remove('active'));
  document.querySelectorAll(`.sk-kpi[data-filter="${filtroActivo}"]`).forEach(c => c.classList.add('active'));
  aplicarFiltros();
}

function buscar() {
  busquedaActiva = document.getElementById('skSearch').value.toLowerCase();
  aplicarFiltros();
}

function aplicarFiltros() {
  const filas = document.querySelectorAll('.sk-row');
  let visibles = 0;
  let prev = null;

  filas.forEach(tr => {
    const matchFilter = filtroActivo === 'todos' || tr.dataset.filter === filtroActivo;
    const matchSearch = !busquedaActiva || tr.dataset.nombre.includes(busquedaActiva);
    const visible     = matchFilter && matchSearch;
    tr.style.display  = visible ? '' : 'none';
    if (visible) {
      visibles++;
      const td = tr.querySelector('.sk-prod-name');
      if (tr.dataset.nombre === prev) {
        td.textContent = '';
      } else {
        td.textContent = tr.dataset['nombreDisplay'] || tr.dataset.nombre;
        prev = tr.dataset.nombre;
      }
    }
  });

  document.getElementById('skCount').textContent = `${visibles} fila${visibles !== 1 ? 's' : ''}`;
  document.getElementById('skEmpty').style.display = visibles === 0 ? '' : 'none';
}

function abrirModal(tr) {
  productoActual = tr.dataset.id;
  productoReceta = tr.dataset.receta;
  document.getElementById('mNombre').textContent   = tr.dataset['nombreDisplay'] || tr.dataset.nombre;
  document.getElementById('mCongelado').value      = tr.dataset.congelado;
  document.getElementById('mHecho').value          = tr.dataset.hecho;
  document.getElementById('mMinCong').value        = tr.dataset.mincongelado;
  document.getElementById('mMinHecho').value       = tr.dataset.minhecho;
  document.getElementById('mDisp').textContent     = parseFloat(tr.dataset.congelado).toFixed(0);
  document.getElementById('mCantProducir').value   = '';
  document.getElementById('mCantHornear').value    = '';
  document.getElementById('skOverlay').classList.add('open');
}
function cerrarModal() { document.getElementById('skOverlay').classList.remove('open'); }

function guardarStock() {
  const data = {
    id: productoActual,
    congelado:    document.getElementById('mCongelado').value,
    hecho:        document.getElementById('mHecho').value,
    minCongelado: document.getElementById('mMinCong').value,
    minHecho:     document.getElementById('mMinHecho').value
  };
  Swal.fire({ title:'¿Guardar ajuste?', html:`Congelado: <b>${data.congelado}</b> &nbsp;|&nbsp; Horneado: <b>${data.hecho}</b>`, icon:'question', showCancelButton:true, confirmButtonColor:'#c88e99', cancelButtonColor:'#999', confirmButtonText:'Guardar', cancelButtonText:'Cancelar' })
  .then(r => {
    if (!r.isConfirmed) return;
    fetch('api/update_stock.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) })
    .then(r=>r.json()).then(r => {
      if (r.ok) { cerrarModal(); Swal.fire({title:'Guardado',icon:'success',confirmButtonColor:'#c88e99'}).then(()=>location.reload()); }
      else Swal.fire('Error', r.error||'No se pudo guardar', 'error');
    });
  });
}

function producirCongelado() {
  const cant = parseFloat(document.getElementById('mCantProducir').value);
  if (!cant || cant <= 0) { Swal.fire('Atención','Ingresá una cantidad válida','warning'); return; }
  if (!productoReceta || productoReceta == 0) { Swal.fire('Error','Este producto no tiene receta asociada','error'); return; }
  Swal.fire({ title:'Calculando…', allowOutsideClick:false, didOpen:()=>Swal.showLoading() });
  fetch('../produccion/congelado/api/preview_receta.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({receta:parseInt(productoReceta),cantidad:cant}) })
  .then(r=>r.json()).then(preview => {
    if (preview.status !== 'ok') { Swal.fire('Error', preview.mensaje||'Error', 'error'); return; }
    const filas = preview.ingredientes.map(i => {
      const c = i.faltante ? 'color:#dc2626;font-weight:700' : 'color:#16a34a';
      return `<tr><td style="text-align:left;padding:4px 8px">${i.faltante?'✗':'✓'} ${i.nombre}</td><td style="padding:4px 8px;font-weight:600;${c}">${i.cantidad} ${i.unidad}</td><td style="padding:4px 8px;color:#888;font-size:.8rem">Stock: ${i.stock}</td></tr>`;
    }).join('');
    const puede = preview.puede_producir;
    Swal.fire({ title:`¿Producir ${cant} uds?`, html:`<table style="width:100%;border-collapse:collapse;font-size:.84rem">${filas}</table>${!puede?'<p style="color:#dc2626;font-weight:600;margin-top:10px">Stock insuficiente en algunos ingredientes</p>':''}`, icon:puede?'question':'warning', showCancelButton:true, confirmButtonColor:puede?'#2563eb':'#d97706', cancelButtonColor:'#999', confirmButtonText:'Producir', cancelButtonText:'Cancelar' })
    .then(r => {
      if (!r.isConfirmed) return;
      fetch('../produccion/congelado/api/producir.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({receta:parseInt(productoReceta),producto:parseInt(productoActual),cantidad:cant}) })
      .then(r=>r.json()).then(r => {
        if (r.status==='ok') { cerrarModal(); Swal.fire({title:'Producción realizada',text:r.mensaje,icon:'success',confirmButtonColor:'#c88e99'}).then(()=>location.reload()); }
        else { const det=Array.isArray(r.detalle)?'<br>'+r.detalle.join('<br>'):''; Swal.fire('Error',(r.mensaje||'Error')+det,'error'); }
      });
    });
  }).catch(()=>Swal.fire('Error','No se pudo conectar','error'));
}

function hornearProducto() {
  const cant   = parseFloat(document.getElementById('mCantHornear').value);
  const dispon = parseFloat(document.getElementById('mCongelado').value);
  if (!cant || cant <= 0) { Swal.fire('Atención','Ingresá una cantidad válida','warning'); return; }
  if (cant > dispon) { Swal.fire('Sin stock',`Solo hay ${dispon} uds congeladas`,'warning'); return; }
  Swal.fire({ title:`¿Hornear ${cant} uds?`, html:'El stock congelado se reducirá y el horneado aumentará.', icon:'question', showCancelButton:true, confirmButtonColor:'#ea580c', cancelButtonColor:'#999', confirmButtonText:'Hornear', cancelButtonText:'Cancelar' })
  .then(r => {
    if (!r.isConfirmed) return;
    fetch('../produccion/horneado/api/procesar_horneado.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({producto_id:parseInt(productoActual),cantidad:cant}) })
    .then(r=>r.json()).then(r => {
      if (r.status==='ok') { cerrarModal(); Swal.fire({title:'Horneado realizado',text:r.mensaje,icon:'success',confirmButtonColor:'#c88e99'}).then(()=>location.reload()); }
      else Swal.fire('Error', r.mensaje||'Error', 'error');
    });
  });
}

window.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sk-row').forEach(tr => {
    if (!tr.dataset.nombreDisplay) tr.dataset.nombreDisplay = tr.querySelector('.sk-prod-name').textContent.trim();
  });
  aplicarFiltros();

  // Auto-abrir producto si viene ?open=ID
  const openId = new URLSearchParams(location.search).get('open');
  if (openId) {
    const tr = document.querySelector(`.sk-row[data-id="${openId}"]`);
    if (tr) {
      tr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => abrirModal(tr), 300);
    }
  }
});
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>
