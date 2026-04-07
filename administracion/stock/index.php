<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$stmt = $pdo->query("
    SELECT 
        p.idproductos,
        p.nombre,

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
MODAL (NUEVO)
========================= -->
<div id="modalStock" class="modal">

    <div class="modal-content">

        <div class="modal-header">
            <span id="modalNombre"></span>
            <button onclick="cerrarModal()">✖</button>
        </div>

        <div class="modal-body">

            <!-- FRIO -->
            <div class="lado congelado">
                <h3>❄️ Congelado</h3>

                <label>Stock actual</label>
                <input type="number" id="inputCongelado">

                <label>Stock mínimo</label>
                <input type="number" id="minCongelado">
            </div>

            <!-- CALIENTE -->
            <div class="lado hecho">
                <h3>🔥 Horneado</h3>

                <label>Stock actual</label>
                <input type="number" id="inputHecho">

                <label>Stock mínimo</label>
                <input type="number" id="minHecho">
            </div>

        </div>

        <div class="modal-footer">
            <button class="btn-guardar" onclick="guardarStock()">💾 Guardar</button>
        </div>

    </div>

</div>

<script>
let productoActual = null;

document.querySelectorAll(".abrir-modal").forEach(card => {

    card.addEventListener("click", () => {

        productoActual = card.dataset.id;

        document.getElementById("modalNombre").innerText = card.dataset.nombre;

        document.getElementById("inputCongelado").value = card.dataset.congelado;
        document.getElementById("inputHecho").value = card.dataset.hecho;

        document.getElementById("minCongelado").value = card.dataset.mincongelado;
        document.getElementById("minHecho").value = card.dataset.minhecho;

        document.getElementById("modalStock").classList.add("open");

    });

});

function cerrarModal() {
    document.getElementById("modalStock").classList.remove("open");
}

function guardarStock() {

    const data = {
        id: productoActual,
        congelado: document.getElementById("inputCongelado").value,
        hecho: document.getElementById("inputHecho").value,
        minCongelado: document.getElementById("minCongelado").value,
        minHecho: document.getElementById("minHecho").value
    };

    /* =========================
    PRECONFIRMACION
    ========================= */
    Swal.fire({
        title: "¿Guardar cambios?",
        html: `
            <b>❄️ Congelado:</b> ${data.congelado}<br>
            <b>🔥 Horneado:</b> ${data.hecho}
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#c88e99",
        cancelButtonColor: "#999",
        confirmButtonText: "Sí, guardar",
        cancelButtonText: "Cancelar"
    }).then((result) => {

        if (result.isConfirmed) {

            fetch("api/update_stock.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(res => {

                if (res.ok) {

                    /* CERRAR MODAL */
                    cerrarModal();

                    /* MENSAJE DE EXITO */
                    Swal.fire({
                        title: "Guardado",
                        text: "El stock se actualizó correctamente",
                        icon: "success",
                        confirmButtonColor: "#c88e99"
                    }).then(() => {
                        location.reload(); // opcional
                    });

                } else {
                    Swal.fire("Error", "No se pudo guardar", "error");
                }

            });

        }

    });
}
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>