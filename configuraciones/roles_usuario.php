<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Roles por Usuario";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

// Cargar todos los roles disponibles para el modal
$rolesDisponibles = $pdo->query("SELECT idroles, nombre FROM roles WHERE estado=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

$totalUsuarios  = (int)$pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
$conRol         = (int)$pdo->query("SELECT COUNT(DISTINCT usuario_idusuario) FROM usuarios_roles")->fetchColumn();
$sinRol         = $totalUsuarios - $conRol;
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
                Roles por usuario
            </div>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card__num" id="statTotal"><?= $totalUsuarios ?></div>
            <div class="stat-card__label">Total usuarios</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-success" id="statConRol"><?= $conRol ?></div>
            <div class="stat-card__label">Con rol asignado</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-warn" id="statSinRol"><?= $sinRol ?></div>
            <div class="stat-card__label">Sin rol</div>
        </div>
    </div>

    <div class="table-wrap">
        <table id="tablaRolUsuario" style="width:100%">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre completo</th>
                    <th>Roles asignados</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<!-- Modal gestión de roles -->
<div class="modal-overlay" id="modalRoles">
    <div class="modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalTitle">Roles de usuario</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <p style="font-size:.82rem; color:var(--ink-soft); margin-bottom:1.2rem;" id="modalSub"></p>
            <div class="role-check-list" id="roleCheckList"></div>
            <p id="sinRolesMsg" style="display:none; color:var(--ink-soft); font-size:.85rem; padding:1rem 0;">
                No hay roles activos disponibles. <a href="/canetto/configuraciones/roles.php" style="color:var(--ink); font-weight:600;">Crear roles</a>
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn-sm" onclick="closeModal()">Cancelar</button>
            <button class="btn-primary" id="btnGuardar" onclick="guardar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Guardar roles
            </button>
        </div>
    </div>
</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
<script>
const ROLES = <?= json_encode($rolesDisponibles) ?>;
let currentUserId = null;
let dt = null;

$(document).ready(function () {
    dt = $('#tablaRolUsuario').DataTable({
        ajax: {
            url: 'ajax/listar_roles_usuario.php',
            dataSrc: function(json) {
                const conRol = json.filter(u => u.roles_ids && u.roles_ids.length > 0).length;
                document.getElementById('statTotal').textContent   = json.length;
                document.getElementById('statConRol').textContent  = conRol;
                document.getElementById('statSinRol').textContent  = json.length - conRol;
                return json;
            }
        },
        columns: [
            { data: 'usuario', render: v => v ? '<code style="font-size:.78rem;background:#f5f5f5;padding:.15rem .4rem;border-radius:3px;">' + esc(v) + '</code>' : '<span style="color:var(--ink-soft)">—</span>' },
            { data: null, render: row => {
                const ap = row.apellido ? ' ' + esc(row.apellido) : '';
                return '<strong>' + esc(row.nombre) + ap + '</strong>';
            }},
            { data: null, render: row => {
                if (!row.roles_nombres) return '<span class="badge-empty">Sin roles</span>';
                return row.roles_nombres.split('||').map(r => '<span class="badge-role">' + esc(r) + '</span>').join('');
            }},
            {
                data: null, orderable: false, width: '130px',
                render: row =>
                    '<button class="btn-sm" onclick=\'gestionarRoles(' + JSON.stringify(row) + ')\'>' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/></svg>' +
                        'Gestionar roles</button>'
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 15,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: 3 }]
    });

    document.getElementById('modalRoles').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
});

function gestionarRoles(row) {
    currentUserId = row.idusuario;
    const ap = row.apellido ? ' ' + row.apellido : '';
    document.getElementById('modalTitle').textContent = 'Roles de ' + row.nombre + ap;
    document.getElementById('modalSub').textContent = 'Seleccioná los roles para este usuario. Los cambios reemplazan la asignación actual.';

    const asignados = row.roles_ids ? row.roles_ids.split(',').map(Number) : [];
    const lista = document.getElementById('roleCheckList');
    lista.innerHTML = '';

    if (ROLES.length === 0) {
        document.getElementById('sinRolesMsg').style.display = 'block';
        document.getElementById('btnGuardar').style.display = 'none';
    } else {
        document.getElementById('sinRolesMsg').style.display = 'none';
        document.getElementById('btnGuardar').style.display = 'inline-flex';
        ROLES.forEach(rol => {
            const checked = asignados.includes(rol.idroles) ? 'checked' : '';
            const id = 'rol_' + rol.idroles;
            lista.insertAdjacentHTML('beforeend',
                '<label class="role-check-item" for="' + id + '">' +
                    '<input type="checkbox" id="' + id + '" value="' + rol.idroles + '" ' + checked + '>' +
                    '<label for="' + id + '">' + esc(rol.nombre) + '</label>' +
                '</label>'
            );
        });
    }

    document.getElementById('modalRoles').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalRoles').classList.remove('open');
    document.body.style.overflow = '';
    currentUserId = null;
}

async function guardar() {
    const checkboxes = document.querySelectorAll('#roleCheckList input[type=checkbox]:checked');
    const roles_ids = Array.from(checkboxes).map(c => parseInt(c.value));

    const btn = document.getElementById('btnGuardar');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="loader"></span>';
    btn.disabled = true;

    try {
        const res = await ajax('ajax/guardar_roles_usuario.php', { usuario_id: currentUserId, roles_ids });
        if (res.ok) {
            closeModal();
            dt.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Roles actualizados', confirmButtonColor: '#0a0a0a', timer: 2500, timerProgressBar: true });
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

async function ajax(url, data) {
    const r = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
    return r.json();
}
function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
