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
    ORDER BY
      CASE WHEN ev.idestado_venta IN (1,2,3) THEN 0 ELSE 1 END ASC,
      v.created_at DESC
    LIMIT 50
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
unset($p);

$eMap = [
    1 => ['lbl'=>'Recibido',        'cls'=>'ped-e1','ic'=>'clock',        'color'=>'#6366f1','light'=>'#eef2ff','border'=>'#818cf8'],
    2 => ['lbl'=>'En preparación',  'cls'=>'ped-e2','ic'=>'fire',         'color'=>'#d97706','light'=>'#fffbeb','border'=>'#fbbf24'],
    3 => ['lbl'=>'En camino',       'cls'=>'ped-e3','ic'=>'motorcycle',   'color'=>'#2563eb','light'=>'#eff6ff','border'=>'#60a5fa'],
    4 => ['lbl'=>'Entregado',       'cls'=>'ped-e4','ic'=>'circle-check', 'color'=>'#16a34a','light'=>'#f0fdf4','border'=>'#4ade80'],
];
$tl = [
    ['ic'=>'clock',       'lbl'=>'Recibido'],
    ['ic'=>'fire',        'lbl'=>'Preparando'],
    ['ic'=>'motorcycle',  'lbl'=>'En camino'],
    ['ic'=>'circle-check','lbl'=>'Entregado'],
];

$totalPedidos = count($pedidos);
$pendientes   = count(array_filter($pedidos, fn($p) => in_array((int)($p['estado_id'] ?? 0), [1,2])));
$enCamino     = count(array_filter($pedidos, fn($p) => (int)($p['estado_id'] ?? 0) === 3));
$entregados   = count(array_filter($pedidos, fn($p) => (int)($p['estado_id'] ?? 0) === 4));
$enCurso      = $pendientes + $enCamino;
$totalGastado = array_sum(array_column($pedidos, 'total'));

$meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];

// Push config para pasar al JS
require_once __DIR__ . '/../config/push_config.php';
$vapidPublic = PUSH_VAPID_PUBLIC;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Pedidos — Canetto</title>
<link rel="icon" type="image/png" href="/canetto/img/Logo_Canetto_Cookie.png">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ══════════════════════════════════════════
   MIS PEDIDOS — Estilos
══════════════════════════════════════════ */

