<?php
define('APP_BOOT', true);
$pageTitle = 'Soporte & Contacto — Canetto';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/mailer.php';

// Procesar formulario de contacto interno
$msg  = '';
$tipo = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'enviar_soporte') {
    $asunto  = trim($_POST['asunto']  ?? '');
    $detalle = trim($_POST['detalle'] ?? '');
    $tipoMsg = trim($_POST['tipo_msg'] ?? 'consulta');

    if ($asunto && $detalle) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $remitente = htmlspecialchars(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
        $contenido = <<<HTML
<h2 style="margin:0 0 8px;font-size:20px;color:#2d2d2d;font-weight:700;">
  Nuevo mensaje de soporte
</h2>
<div style="background:#faf3f5;border-radius:10px;padding:16px 18px;margin-bottom:16px;">
  <p style="margin:0 0 6px;font-size:13px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Remitente</p>
  <p style="margin:0;color:#444;font-size:14px;"><strong>{$remitente}</strong></p>
</div>
<div style="background:#faf3f5;border-radius:10px;padding:16px 18px;margin-bottom:16px;">
  <p style="margin:0 0 6px;font-size:13px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Tipo</p>
  <p style="margin:0;color:#444;font-size:14px;">{$tipoMsg}</p>
</div>
<div style="background:#faf3f5;border-radius:10px;padding:16px 18px;margin-bottom:16px;">
  <p style="margin:0 0 6px;font-size:13px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Asunto</p>
  <p style="margin:0;color:#444;font-size:14px;"><strong>{$asunto}</strong></p>
</div>
<div style="background:#faf3f5;border-radius:10px;padding:16px 18px;">
  <p style="margin:0 0 6px;font-size:13px;color:#999;text-transform:uppercase;letter-spacing:.5px;">Detalle</p>
  <p style="margin:0;color:#444;font-size:14px;line-height:1.7;">{$detalle}</p>
</div>
HTML;
        $ok = enviarEmail(MAIL_SUPPORT, 'Canetto Soporte', "[$tipoMsg] $asunto", 'Soporte interno', $contenido);
        $msg  = $ok ? '✅ Mensaje enviado correctamente.' : '⚠️ No se pudo enviar el email. Revisá la configuración SMTP.';
        $tipo = $ok ? 'success' : 'error';
    } else {
        $msg  = 'Completá el asunto y el detalle.';
        $tipo = 'error';
    }
}

include '../panel/dashboard/layaut/nav.php';
?>

