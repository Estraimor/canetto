<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$stmt = $pdo->query("
    SELECT
        p.idproductos,
        p.nombre,
        p.recetas_idrecetas,

        COALESCE(MAX(CASE
            WHEN sp.tipo_stock = 'CONGELADO'
            THEN sp.stock_actual
        END), 0) AS stock_congelado,

        COALESCE(MAX(CASE
            WHEN sp.tipo_stock = 'HECHO'
            THEN sp.stock_actual
        END), 0) AS stock_hecho,

        COALESCE(MAX(CASE
            WHEN sp.tipo_stock = 'CONGELADO'
            THEN sp.stock_minimo
        END), 0) AS min_congelado,

        COALESCE(MAX(CASE
            WHEN sp.tipo_stock = 'HECHO'
            THEN sp.stock_minimo
        END), 0) AS min_hecho

    FROM productos p
    LEFT JOIN stock_productos sp
        ON sp.productos_idproductos = p.idproductos
    WHERE p.tipo = 'producto'
    GROUP BY p.idproductos
    ORDER BY p.nombre ASC
");

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<link rel="stylesheet" href="stock.css">

<div class="stock-container">

    <div class="stock-header">
        📦 Stock de Productos
    </div>

    <div class="stock-grid">

        <?php foreach ($productos as $p): 

            $bajoCongelado = $p['stock_congelado'] <= $p['min_congelado'];
            $bajoHecho = $p['stock_hecho'] <= $p['min_hecho'];
        ?>

        <div class="stock-card abrir-modal"
            data-id="<?= $p['idproductos'] ?>"
            data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
            data-congelado="<?= $p['stock_congelado'] ?>"
            data-hecho="<?= $p['stock_hecho'] ?>"
            data-mincongelado="<?= $p['min_congelado'] ?>"
            data-minhecho="<?= $p['min_hecho'] ?>"
            data-receta="<?= (int)$p['recetas_idrecetas'] ?>"
        >

            <div class="producto-nombre">
                <?= htmlspecialchars($p['nombre']) ?>
            </div>

            <div class="stock-bloques">

                <!-- CONGELADO -->
                <div class="stock-box congelado <?= $bajoCongelado ? 'low' : '' ?>">

                    <div class="stock-titulo">❄️ Congelado</div>

                    <div class="stock-num">
                        <?= number_format($p['stock_congelado'],2) ?>
                    </div>

                    <div class="stock-min">
                        Mín: <?= $p['min_congelado'] ?>
                    </div>

                </div>

                <!-- HECHO -->
                <div class="stock-box hecho <?= $bajoHecho ? 'low' : '' ?>">

                    <div class="stock-titulo">🔥 Horneado</div>

                    <div class="stock-num">
                        <?= number_format($p['stock_hecho'],2) ?>
                    </div>

                    <div class="stock-min">
                        Mín: <?= $p['min_hecho'] ?>
                    </div>

                </div>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

</div>

<!-- =========================
MODAL
========================= -->
<div id="modalStock" class="modal">

    <div class="modal-content">

        <div class="modal-header">
            <span id="modalNombre"></span>
            <button onclick="cerrarModal()">✖</button>
        </div>

        <div class="modal-body">

            <!-- FRIO: módulo congelado -->
            <div class="lado congelado">
                <h3>❄️ Congelado</h3>

                <label>Stock actual</label>
                <input type="number" id="inputCongelado" min="0" step="0.01">

                <label>Stock mínimo</label>
                <input type="number" id="minCongelado" min="0" step="0.01">

                <div class="lado-separador"></div>

                <label>Cantidad a producir</label>
                <div class="lado-accion-row">
                    <input type="number" id="cantProducir" min="1" step="1" placeholder="0">
                    <button class="btn-accion btn-producir" onclick="producirCongelado()">
                        ❄️ Producir
                    </button>
                </div>
            </div>

            <!-- CALIENTE: módulo horneado -->
            <div class="lado hecho">
                <h3>🔥 Horneado</h3>

                <label>Stock actual</label>
                <input type="number" id="inputHecho" min="0" step="0.01">

                <label>Stock mínimo</label>
                <input type="number" id="minHecho" min="0" step="0.01">

                <div class="lado-separador"></div>

                <label>Disponible para hornear: <strong id="dispCongelado">—</strong></label>
                <div class="lado-accion-row">
                    <input type="number" id="cantHornear" min="1" step="1" placeholder="0">
                    <button class="btn-accion btn-hornear" onclick="hornearProducto()">
                        🔥 Hornear
                    </button>
                </div>
            </div>

        </div>

        <div class="modal-footer">
            <button class="btn-guardar" onclick="guardarStock()">💾 Guardar ajuste</button>
        </div>

    </div>

</div>

<script>
let productoActual = null;
let productoReceta = null;

document.querySelectorAll(".abrir-modal").forEach(card => {
    card.addEventListener("click", () => {
        productoActual = card.dataset.id;
        productoReceta = card.dataset.receta;

        document.getElementById("modalNombre").innerText = card.dataset.nombre;
        document.getElementById("inputCongelado").value  = card.dataset.congelado;
        document.getElementById("inputHecho").value      = card.dataset.hecho;
        document.getElementById("minCongelado").value    = card.dataset.mincongelado;
        document.getElementById("minHecho").value        = card.dataset.minhecho;
        document.getElementById("dispCongelado").textContent = parseFloat(card.dataset.congelado).toFixed(2);
        document.getElementById("cantProducir").value    = '';
        document.getElementById("cantHornear").value     = '';

        document.getElementById("modalStock").classList.add("open");
    });
});

function cerrarModal() {
    document.getElementById("modalStock").classList.remove("open");
}

/* ── Guardar ajuste manual de stock ── */
function guardarStock() {
    const data = {
        id:           productoActual,
        congelado:    document.getElementById("inputCongelado").value,
        hecho:        document.getElementById("inputHecho").value,
        minCongelado: document.getElementById("minCongelado").value,
        minHecho:     document.getElementById("minHecho").value
    };

    Swal.fire({
        title: "¿Guardar ajuste manual?",
        html: `<b>❄️ Congelado:</b> ${data.congelado}<br><b>🔥 Horneado:</b> ${data.hecho}`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#c88e99",
        cancelButtonColor: "#999",
        confirmButtonText: "Sí, guardar",
        cancelButtonText: "Cancelar"
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch("api/update_stock.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(r => {
            if (r.ok) {
                cerrarModal();
                Swal.fire({ title: "Guardado", text: "Stock actualizado correctamente", icon: "success", confirmButtonColor: "#c88e99" })
                    .then(() => location.reload());
            } else {
                Swal.fire("Error", r.error || "No se pudo guardar", "error");
            }
        });
    });
}

