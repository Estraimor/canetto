// historial.js - Canetto

const HistorialApp = (() => {
  const ESTADOS = {
    1: { label: 'Pendiente',            icon: '⏳', cls: 'estado-1' },
    2: { label: 'En Preparación',       icon: '👨‍🍳', cls: 'estado-2' },
    3: { label: 'En manos Repartidor',  icon: '🛵', cls: 'estado-3' },
    4: { label: 'Entregado',            icon: '✅', cls: 'estado-4' }
  };

  const fmt = (n) => '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  function fmtFecha(str) {
    if (!str) return '—';
    const d = new Date(str);
    const dia  = d.toLocaleDateString('es-AR', { day:'2-digit', month:'2-digit', year:'numeric' });
    const hora = d.toLocaleTimeString('es-AR', { hour:'2-digit', minute:'2-digit' });
    return { dia, hora };
  }

  function showToast(msg, type = '') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast' + (type ? ' ' + type : '');
    t.style.display = 'block';
    clearTimeout(t._timeout);
    t._timeout = setTimeout(() => { t.style.display = 'none'; }, 3000);
  }

  // ─── CARGAR VENTAS ────────────────────────
  async function cargarVentas() {
    const estado = document.getElementById('filtro-estado').value;
    const fecha  = document.getElementById('filtro-fecha').value;

    const params = new URLSearchParams();
    if (estado) params.set('estado', estado);
    if (fecha)  params.set('fecha', fecha);

    document.getElementById('ventas-tbody').innerHTML =
      '<tr><td colspan="8" class="loading-row">⏳ Cargando...</td></tr>';

    try {
      const res  = await fetch('api/get_ventas.php?' + params.toString());
      const data = await res.json();
      renderVentas(data.ventas || []);
      renderStats(data.stats || {});
    } catch (e) {
      document.getElementById('ventas-tbody').innerHTML =
        '<tr><td colspan="8" class="loading-row" style="color:#c0392b;">Error al cargar ventas</td></tr>';
    }
  }

  function filtrarPorEstado(id) {
    document.getElementById('filtro-estado').value = id;
    // toggle: si ya está activo, quitar filtro
    document.querySelectorAll('.stat-pill[data-estado]').forEach(p => {
      p.classList.toggle('active', parseInt(p.dataset.estado) === id);
    });
    cargarVentas();
  }

  function renderStats(stats) {
    document.getElementById('count-1').textContent = stats.pendiente   || 0;
    document.getElementById('count-2').textContent = stats.preparacion || 0;
    document.getElementById('count-3').textContent = stats.repartidor  || 0;
    document.getElementById('count-4').textContent = stats.entregado   || 0;
    document.getElementById('total-hoy').textContent = fmt(stats.total_hoy || 0);
  }

  function renderVentas(ventas) {
    const tbody = document.getElementById('ventas-tbody');
    if (!ventas.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="loading-row">No hay ventas para los filtros seleccionados</td></tr>';
      return;
    }

    tbody.innerHTML = ventas.map(v => {
      const est   = ESTADOS[v.estado_id] || ESTADOS[1];
      const fecha = fmtFecha(v.fecha);
      const productos = (v.productos || []).map(p =>
        `<span class="prod-tag">${p.nombre} ×${p.cantidad}</span>`
      ).join('');

      const tipoEntrega   = v.tipo_entrega || 'retiro';
      const esEnvio       = tipoEntrega === 'envio';
      const badgeEntrega  = esEnvio
        ? `<span class="badge-envio">🛵 Envío</span>`
        : `<span class="badge-retiro">🏪 Retiro</span>`;
      const repInfo = v.via_uber
        ? `<small style="color:#7c3aed">🚗 Uber</small>`
        : (v.repartidor_nombre
          ? `<small style="color:#f97316">🛵 ${v.repartidor_nombre}</small>`
          : '');

      return `
        <tr id="row-${v.idventas}" data-tipo-entrega="${tipoEntrega}">
          <td><span class="venta-id">#${v.idventas}</span><br>${badgeEntrega}</td>
          <td>
            <div class="cliente-info">
              <strong>${v.cliente_nombre || 'Sin nombre'}</strong>
              <small>${v.cliente_telefono || v.cliente_email || '—'}</small>
              ${repInfo}
            </div>
          </td>
          <td><div class="productos-mini">${productos || '<span class="prod-tag">—</span>'}</div></td>
          <td><span class="total-cell">${fmt(v.total)}</span></td>
          <td><span class="pago-badge">${v.metodo_pago || '—'}</span></td>
          <td class="fecha-cell">
            <strong>${fecha.dia}</strong>${fecha.hora}
          </td>
          <td>
            <select class="estado-select" id="estado-select-${v.idventas}" onchange="HistorialApp.onEstadoChange(${v.idventas})">
              ${Object.entries(ESTADOS).map(([id, e]) =>
                `<option value="${id}" ${parseInt(v.estado_id) === parseInt(id) ? 'selected' : ''}>${e.icon} ${e.label}</option>`
              ).join('')}
            </select>
          </td>
          <td>
            <div class="acciones-cell">
              <button class="btn-accion btn-ver" onclick="HistorialApp.verDetalle(${v.idventas})">👁 Ver</button>
              <button class="btn-accion btn-guardar-estado" id="btn-save-${v.idventas}"
                      onclick="HistorialApp.guardarEstado(${v.idventas})" disabled>
                💾
              </button>
              ${parseInt(v.estado_id) === 3 ? `
              <button class="btn-accion btn-whatsapp" title="Mandar mensaje al cliente"
                      onclick="HistorialApp.mensajeCliente('${(v.cliente_telefono||'').replace(/\D/g,'')}', ${v.idventas})">
                💬 Mensaje
              </button>` : (parseInt(v.estado_id) === 4 ? `
              <button class="btn-accion btn-whatsapp" disabled title="Pedido ya entregado" style="opacity:.4;cursor:not-allowed;">
                💬 Mensaje
              </button>` : '')}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function onEstadoChange(idVenta) {
    document.getElementById('btn-save-' + idVenta).disabled = false;
  }

  async function guardarEstado(idVenta) {
    const select      = document.getElementById('estado-select-' + idVenta);
    const btn         = document.getElementById('btn-save-' + idVenta);
    const nuevoEstado = parseInt(select.value);
    const row         = document.getElementById('row-' + idVenta);
    const tipoEntrega = row?.dataset.tipoEntrega || 'retiro';

    // Si pasa a estado 3 y el pedido es de envío → pedir repartidor
    if (nuevoEstado === 3 && tipoEntrega === 'envio') {
      await abrirModalRepartidor(idVenta);
      return;
    }

    await ejecutarCambioEstado(idVenta, nuevoEstado, null, btn);
  }

  async function ejecutarCambioEstado(idVenta, nuevoEstado, repartidorId, btn, viaUber = false) {
    if (!btn) btn = document.getElementById('btn-save-' + idVenta);
    btn.disabled    = true;
    btn.textContent = '⏳';

    try {
      const body = { id_venta: idVenta, estado: nuevoEstado };
      if (repartidorId) body.repartidor_id = repartidorId;
      if (viaUber) body.via_uber = true;

      const res  = await fetch('api/actualizar_estado.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(body),
      });
      const data = await res.json();

      if (data.success) {
        showToast('✅ Estado actualizado', 'success');
        btn.textContent = '✓';
        setTimeout(() => { btn.textContent = '💾'; }, 2000);
        cargarVentas();
      } else {
        showToast('Error: ' + (data.message || 'No se pudo actualizar'), 'error');
        btn.disabled    = false;
        btn.textContent = '💾';
      }
    } catch (e) {
      showToast('Error de conexión', 'error');
      btn.disabled    = false;
      btn.textContent = '💾';
    }
  }

  // ─── MODAL REPARTIDOR ────────────────────
  let _repVentaId = null;

  async function abrirModalRepartidor(idVenta) {
    _repVentaId = idVenta;
    const modal = document.getElementById('modal-repartidor');
    const sel   = document.getElementById('rep-select');
    const info  = document.getElementById('rep-cliente-info');

    sel.innerHTML  = '<option value="">Cargando...</option>';
    info.innerHTML = '';
    modal.style.display = 'flex';

    try {
      // Cargar repartidores y detalle del pedido en paralelo
      const [reps, detalle] = await Promise.all([
        fetch('api/get_repartidores.php').then(r => r.json()),
        fetch('api/get_detalle_venta.php?id=' + idVenta).then(r => r.json()),
      ]);

      const uberOpt = '<option value="uber">🚗 Uber — sin repartidor propio</option>';
      if (!reps.length) {
        sel.innerHTML = '<option value="">— Elegí cómo se envía —</option>' + uberOpt;
      } else {
        sel.innerHTML = '<option value="">— Elegí cómo se envía —</option>' +
          reps.map(r => `<option value="${r.idrepartidor}">${r.nombre} ${r.apellido || ''} ${r.celular ? '('+r.celular+')' : ''}</option>`).join('') +
          uberOpt;
      }

      const dir = detalle.direccion_entrega || detalle.cliente_direccion || 'Sin dirección';
      const tel = detalle.cliente_telefono  || '—';
      info.innerHTML = `
        <div class="rep-modal-info">
          <div><strong>Cliente:</strong> ${detalle.cliente_nombre} ${detalle.cliente_apellido || ''}</div>
          <div><strong>Teléfono:</strong> ${tel}</div>
          <div><strong>Dirección:</strong> ${dir}</div>
        </div>`;
    } catch (e) {
      sel.innerHTML = '<option value="">Error al cargar repartidores</option>';
    }
  }

  async function confirmarRepartidor() {
    const sel = document.getElementById('rep-select');
    if (!sel.value) { alert('Seleccioná un repartidor o elegí Uber'); return; }
    const viaUber = sel.value === 'uber';
    const repId   = viaUber ? null : parseInt(sel.value);
    const ventaId = _repVentaId;
    cerrarModalRep();
    const btn = document.getElementById('btn-save-' + ventaId);
    await ejecutarCambioEstado(ventaId, 3, repId, btn, viaUber);
  }

  function cerrarModalRep() {
    document.getElementById('modal-repartidor').style.display = 'none';
    _repVentaId = null;
  }

  // ─── DETALLE MODAL ────────────────────────
  async function verDetalle(idVenta) {
    document.getElementById('detalle-title').textContent = 'Detalle Venta #' + idVenta;
    document.getElementById('detalle-body').innerHTML = '<p style="text-align:center;padding:30px;color:var(--text-light);">⏳ Cargando...</p>';
    document.getElementById('modal-detalle').style.display = 'flex';

    try {
      const res  = await fetch('api/get_detalle_venta.php?id=' + idVenta);
      const data = await res.json();
      renderDetalle(data);
    } catch (e) {
      document.getElementById('detalle-body').innerHTML = '<p style="color:#c0392b;padding:20px;">Error al cargar detalle</p>';
    }
  }

  function renderDetalle(d) {
    const est   = ESTADOS[d.estado_id] || ESTADOS[1];
    const fecha = fmtFecha(d.fecha);
    document.getElementById('detalle-body').innerHTML = `
      <div class="detalle-section">
        <h4>📦 Estado actual</h4>
        <span class="estado-badge ${est.cls}">
          <span class="estado-dot"></span>${est.icon} ${est.label}
        </span>
      </div>

      <div class="detalle-section">
        <h4>👤 Cliente</h4>
        <div class="detalle-info-grid">
          <div class="info-item"><label>Nombre</label><span>${d.cliente_nombre || '—'} ${d.cliente_apellido || ''}</span></div>
          <div class="info-item"><label>Teléfono</label><span>${d.cliente_telefono || '—'}</span></div>
          <div class="info-item"><label>Email</label><span>${d.cliente_email || '—'}</span></div>
          <div class="info-item"><label>Dirección</label><span>${d.cliente_direccion || '—'}</span></div>
          ${d.tipo_entrega === 'envio' ? `
          <div class="info-item"><label>Tipo entrega</label><span>🛵 Envío a domicilio</span></div>
          <div class="info-item"><label>Dirección entrega</label><span>${d.direccion_entrega || '—'}</span></div>
          <div class="info-item"><label>Repartidor</label><span>${d.via_uber ? '🚗 Uber' : (d.repartidor_nombre || '— Sin asignar —')}</span></div>
          ` : `<div class="info-item"><label>Tipo entrega</label><span>🏪 Retiro en local</span></div>`}
        </div>
      </div>

      <div class="detalle-section">
        <h4>💳 Pago & Fecha</h4>
        <div class="detalle-info-grid">
          <div class="info-item"><label>Método</label><span>${d.metodo_pago || '—'}</span></div>
          <div class="info-item"><label>Fecha</label><span>${fecha.dia} ${fecha.hora}</span></div>
        </div>
      </div>

      <div class="detalle-section">
        <h4>🛍️ Productos</h4>
        <div class="detalle-items-list">
          ${(d.productos || []).map(p => `
            <div class="detalle-item">
              <div>
                <div class="di-nombre">🍪 ${p.nombre}</div>
                <div class="di-qty">Cantidad: ${p.cantidad}</div>
              </div>
              <div class="di-precio">${fmt(p.precio_unitario * p.cantidad)}</div>
            </div>
          `).join('')}
        </div>
        <div class="detalle-total">
          <span>Total</span>
          <span>${fmt(d.total)}</span>
        </div>
      </div>
    `;
  }

  function cerrarDetalle() {
    document.getElementById('modal-detalle').style.display = 'none';
  }

  // ─── WHATSAPP MENSAJE AL CLIENTE ──────────
  function mensajeCliente(telefono, idVenta) {
    if (!telefono) {
      showToast('Este cliente no tiene número de teléfono registrado', 'error');
      return;
    }
    // Normalizar número argentino: quitar 0 inicial, agregar +54
    let num = telefono.replace(/\D/g, '');
    if (num.startsWith('0')) num = num.slice(1);
    if (!num.startsWith('54')) num = '54' + num;

    const mensaje = encodeURIComponent(
      `¡Hola! 👋 Te escribimos desde Canetto. Queríamos saber si recibiste bien tu pedido #${idVenta}. ¿Todo llegó bien? ¡Gracias por elegirnos! 🍪`
    );
    window.open(`https://wa.me/${num}?text=${mensaje}`, '_blank');
  }

  // ─── INIT ─────────────────────────────────
  function init() {
    cargarVentas();

    document.getElementById('modal-detalle').addEventListener('click', (e) => {
      if (e.target === document.getElementById('modal-detalle')) cerrarDetalle();
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return { cargarVentas, filtrarPorEstado, guardarEstado, onEstadoChange, verDetalle, cerrarDetalle, mensajeCliente, confirmarRepartidor, cerrarModalRep };
})();
