<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

  :root {
    --ink:       #0a0a0a;
    --ink-mid:   #3a3a3a;
    --ink-soft:  #7a7a7a;
    --paper:     #fafafa;
    --white:     #ffffff;
    --rule:      #e0e0e0;
    --rule-dark: #c0c0c0;
    --accent:    #0a0a0a;
    --danger:    #c0392b;
    --success:   #1a7a4a;
    --shadow-sm: 0 1px 4px rgba(0,0,0,.08);
    --shadow-md: 0 4px 20px rgba(0,0,0,.10);
    --shadow-lg: 0 12px 40px rgba(0,0,0,.14);
    --radius:    6px;
    --transition: .22s cubic-bezier(.4,0,.2,1);
  }

  .prov-module * { box-sizing: border-box; margin: 0; padding: 0; }
  .prov-module { font-family: 'DM Sans', sans-serif; color: var(--ink); background: var(--paper); min-height: 100vh; padding: 2.5rem 2rem 4rem; }

  /* Header */
  .prov-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 2px solid var(--ink); }
  .prov-header__title { font-family: 'Playfair Display', serif; font-size: 2.4rem; font-weight: 700; letter-spacing: -.5px; line-height: 1; }
  .prov-header__title span { display: block; font-family: 'DM Sans', sans-serif; font-size: .72rem; font-weight: 500; letter-spacing: .2em; text-transform: uppercase; color: var(--ink-soft); margin-bottom: .4rem; }
  .btn-primary { display: inline-flex; align-items: center; gap: .5rem; background: var(--ink); color: var(--white); border: none; padding: .7rem 1.5rem; border-radius: var(--radius); font-family: 'DM Sans', sans-serif; font-size: .85rem; font-weight: 600; letter-spacing: .03em; cursor: pointer; transition: background var(--transition), transform var(--transition), box-shadow var(--transition); box-shadow: var(--shadow-sm); }
  .btn-primary:hover { background: #333; transform: translateY(-1px); box-shadow: var(--shadow-md); }
  .btn-primary svg { width:16px; height:16px; flex-shrink:0; }

  /* Tabs */
  .prov-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--rule); margin-bottom: 2rem; }
  .prov-tab { background: none; border: none; padding: .75rem 1.6rem; font-family: 'DM Sans', sans-serif; font-size: .875rem; font-weight: 500; color: var(--ink-soft); cursor: pointer; position: relative; transition: color var(--transition); letter-spacing: .02em; }
  .prov-tab::after { content:''; position:absolute; bottom:-2px; left:0; right:0; height:2px; background: var(--ink); transform: scaleX(0); transition: transform var(--transition); }
  .prov-tab.active { color: var(--ink); font-weight: 600; }
  .prov-tab.active::after { transform: scaleX(1); }
  .prov-tab:hover { color: var(--ink); }

  /* Panels */
  .prov-panel { display: none; animation: fadeUp .3s ease both; }
  .prov-panel.active { display: block; }
  @keyframes fadeUp { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }

  /* Cards */
  .prov-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem; }
  .prov-card { background: var(--white); border: 1px solid var(--rule); border-radius: var(--radius); padding: 1.4rem 1.5rem; transition: box-shadow var(--transition), border-color var(--transition), transform var(--transition); position: relative; overflow: hidden; }
  .prov-card::before { content:''; position:absolute; top:0; left:0; width:3px; height:100%; background: var(--ink); transform: scaleY(0); transform-origin: bottom; transition: transform var(--transition); }
  .prov-card:hover { box-shadow: var(--shadow-md); border-color: var(--rule-dark); transform: translateY(-2px); }
  .prov-card:hover::before { transform: scaleY(1); }
  .prov-card__name { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 600; margin-bottom: .3rem; }
  .prov-card__meta { font-size: .78rem; color: var(--ink-soft); display: flex; flex-direction: column; gap: .2rem; margin-top: .6rem; }
  .prov-card__meta span { display: flex; align-items: center; gap: .4rem; }
  .prov-card__meta svg { width:13px; height:13px; opacity:.6; }
  .prov-card__badge { display:inline-block; padding:.2rem .6rem; border-radius:20px; font-size:.7rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; }
  .badge-active { background:#e8f5e9; color: var(--success); }
  .badge-inactive { background:#fce8e6; color: var(--danger); }
  .prov-card__actions { display: flex; gap: .5rem; margin-top: 1rem; padding-top: .8rem; border-top: 1px solid var(--rule); }
  .btn-sm { display:inline-flex; align-items:center; gap:.35rem; padding:.38rem .85rem; border-radius:var(--radius); font-size:.75rem; font-weight:600; cursor:pointer; border:1px solid var(--rule); background:var(--white); color:var(--ink); transition: all var(--transition); }
  .btn-sm:hover { background:var(--ink); color:var(--white); border-color:var(--ink); }
  .btn-sm.danger:hover { background: var(--danger); border-color: var(--danger); }
  .btn-sm svg { width:13px; height:13px; }

  /* Empty state */
  .empty-state { text-align:center; padding: 4rem 2rem; color:var(--ink-soft); }
  .empty-state svg { width:52px; height:52px; margin-bottom:1rem; opacity:.3; }
  .empty-state h3 { font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--ink); margin-bottom:.4rem; }
  .empty-state p { font-size:.85rem; }

  /* Modal */
  .modal-overlay { position:fixed; inset:0; background:rgba(10,10,10,.55); backdrop-filter:blur(3px); z-index:1000; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity var(--transition); padding:1rem; }
  .modal-overlay.open { opacity:1; pointer-events:auto; }
  .modal { background:var(--white); border-radius:10px; width:100%; max-width:620px; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-lg); transform:translateY(20px) scale(.97); transition: transform .28s cubic-bezier(.4,0,.2,1); }
  .modal-overlay.open .modal { transform:translateY(0) scale(1); }
  .modal-header { display:flex; align-items:center; justify-content:space-between; padding:1.4rem 1.8rem; border-bottom:1px solid var(--rule); position:sticky; top:0; background:var(--white); z-index:1; }
  .modal-header h2 { font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:700; }
  .modal-close { background:none; border:none; cursor:pointer; color:var(--ink-soft); padding:.3rem; border-radius:4px; display:flex; align-items:center; transition:color var(--transition), background var(--transition); }
  .modal-close:hover { color:var(--ink); background:var(--rule); }
  .modal-body { padding:1.8rem; }
  .modal-footer { padding:1rem 1.8rem 1.4rem; display:flex; justify-content:flex-end; gap:.75rem; border-top:1px solid var(--rule); }

  /* Form */
  .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.1rem; }
  .form-group { display:flex; flex-direction:column; gap:.4rem; }
  .form-group.full { grid-column:1/-1; }
  .form-group label { font-size:.775rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:var(--ink-soft); }
  .form-group input, .form-group select, .form-group textarea { padding:.65rem .9rem; border:1px solid var(--rule-dark); border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.875rem; color:var(--ink); background:var(--paper); transition: border-color var(--transition), box-shadow var(--transition); outline:none; }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--ink); box-shadow:0 0 0 3px rgba(10,10,10,.07); }
  .form-group textarea { resize:vertical; min-height:70px; }
  .form-section-title { font-family:'Playfair Display',serif; font-size:.95rem; font-weight:600; color:var(--ink); margin-bottom:.6rem; padding-bottom:.4rem; border-bottom:1px solid var(--rule); grid-column:1/-1; margin-top:.4rem; }

  /* Compras layout */
  .compra-layout { display:grid; grid-template-columns:1fr 1.2fr; gap:1.5rem; align-items:start; }
  @media(max-width:860px){ .compra-layout { grid-template-columns:1fr; } .form-grid { grid-template-columns:1fr; } }
  .compra-form-card { background:var(--white); border:1px solid var(--rule); border-radius:var(--radius); padding:1.8rem; }
  .compra-form-card h3 { font-family:'Playfair Display',serif; font-size:1.15rem; margin-bottom:1.2rem; padding-bottom:.7rem; border-bottom:1px solid var(--rule); }

  /* Table */
  .table-wrap { background:var(--white); border:1px solid var(--rule); border-radius:var(--radius); overflow:hidden; }
  .table-wrap table { width:100%; border-collapse:collapse; font-size:.82rem; }
  .table-wrap thead { background:var(--ink); color:var(--white); }
  .table-wrap thead th { padding:.7rem 1rem; text-align:left; font-weight:600; letter-spacing:.04em; text-transform:uppercase; font-size:.7rem; }
  .table-wrap tbody tr { border-bottom:1px solid var(--rule); transition:background var(--transition); }
  .table-wrap tbody tr:last-child { border-bottom:none; }
  .table-wrap tbody tr:hover { background:#f5f5f5; }
  .table-wrap td { padding:.75rem 1rem; color:var(--ink-mid); vertical-align:middle; }
  .table-wrap td strong { color:var(--ink); font-weight:600; }
  .stock-up { color:var(--success); font-weight:700; display:inline-flex; align-items:center; gap:.25rem; }
  .stock-up svg { width:14px; height:14px; }

  /* Toast */
  .toast-container { position:fixed; bottom:1.5rem; right:1.5rem; z-index:2000; display:flex; flex-direction:column; gap:.5rem; }
  .toast { background:var(--ink); color:var(--white); padding:.75rem 1.2rem; border-radius:var(--radius); font-size:.82rem; font-weight:500; display:flex; align-items:center; gap:.6rem; box-shadow:var(--shadow-lg); animation: slideIn .3s ease both; max-width:320px; }
  .toast.success { border-left:4px solid #4caf50; }
  .toast.error { border-left:4px solid var(--danger); }
  @keyframes slideIn { from{transform:translateX(30px);opacity:0} to{transform:translateX(0);opacity:1} }
  @keyframes slideOut { to{transform:translateX(30px);opacity:0} }

  /* Loader */
  .loader { display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,.3); border-top-color:var(--white); border-radius:50%; animation:spin .6s linear infinite; }
  @keyframes spin { to{transform:rotate(360deg)} }

  /* Search */
  .search-bar { display:flex; align-items:center; gap:.75rem; margin-bottom:1.5rem; }
  .search-bar input { flex:1; max-width:320px; padding:.6rem 1rem; border:1px solid var(--rule-dark); border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.875rem; outline:none; background:var(--white); transition: border-color var(--transition), box-shadow var(--transition); }
  .search-bar input:focus { border-color:var(--ink); box-shadow:0 0 0 3px rgba(10,10,10,.07); }
  .badge-count { background:var(--ink); color:var(--white); font-size:.7rem; font-weight:700; padding:.15rem .5rem; border-radius:20px; margin-left:.5rem; }
</style>

<div class="prov-module">

  <!-- Header -->
  <div class="prov-header">
    <div class="prov-header__title">
      <span>Gestión</span>
      Proveedores
    </div>
    <button class="btn-primary" onclick="openModal('modalAltaProveedor')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Nuevo proveedor
    </button>
  </div>

  <!-- Tabs -->
  <div class="prov-tabs">
    <button class="prov-tab active" onclick="switchTab('proveedores', this)">
      Proveedores <span class="badge-count" id="badgeCount">0</span>
    </button>
    <button class="prov-tab" onclick="switchTab('asignaciones', this)">
    Materias Primas por proveedor
  </button>

    <button class="prov-tab" onclick="switchTab('compras', this)">Registrar compra</button>
    <button class="prov-tab" onclick="switchTab('historial', this)">Historial de compras</button>
  </div>

  <!-- Panel: Proveedores -->
  <div class="prov-panel active" id="panel-proveedores">
    <div class="search-bar">
      <input type="text" id="searchProv" placeholder="Buscar proveedor…" oninput="filtrarProveedores()">
    </div>
    <div class="prov-grid" id="gridProveedores">
      <div class="empty-state" style="grid-column:1/-1">
        <div class="loader" style="border-color:rgba(0,0,0,.15);border-top-color:var(--ink);width:28px;height:28px;margin:0 auto 1rem;"></div>
        <p>Cargando proveedores…</p>
      </div>
    </div>
  </div>

  <!-- Panel: Registrar compra -->
  <div class="prov-panel" id="panel-compras">
    <div class="compra-layout">
      <div class="compra-form-card">
        <h3>Nueva orden de compra</h3>
        <div class="form-grid" style="grid-template-columns:1fr;">
          <div class="form-group">
            <label>Proveedor</label>
            <select id="selectProvCompra" onchange="cargarMateriasPorProveedor()">
              <option value="">— Seleccioná un proveedor —</option>
            </select>
          </div>
          <div class="form-group">
            <label>Materia prima</label>
            <select id="selectMateriaCompra">
              <option value="">— Primero seleccioná proveedor —</option>
            </select>
          </div>
          <div class="form-group">
            <label>Cantidad a comprar</label>
            <input type="number" id="inputCantidad" placeholder="0.00" min="0" step="0.01">
          </div>
          <div class="form-group">
            <label>Costo unitario (opcional)</label>
            <input type="number" id="inputCosto" placeholder="0.00000" min="0" step="0.00001">
          </div>
          <div class="form-group">
            <label>Observaciones</label>
            <textarea id="inputObsCompra" placeholder="Notas de la compra…"></textarea>
          </div>
        </div>
        <div style="margin-top:1.4rem; display:flex; gap:.75rem; justify-content:flex-end;">
          <button class="btn-primary" onclick="registrarCompra()" id="btnRegistrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Registrar y actualizar stock
          </button>
        </div>
      </div>

      <div>
        <div class="compra-form-card" id="cardStockInfo" style="border-left:3px solid var(--ink);">
          <h3>Stock actual</h3>
          <div id="stockInfoContent" style="color:var(--ink-soft);font-size:.85rem;">
            Seleccioná una materia prima para ver el stock actual.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Panel: Historial -->
  <div class="prov-panel" id="panel-historial">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Proveedor</th>
            <th>Materia prima</th>
            <th>Cantidad</th>
            <th>Costo unit.</th>
            <th>Fecha</th>
            <th>Stock resultante</th>
          </tr>
        </thead>
        <tbody id="tablaHistorial">
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--ink-soft);">Cargando historial…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Modal: Alta / Editar proveedor -->
<div class="modal-overlay" id="modalAltaProveedor">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <h2 id="modalTitle">Nuevo proveedor</h2>
      <button class="modal-close" onclick="closeModal('modalAltaProveedor')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <form id="formProveedor" onsubmit="return false;">
        <div class="form-grid">
          <span class="form-section-title">Datos del proveedor</span>
          <div class="form-group full">
            <label>Nombre / Razón social *</label>
            <input type="text" id="pNombre" placeholder="Empresa S.R.L." required>
          </div>
          <div class="form-group">
            <label>Teléfono</label>
            <input type="text" id="pTelefono" placeholder="+54 376 000-0000">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" id="pEmail" placeholder="contacto@empresa.com">
          </div>
          <div class="form-group full">
            <label>Dirección</label>
            <input type="text" id="pDireccion" placeholder="Av. Ejemplo 1234, Posadas">
          </div>
          <span class="form-section-title">Contacto comercial</span>
          <div class="form-group">
            <label>Nombre del contacto</label>
            <input type="text" id="pContactoNombre" placeholder="Juan Pérez">
          </div>
          <div class="form-group">
            <label>Teléfono del contacto</label>
            <input type="text" id="pContactoTel" placeholder="+54 376 000-0000">
          </div>
          <div class="form-group full">
            <label>Observaciones</label>
            <textarea id="pObservaciones" placeholder="Notas internas sobre el proveedor…"></textarea>
          </div>
          <div class="form-group">
            <label>Estado</label>
            <select id="pActivo">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn-sm" onclick="closeModal('modalAltaProveedor')">Cancelar</button>
      <button class="btn-primary" onclick="guardarProveedor()" id="btnGuardar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Guardar proveedor
      </button>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
