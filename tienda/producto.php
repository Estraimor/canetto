<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$id  = (int)($_GET['id'] ?? 0);
$pdo = Conexion::conectar();

if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT p.idproductos, p.nombre, p.precio, p.tipo, p.imagen,
           COALESCE(p.descripcion,'') AS descripcion,
           COALESCE(p.especificaciones,'') AS especificaciones,
        CASE
            WHEN p.tipo = 'box' THEN (
                SELECT COALESCE(MIN(FLOOR(sp2.stock_actual / bp.cantidad)), 0)
                FROM box_productos bp
                JOIN stock_productos sp2
                    ON sp2.productos_idproductos = bp.producto_item
                    AND sp2.tipo_stock = 'HECHO'
                WHERE bp.producto_box = p.idproductos
            )
            ELSE COALESCE(MAX(CASE WHEN sp.tipo_stock='HECHO' THEN sp.stock_actual END), 0)
        END AS stock_hecho
    FROM productos p
    LEFT JOIN stock_productos sp ON sp.productos_idproductos = p.idproductos AND p.tipo != 'box'
    WHERE p.idproductos = ? AND p.activo = 1
    GROUP BY p.idproductos, p.nombre, p.precio, p.tipo, p.imagen, p.descripcion, p.especificaciones
");
$stmt->execute([$id]);
$prod = $stmt->fetch();
if (!$prod) { header('Location: index.php'); exit; }

