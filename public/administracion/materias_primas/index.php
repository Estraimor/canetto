<?php 
include '../../../panel/dashboard/layaut/nav.php';
?>

<link rel="stylesheet" href="estilos_materias_primas.css">

<div class="content-body">

  <!-- ===== HEADER ===== -->
  <div class="mp-header">
    <div>
      <div class="mp-title">Materias Primas</div>
      <div class="mp-sub">Gestión de ingredientes base · estilo Canetto</div>
    </div>

    <div class="mp-actions">
      <button class="btn-mp btn-mp-soft" id="btnToggleFilters">
        <i class="fa-solid fa-sliders"></i> Filtros
      </button>
      <button class="btn-mp" id="btnNuevaMP">
        <i class="fa-solid fa-plus"></i> Nueva
      </button>
    </div>
  </div>

  <!-- ===== MINI STATS ===== -->
  <div class="mp-stats">
    <div class="mp-stat">
      <div class="mp-stat-label">Total materias</div>
      <div class="mp-stat-value" id="statTotal">0</div>
    </div>
    <div class="mp-stat">
      <div class="mp-stat-label">Críticas</div>
      <div class="mp-stat-value" id="statCriticas">0</div>
    </div>
    <div class="mp-stat">
      <div class="mp-stat-label">Bajas</div>
      <div class="mp-stat-value" id="statBajas">0</div>
    </div>
    <div class="mp-stat">
      <div class="mp-stat-label">OK</div>
      <div class="mp-stat-value" id="statOk">0</div>
    </div>
  </div>

  <!-- ===== FILTERS (collapsible) ===== -->
  <div class="mp-filters" id="filtersBox">
    <div class="mp-filter-grid">
      <div class="mp-field">
        <label>Buscar</label>
        <div class="mp-input-icon">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="qBuscar" placeholder="Harina, chocolate, pistacho..." autocomplete="off">
        </div>
      </div>

      <div class="mp-field">
        <label>Estado</label>
        <select id="qEstado">
          <option value="all">Todos</option>
          <option value="ok">OK</option>
          <option value="low">Bajo</option>
          <option value="critical">Crítico</option>
          <option value="nostock">Sin stock</option>
        </select>
      </div>

      <div class="mp-field">
        <label>Activo</label>
        <select id="qActivo">
          <option value="1">Activos</option>
          <option value="0">Inactivos</option>
          <option value="all">Todos</option>
        </select>
      </div>

      <div class="mp-field">
        <label>Orden</label>
        <select id="qOrden">
          <option value="nombre_asc">Nombre (A-Z)</option>
          <option value="nombre_desc">Nombre (Z-A)</option>
          <option value="stock_asc">Stock (menor a mayor)</option>
          <option value="stock_desc">Stock (mayor a menor)</option>
          <option value="estado">Estado (Crítico primero)</option>
        </select>
      </div>
    </div>

    <div class="mp-filter-actions">
      <button class="btn-mp btn-mp-soft" id="btnLimpiar">
        <i class="fa-solid fa-eraser"></i> Limpiar
      </button>
    </div>
  </div>

<div class="mp-table-wrapper">
  <table id="tablaMP" class="display mp-table" style="width:100%">
    <thead>
      <tr>
        <th>Materia Prima</th>
        <th>Stock</th>
        <th>Mínimo</th>
        <th>Estado</th>
        <th>Activo</th>
        <th style="width:120px;">Acciones</th>
      </tr>
    </thead>
  </table>
</div>

</div>

<!-- =========================
     MODAL: NUEVA / EDITAR
========================= -->
<div class="mp-modal" id="modalMP" aria-hidden="true">
  <div class="mp-modal-backdrop" data-close="true"></div>

  <div class="mp-modal-dialog" role="dialog" aria-modal="true">
    <div class="mp-modal-head">
      <div>
        <div class="mp-modal-title" id="modalTitle">Nueva materia prima</div>
        <div class="mp-modal-sub" id="modalSub">Completa los datos básicos</div>
      </div>
      <button class="mp-x" data-close="true" aria-label="Cerrar">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="mp-modal-body">
      <form id="formMP" autocomplete="off">
        <input type="hidden" id="mp_id" value="">

        <div class="mp-form-grid">
          <div class="mp-field">
            <label>Nombre *</label>
            <input type="text" id="mp_nombre" required placeholder="Ej: Chocolate cobertura">
          </div>

          <div class="mp-field">
            <label>Unidad de medida *</label>
            <select id="mp_unidad" required>
    <option value="">-- Seleccionar --</option>
    <?php
    define('APP_BOOT', true);
    require_once '../../../../config/conexion.php';
    $pdo = Conexion::conectar();

    $stmt = $pdo->query("SELECT idunidad_medida, nombre, abreviatura FROM unidad_medida ORDER BY nombre ASC");
    while($u = $stmt->fetch()){
        echo "<option value='{$u['idunidad_medida']}' data-abrev='{$u['abreviatura']}'>
                {$u['nombre']} ({$u['abreviatura']})
              </option>";
    }
    ?>
