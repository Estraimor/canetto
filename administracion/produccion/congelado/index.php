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

// Ingredientes por receta (para precargar en cards y cálculo de consumo)
$ingredientesPorReceta = [];
if (!empty($productos)) {
    $recetaIds    = array_unique(array_column($productos, 'idrecetas'));
    $placeholders = implode(',', array_fill(0, count($recetaIds), '?'));
    $stmtIng = $pdo->prepare("
        SELECT
            ri.recetas_idrecetas,
            mp.idmateria_prima,
            mp.nombre,
            ri.cantidad AS cantidad_base,
            COALESCE(um.abreviatura, um.nombre, '') AS unidad,
            COALESCE(mp.stock_actual, 0) AS stock
        FROM receta_ingredientes ri
        INNER JOIN materia_prima mp ON mp.idmateria_prima = ri.materia_prima_idmateria_prima
        LEFT JOIN unidad_medida um ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
        WHERE ri.recetas_idrecetas IN ($placeholders)
        ORDER BY mp.nombre ASC
    ");
    $stmtIng->execute($recetaIds);
    foreach ($stmtIng->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $ingredientesPorReceta[$row['recetas_idrecetas']][] = $row;
    }
}
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
        <?php foreach ($productos as $p):
          $ings = $ingredientesPorReceta[$p['idrecetas']] ?? [];
          $ingJson = htmlspecialchars(json_encode($ings), ENT_QUOTES);
        ?>
          <div class="receta-card" id="card-<?= $p['idproductos'] ?>">

            <!-- Checkbox selección lote -->
            <label class="card-check-wrap" title="Seleccionar para producir en lote">
              <input type="checkbox"
                class="card-check"
                data-producto-id="<?= (int)$p['idproductos'] ?>"
                data-receta-id="<?= (int)$p['idrecetas'] ?>"
                data-nombre="<?= htmlspecialchars($p['producto_nombre']) ?>"
                data-galletas="<?= (float)$p['cantidad_galletas'] ?>"
                data-ingredientes="<?= $ingJson ?>">
              <span class="card-check-box"></span>
            </label>

            <div class="receta-nombre"><?= htmlspecialchars($p['producto_nombre']) ?></div>
            <div class="receta-info">
              <i class="fa-solid fa-cookie" style="color:#3498db;margin-right:4px"></i>
              <?= (int)$p['cantidad_galletas'] ?> galletas por lote
            </div>

            <!-- Botón expandir ingredientes -->
            <button class="btn-ing-toggle" onclick="toggleIngCard(<?= $p['idproductos'] ?>)" title="Ver materias primas">
              <i class="fa-solid fa-flask-vial" style="color:#64748b"></i>
              <span><?= count($ings) ?> ingrediente<?= count($ings) !== 1 ? 's' : '' ?></span>
              <i class="fa-solid fa-chevron-down card-chevron" id="chevron-<?= $p['idproductos'] ?>"></i>
            </button>

            <!-- Lista ingredientes expandible -->
            <div class="card-ing-list" id="card-ing-<?= $p['idproductos'] ?>" style="display:none">
              <?php foreach ($ings as $ing): ?>
              <div class="card-ing-row <?= $ing['stock'] < $ing['cantidad_base'] ? 'ing-faltante' : '' ?>">
                <span class="ing-nombre"><?= htmlspecialchars($ing['nombre']) ?></span>
                <span class="ing-cant"><?= $ing['cantidad_base'] ?> <?= htmlspecialchars($ing['unidad']) ?></span>
                <?php if ($ing['stock'] < $ing['cantidad_base']): ?>
                <span class="ing-badge-low"><i class="fa-solid fa-triangle-exclamation"></i> Stock bajo</span>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
              <?php if (empty($ings)): ?>
              <div class="card-ing-row" style="color:#94a3b8;font-style:italic">Sin ingredientes cargados</div>
              <?php endif; ?>
            </div>

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
              <th style="width:200px">Galletas a producir</th>
              <th style="width:110px">Máximo (receta)</th>
              <th style="width:60px"></th>
            </tr>
          </thead>
          <tbody id="loteTbody"></tbody>
        </table>
      </div>

      <!-- Consumo estimado de materias primas -->
      <div class="consumo-section">
        <button class="consumo-toggle" onclick="toggleConsumo()">
          <i class="fa-solid fa-flask-vial"></i>
          Consumo estimado de materias primas
          <i class="fa-solid fa-chevron-down" id="consumoChevron" style="margin-left:auto"></i>
        </button>
        <div id="consumoBody" style="display:none">
          <table class="consumo-table">
            <thead>
              <tr>
                <th>Materia prima</th>
                <th style="width:150px">A consumir</th>
                <th style="width:150px">Stock actual</th>
                <th style="width:100px">Estado</th>
              </tr>
            </thead>
            <tbody id="consumoTbody">
              <tr><td colspan="4" style="color:#94a3b8;text-align:center;padding:14px">Seleccioná productos para ver el consumo estimado</td></tr>
            </tbody>
          </table>
        </div>
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
        <input type="number" id="cantidad_galletas" min="1" step="1" id="cantidad_galletas">
        <div id="maxGalletasHint" class="max-hint" style="display:none"></div>
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
// { productoId: { recetaId, nombre, galletas, cantidad, ingredientes } }
const seleccionados = {};

// Datos de ingredientes por recetaId (precargados desde PHP)
const ingredientesData = {};
document.querySelectorAll(".card-check").forEach(chk => {
  try {
    const data = JSON.parse(chk.dataset.ingredientes || '[]');
    ingredientesData[chk.dataset.recetaId] = data;
  } catch(e) {}
});

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

    const inputCant = document.getElementById("cantidad_galletas");
    inputCant.value = Math.round(baseGalletas);
    inputCant.max   = Math.round(baseGalletas);

    const hint = document.getElementById("maxGalletasHint");
    hint.style.display = "block";
    hint.textContent   = `Máximo: ${Math.round(baseGalletas)} galletas (1 lote)`;

    document.getElementById("porcentaje").value = "100";

    abrirModal();
    calcularPreview();
  });
});

