// ventas.js - Canetto Sales Module

const VentasApp = (() => {
  let carrito = [];
  let clienteSeleccionado    = null;
  let metodoPagoSeleccionado = null;
  let productoActivo = null;
  let stockDetalle   = {};
  let toppingsActivos = [];   // [{id, nombre, precio}] seleccionados en el panel info

  // ─── UTILS ────────────────────────────────
  const fmt = n => '$' + Math.round(parseFloat(n)).toLocaleString('es-AR');

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
      const data = await fetch('api/get_productos.php').then(r => r.json());
      if (data.error) throw new Error(data.error);
      data.forEach(p => {
        stockDetalle[p.idproductos] = {
          congelado: parseFloat(p.stock_congelado ?? 0),
          hecho:     parseFloat(p.stock_hecho ?? p.stock ?? 0),
          tipo:      p.tipo || 'galletita',
        };
      });
      renderProductos(data);
    } catch (e) {
      document.getElementById('productos-grid').innerHTML =
        `<p style="color:#c0392b;padding:20px;">Error: ${e.message}</p>`;
    }
  }

  const EMOJIS_MAP = [
    ['cookie','galleta'],    '🍪',
    ['alfajor'],             '🍫',
    ['torta','cake'],        '🎂',
    ['muffin','cupcake'],    '🧁',
    ['croissant','medialuna'],'🥐',
    ['factura','masita'],    '🥮',
    ['pan'],                 '🍞',
    ['tarta','pie'],         '🥧',
    ['dona','donut'],        '🍩',
    ['box','degustacion','navidad','san valentin','premium','clásico','especial'], '🎁',
  ];
  const EMOJIS_FB = ['🍪','🧁','🎂','🥐','🍩','🥧','🍰','🥮'];

  function getEmoji(nombre, tipo) {
    if (tipo === 'box') return '🎁';
    const n = nombre.toLowerCase();
    for (let i = 0; i < EMOJIS_MAP.length; i += 2) {
      if (EMOJIS_MAP[i].some(k => n.includes(k))) return EMOJIS_MAP[i + 1];
    }
    let hash = 0;
    for (let c of nombre) hash = (hash * 31 + c.charCodeAt(0)) & 0xffff;
    return EMOJIS_FB[hash % EMOJIS_FB.length];
  }

  function renderProductos(productos) {
    const grid  = document.getElementById('productos-grid');
    const boxes = productos.filter(p => p.tipo === 'box');
    const cooks = productos.filter(p => p.tipo !== 'box');

    function cardHTML(p) {
      const emoji  = getEmoji(p.nombre, p.tipo);
      const stock  = parseFloat(p.stock_hecho ?? 0);
      const agotado = stock <= 0;
      return `
        <div class="producto-card ${agotado ? 'agotado' : ''}"
             data-id="${p.idproductos}"
             data-nombre="${p.nombre}"
             data-precio="${p.precio}"
             data-tipo="${p.tipo || 'galletita'}"
             data-emoji="${emoji}"
             data-toppings="${p.tiene_toppings > 0 ? '1' : '0'}"
             onclick="VentasApp.mostrarInfo(this)">
          ${p.imagen ? `<img class="producto-img" src="${p.imagen}" alt="${p.nombre}" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
          <span class="producto-icon" style="display:none">${emoji}</span>`
          : `<span class="producto-icon">${emoji}</span>`}
          <div class="producto-nombre">${p.nombre}</div>
          <div class="producto-precio">${fmt(p.precio)}</div>
          <div class="producto-stock ${agotado ? 'sin-stock' : ''}">
            ${agotado ? '⛔ Sin stock' : `Stock: ${stock.toFixed(0)}`}
          </div>
          ${p.tiene_toppings > 0 ? '<div class="producto-topping-badge">✨ con extras</div>' : ''}
        </div>`;
    }

    let html = '';
    if (boxes.length) {
      html += `<div class="productos-section-header">📦 Boxes</div>
               <div class="productos-section">${boxes.map(cardHTML).join('')}</div>`;
    }
    if (cooks.length) {
      html += `<div class="productos-section-header">🍪 Galletitas</div>
               <div class="productos-section">${cooks.map(cardHTML).join('')}</div>`;
    }
    grid.innerHTML = html || '<p style="padding:20px;color:#888">No hay productos</p>';

    document.getElementById('buscar-producto').addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.producto-card').forEach(c => {
        c.style.display = c.dataset.nombre.toLowerCase().includes(q) ? '' : 'none';
      });
      document.querySelectorAll('.productos-section-header').forEach(h => {
        const sec = h.nextElementSibling;
        const visible = sec && [...sec.querySelectorAll('.producto-card')].some(c => c.style.display !== 'none');
        h.style.display = visible ? '' : 'none';
      });
    });
  }

  // ─── PANEL INFO PRODUCTO ──────────────────
  async function mostrarInfo(el) {
    document.querySelectorAll('.producto-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');

    const id     = el.dataset.id;
    const nombre = el.dataset.nombre;
    const precio = parseFloat(el.dataset.precio);
    const emoji  = el.dataset.emoji;
    const tipo   = el.dataset.tipo;

    productoActivo  = { id, nombre, precio, emoji, tipo, el };
    toppingsActivos = [];

    document.getElementById('info-emoji').textContent  = emoji;
    document.getElementById('info-nombre').textContent = nombre;
    document.getElementById('info-precio').textContent = fmt(precio);

    const sd = stockDetalle[id] || { congelado: 0, hecho: 0 };
    document.getElementById('val-congelado').textContent   = sd.congelado.toFixed(0);
    document.getElementById('val-hecho').textContent       = sd.hecho.toFixed(0);
    document.getElementById('info-total-val').textContent  = (sd.congelado + sd.hecho).toFixed(0);

    // Cargar toppings si aplica
    const toppingsWrap  = document.getElementById('info-toppings-wrap');
    const toppingsList  = document.getElementById('info-toppings-list');
    const precioTotalW  = document.getElementById('info-precio-total-wrap');

    if (el.dataset.toppings === '1') {
      toppingsWrap.style.display = '';
      toppingsList.innerHTML = '<span style="color:#888;font-size:.78rem">Cargando extras...</span>';
      try {
        const tops = await fetch('api/get_toppings_producto.php?producto_id=' + id).then(r => r.json());
        renderToppings(tops, precio);
        precioTotalW.style.display = '';
      } catch(e) {
        toppingsList.innerHTML = '<span style="color:#c0392b;font-size:.78rem">Error al cargar extras</span>';
      }
    } else {
      toppingsWrap.style.display = 'none';
      precioTotalW.style.display = 'none';
    }

    document.getElementById('producto-info-panel').style.display = 'flex';
    document.querySelector('.ventas-wrapper').classList.add('info-visible');
  }

  function renderToppings(tops, precioBase) {
    const list = document.getElementById('info-toppings-list');
    if (!tops.length) {
      list.innerHTML = '<span style="color:#888;font-size:.78rem">No hay extras disponibles</span>';
      document.getElementById('info-toppings-wrap').style.display = 'none';
      document.getElementById('info-precio-total-wrap').style.display = 'none';
      return;
    }
    list.innerHTML = tops.map(t => `
      <div class="topping-toggle" id="tt-${t.idtoppings}"
           data-id="${t.idtoppings}" data-nombre="${t.nombre}" data-precio="${t.precio}"
           onclick="VentasApp.toggleTopping(this, ${parseFloat(precioBase)})">
        <span class="tt-icon">✨</span>
        <span class="tt-nombre">${t.nombre}</span>
        <span class="tt-precio">+${fmt(t.precio)}</span>
        <span class="tt-check">○</span>
      </div>
    `).join('');
    actualizarPrecioConToppings(precioBase);
  }

  function toggleTopping(el, precioBase) {
    const id     = el.dataset.id;
    const nombre = el.dataset.nombre;
    const precio = parseFloat(el.dataset.precio);
    const idx    = toppingsActivos.findIndex(t => t.id === id);

    if (idx > -1) {
      toppingsActivos.splice(idx, 1);
      el.classList.remove('selected');
      el.querySelector('.tt-check').textContent = '○';
    } else {
      toppingsActivos.push({ id, nombre, precio });
      el.classList.add('selected');
      el.querySelector('.tt-check').textContent = '✓';
    }
    actualizarPrecioConToppings(precioBase);
  }

  function actualizarPrecioConToppings(precioBase) {
    const sumTops  = toppingsActivos.reduce((s, t) => s + t.precio, 0);
    const total    = precioBase + sumTops;
    const sumEl    = document.getElementById('info-toppings-sum');
    const totalEl  = document.getElementById('info-precio-total');
    const totalWrap = document.getElementById('info-toppings-total');

    if (sumEl) sumEl.textContent = fmt(sumTops);
    if (totalEl) totalEl.textContent = fmt(total);
    if (totalWrap) totalWrap.style.display = toppingsActivos.length ? '' : 'none';
  }

  function cerrarInfo() {
    document.getElementById('producto-info-panel').style.display = 'none';
    document.querySelector('.ventas-wrapper').classList.remove('info-visible');
    document.querySelectorAll('.producto-card').forEach(c => c.classList.remove('active'));
    productoActivo  = null;
    toppingsActivos = [];
  }

  // ─── ANIMACIÓN ────────────────────────────
  function animarCookieAlCarrito(fromEl) {
    const cookie  = document.getElementById('cookie-fly');
    const carPanel = document.querySelector('.carrito-panel');
    const fromRect = fromEl.getBoundingClientRect();
    const toRect   = carPanel.getBoundingClientRect();

    cookie.textContent = fromEl.dataset?.emoji || '🍪';
    cookie.style.cssText = `display:block;left:${fromRect.left+fromRect.width/2-16}px;top:${fromRect.top+fromRect.height/2-16}px;position:fixed;font-size:2rem;z-index:9999;pointer-events:none;transition:none;`;
    cookie.offsetHeight;
    cookie.style.transition = 'left .55s cubic-bezier(.4,0,.2,1),top .55s cubic-bezier(.4,0,.2,1),transform .55s ease,opacity .55s ease';
    cookie.style.left      = (toRect.left + 30) + 'px';
    cookie.style.top       = (toRect.top  + 60) + 'px';
    cookie.style.transform = 'scale(0.25) rotate(360deg)';
    cookie.style.opacity   = '0';
    setTimeout(() => { cookie.style.display='none'; cookie.style.transform=''; cookie.style.opacity=''; }, 580);
  }

  // ─── CARRITO ──────────────────────────────
  function agregarAlCarrito(el) {
    const id      = el.dataset.id;
    const nombre  = el.dataset.nombre;
    const precio  = parseFloat(el.dataset.precio);
    const emoji   = el.dataset.emoji;
    const tops    = [...toppingsActivos];
    const sumTops = tops.reduce((s, t) => s + t.precio, 0);
    const precioFinal = precio + sumTops;

    const sd = stockDetalle[id] || { congelado: 0, hecho: 0 };
    if (sd.hecho <= 0) {
      Swal.fire({ icon:'warning', title:'Sin stock disponible',
        html:`<b>${emoji} ${nombre}</b> no tiene stock hecho disponible.`,
        confirmButtonColor:'#c88e99', confirmButtonText:'Entendido' });
      return;
    }

    // Clave única: id + toppings para permitir mismo producto con distintos extras
    const key = id + '-' + tops.map(t=>t.id).sort().join(',');
    const idx = carrito.findIndex(i => i.key === key);
    if (idx > -1) {
      carrito[idx].cantidad++;
    } else {
      carrito.push({ key, id, nombre, precio: precioFinal, precioBase: precio, emoji, cantidad: 1, toppings: tops });
    }

    renderCarrito();
    animarCookieAlCarrito(el);
    el.style.transform = 'scale(0.93)';
    setTimeout(() => { el.style.transform = ''; }, 160);
  }

  function agregarDesdeInfo() {
    if (!productoActivo) return;
    const sd = stockDetalle[productoActivo.id] || { congelado: 0, hecho: 0 };
    if (sd.hecho <= 0) {
      Swal.fire({ icon:'warning', title:'Sin stock disponible',
        html:`<b>${productoActivo.emoji} ${productoActivo.nombre}</b> no tiene stock hecho.`,
        confirmButtonColor:'#c88e99', confirmButtonText:'Entendido' });
      return;
    }
    agregarAlCarrito(productoActivo.el);
  }

  function cambiarCantidad(key, delta) {
    const idx = carrito.findIndex(i => i.key === key);
    if (idx === -1) return;
    carrito[idx].cantidad += delta;
    if (carrito[idx].cantidad <= 0) carrito.splice(idx, 1);
    renderCarrito();
  }

  function eliminarItem(key) {
    carrito = carrito.filter(i => i.key !== key);
    renderCarrito();
  }

  function renderCarrito() {
    const container = document.getElementById('carrito-items');
    const footer    = document.getElementById('carrito-footer');
    const countEl  = document.getElementById('carrito-count');

    const total      = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const totalItems = carrito.reduce((s, i) => s + i.cantidad, 0);
    countEl.textContent = totalItems + ' item' + (totalItems !== 1 ? 's' : '');

    if (!carrito.length) {
      container.innerHTML = `<div class="carrito-empty"><div class="empty-anim"><div class="caja-icon">📦</div></div><p>El carrito está vacío</p><small>Hacé click en un producto para agregarlo</small></div>`;
      footer.style.display = 'none';
      return;
    }

    container.innerHTML = carrito.map(item => {
      const topsHtml = item.toppings?.length
        ? `<div class="item-toppings">${item.toppings.map(t=>`<span class="item-top-tag">✨ ${t.nombre}</span>`).join('')}</div>`
        : '';
      const precioDisplay = item.toppings?.length
        ? `${fmt(item.precioBase)} <span class="item-top-extra">+${fmt(item.precio - item.precioBase)} extras</span>`
        : fmt(item.precio);
      return `
        <div class="carrito-item" id="item-${item.key.replace(/,/g,'-')}">
          <span class="item-emoji">${item.emoji}</span>
          <div class="item-info">
            <div class="item-nombre">${item.nombre}</div>
            <div class="item-precio-unit">${precioDisplay} c/u</div>
            ${topsHtml}
          </div>
          <div class="item-controls">
            <button class="qty-btn minus" onclick="VentasApp.cambiarCantidad('${item.key}', -1)">−</button>
            <span class="qty-val">${item.cantidad}</span>
            <button class="qty-btn" onclick="VentasApp.cambiarCantidad('${item.key}', 1)">+</button>
          </div>
          <span class="item-subtotal">${fmt(item.precio * item.cantidad)}</span>
          <button class="btn-remove-item" onclick="VentasApp.eliminarItem('${item.key}')" title="Quitar">✕</button>
        </div>`;
    }).join('');

    document.getElementById('subtotal-val').textContent = fmt(total);
    document.getElementById('total-val').textContent    = fmt(total);
    footer.style.display = 'block';
  }

  // ─── CHECKOUT ─────────────────────────────
  function abrirCheckout() {
    if (!carrito.length) { showToast('Agregá productos primero', 'error'); return; }
    renderResumen();
    cargarMetodosPago();
    limpiarCliente();
    document.getElementById('modal-checkout').style.display = 'flex';
  }

  function cerrarCheckout() {
    document.getElementById('modal-checkout').style.display = 'none';
  }

  function renderResumen() {
    const total = carrito.reduce((s, i) => s + i.precio * i.cantidad, 0);
    document.getElementById('resumen-items').innerHTML = carrito.map(i => {
      const topsHtml = i.toppings?.length
        ? `<div style="font-size:.75rem;color:#b45309;margin-top:2px">${i.toppings.map(t=>`✨ ${t.nombre} (+${fmt(t.precio)})`).join(' · ')}</div>`
        : '';
      return `<div class="resumen-item">
        <div><span>${i.emoji} ${i.nombre} ×${i.cantidad}</span>${topsHtml}</div>
        <span>${fmt(i.precio * i.cantidad)}</span>
      </div>`;
    }).join('');
    document.getElementById('resumen-total').textContent = fmt(total);
  }

  async function cargarMetodosPago() {
    try {
      const data = await fetch('api/get_metodos_pago.php').then(r => r.json());
      document.getElementById('metodos-pago').innerHTML = data.map(m => `
        <button class="metodo-btn" data-id="${m.idmetodo_pago}"
                onclick="VentasApp.seleccionarMetodo(this, ${m.idmetodo_pago})">
          ${iconMetodo(m.nombre)} ${m.nombre}
        </button>`).join('');
    } catch (e) {
      document.getElementById('metodos-pago').innerHTML = '<p style="color:#c0392b;font-size:.85rem">Error al cargar métodos</p>';
    }
  }

  function iconMetodo(n) {
    const l = n.toLowerCase();
    if (l.includes('efectivo'))  return '💵';
    if (l.includes('mercado'))   return '💙';
    if (l.includes('transfer'))  return '🏦';
    if (l.includes('tarjeta'))   return '💳';
    return '💰';
  }

  function seleccionarMetodo(el, id) {
    document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    metodoPagoSeleccionado = id;
  }

  // ─── CLIENTE ──────────────────────────────
  async function buscarCliente() {
    const q = document.getElementById('cliente-buscar').value.trim();
    if (!q) { showToast('Ingresá un DNI o nombre', 'error'); return; }
    try {
      const data = await fetch('api/buscar_cliente.php?q=' + encodeURIComponent(q)).then(r => r.json());
      const dd = document.getElementById('sugerencias-cliente');
      if (!data.length) { dd.style.display='none'; showToast('No encontrado. Completá los datos.', ''); return; }
      dd.innerHTML = data.map(c => `
        <div class="sugerencia-item" onclick="VentasApp.seleccionarCliente(${JSON.stringify(c).replace(/"/g,'&quot;')})">
          <strong>${c.nombre} ${c.apellido||''}</strong>
          <div class="sub">🪪 DNI: ${c.dni||'—'} · 📱 ${c.celular||'—'}</div>
        </div>`).join('');
      dd.style.display = 'block';
    } catch (e) { showToast('Error al buscar cliente', 'error'); }
  }

  function seleccionarCliente(c) {
    clienteSeleccionado = c;
    document.getElementById('sugerencias-cliente').style.display  = 'none';
    document.getElementById('form-cliente-datos').style.display   = 'none';
    document.getElementById('cliente-badge-nombre').textContent   = `${c.nombre} ${c.apellido||''} · DNI ${c.dni||'—'}`;
    document.getElementById('cliente-encontrado-badge').style.display = 'flex';
    showToast('✅ Cliente: ' + c.nombre, 'success');
  }

  function limpiarCliente() {
    clienteSeleccionado = null; metodoPagoSeleccionado = null;
    document.getElementById('form-cliente-datos').style.display       = 'block';
    document.getElementById('cliente-encontrado-badge').style.display = 'none';
    document.getElementById('sugerencias-cliente').style.display      = 'none';
    document.getElementById('cliente-buscar').value = '';
    ['cliente-nombre','cliente-apellido','cliente-dni','cliente-celular'].forEach(id => {
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

    // Recolectar todos los toppings únicos del carrito
    const allToppings = [];
    const seenTop = new Set();
    carrito.forEach(item => {
      (item.toppings || []).forEach(t => {
        if (!seenTop.has(t.id)) { seenTop.add(t.id); allToppings.push(t); }
      });
    });

    const payload = {
      carrito:     carrito.map(i => ({ id: i.id, cantidad: i.cantidad, precio: i.precio, nombre: i.nombre, toppings: i.toppings || [] })),
      cliente:     clienteData,
      metodo_pago: metodoPagoSeleccionado,
      total:       carrito.reduce((s, i) => s + i.precio * i.cantidad, 0),
      toppings:    allToppings,
    };

    btnF.disabled = true; btnF.textContent = '⏳ Procesando...';

    try {
      const data = await fetch('api/crear_venta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(r => r.json());

      if (data.success) {
        cerrarCheckout();
        carrito = [];
        clienteSeleccionado = null; metodoPagoSeleccionado = null;
        renderCarrito();
        showToast('🎉 Venta #' + data.id_venta + ' creada', 'success');
      } else {
        showToast('Error: ' + (data.message || 'No se pudo crear'), 'error');
      }
    } catch (e) {
      showToast('Error de conexión', 'error');
    } finally {
      btnF.disabled = false; btnF.textContent = '✅ Confirmar Venta';
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
    document.getElementById('cliente-buscar').addEventListener('keydown', e => { if (e.key==='Enter') buscarCliente(); });
    document.addEventListener('click', e => {
      if (!e.target.closest('#sugerencias-cliente') && !e.target.closest('#cliente-buscar') && !e.target.closest('#btn-buscar-cliente'))
        document.getElementById('sugerencias-cliente').style.display = 'none';
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
    seleccionarMetodo, seleccionarCliente, limpiarCliente,
    toggleTopping,
  };
})();
