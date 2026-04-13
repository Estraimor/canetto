<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
include '../../panel/dashboard/layaut/nav.php';
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap');

  :root {
    --ink: #0a0a0a;
    --ink-mid: #3a3a3a;
    --ink-soft: #7a7a7a;
    --paper: #fafafa;
    --white: #ffffff;
    --rule: #e0e0e0;
    --rule-dark: #c0c0c0;
    --danger: #c88e99;
    --success: #1a7a4a;
    --warning: #b7791f;
    --shadow-sm: 0 1px 4px rgba(0, 0, 0, .08);
    --shadow-md: 0 4px 20px rgba(0, 0, 0, .10);
    --shadow-lg: 0 12px 40px rgba(0, 0, 0, .14);
    --radius: 6px;
    --transition: .22s cubic-bezier(.4, 0, .2, 1);
  }

  .prov-module * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }

  .prov-module {
    font-family: 'DM Sans', sans-serif;
    color: var(--ink);
    background: var(--paper);
    min-height: 100vh;
    padding: 2.5rem 2rem 4rem;
  }

  /* ── Header ── */
  .prov-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--ink);
  }

  .prov-header__title {
    font-family: 'Playfair Display', serif;
    font-size: 2.4rem;
    font-weight: 700;
    letter-spacing: -.5px;
    line-height: 1;
  }

  .prov-header__title span {
    display: block;
    font-family: 'DM Sans', sans-serif;
    font-size: .72rem;
    font-weight: 500;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--ink-soft);
    margin-bottom: .4rem;
  }

  /* ── Buttons ── */
  .btn-primary {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    background: var(--ink);
    color: var(--white);
    border: none;
    padding: .7rem 1.5rem;
    border-radius: var(--radius);
    font-family: 'DM Sans', sans-serif;
    font-size: .85rem;
    font-weight: 600;
    letter-spacing: .03em;
    cursor: pointer;
    transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
    box-shadow: var(--shadow-sm);
  }

  .btn-primary:hover {
    background: #333;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
  }

  .btn-primary svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
  }

  .btn-sm {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .38rem .85rem;
    border-radius: var(--radius);
    font-size: .75rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid var(--rule);
    background: var(--white);
    color: var(--ink);
    transition: all var(--transition);
  }

  .btn-sm:hover {
    background: var(--ink);
    color: var(--white);
    border-color: var(--ink);
  }

  .btn-sm.danger:hover {
    background: var(--danger);
    border-color: var(--danger);
  }

  .btn-sm.warning:hover {
    background: #92400e;
    border-color: #92400e;
    color: var(--white);
  }

  .btn-sm svg {
    width: 13px;
    height: 13px;
  }

  /* ── Tabs ── */
  .prov-tabs {
    display: flex;
    border-bottom: 2px solid var(--rule);
    margin-bottom: 2rem;
    flex-wrap: wrap;
  }

  .prov-tab {
    background: none;
    border: none;
    padding: .75rem 1.4rem;
    font-family: 'DM Sans', sans-serif;
    font-size: .875rem;
    font-weight: 500;
    color: var(--ink-soft);
    cursor: pointer;
    position: relative;
    transition: color var(--transition);
    white-space: nowrap;
  }

  .prov-tab::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--ink);
    transform: scaleX(0);
    transition: transform var(--transition);
  }

  .prov-tab.active {
    color: var(--ink);
    font-weight: 600;
  }

  .prov-tab.active::after {
    transform: scaleX(1);
  }

  .prov-tab:hover {
    color: var(--ink);
  }

  /* ── Info banner ── */
  .info-banner {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    background: #f0f4ff;
    border: 1px solid #c7d7f7;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 1.4rem;
    font-size: .84rem;
    color: #2c3e6e;
    line-height: 1.6;
  }
  .info-banner__icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 1px; }
  .info-banner strong { color: #1a2a5e; }
  .info-banner em { font-style: normal; font-weight: 600; text-decoration: underline dotted; }

  /* ── Panels ── */
  .prov-panel {
    display: none;
    animation: fadeUp .3s ease both;
  }

  .prov-panel.active {
    display: block;
  }

  @keyframes fadeUp {
    from {
      opacity: 0;
      transform: translateY(10px)
    }

    to {
      opacity: 1;
      transform: translateY(0)
    }
  }

  /* ── Cards grid ── */
  .prov-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.2rem;
  }

  .prov-card {
    background: var(--white);
    border: 1px solid var(--rule);
    border-radius: var(--radius);
    padding: 1.4rem 1.5rem;
    transition: box-shadow var(--transition), border-color var(--transition), transform var(--transition);
    position: relative;
    overflow: hidden;
  }

  .prov-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 3px;
    height: 100%;
    background: var(--ink);
    transform: scaleY(0);
    transform-origin: bottom;
    transition: transform var(--transition);
  }

  .prov-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--rule-dark);
    transform: translateY(-2px);
  }

  .prov-card:hover::before {
    transform: scaleY(1);
  }

  .prov-card__name {
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
    font-weight: 600;
  }

  .prov-card__meta {
    font-size: .78rem;
    color: var(--ink-soft);
    display: flex;
    flex-direction: column;
    gap: .2rem;
    margin-top: .6rem;
  }

  .prov-card__meta span {
    display: flex;
    align-items: center;
    gap: .4rem;
  }

  .prov-card__meta svg {
    width: 13px;
    height: 13px;
    opacity: .6;
  }

  .prov-card__badge {
    display: inline-block;
    padding: .2rem .6rem;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .badge-active {
    background: #e8f5e9;
    color: var(--success);
  }

  .badge-inactive {
    background: #fce8e6;
    color: var(--danger);
  }

  .prov-card__actions {
    display: flex;
    gap: .5rem;
    margin-top: 1rem;
    padding-top: .8rem;
    border-top: 1px solid var(--rule);
  }

  /* ── Empty state ── */
  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--ink-soft);
  }

  .empty-state svg {
    width: 52px;
    height: 52px;
    margin-bottom: 1rem;
    opacity: .3;
  }

  .empty-state h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    color: var(--ink);
    margin-bottom: .4rem;
  }

  /* ── Modal ── */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(10, 10, 10, .55);
    backdrop-filter: blur(3px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--transition);
    padding: 1rem;
  }

  .modal-overlay.open {
    opacity: 1;
    pointer-events: auto;
  }

  .modal {
    background: var(--white);
    border-radius: 10px;
    width: 100%;
    max-width: 620px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    transform: translateY(20px) scale(.97);
    transition: transform .28s cubic-bezier(.4, 0, .2, 1);
  }

  .modal-overlay.open .modal {
    transform: translateY(0) scale(1);
  }

  .modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.4rem 1.8rem;
    border-bottom: 1px solid var(--rule);
    position: sticky;
    top: 0;
    background: var(--white);
    z-index: 1;
  }

  .modal-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.3rem;
    font-weight: 700;
  }

  .modal-close {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--ink-soft);
    padding: .3rem;
    border-radius: 4px;
    display: flex;
    transition: color var(--transition), background var(--transition);
  }

  .modal-close:hover {
    color: var(--ink);
    background: var(--rule);
  }

  .modal-body {
    padding: 1.8rem;
  }

  .modal-footer {
    padding: 1rem 1.8rem 1.4rem;
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    border-top: 1px solid var(--rule);
  }

  /* ── Forms ── */
  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.1rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: .4rem;
  }

  .form-group.full {
    grid-column: 1/-1;
  }

  .form-group label {
    font-size: .775rem;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--ink-soft);
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    padding: .65rem .9rem;
    border: 1px solid var(--rule-dark);
    border-radius: var(--radius);
    font-family: 'DM Sans', sans-serif;
    font-size: .875rem;
    color: var(--ink);
    background: var(--paper);
    transition: border-color var(--transition), box-shadow var(--transition);
    outline: none;
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    border-color: var(--ink);
    box-shadow: 0 0 0 3px rgba(10, 10, 10, .07);
  }

  .form-group textarea {
    resize: vertical;
    min-height: 70px;
  }

  .form-section-title {
    font-family: 'Playfair Display', serif;
    font-size: .95rem;
    font-weight: 600;
    color: var(--ink);
    padding-bottom: .4rem;
    border-bottom: 1px solid var(--rule);
    grid-column: 1/-1;
    margin-top: .4rem;
  }

  /* ── Compras layout ── */
  .compra-layout {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 1.5rem;
    align-items: start;
  }

  @media(max-width:860px) {

    .compra-layout,
    .form-grid {
      grid-template-columns: 1fr;
    }
  }

  .compra-form-card {
    background: var(--white);
    border: 1px solid var(--rule);
    border-radius: var(--radius);
    padding: 1.8rem;
  }

  .compra-form-card h3 {
    font-family: 'Playfair Display', serif;
    font-size: 1.15rem;
    margin-bottom: 1.2rem;
    padding-bottom: .7rem;
    border-bottom: 1px solid var(--rule);
  }

  /* ── Table ── */
  .table-wrap {
    background: var(--white);
    border: 1px solid var(--rule);
    border-radius: var(--radius);
    overflow: hidden;
  }

  .table-wrap table {
    width: 100%;
    border-collapse: collapse;
    font-size: .82rem;
  }

  .table-wrap thead {
    background: var(--ink);
    color: var(--white);
  }

  .table-wrap thead th {
    padding: .7rem 1rem;
    text-align: left;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    font-size: .7rem;
  }

  .table-wrap tbody tr {
    border-bottom: 1px solid var(--rule);
    transition: background var(--transition);
  }

  .table-wrap tbody tr:last-child {
    border-bottom: none;
  }

  .table-wrap tbody tr:hover {
    background: #f5f5f5;
  }

  .table-wrap tbody tr.row-cancelada {
    opacity: .6;
    background: #fef2f2;
  }

  .table-wrap tbody tr.row-cancelada:hover {
    background: #f9edf0;
  }

  .table-wrap td {
    padding: .75rem 1rem;
    color: var(--ink-mid);
    vertical-align: middle;
  }

  .table-wrap td strong {
    color: var(--ink);
    font-weight: 600;
  }

  /* Estado badges */
  .badge-estado {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .2rem .65rem;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .badge-activa {
    background: #e8f5e9;
    color: var(--success);
  }

  .badge-cancelada {
    background: #fce8e6;
    color: var(--danger);
  }

  /* stock up/down */
  .stock-up {
    color: var(--success);
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: .25rem;
  }

  .stock-down {
    color: var(--danger);
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: .25rem;
  }

  .stock-up svg,
  .stock-down svg {
    width: 14px;
    height: 14px;
  }

  /* ── Toast ── */
  .toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    z-index: 2000;
    display: flex;
    flex-direction: column;
    gap: .5rem;
  }

  .toast {
    background: var(--ink);
    color: var(--white);
    padding: .75rem 1.2rem;
    border-radius: var(--radius);
    font-size: .82rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: .6rem;
    box-shadow: var(--shadow-lg);
    animation: slideIn .3s ease both;
    max-width: 320px;
  }

  .toast.success {
    border-left: 4px solid #4caf50;
  }

  .toast.error {
    border-left: 4px solid var(--danger);
  }

  @keyframes slideIn {
    from {
      transform: translateX(30px);
      opacity: 0
    }

    to {
      transform: translateX(0);
      opacity: 1
    }
  }

  @keyframes slideOut {
    to {
      transform: translateX(30px);
      opacity: 0
    }
  }

  /* ── Loader ── */
  .loader {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, .3);
    border-top-color: var(--white);
    border-radius: 50%;
    animation: spin .6s linear infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg)
    }
  }

  /* ── Search bar ── */
  .search-bar {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin-bottom: 1.5rem;
  }

  .search-bar input {
    flex: 1;
    max-width: 320px;
    padding: .6rem 1rem;
    border: 1px solid var(--rule-dark);
    border-radius: var(--radius);
    font-family: 'DM Sans', sans-serif;
    font-size: .875rem;
    outline: none;
    background: var(--white);
    transition: border-color var(--transition), box-shadow var(--transition);
  }

  .search-bar input:focus {
    border-color: var(--ink);
    box-shadow: 0 0 0 3px rgba(10, 10, 10, .07);
  }

  .badge-count {
    background: var(--ink);
    color: var(--white);
    font-size: .7rem;
    font-weight: 700;
    padding: .15rem .5rem;
    border-radius: 20px;
    margin-left: .5rem;
  }

  /* ── DataTables override ── */
  .dt-wrapper {
    padding: 1rem 1rem .5rem;
  }

  div.dataTables_wrapper div.dataTables_filter input {
    border: 1px solid var(--rule-dark);
    border-radius: var(--radius);
    padding: .4rem .7rem;
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    outline: none;
  }

  div.dataTables_wrapper div.dataTables_filter input:focus {
    border-color: var(--ink);
  }

  div.dataTables_wrapper div.dataTables_length select {
    border: 1px solid var(--rule-dark);
    border-radius: var(--radius);
    padding: .3rem .5rem;
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
  }

  div.dataTables_wrapper div.dataTables_info {
    font-size: .78rem;
    color: var(--ink-soft);
    padding: 0 1rem .8rem;
  }

  div.dataTables_wrapper div.dataTables_paginate {
    padding: .5rem 1rem 1rem;
  }

  div.dataTables_wrapper div.dataTables_paginate .paginate_button {
    border-radius: var(--radius) !important;
    font-family: 'DM Sans', sans-serif;
    font-size: .78rem !important;
  }

  div.dataTables_wrapper div.dataTables_paginate .paginate_button.current {
    background: var(--ink) !important;
    color: var(--white) !important;
    border-color: var(--ink) !important;
  }

  table.dataTable thead th {
    background: var(--ink);
    color: var(--white);
    border-bottom: none !important;
  }

  table.dataTable.no-footer {
    border-bottom: none;
  }
