<?php 
define('APP_BOOT', true);
require_once '../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();
?>

<link rel="stylesheet" href="estilos_materias_primas.css">

<div class="content-body">

  <!-- =========================
        HEADER
  ========================== -->
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

  <!-- =========================
        MINI STATS
  ========================== -->
  <div class="mp-stats">

    <div class="mp-stat">
      <div class="mp-stat-label">Total materias</div>
      <div class="mp-stat-value" id="statTotal">0</div>
    </div>

    <div class="mp-stat">
      <div class="mp-stat-label">Críticas</div>
      <div class="mp-stat-value critical" id="statCriticas">0</div>
    </div>

    <div class="mp-stat">
      <div class="mp-stat-label">Stock bajo</div>
      <div class="mp-stat-value low" id="statBajas">0</div>
    </div>

    <div class="mp-stat">
      <div class="mp-stat-label">OK</div>
      <div class="mp-stat-value ok" id="statOk">0</div>
    </div>

  </div>

  <!-- =========================
        FILTROS
  ========================== -->
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
        <i class="fa-solid fa-eraser"></i> Limpiar filtros
      </button>
    </div>

  </div>

  <!-- =========================
        TABLA
  ========================== -->
  <div class="mp-table-wrapper">

    <table id="tablaMP" class="display mp-table" style="width:100%">
      <thead>
        <tr>
          <th>Materia Prima</th>
          <th>Stock Actual</th>
          <th>Stock Mínimo</th>
          <th>Estado</th>
          <th>Activo</th>
          <th style="width:120px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <!-- DataTables carga por AJAX -->
      </tbody>
    </table>

  </div>

</div>



<!-- =========================================================
      MODAL NUEVA / EDITAR
