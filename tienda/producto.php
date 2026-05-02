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
$precio     = number_format((float)$prod['precio'], 0, ',', '.');
$precioNum  = (float)$prod['precio'];
$cliente_id = $_SESSION['tienda_cliente_id'] ?? null;

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

// Imágenes: soporta comma-separated en campo imagen, o tabla producto_imagenes
$imagenes = [];
if (!empty($prod['imagen'])) {
    $imagenes = array_values(array_filter(array_map('trim', explode(',', $prod['imagen']))));
}
try {
    $stmtImgs = $pdo->prepare("SELECT imagen FROM producto_imagenes WHERE idproducto = ? AND activo = 1 ORDER BY orden ASC");
    $stmtImgs->execute([$id]);
    $extra = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($extra)) $imagenes = $extra;
} catch (Exception $e) {}
$imgPrincipal = $imagenes[0] ?? '';
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
*, *::before, *::after { box-sizing: border-box; }
body { background: #f8f9fa; margin: 0; font-family: inherit; }

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
.det-thumbs-strip { display: none; }

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
  display: inline-block; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 1px;
  color: #c88e99; background: #fdf0f3;
  padding: 5px 13px; border-radius: 20px; margin-bottom: 10px;
}
.det-nombre {
  font-size: 26px; font-weight: 800; color: #1e293b;
  line-height: 1.2; margin: 0 0 8px;
}
.det-precio {
  font-size: 32px; font-weight: 800; color: #c88e99;
  margin-bottom: 14px;
}
.det-stock-row {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 20px; padding-bottom: 20px;
  border-bottom: 1px solid #f1f5f9;
}
.det-stock-row .det-pill { position: static; font-size: 12px; border-radius: 12px; }
.det-stock-txt { font-size: 15px; color: #64748b; font-weight: 500; }

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
.det-cta-inner { display: flex; align-items: center; gap: 10px; }
.det-cta-qty {
  display: inline-flex; align-items: center;
  background: #f1f5f9; border-radius: 14px; padding: 0 4px; flex-shrink: 0;
}
.det-cta-qty button {
  width: 40px; height: 50px;
  background: transparent; border: none; cursor: pointer;
  font-size: 22px; color: #c88e99; line-height: 1;
  display: flex; align-items: center; justify-content: center;
}
.det-cta-qty span {
  min-width: 34px; text-align: center;
  font-size: 18px; font-weight: 800; color: #1e293b;
}
.btn-add-det {
  flex: 1; padding: 14px 12px; border: none; border-radius: 14px;
  background: #1e293b; color: #fff;
  font-size: 15px; font-weight: 700; cursor: pointer;
  font-family: inherit; letter-spacing: .1px;
  transition: transform .15s, opacity .15s;
  display: flex; align-items: center; justify-content: center;
  flex-direction: column; gap: 2px;
}
.btn-add-det:active:not(:disabled) { transform: scale(.97); }
.btn-add-det:disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }
.btn-add-label { font-size: 15px; font-weight: 700; }
.btn-add-price { font-size: 13px; font-weight: 500; opacity: .8; }

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
    font-size: 13px; font-weight: 500; color: #c88e99;
    text-transform: uppercase; letter-spacing: 1px;
    margin-bottom: 10px;
  }
  .det-dsk-title {
    font-size: 32px; font-weight: 800; color: #1a1a1a;
    line-height: 1.15; margin: 0 0 20px; font-family: inherit;
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

  /* Precio — primera info visible en la card */
  .det-precio {
    font-size: 46px; font-weight: 300; color: #111;
    margin-bottom: 6px; letter-spacing: -2px; font-family: inherit;
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

      <?php if (!empty($ingredientes)): ?>
      <div class="det-ingredients">
        <div class="det-ingredients-title">
          <i class="fa-solid fa-wheat-awn"></i> Ingredientes
        </div>
        <div class="det-ingredients-grid">
          <?php foreach ($ingredientes as $ing): ?>
          <div class="det-ing-chip">
            <i class="fa-solid fa-circle"></i>
            <?= htmlspecialchars($ing) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php elseif ($prod['especificaciones']): ?>
      <div class="det-specs">
        <div class="det-specs-title">Especificaciones</div>
        <p><?= nl2br(htmlspecialchars($prod['especificaciones'])) ?></p>
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

<!-- CTA MOBILE (qty + precio al estilo referencia) -->
<div class="det-cta">
  <div class="det-cta-inner">
    <?php if ($stock > 0): ?>
    <div class="det-cta-qty">
      <button onclick="cambiarQty(-1)">−</button>
      <span id="qtyValMob">1</span>
      <button onclick="cambiarQty(1)">+</button>
    </div>
    <?php endif; ?>
    <button class="btn-add-det" id="btnAddDet"
      <?= $stock <= 0 ? 'disabled' : '' ?>
      onclick="agregarAlCarrito()">
      <?php if ($stock > 0): ?>
        <span class="btn-add-label">
          <i class="fa-solid fa-cart-plus"></i> Agregar al carrito
        </span>
        <span class="btn-add-price" id="ctaPrice">$<?= $precio ?></span>
      <?php else: ?>
        <span class="btn-add-label">Sin stock disponible</span>
      <?php endif; ?>
    </button>
  </div>
</div>

<script>
const PROD_ID    = <?= (int)$prod['idproductos'] ?>;
const PROD_NOMBRE= <?= json_encode($prod['nombre']) ?>;
const PROD_PRECIO= <?= $precioNum ?>;
const PROD_TIPO  = <?= json_encode($prod['tipo']) ?>;
const PROD_STOCK = <?= (int)$stock ?>;
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
  const cp = document.getElementById('ctaPrice');
  if(v)  v.textContent  = qty;
  if(vm) vm.textContent = qty;
  if(cp) cp.textContent = fmtARS(qty * PROD_PRECIO);
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

  const btn  = document.getElementById('btnAddDet');
  const btnD = document.getElementById('btnAddDetDesk');
  [btn, btnD].forEach(b => {
    if(!b) return;
    const orig = b.innerHTML;
    b.innerHTML = '<span class="btn-add-label">✓ Agregado</span>';
    b.style.background = '#2d8a4e';
    setTimeout(() => { b.innerHTML = orig; b.style.background = ''; }, 1400);
  });
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
</script>
<script src="transitions.js"></script>
</body>
</html>
