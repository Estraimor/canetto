<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/tron.php';
include '../../panel/dashboard/layaut/nav.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="cupones.css">

<div class="cup-wrap">

    <a href="javascript:history.back()" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>

    <!-- Header -->
    <div class="cup-header">
        <div>
            <h1>🎟️ Cupones de descuento</h1>
            <p>Creá y gestioná códigos promocionales para la tienda online</p>
        </div>
        <button class="cup-btn-new" id="btnNuevoCupon">
            <i class="fa-solid fa-plus"></i> Nuevo cupón
        </button>
    </div>

    <!-- Stats -->
    <div class="cup-stats">
        <div class="cup-stat s-activos">
            <div class="cup-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="cup-stat-body">
                <div class="cup-stat-num" id="statActivos">—</div>
                <div class="cup-stat-lbl">Activos</div>
            </div>
        </div>
        <div class="cup-stat s-total">
            <div class="cup-stat-icon"><i class="fa-solid fa-ticket"></i></div>
            <div class="cup-stat-body">
                <div class="cup-stat-num" id="statTotal">—</div>
                <div class="cup-stat-lbl">Total</div>
            </div>
        </div>
        <div class="cup-stat s-vencidos">
            <div class="cup-stat-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="cup-stat-body">
                <div class="cup-stat-num" id="statVencidos">—</div>
                <div class="cup-stat-lbl">Vencidos</div>
            </div>
        </div>
        <div class="cup-stat s-usos">
            <div class="cup-stat-icon"><i class="fa-solid fa-chart-simple"></i></div>
            <div class="cup-stat-body">
                <div class="cup-stat-num" id="statUsos">—</div>
                <div class="cup-stat-lbl">Usos totales</div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="cup-table-wrap">
        <div class="cup-toolbar">
            <div class="cup-search-box">
                <i class="fa-solid fa-magnifying-glass cup-search-icon"></i>
                <input type="text" id="cupSearch" class="cup-search-input" placeholder="Buscar cupón...">
            </div>
        </div>
        <table id="cupTable" class="cup-table display" style="width:100%">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Descuento</th>
                    <th>Mín. pedido</th>
                    <th>Usos</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th style="width:110px;text-align:right">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<!-- Modal crear/editar -->
<div class="cup-modal" id="modalCupon">
    <div class="cup-modal-backdrop" id="modalBackdrop"></div>
    <div class="cup-modal-dialog">
        <div class="cup-modal-head">
            <div>
                <div class="cup-modal-title" id="modalTitle">Nuevo cupón</div>
                <div class="cup-modal-sub">Completá los datos del código promocional</div>
            </div>
            <button class="cup-x" id="btnCerrarModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="cup-modal-body">
            <input type="hidden" id="cup_id">
            <div class="cup-grid">
                <div class="cup-field">
                    <label>Código *</label>
                    <div class="cup-codigo-wrap">
                        <input type="text" id="cup_codigo" placeholder="Ej: VERANO20" style="text-transform:uppercase">
                        <button type="button" class="btn-azar" id="btnAzar" title="Generar código aleatorio" onclick="generarCodigo()">
                            <i class="fa-solid fa-shuffle"></i>
                        </button>
                    </div>
                    <span class="cup-hint">Escribí el tuyo o generá uno aleatorio</span>
                </div>
                <div class="cup-field">
                    <label>Tipo *</label>
                    <select id="cup_tipo" onchange="onCupTipoChange(this.value)">
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="fijo">Monto fijo ($)</option>
                        <option value="envio_gratis">Envío Gratis</option>
                    </select>
                </div>
                <div class="cup-field" id="cup_valor_wrap">
                    <label>Valor *</label>
                    <input type="number" id="cup_valor" min="0.01" step="0.01" placeholder="Ej: 15">
                    <span class="cup-hint" id="cup_valor_hint">% de descuento sobre el total</span>
                </div>
                <div class="cup-field">
                    <label>Pedido mínimo ($)</label>
                    <input type="number" id="cup_min_pedido" min="0" step="1" placeholder="0 = sin mínimo">
                </div>
                <div class="cup-field">
                    <label>Máximo de usos</label>
                    <input type="number" id="cup_max_usos" min="1" step="1" placeholder="Vacío = ilimitado">
                </div>
                <div class="cup-field" style="justify-content:flex-end;flex-direction:row;align-items:center;gap:10px">
                    <label style="margin:0;text-transform:none;font-size:13px;color:#555">1 uso por usuario</label>
                    <label class="switch">
                        <input type="checkbox" id="cup_un_uso" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="cup-field">
                    <label>Fecha inicio</label>
                    <input type="date" id="cup_fecha_inicio">
                </div>
                <div class="cup-field">
                    <label>Fecha fin</label>
                    <input type="date" id="cup_fecha_fin">
                </div>
                <div class="cup-field full">
                    <label>Descripción</label>
                    <input type="text" id="cup_descripcion" placeholder="Ej: 20% de descuento en verano">
                </div>
                <div class="cup-field" style="justify-content:flex-end;flex-direction:row;align-items:center;gap:10px">
                    <label style="margin:0;text-transform:none;font-size:13px;color:#555">Activo</label>
                    <label class="switch">
                        <input type="checkbox" id="cup_activo" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            <div class="cup-modal-actions">
                <button class="cup-btn-soft" id="btnCancelar">Cancelar</button>
                <button class="cup-btn-new" id="btnGuardar">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="cup-toast" id="cupToast">
    <i class="fa-solid fa-circle-check"></i>
    <span id="cupToastMsg">Listo</span>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script>