document.getElementById("porcentaje").addEventListener("change", function(){
  let cant = baseGalletas * (parseFloat(this.value) / 100);
  document.getElementById("cantidad_galletas").value = Math.round(cant);
  calcularPreview();
});

document.getElementById("cantidad_galletas").addEventListener("input", function(){
  const max = Math.round(baseGalletas);
  if(parseFloat(this.value) > max){
    this.value = max;
  }
  calcularPreview();
});

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
  if(cantidad > baseGalletas){
    Swal.fire({ icon:"warning", title:"Cantidad excedida", text:`No podés producir más de ${Math.round(baseGalletas)} galletas por lote` });
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
        recetaId:     this.dataset.recetaId,
        nombre:       this.dataset.nombre,
        galletas:     parseFloat(this.dataset.galletas),
        cantidad:     parseFloat(this.dataset.galletas),
        ingredientes: ingredientesData[this.dataset.recetaId] || []
      };
      card.classList.add("selected");
    } else {
      delete seleccionados[pid];
      card.classList.remove("selected");
    }
    actualizarLote();
  });
});

/* ─── TOGGLE INGREDIENTES EN CARD ─── */
function toggleIngCard(pid){
  const el  = document.getElementById("card-ing-" + pid);
  const chv = document.getElementById("chevron-" + pid);
  const open = el.style.display !== "none";
  el.style.display  = open ? "none" : "block";
  chv.style.transform = open ? "" : "rotate(180deg)";
}

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
  const ids   = Object.keys(seleccionados);
  const badge = document.getElementById("seleccionBadge");
  const panel = document.getElementById("panelLote");

  document.getElementById("seleccionCount").textContent = ids.length;
  badge.style.display = ids.length ? "flex" : "none";
  panel.style.display = ids.length ? ""    : "none";

  if(!ids.length){ actualizarConsumo(); return; }

  const tbody = document.getElementById("loteTbody");
  tbody.innerHTML = ids.map(pid => {
    const s   = seleccionados[pid];
    const max = Math.round(s.galletas);
    return `
      <tr id="lote-row-${pid}">
        <td class="lote-td-nombre">${s.nombre}</td>
        <td>
          <div class="lote-inp-wrap">
            <input type="number" min="1" step="1" max="${max}"
                   class="lote-inp"
                   value="${Math.round(s.cantidad)}"
                   data-pid="${pid}"
                   oninput="loteActualizarCant('${pid}', this)">
            <div class="lote-inp-bar">
              <div class="lote-inp-bar-fill" id="lbar-${pid}" style="width:${Math.round(s.cantidad/max*100)}%"></div>
            </div>
          </div>
        </td>
        <td class="lote-td-base">
          <span class="badge-max">${max} gall.</span>
        </td>
        <td>
          <button class="lote-btn-quitar" onclick="quitarDeLote('${pid}')" title="Quitar">
            <i class="fa fa-xmark"></i>
          </button>
        </td>
      </tr>`;
  }).join('');

  actualizarConsumo();
}

