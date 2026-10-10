<?php
/* ══════════════════════════════════════════════════════
   Mis Evaluaciones — Premium Bento Grid v2
   Variables: $pasante, $evaluaciones[]
   Campos evaluacion: id, lapso_academico, promedio_final,
                      observaciones, fecha_formateada, tutor_nombre
   ══════════════════════════════════════════════════════ */
$pasante     = $data['pasante']      ?? null;
$evaluaciones= $data['evaluaciones'] ?? [];

/* ── helpers ── */
// Escala real del sistema: promedio de 14 criterios de 1–5 estrellas
function notaColor(float $n): string {
    if ($n >= 4.5) return '#10b981';
    if ($n >= 3.5) return '#f59e0b';
    if ($n >= 2.5) return '#f97316';
    return '#ef4444';
}
function notaGradient(float $n): string {
    if ($n >= 4.5) return 'linear-gradient(135deg,#064e3b,#059669)';
    if ($n >= 3.5) return 'linear-gradient(135deg,#78350f,#d97706)';
    if ($n >= 2.5) return 'linear-gradient(135deg,#7c2d12,#f97316)';
    return 'linear-gradient(135deg,#7f1d1d,#ef4444)';
}
function notaLabel(float $n): string {
    if ($n >= 4.5) return 'Excelente';
    if ($n >= 3.5) return 'Bueno';
    if ($n >= 2.5) return 'Regular';
    return 'Deficiente';
}
function notaIcon(float $n): string {
    if ($n >= 4.5) return 'ti-star-filled';
    if ($n >= 3.5) return 'ti-star-half-filled';
    if ($n >= 2.5) return 'ti-star';
    return 'ti-star-off';
}

/* ── estadísticas derivadas ── */
$totalEv   = count($evaluaciones);
$promedios = array_map(fn($e) => (float)($e->promedio_final ?? 0), $evaluaciones);
$promGlobal= $totalEv ? round(array_sum($promedios) / $totalEv, 2) : 0;
$mejorNota = $totalEv ? max($promedios) : 0;
$ultima    = $totalEv ? $evaluaciones[0] : null; // ya ordenadas DESC

/* ── arco SVG para el indicador de desempeño ── */
$r     = 52;
$circ  = 2 * M_PI * $r;
$pct   = min(100, ($promGlobal / 5) * 100);
$dash  = round($pct / 100 * $circ, 2);
$gap   = round($circ - $dash, 2);
$ringColor = notaColor($promGlobal);
?>
<style>
/* ── keyframes ── */
@keyframes evFadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
@keyframes evPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.04)}}
@keyframes evGlow{0%,100%{box-shadow:0 0 0 0 rgba(37,99,235,.35)}50%{box-shadow:0 0 0 10px rgba(37,99,235,0)}}
@keyframes dashSpin{from{stroke-dashoffset:<?= round($circ, 2) ?>}to{stroke-dashoffset:<?= round($circ - $dash, 2) ?>}}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}

/* ── layout ── */
.ev-wrap{display:flex;flex-direction:column;gap:22px;animation:evFadeUp .5s ease both}

/* ── hero ── */
.ev-hero{
    background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);
    border-radius:24px;padding:32px 36px;position:relative;overflow:hidden;
    display:flex;align-items:center;gap:20px;flex-wrap:wrap;
    box-shadow:0 8px 32px rgba(30,58,138,.35);
}
.ev-hero::before{
    content:'';position:absolute;top:-40px;right:-40px;
    width:220px;height:220px;border-radius:50%;
    background:radial-gradient(circle,rgba(96,165,250,.18) 0%,transparent 70%);
    pointer-events:none;
}
.ev-hero::after{
    content:'';position:absolute;bottom:-30px;left:30%;
    width:160px;height:160px;border-radius:50%;
    background:radial-gradient(circle,rgba(255,255,255,.06) 0%,transparent 70%);
    pointer-events:none;
}
.ev-hero-icon{
    background:rgba(255,255,255,.15);backdrop-filter:blur(4px);
    border:1px solid rgba(255,255,255,.2);border-radius:18px;
    padding:16px;z-index:1;animation:evPulse 3s ease-in-out infinite;
}
.ev-hero-text{z-index:1;flex:1}
.ev-hero-badge{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
    color:rgba(255,255,255,.9);border-radius:999px;
    padding:4px 12px;font-size:.75rem;font-weight:700;
    letter-spacing:.4px;text-transform:uppercase;margin-bottom:8px;
}

/* ── bento grid KPI row ── */
.ev-kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
@media(max-width:768px){.ev-kpi-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:460px){.ev-kpi-row{grid-template-columns:1fr}}

