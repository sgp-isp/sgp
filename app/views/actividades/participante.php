<?php
/**
 * Vista: Pasante de Actividad Extra — Almanaque + Semanal
 * URL: GET /actividades/participante/{id}
 */
$p            = $data['participante'];
$anio         = (int)$data['anio'];
$anios        = $data['anios']       ?? [$anio];
$grillaMeses  = $data['grillaMeses']   ?? [];  // [m][d] => estado  (para desglose)
$grillaMesMeta= $data['grillaMesMeta'] ?? [];  // [m] => {nombre,dias[],stats,tiene}  (para calendario)
$stats        = $data['stats']         ?? ['P'=>0,'A'=>0,'J'=>0,'laborables'=>0];
$pct          = (float)$data['pct'];
$diasSemana   = $data['diasSemana']    ?? [];
$navSemana    = $data['navSemana']     ?? [];
$historial    = $data['historial']     ?? [];

$nombre = trim(($p->nombres ?? '') . ' ' . ($p->apellidos ?? ''));
$ini    = mb_strtoupper(mb_substr($p->nombres ?? '?', 0, 1) . mb_substr($p->apellidos ?? '?', 0, 1));
$kpiColor = $pct >= 90 ? '#16a34a' : ($pct >= 70 ? '#d97706' : '#dc2626');

$mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mesesCortos  = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$diasEs = ['Monday'=>'Lun','Tuesday'=>'Mar','Wednesday'=>'Mié','Thursday'=>'Jue','Friday'=>'Vie'];
?>

<!-- Tooltip global -->
<div id="almTooltip" style="position:fixed;z-index:9999;pointer-events:none;background:#1e293b;color:#f8fafc;
     border-radius:10px;padding:10px 14px;font-size:.8rem;line-height:1.5;box-shadow:0 8px 24px rgba(0,0,0,.3);
     opacity:0;transition:opacity .15s;max-width:200px;">
    <div id="tt-fecha" style="font-weight:700;font-size:.85rem;margin-bottom:2px;"></div>
    <div id="tt-estado" style="display:flex;align-items:center;gap:5px;">
        <div id="tt-dot" style="width:8px;height:8px;border-radius:2px;flex-shrink:0;"></div>
        <span id="tt-est"></span>
    </div>
    <div id="tt-notas" style="color:#94a3b8;font-size:.72rem;margin-top:2px;display:none;"></div>
</div>

<style>
/* ══ TOKENS ═══════════════════════════════════════════════════════ */
:root {
    --pv-navy:   #172554;
    --pv-blue:   #2563eb;
    --pv-card:   #ffffff;
    --pv-bg:     #f1f5f9;
    --pv-radius: 18px;
    --pv-shadow: 0 2px 16px rgba(15,23,42,.07);
    --pv-border: #e2e8f0;
    --c-P:       #16a34a;
    --c-J:       #2563eb;
    --c-A:       #dc2626;
    --c-sin:     #e2e8f0;
}

/* ── Cards base ──────────────────────────────────────── */
.pv-card {
    background: var(--pv-card);
    border-radius: var(--pv-radius);
    box-shadow: var(--pv-shadow);
    border: 1px solid var(--pv-border);
    padding: 22px 24px;
}
.pv-card-title {
    font-size:.82rem;font-weight:800;color:#64748b;text-transform:uppercase;
    letter-spacing:.6px;display:flex;align-items:center;gap:7px;margin-bottom:16px;
}

