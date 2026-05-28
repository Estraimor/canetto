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
    'min_cookies_pedido'      => '4',
    'max_cookies_pedido'      => '100',
    'mensaje_min_pedido'      => 'El pedido mínimo es de {min} cookies.',
    'tienda_abierta'          => '1',
    'horario_activado'        => '0',
    'horario_apertura'        => '09:00',
    'horario_cierre'          => '21:00',
    'horario_forzado_cerrado' => '0',
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
/* ── Layout ─────────────────────────────────────────── */
.cfg-wrap    { max-width: 1160px; margin: 0 auto; padding: 32px 28px 80px; }
.cfg-header  { margin-bottom: 28px; }
.cfg-header h1 { font-size: 24px; font-weight: 800; color: #1a1a1a; margin: 0 0 4px; }
.cfg-header p  { font-size: 13px; color: #999; margin: 0; }

/* Grid principal de cards */
.cfg-grid {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 24px;
    align-items: start;
}
@media (max-width: 820px) { .cfg-grid { grid-template-columns: 1fr; } }

.cfg-card {
    background: #fff; border-radius: 16px;
    border: 1px solid #ebebeb;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    overflow: hidden;
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

/* ── Toggle switch ──────────────────────────────────── */
.cfg-toggle-wrap { display:inline-flex; align-items:center; cursor:pointer; }
.cfg-toggle-wrap input { display:none; }
.cfg-toggle-track {
    width:52px; height:28px; border-radius:14px; background:#e5e7eb;
    position:relative; transition:background .25s; flex-shrink:0;
    box-shadow:inset 0 1px 3px rgba(0,0,0,.1);
}
.cfg-toggle-wrap input:checked ~ .cfg-toggle-track { background:#c88e99; }
.cfg-toggle-thumb {
    position:absolute; top:3px; left:3px;
    width:22px; height:22px; border-radius:50%; background:#fff;
    box-shadow:0 2px 6px rgba(0,0,0,.2);
    transition:transform .25s cubic-bezier(.34,1.56,.64,1);
}
.cfg-toggle-wrap input:checked ~ .cfg-toggle-track .cfg-toggle-thumb { transform:translateX(24px); }

/* ── Selector de 3 modos ────────────────────────────── */
.modo-grid {
    display: grid; grid-template-columns: repeat(3,1fr); gap: 10px;
}
.modo-card {
    position: relative; cursor: pointer; border-radius: 14px;
    border: 2px solid #ebebeb; background: #fafafa;
    padding: 16px 14px; text-align: center;
    transition: border-color .18s, background .18s, box-shadow .18s;
    user-select: none;
}
.modo-card input[type="radio"] { display:none; }
.modo-card:hover { border-color: #d0d0d0; background: #f5f5f5; }

.modo-card.selected-abierta    { border-color:#16a34a; background:#f0fdf4; box-shadow:0 0 0 3px rgba(22,163,74,.1); }
.modo-card.selected-solo_vista { border-color:#2563eb; background:#eff6ff; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.modo-card.selected-cerrada    { border-color:#dc2626; background:#fef2f2; box-shadow:0 0 0 3px rgba(220,38,38,.1); }

.modo-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin: 0 auto 10px;
}
.modo-card.selected-abierta    .modo-icon { background:#dcfce7; color:#16a34a; }
.modo-card.selected-solo_vista .modo-icon { background:#dbeafe; color:#2563eb; }
.modo-card.selected-cerrada    .modo-icon { background:#fee2e2; color:#dc2626; }
.modo-card:not([class*="selected"]) .modo-icon { background:#f0f0f0; color:#aaa; }

.modo-name {
    font-size: 13px; font-weight: 800; color: #1a1a1a; margin-bottom: 4px;
}
.modo-desc {
    font-size: 11px; color: #aaa; line-height: 1.4;
}
.modo-card.selected-abierta    .modo-name { color:#15803d; }
.modo-card.selected-solo_vista .modo-name { color:#1d4ed8; }
.modo-card.selected-cerrada    .modo-name { color:#b91c1c; }

.modo-check {
    position:absolute; top:8px; right:8px;
    width:18px; height:18px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:9px; color:#fff; opacity:0; transition:opacity .15s;
}
.modo-card.selected-abierta    .modo-check { background:#16a34a; opacity:1; }
.modo-card.selected-solo_vista .modo-check { background:#2563eb; opacity:1; }
.modo-card.selected-cerrada    .modo-check { background:#dc2626; opacity:1; }

/* ── Sección separadora ─────────────────────────────── */
.cfg-section-sep {
    display:flex; align-items:center; gap:12px; margin: 4px 0;
}
.cfg-section-sep span {
    font-size:10px; font-weight:800; color:#bbb;
    text-transform:uppercase; letter-spacing:.08em; white-space:nowrap;
}
.cfg-section-sep::before, .cfg-section-sep::after {
    content:''; flex:1; height:1px; background:#f0f0f0;
}

/* ── Panel horario horizontal ───────────────────────── */
.horario-panel {
    display: grid; grid-template-columns: auto 1fr auto; gap: 0;
    background: #fafafa; border: 1.5px solid #f0f0f0;
    border-radius: 14px; overflow: hidden;
}
.horario-panel-section {
    padding: 16px 18px;
    display: flex; flex-direction: column; justify-content: center;
}
.horario-panel-section + .horario-panel-section { border-left: 1.5px solid #f0f0f0; }

.horario-toggle-col { min-width:150px; gap:10px; }
.horario-toggle-col .toggle-row { display:flex; align-items:center; gap:10px; }
.horario-toggle-col strong { display:block; font-size:13px; font-weight:700; color:#1a1a1a; margin-top:8px; margin-bottom:2px; }
.horario-toggle-col span   { font-size:11px; color:#aaa; line-height:1.4; }

.horario-times-col { gap:12px; }
.horario-times-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.horario-time-block { flex:1; min-width:110px; }
.horario-time-block label {
    display:flex; align-items:center; gap:5px;
    font-size:10px; font-weight:700; color:#aaa;
    text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px;
}
.horario-time-block input[type="time"] {
    width:100%; padding:10px 14px;
    border:1.5px solid #e9e4f0; border-radius:10px;
    font-size:16px; font-weight:800; color:#1a1a1a;
    background:#fff; outline:none; font-family:inherit;
    transition:border-color .15s, box-shadow .15s;
}
.horario-time-block input[type="time"]:focus {
    border-color:#c88e99; box-shadow:0 0 0 3px rgba(200,142,153,.12);
}
.horario-times-sep { color:#ddd; font-size:18px; flex-shrink:0; margin-top:22px; }

.horario-now-col {
    min-width:100px; text-align:center;
    background:linear-gradient(135deg,#fdf0f3,#fce4ec);
    border-left:1.5px solid #e8b4c0 !important;
}
.horario-now-col .now-label { font-size:9px; color:#c0869a; font-weight:700; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
.horario-now-col .now-time  { font-size:26px; font-weight:900; color:#c88e99; letter-spacing:1px; line-height:1; }
.horario-now-col .now-tz    { font-size:9px; color:#d4a0ad; margin-top:5px; }

.horario-alert {
    display:flex; align-items:center; gap:10px;
    background:#fef2f2; border:1.5px solid #fca5a5; border-radius:12px;
    padding:12px 16px; font-size:13px; color:#991b1b;
}

/* ── Responsive ─────────────────────────────────────── */
@media (max-width: 820px) {
    .cfg-grid { grid-template-columns: 1fr; }
    .horario-panel { grid-template-columns: 1fr; }
    .horario-panel-section + .horario-panel-section { border-left:none !important; border-top:1.5px solid #f0f0f0; }
    .horario-now-col { border-left:none !important; border-top:1.5px solid #e8b4c0 !important; }
    .modo-grid { grid-template-columns: 1fr; }
}
@media (max-width:500px) {
    .horario-times-row { flex-direction:column; }
    .horario-times-sep { margin-top:0; transform:rotate(90deg); }
}

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

    <div class="cfg-grid">

    <!-- Card principal: Modo + Horario -->
    <?php
        $tz     = new DateTimeZone('America/Argentina/Buenos_Aires');
        $ahora  = new DateTime('now', $tz);
        $minAct = (int)$ahora->format('H') * 60 + (int)$ahora->format('i');
        $hap = explode(':', $cfg['horario_apertura']); $minAp = (int)$hap[0]*60+(int)($hap[1]??0);
        $hci = explode(':', $cfg['horario_cierre']);   $minCi = (int)$hci[0]*60+(int)($hci[1]??0);
        $enH = ($minAct >= $minAp && $minAct < $minCi);
        // Modo efectivo actual
        $modoConfig = $cfg['tienda_modo'] ?? 'abierta';
        if ($cfg['horario_activado'] === '1') {
            $modoEfectivo = ($enH && $cfg['horario_forzado_cerrado'] !== '1') ? $modoConfig : 'cerrada';
        } else {
            $modoEfectivo = $modoConfig;
        }
        $estadoBadges = [
            'abierta'    => ['cls'=>'abierta',    'icon'=>'fa-check-circle', 'txt'=>'Abierta ahora'],
            'solo_vista' => ['cls'=>'solo_vista',  'icon'=>'fa-eye',          'txt'=>'Cerrado para pedidos'],
            'cerrada'    => ['cls'=>'cerrada',     'icon'=>'fa-ban',          'txt'=>'Cerrada'],
        ];
        $b = $estadoBadges[$modoEfectivo] ?? [];
    ?>
    <div class="cfg-card">
        <div class="cfg-card-head">
            <div class="cfg-card-icon" style="background:#fdf0f3"><i class="fa-solid fa-store" style="color:#c88e99"></i></div>
            <div style="flex:1">
                <div class="cfg-card-title">Estado y horario de la tienda</div>
                <div class="cfg-card-sub">Controlá si los clientes pueden ver y pedir, y en qué horarios</div>
            </div>
            <?php if ($b): ?>
            <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 13px;border-radius:20px;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;flex-shrink:0;
                <?= $modoEfectivo==='abierta' ? 'background:#d1fae5;color:#065f46' : ($modoEfectivo==='solo_vista' ? 'background:#dbeafe;color:#1d4ed8' : 'background:#fee2e2;color:#991b1b') ?>">
                <i class="fa-solid <?= $b['icon'] ?>" style="font-size:8px"></i>
                <?= $b['txt'] ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="cfg-card-body">

            <!-- MODO DE LA TIENDA -->
            <div class="cfg-section-sep"><span>Modo de la tienda</span></div>
            <p style="font-size:12px;color:#888;margin:-8px 0 2px">Elegí cómo ven la tienda tus clientes cuando está dentro del horario</p>

            <div class="modo-grid" id="modoGrid">

                <label class="modo-card <?= $modoConfig==='abierta' ? 'selected-abierta' : '' ?>" data-modo="abierta">
                    <input type="radio" name="tienda_modo" value="abierta" <?= $modoConfig==='abierta' ? 'checked' : '' ?>>
                    <div class="modo-check"><i class="fa-solid fa-check"></i></div>
                    <div class="modo-icon"><i class="fa-solid fa-check-circle"></i></div>
                    <div class="modo-name">Abierta</div>
                    <div class="modo-desc">Clientes ven los productos y pueden hacer pedidos</div>
                </label>

                <label class="modo-card <?= $modoConfig==='solo_vista' ? 'selected-solo_vista' : '' ?>" data-modo="solo_vista">
                    <input type="radio" name="tienda_modo" value="solo_vista" <?= $modoConfig==='solo_vista' ? 'checked' : '' ?>>
                    <div class="modo-check"><i class="fa-solid fa-check"></i></div>
                    <div class="modo-icon"><i class="fa-solid fa-eye"></i></div>
                    <div class="modo-name">Cerrado para pedidos</div>
                    <div class="modo-desc">Pueden ver el menú y los precios, pero no pueden pedir</div>
                </label>

                <label class="modo-card <?= $modoConfig==='cerrada' ? 'selected-cerrada' : '' ?>" data-modo="cerrada">
                    <input type="radio" name="tienda_modo" value="cerrada" <?= $modoConfig==='cerrada' ? 'checked' : '' ?>>
                    <div class="modo-check"><i class="fa-solid fa-check"></i></div>
                    <div class="modo-icon"><i class="fa-solid fa-store-slash"></i></div>
                    <div class="modo-name">Cerrada</div>
                    <div class="modo-desc">Los clientes ven una página de tienda fuera de línea</div>
                </label>

            </div>

            <!-- HORARIO AUTOMÁTICO -->
            <div class="cfg-section-sep" style="margin-top:6px"><span>Horario automático</span></div>
            <p style="font-size:12px;color:#888;margin:-8px 0 2px">La tienda aplica el modo elegido dentro del horario. Fuera del horario, siempre estará <strong>cerrada</strong></p>

            <div class="horario-panel">

                <div class="horario-panel-section horario-toggle-col">
                    <div class="toggle-row">
                        <label class="cfg-toggle-wrap">
                            <input type="checkbox" id="horario_activado"
                                   <?= $cfg['horario_activado'] === '1' ? 'checked' : '' ?>>
                            <span class="cfg-toggle-track"><span class="cfg-toggle-thumb"></span></span>
                        </label>
                    </div>
                    <strong id="horarioActivadoLabel">
                        <?= $cfg['horario_activado'] === '1' ? 'Activado' : 'Desactivado' ?>
                    </strong>
                    <span>Abrir y cerrar según horario</span>
                </div>

                <div class="horario-panel-section horario-times-col" id="horarioInputs"
                     style="<?= $cfg['horario_activado'] !== '1' ? 'opacity:.35;pointer-events:none;' : '' ?>transition:opacity .25s">
                    <div class="horario-times-row">
                        <div class="horario-time-block">
                            <label><i class="fa-solid fa-sun" style="color:#f59e0b"></i> Apertura</label>
                            <input type="time" id="horario_apertura" value="<?= htmlspecialchars($cfg['horario_apertura']) ?>">
                        </div>
                        <div class="horario-times-sep"><i class="fa-solid fa-arrow-right"></i></div>
                        <div class="horario-time-block">
                            <label><i class="fa-solid fa-moon" style="color:#6366f1"></i> Cierre</label>
                            <input type="time" id="horario_cierre" value="<?= htmlspecialchars($cfg['horario_cierre']) ?>">
                        </div>
                    </div>
                </div>

                <div class="horario-panel-section horario-now-col">
                    <div class="now-label">Son las</div>
                    <div class="now-time"><?= $ahora->format('H:i') ?></div>
                    <div class="now-tz">Argentina · UTC-3</div>
                </div>

            </div>

            <?php if ($cfg['horario_forzado_cerrado'] === '1' && $cfg['horario_activado'] === '1'): ?>
            <div class="horario-alert">
                <i class="fa-solid fa-triangle-exclamation" style="flex-shrink:0"></i>
                <div><strong>Cierre manual activo.</strong> La tienda está cerrada aunque esté dentro del horario. Reabrila desde el botón en la barra superior.</div>
            </div>
            <?php endif; ?>

        </div>

        <div class="cfg-actions">
            <button class="btn-cfg-soft" onclick="resetCard('horario')">Cancelar</button>
            <button class="btn-cfg-save" onclick="guardar('horario')">
                <i class="fa-solid fa-floppy-disk"></i> Guardar configuración
            </button>
        </div>
    </div>

    <!-- Pedido mínimo / máximo -->
    <div class="cfg-card" style="align-self:start">
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

    </div><!-- /cfg-grid -->

</div>

<div class="cfg-toast" id="cfgToast">
    <i class="fa-solid fa-circle-check"></i>
    <span id="cfgToastMsg">Configuración guardada</span>
</div>

<script>
const ORIG = {
    min_cookies_pedido: <?= json_encode($cfg['min_cookies_pedido']) ?>,
    max_cookies_pedido: <?= json_encode($cfg['max_cookies_pedido']) ?>,
    mensaje_min_pedido: <?= json_encode($cfg['mensaje_min_pedido']) ?>,
    tienda_modo:        <?= json_encode($cfg['tienda_modo'] ?? 'abierta') ?>,
    horario_activado:   <?= json_encode($cfg['horario_activado']) ?>,
    horario_apertura:   <?= json_encode($cfg['horario_apertura']) ?>,
    horario_cierre:     <?= json_encode($cfg['horario_cierre']) ?>,
};

/* ── Selector de modos ── */
document.querySelectorAll('.modo-card').forEach(card => {
    card.addEventListener('click', () => {
        const modo = card.dataset.modo;
        card.querySelector('input').checked = true;
        document.querySelectorAll('.modo-card').forEach(c => {
            c.className = 'modo-card' + (c.dataset.modo === modo ? ` selected-${modo}` : '');
        });
    });
});

/* ── Toggle horario ── */
document.getElementById('horario_activado')?.addEventListener('change', function() {
    const inputs = document.getElementById('horarioInputs');
    const label  = document.getElementById('horarioActivadoLabel');
    inputs.style.opacity      = this.checked ? '1' : '.35';
    inputs.style.pointerEvents = this.checked ? 'auto' : 'none';
    label.textContent = this.checked ? 'Activado' : 'Desactivado';
});

/* ── Helpers ── */
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
        document.getElementById('min_cookies_pedido').value = ORIG.min_cookies_pedido;
        document.getElementById('max_cookies_pedido').value = ORIG.max_cookies_pedido;
        document.getElementById('mensaje_min_pedido').value = ORIG.mensaje_min_pedido;
    }
    if (card === 'horario') {
        // Resetear modo
        document.querySelectorAll('.modo-card').forEach(c => {
            c.className = 'modo-card' + (c.dataset.modo === ORIG.tienda_modo ? ` selected-${ORIG.tienda_modo}` : '');
            c.querySelector('input').checked = c.dataset.modo === ORIG.tienda_modo;
        });
        // Resetear horario
        const chk = document.getElementById('horario_activado');
        chk.checked = ORIG.horario_activado === '1';
        chk.dispatchEvent(new Event('change'));
        document.getElementById('horario_apertura').value = ORIG.horario_apertura;
        document.getElementById('horario_cierre').value   = ORIG.horario_cierre;
    }
}

async function guardar(card) {
    let data = {};

    if (card === 'limites') {
        const min = parseInt(val('min_cookies_pedido')) || 1;
        const max = parseInt(val('max_cookies_pedido')) || 0;
        if (max > 0 && max < min) { toast('El máximo no puede ser menor al mínimo', false); return; }
        data = {
            min_cookies_pedido: String(min),
            max_cookies_pedido: String(max),
            mensaje_min_pedido: val('mensaje_min_pedido') || 'El pedido mínimo es de {min} cookies.',
        };
    }

    if (card === 'horario') {
        const ap   = val('horario_apertura');
        const ci   = val('horario_cierre');
        const modo = document.querySelector('input[name="tienda_modo"]:checked')?.value ?? 'abierta';
        if (!ap || !ci) { toast('Completá ambas horas', false); return; }
        if (document.getElementById('horario_activado').checked && ap >= ci) {
            toast('La hora de apertura debe ser antes del cierre', false); return;
        }
        data = {
            tienda_modo:      modo,
            horario_activado: document.getElementById('horario_activado').checked ? '1' : '0',
            horario_apertura: ap,
            horario_cierre:   ci,
        };
    }

    const res  = await fetch('ajax/tienda_settings.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) });
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
