<?php
// Fragmento reutilizable: botón de pánico flotante + modal de incidencia
// Incluir ANTES de footer.php en páginas de producción
$_panicoArea = $panicoArea ?? 'Producción';
?>

<!-- ═══ BOTÓN DE PÁNICO FLOTANTE ═══ -->
<button id="btnPanico" onclick="abrirPanico()" title="Reportar incidencia">
    <span style="font-size:22px">🚨</span>
    <span class="panico-label">Reportar incidencia</span>
</button>

<!-- ═══ MODAL DE INCIDENCIA ═══ -->
<div id="overlayPanico" onclick="if(event.target===this)cerrarPanico()" style="
    display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
    z-index:9000;align-items:center;justify-content:center;padding:20px">
    <div style="
        background:#fff;border-radius:18px;width:100%;max-width:500px;
        box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;animation:panSlide .2s ease">

        <!-- Header rojo -->
        <div style="background:#dc2626;padding:22px 26px;display:flex;align-items:center;gap:14px">
            <span style="font-size:32px">🚨</span>
            <div>
                <div style="font-size:20px;font-weight:700;color:#fff">Reportar incidencia</div>
                <div style="font-size:13px;color:rgba(255,255,255,.8);margin-top:2px">Área: <?= htmlspecialchars($_panicoArea) ?></div>
            </div>
            <button onclick="cerrarPanico()" style="margin-left:auto;background:rgba(255,255,255,.2);border:none;color:#fff;border-radius:8px;width:34px;height:34px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>

        <!-- Body -->
        <div style="padding:26px;display:flex;flex-direction:column;gap:16px">

            <div>
                <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px">TIPO DE INCIDENCIA *</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px" id="tipoIncGrid">
                    <?php foreach ([
                        ['maquina',    '⚙️',  'Falla de máquina'],
                        ['ingrediente','🥚',  'Falta ingrediente'],
                        ['quemado',    '🔥',  'Producto quemado'],
                        ['contaminacion','⚠️','Contaminación'],
                        ['temperatura','🌡️', 'Temp. incorrecta'],
                        ['otro',       '📝',  'Otro'],
                    ] as [$val, $ico, $txt]): ?>
                    <label style="cursor:pointer">
                        <input type="radio" name="panicoTipo" value="<?= $val ?>" style="display:none"
                               onchange="document.querySelectorAll('.panico-tipo-btn').forEach(b=>b.classList.remove('sel'));this.nextElementSibling.classList.add('sel')">
                        <div class="panico-tipo-btn" style="
                            padding:10px 12px;border:2px solid #e5e5e5;border-radius:10px;
                            font-size:14px;font-weight:600;color:#444;text-align:center;
                            transition:all .15s;display:flex;align-items:center;gap:8px">
                            <span><?= $ico ?></span><?= $txt ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px">DESCRIPCIÓN *</label>
                <textarea id="panicoDesc" rows="3" placeholder="Describí brevemente qué pasó..." style="
                    width:100%;padding:12px 14px;border:2px solid #e5e5e5;border-radius:10px;
                    font-size:15px;resize:vertical;font-family:inherit;outline:none;
                    transition:border .15s" onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='#e5e5e5'"></textarea>
            </div>

            <div>
                <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px">GRAVEDAD</label>
                <div style="display:flex;gap:8px">
                    <?php foreach ([['leve','🟡','Leve'],['moderada','🟠','Moderada'],['critica','🔴','Crítica']] as [$v,$i,$t]): ?>
                    <label style="flex:1;cursor:pointer">
                        <input type="radio" name="panicoGravedad" value="<?= $v ?>" style="display:none" <?= $v==='moderada'?'checked':'' ?>
                               onchange="document.querySelectorAll('.pan-grav').forEach(b=>b.classList.remove('gsel'));this.nextElementSibling.classList.add('gsel')">
                        <div class="pan-grav <?= $v==='moderada'?'gsel':'' ?>" style="
                            padding:9px;border:2px solid #e5e5e5;border-radius:10px;text-align:center;
                            font-size:13px;font-weight:600;color:#444;transition:all .15s">
                            <?= $i ?> <?= $t ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button onclick="enviarIncidencia()" style="
                background:#dc2626;color:#fff;border:none;border-radius:12px;padding:15px;
                font-size:16px;font-weight:700;cursor:pointer;width:100%;
                transition:background .15s;font-family:inherit" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                🚨 Enviar reporte
            </button>
        </div>
    </div>
</div>

<style>
#btnPanico {
    position: fixed;
    bottom: 28px;
    right: 28px;
    background: #dc2626;
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: 14px 22px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 20px rgba(220,38,38,.45);
    z-index: 8000;
    transition: transform .15s, box-shadow .15s;
    animation: panicoFloat 2.5s ease-in-out infinite;
    font-family: inherit;
}
#btnPanico:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 28px rgba(220,38,38,.6);
    animation: none;
}
.panico-tipo-btn.sel  { border-color:#dc2626 !important; color:#dc2626 !important; background:#fee2e2; }
.pan-grav.gsel        { border-color:#dc2626 !important; color:#dc2626 !important; background:#fee2e2; }

@keyframes panicoFloat {
    0%,100% { transform:translateY(0);   box-shadow:0 4px 20px rgba(220,38,38,.45); }
    50%      { transform:translateY(-4px);box-shadow:0 8px 28px rgba(220,38,38,.55); }
}
@keyframes panSlide {
    from { opacity:0;transform:scale(.95) }
    to   { opacity:1;transform:scale(1)   }
}
</style>

<script>
function abrirPanico(){
    document.getElementById('overlayPanico').style.display='flex';
    document.getElementById('panicoDesc').focus();
}
function cerrarPanico(){
    document.getElementById('overlayPanico').style.display='none';
    document.getElementById('panicoDesc').value='';
    document.querySelectorAll('input[name="panicoTipo"]').forEach(r=>r.checked=false);
    document.querySelectorAll('.panico-tipo-btn').forEach(b=>b.classList.remove('sel'));
}

async function enviarIncidencia(){
    const tipo  = document.querySelector('input[name="panicoTipo"]:checked')?.value;
    const desc  = document.getElementById('panicoDesc').value.trim();
    const grav  = document.querySelector('input[name="panicoGravedad"]:checked')?.value || 'moderada';

    if (!tipo) { alert('Seleccioná el tipo de incidencia'); return; }
    if (!desc)  { alert('Describí la incidencia'); return; }

    const fd = new FormData();
    fd.append('tipo',      tipo);
    fd.append('descripcion', desc);
    fd.append('gravedad',  grav);
    fd.append('area',      '<?= addslashes($_panicoArea) ?>');

    const res = await fetch('<?= URL_ADMIN ?>/incidencias/api/guardar.php', {method:'POST',body:fd})
        .then(r=>r.json()).catch(()=>null);

    if (res?.success) {
        cerrarPanico();
        // Feedback visual
        const btn = document.getElementById('btnPanico');
        btn.style.background='#16a34a';
        btn.querySelector('.panico-label').textContent='✓ Reporte enviado';
        setTimeout(()=>{ btn.style.background='#dc2626'; btn.querySelector('.panico-label').textContent='Reportar incidencia'; },3000);
    } else {
        alert(res?.message || 'Error al enviar el reporte');
    }
}
</script>
