<?php

define('APP_BOOT', true);

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/tron.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

// Agregar columna imagen si no existe
try { $pdo->exec("ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL"); } catch (Throwable $e) {}
try { $pdo->exec("ALTER TABLE productos ADD COLUMN orden INT NULL DEFAULT NULL"); } catch (Throwable $e) {}
// Crear directorio de imágenes de productos
$imgDir = __DIR__ . '/../../img/productos';
if (!is_dir($imgDir)) @mkdir($imgDir, 0755, true);

// Leer modo de orden configurado
$ordenModo = 'aleatorio';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion_tienda (
        clave VARCHAR(60) PRIMARY KEY, valor TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $rModo = $pdo->query("SELECT valor FROM configuracion_tienda WHERE clave='orden_productos'")->fetch();
    if ($rModo) $ordenModo = $rModo['valor'];
} catch (Throwable $e) {}

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
        p.orden,
        r.nombre AS receta_nombre,

        COALESCE(MAX(CASE
            WHEN sp.tipo_stock = 'CONGELADO'
            THEN sp.stock_minimo
        END), 0) AS min_congelado,

        COALESCE(MAX(CASE
            WHEN sp.tipo_stock = 'HECHO'
            THEN sp.stock_minimo
        END), 0) AS min_hecho,

        COALESCE((
            SELECT o.valor FROM oferta o
            WHERE o.productos_idproductos = p.idproductos
              AND o.tipo_panel = 'descuento' AND o.activo = 1
              AND (o.fecha_inicio IS NULL OR o.fecha_inicio <= CURDATE())
              AND (o.fecha_fin   IS NULL OR o.fecha_fin   >= CURDATE())
            LIMIT 1
        ), 0) AS descuento_pct

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

<link rel="stylesheet" href="productos.css?v=<?= filemtime(__DIR__ . '/productos.css') ?>">


<div class="content-body">

<a href="javascript:history.back()" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Volver
</a>

    <!-- HEADER -->
    <div class="editor-header fade-up">
        <div>
            <h1>Productos <span class="header-y">&</span> Box</h1>
            <p>Cookies individuales y combos de Canetto</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
            <button class="btn-secondary" onclick="abrirModalOrden()" style="background:#f5f5f5;color:#333;border:none;padding:10px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:7px">
                <i class="fa-solid fa-arrow-up-wide-short"></i> Orden de visualización
            </button>
            <button class="btn-primary" onclick="abrirModalProducto()">
                <i class="fa-solid fa-plus"></i> Nuevo producto
            </button>
        </div>
    </div>

    <!-- BUSCADOR -->
    <div class="prod-toolbar">
        <div class="prod-search-box">
            <i class="fa-solid fa-magnifying-glass prod-search-icon"></i>
            <input type="text" id="buscadorProductos" class="prod-search-input"
                   placeholder="Buscar producto o box…"
                   oninput="filtrarProductos(this.value)">
            <button class="prod-search-clear" id="btnLimpiarBusqueda" onclick="limpiarBusqueda()" style="display:none">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <select id="filtroEstadoProd" class="prod-filter-select" onchange="aplicarSelects()">
            <option value="todos">Todos los estados</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
        <select id="filtroTipoProd" class="prod-filter-select" onchange="aplicarSelects()">
            <option value="todos">Todos los tipos</option>
            <option value="cookie">Cookies</option>
            <option value="box">Box</option>
        </select>
        <span class="prod-count" id="prodCount"></span>
    </div>

    <!-- =========================
    PRODUCTOS
    ========================= -->

    <div class="seccion-divider seccion-cookies">
        <div class="seccion-divider-inner">
            <span class="seccion-title">Cookies</span>
            <span class="seccion-badge seccion-badge-cookies"><?= count($productos) ?></span>
        </div>
    </div>

    <div class="cards-grid">

        <?php foreach ($productos as $p): ?>

            <div class="producto-card <?= $p['activo'] ? '' : 'inactivo' ?>"
                 data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>"
                 data-estado="<?= $p['activo'] ? 'activo' : 'inactivo' ?>"
                 data-tipo="cookie">

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
                    <?php if (!empty($p['descuento_pct'])): ?>
                    <span class="admin-desc-badge"><?= (int)$p['descuento_pct'] ?>% OFF</span>
                    <?php endif; ?>
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

    <div class="seccion-divider seccion-box">
        <div class="seccion-divider-inner">
            <span class="seccion-title">Box</span>
            <span class="seccion-badge seccion-badge-box"><?= count($boxAgrupados) ?></span>
        </div>
    </div>

    <div class="cards-grid">

        <?php foreach ($boxAgrupados as $b): ?>

            <div class="producto-card box-card <?= $b['activo'] ? '' : 'inactivo' ?>"
                 data-nombre="<?= strtolower(htmlspecialchars($b['nombre'])) ?>"
                 data-estado="<?= $b['activo'] ? 'activo' : 'inactivo' ?>"
                 data-tipo="box">

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
MODAL ORDEN DE VISUALIZACIÓN
========================= -->

