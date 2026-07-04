<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = Conexion::conectar();

$cliente_id     = $_SESSION['tienda_cliente_id'] ?? null;
$cliente_nombre = $_SESSION['tienda_cliente_nombre'] ?? '';

$sucursales = $pdo->query("
    SELECT idsucursal, nombre, direccion, ciudad, provincia, telefono, email, latitud, longitud
    FROM sucursal WHERE activo = 1 ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Sucursales — Canetto</title>
<link rel="icon" type="image/png" href="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ── Desktop overrides ── */
@media (min-width: 1024px) {
  body { padding-bottom: 0 !important; background: #f0eeec !important; }
  .t-nav { padding: 0 56px; box-shadow: 0 1px 0 #e8e8e8; }
  .t-nav-links { display: flex !important; gap: 4px; align-items: center; }
  .t-nav-link {
    font-size: 14px; font-weight: 600; color: #1e293b;
    text-decoration: none; padding: 7px 16px;
    border-radius: 20px; transition: background .15s;
  }
  .t-nav-link:hover { background: #f1f5f9; }
}

/* ── Page layout ── */
.suc-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 16px 40px;
}
@media (min-width: 1024px) {
  .suc-page { padding: 48px 56px 80px; }
}

/* ── Header section ── */
.suc-hero {
  text-align: center;
  padding: 28px 20px 24px;
  background: linear-gradient(135deg, #fdf0f3, #fff5f7);
  border-radius: 20px;
  margin-bottom: 28px;
}
.suc-hero-ic {
  width: 64px; height: 64px;
  background: var(--pk);
  border-radius: 18px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 14px;
  font-size: 28px; color: #fff;
  box-shadow: 0 4px 16px rgba(200,142,153,.35);
}
.suc-hero-title {
  font-family: "Speedee", sans-serif;
  font-size: 26px; font-weight: 800;
  color: var(--dk); margin-bottom: 6px;
}
.suc-hero-title em { font-style: italic; color: var(--pk); }
.suc-hero-sub {
  font-size: 14px; color: #94a3b8;
}
@media (min-width: 640px) {
  .suc-hero-title { font-size: 32px; }
}
@media (min-width: 1024px) {
  .suc-hero { padding: 48px 80px; margin-bottom: 40px; }
  .suc-hero-title { font-size: 40px; }
  .suc-hero-sub { font-size: 16px; }
}

/* ── Nearest button ── */
.btn-nearest-big {
  display: inline-flex; align-items: center; gap: 8px;
  margin-top: 16px;
  padding: 11px 22px;
  background: var(--pk); color: #fff;
  border: none; border-radius: 50px;
  font-family: inherit; font-size: 14px; font-weight: 700;
  cursor: pointer; transition: background .2s, transform .1s;
  box-shadow: 0 4px 12px rgba(200,142,153,.3);
}
.btn-nearest-big:hover { background: #d4708a; }
.btn-nearest-big:active { transform: scale(.97); }

/* ── Banner más cercana ── */
#nearestBanner {
  display: none;
  margin-bottom: 20px;
  padding: 12px 18px;
  background: var(--pk-lt);
  border-left: 3px solid var(--pk);
  border-radius: 12px;
  font-size: 13px; color: var(--dk);
}

/* ── Grid de sucursales ── */
.suc-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
}
@media (min-width: 640px) {
  .suc-grid { grid-template-columns: 1fr 1fr; gap: 20px; }
}

/* ── Tarjeta ── */
.suc-card {
  background: #fff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
  border: 1.5px solid #f0e8ea;
  transition: box-shadow .2s, transform .2s;
}
.suc-card:hover {
  box-shadow: 0 6px 24px rgba(200,142,153,.18);
  transform: translateY(-2px);
}
.suc-card.nearest {
  border-color: var(--pk);
  box-shadow: 0 6px 24px rgba(200,142,153,.22);
}

/* Mapa embed */
.suc-map {
  width: 100%; height: 180px;
  object-fit: cover;
  display: block;
}
.suc-map iframe {
  width: 100%; height: 180px;
  border: 0; display: block;
}
.suc-map-placeholder {
  width: 100%; height: 180px;
  background: linear-gradient(135deg, #f9edf0, #fff5f7);
  display: flex; align-items: center; justify-content: center;
  font-size: 40px; color: var(--pk); opacity: .4;
}

/* Body de la tarjeta */
.suc-body {
  padding: 18px 18px 20px;
}
.suc-top {
  display: flex; align-items: flex-start; gap: 10px;
  margin-bottom: 12px;
}
.suc-ic {
  width: 38px; height: 38px; flex-shrink: 0;
  background: #fdf0f3; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: var(--pk); font-size: 16px;
}
.suc-name {
  font-size: 16px; font-weight: 800;
  color: var(--dk); line-height: 1.3;
  flex: 1;
}
.suc-nearest-badge {
  display: none;
  font-size: 11px; font-weight: 700;
  background: var(--pk); color: #fff;
  padding: 3px 10px; border-radius: 50px;
  flex-shrink: 0;
}
.suc-card.nearest .suc-nearest-badge { display: inline-flex; align-items: center; gap: 4px; }
.suc-addr {
  font-size: 13px; color: #64748b;
  margin-bottom: 14px; line-height: 1.5;
  display: flex; align-items: flex-start; gap: 8px;
}
.suc-addr i { color: var(--pk); margin-top: 2px; flex-shrink: 0; }

/* Chips contacto */
.suc-chips {
  display: flex; flex-wrap: wrap; gap: 8px;
  margin-bottom: 14px;
}
.suc-chip {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 12px;
  background: #fdf0f3; border-radius: 50px;
  font-size: 12px; font-weight: 600; color: var(--pk);
  text-decoration: none;
  transition: background .15s;
}
.suc-chip:hover { background: #f5d8de; }

/* Botón cómo llegar */
.btn-dir {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%;
  padding: 12px;
  background: var(--dk); color: #fff;
  border: none; border-radius: 12px;
  font-family: inherit; font-size: 14px; font-weight: 700;
  text-decoration: none;
  transition: background .2s;
}
.btn-dir:hover { background: var(--pk); }

/* ── Empty state ── */
.suc-empty {
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
}
.suc-empty-ic { font-size: 48px; margin-bottom: 16px; opacity: .35; }
.suc-empty-txt { font-size: 16px; font-weight: 600; }

/* ════════════════════════════════
   DESKTOP ≥ 1024px
════════════════════════════════ */
@media (min-width: 1024px) {
  /* Grid: 2 columnas grandes con buen gap */
  .suc-grid { grid-template-columns: 1fr 1fr; gap: 32px; }

  /* Tarjeta más grande y con layout horizontal */
  .suc-card {
    display: flex;
    flex-direction: row;
    border-radius: 24px;
    overflow: hidden;
    min-height: 300px;
  }

  /* Mapa a la izquierda, fijo en 45% */
  .suc-map {
    width: 45%;
    height: auto;
    min-height: 280px;
    flex-shrink: 0;
  }
  .suc-map iframe {
    width: 100%;
    height: 100%;
    min-height: 280px;
  }
  .suc-map-placeholder {
    width: 45%;
    height: auto;
    min-height: 280px;
  }

  /* Info a la derecha */
  .suc-body {
    flex: 1;
    padding: 28px 28px 24px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .suc-name { font-size: 20px; }
  .suc-addr { font-size: 14px; margin-bottom: 18px; }

  .btn-dir {
    padding: 14px;
    font-size: 15px;
    border-radius: 14px;
  }
}
</style>
</head>
<body class="t-page">
<div id="page-wrap">

<?php include __DIR__ . '/nav-drawer.php'; ?>

<!-- ── HEADER ── -->
<header class="t-nav">
  <button class="nd-toggle" id="ndToggle" aria-label="Abrir menú" onclick="abrirDrawer()">
    <i class="fa-solid fa-bars"></i>
  </button>
  <a href="index.php" class="t-brand">
    <div class="t-brand-icon">
      <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" class="t-brand-logo" onerror="this.style.display='none'">
    </div>
    <span class="t-brand-name">Canetto</span>
  </a>
  <nav class="t-nav-links">
    <a href="tienda.php"       class="t-nav-link">Productos</a>
    <a href="tienda.php#boxesGrid" class="t-nav-link">Boxes</a>
    <a href="sucursales.php"  class="t-nav-link" style="color:var(--pk);font-weight:700">Sucursales</a>
  </nav>
  <div class="t-actions">
    <?php if ($cliente_id): ?>
    <a href="mis-pedidos.php" class="t-btn" title="Mis pedidos">
      <i class="fa-solid fa-bag-shopping" style="font-size:16px"></i>
    </a>
    <a href="mi-cuenta.php" class="t-btn" title="Mi cuenta">
      <i class="fa-solid fa-user" style="font-size:16px"></i>
    </a>
    <?php else: ?>
    <a href="login.php" class="t-btn" style="font-size:12px;font-weight:700;width:auto;padding:0 14px;border-radius:20px">
      Ingresar
    </a>
    <?php endif; ?>
  </div>
</header>

<!-- ── CONTENIDO ── -->
<div class="suc-page">

  <!-- Hero -->
  <div class="suc-hero">
    <div class="suc-hero-ic"><i class="fa-solid fa-location-dot"></i></div>
    <div class="suc-hero-title">Nuestras <em>sucursales</em></div>
    <div class="suc-hero-sub">Retirá tu pedido en la más cercana a vos</div>
    <?php if (!empty($sucursales)): ?>
    <button class="btn-nearest-big" onclick="findNearest()">
      <i class="fa-solid fa-location-crosshairs"></i> La más cercana
    </button>
    <?php endif; ?>
  </div>

  <!-- Banner más cercana -->
  <div id="nearestBanner"></div>

  <?php if (empty($sucursales)): ?>
  <div class="suc-empty">
    <div class="suc-empty-ic"><i class="fa-solid fa-location-dot"></i></div>
    <div class="suc-empty-txt">Próximamente más sucursales</div>
  </div>
  <?php else: ?>
  <div class="suc-grid" id="sucGrid">
    <?php foreach ($sucursales as $i => $s):
      $addr   = implode(', ', array_filter([$s['direccion'], $s['ciudad'], $s['provincia']]));
      $lat    = !empty($s['latitud'])  ? (float)$s['latitud']  : null;
      $lng    = !empty($s['longitud']) ? (float)$s['longitud'] : null;
      $mapsDir = ($lat && $lng)
        ? "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}"
        : ($addr ? "https://www.google.com/maps/search/" . urlencode($addr) : null);
    ?>
    <div class="suc-card" id="sc-<?= $i ?>"
         data-lat="<?= $lat ?? '' ?>" data-lng="<?= $lng ?? '' ?>"
         data-nombre="<?= htmlspecialchars($s['nombre']) ?>">

      <!-- Mapa -->
      <div class="suc-map">
        <?php if ($lat && $lng): ?>
        <iframe loading="lazy" allowfullscreen
          src="https://www.google.com/maps?q=<?= $lat ?>,<?= $lng ?>&z=15&output=embed"></iframe>
        <?php elseif ($addr): ?>
        <iframe loading="lazy" allowfullscreen
          src="https://www.google.com/maps?q=<?= urlencode($addr) ?>&z=15&output=embed"></iframe>
        <?php else: ?>
        <div class="suc-map-placeholder"><i class="fa-solid fa-map"></i></div>
        <?php endif; ?>
      </div>

      <!-- Cuerpo -->
      <div class="suc-body">
        <div class="suc-top">
          <div class="suc-ic"><i class="fa-solid fa-location-dot"></i></div>
          <div class="suc-name"><?= htmlspecialchars($s['nombre']) ?></div>
          <span class="suc-nearest-badge" id="badge-<?= $i ?>">
            <i class="fa-solid fa-star"></i> Más cercana
          </span>
        </div>

        <?php if ($addr): ?>
        <div class="suc-addr">
          <i class="fa-solid fa-map-pin"></i>
          <?= htmlspecialchars($addr) ?>
        </div>
        <?php endif; ?>

        <?php if ($s['telefono'] || $s['email']): ?>
        <div class="suc-chips">
          <?php if ($s['telefono']): ?>
          <a href="tel:<?= htmlspecialchars($s['telefono']) ?>" class="suc-chip">
            <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($s['telefono']) ?>
          </a>
          <?php endif; ?>
          <?php if ($s['email']): ?>
          <a href="mailto:<?= htmlspecialchars($s['email']) ?>" class="suc-chip">
            <i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($s['email']) ?>
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($mapsDir): ?>
        <a href="<?= $mapsDir ?>" target="_blank" rel="noopener" class="btn-dir">
          <i class="fa-solid fa-route"></i> Cómo llegar
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div><!-- /suc-page -->

<!-- ── FOOTER desktop ── -->
<footer class="t-footer">
  <span class="t-footer-brand">© <?= date('Y') ?> Canetto</span>
  <span class="t-footer-tag">Cookies artesanales</span>
</footer>

</div><!-- /page-wrap -->

<script>
window.SUCURSALES = <?= json_encode($sucursales, JSON_UNESCAPED_UNICODE) ?>;

function findNearest(){
  if(!navigator.geolocation){
    showBanner('Tu navegador no soporta geolocalización.');
    return;
  }
  const btn = document.querySelector('.btn-nearest-big');
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Buscando...';
  btn.disabled = true;

  navigator.geolocation.getCurrentPosition(pos => {
    btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> La más cercana';
    btn.disabled = false;

    const {latitude: lat, longitude: lng} = pos.coords;
    let best = null, bestDist = Infinity;

    document.querySelectorAll('.suc-card').forEach((card, i) => {
      card.classList.remove('nearest');
      const clat = parseFloat(card.dataset.lat);
      const clng = parseFloat(card.dataset.lng);
      if(!clat || !clng) return;
      const d = Math.hypot(lat - clat, lng - clng);
      if(d < bestDist){ bestDist = d; best = i; }
    });

    if(best !== null){
      const card = document.querySelectorAll('.suc-card')[best];
      card.classList.add('nearest');
      card.scrollIntoView({behavior:'smooth', block:'center'});
      const nombre = card.dataset.nombre;
      showBanner('<i class="fa-solid fa-star" style="color:var(--pk);margin-right:6px"></i>La sucursal más cercana es <strong>' + nombre + '</strong>');
    }
  }, () => {
    btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> La más cercana';
    btn.disabled = false;
    showBanner('No pudimos obtener tu ubicación. Revisá los permisos.');
  });
}

function showBanner(html){
  const b = document.getElementById('nearestBanner');
  b.innerHTML = html;
  b.style.display = 'block';
}
</script>
</body>
</html>
