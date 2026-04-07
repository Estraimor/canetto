<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../../../config/conexion.php';
include '../../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$stmt = $pdo->query("
    SELECT
        p.idproductos,
        p.nombre AS producto_nombre,
        p.tipo,
        p.recetas_idrecetas,
        r.idrecetas,
        r.nombre AS receta_nombre,
        r.cantidad_galletas
    FROM productos p
    INNER JOIN recetas r
        ON r.idrecetas = p.recetas_idrecetas
    WHERE p.tipo = 'producto'
      AND p.activo = 1
    ORDER BY p.nombre ASC
");

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="congelado.css">

<div id="modulo-congelado">
  <div class="congelado-container">

    <div class="congelado-header">
      <h2 class="titulo-seccion">❄️ Producción - Congelado</h2>
      <div id="seleccionBadge" class="seleccion-badge" style="display:none">
        <i class="fa-solid fa-check"></i>
        <span id="seleccionCount">0</span> seleccionado(s)
        <button id="btnProducirLote" class="btn-lote">
          <i class="fa-solid fa-play"></i> Producir seleccionados
        </button>
        <button id="btnLimpiarSel" class="btn-lote-soft">
          <i class="fa-solid fa-xmark"></i> Limpiar
        </button>
      </div>
    </div>

    <!-- Grid de recetas -->
    <div class="recetas-grid">
      <?php if (!empty($productos)): ?>
        <?php foreach ($productos as $p): ?>
          <div class="receta-card" id="card-<?= $p['idproductos'] ?>">

            <!-- Checkbox selección lote -->
            <label class="card-check-wrap" title="Seleccionar para producir en lote">
              <input type="checkbox"
                class="card-check"
                data-producto-id="<?= (int)$p['idproductos'] ?>"
                data-receta-id="<?= (int)$p['idrecetas'] ?>"
                data-nombre="<?= htmlspecialchars($p['producto_nombre']) ?>"
                data-galletas="<?= (float)$p['cantidad_galletas'] ?>">
              <span class="card-check-box"></span>
            </label>

            <div class="receta-nombre"><?= htmlspecialchars($p['producto_nombre']) ?></div>
            <div class="receta-info">Produce <?= (int)$p['cantidad_galletas'] ?> galletas</div>

            <button
              class="btn-preparar"
              data-producto-id="<?= (int)$p['idproductos'] ?>"
              data-receta-id="<?= (int)$p['idrecetas'] ?>"
              data-nombre="<?= htmlspecialchars($p['producto_nombre']) ?>"
              data-galletas="<?= (float)$p['cantidad_galletas'] ?>">
              <i class="fa-solid fa-snowflake"></i> Preparar
            </button>

          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="receta-card" style="grid-column: 1 / -1;">
          <div class="receta-nombre">Sin productos disponibles</div>
          <div class="receta-info">No hay productos activos con receta asociada.</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Panel de producción en lote -->
    <div id="panelLote" class="panel-lote" style="display:none">
      <div class="panel-lote-head">
        <div>
          <div class="panel-lote-title"><i class="fa-solid fa-layer-group"></i> Producción en lote</div>
          <div class="panel-lote-sub">Ajustá las cantidades y producí todo de una vez</div>
        </div>
        <button class="btn-lote-action" id="btnProducirTodos">
          <i class="fa-solid fa-play"></i> Producir todos
        </button>
      </div>

      <div class="panel-lote-body">
        <table class="lote-table">
          <thead>
            <tr>
              <th>Producto</th>
              <th style="width:160px">Galletas a producir</th>
              <th style="width:100px">Base receta</th>
              <th style="width:60px"></th>
            </tr>
          </thead>
          <tbody id="loteTbody"></tbody>
        </table>
      </div>
    </div>

  </div>
</div>


