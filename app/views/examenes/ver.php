<?php
/**
 * Vista: Examen — Detalle + Resultados
 * URL: /examenes/ver/{id}
 * Variables: $examen (obj), $preguntas[] (cada uno con ->opciones[]), $intentos[]
 */
$examen   = $data['examen']   ?? null;
$preguntas = $data['preguntas'] ?? [];
$intentos = $data['intentos'] ?? [];

if (!$examen) {
    echo '<p style="color:#ef4444;padding:24px;">Examen no encontrado.</p>';
    return;
}

$activo          = (int)($examen->activo ?? 0);
$totalPreguntas  = count($preguntas);
$totalPuntos     = array_sum(array_map(fn($p) => (int)($p->puntos ?? 1), $preguntas));
$totalIntentos   = count($intentos);
$estadoExam      = $activo ? 'publicado' : ($totalIntentos > 0 ? 'cerrado' : 'borrador');

// ── Estadísticas de resultados ─────────────────────────────────────────
$promPct    = 0;
$mejorPct   = 0;
if ($totalIntentos > 0) {
    $pcts    = array_map(fn($i) => (float)($i->porcentaje ?? 0), $intentos);
    $promPct = round(array_sum($pcts) / count($pcts), 1);
    $mejorPct = max($pcts);
}

// ── Velocímetro: colores semánticos por rango de promedio ─────────────
$gaugeColor = $totalIntentos > 0 ? ($promPct >= 60 ? '#22c55e' : ($promPct >= 40 ? '#f59e0b' : '#ef4444')) : '#94a3b8';
$gaugeLabel = $totalIntentos > 0 ? ($promPct >= 60 ? 'Excelente' : ($promPct >= 40 ? 'Regular' : 'Bajo'))      : 'Sin datos';
$gaugeBg    = $totalIntentos > 0 ? ($promPct >= 60 ? '#dcfce7'   : ($promPct >= 40 ? '#fef3c7' : '#fee2e2'))   : '#f1f5f9';
$gaugeTxt   = $totalIntentos > 0 ? ($promPct >= 60 ? '#16a34a'   : ($promPct >= 40 ? '#d97706' : '#dc2626'))   : '#94a3b8';
$tasaAprobacion = $totalIntentos > 0
    ? round((count(array_filter($intentos, fn($i) => (float)($i->porcentaje ?? 0) >= 60)) / $totalIntentos) * 100)
    : 0;

// ── Distribución por rangos para Chart.js ─────────────────────────────
$rangos = ['0-20%' => 0, '20-40%' => 0, '40-60%' => 0, '60-80%' => 0, '80-100%' => 0];
foreach ($intentos as $it) {
    $pct = (float)($it->porcentaje ?? 0);
    if      ($pct <= 20)  $rangos['0-20%']++;
    elseif  ($pct <= 40)  $rangos['20-40%']++;
    elseif  ($pct <= 60)  $rangos['40-60%']++;
    elseif  ($pct <= 80)  $rangos['60-80%']++;
    else                  $rangos['80-100%']++;
}
$chartLabels = json_encode(array_keys($rangos));
$chartData   = json_encode(array_values($rangos));
?>

<style>
@keyframes ev-fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes evBlink   { 0%,100%{opacity:1} 50%{opacity:.3} }

.ev-wrap { padding-bottom:56px; }
.ev-wrap > * { animation:ev-fadeUp .45s ease both; }
.ev-wrap > *:nth-child(1){ animation-delay:.04s; }
.ev-wrap > *:nth-child(2){ animation-delay:.11s; }
.ev-wrap > *:nth-child(3){ animation-delay:.17s; }
.ev-wrap > *:nth-child(4){ animation-delay:.23s; }

