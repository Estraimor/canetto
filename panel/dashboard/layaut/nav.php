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

$_navHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl  = in_array($_navHost, ['localhost', '127.0.0.1'], true) ? '/canetto' : '';
unset($_navHost);
$current = $_SERVER['PHP_SELF'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $pageTitle ?></title>

<link rel="stylesheet" href="<?= $baseUrl ?>/assets/dashboard.css">
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
<aside class="sidebar">
    <div class="brand">Canetto</div>

    <nav class="nav-menu">

    <!-- DASHBOARD -->
    <a href="<?= $baseUrl ?>/administracion/index.php"
       class="<?= str_contains($current,'administracion/index.php') ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i>
        <span>Dashboard</span>
    </a>

    <!-- PRODUCCIÓN -->
    <div class="menu-group <?= str_contains($current,'produccion') ? 'open' : '' ?>">
        <span class="menu-title">
            <i class="fa-solid fa-industry"></i>
            Producción
        </span>
        <div class="submenu">
            <a href="<?= $baseUrl ?>/produccion/plan_produccion.php"
               class="<?= str_contains($current,'plan_produccion') ? 'active' : '' ?>">
                <i class="fa-solid fa-calendar-check"></i>
                Planificación
            </a>

            <a href="<?= $baseUrl ?>/administracion/produccion/congelado/index.php"
               class="<?= str_contains($current,'administracion/produccion/congelado/index') ? 'active' : '' ?>">
                <i class="fa-solid fa-snowflake"></i>
                Masa congelada
            </a>

            <a href="<?= $baseUrl ?>/administracion/produccion/horneado/index.php"
               class="<?= str_contains($current,'administracion/produccion/horneado') ? 'active' : '' ?>">
                <i class="fa-solid fa-fire"></i>
                Horneado
            </a>

        </div>
    </div>

    <!-- RECETAS -->
    <div class="menu-group <?= str_contains($current,'recetas') ? 'open' : '' ?>">
        <span class="menu-title">
            <i class="fa-solid fa-book-open"></i>
            Recetas
        </span>
        <div class="submenu">
            <a href="<?= $baseUrl ?>/administracion/recetas/index.php"
               class="<?= str_contains($current,'administracion/recetas/index') ? 'active' : '' ?>">
                <i class="fa-solid fa-list"></i>
                Listado
            </a>

            
        </div>
    </div>


    <!-- PRODUCTOS -->
<div class="menu-group <?= str_contains($current,'productos') ? 'open' : '' ?>">
    <span class="menu-title">
        <i class="fa-solid fa-cookie-bite"></i>
        Productos
    </span>
    <div class="submenu">

        <a href="<?= $baseUrl ?>/administracion/productos/index.php"
           class="<?= str_contains($current,'administracion/productos/index') ? 'active' : '' ?>">
            <i class="fa-solid fa-list"></i>
            Listado
        </a>

        <a href="<?= $baseUrl ?>/administracion/productos/crear_producto.php"
           class="<?= str_contains($current,'administracion/productos/crear_producto') ? 'active' : '' ?>">
            <i class="fa-solid fa-plus"></i>
            Crear producto
        </a>

    </div>
</div>


    <!-- STOCK -->
    <div class="menu-group <?= str_contains($current,'stock') || str_contains($current,'materias_primas') || str_contains($current,'packaging') ? 'open' : '' ?>">
        <span class="menu-title">
            <i class="fa-solid fa-boxes-stacked"></i>
            Stock
        </span>
        <div class="submenu">
            <a href="<?= $baseUrl ?>/administracion/stock/index.php"
               class="<?= str_contains($current,'administracion/stock') ? 'active' : '' ?>">
                <i class="fa-solid fa-cookie-bite"></i>
                Productos
            </a>

            <a href="<?= $baseUrl ?>/administracion/materias_primas/index.php"
               class="<?= str_contains($current,'materias_primas') ? 'active' : '' ?>">
                <i class="fa-solid fa-seedling"></i>
                Materias primas
            </a>

            <a href="<?= $baseUrl ?>/administracion/packaging/index.php"
               class="<?= str_contains($current,'packaging') ? 'active' : '' ?>">
                <i class="fa-solid fa-box-open"></i>
                Packaging
            </a>
        </div>
    </div>

    <!-- ANALÍTICA -->
    <a href="<?= $baseUrl ?>/administracion/analitica/index.php"
       class="<?= str_contains($current,'analitica') ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-bar"></i>
        <span>Analítica</span>
    </a>

    <!-- VENTAS -->
    <div class="menu-group <?= str_contains($current,'Ventas') || str_contains($current,'ventas') ? 'open' : '' ?>">
        <span class="menu-title">
            <i class="fa-solid fa-cart-shopping"></i>
            Ventas
        </span>
        <div class="submenu">
            <a href="<?= $baseUrl ?>/administracion/Ventas/Pedidos/index.php"
               class="<?= str_contains($current,'Pedidos') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock"></i>
                Pedidos activos
            </a>
            <a href="<?= $baseUrl ?>/administracion/Ventas/Ventas/index.php"
               class="<?= str_contains($current,'Ventas/Ventas/index') ? 'active' : '' ?>">
                <i class="fa-solid fa-plus-circle"></i>
                Nueva venta
            </a>
            <a href="<?= $baseUrl ?>/administracion/Ventas/Ventas/Historial/index.php"
               class="<?= str_contains($current,'Historial') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                Historial de ventas
            </a>
        </div>
    </div>

    <!-- COMPRAS -->
    <div class="menu-group <?= str_contains($current,'compras') ? 'open' : '' ?>">
        <span class="menu-title">
            <i class="fa-solid fa-truck"></i>
            Compras
        </span>
        <div class="submenu">
            <a href="<?= $baseUrl ?>/administracion/proveedor/index.php"
               class="<?= str_contains($current,'proveedores') ? 'active' : '' ?>">
                <i class="fa-solid fa-handshake"></i>
                Proveedores
            </a>

        </div>
    </div>

    <!-- configuraciones -->
    <div class="menu-group <?= str_contains($current,'configuraciones') ? 'open' : '' ?>">
        <span class="menu-title">
            <i class="fa-solid fa-gears"></i>
            Configuraciones
        </span>
        <div class="submenu">
            <a href="<?= $baseUrl ?>/configuraciones/index.php"
               class="<?= str_ends_with($current,'configuraciones/index.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-grip"></i>
                Panel general
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/usuarios.php"
               class="<?= str_contains($current,'configuraciones/usuarios') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i>
                Usuarios
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/roles.php"
               class="<?= str_contains($current,'configuraciones/roles.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-shield-halved"></i>
                Roles
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/roles_usuario.php"
               class="<?= str_contains($current,'roles_usuario') ? 'active' : '' ?>">
                <i class="fa-solid fa-user-tag"></i>
                Roles por usuario
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/metodos_pago.php"
               class="<?= str_contains($current,'metodos_pago') ? 'active' : '' ?>">
                <i class="fa-solid fa-credit-card"></i>
                Métodos de pago
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/sucursales.php"
               class="<?= str_contains($current,'sucursales') ? 'active' : '' ?>">
                <i class="fa-solid fa-building"></i>
                Sucursales
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/ofertas.php"
               class="<?= str_contains($current,'ofertas') ? 'active' : '' ?>">
                <i class="fa-solid fa-tag"></i>
                Ofertas Tienda
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/repartidores.php"
               class="<?= str_contains($current,'repartidores') ? 'active' : '' ?>">
                <i class="fa-solid fa-motorcycle"></i>
                Repartidores
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/auditoria.php"
               class="<?= str_contains($current,'auditoria') ? 'active' : '' ?>">
                <i class="fa-solid fa-clipboard-list"></i>
                Auditoría
            </a>
        </div>
    </div>


</nav>
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

        <i class="fa-regular fa-user"></i>
        <span><?= htmlspecialchars(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')) ?></span>
        <a href="<?= URL_LOGIN ?>/logout.php" title="Cerrar sesión" style="color:inherit;text-decoration:none;margin-left:6px;opacity:.6;transition:opacity .2s" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.6">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</header>