<div class="modal-overlay" id="modalOrden">
    <div class="modal-card" style="max-width:560px">
        <h2>Orden de visualización</h2>
        <p style="color:#aaa;font-size:13px;margin:-8px 0 18px">Configurá cómo se muestran las cookies en la tienda para los clientes.</p>

        <!-- Toggle modo -->
        <div style="display:flex;gap:10px;margin-bottom:20px">
            <button id="btnModoAleatorio" onclick="setModoOrden('aleatorio')"
                    class="btn-modo <?= $ordenModo === 'aleatorio' ? 'btn-modo--activo' : '' ?>">
                <i class="fa-solid fa-shuffle"></i> Aleatorio
            </button>
            <button id="btnModoManual" onclick="setModoOrden('manual')"
                    class="btn-modo <?= $ordenModo === 'manual' ? 'btn-modo--activo' : '' ?>">
                <i class="fa-solid fa-list-ol"></i> Orden manual
            </button>
        </div>

        <!-- Info modo aleatorio -->
        <div id="infoAleatorio" style="<?= $ordenModo !== 'aleatorio' ? 'display:none;' : '' ?>background:#f8f9fa;border-radius:10px;padding:14px 16px;font-size:13px;color:#666;margin-bottom:16px">
            <i class="fa-solid fa-shuffle" style="color:#c88e99;margin-right:6px"></i>
            Las cookies se muestran en orden aleatorio cada vez. Los productos con descuento siempre aparecen primero.
        </div>

        <!-- Lista drag-and-drop para modo manual -->
        <div id="listaOrdenManual" style="<?= $ordenModo !== 'manual' ? 'display:none;' : '' ?>">
            <p style="font-size:12px;color:#aaa;margin-bottom:10px"><i class="fa-solid fa-circle-info"></i> Arrastrá para reordenar. Los productos con descuento activo siempre aparecen primero en la tienda.</p>
            <div id="sortableProductos" style="display:flex;flex-direction:column;gap:8px;max-height:380px;overflow-y:auto;padding-right:4px">
                <?php
                $prodsSorted = $productos;
                usort($prodsSorted, fn($a, $b) => ($a['orden'] ?? 9999) <=> ($b['orden'] ?? 9999));
                foreach ($prodsSorted as $p):
                ?>
                <div class="orden-row" data-id="<?= $p['idproductos'] ?>" draggable="true">
                    <i class="fa-solid fa-grip-vertical" style="color:#ccc;cursor:grab;flex-shrink:0"></i>
                    <?php if (!empty($p['imagen'])): ?>
                        <img src="<?= URL_ASSETS ?>/img/productos/<?= htmlspecialchars($p['imagen']) ?>" style="width:36px;height:36px;border-radius:8px;object-fit:cover;flex-shrink:0">
                    <?php else: ?>
                        <span style="width:36px;height:36px;background:#fdeef1;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0"><i class="fa-solid fa-cookie-bite" style="color:#c88e99;font-size:14px"></i></span>
                    <?php endif; ?>
                    <span style="flex:1;font-size:13px;font-weight:600"><?= htmlspecialchars($p['nombre']) ?></span>
                    <?php if (!empty($p['descuento_pct'])): ?>
                        <span style="background:#fef2f2;color:#dc2626;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px"><?= (int)$p['descuento_pct'] ?>% OFF</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="modal-actions prod-modal-full" style="margin-top:20px">
            <button type="button" onclick="cerrarModalOrden()">Cancelar</button>
            <button class="btn-primary" onclick="guardarOrdenProductos()">
                <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
        </div>
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

            <!-- ── COLUMNA IZQUIERDA: imágenes ── -->
            <div class="prod-modal-img-col">
                <!-- Preview imagen principal -->
                <div class="prod-modal-img-box" id="imgPreviewWrap" onclick="document.getElementById('inputImagen').click()">
                    <img id="imgPreview" src="" alt="preview" style="display:none">
                    <div class="prod-modal-img-placeholder" id="imgPlaceholder">
                        <i class="fa-solid fa-image"></i>
                    </div>
                </div>

                <!-- Miniaturas de imágenes existentes -->
                <div id="galeriaExistente" style="display:flex;flex-wrap:wrap;gap:14px;margin-top:12px;padding-bottom:20px"></div>

                <div class="prod-modal-img-actions">
                    <label for="inputImagen" style="cursor:pointer">
                        <i class="fa-solid fa-plus"></i> Agregar imagen
                    </label>
                    <input type="file" name="imagenes_nuevas[]" id="inputImagen"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           multiple onchange="agregarImagenes(this)" style="display:none">
                    <!-- Campo oculto que mantiene imagen principal (compatibilidad) -->
                    <input type="hidden" name="imagen_actual" id="imagenActual">
                    <small style="color:#bbb;font-size:11px;text-align:center">JPG, PNG, WebP o GIF · Máx 5 MB c/u · Múltiples</small>
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

                <!-- Descuento (solo producto) -->
                <div id="grupoDescuento" style="display:none">
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#aaa;margin:16px 0 10px;display:flex;align-items:center;gap:8px">
                        Descuento
                        <span style="flex:1;height:1px;background:#ebebeb;display:block"></span>
                    </div>

                    <!-- Aviso si ya hay panel activo -->
                    <div id="descuentoPanelInfo" style="display:none;background:#fff5f5;border:1px solid #fca5a5;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:12.5px;color:#991b1b;align-items:center;gap:8px">
                        <i class="fa-solid fa-tag"></i>
                        <span id="descuentoPanelTexto"></span>
                    </div>

                    <div class="form-group" style="position:relative">
                        <label>Descuento (%)</label>
                        <div style="display:flex;gap:8px;align-items:center">
                            <input type="number" id="inputDescuento" min="1" max="99" step="1"
                                   placeholder="Ej: 20"
                                   style="flex:1">
                            <button type="button" id="btnQuitarDescuento" onclick="quitarDescuento()"
                                    style="display:none;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;padding:8px 12px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;flex-shrink:0">
                                <i class="fa-solid fa-xmark"></i> Quitar
                            </button>
                        </div>
                        <span style="font-size:11px;color:#aaa;margin-top:4px;display:block">El descuento crea o actualiza un panel de tipo Descuento vinculado a este producto.</span>
                    </div>

                    <button type="button" id="btnGuardarDescuento" onclick="guardarDescuento()"
                            style="background:#dc2626;color:#fff;border:none;border-radius:9px;padding:9px 16px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:7px;margin-top:4px">
                        <i class="fa-solid fa-tag"></i> Aplicar descuento
                    </button>
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

