<?php
/**
 * tienda/mp_retorno.php
 * Página de retorno después de pagar con MercadoPago.
 */
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$status   = $_GET['status']   ?? 'pending';
$pedidoId = (int)($_GET['pedido'] ?? 0);

$msgs = [
    'success' => ['ic' => '', 'titulo' => '¡Pago confirmado!',     'color' => '#2d8a4e', 'sub' => 'Tu pago fue procesado correctamente. ¡Gracias por tu compra!'],
    'failure' => ['ic' => '❌', 'titulo' => 'El pago no se completó', 'color' => '#c0392b', 'sub' => 'Hubo un problema con el pago. Podés intentar nuevamente o elegir otro método.'],
    'pending' => ['ic' => '⏳', 'titulo' => 'Pago en proceso',        'color' => '#c88e99', 'sub' => 'Tu pago está siendo procesado. Te avisaremos cuando se confirme.'],
];
$info = $msgs[$status] ?? $msgs['pending'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Canetto — Estado del pago</title>
<link rel="icon" type="image/png" href="/canetto/img/Logo_Canetto_Cookie.png">
<style>
@font-face{font-family:"Speedee";src:url("../assets/fonts/Speedee.ttf") format("truetype");font-weight:700;font-display:swap}
@font-face{font-family:"Speedee";src:url("../assets/fonts/Speedee-Regular.otf") format("opentype");font-weight:400;font-display:swap}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Speedee",sans-serif;background:#f8f9fa;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.card{background:#fff;border-radius:20px;padding:40px 32px;max-width:420px;width:100%;text-align:center;box-shadow:0 8px 40px rgba(0,0,0,.1)}
.brand{font-family:"Speedee",sans-serif;font-size:22px;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:#111;margin-bottom:28px}
.ic{font-size:52px;margin-bottom:16px}
.titulo{font-size:22px;font-weight:700;color:#1e293b;margin-bottom:10px}
.sub{font-size:14px;color:#64748b;line-height:1.6;margin-bottom:24px}
.pedido-num{font-size:13px;color:#94a3b8;margin-bottom:28px}
.btn{display:block;width:100%;padding:14px;border-radius:12px;border:none;font-family:inherit;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;text-align:center;margin-bottom:10px}
.btn-primary{background:#c88e99;color:#fff}
.btn-primary:hover{background:#a46678}
.btn-sec{background:#f1f5f9;color:#334155}
.btn-sec:hover{background:#e2e8f0}
</style>
</head>
<body>
<div class="card">
  <div class="brand">Canetto</div>
  <div class="ic"><?= $info['ic'] ?></div>
  <div class="titulo" style="color:<?= $info['color'] ?>"><?= $info['titulo'] ?></div>
  <div class="sub"><?= $info['sub'] ?></div>
  <?php if ($pedidoId): ?>
    <div class="pedido-num">Pedido #<?= $pedidoId ?></div>
  <?php endif; ?>

  <?php if ($status === 'failure'): ?>
    <a href="index.php" class="btn btn-primary">Volver a la tienda</a>
  <?php else: ?>
    <?php if (isset($_SESSION['tienda_cliente_id'])): ?>
      <a href="mis-pedidos.php" class="btn btn-primary">Ver mis pedidos</a>
    <?php endif; ?>
    <a href="index.php" class="btn btn-sec">← Seguir comprando</a>
  <?php endif; ?>
</div>
</body>
</html>
