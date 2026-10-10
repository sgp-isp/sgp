<?php
/**
 * Vista: Actividades Extras — Pasantías Cortas
 */
$pasantesCortos      = $data['pasantesCortos']      ?? [];
$instituciones       = $data['instituciones']        ?? [];
$tutores             = $data['tutores']              ?? [];
$departamentos       = $data['departamentos']        ?? [];
$periodos            = $data['periodos']             ?? [];
$periodoCortoActivo  = $data['periodoCortoActivo']   ?? null;
$statActivos         = $data['statActivos']          ?? 0;
$statFinalizados     = $data['statFinalizados']      ?? 0;
$statPromPct         = $data['statPromPct']          ?? 0;
$kpiCortosActivos    = $data['kpiCortosActivos']     ?? 0;
$ausentesMesCortos   = $data['ausentesMesCortos']    ?? [];
$csrfToken           = $data['csrfToken']            ?? '';
$vistaAsist          = $data['vistaAsist']           ?? null;
$paramsUrl           = $data['paramsUrl']             ?? ['fecha'=>date('Y-m-d'),'semana'=>date('W'),'mes'=>date('m'),'anio'=>date('Y')];
$hoyAsist            = $data['hoyAsist']              ?? date('Y-m-d');
$registrosAsist      = $data['registrosAsist']        ?? [];
$misPasantesAsist    = $data['misPasantesAsist']      ?? [];
$sinMarcarAsist      = $data['sinMarcarAsist']        ?? [];
$presentesAsist      = $data['presentesAsist']        ?? 0;
$ausentesAsist       = $data['ausentesAsist']         ?? 0;
$justificadosAsist   = $data['justificadosAsist']     ?? 0;
$totalActivosAsist   = $data['totalActivosAsist']     ?? 0;
$datosSemanales      = $data['datosSemanales']        ?? [];
$navSemana           = $data['navSemana']             ?? ['ant_url'=>'#','sig_url'=>'#','texto'=>''];
$nombreMes           = $data['nombreMes']             ?? '';
$urlMesAnt           = $data['urlMesAnt']             ?? '#';
$urlMesSig           = $data['urlMesSig']             ?? '#';
$bentoMensual        = $data['bentoMensual']          ?? [];
$healthIndex         = $data['healthIndex']           ?? 100;
$resumenTotal        = $data['resumenTotal']          ?? [];
$periodosFiltroAsist = $data['periodosFiltroAsist']   ?? [];
$periodoSeleccionado = $data['periodoSeleccionado']   ?? null;
$periodoIdSel        = $data['periodoIdSel']          ?? 0;
$esDiaria   = $vistaAsist === 'diaria';
$esSemanal  = $vistaAsist === 'semanal';
$esMensual  = $vistaAsist === 'mensual';
$esTotal    = $vistaAsist === 'total';
$sinMarcarCount   = count($sinMarcarAsist);
$porcAsistencia   = $totalActivosAsist > 0 ? round(($presentesAsist + $justificadosAsist) / $totalActivosAsist * 100) : 0;
$mesLabel = ['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril',
             'May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto',
             'September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre'][date('F')] . ' ' . date('Y');
?>
<style>
@keyframes pc-fadeUp { from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)} }
@keyframes pc-pulse  { 0%,100%{opacity:1}50%{opacity:.4} }

.pc-wrap { width:100%; }

/* Banner */
.pc-banner { background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:20px;padding:28px 36px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;overflow:hidden; }
.pc-back   { display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);backdrop-filter:blur(10px);color:white;padding:8px 16px;border-radius:9px;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s; }
.pc-back:hover { background:rgba(255,255,255,0.25);color:white; }
.pc-nuevo-btn { display:inline-flex;align-items:center;gap:7px;background:white;color:#2563eb;border:none;padding:10px 20px;border-radius:10px;font-weight:700;font-size:.88rem;cursor:pointer;transition:all .2s;box-shadow:0 4px 12px rgba(0,0,0,0.15); }
.pc-nuevo-btn:hover { transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,0.2); }

/* KPIs */
.pc-kpi-row { display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px; }
.pc-kpi { background:white;border-radius:14px;padding:18px;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;justify-content:space-between;align-items:center;transition:all .3s;animation:pc-fadeUp .4s ease both; }
.pc-kpi:hover { transform:translateY(-3px); }