/* ── Producir congelado (módulo congelado) ── */
function producirCongelado() {
    const cant = parseFloat(document.getElementById("cantProducir").value);
    if (!cant || cant <= 0) { Swal.fire("Atención", "Ingresá una cantidad válida", "warning"); return; }
    if (!productoReceta || productoReceta == 0) { Swal.fire("Error", "Este producto no tiene receta asociada", "error"); return; }

    Swal.fire({ title: "Calculando ingredientes...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch("../produccion/congelado/api/preview_receta.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ receta: parseInt(productoReceta), cantidad: cant })
    })
    .then(r => r.json())
    .then(preview => {
        if (preview.status !== "ok") {
            Swal.fire("Error", preview.mensaje || "No se pudo calcular la receta", "error");
            return;
        }

        const filas = preview.ingredientes.map(i => {
            const faltaClass = i.faltante ? 'color:#c0392b;font-weight:700' : 'color:#16a34a';
            const icono = i.faltante ? '❌' : '✅';
            return `<tr>
                <td style="text-align:left;padding:4px 8px">${icono} ${i.nombre}</td>
                <td style="padding:4px 8px;font-weight:600;${faltaClass}">${i.cantidad} ${i.unidad}</td>
                <td style="padding:4px 8px;color:#888;font-size:.8rem">Stock: ${i.stock}</td>
            </tr>`;
        }).join('');

        const puedeProducir = preview.puede_producir;
        const advertencia = puedeProducir ? '' : '<p style="color:#c0392b;font-weight:600;margin-top:10px">⚠️ Stock insuficiente en algunos ingredientes</p>';

        Swal.fire({
            title: `¿Producir ${cant} uds?`,
            html: `
                <p style="margin-bottom:8px;color:#555;font-size:.85rem">Ingredientes a consumir:</p>
                <table style="width:100%;border-collapse:collapse;font-size:.85rem">${filas}</table>
                ${advertencia}
            `,
            icon: puedeProducir ? "question" : "warning",
            showCancelButton: true,
            confirmButtonColor: puedeProducir ? "#2980b9" : "#e67e22",
            cancelButtonColor: "#999",
            confirmButtonText: "Sí, producir",
            cancelButtonText: "Cancelar"
        }).then(result => {
            if (!result.isConfirmed) return;

            fetch("../produccion/congelado/api/producir.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ receta: parseInt(productoReceta), producto: parseInt(productoActual), cantidad: cant })
            })
            .then(r => r.json())
            .then(r => {
                if (r.status === "ok") {
                    cerrarModal();
                    Swal.fire({ title: "Producción realizada", text: r.mensaje, icon: "success", confirmButtonColor: "#c88e99" })
                        .then(() => location.reload());
                } else {
                    const detalle = Array.isArray(r.detalle) ? '<br>' + r.detalle.join('<br>') : '';
                    Swal.fire("Error", (r.mensaje || "No se pudo producir") + detalle, "error");
                }
            });
        });
    })
    .catch(() => Swal.fire("Error", "No se pudo conectar con el servidor", "error"));
}

/* ── Hornear (módulo horneado) ── */
function hornearProducto() {
    const cant    = parseFloat(document.getElementById("cantHornear").value);
    const dispon  = parseFloat(document.getElementById("inputCongelado").value);
    if (!cant || cant <= 0) { Swal.fire("Atención", "Ingresá una cantidad válida", "warning"); return; }
    if (cant > dispon) { Swal.fire("Stock insuficiente", `Solo hay ${dispon} unidades congeladas disponibles`, "warning"); return; }

    Swal.fire({
        title: "¿Hornear?",
        html: `Se van a hornear <b>${cant}</b> unidades (congelado → horneado).`,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#e67e22",
        cancelButtonColor: "#999",
        confirmButtonText: "Sí, hornear",
        cancelButtonText: "Cancelar"
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch("../produccion/horneado/api/procesar_horneado.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ producto_id: parseInt(productoActual), cantidad: cant })
        })
        .then(r => r.json())
        .then(r => {
            if (r.status === "ok") {
                cerrarModal();
                Swal.fire({ title: "Horneado realizado", text: r.mensaje, icon: "success", confirmButtonColor: "#c88e99" })
                    .then(() => location.reload());
            } else {
                Swal.fire("Error", r.mensaje || "No se pudo hornear", "error");
            }
        });
    });
}
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>