<?php
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

            <a href="<?= $baseUrl ?>/produccion/masa_congelada.php"
               class="<?= str_contains($current,'masa_congelada') ? 'active' : '' ?>">
                <i class="fa-solid fa-snowflake"></i>
                Masa congelada
            </a>

            <a href="<?= $baseUrl ?>/produccion/horneado.php"
               class="<?= str_contains($current,'horneado') ? 'active' : '' ?>">
                <i class="fa-solid fa-fire"></i>
                Horneado
            </a>

            <a href="<?= $baseUrl ?>/produccion/mermas.php"
               class="<?= str_contains($current,'mermas') ? 'active' : '' ?>">
                <i class="fa-solid fa-trash"></i>
                Mermas
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
            <a href="<?= $baseUrl ?>/recetas/recetas.php"
               class="<?= str_contains($current,'recetas.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-list"></i>
                Listado
            </a>

            <a href="<?= $baseUrl ?>/recetas/crear_receta.php"
               class="<?= str_contains($current,'crear_receta') ? 'active' : '' ?>">
                <i class="fa-solid fa-plus"></i>
                Crear receta
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
            <a href="<?= $baseUrl ?>/stock/productos.php"
               class="<?= str_contains($current,'productos') ? 'active' : '' ?>">
                <i class="fa-solid fa-cookie-bite"></i>
                Productos
            </a>

            <a href="<?= $baseUrl ?>/administracion/materias_primas/index.php"
               class="<?= str_contains($current,'materias_primas') ? 'active' : '' ?>">
                <i class="fa-solid fa-seedling"></i>
                Materias primas
            </a>

            <a href="<?= $baseUrl ?>/stock/movimientos_stock.php"
               class="<?= str_contains($current,'movimientos_stock') ? 'active' : '' ?>">
                <i class="fa-solid fa-right-left"></i>
                Movimientos
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

            <a href="<?= $baseUrl ?>/ventas/metodos_pago.php"
               class="<?= str_contains($current,'metodos_pago') ? 'active' : '' ?>">
                <i class="fa-solid fa-credit-card"></i>
                Métodos de pago
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
            <a href="<?= $baseUrl ?>/compras/proveedores.php"
               class="<?= str_contains($current,'proveedores') ? 'active' : '' ?>">
                <i class="fa-solid fa-handshake"></i>
                Proveedores
            </a>

            <a href="<?= $baseUrl ?>/compras/registrar_compra.php"
               class="<?= str_contains($current,'registrar_compra') ? 'active' : '' ?>">
                <i class="fa-solid fa-file-invoice"></i>
                Registrar compra
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