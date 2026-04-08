<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Repartidores";
include '../panel/dashboard/layaut/nav.php';

$pdo     = Conexion::conectar();
$total   = (int)$pdo->query("SELECT COUNT(*) FROM usuario u INNER JOIN usuarios_roles ur ON ur.usuario_idusuario=u.idusuario INNER JOIN roles r ON r.idroles=ur.roles_idroles WHERE r.nombre='Repartidor'")->fetchColumn();
$activos = (int)$pdo->query("SELECT COUNT(*) FROM usuario u INNER JOIN usuarios_roles ur ON ur.usuario_idusuario=u.idusuario INNER JOIN roles r ON r.idroles=ur.roles_idroles WHERE r.nombre='Repartidor' AND u.activo=1")->fetchColumn();
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
            <div class="stat-card__num" id="statTotal"><?= $total ?></div>
            <div class="stat-card__label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num" id="statActivos"><?= $activos ?></div>
            <div class="stat-card__label">Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num"><?= $total - $activos ?></div>
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
        <table class="cfg-table" id="tblRepartidores">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Celular</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyRep">
                <tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modal" style="display:none">
    <div class="modal-box" style="max-width:500px">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo repartidor</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="repId">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="fg"><label>Nombre *</label><input type="text" id="repNombre" placeholder="Nombre"></div>
                <div class="fg"><label>Apellido</label><input type="text" id="repApellido" placeholder="Apellido"></div>
            </div>
            <div class="fg" style="margin-top:12px">
                <label>Celular * <span style="color:#64748b;font-weight:400">(para ingresar a la app)</span></label>
                <input type="tel" id="repCelular" placeholder="Ej: 1123456789">
            </div>
            <div class="fg" style="margin-top:12px">
                <label>Email</label>
                <input type="email" id="repEmail" placeholder="email@ejemplo.com">
            </div>
            <div class="fg" style="margin-top:12px">
                <label>Contraseña <span id="passHint" style="color:#94a3b8;font-weight:400"></span></label>
                <input type="password" id="repPassword" placeholder="Mínimo 6 caracteres">
            </div>
            <div class="fg" style="margin-top:12px">
                <label>Estado</label>
                <select id="repActivo">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div id="repError" style="color:#e74c3c;font-size:13px;margin-top:12px;padding:10px;background:#fee2e2;border-radius:8px;display:none"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn-primary" id="btnGuardar" onclick="guardarRepartidor()">Guardar</button>
        </div>
    </div>
</div>

<style>
.fg label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;margin-bottom:5px}
.fg input,.fg select{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-family:inherit;background:#f8fafc;transition:border-color .18s;color:#1e293b}
.fg input:focus,.fg select:focus{outline:none;border-color:#3b82f6;background:white;box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.badge-activo{background:#dcfce7;color:#166534;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700}
.badge-inactivo{background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700}
.modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:16px 20px;border-top:1px solid #f1f5f9}
.btn-secondary{padding:9px 18px;border-radius:9px;border:1.5px solid #e2e8f0;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:14px;font-family:inherit}
</style>

<script>
let repartidores = [];

async function cargarRepartidores() {
    const res       = await fetch('ajax/listar_repartidores.php');
    repartidores    = await res.json();
    const activos   = repartidores.filter(r => r.activo == 1).length;
    document.getElementById('statTotal').textContent   = repartidores.length;
    document.getElementById('statActivos').textContent = activos;
    renderTabla();
}

function renderTabla() {
    const tbody = document.getElementById('tbodyRep');
    if (!repartidores.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8">No hay repartidores. Creá uno con el botón de arriba.</td></tr>';
        return;
    }
    tbody.innerHTML = repartidores.map(r => `
        <tr>
            <td><strong>${r.nombre} ${r.apellido || ''}</strong></td>
            <td>${r.celular || '—'}</td>
            <td>${r.email  || '—'}</td>
            <td>${r.activo == 1
                ? '<span class="badge-activo">Activo</span>'
                : '<span class="badge-inactivo">Inactivo</span>'}</td>
            <td>
                <button class="btn-edit" onclick="editarRepartidor(${r.idusuario})">✏️ Editar</button>
            </td>
        </tr>`).join('');
}

function openModal(id = null) {
    document.getElementById('repId').value       = id || '';
    document.getElementById('repNombre').value   = '';
    document.getElementById('repApellido').value = '';
    document.getElementById('repCelular').value  = '';
    document.getElementById('repEmail').value    = '';
    document.getElementById('repPassword').value = '';
    document.getElementById('repActivo').value   = '1';
    document.getElementById('repError').style.display = 'none';
    document.getElementById('passHint').textContent   = id ? '(dejar vacío para no cambiar)' : '*';
    document.getElementById('modalTitle').textContent = id ? 'Editar repartidor' : 'Nuevo repartidor';
    document.getElementById('modal').style.display = 'flex';
    document.getElementById('repNombre').focus();
}

function editarRepartidor(id) {
    const r = repartidores.find(x => x.idusuario == id);
    if (!r) return;
    openModal(id);
    document.getElementById('repNombre').value   = r.nombre;
    document.getElementById('repApellido').value = r.apellido || '';
    document.getElementById('repCelular').value  = r.celular  || '';
    document.getElementById('repEmail').value    = r.email    || '';
    document.getElementById('repActivo').value   = r.activo;
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

async function guardarRepartidor() {
    const id  = document.getElementById('repId').value;
    const err = document.getElementById('repError');
    const btn = document.getElementById('btnGuardar');
    err.style.display = 'none';
    btn.disabled      = true;
    btn.textContent   = 'Guardando...';

    const body = {
        idusuario: id ? parseInt(id) : null,
        nombre:    document.getElementById('repNombre').value.trim(),
        apellido:  document.getElementById('repApellido').value.trim(),
        celular:   document.getElementById('repCelular').value.trim(),
        email:     document.getElementById('repEmail').value.trim(),
        password:  document.getElementById('repPassword').value,
        activo:    parseInt(document.getElementById('repActivo').value),
    };

    const res  = await fetch('ajax/guardar_repartidor.php', {
        method:  'POST',
        headers: {'Content-Type': 'application/json'},
        body:    JSON.stringify(body),
    });
    const data = await res.json();

    btn.disabled    = false;
    btn.textContent = 'Guardar';

    if (data.ok) {
        closeModal();
        cargarRepartidores();
    } else {
        err.textContent   = data.msg || 'Error al guardar';
        err.style.display = 'block';
    }
}

document.getElementById('modal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});

cargarRepartidores();
</script>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
