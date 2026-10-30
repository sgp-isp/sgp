<?php
/**
 * Vista: Almanaque — Panel Histórico de Pasantía (Bento Grid Premium)
 * Ruta: GET /asistencias/almanaque/{pasante_id}?anio=YYYY
 */

$viewerRole        = (int)Session::get('role_id');
$pasante           = $data['pasante']           ?? null;
$instituciones     = $data['instituciones']      ?? [];
$anio              = (int)($data['anio']         ?? date('Y'));
$anios             = $data['anios']              ?? [$anio];
$periodo           = $data['periodo']            ?? ['nombre' => null, 'estado' => null];
$grilla            = $data['grilla']             ?? [];
$weekLabels        = $data['weekLabels']         ?? [];
$stats             = $data['stats']              ?? ['P'=>0,'A'=>0,'J'=>0,'laborables'=>0];
$pct               = (float)($data['pctAsistencia'] ?? 0);
$rachaActual       = (int)($data['rachaActual']  ?? 0);
$rachaMax          = (int)($data['rachaMax']     ?? 0);
$historialCompleto = $data['historialCompleto']  ?? [];
$diasTrans         = (int)($data['diasTrans']    ?? 0);
$diasTotal         = (int)($data['diasTotal']    ?? 0);
$diasRest          = (int)($data['diasRest']     ?? 0);
$pctTiempo         = (float)($data['pctTiempo']  ?? 0);
$laborablesAnio    = (int)($data['laborablesAnio'] ?? $diasTrans);

$nombre    = trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''));
$ini       = strtoupper(substr($pasante->nombres ?? '?', 0, 1) . substr($pasante->apellidos ?? '?', 0, 1));
$horasAcum = (int)($pasante->horas_acumuladas ?? 0);
$horasMeta = (int)($pasante->horas_meta       ?? 1440);
$pctHoras  = $horasMeta > 0 ? min(100, round($horasAcum / $horasMeta * 100)) : 0;
$estadoPas = $pasante->estado_pasantia ?? 'Sin Asignar';
$instNombre= $pasante->institucion_nombre ?? '—';
$tutorNom  = trim($pasante->tutor_nombre  ?? '—');

// Restricción botón Rellenar: solo habilitado si no hay registros reales (es_auto_fill=0) previos
// y el pasante no está Finalizado. El daemon auto_fill no cuenta como "registro real".
$totalRegistrosReales = (int)($data['totalRegistrosReales'] ?? 0);
$puedeRellenar = ($estadoPas !== 'Finalizado') && ($totalRegistrosReales === 0);

// KPI color por % asistencia
$kpiColor = $pct >= 90 ? '#16a34a' : ($pct >= 75 ? '#d97706' : '#dc2626');

// Color estado pasantía
$estadoMap = [
    'Activo'     => ['color'=>'#16a34a','bg'=>'#dcfce7','label'=>'ACTIVO'],
    'Finalizado' => ['color'=>'#2563eb','bg'=>'#dbeafe','label'=>'FINALIZADO'],
    'Retirado'   => ['color'=>'#dc2626','bg'=>'#fee2e2','label'=>'RETIRADO'],
    'Pendiente'  => ['color'=>'#d97706','bg'=>'#fef3c7','label'=>'PENDIENTE'],
];
$estadoCfg = $estadoMap[$estadoPas] ?? ['color'=>'#64748b','bg'=>'#f1f5f9','label'=>strtoupper($estadoPas)];

// Etiquetas mes heatmap
$mesesEtiquetas = [
    'Jan'=>'Ene','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Abr','May'=>'May','Jun'=>'Jun',
    'Jul'=>'Jul','Aug'=>'Ago','Sep'=>'Sep','Oct'=>'Oct','Nov'=>'Nov','Dec'=>'Dic',
];
$diasNombreCorto = ['lunes','martes','miércoles','jueves','viernes','sábado','domingo'];
?>

<!-- ── TOOLTIP GLOBAL ── -->
<div id="almTooltip">
    <div class="tt-fecha" id="tt-fecha"></div>
    <div class="tt-estado"><div class="tt-dot" id="tt-dot"></div><span id="tt-est"></span></div>
    <div id="tt-extra"></div>
</div>

<style>
/* ═══ TOKENS ═══════════════════════════════════════════════════════ */
:root {
    --alm-navy:    #172554;
    --alm-blue:    #2563eb;
    --alm-card:    #ffffff;
    --alm-bg:      #f1f5f9;
    --alm-radius:  18px;
    --alm-shadow:  0 2px 16px rgba(15,23,42,.07);
    --alm-border:  #e2e8f0;
    --cell-size:   13px;
    --cell-gap:    3px;
    /* estado colores */
    --c-P:         #16a34a;
    --c-J:         #2563eb;
    --c-A:         #dc2626;
    --c-feriado:   #f59e0b;
    --c-sin:       #e2e8f0;
    --c-fuera:     #f8fafc;
}

/* ═══ WRAPPER ══════════════════════════════════════════════════════ */
.alm-wrap { width:100%; padding-bottom:60px; }

/* ═══ BENTO GRID ═══════════════════════════════════════════════════ */
/* Tarjeta genérica */
.alm-card {
    background: var(--alm-card);
    border-radius: var(--alm-radius);
    box-shadow: var(--alm-shadow);
    border: 1px solid var(--alm-border);
    padding: 22px 24px;
}
.alm-card-title {
    font-size: .82rem; font-weight: 800; color: #64748b;
    text-transform: uppercase; letter-spacing: .6px;
    display: flex; align-items: center; gap: 7px;
    margin-bottom: 16px;
}

