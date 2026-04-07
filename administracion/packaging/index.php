<?php
define('APP_BOOT', true);
require_once '../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo      = Conexion::conectar();
$unidades = $pdo->query("SELECT idunidad_medida, nombre, abreviatura FROM unidad_medida ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$productos = $pdo->query("SELECT idproductos, nombre, tipo FROM productos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$allPkg   = $pdo->query("SELECT idpackaging, nombre, unidad_medida_idunidad_medida FROM packaging WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
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

  <!-- TAB: STOCK -->
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

  <!-- TAB: POR PRODUCTO -->
  <div class="pkg-tab-panel" id="tab-productos">

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
      <button class="btn-pkg" id="btnAgregarPkg" disabled>
        <i class="fa-solid fa-plus"></i> Agregar packaging
      </button>
      <button class="btn-pkg" id="btnGuardarAsig" disabled>
        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
      </button>
    </div>

    <div class="asig-panel" id="asigPanel">
      <div class="asig-panel-head">
        <div>
          <h3 id="asigTitulo">Packaging asignado</h3>
          <p id="asigSub">Seleccioná un producto para ver y editar los materiales de empaque</p>
        </div>
      </div>

      <div id="asigCuerpo">
        <div class="asig-empty">
          <i class="fa-regular fa-box-open"></i>
          Seleccioná un producto para ver su packaging
        </div>
      </div>

      <div class="asig-foot" id="asigFoot" style="display:none">
        <div class="asig-foot-info">
          <strong id="asigCount">0</strong> tipo(s) de packaging asignado(s)
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn-pkg btn-pkg-soft" id="btnCancelarAsig">Descartar</button>
          <button class="btn-pkg" id="btnGuardarAsig2">
            <i class="fa-solid fa-floppy-disk"></i> Guardar
          </button>
        </div>
      </div>
    </div>

  </div><!-- /tab-productos -->

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
            <label>Descripción</label>
            <textarea id="pkg_descripcion" placeholder="Tamaño, color, proveedor u observación..."></textarea>
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


<!-- ==============================
     MODAL ELIMINAR
============================== -->
<div class="pkg-modal" id="modalDeletePkg">
  <div class="pkg-modal-backdrop" data-close="true"></div>
  <div class="pkg-modal-dialog pkg-modal-sm">
    <div class="pkg-modal-head">
      <div>
        <div class="pkg-modal-title">Eliminar packaging</div>
        <div class="pkg-modal-sub">Esta acción no se puede deshacer</div>
      </div>
      <button class="pkg-x" data-close="true"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="pkg-modal-body">
      <div class="pkg-danger">
        <div class="pkg-danger-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
          <div class="pkg-danger-title" id="delPkgName">¿Eliminar packaging?</div>
          <div class="pkg-danger-sub">Se desactivará del sistema.</div>
        </div>
      </div>
      <div class="pkg-modal-actions">
        <button class="btn-pkg btn-pkg-soft" data-close="true">Cancelar</button>
        <button class="btn-pkg btn-pkg-danger" id="btnConfirmDeletePkg">
          <i class="fa-solid fa-trash"></i> Eliminar
        </button>
      </div>
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

<script>
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

/* ── Modales ── */
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
  let ok = 0, low = 0, sinStock = 0, critical = 0;
  rows.forEach(r => {
    if(r.estado_key === 'ok')     ok++;
    if(r.estado_key === 'low')    low++;
    if(r.estado_key === 'critical' || r.estado_key === 'nostock') { sinStock++; }
  });
  $('#statTotal').textContent    = rows.length;
  $('#statSinStock').textContent = sinStock;
  $('#statBajo').textContent     = low;
  $('#statOk').textContent       = ok;
}
tablaPkg.on('xhr.dt draw.dt', refreshStats);

/* Filtros */
const pkgSearch      = $('#pkgSearch');
const pkgFiltroEst   = $('#pkgFiltroEstado');
const pkgFiltroAct   = $('#pkgFiltroActivo');

jQuery.fn.DataTable.ext.search.push(function(settings, data, idx){
  if(settings.nTable.id !== 'tablaPkg') return true;
  const row = tablaPkg.row(idx).data();
  if(!row) return true;
  if(pkgFiltroEst.value !== 'all' && row.estado_key !== pkgFiltroEst.value) return false;
  if(pkgFiltroAct.value !== 'all' && String(row.activo) !== pkgFiltroAct.value) return false;
  return true;
});

function applyFilters(){
  tablaPkg.search(pkgSearch.value).draw();
}
[pkgSearch, pkgFiltroEst, pkgFiltroAct].forEach(el => {
  el.addEventListener('input', applyFilters);
  el.addEventListener('change', applyFilters);
});

/* CRUD */
const modalPkg = $('#modalPkg');
const formPkg  = $('#formPkg');

$('#btnNuevoPkg').addEventListener('click', () => {
  formPkg.reset();
  $('#pkg_id').value = '';
  $('#pkg_activo').checked = true;
  $('#modalPkgTitle').textContent = 'Nuevo packaging';
  $('#modalPkgSub').textContent   = 'Completá los datos del material';
  openModal(modalPkg);
});

jQuery('#tablaPkg').on('click', '.editar-pkg', function(){
  const id = jQuery(this).data('id');
  jQuery.get('api/obtener.php', { id }, function(data){
    $('#pkg_id').value                 = data.idpackaging;
    $('#pkg_nombre').value             = data.nombre;
    $('#pkg_descripcion').value        = data.descripcion || '';
    $('#pkg_unidad').value             = data.unidad_medida_idunidad_medida;
    $('#pkg_stock_actual').value       = data.stock_actual;
    $('#pkg_stock_minimo').value       = data.stock_minimo;
    $('#pkg_activo').checked           = data.activo == 1;
    $('#modalPkgTitle').textContent    = 'Editar packaging';
    $('#modalPkgSub').textContent      = 'Actualizá la información';
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

/* Eliminar */
const modalDeletePkg = $('#modalDeletePkg');
let deletePkgId = null;

jQuery('#tablaPkg').on('click', '.eliminar-pkg', function(){
  deletePkgId = jQuery(this).data('id');
  $('#delPkgName').textContent = `¿Eliminar "${jQuery(this).data('nombre')}"?`;
  openModal(modalDeletePkg);
});

$('#btnConfirmDeletePkg').addEventListener('click', () => {
  if(!deletePkgId) return;
  jQuery.post('api/eliminar.php', { id: deletePkgId }, function(resp){
    closeModal(modalDeletePkg);
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


/* ================================================================
   TAB 2 — POR PRODUCTO
================================================================ */
const selectProducto = $('#selectProducto');
const btnAgregarPkg  = $('#btnAgregarPkg');
const btnGuardarAsig = $('#btnGuardarAsig');
const btnGuardarAsig2 = $('#btnGuardarAsig2');
const btnCancelarAsig = $('#btnCancelarAsig');
const asigCuerpo     = $('#asigCuerpo');
const asigFoot       = $('#asigFoot');
const asigCount      = $('#asigCount');
const asigTitulo     = $('#asigTitulo');
const asigSub        = $('#asigSub');

// Filas en memoria
let asigFilas = [];
let asigOriginal = [];

function opcionesPackaging(selectedId = ''){
  return allPackaging.map(p =>
    `<option value="${p.idpackaging}" ${p.idpackaging == selectedId ? 'selected' : ''}>${p.nombre}</option>`
  ).join('');
}

function renderAsig(){
  if(!asigFilas.length){
    asigCuerpo.innerHTML = `
      <div class="asig-empty">
        <i class="fa-solid fa-box-open"></i>
        No hay packaging asignado a este producto
      </div>`;
    asigFoot.style.display = 'none';
    return;
  }

  asigCuerpo.innerHTML = `
    <table class="asig-table">
      <thead>
        <tr>
          <th>Packaging</th>
          <th style="width:130px">Cantidad</th>
          <th style="width:80px">Unidad</th>
          <th style="width:50px"></th>
        </tr>
      </thead>
      <tbody id="asigTbody"></tbody>
    </table>`;

  const tbody = $('#asigTbody');
  asigFilas.forEach((fila, idx) => {
    const tr = document.createElement('tr');
    const pkg = allPackaging.find(p => p.idpackaging == fila.packaging_idpackaging);
    tr.innerHTML = `
      <td>
        <select class="asig-sel-pkg" data-idx="${idx}">
          ${opcionesPackaging(fila.packaging_idpackaging)}
        </select>
      </td>
      <td>
        <input type="number" step="0.01" min="0.01" class="asig-inp-cant" data-idx="${idx}"
               value="${fila.cantidad}" placeholder="1">
      </td>
      <td>
        <span style="font-size:13px;color:#9aa1ad;font-weight:600">${pkg ? pkg.unidad_abrev || '' : ''}</span>
      </td>
      <td>
        <button class="btn-del-asig" data-idx="${idx}" title="Quitar">
          <i class="fa fa-xmark"></i>
        </button>
      </td>`;
    tbody.appendChild(tr);
  });

  // Bind events
  tbody.querySelectorAll('.asig-sel-pkg').forEach(sel => {
    sel.addEventListener('change', function(){
      asigFilas[this.dataset.idx].packaging_idpackaging = this.value;
    });
  });
  tbody.querySelectorAll('.asig-inp-cant').forEach(inp => {
    inp.addEventListener('input', function(){
      asigFilas[this.dataset.idx].cantidad = parseFloat(this.value) || 0;
    });
  });
  tbody.querySelectorAll('.btn-del-asig').forEach(btn => {
    btn.addEventListener('click', function(){
      asigFilas.splice(parseInt(this.dataset.idx), 1);
      renderAsig();
    });
  });

  asigCount.textContent = asigFilas.length;
  asigFoot.style.display = 'flex';
}

function cargarAsignacion(idProducto){
  jQuery.get('api/listar_asignacion.php', { producto: idProducto }, function(data){
    asigFilas    = data.map(d => ({ packaging_idpackaging: d.packaging_idpackaging, cantidad: d.cantidad }));
    asigOriginal = JSON.parse(JSON.stringify(asigFilas));
    renderAsig();
  }, 'json');
}

selectProducto.addEventListener('change', function(){
  const id = this.value;
  if(!id){
    btnAgregarPkg.disabled   = true;
    btnGuardarAsig.disabled  = true;
    asigCuerpo.innerHTML = `<div class="asig-empty"><i class="fa-regular fa-box-open"></i>Seleccioná un producto para ver su packaging</div>`;
    asigFoot.style.display = 'none';
    asigTitulo.textContent = 'Packaging asignado';
    asigSub.textContent    = 'Seleccioná un producto para ver y editar los materiales de empaque';
    return;
  }
  btnAgregarPkg.disabled  = false;
  btnGuardarAsig.disabled = false;
  const nomProd = this.options[this.selectedIndex].text;
  asigTitulo.textContent = `Packaging — ${nomProd}`;
  asigSub.textContent    = 'Editá los materiales de empaque para este producto';
  cargarAsignacion(id);
});

btnAgregarPkg.addEventListener('click', () => {
  if(!allPackaging.length){
    toast('Primero creá al menos un tipo de packaging', false);
    return;
  }
  asigFilas.push({ packaging_idpackaging: allPackaging[0].idpackaging, cantidad: 1 });
  renderAsig();
});

function guardarAsignacion(){
  const idProducto = selectProducto.value;
  if(!idProducto) return;

  // Validar que no haya duplicados
  const ids = asigFilas.map(f => f.packaging_idpackaging);
  const unicos = new Set(ids);
  if(unicos.size !== ids.length){
    toast('Hay packaging duplicado — revisá la lista', false);
    return;
  }

  jQuery.post('api/guardar_asignacion.php', {
    producto: idProducto,
    items: JSON.stringify(asigFilas)
  }, function(resp){
    if(resp.success){
      asigOriginal = JSON.parse(JSON.stringify(asigFilas));
      toast('Packaging guardado correctamente');
    } else {
      toast(resp.message || 'Error al guardar', false);
    }
  }, 'json');
}

btnGuardarAsig.addEventListener('click', guardarAsignacion);
btnGuardarAsig2.addEventListener('click', guardarAsignacion);

btnCancelarAsig.addEventListener('click', () => {
  asigFilas = JSON.parse(JSON.stringify(asigOriginal));
  renderAsig();
  toast('Cambios descartados');
});

})();
</script>