let proveedores = [], materias = [], editId = null;

document.addEventListener('DOMContentLoaded', () => {
  cargarProveedores();
  cargarTodasMaterias();
  cargarHistorial();
});

/* ── Tabs ── */
function switchTab(tab, btn) {
  document.querySelectorAll('.prov-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.prov-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel-' + tab).classList.add('active');
}

/* ── Modal ── */
function openModal(id) { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; if(id==='modalAltaProveedor') resetForm(); }
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if(e.target===o) closeModal(o.id); }));
function resetForm() { document.getElementById('formProveedor').reset(); editId=null; document.getElementById('modalTitle').textContent='Nuevo proveedor'; }

/* ── Toast ── */
function toast(msg, type='success') {
  const c=document.getElementById('toastContainer'), t=document.createElement('div');
  t.className=`toast ${type}`;
  const icon = type==='success' ? '<polyline points="20 6 9 17 4 12"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
  t.innerHTML=`<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">${icon}</svg>${msg}`;
  c.appendChild(t);
  setTimeout(()=>{ t.style.animation='slideOut .3s ease forwards'; setTimeout(()=>t.remove(),300); },3000);
}

/* ── AJAX ── */
async function ajax(url, data=null) {
  const opts = { method: data?'POST':'GET', headers:{'Content-Type':'application/json'} };
  if(data) opts.body=JSON.stringify(data);
  const r=await fetch(url,opts); return r.json();
}

