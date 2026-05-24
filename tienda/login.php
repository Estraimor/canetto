<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$retorno = isset($_GET['retorno']) ? '?retorno=' . urlencode($_GET['retorno']) : '';
redirect(URL_LOGIN . '/login_clientes.php' . $retorno);
