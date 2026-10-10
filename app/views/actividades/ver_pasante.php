<?php
/**
 * Vista: Pasante Corto — Almanaque + Historial de Asistencia
 * URL: GET /actividades/verPasante/{userId}
 */
$p            = $data['pasante'];
$anio         = (int)$data['anio'];
$anios        = $data['anios']        ?? [$anio];
$grillaMesMeta= $data['grillaMesMeta'] ?? [];
$stats        = $data['stats']         ?? ['P'=>0,'A'=>0,'J'=>0,'laborables'=>0];
$pct          = (float)$data['pct'];
$diasSemana   = $data['diasSemana']    ?? [];
$navSemana    = $data['navSemana']     ?? [];
$historial    = $data['historial']     ?? [];
$evaluaciones = $data['evaluaciones']  ?? [];
$ausentesMes  = (int)($data['ausentesMes'] ?? 0);

$nombre   = trim(($p->nombres ?? '') . ' ' . ($p->apellidos ?? ''));
$ini      = mb_strtoupper(mb_substr($p->nombres ?? '?', 0, 1) . mb_substr($p->apellidos ?? '?', 0, 1));
$kpiColor = $pct >= 90 ? '#16a34a' : ($pct >= 70 ? '#d97706' : '#dc2626');

$mesesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasEsLabel  = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles','Thursday'=>'Jueves','Friday'=>'Viernes'];

// Rango de meses del período (solo mostrar los meses que corresponden a la pasantía corta)
$mesInicio  = $p->fecha_inicio_pasantia ? (int)date('n', strtotime($p->fecha_inicio_pasantia)) : 1;
$anioInicio = $p->fecha_inicio_pasantia ? (int)date('Y', strtotime($p->fecha_inicio_pasantia)) : $anio;
$mesFin     = $p->fecha_fin_estimada    ? (int)date('n', strtotime($p->fecha_fin_estimada))    : 12;
$anioFin    = $p->fecha_fin_estimada    ? (int)date('Y', strtotime($p->fecha_fin_estimada))    : $anio;
// Si el período cruza año, extender hasta 12 o contraer hasta 1
if ($anioInicio < $anio) $mesInicio = 1;
if ($anioFin   > $anio) $mesFin    = 12;

$userId    = (int)$p->id;
$hMeta     = max(1,(int)($p->horas_meta ?? 480));
$hAcum     = (int)($p->horas_acumuladas ?? 0);
$progPct   = min(100, round($hAcum / $hMeta * 100));
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
</div>

