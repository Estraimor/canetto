<?php

define('APP_BOOT', true);

require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$materias = $pdo->query("
    SELECT idmateria_prima, nombre, peso_unitario_g,
           um.abreviatura AS unidad_abrev
    FROM materia_prima mp
    JOIN unidad_medida um ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
    WHERE mp.activo = 1
    ORDER BY mp.nombre
")->fetchAll(PDO::FETCH_ASSOC);

$unidades = $pdo->query("
    SELECT idunidad_medida, abreviatura, nombre
    FROM unidad_medida
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<link rel="stylesheet" href="crear.css">

<div class="content-body">

    <div class="receta-crear">

        <!-- HEADER -->
        <div class="editor-header fade-up">

            <div class="editor-badge">
                Producción · Recetas
            </div>

            <h1>Nueva Receta</h1>

            <p>
                Crear formulación completa con ingredientes, cantidades
                y rendimiento estimado.
            </p>

        </div>


        <!-- FORMULARIO -->
        <form id="formReceta">

            <div class="editor-card">


                <!-- DATOS BASICOS -->
                <div class="form-grid">

                    <div class="form-group full">

                        <label for="nombre">
                            Nombre de la receta
                        </label>

                        <input
                            type="text"
                            name="nombre"
                            id="nombre"
                            placeholder="Ej: Cookie Red Velvet Premium"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label for="observacion">
                            Observación
                        </label>

                        <textarea
                            name="observacion"
                            id="observacion"
                            placeholder="Detalles internos, textura, cocción, rendimiento, observaciones de producción..."
                        ></textarea>

                    </div>

                </div>


                <!-- INGREDIENTES -->
                <div class="section-head">

                    <div>

                        <h3>Ingredientes</h3>

                        <p>
                            Agregá materias primas y definí las cantidades
                            para calcular el peso total.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="btn-outline"
                        onclick="agregarFila()"
                    >
                        <span>+</span> Agregar materia prima
                    </button>

                </div>


                <!-- TABLA INGREDIENTES -->
                <div class="ingredientes-wrap">

                    <table
                        class="tabla-ingredientes"
                        id="tablaIngredientes"
                    >

                        <thead>

                            <tr>
                                <th>Materia Prima</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th class="th-aporte">Aporte (g)</th>
                                <th class="th-accion">Acción</th>
                            </tr>

                        </thead>

                        <tbody></tbody>

                    </table>

                </div>


                <!-- RESUMEN PRODUCCION -->
                <div class="resumen-produccion" id="resumenProduccion">

                    <!-- PESO TOTAL -->
                    <div class="resumen-card resumen-card--peso">

                        <div class="resumen-card-top">
                            <span class="resumen-label">Peso Total de la Receta</span>
                            <div class="peso-meta">
                                <span class="peso-badge peso-badge--auto" id="pesoBadge">AUTO</span>
                                <button type="button" class="btn-reset-peso" id="btnResetPeso" title="Volver al cálculo automático">
                                    ↺ automático
                                </button>
                            </div>
                        </div>

                        <div class="resumen-input">
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="masa_total"
                                id="inputMasa"
                                placeholder="0.00"
                                required
                            >
                            <select name="unidad_medida_receta" id="selectUnidadMasa" required>
                                <option value="" disabled selected hidden>Unidad...</option>
                                <?php foreach($unidades as $u): ?>
                                    <option value="<?= $u['idunidad_medida'] ?>"><?= $u['abreviatura'] ?> (<?= $u['nombre'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="peso-hint" id="pesoHint">Suma automática de cantidades de ingredientes</div>

                    </div>


                    <!-- CANTIDAD DE PRODUCTO -->
                    <div class="resumen-card">

                        <div class="resumen-card-top">
                            <span class="resumen-label">Cantidad de Producto</span>
                        </div>

                        <div class="resumen-input">
                            <input
                                type="number"
                                name="cantidad_galletas"
                                id="inputGalletas"
                                placeholder="0"
                                min="1"
                                required
                            >
                            <span class="resumen-unit">un</span>
                        </div>

                        <div class="peso-hint">Unidades que rinde esta receta</div>

                    </div>


                    <!-- CONTADOR INGREDIENTES -->
                    <div class="resumen-card">

                        <div class="resumen-card-top">
                            <span class="resumen-label">Materias Primas</span>
                        </div>

                        <strong id="totalIngredientes">0</strong>

                        <div class="peso-hint">Ingredientes en esta receta</div>

                    </div>

                </div>


                <!-- BOTON GUARDAR -->
                <div class="form-actions">

                    <button
                        class="btn-primary"
                        type="submit"
                    >
                        Guardar Receta
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


<script>

const materias = <?= json_encode($materias, JSON_UNESCAPED_UNICODE) ?>;
const unidades = <?= json_encode($unidades, JSON_UNESCAPED_UNICODE) ?>;

// Mapa rápido idmateria_prima → { peso_unitario_g, unidad_abrev }
const materiasMap = {};
materias.forEach(m => {
    materiasMap[m.idmateria_prima] = {
        peso_unitario_g: m.peso_unitario_g != null ? parseFloat(m.peso_unitario_g) : null,
        unidad_abrev: m.unidad_abrev
    };
});

/* ===============================
   ESTADO GLOBAL
=============================== */
let pesoManual = false;

/* ===============================
   OPCIONES SELECT
=============================== */
function opcionesMaterias(){
    return materias.map(m => {
        const tag = m.peso_unitario_g != null
            ? ` (${m.unidad_abrev} · ${m.peso_unitario_g}g/un)`
            : ` (${m.unidad_abrev})`;
        return `<option value="${m.idmateria_prima}" data-peso="${m.peso_unitario_g ?? ''}" data-unidad="${m.unidad_abrev}">${m.nombre}${tag}</option>`;
    }).join('');
}

function opcionesUnidades(){
    return `<option value="" disabled selected hidden>Seleccione...</option>` +
        unidades.map(u =>
            `<option value="${u.idunidad_medida}">${u.abreviatura} (${u.nombre})</option>`
        ).join('');
}

/* ===============================
   APORTE EN GRAMOS DE UNA FILA
=============================== */
function calcularAporteFila(tr){
    const sel      = tr.querySelector("select[name='materia_prima[]']");
    const opt      = sel.options[sel.selectedIndex];
    const cantidad = parseFloat(tr.querySelector("input[name='cantidad[]']").value) || 0;
    const pesoUnit = opt?.dataset?.peso !== '' ? parseFloat(opt?.dataset?.peso) : null;

    if(pesoUnit != null){
        return { valor: cantidad * pesoUnit, tipo: 'calculado' };
    }
    // Si no tiene peso_unitario_g, tomamos la cantidad directamente (asumiendo g)
    return { valor: cantidad, tipo: 'directo' };
}

/* ===============================
   ACTUALIZAR CELDA APORTE
=============================== */
function actualizarAporteFila(tr){
    const tdAporte = tr.querySelector(".td-aporte");
    if(!tdAporte) return;

    const sel     = tr.querySelector("select[name='materia_prima[]']");
    const opt     = sel.options[sel.selectedIndex];
    const cantidad = parseFloat(tr.querySelector("input[name='cantidad[]']").value) || 0;
    const pesoUnit = opt?.dataset?.peso !== '' && opt?.dataset?.peso != null ? parseFloat(opt.dataset.peso) : null;
    const unidad  = opt?.dataset?.unidad || '';

    if(cantidad <= 0){
        tdAporte.innerHTML = `<span class="aporte-empty">—</span>`;
        return;
    }

    if(pesoUnit != null){
        const gramos = cantidad * pesoUnit;
        tdAporte.innerHTML = `
            <span class="aporte-calc">
                <strong>${gramos.toLocaleString('es-AR', {maximumFractionDigits:1})} g</strong>
                <small>${cantidad} × ${pesoUnit}g</small>
            </span>`;
    } else {
        tdAporte.innerHTML = `
            <span class="aporte-directo">
                <strong>${cantidad.toLocaleString('es-AR', {maximumFractionDigits:2})} ${unidad}</strong>
            </span>`;
    }
}

/* ===============================
   CÁLCULO AUTOMÁTICO DE PESO
=============================== */
function recalcularPesoTotal(){
    // Actualizar cada celda de aporte
    document.querySelectorAll("#tablaIngredientes tbody tr").forEach(tr => actualizarAporteFila(tr));

    if(pesoManual) return;

    let total = 0;
    document.querySelectorAll("#tablaIngredientes tbody tr").forEach(tr => {
        total += calcularAporteFila(tr).valor;
    });

    const inputMasa = document.getElementById("inputMasa");
    inputMasa.value = total > 0 ? total.toFixed(2) : '';

    const card = inputMasa.closest('.resumen-card');
    card.classList.remove('peso-pulse');
    void card.offsetWidth;
    card.classList.add('peso-pulse');

    actualizarHintPeso(total);
}

function actualizarHintPeso(total){
    const hint = document.getElementById("pesoHint");
    if(!pesoManual){
        hint.textContent = total > 0
            ? `Suma automática: ${total.toLocaleString('es-AR', {maximumFractionDigits:2})} g`
            : "Suma automática de cantidades de ingredientes";
    }
}

function activarModoManual(){
    pesoManual = true;
    const badge   = document.getElementById("pesoBadge");
    const btnReset = document.getElementById("btnResetPeso");
    const hint    = document.getElementById("pesoHint");
    badge.textContent = "MANUAL";
    badge.className   = "peso-badge peso-badge--manual";
    btnReset.style.display = "inline-flex";
    hint.textContent  = "Valor editado manualmente — el total no se recalcula automáticamente";
}

function resetearPesoAuto(){
    pesoManual = false;
    const badge   = document.getElementById("pesoBadge");
    const btnReset = document.getElementById("btnResetPeso");
    badge.textContent = "AUTO";
    badge.className   = "peso-badge peso-badge--auto";
    btnReset.style.display = "none";
    recalcularPesoTotal();
}

/* ===============================
   AGREGAR INGREDIENTE
=============================== */
function agregarFila(){
    const tbody = document.querySelector("#tablaIngredientes tbody");
    const tr    = document.createElement("tr");
    tr.classList.add("fila-ingrediente", "fila-enter");

    tr.innerHTML = `
        <td>
            <select name="materia_prima[]" required>
                ${opcionesMaterias()}
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="cantidad[]" placeholder="0.00" required>
        </td>
        <td>
            <select name="unidad[]" required>
                ${opcionesUnidades()}
            </select>
        </td>
        <td class="td-aporte">
            <span class="aporte-empty">—</span>
        </td>
        <td class="td-accion">
            <button type="button" onclick="eliminarFila(this)" class="btn-delete" title="Eliminar ingrediente">✕</button>
        </td>
    `;

    tbody.appendChild(tr);

    // Recalcular al cambiar cantidad o materia prima
    tr.querySelector("input[name='cantidad[]']").addEventListener("input", recalcularPesoTotal);
    tr.querySelector("select[name='materia_prima[]']").addEventListener("change", recalcularPesoTotal);

    setTimeout(() => tr.classList.remove("fila-enter"), 300);
    actualizarContadorIngredientes();
    recalcularPesoTotal();
}

/* ===============================
   ELIMINAR INGREDIENTE
=============================== */
function eliminarFila(btn){
    const tr = btn.closest("tr");
    tr.classList.add("fila-exit");
    setTimeout(() => {
        tr.remove();
        actualizarContadorIngredientes();
        recalcularPesoTotal();
    }, 200);
}

/* ===============================
   CONTADOR INGREDIENTES
=============================== */
function actualizarContadorIngredientes(){
    const total = document.querySelectorAll("#tablaIngredientes tbody tr").length;
    document.getElementById("totalIngredientes").innerText = total;
}

/* ===============================
   GUARDAR RECETA (AJAX)
=============================== */
document.getElementById("formReceta").addEventListener("submit", async function(e){
    e.preventDefault();

    const filas = document.querySelectorAll("#tablaIngredientes tbody tr");
    if(!filas.length){
        Swal.fire({ icon:"warning", title:"Faltan ingredientes", text:"Debés agregar al menos una materia prima" });
        return;
    }

    let cantidadValida = false;
    document.querySelectorAll("input[name='cantidad[]']").forEach(inp => {
        if((parseFloat(inp.value) || 0) > 0) cantidadValida = true;
    });
    if(!cantidadValida){
        Swal.fire({ icon:"warning", title:"Cantidad inválida", text:"Ingresá al menos una cantidad mayor a 0" });
        return;
    }

    const formData = new FormData(this);
    try{
        const res  = await fetch("./api/guardar_receta.php", { method:"POST", body:formData });
        const data = await res.json();
        if(data.status === "ok"){
            Swal.fire({ icon:"success", title:"Receta creada", text:data.mensaje, confirmButtonColor:"#c88e99" })
                .then(() => { window.location = "index.php"; });
        }else{
            Swal.fire({ icon:"error", title:"Error", text:data.mensaje });
        }
    }catch(err){
        Swal.fire({ icon:"error", title:"Error del servidor", text:"No se pudo guardar la receta" });
    }
});

/* ===============================
   INICIO
=============================== */
window.addEventListener("DOMContentLoaded", () => {
    document.getElementById("inputMasa").addEventListener("input", activarModoManual);
    document.getElementById("btnResetPeso").addEventListener("click", resetearPesoAuto);
    agregarFila();
});

</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>