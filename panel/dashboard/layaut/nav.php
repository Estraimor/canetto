<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===========================
   VERIFICAR SESIÓN ACTIVA
=========================== */

if (!isset($_SESSION['usuario_id'])) {
    redirect('/login/login.php');
}

/* ===========================
   VERIFICAR ROL ADMINISTRADOR
=========================== */
$rolesPermitidos = ['admin', 'administrador', 'administracion'];
if (!in_array(strtolower($_SESSION['rol'] ?? ''), $rolesPermitidos, true)) {
    session_destroy();
    redirect('/login/login.php?error=acceso_denegado');
}

/* ===========================
   CONFIG NAV
=========================== */

if (!isset($pageTitle)) {
    $pageTitle = "Canetto Admin";
}

$current = $_SERVER['PHP_SELF'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $pageTitle ?></title>
<link rel="icon" type="image/png" href="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png">

<link rel="stylesheet" href="<?= URL_ASSETS ?>/assets/dashboard.css?v=<?= filemtime(dirname(__DIR__, 3) . '/assets/dashboard.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

</head>
<body>

<!-- ================= LOADER ================= -->
<div id="loader">
    <div class="loader-content">
        <div class="logo-loader">Canetto</div>
        <div class="spinner"></div>
        <p>Cargando panel...</p>
    </div>
</div>

<div class="app">

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar" id="sidebar">

    <div class="brand">
        <a class="brand-logo" href="<?= URL_ADMIN ?>/index.php">
            <div class="brand-icon">C</div>
            <div class="brand-text">
                <span class="brand-name">Canetto</span>
                <span class="brand-sub">Administración</span>
            </div>
        </a>
        <button class="sidebar-toggle" id="sidebarToggle" title="Contraer menú" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <div class="nav-wrapper">
    <nav class="nav-menu">

    <!-- DASHBOARD -->
    <a href="<?= URL_ADMIN ?>/index.php" data-tip="Dashboard"
       class="<?= str_contains($current,'administracion/index.php') ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i>
        <span class="nav-label">Dashboard</span>
    </a>

    <div class="nav-section">Producción</div>

    <!-- PRODUCTOS -->
    <div class="menu-group <?= str_contains($current,'productos') || str_contains($current,'toppings') || str_contains($current,'recetas') ? 'open' : '' ?>">
        <span class="menu-title" data-tip="Productos">
            <i class="fa-solid fa-cookie-bite"></i>
            <span class="menu-label">Productos</span>
        </span>
        <div class="submenu">
            <a href="<?= URL_ADMIN ?>/productos/index.php"
               class="<?= str_contains($current,'administracion/productos/index') ? 'active' : '' ?>">
                <i class="fa-solid fa-list"></i>
                Listado
            </a>
            <a href="<?= URL_ADMIN ?>/productos/crear_producto.php"
               class="<?= str_contains($current,'crear_producto') ? 'active' : '' ?>">
                <i class="fa-solid fa-plus"></i>
                Crear producto
            </a>
            <a href="<?= URL_ADMIN ?>/recetas/index.php"
               class="<?= str_contains($current,'recetas') ? 'active' : '' ?>">
                <i class="fa-solid fa-book-open"></i>
                Recetas
            </a>
            <a href="<?= URL_ADMIN ?>/toppings/index.php"
               class="<?= str_contains($current,'toppings') ? 'active' : '' ?>">
                <i class="fa-solid fa-candy-cane"></i>
                Toppings
            </a>
        </div>
    </div>

    <div class="nav-section">Inventario</div>

    <!-- STOCK -->
    <div class="menu-group <?= str_contains($current,'stock') || str_contains($current,'materias_primas') || str_contains($current,'packaging') ? 'open' : '' ?>">
        <span class="menu-title" data-tip="Stock">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span class="menu-label">Stock</span>
        </span>
        <div class="submenu">
            <a href="<?= URL_ADMIN ?>/stock/index.php"
               class="<?= str_contains($current,'stock/index.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-cookie-bite"></i>
                Productos
            </a>
            <a href="<?= URL_ADMIN ?>/materias_primas/index.php"
               class="<?= str_contains($current,'materias_primas') ? 'active' : '' ?>">
                <i class="fa-solid fa-seedling"></i>
                Materias primas
            </a>
            <a href="<?= URL_ADMIN ?>/packaging/index.php"
               class="<?= str_contains($current,'packaging') ? 'active' : '' ?>">
                <i class="fa-solid fa-box-open"></i>
                Packaging
            </a>
            <a href="<?= URL_ADMIN ?>/stock/toppings/index.php"
               class="<?= str_contains($current,'stock/toppings') ? 'active' : '' ?>">
                <i class="fa-solid fa-candy-cane"></i>
                Toppings
            </a>
        </div>
    </div>

    <!-- MERMAS -->
    <a href="<?= URL_ADMIN ?>/mermas/index.php" data-tip="Mermas"
       class="<?= str_contains($current,'mermas') ? 'active' : '' ?>">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span class="nav-label">Mermas</span>
    </a>

    <div class="nav-section">Comercial</div>

    <!-- VENTAS -->
    <div class="menu-group <?= str_contains($current,'Ventas') || str_contains($current,'ventas') ? 'open' : '' ?>">
        <span class="menu-title" data-tip="Ventas">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="menu-label">Ventas</span>
        </span>
        <div class="submenu">
            <a href="<?= URL_ADMIN ?>/Ventas/Pedidos/index.php"
               class="<?= str_contains($current,'Pedidos') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock"></i>
                Pedidos activos
            </a>
            <a href="<?= URL_ADMIN ?>/Ventas/Ventas/index.php"
               class="<?= str_contains($current,'Ventas/Ventas/index') ? 'active' : '' ?>">
                <i class="fa-solid fa-plus-circle"></i>
                Nueva venta
            </a>
            <a href="<?= URL_ADMIN ?>/Ventas/Ventas/Historial/index.php"
               class="<?= str_contains($current,'Historial') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Historial
            </a>
        </div>
    </div>

    <!-- PROVEEDORES -->
    <a href="<?= URL_ADMIN ?>/proveedor/index.php" data-tip="Proveedores"
       class="<?= str_contains($current,'proveedor') ? 'active' : '' ?>">
        <i class="fa-solid fa-handshake"></i>
        <span class="nav-label">Proveedores</span>
    </a>

    <!-- REPARTIDORES -->
    <div class="menu-group <?= str_contains($current,'repartidores/') ? 'open' : '' ?>">
        <span class="menu-title" data-tip="Repartidores">
            <i class="fa-solid fa-motorcycle"></i>
            <span class="menu-label">Repartidores</span>
        </span>
        <div class="submenu">
            <a href="<?= URL_ADMIN ?>/repartidores/index.php"
               class="<?= str_contains($current,'repartidores/index') ? 'active' : '' ?>">
                <i class="fa-solid fa-map-location-dot"></i>
                Mapa en vivo
            </a>
            <a href="<?= URL_ADMIN ?>/repartidores/notif_test.php"
               class="<?= str_contains($current,'notif_test') ? 'active' : '' ?>">
                <i class="fa-solid fa-bell"></i>
                Notificaciones
            </a>
        </div>
    </div>

    <!-- CLIENTES -->
    <a href="<?= URL_ADMIN ?>/clientes/index.php" data-tip="Clientes"
       class="<?= str_contains($current,'clientes') ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i>
        <span class="nav-label">Clientes</span>
    </a>

    <!-- CUPONES -->
    <a href="<?= URL_ADMIN ?>/cupones/index.php" data-tip="Cupones"
       class="<?= str_contains($current,'cupones') ? 'active' : '' ?>">
        <i class="fa-solid fa-ticket"></i>
        <span class="nav-label">Cupones</span>
    </a>

    <div class="nav-section">Sistema</div>

    <!-- CONFIGURACIONES -->
    <div class="menu-group <?= str_contains($current,'configuraciones') ? 'open' : '' ?>">
        <span class="menu-title" data-tip="Configuraciones">
            <i class="fa-solid fa-gears"></i>
            <span class="menu-label">Configuraciones</span>
        </span>
        <div class="submenu">
            <a href="<?= URL_ASSETS ?>/configuraciones/index.php"
               class="<?= str_ends_with($current,'configuraciones/index.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-grip"></i>
                Panel general
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/usuarios.php"
               class="<?= str_contains($current,'configuraciones/usuarios') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i>
                Usuarios
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/roles.php"
               class="<?= str_contains($current,'configuraciones/roles.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-shield-halved"></i>
                Roles
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/roles_usuario.php"
               class="<?= str_contains($current,'roles_usuario') ? 'active' : '' ?>">
                <i class="fa-solid fa-user-tag"></i>
                Roles por usuario
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/metodos_pago.php"
               class="<?= str_contains($current,'metodos_pago') ? 'active' : '' ?>">
                <i class="fa-solid fa-credit-card"></i>
                Métodos de pago
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/sucursales.php"
               class="<?= str_contains($current,'sucursales') ? 'active' : '' ?>">
                <i class="fa-solid fa-building"></i>
                Sucursales
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/paneles.php"
               class="<?= str_contains($current,'paneles') || str_contains($current,'ofertas') ? 'active' : '' ?>">
                <i class="fa-solid fa-table-columns"></i>
                Paneles
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/repartidores.php"
               class="<?= str_contains($current,'repartidores') ? 'active' : '' ?>">
                <i class="fa-solid fa-motorcycle"></i>
                Repartidores
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/auditoria.php"
               class="<?= str_contains($current,'auditoria') ? 'active' : '' ?>">
                <i class="fa-solid fa-clipboard-list"></i>
                Auditoría
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/tarifas_envio.php"
               class="<?= str_contains($current,'tarifas_envio') ? 'active' : '' ?>">
                <i class="fa-solid fa-motorcycle"></i>
                Tarifas de Envío
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/datos_bancarios.php"
               class="<?= str_contains($current,'datos_bancarios') ? 'active' : '' ?>">
                <i class="fa-solid fa-building-columns"></i>
                Datos Bancarios
            </a>

            <a href="<?= URL_ASSETS ?>/configuraciones/tienda.php"
               class="<?= str_contains($current,'configuraciones/tienda') ? 'active' : '' ?>">
                <i class="fa-solid fa-store"></i>
                Tienda
            </a>
        </div>
    </div>


</nav>
    </div><!-- /nav-wrapper -->
</aside>

<!-- ================= MAIN ================= -->
<div class="main">

<header class="topbar">
    <div class="left">
        <span>Sucursal: <strong>Casa Central</strong></span>
        <?php if (isset($topbarStats)): ?>
            <span class="topbar-stat"><?= htmlspecialchars($topbarStats) ?></span>
        <?php endif; ?>
    </div>

    <div class="right">
        <!-- ── TOGGLE TIENDA ── -->
        <div id="tiendaToggleWrap" style="display:flex;align-items:center;gap:8px">
            <button id="btnTiendaToggle" onclick="tiendaToggle()"
                style="display:flex;align-items:center;gap:8px;padding:6px 14px;border-radius:20px;border:2px solid #e5e7eb;background:#fff;font-family:inherit;font-size:12px;font-weight:800;cursor:pointer;transition:all .2s;letter-spacing:.02em">
                <span id="tiendaDot" style="width:9px;height:9px;border-radius:50%;background:#ccc;flex-shrink:0;transition:background .2s"></span>
                <span id="tiendaLabel">Cargando…</span>
            </button>
        </div>

        <!-- ── DARK MODE TOGGLE ── -->
        <button id="darkToggle" onclick="toggleDark()" title="Cambiar tema" style="
            width:38px;height:38px;border-radius:50%;border:none;cursor:pointer;
            display:flex;align-items:center;justify-content:center;
            font-size:16px;transition:background .3s,transform .3s;
            background:var(--bg-page);color:var(--text-2);
            flex-shrink:0;
        ">
            <i class="fa-solid fa-moon" id="darkIcon"></i>
        </button>

        <div class="topbar-clock">
            <i class="fa-regular fa-clock"></i>
            <span id="navClock">--:--:--</span>
        </div>

        <!-- ── CAMPANA DE NOTIFICACIONES ── -->
        <div class="notif-wrap" id="notifWrap">
            <button class="notif-bell" id="notifBell" onclick="NotifApp.toggle()" title="Notificaciones">
                <i class="fa-solid fa-bell"></i>
                <span class="notif-badge" id="notifBadge" style="display:none">0</span>
            </button>
            <div class="notif-panel" id="notifPanel" style="display:none">
                <div class="notif-panel-header">
                    <span>Notificaciones</span>
                    <button onclick="NotifApp.marcarTodas()" class="notif-mark-all">Marcar todas como leídas</button>
                </div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Sin notificaciones nuevas</div>
                </div>
            </div>
        </div>

        <div class="nav-perfil-wrap" id="navPerfilWrap">
            <div class="nav-perfil-btn" id="navPerfilBtn" onclick="togglePerfilMenu()">
                <div id="navAvatar">
                    <?php if (!empty($_SESSION['avatar'])):
                    $__av = $_SESSION['avatar'];
                    $__avUrl = str_starts_with($__av, 'http') ? $__av : URL_ASSETS . '/' . $__av . '?v=' . time();
                ?>
                        <img src="<?= htmlspecialchars($__avUrl) ?>"
                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid var(--brand)">
                    <?php else:
                        $__ini = strtoupper(substr($_SESSION['nombre'] ?? '', 0, 1) . substr($_SESSION['apellido'] ?? '', 0, 1)) ?: '?';
                    ?>
                        <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#c88e99,#e07a8c);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;border:2px solid var(--brand)"><?= htmlspecialchars($__ini) ?></div>
                    <?php endif; ?>
                </div>
                <span id="navUserName"><?= htmlspecialchars(trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''))) ?></span>
                <i class="fa-solid fa-chevron-down" id="navPerfilChevron" style="font-size:10px;opacity:.5;transition:transform .2s"></i>
            </div>
            <div class="nav-perfil-menu" id="navPerfilMenu">
                <a href="<?= URL_ASSETS ?>/configuraciones/perfil.php" class="nav-perfil-item">
                    <i class="fa-solid fa-circle-user"></i> Mi perfil
                </a>
                <div class="nav-perfil-divider"></div>
                <a href="<?= URL_LOGIN ?>/logout.php" class="nav-perfil-item nav-perfil-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </div>