const fmt = n => '$' + Number(n).toLocaleString('es-AR', {minimumFractionDigits:0});
const fmtDate = d => d ? d.split('-').reverse().join('/') : '—';

function toast(msg, ok=true) {
    const t = document.getElementById('cupToast');
    document.getElementById('cupToastMsg').textContent = msg;
    t.querySelector('i').style.color = ok ? '#4ade80' : '#f87171';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

/* ── DataTable ── */
const tabla = jQuery('#cupTable').DataTable({
    ajax: { url: 'api/listar.php', dataSrc: '' },
    pageLength: 15,
    order: [[0,'asc']],
    language: {
        lengthMenu: 'Mostrar _MENU_ por página',
        zeroRecords: 'No hay cupones',
        info: '_START_–_END_ de _TOTAL_',
        infoEmpty: 'Sin resultados',
        paginate: { first:'«', last:'»', next:'›', previous:'‹' }
    },
    dom: "<'dt-top'<'dt-len'l>><'dt-body'tr><'dt-foot'<'dt-info'i><'dt-pag'p>>",
    columns: [
        { data: 'codigo', render: c => `<span class="cup-code">${c}</span>` },
        { data: 'descripcion', render: d => d || '<span style="color:#ddd">—</span>' },
        {
            data: null,
            render: r => {
                if (r.tipo === 'envio_gratis') return `<span class="cup-valor" style="background:#dcfce7;color:#15803d">Envío Gratis</span>`;
                const v = r.tipo === 'porcentaje' ? `${r.valor}%` : fmt(r.valor);
                return `<span class="cup-valor">${v}</span>`;
            }
        },
        { data: 'min_pedido', render: d => parseFloat(d) > 0 ? fmt(d) : '<span style="color:#ddd">—</span>' },
        {
            data: null,
            render: r => {
                const max = r.max_usos ? `/ ${r.max_usos}` : '/ ∞';
                return `<strong>${r.usos_reales || 0}</strong><span style="color:#aaa;font-size:12px"> ${max}</span>`;
            }
        },
        {
            data: null,
            render: r => {
                const ini = r.fecha_inicio ? fmtDate(r.fecha_inicio) : '';
                const fin = r.fecha_fin    ? fmtDate(r.fecha_fin)    : '';
                if (!ini && !fin) return '<span style="color:#ddd">Sin límite</span>';
                return `<span style="font-size:12px;color:#555">${ini || '∞'} → ${fin || '∞'}</span>`;
            }
        },
        {
            data: 'estado_real',
            render: d => {
                const map = {
                    activo:   ['cup-badge-activo',  'Activo'],
                    vencido:  ['cup-badge-vencido',  'Vencido'],
                    agotado:  ['cup-badge-agotado',  'Agotado'],
                    inactivo: ['cup-badge-inactivo', 'Inactivo'],
                    pendiente:['cup-badge-pendiente','Pendiente'],
                };
                const [cls, lbl] = map[d] || ['cup-badge-inactivo','—'];
                return `<span class="cup-badge ${cls}">${lbl}</span>`;
            }
        },
        {
            data: null, orderable: false, className: 'dt-right',
            render: r => `
                <button class="cup-icon-btn wa" onclick="enviarWhatsApp('${r.codigo}','${(r.descripcion||'').replace(/'/g,'')}')" title="Enviar por WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                </button>
                <button class="cup-icon-btn edit" onclick="abrirEditar(${r.id})" title="Editar">
                    <i class="fa fa-pen"></i>
                </button>
                <button class="cup-icon-btn del" onclick="eliminar(${r.id},'${r.codigo}')" title="Desactivar">
                    <i class="fa fa-trash"></i>
                </button>`
        }
    ]
});

/* Stats */
tabla.on('xhr', function() {
    const data = tabla.ajax.json() || [];
    document.getElementById('statTotal').textContent   = data.length;
    document.getElementById('statActivos').textContent = data.filter(c => c.estado_real === 'activo').length;
    document.getElementById('statVencidos').textContent= data.filter(c => c.estado_real === 'vencido').length;
    document.getElementById('statUsos').textContent    = data.reduce((s,c) => s + (parseInt(c.usos_reales)||0), 0);
});

/* Búsqueda custom */
document.getElementById('cupSearch').addEventListener('input', function() {
    tabla.search(this.value).draw();
});

/* Tipo → hint valor */
function onCupTipoChange(tipo) {
    const wrap = document.getElementById('cup_valor_wrap');
    const hint = document.getElementById('cup_valor_hint');
    if (tipo === 'envio_gratis') {
        wrap.style.display = 'none';
        document.getElementById('cup_valor').value = '0';
    } else {
        wrap.style.display = '';
        hint.textContent = tipo === 'porcentaje' ? '% de descuento sobre el total' : 'Monto fijo en pesos';
    }
}
document.getElementById('cup_tipo').addEventListener('change', function() { onCupTipoChange(this.value); });
document.getElementById('cup_codigo').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

function generarCodigo() {
    const chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // sin I,O para evitar confusión con 1,0
    const digits = '23456789';                  // sin 0,1
    // Formato: 3 letras + 2 números + 2 letras + 1 número  → ej: XKM47QR3
    let codigo = '';
    for (let i = 0; i < 3; i++) codigo += chars[Math.floor(Math.random() * chars.length)];
    for (let i = 0; i < 2; i++) codigo += digits[Math.floor(Math.random() * digits.length)];
    for (let i = 0; i < 2; i++) codigo += chars[Math.floor(Math.random() * chars.length)];
    codigo += digits[Math.floor(Math.random() * digits.length)];

    const input = document.getElementById('cup_codigo');
    input.value = codigo;
    const btn = document.getElementById('btnAzar');
    btn.classList.add('spin');
    setTimeout(() => btn.classList.remove('spin'), 400);
    input.focus();
    input.select();
}

/* ── Modal ── */
function abrirModal(titulo) {
    document.getElementById('modalTitle').textContent = titulo;
    document.getElementById('modalCupon').classList.add('open');
}
function cerrarModal() {
    document.getElementById('modalCupon').classList.remove('open');
    document.getElementById('cup_id').value = '';
}

document.getElementById('btnNuevoCupon').addEventListener('click', () => {
    document.getElementById('cup_id').value        = '';
    document.getElementById('cup_codigo').value    = '';
    document.getElementById('cup_descripcion').value = '';
    document.getElementById('cup_tipo').value      = 'porcentaje';
    document.getElementById('cup_valor').value     = '';
    document.getElementById('cup_min_pedido').value= '';
    document.getElementById('cup_max_usos').value  = '';
    document.getElementById('cup_un_uso').checked  = true;
    document.getElementById('cup_fecha_inicio').value = '';
    document.getElementById('cup_fecha_fin').value    = '';
    document.getElementById('cup_activo').checked  = true;
    onCupTipoChange('porcentaje');
    abrirModal('Nuevo cupón');
});

function abrirEditar(id) {
    const row = tabla.rows().data().toArray().find(r => r.id == id);
    if (!row) return;
    document.getElementById('cup_id').value           = row.id;
    document.getElementById('cup_codigo').value        = row.codigo;
    document.getElementById('cup_descripcion').value   = row.descripcion || '';
    document.getElementById('cup_tipo').value          = row.tipo;
    document.getElementById('cup_valor').value         = row.valor;
    document.getElementById('cup_min_pedido').value    = row.min_pedido || '';
    document.getElementById('cup_max_usos').value      = row.max_usos || '';
    document.getElementById('cup_un_uso').checked      = !!parseInt(row.un_uso_por_usuario);
    document.getElementById('cup_fecha_inicio').value  = row.fecha_inicio || '';
    document.getElementById('cup_fecha_fin').value     = row.fecha_fin    || '';
    document.getElementById('cup_activo').checked      = !!parseInt(row.activo);
    onCupTipoChange(row.tipo);
    abrirModal('Editar cupón');
}

document.getElementById('btnCerrarModal').addEventListener('click', cerrarModal);
document.getElementById('btnCancelar').addEventListener('click', cerrarModal);
document.getElementById('modalBackdrop').addEventListener('click', cerrarModal);
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

/* Guardar */
document.getElementById('btnGuardar').addEventListener('click', async () => {
    const payload = {
        id:               parseInt(document.getElementById('cup_id').value) || 0,
        codigo:           document.getElementById('cup_codigo').value.trim().toUpperCase(),
        descripcion:      document.getElementById('cup_descripcion').value.trim(),
        tipo:             document.getElementById('cup_tipo').value,
        valor:            parseFloat(document.getElementById('cup_valor').value) || 0,
        min_pedido:       parseFloat(document.getElementById('cup_min_pedido').value) || 0,
        max_usos:         document.getElementById('cup_max_usos').value || '',
        un_uso_por_usuario: document.getElementById('cup_un_uso').checked ? 1 : 0,
        fecha_inicio:     document.getElementById('cup_fecha_inicio').value || '',
        fecha_fin:        document.getElementById('cup_fecha_fin').value    || '',
        activo:           document.getElementById('cup_activo').checked ? 1 : 0,
    };
    if (!payload.codigo) { toast('El código es obligatorio', false); return; }
    if (!payload.valor)  { toast('El valor debe ser mayor a 0', false); return; }

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    const res  = await fetch('api/guardar.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const data = await res.json();

    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';

    if (data.ok) {
        cerrarModal();
        tabla.ajax.reload(null, false);
        toast(payload.id ? 'Cupón actualizado' : 'Cupón creado');
    } else {
        toast(data.msg || 'Error al guardar', false);
    }
});

/* WhatsApp */
async function enviarWhatsApp(codigo, descripcion) {
    const desc = descripcion ? `\n📝 ${descripcion}` : '';
    const msgTexto = `🍪 *Canetto Cookies*\n\n¡Hola! Te enviamos tu cupón de descuento exclusivo:\n\n🎟️ *Código:* \`${codigo}\`${desc}\n\nUsalo al finalizar tu pedido en la tienda 🛒`;

    const { value: telefono } = await Swal.fire({
        title: '<i class="fa-brands fa-whatsapp" style="color:#25d366;margin-right:8px"></i>Enviar cupón por WhatsApp',
        width: 520,
        html: `
            <div style="text-align:left">
                <p style="font-size:13px;color:#666;margin:0 0 10px">Buscá un cliente o ingresá el número manualmente.</p>

                <div style="position:relative;margin-bottom:6px">
                    <input id="swal-search" type="text" placeholder="🔍 Buscar cliente por nombre o número..."
                        style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;font-family:inherit;outline:none;box-sizing:border-box">
                </div>

                <div id="swal-lista" style="max-height:160px;overflow-y:auto;border:1px solid #e8e7e4;border-radius:10px;margin-bottom:10px;display:none"></div>

                <label style="font-size:12px;color:#888;font-weight:600;display:block;margin-bottom:4px">Número destino</label>
                <input id="swal-tel" type="tel" placeholder="Ej: 5491123456789"
                    style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;letter-spacing:1px;font-family:inherit;outline:none;box-sizing:border-box">

                <div style="margin-top:10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px">
                    <div style="font-size:10px;color:#16a34a;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Vista previa del mensaje</div>
                    <div style="font-size:12px;color:#555;line-height:1.6;white-space:pre-wrap" id="swal-preview"></div>
                </div>
            </div>`,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-brands fa-whatsapp"></i> Enviar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#25d366',
        cancelButtonColor: '#aaa',
        didOpen: () => {
            document.getElementById('swal-preview').textContent = msgTexto;

            let debounce;
            const searchInput = document.getElementById('swal-search');
            const lista       = document.getElementById('swal-lista');
            const telInput    = document.getElementById('swal-tel');

            function renderClientes(clientes, inicial) {
                lista.style.display = 'block';
                if (!clientes.length) {
                    lista.innerHTML = `<div style="padding:12px 14px;font-size:13px;color:#94a3b8;text-align:center">
                        <i class="fa-solid fa-magnifying-glass" style="margin-right:6px;opacity:.5"></i>
                        No se encontró ningún cliente
                    </div>`;
                    return;
                }
                const footer = inicial
                    ? `<div style="padding:7px 14px;font-size:11px;color:#aaa;text-align:center;border-top:1px solid #f0eef0">Mostrando los primeros 5 — escribí para buscar más</div>`
                    : '';
                lista.innerHTML = clientes.map(c => `
                    <div class="wa-cli-row" data-tel="${c.celular}"
                        style="padding:9px 14px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f5f3f5;gap:10px">
                        <span style="font-weight:600;color:#1a1a1a;font-size:13px">${c.nombre.trim()}</span>
                        <span style="color:#25d366;font-family:monospace;font-size:12px;font-weight:600">${c.celular}</span>
                    </div>`).join('') + footer;

                lista.querySelectorAll('.wa-cli-row').forEach(row => {
                    row.addEventListener('mouseenter', () => row.style.background = '#f0fdf4');
                    row.addEventListener('mouseleave', () => row.style.background = '');
                    row.addEventListener('click', () => {
                        telInput.value = row.dataset.tel;
                        lista.style.display = 'none';
                        searchInput.value = row.querySelector('span').textContent.trim();
                        telInput.focus();
                    });
                });
            }

            async function buscar(q) {
                lista.innerHTML = '<div style="padding:10px 14px;font-size:13px;color:#aaa;text-align:center">Buscando…</div>';
                lista.style.display = 'block';
                try {
                    const r = await fetch(`api/get_clientes_tel.php?q=${encodeURIComponent(q)}`);
                    const d = await r.json();
                    if (d.ok) renderClientes(d.clientes, d.inicial);
                    else lista.innerHTML = `<div style="padding:10px 14px;font-size:13px;color:#f87171;text-align:center">Error: ${d.error ?? 'No se pudo cargar'}</div>`;
                } catch(e) {
                    lista.innerHTML = '<div style="padding:10px 14px;font-size:13px;color:#f87171;text-align:center">Error de conexión</div>';
                }
            }

            searchInput.addEventListener('input', () => {
                clearTimeout(debounce);
                const q = searchInput.value.trim();
                if (!q) { buscar(''); return; }
                lista.innerHTML = '<div style="padding:10px 14px;font-size:13px;color:#aaa;text-align:center">Buscando…</div>';
                lista.style.display = 'block';
                debounce = setTimeout(() => buscar(q), 300);
            });

            searchInput.addEventListener('focus', () => buscar(searchInput.value.trim()));

            Swal.getHtmlContainer().addEventListener('click', e => {
                if (!lista.contains(e.target) && e.target !== searchInput) lista.style.display = 'none';
            });

            searchInput.focus();
        },
        preConfirm: () => {
            const t = document.getElementById('swal-tel').value.replace(/\D/g,'');
            if (!t || t.length < 10) {
                Swal.showValidationMessage('Ingresá un número válido (mínimo 10 dígitos)');
                return false;
            }
            return t;
        }
    });

    if (!telefono) return;
    window.open(`https://wa.me/${telefono}?text=${encodeURIComponent(msgTexto)}`, '_blank');
}

/* Eliminar */
async function eliminar(id, codigo) {
    const r = await Swal.fire({
        title: `¿Desactivar "${codigo}"?`,
        text: 'El cupón quedará inactivo y no podrá usarse en la tienda.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#c88e99', cancelButtonColor: '#9aa1ad',
        confirmButtonText: 'Sí, desactivar', cancelButtonText: 'Cancelar',
        reverseButtons: true
    });
    if (!r.isConfirmed) return;
    const res  = await fetch('api/eliminar.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) });
    const data = await res.json();
    if (data.ok) { tabla.ajax.reload(null, false); toast('Cupón desactivado'); }
    else toast(data.msg || 'Error', false);
}
</script>

<?php include '../../panel/dashboard/layaut/footer.php'; ?>
