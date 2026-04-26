<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['tienda_cliente_id'])) {
    redirect(URL_LOGIN . '/login_clientes.php');
}

$pdo = Conexion::conectar();
$uid = (int)$_SESSION['tienda_cliente_id'];
$cliente_nombre = $_SESSION['tienda_cliente_nombre'] ?? 'Cliente';

// Ensure tienda columns exist (safe, idempotent)
foreach ([
    "ALTER TABLE ventas ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT 'pos'",
    "ALTER TABLE ventas ADD COLUMN sucursal_retiro_idsucursal INT NULL",
    "ALTER TABLE ventas ADD COLUMN observacion_cliente TEXT NULL",
    "ALTER TABLE ventas ADD COLUMN tipo_entrega VARCHAR(10) NOT NULL DEFAULT 'retiro'",
    "ALTER TABLE ventas ADD COLUMN costo_envio DECIMAL(10,2) NOT NULL DEFAULT 0",
] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

$stmtP = $pdo->prepare("
    SELECT v.idventas, v.total, v.fecha, v.created_at,
           COALESCE(v.tipo_entrega, 'retiro')  AS tipo_entrega,
           COALESCE(v.costo_envio, 0)          AS costo_envio,
           ev.nombre AS estado_nombre, ev.idestado_venta AS estado_id,
           mp.nombre AS metodo_pago,
           s.nombre  AS sucursal_nombre
    FROM ventas v
    LEFT JOIN estado_venta ev ON ev.idestado_venta = v.estado_venta_idestado_venta
    LEFT JOIN metodo_pago  mp ON mp.idmetodo_pago  = v.metodo_pago_idmetodo_pago
    LEFT JOIN sucursal      s ON s.idsucursal       = v.sucursal_retiro_idsucursal
    WHERE v.usuario_idusuario = ?
    ORDER BY v.created_at DESC
    LIMIT 20
");
$stmtP->execute([$uid]);
$pedidos = $stmtP->fetchAll();

foreach ($pedidos as &$p) {
    $d = $pdo->prepare("
        SELECT d.cantidad, d.precio_unitario, pr.nombre
        FROM detalle_ventas d
        LEFT JOIN productos pr ON pr.idproductos = d.productos_idproductos
        WHERE d.ventas_idventas = ?
    ");
    $d->execute([$p['idventas']]);
    $p['items'] = $d->fetchAll();
}

$eMap = [
    1 => ['lbl'=>'Recibido',        'cls'=>'ped-e1','ic'=>'clock'],
    2 => ['lbl'=>'En preparación',  'cls'=>'ped-e2','ic'=>'fire'],
    3 => ['lbl'=>'En camino',       'cls'=>'ped-e3','ic'=>'motorcycle'],
    4 => ['lbl'=>'Entregado',       'cls'=>'ped-e4','ic'=>'circle-check'],
];
$tl = [
    ['ic'=>'clock',       'lbl'=>'Recibido'],
    ['ic'=>'fire',        'lbl'=>'Preparando'],
    ['ic'=>'motorcycle',  'lbl'=>'En camino'],
    ['ic'=>'circle-check','lbl'=>'Entregado'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Pedidos — Canetto</title>
<link rel="stylesheet" href="tienda.css">
</head>
<body class="has-bottom-nav">
<div id="page-wrap">

<header class="t-nav">
  <a href="index.php" class="t-brand">
    <div class="t-brand-icon">
      <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" class="t-brand-logo">
    </div>
    <span class="t-brand-name">Canetto</span>
  </a>
  <div class="t-actions" style="display:flex;align-items:center;gap:8px">
    <a href="mi-cuenta.php" class="t-btn" title="Mi cuenta"><i class="fa-solid fa-user" style="font-size:15px"></i></a>
    <a href="index.php" class="t-btn" title="Ir a la tienda" style="font-size:12px;font-weight:700;padding:0 16px;border-radius:22px;width:auto">
      <i class="fa-solid fa-cart-shopping" style="font-size:14px"></i><span class="t-btn-label"> Tienda</span>
    </a>
  </div>
</header>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ── Estilos inline de la página ── */
.ped-pago-recibir{display:flex;align-items:flex-start;gap:10px;margin:0 0 12px;padding:12px 14px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;font-size:13px;color:#166534}
.ped-pago-recibir i{color:#22c55e;margin-top:1px;flex-shrink:0}
.ped-breakdown{padding:12px 0 0;font-size:13px;color:#666}
.ped-breakdown-row{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f5f5f5}
.ped-breakdown-row:last-child{border-bottom:none}
.ped-breakdown-row.total{font-weight:800;color:#111;font-size:15px;padding-top:10px;margin-top:4px}
.tl-dot i{font-size:11px}
.ped-empty-ic{width:64px;height:64px;background:#f5f4f1;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:#ccc;font-size:26px}

/* ── Desktop layout ── */
@media(min-width:1024px){
  .bottom-nav{display:none!important}
  body.has-bottom-nav{padding-bottom:0}
  #page-wrap{padding-bottom:0}
  .t-btn-label{display:inline!important}

  /* Page header */
  .ped-page-hd{
    max-width:1100px;margin:0 auto;
    padding:36px 52px 0;
    display:flex;align-items:center;justify-content:space-between;
    border-bottom:1px solid #f0f0f0;padding-bottom:24px;margin-bottom:32px;
  }
  .ped-page-hd-left h1{font-family:'Speedee',sans-serif;font-size:28px;font-weight:700;color:#111;margin:0 0 4px}
  .ped-page-hd-left p{font-size:14px;color:#888;margin:0}

  /* Contenedor central */
  .ped-outer{max-width:1100px;margin:0 auto;padding:0 52px 80px;display:grid;grid-template-columns:1fr 320px;gap:32px;align-items:start}
  .ped-col-main{min-width:0}
  .ped-col-side{position:sticky;top:96px}

  /* Cards de pedidos más grandes */
  .ped-card{border-radius:20px;margin-bottom:16px;box-shadow:0 2px 16px rgba(0,0,0,.06)}
  .ped-hd{padding:18px 22px}
  .ped-id{font-size:15px}
  .ped-date{font-size:12px}
  .ped-items-wrap{padding:12px 22px 16px}
  .ped-item-row{font-size:13px;padding:5px 0}
  .ped-total{padding:14px 22px;font-size:15px}
  .ped-breakdown{padding:12px 22px 4px}
  .ped-pago-recibir{margin:0 22px 14px}
  .timeline{padding:16px 22px}

  /* Panel lateral de info */
  .ped-side-card{background:#fff;border-radius:20px;box-shadow:0 2px 16px rgba(0,0,0,.06);overflow:hidden;margin-bottom:16px}
  .ped-side-hd{padding:16px 20px;border-bottom:1px solid #f5f5f5;font-size:13px;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#555;display:flex;align-items:center;gap:8px}
  .ped-side-hd i{color:#c88e99}
  .ped-side-body{padding:16px 20px}
  .ped-stat-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f8f5f6;font-size:13px}
  .ped-stat-row:last-child{border-bottom:none}
  .ped-stat-lbl{color:#888;font-weight:600}
  .ped-stat-val{font-weight:800;color:#111}

  /* Footer */
  body.has-bottom-nav .t-footer{display:block}
}
@media(max-width:1023px){
  .ped-page-hd{display:none}
  .ped-outer{display:block;padding:0}
  .ped-col-side{display:none}
  .ped-col-main{padding:16px 20px 40px}
}
</style>

<!-- Header de página (mobile) -->
<div class="page-hd" style="display:flex" id="pedHdMobile">
  <a href="index.php" class="back-btn">←</a>
  <div>
    <div class="page-title">Mis pedidos</div>
    <div style="font-size:12px;color:#888">Hola, <?= htmlspecialchars($cliente_nombre) ?></div>
  </div>
</div>

<!-- Header desktop -->
<div class="ped-page-hd">
  <div class="ped-page-hd-left">
    <h1>Mis pedidos</h1>
    <p>Hola, <?= htmlspecialchars($cliente_nombre) ?> — acá podés seguir todos tus pedidos</p>
  </div>
  <a href="index.php" style="display:inline-flex;align-items:center;gap:8px;background:#111;color:#fff;padding:12px 24px;border-radius:22px;text-decoration:none;font-size:14px;font-weight:700;transition:background .18s" onmouseover="this.style.background='#c88e99'" onmouseout="this.style.background='#111'">
    <i class="fa-solid fa-cart-shopping"></i> Ir a la tienda
  </a>
</div>

<!-- Layout -->
<div class="ped-outer">
<div class="ped-col-main">
<div class="ped-list-wrap" style="display:contents">
<?php if (empty($pedidos)): ?>
  <div style="text-align:center;padding:60px 20px;color:#888">
    <div class="ped-empty-ic"><i class="fa-solid fa-box-open"></i></div>
    <div style="font-size:15px;font-weight:700;margin-bottom:8px;color:#333">No tenés pedidos todavía</div>
    <div style="font-size:13px;margin-bottom:24px">¡Hacé tu primer pedido y lo seguís desde acá!</div>
    <a href="index.php" style="display:inline-block;background:#c88e99;color:#fff;padding:13px 28px;border-radius:30px;text-decoration:none;font-size:14px;font-weight:600">Ver productos</a>
  </div>
<?php else: foreach ($pedidos as $p):
  $eid       = (int)($p['estado_id'] ?? 1);
  $e         = $eMap[$eid] ?? $eMap[1];
  $mpNombre  = strtolower($p['metodo_pago'] ?? '');
  $esEfectivo= str_contains($mpNombre, 'efectivo') || str_contains($mpNombre, 'cash');
  $esEnvio   = ($p['tipo_entrega'] ?? 'retiro') === 'envio';
  $costoEnvio= (float)($p['costo_envio'] ?? 0);
  $subtotal  = $p['total'] - $costoEnvio;
  $pagoAlRecibir = $eid === 1 && $esEfectivo && $esEnvio;
?>
<div class="ped-card">
  <div class="ped-hd">
    <div>
      <div class="ped-id">Pedido #<?= $p['idventas'] ?></div>
      <div class="ped-date"><?= date('d/m/Y H:i', strtotime($p['created_at'] ?? $p['fecha'])) ?></div>
    </div>
    <?php if ($pagoAlRecibir): ?>
      <span class="ped-estado" style="background:#dcfce7;color:#166534;border:1.5px solid #bbf7d0">
        <i class="fa-solid fa-handshake"></i> Pago al recibir
      </span>
    <?php else: ?>
      <span class="ped-estado <?= $e['cls'] ?>">
        <i class="fa-solid fa-<?= $e['ic'] ?>"></i> <?= htmlspecialchars($p['estado_nombre'] ?? $e['lbl']) ?>
      </span>
    <?php endif; ?>
  </div>

  <?php if ($pagoAlRecibir): ?>
  <div class="ped-pago-recibir">
    <i class="fa-solid fa-circle-info"></i>
    <div>Abonás <strong>$<?= number_format($p['total'], 0, ',', '.') ?></strong> en efectivo al momento de recibir tu pedido. No necesitás pagar nada ahora.</div>
  </div>
  <?php endif; ?>

  <div class="timeline">
    <?php foreach ($tl as $si => $step):
      $sn     = $si + 1;
      $done   = $eid > $sn;
      $active = $eid === $sn;
    ?>
    <div class="tl-step <?= $done ? 'done' : ($active ? 'active' : '') ?>">
      <div class="tl-dot"><?= $done ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-'.$step['ic'].'"></i>' ?></div>
      <div class="tl-lbl"><?= $step['lbl'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($p['items'])): ?>
  <div class="ped-items-wrap">
    <?php foreach ($p['items'] as $it): ?>
    <div class="ped-item-row">
      <strong><?= htmlspecialchars($it['nombre'] ?? '—') ?> × <?= (int)$it['cantidad'] ?></strong>
      <span>$<?= number_format($it['precio_unitario'] * $it['cantidad'], 0, ',', '.') ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($costoEnvio > 0): ?>
  <div class="ped-breakdown">
    <div class="ped-breakdown-row">
      <span><i class="fa-solid fa-cookie-bite" style="color:#c88e99;margin-right:5px"></i>Productos</span>
      <span>$<?= number_format($subtotal, 0, ',', '.') ?></span>
    </div>
    <div class="ped-breakdown-row">
      <span><i class="fa-solid fa-motorcycle" style="color:#6b7280;margin-right:5px"></i>Envío</span>
      <span>$<?= number_format($costoEnvio, 0, ',', '.') ?></span>
    </div>
    <div class="ped-breakdown-row total">
      <span>Total</span>
      <span>$<?= number_format($p['total'], 0, ',', '.') ?></span>
    </div>
  </div>
  <?php else: ?>
  <div class="ped-total">
    <span><?= htmlspecialchars($p['metodo_pago'] ?? 'Efectivo') ?></span>
    <span>$<?= number_format($p['total'], 0, ',', '.') ?></span>
  </div>
  <?php endif; ?>

  <?php if (!empty($p['sucursal_nombre'])): ?>
  <div style="padding:8px 16px;font-size:12px;color:#888;border-top:1px solid #f5f5f5">
    <i class="fa-solid fa-location-dot" style="color:#c88e99;margin-right:4px"></i>
    Retiro en: <strong><?= htmlspecialchars($p['sucursal_nombre']) ?></strong>
  </div>
  <?php endif; ?>

  <?php if ($eid === 3): ?>
  <div style="padding:12px 16px;border-top:1px solid #f5f5f5">
    <button class="btn-confirmar-entrega" data-id="<?= $p['idventas'] ?>"
      onclick="confirmarEntrega(<?= $p['idventas'] ?>, this)"
      style="width:100%;padding:13px;background:#c88e99;color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;">
      <i class="fa-solid fa-circle-check"></i> Confirmar que lo recibí
    </button>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div><!-- /ped-list-wrap -->
</div><!-- /ped-col-main -->

<!-- Sidebar desktop -->
<aside class="ped-col-side">
  <?php
    $totalPedidos   = count($pedidos);
    $totalGastado   = array_sum(array_column($pedidos, 'total'));
    $entregados     = count(array_filter($pedidos, fn($p)=>($p['estado_id']??0)==4));
    $enCurso        = $totalPedidos - $entregados;
  ?>
  <div class="ped-side-card">
    <div class="ped-side-hd"><i class="fa-solid fa-chart-bar"></i> Resumen</div>
    <div class="ped-side-body">
      <div class="ped-stat-row">
        <span class="ped-stat-lbl">Total pedidos</span>
        <span class="ped-stat-val"><?= $totalPedidos ?></span>
      </div>
      <div class="ped-stat-row">
        <span class="ped-stat-lbl">En curso</span>
        <span class="ped-stat-val" style="color:#c88e99"><?= $enCurso ?></span>
      </div>
      <div class="ped-stat-row">
        <span class="ped-stat-lbl">Entregados</span>
        <span class="ped-stat-val" style="color:#16a34a"><?= $entregados ?></span>
      </div>
      <div class="ped-stat-row">
        <span class="ped-stat-lbl">Total gastado</span>
        <span class="ped-stat-val">$<?= number_format($totalGastado, 0, ',', '.') ?></span>
      </div>
    </div>
  </div>

  <div class="ped-side-card">
    <div class="ped-side-hd"><i class="fa-solid fa-circle-info"></i> ¿Necesitás ayuda?</div>
    <div class="ped-side-body" style="display:flex;flex-direction:column;gap:10px">
      <?php $waPhone = preg_replace('/\D/', '', $pedidos[0]['sucursal_nombre'] ?? '3764820012'); ?>
      <a href="https://wa.me/3764820012" target="_blank"
         style="display:flex;align-items:center;gap:10px;padding:12px;background:#f0fdf4;border-radius:12px;text-decoration:none;color:#166534;font-size:13px;font-weight:700;transition:background .15s"
         onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
        <i class="fa-brands fa-whatsapp" style="font-size:18px;color:#22c55e"></i>
        Contactar por WhatsApp
      </a>
      <a href="index.php"
         style="display:flex;align-items:center;gap:10px;padding:12px;background:#fdf0f3;border-radius:12px;text-decoration:none;color:#c88e99;font-size:13px;font-weight:700;transition:background .15s"
         onmouseover="this.style.background='#f9dde3'" onmouseout="this.style.background='#fdf0f3'">
        <i class="fa-solid fa-cookie-bite" style="font-size:16px"></i>
        Hacer otro pedido
      </a>
    </div>
  </div>
</aside>

</div><!-- /ped-outer -->

<footer class="t-footer">
  <div class="t-footer-brand">Canetto</div>
  <div class="t-footer-tag">Cookies hechas con amor</div>
</footer>
</div><!-- /page-wrap -->

<nav class="bottom-nav">
  <a href="index.php" class="bn-item">
    <i class="fa-solid fa-house"></i>
    <span>Inicio</span>
  </a>
  <a href="mis-pedidos.php" class="bn-item active">
    <i class="fa-solid fa-bag-shopping"></i>
    <span>Mis pedidos</span>
  </a>
  <a href="index.php#sucursales" class="bn-item">
    <i class="fa-solid fa-location-dot"></i>
    <span>Sucursales</span>
  </a>
  <a href="mi-cuenta.php" class="bn-item">
    <i class="fa-solid fa-user"></i>
    <span>Mi cuenta</span>
  </a>
</nav>
<script>
async function confirmarEntrega(idVenta, btn) {
  if (!confirm('¿Confirmás que recibiste tu pedido #' + idVenta + '?')) return;
  btn.disabled = true;
  btn.textContent = '⏳ Confirmando...';
  try {
    const fd = new FormData();
    fd.append('id_venta', idVenta);
    const res  = await fetch('api/marcar_entregado.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      const estado = btn.closest('.ped-card').querySelector('.ped-estado');
      estado.innerHTML = '<i class="fa-solid fa-circle-check"></i> Entregado';
      estado.className = 'ped-estado ped-e4';
      btn.parentElement.remove();
      const steps = btn.closest('.ped-card')?.querySelectorAll('.tl-step');
      if (steps) steps.forEach(s => { s.classList.add('done'); s.querySelector('.tl-dot').innerHTML = '<i class="fa-solid fa-check"></i>'; });
    } else {
      alert(data.message || 'No se pudo confirmar');
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Confirmar que lo recibí';
    }
  } catch {
    alert('Error de conexión');
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Confirmar que lo recibí';
  }
}
</script>
<script src="transitions.js"></script>
</body>
</html>