async function editarProducto(id, nombre, precio, tipo, receta, minCong, minHecho, imagen) {
    await abrirModalProducto(id);

    document.getElementById("idproducto").value = id;
    document.getElementById("nombreProducto").value = nombre;
    document.getElementById("precioProducto").value = precio;
    document.getElementById("tipoProducto").value = tipo;
    document.querySelector("[name='min_congelado']").value = minCong || 0;
    document.querySelector("[name='min_hecho']").value = minHecho || 0;
    document.getElementById("tituloModal").innerText = "Editar";
    document.getElementById("imagenActual").value = imagen || '';

    // Mostrar imagen principal en preview
    const imgEl = document.getElementById("imgPreview");
    const ph    = document.getElementById("imgPlaceholder");
    // Siempre cargar galería primero — ella actualiza el preview
    imgEl.src = ''; imgEl.style.display = 'none'; ph.style.display = '';
    cargarGaleriaExistente(id);

    if (receta) document.getElementById("selectRecetas").value = receta;
    if (tipo === "box") { activarModoBox(); cargarBox(id); }
    else {
        activarModoProducto();
        document.getElementById("grupoDescuento").style.display = "block";
        cargarDescuento(id);
    }
}

let _dragSrc = null;

async function cargarGaleriaExistente(idProducto) {
    const wrap = document.getElementById('galeriaExistente');
    wrap.innerHTML = '';
    try {
        const res  = await fetch('api/get_imagenes.php?id=' + idProducto);
        const data = await res.json();
        if (!data.imagenes || !data.imagenes.length) return;

        // Mostrar la primera imagen en el preview grande
        const primera = data.imagenes[0];
        const imgEl = document.getElementById('imgPreview');
        const ph    = document.getElementById('imgPlaceholder');
        imgEl.src = '<?= URL_ASSETS ?>/img/productos/' + primera.archivo;
        imgEl.style.display = 'block';
        ph.style.display    = 'none';
        document.getElementById('imagenActual').value = primera.archivo;

        data.imagenes.forEach((img, idx) => {
            const div = document.createElement('div');
            div.dataset.imgId = img.id;
            div.dataset.archivo = img.archivo;
            div.draggable = true;
            div.style.cssText = [
                'position:relative;width:64px;height:64px;border-radius:10px;overflow:visible',
                'border:2px solid ' + (idx === 0 ? '#c88e99' : '#e5e7eb'),
                'flex-shrink:0;cursor:grab;transition:opacity .15s,transform .15s',
                'box-shadow:' + (idx === 0 ? '0 0 0 2px #c88e99' : 'none'),
            ].join(';');

            div.innerHTML = `
              <img src="<?= URL_ASSETS ?>/img/productos/${img.archivo}"
                   style="width:100%;height:100%;object-fit:cover;border-radius:8px;display:block">
              ${idx === 0 ? '<span style="position:absolute;bottom:-18px;left:0;right:0;text-align:center;font-size:9px;font-weight:700;color:#c88e99;white-space:nowrap">PRINCIPAL</span>' : ''}
              <button type="button" onclick="eliminarImagen(${img.id},this.closest('[data-img-id]'))"
                style="position:absolute;top:-6px;right:-6px;background:#e74c3c;color:#fff;
                       border:none;border-radius:50%;width:18px;height:18px;font-size:10px;
                       cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;z-index:2">
                ✕
              </button>`;

            // Drag & drop events
            div.addEventListener('dragstart', e => {
                _dragSrc = div;
                e.dataTransfer.effectAllowed = 'move';
                setTimeout(() => div.style.opacity = '.4', 0);
            });
            div.addEventListener('dragend', () => {
                div.style.opacity = '1';
                div.style.transform = '';
                wrap.querySelectorAll('[data-img-id]').forEach(d => d.style.outline = '');
            });
            div.addEventListener('dragover', e => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                if (div !== _dragSrc) div.style.outline = '2px dashed #c88e99';
            });
            div.addEventListener('dragleave', () => div.style.outline = '');
            div.addEventListener('drop', e => {
                e.preventDefault();
                if (_dragSrc && _dragSrc !== div) {
                    // Reordenar en el DOM
                    const items = [...wrap.querySelectorAll('[data-img-id]')];
                    const fromI = items.indexOf(_dragSrc);
                    const toI   = items.indexOf(div);
                    if (fromI < toI) wrap.insertBefore(_dragSrc, div.nextSibling);
                    else             wrap.insertBefore(_dragSrc, div);
                    guardarOrden();
                }
                div.style.outline = '';
            });

            wrap.appendChild(div);
        });
    } catch(_) {}
}

