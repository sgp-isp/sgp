<?php
/**
 * Vista: Actividades Extras — Servicio Comunitario
 */
$actividades     = $data['actividades']     ?? [];
$instituciones   = $data['instituciones']   ?? [];
$statActivas     = $data['statActivas']     ?? 0;
$statFinalizadas = $data['statFinalizadas'] ?? 0;
$statParticip    = $data['statParticip']    ?? 0;
$csrfToken       = $data['csrfToken']       ?? '';
?>
<style>
@keyframes sc-fadeUp { from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)} }

.sc-wrap { width:100%; }

/* Banner */
.sc-banner { background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:20px;padding:28px 36px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;overflow:hidden; }
.sc-banner::before { content:'';position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,0.06);border-radius:50%; }
.sc-back { display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);backdrop-filter:blur(10px);color:white;padding:8px 16px;border-radius:9px;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s; }
.sc-back:hover { background:rgba(255,255,255,0.25);color:white; }
.sc-action-btn { display:inline-flex;align-items:center;gap:7px;background:white;color:#1e3a8a;border:none;padding:10px 20px;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;transition:all .2s;box-shadow:0 4px 12px rgba(0,0,0,0.15); }
.sc-action-btn:hover { transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,0.2); }

/* KPIs */
.sc-kpi-row { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px; }
.sc-kpi { background:white;border-radius:14px;padding:18px;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;justify-content:space-between;align-items:center;transition:all .3s;animation:sc-fadeUp .4s ease both; }
.sc-kpi:hover { transform:translateY(-3px); }