<!-- MODAL individual -->
<div class="modal-overlay" id="modalPreparar">
  <div class="modal-card">

    <h2 id="tituloReceta"></h2>

    <input type="hidden" id="producto_id">
    <input type="hidden" id="receta_id">

    <div class="form-grid">
      <div>
        <label>Porcentaje receta</label>
        <select id="porcentaje">
          <option value="100">100%</option>
          <option value="75">75%</option>
          <option value="50">50%</option>
          <option value="25">25%</option>
        </select>
      </div>
      <div>
        <label>Cantidad galletas</label>
        <input type="number" id="cantidad_galletas" min="1" step="1">
      </div>
    </div>

    <hr>
    <h4>Materia prima necesaria</h4>

    <table class="tabla-ingredientes">
      <thead>
        <tr>
          <th>Ingrediente</th>
          <th>Cantidad</th>
          <th>Unidad</th>
        </tr>
      </thead>
      <tbody id="tablaIngredientes"></tbody>
    </table>

    <div class="modal-actions">
      <button type="button" onclick="cerrarModal()">Cancelar</button>
      <button type="button" class="btn-confirmar" id="confirmarProduccion">
        Confirmar producción
      </button>
    </div>

  </div>
</div>


<script>
/* ===================================================
   CONGELADO — producción individual + en lote
=================================================== */
let recetaActual   = null;
let productoActual = null;
let baseGalletas   = 0;

// Estado de selección para lote
// { productoId: { recetaId, nombre, galletas, cantidad } }
const seleccionados = {};

/* ─── MODAL INDIVIDUAL ─── */
function abrirModal()  { document.getElementById("modalPreparar").classList.add("open"); }
function cerrarModal() { document.getElementById("modalPreparar").classList.remove("open"); }

document.querySelectorAll(".btn-preparar").forEach(btn => {
  btn.addEventListener("click", () => {
    productoActual = btn.dataset.productoId;
    recetaActual   = btn.dataset.recetaId;
    baseGalletas   = parseFloat(btn.dataset.galletas) || 0;

    document.getElementById("tituloReceta").innerText = "Preparar " + btn.dataset.nombre;
    document.getElementById("producto_id").value  = productoActual;
    document.getElementById("receta_id").value    = recetaActual;
    document.getElementById("cantidad_galletas").value = Math.round(baseGalletas);
    document.getElementById("porcentaje").value   = "100";

    abrirModal();
    calcularPreview();
  });
});

document.getElementById("porcentaje").addEventListener("change", function(){
  let cant = baseGalletas * (parseFloat(this.value) / 100);
  document.getElementById("cantidad_galletas").value = Math.round(cant);
  calcularPreview();
});

document.getElementById("cantidad_galletas").addEventListener("input", calcularPreview);

document.getElementById("confirmarProduccion").addEventListener("click", () => {
  const cantidad = parseFloat(document.getElementById("cantidad_galletas").value);
  if(!recetaActual || !productoActual){
    Swal.fire({ icon:"error", title:"Error interno", text:"No se encontró el producto o la receta" });
    return;
  }
  if(!cantidad || cantidad <= 0){
    Swal.fire({ icon:"warning", title:"Cantidad inválida", text:"Ingresá una cantidad válida" });
    return;
  }
  cerrarModal();
  ejecutarProduccion([{ receta: recetaActual, producto: productoActual, cantidad }]);
});