async function guardarOrden() {
    const wrap = document.getElementById('galeriaExistente');
    const items = [...wrap.querySelectorAll('[data-img-id]')];
    const ids   = items.map(d => +d.dataset.imgId);

    // Actualizar estilos: primero = destacado
    items.forEach((d, i) => {
        d.style.border      = '2px solid ' + (i === 0 ? '#c88e99' : '#e5e7eb');
        d.style.boxShadow   = i === 0 ? '0 0 0 2px #c88e99' : 'none';
        const lbl = d.querySelector('span');
        if (i === 0 && !lbl) {
            const s = document.createElement('span');
            s.style.cssText = 'position:absolute;bottom:-18px;left:0;right:0;text-align:center;font-size:9px;font-weight:700;color:#c88e99;white-space:nowrap';
            s.textContent   = 'PRINCIPAL';
            d.appendChild(s);
        } else if (i !== 0 && lbl) lbl.remove();
    });

    // Preview grande = primera imagen
    if (items[0]) {
        const imgEl = document.getElementById('imgPreview');
        imgEl.src = '<?= URL_ASSETS ?>/img/productos/' + items[0].dataset.archivo;
        imgEl.style.display = 'block';
        document.getElementById('imgPlaceholder').style.display = 'none';
        document.getElementById('imagenActual').value = items[0].dataset.archivo;
    }

    // Persistir orden en servidor
    try {
        await fetch('api/reordenar_imagenes.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ ids })
        });
    } catch(_) {}
}

