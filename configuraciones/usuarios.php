<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
include '../panel/dashboard/layaut/nav.php';
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

  :root {
    --ink:        #0a0a0a;
    --ink-mid:    #3a3a3a;
    --ink-soft:   #7a7a7a;
    --paper:      #fafafa;
    --white:      #ffffff;
    --rule:       #e0e0e0;
    --rule-dark:  #c0c0c0;
    --danger:     #c88e99;
    --success:    #1a7a4a;
    --shadow-sm:  0 1px 4px rgba(0,0,0,.08);
    --shadow-md:  0 4px 20px rgba(0,0,0,.10);
    --shadow-lg:  0 12px 40px rgba(0,0,0,.14);
    --radius:     6px;
    --transition: .22s cubic-bezier(.4,0,.2,1);
  }

  .usr-module * { box-sizing: border-box; margin: 0; padding: 0; }
  .usr-module { font-family: 'DM Sans', sans-serif; color: var(--ink); background: var(--paper); min-height: 100vh; padding: 2.5rem 2rem 4rem; }

  /* ── Header ── */
  .usr-header { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:2.5rem; padding-bottom:1.5rem; border-bottom:2px solid var(--ink); }
  .usr-header__title { font-family:'Playfair Display',serif; font-size:2.4rem; font-weight:700; letter-spacing:-.5px; line-height:1; }
  .usr-header__title span { display:block; font-family:'DM Sans',sans-serif; font-size:.72rem; font-weight:500; letter-spacing:.2em; text-transform:uppercase; color:var(--ink-soft); margin-bottom:.4rem; }

  /* ── Buttons ── */
  .btn-primary { display:inline-flex; align-items:center; gap:.5rem; background:var(--ink); color:var(--white); border:none; padding:.7rem 1.5rem; border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.85rem; font-weight:600; letter-spacing:.03em; cursor:pointer; transition:background var(--transition), transform var(--transition), box-shadow var(--transition); box-shadow:var(--shadow-sm); }
  .btn-primary:hover { background:#333; transform:translateY(-1px); box-shadow:var(--shadow-md); }
  .btn-primary svg { width:16px; height:16px; flex-shrink:0; }
  .btn-sm { display:inline-flex; align-items:center; gap:.35rem; padding:.38rem .85rem; border-radius:var(--radius); font-size:.75rem; font-weight:600; cursor:pointer; border:1px solid var(--rule); background:var(--white); color:var(--ink); transition:all var(--transition); }
  .btn-sm:hover { background:var(--ink); color:var(--white); border-color:var(--ink); }
  .btn-sm.danger:hover { background:var(--danger); border-color:var(--danger); color:var(--white); }
  .btn-pw { color:#1d4ed8; border-color:#bfdbfe; }
  .btn-pw:hover { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
  .btn-sm svg { width:13px; height:13px; }

  /* ── Stats bar ── */
  .stats-bar { display:grid; grid-template-columns:repeat(3, 1fr); gap:1.2rem; margin-bottom:2rem; }
  .stat-card { background:var(--white); border:1px solid var(--rule); border-radius:var(--radius); padding:1.2rem 1.5rem; display:flex; flex-direction:column; gap:.3rem; transition:box-shadow var(--transition); }
  .stat-card:hover { box-shadow:var(--shadow-md); }
  .stat-card__num { font-family:'Playfair Display',serif; font-size:2rem; font-weight:700; line-height:1; }
  .stat-card__label { font-size:.75rem; font-weight:500; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-soft); }
  .stat-card__num.activos { color:var(--success); }
  .stat-card__num.inactivos { color:var(--danger); }

  /* ── Table wrapper ── */
  .table-wrap { background:var(--white); border:1px solid var(--rule); border-radius:var(--radius); overflow:hidden; }

  /* ── DataTables override ── */
  .dt-wrapper { padding:1rem 1rem .5rem; }
  div.dataTables_wrapper div.dataTables_filter input { border:1px solid var(--rule-dark); border-radius:var(--radius); padding:.4rem .7rem; font-family:'DM Sans',sans-serif; font-size:.82rem; outline:none; }
  div.dataTables_wrapper div.dataTables_filter input:focus { border-color:var(--ink); }
  div.dataTables_wrapper div.dataTables_length select { border:1px solid var(--rule-dark); border-radius:var(--radius); padding:.3rem .5rem; font-family:'DM Sans',sans-serif; font-size:.82rem; }
  div.dataTables_wrapper div.dataTables_info { font-size:.78rem; color:var(--ink-soft); padding:0 1rem .8rem; }
  div.dataTables_wrapper div.dataTables_paginate { padding:.5rem 1rem 1rem; }
  div.dataTables_wrapper div.dataTables_paginate .paginate_button { border-radius:var(--radius) !important; font-family:'DM Sans',sans-serif; font-size:.78rem !important; }
  div.dataTables_wrapper div.dataTables_paginate .paginate_button.current { background:var(--ink) !important; color:var(--white) !important; border-color:var(--ink) !important; }
  table.dataTable thead th { background:var(--ink); color:var(--white); border-bottom:none !important; }
  table.dataTable.no-footer { border-bottom:none; }
  table.dataTable tbody td { padding:.72rem 1rem; color:var(--ink-mid); font-size:.83rem; vertical-align:middle; }
  table.dataTable tbody tr { border-bottom:1px solid var(--rule); transition:background var(--transition); }
  table.dataTable tbody tr:hover { background:#f5f5f5; }

  /* ── Badges ── */
  .badge-activo   { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem; border-radius:20px; font-size:.7rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; background:#e8f5e9; color:var(--success); }
  .badge-inactivo { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem; border-radius:20px; font-size:.7rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; background:#f0f0f0; color:var(--ink-soft); }

  /* ── Actions column ── */
  .actions-cell { display:flex; gap:.4rem; }

  /* ── Modal ── */
  .modal-overlay { position:fixed; inset:0; background:rgba(10,10,10,.55); backdrop-filter:blur(3px); z-index:1000; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity var(--transition); padding:1rem; }
  .modal-overlay.open { opacity:1; pointer-events:auto; }
  .modal { background:var(--white); border-radius:10px; width:100%; max-width:640px; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-lg); transform:translateY(20px) scale(.97); transition:transform .28s cubic-bezier(.4,0,.2,1); }
  .modal-overlay.open .modal { transform:translateY(0) scale(1); }
  .modal-header { display:flex; align-items:center; justify-content:space-between; padding:1.4rem 1.8rem; border-bottom:1px solid var(--rule); position:sticky; top:0; background:var(--white); z-index:1; }
  .modal-header h2 { font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:700; }
  .modal-close { background:none; border:none; cursor:pointer; color:var(--ink-soft); padding:.3rem; border-radius:4px; display:flex; transition:color var(--transition),background var(--transition); }
  .modal-close:hover { color:var(--ink); background:var(--rule); }
  .modal-body   { padding:1.8rem; }
  .modal-footer { padding:1rem 1.8rem 1.4rem; display:flex; justify-content:flex-end; gap:.75rem; border-top:1px solid var(--rule); }

  /* ── Forms ── */
  .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.1rem; }
  .form-group { display:flex; flex-direction:column; gap:.4rem; }
  .form-group.full { grid-column:1/-1; }
  .form-group label { font-size:.775rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-soft); }
  .form-group input,
  .form-group select { padding:.65rem .9rem; border:1px solid var(--rule-dark); border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.875rem; color:var(--ink); background:var(--paper); transition:border-color var(--transition),box-shadow var(--transition); outline:none; }
  .form-group input:focus,
  .form-group select:focus { border-color:var(--ink); box-shadow:0 0 0 3px rgba(10,10,10,.07); }
  .form-section-title { font-family:'Playfair Display',serif; font-size:.95rem; font-weight:600; color:var(--ink); padding-bottom:.4rem; border-bottom:1px solid var(--rule); grid-column:1/-1; margin-top:.4rem; }

  /* ── Toggle switch ── */
  .toggle-wrap { display:flex; align-items:center; gap:.75rem; padding-top:.6rem; }
  .toggle { position:relative; width:44px; height:24px; flex-shrink:0; }
  .toggle input { opacity:0; width:0; height:0; }
  .toggle-slider { position:absolute; inset:0; background:var(--rule-dark); border-radius:24px; cursor:pointer; transition:background var(--transition); }
  .toggle-slider::before { content:''; position:absolute; left:3px; top:3px; width:18px; height:18px; background:var(--white); border-radius:50%; transition:transform var(--transition); box-shadow:var(--shadow-sm); }
  .toggle input:checked + .toggle-slider { background:var(--ink); }
  .toggle input:checked + .toggle-slider::before { transform:translateX(20px); }
  .toggle-label { font-size:.875rem; font-weight:500; color:var(--ink); }

  /* ── Loader ── */
  .loader { display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:var(--white); border-radius:50%; animation:spin .6s linear infinite; }
  @keyframes spin { to{transform:rotate(360deg)} }
  @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

  @media(max-width:640px) {
    .stats-bar { grid-template-columns:1fr; }
    .form-grid { grid-template-columns:1fr; }
    .usr-header { flex-direction:column; align-items:flex-start; gap:1rem; }
  }
</style>

<div class="usr-module">

  <!-- Header -->
  <div class="usr-header">
    <div class="usr-header__title">
      <span>Configuración</span>
      Usuarios
    </div>
    <button class="btn-primary" onclick="openModal()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nuevo usuario
    </button>
  </div>

  <!-- Stats bar -->
  <div class="stats-bar">
    <div class="stat-card">
      <div class="stat-card__num" id="statTotal">—</div>
      <div class="stat-card__label">Total usuarios</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__num activos" id="statActivos">—</div>
      <div class="stat-card__label">Activos</div>
    </div>
    <div class="stat-card">
      <div class="stat-card__num inactivos" id="statInactivos">—</div>
      <div class="stat-card__label">Inactivos</div>
    </div>
  </div>

  <!-- DataTable -->
  <div class="table-wrap">
    <table id="tablaUsuarios" style="width:100%">
      <thead>
        <tr>
          <th>Nombre completo</th>
          <th>Usuario</th>
          <th>Email</th>
          <th>Celular</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

</div><!-- /usr-module -->

<!-- Modal: Nuevo / Editar usuario -->
<div class="modal-overlay" id="modalUsuario">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <h2 id="modalTitle">Nuevo usuario</h2>
      <button class="modal-close" onclick="closeModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="formUsuario" onsubmit="return false;">
        <div class="form-grid">

          <span class="form-section-title">Datos personales</span>

          <div class="form-group">
            <label>Nombre *</label>
            <input type="text" id="uNombre" placeholder="Luciano" required>
          </div>
          <div class="form-group">
            <label>Apellido</label>
            <input type="text" id="uApellido" placeholder="García">
          </div>
          <div class="form-group">
            <label>DNI</label>
            <input type="text" id="uDni" placeholder="12345678">
          </div>
          <div class="form-group">
            <label>Celular</label>
            <input type="text" id="uCelular" placeholder="+54 376 000-0000">
          </div>

          <span class="form-section-title">Cuenta</span>

          <div class="form-group">
            <label>Usuario (login) *</label>
            <input type="text" id="uUsuario" placeholder="lgarcia" required autocomplete="off">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" id="uEmail" placeholder="correo@ejemplo.com" autocomplete="off">
          </div>
          <div class="form-group full">
            <label>Contraseña</label>
            <input type="password" id="uPassword" placeholder="Dejar vacío para no cambiar" autocomplete="new-password">
          </div>

          <div class="form-group full">
            <label>Estado</label>
            <div class="toggle-wrap">
              <label class="toggle">
                <input type="checkbox" id="uActivo" checked>
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
      <button class="btn-primary" onclick="guardarUsuario()" id="btnGuardar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Guardar usuario
      </button>
    </div>
  </div>
</div>

<!-- Modal: Cambiar contraseña -->
<div class="modal-overlay" id="modalPw">
  <div class="modal" role="dialog" style="max-width:420px">
    <div class="modal-header">
      <h2>🔒 Cambiar contraseña</h2>
      <button class="modal-close" onclick="cerrarPw()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="font-size:.87rem;color:var(--ink-mid);margin-bottom:1.2rem">
        Cambiando contraseña de: <strong id="pwNombreUsuario"></strong>
      </p>
      <div class="form-grid">
        <div class="form-group full">
          <label>Nueva contraseña *</label>
          <input type="password" id="pwNueva" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
        </div>
        <div class="form-group full">
          <label>Confirmar contraseña *</label>
          <input type="password" id="pwConfirmar" placeholder="Repetir contraseña" autocomplete="new-password">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-sm" onclick="cerrarPw()">Cancelar</button>
      <button class="btn-primary" onclick="guardarPw()" id="btnGuardarPw">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Guardar contraseña
      </button>
    </div>
  </div>
</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
<script>
/* ══ STATE ══ */
let editId = null;
let dtUsuarios = null;

/* ══ INIT ══ */
$(document).ready(function () {
  initDataTable();

  // Toggle label update
  document.getElementById('uActivo').addEventListener('change', function () {
    document.getElementById('toggleLabel').textContent = this.checked ? 'Activo' : 'Inactivo';
  });

  // Close modal on backdrop click
  document.getElementById('modalUsuario').addEventListener('click', function (e) {
    if (e.target === this) closeModal();
  });
});

/* ══ DataTable ══ */
function initDataTable() {
  dtUsuarios = $('#tablaUsuarios').DataTable({
    ajax: {
      url: 'ajax/listar_usuarios.php',
      dataSrc: function (json) {
        actualizarStats(json);
        return json;
      }
    },
    columns: [
      {
        data: null,
        render: function (row) {
          const apellido = row.apellido ? ' ' + esc(row.apellido) : '';
          return '<strong>' + esc(row.nombre) + apellido + '</strong>';
        }
      },
      { data: 'usuario', render: (v) => esc(v) },
      { data: 'email',   render: (v) => v ? esc(v) : '<span style="color:var(--ink-soft)">—</span>' },
      { data: 'celular', render: (v) => v ? esc(v) : '<span style="color:var(--ink-soft)">—</span>' },
      { data: 'estado_html' },
      {
        data: null,
        orderable: false,
        render: function (row) {
          return '<div class="actions-cell">' +
            '<button class="btn-sm" onclick=\'editarUsuario(' + JSON.stringify(row) + ')\'>' +
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' +
              'Editar' +
            '</button>' +
            '<button class="btn-sm btn-pw" onclick="abrirCambiarPw(' + row.idusuario + ',\'' + esc(row.nombre) + '\')">' +
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' +
              'Contraseña' +
            '</button>' +
            '<button class="btn-sm danger" onclick="confirmarEliminar(' + row.idusuario + ',\'' + esc(row.nombre) + '\')">' +
              '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>' +
              'Eliminar' +
            '</button>' +
          '</div>';
        }
      }
    ],
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
    pageLength: 10,
    order: [[0, 'asc']],
    columnDefs: [{ orderable: false, targets: 5 }]
  });
}

/* ══ Stats ══ */
function actualizarStats(data) {
  const total    = data.length;
  const activos  = data.filter(u => u.activo == 1).length;
  const inactivos = total - activos;
  document.getElementById('statTotal').textContent    = total;
  document.getElementById('statActivos').textContent  = activos;
  document.getElementById('statInactivos').textContent = inactivos;
}

/* ══ MODAL ══ */
function openModal() {
  editId = null;
  document.getElementById('modalTitle').textContent = 'Nuevo usuario';
  document.getElementById('formUsuario').reset();
  document.getElementById('uActivo').checked = true;
  document.getElementById('toggleLabel').textContent = 'Activo';
  document.getElementById('uPassword').placeholder = 'Ingresá una contraseña';
  document.getElementById('modalUsuario').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('modalUsuario').classList.remove('open');
  document.body.style.overflow = '';
  editId = null;
}

function editarUsuario(row) {
  editId = row.idusuario;
  document.getElementById('modalTitle').textContent = 'Editar usuario';
  document.getElementById('uNombre').value   = row.nombre   || '';
  document.getElementById('uApellido').value = row.apellido || '';
  document.getElementById('uDni').value      = row.dni      || '';
  document.getElementById('uCelular').value  = row.celular  || '';
  document.getElementById('uUsuario').value  = row.usuario  || '';
  document.getElementById('uEmail').value    = row.email    || '';
  document.getElementById('uPassword').value = '';
  document.getElementById('uPassword').placeholder = 'Dejar vacío para no cambiar';
  const activo = row.activo == 1;
  document.getElementById('uActivo').checked = activo;
  document.getElementById('toggleLabel').textContent = activo ? 'Activo' : 'Inactivo';
  document.getElementById('modalUsuario').classList.add('open');
  document.body.style.overflow = 'hidden';
}

/* ══ AJAX helper ══ */
async function ajax(url, data) {
  const opts = { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) };
  const r = await fetch(url, opts);
  return r.json();
}

/* ══ GUARDAR ══ */
async function guardarUsuario() {
  const nombre  = document.getElementById('uNombre').value.trim();
  const usuario = document.getElementById('uUsuario').value.trim();

  if (!nombre) {
    Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El nombre es obligatorio.', confirmButtonColor: '#0a0a0a' });
    return;
  }
  if (!usuario) {
    Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El usuario (login) es obligatorio.', confirmButtonColor: '#0a0a0a' });
    return;
  }
  const password = document.getElementById('uPassword').value;
  if (!editId && !password) {
    Swal.fire({ icon: 'warning', title: 'Contraseña requerida', text: 'Debés ingresar una contraseña para el nuevo usuario.', confirmButtonColor: '#0a0a0a' });
    return;
  }

  const btn = document.getElementById('btnGuardar');
  const originalHTML = btn.innerHTML;
  btn.innerHTML = '<span class="loader"></span>';
  btn.disabled = true;

  const data = {
    idusuario: editId,
    nombre:    nombre,
    apellido:  document.getElementById('uApellido').value.trim(),
    dni:       document.getElementById('uDni').value.trim(),
    celular:   document.getElementById('uCelular').value.trim(),
    usuario:   usuario,
    email:     document.getElementById('uEmail').value.trim(),
    password:  password,
    activo:    document.getElementById('uActivo').checked ? 1 : 0
  };

  try {
    const res = await ajax('ajax/guardar_usuario.php', data);
    if (res.ok) {
      closeModal();
      dtUsuarios.ajax.reload(null, false);
      Swal.fire({
        icon: 'success',
        title: editId ? '¡Usuario actualizado!' : '¡Usuario creado!',
        text: editId
          ? 'Los datos de "' + nombre + '" fueron guardados.'
          : '"' + nombre + '" fue agregado al sistema.',
        confirmButtonColor: '#0a0a0a',
        confirmButtonText: 'Continuar',
        timer: 3000,
        timerProgressBar: true
      });
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.msg || 'No se pudo guardar.', confirmButtonColor: '#0a0a0a' });
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.', confirmButtonColor: '#0a0a0a' });
  } finally {
    btn.innerHTML = originalHTML;
    btn.disabled = false;
  }
}