function calcularPreview(){
  const cantidad = parseFloat(document.getElementById("cantidad_galletas").value);
  if(!recetaActual || !cantidad || cantidad <= 0){
    document.getElementById("tablaIngredientes").innerHTML = "";
    return;
  }
  document.getElementById("tablaIngredientes").innerHTML = "<tr><td colspan='3'>⏳ Calculando...</td></tr>";

  $.ajax({
    url:"api/preview_receta.php", type:"POST",
    contentType:"application/json",
    data: JSON.stringify({ receta: recetaActual, cantidad }),
    success: function(data){
      if(typeof data === "string") try{ data = JSON.parse(data); }catch(e){}
      if(!data || data.status !== "ok"){
        document.getElementById("tablaIngredientes").innerHTML = "<tr><td colspan='3'>❌ Error backend</td></tr>";
        return;
      }
      if(!data.ingredientes || !data.ingredientes.length){
        document.getElementById("tablaIngredientes").innerHTML = "<tr><td colspan='3'>Sin ingredientes</td></tr>";
        return;
      }
      let html = "";
      data.ingredientes.forEach(i => {
        const style = i.faltante ? "style='color:#e74c3c;font-weight:600'" : "";
        html += `<tr ${style}><td>${i.nombre}</td><td>${i.cantidad}</td><td>${i.unidad}</td></tr>`;
      });
      document.getElementById("tablaIngredientes").innerHTML = html;

      const btn = document.getElementById("confirmarProduccion");
      if(!data.puede_producir){
        btn.disabled = true;
        btn.innerText = "❌ Stock insuficiente";
        btn.style.background = "#e74c3c";
      } else {
        btn.disabled = false;
        btn.innerText = "Confirmar producción";
        btn.style.background = "linear-gradient(135deg,#2ecc71,#27ae60)";
      }
    },
    error: function(){ document.getElementById("tablaIngredientes").innerHTML = "<tr><td colspan='3'>❌ Error servidor</td></tr>"; }
  });
}

/* ─── SELECCIÓN EN LOTE ─── */
document.querySelectorAll(".card-check").forEach(chk => {
  chk.addEventListener("change", function(){
    const pid = this.dataset.productoId;
    const card = document.getElementById("card-" + pid);

    if(this.checked){
      seleccionados[pid] = {
        recetaId: this.dataset.recetaId,
        nombre:   this.dataset.nombre,
        galletas: parseFloat(this.dataset.galletas),
        cantidad: parseFloat(this.dataset.galletas)
      };
      card.classList.add("selected");
    } else {
      delete seleccionados[pid];
      card.classList.remove("selected");
    }
    actualizarLote();
  });
});

document.getElementById("btnLimpiarSel").addEventListener("click", () => {
  document.querySelectorAll(".card-check").forEach(c => {
    c.checked = false;
    const card = document.getElementById("card-" + c.dataset.productoId);
    if(card) card.classList.remove("selected");
  });
  Object.keys(seleccionados).forEach(k => delete seleccionados[k]);
  actualizarLote();
});

document.getElementById("btnProducirLote").addEventListener("click", () => {
  document.getElementById("panelLote").scrollIntoView({ behavior:"smooth", block:"start" });
});

function actualizarLote(){
  const ids = Object.keys(seleccionados);
  const badge = document.getElementById("seleccionBadge");
  const panel = document.getElementById("panelLote");

  document.getElementById("seleccionCount").textContent = ids.length;
  badge.style.display = ids.length ? "flex" : "none";
  panel.style.display = ids.length ? "" : "none";

  if(!ids.length) return;

  const tbody = document.getElementById("loteTbody");
  tbody.innerHTML = ids.map(pid => {
    const s = seleccionados[pid];
    return `
      <tr id="lote-row-${pid}">
        <td class="lote-td-nombre">${s.nombre}</td>
        <td>
          <input type="number" min="1" step="1"
                 class="lote-inp"
                 value="${Math.round(s.cantidad)}"
                 data-pid="${pid}"
                 onchange="seleccionados['${pid}'].cantidad=parseFloat(this.value)||1">
        </td>
        <td class="lote-td-base">${Math.round(s.galletas)} u.</td>
        <td>
          <button class="lote-btn-quitar" onclick="quitarDeLote('${pid}')" title="Quitar">
            <i class="fa fa-xmark"></i>
          </button>
        </td>
      </tr>`;
  }).join('');
}

function quitarDeLote(pid){
  delete seleccionados[pid];
  const chk = document.querySelector(`.card-check[data-producto-id="${pid}"]`);
  if(chk) chk.checked = false;
  const card = document.getElementById("card-" + pid);
  if(card) card.classList.remove("selected");
  actualizarLote();
}

