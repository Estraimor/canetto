// pedidos.js - Canetto

const PedidosApp = (() => {
  const ESTADOS = {
    1: { label: 'Pendiente',           cls: 'est-1' },
    2: { label: 'En Preparación',      cls: 'est-2' },
    3: { label: 'En reparto',          cls: 'est-3' },
    4: { label: 'Entregado',           cls: 'est-4' },
    5: { label: 'Pendiente de Pago',   cls: 'est-5' },
    6: { label: 'Cancelado',           cls: 'est-6' },
    7: { label: 'Listo para retiro',   cls: 'est-7' }
  };

  // Todos los estados disponibles (sin Cancelado, que tiene su propio botón)
  function getTransiciones(estadoId, tipoEntrega) {
    return [1, 2, 5, 7, 3, 4];
  }

  const fmt = n => '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  function fmtFecha(str) {
    if (!str) return { dia: '—', hora: '' };
    const d    = new Date(str);
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

  // ─── CARGAR ───────────────────────────────
  async function cargarPedidos() {
    const estado = document.getElementById('filtro-estado').value;
    const fecha  = document.getElementById('filtro-fecha').value;
    const origen = document.getElementById('filtro-origen')?.value || '';

    const params = new URLSearchParams();
    if (estado) params.set('estado', estado);
    if (fecha)  params.set('fecha', fecha);
    if (origen) params.set('origen', origen);

    document.getElementById('pedidos-tbody').innerHTML =
      '<tr><td colspan="8" class="loading-row">⏳ Cargando pedidos...</td></tr>';

    try {
      const res  = await fetch('api/get_pedidos.php?' + params.toString());
      const data = await res.json();
      if (data.error) {
        document.getElementById('pedidos-tbody').innerHTML =
          `<tr><td colspan="8" class="loading-row" style="color:#c0392b;">Error: ${data.error}</td></tr>`;
        return;
      }
      renderPedidos(data.pedidos || []);
      renderStats(data.stats   || {});
    } catch (e) {
      document.getElementById('pedidos-tbody').innerHTML =
        `<tr><td colspan="8" class="loading-row" style="color:#c0392b;">Error: ${e.message}</td></tr>`;
    }
  }

  function filtrarPorEstado(id) {
    document.getElementById('filtro-estado').value = id;
    document.querySelectorAll('.stat-pill[data-estado]').forEach(p => {
      p.classList.toggle('active', parseInt(p.dataset.estado) === id);
    });
    cargarPedidos();
  }

  function renderStats(stats) {
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || 0; };
    set('count-1', stats.pendiente);
    set('count-2', stats.preparacion);
    set('count-3', stats.repartidor);
    set('count-5', stats.pend_pago);
    set('count-7', stats.listo_retiro);
    document.getElementById('total-hoy').textContent = fmt(stats.total_hoy || 0);
  }

  function renderPedidos(pedidos) {
    const tbody = document.getElementById('pedidos-tbody');
    if (!pedidos.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="loading-row">No hay pedidos activos</td></tr>';
      return;
    }

    tbody.innerHTML = pedidos.map(v => {
      const fecha      = fmtFecha(v.fecha);
      const estadoId   = parseInt(v.estado_id);
      const esCancelado = estadoId === 6;
      const est        = ESTADOS[estadoId] || ESTADOS[1];

      const productos = [
        ...(v.productos || []).map(p =>
          `<span class="prod-tag">${p.nombre} ×${p.cantidad}</span>`
        ),
        ...(v.toppings || []).map(t =>
          `<span class="prod-tag prod-tag--topping">✨ ${t.nombre}</span>`
        )
      ].join('');

      const tipoEntrega  = v.tipo_entrega || 'retiro';
      const esEnvio      = tipoEntrega === 'envio';
      const badgeEntrega = esEnvio
        ? `<span class="badge-envio">🛵 Envío</span>`
        : `<span class="badge-retiro">🏪 Retiro</span>`;
      const badgeOrigen  = v.origen === 'tienda'
        ? `<span class="badge-origen badge-app">📱 App</span>`
        : `<span class="badge-origen badge-pos">🖥 Admin</span>`;
      const repInfo = v.via_uber
        ? `<small style="color:#7c3aed">🚗 Uber</small>`
        : (v.repartidor_nombre ? `<small style="color:#f97316">🛵 ${v.repartidor_nombre}</small>` : '');

      const rowCls = `row-estado-${estadoId}` + (esCancelado ? ' row-cancelado' : '');

      const trans      = getTransiciones(estadoId, tipoEntrega);
      const selectOpts = trans.map(id => {
        const e = ESTADOS[id];
        if (!e) return '';
        return `<option value="${id}" ${estadoId === id ? 'selected' : ''}>${e.label}</option>`;
      }).join('');

      return `
        <tr id="row-${v.idventas}" data-tipo-entrega="${tipoEntrega}" class="${rowCls}">
          <td>
            <span class="venta-id">#${v.idventas}</span><br>
            ${badgeEntrega}${badgeOrigen}
            ${esCancelado ? '<br><span class="badge-cancelado">✖ CANCELADO</span>' : ''}
          </td>
          <td>
            <div class="cliente-info">
              <strong>${v.cliente_nombre || 'Sin nombre'}</strong>
              <small>${String(v.cliente_telefono || '') || v.cliente_email || '—'}</small>
              ${repInfo}
            </div>
          </td>
          <td><div class="productos-mini">${productos || '<span class="prod-tag">—</span>'}</div></td>
          <td><span class="total-cell">${fmt(v.total)}</span></td>
          <td><span class="pago-badge">${v.metodo_pago || '—'}</span></td>
          <td class="fecha-cell"><strong>${fecha.dia}</strong>${fecha.hora}</td>
          <td>
            ${esCancelado
              ? `<span class="estado-pill est-6"><span class="est-dot"></span>Cancelado</span>`
              : `<select class="estado-select estado-bg-${estadoId}" id="estado-select-${v.idventas}"
                         onchange="PedidosApp.onEstadoChange(${v.idventas})">
                   ${selectOpts}
                 </select>`
            }
          </td>
          <td>
            <div class="acciones-cell">
              <button class="btn-accion btn-ver" onclick="PedidosApp.verDetalle(${v.idventas})">👁 Ver</button>
              ${!esCancelado ? `
              <button class="btn-accion btn-guardar-estado" id="btn-save-${v.idventas}"
                      onclick="PedidosApp.guardarEstado(${v.idventas})" disabled>💾</button>
              <button class="btn-accion btn-cancelar-venta" onclick="PedidosApp.cancelarPedido(${v.idventas})">✖ Cancelar</button>
              ` : ''}
              ${estadoId === 3 && esEnvio ? `
              <button class="btn-accion btn-reasignar" onclick="PedidosApp.reasignarRepartidor(${v.idventas})">
                🔄 Reasignar
              </button>
              <button class="btn-accion btn-whatsapp"
                      onclick="PedidosApp.mensajeCliente('${String(v.cliente_telefono||'').replace(/\D/g,'')}', ${v.idventas})">
                💬 Mensaje
              </button>` : ''}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function onEstadoChange(idVenta) {
    const btn = document.getElementById('btn-save-' + idVenta);
    if (btn) btn.disabled = false;
  }

  async function guardarEstado(idVenta) {
    const select      = document.getElementById('estado-select-' + idVenta);
    const btn         = document.getElementById('btn-save-' + idVenta);
    const nuevoEstado = parseInt(select.value);
    const row         = document.getElementById('row-' + idVenta);
    const tipoEntrega = row?.dataset.tipoEntrega || 'retiro';

    // Para pedidos de envío que van a "En reparto": pedir repartidor
    if (tipoEntrega === 'envio' && nuevoEstado === 3) {
      await abrirModalRepartidor(idVenta);
      return;
    }
    // Para pedidos de envío en CUALQUIER otro estado: ofrecer también cambiar repartidor
    if (tipoEntrega === 'envio' && nuevoEstado !== 3) {
      await ejecutarCambio(idVenta, nuevoEstado, null, btn);
      // Mostrar opción de actualizar repartidor sin bloquear
      const row2 = document.getElementById('row-' + idVenta);
      if (row2) {
        const repBtn = document.createElement('button');
        repBtn.className = 'btn-accion btn-reasignar';
        repBtn.style.cssText = 'margin-top:6px;font-size:11px';
        repBtn.textContent = '🔄 Actualizar reparto';
        repBtn.onclick = () => { repBtn.remove(); PedidosApp.reasignarRepartidor(idVenta); };
        const acciones = row2.querySelector('.acciones-cell');
        if (acciones && !acciones.querySelector('.btn-reasignar')) acciones.appendChild(repBtn);
      }
      return;
    }
    await ejecutarCambio(idVenta, nuevoEstado, null, btn);
  }

  async function reasignarRepartidor(idVenta) {
    await abrirModalRepartidor(idVenta, true);
  }

  async function cancelarPedido(idVenta) {
    const ok = await Swal.fire({
      title: '¿Cancelar este pedido?',
      text: 'Esta acción quedará registrada y no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e53e3e',
      cancelButtonColor: '#718096',
      confirmButtonText: 'Sí, cancelar',
      cancelButtonText: 'No'
    });
    if (!ok.isConfirmed) return;
    const btn = { disabled: false, textContent: '' };
    await ejecutarCambio(idVenta, 6, null, btn);
  }

  async function ejecutarCambio(idVenta, nuevoEstado, repartidorId, btn, viaUber = false, tipoEntrega = null) {
    if (btn) { btn.disabled = true; btn.textContent = '⏳'; }

    try {
      const body = { id_venta: idVenta, estado: nuevoEstado };
      if (repartidorId)  body.repartidor_id = repartidorId;
      if (viaUber)       body.via_uber = true;
      if (tipoEntrega)   body.tipo_entrega = tipoEntrega;

      const res  = await fetch('api/actualizar_pedido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const data = await res.json();

      if (data.success) {
        showToast('✅ Estado actualizado', 'success');
        cargarPedidos();
      } else {
        showToast('Error: ' + (data.message || 'No se pudo actualizar'), 'error');
        if (btn) { btn.disabled = false; btn.textContent = '💾'; }
      }
    } catch (e) {
      showToast('Error de conexión', 'error');
      if (btn) { btn.disabled = false; btn.textContent = '💾'; }
    }
  }

  // ─── MODAL REPARTIDOR ─────────────────────
  let _repVentaId = null;

  async function abrirModalRepartidor(idVenta, soloReasignar = false) {
    _repVentaId = idVenta;
    const modal = document.getElementById('modal-repartidor');
    const sel   = document.getElementById('rep-select');
    const info  = document.getElementById('rep-cliente-info');
    sel.innerHTML  = '<option value="">Cargando...</option>';
    info.innerHTML = '';
    // Reset siempre a "envio" al abrir
    if (typeof setTipoEntregaModal === 'function') setTipoEntregaModal('envio');
    modal.style.display = 'flex';

    try {
      const [reps, detalle] = await Promise.all([
        fetch('api/get_repartidores.php').then(r => r.json()),
        fetch('api/get_detalle.php?id=' + idVenta).then(r => r.json())
      ]);
      const uberOpt = '<option value="uber">🚗 Uber — sin repartidor propio</option>';
      sel.innerHTML = '<option value="">— Elegí cómo se envía —</option>' +
        (reps.map ? reps.map(r => `<option value="${r.idrepartidor}">${r.nombre} ${r.apellido || ''} ${r.celular ? '('+r.celular+')' : ''}</option>`).join('') : '') +
        uberOpt;
      const dir = detalle.direccion_entrega || '—';
      const tel = String(detalle.cliente_telefono || '—');
      info.innerHTML = `<div class="rep-modal-info">
        <div><strong>Cliente:</strong> ${detalle.cliente_nombre} ${detalle.cliente_apellido || ''}</div>
        <div><strong>Teléfono:</strong> ${tel}</div>
        <div><strong>Dirección:</strong> ${dir}</div>
      </div>`;
    } catch (e) {
      sel.innerHTML = '<option value="">Error al cargar repartidores</option>';
    }
  }

  async function confirmarRepartidor() {
    const ventaId  = _repVentaId;
    const tipoModal = (typeof _modalTipoEntrega !== 'undefined') ? _modalTipoEntrega : 'envio';

    if (tipoModal === 'retiro') {
      // Cambiar a retiro en local → estado 7 "Listo para retiro"
      cerrarModalRep();
      const btn = document.getElementById('btn-save-' + ventaId) || { disabled: false, textContent: '' };
      await ejecutarCambio(ventaId, 7, null, btn, false, 'retiro');
      return;
    }

    // Envío a domicilio → requiere repartidor
    const sel = document.getElementById('rep-select');
    if (!sel.value) { alert('Seleccioná un repartidor o elegí Uber'); return; }
    const viaUber = sel.value === 'uber';
    const repId   = viaUber ? null : parseInt(sel.value);
    cerrarModalRep();
    const btn = document.getElementById('btn-save-' + ventaId) || { disabled: false, textContent: '' };
    await ejecutarCambio(ventaId, 3, repId, btn, viaUber, 'envio');
  }

  function cerrarModalRep() {
    document.getElementById('modal-repartidor').style.display = 'none';
    _repVentaId = null;
  }

  // ─── DETALLE ──────────────────────────────
  async function verDetalle(idVenta) {
    document.getElementById('detalle-title').textContent = 'Detalle Pedido #' + idVenta;
    document.getElementById('detalle-body').innerHTML = '<p style="text-align:center;padding:30px;color:#7a7a7a">⏳ Cargando...</p>';
    document.getElementById('modal-detalle').style.display = 'flex';
    try {
      const data = await fetch('api/get_detalle.php?id=' + idVenta).then(r => r.json());
      renderDetalle(data);
    } catch (e) {
      document.getElementById('detalle-body').innerHTML = '<p style="color:#c0392b;padding:20px;">Error al cargar detalle</p>';
    }
  }

  function renderDetalle(d) {
    const estadoId = parseInt(d.estado_id);
    const est = ESTADOS[estadoId] || { label: 'Desconocido', cls: 'est-1' };
    const fecha = fmtFecha(d.fecha);
    document.getElementById('detalle-body').innerHTML = `
      <div class="detalle-section">
        <h4>📦 Estado actual</h4>
        <span class="estado-pill ${est.cls}"><span class="est-dot"></span>${est.label}</span>
      </div>
      <div class="detalle-section">
        <h4>👤 Cliente</h4>
        <div class="detalle-info-grid">
          <div class="info-item"><label>Nombre</label><span>${d.cliente_nombre || '—'} ${d.cliente_apellido || ''}</span></div>
          <div class="info-item"><label>Teléfono</label><span>${String(d.cliente_telefono || '—')}</span></div>
          <div class="info-item"><label>Email</label><span>${d.cliente_email || '—'}</span></div>
          ${d.tipo_entrega === 'envio' ? `
          <div class="info-item"><label>Tipo entrega</label><span>🛵 Envío</span></div>
          <div class="info-item"><label>Dirección</label><span>${d.direccion_entrega || '—'}</span></div>
          <div class="info-item"><label>Repartidor</label><span>${d.via_uber ? '🚗 Uber' : (d.repartidor_nombre || '— Sin asignar —')}</span></div>
          ` : `<div class="info-item"><label>Tipo entrega</label><span>🏪 Retiro</span></div>`}
        </div>
      </div>
      <div class="detalle-section">
        <h4>💳 Pago & Fecha</h4>
        <div class="detalle-info-grid">
          <div class="info-item"><label>Método</label><span>${d.metodo_pago || '—'}</span></div>
          <div class="info-item"><label>Fecha</label><span>${fecha.dia} ${fecha.hora}</span></div>
          <div class="info-item"><label>Origen</label><span>${d.origen === 'tienda' ? '📱 App/Tienda' : '🖥 Administración'}</span></div>
        </div>
      </div>
      <div class="detalle-section">
        <h4>🛍️ Productos</h4>
        <div class="detalle-items-list">
          ${(d.productos || []).map(p => {
            const boxHtml = p.contenido_box
              ? `<div class="di-box">📦 ${p.contenido_box}</div>` : '';
            return `
            <div class="detalle-item">
              <div style="flex:1">
                <div class="di-nombre">${p.tipo === 'box' ? '📦' : '🍪'} ${p.nombre}</div>
                <div class="di-qty">Cantidad: ${p.cantidad}</div>
                ${boxHtml}
              </div>
              <div class="di-precio">${fmt(p.precio_unitario * p.cantidad)}</div>
            </div>`;
          }).join('')}
          ${(d.toppings || []).map(t => `
            <div class="detalle-item detalle-item--topping">
              <div style="flex:1">
                <div class="di-nombre">✨ ${t.nombre}</div>
                <div class="di-qty">Extra / Topping</div>
              </div>
              <div class="di-precio di-precio--topping">+${fmt(t.precio)}</div>
            </div>`).join('')}
        </div>
        <div class="detalle-total"><span>Total</span><span>${fmt(d.total)}</span></div>
      </div>
    `;
  }

  function cerrarDetalle() {
    document.getElementById('modal-detalle').style.display = 'none';
  }

  function mensajeCliente(telefono, idVenta) {
    if (!telefono) { showToast('Sin número de teléfono registrado', 'error'); return; }
    let num = telefono.replace(/\D/g, '');
    if (num.startsWith('0')) num = num.slice(1);
    if (!num.startsWith('54')) num = '54' + num;
    const msg = encodeURIComponent(`¡Hola! 👋 Te escribimos desde Canetto. Tu pedido #${idVenta} está en camino. ¡Gracias por elegirnos! 🍪`);
    window.open(`https://wa.me/${num}?text=${msg}`, '_blank');
  }

  // ─── AUTO-REFRESH cada 30s ─────────────────
  function init() {
    cargarPedidos();
    setInterval(cargarPedidos, 30000);
    document.getElementById('modal-detalle').addEventListener('click', e => {
      if (e.target === document.getElementById('modal-detalle')) cerrarDetalle();
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return { cargarPedidos, filtrarPorEstado, guardarEstado, onEstadoChange, verDetalle, cerrarDetalle, mensajeCliente, cancelarPedido, confirmarRepartidor, cerrarModalRep, reasignarRepartidor };
})();
