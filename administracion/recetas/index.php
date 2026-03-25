<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

/* =========================
   RECETAS
========================= */

$sql = "
    SELECT 
        r.idrecetas,
        r.nombre,
        r.observacion,
        r.masa_total,
        r.cantidad_galletas,
        um.abreviatura AS unidad_receta,

        COUNT(ri.idreceta_ingredientes) AS total_ingredientes

    FROM recetas r

    LEFT JOIN receta_ingredientes ri
        ON ri.recetas_idrecetas = r.idrecetas

    LEFT JOIN unidad_medida um
        ON um.idunidad_medida = r.unidad_medida_idunidad_medida

    GROUP BY 
        r.idrecetas,
        r.nombre,
        r.observacion,
        r.masa_total,
        r.cantidad_galletas,
        um.abreviatura

    ORDER BY r.nombre ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="receta.css">

<div class="main-wrapper">

<div class="content-body">

<!-- HEADER -->
<div class="recetas-header">

    <div>
        <h1>Recetas</h1>
        <p>Gestión de formulaciones y composición de productos</p>
    </div>

    <a href="crear.php" class="btn-primary">
        <i class="fa-solid fa-plus"></i> Nueva receta
    </a>

</div>


<?php if (count($recetas) > 0): ?>

<div class="recetas-grid">

<?php foreach ($recetas as $r): ?>

<?php

