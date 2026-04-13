<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
redirect(URL_LOGIN . '/login_clientes.php');