/* ══ ELIMINAR ══ */
function confirmarEliminar(id, nombre) {
  Swal.fire({
    title: '¿Eliminar usuario?',
    html: 'Se eliminará permanentemente a <strong>' + esc(nombre) + '</strong>.<br>Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#0a0a0a',
    cancelButtonColor: '#e0e0e0',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(async (result) => {
    if (!result.isConfirmed) return;
    try {
      const res = await ajax('ajax/eliminar_usuario.php', { idusuario: id });
      if (res.ok) {
        dtUsuarios.ajax.reload(null, false);
        Swal.fire({
          icon: 'success',
          title: 'Eliminado',
          text: '"' + esc(nombre) + '" fue eliminado del sistema.',
          confirmButtonColor: '#0a0a0a',
          timer: 2500,
          timerProgressBar: true
        });
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: res.msg || 'No se pudo eliminar.', confirmButtonColor: '#0a0a0a' });
      }
    } catch (e) {
      Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.', confirmButtonColor: '#0a0a0a' });
    }
  });
}

/* ══ UTILS ══ */
function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* ══ CAMBIAR CONTRASEÑA ══ */
let _pwUserId = null;

function abrirCambiarPw(id, nombre) {
  _pwUserId = id;
  document.getElementById('pwNombreUsuario').textContent = nombre;
  document.getElementById('pwNueva').value    = '';
  document.getElementById('pwConfirmar').value = '';
  document.getElementById('modalPw').classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('pwNueva').focus();
}

