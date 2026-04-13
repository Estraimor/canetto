// ventas.js - Canetto Sales Module

const VentasApp = (() => {
  let carrito = [];
  let clienteSeleccionado = null;
  let metodoPagoSeleccionado = null;
  let productoActivo = null;  // producto actualmente en el panel info
  let stockDetalle = {};      // { id: { congelado, hecho } }

  // ─── UTILS ────────────────────────────────
  const fmt = (n) => '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (type ? ' ' + type : '');
    t.style.display = 'block';
    clearTimeout(t._tid);
    t._tid = setTimeout(() => { t.style.display = 'none'; }, 3000);
  }

  // ─── CARGAR PRODUCTOS ─────────────────────
  async function cargarProductos() {
    try {
      const res  = await fetch('api/get_productos.php');
      const data = await res.json();
      if (data.error) throw new Error(data.error);

      // Guardamos stock detallado si la API lo devuelve
      data.forEach(p => {
        stockDetalle[p.idproductos] = {
          congelado: parseFloat(p.stock_congelado ?? 0),
          hecho:     parseFloat(p.stock_hecho     ?? p.stock ?? 0)
        };
      });

      renderProductos(data);
    } catch (e) {
      document.getElementById('productos-grid').innerHTML =
        `<p style="color:#c0392b;padding:20px;">Error: ${e.message}</p>`;
    }
  }

  const EMOJIS_MAP = [
    ['cookie','galleta'],   '🍪',
    ['alfajor'],             '🍫',
    ['torta','cake'],        '🎂',
    ['muffin','cupcake'],    '🧁',
    ['croissant','medialuna'],'🥐',
    ['factura','masita'],    '🥮',
    ['pan'],                 '🍞',
    ['tarta','pie'],         '🥧',
    ['dona','donut'],        '🍩',
    ['box','degustacion'],   '🎁',
  ];
  const EMOJIS_FB = ['🍪','🧁','🎂','🥐','🍩','🥧','🍰','🥮'];

  function getEmoji(nombre) {
    const n = nombre.toLowerCase();
    for (let i = 0; i < EMOJIS_MAP.length; i += 2) {
      if (EMOJIS_MAP[i].some(k => n.includes(k))) return EMOJIS_MAP[i + 1];
    }
    // emoji consistente por nombre (no random cada render)
    let hash = 0;
    for (let c of nombre) hash = (hash * 31 + c.charCodeAt(0)) & 0xffff;
    return EMOJIS_FB[hash % EMOJIS_FB.length];
  }

  function renderProductos(productos) {
    const grid = document.getElementById('productos-grid');
    if (!productos.length) {
      grid.innerHTML = '<p style="color:var(--text-light);padding:20px;">No hay productos disponibles</p>';
      return;
    }

    grid.innerHTML = productos.map(p => `
      <div class="producto-card"
           data-id="${p.idproductos}"
           data-nombre="${p.nombre}"
           data-precio="${p.precio}"
           data-emoji="${getEmoji(p.nombre)}"
           onclick="VentasApp.mostrarInfo(this)">
        <span class="producto-icon">${getEmoji(p.nombre)}</span>
        <div class="producto-nombre">${p.nombre}</div>
        <div class="producto-precio">${fmt(p.precio)}</div>
        <div class="producto-stock">Stock: ${parseFloat(p.stock).toFixed(0)}</div>
      </div>
    `).join('');

    // Búsqueda
    document.getElementById('buscar-producto').addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.producto-card').forEach(c => {
        c.style.display = c.dataset.nombre.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // ─── PANEL INFO PRODUCTO ──────────────────
  function mostrarInfo(el) {
    // Marcar activo
    document.querySelectorAll('.producto-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');

    const id     = el.dataset.id;
    const nombre = el.dataset.nombre;
    const precio = parseFloat(el.dataset.precio);
    const emoji  = el.dataset.emoji;

    productoActivo = { id, nombre, precio, emoji, el };

    // Actualizar panel info
    document.getElementById('info-emoji').textContent  = emoji;
    document.getElementById('info-nombre').textContent = nombre;
    document.getElementById('info-precio').textContent = fmt(precio);

    const sd = stockDetalle[id] || { congelado: 0, hecho: 0 };
    document.getElementById('val-congelado').textContent = sd.congelado.toFixed(0);
    document.getElementById('val-hecho').textContent     = sd.hecho.toFixed(0);
    document.getElementById('info-total-val').textContent = (sd.congelado + sd.hecho).toFixed(0);

    // Mostrar panel y expandir grid
    const panel = document.getElementById('producto-info-panel');
    panel.style.display = 'flex';
    document.querySelector('.ventas-wrapper').classList.add('info-visible');
  }

  function cerrarInfo() {
    document.getElementById('producto-info-panel').style.display = 'none';
    document.querySelector('.ventas-wrapper').classList.remove('info-visible');
    document.querySelectorAll('.producto-card').forEach(c => c.classList.remove('active'));
    productoActivo = null;
  }

  // ─── ANIMACIÓN COOKIE VOLANDO ─────────────
  function animarCookieAlCarrito(fromEl) {
    const cookie  = document.getElementById('cookie-fly');
    const carrito = document.querySelector('.carrito-panel');

    const fromRect  = fromEl.getBoundingClientRect();
    const toRect    = carrito.getBoundingClientRect();

    const startX = fromRect.left + fromRect.width / 2 - 16;
    const startY = fromRect.top  + fromRect.height / 2 - 16;
    const endX   = toRect.left   + 30;
    const endY   = toRect.top    + 60;

    cookie.textContent = fromEl.dataset ? fromEl.dataset.emoji || '🍪' : '🍪';
    cookie.style.cssText = `
      display: block;
      left: ${startX}px;
      top: ${startY}px;
      position: fixed;
      font-size: 2rem;
      z-index: 9999;
      pointer-events: none;
      transition: none;
    `;

    // Forzar reflow
    cookie.offsetHeight;

    cookie.style.transition = 'left 0.55s cubic-bezier(.4,0,.2,1), top 0.55s cubic-bezier(.4,0,.2,1), transform 0.55s ease, opacity 0.55s ease';
    cookie.style.left      = endX + 'px';
    cookie.style.top       = endY + 'px';
    cookie.style.transform = 'scale(0.25) rotate(360deg)';
    cookie.style.opacity   = '0';

    setTimeout(() => {
      cookie.style.display = 'none';
      cookie.style.transform = '';
      cookie.style.opacity   = '';
    }, 580);
  }

  // ─── CARRITO ──────────────────────────────
  function agregarAlCarrito(el) {
    const id     = el.dataset.id;
    const nombre = el.dataset.nombre;
    const precio = parseFloat(el.dataset.precio);
    const emoji  = el.dataset.emoji;

    // Validar stock hecho antes de agregar
    const sd = stockDetalle[id] || { congelado: 0, hecho: 0 };
    if (sd.hecho <= 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Sin stock disponible',
        html: `<b>${emoji} ${nombre}</b> no tiene stock hecho disponible para la venta.<br><br>
               Primero debe registrarse producción <em>"hecha"</em> para este producto.`,
        confirmButtonColor: '#c88e99',
        confirmButtonText: 'Entendido',
        background: '#fff',
      });
      return;
    }

    const idx = carrito.findIndex(i => i.id === id);
    if (idx > -1) {
      carrito[idx].cantidad++;
    } else {
      carrito.push({ id, nombre, precio, emoji, cantidad: 1 });
    }

    renderCarrito();
    animarCookieAlCarrito(el);

    // Micro feedback
    el.style.transform = 'scale(0.93)';
    setTimeout(() => { el.style.transform = ''; }, 160);
  }

  // Botón "Agregar al pedido" del panel info
  function agregarDesdeInfo() {
    if (!productoActivo) return;
    const sd = stockDetalle[productoActivo.id] || { congelado: 0, hecho: 0 };
    if (sd.hecho <= 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Sin stock disponible',
        html: `<b>${productoActivo.emoji} ${productoActivo.nombre}</b> no tiene stock hecho disponible.<br><br>
               Primero debe registrarse producción <em>"hecha"</em> para este producto.`,
        confirmButtonColor: '#c88e99',
        confirmButtonText: 'Entendido',
      });
      return;
    }
    agregarAlCarrito(productoActivo.el);
  }

  function cambiarCantidad(id, delta) {
    const idx = carrito.findIndex(i => i.id === id);
    if (idx === -1) return;
    carrito[idx].cantidad += delta;
    if (carrito[idx].cantidad <= 0) carrito.splice(idx, 1);
    renderCarrito();
  }

  function eliminarItem(id) {
    carrito = carrito.filter(i => i.id !== id);
    renderCarrito();
  }

  function renderCarrito() {
    const container = document.getElementById('carrito-items');
    const footer    = document.getElementById('carrito-footer');
    const countEl   = document.getElementById('carrito-count');

    const total      = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const totalItems = carrito.reduce((s, i) => s + i.cantidad, 0);

    countEl.textContent = totalItems + ' item' + (totalItems !== 1 ? 's' : '');

    if (!carrito.length) {
      container.innerHTML = `
        <div class="carrito-empty" id="carrito-empty">
          <div class="empty-anim"><div class="caja-icon">📦</div></div>
          <p>El carrito está vacío</p>
          <small>Hacé click en un producto para agregarlo</small>
        </div>`;
      footer.style.display = 'none';
      return;
    }

    container.innerHTML = carrito.map(item => `
      <div class="carrito-item" id="item-${item.id}">
        <span class="item-emoji">${item.emoji}</span>
        <div class="item-info">
          <div class="item-nombre">${item.nombre}</div>
          <div class="item-precio-unit">${fmt(item.precio)} c/u</div>
        </div>
        <div class="item-controls">
          <button class="qty-btn minus" onclick="VentasApp.cambiarCantidad('${item.id}', -1)">−</button>
          <span class="qty-val">${item.cantidad}</span>
          <button class="qty-btn"       onclick="VentasApp.cambiarCantidad('${item.id}', 1)">+</button>
        </div>
        <span class="item-subtotal">${fmt(item.precio * item.cantidad)}</span>
        <button class="btn-remove-item" onclick="VentasApp.eliminarItem('${item.id}')" title="Quitar">✕</button>
      </div>
    `).join('');

    document.getElementById('subtotal-val').textContent = fmt(total);
    document.getElementById('total-val').textContent    = fmt(total);
    footer.style.display = 'block';
  }

  // ─── CHECKOUT MODAL ───────────────────────
  function abrirCheckout() {
    if (!carrito.length) { showToast('Agregá productos primero', 'error'); return; }
    renderResumen();
    cargarMetodosPago();
    // Reset cliente
    limpiarCliente();
    document.getElementById('modal-checkout').style.display = 'flex';
  }

  function cerrarCheckout() {
    document.getElementById('modal-checkout').style.display = 'none';
  }

  function renderResumen() {
    const total = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
    document.getElementById('resumen-items').innerHTML = carrito.map(i => `
      <div class="resumen-item">
        <span>${i.emoji} ${i.nombre} ×${i.cantidad}</span>
        <span>${fmt(i.precio * i.cantidad)}</span>
      </div>
    `).join('');
    document.getElementById('resumen-total').textContent = fmt(total);
  }

  async function cargarMetodosPago() {
    try {
      const res  = await fetch('api/get_metodos_pago.php');
      const data = await res.json();
      document.getElementById('metodos-pago').innerHTML = data.map(m => `
        <button class="metodo-btn" data-id="${m.idmetodo_pago}"
                onclick="VentasApp.seleccionarMetodo(this, ${m.idmetodo_pago})">
          ${iconMetodo(m.nombre)} ${m.nombre}
        </button>
      `).join('');
    } catch (e) {
      document.getElementById('metodos-pago').innerHTML =
        '<p style="color:#c0392b;font-size:.85rem;">Error al cargar métodos</p>';
    }
  }

  function iconMetodo(n) {
    const l = n.toLowerCase();
    if (l.includes('efectivo'))   return '💵';
    if (l.includes('mercado'))    return '💙';
    if (l.includes('transfer'))   return '🏦';
    if (l.includes('tarjeta'))    return '💳';
    return '💰';
  }

  function seleccionarMetodo(el, id) {
    document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    metodoPagoSeleccionado = id;
  }

  // ─── BÚSQUEDA CLIENTE ─────────────────────
  async function buscarCliente() {
    const q = document.getElementById('cliente-buscar').value.trim();
    if (!q) { showToast('Ingresá un DNI o nombre', 'error'); return; }

    try {
      const res  = await fetch('api/buscar_cliente.php?q=' + encodeURIComponent(q));
      const data = await res.json();
      const dd   = document.getElementById('sugerencias-cliente');

      if (!data.length) {
        dd.style.display = 'none';
        showToast('No encontrado. Completá los datos manualmente.', '');
        return;
      }

      dd.innerHTML = data.map(c => `
        <div class="sugerencia-item"
             onclick="VentasApp.seleccionarCliente(${JSON.stringify(c).replace(/"/g,'&quot;')})">
          <strong>${c.nombre} ${c.apellido || ''}</strong>
          <div class="sub">🪪 DNI: ${c.dni || '—'} · 📱 ${c.celular || '—'}</div>
        </div>
      `).join('');
      dd.style.display = 'block';
    } catch (e) {
      showToast('Error al buscar cliente', 'error');
    }
  }

  function seleccionarCliente(c) {
    clienteSeleccionado = c;
    document.getElementById('sugerencias-cliente').style.display = 'none';
    document.getElementById('form-cliente-datos').style.display  = 'none';
    document.getElementById('cliente-badge-nombre').textContent  =
      `${c.nombre} ${c.apellido || ''} · DNI ${c.dni || '—'}`;
    document.getElementById('cliente-encontrado-badge').style.display = 'flex';
    showToast('✅ Cliente: ' + c.nombre, 'success');
  }

  function limpiarCliente() {
    clienteSeleccionado    = null;
    metodoPagoSeleccionado = null;
    document.getElementById('form-cliente-datos').style.display         = 'block';
    document.getElementById('cliente-encontrado-badge').style.display   = 'none';
    document.getElementById('sugerencias-cliente').style.display        = 'none';
    document.getElementById('cliente-buscar').value = '';
    ['cliente-nombre','cliente-apellido','cliente-dni','cliente-celular']
      .forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.value = ''; el.readOnly = false; }
      });
    document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('active'));
  }

  // ─── CONFIRMAR VENTA ─────────────────────
  async function confirmarVenta() {
    const btnF = document.getElementById('btn-finalizar');
    if (!metodoPagoSeleccionado) { showToast('Seleccioná un método de pago', 'error'); return; }

    let clienteData;
    if (clienteSeleccionado) {
      clienteData = { id: clienteSeleccionado.idusuario };
    } else {
      const nombre   = document.getElementById('cliente-nombre').value.trim();
      const apellido = document.getElementById('cliente-apellido').value.trim();
      if (!nombre || !apellido) { showToast('Completá nombre y apellido', 'error'); return; }
      clienteData = {
        nombre, apellido,
        dni:     document.getElementById('cliente-dni').value.trim(),
        celular: document.getElementById('cliente-celular').value.trim(),
      };
    }

    const payload = {
      carrito:     carrito.map(i => ({ id: i.id, cantidad: i.cantidad, precio: i.precio })),
      cliente:     clienteData,
      metodo_pago: metodoPagoSeleccionado,
      total:       carrito.reduce((s, i) => s + i.precio * i.cantidad, 0)
    };

    btnF.disabled    = true;
    btnF.textContent = '⏳ Procesando...';

    try {
      const res  = await fetch('api/crear_venta.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success) {
        cerrarCheckout();
        carrito = [];
        clienteSeleccionado    = null;
        metodoPagoSeleccionado = null;
        renderCarrito();
        showToast('🎉 Venta #' + data.id_venta + ' creada', 'success');
      } else {
        showToast('Error: ' + (data.message || 'No se pudo crear'), 'error');
      }
    } catch (e) {
      showToast('Error de conexión', 'error');
    } finally {
      btnF.disabled    = false;
      btnF.textContent = '✅ Confirmar Venta';
    }
  }

  // ─── INIT ─────────────────────────────────
  function init() {
    cargarProductos();

    document.getElementById('btn-confirmar').addEventListener('click', abrirCheckout);
    document.getElementById('modal-close').addEventListener('click', cerrarCheckout);
    document.getElementById('btn-cancelar').addEventListener('click', cerrarCheckout);
    document.getElementById('btn-finalizar').addEventListener('click', confirmarVenta);
    document.getElementById('btn-buscar-cliente').addEventListener('click', buscarCliente);
    document.getElementById('btn-limpiar-cliente').addEventListener('click', limpiarCliente);
    document.getElementById('info-close').addEventListener('click', cerrarInfo);
    document.getElementById('btn-agregar-info').addEventListener('click', agregarDesdeInfo);

    document.getElementById('cliente-buscar').addEventListener('keydown', e => {
      if (e.key === 'Enter') buscarCliente();
    });

    document.addEventListener('click', e => {
      if (!e.target.closest('#sugerencias-cliente') &&
          !e.target.closest('#cliente-buscar') &&
          !e.target.closest('#btn-buscar-cliente')) {
        document.getElementById('sugerencias-cliente').style.display = 'none';
      }
    });

    document.getElementById('modal-checkout').addEventListener('click', e => {
      if (e.target === document.getElementById('modal-checkout')) cerrarCheckout();
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return {
    mostrarInfo, cerrarInfo,
    agregarAlCarrito, agregarDesdeInfo,
    cambiarCantidad, eliminarItem,
    seleccionarMetodo, seleccionarCliente, limpiarCliente
  };
})();