<style>
:root {
    --pv-navy:#172554; --pv-blue:#2563eb;
    --pv-card:#ffffff; --pv-bg:#f1f5f9;
    --pv-radius:18px; --pv-shadow:0 2px 16px rgba(15,23,42,.07);
    --pv-border:#e2e8f0;
    --c-P:#16a34a; --c-J:#2563eb; --c-A:#dc2626; --c-sin:#e2e8f0;
}
.pv-card { background:var(--pv-card);border-radius:var(--pv-radius);box-shadow:var(--pv-shadow);border:1px solid var(--pv-border);padding:22px 24px; }
.pv-card-title { font-size:.82rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.6px;display:flex;align-items:center;gap:7px;margin-bottom:16px; }
.pv-banner { background:linear-gradient(135deg,var(--pv-navy) 0%,#1e3a8a 55%,var(--pv-blue) 100%);border-radius:20px;padding:26px 32px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;overflow:hidden; }
.pv-banner::before { content:'';position:absolute;top:-60px;right:-40px;width:240px;height:240px;background:rgba(255,255,255,.05);border-radius:50%;pointer-events:none; }
.pv-kpis { display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:18px; }
.pv-kpi { background:var(--pv-card);border-radius:var(--pv-radius);box-shadow:var(--pv-shadow);border:1px solid var(--pv-border);padding:16px 18px 12px;display:flex;flex-direction:column;gap:3px;transition:transform .2s,box-shadow .2s; }
.pv-kpi:hover { transform:translateY(-3px);box-shadow:0 10px 28px rgba(15,23,42,.1); }
.pv-kpi-val { font-size:1.75rem;font-weight:900;line-height:1; }
.pv-kpi-lbl { font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.4px; }
.pv-tabs { display:flex;gap:4px;background:#f8fafc;padding:5px;border-radius:12px;width:fit-content;margin-bottom:18px; }
.pv-tab { padding:8px 20px;border-radius:9px;font-size:.83rem;font-weight:700;cursor:pointer;border:none;background:transparent;color:#64748b;transition:all .2s;display:flex;align-items:center;gap:6px; }
.pv-tab.active { background:white;color:#1e293b;box-shadow:0 2px 8px rgba(0,0,0,.07); }
.alm-mm-mobile-controls { display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding:0 4px; }
.alm-mm-grid { display:flex;gap:0; }
.alm-mm-card { display:none;width:100%;flex-shrink:0;border:1px solid #f1f5f9;border-radius:14px;padding:14px;background:#f8fafc; }
.alm-mm-card.active-mobile { display:block; }
.alm-mm-days-header { display:grid;grid-template-columns:repeat(5,1fr);gap:4px;margin-bottom:8px; }
.alm-mm-days-header div { font-size:.68rem;font-weight:700;color:#94a3b8;text-align:center; }
.alm-mm-days-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:4px; }
.alm-cell { aspect-ratio:1;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;cursor:pointer;transition:transform .1s; }
.alm-cell:hover { transform:scale(1.15);z-index:10;box-shadow:0 2px 6px rgba(0,0,0,.15); }
.alm-cell.is-empty { cursor:default; }
.alm-cell.is-empty:hover { transform:none;box-shadow:none; }
.alm-cell[data-e="futuro"]   { background:#ffffff;color:#cbd5e1;border:1px solid #e2e8f0; }
.alm-cell[data-e="sin_dato"] { background:var(--c-sin);color:#64748b; }
.alm-cell[data-e="P"]        { background:var(--c-P);color:white; }
.alm-cell[data-e="J"]        { background:var(--c-J);color:white; }
.alm-cell[data-e="A"]        { background:var(--c-A);color:white; }
.alm-legend { display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px;font-size:.72rem;color:#64748b; }
.alm-legend-item { display:flex;align-items:center;gap:5px;font-weight:600; }
.alm-legend-dot  { width:12px;height:12px;border-radius:3px;flex-shrink:0; }
.sem-grid { display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:18px; }
.sem-day { background:#f8fafc;border-radius:14px;padding:16px 12px;text-align:center;border:2px solid #e2e8f0;transition:all .2s;display:flex;flex-direction:column;gap:8px; }
.sem-day.es-P { background:#ecfdf5;border-color:#10b981; }
.sem-day.es-A { background:#fef2f2;border-color:#ef4444; }
.sem-day.es-J { background:#eff6ff;border-color:#2563eb; }
.sem-day.fuera { opacity:.5; }
.sem-day-label { font-size:.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px; }
.sem-day-num   { font-size:1.35rem;font-weight:900;color:#1e293b; }
.sem-est-badge { font-size:.68rem;font-weight:800;padding:3px 10px;border-radius:20px; }
.sem-btn-group { display:flex;gap:4px;justify-content:center; }
.sem-btn { width:34px;height:34px;border-radius:8px;border:2px solid #e2e8f0;background:white;font-weight:800;font-size:.78rem;color:#94a3b8;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center; }
.sem-btn:hover { background:#f8fafc;border-color:#cbd5e1; }
.sem-btn.active[data-est="P"] { background:#ecfdf5;border-color:#10b981;color:#10b981; }
.sem-btn.active[data-est="A"] { background:#fef2f2;border-color:#ef4444;color:#ef4444; }
.sem-btn.active[data-est="J"] { background:#eff6ff;border-color:#2563eb;color:#2563eb; }
.hist-table { width:100%;border-collapse:collapse;font-size:.83rem; }
.hist-table th { padding:9px 14px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #f1f5f9;white-space:nowrap; }
.hist-table td { padding:10px 14px;border-bottom:1px solid #f8fafc;vertical-align:middle; }
.hist-table tr:hover td { background:#f8fafc; }
.hist-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:800; }
.pv-back { display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,.7);text-decoration:none;font-size:.82rem;font-weight:600;margin-bottom:12px;transition:color .2s; }
.pv-back:hover { color:white; }
.pv-main-grid { display:grid;grid-template-columns:380px 1fr;gap:18px;margin-bottom:18px; }
.pv-full { margin-bottom:18px; }

@media(max-width:1100px) { .pv-main-grid{grid-template-columns:1fr;} .pv-kpis{grid-template-columns:repeat(3,1fr);} }
@media(max-width:768px)  { .pv-kpis{grid-template-columns:repeat(2,1fr);} .sem-grid{grid-template-columns:repeat(2,1fr);} .pv-banner{padding:18px 20px;} }
</style>

<div class="container-fluid py-4">

<!-- BANNER -->
<div class="pv-banner">
    <div style="z-index:1;min-width:0;">
        <a href="<?= URLROOT ?>/actividades/pasantias" class="pv-back">
            <i class="ti ti-arrow-left"></i> Volver a Pasantías Cortas
        </a>
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div style="width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:1.1rem;flex-shrink:0;border:2px solid rgba(255,255,255,.25);">
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
                    <?php if (!empty($p->departamento_nombre)): ?>
                    <span style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 10px;color:rgba(255,255,255,.65);font-size:.72rem;">
                        <i class="ti ti-door"></i> <?= htmlspecialchars($p->departamento_nombre) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Selector año -->
    <div style="display:flex;align-items:center;gap:8px;z-index:1;flex-shrink:0;">
        <?php if (count($anios) > 1): ?>
        <form method="GET" style="display:flex;align-items:center;">
            <select name="anio" onchange="this.form.submit()"
                    style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:10px;padding:8px 14px;font-size:.84rem;font-weight:700;cursor:pointer;appearance:none;text-align:center;" title="Cambiar año">
                <?php foreach (array_reverse($anios) as $a): ?>
                <option value="<?= $a ?>" <?= $a === $anio ? 'selected' : '' ?> style="background:#1e3a8a;"><?= $a ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
        <!-- Progreso horas -->
        <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:8px 16px;color:#fff;font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:8px;">
            <i class="ti ti-clock"></i>
            <?= $hAcum ?>/<?= $hMeta ?>h &nbsp;
            <span style="background:rgba(255,255,255,.2);border-radius:20px;padding:1px 8px;font-size:.75rem;"><?= $progPct ?>%</span>
        </div>
    </div>
</div>

<!-- KPIs -->
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
        <div class="pv-kpi-lbl">Días registrados</div>
    </div>
</div>

<!-- TABS -->
<div class="pv-tabs" style="flex-wrap:wrap;">
    <button class="pv-tab active" onclick="pvTab('semanal',this)"><i class="ti ti-calendar-week"></i> Vista Semanal</button>
    <button class="pv-tab"        onclick="pvTab('almanaque',this)"><i class="ti ti-calendar-stats"></i> Calendario del Período</button>
    <button class="pv-tab"        onclick="pvTab('historial',this)"><i class="ti ti-list-details"></i> Historial</button>
    <button class="pv-tab"        onclick="pvTab('evaluaciones',this)">
        <i class="ti ti-clipboard-check"></i> Evaluaciones
        <?php if(count($evaluaciones) > 0): ?>
        <span style="background:#7c3aed;color:white;border-radius:20px;padding:1px 7px;font-size:.65rem;margin-left:2px;"><?= count($evaluaciones) ?></span>
        <?php endif; ?>
    </button>
    <a href="<?= URLROOT ?>/actividades/pdfPasanteCorto/<?= $userId ?>" target="_blank"
       style="display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:9px;
              background:linear-gradient(135deg,#dc2626,#f87171);color:white;
              font-size:.83rem;font-weight:700;text-decoration:none;border:none;
              box-shadow:0 2px 8px rgba(220,38,38,.3);transition:opacity .2s;margin-left:auto;"
       onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
        <i class="ti ti-file-type-pdf"></i> Descargar PDF
    </a>
</div>

<!-- SECCIÓN: SEMANAL -->
<div id="pv-sec-semanal">
    <div class="pv-card pv-full">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
            <div style="font-size:.95rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
                <i class="ti ti-calendar-week" style="color:#2563eb;"></i> Control Semanal de Asistencia
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <?php $antOff = ($navSemana['ant_url'] ?? '#') === '#'; ?>
                <a href="<?= htmlspecialchars($navSemana['ant_url'] ?? '#') ?>" class="pjax-link"
                   style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 14px;font-size:.8rem;font-weight:700;color:#475569;text-decoration:none;display:flex;align-items:center;gap:4px;<?= $antOff ? 'opacity:.3;pointer-events:none;' : '' ?>"
                   onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    <i class="ti ti-chevron-left"></i> Ant
                </a>
                <span style="font-size:.85rem;font-weight:700;color:#1e293b;padding:0 12px;white-space:nowrap;">
                    <?= htmlspecialchars($navSemana['semLabel'] ?? '') ?>
                </span>
                <?php $sigOff = ($navSemana['sig_url'] ?? '#') === '#'; ?>
                <a href="<?= htmlspecialchars($navSemana['sig_url'] ?? '#') ?>" class="pjax-link"
                   style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 14px;font-size:.8rem;font-weight:700;color:#475569;text-decoration:none;display:flex;align-items:center;gap:4px;<?= $sigOff ? 'opacity:.3;pointer-events:none;' : '' ?>"
                   onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    Sig <i class="ti ti-chevron-right"></i>
                </a>
            </div>
        </div>

        <div class="sem-grid">
        <?php
        $badgeCfg = [
            'Presente'   => ['bg'=>'#dcfce7','color'=>'#15803d','label'=>'Presente'],
            'Ausente'    => ['bg'=>'#fee2e2','color'=>'#dc2626','label'=>'Ausente'],
            'Justificado'=> ['bg'=>'#dbeafe','color'=>'#1d4ed8','label'=>'Justificado'],
            'P'          => ['bg'=>'#dcfce7','color'=>'#15803d','label'=>'Presente'],
            'A'          => ['bg'=>'#fee2e2','color'=>'#dc2626','label'=>'Ausente'],
            'J'          => ['bg'=>'#dbeafe','color'=>'#1d4ed8','label'=>'Justificado'],
            'sin_dato'   => ['bg'=>'#f1f5f9','color'=>'#94a3b8','label'=>'Sin registro'],
            'fuera'      => ['bg'=>'#f1f5f9','color'=>'#94a3b8','label'=>'—'],
        ];
        foreach ($diasSemana as $dia):
            $e   = $dia['estado'];
            $bc  = $badgeCfg[$e] ?? $badgeCfg['sin_dato'];
            $eL  = ['Presente'=>'P','Ausente'=>'A','Justificado'=>'J'][$e] ?? $e;
            $cls = in_array($eL, ['P','A','J']) ? "es-{$eL}" : ($e === 'fuera' ? 'fuera' : '');
            $dt  = new DateTime($dia['fecha']);
            $dow = $dt->format('l');
        ?>
        <div class="sem-day <?= $cls ?>" data-fecha="<?= $dia['fecha'] ?>" id="semday-<?= $dia['fecha'] ?>">
            <div class="sem-day-label"><?= $diasEsLabel[$dow] ?? $dow ?></div>
            <div class="sem-day-num"><?= $dt->format('d') ?></div>
            <span class="sem-est-badge" id="badge-<?= $dia['fecha'] ?>"
                  style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;">
                <?= $bc['label'] ?>
            </span>
            <?php if ($e !== 'fuera'): ?>
            <div class="sem-btn-group">
                <button class="sem-btn <?= $eL==='P'?'active':'' ?>" data-est="P"
                        onclick="marcarCorto('<?= $dia['fecha'] ?>', 'Presente', this)"
                        title="Presente">P</button>
                <button class="sem-btn <?= $eL==='A'?'active':'' ?>" data-est="A"
                        onclick="marcarCorto('<?= $dia['fecha'] ?>', 'Ausente', this)"
                        title="Ausente">A</button>
                <button class="sem-btn <?= $eL==='J'?'active':'' ?>" data-est="J"
                        onclick="marcarCorto('<?= $dia['fecha'] ?>', 'Justificado', this)"
                        title="Justificado">J</button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>

        <div class="alm-legend">
            <span style="color:#94a3b8;">Leyenda:</span>
            <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#16a34a;"></div> Presente</div>
            <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#2563eb;"></div> Justificado</div>
            <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#dc2626;"></div> Ausente</div>
            <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#e2e8f0;"></div> Sin registro</div>
        </div>
    </div>
</div>

<!-- SECCIÓN: ALMANAQUE -->
<div id="pv-sec-almanaque" class="d-none">
    <div class="pv-main-grid">
        <div class="pv-card">
            <div class="pv-card-title"><i class="ti ti-calendar-stats" style="color:#2563eb;"></i> Período <?= $mesesNombres[$mesInicio] ?> – <?= $mesesNombres[$mesFin] ?> <?= $anio ?></div>

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
                'Presente'    => ['color'=>'#16a34a','e'=>'P'],
                'Ausente'     => ['color'=>'#dc2626','e'=>'A'],
                'Justificado' => ['color'=>'#2563eb','e'=>'J'],
            ];
            for ($m = $mesInicio; $m <= $mesFin; $m++):
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
                    if ($celda['esFinde']) continue;
                    $est    = $celda['estado'];
                    $fechaS = $celda['fecha'];
                    $d      = $celda['dia'];
                    $hoy    = date('Y-m-d');
                    if ($est !== null) {
                        $mc = $estMapCal[$est];
                        $eKey = $mc['e'];
                        $isEmpty = '';
                    } elseif ($fechaS > $hoy) {
                        $eKey = 'futuro'; $isEmpty = ' is-empty';
                    } else {
                        $eKey = 'sin_dato'; $isEmpty = '';
                    }
                ?>
                    <div class="alm-cell<?= $isEmpty ?>" data-e="<?= $eKey ?>" data-f="<?= $fechaS ?>"><?= $d ?></div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endfor; ?>
            </div>

            <div class="alm-legend">
                <span style="color:#94a3b8;">Leyenda:</span>
                <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#16a34a;"></div> Presente</div>
                <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#2563eb;"></div> Justificado</div>
                <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#dc2626;"></div> Ausente</div>
                <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#e2e8f0;"></div> Sin registro</div>
            </div>
        </div>

        <!-- Info del pasante -->
        <div style="display:flex;flex-direction:column;gap:18px;">
            <div class="pv-card">
                <div class="pv-card-title"><i class="ti ti-user" style="color:#2563eb;"></i> Información del Pasante</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.85rem;">
                    <div>
                        <div style="color:#94a3b8;font-size:.72rem;font-weight:700;text-transform:uppercase;margin-bottom:3px;">Institución</div>
                        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($p->institucion_nombre ?? '—') ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.72rem;font-weight:700;text-transform:uppercase;margin-bottom:3px;">Departamento</div>
                        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($p->departamento_nombre ?? '—') ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.72rem;font-weight:700;text-transform:uppercase;margin-bottom:3px;">Tutor</div>
                        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars(trim($p->tutor_nombre ?? '—')) ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.72rem;font-weight:700;text-transform:uppercase;margin-bottom:3px;">Período</div>
                        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($p->periodo_nombre ?? '—') ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.72rem;font-weight:700;text-transform:uppercase;margin-bottom:3px;">Inicio</div>
                        <div style="font-weight:600;color:#1e293b;"><?= $p->fecha_inicio_pasantia ? date('d/m/Y', strtotime($p->fecha_inicio_pasantia)) : '—' ?></div>
                    </div>
                    <div>
                        <div style="color:#94a3b8;font-size:.72rem;font-weight:700;text-transform:uppercase;margin-bottom:3px;">Fin estimado</div>
                        <div style="font-weight:600;color:#1e293b;"><?= $p->fecha_fin_estimada ? date('d/m/Y', strtotime($p->fecha_fin_estimada)) : '—' ?></div>
                    </div>
                </div>
                <!-- Barra progreso horas -->
                <div style="margin-top:16px;">
                    <div style="display:flex;justify-content:space-between;font-size:.75rem;color:#64748b;font-weight:600;margin-bottom:6px;">
                        <span>Progreso de horas</span>
                        <span><?= $hAcum ?>/<?= $hMeta ?>h (<?= $progPct ?>%)</span>
                    </div>
                    <div style="height:8px;background:#f1f5f9;border-radius:10px;overflow:hidden;">
                        <div style="height:100%;border-radius:10px;width:<?= $progPct ?>%;background:linear-gradient(90deg,#2563eb,#10b981);transition:width 1s;"></div>
                    </div>
                </div>
            </div>

            <!-- Stats por mes -->
            <div class="pv-card">
                <div class="pv-card-title"><i class="ti ti-chart-bar" style="color:#2563eb;"></i> Resumen mensual del período</div>
                <div style="display:grid;grid-template-columns:repeat(<?= min(4, $mesFin - $mesInicio + 1) ?>,1fr);gap:8px;">
                <?php for ($m = $mesInicio; $m <= $mesFin; $m++):
                    $meta = $grillaMesMeta[$m] ?? null;
                    $ms   = $meta['stats'] ?? ['P'=>0,'A'=>0,'J'=>0];
                    $total= $ms['P'] + $ms['A'] + $ms['J'];
                    $mesNom = substr($mesesNombres[$m], 0, 3);
                    $hasData = $total > 0;
                ?>
                <div style="background:<?= $hasData ? '#f8fafc' : 'transparent' ?>;border:2px solid <?= $hasData ? '#e2e8f0' : 'transparent' ?>;border-radius:10px;padding:8px;text-align:center;font-size:.72rem;">
                    <div style="font-weight:800;color:#64748b;text-transform:uppercase;"><?= $mesNom ?></div>
                    <?php if ($hasData): ?>
                    <div style="color:#16a34a;font-weight:700;"><?= $ms['P'] ?>P</div>
                    <div style="color:#dc2626;font-size:.68rem;"><?= $ms['A'] ?>A <?= $ms['J'] ?>J</div>
                    <?php else: ?>
                    <div style="color:#cbd5e1;">—</div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECCIÓN: HISTORIAL -->
<div id="pv-sec-historial" class="d-none">
    <div class="pv-card pv-full">
        <div class="pv-card-title"><i class="ti ti-list-details" style="color:#2563eb;"></i> Historial de Asistencia (últimos 100 registros)</div>
        <?php if (empty($historial)): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.4;"></i>
            Sin registros de asistencia aún.
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="hist-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estado</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($historial as $h):
                $eBadge = match($h->estado ?? '') {
                    'Presente'    => ['bg'=>'#dcfce7','color'=>'#15803d','icon'=>'ti-check'],
                    'Ausente'     => ['bg'=>'#fee2e2','color'=>'#dc2626','icon'=>'ti-x'],
                    'Justificado' => ['bg'=>'#dbeafe','color'=>'#1d4ed8','icon'=>'ti-file-description'],
                    default       => ['bg'=>'#f1f5f9','color'=>'#64748b','icon'=>'ti-minus'],
                };
            ?>
            <tr>
                <td style="font-weight:700;color:#1e293b;"><?= date('d/m/Y', strtotime($h->fecha)) ?></td>
                <td style="color:#64748b;"><?= $h->hora_registro ? substr($h->hora_registro, 0, 5) : '—' ?></td>
                <td>
                    <span class="hist-badge" style="background:<?= $eBadge['bg'] ?>;color:<?= $eBadge['color'] ?>;">
                        <i class="ti <?= $eBadge['icon'] ?>"></i> <?= htmlspecialchars($h->estado ?? '—') ?>
                    </span>
                </td>
                <td style="color:#64748b;font-size:.8rem;"><?= htmlspecialchars($h->motivo_justificacion ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- SECCIÓN: EVALUACIONES -->
<div id="pv-sec-evaluaciones" class="d-none">
    <div class="pv-card pv-full">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
            <div class="pv-card-title" style="margin:0;">
                <i class="ti ti-clipboard-check" style="color:#7c3aed;"></i> Evaluaciones ISP
            </div>
            <button onclick="document.getElementById('overlayEvalCorta').classList.add('open')"
                    style="display:inline-flex;align-items:center;gap:6px;padding:9px 18px;
                           border-radius:10px;border:none;cursor:pointer;font-size:.83rem;
                           font-weight:700;font-family:inherit;
                           background:linear-gradient(135deg,#5b21b6,#7c3aed);
                           color:white;box-shadow:0 3px 10px rgba(124,58,237,.3);transition:all .2s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <i class="ti ti-plus"></i> Registrar Evaluación
            </button>
        </div>

        <?php if(empty($evaluaciones)): ?>
        <div style="display:flex;flex-direction:column;align-items:center;padding:40px 16px;
                    border:2px dashed #e2e8f0;border-radius:14px;text-align:center;gap:10px;">
            <div style="width:60px;height:60px;border-radius:50%;background:#f5f3ff;
                        display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:#7c3aed;">
                <i class="ti ti-clipboard-x"></i>
            </div>
            <div style="font-weight:700;color:#334155;">Sin evaluaciones registradas</div>
            <div style="font-size:.78rem;color:#94a3b8;">Registra la primera evaluación ISP para este pasante.</div>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.84rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:10px 14px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Fecha</th>
                    <th style="text-align:left;padding:10px 12px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Lapso / Fase</th>
                    <th style="text-align:center;padding:10px 12px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Promedio</th>
                    <th style="text-align:left;padding:10px 12px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Evaluador</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($evaluaciones as $ev):
                $prom = (float)$ev->promedio_final;
                $promColor = $prom >= 4 ? '#059669' : ($prom >= 3 ? '#d97706' : '#dc2626');
                $promBg    = $prom >= 4 ? '#dcfce7' : ($prom >= 3 ? '#fef3c7' : '#fee2e2');
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;"
                onmouseover="this.style.background='#faf5ff'" onmouseout="this.style.background=''">
                <td style="padding:12px 14px;font-weight:700;color:#0f172a;">
                    <?= date('d/m/Y', strtotime($ev->fecha_evaluacion)) ?>
                </td>
                <td style="padding:12px;color:#475569;"><?= htmlspecialchars($ev->lapso_academico ?? '—') ?></td>
                <td style="padding:12px;text-align:center;">
                    <span style="background:<?= $promBg ?>;color:<?= $promColor ?>;
                                 padding:4px 12px;border-radius:20px;font-weight:800;font-size:.85rem;">
                        <?= number_format($prom, 2) ?> / 5.00
                    </span>
                </td>
                <td style="padding:12px;color:#475569;"><?= htmlspecialchars(trim($ev->tutor_nombre ?? '—')) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</div><!-- /container-fluid -->

<!-- MODAL: Registrar Evaluación Corta -->
<style>
.eval-overlay { display:none;position:fixed;inset:0;background:rgba(10,18,40,.72);backdrop-filter:blur(6px);z-index:9500;align-items:center;justify-content:center;padding:16px; }
.eval-overlay.open { display:flex; }
.eval-box { background:white;border-radius:20px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:0 32px 80px rgba(10,18,40,.3); }
.eval-head { background:linear-gradient(135deg,#5b21b6,#7c3aed);padding:20px 24px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:1; }
.eval-head h3 { font-size:.95rem;font-weight:800;color:white;margin:0; }
.eval-close { background:rgba(255,255,255,.2);border:none;color:white;width:28px;height:28px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem; }
.eval-body { padding:22px 24px; }
.eval-crit-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px; }
.eval-crit { background:#faf5ff;border:1.5px solid #e9d5ff;border-radius:10px;padding:10px 12px; }
.eval-crit label { display:block;font-size:.7rem;font-weight:800;color:#5b21b6;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px; }
.eval-stars { display:flex;gap:3px; }
.eval-star { width:28px;height:28px;border-radius:6px;border:1.5px solid #e2e8f0;background:white;color:#94a3b8;font-size:.75rem;font-weight:800;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center; }
.eval-star.sel { background:#7c3aed;border-color:#7c3aed;color:white; }
.eval-foot { padding:14px 24px;border-top:1px solid #f1f5f9;display:flex;gap:10px;background:#f8fafc;position:sticky;bottom:0; }
</style>

<div id="overlayEvalCorta" class="eval-overlay" onclick="if(event.target===this)this.classList.remove('open')">
<div class="eval-box">
    <div class="eval-head">
        <h3><i class="ti ti-clipboard-check" style="margin-right:7px;"></i>Registrar Evaluación ISP</h3>
        <button class="eval-close" onclick="document.getElementById('overlayEvalCorta').classList.remove('open')"><i class="ti ti-x"></i></button>
    </div>
    <form id="formEvalCorta">
    <div class="eval-body">
        <!-- Info row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div>
                <label style="display:block;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;">Fecha de Evaluación</label>
                <input type="date" name="fecha" id="evalFecha" value="<?= date('Y-m-d') ?>"
                       style="width:100%;padding:9px 12px;border:2px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-family:inherit;box-sizing:border-box;outline:none;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
                <label style="display:block;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;">Lapso / Fase</label>
                <input type="text" name="lapso" placeholder="Ej: Período 2025-2026"
                       style="width:100%;padding:9px 12px;border:2px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-family:inherit;box-sizing:border-box;outline:none;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
        </div>

        <!-- Criterios -->
        <div style="font-size:.72rem;font-weight:800;color:#5b21b6;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px;">Criterios de Evaluación (1-5)</div>
        <div class="eval-crit-grid">
        <?php
        $criteriosLabels = [
            'criterio_iniciativa'   => 'Iniciativa',
            'criterio_interes'      => 'Interés',
            'criterio_conocimiento' => 'Conocimiento',
            'criterio_analisis'     => 'Análisis',
            'criterio_comunicacion' => 'Comunicación',
            'criterio_aprendizaje'  => 'Aprendizaje',
            'criterio_companerismo' => 'Compañerismo',
            'criterio_cooperacion'  => 'Cooperación',
            'criterio_puntualidad'  => 'Puntualidad',
            'criterio_presentacion' => 'Presentación',
            'criterio_desarrollo'   => 'Desarrollo',
            'criterio_analisis_res' => 'Análisis de Res.',
            'criterio_conclusiones' => 'Conclusiones',
            'criterio_recomendacion'=> 'Recomendaciones',
        ];
        foreach($criteriosLabels as $key => $label): ?>
        <div class="eval-crit">
            <label><?= $label ?></label>
            <div class="eval-stars" data-crit="<?= $key ?>">
                <?php for($s=1;$s<=5;$s++): ?>
                <button type="button" class="eval-star" data-val="<?= $s ?>" onclick="selectStar(this)"><?= $s ?></button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="<?= $key ?>" id="hdn_<?= $key ?>" value="0">
        </div>
        <?php endforeach; ?>
        </div>

        <!-- Promedio en tiempo real -->
        <div style="background:#f5f3ff;border-radius:10px;padding:10px 14px;margin-bottom:14px;
                    display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:.8rem;font-weight:700;color:#5b21b6;">Promedio estimado</span>
            <span id="evalPromedioVivo" style="font-size:1.2rem;font-weight:900;color:#7c3aed;">— / 5.00</span>
        </div>

        <!-- Observaciones -->
        <div>
            <label style="display:block;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;">Observaciones</label>
            <textarea name="observaciones" rows="2" placeholder="Comentarios sobre el desempeño del pasante..."
                      style="width:100%;padding:9px 12px;border:2px solid #e2e8f0;border-radius:9px;font-size:.85rem;
                             font-family:inherit;box-sizing:border-box;outline:none;resize:vertical;"
                      onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
        </div>
    </div>
    <div class="eval-foot">
        <button type="button" onclick="document.getElementById('overlayEvalCorta').classList.remove('open')"
                style="flex:1;padding:10px;background:white;border:1.5px solid #e2e8f0;border-radius:10px;
                       color:#64748b;font-weight:600;font-size:.85rem;cursor:pointer;font-family:inherit;">
            Cancelar
        </button>
        <button type="submit" id="btnGuardarEval"
                style="flex:2;padding:10px;border:none;border-radius:10px;cursor:pointer;
                       background:linear-gradient(135deg,#5b21b6,#7c3aed);color:white;
                       font-weight:700;font-size:.88rem;font-family:inherit;
                       display:flex;align-items:center;justify-content:center;gap:6px;
                       box-shadow:0 4px 12px rgba(124,58,237,.3);transition:opacity .2s;">
            <i class="ti ti-check"></i> Guardar Evaluación
        </button>
    </div>
    </form>
</div>
</div>

<script>
// [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
var VP_PASANTE_ID  = <?= $userId ?>;
var VP_CSRF        = '<?= htmlspecialchars(Session::generateCsrfToken()) ?>';

// Tabs
function pvTab(tab, btn) {
    document.querySelectorAll('.pv-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    ['semanal','almanaque','historial','evaluaciones'].forEach(s => {
        document.getElementById('pv-sec-' + s)?.classList.toggle('d-none', s !== tab);
    });
}

// ── Evaluación ISP ─────────────────────────────────────────
function selectStar(btn) {
    const grupo = btn.closest('.eval-stars');
    const val   = parseInt(btn.dataset.val);
    const key   = grupo.dataset.crit;
    grupo.querySelectorAll('.eval-star').forEach((s, i) => {
        s.classList.toggle('sel', i < val);
    });
    document.getElementById('hdn_' + key).value = val;
    recalcPromedio();
}

function recalcPromedio() {
    const criterios = ['criterio_iniciativa','criterio_interes','criterio_conocimiento','criterio_analisis',
        'criterio_comunicacion','criterio_aprendizaje','criterio_companerismo','criterio_cooperacion',
        'criterio_puntualidad','criterio_presentacion','criterio_desarrollo','criterio_analisis_res',
        'criterio_conclusiones','criterio_recomendacion'];
    let suma = 0, llenos = 0;
    criterios.forEach(c => {
        const v = parseInt(document.getElementById('hdn_' + c)?.value || 0);
        if (v > 0) { suma += v; llenos++; }
    });
    const el = document.getElementById('evalPromedioVivo');
    if (llenos === criterios.length) {
        const prom = (suma / criterios.length).toFixed(2);
        el.textContent = prom + ' / 5.00';
        const p = parseFloat(prom);
        el.style.color = p >= 4 ? '#059669' : p >= 3 ? '#d97706' : '#dc2626';
    } else {
        el.textContent = (llenos > 0 ? (suma/llenos).toFixed(2) + '* / 5.00' : '— / 5.00');
        el.style.color = '#7c3aed';
    }
}

document.getElementById('formEvalCorta')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const criterios = ['criterio_iniciativa','criterio_interes','criterio_conocimiento','criterio_analisis',
        'criterio_comunicacion','criterio_aprendizaje','criterio_companerismo','criterio_cooperacion',
        'criterio_puntualidad','criterio_presentacion','criterio_desarrollo','criterio_analisis_res',
        'criterio_conclusiones','criterio_recomendacion'];
    for (const c of criterios) {
        if (!parseInt(document.getElementById('hdn_' + c)?.value || 0)) {
            Swal.fire({ icon:'warning', title:'Evaluación incompleta', text:'Completa todos los criterios (1-5).', confirmButtonColor:'#7c3aed' });
            return;
        }
    }
    const btn = document.getElementById('btnGuardarEval');
    btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader"></i> Guardando...';

    const fd = new FormData(this);
    fd.append('pasante_id', VP_PASANTE_ID);
    fd.append('csrf_token', VP_CSRF);
    const data = Object.fromEntries(fd.entries());

    fetch(URLROOT + '/actividades/registrarEvaluacionCorta', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':VP_CSRF},
        body: JSON.stringify(data)
    }).then(r=>r.json()).then(res => {
        if (res.success) {
            document.getElementById('overlayEvalCorta').classList.remove('open');
            Swal.fire({ icon:'success', title:'Evaluación guardada', text: res.message, timer:2000, showConfirmButton:false })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon:'error', title:'Error', text: res.message, confirmButtonColor:'#7c3aed' });
            btn.disabled=false; btn.innerHTML='<i class="ti ti-check"></i> Guardar Evaluación';
        }
    });
});

// Slide mes en almanaque
var _pvMesActual = <?= (int)date('n') ?> - 1; // [SGP-FIX-PJAX-REDECL]
var _pvMeses = <?= json_encode(array_values(array_slice($mesesNombres, 1))) ?>;
function pvSlideMes(dir) {
    const cards = document.querySelectorAll('.alm-mm-card');
    cards.forEach(c => c.classList.remove('active-mobile'));
    _pvMesActual = Math.max(0, Math.min(11, _pvMesActual + dir));
    cards[_pvMesActual]?.classList.add('active-mobile');
    document.getElementById('pv-mes-lbl').textContent = _pvMeses[_pvMesActual] ?? '';
}

// Tooltip almanaque — [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta sin reload completo.
var tooltip = document.getElementById('almTooltip');
var ESTADO_LABELS = {P:'Presente', A:'Ausente', J:'Justificado', sin_dato:'Sin registro', futuro:'Futuro'};
var ESTADO_COLORS = {P:'#16a34a', A:'#dc2626', J:'#2563eb', sin_dato:'#e2e8f0', futuro:'#e2e8f0'};
document.querySelectorAll('.alm-cell:not(.is-empty)').forEach(cell => {
    cell.addEventListener('mouseenter', e => {
        const eKey = cell.dataset.e;
        document.getElementById('tt-fecha').textContent = cell.dataset.f ?? '';
        document.getElementById('tt-est').textContent   = ESTADO_LABELS[eKey] ?? eKey;
        document.getElementById('tt-dot').style.background = ESTADO_COLORS[eKey] ?? '#e2e8f0';
        tooltip.style.opacity = '1';
    });
    cell.addEventListener('mousemove', e => {
        tooltip.style.left = (e.clientX + 14) + 'px';
        tooltip.style.top  = (e.clientY - 40) + 'px';
    });
    cell.addEventListener('mouseleave', () => { tooltip.style.opacity = '0'; });
});

// Marcar asistencia (pasante corto usa tabla asistencias)
async function marcarCorto(fecha, estado, btn) {
    const payload = { csrf_token: VP_CSRF, pasante_id: VP_PASANTE_ID, fecha, estado };
    try {
        const r = await fetch(URLROOT + '/actividades/marcarAsistenciaCorto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': VP_CSRF },
            body: JSON.stringify(payload)
        });
        const res = await r.json();
        if (!res.success) { Swal.fire({ icon:'error', title:'Error', text: res.message, confirmButtonColor:'#EF4444' }); return; }

        // Actualizar UI del día
        const dayEl  = document.getElementById('semday-' + fecha);
        const badge  = document.getElementById('badge-'   + fecha);
        const btns   = dayEl?.querySelectorAll('.sem-btn') ?? [];
        const cfgMap = {
            'Presente':    {bg:'#dcfce7',color:'#15803d',cls:'es-P',est:'P'},
            'Ausente':     {bg:'#fee2e2',color:'#dc2626',cls:'es-A',est:'A'},
            'Justificado': {bg:'#dbeafe',color:'#1d4ed8',cls:'es-J',est:'J'},
        };
        const cfg = cfgMap[estado];
        if (cfg && dayEl && badge) {
            dayEl.className = 'sem-day ' + cfg.cls;
            badge.style.background = cfg.bg;
            badge.style.color      = cfg.color;
            badge.textContent      = estado;
            btns.forEach(b => b.classList.toggle('active', b.dataset.est === cfg.est));
        }

        Swal.fire({ toast:true, position:'top-end', icon:'success', title:estado + ' registrado', showConfirmButton:false, timer:2000, timerProgressBar:true });
    } catch(err) {
        Swal.fire({ icon:'error', title:'Error de red', text: err.message, confirmButtonColor:'#EF4444' });
    }
}
</script>
