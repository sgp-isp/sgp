<?php
/**
 * Vista: Actividades Extras — Instituciones Aliadas
 */
$instituciones    = $data['instituciones']    ?? [];
$porTipo          = $data['porTipo']          ?? [];
$kpiInstituciones = $data['kpiInstituciones'] ?? 0;
$csrfToken        = $data['csrfToken']        ?? '';
?>
<style>
@keyframes inst-fadeUp { from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)} }

.inst-wrap { width:100%; }

.inst-banner { background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:20px;padding:28px 36px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;overflow:hidden; }
.inst-banner::before { content:'';position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,0.04);border-radius:50%; }
.inst-back { display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);backdrop-filter:blur(10px);color:white;padding:8px 16px;border-radius:9px;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s; }
.inst-back:hover { background:rgba(255,255,255,0.25);color:white; }
.inst-action-btn { display:inline-flex;align-items:center;gap:7px;background:white;color:#2563eb;border:none;padding:10px 20px;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;transition:all .2s;box-shadow:0 4px 12px rgba(0,0,0,0.15); }
.inst-action-btn:hover { transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,0.2); }

.inst-layout { display:grid;grid-template-columns:1fr 300px;gap:22px; }
.inst-card   { background:white;border-radius:20px;padding:22px;box-shadow:0 4px 20px rgba(0,0,0,0.05);border:1px solid rgba(0,0,0,0.05);animation:inst-fadeUp .4s ease both; }

