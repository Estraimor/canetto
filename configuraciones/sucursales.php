<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Sucursales";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

// Crear tabla si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS `sucursal` (
    `idsucursal` INT(11) NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `direccion` VARCHAR(200) DEFAULT NULL,
    `ciudad` VARCHAR(100) DEFAULT NULL,
    `provincia` VARCHAR(100) DEFAULT NULL,
    `telefono` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`idsucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$total   = (int)$pdo->query("SELECT COUNT(*) FROM sucursal")->fetchColumn();
$activas = (int)$pdo->query("SELECT COUNT(*) FROM sucursal WHERE activo=1")->fetchColumn();
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
                Sucursales
            </div>
        </div>
        <button class="btn-primary" onclick="openModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva sucursal
        </button>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card__num" id="statTotal"><?= $total ?></div>
            <div class="stat-card__label">Total sucursales</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-success" id="statActivas"><?= $activas ?></div>
            <div class="stat-card__label">Activas</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-danger" id="statInactivas"><?= $total - $activas ?></div>
            <div class="stat-card__label">Inactivas</div>
        </div>
    </div>

    <div class="table-wrap">
        <table id="tablaSucursales" style="width:100%">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Ciudad</th>
                    <th>Provincia</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<!-- Modal -->
<div class="modal-overlay" id="modalSucursal">
    <div class="modal" style="max-width:620px;" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalTitle">Nueva sucursal</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre de la sucursal *</label>
                    <input type="text" id="sNombre" placeholder="Ej: Casa Central, Sucursal Norte...">
                </div>
                <div class="form-group full">
                    <label>Dirección</label>
                    <input type="text" id="sDireccion" placeholder="Av. Independencia 1234">
                </div>
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" id="sCiudad" placeholder="Posadas">
                </div>
                <div class="form-group">
                    <label>Provincia</label>
                    <input type="text" id="sProvincia" placeholder="Misiones">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" id="sTelefono" placeholder="+54 376 000-0000">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="sEmail" placeholder="sucursal@canetto.com">
                </div>
                <div class="form-group full">
                    <label>Estado</label>
                    <div class="toggle-wrap">
                        <label class="toggle">
                            <input type="checkbox" id="sActivo" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label" id="toggleLabel">Activa</span>
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
    dt = $('#tablaSucursales').DataTable({
        ajax: {
            url: 'ajax/listar_sucursales.php',
            dataSrc: function(json) {
                const total   = json.length;
                const activas = json.filter(s => s.activo == 1).length;
                document.getElementById('statTotal').textContent    = total;
                document.getElementById('statActivas').textContent  = activas;
                document.getElementById('statInactivas').textContent = total - activas;
                return json;
            }
        },
        columns: [
            { data: 'nombre', render: v => '<strong>' + esc(v) + '</strong>' },
            { data: 'direccion', render: v => v ? esc(v) : '<span style="color:var(--ink-soft)">—</span>' },
            { data: 'ciudad',    render: v => v ? esc(v) : '<span style="color:var(--ink-soft)">—</span>' },
            { data: 'provincia', render: v => v ? esc(v) : '<span style="color:var(--ink-soft)">—</span>' },
            { data: 'telefono',  render: v => v ? esc(v) : '<span style="color:var(--ink-soft)">—</span>' },
            { data: null, render: row => row.activo == 1
                ? '<span class="badge-activo"><i class="fa-solid fa-circle" style="font-size:.4rem"></i>Activa</span>'
                : '<span class="badge-inactivo"><i class="fa-solid fa-circle" style="font-size:.4rem"></i>Inactiva</span>'
            },
            {
                data: null, orderable: false, width: '160px',
                render: row =>
                    '<div class="actions-cell">' +
                    '<button class="btn-sm" onclick=\'editarRow(' + JSON.stringify(row) + ')\'>' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Editar</button>' +
                    '<button class="btn-sm danger" onclick="confirmarEliminar(' + row.idsucursal + ',\'' + esc(row.nombre) + '\')">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Eliminar</button>' +
                    '</div>'
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 15,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: 6 }]
    });

    document.getElementById('sActivo').addEventListener('change', function() {
        document.getElementById('toggleLabel').textContent = this.checked ? 'Activa' : 'Inactiva';
    });
    document.getElementById('modalSucursal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
});

function openModal() {
    editId = null;
    document.getElementById('modalTitle').textContent = 'Nueva sucursal';
    ['sNombre','sDireccion','sCiudad','sProvincia','sTelefono','sEmail'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('sActivo').checked = true;
    document.getElementById('toggleLabel').textContent = 'Activa';
    document.getElementById('modalSucursal').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('sNombre').focus(), 200);
}

function closeModal() {
    document.getElementById('modalSucursal').classList.remove('open');
    document.body.style.overflow = '';
    editId = null;
}

function editarRow(row) {
    editId = row.idsucursal;
    document.getElementById('modalTitle').textContent = 'Editar sucursal';
    document.getElementById('sNombre').value    = row.nombre    || '';
    document.getElementById('sDireccion').value = row.direccion || '';
    document.getElementById('sCiudad').value    = row.ciudad    || '';
    document.getElementById('sProvincia').value = row.provincia || '';
    document.getElementById('sTelefono').value  = row.telefono  || '';
    document.getElementById('sEmail').value     = row.email     || '';
    const activo = row.activo == 1;
    document.getElementById('sActivo').checked = activo;
    document.getElementById('toggleLabel').textContent = activo ? 'Activa' : 'Inactiva';
    document.getElementById('modalSucursal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

async function guardar() {
    const nombre = document.getElementById('sNombre').value.trim();
    if (!nombre) {
        Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El nombre de la sucursal es obligatorio.', confirmButtonColor: '#0a0a0a' });
        return;
    }
    const btn = document.getElementById('btnGuardar');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="loader"></span>';
    btn.disabled = true;

    try {
        const res = await ajax('ajax/guardar_sucursal.php', {
            idsucursal: editId,
            nombre,
            direccion: document.getElementById('sDireccion').value.trim(),
            ciudad:    document.getElementById('sCiudad').value.trim(),
            provincia: document.getElementById('sProvincia').value.trim(),
            telefono:  document.getElementById('sTelefono').value.trim(),
            email:     document.getElementById('sEmail').value.trim(),
            activo:    document.getElementById('sActivo').checked ? 1 : 0
        });
        if (res.ok) {
            closeModal();
            dt.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: editId ? 'Actualizada' : 'Creada', text: '"' + nombre + '" fue guardada.', confirmButtonColor: '#0a0a0a', timer: 2500, timerProgressBar: true });
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
        title: '¿Eliminar sucursal?',
        html: 'Se eliminará <strong>' + esc(nombre) + '</strong>.<br>Esta acción no se puede deshacer.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#0a0a0a', cancelButtonColor: '#e0e0e0',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const res = await ajax('ajax/eliminar_sucursal.php', { idsucursal: id });
            if (res.ok) {
                dt.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Eliminada', confirmButtonColor: '#0a0a0a', timer: 2000, timerProgressBar: true });
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
