<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/audit.php';
$pageTitle = "Configuración de la Tienda";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

// Crear tabla si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS configuracion_tienda (
    clave VARCHAR(60) PRIMARY KEY,
    valor TEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Defaults
$defaults = [
    'min_cookies_pedido'   => '4',
    'max_cookies_pedido'   => '100',
    'mensaje_min_pedido'   => 'El pedido mínimo es de {min} cookies.',
    'tienda_abierta'       => '1',
];

foreach ($defaults as $clave => $valor) {
    $pdo->prepare("INSERT IGNORE INTO configuracion_tienda (clave, valor) VALUES (?,?)")
        ->execute([$clave, $valor]);
}

// Leer config actual
$cfg = [];
foreach ($pdo->query("SELECT clave, valor FROM configuracion_tienda")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $cfg[$r['clave']] = $r['valor'];
}
$cfg = array_merge($defaults, $cfg);
?>

<style>
.cfg-wrap { max-width: 700px; margin: 0 auto; padding: 32px 24px 80px; }
.cfg-header { margin-bottom: 32px; }
.cfg-header h1 { font-size: 24px; font-weight: 800; color: #1a1a1a; margin: 0 0 4px; }
.cfg-header p  { font-size: 13px; color: #999; margin: 0; }

.cfg-card {
    background: #fff; border-radius: 16px;
    border: 1px solid #ebebeb;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    margin-bottom: 20px; overflow: hidden;
}
.cfg-card-head {
    padding: 16px 22px; border-bottom: 1px solid #f5f5f5;
    display: flex; align-items: center; gap: 12px;
}
.cfg-card-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: #fdf0f3; color: #c88e99;
    display: flex; align-items: center; justify-content: center; font-size: 16px;
    flex-shrink: 0;
}
.cfg-card-title { font-size: 15px; font-weight: 700; color: #1a1a1a; }
.cfg-card-sub   { font-size: 12px; color: #aaa; margin-top: 2px; }
.cfg-card-body  { padding: 22px; display: flex; flex-direction: column; gap: 18px; }

.cfg-field label {
    display: block; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    color: #888; margin-bottom: 7px;
}
.cfg-field input, .cfg-field textarea {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid #e9e4f0; border-radius: 10px;
    font-size: 14px; font-family: inherit; color: #1a1a1a;
    background: #fafafa; outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.cfg-field input:focus, .cfg-field textarea:focus {
    border-color: #c88e99;
    box-shadow: 0 0 0 3px rgba(200,142,153,.12);
    background: #fff;
}
.cfg-field input[type="number"] { max-width: 140px; }
.cfg-hint { font-size: 11px; color: #aaa; margin-top: 5px; }
.cfg-row { display: flex; gap: 16px; flex-wrap: wrap; }
.cfg-row .cfg-field { flex: 1; min-width: 120px; }

.cfg-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 22px; border-top: 1px solid #f5f5f5; }
.btn-cfg-save {
    background: #c88e99; color: #fff; border: none;
    padding: 10px 24px; border-radius: 10px;
    font-size: 13px; font-weight: 700; font-family: inherit;
    cursor: pointer; display: flex; align-items: center; gap: 7px;
    transition: background .15s;
}
.btn-cfg-save:hover { background: #b37a87; }
.btn-cfg-soft {
    background: #f3f4f6; color: #555; border: none;
    padding: 10px 18px; border-radius: 10px;
    font-size: 13px; font-weight: 600; font-family: inherit;
    cursor: pointer; transition: background .15s;
}
.btn-cfg-soft:hover { background: #e5e7eb; }

.cfg-toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 9999;
    background: #1a1a1a; color: #fff;
    padding: 13px 22px; border-radius: 12px;
    font-size: 13px; font-weight: 600;
    display: flex; align-items: center; gap: 10px;
    transform: translateY(80px); opacity: 0;
    transition: all .3s cubic-bezier(.34,1.56,.64,1);
    box-shadow: 0 8px 24px rgba(0,0,0,.2);
}
.cfg-toast.show { transform: translateY(0); opacity: 1; }
.cfg-toast i { color: #4ade80; }
</style>

<div class="cfg-wrap">

    <a href="javascript:history.back()" class="btn-back" style="margin-bottom:20px;display:inline-flex">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>

    <div class="cfg-header">
        <h1>⚙️ Configuración de la Tienda</h1>
        <p>Parámetros que controlan el comportamiento del pedido online</p>
    </div>

    <!-- Pedido mínimo / máximo -->
    <div class="cfg-card">
        <div class="cfg-card-head">
            <div class="cfg-card-icon"><i class="fa-solid fa-cookie-bite"></i></div>
            <div>
                <div class="cfg-card-title">Límites de pedido</div>
                <div class="cfg-card-sub">Cantidad mínima y máxima de cookies por pedido</div>
            </div>
        </div>
        <div class="cfg-card-body">
            <div class="cfg-row">
                <div class="cfg-field">
                    <label>Mínimo de cookies</label>
                    <input type="number" id="min_cookies_pedido" min="1" max="999"
                           value="<?= htmlspecialchars($cfg['min_cookies_pedido']) ?>">
                    <div class="cfg-hint">El cliente no puede pedir menos de este número</div>
                </div>
                <div class="cfg-field">
                    <label>Máximo de cookies</label>
                    <input type="number" id="max_cookies_pedido" min="1" max="9999"
                           value="<?= htmlspecialchars($cfg['max_cookies_pedido']) ?>">
                    <div class="cfg-hint">Límite por pedido (0 = sin límite)</div>
                </div>
            </div>
            <div class="cfg-field">
                <label>Mensaje al cliente cuando no cumple el mínimo</label>
                <input type="text" id="mensaje_min_pedido"
                       value="<?= htmlspecialchars($cfg['mensaje_min_pedido']) ?>">
                <div class="cfg-hint">Usá {min} para insertar el número mínimo automáticamente</div>
            </div>
        </div>
        <div class="cfg-actions">
            <button class="btn-cfg-soft" onclick="resetCard('limites')">Cancelar</button>
            <button class="btn-cfg-save" onclick="guardar('limites')">
                <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
        </div>
    </div>

</div>

<div class="cfg-toast" id="cfgToast">
    <i class="fa-solid fa-circle-check"></i>
    <span id="cfgToastMsg">Configuración guardada</span>
</div>

<script>
const ORIG = {
    min_cookies_pedido:  <?= json_encode($cfg['min_cookies_pedido']) ?>,
    max_cookies_pedido:  <?= json_encode($cfg['max_cookies_pedido']) ?>,
    mensaje_min_pedido:  <?= json_encode($cfg['mensaje_min_pedido']) ?>,
};

function toast(msg, ok = true) {
    const t = document.getElementById('cfgToast');
    document.getElementById('cfgToastMsg').textContent = msg;
    t.querySelector('i').style.color = ok ? '#4ade80' : '#f87171';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

function val(id) { return document.getElementById(id)?.value ?? ''; }

function resetCard(card) {
    if (card === 'limites') {
        document.getElementById('min_cookies_pedido').value  = ORIG.min_cookies_pedido;
        document.getElementById('max_cookies_pedido').value  = ORIG.max_cookies_pedido;
        document.getElementById('mensaje_min_pedido').value  = ORIG.mensaje_min_pedido;
    }
}

async function guardar(card) {
    let data = {};
    if (card === 'limites') {
        const min = parseInt(val('min_cookies_pedido')) || 1;
        const max = parseInt(val('max_cookies_pedido')) || 0;
        if (max > 0 && max < min) { toast('El máximo no puede ser menor al mínimo', false); return; }
        data = {
            min_cookies_pedido:  String(min),
            max_cookies_pedido:  String(max),
            mensaje_min_pedido:  val('mensaje_min_pedido') || 'El pedido mínimo es de {min} cookies.',
        };
    }

    const res  = await fetch('ajax/tienda_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.ok) {
        Object.assign(ORIG, data);
        toast('Configuración guardada ✓');
    } else {
        toast(json.msg || 'Error al guardar', false);
    }
}
</script>

<?php include '../panel/dashboard/layaut/footer.php'; ?>
