<?php
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($host, ['localhost', '127.0.0.1'], true);

if (!$isLocal) {
    header('Location: https://tienda.canettocookies.com');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Canetto — Local</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh;
         display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; }
  h1  { font-size: 28px; font-weight: 800; margin-bottom: 6px; }
  p   { font-size: 13px; color: #64748b; margin-bottom: 32px; }
  .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; width: 100%; max-width: 640px; }
  a.card { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 28px 20px;
           background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
           border-radius: 16px; text-decoration: none; color: inherit; transition: .15s; }
  a.card:hover { background: rgba(255,255,255,.1); border-color: #c88e99; transform: translateY(-2px); }
  a.card span.icon { font-size: 32px; }
  a.card span.label { font-size: 13px; font-weight: 700; }
  a.card span.url   { font-size: 11px; color: #64748b; }
  .badge { font-size: 10px; background: #c88e99; color: #fff; border-radius: 6px; padding: 2px 7px; }
</style>
</head>
<body>
<h1>Canetto</h1>
<p>Entorno local — elegí una sección</p>
<div class="grid">
  <a class="card" href="/canetto/tienda/">
    <span class="icon">🛒</span>
    <span class="label">Tienda</span>
    <span class="url">/canetto/tienda/</span>
  </a>
  <a class="card" href="/canetto/administracion/">
    <span class="icon">⚙️</span>
    <span class="label">Administración</span>
    <span class="url">/canetto/administracion/</span>
  </a>
  <a class="card" href="/canetto/repartidor/">
    <span class="icon">🏍️</span>
    <span class="label">Repartidor</span>
    <span class="url">/canetto/repartidor/</span>
  </a>
  <a class="card" href="/canetto/login/">
    <span class="icon">🔐</span>
    <span class="label">Login</span>
    <span class="url">/canetto/login/</span>
  </a>
</div>
</body>
</html>