/* ─── PRODUCIR EN LOTE ─── */
document.getElementById("btnProducirTodos").addEventListener("click", () => {
  const ids = Object.keys(seleccionados);
  if(!ids.length) return;

  const items = ids.map(pid => ({
    receta:   parseInt(seleccionados[pid].recetaId),
    producto: parseInt(pid),
    cantidad: parseFloat(seleccionados[pid].cantidad)
  }));

  const nombres = items.map(i => `• ${seleccionados[i.producto]?.nombre || 'ID '+i.producto}: ${i.cantidad} galletas`).join('<br>');

  Swal.fire({
    title: 'Producir en lote',
    html: `<p style="margin-bottom:10px">Se van a producir:</p>
           <div style="text-align:left;background:#f0f9ff;border-radius:8px;padding:10px 16px;font-size:14px;line-height:1.8">${nombres}</div>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3498db',
    cancelButtonColor: '#9aa1ad',
    confirmButtonText: '<i class="fa fa-play"></i> Producir todo',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then(result => {
    if(!result.isConfirmed) return;
    ejecutarProduccion(items, true);
  });
});

/* ─── EJECUTAR PRODUCCIÓN ─── */
function ejecutarProduccion(items, esBulk = false){
  Swal.fire({
    title: esBulk ? "Procesando lote..." : "Procesando producción...",
    text: "Calculando ingredientes y validando stock",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  if(esBulk){
    fetch("api/producir_bulk.php", {
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body: JSON.stringify({ items })
    })
    .then(r => r.json())
    .then(res => {
      const ok  = res.resultados.filter(r => r.status === "ok");
      const err = res.resultados.filter(r => r.status !== "ok");

      let html = '';
      if(ok.length){
        html += `<div style="margin-bottom:10px"><b style="color:#27ae60">✓ Producidos (${ok.length})</b><br>
          ${ok.map(r => `• ${r.nombre}: ${r.cantidad} galletas`).join('<br>')}</div>`;
      }
      if(err.length){
        html += `<div><b style="color:#e74c3c">✗ Con errores (${err.length})</b><br>
          ${err.map(r => `• ${r.nombre}: ${r.mensaje}`).join('<br>')}</div>`;
      }

      Swal.fire({
        icon: err.length === 0 ? 'success' : (ok.length === 0 ? 'error' : 'warning'),
        title: err.length === 0 ? 'Lote producido correctamente' : 'Lote con errores',
        html,
        confirmButtonColor: '#3498db',
        confirmButtonText: 'Aceptar'
      }).then(() => location.reload());
    })
    .catch(() => {
      Swal.fire({ icon:"error", title:"Error del servidor", text:"No se pudo procesar el lote", confirmButtonColor:"#e74c3c" });
    });

  } else {
    const item = items[0];
    fetch("api/producir.php", {
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body: JSON.stringify(item)
    })
    .then(async r => {
      let text = await r.text();
      try { return JSON.parse(text); } catch(e){ throw new Error("Respuesta inválida"); }
    })
    .then(res => {
      if(res.status === "ok"){
        Swal.fire({
          icon:"success", title:"Producción realizada",
          text: res.mensaje || "Stock actualizado correctamente",
          confirmButtonColor:"#2ecc71"
        }).then(() => location.reload());
      } else {
        let detalle = "";
        if(res.detalle && Array.isArray(res.detalle)){
          detalle = "<br><br><b>Faltantes:</b><br>" + res.detalle.join("<br>");
        }
        Swal.fire({ icon:"error", title: res.mensaje || "Error en producción", html: detalle, confirmButtonColor:"#e74c3c" });
      }
    })
    .catch(() => {
      Swal.fire({ icon:"error", title:"Error del servidor", text:"No se pudo procesar la producción", confirmButtonColor:"#e74c3c" });
    });
  }
}
</script>

<?php include '../../../panel/dashboard/layaut/footer.php'; ?>
