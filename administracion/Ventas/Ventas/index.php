<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../../config/conexion.php';
include '../../../panel/dashboard/layaut/nav.php';
?>

<link rel="stylesheet" href="ventas.css?v=<?=filemtime(__DIR__.'/ventas.css')?>">

<div class="ventas-wrapper">

  <!-- LEFT: Catálogo de productos -->
  <div class="catalogo-panel">
    <div class="panel-header">
      <h2>🛍️ Productos</h2>
      <input type="text" id="buscar-producto" placeholder="Buscar producto..." class="search-input">
    </div>
    <div class="productos-grid" id="productos-grid"></div>
  </div>

  <!-- CENTER: Panel info producto (oculto por defecto) -->
  <div class="producto-info-panel" id="producto-info-panel" style="display:none;">
    <button class="info-close" id="info-close">✕</button>
    <div class="info-emoji" id="info-emoji">🍪</div>
    <h3 class="info-nombre" id="info-nombre">Producto</h3>
    <div class="info-precio" id="info-precio">$0.00</div>
    <div class="info-stock-row">
      <div class="info-stock-pill congelado" id="info-stock-congelado">
        <span>❄️ Congelado</span><strong id="val-congelado">—</strong>
      </div>
      <div class="info-stock-pill hecho" id="info-stock-hecho">
        <span>🔥 Hecho</span><strong id="val-hecho">—</strong>
      </div>
    </div>
    <div class="info-total-stock">Stock total: <span id="info-total-val">—</span></div>

    <!-- Toppings del producto -->
    <div class="info-toppings-wrap" id="info-toppings-wrap" style="display:none;">
      <div class="info-toppings-title">✨ Extras / Toppings</div>
      <div class="info-toppings-list" id="info-toppings-list"></div>
      <div class="info-toppings-total" id="info-toppings-total" style="display:none;">
        Extras seleccionados: <strong id="info-toppings-sum">$0</strong>
      </div>
    </div>

    <div class="info-precio-total-wrap" id="info-precio-total-wrap" style="display:none;">
      <span class="ipt-label">Total con extras</span>
      <span class="ipt-val" id="info-precio-total">$0.00</span>
    </div>

    <button class="btn-agregar-info" id="btn-agregar-info">
      🛒 Agregar al pedido
    </button>
  </div>

  <!-- RIGHT: Carrito -->
  <div class="carrito-panel">
    <div class="carrito-header">
      <h2>🧺 Pedido</h2>
      <span class="carrito-count" id="carrito-count">0 items</span>
    </div>

    <div class="carrito-items" id="carrito-items">
      <div class="carrito-empty" id="carrito-empty">
        <div class="empty-anim" id="empty-anim">
          <div class="caja-icon">📦</div>
        </div>
        <p>El carrito está vacío</p>
        <small>Hacé click en un producto para agregarlo</small>
      </div>
    </div>

    <div class="carrito-footer" id="carrito-footer" style="display:none;">
      <div class="total-row">
        <span>Subtotal</span>
        <span id="subtotal-val">$0.00</span>
      </div>
      <div class="total-row total-final">
        <span>TOTAL</span>
        <span id="total-val">$0.00</span>
      </div>
      <button class="btn-confirmar" id="btn-confirmar">
        Continuar al checkout →
      </button>
    </div>
  </div>
</div>

<!-- Animación galleta volando -->
<div class="cookie-fly" id="cookie-fly" style="display:none;">🍪</div>

<!-- MODAL CHECKOUT -->
<div class="modal-overlay" id="modal-checkout" style="display:none;">
  <div class="modal-box">
    <div class="modal-header">
      <h3>🧾 Finalizar Venta</h3>
      <button class="modal-close" id="modal-close">✕</button>
    </div>

    <div class="modal-body">
      <!-- Sección cliente -->
      <div class="form-section">
        <h4>👤 Datos del cliente</h4>

        <div class="form-group">
          <label>DNI / Nombre</label>
          <div class="input-search-row">
            <input type="text" id="cliente-buscar" placeholder="Buscá por DNI o nombre..." autocomplete="off">
            <button type="button" id="btn-buscar-cliente" class="btn-buscar">Buscar</button>
          </div>
          <div id="sugerencias-cliente" class="sugerencias-dropdown" style="display:none;"></div>
        </div>

        <div id="form-cliente-datos">
          <div class="form-row">
            <div class="form-group">
              <label>Nombre</label>
              <input type="text" id="cliente-nombre" placeholder="Nombre">
            </div>
            <div class="form-group">
              <label>Apellido</label>
              <input type="text" id="cliente-apellido" placeholder="Apellido">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>DNI</label>
              <input type="number" id="cliente-dni" placeholder="12345678">
            </div>
            <div class="form-group">
              <label>Celular</label>
              <input type="text" id="cliente-celular" placeholder="3764...">
            </div>
          </div>
        </div>

        <div id="cliente-encontrado-badge" style="display:none;" class="cliente-badge">
          <span>✅</span> <span id="cliente-badge-nombre">Cliente encontrado</span>
          <button type="button" id="btn-limpiar-cliente" class="btn-limpiar">✕</button>
        </div>
      </div>

      <!-- Método de pago -->
      <div class="form-section">
        <h4>💳 Método de pago</h4>
        <div class="metodos-pago" id="metodos-pago"></div>
      </div>

      <!-- Resumen del pedido -->
      <div class="form-section resumen-section">
        <h4>📋 Resumen</h4>
        <div id="resumen-items"></div>
        <div class="total-row total-final" style="margin-top:12px;">
          <span>TOTAL</span>
          <span id="resumen-total">$0.00</span>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn-cancelar" id="btn-cancelar">Cancelar</button>
      <button class="btn-finalizar" id="btn-finalizar">✅ Confirmar Venta</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div id="toast" class="toast" style="display:none;"></div>

<script src="ventas.js"></script>

<?php include '../../../panel/dashboard/layaut/footer.php'; ?>
