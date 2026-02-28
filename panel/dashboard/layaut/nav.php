<?php
if (!isset($pageTitle)) {
    $pageTitle = "Canetto Admin";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="/canetto/assets/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">


<!-- datatables  -->



<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

<!-- DataTables JS -->
 

<!-- Responsive (opcional pero recomendable) -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

</head>
<body>

<!-- LOADER -->
<div id="loader">
    <div class="loader-content">
        <div class="logo-loader">Canetto</div>
        <div class="spinner"></div>
        <p>Cargando panel...</p>
    </div>
</div>


<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">Canetto</div>

        <nav class="nav-menu">

    <!-- DASHBOARD -->
    <a href="/canetto/administracion/index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i>
        <span>Dashboard</span>
    </a>

    <!-- PRODUCCIÓN -->
    <div class="menu-group">
        <span class="menu-title">
            <i class="fa-solid fa-industry"></i>
            Producción
        </span>
        <div class="submenu">
            <a href="plan_produccion.php">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Planificación</span>
            </a>

            <a href="masa_congelada.php">
                <i class="fa-solid fa-snowflake"></i>
                <span>Masa congelada</span>
            </a>

            <a href="horneado.php">
                <i class="fa-solid fa-fire"></i>
                <span>Horneado</span>
            </a>

            <a href="mermas.php">
                <i class="fa-solid fa-trash"></i>
                <span>Mermas</span>
            </a>
        </div>
    </div>

    <!-- RECETAS -->
    <div class="menu-group">
        <span class="menu-title">
            <i class="fa-solid fa-book-open"></i>
            Recetas
        </span>
        <div class="submenu">
            <a href="recetas.php">
                <i class="fa-solid fa-list"></i>
                <span>Listado</span>
            </a>

            <a href="crear_receta.php">
                <i class="fa-solid fa-plus"></i>
                <span>Crear receta</span>
            </a>
        </div>
    </div>

    <!-- STOCK -->
    <div class="menu-group">
        <span class="menu-title">
            <i class="fa-solid fa-boxes-stacked"></i>
            Stock
        </span>
        <div class="submenu">
            <a href="productos.php">
                <i class="fa-solid fa-cookie-bite"></i>
                <span>Productos</span>
            </a>

            <a href="../administracion/materias_primas/index.php">
                <i class="fa-solid fa-seedling"></i>
                <span>Materias primas</span>
            </a>

            <a href="movimientos_stock.php">
                <i class="fa-solid fa-right-left"></i>
                <span>Movimientos</span>
            </a>
        </div>
    </div>

    <!-- VENTAS -->
    <div class="menu-group">
        <span class="menu-title">
            <i class="fa-solid fa-cart-shopping"></i>
            Ventas
        </span>
        <div class="submenu">
            <a href="pedidos.php">
                <i class="fa-solid fa-receipt"></i>
                <span>Pedidos</span>
            </a>

            <a href="metodos_pago.php">
                <i class="fa-solid fa-credit-card"></i>
                <span>Métodos de pago</span>
            </a>
        </div>
    </div>

    <!-- COMPRAS -->
    <div class="menu-group">
        <span class="menu-title">
            <i class="fa-solid fa-truck"></i>
            Compras
        </span>
        <div class="submenu">
            <a href="proveedores.php">
                <i class="fa-solid fa-handshake"></i>
                <span>Proveedores</span>
            </a>

            <a href="registrar_compra.php">
                <i class="fa-solid fa-file-invoice"></i>
                <span>Registrar compra</span>
            </a>
        </div>
    </div>

</nav>
    </aside>

    <!-- MAIN -->
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