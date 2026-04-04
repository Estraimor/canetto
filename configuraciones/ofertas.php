<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../config/conexion.php';
$pageTitle = "Ofertas del Carrusel";
include '../panel/dashboard/layaut/nav.php';

$pdo = Conexion::conectar();

$pdo->exec("CREATE TABLE IF NOT EXISTS `oferta` (
    `idoferta` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(200) NOT NULL,
    `descripcion` TEXT,
    `emoji` VARCHAR(10) DEFAULT '🎉',
    `tipo` VARCHAR(20) DEFAULT 'promo',
    `valor` DECIMAL(10,2) NULL,
    `imagen` VARCHAR(255) NULL,
    `activo` TINYINT DEFAULT 1,
    `fecha_inicio` DATE NULL,
    `fecha_fin` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
try { $pdo->exec("ALTER TABLE oferta ADD COLUMN imagen VARCHAR(255) NULL"); } catch (Throwable $e) {}

$total   = (int)$pdo->query("SELECT COUNT(*) FROM oferta")->fetchColumn();
$activas = (int)$pdo->query("SELECT COUNT(*) FROM oferta WHERE activo=1")->fetchColumn();
?>

<link rel="stylesheet" href="/canetto/configuraciones/cfg.css">
<style>
.of-preview{width:100%;height:160px;object-fit:cover;border-radius:10px;margin-top:8px;display:block}
.of-emoji-big{font-size:48px;text-align:center;padding:20px;background:#fafafa;border-radius:10px;margin-top:8px}
#previewWrap{display:none;margin-top:8px}
.date-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
</style>

<div class="cfg-module">

    <div class="cfg-page-header">
        <div class="cfg-page-header__left">
            <a class="cfg-back" href="/canetto/configuraciones/index.php">
                <i class="fa-solid fa-chevron-left" style="font-size:.6rem"></i> Configuraciones
            </a>
            <div class="cfg-page-title">
                <span>Tienda Online</span>
                Ofertas del Carrusel
            </div>
        </div>
        <button class="btn-primary" onclick="openModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nueva oferta
        </button>
    </div>

    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-card__num" id="statTotal"><?= $total ?></div>
            <div class="stat-card__label">Total ofertas</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-success" id="statActivas"><?= $activas ?></div>
            <div class="stat-card__label">Activas</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__num num-danger" id="statInactivas"><?= $total - $activas ?></div>
            <div class="stat-card__label">Inactivas</div>
        </div>
    </div>

    <div class="table-wrap">
        <table id="tablaOfertas" style="width:100%">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Tipo</th>
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
    <div class="modal" style="max-width:580px;" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalTitle">Nueva oferta</h2>
            <button class="modal-close" onclick="closeModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Título *</label>
                    <input type="text" id="oTitulo" placeholder="Ej: ¡50% OFF en Boxes!">
                </div>
                <div class="form-group full">
                    <label>Descripción</label>
                    <textarea id="oDesc" rows="2" placeholder="Descripción breve de la oferta..." style="resize:vertical"></textarea>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
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
                        <span class="toggle-label" id="oToggleLbl">Activa</span>
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
                        <button type="button" onclick="removeImage()" style="font-size:12px;color:#c0392b;background:none;border:none;cursor:pointer;margin-top:4px">✕ Quitar imagen</button>
                    </div>
                    <p style="font-size:11px;color:#888;margin-top:5px">Si no subís imagen, se usará el emoji como fondo del slide.</p>
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
<script>
let editId = null;
let dt = null;

$(document).ready(function () {
    dt = $('#tablaOfertas').DataTable({
        ajax: {
            url: 'ajax/listar_ofertas.php',
            dataSrc: function(json) {
                const total   = json.length;
                const activas = json.filter(o => o.activo == 1).length;
                document.getElementById('statTotal').textContent     = total;
                document.getElementById('statActivas').textContent   = activas;
                document.getElementById('statInactivas').textContent = total - activas;
                return json;
            }
        },
        columns: [
            { data: null, orderable: false, width: '70px', render: row =>
                row.imagen
                    ? `<img src="/canetto/img/ofertas/${esc(row.imagen)}" style="width:56px;height:40px;object-fit:cover;border-radius:6px">`
                    : `<span style="font-size:26px">${esc(row.emoji||'🎉')}</span>`
            },
            { data: 'titulo', render: v => '<strong>' + esc(v) + '</strong>' },
            { data: 'tipo',   render: v => ({promo:'Promoción',descuento:'Descuento',temporada:'Temporada'}[v]||esc(v)) },
            { data: 'valor',  render: (v,t,row) => v ? (row.tipo==='descuento'?v+'%':'$'+v) : '—' },
            { data: null, render: row => {
                const fi = row.fecha_inicio || '—'; const ff = row.fecha_fin || '—';
                return fi === '—' && ff === '—' ? 'Sin límite' : fi + ' → ' + ff;
            }},
            { data: null, render: row => row.activo == 1
                ? '<span class="badge-activo"><i class="fa-solid fa-circle" style="font-size:.4rem"></i>Activa</span>'
                : '<span class="badge-inactivo"><i class="fa-solid fa-circle" style="font-size:.4rem"></i>Inactiva</span>'
            },
            {
                data: null, orderable: false, width: '160px',
                render: row =>
                    '<div class="actions-cell">' +
                    '<button class="btn-sm" onclick=\'editarRow(' + JSON.stringify(row) + ')\'>' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Editar</button>' +
                    '<button class="btn-sm danger" onclick="confirmarEliminar(' + row.idoferta + ',\'' + esc(row.titulo) + '\')">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Eliminar</button>' +
                    '</div>'
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        pageLength: 15,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: [0,6] }]
    });

    document.getElementById('oActivo').addEventListener('change', function() {
        document.getElementById('oToggleLbl').textContent = this.checked ? 'Activa' : 'Inactiva';
    });
    document.getElementById('oTipo').addEventListener('change', function() {
        document.getElementById('valorLabel').textContent =
            this.value === 'descuento' ? '(porcentaje %)' : '(precio $, opcional)';
    });
    document.getElementById('modalOferta').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
});

function openModal() {
    editId = null;
    document.getElementById('modalTitle').textContent = 'Nueva oferta';
    ['oTitulo','oDesc','oValor','oFechaIni','oFechaFin','oImagenActual'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('oEmoji').value  = '🎉';
    document.getElementById('oTipo').value   = 'promo';
    document.getElementById('oActivo').checked = true;
    document.getElementById('oToggleLbl').textContent = 'Activa';
    document.getElementById('oImagen').value  = '';
    document.getElementById('previewWrap').style.display = 'none';
    document.getElementById('modalOferta').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('oTitulo').focus(), 200);
}

function closeModal() {
    document.getElementById('modalOferta').classList.remove('open');
    document.body.style.overflow = '';
    editId = null;
}

function editarRow(row) {
    editId = row.idoferta;
    document.getElementById('modalTitle').textContent = 'Editar oferta';
    document.getElementById('oTitulo').value    = row.titulo      || '';
    document.getElementById('oDesc').value      = row.descripcion || '';
    document.getElementById('oEmoji').value     = row.emoji       || '🎉';
    document.getElementById('oTipo').value      = row.tipo        || 'promo';
    document.getElementById('oValor').value     = row.valor       || '';
    document.getElementById('oFechaIni').value  = row.fecha_inicio || '';
    document.getElementById('oFechaFin').value  = row.fecha_fin   || '';
    document.getElementById('oImagenActual').value = row.imagen   || '';
    const activo = row.activo == 1;
    document.getElementById('oActivo').checked = activo;
    document.getElementById('oToggleLbl').textContent = activo ? 'Activa' : 'Inactiva';
    document.getElementById('oImagen').value = '';
    if (row.imagen) {
        document.getElementById('imgPreview').src = '/canetto/img/ofertas/' + row.imagen;
        document.getElementById('previewWrap').style.display = 'block';
    } else {
        document.getElementById('previewWrap').style.display = 'none';
    }
    document.getElementById('modalOferta').classList.add('open');
    document.body.style.overflow = 'hidden';
}

async function guardar() {
    const titulo = document.getElementById('oTitulo').value.trim();
    if (!titulo) {
        Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El título es obligatorio.', confirmButtonColor: '#0a0a0a' });
        return;
    }
    const btn = document.getElementById('btnGuardar');
    const orig = btn.innerHTML;
    btn.innerHTML = '<span class="loader"></span>';
    btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('idoferta',    editId || '');
        fd.append('titulo',      titulo);
        fd.append('descripcion', document.getElementById('oDesc').value.trim());
        fd.append('emoji',       document.getElementById('oEmoji').value.trim() || '🎉');
        fd.append('tipo',        document.getElementById('oTipo').value);
        fd.append('valor',       document.getElementById('oValor').value.trim());
        fd.append('fecha_inicio',document.getElementById('oFechaIni').value);
        fd.append('fecha_fin',   document.getElementById('oFechaFin').value);
        fd.append('activo',      document.getElementById('oActivo').checked ? 1 : 0);
        fd.append('imagen_actual', document.getElementById('oImagenActual').value);
        const imgFile = document.getElementById('oImagen').files[0];
        if (imgFile) fd.append('imagen', imgFile);

        const res = await fetch('ajax/guardar_oferta.php', { method: 'POST', body: fd });
        const d   = await res.json();
        if (d.ok) {
            closeModal();
            dt.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: editId ? 'Actualizada' : 'Creada', text: '"' + titulo + '" fue guardada.', confirmButtonColor: '#0a0a0a', timer: 2500, timerProgressBar: true });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: d.msg || 'No se pudo guardar.', confirmButtonColor: '#0a0a0a' });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonColor: '#0a0a0a' });
    } finally {
        btn.innerHTML = orig;
        btn.disabled = false;
    }
}

function confirmarEliminar(id, titulo) {
    Swal.fire({
        title: '¿Eliminar oferta?',
        html: 'Se eliminará <strong>' + esc(titulo) + '</strong>.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#0a0a0a', cancelButtonColor: '#e0e0e0',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    }).then(async r => {
        if (!r.isConfirmed) return;
        try {
            const res = await fetch('ajax/eliminar_oferta.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({idoferta:id}) });
            const d   = await res.json();
            if (d.ok) {
                dt.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Eliminada', confirmButtonColor: '#0a0a0a', timer: 2000, timerProgressBar: true });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: d.msg, confirmButtonColor: '#0a0a0a' });
            }
        } catch(e) { Swal.fire({ icon: 'error', title: 'Error de conexión', confirmButtonColor: '#0a0a0a' }); }
    });
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({ icon: 'warning', title: 'Imagen muy grande', text: 'Máximo 2MB permitido.', confirmButtonColor: '#0a0a0a' });
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('previewWrap').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    document.getElementById('oImagen').value = '';
    document.getElementById('oImagenActual').value = '';
    document.getElementById('imgPreview').src = '';
    document.getElementById('previewWrap').style.display = 'none';
}

function esc(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