/* ══ HERO — idéntico al dashboard pasante ══════════════════ */
.ev-hero {
    background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 45%,#2563eb 100%);
    border-radius:22px; padding:30px 36px;
    position:relative; overflow:hidden;
    display:flex; align-items:center; justify-content:space-between;
    gap:20px; flex-wrap:wrap; margin-bottom:20px;
    box-shadow:0 8px 32px rgba(15,23,42,.3);
}
.ev-hero::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:280px; height:280px; background:rgba(255,255,255,.05); border-radius:50%;
}
.ev-hero::after {
    content:''; position:absolute; bottom:-40px; left:30%;
    width:180px; height:180px; background:rgba(255,255,255,.03); border-radius:50%;
}
.ev-hero-avatar {
    width:56px; height:56px;
    background:rgba(255,255,255,.18); border:2px solid rgba(255,255,255,.28);
    border-radius:16px; display:flex; align-items:center; justify-content:center;
    font-size:1.55rem; color:#fff; flex-shrink:0; z-index:1;
}
.ev-hero-left  { display:flex; align-items:center; gap:18px; z-index:1; }
.ev-hero-right { display:flex; align-items:center; gap:12px; flex-wrap:wrap; z-index:1; }
.ev-hero-title { color:#fff; font-size:1.65rem; font-weight:800; margin:0 0 8px; letter-spacing:-.3px; line-height:1.2; }
.ev-hero-desc  { color:rgba(255,255,255,.68); font-size:.87rem; margin:0; line-height:1.55; max-width:500px;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

/* pills */
.ev-pill {
    display:inline-flex; align-items:center; gap:6px;
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
    border-radius:50px; padding:6px 16px;
    color:rgba(255,255,255,.9); font-size:.8rem; font-weight:600;
    backdrop-filter:blur(8px);
}
.ev-pill-pub  { background:rgba(16,185,129,.2); border-color:rgba(16,185,129,.35); }
.ev-pill-draf { background:rgba(245,158,11,.2);  border-color:rgba(245,158,11,.35); }
.ev-pill-dot  { width:7px; height:7px; border-radius:50%; animation:evBlink 2.2s ease-in-out infinite; }
.ev-pill-pub  .ev-pill-dot { background:#34d399; }
.ev-pill-draf .ev-pill-dot { background:#fbbf24; }

/* ══ KPI CARDS — réplica exacta pb-kpi del dashboard ═══════ */
.ev-kpi-grid {
    display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px;
}
@media(max-width:900px){ .ev-kpi-grid{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:480px){ .ev-kpi-grid{ grid-template-columns:1fr; } }

.ev-kpi {
    background:#fff; border-radius:18px; padding:22px;
    box-shadow:0 2px 14px rgba(0,0,0,.07);
    display:flex; flex-direction:column; gap:12px;
    position:relative; overflow:hidden;
    transition:transform .25s, box-shadow .25s; cursor:default;
}
.ev-kpi:hover { transform:translateY(-4px); }
.ev-kpi::after {
    content:''; position:absolute; bottom:-20px; right:-20px;
    width:80px; height:80px; border-radius:50%; opacity:.07;
}
.ev-kpi-top   { display:flex; justify-content:space-between; align-items:flex-start; }
.ev-kpi-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; }
.ev-kpi-val   { font-size:2.6rem; font-weight:900; line-height:1; margin-top:2px; }
.ev-kpi-sub   { font-size:.74rem; color:#94a3b8; font-weight:500; }
.ev-kpi-icon  { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }

.ev-kpi.c-teal   { border-top:3px solid #0d9488; } .ev-kpi.c-teal::after   { background:#0d9488; }
.ev-kpi.c-blue   { border-top:3px solid #2563eb; } .ev-kpi.c-blue::after   { background:#2563eb; }
.ev-kpi.c-amber  { border-top:3px solid #f59e0b; } .ev-kpi.c-amber::after  { background:#f59e0b; }
.ev-kpi.c-indigo { border-top:3px solid #6366f1; } .ev-kpi.c-indigo::after { background:#6366f1; }
.ev-kpi:hover.c-teal   { box-shadow:0 12px 28px rgba(13,148,136,.2); }
.ev-kpi:hover.c-blue   { box-shadow:0 12px 28px rgba(37,99,235,.2); }
.ev-kpi:hover.c-amber  { box-shadow:0 12px 28px rgba(245,158,11,.2); }
.ev-kpi:hover.c-indigo { box-shadow:0 12px 28px rgba(99,102,241,.2); }

.ev-tab.disabled { opacity:.38; cursor:not-allowed; pointer-events:none; }

/* ── Tabs ─────────────────────────────────────────────────── */
.ev-tabs {
    display:flex; gap:6px; margin-bottom:22px;
    background:#fff; padding:6px; border-radius:16px;
    box-shadow:0 2px 10px rgba(0,0,0,.06); width:fit-content;
}
.ev-tab {
    padding:10px 22px; border-radius:12px; font-size:.88rem; font-weight:700;
    cursor:pointer; border:none; background:transparent; color:#94a3b8; transition:all .2s;
    display:flex; align-items:center; gap:7px;
}
.ev-tab.active { background:linear-gradient(135deg,#7c3aed,#a78bfa); color:#fff; box-shadow:0 4px 12px rgba(109,40,217,.3); }

/* ── Card ─────────────────────────────────────────────────── */
.ev-card {
    background:#fff; border-radius:18px; padding:24px 26px;
    box-shadow:0 2px 14px rgba(0,0,0,.06); border:1px solid rgba(0,0,0,.04);
    margin-bottom:20px;
}
.ev-card-title {
    font-size:.95rem; font-weight:700; color:#1e293b; margin:0 0 18px;
    display:flex; align-items:center; gap:8px;
}
.ev-card-title i { color:#7c3aed; }

/* ── Preguntas Tab ────────────────────────────────────────── */
.ev-preg-item {
    border:1.5px solid #e9d5ff; border-radius:14px; padding:18px 20px; margin-bottom:14px;
    background:#faf5ff;
}
.ev-preg-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
.ev-preg-num {
    width:26px; height:26px; border-radius:7px;
    background:linear-gradient(135deg,#7c3aed,#a78bfa); color:#fff;
    font-weight:800; font-size:.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.ev-preg-enunc { font-weight:700; color:#1e293b; font-size:.92rem; flex:1; }
.ev-badge-tipo {
    padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; flex-shrink:0;
    background:#ede9fe; color:#6d28d9;
}
.ev-badge-pts {
    padding:3px 10px; border-radius:20px; font-size:.7rem; font-weight:700; flex-shrink:0;
    background:#dbeafe; color:#1e40af;
}

/* ── Medalla emoji ───────────────────────────────────────── */
.ev-medal { font-size:1.25rem; line-height:1; }

/* ── Editar puntos inline ────────────────────────────────── */
.ev-pts-input {
    width:52px; padding:3px 7px; border:1.5px solid #bfdbfe;
    border-radius:8px; font-size:.78rem; font-weight:700; color:#1e40af;
    text-align:center; background:#eff6ff; cursor:pointer;
    transition:border-color .15s, box-shadow .15s;
}
.ev-pts-input:focus { outline:none; border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); }
.ev-opciones { display:flex; flex-direction:column; gap:6px; padding-left:36px; }
.ev-opcion-row {
    display:flex; align-items:center; gap:10px;
    padding:7px 12px; border-radius:10px; font-size:.85rem;
}
.ev-opcion-row.correcta { background:#f0fdf4; border:1.5px solid #10b981; color:#065f46; font-weight:700; }
.ev-opcion-row.incorrecta { background:#f8fafc; border:1.5px solid #e2e8f0; color:#94a3b8; }
.ev-opcion-icon { font-size:1rem; flex-shrink:0; }

/* ── Resultados Tab ───────────────────────────────────────── */
.ev-kpi-row { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:22px; }
@media(max-width:640px) { .ev-kpi-row { grid-template-columns:1fr; } }
.ev-kpi {
    background:#fff; border-radius:16px; padding:18px 20px;
    box-shadow:0 2px 12px rgba(0,0,0,.05); border:1px solid rgba(0,0,0,.04);
    display:flex; align-items:center; gap:14px;
}
.ev-kpi-icon {
    width:44px; height:44px; border-radius:12px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:1.3rem;
}
.ev-kpi-val { font-size:1.6rem; font-weight:800; color:#1e293b; line-height:1; }
.ev-kpi-lbl { font-size:.76rem; color:#94a3b8; font-weight:500; margin-top:3px; }

/* ── Results grid (chart + ranking side-by-side) ─────────── */
.ev-results-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; margin-bottom:20px; }
.ev-results-grid .ev-card { margin-bottom:0; }
@media(max-width:900px) { .ev-results-grid { grid-template-columns:1fr; } }

/* ── Chart ───────────────────────────────────────────────── */
.ev-chart-wrap { height:280px; position:relative; margin-bottom:0; }

/* ── Table ───────────────────────────────────────────────── */
.ev-table-wrap { overflow-x:auto; border-radius:14px; border:1.5px solid #e9d5ff; }
.ev-table {
    width:100%; border-collapse:collapse; font-size:.82rem;
}
.ev-table th {
    background:#f5f3ff; color:#6d28d9; font-weight:700; padding:11px 14px;
    text-align:left; white-space:nowrap; border-bottom:1.5px solid #e9d5ff;
}
.ev-table td { padding:11px 14px; border-bottom:1px solid #f1f5f9; color:#334155; }
.ev-table tr:last-child td { border-bottom:none; }
.ev-table tr:hover td { background:#faf5ff; }

.ev-medal { font-size:1.1rem; }

/* ── Empty State ─────────────────────────────────────────── */
.ev-empty {
    text-align:center; padding:48px 20px; color:#94a3b8;
}
.ev-empty i { font-size:2.8rem; color:#c4b5fd; display:block; margin-bottom:12px; }
.ev-empty p { margin:0; font-size:.95rem; }

/* ── Pct bar inline ─────────────────────────────────────── */
.ev-pct-bar { display:flex; align-items:center; gap:8px; }
.ev-pct-track { flex:1; height:7px; background:#e9d5ff; border-radius:4px; min-width:60px; }
.ev-pct-fill  { height:100%; border-radius:4px; background:linear-gradient(90deg,#7c3aed,#a78bfa); }

/* ── Mobile ≤ 767px ─────────────────────────────────────────── */
@media(max-width:767px){
    .ev-hero{padding:20px 18px;flex-direction:column;gap:14px}
    .ev-hero-left{flex-direction:column;gap:10px}
    .ev-hero-avatar{width:48px;height:48px;font-size:1.2rem}
    .ev-hero-title{font-size:1.2rem}
    .ev-kpi-grid{grid-template-columns:repeat(2,1fr)}
    .ev-kpi-val{font-size:1.8rem}
    .ev-kpi-row{grid-template-columns:1fr}
    .ev-results-grid{grid-template-columns:1fr}
    .ev-chart-wrap{height:200px}
    .ev-table th,.ev-table td{padding:8px 10px;font-size:.8rem}
    .ev-tabs{gap:4px}
    .ev-tab{padding:7px 10px;font-size:.78rem}
    .ev-tab i{display:none}
}
</style>


<div class="ev-wrap">

    <!-- ══ HERO ══ -->
    <div class="ev-hero">

        <!-- Izquierda: avatar + título + meta -->
        <div class="ev-hero-left">
            <div class="ev-hero-avatar">
                <i class="ti ti-clipboard-text"></i>
            </div>
            <div>
                <a href="<?= URLROOT ?>/examenes"
                   style="display:inline-flex;align-items:center;gap:5px;color:rgba(255,255,255,.55);
                          text-decoration:none;font-size:.78rem;font-weight:600;margin-bottom:6px;transition:color .2s;"
                   onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.55)'">
                    <i class="ti ti-arrow-left" style="font-size:.8rem;"></i> Volver a exámenes
                </a>
                <h1 class="ev-hero-title"><?= htmlspecialchars($examen->titulo ?? 'Sin título') ?></h1>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <?php if (!empty($examen->periodo_nombre) && $examen->periodo_nombre !== 'Sin período'): ?>
                    <span class="ev-pill">
                        <i class="ti ti-calendar-stats" style="font-size:.82rem;"></i>
                        <?= htmlspecialchars($examen->periodo_nombre) ?>
                    </span>
                    <?php endif; ?>
                    <span class="ev-pill">
                        <i class="ti ti-user" style="font-size:.82rem;"></i>
                        <?= htmlspecialchars($examen->creador_nombre ?? 'Desconocido') ?>
                    </span>
                    <?php if (!empty($examen->descripcion)): ?>
                    <p class="ev-hero-desc" style="width:100%;margin-top:4px;">
                        <?= htmlspecialchars($examen->descripcion) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Derecha: estado + acción -->
        <div class="ev-hero-right">
            <?php if ($estadoExam === 'publicado'): ?>
            <span class="ev-pill ev-pill-pub">
                <span class="ev-pill-dot"></span> Publicado
            </span>
            <button onclick="cerrarExamen(<?= (int)$examen->id ?>, <?= $totalIntentos ?>)"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;
                       background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
                       border-radius:50px;color:#fca5a5;font-size:.8rem;font-weight:700;
                       cursor:pointer;transition:background .2s;"
                onmouseover="this.style.background='rgba(239,68,68,.28)'"
                onmouseout="this.style.background='rgba(239,68,68,.15)'">
                <i class="ti ti-lock"></i> Cerrar examen
            </button>
            <?php elseif ($estadoExam === 'cerrado'): ?>
            <span class="ev-pill" style="background:rgba(100,116,139,.2);border-color:rgba(100,116,139,.35);">
                <i class="ti ti-lock" style="font-size:.8rem;"></i> Cerrado
            </span>
            <button onclick="reabrirExamen(<?= (int)$examen->id ?>)"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;
                       background:rgba(5,150,105,.15);border:1px solid rgba(5,150,105,.3);
                       border-radius:50px;color:#6ee7b7;font-size:.8rem;font-weight:700;
                       cursor:pointer;transition:background .2s;"
                onmouseover="this.style.background='rgba(5,150,105,.28)'"
                onmouseout="this.style.background='rgba(5,150,105,.15)'">
                <i class="ti ti-lock-open"></i> Reabrir
            </button>
            <?php else: ?>
            <span class="ev-pill ev-pill-draf">
                <span class="ev-pill-dot"></span> Borrador
            </span>
            <button onclick="reabrirExamen(<?= (int)$examen->id ?>)"
                style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;
                       background:rgba(5,150,105,.15);border:1px solid rgba(5,150,105,.3);
                       border-radius:50px;color:#6ee7b7;font-size:.8rem;font-weight:700;
                       cursor:pointer;transition:background .2s;"
                onmouseover="this.style.background='rgba(5,150,105,.28)'"
                onmouseout="this.style.background='rgba(5,150,105,.15)'">
                <i class="ti ti-eye"></i> Publicar
            </button>
            <?php endif; ?>
        </div>

    </div>

    <!-- ══ KPI GRID — réplica dashboard pasante ══ -->
    <?php
    $promColor = $totalIntentos > 0 ? ($promPct >= 60 ? '#059669' : '#dc2626') : '#94a3b8';
    $promVal   = $totalIntentos > 0 ? $promPct . '%' : '—';
    $promSub   = $totalIntentos > 0 ? ($promPct >= 60 ? 'Por encima del mínimo' : 'Por debajo del mínimo') : 'Sin respuestas aún';
    ?>
    <div class="ev-kpi-grid">

        <div class="ev-kpi c-teal">
            <div class="ev-kpi-top">
                <div>
                    <div class="ev-kpi-label">Preguntas</div>
                    <div class="ev-kpi-val" style="color:#0d9488;"><?= $totalPreguntas ?></div>
                    <div class="ev-kpi-sub"><?= $totalPreguntas === 1 ? '1 pregunta registrada' : "$totalPreguntas preguntas registradas" ?></div>
                </div>
                <div class="ev-kpi-icon" style="background:#ccfbf1;">
                    <i class="ti ti-help-circle" style="color:#0d9488;"></i>
                </div>
            </div>
        </div>

        <div class="ev-kpi c-blue">
            <div class="ev-kpi-top">
                <div>
                    <div class="ev-kpi-label">Puntos totales</div>
                    <div class="ev-kpi-val" style="color:#2563eb;"><?= $totalPuntos ?></div>
                    <div class="ev-kpi-sub"><?= $totalPreguntas > 0 ? round($totalPuntos / $totalPreguntas, 1) . ' pts por pregunta' : 'Sin puntos asignados' ?></div>
                </div>
                <div class="ev-kpi-icon" style="background:#eff6ff;">
                    <i class="ti ti-star" style="color:#2563eb;"></i>
                </div>
            </div>
        </div>

        <div class="ev-kpi c-amber">
            <div class="ev-kpi-top">
                <div>
                    <div class="ev-kpi-label">Respondieron</div>
                    <div class="ev-kpi-val" style="color:#d97706;"><?= $totalIntentos ?></div>
                    <div class="ev-kpi-sub"><?= $totalIntentos === 1 ? '1 pasante evaluado' : "$totalIntentos pasantes evaluados" ?></div>
                </div>
                <div class="ev-kpi-icon" style="background:#fef3c7;">
                    <i class="ti ti-users" style="color:#d97706;"></i>
                </div>
            </div>
        </div>

        <div class="ev-kpi c-indigo">
            <div class="ev-kpi-top">
                <div>
                    <div class="ev-kpi-label">Promedio</div>
                    <div class="ev-kpi-val" style="color:<?= $promColor ?>;"><?= $promVal ?></div>
                    <div class="ev-kpi-sub"><?= $promSub ?></div>
                </div>
                <div class="ev-kpi-icon" style="background:#ede9fe;">
                    <i class="ti ti-chart-pie" style="color:#6366f1;"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Tabs -->
    <div class="ev-tabs">
        <button class="ev-tab active" id="tab-btn-preguntas" onclick="switchTab('preguntas')">
            <i class="ti ti-help-circle"></i> Preguntas (<?= $totalPreguntas ?>)
        </button>
        <button class="ev-tab <?= $totalIntentos === 0 ? 'disabled' : '' ?>"
                id="tab-btn-resultados"
                onclick="<?= $totalIntentos > 0 ? "switchTab('resultados')" : '' ?>"
                title="<?= $totalIntentos === 0 ? 'Sin resultados aún' : '' ?>">
            <i class="ti ti-chart-bar"></i> Resultados (<?= $totalIntentos ?>)
        </button>
    </div>

    <!-- ══════════ TAB: PREGUNTAS ══════════ -->
    <div id="tab-preguntas" class="tab-content">
        <?php if (empty($preguntas)): ?>
        <div class="ev-card">
            <div class="ev-empty">
                <i class="ti ti-help-circle"></i>
                <p>Este examen no tiene preguntas registradas.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="ev-card">
            <div class="ev-card-title">
                <i class="ti ti-list"></i>
                <?= $totalPreguntas ?> pregunta<?= $totalPreguntas !== 1 ? 's' : '' ?> &mdash; <?= $totalPuntos ?> punto<?= $totalPuntos !== 1 ? 's' : '' ?> en total
            </div>

            <?php foreach ($preguntas as $qi => $preg): ?>
            <div class="ev-preg-item">
                <div class="ev-preg-header">
                    <div class="ev-preg-num"><?= $qi + 1 ?></div>
                    <div class="ev-preg-enunc"><?= htmlspecialchars($preg->enunciado ?? '') ?></div>
                    <span class="ev-badge-tipo">
                        <?= $preg->tipo === 'verdadero_falso' ? 'V / F' : 'Opción múltiple' ?>
                    </span>
                    <div style="display:flex;align-items:center;gap:5px;flex-shrink:0;" title="Puntos de esta pregunta">
                        <input type="number"
                               class="ev-pts-input"
                               data-preg-id="<?= (int)$preg->id ?>"
                               value="<?= (int)($preg->puntos ?? 1) ?>"
                               min="1" max="100"
                               onchange="actualizarPuntos(<?= (int)$preg->id ?>, this)"
                               title="Cambiar puntos">
                        <span style="font-size:.7rem;color:#64748b;font-weight:600;">pts</span>
                    </div>
                </div>
                <div class="ev-opciones">
                    <?php foreach (($preg->opciones ?? []) as $op):
                        $esCor = (int)($op->es_correcta ?? 0) === 1;
                    ?>
                    <div class="ev-opcion-row <?= $esCor ? 'correcta' : 'incorrecta' ?>">
                        <span class="ev-opcion-icon">
                            <?php if ($esCor): ?>
                            <i class="ti ti-circle-check-filled" style="color:#10b981;"></i>
                            <?php else: ?>
                            <i class="ti ti-circle" style="color:#cbd5e1;"></i>
                            <?php endif; ?>
                        </span>
                        <?= htmlspecialchars($op->texto ?? '') ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══════════ TAB: RESULTADOS ══════════ -->
    <div id="tab-resultados" class="tab-content" style="display:none;">

        <!-- KPIs -->
        <div class="ev-kpi-row">
            <div class="ev-kpi">
                <div class="ev-kpi-icon" style="background:#ede9fe;">
                    <i class="ti ti-users" style="color:#7c3aed;"></i>
                </div>
                <div>
                    <div class="ev-kpi-val"><?= $totalIntentos ?></div>
                    <div class="ev-kpi-lbl">Respondieron</div>
                </div>
            </div>
            <div class="ev-kpi">
                <div class="ev-kpi-icon" style="background:#dbeafe;">
                    <i class="ti ti-chart-pie" style="color:#2563eb;"></i>
                </div>
                <div>
                    <div class="ev-kpi-val"><?= number_format($promPct, 1) ?>%</div>
                    <div class="ev-kpi-lbl">Promedio</div>
                </div>
            </div>
            <div class="ev-kpi">
                <div class="ev-kpi-icon" style="background:#d1fae5;">
                    <i class="ti ti-trophy" style="color:#059669;"></i>
                </div>
                <div>
                    <div class="ev-kpi-val"><?= number_format($mejorPct, 1) ?>%</div>
                    <div class="ev-kpi-lbl">Mejor puntaje</div>
                </div>
            </div>
        </div>

        <?php if ($totalIntentos > 0): ?>

        <div class="ev-results-grid">

        <!-- Velocímetro promedio -->
        <div class="ev-card">
            <div class="ev-card-title" style="justify-content:space-between;margin-bottom:14px;">
                <span style="display:flex;align-items:center;gap:8px;">
                    <i class="ti ti-gauge" style="color:<?= $gaugeColor ?>;"></i>
                    Promedio del examen
                </span>
                <span style="background:<?= $gaugeBg ?>;color:<?= $gaugeTxt ?>;font-size:.72rem;font-weight:700;padding:3px 12px;border-radius:20px;">
                    <?= $gaugeLabel ?>
                </span>
            </div>

            <!-- Velocímetro ECharts — sin texto interno -->
            <div id="ev-gauge-chart" style="width:100%;height:185px;"></div>

            <!-- Texto del valor FUERA del canvas -->
            <div style="text-align:center;margin:-18px 0 10px;line-height:1.2;">
                <div style="font-size:2rem;font-weight:900;color:<?= $gaugeColor ?>;letter-spacing:-.5px;">
                    <?= $totalIntentos > 0 ? $promPct . '%' : '—' ?>
                </div>
                <div style="font-size:.68rem;color:#94a3b8;font-weight:700;letter-spacing:.05em;text-transform:uppercase;margin-top:2px;">
                    Promedio General
                </div>
            </div>

            <!-- Métricas de breakdown (estilo analíticas) -->
            <div style="margin-top:18px;border-top:1px solid #f1f5f9;padding-top:14px;display:flex;flex-direction:column;gap:11px;">
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:.76rem;font-weight:600;margin-bottom:5px;">
                        <span style="color:#334155;">Tasa de aprobación</span>
                        <span style="color:#22c55e;"><?= $tasaAprobacion ?>%</span>
                    </div>
                    <div style="height:6px;background:#e2e8f0;border-radius:4px;">
                        <div style="width:<?= $tasaAprobacion ?>%;height:100%;background:#22c55e;border-radius:4px;"></div>
                    </div>
                    <div style="font-size:.68rem;color:#94a3b8;margin-top:3px;">% de pasantes que aprobaron (≥60%)</div>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:.76rem;font-weight:600;margin-bottom:5px;">
                        <span style="color:#334155;">Mejor puntaje</span>
                        <span style="color:#2563eb;"><?= number_format($mejorPct, 1) ?>%</span>
                    </div>
                    <div style="height:6px;background:#e2e8f0;border-radius:4px;">
                        <div style="width:<?= min(100, $mejorPct) ?>%;height:100%;background:#2563eb;border-radius:4px;"></div>
                    </div>
                    <div style="font-size:.68rem;color:#94a3b8;margin-top:3px;">Puntaje más alto registrado</div>
                </div>
            </div>
        </div>

        <!-- Ranking tabla -->
        <div class="ev-card">
            <div class="ev-card-title"><i class="ti ti-medal"></i> Ranking de resultados</div>
            <div class="ev-table-wrap">
                <table class="ev-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Puntaje</th>
                            <th>%</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($intentos, 0, 10) as $pos => $it):
                            $pct = (float)($it->porcentaje ?? 0);
                            $pctFill = min(100, $pct);
                            $pctColor = $pct >= 60 ? '#059669' : ($pct >= 40 ? '#d97706' : '#ef4444');
                            $medal = match($pos) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => $pos + 1 };
                        ?>
                        <tr data-intento-id="<?= (int)$it->id ?>">
                            <td style="text-align:center;">
                                <span class="ev-medal"><?= $medal ?></span>
                            </td>
                            <td style="font-weight:600;">
                                <?= htmlspecialchars($it->pasante_nombre ?? 'Desconocido') ?>
                            </td>
                            <td>
                                <span style="font-weight:700;color:<?= $pctColor ?>;">
                                    <?= number_format((float)($it->puntaje_obtenido ?? 0), 1) ?>
                                    / <?= number_format((float)($it->puntaje_maximo ?? $totalPuntos), 1) ?>
                                </span>
                            </td>
                            <td>
                                <div class="ev-pct-bar">
                                    <div class="ev-pct-track">
                                        <div class="ev-pct-fill" style="width:<?= $pctFill ?>%;background:<?= $pctColor ?>;"></div>
                                    </div>
                                    <span style="font-size:.78rem;font-weight:700;color:<?= $pctColor ?>;white-space:nowrap;">
                                        <?= number_format($pct, 1) ?>%
                                    </span>
                                </div>
                            </td>
                            <td style="color:#64748b;white-space:nowrap;font-size:.78rem;">
                                <?= !empty($it->enviado_at) ? date('d/m/y', strtotime($it->enviado_at)) : '—' ?>
                            </td>
                            <td id="ev-rev-<?= (int)$it->id ?>">
                                <?php if (!empty($it->revisado_at)): ?>
                                <span style="display:inline-flex;align-items:center;gap:4px;background:#d1fae5;color:#065f46;border-radius:20px;padding:3px 9px;font-size:.72rem;font-weight:700;">
                                    <i class="ti ti-eye-check"></i> Revisado
                                </span>
                                <?php else: ?>
                                <button onclick="marcarRevisado(<?= (int)$it->id ?>)"
                                    style="display:inline-flex;align-items:center;gap:4px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:20px;padding:3px 10px;font-size:.72rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                                    <i class="ti ti-eye"></i> Revisar
                                </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="eliminarIntento(<?= (int)$it->id ?>)"
                                    title="Eliminar resultado"
                                    style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:#fff1f2;color:#ef4444;border:1px solid #fecaca;border-radius:8px;cursor:pointer;font-size:.85rem;transition:background .15s;">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalIntentos > 10): ?>
            <p style="text-align:center;color:#94a3b8;font-size:.8rem;margin-top:12px;margin-bottom:0;">
                Mostrando top 10 de <?= $totalIntentos ?> participantes
            </p>
            <?php endif; ?>
        </div>

        </div><!-- /ev-results-grid -->

        <?php else: ?>
        <!-- Empty state resultados -->
        <div class="ev-card">
            <div class="ev-empty">
                <i class="ti ti-chart-bar"></i>
                <p>Aún no hay respuestas enviadas para este examen.</p>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /tab-resultados -->
</div>

<script>
// URLROOT ya disponible desde main_layout.php

// ── Tab switching ────────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.ev-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).style.display = '';
    document.getElementById('tab-btn-' + tab).classList.add('active');

    if (tab === 'resultados' && !window._chartInit) {
        window._chartInit = true;
        initChart();
    }
}

// ── ECharts velocímetro (idéntico al de analíticas) ────────────────
function initChart() {
    if (typeof echarts === 'undefined') { setTimeout(initChart, 50); return; }

    const el = document.getElementById('ev-gauge-chart');
    if (!el) return;

    const avg = <?= (float)$promPct ?>;
    const chart = echarts.init(el);

    chart.setOption({
        backgroundColor: 'transparent',
        series: [
            // ── Pista de fondo gris ──
            {
                type: 'gauge',
                startAngle: 210, endAngle: -30,
                min: 0, max: 100,
                radius: '80%', center: ['50%', '58%'],
                splitNumber: 0,
                axisLine: { lineStyle: { width: 12, color: [[1, '#e2e8f0']] } },
                axisTick:  { show: false },
                splitLine: { show: false },
                axisLabel: { show: false },
                pointer:   { show: false },
                anchor:    { show: false },
                detail:    { show: false },
                title:     { show: false }
            },
            // ── Arco con zonas de color + aguja ──
            {
                type: 'gauge',
                startAngle: 210, endAngle: -30,
                min: 0, max: 100,
                radius: '80%', center: ['50%', '58%'],
                splitNumber: 10,
                axisLine: {
                    lineStyle: {
                        width: 12,
                        color: [
                            [0.40, '#ef4444'],
                            [0.60, '#f59e0b'],
                            [1.00, '#22c55e']
                        ]
                    }
                },
                axisTick: {
                    show: true, distance: -16, length: 4,
                    lineStyle: { color: 'rgba(255,255,255,0.7)', width: 1.5 }
                },
                splitLine: {
                    show: true, distance: -18, length: 10,
                    lineStyle: { color: 'rgba(255,255,255,0.9)', width: 2.5 }
                },
                axisLabel: {
                    show: true, distance: 20, color: '#94a3b8',
                    fontSize: 10, fontFamily: 'Inter, sans-serif', fontWeight: 600,
                    formatter: v => (v === 0 || v === 40 || v === 60 || v === 100) ? v : ''
                },
                pointer: {
                    show: true, length: '62%', width: 4,
                    itemStyle: {
                        color: '#1e293b',
                        shadowColor: 'rgba(0,0,0,0.25)', shadowBlur: 8, shadowOffsetY: 4
                    }
                },
                anchor: {
                    show: true, showAbove: true, size: 14,
                    itemStyle: {
                        color: '#1e293b', borderColor: '#fff', borderWidth: 3,
                        shadowColor: 'rgba(0,0,0,0.2)', shadowBlur: 6
                    }
                },
                title:  { show: false },
                detail: { show: false },
                data: [{ value: avg }]
            }
        ]
    });

    setTimeout(() => chart.resize(), 100);
}

// ── Cerrar / Reabrir examen ─────────────────────────────────────────
async function cerrarExamen(id, respondieron) {
    const ok = await Swal.fire({
        title: '¿Cerrar este examen?',
        html: 'Los pasantes <b>ya no podrán enviar</b> respuestas.' +
              (respondieron > 0 ? '<br>Los <b>' + respondieron + '</b> resultado(s) se conservarán.' : ''),
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="ti ti-lock"></i> Cerrar examen',
        cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;
    await _cambiarEstadoExamen(id, 0, 'Examen cerrado', 'Los pasantes ya no pueden enviar respuestas.');
}

async function reabrirExamen(id) {
    const ok = await Swal.fire({
        title: '¿Publicar/Reabrir examen?',
        text: 'Los pasantes podrán acceder y enviar respuestas nuevamente.',
        icon: 'question', showCancelButton: true,
        confirmButtonColor: '#059669', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, publicar', cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;
    await _cambiarEstadoExamen(id, 1, 'Examen publicado', 'Los pasantes fueron notificados.');
}

async function _cambiarEstadoExamen(id, activo, titulo, texto) {
    try {
        const csrfMetaPub = document.querySelector('meta[name="csrf-token"]');
        const res  = await fetch(URLROOT + '/examenes/publicar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMetaPub ? csrfMetaPub.getAttribute('content') : '' },
            body: JSON.stringify({ id, activo })
        });
        const data = await res.json();
        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo actualizar' });
            return;
        }
        Swal.fire({ icon: 'success', title: titulo, text: texto, timer: 2000, showConfirmButton: false })
            .then(() => window.location.reload());
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión' });
    }
}

// ── Marcar revisado — muestra panel con respuestas antes de confirmar
async function marcarRevisado(intentoId) {
    // 1. Cargar detalle del intento
    let detalle;
    try {
        const res = await fetch(URLROOT + '/examenes/detalleIntento?intento_id=' + intentoId);
        const data = await res.json();
        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo cargar el detalle.' });
            return;
        }
        detalle = data;
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión' });
        return;
    }

    // 2. Construir HTML del panel de revisión
    const pct       = detalle.intento.porcentaje.toFixed(1);
    const pctColor  = detalle.intento.porcentaje >= 60 ? '#059669' : (detalle.intento.porcentaje >= 40 ? '#d97706' : '#ef4444');
    const acertadas = detalle.preguntas.filter(p => p.acierto).length;
    const total     = detalle.preguntas.length;
    const revisadoYa = !!detalle.intento.revisado_at;

    let htmlPregs = '';
    detalle.preguntas.forEach((preg, idx) => {
        const ok         = preg.acierto;
        const borderPreg = ok ? '#86efac' : '#fecaca';
        const bgPreg     = ok ? '#f0fdf4' : '#fff1f2';
        const numBg      = ok ? '#059669' : '#ef4444';
        const icono      = ok ? '✓' : '✗';
        const iconColor  = ok ? '#059669' : '#ef4444';

        let htmlOps = '';
        preg.opciones.forEach(op => {
            let bg = '#f8fafc', border = '#e2e8f0', color = '#64748b', icon = '○', fw = '500', tag = '';
            if (op.es_correcta && op.elegida) {
                bg = '#f0fdf4'; border = '#10b981'; color = '#065f46'; icon = '✓'; fw = '700';
                tag = '<span style="margin-left:auto;font-size:.68rem;font-weight:700;background:#d1fae5;color:#065f46;padding:2px 9px;border-radius:20px;white-space:nowrap;">Tu respuesta ✓</span>';
            } else if (op.es_correcta) {
                bg = '#f0fdf4'; border = '#10b981'; color = '#065f46'; icon = '✓'; fw = '700';
                tag = '<span style="margin-left:auto;font-size:.68rem;font-weight:700;background:#d1fae5;color:#065f46;padding:2px 9px;border-radius:20px;white-space:nowrap;">Correcta</span>';
            } else if (op.elegida) {
                bg = '#fff1f2'; border = '#ef4444'; color = '#b91c1c'; icon = '✗'; fw = '700';
                tag = '<span style="margin-left:auto;font-size:.68rem;font-weight:700;background:#fee2e2;color:#b91c1c;padding:2px 9px;border-radius:20px;white-space:nowrap;">Tu respuesta ✗</span>';
            }
            htmlOps += `<div style="display:flex;align-items:center;gap:9px;padding:7px 12px;border-radius:10px;background:${bg};border:1.5px solid ${border};margin-bottom:5px;">
                <span style="font-size:.9rem;color:${color};font-weight:700;flex-shrink:0;width:15px;text-align:center;">${icon}</span>
                <span style="font-size:.84rem;color:${color};font-weight:${fw};flex:1;">${_escHtml(op.texto)}</span>
                ${tag}
            </div>`;
        });

        htmlPregs += `<div style="border:1.5px solid ${borderPreg};border-radius:12px;padding:13px 15px;margin-bottom:10px;background:${bgPreg};">
            <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:9px;">
                <div style="min-width:25px;height:25px;border-radius:7px;background:${numBg};color:#fff;font-weight:800;font-size:.72rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${idx+1}</div>
                <div style="flex:1;font-weight:700;color:#1e293b;font-size:.87rem;line-height:1.4;">${_escHtml(preg.enunciado)}</div>
                <span style="font-size:1rem;font-weight:900;color:${iconColor};flex-shrink:0;">${icono}</span>
            </div>
            <div style="padding-left:33px;">${htmlOps}</div>
        </div>`;
    });

    // 3. Mostrar SweetAlert con panel de revisión
    const result = await Swal.fire({
        title: '',
        html: `<div style="text-align:left;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:13px;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.97rem;font-weight:800;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${_escHtml(detalle.intento.pasante_nombre)}</div>
                    <div style="font-size:.76rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${_escHtml(detalle.intento.examen_titulo)}</div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:1.45rem;font-weight:900;color:${pctColor};line-height:1;">${pct}%</div>
                    <div style="font-size:.7rem;color:#94a3b8;margin-top:2px;">${acertadas}/${total} correctas</div>
                </div>
            </div>
            <div style="max-height:52vh;overflow-y:auto;padding-right:2px;">${htmlPregs}</div>
        </div>`,
        width: '680px',
        padding: '22px 24px',
        showCancelButton: true,
        showConfirmButton: !revisadoYa,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="ti ti-eye-check"></i> Confirmar revisión',
        cancelButtonText: revisadoYa ? 'Cerrar' : 'Cancelar',
        footer: revisadoYa ? '<span style="color:#059669;font-size:.8rem;font-weight:600;">✓ Ya fue marcado como revisado</span>' : '',
    });

    if (!result.isConfirmed) return;

    // 4. Enviar confirmación al servidor
    try {
        const csrfMetaRev = document.querySelector('meta[name="csrf-token"]');
        const res  = await fetch(URLROOT + '/examenes/marcarRevisado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMetaRev ? csrfMetaRev.getAttribute('content') : '' },
            body: JSON.stringify({ intento_id: intentoId })
        });
        const data = await res.json();

        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo marcar como revisado.' });
            return;
        }

        const cell = document.getElementById('ev-rev-' + intentoId);
        if (cell) {
            cell.innerHTML = '<span style="display:inline-flex;align-items:center;gap:4px;background:#d1fae5;color:#065f46;border-radius:20px;padding:3px 9px;font-size:.72rem;font-weight:700;"><i class=\'ti ti-eye-check\'></i> Revisado</span>';
        }

        Swal.fire({ icon: 'success', title: 'Revisado', text: 'El pasante fue notificado.', timer: 1800, showConfirmButton: false });

    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión' });
    }
}

function _escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ── Eliminar intento del ranking ────────────────────────────────────
async function eliminarIntento(intentoId) {
    const ok = await Swal.fire({
        title: '¿Eliminar este resultado?',
        text: 'Se borrarán las respuestas y el registro del ranking. No se puede deshacer.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;

    try {
        const csrfMetaEli = document.querySelector('meta[name="csrf-token"]');
        const res  = await fetch(URLROOT + '/examenes/eliminarIntento', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMetaEli ? csrfMetaEli.getAttribute('content') : '' },
            body: JSON.stringify({ intento_id: intentoId })
        });
        const data = await res.json();

        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo eliminar.' });
            return;
        }

        const row = document.querySelector(`[data-intento-id="${intentoId}"]`);
        if (row) {
            row.style.transition = 'opacity .3s, transform .3s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-16px)';
            setTimeout(() => row.remove(), 320);
        }

        Swal.fire({ icon: 'success', title: 'Eliminado', text: 'El resultado fue eliminado del ranking.', timer: 1800, showConfirmButton: false });

    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión' });
    }
}

// ── Actualizar puntos de pregunta ───────────────────────────────────
async function actualizarPuntos(pregId, input) {
    const pts = parseInt(input.value);
    if (!pts || pts < 1 || pts > 100) { input.value = 1; return; }

    input.style.borderColor = '#94a3b8';
    try {
        const csrfMetaPts = document.querySelector('meta[name="csrf-token"]');
        const res  = await fetch(URLROOT + '/examenes/actualizarPuntos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMetaPts ? csrfMetaPts.getAttribute('content') : '' },
            body: JSON.stringify({ pregunta_id: pregId, puntos: pts })
        });
        const data = await res.json();
        if (data.success) {
            input.style.borderColor = '#10b981';
            setTimeout(() => input.style.borderColor = '#bfdbfe', 1200);
        } else {
            input.style.borderColor = '#ef4444';
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo actualizar.', timer: 2000, showConfirmButton: false });
        }
    } catch(e) {
        input.style.borderColor = '#ef4444';
    }
}
</script>
