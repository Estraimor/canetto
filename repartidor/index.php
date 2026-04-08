<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$repId     = $_SESSION['repartidor_id']     ?? null;
$repNombre = $_SESSION['repartidor_nombre'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>Canetto — Repartidor</title>
<link rel="stylesheet" href="repartidor.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- APP LOGIN -->
<div id="appLogin" class="app-screen <?= $repId ? 'hidden' : '' ?>">
  <div class="login-wrap">
    <div class="login-logo">🛵</div>
    <div class="login-title">Canetto Repartidor</div>
    <div class="login-sub">Ingresá con tus datos</div>
    <div id="loginAlert" class="alert" style="display:none"></div>
    <div class="form-field">
      <label>Celular</label>
      <input type="tel" id="lCelular" placeholder="Ej: 1123456789" autocomplete="tel">
    </div>
    <div class="form-field">
      <label>Contraseña</label>
      <input type="password" id="lPassword" placeholder="••••••">
    </div>
    <button class="btn-primary" id="btnLogin" onclick="doLogin()">Ingresar</button>
  </div>
</div>

<!-- APP DASHBOARD -->
<div id="appDash" class="app-screen <?= $repId ? '' : 'hidden' ?>">

  <div class="dash-header">
    <div>
      <div class="dash-title">Mis entregas</div>
      <div class="dash-sub" id="dashNombre"><?= htmlspecialchars($repNombre) ?></div>
    </div>
    <button class="btn-logout" onclick="doLogout()">
      <i class="fa-solid fa-right-from-bracket"></i>
    </button>
  </div>

  <div id="pedidosList" class="pedidos-list">
    <div class="pedido-empty"><i class="fa-solid fa-box-open"></i><br>Cargando pedidos...</div>
  </div>

  <button class="btn-refresh" onclick="cargarPedidos()">
    <i class="fa-solid fa-arrows-rotate"></i> Actualizar
  </button>

</div>

<!-- TEMPLATE PEDIDO (hidden) -->
<template id="tplPedido">
  <div class="pedido-card">
    <div class="pedido-head">
      <span class="pedido-num"></span>
      <span class="pedido-total"></span>
    </div>
    <div class="pedido-cliente">
      <i class="fa-solid fa-user"></i>
      <span class="pedido-nombre"></span>
    </div>
    <div class="pedido-direccion">
      <i class="fa-solid fa-location-dot"></i>
      <span class="pedido-dir-txt"></span>
    </div>
    <div class="pedido-prods">
      <i class="fa-solid fa-cookie"></i>
      <span class="pedido-prods-txt"></span>
    </div>
    <div class="pedido-actions">
      <a class="btn-tel" href="#"><i class="fa-solid fa-phone"></i> Llamar</a>
      <a class="btn-map" href="#" target="_blank"><i class="fa-solid fa-map"></i> Mapa</a>
      <button class="btn-entregar"><i class="fa-solid fa-check"></i> Entregado</button>
    </div>
  </div>
</template>

<script>
/* ═══════════════════════════════════════
   REPARTIDOR APP
═══════════════════════════════════════ */
const fmt = n => '$' + parseFloat(n).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

async function doLogin() {
  const celular  = document.getElementById('lCelular').value.trim();
  const password = document.getElementById('lPassword').value;
  const btn      = document.getElementById('btnLogin');
  const alert    = document.getElementById('loginAlert');

  if (!celular || !password) { showAlert('Completá todos los campos'); return; }

  btn.disabled    = true;
  btn.textContent = 'Ingresando...';
  alert.style.display = 'none';

  try {
    const res  = await fetch('api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ celular, password }),
    });
    const data = await res.json();
    if (data.success) {
      document.getElementById('dashNombre').textContent = data.nombre;
      document.getElementById('appLogin').classList.add('hidden');
      document.getElementById('appDash').classList.remove('hidden');
      cargarPedidos();
    } else {
      showAlert(data.message || 'Error al ingresar');
    }
  } catch (e) {
    showAlert('Error de conexión');
  }
  btn.disabled    = false;
  btn.textContent = 'Ingresar';
}

