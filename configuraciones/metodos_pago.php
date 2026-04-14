<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Métodos de Pago";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();
$total = (int)$pdo->query("SELECT COUNT(*) FROM metodo_pago")->fetchColumn();
?>

<link rel="stylesheet" href="<?= URL_ASSETS ?>/configuraciones/cfg.css">

<div class="cfg-module">

    <div class="cfg-page-header">
        <div class="cfg-page-header__left">
            <a class="cfg-back" href="<?= URL_ASSETS ?>/configuraciones/index.php">
                <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
            </a>
            <div class="cfg-page-title">
                <span>Configuración</span>
                Métodos de pago
            </div>
        </div>
        <button class="btn-primary" onclick="openModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo método
        </button>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card__num" id="statTotal"><?= $total ?></div>
            <div class="stat-card__label">Total métodos</div>
        </div>
    </div>

    <div class="table-wrap">
        <table id="tablaMetodos" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre del método</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<!-- Modal -->
<div class="modal-overlay" id="modalMetodo">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo método de pago</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre del método *</label>
                    <input type="text" id="mNombre" placeholder="Ej: Efectivo, Transferencia..." required>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-sm" onclick="closeModal()">Cancelar</button>
            <button class="btn-primary" id="btnGuardar" onclick="guardar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Guardar
            </button>
        </div>
    </div>
</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
<script>
let editId = null;
let dt = null;

$(document).ready(function () {
    dt = $('#tablaMetodos').DataTable({
        ajax: {
            url: 'ajax/listar_metodos_pago.php',
            dataSrc: function(json) {
                document.getElementById('statTotal').textContent = json.length;
                return json;
            }
        },
        columns: [
            { data: 'idmetodo_pago', width: '60px' },
            { data: 'nombre', render: v => '<strong>' + esc(v) + '</strong>' },
            {
                data: null, orderable: false, width: '160px',
                render: row =>
                    '<div class="actions-cell">' +
                    '<button class="btn-sm" onclick=\'editarRow(' + JSON.stringify(row) + ')\'>' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Editar</button>' +
                    '<button class="btn-sm danger" onclick="confirmarEliminar(' + row.idmetodo_pago + ',\'' + esc(row.nombre) + '\')">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Eliminar</button>' +
                    '</div>'
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 15,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: 2 }]
    });

    document.getElementById('modalMetodo').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
});

function openModal() {
    editId = null;
    document.getElementById('modalTitle').textContent = 'Nuevo método de pago';
    document.getElementById('mNombre').value = '';
    document.getElementById('modalMetodo').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('mNombre').focus(), 200);
}

function closeModal() {
    document.getElementById('modalMetodo').classList.remove('open');
    document.body.style.overflow = '';
    editId = null;
}

function editarRow(row) {
    editId = row.idmetodo_pago;
    document.getElementById('modalTitle').textContent = 'Editar método de pago';
    document.getElementById('mNombre').value = row.nombre || '';
    document.getElementById('modalMetodo').classList.add('open');
    document.body.style.overflow = 'hidden';
}

async function guardar() {
    const nombre = document.getElementById('mNombre').value.trim();
    if (!nombre) {
        Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El nombre es obligatorio.', confirmButtonColor: '#0a0a0a' });
        return;
    }
    const btn = document.getElementById('btnGuardar');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="loader"></span>';
    btn.disabled = true;

    try {
        const res = await ajax('ajax/guardar_metodo_pago.php', { idmetodo_pago: editId, nombre });
        if (res.ok) {
            closeModal();
            dt.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: editId ? 'Actualizado' : 'Creado', text: '"' + nombre + '" fue guardado.', confirmButtonColor: '#0a0a0a', timer: 2500, timerProgressBar: true });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.msg || 'No se pudo guardar.', confirmButtonColor: '#0a0a0a' });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonColor: '#0a0a0a' });
    } finally {
        btn.innerHTML = orig;
        btn.disabled = false;
    }
}

function confirmarEliminar(id, nombre) {
    Swal.fire({
        title: '¿Eliminar método?',
        html: 'Se eliminará <strong>' + esc(nombre) + '</strong>.<br>Esta acción no se puede deshacer.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#0a0a0a', cancelButtonColor: '#e0e0e0',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const res = await ajax('ajax/eliminar_metodo_pago.php', { idmetodo_pago: id });
            if (res.ok) {
                dt.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Eliminado', confirmButtonColor: '#0a0a0a', timer: 2000, timerProgressBar: true });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.msg, confirmButtonColor: '#0a0a0a' });
            }
        } catch(e) { Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonColor: '#0a0a0a' }); }
    });
}

async function ajax(url, data) {
    const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
    return r.json();
}
function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