.ev-kpi{
    background:#fff;border-radius:18px;padding:20px 22px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);border:1px solid #f1f5f9;
    position:relative;overflow:hidden;
    animation:evFadeUp .5s ease both;
    transition:transform .2s,box-shadow .2s;
}
.ev-kpi:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.ev-kpi-accent{
    position:absolute;top:0;left:0;right:0;height:3px;border-radius:18px 18px 0 0;
}
.ev-kpi-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:6px}
.ev-kpi-val{font-size:1.8rem;font-weight:900;line-height:1;margin-bottom:4px}
.ev-kpi-sub{font-size:.75rem;color:#94a3b8}

/* ── bento main ── */
.ev-bento{display:grid;grid-template-columns:280px 1fr;gap:18px;align-items:start}
@media(max-width:900px){.ev-bento{grid-template-columns:1fr}}

/* ── desempeño card ── */
.ev-perf{
    background:#fff;border-radius:20px;padding:28px 24px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);border:1px solid #f1f5f9;
    display:flex;flex-direction:column;align-items:center;gap:20px;
    animation:evFadeUp .55s ease both;
}
.ev-ring-wrap{position:relative;display:flex;align-items:center;justify-content:center}
.ev-ring-center{
    position:absolute;text-align:center;
    display:flex;flex-direction:column;align-items:center;
}
.ev-ring-score{font-size:2rem;font-weight:900;line-height:1}
.ev-ring-denom{font-size:.7rem;color:#94a3b8;font-weight:600;letter-spacing:.5px}
.ev-ring-label{font-size:.78rem;font-weight:700;color:#64748b;margin-top:2px}

/* ── lista evaluaciones ── */
.ev-list-card{
    background:#fff;border-radius:20px;padding:24px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);border:1px solid #f1f5f9;
    display:flex;flex-direction:column;gap:0;
    animation:evFadeUp .6s ease both;
}
.ev-list-header{
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid #f1f5f9;
}
.ev-item{
    display:flex;align-items:stretch;gap:0;
    border-radius:14px;overflow:hidden;margin-bottom:12px;
    border:1px solid #f1f5f9;
    transition:transform .18s,box-shadow .18s;
    animation:evFadeUp .5s ease both;
}
.ev-item:last-child{margin-bottom:0}
.ev-item:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.08)}
.ev-item-side{
    width:72px;flex-shrink:0;display:flex;flex-direction:column;
    align-items:center;justify-content:center;gap:4px;padding:16px 8px;
    color:#fff;
}
.ev-item-score{font-size:1.5rem;font-weight:900;line-height:1}
.ev-item-base{font-size:.65rem;font-weight:600;opacity:.75}
.ev-item-body{flex:1;padding:14px 18px;min-width:0}
.ev-item-lapso{font-size:.92rem;font-weight:700;color:#1e293b;margin-bottom:4px}
.ev-item-meta{display:flex;gap:14px;flex-wrap:wrap;font-size:.78rem;color:#94a3b8;margin-bottom:8px}
.ev-item-obs{
    font-size:.82rem;color:#475569;background:#f8fafc;
    padding:8px 12px;border-radius:8px;border-left:3px solid #e2e8f0;
    margin:0;line-height:1.55;
}
.ev-badge{
    display:inline-flex;align-items:center;gap:4px;
    padding:3px 10px;border-radius:999px;font-size:.7rem;
    font-weight:700;color:#fff;
}

/* ── empty state ── */
.ev-empty{
    text-align:center;padding:72px 24px;
    animation:evFadeUp .5s ease both;
}
.ev-empty-icon{
    width:80px;height:80px;border-radius:50%;
    background:linear-gradient(135deg,#dbeafe,#bfdbfe);
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 18px;font-size:2rem;color:#1e3a8a;
}

/* ── sparkline-bar mensual ── */
.ev-spark{width:100%;height:6px;background:#f1f5f9;border-radius:4px;overflow:hidden;margin-top:4px}
.ev-spark-fill{height:100%;border-radius:4px;transition:width .6s cubic-bezier(.4,0,.2,1)}
</style>

<div class="ev-wrap">

<!-- ══════════════════════ HERO ══════════════════════ -->
<div class="ev-hero">
    <div class="ev-hero-icon">
        <i class="ti ti-medal" style="font-size:32px;color:rgba(255,255,255,.9);"></i>
    </div>
    <div class="ev-hero-text">
        <div class="ev-hero-badge">
            <i class="ti ti-star-filled" style="font-size:.75rem;"></i>
            Registro de Desempeño
        </div>
        <h1 style="color:#fff;font-size:1.65rem;font-weight:800;margin:0 0 4px;text-shadow:0 2px 8px rgba(0,0,0,.15);">
            Mis Evaluaciones
        </h1>
        <p style="color:rgba(255,255,255,.75);margin:0;font-size:.88rem;">
            <?php if ($totalEv > 0): ?>
                <?= $totalEv ?> evaluación<?= $totalEv > 1 ? 'es' : '' ?> registrada<?= $totalEv > 1 ? 's' : '' ?>
                &nbsp;·&nbsp; Promedio global:
                <strong style="color:#93c5fd;"><?= number_format($promGlobal, 1) ?>/5</strong>
            <?php else: ?>
                Aún no tienes evaluaciones registradas
            <?php endif; ?>
        </p>
    </div>
    <?php if ($totalEv > 0): ?>
    <div style="z-index:1;text-align:center;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:16px;padding:14px 22px;">
        <div style="font-size:2.4rem;font-weight:900;color:#93c5fd;line-height:1;"><?= number_format($mejorNota, 1) ?></div>
        <div style="font-size:.72rem;color:rgba(255,255,255,.7);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;">Mejor nota</div>
    </div>
    <?php endif; ?>
</div>

<?php if ($totalEv === 0): ?>
<!-- ══════════════════════ EMPTY STATE ══════════════════════ -->
<div class="ev-list-card">
    <div class="ev-empty">
        <div class="ev-empty-icon">
            <i class="ti ti-star-off"></i>
        </div>
        <p style="font-size:1.1rem;font-weight:700;color:#1e293b;margin:0 0 8px;">Sin evaluaciones por ahora</p>
        <p style="font-size:.88rem;color:#94a3b8;max-width:360px;margin:0 auto;line-height:1.65;">
            Tu tutor o administrador registrará tu evaluación académica cuando corresponda al lapso.
        </p>
    </div>
</div>

<?php else: ?>

<!-- ══════════════════════ KPI ROW ══════════════════════ -->
<div class="ev-kpi-row">
    <?php
    $kpis = [
        ['label'=>'Evaluaciones', 'val'=>$totalEv,                        'sub'=>'registradas',       'color'=>'#7c3aed', 'icon'=>'ti-clipboard-list'],
        ['label'=>'Promedio',     'val'=>number_format($promGlobal, 1),    'sub'=>notaLabel($promGlobal),'color'=>notaColor($promGlobal), 'icon'=>notaIcon($promGlobal)],
        ['label'=>'Mejor Nota',   'val'=>number_format($mejorNota, 1),     'sub'=>notaLabel($mejorNota), 'color'=>notaColor($mejorNota),  'icon'=>'ti-trophy'],
        ['label'=>'Última',       'val'=>number_format((float)($ultima->promedio_final??0),1),'sub'=>$ultima->lapso_academico??'—','color'=>notaColor((float)($ultima->promedio_final??0)),'icon'=>'ti-clock'],
    ];
    foreach ($kpis as $i => $k): ?>
    <div class="ev-kpi" style="animation-delay:<?= $i * 0.07 ?>s">
        <div class="ev-kpi-accent" style="background:<?= $k['color'] ?>;"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;margin-top:8px;">
            <span class="ev-kpi-label"><?= $k['label'] ?></span>
            <span style="width:32px;height:32px;border-radius:10px;background:<?= $k['color'] ?>18;display:flex;align-items:center;justify-content:center;color:<?= $k['color'] ?>;">
                <i class="ti <?= $k['icon'] ?>" style="font-size:1rem;"></i>
            </span>
        </div>
        <div class="ev-kpi-val" style="color:<?= $k['color'] ?>"><?= $k['val'] ?></div>
        <div class="ev-kpi-sub"><?= htmlspecialchars($k['sub']) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ══════════════════════ BENTO MAIN ══════════════════════ -->
<div class="ev-bento">

    <!-- ── DESEMPEÑO RING ── -->
    <div class="ev-perf">
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;align-self:flex-start;">
            <i class="ti ti-chart-donut" style="margin-right:4px;"></i>Índice de Desempeño
        </div>

        <div class="ev-ring-wrap">
            <svg width="140" height="140" viewBox="0 0 140 140">
                <circle cx="70" cy="70" r="<?= $r ?>" fill="none" stroke="#f1f5f9" stroke-width="12"/>
                <circle cx="70" cy="70" r="<?= $r ?>" fill="none"
                    stroke="<?= $ringColor ?>" stroke-width="12"
                    stroke-linecap="round"
                    stroke-dasharray="<?= $dash ?> <?= $gap ?>"
                    stroke-dashoffset="<?= round($circ * 0.25, 2) ?>"
                    transform="rotate(-90 70 70)"
                    style="transition:stroke-dasharray 1s cubic-bezier(.4,0,.2,1);">
                </circle>
            </svg>
            <div class="ev-ring-center">
                <div class="ev-ring-score" style="color:<?= $ringColor ?>"><?= number_format($promGlobal, 1) ?></div>
                <div class="ev-ring-denom">/ 5 pts</div>
                <div class="ev-ring-label"><?= notaLabel($promGlobal) ?></div>
            </div>
        </div>

        <!-- distribución por nivel -->
        <?php
        $cnt = ['Excelente'=>0,'Bueno'=>0,'Regular'=>0,'Deficiente'=>0];
        foreach ($promedios as $p) $cnt[notaLabel((float)$p)]++;
        $levels = [
            ['Excelente', '#10b981', $cnt['Excelente']],
            ['Bueno',     '#f59e0b', $cnt['Bueno']],
            ['Regular',   '#f97316', $cnt['Regular']],
            ['Deficiente','#ef4444', $cnt['Deficiente']],
        ];
        ?>
        <div style="width:100%;display:flex;flex-direction:column;gap:10px;">
            <?php foreach ($levels as [$lbl, $col, $n]): ?>
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                    <span style="font-size:.76rem;font-weight:600;color:#64748b;"><?= $lbl ?></span>
                    <span style="font-size:.76rem;font-weight:700;color:<?= $col ?>;"><?= $n ?></span>
                </div>
                <div class="ev-spark">
                    <div class="ev-spark-fill" style="width:<?= $totalEv > 0 ? round($n/$totalEv*100) : 0 ?>%;background:<?= $col ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="font-size:.72rem;color:#cbd5e1;text-align:center;">
            Basado en <?= $totalEv ?> evaluación<?= $totalEv!==1?'es':'' ?>
        </div>
    </div>

    <!-- ── LISTA DE EVALUACIONES ── -->
    <div class="ev-list-card">
        <div class="ev-list-header">
            <div>
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:4px;">
                    <i class="ti ti-list-details" style="margin-right:4px;"></i>Historial
                </div>
                <div style="font-size:1rem;font-weight:700;color:#1e293b;">Todas las evaluaciones</div>
            </div>
            <span style="background:#fef3c7;color:#92400e;border-radius:999px;padding:4px 12px;font-size:.75rem;font-weight:700;">
                <?= $totalEv ?> total<?= $totalEv!==1?'es':'' ?>
            </span>
        </div>

        <div style="display:flex;flex-direction:column;gap:0;">
            <?php foreach ($evaluaciones as $idx => $ev): ?>
            <?php
                $nota  = (float)($ev->promedio_final ?? 0);
                $color = notaColor($nota);
                $grad  = notaGradient($nota);
                $label = notaLabel($nota);
                $icon  = notaIcon($nota);
            ?>
            <div class="ev-item" style="animation-delay:<?= $idx * 0.06 ?>s">
                <div class="ev-item-side" style="background:linear-gradient(160deg,#1e3a8a,#2563eb);">
                    <i class="ti <?= $icon ?>" style="font-size:.85rem;opacity:.8;"></i>
                    <div class="ev-item-score"><?= number_format($nota, 1) ?></div>
                    <div class="ev-item-base">/ 5</div>
                </div>
                <div class="ev-item-body">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                        <span class="ev-item-lapso"><?= htmlspecialchars($ev->lapso_academico ?? 'Sin lapso') ?></span>
                        <span class="ev-badge" style="background:<?= $color ?>;">
                            <i class="ti <?= $icon ?>" style="font-size:.65rem;"></i>
                            <?= $label ?>
                        </span>
                    </div>
                    <div class="ev-item-meta">
                        <span><i class="ti ti-calendar" style="margin-right:3px;"></i><?= htmlspecialchars($ev->fecha_formateada ?? '—') ?></span>
                        <span><i class="ti ti-user-check" style="margin-right:3px;"></i><?= htmlspecialchars($ev->tutor_nombre ?? 'Sin tutor') ?></span>
                    </div>
                    <?php if (!empty($ev->observaciones)): ?>
                    <p class="ev-item-obs">
                        <i class="ti ti-quote" style="margin-right:4px;opacity:.4;"></i><?= htmlspecialchars($ev->observaciones) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div><!-- /ev-bento -->

<!-- ── NOTA BENE ── -->
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-left:4px solid #2563eb;border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:12px;animation:evFadeUp .7s ease both;">
    <i class="ti ti-info-circle" style="color:#2563eb;font-size:1rem;margin-top:2px;flex-shrink:0;"></i>
    <p style="margin:0;font-size:.82rem;color:#1e40af;line-height:1.6;">
        <strong>Escala de evaluación:</strong>
        Excelente 4.5–5 &nbsp;·&nbsp; Bueno 3.5–4.4 &nbsp;·&nbsp; Regular 2.5–3.4 &nbsp;·&nbsp; Deficiente &lt;2.5.
        Las evaluaciones son registradas por tu tutor o el administrador del sistema.
    </p>
</div>

<?php endif; ?>
</div><!-- /ev-wrap -->