</header>

<style>
.nav-perfil-wrap {
    position: relative;
}
.nav-perfil-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 10px 4px 4px;
    border-radius: 40px;
    cursor: pointer;
    transition: background .15s;
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    user-select: none;
}
.nav-perfil-btn:hover { background: var(--bg-page); }
.nav-perfil-wrap.open .nav-perfil-btn { background: var(--bg-page); }
.nav-perfil-wrap.open #navPerfilChevron { transform: rotate(180deg); }

.nav-perfil-menu {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    background: var(--bg-card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 14px;
    min-width: 180px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
    overflow: hidden;
    z-index: 9999;
    animation: perfilMenuIn .15s ease;
}
.nav-perfil-wrap.open .nav-perfil-menu { display: block; }
@keyframes perfilMenuIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.nav-perfil-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 16px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    text-decoration: none;
    transition: background .1s;
}
.nav-perfil-item:hover { background: var(--bg-page, #f5f5f5); }
.nav-perfil-item i { width: 16px; text-align: center; opacity: .7; }
.nav-perfil-logout { color: #dc2626; }
.nav-perfil-logout i { opacity: 1; }
.nav-perfil-logout:hover { background: #fef2f2; }
.nav-perfil-divider { height: 1px; background: var(--border, #e5e7eb); margin: 2px 0; }
</style>

<script>
function togglePerfilMenu() {
    document.getElementById('navPerfilWrap').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('navPerfilWrap');
    if (wrap && !wrap.contains(e.target)) wrap.classList.remove('open');
});
</script>

<style>
/* ── Dark toggle button ── */
#darkToggle {
    position: relative;
    overflow: visible !important;
}
#darkToggle:hover {
    background: var(--brand-light) !important;
    color: var(--brand) !important;
}
#darkIcon {
    display: block;
    transition: transform .5s cubic-bezier(.34,1.56,.64,1), opacity .2s ease;
}
#darkToggle.spinning #darkIcon {
    animation: iconSpin .5s cubic-bezier(.34,1.56,.64,1) forwards;
}
@keyframes iconSpin {
    0%   { transform: rotate(0)   scale(1);   opacity: 1; }
    40%  { transform: rotate(180deg) scale(0); opacity: 0; }
    60%  { transform: rotate(180deg) scale(0); opacity: 0; }
    100% { transform: rotate(360deg) scale(1); opacity: 1; }
}

/* ── Partículas ── */
.dark-spark {
    position: fixed;
    pointer-events: none;
    z-index: 99998;
    border-radius: 50%;
    animation: sparkFly .6s ease forwards;
}
@keyframes sparkFly {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--tx), var(--ty)) scale(0); opacity: 0; }
}

