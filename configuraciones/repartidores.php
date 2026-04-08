<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Repartidores";
include '../panel/dashboard/layaut/nav.php';
?>

<link rel="stylesheet" href="/canetto/configuraciones/cfg.css">

<div class="cfg-module">

    <div class="cfg-page-header">
        <div class="cfg-page-header__left">
            <a class="cfg-back" href="/canetto/configuraciones/index.php">
                <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
            </a>
            <div class="cfg-page-title">
                <span>Configuración</span>
                Repartidores
            </div>
        </div>
        <button class="btn-primary" onclick="openModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo repartidor
        </button>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card__num" id="statTotal">—</div>
            <div class="stat-card__label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-success" id="statActivos">—</div>
            <div class="stat-card__label">Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-danger" id="statInactivos">—</div>
            <div class="stat-card__label">Inactivos</div>
        </div>
    </div>

    <p style="font-size:13px;color:#64748b;margin:0 0 16px;padding:0 4px">
        <i class="fa-solid fa-circle-info" style="color:#3b82f6"></i>
        Los repartidores son usuarios del sistema con el rol <strong>Repartidor</strong>.
        Ingresan a la app con su celular y contraseña desde
        <a href="/canetto/repartidor/" target="_blank" style="color:#3b82f6">/repartidor/</a>.
    </p>

    <div class="table-wrap">
        <table id="tblRepartidores" style="width:100%">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Celular</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalRep">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo repartidor</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="formRep" onsubmit="return false;">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Nombre *</label>
                        <input type="text" id="repNombre" placeholder="Juan">
                    </div>
                    <div class="form-group">
                        <label>Apellido</label>
                        <input type="text" id="repApellido" placeholder="García">
                    </div>
                    <div class="form-group">
                        <label>Celular * <span style="color:var(--ink-soft);font-weight:400;text-transform:none;font-size:.72rem">(para ingresar a la app)</span></label>
                        <input type="tel" id="repCelular" placeholder="Ej: 1123456789">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="repEmail" placeholder="email@ejemplo.com">
                    </div>
                    <div class="form-group full">
                        <label>Contraseña <span id="passHint" style="color:var(--ink-soft);font-weight:400;text-transform:none;font-size:.72rem"></span></label>
                        <input type="password" id="repPassword" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                    </div>
                    <div class="form-group full">
                        <label>Estado</label>
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="checkbox" id="repActivo" checked>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-label" id="toggleLabel">Activo</span>
                        </div>
                    </div>

                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-sm" onclick="closeModal()">Cancelar</button>
            <button class="btn-primary" id="btnGuardar" onclick="guardarRepartidor()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Guardar
            </button>
        </div>
    </div>
</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>

<script>
let editId = null;
let dtRep  = null;

$(document).ready(function () {
    initDataTable();

    document.getElementById('repActivo').addEventListener('change', function () {
        document.getElementById('toggleLabel').textContent = this.checked ? 'Activo' : 'Inactivo';
    });

    document.getElementById('modalRep').addEventListener('click', function (e) {
        if (e.target === this) closeModal();
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });
});

function initDataTable() {
    dtRep = $('#tblRepartidores').DataTable({
        ajax: {
            url: 'ajax/listar_repartidores.php',
            dataSrc: function (json) {
                actualizarStats(json);
                return json;
            }
        },
        columns: [
            {
                data: null,
                render: row => '<strong>' + esc(row.nombre) + (row.apellido ? ' ' + esc(row.apellido) : '') + '</strong>'
            },
            { data: 'celular', render: v => v ? esc(v) : '<span style="color:var(--ink-soft)">—</span>' },
            { data: 'email',   render: v => v ? esc(v) : '<span style="color:var(--ink-soft)">—</span>' },
            {
                data: 'activo',
                render: v => v == 1
                    ? '<span class="badge-activo">Activo</span>'
                    : '<span class="badge-inactivo">Inactivo</span>'
            },
            {
                data: null,
                orderable: false,
                render: row => `<div class="actions-cell">
                    <button class="btn-sm" onclick='editarRepartidor(${JSON.stringify(row)})'>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Editar
                    </button>
                </div>`
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 10,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: 4 }]
    });
}

