<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Estado de la tienda (solo para mostrar un aviso, la landing siempre se ve) ──
try {
    $pdo = Conexion::conectar();
    $_rows = $pdo->query("SELECT clave, valor FROM configuracion_tienda")->fetchAll(PDO::FETCH_KEY_PAIR);
    $_cfg = array_merge([
        'tienda_mensaje_cierre'   => 'La tienda está temporalmente cerrada. ¡Volvemos pronto!',
        'tienda_modo'             => 'abierta',
        'horario_activado'        => '0',
        'horario_apertura'        => '09:00',
        'horario_cierre'          => '21:00',
        'horario_forzado_cerrado' => '0',
    ], $_rows);

    $_modoConfig = $_cfg['tienda_modo'];
    if ($_cfg['horario_activado'] === '1') {
        $_tz     = new DateTimeZone('America/Argentina/Buenos_Aires');
        $_ahora  = new DateTime('now', $_tz);
        $_minAct = (int)$_ahora->format('H') * 60 + (int)$_ahora->format('i');
        [$_ha, $_ma] = explode(':', $_cfg['horario_apertura']);
        [$_hc, $_mc] = explode(':', $_cfg['horario_cierre']);
        $_minAp = (int)$_ha * 60 + (int)$_ma;
        $_minCi = (int)$_hc * 60 + (int)$_mc;
        $_enH    = ($_minAct >= $_minAp && $_minAct < $_minCi);
        $_forzado = $_cfg['horario_forzado_cerrado'] === '1';
        $_modoEfectivo = ($_enH && !$_forzado) ? $_modoConfig : 'cerrada';
    } else {
        $_modoEfectivo = $_modoConfig;
    }

    $_tiendaAbierta       = $_modoEfectivo !== 'cerrada';
    $_tiendaAceptaPedidos = $_modoEfectivo === 'abierta';
    $_tiendaMensaje       = $_cfg['tienda_mensaje_cierre'];

    // Hero: oferta activa más reciente (texto). El fondo es diseño (degradé + ícono), no foto.
    $_hero = $pdo->query("
        SELECT o.titulo, o.descripcion, o.tipo, o.valor
        FROM oferta o
        WHERE o.activo = 1
          AND (o.fecha_inicio IS NULL OR o.fecha_inicio <= CURDATE())
          AND (o.fecha_fin   IS NULL OR o.fecha_fin   >= CURDATE())
        ORDER BY o.created_at DESC
        LIMIT 1
    ")->fetch();

    if (!$_hero) {
        $_hero = ['titulo' => '¡Bienvenidos a Canetto!', 'descripcion' => 'Las mejores cookies artesanales, hechas con amor', 'tipo' => 'promo', 'valor' => null];
    }

    // Promos con foto de producto, para la franja de descuentos estilo McDonald's
    $promos = $pdo->query("
        SELECT o.idoferta, o.titulo, o.tipo, o.valor, o.productos_idproductos,
               p.imagen AS prod_imagen, p.nombre AS prod_nombre
        FROM oferta o
        JOIN productos p ON p.idproductos = o.productos_idproductos
        WHERE o.activo = 1
          AND o.tipo IN ('descuento', 'promo', 'nuevo')
          AND p.imagen IS NOT NULL
          AND (o.fecha_inicio IS NULL OR o.fecha_inicio <= CURDATE())
          AND (o.fecha_fin   IS NULL OR o.fecha_fin   >= CURDATE())
        ORDER BY o.created_at DESC
        LIMIT 6
    ")->fetchAll();
} catch (Throwable $e) {
    $_tiendaAbierta       = true;
    $_tiendaAceptaPedidos = true;
    $_tiendaMensaje       = '';
    $_hero = ['titulo' => '¡Bienvenidos a Canetto!', 'descripcion' => 'Cookies artesanales hechas con amor', 'tipo' => 'promo', 'valor' => null];
    $promos = [];
}

$cliente_id   = $_SESSION['tienda_cliente_id']     ?? null;
$tagLabels    = ['promo' => 'Canetto', 'descuento' => 'Descuento', 'temporada' => 'Temporada', 'nuevo' => 'Nuevo'];
$promoBadge   = ['descuento' => '#dc2626', 'nuevo' => '#16a34a', 'promo' => '#c2185b'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Canetto — Cookies Artesanales</title>
<link rel="icon" type="image/png" href="https://canettocookies.com/img/Logo_Canetto_Cookie.png">
<meta name="description" content="Cookies artesanales hechas con amor. Pedí online y retirá en tu sucursal más cercana o recibí por delivery.">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= URL_TIENDA ?>/">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Canetto">
<meta property="og:title" content="Canetto — Cookies Artesanales">
<meta property="og:description" content="Cookies artesanales hechas con amor. Pedí online y retirá en tu sucursal más cercana o recibí por delivery.">
<meta property="og:image" content="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png">
<link rel="stylesheet" href="tienda.css?v=<?= filemtime(__DIR__ . '/tienda.css') ?>">
</head>
<body class="lp-page">
<div id="page-wrap">

<?php include __DIR__ . '/nav-drawer.php'; ?>

<!-- ── HEADER ──────────────────── -->
<header class="t-nav">
  <button class="nd-toggle" id="ndToggle" aria-label="Abrir menú" onclick="abrirDrawer()">
    <i class="fa-solid fa-bars"></i>
  </button>
  <a href="index.php" class="t-brand">
    <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" class="t-brand-logo" onerror="this.style.display='none'">
  </a>
  <div class="t-actions">
    <?php if ($cliente_id): ?>
    <a href="mi-cuenta.php" class="t-btn" title="Mi cuenta"><i class="fa-solid fa-user" style="font-size:14px"></i></a>
    <?php else: ?>
    <a href="login.php" class="t-btn" data-instant style="font-size:12px;font-weight:700;width:auto;padding:0 14px;border-radius:20px">
      Ingresar
    </a>
    <?php endif; ?>
  </div>
</header>

<?php if (!$_tiendaAbierta): ?>
<div class="lp-banner-cerrada">
  <i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($_tiendaMensaje) ?>
</div>
<?php elseif (!$_tiendaAceptaPedidos): ?>
<div class="lp-banner-cerrada" style="background:linear-gradient(90deg,#1e40af,#3b82f6)">
  <i class="fa-solid fa-eye"></i> Por el momento podés ver los productos, pero no se pueden hacer pedidos.
</div>
<?php endif; ?>

<!-- ── HERO (imagen fija) ───── -->
<section class="lp-hero">
  <div class="lp-hero-bg lp-hero-bg--brand"></div>
  <i class="fa-solid fa-cookie-bite lp-hero-deco"></i>
  <div class="lp-hero-overlay"></div>
  <div class="lp-hero-wave"></div>
  <div class="lp-hero-content">
    <span class="slide-tag"><?= htmlspecialchars($tagLabels[$_hero['tipo']] ?? 'Canetto') ?></span>
    <h1 class="lp-hero-title"><?= htmlspecialchars($_hero['titulo']) ?></h1>
    <?php if (!empty($_hero['descripcion'])): ?>
      <p class="lp-hero-desc"><?= htmlspecialchars($_hero['descripcion']) ?></p>
    <?php endif; ?>
    <div class="lp-hero-cta">
      <?php if ($_tiendaAceptaPedidos): ?>
      <a href="tienda.php" class="lp-cta-btn lp-cta-btn--wh"><i class="fa-solid fa-store"></i> Pedí y retirá</a>
      <a href="tienda.php" class="lp-cta-btn lp-cta-btn--pk"><i class="fa-solid fa-motorcycle"></i> Delivery</a>
      <?php else: ?>
      <a href="tienda.php" class="lp-cta-btn lp-cta-btn--wh"><i class="fa-solid fa-eye"></i> Ver productos</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ── PROMOS CON FOTO (estilo McDonald's) ───── -->
<?php if (!empty($promos)): ?>
<section class="lp-promos lp-reveal">
  <div class="lp-promos-scroll">
    <?php foreach ($promos as $pr): ?>
    <a href="producto.php?id=<?= (int)$pr['productos_idproductos'] ?>" class="lp-promo-card">
      <div class="lp-promo-bg" style="background-image:url('<?= URL_ASSETS ?>/img/productos/<?= rawurlencode($pr['prod_imagen']) ?>')"></div>
      <div class="lp-promo-overlay"></div>
      <?php if ((float)($pr['valor'] ?? 0) > 0): ?>
      <span class="lp-promo-badge" style="background:<?= $promoBadge[$pr['tipo']] ?? '#c2185b' ?>">
        <?= $pr['tipo'] === 'descuento' ? '-'.(int)$pr['valor'].'%' : htmlspecialchars($tagLabels[$pr['tipo']] ?? '') ?>
      </span>
      <?php elseif ($pr['tipo'] === 'nuevo'): ?>
      <span class="lp-promo-badge" style="background:<?= $promoBadge['nuevo'] ?>">Nuevo</span>
      <?php endif; ?>
      <span class="lp-promo-title"><?= htmlspecialchars($pr['titulo']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ── CATEGORÍAS + BENEFICIOS (banda con fondo) ───── -->
<section class="lp-band">

  <div class="lp-cats lp-reveal">
    <a href="tienda.php#prodsGrid" class="lp-cat-item">
      <span class="lp-cat-ic"><i class="fa-solid fa-cookie-bite"></i></span>
      <span class="lp-cat-lbl">Cookies</span>
    </a>
    <a href="tienda.php#boxesGrid" class="lp-cat-item">
      <span class="lp-cat-ic"><i class="fa-solid fa-box-open"></i></span>
      <span class="lp-cat-lbl">Boxes</span>
    </a>
  </div>

  <div class="lp-section-hd lp-reveal">
    <div class="lp-eyebrow">¿Por qué Canetto?</div>
    <h2 class="lp-section-title">Pensado para que solo te preocupes por disfrutar</h2>
  </div>

  <!-- ── BENEFICIOS ───── -->
  <div class="lp-benefits lp-reveal">
    <div class="lp-benefit">
      <div class="lp-benefit-ic"><i class="fa-solid fa-store"></i></div>
      <div class="lp-benefit-title">Retiro fácil</div>
      <p>Retirá tu pedido en la sucursal que prefieras, cuando más te convenga</p>
    </div>
    <div class="lp-benefit">
      <div class="lp-benefit-ic"><i class="fa-solid fa-tag"></i></div>
      <div class="lp-benefit-title">Beneficios exclusivos</div>
      <p>Disfrutá de descuentos y promociones exclusivas por registrarte</p>
    </div>
    <div class="lp-benefit">
      <div class="lp-benefit-ic"><i class="fa-solid fa-heart"></i></div>
      <div class="lp-benefit-title">Hecho con amor</div>
      <p>Cookies artesanales con ingredientes seleccionados y mucho cariño</p>
    </div>
  </div>
</section>

<!-- ── CONTACTO / REDES ───── -->
<section class="lp-contact lp-reveal" id="sobre-nosotros">
  <div class="lp-contact-card">
    <div class="lp-contact-card-txt">
      <div class="lp-contact-title">¿Tenés una consulta o un pedido especial?</div>
      <div class="lp-contact-sub">Escribinos directo, te respondemos al toque</div>
      <div class="lp-contact-btns">
        <a href="https://wa.me/5493765123808" target="_blank" class="lp-contact-btn" style="background:#dcfce7;color:#16a34a">
          <i class="fa-brands fa-whatsapp"></i> WhatsApp
        </a>
        <a href="https://www.instagram.com/canetto__/" target="_blank" class="lp-contact-btn" style="background:#fdf0f3;color:#c88e99">
          <i class="fa-brands fa-instagram"></i> @canetto__
        </a>
      </div>
    </div>
    <div class="lp-contact-card-ic"><i class="fa-solid fa-cookie-bite"></i></div>
  </div>
</section>

<!-- ── FAQ ───── -->
<section class="lp-faq lp-reveal">
  <div class="lp-eyebrow" style="text-align:center">Preguntas frecuentes</div>
  <div class="lp-faq-title">¿Necesitás alguna razón más?</div>

  <div class="lp-faq-list">
    <div class="lp-faq-item">
      <button class="lp-faq-q" onclick="toggleFaq(this)">
        ¿Qué medios de pago aceptan? <i class="fa-solid fa-plus lp-faq-ic"></i>
      </button>
      <div class="lp-faq-a"><p>Aceptamos efectivo, transferencia bancaria y Mercado Pago. Por el momento no aceptamos pago con tarjeta.</p></div>
    </div>
    <div class="lp-faq-item">
      <button class="lp-faq-q" onclick="toggleFaq(this)">
        ¿Cómo puedo cancelar mi pedido? <i class="fa-solid fa-plus lp-faq-ic"></i>
      </button>
      <div class="lp-faq-a"><p>Podés solicitar la cancelación desde "Mis pedidos" mientras el pedido todavía no fue confirmado por la sucursal.</p></div>
    </div>
    <div class="lp-faq-item">
      <button class="lp-faq-q" onclick="toggleFaq(this)">
        ¿Cómo sigo el estado de mi pedido? <i class="fa-solid fa-plus lp-faq-ic"></i>
      </button>
      <div class="lp-faq-a"><p>Una vez que iniciás sesión, en "Mis pedidos" podés ver en tiempo real si está pendiente, confirmado o listo para retirar.</p></div>
    </div>
    <div class="lp-faq-item">
      <button class="lp-faq-q" onclick="toggleFaq(this)">
        ¿Tienen envío a domicilio? <i class="fa-solid fa-plus lp-faq-ic"></i>
      </button>
      <div class="lp-faq-a"><p>Sí, hacemos envío a domicilio a cualquier dirección. Elegí "Envío" al momento de pagar e ingresá tu ubicación.</p></div>
    </div>
  </div>
</section>

<!-- ── BANNER FINAL ───── -->
<?php if (!$cliente_id): ?>
<section class="lp-final-banner lp-reveal">
  <div>
    <div class="lp-final-title">Registrate y disfrutá de la experiencia completa</div>
    <div class="lp-final-sub">Ofertas, descuentos y muchas sorpresas</div>
  </div>
  <a href="login.php" class="lp-final-btn" data-instant>Ingresá ya</a>
</section>
<?php endif; ?>

<!-- ── FOOTER ──────────────────── -->
<footer class="t-footer lp-footer">
  <div class="lp-footer-cols">
    <div class="lp-footer-col">
      <div class="lp-footer-logo-wrap">
        <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" class="lp-footer-logo" onerror="this.style.display='none'">
      </div>
      <div class="lp-footer-brand">Canetto</div>
      <div class="lp-footer-tag">Cookies hechas con amor</div>
    </div>
    <div class="lp-footer-col">
      <div class="lp-footer-hd">Sobre Nosotros</div>
      <a href="#sobre-nosotros">Quiénes somos</a>
      <a href="sucursales.php">Sucursales</a>
    </div>
    <div class="lp-footer-col">
      <div class="lp-footer-hd">Descubre</div>
      <a href="tienda.php#prodsGrid">Cookies</a>
      <a href="tienda.php#boxesGrid">Boxes</a>
    </div>
    <div class="lp-footer-col">
      <div class="lp-footer-hd">Contacto</div>
      <a href="https://wa.me/5493765123808" target="_blank"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
      <a href="https://www.instagram.com/canetto__/" target="_blank"><i class="fa-brands fa-instagram"></i> Instagram</a>
    </div>
  </div>
  <div class="lp-footer-bottom">© <?= date('Y') ?> Canetto. Todos los derechos reservados.</div>
</footer>
</div><!-- /page-wrap -->

<script>
// Header transparente -> sólido al hacer scroll
const _lpNav = document.querySelector('.t-nav');
function _lpUpdateNav(){
  if (window.scrollY > 60) _lpNav.classList.add('scrolled');
  else _lpNav.classList.remove('scrolled');
}
window.addEventListener('scroll', _lpUpdateNav, { passive: true });
_lpUpdateNav();

// Animación de aparición al hacer scroll
const _lpIo = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('in');
      _lpIo.unobserve(entry.target);
    }
  });
}, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
document.querySelectorAll('.lp-reveal').forEach(el => _lpIo.observe(el));

// FAQ: acordeón con despliegue animado hacia abajo
function toggleFaq(btn){
  const item   = btn.closest('.lp-faq-item');
  const answer = item.querySelector('.lp-faq-a');
  const isOpen = item.classList.contains('open');

  document.querySelectorAll('.lp-faq-item.open').forEach(other => {
    if (other !== item) {
      other.classList.remove('open');
      other.querySelector('.lp-faq-a').style.maxHeight = null;
    }
  });

  if (isOpen) {
    item.classList.remove('open');
    answer.style.maxHeight = null;
  } else {
    item.classList.add('open');
    answer.style.maxHeight = answer.scrollHeight + 'px';
  }
}
</script>
</body>
</html>