/* ── Banner ──────────────────────────────────────────── */
.pv-banner {
    background: linear-gradient(135deg, var(--pv-navy) 0%, #1e3a8a 55%, var(--pv-blue) 100%);
    border-radius:20px;padding:26px 32px;margin-bottom:18px;
    display:flex;align-items:center;justify-content:space-between;
    flex-wrap:wrap;gap:16px;position:relative;overflow:hidden;
}
.pv-banner::before {
    content:'';position:absolute;top:-60px;right:-40px;
    width:240px;height:240px;background:rgba(255,255,255,.05);
    border-radius:50%;pointer-events:none;
}

/* ── KPIs ─────────────────────────────────────────────── */
.pv-kpis { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:18px; }
.pv-kpi {
    background:var(--pv-card);border-radius:var(--pv-radius);
    box-shadow:var(--pv-shadow);border:1px solid var(--pv-border);
    padding:16px 18px 12px;display:flex;flex-direction:column;gap:3px;
    transition:transform .2s,box-shadow .2s;
}
.pv-kpi:hover { transform:translateY(-3px);box-shadow:0 10px 28px rgba(15,23,42,.1); }
.pv-kpi-val { font-size:1.75rem;font-weight:900;line-height:1; }
.pv-kpi-lbl { font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.4px; }

/* ── Tabs ─────────────────────────────────────────────── */
.pv-tabs { display:flex;gap:4px;background:#f8fafc;padding:5px;border-radius:12px;width:fit-content;margin-bottom:18px; }
.pv-tab  { padding:8px 20px;border-radius:9px;font-size:.83rem;font-weight:700;cursor:pointer;border:none;background:transparent;color:#64748b;transition:all .2s;display:flex;align-items:center;gap:6px; }
.pv-tab.active { background:white;color:#1e293b;box-shadow:0 2px 8px rgba(0,0,0,.07); }

/* ── Almanaque ────────────────────────────────────────── */
.alm-mm-mobile-controls { display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding:0 4px; }
.alm-mm-grid { display:flex;gap:0; }
.alm-mm-card { display:none;width:100%;flex-shrink:0;border:1px solid #f1f5f9;border-radius:14px;padding:14px;background:#f8fafc; }
.alm-mm-card.active-mobile { display:block; }
.alm-mm-days-header { display:grid;grid-template-columns:repeat(5,1fr);gap:4px;margin-bottom:8px; }
.alm-mm-days-header div { font-size:.68rem;font-weight:700;color:#94a3b8;text-align:center; }
.alm-mm-days-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:4px; }
.alm-cell {
    aspect-ratio:1;border-radius:6px;display:flex;align-items:center;justify-content:center;
    font-size:.72rem;font-weight:700;cursor:pointer;transition:transform .1s;
}
.alm-cell:hover { transform:scale(1.15);z-index:10;box-shadow:0 2px 6px rgba(0,0,0,.15); }
.alm-cell.is-empty { cursor:default; }
.alm-cell.is-empty:hover { transform:none;box-shadow:none; }
.alm-cell[data-e="fuera"]    { background:transparent;color:#cbd5e1;font-weight:600;opacity:.5; }
.alm-cell[data-e="futuro"]   { background:#ffffff;color:#cbd5e1;border:1px solid #e2e8f0; }
.alm-cell[data-e="sin_dato"] { background:var(--c-sin);color:#64748b; }
.alm-cell[data-e="P"]        { background:var(--c-P);color:white; }
.alm-cell[data-e="J"]        { background:var(--c-J);color:white; }
.alm-cell[data-e="A"]        { background:var(--c-A);color:white; }

.alm-legend { display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px;font-size:.72rem;color:#64748b; }
.alm-legend-item { display:flex;align-items:center;gap:5px;font-weight:600; }
.alm-legend-dot  { width:12px;height:12px;border-radius:3px;flex-shrink:0; }

/* ── Vista semanal ────────────────────────────────────── */
.sem-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:18px; }
.sem-day  {
    background:#f8fafc;border-radius:14px;padding:16px 12px;text-align:center;
    border:2px solid #e2e8f0;transition:all .2s;display:flex;flex-direction:column;gap:8px;
}
.sem-day.es-P { background:#ecfdf5;border-color:#10b981; }
.sem-day.es-A { background:#fef2f2;border-color:#ef4444; }
.sem-day.es-J { background:#eff6ff;border-color:#2563eb; }
.sem-day.fuera { opacity:.5; }
.sem-day-label { font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px; }
.sem-day-num   { font-size:1.35rem;font-weight:900;color:#1e293b; }
.sem-est-badge { font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px; }
.sem-btn-group { display:flex;gap:4px;justify-content:center; }
.sem-btn {
    width:34px;height:34px;border-radius:8px;border:2px solid #e2e8f0;
    background:white;font-weight:800;font-size:.78rem;color:#94a3b8;
    cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;
}
.sem-btn:hover { background:#f8fafc;border-color:#cbd5e1; }
.sem-btn.active[data-est="P"] { background:#ecfdf5;border-color:#10b981;color:#10b981; }
.sem-btn.active[data-est="A"] { background:#fef2f2;border-color:#ef4444;color:#ef4444; }
.sem-btn.active[data-est="J"] { background:#eff6ff;border-color:#2563eb;color:#2563eb; }

/* ── Historial ────────────────────────────────────────── */
.hist-table { width:100%;border-collapse:collapse;font-size:.83rem; }
.hist-table th { padding:9px 14px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #f1f5f9;white-space:nowrap; }
.hist-table td { padding:10px 14px;border-bottom:1px solid #f8fafc;vertical-align:middle; }
.hist-table tr:hover td { background:#f8fafc; }
.hist-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:800; }

/* ── Layout ───────────────────────────────────────────── */
.pv-main-grid { display:grid;grid-template-columns:380px 1fr;gap:18px;margin-bottom:18px; }
.pv-full { margin-bottom:18px; }

/* ── Desglose mensual ─────────────────────────────────── */
.pv-meses-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px; }
.pv-mes-card { background:#f8fafc;border-radius:12px;padding:10px;text-align:center;border:2px solid transparent;transition:all .2s; }
.pv-mes-card.has-data { border-color:#e2e8f0; }
.pv-mes-card:hover { transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.05); }

/* ── Breadcrumb ──────────────────────────────────────── */
.pv-back { display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.82rem;font-weight:600;margin-bottom:12px;transition:color .2s; }
.pv-back:hover { color:white; }

@media(max-width:1100px) { .pv-main-grid { grid-template-columns:1fr; } .pv-kpis { grid-template-columns:repeat(3,1fr); } }
@media(max-width:768px)  { .pv-kpis { grid-template-columns:repeat(2,1fr); } .sem-grid { grid-template-columns:repeat(2,1fr); } .pv-banner { padding:18px 20px; } }
</style>

<div class="container-fluid py-4">

<!-- ═══ BANNER ══════════════════════════════════════════════════════ -->
<div class="pv-banner">
    <div style="z-index:1;min-width:0;">
        <a href="<?= URLROOT ?>/actividades?tab=pasantias" class="pv-back">
            <i class="ti ti-arrow-left"></i> Volver a Pasantías Cortas
        </a>
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);
                        display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;
                        font-size:1.1rem;flex-shrink:0;border:2px solid rgba(255,255,255,.25);">
                <?= $ini ?>
            </div>
            <div style="min-width:0;">
                <h1 style="color:#fff;font-size:1.45rem;font-weight:900;margin:0 0 4px;"><?= htmlspecialchars($nombre) ?></h1>
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    <span style="color:rgba(255,255,255,.75);font-size:.78rem;display:flex;align-items:center;gap:5px;">
                        <i class="ti ti-id"></i> C.I. <?= htmlspecialchars($p->cedula ?? '—') ?>
                    </span>
                    <?php if (!empty($p->carrera)): ?>
                    <span style="background:rgba(255,255,255,.12);border-radius:20px;padding:2px 10px;color:rgba(255,255,255,.85);font-size:.75rem;font-weight:600;">
                        <i class="ti ti-book"></i> <?= htmlspecialchars($p->carrera) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($p->institucion_nombre)): ?>
                    <span style="background:rgba(255,255,255,.1);border-radius:20px;padding:2px 10px;color:rgba(255,255,255,.75);font-size:.75rem;">
                        <i class="ti ti-building"></i> <?= htmlspecialchars($p->institucion_nombre) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($p->actividad_nombre)): ?>
                    <span style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 10px;color:rgba(255,255,255,.65);font-size:.72rem;">
                        <i class="ti ti-certificate"></i> <?= htmlspecialchars($p->actividad_nombre) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Selector de año -->
    <div style="display:flex;align-items:center;gap:8px;z-index:1;flex-shrink:0;">
        <?php if (count($anios) > 1): ?>
        <form method="GET" style="display:flex;align-items:center;">
            <select name="anio" onchange="this.form.submit()"
                    style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
                           color:#fff;border-radius:10px;padding:8px 14px;font-size:.84rem;font-weight:700;
                           cursor:pointer;appearance:none;text-align:center;" title="Cambiar año">
                <?php foreach (array_reverse($anios) as $a): ?>
                <option value="<?= $a ?>" <?= $a === $anio ? 'selected' : '' ?> style="background:#1e3a8a;"><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
        <a href="<?= URLROOT ?>/actividades/ver/<?= $p->actividad_id ?>"
           style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:10px;
                  padding:8px 16px;color:#fff;font-size:.82rem;font-weight:600;text-decoration:none;
                  display:flex;align-items:center;gap:5px;transition:background .2s;"
           onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
            <i class="ti ti-users"></i> Ver Grupo
        </a>
    </div>
</div>

<!-- ═══ KPIs ═════════════════════════════════════════════════════════ -->
<div class="pv-kpis">
    <div class="pv-kpi" style="border-left:4px solid <?= $kpiColor ?>;">
        <div class="pv-kpi-val" style="color:<?= $kpiColor ?>;"><?= $pct ?>%</div>
        <div class="pv-kpi-lbl">Asistencia <?= $anio ?></div>
        <div style="height:5px;border-radius:10px;background:#e2e8f0;overflow:hidden;margin-top:4px;">
            <div style="height:100%;border-radius:10px;background:<?= $kpiColor ?>;width:<?= $pct ?>%;transition:width .8s;"></div>
        </div>
    </div>
    <div class="pv-kpi" style="border-left:4px solid #16a34a;">
        <div class="pv-kpi-val" style="color:#16a34a;"><?= $stats['P'] ?></div>
        <div class="pv-kpi-lbl">Presentes</div>
    </div>
    <div class="pv-kpi" style="border-left:4px solid #dc2626;">
        <div class="pv-kpi-val" style="color:#dc2626;"><?= $stats['A'] ?></div>
        <div class="pv-kpi-lbl">Ausentes</div>
    </div>
    <div class="pv-kpi" style="border-left:4px solid #2563eb;">
        <div class="pv-kpi-val" style="color:#2563eb;"><?= $stats['J'] ?></div>
        <div class="pv-kpi-lbl">Justificados</div>
    </div>
    <div class="pv-kpi" style="border-left:4px solid #64748b;">
        <div class="pv-kpi-val" style="color:#64748b;"><?= $stats['laborables'] ?></div>
        <div class="pv-kpi-lbl">Total Días</div>
    </div>
</div>

<!-- ═══ TABS ══════════════════════════════════════════════════════════ -->
<div class="pv-tabs">
    <button class="pv-tab active" onclick="pvTab('semanal',this)"><i class="ti ti-calendar-week"></i> Vista Semanal</button>
    <button class="pv-tab"        onclick="pvTab('almanaque',this)"><i class="ti ti-calendar-stats"></i> Almanaque Anual</button>
    <button class="pv-tab"        onclick="pvTab('historial',this)"><i class="ti ti-list-details"></i> Historial</button>
</div>

<!-- ═══ SECCIÓN: SEMANAL ══════════════════════════════════════════════ -->
<div id="pv-sec-semanal">
    <div class="pv-card pv-full">
        <!-- Toolbar semana -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
            <div style="font-size:.95rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
                <i class="ti ti-calendar-week" style="color:#2563eb;"></i>
                Control Semanal de Asistencia
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <a href="<?= htmlspecialchars($navSemana['ant_url'] ?? '#') ?>"
                   style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 14px;
                          font-size:.8rem;font-weight:700;color:#475569;text-decoration:none;display:flex;align-items:center;gap:4px;transition:.2s;"
                   onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    <i class="ti ti-chevron-left"></i> Ant
                </a>
                <span style="font-size:.85rem;font-weight:700;color:#1e293b;padding:0 12px;white-space:nowrap;">
                    <?= htmlspecialchars($navSemana['label'] ?? $navSemana['semLabel'] ?? '') ?>
                </span>
                <a href="<?= htmlspecialchars($navSemana['sig_url'] ?? '#') ?>"
                   style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 14px;
                          font-size:.8rem;font-weight:700;color:#475569;text-decoration:none;display:flex;align-items:center;gap:4px;transition:.2s;"
                   onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    Sig <i class="ti ti-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- Grid de días -->
        <div class="sem-grid">
            <?php
            $diaLabel = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes'];
            $badgeCfg = [
                'P'       => ['bg'=>'#dcfce7','color'=>'#15803d','label'=>'Presente'],
                'A'       => ['bg'=>'#fee2e2','color'=>'#dc2626','label'=>'Ausente'],
                'J'       => ['bg'=>'#dbeafe','color'=>'#1d4ed8','label'=>'Justificado'],
                'futuro'  => ['bg'=>'#f1f5f9','color'=>'#94a3b8','label'=>'—'],
                'fuera'   => ['bg'=>'#f1f5f9','color'=>'#94a3b8','label'=>'—'],
                'sin_dato'=> ['bg'=>'#f1f5f9','color'=>'#94a3b8','label'=>'Sin registro'],
            ];
            foreach ($diasSemana as $dia):
                $e   = $dia['estado'];
                $bc  = $badgeCfg[$e] ?? $badgeCfg['sin_dato'];
                $cls = in_array($e, ['P','A','J']) ? "es-{$e}" : ($e === 'fuera' || $e === 'futuro' ? 'fuera' : '');
                $dt  = new DateTime($dia['fecha']);
                $dow = $dt->format('l');
                $participanteId = $p->id;
                $actividadId    = $p->actividad_id;
            ?>
            <div class="sem-day <?= $cls ?>" data-fecha="<?= $dia['fecha'] ?>" id="semday-<?= $dia['fecha'] ?>">
                <div class="sem-day-label"><?= $diaLabel[$dow] ?? $dow ?></div>
                <div class="sem-day-num"><?= $dt->format('d') ?></div>
                <span class="sem-est-badge" id="badge-<?= $dia['fecha'] ?>"
                      style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;">
                    <?= $bc['label'] ?>
                </span>
                <?php if ($e !== 'fuera' && $e !== 'futuro'): ?>
                <div class="sem-btn-group">
                    <button class="sem-btn <?= $e==='P'?'active':'' ?>" data-est="P"
                            onclick="marcarSemana('<?= $dia['fecha'] ?>', 'Presente', this, <?= $participanteId ?>, <?= $actividadId ?>)"
                            title="Presente">P</button>
                    <button class="sem-btn <?= $e==='A'?'active':'' ?>" data-est="A"
                            onclick="marcarSemana('<?= $dia['fecha'] ?>', 'Ausente', this, <?= $participanteId ?>, <?= $actividadId ?>)"
                            title="Ausente">A</button>
                    <button class="sem-btn <?= $e==='J'?'active':'' ?>" data-est="J"
                            onclick="marcarSemana('<?= $dia['fecha'] ?>', 'Justificado', this, <?= $participanteId ?>, <?= $actividadId ?>)"
                            title="Justificado">J</button>
                </div>
                <?php endif; ?>
                <?php if (!empty($dia['notas'])): ?>
                <div style="font-size:.65rem;color:#64748b;font-style:italic;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                     title="<?= htmlspecialchars($dia['notas']) ?>">
                    <?= htmlspecialchars(mb_strimwidth($dia['notas'], 0, 25, '…')) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Leyenda -->
        <div class="alm-legend">
            <span style="color:#94a3b8;">Leyenda:</span>
            <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#16a34a;"></div> Presente</div>
            <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#2563eb;"></div> Justificado</div>
            <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#dc2626;"></div> Ausente</div>
            <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#e2e8f0;"></div> Sin registro</div>
        </div>
    </div>
</div>

<!-- ═══ SECCIÓN: ALMANAQUE ════════════════════════════════════════════ -->
<div id="pv-sec-almanaque" class="d-none">
    <div class="pv-main-grid">

        <!-- CALENDARIO MENSUAL (izq) -->
        <div class="pv-card">
            <div class="pv-card-title"><i class="ti ti-calendar-stats" style="color:#2563eb;"></i> Calendario <?= $anio ?></div>

            <!-- Controles mes -->
            <div class="alm-mm-mobile-controls">
                <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-weight:700;padding:5px 12px;" onclick="pvSlideMes(-1)">
                    <i class="ti ti-chevron-left"></i>
                </button>
                <span id="pv-mes-lbl" style="font-weight:800;font-size:.92rem;color:#1e3a8a;text-transform:uppercase;">Enero</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-weight:700;padding:5px 12px;" onclick="pvSlideMes(1)">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>

            <div class="alm-mm-grid" id="pv-mm-grid">
            <?php
            $estMapCal = [
                'Presente'    => ['color'=>'#16a34a','label'=>'Presente',   'e'=>'P'],
                'Ausente'     => ['color'=>'#dc2626','label'=>'Ausente',    'e'=>'A'],
                'Justificado' => ['color'=>'#2563eb','label'=>'Justificado','e'=>'J'],
            ];
            for ($m = 1; $m <= 12; $m++):
                $meta = $grillaMesMeta[$m] ?? null;
                if (!$meta) continue;
            ?>
            <div class="alm-mm-card <?= $m === (int)date('n') ? 'active-mobile' : '' ?>"
                 data-mes="<?= $m ?>" data-nombre="<?= $mesesNombres[$m] ?>">

                <div style="font-size:.8rem;font-weight:800;color:#1e3a8a;text-transform:uppercase;letter-spacing:.5px;text-align:center;margin-bottom:10px;">
                    <?= $mesesNombres[$m] ?>
                </div>

                <div class="alm-mm-days-header">
                    <div>L</div><div>M</div><div>M</div><div>J</div><div>V</div>
                </div>

                <div class="alm-mm-days-grid">
                <?php foreach ($meta['dias'] as $celda):
                    if ($celda === null) { echo '<div></div>'; continue; }
                    if ($celda['esFinde']) continue;  // saltar fin de semana
                    $est    = $celda['estado'];  // null | 'Presente' | 'Ausente' | 'Justificado'
                    $fechaS = $celda['fecha'];
                    $d      = $celda['dia'];
                    $hoy    = date('Y-m-d');
                    if ($est !== null) {
                        $mc = $estMapCal[$est];
                        $eKey = $mc['e'];
                        $clr  = $mc['color'];
                        $lbl  = $mc['label'];
                        $isEmpty = '';
                    } elseif ($fechaS > $hoy) {
                        $eKey = 'futuro'; $clr = '#f1f5f9'; $lbl = 'Futuro'; $isEmpty = ' is-empty';
                    } else {
                        $eKey = 'sin_dato'; $clr = '#e2e8f0'; $lbl = 'Sin registro'; $isEmpty = '';
                    }
                ?>
                    <div class="alm-cell<?= $isEmpty ?>"
                         data-e="<?= $eKey ?>"
                         data-f="<?= $fechaS ?>"
                         data-est="<?= $lbl ?>"
                         data-color="<?= $clr ?>"
                    ><?= $d ?></div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endfor; ?>
            </div>

            <!-- Leyenda -->
            <div class="alm-legend">
                <span style="color:#94a3b8;">Leyenda:</span>
                <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#16a34a;"></div> Presente</div>
                <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#2563eb;"></div> Justificado</div>
                <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#dc2626;"></div> Ausente</div>
                <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#e2e8f0;"></div> Sin registro</div>
            </div>
        </div>

        <!-- DESGLOSE MENSUAL (der) -->
        <div style="display:flex;flex-direction:column;gap:18px;">
            <!-- Stats visuales -->
            <div class="pv-card">
                <div class="pv-card-title"><i class="ti ti-chart-bar" style="color:#2563eb;"></i> Desglose por mes <?= $anio ?></div>
                <div class="pv-meses-grid">
                <?php
                // Usamos grillaMeses[$m][d]=estado (mapa simple generado en el controlador)
                for ($m = 1; $m <= 12; $m++):
                    $ms  = $grillaMesMeta[$m]['stats'] ?? ['P'=>0,'A'=>0,'J'=>0];
                    $tot = $ms['P'] + $ms['A'] + $ms['J'];
                    $pcm = $tot > 0 ? round(($ms['P']+$ms['J'])/$tot*100) : null;
                    $col = $pcm === null ? '#94a3b8' : ($pcm >= 90 ? '#16a34a' : ($pcm >= 70 ? '#d97706' : '#dc2626'));
                ?>
                <div class="pv-mes-card <?= $tot > 0 ? 'has-data' : '' ?>">
                    <div style="font-size:.68rem;font-weight:800;color:#64748b;margin-bottom:4px;text-transform:uppercase;"><?= $mesesCortos[$m] ?></div>
                    <?php if ($tot > 0): ?>
                    <div style="font-size:1.2rem;font-weight:900;color:<?= $col ?>;"><?= $pcm ?>%</div>
                    <div style="font-size:.62rem;color:#94a3b8;margin-top:2px;display:flex;justify-content:center;gap:3px;">
                        <span style="color:#16a34a;font-weight:700;"><?= $ms['P'] ?>P</span>
                        <span style="color:#dc2626;font-weight:700;"><?= $ms['A'] ?>A</span>
                        <?php if ($ms['J'] > 0): ?><span style="color:#2563eb;font-weight:700;"><?= $ms['J'] ?>J</span><?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div style="font-size:.8rem;color:#cbd5e1;margin-top:2px;">—</div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
                </div>
            </div>

            <!-- Barra de progreso global -->
            <div class="pv-card">
                <div class="pv-card-title"><i class="ti ti-chart-pie-2" style="color:#7c3aed;"></i> Resumen Global</div>
                <?php
                $total = $stats['laborables'];
                $barP  = $total > 0 ? round($stats['P'] / $total * 100) : 0;
                $barJ  = $total > 0 ? round($stats['J'] / $total * 100) : 0;
                $barA  = $total > 0 ? round($stats['A'] / $total * 100) : 0;
                ?>
                <div style="margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#64748b;margin-bottom:4px;font-weight:700;">
                        <span>Asistencia efectiva</span><span style="color:<?= $kpiColor ?>;"><?= $pct ?>%</span>
                    </div>
                    <div style="height:10px;border-radius:20px;background:#e2e8f0;overflow:hidden;">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= $kpiColor ?>;border-radius:20px;transition:width .8s;"></div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px;">
                    <div style="text-align:center;padding:10px;background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
                        <div style="font-size:1.2rem;font-weight:900;color:#16a34a;"><?= $stats['P'] ?></div>
                        <div style="font-size:.65rem;color:#16a34a;font-weight:700;">Presentes</div>
                    </div>
                    <div style="text-align:center;padding:10px;background:#fee2e2;border-radius:12px;border:1px solid #fecaca;">
                        <div style="font-size:1.2rem;font-weight:900;color:#dc2626;"><?= $stats['A'] ?></div>
                        <div style="font-size:.65rem;color:#dc2626;font-weight:700;">Ausentes</div>
                    </div>
                    <div style="text-align:center;padding:10px;background:#dbeafe;border-radius:12px;border:1px solid #bfdbfe;">
                        <div style="font-size:1.2rem;font-weight:900;color:#2563eb;"><?= $stats['J'] ?></div>
                        <div style="font-size:.65rem;color:#2563eb;font-weight:700;">Justificados</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ SECCIÓN: HISTORIAL ════════════════════════════════════════════ -->
<div id="pv-sec-historial" class="d-none">
    <div class="pv-card pv-full">
        <div class="pv-card-title"><i class="ti ti-list-details" style="color:#1e3a8a;"></i>
            Historial de Asistencias
            <span style="background:#f1f5f9;color:#475569;font-size:.65rem;padding:2px 8px;border-radius:20px;font-weight:700;text-transform:none;letter-spacing:0;">
                <?= count($historial) ?> registros
            </span>
        </div>

        <?php if (empty($historial)): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="ti ti-ghost" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;"></i>
            <p style="font-weight:600;margin:0;">Sin registros de asistencia aún</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="hist-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Día</th>
                    <th>Estado</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $mesesEs = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
            $diasEsFull = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes'];
            $badgeCfg = [
                'Presente'   => ['bg'=>'#dcfce7','color'=>'#15803d','ico'=>'ti-check'],
                'Ausente'    => ['bg'=>'#fee2e2','color'=>'#dc2626','ico'=>'ti-x'],
                'Justificado'=> ['bg'=>'#dbeafe','color'=>'#1d4ed8','ico'=>'ti-file-check'],
            ];
            foreach ($historial as $h):
                $fecha    = $h->fecha ?? '';
                $est      = $h->estado ?? '—';
                $bc       = $badgeCfg[$est] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','ico'=>'ti-minus'];
                $mes      = substr($fecha, 5, 2);
                $diaEn    = date('l', strtotime($fecha));
                $diaEs    = $diasEsFull[$diaEn] ?? $diaEn;
                $fechaFmt = date('d', strtotime($fecha)) . ' ' . ($mesesEs[$mes] ?? '') . ' ' . date('Y', strtotime($fecha));
            ?>
            <tr>
                <td style="font-weight:700;color:#1e293b;white-space:nowrap;"><?= htmlspecialchars($fechaFmt) ?></td>
                <td style="color:#64748b;font-size:.8rem;"><?= $diaEs ?></td>
                <td>
                    <span class="hist-badge" style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;">
                        <i class="ti <?= $bc['ico'] ?>" style="font-size:.72rem;"></i>
                        <?= htmlspecialchars($est) ?>
                    </span>
                </td>
                <td style="color:#64748b;font-size:.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                    title="<?= htmlspecialchars($h->notas ?? '') ?>">
                    <?= !empty($h->notas) ? htmlspecialchars($h->notas) : '<span style="color:#cbd5e1;">—</span>' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</div><!-- /container-fluid -->

<script>
(function(){
    const URLROOT  = '<?= URLROOT ?>';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // ── Tabs ─────────────────────────────────────────────────
    window.pvTab = function(tab, btn) {
        document.querySelectorAll('.pv-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        ['semanal','almanaque','historial'].forEach(s => {
            document.getElementById('pv-sec-' + s)?.classList.toggle('d-none', s !== tab);
        });
    };

    // ── Slider mes almanaque ─────────────────────────────────
    let pvCurrentMonth = <?= (int)date('n') ?>;
    window.pvSlideMes = function(delta) {
        pvCurrentMonth += delta;
        if (pvCurrentMonth < 1) pvCurrentMonth = 12;
        if (pvCurrentMonth > 12) pvCurrentMonth = 1;
        document.querySelectorAll('.alm-mm-card').forEach(card => {
            const active = parseInt(card.dataset.mes) === pvCurrentMonth;
            card.classList.toggle('active-mobile', active);
            if (active) document.getElementById('pv-mes-lbl').textContent = card.dataset.nombre;
        });
    };
    setTimeout(() => pvSlideMes(0), 50);

    // ── Tooltip almanaque ────────────────────────────────────
    const tooltip = document.getElementById('almTooltip');
    const ttFecha = document.getElementById('tt-fecha');
    const ttEst   = document.getElementById('tt-est');
    const ttDot   = document.getElementById('tt-dot');
    const ttNotas = document.getElementById('tt-notas');
    const dS = ['','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
    const mS = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    function fmtF(iso) {
        const [y,m,d] = iso.split('-').map(Number);
        const dt = new Date(y,m-1,d);
        const dow = dt.getDay()||7;
        return dS[dow] + ' ' + d + ' ' + mS[m] + ' ' + y;
    }
    document.querySelectorAll('.alm-cell[data-f]').forEach(cell => {
        cell.addEventListener('mouseenter', e => {
            ttFecha.textContent = fmtF(cell.dataset.f);
            ttEst.textContent   = cell.dataset.est;
            ttDot.style.background = cell.dataset.color;
            ttNotas.style.display = 'none';
            tooltip.style.opacity = '1';
            posT(e);
        });
        cell.addEventListener('mousemove', e => posT(e));
        cell.addEventListener('mouseleave', () => tooltip.style.opacity = '0');
    });
    function posT(e) {
        const x = e.clientX+14, y = e.clientY-10;
        const w = tooltip.offsetWidth, vw = window.innerWidth;
        tooltip.style.left = (x+w > vw ? x-w-28 : x) + 'px';
        tooltip.style.top  = y + 'px';
    }

    // ── Marcar asistencia semanal ────────────────────────────
    const estLabels = { 'Presente':'Presente','Ausente':'Ausente','Justificado':'Justificado' };
    const estBadge  = {
        'Presente':   {bg:'#dcfce7',color:'#15803d'},
        'Ausente':    {bg:'#fee2e2',color:'#dc2626'},
        'Justificado':{bg:'#dbeafe',color:'#1d4ed8'},
    };
    const dayCls = { 'Presente':'es-P','Ausente':'es-A','Justificado':'es-J' };

    window.marcarSemana = function(fecha, estado, btnClicked, participanteId, actividadId) {
        const estKey = { 'Presente':'P','Ausente':'A','Justificado':'J' }[estado];

        // Optimistic UI
        const dayEl  = document.getElementById('semday-' + fecha);
        const badge  = document.getElementById('badge-' + fecha);
        const btns   = dayEl?.querySelectorAll('.sem-btn');
        if (btns) btns.forEach(b => b.classList.remove('active'));
        btnClicked.classList.add('active');
        dayEl?.classList.remove('es-P','es-A','es-J');
        if (estKey && dayCls[estado]) dayEl?.classList.add(dayCls[estado]);
        if (badge) {
            const bc = estBadge[estado];
            badge.style.background = bc.bg;
            badge.style.color      = bc.color;
            badge.textContent      = estado;
        }

        fetch(URLROOT + '/actividades/registrarAsistencia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ actividad_id: actividadId, participante_id: participanteId, fecha, estado })
        })
        .then(r => r.json())
        .then(r => {
            if (!r.success && typeof NotificationService !== 'undefined') NotificationService.error(r.message || 'Error');
        })
        .catch(() => {
            if (typeof NotificationService !== 'undefined') NotificationService.error('Error de red');
        });
    };

})();
</script>
