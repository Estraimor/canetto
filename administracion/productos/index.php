<?php

define('APP_BOOT', true);

require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

/* =========================
PRODUCTOS
========================= */

$stmtProductos = $pdo->query("
    SELECT 
        p.idproductos,
        p.nombre,
        p.precio,
        p.activo,
        p.tipo,
        p.recetas_idrecetas,
        r.nombre AS receta_nombre,

        COALESCE(MAX(CASE 
            WHEN sp.tipo_stock = 'CONGELADO' 
            THEN sp.stock_minimo 
        END), 0) AS min_congelado,

        COALESCE(MAX(CASE 
            WHEN sp.tipo_stock = 'HECHO' 
            THEN sp.stock_minimo 
        END), 0) AS min_hecho

    FROM productos p

    LEFT JOIN recetas r 
        ON r.idrecetas = p.recetas_idrecetas

    LEFT JOIN stock_productos sp
        ON sp.productos_idproductos = p.idproductos

    WHERE p.tipo = 'producto'

    GROUP BY p.idproductos
    ORDER BY p.nombre ASC
");

$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);


/* =========================
BOX
========================= */

$stmtBox = $pdo->query("
    SELECT 
        b.idproductos,
        b.nombre,
        b.precio,
        b.activo,
        p.nombre AS producto,
        bp.cantidad
    FROM productos b

    LEFT JOIN box_productos bp
        ON bp.producto_box = b.idproductos

    LEFT JOIN productos p
        ON p.idproductos = bp.producto_item

    WHERE b.tipo = 'box'

    ORDER BY b.nombre, p.nombre
");

$boxRows = $stmtBox->fetchAll(PDO::FETCH_ASSOC);


/* =========================
AGRUPAR BOX
========================= */

$boxAgrupados = [];

foreach ($boxRows as $row) {

    $id = $row['idproductos'];

    if (!isset($boxAgrupados[$id])) {

        $boxAgrupados[$id] = [
            "idproductos" => $row['idproductos'],
            "nombre"      => $row['nombre'],
            "precio"      => $row['precio'],
            "activo"      => $row['activo'],
            "items"       => []
        ];
    }

    if ($row['producto']) {

        $boxAgrupados[$id]["items"][] = [
            "producto" => $row['producto'],
            "cantidad" => $row['cantidad']
        ];
    }
}

?>

<link rel="stylesheet" href="productos.css">


<div class="content-body">

    <!-- HEADER -->

    <div class="editor-header fade-up">

        <div>
            <h1>Productos y Box</h1>
            <p>Gestión de cookies y box de Canetto</p>
        </div>

        <button class="btn-primary" onclick="abrirModalProducto()">
            <i class="fa-solid fa-plus"></i>
            Nuevo
        </button>

    </div>


    <!-- =========================
    PRODUCTOS
    ========================= -->

    <h2 class="titulo-seccion">Productos</h2>

    <div class="cards-grid">

        <?php foreach ($productos as $p): ?>

            <div class="producto-card <?= $p['activo'] ? '' : 'inactivo' ?>">

                <div class="producto-header">

                    <h3><?= htmlspecialchars($p['nombre']) ?></h3>

                    <span class="estado">
                        <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>

                </div>

                <div class="producto-body">

                    <p>
                        <strong>Receta:</strong>
                        <?= $p['receta_nombre'] ?? 'Sin receta' ?>
                    </p>

                    <p>
                        <strong>Precio:</strong>
                        $<?= number_format($p['precio'], 2) ?>
                    </p>

                </div>

                <div class="card-actions">

                    <button
                        class="btn-edit"
                        onclick="editarProducto(
    <?= $p['idproductos'] ?>,
    '<?= $p['nombre'] ?>',
    <?= $p['precio'] ?>,
    '<?= $p['tipo'] ?>',
    <?= $p['recetas_idrecetas'] ?? 'null' ?>,
    <?= $p['min_congelado'] ?? 0 ?>,
    <?= $p['min_hecho'] ?? 0 ?>
)"
                    >
                        Editar
                    </button>

                    <button
                        class="btn-delete"
                        onclick="toggleActivo(<?= $p['idproductos'] ?>, <?= $p['activo'] ?>)"
                    >
                        <?= $p['activo'] ? 'Dar de baja' : 'Activar' ?>
                    </button>

                </div>

            </div>

        <?php endforeach; ?>

    </div>


    <!-- =========================
    BOX
    ========================= -->

    <h2 class="titulo-seccion">Box</h2>

    <div class="cards-grid">

        <?php foreach ($boxAgrupados as $b): ?>

            <div class="producto-card box-card <?= $b['activo'] ? '' : 'inactivo' ?>">

                <div class="producto-header">

                    <h3><?= htmlspecialchars($b['nombre']) ?></h3>

                    <span class="estado">
                        <?= $b['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>

                </div>

                <div class="producto-body">

                    <p>
                        <strong>Precio:</strong>
                        $<?= number_format($b['precio'], 2) ?>
                    </p>

                    <div class="box-contenido">

                        <?php foreach ($b['items'] as $item): ?>

                            <div class="box-item">
                                <?= htmlspecialchars($item['producto']) ?>
                                <span>x<?= $item['cantidad'] ?></span>
                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="card-actions">

                    <button
                        class="btn-edit"
                        onclick="editarProducto(
                            <?= $b['idproductos'] ?>,
                            '<?= htmlspecialchars($b['nombre']) ?>',
                            <?= $b['precio'] ?>,
                            'box',
                            null
                        )"
                    >
                        Editar
                    </button>

                    <button
                        class="btn-delete"
                        onclick="toggleActivo(<?= $b['idproductos'] ?>, <?= $b['activo'] ?>)"
                    >
                        <?= $b['activo'] ? 'Dar de baja' : 'Activar' ?>
                    </button>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>



