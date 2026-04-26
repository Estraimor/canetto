<?php

define('APP_BOOT', true);

require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

// Agregar columna imagen si no existe
try { $pdo->exec("ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL"); } catch (Throwable $e) {}
// Crear directorio de imágenes de productos
$imgDir = __DIR__ . '/../../img/productos';
if (!is_dir($imgDir)) @mkdir($imgDir, 0755, true);

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
        p.imagen,
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
        b.imagen,
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

                <div class="prod-thumb-wrap">
                    <?php if (!empty($p['imagen'])): ?>
                        <img src="<?= URL_ASSETS ?>/img/productos/<?= htmlspecialchars($p['imagen']) ?>"
                             alt="" class="prod-thumb-admin">
                    <?php else: ?>
                        <div class="prod-thumb-placeholder"><i class="fa-solid fa-cookie-bite"></i></div>
                    <?php endif; ?>
                    <span class="estado-badge <?= $p['activo'] ? 'badge-activo' : 'badge-inactivo' ?>">
                        <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>

                <div class="prod-card-body">
                    <div class="producto-header">
                        <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                    </div>

                    <div class="producto-body">
                        <div class="prod-meta-row">
                            <span class="prod-meta-label">Receta</span>
                            <span><?= htmlspecialchars($p['receta_nombre'] ?? 'Sin receta') ?></span>
                        </div>
                        <div class="prod-meta-row">
                            <span class="prod-meta-label">Precio</span>
                            <span class="prod-price-val">$<?= number_format($p['precio'], 2) ?></span>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button class="btn-edit" onclick="editarProducto(<?= $p['idproductos'] ?>,'<?= addslashes(htmlspecialchars($p['nombre'])) ?>',<?= $p['precio'] ?>,'<?= $p['tipo'] ?>',<?= $p['recetas_idrecetas'] ?? 'null' ?>,<?= $p['min_congelado'] ?? 0 ?>,<?= $p['min_hecho'] ?? 0 ?>,'<?= htmlspecialchars($p['imagen'] ?? '') ?>')">
                            <i class="fa-solid fa-pen"></i> Editar
                        </button>
                        <button class="btn-delete" onclick="toggleActivo(<?= $p['idproductos'] ?>, <?= $p['activo'] ?>)">
                            <?= $p['activo'] ? '<i class="fa-solid fa-ban"></i> Dar de baja' : '<i class="fa-solid fa-check"></i> Activar' ?>
                        </button>
                    </div>
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

                <div class="prod-thumb-wrap prod-thumb-wrap--box">
                    <div class="prod-thumb-placeholder prod-thumb-placeholder--box">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <span class="estado-badge <?= $b['activo'] ? 'badge-activo' : 'badge-inactivo' ?>">
                        <?= $b['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>

                <div class="prod-card-body">
                    <div class="producto-header">
                        <h3><?= htmlspecialchars($b['nombre']) ?></h3>
                    </div>

                    <div class="producto-body">
                        <div class="prod-meta-row">
                            <span class="prod-meta-label">Precio</span>
                            <span class="prod-price-val">$<?= number_format($b['precio'], 2) ?></span>
                        </div>
                        <div class="box-contenido">
                            <?php foreach ($b['items'] as $item): ?>
                                <div class="box-item">
                                    <span><?= htmlspecialchars($item['producto']) ?></span>
                                    <span class="box-qty">×<?= $item['cantidad'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button class="btn-edit" onclick="editarProducto(<?= $b['idproductos'] ?>,'<?= htmlspecialchars($b['nombre']) ?>',<?= $b['precio'] ?>,'box',null)">
                            <i class="fa-solid fa-pen"></i> Editar
                        </button>
                        <button class="btn-delete" onclick="toggleActivo(<?= $b['idproductos'] ?>, <?= $b['activo'] ?>)">
                            <?= $b['activo'] ? '<i class="fa-solid fa-ban"></i> Dar de baja' : '<i class="fa-solid fa-check"></i> Activar' ?>
                        </button>
                    </div>
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

        <form id="formCrearProducto" enctype="multipart/form-data">

            <input type="hidden" name="idproducto" id="idproducto">
            <input type="hidden" name="imagen_actual" id="imagenActual">

            <!-- ── COLUMNA IZQUIERDA: imagen ── -->
            <div class="prod-modal-img-col">
                <div class="prod-modal-img-box" id="imgPreviewWrap" onclick="document.getElementById('inputImagen').click()">
                    <img id="imgPreview" src="" alt="preview" style="display:none">
                    <div class="prod-modal-img-placeholder" id="imgPlaceholder">
                        <i class="fa-solid fa-image"></i>
                    </div>
                </div>
                <div class="prod-modal-img-actions">
                    <label for="inputImagen">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i> Subir imagen
                    </label>
                    <input type="file" name="imagen" id="inputImagen" accept="image/jpeg,image/png,image/webp"
                           onchange="previsualizarImagen(this)" style="display:none">
                    <button type="button" class="btn-quitar-img" id="btnQuitarImg" onclick="quitarImagen()" style="display:none">
                        <i class="fa-solid fa-trash"></i> Quitar imagen
                    </button>
                    <small style="color:#bbb;font-size:11px;text-align:center">JPG, PNG o WebP · Máx 2 MB</small>
                </div>
            </div>

            <!-- ── COLUMNA DERECHA: campos ── -->
            <div class="prod-modal-fields-col">

                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" id="nombreProducto" required>
                </div>

                <div class="form-grid">
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
                </div>

                <!-- Receta (solo producto) -->
                <div class="form-group" id="grupoReceta">
                    <label>Receta</label>
                    <select name="recetas_idrecetas" id="selectRecetas"></select>
                </div>

                <!-- Stock -->
                <div id="grupoStock">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#aaa;margin:16px 0 10px;display:flex;align-items:center;gap:8px">
                        Stock mínimo
                        <span style="flex:1;height:1px;background:#ebebeb;display:block"></span>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Congelado</label>
                            <input type="number" name="min_congelado" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Hecho</label>
                            <input type="number" name="min_hecho" value="0" min="0">
                        </div>
                    </div>
                </div>

                <!-- Builder Box -->
                <div id="builderBox" style="display:none">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#aaa;margin:16px 0 10px;display:flex;align-items:center;gap:8px">
                        Contenido del box
                        <span style="flex:1;height:1px;background:#ebebeb;display:block"></span>
                    </div>
                    <div id="listaBox"></div>
                    <button type="button" class="btn-secondary" onclick="agregarProductoBox()">
                        <i class="fa-solid fa-plus"></i> Agregar producto
                    </button>
                </div>

                <!-- Toppings (solo producto) -->
                <div id="grupoToppings" style="display:none">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#aaa;margin:16px 0 10px;display:flex;align-items:center;gap:8px">
                        <i class="fa-solid fa-candy-cane" style="color:#c88e99"></i> Toppings disponibles
                        <span style="flex:1;height:1px;background:#ebebeb;display:block"></span>
                    </div>
                    <p style="font-size:12px;color:#888;margin:0 0 10px">Extras que el cliente podrá elegir al pedir este producto</p>
                    <div id="toppingsCheckList" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div style="color:#aaa;font-size:13px;grid-column:1/-1">Cargando...</div>
                    </div>
                    <div style="margin-top:8px;font-size:11px;color:#bbb">
                        Sin selección = sin toppings en la tienda ·
                        <a href="../../administracion/toppings/" style="color:#c88e99;font-weight:600" target="_blank">Crear toppings →</a>
                    </div>
                </div>

            </div><!-- /fields-col -->

            <!-- ── ACCIONES (fila completa) ── -->
            <div class="modal-actions prod-modal-full">
                <button type="button" onclick="cerrarModalProducto()">Cancelar</button>
                <button class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>

            </div>

        </form>

    </div>

</div>


<script>

/* =========================
EDITAR PRODUCTO / BOX
========================= */

function editarProducto(id, nombre, precio, tipo, receta, minCong, minHecho, imagen) {

    abrirModalProducto(id);

    setTimeout(() => {

        document.getElementById("idproducto").value = id;
        document.getElementById("nombreProducto").value = nombre;
        document.getElementById("precioProducto").value = precio;
        document.getElementById("tipoProducto").value = tipo;

        document.querySelector("[name='min_congelado']").value = minCong || 0;
        document.querySelector("[name='min_hecho']").value = minHecho || 0;

        document.getElementById("tituloModal").innerText = "Editar";

        // Imagen actual
        const imgEl  = document.getElementById("imgPreview");
        const ph     = document.getElementById("imgPlaceholder");
        const btnQ   = document.getElementById("btnQuitarImg");
        document.getElementById("imagenActual").value = imagen || '';
        if (imagen) {
            imgEl.src          = '<?= URL_ASSETS ?>/img/productos/' + imagen;
            imgEl.style.display = 'block';
            ph.style.display    = 'none';
            btnQ.style.display  = '';
        } else {
            imgEl.src          = '';
            imgEl.style.display = 'none';
            ph.style.display    = '';
            btnQ.style.display  = 'none';
        }

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

function previsualizarImagen(input) {
    const img  = document.getElementById("imgPreview");
    const ph   = document.getElementById("imgPlaceholder");
    const btn  = document.getElementById("btnQuitarImg");
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            img.style.display = 'block';
            ph.style.display  = 'none';
            btn.style.display = '';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function quitarImagen() {
    document.getElementById("inputImagen").value  = '';
    document.getElementById("imagenActual").value = '';
    const img = document.getElementById("imgPreview");
    img.src = ''; img.style.display = 'none';
    document.getElementById("imgPlaceholder").style.display = '';
    document.getElementById("btnQuitarImg").style.display   = 'none';
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

async function abrirModalProducto(idProductoEditar = 0) {

    resetModalProducto();

    const modal = document.getElementById("modalProducto");
    modal.classList.add("open");

    try{
        const [resRecetas, resProductos, resToppings] = await Promise.all([
            fetch("api/obtener_recetas.php").then(r=>r.json()),
            fetch("api/obtener_productos.php").then(r=>r.json()),
            fetch("../../administracion/toppings/api/listar.php").then(r=>r.json()),
        ]);

        /* RECETAS */
        const select = document.getElementById("selectRecetas");
        select.innerHTML = `<option value="">Seleccionar receta</option>`
            + resRecetas.map(r=>`<option value="${r.idrecetas}">${r.nombre}</option>`).join('');

        /* PRODUCTOS PARA BOX */
        window.productosDisponibles = resProductos;

        /* TOPPINGS */
        window._todosLosToppings = resToppings;
        let asignadosIds = new Set();
        if (idProductoEditar) {
            const rAsig = await fetch(`../../administracion/toppings/api/toppings_producto.php?id=${idProductoEditar}`).then(r=>r.json());
            asignadosIds = new Set(rAsig.map(Number));
        }
        renderToppingsCheck(resToppings, asignadosIds);

    }catch(e){
        Swal.fire("Error","No se pudieron cargar los datos","error");
    }
}

function renderToppingsCheck(toppings, asignadosIds = new Set()) {
    const wrap = document.getElementById("toppingsCheckList");
    if (!toppings || !toppings.length) {
        wrap.innerHTML = `<div style="color:#aaa;font-size:13px;grid-column:1/-1">No hay toppings creados aún.
            <a href="../../administracion/toppings/" style="color:#c88e99;font-weight:600" target="_blank">Crear toppings →</a></div>`;
        return;
    }
    wrap.innerHTML = toppings.map(t => `
        <label style="display:flex;align-items:center;gap:8px;padding:9px 11px;border:1.5px solid ${asignadosIds.has(+t.idtoppings)?'#c88e99':'#e8e8e8'};
               border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;color:#333;
               background:${asignadosIds.has(+t.idtoppings)?'#fdf0f3':'#fff'};transition:all .15s;"
               onmouseenter="this.style.borderColor='#c88e99'"
               onmouseleave="if(!this.querySelector('input').checked){this.style.borderColor='#e8e8e8';this.style.background='#fff';}">
          <input type="checkbox" name="toppings[]" value="${t.idtoppings}"
                 ${asignadosIds.has(+t.idtoppings)?'checked':''}
                 style="accent-color:#c88e99;width:15px;height:15px"
                 onchange="this.closest('label').style.borderColor=this.checked?'#c88e99':'#e8e8e8';this.closest('label').style.background=this.checked?'#fdf0f3':'#fff'">
          <span style="flex:1">${t.nombre}</span>
          <span style="font-size:12px;color:#c88e99;font-weight:700">+$${Number(t.precio).toLocaleString('es-AR')}</span>
        </label>`).join('');
}


/* =========================
RESET MODAL 🔥 (MUY IMPORTANTE)
========================= */

function resetModalProducto(){

    document.getElementById("formCrearProducto").reset();
    document.getElementById("idproducto").value = "";
    document.getElementById("imagenActual").value = "";
    const imgEl = document.getElementById("imgPreview");
    imgEl.src = ''; imgEl.style.display = 'none';
    document.getElementById("imgPlaceholder").style.display = '';
    document.getElementById("btnQuitarImg").style.display   = 'none';

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
    document.getElementById("grupoToppings").style.display = "none";
}

function activarModoProducto(){
    document.getElementById("grupoReceta").style.display = "block";
    document.getElementById("builderBox").style.display = "none";
    document.getElementById("grupoStock").style.display = "block";
    document.getElementById("grupoToppings").style.display = "block";
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

        // Guardar toppings si es producto (no box)
        const tipo = document.getElementById("tipoProducto").value;
        if (tipo !== 'box') {
            const idGuardado = data.id || document.getElementById("idproducto").value;
            if (idGuardado) {
                const tpIds = [...document.querySelectorAll('#toppingsCheckList input[type="checkbox"]:checked')]
                    .map(c => +c.value);
                try {
                    await fetch('../../administracion/toppings/api/asignar.php', {
                        method: 'POST',
                        body: JSON.stringify({ productos_idproductos: +idGuardado, toppings: tpIds }),
                        headers: { 'Content-Type': 'application/json' }
                    });
                } catch {}
            }
        }

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