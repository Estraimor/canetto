<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$materias = $pdo->query("
    SELECT idmateria_prima, nombre 
    FROM materia_prima 
    WHERE activo = 1 
    ORDER BY nombre
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
        
        <div class="editor-header fade-up">
            <div class="editor-badge">Producción · Recetas</div>
            <h1>Nueva Receta</h1>
            <p>Crear formulación completa con ingredientes, cantidades y rendimiento estimado.</p>
        </div>

        <form method="POST" action="guardar_receta.php" id="formReceta" class="fade-up delay-1">
            <div class="editor-card">

                <div class="form-grid">
                    <div class="form-group full">
                        <label for="nombre">Nombre de la receta</label>
                        <input type="text" name="nombre" id="nombre" placeholder="Ej: Cookie Red Velvet Premium" required>
                    </div>

                    <div class="form-group full">
                        <label for="observacion">Observación</label>
                        <textarea name="observacion" id="observacion" placeholder="Detalles internos, textura, cocción, rendimiento, observaciones de producción..."></textarea>
                    </div>
                </div>

                <div class="section-head">
                    <div>
                        <h3>Ingredientes</h3>
                        <p>Agregá materias primas y definí las cantidades para calcular el peso total.</p>
                    </div>
                    <button type="button" class="btn-outline" onclick="agregarFila()">
                        <span>+</span> Agregar materia prima
                    </button>
                </div>

                <div class="ingredientes-wrap">
                    <table class="tabla-ingredientes" id="tablaIngredientes">
                        <thead>
                            <tr>
                                <th>Materia Prima</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th class="th-accion">Acción</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="resumen-produccion" id="resumenProduccion">

    <div class="resumen-card">
    <span class="resumen-label">Total Masa</span>
    <div class="resumen-input">
        <input 
            type="number"
            step="0.01"
            name="masa_total"
            id="inputMasa"
            placeholder="0"
        >
        <span>kg</span>
    </div>
</div>

<div class="resumen-card">
    <span class="resumen-label">Cantidad de Galletas</span>
    <div class="resumen-input">
        <input 
            type="number"
            name="cantidad_galletas"
            id="inputGalletas"
            placeholder="0"
        >
        <span>un</span>
    </div>
</div>

<div class="resumen-card">
    <span class="resumen-label">Materias Primas</span>
    <strong id="totalIngredientes">0</strong>
</div>

</div>

                <div class="form-actions">
                    <button class="btn-primary" type="submit">
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


/* ===============================
   OPCIONES SELECT
================================*/

function opcionesMaterias(){
    return materias.map(m =>
        `<option value="${m.idmateria_prima}">${m.nombre}</option>`
    ).join('');
}

function opcionesUnidades(){
    return unidades.map(u =>
        `<option value="${u.idunidad_medida}">${u.abreviatura ?? u.nombre}</option>`
    ).join('');
}


/* ===============================
   AGREGAR INGREDIENTE
================================*/

function agregarFila(){

    const tbody = document.querySelector("#tablaIngredientes tbody");

    const tr = document.createElement("tr");

    tr.classList.add("fila-ingrediente","fila-enter");

    tr.innerHTML = `
        <td>
            <select name="materia_prima[]">
                ${opcionesMaterias()}
            </select>
        </td>

        <td>
            <input
                type="number"
                step="0.01"
                min="0"
                name="cantidad[]"
                placeholder="0.00"
                required
            >
        </td>

        <td>
            <select name="unidad[]">
                ${opcionesUnidades()}
            </select>
        </td>

        <td class="td-accion">
            <button
                type="button"
                onclick="eliminarFila(this)"
                class="btn-delete"
                title="Eliminar ingrediente"
            >
                ✕
            </button>
        </td>
    `;

    tbody.appendChild(tr);

    setTimeout(()=>{
        tr.classList.remove("fila-enter");
    },300);

    actualizarContadorIngredientes();
}


/* ===============================
   ELIMINAR INGREDIENTE
================================*/

function eliminarFila(btn){

    const tr = btn.closest("tr");

    tr.classList.add("fila-exit");

    setTimeout(()=>{
        tr.remove();
        actualizarContadorIngredientes();
    },200);

}


/* ===============================
   CONTADOR MATERIAS
================================*/

function actualizarContadorIngredientes(){

    const total =
        document.querySelectorAll("#tablaIngredientes tbody tr").length;

    document.getElementById("totalIngredientes").innerText = total;

}


/* ===============================
   VALIDACION FORMULARIO
================================*/

document
.getElementById("formReceta")
.addEventListener("submit",function(e){

    const filas =
        document.querySelectorAll("#tablaIngredientes tbody tr");

    if(!filas.length){

        e.preventDefault();

        alert("Debés agregar al menos una materia prima.");

        return;

    }

    let cantidadValida = false;

    document
    .querySelectorAll("input[name='cantidad[]']")
    .forEach(input=>{

        if((parseFloat(input.value)||0)>0){
            cantidadValida = true;
        }

    });

    if(!cantidadValida){

        e.preventDefault();

        alert("Ingresá al menos una cantidad válida mayor a 0.");

    }

});


/* ===============================
   INICIO
================================*/

window.addEventListener("DOMContentLoaded",()=>{
    agregarFila();
});

</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>