<!-- =========================
MODAL CREAR / EDITAR
========================= -->

<div class="modal-overlay" id="modalProducto">

    <div class="modal-card">

        <h2 id="tituloModal">Crear Producto</h2>

        <form id="formCrearProducto">

            <input type="hidden" name="idproducto" id="idproducto">

            <!-- =========================
            DATOS BASICOS
            ========================= -->

            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" id="nombreProducto" required>
            </div>

            <div class="form-group">
                <label>Precio</label>
                <input type="number" step="0.01" name="precio" id="precioProducto">
            </div>

            <div class="form-group">
                <label>Tipo</label>

                <select name="tipo" id="tipoProducto">
                    <option value="producto">Producto</option>
                    <option value="box">Box</option>
                </select>

            </div>

            <!-- =========================
            RECETA (SOLO PRODUCTO)
            ========================= -->

            <div class="form-group" id="grupoReceta">

                <label>Receta</label>

                <select name="recetas_idrecetas" id="selectRecetas"></select>

            </div>

            <!-- =========================
            STOCK CONFIG 🔥
            ========================= -->

            <div id="grupoStock">

                <h3 style="margin-top:20px;">📦 Configuración de stock</h3>

                <div class="form-grid">

                    <div class="form-group">
                        <label>Stock mínimo congelado</label>
                        <input 
                            type="number" 
                            name="min_congelado" 
                            value="0" 
                            min="0"
                        >
                    </div>

                    <div class="form-group">
                        <label>Stock mínimo hecho</label>
                        <input 
                            type="number" 
                            name="min_hecho" 
                            value="0" 
                            min="0"
                        >
                    </div>

                </div>

            </div>

            <!-- =========================
            BUILDER BOX
            ========================= -->

            <div id="builderBox" style="display:none">

                <h3>Contenido del Box</h3>

                <div id="listaBox"></div>

                <button 
                    type="button" 
                    class="btn-secondary"
                    onclick="agregarProductoBox()"
                >
                    + Agregar producto
                </button>

            </div>

            <!-- =========================
            ACCIONES
            ========================= -->

            <div class="modal-actions">

                <button 
                    type="button" 
                    onclick="cerrarModalProducto()"
                >
                    Cancelar
                </button>

                <button class="btn-primary">
                    Guardar
                </button>

            </div>

        </form>

    </div>

</div>


<script>

/* =========================
EDITAR PRODUCTO / BOX
========================= */

function editarProducto(id, nombre, precio, tipo, receta, minCong, minHecho) {

    abrirModalProducto();

    setTimeout(() => {

        document.getElementById("idproducto").value = id;
        document.getElementById("nombreProducto").value = nombre;
        document.getElementById("precioProducto").value = precio;
        document.getElementById("tipoProducto").value = tipo;

        document.querySelector("[name='min_congelado']").value = minCong || 0;
        document.querySelector("[name='min_hecho']").value = minHecho || 0;

        document.getElementById("tituloModal").innerText = "Editar";

        if (receta) {
            document.getElementById("selectRecetas").value = receta;
        }

        if (tipo === "box") {
            activarModoBox();
            cargarBox(id);
        } else {
            activarModoProducto();
        }

    }, 200);
}


/* =========================
ACTIVO / INACTIVO
========================= */

