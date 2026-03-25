<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../../../config/conexion.php';
include '../../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$stmt = $pdo->query("
    SELECT 
        p.idproductos,
        p.nombre,
        sp.stock_actual AS stock_congelado
    FROM productos p
    INNER JOIN stock_productos sp
        ON sp.productos_idproductos = p.idproductos
        AND sp.tipo_stock = 'CONGELADO'
    WHERE sp.stock_actual > 0
    ORDER BY p.nombre ASC
");

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="horneado.css">

<!-- SWEET ALERT -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="horneado-container">

    <div class="titulo-horneado">🔥 Producción - Horneado</div>

    <div class="horneado-grid">

        <?php foreach ($productos as $p): 
            $sinStock = $p['stock_congelado'] <= 0;
        ?>

        <div class="horneado-card <?= $sinStock ? 'sin-stock' : '' ?>">

            <div class="producto-nombre">
                <?= htmlspecialchars($p['nombre']) ?>
            </div>

            <div class="stock-info">
                Stock congelado: 
                <span class="badge-stock <?= $sinStock ? 'badge-low' : 'badge-ok' ?>">
                    <?= number_format($p['stock_congelado'],2) ?>
                </span>
            </div>

            <button 
                class="btn-hornear"
                data-id="<?= $p['idproductos'] ?>"
                data-stock="<?= $p['stock_congelado'] ?>"
                data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                <?= $sinStock ? 'disabled' : '' ?>
            >
                Hornear
            </button>

        </div>

        <?php endforeach; ?>

    </div>

</div>

<!-- =========================
MODAL PROPIO
========================= -->
<div id="modalHorneado" class="modal">

    <div class="modal-content">

        <div class="modal-header">
            <h3 id="modalTitulo"></h3>
            <span class="cerrar" onclick="cerrarModal()">×</span>
        </div>

        <div class="modal-body">

            <input type="hidden" id="producto_id">

            <p>
                Stock disponible: 
                <strong id="stockDisponible"></strong>
            </p>

            <label>Cantidad a hornear</label>
            <input type="number" id="cantidadHornear" min="1" step="1">

            <div id="errorHorneado" class="error"></div>

        </div>

        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-confirmar" onclick="confirmarHorneado()">Confirmar</button>
        </div>

    </div>

</div>

<?php include '../../../panel/dashboard/layaut/footer.php'; ?>


<!-- =========================
JS
========================= -->
<script>

let productoActual = null;
let stockActual = 0;

/* ABRIR MODAL */
document.querySelectorAll(".btn-hornear").forEach(btn => {

    btn.addEventListener("click", () => {

        productoActual = btn.dataset.id;
        stockActual = parseFloat(btn.dataset.stock);

        document.getElementById("modalTitulo").innerText =
            "🔥 Hornear " + btn.dataset.nombre;

        document.getElementById("producto_id").value = productoActual;
        document.getElementById("stockDisponible").innerText = stockActual;

        document.getElementById("cantidadHornear").value = "";
        document.getElementById("errorHorneado").innerText = "";

        document.getElementById("modalHorneado").classList.add("open");

    });

});

/* CERRAR */
function cerrarModal() {
    document.getElementById("modalHorneado").classList.remove("open");
}

/* CONFIRMAR */
function confirmarHorneado() {

    const cantidad = parseFloat(document.getElementById("cantidadHornear").value);
    const error = document.getElementById("errorHorneado");

    error.innerText = "";

    if (!cantidad || cantidad <= 0) {
        error.innerText = "Ingresá una cantidad válida";
        return;
    }

    if (cantidad > stockActual) {
        error.innerText = "No podés hornear más que el stock disponible";
        return;
    }

    // 🔥 CERRAMOS TU MODAL PRIMERO
    cerrarModal();

    Swal.fire({
        title: '¿Confirmar horneado?',
        text: `Vas a hornear ${cantidad} cookies`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, hornear',
        cancelButtonText: 'Cancelar'
    }).then((result) => {

        if (result.isConfirmed) {

            fetch("api/procesar_horneado.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    producto_id: productoActual,
                    cantidad: cantidad
                })
            })
            .then(res => res.json())
            .then(data => {

                if (data.status === "ok") {

                    Swal.fire({
                        icon: 'success',
                        title: 'Horneado realizado',
                        text: data.mensaje,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        location.reload();
                    }, 1200);

                } else {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.mensaje
                    });

                }

            })
            .catch(() => {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión'
                });

            });

        }

    });

}

</script>