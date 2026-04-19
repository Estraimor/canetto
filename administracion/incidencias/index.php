<?php
define('APP_BOOT', true);
require_once __DIR__ . '/../../config/conexion.php';
$pageTitle = "Incidencias de Producción — Canetto";
include '../../panel/dashboard/layaut/nav.php';
?>
<link rel="stylesheet" href="incidencias.css">

<div class="inc-wrapper">

  <!-- HEADER -->
  <div class="inc-header">
    <div>
      <h1 class="inc-title">🚨 Incidencias de Producción</h1>
      <p class="inc-subtitle">Registrá fallas en Horneado y Amasado en tiempo real</p>
    </div>
    <div class="inc-header-stats">
      <div class="inc-stat-pill inc-stat--open">
        <span id="statAbiertas">—</span> abiertas
      </div>
      <div class="inc-stat-pill inc-stat--crit">
        <span id="statCriticas">—</span> críticas
      </div>
    </div>
  </div>

  <!-- BOTONES DE PÁNICO -->
  <div class="inc-panic-row">

    <button class="inc-panic-btn inc-panic--horneado" onclick="abrirModal('Horneado')">
      <div class="inc-panic-icon">🔥</div>
      <div class="inc-panic-label">FALLA EN<br><strong>HORNEADO</strong></div>
      <div class="inc-panic-sub">Reportar incidencia</div>
    </button>

    <button class="inc-panic-btn inc-panic--amasado" onclick="abrirModal('Amasado')">
      <div class="inc-panic-icon">🥣</div>
      <div class="inc-panic-label">FALLA EN<br><strong>AMASADO</strong></div>
      <div class="inc-panic-sub">Reportar incidencia</div>
    </button>

    <button class="inc-panic-btn inc-panic--general" onclick="abrirModal('General')">
      <div class="inc-panic-icon">⚠️</div>
      <div class="inc-panic-label">INCIDENCIA<br><strong>GENERAL</strong></div>
      <div class="inc-panic-sub">Otra área / equipo</div>
    </button>

  </div>

  <!-- FILTROS + TABLA -->
  <div class="inc-card">
    <div class="inc-card-head">
      <span class="inc-card-title"><i class="fa-solid fa-list"></i> Registro de incidencias</span>
      <div class="inc-filtros">
        <select id="filtroArea" onchange="cargar()">
          <option value="">Todas las áreas</option>
          <option value="Horneado">Horneado</option>
          <option value="Amasado">Amasado</option>
          <option value="General">General</option>
        </select>
        <select id="filtroEstado" onchange="cargar()">
          <option value="">Todos los estados</option>
          <option value="abierta">Abiertas</option>
          <option value="cerrada">Cerradas</option>
        </select>
      </div>
    </div>
    <div id="incTable"><div class="inc-loading">Cargando...</div></div>
  </div>

</div>

<!-- MODAL REPORTE -->
<div class="inc-modal-bg" id="incModalBg" onclick="if(event.target===this)cerrarModal()">
  <div class="inc-modal">
    <div class="inc-modal-head">
      <span id="incModalTitle">Reportar incidencia</span>
      <button onclick="cerrarModal()" class="inc-modal-close">×</button>
    </div>
    <div class="inc-modal-body">

      <input type="hidden" id="incArea">

      <div class="inc-fg">
        <label>Tipo de falla</label>
        <select id="incTipo">
          <option value="">— Seleccioná el tipo —</option>
          <option>Temperatura incorrecta del horno</option>
          <option>Masa no levó / textura incorrecta</option>
          <option>Producto quemado</option>
          <option>Falla en equipo / maquinaria</option>
          <option>Contaminación / higiene</option>
          <option>Falta de materia prima</option>
          <option>Error en receta / cantidades</option>
          <option>Corte de energía</option>
          <option>Otro</option>
        </select>
      </div>

      <div class="inc-fg">
        <label>Prioridad</label>
        <div class="inc-prioridad-row">
          <label class="inc-prio-opt">
            <input type="radio" name="prioridad" value="critica">
            <span class="inc-prio-badge inc-prio--critica">🔴 Crítica</span>
          </label>
          <label class="inc-prio-opt">
            <input type="radio" name="prioridad" value="alta" checked>
            <span class="inc-prio-badge inc-prio--alta">🟠 Alta</span>
          </label>
          <label class="inc-prio-opt">
            <input type="radio" name="prioridad" value="media">
            <span class="inc-prio-badge inc-prio--media">🟡 Media</span>
          </label>
        </div>
      </div>

      <div class="inc-fg">
        <label>Descripción del suceso *</label>
        <textarea id="incDescripcion" rows="4"
          placeholder="Describí qué pasó, a qué hora, qué lote/receta estaba en curso..."></textarea>
      </div>

      <div class="inc-alert" id="incAlert" style="display:none"></div>

      <button class="inc-btn-enviar" id="incBtnEnviar" onclick="enviarIncidencia()">
        🚨 Reportar incidencia
      </button>

    </div>
  </div>
</div>

<script>
const INC_API = '<?= URL_ADMIN ?>/incidencias/api/';

