<?php
define('APP_BOOT', true);
require_once '../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo      = Conexion::conectar();
$unidades = $pdo->query("SELECT idunidad_medida, nombre, abreviatura FROM unidad_medida ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT idproductos, nombre, tipo FROM productos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$allPkg   = $pdo->query("
    SELECT pk.idpackaging, pk.nombre, pk.unidad_medida_idunidad_medida, um.abreviatura AS unidad_abrev
    FROM packaging pk
    JOIN unidad_medida um ON um.idunidad_medida = pk.unidad_medida_idunidad_medida
    WHERE pk.activo = 1
    ORDER BY pk.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="packaging.css">

<div class="content-body">

  <!-- HEADER -->
  <div class="pkg-header">
    <div>
      <h1>Packaging</h1>
      <p>Cajas, cintas, stickers y todo lo que se usa al empaquetar un pedido. Controlá el stock y asigná materiales por producto.</p>
    </div>
    <button class="btn-pkg" id="btnNuevoPkg">
      <i class="fa-solid fa-plus"></i> Nuevo packaging
    </button>
  </div>

  <!-- STATS -->
  <div class="pkg-stats">
    <div class="pkg-stat">
      <div class="pkg-stat-label">Total tipos</div>
      <div class="pkg-stat-value" id="statTotal">—</div>
    </div>
    <div class="pkg-stat">
      <div class="pkg-stat-label">Sin stock</div>
      <div class="pkg-stat-value critical" id="statSinStock">—</div>
    </div>
    <div class="pkg-stat">
      <div class="pkg-stat-label">Stock bajo</div>
      <div class="pkg-stat-value low" id="statBajo">—</div>
    </div>
    <div class="pkg-stat">
      <div class="pkg-stat-label">OK</div>
      <div class="pkg-stat-value ok" id="statOk">—</div>
    </div>
  </div>

  <!-- TABS -->
  <div class="pkg-tabs">
    <button class="pkg-tab active" data-tab="stock">
      <i class="fa-solid fa-boxes-stacked"></i> Stock de packaging
    </button>
    <button class="pkg-tab" data-tab="productos">
      <i class="fa-solid fa-box-open"></i> Packaging por producto
    </button>
  </div>

  <!-- ================================================================
       TAB: STOCK
  ================================================================ -->
  <div class="pkg-tab-panel active" id="tab-stock">

    <div class="pkg-toolbar">
      <div class="pkg-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="pkgSearch" placeholder="Buscar caja, cinta, sticker...">
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <select id="pkgFiltroEstado" style="height:40px;border:1px solid #e0e0e0;border-radius:8px;padding:0 12px;font-size:13px;font-family:'DM Sans',sans-serif;background:#fff;color:#3a3a3a">
          <option value="all">Todos los estados</option>
          <option value="ok">OK</option>
          <option value="low">Bajo</option>
          <option value="critical">Crítico</option>
          <option value="nostock">Sin stock</option>
        </select>
        <select id="pkgFiltroActivo" style="height:40px;border:1px solid #e0e0e0;border-radius:8px;padding:0 12px;font-size:13px;font-family:'DM Sans',sans-serif;background:#fff;color:#3a3a3a">
          <option value="1">Activos</option>
          <option value="0">Inactivos</option>
          <option value="all">Todos</option>
        </select>
      </div>
    </div>

    <div class="pkg-table-wrap">
      <table id="tablaPkg" class="pkg-table display" style="width:100%">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Stock actual</th>
            <th>Stock mínimo</th>
            <th>Estado</th>
            <th>Activo</th>
            <th style="width:110px;text-align:right">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

  </div><!-- /tab-stock -->

  <!-- ================================================================
       TAB: POR PRODUCTO
  ================================================================ -->
  <div class="pkg-tab-panel" id="tab-productos">

    <!-- Selector de producto -->
    <div class="asig-producto-selector">
      <label><i class="fa-solid fa-cookie-bite" style="margin-right:6px;color:#c88e99"></i>Producto</label>
      <select id="selectProducto">
        <option value="">— Seleccioná un producto —</option>
        <?php foreach($productos as $p): ?>
          <option value="<?= $p['idproductos'] ?>">
            <?= htmlspecialchars($p['nombre']) ?>
            <?= $p['tipo'] === 'box' ? ' 📦' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button class="btn-pkg" id="btnGuardarAsig" disabled>
        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
      </button>
    </div>

    <!-- Estado vacío (sin producto seleccionado) -->
    <div id="asigEmpty" class="asig-empty-product">
      <i class="fa-regular fa-box-open"></i>
      <p>Seleccioná un producto para ver y editar los materiales de empaque</p>
    </div>

    <!-- Panel split (visible al seleccionar producto) -->
    <div id="asigSplitPanel" class="asig-split-panel" style="display:none">

      <div class="asig-split-header">
        <div>
          <div class="asig-split-title" id="asigTitulo">Packaging asignado</div>
          <div class="asig-split-sub" id="asigSub">Editá los materiales de empaque para este producto</div>
        </div>
        <div class="asig-count-badge" id="asigCountBadge">0 asignados</div>
      </div>

      <div class="asig-split-body">

        <!-- Izquierda: asignado -->
        <div class="asig-split-col asig-split-left">
          <div class="asig-split-col-head">
            <i class="fa-solid fa-circle-check" style="color:#1a7a4a"></i>
            Asignado al producto
          </div>
          <div id="asigLeft"></div>
        </div>

        <!-- Derecha: disponible -->
        <div class="asig-split-col asig-split-right">
          <div class="asig-split-col-head">
            <i class="fa-solid fa-circle-plus" style="color:#c88e99"></i>
            Disponible para agregar
          </div>
          <div id="asigRight"></div>
        </div>

      </div><!-- /asig-split-body -->

      <div class="asig-split-foot">
        <div class="asig-foot-info">
          <strong id="asigCount">0</strong> tipo(s) de packaging asignado(s)
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn-pkg btn-pkg-soft" id="btnCancelarAsig">Descartar cambios</button>
          <button class="btn-pkg" id="btnGuardarAsig2">
            <i class="fa-solid fa-floppy-disk"></i> Guardar
          </button>
        </div>
      </div>

    </div><!-- /asig-split-panel -->

  </div><!-- /tab-productos -->

  <!-- ================================================================
       CALCULADORA CANETTO — Caja de 6
  ================================================================ -->
  <div class="pkg-calc-card" id="pkgCalcCard">
    <div class="pkg-calc-header">
      <div>
        <div class="pkg-calc-title"><i class="fa-solid fa-calculator"></i> Calculadora de Cajas — Estándar Canetto</div>
        <div class="pkg-calc-sub">Lógica: pedidos de 4–6 cookies = 1 caja de 6 · más de 6 = se suman cajas de 6</div>
      </div>
    </div>
    <div class="pkg-calc-body">
      <div class="pkg-calc-input-wrap">
        <label>Cantidad de cookies en el pedido</label>
        <div style="display:flex;gap:10px;align-items:center">
          <input type="number" id="calcCantidad" min="1" max="500" placeholder="Ej: 12"
            style="width:120px;height:44px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:18px;font-weight:700;text-align:center;font-family:inherit;outline:none;padding:0 10px"
            oninput="calcCajas()">
          <button onclick="calcCajas()" style="height:44px;padding:0 20px;background:#c88e99;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">
            Calcular
          </button>
        </div>
      </div>
      <div id="calcResultado" class="pkg-calc-resultado" style="display:none">
        <div class="pkg-calc-res-num" id="calcCajasNum">—</div>
        <div class="pkg-calc-res-label" id="calcCajasLabel">caja(s) de 6 necesarias</div>
        <div class="pkg-calc-res-detail" id="calcDetail"></div>
      </div>
    </div>
  </div>

</div><!-- /content-body -->


<!-- ==============================
     MODAL NUEVO / EDITAR
============================== -->
<div class="pkg-modal" id="modalPkg">
  <div class="pkg-modal-backdrop" data-close="true"></div>
  <div class="pkg-modal-dialog">

    <div class="pkg-modal-head">
      <div>
        <div class="pkg-modal-title" id="modalPkgTitle">Nuevo packaging</div>
        <div class="pkg-modal-sub" id="modalPkgSub">Completá los datos del material</div>
      </div>
      <button class="pkg-x" data-close="true"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="pkg-modal-body">
      <form id="formPkg" autocomplete="off">
        <input type="hidden" id="pkg_id">

        <div class="pkg-form-grid">

          <div class="pkg-field full">
            <label>Nombre *</label>
            <input type="text" id="pkg_nombre" required placeholder="Ej: Caja mediana, Cinta rosa, Sticker Canetto">
          </div>

          <div class="pkg-field full">
            <label>Descripción <span style="color:#c88e99;font-size:11px">(medidas en CM recomendadas)</span></label>
            <textarea id="pkg_descripcion" placeholder="Ej: 20cm x 15cm x 8cm — Caja kraft natural, cierre autoadhesivo..."></textarea>
          </div>

          <div class="pkg-field">
            <label>Unidad *</label>
            <select id="pkg_unidad" required>
              <option value="">— Seleccionar —</option>
              <?php foreach($unidades as $u): ?>
                <option value="<?= $u['idunidad_medida'] ?>"><?= $u['nombre'] ?> (<?= $u['abreviatura'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="pkg-field">
            <label>Stock actual</label>
            <input type="number" step="0.01" min="0" id="pkg_stock_actual" placeholder="0">
          </div>

          <div class="pkg-field">
            <label>Stock mínimo</label>
            <input type="number" step="0.01" min="0" id="pkg_stock_minimo" placeholder="0">
          </div>

          <div class="pkg-field" style="justify-content:flex-end;flex-direction:row;align-items:center;gap:10px">
            <label style="margin:0">Activo</label>
            <label class="switch">
              <input type="checkbox" id="pkg_activo" checked>
              <span class="slider"></span>
            </label>
          </div>

        </div>

        <div class="pkg-modal-actions">
          <button type="button" class="btn-pkg btn-pkg-soft" data-close="true">Cancelar</button>
          <button type="submit" class="btn-pkg"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- TOAST -->
<div class="pkg-toast" id="pkgToast">
  <div class="pkg-toast-inner">
    <i class="fa-solid fa-circle-check"></i>
    <span id="pkgToastMsg">Listo</span>
  </div>
</div>


<?php include '../../panel/dashboard/layaut/footer.php'; ?>

<style>
/* ── Calculadora Canetto ── */
.pkg-calc-card {
  margin-top: 28px; background: linear-gradient(135deg,#fff5f7,#fff);
  border: 1.5px solid #f0d0d8; border-radius: 16px;
  overflow: hidden; box-shadow: 0 2px 14px rgba(200,142,153,.08);
}
.pkg-calc-header {
  padding: 16px 20px; border-bottom: 1px solid #f0d0d8;
  background: rgba(200,142,153,.06);
}
.pkg-calc-title {
  font-size: 15px; font-weight: 700; color: #c88e99;
  display: flex; align-items: center; gap: 8px;
}
.pkg-calc-sub { font-size: 13px; color: #888; margin-top: 4px; }
.pkg-calc-body {
  padding: 20px; display: flex; gap: 24px; align-items: center; flex-wrap: wrap;
}
.pkg-calc-input-wrap label {
  display: block; font-size: 12px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .04em; color: #666; margin-bottom: 8px;
}
.pkg-calc-resultado {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  background: #fff; border: 2px solid #c88e99; border-radius: 14px;
  padding: 16px 28px; min-width: 160px; text-align: center; gap: 4px;
}
.pkg-calc-res-num   { font-size: 44px; font-weight: 900; color: #c88e99; line-height: 1; }
.pkg-calc-res-label { font-size: 13px; font-weight: 600; color: #555; }
.pkg-calc-res-detail{ font-size: 12px; color: #999; margin-top: 4px; }
</style>

<script>
function calcCajas(){
  const qty = parseInt(document.getElementById('calcCantidad').value)||0;
  const res = document.getElementById('calcResultado');
  if(qty < 1){ res.style.display='none'; return; }
  const cajas = Math.ceil(qty / 6);
  const sobrante = cajas * 6 - qty;
  document.getElementById('calcCajasNum').textContent   = cajas;
  document.getElementById('calcCajasLabel').textContent = 'caja' + (cajas===1?'':'s') + ' de 6 necesaria' + (cajas===1?'':'s');
  document.getElementById('calcDetail').textContent     = sobrante > 0
    ? `Capacidad: ${cajas*6} u. · Espacio libre: ${sobrante} u.`
    : `Caja(s) completa(s)`;
  res.style.display = 'flex';
}
document.getElementById('calcCantidad')?.addEventListener('keydown', e => { if(e.key==='Enter') calcCajas(); });

/* ============================================================
   CANETTO — PACKAGING
============================================================ */
(function(){
"use strict";

const allPackaging = <?= json_encode($allPkg, JSON_UNESCAPED_UNICODE) ?>;

/* ── Helpers ── */
const $ = q => document.querySelector(q);
const $$ = q => document.querySelectorAll(q);

function toast(msg, ok = true){
  const t = $('#pkgToast');
  $('#pkgToastMsg').textContent = msg;
  t.querySelector('i').style.color = ok ? '#4caf50' : '#c88e99';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2600);
}

/* ── Tabs ── */
$$('.pkg-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    $$('.pkg-tab').forEach(t => t.classList.remove('active'));
    $$('.pkg-tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    $('#tab-' + btn.dataset.tab).classList.add('active');
  });
});

/* ── Modal CRUD ── */
function openModal(m){ m.classList.add('open'); }
function closeModal(m){ m.classList.remove('open'); }

$$('[data-close="true"]').forEach(el => {
  el.addEventListener('click', function(){
    closeModal(this.closest('.pkg-modal'));
  });
});

document.addEventListener('keydown', e => {
  if(e.key === 'Escape') $$('.pkg-modal.open').forEach(closeModal);
});


/* ================================================================
   TAB 1 — STOCK
================================================================ */
const tablaPkg = jQuery('#tablaPkg').DataTable({
  ajax: { url: 'api/listar.php', dataSrc: '' },
  pageLength: 15,
  responsive: true,
  order: [[0, 'asc']],
  language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
  columns: [
    { data: 'nombre', className: 'td-name' },
    {
      data: 'descripcion',
      className: 'td-desc',
      render: d => d || '<span style="color:#d1d5db">—</span>'
    },
    {
      data: 'stock_actual',
      className: 'td-num',
      render: (d, t, r) => `${parseFloat(d).toLocaleString('es-AR')} <small style="color:#9aa1ad">${r.unidad}</small>`
    },
    {
      data: 'stock_minimo',
      className: 'td-num',
      render: (d, t, r) => `${parseFloat(d).toLocaleString('es-AR')} <small style="color:#9aa1ad">${r.unidad}</small>`
    },
    { data: 'estado_html', orderable: false },
    {
      data: 'activo',
      render: d => d == 1
        ? '<span class="status-ok">Activo</span>'
        : '<span style="background:#f3f4f6;color:#9aa1ad;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:700">Inactivo</span>'
    },
    {
      data: null,
      orderable: false,
      className: 'td-actions',
      render: r => `
        <button class="pkg-icon-btn editar-pkg" data-id="${r.idpackaging}" title="Editar">
          <i class="fa fa-pen"></i>
        </button>
        <button class="pkg-icon-btn pkg-icon-danger eliminar-pkg" data-id="${r.idpackaging}" data-nombre="${r.nombre}" title="Eliminar">
          <i class="fa fa-trash"></i>
        </button>`
    }
  ]
});

/* Stats */
function refreshStats(){
  const rows = tablaPkg.rows({ search: 'applied' }).data().toArray();
  let ok = 0, low = 0, sinStock = 0;
  rows.forEach(r => {
    if(r.estado_key === 'ok')     ok++;
    if(r.estado_key === 'low')    low++;
    if(r.estado_key === 'critical' || r.estado_key === 'nostock') sinStock++;
  });
  $('#statTotal').textContent    = rows.length;
  $('#statSinStock').textContent = sinStock;
  $('#statBajo').textContent     = low;
  $('#statOk').textContent       = ok;
}
tablaPkg.on('xhr.dt draw.dt', refreshStats);

/* Filtros */
const pkgSearch    = $('#pkgSearch');
const pkgFiltroEst = $('#pkgFiltroEstado');
const pkgFiltroAct = $('#pkgFiltroActivo');

jQuery.fn.DataTable.ext.search.push(function(settings, data, idx){
  if(settings.nTable.id !== 'tablaPkg') return true;
  const row = tablaPkg.row(idx).data();
  if(!row) return true;
  if(pkgFiltroEst.value !== 'all' && row.estado_key !== pkgFiltroEst.value) return false;
  if(pkgFiltroAct.value !== 'all' && String(row.activo) !== pkgFiltroAct.value) return false;
  return true;
});

function applyFilters(){ tablaPkg.search(pkgSearch.value).draw(); }
[pkgSearch, pkgFiltroEst, pkgFiltroAct].forEach(el => {
  el.addEventListener('input',  applyFilters);
  el.addEventListener('change', applyFilters);
});

/* ── CRUD ── */
const modalPkg = $('#modalPkg');
const formPkg  = $('#formPkg');

$('#btnNuevoPkg').addEventListener('click', () => {
  formPkg.reset();
  $('#pkg_id').value = '';
  $('#pkg_activo').checked = true;
  $('#modalPkgTitle').textContent = 'Nuevo packaging';
  $('#modalPkgSub').textContent   = 'Completá los datos del material (medidas en CM)';
  // Pre-seleccionar CM (id=6) como unidad por defecto para cajas
  const cmOpt = $('#pkg_unidad option[value="6"]') || $('#pkg_unidad option[value="<?= array_values(array_filter($unidades, fn($u)=>$u['abreviatura']==='cm'))[0]['idunidad_medida'] ?? 6 ?>"]');
  if(cmOpt) $('#pkg_unidad').value = cmOpt.value;
  openModal(modalPkg);
});

jQuery('#tablaPkg').on('click', '.editar-pkg', function(){
  const id = jQuery(this).data('id');
  jQuery.get('api/obtener.php', { id }, function(data){
    $('#pkg_id').value           = data.idpackaging;
    $('#pkg_nombre').value       = data.nombre;
    $('#pkg_descripcion').value  = data.descripcion || '';
    $('#pkg_unidad').value       = data.unidad_medida_idunidad_medida;
    $('#pkg_stock_actual').value = data.stock_actual;
    $('#pkg_stock_minimo').value = data.stock_minimo;
    $('#pkg_activo').checked     = data.activo == 1;
    $('#modalPkgTitle').textContent = 'Editar packaging';
    $('#modalPkgSub').textContent   = 'Actualizá la información';
    openModal(modalPkg);
  }, 'json');
});

formPkg.addEventListener('submit', function(e){
  e.preventDefault();
  const payload = {
    id:           $('#pkg_id').value,
    nombre:       $('#pkg_nombre').value.trim(),
    descripcion:  $('#pkg_descripcion').value.trim(),
    unidad:       $('#pkg_unidad').value,
    stock_actual: $('#pkg_stock_actual').value || 0,
    stock_minimo: $('#pkg_stock_minimo').value || 0,
    activo:       $('#pkg_activo').checked ? 1 : 0
  };
  if(!payload.nombre){ toast('El nombre es obligatorio', false); return; }
  jQuery.post('api/guardar.php', payload, function(resp){
    if(resp.success){
      closeModal(modalPkg);
      tablaPkg.ajax.reload(null, false);
      toast(payload.id ? 'Packaging actualizado' : 'Packaging creado');
    } else {
      toast(resp.message || 'Error al guardar', false);
    }
  }, 'json');
});

/* ── Eliminar (SweetAlert) ── */
jQuery('#tablaPkg').on('click', '.eliminar-pkg', function(){
  const id     = jQuery(this).data('id');
  const nombre = jQuery(this).data('nombre');

  Swal.fire({
    title: `¿Eliminar "${nombre}"?`,
    text: 'Se desactivará del sistema. Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#c88e99',
    cancelButtonColor: '#9aa1ad',
    confirmButtonText: '<i class="fa fa-trash"></i> Sí, eliminar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then(result => {
    if(!result.isConfirmed) return;

    jQuery.post('api/eliminar.php', { id }, function(resp){
      if(resp.success){
        tablaPkg.ajax.reload(null, false);
        toast('Packaging eliminado');
      } else if(resp.en_uso){
        const lista = resp.productos.map(p => `• ${p}`).join('<br>');
        Swal.fire({
          icon: 'warning',
          title: 'No se puede eliminar',
          html: `<p style="margin-bottom:10px">Este packaging está asignado a:</p>
                 <div style="text-align:left;background:#f9edf0;border-radius:8px;padding:12px 16px;font-size:14px;line-height:1.8">${lista}</div>
                 <p style="margin-top:12px;font-size:13px;color:#888">Quitalo de esos productos antes de eliminarlo.</p>`,
          confirmButtonColor: '#c88e99',
          confirmButtonText: 'Entendido'
        });
      } else {
        toast(resp.message || 'Error al eliminar', false);
      }
    }, 'json');
  });
});


/* ================================================================
   TAB 2 — POR PRODUCTO (split layout)
================================================================ */
const selectProducto  = $('#selectProducto');
const btnGuardarAsig  = $('#btnGuardarAsig');
const btnGuardarAsig2 = $('#btnGuardarAsig2');
const btnCancelarAsig = $('#btnCancelarAsig');
const asigEmpty       = $('#asigEmpty');
const asigSplitPanel  = $('#asigSplitPanel');

let asigFilas    = [];   // [{packaging_idpackaging, cantidad}]
let asigOriginal = [];

/* Renderiza el panel split */
function renderSplit(){
  const assignedIds = asigFilas.map(f => String(f.packaging_idpackaging));

  /* — Izquierda: asignado — */
  const leftEl = $('#asigLeft');
  if(!asigFilas.length){
    leftEl.innerHTML = '<div class="asig-empty-inner"><i class="fa-solid fa-inbox"></i><br>Sin materiales asignados</div>';
  } else {
    leftEl.innerHTML = asigFilas.map((fila, idx) => {
      const pkg = allPackaging.find(p => String(p.idpackaging) === String(fila.packaging_idpackaging));
      if(!pkg) return '';
      return `
        <div class="asig-item-assigned">
          <div class="asig-item-info">
            <span class="asig-item-name">${pkg.nombre}</span>
            <span class="asig-item-unit-badge">${pkg.unidad_abrev || ''}</span>
          </div>
          <div class="asig-item-controls">
            <label class="asig-cant-label">Cant.</label>
            <input type="number" step="0.01" min="0.01" class="asig-inp-cant" data-idx="${idx}"
                   value="${fila.cantidad}" title="Cantidad">
            <button class="btn-del-asig" data-idx="${idx}" title="Quitar">
              <i class="fa fa-xmark"></i>
            </button>
          </div>
        </div>`;
    }).join('');

    leftEl.querySelectorAll('.asig-inp-cant').forEach(inp => {
      inp.addEventListener('input', function(){
        asigFilas[this.dataset.idx].cantidad = parseFloat(this.value) || 0;
        updateCountBadge();
      });
    });
    leftEl.querySelectorAll('.btn-del-asig').forEach(btn => {
      btn.addEventListener('click', function(){
        asigFilas.splice(parseInt(this.dataset.idx), 1);
        renderSplit();
      });
    });
  }

  /* — Derecha: disponible — */
  const rightEl = $('#asigRight');
  const available = allPackaging.filter(p => !assignedIds.includes(String(p.idpackaging)));

  if(!available.length){
    rightEl.innerHTML = '<div class="asig-empty-inner"><i class="fa-solid fa-circle-check" style="color:#1a7a4a"></i><br>Todos los materiales ya están asignados</div>';
  } else {
    rightEl.innerHTML = available.map(pkg => `
      <div class="asig-item-available">
        <div class="asig-item-info">
          <span class="asig-item-name">${pkg.nombre}</span>
          <span class="asig-item-unit-badge">${pkg.unidad_abrev || ''}</span>
        </div>
        <button class="btn-add-pkg btn-pkg" data-pkg-id="${pkg.idpackaging}" title="Agregar">
          <i class="fa fa-plus"></i> Agregar
        </button>
      </div>`).join('');

    rightEl.querySelectorAll('.btn-add-pkg').forEach(btn => {
      btn.addEventListener('click', function(){
        asigFilas.push({ packaging_idpackaging: this.dataset.pkgId, cantidad: 1 });
        renderSplit();
      });
    });
  }

  updateCountBadge();
}

function updateCountBadge(){
  const n = asigFilas.length;
  $('#asigCount').textContent     = n;
  $('#asigCountBadge').textContent = n + (n === 1 ? ' asignado' : ' asignados');
}

function cargarAsignacion(idProducto){
  jQuery.get('api/listar_asignacion.php', { producto: idProducto }, function(data){
    asigFilas    = data.map(d => ({ packaging_idpackaging: String(d.packaging_idpackaging), cantidad: d.cantidad }));
    asigOriginal = JSON.parse(JSON.stringify(asigFilas));
    renderSplit();
  }, 'json');
}

selectProducto.addEventListener('change', function(){
  const id = this.value;
  if(!id){
    asigEmpty.style.display      = '';
    asigSplitPanel.style.display = 'none';
    btnGuardarAsig.disabled = true;
    return;
  }
  btnGuardarAsig.disabled = false;
  asigEmpty.style.display      = 'none';
  asigSplitPanel.style.display = '';

  const nomProd = this.options[this.selectedIndex].text.trim();
  $('#asigTitulo').textContent = `Packaging — ${nomProd}`;
  $('#asigSub').textContent    = 'Gestioná los materiales de empaque para este producto';
  cargarAsignacion(id);
});

function guardarAsignacion(){
  const idProducto = selectProducto.value;
  if(!idProducto) return;

  const ids    = asigFilas.map(f => f.packaging_idpackaging);
  const unicos = new Set(ids);
  if(unicos.size !== ids.length){
    Swal.fire({
      icon: 'warning',
      title: 'Packaging duplicado',
      text: 'Hay materiales repetidos en la lista. Revisalos antes de guardar.',
      confirmButtonColor: '#c88e99'
    });
    return;
  }

  const items = asigFilas.map(f => ({
    packaging_idpackaging: f.packaging_idpackaging,
    cantidad: f.cantidad
  }));

  jQuery.post('api/guardar_asignacion.php', {
    producto: idProducto,
    items: JSON.stringify(items)
  }, function(resp){
    if(resp.success){
      asigOriginal = JSON.parse(JSON.stringify(asigFilas));
      Swal.fire({
        icon: 'success',
        title: 'Guardado',
        text: 'El packaging del producto fue actualizado correctamente.',
        timer: 1800,
        showConfirmButton: false,
        confirmButtonColor: '#c88e99'
      });
    } else {
      toast(resp.message || 'Error al guardar', false);
    }
  }, 'json');
}

btnGuardarAsig.addEventListener('click',  guardarAsignacion);
btnGuardarAsig2.addEventListener('click', guardarAsignacion);

btnCancelarAsig.addEventListener('click', () => {
  asigFilas = JSON.parse(JSON.stringify(asigOriginal));
  renderSplit();
  toast('Cambios descartados');
});

})();
</script>
