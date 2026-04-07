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

// Para el modo caja: todos los productos (incluso sin stock para el selector)
$stmtTodos = $pdo->query("
    SELECT
        p.idproductos,
        p.nombre,
        COALESCE(sp.stock_actual, 0) AS stock_congelado
    FROM productos p
    LEFT JOIN stock_productos sp
        ON sp.productos_idproductos = p.idproductos
        AND sp.tipo_stock = 'CONGELADO'
    WHERE p.activo = 1
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
        <div class="modo-caja-sub">Elegí cuántas cajas querés hornear y el sistema calcula el total de galletas</div>
      </div>
    </div>

    <div class="modo-caja-body">

      <div class="mc-field">
        <label>Producto</label>
        <select id="mcProducto">
          <option value="">— Seleccioná un producto —</option>
          <?php foreach($todosProductos as $p): ?>
            <option value="<?= $p['idproductos'] ?>"
                    data-stock="<?= $p['stock_congelado'] ?>">
              <?= htmlspecialchars($p['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mc-field">
        <label>Galletas por caja</label>
        <input type="number" id="mcGalletasPorCaja" min="1" step="1" placeholder="Ej: 6" value="6">
      </div>

      <div class="mc-field">
        <label>Cantidad de cajas</label>
        <input type="number" id="mcCantCajas" min="1" step="1" placeholder="Ej: 6" value="1">
      </div>

      <div class="mc-resultado" id="mcResultado" style="display:none">
        <div class="mc-res-label">Total a hornear</div>
        <div class="mc-res-valor" id="mcTotal">—</div>
        <div class="mc-res-sub" id="mcStockInfo"></div>
      </div>

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
const mcProducto       = document.getElementById("mcProducto");
const mcGalletasPorCaja= document.getElementById("mcGalletasPorCaja");
const mcCantCajas      = document.getElementById("mcCantCajas");
const mcResultado      = document.getElementById("mcResultado");
const mcTotal          = document.getElementById("mcTotal");
const mcStockInfo      = document.getElementById("mcStockInfo");
const mcWarning        = document.getElementById("mcWarning");
const btnMCHornear     = document.getElementById("btnMCHornear");
const btnMCLabel       = document.getElementById("btnMCLabel");

function calcularMC(){
  const stock    = parseFloat(mcProducto.options[mcProducto.selectedIndex]?.dataset.stock || 0);
  const porCaja  = parseInt(mcGalletasPorCaja.value) || 0;
  const cajas    = parseInt(mcCantCajas.value) || 0;
  const total    = porCaja * cajas;

  if(!mcProducto.value || porCaja <= 0 || cajas <= 0){
    mcResultado.style.display = "none";
    mcWarning.style.display   = "none";
    btnMCHornear.disabled = true;
    return;
  }

  mcResultado.style.display = "flex";
  mcTotal.textContent = total + " galletas";
  mcStockInfo.textContent = `${cajas} caja${cajas>1?'s':''} × ${porCaja} galleta${porCaja>1?'s':''}`;

  if(total > stock){
    mcWarning.style.display = "flex";
    mcWarning.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Stock insuficiente (disponible: ${stock} galletas)`;
    btnMCHornear.disabled = true;
  } else {
    mcWarning.style.display = "none";
    btnMCHornear.disabled = false;
    btnMCLabel.textContent = `Hornear ${total} galletas (${cajas} caja${cajas>1?'s':''})`;
  }
}

[mcProducto, mcGalletasPorCaja, mcCantCajas].forEach(el => el.addEventListener("input", calcularMC));
[mcProducto, mcGalletasPorCaja, mcCantCajas].forEach(el => el.addEventListener("change", calcularMC));

btnMCHornear.addEventListener("click", () => {
  const pid    = mcProducto.value;
  const total  = (parseInt(mcGalletasPorCaja.value)||0) * (parseInt(mcCantCajas.value)||0);
  const cajas  = parseInt(mcCantCajas.value);
  const nombre = mcProducto.options[mcProducto.selectedIndex].text;

  if(!pid || total <= 0) return;

  Swal.fire({
    title: '¿Confirmar horneado?',
    html: `<p>Producto: <b>${nombre}</b></p>
           <p>${cajas} caja${cajas>1?'s':''} × ${mcGalletasPorCaja.value} galletas = <b>${total} galletas</b></p>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#ff7a18',
    cancelButtonColor: '#9aa1ad',
    confirmButtonText: '🔥 Hornear',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then(result => {
    if(!result.isConfirmed) return;
    ejecutarHorneado([{ producto_id: parseInt(pid), cantidad: total }]);
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