/* ═══ BANNER ═══════════════════════════════════════════════════════ */
.alm-banner {
    background: linear-gradient(135deg,var(--alm-navy) 0%,#1e3a8a 50%,var(--alm-blue) 100%);
    border-radius: 20px; padding: 28px 32px; margin-bottom: 20px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px; position: relative; overflow: hidden;
}
.alm-banner::before {
    content:''; position:absolute; top:-50px; right:-50px;
    width:220px; height:220px; background:rgba(255,255,255,.04);
    border-radius:50%; pointer-events:none;
}

/* ═══ KPIs ══════════════════════════════════════════════════════════ */
.alm-kpis {
    display: grid;
    grid-template-columns: repeat(6,1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.alm-kpi {
    background: var(--alm-card);
    border-radius: var(--alm-radius);
    box-shadow: var(--alm-shadow);
    border: 1px solid var(--alm-border);
    padding: 18px 18px 14px;
    display: flex; flex-direction: column; gap: 4px;
    transition: transform .2s, box-shadow .2s;
}
.alm-kpi:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(15,23,42,.1); }
.alm-kpi-val { font-size: 1.85rem; font-weight: 900; line-height: 1; }
.alm-kpi-lbl { font-size: .72rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }

/* ═══ BENTO FILA 2: Progreso + Timeline ══════════════════════════ */
.alm-row2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 20px;
}

/* Barra de progreso horas */
.alm-progress-track {
    height: 12px; border-radius: 20px; background: #e2e8f0; overflow: hidden; margin: 10px 0 6px;
}
.alm-progress-fill {
    height: 100%; border-radius: 20px;
    transition: width .8s cubic-bezier(.4,0,.2,1);
}

/* Timeline */
.alm-timeline {
    position: relative; padding: 12px 0;
}
.alm-tl-line {
    position: absolute; top: 50%; left: 0; right: 0;
    height: 4px; background: #e2e8f0; border-radius: 4px; transform: translateY(-50%);
}
.alm-tl-fill {
    position: absolute; top: 0; left: 0; height: 100%;
    background: linear-gradient(90deg, #16a34a, #22c55e);
    border-radius: 4px; transition: width .6s ease;
}
.alm-tl-dots {
    position: relative; display: flex; justify-content: space-between; align-items: center;
}
.alm-tl-dot {
    width: 14px; height: 14px; border-radius: 50%; border: 3px solid white;
    box-shadow: 0 0 0 2px currentColor; flex-shrink: 0;
}
.alm-tl-hoy {
    position: absolute; display: flex; flex-direction: column; align-items: center;
}

/* ═══ MULTI-MONTH GRID CALENDAR ══════════════════════════════════ */
.alm-row-mid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
.alm-calendar-wrapper {
    background: var(--alm-card); border-radius: var(--alm-radius);
    box-shadow: var(--alm-shadow); border: 1px solid var(--alm-border);
    padding: 24px 28px;
}
/* Unified 1-Month Slider for Desktop and Mobile */
.alm-mm-mobile-controls {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px; padding: 0 4px;
}
.alm-mm-grid {
    display: flex; gap: 0;
}
.alm-mm-card {
    display: none; width: 100%; flex-shrink: 0;
    border: 1px solid #f1f5f9; border-radius: 14px; padding: 14px;
    background: #f8fafc; transition: transform .2s, box-shadow .2s;
}
.alm-mm-card.active-mobile {
    display: block;
}
.alm-mm-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,.05); border-color:#e2e8f0; }
.alm-mm-title {
    font-size: .85rem; font-weight: 800; color: #1e293b; text-transform: uppercase;
    letter-spacing: .5px; margin-bottom: 12px; text-align: center; display: none; /* Hide internal title as it's in the controls */
}
.alm-mm-days-header {
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px;
    margin-bottom: 8px;
}
.alm-mm-days-header div {
    font-size: .7rem; font-weight: 700; color: #94a3b8; text-align: center;
}
.alm-mm-days-grid {
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px;
}
.alm-cell {
    aspect-ratio: 1; border-radius: 6px; display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 700; cursor: pointer; transition: transform .1s;
    color: transparent; /* color oculto si es empty. se sobreescribe abajo */
}
.alm-cell:hover { transform: scale(1.15); z-index: 10; box-shadow: 0 2px 6px rgba(0,0,0,.15); }
.alm-cell.is-empty { cursor: default; }
.alm-cell.is-empty:hover { transform: none; box-shadow: none; }

.alm-cell[data-e="fuera"]    { background: transparent; color: #cbd5e1; font-weight: 600; opacity: .5; }
.alm-cell[data-e="futuro"]   { background: #ffffff; color: #cbd5e1; font-weight: 600; border: 1px solid #e2e8f0; }
.alm-cell[data-e="sin_dato"] { background: var(--c-sin); color: #64748b; }
.alm-cell[data-e="P"]        { background: var(--c-P); color: white; }
.alm-cell[data-e="J"]        { background: var(--c-J); color: white; }
.alm-cell[data-e="A"]        { background: var(--c-A); color: white; }
.alm-cell[data-e="feriado"]  { background: var(--c-feriado); color: white; }

/* Celda editable — anillo sutil al hover */
.alm-cell-editable { cursor: pointer !important; }
.alm-cell-editable:hover { outline: 2px solid rgba(255,255,255,.7); outline-offset: -2px; }

.alm-legend {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    margin-top: 18px; font-size: .75rem; color: #64748b;
}
.alm-legend-item { display: flex; align-items: center; gap: 5px; font-weight: 600; }
.alm-legend-dot  { width: 12px; height: 12px; border-radius: 3px; flex-shrink: 0; }

/* ═══ HISTORIAL TABLE BENTO ════════════════════════════════════════ */
.hist-filters {
    display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px;
}
.hist-pill {
    padding: 5px 14px; border-radius: 20px; font-size: .77rem; font-weight: 700;
    cursor: pointer; border: 2px solid transparent; transition: all .15s;
    user-select: none;
}
.hist-pill[data-active="1"] { border-color: currentColor; }

/* Tabla historial */
.hist-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
.hist-table th {
    padding: 9px 14px; text-align: left; font-size: .7rem; font-weight: 800;
    color: #64748b; text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 2px solid #f1f5f9; white-space: nowrap;
}
.hist-table td {
    padding: 10px 14px; border-bottom: 1px solid #f8fafc; vertical-align: middle;
}
.hist-table tr:hover td { background: #f8fafc; }
.hist-table tr.hist-ausente td { background: #fff8f8; }
.hist-table tr.hist-ausente:hover td { background: #fee2e2; }

.hist-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 800;
}
.hist-metodo-badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 8px; border-radius: 8px; font-size: .68rem; font-weight: 700;
    background: #f1f5f9; color: #64748b;
}
.hist-empty {
    text-align: center; padding: 40px 20px;
    color: #94a3b8; font-size: .9rem;
}

/* ── DataTables paginación custom ── */
.hist-dt-top {
    display: flex; align-items: center; justify-content: flex-end;
    margin-bottom: 10px;
}
.hist-dt-top label {
    display: flex; align-items: center; gap: 6px;
    font-size: .78rem; color: #64748b; font-weight: 600;
}
.hist-dt-top select {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    padding: 4px 8px; font-size: .78rem; color: #1e293b;
    background: #f8fafc; cursor: pointer; outline: none;
}
.hist-dt-bottom {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px; margin-top: 14px; padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}
.hist-dt-bottom .dataTables_info {
    font-size: .75rem; color: #94a3b8; font-weight: 600;
}
.hist-dt-bottom .dataTables_paginate {
    display: flex; align-items: center; gap: 4px;
}
.hist-dt-bottom .dataTables_paginate .paginate_button {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 30px; height: 30px; border-radius: 8px;
    font-size: .8rem; font-weight: 700; cursor: pointer;
    border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569;
    transition: all .15s; user-select: none; padding: 0 8px;
}
.hist-dt-bottom .dataTables_paginate .paginate_button:hover:not(.disabled) {
    background: #1e3a8a; color: white; border-color: #1e3a8a;
}
.hist-dt-bottom .dataTables_paginate .paginate_button.current {
    background: #1e3a8a; color: white; border-color: #1e3a8a;
}
.hist-dt-bottom .dataTables_paginate .paginate_button.disabled {
    opacity: .35; cursor: default;
}
/* Ocultar la barra de búsqueda interna de DT (usamos nuestros propios filtros) */
.hist-dt-top .dataTables_filter { display: none; }

/* ═══ DESGLOSE MENSUAL ═════════════════════════════════════════════ */
.alm-meses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
    gap: 10px;
}
.alm-mes-card {
    background: #f8fafc; border-radius: 12px; padding: 16px 12px;
    text-align: center; border: 2px solid transparent; transition: all .2s;
}
.alm-mes-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.06); }
.alm-mes-card.has-data { border-color: #e2e8f0; }

/* ═══ TOOLTIP ══════════════════════════════════════════════════════ */
#almTooltip {
    position: fixed; z-index: 9999; pointer-events: none;
    background: #1e293b; color: #f8fafc; border-radius: 10px;
    padding: 10px 14px; font-size: .8rem; line-height: 1.5;
    box-shadow: 0 8px 24px rgba(0,0,0,.3);
    opacity: 0; transition: opacity .15s; max-width: 200px;
}
#almTooltip.show { opacity: 1; }
#almTooltip .tt-fecha  { font-weight: 700; font-size: .85rem; margin-bottom: 2px; }
#almTooltip .tt-estado { display: flex; align-items: center; gap: 5px; }
#almTooltip .tt-dot    { width: 8px; height: 8px; border-radius: 2px; flex-shrink: 0; }
#almTooltip #tt-extra  { color: #94a3b8; font-size: .75rem; margin-top: 2px; }

/* ═══ RESPONSIVE ════════════════════════════════════════════════════ */
@media (max-width: 1100px) {
    .alm-kpis { grid-template-columns: repeat(3, 1fr); }
    .alm-row-mid { grid-template-columns: 1fr; }
}
@media (max-width: 860px) {
    #almActividadesSection            { grid-column: 1 / -1 !important; }
    /* Exámenes también ocupa ancho completo cuando actividades expande */
    #almActividadesSection + div      { grid-column: 1 / -1 !important; }
}
@media (max-width: 768px) {
    .alm-banner {
        flex-direction: column; align-items: flex-start;
        padding: 20px 18px; gap: 14px;
    }
    .alm-banner-actions {
        width: 100%; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
    }
    .alm-banner-actions form  { flex: 1; min-width: 120px; }
    .alm-banner-actions > a.alm-btn-action { flex: 1; min-width: 100px; justify-content: center; text-align: center; }
    .alm-kpis    { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .alm-row2    { grid-template-columns: 1fr; }
    .alm-heatmap-wrap { padding: 16px 12px; }
    :root { --cell-size: 11px; --cell-gap: 2px; }
    .hist-table th:nth-child(6),
    .hist-table td:nth-child(6)  { display: none; }  /* Ocultar obsrv en móvil */
    .alm-meses-grid { grid-template-columns: repeat(3,1fr); }
    /* Evaluación mini-radar: 2 cols en móvil */
    .eval-grupos-grid { grid-template-columns: repeat(2, 1fr) !important; }
    /* Justificaciones: header con wrap */
    .just-item-header { flex-wrap: wrap !important; gap: 6px !important; }
}
@media (max-width: 480px) {
    .alm-kpis { grid-template-columns: repeat(2,1fr); gap: 8px; }
    .alm-kpi-val { font-size: 1.5rem; }
    .alm-banner  { padding: 18px 14px; }
    .alm-banner h1 { font-size: clamp(.95rem, 4.5vw, 1.15rem); }
    /* Botones en fila única bajo los badges de período/año */
    .alm-banner-actions { gap: 6px; }
    .alm-banner-actions form,
    .alm-banner-actions > a.alm-btn-action { min-width: 0; flex: 1; }
    /* Badge período y navegador año: ancho automático (no crecen) */
    .alm-banner-actions > div { flex-shrink: 0; }
    /* Desglose mensual: 2 columnas — evita cards de ~107px que truncan "Septiembre" */
    .alm-meses-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    .alm-mes-card { padding: 14px 10px; }
}
</style>

<div class="dashboard-container" style="width:100%; max-width:100%; padding:0;">
<div class="alm-wrap">

<!-- ═══ BANNER ════════════════════════════════════════════════════════ -->
<div class="alm-banner">
    <!-- Info pasante -->
    <div style="display:flex;align-items:center;gap:16px;z-index:1;min-width:0;">
        <div style="width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,0.18);
                    display:flex;align-items:center;justify-content:center;
                    color:#fff;font-weight:900;font-size:1.2rem;flex-shrink:0;letter-spacing:1px;
                    border:2px solid rgba(255,255,255,0.25);">
            <?= htmlspecialchars($ini) ?>
        </div>
        <div style="min-width:0;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <h1 style="color:#fff;font-size:1.4rem;font-weight:800;margin:0;word-break:break-word;overflow-wrap:anywhere;">
                    <?= htmlspecialchars($nombre) ?>
                </h1>
                <span style="background:<?= $estadoCfg['bg'] ?>;color:<?= $estadoCfg['color'] ?>;
                             font-size:.65rem;font-weight:800;padding:3px 10px;border-radius:20px;
                             text-transform:uppercase;letter-spacing:.6px;white-space:nowrap;flex-shrink:0;">
                    <?= $estadoCfg['label'] ?>
                </span>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:6px;">
                <span style="color:rgba(255,255,255,.75);font-size:.8rem;display:flex;align-items:center;gap:5px;">
                    <i class="ti ti-id-badge"></i> C.I. <?= htmlspecialchars($pasante->cedula ?? '—') ?>
                </span>
                <?php if ($pasante->departamento ?? null): ?>
                <span style="background:rgba(255,255,255,.12);border-radius:20px;padding:2px 10px;
                             color:rgba(255,255,255,.85);font-size:.78rem;font-weight:600;">
                    <i class="ti ti-building"></i> <?= htmlspecialchars($pasante->departamento) ?>
                </span>
                <?php endif; ?>
                <?php if ($instNombre !== '—'): ?>
                <span style="background:rgba(255,255,255,.10);border-radius:20px;padding:2px 10px;
                             color:rgba(255,255,255,.75);font-size:.78rem;">
                    <i class="ti ti-school"></i> <?= htmlspecialchars($instNombre) ?>
                </span>
                <?php endif; ?>
                <?php if ($tutorNom !== '—'): ?>
                <span style="color:rgba(255,255,255,.65);font-size:.78rem;display:flex;align-items:center;gap:4px;">
                    <i class="ti ti-user-check"></i> <?= htmlspecialchars($tutorNom) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="alm-banner-actions" style="display:flex;align-items:center;gap:8px;z-index:1;flex-shrink:0;">

        <?php
        // ── Badge de Período ──
        $periodoEstadoColors = [
            'activo'  => ['bg' => 'rgba(16,185,129,.25)', 'color' => '#6ee7b7', 'dot' => '#10b981'],
            'cerrado' => ['bg' => 'rgba(148,163,184,.2)', 'color' => 'rgba(255,255,255,.6)', 'dot' => '#94a3b8'],
        ];
        $perEstado  = strtolower($periodo['estado'] ?? '');
        $perCfg     = $periodoEstadoColors[$perEstado] ?? $periodoEstadoColors['cerrado'];
        $perNombre  = $periodo['nombre'] ?? null;
        ?>

        <?php if ($perNombre): ?>
        <!-- Badge de Período / Cohorte -->
        <div style="background:<?= $perCfg['bg'] ?>;border:1px solid rgba(255,255,255,.18);
                    border-radius:12px;padding:8px 14px;display:flex;align-items:center;gap:8px;">
            <span style="width:8px;height:8px;border-radius:50%;background:<?= $perCfg['dot'] ?>;flex-shrink:0;
                         <?= $perEstado === 'activo' ? 'animation:alm-pulse-dot 1.5s infinite;' : '' ?>"></span>
            <span style="color:<?= $perCfg['color'] ?>;font-size:.8rem;font-weight:700;
                               white-space:nowrap;max-width:160px;overflow:hidden;text-overflow:ellipsis;"
                  title="<?= htmlspecialchars($perNombre) ?>">
                <?= htmlspecialchars(mb_strimwidth($perNombre, 0, 28, '…')) ?>
            </span>
        </div>
        <style>
        @keyframes alm-pulse-dot {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:.5; transform:scale(1.4); }
        }
        </style>
        <?php endif; ?>

        <?php if (count($anios) > 1):
            sort($anios); // garantizar ASC
            $idxActual = array_search($anio, $anios);
            $anioAnt   = ($idxActual !== false && $idxActual > 0)                    ? $anios[$idxActual - 1] : null;
            $anioSig   = ($idxActual !== false && $idxActual < count($anios) - 1)    ? $anios[$idxActual + 1] : null;
        ?>
        <!-- Navegador de año — reemplaza el select nativo por pill coherente con el badge de período -->
        <div style="display:flex;align-items:center;gap:2px;background:rgba(255,255,255,.12);
                    border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:5px 8px;">
            <?php if ($anioAnt): ?>
            <a href="?anio=<?= $anioAnt ?>" data-no-pjax
               title="Ver <?= $anioAnt ?>"
               style="color:rgba(255,255,255,.8);text-decoration:none;padding:3px 7px;
                      border-radius:8px;font-size:.85rem;line-height:1;transition:background .15s;"
               onmouseover="this.style.background='rgba(255,255,255,.18)'"
               onmouseout="this.style.background='transparent'">
                <i class="ti ti-chevron-left"></i>
            </a>
            <?php else: ?>
            <span style="color:rgba(255,255,255,.25);padding:3px 7px;font-size:.85rem;line-height:1;">
                <i class="ti ti-chevron-left"></i>
            </span>
            <?php endif; ?>

            <span style="color:#fff;font-size:.8rem;font-weight:700;padding:0 6px;white-space:nowrap;">
                <i class="ti ti-calendar" style="font-size:.75rem;opacity:.65;margin-right:3px;"></i><?= $anio ?>
            </span>

            <?php if ($anioSig): ?>
            <a href="?anio=<?= $anioSig ?>" data-no-pjax
               title="Ver <?= $anioSig ?>"
               style="color:rgba(255,255,255,.8);text-decoration:none;padding:3px 7px;
                      border-radius:8px;font-size:.85rem;line-height:1;transition:background .15s;"
               onmouseover="this.style.background='rgba(255,255,255,.18)'"
               onmouseout="this.style.background='transparent'">
                <i class="ti ti-chevron-right"></i>
            </a>
            <?php else: ?>
            <span style="color:rgba(255,255,255,.25);padding:3px 7px;font-size:.85rem;line-height:1;">
                <i class="ti ti-chevron-right"></i>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <a href="<?= URLROOT ?>/asistencias/pdfAlmanaque/<?= (int)($pasante->id ?? 0) ?>?anio=<?= $anio ?>"
           target="_blank"
           title="Exportar PDF del almanaque"
           style="width:38px;height:38px;border-radius:10px;background:#dc2626;color:#fff;
                  text-decoration:none;display:flex;align-items:center;justify-content:center;
                  font-size:1.15rem;flex-shrink:0;transition:background .2s;"
           onmouseover="this.style.background='#b91c1c'"
           onmouseout="this.style.background='#dc2626'">
            <i class="ti ti-file-type-pdf"></i>
        </a>

        <a href="<?= URLROOT ?>/asistencias"
           class="pjax-link alm-btn-action"
           style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);
                  border-radius:10px;padding:9px 18px;color:#fff;font-size:.84rem;
                  font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;
                  transition:background .2s;white-space:nowrap;"
           onmouseover="this.style.background='rgba(255,255,255,.25)'"
           onmouseout="this.style.background='rgba(255,255,255,.15)'">
            <i class="ti ti-arrow-left"></i> Asistencias
        </a>
    </div>
</div>

<!-- ═══ DATOS PERSONALES ════════════════════════════════════════════════ -->
<div style="background:#fff;border-radius:18px;border:1px solid #e2e8f0;padding:20px 24px;margin-bottom:16px;box-shadow:0 2px 16px rgba(15,23,42,.06);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="ti ti-id-badge-2" style="color:#2563eb;font-size:1.1rem;"></i>
            <span style="font-weight:800;font-size:.92rem;color:#1e293b;">Datos Personales</span>
        </div>
        <button onclick="almAbrirEditar()" style="background:#eff6ff;color:#2563eb;border:1.5px solid #bfdbfe;border-radius:10px;padding:6px 14px;font-size:.78rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .2s;" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
            <i class="ti ti-edit"></i> Editar
        </button>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
        <div style="background:#f8fafc;border-radius:12px;padding:12px 14px;border:1px solid #f1f5f9;">
            <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;"><i class="ti ti-user" style="margin-right:3px;"></i>Nombres</div>
            <div id="alm-dp-nombres" style="font-weight:700;color:#1e293b;font-size:.85rem;"><?= htmlspecialchars($pasante->nombres ?? '—') ?></div>
        </div>
        <div style="background:#f8fafc;border-radius:12px;padding:12px 14px;border:1px solid #f1f5f9;">
            <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;"><i class="ti ti-user" style="margin-right:3px;"></i>Apellidos</div>
            <div id="alm-dp-apellidos" style="font-weight:700;color:#1e293b;font-size:.85rem;"><?= htmlspecialchars($pasante->apellidos ?? '—') ?></div>
        </div>
        <div style="background:#f8fafc;border-radius:12px;padding:12px 14px;border:1px solid #f1f5f9;">
            <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;"><i class="ti ti-id" style="margin-right:3px;"></i>Cédula</div>
            <div style="font-weight:700;color:#1e293b;font-size:.85rem;"><?= htmlspecialchars($pasante->cedula ?? '—') ?></div>
        </div>
        <div style="background:#f8fafc;border-radius:12px;padding:12px 14px;border:1px solid #f1f5f9;">
            <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;"><i class="ti ti-phone" style="margin-right:3px;"></i>Teléfono</div>
            <div id="alm-dp-telefono" style="font-weight:700;color:#1e293b;font-size:.85rem;"><?= htmlspecialchars($pasante->telefono ?? 'No registrado') ?></div>
        </div>
        <div style="background:#f8fafc;border-radius:12px;padding:12px 14px;border:1px solid #f1f5f9;grid-column:span 2;">
            <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;"><i class="ti ti-school" style="margin-right:3px;"></i>Institución de Procedencia</div>
            <div id="alm-dp-institucion" style="font-weight:700;color:#1e293b;font-size:.85rem;"><?= htmlspecialchars($instNombre) ?></div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR DATOS PERSONALES (almanaque) -->
<div id="almModalEditar" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:20px;width:90%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(15,23,42,.3);">
        <div style="background:linear-gradient(135deg,#172554,#2563eb);border-radius:20px 20px 0 0;padding:24px 28px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="color:#fff;font-weight:800;font-size:1.1rem;"><i class="ti ti-edit" style="margin-right:8px;"></i>Editar Datos Personales</div>
                <div style="color:rgba(255,255,255,.7);font-size:.8rem;margin-top:3px;"><?= htmlspecialchars($nombre) ?></div>
            </div>
            <button onclick="almCerrarEditar()" style="background:rgba(255,255,255,.15);border:none;color:#fff;width:34px;height:34px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;"><i class="ti ti-x"></i></button>
        </div>
        <div style="padding:24px 28px;display:flex;flex-direction:column;gap:16px;">
            <input type="hidden" id="alm-edit-id" value="<?= (int)($pasante->id ?? 0) ?>">
            <div>
                <label style="font-size:.78rem;font-weight:700;color:#1e3a8a;display:block;margin-bottom:6px;">Nombres</label>
                <input type="text" id="alm-edit-nombres" placeholder="Nombres del pasante" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;outline:none;transition:border .2s;box-sizing:border-box;" onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
                <label style="font-size:.78rem;font-weight:700;color:#1e3a8a;display:block;margin-bottom:6px;">Apellidos</label>
                <input type="text" id="alm-edit-apellidos" placeholder="Apellidos del pasante" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;outline:none;transition:border .2s;box-sizing:border-box;" onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
                <label style="font-size:.78rem;font-weight:700;color:#1e3a8a;display:block;margin-bottom:6px;">Teléfono</label>
                <input type="text" id="alm-edit-telefono" placeholder="Teléfono de contacto" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;outline:none;transition:border .2s;box-sizing:border-box;" onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div>
                <label style="font-size:.78rem;font-weight:700;color:#1e3a8a;display:block;margin-bottom:6px;">Institución de Procedencia</label>
                <select id="alm-edit-institucion" style="width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.88rem;outline:none;transition:border .2s;box-sizing:border-box;" onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
                    <option value="">Seleccione una institución</option>
                    <?php foreach ($instituciones as $inst): ?>
                    <option value="<?= $inst->id ?>"><?= htmlspecialchars($inst->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button onclick="almGuardarDatos()" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:12px 20px;border-radius:12px;font-size:.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 12px rgba(37,99,235,.3);transition:all .2s;margin-top:4px;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='none'">
                <i class="ti ti-device-floppy"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    var modal = document.getElementById('almModalEditar');

    window.almAbrirEditar = async function() {
        var pid = document.getElementById('alm-edit-id').value;
        modal.style.display = 'flex';
        try {
            var resp = await fetch(URLROOT + '/pasantes/obtenerDatosPersonales/' + pid);
            var json = await resp.json();
            if (json.success) {
                var d = json.data;
                document.getElementById('alm-edit-nombres').value    = d.nombres   || '';
                document.getElementById('alm-edit-apellidos').value  = d.apellidos || '';
                document.getElementById('alm-edit-telefono').value   = d.telefono  || '';
                document.getElementById('alm-edit-institucion').value = d.institucion_id || '';
            }
        } catch(e) {}
    };

    window.almCerrarEditar = function() {
        modal.style.display = 'none';
    };

    window.almGuardarDatos = async function() {
        var id        = document.getElementById('alm-edit-id').value;
        var nombres   = document.getElementById('alm-edit-nombres').value.trim();
        var apellidos = document.getElementById('alm-edit-apellidos').value.trim();
        var telefono  = document.getElementById('alm-edit-telefono').value.trim();
        var inst      = document.getElementById('alm-edit-institucion').value;

        if (!nombres || !apellidos) {
            Swal.fire('Atención', 'Nombres y apellidos son obligatorios', 'warning');
            return;
        }

        var fd = new FormData();
        fd.append('id', id);
        fd.append('nombres', nombres);
        fd.append('apellidos', apellidos);
        fd.append('telefono', telefono);
        fd.append('institucion', inst);

        try {
            var resp = await fetch(URLROOT + '/pasantes/actualizarDatos', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var json = await resp.json();
            if (json.success) {
                almCerrarEditar();
                // Actualizar los campos visibles sin recargar
                document.getElementById('alm-dp-nombres').textContent    = nombres;
                document.getElementById('alm-dp-apellidos').textContent  = apellidos;
                document.getElementById('alm-dp-telefono').textContent   = telefono || 'No registrado';
                var selInst = document.getElementById('alm-edit-institucion');
                var instLabel = selInst.options[selInst.selectedIndex]?.text || '—';
                if (inst) document.getElementById('alm-dp-institucion').textContent = instLabel;
                Swal.fire({ icon:'success', title:'¡Actualizado!', text: json.message, timer:1500, showConfirmButton:false });
            } else {
                Swal.fire('Error', json.message, 'error');
            }
        } catch(e) {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    };

    modal.addEventListener('click', function(e) {
        if (e.target === modal) almCerrarEditar();
    });
})();
</script>

<!-- ═══ KPIs ═══════════════════════════════════════════════════════════ -->
<div class="alm-kpis">

    <div class="alm-kpi" style="border-left:4px solid <?= $kpiColor ?>;" title="Porcentaje de asistencia sobre los <?= $laborablesAnio ?> días transcurridos a la fecha. El avance total de la meta es <?= $pctHoras ?>% (<?= $horasAcum ?>h / <?= $horasMeta ?>h).">
        <div class="alm-kpi-val" style="color:<?= $kpiColor ?>;"><?= $pct ?>%</div>
        <div class="alm-kpi-lbl" style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
            <?php if ($estadoPas === 'Activo'): ?>
            Puntualidad a la Fecha
            <span style="font-size:.6rem;background:#eff6ff;color:#1d4ed8;border-radius:10px;padding:1px 6px;font-weight:700;white-space:nowrap;">en curso</span>
            <?php else: ?>
            Asistencia <?= $anio ?>
            <?php endif; ?>
        </div>
        <div style="font-size:.68rem;color:#64748b;margin-top:2px;">
            <?php if ($estadoPas === 'Activo'): ?>
            <strong><?= ($stats['P'] + $stats['J']) ?></strong> de <strong><?= $laborablesAnio ?></strong> días transcurridos asistidos
            <?php else: ?>
            <strong><?= $diasTrans ?></strong> de <strong><?= $diasTotal ?></strong> días transcurridos
            <?php endif; ?>
        </div>
        <div style="height:5px;border-radius:20px;background:#e2e8f0;overflow:hidden;margin-top:6px;">
            <div style="height:100%;border-radius:20px;background:<?= $kpiColor ?>;width:<?= $pct ?>%;transition:width .8s ease;"></div>
        </div>
    </div>

    <div class="alm-kpi" style="border-left:4px solid #16a34a;">
        <div class="alm-kpi-val" style="color:#16a34a;"><?= $stats['P'] ?></div>
        <div class="alm-kpi-lbl">Presentes</div>
        <div style="height:5px;border-radius:20px;background:#dcfce7;overflow:hidden;margin-top:6px;">
            <div style="height:100%;border-radius:20px;background:#16a34a;width:<?= $stats['laborables']>0?round($stats['P']/$stats['laborables']*100):0 ?>%;"></div>
        </div>
    </div>

    <div class="alm-kpi" style="border-left:4px solid #2563eb;">
        <div class="alm-kpi-val" style="color:#2563eb;"><?= $stats['J'] ?></div>
        <div class="alm-kpi-lbl">Justificados</div>
        <div style="height:5px;border-radius:20px;background:#dbeafe;overflow:hidden;margin-top:6px;">
            <div style="height:100%;border-radius:20px;background:#2563eb;width:<?= $stats['laborables']>0?round($stats['J']/$stats['laborables']*100):0 ?>%;"></div>
        </div>
    </div>

    <div class="alm-kpi" style="border-left:4px solid #dc2626;">
        <div class="alm-kpi-val" style="color:#dc2626;"><?= $stats['A'] ?></div>
        <div class="alm-kpi-lbl">Ausentes</div>
        <div style="height:5px;border-radius:20px;background:#fee2e2;overflow:hidden;margin-top:6px;">
            <div style="height:100%;border-radius:20px;background:#dc2626;width:<?= $stats['laborables']>0?round($stats['A']/$stats['laborables']*100):0 ?>%;"></div>
        </div>
    </div>

    <div class="alm-kpi" style="border-left:4px solid #7c3aed;">
        <div class="alm-kpi-val" style="color:#7c3aed;"><?= $rachaMax ?></div>
        <div class="alm-kpi-lbl">Racha máxima</div>
        <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;">días sin faltar (seguidos)</div>
    </div>

    <div class="alm-kpi" style="border-left:4px solid #0891b2;">
        <div class="alm-kpi-val" style="color:#0891b2;"><?= $stats['P'] + $stats['J'] ?></div>
        <div class="alm-kpi-lbl">Días marcados</div>
        <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;">presentes + justificados</div>
    </div>

</div>

<!-- ═══ FILA 2: Progreso Horas + Timeline ══════════════════════════════ -->
<div class="alm-row2">

    <!-- Card Progreso de Horas -->
    <div class="alm-card">
        <div class="alm-card-title">
            <i class="ti ti-clock-hour-4" style="color:#2563eb;"></i>
            Progreso de Horas de Pasantía
        </div>
        <?php
        $pctHorasColor = $pctHoras >= 90 ? '#16a34a' : ($pctHoras >= 60 ? '#d97706' : '#2563eb');
        ?>
        <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:4px;">
            <div>
                <span style="font-size:2.4rem;font-weight:900;color:<?= $pctHorasColor ?>;line-height:1;"><?= $horasAcum ?></span>
                <span style="font-size:1rem;color:#94a3b8;font-weight:600;"> / <?= $horasMeta ?>h</span>
            </div>
            <span style="background:<?= $pctHorasColor ?>18;color:<?= $pctHorasColor ?>;
                         font-size:.8rem;font-weight:800;padding:6px 14px;border-radius:10px;">
                <?= $pctHoras ?>%
            </span>
        </div>
        <div class="alm-progress-track">
            <div class="alm-progress-fill" style="width:<?= $pctHoras ?>%;background:<?= $pctHorasColor ?>;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#94a3b8;margin-top:4px;">
            <span>0h</span>
            <span><?= $horasMeta ?>h meta</span>
        </div>
        <?php
        $horasFaltantes    = $horasMeta - $horasAcum;
        $horasPosiblesTot  = $diasRest * 8;           // máximo alcanzable antes de fecha fin
        $metaAlcanzable    = $horasPosiblesTot >= $horasFaltantes;
        $diasMarcados      = ($stats['P'] ?? 0) + ($stats['J'] ?? 0) + ($stats['A'] ?? 0);
        $promH             = $diasMarcados > 0 ? round($horasAcum / $diasMarcados, 1) : ($diasTrans > 0 ? round($horasAcum / $diasTrans, 1) : 8.0);
        ?>
        <?php if ($horasFaltantes > 0): ?>
        <?php if ($metaAlcanzable): ?>
        <div style="margin-top:14px;padding:10px 14px;background:#f8fafc;border-radius:10px;
                    border:1px dashed #e2e8f0;font-size:.78rem;color:#64748b;">
            <i class="ti ti-info-circle" style="color:#2563eb;"></i>
            Faltan <strong style="color:#1e293b;"><?= $horasFaltantes ?>h</strong>
            para completar — quedan <strong><?= $diasRest ?> días hábiles</strong>
            (<?= $horasPosiblesTot ?>h posibles)
            <?php if ($diasTrans > 0): ?>
            · promedio actual <strong><?= $promH ?>h/día</strong>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div style="margin-top:14px;padding:11px 14px;background:#fff7ed;border-radius:10px;
                    border:1px solid #fed7aa;font-size:.78rem;color:#9a3412;">
            <div style="display:flex;align-items:flex-start;gap:8px;">
                <i class="ti ti-alert-triangle" style="color:#ea580c;font-size:1rem;flex-shrink:0;margin-top:1px;"></i>
                <div>
                    <strong>Meta no alcanzable con la fecha fin actual</strong><br>
                    Faltan <strong><?= $horasFaltantes ?>h</strong> para la meta, pero solo quedan
                    <strong><?= $diasRest ?> día<?= $diasRest !== 1 ? 's' : '' ?> hábil<?= $diasRest !== 1 ? 'es' : '' ?></strong>
                    = <strong><?= $horasPosiblesTot ?>h</strong> posibles antes de
                    <strong><?= $pasante->fecha_fin ? date('d/m/Y', strtotime($pasante->fecha_fin)) : 'fecha fin' ?></strong>.
                    <?php if ($horasPosiblesTot > 0): ?>
                    Alcanzará <strong><?= $horasAcum + $horasPosiblesTot ?>h</strong> de <?= $horasMeta ?>h
                    (<?= round(($horasAcum + $horasPosiblesTot) / $horasMeta * 100) ?>%).
                    <?php endif; ?>
                    <br><span style="color:#c2410c;font-size:.72rem;">Considera ajustar la meta de horas o extender la fecha fin.</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div style="margin-top:14px;padding:10px 14px;background:#dcfce7;border-radius:10px;
                    border:1px solid #bbf7d0;font-size:.78rem;color:#16a34a;font-weight:700;">
            <i class="ti ti-circle-check"></i> ¡Meta de horas completada!
        </div>
        <?php endif; ?>
    </div>

    <!-- Card Timeline Pasantía -->
    <div class="alm-card">
        <div class="alm-card-title">
            <i class="ti ti-timeline" style="color:#7c3aed;"></i>
            Cronograma de Pasantía
        </div>
        <?php if ($pasante->fecha_inicio): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;">
            <div style="background:#f0fdf4;border-radius:12px;padding:12px 14px;border:1px solid #bbf7d0;">
                <div style="font-size:.65rem;font-weight:800;color:#16a34a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Inicio</div>
                <div style="font-size:.95rem;font-weight:800;color:#15803d;">
                    <?= date('d/m/Y', strtotime($pasante->fecha_inicio)) ?>
                </div>
            </div>
            <div style="background:<?= $pasante->fecha_fin ? '#f0f4ff' : '#f8fafc' ?>;border-radius:12px;padding:12px 14px;border:1px solid <?= $pasante->fecha_fin ? '#c7d2fe' : '#e2e8f0' ?>;">
                <div style="font-size:.65rem;font-weight:800;color:<?= $pasante->fecha_fin ? '#4f46e5' : '#94a3b8' ?>;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Fin Estimado</div>
                <div style="font-size:.95rem;font-weight:800;color:<?= $pasante->fecha_fin ? '#312e81' : '#94a3b8' ?>;">
                    <?= $pasante->fecha_fin ? date('d/m/Y', strtotime($pasante->fecha_fin)) : 'Sin definir' ?>
                </div>
            </div>
        </div>

        <?php if ($diasTotal > 0): ?>
        <!-- Barra Timeline -->
        <div style="position:relative;height:32px;background:#f1f5f9;border-radius:20px;overflow:hidden;margin-bottom:10px;">
            <div style="position:absolute;top:0;left:0;height:100%;width:<?= min(100,$pctTiempo) ?>%;
                        background:linear-gradient(90deg,#16a34a,#22c55e);border-radius:20px;
                        transition:width .8s ease;"></div>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
                        font-size:.72rem;font-weight:800;color:<?= $pctTiempo>40?'white':'#374151' ?>;">
                <?= $pctTiempo ?>% completado
            </div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.72rem;color:#94a3b8;margin-bottom:14px;">
            <span><?= $diasTrans ?> día<?= $diasTrans !== 1 ? 's' : '' ?> hábil<?= $diasTrans !== 1 ? 'es' : '' ?> transcurrido<?= $diasTrans !== 1 ? 's' : '' ?></span>
            <span><?= $diasRest ?> día<?= $diasRest !== 1 ? 's' : '' ?> hábil<?= $diasRest !== 1 ? 'es' : '' ?> restante<?= $diasRest !== 1 ? 's' : '' ?></span>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <span style="background:<?= $estadoCfg['bg'] ?>;color:<?= $estadoCfg['color'] ?>;
                         padding:5px 14px;border-radius:20px;font-size:.75rem;font-weight:800;">
                <i class="ti ti-point-filled"></i> <?= $estadoCfg['label'] ?>
            </span>
            <?php if ($diasRest > 0 && $estadoPas === 'Activo'): ?>
            <span style="background:#f1f5f9;color:#475569;padding:5px 14px;border-radius:20px;font-size:.75rem;font-weight:700;">
                <i class="ti ti-clock"></i> <?= $diasRest ?> día<?= $diasRest !== 1 ? 's' : '' ?> hábil<?= $diasRest !== 1 ? 'es' : '' ?> restante<?= $diasRest !== 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div style="text-align:center;padding:24px;color:#94a3b8;">
            <i class="ti ti-calendar-off" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
            <p style="font-size:.85rem;margin:0;">Sin fecha de inicio registrada</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ═══ LAYOUT EN GRAN GRILLA: CALENDARIO + HISTORIAL ═══ -->
<div class="alm-row-mid">

<!-- COLUMNA IZQ: CALENDARIO -->
<div class="alm-calendar-col">
<!-- ═══ CALENDARIO MULTI-MONTH BENTO ═══════════════════════════════════════ -->
<div class="alm-calendar-wrapper" style="margin-bottom: 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
        <div style="font-size:1rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
            <i class="ti ti-calendar-stats" style="color:#2563eb;font-size:1.1rem;"></i>
            Calendario de Asistencias <?= $anio ?>
            <span style="font-size:.75rem;color:#94a3b8;font-weight:500;">
                <?= $stats['laborables'] ?> días laborables
            </span>
        </div>
    </div>

    <div class="alm-mm-container" id="alm-mm-container">
        <!-- Controles Móviles -->
        <div class="alm-mm-mobile-controls">
            <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-weight:700;padding:6px 14px;" onclick="almSlideMes(-1)"><i class="ti ti-chevron-left"></i></button>
            <span id="alm-mm-mes-lbl" style="font-weight:800;font-size:.95rem;color:#1e3a8a;text-transform:uppercase;">Enero</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:10px;font-weight:700;padding:6px 14px;" onclick="almSlideMes(1)"><i class="ti ti-chevron-right"></i></button>
        </div>

        <div class="alm-mm-grid" id="alm-mm-grid">
        <?php
        // Construir mapa lineal por fecha para fácil consulta
        $mapaFechas = [];
        foreach ($grilla as $wk => $dias) {
            foreach ($dias as $dow => $cell) {
                if ($cell) {
                    $mapaFechas[$cell['fecha']] = $cell;
                }
            }
        }

        $estadoLabel = ['P'=>'Presente','A'=>'Ausente','J'=>'Justificado',
                        'feriado'=>'Feriado','sin_dato'=>'Sin registro',
                        'futuro'=>'Futuro','fuera'=>'Fuera del período'];
        $estadoColor = ['P'=>'#16a34a','A'=>'#dc2626','J'=>'#2563eb',
                        'feriado'=>'#f59e0b','sin_dato'=>'#e2e8f0',
                        'futuro'=>'#f1f5f9','fuera'=>'transparent'];
        $mesesEsFull = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                        'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        for ($m = 1; $m <= 12; $m++):
            $dt = new DateTime("$anio-" . sprintf('%02d', $m) . "-01");
            $diasEnMes = $dt->format('t');
        ?>
            <div class="alm-mm-card <?= $m === 1 ? 'active-mobile' : '' ?>" data-mes="<?= $m ?>" data-nombre="<?= $mesesEsFull[$m] ?>">
                <div class="alm-mm-title" style="display:none;"><?= $mesesEsFull[$m] ?></div>
                <div class="alm-mm-title pc-only-title"><?= $mesesEsFull[$m] ?></div>
                <div class="alm-mm-days-header">
                    <div>L</div><div>M</div><div>M</div><div>J</div><div>V</div>
                </div>
                <div class="alm-mm-days-grid">
                    <?php
                    $primerDiaDow = (int)$dt->format('N'); // 1 = Lunes
                    // Rellenos en blanco hasta el primer día laborable (lun a vie)
                    $espacios = ($primerDiaDow <= 5) ? ($primerDiaDow - 1) : 0;
                    for ($i = 0; $i < $espacios; $i++) {
                        echo '<div></div>';
                    }

                    for ($d = 1; $d <= $diasEnMes; $d++) {
                        $currentDt = new DateTime("$anio-" . sprintf('%02d', $m) . "-" . sprintf('%02d', $d));
                        $dow = (int)$currentDt->format('N');
                        if ($dow > 5) continue; // Solo lunes a viernes

                        $fechaS = $currentDt->format('Y-m-d');
                        $cell = $mapaFechas[$fechaS] ?? null;

                        if ($cell) {
                            $e = $cell['estado'];
                            $hora = $cell['hora'] ?? '';
                            $metodo = $cell['metodo'] ?? '';
                            $ttEst = $estadoLabel[$e] ?? $e;
                            $ttColor = $estadoColor[$e] ?? '#94a3b8';
                            $ttExtra = $hora ? $hora . ($metodo ? ' · '.$metodo : '') : '';
                            
                            if ($e === 'feriado' && !empty($cell['feriadoNombre'])) {
                                $ttExtra = $cell['feriadoNombre'];
                            }

                            $isEmptyClass = ($e === 'fuera' || $e === 'futuro') ? ' is-empty' : '';

                            echo '<div class="alm-cell'.$isEmptyClass.'"
                                       data-e="'.htmlspecialchars($e).'"
                                       data-f="'.htmlspecialchars($fechaS).'"
                                       data-est="'.htmlspecialchars($ttEst).'"
                                       data-color="'.htmlspecialchars($ttColor).'"
                                       data-extra="'.htmlspecialchars($ttExtra).'"
                                       data-af="'.((int)($cell['es_auto_fill'] ?? 0)).'">'.$d.'</div>';
                        } else {
                            echo '<div class="alm-cell is-empty"></div>'; // Fuera de grilla
                        }
                    }
                    ?>
                </div>
            </div>
        <?php endfor; ?>
        </div>
    </div>

    <!-- Leyenda -->
    <div class="alm-legend">
        <span style="color:#94a3b8;">Leyenda:</span>
        <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#16a34a;"></div> Presente</div>
        <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#2563eb;"></div> Justificado</div>
        <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#dc2626;"></div> Ausente</div>
        <div class="alm-legend-item" title="Basado en el Almanaque Oficial de Feriados de Venezuela" style="cursor:help;">
            <div class="alm-legend-dot" style="background:#f59e0b;"></div> Feriado
        </div>
        <div class="alm-legend-item"><div class="alm-legend-dot" style="background:#e2e8f0;"></div> Sin registro</div>
    </div>
</div>
<style>
@media (max-width: 768px) { .pc-only-title { display: none !important; } }
</style>
</div>
<!-- /COLUMNA IZQ -->

<!-- COLUMNA DER: HISTORIAL -->
<div class="alm-hist-col" style="min-width: 0;">
<!-- ═══ HISTORIAL CRONOLÓGICO ═══════════════════════════════════════════ -->
<div class="alm-card" style="height: 100%; margin-bottom: 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
        <div class="alm-card-title" style="margin-bottom:0;">
            <i class="ti ti-list-details" style="color:#1e3a8a;"></i>
            Historial de Asistencias
            <span style="background:#f1f5f9;color:#475569;font-size:.68rem;padding:2px 10px;border-radius:20px;font-weight:700;text-transform:none;letter-spacing:0;">
                <?= count($historialCompleto) ?> registros
            </span>
        </div>
        <?php if (in_array($viewerRole, [0, 1])): ?>
        <div style="display:inline-flex;gap:8px;flex-wrap:wrap;">
            <?php if ($puedeRellenar): ?>
            <button onclick="abrirRellenarAsistencia()" title="Rellenar toda la asistencia del pasante (SuperAdmin/Admin)"
                    style="display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:white;border:none;padding:8px 16px;border-radius:10px;font-size:.8rem;font-weight:700;cursor:pointer;box-shadow:0 3px 10px rgba(124,58,237,.25);transition:all .2s;"
                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 5px 15px rgba(124,58,237,.35)'"
                    onmouseout="this.style.transform='none';this.style.boxShadow='0 3px 10px rgba(124,58,237,.25)'">
                <i class="ti ti-wand" style="font-size:.95rem;"></i> Rellenar Asistencia
            </button>
            <?php elseif ($estadoPas !== 'Finalizado'): ?>
            <button disabled
                    title="Este pasante ya tiene <?= $totalRegistrosReales ?> registro(s) manual(es). Usa el almanaque para modificaciones individuales."
                    style="display:inline-flex;align-items:center;gap:7px;background:#e5e7eb;color:#9ca3af;border:none;padding:8px 16px;border-radius:10px;font-size:.8rem;font-weight:700;cursor:not-allowed;opacity:.65;">
                <i class="ti ti-wand" style="font-size:.95rem;"></i> Rellenar Asistencia
            </button>
            <?php endif; ?>
            <button onclick="abrirAjustarFechasAlm()" title="Editar fechas individuales de esta pasantía"
                    style="display:inline-flex;align-items:center;gap:7px;background:white;color:#0369a1;border:1.5px solid #bae6fd;padding:8px 16px;border-radius:10px;font-size:.8rem;font-weight:700;cursor:pointer;box-shadow:0 2px 6px rgba(3,105,161,.1);transition:all .2s;"
                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 12px rgba(3,105,161,.2)'"
                    onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 6px rgba(3,105,161,.1)'">
                <i class="ti ti-calendar-cog" style="font-size:.95rem;"></i> Ajustar Fechas
            </button>
        </div>
        <?php endif; ?>
        <!-- Filtros por estado y fecha -->
        <div class="hist-filters" id="histFilters">
            <!-- Buscador por fecha -->
            <div style="position:relative; margin-right:auto;">
                <i class="ti ti-calendar" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; z-index:2; pointer-events:none;"></i>
                <input type="text" id="filtroFechaHistorial" class="form-control form-control-sm" placeholder="Buscar por fecha..." style="width:200px; padding-left:32px; border-radius:20px; font-size:.8rem;">
            </div>
            
            <button type="button" id="btnLimpiarFechaAlm" class="btn btn-sm btn-light" style="display:none; border-radius:20px; font-size:.7rem; font-weight:700;"><i class="ti ti-x"></i> Limpiar</button>
            <div class="hist-pill" data-filtro="todos" data-active="1"
                 style="background:#1e2b58;color:white;"
                 onclick="filtrarHistorial('todos',this)">
                <i class="ti ti-list"></i> Todos
            </div>
            <div class="hist-pill" data-filtro="Presente" data-active="0"
                 style="background:#dcfce7;color:#16a34a;"
                 onclick="filtrarHistorial('Presente',this)">
                <i class="ti ti-check"></i> Presentes
            </div>
            <div class="hist-pill" data-filtro="Ausente" data-active="0"
                 style="background:#fee2e2;color:#dc2626;"
                 onclick="filtrarHistorial('Ausente',this)">
                <i class="ti ti-x"></i> Ausentes
            </div>
            <div class="hist-pill" data-filtro="Justificado" data-active="0"
                 style="background:#dbeafe;color:#2563eb;"
                 onclick="filtrarHistorial('Justificado',this)">
                <i class="ti ti-file-check"></i> Justificados
            </div>
        </div>
    </div>

    <?php if (empty($historialCompleto)): ?>
    <div class="hist-empty">
        <i class="ti ti-ghost" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.3;"></i>
        <p style="margin:0;font-weight:600;">Sin registros de asistencia aún</p>
        <p style="margin:4px 0 0;font-size:.8rem;color:#b0bec5;">Los registros aparecerán conforme el pasante marque asistencia</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="hist-table" id="histTable">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Día</th>
                <th>Estado</th>
                <th>Hora Entrada</th>
                <th>Método</th>
                <th>Observación</th>
                <?php if (in_array($viewerRole, [0, 1])): ?><th style="width:80px;text-align:center;">Acción</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $mesesEs = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun',
                    '07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
        $diasEs  = ['Monday'=>'Lunes','Tuesday'=>'Martes','Wednesday'=>'Miércoles',
                    'Thursday'=>'Jueves','Friday'=>'Viernes','Saturday'=>'Sábado','Sunday'=>'Domingo'];
        foreach ($historialCompleto as $h):
            $est     = $h->estado ?? '—';
            $fecha   = $h->fecha  ?? '';
            $horaEnt = $h->hora_entrada  ?? ($h->hora_registro ?? null);
            $horaFmt = $horaEnt ? substr($horaEnt, 0, 5) : '—';
            $metodo  = $h->metodo        ?? '—';
            $obs     = trim($h->observacion ?? $h->motivo_justificacion ?? '');
            $diaEn   = date('l', strtotime($fecha));
            $diaEs   = $diasEs[$diaEn] ?? $diaEn;
            $mes     = substr($fecha, 5, 2);
            $fechaFmt= date('d', strtotime($fecha)) . ' ' . ($mesesEs[$mes]??'') . ' '. date('Y', strtotime($fecha));

            $rowClass = $est === 'Ausente' ? 'hist-ausente' : '';

            $badgeCfg = [
                'Presente'   => ['bg'=>'#dcfce7','color'=>'#15803d','ico'=>'ti-check'],
                'Ausente'    => ['bg'=>'#fee2e2','color'=>'#dc2626','ico'=>'ti-x'],
                'Justificado'=> ['bg'=>'#dbeafe','color'=>'#1d4ed8','ico'=>'ti-file-check'],
            ];
            $bc = $badgeCfg[$est] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','ico'=>'ti-minus'];

            $metodoCfg = [
                'Kiosco' => ['bg'=>'#f0f4ff','color'=>'#4338ca','ico'=>'ti-device-tablet'],
                'Manual' => ['bg'=>'#fef3c7','color'=>'#d97706','ico'=>'ti-pencil'],
                'AutoFill' => ['bg'=>'#f0fdf4','color'=>'#16a34a','ico'=>'ti-refresh'],
            ];
            $mc = $metodoCfg[$metodo] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','ico'=>'ti-help'];
        ?>
        <tr class="<?= $rowClass ?>" data-estado="<?= htmlspecialchars($est) ?>">
            <td style="font-weight:700;color:#1e293b;white-space:nowrap;"><?= htmlspecialchars($fechaFmt) ?></td>
            <td style="color:#64748b;font-size:.8rem;"><?= htmlspecialchars($diaEs) ?></td>
            <td>
                <span class="hist-badge" style="background:<?= $bc['bg'] ?>;color:<?= $bc['color'] ?>;">
                    <i class="ti <?= $bc['ico'] ?>" style="font-size:.75rem;"></i>
                    <?= htmlspecialchars($est) ?>
                </span>
            </td>
            <td>
                <?php if ($horaFmt !== '—'): ?>
                <span style="font-family:monospace;font-weight:700;color:#1e293b;font-size:.9rem;">
                    <?= htmlspecialchars($horaFmt) ?>
                </span>
                <?php else: ?>
                <span style="color:#cbd5e1;font-size:.8rem;">—</span>
                <?php endif; ?>
            </td>
            <td>
                <span class="hist-metodo-badge" style="background:<?= $mc['bg'] ?>;color:<?= $mc['color'] ?>;">
                    <i class="ti <?= $mc['ico'] ?>" style="font-size:.75rem;"></i>
                    <?= htmlspecialchars($metodo) ?>
                </span>
            </td>
            <td style="color:#64748b;font-size:.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                title="<?= htmlspecialchars($obs) ?>">
                <?= $obs ? htmlspecialchars($obs) : '<span style="color:#cbd5e1">—</span>' ?>
            </td>
            <?php if (in_array($viewerRole, [0, 1])): ?>
            <td style="text-align:center;">
                <?php if ($est === 'Ausente'): ?>
                <button onclick="cambiarAPresente(<?= (int)($h->id ?? 0) ?>, '<?= htmlspecialchars($fechaFmt) ?>', this)"
                        title="Cambiar a Presente"
                        style="background:#dcfce7;color:#15803d;border:1px solid #86efac;border-radius:8px;padding:4px 10px;font-size:.72rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px;white-space:nowrap;transition:all .15s;"
                        onmouseover="this.style.background='#bbf7d0'" onmouseout="this.style.background='#dcfce7'">
                    <i class="ti ti-check" style="font-size:.8rem;"></i> Presente
                </button>
                <?php else: ?>
                <span style="color:#e2e8f0;font-size:.75rem;">—</span>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
</div>
<!-- /COLUMNA DER -->
</div><!-- /alm-row-mid -->

<!-- ═══ DESGLOSE MENSUAL COMPLETO (multi-año) ══════════════════════════ -->
<?php
$mesesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$fi_global = $fi_pasantia ?? ($pasante->fecha_inicio ?? null);
$ff_global = $ff_pasantia ?? ($pasante->fecha_fin    ?? date('Y-m-d'));

if ($fi_global && !empty($statsDesglose)):
    $fiY = (int)substr($fi_global, 0, 4);
    $fiM = (int)substr($fi_global, 5, 2);
    $ffY = (int)substr($ff_global, 0, 4);
    $ffM = (int)substr($ff_global, 5, 2);
    $aniosRango   = range($fiY, $ffY);
    $esMultiAnio  = count($aniosRango) > 1;
    $hoyY = (int)date('Y');
    $hoyM = (int)date('m');
?>
<div class="alm-card">
    <div class="alm-card-title" style="margin-bottom:16px;">
        <i class="ti ti-chart-bar" style="color:#2563eb;"></i>
        Desglose mensual
        <span style="background:#dbeafe;color:#1d4ed8;font-size:.68rem;padding:2px 10px;border-radius:20px;font-weight:700;text-transform:none;letter-spacing:0;">
            <?= $esMultiAnio ? "{$fiY} – {$ffY}" : $fiY ?>
        </span>
    </div>

    <div class="alm-meses-grid">
    <?php foreach ($aniosRango as $yrBloque):
        $mesStart = ($yrBloque === $fiY) ? $fiM : 1;
        $mesEnd   = ($yrBloque === $ffY) ? $ffM : 12;
        for ($m = $mesStart; $m <= $mesEnd; $m++):
            $ms  = $statsDesglose[$yrBloque][$m] ?? ['P'=>0,'A'=>0,'J'=>0];
            $tot = $ms['P'] + $ms['A'] + $ms['J'];
            $esMesActual = ($yrBloque === $hoyY && $m === $hoyM);

            if ($esMesActual && $tot > 0) {
                // Mes en curso: denominar sobre el total de días laborables del mes completo
                $diasEnMes = cal_days_in_month(CAL_GREGORIAN, $m, $yrBloque);
                $laborablesMes = 0;
                for ($d = 1; $d <= $diasEnMes; $d++) {
                    if ((int)date('N', mktime(0,0,0,$m,$d,$yrBloque)) < 6) $laborablesMes++;
                }
                $pcm = $laborablesMes > 0 ? round(($ms['P']+$ms['J'])/$laborablesMes*100) : null;
            } else {
                $pcm = $tot > 0 ? round(($ms['P']+$ms['J'])/$tot*100) : null;
            }
            $col = $pcm === null ? '#94a3b8' : ($pcm >= 90 ? '#16a34a' : ($pcm >= 75 ? '#d97706' : '#dc2626'));
    ?>
    <div class="alm-mes-card <?= $tot > 0 ? 'has-data' : '' ?> <?= $esMesActual ? 'mes-actual' : '' ?>"
         style="<?= $esMesActual ? 'border-color:#bfdbfe;background:#eff6ff;' : '' ?>">
        <div style="font-size:.78rem;font-weight:800;color:#64748b;margin-bottom:2px;text-transform:uppercase;line-height:1.2;overflow-wrap:anywhere;">
            <?= $mesesNombres[$m-1] ?>
        </div>
        <?php if ($esMultiAnio): ?>
        <div style="font-size:.6rem;color:#cbd5e1;font-weight:600;margin-bottom:6px;"><?= $yrBloque ?></div>
        <?php else: ?>
        <div style="margin-bottom:6px;"></div>
        <?php endif; ?>
        <?php if ($tot > 0): ?>
        <div style="font-size:1.35rem;font-weight:900;color:<?= $col ?>;"><?= $pcm ?>%</div>
        <div style="font-size:.66rem;color:#94a3b8;margin-top:3px;display:flex;justify-content:center;gap:4px;">
            <span style="color:#16a34a;font-weight:700;"><?= $ms['P'] ?>P</span>
            <span style="color:#dc2626;font-weight:700;"><?= $ms['A'] ?>A</span>
            <?php if ($ms['J']>0): ?><span style="color:#2563eb;font-weight:700;"><?= $ms['J'] ?>J</span><?php endif; ?>
        </div>
        <?php else: ?>
        <div style="font-size:.8rem;color:#cbd5e1;margin-top:4px;">—</div>
        <?php endif; ?>
    </div>
    <?php endfor; endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ═══ FILA 3: JUSTIFICACIONES + EVALUACIONES (BENTO) ════════════════════ -->
<?php
// extract($data) en Controller::view() ya las expone como variables directas
if (!isset($justificaciones)) $justificaciones = [];
if (!isset($evaluaciones))    $evaluaciones    = [];

// Meses en español (disponible para ambas cards)
$mesesEs2 = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun',
             '07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
?>
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:20px;" class="alm-row-jeval">
<style>
@media (max-width: 900px) { .alm-row-jeval { grid-template-columns: 1fr !important; } }
</style>

<!-- ────── CARD: Justificaciones ────── -->
<div class="alm-card" style="min-height:280px;">
    <div class="alm-card-title" style="margin-bottom:10px;">
        <i class="ti ti-file-certificate" style="color:#d97706;"></i>
        Justificaciones personales
        <span style="background:#fef3c7;color:#d97706;font-size:.68rem;padding:2px 10px;border-radius:20px;font-weight:700;text-transform:none;letter-spacing:0;">
            <?= count($justificaciones) ?>
        </span>
    </div>

    <!-- Callout informativo inline -->
    <div style="background:linear-gradient(to right,#f8fafc,#edf2f9);border:1px solid #e2e8f0;border-left:3px solid #2563eb;border-radius:10px;padding:9px 14px;margin-bottom:14px;display:flex;align-items:flex-start;gap:10px;">
        <div style="background:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(37,99,235,.12);flex-shrink:0;margin-top:1px;">
            <i class="ti ti-info-circle" style="color:#2563eb;font-size:.95rem;"></i>
        </div>
        <p style="margin:0;color:#64748b;font-size:.74rem;line-height:1.55;">
            Solo se listan justificaciones personales: <strong style="color:#1e293b;">reposos médicos, récipes y permisos</strong>. Los días feriados se registran automáticamente y se reflejan en el calendario de asistencia.
        </p>
    </div>

    <?php if (empty($justificaciones)): ?>
    <div style="text-align:center;padding:32px 20px;color:#94a3b8;">
        <i class="ti ti-check-circle" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;color:#16a34a;"></i>
        <p style="margin:0;font-weight:600;font-size:.88rem;">Sin justificaciones personales</p>
        <p style="margin:4px 0 0;font-size:.75rem;color:#b0bec5;">No hay excusas médicas u otros documentos cargados</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px;max-height:420px;overflow-y:auto;padding-right:4px;">
    <?php
    foreach ($justificaciones as $j):
        $fStr   = $j->fecha ?? '';
        $mes    = $fStr ? ($mesesEs2[substr($fStr,5,2)] ?? '') : '';
        $dia    = $fStr ? (int)substr($fStr,8,2) : '—';
        $yr     = $fStr ? substr($fStr,0,4) : '';
        $fechaFmt2 = "$dia $mes $yr";
        $motivo = $j->motivo_justificacion ?? '—';
        $ruta   = $j->ruta_evidencia ?? null;
    ?>
    <div style="background:#fffbeb;border-left:4px solid #f59e0b;border-radius:10px;padding:12px 14px;
                transition:box-shadow .2s;"
         onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.07)'"
         onmouseout="this.style.boxShadow='none'">
        <div class="just-item-header" style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;gap:8px;">
            <span style="font-size:.72rem;font-weight:800;color:#d97706;display:flex;align-items:center;gap:4px;">
                <i class="ti ti-calendar-event" style="font-size:.85rem;"></i>
                <?= htmlspecialchars($fechaFmt2) ?>
            </span>
            <?php if ($ruta): ?>
            <a href="<?= URLROOT ?>/asistencias/verEvidencia/<?= htmlspecialchars(basename($ruta)) ?>" target="_blank"
               style="background:#fde68a;color:#92400e;font-size:.68rem;font-weight:800;padding:3px 10px;
                      border-radius:20px;text-decoration:none;display:flex;align-items:center;gap:4px;
                      transition:background .15s;"
               onmouseover="this.style.background='#fcd34d'"
               onmouseout="this.style.background='#fde68a'">
                <?php
                $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
                $icon = $ext === 'pdf' ? 'ti-file-type-pdf' : 'ti-photo';
                ?>
                <i class="ti <?= $icon ?>"></i>
                <?= strtoupper($ext) === 'PDF' ? 'Ver PDF' : 'Ver Imagen' ?>
            </a>
            <?php else: ?>
            <span style="font-size:.65rem;color:#94a3b8;font-weight:600;">Sin evidencia</span>
            <?php endif; ?>
        </div>
        <p style="margin:0;font-size:.8rem;color:#92400e;line-height:1.45;">
            <?= htmlspecialchars(mb_strimwidth($motivo, 0, 120, '…')) ?>
        </p>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ────── CARD: Evaluaciones ────── -->
<div class="alm-card" style="min-height:280px;">
    <div class="alm-card-title" style="margin-bottom:14px;">
        <i class="ti ti-award" style="color:#7c3aed;"></i>
        Evaluaciones
        <span style="background:#f3e8ff;color:#7c3aed;font-size:.68rem;padding:2px 10px;border-radius:20px;font-weight:700;text-transform:none;letter-spacing:0;">
            <?= count($evaluaciones) ?>
        </span>
    </div>

    <?php if (empty($evaluaciones)): ?>
    <div style="text-align:center;padding:32px 20px;color:#94a3b8;">
        <i class="ti ti-chart-radar" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;color:#7c3aed;"></i>
        <p style="margin:0;font-weight:600;font-size:.88rem;">Sin evaluaciones registradas</p>
        <p style="margin:4px 0 0;font-size:.75rem;color:#b0bec5;">Aún no se ha aplicado ninguna evaluación</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;max-height:420px;overflow-y:auto;padding-right:4px;">
    <?php foreach ($evaluaciones as $ev):
        $prom = (float)($ev->promedio_final ?? 0);
        $promPct = min(100, $prom / 5 * 100);
        $promColor = $prom >= 4 ? '#16a34a' : ($prom >= 3 ? '#d97706' : '#dc2626');
        $promBg    = $prom >= 4 ? '#dcfce7' : ($prom >= 3 ? '#fef3c7' : '#fee2e2');

        $fechaEv = $ev->fecha_evaluacion ?? '';
        $mesEv   = $fechaEv ? ($mesesEs2[substr($fechaEv,5,2)] ?? '') : '';
        $diaEv   = $fechaEv ? (int)substr($fechaEv,8,2) : '—';
        $yrEv    = $fechaEv ? substr($fechaEv,0,4) : '';
        $fechaEvFmt = "$diaEv $mesEv $yrEv";

        // Criterios agrupados
        $criteriosGrupos = [
            'Actitud'       => ['criterio_iniciativa','criterio_interes','criterio_companerismo','criterio_cooperacion'],
            'Conocimiento'  => ['criterio_conocimiento','criterio_analisis','criterio_aprendizaje'],
            'Comunicación'  => ['criterio_comunicacion','criterio_presentacion'],
            'Puntualidad'   => ['criterio_puntualidad'],
            'Resultados'    => ['criterio_desarrollo','criterio_analisis_res','criterio_conclusiones','criterio_recomendacion'],
        ];
    ?>
    <div style="background:#faf5ff;border-left:4px solid #7c3aed;border-radius:10px;padding:14px;
                transition:box-shadow .2s;"
         onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.07)'"
         onmouseout="this.style.boxShadow='none'">

        <!-- Header evaluación -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div>
                <span style="font-size:.72rem;font-weight:800;color:#7c3aed;display:flex;align-items:center;gap:4px;">
                    <i class="ti ti-calendar-event" style="font-size:.85rem;"></i>
                    <?= htmlspecialchars($fechaEvFmt) ?>
                    <?php if ($ev->lapso_academico ?? null): ?>
                    <span style="background:#ede9fe;padding:1px 7px;border-radius:10px;font-size:.62rem;letter-spacing:.3px;">
                        <?= htmlspecialchars($ev->lapso_academico) ?>
                    </span>
                    <?php endif; ?>
                </span>
                <?php if ($ev->tutor_nombre ?? null): ?>
                <span style="font-size:.68rem;color:#64748b;display:flex;align-items:center;gap:3px;margin-top:2px;">
                    <i class="ti ti-user-check" style="font-size:.75rem;"></i>
                    <?= htmlspecialchars(trim($ev->tutor_nombre)) ?>
                </span>
                <?php endif; ?>
            </div>
            <!-- Promedio badge circular -->
            <div style="width:52px;height:52px;border-radius:50%;background:<?= $promBg ?>;
                        border:3px solid <?= $promColor ?>;display:flex;flex-direction:column;
                        align-items:center;justify-content:center;flex-shrink:0;">
                <span style="font-size:1.1rem;font-weight:900;color:<?= $promColor ?>;line-height:1;"><?= number_format($prom,1) ?></span>
                <span style="font-size:.52rem;font-weight:700;color:<?= $promColor ?>;opacity:.75;">/5</span>
            </div>
        </div>

        <!-- Barra de promedio -->
        <div style="height:6px;border-radius:20px;background:#ede9fe;overflow:hidden;margin-bottom:8px;">
            <div style="height:100%;border-radius:20px;background:<?= $promColor ?>;width:<?= $promPct ?>%;transition:width .8s ease;"></div>
        </div>

        <!-- Mini-radar de grupos -->
        <div class="eval-grupos-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;margin-bottom:8px;">
        <?php foreach ($criteriosGrupos as $grpNombre => $grpCriterios):
            $suma = 0; $cnt = 0;
            foreach ($grpCriterios as $cKey) {
                if (isset($ev->$cKey)) { $suma += (int)$ev->$cKey; $cnt++; }
            }
            $avg = $cnt > 0 ? round($suma/$cnt, 1) : 0;
            $grpPct = min(100, $avg / 5 * 100);
            $grpCol = $avg >= 4 ? '#16a34a' : ($avg >= 3 ? '#d97706' : '#dc2626');
        ?>
        <div style="background:white;border-radius:8px;padding:6px 8px;border:1px solid #ede9fe;">
            <div style="font-size:.6rem;font-weight:800;color:#64748b;margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px;">
                <?= htmlspecialchars($grpNombre) ?>
            </div>
            <div style="height:4px;border-radius:10px;background:#f1f5f9;overflow:hidden;">
                <div style="height:100%;border-radius:10px;background:<?= $grpCol ?>;width:<?= $grpPct ?>%;"></div>
            </div>
            <div style="font-size:.72rem;font-weight:800;color:<?= $grpCol ?>;margin-top:2px;"><?= number_format($avg,1) ?></div>
        </div>
        <?php endforeach; ?>
        </div>

        <?php if ($ev->observaciones ?? null): ?>
        <p style="margin:0;font-size:.75rem;color:#7c3aed;line-height:1.4;font-style:italic;border-top:1px solid #ede9fe;padding-top:8px;">
            <i class="ti ti-notes" style="font-size:.8rem;"></i>
            <?= htmlspecialchars(mb_strimwidth($ev->observaciones, 0, 100, '…')) ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</div><!-- /alm-row-jeval -->


</div><!-- /alm-wrap -->
</div><!-- /dashboard-container -->

<!-- ═══ TOOLTIP JS ════════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';
    const tooltip = document.getElementById('almTooltip');
    const ttFecha = document.getElementById('tt-fecha');
    const ttEst   = document.getElementById('tt-est');
    const ttDot   = document.getElementById('tt-dot');
    const ttExtra = document.getElementById('tt-extra');

    const diasSemana = ['','Lunes','Martes','Mi\u00e9rcoles','Jueves','Viernes','S\u00e1bado','Domingo'];
    const mesesEs    = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    function formatFecha(iso) {
        const [y,m,d] = iso.split('-').map(Number);
        const dt  = new Date(y,m-1,d);
        const dow = dt.getDay() || 7;
        return `${diasSemana[dow]} ${d} ${mesesEs[m]} ${y}`;
    }

    document.querySelectorAll('.alm-cell[data-f]').forEach(cell => {
        cell.addEventListener('mouseenter', e => {
            ttFecha.textContent    = formatFecha(cell.dataset.f);
            ttEst.textContent      = cell.dataset.est;
            ttDot.style.background = cell.dataset.color;
            const hint = cell.classList.contains('alm-cell-editable') ? '✏ Clic para editar' : '';
            const extraParts = [cell.dataset.extra, hint].filter(Boolean);
            const extraTxt = extraParts.join(' · ');
            ttExtra.textContent   = extraTxt;
            ttExtra.style.display = extraTxt ? 'block' : 'none';
            tooltip.classList.add('show');
            posTooltip(e);
        });
        cell.addEventListener('mousemove',  e => posTooltip(e));
        cell.addEventListener('mouseleave', () => tooltip.classList.remove('show'));
    });

    function posTooltip(e) {
        const x = e.clientX + 14, y = e.clientY - 10;
        const w = tooltip.offsetWidth, vw = window.innerWidth;
        tooltip.style.left = (x + w > vw ? x - w - 28 : x) + 'px';
        tooltip.style.top  = y + 'px';
    }

    // Inicializar slider móvil al mes actual
    <?php $mesActual = (int)date('n'); ?>
    window.currentAlmMonth = <?= $mesActual ?>;
    window.almSlideMes = function(delta) {
        let newMes = window.currentAlmMonth + delta;
        if (newMes < 1) newMes = 12;
        if (newMes > 12) newMes = 1;
        window.currentAlmMonth = newMes;

        document.querySelectorAll('.alm-mm-card').forEach(card => {
            card.classList.remove('active-mobile');
            if (parseInt(card.dataset.mes) === window.currentAlmMonth) {
                card.classList.add('active-mobile');
                document.getElementById('alm-mm-mes-lbl').textContent = card.dataset.nombre;
            }
        });
    };
    
    // Auto-correr al cargar para que siempre empiece en el mes que estamos (unificado PC y Móvil)
    setTimeout(() => almSlideMes(0), 50);

})();

