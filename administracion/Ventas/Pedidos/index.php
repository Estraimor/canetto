<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../config/tron.php';
$pageTitle = "Pedidos — Canetto";
include '../../../panel/dashboard/layaut/nav.php';
?>

<link rel="stylesheet" href="pedidos.css">

<div class="pedidos-wrapper">

<a href="javascript:history.back()" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Volver
</a>

  <div class="pedidos-header">
    <div>
      <h1>🧾 Pedidos activos</h1>
      <p class="subtitle">Gestioná los pedidos en curso — se actualiza cada 30 segundos</p>
    </div>
    <div class="header-actions">
      <div class="filter-group">
        <select id="filtro-estado" class="filter-select">
          <option value="">Todos los estados</option>
          <option value="1">Pendiente</option>
          <option value="2">En Preparación</option>
          <option value="3">En reparto</option>
          <option value="5">Pendiente de Pago</option>
          <option value="7">Listo para retiro</option>
        </select>
        <select id="filtro-origen" class="filter-select">
          <option value="">Todos los orígenes</option>
          <option value="tienda">📱 App / Tienda</option>
          <option value="pos">🖥 Administración</option>
        </select>
        <input type="date" id="filtro-fecha" class="filter-input">
        <button class="btn-filter" onclick="PedidosApp.cargarPedidos()">Filtrar</button>
      </div>
      <a href="<?= URL_ADMIN ?>/Ventas/Ventas/index.php" class="btn-nueva-venta">+ Nueva Venta</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-bar" id="stats-bar">
    <div class="stat-pill" data-estado="1" onclick="PedidosApp.filtrarPorEstado(1)">
      <span class="stat-dot pendiente"></span>
      <span id="count-1">0</span> Pendiente
    </div>
    <div class="stat-pill" data-estado="2" onclick="PedidosApp.filtrarPorEstado(2)">
      <span class="stat-dot preparacion"></span>
      <span id="count-2">0</span> En Preparación
    </div>
    <div class="stat-pill" data-estado="3" onclick="PedidosApp.filtrarPorEstado(3)">
      <span class="stat-dot repartidor"></span>
      <span id="count-3">0</span> En reparto
    </div>
    <div class="stat-pill" data-estado="5" onclick="PedidosApp.filtrarPorEstado(5)">
      <span class="stat-dot pend-pago"></span>
      <span id="count-5">0</span> Pend. Pago
    </div>
    <div class="stat-pill" data-estado="7" onclick="PedidosApp.filtrarPorEstado(7)">
      <span class="stat-dot listo-retiro"></span>
      <span id="count-7">0</span> Listo retiro
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
          <th>#</th><th>Cliente</th><th>Productos</th>
          <th>Total</th><th>Pago</th><th>Fecha</th>
          <th>Estado</th><th style="width:32px"></th>
        </tr>
      </thead>
      <tbody id="pedidos-tbody">
        <tr><td colspan="8" class="loading-row">⏳ Cargando pedidos...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- MODAL DETALLE -->
<div class="modal-overlay" id="modal-detalle" style="display:none;">
  <div class="modal-box detalle-box">
    <div class="modal-header">
      <h3 id="detalle-title">Detalle de Pedido</h3>
      <button class="modal-close" onclick="PedidosApp.cerrarDetalle()">✕</button>
    </div>
    <div class="modal-body" id="detalle-body"></div>
  </div>
</div>