$stmtIng = $pdo->prepare("
    SELECT 
        mp.nombre,
        ri.cantidad,
        um.abreviatura
    FROM receta_ingredientes ri
    INNER JOIN materia_prima mp
        ON mp.idmateria_prima = ri.materia_prima_idmateria_prima
    INNER JOIN unidad_medida um
        ON um.idunidad_medida = mp.unidad_medida_idunidad_medida
    WHERE ri.recetas_idrecetas = ?
");

$stmtIng->execute([$r['idrecetas']]);
$ingredientes = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="receta-card">

    <div class="receta-main" onclick="toggleReceta(event, <?= $r['idrecetas'] ?>)">

        <div class="receta-info">

            <h3>
                <?= htmlspecialchars($r['nombre']) ?>
            </h3>

            <span class="badge">
                <?= $r['total_ingredientes'] ?> ingredientes
            </span>

        </div>

        <div class="receta-actions">

            <i 
                class="fa-solid fa-chevron-down arrow"
                id="arrow-<?= $r['idrecetas'] ?>"
            ></i>

        </div>

    </div>


    <p class="receta-desc">
        <?= htmlspecialchars($r['observacion'] ?? 'Sin observaciones registradas') ?>
    </p>


    <div class="receta-meta">

        <?php if ($r['masa_total']): ?>

        <span class="meta-item">
            ⚖ <?= number_format($r['masa_total'],2,",",".") ?>
            <?= $r['unidad_receta'] ?> Producto base
        </span>

        <?php endif; ?>


        <?php if ($r['cantidad_galletas']): ?>

        <span class="meta-item">
            🍪 <?= $r['cantidad_galletas'] ?> unidades
        </span>

        <?php endif; ?>

    </div>


    <div 
        class="receta-expand"
        id="expand-<?= $r['idrecetas'] ?>"
    >

        <?php if (count($ingredientes) > 0): ?>

        <div class="ingredientes-list">

            <?php foreach ($ingredientes as $ing): ?>

            <div class="ingrediente-item">

                <span>
                    <?= htmlspecialchars($ing['nombre']) ?>
                </span>

                <strong>
                    <?= $ing['cantidad'] ?> <?= $ing['abreviatura'] ?>
                </strong>

            </div>

            <?php endforeach; ?>

        </div>

        <?php else: ?>

        <div class="sin-ingredientes">
            No tiene ingredientes cargados
        </div>

        <?php endif; ?>


        <div class="expand-actions">

            


            <button 
                class="btn-card ghost btnEditar"
                data-id="<?= $r['idrecetas'] ?>"
            >
                <i class="fa-solid fa-pen"></i> Editar
            </button>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

<?php else: ?>

<div class="empty-state">

    <i class="fa-solid fa-cookie-bite"></i>

    <h3>No hay recetas creadas</h3>

    <p>
        Comenzá creando tu primera formulación de producto.
    </p>

    <a href="crear.php" class="btn-primary">
        Crear receta
    </a>

</div>

<?php endif; ?>

</div>
</div>


<!-- =========================
     MODAL EDITAR RECETA
========================= -->

<div class="modal-receta" id="modalEditar">

    <div class="modal-overlay" onclick="cerrarModalReceta()"></div>

    <div class="modal-content">

        <h2>Editar Receta</h2>

        <form class="form-receta-editar" id="formEditarReceta">

            <input type="hidden" id="edit_id" name="id">

            <!-- NOMBRE -->

            <div class="form-row">

                <label>Nombre</label>

                <input
                    type="text"
                    id="edit_nombre"
                    name="nombre"
                    required
                >

            </div>


            <!-- OBSERVACION -->

            <div class="form-row">

                <label>Observación</label>

                <textarea
                    id="edit_observacion"
                    name="observacion"
                ></textarea>

            </div>


            <hr>


            


            <hr>


            <!-- INGREDIENTES -->

            <div class="ingredientes-header">

                <h3>Ingredientes</h3>

                <button
                    type="button"
                    onclick="agregarIngredienteEditar()"
                >
                    + Agregar ingrediente
                </button>

            </div>


            <div
                class="ingredientes-editor"
                id="ingredientesEditar"
            ></div>


            <!-- ESTADISTICAS RECETA -->

            <div class="receta-stats">

                <!-- TOTAL CREACION -->

                <div class="stat-card">

                    <label>Total creación</label>

                    <div class="stat-row">

                        <input
                            type="number"
                            step="0.01"
                            id="edit_masa_total"
                            name="masa_total"
                            placeholder="0.00"
                        >

                        <select
                            id="edit_unidad_medida"
                            name="unidad_medida"
                        >
                        </select>

                    </div>

                </div>


                <!-- CANTIDAD PRODUCTO -->

                <div class="stat-card">

                    <label>Cantidad de producto</label>

                    <div class="stat-row">

                        <input
                            type="number"
                            id="edit_cantidad_galletas"
                            name="cantidad_galletas"
                            placeholder="0"
                        >

                        <span class="stat-unit">un</span>

                    </div>

                </div>


                <!-- MATERIAS PRIMAS -->

                <div class="stat-card stat-materias">

                    <label>Materias primas</label>

                    <div class="stat-number" id="total_materias">
                        0
                    </div>

                </div>

            </div>

            <!-- ACCIONES -->

            <div class="form-actions">

                <button
                    type="button"
                    class="btn-cancelar"
                    onclick="cerrarModalReceta()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-guardar"
                >
                    Guardar cambios
                </button>

            </div>

        </form>

    </div>

</div>

<script>

/* =========================================
VARIABLES GLOBALES
========================================= */

let materiasGlobal = [];
let unidadesGlobal = [];


/* =========================================
INICIO
========================================= */

document.addEventListener("DOMContentLoaded", () => {

    /* evitar que botones internos disparen toggle */

    document.querySelectorAll(".btn-card, .btnEditar").forEach(btn => {
        btn.addEventListener("click", e => {
            e.stopPropagation();
        });
    });


    /* botones editar */

    document.querySelectorAll(".btnEditar").forEach(btn => {

        btn.addEventListener("click", () => {

            const id = btn.dataset.id;
            abrirModalEditar(id);

        });

    });


    /* arrancar todas cerradas */

    document.querySelectorAll(".receta-expand").forEach(el=>{
        el.classList.remove("open");
    });

    document.querySelectorAll(".arrow").forEach(el=>{
        el.classList.remove("rotate");
    });


    /* submit editar */

    const formEditar = document.getElementById("formEditarReceta");

    if(formEditar){

        formEditar.addEventListener("submit", guardarReceta);

    }

});


/* =========================================
EXPANDIR RECETA
========================================= */

function toggleReceta(event, id){

    event.stopPropagation();

    const expand = document.getElementById("expand-" + id);
    const arrow  = document.getElementById("arrow-" + id);

    if(!expand) return;

    const abierta = expand.classList.contains("open");

    /* cerrar todas */

    document.querySelectorAll(".receta-expand").forEach(el=>{
        el.classList.remove("open");
    });

    document.querySelectorAll(".arrow").forEach(el=>{
        el.classList.remove("rotate");
    });

    /* abrir solo la clickeada */

    if(!abierta){
        expand.classList.add("open");
        arrow.classList.add("rotate");
    }

}


/* =========================================
ABRIR MODAL EDITAR
========================================= */

async function abrirModalEditar(id){

    const modal = document.getElementById("modalEditar");
    if(!modal) return;

    modal.classList.add("open");
    document.body.style.overflow = "hidden";

    try{

        const res = await fetch("api/obtener_receta.php?id=" + id);
        const data = await res.json();

        if(!data.receta){
            alert("No se pudo cargar la receta");
            return;
        }

        /* guardar materias y unidades */

        materiasGlobal = data.materias || [];
        unidadesGlobal = data.unidades || [];

        const r = data.receta;

        /* =========================
        CAMPOS PRINCIPALES
        ========================= */

        document.getElementById("edit_id").value = r.idrecetas || "";
        document.getElementById("edit_nombre").value = r.nombre || "";
        document.getElementById("edit_observacion").value = r.observacion || "";

        document.getElementById("edit_masa_total").value = r.masa_total || "";
        document.getElementById("edit_cantidad_galletas").value = r.cantidad_galletas || "";

        /* =========================
        SELECT UNIDADES
        ========================= */

        const selectUnidad = document.getElementById("edit_unidad_medida");

        if(selectUnidad){
            selectUnidad.innerHTML = unidadesGlobal.map(u => `
                <option value="${u.idunidad_medida}"
                ${u.idunidad_medida == r.unidad_medida_idunidad_medida ? "selected":""}>
                    ${u.abreviatura}
                </option>
            `).join("");
        }

        /* =========================
        INGREDIENTES
        ========================= */

        const cont = document.getElementById("ingredientesEditar");
        cont.innerHTML = "";

        const ingredientes = data.ingredientes || [];

        ingredientes.forEach(ing => {

            const row = document.createElement("div");
            row.className = "ingrediente-row";

            row.innerHTML = `

                <select name="materia_prima[]">

                    ${materiasGlobal.map(m => `
                        <option value="${m.idmateria_prima}"
                        ${m.idmateria_prima == ing.idmateria_prima ? "selected":""}>
                        ${m.nombre}
                        </option>
                    `).join("")}

                </select>

                <input
                    type="number"
                    step="0.01"
                    name="cantidad[]"
                    value="${ing.cantidad}"
                >

                <select name="unidad[]">

                    ${unidadesGlobal.map(u => `
                        <option value="${u.idunidad_medida}"
                        ${u.idunidad_medida == ing.unidad_medida_idunidad_medida ? "selected":""}>
                        ${u.abreviatura}
                        </option>
                    `).join("")}

                </select>

                <button
                    type="button"
                    class="btn-eliminar-ingrediente"
                    onclick="this.parentElement.remove(); actualizarTotalMaterias();"
                >
                    ✖
                </button>

            `;

            cont.appendChild(row);

        });

        /* =========================
        TOTAL MATERIAS
        ========================= */

        const totalMaterias = document.getElementById("total_materias");

        if(totalMaterias){
            totalMaterias.innerText = ingredientes.length;
        }

    }catch(err){

        console.error(err);
        alert("Error cargando receta");

    }

}


function actualizarTotalMaterias(){

    const total = document.querySelectorAll("#ingredientesEditar .ingrediente-row").length;

    const cont = document.getElementById("total_materias");

    if(cont){
        cont.innerText = total;
    }

}

    /* =========================================
    AGREGAR INGREDIENTE
    ========================================= */

function agregarIngredienteEditar(){

    const cont = document.getElementById("ingredientesEditar");

    const row = document.createElement("div");
    row.className = "ingrediente-row";

    row.innerHTML = `

        <select name="materia_prima[]">

            ${materiasGlobal.map(m => `
                <option value="${m.idmateria_prima}">
                ${m.nombre}
                </option>
            `).join("")}

        </select>

        <input
            type="number"
            step="0.01"
            name="cantidad[]"
            value="0"
        >

        <select name="unidad[]">

            ${unidadesGlobal.map(u => `
                <option value="${u.idunidad_medida}">
                ${u.abreviatura}
                </option>
            `).join("")}

        </select>

        <button
            type="button"
            class="btn-eliminar-ingrediente"
            onclick="this.parentElement.remove(); actualizarTotalMaterias();"
        >
            ✖
        </button>

    `;

    cont.appendChild(row);
actualizarTotalMaterias();

}


/* =========================================
CERRAR MODAL
========================================= */

function cerrarModalReceta(){

    const modal = document.getElementById("modalEditar");

    if(!modal) return;

    modal.classList.remove("open");
    document.body.style.overflow = "auto";

}


/* =========================================
ESC
========================================= */

document.addEventListener("keydown", e => {

    if(e.key === "Escape"){
        cerrarModalReceta();
    }

});


/* =========================================
OVERLAY
========================================= */

document.addEventListener("click", e => {

    if(e.target.classList.contains("modal-overlay")){
        cerrarModalReceta();
    }

});


/* =========================================
GUARDAR RECETA
========================================= */

async function guardarReceta(e){

    e.preventDefault();

    const formData = new FormData(e.target);

    const res = await fetch("api/actualizar_receta.php",{
        method:"POST",
        body:formData
    });

    const data = await res.json();

    if(data.status === "ok"){

        cerrarModalReceta();

        Swal.fire({
            icon:"success",
            title:"Cambios guardados",
            text:"La receta se actualizó correctamente",
            confirmButtonColor:"#e85d75"
        }).then(()=>{
            location.reload();
        });

    }else{

        Swal.fire({
            icon:"error",
            title:"Error",
            text:data.mensaje
        });

    }

}

</script>


<?php include '../../panel/dashboard/layaut/footer.php'; ?>