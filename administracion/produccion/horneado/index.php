<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../../../config/conexion.php';
include '../../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

// Productos con stock congelado
$stmt = $pdo->query("
    SELECT
        p.idproductos,
        p.nombre,
        sp.stock_actual AS stock_congelado
    FROM productos p
    INNER JOIN stock_productos sp
        ON sp.productos_idproductos = p.idproductos
        AND sp.tipo_stock = 'CONGELADO'
    WHERE sp.stock_actual > 0
    ORDER BY p.nombre ASC
");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para el modo caja: solo productos de tipo 'box' con total de galletas
$stmtTodos = $pdo->query("
    SELECT
        p.idproductos,
        p.nombre,
        COALESCE(sp.stock_actual, 0) AS stock_congelado,
        COALESCE(SUM(bp.cantidad), 0) AS galletas_por_caja
    FROM productos p
    LEFT JOIN stock_productos sp
        ON sp.productos_idproductos = p.idproductos
        AND sp.tipo_stock = 'CONGELADO'
    LEFT JOIN box_productos bp
        ON bp.producto_box = p.idproductos
    WHERE p.activo = 1 AND p.tipo = 'box'
    GROUP BY p.idproductos, p.nombre, sp.stock_actual
    ORDER BY p.nombre ASC
");
$todosProductos = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="horneado.css">

<div class="horneado-container">

  <!-- Header -->
  <div class="horneado-header">
    <div class="titulo-horneado">🔥 Producción - Horneado</div>
    <div id="hornSelBadge" class="horn-sel-badge" style="display:none">
      <i class="fa-solid fa-check"></i>
      <span id="hornSelCount">0</span> seleccionado(s)
      <button id="btnHornearLote" class="btn-horn-lote">
        <i class="fa-solid fa-fire"></i> Hornear seleccionados
      </button>
      <button id="btnHornLimpiar" class="btn-horn-soft">
        <i class="fa-solid fa-xmark"></i> Limpiar
      </button>
    </div>
  </div>

  <!-- Grid de productos -->
  <div class="horneado-grid">
    <?php foreach ($productos as $p):
      $sinStock = $p['stock_congelado'] <= 0;
    ?>
    <div class="horneado-card <?= $sinStock ? 'sin-stock' : '' ?>" id="hcard-<?= $p['idproductos'] ?>">

      <!-- Checkbox lote -->
      <?php if(!$sinStock): ?>
      <label class="horn-check-wrap" title="Seleccionar para hornear en lote">
        <input type="checkbox"
          class="horn-check"
          data-id="<?= $p['idproductos'] ?>"
          data-stock="<?= $p['stock_congelado'] ?>"
          data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
        <span class="horn-check-box"></span>
      </label>
      <?php endif; ?>

      <div class="producto-nombre"><?= htmlspecialchars($p['nombre']) ?></div>
      <div class="stock-info">
        Stock congelado:
        <span class="badge-stock <?= $sinStock ? 'badge-low' : 'badge-ok' ?>">
          <?= number_format($p['stock_congelado'], 2) ?>
        </span>
      </div>

      <button
        class="btn-hornear"
        data-id="<?= $p['idproductos'] ?>"
        data-stock="<?= $p['stock_congelado'] ?>"
        data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
        <?= $sinStock ? 'disabled' : '' ?>>
        🔥 Hornear
      </button>

    </div>
    <?php endforeach; ?>

    <?php if(empty($productos)): ?>
    <div class="horneado-card" style="grid-column:1/-1;text-align:center;opacity:.6">
      <div class="producto-nombre">Sin stock congelado disponible</div>
      <div class="stock-info">No hay productos listos para hornear.</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Panel de lote hornear -->
  <div id="panelHornLote" class="panel-horn-lote" style="display:none">
    <div class="panel-horn-head">
      <div>
        <div class="panel-horn-title"><i class="fa-solid fa-layer-group"></i> Horneado en lote</div>
        <div class="panel-horn-sub">Ajustá las cantidades y horneá todo de una vez</div>
      </div>
      <button class="btn-horn-action" id="btnHornearTodos">
        <i class="fa-solid fa-fire"></i> Hornear todos
      </button>
    </div>
    <div class="panel-horn-body">
      <table class="horn-lote-table">
        <thead>
          <tr>
            <th>Producto</th>
            <th style="width:180px">Cantidad a hornear</th>
            <th style="width:140px">Stock disponible</th>
            <th style="width:60px"></th>
          </tr>
        </thead>
        <tbody id="hornLoteTbody"></tbody>
      </table>
    </div>
  </div>

  <!-- ===================================================
       MODO CAJA
  =================================================== -->
  <div class="modo-caja-section">
    <div class="modo-caja-head">
      <div>
        <div class="modo-caja-title"><i class="fa-solid fa-box-open"></i> Hornear por caja</div>
        <div class="modo-caja-sub">Agregá los boxes que querés hornear y el sistema calcula el total de galletas</div>
      </div>
    </div>

    <!-- Fila de selección -->
    <div class="modo-caja-body">

      <div class="mc-field" style="flex:2">
        <label>Producto (box)</label>
        <select id="mcProducto">
          <option value="">— Seleccioná un box —</option>
          <?php foreach($todosProductos as $p): ?>
            <option value="<?= $p['idproductos'] ?>"
                    data-stock="<?= $p['stock_congelado'] ?>"
                    data-galletas="<?= (int)$p['galletas_por_caja'] ?>"
                    data-nombre="<?= htmlspecialchars($p['nombre']) ?>">
              <?= htmlspecialchars($p['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mc-field">
        <label>Galletas por caja</label>
        <input type="number" id="mcGalletasPorCaja" min="1" step="1" placeholder="—">
      </div>

      <div class="mc-field">
        <label>Cantidad de cajas</label>
        <input type="number" id="mcCantCajas" min="1" step="1" placeholder="Ej: 1" value="1">
      </div>

      <div class="mc-field" style="justify-content:flex-end;align-items:flex-end">
        <button class="btn-horn-action" id="btnMCAgregar" disabled style="white-space:nowrap">
          <i class="fa-solid fa-plus"></i> Agregar
        </button>
      </div>

    </div>

    <div id="mcAlertaAdd" class="mc-warning" style="display:none;margin:0 0 10px 0"></div>

    <!-- Lista de boxes agregados -->
    <div id="mcListaWrap" style="display:none">
      <table class="horn-lote-table">
        <thead>
          <tr>
            <th>Box</th>
            <th style="width:130px">Galletas/caja</th>
            <th style="width:130px">Cajas</th>
            <th style="width:130px">Total galletas</th>
            <th style="width:130px">Stock disp.</th>
            <th style="width:50px"></th>
          </tr>
        </thead>
        <tbody id="mcListaTbody"></tbody>
        <tfoot>
          <tr>
            <td colspan="3" style="text-align:right;font-weight:600;padding:8px 12px">Total a hornear:</td>
            <td id="mcTotalFoot" style="font-weight:700;color:#ff7a18;padding:8px 12px">0 galletas</td>
            <td colspan="2"></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="mc-footer">
      <div id="mcWarning" class="mc-warning" style="display:none"></div>
      <button class="btn-horn-action" id="btnMCHornear" disabled>
        <i class="fa-solid fa-fire"></i> <span id="btnMCLabel">Hornear</span>
      </button>
    </div>

  </div><!-- /modo-caja-section -->

</div><!-- /horneado-container -->


<!-- Modal individual -->
<div id="modalHorneado" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3 id="modalTitulo"></h3>
      <span class="cerrar" onclick="cerrarModalHorn()">×</span>
    </div>
    <div class="modal-body">
      <input type="hidden" id="producto_id">
      <p>Stock disponible: <strong id="stockDisponible"></strong></p>
      <label>Cantidad a hornear</label>
      <input type="number" id="cantidadHornear" min="1" step="1">
      <div id="errorHorneado" class="error"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancelar" onclick="cerrarModalHorn()">Cancelar</button>
      <button class="btn-confirmar" onclick="confirmarHorneadoIndividual()">Confirmar</button>
    </div>
  </div>
</div>


<?php include '../../../panel/dashboard/layaut/footer.php'; ?>

<script>
/* ===================================================
   HORNEADO — individual + lote + modo caja
=================================================== */
let hProductoActual = null;
let hStockActual    = 0;

/* ─── MODAL INDIVIDUAL ─── */
document.querySelectorAll(".btn-hornear").forEach(btn => {
  btn.addEventListener("click", () => {
    hProductoActual = btn.dataset.id;
    hStockActual    = parseFloat(btn.dataset.stock);

    document.getElementById("modalTitulo").innerText = "🔥 Hornear " + btn.dataset.nombre;
    document.getElementById("producto_id").value     = hProductoActual;
    document.getElementById("stockDisponible").innerText = hStockActual;
    document.getElementById("cantidadHornear").value = "";
    document.getElementById("errorHorneado").innerText  = "";
    document.getElementById("cantidadHornear").max      = hStockActual;

    document.getElementById("modalHorneado").classList.add("open");
  });
});

function cerrarModalHorn(){
  document.getElementById("modalHorneado").classList.remove("open");
}

function confirmarHorneadoIndividual(){
  const cantidad = parseFloat(document.getElementById("cantidadHornear").value);
  const errEl    = document.getElementById("errorHorneado");
  errEl.innerText = "";

  if(!cantidad || cantidad <= 0){ errEl.innerText = "Ingresá una cantidad válida"; return; }
  if(cantidad > hStockActual){    errEl.innerText = "No podés hornear más que el stock disponible"; return; }

  cerrarModalHorn();

  Swal.fire({
    title: '¿Confirmar horneado?',
    text: `Vas a hornear ${cantidad} galletas`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: '🔥 Hornear',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ff7a18',
    cancelButtonColor: '#9aa1ad',
    reverseButtons: true
  }).then(result => {
    if(!result.isConfirmed) return;
    ejecutarHorneado([{ producto_id: parseInt(hProductoActual), cantidad }]);
  });
}

/* ─── SELECCIÓN EN LOTE ─── */
const hornSeleccionados = {}; // { productoId: { nombre, stock, cantidad } }

document.querySelectorAll(".horn-check").forEach(chk => {
  chk.addEventListener("change", function(){
    const pid  = this.dataset.id;
    const card = document.getElementById("hcard-" + pid);

    if(this.checked){
      hornSeleccionados[pid] = {
        nombre:   this.dataset.nombre,
        stock:    parseFloat(this.dataset.stock),
        cantidad: parseFloat(this.dataset.stock) // default: todo el stock
      };
      card.classList.add("selected");
    } else {
      delete hornSeleccionados[pid];
      card.classList.remove("selected");
    }
    actualizarPanelLote();
  });
});

document.getElementById("btnHornLimpiar").addEventListener("click", () => {
  document.querySelectorAll(".horn-check").forEach(c => {
    c.checked = false;
    const card = document.getElementById("hcard-" + c.dataset.id);
    if(card) card.classList.remove("selected");
  });
  Object.keys(hornSeleccionados).forEach(k => delete hornSeleccionados[k]);
  actualizarPanelLote();
});

document.getElementById("btnHornearLote").addEventListener("click", () => {
  document.getElementById("panelHornLote").scrollIntoView({ behavior:"smooth", block:"start" });
});

function actualizarPanelLote(){
  const ids   = Object.keys(hornSeleccionados);
  const badge = document.getElementById("hornSelBadge");
  const panel = document.getElementById("panelHornLote");

  document.getElementById("hornSelCount").textContent = ids.length;
  badge.style.display = ids.length ? "flex" : "none";
  panel.style.display = ids.length ? "" : "none";

  if(!ids.length) return;

  const tbody = document.getElementById("hornLoteTbody");
  tbody.innerHTML = ids.map(pid => {
    const s = hornSeleccionados[pid];
    return `
      <tr id="hrow-${pid}">
        <td class="lote-td-nombre">${s.nombre}</td>
        <td>
          <input type="number" min="1" step="1" max="${s.stock}"
                 class="lote-inp"
                 value="${Math.floor(s.cantidad)}"
                 data-pid="${pid}"
                 oninput="hornSeleccionados['${pid}'].cantidad=Math.min(parseFloat(this.value)||1,${s.stock})">
        </td>
        <td>
          <span class="badge-stock badge-ok">${s.stock}</span>
        </td>
        <td>
          <button class="lote-btn-quitar" onclick="quitarHornLote('${pid}')" title="Quitar">
            <i class="fa fa-xmark"></i>
          </button>
        </td>
      </tr>`;
  }).join('');
}

function quitarHornLote(pid){
  delete hornSeleccionados[pid];
  const chk = document.querySelector(`.horn-check[data-id="${pid}"]`);
  if(chk) chk.checked = false;
  const card = document.getElementById("hcard-" + pid);
  if(card) card.classList.remove("selected");
  actualizarPanelLote();
}

document.getElementById("btnHornearTodos").addEventListener("click", () => {
  const ids = Object.keys(hornSeleccionados);
  if(!ids.length) return;

  const items = ids.map(pid => ({
    producto_id: parseInt(pid),
    cantidad:    parseFloat(hornSeleccionados[pid].cantidad)
  }));

  const resumen = items.map(i => `• ${hornSeleccionados[i.producto_id]?.nombre}: ${i.cantidad} galletas`).join('<br>');

  Swal.fire({
    title: 'Hornear en lote',
    html: `<p style="margin-bottom:10px">Se van a hornear:</p>
           <div style="text-align:left;background:#fff5ee;border-radius:8px;padding:10px 16px;font-size:14px;line-height:1.8">${resumen}</div>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#ff7a18',
    cancelButtonColor: '#9aa1ad',
    confirmButtonText: '🔥 Hornear todo',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then(result => {
    if(!result.isConfirmed) return;
    ejecutarHorneado(items, true);
  });
});

/* ─── MODO CAJA ─── */
const mcProducto        = document.getElementById("mcProducto");
const mcGalletasPorCaja = document.getElementById("mcGalletasPorCaja");
const mcCantCajas       = document.getElementById("mcCantCajas");
const mcWarning         = document.getElementById("mcWarning");
const mcAlertaAdd       = document.getElementById("mcAlertaAdd");
const btnMCAgregar      = document.getElementById("btnMCAgregar");
const btnMCHornear      = document.getElementById("btnMCHornear");
const btnMCLabel        = document.getElementById("btnMCLabel");
const mcListaWrap       = document.getElementById("mcListaWrap");
const mcListaTbody      = document.getElementById("mcListaTbody");
const mcTotalFoot       = document.getElementById("mcTotalFoot");

// { productoId: { nombre, galletasPorCaja, cajas, stock } }
const mcLista = {};

mcProducto.addEventListener("change", () => {
  const opt     = mcProducto.options[mcProducto.selectedIndex];
  const galletas = parseInt(opt?.dataset.galletas || 0);
  mcAlertaAdd.style.display = "none";

  if(mcProducto.value && galletas > 0){
    mcGalletasPorCaja.value    = galletas;
    mcGalletasPorCaja.readOnly = true;
  } else {
    mcGalletasPorCaja.value    = "";
    mcGalletasPorCaja.readOnly = false;
  }
  actualizarBtnAgregar();
});

[mcGalletasPorCaja, mcCantCajas].forEach(el => el.addEventListener("input", actualizarBtnAgregar));

function actualizarBtnAgregar(){
  const porCaja = parseInt(mcGalletasPorCaja.value) || 0;
  const cajas   = parseInt(mcCantCajas.value) || 0;
  btnMCAgregar.disabled = !(mcProducto.value && porCaja > 0 && cajas > 0);
}

btnMCAgregar.addEventListener("click", () => {
  const pid      = mcProducto.value;
  const opt      = mcProducto.options[mcProducto.selectedIndex];
  const nombre   = opt.dataset.nombre;
  const stock    = parseFloat(opt.dataset.stock || 0);
  const porCaja  = parseInt(mcGalletasPorCaja.value) || 0;
  const cajas    = parseInt(mcCantCajas.value) || 0;
  const total    = porCaja * cajas;

  mcAlertaAdd.style.display = "none";

  if(mcLista[pid]){
    mcAlertaAdd.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Ese box ya está en la lista. Modificá la cantidad directamente en la tabla.`;
    mcAlertaAdd.style.display = "flex";
    return;
  }

  mcLista[pid] = { nombre, galletasPorCaja: porCaja, cajas, stock };
  renderMCLista();

  // Resetear selector
  mcProducto.value           = "";
  mcGalletasPorCaja.value    = "";
  mcGalletasPorCaja.readOnly = false;
  mcCantCajas.value          = "1";
  btnMCAgregar.disabled      = true;
});

function renderMCLista(){
  const ids = Object.keys(mcLista);
  mcListaWrap.style.display = ids.length ? "" : "none";

  let totalGeneral = 0;
  let hayStockError = false;

  mcListaTbody.innerHTML = ids.map(pid => {
    const s     = mcLista[pid];
    const total = s.galletasPorCaja * s.cajas;
    const sinStock = total > s.stock;
    totalGeneral += total;
    if(sinStock) hayStockError = true;

    return `<tr id="mcrow-${pid}">
      <td class="lote-td-nombre">${s.nombre}</td>
      <td style="text-align:center">${s.galletasPorCaja}</td>
      <td>
        <input type="number" min="1" step="1" class="lote-inp" value="${s.cajas}" data-pid="${pid}"
               oninput="mcActualizarCajas('${pid}', this.value)">
      </td>
      <td>
        <span class="badge-stock ${sinStock ? 'badge-low' : 'badge-ok'}">${total} galletas</span>
      </td>
      <td>
        <span class="badge-stock badge-ok">${s.stock}</span>
      </td>
      <td>
        <button class="lote-btn-quitar" onclick="mcQuitarItem('${pid}')" title="Quitar">
          <i class="fa fa-xmark"></i>
        </button>
      </td>
    </tr>`;
  }).join('');

  mcTotalFoot.textContent = totalGeneral + " galletas";

  if(ids.length === 0){
    mcWarning.style.display   = "none";
    btnMCHornear.disabled     = true;
    btnMCLabel.textContent    = "Hornear";
  } else if(hayStockError){
    mcWarning.style.display   = "flex";
    mcWarning.innerHTML       = `<i class="fa-solid fa-triangle-exclamation"></i> Uno o más boxes superan el stock disponible`;
    btnMCHornear.disabled     = true;
  } else {
    mcWarning.style.display   = "none";
    btnMCHornear.disabled     = false;
    const totalCajas = ids.reduce((s, pid) => s + mcLista[pid].cajas, 0);
    btnMCLabel.textContent    = `Hornear ${totalGeneral} galletas (${totalCajas} caja${totalCajas>1?'s':''})`;
  }
}

function mcActualizarCajas(pid, valor){
  const cajas = parseInt(valor) || 1;
  mcLista[pid].cajas = cajas;
  renderMCLista();
}

function mcQuitarItem(pid){
  delete mcLista[pid];
  renderMCLista();
}

btnMCHornear.addEventListener("click", () => {
  const ids = Object.keys(mcLista);
  if(!ids.length) return;

  const items = ids.map(pid => ({
    producto_id: parseInt(pid),
    cantidad: mcLista[pid].galletasPorCaja * mcLista[pid].cajas
  }));

  const resumen = ids.map(pid => {
    const s = mcLista[pid];
    const t = s.galletasPorCaja * s.cajas;
    return `• ${s.nombre}: ${s.cajas} caja${s.cajas>1?'s':''} × ${s.galletasPorCaja} = ${t} galletas`;
  }).join('<br>');

  Swal.fire({
    title: '¿Confirmar horneado?',
    html: `<div style="text-align:left;background:#fff5ee;border-radius:8px;padding:10px 16px;font-size:14px;line-height:1.8">${resumen}</div>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#ff7a18',
    cancelButtonColor: '#9aa1ad',
    confirmButtonText: '🔥 Hornear',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then(result => {
    if(!result.isConfirmed) return;
    ejecutarHorneado(items, items.length > 1);
  });
});

/* ─── EJECUTAR HORNEADO ─── */
function ejecutarHorneado(items, esBulk = false){
  Swal.fire({
    title: esBulk ? "Procesando lote..." : "Horneando...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  if(esBulk){
    fetch("api/procesar_horneado_bulk.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ items })
    })
    .then(r => r.json())
    .then(res => {
      const ok  = res.resultados.filter(r => r.status === "ok");
      const err = res.resultados.filter(r => r.status !== "ok");

      let html = '';
      if(ok.length){
        html += `<div style="margin-bottom:10px"><b style="color:#27ae60">✓ Horneados (${ok.length})</b><br>
          ${ok.map(r => `• ${r.nombre}: ${r.cantidad} galletas`).join('<br>')}</div>`;
      }
      if(err.length){
        html += `<div><b style="color:#e74c3c">✗ Con errores (${err.length})</b><br>
          ${err.map(r => `• ${r.nombre}: ${r.mensaje}`).join('<br>')}</div>`;
      }

      Swal.fire({
        icon: err.length === 0 ? 'success' : (ok.length === 0 ? 'error' : 'warning'),
        title: err.length === 0 ? 'Lote horneado correctamente' : 'Lote con errores',
        html,
        confirmButtonColor: '#ff7a18',
        confirmButtonText: 'Aceptar'
      }).then(() => location.reload());
    })
    .catch(() => {
      Swal.fire({ icon:"error", title:"Error del servidor", text:"No se pudo procesar el lote" });
    });

  } else {
    fetch("api/procesar_horneado.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(items[0])
    })
    .then(r => r.json())
    .then(data => {
      if(data.status === "ok"){
        Swal.fire({
          icon:'success', title:'Horneado realizado',
          text: data.mensaje,
          timer: 1500, showConfirmButton: false
        }).then(() => location.reload());
      } else {
        Swal.fire({ icon:'error', title:'Error', text: data.mensaje });
      }
    })
    .catch(() => Swal.fire({ icon:'error', title:'Error', text:'Error de conexión' }));
  }
}
</script>