</select>
          </div>

          <div class="mp-field">
            <label>Stock actual</label>
            <input type="number" step="0.01" id="mp_stock_actual" placeholder="0.00">
          </div>

          <div class="mp-field">
            <label>Stock mínimo</label>
            <input type="number" step="0.01" id="mp_stock_minimo" placeholder="0.00">
          </div>

          <div class="mp-field mp-switch">
            <label>Activo</label>
            <div class="switch">
              <input type="checkbox" id="mp_activo" checked>
              <span class="slider"></span>
            </div>
          </div>

          <div class="mp-field">
            <label>Nota (opcional)</label>
            <input type="text" id="mp_nota" placeholder="Ej: marca preferida, proveedor, etc. (front-only)">
          </div>
        </div>

        <div class="mp-modal-actions">
          <button type="button" class="btn-mp btn-mp-soft" data-close="true">
            Cancelar
          </button>

          <button type="submit" class="btn-mp" id="btnGuardar">
            <i class="fa-solid fa-floppy-disk"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- =========================
     MODAL: ELIMINAR
========================= -->
<div class="mp-modal" id="modalDelete" aria-hidden="true">
  <div class="mp-modal-backdrop" data-close="true"></div>

  <div class="mp-modal-dialog mp-modal-sm" role="dialog" aria-modal="true">
    <div class="mp-modal-head">
      <div>
        <div class="mp-modal-title">Eliminar</div>
        <div class="mp-modal-sub">Esta acción no se puede deshacer</div>
      </div>
      <button class="mp-x" data-close="true" aria-label="Cerrar">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="mp-modal-body">
      <div class="mp-danger">
        <div class="mp-danger-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
          <div class="mp-danger-title" id="delName">¿Eliminar materia?</div>
          <div class="mp-danger-sub">Se eliminará del listado.</div>
        </div>
      </div>

      <div class="mp-modal-actions">
        <button class="btn-mp btn-mp-soft" data-close="true">Cancelar</button>
        <button class="btn-mp btn-mp-danger" id="btnConfirmDelete">
          <i class="fa-solid fa-trash"></i> Eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="mp-toast" id="toast">
  <div class="mp-toast-inner">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMsg">Listo</span>
  </div>
</div>

<script>
/* =========================================================
   CANETTO - Materias Primas (Front Only)
   - Lista con tarjetas minimal
   - Filtros + stats
   - Modales con animación
   - ABM mock (en memoria) listo para enchufar AJAX
========================================================= */

const $ = (q) => document.querySelector(q);
const $$ = (q) => document.querySelectorAll(q);

const mpList = $("#mpList");
const filtersBox = $("#filtersBox");

const qBuscar = $("#qBuscar");
const qEstado = $("#qEstado");
const qActivo = $("#qActivo");
const qOrden  = $("#qOrden");

const toast = $("#toast");
const toastMsg = $("#toastMsg");

const modalMP = $("#modalMP");
const modalDelete = $("#modalDelete");

const formMP = $("#formMP");

const mp_id = $("#mp_id");
const mp_nombre = $("#mp_nombre");
const mp_unidad = $("#mp_unidad");
const mp_stock_actual = $("#mp_stock_actual");
const mp_stock_minimo = $("#mp_stock_minimo");
const mp_activo = $("#mp_activo");
const mp_nota = $("#mp_nota");

const modalTitle = $("#modalTitle");
const modalSub = $("#modalSub");

const statTotal = $("#statTotal");
const statCriticas = $("#statCriticas");
const statBajas = $("#statBajas");
const statOk = $("#statOk");

let deletingId = null;



/* ===== Helpers ===== */
function estado(item){
  const a = Number(item.stock_actual || 0);
  const m = Number(item.stock_minimo || 0);

  if (a <= 0) return { key:"nostock", label:"Sin stock", cls:"status-critical" };
  if (a <= m) return { key:"critical", label:"Crítico", cls:"status-critical" };
  if (a <= (m * 1.3)) return { key:"low", label:"Bajo", cls:"status-low" };
  return { key:"ok", label:"OK", cls:"status-ok" };
}

