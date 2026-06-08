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
           COALESCE(v.toppings_json, '')        AS toppings_json,
           ev.nombre AS estado_nombre, ev.idestado_venta AS estado_id,
           mp.nombre AS metodo_pago,
           s.nombre  AS sucursal_nombre,
           v.lat_entrega, v.lng_entrega
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

    // Parsear toppings
    $p['toppings'] = [];
    if (!empty($p['toppings_json'])) {
        $tj = json_decode($p['toppings_json'], true);
        if (is_array($tj)) {
            foreach ($tj as $t) {
                if (!empty($t['nombre'])) {
                    $p['toppings'][] = ['nombre' => $t['nombre'], 'precio' => (float)($t['precio'] ?? 0)];
                }
            }
        }
    }
}
unset($p);

$eMap = [
    1 => ['lbl'=>'Recibido',          'cls'=>'ped-e1','ic'=>'clock',        'color'=>'#6366f1','light'=>'#eef2ff','border'=>'#818cf8'],
    2 => ['lbl'=>'En preparación',    'cls'=>'ped-e2','ic'=>'fire',         'color'=>'#d97706','light'=>'#fffbeb','border'=>'#fbbf24'],
    3 => ['lbl'=>'En camino',         'cls'=>'ped-e3','ic'=>'motorcycle',   'color'=>'#2563eb','light'=>'#eff6ff','border'=>'#60a5fa'],
    4 => ['lbl'=>'Entregado',         'cls'=>'ped-e4','ic'=>'circle-check', 'color'=>'#16a34a','light'=>'#f0fdf4','border'=>'#4ade80'],
    5 => ['lbl'=>'Verificando pago…', 'cls'=>'ped-e5','ic'=>'spinner fa-spin', 'color'=>'#c88e99','light'=>'#fdf0f3','border'=>'#e8b4c0'],
    6 => ['lbl'=>'Cancelado',         'cls'=>'ped-e6','ic'=>'ban',          'color'=>'#dc2626','light'=>'#fef2f2','border'=>'#fca5a5'],
];

// Número de WhatsApp para consultas de reembolso
$whatsappSoporte = '3764820012';
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
$cancelados   = count(array_filter($pedidos, fn($p) => (int)($p['estado_id'] ?? 0) === 6));
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
<link rel="icon" type="image/png" href="https://canettocookies.com/img/Logo_Canetto_Cookie.png">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ══════════════════════════════════════════
   MIS PEDIDOS — Estilos
══════════════════════════════════════════ */