<style>
.soporte-page { max-width: 860px; margin: 0 auto; padding: 30px 24px; }
.soporte-page h1 { font-size: 22px; font-weight: 700; color: #2d2d2d; margin: 0 0 6px; }
.soporte-page .sub { color: #888; font-size: 14px; margin-bottom: 28px; }

.soporte-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 32px; }
@media(max-width:600px){ .soporte-grid { grid-template-columns:1fr; } }

.soporte-card {
  background: #fff;
  border: 1px solid #f0e8e8;
  border-radius: 14px;
  padding: 22px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  box-shadow: 0 2px 12px rgba(200,142,153,.07);
}
.soporte-card .sc-icon {
  width: 44px; height: 44px; border-radius: 12px;
  background: linear-gradient(135deg,#f9d6df,#fce8ed);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; flex-shrink: 0;
}
.soporte-card .sc-title { font-weight: 700; font-size: 14px; color: #2d2d2d; margin-bottom: 4px; }
.soporte-card .sc-desc { font-size: 13px; color: #888; line-height: 1.5; }
.soporte-card a { color: #c88e99; font-weight: 600; text-decoration: none; }
.soporte-card a:hover { color: #a46678; }

.soporte-form-card {
  background: #fff;
  border: 1px solid #f0e8e8;
  border-radius: 16px;
  padding: 28px;
  box-shadow: 0 2px 14px rgba(200,142,153,.07);
}
.soporte-form-card h2 { font-size: 17px; font-weight: 700; color: #2d2d2d; margin: 0 0 20px; }

.sf-row { margin-bottom: 18px; }
.sf-row label { display: block; font-size: 12px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 7px; }
.sf-row input, .sf-row select, .sf-row textarea {
  width: 100%; padding: 11px 14px; border: 1px solid #e8dfe1; border-radius: 10px;
  font-size: 14px; color: #333; font-family: inherit; background: #fff;
  outline: none; box-sizing: border-box; transition: border-color .2s;
}
.sf-row input:focus, .sf-row select:focus, .sf-row textarea:focus { border-color: #c88e99; }
.sf-row textarea { resize: vertical; min-height: 110px; }

.btn-enviar {
  width: 100%; padding: 13px; background: linear-gradient(135deg,#c88e99,#a46678);
  color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 700;
  cursor: pointer; transition: .2s; font-family: inherit;
}
.btn-enviar:hover { opacity: .9; transform: translateY(-1px); }

.sf-alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 18px; }
.sf-alert.success { background: #e8f5e9; color: #1d8348; }
.sf-alert.error   { background: #f9edf0; color: #c88e99; }
</style>

<div class="soporte-page">

  <h1><i class="fa-solid fa-headset" style="color:#c88e99;margin-right:8px;"></i>Soporte &amp; Contacto</h1>
  <p class="sub">Canales de ayuda para el equipo y contacto con los clientes.</p>

  <!-- Tarjetas de contacto rápido -->
  <div class="soporte-grid">

    <div class="soporte-card">
      <div class="sc-icon">📧</div>
      <div>
        <div class="sc-title">Email administrativo</div>
        <div class="sc-desc">
          Para consultas internas del sistema.<br>
          <a href="mailto:soporte@canettocookies.com">soporte@canettocookies.com</a>
        </div>
      </div>
    </div>

    <div class="soporte-card">
      <div class="sc-icon">💬</div>
      <div>
        <div class="sc-title">WhatsApp de soporte</div>
        <div class="sc-desc">
          Contacto directo con el equipo técnico para problemas urgentes del sistema.
          <br><a href="https://wa.me/5491100000000" target="_blank">Abrir WhatsApp</a>
        </div>
      </div>
    </div>

    <div class="soporte-card">
      <div class="sc-icon">🛒</div>
      <div>
        <div class="sc-title">Problemas con ventas</div>
        <div class="sc-desc">
          Si hubo una venta incorrecta o reclamo de cliente, usá el formulario de abajo
          con el tipo <em>"Problema en venta"</em>.
        </div>
      </div>
    </div>

    <div class="soporte-card">
      <div class="sc-icon">⚙️</div>
      <div>
        <div class="sc-title">Errores del sistema</div>
        <div class="sc-desc">
          Para reportar bugs o comportamientos inesperados en la plataforma,
          usá el formulario con el tipo <em>"Error del sistema"</em>.
        </div>
      </div>
    </div>

  </div>

  <!-- Formulario de contacto interno -->
  <div class="soporte-form-card">
    <h2><i class="fa-solid fa-paper-plane" style="color:#c88e99;margin-right:8px;"></i>Enviar consulta o reporte</h2>

    <?php if ($msg): ?>
      <div class="sf-alert <?= $tipo ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="accion" value="enviar_soporte">

      <div class="sf-row">
        <label>Tipo de consulta</label>
        <select name="tipo_msg">
          <option value="consulta">Consulta general</option>
          <option value="Problema en venta">Problema en venta</option>
          <option value="Error del sistema">Error del sistema</option>
          <option value="Solicitud de cambio">Solicitud de cambio / mejora</option>
          <option value="Otro">Otro</option>
        </select>
      </div>

      <div class="sf-row">
        <label>Asunto *</label>
        <input type="text" name="asunto" placeholder="Descripción breve del problema" required>
      </div>

      <div class="sf-row">
        <label>Detalle *</label>
        <textarea name="detalle" placeholder="Describí el problema con el mayor detalle posible. Si es un error del sistema, indicá en qué página ocurrió y qué pasos hiciste." required></textarea>
      </div>

      <button type="submit" class="btn-enviar">
        <i class="fa-solid fa-paper-plane"></i> Enviar reporte
      </button>
    </form>
  </div>

</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
