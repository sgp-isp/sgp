<?php
// Mis Analíticas — Pasante SGP v3 Premium
$pasante  = $data['pasante']  ?? null;
$porMes   = $data['porMes']   ?? [];
$totales  = $data['totales']  ?? null;
$proRata  = $data['proRata']  ?? null;

$horasAcum = (int)($proRata->horas_mostradas ?? 0);
$horasMeta = (int)($proRata->horas_meta      ?? 1440);
$pct       = $horasMeta > 0 ? min(100, round($horasAcum / $horasMeta * 100)) : 0;

$total     = (int)($totales->total       ?? 0);
$presentes = (int)($totales->presentes   ?? 0);
$ausentes  = (int)($totales->ausentes    ?? 0);
$justif    = (int)($totales->justificados?? 0);
$pctAsist  = $total > 0 ? round(($presentes + $justif) / $total * 100) : 0;

$labels     = array_map(fn($r) => $r->mes_label, $porMes);
$dPresentes = array_map(fn($r) => (int)$r->presentes,    $porMes);
$dAusentes  = array_map(fn($r) => (int)$r->ausentes,     $porMes);
$dJustif    = array_map(fn($r) => (int)$r->justificados, $porMes);
$dTasa      = array_map(fn($r) => (int)$r->total > 0
    ? round(((int)$r->presentes + (int)$r->justificados) / (int)$r->total * 100)
    : 0,
    $porMes
);
?>
<style>
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.an-wrap { display:flex; flex-direction:column; gap:20px; animation:fadeUp .4s ease; }

