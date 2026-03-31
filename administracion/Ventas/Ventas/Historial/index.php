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

<!-- TOAST -->
<div id="toast" class="toast" style="display:none;"></div>

<script src="historial.js"></script>

<?php include '../../../../panel/dashboard/layaut/footer.php'; ?>