async function cargar(){
  const area   = document.getElementById('filtroArea').value;
  const estado = document.getElementById('filtroEstado').value;
  let url = INC_API + 'listar.php?limit=100';
  if(area)   url += '&area='   + encodeURIComponent(area);
  if(estado) url += '&estado=' + encodeURIComponent(estado);

  const r = await fetch(url).then(r=>r.json()).catch(()=>null);
  if(!r){document.getElementById('incTable').innerHTML='<div class="inc-empty">Error al cargar</div>';return;}

  document.getElementById('statAbiertas').textContent = r.total_abiertas;
  document.getElementById('statCriticas').textContent = r.total_criticas;

  const data = r.incidencias || [];
  if(!data.length){
    document.getElementById('incTable').innerHTML='<div class="inc-empty">✅ No hay incidencias registradas</div>';
    return;
  }

  const prioLabel = {critica:'🔴 Crítica', alta:'🟠 Alta', media:'🟡 Media'};
  const areaIcon  = {Horneado:'🔥', Amasado:'🥣', General:'⚠️'};

  document.getElementById('incTable').innerHTML = `
    <table class="inc-table">
      <thead><tr>
        <th>#</th><th>Área</th><th>Tipo</th><th>Prioridad</th>
        <th>Descripción</th><th>Reportado por</th><th>Fecha</th><th>Estado</th><th></th>
      </tr></thead>
      <tbody>
        ${data.map(i=>`<tr class="inc-row inc-row--${i.prioridad} ${i.estado==='cerrada'?'inc-row--cerrada':''}">
          <td class="inc-id">#${i.id}</td>
          <td><span class="inc-area-badge inc-area--${i.area.toLowerCase()}">${areaIcon[i.area]||'📋'} ${i.area}</span></td>
          <td>${i.tipo||'—'}</td>
          <td><span class="inc-prio-badge2 inc-prio2--${i.prioridad}">${prioLabel[i.prioridad]||i.prioridad}</span></td>
          <td class="inc-desc-cell">${escHtml(i.descripcion)}</td>
          <td style="font-size:13px">${escHtml(i.usuario_nombre||'—')}</td>
          <td style="font-size:12px;color:#888">${fmtDate(i.created_at)}</td>
          <td><span class="inc-estado inc-estado--${i.estado}">${i.estado==='abierta'?'Abierta':'Cerrada'}</span></td>
          <td>${i.estado==='abierta'?`<button class="inc-btn-cerrar" onclick="cerrarInc(${i.id})">✓ Cerrar</button>`:''}</td>
        </tr>`).join('')}
      </tbody>
    </table>`;
}

function escHtml(s){
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function fmtDate(s){
  if(!s) return '—';
  const d = new Date(s);
  return d.toLocaleDateString('es-AR')+' '+d.toLocaleTimeString('es-AR',{hour:'2-digit',minute:'2-digit'});
}

function abrirModal(area){
  document.getElementById('incArea').value = area;
  document.getElementById('incModalTitle').textContent = {
    Horneado:'🔥 Falla en Horneado',
    Amasado:'🥣 Falla en Amasado',
    General:'⚠️ Incidencia General'
  }[area] || 'Reportar incidencia';
  document.getElementById('incDescripcion').value='';
  document.getElementById('incTipo').value='';
  document.querySelector('input[name="prioridad"][value="alta"]').checked=true;
  document.getElementById('incAlert').style.display='none';
  document.getElementById('incModalBg').classList.add('on');
  document.body.style.overflow='hidden';
}
function cerrarModal(){
  document.getElementById('incModalBg').classList.remove('on');
  document.body.style.overflow='';
}

async function enviarIncidencia(){
  const area = document.getElementById('incArea').value;
  const tipo = document.getElementById('incTipo').value;
  const desc = document.getElementById('incDescripcion').value.trim();
  const prio = document.querySelector('input[name="prioridad"]:checked')?.value || 'alta';
  const alrt = document.getElementById('incAlert');

  if(!desc){alrt.textContent='La descripción es obligatoria.';alrt.style.display='block';return;}

  const btn = document.getElementById('incBtnEnviar');
  btn.disabled=true; btn.textContent='Enviando...';

  const fd = new FormData();
  fd.append('area', area);
  fd.append('tipo', tipo);
  fd.append('descripcion', desc);
  fd.append('prioridad', prio);

  const r = await fetch(INC_API+'guardar.php',{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);

  btn.disabled=false; btn.textContent='🚨 Reportar incidencia';

  if(r?.success){
    cerrarModal();
    cargar();
    // Feedback visual
    const toast = document.createElement('div');
    toast.textContent='✅ Incidencia registrada correctamente';
    toast.style.cssText='position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#1a7a4a;color:#fff;padding:12px 24px;border-radius:30px;font-weight:600;z-index:9999;font-size:15px';
    document.body.appendChild(toast);
    setTimeout(()=>toast.remove(),2800);
  } else {
    alrt.textContent = r?.message || 'Error al guardar. Intentá de nuevo.';
    alrt.style.display='block';
  }
}

async function cerrarInc(id){
  if(!confirm('¿Cerrar esta incidencia?')) return;
  const fd=new FormData(); fd.append('id',id);
  await fetch(INC_API+'cerrar.php',{method:'POST',body:fd});
  cargar();
}

cargar();
</script>