/* Fondo sólido para evitar bleed del carousel/index */
html, body { background: #fff !important; }


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
.ped-filter-btn.f-cancelado.active { background: #dc2626; border-color: #dc2626; }

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
.ped-card[data-ec="5"] { border-left-color: #e8b4c0; }
.ped-card[data-ec="6"] { border-left-color: #fca5a5; }

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
/* Toppings */
.ped-item-topping { background: #fdf4f6; border-radius: 8px; padding: 5px 8px; margin: 2px 0; border-bottom: none !important; }
.ped-topping-ic   { margin-right: 4px; font-size: 12px; }
.ped-topping-tag  { display: inline-block; background: #f3d4da; color: #9b3a52; font-size: 10px;
                    font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: 5px;
                    text-transform: uppercase; letter-spacing: .04em; }

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

/* ── Tracking en tiempo real ── */
.ped-tracking {
  margin: 0 16px 14px;
  padding: 14px 16px;
  background: #eff6ff;
  border: 1.5px solid #60a5fa;
  border-radius: 14px;
}
.ped-tracking-header {
  display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
}
.ped-tracking-ic {
  font-size: 20px; color: #2563eb;
  animation: motorbike 1.2s ease-in-out infinite alternate;
}
@keyframes motorbike { from { transform: translateX(0); } to { transform: translateX(6px); } }
.ped-tracking-title { font-size: 13px; font-weight: 700; color: #1e40af; }
.ped-tracking-sub   { font-size: 12px; color: #3b82f6; margin-top: 2px; }
.ped-tracking-dist  {
  margin-left: auto; font-size: 13px; font-weight: 800; color: #1e40af;
  background: #dbeafe; padding: 4px 10px; border-radius: 50px; white-space: nowrap;
}
.ped-tracking-bar {
  height: 6px; background: #bfdbfe; border-radius: 6px; overflow: hidden;
}
.ped-tracking-bar-fill {
  height: 100%; width: 0%; background: #2563eb;
  border-radius: 6px; transition: width 1s ease;
}

/* ── Banner de pedido cancelado ── */
.ped-cancel-banner {
  margin: 0 16px 14px;
  padding: 16px;
  background: linear-gradient(135deg, #fef2f2, #fff5f5);
  border: 1.5px solid #fca5a5;
  border-radius: 14px;
}
.ped-cancel-top {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}
.ped-cancel-ic {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #fee2e2;
  color: #dc2626;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
}
.ped-cancel-title { font-size: 14px; font-weight: 800; color: #991b1b; margin-bottom: 3px; }
.ped-cancel-msg   { font-size: 12px; color: #b45361; line-height: 1.5; }
.ped-cancel-wa-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  margin-top: 14px;
  padding: 12px 16px;
  background: #25d366;
  color: #fff;
  border-radius: 11px;
  text-decoration: none;
  font-size: 13px;
  font-weight: 700;
  transition: background .15s, transform .1s;
}
.ped-cancel-wa-btn:hover  { background: #1fb855; }
.ped-cancel-wa-btn:active { transform: scale(.98); }
.ped-cancel-wa-btn i { font-size: 17px; }

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

/* ── Cancelar pedido (cliente) ── */
.ped-btn-cancelar-cliente {
  display: block;
  width: calc(100% - 32px);
  margin: 4px 16px 14px;
  padding: 12px;
  background: #fff;
  color: #dc2626;
  border: 1.5px solid #fca5a5;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: all .15s;
}
.ped-btn-cancelar-cliente:hover     { background: #fef2f2; border-color: #dc2626; }
.ped-btn-cancelar-cliente:disabled  { opacity: .6; cursor: default; }

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
    max-width: 1340px;
    margin: 0 auto;
    border: none;
    background: transparent;
    padding: 0 48px;
    margin-bottom: 8px;
  }
  .ped-filter-toggle { padding: 0 0 12px; }
  .ped-filter-bar    { padding: 6px 0 14px; }

  /* Page header desktop */
  .ped-page-hd {
    max-width: 1340px;
    margin: 0 auto;
    padding: 36px 48px 24px;
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
    max-width: 1340px;
    margin: 0 auto;
    padding: 0 48px 80px;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 36px;
    align-items: start;
  }
  .ped-col-main { min-width: 0; }
  .ped-col-side { position: sticky; top: 90px; }

  /* Bigger cards on desktop */
  .ped-card { border-radius: 22px; margin-bottom: 20px; box-shadow: 0 3px 24px rgba(0,0,0,.07); }
  .ped-card-hd    { padding: 22px 28px 16px; }
  .ped-card-num   { font-size: 20px; font-weight: 800; }
  .ped-card-date  { font-size: 13px; }
  .ped-card-badge { font-size: 13px; padding: 8px 18px; }
  .ped-tl         { padding: 14px 28px 20px; gap: 0; }
  .ped-tl-dot     { width: 36px; height: 36px; font-size: 14px; }
  .ped-tl-lbl     { font-size: 12px; margin-top: 6px; }
  .ped-items      { padding: 6px 28px 14px; }
  .ped-item-row   { font-size: 15px; padding: 9px 0; }
  .ped-item-name  { font-size: 15px; }
  .ped-item-qty   { font-size: 13px; }
  .ped-item-price { font-size: 15px; }
  .ped-topping-tag { font-size: 11px; padding: 2px 8px; }
  .ped-breakdown  { padding: 14px 28px; font-size: 15px; }
  .ped-breakdown-row { padding: 5px 0; }
  .bdr-total      { font-size: 18px !important; padding-top: 10px !important; margin-top: 6px; }
  .ped-total-row  { padding: 14px 28px; font-size: 16px; }
  .ped-sucursal   { padding: 10px 28px; font-size: 14px; }
  .ped-pago-recibir { padding: 14px 28px; font-size: 14px; }
  .ped-btn-confirmar { width: calc(100% - 56px); margin: 6px 28px 20px; font-size: 16px; padding: 16px; }
  .ped-btn-cancelar-cliente { width: calc(100% - 56px); margin: 6px 28px 20px; font-size: 14px; padding: 14px; }

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
</head>
<body>
<div id="page-wrap">

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
      <?php if ($cancelados > 0): ?>
      <button class="ped-filter-btn f-cancelado" onclick="filtrar(this,'cancelado','Cancelados',<?= $cancelados ?>)">
        <i class="fa-solid fa-ban" style="font-size:10px"></i>
        Cancelados <span class="ped-filter-count"><?= $cancelados ?></span>
      </button>
      <?php endif; ?>
    </div>
  </div>

</div>


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
  // El cliente puede cancelar mientras el pedido no salió de la tienda (no en camino/entregado/cancelado/verificando pago)
  $puedeCancelar = in_array($eid, [1, 2, 7], true);
  if (in_array($eid, [1,2]))  $filtroVal = 'pendiente';
  elseif ($eid === 3)          $filtroVal = 'camino';
  elseif ($eid === 6)          $filtroVal = 'cancelado';
  else                         $filtroVal = 'entregado';

  $ts       = strtotime($p['created_at'] ?? $p['fecha']);
  $fechaFmt = date('j', $ts) . ' ' . $meses[date('n', $ts) - 1] . ' · ' . date('H:i', $ts);
?>
<div class="ped-card" data-filtro="<?= $filtroVal ?>" data-ec="<?= $eid ?>" data-pedido-id="<?= $p['idventas'] ?>">

  <!-- Cabecera de la card -->
  <div class="ped-card-hd">
    <div>
      <div class="ped-card-num">Pedido #<?= $p['idventas'] ?></div>
      <div class="ped-card-date"><?= $fechaFmt ?></div>
    </div>
    <span class="ped-card-badge" style="background:<?= $e['light'] ?>;color:<?= $e['color'] ?>;border-color:<?= $e['border'] ?>">
      <i class="fa-solid fa-<?= $e['ic'] ?>"></i> <?= htmlspecialchars($p['estado_nombre'] ?? $e['lbl']) ?>
    </span>
  </div>

  <!-- Aviso pago MP pendiente -->
  <?php if ($eid === 5): ?>
  <div class="mp-verificando-banner" style="display:flex;align-items:center;gap:10px;background:#fdf0f3;border:1.5px solid #e8b4c0;border-radius:10px;padding:10px 14px;margin-bottom:10px;font-size:13px;color:#8a3550">
    <i class="fa-solid fa-spinner fa-spin" style="font-size:16px;color:#c88e99"></i>
    <div>
      <strong>Verificando tu pago con Mercado Pago…</strong><br>
      <span style="color:#b06080;font-size:12px">Esto se actualiza solo, no hace falta que recargues la página.</span>
    </div>
  </div>
  <?php endif; ?>

  <!-- Aviso de pedido cancelado -->
  <?php if ($eid === 6):
    $waMsg = "Hola! Te escribo por mi Pedido #{$p['idventas']}, figura como cancelado y quería consultar sobre el reembolso.";
    $waUrl = 'https://wa.me/' . $whatsappSoporte . '?text=' . rawurlencode($waMsg);
  ?>
  <div class="ped-cancel-banner">
    <div class="ped-cancel-top">
      <div class="ped-cancel-ic"><i class="fa-solid fa-ban"></i></div>
      <div>
        <div class="ped-cancel-title">Este pedido fue cancelado</div>
        <div class="ped-cancel-msg">Si ya habías abonado, no te preocupes: te ayudamos a gestionar el reembolso por WhatsApp en minutos.</div>
      </div>
    </div>
    <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener" class="ped-cancel-wa-btn">
      <i class="fa-brands fa-whatsapp"></i>
      Consultar reembolso por WhatsApp
    </a>
  </div>
  <?php else: ?>

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
  <?php endif; ?>

  <!-- Ítems del pedido -->
  <?php if (!empty($p['items']) || !empty($p['toppings'])): ?>
  <div class="ped-items">
    <?php foreach ($p['items'] as $it): ?>
    <div class="ped-item-row">
      <span>
        <span class="ped-item-name"><?= htmlspecialchars($it['nombre'] ?? '—') ?></span>
        <span class="ped-item-qty">×<?= (int)$it['cantidad'] ?></span>
      </span>
    </div>
    <?php endforeach; ?>
    <?php foreach ($p['toppings'] as $tp): ?>
    <div class="ped-item-row ped-item-topping">
      <span>
        <span class="ped-topping-ic">✨</span>
        <span class="ped-item-name"><?= htmlspecialchars($tp['nombre']) ?></span>
        <span class="ped-topping-tag">Topping</span>
      </span>
      <?php if ($tp['precio'] > 0): ?>
      <span class="ped-item-price" style="color:#c88e99">+$<?= number_format($tp['precio'], 0, ',', '.') ?></span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Total (+ envío si aplica) -->
  <div class="ped-breakdown">
    <?php if ($costoEnvio > 0): ?>
    <div class="ped-breakdown-row">
      <span><i class="fa-solid fa-motorcycle" style="color:#6b7280;margin-right:5px"></i>Envío</span>
      <span>$<?= number_format($costoEnvio, 0, ',', '.') ?></span>
    </div>
    <?php endif; ?>
    <div class="ped-breakdown-row bdr-total">
      <span>Total</span>
      <span>$<?= number_format($p['total'], 0, ',', '.') ?></span>
    </div>
  </div>

  <!-- Aviso pago en efectivo (al pie, después del total) -->
  <?php if ($pagAlRec): ?>
  <div class="ped-pago-recibir">
    <i class="fa-solid fa-coins"></i>
    <div>Pagás <strong>$<?= number_format($p['total'], 0, ',', '.') ?> en efectivo</strong> cuando lo recibas. No necesitás pagar nada ahora.</div>
  </div>
  <?php endif; ?>

  <!-- Sucursal de retiro -->
  <?php if (!empty($p['sucursal_nombre'])): ?>
  <div class="ped-sucursal">
    <i class="fa-solid fa-location-dot" style="color:#c88e99"></i>
    Retiro en: <strong><?= htmlspecialchars($p['sucursal_nombre']) ?></strong>
  </div>
  <?php endif; ?>

  <!-- Tracking en tiempo real (solo envío en camino) -->
  <?php if ($eid === 3 && $p['tipo_entrega'] === 'envio' && !empty($p['lat_entrega'])): ?>
  <div class="ped-tracking" id="tracking-<?= $p['idventas'] ?>"
       data-venta="<?= $p['idventas'] ?>"
       data-dest-lat="<?= (float)$p['lat_entrega'] ?>"
       data-dest-lng="<?= (float)$p['lng_entrega'] ?>">
    <div class="ped-tracking-header">
      <i class="fa-solid fa-motorcycle ped-tracking-ic"></i>
      <div>
        <div class="ped-tracking-title">Tu pedido está en camino</div>
        <div class="ped-tracking-sub" id="eta-<?= $p['idventas'] ?>">Calculando tiempo estimado…</div>
      </div>
      <div class="ped-tracking-dist" id="dist-<?= $p['idventas'] ?>">—</div>
    </div>
    <div class="ped-tracking-bar">
      <div class="ped-tracking-bar-fill" id="bar-<?= $p['idventas'] ?>"></div>
    </div>
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

  <!-- Cancelar pedido (cliente) -->
  <?php if ($puedeCancelar): ?>
  <div>
    <button class="ped-btn-cancelar-cliente"
            data-id="<?= $p['idventas'] ?>"
            data-efectivo="<?= $esEfectivo ? '1' : '0' ?>"
            onclick="cancelarPedidoCliente(this)">
      <i class="fa-solid fa-ban"></i> Cancelar pedido
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const FILTRO_EMPTY = {
  pendiente: { title: 'Sin pedidos pendientes',  msg: 'No tenés pedidos en preparación ahora.' },
  camino:    { title: 'Nada en camino',           msg: 'Ningún pedido está siendo entregado.' },
  entregado: { title: 'Sin compras entregadas',   msg: 'Todavía no tenés pedidos completados.' },
  cancelado: { title: 'Sin pedidos cancelados',   msg: 'Por suerte no tenés pedidos cancelados.' },
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
  const reg = await navigator.serviceWorker.getRegistration('./sw.php').catch(() => null);
  if (!reg) return;
  const sub = await reg.pushManager.getSubscription().catch(() => null);
  if (sub) setNotifUI(true);
}

async function toggleNotificaciones() {
  if (!('Notification' in window) || !('serviceWorker' in navigator)) {
    alert('Tu navegador no soporta notificaciones push.'); return;
  }
  if (localStorage.getItem('canetto_cookie_consent') !== 'all') {
    alert('Para activar notificaciones necesitamos que aceptes todas las cookies.'); return;
  }

  const reg = await navigator.serviceWorker.register('./sw.php', { scope: './' });
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

// ── Polling para pedidos MP pendientes de pago ──────────────────────
(function() {
  // Recopilar IDs de pedidos en estado 5 (Verificando pago MP)
  const pendientesMP = <?php
    $ids = array_values(array_map(fn($p) => (int)$p['idventas'],
      array_filter($pedidos, fn($p) => (int)($p['estado_id'] ?? 0) === 5)
    ));
    echo json_encode($ids);
  ?>;

  if (!pendientesMP.length) return;

  const LABELS = {
    1: { txt: 'Recibido',        ic: 'clock',         col: '#6366f1', bg: '#eef2ff', bd: '#818cf8' },
    2: { txt: 'En preparación',  ic: 'fire',          col: '#d97706', bg: '#fffbeb', bd: '#fbbf24' },
    3: { txt: 'En camino',       ic: 'motorcycle',    col: '#2563eb', bg: '#eff6ff', bd: '#60a5fa' },
    4: { txt: 'Entregado',       ic: 'circle-check',  col: '#16a34a', bg: '#f0fdf4', bd: '#4ade80' },
    5: { txt: 'Verificando pago…', ic: 'spinner fa-spin', col: '#c88e99', bg: '#fdf0f3', bd: '#e8b4c0' },
    6: { txt: 'Cancelado',        ic: 'ban',             col: '#dc2626', bg: '#fef2f2', bd: '#fca5a5' },
  };

  let activos = [...pendientesMP];
  let intentos = 0;
  const MAX_INTENTOS = 20; // ~1 min de polling

  async function verificar() {
    if (!activos.length || intentos >= MAX_INTENTOS) return;
    intentos++;

    const resueltos = [];
    for (const id of activos) {
      try {
        const r = await fetch(`api/check_pedido_estado.php?id=${id}`);
        const d = await r.json();
        if (!d.ok) continue;

        if (d.estado_id !== 5) {
          // Estado cambió — actualizar badge en pantalla
          const card  = document.querySelector(`.ped-card[data-pedido-id="${id}"]`);
          const badge = card?.querySelector('.ped-card-badge');
          const lbl   = LABELS[d.estado_id];
          if (badge && lbl) {
            badge.innerHTML = `<i class="fa-solid fa-${lbl.ic}"></i> ${lbl.txt}`;
            badge.style.cssText = `background:${lbl.bg};color:${lbl.col};border-color:${lbl.bd}`;
          }
          // Quitar banner "verificando pago"
          card?.querySelector('.mp-verificando-banner')?.remove();
          resueltos.push(id);
        }
      } catch(e) {}
    }

    activos = activos.filter(id => !resueltos.includes(id));
    if (activos.length) setTimeout(verificar, 3000); // cada 3 seg
  }

  // Empezar polling a los 2 seg (tiempo para que el webhook de MP llegue)
  setTimeout(verificar, 2000);
})();
// ──────────────────────────────────────────────────────────────────

// ── TRACKING EN TIEMPO REAL ──────────────────────────────────────
(function initTracking() {
  const cards = document.querySelectorAll('.ped-tracking');
  if (!cards.length) return;

  // Pedir permiso para notificaciones
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  const DIST_NOTIF_M = 50; // metros para notificar
  const notifEnviada = {};

  function haversineM(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  }

  function fmtETA(metros) {
    if (metros < 50)  return '¡Ya está llegando!';
    const mins = Math.ceil((metros / 1000) / 30 * 60); // 30 km/h promedio moto
    if (mins <= 1)    return 'Menos de 1 minuto';
    if (mins < 60)    return 'Aprox. ' + mins + ' min';
    const h = Math.floor(mins/60), m = mins%60;
    return h + 'h ' + (m ? m+'min' : '');
  }

  async function pollTracking(card) {
    const idVenta  = card.dataset.venta;
    const destLat  = parseFloat(card.dataset.destLat);
    const destLng  = parseFloat(card.dataset.destLng);

    try {
      const res  = await fetch('api/get_repartidor_ubicacion.php?id=' + idVenta);
      const data = await res.json();
      if (!data.ok || !data.rep_lat || !data.rep_lng) return;

      const distM = haversineM(data.rep_lat, data.rep_lng, destLat, destLng);
      const distStr = distM < 1000
        ? Math.round(distM) + ' m'
        : (distM/1000).toFixed(1) + ' km';

      const etaEl  = document.getElementById('eta-'  + idVenta);
      const distEl = document.getElementById('dist-' + idVenta);
      const barEl  = document.getElementById('bar-'  + idVenta);

      if (etaEl)  etaEl.textContent  = fmtETA(distM);
      if (distEl) distEl.textContent = distStr;

      // Barra de progreso (0 = lejos, 100% = llegó)
      if (barEl) {
        const maxDist = 5000; // 5km = 0%
        const pct = Math.min(100, Math.max(0, (1 - distM / maxDist) * 100));
        barEl.style.width = pct + '%';
      }

      // Notificación de proximidad
      if (distM <= DIST_NOTIF_M && !notifEnviada[idVenta]) {
        notifEnviada[idVenta] = true;
        if ('Notification' in window && Notification.permission === 'granted') {
          new Notification('🛵 Canetto — Tu pedido llegó', {
            body: '¡Tu pedido #' + idVenta + ' está en tu puerta!',
            icon: '<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png',
          });
        }
        // Resaltar visualmente
        card.style.background = '#dcfce7';
        card.style.borderColor = '#4ade80';
        const ti = card.querySelector('.ped-tracking-title');
        const si = card.querySelector('.ped-tracking-ic');
        if (ti) ti.textContent = '¡Tu pedido llegó!';
        if (si) { si.className = 'fa-solid fa-circle-check ped-tracking-ic'; si.style.color='#16a34a'; }
        const dist2 = card.querySelector('.ped-tracking-dist');
        if (dist2) { dist2.style.background='#bbf7d0'; dist2.style.color='#15803d'; }
        if (barEl) { barEl.style.width='100%'; barEl.style.background='#16a34a'; }
      }
    } catch(_) {}
  }

  // Arrancar polling cada 5 segundos para cada card de tracking
  cards.forEach(card => {
    pollTracking(card);
    setInterval(() => pollTracking(card), 5000);
  });
})();
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

// ── Cancelar pedido (cliente) ────────────────────────────────────
const WHATSAPP_SOPORTE = '<?= htmlspecialchars($whatsappSoporte) ?>';

async function cancelarPedidoCliente(btn) {
  const idVenta    = btn.dataset.id;
  const esEfectivo = btn.dataset.efectivo === '1';

  if (esEfectivo) {
    await cancelarPorEfectivo(idVenta, btn);
  } else {
    await cancelarPorMercadoPago(idVenta);
  }
}

async function cancelarPorEfectivo(idVenta, btn) {
  const c1 = await Swal.fire({
    title: '¿Cancelar este pedido?',
    text: 'Pedido #' + idVenta + ' — esta acción quedará registrada y no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'No',
  });
  if (!c1.isConfirmed) return;

  const c2 = await Swal.fire({
    title: 'Última confirmación',
    text: 'Si cancelás ahora, vas a tener que volver a hacer el pedido si te arrepentís. ¿Confirmás la cancelación?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Sí, cancelar pedido',
    cancelButtonText: 'Volver atrás',
  });
  if (!c2.isConfirmed) return;

  btn.disabled  = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cancelando...';

  try {
    const fd = new FormData();
    fd.append('id_venta', idVenta);
    const res  = await fetch('api/cancelar_pedido.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      Swal.fire({
        title: 'Pedido cancelado',
        text: 'Tu pedido #' + idVenta + ' fue cancelado correctamente.',
        icon: 'success',
        confirmButtonColor: '#c88e99',
      }).then(() => location.reload());
    } else {
      Swal.fire('No se pudo cancelar', data.message || 'Intentá de nuevo en un momento.', 'error');
      btn.disabled  = false;
      btn.innerHTML = '<i class="fa-solid fa-ban"></i> Cancelar pedido';
    }
  } catch {
    Swal.fire('Error de conexión', 'Probá de nuevo en un momento.', 'error');
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-ban"></i> Cancelar pedido';
  }
}

async function cancelarPorMercadoPago(idVenta) {
  const c1 = await Swal.fire({
    title: '¿Cancelar este pedido?',
    html: 'El Pedido #' + idVenta + ' fue pagado con <strong>Mercado Pago</strong>.<br>' +
          'Como el pago ya se procesó, la única forma de cancelarlo es coordinando con nosotros el reembolso por WhatsApp.',
    icon: 'info',
    showCancelButton: true,
    confirmButtonColor: '#25d366',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Continuar por WhatsApp',
    cancelButtonText: 'No, volver',
  });
  if (!c1.isConfirmed) return;

  const c2 = await Swal.fire({
    title: 'Te vamos a abrir WhatsApp',
    text: 'Te llevamos a WhatsApp con un mensaje ya redactado para coordinar la cancelación y el reembolso de tu pedido #' + idVenta + '. ¿Continuamos?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#25d366',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Sí, abrir WhatsApp',
    cancelButtonText: 'Cancelar',
  });
  if (!c2.isConfirmed) return;

  const msg = 'Hola! Quiero cancelar mi Pedido #' + idVenta + '. Lo pagué con Mercado Pago y necesito coordinar la cancelación y el reembolso. ¡Gracias!';
  window.open('https://wa.me/' + WHATSAPP_SOPORTE + '?text=' + encodeURIComponent(msg), '_blank');
}
</script>

<!-- ── Banner notificaciones ─────────────────────────────────────── -->
<div id="cookieBanner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;
     background:#fff;border-top:2px solid #f0d0d8;padding:14px 20px;
     box-shadow:0 -4px 24px rgba(200,142,153,.18)">
  <div style="max-width:720px;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;gap:12px">
    <span style="font-size:20px">🍪</span>
    <p style="flex:1;min-width:200px;font-size:13px;color:#555;margin:0;line-height:1.5">
      Queremos avisarte cuando tu pedido esté listo o en camino.
    </p>
    <div style="display:flex;gap:10px;flex-shrink:0">
      <button onclick="notifBannerChoice(false)" style="padding:9px 18px;border-radius:20px;border:1.5px solid #ddd;
              background:#fff;font-size:13px;color:#888;cursor:pointer;font-weight:600">
        Ahora no
      </button>
      <button onclick="notifBannerChoice(true)" style="padding:9px 22px;border-radius:20px;border:none;
              background:#c88e99;color:#fff;font-size:13px;font-weight:700;cursor:pointer">
        Activar notificaciones ✓
      </button>
    </div>
  </div>
</div>
<script>
(function() {
  if (!localStorage.getItem('canetto_cookie_consent')) {
    document.getElementById('cookieBanner').style.display = 'block';
  }
})();
async function notifBannerChoice(accepted) {
  localStorage.setItem('canetto_cookie_consent', accepted ? 'all' : 'essential');
  document.getElementById('cookieBanner').style.display = 'none';
  if (accepted) toggleNotificaciones();
}
</script>
<!-- ─────────────────────────────────────────────────────────────── -->
<script src="transitions.js"></script>
</body>
</html>