/* ── Capa de clip-path ── */
#darkClip {
    position: fixed;
    inset: 0;
    z-index: 99997;
    pointer-events: none;
    clip-path: circle(0px at 50% 50%);
    transition: none;
}
</style>

<div id="darkClip"></div>

<script>
/* ── Dark mode — init ─────────────────────────────────────── */
(function initDark() {
    const dark = localStorage.getItem('canetto_dark') === '1';
    if (dark) {
        document.documentElement.classList.add('dark');
        const icon = document.getElementById('darkIcon');
        if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
    }
})();

/* ── Partículas ── */
function lanzarSparks(cx, cy, color) {
    const count = 10;
    for (let i = 0; i < count; i++) {
        const el = document.createElement('div');
        el.className = 'dark-spark';
        const angle  = (360 / count) * i + Math.random() * 20;
        const dist   = 40 + Math.random() * 50;
        const size   = 4 + Math.random() * 6;
        const rad    = angle * Math.PI / 180;
        el.style.cssText = `
            left:${cx - size/2}px; top:${cy - size/2}px;
            width:${size}px; height:${size}px;
            background:${color};
            --tx:${Math.cos(rad)*dist}px;
            --ty:${Math.sin(rad)*dist}px;
            box-shadow: 0 0 ${size}px ${color};
        `;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 700);
    }
}