function actualizarStats(data) {
    const total   = data.length;
    const activos = data.filter(r => r.activo == 1).length;
    document.getElementById('statTotal').textContent     = total;
    document.getElementById('statActivos').textContent   = activos;
    document.getElementById('statInactivos').textContent = total - activos;
}

function openModal() {
    editId = null;
    document.getElementById('modalTitle').textContent  = 'Nuevo repartidor';
    document.getElementById('formRep').reset();
    document.getElementById('repActivo').checked       = true;
    document.getElementById('toggleLabel').textContent = 'Activo';
    document.getElementById('passHint').textContent    = '(obligatoria)';
    document.getElementById('modalRep').classList.add('open');
    document.body.style.overflow = 'hidden';
    document.getElementById('repNombre').focus();
}

function closeModal() {
    document.getElementById('modalRep').classList.remove('open');
    document.body.style.overflow = '';
    editId = null;
}

function editarRepartidor(row) {
    editId = row.idusuario;
    document.getElementById('modalTitle').textContent  = 'Editar repartidor';
    document.getElementById('repNombre').value         = row.nombre   || '';
    document.getElementById('repApellido').value       = row.apellido || '';
    document.getElementById('repCelular').value        = row.celular  || '';
    document.getElementById('repEmail').value          = row.email    || '';
    document.getElementById('repPassword').value       = '';
    document.getElementById('passHint').textContent    = '(dejar vacío para no cambiar)';
    const activo = row.activo == 1;
    document.getElementById('repActivo').checked       = activo;
    document.getElementById('toggleLabel').textContent = activo ? 'Activo' : 'Inactivo';
    document.getElementById('modalRep').classList.add('open');
    document.body.style.overflow = 'hidden';
}

async function guardarRepartidor() {
    const nombre   = document.getElementById('repNombre').value.trim();
    const celular  = document.getElementById('repCelular').value.trim();
    const password = document.getElementById('repPassword').value;

    if (!nombre) {
        Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El nombre es obligatorio.', confirmButtonColor: '#0a0a0a' });
        return;
    }
    if (!celular) {
        Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El celular es obligatorio (se usa para ingresar a la app).', confirmButtonColor: '#0a0a0a' });
        return;
    }
    if (!editId && !password) {
        Swal.fire({ icon: 'warning', title: 'Contraseña requerida', text: 'Debés ingresar una contraseña para el nuevo repartidor.', confirmButtonColor: '#0a0a0a' });
        return;
    }

    const btn = document.getElementById('btnGuardar');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<span class="loader"></span>';
    btn.disabled  = true;

    const body = {
        idusuario: editId ? parseInt(editId) : null,
        nombre,
        apellido: document.getElementById('repApellido').value.trim(),
        celular,
        email:    document.getElementById('repEmail').value.trim(),
        password,
        activo:   document.getElementById('repActivo').checked ? 1 : 0,
    };

    try {
        const res  = await fetch('ajax/guardar_repartidor.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(body),
        });
        const data = await res.json();

        if (data.ok) {
            closeModal();
            dtRep.ajax.reload(null, false);
            Swal.fire({
                icon: 'success',
                title: editId ? '¡Repartidor actualizado!' : '¡Repartidor creado!',
                text: editId
                    ? 'Los datos de "' + nombre + '" fueron guardados.'
                    : '"' + nombre + '" fue agregado al sistema.',
                confirmButtonColor: '#0a0a0a',
                confirmButtonText: 'Continuar',
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.msg || 'No se pudo guardar.', confirmButtonColor: '#0a0a0a' });
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.', confirmButtonColor: '#0a0a0a' });
    } finally {
        btn.innerHTML = originalHTML;
        btn.disabled  = false;
    }
}

function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
</script>