function showAlert(msg) {
  const el = document.getElementById('loginAlert');
  el.textContent    = msg;
  el.style.display  = 'block';
}

async function doLogout() {
  await fetch('api/logout.php', { method: 'POST' });
  document.getElementById('appDash').classList.add('hidden');
  document.getElementById('appLogin').classList.remove('hidden');
  document.getElementById('lPassword').value = '';
}

async function cargarPedidos() {
  const list = document.getElementById('pedidosList');
  list.innerHTML = '<div class="pedido-empty"><i class="fa-solid fa-spinner fa-spin"></i><br>Cargando...</div>';

  try {
    const res  = await fetch('api/get_pedidos.php');
    const data = await res.json();

    if (!data.success) {
      if (data.message === 'No autenticado') {
        document.getElementById('appDash').classList.add('hidden');
        document.getElementById('appLogin').classList.remove('hidden');
        return;
      }
      list.innerHTML = '<div class="pedido-empty">Error al cargar pedidos</div>';
      return;
    }

    if (!data.pedidos || data.pedidos.length === 0) {
      list.innerHTML = `
        <div class="pedido-empty">
          <i class="fa-solid fa-check-circle" style="color:#22c55e;font-size:2.5rem"></i>
          <br>No tenés pedidos pendientes
        </div>`;
      return;
    }

    const tpl = document.getElementById('tplPedido');
    list.innerHTML = '';

    data.pedidos.forEach(p => {
      const clone = tpl.content.cloneNode(true);
      clone.querySelector('.pedido-num').textContent    = '#' + p.idventas;
      clone.querySelector('.pedido-total').textContent  = fmt(p.total);
      clone.querySelector('.pedido-nombre').textContent = p.cliente_nombre || 'Cliente';
      clone.querySelector('.pedido-dir-txt').textContent = p.direccion_entrega || 'Sin dirección registrada';
      clone.querySelector('.pedido-prods-txt').textContent = p.productos || '—';

      // Teléfono
      const btnTel = clone.querySelector('.btn-tel');
      if (p.cliente_celular) {
        btnTel.href = 'tel:' + p.cliente_celular.replace(/\D/g,'');
      } else {
        btnTel.classList.add('disabled');
        btnTel.href = '#';
        btnTel.onclick = e => e.preventDefault();
      }

      // Mapa
      const btnMap = clone.querySelector('.btn-map');
      if (p.lat_entrega && p.lng_entrega) {
        btnMap.href = `https://www.google.com/maps/dir/?api=1&destination=${p.lat_entrega},${p.lng_entrega}`;
      } else if (p.direccion_entrega) {
        btnMap.href = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.direccion_entrega)}`;
      } else {
        btnMap.classList.add('disabled');
        btnMap.href = '#';
        btnMap.onclick = e => e.preventDefault();
      }

      // Marcar entregado
      const btnEntregar = clone.querySelector('.btn-entregar');
      btnEntregar.addEventListener('click', () => marcarEntregado(p.idventas, btnEntregar));

      list.appendChild(clone);
    });

  } catch (e) {
    list.innerHTML = '<div class="pedido-empty">Error de conexión</div>';
  }
}

async function marcarEntregado(idVenta, btn) {
  if (!confirm('¿Marcar el pedido #' + idVenta + ' como entregado?')) return;
  btn.disabled    = true;
  btn.innerHTML   = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

  try {
    const res  = await fetch('api/marcar_entregado.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ id_venta: idVenta }),
    });
    const data = await res.json();
    if (data.success) {
      cargarPedidos();
    } else {
      alert(data.message || 'No se pudo marcar como entregado');
      btn.disabled  = false;
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Entregado';
    }
  } catch (e) {
    alert('Error de conexión');
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Entregado';
  }
}

// Tecla Enter en login
document.getElementById('lPassword')?.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

// Si ya está logueado, cargar pedidos
if (document.getElementById('appDash') && !document.getElementById('appDash').classList.contains('hidden')) {
  cargarPedidos();
  // Auto-refresh cada 60 segundos
  setInterval(cargarPedidos, 60000);
}
</script>
</body>
</html>
