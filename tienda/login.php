<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['tienda_cliente_id'])) {
    header('Location: mis-pedidos.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Cuenta — Canetto</title>
<link rel="stylesheet" href="tienda.css">
</head>
<body>
<div id="page-wrap">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-ic">🍪</div>
      <div class="auth-title">Canetto</div>
      <div class="auth-sub">Galletitas artesanales con amor</div>
    </div>

    <div class="auth-tabs">
      <button class="auth-tab-btn on" id="tabL" onclick="sw('login')">Ingresar</button>
      <button class="auth-tab-btn"    id="tabR" onclick="sw('reg')">Registrarse</button>
    </div>

    <!-- Login -->
    <div class="auth-form-panel on" id="pLogin">
      <div class="auth-alert" id="lAlert"></div>
      <div class="fg"><label>Celular</label><input id="lCel" type="tel" placeholder="Ej: 1123456789" onkeydown="if(event.key==='Enter')doLogin()"></div>
      <div class="fg"><label>Contraseña</label><input id="lPass" type="password" placeholder="Tu contraseña" onkeydown="if(event.key==='Enter')doLogin()"></div>
      <button class="btn-pk" onclick="doLogin()">Ingresar →</button>
      <div class="ck-divider" style="margin:16px 0">o</div>
      <a href="index.php" class="btn-sec">Continuar sin cuenta</a>
    </div>

    <!-- Register -->
    <div class="auth-form-panel" id="pReg">
      <div class="auth-alert" id="rAlert"></div>
      <div class="fg-row">
        <div class="fg"><label>Nombre *</label><input id="rNom" type="text" placeholder="Tu nombre"></div>
        <div class="fg"><label>Apellido</label><input id="rApe" type="text" placeholder="Apellido"></div>
      </div>
      <div class="fg"><label>Celular *</label><input id="rCel" type="tel" placeholder="1123456789"></div>
      <div class="fg"><label>DNI (opcional)</label><input id="rDni" type="text" placeholder="12345678"></div>
      <div class="fg"><label>Contraseña *</label><input id="rPass" type="password" placeholder="Mínimo 6 caracteres"></div>
      <button class="btn-pk" onclick="doRegister()">Crear cuenta →</button>
    </div>

    <p style="text-align:center;font-size:12px;color:#aaa;margin-top:20px">
      <a href="index.php" style="color:#888;text-decoration:none">← Volver a la tienda</a>
    </p>
  </div>
</div>
</div><!-- /page-wrap -->

<script>
function sw(tab){
  document.querySelectorAll('.auth-tab-btn').forEach(b=>b.classList.remove('on'));
  document.querySelectorAll('.auth-form-panel').forEach(p=>p.classList.remove('on'));
  if(tab==='login'){document.getElementById('tabL').classList.add('on');document.getElementById('pLogin').classList.add('on')}
  else{document.getElementById('tabR').classList.add('on');document.getElementById('pReg').classList.add('on')}
}
function setAlert(id,msg,type){const el=document.getElementById(id);el.textContent=msg;el.className='auth-alert on '+(type==='err'?'e':'s')}

async function doLogin(){
  const cel=document.getElementById('lCel').value.trim(),pass=document.getElementById('lPass').value;
  if(!cel||!pass){setAlert('lAlert','Completá todos los campos','err');return}
  const btn=document.querySelector('#pLogin .btn-pk');btn.disabled=true;btn.textContent='Ingresando...';
  try{const fd=new FormData();fd.append('action','login');fd.append('celular',cel);fd.append('password',pass);
    const d=await(await fetch('api/auth.php',{method:'POST',body:fd})).json();
    if(d.success){setAlert('lAlert','¡Bienvenido! Redirigiendo...','ok');setTimeout(()=>location.href='mis-pedidos.php',900)}
    else setAlert('lAlert',d.message||'Datos incorrectos','err');
  }catch{setAlert('lAlert','Error de conexión','err')}
  btn.disabled=false;btn.textContent='Ingresar →';
}

async function doRegister(){
  const nom=document.getElementById('rNom').value.trim(),cel=document.getElementById('rCel').value.trim(),pass=document.getElementById('rPass').value;
  if(!nom||!cel||!pass){setAlert('rAlert','Completá los campos obligatorios','err');return}
  if(pass.length<6){setAlert('rAlert','Contraseña de al menos 6 caracteres','err');return}
  const btn=document.querySelector('#pReg .btn-pk');btn.disabled=true;btn.textContent='Registrando...';
  try{const fd=new FormData();fd.append('action','register');fd.append('nombre',nom);fd.append('apellido',document.getElementById('rApe').value.trim());fd.append('celular',cel);fd.append('dni',document.getElementById('rDni').value.trim());fd.append('password',pass);
    const d=await(await fetch('api/auth.php',{method:'POST',body:fd})).json();
    if(d.success){setAlert('rAlert','¡Cuenta creada! Redirigiendo...','ok');setTimeout(()=>location.href='mis-pedidos.php',900)}
    else setAlert('rAlert',d.message||'Error al registrar','err');
  }catch{setAlert('rAlert','Error de conexión','err')}
  btn.disabled=false;btn.textContent='Crear cuenta →';
}
</script>
<script src="transitions.js"></script>
</body>
</html>
