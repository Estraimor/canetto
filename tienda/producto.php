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

// Si el packaging asignado está agotado, forzar stock = 0
try {
    $chkPkg = $pdo->prepare("
        SELECT COUNT(*) FROM producto_packaging pp
        JOIN packaging pk ON pk.idpackaging = pp.packaging_idpackaging
        WHERE pp.productos_idproductos = ? AND pk.activo = 1 AND pk.stock_actual <= 0
    ");
    $chkPkg->execute([$id]);
    if ($chkPkg->fetchColumn() > 0) $prod['stock_hecho'] = 0;
} catch (Throwable $e) {}

// Cargar todas las imágenes del producto desde la tabla nueva
$stmtImg = $pdo->prepare("
    SELECT archivo FROM productos_imagenes
    WHERE productos_idproductos = ?
    ORDER BY orden ASC, id ASC
");
$stmtImg->execute([$id]);
$imagenes = $stmtImg->fetchAll(PDO::FETCH_COLUMN);
// Fallback a la imagen principal si la tabla está vacía
if (empty($imagenes) && !empty($prod['imagen'])) {
    $imagenes = [$prod['imagen']];
}

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

$stock      = (float)$prod['stock_hecho'];
$esBox      = $prod['tipo'] === 'box';
$nombre     = htmlspecialchars($prod['nombre']);
$precioNum  = (float)$prod['precio'];
$cliente_id = $_SESSION['tienda_cliente_id'] ?? null;

// Oferta desde carrusel (?oferta=ID) o buscar oferta activa del producto
$ofertaActiva = null;
$ofertaFromCarrusel = false;

$ofId = (int)($_GET['oferta'] ?? 0);
if ($ofId) {
    try {
        $stmtOf = $pdo->prepare("
            SELECT * FROM oferta
            WHERE idoferta = ? AND activo = 1 AND productos_idproductos = ?
              AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
              AND (fecha_fin   IS NULL OR fecha_fin   >= CURDATE())
        ");
        $stmtOf->execute([$ofId, $id]);
        $ofertaActiva = $stmtOf->fetch() ?: null;
        $ofertaFromCarrusel = !empty($ofertaActiva);
    } catch (Throwable $e) {}
}
if (!$ofertaActiva) {
    // Buscar oferta activa del producto aunque no venga del carrusel
    try {
        $stmtOf2 = $pdo->prepare("
            SELECT * FROM oferta
            WHERE activo = 1 AND productos_idproductos = ?
              AND tipo_panel IN ('descuento','temporada','promo')
              AND (fecha_inicio IS NULL OR fecha_inicio <= CURDATE())
              AND (fecha_fin   IS NULL OR fecha_fin   >= CURDATE())
            ORDER BY idoferta DESC LIMIT 1
        ");
        $stmtOf2->execute([$id]);
        $ofertaActiva = $stmtOf2->fetch() ?: null;
    } catch (Throwable $e) {}
}

// Calcular precio final
$precioFinal = $precioNum;
$descuentoPct = 0;
if ($ofertaActiva && (float)$ofertaActiva['valor'] > 0 && $ofertaActiva['tipo_panel'] === 'descuento') {
    $descuentoPct = (float)$ofertaActiva['valor'];
    $precioFinal  = round($precioNum * (1 - $descuentoPct / 100));
}

$precio = number_format($precioFinal, 0, ',', '.');

if ($stock <= 0)      { $pillCls='sp-out'; $pillTxt='Sin stock'; }
elseif ($stock <= 10) { $pillCls='sp-low'; $pillTxt='Pocas unidades'; }
else                  { $pillCls='sp-ok';  $pillTxt='Disponible'; }

// Parsear ingredientes: solo nombres, sin cantidades/unidades
$ingredientes = [];
if ($prod['especificaciones']) {
    foreach (preg_split('/[,\n;]+/', $prod['especificaciones']) as $part) {
        $name = preg_replace('/\s*[\d,.]+\s*(g|gr|gramos?|kg|ml|cc|oz|u\.?|unid\.?|unidades?|taza|cdas?\.?|cucharadas?)\.?\s*$/i', '', trim($part));
        $name = preg_replace('/\s+[\d,.]+\s*$/', '', $name);
        $name = trim($name, " \t\n\r\0\x0B-/()");
        if (strlen($name) > 1) $ingredientes[] = mb_strtolower(trim($name));
    }
}

// Imágenes desde tabla productos_imagenes (ya cargadas arriba)
$imgPrincipal = $imagenes[0] ?? '';

// Toppings asignados a este producto
$toppingsProd = [];
if (!$esBox) {
    try {
        $stmtTp = $pdo->prepare("
            SELECT t.idtoppings AS id, t.nombre, t.precio,
                   COALESCE(ts.stock_actual, -1) AS stock
            FROM producto_toppings pt
            JOIN toppings t ON t.idtoppings = pt.toppings_idtoppings
            LEFT JOIN toppings_stock ts ON ts.toppings_idtoppings = t.idtoppings
            WHERE pt.productos_idproductos = ? AND t.activo = 1
            ORDER BY t.precio ASC, t.nombre ASC
        ");
        $stmtTp->execute([$id]);
        $toppingsProd = $stmtTp->fetchAll(PDO::FETCH_ASSOC);
        // Si no tiene asignados, traer todos los activos
        if (empty($toppingsProd)) {
            $toppingsProd = $pdo->query("
                SELECT t.idtoppings AS id, t.nombre, t.precio,
                       COALESCE(ts.stock_actual, -1) AS stock
                FROM toppings t
                LEFT JOIN toppings_stock ts ON ts.toppings_idtoppings = t.idtoppings
                WHERE t.activo = 1
                ORDER BY t.precio ASC, t.nombre ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach ($toppingsProd as &$tp) {
            $tp['id']     = (int)$tp['id'];
            $tp['precio'] = (float)$tp['precio'];
            $tp['stock']  = (float)$tp['stock'];
        }
    } catch (Throwable $e) { $toppingsProd = []; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title><?= $nombre ?> — Canetto</title>
<link rel="icon" type="image/png" href="https://canettocookies.com/img/Logo_Canetto_Cookie.png">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ════════════════════════════════
   PRODUCTO — Mobile first
════════════════════════════════ */
@font-face {
  font-family: 'Speedee';
  src: url('<?= URL_ASSETS ?>/assets/fonts/Speedee.ttf') format('truetype');
  font-weight: 400 900;
  font-display: swap;
}
*, *::before, *::after { box-sizing: border-box; }
body { background: #f8f9fa; margin: 0; font-family: 'Speedee', system-ui, sans-serif; }

/* ── NAV ── */
.det-nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 200;
  height: 60px; padding: 0 16px;
  display: flex; align-items: center; justify-content: space-between;
  background: #fff; border-bottom: 1px solid #f0ece8;
  box-shadow: 0 1px 6px rgba(0,0,0,.06);
}
.det-nav-back {
  width: 38px; height: 38px; border-radius: 50%;
  background: #f5f5f5; border: none; cursor: pointer;
  font-size: 15px; display: flex; align-items: center; justify-content: center;
  color: #333; text-decoration: none; flex-shrink: 0;
}
.det-nav-title { display: none; }
.det-nav-cart {
  position: relative; width: 38px; height: 38px; border-radius: 50%;
  background: #1e293b; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 15px; flex-shrink: 0;
}
.det-nav-brand {
  display: flex; align-items: center; gap: 9px; text-decoration: none;
}
.det-nav-brand-icon {
  width: 36px; height: 36px; border-radius: 10px; overflow: hidden;
  background: linear-gradient(135deg,#a46678,#c88e99);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; padding: 4px;
}
.det-nav-brand-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }
.det-nav-brand-name {
  font-size: 16px; font-weight: 700; letter-spacing: 3px;
  text-transform: uppercase; color: #1e293b;
}
.det-nav-link { display: none; }

/* Header desktop-only (title + separator) */
.det-desktop-hdr { display: none; }
.det-divider-qty  { display: none; }

/* ── PILLS ── */
.det-pill {
  position: absolute; top: 12px; right: 12px;
  padding: 5px 13px; border-radius: 20px; font-size: 11px;
  font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
  pointer-events: none; z-index: 2;
}
.det-pill.sp-ok  { background: #dcfce7; color: #16a34a; }
.det-pill.sp-low { background: #fef9c3; color: #ca8a04; }
.det-pill.sp-out { background: #fee2e2; color: #dc2626; }

/* ── WRAP ── */
.det-wrap { padding-top: 60px; }

/* ── GALLERY / IMAGEN ── */
.det-img-col { display: block; }
.det-main-img-wrap {
  width: 100%; height: 68vw; max-height: 360px; min-height: 240px;
  background: linear-gradient(135deg,#fdf0f3,#fff5f7);
  display: flex; align-items: center; justify-content: center;
  position: relative; overflow: hidden;
}
.det-main-img-wrap img {
  width: 100%; height: 100%; object-fit: cover;
  transition: opacity .2s;
}
.det-img-emoji { font-size: 110px; color: #c88e99; opacity: .4; line-height: 1; }

/* ── Galería mobile: carrusel deslizable ── */
.det-thumbs-strip {
  display: flex;
  gap: 8px;
  padding: 10px 16px 4px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.det-thumbs-strip::-webkit-scrollbar { display: none; }
.det-thumb {
  flex-shrink: 0;
  width: 58px; height: 58px;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid #e5e7eb;
  cursor: pointer;
  scroll-snap-align: start;
  transition: border-color .15s, transform .15s;
}
.det-thumb img { width: 100%; height: 100%; object-fit: cover; }
.det-thumb.active { border-color: #c88e99; }
.det-thumb:hover { border-color: #c88e99; transform: scale(1.05); }

/* ── INFO CARD MOBILE ── */
.det-info-col { display: block; }
.det-breadcrumb { display: none; }

.det-body {
  background: #fff;
  border-radius: 24px 24px 0 0;
  margin-top: -24px;
  position: relative;
  padding: 0 20px 140px;
  min-height: 55vh;
}
.det-handle {
  width: 40px; height: 4px; background: #e2e8f0;
  border-radius: 4px; margin: 14px auto 22px;
}
.det-tipo-tag {
  display: inline-block; font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1.2px;
  color: #c88e99; background: #fdf0f3;
  padding: 4px 12px; border-radius: 20px; margin-bottom: 12px;
}
.det-nombre {
  font-size: 28px; font-weight: 900; color: #111;
  line-height: 1.15; margin: 0 0 14px;
  letter-spacing: -.3px;
}
.det-precio-row {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 6px; flex-wrap: wrap;
}
.det-precio {
  font-size: 22px; font-weight: 700; color: #111;
  letter-spacing: -.3px; line-height: 1;
}
.det-precio-tachado {
  font-size: .6em; font-weight: 600;
  text-decoration: line-through; color: #aaa; margin-left: 6px;
}
.det-precio-descuento {
  font-size: .5em; font-weight: 800;
  background: #dc2626; color: #fff;
  border-radius: 20px; padding: 3px 10px; margin-left: 4px;
  vertical-align: middle;
}
.det-precio-row .det-pill { position: static; font-size: 11px; border-radius: 12px; }
.det-stock-row {
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 18px; padding-bottom: 18px;
  border-bottom: 1px solid #f1f5f9;
}
.det-stock-txt { font-size: 13px; color: #94a3b8; font-weight: 500; }

/* Descripción mobile */
.det-desc {
  font-size: 16px; color: #475569; line-height: 1.85;
  margin-bottom: 22px;
}

/* Specs fallback */
.det-specs {
  background: #fdf8f9; border-radius: 14px;
  padding: 14px 16px; margin-bottom: 20px;
}
.det-specs-title {
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: #c88e99; margin-bottom: 8px;
}
.det-specs p { font-size: 14px; color: #475569; line-height: 1.7; margin: 0; }

/* Ingredientes */
.det-ingredients { margin-bottom: 22px; }
.det-ingredients-title {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: #c88e99; margin-bottom: 12px;
  display: flex; align-items: center; gap: 6px;
}
.det-ingredients-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.det-ing-chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 14px;
  background: #fff; border: 1.5px solid #ecd5da;
  border-radius: 8px; font-size: 14px; color: #444; font-weight: 500;
}
.det-ing-chip i { color: #c88e99; font-size: 6px; }

/* Box items */
.box-items-list { margin-bottom: 20px; }
.box-items-title {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: #c88e99; margin-bottom: 12px;
}
.box-item-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 0; border-bottom: 1px solid #f1e8ea;
  font-size: 15px; color: #334155;
}
.box-item-row:last-child { border-bottom: none; }
.box-item-qty { font-weight: 700; color: #c88e99; }

/* ── TOPPINGS SELECTOR ── */
.det-tp-section {
  margin-bottom: 20px;
  border: 1.5px solid #f0e8ed;
  border-radius: 16px;
  overflow: hidden;
}
.det-tp-title {
  font-size: 12px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .06em; color: #c88e99;
  padding: 12px 16px 10px;
  display: flex; align-items: center; gap: 6px;
  border-bottom: 1px solid #f5eff2;
  background: #fff9fb;
}
.det-tp-obligatorio {
  font-size: 10px; font-weight: 700; color: #dc2626;
  background: #fee2e2; padding: 2px 8px; border-radius: 20px;
  margin-left: auto; text-transform: none; letter-spacing: 0;
}
.det-tp-sub {
  font-size: 11px; color: #94a3b8; display: block; margin-top: 1px;
}
.det-tp-row-sin { border-top: 1px dashed #f0e8ed; }
.det-tp-check-sin {
  border-color: #94a3b8 !important;
}
.det-tp-row-sin.selected .det-tp-check-sin {
  background: #64748b !important; border-color: #64748b !important; color: #fff;
}
.det-tp-row-sin.selected .det-tp-nombre { color: #475569 !important; }
.det-tp-alerta {
  font-size: 12px; color: #dc2626; font-weight: 600;
  padding: 10px 16px; background: #fff5f5;
  border-top: 1px solid #fecaca;
  display: flex; align-items: center; gap: 6px;
}
.det-tp-row {
  display: flex; align-items: center; gap: 12px;
  padding: 13px 16px; cursor: pointer;
  border-bottom: 1px solid #faf5f7;
  transition: background .12s;
}
.det-tp-row:last-child { border-bottom: none; }
.det-tp-row:hover:not(.det-tp-disabled) { background: #fff5f8; }
.det-tp-row input { display: none; }
.det-tp-disabled { opacity: .45; cursor: default; }
.det-tp-info { flex: 1; min-width: 0; }
.det-tp-nombre { font-size: 14px; font-weight: 600; color: #1e293b; }
.det-tp-tag { font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 10px; margin-left: 6px; }
.det-tp-tag-out { background: #fee2e2; color: #dc2626; }
.det-tp-price {
  font-size: 13px; font-weight: 700; color: #c88e99;
  white-space: nowrap; flex-shrink: 0;
}
.det-tp-check {
  width: 22px; height: 22px; border-radius: 50%;
  border: 2px solid #e2e8f0; background: #fff;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; color: transparent; flex-shrink: 0;
  transition: all .15s;
}
.det-tp-row.selected .det-tp-check {
  background: #c88e99; border-color: #c88e99; color: #fff;
}
.det-tp-row.selected .det-tp-nombre { color: #c88e99; }

/* ── Cart fly animation ── */
@keyframes cartReceive {
  0%   { transform: scale(1)    rotate(0deg); }
  25%  { transform: scale(1.4)  rotate(-12deg); }
  55%  { transform: scale(0.88) rotate(6deg); }
  80%  { transform: scale(1.12) rotate(-3deg); }
  100% { transform: scale(1)    rotate(0deg); }
}
.cart-receive { animation: cartReceive .5s cubic-bezier(.36,.07,.19,.97) forwards; }
.fly-dot {
  position: fixed; border-radius: 50%;
  background: #c88e99; pointer-events: none; z-index: 9999;
  box-shadow: 0 2px 8px rgba(200,142,153,.5);
}

/* Cantidad en card (solo desktop) */
.det-qty-section { display: none; }
.qty-selector { display: inline-flex; align-items: center; gap: 4px; }
.qty-selector button {
  width: 38px; height: 38px; border-radius: 50%;
  background: #f5f5f5; border: 1px solid #e0e0e0;
  font-size: 20px; cursor: pointer; color: #c88e99;
  display: flex; align-items: center; justify-content: center;
  transition: background .15s;
}
.qty-selector button:active { background: #fdf0f3; border-color: #c88e99; }
.qty-selector span { min-width: 40px; text-align: center; font-size: 18px; font-weight: 800; color: #1e293b; }

/* Desktop buttons (hidden mobile) */
.det-btn-desktop { display: none; }
.det-btn-back    { display: none; }
.det-trust       { display: none; }

/* ── CTA FIJA MOBILE (estilo referencia: qty + precio) ── */
.det-cta {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 300;
  padding: 10px 16px max(20px, env(safe-area-inset-bottom));
  background: #fff; border-top: 1px solid #f0ece8;
  box-shadow: 0 -2px 16px rgba(0,0,0,.08);
}
.det-cta-inner { display: flex; align-items: center; }
.det-cta-qty  { display: none; } /* integrado dentro del botón */
.btn-add-det {
  width: 100%; padding: 6px; border: none; border-radius: 50px;
  background: #1a1a1a; color: #fff;
  font-family: inherit; cursor: default;
  transition: transform .15s;
  display: flex; align-items: center; justify-content: space-between;
  min-height: 60px; gap: 4px;
}
.btn-add-det:active:not(:disabled) { transform: scale(.98); }
.btn-add-det:disabled { background: #bbb; cursor: not-allowed; opacity: .7; }
/* Badge qty izquierdo */
.det-add-qty-badge {
  display: flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,.13);
  padding: 10px 16px; border-radius: 50px;
  font-size: 16px; font-weight: 800; flex-shrink: 0;
}
.det-add-qty-btn {
  cursor: pointer; font-size: 20px; font-weight: 300;
  line-height: 1; padding: 0 2px;
  user-select: none; opacity: .85; transition: opacity .15s;
  background: none; border: none; color: #fff;
}
.det-add-qty-btn:hover { opacity: 1; }
.det-add-qty-num { min-width: 20px; text-align: center; font-size: 17px; font-weight: 800; color: #fff; }
/* Texto central */
.btn-add-label {
  flex: 1; text-align: center;
  font-size: 16px; font-weight: 700; letter-spacing: .3px; cursor: pointer;
}
/* Badge precio derecho */
.btn-add-price {
  background: rgba(255,255,255,.13);
  padding: 10px 16px; border-radius: 50px;
  font-size: 15px; font-weight: 800; white-space: nowrap; cursor: pointer;
}

/* ════════════════════════════════
   DESKTOP ≥ 1024px
════════════════════════════════ */
@media (min-width: 1024px) {
  body { background: #ebebeb; }

  /* ── NAV ── */
  .det-nav {
    height: 64px; padding: 0 40px;
    background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.1); border-bottom: none;
  }
  .det-nav-brand { display: flex; }
  .det-nav-brand-icon { width: 36px; height: 36px; border-radius: 8px; padding: 4px; }
  .det-nav-brand-name { font-size: 15px; letter-spacing: 3px; color: #333; }
  .det-nav-back {
    background: transparent; border: 1px solid #e0e0e0; color: #555;
    width: 36px; height: 36px; border-radius: 50%; font-size: 13px;
    transition: background .15s;
  }
  .det-nav-back:hover { background: #f5f5f5; }
  .det-nav-actions { display: flex; align-items: center; gap: 10px; }
  .det-nav-cart { background: #c88e99; }
  .det-nav-link {
    display: flex; align-items: center; gap: 6px;
    padding: 0 16px; height: 36px; border-radius: 18px;
    font-size: 13px; font-weight: 500; color: #555;
    text-decoration: none; background: #f5f5f5; border: 1px solid #e5e5e5;
    transition: background .15s;
  }
  .det-nav-link:hover { background: #e8e8e8; }

  /* Ocultar CTA mobile */
  .det-cta         { display: none !important; }
  .det-divider-qty { display: block; }

  /* ── GRID PRINCIPAL: 3 filas ──
     Fila 1: header título (ambas columnas)
     Fila 2: imagen | info                */
  .det-wrap {
    max-width: 1240px; margin: 0 auto;
    padding: 80px 32px 60px;
    display: grid;
    grid-template-columns: 46fr 54fr;
    grid-template-rows: auto auto;
    column-gap: 32px;
    align-items: start;
  }

  /* ── HEADER TÍTULO (fila 1, ambas columnas) ── */
  .det-desktop-hdr {
    display: block;
    grid-column: 1 / -1;
    padding: 24px 0 0;
    margin-bottom: 24px;
  }
  .det-dsk-meta {
    font-size: 11px; font-weight: 700; color: #c88e99;
    text-transform: uppercase; letter-spacing: 1.2px;
    margin-bottom: 10px;
  }
  .det-dsk-title {
    font-size: 36px; font-weight: 900; color: #111;
    line-height: 1.1; margin: 0 0 20px;
    letter-spacing: -.5px; font-family: 'Speedee', system-ui, sans-serif;
  }
  .det-dsk-hr {
    height: 2px;
    background: linear-gradient(to right, #c88e99 0%, #e8d0d5 40%, #ebebeb 100%);
    border: none; border-radius: 2px;
  }

  /* ── COLUMNA IMAGEN (fila 2, col 1) ── */
  .det-img-col {
    position: sticky; top: 80px;
    grid-row: 2;
    background: #fff; border-radius: 16px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08), 0 0 0 1px rgba(0,0,0,.04);
  }
  .det-main-img-wrap {
    width: 100%; height: auto;
    aspect-ratio: 1 / 1;
    max-height: none; min-height: unset;
    background: #f8f8f8; border-radius: 10px;
    overflow: hidden; display: flex;
    align-items: center; justify-content: center;
  }
  .det-main-img-wrap img { width: 100%; height: 100%; object-fit: contain; }
  .det-img-emoji { font-size: 220px; opacity: .12; }
  .det-pill { top: 14px; right: 14px; font-size: 11px; padding: 5px 12px; border-radius: 6px; }

  /* Thumbnails */
  .det-thumbs-strip { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
  .det-thumb {
    width: 72px; height: 72px; border-radius: 8px; overflow: hidden;
    cursor: pointer; border: 2px solid #e8e8e8;
    background: #f8f8f8; transition: border-color .15s, transform .12s;
    flex-shrink: 0;
  }
  .det-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .det-thumb.active { border-color: #c88e99; }
  .det-thumb:hover { border-color: #c88e99; transform: scale(1.05); }

  /* ── COLUMNA INFO (fila 2, col 2) ── */
  .det-info-col { grid-row: 2; background: none; }

  /* Breadcrumb — oculto en desktop */
  .det-breadcrumb { display: none; }

  /* Card info */
  .det-body {
    background: #fff; border-radius: 16px;
    padding: 28px 30px 36px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08), 0 0 0 1px rgba(0,0,0,.04);
    margin-top: 0; min-height: auto;
  }
  .det-handle { display: none; }

  /* Ocultar título y tag: ya están en el header desktop */
  .det-tipo-tag { display: none; }
  .det-nombre   { display: none; }

  /* Precio desktop */
  .det-precio-row { margin-bottom: 8px; }
  .det-precio {
    font-size: 28px; font-weight: 700; color: #111;
    letter-spacing: -.4px; font-family: 'Speedee', system-ui, sans-serif;
  }

  /* Stock */
  .det-stock-row {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 24px; padding-bottom: 24px;
    border-bottom: 1px solid #f0f0f0; flex-wrap: wrap;
  }
  .det-stock-txt { font-size: 14px; color: #555; font-weight: 500; }
  .det-stock-row .det-pill { position: static; border-radius: 6px; }

  /* Descripción — grande, limpia, sin caja */
  .det-desc {
    font-size: 16px; line-height: 1.9; color: #444;
    margin-bottom: 24px; padding: 0; background: none; border: none;
  }

  /* Specs fallback */
  .det-specs {
    background: #fafafa; border: 1px solid #efefef;
    border-radius: 8px; padding: 16px; margin-bottom: 22px;
  }
  .det-specs-title { font-size: 11px; color: #999; margin-bottom: 8px; }
  .det-specs p { font-size: 15px; color: #555; line-height: 1.75; }
  .box-item-row { font-size: 14px; padding: 10px 0; }
  .box-items-title { color: #999; }

  /* Ingredientes */
  .det-ingredients { margin-bottom: 24px; }
  .det-ingredients-title {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: #aaa; margin-bottom: 14px;
  }
  .det-ing-chip {
    font-size: 13px; padding: 7px 14px; border-radius: 8px;
    background: #fafafa; border: 1.5px solid #e8d0d5;
    color: #444; transition: all .15s;
  }
  .det-ing-chip:hover { background: #fdf0f3; border-color: #c88e99; color: #b06070; }
  .det-ingredients-grid { gap: 7px; }

  /* Separador antes de cantidad */
  .det-divider-qty {
    height: 1px; background: #f0f0f0; margin: 0 0 22px;
  }

  /* Cantidad */
  .det-qty-section {
    display: flex; align-items: center; gap: 20px;
    margin-bottom: 18px; background: none;
    padding: 0; border: none; justify-content: flex-start;
  }
  .det-qty-label { font-size: 14px; font-weight: 600; color: #333; white-space: nowrap; }
  .qty-selector button {
    width: 38px; height: 38px; border-radius: 50%;
    background: #f5f5f5; border: 1.5px solid #e0e0e0;
    font-size: 20px; color: #555; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, border-color .15s;
  }
  .qty-selector button:hover { background: #f0f0f0; border-color: #c0c0c0; }
  .qty-selector button:active { background: #fdf0f3; border-color: #c88e99; color: #c88e99; }
  .qty-selector span { font-size: 20px; min-width: 42px; font-weight: 800; color: #111; text-align: center; }

  /* Botón primario */
  .det-btn-desktop {
    display: block; width: 100%;
    padding: 18px 20px; border: none; border-radius: 12px;
    background: #c88e99; color: #fff;
    font-size: 17px; font-weight: 700;
    cursor: pointer; font-family: inherit;
    transition: background .15s, box-shadow .15s, transform .1s;
    margin-bottom: 12px;
    box-shadow: 0 4px 14px rgba(200,142,153,.35);
    letter-spacing: .2px;
  }
  .det-btn-desktop:hover:not(:disabled) {
    background: #b87080; box-shadow: 0 6px 22px rgba(200,142,153,.45);
  }
  .det-btn-desktop:active:not(:disabled) { transform: scale(.99); }
  .det-btn-desktop:disabled { background: #e8e8e8; color: #aaa; cursor: not-allowed; box-shadow: none; }

  /* Botón secundario */
  .det-btn-back {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 13px;
    border: 1.5px solid #ddd; border-radius: 12px;
    background: #fff; color: #777;
    font-size: 14px; font-weight: 500;
    cursor: pointer; font-family: inherit; text-decoration: none;
    transition: all .15s; margin-bottom: 28px;
  }
  .det-btn-back:hover { border-color: #c88e99; color: #c88e99; background: #fdf8f9; }

  /* Trust badges */
  .det-trust {
    display: grid; grid-template-columns: repeat(3,1fr);
    border-top: 1px solid #f0f0f0; padding-top: 20px; gap: 0;
  }
  .det-trust-item { text-align: center; padding: 14px 8px; }
  .det-trust-ic { font-size: 22px; margin-bottom: 8px; color: #c88e99; }
  .det-trust-txt { font-size: 11px; font-weight: 600; color: #888; line-height: 1.4; }
}

/* ── Modal sugerencias ─────────────────────────────────────────── */
.sug-popup {
  border-radius: 28px !important;
  padding: 0 !important;
  overflow: hidden !important;
  max-width: 380px !important;
  width: 92vw !important;
}
.sug-wrap { padding: 28px 20px 8px; text-align: center; }
.sug-check {
  width: 56px; height: 56px; border-radius: 50%;
  background: linear-gradient(135deg,#2d8a4e,#3aab60);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 14px;
  box-shadow: 0 6px 20px rgba(45,138,78,.28);
  animation: sugCheckPop .4s cubic-bezier(.34,1.56,.64,1) both;
}
.sug-check i { color: #fff; font-size: 26px; }
@keyframes sugCheckPop {
  from { transform: scale(0); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}
.sug-title {
  font-family: 'Speedee', system-ui, sans-serif;
  font-size: 20px; font-weight: 900; color: #1a1a1a;
  margin-bottom: 4px; letter-spacing: -.3px;
}
.sug-sub { font-size: 13px; color: #888; margin-bottom: 18px; }
.sug-cards { display: flex; flex-direction: column; gap: 10px; text-align: left; }
.sug-card {
  display: flex; align-items: center; gap: 12px;
  background: #fdf8f9; border: 1.5px solid #f1e4e8;
  border-radius: 16px; padding: 10px 12px;
  transition: border-color .2s, box-shadow .2s;
  cursor: pointer;
}
.sug-card:hover { border-color: #c88e99; box-shadow: 0 4px 16px rgba(200,142,153,.18); }
.sug-card-img {
  width: 56px; height: 56px; border-radius: 12px;
  background: #fff; overflow: hidden; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border: 1px solid #f0e4e8;
}
.sug-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sug-card-info { flex: 1; min-width: 0; }
.sug-card-name {
  font-size: 13px; font-weight: 700; color: #1a1a1a;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-bottom: 3px;
}
.sug-card-price { font-size: 14px; font-weight: 800; color: #c88e99; }
.sug-card-btn {
  width: 32px; height: 32px; border-radius: 50%;
  background: #c88e99; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: background .2s;
}
.sug-card:hover .sug-card-btn { background: #b57585; }
.sug-card-btn i { color: #fff; font-size: 13px; }
.sug-footer-wrap { border-top: 1px solid #f1e4e8 !important; padding: 0 !important; }
.sug-skip {
  width: 100%; padding: 14px 20px;
  font-size: 13px; font-weight: 600; color: #888;
  background: none; border: none; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: color .2s;
}
.sug-skip:hover { color: #c88e99; }
.swal2-timer-progress-bar { background: #c88e99 !important; }
</style>
</head>
<body>

<!-- NAV -->
<header class="det-nav">
  <a href="javascript:history.back()" class="det-nav-back">
    <i class="fa-solid fa-arrow-left" style="font-size:14px"></i>
  </a>
  <span class="det-nav-title"><?= $esBox ? 'Box' : 'Cookie' ?></span>

  <a href="index.php" class="det-nav-brand" style="position:absolute;left:50%;transform:translateX(-50%)">
    <div class="det-nav-brand-icon">
      <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" onerror="this.style.display='none'">
    </div>
    <span class="det-nav-brand-name">Canetto</span>
  </a>

  <div class="det-nav-actions" style="display:flex;align-items:center;gap:10px">
    <a href="index.php" class="det-nav-link">
      <i class="fa-solid fa-arrow-left" style="font-size:12px"></i> Volver al catálogo
    </a>
    <button class="det-nav-cart" id="btnOpenCart2">
      <i class="fa-solid fa-cart-shopping" style="font-size:15px"></i>
      <span class="t-cart-badge" id="cartBadge2">0</span>
    </button>
  </div>
</header>

<!-- CONTENIDO PRINCIPAL -->
<div class="det-wrap">

  <!-- Título + separador: solo visible en desktop, abarca ambas columnas -->
  <div class="det-desktop-hdr">
    <div class="det-dsk-meta"><?= $esBox ? 'Box' : 'Cookie artesanal' ?></div>
    <h1 class="det-dsk-title"><?= $nombre ?></h1>
    <div class="det-dsk-hr"></div>
  </div>

  <!-- COLUMNA IMAGEN -->
  <div class="det-img-col">
    <div class="det-main-img-wrap">
      <?php if ($imgPrincipal): ?>
        <img id="mainImg"
             src="<?= URL_ASSETS ?>/img/productos/<?= htmlspecialchars($imgPrincipal) ?>"
             alt="<?= $nombre ?>">
      <?php else: ?>
        <span class="det-img-emoji">
          <i class="fa-solid <?= $esBox ? 'fa-box' : 'fa-cookie' ?>"></i>
        </span>
      <?php endif; ?>
      <span class="det-pill <?= $pillCls ?>"><?= $pillTxt ?></span>
    </div>

    <?php if (count($imagenes) > 1): ?>
    <div class="det-thumbs-strip">
      <?php foreach ($imagenes as $i => $img): ?>
      <div class="det-thumb <?= $i === 0 ? 'active' : '' ?>"
           onclick="switchImg(this, '<?= URL_ASSETS ?>/img/productos/<?= htmlspecialchars($img) ?>')">
        <img src="<?= URL_ASSETS ?>/img/productos/<?= htmlspecialchars($img) ?>" alt="">
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- COLUMNA INFO -->
  <div class="det-info-col">

    <!-- Breadcrumb (solo desktop) -->
    <div class="det-breadcrumb">
      <a href="index.php">Inicio</a>
      <span>›</span>
      <a href="index.php"><?= $esBox ? 'Boxes' : 'Cookies' ?></a>
      <span>›</span>
      <?= $nombre ?>
    </div>

    <div class="det-body">
      <div class="det-handle"></div>

      <div class="det-tipo-tag"><?= $esBox ? 'Box' : 'Cookie artesanal' ?></div>
      <?php if ($ofertaActiva && !empty($ofertaActiva['titulo'])): ?>
      <div style="display:inline-flex;align-items:center;gap:6px;background:#fef2f2;color:#dc2626;border-radius:20px;padding:4px 12px;font-size:.75rem;font-weight:700;margin-bottom:8px">
        <?php if ($ofertaFromCarrusel): ?>⚡ Oferta especial<?php else: ?>🏷 Oferta activa<?php endif; ?> — <?= htmlspecialchars($ofertaActiva['titulo']) ?>
      </div>
      <?php endif; ?>
      <div class="det-nombre"><?= $nombre ?></div>
      <div class="det-precio-row">
        <div class="det-precio">
          $<?= $precio ?>
          <?php if ($descuentoPct > 0): ?>
            <span class="det-precio-tachado">$<?= number_format($precioNum,0,',','.') ?></span>
            <span class="det-precio-descuento">-<?= $descuentoPct ?>%</span>
          <?php endif; ?>
        </div>
        <span class="det-pill <?= $pillCls ?>" style="position:static"><?= $pillTxt ?></span>
      </div>

      <?php if ($stock <= 0): ?>
      <div class="det-stock-row">
        <span class="det-stock-txt">Sin stock disponible</span>
      </div>
      <?php elseif ($stock <= 10): ?>
      <div class="det-stock-row">
        <span class="det-stock-txt">¡Solo quedan <?= (int)$stock ?>!</span>
      </div>
      <?php endif; ?>

      <?php if ($prod['descripcion']): ?>
      <div class="det-desc"><?= nl2br(htmlspecialchars($prod['descripcion'])) ?></div>
      <?php endif; ?>

      <?php if ($esBox && !empty($boxItems)): ?>
      <div class="box-items-list">
        <div class="box-items-title">
          <i class="fa-solid fa-box" style="margin-right:5px"></i>Contenido del box
        </div>
        <?php foreach ($boxItems as $item): ?>
        <div class="box-item-row">
          <span><?= htmlspecialchars($item['nombre']) ?></span>
          <span class="box-item-qty">×<?= (int)$item['cantidad'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>


      <!-- SELECTOR DE TOPPINGS -->
      <?php if (!$esBox && !empty($toppingsProd)): ?>
      <div class="det-tp-section" id="detTpSection">
        <div class="det-tp-title">
          <i class="fa-solid fa-sparkles"></i> Elegí tu topping
        </div>
        <div class="det-tp-list" id="detTpList">
          <?php foreach ($toppingsProd as $tp): ?>
            <?php $sinStock = $tp['stock'] == 0; ?>
            <label class="det-tp-row <?= $sinStock ? 'det-tp-disabled' : '' ?>">
              <input type="checkbox" class="det-tp-chk" name="topping"
                     value="<?= $tp['id'] ?>"
                     data-nombre="<?= htmlspecialchars($tp['nombre']) ?>"
                     data-precio="<?= $tp['precio'] ?>"
                     <?= $sinStock ? 'disabled' : '' ?>
                     onchange="onToppingChange()">
              <div class="det-tp-info">
                <span class="det-tp-nombre"><?= htmlspecialchars($tp['nombre']) ?></span>
                <?php if ($sinStock): ?>
                  <span class="det-tp-tag det-tp-tag-out">Sin stock</span>
                <?php endif; ?>
              </div>
              <span class="det-tp-price">
                <?= $tp['precio'] > 0 ? '+$'.number_format($tp['precio'],0,',','.') : 'Gratis' ?>
              </span>
              <span class="det-tp-check"><i class="fa-solid fa-check"></i></span>
            </label>
          <?php endforeach; ?>
          <!-- Opción sin topping -->
          <div class="det-tp-row det-tp-row-sin" id="detTpSinRow" onclick="toggleSinTopping()">
            <div class="det-tp-info">
              <span class="det-tp-nombre" style="color:#64748b">Sin topping</span>
              <span class="det-tp-sub">Continuar sin agregar extras</span>
            </div>
            <span class="det-tp-price" style="color:#94a3b8">$0</span>
            <span class="det-tp-check det-tp-check-sin" id="detTpSinCheck"><i class="fa-solid fa-check"></i></span>
          </div>
        </div>
        <div class="det-tp-alerta" id="detTpAlerta" style="display:none">
          <i class="fa-solid fa-triangle-exclamation"></i> Seleccioná un topping o elegí "Sin topping"
        </div>
      </div>
      <?php endif; ?>

      <!-- Separador + cantidad (solo desktop) -->
      <?php if ($stock > 0): ?>
      <div class="det-divider-qty"></div>
      <div class="det-qty-section">
        <div class="det-qty-label">Cantidad</div>
        <div class="qty-selector">
          <button onclick="cambiarQty(-1)">−</button>
          <span id="qtyVal">1</span>
          <button onclick="cambiarQty(1)">+</button>
        </div>
      </div>
      <?php endif; ?>

      <!-- Botón desktop -->
      <button class="det-btn-desktop" id="btnAddDetDesk"
        <?= $stock <= 0 ? 'disabled' : '' ?>
        onclick="agregarAlCarrito()">
        <i class="fa-solid fa-cart-plus" style="margin-right:8px"></i>
        <?= $stock <= 0 ? 'Sin stock disponible' : 'Agregar al carrito' ?>
      </button>

      <a href="index.php" class="det-btn-back">
        <i class="fa-solid fa-arrow-left" style="font-size:12px"></i> Ver más productos
      </a>

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
  </div>

</div><!-- /det-wrap -->

<!-- CTA MOBILE — estilo McDonald's -->
<div class="det-cta">
  <div class="det-cta-inner">
    <button class="btn-add-det" id="btnAddDet" <?= $stock <= 0 ? 'disabled' : '' ?>>
      <?php if ($stock > 0): ?>
        <span class="det-add-qty-badge">
          <span class="det-add-qty-btn" onclick="event.stopPropagation();cambiarQty(-1)">−</span>
          <span class="det-add-qty-num" id="qtyValMob">1</span>
          <span class="det-add-qty-btn" onclick="event.stopPropagation();cambiarQty(1)">+</span>
        </span>
        <span class="btn-add-label" onclick="agregarAlCarrito()">Agregar</span>
        <span class="btn-add-price" id="ctaPrice" onclick="agregarAlCarrito()">$<?= number_format($precioFinal, 0, ',', '.') ?></span>
      <?php else: ?>
        <span class="btn-add-label">Sin stock disponible</span>
      <?php endif; ?>
    </button>
  </div>
</div>

<script>
const PROD_ID    = <?= (int)$prod['idproductos'] ?>;
const PROD_NOMBRE= <?= json_encode($prod['nombre']) ?>;
const PROD_PRECIO= <?= $precioFinal ?>; // precio final con descuento si aplica
const PROD_PRECIO_ORIGINAL = <?= $precioNum ?>;
const PROD_DESCUENTO = <?= $descuentoPct ?>;
const PROD_TIPO  = <?= json_encode($prod['tipo']) ?>;
const PROD_STOCK = <?= (int)$stock ?>;
const PROD_IMAGEN= <?= json_encode($imgPrincipal) ?>;
const CLIENTE_PHP= <?= json_encode($cliente_id ? ['id'=>$cliente_id] : null) ?>;

const CK      = CLIENTE_PHP ? 'canetto_cart_' + CLIENTE_PHP.id : 'canetto_cart_guest';
const getCart = () => { try { return JSON.parse(localStorage.getItem(CK)||'[]'); } catch { return []; } };
const saveCart = c => { localStorage.setItem(CK, JSON.stringify(c)); updateBadge(); };
const fmtARS  = n => '$' + Number(n).toLocaleString('es-AR', {minimumFractionDigits:0});

function updateBadge(){
  const n = getCart().reduce((s,i)=>s+i.cantidad,0);
  const b = document.getElementById('cartBadge2');
  if(b){ b.textContent = n > 99 ? '99+' : n; b.classList.toggle('on', n>0); }
}

let qty = 1;
function cambiarQty(d){
  qty = Math.max(1, Math.min(qty + d, PROD_STOCK));
  const v = document.getElementById('qtyVal');
  const vm = document.getElementById('qtyValMob');
  if(v)  v.textContent  = qty;
  if(vm) vm.textContent = qty;
  actualizarPrecioBtn();
}

const PROD_TOPPINGS = <?= json_encode(array_values($toppingsProd ?? []), JSON_UNESCAPED_UNICODE) ?>;

const HAS_TOPPINGS = <?= !$esBox && !empty($toppingsProd) ? 'true' : 'false' ?>;
let _sinTopping = false;

function getSelectedToppings() {
  return [...document.querySelectorAll('.det-tp-chk:checked')].map(c => ({
    id:     parseInt(c.value),
    nombre: c.dataset.nombre,
    precio: parseFloat(c.dataset.precio) || 0
  }));
}

function toppingSeleccionado() {
  if (!HAS_TOPPINGS) return true;
  return _sinTopping || document.querySelectorAll('.det-tp-chk:checked').length > 0;
}

function toggleSinTopping() {
  _sinTopping = !_sinTopping;
  const row = document.getElementById('detTpSinRow');
  if (!row) return;
  if (_sinTopping) {
    document.querySelectorAll('.det-tp-chk').forEach(c => {
      c.checked = false;
      c.closest('.det-tp-row').classList.remove('selected');
    });
    row.classList.add('selected');
  } else {
    row.classList.remove('selected');
  }
  actualizarPrecioBtn();
  const al = document.getElementById('detTpAlerta');
  if(al) al.style.display = 'none';
}

function onToppingChange() {
  document.querySelectorAll('.det-tp-chk').forEach(c => {
    c.closest('.det-tp-row').classList.toggle('selected', c.checked);
  });
  // Si elige un topping, desmarcar "sin topping"
  if (document.querySelectorAll('.det-tp-chk:checked').length > 0 && _sinTopping) {
    _sinTopping = false;
    const row = document.getElementById('detTpSinRow');
    const chk = document.getElementById('detTpSinChk');
    if(row) row.classList.remove('selected');
    if(chk) chk.checked = false;
  }
  actualizarPrecioBtn();
  document.getElementById('detTpAlerta').style.display = 'none';
}

function actualizarPrecioBtn() {
  const extra    = _sinTopping ? 0 : getSelectedToppings().reduce((s,t) => s + t.precio, 0);
  const porUnidad = PROD_PRECIO + extra;
  const total    = porUnidad * qty;
  const cp = document.getElementById('ctaPrice');
  if (cp) {
    if (extra > 0 && qty > 1) {
      cp.innerHTML = `<span style="font-size:.7em;opacity:.75">${fmtARS(porUnidad)} × ${qty}</span><br>${fmtARS(total)}`;
    } else {
      cp.textContent = fmtARS(total);
    }
  }
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
  // Validar selección de topping
  if (HAS_TOPPINGS && !toppingSeleccionado()) {
    Swal.fire({
      icon: 'info',
      title: '¿Con qué topping?',
      html: `<p style="font-size:15px;color:#555;line-height:1.6">
               Elegí un topping para tu cookie o seleccioná
               <strong>"Sin topping"</strong> para continuar sin extras.
             </p>`,
      confirmButtonColor: '#c88e99',
      confirmButtonText: 'Elegir topping',
      showClass: { popup: 'animate__animated animate__fadeInDown animate__faster' },
    }).then(() => {
      document.getElementById('detTpList')?.scrollIntoView({ behavior:'smooth', block:'center' });
    });
    return;
  }
  const selectedToppings = _sinTopping ? [] : getSelectedToppings();
  const extraPrecio = selectedToppings.reduce((s,t) => s + t.precio, 0);
  const precioTotal = PROD_PRECIO + extraPrecio;

  const cart = getCart();
  // Buscar ítem con mismos toppings
  const tpIds = selectedToppings.map(t=>t.id).sort().join(',');
  const ex = cart.find(i => i.id === PROD_ID && (i._tpKey||'') === tpIds);

  if(ex ? ex.cantidad + qty > PROD_STOCK : qty > PROD_STOCK){
    Swal.fire({ icon:'warning', title:'Stock insuficiente',
      text:`Solo quedan ${PROD_STOCK} unidades disponibles.`,
      confirmButtonColor:'#c88e99', confirmButtonText:'Entendido' });
    return;
  }
  if(ex) {
    ex.cantidad += qty;
  } else {
    cart.push({
      id: PROD_ID, nombre: PROD_NOMBRE, precio: precioTotal,
      tipo: PROD_TIPO, imagen: PROD_IMAGEN, cantidad: qty,
      toppings: selectedToppings, _tpKey: tpIds
    });
  }
  saveCart(cart);
  flyToCart();

  // Marcar botón como agregado
  const btn  = document.getElementById('btnAddDet');
  const btnD = document.getElementById('btnAddDetDesk');
  [btn, btnD].forEach(b => {
    if(!b) return;
    b.innerHTML = '<span class="btn-add-label">✓ Agregado</span>';
    b.style.background = '#2d8a4e';
  });

  const _goCart = () => { window.location.href = 'index.php?carrito=1'; };
  const _fmtPrecio = n => '$' + Number(n).toLocaleString('es-AR', {maximumFractionDigits:0});

  // Buscar sugerencias y mostrar modal
  fetch(`<?= URL_ASSETS ?>/tienda/api/sugeridos.php?id=${PROD_ID}&tipo=${encodeURIComponent(PROD_TIPO)}`)
    .then(r => r.json())
    .then(sugeridos => {
      if (!sugeridos || sugeridos.length === 0) { setTimeout(_goCart, 550); return; }

      const cardsHtml = sugeridos.map(p => {
        const icon = p.tipo === 'box' ? 'fa-box-open' : 'fa-cookie-bite';
        const imgSrc = `<?= URL_ASSETS ?>/img/productos/${encodeURIComponent(p.imagen || '')}`;
        return `
          <a href="producto.php?id=${p.idproductos}" class="sug-card">
            <div class="sug-card-img">
              <img src="${imgSrc}" alt="${p.nombre}"
                   onerror="this.parentElement.innerHTML='<i class=\'fa-solid ${icon}\' style=\'font-size:28px;color:#c88e99\'></i>'">
            </div>
            <div class="sug-card-info">
              <div class="sug-card-name">${p.nombre}</div>
              <div class="sug-card-price">${_fmtPrecio(p.precio)}</div>
            </div>
            <div class="sug-card-btn"><i class="fa-solid fa-plus"></i></div>
          </a>`;
      }).join('');

      Swal.fire({
        html: `
          <div class="sug-wrap">
            <div class="sug-check"><i class="fa-solid fa-circle-check"></i></div>
            <div class="sug-title">¡Agregado!</div>
            <div class="sug-sub">¿Querés sumarle algo más?</div>
            <div class="sug-cards">${cardsHtml}</div>
          </div>`,
        showConfirmButton: false,
        showCancelButton:  false,
        showCloseButton:   false,
        footer: `<button class="sug-skip" onclick="Swal.close()">
                   No gracias, ir al carrito <i class="fa-solid fa-arrow-right"></i>
                 </button>`,
        customClass: { popup: 'sug-popup', footer: 'sug-footer-wrap' },
        timer: 14000,
        timerProgressBar: true,
        didClose: _goCart,
        willOpen: () => {
          // Evitar que el footer de Swal tenga padding propio
          const f = document.querySelector('.swal2-footer');
          if (f) { f.style.padding = '0'; f.style.margin = '0'; }
        }
      });
    })
    .catch(() => { setTimeout(_goCart, 550); });
}

function flyToCart() {
  const cartBtn = document.getElementById('btnOpenCart2') || document.querySelector('.det-nav-cart');
  const fromEl  = document.getElementById('btnAddDet');
  if (!cartBtn || !fromEl) return;

  const from = fromEl.getBoundingClientRect();
  const to   = cartBtn.getBoundingClientRect();

  const startX = from.left + from.width  / 2;
  const startY = from.top  + from.height / 2;
  const endX   = to.left   + to.width    / 2;
  const endY   = to.top    + to.height   / 2;

  // Control point for the arc — above the midpoint
  const ctrlX  = (startX + endX) / 2;
  const ctrlY  = Math.min(startY, endY) - 90;

  const SIZE    = 16;
  const DURATION = 620;

  const dot = document.createElement('div');
  dot.className = 'fly-dot';
  dot.style.cssText = `width:${SIZE}px;height:${SIZE}px;left:${startX - SIZE/2}px;top:${startY - SIZE/2}px;`;
  document.body.appendChild(dot);

  const start = performance.now();

  function ease(t) {
    // ease-in-out cubic
    return t < .5 ? 4*t*t*t : 1 - Math.pow(-2*t+2, 3)/2;
  }

  function tick(now) {
    const raw = Math.min((now - start) / DURATION, 1);
    const t   = ease(raw);

    // Quadratic bezier arc
    const x = (1-t)*(1-t)*startX + 2*(1-t)*t*ctrlX + t*t*endX;
    const y = (1-t)*(1-t)*startY + 2*(1-t)*t*ctrlY + t*t*endY;

    const scale   = 1 - t * 0.65;
    const opacity = raw > 0.72 ? 1 - (raw - 0.72) / 0.28 : 1;

    dot.style.left    = (x - SIZE/2) + 'px';
    dot.style.top     = (y - SIZE/2) + 'px';
    dot.style.transform  = `scale(${scale})`;
    dot.style.opacity    = opacity;

    if (raw < 1) {
      requestAnimationFrame(tick);
    } else {
      dot.remove();
      cartBtn.classList.remove('cart-receive');
      void cartBtn.offsetWidth; // reflow para reiniciar animación si se llama dos veces
      cartBtn.classList.add('cart-receive');
      cartBtn.addEventListener('animationend', () => cartBtn.classList.remove('cart-receive'), { once: true });
    }
  }

  requestAnimationFrame(tick);
}

function switchImg(thumb, src){
  const img = document.getElementById('mainImg');
  if(img){ img.style.opacity = '0'; setTimeout(()=>{ img.src=src; img.style.opacity='1'; },150); }
  document.querySelectorAll('.det-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}

document.getElementById('btnOpenCart2')?.addEventListener('click', () => {
  window.location.href = 'index.php';
});

updateBadge();
window.addEventListener('pageshow', updateBadge);
actualizarPrecioBtn();

</script>
<script src="transitions.js"></script>
</body>
</html>
