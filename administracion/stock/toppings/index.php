<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
include '../../../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$pdo->exec("CREATE TABLE IF NOT EXISTS toppings (
    idtoppings INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT NOW()
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS toppings_stock (
    idtoppings_stock INT AUTO_INCREMENT PRIMARY KEY,
    toppings_idtoppings INT NOT NULL,
    stock_actual DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE KEY uq_tp (toppings_idtoppings)
)");

$toppings = $pdo->query("
    SELECT
        t.idtoppings,
        t.nombre,
        t.precio,
        t.activo,
        COALESCE(ts.stock_actual, 0) AS stock_actual,
        COALESCE(ts.stock_minimo, 0) AS stock_minimo
    FROM toppings t
    LEFT JOIN toppings_stock ts ON ts.toppings_idtoppings = t.idtoppings
    ORDER BY t.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$total = count($toppings);
$bajos = count(array_filter($toppings, fn($t) => $t['activo'] && ($t['stock_actual'] <= 0 || ($t['stock_minimo'] > 0 && $t['stock_actual'] <= $t['stock_minimo']))));
?>

<!-- DataTables -->
<link  rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<link  rel="stylesheet" href="toppings.css">

<div class="tp-wrap">

<a href="javascript:history.back()" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Volver
</a>

    <div class="tp-page-header">
        <div>
            <h1>✨ Toppings</h1>
            <p>Stock, precios y gestión de extras</p>
        </div>
        <button class="tp-btn-new" onclick="abrirModal()">
            <i class="fa-solid fa-plus"></i> Nuevo topping
        </button>
    </div>

    <div class="tp-stats-row">
        <div class="tp-stat-box">
            <div class="tp-stat-num"><?= $total ?></div>
            <div class="tp-stat-lbl">Total</div>
        </div>
        <div class="tp-stat-box <?= $bajos > 0 ? 'warn' : '' ?>">
            <div class="tp-stat-num"><?= $bajos ?></div>
            <div class="tp-stat-lbl">Stock bajo</div>
        </div>
    </div>

    <div class="tp-table-wrap">
        <table id="tpDT" class="tp-table" style="width:100%">
            <thead>
                <tr>
                    <th>Topping</th>
                    <th>Precio extra</th>
                    <th>Stock actual</th>
                    <th>Stock mínimo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($toppings as $t):
                $bajo = $t['activo'] && ($t['stock_actual'] <= 0 || ($t['stock_minimo'] > 0 && $t['stock_actual'] <= $t['stock_minimo']));
                $edArgs = implode(', ', [
                    $t['idtoppings'],
                    htmlspecialchars(json_encode($t['nombre']), ENT_QUOTES),
                    (float)$t['precio'],
                    (int)$t['activo'],
                    (float)$t['stock_actual'],
                    (float)$t['stock_minimo'],
                ]);
            ?>
                <tr>
                    <td data-order="<?= htmlspecialchars($t['nombre']) ?>">
                        <span class="tp-td-nombre"><?= htmlspecialchars($t['nombre']) ?></span>
                        <?= $bajo ? '<span class="badge-low">⚠ bajo</span>' : '' ?>
                    </td>
                    <td data-order="<?= $t['precio'] ?>">
                        <?= $t['precio'] > 0
                            ? '<span class="td-precio">+$' . number_format($t['precio'], 0, ',', '.') . '</span>'
                            : '<span class="td-gratis">Gratis</span>' ?>
                    </td>
                    <td data-order="<?= $t['stock_actual'] ?>" class="<?= $bajo ? 'td-stock-low' : 'td-stock-ok' ?>">
                        <?= number_format($t['stock_actual'], 2) ?>
                    </td>
                    <td data-order="<?= $t['stock_minimo'] ?>" class="td-minimo">
                        <?= number_format($t['stock_minimo'], 0) ?>
                    </td>
                    <td data-order="<?= $t['activo'] ?>">
                        <?= $t['activo']
                            ? '<span class="badge-activo"><i class="fa-solid fa-circle" style="font-size:7px"></i> Activo</span>'
                            : '<span class="badge-inactivo"><i class="fa-solid fa-circle" style="font-size:7px"></i> Inactivo</span>' ?>
                    </td>
                    <td>
                        <button class="tp-icon-btn edit" title="Editar" onclick="abrirModal(<?= $edArgs ?>)">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="tp-icon-btn del" title="Eliminar"
                            onclick="eliminar(<?= $t['idtoppings'] ?>, <?= htmlspecialchars(json_encode($t['nombre']), ENT_QUOTES) ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Modal -->
<div class="tp-modal-overlay" id="modalOverlay" onclick="cerrarModal()"></div>
<div class="tp-modal" id="modal">
    <div class="tp-modal-head">
        <span id="modalTitulo">Nuevo topping</span>
        <button class="tp-modal-x" onclick="cerrarModal()">✖</button>
    </div>
    <div class="tp-modal-body">
        <input type="hidden" id="mId">

        <div class="mf-row">
            <div class="mf-group" style="flex:2">
                <label>Nombre *</label>
                <input type="text" id="mNombre" placeholder="Ej: Glasé de chocolate">
            </div>
            <div class="mf-group">
                <label>Precio extra ($)</label>
                <input type="number" id="mPrecio" min="0" step="1" placeholder="0">
            </div>
        </div>

        <div class="mf-row">
            <div class="mf-group">
                <label>Stock actual</label>
                <input type="number" id="mStock" min="0" step="0.01" placeholder="0">
            </div>
            <div class="mf-group">
                <label>Stock mínimo</label>
                <input type="number" id="mMinimo" min="0" step="0.01" placeholder="0">
            </div>
            <div class="mf-group">
                <label>Estado</label>
                <select id="mActivo">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
        </div>

        <div class="tp-modal-alert" id="mAlert"></div>

        <div class="mf-actions">
            <button class="tp-btn-soft" onclick="cerrarModal()">Cancelar</button>
            <button class="tp-btn-new" id="btnGuardar" onclick="guardar()">
                <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script>
$(function() {
    $('#tpDT').DataTable({
        language: {
            search:           '',
            searchPlaceholder: '🔍  Buscar topping...',
            lengthMenu:       'Mostrar _MENU_ por página',
            zeroRecords:      'No se encontraron toppings',
            emptyTable:       'No hay toppings cargados',
            info:             'Mostrando _START_ a _END_ de _TOTAL_ toppings',
            infoEmpty:        'Sin resultados',
            infoFiltered:     '(filtrado de _MAX_ en total)',
            paginate: {
                first:    '«',
                last:     '»',
                next:     '›',
                previous: '‹',
            }
        },
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50, 100],
        order: [[0, 'asc']],
        columnDefs: [
            { targets: [1, 2, 3, 4], className: 'dt-center' },
            { targets: 5, orderable: false, className: 'dt-center' },
        ],
        dom:
            "<'dt-top'<'dt-search'f><'dt-len'l>>" +
            "<'dt-body'tr>" +
            "<'dt-foot'<'dt-info'i><'dt-pag'p>>",
    });
});