function cerrarPw() {
  document.getElementById('modalPw').classList.remove('open');
  document.body.style.overflow = '';
  _pwUserId = null;
}

async function guardarPw() {
  const pw1 = document.getElementById('pwNueva').value.trim();
  const pw2 = document.getElementById('pwConfirmar').value.trim();

  if (!pw1) {
    Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'Ingresá la nueva contraseña.', confirmButtonColor: '#0a0a0a' }); return;
  }
  if (pw1.length < 6) {
    Swal.fire({ icon: 'warning', title: 'Muy corta', text: 'La contraseña debe tener al menos 6 caracteres.', confirmButtonColor: '#0a0a0a' }); return;
  }
  if (pw1 !== pw2) {
    Swal.fire({ icon: 'warning', title: 'No coinciden', text: 'Las contraseñas no son iguales.', confirmButtonColor: '#0a0a0a' }); return;
  }

  const btn = document.getElementById('btnGuardarPw');
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="loader"></span>';
  btn.disabled  = true;

  try {
    const res = await ajax('ajax/cambiar_password.php', { idusuario: _pwUserId, password: pw1 });
    if (res.ok) {
      cerrarPw();
      Swal.fire({ icon: 'success', title: '¡Contraseña actualizada!', text: 'La contraseña fue cambiada correctamente.', confirmButtonColor: '#0a0a0a', timer: 3000, timerProgressBar: true });
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.msg || 'No se pudo cambiar la contraseña.', confirmButtonColor: '#0a0a0a' });
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.', confirmButtonColor: '#0a0a0a' });
  } finally {
    btn.innerHTML = orig;
    btn.disabled  = false;
  }
}

// Cerrar modal contraseña con backdrop
document.getElementById('modalPw')?.addEventListener('click', function(e) {
  if (e.target === this) cerrarPw();
});
</script>
