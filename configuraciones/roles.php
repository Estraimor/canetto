<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Roles";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();
$total   = (int)$pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
$activos = (int)$pdo->query("SELECT COUNT(*) FROM roles WHERE estado=1")->fetchColumn();
?>

<link rel="stylesheet" href="<?= base() ?>/configuraciones/cfg.css">

<div class="cfg-module">

    <div class="cfg-page-header">
        <div class="cfg-page-header__left">
            <a class="cfg-back" href="<?= base() ?>/configuraciones/index.php">
                <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
            </a>
            <div class="cfg-page-title">
                <span>Configuración</span>
                Roles
            </div>
        </div>
        <button class="btn-primary" onclick="openModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo rol
        </button>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card__num" id="statTotal"><?= $total ?></div>
            <div class="stat-card__label">Total roles</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-success" id="statActivos"><?= $activos ?></div>
            <div class="stat-card__label">Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-danger" id="statInactivos"><?= $total - $activos ?></div>
            <div class="stat-card__label">Inactivos</div>
        </div>
    </div>

    <div class="table-wrap">
        <table id="tablaRoles" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre del rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<!-- Modal -->
<div class="modal-overlay" id="modalRol">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo rol</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre del rol *</label>
                    <input type="text" id="rNombre" placeholder="Ej: Administrador, Operario...">
                </div>
                <div class="form-group full">
                    <label>Estado</label>
                    <div class="toggle-wrap">
                        <label class="toggle">
                            <input type="checkbox" id="rEstado" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label" id="toggleLabel">Activo</span>
                    </div>
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
    dt = $('#tablaRoles').DataTable({
        ajax: {
            url: 'ajax/listar_roles.php',
            dataSrc: function(json) {
                const total    = json.length;
                const activos  = json.filter(r => r.estado == 1).length;
                document.getElementById('statTotal').textContent    = total;
                document.getElementById('statActivos').textContent  = activos;
                document.getElementById('statInactivos').textContent = total - activos;
                return json;
            }
        },
        columns: [
            { data: 'idroles', width: '60px' },
            { data: 'nombre', render: v => '<strong>' + esc(v) + '</strong>' },
            { data: null, render: row => row.estado == 1
                ? '<span class="badge-activo"><i class="fa-solid fa-circle" style="font-size:.4rem"></i>Activo</span>'
                : '<span class="badge-inactivo"><i class="fa-solid fa-circle" style="font-size:.4rem"></i>Inactivo</span>'
            },
            {
                data: null, orderable: false, width: '160px',
                render: row =>
                    '<div class="actions-cell">' +
                    '<button class="btn-sm" onclick=\'editarRow(' + JSON.stringify(row) + ')\'>' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Editar</button>' +
                    '<button class="btn-sm danger" onclick="confirmarEliminar(' + row.idroles + ',\'' + esc(row.nombre) + '\')">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Eliminar</button>' +
                    '</div>'
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 15,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: 3 }]
    });

    document.getElementById('rEstado').addEventListener('change', function() {
        document.getElementById('toggleLabel').textContent = this.checked ? 'Activo' : 'Inactivo';
    });
    document.getElementById('modalRol').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
});

function openModal() {
    editId = null;
    document.getElementById('modalTitle').textContent = 'Nuevo rol';
    document.getElementById('rNombre').value = '';
    document.getElementById('rEstado').checked = true;
    document.getElementById('toggleLabel').textContent = 'Activo';
    document.getElementById('modalRol').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('rNombre').focus(), 200);
}

function closeModal() {
    document.getElementById('modalRol').classList.remove('open');
    document.body.style.overflow = '';
    editId = null;
}

function editarRow(row) {
    editId = row.idroles;
    document.getElementById('modalTitle').textContent = 'Editar rol';
    document.getElementById('rNombre').value = row.nombre || '';
    const activo = row.estado == 1;
    document.getElementById('rEstado').checked = activo;
    document.getElementById('toggleLabel').textContent = activo ? 'Activo' : 'Inactivo';
    document.getElementById('modalRol').classList.add('open');
    document.body.style.overflow = 'hidden';
}

async function guardar() {
    const nombre = document.getElementById('rNombre').value.trim();
    if (!nombre) {
        Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El nombre del rol es obligatorio.', confirmButtonColor: '#0a0a0a' });
        return;
    }
    const btn = document.getElementById('btnGuardar');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="loader"></span>';
    btn.disabled = true;

    try {
        const res = await ajax('ajax/guardar_rol.php', {
            idroles: editId,
            nombre,
            estado: document.getElementById('rEstado').checked ? 1 : 0
        });
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
        title: '¿Eliminar rol?',
        html: 'Se eliminará <strong>' + esc(nombre) + '</strong>.<br>Esto también quitará el rol de todos los usuarios asignados.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#0a0a0a', cancelButtonColor: '#e0e0e0',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const res = await ajax('ajax/eliminar_rol.php', { idroles: id });
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
