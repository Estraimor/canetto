<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===========================
   VERIFICAR SESIÓN ACTIVA
=========================== */

if (!isset($_SESSION['usuario_id'])) {
    header("Location: /canetto/login/login.php");
    exit;
}

/* ===========================
   CONFIG NAV
=========================== */

if (!isset($pageTitle)) {
    $pageTitle = "Canetto Admin";
}

$baseUrl = "/canetto";
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
    <div class="menu-group <?= str_contains($current,'stock') || str_contains($current,'materias_primas') ? 'open' : '' ?>">
        <span class="menu-title">
            <i class="fa-solid fa-boxes-stacked"></i>
            Stock
        </span>
        <div class="submenu">
            <a href="<?= $baseUrl ?>/administracion/stock/index.php"
               class="<?= str_contains($current,'productos') ? 'active' : '' ?>">
                <i class="fa-solid fa-cookie-bite"></i>
                Productos
            </a>

            <a href="<?= $baseUrl ?>/administracion/materias_primas/index.php"
               class="<?= str_contains($current,'materias_primas') ? 'active' : '' ?>">
                <i class="fa-solid fa-seedling"></i>
                Materias primas
            </a>

            
        </div>
    </div>

    <!-- VENTAS -->
    <div class="menu-group <?= str_contains($current,'ventas') ? 'open' : '' ?>">
        <span class="menu-title">
            <i class="fa-solid fa-cart-shopping"></i>
            Ventas
        </span>
        <div class="submenu">
            <a href="<?= $baseUrl ?>/ventas/pedidos.php"
               class="<?= str_contains($current,'pedidos') ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i>
                Pedidos
            </a>
            <a href="<?= $baseUrl ?>/administracion/ventas/ventas/index.php"
               class="<?= str_contains($current,'pedidos') ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i>
                Ventas
            </a>
<a href="<?= $baseUrl ?>/administracion/ventas/ventas/Historial/index.php"
               class="<?= str_contains($current,'pedidos') ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i>
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
            <a href="<?= $baseUrl ?>/configuraciones/usuarios.php"
               class="<?= str_contains($current,'usuarios') ? 'active' : '' ?>">
                <i class="fa-solid fa-gears"></i>
                Configuraciones Generales
            </a>

            <a href="<?= $baseUrl ?>/configuraciones/sucursales.php"
               class="<?= str_contains($current,'sucursales') ? 'active' : '' ?>">
                <i class="fa-solid fa-building"></i>
                Sucursales
            </a>

            <a href="<?= $baseUrl ?>/ventas/metodos_pago.php"
               class="<?= str_contains($current,'metodos_pago') ? 'active' : '' ?>">
                <i class="fa-solid fa-credit-card"></i>
                Métodos de pago
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
    </div>

    <div class="right">
        <i class="fa-regular fa-user"></i>
        <span>Luciano</span>
    </div>
</header>