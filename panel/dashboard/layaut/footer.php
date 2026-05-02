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

/* Toast de notificación */
.notif-toast {
    position:fixed; top:72px; right:24px;
    background:#1e293b; color:#fff;
    border-radius:12px; padding:10px 14px;
    max-width:260px; z-index:99999;
    box-shadow:0 8px 30px rgba(0,0,0,.25);
    animation:toastSlide .35s cubic-bezier(.36,.07,.19,.97);
    cursor:pointer; border-left:4px solid #3b82f6;
}
.notif-toast.stock_bajo { border-left-color:#f97316; }
.notif-toast-title { font-weight:800; font-size:.93rem; margin-bottom:4px; }
.notif-toast-desc  { font-size:.8rem; opacity:.85; }
.notif-toast-close { position:absolute;top:8px;right:10px;background:none;border:none;color:#fff;opacity:.6;cursor:pointer;font-size:.9rem;padding:2px; }
@keyframes toastSlide { from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)} }
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
    function showToastNotif(n) {
        if (_shownToasts.has(n.id)) return;
        _shownToasts.add(n.id);

        const el = document.createElement('div');
        el.className = 'notif-toast ' + n.tipo;
        el.innerHTML = `
            <button class="notif-toast-close" onclick="this.parentElement.remove()">✕</button>
            <div class="notif-toast-title">${ICONS[n.tipo] || '🔔'} ${n.titulo}</div>
            <div class="notif-toast-desc">${n.descripcion || ''}</div>
        `;
        el.addEventListener('click', () => {
            if (n.link) { marcar(n.id); window.location.href = n.link; }
        });
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 10000);
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