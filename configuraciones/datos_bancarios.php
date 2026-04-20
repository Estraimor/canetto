<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Datos Bancarios";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();
$pdo->exec("CREATE TABLE IF NOT EXISTS datos_bancarios (
    id        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titular   VARCHAR(200) NOT NULL DEFAULT '',
    banco     VARCHAR(100) NOT NULL DEFAULT '',
    cbu       VARCHAR(22)  NOT NULL DEFAULT '',
    alias     VARCHAR(50)  NOT NULL DEFAULT '',
    instrucciones TEXT NULL,
    pin_hash  VARCHAR(255) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$datos   = $pdo->query("SELECT * FROM datos_bancarios ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$tienePin = !empty($datos['pin_hash']);
?>

<link rel="stylesheet" href="<?= URL_ASSETS ?>/configuraciones/cfg.css">
<style>
.bank-wrap{max-width:580px;margin:0 auto}
.bank-card{background:#fff;border:1px solid var(--border,#e8e7e4);border-radius:14px;padding:28px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.bank-section-title{font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#9a9a96;margin-bottom:16px}
.bank-field{margin-bottom:16px}
.bank-field label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:#333}
.bank-field input,.bank-field textarea{width:100%;padding:11px 14px;border:1.5px solid #e5e5e2;border-radius:10px;font-size:14px;font-family:inherit;outline:none;transition:border .15s;box-sizing:border-box}
.bank-field input:focus,.bank-field textarea:focus{border-color:#c88e99}
.bank-field textarea{resize:vertical;min-height:70px}
.security-banner{display:flex;align-items:flex-start;gap:12px;background:#fff8f0;border:1.5px solid #fbbf24;border-radius:12px;padding:14px 16px;margin-bottom:20px}
.security-banner i{color:#f59e0b;margin-top:2px;flex-shrink:0}
.security-banner p{font-size:13px;color:#7c5308;line-height:1.5;margin:0}
.pin-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:900;display:flex;align-items:center;justify-content:center}
.pin-card{background:#fff;border-radius:16px;padding:32px 28px;max-width:340px;width:90%;text-align:center}
.pin-card h3{font-size:18px;margin-bottom:8px}
.pin-card p{font-size:13px;color:#666;margin-bottom:20px}
.pin-dots{display:flex;justify-content:center;gap:10px;margin-bottom:20px}
.pin-dot{width:14px;height:14px;border-radius:50%;border:2px solid #ddd;transition:.15s}
.pin-dot.filled{background:#c88e99;border-color:#c88e99}
.pin-input{position:absolute;opacity:0;pointer-events:none}
.pin-btn-area{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;max-width:220px;margin:0 auto 16px}
.pin-key{padding:14px;border:1.5px solid #e5e5e2;border-radius:10px;font-size:18px;font-weight:700;cursor:pointer;background:#fff;transition:.12s;color:#111}
.pin-key:hover{background:#f5f4f1;border-color:#c88e99}
.pin-key.del{font-size:14px;color:#888}
.updated-at{font-size:11px;color:#aaa;margin-top:6px}
</style>

<div class="cfg-module">
  <div class="cfg-page-header">
    <div class="cfg-page-header__left">
      <a class="cfg-back" href="<?= URL_ASSETS ?>/configuraciones/index.php">
        <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
      </a>
      <div class="cfg-page-title">
        <span>Configuración</span>
        Datos bancarios para transferencia
      </div>
    </div>
  </div>

  <div class="bank-wrap">

    <div class="security-banner">
      <i class="fa-solid fa-shield-halved"></i>
      <p><strong>Módulo protegido.</strong> Cualquier modificación requiere autenticación con PIN de seguridad.
      Estos datos se muestran a los clientes cuando seleccionan "Transferencia" como método de pago.</p>
    </div>

    <div class="bank-card">
      <div class="bank-section-title"><i class="fa-solid fa-building-columns"></i> Cuenta bancaria</div>
      <div class="bank-field">
        <label>Titular de la cuenta *</label>
        <input type="text" id="fTitular" value="<?= htmlspecialchars($datos['titular'] ?? '') ?>" placeholder="Nombre completo o razón social">
      </div>
      <div class="bank-field">
        <label>Banco</label>
        <input type="text" id="fBanco" value="<?= htmlspecialchars($datos['banco'] ?? '') ?>" placeholder="Ej: Banco Galicia, Brubank, Mercado Pago...">
      </div>
      <div class="bank-field">
        <label>CBU <span style="font-size:11px;color:#aaa">(22 dígitos)</span></label>
        <input type="text" id="fCbu" value="<?= htmlspecialchars($datos['cbu'] ?? '') ?>" placeholder="0000000000000000000000" maxlength="22" inputmode="numeric">
      </div>
      <div class="bank-field">
        <label>Alias</label>
        <input type="text" id="fAlias" value="<?= htmlspecialchars($datos['alias'] ?? '') ?>" placeholder="CANETTO.COOKIES.MP">
      </div>
      <div class="bank-field">
        <label>Instrucciones para el cliente <span style="font-size:11px;color:#aaa">(opcional)</span></label>
        <textarea id="fInstrucciones" placeholder="Ej: Enviá el comprobante por WhatsApp al ..."><?= htmlspecialchars($datos['instrucciones'] ?? '') ?></textarea>
      </div>
      <?php if ($datos): ?>
        <div class="updated-at">Última actualización: <?= date('d/m/Y H:i', strtotime($datos['updated_at'])) ?></div>
      <?php endif; ?>
    </div>

    <div class="bank-card">
      <div class="bank-section-title"><i class="fa-solid fa-lock"></i> PIN de seguridad</div>
      <?php if ($tienePin): ?>
        <p style="font-size:13px;color:#666;margin:0 0 14px">El módulo ya tiene un PIN configurado. Ingresalo a continuación para poder modificarlo o actualizarlo.</p>
      <?php else: ?>
        <p style="font-size:13px;color:#666;margin:0 0 14px">Configurá un PIN numérico (mínimo 4 dígitos) para proteger los cambios en estos datos bancarios.</p>
      <?php endif; ?>
      <div class="bank-field">
        <label><?= $tienePin ? 'PIN actual (para verificar)' : 'Nuevo PIN *' ?></label>
        <input type="password" id="fPin" placeholder="••••" inputmode="numeric" maxlength="10" autocomplete="off">
      </div>
      <?php if ($tienePin): ?>
      <div class="bank-field">
        <label>Nuevo PIN <span style="font-size:11px;color:#aaa">(dejá vacío para mantener el actual)</span></label>
        <input type="password" id="fNuevoPin" placeholder="Dejar vacío para no cambiar" inputmode="numeric" maxlength="10" autocomplete="off">
      </div>
      <?php endif; ?>
    </div>

    <div id="alertMsg" style="display:none;margin-bottom:16px"></div>

    <button class="btn-primary" id="btnGuardar" onclick="guardar()" style="width:100%;padding:14px;font-size:15px">
      <i class="fa-solid fa-floppy-disk"></i> Guardar datos bancarios
    </button>

  </div>
</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
<script>
async function guardar() {
    const titular  = document.getElementById('fTitular').value.trim();
    const banco    = document.getElementById('fBanco').value.trim();
    const cbu      = document.getElementById('fCbu').value.trim();
    const alias    = document.getElementById('fAlias').value.trim();
    const instrucciones = document.getElementById('fInstrucciones').value.trim();
    const pin      = document.getElementById('fPin').value.trim();
    const nuevoPinEl = document.getElementById('fNuevoPin');
    const nuevo_pin  = nuevoPinEl ? nuevoPinEl.value.trim() : pin;

    if (!titular) { showAlert('El titular es obligatorio', 'err'); return; }
    if (!cbu && !alias) { showAlert('Ingresá al menos el CBU o el Alias', 'err'); return; }
    if (!pin) { showAlert('El PIN de seguridad es requerido', 'err'); return; }

    const btn = document.getElementById('btnGuardar');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    try {
        const res = await fetch('ajax/guardar_datos_bancarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion:'guardar', titular, banco, cbu, alias, instrucciones, pin, nuevo_pin })
        });
        const data = await res.json();
        if (data.ok) {
            showAlert('Datos bancarios guardados correctamente', 'ok');
        } else {
            showAlert(data.msg || 'No se pudo guardar', 'err');
        }
    } catch(e) {
        showAlert('Error de conexión', 'err');
    }
    btn.innerHTML = orig;
    btn.disabled = false;
}

function showAlert(msg, type) {
    const el = document.getElementById('alertMsg');
    el.style.display = 'block';
    el.className = type === 'ok'
        ? 'alert-success'
        : 'alert';
    el.innerHTML = (type === 'ok'
        ? '<i class="fa-solid fa-circle-check"></i> '
        : '<i class="fa-solid fa-circle-exclamation"></i> ') + msg;
    setTimeout(() => { el.style.display = 'none'; }, 5000);
}
</script>