/* ── Botón de notificaciones ── */
.notif-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 14px;
  border-radius: 22px;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  color: #555;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: all .15s;
  flex-shrink: 0;
}
.notif-toggle-btn:hover { border-color: #c88e99; color: #c88e99; }
.notif-toggle-btn.enabled {
  background: #c88e99;
  color: #fff;
  border-color: #c88e99;
}
.notif-toggle-btn.enabled:hover { background: #b87888; border-color: #b87888; }
.notif-toggle-btn i { font-size: 12px; }

/* ── Notif card lateral desktop ── */
.ped-notif-side-card {
  background: linear-gradient(135deg, #fdf8f9, #f9f0f3);
  border-radius: 20px;
  overflow: hidden;
  margin-bottom: 16px;
  border: 1.5px solid #f0e0e6;
}

/* ── Filter toggle wrapper (sticky en mobile) ── */
.ped-filter-wrap {
  background: #fff;
  border-bottom: 1px solid #f0f0f0;
}

/* Fila del botón toggle */
.ped-filter-toggle {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 16px;
}
.ped-ftoggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 18px;
  border-radius: 22px;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  color: #444;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: all .15s;
  flex-shrink: 0;
}
.ped-ftoggle-btn:hover    { border-color: #c88e99; color: #c88e99; }
.ped-ftoggle-btn.is-open  { background: #111; color: #fff; border-color: #111; }
.ped-ftoggle-icon { font-size: 10px; transition: transform .22s; }
.ped-ftoggle-btn.is-open .ped-ftoggle-icon { transform: rotate(180deg); }

.ped-fcurrent {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  color: #555;
  font-weight: 600;
  flex: 1;
  min-width: 0;
}
.ped-fcurrent-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ped-fcurrent-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f0f0f0;
  border-radius: 20px;
  padding: 2px 9px;
  font-size: 11px;
  font-weight: 800;
  color: #555;
  flex-shrink: 0;
}

/* Panel colapsable con las pills */
.ped-filter-panel {
  max-height: 0;
  overflow: hidden;
  transition: max-height .28s cubic-bezier(.4,0,.2,1);
}
.ped-filter-panel.is-open { max-height: 80px; }

/* Pills de filtro */
.ped-filter-bar {
  display: flex;
  gap: 8px;
  padding: 6px 16px 14px;
  overflow-x: auto;
  scrollbar-width: none;
  background: #fff;
}
.ped-filter-bar::-webkit-scrollbar { display: none; }

.ped-filter-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 30px;
  border: 1.5px solid #e5e7eb;
  background: #fff;
  color: #555;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all .15s;
  font-family: inherit;
  flex-shrink: 0;
}
.ped-filter-btn:hover { border-color: #c88e99; color: #c88e99; }
.ped-filter-btn.active { background: #111; color: #fff; border-color: #111; }
.ped-filter-btn.f-pendiente.active { background: #6366f1; border-color: #6366f1; }
.ped-filter-btn.f-camino.active    { background: #2563eb; border-color: #2563eb; }
.ped-filter-btn.f-entregado.active { background: #16a34a; border-color: #16a34a; }

.ped-filter-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,.1);
  border-radius: 20px;
  padding: 1px 8px;
  font-size: 11px;
  font-weight: 800;
  min-width: 20px;
}
.ped-filter-btn.active .ped-filter-count { background: rgba(255,255,255,.22); }

/* ── Mobile stats strip ── */
.ped-stats-strip {
  display: flex;
  gap: 10px;
  padding: 14px 16px;
  overflow-x: auto;
  scrollbar-width: none;
  background: linear-gradient(135deg, #fdf8f9 0%, #f9f0f3 100%);
  border-bottom: 1px solid #f0e8ea;
}
.ped-stats-strip::-webkit-scrollbar { display: none; }

.ped-stat-pill {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  background: #fff;
  border-radius: 14px;
  padding: 10px 16px;
  min-width: 76px;
  flex-shrink: 0;
  box-shadow: 0 1px 6px rgba(0,0,0,.07);
}
.ped-stat-pill-val { font-size: 20px; font-weight: 800; color: #111; line-height: 1; }
.ped-stat-pill-val.sm { font-size: 15px; }
.ped-stat-pill-lbl { font-size: 10px; color: #888; font-weight: 500; text-align: center; line-height: 1.3; margin-top: 3px; }
.ped-stat-pill.accent .ped-stat-pill-val { color: #c88e99; }
.ped-stat-pill.green  .ped-stat-pill-val { color: #16a34a; }

/* ── Cards ── */
.ped-card {
  background: #fff;
  border-radius: 16px;
  margin-bottom: 12px;
  box-shadow: 0 1px 8px rgba(0,0,0,.06);
  border-left: 4px solid #e5e7eb;
  overflow: hidden;
  transition: opacity .2s, transform .2s;
}
.ped-card.hidden { display: none; }
.ped-card[data-ec="1"] { border-left-color: #818cf8; }
.ped-card[data-ec="2"] { border-left-color: #fbbf24; }
.ped-card[data-ec="3"] { border-left-color: #60a5fa; }
.ped-card[data-ec="4"] { border-left-color: #4ade80; }

/* Card header */
.ped-card-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px 12px;
  gap: 10px;
}
.ped-card-num { font-size: 15px; font-weight: 800; color: #111; }
.ped-card-date { font-size: 11px; color: #999; margin-top: 2px; }

.ped-card-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
  flex-shrink: 0;
  border-width: 1.5px;
  border-style: solid;
}

/* ── Timeline horizontal ── */
.ped-tl {
  display: flex;
  align-items: flex-start;
  padding: 10px 16px 14px;
}
.ped-tl-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  position: relative;
  gap: 5px;
}
.ped-tl-step::before {
  content: '';
  position: absolute;
  top: 12px;
  left: calc(-50% + 13px);
  right: calc(50% + 13px);
  height: 2px;
  background: #e9ecef;
}
.ped-tl-step:first-child::before { display: none; }
.ped-tl-step.tl-done::before { background: #c88e99; }
.ped-tl-step.tl-active::before { background: linear-gradient(90deg, #c88e99 60%, #e9ecef 60%); }

.ped-tl-dot {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: 2px solid #e9ecef;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: #ccc;
  z-index: 1;
  transition: all .2s;
}
.ped-tl-step.tl-done .ped-tl-dot { background: #c88e99; border-color: #c88e99; color: #fff; }
.ped-tl-step.tl-active .ped-tl-dot {
  border-color: #c88e99;
  color: #c88e99;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(200,142,153,.2);
}
.ped-tl-lbl { font-size: 9px; color: #bbb; text-align: center; line-height: 1.2; font-weight: 500; }
.ped-tl-step.tl-done .ped-tl-lbl   { color: #c88e99; font-weight: 700; }
.ped-tl-step.tl-active .ped-tl-lbl { color: #c88e99; font-weight: 800; }

/* ── Items ── */
.ped-items { padding: 2px 16px 10px; border-top: 1px solid #f8f5f6; }
.ped-item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid #f8f5f6;
  font-size: 12px;
}
.ped-item-row:last-child { border-bottom: none; }
.ped-item-name { font-weight: 600; color: #333; }
.ped-item-qty  { color: #999; font-size: 11px; margin-left: 4px; }
.ped-item-price { font-weight: 700; color: #111; flex-shrink: 0; }

/* ── Breakdown ── */
.ped-breakdown { padding: 10px 16px; border-top: 1px solid #f5f5f5; font-size: 12px; color: #666; }
.ped-breakdown-row { display: flex; justify-content: space-between; padding: 4px 0; }
.ped-breakdown-row.bdr-total {
  font-weight: 800; color: #111; font-size: 14px;
  padding-top: 8px; margin-top: 4px; border-top: 1px solid #f0f0f0;
}

/* ── Total simple ── */
.ped-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  border-top: 1px solid #f5f5f5;
  font-size: 13px;
}
.ped-total-label { color: #888; font-weight: 500; }
.ped-total-val   { font-weight: 800; font-size: 15px; color: #111; }

/* ── Pago al recibir ── */
.ped-pago-recibir {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px 16px;
  background: #f0fdf4;
  border-top: 1px solid #bbf7d0;
  font-size: 12px;
  color: #166534;
}
.ped-pago-recibir i { color: #22c55e; margin-top: 1px; flex-shrink: 0; }

/* ── Sucursal ── */
.ped-sucursal {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 7px 16px;
  font-size: 11px;
  color: #888;
  border-top: 1px solid #f5f5f5;
}

/* ── Confirm button ── */
.ped-btn-confirmar {
  display: block;
  width: calc(100% - 32px);
  margin: 4px 16px 14px;
  padding: 13px;
  background: #c88e99;
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: background .15s;
}
.ped-btn-confirmar:hover { background: #b87888; }

/* ── Empty states ── */
.ped-empty {
  text-align: center;
  padding: 60px 20px;
  color: #888;
}
.ped-empty-ic {
  width: 72px;
  height: 72px;
  background: #f5f4f1;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  color: #ccc;
  font-size: 28px;
}
.ped-empty h3 { font-size: 16px; font-weight: 700; color: #333; margin: 0 0 6px; }
.ped-empty p  { font-size: 13px; color: #888; margin: 0 0 24px; }
.ped-empty-cta {
  display: inline-block;
  background: #c88e99;
  color: #fff;
  padding: 13px 28px;
  border-radius: 30px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
}

/* ── Sidebar helpers ── */
.ped-help-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 12px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
  transition: background .15s;
}

/* ═══════════════════════════════════
   DESKTOP (≥ 1024px)
═══════════════════════════════════ */
@media (min-width: 1024px) {
  .bottom-nav { display: none !important; }
  body.has-bottom-nav { padding-bottom: 0; }
  #page-wrap { padding-bottom: 0; }
  .t-btn-label { display: inline !important; }

  /* Hide mobile-only elements */
  #pedHdMobile  { display: none !important; }
  .ped-stats-strip { display: none; }

  /* Filter toggle en desktop */
  .ped-filter-wrap {
    max-width: 1100px;
    margin: 0 auto;
    border: none;
    background: transparent;
    padding: 0 52px;
    margin-bottom: 8px;
  }
  .ped-filter-toggle { padding: 0 0 12px; }
  .ped-filter-bar    { padding: 6px 0 14px; }

  /* Page header desktop */
  .ped-page-hd {
    max-width: 1100px;
    margin: 0 auto;
    padding: 36px 52px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f0f0f0;
    margin-bottom: 32px;
  }
  .ped-page-hd-left h1 { font-size: 28px; font-weight: 800; color: #111; margin: 0 0 4px; }
  .ped-page-hd-left p  { font-size: 14px; color: #888; margin: 0; }

  .ped-desk-cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #111;
    color: #fff;
    padding: 12px 24px;
    border-radius: 22px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 700;
    transition: background .18s;
  }
  .ped-desk-cta:hover { background: #c88e99; }

  /* Grid layout */
  .ped-outer {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 52px 80px;
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 32px;
    align-items: start;
  }
  .ped-col-main { min-width: 0; }
  .ped-col-side { position: sticky; top: 90px; }

  /* Bigger cards on desktop */
  .ped-card { border-radius: 20px; margin-bottom: 16px; box-shadow: 0 2px 16px rgba(0,0,0,.06); }
  .ped-card-hd    { padding: 18px 22px 14px; }
  .ped-card-num   { font-size: 16px; }
  .ped-card-badge { font-size: 12px; padding: 6px 14px; }
  .ped-tl         { padding: 12px 22px 16px; }
  .ped-items      { padding: 4px 22px 12px; }
  .ped-item-row   { font-size: 13px; }
  .ped-breakdown  { padding: 12px 22px; }
  .ped-total-row  { padding: 12px 22px; }
  .ped-sucursal   { padding: 8px 22px; }
  .ped-pago-recibir { padding: 12px 22px; font-size: 13px; }
  .ped-btn-confirmar { width: calc(100% - 44px); margin: 4px 22px 16px; }

  /* Sidebar */
  .ped-side-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    overflow: hidden;
    margin-bottom: 16px;
  }
  .ped-side-hd {
    padding: 16px 20px;
    border-bottom: 1px solid #f5f5f5;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #555;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .ped-side-hd i { color: #c88e99; }
  .ped-side-body { padding: 16px 20px; }
  .ped-stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 9px 0;
    border-bottom: 1px solid #f8f5f6;
    font-size: 13px;
  }
  .ped-stat-row:last-child { border-bottom: none; }
  .ped-stat-lbl { color: #888; font-weight: 600; }
  .ped-stat-val { font-weight: 800; color: #111; }

  body.has-bottom-nav .t-footer { display: block; }
}

/* ═══════════════════════════════════
   MOBILE (< 1024px)
═══════════════════════════════════ */
@media (max-width: 1023px) {
  .ped-page-hd { display: none; }
  .ped-outer   { display: block; padding: 0; }
  .ped-col-side { display: none; }
  .ped-col-main { padding: 12px 16px 90px; }

  .ped-filter-wrap {
    position: sticky;
    top: 56px;
    z-index: 20;
  }
}
</style>

<!-- Nav principal -->
<header class="t-nav">
  <a href="index.php" class="t-brand">
    <div class="t-brand-icon">
      <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" class="t-brand-logo">
    </div>
    <span class="t-brand-name">Canetto</span>
  </a>
  <div class="t-actions" style="display:flex;align-items:center;gap:8px">
    <a href="mi-cuenta.php" class="t-btn" title="Mi cuenta"><i class="fa-solid fa-user" style="font-size:15px"></i></a>
    <a href="index.php" class="t-btn" title="Tienda" style="font-size:12px;font-weight:700;padding:0 16px;border-radius:22px;width:auto">
      <i class="fa-solid fa-cart-shopping" style="font-size:14px"></i><span class="t-btn-label"> Tienda</span>
    </a>
  </div>
</header>

<!-- Header mobile -->
<div class="page-hd" style="display:flex;align-items:center;justify-content:space-between" id="pedHdMobile">
  <div style="display:flex;align-items:center;gap:10px">
    <a href="index.php" class="back-btn">←</a>
    <div>
      <div class="page-title">Mis pedidos</div>
      <div style="font-size:12px;color:#888">Hola, <?= htmlspecialchars($cliente_nombre) ?></div>
    </div>
  </div>
  <button class="notif-toggle-btn" id="notifBtnMobile" onclick="toggleNotificaciones()" title="Notificaciones de pedidos" style="margin-right:8px">
    <i class="fa-solid fa-bell" id="notifIconMobile"></i>
    <span id="notifLabelMobile" style="display:none">Activas</span>
  </button>
</div>

<!-- Header desktop -->
<div class="ped-page-hd">
  <div class="ped-page-hd-left">
    <h1>Mis pedidos</h1>
    <p>Hola, <?= htmlspecialchars($cliente_nombre) ?> — acá podés seguir todos tus pedidos</p>
  </div>
  <a href="index.php" class="ped-desk-cta">
    <i class="fa-solid fa-cart-shopping"></i> Ir a la tienda
  </a>
</div>

<!-- Filtros — siempre visibles -->
<div class="ped-filter-wrap" id="pedFilterWrap">

  <!-- Botón toggle -->
  <div class="ped-filter-toggle">
    <button class="ped-ftoggle-btn" id="pedFToggleBtn" onclick="toggleFiltros()">
      <i class="fa-solid fa-sliders"></i>
      <span>Filtrar</span>
      <i class="fa-solid fa-chevron-down ped-ftoggle-icon" id="pedFChevron"></i>
    </button>
    <div class="ped-fcurrent">
      <span class="ped-fcurrent-name" id="pedFCurrentLabel">Todos los pedidos</span>
      <span class="ped-fcurrent-badge" id="pedFCurrentCount"><?= $totalPedidos ?></span>
    </div>
  </div>

  <!-- Panel de pills (colapsable) -->
  <div class="ped-filter-panel" id="pedFilterPanel">
    <div class="ped-filter-bar">
      <button class="ped-filter-btn active" onclick="filtrar(this,'todos','Todos los pedidos',<?= $totalPedidos ?>)">
        <i class="fa-solid fa-list" style="font-size:10px"></i>
        Todos <span class="ped-filter-count"><?= $totalPedidos ?></span>
      </button>
      <button class="ped-filter-btn f-pendiente" onclick="filtrar(this,'pendiente','Pendientes',<?= $pendientes ?>)">
        <i class="fa-solid fa-clock" style="font-size:10px"></i>
        Pendientes <span class="ped-filter-count"><?= $pendientes ?></span>
      </button>
      <button class="ped-filter-btn f-camino" onclick="filtrar(this,'camino','En camino',<?= $enCamino ?>)">
        <i class="fa-solid fa-motorcycle" style="font-size:10px"></i>
        En camino <span class="ped-filter-count"><?= $enCamino ?></span>
      </button>
      <button class="ped-filter-btn f-entregado" onclick="filtrar(this,'entregado','Entregados',<?= $entregados ?>)">
        <i class="fa-solid fa-circle-check" style="font-size:10px"></i>
        Entregados <span class="ped-filter-count"><?= $entregados ?></span>
      </button>
    </div>
  </div>

</div>

<?php if (!empty($pedidos)): ?>
<!-- Stats strip (mobile) -->
<div class="ped-stats-strip">
  <div class="ped-stat-pill">
    <span class="ped-stat-pill-val"><?= $totalPedidos ?></span>
    <span class="ped-stat-pill-lbl">Pedidos</span>
  </div>
  <div class="ped-stat-pill accent">
    <span class="ped-stat-pill-val"><?= $enCurso ?></span>
    <span class="ped-stat-pill-lbl">En curso</span>
  </div>
  <div class="ped-stat-pill green">
    <span class="ped-stat-pill-val"><?= $entregados ?></span>
    <span class="ped-stat-pill-lbl">Entregados</span>
  </div>
  <div class="ped-stat-pill">
    <span class="ped-stat-pill-val sm">$<?= number_format($totalGastado, 0, ',', '.') ?></span>
    <span class="ped-stat-pill-lbl">Total gastado</span>
  </div>
</div>
<?php endif; ?>

<!-- Layout principal -->
<div class="ped-outer">
<div class="ped-col-main">

<?php if (empty($pedidos)): ?>
<div class="ped-empty">
  <div class="ped-empty-ic"><i class="fa-solid fa-box-open"></i></div>
  <h3>No tenés pedidos todavía</h3>
  <p>¡Hacé tu primer pedido y lo seguís desde acá!</p>
  <a href="index.php" class="ped-empty-cta">Ver productos</a>
</div>

<?php else: ?>

<!-- Estado vacío por filtro -->
<div class="ped-empty" id="pedEmptyFilter" style="display:none">
  <div class="ped-empty-ic"><i class="fa-solid fa-magnifying-glass"></i></div>
  <h3 id="pedEmptyFilterTitle">Sin resultados</h3>
  <p id="pedEmptyFilterMsg">No hay pedidos en este filtro.</p>
</div>

<!-- Lista de pedidos -->
<?php foreach ($pedidos as $p):
  $eid        = (int)($p['estado_id'] ?? 1);
  $e          = $eMap[$eid] ?? $eMap[1];
  $mpNombre   = strtolower($p['metodo_pago'] ?? '');
  $esEfectivo = str_contains($mpNombre, 'efectivo') || str_contains($mpNombre, 'cash');
  $esEnvio    = ($p['tipo_entrega'] ?? 'retiro') === 'envio';
  $costoEnvio = (float)($p['costo_envio'] ?? 0);
  $subtotal   = $p['total'] - $costoEnvio;
  $pagAlRec   = $eid === 1 && $esEfectivo && $esEnvio;
  if (in_array($eid, [1,2]))  $filtroVal = 'pendiente';
  elseif ($eid === 3)          $filtroVal = 'camino';
  else                         $filtroVal = 'entregado';

  $ts       = strtotime($p['created_at'] ?? $p['fecha']);
  $fechaFmt = date('j', $ts) . ' ' . $meses[date('n', $ts) - 1] . ' · ' . date('H:i', $ts);
?>
<div class="ped-card" data-filtro="<?= $filtroVal ?>" data-ec="<?= $eid ?>">

  <!-- Cabecera de la card -->
  <div class="ped-card-hd">
    <div>
      <div class="ped-card-num">Pedido #<?= $p['idventas'] ?></div>
      <div class="ped-card-date"><?= $fechaFmt ?></div>
    </div>
    <?php if ($pagAlRec): ?>
      <span class="ped-card-badge" style="background:#f0fdf4;color:#166534;border-color:#bbf7d0">
        <i class="fa-solid fa-handshake"></i> Pago al recibir
      </span>
    <?php else: ?>
      <span class="ped-card-badge" style="background:<?= $e['light'] ?>;color:<?= $e['color'] ?>;border-color:<?= $e['border'] ?>">
        <i class="fa-solid fa-<?= $e['ic'] ?>"></i> <?= htmlspecialchars($p['estado_nombre'] ?? $e['lbl']) ?>
      </span>
    <?php endif; ?>
  </div>

  <!-- Aviso pago en efectivo -->
  <?php if ($pagAlRec): ?>
  <div class="ped-pago-recibir">
    <i class="fa-solid fa-circle-info"></i>
    <div>Abonás <strong>$<?= number_format($p['total'], 0, ',', '.') ?></strong> en efectivo al momento de recibir. No necesitás pagar nada ahora.</div>
  </div>
  <?php endif; ?>

  <!-- Timeline -->
  <div class="ped-tl">
    <?php foreach ($tl as $si => $step):
      $sn     = $si + 1;
      $done   = $eid > $sn;
      $active = $eid === $sn;
      $cls    = $done ? 'tl-done' : ($active ? 'tl-active' : '');
    ?>
    <div class="ped-tl-step <?= $cls ?>">
      <div class="ped-tl-dot">
        <?= $done
          ? '<i class="fa-solid fa-check"></i>'
          : '<i class="fa-solid fa-'.$step['ic'].'"></i>' ?>
      </div>
      <div class="ped-tl-lbl"><?= $step['lbl'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Ítems del pedido -->
  <?php if (!empty($p['items'])): ?>
  <div class="ped-items">
    <?php foreach ($p['items'] as $it): ?>
    <div class="ped-item-row">
      <span>
        <span class="ped-item-name"><?= htmlspecialchars($it['nombre'] ?? '—') ?></span>
        <span class="ped-item-qty">×<?= (int)$it['cantidad'] ?></span>
      </span>
      <span class="ped-item-price">$<?= number_format($it['precio_unitario'] * $it['cantidad'], 0, ',', '.') ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Desglose o total simple -->
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
    <div class="ped-breakdown-row bdr-total">
      <span>Total</span>
      <span>$<?= number_format($p['total'], 0, ',', '.') ?></span>
    </div>
  </div>
  <?php else: ?>
  <div class="ped-total-row">
    <span class="ped-total-label"><?= htmlspecialchars($p['metodo_pago'] ?? 'Efectivo') ?></span>
    <span class="ped-total-val">$<?= number_format($p['total'], 0, ',', '.') ?></span>
  </div>
  <?php endif; ?>

  <!-- Sucursal de retiro -->
  <?php if (!empty($p['sucursal_nombre'])): ?>
  <div class="ped-sucursal">
    <i class="fa-solid fa-location-dot" style="color:#c88e99"></i>
    Retiro en: <strong><?= htmlspecialchars($p['sucursal_nombre']) ?></strong>
  </div>
  <?php endif; ?>

  <!-- Confirmar entrega -->
  <?php if ($eid === 3): ?>
  <div>
    <button class="ped-btn-confirmar" onclick="confirmarEntrega(<?= $p['idventas'] ?>, this)">
      <i class="fa-solid fa-circle-check"></i> Confirmar que lo recibí
    </button>
  </div>
  <?php endif; ?>

</div><!-- /ped-card -->
<?php endforeach; endif; ?>

</div><!-- /ped-col-main -->

<!-- Sidebar desktop -->
<aside class="ped-col-side">
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

  <!-- Card de notificaciones (desktop) -->
  <div class="ped-notif-side-card ped-side-card">
    <div class="ped-side-hd" style="border-color:#f0e0e6"><i class="fa-solid fa-bell" style="color:#c88e99"></i> Notificaciones</div>
    <div class="ped-side-body">
      <p style="font-size:12px;color:#888;margin:0 0 12px;line-height:1.5">
        Recibí una notificación en tu celular o PC cada vez que tu pedido cambie de estado.
      </p>
      <button class="notif-toggle-btn" id="notifBtnDesktop" onclick="toggleNotificaciones()" style="width:100%;justify-content:center">
        <i class="fa-solid fa-bell" id="notifIconDesktop"></i>
        <span id="notifLabelDesktop">Activar notificaciones</span>
      </button>
      <p style="font-size:10px;color:#bbb;margin:8px 0 0;text-align:center" id="notifSubtextDesktop">
        Se activa en este dispositivo
      </p>
    </div>
  </div>

  <div class="ped-side-card">
    <div class="ped-side-hd"><i class="fa-solid fa-circle-info"></i> ¿Necesitás ayuda?</div>
    <div class="ped-side-body" style="display:flex;flex-direction:column;gap:10px">
      <a href="https://wa.me/3764820012" target="_blank"
         class="ped-help-link"
         style="color:#166534;background:#f0fdf4"
         onmouseover="this.style.background='#dcfce7'"
         onmouseout="this.style.background='#f0fdf4'">
        <i class="fa-brands fa-whatsapp" style="font-size:18px;color:#22c55e"></i>
        Contactar por WhatsApp
      </a>
      <a href="index.php"
         class="ped-help-link"
         style="color:#c88e99;background:#fdf0f3"
         onmouseover="this.style.background='#f9dde3'"
         onmouseout="this.style.background='#fdf0f3'">
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
const FILTRO_EMPTY = {
  pendiente: { title: 'Sin pedidos pendientes',  msg: 'No tenés pedidos en preparación ahora.' },
  camino:    { title: 'Nada en camino',           msg: 'Ningún pedido está siendo entregado.' },
  entregado: { title: 'Sin compras entregadas',   msg: 'Todavía no tenés pedidos completados.' },
};

function toggleFiltros() {
  const panel  = document.getElementById('pedFilterPanel');
  const btn    = document.getElementById('pedFToggleBtn');
  const isOpen = panel.classList.contains('is-open');
  panel.classList.toggle('is-open', !isOpen);
  btn.classList.toggle('is-open', !isOpen);
}

function filtrar(btn, filtro, label, count) {
  // Actualizar pills activas
  document.querySelectorAll('.ped-filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  // Actualizar label del botón toggle
  const nameEl  = document.getElementById('pedFCurrentLabel');
  const countEl = document.getElementById('pedFCurrentCount');
  if (nameEl)  nameEl.textContent  = label  || 'Todos los pedidos';
  if (countEl) countEl.textContent = count !== undefined ? count : '—';

  // Filtrar cards
  const cards = document.querySelectorAll('.ped-card[data-filtro]');
  let visible = 0;
  cards.forEach(card => {
    const show = filtro === 'todos' || card.dataset.filtro === filtro;
    card.classList.toggle('hidden', !show);
    if (show) visible++;
  });

  // Estado vacío por filtro
  const emptyEl = document.getElementById('pedEmptyFilter');
  const titleEl = document.getElementById('pedEmptyFilterTitle');
  const msgEl   = document.getElementById('pedEmptyFilterMsg');
  if (emptyEl) {
    const hasOrders = cards.length > 0;
    emptyEl.style.display = (hasOrders && visible === 0) ? '' : 'none';
    if (FILTRO_EMPTY[filtro]) {
      if (titleEl) titleEl.textContent = FILTRO_EMPTY[filtro].title;
      if (msgEl)   msgEl.textContent   = FILTRO_EMPTY[filtro].msg;
    }
  }

  // Cerrar panel después de seleccionar
  const panel = document.getElementById('pedFilterPanel');
  const tBtn  = document.getElementById('pedFToggleBtn');
  panel.classList.remove('is-open');
  tBtn.classList.remove('is-open');
}

// ── Web Push Notifications ─────────────────────────────────────
const VAPID_PUBLIC_KEY = '<?= htmlspecialchars($vapidPublic) ?>';

function urlB64ToUint8(b64) {
  const pad = '='.repeat((4 - b64.length % 4) % 4);
  const b64s = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/');
  return Uint8Array.from(atob(b64s), c => c.charCodeAt(0));
}

function setNotifUI(enabled) {
  ['Mobile','Desktop'].forEach(s => {
    const btn   = document.getElementById('notifBtn'   + s);
    const icon  = document.getElementById('notifIcon'  + s);
    const label = document.getElementById('notifLabel' + s);
    const sub   = document.getElementById('notifSubtext' + s);
    if (!btn) return;
    btn.classList.toggle('enabled', enabled);
    if (icon)  icon.className  = enabled ? 'fa-solid fa-bell-slash' : 'fa-solid fa-bell';
    if (label) { label.style.display = ''; label.textContent = enabled ? 'Desactivar' : 'Activar notificaciones'; }
    if (sub)   sub.textContent = enabled ? '✓ Activo en este dispositivo' : 'Se activa en este dispositivo';
  });
}

async function checkNotifStatus() {
  if (!('Notification' in window) || !('serviceWorker' in navigator)) return;
  if (Notification.permission !== 'granted') return;
  const reg = await navigator.serviceWorker.getRegistration('./sw.js').catch(() => null);
  if (!reg) return;
  const sub = await reg.pushManager.getSubscription().catch(() => null);
  if (sub) setNotifUI(true);
}

async function toggleNotificaciones() {
  if (!('Notification' in window) || !('serviceWorker' in navigator)) {
    alert('Tu navegador no soporta notificaciones push.'); return;
  }

  const reg = await navigator.serviceWorker.register('./sw.js', { scope: './' });
  await navigator.serviceWorker.ready;
  const existing = await reg.pushManager.getSubscription().catch(() => null);

  if (existing) {
    // Desactivar
    await existing.unsubscribe();
    await fetch('./api/suscribir_push.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...existing.toJSON(), activo: 0 }),
    }).catch(() => {});
    setNotifUI(false);
    return;
  }

  // Activar — pedir permiso
  const perm = await Notification.requestPermission();
  if (perm !== 'granted') {
    alert('Necesitamos tu permiso para enviarte notificaciones de tus pedidos.'); return;
  }

  try {
    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlB64ToUint8(VAPID_PUBLIC_KEY),
    });
    const res = await fetch('./api/suscribir_push.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(sub),
    });
    const data = await res.json();
    if (data.ok) {
      setNotifUI(true);
    } else {
      alert('No se pudo activar. Intentá de nuevo.');
    }
  } catch (e) {
    alert('Error al activar notificaciones: ' + e.message);
  }
}

// Verificar estado al cargar
checkNotifStatus();
// ──────────────────────────────────────────────────────────────────

async function confirmarEntrega(idVenta, btn) {
  if (!confirm('¿Confirmás que recibiste tu pedido #' + idVenta + '?')) return;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Confirmando...';
  try {
    const fd = new FormData();
    fd.append('id_venta', idVenta);
    const res  = await fetch('api/marcar_entregado.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      const card  = btn.closest('.ped-card');
      const badge = card.querySelector('.ped-card-badge');
      badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Entregado';
      badge.style.cssText = 'background:#f0fdf4;color:#16a34a;border:1.5px solid #4ade80;border-style:solid';
      card.dataset.ec      = '4';
      card.dataset.filtro  = 'entregado';
      card.style.borderLeftColor = '#4ade80';
      btn.closest('div').remove();
      card.querySelectorAll('.ped-tl-step').forEach(s => {
        s.classList.remove('tl-active');
        s.classList.add('tl-done');
        s.querySelector('.ped-tl-dot').innerHTML = '<i class="fa-solid fa-check"></i>';
      });
      card.querySelectorAll('.ped-tl-step::before');
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