function fmt(n){
  const x = Number(n || 0);
  return x.toLocaleString("es-AR", { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function showToast(msg){
  toastMsg.textContent = msg;
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 2200);
}

/* ===== Modal System ===== */
function openModal(modal){
  modal.classList.add("open");
  modal.setAttribute("aria-hidden","false");
  document.body.classList.add("mp-lock");
  requestAnimationFrame(() => modal.classList.add("in"));
}

function closeModal(modal){
  modal.classList.remove("in");
  modal.setAttribute("aria-hidden","true");
  setTimeout(() => {
    modal.classList.remove("open");
    if (![modalMP, modalDelete].some(m => m.classList.contains("open"))) {
      document.body.classList.remove("mp-lock");
    }
  }, 180);
}

$$("[data-close='true']").forEach(el => {
  el.addEventListener("click", (e) => {
    const m = e.target.closest(".mp-modal");
    if (m) closeModal(m);
  });
});

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape"){
    if (modalMP.classList.contains("open")) closeModal(modalMP);
    if (modalDelete.classList.contains("open")) closeModal(modalDelete);
  }
});

/* ===== Render ===== */
function render(){
  let items = [...data];

  // activo
  const act = qActivo.value;
  if (act !== "all"){
    items = items.filter(x => String(x.activo) === act);
  }

  // buscar
  const q = qBuscar.value.trim().toLowerCase();
  if (q){
    items = items.filter(x => (x.nombre || "").toLowerCase().includes(q));
  }

  // estado
  const est = qEstado.value;
  if (est !== "all"){
    items = items.filter(x => estado(x).key === est);
  }

  // orden
  switch(qOrden.value){
    case "nombre_asc":
      items.sort((a,b) => (a.nombre||"").localeCompare(b.nombre||""));
      break;
    case "nombre_desc":
      items.sort((a,b) => (b.nombre||"").localeCompare(a.nombre||""));
      break;
    case "stock_asc":
      items.sort((a,b) => Number(a.stock_actual||0) - Number(b.stock_actual||0));
      break;
    case "stock_desc":
      items.sort((a,b) => Number(b.stock_actual||0) - Number(a.stock_actual||0));
      break;
    case "estado":
      const prio = { nostock:0, critical:1, low:2, ok:3 };
      items.sort((a,b) => prio[estado(a).key] - prio[estado(b).key]);
      break;
  }

  // stats (sobre TODO data)
  const all = [...data].filter(x => qActivo.value === "all" ? true : String(x.activo) === qActivo.value);
  const counts = all.reduce((acc,it)=>{
    const k = estado(it).key;
    acc[k] = (acc[k]||0)+1;
    acc.total++;
    return acc;
  }, { total:0, ok:0, low:0, critical:0, nostock:0 });

  statTotal.textContent = counts.total;
  statCriticas.textContent = (counts.critical + counts.nostock);
  statBajas.textContent = counts.low;
  statOk.textContent = counts.ok;

  // list
  if (!items.length){
    mpList.innerHTML = `
      <div class="mp-empty">
        <div class="mp-empty-icon"><i class="fa-regular fa-face-smile"></i></div>
        <div class="mp-empty-title">No hay resultados</div>
        <div class="mp-empty-sub">Probá cambiar filtros o crear una nueva materia prima.</div>
      </div>
    `;
    return;
  }

  mpList.innerHTML = items.map(item => {
    const st = estado(item);
    return `
      <div class="mp-card ${item.activo ? "" : "mp-inactive"}">
        <div class="mp-left">
          <div class="mp-name-row">
            <div class="mp-name">${item.nombre}</div>
            ${item.activo ? "" : "<span class='mp-pill mp-pill-off'>Inactiva</span>"}
          </div>
          <div class="mp-detail">
            <span class="mp-dot"></span>
            <strong>${fmt(item.stock_actual)}</strong> ${item.unidad}
            <span class="mp-sep">·</span>
            Mín: <strong>${fmt(item.stock_minimo)}</strong> ${item.unidad}
          </div>
        </div>

        <div class="mp-right">
          <span class="mp-status ${st.cls}">${st.label}</span>

          <div class="mp-btns">
            <button class="mp-icon-btn" title="Editar" data-edit="${item.id}">
              <i class="fa-regular fa-pen-to-square"></i>
            </button>
            <button class="mp-icon-btn mp-icon-danger" title="Eliminar" data-del="${item.id}">
              <i class="fa-regular fa-trash-can"></i>
            </button>
          </div>
        </div>
      </div>
    `;
  }).join("");

  // bind btns
  $$("[data-edit]").forEach(b => b.addEventListener("click", () => openEdit(Number(b.dataset.edit))));
  $$("[data-del]").forEach(b => b.addEventListener("click", () => openDelete(Number(b.dataset.del))));
}

/* ===== UI events ===== */
$("#btnToggleFilters").addEventListener("click", () => {
  filtersBox.classList.toggle("open");
});

$("#btnLimpiar").addEventListener("click", () => {
  qBuscar.value = "";
  qEstado.value = "all";
  qActivo.value = "1";
  qOrden.value = "nombre_asc";
  render();
});

