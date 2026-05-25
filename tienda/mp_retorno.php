<?php
/**
 * tienda/mp_retorno.php
 * Página de retorno después de pagar con MercadoPago.
 */
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/mp_config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$status   = $_GET['status']   ?? 'pending';
$pedidoId = (int)($_GET['pedido'] ?? 0);

// MP incluye collection_status en la URL de retorno.
$mpCollStatus = $_GET['collection_status'] ?? $_GET['payment_status'] ?? '';
if ($status === 'pending' && $mpCollStatus === 'approved') {
    $status = 'success';
}

// Si sigue siendo pending pero vino collection_id/payment_id, consultar MP directamente
// para no depender solo del webhook (que puede tardar >60s).
if ($status === 'pending') {
    $collectionId = $_GET['collection_id'] ?? $_GET['payment_id'] ?? '';
    if ($collectionId) {
        $ch = curl_init("https://api.mercadopago.com/v1/payments/" . (int)$collectionId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . MP_ACCESS_TOKEN],
        ]);
        $mpResp = curl_exec($ch);
        $mpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($mpCode === 200) {
            $mpData   = json_decode($mpResp, true);
            $mpStatus = $mpData['status'] ?? '';
            if ($mpStatus === 'approved') {
                $status = 'success';
                // Actualizar el pedido ya que el webhook puede no haber llegado aún
                try {
                    $pdo = Conexion::conectar();
                    $pdo->prepare("UPDATE ventas SET estado_venta_idestado_venta = 1, updated_at = NOW() WHERE idventas = ?")
                        ->execute([$pedidoId]);
                } catch (Throwable $e) {}
            } elseif ($mpStatus === 'rejected' || $mpStatus === 'cancelled') {
                $status = 'failure';
            }
        }
    }
}

$clienteId = (int)($_SESSION['tienda_cliente_id'] ?? 0);

$msgs = [
    'success' => ['ic' => '🎉', 'titulo' => '¡Pago confirmado!',     'color' => '#2d8a4e', 'sub' => 'Tu pago fue procesado correctamente. ¡Gracias por tu compra!'],
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
<link rel="icon" type="image/png" href="https://canettocookies.com/img/Logo_Canetto_Cookie.png">
<style>
@font-face{font-family:"Speedee";src:url("https://canettocookies.com/assets/fonts/Speedee.ttf") format("truetype");font-weight:700;font-display:swap}
@font-face{font-family:"Speedee";src:url("https://canettocookies.com/assets/fonts/Speedee-Regular.otf") format("opentype");font-weight:400;font-display:swap}
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
<script>
(function () {
    const status    = '<?= $status ?>';
    const pedidoId  = <?= (int)$pedidoId ?>;
    const uid       = <?= $clienteId ?>;
    const ck        = uid ? 'canetto_cart_' + uid : 'canetto_cart_guest';

    // Limpiar el carrito en cualquier caso excepto failure:
    // el pedido ya fue creado en el sistema, no tiene sentido mantener los items.
    if (status !== 'failure') {
        localStorage.removeItem(ck);
    }

    if (status !== 'pending' || !pedidoId) return;

    // Pending: pollear hasta confirmar aprobación o rechazo (~3 minutos)
    let attempts = 0;
    const maxTries = 90; // 90 × 2s = 3 minutos

    const msgsOk   = { ic: '🎉', titulo: '¡Pago confirmado!',      color: '#2d8a4e', sub: 'Tu pago fue procesado correctamente. ¡Gracias por tu compra!' };
    const msgsFail = { ic: '❌', titulo: 'El pago no se completó', color: '#c0392b', sub: 'Hubo un problema con el pago. Podés intentar nuevamente o elegir otro método.' };

    function applyUI(info) {
        document.querySelector('.ic').textContent  = info.ic;
        const tit = document.querySelector('.titulo');
        tit.style.color = info.color;
        tit.textContent = info.titulo;
        document.querySelector('.sub').textContent = info.sub;
        const card = document.querySelector('.card');
        card.style.transition = 'box-shadow 0.4s';
        card.style.boxShadow  = info.color === '#2d8a4e'
            ? '0 8px 40px rgba(45,138,78,.18)'
            : '0 8px 40px rgba(192,57,43,.18)';
    }

    function poll() {
        fetch('api/check_pedido_estado.php?id=' + pedidoId + '&uid=' + uid)
            .then(r => r.json())
            .then(data => {
                if (!data.ok) { retry(); return; }
                if (data.estado_id === 1) {
                    applyUI(msgsOk);
                    // El carrito ya fue limpiado al cargar la página
                } else if (data.estado_id === 6) {
                    applyUI(msgsFail);
                } else {
                    retry();
                }
            })
            .catch(retry);
    }

    function retry() {
        attempts++;
        if (attempts < maxTries) setTimeout(poll, 2000);
    }

    // Primera consulta a 1.5s
    setTimeout(poll, 1500);
})();
</script>
</body>
</html>