/* Timeline período */
.pc-timeline-card { background:white;border-radius:18px;padding:20px 24px;box-shadow:0 4px 20px rgba(0,0,0,0.05);margin-bottom:22px;animation:pc-fadeUp .4s .05s ease both; }
.pc-timeline-bar  { height:10px;background:#f1f5f9;border-radius:100px;overflow:hidden;margin:14px 0 8px; }
.pc-timeline-fill { height:100%;background:linear-gradient(90deg,#34d399,#2563eb);border-radius:100px;transition:width 1.2s cubic-bezier(.4,0,.2,1); }

/* Filtros estado */
.estado-pills { display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap; }
.epill { padding:6px 16px;border-radius:20px;border:1.5px solid #e2e8f0;font-size:.8rem;font-weight:700;cursor:pointer;background:white;color:#64748b;transition:all .2s; }
.epill:hover { border-color:#2563eb;color:#2563eb; }
.epill.active { background:#2563eb;color:white;border-color:#2563eb; }

/* Tabla */
.pc-table-card { background:white;border-radius:18px;padding:20px 22px;box-shadow:0 4px 20px rgba(0,0,0,0.05);margin-bottom:22px;animation:pc-fadeUp .4s .1s ease both; }
.pc-table { width:100%;border-collapse:collapse;font-size:.84rem; }
.pc-table th { padding:10px 12px;text-align:left;font-size:.71rem;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #f1f5f9; }
.pc-table td { padding:11px 12px;border-bottom:1px solid #f8fafc;vertical-align:middle; }
.pc-table tr:last-child td { border-bottom:none; }
.pc-table tr:hover td { background:#fafafa; }
.pct-name  { font-weight:700;color:#1e293b; }
.pct-sub   { font-size:.73rem;color:#94a3b8;margin-top:2px; }
.pct-bar   { display:flex;align-items:center;gap:7px; }
.pct-track { flex:1;height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden; }
.pct-fill  { height:100%;border-radius:3px;background:linear-gradient(90deg,#2563eb,#10b981); }
.pct-pct   { font-size:.73rem;font-weight:700;color:#64748b;min-width:30px;text-align:right; }
.estado-act  { background:#dcfce7;color:#059669;font-size:.7rem;padding:3px 10px;border-radius:100px;font-weight:700; }
.estado-fin  { background:#f1f5f9;color:#64748b;font-size:.7rem;padding:3px 10px;border-radius:100px;font-weight:700; }
.estado-ret  { background:#fee2e2;color:#dc2626;font-size:.7rem;padding:3px 10px;border-radius:100px;font-weight:700; }
.btn-pc { width:30px;height:30px;border-radius:8px;border:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:.88rem;transition:all .2s; }
.pc-field-msg { display:block;font-size:.72rem;margin-top:3px;min-height:14px;transition:all .2s; }
.pc-field-msg.error   { color:#ef4444; }
.pc-field-msg.success { color:#10b981; }
.btn-eye  { background:#eff6ff;color:#2563eb; } .btn-eye:hover  { background:#2563eb;color:white; }
.btn-edit { background:#f0fdf4;color:#059669; } .btn-edit:hover { background:#059669;color:white; }
.btn-asist{ background:#fdf4ff;color:#7c3aed; } .btn-asist:hover{ background:#7c3aed;color:white; }
.btn-del  { background:#fff1f2;color:#ef4444; } .btn-del:hover  { background:#ef4444;color:white; }
/* Chips selector pasante */
.pasante-chip { border:2px solid #e2e8f0;border-radius:14px;padding:14px;cursor:pointer;background:#fafcff;transition:all .2s;position:relative;overflow:hidden; }
.pasante-chip:hover { border-color:#c4b5fd;box-shadow:0 4px 12px rgba(124,58,237,.1); }
.sc-part-row { background:white;border:1.5px solid #e8edf5;border-radius:14px;padding:14px 14px 10px;box-shadow:0 1px 4px rgba(15,23,42,.05);transition:border-color .2s; }
.sc-part-row:focus-within { border-color:#7c3aed; }
.sc-part-grid { display:grid;grid-template-columns:1fr 1fr 110px 32px;gap:8px;align-items:center; }
.sc-part-input { padding:8px 10px;border:1.5px solid #e8edf5;border-radius:8px;font-size:.8rem;font-family:inherit;background:#f8fafc;width:100%;box-sizing:border-box;outline:none;transition:border-color .2s; }
.sc-part-input:focus { border-color:#7c3aed;background:white; }
.sc-del-btn { width:32px;height:32px;border-radius:8px;border:1.5px solid #fecaca;background:#fff1f2;color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .2s;flex-shrink:0; }
.sc-del-btn:hover { background:#ef4444;color:white; }

/* Asistencias */
.asist-card  { background:white;border-radius:18px;padding:20px 24px;box-shadow:0 4px 20px rgba(0,0,0,0.05);animation:pc-fadeUp .4s .15s ease both; }
.asist-tabs  { display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap; }
.asist-tab   { padding:7px 18px;border-radius:20px;border:1.5px solid #e2e8f0;font-size:.82rem;font-weight:700;cursor:pointer;background:white;color:#64748b;transition:all .2s; }
.asist-tab:hover  { border-color:#7c3aed;color:#7c3aed; }
.asist-tab.active { background:linear-gradient(135deg,#172554,#2563eb);color:white;border-color:transparent; }
.asist-day-table  { width:100%;border-collapse:collapse;font-size:.84rem; }
.asist-day-table th { padding:10px 14px;text-align:center;font-size:.71rem;font-weight:800;color:#94a3b8;text-transform:uppercase;border-bottom:2px solid #f1f5f9; }
.asist-day-table td { padding:12px 14px;text-align:center;border-bottom:1px solid #f8fafc; }
.estado-pill { display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:.74rem;font-weight:700; }
.ep-p  { background:#dcfce7;color:#059669; } .ep-a { background:#fee2e2;color:#dc2626; }
.ep-j  { background:#fef9c3;color:#b45309; } .ep-r { background:#ffe4e6;color:#be123c; }
.ep-nd { background:#f1f5f9;color:#94a3b8; }
.cal-grid-wrap { display:grid;grid-template-columns:repeat(7,1fr);gap:4px;font-size:.78rem; }
.cal-day-hdr   { text-align:center;font-weight:800;color:#94a3b8;font-size:.67rem;text-transform:uppercase;padding:6px 2px; }
.cal-cell      { aspect-ratio:1;border-radius:8px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:700;font-size:.78rem; }
.cal-cell.finde{ opacity:.35; } .cal-cell.empty{ background:transparent; }
.cal-cell.cp   { background:#dcfce7;color:#059669; } .cal-cell.ca { background:#fee2e2;color:#dc2626; }
.cal-cell.cj   { background:#fef9c3;color:#b45309; } .cal-cell.cr { background:#ffe4e6;color:#be123c; }
.cal-cell.sin  { background:#f8fafc;color:#cbd5e1; }

/* Modales */
.pc-modal-overlay { display:none;position:fixed;inset:0;background:rgba(15,23,42,.7);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center; }
.pc-modal-overlay.active { display:flex; }
.pc-modal-box { background:white;border-radius:22px;width:90%;max-width:640px;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 32px 80px rgba(15,23,42,.3);animation:pc-fadeUp .3s ease; }
.pc-modal-sm  { max-width:440px; }
.pm-head { background:linear-gradient(135deg,#172554,#2563eb);padding:22px 26px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0; }
.pm-head h2 { font-size:1rem;font-weight:700;margin:0;color:white; }
.pm-head p  { font-size:.78rem;margin:3px 0 0;color:rgba(255,255,255,.8); }
.pm-body { padding:22px 26px;overflow-y:auto;flex:1; }
.pm-close { background:rgba(255,255,255,.2);border:none;color:white;width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center; }
.pm-close:hover { background:rgba(255,255,255,.35); }
.f-label { display:block;font-size:.72rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px; }
.f-input { width:100%;padding:9px 12px;border:2px solid #e5e7eb;border-radius:9px;font-size:.88rem;color:#1e293b;transition:border-color .2s;box-sizing:border-box;background:#fafafa;font-family:inherit; }
.f-input:focus { outline:none;border-color:#2563eb;background:white; }
.f-group { margin-bottom:14px; }
.f-row2  { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
.f-section-lbl { font-size:.72rem;font-weight:800;color:#7c3aed;text-transform:uppercase;letter-spacing:.8px;margin:16px 0 10px;padding-bottom:5px;border-bottom:2px solid #f3e8ff; }
.f-btn-primary { width:100%;padding:11px;border:none;border-radius:9px;cursor:pointer;background:linear-gradient(135deg,#172554,#2563eb);color:white;font-size:.88rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:7px;transition:all .2s;font-family:inherit; }
.f-btn-primary:hover { transform:translateY(-1px);box-shadow:0 6px 16px rgba(37,99,235,.3); }
.f-btn-cancel { flex:1;padding:10px;background:#f1f5f9;color:#475569;border:2px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;font-family:inherit; }
.calc-box  { background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:14px; }
.proy-card { background:linear-gradient(135deg,#172554,#1e3a8a);border-radius:10px;padding:14px;color:white;margin-top:12px; }
.proy-dato { display:flex;justify-content:space-between;align-items:center;padding:4px 0;border-bottom:1px solid rgba(255,255,255,.1);font-size:.8rem; }
.proy-dato span { color:rgba(255,255,255,.7); } .proy-dato strong { color:white; }
.proy-highlight { background:rgba(255,255,255,.12);border-radius:8px;padding:8px 12px;margin-top:8px;font-size:.83rem;font-weight:700;color:#7dd3fc; }
.temp-pass-box { background:#f0fdf4;border:2px dashed #10b981;border-radius:10px;padding:12px 16px;margin-top:14px;display:none; }
.temp-pass-box .lbl { font-size:.7rem;font-weight:800;color:#059669;text-transform:uppercase;letter-spacing:.5px; }
.temp-pass-box .val { font-size:1.2rem;font-weight:900;color:#065f46;letter-spacing:1px;margin-top:3px;font-family:monospace; }

@media(max-width:900px){ .pc-kpi-row{grid-template-columns:1fr 1fr;} }
@media(max-width:640px){ .pc-kpi-row{grid-template-columns:1fr;} }
</style>

<div class="pc-wrap">

<!-- BANNER -->
<div class="pc-banner">
    <div style="display:flex;align-items:center;gap:14px;z-index:1;flex-wrap:wrap;">
        <a href="<?= URLROOT ?>/actividades" class="pc-back"><i class="ti ti-arrow-left"></i> Volver</a>
        <div style="width:1px;height:28px;background:rgba(255,255,255,.2);"></div>
        <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:10px;">
            <i class="ti ti-user-star" style="font-size:24px;color:white;"></i>
        </div>
        <div>
            <h1 style="color:white;font-size:1.5rem;font-weight:800;margin:0;">Pasantías Cortas</h1>
            <p style="color:rgba(255,255,255,.7);margin:3px 0 0;font-size:.82rem;">
                <?php if ($periodoCortoActivo): ?>
                <span style="background:rgba(16,185,129,.25);border-radius:100px;padding:2px 10px;margin-right:6px;">
                    <span style="width:6px;height:6px;background:#34d399;border-radius:50%;display:inline-block;margin-right:4px;animation:pc-pulse 2s infinite;vertical-align:middle;"></span>
                    <?= htmlspecialchars($periodoCortoActivo->nombre) ?>
                </span>
                <?php else: ?>
                <span style="background:rgba(239,68,68,.25);border-radius:100px;padding:2px 10px;"><i class="ti ti-alert-circle"></i> Sin período corto activo</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <button class="pc-nuevo-btn" onclick="abrirModalNuevoPasante()">
        <i class="ti ti-plus"></i> Nuevo Pasante
    </button>
</div>

<!-- KPIs -->
<div class="pc-kpi-row">
    <div class="pc-kpi" style="border-left:4px solid #2563eb;" onmouseover="this.style.boxShadow='0 12px 24px rgba(37,99,235,.2)'" onmouseout="this.style.boxShadow=''">
        <div><p style="color:#64748b;font-size:.75rem;margin:0 0 5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Activos</p>
        <h2 style="font-size:2rem;font-weight:800;color:#2563eb;margin:0;"><?= $statActivos ?></h2></div>
        <div style="background:#eff6ff;color:#2563eb;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="ti ti-user-check"></i></div>
    </div>
    <div class="pc-kpi" style="border-left:4px solid #059669;" onmouseover="this.style.boxShadow='0 12px 24px rgba(5,150,105,.2)'" onmouseout="this.style.boxShadow=''">
        <div><p style="color:#64748b;font-size:.75rem;margin:0 0 5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Finalizados</p>
        <h2 style="font-size:2rem;font-weight:800;color:#059669;margin:0;"><?= $statFinalizados ?></h2></div>
        <div style="background:#f0fdf4;color:#059669;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="ti ti-trophy"></i></div>
    </div>
    <div class="pc-kpi" style="border-left:4px solid #7c3aed;" onmouseover="this.style.boxShadow='0 12px 24px rgba(124,58,237,.2)'" onmouseout="this.style.boxShadow=''">
        <div><p style="color:#64748b;font-size:.75rem;margin:0 0 5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Promedio Progreso</p>
        <h2 style="font-size:2rem;font-weight:800;color:#7c3aed;margin:0;"><?= $statPromPct ?>%</h2></div>
        <div style="background:#fdf4ff;color:#7c3aed;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="ti ti-trending-up"></i></div>
    </div>
    <div class="pc-kpi" style="border-left:4px solid #d97706;" onmouseover="this.style.boxShadow='0 12px 24px rgba(217,119,6,.2)'" onmouseout="this.style.boxShadow=''">
        <div><p style="color:#64748b;font-size:.75rem;margin:0 0 5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">Total Registrados</p>
        <h2 style="font-size:2rem;font-weight:800;color:#d97706;margin:0;"><?= count($pasantesCortos) ?></h2></div>
        <div style="background:#fffbeb;color:#d97706;width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;"><i class="ti ti-users"></i></div>
    </div>
</div>

<!-- TIMELINE PERÍODO -->
<?php if ($periodoCortoActivo): ?>
<?php
$pIni  = new DateTime($periodoCortoActivo->fecha_inicio);
$pFin  = new DateTime($periodoCortoActivo->fecha_fin);
$hoy   = new DateTime();
$totD  = max(1, $pFin->diff($pIni)->days);
$trnD  = max(0, min($totD, $hoy->diff($pIni)->days));
if ($hoy < $pIni) $trnD = 0;
if ($hoy > $pFin) $trnD = $totD;
$ppct  = round($trnD / $totD * 100);
$restD = max(0, $totD - $trnD);
?>
<div class="pc-timeline-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
        <span style="font-size:.82rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:7px;">
            <i class="ti ti-calendar-stats" style="color:#2563eb;"></i> Progreso del Período Corto
        </span>
        <span style="font-size:.8rem;font-weight:700;color:#2563eb;"><?= $ppct ?>% transcurrido</span>
    </div>
    <div class="pc-timeline-bar">
        <div class="pc-timeline-fill" id="timelineFill" style="width:0%"></div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:.74rem;color:#94a3b8;">
        <span><?= date('d/m/Y', strtotime($periodoCortoActivo->fecha_inicio)) ?></span>
        <span style="font-weight:700;color:#64748b;">Hoy · Día <?= $trnD ?> de <?= $totD ?> · <?= $restD ?> restantes</span>
        <span><?= date('d/m/Y', strtotime($periodoCortoActivo->fecha_fin)) ?></span>
    </div>
</div>
<script>setTimeout(()=>{const f=document.getElementById('timelineFill');if(f)f.style.width='<?= $ppct ?>%';},200);</script>
<?php endif; ?>

<!-- TABLA PASANTES -->
<div class="pc-table-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
        <span style="font-size:.9rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
            <i class="ti ti-table" style="color:#2563eb;"></i> Lista de Pasantes
        </span>
        <div class="estado-pills">
            <button class="epill active" data-estado="Todos" onclick="filtrarEstado(this)">Todos</button>
            <button class="epill" data-estado="Activo" onclick="filtrarEstado(this)">Activos</button>
            <button class="epill" data-estado="Finalizado" onclick="filtrarEstado(this)">Finalizados</button>
            <button class="epill" data-estado="Retirado" onclick="filtrarEstado(this)">Retirados</button>
        </div>
    </div>
    <?php if (empty($pasantesCortos)): ?>
    <div style="text-align:center;padding:50px 20px;color:#94a3b8;">
        <i class="ti ti-users" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;"></i>
        <div style="font-weight:600;">Sin pasantes de pasantía corta</div>
        <div style="font-size:.8rem;margin-top:4px;">Usa "Nuevo Pasante" para registrar el primero</div>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="pc-table">
        <thead><tr>
            <th>Pasante</th><th>Institución</th><th>Departamento / Tutor</th>
            <th>Período</th><th>Progreso</th><th>Estado</th><th style="text-align:center;">Acciones</th>
        </tr></thead>
        <tbody id="tablaPasantes">
        <?php foreach ($pasantesCortos as $pc):
            $hMeta = max(1, (int)($pc->horas_meta ?? 480));
            $hAcum = (int)($pc->horas_acumuladas ?? 0);
            $pct   = min(100, round($hAcum / $hMeta * 100));
            $ep    = $pc->estado_pasantia ?? 'Pendiente';
            $eCls  = $ep === 'Activo' ? 'estado-act' : ($ep === 'Finalizado' ? 'estado-fin' : 'estado-ret');
            $pcJson= htmlspecialchars(json_encode($pc), ENT_QUOTES);
        ?>
        <tr data-estado="<?= $ep ?>">
            <td>
                <div class="pct-name"><?= htmlspecialchars(($pc->nombres??'').' '.($pc->apellidos??'')) ?></div>
                <div class="pct-sub"><?= htmlspecialchars($pc->cedula ?? '') ?></div>
            </td>
            <td style="font-size:.82rem;color:#475569;"><?= htmlspecialchars($pc->institucion_nombre ?? '—') ?></td>
            <td>
                <div style="font-size:.82rem;color:#475569;"><?= htmlspecialchars($pc->departamento_nombre ?? '—') ?></div>
                <div style="font-size:.73rem;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars(trim($pc->tutor_nombre ?? '—')) ?></div>
            </td>
            <td style="font-size:.78rem;color:#64748b;"><?= htmlspecialchars($pc->periodo_nombre ?? '—') ?></td>
            <td style="min-width:100px;">
                <div class="pct-bar">
                    <div class="pct-track"><div class="pct-fill" style="width:<?= $pct ?>%;"></div></div>
                    <span class="pct-pct"><?= $pct ?>%</span>
                </div>
                <div style="font-size:.69rem;color:#94a3b8;margin-top:2px;"><?= $hAcum ?>/<?= $hMeta ?>h</div>
            </td>
            <td><span class="<?= $eCls ?>"><?= htmlspecialchars($ep) ?></span></td>
            <td style="text-align:center;">
                <div style="display:flex;gap:5px;justify-content:center;">
                    <button class="btn-pc btn-eye" title="Ver perfil" onclick="SGPModal.verUsuario(<?= $pc->id ?>)"><i class="ti ti-eye"></i></button>
                    <button class="btn-pc btn-edit" title="Editar" onclick="abrirEditarPasante(<?= $pcJson ?>)"><i class="ti ti-edit"></i></button>
                    <a href="<?= URLROOT ?>/actividades/verPasante/<?= $pc->id ?>" class="btn-pc btn-asist pjax-link" title="Ver almanaque"><i class="ti ti-calendar-stats"></i></a>
                    <button class="btn-pc btn-del" title="Eliminar pasante" onclick="confirmarEliminarPasanteCorto(<?= $pc->id ?>, '<?= htmlspecialchars(($pc->nombres??'').' '.($pc->apellidos??''), ENT_QUOTES) ?>')"><i class="ti ti-trash"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════════ CONTROL DE ASISTENCIAS ═══════════ -->
<?php
$baseAsist = URLROOT . '/actividades/pasantias';
$tabsAsist = [
    ['key'=>'diaria',  'label'=>'Diaria',  'ico'=>'ti-calendar-event'],
    ['key'=>'semanal', 'label'=>'Semanal', 'ico'=>'ti-calendar-week'],
    ['key'=>'mensual', 'label'=>'Mensual', 'ico'=>'ti-calendar-month'],
    ['key'=>'total',   'label'=>'Total',   'ico'=>'ti-chart-bar'],
];
$hrefsAsist = [
    'diaria'  => $baseAsist.'?vista=diaria',
    'semanal' => $baseAsist.'?vista=semanal&semana='.$paramsUrl['semana'].'&anio='.$paramsUrl['anio'],
    'mensual' => $baseAsist.'?vista=mensual&mes='.$paramsUrl['mes'].'&anio='.$paramsUrl['anio'],
    'total'   => $baseAsist.'?vista=total',
];
?>

<!-- Banner de control de asistencias -->
<div style="background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 50%,#a78bfa 100%);border-radius:18px;padding:24px 28px;position:relative;overflow:hidden;margin-bottom:18px;">
    <div style="position:absolute;top:-30px;right:-30px;width:160px;height:160px;background:rgba(255,255,255,.06);border-radius:50%;"></div>
    <div style="position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:14px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;"><i class="ti ti-clock-check" style="font-size:24px;color:#fff;"></i></div>
            <div>
                <div style="display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);color:rgba(255,255,255,.9);font-size:.68rem;font-weight:700;padding:2px 10px;border-radius:20px;margin-bottom:4px;">
                    <i class="ti ti-shield-check" style="font-size:.75rem;"></i> Control de Asistencia · Pasantías Cortas
                </div>
                <h2 style="color:#fff;font-size:1.4rem;font-weight:800;margin:0;">Asistencias</h2>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <?php foreach ($tabsAsist as $t):
                $active = ($vistaAsist === $t['key']);
                $sty = $active
                    ? "background:rgba(255,255,255,.28);border:1px solid rgba(255,255,255,.4);color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.12);"
                    : "background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.85);";
            ?>
            <a href="<?= $hrefsAsist[$t['key']] ?>" class="pjax-link" style="<?= $sty ?>border-radius:50px;padding:6px 16px;font-size:.82rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all .15s;"
               onmouseover="this.style.background='rgba(255,255,255,.3)'" onmouseout="this.style.background='<?= $active ? 'rgba(255,255,255,.28)' : 'rgba(255,255,255,.10)' ?>'">
                <i class="ti <?= $t['ico'] ?>" style="font-size:.9rem;"></i> <?= $t['label'] ?>
            </a>
            <?php endforeach; ?>
            <button onclick="document.getElementById('pcModalManual').classList.add('open')"
                style="background:#fff;color:#4c1d95;border:none;padding:8px 18px;border-radius:10px;font-weight:700;font-size:.82rem;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 4px 10px rgba(0,0,0,.1);transition:all .15s;"
                onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
                <i class="ti ti-plus" style="font-size:1rem;"></i> Registro Manual
            </button>
        </div>
    </div>
</div>

<?php if ($vistaAsist): ?>
<!-- KPI Cards -->
<?php if ($esDiaria): ?>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;">
    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;border-radius:16px;padding:16px 18px;">
        <div style="font-size:.7rem;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.05em;">Presentes</div>
        <div style="font-size:2rem;font-weight:900;color:#15803d;margin-top:4px;"><?= $presentesAsist ?></div>
    </div>
    <div style="background:linear-gradient(135deg,#fff1f2,#fee2e2);border:1px solid #fecaca;border-radius:16px;padding:16px 18px;">
        <div style="font-size:.7rem;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.05em;">Ausentes</div>
        <div style="font-size:2rem;font-weight:900;color:#dc2626;margin-top:4px;"><?= $ausentesAsist ?></div>
    </div>
    <div style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1px solid #fde68a;border-radius:16px;padding:16px 18px;">
        <div style="font-size:.7rem;font-weight:700;color:#b45309;text-transform:uppercase;letter-spacing:.05em;">Justificados</div>
        <div style="font-size:2rem;font-weight:900;color:#b45309;margin-top:4px;"><?= $justificadosAsist ?></div>
    </div>
    <div style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1px solid #c4b5fd;border-radius:16px;padding:16px 18px;">
        <div style="font-size:.7rem;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.05em;">% Asistencia</div>
        <div style="font-size:2rem;font-weight:900;color:#7c3aed;margin-top:4px;"><?= $porcAsistencia ?>%</div>
    </div>
</div>
<?php endif; ?>

<?php if ($esDiaria): ?>
<!-- VISTA DIARIA -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="pc-diaria-grid">
    <!-- Marcaron Hoy -->
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;border:1px solid #e2e8f0;border-top:3px solid #10b981;">
        <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:.9rem;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:6px;"><i class="ti ti-check" style="color:#10b981;"></i> Marcaron Hoy</span>
            <span style="background:#dcfce7;color:#15803d;padding:3px 12px;border-radius:20px;font-size:.72rem;font-weight:700;"><?= count($registrosAsist) ?></span>
        </div>
        <?php if (empty($registrosAsist)): ?>
        <div style="padding:2.5rem;text-align:center;color:#94a3b8;"><i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:8px;opacity:.3;"></i><p style="font-size:.85rem;">Ningún pasante ha marcado</p></div>
        <?php else: ?>
        <div style="overflow-x:auto;max-height:380px;overflow-y:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th style="padding:10px 16px;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#64748b;text-align:left;">Pasante</th>
                <th style="padding:10px 12px;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#64748b;text-align:left;">Hora</th>
                <th style="padding:10px 12px;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#64748b;text-align:left;">Estado</th>
            </tr></thead>
            <tbody>
            <?php foreach ($registrosAsist as $r):
                $fn = trim(($r->apellidos??'').', '.($r->nombres??''));
                $ini = mb_strtoupper(mb_substr($r->apellidos??'P',0,1).mb_substr($r->nombres??'P',0,1));
                $est = strtolower($r->estado??'');
                $cls = str_contains($est,'presente')?'#10b981':(str_contains($est,'ausente')?'#ef4444':'#f59e0b');
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px 16px;"><div style="display:flex;align-items:center;gap:8px;"><div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0;"><?= $ini ?></div><div><div style="font-size:.82rem;font-weight:600;color:#1e293b;"><?= htmlspecialchars($fn) ?></div><div style="font-size:.7rem;color:#94a3b8;">V-<?= htmlspecialchars($r->cedula??'') ?></div></div></div></td>
                <td style="padding:10px 12px;font-size:.8rem;color:#475569;"><?= ($r->hora_registro && $r->hora_registro !== '00:00:00') ? date('g:i A', strtotime($r->hora_registro)) : '—' ?></td>
                <td style="padding:10px 12px;"><span style="background:<?= $cls ?>1a;color:<?= $cls ?>;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;"><?= ucfirst($r->estado??'') ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <!-- Sin Marcar -->
    <div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;border:1px solid #e2e8f0;border-top:3px solid #ef4444;">
        <div style="padding:14px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
            <span style="font-size:.9rem;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:6px;"><i class="ti ti-alert-circle" style="color:#ef4444;"></i> Sin Marcar Hoy</span>
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="background:#fee2e2;color:#b91c1c;padding:3px 12px;border-radius:20px;font-size:.72rem;font-weight:700;"><?= $sinMarcarCount ?></span>
                <?php if ($sinMarcarCount > 0 && (int)date('N') <= 5): ?>
                <button onclick="marcarTodosCorta()" style="display:flex;align-items:center;gap:4px;padding:4px 12px;border-radius:8px;border:1.5px solid #c4b5fd;background:#f5f3ff;color:#7c3aed;font-size:.72rem;font-weight:700;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.background='#7c3aed';this.style.color='#fff'" onmouseout="this.style.background='#f5f3ff';this.style.color='#7c3aed'">
                    <i class="ti ti-checks" style="font-size:.85rem;"></i> Marcar todos
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php if (empty($sinMarcarAsist)): ?>
        <div style="padding:2.5rem;text-align:center;"><i class="ti ti-circle-check" style="font-size:2.5rem;color:#10b981;display:block;margin-bottom:8px;opacity:.7;"></i><p style="color:#10b981;font-size:.9rem;font-weight:700;">¡Todos marcaron!</p></div>
        <?php else: ?>
        <div style="overflow-x:auto;max-height:380px;overflow-y:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th style="padding:10px 16px;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#64748b;text-align:left;">Pasante</th>
                <th style="padding:10px 12px;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#64748b;text-align:center;">Acción</th>
            </tr></thead>
            <tbody>
            <?php foreach ($sinMarcarAsist as $sm):
                $fn = trim(($sm->apellidos??'').', '.($sm->nombres??''));
                $ini = mb_strtoupper(mb_substr($sm->apellidos??'P',0,1).mb_substr($sm->nombres??'P',0,1));
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:10px 16px;"><div style="display:flex;align-items:center;gap:8px;"><div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#94a3b8,#64748b);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0;"><?= $ini ?></div><div><div style="font-size:.82rem;font-weight:600;color:#1e293b;"><?= htmlspecialchars($fn) ?></div><div style="font-size:.7rem;color:#94a3b8;">V-<?= htmlspecialchars($sm->cedula??'') ?></div></div></div></td>
                <td style="padding:10px 12px;text-align:center;">
                    <button onclick="marcarRapidoCorta(<?= $sm->id ?>,'<?= htmlspecialchars($fn,ENT_QUOTES) ?>')" style="display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:8px;border:1.5px solid #c4b5fd;background:#f5f3ff;color:#7c3aed;font-size:.75rem;font-weight:700;cursor:pointer;transition:all .15s;"
                        onmouseover="this.style.background='#7c3aed';this.style.color='#fff'" onmouseout="this.style.background='#f5f3ff';this.style.color='#7c3aed'">
                        <i class="ti ti-check"></i> Marcar
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<style>@media(max-width:700px){ .pc-diaria-grid{ grid-template-columns:1fr !important; } }</style>

<?php elseif ($esSemanal): ?>
<!-- VISTA SEMANAL -->
<div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;border:1px solid #e2e8f0;">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span style="font-size:.9rem;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:6px;"><i class="ti ti-calendar-week" style="color:#7c3aed;"></i> Resumen Semanal</span>
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="<?= $navSemana['ant_url'] ?>" class="pjax-link" style="padding:6px 12px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .15s;" onmouseover="this.style.borderColor='#7c3aed';this.style.color='#7c3aed'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569'"><i class="ti ti-chevron-left"></i> Anterior</a>
            <span style="font-size:.88rem;font-weight:700;color:#1e293b;"><i class="ti ti-calendar-event" style="color:#7c3aed;margin-right:4px;"></i><?= htmlspecialchars($navSemana['texto']) ?></span>
            <a href="<?= $navSemana['sig_url'] ?>" class="pjax-link" style="padding:6px 12px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .15s;" onmouseover="this.style.borderColor='#7c3aed';this.style.color='#7c3aed'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#475569'">Siguiente <i class="ti ti-chevron-right"></i></a>
        </div>
    </div>
    <div style="padding:16px 20px;">
    <?php if (empty($datosSemanales)): ?>
        <div style="text-align:center;padding:2.5rem;color:#94a3b8;"><i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:8px;opacity:.3;"></i><p>Sin datos para esta semana.</p></div>
    <?php endif; ?>
    <?php foreach ($datosSemanales as $depto => $pasantesDepto): ?>
    <div style="margin-bottom:14px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <div style="padding:10px 14px;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:.82rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:6px;"><span style="width:8px;height:8px;border-radius:50%;background:#7c3aed;flex-shrink:0;"></span><?= htmlspecialchars($depto) ?></span>
            <span style="font-size:.7rem;font-weight:700;color:#7c3aed;background:#ede9fe;padding:2px 8px;border-radius:20px;"><?= count($pasantesDepto) ?></span>
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:480px;">
            <thead><tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th style="padding:8px 14px;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#64748b;text-align:left;min-width:140px;">Pasante</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#64748b;text-align:center;">Lun</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#64748b;text-align:center;">Mar</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#64748b;text-align:center;">Mié</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#64748b;text-align:center;">Jue</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#64748b;text-align:center;">Vie</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#15803d;text-align:center;">P</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#dc2626;text-align:center;">A</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#b45309;text-align:center;">J</th>
            </tr></thead>
            <tbody>
            <?php foreach ($pasantesDepto as $pid => $pd): ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px 14px;font-size:.82rem;font-weight:500;color:#1e293b;"><?= htmlspecialchars($pd['nombre']) ?></td>
                <?php for ($d=1; $d<=5; $d++):
                    $letra = $pd['dias'][$d] ?? '-';
                    $bg = $letra==='P'?'rgba(16,185,129,.14)':($letra==='A'?'rgba(239,68,68,.12)':($letra==='J'?'rgba(245,158,11,.13)':'transparent'));
                    $fg = $letra==='P'?'#059669':($letra==='A'?'#dc2626':($letra==='J'?'#b45309':'#cbd5e1'));
                ?>
                <td style="padding:8px;text-align:center;"><span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:22px;border-radius:6px;font-size:.76rem;font-weight:700;background:<?= $bg ?>;color:<?= $fg ?>;"><?= htmlspecialchars($letra) ?></span></td>
                <?php endfor; ?>
                <td style="text-align:center;font-weight:800;color:#059669;font-size:.82rem;"><?= $pd['totales']['P'] ?></td>
                <td style="text-align:center;font-weight:800;color:#dc2626;font-size:.82rem;"><?= $pd['totales']['A'] ?></td>
                <td style="text-align:center;font-weight:800;color:#b45309;font-size:.82rem;"><?= $pd['totales']['J'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<?php elseif ($esMensual): ?>
<!-- VISTA MENSUAL -->
<div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;border:1px solid #e2e8f0;">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span style="font-size:.9rem;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:6px;"><i class="ti ti-calendar-month" style="color:#7c3aed;"></i> Resumen Mensual</span>
        <div style="display:flex;align-items:center;gap:8px;">
            <a href="<?= $urlMesAnt ?>" class="pjax-link" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#475569;text-decoration:none;"><i class="ti ti-chevron-left"></i></a>
            <span style="font-size:.88rem;font-weight:700;color:#1e293b;padding:0 8px;"><?= $nombreMes ?> <?= $paramsUrl['anio'] ?></span>
            <a href="<?= $urlMesSig ?>" class="pjax-link" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#475569;text-decoration:none;"><i class="ti ti-chevron-right"></i></a>
            <span style="background:<?= $healthIndex>=80?'#dcfce7':($healthIndex>=60?'#fef3c7':'#fee2e2') ?>;color:<?= $healthIndex>=80?'#15803d':($healthIndex>=60?'#b45309':'#b91c1c') ?>;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;"><?= $healthIndex ?>% global</span>
        </div>
    </div>
    <div style="padding:16px 20px;">
    <?php if (empty($bentoMensual)): ?>
        <div style="text-align:center;padding:2.5rem;color:#94a3b8;"><i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:8px;opacity:.3;"></i><p>Sin registros en <?= $nombreMes ?>.</p></div>
    <?php else: ?>
    <?php foreach ($bentoMensual as $depto => $pasantes): ?>
    <div style="margin-bottom:14px;">
        <div style="font-size:.72rem;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;display:flex;align-items:center;gap:6px;"><i class="ti ti-building"></i> <?= htmlspecialchars($depto) ?></div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:460px;">
            <thead><tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                <th style="padding:8px 14px;font-size:.68rem;font-weight:700;text-transform:uppercase;color:#94a3b8;text-align:left;">Pasante</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#15803d;text-align:center;">P</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#b91c1c;text-align:center;">A</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#b45309;text-align:center;">J</th>
                <th style="padding:8px;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:center;">%</th>
                <th style="padding:8px 14px;font-size:.68rem;font-weight:700;color:#94a3b8;text-align:left;">Tendencia</th>
            </tr></thead>
            <tbody>
            <?php foreach ($pasantes as $rm):
                $total = $rm['presentes']+$rm['ausentes']+$rm['justificados'];
                $pct = $total>0 ? round(($rm['presentes']+$rm['justificados'])/$total*100) : 0;
                $barC = $pct>=80?'#10b981':($pct>=60?'#f59e0b':'#ef4444');
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px 14px;"><div style="display:flex;align-items:center;gap:8px;"><div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;color:#fff;flex-shrink:0;"><?= htmlspecialchars($rm['iniciales']) ?></div><div style="font-size:.82rem;font-weight:600;color:#1e293b;"><?= htmlspecialchars($rm['nombre']) ?></div></div></td>
                <td style="text-align:center;font-weight:700;color:#15803d;"><?= $rm['presentes'] ?></td>
                <td style="text-align:center;font-weight:700;color:#b91c1c;"><?= $rm['ausentes'] ?></td>
                <td style="text-align:center;font-weight:700;color:#b45309;"><?= $rm['justificados'] ?></td>
                <td style="text-align:center;font-weight:800;color:<?= $barC ?>;"><?= $pct ?>%</td>
                <td style="padding:8px 14px;min-width:80px;"><div style="height:5px;background:#f1f5f9;border-radius:99px;overflow:hidden;"><div style="height:100%;width:<?= $pct ?>%;background:<?= $barC ?>;border-radius:99px;"></div></div></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<?php elseif ($esTotal): ?>
<!-- VISTA TOTAL -->
<?php
$tituloVista = $periodoSeleccionado->nombre ?? 'Todos los Períodos';
$idxAct = -1;
foreach ($periodosFiltroAsist as $i => $pf) { if ($pf->id == $periodoIdSel) { $idxAct = $i; break; } }
$urlAntT = '#'; $urlSigT = '#';
if ($idxAct !== -1) {
    if (isset($periodosFiltroAsist[$idxAct+1])) $urlAntT = $baseAsist.'?vista=total&periodo_id='.$periodosFiltroAsist[$idxAct+1]->id;
    if (isset($periodosFiltroAsist[$idxAct-1])) $urlSigT = $baseAsist.'?vista=total&periodo_id='.$periodosFiltroAsist[$idxAct-1]->id;
}
?>
<div style="background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;border:1px solid #e2e8f0;">
    <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <span style="font-size:.9rem;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:6px;"><i class="ti ti-chart-bar" style="color:#7c3aed;"></i> Histórico Total <span style="background:#ede9fe;color:#7c3aed;padding:2px 10px;border-radius:20px;font-size:.72rem;"><?= count($resumenTotal) ?> pasantes</span></span>
        <div style="display:flex;align-items:center;gap:8px;">
            <a href="<?= $urlAntT ?>" class="pjax-link" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#475569;text-decoration:none;<?= $urlAntT==='#'?'opacity:.3;pointer-events:none;':'' ?>"><i class="ti ti-chevron-left"></i></a>
            <span style="font-size:.85rem;font-weight:700;color:#1e293b;"><i class="ti ti-calendar" style="color:#7c3aed;margin-right:4px;"></i><?= htmlspecialchars($tituloVista) ?></span>
            <a href="<?= $urlSigT ?>" class="pjax-link" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#475569;text-decoration:none;<?= $urlSigT==='#'?'opacity:.3;pointer-events:none;':'' ?>"><i class="ti ti-chevron-right"></i></a>
        </div>
    </div>
    <?php if (empty($resumenTotal)): ?>
    <div style="text-align:center;padding:3rem;color:#94a3b8;"><i class="ti ti-database-off" style="font-size:2.5rem;display:block;margin-bottom:8px;opacity:.3;"></i><p>Sin registros en este período.</p></div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;padding:18px 20px;">
    <?php foreach ($resumenTotal as $rm):
        $horasCalc = ($rm['presentes']+$rm['ausentes']+$rm['justificados'])*8;
        $meta = $rm['horas_meta']??480;
        $pctH = $meta>0?min(100,round($horasCalc/$meta*100)):0;
        $circum = 2*3.14159*42;
        $offset = $circum-($pctH/100)*$circum;
        $strokeC = $pctH>=80?'#10b981':($pctH>=50?'#f59e0b':'#7c3aed');
        $estado = $rm['estado_pasantia']??'Activo';
        $estBg = $estado==='Finalizado'?'#ede9fe':'#dcfce7';
        $estFg = $estado==='Finalizado'?'#7c3aed':'#15803d';
    ?>
    <div style="background:#fff;border-radius:16px;border:1px solid #e2e8f0;padding:18px;display:flex;flex-direction:column;align-items:center;gap:12px;box-shadow:0 2px 10px rgba(0,0,0,.04);transition:all .2s;position:relative;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 28px rgba(124,58,237,.12)'" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,.04)'">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:.88rem;font-weight:800;color:#fff;box-shadow:0 3px 10px rgba(124,58,237,.3);"><?= htmlspecialchars($rm['iniciales']) ?></div>
        <div style="text-align:center;"><div style="font-size:.84rem;font-weight:700;color:#1e293b;"><?= htmlspecialchars($rm['nombre']) ?></div><div style="font-size:.7rem;color:#94a3b8;">V-<?= htmlspecialchars($rm['cedula']) ?></div></div>
        <span style="background:<?= $estBg ?>;color:<?= $estFg ?>;padding:2px 10px;border-radius:20px;font-size:.65rem;font-weight:700;"><?= htmlspecialchars($estado) ?></span>
        <svg width="90" height="90" viewBox="0 0 100 100"><circle cx="50" cy="50" r="42" fill="none" stroke="#f1f5f9" stroke-width="8"/><circle cx="50" cy="50" r="42" fill="none" stroke="<?= $strokeC ?>" stroke-width="8" stroke-linecap="round" stroke-dasharray="<?= round($circum) ?>" stroke-dashoffset="<?= round($offset) ?>" transform="rotate(-90 50 50)" style="transition:stroke-dashoffset .8s;"/><text x="50" y="46" text-anchor="middle" style="font-size:14px;font-weight:900;fill:#1e293b;"><?= $pctH ?>%</text><text x="50" y="60" text-anchor="middle" style="font-size:7px;font-weight:600;fill:#64748b;"><?= number_format($horasCalc) ?> / <?= number_format($meta) ?> hrs</text></svg>
        <div style="display:flex;gap:10px;font-size:.72rem;font-weight:700;">
            <span style="color:#10b981;"><?= $rm['presentes'] ?>P</span>
            <span style="color:#ef4444;"><?= $rm['ausentes'] ?>A</span>
            <span style="color:#f59e0b;"><?= $rm['justificados'] ?>J</span>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php else: ?>
<!-- Estado inicial: sin vista seleccionada, mostrar invitación -->
<div style="background:#fff;border-radius:16px;padding:40px;text-align:center;border:2px dashed #e2e8f0;">
    <i class="ti ti-calendar-stats" style="font-size:3rem;color:#c4b5fd;display:block;margin-bottom:12px;"></i>
    <h3 style="font-size:1.2rem;font-weight:800;color:#1e293b;margin:0 0 6px;">Control de Asistencias</h3>
    <p style="color:#64748b;font-size:.88rem;margin:0 0 16px;">Selecciona una vista para ver las asistencias de los pasantes de pasantía corta</p>
    <a href="<?= $hrefsAsist['diaria'] ?>" class="pjax-link" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;padding:10px 24px;border-radius:10px;font-weight:700;font-size:.88rem;text-decoration:none;box-shadow:0 4px 12px rgba(124,58,237,.3);transition:all .15s;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform=''">
        <i class="ti ti-calendar-event"></i> Ver Asistencia Diaria
    </a>
</div>
<?php endif; ?>

<!-- Modal Registro Manual -->
<div class="ta-modal-overlay" id="pcModalManual" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);z-index:1050;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:440px;margin:16px;box-shadow:0 24px 80px rgba(0,0,0,.25);overflow:hidden;">
        <div style="background:linear-gradient(135deg,#4c1d95,#7c3aed);padding:18px 22px;display:flex;align-items:center;justify-content:space-between;">
            <span style="color:#fff;font-size:1rem;font-weight:700;display:flex;align-items:center;gap:8px;"><i class="ti ti-clipboard-plus"></i> Registro Manual</span>
            <button onclick="document.getElementById('pcModalManual').style.display='none'" style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.12);border:none;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;"><i class="ti ti-x"></i></button>
        </div>
        <div style="padding:20px 22px;">
            <div style="margin-bottom:12px;"><label style="font-size:.75rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">Pasante</label>
                <select id="pcSelPasante" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;background:#fafbfd;">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($misPasantesAsist as $p): ?><option value="<?= $p->id ?>"><?= htmlspecialchars(($p->apellidos??'').', '.($p->nombres??'')) ?> — V-<?= $p->cedula ?></option><?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:12px;"><label style="font-size:.75rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">Fecha</label>
                <input type="date" id="pcManualFecha" value="<?= $hoyAsist ?>" max="<?= $hoyAsist ?>" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;background:#fafbfd;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:12px;"><label style="font-size:.75rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">Estado</label>
                <select id="pcManualEstado" onchange="document.getElementById('pcMotivoRow').style.display=this.value==='Justificado'?'block':'none'" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;background:#fafbfd;">
                    <option value="Presente">Presente</option><option value="Ausente">Ausente</option><option value="Justificado">Justificado</option>
                </select>
            </div>
            <div id="pcMotivoRow" style="display:none;margin-bottom:12px;"><label style="font-size:.75rem;font-weight:700;color:#475569;display:block;margin-bottom:4px;">Motivo</label>
                <textarea id="pcManualMotivo" rows="2" placeholder="Motivo de justificación…" style="width:100%;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;background:#fafbfd;resize:vertical;box-sizing:border-box;"></textarea>
            </div>
        </div>
        <div style="display:flex;gap:10px;padding:0 22px 18px;">
            <button onclick="document.getElementById('pcModalManual').style.display='none'" style="flex:1;padding:10px;border-radius:10px;background:#f1f5f9;color:#475569;border:none;font-weight:700;cursor:pointer;">Cancelar</button>
            <button onclick="guardarManualCorta()" style="flex:1;padding:10px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(124,58,237,.3);">Guardar</button>
        </div>
    </div>
</div>
<script>
document.getElementById('pcModalManual')?.addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });

async function guardarManualCorta(){
    const pid=document.getElementById('pcSelPasante').value;
    const fecha=document.getElementById('pcManualFecha').value;
    const estado=document.getElementById('pcManualEstado').value;
    const motivo=document.getElementById('pcManualMotivo')?.value?.trim()||'';
    if(!pid) return Swal.fire('','Selecciona un pasante.','warning');
    if(!fecha) return Swal.fire('','Selecciona una fecha.','warning');
    if(estado==='Justificado'&&!motivo) return Swal.fire('','El motivo es obligatorio.','warning');
    const fd=new FormData();
    fd.append('pasante_id',pid); fd.append('fecha',fecha); fd.append('estado',estado); fd.append('motivo_justificacion',motivo);
    fd.append('csrf_token','<?= $csrfToken ?>');
    try{
        const r=await fetch(`${URLROOT}/asistencias/registro_manual`,{method:'POST',body:fd});
        const d=await r.json();
        if(d.success) Swal.fire({icon:'success',title:'Registrado',text:d.message,confirmButtonColor:'#7c3aed'}).then(()=>location.reload());
        else Swal.fire('Error',d.message,'error');
    }catch(e){Swal.fire('Error','Error de red.','error');}
}
function marcarRapidoCorta(pid,nombre){
    document.getElementById('pcSelPasante').value=pid;
    document.getElementById('pcModalManual').style.display='flex';
}
async function marcarTodosCorta(){
    const pasantes=<?= json_encode(array_values(array_map(fn($p)=>['id'=>$p->id,'nombre'=>trim(($p->apellidos??'').', '.($p->nombres??''))], $sinMarcarAsist))) ?>;
    if(!pasantes.length) return;
    const {isConfirmed}=await Swal.fire({title:`¿Marcar ${pasantes.length} como Presente?`,icon:'warning',showCancelButton:true,confirmButtonText:'Sí, marcar todos',confirmButtonColor:'#7c3aed',cancelButtonColor:'#64748b'});
    if(!isConfirmed) return;
    Swal.fire({title:'Procesando...',html:'<span id="pc-prog">0</span> de '+pasantes.length,allowOutsideClick:false,didOpen:()=>Swal.showLoading()});
    let ok=0,err=0;
    for(const p of pasantes){
        const fd=new FormData();
        fd.append('pasante_id',p.id); fd.append('fecha','<?= $hoyAsist ?>'); fd.append('estado','Presente');
        fd.append('observacion','Marcado masivo (Corta)'); fd.append('csrf_token','<?= $csrfToken ?>');
        try{ const r=await fetch(`${URLROOT}/asistencias/registro_manual`,{method:'POST',body:fd}); const d=await r.json(); d.success?ok++:err++; }catch(e){err++;}
        const prog=document.getElementById('pc-prog'); if(prog) prog.textContent=ok+err;
    }
    Swal.fire({icon:err>0?'warning':'success',title:'Completado',text:`${ok} marcados`+(err>0?`, ${err} con error`:''),confirmButtonColor:'#7c3aed'}).then(()=>location.reload());
}
</script>

<!-- ═══════════ INASISTENCIAS DEL MES ═══════════ -->
<div style="background:white;border-radius:18px;padding:22px 28px;box-shadow:0 2px 12px rgba(0,0,0,.06);margin-top:22px;border:1px solid #f1f5f9;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#dc2626;"><i class="ti ti-user-x"></i></div>
            <div>
                <div style="font-size:.95rem;font-weight:800;color:#0f172a;">Inasistencias — <?= $mesLabel ?></div>
                <div style="font-size:.72rem;color:#94a3b8;margin-top:2px;">Pasantes cortos activos con ausencias este mes</div>
            </div>
        </div>
        <?php if(!empty($ausentesMesCortos)): ?>
        <span style="background:#fee2e2;color:#dc2626;padding:4px 12px;border-radius:20px;font-size:.74rem;font-weight:700;"><?= count($ausentesMesCortos) ?> con ausencias</span>
        <?php endif; ?>
    </div>

    <?php if(empty($ausentesMesCortos)): ?>
    <div style="display:flex;flex-direction:column;align-items:center;padding:28px 16px;border:2px dashed #e2e8f0;border-radius:14px;text-align:center;gap:10px;">
        <div style="width:52px;height:52px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#059669;"><i class="ti ti-calendar-check"></i></div>
        <div style="font-weight:700;color:#059669;">¡Sin inasistencias este mes!</div>
        <div style="font-size:.75rem;color:#94a3b8;">Todos los pasantes cortos activos tienen asistencia en <?= $mesLabel ?>.</div>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:.83rem;">
        <thead>
            <tr style="background:#f8fafc;">
                <th style="text-align:left;padding:9px 14px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Pasante</th>
                <th style="text-align:left;padding:9px 12px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Departamento</th>
                <th style="text-align:center;padding:9px 12px;font-size:.68rem;font-weight:800;color:#dc2626;text-transform:uppercase;letter-spacing:.4px;">Ausencias</th>
                <th style="text-align:center;padding:9px 12px;font-size:.68rem;font-weight:800;color:#059669;text-transform:uppercase;letter-spacing:.4px;">Presentes</th>
                <th style="text-align:center;padding:9px 12px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">% Asist.</th>
                <th style="padding:9px 14px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($ausentesMesCortos as $au):
            $pct = (float)($au->pct_asistencia ?? 0);
            $pC  = $pct>=80?'#059669':($pct>=60?'#d97706':'#dc2626');
            $pB  = $pct>=80?'#dcfce7':($pct>=60?'#fef3c7':'#fee2e2');
            $ini = mb_strtoupper(mb_substr($au->nombres??'?',0,1).mb_substr($au->apellidos??'?',0,1));
            $nom = htmlspecialchars(trim(($au->nombres??'').' '.($au->apellidos??'')));
        ?>
        <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background=''">
            <td style="padding:11px 14px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:white;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0;"><?= $ini ?></div>
                    <div>
                        <div style="font-weight:700;color:#0f172a;"><?= $nom ?></div>
                        <div style="font-size:.68rem;color:#94a3b8;">V-<?= htmlspecialchars($au->cedula??'') ?></div>
                    </div>
                </div>
            </td>
            <td style="padding:11px 12px;color:#475569;font-weight:500;"><?= htmlspecialchars($au->departamento??'—') ?></td>
            <td style="padding:11px 12px;text-align:center;"><span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:20px;font-weight:800;font-size:.8rem;"><?= (int)$au->total_ausentes ?></span></td>
            <td style="padding:11px 12px;text-align:center;"><span style="background:#dcfce7;color:#059669;padding:3px 10px;border-radius:20px;font-weight:700;font-size:.8rem;"><?= (int)$au->total_presentes ?></span></td>
            <td style="padding:11px 12px;text-align:center;"><span style="background:<?= $pB ?>;color:<?= $pC ?>;padding:3px 10px;border-radius:20px;font-weight:800;font-size:.8rem;"><?= $pct ?>%</span></td>
            <td style="padding:11px 14px;text-align:right;">
                <div style="display:flex;gap:6px;justify-content:flex-end;">
                    <button onclick="marcarDesdeInasist(<?= (int)$au->id ?>, '<?= htmlspecialchars(trim(($au->nombres??'').' '.($au->apellidos??'')),ENT_QUOTES) ?>')"
                            style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:8px;border:none;cursor:pointer;background:linear-gradient(135deg,#7c3aed,#a78bfa);color:white;font-size:.74rem;font-weight:700;font-family:inherit;box-shadow:0 2px 6px rgba(124,58,237,.3);transition:all .2s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <i class="ti ti-calendar-plus"></i> Marcar
                    </button>
                    <a href="<?= URLROOT ?>/actividades/verPasante/<?= (int)$au->id ?>"
                       style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:8px;border:1.5px solid #e9d5ff;background:#f5f3ff;color:#7c3aed;font-size:.74rem;font-weight:700;text-decoration:none;transition:all .2s;" onmouseover="this.style.background='#7c3aed';this.style.color='white'" onmouseout="this.style.background='#f5f3ff';this.style.color='#7c3aed'">
                        <i class="ti ti-eye"></i> Ver
                    </a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div><!-- /pc-wrap -->

<!-- MODAL: Nuevo Pasante -->
<div id="modalNuevoPasante" class="pc-modal-overlay">
<div class="pc-modal-box">
    <div class="pm-head">
        <div><h2><i class="ti ti-user-plus" style="margin-right:7px;"></i>Nuevo Pasante — Pasantía Corta</h2>
        <p>Datos personales + asignación académica</p></div>
        <button class="pm-close" onclick="cerrarModalPC('modalNuevoPasante')"><i class="ti ti-x"></i></button>
    </div>
    <div class="pm-body">
        <div id="tempPassBox" class="temp-pass-box">
            <div class="lbl"><i class="ti ti-key"></i> Credenciales generadas — anótalas antes de cerrar</div>
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:4px;">
                <div>
                    <div style="font-size:.71rem;color:#065f46;font-weight:700;margin-bottom:3px;">Contraseña temporal (sistema)</div>
                    <div class="val" id="tempPassVal">—</div>
                </div>
                <div>
                    <div style="font-size:.71rem;color:#065f46;font-weight:700;margin-bottom:3px;">PIN de Kiosco (asistencia)</div>
                    <div class="val" id="pinKioscoVal">—</div>
                </div>
            </div>
            <div style="font-size:.72rem;color:#065f46;margin-top:6px;">El pasante usará el PIN para marcar asistencia desde el kiosco desde el primer día.</div>
        </div>

        <div class="f-section-lbl"><i class="ti ti-id-badge"></i> Datos del Pasante</div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Nombres *</label><input type="text" class="f-input" id="pc-nombres" placeholder="Juan Carlos"></div>
            <div class="f-group"><label class="f-label">Apellidos *</label><input type="text" class="f-input" id="pc-apellidos" placeholder="Pérez García"></div>
        </div>
        <div class="f-row2">
            <div class="f-group">
                <label class="f-label">Cédula * <span class="sgp-tip" data-tip="Solo números sin puntos ni letras. Ej: 12345678. Se usa como identificador único del pasante en el sistema.">?</span></label>
                <input type="text" class="f-input" id="pc-cedula" placeholder="12345678"
                    oninput="verificarCampoPc('cedula',this.value,'pc-cedula-msg')">
                <span id="pc-cedula-msg" class="pc-field-msg"></span>
            </div>
            <div class="f-group">
                <label class="f-label">Correo * <span class="sgp-tip" data-tip="Correo institucional o personal del pasante. Formato: nombre@correo.com. Se usa para notificaciones del sistema.">?</span></label>
                <input type="email" class="f-input" id="pc-correo" placeholder="correo@universidad.edu"
                    oninput="verificarCampoPc('correo',this.value,'pc-correo-msg')">
                <span id="pc-correo-msg" class="pc-field-msg"></span>
            </div>
        </div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Carrera <span class="sgp-tip" data-tip="Nombre del programa académico. Aparece en el perfil del pasante y en los PDFs. Ej: Ing. Informática.">?</span></label><input type="text" class="f-input" id="pc-carrera" placeholder="Ing. Informática"></div>
            <div class="f-group"><label class="f-label">Institución</label>
                <select class="f-input" id="pc-inst">
                    <option value="">— Sin especificar —</option>
                    <?php foreach ($instituciones as $i): ?>
                    <option value="<?= $i->id ?>"><?= htmlspecialchars($i->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="f-section-lbl"><i class="ti ti-link"></i> Asignación Académica</div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Tutor *</label>
                <select class="f-input" id="pc-tutor">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($tutores as $t): ?>
                    <option value="<?= $t->id ?>"><?= htmlspecialchars($t->nombre_completo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="f-group"><label class="f-label">Departamento *</label>
                <select class="f-input" id="pc-depto">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($departamentos as $d): ?>
                    <option value="<?= $d->id ?>"><?= htmlspecialchars($d->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="f-group">
            <label class="f-label">Período Corto</label>
            <?php if (empty($periodos)): ?>
            <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:9px;padding:10px 13px;font-size:.82rem;color:#92400e;display:flex;align-items:center;gap:8px;">
                <i class="ti ti-alert-triangle"></i> Sin período corto activo. <a href="<?= URLROOT ?>/periodos" style="color:#1e3a8a;font-weight:700;">Crear en Configuración →</a>
            </div>
            <?php else: ?>
            <select class="f-input" id="pc-periodo">
                <option value="">— Sin especificar —</option>
                <?php foreach ($periodos as $p): ?>
                <option value="<?= $p->id ?>" <?= ($p->estado === 'Activo') ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p->nombre) ?> (<?= $p->estado ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>

        <div class="calc-box">
            <div style="font-size:.78rem;font-weight:700;color:#374151;margin-bottom:10px;"><i class="ti ti-calculator"></i> Calculadora de Culminación</div>
            <div class="f-row2" style="margin-bottom:10px;">
                <div>
                    <label class="f-label">Horas Meta <span class="sgp-tip" data-tip="Total de horas que el pasante debe cumplir. Valor estándar: 480 horas (3 meses). Ajusta según el plan de estudios.">?</span></label>
                    <div style="display:flex;gap:7px;align-items:center;">
                        <input type="number" class="f-input" id="pc-horas" value="480" min="1" oninput="pcRecalcular()" style="flex:1;">
                        <button type="button" onclick="document.getElementById('pc-horas').value=480;pcRecalcular();"
                                style="padding:8px 10px;background:#f0fdf4;color:#059669;border:1.5px solid #bbf7d0;border-radius:9px;font-size:.72rem;font-weight:700;cursor:pointer;white-space:nowrap;">3 meses</button>
                    </div>
                </div>
                <div><label class="f-label">Fecha de Inicio *</label>
                    <input type="date" class="f-input" id="pc-fecha-inicio" value="<?= date('Y-m-d') ?>" oninput="pcRecalcular()">
                </div>
            </div>
            <div class="proy-card">
                <div style="font-size:.72rem;font-weight:800;color:rgba(255,255,255,.6);text-transform:uppercase;margin-bottom:8px;"><i class="ti ti-sparkles"></i> Proyección</div>
                <div class="proy-dato"><span>Meta</span><strong id="pc-p-horas">480h</strong></div>
                <div class="proy-dato"><span>Intensidad</span><strong>8 h/día</strong></div>
                <div class="proy-dato"><span>Días hábiles</span><strong id="pc-p-dias">60 días</strong></div>
                <div class="proy-highlight">🎓 Culminación estimada: <span id="pc-p-fin">—</span></div>
            </div>
            <input type="hidden" id="pc-fecha-fin">
        </div>

        <div style="display:flex;gap:10px;margin-top:4px;">
            <button class="f-btn-cancel" onclick="cerrarModalPC('modalNuevoPasante')">Cancelar</button>
            <button class="f-btn-primary" onclick="guardarNuevoPasante()"><i class="ti ti-user-plus"></i> Registrar Pasante</button>
        </div>
    </div>
</div>
</div>

<!-- MODAL: Editar Pasante -->
<div id="modalEditarPasante" class="pc-modal-overlay">
<div class="pc-modal-box">
    <div class="pm-head">
        <div><h2><i class="ti ti-edit" style="margin-right:7px;"></i>Editar Pasante</h2>
        <p id="editPasSubtitle">Actualizar datos</p></div>
        <button class="pm-close" onclick="cerrarModalPC('modalEditarPasante')"><i class="ti ti-x"></i></button>
    </div>
    <div class="pm-body">
        <input type="hidden" id="edit-uid">
        <div class="f-section-lbl"><i class="ti ti-id-badge"></i> Datos Personales</div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Nombres</label><input type="text" class="f-input" id="edit-nombres"></div>
            <div class="f-group"><label class="f-label">Apellidos</label><input type="text" class="f-input" id="edit-apellidos"></div>
        </div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Carrera</label><input type="text" class="f-input" id="edit-carrera"></div>
            <div class="f-group"><label class="f-label">Institución</label>
                <select class="f-input" id="edit-inst">
                    <option value="">— Sin especificar —</option>
                    <?php foreach ($instituciones as $i): ?>
                    <option value="<?= $i->id ?>"><?= htmlspecialchars($i->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="f-section-lbl"><i class="ti ti-link"></i> Asignación</div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Tutor</label>
                <select class="f-input" id="edit-tutor">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($tutores as $t): ?>
                    <option value="<?= $t->id ?>"><?= htmlspecialchars($t->nombre_completo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="f-group"><label class="f-label">Departamento</label>
                <select class="f-input" id="edit-depto">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($departamentos as $d): ?>
                    <option value="<?= $d->id ?>"><?= htmlspecialchars($d->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Estado</label>
                <select class="f-input" id="edit-estado">
                    <option value="Activo">Activo</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Retirado">Retirado</option>
                </select>
            </div>
            <div class="f-group"><label class="f-label">Horas Meta</label>
                <input type="number" class="f-input" id="edit-horas" min="1">
            </div>
        </div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Fecha Inicio <span style="font-size:.65rem;color:#94a3b8;font-weight:500;">(del período)</span></label><input type="date" class="f-input" id="edit-fecha-inicio" readonly style="background:#f1f5f9;color:#64748b;"></div>
            <div class="f-group"><label class="f-label">Período</label>
                <select class="f-input" id="edit-periodo" onchange="editPeriodoChanged()">
                    <option value="">— Selecciona —</option>
                    <?php foreach ($periodos as $p): ?>
                    <option value="<?= $p->id ?>" data-inicio="<?= $p->fecha_inicio ?>" data-fin="<?= $p->fecha_fin ?>"><?= htmlspecialchars($p->nombre) ?> (<?= $p->estado ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:4px;">
            <button class="f-btn-cancel" onclick="cerrarModalPC('modalEditarPasante')">Cancelar</button>
            <button class="f-btn-primary" onclick="guardarEdicionPasante()"><i class="ti ti-device-floppy"></i> Guardar Cambios</button>
        </div>
    </div>
</div>
</div>

<!-- MODAL: Marcar Asistencia -->
<div id="modalMarcarAsist" class="pc-modal-overlay">
<div class="pc-modal-box pc-modal-sm">
    <div class="pm-head">
        <div><h2><i class="ti ti-calendar-check" style="margin-right:7px;"></i>Marcar Asistencia</h2></div>
        <button class="pm-close" onclick="cerrarModalPC('modalMarcarAsist')"><i class="ti ti-x"></i></button>
    </div>
    <div class="pm-body">
        <input type="hidden" id="ma-pid">
        <div class="f-group"><label class="f-label">Pasante</label>
            <select class="f-input" id="ma-sel" onchange="document.getElementById('ma-pid').value=this.value">
                <option value="">— Selecciona —</option>
                <?php foreach ($pasantesCortos as $pc): ?>
                <option value="<?= $pc->id ?>"><?= htmlspecialchars(($pc->nombres??'').' '.($pc->apellidos??'')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="f-row2">
            <div class="f-group"><label class="f-label">Fecha</label><input type="date" class="f-input" id="ma-fecha" value="<?= date('Y-m-d') ?>"></div>
            <div class="f-group"><label class="f-label">Estado</label>
                <select class="f-input" id="ma-estado">
                    <option value="Presente">✅ Presente</option>
                    <option value="Ausente">❌ Ausente</option>
                    <option value="Justificado">📋 Justificado</option>
                    <option value="Retardo">⚠️ Retardo</option>
                </select>
            </div>
        </div>
        <div class="f-group"><label class="f-label">Motivo / Nota</label><input type="text" class="f-input" id="ma-motivo" placeholder="Opcional..."></div>
        <div style="display:flex;gap:10px;">
            <button class="f-btn-cancel" onclick="cerrarModalPC('modalMarcarAsist')">Cancelar</button>
            <button class="f-btn-primary" onclick="enviarMarcarAsistencia()"><i class="ti ti-check"></i> Registrar</button>
        </div>
    </div>
</div>
</div>

<script>
var PC_CSRF = '<?= $csrfToken ?>';
var tabActual = 'diaria', pasanteAsistId = null;

// Filtro por estado
function filtrarEstado(btn) {
    document.querySelectorAll('.epill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const est = btn.dataset.estado;
    document.querySelectorAll('#tablaPasantes tr').forEach(tr => {
        tr.style.display = (est === 'Todos' || tr.dataset.estado === est) ? '' : 'none';
    });
}

// Modales
function abrirModalNuevoPasante() {
    <?php if (!$periodoCortoActivo): ?>
    Swal.fire({
        icon: 'warning',
        title: 'Acción Bloqueada',
        html: 'No puedes agregar pasantes nuevos mientras no exista un período corto <b>activo</b>.<br><br>Por favor, dirígete al módulo de <b>Períodos Académicos</b> para aperturar uno.',
        confirmButtonColor: '#2563eb',
        confirmButtonText: 'Ir a Períodos <i class="ti ti-arrow-right"></i>'
    }).then((r) => {
        if (r.isConfirmed) {
            window.location.href = '<?= URLROOT ?>/periodos';
        }
    });
    return;
    <?php endif; ?>
    document.getElementById('tempPassBox').style.display = 'none';
    document.getElementById('modalNuevoPasante').classList.add('active');
    pcRecalcular();
}
function cerrarModalPC(id) { document.getElementById(id).classList.remove('active'); }

function editPeriodoChanged() {
    var sel = document.getElementById('edit-periodo');
    var opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.inicio) {
        document.getElementById('edit-fecha-inicio').value = opt.dataset.inicio;
    }
}

function abrirEditarPasante(pc) {
    document.getElementById('edit-uid').value       = pc.id;
    document.getElementById('edit-nombres').value   = pc.nombres  ?? '';
    document.getElementById('edit-apellidos').value = pc.apellidos ?? '';
    document.getElementById('edit-carrera').value   = pc.cargo ?? '';
    document.getElementById('edit-horas').value     = pc.horas_meta ?? 480;
    document.getElementById('edit-fecha-inicio').value = pc.fecha_inicio_pasantia ?? '';
    document.getElementById('edit-estado').value    = pc.estado_pasantia ?? 'Activo';
    document.getElementById('editPasSubtitle').textContent = (pc.nombres??'') + ' ' + (pc.apellidos??'');
    // Pre-seleccionar período si tiene periodo_id
    var periodoSel = document.getElementById('edit-periodo');
    if (pc.periodo_id) {
        periodoSel.value = pc.periodo_id;
    } else {
        periodoSel.value = '';
    }
    document.getElementById('modalEditarPasante').classList.add('active');
}

function seleccionarAsist(id, nombre) {
    pasanteAsistId = id;
    cargarAsistencia();
    document.getElementById('asistContent').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function abrirMarcarAsist() {
    if (!pasanteAsistId) {
        pcToast('warning', 'Selecciona un pasante primero.');
        return;
    }
    document.getElementById('ma-pid').value = pasanteAsistId;
    document.getElementById('ma-sel').value = pasanteAsistId;
    document.getElementById('ma-fecha').value = new Date().toISOString().split('T')[0];
    document.getElementById('modalMarcarAsist').classList.add('active');
}

// ── Chips de selección de pasante ────────────────────────────
function seleccionarChip(id, nombre) {
    pasanteAsistId = id;
    document.querySelectorAll('.pasante-chip').forEach(c => {
        const check = c.querySelector('.chip-check');
        const isThis = parseInt(c.dataset.id) === id;
        c.style.borderColor = isThis ? '#7c3aed' : '#e2e8f0';
        c.style.background  = isThis ? '#f5f3ff' : '#fafcff';
        c.style.transform   = isThis ? 'translateY(-2px)' : '';
        c.style.boxShadow   = isThis ? '0 6px 16px rgba(124,58,237,.15)' : '';
        if (check) check.style.display = isThis ? 'block' : 'none';
    });
    const ini = nombre.split(' ').map(p=>p[0]||'').join('').toUpperCase().slice(0,2);
    document.getElementById('asistChipAvatar').textContent = ini;
    document.getElementById('asistNombre').textContent     = nombre;
    document.getElementById('asistControls').style.display = '';
    seleccionarAsist(id, nombre);
}

function deseleccionarChip() {
    document.querySelectorAll('.pasante-chip').forEach(c => {
        c.style.borderColor = '#e2e8f0'; c.style.background = '#fafcff';
        c.style.transform = ''; c.style.boxShadow = '';
        const check = c.querySelector('.chip-check');
        if (check) check.style.display = 'none';
    });
    document.getElementById('asistControls').style.display = 'none';
    pasanteAsistId = null;
    document.getElementById('asistContent').innerHTML = `
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:60px 30px;background:white;border-radius:22px;border:2px dashed #e2e8f0;gap:16px;margin:8px 0;">
            <div style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#f8fafc,#e2e8f0);display:flex;align-items:center;justify-content:center;font-size:2.8rem;color:#94a3b8;"><i class="ti ti-calendar-x"></i></div>
            <h3 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0;">Selecciona un Pasante</h3>
            <p style="color:#64748b;max-width:320px;line-height:1.7;margin:0;font-size:.9rem;">Haz clic en una tarjeta para ver su historial de asistencia.</p>
        </div>`;
}

function filtrarChips(q) {
    const term = q.toLowerCase().trim();
    document.querySelectorAll('.pasante-chip').forEach(c => {
        c.style.display = !term || c.dataset.search.includes(term) ? '' : 'none';
    });
}

// ── Verificación cédula en tiempo real (modal nuevo pasante) ─
var scVerifyTimer = null;
function scVerificarCedulaRT(input) {
    const fila = input.closest('.sc-part-row');
    const ico  = fila?.querySelector('.sc-ced-ico');
    const msg  = fila?.querySelector('.sc-ced-msg');
    const ced  = input.value.trim();
    input.style.borderColor = '#7c3aed';
    if (ico) { ico.textContent = ''; }
    if (msg) { msg.style.display = 'none'; }
    if (!ced || ced.length < 6) return;
    // Duplicado en modal
    let count = 0;
    document.querySelectorAll('#scFilas [data-field="cedula"]').forEach(el => {
        if (el !== input && el.value.trim() === ced) count++;
    });
    if (count > 0) {
        input.style.borderColor = '#dc2626';
        if (ico) { ico.textContent = '✗'; ico.style.color = '#dc2626'; }
        if (msg) { msg.style.cssText = 'display:block;background:#fee2e2;color:#dc2626;font-size:.71rem;font-weight:700;padding:3px 8px;border-radius:6px;margin-top:6px;'; msg.textContent = 'Cédula duplicada en este grupo.'; }
        return;
    }
    clearTimeout(scVerifyTimer);
    if (ico) { ico.textContent = '…'; ico.style.color = '#94a3b8'; }
    scVerifyTimer = setTimeout(() => {
        fetch(`${URLROOT}/actividades/verificarCedula?cedula=${encodeURIComponent(ced)}`)
        .then(r=>r.json()).then(res => {
            if (res.existe) {
                input.style.borderColor = '#f59e0b';
                if (ico) { ico.textContent = '⚠'; ico.style.color = '#f59e0b'; }
                if (msg) { msg.style.cssText = 'display:block;background:#fef3c7;color:#b45309;font-size:.71rem;font-weight:700;padding:3px 8px;border-radius:6px;margin-top:6px;'; msg.textContent = res.mensaje; }
            } else {
                input.style.borderColor = '#10b981';
                if (ico) { ico.textContent = '✓'; ico.style.color = '#10b981'; }
                if (msg) { msg.style.display = 'none'; }
            }
        }).catch(() => { if (ico) ico.textContent = ''; });
    }, 450);
}

// ── Marcar desde inasistencias ───────────────────────────────
function marcarDesdeInasist(id, nombre) {
    seleccionarChip(id, nombre);
    document.getElementById('asistControls')?.scrollIntoView({ behavior:'smooth', block:'center' });
    setTimeout(() => { pasanteAsistId = id; abrirMarcarAsist(); }, 500);
}

// ── Eliminar pasante corto ───────────────────────────────────
function confirmarEliminarPasanteCorto(id, nombre) {
    Swal.fire({
        title: '¿Eliminar pasante?',
        html: `<span style="color:#64748b;">Se eliminará a <strong style="color:#0f172a;">${nombre}</strong> y todos sus registros de asistencia.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        focusCancel: true,
    }).then(result => {
        if (!result.isConfirmed) return;
        fetch(URLROOT + '/actividades/eliminarPasanteCorto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': PC_CSRF },
            body: JSON.stringify({ usuario_id: id, csrf_token: PC_CSRF })
        }).then(r=>r.json()).then(res => {
            if (res.success) {
                Swal.fire({ icon:'success', title:'Eliminado', text:'El pasante fue eliminado correctamente.', timer:1800, showConfirmButton:false })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon:'error', title:'Error', text: res.message || 'No se pudo eliminar.' });
            }
        });
    });
}

// Calculadora
function pcRecalcular() {
    const horas = parseInt(document.getElementById('pc-horas').value) || 480;
    const fechaStr = document.getElementById('pc-fecha-inicio').value;
    const diasHab = Math.ceil(horas / 8);
    document.getElementById('pc-p-horas').textContent = horas + 'h';
    document.getElementById('pc-p-dias').textContent  = diasHab + ' días';
    if (fechaStr) {
        const d = new Date(fechaStr + 'T00:00:00');
        let count = 0, cur = new Date(d);
        while (count < diasHab) { cur.setDate(cur.getDate()+1); const dn=cur.getDay(); if(dn!==0&&dn!==6)count++; }
        document.getElementById('pc-p-fin').textContent = cur.toLocaleDateString('es-VE',{day:'2-digit',month:'2-digit',year:'numeric'});
        document.getElementById('pc-fecha-fin').value = cur.toISOString().split('T')[0];
    }
}

// Validación en tiempo real de campos únicos
var _pcVerifTimers = _pcVerifTimers || {};
var _pcVerifErrors = _pcVerifErrors || {};

function verificarCampoPc(campo, valor, msgId) {
    clearTimeout(_pcVerifTimers[campo]);
    const msgEl = document.getElementById(msgId);
    if (!msgEl) return;
    if (!valor || valor.length < 3) {
        msgEl.textContent = ''; msgEl.className = 'pc-field-msg';
        delete _pcVerifErrors[campo]; return;
    }
    msgEl.textContent = '⏳ Verificando...'; msgEl.className = 'pc-field-msg';
    _pcVerifTimers[campo] = setTimeout(async () => {
        try {
            const r   = await fetch(`${URLROOT}/actividades/verificarCampo?campo=${encodeURIComponent(campo)}&valor=${encodeURIComponent(valor)}`);
            const res = await r.json();
            if (res.disponible) {
                msgEl.textContent = '✔ Disponible'; msgEl.className = 'pc-field-msg success';
                delete _pcVerifErrors[campo];
            } else {
                msgEl.textContent = '✖ ' + res.mensaje; msgEl.className = 'pc-field-msg error';
                _pcVerifErrors[campo] = true;
            }
        } catch(e) {
            msgEl.textContent = ''; msgEl.className = 'pc-field-msg';
        }
    }, 600);
}

function pcToast(icon, title) {
    Swal.fire({ toast:true, position:'top-end', icon, title, showConfirmButton:false,
        timer:3500, timerProgressBar:true, customClass:{popup:'sgp-swal-toast'},
        didOpen: t => { t.addEventListener('mouseenter',Swal.stopTimer); t.addEventListener('mouseleave',Swal.resumeTimer); }
    });
}

// AJAX — Nuevo pasante
async function guardarNuevoPasante() {
    const nombres = document.getElementById('pc-nombres').value.trim();
    const apellidos = document.getElementById('pc-apellidos').value.trim();
    const cedula  = document.getElementById('pc-cedula').value.trim();
    const correo  = document.getElementById('pc-correo').value.trim();
    const fi      = document.getElementById('pc-fecha-inicio').value;
    if (!nombres||!apellidos||!cedula||!correo||!fi) { pcToast('warning','Completa los campos obligatorios.'); return; }
    if (Object.keys(_pcVerifErrors).length > 0) { pcToast('error','Corrige los campos marcados en rojo antes de continuar.'); return; }
    const body = { csrf_token:PC_CSRF, nombres, apellidos, cedula, correo,
        carrera: document.getElementById('pc-carrera').value.trim(),
        institucion_id: document.getElementById('pc-inst').value,
        tutor_id: document.getElementById('pc-tutor').value,
        departamento_id: document.getElementById('pc-depto').value,
        periodo_id: document.getElementById('pc-periodo')?.value ?? '',
        fecha_inicio: fi, horas_meta: document.getElementById('pc-horas').value,
        fecha_fin: document.getElementById('pc-fecha-fin').value };
    const r = await fetch(URLROOT+'/actividades/crearPasanteCorto',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':PC_CSRF},body:JSON.stringify(body)});
    const res = await r.json();
    if (res.success) {
        document.getElementById('tempPassVal').textContent = res.temp_password;
        document.getElementById('pinKioscoVal').textContent = res.pin_kiosco;
        const box = document.getElementById('tempPassBox');
        box.style.display='block'; box.scrollIntoView({behavior:'smooth'});
        pcToast('success', `${nombres} ${apellidos} registrado correctamente.`);
        setTimeout(()=>location.reload(), 4000);
    } else {
        pcToast('error', res.message||'Error al registrar el pasante.');
    }
}

// AJAX — Editar pasante
async function guardarEdicionPasante() {
    const nombres = document.getElementById('edit-nombres').value.trim();
    const apellidos = document.getElementById('edit-apellidos').value.trim();
    const body = { csrf_token:PC_CSRF, usuario_id: document.getElementById('edit-uid').value,
        nombres, apellidos,
        carrera: document.getElementById('edit-carrera').value.trim(),
        institucion_id: document.getElementById('edit-inst').value,
        tutor_id: document.getElementById('edit-tutor').value,
        departamento_id: document.getElementById('edit-depto').value,
        periodo_id: document.getElementById('edit-periodo').value,
        estado_pasantia: document.getElementById('edit-estado').value,
        fecha_inicio: document.getElementById('edit-fecha-inicio').value,
        horas_meta: document.getElementById('edit-horas').value };
    const r = await fetch(URLROOT+'/actividades/editarPasanteCorto',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':PC_CSRF},body:JSON.stringify(body)});
    const res = await r.json();
    if (res.success) {
        cerrarModalPC('modalEditarPasante');
        pcToast('success', `${nombres} ${apellidos} actualizado correctamente.`);
        setTimeout(()=>location.reload(), 1800);
    } else {
        pcToast('error', res.message||'Error al actualizar el pasante.');
    }
}

// Tabs asistencia
function cambiarTab(modo, btn) {
    tabActual = modo;
    document.querySelectorAll('.asist-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    cargarAsistencia();
}

async function cargarAsistencia() {
    const pid   = pasanteAsistId;
    const fecha = document.getElementById('inputFechaAsist')?.value || new Date().toISOString().split('T')[0];
    if (!pid) return;
    const r   = await fetch(`${URLROOT}/actividades/asistenciaCorto?pasante_id=${pid}&modo=${tabActual}&fecha=${fecha}`);
    const res = await r.json();
    if (!res.success) return;
    const cont = document.getElementById('asistContent');
    if (tabActual==='diaria') cont.innerHTML = renderDiaria(res);
    else if (tabActual==='semanal') cont.innerHTML = renderSemanal(res);
    else cont.innerHTML = renderMensual(res, fecha);
}

function estadoPill(e) {
    if (!e) return '<span class="estado-pill ep-nd">Sin registro</span>';
    const m = {Presente:'ep-p',Ausente:'ep-a',Justificado:'ep-j',Retardo:'ep-r'};
    return `<span class="estado-pill ${m[e]??'ep-nd'}">${e}</span>`;
}
function renderDiaria(res) {
    const dias=['Lunes','Martes','Miércoles','Jueves','Viernes'];
    const inicio=new Date(res.inicio+'T00:00:00');
    let html='<table class="asist-day-table"><thead><tr>'+dias.map(d=>`<th>${d}</th>`).join('')+'</tr></thead><tbody><tr>';
    for(let i=0;i<5;i++){
        const d=new Date(inicio);d.setDate(d.getDate()+i);
        const fStr=d.toISOString().split('T')[0],reg=res.registros[fStr];
        html+=`<td><div style="font-size:.72rem;color:#94a3b8;margin-bottom:5px;">${d.getDate()}/${d.getMonth()+1}</div>${estadoPill(reg?.estado)}${reg?.hora?`<div style="font-size:.7rem;color:#94a3b8;margin-top:3px;">${reg.hora}</div>`:''}</td>`;
    }
    return html+'</tr></tbody></table>';
}
function renderSemanal(res) {
    const inicio=new Date(res.inicio+'T00:00:00'),fin=new Date(res.fin+'T00:00:00');
    let stats={Presente:0,Ausente:0,Justificado:0,Retardo:0,sinReg:0},rows='',cur=new Date(inicio);
    while(cur<=fin){
        const fStr=cur.toISOString().split('T')[0],dn=cur.getDay();
        if(dn>=1&&dn<=5){const reg=res.registros[fStr];if(reg)stats[reg.estado]=(stats[reg.estado]||0)+1;else stats.sinReg++;
        rows+=`<tr><td>${cur.toLocaleDateString('es-VE',{weekday:'long',day:'2-digit',month:'2-digit'})}</td><td>${estadoPill(reg?.estado)}</td><td style="font-size:.8rem;color:#64748b;">${reg?.hora??'—'}</td><td style="font-size:.8rem;color:#94a3b8;">${reg?.metodo??'—'}</td></tr>`;}
        cur.setDate(cur.getDate()+1);
    }
    return `<table class="asist-day-table"><thead><tr><th style="text-align:left;">Día</th><th>Estado</th><th>Hora</th><th>Método</th></tr></thead><tbody>${rows}</tbody></table>
    <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">${Object.entries(stats).map(([k,v])=>`<div style="background:#f8fafc;padding:7px 14px;border-radius:10px;font-size:.8rem;"><strong>${v}</strong> <span style="color:#94a3b8;">${k}</span></div>`).join('')}</div>`;
}
function renderMensual(res,fecha){
    const [y,m]=fecha.split('-').map(Number),primerDia=new Date(y,m-1,1),ultimoDia=new Date(y,m,0).getDate();
    const inicioSem=(primerDia.getDay()+6)%7,dias=['Lu','Ma','Mi','Ju','Vi','Sa','Do'];
    let html='<div class="cal-grid-wrap">'+dias.map(d=>`<div class="cal-day-hdr">${d}</div>`).join('');
    for(let i=0;i<inicioSem;i++)html+='<div class="cal-cell empty"></div>';
    for(let d=1;d<=ultimoDia;d++){
        const fStr=`${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`,dn=new Date(fStr+'T00:00:00').getDay();
        const esFinde=dn===0||dn===6,reg=res.registros[fStr];
        const cls=esFinde?'finde':(reg?({Presente:'cp',Ausente:'ca',Justificado:'cj',Retardo:'cr'}[reg.estado]??'sin'):'sin');
        html+=`<div class="cal-cell ${cls}" title="${fStr}${reg?' · '+reg.estado:''}">${d}</div>`;
    }
    return html+'</div>';
}

async function enviarMarcarAsistencia() {
    const pid = document.getElementById('ma-pid').value || document.getElementById('ma-sel').value;
    if (!pid) { pcToast('warning', 'Selecciona un pasante.'); return; }
    const btnReg = document.querySelector('#modalMarcarAsist .f-btn-primary');
    if (btnReg) { btnReg.disabled = true; btnReg.innerHTML = '<i class="ti ti-loader"></i> Registrando...'; }
    const body = {
        csrf_token: PC_CSRF, pasante_id: pid,
        fecha:  document.getElementById('ma-fecha').value,
        estado: document.getElementById('ma-estado').value,
        motivo: (document.getElementById('ma-motivo')?.value || '').trim()
    };
    try {
        const r = await fetch(URLROOT + '/actividades/marcarAsistenciaCorto', {
            method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':PC_CSRF},
            body: JSON.stringify(body)
        });
        let res;
        try { res = await r.json(); }
        catch { throw new Error('Respuesta inválida del servidor (HTTP ' + r.status + ')'); }
        if (res.success) {
            cerrarModalPC('modalMarcarAsist');
            if (document.getElementById('ma-motivo')) document.getElementById('ma-motivo').value = '';
            pcToast('success', 'Asistencia registrada correctamente.');
            if (pasanteAsistId == pid) cargarAsistencia();
        } else {
            pcToast('error', res.message || 'Error al registrar la asistencia.');
        }
    } catch (err) {
        pcToast('error', 'Error de conexión: ' + err.message);
        console.error('[marcarAsistencia]', err);
    } finally {
        if (btnReg) { btnReg.disabled = false; btnReg.innerHTML = '<i class="ti ti-check"></i> Registrar'; }
    }
}

// Escape / overlay
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.pc-modal-overlay.active').forEach(m=>m.classList.remove('active'));});
document.querySelectorAll('.pc-modal-overlay').forEach(m=>m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('active');}));
document.addEventListener('DOMContentLoaded', pcRecalcular);
</script>