.an-hero {
    background: linear-gradient(135deg,#0f172a 0%,#312e81 50%,#6366f1 100%);
    border-radius:22px; padding:28px 36px;
    display:flex; align-items:center; gap:18px;
    position:relative; overflow:hidden;
    flex-wrap:wrap;
}
.an-hero::before { content:''; position:absolute; top:-50px; right:-50px; width:220px; height:220px; background:rgba(255,255,255,.04); border-radius:50%; }

.an-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
@media(max-width:900px){ .an-kpi-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px){ .an-kpi-grid{grid-template-columns:1fr 1fr;} }

.an-kpi {
    background:#fff; border-radius:18px; padding:20px;
    box-shadow:0 2px 14px rgba(0,0,0,.07);
    display:flex; justify-content:space-between; align-items:flex-start;
    border-top:3px solid transparent; transition:transform .2s,box-shadow .2s;
}
.an-kpi:hover { transform:translateY(-4px); }
.an-kpi.g { border-top-color:#10b981; } .an-kpi.g:hover{box-shadow:0 10px 24px rgba(16,185,129,.2);}
.an-kpi.r { border-top-color:#ef4444; } .an-kpi.r:hover{box-shadow:0 10px 24px rgba(239,68,68,.2);}
.an-kpi.y { border-top-color:#f59e0b; } .an-kpi.y:hover{box-shadow:0 10px 24px rgba(245,158,11,.2);}
.an-kpi.i { border-top-color:#6366f1; } .an-kpi.i:hover{box-shadow:0 10px 24px rgba(99,102,241,.2);}
.an-kpi-lbl { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:6px; }
.an-kpi-num { font-size:2.4rem; font-weight:900; line-height:1; }
.an-kpi-ico { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }

.an-card { background:#fff; border-radius:20px; padding:24px; box-shadow:0 2px 14px rgba(0,0,0,.07); }
.an-card-ttl { font-size:1rem; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px; margin-bottom:18px; }
.an-card-ttl i { color:#6366f1; font-size:1.1rem; }

.an-two { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
@media(max-width:1100px){ .an-two{grid-template-columns:repeat(2,1fr);} }
@media(max-width:600px){ .an-two{grid-template-columns:1fr;} }

.an-bar { height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin:.3rem 0; }
.an-bar-fill { height:100%; border-radius:999px; }

/* Donut ring */
.an-ring-wrap { display:flex; flex-direction:column; align-items:center; gap:16px; }
.an-ring { position:relative; width:160px; height:160px; flex-shrink:0; }
.an-ring svg { transform:rotate(-90deg); }
.an-ring-txt { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; }

/* Mes stats bars */
.an-mes-row { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.an-mes-lbl { font-size:.78rem; font-weight:700; color:#64748b; width:60px; flex-shrink:0; text-align:right; }
.an-mes-bars { flex:1; display:flex; flex-direction:column; gap:3px; }
.an-mes-minibar { height:6px; border-radius:999px; transition:width .6s ease; }
.an-mes-count { font-size:.72rem; color:#94a3b8; width:20px; text-align:right; flex-shrink:0; }
</style>

<div class="an-wrap">

<!-- ── HERO ── -->
<div class="an-hero">
    <div style="background:rgba(255,255,255,.15);border-radius:14px;padding:14px;z-index:1;flex-shrink:0;">
        <i class="ti ti-chart-dots" style="font-size:28px;color:#fff;"></i>
    </div>
    <div style="z-index:1;">
        <h1 style="color:#fff;font-size:1.6rem;font-weight:800;margin:0 0 4px;">Mis Analíticas</h1>
        <p style="color:rgba(255,255,255,.7);margin:0;font-size:.88rem;">
            <i class="ti ti-chart-bar"></i> Estadísticas de asistencia y progreso de pasantía
        </p>
    </div>
    <div style="margin-left:auto;z-index:1;text-align:right;flex-shrink:0;">
        <div style="font-size:2rem;font-weight:900;color:#fff;line-height:1;"><?= $pctAsist ?>%</div>
        <div style="font-size:.75rem;color:rgba(255,255,255,.7);font-weight:600;">asistencia</div>
    </div>
</div>

<!-- ── KPI ── -->
<div class="an-kpi-grid">
    <?php $kpis = [
        ['lbl'=>'Días Presentes', 'num'=>$presentes, 'cls'=>'g','color'=>'#10b981','ibg'=>'#d1fae5','icon'=>'ti-circle-check'],
        ['lbl'=>'Ausencias',      'num'=>$ausentes,  'cls'=>'r','color'=>'#ef4444','ibg'=>'#fee2e2','icon'=>'ti-circle-x'],
        ['lbl'=>'Justificados',   'num'=>$justif,    'cls'=>'y','color'=>'#f59e0b','ibg'=>'#fef3c7','icon'=>'ti-file-check'],
        ['lbl'=>'% Asistencia',   'num'=>$pctAsist.'%','cls'=>'i','color'=>'#6366f1','ibg'=>'#ede9fe','icon'=>'ti-percentage'],
    ];
    foreach ($kpis as $k): ?>
    <div class="an-kpi <?= $k['cls'] ?>">
        <div>
            <div class="an-kpi-lbl"><?= $k['lbl'] ?></div>
            <div class="an-kpi-num" style="color:<?= $k['color'] ?>;"><?= $k['num'] ?></div>
        </div>
        <div class="an-kpi-ico" style="background:<?= $k['ibg'] ?>;color:<?= $k['color'] ?>;">
            <i class="ti <?= $k['icon'] ?>"></i>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── PROGRESO HORAS ── -->
<div class="an-card">
    <div class="an-card-ttl" style="margin-bottom:12px;">
        <i class="ti ti-clock"></i> Progreso de Horas
        <span style="margin-left:auto;font-size:.78rem;color:#94a3b8;font-weight:500;"><?= $horasAcum ?> de <?= $horasMeta ?> horas acumuladas</span>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:.78rem;color:#64748b;margin-bottom:5px;">
        <span>0h</span><span style="font-weight:700;color:#6366f1;"><?= $pct ?>%</span><span><?= $horasMeta ?>h</span>
    </div>
    <div class="an-bar"><div class="an-bar-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#6366f1,#2563eb,#0d9488);"></div></div>
</div>

<!-- ── DOS COLUMNAS: Donut + Barras por mes ── -->
<div class="an-two">

    <!-- Donut distribución -->
    <div class="an-card">
        <div class="an-card-ttl"><i class="ti ti-chart-donut"></i> Distribución</div>
        <?php if ($total === 0): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="ti ti-chart-off" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4;"></i>
            Sin datos
        </div>
        <?php else:
            $pctP = round($presentes / $total * 100);
            $pctA = round($ausentes  / $total * 100);
            $pctJ = round($justif    / $total * 100);
            $r = 64; $cx = 80; $cy = 80; $circ = 2*M_PI*$r;
            $dashP = $circ*($pctP/100); $dashA = $circ*($pctA/100); $dashJ = $circ*($pctJ/100);
            $gapP = $circ-$dashP; $gapA = $circ-$dashA; $gapJ = $circ-$dashJ;
            $rotP = 0;
            $rotA = $pctP/100*360;
            $rotJ = ($pctP+$pctA)/100*360;
        ?>
        <div class="an-ring-wrap">
            <div class="an-ring" style="width:160px;height:160px;">
                <svg width="160" height="160" viewBox="0 0 160 160">
                    <?php if ($pctP > 0): ?>
                    <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#10b981" stroke-width="20"
                        stroke-dasharray="<?= round($dashP,2) ?> <?= round($gapP,2) ?>"
                        transform="rotate(<?= $rotP ?> <?= $cx ?> <?= $cy ?>)" style="transform-origin:<?= $cx ?>px <?= $cy ?>px;transform:rotate(<?= $rotP ?>deg);"/>
                    <?php endif; ?>
                    <?php if ($pctA > 0): ?>
                    <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#ef4444" stroke-width="20"
                        stroke-dasharray="<?= round($dashA,2) ?> <?= round($gapA,2) ?>"
                        style="transform-origin:<?= $cx ?>px <?= $cy ?>px;transform:rotate(<?= $rotA ?>deg);"/>
                    <?php endif; ?>
                    <?php if ($pctJ > 0): ?>
                    <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#f59e0b" stroke-width="20"
                        stroke-dasharray="<?= round($dashJ,2) ?> <?= round($gapJ,2) ?>"
                        style="transform-origin:<?= $cx ?>px <?= $cy ?>px;transform:rotate(<?= $rotJ ?>deg);"/>
                    <?php endif; ?>
                </svg>
                <div class="an-ring-txt">
                    <div style="font-size:1.8rem;font-weight:900;color:#0f172a;"><?= $total ?></div>
                    <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;">total días</div>
                </div>
            </div>
            <div style="width:100%;display:flex;flex-direction:column;gap:8px;">
                <?php $items = [['Presentes',$presentes,$pctP,'#10b981'],['Ausentes',$ausentes,$pctA,'#ef4444'],['Justificados',$justif,$pctJ,'#f59e0b']];
                foreach ($items as [$lbl,$n,$pp,$clr]): ?>
                <div style="display:flex;align-items:center;gap:8px;font-size:.8rem;">
                    <span style="width:10px;height:10px;border-radius:3px;background:<?= $clr ?>;flex-shrink:0;"></span>
                    <span style="color:#64748b;flex:1;"><?= $lbl ?></span>
                    <strong style="color:<?= $clr ?>"><?= $n ?></strong>
                    <span style="color:#94a3b8;"><?= $pp ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Barras por mes -->
    <div class="an-card">
        <div class="an-card-ttl"><i class="ti ti-chart-bar"></i> Asistencia por Mes <span style="font-size:.75rem;color:#94a3b8;font-weight:500;margin-left:4px;">(últimos 6 meses)</span></div>
        <?php if (empty($porMes)): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="ti ti-chart-off" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4;"></i>
            Sin datos de asistencia
        </div>
        <?php else: ?>
        <div style="margin-bottom:12px;">
            <div style="display:flex;gap:16px;font-size:.72rem;font-weight:600;color:#64748b;margin-bottom:12px;">
                <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:3px;background:#10b981;display:inline-block;"></span>Presentes</span>
                <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:3px;background:#f59e0b;display:inline-block;"></span>Justificados</span>
                <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:10px;border-radius:3px;background:#ef4444;display:inline-block;"></span>Ausentes</span>
            </div>
            <?php
            $maxTotal = max(1, ...array_map(fn($r)=>(int)$r->total, $porMes));
            foreach ($porMes as $mes):
                $tp = (int)$mes->presentes; $ta = (int)$mes->ausentes; $tj = (int)$mes->justificados;
                $tot = $tp + $ta + $tj;
                $wP = $maxTotal > 0 ? round($tp/$maxTotal*100) : 0;
                $wA = $maxTotal > 0 ? round($ta/$maxTotal*100) : 0;
                $wJ = $maxTotal > 0 ? round($tj/$maxTotal*100) : 0;
            ?>
            <div class="an-mes-row">
                <div class="an-mes-lbl"><?= $mes->mes_label ?></div>
                <div class="an-mes-bars">
                    <?php if ($tp > 0): ?><div class="an-mes-minibar" style="width:<?= $wP ?>%;background:#10b981;" title="Presentes: <?= $tp ?>"></div><?php endif; ?>
                    <?php if ($tj > 0): ?><div class="an-mes-minibar" style="width:<?= $wJ ?>%;background:#f59e0b;" title="Justificados: <?= $tj ?>"></div><?php endif; ?>
                    <?php if ($ta > 0): ?><div class="an-mes-minibar" style="width:<?= $wA ?>%;background:#ef4444;" title="Ausentes: <?= $ta ?>"></div><?php endif; ?>
                    <?php if ($tot === 0): ?><div class="an-mes-minibar" style="width:100%;background:#f1f5f9;"></div><?php endif; ?>
                </div>
                <div class="an-mes-count"><?= $tot ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Chart.js canvas -->
        <canvas id="chartMes" height="120"></canvas>
        <?php endif; ?>
    </div>

    <!-- Tendencia de asistencia -->
    <div class="an-card">
        <div class="an-card-ttl" style="margin-bottom:10px;">
            <i class="ti ti-trending-up"></i> Tendencia
            <span style="margin-left:auto;font-size:.72rem;color:#94a3b8;font-weight:500;">% mensual</span>
        </div>
        <?php if (empty($porMes)): ?>
        <div style="text-align:center;padding:40px;color:#94a3b8;">
            <i class="ti ti-chart-off" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4;"></i>
            Sin datos
        </div>
        <?php else:
            $tasaActual   = end($dTasa) ?: 0;
            $tasaAnterior = count($dTasa) > 1 ? $dTasa[count($dTasa)-2] : $tasaActual;
            $delta        = $tasaActual - $tasaAnterior;
            $colorDelta   = $delta >= 0 ? '#10b981' : '#ef4444';
            $iconDelta    = $delta >= 0 ? 'ti-trending-up' : 'ti-trending-down';
        ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
            <div style="background:#f5f3ff;border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:8px;">
                <i class="ti <?= $iconDelta ?>" style="color:<?= $colorDelta ?>;font-size:1.1rem;"></i>
                <div>
                    <div style="font-size:1.4rem;font-weight:900;color:<?= $colorDelta ?>;line-height:1;"><?= $tasaActual ?>%</div>
                    <div style="font-size:.65rem;color:#94a3b8;font-weight:600;">Mes actual</div>
                </div>
            </div>
            <?php if ($delta !== 0): ?>
            <div style="font-size:.78rem;color:<?= $colorDelta ?>;font-weight:700;">
                <?= $delta > 0 ? '+' : '' ?><?= $delta ?>pp
            </div>
            <?php endif; ?>
        </div>
        <canvas id="chartTendencia" height="140"></canvas>
        <?php endif; ?>
    </div>
</div>

</div><!-- /an-wrap -->

<?php if (!empty($porMes)): ?>
<script>
(function(){
    const ctx = document.getElementById('chartMes');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                { label:'Presentes',    data: <?= json_encode($dPresentes) ?>, backgroundColor:'rgba(16,185,129,.8)',  borderRadius:6, borderSkipped:false },
                { label:'Justificados', data: <?= json_encode($dJustif) ?>,    backgroundColor:'rgba(245,158,11,.75)', borderRadius:6, borderSkipped:false },
                { label:'Ausentes',     data: <?= json_encode($dAusentes) ?>,  backgroundColor:'rgba(239,68,68,.75)',  borderRadius:6, borderSkipped:false },
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:true,
            plugins:{ legend:{ display:false }, tooltip:{ mode:'index', intersect:false } },
            scales:{
                x:{ grid:{display:false}, ticks:{color:'#94a3b8',font:{size:11,weight:'600'}} },
                y:{ grid:{color:'#f1f5f9'}, ticks:{color:'#94a3b8',font:{size:11},stepSize:1,precision:0}, beginAtZero:true }
            }
        }
    });
})();

// ── Tendencia ──
(function(){
    const ctx2 = document.getElementById('chartTendencia');
    if (!ctx2) return;
    const gradFill = ctx2.getContext('2d').createLinearGradient(0, 0, 0, 220);
    gradFill.addColorStop(0,   'rgba(99,102,241,.35)');
    gradFill.addColorStop(0.6, 'rgba(99,102,241,.08)');
    gradFill.addColorStop(1,   'rgba(99,102,241,0)');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: '% Asistencia',
                    data: <?= json_encode($dTasa) ?>,
                    borderColor: '#6366f1',
                    borderWidth: 3,
                    backgroundColor: gradFill,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                },
                {
                    label: 'Meta 85%',
                    data: Array(<?= count($labels) ?>).fill(85),
                    borderColor: '#94a3b8',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    pointRadius: 0,
                    fill: false,
                    tension: 0,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y + '%'
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11, weight: '600' } } },
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: { color: '#94a3b8', font: { size: 11 }, callback: v => v + '%' },
                    min: 0, max: 100, stepSize: 20
                }
            }
        }
    });
})();
</script>
<?php endif; ?>