/* ── Modal ── */
function abrirModal(id=0, nombre='', precio=0, activo=1, stock=0, minimo=0) {
    document.getElementById('mId').value     = id;
    document.getElementById('mNombre').value = nombre;
    document.getElementById('mPrecio').value = precio || '';
    document.getElementById('mActivo').value = activo;
    document.getElementById('mStock').value  = stock  || '';
    document.getElementById('mMinimo').value = minimo || '';
    document.getElementById('modalTitulo').textContent = id ? 'Editar topping' : 'Nuevo topping';
    document.getElementById('mAlert').textContent = '';
    document.getElementById('mAlert').className   = 'tp-modal-alert';
    document.getElementById('modalOverlay').classList.add('open');
    document.getElementById('modal').classList.add('open');
    setTimeout(() => document.getElementById('mNombre').focus(), 100);
}

function cerrarModal() {
    document.getElementById('modalOverlay').classList.remove('open');
    document.getElementById('modal').classList.remove('open');
}

async function guardar() {
    const id     = parseInt(document.getElementById('mId').value) || 0;
    const nombre = document.getElementById('mNombre').value.trim();
    const precio = parseFloat(document.getElementById('mPrecio').value) || 0;
    const activo = parseInt(document.getElementById('mActivo').value);
    const stock  = parseFloat(document.getElementById('mStock').value)  || 0;
    const minimo = parseFloat(document.getElementById('mMinimo').value) || 0;
    const alEl   = document.getElementById('mAlert');

    if (!nombre) {
        alEl.textContent = 'El nombre es obligatorio.';
        alEl.className   = 'tp-modal-alert show';
        document.getElementById('mNombre').focus();
        return;
    }

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    try {
        const res  = await fetch('api/guardar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, nombre, precio, activo, stock, minimo })
        });
        const data = await res.json();
        if (data.ok) { cerrarModal(); location.reload(); }
        else {
            alEl.textContent = data.msg || 'Error al guardar.';
            alEl.className   = 'tp-modal-alert show';
        }
    } catch {
        alEl.textContent = 'Error de conexión.';
        alEl.className   = 'tp-modal-alert show';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';
}

async function eliminar(id, nombre) {
    const r = await Swal.fire({
        title: '¿Eliminar topping?',
        html: `Se eliminará <strong>${nombre}</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c0392b',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    if (!r.isConfirmed) return;

    const res  = await fetch('api/eliminar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    const data = await res.json();
    if (data.ok) location.reload();
    else Swal.fire('Error', data.msg || 'No se pudo eliminar', 'error');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

// Auto-abrir topping si viene ?open=ID
(function() {
    const openId = new URLSearchParams(location.search).get('open');
    if (!openId) return;
    // Buscar el botón editar de ese ID y dispararlo
    const btn = document.querySelector(`.tp-icon-btn.edit[onclick*="abrirModal(${openId},"]`);
    if (btn) {
        setTimeout(() => { btn.click(); }, 400);
    }
})();
</script>

<?php include '../../../panel/dashboard/layaut/footer.php'; ?>