let _darkBusy = false;

function toggleDark() {
    if (_darkBusy) return;
    _darkBusy = true;

    const html   = document.documentElement;
    const isDark = html.classList.contains('dark');
    const btn    = document.getElementById('darkToggle');
    const icon   = document.getElementById('darkIcon');
    const clip   = document.getElementById('darkClip');

    const rect = btn.getBoundingClientRect();
    const cx   = rect.left + rect.width  / 2;
    const cy   = rect.top  + rect.height / 2;

    const maxR = Math.hypot(
        Math.max(cx, window.innerWidth  - cx),
        Math.max(cy, window.innerHeight - cy)
    ) + 50;

    /* 1 — Partículas */
    lanzarSparks(cx, cy, isDark ? '#f59e0b' : '#c88e99');

    /* 2 — Animar ícono */
    btn.classList.add('spinning');
    setTimeout(() => btn.classList.remove('spinning'), 520);

    /* 3 — Preparar capa clip-path con el color DESTINO */
    clip.style.background = isDark ? '#f4f3f0' : '#0e0e1a';
    clip.style.clipPath    = `circle(0px at ${cx}px ${cy}px)`;
    clip.style.transition  = 'none';

    requestAnimationFrame(() => requestAnimationFrame(() => {
        /* Expandir la capa */
        clip.style.transition = `clip-path .55s cubic-bezier(.4,0,.06,1)`;
        clip.style.clipPath   = `circle(${maxR}px at ${cx}px ${cy}px)`;

        setTimeout(() => {
            /* Aplicar el tema cuando la capa ya cubre todo */
            html.classList.add('dark-transitioning');
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('canetto_dark', '0');
                icon.classList.replace('fa-sun', 'fa-moon');
            } else {
                html.classList.add('dark');
                localStorage.setItem('canetto_dark', '1');
                icon.classList.replace('fa-moon', 'fa-sun');
            }

            /* Contraer la capa revelando el nuevo tema */
            clip.style.transition = `clip-path .45s cubic-bezier(.4,0,.2,1)`;
            clip.style.clipPath   = `circle(0px at ${cx}px ${cy}px)`;

            setTimeout(() => {
                html.classList.remove('dark-transitioning');
                clip.style.transition = 'none';
                _darkBusy = false;
            }, 480);

        }, 380);
    }));
}

