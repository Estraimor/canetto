<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $pdo = Conexion::conectar();



    $ofertas = $pdo->query("
        SELECT o.titulo, o.descripcion, o.emoji, o.tipo, o.valor, o.imagen,
               o.productos_idproductos,
               p.nombre AS prod_nombre, p.precio AS prod_precio, p.tipo AS prod_tipo,
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
               END AS prod_stock
        FROM oferta o
        LEFT JOIN productos p ON p.idproductos = o.productos_idproductos AND p.activo = 1
        LEFT JOIN stock_productos sp ON sp.productos_idproductos = p.idproductos AND p.tipo != 'box'
        WHERE o.activo = 1
          AND (o.fecha_inicio IS NULL OR o.fecha_inicio <= CURDATE())
          AND (o.fecha_fin   IS NULL OR o.fecha_fin   >= CURDATE())
        GROUP BY o.idoferta, o.titulo, o.descripcion, o.emoji, o.tipo, o.valor, o.imagen,
                 o.productos_idproductos, p.nombre, p.precio, p.tipo
        ORDER BY o.created_at DESC
    ")->fetchAll();

    if (empty($ofertas)) {
        $ofertas = [
            ['titulo' => '¡Bienvenidos a Canetto!', 'descripcion' => 'Las mejores galletitas artesanales, hechas con amor', 'emoji' => '🍪', 'tipo' => 'promo', 'valor' => null],
            ['titulo' => 'Armá tu Box', 'descripcion' => 'Combinaciones únicas para cada momento especial', 'emoji' => '📦', 'tipo' => 'promo', 'valor' => null],
        ];
    }

    $productos = $pdo->query("
        SELECT p.idproductos, p.nombre, p.precio, p.tipo,
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
        WHERE p.activo = 1
        GROUP BY p.idproductos, p.nombre, p.precio, p.tipo
        ORDER BY CASE p.tipo WHEN 'box' THEN 0 ELSE 1 END, p.nombre ASC
    ")->fetchAll();

    // Contenido de cada box (para mostrar en modal)
    $boxContenidoRaw = $pdo->query("
        SELECT bp.producto_box, p.nombre, bp.cantidad
        FROM box_productos bp
        JOIN productos p ON p.idproductos = bp.producto_item
        ORDER BY bp.producto_box, p.nombre
    ")->fetchAll();
    $boxContenido = [];
    foreach ($boxContenidoRaw as $row) {
        $boxContenido[$row['producto_box']][] = ['nombre' => $row['nombre'], 'cantidad' => $row['cantidad']];
    }

    try {
        $pdo->exec("ALTER TABLE sucursal ADD COLUMN latitud DECIMAL(10,8) NULL");
        $pdo->exec("ALTER TABLE sucursal ADD COLUMN longitud DECIMAL(11,8) NULL");
    } catch (Throwable $e) {}

    $sucursales = $pdo->query("
        SELECT idsucursal, nombre, direccion, ciudad, provincia, telefono, email,
               latitud, longitud
        FROM sucursal WHERE activo = 1 ORDER BY nombre
    ")->fetchAll();

    $metodos_pago = $pdo->query("SELECT idmetodo_pago, nombre FROM metodo_pago ORDER BY nombre")->fetchAll();

} catch (Throwable $e) {
    $ofertas = [['titulo' => '¡Bienvenidos a Canetto!', 'descripcion' => 'Galletitas artesanales', 'emoji' => '🍪', 'tipo' => 'promo', 'valor' => null]];
    $productos = []; $sucursales = []; $metodos_pago = [];
}

$cliente_id     = $_SESSION['tienda_cliente_id']     ?? null;
$cliente_nombre = $_SESSION['tienda_cliente_nombre'] ?? null;
$bgClasses      = ['slide-bg-0','slide-bg-1','slide-bg-2','slide-bg-3'];
$tagLabels      = ['promo' => 'Canetto', 'descuento' => 'Descuento', 'temporada' => 'Temporada'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Canetto — Galletitas Artesanales</title>
<meta name="description" content="Las mejores galletitas artesanales. Pedí online y retirá en tu sucursal más cercana.">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<link rel="stylesheet" href="tienda.css">
<style>
.ck-entrega-toggle label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#475569;margin-bottom:8px}
.ck-toggle-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.ck-toggle-btn{padding:12px 8px;border:2px solid #e2e8f0;border-radius:12px;background:white;font-size:14px;font-weight:600;color:#64748b;cursor:pointer;transition:all .18s;font-family:inherit}
.ck-toggle-btn.on{border-color:#3b82f6;background:#eff6ff;color:#1d4ed8}
.btn-geo{width:100%;margin-top:8px;padding:10px;background:#f0f9ff;border:1.5px solid #bfdbfe;border-radius:10px;color:#1d4ed8;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .18s}
.btn-geo:hover{background:#dbeafe}
</style>
</head>
<body class="has-bottom-nav">
<div id="page-wrap">

<!-- ── HEADER ──────────────────── -->
<header class="t-nav">
  <a href="index.php" class="t-brand">
    <div class="t-brand-icon">
      <img src="../img/canetto_logo.jpg" alt="Canetto" class="t-brand-logo" onerror="this.style.display='none'">
    </div>
    <span class="t-brand-name">Canetto</span>
  </a>
  <div class="t-actions">
    <button class="t-btn" id="btnOpenCart" title="Carrito">
      🛒
      <span class="t-cart-badge" id="cartBadge">0</span>
    </button>
  </div>
</header>

<!-- ── CAROUSEL ────────────────── -->
<div class="swiper" id="mainSwiper">
  <div class="swiper-wrapper">
    <?php foreach ($ofertas as $i => $o): ?>
    <div class="swiper-slide">
      <?php if (!empty($o['imagen'])): ?>
        <div class="slide-bg" style="background:url('<?= URL_ASSETS ?>/img/ofertas/<?= rawurlencode($o['imagen']) ?>') center/cover no-repeat;"></div>
      <?php else: ?>
        <div class="slide-bg <?= $bgClasses[$i % 4] ?>"><?= htmlspecialchars($o['emoji'] ?? '🍪') ?></div>
      <?php endif; ?>
      <div class="slide-content">
        <span class="slide-tag"><?= htmlspecialchars($tagLabels[$o['tipo']] ?? 'Canetto') ?></span>
        <div class="slide-title"><?= htmlspecialchars($o['titulo']) ?></div>
        <?php if (!empty($o['descripcion'])): ?>
          <div class="slide-desc"><?= htmlspecialchars($o['descripcion']) ?></div>
        <?php endif; ?>
        <?php if (!empty($o['valor'])): ?>
          <div class="slide-desc" style="margin-top:6px;font-size:15px;font-weight:700">
            <?= $o['tipo'] === 'descuento' ? number_format($o['valor'],0).'% OFF' : '$'.number_format($o['valor'],0,',','.') ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($o['productos_idproductos']) && !empty($o['prod_nombre'])): ?>
          <?php
            $pStock      = (float)($o['prod_stock'] ?? 0);
            $pid         = (int)$o['productos_idproductos'];
            $pNombre     = htmlspecialchars($o['prod_nombre']);
            $pPrecio     = (float)$o['prod_precio'];
            $pTipo       = $o['prod_tipo'];
            $esBox       = $pTipo === 'box';
            $ofTipo      = $o['tipo'];
            $ofValor     = (float)($o['valor'] ?? 0);
            $esDescuento = $ofTipo === 'descuento' && $ofValor > 0;
            $pPrecioFinal = $esDescuento ? round($pPrecio * (1 - $ofValor / 100)) : $pPrecio;
          ?>
          <?php if ($esBox): ?>
            <button class="slide-cart-btn" <?= $pStock <= 0 ? 'disabled' : '' ?>
              data-boxid="<?= $pid ?>"
              data-nombre="<?= $pNombre ?>"
              data-precio="<?= $pPrecioFinal ?>"
              data-precio-original="<?= $pPrecio ?>"
              data-descuento="<?= $esDescuento ? $ofValor : 0 ?>"
              data-stock="<?= (int)$pStock ?>"
              onclick="abrirModalBox(this)">
              <?= $pStock <= 0 ? 'Sin stock' : '📦 Ver contenido' ?>
            </button>
          <?php else: ?>
            <button class="btn-add-cart slide-cart-btn"
              <?= $pStock <= 0 ? 'disabled' : '' ?>
              data-id="<?= $pid ?>"
              data-nombre="<?= $pNombre ?>"
              data-precio="<?= $pPrecioFinal ?>"
              data-precio-original="<?= $pPrecio ?>"
              data-descuento="<?= $esDescuento ? $ofValor : 0 ?>"
              data-tipo="<?= $pTipo ?>"
              data-stock="<?= (int)$pStock ?>"
              onclick="addToCart(this)">
              <?= $pStock <= 0 ? 'Sin stock' : '+ Agregar al carrito' ?>
            </button>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="swiper-pagination"></div>
</div>

<!-- ── PRODUCTS ────────────────── -->
<div class="sec-head">
  <div>
    <div class="sec-title">Nuestros <em>productos</em></div>
    <div class="sec-sub"><?= count($productos) ?> productos disponibles</div>
  </div>
</div>

<div class="filters">
  <button class="filter-btn on" data-filter="all">Todos</button>
  <button class="filter-btn" data-filter="producto">Galletitas</button>
  <button class="filter-btn" data-filter="box">Boxes</button>
</div>

<div class="prods-grid" id="prodsGrid">
<?php if (empty($productos)): ?>
  <div style="grid-column:1/-1;text-align:center;padding:50px 20px;color:#888">
    <div style="font-size:52px;margin-bottom:14px">🍪</div>
    <div style="font-size:14px">Próximamente más productos</div>
  </div>
<?php else: foreach ($productos as $p):
  $stock   = (float)$p['stock_hecho'];
  $disabled = $stock <= 0 ? 'disabled' : '';
  if ($stock <= 0)  { $pill = 'sp-out'; $pillTxt = 'Sin stock';       $stockTxt = 'No disponible'; }
  elseif ($stock <= 10) { $pill = 'sp-low'; $pillTxt = 'Pocas unidades'; $stockTxt = 'Quedan '.(int)$stock.' u.'; }
  else              { $pill = 'sp-ok';  $pillTxt = 'Disponible';      $stockTxt = (int)$stock.' disponibles'; }
  $emoji  = $p['tipo'] === 'box' ? '📦' : '🍪';
  $nombre = htmlspecialchars($p['nombre']);
  $precio = number_format((float)$p['precio'], 0, ',', '.');
?>
<div class="prod-card" data-tipo="<?= $p['tipo'] ?>">
  <div class="prod-thumb">
    <?= $emoji ?>
    <span class="stock-pill <?= $pill ?>"><?= $pillTxt ?></span>
  </div>
  <div class="prod-body">
    <div class="prod-name"><?= $nombre ?></div>
    <div class="prod-price">$<?= $precio ?></div>
    <div class="prod-stock-txt"><?= $stockTxt ?></div>
    <?php if ($p['tipo'] === 'box'): ?><span class="prod-type-tag">Box</span><?php endif; ?>
  </div>
  <button class="btn-add-cart" <?= $disabled ?>
    data-id="<?= (int)$p['idproductos'] ?>"
    data-nombre="<?= $nombre ?>"
    data-precio="<?= (float)$p['precio'] ?>"
    data-tipo="<?= $p['tipo'] ?>"
    data-stock="<?= (int)$stock ?>"
    onclick="addToCart(this)">
    + Agregar
  </button>
</div>
<?php endforeach; endif; ?>
</div>

<!-- ── BOX BUILDER CTA ─────────── -->
<div class="box-cta">
  <div class="box-cta-tag">Personalizado</div>
  <div class="box-cta-title">Armá <em>tu Box</em></div>
  <div class="box-cta-desc">Elegí tus galletitas favoritas y armá tu combinación ideal para regalar o darte un gusto</div>
  <button class="btn-box-open" id="btnOpenBox">📦 Armar mi Box</button>
</div>

<!-- ── BRANCHES ────────────────── -->
<?php if (!empty($sucursales)): ?>
<div class="sec-head" id="sucursales">
  <div>
    <div class="sec-title">Nuestras <em>sucursales</em></div>
    <div class="sec-sub">Retirá tu pedido en la más cercana</div>
  </div>
  <button class="btn-nearest" id="btnNearest" onclick="findNearest()">
    📍 La más cercana
  </button>
</div>

<!-- Banner "más cercana" -->
<div id="nearestBanner" style="display:none;margin:0 20px 12px;padding:12px 16px;background:var(--pk-lt);border-left:3px solid var(--pk);border-radius:10px;font-size:13px;color:var(--dk)"></div>

<div class="branch-grid" id="branchGrid">
  <?php foreach ($sucursales as $i => $s):
    $addr = implode(', ', array_filter([$s['direccion'], $s['ciudad'], $s['provincia']]));
    $lat  = !empty($s['latitud'])  ? (float)$s['latitud']  : null;
    $lng  = !empty($s['longitud']) ? (float)$s['longitud'] : null;
    $osmDir = ($lat && $lng)
        ? "https://www.openstreetmap.org/directions?to={$lat},{$lng}"
        : ($addr ? "https://www.openstreetmap.org/search?query=" . urlencode($addr) : null);
  ?>
  <div class="branch-card" id="branch-<?= $i ?>"
       data-lat="<?= $lat ?? '' ?>" data-lng="<?= $lng ?? '' ?>"
       data-nombre="<?= htmlspecialchars($s['nombre']) ?>">
    <div class="branch-head">
      <div class="branch-ic">📍</div>
      <div class="branch-name"><?= htmlspecialchars($s['nombre']) ?></div>
      <span class="branch-nearest-badge" id="badge-<?= $i ?>" style="display:none">⭐ Más cercana</span>
    </div>
    <?php if ($addr): ?><div class="branch-addr"><?= htmlspecialchars($addr) ?></div><?php endif; ?>
    <?php if ($lat && $lng): ?>
  <iframe
    width="100%"
    height="200"
    style="border:0; border-radius:12px;"
    loading="lazy"
    allowfullscreen
    src="https://www.google.com/maps?q=<?= $lat ?>,<?= $lng ?>&z=15&output=embed">
  </iframe>
<?php elseif ($addr): ?>
  <iframe
    width="100%"
    height="200"
    style="border:0; border-radius:12px;"
    loading="lazy"
    allowfullscreen
    src="https://www.google.com/maps?q=<?= urlencode($addr) ?>&z=15&output=embed">
  </iframe>
<?php endif; ?>
    <div class="branch-chips">
      <?php if ($s['telefono']): ?><span class="branch-chip">📞 <?= htmlspecialchars($s['telefono']) ?></span><?php endif; ?>
      <?php if ($s['email']): ?><span class="branch-chip">✉️ <?= htmlspecialchars($s['email']) ?></span><?php endif; ?>
    </div>
    <?php if ($osmDir): ?>
    <?php if ($lat && $lng): ?>
<a href="https://www.google.com/maps/dir/?api=1&destination=<?= $lat ?>,<?= $lng ?>"
   target="_blank"
   class="btn-dir">
   🧭 Cómo llegar
</a>
<?php endif; ?>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── FOOTER ──────────────────── -->
<footer class="t-footer">
  <div class="t-footer-brand">Canetto</div>
  <div class="t-footer-tag">Galletitas artesanales hechas con amor ❤️</div>
  <div class="t-footer-links">
    <a href="mis-pedidos.php">Mis pedidos</a>
    <a href="login.php">Mi cuenta</a>
    <a href="#sucursales">Sucursales</a>
  </div>
  <div class="t-footer-copy">&copy; <?= date('Y') ?> Canetto. Todos los derechos reservados.</div>
</footer>
</div><!-- /page-wrap -->

<!-- ── BOX DETAIL MODAL ─────────── -->
<div class="cart-overlay" id="boxModalOverlay" onclick="cerrarModalBox()"></div>
<div class="box-detail-modal" id="boxDetailModal">
  <div class="box-detail-header">
    <span id="boxModalNombre" style="font-weight:700;font-size:17px"></span>
    <button onclick="cerrarModalBox()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#888;line-height:1">×</button>
  </div>
  <div class="box-detail-body">
    <div style="font-size:13px;color:#666;margin-bottom:10px">Contenido del box:</div>
    <ul id="boxModalItems" style="list-style:none;padding:0;margin:0 0 16px"></ul>
    <div style="font-size:20px;font-weight:800;margin-bottom:16px" id="boxModalPrecio"></div>
    <button class="btn-add-cart" id="boxModalBtn" style="width:100%;justify-content:center;font-size:15px;padding:14px"
      onclick="addBoxDesdeModal()">
      + Agregar al carrito
    </button>
  </div>
</div>

<!-- ── CART DRAWER ─────────────── -->
<div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
<aside class="cart-drawer" id="cartDrawer">
  <div class="cart-dhead">
    <span class="cart-dhead-title">Mi Carrito</span>
    <span class="cart-count-tag" id="cartCountTag">0 items</span>
    <button class="btn-close" onclick="closeCart()">✕</button>
  </div>
  <div class="cart-items-wrap" id="cartItemsWrap"></div>
  <div class="cart-dfooter">
    <div class="cart-total">
      <span class="cart-total-lbl">Total</span>
      <span class="cart-total-amt" id="cartTotal">$0</span>
    </div>
    <button class="btn-checkout" onclick="openCheckout()">Finalizar pedido →</button>
    <button class="btn-clear" onclick="clearCartConfirm()">Vaciar carrito</button>
  </div>
</aside>

<!-- ── BOX BUILDER MODAL ────────── -->
<div class="modal-bg" id="boxModal">
  <div class="modal-sheet">
    <div class="modal-hd">
      <span class="modal-title">📦 Armá tu Box</span>
      <button class="btn-close" onclick="closeBoxModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="box-step on" id="boxStep1">
        <p style="font-size:13px;color:#666;margin-bottom:16px">¿De cuántas unidades querés tu box?</p>
        <div class="box-sizes">
          <button class="box-sz-btn" data-size="6"  onclick="selectBoxSize(6,this)"><div class="box-sz-num">6</div><div class="box-sz-lbl">unidades</div></button>
          <button class="box-sz-btn" data-size="12" onclick="selectBoxSize(12,this)"><div class="box-sz-num">12</div><div class="box-sz-lbl">unidades</div></button>
          <button class="box-sz-btn" data-size="24" onclick="selectBoxSize(24,this)"><div class="box-sz-num">24</div><div class="box-sz-lbl">unidades</div></button>
          <button class="box-sz-btn box-sz-custom" onclick="selectBoxSize(0,this)">
            <div class="box-sz-num" style="font-size:20px">✏️</div>
            <div class="box-sz-lbl">Personalizado</div>
            <input class="box-custom-input" id="customSizeInput" type="number" min="1" max="200" placeholder="¿Cuántas?" onclick="event.stopPropagation()" oninput="onCustomSizeInput(this)">
          </button>
        </div>
      </div>
      <div class="box-step" id="boxStep2">
        <div class="box-bar-wrap"><div class="box-bar" id="boxBar" style="width:0%"></div></div>
        <div class="box-prog-txt" id="boxProgTxt">Seleccioná productos</div>
        <div class="box-prod-grid" id="boxProdGrid"></div>
      </div>
    </div>
    <div class="modal-foot">
      <div id="boxFoot1">
        <button class="btn-pk" id="btnBoxNext" onclick="goBoxStep2()" disabled>Elegir productos →</button>
      </div>
      <div id="boxFoot2" style="display:none">
        <button class="btn-pk" id="btnBoxAdd" onclick="addBoxToCart()">Agregar al carrito</button>
        <button class="btn-sec" onclick="backBoxStep1()">← Cambiar tamaño</button>
      </div>
    </div>
  </div>
</div>

<!-- ── CHECKOUT MODAL ───────────── -->
<div class="modal-bg" id="checkoutModal">
  <div class="modal-sheet">
    <div class="modal-hd">
      <span class="modal-title">Finalizar pedido</span>
      <button class="btn-close" onclick="closeCheckout()">✕</button>
    </div>
    <div class="modal-body">

      <!-- Paso A: quién sos -->
      <div class="ck-step on" id="ckAuth">
        <div class="ck-tabs">
          <button class="ck-tab on" onclick="switchCkTab('guest',this)">Invitado</button>
          <button class="ck-tab"   onclick="switchCkTab('login',this)">Ingresar</button>
          <button class="ck-tab"   onclick="switchCkTab('reg',this)">Registrarse</button>
        </div>

        <div class="ck-form on" id="ckGuest">
          <div class="ck-alert" id="gAlert"></div>
          <div class="fg-row">
            <div class="fg"><label>Nombre *</label><input id="gNom" type="text" placeholder="Tu nombre"></div>
            <div class="fg"><label>Apellido</label><input id="gApe" type="text" placeholder="Apellido"></div>
          </div>
          <div class="fg"><label>Celular</label><input id="gCel" type="tel" placeholder="Ej: 1123456789"></div>
          <button class="btn-pk" onclick="guestContinue()">Continuar →</button>
        </div>

        <div class="ck-form" id="ckLogin">
          <div class="ck-alert" id="lAlert"></div>
          <div class="fg"><label>Celular *</label><input id="lCel" type="tel" placeholder="Número de celular"></div>
          <div class="fg"><label>Contraseña *</label><input id="lPass" type="password" placeholder="Tu contraseña"></div>
          <button class="btn-pk" onclick="doLogin()">Ingresar →</button>
          <div class="ck-divider">o</div>
          <button class="btn-sec" onclick="switchCkTab('reg',null)">Crear cuenta</button>
        </div>

        <div class="ck-form" id="ckReg">
          <div class="ck-alert" id="rAlert"></div>
          <div class="fg-row">
            <div class="fg"><label>Nombre *</label><input id="rNom" type="text" placeholder="Nombre"></div>
            <div class="fg"><label>Apellido</label><input id="rApe" type="text" placeholder="Apellido"></div>
          </div>
          <div class="fg"><label>Celular *</label><input id="rCel" type="tel" placeholder="1123456789"></div>
          <div class="fg"><label>DNI</label><input id="rDni" type="text" placeholder="Ej: 38123456"></div>
          <div class="fg"><label>Contraseña *</label><input id="rPass" type="password" placeholder="Mínimo 6 caracteres"></div>
          <button class="btn-pk" onclick="doRegister()">Crear cuenta →</button>
        </div>
      </div>

      <!-- Paso B: detalles -->
      <div class="ck-step" id="ckDetails">
        <div class="ck-summary" id="ckSummary"></div>

        <!-- Toggle retiro / envío -->
        <div class="fg ck-entrega-toggle">
          <label>¿Cómo recibís tu pedido?</label>
          <div class="ck-toggle-row">
            <button type="button" class="ck-toggle-btn on" id="btnRetiro" onclick="setEntrega('retiro')">
              🏪 Retiro en local
            </button>
            <button type="button" class="ck-toggle-btn" id="btnEnvio" onclick="setEntrega('envio')">
              🛵 Envío a domicilio
            </button>
          </div>
        </div>

        <!-- Sucursal (solo retiro) -->
        <div class="fg" id="wrapSucursal">
          <label>Sucursal de retiro<?php if (!empty($sucursales)): ?> *<?php endif; ?></label>
          <select id="ckSuc">
            <option value="">— Elegí una sucursal —</option>
            <?php foreach ($sucursales as $s): ?>
            <option value="<?= $s['idsucursal'] ?>"><?= htmlspecialchars($s['nombre']) ?><?= $s['ciudad'] ? ' — '.htmlspecialchars($s['ciudad']) : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Dirección (solo envío) -->
        <div class="fg" id="wrapEnvio" style="display:none">
          <label>Tu dirección de entrega *</label>
          <input type="text" id="ckDireccion" placeholder="Ej: Corrientes 1234, CABA">
          <button type="button" class="btn-geo" id="btnGeo" onclick="usarMiUbicacion()">
            📍 Usar mi ubicación actual
          </button>
          <input type="hidden" id="ckLat">
          <input type="hidden" id="ckLng">
          <div id="geoStatus" style="font-size:12px;color:#64748b;margin-top:4px"></div>
        </div>

        <div class="fg">
          <label>Método de pago *</label>
          <select id="ckMetodo">
            <option value="">— Cómo vas a pagar —</option>
            <?php foreach ($metodos_pago as $m): ?>
            <option value="<?= $m['idmetodo_pago'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Observaciones (opcional)</label>
          <textarea id="ckObs" rows="2" placeholder="Ej: Sin gluten, para regalo..."></textarea>
        </div>
        <div class="ck-alert" id="dAlert"></div>
        <button class="btn-pk" id="btnConfirm" onclick="confirmOrder()">Confirmar pedido ✓</button>
        <button class="btn-sec" onclick="backToAuth()">← Volver</button>
      </div>

      <!-- Paso C: éxito -->
      <div class="ck-step" id="ckSuccess">
        <div class="ck-success">
          <div class="ck-success-ic">🎉</div>
          <div class="ck-success-title">¡Pedido realizado!</div>
          <div class="ck-success-sub">Tu pedido fue registrado. Te esperamos en la sucursal.</div>
          <div class="ck-success-order" id="ckOrderNum">#0</div>
          <button class="btn-pk" onclick="closeCheckout();clearCart()">¡Entendido! 🍪</button>
          <?php if ($cliente_id): ?>
            <a href="mis-pedidos.php" class="btn-sec">Ver mis pedidos →</a>
          <?php else: ?>
            <a href="login.php" class="btn-sec">Crear cuenta para seguir mis pedidos</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- TOAST + FAB -->
<div class="toast" id="toast"></div>
<button class="fab" id="fabCart" onclick="openCart()">🛒<span class="fab-badge" id="fabBadge">0</span></button>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ── PHP DATA ────────────────────────────
const PRODUCTOS   = <?= json_encode($productos,   JSON_UNESCAPED_UNICODE) ?>;
const SUCURSALES  = <?= json_encode($sucursales,  JSON_UNESCAPED_UNICODE) ?>;
const CLIENTE_PHP = <?= json_encode($cliente_id ? ['id'=>$cliente_id,'nombre'=>$cliente_nombre] : null) ?>;
const BOX_CONTENIDO = <?= json_encode($boxContenido, JSON_UNESCAPED_UNICODE) ?>;

// ── SWIPER ──────────────────────────────
new Swiper('#mainSwiper',{loop:true,autoplay:{delay:4500,disableOnInteraction:false},pagination:{el:'.swiper-pagination',clickable:true}});

// ── CART ────────────────────────────────
// Clave de carrito vinculada al usuario para que cada cuenta tenga su propio carrito
const CK = CLIENTE_PHP ? 'canetto_cart_' + CLIENTE_PHP.id : 'canetto_cart_guest';
// Limpiar clave genérica legada si quedó de versiones anteriores
if (localStorage.getItem('canetto_cart') !== null) { localStorage.removeItem('canetto_cart'); }
const getCart=()=>{try{return JSON.parse(localStorage.getItem(CK)||'[]')}catch{return[]}};
const saveCart=c=>{localStorage.setItem(CK,JSON.stringify(c));renderCart()};

function requireLogin(){
  if(!CLIENTE_PHP){
    showToast('Iniciá sesión para continuar 👤','err');
    setTimeout(()=>window.location.href='<?= base() ?>/login/login.php',1400);
    return true;
  }
  return false;
}
function addToCart(btn){
  if(requireLogin()) return;
  const id=+btn.dataset.id,nombre=btn.dataset.nombre,precio=+btn.dataset.precio,tipo=btn.dataset.tipo,stock=+btn.dataset.stock;
  const precioOriginal=btn.dataset.precioOriginal?+btn.dataset.precioOriginal:null;
  const descuentoPct=btn.dataset.descuento?+btn.dataset.descuento:null;
  const cart=getCart(),ex=cart.find(i=>i.id===id);
  if(ex){if(ex.cantidad>=stock){showToast('Máximo stock disponible','err');return}ex.cantidad++}
  else cart.push({id,nombre,precio,tipo,cantidad:1,
    precio_original: descuentoPct>0 ? precioOriginal : null,
    descuento_pct:   descuentoPct>0 ? descuentoPct   : null});
  saveCart(cart);showToast(nombre+' agregado ✓','ok');
  const o=btn.innerHTML;btn.innerHTML='✓ Listo';btn.style.background='#2d8a4e';
  setTimeout(()=>{btn.innerHTML=o;btn.style.background=''},1200);
}
function updateQty(id,d){const c=getCart(),i=c.findIndex(x=>x.id===id);if(i<0)return;c[i].cantidad+=d;if(c[i].cantidad<=0)c.splice(i,1);saveCart(c)}
function clearCart(){saveCart([]);showToast('Carrito vaciado')}
function clearCartConfirm(){if(!confirm('¿Vaciar el carrito?'))return;clearCart()}
const total=c=>c.reduce((s,i)=>s+i.precio*i.cantidad,0);
const count=c=>c.reduce((s,i)=>s+i.cantidad,0);
const fmt=n=>'$'+Number(n).toLocaleString('es-AR',{minimumFractionDigits:0});
function emoji(n,t){if(t==='box')return'📦';const l=(n||'').toLowerCase();if(l.includes('alfajor'))return'🍫';if(l.includes('torta'))return'🎂';if(l.includes('brownie'))return'🟫';if(l.includes('muffin')||l.includes('cupcake'))return'🧁';return'🍪'}

function renderCart(){
  const c=getCart(),n=count(c),t=total(c);
  ['cartBadge','fabBadge'].forEach(id=>{const el=document.getElementById(id);el.textContent=n>99?'99+':n;el.classList.toggle('on',n>0)});
  document.getElementById('cartCountTag').textContent=n+(n===1?' item':' items');
  document.getElementById('cartTotal').textContent=fmt(t);
  const w=document.getElementById('cartItemsWrap');
  if(!c.length){w.innerHTML='<div class="cart-empty"><div class="cart-empty-ic">🛒</div><div class="cart-empty-txt">Tu carrito está vacío</div></div>';return}
  w.innerHTML=c.map(it=>`<div class="cart-item"><div class="cart-item-ic">${emoji(it.nombre,it.tipo)}</div><div class="cart-item-inf"><div class="cart-item-name">${it.nombre}</div><div class="cart-item-price">${fmt(it.precio*it.cantidad)}</div></div><div class="qty-ctrl"><button class="qty-btn" onclick="updateQty(${it.id},-1)">−</button><span class="qty-num">${it.cantidad}</span><button class="qty-btn" onclick="updateQty(${it.id},1)">+</button></div></div>`).join('');
}

// ── CART DRAWER ─────────────────────────
function openCart(){document.getElementById('cartDrawer').classList.add('on');document.getElementById('cartOverlay').classList.add('on');document.body.style.overflow='hidden'}
function closeCart(){document.getElementById('cartDrawer').classList.remove('on');document.getElementById('cartOverlay').classList.remove('on');document.body.style.overflow=''}

// ── FILTER ──────────────────────────────
document.querySelectorAll('.filter-btn').forEach(b=>b.addEventListener('click',function(){
  document.querySelectorAll('.filter-btn').forEach(x=>x.classList.remove('on'));this.classList.add('on');
  const f=this.dataset.filter;
  document.querySelectorAll('.prod-card').forEach(c=>c.style.display=(f==='all'||c.dataset.tipo===f)?'':'none');
}));

// ── BOX BUILDER ─────────────────────────
let box={size:0,items:{}};
function openBoxModal(){
  if(requireLogin()) return;
  box={size:0,items:{}};
  document.getElementById('boxStep1').classList.add('on');
  document.getElementById('boxStep2').classList.remove('on');
  document.getElementById('boxFoot1').style.display='';
  document.getElementById('boxFoot2').style.display='none';
  document.getElementById('btnBoxNext').disabled=true;
  document.querySelectorAll('.box-sz-btn').forEach(b=>b.classList.remove('on'));
  const ci=document.getElementById('customSizeInput');if(ci)ci.value='';
  document.getElementById('boxModal').classList.add('on');
  document.body.style.overflow='hidden';
}
function closeBoxModal(){document.getElementById('boxModal').classList.remove('on');document.body.style.overflow=''}
function selectBoxSize(s,b){
  box.size=s;
  document.querySelectorAll('.box-sz-btn').forEach(x=>x.classList.remove('on'));
  b.classList.add('on');
  document.getElementById('btnBoxNext').disabled=(s<=0);
}
function onCustomSizeInput(inp){
  const v=parseInt(inp.value)||0;
  box.size=v>0?v:0;
  document.querySelectorAll('.box-sz-btn').forEach(x=>x.classList.remove('on'));
  if(v>0) inp.closest('.box-sz-btn').classList.add('on');
  document.getElementById('btnBoxNext').disabled=(box.size<=0);
}
function goBoxStep2(){
  if(!box.size)return;box.items={};
  document.getElementById('boxStep1').classList.remove('on');document.getElementById('boxStep2').classList.add('on');
  document.getElementById('boxFoot1').style.display='none';document.getElementById('boxFoot2').style.display='';
  const avail=PRODUCTOS.filter(p=>p.tipo==='producto'&&parseFloat(p.stock_hecho)>0);
  document.getElementById('boxProdGrid').innerHTML=avail.length
    ?avail.map(p=>`<div class="box-prod-item"><div class="box-prod-ic">${emoji(p.nombre,p.tipo)}</div><div class="box-prod-name">${p.nombre}</div><div class="box-prod-price">${fmt(p.precio)}</div><div class="box-prod-qty"><button class="bqb" onclick="bqChange(${p.idproductos},-1,event)">−</button><span class="bqn" id="bq${p.idproductos}">0</span><button class="bqb" onclick="bqChange(${p.idproductos},1,event)">+</button></div></div>`).join('')
    :'<div style="grid-column:1/-1;text-align:center;padding:30px;color:#888;font-size:13px">No hay productos con stock disponible.</div>';
  updateBoxUI();
}
function bqChange(id,d,e){
  e.stopPropagation();
  const p=PRODUCTOS.find(x=>x.idproductos==id);if(!p)return;
  const cur=box.items[id]?box.items[id].cantidad:0,tot=Object.values(box.items).reduce((s,i)=>s+i.cantidad,0);
  if(d>0&&tot>=box.size){showToast('Tu box solo tiene '+box.size+' lugares','err');return}
  const nq=cur+d;
  if(nq<=0)delete box.items[id];
  else box.items[id]={id,nombre:p.nombre,precio:+p.precio,cantidad:nq};
  const el=document.getElementById('bq'+id);if(el)el.textContent=box.items[id]?box.items[id].cantidad:0;
  updateBoxUI();
}
function updateBoxUI(){
  const filled=Object.values(box.items).reduce((s,i)=>s+i.cantidad,0),pct=box.size?Math.round(filled/box.size*100):0;
  document.getElementById('boxBar').style.width=pct+'%';
  document.getElementById('boxProgTxt').textContent=filled+' de '+box.size+' productos seleccionados';
  const btn=document.getElementById('btnBoxAdd');
  btn.disabled=filled===0;
  btn.textContent=filled===box.size?'🎉 Box completa — Agregar al carrito':'Agregar al carrito ('+filled+'/'+box.size+')';
  btn.style.background=filled===box.size?'#2d8a4e':'';
}
function addBoxToCart(){
  const items=Object.values(box.items);if(!items.length){showToast('Seleccioná al menos un producto','err');return}
  const cart=getCart();
  items.forEach(it=>{const ex=cart.find(i=>i.id===it.id);if(ex)ex.cantidad+=it.cantidad;else cart.push({id:it.id,nombre:it.nombre,precio:it.precio,tipo:'producto',cantidad:it.cantidad})});
  saveCart(cart);closeBoxModal();openCart();showToast('Box de '+box.size+' agregada al carrito 🎉','ok');
}
function backBoxStep1(){document.getElementById('boxStep2').classList.remove('on');document.getElementById('boxStep1').classList.add('on');document.getElementById('boxFoot2').style.display='none';document.getElementById('boxFoot1').style.display=''}
document.getElementById('btnOpenBox').addEventListener('click',openBoxModal);
document.getElementById('boxModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closeBoxModal()});

// ── CHECKOUT ────────────────────────────
let ckCliente=null;
function openCheckout(){
  if(!getCart().length){showToast('Tu carrito está vacío','err');return}
  closeCart();
  if(CLIENTE_PHP){ckCliente={id:CLIENTE_PHP.id,nombre:CLIENTE_PHP.nombre};showCkStep('ckDetails');buildSummary()}
  else{showCkStep('ckAuth');syncCkTab()}
  document.getElementById('checkoutModal').classList.add('on');
  document.body.style.overflow='hidden';
}
function closeCheckout(){document.getElementById('checkoutModal').classList.remove('on');document.body.style.overflow=''}
function showCkStep(id){document.querySelectorAll('.ck-step').forEach(s=>s.classList.remove('on'));document.getElementById(id)?.classList.add('on')}
let _currentTab='guest';
function switchCkTab(tab,btn){
  _currentTab=tab;
  document.querySelectorAll('.ck-tab').forEach(b=>b.classList.remove('on'));if(btn)btn.classList.add('on');
  document.querySelectorAll('.ck-form').forEach(f=>f.classList.remove('on'));
  ({guest:'ckGuest',login:'ckLogin',reg:'ckReg'}[tab]&&document.getElementById({guest:'ckGuest',login:'ckLogin',reg:'ckReg'}[tab])?.classList.add('on'));
}
function syncCkTab(){document.querySelectorAll('.ck-form').forEach(f=>f.classList.remove('on'));document.getElementById('ckGuest')?.classList.add('on');document.querySelectorAll('.ck-tab').forEach((b,i)=>{b.classList.toggle('on',i===0)})}
function buildSummary(){
  const c=getCart();
  document.getElementById('ckSummary').innerHTML=c.map(i=>`<div class="ck-sum-row"><span>${i.nombre} × ${i.cantidad}</span><span>${fmt(i.precio*i.cantidad)}</span></div>`).join('')+`<div class="ck-sum-row tot"><span>Total</span><span>${fmt(total(c))}</span></div>`;
}
function guestContinue(){
  const n=document.getElementById('gNom').value.trim();
  if(!n){setAlert('gAlert','Ingresá tu nombre','err');return}
  ckCliente={nombre:n,apellido:document.getElementById('gApe').value.trim(),celular:document.getElementById('gCel').value.trim()};
  showCkStep('ckDetails');buildSummary();
}
async function doLogin(){
  const cel=document.getElementById('lCel').value.trim(),pass=document.getElementById('lPass').value;
  if(!cel||!pass){setAlert('lAlert','Completá todos los campos','err');return}
  const btn=document.querySelector('#ckLogin .btn-pk');btn.disabled=true;btn.textContent='Ingresando...';
  try{const fd=new FormData();fd.append('action','login');fd.append('celular',cel);fd.append('password',pass);
    const d=await(await fetch('api/auth.php',{method:'POST',body:fd})).json();
    if(d.success){ckCliente={id:d.id,nombre:d.nombre};showCkStep('ckDetails');buildSummary()}
    else setAlert('lAlert',d.message||'Datos incorrectos','err');
  }catch{setAlert('lAlert','Error de conexión','err')}
  btn.disabled=false;btn.textContent='Ingresar →';
}
async function doRegister(){
  const n=document.getElementById('rNom').value.trim(),cel=document.getElementById('rCel').value.trim(),p=document.getElementById('rPass').value;
  if(!n||!cel||!p){setAlert('rAlert','Completá los campos obligatorios','err');return}
  if(p.length<6){setAlert('rAlert','Contraseña de al menos 6 caracteres','err');return}
  const btn=document.querySelector('#ckReg .btn-pk');btn.disabled=true;btn.textContent='Registrando...';
  try{
    const fd=new FormData();
    fd.append('action','register');fd.append('nombre',n);
    fd.append('apellido',document.getElementById('rApe').value.trim());
    fd.append('celular',cel);fd.append('password',p);
    const dni=document.getElementById('rDni')?.value.trim();
    if(dni) fd.append('dni',dni);
    const d=await(await fetch('api/auth.php',{method:'POST',body:fd})).json();
    if(d.success){ckCliente={id:d.id,nombre:d.nombre};showCkStep('ckDetails');buildSummary()}
    else if(d.merge_required){
      // DNI ya existe en otra cuenta
      if(d.has_email){
        setAlert('rAlert', d.message + ' Revisá tu casilla de correo y hacé clic en el enlace de verificación.', 'err');
      } else {
        // Sin email: confirmación directa con token (caso sin email registrado)
        if(confirm(d.message + '\n\n¿Querés vincular la nueva información a esa cuenta?')){
          window.location.href='verificar_cuenta.php?token='+d.token;
        }
      }
    }
    else setAlert('rAlert',d.message||'Error al registrar','err');
  }catch{setAlert('rAlert','Error de conexión','err')}
  btn.disabled=false;btn.textContent='Crear cuenta →';
}
function backToAuth(){ckCliente=null;showCkStep('ckAuth');syncCkTab()}

let _tipoEntrega = 'retiro';
function setEntrega(tipo){
  _tipoEntrega = tipo;
  document.getElementById('btnRetiro').classList.toggle('on', tipo==='retiro');
  document.getElementById('btnEnvio').classList.toggle('on', tipo==='envio');
  document.getElementById('wrapSucursal').style.display = tipo==='retiro' ? '' : 'none';
  document.getElementById('wrapEnvio').style.display    = tipo==='envio'  ? '' : 'none';
  // Actualizar mensaje de éxito
  const sub = document.querySelector('.ck-success-sub');
  if(sub) sub.textContent = tipo==='envio'
    ? 'Tu pedido fue registrado. Un repartidor lo llevará a tu domicilio.'
    : 'Tu pedido fue registrado. Te esperamos en la sucursal.';
}

function usarMiUbicacion(){
  if(!navigator.geolocation){setAlert('dAlert','Tu navegador no soporta geolocalización','err');return}
  const btn=document.getElementById('btnGeo'),st=document.getElementById('geoStatus');
  btn.disabled=true;btn.textContent='📍 Obteniendo...';st.textContent='';
  navigator.geolocation.getCurrentPosition(
    pos=>{
      document.getElementById('ckLat').value=pos.coords.latitude;
      document.getElementById('ckLng').value=pos.coords.longitude;
      st.textContent='✅ Ubicación obtenida. Podés completar la dirección arriba para más detalle.';
      btn.disabled=false;btn.textContent='📍 Usar mi ubicación actual';
    },
    ()=>{st.textContent='No se pudo obtener la ubicación. Escribí tu dirección manualmente.';btn.disabled=false;btn.textContent='📍 Usar mi ubicación actual'}
  );
}

async function confirmOrder(){
  const met=document.getElementById('ckMetodo').value;
  if(!met){setAlert('dAlert','Seleccioná un método de pago','err');return}
  if(_tipoEntrega==='retiro'){
    const suc=document.getElementById('ckSuc').value;
    if(!suc&&<?= !empty($sucursales)?'true':'false'?>){setAlert('dAlert','Seleccioná una sucursal de retiro','err');return}
  }
  if(_tipoEntrega==='envio'){
    const dir=document.getElementById('ckDireccion').value.trim();
    if(!dir){setAlert('dAlert','Ingresá tu dirección de entrega','err');return}
  }
  const btn=document.getElementById('btnConfirm');btn.disabled=true;btn.textContent='Procesando...';
  try{
    const body={
      carrito:getCart(),cliente:ckCliente,metodo_pago:+met,
      sucursal_id:_tipoEntrega==='retiro'?document.getElementById('ckSuc').value||null:null,
      observacion:document.getElementById('ckObs').value.trim(),
      total:total(getCart()),
      tipo_entrega:_tipoEntrega,
      direccion_entrega:_tipoEntrega==='envio'?document.getElementById('ckDireccion').value.trim():'',
      lat_entrega:document.getElementById('ckLat')?.value||null,
      lng_entrega:document.getElementById('ckLng')?.value||null,
    };
    const d=await(await fetch('api/crear_pedido.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})).json();
    if(d.success){document.getElementById('ckOrderNum').textContent='#'+d.id_venta;saveCart([]);renderCart();showCkStep('ckSuccess')}
    else setAlert('dAlert',d.message||'Error al procesar','err');
  }catch{setAlert('dAlert','Error de conexión. Intentá nuevamente.','err')}
  btn.disabled=false;btn.textContent='Confirmar pedido ✓';
}
function setAlert(id,msg,type){const el=document.getElementById(id);if(!el)return;el.textContent=msg;el.className='ck-alert on '+(type==='err'?'err':'ok');setTimeout(()=>el.classList.remove('on'),5000)}
document.getElementById('checkoutModal').addEventListener('click',e=>{if(e.target===e.currentTarget)closeCheckout()});

// ── TOAST ────────────────────────────────
let _tt;
function showToast(msg,type){const t=document.getElementById('toast');t.textContent=msg;t.className='toast on'+(type==='ok'?' ok-t':type==='err'?' err-t':'');clearTimeout(_tt);_tt=setTimeout(()=>t.classList.remove('on'),2500)}

// ── INIT ──────────────────────────────────
renderCart();
document.getElementById('btnOpenCart').addEventListener('click',openCart);

// ── BRANCH MAPS (Leaflet + OpenStreetMap) ──────────────────────────────


// ── GEOLOCATION: sucursal más cercana ──────────────────────────────────
function haversine(lat1,lng1,lat2,lng2){
  const R=6371, dL=Math.PI/180;
  const a=Math.sin((lat2-lat1)*dL/2)**2 + Math.cos(lat1*dL)*Math.cos(lat2*dL)*Math.sin((lng2-lng1)*dL/2)**2;
  return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}
async function findNearest() {

  const btn = document.getElementById('btnNearest');

  // detectar si ya está bloqueado (no soportado en iOS Safari, por eso el try/catch)
  if (navigator.permissions) {
    try {
      const perm = await navigator.permissions.query({ name: 'geolocation' });
      if (perm.state === 'denied') {
        Swal.fire({
          icon: 'error',
          title: 'Ubicación bloqueada',
          html: `
            Tenés la ubicación bloqueada ❌<br><br>
            👉 Tocá el candado 🔒 arriba en la barra del navegador<br>
            👉 Activá <b>Ubicación → Permitir</b><br><br>
            Luego recargá la página
          `,
          showCancelButton: true,
          cancelButtonText: 'Entendido',
          cancelButtonColor: '#888',
          confirmButtonText: '📍 Permitir ubicación',
          confirmButtonColor: '#22c55e',
        }).then(result => {
          if (result.isConfirmed) pedirUbicacion();
        });
        return;
      }
    } catch(e) {
      // navigator.permissions.query no soportado (iOS Safari) — continuar normalmente
    }
  }

  const cards = [...document.querySelectorAll('.branch-card[data-lat]')]
    .filter(c => c.dataset.lat && c.dataset.lng);

  if (!cards.length) {
    showToast('Las sucursales aún no tienen coordenadas cargadas', 'err');
    return;
  }

  if (!navigator.geolocation) {
    showToast('Tu navegador no soporta geolocalización', 'err');
    return;
  }

  btn.textContent = '📍 Buscando...';
  btn.disabled = true;

  navigator.geolocation.getCurrentPosition(

    // ✅ SUCCESS
    pos => {

      const ulat = pos.coords.latitude;
      const ulng = pos.coords.longitude;

      let minDist = Infinity, nearest = null, nearestIdx = null;

      cards.forEach(c => {
        const d = haversine(
          ulat,
          ulng,
          parseFloat(c.dataset.lat),
          parseFloat(c.dataset.lng)
        );

        if (d < minDist) {
          minDist = d;
          nearest = c;
          nearestIdx = c.id.split('-')[1];
        }
      });

      document.querySelectorAll('.branch-card')
        .forEach(c => c.classList.remove('branch-highlight'));

      document.querySelectorAll('.branch-nearest-badge')
        .forEach(b => b.style.display = 'none');

      nearest.classList.add('branch-highlight');
      document.getElementById('badge-' + nearestIdx).style.display = 'inline-flex';

      const km = minDist < 1
        ? (minDist * 1000).toFixed(0) + ' m'
        : (minDist.toFixed(1) + ' km');

      document.getElementById('nearestBanner').style.display = 'block';
      document.getElementById('nearestBanner').innerHTML =
        `📍 La sucursal más cercana es <strong>${nearest.dataset.nombre}</strong> — a <strong>${km}</strong> de tu ubicación`;

      nearest.scrollIntoView({ behavior: 'smooth', block: 'center' });

      btn.textContent = '📍 La más cercana';
      btn.disabled = false;
    },

    // ❌ ERROR
    err => {

      btn.textContent = '📍 La más cercana';
      btn.disabled = false;

      if (err.code === 1) {
        Swal.fire({
          icon: 'warning',
          title: 'Permiso necesario',
          html: 'Es necesario <strong>permitir la ubicación</strong> para encontrar la sucursal más cercana.',
          showCancelButton: true,
          cancelButtonText: 'Entendido',
          cancelButtonColor: '#888',
          confirmButtonText: '📍 Permitir ubicación',
          confirmButtonColor: '#22c55e',
        }).then(result => {
          if (result.isConfirmed) pedirUbicacion();
        });
      } else {
        showToast('Error obteniendo ubicación', 'err');
      }
    },

    {
      enableHighAccuracy: true,
      timeout: 10000
    }
  );
}

function pedirUbicacion() {
  if (!navigator.geolocation) {
    showToast('Tu navegador no soporta geolocalización', 'err');
    return;
  }
  navigator.geolocation.getCurrentPosition(
    () => {
      showToast('Ubicación permitida ✓', 'ok');
      findNearest();
    },
    err => {
      if (err.code === 1) {
        Swal.fire({
          icon: 'warning',
          title: 'Ubicación bloqueada',
          html: 'Bloqueaste la ubicación en este sitio.<br><br>👉 Tocá el candado 🔒 en la barra del navegador y activá <b>Ubicación → Permitir</b>, luego recargá la página.',
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#0a0a0a',
        });
      } else {
        showToast('No se pudo obtener la ubicación', 'err');
      }
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

// ── Modal detalle box ──
let _boxModal = { id: null, nombre: null, precio: null, stock: null };

function abrirModalBox(btn) {
  const id             = +btn.dataset.boxid;
  const nombre         = btn.dataset.nombre;
  const precio         = +btn.dataset.precio;
  const precioOriginal = +btn.dataset.precioOriginal;
  const descuento      = +btn.dataset.descuento;
  const stock          = +btn.dataset.stock;
  _boxModal = { id, nombre, precio, precioOriginal, descuento, stock };
  document.getElementById('boxModalNombre').textContent = nombre;

  const precioEl = document.getElementById('boxModalPrecio');
  if (descuento > 0) {
    precioEl.innerHTML =
      `<span style="text-decoration:line-through;color:#aaa;font-size:14px;font-weight:400">$${Number(precioOriginal).toLocaleString('es-AR')}</span>
       <span style="color:#e11d48;margin-left:8px">$${Number(precio).toLocaleString('es-AR')}</span>
       <span style="font-size:12px;background:#e11d48;color:#fff;border-radius:20px;padding:2px 8px;margin-left:6px">${descuento}% OFF</span>`;
  } else {
    precioEl.textContent = '$' + Number(precio).toLocaleString('es-AR');
  }

  const items = BOX_CONTENIDO[id] || [];
  const ul = document.getElementById('boxModalItems');
  if (items.length) {
    ul.innerHTML = items.map(i =>
      `<li class="box-item-row">
        <span>${i.nombre}</span>
        <span class="box-item-qty">x${i.cantidad}</span>
      </li>`
    ).join('');
  } else {
    ul.innerHTML = '<li style="color:#888;font-size:13px">Sin detalle de contenido cargado.</li>';
  }

  document.getElementById('boxModalOverlay').classList.add('on');
  document.getElementById('boxDetailModal').classList.add('open');
}

function cerrarModalBox() {
  document.getElementById('boxModalOverlay').classList.remove('on');
  document.getElementById('boxDetailModal').classList.remove('open');
}

function addBoxDesdeModal() {
  if (requireLogin()) return;
  const { id, nombre, precio, precioOriginal, descuento, stock } = _boxModal;
  const cart = getCart(), ex = cart.find(i => i.id === id);
  if (ex) {
    if (ex.cantidad >= stock) { showToast('Máximo stock disponible', 'err'); return; }
    ex.cantidad++;
  } else {
    cart.push({ id, nombre, precio, tipo: 'box', cantidad: 1,
      precio_original: descuento > 0 ? precioOriginal : null,
      descuento_pct:   descuento > 0 ? descuento : null });
  }
  saveCart(cart);
  showToast(nombre + ' agregado ✓', 'ok');
  cerrarModalBox();
}
</script>

<nav class="bottom-nav">
  <a href="index.php" class="bn-item active">
    <span class="bn-ic">🏠</span>
    <span>Inicio</span>
  </a>
  <?php if ($cliente_id): ?>
  <a href="mis-pedidos.php" class="bn-item">
    <span class="bn-ic">📦</span>
    <span>Mis pedidos</span>
  </a>
  <?php else: ?>
  <a href="login.php" class="bn-item">
    <span class="bn-ic">📦</span>
    <span>Mis pedidos</span>
  </a>
  <?php endif; ?>
  <a href="#sucursales" class="bn-item">
    <span class="bn-ic">📍</span>
    <span>Sucursales</span>
  </a>
  <?php if ($cliente_id): ?>
  <a href="mi-cuenta.php" class="bn-item">
    <span class="bn-ic">👤</span>
    <span>Mi cuenta</span>
  </a>
  <?php else: ?>
  <a href="login.php" class="bn-item">
    <span class="bn-ic">👤</span>
    <span>Ingresar</span>
  </a>
  <?php endif; ?>
</nav>
<script src="transitions.js"></script>
</body>
</html>