async function eliminarImagen(id, el) {
    if (!confirm('¿Eliminar esta imagen?')) return;
    const res  = await fetch('api/eliminar_imagen.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id})
    });
    const data = await res.json();
    if (data.ok) {
        el.remove();
        guardarOrden(); // actualizar preview y badge PRINCIPAL
    } else alert('Error al eliminar');
}

function agregarImagenes(input) {
    if (!input.files || !input.files.length) return;
    const img = document.getElementById("imgPreview");
    const ph  = document.getElementById("imgPlaceholder");
    // Preview de la primera imagen seleccionada
    const reader = new FileReader();
    reader.onload = e => {
        img.src = e.target.result;
        img.style.display = 'block';
        ph.style.display  = 'none';
    };
    reader.readAsDataURL(input.files[0]);
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
        const [resRecetas, resProductos] = await Promise.all([
            fetch("api/obtener_recetas.php").then(r=>r.json()),
            fetch("api/obtener_productos.php").then(r=>r.json()),
        ]);

        /* RECETAS */
        const select = document.getElementById("selectRecetas");
        select.innerHTML = `<option value="">Seleccionar receta</option>`
            + resRecetas.map(r=>`<option value="${r.idrecetas}">${r.nombre}</option>`).join('');

        /* PRODUCTOS PARA BOX */
        window.productosDisponibles = resProductos;

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
    document.getElementById("imagenActual").value = "";
    const imgEl = document.getElementById("imgPreview");
    imgEl.src = ''; imgEl.style.display = 'none';
    document.getElementById("imgPlaceholder").style.display = '';
    document.getElementById("galeriaExistente").innerHTML   = '';
    const btnQ = document.getElementById("btnQuitarImg");
    if (btnQ) btnQ.style.display = 'none';

    document.getElementById("tituloModal").innerText = "Crear Producto";

    document.getElementById("listaBox").innerHTML = "";

    // Reset descuento
    _idProductoActual = 0;
    document.getElementById("inputDescuento").value = '';
    document.getElementById("descuentoPanelInfo").style.display = 'none';
    document.getElementById("btnQuitarDescuento").style.display = 'none';
    document.getElementById("grupoDescuento").style.display = 'none';

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
    document.getElementById("grupoDescuento").style.display = "none";
}