========================================================= -->
<div class="mp-modal" id="modalMP" aria-hidden="true">

  <div class="mp-modal-backdrop" data-close="true"></div>

  <div class="mp-modal-dialog">

    <div class="mp-modal-head">
      <div>
        <div class="mp-modal-title" id="modalTitle">Nueva materia prima</div>
        <div class="mp-modal-sub" id="modalSub">Completa los datos básicos</div>
      </div>

      <button class="mp-x" data-close="true">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="mp-modal-body">

      <form id="formMP" autocomplete="off">

        <input type="hidden" id="mp_id">

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
              $stmt = $pdo->query("SELECT idunidad_medida, nombre, abreviatura 
                                   FROM unidad_medida 
                                   ORDER BY nombre ASC");
              while($u = $stmt->fetch()){
                  echo "<option value='{$u['idunidad_medida']}'>
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
            <input type="text" id="mp_nota" placeholder="Proveedor, marca preferida, observación interna...">
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



<!-- =========================================================
      MODAL ELIMINAR
========================================================= -->
<div class="mp-modal" id="modalDelete" aria-hidden="true">

  <div class="mp-modal-backdrop" data-close="true"></div>

  <div class="mp-modal-dialog mp-modal-sm">

    <div class="mp-modal-head">
      <div>
        <div class="mp-modal-title">Eliminar materia prima</div>
        <div class="mp-modal-sub">Esta acción no se puede deshacer</div>
      </div>

      <button class="mp-x" data-close="true">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="mp-modal-body">

      <div class="mp-danger">
        <div class="mp-danger-icon">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
          <div class="mp-danger-title" id="delName">
            ¿Eliminar materia?
          </div>
          <div class="mp-danger-sub">
            Se eliminará permanentemente del sistema.
          </div>
        </div>
      </div>

      <div class="mp-modal-actions">
        <button class="btn-mp btn-mp-soft" data-close="true">
          Cancelar
        </button>

        <button class="btn-mp btn-mp-danger" id="btnConfirmDelete">
          <i class="fa-solid fa-trash"></i> Eliminar
        </button>
      </div>

    </div>
  </div>
</div>



<!-- =========================================================
      TOAST NOTIFICACIÓN
========================================================= -->
<div class="mp-toast" id="toast">
  <div class="mp-toast-inner">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMsg">Operación realizada correctamente</span>
  </div>
</div>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>


<script>
  /* =========================================================
   CANETTO - MATERIAS PRIMAS
   Sistema completo con DataTables + API
========================================================= */

(function () {
  "use strict";

  /* =========================================================
     HELPERS
  ========================================================= */

  const $ = (q) => document.querySelector(q);
  const $$ = (q) => document.querySelectorAll(q);

  function showToast(msg) {
    const toast = $("#toast");
    const toastMsg = $("#toastMsg");

    if (!toast) return;

    toastMsg.textContent = msg;
    toast.classList.add("show");

    setTimeout(() => {
      toast.classList.remove("show");
    }, 2500);
  }

  /* =========================================================
     MODALES
  ========================================================= */

  const modalMP = $("#modalMP");
  const modalDelete = $("#modalDelete");

  function openModal(modal) {
    if (!modal) return;
    modal.classList.add("open");
    document.body.classList.add("mp-lock");

    requestAnimationFrame(() => {
      modal.classList.add("in");
    });
  }

  function closeModal(modal) {
    if (!modal) return;

    modal.classList.remove("in");

    setTimeout(() => {
      modal.classList.remove("open");
      document.body.classList.remove("mp-lock");
    }, 180);
  }

  $$("[data-close='true']").forEach(btn => {
    btn.addEventListener("click", function () {
      const m = this.closest(".mp-modal");
      closeModal(m);
    });
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (modalMP.classList.contains("open")) closeModal(modalMP);
      if (modalDelete.classList.contains("open")) closeModal(modalDelete);
    }
  });

  /* =========================================================
     DATA TABLE
  ========================================================= */

  if (!window.jQuery || !jQuery.fn.DataTable) {
    console.error("DataTables no está cargado.");
    return;
  }

  const tabla = jQuery("#tablaMP").DataTable({
    ajax: {
      url: "api/listar.php",
      dataSrc: ""
    },
    pageLength: 10,
    responsive: true,
    order: [[0, "asc"]],
    columns: [
      { data: "nombre" },
      {
        data: "stock_actual",
        render: function (data, type, row) {
          return parseFloat(data).toLocaleString("es-AR") + " " + (row.unidad || "");
        }
      },
      {
        data: "stock_minimo",
        render: function (data, type, row) {
          return parseFloat(data).toLocaleString("es-AR") + " " + (row.unidad || "");
        }
      },
      { data: "estado_html" },
      {
        data: "activo",
        render: function (data) {
          return data == 1
            ? '<span class="badge-ok">Activo</span>'
            : '<span class="badge-low">Inactivo</span>';
        }
      },
      {
        data: null,
        orderable: false,
        render: function (data) {
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

  /* =========================================================
     STATS
  ========================================================= */

  function refreshStats() {
    const rows = tabla.rows({ search: "applied" }).data().toArray();

    let total = rows.length;
    let ok = 0;
    let low = 0;
    let critical = 0;

    rows.forEach(r => {
      if (r.estado_key === "ok") ok++;
      if (r.estado_key === "low") low++;
      if (r.estado_key === "critical" || r.estado_key === "nostock") critical++;
    });

    $("#statTotal").textContent = total;
    $("#statOk").textContent = ok;
    $("#statBajas").textContent = low;
    $("#statCriticas").textContent = critical;
  }

  tabla.on("xhr.dt", refreshStats);
  tabla.on("draw.dt", refreshStats);

  /* =========================================================
     FILTROS
  ========================================================= */

/* =========================================================
   FILTROS REALES (estado + activo + buscar)
========================================================= */

const qBuscar = $("#qBuscar");
const qEstado = $("#qEstado");
const qActivo = $("#qActivo");
const qOrden  = $("#qOrden");
const btnLimpiar = $("#btnLimpiar");


// Filtro personalizado global
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {

    const row = tabla.row(dataIndex).data();

    // =====================
    // FILTRO ESTADO
    // =====================
    if (qEstado.value !== "all") {
        if (row.estado_key !== qEstado.value) {
            return false;
        }
    }

    // =====================
    // FILTRO ACTIVO
    // =====================
    if (qActivo.value !== "all") {
        if (String(row.activo) !== qActivo.value) {
            return false;
        }
    }

    return true;
});


function applyFilters() {

    // Buscar por texto
    tabla.search(qBuscar.value);

    // Orden
    switch (qOrden.value) {
        case "nombre_asc":  tabla.order([0, "asc"]); break;
        case "nombre_desc": tabla.order([0, "desc"]); break;
        case "stock_asc":   tabla.order([1, "asc"]); break;
        case "stock_desc":  tabla.order([1, "desc"]); break;
        case "estado":      tabla.order([3, "asc"]); break;
    }

    tabla.draw();
}


// Eventos
[qBuscar, qEstado, qActivo, qOrden].forEach(el => {
    el.addEventListener("input", applyFilters);
    el.addEventListener("change", applyFilters);
});

btnLimpiar.addEventListener("click", () => {
    qBuscar.value = "";
    qEstado.value = "all";
    qActivo.value = "1";
    qOrden.value  = "nombre_asc";
    tabla.search("");
    tabla.draw();
});
  /* =========================================================
     NUEVA
  ========================================================= */

  const btnNuevaMP = $("#btnNuevaMP");
  const formMP = $("#formMP");

  const mp_id = $("#mp_id");
  const mp_nombre = $("#mp_nombre");
  const mp_unidad = $("#mp_unidad");
  const mp_stock_actual = $("#mp_stock_actual");
  const mp_stock_minimo = $("#mp_stock_minimo");
  const mp_activo = $("#mp_activo");
  const mp_nota = $("#mp_nota");

  btnNuevaMP.addEventListener("click", () => {
    formMP.reset();
    mp_id.value = "";
    mp_activo.checked = true;

    $("#modalTitle").textContent = "Nueva materia prima";
    $("#modalSub").textContent = "Completa los datos básicos";

    openModal(modalMP);
  });

  /* =========================================================
     EDITAR
  ========================================================= */

  jQuery("#tablaMP").on("click", ".editar", function () {
    const id = jQuery(this).data("id");

    jQuery.get("api/obtener.php", { id }, function (data) {

      mp_id.value = data.idmateria_prima;
      mp_nombre.value = data.nombre;
      mp_unidad.value = data.unidad_medida_idunidad_medida;
      mp_stock_actual.value = data.stock_actual;
      mp_stock_minimo.value = data.stock_minimo;
      mp_activo.checked = data.activo == 1;
      mp_nota.value = data.nota || "";

      $("#modalTitle").textContent = "Editar materia prima";
      $("#modalSub").textContent = "Actualiza la información";

      openModal(modalMP);

    }, "json");
  });

  /* =========================================================
     GUARDAR
  ========================================================= */

  formMP.addEventListener("submit", function (e) {
    e.preventDefault();

    const payload = {
      id: mp_id.value,
      nombre: mp_nombre.value.trim(),
      unidad: mp_unidad.value,
      stock_actual: mp_stock_actual.value || 0,
      stock_minimo: mp_stock_minimo.value || 0,
      activo: mp_activo.checked ? 1 : 0,
      nota: mp_nota.value.trim()
    };

    if (!payload.nombre) {
      showToast("El nombre es obligatorio");
      return;
    }

    jQuery.post("api/guardar.php", payload, function (resp) {

      if (resp.success) {
        closeModal(modalMP);
        tabla.ajax.reload(null, false);
        showToast("Guardado correctamente");
      } else {
        showToast("No se pudo guardar");
      }

    }, "json");
  });

  /* =========================================================
     ELIMINAR
  ========================================================= */

  let deletingId = null;

  jQuery("#tablaMP").on("click", ".eliminar", function () {
    deletingId = jQuery(this).data("id");

    const row = tabla.row(jQuery(this).closest("tr")).data();
    $("#delName").textContent = `¿Eliminar "${row.nombre}"?`;

    openModal(modalDelete);
  });

  $("#btnConfirmDelete").addEventListener("click", function () {

    jQuery.post("api/eliminar.php", { id: deletingId }, function (resp) {

      if (resp.success) {
        closeModal(modalDelete);
        tabla.ajax.reload(null, false);
        showToast("Eliminado correctamente");
      } else {
        showToast("No se pudo eliminar");
      }

    }, "json");
  });

})();
</script>