async function toggleActivo(id, activo) {

    const accion = activo ? "dar de baja" : "activar";

    const confirm = await Swal.fire({
        title: "¿Confirmar acción?",
        text: `¿Seguro que deseas ${accion} este producto?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí",
        cancelButtonText: "Cancelar"
    });

    if(!confirm.isConfirmed) return;

    const nuevo = activo ? 0 : 1;

    const res = await fetch("api/cambiar_estado_producto.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, activo: nuevo })
    });

    const data = await res.json();

    if (data.status === "ok") {

        Swal.fire({
            icon: "success",
            title: "Actualizado",
            text: "Estado modificado correctamente",
            timer: 1500,
            showConfirmButton: false
        });

        setTimeout(() => location.reload(), 1200);

    } else {

        Swal.fire("Error", data.mensaje || "No se pudo actualizar", "error");

    }
}


/* =========================
ABRIR MODAL
========================= */

async function abrirModalProducto() {

    resetModalProducto();

    const modal = document.getElementById("modalProducto");
    modal.classList.add("open");

    try{

        /* RECETAS */

        const res = await fetch("api/obtener_recetas.php");
        const recetas = await res.json();

        const select = document.getElementById("selectRecetas");

        select.innerHTML = `
<option value="">Seleccionar receta</option>
${recetas.map(r => `
<option value="${r.idrecetas}">
${r.nombre}
</option>
`).join("")}
`;

        /* PRODUCTOS PARA BOX */

        const resProductos = await fetch("api/obtener_productos.php");
        window.productosDisponibles = await resProductos.json();

    }catch(e){

        Swal.fire("Error","No se pudieron cargar los datos","error");

    }

}


/* =========================
RESET MODAL 🔥 (MUY IMPORTANTE)
========================= */

function resetModalProducto(){

    document.getElementById("formCrearProducto").reset();
    document.getElementById("idproducto").value = "";

    document.getElementById("tituloModal").innerText = "Crear Producto";

    document.getElementById("listaBox").innerHTML = "";

    activarModoProducto();

}


/* =========================
CERRAR MODAL
========================= */

function cerrarModalProducto() {

    document.getElementById("modalProducto").classList.remove("open");

}


/* =========================
TIPO PRODUCTO
========================= */

document
.getElementById("tipoProducto")
.addEventListener("change", function(){

    if(this.value === "box"){
        activarModoBox();
    }else{
        activarModoProducto();
    }

});


/* =========================
MODOS
========================= */

function activarModoBox(){

    document.getElementById("grupoReceta").style.display = "none";
    document.getElementById("builderBox").style.display = "block";
    document.getElementById("grupoStock").style.display = "none";

}

function activarModoProducto(){

    document.getElementById("grupoReceta").style.display = "block";
    document.getElementById("builderBox").style.display = "none";
    document.getElementById("grupoStock").style.display = "block";

}


/* =========================
AGREGAR PRODUCTO A BOX
========================= */

function agregarProductoBox(){

    const cont = document.getElementById("listaBox");

    const select = `
<select name="box_producto[]">

${window.productosDisponibles.map(p => `
<option value="${p.idproductos}">
${p.nombre}
</option>
`).join("")}

</select>
`;

    const html = `

<div class="fila-box">

${select}

<input 
type="number"
name="box_cantidad[]"
placeholder="Cantidad"
min="1"
value="1">

<button type="button" class="btn-remove" onclick="this.parentElement.remove()">
    ✕
</button>

</div>

`;

    cont.insertAdjacentHTML("beforeend", html);

}


/* =========================
VER CONTENIDO BOX
========================= */

async function verContenidoBox(id){

    const res = await fetch("api/ver_box.php?id=" + id);
    const data = await res.json();

    if(data.length === 0){

        Swal.fire({
            icon:"info",
            title:"Box vacío",
            text:"Este box no tiene productos."
        });

        return;
    }

    const contenido = data
        .map(i => `${i.producto}  x${i.cantidad}`)
        .join("<br>");

    Swal.fire({
        title: "Contenido del Box",
        html: contenido,
        icon: "info"
    });

}


/* =========================
CARGAR BOX (EDITAR)
========================= */

async function cargarBox(id){

    const res = await fetch("api/ver_box.php?id=" + id);
    const data = await res.json();

    const cont = document.getElementById("listaBox");
    cont.innerHTML = "";

    data.forEach(i => {

        const html = `

<div class="fila-box">

<select name="box_producto[]">

${window.productosDisponibles.map(p => `

<option value="${p.idproductos}" ${p.idproductos == i.producto_id ? 'selected' : ''}>
${p.nombre}
</option>

`).join("")}

</select>

<input 
type="number"
name="box_cantidad[]"
value="${i.cantidad}"
min="1">

<button 
type="button"
onclick="this.parentElement.remove()">
X
</button>

</div>

`;

        cont.insertAdjacentHTML("beforeend", html);

    });

}


/* =========================
GUARDAR PRODUCTO
========================= */

document
.getElementById("formCrearProducto")
.addEventListener("submit", async function(e){

    e.preventDefault();

    const formData = new FormData(this);

    const res = await fetch("api/crear_producto.php", {
        method: "POST",
        body: formData
    });

    const data = await res.json();

    if(data.status === "ok"){

        Swal.fire({
            icon:"success",
            title:"Guardado",
            text:"Producto guardado correctamente",
            timer:1500,
            showConfirmButton:false
        });

        setTimeout(()=>location.reload(),1200);

    }else{

        Swal.fire({
            icon:"error",
            title:"Error",
            text:data.mensaje || "No se pudo guardar"
        });

    }

});

</script>



<?php include '../../panel/dashboard/layaut/footer.php'; ?>