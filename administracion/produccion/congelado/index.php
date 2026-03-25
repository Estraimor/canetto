<?php
define('APP_BOOT', true);

require_once __DIR__ . '/../../../config/conexion.php';
include '../../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

/* =========================
PRODUCTOS CON RECETA
========================= */
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
?>

<link rel="stylesheet" href="congelado.css">

<div id="modulo-congelado">

    <div class="congelado-container">

        <h2 class="titulo-seccion">❄️ Producción - Congelado</h2>

        <div class="recetas-grid">

            <?php if (!empty($productos)): ?>

                <?php foreach ($productos as $p): ?>

                    <div class="receta-card">

                        <div class="receta-nombre">
                            <?= htmlspecialchars($p['producto_nombre']) ?>
                        </div>

                        <div class="receta-info">
                            Produce <?= (int)$p['cantidad_galletas'] ?> galletas
                        </div>

                        <button
                            class="btn-preparar"
                            data-producto-id="<?= (int)$p['idproductos'] ?>"
                            data-receta-id="<?= (int)$p['idrecetas'] ?>"
                            data-nombre="<?= htmlspecialchars($p['producto_nombre']) ?>"
                            data-galletas="<?= (float)$p['cantidad_galletas'] ?>">
                            Preparar
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

    </div>

</div>

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
                <input type="number" id="cantidad_galletas" min="1" step="1">
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
let recetaActual = null;
let productoActual = null;
let baseGalletas = 0;

/* =========================
ABRIR MODAL
========================= */
function abrirModal() {
    document.getElementById("modalPreparar").classList.add("open");
}

/* =========================
CERRAR MODAL
========================= */
function cerrarModal() {
    document.getElementById("modalPreparar").classList.remove("open");
}

/* =========================
BOTONES PREPARAR
========================= */
document.querySelectorAll(".btn-preparar").forEach(btn => {
    btn.addEventListener("click", () => {
        productoActual = btn.dataset.productoId;
        recetaActual = btn.dataset.recetaId;
        baseGalletas = parseFloat(btn.dataset.galletas) || 0;

        document.getElementById("tituloReceta").innerText =
            "Preparar " + btn.dataset.nombre;

        document.getElementById("producto_id").value = productoActual;
        document.getElementById("receta_id").value = recetaActual;
        document.getElementById("cantidad_galletas").value = Math.round(baseGalletas);

        abrirModal();
        calcularPreview();
    });
});

/* =========================
PORCENTAJE
========================= */
document.getElementById("porcentaje").addEventListener("change", function () {
    let porcentaje = parseFloat(this.value) || 100;
    let cant = baseGalletas * (porcentaje / 100);

    document.getElementById("cantidad_galletas").value = Math.round(cant);
    calcularPreview();
});

/* =========================
INPUT CANTIDAD
========================= */
document.getElementById("cantidad_galletas").addEventListener("input", () => {
    calcularPreview();
});

/* =========================
CONFIRMAR PRODUCCION
========================= */
document.getElementById("confirmarProduccion").addEventListener("click", () => {
    let cantidad = parseFloat(document.getElementById("cantidad_galletas").value);

    if (!recetaActual || !productoActual) {
        Swal.fire({
            icon: "error",
            title: "Error interno",
            text: "No se encontró el producto o la receta"
        });
        return;
    }

    if (!cantidad || cantidad <= 0) {
        Swal.fire({
            icon: "warning",
            title: "Cantidad inválida",
            text: "Ingresá una cantidad válida"
        });
        return;
    }

    cerrarModal();

    Swal.fire({
        title: "Procesando producción...",
        text: "Calculando ingredientes y validando stock",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch("api/producir.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            receta: recetaActual,
            producto: productoActual,
            cantidad: cantidad
        })
    })
    .then(async r => {
        let text = await r.text();
        console.log("PRODUCCION RAW:", text);

        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error("Respuesta inválida del servidor");
        }
    })
    .then(res => {
        if (res.status === "ok") {
            Swal.fire({
                icon: "success",
                title: "Producción realizada",
                text: res.mensaje || "Stock actualizado correctamente",
                confirmButtonColor: "#2ecc71"
            }).then(() => {
                location.reload();
            });
        } else {
            let detalle = "";

            if (res.detalle && Array.isArray(res.detalle)) {
                detalle = "<br><br><b>Faltantes:</b><br>" + res.detalle.join("<br>");
            }

            Swal.fire({
                icon: "error",
                title: res.mensaje || "Error en producción",
                html: detalle,
                confirmButtonColor: "#e74c3c"
            });
        }
    })
    .catch(error => {
        console.error("ERROR PRODUCCION:", error);

        Swal.fire({
            icon: "error",
            title: "Error del servidor",
            text: "No se pudo procesar la producción",
            confirmButtonColor: "#e74c3c"
        });
    });
});

/* =========================
PREVIEW CON AJAX
========================= */
function calcularPreview() {
    let cantidad = parseFloat(document.getElementById("cantidad_galletas").value);

    if (!recetaActual || !cantidad || cantidad <= 0) {
        document.getElementById("tablaIngredientes").innerHTML = "";
        return;
    }

    document.getElementById("tablaIngredientes").innerHTML =
        "<tr><td colspan='3'>⏳ Calculando...</td></tr>";

    $.ajax({
        url: "api/preview_receta.php",
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify({
            receta: recetaActual,
            cantidad: cantidad
        }),
        success: function (data) {
            console.log("PREVIEW:", data);

            if (typeof data === "string") {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    document.getElementById("tablaIngredientes").innerHTML =
                        "<tr><td colspan='3'>❌ JSON inválido</td></tr>";
                    return;
                }
            }

            if (!data || data.status !== "ok") {
                document.getElementById("tablaIngredientes").innerHTML =
                    "<tr><td colspan='3'>❌ Error backend</td></tr>";
                return;
            }

            if (!data.ingredientes || data.ingredientes.length === 0) {
                document.getElementById("tablaIngredientes").innerHTML =
                    "<tr><td colspan='3'>Sin ingredientes</td></tr>";
                return;
            }

            let html = "";

            data.ingredientes.forEach(i => {
                let clase = i.faltante
                    ? "style='color:#e74c3c;font-weight:600'"
                    : "";

                html += `
                    <tr ${clase}>
                        <td>${i.nombre}</td>
                        <td>${i.cantidad}</td>
                        <td>${i.unidad}</td>
                    </tr>
                `;
            });

            document.getElementById("tablaIngredientes").innerHTML = html;

            const btn = document.getElementById("confirmarProduccion");

            if (!data.puede_producir) {
                btn.disabled = true;
                btn.innerText = "❌ Stock insuficiente";
                btn.style.background = "#e74c3c";
            } else {
                btn.disabled = false;
                btn.innerText = "Confirmar producción";
                btn.style.background = "linear-gradient(135deg,#2ecc71,#27ae60)";
            }
        },
        error: function (xhr) {
            console.error("ERROR PREVIEW:", xhr.responseText);

            document.getElementById("tablaIngredientes").innerHTML =
                "<tr><td colspan='3'>❌ Error servidor</td></tr>";
        }
    });
}
</script>

<?php include '../../../panel/dashboard/layaut/footer.php'; ?>