<!-- MODAL REPARTIDOR -->
<div class="modal-overlay" id="modal-repartidor" style="display:none;">
  <div class="modal-box" style="max-width:460px">
    <div class="modal-header">
      <h3>🛵 Forma de envío</h3>
      <button class="modal-close" onclick="PedidosApp.cerrarModalRep()">✕</button>
    </div>
    <div class="modal-body">
      <div id="rep-cliente-info"></div>

      <div style="margin-top:14px">
        <label style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;margin-bottom:6px">¿Cómo se entrega?</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px">
          <button type="button" id="btn-entrega-envio" onclick="setTipoEntregaModal('envio')"
            style="padding:10px;border:2px solid #3b82f6;background:#eff6ff;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;color:#1d4ed8">
            🛵 Envío a domicilio
          </button>
          <button type="button" id="btn-entrega-retiro" onclick="setTipoEntregaModal('retiro')"
            style="padding:10px;border:2px solid #e2e8f0;background:white;border-radius:10px;font-weight:600;font-size:13px;cursor:pointer;color:#64748b">
            🏪 Retiro en local
          </button>
        </div>
      </div>

      <!-- Opciones de envío (visible solo cuando tipo=envio) -->
      <div id="rep-envio-fields">
        <label style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;margin-bottom:8px">¿Quién lo lleva?</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
          <button type="button" id="btn-metodo-rep" onclick="setMetodoEnvio('repartidor')"
            style="padding:12px 8px;border:2px solid #f97316;background:#fff7ed;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;color:#c2410c">
            🛵 Repartidor propio
          </button>
          <button type="button" id="btn-metodo-uber" onclick="setMetodoEnvio('uber')"
            style="padding:12px 8px;border:2px solid #e2e8f0;background:white;border-radius:10px;font-weight:600;font-size:13px;cursor:pointer;color:#64748b">
            🚗 Uber
          </button>
        </div>
        <!-- Campo link Uber (solo visible cuando metodo=uber) -->
        <div id="uber-link-field" style="display:none">
          <label style="display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;margin-bottom:6px">Link de seguimiento Uber</label>
          <input type="url" id="uber-link-input" placeholder="https://uber.com/track/..."
            style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:14px;font-family:inherit;background:#f8fafc;box-sizing:border-box">
          <small style="color:#94a3b8;font-size:11px;margin-top:4px;display:block">Podés pegar el link de seguimiento de Uber para que se muestre en el admin.</small>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancelar" onclick="PedidosApp.cerrarModalRep()">Cancelar</button>
      <button class="btn-confirmar" id="btn-confirmar-rep" onclick="PedidosApp.confirmarRepartidor()">✓ Confirmar</button>
    </div>
  </div>
</div>

<script>
let _modalTipoEntrega  = 'envio';
let _modalMetodoEnvio  = 'repartidor';

function setTipoEntregaModal(tipo) {
  _modalTipoEntrega = tipo;
  const envioBtn  = document.getElementById('btn-entrega-envio');
  const retiroBtn = document.getElementById('btn-entrega-retiro');
  const repFields = document.getElementById('rep-envio-fields');
  const confBtn   = document.getElementById('btn-confirmar-rep');
  if (tipo === 'envio') {
    envioBtn.style.borderColor  = '#3b82f6'; envioBtn.style.background  = '#eff6ff'; envioBtn.style.color = '#1d4ed8';
    retiroBtn.style.borderColor = '#e2e8f0'; retiroBtn.style.background = 'white';   retiroBtn.style.color = '#64748b';
    repFields.style.display = 'block';
    confBtn.textContent = '🛵 Confirmar envío';
  } else {
    retiroBtn.style.borderColor = '#3b82f6'; retiroBtn.style.background  = '#eff6ff'; retiroBtn.style.color = '#1d4ed8';
    envioBtn.style.borderColor  = '#e2e8f0'; envioBtn.style.background = 'white';    envioBtn.style.color = '#64748b';
    repFields.style.display = 'none';
    confBtn.textContent = '🏪 Confirmar retiro';
  }
}

function setMetodoEnvio(metodo) {
  _modalMetodoEnvio = metodo;
  const repBtn   = document.getElementById('btn-metodo-rep');
  const uberBtn  = document.getElementById('btn-metodo-uber');
  const uberFld  = document.getElementById('uber-link-field');
  const confBtn  = document.getElementById('btn-confirmar-rep');
  if (metodo === 'repartidor') {
    repBtn.style.borderColor  = '#f97316'; repBtn.style.background  = '#fff7ed'; repBtn.style.color = '#c2410c';
    uberBtn.style.borderColor = '#e2e8f0'; uberBtn.style.background = 'white';  uberBtn.style.color = '#64748b';
    uberFld.style.display = 'none';
    confBtn.textContent = '🛵 Buscar repartidor';
  } else {
    uberBtn.style.borderColor = '#7c3aed'; uberBtn.style.background  = '#f5f3ff'; uberBtn.style.color = '#6d28d9';
    repBtn.style.borderColor  = '#e2e8f0'; repBtn.style.background   = 'white';   repBtn.style.color  = '#64748b';
    uberFld.style.display = 'block';
    confBtn.textContent = '🚗 Confirmar Uber';
  }
}
</script>

<!-- TOAST -->
<div id="toast" class="toast" style="display:none;"></div>

<script src="pedidos.js?v=<?= filemtime(__DIR__.'/pedidos.js') ?>"></script>

<?php include '../../../panel/dashboard/layaut/footer.php'; ?>
