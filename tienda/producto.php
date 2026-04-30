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
<link rel="icon" type="image/png" href="/canetto/img/Logo_Canetto_Cookie.png">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ════════════════════════════════
   PRODUCTO.PHP — Mobile first (McD style)
════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; }
body { background: #f8f9fa; margin: 0; font-family: inherit; }

/* ── NAV siempre visible ── */
.det-nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 200;
  height: 60px; padding: 0 16px;
  display: flex; align-items: center; justify-content: space-between;
  background: #fff;
  border-bottom: 1px solid #f0ece8;
  box-shadow: 0 1px 6px rgba(0,0,0,.06);
}
.det-nav-back {
  width: 38px; height: 38px; border-radius: 50%;
  background: #f5f5f5; border: none; cursor: pointer;
  font-size: 15px; display: flex; align-items: center; justify-content: center;
  color: #333; text-decoration: none;
}
.det-nav-title { display: none; }
.det-nav-cart {
  position: relative; width: 38px; height: 38px; border-radius: 50%;
  background: #1e293b; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 15px;
}

/* ── IMAGEN hero — ocupa 45% de la pantalla ── */
.det-img-col { display: block; }
.det-img-wrap {
  width: 100%; height: 52vw; max-height: 260px; min-height: 200px;
  background: linear-gradient(135deg, #fdf0f3, #fff5f7);
  display: flex; align-items: center; justify-content: center;
  position: relative; overflow: hidden;
}
.det-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.det-img-emoji {
  font-size: 100px; line-height: 1; color: #c88e99; opacity: .5;
}
.det-pill {
  position: absolute; top: 12px; right: 12px;
  padding: 5px 13px; border-radius: 20px; font-size: 11px;
  font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
}
.det-pill.sp-ok  { background: #dcfce7; color: #16a34a; }
.det-pill.sp-low { background: #fef9c3; color: #ca8a04; }
.det-pill.sp-out { background: #fee2e2; color: #dc2626; }

/* ── CARD blanca que sube sobre la imagen ── */
.det-info-col { display: block; }
.det-body {
  background: #fff;
  border-radius: 24px 24px 0 0;
  margin-top: -20px;
  position: relative;
  padding: 24px 22px 120px;
  min-height: 60vh;
}

/* Handle decorativo */
.det-body::before {
  content: '';
  display: block;
  width: 40px; height: 4px;
  background: #e2e8f0;
  border-radius: 4px;
  margin: 0 auto 20px;
}

.det-tipo-tag {
  display: inline-block; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1px;
  color: #c88e99; background: #fdf0f3;
  padding: 5px 13px; border-radius: 20px; margin-bottom: 12px;
}
.det-nombre {
  font-size: 28px; font-weight: 800; color: #1e293b;
  line-height: 1.15; margin-bottom: 8px;
  font-family: 'Speedee', sans-serif;
}
.det-precio {
  font-size: 32px; font-weight: 800; color: #c88e99;
  font-family: 'Speedee', sans-serif; margin-bottom: 14px;
}

/* Stock row */
.det-stock-row {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 22px; padding-bottom: 22px;
  border-bottom: 1px solid #f1f5f9;
}
.det-stock-row .det-pill { position: static; font-size: 12px; }
.det-stock-txt { font-size: 15px; color: #64748b; font-weight: 500; }

/* Descripción */
.det-desc {
  font-size: 16px; color: #475569; line-height: 1.8;
  margin-bottom: 22px;
}

/* Specs */
.det-specs {
  background: #fdf8f9; border-radius: 16px;
  padding: 16px 18px; margin-bottom: 22px;
}
.det-specs-title {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: #c88e99; margin-bottom: 10px;
}
.det-specs p { font-size: 15px; color: #475569; line-height: 1.7; margin: 0; }

/* Box items */
.box-items-list { margin-bottom: 22px; }
.box-items-title {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: #c88e99; margin-bottom: 12px;
}
.box-item-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 0; border-bottom: 1px solid #f1e8ea;
  font-size: 15px; color: #334155;
}
.box-item-row:last-child { border-bottom: none; }
.box-item-qty { font-weight: 700; color: #c88e99; }

/* ── Selector de cantidad ── */
.det-qty-section {
  display: flex; align-items: center; justify-content: space-between;
  background: #f8fafc; border-radius: 16px;
  padding: 14px 18px; margin-bottom: 10px;
}
.det-qty-label {
  font-size: 16px; font-weight: 600; color: #1e293b;
}
.qty-selector {
  display: inline-flex; align-items: center; gap: 4px;
}
.qty-selector button {
  width: 40px; height: 40px; border-radius: 50%;
  background: #fff; border: 1.5px solid #e2e8f0;
  font-size: 22px; cursor: pointer; color: #c88e99;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s, border-color .15s;
}
.qty-selector button:active { background: #fdf0f3; border-color: #c88e99; }
.qty-selector span {
  min-width: 44px; text-align: center;
  font-size: 22px; font-weight: 800; color: #1e293b;
}

/* ── CTA fija ── */
.det-cta {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 300;
  padding: 12px 20px 20px;
  background: linear-gradient(to top, #fff 80%, transparent);
}
.btn-add-det {
  width: 100%; padding: 18px; border: none; border-radius: 18px;
  background: #1e293b;
  color: #fff; font-size: 18px; font-weight: 800; cursor: pointer;
  font-family: inherit; letter-spacing: .2px;
  transition: transform .15s, opacity .15s;
  display: flex; align-items: center; justify-content: center; gap: 10px;
}
.btn-add-det:active:not(:disabled) { transform: scale(.97); }
.btn-add-det:disabled {
  background: #e2e8f0; color: #94a3b8; cursor: not-allowed;
}

/* ── Ocultar elementos solo desktop ── */
.det-breadcrumb, .det-img-thumbs, .det-btn-back,
.det-trust, .det-btn-desktop, .det-divider,
.det-nav-link { display: none; }

/* Logo en nav */
.det-nav-brand {
  display: flex; align-items: center; gap: 9px; text-decoration: none;
}
.det-nav-brand-icon {
  width: 38px; height: 38px; border-radius: 10px; overflow: hidden;
  background: linear-gradient(135deg, #a46678, #c88e99);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; padding: 4px;
}
.det-nav-brand-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }
.det-nav-brand-name {
  font-family: 'Speedee', sans-serif;
  font-size: 16px; font-weight: 700; letter-spacing: 3px;
  text-transform: uppercase; color: #1e293b;
}

/* Wrap mobile */
.det-wrap { padding-bottom: 0; padding-top: 60px; }

/* ════════════════════════════════
   DESKTOP ≥ 1024px
════════════════════════════════ */
@media (min-width: 1024px) {
  body { background: #f4f4f2; }

  .det-nav {
    height: 72px; padding: 0 52px;
    box-shadow: 0 1px 0 #f0f0f0;
  }
  .det-nav-brand {
    display: flex; align-items: center; gap: 12px; text-decoration: none;
  }
  .det-nav-brand-icon {
    width: 46px; height: 46px; border-radius: 12px; overflow: hidden;
    border: 1px solid #f0f0f0;
  }
  .det-nav-brand-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .det-nav-brand-name {
    font-family: 'Speedee', sans-serif;
    font-size: 18px; font-weight: 700; letter-spacing: 4px;
    text-transform: uppercase; color: #111;
  }
  .det-nav-back { background: #f5f5f5; }
  .det-nav-title { display: none; } /* el título está en la columna de info */
  .det-nav-actions { display: flex; align-items: center; gap: 10px; }
  .det-nav-link {
    display: flex; align-items: center; gap: 6px;
    padding: 0 16px; height: 40px; border-radius: 22px;
    font-size: 13px; font-weight: 700; color: #555;
    text-decoration: none; background: #f5f5f5;
    transition: background .15s, color .15s;
  }
  .det-nav-link:hover { background: #fdf0f3; color: #c88e99; }

  /* Ocultar CTA fija — el botón va en la columna de info */
  .det-cta { display: none !important; }

  /* Mostrar elementos desktop */
  .det-breadcrumb  { display: flex; }
  .det-img-thumbs  { display: flex; }
  .det-btn-back    { display: flex; }
  .det-trust       { display: grid; }
  .det-btn-desktop { display: block; }
  .det-divider     { display: block; }
  .det-nav-brand   { display: flex; }
  .det-nav-link    { display: flex; }
  /* Stock row: mostrar pill en desktop */
  .det-stock-row .det-pill { display: inline-block; }

  /* Layout de 2 columnas */
  .det-wrap {
    max-width: 1200px;
    margin: 0 auto;
    padding: 52px 56px 80px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 64px;
    align-items: start;
  }

  /* Columna izquierda: imagen sticky */
  .det-img-col {
    position: sticky;
    top: 96px;
  }
  .det-img-wrap {
    max-height: none;
    aspect-ratio: 1/1;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.10);
  }
  .det-img-emoji { font-size: 140px; }
  .det-pill { top: 20px; right: 20px; font-size: 12px; padding: 6px 16px; }

  /* Miniaturas / galería placeholder */
  .det-img-thumbs { display: flex; gap: 10px; margin-top: 14px; }
  .det-img-thumb {
    width: 64px; height: 64px; border-radius: 12px;
    background: linear-gradient(135deg, #fdf0f3, #fff5f7);
    border: 2px solid #f0f0f0; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; overflow: hidden; transition: border-color .15s;
  }
  .det-img-thumb.active { border-color: #c88e99; }
  .det-img-thumb img { width: 100%; height: 100%; object-fit: cover; }

  /* Columna derecha: info */
  .det-info-col { padding: 8px 0; }
  .det-body { padding: 0; }

  /* Breadcrumb */
  .det-breadcrumb {
    font-size: 12px; color: #94a3b8; margin-bottom: 16px;
    display: flex; align-items: center; gap: 6px;
  }
  .det-breadcrumb a { color: #94a3b8; text-decoration: none; }
  .det-breadcrumb a:hover { color: #c88e99; }
  .det-breadcrumb span { color: #ccc; }

  .det-tipo-tag { font-size: 11px; padding: 5px 14px; margin-bottom: 12px; }
  .det-nombre   { font-size: 36px; margin-bottom: 8px; }
  .det-precio   { font-size: 40px; margin-bottom: 8px; }
  .det-stock-row { margin-bottom: 24px; }

  /* Separador */
  .det-divider {
    height: 1px; background: #f0f0f0;
    margin: 22px 0;
  }

  /* Selector de cantidad en desktop */
  .det-qty-section { display: flex; align-items: center; gap: 20px; margin-bottom: 24px; }
  .det-qty-label { margin: 0; font-size: 13px; }
  .qty-selector { border-radius: 16px; }
  .qty-selector button { width: 52px; height: 52px; }
  .qty-selector span { font-size: 22px; min-width: 60px; }

  /* Botón de desktop en la columna de info */
  .det-btn-desktop {
    display: block;
    width: 100%; padding: 18px;
    border: none; border-radius: 18px;
    background: linear-gradient(135deg, #c88e99, #a46678);
    color: #fff; font-size: 17px; font-weight: 700;
    cursor: pointer; font-family: inherit;
    transition: opacity .18s, transform .18s;
    box-shadow: 0 6px 24px rgba(164,102,120,.35);
    letter-spacing: .2px;
    margin-bottom: 14px;
  }
  .det-btn-desktop:hover:not(:disabled) { opacity: .92; transform: translateY(-1px); }
  .det-btn-desktop:disabled { background: #e2e8f0; color: #94a3b8; box-shadow: none; cursor: not-allowed; }

  /* Botón volver al catálogo */
  .det-btn-back {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 14px;
    border: 1.5px solid #e8e8e8; border-radius: 14px;
    background: #fff; color: #555; font-size: 14px; font-weight: 600;
    cursor: pointer; font-family: inherit; text-decoration: none;
    transition: border-color .15s, color .15s;
  }
  .det-btn-back:hover { border-color: #c88e99; color: #c88e99; }

  /* Garantías / info de confianza */
  .det-trust {
    display: grid; grid-template-columns: 1fr 1fr 1fr;
    gap: 12px; margin-top: 28px;
  }
  .det-trust-item {
    text-align: center; padding: 14px 10px;
    background: #fafafa; border-radius: 14px; border: 1px solid #f0f0f0;
  }
  .det-trust-ic { font-size: 22px; margin-bottom: 6px; }
  .det-trust-txt { font-size: 11px; font-weight: 700; color: #555; line-height: 1.4; }

  .det-desc { font-size: 15px; line-height: 1.8; }
  .det-specs { padding: 20px 22px; border-radius: 16px; }
  .box-item-row { font-size: 15px; padding: 12px 0; }
}
</style>
</head>
<body>

<!-- NAV -->
<header class="det-nav">
  <!-- Mobile: solo back + título + carrito -->
  <a href="javascript:history.back()" class="det-nav-back">
    <i class="fa-solid fa-arrow-left" style="font-size:14px"></i>
  </a>
  <span class="det-nav-title"><?= $esBox ? 'Box' : 'Cookie' ?></span>

  <!-- Logo centrado — visible siempre -->
  <a href="index.php" class="det-nav-brand" style="position:absolute;left:50%;transform:translateX(-50%)">
    <div class="det-nav-brand-icon">
      <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" onerror="this.style.display='none'">
    </div>
    <span class="det-nav-brand-name">Canetto</span>
  </a>

  <div class="det-nav-actions" style="display:flex;align-items:center;gap:8px">
    <a href="index.php" class="det-nav-link" style="display:none">
      <i class="fa-solid fa-arrow-left" style="font-size:12px"></i> Volver al catálogo
    </a>
    <button class="det-nav-cart" id="btnOpenCart2">
      <i class="fa-solid fa-cart-shopping" style="font-size:15px"></i>
      <span class="t-cart-badge" id="cartBadge2">0</span>
    </button>
  </div>
</header>

<!-- MOBILE WRAP -->
<div class="det-wrap">

  <!-- Columna imagen (en desktop se convierte en sticky col) -->
  <div class="det-img-col">
    <div class="det-img-wrap">
      <?php if (!empty($prod['imagen'])): ?>
        <img src="<?= URL_ASSETS ?>/img/productos/<?= htmlspecialchars($prod['imagen']) ?>"
             alt="<?= $nombre ?>">
      <?php else: ?>
        <span class="det-img-emoji"><i class="fa-solid <?= $esBox ? 'fa-box' : 'fa-cookie' ?>"></i></span>
      <?php endif; ?>
      <span class="det-pill <?= $pillCls ?>"><?= $pillTxt ?></span>
    </div>
    <!-- Miniaturas decorativas (solo desktop) -->
    <div class="det-img-thumbs">
      <div class="det-img-thumb active">
        <?php if (!empty($prod['imagen'])): ?>
          <img src="<?= URL_ASSETS ?>/img/productos/<?= htmlspecialchars($prod['imagen']) ?>" alt="">
        <?php else: ?>
          <i class="fa-solid <?= $esBox ? 'fa-box' : 'fa-cookie' ?>"></i>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Columna info -->
  <div class="det-info-col">
    <div class="det-body">

      <!-- Breadcrumb desktop -->
      <div class="det-breadcrumb">
        <a href="index.php">Inicio</a>
        <span>›</span>
        <a href="index.php"><?= $esBox ? 'Boxes' : 'Cookies' ?></a>
        <span>›</span>
        <?= $nombre ?>
      </div>

      <div class="det-tipo-tag"><?= $esBox ? 'Box' : 'Cookie artesanal' ?></div>
      <div class="det-nombre"><?= $nombre ?></div>
      <div class="det-precio">$<?= $precio ?></div>

      <div class="det-stock-row">
        <span class="det-pill <?= $pillCls ?>" style="position:static"><?= $pillTxt ?></span>
        <span class="det-stock-txt">
          <?php if ($stock <= 0): ?>Sin stock disponible
          <?php elseif ($stock <= 10): ?>¡Solo quedan <?= (int)$stock ?>!
          <?php else: ?><?= (int)$stock ?> unidades disponibles
          <?php endif; ?>
        </span>
      </div>

      <?php if ($prod['descripcion']): ?>
      <div class="det-desc"><?= nl2br(htmlspecialchars($prod['descripcion'])) ?></div>
      <?php else: ?>
      <div class="det-divider"></div>
      <?php endif; ?>

      <?php if ($esBox && !empty($boxItems)): ?>
      <div class="box-items-list">
        <div class="box-items-title"><i class="fa-solid fa-box" style="margin-right:5px"></i>Contenido del box</div>
        <?php foreach ($boxItems as $item): ?>
        <div class="box-item-row">
          <span><?= htmlspecialchars($item['nombre']) ?></span>
          <span class="box-item-qty">×<?= (int)$item['cantidad'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($prod['especificaciones']): ?>
      <div class="det-specs">
        <div class="det-specs-title">Especificaciones</div>
        <p><?= nl2br(htmlspecialchars($prod['especificaciones'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- Cantidad -->
      <?php if ($stock > 0): ?>
      <div class="det-qty-section">
        <div class="det-qty-label">Cantidad</div>
        <div class="qty-selector">
          <button onclick="cambiarQty(-1)">−</button>
          <span id="qtyVal">1</span>
          <button onclick="cambiarQty(1)">+</button>
        </div>
      </div>
      <?php endif; ?>

      <!-- Botón desktop (oculto en mobile — está en .det-cta) -->
      <button class="det-btn-desktop" id="btnAddDetDesk"
        <?= $stock <= 0 ? 'disabled' : '' ?>
        onclick="agregarAlCarrito()">
        <i class="fa-solid fa-cart-plus" style="margin-right:8px"></i>
        <?= $stock <= 0 ? 'Sin stock disponible' : 'Agregar al carrito' ?>
      </button>

      <a href="index.php" class="det-btn-back">
        <i class="fa-solid fa-arrow-left" style="font-size:12px"></i> Ver más productos
      </a>

      <!-- Info de confianza -->
      <div class="det-trust">
        <div class="det-trust-item">
          <div class="det-trust-ic"><i class="fa-solid fa-cookie"></i></div>
          <div class="det-trust-txt">100% artesanal</div>
        </div>
        <div class="det-trust-item">
          <div class="det-trust-ic"><i class="fa-solid fa-heart"></i></div>
          <div class="det-trust-txt">Hecho con amor</div>
        </div>
        <div class="det-trust-item">
          <div class="det-trust-ic"><i class="fa-solid fa-box"></i></div>
          <div class="det-trust-txt">Envío y retiro</div>
        </div>
      </div>

    </div>
  </div><!-- /det-info-col -->

</div><!-- /det-wrap -->

<!-- CTA fija MOBILE -->
<div class="det-cta">
  <button class="btn-add-det" id="btnAddDet"
    <?= $stock <= 0 ? 'disabled' : '' ?>
    onclick="agregarAlCarrito()">
    <?php if ($stock > 0): ?>
      <i class="fa-solid fa-cart-plus"></i> Agregar al carrito
    <?php else: ?>
      Sin stock disponible
    <?php endif; ?>
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
  if(ex ? ex.cantidad + qty > PROD_STOCK : qty > PROD_STOCK){
    Swal.fire({ icon:'warning', title:'Stock insuficiente',
      text:`Solo quedan ${PROD_STOCK} unidades disponibles.`,
      confirmButtonColor:'#c88e99', confirmButtonText:'Entendido' });
    return;
  }
  if(ex) ex.cantidad += qty;
  else cart.push({ id:PROD_ID, nombre:PROD_NOMBRE, precio:PROD_PRECIO, tipo:PROD_TIPO, cantidad:qty });
  saveCart(cart);
  const btn = document.getElementById('btnAddDet');
  const orig = btn.innerHTML;
  btn.innerHTML = '✓ Agregado al carrito';
  btn.style.background = '#2d8a4e';
  setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; }, 1400);
}

document.getElementById('btnOpenCart2')?.addEventListener('click', () => {
  window.location.href = 'index.php';
});

// Mostrar elementos solo desktop
function applyDesktop(){
  const isDesk = window.innerWidth >= 1024;
  document.querySelector('.det-nav-link').style.display  = isDesk ? 'flex' : 'none';
  const bd = document.getElementById('btnAddDetDesk');
  if(bd) bd.style.display = isDesk ? 'block' : 'none';
}
applyDesktop();
window.addEventListener('resize', applyDesktop);

updateBadge();
window.addEventListener('pageshow', function() { updateBadge(); });

</script>
<script src="transitions.js"></script>
</body>
</html>
