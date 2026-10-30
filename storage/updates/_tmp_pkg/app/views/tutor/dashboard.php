<?php
// Vista — Tutor Dashboard Premium v3
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<?php
$user_name          = $data['user_name']          ?? 'Tutor';
$totalPasantes      = $data['totalPasantes']      ?? 0;
$pasantesActivos    = $data['pasantesActivos']    ?? 0;
$evaluacionesPend   = $data['evaluacionesPendientes'] ?? 0;
$horasSupervisadas  = $data['horasSupervisadas']  ?? 0;
$presentesHoy       = $data['presentesHoy']       ?? 0;
$sinMarcarHoy       = $data['sinMarcarHoy']       ?? 0;
$proximosVencer     = $data['proximosVencer']     ?? 0;
$enRiesgo           = $data['enRiesgo']           ?? 0;
$tendencia          = $data['tendencia']          ?? [];
$alertas            = $data['alertas']            ?? [];
$misPasantes        = $data['misPasantes']        ?? [];
$nombreCorto        = htmlspecialchars(explode(' ', $user_name)[0]);
$porcHoy            = $pasantesActivos > 0 ? round($presentesHoy / $pasantesActivos * 100) : 0;
?>
<style>
/* ══ TUTOR DASHBOARD PREMIUM v3 ══ */
.td-wrap { display:flex; flex-direction:column; gap:20px; width:100%; }