/* ── Proveedores ── */
async function cargarProveedores() {
  try {
    const res=await ajax('ajax/get_proveedores.php');
    proveedores=res.data||[];
    renderGrid(proveedores);
    actualizarSelectProvCompra();
    document.getElementById('badgeCount').textContent=proveedores.filter(p=>p.activo==1).length;
  } catch(e){ toast('Error al cargar proveedores','error'); }
}

function renderGrid(lista) {
  const g=document.getElementById('gridProveedores');
  if(!lista.length){ g.innerHTML=`<div class="empty-state" style="grid-column:1/-1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><h3>Sin proveedores aún</h3><p>Creá tu primer proveedor con el botón de arriba.</p></div>`; return; }
  g.innerHTML=lista.map((p,i)=>`
    <div class="prov-card" style="animation:fadeUp .3s ease ${i*0.05}s both">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <div class="prov-card__name">${esc(p.nombre)}</div>
        <span class="prov-card__badge ${p.activo==1?'badge-active':'badge-inactive'}">${p.activo==1?'Activo':'Inactivo'}</span>
      </div>
      <div class="prov-card__meta">
        ${p.telefono?`<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 2.08 5.18 2 2 0 0 1 4.11 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 10.91a16 16 0 0 0 5 5l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 17z"/></svg>${esc(p.telefono)}</span>`:''}
        ${p.email?`<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>${esc(p.email)}</span>`:''}
        ${p.contacto_nombre?`<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Contacto: ${esc(p.contacto_nombre)}</span>`:''}
      </div>
      <div class="prov-card__actions">
        <button class="btn-sm" onclick="editarProveedor(${p.idproveedor})">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Editar
        </button>
        <button class="btn-sm danger" onclick="eliminarProveedor(${p.idproveedor},'${esc(p.nombre)}')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
          Eliminar
        </button>
      </div>
    </div>`).join('');
}

function filtrarProveedores() {
  const q=document.getElementById('searchProv').value.toLowerCase();
  renderGrid(proveedores.filter(p=>p.nombre.toLowerCase().includes(q)||(p.email||'').toLowerCase().includes(q)));
}

async function guardarProveedor() {
  const btn=document.getElementById('btnGuardar');
  const data={ idproveedor:editId, nombre:document.getElementById('pNombre').value.trim(), telefono:document.getElementById('pTelefono').value.trim(), email:document.getElementById('pEmail').value.trim(), direccion:document.getElementById('pDireccion').value.trim(), contacto_nombre:document.getElementById('pContactoNombre').value.trim(), contacto_telefono:document.getElementById('pContactoTel').value.trim(), observaciones:document.getElementById('pObservaciones').value.trim(), activo:document.getElementById('pActivo').value };
  if(!data.nombre){ toast('El nombre es requerido','error'); return; }
  btn.innerHTML='<span class="loader"></span>'; btn.disabled=true;
  try {
    const res=await ajax('ajax/guardar_proveedor.php',data);
    if(res.ok){ toast(editId?'Proveedor actualizado':'Proveedor creado ✓'); closeModal('modalAltaProveedor'); cargarProveedores(); }
    else toast(res.msg||'Error al guardar','error');
  } catch(e){ toast('Error de conexión','error'); }
  btn.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar proveedor';
  btn.disabled=false;
}

function editarProveedor(id) {
  const p=proveedores.find(x=>x.idproveedor==id); if(!p) return;
  editId=id;
  document.getElementById('pNombre').value=p.nombre||''; document.getElementById('pTelefono').value=p.telefono||''; document.getElementById('pEmail').value=p.email||''; document.getElementById('pDireccion').value=p.direccion||''; document.getElementById('pContactoNombre').value=p.contacto_nombre||''; document.getElementById('pContactoTel').value=p.contacto_telefono||''; document.getElementById('pObservaciones').value=p.observaciones||''; document.getElementById('pActivo').value=p.activo;
  document.getElementById('modalTitle').textContent='Editar proveedor';
  openModal('modalAltaProveedor');
}

async function eliminarProveedor(id,nombre) {
  if(!confirm(`¿Eliminar el proveedor "${nombre}"?`)) return;
  try{ const res=await ajax('ajax/eliminar_proveedor.php',{idproveedor:id}); if(res.ok){ toast('Proveedor eliminado'); cargarProveedores(); } else toast(res.msg||'Error','error'); } catch(e){ toast('Error de conexión','error'); }
}

/* ── Compras ── */
async function cargarTodasMaterias() {
  try { const res=await ajax('ajax/get_materias.php'); materias=res.data||[]; } catch(e){}
}

function actualizarSelectProvCompra() {
  const s=document.getElementById('selectProvCompra');
  s.innerHTML='<option value="">— Seleccioná un proveedor —</option>'+proveedores.filter(p=>p.activo==1).map(p=>`<option value="${p.idproveedor}">${esc(p.nombre)}</option>`).join('');
}

async function cargarMateriasPorProveedor() {
  const idProv=document.getElementById('selectProvCompra').value;
  const s=document.getElementById('selectMateriaCompra');
  s.innerHTML='<option value="">Cargando…</option>';
  document.getElementById('stockInfoContent').innerHTML='Seleccioná una materia prima para ver el stock actual.';
  if(!idProv){ s.innerHTML='<option value="">— Primero seleccioná proveedor —</option>'; return; }
  try {
    const res=await ajax(`ajax/get_materias_proveedor.php?idproveedor=${idProv}`);
    const lista=res.data||[];
    s.innerHTML=lista.length ? '<option value="">— Seleccioná materia prima —</option>'+lista.map(m=>`<option value="${m.idmateria_prima}" data-stock="${m.stock_actual}" data-min="${m.stock_minimo}" data-costo="${m.costo||''}">${esc(m.nombre)}</option>`).join('') : '<option value="">Sin materias primas asignadas</option>';
    s.onchange=mostrarStockInfo;
  } catch(e){ s.innerHTML='<option value="">Error al cargar</option>'; }
}

function mostrarStockInfo() {
  const opt=document.getElementById('selectMateriaCompra').selectedOptions[0];
  const c=document.getElementById('stockInfoContent');
  if(!opt||!opt.value){ c.innerHTML='Seleccioná una materia prima para ver el stock actual.'; return; }
  const stock=parseFloat(opt.dataset.stock)||0, min=parseFloat(opt.dataset.min)||0;
  const costo=opt.dataset.costo?`$${parseFloat(opt.dataset.costo).toFixed(5)}`:'—';
  const pct=min>0?Math.min(100,(stock/min)*100):100;
  const color=stock>=min?'#1a7a4a':'#c0392b';
  c.innerHTML=`<div style="display:grid;gap:.9rem;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;font-size:.82rem;">
      <div style="background:#f5f5f5;border-radius:4px;padding:.7rem;text-align:center;"><div style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:${color}">${stock}</div><div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-soft);margin-top:.2rem;">Stock actual</div></div>
      <div style="background:#f5f5f5;border-radius:4px;padding:.7rem;text-align:center;"><div style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;">${min}</div><div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-soft);margin-top:.2rem;">Stock mínimo</div></div>
    </div>
    <div><div style="font-size:.72rem;color:var(--ink-soft);margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.05em;">Nivel de stock</div><div style="background:var(--rule);border-radius:20px;height:6px;overflow:hidden;"><div style="height:100%;width:${pct}%;background:${color};border-radius:20px;transition:width .6s ease;"></div></div></div>
    <div style="font-size:.8rem;color:var(--ink-soft);">Costo registrado: <strong style="color:var(--ink)">${costo}</strong></div>
  </div>`;
  if(opt.dataset.costo) document.getElementById('inputCosto').value=parseFloat(opt.dataset.costo).toFixed(5);
}

async function registrarCompra() {
  const idProv=document.getElementById('selectProvCompra').value;
  const idMateria=document.getElementById('selectMateriaCompra').value;
  const cantidad=parseFloat(document.getElementById('inputCantidad').value);
  const costo=document.getElementById('inputCosto').value;
  const obs=document.getElementById('inputObsCompra').value;
  if(!idProv){ toast('Seleccioná un proveedor','error'); return; }
  if(!idMateria){ toast('Seleccioná una materia prima','error'); return; }
  if(!cantidad||cantidad<=0){ toast('Ingresá una cantidad válida','error'); return; }
  const btn=document.getElementById('btnRegistrar');
  btn.innerHTML='<span class="loader"></span> Procesando…'; btn.disabled=true;
  try {
    const res=await ajax('ajax/registrar_compra.php',{idproveedor:idProv,idmateria_prima:idMateria,cantidad,costo:costo||null,observaciones:obs});
    if(res.ok){ toast(`Stock actualizado +${cantidad} unidades ✓`); document.getElementById('inputCantidad').value=''; document.getElementById('inputObsCompra').value=''; mostrarStockInfo(); cargarHistorial(); }
    else toast(res.msg||'Error al registrar','error');
  } catch(e){ toast('Error de conexión','error'); }
  btn.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Registrar y actualizar stock';
  btn.disabled=false;
}

/* ── Historial ── */
async function cargarHistorial() {
  try {
    const res=await ajax('ajax/get_historial_compras.php');
    const lista=res.data||[];
    const tb=document.getElementById('tablaHistorial');
    if(!lista.length){ tb.innerHTML='<tr><td colspan="7" style="text-align:center;padding:2.5rem;color:var(--ink-soft);">Sin registros de compras aún.</td></tr>'; return; }
    tb.innerHTML=lista.map((c,i)=>`<tr style="animation:fadeUp .25s ease ${i*0.04}s both"><td><strong>${c.id}</strong></td><td>${esc(c.proveedor_nombre)}</td><td>${esc(c.materia_nombre)}</td><td><span class="stock-up"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>+${parseFloat(c.cantidad).toFixed(2)}</span></td><td>${c.costo?'$'+parseFloat(c.costo).toFixed(5):'—'}</td><td style="color:var(--ink-soft)">${c.created_at}</td><td><strong>${parseFloat(c.stock_nuevo||0).toFixed(2)}</strong></td></tr>`).join('');
  } catch(e){}
}

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>