</style>

<div class="prov-module">

  <!-- Header -->
  <div class="prov-header">
    <div class="prov-header__title">
      <span>Gestión</span>
      Proveedores
    </div>
    <button class="btn-primary" onclick="openModal('modalAltaProveedor')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19" />
        <line x1="5" y1="12" x2="19" y2="12" />
      </svg>
      Nuevo proveedor
    </button>
  </div>

  <!-- Tabs -->
  <div class="prov-tabs">
    <button class="prov-tab active" onclick="switchTab('proveedores',this)">
      Proveedores <span class="badge-count" id="badgeCount">0</span>
    </button>
    <button class="prov-tab" onclick="switchTab('asignaciones',this)">Materias por proveedor</button>
    <button class="prov-tab" onclick="switchTab('compras',this)">Registrar compra</button>
    <button class="prov-tab" onclick="switchTab('historial',this)">Historial de compras</button>
  </div>

  <!-- ══ Panel: Proveedores ══ -->
  <div class="prov-panel active" id="panel-proveedores">
    <div class="info-banner">
      <span class="info-banner__icon">ℹ️</span>
      <div>
        <strong>¿Para qué sirve esta sección?</strong>
        Los proveedores son las empresas o personas que te venden las materias primas (harina, manteca, huevos, etc.).
        Registrá acá a cada uno con sus datos de contacto. Luego, en la pestaña <em>Materias por proveedor</em> asignás qué materiales te provee cada uno,
        y en <em>Registrar compra</em> ingresás cada compra para actualizar el stock automáticamente.
      </div>
    </div>
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

  <!-- ══ Panel: Asignaciones ══ -->
  <div class="prov-panel" id="panel-asignaciones">
    <div class="info-banner">
      <span class="info-banner__icon">🔗</span>
      <div>
        <strong>¿Para qué sirve esta sección?</strong>
        Acá vinculás cada proveedor con las materias primas que te puede suministrar.
        Esta relación es necesaria para que, al registrar una compra, solo veas los insumos que corresponden a ese proveedor.
        Un mismo insumo puede estar asignado a varios proveedores (por ejemplo, podés comprar harina de dos distribuidores distintos).
      </div>
    </div>
    <div class="compra-form-card" style="margin-bottom:1.5rem;">
      <h3>Asignar materia prima a proveedor</h3>
      <div class="form-grid">
        <div class="form-group">
          <label>Proveedor</label>
          <select id="selectProvAsignacion">
            <option value="">— Seleccioná proveedor —</option>
          </select>
        </div>
        <div class="form-group">
          <label>Materia prima</label>
          <select id="selectMateriaAsignacion">
            <option value="">— Seleccioná materia prima —</option>
          </select>
        </div>
      </div>
      <div style="margin-top:1.2rem;display:flex;justify-content:flex-end;">
        <button class="btn-primary" onclick="asignarMateriaProveedor()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Asignar
        </button>
      </div>
    </div>
    <div class="table-wrap">
      <table id="tablaAsignacionesDT" style="width:100%">
        <thead>
          <tr>
            <th>Proveedor</th>
            <th>Materia prima</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody id="tablaAsignaciones"></tbody>
      </table>
    </div>
  </div>

  <!-- ══ Panel: Registrar compra ══ -->
  <div class="prov-panel" id="panel-compras">
    <div class="info-banner">
      <span class="info-banner__icon">🛒</span>
      <div>
        <strong>¿Cómo funciona registrar una compra?</strong>
        Seleccioná el proveedor y la materia prima. Ingresá la <strong>cantidad</strong> recibida (en la unidad de medida del insumo: g, ml, u.).
        El <strong>costo unitario</strong> es opcional pero recomendado — se usa para calcular el costo de producción y la rentabilidad en el módulo de Analítica.
        Al confirmar, el stock de esa materia prima se actualiza automáticamente y la compra queda en el historial.
        <br><span style="color:#b7791f">⚠️ Si cometiste un error, podés cancelar la compra desde el Historial — esto revierte el stock.</span>
      </div>
    </div>
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
            <select id="selectMateriaCompra" onchange="mostrarStockInfo()">
              <option value="">— Primero seleccioná proveedor —</option>
            </select>
          </div>
          <div class="form-group">
            <label>Cantidad a comprar</label>
            <div style="display:flex;gap:8px;align-items:center;">
              <input type="number" id="inputCantidad" placeholder="0.00" min="0" step="0.01" style="flex:1" oninput="actualizarConversion()">
              <select id="selectUnidadCompra" style="width:90px;padding:10px 8px;border:1.5px solid var(--rule);border-radius:var(--radius);font-family:inherit;font-size:.85rem;background:#fff;color:var(--ink);outline:none;" onchange="actualizarConversion()">
                <option value="">—</option>
              </select>
            </div>
            <div id="conversionNote" style="display:none;margin-top:6px;padding:8px 12px;background:#f0f8f0;border:1px solid #b8e0c0;border-radius:6px;font-size:.8rem;color:#1a5c30;line-height:1.5;"></div>
          </div>
          <div class="form-group">
            <label id="labelCosto">Costo unitario (opcional)</label>
            <input type="number" id="inputCosto" placeholder="0.00" min="0" step="0.01">
            <div style="margin-top:4px;font-size:.75rem;color:var(--ink-soft)" id="costoHint">Ingresá el precio que pagás por cada unidad de compra.</div>
          </div>
          <div class="form-group">
            <label>Observaciones</label>
            <textarea id="inputObsCompra" placeholder="Notas de la compra…"></textarea>
          </div>
        </div>
        <div style="margin-top:1.4rem;display:flex;justify-content:flex-end;">
          <button class="btn-primary" onclick="registrarCompra()" id="btnRegistrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            Registrar y actualizar stock
          </button>
        </div>
      </div>
      <div>
        <div class="compra-form-card" style="border-left:3px solid var(--ink);">
          <h3>Stock actual</h3>
          <div id="stockInfoContent" style="color:var(--ink-soft);font-size:.85rem;">
            Seleccioná una materia prima para ver el stock actual.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ Panel: Historial ══ -->
  <div class="prov-panel" id="panel-historial">
    <div class="info-banner">
      <span class="info-banner__icon">📋</span>
      <div>
        <strong>Historial de compras</strong>
        Acá están todas las compras de materias primas registradas.
        <strong>FINALIZADA</strong> significa que el stock ya fue sumado y la compra está vigente.
        <strong>Cancelada</strong> significa que se revirtió — el stock volvió a su estado anterior.
        Podés <em>cancelar</em> una compra finalizada (por error de carga o devolución) o <em>reactivar</em> una cancelada.
        El campo <strong>Costo unit.</strong> es el precio por unidad pagado al proveedor; se usa para calcular la inversión en materiales.
      </div>
    </div>
    <div class="table-wrap">
      <table id="tablaHistorialDT" style="width:100%">
        <thead>
          <tr>
            <th>#</th>
            <th>Proveedor</th>
            <th>Materia prima</th>
            <th>Cantidad</th>
            <th>Costo unit.</th>
            <th>Fecha</th>
            <th>Stock ant.</th>
            <th>Stock result.</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody id="tablaHistorial"></tbody>
      </table>
    </div>
  </div>

