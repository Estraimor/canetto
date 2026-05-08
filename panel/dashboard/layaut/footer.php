</div><!-- /main-content -->

<footer class="footer">
    © <?= date('Y') ?> Canetto Software
</footer>

</div> <!-- cierre main -->
</div> <!-- cierre app -->

<!-- ================= LIBRERÍAS ================= -->

<!-- jQuery (SIEMPRE PRIMERO) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables Core -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<!-- DataTables Responsive -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ================= INICIALIZACIONES ================= -->



<!-- ================= SIDEBAR TOGGLE ================= -->
<script>
(function() {
    document.querySelectorAll('.menu-title').forEach(function(title) {
        title.addEventListener('click', function() {
            var group = this.closest('.menu-group');
            var isOpen = group.classList.contains('open');
            // Cerrar todos
            document.querySelectorAll('.menu-group').forEach(function(g) {
                g.classList.remove('open');
            });
            // Abrir el clickeado si estaba cerrado
            if (!isOpen) {
                group.classList.add('open');
            }
        });
    });
})();
</script>

<!-- ================= RELOJ GLOBAL ================= -->
<script>
(function tickNav() {
    const el = document.getElementById('navClock');
    const ed = document.getElementById('dashClock');
    const d  = new Date();
    const p  = x => String(x).padStart(2, '0');
    const t  = `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
    if (el) el.textContent = t;
    if (ed) ed.textContent = t;
    setTimeout(tickNav, 1000);
})();
</script>

<!-- ================= LOADER ================= -->
<script>
window.addEventListener("load", function() {
    const loader = document.getElementById("loader");
    loader.style.transition = "opacity 0.6s ease";
    loader.style.opacity = "0";
    setTimeout(() => { loader.style.display = "none"; }, 600);
});
</script>

<!-- ================= NOTIFICACIONES ================= -->
<style>
.notif-wrap { position:relative; }
.notif-bell {
    background:none; border:none; cursor:pointer; position:relative;
    color:inherit; padding:6px; border-radius:8px;
    transition:background .2s; display:flex; align-items:center;
}
.notif-bell:hover { background:rgba(255,255,255,.15); }
.notif-bell i { font-size:1.1rem; }
.notif-badge {
    position:absolute; top:-2px; right:-2px;
    background:#ef4444; color:#fff;
    font-size:10px; font-weight:800;
    min-width:17px; height:17px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    padding:0 4px; border:2px solid #1e1e1e;
    animation:badgePop .3s cubic-bezier(.36,.07,.19,.97);
}
@keyframes badgePop { 0%{transform:scale(0)}60%{transform:scale(1.25)}100%{transform:scale(1)} }

.notif-panel {
    position:absolute; top:calc(100% + 10px); right:0;
    width:360px; max-height:480px;
    background:#fff; border-radius:12px;
    box-shadow:0 8px 40px rgba(0,0,0,.22);
    border:1px solid #e5e7eb;
    display:flex; flex-direction:column;
    z-index:9999; animation:panelSlide .2s ease;
    overflow:hidden;
}
@keyframes panelSlide { from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)} }

.notif-panel-header {
    padding:14px 16px; border-bottom:1px solid #f3f4f6;
    display:flex; align-items:center; justify-content:space-between;
    background:#fff; position:sticky; top:0; z-index:1;
}
.notif-panel-header span { font-weight:700; font-size:.95rem; color:#111; }
.notif-mark-all {
    background:none; border:none; cursor:pointer;
    font-size:.74rem; color:#6b7280; font-weight:600;
    padding:4px 8px; border-radius:6px; transition:all .2s;
}
.notif-mark-all:hover { background:#f3f4f6; color:#111; }

.notif-list { overflow-y:auto; flex:1; }
.notif-empty { padding:32px 16px; text-align:center; color:#9ca3af; font-size:.87rem; }

.notif-item {
    display:flex; gap:12px; padding:13px 16px;
    border-bottom:1px solid #f3f4f6; cursor:pointer;
    transition:background .15s; text-decoration:none;
    align-items:flex-start;
}
.notif-item:hover { background:#f9fafb; }
.notif-item:last-child { border-bottom:none; }

.notif-icon {
    width:38px; height:38px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; flex-shrink:0;
}
.notif-icon.pedido_nuevo  { background:#eff6ff; }
.notif-icon.stock_bajo    { background:#fff7ed; }
.notif-icon.default       { background:#f3f4f6; }

.notif-content { flex:1; min-width:0; }
.notif-title  { font-size:.88rem; font-weight:700; color:#111; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.notif-desc   { font-size:.78rem; color:#6b7280; line-height:1.4; }
.notif-time   { font-size:.7rem; color:#9ca3af; margin-top:4px; }

/* ── Toast estilo WhatsApp ── */
.notif-toast {
    position:fixed; top:72px; right:16px;
    background:#1e293b; color:#fff;
    border-radius:14px; width:280px;
    height:fit-content; max-height:300px;
    display:flex; flex-direction:column;
    z-index:99999; overflow:hidden; cursor:pointer;
    box-shadow:0 8px 32px rgba(0,0,0,.45);
    animation:toastSlide .25s cubic-bezier(.22,.68,0,1.2) both;
    border-left:3px solid #3b82f6;
}
.notif-toast.stock_bajo  { border-left-color:#f97316; }
.notif-toast.sin_stock   { border-left-color:#ef4444; }
.notif-toast.pedido_nuevo{ border-left-color:#22c55e; }

/* Cabecera compacta */
.nt-head {
    display:flex; align-items:center; gap:10px;
    padding:10px 12px 8px;
}
.nt-tipo-badge {
    font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em;
    background:rgba(255,255,255,.12); border-radius:6px;
    padding:3px 7px; white-space:nowrap; flex-shrink:0;
}
.notif-toast.pedido_nuevo .nt-tipo-badge { background:rgba(34,197,94,.2); color:#86efac; }
.notif-toast.sin_stock .nt-tipo-badge    { background:rgba(239,68,68,.2);  color:#fca5a5; }
.notif-toast.stock_bajo .nt-tipo-badge   { background:rgba(249,115,22,.2); color:#fdba74; }
.nt-text { flex:1; min-width:0; }
.nt-title {
    font-weight:800; font-size:15px;
    white-space:normal; word-break:break-word;
}
.nt-subtitle {
    font-size:13px; opacity:.6; margin-top:1px;
    white-space:normal; word-break:break-word;
}
.nt-close {
    background:none; border:none; color:#fff; opacity:.4;
    cursor:pointer; font-size:.9rem; padding:4px; line-height:1;
    flex-shrink:0; transition:opacity .15s;
}
.nt-close:hover { opacity:1; }

/* Cuerpo: productos en una línea */
.nt-body {
    padding:0 12px 8px;
    border-top:1px solid rgba(255,255,255,.07);
    padding-top:7px;
}
.nt-prods {
    font-size:14px; font-weight:600;
    white-space:normal; word-break:break-word;
    opacity:.9;
}
.nt-tops {
    font-size:13px; color:#c4b5fd; margin-top:3px;
    white-space:normal; word-break:break-word;
}

/* Footer: tags + total */
.nt-footer {
    display:flex; align-items:center; justify-content:space-between;
    padding:6px 12px 8px; gap:6px;
}
.nt-tags { display:flex; gap:4px; flex-wrap:wrap; }
.nt-tag {
    font-size:12px; font-weight:700; padding:2px 7px;
    border-radius:20px; background:rgba(255,255,255,.12);
    white-space:nowrap;
}
.nt-total {
    font-size:16px; font-weight:800; color:#86efac; white-space:nowrap;
}

/* Barra de progreso */
.nt-bar { height:2px; background:rgba(255,255,255,.1); }
.nt-bar-fill {
    height:2px; background:#22c55e;
    animation:toastProgress 60s linear forwards;
}
@keyframes toastProgress { from{width:100%} to{width:0%} }
@keyframes toastSlide {
    from { opacity:0; transform:translateX(calc(100% + 20px)); }
    to   { opacity:1; transform:translateX(0); }
}
</style>

<script>
const NotifApp = (() => {
    let _open     = false;
    let _interval = null;
    const ICONS = {
        pedido_nuevo:  '🛍️',
        sin_stock:     '⛔',
        stock_bajo:    '⚠️',
        mp_sin_stock:  '⛔',
        mp_stock_bajo: '⚠️',
        receta_sin_mp: '📋',
        incidencia:    '🚨',
    };

    function fmtAgo(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)  return 'hace ' + diff + 's';
        if (diff < 3600) return 'hace ' + Math.floor(diff/60) + 'min';
        return 'hace ' + Math.floor(diff/3600) + 'h';
    }

    async function poll() {
        try {
            const data = await fetch('<?= URL_ADMIN ?>/api/notificaciones.php').then(r => r.json());
            const total = data.total || 0;
            const badge = document.getElementById('notifBadge');
            const bell  = document.getElementById('notifBell');

            if (badge) {
                badge.textContent = total > 99 ? '99+' : total;
                badge.style.display = total > 0 ? 'flex' : 'none';
                bell.style.color = total > 0 ? '#fbbf24' : '';
            }

            if (_open) renderList(data.notificaciones || []);

            // Mostrar toast solo para pedidos_nuevo muy recientes (< 30s)
            (data.notificaciones || []).forEach(n => {
                if (n.tipo === 'pedido_nuevo') {
                    const age = (Date.now() - new Date(n.created_at)) / 1000;
                    if (age < 35) showToastNotif(n);
                }
            });
        } catch (e) {}
    }

    const _shownToasts = new Set();
    const fmt$ = v => '$' + Math.round(parseFloat(v||0)).toLocaleString('es-AR');

    function showToastNotif(n) {
        if (_shownToasts.has(n.id)) return;
        _shownToasts.add(n.id);

        let datos = {};
        try { datos = JSON.parse(n.datos_json || '{}'); } catch(e) {}

        const prods   = datos.productos  || [];
        const tops    = datos.toppings   || [];
        const cliente = datos.cliente    || '';
        const origen  = datos.origen     || '';
        const entrega = datos.entrega    || '';
        const metodo  = datos.metodo     || '';
        const total   = datos.total      || 0;

        // Línea de productos compacta: "Cookie ×4, Brownie ×2"
        const prodsLinea = prods.map(p => `${p.nombre} ×${p.cantidad}`).join(' · ') || '—';

        // Toppings si los hay
        const topsLinea = tops.length ? `✨ ${tops.join(', ')}` : '';

        // Subtítulo: cliente + entrega
        const subtitulo = [cliente, entrega].filter(Boolean).join(' · ');

        const el = document.createElement('div');
        el.className = 'notif-toast ' + n.tipo;
        const tipoLabel = {pedido_nuevo:'Nuevo pedido', sin_stock:'Sin stock', stock_bajo:'Stock bajo', mp_sin_stock:'Sin stock', mp_stock_bajo:'Stock bajo'}[n.tipo] || 'Notificación';
        el.innerHTML = `
            <div class="nt-head">
                <div class="nt-tipo-badge">${tipoLabel}</div>
                <div class="nt-text">
                    <div class="nt-title">${n.titulo}</div>
                    ${subtitulo ? `<div class="nt-subtitle">${subtitulo}</div>` : ''}
                </div>
                <button class="nt-close" onclick="event.stopPropagation();this.closest('.notif-toast').remove()">✕</button>
            </div>
            <div class="nt-body">
                <div class="nt-prods">${prodsLinea}</div>
                ${topsLinea ? `<div class="nt-tops">${topsLinea}</div>` : ''}
            </div>
            <div class="nt-footer">
                <div class="nt-tags">
                    ${metodo ? `<span class="nt-tag">${metodo}</span>` : ''}
                    ${origen ? `<span class="nt-tag">${origen}</span>` : ''}
                </div>
                <span class="nt-total">${fmt$(total)}</span>
            </div>
            <div class="nt-bar"><div class="nt-bar-fill"></div></div>
        `;
        el.addEventListener('click', () => {
            if (n.link) { marcar(n.id); window.location.href = n.link; }
        });
        document.body.appendChild(el);
        setTimeout(() => { el.style.transition='opacity .4s,transform .4s'; el.style.opacity='0'; el.style.transform='translateX(calc(100% + 20px))'; setTimeout(()=>el.remove(),400); }, 60000);
    }

    function renderList(notifs) {
        const list = document.getElementById('notifList');
        if (!list) return;
        if (!notifs.length) {
            list.innerHTML = '<div class="notif-empty">Sin notificaciones nuevas ✓</div>';
            return;
        }
        list.innerHTML = notifs.map(n => `
            <a class="notif-item" href="${n.link || '#'}" onclick="NotifApp.marcar(${n.id}, event)">
                <div class="notif-icon ${n.tipo}">${ICONS[n.tipo] || '🔔'}</div>
                <div class="notif-content">
                    <div class="notif-title">${n.titulo}</div>
                    <div class="notif-desc">${n.descripcion || ''}</div>
                    <div class="notif-time">${fmtAgo(n.created_at)}</div>
                </div>
            </a>
        `).join('');
    }

    function toggle() {
        _open = !_open;
        const panel = document.getElementById('notifPanel');
        if (!panel) return;
        panel.style.display = _open ? 'flex' : 'none';
        if (_open) poll();
    }

    function marcar(id, e) {
        fetch('<?= URL_ADMIN ?>/api/notificaciones.php?marcar=' + id).catch(() => {});
    }

    async function marcarTodas() {
        await fetch('<?= URL_ADMIN ?>/api/notificaciones.php?marcar_todas=1').catch(() => {});
        poll();
    }

    // Cerrar al click fuera
    document.addEventListener('click', e => {
        const wrap = document.getElementById('notifWrap');
        if (wrap && !wrap.contains(e.target) && _open) {
            _open = false;
            const panel = document.getElementById('notifPanel');
            if (panel) panel.style.display = 'none';
        }
    });

    // Arrancar polling cada 30s
    document.addEventListener('DOMContentLoaded', () => {
        poll();
        _interval = setInterval(poll, 30000);
    });

    return { toggle, marcar, marcarTodas };
})();

// ── Sidebar toggle ────────────────────────────────────────
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const isMini  = sidebar.classList.toggle('mini');
    localStorage.setItem('sidebarMini', isMini ? '1' : '0');
    document.documentElement.style.setProperty(
        '--sidebar-w', isMini ? 'var(--sidebar-w-mini)' : '240px'
    );
}

// Restaurar estado al cargar
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('sidebarMini') === '1') {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.add('mini');
            document.documentElement.style.setProperty('--sidebar-w', 'var(--sidebar-w-mini)');
        }
    }
});
</script>

</body>
</html>