/* ── Hero ── */
.td-hero {
    background:linear-gradient(135deg,var(--color-primary, #0f172a) 0%,var(--gradient-mid, #1e3a8a) 45%,var(--color-accent-brand, #3b82f6) 100%);
    border-radius:24px; padding:0; overflow:hidden; position:relative;
    box-shadow:0 20px 60px rgba(30,58,138,.28);
}
.td-hero::before {
    content:''; position:absolute; inset:0;
    background:
        radial-gradient(circle at 15% 50%, rgba(99,102,241,.22) 0%, transparent 50%),
        radial-gradient(circle at 85% 20%, rgba(16,185,129,.12) 0%, transparent 40%);
}
.td-hero::after {
    content:''; position:absolute; top:-80px; right:-80px;
    width:320px; height:320px; border-radius:50%;
    border:1px solid rgba(255,255,255,.06);
    box-shadow:0 0 0 60px rgba(255,255,255,.03),0 0 0 120px rgba(255,255,255,.015);
}
.td-hero-inner { position:relative; z-index:2; display:flex; align-items:stretch; flex-wrap:wrap; }
.td-hero-left  { display:flex; align-items:center; gap:18px; padding:28px 36px; flex:1; min-width:260px; }
.td-hero-icon  {
    width:62px; height:62px; border-radius:18px; flex-shrink:0;
    background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.18);
    display:flex; align-items:center; justify-content:center;
    backdrop-filter:blur(10px);
}
.td-hero-tag {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(16,185,129,.2); border:1px solid rgba(16,185,129,.35);
    color:#6ee7b7; font-size:.7rem; font-weight:700; letter-spacing:.04em;
    padding:3px 9px; border-radius:20px; margin-bottom:5px;
}
.td-hero-title { color:#fff; font-size:1.75rem; font-weight:800; margin:0 0 2px; letter-spacing:-.02em; }
.td-hero-sub   { color:rgba(255,255,255,.6); font-size:.85rem; margin:0; }
.td-hero-right {
    display:flex; align-items:center; padding:22px 28px;
    border-left:1px solid rgba(255,255,255,.08);
}
.td-datetime {
    background:rgba(0,0,0,.2); backdrop-filter:blur(12px);
    border:1px solid rgba(255,255,255,.12); border-radius:14px;
    padding:12px 20px; display:flex; align-items:center; gap:18px; color:#fff;
}
.td-dt-item { display:flex; align-items:center; gap:8px; font-size:.88rem; }
.td-dt-item i { color:rgba(255,255,255,.6); }
.td-dt-sep { width:1px; height:22px; background:rgba(255,255,255,.15); }
.td-dt-time { font-size:1.05rem; font-weight:700; letter-spacing:.04em; }

/* ── KPI Grid ── */
.td-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
@media(max-width:960px){ .td-kpis{grid-template-columns:repeat(2,1fr);} }
@media(max-width:500px){ .td-kpis{grid-template-columns:1fr;} }

.td-kpi {
    border-radius:18px; padding:20px 22px; position:relative; overflow:hidden;
    display:flex; flex-direction:column; gap:12px;
    box-shadow:0 4px 20px rgba(0,0,0,.07);
    transition:transform .2s, box-shadow .2s; cursor:default;
}
.td-kpi:hover { transform:translateY(-4px); }
.td-kpi.kp-indigo{ background:linear-gradient(135deg,#f5f3ff,#ede9fe); border:1px solid #c4b5fd; }
.td-kpi.kp-green { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1px solid #bbf7d0; }
.td-kpi.kp-amber { background:linear-gradient(135deg,#fffbeb,#fef3c7); border:1px solid #fde68a; }
.td-kpi.kp-red   { background:linear-gradient(135deg,#fff1f2,#fee2e2); border:1px solid #fecaca; }
.td-kpi:hover.kp-indigo{ box-shadow:0 12px 30px rgba(99,102,241,.18); }
.td-kpi:hover.kp-green { box-shadow:0 12px 30px rgba(16,185,129,.16); }
.td-kpi:hover.kp-amber { box-shadow:0 12px 30px rgba(245,158,11,.15); }
.td-kpi:hover.kp-red   { box-shadow:0 12px 30px rgba(239,68,68,.14); }
.td-kpi::before {
    content:''; position:absolute; width:90px; height:90px;
    border-radius:50%; top:-18px; right:-18px; opacity:.35;
}
.kp-indigo::before{ background:#a5b4fc; }
.kp-green::before { background:#86efac; }
.kp-amber::before { background:#fcd34d; }
.kp-red::before   { background:#fca5a5; }
.td-kpi-top { display:flex; align-items:center; justify-content:space-between; }
.td-kpi-lbl { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
.kp-indigo .td-kpi-lbl{ color:#4338ca; }
.kp-green  .td-kpi-lbl{ color:#15803d; }
.kp-amber  .td-kpi-lbl{ color:#b45309; }
.kp-red    .td-kpi-lbl{ color:#b91c1c; }
.td-kpi-badge { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.95rem; }
.kp-indigo .td-kpi-badge{ background:rgba(99,102,241,.15); color:#6366f1; }
.kp-green  .td-kpi-badge{ background:rgba(16,185,129,.15); color:#10b981; }
.kp-amber  .td-kpi-badge{ background:rgba(245,158,11,.15); color:#f59e0b; }
.kp-red    .td-kpi-badge{ background:rgba(239,68,68,.15);  color:#ef4444; }
.td-kpi-val { font-size:2.4rem; font-weight:900; line-height:1; letter-spacing:-.04em; }
.kp-indigo .td-kpi-val{ color:#4338ca; }
.kp-green  .td-kpi-val{ color:#15803d; }
.kp-amber  .td-kpi-val{ color:#b45309; }
.kp-red    .td-kpi-val{ color:#dc2626; }
.td-kpi-sub { font-size:.73rem; color:#64748b; display:flex; align-items:center; gap:5px; }
.td-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.td-kpi-bar { height:4px; border-radius:99px; background:rgba(0,0,0,.08); overflow:hidden; }
.td-kpi-bar-fill { height:100%; border-radius:99px; transition:width .6s; }
.kp-indigo .td-kpi-bar-fill{ background:linear-gradient(90deg,#818cf8,#6366f1); }
.kp-green  .td-kpi-bar-fill{ background:linear-gradient(90deg,#34d399,#10b981); }
.kp-amber  .td-kpi-bar-fill{ background:linear-gradient(90deg,#fbbf24,#f59e0b); }
.kp-red    .td-kpi-bar-fill{ background:linear-gradient(90deg,#f87171,#ef4444); }

/* ── Mid row: tendencia + alertas ── */
.td-mid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:780px){ .td-mid{ grid-template-columns:1fr; } }

.td-panel {
    background:#fff; border-radius:18px;
    border:1px solid #f1f5f9;
    box-shadow:0 2px 14px rgba(0,0,0,.05); overflow:hidden;
}
.td-panel-hd {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 20px; background:#fafbfd;
    border-bottom:1px solid #f1f5f9;
}
.td-panel-title { font-size:.9rem; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:7px; }
.td-panel-title i { color:#6366f1; }
.td-panel-bd { padding:18px 20px; }

/* Tendencia semanal */
.td-chart-wrap { position:relative; height:160px; }
.td-tendency-pills { display:flex; gap:6px; flex-wrap:wrap; margin-top:14px; padding-top:12px; border-top:1px solid #f1f5f9; }
.td-pill { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:20px; font-size:.74rem; font-weight:600; }
.td-pill-g { background:#dcfce7; color:#15803d; }
.td-pill-r { background:#fee2e2; color:#b91c1c; }
.td-pill-a { background:#fef3c7; color:#b45309; }
.td-pill-gr{ background:#f1f5f9; color:#64748b; }

/* Alertas */
.td-alert { display:flex; align-items:flex-start; gap:10px; padding:10px 0; border-bottom:1px solid #f8fafc; }
.td-alert:last-child { border-bottom:none; padding-bottom:0; }
.td-alert-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.95rem; flex-shrink:0; }
.ai-red   { background:#fee2e2; color:#dc2626; }
.ai-amber { background:#fef3c7; color:#b45309; }
.ai-blue  { background:#dbeafe; color:#1d4ed8; }
.td-alert-name { font-size:.83rem; font-weight:600; color:#1e293b; line-height:1.3; }
.td-alert-det  { font-size:.75rem; color:#64748b; margin-top:2px; }

/* ── Acciones rápidas ── */
.td-quick { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
@media(max-width:700px){ .td-quick{ grid-template-columns:repeat(2,1fr); } }
.td-quick-btn {
    background:#fff; border:1px solid #f1f5f9; border-radius:14px;
    padding:14px 16px; display:flex; align-items:center; gap:12px;
    text-decoration:none; cursor:pointer;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
    transition:all .18s;
}
.td-quick-btn:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(0,0,0,.1); border-color:#c4b5fd; }
.td-quick-ico { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex-shrink:0; }
.td-quick-lbl { font-size:.82rem; font-weight:600; color:#1e293b; line-height:1.25; }
.td-quick-sub { font-size:.72rem; color:#94a3b8; }

/* ── Tabla pasantes ── */
.td-table-wrap { overflow-x:auto; }
.td-table { width:100%; border-collapse:collapse; min-width:620px; }
.td-table thead tr { background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.td-table th { padding:.65rem 1rem; font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; text-align:left; }
.td-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background .12s; }
.td-table tbody tr:last-child { border-bottom:none; }
.td-table tbody tr:hover { background:#f8faff; }
.td-table tbody tr.tr-risk { background:rgba(254,243,199,.35); }
.td-table tbody tr.tr-risk:hover { background:rgba(254,243,199,.6); }
.td-table td { padding:.7rem 1rem; font-size:.86rem; color:#1e293b; vertical-align:middle; }
.td-av { width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:800; color:#fff; background:linear-gradient(135deg,#6366f1,#4f46e5); flex-shrink:0; }
.td-av-fin { background:linear-gradient(135deg,#94a3b8,#64748b); }
.td-pct-bar { height:5px; background:#e2e8f0; border-radius:99px; overflow:hidden; margin-top:3px; }
.td-pct-fill { height:100%; border-radius:99px; }
.td-fill-ok   { background:linear-gradient(90deg,#34d399,#10b981); }
.td-fill-warn { background:linear-gradient(90deg,#fbbf24,#f59e0b); }
.td-fill-risk { background:linear-gradient(90deg,#f87171,#ef4444); }
.td-fill-done { background:linear-gradient(90deg,#818cf8,#6366f1); }
.td-badge { display:inline-flex; align-items:center; gap:4px; padding:.2rem .7rem; border-radius:999px; font-size:.72rem; font-weight:700; }
.td-badge::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
.td-badge-activo     { background:#dcfce7; color:#15803d; } .td-badge-activo::before    { background:#10b981; }
.td-badge-pendiente  { background:#fef3c7; color:#b45309; } .td-badge-pendiente::before  { background:#f59e0b; }
.td-badge-finalizado { background:#ede9fe; color:#4338ca; } .td-badge-finalizado::before { background:#6366f1; }
.td-badge-retirado   { background:#fee2e2; color:#b91c1c; } .td-badge-retirado::before   { background:#ef4444; }
.td-badge-risk       { background:#fef3c7; color:#b45309; font-size:.65rem; padding:.15rem .5rem; margin-left:4px; }
.td-act-btn { display:inline-flex; align-items:center; gap:3px; padding:.28rem .65rem; border-radius:7px; font-size:.74rem; font-weight:600; border:none; cursor:pointer; text-decoration:none; transition:all .14s; }
.td-act-cal { background:#e0f2fe; color:#0369a1; } .td-act-cal:hover { background:#0369a1; color:#fff; }
.td-act-star{ background:#ede9fe; color:#6366f1; } .td-act-star:hover{ background:#6366f1; color:#fff; }
.td-empty { text-align:center; padding:3rem 2rem; color:#94a3b8; }
.td-empty i { font-size:2.5rem; display:block; margin-bottom:10px; opacity:.3; }
</style>

<div class="td-wrap">

<!-- ════ HERO BANNER ════ -->
<div class="td-hero">
    <div class="td-hero-inner">
        <div class="td-hero-left">
            <div class="td-hero-icon">
                <i class="ti ti-school" style="font-size:30px;color:#fff;"></i>
            </div>
            <div>
                <div class="td-hero-tag"><i class="ti ti-shield-check" style="font-size:.85rem;"></i> Panel de Control · Tutor</div>
                <h1 class="td-hero-title">¡Bienvenido, <?= $nombreCorto ?>!</h1>
                <p class="td-hero-sub"><i class="ti ti-users" style="margin-right:4px;"></i> Supervisando <?= $pasantesActivos ?> pasante<?= $pasantesActivos !== 1 ? 's' : '' ?> activo<?= $pasantesActivos !== 1 ? 's' : '' ?></p>
            </div>
        </div>
        <div class="td-hero-right">
            <div class="td-datetime">
                <div class="td-dt-item"><i class="ti ti-calendar"></i><span id="tdDate"></span></div>
                <div class="td-dt-sep"></div>
                <div class="td-dt-item"><i class="ti ti-clock"></i><span id="tdTime" class="td-dt-time"></span></div>
            </div>
        </div>
    </div>
</div>

<!-- ════ KPI CARDS ════ -->
<div class="td-kpis">

    <a href="<?= URLROOT ?>/tutor/pasantes" style="text-decoration:none;">
    <div class="td-kpi kp-indigo">
        <div class="td-kpi-top">
            <span class="td-kpi-lbl">Mis Pasantes</span>
            <div class="td-kpi-badge"><i class="ti ti-users-group"></i></div>
        </div>
        <div class="td-kpi-val"><?= $totalPasantes ?></div>
        <div class="td-kpi-sub"><span class="td-dot" style="background:#6366f1;"></span><?= $pasantesActivos ?> activos</div>
        <div class="td-kpi-bar"><div class="td-kpi-bar-fill" style="width:<?= $totalPasantes > 0 ? round($pasantesActivos/$totalPasantes*100) : 0 ?>%;"></div></div>
    </div>
    </a>

    <a href="<?= URLROOT ?>/tutor/asistencias?vista=diaria" style="text-decoration:none;">
    <div class="td-kpi kp-green">
        <div class="td-kpi-top">
            <span class="td-kpi-lbl">Asistencia Hoy</span>
            <div class="td-kpi-badge"><i class="ti ti-calendar-check"></i></div>
        </div>
        <div class="td-kpi-val"><?= $presentesHoy ?><span style="font-size:1.1rem;font-weight:500;opacity:.6;"> / <?= $pasantesActivos ?></span></div>
        <div class="td-kpi-sub">
            <?php if ($sinMarcarHoy > 0): ?>
            <span class="td-dot" style="background:#f59e0b;"></span><?= $sinMarcarHoy ?> sin marcar
            <?php else: ?>
            <span class="td-dot" style="background:#10b981;"></span>Todos marcados
            <?php endif; ?>
        </div>
        <div class="td-kpi-bar"><div class="td-kpi-bar-fill" style="width:<?= $porcHoy ?>%;"></div></div>
    </div>
    </a>

    <div class="td-kpi kp-amber">
        <div class="td-kpi-top">
            <span class="td-kpi-lbl">En Riesgo</span>
            <div class="td-kpi-badge"><i class="ti ti-alert-triangle"></i></div>
        </div>
        <div class="td-kpi-val"><?= $enRiesgo ?></div>
        <div class="td-kpi-sub"><span class="td-dot" style="background:#f59e0b;"></span>Progreso &lt; calendario</div>
        <div class="td-kpi-bar"><div class="td-kpi-bar-fill" style="width:<?= $pasantesActivos > 0 ? round($enRiesgo/$pasantesActivos*100) : 0 ?>%;"></div></div>
    </div>

    <div class="td-kpi kp-red">
        <div class="td-kpi-top">
            <span class="td-kpi-lbl">Próx. a Vencer</span>
            <div class="td-kpi-badge"><i class="ti ti-clock-exclamation"></i></div>
        </div>
        <div class="td-kpi-val"><?= $proximosVencer ?></div>
        <div class="td-kpi-sub"><span class="td-dot" style="background:#ef4444;"></span>En los próximos 30 días</div>
        <div class="td-kpi-bar"><div class="td-kpi-bar-fill" style="width:<?= $pasantesActivos > 0 ? round($proximosVencer/$pasantesActivos*100) : 0 ?>%;"></div></div>
    </div>

</div>

<!-- ════ ACCIONES RÁPIDAS ════ -->
<div class="td-quick">
    <a href="<?= URLROOT ?>/tutor/asistencias?vista=diaria" class="td-quick-btn">
        <div class="td-quick-ico" style="background:#dcfce7;color:#15803d;"><i class="ti ti-clock-check"></i></div>
        <div><div class="td-quick-lbl">Marcar hoy</div><div class="td-quick-sub">Registrar asistencia</div></div>
    </a>
    <a href="<?= URLROOT ?>/evaluaciones" class="td-quick-btn">
        <div class="td-quick-ico" style="background:#ede9fe;color:#6366f1;"><i class="ti ti-star"></i></div>
        <div><div class="td-quick-lbl">Evaluaciones</div><div class="td-quick-sub"><?= $evaluacionesPend ?> pendiente<?= $evaluacionesPend !== 1 ? 's' : '' ?></div></div>
    </a>
    <a href="<?= URLROOT ?>/tutor/puntualidad" class="td-quick-btn">
        <div class="td-quick-ico" style="background:#dbeafe;color:#1d4ed8;"><i class="ti ti-chart-bar"></i></div>
        <div><div class="td-quick-lbl">Puntualidad</div><div class="td-quick-sub">Ranking del mes</div></div>
    </a>
    <a href="<?= URLROOT ?>/tutor/asistencias?vista=semanal" class="td-quick-btn">
        <div class="td-quick-ico" style="background:#fef3c7;color:#b45309;"><i class="ti ti-calendar-week"></i></div>
        <div><div class="td-quick-lbl">Vista Semanal</div><div class="td-quick-sub">Resumen de la semana</div></div>
    </a>
</div>

<!-- ════ TENDENCIA + ALERTAS ════ -->
<div class="td-mid">

    <!-- Tendencia semanal -->
    <div class="td-panel">
        <div class="td-panel-hd">
            <div class="td-panel-title"><i class="ti ti-chart-bar"></i> Tendencia de Asistencia</div>
            <span style="font-size:.74rem;color:#94a3b8;">Últimas 4 semanas</span>
        </div>
        <div class="td-panel-bd">
            <?php
            // Preparar datos para Chart.js
            $chartLabels = [];
            $chartPcts   = [];
            $chartColors = [];
            foreach ($tendencia as $sem) {
                $total = (int)($sem->dias_totales ?? 0);
                $asist = (int)($sem->dias_asistidos ?? 0);
                $pct   = $total > 0 ? round($asist / $total * 100) : 0;
                $chartLabels[] = !empty($sem->inicio_semana) ? 'S-' . date('W', strtotime($sem->inicio_semana)) : '—';
                $chartPcts[]   = $pct;
                $chartColors[] = $pct >= 80 ? '#10b981' : ($pct >= 60 ? '#f59e0b' : '#ef4444');
            }
            // Completar hasta 4 puntos si faltan semanas
            while (count($chartLabels) < 4) { $chartLabels[] = '—'; $chartPcts[] = 0; $chartColors[] = '#e2e8f0'; }
            ?>
            <div class="td-chart-wrap">
                <canvas id="chartTendencia"></canvas>
            </div>
            <div class="td-tendency-pills">
                <span class="td-pill td-pill-g"><i class="ti ti-circle-check" style="font-size:.85rem;"></i> ≥80% Bueno</span>
                <span class="td-pill td-pill-a"><i class="ti ti-alert-circle" style="font-size:.85rem;"></i> 60–79% Regular</span>
                <span class="td-pill td-pill-r"><i class="ti ti-circle-x"     style="font-size:.85rem;"></i> &lt;60% Crítico</span>
            </div>
            <script>
            (function(){
                const labels = <?= json_encode($chartLabels) ?>;
                const pcts   = <?= json_encode($chartPcts) ?>;
                const bgColors = <?= json_encode($chartColors) ?>;
                // Colores con transparencia para las barras
                const bgAlpha = bgColors.map(c => c + 'cc');

                function loadChart(){
                    if(typeof Chart === 'undefined'){ setTimeout(loadChart, 100); return; }
                    const ctx = document.getElementById('chartTendencia');
                    if(!ctx) return;
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    type: 'bar',
                                    label: 'Asistencia %',
                                    data: pcts,
                                    backgroundColor: bgAlpha,
                                    borderColor: bgColors,
                                    borderWidth: 2,
                                    borderRadius: 8,
                                    borderSkipped: false,
                                    order: 2,
                                },
                                {
                                    type: 'line',
                                    label: 'Tendencia',
                                    data: pcts,
                                    borderColor: '#6366f1',
                                    borderWidth: 2.5,
                                    pointBackgroundColor: '#6366f1',
                                    pointRadius: 5,
                                    pointHoverRadius: 7,
                                    tension: 0.35,
                                    fill: false,
                                    order: 1,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: ctx => ' ' + ctx.parsed.y + '%'
                                    },
                                    backgroundColor: '#1e293b',
                                    titleColor: '#94a3b8',
                                    bodyColor: '#fff',
                                    padding: 10,
                                    cornerRadius: 8,
                                }
                            },
                            scales: {
                                y: {
                                    min: 0, max: 100,
                                    ticks: {
                                        callback: v => v + '%',
                                        font: { size: 11 },
                                        color: '#94a3b8',
                                        stepSize: 25,
                                    },
                                    grid: { color: '#f1f5f9', drawBorder: false }
                                },
                                x: {
                                    ticks: { font: { size: 11 }, color: '#64748b' },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }
                loadChart();
            })();
            </script>
        </div>
    </div>

    <!-- Alertas inteligentes -->
    <div class="td-panel">
        <div class="td-panel-hd">
            <div class="td-panel-title"><i class="ti ti-bell-ringing"></i> Alertas</div>
            <?php if (!empty($alertas)): ?>
            <span style="background:#fee2e2;color:#b91c1c;font-size:.72rem;font-weight:700;padding:3px 8px;border-radius:20px;"><?= count($alertas) ?></span>
            <?php endif; ?>
        </div>
        <div class="td-panel-bd">
            <?php if (empty($alertas)): ?>
            <div style="text-align:center;padding:1.5rem;color:#94a3b8;">
                <i class="ti ti-circle-check" style="font-size:2rem;display:block;margin-bottom:8px;color:#10b981;opacity:.6;"></i>
                <p style="font-size:.85rem;">Sin alertas pendientes</p>
            </div>
            <?php else: ?>
            <?php foreach ($alertas as $al):
                $iconos = ['riesgo'=>['ai-amber','ti-alert-triangle'],'sin_eval'=>['ai-blue','ti-star'],'vence'=>['ai-red','ti-clock-exclamation']];
                [$cls,$ic] = $iconos[$al['tipo']] ?? ['ai-blue','ti-info-circle'];
            ?>
            <div class="td-alert">
                <div class="td-alert-icon <?= $cls ?>"><i class="ti <?= $ic ?>"></i></div>
                <div>
                    <a href="<?= URLROOT ?>/tutor/perfil/<?= $al['id'] ?>" class="td-alert-name" style="text-decoration:none;color:#1e293b;"><?= htmlspecialchars($al['nombre']) ?></a>
                    <div class="td-alert-det"><?= htmlspecialchars($al['detalle']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ════ TABLA PASANTES ════ -->
<div class="td-panel">
    <div class="td-panel-hd">
        <div class="td-panel-title"><i class="ti ti-users"></i> Mis Pasantes Asignados</div>
        <a href="<?= URLROOT ?>/tutor/pasantes" style="font-size:.78rem;color:#6366f1;font-weight:600;text-decoration:none;">Ver todos →</a>
    </div>
    <?php if (empty($misPasantes)): ?>
    <div class="td-empty"><i class="ti ti-users-off"></i><p>No tienes pasantes asignados.</p></div>
    <?php else: ?>
    <div class="td-table-wrap">
    <table class="td-table">
        <thead>
            <tr>
                <th>Pasante</th>
                <th>Progreso</th>
                <th>Eval.</th>
                <th>Estado</th>
                <th style="text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($misPasantes as $p):
            $nombre   = trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''));
            $ini      = strtoupper(substr($p->apellidos ?? 'P', 0, 1) . substr($p->nombres ?? 'P', 0, 1));
            $pct      = (float)($p->progreso_pct ?? 0);
            $calPct   = (float)($p->progreso_cal_pct ?? 0);
            $brecha   = $calPct - $pct;
            $esRiesgo = ($p->estado_pasantia === 'Activo') && $brecha > 20;
            $esFin    = in_array($p->estado_pasantia, ['Finalizado','Retirado']);
            $fillCls  = $pct >= 90 ? 'td-fill-done' : ($esRiesgo ? 'td-fill-risk' : ($pct >= 60 ? 'td-fill-ok' : 'td-fill-warn'));
            $bdgKey   = strtolower($p->estado_pasantia ?? 'default');
            $bdgClass = 'td-badge-' . ($bdgKey === 'sin asignar' ? 'pendiente' : $bdgKey);
        ?>
        <tr class="<?= $esRiesgo ? 'tr-risk' : '' ?>">
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="td-av <?= $esFin ? 'td-av-fin' : '' ?>"><?= $ini ?></div>
                    <div>
                        <div style="font-weight:600;font-size:.86rem;"><?= htmlspecialchars($nombre) ?></div>
                        <div style="font-size:.74rem;color:#94a3b8;">V-<?= htmlspecialchars($p->cedula ?? '—') ?></div>
                    </div>
                </div>
            </td>
            <td style="min-width:140px;">
                <div style="display:flex;justify-content:space-between;font-size:.76rem;color:#64748b;margin-bottom:3px;">
                    <span><?= (int)($p->horas_acumuladas ?? 0) ?>h / <?= (int)($p->horas_meta ?? 1440) ?>h</span>
                    <span style="font-weight:700;color:<?= $esRiesgo ? '#b45309' : '#6366f1' ?>;"><?= $pct ?>%<?= $esRiesgo ? ' ⚠' : '' ?></span>
                </div>
                <div class="td-pct-bar"><div class="td-pct-fill <?= $fillCls ?>" style="width:<?= $pct ?>%;"></div></div>
            </td>
            <td style="color:#1e293b;font-weight:600;font-size:.86rem;">
                <?= $p->promedio_eval ? number_format($p->promedio_eval, 1) . '<span style="font-size:.72rem;color:#94a3b8;">/5</span>' : '<span style="color:#94a3b8;">—</span>' ?>
            </td>
            <td>
                <span class="td-badge <?= $bdgClass ?>"><?= htmlspecialchars($p->estado_pasantia ?? 'N/A') ?></span>
                <?php if ($esRiesgo): ?><span class="td-badge td-badge-risk">Riesgo</span><?php endif; ?>
            </td>
            <td style="text-align:center;">
                <div style="display:flex;gap:5px;justify-content:center;">
                    <a href="<?= URLROOT ?>/asistencias/almanaque/<?= $p->pasante_id ?>" class="td-act-btn td-act-cal" title="Almanaque">
                        <i class="ti ti-calendar-stats"></i>
                    </a>
                    <a href="<?= URLROOT ?>/evaluaciones" class="td-act-btn td-act-star" title="Evaluar">
                        <i class="ti ti-star"></i>
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

</div><!-- /.td-wrap -->

<script>
(function(){
    const DIAS  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    function tick(){
        const n = new Date();
        const d = document.getElementById('tdDate');
        const t = document.getElementById('tdTime');
        if(d) d.textContent = DIAS[n.getDay()] + ', ' + n.getDate() + ' de ' + MESES[n.getMonth()] + ' ' + n.getFullYear();
        if(t) t.textContent = n.toLocaleTimeString('es-ES',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true}).toUpperCase();
    }
    tick(); setInterval(tick, 1000);
})();
</script>