/* ── Filtro Historial con DataTables (var para PJAX) ── */
var tableHistorial = null;
var fpHistorial = null;

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('histTable')) {
        tableHistorial = $('#histTable').DataTable({
            "pageLength": 5,
            "lengthMenu": [[5, 10, 20, 50, -1], [5, 10, 20, 50, "Todos"]],
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "No hay registros disponibles",
                "sInfo": "_START_–_END_ de _TOTAL_",
                "sInfoEmpty": "0 registros",
                "sInfoFiltered": "(de _MAX_ total)",
                "sSearch": "",
                "oPaginate": { "sNext": "›", "sPrevious": "‹" }
            },
            "order": [],
            "responsive": true,
            "dom": '<"hist-dt-top"l>t<"hist-dt-bottom"ip>'
        });

        // Inicializar Flatpickr si existe el field
        if (typeof flatpickr !== 'undefined' && document.getElementById('filtroFechaHistorial')) {
            fpHistorial = flatpickr("#filtroFechaHistorial", {
                mode: "range",
                dateFormat: "d/m/Y",
                locale: "es",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length > 0) {
                        document.getElementById('btnLimpiarFechaAlm').style.display = 'inline-block';
                    } else {
                        document.getElementById('btnLimpiarFechaAlm').style.display = 'none';
                    }
                    tableHistorial.draw();
                }
            });

            document.getElementById('btnLimpiarFechaAlm').addEventListener('click', function() {
                fpHistorial.clear();
                this.style.display = 'none';
                tableHistorial.draw();
            });

            // Extensión del buscador de DataTables para evaluar las fechas del Historial
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'histTable') return true;

                    if (!fpHistorial || fpHistorial.selectedDates.length === 0) {
                        return true;
                    }

                    var min = fpHistorial.selectedDates[0];
                    min.setHours(0, 0, 0, 0);

                    var max = null;
                    if (fpHistorial.selectedDates.length > 1) {
                        max = fpHistorial.selectedDates[1];
                        max.setHours(23, 59, 59, 999);
                    } else {
                        max = new Date(min.getTime());
                        max.setHours(23, 59, 59, 999);
                    }

                    // Extraer fecha del texto: "13 Ene 2026" (columna 0)
                    var str = data[0].trim();
                    var partes = str.split(' ');
                    if (partes.length < 3) return true;
                    
                    var dia = parseInt(partes[0], 10);
                    var mesesList = {'Ene':0,'Feb':1,'Mar':2,'Abr':3,'May':4,'Jun':5,'Jul':6,'Ago':7,'Sep':8,'Oct':9,'Nov':10,'Dic':11};
                    var mes = mesesList[partes[1]] ?? 0;
                    var anio = parseInt(partes[2], 10);
                    
                    var rowDate = new Date(anio, mes, dia);
                    return rowDate >= min && rowDate <= max;
                }
            );
        }
    }
});