</div><!-- /prov-module -->

<!-- ══ Modal: Alta / Editar proveedor ══ -->
<div class="modal-overlay" id="modalAltaProveedor">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <h2 id="modalTitle">Nuevo proveedor</h2>
      <button class="modal-close" onclick="closeModal('modalAltaProveedor')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
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
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
          <polyline points="17 21 17 13 7 13 7 21" />
          <polyline points="7 3 7 8 15 8" />
        </svg>
        Guardar proveedor
      </button>
    </div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
  /* ══ STATE ══ */
  let proveedores = [],
    materias = [],
    editId = null;
  let dtAsignaciones = null,
    dtHistorial = null;

  /* ══ INIT ══ */
  document.addEventListener('DOMContentLoaded', () => {
    initDataTables();
    cargarProveedores();
    cargarTodasMaterias();
    cargarHistorial();
    cargarAsignaciones();
  });

  /* ══ DataTables init ══ */
  function initDataTables() {
    dtAsignaciones = $('#tablaAsignacionesDT').DataTable({
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
      },
      pageLength: 10,
      order: [
        [0, 'asc']
      ],
      columnDefs: [{
        orderable: false,
        targets: 2
      }],
      drawCallback: function() {
        aplicarEstilosTabla();
      }
    });
    dtHistorial = $('#tablaHistorialDT').DataTable({
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
      },
      pageLength: 15,
      order: [
        [5, 'desc']
      ],
      columnDefs: [{
        orderable: false,
        targets: 9
      }],
      drawCallback: function() {
        aplicarEstilosTabla();
      }
    });
  }

  function aplicarEstilosTabla() {
    // Filas canceladas
    $('#tablaHistorialDT tbody tr').each(function() {
      if ($(this).find('.badge-cancelada').length) $(this).addClass('row-cancelada');
    });
  }

  /* ══ TABS ══ */
  function switchTab(tab, btn) {
    document.querySelectorAll('.prov-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.prov-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + tab).classList.add('active');
    if (tab === 'asignaciones') cargarAsignaciones();
    if (tab === 'historial') cargarHistorial();
  }

  /* ══ MODAL ══ */
  function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
    if (id === 'modalAltaProveedor') resetForm();
  }
  document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => {
    if (e.target === o) closeModal(o.id);
  }));

  function resetForm() {
    document.getElementById('formProveedor').reset();
    editId = null;
    document.getElementById('modalTitle').textContent = 'Nuevo proveedor';
  }

  /* ══ TOAST ══ */
  function toast(msg, type = 'success') {
    const c = document.getElementById('toastContainer'),
      t = document.createElement('div');
    t.className = `toast ${type}`;
    const icon = type === 'success' ? '<polyline points="20 6 9 17 4 12"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>';
    t.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">${icon}</svg>${msg}`;
    c.appendChild(t);
    setTimeout(() => {
      t.style.animation = 'slideOut .3s ease forwards';
      setTimeout(() => t.remove(), 300);
    }, 3000);
  }

  /* ══ AJAX ══ */
  async function ajax(url, data = null) {
    const opts = {
      method: data ? 'POST' : 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    };
    if (data) opts.body = JSON.stringify(data);
    const r = await fetch(url, opts);
    return r.json();
  }

  /* ══════════════════════════
     PROVEEDORES
  ══════════════════════════ */
  async function cargarProveedores() {
    try {
      const res = await ajax('ajax/get_proveedores.php');
      proveedores = res.data || [];
      renderGrid(proveedores);
      poblarSelectsProveedores();
      document.getElementById('badgeCount').textContent = proveedores.filter(p => p.activo == 1).length;
    } catch (e) {
      toast('Error al cargar proveedores', 'error');
    }
  }

  function renderGrid(lista) {
    const g = document.getElementById('gridProveedores');
    if (!lista.length) {
      g.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      <h3>Sin proveedores aún</h3><p>Creá tu primer proveedor.</p></div>`;
      return;
    }
    g.innerHTML = lista.map((p, i) => `
    <div class="prov-card" style="animation:fadeUp .3s ease ${i*.05}s both">
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
        <button class="btn-sm danger" onclick="confirmarEliminarProveedor(${p.idproveedor},'${esc(p.nombre)}')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
          Eliminar
        </button>
      </div>
    </div>`).join('');
  }

  function filtrarProveedores() {
    const q = document.getElementById('searchProv').value.toLowerCase();
    renderGrid(proveedores.filter(p => p.nombre.toLowerCase().includes(q) || (p.email || '').toLowerCase().includes(q)));
  }

  function poblarSelectsProveedores() {
    const activos = proveedores.filter(p => p.activo == 1);
    const base = (ph) => `<option value="">— ${ph} —</option>` + activos.map(p => `<option value="${p.idproveedor}">${esc(p.nombre)}</option>`).join('');
    document.getElementById('selectProvAsignacion').innerHTML = base('Seleccioná proveedor');
    document.getElementById('selectProvCompra').innerHTML = base('Seleccioná un proveedor');
  }

  async function guardarProveedor() {
    const btn = document.getElementById('btnGuardar');
    const data = {
      idproveedor: editId,
      nombre: document.getElementById('pNombre').value.trim(),
      telefono: document.getElementById('pTelefono').value.trim(),
      email: document.getElementById('pEmail').value.trim(),
      direccion: document.getElementById('pDireccion').value.trim(),
      contacto_nombre: document.getElementById('pContactoNombre').value.trim(),
      contacto_telefono: document.getElementById('pContactoTel').value.trim(),
      observaciones: document.getElementById('pObservaciones').value.trim(),
      activo: document.getElementById('pActivo').value,
    };
    if (!data.nombre) {
      toast('El nombre es requerido', 'error');
      return;
    }
    btn.innerHTML = '<span class="loader"></span>';
    btn.disabled = true;
    try {
      const res = await ajax('ajax/guardar_proveedor.php', data);
      if (res.ok) {
        closeModal('modalAltaProveedor');
        await cargarProveedores();
        // SweetAlert éxito
        Swal.fire({
          icon: 'success',
          title: editId ? '¡Proveedor actualizado!' : '¡Proveedor creado!',
          text: editId ? `Los datos de "${data.nombre}" fueron guardados.` : `"${data.nombre}" fue agregado al sistema.`,
          confirmButtonColor: '#0a0a0a',
          confirmButtonText: 'Continuar',
          timer: 3000,
          timerProgressBar: true,
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: res.msg || 'No se pudo guardar',
          confirmButtonColor: '#0a0a0a'
        });
      }
    } catch (e) {
      toast('Error de conexión', 'error');
    }
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar proveedor';
    btn.disabled = false;
  }

  function editarProveedor(id) {
    const p = proveedores.find(x => x.idproveedor == id);
    if (!p) return;
    editId = id;
    document.getElementById('pNombre').value = p.nombre || '';
    document.getElementById('pTelefono').value = p.telefono || '';
    document.getElementById('pEmail').value = p.email || '';
    document.getElementById('pDireccion').value = p.direccion || '';
    document.getElementById('pContactoNombre').value = p.contacto_nombre || '';
    document.getElementById('pContactoTel').value = p.contacto_telefono || '';
    document.getElementById('pObservaciones').value = p.observaciones || '';
    document.getElementById('pActivo').value = p.activo;
    document.getElementById('modalTitle').textContent = 'Editar proveedor';
    openModal('modalAltaProveedor');
  }

  function confirmarEliminarProveedor(id, nombre) {
    Swal.fire({
      title: '¿Eliminar proveedor?',
      html: `Vas a eliminar a <strong>${nombre}</strong>.<br>Esta acción no se puede deshacer.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#c88e99',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      try {
        const res = await ajax('ajax/eliminar_proveedor.php', {
          idproveedor: id
        });
        if (res.ok) {
          await cargarProveedores();
          Swal.fire({
            icon: 'success',
            title: 'Eliminado',
            text: `"${nombre}" fue eliminado.`,
            confirmButtonColor: '#0a0a0a',
            timer: 2500,
            timerProgressBar: true
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'No se pudo eliminar',
            text: res.msg || 'Tiene registros asociados.',
            confirmButtonColor: '#0a0a0a'
          });
        }
      } catch (e) {
        toast('Error de conexión', 'error');
      }
    });
  }

  /* ══════════════════════════
     ASIGNACIONES
  ══════════════════════════ */
  async function cargarTodasMaterias() {
    try {
      const res = await ajax('ajax/get_materias.php');
      materias = res.data || [];
      const s = document.getElementById('selectMateriaAsignacion');
      s.innerHTML = '<option value="">— Seleccioná materia prima —</option>' +
        materias.map(m => `<option value="${m.idmateria_prima}">${esc(m.nombre)}</option>`).join('');
    } catch (e) {}
  }

  async function cargarAsignaciones() {
    try {
      const res = await ajax('ajax/get_asignaciones.php');
      const lista = res.data || [];
      // Limpiar DT y reinsertar
      dtAsignaciones.clear();
      lista.forEach((a, i) => {
        dtAsignaciones.row.add([
          `<strong>${esc(a.proveedor_nombre)}</strong>`,
          esc(a.materia_nombre),
          `<button class="btn-sm danger" onclick="confirmarEliminarAsignacion(${a.idproveedor},${a.idmateria_prima},'${esc(a.materia_nombre)}','${esc(a.proveedor_nombre)}')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
          Quitar
        </button>`
        ]);
      });
      dtAsignaciones.draw();
    } catch (e) {
      toast('Error al cargar asignaciones', 'error');
    }
  }

  async function asignarMateriaProveedor() {
    const idproveedor = document.getElementById('selectProvAsignacion').value;
    const idmateria = document.getElementById('selectMateriaAsignacion').value;
    if (!idproveedor || !idmateria) {
      toast('Seleccioná proveedor y materia prima', 'error');
      return;
    }
    try {
      const res = await ajax('ajax/asignar_materia_proveedor.php', {
        idproveedor,
        idmateria_prima: idmateria
      });
      if (res.ok) {
        // Reset selects
        document.getElementById('selectProvAsignacion').value = '';
        document.getElementById('selectMateriaAsignacion').value = '';
        await cargarAsignaciones();
        Swal.fire({
          icon: 'success',
          title: '¡Asignación creada!',
          text: 'La materia prima fue vinculada al proveedor.',
          confirmButtonColor: '#0a0a0a',
          timer: 2500,
          timerProgressBar: true
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: res.msg || 'No se pudo asignar.',
          confirmButtonColor: '#0a0a0a'
        });
      }
    } catch (e) {
      toast('Error de conexión', 'error');
    }
  }

  function confirmarEliminarAsignacion(idproveedor, idmateria, materia, proveedor) {
    Swal.fire({
      title: '¿Quitar asignación?',
      html: `Desvincular <strong>${materia}</strong> del proveedor <strong>${proveedor}</strong>.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#c88e99',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Sí, quitar',
      cancelButtonText: 'Cancelar',
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      try {
        const res = await ajax('ajax/eliminar_asignacion.php', {
          idproveedor,
          idmateria_prima: idmateria
        });
        if (res.ok) {
          await cargarAsignaciones();
          Swal.fire({
            icon: 'success',
            title: 'Eliminada',
            text: 'La asignación fue removida.',
            confirmButtonColor: '#0a0a0a',
            timer: 2000,
            timerProgressBar: true
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: res.msg || 'No se pudo eliminar.',
            confirmButtonColor: '#0a0a0a'
          });
        }
      } catch (e) {
        toast('Error de conexión', 'error');
      }
    });
  }

  /* ══════════════════════════
     COMPRAS
  ══════════════════════════ */
  // Mapa de conversiones entre unidades compatibles
  // Formato: { 'desde_abrev|hasta_abrev': factor }
  const CONV = {
    'Kg|G': 1000, 'G|Kg': 0.001,
    'L|ml': 1000, 'ml|L': 0.001,
  };

  // Unidades de compra disponibles según la unidad base del insumo
  const UNIDADES_COMPATIBLES = {
    'G':  [{ abrev:'G',  nombre:'Gramos (G)' }, { abrev:'Kg', nombre:'Kilogramos (Kg)' }],
    'Kg': [{ abrev:'Kg', nombre:'Kilogramos (Kg)' }, { abrev:'G', nombre:'Gramos (G)' }],
    'ml': [{ abrev:'ml', nombre:'Mililitros (ml)' }, { abrev:'L', nombre:'Litros (L)' }],
    'L':  [{ abrev:'L',  nombre:'Litros (L)' }, { abrev:'ml', nombre:'Mililitros (ml)' }],
    'U':  [{ abrev:'U',  nombre:'Unidades (U)' }],
  };

  async function cargarMateriasPorProveedor() {
    const idProv = document.getElementById('selectProvCompra').value;
    const s = document.getElementById('selectMateriaCompra');
    document.getElementById('stockInfoContent').innerHTML = 'Seleccioná una materia prima para ver el stock actual.';
    resetUnidadSelector();
    if (!idProv) {
      s.innerHTML = '<option value="">— Primero seleccioná proveedor —</option>';
      return;
    }
    s.innerHTML = '<option value="">Cargando…</option>';
    try {
      const res = await ajax(`ajax/get_materias_proveedor.php?idproveedor=${idProv}`);
      const lista = res.data || [];
      s.innerHTML = lista.length
        ? '<option value="">— Seleccioná materia prima —</option>' +
          lista.map(m =>
            `<option value="${m.idmateria_prima}"
              data-stock="${m.stock_actual}"
              data-min="${m.stock_minimo}"
              data-costo="${m.costo||''}"
              data-unidad-abrev="${m.unidad_abrev||''}"
              data-unidad-nombre="${m.unidad_nombre||''}"
            >${esc(m.nombre)} (${m.unidad_abrev||'?'})</option>`
          ).join('')
        : '<option value="">Sin materias primas asignadas</option>';
    } catch (e) {
      s.innerHTML = '<option value="">Error al cargar</option>';
    }
  }

  function resetUnidadSelector() {
    const sel = document.getElementById('selectUnidadCompra');
    sel.innerHTML = '<option value="">—</option>';
    document.getElementById('conversionNote').style.display = 'none';
    document.getElementById('labelCosto').textContent = 'Costo unitario (opcional)';
    document.getElementById('costoHint').textContent  = 'Ingresá el precio que pagás por cada unidad de compra.';
  }

  function mostrarStockInfo() {
    const opt = document.getElementById('selectMateriaCompra').selectedOptions[0];
    const c = document.getElementById('stockInfoContent');
    if (!opt || !opt.value) {
      c.innerHTML = 'Seleccioná una materia prima para ver el stock actual.';
      resetUnidadSelector();
      return;
    }

    const stock      = parseFloat(opt.dataset.stock)       || 0;
    const min        = parseFloat(opt.dataset.min)         || 0;
    const costo      = opt.dataset.costo ? `$${parseFloat(opt.dataset.costo).toFixed(2)}` : '—';
    const unidAbrev  = opt.dataset.unidadAbrev  || '';
    const pct        = min > 0 ? Math.min(100, (stock / min) * 100) : 100;
    const color      = stock >= min ? '#1a7a4a' : '#c88e99';

    c.innerHTML = `
    <div style="display:grid;gap:.9rem;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
        <div style="background:#f5f5f5;border-radius:4px;padding:.7rem;text-align:center;">
          <div style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:${color}">${stock} <span style="font-size:.9rem">${unidAbrev}</span></div>
          <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-soft);margin-top:.2rem;">Stock actual</div>
        </div>
        <div style="background:#f5f5f5;border-radius:4px;padding:.7rem;text-align:center;">
          <div style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;">${min} <span style="font-size:.9rem">${unidAbrev}</span></div>
          <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-soft);margin-top:.2rem;">Stock mínimo</div>
        </div>
      </div>
      <div>
        <div style="font-size:.72rem;color:var(--ink-soft);margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.05em;">Nivel de stock</div>
        <div style="background:var(--rule);border-radius:20px;height:6px;overflow:hidden;">
          <div style="height:100%;width:${pct}%;background:${color};border-radius:20px;transition:width .6s ease;"></div>
        </div>
      </div>
      <div style="font-size:.8rem;color:var(--ink-soft);">Costo registrado: <strong style="color:var(--ink)">${costo}</strong></div>
    </div>`;

    if (opt.dataset.costo) document.getElementById('inputCosto').value = parseFloat(opt.dataset.costo).toFixed(2);

    // Poblar selector de unidades de compra
    const sel = document.getElementById('selectUnidadCompra');
    const compatibles = UNIDADES_COMPATIBLES[unidAbrev] || [{ abrev: unidAbrev, nombre: unidAbrev }];
    sel.innerHTML = compatibles.map(u =>
      `<option value="${u.abrev}" ${u.abrev === unidAbrev ? 'selected' : ''}>${u.abrev}</option>`
    ).join('');
    actualizarConversion();
  }

  function actualizarConversion() {
    const opt     = document.getElementById('selectMateriaCompra').selectedOptions[0];
    const unidBase= opt?.dataset?.unidadAbrev || '';
    const unidComp= document.getElementById('selectUnidadCompra').value;
    const cantStr = document.getElementById('inputCantidad').value;
    const cant    = parseFloat(cantStr);
    const note    = document.getElementById('conversionNote');
    const label   = document.getElementById('labelCosto');
    const hint    = document.getElementById('costoHint');

    if (!unidComp) { note.style.display = 'none'; return; }

    // Actualizar etiqueta del costo
    label.textContent = `Costo por ${unidComp} (opcional)`;
    hint.textContent  = `Precio que pagás por cada ${unidComp}. El sistema convierte al stock en ${unidBase}.`;

    if (!cant || isNaN(cant) || !unidBase) { note.style.display = 'none'; return; }

    const factor = CONV[`${unidComp}|${unidBase}`] ?? 1;
    const cantBase = cant * factor;

    if (factor === 1) {
      note.style.display = 'none';
    } else {
      note.style.display = 'block';
      const fmtN = n => n % 1 === 0 ? n.toLocaleString('es-AR') : n.toLocaleString('es-AR', {maximumFractionDigits:3});
      note.innerHTML = `📦 <strong>${fmtN(cant)} ${unidComp}</strong> → se agregarán <strong>${fmtN(cantBase)} ${unidBase}</strong> al stock`;
    }
  }

  async function registrarCompra() {
    const idProv    = document.getElementById('selectProvCompra').value;
    const idMateria = document.getElementById('selectMateriaCompra').value;
    const cantOrig  = parseFloat(document.getElementById('inputCantidad').value);
    const unidComp  = document.getElementById('selectUnidadCompra').value;
    const costo     = document.getElementById('inputCosto').value;
    const obs       = document.getElementById('inputObsCompra').value;
    const opt       = document.getElementById('selectMateriaCompra').selectedOptions[0];
    const unidBase  = opt?.dataset?.unidadAbrev || '';

    if (!idProv)              { toast('Seleccioná un proveedor', 'error'); return; }
    if (!idMateria)           { toast('Seleccioná una materia prima', 'error'); return; }
    if (!cantOrig || cantOrig <= 0) { toast('Ingresá una cantidad válida', 'error'); return; }
    if (!unidComp)            { toast('Seleccioná la unidad de compra', 'error'); return; }

    // Convertir a unidad base para el stock
    const factor   = CONV[`${unidComp}|${unidBase}`] ?? 1;
    const cantBase = cantOrig * factor;

    const btn = document.getElementById('btnRegistrar');
    btn.innerHTML = '<span class="loader"></span> Procesando…';
    btn.disabled = true;
    try {
      const res = await ajax('ajax/registrar_compra.php', {
        idproveedor:       idProv,
        idmateria_prima:   idMateria,
        cantidad:          cantBase,        // en unidad BASE → para el stock
        cantidad_original: cantOrig,        // en unidad de compra → para historial
        unidad_compra:     unidComp,        // abreviatura ej. "Kg"
        costo:             costo || null,   // precio por unidad de compra
        observaciones:     obs,
      });
      if (res.ok) {
        // Reset form
        document.getElementById('selectProvCompra').value = '';
        document.getElementById('selectMateriaCompra').innerHTML = '<option value="">— Primero seleccioná proveedor —</option>';
        document.getElementById('inputCantidad').value = '';
        document.getElementById('inputCosto').value = '';
        document.getElementById('inputObsCompra').value = '';
        document.getElementById('stockInfoContent').innerHTML = 'Seleccioná una materia prima para ver el stock actual.';
        resetUnidadSelector();
        await cargarHistorial();
        Swal.fire({
          icon: 'success',
          title: '¡Compra registrada!',
          html: factor !== 1
            ? `Se sumaron <strong>+${cantOrig} ${unidComp}</strong> = <strong>+${cantBase} ${unidBase}</strong> al stock.`
            : `Se sumaron <strong>+${cantBase} ${unidBase}</strong> al stock.`,
          confirmButtonColor: '#0a0a0a',
          confirmButtonText: 'Continuar',
          timer: 3000,
          timerProgressBar: true,
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: res.msg || 'No se pudo registrar.',
          confirmButtonColor: '#0a0a0a'
        });
      }
    } catch (e) {
      toast('Error de conexión', 'error');
    }
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Registrar y actualizar stock';
    btn.disabled = false;
  }

  /* ══════════════════════════
     CANCELAR COMPRA
  ══════════════════════════ */
  function confirmarCancelarCompra(id, materia, cantidad) {
    Swal.fire({
      title: '¿Cancelar esta compra?',
      html: `<p style="margin-bottom:.8rem">Compra de <strong>${cantidad} uds</strong> de <strong>${materia}</strong>.</p>
           <p style="font-size:.85rem;color:#6b7280;margin-bottom:.5rem">Se <strong>restará del stock</strong> la cantidad indicada.</p>
           <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;text-align:left;margin-bottom:.3rem">Motivo (opcional)</label>
           <input id="swal-motivo" class="swal2-input" placeholder="Ej: Error de carga, devolución…" style="font-size:.85rem;">`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#c88e99',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Sí, cancelar compra',
      cancelButtonText: 'No, mantener',
      preConfirm: () => {
        return document.getElementById('swal-motivo').value;
      }
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      try {
        const res = await ajax('ajax/cancelar_compra.php', {
          id,
          motivo: result.value || ''
        });
        if (res.ok) {
          await cargarHistorial();
          Swal.fire({
            icon: 'success',
            title: 'Compra cancelada',
            text: 'El stock fue ajustado correctamente.',
            confirmButtonColor: '#0a0a0a',
            timer: 2500,
            timerProgressBar: true
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: res.msg || 'No se pudo cancelar.',
            confirmButtonColor: '#0a0a0a'
          });
        }
      } catch (e) {
        toast('Error de conexión', 'error');
      }
    });
  }

  /* ══════════════════════════
     REACTIVAR COMPRA
  ══════════════════════════ */
  function confirmarReactivarCompra(id, materia, cantidad) {
    Swal.fire({
      title: '¿Reactivar esta compra?',
      html: `<p>Se sumarán nuevamente <strong>+${cantidad} uds</strong> de <strong>${materia}</strong> al stock.</p>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#1a7a4a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Sí, reactivar',
      cancelButtonText: 'No',
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      try {
        const res = await ajax('ajax/reactivar_compra.php', {
          id
        });
        if (res.ok) {
          await cargarHistorial();
          Swal.fire({
            icon: 'success',
            title: 'Compra reactivada',
            text: 'El stock fue actualizado correctamente.',
            confirmButtonColor: '#0a0a0a',
            timer: 2500,
            timerProgressBar: true
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: res.msg || 'No se pudo reactivar.',
            confirmButtonColor: '#0a0a0a'
          });
        }
      } catch (e) {
        toast('Error de conexión', 'error');
      }
    });
  }

  /* ══════════════════════════
     HISTORIAL
  ══════════════════════════ */
  async function cargarHistorial() {
    try {
      const res = await ajax('ajax/get_historial_compras.php');
      const lista = res.data || [];
      dtHistorial.clear();
      lista.forEach(c => {
        const esActiva   = c.estado === 'activa';
        const unidBase   = c.unidad_base   || '';
        const unidComp   = c.unidad_compra || unidBase;
        const cantOrig   = c.cantidad_original != null ? parseFloat(c.cantidad_original) : null;
        const cantBase   = parseFloat(c.cantidad);

        // Etiqueta de cantidad: muestra unidad de compra + equivalente en base si difieren
        let cantLabel;
        if (cantOrig !== null && unidComp && unidComp !== unidBase) {
          cantLabel = `${cantOrig.toLocaleString('es-AR')} ${unidComp}<br><span style="font-size:.72rem;color:var(--ink-soft)">${cantBase.toLocaleString('es-AR')} ${unidBase}</span>`;
        } else {
          cantLabel = `${cantBase.toLocaleString('es-AR')} ${unidBase}`;
        }

        const cantHtml = esActiva
          ? `<span class="stock-up"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 15 12 9 6 15"/></svg>${cantLabel}</span>`
          : `<span class="stock-down"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="18 9 12 15 6 9"/></svg>${cantLabel}</span>`;

        // Costo: muestra precio por unidad de compra
        const costoHtml = c.costo
          ? `$${parseFloat(c.costo).toFixed(2)}<span style="font-size:.72rem;color:var(--ink-soft)">/${unidComp||unidBase}</span>`
          : '—';

        const estadoLabel = c.estado === 'activa' ? 'FINALIZADA' : (c.estado || '').toUpperCase();
        const estadoHtml = `<span class="badge-estado badge-${c.estado}">${estadoLabel}</span>`;
        const accionHtml = esActiva ?
          `<button class="btn-sm warning" onclick="confirmarCancelarCompra(${c.id},'${esc(c.materia_nombre)}',${cantBase})">
             <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
             Cancelar
           </button>` :
          `<div style="display:flex;flex-direction:column;gap:.35rem;align-items:flex-start;">
             <span style="font-size:.75rem;color:var(--ink-soft);">${c.cancelado_motivo||'—'}</span>
             <button class="btn-sm" onclick="confirmarReactivarCompra(${c.id},'${esc(c.materia_nombre)}',${cantBase})" style="color:var(--success);border-color:var(--success);">
               <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
               Reactivar
             </button>
           </div>`;

        const unidStr = unidBase || '';
        dtHistorial.row.add([
          `<strong>${c.id}</strong>`,
          esc(c.proveedor_nombre),
          esc(c.materia_nombre),
          cantHtml,
          costoHtml,
          c.created_at,
          c.stock_anterior != null ? `${parseFloat(c.stock_anterior).toLocaleString('es-AR')} ${unidStr}` : '—',
          `<strong>${parseFloat(c.stock_nuevo||0).toLocaleString('es-AR')} ${unidStr}</strong>`,
          estadoHtml,
          accionHtml
        ]);
      });
      dtHistorial.draw();
    } catch (e) {}
  }

  /* ══ Util ══ */
  function esc(s) {
    return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>