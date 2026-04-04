<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['tienda_cliente_id'])) {
    header('Location: login.php'); exit;
}

$pdo = Conexion::conectar();
$uid = (int)$_SESSION['tienda_cliente_id'];
$cliente_nombre = $_SESSION['tienda_cliente_nombre'] ?? 'Cliente';

// Ensure tienda columns exist (safe, idempotent)
foreach ([
    "ALTER TABLE ventas ADD COLUMN origen VARCHAR(20) NOT NULL DEFAULT 'pos'",
    "ALTER TABLE ventas ADD COLUMN sucursal_retiro_idsucursal INT NULL",
    "ALTER TABLE ventas ADD COLUMN observacion_cliente TEXT NULL",
] as $sql) { try { $pdo->exec($sql); } catch (Throwable $e) {} }

$stmtP = $pdo->prepare("
    SELECT v.idventas, v.total, v.fecha, v.created_at,
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
    1 => ['lbl'=>'Pendiente',       'cls'=>'ped-e1','ic'=>'⏳'],
    2 => ['lbl'=>'En preparación',  'cls'=>'ped-e2','ic'=>'👨‍🍳'],
    3 => ['lbl'=>'En camino',       'cls'=>'ped-e3','ic'=>'🚀'],
    4 => ['lbl'=>'Entregado',       'cls'=>'ped-e4','ic'=>'✅'],
];
$tl = [
    ['ic'=>'⏳','lbl'=>'Pendiente'],
    ['ic'=>'👨‍🍳','lbl'=>'Preparando'],
    ['ic'=>'🚀','lbl'=>'En camino'],
    ['ic'=>'✅','lbl'=>'Entregado'],
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
<body>

<header class="t-nav">
  <a href="index.php" class="t-brand">
    <div class="t-brand-icon">🍪</div>
    <span class="t-brand-name">Canetto</span>
  </a>
  <div class="t-actions">
    <a href="api/auth.php?action=logout_redirect" class="t-btn" title="Cerrar sesión">🚪</a>
  </div>
</header>

<div class="page-hd">
  <a href="index.php" class="back-btn">←</a>
  <div>
    <div class="page-title">Mis pedidos</div>
    <div style="font-size:12px;color:#888">Hola, <?= htmlspecialchars($cliente_nombre) ?> 👋</div>
  </div>
</div>

<div style="padding:16px 20px 40px">
<?php if (empty($pedidos)): ?>
  <div style="text-align:center;padding:60px 20px;color:#888">
    <div style="font-size:52px;margin-bottom:14px">📦</div>
    <div style="font-size:15px;font-weight:700;margin-bottom:8px">No tenés pedidos todavía</div>
    <div style="font-size:13px;margin-bottom:24px">¡Hacé tu primer pedido y lo seguís desde acá!</div>
    <a href="index.php" style="display:inline-block;background:#111;color:#fff;padding:13px 28px;border-radius:30px;text-decoration:none;font-size:14px;font-weight:700">Ver productos 🍪</a>
  </div>
<?php else: foreach ($pedidos as $p):
  $eid = (int)($p['estado_id'] ?? 1);
  $e   = $eMap[$eid] ?? $eMap[1];
?>
<div class="ped-card">
  <div class="ped-hd">
    <div>
      <div class="ped-id">Pedido #<?= $p['idventas'] ?></div>
      <div class="ped-date"><?= date('d/m/Y H:i', strtotime($p['created_at'] ?? $p['fecha'])) ?></div>
    </div>
    <span class="ped-estado <?= $e['cls'] ?>"><?= $e['ic'] ?> <?= htmlspecialchars($p['estado_nombre'] ?? $e['lbl']) ?></span>
  </div>

  <div class="timeline">
    <?php foreach ($tl as $si => $step):
      $sn     = $si + 1;
      $done   = $eid > $sn;
      $active = $eid === $sn;
    ?>
    <div class="tl-step <?= $done ? 'done' : ($active ? 'active' : '') ?>">
      <div class="tl-dot"><?= $done ? '✓' : $step['ic'] ?></div>
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

  <div class="ped-total">
    <span><?= htmlspecialchars($p['metodo_pago'] ?? 'Efectivo') ?></span>
    <span>$<?= number_format($p['total'], 0, ',', '.') ?></span>
  </div>

  <?php if (!empty($p['sucursal_nombre'])): ?>
  <div style="padding:8px 16px;font-size:12px;color:#888;border-top:1px solid #f5f5f5">
    📍 Retiro en: <strong><?= htmlspecialchars($p['sucursal_nombre']) ?></strong>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; endif; ?>
</div>

<footer class="t-footer">
  <div class="t-footer-brand">Canetto</div>
  <div class="t-footer-copy">&copy; <?= date('Y') ?> Canetto. Todos los derechos reservados.</div>
</footer>
</body>
</html>