/* ── Estado Tienda ───────────────────────────── */
let _tiendaModo      = 'abierta'; // abierta | solo_vista | cerrada (efectivo)
let _tiendaModoConf  = 'abierta'; // modo guardado en config
let _tiendaMensaje   = '';
let _tiendaHorario   = false;
let _tiendaEnHorario = null;
let _tiendaApertura  = '09:00';
let _tiendaCierre    = '21:00';

const _MODO_CFG = {
    abierta:    { color:'#c88e99', bg:'#fdf0f3', bdr:'#c88e99', dot:'#c88e99', label:'Tienda abierta',   labelAuto:'Abierta (auto)' },
    solo_vista: { color:'#2563eb', bg:'#eff6ff', bdr:'#2563eb', dot:'#2563eb', label:'Cerrado para pedidos',    labelAuto:'Cerrado para pedidos (auto)' },
    cerrada:    { color:'#dc2626', bg:'#fef2f2', bdr:'#dc2626', dot:'#dc2626', label:'⛔ Cerrada',       labelAuto:'⛔ Fuera de horario' },
};

function tiendaRenderBtn() {
    const btn = document.getElementById('btnTiendaToggle');
    const dot = document.getElementById('tiendaDot');
    const lbl = document.getElementById('tiendaLabel');
    if (!btn) return;
    const BASE = 'display:flex;align-items:center;gap:8px;padding:6px 14px;border-radius:20px;font-family:inherit;font-size:12px;font-weight:800;cursor:pointer;transition:all .2s;';
    const fueraHorario = _tiendaHorario && _tiendaEnHorario === false;
    if (fueraHorario) {
        btn.style.cssText = BASE + 'border:2px solid #f59e0b;background:#fffbeb;color:#b45309';
        dot.style.background = '#f59e0b';
        lbl.textContent = 'Fuera de horario';
        btn.title = `Abre a las ${_tiendaApertura}`;
    } else {
        const cfg = _MODO_CFG[_tiendaModo] ?? _MODO_CFG.cerrada;
        btn.style.cssText = BASE + `border:2px solid ${cfg.bdr};background:${cfg.bg};color:${cfg.color}`;
        dot.style.background = cfg.dot;
        lbl.textContent = _tiendaHorario ? cfg.labelAuto : cfg.label;
        btn.title = _tiendaHorario ? `Horario: ${_tiendaApertura} – ${_tiendaCierre}` : '';
    }
}