// Contenido del box (si aplica)
$boxItems = [];
if ($prod['tipo'] === 'box') {
    $stmtB = $pdo->prepare("
        SELECT p.nombre, bp.cantidad
        FROM box_productos bp
        JOIN productos p ON p.idproductos = bp.producto_item
        WHERE bp.producto_box = ?
        ORDER BY p.nombre
    ");
    $stmtB->execute([$id]);
    $boxItems = $stmtB->fetchAll();
}

$stock   = (float)$prod['stock_hecho'];
$esBox   = $prod['tipo'] === 'box';
$nombre  = htmlspecialchars($prod['nombre']);
$precio  = number_format((float)$prod['precio'], 0, ',', '.');
$cliente_id = $_SESSION['tienda_cliente_id'] ?? null;

if ($stock <= 0)      { $pillCls='sp-out'; $pillTxt='Sin stock'; }
elseif ($stock <= 10) { $pillCls='sp-low'; $pillTxt='Pocas unidades'; }
else                  { $pillCls='sp-ok';  $pillTxt='Disponible'; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title><?= $nombre ?> — Canetto</title>
<link rel="stylesheet" href="tienda.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ── Detalle producto ──────────────────── */
.det-wrap{ max-width:540px; margin:0 auto; padding-bottom:100px; }

.det-img-wrap{
  width:100%; aspect-ratio:1/1; max-height:340px;
  background:#fdf0f3; display:flex; align-items:center; justify-content:center;
  position:relative; overflow:hidden;
}
.det-img-wrap img{ width:100%; height:100%; object-fit:cover; }
.det-img-emoji{ font-size:90px; }
.det-pill{
  position:absolute; top:14px; right:14px;
  padding:5px 12px; border-radius:20px; font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:.5px;
}
.det-pill.sp-ok { background:#dcfce7; color:#16a34a; }
.det-pill.sp-low{ background:#fef9c3; color:#ca8a04; }
.det-pill.sp-out{ background:#fee2e2; color:#dc2626; }

.det-body{ padding:20px 20px 0; }
.det-tipo-tag{
  display:inline-block; font-size:10px; font-weight:700; text-transform:uppercase;
  letter-spacing:1px; color:#c88e99; background:#fdf0f3;
  padding:3px 10px; border-radius:20px; margin-bottom:8px;
}
.det-nombre{ font-size:26px; font-weight:800; color:#1e293b; line-height:1.2; margin-bottom:6px; }
.det-precio{ font-size:28px; font-weight:800; color:#c88e99; margin-bottom:6px; }
.det-stock-txt{ font-size:13px; color:#94a3b8; margin-bottom:16px; }

.det-desc{
  font-size:14px; color:#475569; line-height:1.7;
  border-top:1px solid #f1e8ea; padding-top:16px; margin-bottom:16px;
}
.det-specs{
  background:#fdf8f9; border-radius:12px; padding:14px 16px;
  margin-bottom:16px;
}
.det-specs-title{ font-size:11px; font-weight:700; text-transform:uppercase;
  letter-spacing:.5px; color:#c88e99; margin-bottom:8px; }
.det-specs p{ font-size:13px; color:#475569; line-height:1.7; margin:0; }

/* Box contenido */
.box-items-list{ margin-bottom:16px; }
.box-items-title{ font-size:11px; font-weight:700; text-transform:uppercase;
  letter-spacing:.5px; color:#c88e99; margin-bottom:8px; }
.box-item-row{
  display:flex; justify-content:space-between; align-items:center;
  padding:8px 0; border-bottom:1px solid #f1e8ea; font-size:14px; color:#334155;
}
.box-item-row:last-child{ border-bottom:none; }
.box-item-qty{ font-weight:700; color:#c88e99; }

/* Qty selector */
.qty-selector{
  display:flex; align-items:center; gap:0;
  border:2px solid #e8d0d5; border-radius:12px; overflow:hidden;
  width:fit-content; margin:0 auto 16px;
}
.qty-selector button{
  background:none; border:none; width:44px; height:44px;
  font-size:20px; cursor:pointer; color:#c88e99;
  transition:background .15s;
}
.qty-selector button:hover{ background:#fdf0f3; }
.qty-selector span{
  min-width:44px; text-align:center; font-size:18px;
  font-weight:700; color:#1e293b;
}

/* CTA fija abajo */
.det-cta{
  position:fixed; bottom:0; left:0; right:0;
  background:#fff; border-top:1px solid #f1e8ea;
  padding:12px 20px; display:flex; gap:10px;
  max-width:540px; margin:0 auto;
  box-shadow:0 -4px 20px rgba(0,0,0,.06);
}
.btn-add-det{
  flex:1; padding:15px; border:none; border-radius:14px;
  background:linear-gradient(135deg,#c88e99,#a46678);
  color:#fff; font-size:16px; font-weight:700; cursor:pointer;
  font-family:inherit; transition:opacity .18s;
  box-shadow:0 4px 14px rgba(164,102,120,.35);
}
.btn-add-det:disabled{ background:#e2e8f0; color:#94a3b8;
  box-shadow:none; cursor:not-allowed; }
.btn-add-det:hover:not(:disabled){ opacity:.9; }
</style>
</head>
<body style="background:#fff; margin:0;">
<div class="det-wrap">

  <!-- Header -->
  <header class="t-nav" style="position:sticky;top:0;z-index:100;background:#fff;border-bottom:1px solid #f1e8ea;">
    <button onclick="history.back()" class="t-btn" style="font-size:20px;padding:8px 12px;">←</button>
    <span style="font-weight:700;font-size:16px;color:#1e293b;flex:1;text-align:center;margin-right:44px;">
      <?= $esBox ? 'Box' : 'Cookie' ?>
    </span>
    <button class="t-btn" id="btnOpenCart2" style="font-size:20px;padding:8px 12px;">
      🛒<span class="t-cart-badge" id="cartBadge2">0</span>
    </button>
  </header>

  <!-- Imagen -->
  <div class="det-img-wrap">
    <?php if (!empty($prod['imagen'])): ?>
      <img src="<?= URL_ASSETS ?>/img/productos/<?= htmlspecialchars($prod['imagen']) ?>"
           alt="<?= $nombre ?>">
    <?php else: ?>
      <span class="det-img-emoji"><?= $esBox ? '📦' : '🍪' ?></span>
    <?php endif; ?>
    <span class="det-pill <?= $pillCls ?>"><?= $pillTxt ?></span>
  </div>

  <!-- Info -->
  <div class="det-body">
    <div class="det-tipo-tag"><?= $esBox ? 'Box' : 'Cookie' ?></div>
    <div class="det-nombre"><?= $nombre ?></div>
    <div class="det-precio">$<?= $precio ?></div>
    <div class="det-stock-txt">
      <?php if ($stock <= 0): ?>Sin stock disponible
      <?php elseif ($stock <= 10): ?>¡Quedan solo <?= (int)$stock ?> unidades!
      <?php else: ?><?= (int)$stock ?> unidades disponibles
      <?php endif; ?>
    </div>

    <?php if ($esBox && !empty($boxItems)): ?>
    <!-- Contenido del box -->
    <div class="box-items-list">
      <div class="box-items-title">Contenido del box</div>
      <?php foreach ($boxItems as $item): ?>
      <div class="box-item-row">
        <span><?= htmlspecialchars($item['nombre']) ?></span>
        <span class="box-item-qty">×<?= (int)$item['cantidad'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($prod['descripcion']): ?>
    <div class="det-desc"><?= nl2br(htmlspecialchars($prod['descripcion'])) ?></div>
    <?php endif; ?>

    <?php if ($prod['especificaciones']): ?>
    <div class="det-specs">
      <div class="det-specs-title">Especificaciones</div>
      <p><?= nl2br(htmlspecialchars($prod['especificaciones'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- Selector cantidad -->
    <?php if ($stock > 0): ?>
    <div style="text-align:center;margin-bottom:8px;">
      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:10px;">Cantidad</div>
      <div class="qty-selector">
        <button onclick="cambiarQty(-1)">−</button>
        <span id="qtyVal">1</span>
        <button onclick="cambiarQty(1)">+</button>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /det-wrap -->

<!-- CTA fija -->
<div class="det-cta">
  <button class="btn-add-det" id="btnAddDet"
    <?= $stock <= 0 ? 'disabled' : '' ?>
    onclick="agregarAlCarrito()">
    <?= $stock <= 0 ? 'Sin stock' : '+ Agregar al carrito' ?>
  </button>
</div>

<script>
const PROD_ID    = <?= (int)$prod['idproductos'] ?>;
const PROD_NOMBRE= <?= json_encode($prod['nombre']) ?>;
const PROD_PRECIO= <?= (float)$prod['precio'] ?>;
const PROD_TIPO  = <?= json_encode($prod['tipo']) ?>;
const PROD_STOCK = <?= (int)$stock ?>;
const CLIENTE_PHP= <?= json_encode($cliente_id ? ['id'=>$cliente_id] : null) ?>;

const CK = CLIENTE_PHP ? 'canetto_cart_' + CLIENTE_PHP.id : 'canetto_cart_guest';
const getCart = () => { try { return JSON.parse(localStorage.getItem(CK)||'[]'); } catch { return []; } };
const saveCart = c => { localStorage.setItem(CK, JSON.stringify(c)); updateBadge(); };
const fmt = n => '$' + Number(n).toLocaleString('es-AR', {minimumFractionDigits:0});

function updateBadge(){
  const n = getCart().reduce((s,i)=>s+i.cantidad,0);
  const b = document.getElementById('cartBadge2');
  if(b){ b.textContent = n > 99 ? '99+' : n; b.classList.toggle('on', n>0); }
}

let qty = 1;
function cambiarQty(d){
  qty = Math.max(1, Math.min(qty + d, PROD_STOCK));
  document.getElementById('qtyVal').textContent = qty;
}

function agregarAlCarrito(){
  if(!CLIENTE_PHP){
    Swal.fire({
      icon:'info', title:'Iniciá sesión',
      text:'Necesitás una cuenta para agregar productos al carrito.',
      confirmButtonColor:'#c88e99', confirmButtonText:'Ingresar'
    }).then(r=>{ if(r.isConfirmed) window.location.href='login.php'; });
    return;
  }
  const cart = getCart();
  const ex   = cart.find(i => i.id === PROD_ID);
  const totalEnCarrito = ex ? ex.cantidad : 0;
  if (totalEnCarrito + qty > PROD_STOCK) {
    Swal.fire({ icon:'warning', title:'Stock insuficiente',
      text:`Solo quedan ${PROD_STOCK} unidades disponibles.`,
      confirmButtonColor:'#c88e99', confirmButtonText:'Entendido' });
    return;
  }
  if (ex) { ex.cantidad += qty; }
  else { cart.push({ id:PROD_ID, nombre:PROD_NOMBRE, precio:PROD_PRECIO, tipo:PROD_TIPO, cantidad:qty }); }
  saveCart(cart);
  const btn = document.getElementById('btnAddDet');
  const orig = btn.innerHTML;
  btn.innerHTML = '✓ Agregado';
  btn.style.background = '#2d8a4e';
  setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; }, 1400);
}

document.getElementById('btnOpenCart2')?.addEventListener('click', () => {
  window.location.href = 'index.php#cart';
});

updateBadge();
window.addEventListener('pageshow', function() { updateBadge(); });
</script>
<script src="transitions.js"></script>
</body>
</html>
