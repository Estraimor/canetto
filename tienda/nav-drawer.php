<?php
/**
 * Menú lateral (drawer) compartido entre todas las páginas de tienda/.
 * Lee el cliente logueado directamente de sesión para no depender del
 * nombre de variable que use cada página que lo incluye.
 */
$cliente_id = $cliente_id ?? ($_SESSION['tienda_cliente_id'] ?? null);
?>
<div class="nd-overlay" id="ndOverlay" onclick="cerrarDrawer()"></div>
<aside class="nav-drawer" id="navDrawer">
  <div class="nd-head">
    <div class="t-brand-icon" style="width:56px;height:56px">
      <img src="<?= URL_ASSETS ?>/img/Logo_Canetto_Cookie.png" alt="Canetto" style="width:100%;height:100%;object-fit:contain" onerror="this.style.display='none'">
    </div>
    <span class="t-brand-name" style="font-size:17px">Canetto</span>
    <button class="nd-close" onclick="cerrarDrawer()" aria-label="Cerrar menú">✕</button>
  </div>
  <nav class="nd-links">
    <a href="index.php" class="nd-link"><i class="fa-solid fa-house"></i> Inicio</a>
    <a href="tienda.php#prodsGrid" class="nd-link"><i class="fa-solid fa-cookie-bite"></i> Productos</a>
    <a href="tienda.php#boxesGrid" class="nd-link"><i class="fa-solid fa-box-open"></i> Boxes</a>
    <a href="sucursales.php" class="nd-link"><i class="fa-solid fa-location-dot"></i> Sucursales</a>
    <?php if ($cliente_id): ?>
    <a href="mis-pedidos.php" class="nd-link"><i class="fa-solid fa-bag-shopping"></i> Mis pedidos</a>
    <a href="mi-cuenta.php" class="nd-link"><i class="fa-solid fa-user"></i> Mi cuenta</a>
    <?php else: ?>
    <a href="login.php" class="nd-link" data-instant><i class="fa-solid fa-user"></i> Ingresar</a>
    <?php endif; ?>
    <a href="#sobre-nosotros" class="nd-link"><i class="fa-solid fa-circle-info"></i> Sobre nosotros</a>
  </nav>
  <div class="nd-social">
    <a href="https://wa.me/5493765123808" target="_blank" class="nd-social-btn" style="background:#dcfce7;color:#16a34a" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
    <a href="https://www.instagram.com/canetto__/" target="_blank" class="nd-social-btn" style="background:#fdf0f3;color:#c88e99" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
  </div>
</aside>
<script>
function abrirDrawer(){
  document.getElementById('navDrawer')?.classList.add('on');
  document.getElementById('ndOverlay')?.classList.add('on');
  document.body.style.overflow = 'hidden';
}
function cerrarDrawer(){
  document.getElementById('navDrawer')?.classList.remove('on');
  document.getElementById('ndOverlay')?.classList.remove('on');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarDrawer(); });
</script>
