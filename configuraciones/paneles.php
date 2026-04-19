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

$TIPOS_PANEL = [
    'promo'      => ['label' => '📢 Promo',      'color' => '#c88e99'],
    'bienvenida' => ['label' => '👋 Bienvenida', 'color' => '#1d9e75'],
    'regalo'     => ['label' => '🎁 Regalo',     'color' => '#7c3aed'],
    'soporte'    => ['label' => '🛟 Soporte',    'color' => '#0891b2'],
    'temporada'  => ['label' => '🌸 Temporada',  'color' => '#f59e0b'],
    'descuento'  => ['label' => '💸 Descuento',  'color' => '#dc2626'],
];
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
        <button class="btn-primary" onclick="openModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nuevo panel
        </button>
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
                <div class="form-group">
                    <label>Tipo oferta</label>
                    <select id="oTipo">
                        <option value="promo">Promoción</option>
                        <option value="descuento">Descuento (%)</option>
                        <option value="temporada">Temporada</option>
                    </select>
                </div>
                <div class="form-group">
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
    document.getElementById('oTipo').value = 'promo';
    document.getElementById('oProducto').value = '';
    document.getElementById('oFechaIni').value = '';
    document.getElementById('oFechaFin').value = '';
    document.getElementById('oActivo').checked = true;
    document.getElementById('oToggleLbl').textContent = 'Activo';
    document.getElementById('previewWrap').style.display = 'none';
    document.getElementById('oImagenActual').value = '';
    document.getElementById('oImagen').value = '';
    document.getElementById('oTipoPanel').value = 'promo';
    document.querySelectorAll('input[name="tipoPanelRadio"]').forEach(r=>r.checked=(r.value==='promo'));
}
document.getElementById('oActivo').addEventListener('change', function(){
    document.getElementById('oToggleLbl').textContent = this.checked ? 'Activo' : 'Inactivo';
});
document.getElementById('oTipo').addEventListener('change', function(){
    document.getElementById('valorLabel').textContent = this.value==='descuento' ? '(%)' : '(opcional)';
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
    document.getElementById('oTipo').value      = o.tipo || 'promo';
    document.getElementById('oValor').value     = o.valor || '';
    document.getElementById('oEmoji').value     = o.emoji || '';
    document.getElementById('oFechaIni').value  = o.fecha_inicio || '';
    document.getElementById('oFechaFin').value  = o.fecha_fin || '';
    document.getElementById('oActivo').checked  = o.activo == 1;
    document.getElementById('oToggleLbl').textContent = o.activo==1?'Activo':'Inactivo';
    document.getElementById('oProducto').value  = o.productos_idproductos || '';
    document.getElementById('oImagenActual').value = o.imagen || '';
    const tp = o.tipo_panel || 'promo';
    document.getElementById('oTipoPanel').value = tp;
    document.querySelectorAll('input[name="tipoPanelRadio"]').forEach(r=>r.checked=(r.value===tp));
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
    if(!titulo){ alert('El título es obligatorio'); return; }
    const btn = document.getElementById('btnGuardar');
    btn.disabled = true; btn.textContent = 'Guardando...';

    const fd = new FormData();
    if(editId) fd.append('id', editId);
    fd.append('titulo',    titulo);
    fd.append('descripcion', document.getElementById('oDesc').value.trim());
    fd.append('tipo',      document.getElementById('oTipo').value);
    fd.append('tipo_panel',document.getElementById('oTipoPanel').value);
    fd.append('valor',     document.getElementById('oValor').value || '');
    fd.append('emoji',     document.getElementById('oEmoji').value || '');
    fd.append('fecha_inicio', document.getElementById('oFechaIni').value || '');
    fd.append('fecha_fin',    document.getElementById('oFechaFin').value || '');
    fd.append('activo',    document.getElementById('oActivo').checked ? 1 : 0);
    fd.append('productos_idproductos', document.getElementById('oProducto').value || '');
    fd.append('imagen_actual', document.getElementById('oImagenActual').value || '');
    const imgFile = document.getElementById('oImagen').files[0];
    if(imgFile) fd.append('imagen', imgFile);

    const res = await fetch('ajax/guardar_oferta.php', {method:'POST', body:fd}).then(r=>r.json()).catch(()=>null);
    btn.disabled = false;
    btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar`;

    if(res?.success){ closeModal(); dt.ajax.reload(); }
    else alert(res?.message || 'Error al guardar');
}

async function eliminar(id){
    if(!confirm('¿Eliminar este panel?')) return;
    const fd = new FormData(); fd.append('id', id);
    const res = await fetch('ajax/eliminar_oferta.php',{method:'POST',body:fd}).then(r=>r.json());
    if(res?.success) dt.ajax.reload();
    else alert(res?.message||'Error');
}
</script>