async function tiendaCargarEstado() {
    try {
        const r = await fetch('<?= URL_ADMIN ?>/api/tienda_estado.php');
        const d = await r.json();
        _tiendaModo      = d.modo          ?? 'abierta';
        _tiendaModoConf  = d.modo_config   ?? 'abierta';
        _tiendaMensaje   = d.mensaje       ?? '';
        _tiendaHorario   = d.horario_activado  ?? false;
        _tiendaEnHorario = d.en_horario    ?? null;
        _tiendaApertura  = d.horario_apertura ?? '09:00';
        _tiendaCierre    = d.horario_cierre   ?? '21:00';
    } catch(e) { _tiendaModo = 'abierta'; }
    finally { tiendaRenderBtn(); }
}

async function tiendaToggle() {
    // Fuera de horario: sólo info
    if (_tiendaHorario && _tiendaEnHorario === false) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon:'info', title:'Fuera de horario',
                html:`La tienda se abrirá automáticamente a las <strong>${_tiendaApertura}</strong>.<br><small style="color:#aaa">Podés cambiar el horario en <a href="<?= URL_ADMIN ?>/configuraciones/tienda.php" style="color:#c88e99">Configuración → Tienda</a></small>`,
                confirmButtonColor:'#c88e99' });
        }
        return;
    }

    if (typeof Swal === 'undefined') {
        const nuevo = _tiendaModo === 'cerrada' ? 'abierta' : 'cerrada';
        await _tiendaSetModo(nuevo, '');
        return;
    }

    // Modal con 3 opciones
    const modoActual = _tiendaModo;
    const { value: formValues } = await Swal.fire({
        title: 'Estado de la tienda',
        width: 420,
        html: `
<style>
.swal-modo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:12px 0}
.swal-modo-opt{cursor:pointer;border-radius:12px;border:2px solid #ebebeb;padding:12px 8px;text-align:center;transition:.15s;background:#fafafa}
.swal-modo-opt:hover{border-color:#d0d0d0;background:#f5f5f5}
.swal-modo-opt input{display:none}
.swal-modo-opt.sel-abierta{border-color:#16a34a;background:#f0fdf4}
.swal-modo-opt.sel-solo_vista{border-color:#2563eb;background:#eff6ff}
.swal-modo-opt.sel-cerrada{border-color:#dc2626;background:#fef2f2}
.swal-modo-ico{font-size:22px;margin-bottom:5px}
.swal-modo-nm{font-size:12px;font-weight:800;color:#1a1a1a}
.swal-modo-ds{font-size:10px;color:#aaa;margin-top:2px;line-height:1.3}
.swal-modo-opt.sel-abierta .swal-modo-nm{color:#15803d}
.swal-modo-opt.sel-solo_vista .swal-modo-nm{color:#1d4ed8}
.swal-modo-opt.sel-cerrada .swal-modo-nm{color:#b91c1c}
</style>
<div class="swal-modo-grid">
  <label class="swal-modo-opt ${modoActual==='abierta'?'sel-abierta':''}" data-v="abierta">
    <input type="radio" name="sm" value="abierta" ${modoActual==='abierta'?'checked':''}>
    <div class="swal-modo-ico">✅</div>
    <div class="swal-modo-nm">Abierta</div>
    <div class="swal-modo-ds">Ven y piden</div>
  </label>
  <label class="swal-modo-opt ${modoActual==='solo_vista'?'sel-solo_vista':''}" data-v="solo_vista">
    <input type="radio" name="sm" value="solo_vista" ${modoActual==='solo_vista'?'checked':''}>
    <div class="swal-modo-ico">👁️</div>
    <div class="swal-modo-nm">Cerrado para pedidos</div>
    <div class="swal-modo-ds">Ven, sin pedidos</div>
  </label>
  <label class="swal-modo-opt ${modoActual==='cerrada'?'sel-cerrada':''}" data-v="cerrada">
    <input type="radio" name="sm" value="cerrada" ${modoActual==='cerrada'?'checked':''}>
    <div class="swal-modo-ico">⛔</div>
    <div class="swal-modo-nm">Cerrada</div>
    <div class="swal-modo-ds">Página offline</div>
  </label>
</div>
<div id="swalMsgWrap" style="display:${modoActual==='cerrada'?'block':'none'}">
  <label style="display:block;text-align:left;font-size:11px;font-weight:700;color:#888;margin-bottom:5px;text-transform:uppercase">Mensaje para los clientes</label>
  <textarea id="swalMensajeCierre" rows="2" style="width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:8px 10px;font-family:inherit;font-size:13px;resize:none;outline:none" placeholder="Ej: Hoy no atendemos. Volvemos mañana.">${_tiendaMensaje}</textarea>
</div>`,
        didOpen: () => {
            document.querySelectorAll('.swal-modo-opt').forEach(opt => {
                opt.addEventListener('click', () => {
                    const v = opt.dataset.v;
                    document.querySelectorAll('.swal-modo-opt').forEach(o => {
                        o.className = 'swal-modo-opt' + (o.dataset.v === v ? ` sel-${v}` : '');
                        o.querySelector('input').checked = o.dataset.v === v;
                    });
                    document.getElementById('swalMsgWrap').style.display = v === 'cerrada' ? 'block' : 'none';
                });
            });
        },
        preConfirm: () => ({
            modo:    document.querySelector('input[name="sm"]:checked')?.value ?? modoActual,
            mensaje: document.getElementById('swalMensajeCierre')?.value ?? '',
        }),
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText:  'Cancelar',
        confirmButtonColor: '#c88e99',
        cancelButtonColor:  '#aaa',
    });

    if (!formValues) return;
    await _tiendaSetModo(formValues.modo, formValues.mensaje);
}

async function _tiendaSetModo(modo, mensaje) {
    const r = await fetch('<?= URL_ADMIN ?>/api/tienda_estado.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ accion:'set_modo', modo, mensaje })
    });
    const d = await r.json();
    if (!d.ok) {
        if (typeof Swal !== 'undefined') Swal.fire({ icon:'warning', title:'Error', text: d.msg, confirmButtonColor:'#c88e99' });
        return;
    }
    _tiendaModo      = d.modo        ?? modo;
    _tiendaModoConf  = d.modo_config ?? modo;
    _tiendaMensaje   = d.mensaje     ?? mensaje;
    _tiendaEnHorario = d.en_horario  ?? _tiendaEnHorario;
    tiendaRenderBtn();
    if (typeof Swal !== 'undefined') {
        const labels = { abierta:'Tienda abierta ✅', solo_vista:'Cerrado para pedidos 👁️', cerrada:'Tienda cerrada ⛔' };
        Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer:2500,
            icon: _tiendaModo === 'abierta' ? 'success' : 'warning',
            title: labels[_tiendaModo] ?? 'Listo' });
    }
}

document.addEventListener('DOMContentLoaded', () => { try { tiendaCargarEstado(); } catch(e) {} });
</script>

<div class="main-content">