<?php
/**
 * Vista: Detalle de Período Académico (Cohorte)
 * Muestra los pasantes agrupados por departamento mediante acordeones
 * con opciones individuales y masivas.
 */
?>
<style>
/* ── Modal overlay ─────────────────────────────────────────────────── */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,0.7); backdrop-filter: blur(6px);
    z-index: 1000; align-items: center; justify-content: center;
    animation: act-fadeIn 0.2s ease;
}
.modal-overlay.active { display: flex; }
@keyframes act-fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes act-slideUp { from { transform:translateY(24px);opacity:0; } to { transform:translateY(0);opacity:1; } }

.modal-box {
    background: white; border-radius: 20px;
    width: 90%; max-width: 480px; max-height: 90vh;
    display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 24px 60px rgba(15,23,42,0.25);
    animation: act-slideUp 0.3s ease;
}
.modal-head {
    background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
    padding: 24px; display: flex; justify-content: space-between; align-items: center;
    color: white;
}
.modal-head h2 { font-size:1.25rem; font-weight:700; margin:0; color:white; }
.modal-head p  { font-size:0.85rem; margin:4px 0 0; color:rgba(255,255,255,0.8); }
.btn-close-modal { background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.btn-close-modal:hover { background: rgba(255,255,255,0.35); }
.modal-body { padding: 24px; overflow-y: auto; flex: 1; }

.form-group { margin-bottom: 20px; }
.form-label { display:block; font-size:0.8rem; font-weight:700; color:#374151; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px; }
.form-input { width:100%; padding:12px; border:2px solid #e5e7eb; border-radius:12px; font-size:0.95rem; color:#1e293b; background:#fafafa; transition: border-color 0.2s; }
.form-input:focus { outline:none; border-color:#2563eb; }
.btn-submit { width:100%; padding:14px; background:linear-gradient(135deg,#172554 0%,#2563eb 100%); color:white; border:none; border-radius:12px; font-size:0.95rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; }

/* ── KPI Cards V2 ─────────────────────────────────────────────────── */
.per-kpi-ver2 {
    background: white; border-radius: 20px; padding: 24px;
    display: flex; flex-direction: column; justify-content: space-between;
    box-shadow: 0 4px 15px rgba(22,38,96,0.05); border-left: 5px solid transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.per-kpi-ver2:hover { transform: translateY(-4px); box-shadow: 0 12px 25px rgba(22,38,96,0.1); }
.per-kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.per-kpi-label { font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; }
.per-kpi-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
.per-kpi-val { font-size: 2.2rem; font-weight: 800; color: #1e293b; line-height: 1; margin: 0; }

/* ── Accordion por Departamento ───────────────────────────────────── */
.dept-accordion { background: white; border-radius: 16px; margin-bottom: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); overflow: hidden; }
.dept-acc-header { padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: background 0.2s; user-select: none; }
.dept-acc-header:hover { background: #f8fafc; }
.dept-acc-title { display: flex; align-items: center; gap: 12px; }
.dept-acc-icon { width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.dept-acc-name { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; }
.dept-acc-count { background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
.dept-acc-toggle { font-size: 1.2rem; color: #94a3b8; transition: transform 0.3s; }
.dept-accordion.open .dept-acc-toggle { transform: rotate(180deg); }
.dept-acc-body { display: none; padding: 0 24px 24px; border-top: 1px solid #f1f5f9; background: #f8fafc; }
.dept-accordion.open .dept-acc-body { display: block; animation: act-fadeIn 0.3s ease; }

/* ── Bento Pasantes Cards V2 ──────────────────────────────────────── */
.pasantes-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; }
@media (max-width: 1300px) { .pasantes-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px)  { .pasantes-grid { grid-template-columns: 1fr; } }
@media (max-width: 1024px) { .kpi-grid-4 { grid-template-columns: repeat(2,1fr) !important; } }
@media (max-width: 600px)  { .kpi-grid-4 { grid-template-columns: 1fr !important; } }

.sw-pcard {
    background: white; border-radius: 16px; border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02); padding: 18px;
    display: flex; flex-direction: column; transition: all 0.2s;
}
.sw-pcard:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.06); border-color:#cbd5e1; }
.sw-pcard-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.sw-pcard-user { display: flex; align-items: center; gap: 12px; }
.sw-pcard-av { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 800; color: white; flex-shrink: 0; }
.sw-pcard-info h4 { margin: 0; font-size: 1rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.sw-pcard-info p { margin: 2px 0 0; font-size: 0.75rem; color: #64748b; }

.b-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; height: fit-content;}
.b-b-activo { background: #dcfce7; color: #166534; }
.b-b-finalizado { background: #e0e7ff; color: #3730a3; }
.b-b-pendiente { background: #fef3c7; color: #92400e; }
.b-b-inactivo { background: #fef2f2; color: #991b1b; }
.b-b-custom-date {
    background: linear-gradient(135deg, #fdf4ff, #ede9fe);
    color: #7c3aed;
    border: 1px solid #ddd6fe;
    font-size: 0.62rem;
    padding: 3px 8px;
    cursor: default;
    position: relative;
}
.b-b-custom-date::before { content: ''; display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: #7c3aed; margin-right: 3px; flex-shrink: 0; }
.sw-pcard-badges { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

.sw-pcard-stats { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; border: 1px dashed #cbd5e1; }
.sw-stat-box { display: flex; flex-direction: column; align-items: center; }
.sw-stat-val { font-size: 1.1rem; font-weight: 800; color: #1e293b; }
.sw-stat-lbl { font-size: 0.65rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-top: 2px; }

.sw-pcard-progress { margin-bottom: 16px; }
.sw-prog-info { display: flex; justify-content: space-between; font-size: 0.7rem; color: #475569; font-weight: 600; margin-bottom: 6px; }
.sw-prog-bar { height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
.sw-prog-fill { height: 100%; border-radius: 4px; }

.sw-pcard-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: auto; }
.btn-p-acti { display:flex; align-items:center; justify-content:center; gap:6px; padding: 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-decoration: none; border:none; cursor:pointer; transition:all 0.2s; white-space: nowrap;}
.btn-asis { background: rgba(16,185,129,0.1); color: #059669; }
.btn-asis:hover { background: #10b981; color: white; }
.btn-carta { background: rgba(37,99,235,0.1); color: #2563eb; }
.btn-carta:hover { background: #2563eb; color: white; }
.btn-alm { background: rgba(245,158,11,0.1); color: #d97706; }
.btn-alm:hover { background: #f59e0b; color: white; }
.btn-off { background: rgba(220,38,38,0.1); color: #dc2626; }
.btn-off:hover { background: #dc2626; color: white; }
.btn-trim { background: rgba(6,182,212,0.1); color: #0891b2; }
.btn-trim:hover { background: #06b6d4; color: white; }
.btn-aj { background: rgba(124,58,237,0.1); color: #7c3aed; }
.btn-aj:hover { background: #7c3aed; color: white; }

/* ── Pills de Trimestre (mismo estilo que módulo Reportes) ────── */
.rpt-pill {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 10px 12px; border-radius: 50px; border: 1.5px solid #dde2f0;
    background: #f8fafc; color: #3A4768; font-size: 0.85rem; font-weight: 600;
    cursor: pointer; user-select: none; transition: all 0.2s ease;
    width: 100%; text-align: center;
}
.rpt-pill input[type="radio"] { display: none; }
.rpt-pill:hover { border-color: #1D4ED8; color: #1D4ED8; background: rgba(29,78,216,0.07); }
.rpt-pill:has(input[type="radio"]:checked) {
    background: #162660; border-color: #162660; color: #ffffff;
    box-shadow: 0 4px 12px rgba(22,38,96,0.25);
}
/* ── Input de fecha para el modal ─────────────────────────────── */
.rpt-date-input {
    width: 100%; padding: 12px 16px; border: 2px solid #f1f5f9; border-radius: 14px;
    font-size: 0.9rem; background: #f8fafc; color: #0D1424; outline: none;
    transition: all 0.2s;
}
.rpt-date-input:focus { border-color: #1D4ED8; background: #fff; box-shadow: 0 0 0 4px rgba(29,78,216,0.07); }
</style>

<div class="dashboard-container">

    <?php
    // Estado visual del periodo
    switch ($periodo->estado) {
        case 'Activo':  $estCol = '#10b981'; $estBg = 'rgba(16,185,129,0.15)'; $estIco = 'ti-player-play'; break;
        case 'Cerrado': $estCol = '#94a3b8'; $estBg = 'rgba(255,255,255,0.15)'; $estIco = 'ti-lock'; break;
        default:        $estCol = '#f59e0b'; $estBg = 'rgba(245,158,11,0.15)'; $estIco = 'ti-clock';
    }
    ?>

    <!-- ── Banner Bento del Período ─────────────────────────────────────── -->
    <div style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:24px;padding:32px;margin-bottom:24px;position:relative;overflow:hidden;box-shadow:0 10px 30px rgba(30,58,138,0.25);">
        <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);border-radius:50%;pointer-events:none;"></div>
        
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:20px;position:relative;z-index:1;">
            <div style="display:flex;align-items:center;gap:20px;">
                <div style="background:rgba(255,255,255,0.15);backdrop-filter:blur(10px);width:70px;height:70px;border-radius:18px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">
                    <i class="ti ti-calendar-event" style="font-size:2.2rem;color:white;"></i>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px;">
                        <h1 style="color:white;font-size:1.8rem;font-weight:800;margin:0;letter-spacing:-0.5px;">
                            <?= htmlspecialchars($periodo->nombre) ?>
                        </h1>
                        <span style="background:<?= $estBg ?>;color:<?= $estCol ?>;border:1px solid <?= $estCol ?>;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                            <i class="ti <?= $estIco ?>"></i> <?= htmlspecialchars($periodo->estado) ?>
                        </span>
                    </div>
                    <p style="color:rgba(255,255,255,0.8);margin:0;font-size:0.9rem;display:flex;align-items:center;gap:8px;">
                        <i class="ti ti-calendar"></i> <?= date('d/m/Y', strtotime($periodo->fecha_inicio)) ?> &rarr; <?= date('d/m/Y', strtotime($periodo->fecha_fin)) ?>
                    </p>
                </div>
            </div>
            
            <!-- Barra de Herramientas Global — compacta -->
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:rgba(255,255,255,0.1);padding:8px 10px;border-radius:14px;border:1px solid rgba(255,255,255,0.15);backdrop-filter:blur(10px);">
                <a href="<?= URLROOT ?>/periodos" style="background:rgba(255,255,255,0.2);color:white;padding:7px 12px;border-radius:8px;font-weight:600;font-size:0.78rem;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="ti ti-arrow-left"></i> Volver a Cohortes
                </a>

                <a href="#" onclick="generarInformePeriodo(<?= (int)$periodo->id ?>, '<?= htmlspecialchars($periodo->estado) ?>', event)" style="background:white;color:#1e3a8a;border:none;padding:7px 12px;border-radius:8px;font-weight:700;font-size:0.78rem;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all 0.2s;box-shadow:0 2px 8px rgba(0,0,0,0.1);" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                    <i class="ti ti-file-analytics"></i> Informe de Período
                </a>

                <?php if (strtolower($periodo->estado) === 'activo'): ?>
                <button onclick="abrirAjustarInstitucion()"
                        style="background:rgba(37,99,235,0.9);color:white;border:1px solid #60a5fa;padding:7px 12px;border-radius:8px;font-weight:700;font-size:0.78rem;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .2s;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='rgba(37,99,235,0.9)'">
                    <i class="ti ti-building-community"></i> Ajustar por Institución
                </button>
                <button onclick="ajustarTodosA1440(<?= (int)$periodo->id ?>)"
                        style="background:rgba(124,58,237,0.9);color:white;border:1px solid #8b5cf6;padding:7px 12px;border-radius:8px;font-weight:700;font-size:0.78rem;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all 0.2s;" onmouseover="this.style.background='#7c3aed'" onmouseout="this.style.background='rgba(124,58,237,0.9)'">
                    <i class="ti ti-clock-bolt"></i> Ajustar todos a 1440h
                </button>
                <button onclick="confirmarCerrarDesde(<?= (int)$periodo->id ?>, '<?= htmlspecialchars(addslashes($periodo->nombre)) ?>', <?= (int)$kpiActivos ?>)"
                        style="background:rgba(220,38,38,0.9);color:white;border:1px solid #ef4444;padding:7px 12px;border-radius:8px;font-weight:700;font-size:0.78rem;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='rgba(220,38,38,0.9)'">
                    <i class="ti ti-power"></i> Finalizar Cohorte (Desactivar)
                </button>
                <?php elseif (strtolower($periodo->estado) === 'cerrado'): ?>
                <button onclick="reabrirPeriodoDesdever(<?= (int)$periodo->id ?>, '<?= htmlspecialchars(addslashes($periodo->nombre)) ?>')"
                        style="background:rgba(5,150,105,0.9);color:white;border:1px solid #10b981;padding:7px 12px;border-radius:8px;font-weight:700;font-size:0.78rem;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all 0.2s;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='rgba(5,150,105,0.9)'">
                    <i class="ti ti-lock-open"></i> Reabrir Período
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── KPI Cards ──────────────────────────────────────────────── -->
    <div class="kpi-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-bottom:32px;">
        <?php
        $kpis = [
            ['lbl' => 'Pasantes Cohorte', 'v' => $kpiPasantes,    'c' => '#2563eb', 'ico' => 'ti-users'],
            ['lbl' => 'En Pasantía',      'v' => $kpiActivos,     'c' => '#10b981', 'ico' => 'ti-user-check'],
            ['lbl' => 'Culminados',       'v' => $kpiFinalizados, 'c' => '#8b5cf6', 'ico' => 'ti-award'],
            ['lbl' => 'Prom. Asistencia', 'v' => $promAsistencia . '%', 'c' => '#f59e0b', 'ico' => 'ti-chart-bar'],
        ];
        foreach ($kpis as $k): ?>
        <div class="per-kpi-ver2" style="border-left-color: <?= $k['c'] ?>;">
            <div class="per-kpi-header">
                <p class="per-kpi-label"><?= $k['lbl'] ?></p>
                <div class="per-kpi-icon" style="background:<?= $k['c'] ?>18; color:<?= $k['c'] ?>;">
                    <i class="ti <?= $k['ico'] ?>"></i>
                </div>
            </div>
            <h3 class="per-kpi-val"><?= $k['v'] ?></h3>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Grupos por Departamento ──────────────────────────────────────── -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <h2 style="font-size:1.3rem;font-weight:800;color:#1e293b;margin:0;display:flex;align-items:center;gap:10px;">
            <i class="ti ti-building-community" style="padding:6px;background:#eff6ff;color:#2563eb;border-radius:8px;"></i>
            Grupos de Trabajo
        </h2>
    </div>

    <?php if (empty($pasantesPorDepto)): ?>
    <div style="background:white; border-radius:24px; padding:60px 20px; text-align:center; border:1px dashed #cbd5e1; box-shadow:0 10px 40px rgba(0,0,0,0.03);">
        <div style="width:100px; height:100px; background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; color:#94a3b8; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05) inset;">
            <i class="ti ti-users-group" style="font-size:3.5rem;"></i>
        </div>
        <h3 style="font-size:1.4rem; font-weight:800; margin:0 0 12px; color:#1e293b; letter-spacing:-0.5px;">Cohorte Reciente — Esperando Asignaciones</h3>
        <p style="color:#64748b; margin:0 auto; max-width:550px; font-size:1.05rem; line-height:1.6;">
            Aún no hay pasantes vinculados a esta línea de tiempo. Los estudiantes aparecerán listados aquí <b>automáticamente</b> en cuanto se consolide su ingreso desde el <a href="<?= URLROOT ?>/asignaciones" style="color:#2563eb; font-weight:700; text-decoration:none;"><i class="ti ti-target-arrow"></i> Módulo de Asignaciones</a>.
        </p>
    </div>
    <?php else: ?>

        <?php 
        $coloresDep = ['#2563eb', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4'];
        $idxD = 0;
        
        foreach ($pasantesPorDepto as $depto => $pasantesList): 
            $dColor = $coloresDep[$idxD % count($coloresDep)]; $idxD++;
            $totalDep = count($pasantesList);
            // Default abierto el primer acordeón
            $isOpenClass = ($idxD === 1) ? 'open' : '';
        ?>
        <div class="dept-accordion <?= $isOpenClass ?>">
            <div class="dept-acc-header" onclick="this.parentElement.classList.toggle('open')">
                <div class="dept-acc-title">
                    <div class="dept-acc-icon" style="background:<?= $dColor ?>15; color:<?= $dColor ?>;"><i class="ti ti-folder-open"></i></div>
                    <h3 class="dept-acc-name"><?= htmlspecialchars($depto) ?></h3>
                    <span class="dept-acc-count"><?= $totalDep ?> pasante<?= $totalDep !== 1 ? 's' : '' ?></span>
                </div>
                <div class="dept-acc-toggle"><i class="ti ti-chevron-down"></i></div>
            </div>
            
            <div class="dept-acc-body">
                <div class="pasantes-grid">
                    <?php foreach ($pasantesList as $p):
                        $iniciales = strtoupper(substr($p->nombres ?? '?', 0, 1) . substr($p->apellidos ?? '', 0, 1));
                        $nombreCompl = htmlspecialchars(($p->nombres ?? '') . ' ' . ($p->apellidos ?? ''));
                        
                        // Progreso Horas
                        $hMeta = (int)($p->horas_meta ?? 1440);
                        $hAcum = (int)($p->horas_acumuladas ?? 0);
                        $progreso = $hMeta > 0 ? round(($hAcum / $hMeta) * 100) : 0;
                        $progColor = $progreso >= 80 ? '#10b981' : ($progreso >= 50 ? '#f59e0b' : '#ef4444');
                        if($progreso > 100) $progreso = 100;

                        // Tasa: solo días físicamente presentes / total registrado
                        $diasMeta        = min((int)($p->dias_meta ?? 0), (int)floor($hMeta / 8));
                        $diasPres        = (int)($p->dias_presentes     ?? 0);
                        $diasJust        = (int)($p->dias_justificados  ?? 0);
                        $diasAus         = (int)($p->dias_ausentes      ?? 0);
                        $totalReg        = (int)($p->total_dias_registrados ?? 0);
                        $pctAsist        = $totalReg > 0 ? round((($diasPres + $diasJust) / $totalReg) * 100) : 0;
                        
                        // Estado y color
                        $bBadgeClass = 'b-b-sinasig';
                        if ($p->estado_usuario === 'inactivo') {
                            $bBadgeClass   = 'b-b-inactivo';
                            $p->estado_pasantia = 'Finalizado';
                        } else {
                            switch ($p->estado_pasantia) {
                                case 'Activo':     $bBadgeClass = 'b-b-activo';     break;
                                case 'Finalizado': $bBadgeClass = 'b-b-finalizado'; break;
                                case 'Pendiente':  $bBadgeClass = 'b-b-pendiente';  break;
                            }
                        }

                        // Badge de fecha personalizada
                        $tieneFechaCustom = !empty($p->fecha_inicio_pasantia)
                            && !empty($periodo->fecha_inicio)
                            && substr($p->fecha_inicio_pasantia, 0, 10) !== substr($periodo->fecha_inicio, 0, 10);
                        $fechaCustomLabel = $tieneFechaCustom
                            ? date('d/m/Y', strtotime($p->fecha_inicio_pasantia))
                            : '';
                    ?>
                    <div class="sw-pcard">
                        <div class="sw-pcard-head">
                            <div class="sw-pcard-user">
                                <div class="sw-pcard-av" style="background: linear-gradient(135deg, <?= $dColor ?>, <?= $dColor ?>99);"><?= htmlspecialchars($iniciales) ?></div>
                                <div class="sw-pcard-info">
                                    <h4><?= $nombreCompl ?></h4>
                                    <p>V-<?= htmlspecialchars($p->cedula ?? '—') ?></p>
                                    <?php if (!empty($p->institucion)): ?>
                                    <span style="display:inline-flex;align-items:center;gap:3px;margin-top:3px;background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;border-radius:6px;padding:2px 7px;font-size:.62rem;font-weight:700;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($p->institucion) ?>">
                                        <i class="ti ti-building" style="font-size:.65rem;flex-shrink:0;"></i>
                                        <?= htmlspecialchars(mb_strimwidth($p->institucion, 0, 28, '…')) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="sw-pcard-badges">
                                <?php if ($tieneFechaCustom): ?>
                                <span class="b-badge b-b-custom-date" title="Inició el <?= $fechaCustomLabel ?> — fecha distinta al período">
                                    <i class="ti ti-calendar-event" style="font-size:.7rem;"></i> <?= $fechaCustomLabel ?>
                                </span>
                                <?php endif; ?>
                                <span class="b-badge <?= $bBadgeClass ?>"><?= htmlspecialchars($p->estado_pasantia) ?></span>
                            </div>
                        </div>

                        <!-- Métricas Rápidas + Badges P/A/J integrados -->
                        <div class="sw-pcard-stats">
                            <div class="sw-stat-box">
                                <span class="sw-stat-val"><?= $diasMeta ?></span>
                                <span class="sw-stat-lbl">Días Válidos</span>
                            </div>
                            <!-- Badges P/J/A en fila -->
                            <div style="display:flex;align-items:center;justify-content:center;gap:4px;flex-wrap:nowrap;">
                                <span style="display:inline-flex;align-items:center;gap:2px;background:#dcfce7;color:#15803d;border-radius:5px;padding:2px 7px;font-size:.63rem;font-weight:800;" title="Días Presente">
                                    <i class="ti ti-check" style="font-size:.65rem;"></i> P <?= $diasPres ?>
                                </span>
                                <?php if ($diasJust > 0): ?>
                                <span style="display:inline-flex;align-items:center;gap:2px;background:#dbeafe;color:#1d4ed8;border-radius:5px;padding:2px 7px;font-size:.63rem;font-weight:800;" title="Justificados (feriados)">
                                    <i class="ti ti-calendar-event" style="font-size:.65rem;"></i> J <?= $diasJust ?>
                                </span>
                                <?php endif; ?>
                                <span style="display:inline-flex;align-items:center;gap:2px;border-radius:5px;padding:2px 7px;font-size:.63rem;font-weight:800;<?= $diasAus > 0 ? 'background:#fee2e2;color:#dc2626;' : 'background:#f1f5f9;color:#94a3b8;' ?>" title="Ausentes">
                                    <i class="ti ti-x" style="font-size:.65rem;"></i> A <?= $diasAus ?>
                                </span>
                            </div>
                            <div class="sw-stat-box">
                                <span class="sw-stat-val" style="color:<?= $pctAsist >= 80 ? '#059669' : ($pctAsist >= 60 ? '#d97706' : '#dc2626') ?>;"><?= $pctAsist ?>%</span>
                                <span class="sw-stat-lbl">Cumplimiento</span>
                            </div>
                        </div>

                        <!-- Progreso Horas -->
                        <div class="sw-pcard-progress">
                            <div class="sw-prog-info">
                                <span><i class="ti ti-target" style="color:<?= $dColor ?>"></i> Progreso de Horas</span>
                                <span><?= $hAcum ?>/<?= $hMeta ?> hrs</span>
                            </div>
                            <div class="sw-prog-bar">
                                <div class="sw-prog-fill" style="width:<?= $progreso ?>%; background:<?= $progColor ?>;"></div>
                            </div>
                        </div>

                        <!-- Acciones Grid -->
                        <div class="sw-pcard-actions">
                            <a href="<?= URLROOT ?>/asistencias/almanaque/<?= (int)$p->id ?>" class="btn-p-acti btn-alm" title="Ver Almanaque Personal">
                                <i class="ti ti-calendar-stats"></i> Almanaque
                            </a>
                            <button type="button" onclick="abrirModalReporteAsist(<?= (int)$p->id ?>, '<?= addslashes($nombreCompl) ?>')" class="btn-p-acti btn-asis" title="Reporte de Asistencias por Trimestre">
                                <i class="ti ti-file-analytics"></i> Reporte Asist.
                            </button>
                            <button type="button" onclick="generarPlanillaTrimestral(<?= (int)$p->id ?>)" class="btn-p-acti btn-trim" title="Planilla Trimestral ISP (3 trimestres)">
                                <i class="ti ti-table"></i> Planilla Trim.
                            </button>
                            <button type="button" onclick="generarConstancia(<?= (int)$p->id ?>, '<?= $p->estado_pasantia ?>', <?= $progreso ?>)" class="btn-p-acti btn-carta" title="Generar Constancia PDF">
                                <i class="ti ti-file-certificate"></i> Constancia
                            </button>
                            <button type="button"
                                onclick="abrirAjustarFechas(<?= (int)$p->id ?>, '<?= addslashes($nombreCompl) ?>', '<?= $p->fecha_inicio_pasantia ?? '' ?>', '<?= $p->fecha_fin_estimada ?? '' ?>', <?= $hMeta ?>)"
                                class="btn-p-acti btn-aj" title="Ajustar fechas y horas individuales">
                                <i class="ti ti-calendar-cog"></i> Ajustar Fechas
                            </button>
                            
                            <?php if ($p->estado_usuario !== 'inactivo' && $p->estado_pasantia === 'Activo'): ?>
                            <form action="<?= URLROOT ?>/periodos/desactivarPasante/<?= (int)$p->id ?>" method="POST" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="periodo_id" value="<?= (int)$periodo->id ?>">
                                <button type="button" onclick="confirmarDesactivarIndividual(this.form, '<?= addslashes($nombreCompl) ?>')" class="btn-p-acti btn-off" style="width:100%;">
                                    <i class="ti ti-power"></i> Desvincular
                                </button>
                            </form>
                            <?php else: ?>
                            <div style="background:#f1f5f9; color:#94a3b8; display:flex; align-items:center; justify-content:center; padding:8px; border-radius:8px; font-size:0.75rem; font-weight:700; cursor:not-allowed;">
                                <i class="ti ti-lock" style="margin-right:4px;"></i> Inactivo
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

    <div style="margin-bottom:60px;"></div>

<!-- ── Modal: Reporte de Asistencia por Trimestre ─────────────────── -->
<div id="modalReporteAsist" class="sgp-modal-overlay" onclick="if(event.target===this)cerrarModalReporte()">
    <div class="sgp-modal" style="max-width:500px;">

        <!-- Header idéntico al módulo de reportes -->
        <div class="sgp-modal-header" style="padding:24px 28px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h3 style="color:white;font-weight:800;font-size:1.2rem;margin:0;display:flex;align-items:center;gap:8px;">
                    <i class="ti ti-file-analytics"></i> Reporte de Asistencias
                </h3>
                <p id="rptAsistNombre" style="color:rgba(255,255,255,0.72);font-size:0.85rem;margin:5px 0 0;"></p>
            </div>
            <button onclick="cerrarModalReporte()"
                    style="background:rgba(255,255,255,0.15);border:none;color:rgba(255,255,255,0.8);width:36px;height:36px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:background 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="sgp-modal-body" style="padding:24px 28px;overflow-y:auto;">

            <p style="font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:12px;">
                Selección rápida por trimestre
            </p>

            <!-- 3 Trimestres en fila -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:8px;">
                <label class="rpt-pill">
                    <input type="radio" name="rptTrimestre" value="1" onchange="selTrimestre(1)">
                    <i class="ti ti-calendar-event" style="font-size:0.85rem;"></i> Trimestre I
                </label>
                <label class="rpt-pill">
                    <input type="radio" name="rptTrimestre" value="2" onchange="selTrimestre(2)">
                    <i class="ti ti-calendar-event" style="font-size:0.85rem;"></i> Trimestre II
                </label>
                <label class="rpt-pill">
                    <input type="radio" name="rptTrimestre" value="3" onchange="selTrimestre(3)">
                    <i class="ti ti-calendar-event" style="font-size:0.85rem;"></i> Trimestre III
                </label>
            </div>
            <!-- Período completo full-width -->
            <div style="margin-bottom:22px;">
                <label class="rpt-pill" id="rptPillCompleto" style="width:100%;justify-content:center;">
                    <input type="radio" name="rptTrimestre" value="0" onchange="selTrimestre(0)" checked>
                    <i class="ti ti-chart-bar" style="font-size:0.85rem;"></i> Período Completo
                </label>
            </div>

            <p style="font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;">
                O define un rango personalizado
            </p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px;">
                <div>
                    <label style="display:block;font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px;">Desde</label>
                    <input type="date" id="rptDesde" class="rpt-date-input">
                </div>
                <div>
                    <label style="display:block;font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px;">Hasta</label>
                    <input type="date" id="rptHasta" class="rpt-date-input">
                </div>
            </div>

            <button onclick="generarReporteAsist()"
                    style="width:100%;padding:14px;background:linear-gradient(135deg,#172554 0%,#2563eb 100%);color:white;border:none;border-radius:14px;font-size:0.95rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity 0.2s;"
                    onmouseover="this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
                <i class="ti ti-file-download"></i> Generar PDF
            </button>
        </div>
    </div>
</div>

<!-- Formulario oculto para cerrar período y desactivar a todos -->
<form id="formCerrarPeriodo" action="<?= URLROOT ?>/periodos/cerrar" method="POST" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="periodo_id" value="<?= (int)$periodo->id ?>">
</form>

<script>
// [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
var PERIODO_ESTADO = '<?= htmlspecialchars($periodo->estado) ?>';
var PERIODO_INICIO = '<?= $periodo->fecha_inicio ?>';
var PERIODO_FIN    = '<?= $periodo->fecha_fin ?>';

/* ── Modal: Reporte de Asistencia ────────────────────────────── */
var _rptPasanteId = 0;

function abrirModalReporteAsist(pasanteId, nombrePasante) {
    _rptPasanteId = pasanteId;
    document.getElementById('rptAsistNombre').textContent = nombrePasante;
    // Resetear al período completo
    document.querySelectorAll('input[name="rptTrimestre"]').forEach(r => r.checked = false);
    document.querySelector('input[name="rptTrimestre"][value="0"]').checked = true;
    document.getElementById('rptDesde').value = PERIODO_INICIO;
    document.getElementById('rptHasta').value = PERIODO_FIN;
    document.getElementById('modalReporteAsist').classList.add('active');
}

function cerrarModalReporte() {
    document.getElementById('modalReporteAsist').classList.remove('active');
}

function selTrimestre(num) {
    const inicio = new Date(PERIODO_INICIO + 'T00:00:00');
    const fin    = new Date(PERIODO_FIN    + 'T00:00:00');
    let desde, hasta;

    if (num === 0) {
        desde = PERIODO_INICIO;
        hasta = PERIODO_FIN;
    } else {
        // Dividir el período en 3 trimestres iguales
        const totalMs = fin.getTime() - inicio.getTime();
        const trimMs  = totalMs / 3;
        const dIni    = new Date(inicio.getTime() + (num - 1) * trimMs);
        const dFin    = new Date(inicio.getTime() + num * trimMs - 86400000);
        if (dFin > fin) dFin.setTime(fin.getTime());
        desde = dIni.toISOString().slice(0, 10);
        hasta = dFin.toISOString().slice(0, 10);
    }

    document.getElementById('rptDesde').value = desde;
    document.getElementById('rptHasta').value = hasta;
}

function generarReporteAsist() {
    const desde = document.getElementById('rptDesde').value;
    const hasta = document.getElementById('rptHasta').value;
    if (!desde || !hasta) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Selecciona las fechas de inicio y fin.', showConfirmButton: false, timer: 2500 });
        return;
    }
    if (hasta < desde) {
        Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'La fecha final debe ser mayor o igual a la inicial.', showConfirmButton: false, timer: 2500 });
        return;
    }
    window.open(`<?= URLROOT ?>/periodos/reporteAsistencia/${_rptPasanteId}?desde=${desde}&hasta=${hasta}`, '_blank');
    cerrarModalReporte();
}

/* ── Constancia de Culminación ──────────────────────────────── */
function generarConstancia(id, estadoPasantia, progreso) {
    if (typeof Swal === 'undefined') return;

    if (PERIODO_ESTADO !== 'Cerrado') {
        Swal.fire({
            title: 'Período Aún Activo',
            html: '<p style="color:#64748b;font-size:0.95rem;">La constancia de culminación solo puede generarse cuando el período académico esté en estado <strong>Cerrado</strong>.<br><br>Finaliza el período primero desde la administración.</p>',
            icon: 'info',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#2563eb',
            customClass: { popup: 'sgp-swal-modal' },
            didOpen: () => { document.querySelector('.swal2-popup').style.borderRadius = '20px'; }
        });
        return;
    }

    if (progreso < 100) {
        Swal.fire({
            title: 'Horas Incompletas',
            html: `<p style="color:#64748b;font-size:0.95rem;">Este pasante solo tiene <strong>${progreso}%</strong> de sus horas completadas.<br><br>No se puede emitir la constancia de culminación hasta que haya alcanzado el <strong>100%</strong> de las horas requeridas.</p>`,
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#f59e0b',
            customClass: { popup: 'sgp-swal-modal' },
            didOpen: () => { document.querySelector('.swal2-popup').style.borderRadius = '20px'; }
        });
        return;
    }

    window.open('<?= URLROOT ?>/periodos/cartaCulminacion/' + id, '_blank');
}

/* ── Cerrar período desde la vista de detalle ───────────────── */
function confirmarCerrarDesde(id, nombre, activosCount) {
    if (typeof Swal === 'undefined') return;

    if (activosCount > 0) {
        Swal.fire({
            title: 'Acción Denegada',
            html: `<p style="color:#64748b;font-size:0.95rem;">No puedes finalizar la cohorte <strong>${nombre}</strong> porque aún existen <strong>${activosCount} pasantes en curso activo</strong>.<br><br>Debes esperar a que culminen o desvincularlos individualmente antes de cerrar el período.</p>`,
            icon: 'error',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#2563eb',
            customClass: { popup: 'sgp-swal-modal' },
            didOpen: () => { document.querySelector('.swal2-popup').style.borderRadius = '20px'; }
        });
        return;
    }

    Swal.fire({
        title: '¿Finalizar Cohorte?',
        html: `<p style="color:#64748b;font-size:0.95rem;">Vas a finalizar y aislar la cohorte <strong>${nombre}</strong>.<br><br>Se <strong>deshabilitará permanentemente el acceso</strong> de todos los pasantes vinculados ya que se asume que culminaron sus funciones.<br><br>Esta acción es irreversible y consolida los reportes.</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-lock"></i> Sí, Finalizar Cohorte',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        customClass: { popup: 'sgp-swal-modal' },
        didOpen: () => { document.querySelector('.swal2-popup').style.borderRadius = '20px'; }
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('formCerrarPeriodo').submit();
        }
    });
}

function confirmarDesactivarIndividual(form, nombrePasante) {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        title: 'Protección de Pasante',
        html: `<p style="color:#64748b;font-size:0.95rem;">¿De verdad deseas retirar el acceso al sistema a <strong>${nombrePasante}</strong> de forma prematura?<br><br>Esta acción cortará su actividad inmediatamente.</p>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: '<i class="ti ti-power"></i> Sí, Estoy Seguro / Retirar',
        cancelButtonText: 'Cancelar',
        customClass: { popup: 'sgp-swal-modal' },
        didOpen: () => { document.querySelector('.swal2-popup').style.borderRadius = '20px'; }
    }).then(result => {
        if (result.isConfirmed) form.submit();
    });
}

function generarInformePeriodo(id, estado, event) {
    if (event) event.preventDefault();
    if (typeof Swal === 'undefined') return;

    if (estado !== 'Cerrado') {
        Swal.fire({
            title: 'Informe No Disponible',
            html: '<p style="color:#64748b;font-size:0.95rem;">Aún no se puede generar el informe general consolidado debido a que <b>este período no ha culminado legalmente</b>.<br><br>Debes esperar a que la cohorte sea finalizada desde la administración.</p>',
            icon: 'info',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#2563eb',
            customClass: { popup: 'sgp-swal-modal' },
            didOpen: () => { document.querySelector('.swal2-popup').style.borderRadius = '20px'; }
        });
    } else {
        window.open('<?= URLROOT ?>/periodos/informeGeneral/' + id, '_blank');
    }
}

// ── Ajustar fechas individuales de un pasante ────────────────
let _ajPasanteId = 0;

function abrirAjustarFechas(id, nombre, fechaIni, fechaFin, horasMeta) {
    _ajPasanteId = id;

    Swal.fire({
        title: 'Ajustar fechas individuales',
        html: `<div style="text-align:left;font-size:.88rem;color:#475569;">
                   <div style="font-weight:700;color:#0f172a;margin-bottom:12px;">
                       <i class="ti ti-user" style="color:#7c3aed;"></i> ${nombre}
                   </div>

                   <!-- Fecha inicio -->
                   <label style="display:block;font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Fecha de Inicio</label>
                   <input type="date" id="aj-fi" value="${fechaIni}"
                          style="width:100%;padding:9px 12px;border:2px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.85rem;box-sizing:border-box;margin-bottom:12px;"
                          oninput="recalcAj()">

                   <!-- Fecha fin -->
                   <label style="display:block;font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Fecha de Fin Estimada</label>
                   <input type="date" id="aj-ff" value="${fechaFin}"
                          style="width:100%;padding:9px 12px;border:2px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.85rem;box-sizing:border-box;margin-bottom:8px;"
                          oninput="recalcAj()">

                   <!-- Botón calcular 1440h -->
                   <button type="button" onclick="calcularPara1440()"
                           style="width:100%;padding:8px;border:1.5px dashed #7c3aed;border-radius:8px;background:#f5f3ff;
                                  color:#7c3aed;font-weight:700;font-size:.78rem;cursor:pointer;font-family:inherit;margin-bottom:12px;">
                       <i class="ti ti-calculator"></i> Calcular fecha_fin para 1440h desde el inicio
                   </button>

                   <!-- Preview horas_meta -->
                   <div id="aj-preview" style="background:#f8fafc;border-radius:10px;padding:12px;border:1px solid #e2e8f0;">
                       <div style="font-size:.7rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;">Preview de horas_meta calculadas</div>
                       <div id="aj-horas" style="font-size:1.4rem;font-weight:900;color:#7c3aed;">${horasMeta}h</div>
                       <div id="aj-dias" style="font-size:.75rem;color:#94a3b8;margin-top:2px;"></div>
                   </div>
               </div>`,
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-device-floppy"></i> Guardar cambios',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        didOpen: () => { recalcAj(); },
        preConfirm: () => {
            const fi = document.getElementById('aj-fi').value;
            const ff = document.getElementById('aj-ff').value;
            if (!fi || !ff || ff <= fi) {
                Swal.showValidationMessage('La fecha de fin debe ser posterior a la de inicio.');
                return false;
            }
            return fetch('<?= URLROOT ?>/periodos/ajustarFechasPasante', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _CSRF_VER },
                body: JSON.stringify({
                    pasante_id:    _ajPasanteId,
                    fecha_inicio:  fi,
                    fecha_fin:     ff,
                    csrf_token:    _CSRF_VER
                })
            }).then(r => r.json()).then(res => {
                if (!res.success) Swal.showValidationMessage(res.message || 'Error al guardar.');
                return res;
            }).catch(err => Swal.showValidationMessage('Error: ' + err.message));
        }
    }).then(result => {
        if (result.isConfirmed && result.value?.success) {
            Swal.fire({
                icon: 'success',
                title: 'Fechas actualizadas',
                html: `<p style="color:#475569;">
                           Nueva meta: <strong style="color:#7c3aed;">${result.value.horas_meta}h</strong><br>
                           Inicio: ${result.value.fecha_inicio_fmt} → Fin: ${result.value.fecha_fin_fmt}
                       </p>`,
                confirmButtonColor: '#7c3aed', confirmButtonText: 'Entendido'
            }).then(() => location.reload());
        }
    });
}

// Los feriados NO se descuentan del conteo — solo son para visualización e interfaz.
// El sistema cuenta todos los días L-V como 8h independientemente de si son feriados.
function recalcAj() {
    const fi  = document.getElementById('aj-fi')?.value;
    const ff  = document.getElementById('aj-ff')?.value;
    const hEl = document.getElementById('aj-horas');
    const dEl = document.getElementById('aj-dias');
    if (!fi || !ff || ff <= fi) {
        if (hEl) { hEl.textContent = '—'; hEl.style.color = '#7c3aed'; }
        if (dEl) dEl.textContent = 'Fechas inválidas';
        return;
    }
    // Conteo exacto de días L-V (lunes a viernes)
    let diasLV = 0;
    const cur = new Date(fi + 'T12:00:00');
    const end = new Date(ff + 'T12:00:00');
    while (cur <= end) {
        const dow = cur.getDay();
        if (dow !== 0 && dow !== 6) diasLV++;
        cur.setDate(cur.getDate() + 1);
    }
    const diasCal = Math.round((new Date(ff) - new Date(fi)) / 86400000);
    const horas = Math.max(160, diasLV * 8);
    if (hEl) { hEl.textContent = horas + 'h'; hEl.style.color = horas >= 1440 ? '#059669' : (horas >= 1200 ? '#d97706' : '#7c3aed'); }
    if (dEl) dEl.textContent = `${diasLV} días L-V · ${diasCal} días calendario`;
}

function calcularPara1440() {
    const fi = document.getElementById('aj-fi')?.value;
    if (!fi) { alert('Primero ingresa la fecha de inicio.'); return; }
    // 1440h ÷ 8h = 180 días L-V exactos
    const inicio = new Date(fi + 'T12:00:00');
    let diasLV = 0, cur = new Date(inicio);
    while (diasLV < 180) {
        cur.setDate(cur.getDate() + 1);
        const dow = cur.getDay();
        if (dow !== 0 && dow !== 6) diasLV++;
    }
    document.getElementById('aj-ff').value = cur.toISOString().split('T')[0];
    recalcAj();
}

// ── Planilla Trimestral ISP desde tarjeta de pasante ─────
window.generarPlanillaTrimestral = function(pasanteId) {
    // Crea un form temporal y lo envía en nueva pestaña
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= URLROOT ?>/reportes/exportar';
    form.target = '_blank';
    form.style.display = 'none';

    const campos = {
        modulo:            'asistencias',
        formato_trimestral:'1',
        trimestre:         '0',       // 0 = todos los trimestres
        pasante_id:        pasanteId,
        csrf_token:        _CSRF_VER
    };

    Object.entries(campos).forEach(([k, v]) => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = k;
        inp.value = v;
        form.appendChild(inp);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

// ── Ajustar por institución a 1440h ──────────────────────────
function abrirAjustarInstitucion() {
    const modal = document.getElementById('modalAjustarInst');
    if (!modal) return;
    // Opciones ya renderizadas por PHP — solo resetear estado
    document.getElementById('aiInstSelect').value = '';
    document.getElementById('aiFechaInicio').value = '';
    document.getElementById('aiPreview').style.display = 'none';
    modal.style.display = 'flex';
}

function cerrarAjustarInstitucion() {
    document.getElementById('modalAjustarInst').style.display = 'none';
}

async function aiCalcularPreview() {
    const fi   = document.getElementById('aiFechaInicio').value;
    const inst = document.getElementById('aiInstSelect').value;
    const prev = document.getElementById('aiPreview');
    if (!fi || !inst) { prev.style.display = 'none'; return; }

    try {
        const r = await fetch(`<?= URLROOT ?>/periodos/calcularFechaFin?inicio=${fi}&horas=1440`);
        const raw = await r.text();
        console.log('[calcularFechaFin] status:', r.status, 'body:', raw.substring(0, 200));
        let d; try { d = JSON.parse(raw); } catch(e) { console.error('[calcularFechaFin] No es JSON:', raw); return; }
        if (d.success) {
            document.getElementById('aiFinFmt').textContent    = d.fecha_fmt;
            document.getElementById('aiDiasLV').textContent    = d.dias_lv;
            document.getElementById('aiIniFmt').textContent    = new Date(fi + 'T12:00:00').toLocaleDateString('es-VE', {day:'2-digit',month:'long',year:'numeric'});
            prev.style.display = 'block';
        }
    } catch(_) {}
}

async function ejecutarAjustarInstitucion() {
    const inst = document.getElementById('aiInstSelect').value;
    const fi   = document.getElementById('aiFechaInicio').value;
    if (!inst || !fi) {
        Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Selecciona institución y fecha de inicio.', confirmButtonColor: '#2563eb' });
        return;
    }
    const instNombre = document.getElementById('aiInstSelect').options[document.getElementById('aiInstSelect').selectedIndex].text;
    const fechaFmt   = document.getElementById('aiFinFmt').textContent || '—';

    const confirm = await Swal.fire({
        title: `Ajustar pasantes de`,
        html: `<p style="font-size:.9rem;color:#475569;margin:0 0 10px;">
                   <strong>${instNombre}</strong><br>
                   Fecha inicio: <strong>${fi}</strong> → Fecha fin: <strong>${fechaFmt}</strong><br>
                   Meta: <strong style="color:#2563eb;">1440h</strong>
               </p>
               <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px;font-size:.78rem;color:#1d4ed8;">
                   Solo se actualizarán los pasantes de esta institución en el período actual.
               </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-building-community"></i> Sí, ajustar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2563eb',
    });
    if (!confirm.isConfirmed) return;

    Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

    try {
        const r = await fetch('<?= URLROOT ?>/periodos/ajustarInstitucionA1440', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': _CSRF_VER },
            body: JSON.stringify({ periodo_id: _PERIODO_ID, institucion_id: parseInt(inst), fecha_inicio: fi, csrf_token: _CSRF_VER })
        });
        const d = await r.json();
        if (d.success) {
            cerrarAjustarInstitucion();
            const lista = (d.pasantes || []).map(n => `<li style="text-align:left;">${n}</li>`).join('');
            await Swal.fire({
                icon: 'success',
                title: `${d.actualizados} pasante(s) actualizados`,
                html: `<p style="font-size:.85rem;color:#475569;">
                           Inicio: <strong>${d.fecha_inicio}</strong> → Fin: <strong>${d.fecha_fin}</strong> | Meta: <strong>1440h</strong>
                       </p>
                       <ul style="max-height:160px;overflow-y:auto;font-size:.8rem;color:#374151;padding-left:20px;">${lista}</ul>`,
                confirmButtonColor: '#2563eb',
            });
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: d.message, confirmButtonColor: '#dc2626' });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de red', text: e.message, confirmButtonColor: '#dc2626' });
    }
}

// ── Ajustar todos los pasantes a 1440h ───────────────────────
async function ajustarTodosA1440(periodoId) {
    const result = await Swal.fire({
        title: 'Ajustar todos a 1440h',
        html: `<div style="text-align:left;font-size:.9rem;color:#475569;">
                   <p>Cada pasante tendrá su <strong>fecha_fin</strong> recalculada para acumular
                   exactamente <strong style="color:#7c3aed;">1440 horas</strong> (180 días hábiles L-V)
                   desde su fecha de inicio individual.</p>
                   <div style="background:#faf5ff;border:1px solid #ddd6fe;border-radius:10px;padding:10px 14px;margin-top:10px;font-size:.82rem;color:#6d28d9;">
                       <i class="ti ti-info-circle"></i>
                       Los feriados no laborables registrados en el sistema serán excluidos del conteo.
                   </div>
               </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-clock-bolt"></i> Sí, ajustar todos',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#7c3aed',
    });
    if (!result.isConfirmed) return;

    Swal.fire({ title: 'Calculando...', text: 'Ajustando fechas de cada pasante', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

    try {
        const r = await fetch('<?= URLROOT ?>/periodos/ajustarTodosA1440', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': _CSRF_VER },
            body: JSON.stringify({ periodo_id: periodoId, csrf_token: _CSRF_VER })
        });
        const d = await r.json();
        if (d.success) {
            const filas = (d.resultados || []).map(p =>
                `<tr><td style="padding:3px 8px;">${p.inicio}</td><td style="padding:3px 8px;color:#7c3aed;">→ ${p.fin}</td><td style="padding:3px 8px;color:#059669;font-weight:700;">1440h</td></tr>`
            ).join('');
            await Swal.fire({
                title: `${d.actualizados} pasante(s) ajustados`,
                html: `<div style="max-height:280px;overflow-y:auto;font-size:.82rem;">
                           <table style="width:100%;border-collapse:collapse;">
                               <thead><tr style="background:#f3f4f6;"><th style="padding:4px 8px;text-align:left;">Inicio</th><th style="padding:4px 8px;text-align:left;">Nueva Fin</th><th style="padding:4px 8px;text-align:left;">Meta</th></tr></thead>
                               <tbody>${filas}</tbody>
                           </table>
                       </div>`,
                icon: 'success',
                confirmButtonColor: '#7c3aed',
            });
            location.reload();
        } else {
            Swal.fire('Error', d.message || 'No se pudo ajustar.', 'error');
        }
    } catch (e) {
        Swal.fire('Error', e.message, 'error');
    }
}

// ── Reabrir período desde la vista de detalle ────────────────
const _CSRF_VER  = '<?= Session::generateCsrfToken() ?>';
const _PERIODO_ID = <?= (int)($periodo->id ?? 0) ?>;

<?php
// Recopilar instituciones únicas de los pasantes de este período
$instUnicas = [];
foreach ($pasantes as $p) {
    if (!empty($p->institucion) && !isset($instUnicas[$p->institucion])) {
        // Buscar el id de la institución en datos_pasante
        // Se expone como data-inst-id en el HTML via JS map
        $instUnicas[$p->institucion] = true;
    }
}
?>
// Mapa institución nombre → id (desde query dedicada en el controlador)
const _INST_MAP = <?php
    $instMap = [];
    foreach ($institucionesPeriodo ?? [] as $inst) {
        if (!empty($inst->nombre) && (int)$inst->id > 0) {
            $instMap[$inst->nombre] = (int)$inst->id;
        }
    }
    echo json_encode($instMap, JSON_UNESCAPED_UNICODE);
?>;

function reabrirPeriodoDesdever(periodoId, periodoNombre) {
    Swal.fire({
        title: 'Reabrir período',
        html: `<div style="text-align:left;font-size:.9rem;color:#475569;">
                   <p style="margin:0 0 12px;">¿Deseas reabrir el período <strong>${periodoNombre}</strong>?</p>
                   <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 14px;font-size:.82rem;color:#15803d;">
                       <div style="margin-bottom:5px;font-weight:700;"><i class="ti ti-info-circle"></i> Qué ocurre al reabrir:</div>
                       <ul style="margin:0;padding-left:18px;line-height:1.8;">
                           <li>El período vuelve a estado <strong>Activo</strong></li>
                           <li>Los pasantes vuelven a estado <strong>Activo</strong></li>
                           <li>Podrás editar las fechas y las horas se recalculan automáticamente</li>
                           <li>Los pasantes podrán marcar asistencia nuevamente</li>
                       </ul>
                   </div>
               </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-lock-open"></i> Sí, reabrir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        showLoaderOnConfirm: true,
        preConfirm: () => fetch('<?= URLROOT ?>/admin/reactivarPeriodo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _CSRF_VER },
            body: JSON.stringify({ periodo_id: periodoId, csrf_token: _CSRF_VER })
        }).then(r => r.json()).then(res => {
            if (!res.success) Swal.showValidationMessage(res.message);
            return res;
        }).catch(err => Swal.showValidationMessage('Error: ' + err.message)),
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (result.isConfirmed && result.value?.success) {
            Swal.fire({
                icon: 'success',
                title: 'Período reabierto',
                html: `<p style="color:#475569;">${result.value.message}<br>
                       <strong style="color:#059669;">${result.value.pasantes_reactivados} pasante(s)</strong> reactivados.<br><br>
                       <span style="font-size:.85rem;color:#64748b;">Ahora puedes editar las fechas del período para ajustar las horas meta.</span></p>`,
                confirmButtonColor: '#059669',
                confirmButtonText: 'Entendido'
            }).then(() => location.reload());
        }
    });
}
</script>

<!-- ══ Modal: Ajustar por Institución a 1440h ════════════════════════════ -->
<div id="modalAjustarInst" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9990;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:#fff;border-radius:20px;padding:30px 28px;width:100%;max-width:480px;box-shadow:0 25px 60px rgba(0,0,0,.18);margin:16px;">

        <!-- Header -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#1d4ed8);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="ti ti-building-community" style="color:white;font-size:1.25rem;"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:1.05rem;font-weight:800;color:#1e293b;">Ajustar por Institución a 1440h</h3>
                <p style="margin:2px 0 0;font-size:.76rem;color:#64748b;">Actualiza fechas de todos los pasantes de una institución</p>
            </div>
            <button onclick="cerrarAjustarInstitucion()" style="margin-left:auto;background:none;border:none;font-size:1.5rem;color:#94a3b8;cursor:pointer;line-height:1;padding:0;">×</button>
        </div>

        <!-- Institución -->
        <div style="margin-bottom:18px;">
            <label style="display:block;font-size:.82rem;font-weight:700;color:#374151;margin-bottom:7px;">
                <i class="ti ti-building" style="color:#2563eb;"></i> Institución
            </label>
            <select id="aiInstSelect" onchange="aiCalcularPreview()"
                    style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;background:#fff;outline:none;cursor:pointer;"
                    onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                <option value="">-- Selecciona institución --</option>
                <?php foreach ($institucionesPeriodo ?? [] as $instOpt):
                    if (empty($instOpt) || !isset($instOpt->id)) continue; ?>
                <option value="<?= (int)$instOpt->id ?>"><?= htmlspecialchars((string)($instOpt->nombre ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Fecha inicio -->
        <div style="margin-bottom:18px;">
            <label style="display:block;font-size:.82rem;font-weight:700;color:#374151;margin-bottom:7px;">
                <i class="ti ti-calendar" style="color:#2563eb;"></i> Fecha de inicio del grupo
            </label>
            <input type="date" id="aiFechaInicio" data-no-flatpickr
                   style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;background:#fff;outline:none;cursor:pointer;box-sizing:border-box;color:#1e293b;font-weight:500;transition:border .2s,box-shadow .2s;appearance:auto;-webkit-appearance:auto;"
                   onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,.1)'"
                   onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'"
                   onchange="aiCalcularPreview()">
            <p style="margin:6px 0 0;font-size:.72rem;color:#94a3b8;display:flex;align-items:center;gap:4px;">
                <i class="ti ti-info-circle" style="font-size:.78rem;"></i>
                La fecha fin se calculará automáticamente para 180 días L-V (1440h)
            </p>
        </div>

        <!-- Preview calculado -->
        <div id="aiPreview" style="display:none;background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #93c5fd;border-radius:12px;padding:14px 16px;margin-bottom:20px;font-size:.82rem;">
            <div style="display:flex;align-items:center;gap:6px;font-weight:800;color:#1d4ed8;margin-bottom:10px;font-size:.85rem;">
                <i class="ti ti-sparkles"></i> Proyección calculada
                <span style="margin-left:auto;font-size:.68rem;background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:10px;font-weight:700;">1440h · 180 días L-V</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <div style="background:white;border-radius:8px;padding:10px 12px;border:1px solid #e0f2fe;">
                    <div style="font-size:.62rem;color:#0369a1;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">
                        <i class="ti ti-calendar" style="font-size:.7rem;"></i> Inicio
                    </div>
                    <div style="font-weight:800;color:#1e293b;font-size:.88rem;" id="aiIniFmt">—</div>
                </div>
                <div style="background:white;border-radius:8px;padding:10px 12px;border:1px solid #d1fae5;">
                    <div style="font-size:.62rem;color:#059669;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">
                        <i class="ti ti-calendar-check" style="font-size:.7rem;"></i> Fin estimado
                    </div>
                    <div style="font-weight:800;color:#059669;font-size:.88rem;" id="aiFinFmt">—</div>
                </div>
                <div style="background:white;border-radius:8px;padding:10px 12px;grid-column:1/-1;border:1px solid #ede9fe;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <div style="font-size:.62rem;color:#7c3aed;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Días L-V contados</div>
                        <div style="font-weight:800;color:#7c3aed;font-size:.88rem;"><span id="aiDiasLV">—</span> días × 8h/día</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:.62rem;color:#64748b;font-weight:700;text-transform:uppercase;margin-bottom:2px;">Meta</div>
                        <div style="font-size:1.2rem;font-weight:900;color:#059669;">1440h</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div style="display:flex;gap:10px;">
            <button onclick="cerrarAjustarInstitucion()" style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#64748b;font-weight:600;cursor:pointer;font-size:.88rem;">Cancelar</button>
            <button onclick="ejecutarAjustarInstitucion()" style="flex:2;padding:11px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:white;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:8px;">
                <i class="ti ti-building-community"></i> Aplicar a institución
            </button>
        </div>
    </div>
</div>
