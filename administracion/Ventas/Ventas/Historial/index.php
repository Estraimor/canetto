<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../../config/conexion.php';
include '../../../../panel/dashboard/layaut/nav.php';
?>

<link rel="stylesheet" href="historial.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<div class="historial-wrapper">

  <div class="historial-header">
    <div>
      <h1>📋 Historial de Ventas</h1>
      <p class="subtitle">Gestioná y actualizá el estado de cada pedido</p>
    </div>
    <div class="header-actions">
      <div class="filter-group">
        <select id="filtro-estado" class="filter-select">
          <option value="">Todos los estados</option>
          <option value="1">Pendiente</option>
          <option value="2">En Preparación</option>
          <option value="3">En manos del Repartidor</option>
          <option value="4">Entregado</option>
        </select>
        <input type="date" id="filtro-fecha" class="filter-input">
        <button class="btn-filter" onclick="HistorialApp.cargarVentas()">Filtrar</button>
      </div>
      <a href="index.php" class="btn-nueva-venta">+ Nueva Venta</a>
    </div>
  </div>

  <!-- Stats rápidos -->
  <div class="stats-bar" id="stats-bar">
    <div class="stat-pill" data-estado="1" onclick="HistorialApp.filtrarPorEstado(1)">
      <span class="stat-dot pendiente"></span>
      <span id="count-1">0</span> Pendiente
    </div>
    <div class="stat-pill" data-estado="2" onclick="HistorialApp.filtrarPorEstado(2)">
      <span class="stat-dot preparacion"></span>
      <span id="count-2">0</span> En Preparación
    </div>
    <div class="stat-pill" data-estado="3" onclick="HistorialApp.filtrarPorEstado(3)">
      <span class="stat-dot repartidor"></span>
      <span id="count-3">0</span> En reparto
    </div>
    <div class="stat-pill" data-estado="4" onclick="HistorialApp.filtrarPorEstado(4)">
      <span class="stat-dot entregado"></span>
      <span id="count-4">0</span> Entregados
    </div>
    <div class="stat-pill total-pill">
      💰 Total hoy: <strong id="total-hoy">$0.00</strong>
    </div>
  </div>

  <!-- Tabla -->
  <div class="tabla-container">
    <table class="ventas-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Cliente</th>
          <th>Productos</th>
          <th>Total</th>
          <th>Pago</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="ventas-tbody">
        <tr><td colspan="8" class="loading-row">⏳ Cargando ventas...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL DETALLE VENTA -->
<div class="modal-overlay" id="modal-detalle" style="display:none;">
  <div class="modal-box detalle-box">
    <div class="modal-header">
      <h3 id="detalle-title">Detalle de Venta</h3>
      <button class="modal-close" onclick="HistorialApp.cerrarDetalle()">✕</button>
    </div>
    <div class="modal-body" id="detalle-body">
      <!-- cargado por JS -->
    </div>
  </div>
</div>

<!-- MODAL REPARTIDOR -->
<div class="modal-overlay" id="modal-repartidor" style="display:none;">
  <div class="modal-box" style="max-width:460px">
    <div class="modal-header">
      <h3>🛵 Asignar repartidor</h3>
      <button class="modal-close" onclick="HistorialApp.cerrarModalRep()">✕</button>
    </div>
    <div class="modal-body">
      <div id="rep-cliente-info"></div>
      <div style="margin-top:14px">
        <label style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;margin-bottom:6px">¿Quién lo lleva?</label>
        <select id="rep-select" style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-family:inherit;background:#f8fafc">
          <option value="">— Elegí un repartidor —</option>
        </select>
      </div>
    </div>
    <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 20px;border-top:1px solid #f1f5f9">
      <button class="btn-cancelar" onclick="HistorialApp.cerrarModalRep()">Cancelar</button>
      <button class="btn-confirmar" onclick="HistorialApp.confirmarRepartidor()">🛵 Confirmar y enviar</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div id="toast" class="toast" style="display:none;"></div>

<script src="historial.js"></script>

<style>
.badge-envio  { display:inline-block;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;border-radius:5px;font-size:11px;font-weight:700;padding:2px 7px;margin-top:3px }
.badge-retiro { display:inline-block;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:5px;font-size:11px;font-weight:700;padding:2px 7px;margin-top:3px }
.badge-uber   { display:inline-block;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:5px;font-size:11px;font-weight:700;padding:2px 7px;margin-top:3px }
.rep-modal-info { background:#f8fafc;border-radius:10px;padding:12px 14px;font-size:13.5px;line-height:1.8;color:#334155;border:1px solid #e2e8f0 }
.rep-modal-info strong { color:#1e293b }
.modal-footer .btn-cancelar { padding:9px 18px;border-radius:9px;border:1.5px solid #e2e8f0;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:14px }
.modal-footer .btn-confirmar { padding:9px 18px;border-radius:9px;border:none;background:linear-gradient(135deg,#f97316,#ea580c);color:white;font-weight:700;cursor:pointer;font-size:14px }
</style>

<?php include '../../../../panel/dashboard/layaut/footer.php'; ?>