function loteActualizarCant(pid, input){
  const max = Math.round(seleccionados[pid].galletas);
  let val   = parseInt(input.value) || 1;
  if(val > max) { val = max; input.value = max; }
  if(val < 1)   { val = 1;   input.value = 1;   }
  seleccionados[pid].cantidad = val;
  const bar = document.getElementById("lbar-" + pid);
  if(bar) bar.style.width = Math.round(val/max*100) + "%";
  actualizarConsumo();
}

function quitarDeLote(pid){
  delete seleccionados[pid];
  const chk = document.querySelector(`.card-check[data-producto-id="${pid}"]`);
  if(chk) chk.checked = false;
  const card = document.getElementById("card-" + pid);
  if(card) card.classList.remove("selected");
  actualizarLote();
}

/* ─── CONSUMO ESTIMADO ─── */
let consumoVisible = false;
function toggleConsumo(){
  consumoVisible = !consumoVisible;
  document.getElementById("consumoBody").style.display    = consumoVisible ? "" : "none";
  document.getElementById("consumoChevron").style.transform = consumoVisible ? "rotate(180deg)" : "";
}

function actualizarConsumo(){
  const ids = Object.keys(seleccionados);
  const tbody = document.getElementById("consumoTbody");
  if(!tbody) return;

  if(!ids.length){
    tbody.innerHTML = `<tr><td colspan="4" style="color:#94a3b8;text-align:center;padding:14px">Seleccioná productos para ver el consumo estimado</td></tr>`;
    return;
  }

  // Agregar consumo por ingrediente
  const consumo = {}; // nombre => { cantidad, unidad, stock }
  ids.forEach(pid => {
    const s      = seleccionados[pid];
    const factor = s.cantidad / s.galletas;
    (s.ingredientes || []).forEach(i => {
      const cant = Math.round(parseFloat(i.cantidad_base) * factor * 100) / 100;
      if(!consumo[i.nombre]){
        consumo[i.nombre] = { cantidad: 0, unidad: i.unidad, stock: parseFloat(i.stock) };
      }
      consumo[i.nombre].cantidad = Math.round((consumo[i.nombre].cantidad + cant) * 100) / 100;
    });
  });

  const rows = Object.entries(consumo).map(([nombre, d]) => {
    const falta  = d.cantidad > d.stock;
    const pct    = d.stock > 0 ? Math.min(100, Math.round(d.cantidad / d.stock * 100)) : 100;
    const badgeClass = falta ? 'consumo-badge-err' : 'consumo-badge-ok';
    const badgeTxt   = falta
      ? `<i class="fa-solid fa-triangle-exclamation"></i> Insuficiente`
      : `<i class="fa-solid fa-check"></i> OK`;
    return `<tr>
      <td class="lote-td-nombre">${nombre}</td>
      <td><b>${d.cantidad} ${d.unidad}</b></td>
      <td>
        ${d.stock} ${d.unidad}
        <div class="consumo-bar"><div class="consumo-bar-fill ${falta?'consumo-bar-err':''}" style="width:${pct}%"></div></div>
      </td>
      <td><span class="consumo-badge ${badgeClass}">${badgeTxt}</span></td>
    </tr>`;
  }).join('');

  tbody.innerHTML = rows || `<tr><td colspan="4" style="color:#94a3b8;text-align:center">Sin ingredientes registrados</td></tr>`;
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