function activarModoProducto(){
    document.getElementById("grupoReceta").style.display = "block";
    document.getElementById("builderBox").style.display = "none";
    document.getElementById("grupoStock").style.display = "block";
    // grupoDescuento solo se muestra si hay un ID (modo edición)
    const id = document.getElementById("idproducto").value;
    document.getElementById("grupoDescuento").style.display = id ? "block" : "none";
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
BUSCADOR + FILTRO
========================= */

function filtrarProductos(q) {
    const term = q.trim().toLowerCase();
    document.getElementById('btnLimpiarBusqueda').style.display = term ? '' : 'none';
    aplicarFiltro(term);
}

function limpiarBusqueda() {
    const inp = document.getElementById('buscadorProductos');
    inp.value = '';
    inp.focus();
    document.getElementById('btnLimpiarBusqueda').style.display = 'none';
    aplicarFiltro('');
}

function aplicarSelects() {
    aplicarFiltro(document.getElementById('buscadorProductos').value.trim().toLowerCase());
}

function aplicarFiltro(term) {
    const _filtroEstado = document.getElementById('filtroEstadoProd').value;
    const _filtroTipo   = document.getElementById('filtroTipoProd').value;
    const cards = document.querySelectorAll('.producto-card');
    let visible = 0;
    cards.forEach(card => {
        const nombre     = card.dataset.nombre || '';
        const cardEstado = card.dataset.estado || 'activo';
        const cardTipo   = card.dataset.tipo   || 'cookie';
        const matchNombre = !term || nombre.includes(term);
        const matchEstado = _filtroEstado === 'todos' || cardEstado === _filtroEstado;
        const matchTipo   = _filtroTipo   === 'todos' || cardTipo   === _filtroTipo;
        const show = matchNombre && matchEstado && matchTipo;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const countEl = document.getElementById('prodCount');
    countEl.textContent = visible === cards.length ? '' : `${visible} resultado${visible !== 1 ? 's' : ''}`;
}

/* =========================
MODAL ORDEN DE VISUALIZACIÓN
========================= */

let _modoOrden = '<?= $ordenModo ?>';
let _dragOrdenSrc = null;

function abrirModalOrden() {
    document.getElementById('modalOrden').classList.add('open');
}
function cerrarModalOrden() {
    document.getElementById('modalOrden').classList.remove('open');
}

function setModoOrden(modo) {
    _modoOrden = modo;
    document.getElementById('btnModoAleatorio').classList.toggle('btn-modo--activo', modo === 'aleatorio');
    document.getElementById('btnModoManual').classList.toggle('btn-modo--activo', modo === 'manual');
    document.getElementById('infoAleatorio').style.display    = modo === 'aleatorio' ? '' : 'none';
    document.getElementById('listaOrdenManual').style.display = modo === 'manual'    ? '' : 'none';
}

async function guardarOrdenProductos() {
    const ids = [...document.querySelectorAll('#sortableProductos .orden-row')].map(el => +el.dataset.id);
    const res = await fetch('api/guardar_orden_productos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ modo: _modoOrden, ids })
    });
    const data = await res.json();
    if (data.ok) {
        Swal.fire({ icon: 'success', title: 'Guardado', timer: 1400, showConfirmButton: false });
        cerrarModalOrden();
    } else {
        Swal.fire('Error', data.msg || 'No se pudo guardar', 'error');
    }
}

// Drag & drop para lista de orden
document.addEventListener('DOMContentLoaded', () => {
    const sortable = document.getElementById('sortableProductos');
    if (!sortable) return;

    sortable.addEventListener('dragstart', e => {
        const row = e.target.closest('.orden-row');
        if (!row) return;
        _dragOrdenSrc = row;
        e.dataTransfer.effectAllowed = 'move';
        setTimeout(() => row.style.opacity = '.4', 0);
    });
    sortable.addEventListener('dragend', e => {
        const row = e.target.closest('.orden-row');
        if (row) { row.style.opacity = '1'; row.style.outline = ''; }
        document.querySelectorAll('.orden-row').forEach(r => r.style.outline = '');
    });
    sortable.addEventListener('dragover', e => {
        e.preventDefault();
        const row = e.target.closest('.orden-row');
        if (row && row !== _dragOrdenSrc) row.style.outline = '2px solid #c88e99';
    });
    sortable.addEventListener('dragleave', e => {
        const row = e.target.closest('.orden-row');
        if (row) row.style.outline = '';
    });
    sortable.addEventListener('drop', e => {
        e.preventDefault();
        const target = e.target.closest('.orden-row');
        if (!target || !_dragOrdenSrc || target === _dragOrdenSrc) return;
        const items = [...sortable.querySelectorAll('.orden-row')];
        const fromI = items.indexOf(_dragOrdenSrc);
        const toI   = items.indexOf(target);
        if (fromI < toI) sortable.insertBefore(_dragOrdenSrc, target.nextSibling);
        else             sortable.insertBefore(_dragOrdenSrc, target);
        target.style.outline = '';
        _dragOrdenSrc.style.opacity = '1';
    });
});

/* =========================
DESCUENTO DESDE MODAL
========================= */

let _idProductoActual = 0;

async function cargarDescuento(idProducto) {
    _idProductoActual = idProducto;
    const info  = document.getElementById('descuentoPanelInfo');
    const texto = document.getElementById('descuentoPanelTexto');
    const input = document.getElementById('inputDescuento');
    const btnQ  = document.getElementById('btnQuitarDescuento');

    info.style.display  = 'none';
    input.value         = '';
    btnQ.style.display  = 'none';

    try {
        const res  = await fetch('api/descuento_producto.php?id=' + idProducto);
        const data = await res.json();
        if (data.ok && data.panel) {
            const p = data.panel;
            input.value = p.valor ? Math.round(p.valor) : '';
            if (p.activo == 1) {
                texto.textContent = 'Panel activo: "' + p.titulo + '"';
                info.style.display = 'flex';
            }
            if (p.valor) btnQ.style.display = '';
        }
    } catch(_) {}
}

async function guardarDescuento() {
    const valor = document.getElementById('inputDescuento').value;
    if (!valor || +valor <= 0) {
        Swal.fire({ icon: 'warning', title: 'Ingresá un porcentaje', text: 'El descuento debe ser mayor a 0%', timer: 1800, showConfirmButton: false });
        return;
    }
    const res  = await fetch('api/descuento_producto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'guardar', id_producto: _idProductoActual, valor: +valor })
    });
    const data = await res.json();
    if (data.ok) {
        Swal.fire({ icon: 'success', title: 'Descuento aplicado', timer: 1400, showConfirmButton: false });
        cargarDescuento(_idProductoActual);
    } else {
        Swal.fire('Error', data.msg || 'No se pudo guardar', 'error');
    }
}

async function quitarDescuento() {
    const confirmQ = await Swal.fire({
        title: '¿Quitar descuento?',
        text: 'Se desactivará el panel de descuento de este producto.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, quitar',
        cancelButtonText: 'Cancelar'
    });
    if (!confirmQ.isConfirmed) return;

    const res  = await fetch('api/descuento_producto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'quitar', id_producto: _idProductoActual })
    });
    const data = await res.json();
    if (data.ok) {
        Swal.fire({ icon: 'success', title: 'Descuento quitado', timer: 1400, showConfirmButton: false });
        cargarDescuento(_idProductoActual);
    } else {
        Swal.fire('Error', data.msg || 'No se pudo quitar', 'error');
    }
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