[qBuscar, qEstado, qActivo, qOrden].forEach(el => {
  el.addEventListener("input", render);
  el.addEventListener("change", render);
});

/* ===== ABM (front-only) ===== */
$("#btnNuevaMP").addEventListener("click", () => {
  modalTitle.textContent = "Nueva materia prima";
  modalSub.textContent = "Completa los datos básicos";
  formMP.reset();

  mp_id.value = "";
  mp_activo.checked = true;

  openModal(modalMP);
  setTimeout(() => mp_nombre.focus(), 120);
});

function openEdit(id){
  const item = data.find(x => x.id === id);
  if (!item) return;

  modalTitle.textContent = "Editar materia prima";
  modalSub.textContent = "Actualizá stock o datos";

  mp_id.value = item.id;
  mp_nombre.value = item.nombre || "";
  mp_stock_actual.value = item.stock_actual ?? "";
  mp_stock_minimo.value = item.stock_minimo ?? "";
  mp_activo.checked = !!item.activo;

  // unidad demo (si tuvieras idunidad, acá seteás el select con ese id)
  // como demo usamos abrev:
  const opt = [...mp_unidad.options].find(o => (o.dataset.abrev || "") === (item.unidad || ""));
  mp_unidad.value = opt ? opt.value : "";

  mp_nota.value = "";

  openModal(modalMP);
  setTimeout(() => mp_nombre.focus(), 120);
}

formMP.addEventListener("submit", (e) => {
  e.preventDefault();

  const payload = {
    id: mp_id.value ? Number(mp_id.value) : null,
    nombre: mp_nombre.value.trim(),
    unidad: mp_unidad.options[mp_unidad.selectedIndex]?.dataset?.abrev || "",
    stock_actual: Number(mp_stock_actual.value || 0),
    stock_minimo: Number(mp_stock_minimo.value || 0),
    activo: mp_activo.checked ? 1 : 0,
  };

  if (!payload.nombre){
    showToast("El nombre es obligatorio");
    mp_nombre.focus();
    return;
  }
  if (!payload.unidad){
    showToast("Seleccioná una unidad");
    mp_unidad.focus();
    return;
  }

  if (payload.id){
    // TODO: AJAX -> update
    const idx = data.findIndex(x => x.id === payload.id);
    if (idx >= 0) data[idx] = { ...data[idx], ...payload };
    showToast("Materia prima actualizada");
  } else {
    // TODO: AJAX -> insert
    const nextId = Math.max(...data.map(x => x.id)) + 1;
    data.unshift({ ...payload, id: nextId });
    showToast("Materia prima creada");
  }

  closeModal(modalMP);
  render();
});

function openDelete(id){
  const item = data.find(x => x.id === id);
  if (!item) return;

  deletingId = id;
  $("#delName").textContent = `¿Eliminar "${item.nombre}"?`;

  openModal(modalDelete);
}

$("#btnConfirmDelete").addEventListener("click", () => {
  if (!deletingId) return;

  // TODO: AJAX -> delete
  data = data.filter(x => x.id !== deletingId);
  deletingId = null;

  closeModal(modalDelete);
  showToast("Materia prima eliminada");
  render();
});

/* init */
render();
</script>

<script>
    $(document).ready(function(){

  let tabla = $('#tablaMP').DataTable({
      ajax:{
          url:'api/listar.php',
          dataSrc:''
      },
      pageLength:10,
      responsive:true,
      dom:'Bfrtip',
      buttons:['excel'],
      columns:[
          { data:'nombre' },
          { data:'stock_actual',
            render:function(data,row,type){
                return data + ' ' + row.unidad;
            }
          },
          { data:'stock_minimo',
            render:function(data,row,type){
                return data + ' ' + row.unidad;
            }
          },
          { data:null,
            render:function(data){
                return data.estado_html;
            }
          },
          { data:'activo',
            render:function(data){
                return data == 1
                ? '<span class="badge-ok">Activo</span>'
                : '<span class="badge-low">Inactivo</span>';
            }
          },
          { data:null,
            render:function(data){
                return `
                <button class="mp-icon-btn editar" data-id="${data.idmateria_prima}">
                    <i class="fa fa-pen"></i>
                </button>
                <button class="mp-icon-btn mp-icon-danger eliminar" data-id="${data.idmateria_prima}">
                    <i class="fa fa-trash"></i>
                </button>
                `;
            }
          }
      ]
  });

  // ELIMINAR
  $('#tablaMP').on('click','.eliminar',function(){
      let id = $(this).data('id');

      if(confirm('¿Eliminar materia prima?')){
          $.post('api/eliminar.php',{id:id},function(){
              tabla.ajax.reload();
          });
      }
  });

});
</script>

<?php include '../../../panel/dashboard/layaut/footer.php'; ?>