/* Toggle vista */
.view-toggle { display:flex;gap:0;background:#f1f5f9;border-radius:10px;padding:3px; }
.vt-btn { padding:7px 16px;border:none;border-radius:8px;background:transparent;color:#64748b;font-size:.82rem;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:5px; }
.vt-btn.active { background:white;color:#1e293b;box-shadow:0 2px 6px rgba(0,0,0,0.08); }

/* Grid actividades */
.sc-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:18px;animation:sc-fadeUp .4s .1s ease both; }
.sc-card { background:white;border-radius:16px;border:1px solid #f1f5f9;border-left:5px solid #10b981;overflow:hidden;transition:all .3s; }
.sc-card:hover { transform:translateY(-4px);box-shadow:0 14px 28px rgba(0,0,0,0.08); }
.sc-card-head { padding:16px 18px 10px; }
.sc-card-title { font-size:.9rem;font-weight:700;color:#0f172a;margin:0 0 8px; }
.sc-card-body { padding:0 18px 10px;display:flex;flex-direction:column;gap:7px; }
.sc-row { display:flex;align-items:center;gap:7px;color:#475569;font-size:.8rem;font-weight:500; }
.sc-prog-bar { height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;margin:8px 0; }
.sc-prog-fill { height:100%;border-radius:3px; }
.sc-foot { padding:10px 18px;border-top:1px solid #f1f5f9;background:#fafafa;display:flex;justify-content:space-between;align-items:center; }
.btn-ver-sc { display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#172554,#2563eb);color:white;border:none;padding:7px 14px;border-radius:8px;font-weight:600;font-size:.8rem;text-decoration:none;cursor:pointer;transition:all .2s; }
.btn-ver-sc:hover { opacity:.9;color:white; }
.btn-del-sc { display:inline-flex;align-items:center;gap:5px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:7px 12px;border-radius:8px;font-weight:600;font-size:.8rem;cursor:pointer;transition:all .2s; }
.btn-del-sc:hover { background:#dc2626;color:white;border-color:#dc2626; }

/* Vista lista */
.sc-list-card { background:white;border-radius:18px;padding:20px 22px;box-shadow:0 4px 20px rgba(0,0,0,0.05);animation:sc-fadeUp .4s .1s ease both; }
.sc-list-row  { display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid #f8fafc; }
.sc-list-row:last-child { border-bottom:none; }
.sc-list-dot  { width:12px;height:12px;border-radius:50%;flex-shrink:0; }

/* Timeline vertical */
.timeline-card { background:white;border-radius:18px;padding:22px;box-shadow:0 4px 20px rgba(0,0,0,0.05);margin-top:22px;animation:sc-fadeUp .4s .15s ease both; }
.tl-item { display:flex;gap:16px;padding-bottom:22px;position:relative; }
.tl-item:last-child { padding-bottom:0; }
.tl-item::before { content:'';position:absolute;left:17px;top:32px;bottom:0;width:2px;background:#f1f5f9; }
.tl-item:last-child::before { display:none; }
.tl-dot  { width:36px;height:36px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.9rem;z-index:1; }
.tl-body { flex:1;min-width:0;padding-top:6px; }
.tl-title { font-weight:700;color:#1e293b;font-size:.88rem;margin-bottom:3px; }
.tl-meta  { font-size:.75rem;color:#94a3b8; }

/* ═══════════════════════════════════════════════
   MODAL WIZARD — Premium UI
   ═══════════════════════════════════════════════ */
.sc-modal-overlay {
    display:none;position:fixed;inset:0;
    background:rgba(10,18,40,.75);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    z-index:9999;align-items:center;justify-content:center;padding:16px;
}
.sc-modal-overlay.active { display:flex; }

.sc-modal-box {
    background:white;border-radius:24px;
    width:100%;max-width:560px;max-height:90vh;
    display:flex;flex-direction:column;overflow:hidden;
    box-shadow:0 40px 100px rgba(10,18,40,.35),0 0 0 1px rgba(255,255,255,.08);
    animation:sc-fadeUp .3s cubic-bezier(.4,0,.2,1);
}

/* ── Header premium ── */
.sm-head {
    background:linear-gradient(135deg,#0f2460 0%,#1e3a8a 45%,#2563eb 100%);
    padding:22px 26px 18px;
    display:flex;justify-content:space-between;align-items:flex-start;
    flex-shrink:0;position:relative;overflow:hidden;
}
.sm-head::before {
    content:'';position:absolute;top:-30px;right:-30px;
    width:140px;height:140px;border-radius:50%;
    background:rgba(255,255,255,.06);
}
.sm-head::after {
    content:'';position:absolute;bottom:-20px;right:60px;
    width:80px;height:80px;border-radius:50%;
    background:rgba(255,255,255,.04);
}
.sm-icon-wrap {
    width:40px;height:40px;border-radius:12px;
    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.2);
    display:flex;align-items:center;justify-content:center;
    font-size:1.2rem;color:white;margin-bottom:10px;
    backdrop-filter:blur(4px);flex-shrink:0;
}
.sm-close {
    background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);
    color:white;width:30px;height:30px;border-radius:50%;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    font-size:.85rem;transition:all .2s;flex-shrink:0;margin-top:2px;
    position:relative;z-index:1;
}
.sm-close:hover { background:rgba(255,255,255,.3); }

/* ── Step progress bar ── */
.sm-progress {
    background:linear-gradient(135deg,#0f2460,#1e3a8a);
    padding:0 26px 20px;display:flex;align-items:center;flex-shrink:0;
}
.sm-prog-track { flex:1;display:flex;align-items:center;gap:0; }
.sm-dot {
    width:32px;height:32px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:.75rem;font-weight:800;transition:all .35s;
    box-shadow:0 2px 8px rgba(0,0,0,.2);position:relative;z-index:1;
}
.sm-dot.active { background:white;color:#1e3a8a;box-shadow:0 0 0 4px rgba(255,255,255,.25); }
.sm-dot.done   { background:#10b981;color:white;box-shadow:0 0 0 3px rgba(16,185,129,.3); }
.sm-dot.idle   { background:rgba(255,255,255,.15);color:rgba(255,255,255,.5);border:1px solid rgba(255,255,255,.2); }
.sm-line {
    flex:1;height:2px;margin:0 8px;
    background:rgba(255,255,255,.2);
    transition:background .35s;position:relative;overflow:hidden;
}
.sm-line.done { background:#10b981; }
.sm-step-lbl {
    display:flex;flex-direction:column;align-items:center;gap:4px;
    min-width:60px;
}
.sm-step-lbl span { font-size:.62rem;font-weight:700;color:rgba(255,255,255,.65);text-align:center;white-space:nowrap; }
.sm-step-lbl span.active-lbl { color:white; }

/* ── Body / Form ── */
.sm-body { overflow:hidden;flex:1;min-height:0; }
.sm-steps-wrap { display:flex;transition:transform .38s cubic-bezier(.4,0,.2,1);width:200%;height:100%; }
.sm-step { width:50%;padding:24px 26px 20px;overflow-y:auto;box-sizing:border-box;height:100%; }

/* Form elements */
.f-label {
    display:block;font-size:.68rem;font-weight:800;color:#64748b;
    margin-bottom:6px;text-transform:uppercase;letter-spacing:.6px;
}
.f-input {
    width:100%;padding:10px 14px;
    border:2px solid #e8edf5;border-radius:10px;
    font-size:.88rem;color:#0f172a;
    transition:border-color .2s,box-shadow .2s,background .2s;
    box-sizing:border-box;background:#f8fafc;font-family:inherit;
}
.f-input:focus {
    outline:none;border-color:#2563eb;background:white;
    box-shadow:0 0 0 4px rgba(37,99,235,.08);
}
.f-input::placeholder { color:#b0bcc8; }
.f-group { margin-bottom:16px; }
.f-row2  { display:grid;grid-template-columns:1fr 1fr;gap:14px; }

/* Botones */
.f-btn-primary {
    flex:2;padding:12px 20px;border:none;border-radius:11px;cursor:pointer;
    background:linear-gradient(135deg,#1e3a8a,#2563eb);
    color:white;font-size:.88rem;font-weight:700;
    display:flex;align-items:center;justify-content:center;gap:7px;
    transition:all .2s;font-family:inherit;
    box-shadow:0 4px 14px rgba(37,99,235,.35);
}
.f-btn-primary:hover { transform:translateY(-1px);box-shadow:0 8px 20px rgba(37,99,235,.4); }
.f-btn-primary:active { transform:translateY(0); }

.f-btn-cancel {
    flex:1;padding:11px 16px;
    background:transparent;color:#64748b;
    border:1.5px solid #e2e8f0;border-radius:11px;
    font-size:.85rem;font-weight:600;cursor:pointer;
    transition:all .2s;font-family:inherit;
    display:flex;align-items:center;justify-content:center;gap:6px;
}
.f-btn-cancel:hover { background:#f1f5f9;border-color:#cbd5e1;color:#374151; }

/* Action bar en el paso */
.sm-actions {
    display:flex;gap:10px;margin-top:18px;
    padding-top:16px;border-top:1px solid #f1f5f9;
}

/* ── Participante row ── */
.sc-part-row {
    background:white;border:1.5px solid #e8edf5;border-radius:14px;
    padding:14px 14px 10px;
    box-shadow:0 1px 4px rgba(15,23,42,.05);
    transition:border-color .2s,box-shadow .2s;
}
.sc-part-row:focus-within {
    border-color:#7c3aed;
    box-shadow:0 0 0 3px rgba(124,58,237,.08);
}
.sc-part-grid {
    display:grid;grid-template-columns:1fr 1fr 110px 32px;
    gap:8px;align-items:center;
}
.sc-part-input {
    padding:8px 10px;border:1.5px solid #e8edf5;border-radius:8px;
    font-size:.8rem;font-family:inherit;background:#f8fafc;
    width:100%;box-sizing:border-box;outline:none;
    transition:border-color .2s,background .2s,box-shadow .2s;
    color:#0f172a;
}
.sc-part-input:focus { border-color:#7c3aed;background:white;box-shadow:0 0 0 3px rgba(124,58,237,.08); }
.sc-part-input::placeholder { color:#b0bcc8; }

.sc-del-btn {
    width:32px;height:32px;border-radius:8px;
    border:1.5px solid #fecaca;background:#fff1f2;
    color:#ef4444;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    font-size:.85rem;transition:all .2s;flex-shrink:0;
}
.sc-del-btn:hover { background:#ef4444;color:white;border-color:#ef4444;transform:scale(1.08); }

/* Empty hint */
.sc-empty-hint {
    text-align:center;padding:28px 16px;color:#94a3b8;
    border:2px dashed #e8edf5;border-radius:14px;
    background:#fafcff;margin:4px 0;
}

/* Badges de estado */
.estado-badge { font-size:.7rem;padding:3px 10px;border-radius:100px;font-weight:700; }
.eb-activa { background:#dcfce7;color:#059669; }
.eb-finalizada { background:#f1f5f9;color:#64748b; }
.eb-otra { background:#fef9c3;color:#b45309; }

@media(max-width:1100px){ .sc-grid{grid-template-columns:1fr 1fr;} }
@media(max-width:640px)  { .sc-grid{grid-template-columns:1fr;} .sc-kpi-row{grid-template-columns:1fr;} }
</style>

<div class="sc-wrap">

<!-- BANNER -->
<div class="sc-banner">
    <div style="display:flex;align-items:center;gap:14px;z-index:1;flex-wrap:wrap;">
        <a href="<?= URLROOT ?>/actividades" class="sc-back"><i class="ti ti-arrow-left"></i> Volver</a>
        <div style="width:1px;height:28px;background:rgba(255,255,255,.2);"></div>
        <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:10px;">
            <i class="ti ti-hearts" style="font-size:24px;color:white;"></i>
        </div>
        <div>
            <h1 style="color:white;font-size:1.5rem;font-weight:800;margin:0;">Servicio Comunitario</h1>
            <p style="color:rgba(255,255,255,.7);margin:3px 0 0;font-size:.82rem;">Actividades externas e impacto social · <?= count($actividades) ?> actividades registradas</p>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;z-index:1;">
        <div class="view-toggle">
            <button class="vt-btn active" id="btnGrid" onclick="cambiarVista('grid')"><i class="ti ti-layout-grid"></i> Grid</button>
            <button class="vt-btn" id="btnLista" onclick="cambiarVista('lista')"><i class="ti ti-list"></i> Lista</button>
        </div>
        <button class="sc-action-btn" onclick="document.getElementById('modalNuevaActividad').classList.add('active')">
            <i class="ti ti-plus"></i> Nueva Actividad
        </button>
    </div>
</div>

<!-- KPIs -->
<div class="sc-kpi-row">
    <div class="sc-kpi" style="border-left:4px solid #059669;" onmouseover="this.style.boxShadow='0 12px 24px rgba(5,150,105,.2)'" onmouseout="this.style.boxShadow=''">
        <div><p style="color:#64748b;font-size:.75rem;margin:0 0 5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Actividades en Curso</p>
        <h2 style="font-size:2rem;font-weight:800;color:#059669;margin:0;"><?= $statActivas ?></h2></div>
        <div style="background:#f0fdf4;color:#059669;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="ti ti-activity"></i></div>
    </div>
    <div class="sc-kpi" style="border-left:4px solid #2563eb;" onmouseover="this.style.boxShadow='0 12px 24px rgba(37,99,235,.2)'" onmouseout="this.style.boxShadow=''">
        <div><p style="color:#64748b;font-size:.75rem;margin:0 0 5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Total Participantes</p>
        <h2 style="font-size:2rem;font-weight:800;color:#2563eb;margin:0;"><?= $statParticip ?></h2></div>
        <div style="background:#eff6ff;color:#2563eb;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="ti ti-users"></i></div>
    </div>
    <div class="sc-kpi" style="border-left:4px solid #64748b;" onmouseover="this.style.boxShadow='0 12px 24px rgba(100,116,139,.2)'" onmouseout="this.style.boxShadow=''">
        <div><p style="color:#64748b;font-size:.75rem;margin:0 0 5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Finalizadas</p>
        <h2 style="font-size:2rem;font-weight:800;color:#64748b;margin:0;"><?= $statFinalizadas ?></h2></div>
        <div style="background:#f1f5f9;color:#64748b;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="ti ti-check"></i></div>
    </div>
</div>

<!-- VISTA GRID -->
<div id="vistaGrid">
<?php if (empty($actividades)): ?>
<div style="background:white;border-radius:18px;padding:60px 20px;text-align:center;color:#94a3b8;box-shadow:0 4px 20px rgba(0,0,0,0.05);">
    <i class="ti ti-hearts" style="font-size:3rem;display:block;margin-bottom:14px;opacity:.3;"></i>
    <div style="font-weight:600;font-size:1rem;">Sin actividades registradas</div>
    <div style="font-size:.85rem;margin-top:6px;">Crea la primera actividad de servicio comunitario</div>
</div>
<?php else: ?>
<div class="sc-grid">
<?php foreach ($actividades as $act):
    $colorBorder = $act->estado === 'Activa' ? '#10b981' : ($act->estado === 'Finalizada' ? '#64748b' : '#d97706');
    $eBadge = $act->estado === 'Activa' ? 'eb-activa' : ($act->estado === 'Finalizada' ? 'eb-finalizada' : 'eb-otra');
    // Progreso temporal
    $tProg = 0;
    if ($act->fecha_inicio && $act->fecha_fin) {
        $ini = new DateTime($act->fecha_inicio); $fin = new DateTime($act->fecha_fin); $hoy = new DateTime();
        $totD = max(1, $fin->diff($ini)->days); $trD = max(0, min($totD, $hoy->diff($ini)->days));
        if ($hoy > $fin) $trD = $totD; if ($hoy < $ini) $trD = 0;
        $tProg = round($trD / $totD * 100);
    }
    $progColor = $act->estado === 'Activa' ? '#10b981' : '#64748b';
?>
<div class="sc-card" style="border-left-color:<?= $colorBorder ?>;">
    <div class="sc-card-head">
        <h5 class="sc-card-title"><?= htmlspecialchars($act->nombre) ?></h5>
        <span class="estado-badge <?= $eBadge ?>">
            <?php if ($act->estado === 'Activa'): ?><span style="display:inline-block;width:6px;height:6px;background:#10b981;border-radius:50%;margin-right:4px;vertical-align:middle;"></span><?php endif; ?>
            <?= htmlspecialchars($act->estado) ?>
        </span>
        <?php if ($act->tipo !== 'Servicio Comunitario'): ?>
        <span style="background:#f1f5f9;color:#64748b;font-size:.67rem;font-weight:700;padding:2px 8px;border-radius:100px;margin-left:4px;"><?= htmlspecialchars($act->tipo) ?></span>
        <?php endif; ?>
    </div>
    <?php if ($act->fecha_inicio && $act->fecha_fin): ?>
    <div style="padding:0 18px 4px;">
        <div class="sc-prog-bar"><div class="sc-prog-fill" style="width:<?= $tProg ?>%;background:<?= $progColor ?>;"></div></div>
        <div style="font-size:.69rem;color:#94a3b8;text-align:right;"><?= $tProg ?>% del período</div>
    </div>
    <?php endif; ?>
    <div class="sc-card-body">
        <?php if ($act->institucion_nombre): ?>
        <div class="sc-row"><i class="ti ti-building-bank" style="color:#7c3aed;"></i><?= htmlspecialchars($act->institucion_nombre) ?></div>
        <?php endif; ?>
        <div class="sc-row"><i class="ti ti-users" style="color:#2563eb;"></i><?= (int)$act->total_participantes ?> participantes</div>
        <div class="sc-row"><i class="ti ti-calendar" style="color:#d97706;"></i>
            <?= date('d/m/Y', strtotime($act->fecha_inicio)) ?>
            <?= $act->fecha_fin ? ' → '.date('d/m/Y', strtotime($act->fecha_fin)) : '' ?>
        </div>
    </div>
    <div class="sc-foot">
        <a href="<?= URLROOT ?>/actividades/ver/<?= $act->id ?>" class="btn-ver-sc">
            <i class="ti ti-eye"></i> Ver detalle
        </a>
        <button type="button" class="btn-del-sc" onclick="confirmarEliminarActividad(<?= (int)$act->id ?>, '<?= addslashes(htmlspecialchars($act->nombre)) ?>')">
            <i class="ti ti-trash"></i> Eliminar
        </button>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- VISTA LISTA -->
<div id="vistaLista" style="display:none;">
<div class="sc-list-card">
    <?php if (empty($actividades)): ?>
    <div style="text-align:center;padding:40px;color:#94a3b8;">Sin actividades registradas.</div>
    <?php else: ?>
    <?php foreach ($actividades as $act):
        $dotColor = $act->estado === 'Activa' ? '#10b981' : ($act->estado === 'Finalizada' ? '#64748b' : '#d97706');
    ?>
    <div class="sc-list-row">
        <div class="sc-list-dot" style="background:<?= $dotColor ?>;"></div>
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;color:#1e293b;font-size:.88rem;"><?= htmlspecialchars($act->nombre) ?></div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;display:flex;gap:10px;flex-wrap:wrap;">
                <span><?= htmlspecialchars($act->tipo) ?></span>
                <?php if ($act->institucion_nombre): ?><span>· <?= htmlspecialchars($act->institucion_nombre) ?></span><?php endif; ?>
                <span>· <?= (int)$act->total_participantes ?> participantes</span>
                <span>· <?= date('d/m/Y', strtotime($act->fecha_inicio)) ?></span>
            </div>
        </div>
        <span class="estado-badge <?= $act->estado === 'Activa' ? 'eb-activa' : ($act->estado === 'Finalizada' ? 'eb-finalizada' : 'eb-otra') ?>"><?= htmlspecialchars($act->estado) ?></span>
        <a href="<?= URLROOT ?>/actividades/ver/<?= $act->id ?>" class="btn-ver-sc" style="margin-left:10px;"><i class="ti ti-eye"></i></a>
        <button type="button" class="btn-del-sc" style="margin-left:6px;" onclick="confirmarEliminarActividad(<?= (int)$act->id ?>, '<?= addslashes(htmlspecialchars($act->nombre)) ?>')"><i class="ti ti-trash"></i></button>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>

<!-- TIMELINE VERTICAL -->
<?php if (!empty($actividades)): ?>
<div class="timeline-card">
    <div style="font-size:.88rem;font-weight:700;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
        <i class="ti ti-timeline" style="color:#059669;"></i> Línea de Tiempo
    </div>
    <?php
    $sortedActs = $actividades;
    usort($sortedActs, fn($a,$b) => strcmp($a->fecha_inicio, $b->fecha_inicio));
    foreach ($sortedActs as $act):
        $dotBg    = $act->estado === 'Activa' ? '#f0fdf4' : '#f1f5f9';
        $dotColor = $act->estado === 'Activa' ? '#059669' : '#64748b';
        $dotIcon  = $act->estado === 'Activa' ? 'ti-heart' : 'ti-check';
    ?>
    <div class="tl-item">
        <div class="tl-dot" style="background:<?= $dotBg ?>;color:<?= $dotColor ?>;border:2px solid <?= $dotColor ?>;">
            <i class="ti <?= $dotIcon ?>" style="font-size:.85rem;"></i>
        </div>
        <div class="tl-body">
            <div class="tl-title"><?= htmlspecialchars($act->nombre) ?>
                <span class="estado-badge <?= $act->estado === 'Activa' ? 'eb-activa' : 'eb-finalizada' ?>" style="margin-left:8px;"><?= htmlspecialchars($act->estado) ?></span>
            </div>
            <div class="tl-meta">
                <?= date('d/m/Y', strtotime($act->fecha_inicio)) ?><?= $act->fecha_fin ? ' → '.date('d/m/Y', strtotime($act->fecha_fin)) : '' ?>
                <?= $act->institucion_nombre ? ' · '.htmlspecialchars($act->institucion_nombre) : '' ?>
                · <?= (int)$act->total_participantes ?> participantes
            </div>
        </div>
        <a href="<?= URLROOT ?>/actividades/ver/<?= $act->id ?>" style="color:#059669;font-size:.78rem;font-weight:700;text-decoration:none;white-space:nowrap;">Ver →</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</div><!-- /sc-wrap -->

<!-- Formulario oculto para eliminar actividad -->
<form id="formEliminarActividad" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
</form>

<!-- MODAL WIZARD: Nueva Actividad SC — Premium 2 pasos -->
<div id="modalNuevaActividad" class="sc-modal-overlay" onclick="if(event.target===this)scCerrarModal()">
<div class="sc-modal-box">

    <!-- ── Header ── -->
    <div class="sm-head">
        <div style="display:flex;align-items:flex-start;gap:14px;position:relative;z-index:1;">
            <div class="sm-icon-wrap" id="smIconWrap">
                <i class="ti ti-clipboard-text" id="smIcon"></i>
            </div>
            <div>
                <div id="smStepTitle" style="font-size:1.05rem;font-weight:800;color:white;letter-spacing:-.3px;">
                    Datos de la Actividad
                </div>
                <div id="smStepSub" style="font-size:.72rem;color:rgba(255,255,255,.6);margin-top:3px;font-weight:500;">
                    Paso 1 de 2 — Completa la información general
                </div>
            </div>
        </div>
        <button class="sm-close" onclick="scCerrarModal()"><i class="ti ti-x"></i></button>
    </div>

    <!-- ── Progress bar con labels ── -->
    <div class="sm-progress">
        <div class="sm-prog-track">
            <div class="sm-step-lbl">
                <div id="sdot1" class="sm-dot active">1</div>
                <span id="slbl1" class="active-lbl">Actividad</span>
            </div>
            <div id="sline1" class="sm-line"></div>
            <div class="sm-step-lbl">
                <div id="sdot2" class="sm-dot idle">2</div>
                <span id="slbl2">Integrantes</span>
            </div>
        </div>
    </div>

    <!-- ── Body (slider) ── -->
    <div class="sm-body">
        <form id="formCrearActividad" method="POST" action="<?= URLROOT ?>/actividades/crear">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="sm-steps-wrap" id="smStepsWrap">

            <!-- ══ PASO 1: Datos de la actividad ══ -->
            <div class="sm-step" id="smStep1">

                <div class="f-group">
                    <label class="f-label">Nombre / Título *</label>
                    <input type="text" id="sc-nombre" name="nombre" class="f-input" required
                           placeholder="Ej. Brigada de mantenimiento escolar...">
                </div>

                <div class="f-row2">
                    <div class="f-group">
                        <label class="f-label">Tipo de Actividad</label>
                        <select name="tipo" class="f-input">
                            <option value="Servicio Comunitario">🤝 Servicio Comunitario</option>
                            <option value="Mantenimiento">🔧 Mantenimiento</option>
                            <option value="Otro">📋 Otro</option>
                        </select>
                    </div>
                    <div class="f-group">
                        <label class="f-label">Institución de Origen</label>
                        <select name="institucion_id" id="sc-inst" class="f-input">
                            <option value="">— Sin especificar —</option>
                            <?php foreach ($instituciones as $i): ?>
                            <option value="<?= $i->id ?>"><?= htmlspecialchars($i->nombre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="f-row2">
                    <div class="f-group">
                        <label class="f-label">Fecha de Inicio *</label>
                        <input type="date" name="fecha_inicio" id="sc-fi" class="f-input" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="f-group">
                        <label class="f-label">Fecha de Fin</label>
                        <input type="date" name="fecha_fin" class="f-input">
                    </div>
                </div>

                <div class="f-group">
                    <label class="f-label">Descripción <span style="font-weight:500;text-transform:none;letter-spacing:0;">(opcional)</span></label>
                    <textarea name="descripcion" class="f-input" rows="2"
                              placeholder="Objetivo o contexto de la actividad..."></textarea>
                </div>

                <div class="sm-actions">
                    <button type="button" class="f-btn-cancel" onclick="scCerrarModal()">
                        <i class="ti ti-x"></i> Cancelar
                    </button>
                    <button type="button" class="f-btn-primary" onclick="scIrPaso2()">
                        Continuar <i class="ti ti-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ══ PASO 2: Integrantes ══ -->
            <div class="sm-step" id="smStep2">

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <div>
                        <div style="font-size:.92rem;font-weight:800;color:#0f172a;letter-spacing:-.2px;">
                            Integrantes del Grupo
                        </div>
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:3px;font-weight:500;">
                            Cédula única por integrante · Verificación en tiempo real
                        </div>
                    </div>
                    <button type="button" onclick="scAgregarFila()"
                            style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;
                                   border-radius:22px;border:1.5px solid #7c3aed;background:transparent;
                                   color:#7c3aed;font-size:.76rem;font-weight:700;cursor:pointer;
                                   font-family:inherit;transition:all .18s;white-space:nowrap;"
                            onmouseover="this.style.background='#7c3aed';this.style.color='white';this.style.boxShadow='0 4px 12px rgba(124,58,237,.3)'"
                            onmouseout="this.style.background='transparent';this.style.color='#7c3aed';this.style.boxShadow='none'">
                        <i class="ti ti-user-plus"></i> Agregar
                    </button>
                </div>

                <div id="scFilas" style="display:flex;flex-direction:column;gap:10px;"></div>

                <div id="scEmptyHint" class="sc-empty-hint">
                    <i class="ti ti-users" style="font-size:2rem;color:#cbd5e1;display:block;margin-bottom:8px;"></i>
                    <div style="font-weight:700;color:#64748b;font-size:.85rem;">Sin integrantes aún</div>
                    <div style="font-size:.73rem;color:#94a3b8;margin-top:4px;">
                        Haz clic en <strong style="color:#7c3aed;">Agregar</strong> para incluir participantes.<br>
                        También puedes crear la actividad sin integrantes.
                    </div>
                </div>

                <div class="sm-actions">
                    <button type="button" class="f-btn-cancel" onclick="scIrPaso1()">
                        <i class="ti ti-arrow-left"></i> Volver
                    </button>
                    <button type="submit" id="scBtnCrear" class="f-btn-primary">
                        <i class="ti ti-check"></i> Crear Actividad
                    </button>
                </div>

            </div>
        </div><!-- /sm-steps-wrap -->
        </form>
    </div>
</div>
</div>

<script>
// ════════════════════════════════════════════════════
// WIZARD: Nueva Actividad SC — 2 pasos con slide
// ════════════════════════════════════════════════════
// [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
var URLROOT_SC = '<?= URLROOT ?>';
var scPaso = 1;
var scFilaIdx = 0;
var cedulasEnModal = new Set(); // Para evitar duplicados dentro del modal

function scCerrarModal() {
    document.getElementById('modalNuevaActividad').classList.remove('active');
    // Reset completo
    document.getElementById('formCrearActividad').reset();
    document.getElementById('scFilas').innerHTML = '';
    cedulasEnModal.clear();
    scFilaIdx = 0;
    scIrPaso1(false);
}

function scIrPaso2() {
    const nombre = document.getElementById('sc-nombre').value.trim();
    if (!nombre) {
        const inp = document.getElementById('sc-nombre');
        inp.focus();
        inp.style.borderColor = '#ef4444';
        inp.style.boxShadow   = '0 0 0 4px rgba(239,68,68,.1)';
        inp.addEventListener('input', () => { inp.style.borderColor=''; inp.style.boxShadow=''; }, { once:true });
        return;
    }
    scPaso = 2;
    document.getElementById('smStepsWrap').style.transform = 'translateX(-50%)';
    document.getElementById('smStepTitle').textContent = 'Integrantes del Grupo';
    document.getElementById('smStepSub').textContent   = 'Paso 2 de 2 — Agrega los participantes';
    document.getElementById('smIcon').className        = 'ti ti-users';
    document.getElementById('smIconWrap').style.background = 'rgba(167,139,250,.25)';
    document.getElementById('sdot1').className  = 'sm-dot done';
    document.getElementById('sline1').className = 'sm-line done';
    document.getElementById('sdot2').className  = 'sm-dot active';
    document.getElementById('slbl1').className  = '';
    document.getElementById('slbl2').className  = 'active-lbl';
    if (document.getElementById('scFilas').children.length === 0) scAgregarFila();
}

function scIrPaso1(animate = true) {
    scPaso = 1;
    if (animate) {
        document.getElementById('smStepsWrap').style.transform = 'translateX(0)';
    } else {
        document.getElementById('smStepsWrap').style.transition = 'none';
        document.getElementById('smStepsWrap').style.transform  = 'translateX(0)';
        setTimeout(() => document.getElementById('smStepsWrap').style.transition = '', 10);
    }
    document.getElementById('smStepTitle').textContent = 'Datos de la Actividad';
    document.getElementById('smStepSub').textContent   = 'Paso 1 de 2 — Completa la información general';
    document.getElementById('smIcon').className        = 'ti ti-clipboard-text';
    document.getElementById('smIconWrap').style.background = 'rgba(255,255,255,.15)';
    document.getElementById('sdot1').className  = 'sm-dot active';
    document.getElementById('sline1').className = 'sm-line';
    document.getElementById('sdot2').className  = 'sm-dot idle';
    document.getElementById('slbl1').className  = 'active-lbl';
    document.getElementById('slbl2').className  = '';
}

var scVerifyTimer = null; // [SGP-FIX-PJAX-REDECL]

function scAgregarFila() {
    const hint = document.getElementById('scEmptyHint');
    if (hint) hint.style.display = 'none';

    const fila = document.createElement('div');
    fila.className = 'sc-part-row';
    fila.innerHTML = `
        <div class="sc-part-grid">
            <input type="text" placeholder="Nombres *" data-field="nombres" class="sc-part-input">
            <input type="text" placeholder="Apellidos *" data-field="apellidos" class="sc-part-input">
            <div style="position:relative;">
                <input type="text" placeholder="Cédula *" data-field="cedula" class="sc-part-input"
                       style="padding-right:26px;"
                       oninput="scVerificarCedulaRT(this)">
                <span class="sc-ced-ico" style="position:absolute;right:7px;top:50%;
                      transform:translateY(-50%);font-size:.85rem;pointer-events:none;"></span>
            </div>
            <button type="button" class="sc-del-btn" onclick="scEliminarFila(this)">
                <i class="ti ti-trash"></i>
            </button>
        </div>
        <div class="sc-ced-msg" style="display:none;font-size:.71rem;font-weight:700;
             padding:3px 8px;border-radius:6px;margin-top:6px;"></div>`;
    document.getElementById('scFilas').appendChild(fila);
    fila.querySelector('[data-field="nombres"]').focus();
}

function scEliminarFila(btn) {
    const fila = btn.closest('.sc-part-row');
    fila.remove();
    if (document.getElementById('scFilas').children.length === 0) {
        document.getElementById('scEmptyHint').style.display = '';
    }
}

function scVerificarCedulaRT(input) {
    const fila    = input.closest('.sc-part-row');
    const ico     = fila.querySelector('.sc-ced-ico');
    const msg     = fila.querySelector('.sc-ced-msg');
    const ced     = input.value.trim();

    // Reset visual
    input.style.borderColor = '#7c3aed';
    ico.textContent = '';
    msg.style.display = 'none';

    if (!ced || ced.length < 6) return;

    // 1. Duplicado dentro del modal
    let count = 0;
    document.querySelectorAll('#scFilas [data-field="cedula"]').forEach(el => {
        if (el !== input && el.value.trim() === ced) count++;
    });
    if (count > 0) {
        input.style.borderColor = '#dc2626';
        ico.textContent = '✗'; ico.style.color = '#dc2626';
        msg.style.cssText += ';display:block;background:#fee2e2;color:#dc2626;';
        msg.textContent = 'Esta cédula ya fue ingresada en otro integrante.';
        return;
    }

    // 2. Verificar en BD con debounce
    clearTimeout(scVerifyTimer);
    ico.textContent = '…'; ico.style.color = '#94a3b8';
    scVerifyTimer = setTimeout(() => {
        fetch(`${URLROOT_SC}/actividades/verificarCedula?cedula=${encodeURIComponent(ced)}`)
        .then(r => r.json())
        .then(res => {
            if (res.existe) {
                input.style.borderColor = '#f59e0b';
                ico.textContent = '⚠'; ico.style.color = '#f59e0b';
                msg.style.cssText = 'display:block;background:#fef3c7;color:#b45309;font-size:.71rem;font-weight:700;padding:3px 8px;border-radius:6px;margin-top:2px;';
                msg.textContent = res.mensaje + (res.nombre ? ` (${res.nombre.trim()})` : '');
            } else {
                input.style.borderColor = '#10b981';
                ico.textContent = '✓'; ico.style.color = '#10b981';
                msg.style.display = 'none';
            }
        })
        .catch(() => { ico.textContent = ''; });
    }, 450);
}

// Submit: serializar filas válidas como hidden inputs
document.getElementById('formCrearActividad').addEventListener('submit', function(e) {
    document.querySelectorAll('.sc-hdn-int').forEach(el => el.remove());
    let i = 0;
    let hayError = false;
    document.querySelectorAll('.sc-part-row').forEach(fila => {
        const n = fila.querySelector('[data-field="nombres"]').value.trim();
        const a = fila.querySelector('[data-field="apellidos"]').value.trim();
        const c = fila.querySelector('[data-field="cedula"]').value.trim();
        if (!n || !a || !c) { hayError = true; return; }
        ['nombres','apellidos','cedula'].forEach(f => {
            const val = f==='nombres'?n : f==='apellidos'?a : c;
            const inp = document.createElement('input');
            inp.type='hidden'; inp.className='sc-hdn-int';
            inp.name=`participantes[${i}][${f}]`; inp.value=val;
            this.appendChild(inp);
        });
        i++;
    });
    if (hayError) {
        e.preventDefault();
        alert('Completa todos los campos de cada integrante (Nombres, Apellidos y Cédula).');
    }
});

function cambiarVista(modo) {
    const grid  = document.getElementById('vistaGrid');
    const lista = document.getElementById('vistaLista');
    const btnG  = document.getElementById('btnGrid');
    const btnL  = document.getElementById('btnLista');
    if (modo === 'grid') {
        grid.style.display=''; lista.style.display='none';
        btnG.classList.add('active'); btnL.classList.remove('active');
    } else {
        grid.style.display='none'; lista.style.display='';
        btnL.classList.add('active'); btnG.classList.remove('active');
    }
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.sc-modal-overlay.active').forEach(m => m.classList.remove('active'));
});
document.querySelectorAll('.sc-modal-overlay').forEach(m => m.addEventListener('click', e => { if(e.target===m) m.classList.remove('active'); }));

function confirmarEliminarActividad(id, nombre) {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        title: '¿Eliminar actividad?',
        html: `<p style="color:#64748b;font-size:.95rem;">Estás a punto de eliminar permanentemente la actividad <strong>${nombre}</strong> y todos sus participantes registrados.<br><br>Esta acción <strong>no se puede deshacer</strong>.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        customClass: { popup: 'sgp-swal-modal' },
        didOpen: () => { document.querySelector('.swal2-popup').style.borderRadius = '20px'; }
    }).then(result => {
        if (!result.isConfirmed) return;
        const form = document.getElementById('formEliminarActividad');
        form.action = `<?= URLROOT ?>/actividades/eliminar/${id}`;
        form.submit();
    });
}
</script>
