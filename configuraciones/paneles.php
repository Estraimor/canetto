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

$pdo->exec("CREATE TABLE IF NOT EXISTS tipos_panel (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    clave  VARCHAR(40) NOT NULL UNIQUE,
    label  VARCHAR(60) NOT NULL,
    emoji  VARCHAR(8)  NOT NULL DEFAULT '📌',
    color  VARCHAR(20) NOT NULL DEFAULT '#888888',
    activo TINYINT(1)  NOT NULL DEFAULT 1,
    orden  INT         NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

$tiposRows   = $pdo->query("SELECT * FROM tipos_panel WHERE activo=1 ORDER BY orden ASC")->fetchAll(PDO::FETCH_ASSOC);
$TIPOS_PANEL = [];
foreach ($tiposRows as $t) {
    $TIPOS_PANEL[$t['clave']] = ['id' => $t['id'], 'label' => $t['emoji'] . ' ' . $t['label'], 'color' => $t['color'], 'emoji' => $t['emoji']];
}

// Tipos que usan precio/producto
$TIPOS_CON_PRECIO_PHP = ['promo', 'descuento', 'temporada'];
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<link rel="stylesheet" href="<?= URL_ASSETS ?>/configuraciones/cfg.css">
<style>
/* ══════════════════════════════════════════
   MODAL PANELES — 2 columnas
══════════════════════════════════════════ */
.pnl-modal { max-width: 1080px !important; width: 96vw !important; display: flex !important; flex-direction: column !important; max-height: 92vh !important; }
.pnl-modal > .modal-header { flex-shrink: 0; }
.pnl-modal > .modal-body {
  padding: 0;
  display: grid;
  grid-template-columns: 1fr 300px;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}
.pnl-modal > .modal-footer { flex-shrink: 0; }
.pnl-modal-form { overflow-y: auto; border-right: 1px solid #f0f0f0; }
.pnl-modal-prev { overflow-y: auto; background: #f8f7f6; }

@media (max-width: 700px) {
  .pnl-modal > .modal-body { grid-template-columns: 1fr; }
  .pnl-modal-prev { display: none; }
}

/* Secciones */
.pnl-sec { padding: 16px 22px; border-bottom: 1px solid #f0f0f0; }
.pnl-sec:last-child { border-bottom: none; }
.pnl-sec-hd {
  font-size: 10px; font-weight: 800; text-transform: uppercase;
  letter-spacing: .08em; color: #bbb; margin-bottom: 10px;
}

/* Grids */
.pnl-row-2   { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.pnl-row-3   { display: grid; grid-template-columns: 150px 1fr 1fr; gap: 12px; }
@media (max-width: 640px) {
  .pnl-row-2,.pnl-row-3 { grid-template-columns: 1fr; }
}

/* Tipo de panel */
.panel-tipo-grid {
  display: grid; grid-template-columns: repeat(4,1fr); gap: 7px; margin-top: 4px;
}
@media (max-width: 640px) { .panel-tipo-grid { grid-template-columns: repeat(3,1fr); } }
.panel-tipo-opt input { display: none; }
.panel-tipo-badge {
  display: block; padding: 8px 4px; border-radius: 9px;
  font-size: 11px; font-weight: 700; text-align: center;
  border: 2px solid transparent; background: #f5f5f5; color: #444;
  transition: all .15s; cursor: pointer;
}
.panel-tipo-opt input:checked + .panel-tipo-badge {
  border-color: currentColor; background: rgba(200,142,153,.07);
  box-shadow: 0 0 0 3px rgba(200,142,153,.12);
}

/* Presets enlace */
.btn-link-preset {
  padding: 5px 11px; border-radius: 20px; border: 1.5px solid #e0e0e0;
  background: #f8f8f8; color: #444; font-size: 12px; font-weight: 600;
  cursor: pointer; font-family: inherit; transition: all .15s;
}
.btn-link-preset:hover { background: #f0e8ea; border-color: #c88e99; color: #c88e99; }

/* Imagen preview (upload) */
.of-preview { width:100%;height:120px;object-fit:cover;border-radius:8px;margin-top:6px;display:block; }
#previewWrap { display:none; margin-top:6px; }

/* Notificación push */
.pnl-sec-notif { background: linear-gradient(135deg, #fdf8f9 0%, #f5f0ff 100%); }
.pnl-notif-toggle {
  display: flex; align-items: flex-start; gap: 12px; cursor: pointer;
  padding: 12px 14px; border-radius: 12px; border: 1.5px solid #e8dff0;
  background: #fff; transition: border-color .15s;
}
.pnl-notif-toggle:has(input:checked) { border-color: #c88e99; background: #fdf5f7; }
.pnl-notif-txt-title { font-size: 13px; font-weight: 700; color: #333; }
.pnl-notif-txt-sub   { font-size: 11px; color: #888; margin-top: 2px; line-height: 1.4; }
.pnl-notif-badge {
  display: inline-flex; align-items: center; gap: 4px; margin-top: 5px;
  padding: 2px 9px; border-radius: 20px; background: #f0e8ea;
  color: #c88e99; font-size: 10px; font-weight: 700;
}

/* ── VISTA PREVIA: estilos del carrusel real de la tienda (scoped) ── */
.tienda-sim { border-radius: 14px; overflow: hidden; }
.tienda-sim .swiper-slide {
  position: relative; height: 240px; overflow: hidden;
  display: flex; align-items: flex-end;
}
.tienda-sim .slide-bg {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center; font-size: 80px;
  background-size: cover; background-position: center;
}
.tienda-sim .slide-bg-0 { background: linear-gradient(135deg, #c88e99, #9c27b0); }
.tienda-sim .slide-bg-1 { background: linear-gradient(135deg, #111, #a46678); }
.tienda-sim .slide-bg-2 { background: linear-gradient(135deg, #ff6d00, #ffca28); }
.tienda-sim .slide-bg-3 { background: linear-gradient(135deg, #1d9e75, #0288d1); }
.tienda-sim .slide-content {
  position: relative; z-index: 2; padding: 22px 20px; width: 100%;
  background: linear-gradient(to top, rgba(0,0,0,.65) 0%, transparent 100%);
  color: #fff;
}
.tienda-sim .slide-tag {
  display: inline-block; background: rgba(255,255,255,.2);
  border: 1px solid rgba(255,255,255,.3); color: #fff;
  font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
  padding: 3px 10px; border-radius: 20px; margin-bottom: 8px; backdrop-filter: blur(4px);
}
.tienda-sim .slide-title {
  font-size: 22px; font-weight: 800; line-height: 1.2; margin-bottom: 4px;
}
.tienda-sim .slide-desc  { font-size: 14px; opacity: .85; }
.tienda-sim .slide-valor { font-size: 15px; font-weight: 800; opacity: 1; margin-top: 4px; }
.tienda-sim .slide-cart-btn {
  margin-top: 10px; background: rgba(255,255,255,.95); color: #0a0a0a;
  border: none; border-radius: 20px; padding: 8px 18px; font-size: 13px; font-weight: 700;
  display: inline-flex; align-items: center; gap: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,.18); backdrop-filter: blur(4px); cursor: default;
}
.tienda-sim .swiper-pagination-bullet { background: rgba(255,255,255,.5) !important; }
.tienda-sim .swiper-pagination-bullet-active { background: #fff !important; }

/* Preview col (derecha del modal) */
.pnl-prev-wrap { padding: 16px; display: flex; flex-direction: column; gap: 10px; }
.pnl-prev-label {
  font-size: 10px; font-weight: 800; text-transform: uppercase;
  letter-spacing: .08em; color: #bbb;
  display: flex; align-items: center; gap: 5px;
}
.pnl-prev-placeholder {
  height: 200px; border: 2px dashed #e5e5e5; border-radius: 12px;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  color: #ccc; font-size: 12px; gap: 8px;
}
.pnl-prev-note { font-size: 10px; color: #bbb; text-align: center; line-height: 1.5; }

/* Lista compacta bajo el carrusel (ver todos) */
.sim-panel-row {
  display: flex; align-items: center; gap: 10px; padding: 9px 14px;
  border-radius: 10px; background: #fafafa; margin-bottom: 6px;
}
.sim-panel-row.inactive { opacity: .5; }
.sim-panel-row-img {
  width: 42px; height: 32px; border-radius: 7px; overflow: hidden;
  flex-shrink: 0; display: flex; align-items: center; justify-content: center;
  font-size: 18px;
}
.sim-panel-row-img img { width: 100%; height: 100%; object-fit: cover; }

/* Filtros */
.cfg-filter-tag {
  padding: 6px 14px; border-radius: 20px; border: 1.5px solid #e0e0e0; background: #fff;
  color: #666; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .15s; font-family: inherit;
}
.cfg-filter-tag.on,.cfg-filter-tag:hover { background: #111; border-color: #111; color: #fff; }

/* Botón ojo en tabla */
.btn-eye {
  padding: 5px 10px; border-radius: 7px; border: 1.5px solid #e0e0e0;
  background: #f8f8f8; color: #555; font-size: 13px; cursor: pointer; transition: all .15s;
}
.btn-eye:hover { background: #eff6ff; border-color: #60a5fa; color: #2563eb; }
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
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn-sm" onclick="verTodosPanel()" title="Ver todos los paneles en modo carrusel">
        <i class="fa-solid fa-eye"></i> Ver carrusel
      </button>
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
          <th>Panel</th>
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

<!-- ══════════════════════════════════════════
     MODAL CREAR / EDITAR PANEL
══════════════════════════════════════════ -->
<div class="modal-overlay" id="modalOferta">
  <div class="modal pnl-modal" role="dialog" aria-modal="true">

    <div class="modal-header">
      <h2 id="modalTitle">Nuevo panel</h2>
      <button class="modal-close" onclick="closeModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <div class="modal-body">

      <!-- Columna izquierda: formulario -->
      <div class="pnl-modal-form">

        <!-- ① Tipo de panel -->
        <div class="pnl-sec">
          <div class="pnl-sec-hd">① Tipo de panel</div>
          <div class="panel-tipo-grid">
            <?php foreach($TIPOS_PANEL as $key=>$tp): ?>
            <label class="panel-tipo-opt">
              <input type="radio" name="tipoPanelRadio" value="<?= $key ?>"
                     <?= $key==='promo'?'checked':'' ?>
                     onchange="onTipoPanelChange(this.value)">
              <span class="panel-tipo-badge" style="color:<?= $tp['color'] ?>"><?= $tp['label'] ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="oTipoPanel" value="promo">
        </div>

        <!-- ② Contenido -->
        <div class="pnl-sec">
          <div class="pnl-sec-hd">② Contenido</div>
          <div class="pnl-row-2" style="margin-bottom:10px">
            <div class="form-group" style="margin:0">
              <label>Título *</label>
              <input type="text" id="oTitulo" placeholder="Ej: ¡Bienvenida a Canetto!" oninput="updatePreview()">
            </div>
            <div class="form-group" style="margin:0">
              <label>Emoji del slide</label>
              <input type="text" id="oEmoji" maxlength="8" placeholder="🎉" style="font-size:20px" oninput="updatePreview()">
            </div>
          </div>
          <div class="form-group" style="margin:0">
            <label>Descripción</label>
            <textarea id="oDesc" rows="2" placeholder="Descripción breve del panel..." style="resize:vertical" oninput="updatePreview()"></textarea>
          </div>
        </div>

        <!-- ③ Producto y valor (solo tipos con precio) -->
        <div class="pnl-sec" id="secProductoValor">
          <div class="pnl-sec-hd">③ Producto y valor</div>
          <div class="pnl-row-2">
            <div class="form-group" style="margin:0">
              <label>Producto vinculado <span style="font-size:11px;color:#bbb">(opcional)</span></label>
              <select id="oProducto" onchange="onProductoChange(this);updatePreview()">
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
            <div class="form-group" style="margin:0">
              <label>Valor <span id="valorLabel" style="font-size:11px;color:#bbb">(opcional)</span></label>
              <input type="number" id="oValor" min="0" step="0.01" placeholder="0" oninput="updatePreview()">
            </div>
          </div>
        </div>

        <!-- ④ Configuración -->
        <div class="pnl-sec">
          <div class="pnl-sec-hd">④ Configuración</div>
          <div class="pnl-row-3">
            <div class="form-group" style="margin:0">
              <label>Estado</label>
              <div class="toggle-wrap">
                <label class="toggle">
                  <input type="checkbox" id="oActivo" checked>
                  <span class="toggle-slider"></span>
                </label>
                <span class="toggle-label" id="oToggleLbl">Activo</span>
              </div>
            </div>
            <div class="form-group" style="margin:0">
              <label>Fecha inicio <span style="font-size:11px;color:#bbb">(opcional)</span></label>
              <input type="date" id="oFechaIni">
            </div>
            <div class="form-group" style="margin:0">
              <label>Fecha fin <span style="font-size:11px;color:#bbb">(opcional)</span></label>
              <input type="date" id="oFechaFin">
            </div>
          </div>
        </div>

        <!-- ⑤ Enlace y botón -->
        <div class="pnl-sec">
          <div class="pnl-sec-hd">⑤ Enlace y botón</div>
          <div class="pnl-row-2">
            <div class="form-group" style="margin:0">
              <label>Enlace / Contacto <span style="font-size:11px;color:#bbb">(URL, WhatsApp...)</span></label>
              <input type="text" id="oLink" placeholder="https://wa.me/549..." oninput="actualizarLinkPreview();updatePreview()">
              <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" class="btn-link-preset" onclick="setLinkPreset('wa')">💬 WhatsApp</button>
                <button type="button" class="btn-link-preset" onclick="setLinkPreset('ig')">📸 Instagram</button>
                <button type="button" class="btn-link-preset" onclick="setLinkPreset('tel')">📞 Teléfono</button>
              </div>
              <div id="linkPreview" style="margin-top:6px;font-size:12px;color:#64748b;display:none"></div>
            </div>
            <div class="form-group" style="margin:0">
              <label>Texto del botón <span style="font-size:11px;color:#bbb">(si hay enlace)</span></label>
              <input type="text" id="oBtnTxt" placeholder="Ver más, Pedir ahora..." oninput="updatePreview()">
            </div>
          </div>
        </div>

        <!-- ⑥ Imagen -->
        <div class="pnl-sec">
          <div class="pnl-sec-hd">⑥ Imagen del slide <span style="text-transform:none;letter-spacing:0;font-size:10px;color:#ccc">(JPG/PNG/WebP, máx 2MB)</span></div>
          <input type="file" id="oImagen" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this)">
          <input type="hidden" id="oImagenActual">
          <div id="previewWrap">
            <img id="imgPreview" class="of-preview" src="" alt="Preview">
            <button type="button" onclick="removeImage()" style="font-size:12px;color:#c88e99;background:none;border:none;cursor:pointer;margin-top:4px">✕ Quitar imagen</button>
          </div>
        </div>

        <!-- ⑦ Notificación push -->
        <div class="pnl-sec pnl-sec-notif">
          <div class="pnl-sec-hd">⑦ Notificación push</div>
          <label class="pnl-notif-toggle">
            <div class="toggle">
              <input type="checkbox" id="oEnviarNotif">
              <span class="toggle-slider"></span>
            </div>
            <div>
              <div class="pnl-notif-txt-title">Notificar a todos los clientes</div>
              <div class="pnl-notif-txt-sub">Envía push a todos los usuarios que tienen notificaciones activadas en su dispositivo.</div>
              <div class="pnl-notif-badge"><i class="fa-solid fa-bell" style="font-size:10px"></i> Solo llega a quienes aceptaron notificaciones</div>
            </div>
          </label>
        </div>

      </div><!-- /pnl-modal-form -->

      <!-- Columna derecha: vista previa exacta del carrusel -->
      <div class="pnl-modal-prev">
        <div class="pnl-prev-wrap">
          <div class="pnl-prev-label">
            <i class="fa-solid fa-eye" style="font-size:10px"></i> Así se ve en la tienda
          </div>
          <div class="pnl-prev-placeholder" id="prevPlaceholder">
            <i class="fa-solid fa-image" style="font-size:28px"></i>
            <span>Completá el título<br>para ver la preview</span>
          </div>
          <!-- Slide real con los estilos de la tienda -->
          <div class="tienda-sim" id="prevCard" style="display:none">
            <div class="swiper-slide" id="prevSlide" style="height:220px;border-radius:14px">
              <div class="slide-bg slide-bg-0" id="prevBg">🍪</div>
              <div class="slide-content">
                <span class="slide-tag" id="prevTag">Canetto</span>
                <div class="slide-title" id="prevTitle" style="font-size:18px">Título</div>
                <div class="slide-desc" id="prevDesc" style="display:none"></div>
                <div class="slide-valor slide-desc" id="prevValor" style="display:none"></div>
                <span class="slide-cart-btn" id="prevBtn" style="display:none">Ver más</span>
              </div>
            </div>
          </div>
          <p class="pnl-prev-note">Preview idéntica al carrusel real de la tienda</p>
        </div>
      </div><!-- /pnl-modal-prev -->

    </div><!-- /modal-body -->

    <div class="modal-footer">
      <button class="btn-sm" onclick="closeModal()">Cancelar</button>
      <button class="btn-primary" id="btnGuardar" onclick="guardar()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
          <polyline points="17 21 17 13 7 13 7 21"/>
          <polyline points="7 3 7 8 15 8"/>
        </svg>
        Guardar
      </button>
    </div>

  </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL VER PANEL (individual)
══════════════════════════════════════════ -->
<div class="modal-overlay" id="modalVistaPanel">
  <div class="modal" style="max-width:540px" role="dialog" aria-modal="true">
    <div class="modal-header">
      <h2>Vista del panel</h2>
      <button class="modal-close" onclick="document.getElementById('modalVistaPanel').classList.remove('open')">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body" style="padding:0">
      <!-- Slide real en tamaño grande -->
      <div class="tienda-sim" style="border-radius:0 0 0 0">
        <div id="vistaPanelSlide" class="swiper-slide" style="height:320px">
          <!-- Contenido inyectado por JS -->
        </div>
      </div>
      <!-- Info extra debajo -->
      <div id="vistaPanelMeta" style="padding:16px 20px;border-top:1px solid #f5f5f5"></div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════
     MODAL VER TODOS LOS PANELES (carrusel)
══════════════════════════════════════════ -->
<div class="modal-overlay" id="modalVistaTodos">
  <div class="modal" style="max-width:860px;width:96vw" role="dialog" aria-modal="true">
    <div class="modal-header">
      <h2><i class="fa-solid fa-rectangle-history" style="color:#c88e99;margin-right:6px"></i> Vista del carrusel</h2>
      <div style="display:flex;align-items:center;gap:10px">
        <label style="font-size:12px;color:#888;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px">
          <input type="checkbox" id="verSoloActivos" onchange="renderVistaTodos()"> Solo activos
        </label>
        <button class="modal-close" onclick="document.getElementById('modalVistaTodos').classList.remove('open')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
    </div>
    <div class="modal-body" style="padding:0;max-height:82vh;overflow-y:auto">
      <!-- Carrusel exacto de la tienda -->
      <div class="tienda-sim" style="border-radius:0" id="vistaTodosSimWrap">
        <div class="swiper" id="vistaTodosSwiper">
          <div class="swiper-wrapper" id="vistaTodosSwiperInner"></div>
          <div class="swiper-pagination"></div>
        </div>
      </div>
      <!-- Lista de todos los paneles debajo -->
      <div style="padding:16px 20px">
        <div id="vistaTodosLista"></div>
      </div>
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
      <div id="tiposLista" style="display:flex;flex-direction:column;gap:8px;margin-bottom:20px"></div>
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

<script>
const TIPOS_PANEL_JS   = <?= json_encode($TIPOS_PANEL, JSON_UNESCAPED_UNICODE) ?>;
const ASSETS_URL       = '<?= URL_ASSETS ?>';
// Tipos que muestran campo de precio/producto
const TIPOS_CON_PRECIO = ['promo','descuento','temporada'];

let editId = null, dt = null, filtroTipoActivo = '', allPaneles = [];

// ── DataTable ──────────────────────────────────────────────────────────────
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
        allPaneles = json;
        const data = filtroTipoActivo ? json.filter(o=>o.tipo_panel===filtroTipoActivo) : json;
        const act  = data.filter(o=>o.activo==1).length;
        document.getElementById('statTotal').textContent     = data.length;
        document.getElementById('statActivas').textContent   = act;
        document.getElementById('statInactivas').textContent = data.length - act;
        return data;
      }
    },
    columns: [
      { data: null, orderable: false, render: row => {
          const img = row.imagen
            ? `<img src="${ASSETS_URL}/img/ofertas/${esc(row.imagen)}" style="width:48px;height:36px;object-fit:cover;border-radius:6px;margin-right:8px">`
            : `<span style="font-size:24px;margin-right:8px">${esc(row.emoji||'🎉')}</span>`;
          return `<div style="display:flex;align-items:center">
                    ${img}
                    <div>
                      <strong>${esc(row.titulo)}</strong>
                      ${row.descripcion ? `<div style="font-size:11px;color:#888;margin-top:2px;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(row.descripcion)}</div>` : ''}
                    </div>
                  </div>`;
      }},
      { data: 'tipo_panel', render: v => {
          const tp = TIPOS_PANEL_JS[v] || {label:v||'—',color:'#888'};
          return `<span style="background:#f5f5f5;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;color:${tp.color}">${tp.label}</span>`;
      }},
      { data: null, render: row => {
          if (!row.valor) return '<span style="color:#ccc">—</span>';
          return row.tipo==='descuento'
            ? `<strong style="color:#dc2626">${row.valor}% OFF</strong>`
            : `<strong style="color:#c88e99">$${row.valor}</strong>`;
      }},
      { data: null, render: row => {
          const fi=row.fecha_inicio||'—', ff=row.fecha_fin||'—';
          if(fi==='—'&&ff==='—') return '<span style="color:#bbb;font-size:12px">Sin límite</span>';
          return `<span style="font-size:12px;color:#64748b">${fi} → ${ff}</span>`;
      }},
      { data: 'activo', render: v => v==1
          ? '<span class="badge badge-success">Activo</span>'
          : '<span class="badge badge-danger">Inactivo</span>'
      },
      { data: null, orderable: false, render: row =>
          `<div style="display:flex;gap:5px">
             <button class="btn-eye" onclick="verPanel(${row.idoferta})" title="Vista previa"><i class="fa-solid fa-eye"></i></button>
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

// ── Modal abrir/cerrar ────────────────────────────────────────────────────
function openModal(){
  document.getElementById('modalOferta').classList.add('open');
  updatePreview();
}
function closeModal(){
  document.getElementById('modalOferta').classList.remove('open');
  editId = null;
  document.getElementById('modalTitle').textContent = 'Nuevo panel';
  ['oTitulo','oDesc','oValor','oEmoji','oFechaIni','oFechaFin','oLink','oBtnTxt'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('valorLabel').textContent = '(opcional)';
  document.getElementById('oProducto').value   = '';
  document.getElementById('oActivo').checked   = true;
  document.getElementById('oToggleLbl').textContent = 'Activo';
  document.getElementById('previewWrap').style.display = 'none';
  document.getElementById('oImagenActual').value = '';
  document.getElementById('oImagen').value       = '';
  document.getElementById('linkPreview').style.display = 'none';
  document.getElementById('oTipoPanel').value = 'promo';
  document.getElementById('oEnviarNotif').checked = false;
  document.querySelectorAll('input[name="tipoPanelRadio"]').forEach(r=>r.checked=(r.value==='promo'));
  onTipoPanelChange('promo', false);
}

document.getElementById('oActivo').addEventListener('change', function(){
  document.getElementById('oToggleLbl').textContent = this.checked ? 'Activo' : 'Inactivo';
});

// ── Tipo de panel: campos condicionales + preview ─────────────────────────
function onTipoPanelChange(val, doPreview = true) {
  document.getElementById('oTipoPanel').value = val;
  const conPrecio = TIPOS_CON_PRECIO.includes(val);
  const sec = document.getElementById('secProductoValor');
  sec.style.display = conPrecio ? '' : 'none';
  document.getElementById('valorLabel').textContent = val === 'descuento' ? '(%)' : '(opcional)';
  if (doPreview) updatePreview();
}

// Enganchar radios
document.querySelectorAll('input[name="tipoPanelRadio"]').forEach(r => {
  r.addEventListener('change', () => onTipoPanelChange(r.value));
});

// ── Vista previa en tiempo real (estilo carrusel real) ────────────────────
function updatePreview() {
  const titulo  = document.getElementById('oTitulo').value.trim();
  const desc    = document.getElementById('oDesc').value.trim();
  const emoji   = document.getElementById('oEmoji').value.trim() || '🍪';
  const valor   = document.getElementById('oValor').value.trim();
  const btnTxt  = document.getElementById('oBtnTxt').value.trim();
  const link    = document.getElementById('oLink').value.trim();
  const tipo    = document.getElementById('oTipoPanel').value;
  const imgSrc  = document.getElementById('previewWrap').style.display !== 'none'
                    ? document.getElementById('imgPreview').src : null;
  const imgAct  = document.getElementById('oImagenActual').value;

  const placeholder = document.getElementById('prevPlaceholder');
  const card        = document.getElementById('prevCard');

  if (!titulo) {
    placeholder.style.display = ''; card.style.display = 'none'; return;
  }
  placeholder.style.display = 'none'; card.style.display = '';

  // Fondo (imagen real o gradiente)
  const prevBg = document.getElementById('prevBg');
  prevBg.className = 'slide-bg'; // reset
  if (imgSrc) {
    prevBg.style.background = `url('${imgSrc}') center/cover no-repeat`;
    prevBg.textContent = '';
  } else if (imgAct && imgAct !== '__remove__') {
    prevBg.style.background = `url('${ASSETS_URL}/img/ofertas/${imgAct}') center/cover no-repeat`;
    prevBg.textContent = '';
  } else {
    prevBg.style.background = '';
    prevBg.className = 'slide-bg slide-bg-0';
    prevBg.textContent = emoji;
  }

  // Tag
  document.getElementById('prevTag').textContent = TAG_LABELS[tipo] || 'Canetto';

  // Título
  document.getElementById('prevTitle').textContent = titulo;

  // Descripción
  const prevDesc = document.getElementById('prevDesc');
  if (desc) { prevDesc.textContent = desc; prevDesc.style.display = ''; }
  else       { prevDesc.style.display = 'none'; }

  // Valor/precio
  const prevValor = document.getElementById('prevValor');
  if (TIPOS_CON_PRECIO.includes(tipo) && valor) {
    prevValor.textContent = tipo === 'descuento' ? valor + '% OFF' : '$' + valor;
    prevValor.style.display = '';
  } else {
    prevValor.style.display = 'none';
  }

  // Botón CTA
  const prevBtn = document.getElementById('prevBtn');
  if (link || btnTxt) { prevBtn.textContent = btnTxt || 'Ver más'; prevBtn.style.display = ''; }
  else                 { prevBtn.style.display = 'none'; }
}

// ── Imagen upload ──────────────────────────────────────────────────────────
function previewImage(input){
  if(input.files && input.files[0]){
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('imgPreview').src = e.target.result;
      document.getElementById('previewWrap').style.display = 'block';
      updatePreview();
    };
    reader.readAsDataURL(input.files[0]);
  }
}
function removeImage(){
  document.getElementById('oImagen').value = '';
  document.getElementById('oImagenActual').value = '__remove__';
  document.getElementById('previewWrap').style.display = 'none';
  updatePreview();
}

// ── Presets de enlace ──────────────────────────────────────────────────────
function setLinkPreset(tipo) {
  const inp = document.getElementById('oLink');
  if(tipo==='wa')  inp.value = 'https://wa.me/549';
  if(tipo==='ig')  inp.value = 'https://instagram.com/';
  if(tipo==='tel') inp.value = 'tel:+549';
  inp.focus(); inp.setSelectionRange(inp.value.length, inp.value.length);
  actualizarLinkPreview(); updatePreview();
}
function actualizarLinkPreview() {
  const v   = document.getElementById('oLink').value.trim();
  const pre = document.getElementById('linkPreview');
  if (!v) { pre.style.display='none'; return; }
  let label = '🔗 ' + v;
  if(v.includes('wa.me'))        label = '💬 WhatsApp: ' + v;
  else if(v.includes('instagram')) label = '📸 Instagram: ' + v;
  else if(v.startsWith('tel:'))  label = '📞 Teléfono: ' + v.replace('tel:','');
  pre.textContent = label; pre.style.display = 'block';
}

// ── Producto autocompletado ────────────────────────────────────────────────
function onProductoChange(sel){
  const opt = sel.options[sel.selectedIndex];
  if(!opt.value) return;
  if(!document.getElementById('oTitulo').value) document.getElementById('oTitulo').value = opt.dataset.nombre;
  if(opt.dataset.tipo !== 'box' && !document.getElementById('oValor').value) document.getElementById('oValor').value = opt.dataset.precio;
}

// ── Editar panel ───────────────────────────────────────────────────────────
async function editar(id){
  const res = await fetch('ajax/listar_ofertas.php').then(r=>r.json());
  const o   = res.find(x=>x.idoferta==id);
  if(!o) return;
  editId = id;
  document.getElementById('modalTitle').textContent = 'Editar panel';
  document.getElementById('oTitulo').value    = o.titulo||'';
  document.getElementById('oDesc').value      = o.descripcion||'';
  document.getElementById('oValor').value     = o.valor||'';
  document.getElementById('oEmoji').value     = o.emoji||'';
  document.getElementById('oFechaIni').value  = o.fecha_inicio||'';
  document.getElementById('oFechaFin').value  = o.fecha_fin||'';
  document.getElementById('oActivo').checked  = o.activo==1;
  document.getElementById('oToggleLbl').textContent = o.activo==1?'Activo':'Inactivo';
  document.getElementById('oProducto').value  = o.productos_idproductos||'';
  document.getElementById('oImagenActual').value = o.imagen||'';
  document.getElementById('oLink').value   = o.link||'';
  document.getElementById('oBtnTxt').value = o.btn_txt||'';
  actualizarLinkPreview();
  const tp = o.tipo_panel||'promo';
  document.getElementById('oTipoPanel').value = tp;
  document.querySelectorAll('input[name="tipoPanelRadio"]').forEach(r=>r.checked=(r.value===tp));
  onTipoPanelChange(tp, false);
  if(o.imagen){
    document.getElementById('imgPreview').src = `${ASSETS_URL}/img/ofertas/${o.imagen}`;
    document.getElementById('previewWrap').style.display='block';
  } else {
    document.getElementById('previewWrap').style.display='none';
  }
  openModal();
}

// ── Guardar panel ──────────────────────────────────────────────────────────
async function guardar(){
  const titulo = document.getElementById('oTitulo').value.trim();
  if(!titulo){ Swal.fire({icon:'warning',title:'Falta el título',text:'El título es obligatorio',confirmButtonColor:'#c88e99'}); return; }
  const btn = document.getElementById('btnGuardar');
  btn.disabled=true; btn.textContent='Guardando...';

  const fd = new FormData();
  if(editId) fd.append('idoferta', editId);
  const tipoPanel  = document.getElementById('oTipoPanel').value;
  const tipoMap    = {descuento:'descuento',temporada:'temporada'};
  const tipoOferta = tipoMap[tipoPanel]||'promo';
  fd.append('titulo',       titulo);
  fd.append('descripcion',  document.getElementById('oDesc').value.trim());
  fd.append('tipo',         tipoOferta);
  fd.append('tipo_panel',   tipoPanel);
  fd.append('valor',        document.getElementById('oValor').value||'');
  fd.append('emoji',        document.getElementById('oEmoji').value||'');
  fd.append('fecha_inicio', document.getElementById('oFechaIni').value||'');
  fd.append('fecha_fin',    document.getElementById('oFechaFin').value||'');
  fd.append('activo',       document.getElementById('oActivo').checked?1:0);
  fd.append('productos_idproductos', document.getElementById('oProducto').value||'');
  fd.append('imagen_actual', document.getElementById('oImagenActual').value||'');
  fd.append('link',    document.getElementById('oLink').value.trim()||'');
  fd.append('btn_txt', document.getElementById('oBtnTxt').value.trim()||'');
  const imgFile = document.getElementById('oImagen').files[0];
  if(imgFile) fd.append('imagen', imgFile);

  const res = await fetch('ajax/guardar_oferta.php',{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
  btn.disabled=false;
  btn.innerHTML=`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="15" height="15"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Guardar`;

  if(res?.ok){
    const idGuardado  = res.idoferta || editId;
    const enviarNotif = document.getElementById('oEnviarNotif').checked;
    closeModal(); dt.ajax.reload();

    if(enviarNotif && idGuardado){
      const fdN = new FormData(); fdN.append('idoferta', idGuardado);
      const rn  = await fetch('ajax/notificar_panel.php',{method:'POST',body:fdN}).then(r=>r.json()).catch(()=>null);
      const enviados = rn?.sent ?? 0;
      Swal.fire({
        icon:'success', title:'Panel guardado',
        html: enviados>0
          ? `✅ Guardado correctamente<br><br><span style="font-size:13px;color:#555">🔔 Notificación enviada a <strong>${enviados}</strong> dispositivo${enviados!==1?'s':''}</span>`
          : `✅ Guardado correctamente<br><br><span style="font-size:12px;color:#aaa">Sin dispositivos suscritos aún</span>`,
        confirmButtonColor:'#c88e99',
      });
    } else {
      Swal.fire({icon:'success',title:'Guardado',timer:1200,showConfirmButton:false});
    }
  } else {
    Swal.fire({icon:'error',title:'Error',text:res?.msg||'Error al guardar',confirmButtonColor:'#c88e99'});
  }
}

// ── Eliminar panel ─────────────────────────────────────────────────────────
async function eliminar(id){
  const { isConfirmed } = await Swal.fire({
    icon:'warning', title:'¿Eliminar panel?', text:'Esta acción no se puede deshacer.',
    showCancelButton:true, confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar',
    confirmButtonColor:'#dc2626', cancelButtonColor:'#aaa'
  });
  if(!isConfirmed) return;
  const fd=new FormData(); fd.append('id',id);
  const res=await fetch('ajax/eliminar_oferta.php',{method:'POST',body:fd}).then(r=>r.json());
  if(res?.ok){
    dt.ajax.reload();
    Swal.fire({icon:'success',title:'Eliminado',timer:1000,showConfirmButton:false});
  } else {
    Swal.fire({icon:'error',title:'Error',text:res?.msg||'No se pudo eliminar',confirmButtonColor:'#c88e99'});
  }
}

// ── Helpers del carrusel real ──────────────────────────────────────────────
const BG_CLASSES  = ['slide-bg-0','slide-bg-1','slide-bg-2','slide-bg-3'];
const TAG_LABELS  = {promo:'Canetto', descuento:'Descuento', temporada:'Temporada'};

function buildSlideInner(o, idx) {
  const bgHtml = o.imagen
    ? `<div class="slide-bg" style="background:url('${ASSETS_URL}/img/ofertas/${esc(o.imagen)}') center/cover no-repeat"></div>`
    : `<div class="slide-bg ${BG_CLASSES[idx % 4]}">${esc(o.emoji||'🍪')}</div>`;
  const tag    = TAG_LABELS[o.tipo] || (TIPOS_PANEL_JS[o.tipo_panel]?.label?.replace(/^.+ /,'') || 'Canetto');
  const desc   = o.descripcion ? `<div class="slide-desc">${esc(o.descripcion)}</div>` : '';
  const valor  = (TIPOS_CON_PRECIO.includes(o.tipo_panel) && o.valor)
    ? `<div class="slide-desc slide-valor">${o.tipo==='descuento'?o.valor+'% OFF':'$'+o.valor}</div>` : '';
  const btn    = (o.link||o.btn_txt) ? `<span class="slide-cart-btn">${esc(o.btn_txt||'Ver más')}</span>` : '';
  const inactive = o.activo==0
    ? `<div style="position:absolute;inset:0;background:rgba(0,0,0,.45);z-index:3;display:flex;align-items:center;justify-content:center">
         <span style="background:#dc2626;color:#fff;padding:5px 16px;border-radius:20px;font-size:13px;font-weight:800">INACTIVO</span>
       </div>` : '';
  return `${bgHtml}${inactive}
    <div class="slide-content">
      <span class="slide-tag">${esc(tag)}</span>
      <div class="slide-title">${esc(o.titulo)}</div>
      ${desc}${valor}${btn}
    </div>`;
}

// ── VER PANEL individual ───────────────────────────────────────────────────
async function verPanel(id){
  const all = allPanelesCache || await fetch('ajax/listar_ofertas.php').then(r=>r.json());
  const o   = all.find(x=>x.idoferta==id);
  if(!o) return;
  const idx = all.indexOf(o);

  document.getElementById('vistaPanelSlide').innerHTML = buildSlideInner(o, idx);

  const tp = TIPOS_PANEL_JS[o.tipo_panel] || {label:'—',color:'#888'};
  const fi = o.fecha_inicio||'', ff = o.fecha_fin||'';
  document.getElementById('vistaPanelMeta').innerHTML = `
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
      <span style="background:${tp.color}20;color:${tp.color};padding:3px 10px;border-radius:20px;font-size:12px;font-weight:800">${tp.label}</span>
      ${o.activo==1 ? '<span style="background:#dcfce7;color:#16a34a;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:800">Activo</span>' : '<span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:800">Inactivo</span>'}
      ${fi||ff ? `<span style="font-size:12px;color:#888">${fi||'—'} → ${ff||'—'}</span>` : ''}
    </div>`;

  document.getElementById('modalVistaPanel').classList.add('open');
}

// ── VER TODOS (carrusel real con Swiper) ───────────────────────────────────
let allPanelesCache = null, vistaTodosSwiper = null;

async function verTodosPanel(){
  document.getElementById('modalVistaTodos').classList.add('open');
  if(!allPanelesCache) {
    allPanelesCache = await fetch('ajax/listar_ofertas.php').then(r=>r.json());
  }
  renderVistaTodos();
}

function renderVistaTodos(){
  const soloActivos = document.getElementById('verSoloActivos').checked;
  const lista = (allPanelesCache||[]).filter(o => !soloActivos || o.activo==1);

  if(!lista.length){
    document.getElementById('vistaTodosSwiperInner').innerHTML =
      `<div class="swiper-slide"><div class="slide-bg slide-bg-1" style="font-size:14px;color:rgba(255,255,255,.6)"><div style="text-align:center"><i class="fa-solid fa-box-open" style="font-size:40px;display:block;margin-bottom:12px"></i>Sin paneles para mostrar</div></div></div>`;
    document.getElementById('vistaTodosLista').innerHTML = '';
    if(vistaTodosSwiper){ vistaTodosSwiper.destroy(true,true); vistaTodosSwiper=null; }
    return;
  }

  // Slides del carrusel (idénticos a la tienda)
  document.getElementById('vistaTodosSwiperInner').innerHTML = lista.map((o,i) =>
    `<div class="swiper-slide">${buildSlideInner(o, i)}</div>`
  ).join('');

  // Inicializar / reinicializar Swiper
  if(vistaTodosSwiper){ vistaTodosSwiper.destroy(true,true); }
  vistaTodosSwiper = new Swiper('#vistaTodosSwiper', {
    loop:   lista.length > 1,
    speed:  600,
    effect: 'fade',
    fadeEffect: { crossFade: true },
    autoplay: lista.length > 1 ? { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
    pagination: { el: '#vistaTodosSwiper .swiper-pagination', clickable: true },
    grabCursor: true,
  });

  // Lista compacta debajo del carrusel
  document.getElementById('vistaTodosLista').innerHTML = `
    <p style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#bbb;margin-bottom:10px">
      ${lista.length} panel${lista.length!==1?'es':''} en el carrusel
    </p>
    ${lista.map((o,i) => {
      const tp = TIPOS_PANEL_JS[o.tipo_panel]||{label:'—',color:'#888'};
      const bg = o.imagen
        ? `<img src="${ASSETS_URL}/img/ofertas/${esc(o.imagen)}" style="width:100%;height:100%;object-fit:cover">`
        : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:16px;background:linear-gradient(135deg,${['#c88e99,#9c27b0','#111,#a46678','#ff6d00,#ffca28','#1d9e75,#0288d1'][i%4]})">${esc(o.emoji||'🍪')}</div>`;
      return `<div class="sim-panel-row${o.activo==0?' inactive':''}">
        <div class="sim-panel-row-img">${bg}</div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(o.titulo)}</div>
          ${o.descripcion?`<div style="font-size:11px;color:#aaa;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(o.descripcion)}</div>`:''}
        </div>
        <span style="background:${tp.color}20;color:${tp.color};padding:2px 9px;border-radius:12px;font-size:11px;font-weight:700;flex-shrink:0">${tp.label}</span>
        ${o.activo==0?'<span style="background:#fee2e2;color:#dc2626;padding:2px 8px;border-radius:12px;font-size:10px;font-weight:700">Inactivo</span>':''}
      </div>`;
    }).join('')}`;
}

// ── Gestión de tipos de panel ──────────────────────────────────────────────
function openTiposModal(){ cargarTiposLista(); document.getElementById('modalTipos').classList.add('open'); }
function closeTiposModal(){ document.getElementById('modalTipos').classList.remove('open'); }

async function cargarTiposLista() {
  const tipos     = await fetch('ajax/tipos_panel.php?accion=listar').then(r=>r.json());
  const lista     = document.getElementById('tiposLista');
  if(!tipos.length){ lista.innerHTML='<p style="color:#999;font-size:13px">Sin tipos cargados.</p>'; return; }

  const sistema   = tipos.filter(t=>t.sistema==1 && t.activo==1);
  const personales = tipos.filter(t=>t.sistema==0 && t.activo==1);
  const inactivos  = tipos.filter(t=>t.activo==0 && t.sistema==0);

  const filaSistema = t => `
    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid #f0f0f0;border-radius:10px;background:#fafafa">
      <span style="font-size:18px">${t.emoji}</span>
      <span style="font-weight:700;font-size:13px;flex:1;color:${t.color}">${t.label}</span>
      <span style="font-size:10px;color:#ccc;font-family:monospace">${t.clave}</span>
      <span title="Tipo del sistema — protegido" style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#f5f5f5;color:#aaa">
        <i class="fa-solid fa-lock" style="font-size:9px"></i> Sistema
      </span>
    </div>`;

  const filaPersonal = t => {
    const esA = t.activo==1;
    return `<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid ${esA?'#e5e7eb':'#fca5a5'};border-radius:10px;background:${esA?'#fff':'#fff5f5'}">
      <span style="font-size:18px">${t.emoji}</span>
      <span style="font-weight:700;font-size:13px;flex:1;color:${esA?t.color:'#aaa'}">${t.label}</span>
      <span style="font-size:10px;color:#ccc;font-family:monospace">${t.clave}</span>
      ${esA
        ? `<button onclick="inactivarTipo(${t.id},'${t.label}')" style="background:#fef3c7;border:1.5px solid #fcd34d;color:#92400e;border-radius:7px;padding:4px 10px;font-size:11px;font-weight:700;cursor:pointer">Pausar</button>
           <button onclick="eliminarTipo(${t.id},'${t.label}')"  style="background:#fee2e2;border:1.5px solid #fca5a5;color:#dc2626;border-radius:7px;padding:4px 10px;font-size:11px;font-weight:700;cursor:pointer">Eliminar</button>`
        : `<span style="font-size:11px;color:#dc2626;font-weight:700;background:#fee2e2;padding:2px 8px;border-radius:12px">Inactivo</span>
           <button onclick="activarTipo(${t.id},'${t.label}')" style="background:#dcfce7;border:1.5px solid #86efac;color:#16a34a;border-radius:7px;padding:4px 10px;font-size:11px;font-weight:700;cursor:pointer">Activar</button>
           <button onclick="eliminarTipo(${t.id},'${t.label}')" style="background:#fee2e2;border:1.5px solid #fca5a5;color:#dc2626;border-radius:7px;padding:4px 10px;font-size:11px;font-weight:700;cursor:pointer">Eliminar</button>`}
    </div>`;
  };

  let html = '';
  if(sistema.length){
    html += `<div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#bbb;margin-bottom:8px;display:flex;align-items:center;gap:6px"><i class="fa-solid fa-shield-halved" style="color:#c88e99"></i> Tipos del sistema (protegidos)</div>`;
    html += `<div style="display:flex;flex-direction:column;gap:6px;margin-bottom:18px">${sistema.map(filaSistema).join('')}</div>`;
  }
  if(personales.length){
    html += `<div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#bbb;margin-bottom:8px;display:flex;align-items:center;gap:6px"><i class="fa-solid fa-tag" style="color:#6366f1"></i> Tipos personalizados</div>`;
    html += `<div style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px">${personales.map(filaPersonal).join('')}</div>`;
  }
  if(inactivos.length){
    html += `<div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#bbb;margin-bottom:8px">Pausados</div>`;
    html += `<div style="display:flex;flex-direction:column;gap:6px">${inactivos.map(filaPersonal).join('')}</div>`;
  }
  lista.innerHTML = html;
}

async function activarTipo(id,nombre){
  const fd=new FormData(); fd.append('accion','activar'); fd.append('id',id);
  const res=await fetch('ajax/tipos_panel.php',{method:'POST',body:fd}).then(r=>r.json());
  if(res.success){ Swal.fire({icon:'success',title:'Tipo activado',text:`"${nombre}" vuelve a estar disponible.`,timer:1400,showConfirmButton:false}); cargarTiposLista(); setTimeout(()=>location.reload(),1500); }
  else Swal.fire({icon:'error',title:'Error',text:res.message||'No se pudo activar',confirmButtonColor:'#c88e99'});
}

async function agregarTipo(){
  const label=document.getElementById('nuevoLabel').value.trim();
  const emoji=document.getElementById('nuevoEmoji').value.trim()||'📌';
  const color=document.getElementById('nuevoColor').value;
  if(!label){ Swal.fire({icon:'warning',title:'Falta el nombre',text:'Escribí un nombre para el tipo',confirmButtonColor:'#c88e99'}); return; }
  const fd=new FormData(); fd.append('accion','guardar'); fd.append('label',label); fd.append('emoji',emoji); fd.append('color',color);
  const res=await fetch('ajax/tipos_panel.php',{method:'POST',body:fd}).then(r=>r.json());
  if(res.success){ document.getElementById('nuevoLabel').value=''; document.getElementById('nuevoEmoji').value=''; Swal.fire({icon:'success',title:'Tipo agregado',timer:1200,showConfirmButton:false}); cargarTiposLista(); setTimeout(()=>location.reload(),1400); }
  else Swal.fire({icon:'error',title:'Error',text:res.message||'No se pudo guardar',confirmButtonColor:'#c88e99'});
}

async function inactivarTipo(id,nombre){
  const {isConfirmed}=await Swal.fire({icon:'warning',title:'¿Inactivar tipo?',html:`El tipo <strong>${nombre}</strong> no aparecerá en el selector pero sus paneles se mantienen.`,showCancelButton:true,confirmButtonText:'Sí, inactivar',cancelButtonText:'Cancelar',confirmButtonColor:'#d97706',cancelButtonColor:'#aaa'});
  if(!isConfirmed) return;
  const fd=new FormData(); fd.append('accion','eliminar'); fd.append('id',id);
  const res=await fetch('ajax/tipos_panel.php',{method:'POST',body:fd}).then(r=>r.json());
  if(res.success){ Swal.fire({icon:'success',title:'Tipo inactivado',timer:1200,showConfirmButton:false}); cargarTiposLista(); setTimeout(()=>location.reload(),1400); }
  else Swal.fire({icon:'error',title:'No se puede inactivar',text:res.message,confirmButtonColor:'#c88e99'});
}

// Cerrar "ver todos" destruye el Swiper
document.getElementById('modalVistaTodos').addEventListener('click', function(e){
  if(e.target === this){
    this.classList.remove('open');
    if(vistaTodosSwiper){ vistaTodosSwiper.destroy(true,true); vistaTodosSwiper=null; }
  }
});

async function eliminarTipo(id,nombre){
  const {isConfirmed}=await Swal.fire({icon:'warning',title:'¿Eliminar tipo?',html:`Se eliminará permanentemente <strong>${nombre}</strong>.<br>Solo se puede si no tiene paneles asociados.`,showCancelButton:true,confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar',confirmButtonColor:'#dc2626',cancelButtonColor:'#aaa'});
  if(!isConfirmed) return;
  const fd=new FormData(); fd.append('accion','eliminar_hard'); fd.append('id',id);
  const res=await fetch('ajax/tipos_panel.php',{method:'POST',body:fd}).then(r=>r.json());
  if(res.success){ Swal.fire({icon:'success',title:'Eliminado',timer:1200,showConfirmButton:false}); cargarTiposLista(); setTimeout(()=>location.reload(),1400); }
  else Swal.fire({icon:'error',title:'No se puede eliminar',text:res.message,confirmButtonColor:'#c88e99'});
}
</script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