function filtrarHistorial(estado, pill) {
    // Resetear pills
    document.querySelectorAll('#histFilters .hist-pill').forEach(p => {
        p.dataset.active = '0';
        p.style.borderColor = 'transparent';
        p.style.fontWeight  = '700';
    });
    // Activar pill clicado
    if (pill) {
        pill.dataset.active   = '1';
        pill.style.borderColor = 'currentColor';
    }

    if (tableHistorial) {
        if (estado === 'todos') {
            tableHistorial.column(2).search('').draw();
        } else {
            tableHistorial.column(2).search(estado).draw();
        }
    }
}
</script>

<?php if (in_array($viewerRole, [0, 1])): ?>
<script>
/* ── Editor de celdas almanaque ── */
(function () {
    'use strict';

    var PASANTE_ID   = <?= (int)($pasante->id ?? 0) ?>;
    var ESTADO_LABEL = { P: 'Presente', A: 'Ausente', J: 'Justificado' };
    var ESTADO_COLOR = { P: '#16a34a',  A: '#dc2626',  J: '#2563eb' };
    var ESTADO_EST   = { P: 'Presente', A: 'Ausente',  J: 'Justificado' };

    var hoy = new Date(); hoy.setHours(0, 0, 0, 0);

    document.querySelectorAll('.alm-cell[data-f]:not(.is-empty)').forEach(function (cell) {
        var e = cell.dataset.e;
        if (['P','A','J','sin_dato','feriado'].indexOf(e) === -1) return;
        var p = cell.dataset.f.split('-').map(Number);
        if (new Date(p[0], p[1] - 1, p[2]) >= hoy) return;
        cell.classList.add('alm-cell-editable');
        cell.addEventListener('click', function (ev) { ev.stopPropagation(); abrirEditorCelda(this); });
    });

    function formatFechaEs(iso) {
        var dias  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        var meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        var p  = iso.split('-').map(Number);
        var dt = new Date(p[0], p[1] - 1, p[2]);
        return dias[dt.getDay()] + ' ' + p[2] + ' ' + meses[p[1]] + ' ' + p[0];
    }

    function btnStyle(k, sel) {
        var cfgs = {
            P: { bg: '#dcfce7', color: '#15803d', border: '#16a34a' },
            A: { bg: '#fee2e2', color: '#b91c1c', border: '#dc2626' },
            J: { bg: '#dbeafe', color: '#1d4ed8', border: '#2563eb' }
        };
        return k === sel
            ? 'background:' + cfgs[k].bg + ';color:' + cfgs[k].color + ';border-color:' + cfgs[k].border + ';'
            : 'background:#f8fafc;color:#64748b;border-color:#e2e8f0;';
    }

    async function abrirEditorCelda(cell) {
        var fecha     = cell.dataset.f;
        var estadoAct = cell.dataset.e;
        var ini       = estadoAct === 'P' ? 'P' : estadoAct === 'J' ? 'J' : 'A';

        var BASE = 'flex:1;padding:10px 8px;border-radius:10px;border:2px solid;cursor:pointer;font-weight:700;font-size:.85rem;';
        var html = '<div style="font-size:.85rem;color:#64748b;margin-bottom:14px;">'
            + '<i class="ti ti-calendar" style="margin-right:4px;"></i>'
            + '<strong style="color:#1e293b;">' + formatFechaEs(fecha) + '</strong></div>'
            + '<div style="display:flex;gap:8px;justify-content:center;margin-bottom:16px;">'
            + '<button type="button" id="almBtn_P" onclick="window.almSelEstado(\'P\',this)" style="' + BASE + btnStyle('P', ini) + '">'
            + '<i class="ti ti-check"></i><br>Presente</button>'
            + '<button type="button" id="almBtn_A" onclick="window.almSelEstado(\'A\',this)" style="' + BASE + btnStyle('A', ini) + '">'
            + '<i class="ti ti-x"></i><br>Ausente</button>'
            + '<button type="button" id="almBtn_J" onclick="window.almSelEstado(\'J\',this)" style="' + BASE + btnStyle('J', ini) + '">'
            + '<i class="ti ti-file-certificate"></i><br>Justificado</button>'
            + '</div>'
            + '<div id="almMotivoWrap" style="display:' + (ini === 'J' ? 'block' : 'none') + ';">'
            + '<textarea id="almMotivoTxt" placeholder="Motivo de la justificación…"'
            + ' style="width:100%;box-sizing:border-box;border:1.5px solid #e2e8f0;border-radius:10px;'
            + 'padding:10px 12px;font-size:.85rem;resize:vertical;min-height:72px;outline:none;"'
            + ' onfocus="this.style.borderColor=\'#2563eb\'" onblur="this.style.borderColor=\'#e2e8f0\'"></textarea>'
            + '</div>';

        window._almEstadoSel = ini;

        var result = await Swal.fire({
            title:              '<span style="font-size:1rem;font-weight:700;color:#1e293b;">Editar Asistencia</span>',
            html:               html,
            width:              380,
            showCancelButton:   true,
            confirmButtonText:  '<i class="ti ti-device-floppy"></i> Guardar',
            cancelButtonText:   'Cancelar',
            confirmButtonColor: '#7c3aed',
            cancelButtonColor:  '#94a3b8',
            reverseButtons:     true,
            focusConfirm:       false,
            preConfirm: function () {
                var est    = window._almEstadoSel;
                var motivo = (document.getElementById('almMotivoTxt') || {}).value || '';
                if (est === 'J' && !motivo.trim()) {
                    Swal.showValidationMessage('El motivo es obligatorio para Justificado.');
                    return false;
                }
                return { estado: est, motivo: motivo.trim() };
            }
        });

        if (!result.isConfirmed || !result.value) return;

        var est    = result.value.estado;
        var motivo = result.value.motivo;
        var fd     = new FormData();
        fd.append('pasante_id', PASANTE_ID);
        fd.append('fecha',      fecha);
        fd.append('estado',     ESTADO_LABEL[est]);
        if (est === 'J') fd.append('motivo_justificacion', motivo);

        try {
            var resp = await fetch(URLROOT + '/asistencias/registro_manual', { method: 'POST', body: fd });
            var data = await resp.json();
            if (data.success) {
                cell.dataset.e     = est;
                cell.dataset.est   = ESTADO_EST[est];
                cell.dataset.color = ESTADO_COLOR[est];
                cell.dataset.extra = '';
                cell.dataset.af    = '0';
                Swal.fire({ icon: 'success', title: 'Guardado', text: data.message, timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#dc2626' });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo guardar.', confirmButtonColor: '#dc2626' });
        }
    }

    window.almSelEstado = function (val) {
        window._almEstadoSel = val;
        var cfgs = {
            P: { bg: '#dcfce7', color: '#15803d', border: '#16a34a' },
            A: { bg: '#fee2e2', color: '#b91c1c', border: '#dc2626' },
            J: { bg: '#dbeafe', color: '#1d4ed8', border: '#2563eb' }
        };
        ['P', 'A', 'J'].forEach(function (k) {
            var b = document.getElementById('almBtn_' + k);
            if (!b) return;
            if (k === val) {
                b.style.background  = cfgs[k].bg;
                b.style.color       = cfgs[k].color;
                b.style.borderColor = cfgs[k].border;
            } else {
                b.style.background  = '#f8fafc';
                b.style.color       = '#64748b';
                b.style.borderColor = '#e2e8f0';
            }
        });
        var wrap = document.getElementById('almMotivoWrap');
        if (wrap) wrap.style.display = val === 'J' ? 'block' : 'none';
    };
})();
</script>
<?php endif; ?>

<?php
// ── Bitácora de Actividades (visible para roles 0, 1, 2) ────────────────────
$actividadesPasante = $data['actividadesPasante'] ?? [];
$pasanteIdVista = (int)($pasante->id ?? 0);

// Agrupar por mes_key
$actGrupos = [];
foreach ($actividadesPasante as $a) {
    $mk = $a->mes_key ?? '';
    if (!isset($actGrupos[$mk])) {
        $actGrupos[$mk] = ['label' => $a->mes_label ?? $mk, 'items' => []];
    }
    $actGrupos[$mk]['items'][] = $a;
}

// Meses únicos para el select filtro
$mesesActOpts = [];
foreach ($actGrupos as $mk => $g) {
    $mesesActOpts[$mk] = $g['label'];
}
?>

<!-- ═══ GRID: ACTIVIDADES + EXÁMENES ═══════════════════════════════════════ -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;align-items:start;">

<!-- ── Bitácora de Actividades (mitad izquierda) ── -->
<div class="alm-card" id="almActividadesSection"><?php // wrapper mitad izquierda ?>
    <div class="alm-card-title" style="margin-bottom:14px;">
        <i class="ti ti-notebook" style="color:#2563eb;"></i>
        Bitácora de Actividades
        <span style="background:#dbeafe;color:#1d4ed8;font-size:.68rem;padding:2px 10px;border-radius:20px;font-weight:700;text-transform:none;letter-spacing:0;" id="almActCount">
            <?= count($actividadesPasante) ?> actividad<?= count($actividadesPasante) !== 1 ? 'es' : '' ?>
        </span>
    </div>

    <!-- Barra de filtros -->
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center;">
        <div style="position:relative;flex:1;min-width:180px;">
            <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.95rem;pointer-events:none;"></i>
            <input type="text" id="almActSearch" placeholder="Buscar actividad…"
                   oninput="almFiltrarAct()"
                   style="width:100%;padding:9px 12px 9px 36px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.85rem;outline:none;box-sizing:border-box;transition:border .2s;"
                   onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
        </div>
        <select id="almActMes" onchange="almFiltrarAct()"
                style="padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.85rem;outline:none;background:#fff;cursor:pointer;min-width:140px;transition:border .2s;"
                onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">
            <option value="">Todos los meses</option>
            <?php foreach ($mesesActOpts as $mk => $lbl): ?>
            <option value="<?= htmlspecialchars($mk) ?>"><?= htmlspecialchars($lbl) ?></option>
            <?php endforeach; ?>
        </select>
        <button onclick="almResetAct()" title="Limpiar filtros"
                style="padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:10px;background:#f8fafc;font-size:.85rem;cursor:pointer;color:#64748b;display:flex;align-items:center;gap:6px;transition:all .15s;"
                onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
            <i class="ti ti-x"></i> Limpiar
        </button>
    </div>

    <!-- Estado vacío -->
    <?php if (empty($actividadesPasante)): ?>
    <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
        <i class="ti ti-notebook-off" style="font-size:2.8rem;display:block;margin-bottom:12px;opacity:.25;"></i>
        <p style="margin:0;font-weight:700;font-size:.9rem;color:#64748b;">Sin actividades registradas</p>
        <p style="margin:6px 0 0;font-size:.78rem;">El pasante aún no ha registrado actividades en este período.</p>
    </div>
    <?php else: ?>

    <!-- Sin resultados (filtro) -->
    <div id="almActNoResults" style="display:none;text-align:center;padding:32px;color:#94a3b8;">
        <i class="ti ti-search-off" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.3;"></i>
        <p style="margin:0;font-size:.88rem;font-weight:600;">Sin coincidencias</p>
        <p style="margin:4px 0 0;font-size:.76rem;">Prueba con otra búsqueda o mes.</p>
    </div>

    <!-- Grupos por mes -->
    <div id="almActLista" style="display:flex;flex-direction:column;gap:20px;">
    <?php foreach ($actGrupos as $mesKey => $grupo): ?>
    <div class="alm-act-grupo" data-grupo="<?= htmlspecialchars($mesKey) ?>">
        <!-- Cabecera mes -->
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div style="font-size:.72rem;font-weight:900;color:#1e3a8a;text-transform:uppercase;letter-spacing:.8px;
                        background:#dbeafe;padding:3px 12px;border-radius:20px;">
                <?= htmlspecialchars($grupo['label']) ?>
            </div>
            <div style="flex:1;height:1px;background:#e2e8f0;"></div>
            <span style="font-size:.7rem;color:#94a3b8;"><?= count($grupo['items']) ?> act.</span>
        </div>
        <!-- Cards del mes -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
        <?php foreach ($grupo['items'] as $act): ?>
        <div class="alm-act-card"
             id="alm-act-card-<?= $act->id ?>"
             data-titulo="<?= htmlspecialchars(mb_strtolower($act->titulo ?? '', 'UTF-8')) ?>"
             data-desc="<?= htmlspecialchars(mb_strtolower($act->descripcion ?? '', 'UTF-8')) ?>"
             data-mes="<?= htmlspecialchars($act->mes_key ?? '') ?>"
             style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid <?= !empty($act->correccion) ? '#d97706' : '#2563eb' ?>;
                    border-radius:12px;padding:14px 16px;transition:box-shadow .2s,transform .15s;">
            <!-- Fecha + acciones -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <div style="font-size:.7rem;font-weight:800;color:#2563eb;display:flex;align-items:center;gap:5px;">
                    <i class="ti ti-calendar-event" style="font-size:.85rem;"></i>
                    <?= htmlspecialchars($act->fecha_fmt ?? '') ?>
                </div>
                <?php if (in_array($viewerRole, [0,1,2])): ?>
                <div style="display:flex;gap:4px;">
                    <button onclick="almAbrirCorreccion(<?= $act->id ?>,<?= htmlspecialchars(json_encode($act->correccion ?? '')) ?>)"
                            title="Agregar / editar corrección"
                            style="background:#fef3c7;color:#92400e;border:none;border-radius:7px;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:background .15s;"
                            onmouseover="this.style.background='#fde68a'" onmouseout="this.style.background='#fef3c7'">
                        <i class="ti ti-edit"></i>
                    </button>
                    <?php if (in_array($viewerRole, [0,1])): ?>
                    <button onclick="almEliminarActividad(<?= $act->id ?>)"
                            title="Eliminar actividad"
                            style="background:#fee2e2;color:#991b1b;border:none;border-radius:7px;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:background .15s;"
                            onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                        <i class="ti ti-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div style="font-size:.88rem;font-weight:700;color:#0f172a;margin-bottom:6px;line-height:1.3;">
                <?= htmlspecialchars($act->titulo ?? '') ?>
            </div>
            <?php if (!empty($act->descripcion)): ?>
            <div style="font-size:.78rem;color:#475569;line-height:1.55;border-top:1px solid #e2e8f0;padding-top:8px;margin-top:2px;">
                <?= nl2br(htmlspecialchars($act->descripcion)) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($act->correccion)): ?>
            <div style="margin-top:10px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #d97706;border-radius:8px;padding:10px 12px;">
                <div style="font-size:.68rem;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;display:flex;align-items:center;gap:5px;">
                    <i class="ti ti-edit" style="font-size:.8rem;"></i>
                    Corrección<?php if (!empty($act->corrector_nombre)): ?> — <?= htmlspecialchars($act->corrector_nombre) ?><?php endif; ?>
                </div>
                <div style="font-size:.78rem;color:#78350f;line-height:1.55;">
                    <?= nl2br(htmlspecialchars($act->correccion)) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div><!-- /mitad izquierda actividades -->

<!-- ── Resultados de Exámenes (mitad derecha) ── -->
<?php $examenesResultados = $examenesResultados ?? []; ?>
<div class="alm-card" style="height:100%;">
    <div class="alm-card-title" style="margin-bottom:16px;">
        <i class="ti ti-file-certificate" style="color:#7c3aed;"></i>
        Exámenes Aplicados
        <span style="background:#ede9fe;color:#6d28d9;font-size:.68rem;padding:2px 10px;border-radius:20px;font-weight:700;text-transform:none;letter-spacing:0;">
            <?= count($examenesResultados) ?> resultado<?= count($examenesResultados) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($examenesResultados)): ?>
    <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
        <i class="ti ti-file-off" style="font-size:2.8rem;display:block;margin-bottom:12px;opacity:.25;"></i>
        <p style="margin:0;font-weight:700;font-size:.9rem;color:#64748b;">Sin exámenes aplicados</p>
        <p style="margin:6px 0 0;font-size:.78rem;">El pasante aún no ha tomado ningún examen.</p>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;max-height:420px;overflow-y:auto;padding-right:4px;">
        <?php foreach ($examenesResultados as $ex):
            // Usar porcentaje ya calculado en BD; fallback a puntaje_obtenido / puntaje_maximo
            $pct = min(100, (float)($ex->porcentaje ?? (
                $ex->puntaje_maximo > 0
                    ? round(($ex->puntaje_obtenido / $ex->puntaje_maximo) * 100, 1)
                    : 0
            )));
            if ($pct >= 60)      { $colBg='#dcfce7'; $colTxt='#16a34a'; $colBar='#22c55e'; $ico='ti-circle-check'; $lbl='Aprobado'; }
            elseif ($pct >= 40)  { $colBg='#fef3c7'; $colTxt='#d97706'; $colBar='#f59e0b'; $ico='ti-alert-triangle'; $lbl='Cerca'; }
            else                 { $colBg='#fee2e2'; $colTxt='#dc2626'; $colBar='#ef4444'; $ico='ti-circle-x'; $lbl='Reprobado'; }
        ?>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;">
            <!-- Título y badge -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px;">
                <div style="font-size:.85rem;font-weight:700;color:#1e293b;line-height:1.3;flex:1;">
                    <i class="ti ti-file-text" style="color:#7c3aed;margin-right:4px;font-size:.8rem;"></i>
                    <?= htmlspecialchars($ex->titulo) ?>
                </div>
                <span style="display:inline-flex;align-items:center;gap:4px;background:<?= $colBg ?>;color:<?= $colTxt ?>;border-radius:20px;padding:3px 9px;font-size:.65rem;font-weight:800;white-space:nowrap;flex-shrink:0;">
                    <i class="ti <?= $ico ?>" style="font-size:.7rem;"></i> <?= $lbl ?>
                </span>
            </div>
            <!-- Puntaje + barra -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <div style="font-size:1.4rem;font-weight:800;color:<?= $colTxt ?>;"><?= number_format((float)$ex->puntaje_obtenido, 1) ?><span style="font-size:.8rem;font-weight:500;color:#94a3b8;"> / <?= (float)$ex->puntaje_maximo ?></span></div>
                <div style="flex:1;">
                    <div style="height:7px;background:#e2e8f0;border-radius:4px;overflow:hidden;">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= $colBar ?>;border-radius:4px;transition:width .4s;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:.62rem;color:#94a3b8;margin-top:3px;">
                        <span>0</span>
                        <span style="color:<?= $colTxt ?>;font-weight:700;"><?= $pct ?>%</span>
                        <span>100</span>
                    </div>
                </div>
            </div>
            <!-- Fecha -->
            <div style="font-size:.7rem;color:#94a3b8;display:flex;align-items:center;gap:4px;">
                <i class="ti ti-calendar" style="font-size:.72rem;"></i>
                <?= htmlspecialchars($ex->fecha_fmt) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div><!-- /mitad derecha exámenes -->

</div><!-- /grid actividades+exámenes -->

<script>
(function(){
    function almFiltrarAct() {
        var search = (document.getElementById('almActSearch')?.value ?? '').toLowerCase().trim();
        var mes    = document.getElementById('almActMes')?.value ?? '';
        var visibles = 0;

        document.querySelectorAll('.alm-act-grupo').forEach(function(grupo) {
            var grupoMes = grupo.dataset.grupo ?? '';
            var grupoVis = false;

            grupo.querySelectorAll('.alm-act-card').forEach(function(card) {
                var okSearch = !search || card.dataset.titulo.includes(search) || card.dataset.desc.includes(search);
                var okMes    = !mes    || card.dataset.mes === mes;
                var show = okSearch && okMes;
                card.style.display = show ? '' : 'none';
                if (show) { grupoVis = true; visibles++; }
            });

            grupo.style.display = grupoVis ? '' : 'none';
        });

        var noRes = document.getElementById('almActNoResults');
        var lista = document.getElementById('almActLista');
        if (noRes && lista) {
            noRes.style.display  = visibles === 0 ? 'block' : 'none';
            lista.style.display  = visibles === 0 ? 'none'  : '';
        }
        var ctr = document.getElementById('almActCount');
        if (ctr) {
            ctr.textContent = visibles + ' actividad' + (visibles !== 1 ? 'es' : '');
        }
    }

    function almResetAct() {
        var s = document.getElementById('almActSearch');
        var m = document.getElementById('almActMes');
        if (s) s.value = '';
        if (m) m.value = '';
        almFiltrarAct();
    }

    window.almFiltrarAct = almFiltrarAct;
    window.almResetAct   = almResetAct;
})();
</script>

<?php if (in_array($viewerRole, [0,1,2])): ?>
<!-- ═══ MODAL CORRECCIÓN DE ACTIVIDAD ════════════════════════════════════════ -->
<div id="almCorrModal" style="display:none;position:fixed;inset:0;z-index:9100;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:22px;width:100%;max-width:500px;box-shadow:0 24px 80px rgba(0,0,0,.2);overflow:hidden;">
        <div style="background:linear-gradient(135deg,#92400e,#d97706);padding:22px 26px;display:flex;align-items:center;gap:14px;">
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:10px;">
                <i class="ti ti-edit" style="font-size:1.4rem;color:#fff;"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:.7rem;color:rgba(255,255,255,.7);font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Revisión de actividad</div>
                <div style="font-size:1rem;font-weight:800;color:#fff;">Agregar corrección</div>
            </div>
            <button onclick="almCerrarCorreccion()" style="background:rgba(255,255,255,.15);border:none;border-radius:10px;width:34px;height:34px;cursor:pointer;color:#fff;font-size:1.1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div style="padding:24px 26px;">
            <input type="hidden" id="almCorrActId" value="">
            <label style="display:block;font-size:.78rem;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px;">
                <i class="ti ti-pencil" style="margin-right:4px;"></i>Corrección / comentario
            </label>
            <textarea id="almCorrTexto" rows="5"
                      placeholder="Escribe aquí la corrección o comentario para el pasante…"
                      style="width:100%;border:1.5px solid #e2e8f0;border-radius:10px;padding:11px 14px;font-size:.88rem;color:#1e293b;outline:none;resize:vertical;font-family:inherit;box-sizing:border-box;transition:border-color .2s;"
                      onfocus="this.style.borderColor='#d97706'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
            <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;text-align:right;"><span id="almCorrLen">0</span>/1000</div>
        </div>
        <div style="padding:0 26px 22px;display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="almGuardarCorreccionVacia()" title="Quitar corrección existente"
                    style="background:#f1f5f9;color:#64748b;border:none;border-radius:10px;padding:9px 16px;font-size:.82rem;font-weight:600;cursor:pointer;">
                Quitar corrección
            </button>
            <button onclick="almCerrarCorreccion()"
                    style="background:#f1f5f9;color:#64748b;border:none;border-radius:10px;padding:9px 16px;font-size:.82rem;font-weight:600;cursor:pointer;">
                Cancelar
            </button>
            <button onclick="almGuardarCorreccion()" id="almCorrBtn"
                    style="background:linear-gradient(135deg,#92400e,#d97706);color:#fff;border:none;border-radius:10px;padding:9px 20px;font-size:.88rem;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(217,119,6,.3);">
                <i class="ti ti-device-floppy" style="margin-right:5px;"></i>Guardar
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    var URLROOT = '<?= URLROOT ?>';

    function almAbrirCorreccion(id, corrActual) {
        document.getElementById('almCorrActId').value = id;
        var txt = document.getElementById('almCorrTexto');
        txt.value = corrActual || '';
        document.getElementById('almCorrLen').textContent = txt.value.length;
        var modal = document.getElementById('almCorrModal');
        modal.style.display = 'flex';
        setTimeout(() => txt.focus(), 80);
    }

    function almCerrarCorreccion() {
        document.getElementById('almCorrModal').style.display = 'none';
    }

    document.getElementById('almCorrTexto').addEventListener('input', function(){
        document.getElementById('almCorrLen').textContent = this.value.length;
    });

    function almGuardarCorreccion() {
        var id   = document.getElementById('almCorrActId').value;
        var corr = document.getElementById('almCorrTexto').value.trim();
        if (!corr) {
            Swal.fire({icon:'warning', title:'Campo vacío', text:'Escribe una corrección o usa "Quitar corrección".', confirmButtonColor:'#d97706'});
            return;
        }
        _almEnviarCorreccion(id, corr);
    }

    function almGuardarCorreccionVacia() {
        var id = document.getElementById('almCorrActId').value;
        Swal.fire({
            icon:'question', title:'¿Quitar corrección?',
            text:'Se eliminará la corrección de esta actividad.',
            showCancelButton:true, confirmButtonText:'Sí, quitar',
            confirmButtonColor:'#64748b', cancelButtonColor:'#d97706'
        }).then(function(r){ if (r.isConfirmed) _almEnviarCorreccion(id, ''); });
    }

    function _almEnviarCorreccion(id, corr) {
        var btn = document.getElementById('almCorrBtn');
        btn.disabled = true;

        var csrfMetaCorr = document.querySelector('meta[name="csrf-token"]');
        fetch(URLROOT + '/pasante/corregirActividad', {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'X-CSRF-TOKEN': csrfMetaCorr ? csrfMetaCorr.getAttribute('content') : ''},
            body: JSON.stringify({id: parseInt(id), correccion: corr})
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data.success) {
                almCerrarCorreccion();
                Swal.fire({
                    icon:'success', title:'Guardado',
                    text: data.message,
                    confirmButtonColor:'#d97706',
                    timer:1600, showConfirmButton:false
                }).then(function(){ location.reload(); });
            } else {
                Swal.fire({icon:'error', title:'Error', text:data.message, confirmButtonColor:'#d97706'});
                btn.disabled = false;
            }
        })
        .catch(function(){
            Swal.fire({icon:'error', title:'Error de red', text:'No se pudo conectar.', confirmButtonColor:'#d97706'});
            btn.disabled = false;
        });
    }

    function almEliminarActividad(id) {
        Swal.fire({
            icon:'warning', title:'¿Eliminar actividad?',
            text:'Esta acción no se puede deshacer.',
            showCancelButton:true, confirmButtonText:'Sí, eliminar',
            cancelButtonText:'Cancelar',
            confirmButtonColor:'#dc2626', cancelButtonColor:'#64748b'
        }).then(function(result){
            if (!result.isConfirmed) return;

            fetch(URLROOT + '/pasante/eliminarActividadAdmin', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({id: id})
            })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.success) {
                    var card = document.getElementById('alm-act-card-' + id);
                    if (card) {
                        card.style.transition = 'opacity .3s,transform .3s';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(.95)';
                        setTimeout(function(){ card.remove(); }, 300);
                    }
                } else {
                    Swal.fire({icon:'error', title:'Error', text:data.message, confirmButtonColor:'#dc2626'});
                }
            })
            .catch(function(){
                Swal.fire({icon:'error', title:'Error de red', text:'No se pudo conectar.', confirmButtonColor:'#dc2626'});
            });
        });
    }

    // ── Cambiar Ausente → Presente ────────────────────────────────
    const _CSRF_AUSENTE = '<?= Session::generateCsrfToken() ?>';
    window.cambiarAPresente = async function(asistenciaId, fechaFmt, btn) {
        if (!asistenciaId) return;
        const confirm = await Swal.fire({
            title: '¿Cambiar a Presente?',
            html: `<p style="font-size:.9rem;color:#475569;">Se marcará el día <strong>${fechaFmt}</strong> como <strong style="color:#15803d;">Presente</strong> y se contabilizarán <strong>8h</strong>.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-check"></i> Sí, cambiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#16a34a',
        });
        if (!confirm.isConfirmed) return;

        try {
            const r = await fetch(URLROOT + '/asistencias/corregirAusente', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': _CSRF_AUSENTE },
                body: JSON.stringify({ asistencia_id: asistenciaId, csrf_token: _CSRF_AUSENTE })
            });
            const d = await r.json();
            if (d.success && d.afectados > 0) {
                // Actualizar fila visualmente sin recargar
                const tr = btn.closest('tr');
                tr.classList.remove('hist-ausente');
                tr.setAttribute('data-estado', 'Presente');
                // Badge
                const badge = tr.querySelector('.hist-badge');
                if (badge) {
                    badge.style.background = '#dcfce7';
                    badge.style.color = '#15803d';
                    badge.innerHTML = '<i class="ti ti-check" style="font-size:.75rem;"></i> Presente';
                }
                // Reemplazar botón por guión
                const tdAccion = btn.parentElement;
                tdAccion.innerHTML = '<span style="color:#e2e8f0;font-size:.75rem;">—</span>';
                Swal.fire({ icon: 'success', title: 'Registro actualizado', timer: 1400, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'warning', title: 'Sin cambios', text: d.message || 'El registro no pudo actualizarse.', confirmButtonColor: '#d97706' });
            }
        } catch(e) {
            Swal.fire({ icon: 'error', title: 'Error de red', text: e.message });
        }
    };

    window.almAbrirCorreccion   = almAbrirCorreccion;
    window.almEliminarActividad = almEliminarActividad;
    window.almGuardarCorreccion = almGuardarCorreccion;
    window.almGuardarCorreccionVacia = almGuardarCorreccionVacia;
    window.almCerrarCorreccion  = almCerrarCorreccion;
})();
</script>
<?php endif; ?>

<?php if (in_array($viewerRole, [0, 1])): ?>
<!-- ══ Modal Rellenar Asistencia (SuperAdmin/Admin) ══════════════════════ -->
<div id="modalRellenarAsist" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:#fff;border-radius:20px;padding:30px 28px;width:100%;max-width:460px;box-shadow:0 25px 60px rgba(0,0,0,.18);margin:16px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#7c3aed,#6d28d9);display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-wand" style="color:white;font-size:1.2rem;"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:1.05rem;font-weight:800;color:#1e293b;">Rellenar Asistencia Completa</h3>
                <p style="margin:2px 0 0;font-size:.78rem;color:#64748b;">Solo visible para SuperAdmin y Administrador</p>
            </div>
            <button onclick="cerrarRellenarAsistencia()" style="margin-left:auto;background:none;border:none;font-size:1.4rem;color:#94a3b8;cursor:pointer;line-height:1;">×</button>
        </div>

        <div style="background:#faf5ff;border:1px solid #ddd6fe;border-radius:12px;padding:14px 16px;margin-bottom:20px;font-size:.82rem;color:#6d28d9;line-height:1.7;">
            <strong><i class="ti ti-info-circle"></i> ¿Qué hace esto?</strong><br>
            Marca como <strong>Presente (Admin)</strong> todos los días hábiles L-V del rango seleccionado que <u>aún no tengan registro</u>. Los días ya registrados no se modifican.<br>
            <span style="color:#dc2626;font-weight:700;"><i class="ti ti-calendar-off"></i> Solo aplica a días pasados hasta hoy — las fechas futuras son ignoradas automáticamente.</span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:12px;">
            <div>
                <label style="font-size:.8rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Desde</label>
                <input type="date" id="rellFechaInicio" class="input-modern" style="font-size:.88rem;" max="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label style="font-size:.8rem;font-weight:700;color:#374151;display:block;margin-bottom:6px;">Hasta</label>
<?php
// El máximo permitido es el menor entre hoy y la fecha_fin_estimada del pasante.
// Evita que el admin rellene más allá de cuando realmente terminó la pasantía.
$_rellMaxFechaFin = min(
    date('Y-m-d'),
    $pasante->fecha_fin_estimada ?? $pasante->fecha_fin ?? date('Y-m-d')
);
?>
                <input type="date" id="rellFechaFin" class="input-modern" style="font-size:.88rem;"
                       max="<?= htmlspecialchars($_rellMaxFechaFin) ?>">
            </div>
        </div>

        <!-- Aviso dinámico si la fecha fin supera el límite permitido -->
        <div id="rellAvisoFuturo" style="display:none;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:.78rem;color:#92400e;">
            <i class="ti ti-alert-triangle"></i> La fecha fin fue ajustada automáticamente al límite de la pasantía: <strong><?= date('d/m/Y', strtotime($_rellMaxFechaFin)) ?></strong>.
        </div>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;margin-bottom:20px;font-size:.78rem;color:#475569;">
            <i class="ti ti-calendar-stats"></i> Los feriados registrados en el sistema serán excluidos automáticamente.
        </div>

        <div style="display:flex;gap:10px;">
            <button onclick="cerrarRellenarAsistencia()" style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#64748b;font-weight:600;cursor:pointer;font-size:.88rem;">Cancelar</button>
            <button onclick="ejecutarRellenarAsistencia()" style="flex:2;padding:11px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:white;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:8px;">
                <i class="ti ti-wand"></i> Rellenar ahora
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    const _CSRF_ALM = '<?= Session::generateCsrfToken() ?>';
    const _PASANTE_ID = <?= (int)($pasante->id ?? 0) ?>;
    const _FECHA_INICIO_DEF = '<?= substr($pasante->fecha_inicio_pasantia ?? $pasante->fecha_inicio ?? date('Y-m-d'), 0, 10) ?>';
    const _FECHA_FIN_DEF    = '<?= substr($pasante->fecha_fin_estimada   ?? $pasante->fecha_fin   ?? date('Y-m-d'), 0, 10) ?>';

    const _HOY = new Date().toISOString().slice(0, 10);

    // Límite real: el menor entre hoy y la fecha_fin_estimada del pasante
    const _RELLENA_MAX = _FECHA_FIN_DEF < _HOY ? _FECHA_FIN_DEF : _HOY;

    window.abrirRellenarAsistencia = function() {
        const avisoEl  = document.getElementById('rellAvisoFuturo');
        const ffDefault = _FECHA_FIN_DEF > _HOY ? _HOY : _FECHA_FIN_DEF;
        const fiDefault = _FECHA_INICIO_DEF > _HOY ? _HOY : _FECHA_INICIO_DEF;
        document.getElementById('rellFechaInicio').value = fiDefault;
        document.getElementById('rellFechaFin').value    = ffDefault;
        avisoEl.style.display = (_FECHA_FIN_DEF > _HOY) ? 'block' : 'none';
        document.getElementById('modalRellenarAsist').style.display = 'flex';
    };

    // Forzar límite tanto en hoy como en fecha_fin_estimada al cambiar manualmente
    ['rellFechaInicio','rellFechaFin'].forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            const ff = document.getElementById('rellFechaFin').value;
            if (ff && ff > _RELLENA_MAX) {
                document.getElementById('rellFechaFin').value = _RELLENA_MAX;
                document.getElementById('rellAvisoFuturo').style.display = 'block';
            } else {
                document.getElementById('rellAvisoFuturo').style.display = 'none';
            }
        });
    });

    window.cerrarRellenarAsistencia = function() {
        document.getElementById('modalRellenarAsist').style.display = 'none';
    };

    window.ejecutarRellenarAsistencia = async function() {
        const fi = document.getElementById('rellFechaInicio').value;
        const ff = document.getElementById('rellFechaFin').value;
        if (!fi || !ff || ff < fi) {
            Swal.fire({ icon:'warning', title:'Fechas inválidas', text:'Verifica que las fechas sean correctas.', confirmButtonColor:'#7c3aed' });
            return;
        }
        // Mostrar fecha efectiva (el backend también corta en fecha_fin_estimada y hoy)
        const ffEfectivo = ff > _RELLENA_MAX ? _RELLENA_MAX : ff;
        const confirm = await Swal.fire({
            title: '¿Rellenar asistencia?',
            html: `<p style="font-size:.9rem;color:#475569;">
                       Se marcarán como <strong>Presente</strong> todos los días hábiles sin registro entre<br>
                       <strong>${fi}</strong> y <strong>${ffEfectivo}</strong>.
                       ${ffEfectivo !== ff ? '<br><span style="color:#d97706;font-size:.82rem;">⚠ Rango ajustado a hoy (sin fechas futuras).</span>' : ''}
                   </p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-wand"></i> Sí, rellenar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#7c3aed',
        });
        if (!confirm.isConfirmed) return;

        Swal.fire({ title: 'Procesando...', text: 'Rellenando días hábiles...', didOpen: () => Swal.showLoading(), allowOutsideClick: false });

        try {
            const r = await fetch('<?= URLROOT ?>/asistencias/rellenarAdmin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': _CSRF_ALM },
                body: JSON.stringify({ pasante_id: _PASANTE_ID, fecha_inicio: fi, fecha_fin: ff, csrf_token: _CSRF_ALM })
            });
            const d = await r.json();
            if (d.success) {
                cerrarRellenarAsistencia();
                await Swal.fire({
                    icon: 'success',
                    title: `${d.insertados} día(s) rellenados`,
                    text: `${d.omitidos} día(s) ya tenían registro y no fueron modificados.`,
                    confirmButtonColor: '#7c3aed'
                });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: d.message, confirmButtonColor: '#dc2626' });
            }
        } catch(e) {
            Swal.fire({ icon: 'error', title: 'Error de red', text: e.message, confirmButtonColor: '#dc2626' });
        }
    };

    // ── Ajustar fechas individuales de la pasantía (2 pasos) ─────────
    window.abrirAjustarFechasAlm = function() {
        const fi = _FECHA_INICIO_DEF;
        const ff = _FECHA_FIN_DEF;

        // Paso 1: recopilación de las nuevas fechas
        Swal.fire({
            title: '<i class="ti ti-calendar-cog" style="color:#0369a1;margin-right:6px;"></i>Ajustar Fechas Individuales',
            html: `
            <div style="text-align:left;font-size:.88rem;color:#475569;line-height:1.7;">
                <p style="margin:0 0 14px;background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:10px 14px;font-size:.8rem;color:#92400e;">
                    <i class="ti ti-alert-triangle"></i>
                    <strong>Esta acción modifica las fechas individuales del pasante</strong> y tiene prioridad sobre las fechas globales del período.
                    Se te pedirá confirmación antes de guardar.
                </p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="font-size:.78rem;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Fecha inicio</label>
                        <input type="date" id="almAjFi" value="${fi}" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:.85rem;">
                    </div>
                    <div>
                        <label style="font-size:.78rem;font-weight:700;color:#374151;display:block;margin-bottom:4px;">Fecha fin</label>
                        <input type="date" id="almAjFf" value="${ff}" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:.85rem;">
                    </div>
                </div>
                <div id="almAjHoras" style="background:#f8fafc;border-radius:8px;padding:8px 12px;font-size:.78rem;color:#64748b;">
                    Las horas meta se recalcularán automáticamente al guardar.
                </div>
            </div>`,
            showCancelButton: true,
            confirmButtonText: '<i class="ti ti-arrow-right"></i> Continuar →',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0369a1',
            width: 480,
            didOpen: () => {
                const preview = () => {
                    const fi2 = document.getElementById('almAjFi').value;
                    const ff2 = document.getElementById('almAjFf').value;
                    const el  = document.getElementById('almAjHoras');
                    if (!fi2 || !ff2 || ff2 <= fi2) { el.textContent = 'Selecciona fechas válidas.'; return; }
                    let cur = new Date(fi2 + 'T00:00:00'), end = new Date(ff2 + 'T00:00:00'), lv = 0;
                    while (cur <= end) { const d = cur.getDay(); if (d > 0 && d < 6) lv++; cur.setDate(cur.getDate() + 1); }
                    el.innerHTML = `<strong>${lv}</strong> días L-V → <strong>${lv * 8}h</strong> meta estimada`;
                };
                document.getElementById('almAjFi').addEventListener('change', preview);
                document.getElementById('almAjFf').addEventListener('change', preview);
                preview();
            },
            preConfirm: () => {
                const fi2 = document.getElementById('almAjFi').value;
                const ff2 = document.getElementById('almAjFf').value;
                if (!fi2 || !ff2 || ff2 <= fi2) {
                    Swal.showValidationMessage('La fecha fin debe ser posterior a la de inicio.');
                    return false;
                }
                return { fi: fi2, ff: ff2 };
            }
        }).then(paso1 => {
            if (!paso1.isConfirmed || !paso1.value) return;
            const { fi: fi2, ff: ff2 } = paso1.value;

            // Calcular días L-V para mostrarlos en el paso 2
            let cur = new Date(fi2 + 'T00:00:00'), end = new Date(ff2 + 'T00:00:00'), lv = 0;
            while (cur <= end) { const d = cur.getDay(); if (d > 0 && d < 6) lv++; cur.setDate(cur.getDate() + 1); }
            const fmt = s => s.split('-').reverse().join('/');

            // Paso 2: confirmación explícita antes de guardar
            Swal.fire({
                icon: 'warning',
                title: '¿Confirmar ajuste de fechas?',
                html: `
                <div style="font-size:.88rem;color:#475569;line-height:1.9;text-align:left;">
                    <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
                        <tr><td style="padding:4px 0;color:#64748b;font-size:.8rem;">Inicio anterior:</td><td style="font-weight:700;text-align:right;">${fmt(_FECHA_INICIO_DEF)}</td></tr>
                        <tr><td style="padding:4px 0;color:#64748b;font-size:.8rem;">Fin anterior:</td><td style="font-weight:700;text-align:right;">${fmt(_FECHA_FIN_DEF)}</td></tr>
                        <tr><td colspan="2" style="border-top:1px solid #e2e8f0;padding-top:8px;"></td></tr>
                        <tr><td style="padding:4px 0;color:#0369a1;font-size:.8rem;font-weight:700;">Nuevo inicio:</td><td style="font-weight:800;color:#0369a1;text-align:right;">${fmt(fi2)}</td></tr>
                        <tr><td style="padding:4px 0;color:#0369a1;font-size:.8rem;font-weight:700;">Nuevo fin:</td><td style="font-weight:800;color:#0369a1;text-align:right;">${fmt(ff2)}</td></tr>
                        <tr><td style="padding:4px 0;color:#64748b;font-size:.8rem;">Nueva meta:</td><td style="font-weight:700;text-align:right;">${lv} días L-V → ${lv * 8}h</td></tr>
                    </table>
                    <p style="margin:0;font-size:.78rem;background:#fef3c7;border-radius:6px;padding:8px 10px;color:#92400e;">
                        <i class="ti ti-info-circle"></i> Esta acción no puede deshacerse automáticamente.
                    </p>
                </div>`,
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-device-floppy"></i> Sí, guardar cambios',
                cancelButtonText: '← Volver',
                confirmButtonColor: '#0369a1',
                cancelButtonColor: '#64748b',
                width: 460,
                preConfirm: async () => {
                    const resp = await fetch('<?= URLROOT ?>/periodos/ajustarFechasPasante', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _CSRF_ALM },
                        body: JSON.stringify({ pasante_id: _PASANTE_ID, fecha_inicio: fi2, fecha_fin: ff2, csrf_token: _CSRF_ALM })
                    });
                    const d = await resp.json();
                    if (!d.success) { Swal.showValidationMessage(d.message || 'Error al guardar.'); return false; }
                    return d;
                }
            }).then(paso2 => {
                if (!paso2.isConfirmed || !paso2.value) return;
                const d = paso2.value;
                Swal.fire({
                    icon: 'success',
                    title: 'Fechas actualizadas',
                    html: `Inicio: <strong>${d.fecha_inicio_fmt}</strong> — Fin: <strong>${d.fecha_fin_fmt}</strong><br>Nueva meta: <strong>${d.horas_meta}h</strong>`,
                    confirmButtonColor: '#0369a1'
                }).then(() => location.reload());
            });
        });
    };
})();
</script>
<?php endif; ?>
