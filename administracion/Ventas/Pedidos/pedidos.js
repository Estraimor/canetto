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

  // Estados disponibles según tipo de entrega
  // Retiro: no puede pasar a "En reparto" (3) — solo a "Listo para retiro" (7)
  // Envío:  no puede pasar a "Listo para retiro" (7) — solo a "En reparto" (3)
  function getTransiciones(estadoId, tipoEntrega) {
    if (tipoEntrega === 'retiro') return [1, 2, 5, 7, 4]; // sin estado 3
    return [1, 2, 5, 3, 4];                               // sin estado 7
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
        ? (v.uber_link
            ? `<small style="color:#7c3aed">🚗 <a href="${v.uber_link}" target="_blank" rel="noopener" style="color:#7c3aed;text-decoration:underline">Ver en Uber</a></small>`
            : `<small style="color:#7c3aed">🚗 Uber</small>`)
        : (v.repartidor_nombre
            ? `<small style="color:#f97316">🛵 ${v.repartidor_nombre}</small>`
            : (v.repartidor_pendiente_nombre
                ? `<small style="color:#f59e0b" id="rep-status-${v.idventas}">⏳ Esperando a repartidores...</small>`
                : (v.estado_id == 3 && v.tipo_entrega === 'envio'
                    ? `<small style="color:#94a3b8" id="rep-status-${v.idventas}">🔍 Buscando repartidor...</small>`
                    : '')));

      const rowCls = `row-estado-${estadoId}` + (esCancelado ? ' row-cancelado' : '');

      const trans      = getTransiciones(estadoId, tipoEntrega);
      const selectOpts = trans.map(id => {
        const e = ESTADOS[id];
        if (!e) return '';
        return `<option value="${id}" ${estadoId === id ? 'selected' : ''}>${e.label}</option>`;
      }).join('');

      const accionesBtns = `
        <button class="btn-accion btn-ver" onclick="PedidosApp.verDetalle(${v.idventas})">👁 Ver</button>
        ${!esCancelado ? `
        <button class="btn-accion btn-guardar-estado" id="btn-save-${v.idventas}"
                onclick="PedidosApp.guardarEstado(${v.idventas})" disabled>💾</button>
        <button class="btn-accion btn-cancelar-venta" onclick="PedidosApp.cancelarPedido(${v.idventas})">✖ Cancelar</button>
        ` : ''}
        ${estadoId === 3 && esEnvio ? `
        <button class="btn-accion btn-reasignar" onclick="PedidosApp.reasignarRepartidor(${v.idventas})">
          🔄 Liberar y rebuscar
        </button>
        <button class="btn-accion btn-whatsapp"
                onclick="PedidosApp.mensajeCliente('${String(v.cliente_telefono||'').replace(/\D/g,'')}', '${(v.cliente_nombre||'').split(' ')[0].replace(/'/g,"\\'")}')">
          💬 Mensaje
        </button>` : ''}
      `;

      return `
        <tr id="row-${v.idventas}" data-tipo-entrega="${tipoEntrega}" data-rep-pendiente="${v.repartidor_pendiente_idusuario || ''}" class="${rowCls} row-clickable" onclick="PedidosApp.toggleAcciones(${v.idventas})">
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
          <td onclick="event.stopPropagation()">
            ${esCancelado
              ? `<span class="estado-pill est-6"><span class="est-dot"></span>Cancelado</span>`
              : `<select class="estado-select estado-bg-${estadoId}" id="estado-select-${v.idventas}"
                         onchange="PedidosApp.onEstadoChange(${v.idventas})">
                   ${selectOpts}
                 </select>`
            }
          </td>
          <td class="expand-td"><span class="expand-chevron" id="chev-${v.idventas}">▾</span></td>
        </tr>
        <tr id="acc-row-${v.idventas}" class="acciones-expand-row" style="display:none">
          <td colspan="8">
            <div class="acciones-expand-inner">
              ${accionesBtns}
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function toggleAcciones(idVenta) {
    const row = document.getElementById('acc-row-' + idVenta);
    const chev = document.getElementById('chev-' + idVenta);
    if (!row) return;
    const open = row.style.display !== 'none';
    row.style.display = open ? 'none' : 'table-row';
    if (chev) chev.classList.toggle('open', !open);
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

    // Bloqueo: retiro no puede ir a "En reparto"
    if (tipoEntrega === 'retiro' && nuevoEstado === 3) {
      // Revertir selector al estado actual
      const estadoActual = row?.className.match(/row-estado-(\d+)/)?.[1] || '1';
      if (select) select.value = estadoActual;
      if (btn)    btn.disabled = true;
      await Swal.fire({
        icon: 'info',
        title: 'Pedido de retiro en local',
        html:  'Este pedido <strong>lo viene a buscar el cliente al local</strong>.<br>No se puede asignar reparto a domicilio.',
        confirmButtonColor: '#c88e99',
        confirmButtonText: 'Entendido',
      });
      return;
    }

    // Para pedidos de envío que van a "En reparto": elegir repartidor o Uber
    if (tipoEntrega === 'envio' && nuevoEstado === 3) {
      await abrirModalRepartidor(idVenta);
      if (btn) { btn.disabled = false; btn.textContent = '💾'; }
      return;
    }
    await ejecutarCambio(idVenta, nuevoEstado, null, btn);
  }

  async function reasignarRepartidor(idVenta) {
    const ok = await Swal.fire({
      title: '¿Liberar repartidor?',
      html: 'Se le quitará el pedido al repartidor actual y se buscará uno nuevo automáticamente.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#f97316',
      cancelButtonColor: '#718096',
      confirmButtonText: '🔄 Sí, rebuscar',
      cancelButtonText: 'Cancelar',
    });
    if (!ok.isConfirmed) return;

    _cancelarBusqueda(idVenta);
    try {
      const res  = await fetch('api/liberar_repartidor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_venta: idVenta }),
      });
      const data = await res.json();
      if (!data.success) { showToast('Error: ' + (data.message || 'No se pudo liberar'), 'error'); return; }
    } catch (e) {
      showToast('Error de conexión', 'error'); return;
    }
    iniciarBusquedaRep(idVenta);
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

  // ─── BÚSQUEDA DE REPARTIDOR (con aceptación/rechazo) ────────
  // Estado por pedido: { repId, rechazados:[], retryTimer, pollTimer }
  const _busquedas = {};

  function _cancelarBusqueda(idVenta) {
    const b = _busquedas[idVenta];
    if (!b) return;
    clearTimeout(b.retryTimer);
    clearTimeout(b.pollTimer);
    delete _busquedas[idVenta];
  }

  function _setRepStatus(idVenta, html) {
    const el = document.getElementById('rep-status-' + idVenta);
    if (el) el.outerHTML = `<small id="rep-status-${idVenta}">${html}</small>`;
  }

  async function iniciarBusquedaRep(idVenta, rechazados = []) {
    _cancelarBusqueda(idVenta);
    _busquedas[idVenta] = { repId: null, rechazados, retryTimer: null, pollTimer: null };
    _setRepStatus(idVenta, '🔍 Buscando repartidor...');

    try {
      const res  = await fetch('api/auto_asignar_repartidor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_venta: idVenta, rechazados }),
      });
      const data = await res.json();

      if (data.success && data.propuesta) {
        const b = _busquedas[idVenta];
        if (!b) return; // fue cancelada mientras esperaba
        b.repId = data.repartidor_id;
        _setRepStatus(idVenta, `⏳ Esperando a repartidores...`);
        showToast(`⏳ Propuesta enviada a repartidores`, '');
        pollPropuesta(idVenta);

      } else if (data.sin_repartidor) {
        _setRepStatus(idVenta, '⚠️ Sin repartidores disponibles. Reintentando...');
        showToast('⚠️ Sin repartidores libres. Reintentando en 15s...', 'warning');
        if (_busquedas[idVenta]) {
          _busquedas[idVenta].retryTimer = setTimeout(
            () => iniciarBusquedaRep(idVenta, rechazados),
            15_000
          );
        }

      } else {
        _cancelarBusqueda(idVenta);
        _setRepStatus(idVenta, '❌ Error al buscar');
        showToast('Error: ' + (data.message || 'No se pudo buscar repartidor'), 'error');
      }

    } catch (e) {
      _setRepStatus(idVenta, '⚠️ Error de conexión. Reintentando...');
      showToast('⚠️ Error de conexión. Reintentando en 15s...', 'warning');
      if (_busquedas[idVenta]) {
        _busquedas[idVenta].retryTimer = setTimeout(
          () => iniciarBusquedaRep(idVenta, rechazados),
          15_000
        );
      }
    }
  }

  function pollPropuesta(idVenta) {
    const b = _busquedas[idVenta];
    if (!b) return;

    b.pollTimer = setTimeout(async () => {
      try {
        const res  = await fetch('api/check_propuesta.php?id_venta=' + idVenta);
        const data = await res.json();
        const bNow = _busquedas[idVenta];
        if (!bNow) return; // cancelada

        if (data.status === 'aceptado') {
          _cancelarBusqueda(idVenta);
          showToast(`✅ Pedido aceptado por ${data.repartidor}`, 'success');
          cargarPedidos();

        } else if (data.status === 'libre') {
          // Repartidor rechazó — agregar a rechazados y proponer al siguiente
          const rechazadosNuevos = [...(bNow.rechazados || [])];
          if (bNow.repId && !rechazadosNuevos.includes(bNow.repId)) {
            rechazadosNuevos.push(bNow.repId);
          }
          _cancelarBusqueda(idVenta);
          showToast('El repartidor rechazó el pedido. Buscando otro...', 'warning');
          iniciarBusquedaRep(idVenta, rechazadosNuevos);

        } else {
          // 'esperando' o error → seguir polling cada 4s
          pollPropuesta(idVenta);
        }
      } catch (e) {
        pollPropuesta(idVenta); // error de red, reintentar poll
      }
    }, 4_000);
  }

  // ─── MODAL REPARTIDOR ─────────────────────
  let _repVentaId = null;

  async function abrirModalRepartidor(idVenta) {
    _repVentaId = idVenta;
    const modal = document.getElementById('modal-repartidor');
    const info  = document.getElementById('rep-cliente-info');
    info.innerHTML = '<div style="color:#94a3b8;font-size:13px">Cargando...</div>';

    // Reset al abrir
    if (typeof setTipoEntregaModal === 'function') setTipoEntregaModal('envio');
    if (typeof setMetodoEnvio === 'function') setMetodoEnvio('repartidor');
    const uberInput = document.getElementById('uber-link-input');
    if (uberInput) uberInput.value = '';

    modal.style.display = 'flex';

    try {
      const detalle = await fetch('api/get_detalle.php?id=' + idVenta).then(r => r.json());
      const dir = detalle.direccion_entrega || '—';
      const tel = String(detalle.cliente_telefono || '—');
      info.innerHTML = `<div class="rep-modal-info">
        <div><strong>Cliente:</strong> ${detalle.cliente_nombre || ''} ${detalle.cliente_apellido || ''}</div>
        <div><strong>Teléfono:</strong> ${tel}</div>
        <div><strong>Dirección:</strong> ${dir}</div>
      </div>`;
    } catch (e) {
      info.innerHTML = '';
    }
  }

  async function confirmarRepartidor() {
    const ventaId   = _repVentaId;
    const tipoModal  = (typeof _modalTipoEntrega !== 'undefined')  ? _modalTipoEntrega  : 'envio';
    const metodoEnvio = (typeof _modalMetodoEnvio !== 'undefined') ? _modalMetodoEnvio  : 'repartidor';

    if (tipoModal === 'retiro') {
      cerrarModalRep();
      const btn = document.getElementById('btn-save-' + ventaId) || { disabled: false, textContent: '' };
      await ejecutarCambio(ventaId, 7, null, btn, false, 'retiro');
      return;
    }

    // Envío a domicilio
    if (metodoEnvio === 'uber') {
      const uberLink = (document.getElementById('uber-link-input')?.value || '').trim();
      cerrarModalRep();
      const btn = document.getElementById('btn-save-' + ventaId) || { disabled: false, textContent: '' };
      await ejecutarCambioUber(ventaId, uberLink, btn);
      return;
    }

    // Repartidor propio → iniciar búsqueda automática
    cerrarModalRep();
    iniciarBusquedaRep(ventaId);
  }

  async function ejecutarCambioUber(idVenta, uberLink, btn) {
    if (btn) { btn.disabled = true; btn.textContent = '⏳'; }
    try {
      const body = { id_venta: idVenta, estado: 3, via_uber: true, tipo_entrega: 'envio' };
      if (uberLink) body.uber_link = uberLink;
      const res  = await fetch('api/actualizar_pedido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (data.success) {
        showToast('🚗 Uber asignado', 'success');
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

  function toggleSeccion(h4) {
    const content = h4.nextElementSibling;
    const chevron = h4.querySelector('.toggle-chevron');
    const isOpen  = content.style.display !== 'none';
    content.style.display = isOpen ? 'none' : '';
    if (chevron) chevron.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
  }

  function renderDetalle(d) {
    const estadoId = parseInt(d.estado_id);
    const est = ESTADOS[estadoId] || { label: 'Desconocido', cls: 'est-1' };
    const fecha = fmtFecha(d.fecha);
    document.getElementById('detalle-body').innerHTML = `
      <div class="detalle-section">
        <h4>📦 ESTADO ACTUAL</h4>
        <span class="estado-pill ${est.cls}"><span class="est-dot"></span>${est.label}</span>
      </div>
      <div class="detalle-section">
        <h4 class="section-toggle" onclick="PedidosApp.toggleSeccion(this)">
          👤 CLIENTE <span class="toggle-chevron" style="transform:rotate(-90deg)">▼</span>
        </h4>
        <div class="section-content" style="display:none">
          <div class="detalle-info-grid">
            <div class="info-item"><label>Nombre</label><span>${d.cliente_nombre || '—'} ${d.cliente_apellido || ''}</span></div>
            <div class="info-item"><label>Teléfono</label><span>${String(d.cliente_telefono || '—')}</span></div>
            <div class="info-item"><label>Email</label><span>${d.cliente_email || '—'}</span></div>
            ${d.tipo_entrega === 'envio' ? `
            <div class="info-item"><label>Tipo entrega</label><span>🛵 Envío</span></div>
            <div class="info-item" style="grid-column:1/-1"><label>Dirección</label><span>${d.direccion_entrega || '—'}</span></div>
            <div class="info-item"><label>Repartidor</label><span>${d.via_uber ? '🚗 Uber' : (d.repartidor_nombre || '— Sin asignar —')}</span></div>
            ` : `<div class="info-item"><label>Tipo entrega</label><span>🏪 Retiro</span></div>`}
          </div>
        </div>
      </div>
      <div class="detalle-section">
        <h4>💳 PAGO & FECHA</h4>
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
                <div class="di-nombre"><i class="fa-solid fa-cookie-bite" style="font-size:11px;color:#c88e99;margin-right:5px"></i>${p.nombre}</div>
                <div class="di-qty">Cantidad: ${p.cantidad}</div>
                ${boxHtml}
              </div>
              <div class="di-precio">${fmt(p.precio_unitario * p.cantidad)}</div>
            </div>`;
          }).join('')}
          ${(d.toppings && d.toppings.length)
            ? d.toppings.map(t => `
              <div class="detalle-item detalle-item--topping">
                <div style="flex:1">
                  <div class="di-nombre"><i class="fa-solid fa-plus" style="font-size:10px;color:#f59e0b;margin-right:5px"></i>${t.nombre}</div>
                  <div class="di-qty">Extra / Topping</div>
                </div>
                <div class="di-precio di-precio--topping">+${fmt(t.precio)}</div>
              </div>`).join('')
            : `<div style="font-size:12px;color:#94a3b8;padding:6px 0 2px">Sin extras</div>`
          }
        </div>
        <div class="detalle-total"><span>Total productos</span><span>${fmt((d.productos||[]).reduce((s,p)=>s+(p.precio_unitario*p.cantidad),0) + (d.toppings||[]).reduce((s,t)=>s+t.precio,0))}</span></div>
      </div>

      <!-- Resumen de costos -->
      <div class="detalle-section">
        <h4><i class="fa-solid fa-receipt" style="font-size:13px;margin-right:6px;color:#64748b"></i>Resumen del pedido</h4>
        <div class="detalle-resumen-list">
          ${d.tipo_entrega === 'envio' ? `
            <div class="det-res-row"><span><i class="fa-solid fa-motorcycle" style="color:#3b82f6;margin-right:6px"></i>Envío${d.direccion_entrega ? ` — <span style="font-weight:400;color:#64748b">${d.direccion_entrega}</span>` : ''}</span><span>${+d.costo_envio > 0 ? fmt(d.costo_envio) : '<span style="color:#16a34a">Gratis</span>'}</span></div>
          ` : `
            <div class="det-res-row"><span><i class="fa-solid fa-store" style="color:#64748b;margin-right:6px"></i>Retiro en sucursal</span><span style="color:#64748b">—</span></div>
          `}
          ${+d.tarifa_servicio > 0 ? `<div class="det-res-row"><span><i class="fa-solid fa-circle-info" style="color:#94a3b8;margin-right:6px"></i>Tarifa de servicio</span><span>${fmt(d.tarifa_servicio)}</span></div>` : ''}
          ${+d.propina > 0 ? `<div class="det-res-row"><span><i class="fa-solid fa-hand-holding-heart" style="color:#3b82f6;margin-right:6px"></i>Propina repartidor</span><span>${fmt(d.propina)}</span></div>` : ''}
          ${d.cupon_codigo ? `<div class="det-res-row" style="color:#16a34a"><span><i class="fa-solid fa-tag" style="margin-right:6px"></i>Cupón ${d.cupon_codigo}</span><span>−${fmt(d.descuento_cupon)}</span></div>` : ''}
          ${d.observacion_cliente ? `<div class="det-res-row" style="flex-direction:column;gap:2px"><span style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em">Observación</span><span style="font-weight:500">${d.observacion_cliente}</span></div>` : ''}
          <div class="det-res-row det-res-total"><span>Total</span><span>${fmt(d.total)}</span></div>
        </div>
      </div>
    `;
  }

  function cerrarDetalle() {
    document.getElementById('modal-detalle').style.display = 'none';
  }

  function mensajeCliente(telefono, nombre) {
    if (!telefono) { showToast('Sin número de teléfono registrado', 'error'); return; }
    let num = telefono.replace(/\D/g, '');
    if (num.startsWith('0')) num = num.slice(1);
    if (!num.startsWith('54')) num = '54' + num;
    const saludo = nombre ? `¡Hola, ${nombre}! 👋` : '¡Hola! 👋';
    const msg = encodeURIComponent(`${saludo} Tu pedido está en camino. ¡Gracias por elegirnos! 🍪`);
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

  return { cargarPedidos, filtrarPorEstado, guardarEstado, onEstadoChange, verDetalle, cerrarDetalle, mensajeCliente, cancelarPedido, confirmarRepartidor, cerrarModalRep, reasignarRepartidor, toggleSeccion, toggleAcciones };
})();
