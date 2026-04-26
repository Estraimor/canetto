<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Paneles del Carrusel";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

try { $pdo->exec("ALTER TABLE oferta ADD COLUMN imagen VARCHAR(255) NULL"); } catch (Throwable $e) {}
try { $pdo->exec("ALTER TABLE oferta ADD COLUMN productos_idproductos INT NULL"); } catch (Throwable $e) {}
try { $pdo->exec("ALTER TABLE oferta ADD COLUMN tipo_panel VARCHAR(30) NULL DEFAULT 'promo'"); } catch (Throwable $e) {}
$pdo->exec("UPDATE oferta SET tipo_panel='promo' WHERE tipo_panel IS NULL");

$total   = (int)$pdo->query("SELECT COUNT(*) FROM oferta")->fetchColumn();
$activas = (int)$pdo->query("SELECT COUNT(*) FROM oferta WHERE activo=1")->fetchColumn();

$productos_lista = $pdo->query("
    SELECT idproductos, nombre, precio, tipo FROM productos WHERE activo=1 ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Crear tabla tipos_panel si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS tipos_panel (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    clave  VARCHAR(40) NOT NULL UNIQUE,
    label  VARCHAR(60) NOT NULL,
    emoji  VARCHAR(8)  NOT NULL DEFAULT '📌',
    color  VARCHAR(20) NOT NULL DEFAULT '#888888',
    activo TINYINT(1)  NOT NULL DEFAULT 1,
    orden  INT         NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// INSERT IGNORE garantiza que siempre existan los tipos base sin duplicar
$ins = $pdo->prepare("INSERT IGNORE INTO tipos_panel (clave,label,emoji,color,orden) VALUES (?,?,?,?,?)");
foreach ([
    ['promo',       'Promo',        '📢', '#c88e99', 0],
    ['bienvenida',  'Bienvenida',   '👋', '#1d9e75', 1],
    ['regalo',      'Regalo',       '🎁', '#7c3aed', 2],
    ['soporte',     'Soporte',      '🛟', '#0891b2', 3],
    ['temporada',   'Temporada',    '🌸', '#f59e0b', 4],
    ['descuento',   'Descuento',    '💸', '#dc2626', 5],
    ['novedad',     'Novedad',      '✨', '#8b5cf6', 6],
    ['anuncio',     'Anuncio',      '📣', '#0ea5e9', 7],
    ['informativo', 'Informativo',  'ℹ️',  '#64748b', 8],
    ['marketing',   'Marketing',    '🚀', '#f97316', 9],
] as $d) $ins->execute($d);

// Cargar tipos desde DB
$tiposRows   = $pdo->query("SELECT * FROM tipos_panel WHERE activo=1 ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
$TIPOS_PANEL = [];
foreach ($tiposRows as $t) {
    $TIPOS_PANEL[$t['clave']] = ['id' => $t['id'], 'label' => $t['emoji'] . ' ' . $t['label'], 'color' => $t['color'], 'emoji' => $t['emoji']];
}
?>

<link rel="stylesheet" href="<?= URL_ASSETS ?>/configuraciones/cfg.css">
<style>
.of-preview{width:100%;height:160px;object-fit:cover;border-radius:10px;margin-top:8px;display:block}
#previewWrap{display:none;margin-top:8px}
.date-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.panel-tipo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:4px}
.panel-tipo-opt{cursor:pointer}
.panel-tipo-opt input{display:none}
.panel-tipo-badge{
  display:block;padding:10px 8px;border-radius:10px;font-size:13px;font-weight:700;
  text-align:center;border:2px solid transparent;background:#f5f5f5;color:#444;
  transition:all .15s;cursor:pointer;
}
.panel-tipo-opt input:checked + .panel-tipo-badge{
  border-color:currentColor;background:rgba(200,142,153,.08);
}
.btn-link-preset{
  padding:5px 12px;border-radius:20px;border:1.5px solid #e0e0e0;
  background:#f8f8f8;color:#444;font-size:12px;font-weight:600;
  cursor:pointer;font-family:inherit;transition:all .15s;
}
.btn-link-preset:hover{background:#f0e8ea;border-color:#c88e99;color:#c88e99}
</style>

<div class="cfg-module">

    <div class="cfg-page-header">
        <div class="cfg-page-header__left">
            <a class="cfg-back" href="<?= URL_ASSETS ?>/configuraciones/index.php">
                <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
            </a>
            <div class="cfg-page-title">
                <span>Tienda Online</span>
                Paneles del Carrusel
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <button class="btn-sm" onclick="openTiposModal()" title="Gestionar tipos de panel">
                <i class="fa-solid fa-tags"></i> Gestionar tipos
            </button>
            <button class="btn-primary" onclick="openModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo panel
            </button>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card__num" id="statTotal"><?= $total ?></div>
            <div class="stat-card__label">Total paneles</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-success" id="statActivas"><?= $activas ?></div>
            <div class="stat-card__label">Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-danger" id="statInactivas"><?= $total - $activas ?></div>
            <div class="stat-card__label">Inactivos</div>
        </div>
    </div>

    <!-- Filtro por tipo de panel -->
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
      <button class="cfg-filter-tag on" data-tp="" onclick="filtrarTipo(this,'')">Todos</button>
      <?php foreach($TIPOS_PANEL as $key=>$tp): ?>
      <button class="cfg-filter-tag" data-tp="<?= $key ?>" onclick="filtrarTipo(this,'<?= $key ?>')"><?= $tp['label'] ?></button>
      <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table id="tablaOfertas" style="width:100%">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Tipo de panel</th>
                    <th>Tipo oferta</th>
                    <th>Valor</th>
                    <th>Vigencia</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOferta">
    <div class="modal" style="max-width:600px;" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo panel</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-grid">

                <!-- Tipo de panel -->
                <div class="form-group full">
                    <label>Tipo de panel *</label>
                    <div class="panel-tipo-grid">
                      <?php foreach($TIPOS_PANEL as $key=>$tp): ?>
                      <label class="panel-tipo-opt">
                        <input type="radio" name="tipoPanelRadio" value="<?= $key ?>" <?= $key==='promo'?'checked':'' ?> onchange="document.getElementById('oTipoPanel').value=this.value">
                        <span class="panel-tipo-badge" style="color:<?= $tp['color'] ?>"><?= $tp['label'] ?></span>
                      </label>
                      <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="oTipoPanel" value="promo">
                </div>

                <div class="form-group full">
                    <label>Producto vinculado <span style="font-size:11px;color:#888">(opcional)</span></label>
                    <select id="oProducto" onchange="onProductoChange(this)">
                        <option value="">— Sin producto vinculado —</option>
                        <?php foreach ($productos_lista as $pr): ?>
                        <option value="<?= $pr['idproductos'] ?>"
                                data-nombre="<?= htmlspecialchars($pr['nombre']) ?>"
                                data-precio="<?= (float)$pr['precio'] ?>"
                                data-tipo="<?= $pr['tipo'] ?>">
                            <?= htmlspecialchars($pr['nombre']) ?> — $<?= number_format($pr['precio'],0,',','.') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Título *</label>
                    <input type="text" id="oTitulo" placeholder="Ej: ¡Bienvenida a Canetto!">
                </div>
                <div class="form-group full">
                    <label>Descripción</label>
                    <textarea id="oDesc" rows="2" placeholder="Descripción breve del panel..." style="resize:vertical"></textarea>
                </div>
                <div class="form-group" id="valorWrap">
                    <label>Valor <span id="valorLabel" style="font-size:11px;color:#888">(opcional)</span></label>
                    <input type="number" id="oValor" min="0" step="0.01" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Emoji del slide</label>
                    <input type="text" id="oEmoji" maxlength="8" placeholder="🎉" style="font-size:20px">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <div class="toggle-wrap">
                        <label class="toggle">
                            <input type="checkbox" id="oActivo" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label" id="oToggleLbl">Activo</span>
                    </div>
                </div>
                <div class="form-group full date-row">
                    <div>
                        <label>Fecha inicio <span style="font-size:11px;color:#888">(opcional)</span></label>
                        <input type="date" id="oFechaIni">
                    </div>
                    <div>
                        <label>Fecha fin <span style="font-size:11px;color:#888">(opcional)</span></label>
                        <input type="date" id="oFechaFin">
                    </div>
                </div>
                <div class="form-group full">
                    <label>Enlace / Contacto <span style="font-size:11px;color:#888">(opcional — URL, WhatsApp, Instagram...)</span></label>
                    <input type="text" id="oLink" placeholder="Ej: https://wa.me/5493764... o https://instagram.com/canetto">
                    <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
                        <button type="button" class="btn-link-preset" onclick="setLinkPreset('wa')">💬 WhatsApp</button>
                        <button type="button" class="btn-link-preset" onclick="setLinkPreset('ig')">📸 Instagram</button>
                        <button type="button" class="btn-link-preset" onclick="setLinkPreset('tel')">📞 Teléfono</button>
                    </div>
                    <div id="linkPreview" style="margin-top:6px;font-size:12px;color:#64748b;display:none"></div>
                </div>
                <div class="form-group full">
                    <label>Texto del botón <span style="font-size:11px;color:#888">(opcional — aparece si hay enlace)</span></label>
                    <input type="text" id="oBtnTxt" placeholder="Ej: Contactar, Ver más, Pedir ahora...">
                </div>
                <div class="form-group full">
                    <label>Imagen del slide <span style="font-size:11px;color:#888">(JPG/PNG/WebP, max 2MB)</span></label>
                    <input type="file" id="oImagen" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this)">
                    <input type="hidden" id="oImagenActual">
                    <div id="previewWrap">
                        <img id="imgPreview" class="of-preview" src="" alt="Preview">
                        <button type="button" onclick="removeImage()" style="font-size:12px;color:#c88e99;background:none;border:none;cursor:pointer;margin-top:4px">✕ Quitar imagen</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-sm" onclick="closeModal()">Cancelar</button>
            <button class="btn-primary" id="btnGuardar" onclick="guardar()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Gestionar tipos de panel -->
<div class="modal-overlay" id="modalTipos">
    <div class="modal" style="max-width:480px" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h2>Tipos de panel</h2>
            <button class="modal-close" onclick="closeTiposModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <!-- Lista de tipos existentes -->
            <div id="tiposLista" style="display:flex;flex-direction:column;gap:8px;margin-bottom:20px"></div>

            <!-- Formulario agregar nuevo tipo -->
            <div style="border-top:1px solid #eee;padding-top:16px">
                <p style="font-size:13px;font-weight:600;color:#555;margin-bottom:10px">Agregar nuevo tipo</p>
                <div style="display:grid;grid-template-columns:48px 1fr 100px;gap:8px;align-items:center">
                    <input type="text" id="nuevoEmoji" maxlength="4" placeholder="📌" style="font-size:22px;text-align:center;padding:8px 4px;border:1.5px solid #e0e0e0;border-radius:8px">
                    <input type="text" id="nuevoLabel" placeholder="Nombre del tipo" style="padding:10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:14px">
                    <input type="color" id="nuevoColor" value="#c88e99" style="height:42px;width:100%;border:1.5px solid #e0e0e0;border-radius:8px;cursor:pointer;padding:2px">
                </div>
                <button class="btn-primary" onclick="agregarTipo()" style="margin-top:10px;width:100%">
                    <i class="fa-solid fa-plus"></i> Agregar tipo
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../panel/dashboard/layaut/footer.php'; ?>

<style>
.cfg-filter-tag{
  padding:6px 14px;border-radius:20px;border:1.5px solid #e0e0e0;background:#fff;
  color:#666;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;font-family:inherit;
}
.cfg-filter-tag.on,.cfg-filter-tag:hover{background:#111;border-color:#111;color:#fff}
</style>

<script>
const TIPOS_PANEL_JS = <?= json_encode($TIPOS_PANEL, JSON_UNESCAPED_UNICODE) ?>;

let editId = null;
let dt = null;
let filtroTipoActivo = '';

function filtrarTipo(btn, tp){
  document.querySelectorAll('.cfg-filter-tag').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on');
  filtroTipoActivo = tp;
  if(dt) dt.ajax.reload();
}

$(document).ready(function () {
    dt = $('#tablaOfertas').DataTable({
        ajax: {
            url: 'ajax/listar_ofertas.php',
            dataSrc: function(json) {
                const all  = json;
                const data = filtroTipoActivo ? all.filter(o=>o.tipo_panel===filtroTipoActivo) : all;
                const activas = data.filter(o => o.activo == 1).length;
                document.getElementById('statTotal').textContent     = data.length;
                document.getElementById('statActivas').textContent   = activas;
                document.getElementById('statInactivas').textContent = data.length - activas;
                return data;
            }
        },
        columns: [
            { data: null, orderable: false, width: '70px', render: row =>
                row.imagen
                    ? `<img src="<?= URL_ASSETS ?>/img/ofertas/${esc(row.imagen)}" style="width:56px;height:40px;object-fit:cover;border-radius:6px">`
                    : `<span style="font-size:26px">${esc(row.emoji||'🎉')}</span>`
            },
            { data: 'titulo', render: v => '<strong>' + esc(v) + '</strong>' },
            { data: 'tipo_panel', render: v => {
                const tp = TIPOS_PANEL_JS[v] || {label: v||'—', color:'#888'};
                return `<span style="background:#f5f5f5;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;color:${tp.color}">${tp.label}</span>`;
            }},
            { data: 'tipo', render: v => ({promo:'Promo',descuento:'Descuento',temporada:'Temporada'}[v]||esc(v)) },
            { data: 'valor',  render: (v,t,row) => v ? (row.tipo==='descuento'?v+'%':'$'+v) : '—' },
            { data: null, render: row => {
                const fi = row.fecha_inicio || '—'; const ff = row.fecha_fin || '—';
                return fi === '—' && ff === '—' ? 'Sin límite' : fi + ' → ' + ff;
            }},
            { data: 'activo', render: v => v==1
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-danger">Inactivo</span>'
            },
            { data: null, orderable: false, render: row =>
                `<div style="display:flex;gap:5px">
                   <button class="btn-sm btn-edit" onclick="editar(${row.idoferta})">Editar</button>
                   <button class="btn-sm btn-danger" onclick="eliminar(${row.idoferta})">Eliminar</button>
                 </div>`
            },
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
        pageLength: 10,
        order: [[0,'desc']],
    });
});

function esc(v){ return String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function openModal(){ document.getElementById('modalOferta').classList.add('open'); }
function closeModal(){
    document.getElementById('modalOferta').classList.remove('open');
    editId = null;
    document.getElementById('modalTitle').textContent = 'Nuevo panel';
    document.getElementById('oTitulo').value = '';
    document.getElementById('oDesc').value = '';
    document.getElementById('oValor').value = '';
    document.getElementById('oEmoji').value = '';
    document.getElementById('valorLabel').textContent = '(opcional)';
    document.getElementById('oProducto').value = '';
    document.getElementById('oFechaIni').value = '';
    document.getElementById('oFechaFin').value = '';
    document.getElementById('oActivo').checked = true;
    document.getElementById('oToggleLbl').textContent = 'Activo';
    document.getElementById('previewWrap').style.display = 'none';
    document.getElementById('oImagenActual').value = '';
    document.getElementById('oImagen').value = '';
    document.getElementById('oLink').value = '';
    document.getElementById('oBtnTxt').value = '';
    document.getElementById('linkPreview').style.display = 'none';
    document.getElementById('oTipoPanel').value = 'promo';
    document.querySelectorAll('input[name="tipoPanelRadio"]').forEach(r=>r.checked=(r.value==='promo'));
}
document.getElementById('oActivo').addEventListener('change', function(){
    document.getElementById('oToggleLbl').textContent = this.checked ? 'Activo' : 'Inactivo';
});

// Actualizar etiqueta del valor según tipo de panel
function onTipoPanelChange(val) {
    const label = document.getElementById('valorLabel');
    label.textContent = val === 'descuento' ? '(%)' : '(opcional)';
}
// Enganchar el evento a cada radio
document.querySelectorAll('input[name="tipoPanelRadio"]').forEach(r => {
    r.addEventListener('change', () => {
        document.getElementById('oTipoPanel').value = r.value;
        onTipoPanelChange(r.value);
    });
});
function previewImage(input){
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('previewWrap').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function removeImage(){
    document.getElementById('oImagen').value = '';
    document.getElementById('oImagenActual').value = '__remove__';
    document.getElementById('previewWrap').style.display = 'none';
}
function setLinkPreset(tipo) {
    const inp = document.getElementById('oLink');
    if (tipo === 'wa')  inp.value = 'https://wa.me/549';
    if (tipo === 'ig')  inp.value = 'https://instagram.com/';
    if (tipo === 'tel') inp.value = 'tel:+549';
    inp.focus();
    inp.setSelectionRange(inp.value.length, inp.value.length);
    actualizarLinkPreview();
}

function actualizarLinkPreview() {
    const v   = document.getElementById('oLink').value.trim();
    const pre = document.getElementById('linkPreview');
    if (!v) { pre.style.display = 'none'; return; }
    let label = '🔗 ' + v;
    if (v.includes('wa.me'))        label = '💬 WhatsApp: ' + v;
    else if (v.includes('instagram')) label = '📸 Instagram: ' + v;
    else if (v.startsWith('tel:'))  label = '📞 Teléfono: ' + v.replace('tel:','');
    pre.textContent = label;
    pre.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('oLink')?.addEventListener('input', actualizarLinkPreview);
});

function onProductoChange(sel){
    const opt = sel.options[sel.selectedIndex];
    if(!opt.value) return;
    if(!document.getElementById('oTitulo').value)
        document.getElementById('oTitulo').value = opt.dataset.nombre;
    if(opt.dataset.tipo !== 'box' && !document.getElementById('oValor').value)
        document.getElementById('oValor').value = opt.dataset.precio;
}

async function editar(id){
    const res = await fetch('ajax/listar_ofertas.php').then(r=>r.json());
    const o = res.find(x=>x.idoferta==id);
    if(!o) return;
    editId = id;
    document.getElementById('modalTitle').textContent = 'Editar panel';
    document.getElementById('oTitulo').value    = o.titulo || '';
    document.getElementById('oDesc').value      = o.descripcion || '';
    document.getElementById('oValor').value     = o.valor || '';
    document.getElementById('oEmoji').value     = o.emoji || '';
    document.getElementById('oFechaIni').value  = o.fecha_inicio || '';
    document.getElementById('oFechaFin').value  = o.fecha_fin || '';
    document.getElementById('oActivo').checked  = o.activo == 1;
    document.getElementById('oToggleLbl').textContent = o.activo==1?'Activo':'Inactivo';
    document.getElementById('oProducto').value  = o.productos_idproductos || '';
    document.getElementById('oImagenActual').value = o.imagen || '';
    document.getElementById('oLink').value   = o.link || '';
    document.getElementById('oBtnTxt').value = o.btn_txt || '';
    actualizarLinkPreview();
    const tp = o.tipo_panel || 'promo';
    document.getElementById('oTipoPanel').value = tp;
    document.querySelectorAll('input[name="tipoPanelRadio"]').forEach(r=>r.checked=(r.value===tp));
    onTipoPanelChange(tp);
    document.getElementById('oValor').value = o.valor || '';
    if(o.imagen){
        document.getElementById('imgPreview').src = `<?= URL_ASSETS ?>/img/ofertas/${o.imagen}`;
        document.getElementById('previewWrap').style.display='block';
    } else {
        document.getElementById('previewWrap').style.display='none';
    }
    openModal();
}

async function guardar(){
    const titulo = document.getElementById('oTitulo').value.trim();
    if(!titulo){ Swal.fire({icon:'warning',title:'Falta el título',text:'El título es obligatorio',confirmButtonColor:'#c88e99'}); return; }
    const btn = document.getElementById('btnGuardar');
    btn.disabled = true; btn.textContent = 'Guardando...';

    const fd = new FormData();
    if(editId) fd.append('idoferta', editId);
    const tipoPanel = document.getElementById('oTipoPanel').value;
    // Derivar tipo desde tipo_panel (una sola configuración)
    const tipoMap   = { descuento: 'descuento', temporada: 'temporada' };
    const tipoOferta= tipoMap[tipoPanel] || 'promo';
    fd.append('titulo',    titulo);
    fd.append('descripcion', document.getElementById('oDesc').value.trim());
    fd.append('tipo',      tipoOferta);
    fd.append('tipo_panel', tipoPanel);
    fd.append('valor',     document.getElementById('oValor').value || '');
    fd.append('emoji',     document.getElementById('oEmoji').value || '');
    fd.append('fecha_inicio', document.getElementById('oFechaIni').value || '');
    fd.append('fecha_fin',    document.getElementById('oFechaFin').value || '');
    fd.append('activo',    document.getElementById('oActivo').checked ? 1 : 0);
    fd.append('productos_idproductos', document.getElementById('oProducto').value || '');
    fd.append('imagen_actual', document.getElementById('oImagenActual').value || '');
    fd.append('link',    document.getElementById('oLink').value.trim() || '');
    fd.append('btn_txt', document.getElementById('oBtnTxt').value.trim() || '');
    const imgFile = document.getElementById('oImagen').files[0];
    if(imgFile) fd.append('imagen', imgFile);

    const res = await fetch('ajax/guardar_oferta.php', {method:'POST', body:fd}).then(r=>r.json()).catch(()=>null);
    btn.disabled = false;
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar`;

    if(res?.ok){
        closeModal(); dt.ajax.reload();
        Swal.fire({icon:'success',title:'Guardado',timer:1200,showConfirmButton:false});
    } else {
        Swal.fire({icon:'error',title:'Error',text:res?.msg||'Error al guardar',confirmButtonColor:'#c88e99'});
    }
}

async function eliminar(id){
    const { isConfirmed } = await Swal.fire({
        icon:'warning', title:'¿Eliminar panel?',
        text:'Esta acción no se puede deshacer.',
        showCancelButton:true, confirmButtonText:'Sí, eliminar',
        cancelButtonText:'Cancelar', confirmButtonColor:'#dc2626', cancelButtonColor:'#aaa'
    });
    if(!isConfirmed) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('ajax/eliminar_oferta.php',{method:'POST',body:fd}).then(r=>r.json());
    if(res?.ok){
        dt.ajax.reload();
        Swal.fire({icon:'success',title:'Eliminado',timer:1000,showConfirmButton:false});
    } else {
        Swal.fire({icon:'error',title:'Error',text:res?.msg||'No se pudo eliminar',confirmButtonColor:'#c88e99'});
    }
}

// ── Gestión de tipos de panel ─────────────────────────────
function openTiposModal() {
    cargarTiposLista();
    document.getElementById('modalTipos').classList.add('open');
}
function closeTiposModal() {
    document.getElementById('modalTipos').classList.remove('open');
}

async function cargarTiposLista() {
    const tipos = await fetch('ajax/tipos_panel.php?accion=listar').then(r=>r.json());
    const lista = document.getElementById('tiposLista');
    if (!tipos.length) { lista.innerHTML = '<p style="color:#999;font-size:13px">Sin tipos cargados.</p>'; return; }

    const activos   = tipos.filter(t => t.activo == 1);
    const inactivos = tipos.filter(t => t.activo == 0);

    const renderFila = (t) => {
        const esActivo = t.activo == 1;
        return `
        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid ${esActivo?'#eee':'#f5d0d0'};border-radius:10px;background:${esActivo?'#fafafa':'#fff5f5'};opacity:${esActivo?1:.75}">
            <span style="font-size:20px">${t.emoji}</span>
            <span style="font-weight:600;font-size:14px;flex:1;color:${esActivo?t.color:'#aaa'}">${t.label}</span>
            <span style="font-size:11px;color:#bbb;font-family:monospace;margin-right:4px">${t.clave}</span>
            ${esActivo ? `
                <button onclick="inactivarTipo(${t.id},'${t.label}')"
                    style="background:#fef3c7;border:1.5px solid #fcd34d;color:#92400e;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer">Inactivar</button>
                <button onclick="eliminarTipo(${t.id},'${t.label}')"
                    style="background:#fee2e2;border:1.5px solid #fca5a5;color:#dc2626;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer">Eliminar</button>
            ` : `
                <span style="font-size:11px;color:#dc2626;font-weight:600">Inactivo</span>
                <button onclick="activarTipo(${t.id},'${t.label}')"
                    style="background:#dcfce7;border:1.5px solid #86efac;color:#16a34a;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer">Activar</button>
                <button onclick="eliminarTipo(${t.id},'${t.label}')"
                    style="background:#fee2e2;border:1.5px solid #fca5a5;color:#dc2626;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;cursor:pointer">Eliminar</button>
            `}
        </div>`;
    };

    let html = activos.map(renderFila).join('');
    if (inactivos.length) {
        html += `<div style="font-size:12px;font-weight:600;color:#aaa;text-transform:uppercase;letter-spacing:.05em;margin:14px 0 6px">Inactivos</div>`;
        html += inactivos.map(renderFila).join('');
    }
    lista.innerHTML = html;
}

async function activarTipo(id, nombre) {
    const fd = new FormData();
    fd.append('accion', 'activar');
    fd.append('id', id);
    const res = await fetch('ajax/tipos_panel.php', {method:'POST', body:fd}).then(r=>r.json());
    if (res.success) {
        Swal.fire({icon:'success', title:'Tipo activado', text:`"${nombre}" vuelve a estar disponible.`, timer:1400, showConfirmButton:false});
        cargarTiposLista();
        setTimeout(() => location.reload(), 1500);
    } else {
        Swal.fire({icon:'error', title:'Error', text: res.message || 'No se pudo activar', confirmButtonColor:'#c88e99'});
    }
}

async function agregarTipo() {
    const label = document.getElementById('nuevoLabel').value.trim();
    const emoji = document.getElementById('nuevoEmoji').value.trim() || '📌';
    const color = document.getElementById('nuevoColor').value;
    if (!label) {
        Swal.fire({ icon:'warning', title:'Falta el nombre', text:'Escribí un nombre para el tipo', confirmButtonColor:'#c88e99' });
        return;
    }
    const fd = new FormData();
    fd.append('accion', 'guardar');
    fd.append('label', label);
    fd.append('emoji', emoji);
    fd.append('color', color);
    const res = await fetch('ajax/tipos_panel.php', {method:'POST', body:fd}).then(r=>r.json());
    if (res.success) {
        document.getElementById('nuevoLabel').value = '';
        document.getElementById('nuevoEmoji').value = '';
        Swal.fire({ icon:'success', title:'Tipo agregado', timer:1200, showConfirmButton:false });
        cargarTiposLista();
        setTimeout(() => location.reload(), 1400);
    } else {
        Swal.fire({ icon:'error', title:'Error', text: res.message || 'No se pudo guardar', confirmButtonColor:'#c88e99' });
    }
}

async function inactivarTipo(id, nombre) {
    const { isConfirmed } = await Swal.fire({
        icon: 'warning',
        title: '¿Inactivar tipo?',
        html: `El tipo <strong>${nombre}</strong> no aparecerá en el selector pero sus paneles se mantienen.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, inactivar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#aaa',
    });
    if (!isConfirmed) return;
    const fd = new FormData();
    fd.append('accion', 'eliminar'); // soft-delete = inactivar
    fd.append('id', id);
    const res = await fetch('ajax/tipos_panel.php', {method:'POST', body:fd}).then(r=>r.json());
    if (res.success) {
        Swal.fire({ icon:'success', title:'Tipo inactivado', timer:1200, showConfirmButton:false });
        cargarTiposLista();
        setTimeout(() => location.reload(), 1400);
    } else {
        Swal.fire({ icon:'error', title:'No se puede inactivar', text: res.message, confirmButtonColor:'#c88e99' });
    }
}

async function eliminarTipo(id, nombre) {
    const { isConfirmed } = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar tipo?',
        html: `Se eliminará permanentemente <strong>${nombre}</strong>.<br>Solo se puede eliminar si no tiene paneles asociados.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#aaa',
    });
    if (!isConfirmed) return;
    const fd = new FormData();
    fd.append('accion', 'eliminar_hard');
    fd.append('id', id);
    const res = await fetch('ajax/tipos_panel.php', {method:'POST', body:fd}).then(r=>r.json());
    if (res.success) {
        Swal.fire({ icon:'success', title:'Eliminado', timer:1200, showConfirmButton:false });
        cargarTiposLista();
        setTimeout(() => location.reload(), 1400);
    } else {
        Swal.fire({ icon:'error', title:'No se puede eliminar', text: res.message, confirmButtonColor:'#c88e99' });
    }
}
</script>