.inst-filters { display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;align-items:center; }
.inst-search  { flex:1;min-width:180px;padding:9px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:.85rem;font-family:inherit;color:#1e293b;outline:none;transition:border-color .2s; }
.inst-search:focus { border-color:#2563eb; }
.pill-filter  { padding:6px 14px;border-radius:20px;border:1.5px solid #e2e8f0;font-size:.78rem;font-weight:700;cursor:pointer;background:white;color:#64748b;transition:all .2s; }
.pill-filter:hover,.pill-filter.active { background:#2563eb;color:white;border-color:#2563eb; }

.inst-row    { display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f8fafc; }
.inst-row:last-child { border-bottom:none; }
.inst-avatar { width:42px;height:42px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;color:white; }
.inst-name   { font-weight:700;color:#1e293b;font-size:.9rem; }
.inst-meta   { font-size:.75rem;color:#94a3b8;margin-top:2px;display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.badge-tipo  { font-size:.67rem;padding:2px 8px;border-radius:100px;font-weight:700;text-transform:uppercase; }
.tipo-univ   { background:#eff6ff;color:#2563eb; }
.tipo-inst   { background:#f5f3ff;color:#7c3aed; }
.tipo-col    { background:#fffbeb;color:#d97706; }
.tipo-otro   { background:#f0fdf4;color:#059669; }
.badge-activos { background:#dcfce7;color:#059669;font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:100px; }
.ibtn-edit   { width:32px;height:32px;border-radius:8px;border:none;background:#eff6ff;color:#2563eb;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s; }
.ibtn-edit:hover { background:#dbeafe;transform:scale(1.08); }
.ibtn-del    { width:32px;height:32px;border-radius:8px;border:none;background:#fef2f2;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s; }
.ibtn-del:hover  { background:#fee2e2;transform:scale(1.08); }

.inst-side   { display:flex;flex-direction:column;gap:18px; }
.donut-card  { background:white;border-radius:20px;padding:20px;box-shadow:0 4px 20px rgba(0,0,0,0.05);border:1px solid rgba(0,0,0,0.05);animation:inst-fadeUp .4s .1s ease both; }
.donut-legend { display:flex;flex-direction:column;gap:8px;margin-top:14px; }
.donut-leg-row { display:flex;align-items:center;gap:8px;font-size:.8rem; }
.donut-dot   { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
.inst-kpi-mini { background:white;border-radius:20px;padding:18px;box-shadow:0 4px 20px rgba(0,0,0,0.05);border:1px solid rgba(0,0,0,0.05);animation:inst-fadeUp .4s .15s ease both; }

.inst-modal-overlay { display:none;position:fixed;inset:0;background:rgba(15,23,42,.7);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center; }
.inst-modal-overlay.active { display:flex; }
.inst-modal-box { background:white;border-radius:22px;width:90%;max-width:480px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 32px 80px rgba(15,23,42,.3);animation:inst-fadeUp .3s ease; }
.im-head { background:linear-gradient(135deg,#172554,#2563eb);padding:22px 26px;display:flex;justify-content:space-between;align-items:center; }
.im-head h2 { font-size:1rem;font-weight:700;margin:0;color:white; }
.im-body { padding:22px 26px;overflow-y:auto; }
.im-close { background:rgba(255,255,255,.2);border:none;color:white;width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center; }
.im-close:hover { background:rgba(255,255,255,.35); }
.f-label { display:block;font-size:.72rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px; }
.f-input { width:100%;padding:9px 12px;border:2px solid #e5e7eb;border-radius:9px;font-size:.88rem;color:#1e293b;transition:border-color .2s;box-sizing:border-box;background:#fafafa;font-family:inherit; }
.f-input:focus { outline:none;border-color:#2563eb;background:white; }
.f-group { margin-bottom:14px; }
.f-row2  { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
.f-btn-primary { width:100%;padding:11px;border:none;border-radius:9px;cursor:pointer;background:linear-gradient(135deg,#172554,#2563eb);color:white;font-size:.88rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:7px;transition:all .2s;font-family:inherit; }
.f-btn-primary:hover { transform:translateY(-1px);box-shadow:0 6px 16px rgba(37,99,235,.3); }
.f-btn-cancel { flex:1;padding:10px;background:#f1f5f9;color:#475569;border:2px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit; }
.f-btn-cancel:hover { background:#e2e8f0; }

@media(max-width:900px){ .inst-layout{grid-template-columns:1fr;} }
</style>

<div class="inst-wrap">

<!-- BANNER -->
<div class="inst-banner">
    <div style="display:flex;align-items:center;gap:14px;z-index:1;">
        <a href="<?= URLROOT ?>/actividades" class="inst-back"><i class="ti ti-arrow-left"></i> Volver</a>
        <div style="width:1px;height:28px;background:rgba(255,255,255,.2);"></div>
        <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:10px;">
            <i class="ti ti-building-bank" style="font-size:24px;color:white;"></i>
        </div>
        <div>
            <h1 style="color:white;font-size:1.5rem;font-weight:800;margin:0;">Instituciones Aliadas</h1>
            <p style="color:rgba(255,255,255,.7);margin:3px 0 0;font-size:.82rem;">Universidades e institutos externos · <?= $kpiInstituciones ?> registradas</p>
        </div>
    </div>
    <button class="inst-action-btn" onclick="abrirModalNuevaInst()">
        <i class="ti ti-plus"></i> Nueva Institución
    </button>
</div>

<!-- LAYOUT -->
<div class="inst-layout">

    <!-- Lista -->
    <div class="inst-card">
        <div class="inst-filters">
            <input type="text" class="inst-search" id="buscarInst" placeholder="🔍 Buscar institución..." oninput="filtrarInstituciones()">
            <button class="pill-filter active" data-tipo="Todos" onclick="filtrarPorTipo(this)">Todos</button>
            <button class="pill-filter" data-tipo="Universidad" onclick="filtrarPorTipo(this)">Universidad</button>
            <button class="pill-filter" data-tipo="Instituto" onclick="filtrarPorTipo(this)">Instituto</button>
            <button class="pill-filter" data-tipo="Colegio Técnico" onclick="filtrarPorTipo(this)">Colegio</button>
            <button class="pill-filter" data-tipo="Otro" onclick="filtrarPorTipo(this)">Otro</button>
        </div>
        <div id="listaInstituciones">
        <?php if (empty($instituciones)): ?>
        <div style="text-align:center;padding:50px 20px;color:#94a3b8;">
            <i class="ti ti-building-bank" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;"></i>
            <div style="font-weight:600;">Sin instituciones registradas</div>
            <div style="font-size:.8rem;margin-top:4px;">Usa "Nueva Institución" para agregar la primera</div>
        </div>
        <?php else:
        $tipoColors = ['Universidad'=>'#2563eb','Instituto'=>'#7c3aed','Colegio Técnico'=>'#d97706','Otro'=>'#059669'];
        $tipoBadge  = ['Universidad'=>'tipo-univ','Instituto'=>'tipo-inst','Colegio Técnico'=>'tipo-col','Otro'=>'tipo-otro'];
        foreach ($instituciones as $inst):
            $color    = $tipoColors[$inst->tipo] ?? '#64748b';
            $inicial  = strtoupper(substr($inst->nombre, 0, 1));
            $instData = htmlspecialchars(json_encode(['id'=>(int)$inst->id,'nombre'=>$inst->nombre,'tipo'=>$inst->tipo,'contacto'=>$inst->contacto??'','telefono'=>$inst->telefono??'']), ENT_QUOTES);
        ?>
        <div class="inst-row" id="inst-row-<?= $inst->id ?>" data-tipo="<?= htmlspecialchars($inst->tipo) ?>" data-nombre="<?= htmlspecialchars(strtolower($inst->nombre)) ?>">
            <div class="inst-avatar" style="background:linear-gradient(135deg,<?= $color ?>,<?= $color ?>cc);"><?= $inicial ?></div>
            <div style="flex:1;min-width:0;">
                <div class="inst-name"><?= htmlspecialchars($inst->nombre) ?></div>
                <div class="inst-meta">
                    <span class="badge-tipo <?= $tipoBadge[$inst->tipo] ?? 'tipo-otro' ?>"><?= htmlspecialchars($inst->tipo) ?></span>
                    <?php if ($inst->contacto): ?><span><?= htmlspecialchars($inst->contacto) ?></span><?php endif; ?>
                    <?php if ($inst->telefono): ?><span><i class="ti ti-phone"></i> <?= htmlspecialchars($inst->telefono) ?></span><?php endif; ?>
                    <?php if ((int)($inst->pasantes_activos??0) > 0): ?>
                    <span class="badge-activos"><i class="ti ti-users"></i> <?= (int)$inst->pasantes_activos ?> activos</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;gap:6px;">
                <button class="ibtn-edit" title="Editar" onclick="abrirEditarInst(<?= $instData ?>)"><i class="ti ti-edit" style="font-size:.9rem;"></i></button>
                <button class="ibtn-del"  title="Eliminar" onclick="eliminarInst(<?= $inst->id ?>, '<?= htmlspecialchars($inst->nombre, ENT_QUOTES) ?>')"><i class="ti ti-trash" style="font-size:.9rem;"></i></button>
            </div>
        </div>
        <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Lateral -->
    <div class="inst-side">
        <div class="donut-card">
            <div style="font-size:.82rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:7px;">
                <i class="ti ti-chart-donut" style="color:#7c3aed;"></i> Distribución por Tipo
            </div>
            <canvas id="instDonut" width="120" height="120" style="display:block;margin:14px auto 0;"></canvas>
            <div class="donut-legend" id="instLegend"></div>
        </div>
        <div class="inst-kpi-mini">
            <div style="font-size:.82rem;font-weight:700;color:#1e293b;margin-bottom:14px;display:flex;align-items:center;gap:7px;">
                <i class="ti ti-info-circle" style="color:#2563eb;"></i> Resumen
            </div>
            <?php
            $totalInst   = count($instituciones);
            $conPasantes = count(array_filter($instituciones, fn($i) => (int)($i->pasantes_activos??0) > 0));
            ?>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:10px;">
                    <span style="font-size:.8rem;color:#64748b;">Total registradas</span>
                    <span style="font-weight:800;color:#1e293b;"><?= $totalInst ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f0fdf4;border-radius:10px;">
                    <span style="font-size:.8rem;color:#059669;">Con pasantes activos</span>
                    <span style="font-weight:800;color:#059669;"><?= $conPasantes ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:#f8fafc;border-radius:10px;">
                    <span style="font-size:.8rem;color:#64748b;">Sin pasantes</span>
                    <span style="font-weight:800;color:#94a3b8;"><?= $totalInst - $conPasantes ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- MODAL: Nueva Institución -->
<div id="modalNuevaInst" class="inst-modal-overlay">
<div class="inst-modal-box">
    <div class="im-head">
        <h2><i class="ti ti-building-bank" style="margin-right:7px;"></i>Nueva Institución</h2>
        <button class="im-close" onclick="cerrarModalInst('modalNuevaInst')"><i class="ti ti-x"></i></button>
    </div>
    <div class="im-body">
        <div class="f-group"><label class="f-label">Nombre *</label><input type="text" class="f-input" id="ni-nombre" placeholder="Ej. Universidad Central de Venezuela"></div>
        <div class="f-group"><label class="f-label">Tipo</label>
            <select class="f-input" id="ni-tipo">
                <option value="Universidad">Universidad</option><option value="Instituto">Instituto</option>
                <option value="Colegio Técnico">Colegio Técnico</option><option value="Otro">Otro</option>
            </select>
        </div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Contacto</label><input type="text" class="f-input" id="ni-contacto" placeholder="Nombre del representante"></div>
            <div class="f-group"><label class="f-label">Teléfono</label><input type="text" class="f-input" id="ni-telefono" placeholder="0414-0000000"></div>
        </div>
        <div style="display:flex;gap:10px;margin-top:6px;">
            <button class="f-btn-cancel" onclick="cerrarModalInst('modalNuevaInst')">Cancelar</button>
            <button class="f-btn-primary" onclick="guardarNuevaInst()"><i class="ti ti-plus"></i> Registrar</button>
        </div>
    </div>
</div>
</div>

<!-- MODAL: Editar Institución -->
<div id="modalEditarInst" class="inst-modal-overlay">
<div class="inst-modal-box">
    <div class="im-head">
        <h2><i class="ti ti-edit" style="margin-right:7px;"></i>Editar Institución</h2>
        <button class="im-close" onclick="cerrarModalInst('modalEditarInst')"><i class="ti ti-x"></i></button>
    </div>
    <div class="im-body">
        <input type="hidden" id="ei-id">
        <div class="f-group"><label class="f-label">Nombre *</label><input type="text" class="f-input" id="ei-nombre"></div>
        <div class="f-group"><label class="f-label">Tipo</label>
            <select class="f-input" id="ei-tipo">
                <option value="Universidad">Universidad</option><option value="Instituto">Instituto</option>
                <option value="Colegio Técnico">Colegio Técnico</option><option value="Otro">Otro</option>
            </select>
        </div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Contacto</label><input type="text" class="f-input" id="ei-contacto"></div>
            <div class="f-group"><label class="f-label">Teléfono</label><input type="text" class="f-input" id="ei-telefono"></div>
        </div>
        <div style="display:flex;gap:10px;margin-top:6px;">
            <button class="f-btn-cancel" onclick="cerrarModalInst('modalEditarInst')">Cancelar</button>
            <button class="f-btn-primary" onclick="guardarEdicionInst()"><i class="ti ti-device-floppy"></i> Guardar</button>
        </div>
    </div>
</div>
</div>

<script>
// [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
var INST_CSRF = '<?= $csrfToken ?>';

(function(){
    const datos = <?= json_encode(array_map(fn($t) => ['tipo'=>$t->tipo,'total'=>(int)$t->total], $porTipo)) ?>;
    const colors = {'Universidad':'#2563eb','Instituto':'#7c3aed','Colegio Técnico':'#d97706','Otro':'#059669'};
    const canvas = document.getElementById('instDonut');
    if (!canvas || !datos.length) return;
    const ctx = canvas.getContext('2d'), cx=60,cy=60,r=48,ri=28;
    const total = datos.reduce((s,t)=>s+t.total,0)||1;
    let angle = -Math.PI/2;
    datos.forEach(t => {
        const slice=(t.total/total)*2*Math.PI;
        ctx.beginPath();ctx.moveTo(cx,cy);ctx.arc(cx,cy,r,angle,angle+slice);ctx.closePath();
        ctx.fillStyle=colors[t.tipo]||'#94a3b8';ctx.fill();angle+=slice;
    });
    ctx.beginPath();ctx.arc(cx,cy,ri,0,2*Math.PI);ctx.fillStyle='white';ctx.fill();
    ctx.fillStyle='#1e293b';ctx.font='bold 15px sans-serif';ctx.textAlign='center';ctx.textBaseline='middle';ctx.fillText(total,cx,cy);
    const leg=document.getElementById('instLegend');
    if(leg) leg.innerHTML=datos.map(t=>`<div class="donut-leg-row"><div class="donut-dot" style="background:${colors[t.tipo]||'#94a3b8'}"></div><span style="color:#64748b;flex:1">${t.tipo}</span><strong>${t.total}</strong></div>`).join('');
})();

let tipoFiltro='Todos';
function filtrarPorTipo(btn){
    document.querySelectorAll('.pill-filter').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active'); tipoFiltro=btn.dataset.tipo; filtrarInstituciones();
}
function filtrarInstituciones(){
    const q=document.getElementById('buscarInst').value.toLowerCase();
    document.querySelectorAll('.inst-row').forEach(row=>{
        const ok=(tipoFiltro==='Todos'||row.dataset.tipo===tipoFiltro)&&row.dataset.nombre.includes(q);
        row.style.display=ok?'':'none';
    });
}

function abrirModalNuevaInst(){ document.getElementById('modalNuevaInst').classList.add('active'); }
function cerrarModalInst(id){ document.getElementById(id).classList.remove('active'); }
function abrirEditarInst(inst){
    document.getElementById('ei-id').value=inst.id; document.getElementById('ei-nombre').value=inst.nombre;
    document.getElementById('ei-tipo').value=inst.tipo; document.getElementById('ei-contacto').value=inst.contacto??'';
    document.getElementById('ei-telefono').value=inst.telefono??'';
    document.getElementById('modalEditarInst').classList.add('active');
}

function instToast(icon, title) {
    Swal.fire({ toast:true, position:'top-end', icon, title, showConfirmButton:false,
        timer:3500, timerProgressBar:true, customClass:{popup:'sgp-swal-toast'},
        didOpen: t => { t.addEventListener('mouseenter',Swal.stopTimer); t.addEventListener('mouseleave',Swal.resumeTimer); }
    });
}

async function guardarNuevaInst(){
    const nombre=document.getElementById('ni-nombre').value.trim();
    if(!nombre){ instToast('warning','El nombre es obligatorio.'); return; }
    const r=await fetch(URLROOT+'/actividades/crearInstitucion',{method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':INST_CSRF},
        body:JSON.stringify({csrf_token:INST_CSRF,nombre,tipo:document.getElementById('ni-tipo').value,
            contacto:document.getElementById('ni-contacto').value.trim(),telefono:document.getElementById('ni-telefono').value.trim()})
    });
    const res=await r.json();
    if(res.success){
        cerrarModalInst('modalNuevaInst');
        instToast('success', '¡Institución registrada correctamente!');
        setTimeout(()=>location.reload(), 1800);
    } else {
        instToast('error', res.message||'Error al registrar la institución.');
    }
}

async function guardarEdicionInst(){
    const nombre=document.getElementById('ei-nombre').value.trim();
    if(!nombre){ instToast('warning','El nombre es obligatorio.'); return; }
    const params=new URLSearchParams({csrf_token:INST_CSRF,id:document.getElementById('ei-id').value,nombre,
        tipo:document.getElementById('ei-tipo').value,contacto:document.getElementById('ei-contacto').value.trim(),
        telefono:document.getElementById('ei-telefono').value.trim()});
    const r=await fetch(URLROOT+'/actividades/editarInstitucion',{method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':INST_CSRF},body:params.toString()});
    const res=await r.json();
    if(res.success){
        cerrarModalInst('modalEditarInst');
        instToast('success', `"${nombre}" actualizada correctamente.`);
        setTimeout(()=>location.reload(), 1800);
    } else {
        instToast('error', res.message||'Error al actualizar la institución.');
    }
}

async function eliminarInst(id, nombre){
    const conf = await Swal.fire({
        title: '¿Eliminar institución?',
        html: `<span style="color:#475569">La institución <strong>"${nombre}"</strong> será eliminada permanentemente.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="ti ti-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    });
    if(!conf.isConfirmed) return;
    const r=await fetch(URLROOT+'/actividades/eliminarInstitucion',{method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':INST_CSRF},
        body:JSON.stringify({csrf_token:INST_CSRF,id})});
    const res=await r.json();
    if(res.success){
        const row=document.getElementById('inst-row-'+id);
        if(row){ row.style.transition='opacity .3s,transform .3s'; row.style.opacity='0'; row.style.transform='translateX(-20px)'; setTimeout(()=>row.remove(),300); }
        instToast('success', `"${nombre}" eliminada.`);
    } else {
        instToast('error', res.message||'No se pudo eliminar la institución.');
    }
}

document.addEventListener('keydown',e=>{if(e.key==='Escape') document.querySelectorAll('.inst-modal-overlay.active').forEach(m=>m.classList.remove('active'));});
document.querySelectorAll('.inst-modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('active');}));
</script>
