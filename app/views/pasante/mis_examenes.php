<?php
/* ══════════════════════════════════════════════════════
   Mis Exámenes — Bento UI v1
   Variables: $examenes[], $pasante
   ══════════════════════════════════════════════════════ */
$examenes = $data['examenes'] ?? [];
$pasante  = $data['pasante']  ?? null;

$total      = count($examenes);
$completados = 0;
foreach ($examenes as $ex) {
    if (!empty($ex->enviado_at)) $completados++;
}
$disponibles = $total - $completados;
?>
<style>
/* ── keyframes ── */
@keyframes exFadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
@keyframes exPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
@keyframes exShimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}

/* ── layout ── */
.ex-wrap{display:flex;flex-direction:column;gap:22px;animation:exFadeUp .45s ease both}

/* ── hero — idéntico al dashboard pasante ── */
.ex-hero{
    background:linear-gradient(135deg,#2e1065 0%,#4c1d95 40%,#6d28d9 75%,#7c3aed 100%);
    border-radius:22px;padding:30px 36px;position:relative;overflow:hidden;
    display:flex;align-items:center;justify-content:space-between;
    gap:20px;flex-wrap:wrap;
    box-shadow:0 8px 32px rgba(76,29,149,.4);
}
.ex-hero::before{
    content:'';position:absolute;top:-60px;right:-60px;
    width:280px;height:280px;border-radius:50%;
    background:rgba(255,255,255,.05);pointer-events:none;
}
.ex-hero::after{
    content:'';position:absolute;bottom:-40px;left:30%;
    width:180px;height:180px;border-radius:50%;
    background:rgba(255,255,255,.03);pointer-events:none;
}

/* avatar — igual que pb-avatar */
.ex-hero-avatar{
    width:56px;height:56px;
    background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.28);
    border-radius:16px;display:flex;align-items:center;justify-content:center;
    font-size:1.55rem;color:#fff;flex-shrink:0;
}
.ex-hero-left{display:flex;align-items:center;gap:18px;z-index:1;}
.ex-hero-badge{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);
    color:rgba(255,255,255,.9);border-radius:50px;
    padding:5px 14px;font-size:.76rem;font-weight:600;
    backdrop-filter:blur(8px);margin-bottom:7px;
}
.ex-hero-title{color:#fff;font-size:1.65rem;font-weight:800;margin:0;letter-spacing:-.3px;line-height:1.2;}

/* panel de stats derecha — igual que pb-clock-box */
.ex-stats-box{
    display:flex;align-items:center;gap:0;
    background:rgba(0,0,0,.2);backdrop-filter:blur(12px);
    border:1px solid rgba(255,255,255,.12);border-radius:16px;
    overflow:hidden;z-index:1;flex-shrink:0;
}
.ex-stat-item{
    padding:14px 22px;text-align:center;position:relative;
}
.ex-stat-item + .ex-stat-item::before{
    content:'';position:absolute;left:0;top:18%;bottom:18%;
    width:1px;background:rgba(255,255,255,.15);
}
.ex-stat-num{font-size:1.7rem;font-weight:900;line-height:1;color:#fff;letter-spacing:-.3px;}
.ex-stat-lbl{font-size:.65rem;color:rgba(255,255,255,.52);font-weight:700;text-transform:uppercase;letter-spacing:.45px;margin-top:4px;white-space:nowrap;}

/* ── exam grid ── */
.ex-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}

/* ── exam card ── */
.ex-card{
    background:#fff;border-radius:20px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);border:1px solid #f1f5f9;
    overflow:hidden;animation:exFadeUp .5s ease both;
    display:flex;flex-direction:column;
    transition:transform .2s,box-shadow .2s;
}
.ex-card:hover{transform:translateY(-4px);box-shadow:0 10px 28px rgba(0,0,0,.1)}
.ex-card-top{padding:20px 20px 16px;flex:1}
.ex-card-accent{height:4px;width:100%}
.ex-card-header{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px}
.ex-card-icon{
    width:44px;height:44px;border-radius:12px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:1.2rem;
}
.ex-card-title{font-size:.95rem;font-weight:700;color:#1e293b;line-height:1.35;margin-bottom:3px}
.ex-card-desc{font-size:.8rem;color:#64748b;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ex-card-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.ex-meta-tag{
    display:inline-flex;align-items:center;gap:4px;
    background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;
    padding:4px 9px;font-size:.73rem;color:#64748b;font-weight:500;
}
.ex-card-footer{padding:14px 20px;border-top:1px solid #f1f5f9;background:#fafafa}

/* score badge */
.ex-score-badge{
    display:inline-flex;align-items:center;gap:6px;
    border-radius:10px;padding:6px 12px;font-size:.8rem;font-weight:700;
}

/* btn */
.ex-btn{
    display:inline-flex;align-items:center;gap:7px;
    border:none;border-radius:10px;padding:9px 18px;font-size:.83rem;font-weight:700;
    cursor:pointer;text-decoration:none;transition:opacity .15s,transform .15s;
    line-height:1;
}
.ex-btn:hover{opacity:.88;transform:translateY(-1px)}

/* ── empty state ── */
.ex-empty{
    text-align:center;padding:72px 24px;background:#fff;
    border-radius:20px;box-shadow:0 2px 14px rgba(0,0,0,.06);
    animation:exFadeUp .5s ease both;
}
.ex-empty-icon{
    width:80px;height:80px;border-radius:50%;
    background:linear-gradient(135deg,#ede9fe,#ddd6fe);
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 18px;font-size:2rem;color:#5b21b6;
}

/* result inline panel (for completed exams) */
.ex-result-panel{margin-top:14px;background:#f8f7ff;border:1px solid #ddd6fe;border-radius:14px;overflow:hidden}
.ex-result-header{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;background:rgba(109,40,217,.06);border-bottom:1px solid #ddd6fe}
.ex-result-header-lbl{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#6d28d9;display:flex;align-items:center;gap:5px}
.ex-result-body{padding:14px 14px 10px}
.ex-result-score-row{display:flex;align-items:baseline;justify-content:space-between;gap:8px;margin-bottom:8px}
.ex-result-pct{font-size:2rem;font-weight:900;line-height:1;letter-spacing:-.5px}
.ex-result-pts{font-size:.78rem;color:#64748b;font-weight:600;text-align:right;line-height:1.3}
.ex-result-bar{height:7px;background:#ede9fe;border-radius:4px;overflow:hidden;margin-bottom:10px}
.ex-result-fill{height:100%;border-radius:4px;transition:width .7s cubic-bezier(.4,0,.2,1)}
.ex-result-badges{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.ex-result-badge{display:inline-flex;align-items:center;gap:4px;border-radius:20px;padding:4px 10px;font-size:.72rem;font-weight:700;line-height:1}
.ex-result-ts{padding:0 14px 10px;font-size:.71rem;color:#94a3b8;display:flex;align-items:center;gap:5px}

/* ── Mobile ≤ 768px ─────────────────────────────────────────── */
@media(max-width:768px){
    .ex-hero{
        padding:20px 18px !important;
        border-radius:18px !important;
        flex-direction:column !important;
        align-items:stretch !important;
        gap:16px !important;
    }
    .ex-hero-left{
        width:100% !important;
        display:flex !important;
        align-items:center !important;
        gap:14px !important;
    }
    .ex-hero-avatar{
        width:48px !important;
        height:48px !important;
        font-size:1.35rem !important;
        border-radius:14px !important;
        flex-shrink:0 !important;
    }
    .ex-hero-badge{
        font-size:.72rem !important;
        padding:4px 10px !important;
        margin-bottom:4px !important;
    }
    .ex-hero-title{
        font-size:1.35rem !important;
        line-height:1.25 !important;
    }
    /* Stats box: distribución de 3 columnas balanceadas y elegantes */
    .ex-stats-box{
        width:100% !important;
        display:grid !important;
        grid-template-columns:repeat(3, 1fr) !important;
        border-radius:14px !important;
        background:rgba(0,0,0,.25) !important;
        box-sizing:border-box !important;
    }
    .ex-stat-item{
        padding:10px 4px !important;
        text-align:center !important;
        border-bottom:none !important;
    }
    .ex-stat-num{
        font-size:1.35rem !important;
        font-weight:900 !important;
        line-height:1.1 !important;
    }
    .ex-stat-lbl{
        font-size:.63rem !important;
        white-space:normal !important;
        letter-spacing:.3px !important;
        margin-top:3px !important;
    }
    /* Grid: una columna */
    .ex-grid{grid-template-columns:1fr !important}
    /* Card */
    .ex-card-top{padding:16px 16px 12px}
    .ex-card-footer{padding:12px 16px}
    /* Resultado */
    .ex-result-pct{font-size:1.5rem}
    .ex-result-body{padding:12px 12px 8px}
}

/* ─── DARK MODE SUPPORT (MIS EXÁMENES) ──────────────── */
[data-theme="dark"] .ex-hero {
    background: linear-gradient(135deg, #090d16 0%, #0f1c3f 50%, #1e3a8a 100%) !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6) !important;
}
[data-theme="dark"] .ex-card {
    background: #111a2e !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-theme="dark"] .ex-card-title {
    color: #f8fafc !important;
}
[data-theme="dark"] .ex-card-desc {
    color: #94a3b8 !important;
}
[data-theme="dark"] .ex-card-footer {
    background: #0d1526 !important;
    border-top-color: #1e293b !important;
}
[data-theme="dark"] .ex-meta-tag {
    background: #0d1526 !important;
    border-color: #1e293b !important;
    color: #94a3b8 !important;
}
[data-theme="dark"] .ex-empty {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .ex-empty h3 {
    color: #f8fafc !important;
}
[data-theme="dark"] .ex-result-panel {
    background: #0d1526 !important;
    border-color: #1e293b !important;
}
[data-theme="dark"] .ex-result-header {
    background: rgba(30, 58, 138, 0.25) !important;
    border-bottom-color: #1e293b !important;
}
[data-theme="dark"] .ex-result-bar {
    background: #1e293b !important;
}
[data-theme="dark"] .ex-result-pts {
    color: #94a3b8 !important;
}
[data-theme="dark"] .ex-empty p {
    color: #94a3b8 !important;
}
[data-theme="dark"] .ex-empty p:first-of-type {
    color: #f8fafc !important;
}
</style>

<div class="ex-wrap">

<!-- ══════════════════════ HERO con KPIs integrados ══════════════════════ -->
<?php
    // Calcular promedio si hay completados
    $avgPasante = 0;
    if ($completados > 0) {
        $sumPct = array_sum(array_map(fn($e) => !empty($e->enviado_at) ? (float)($e->porcentaje ?? 0) : 0, $examenes));
        $avgPasante = round($sumPct / $completados, 1);
    }
?>
<div class="ex-hero">

    <!-- Izquierda: avatar + badge + título -->
    <div class="ex-hero-left">
        <div class="ex-hero-avatar">
            <i class="ti ti-school"></i>
        </div>
        <div>
            <div class="ex-hero-badge">
                <i class="ti ti-clipboard-check" style="font-size:.8rem;"></i>
                Portal de Evaluaciones
            </div>
            <h1 class="ex-hero-title">Mis Exámenes</h1>
        </div>
    </div>

    <!-- Derecha: panel de contadores (estilo pb-clock-box) -->
    <div class="ex-stats-box">
        <div class="ex-stat-item">
            <div class="ex-stat-num" style="color:<?= $disponibles > 0 ? '#86efac' : 'rgba(255,255,255,.45)' ?>;">
                <?= $disponibles ?>
            </div>
            <div class="ex-stat-lbl">Disponibles</div>
        </div>
        <div class="ex-stat-item">
            <div class="ex-stat-num" style="color:#c4b5fd;"><?= $completados ?></div>
            <div class="ex-stat-lbl">Completados</div>
        </div>
        <div class="ex-stat-item">
            <?php if ($completados > 0): ?>
            <div class="ex-stat-num" style="color:<?= $avgPasante >= 60 ? '#86efac' : '#fca5a5' ?>;">
                <?= $avgPasante ?>%
            </div>
            <?php else: ?>
            <div class="ex-stat-num" style="color:rgba(255,255,255,.3);">—</div>
            <?php endif; ?>
            <div class="ex-stat-lbl">Mi promedio</div>
        </div>
    </div>

</div>

<!-- flash messages -->
<?php if (Session::getFlash('error')): ?>
<div style="background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:12px;padding:13px 18px;display:flex;align-items:center;gap:10px;color:#b91c1c;font-size:.85rem;font-weight:600;">
    <i class="ti ti-alert-circle" style="font-size:1rem;flex-shrink:0;"></i>
    <?= htmlspecialchars(Session::getFlash('error')) ?>
</div>
<?php endif; ?>

<?php if ($total === 0): ?>
<!-- ══════════════════════ EMPTY STATE ══════════════════════ -->
<div class="ex-empty">
    <div class="ex-empty-icon">
        <i class="ti ti-school"></i>
    </div>
    <p style="font-size:1.1rem;font-weight:700;color:#1e293b;margin:0 0 8px;">Sin exámenes disponibles</p>
    <p style="font-size:.87rem;color:#94a3b8;max-width:380px;margin:0 auto;line-height:1.65;">
        El administrador publicará los exámenes cuando corresponda al período académico activo.
    </p>
</div>

<?php else: ?>
<!-- ══════════════════════ EXAM GRID ══════════════════════ -->
<div class="ex-grid">
    <?php foreach ($examenes as $idx => $ex):
        $completado    = !empty($ex->enviado_at);
        $pct           = (float)($ex->porcentaje ?? 0);
        $aprobado      = $pct >= 60;
        // Escala de colores: ≥60 verde · ≥40 amarillo · <40 rojo
        $pctColor = $pct >= 60 ? '#059669' : ($pct >= 40 ? '#d97706' : '#dc2626');
        $pctBg    = $pct >= 60 ? 'linear-gradient(90deg,#059669,#34d399)' : ($pct >= 40 ? 'linear-gradient(90deg,#d97706,#fbbf24)' : 'linear-gradient(90deg,#dc2626,#f87171)');
        $badgeBg  = $pct >= 60 ? '#d1fae5' : ($pct >= 40 ? '#fef3c7' : '#fee2e2');
        $badgeFg  = $pct >= 60 ? '#065f46' : ($pct >= 40 ? '#92400e' : '#991b1b');
        $badgeIcon = $pct >= 60 ? 'ti-circle-check' : ($pct >= 40 ? 'ti-alert-circle' : 'ti-circle-x');
        $badgeLabel = $pct >= 60 ? 'Aprobado' : ($pct >= 40 ? 'Cerca del aprobado' : 'No aprobado');
        $accentColor   = $completado ? '#7c3aed' : '#059669';
        $bgIcon        = $completado ? '#ede9fe' : '#d1fae5';
        $colorIcon     = $completado ? '#5b21b6' : '#065f46';
        $iconName      = $completado ? 'ti-circle-check' : 'ti-pencil';
        $totalPreg     = (int)($ex->total_preguntas ?? 0);

        $fechaDesde = !empty($ex->fecha_inicio) ? date('d/m/Y', strtotime($ex->fecha_inicio)) : null;
        $fechaHasta = !empty($ex->fecha_fin)    ? date('d/m/Y', strtotime($ex->fecha_fin))    : null;

        $ptsObt = (float)($ex->puntaje_obtenido ?? 0);
        $ptsMx  = (float)($ex->puntaje_maximo  ?? 0);
    ?>
    <div class="ex-card" style="animation-delay:<?= $idx * 0.07 ?>s">
        <div class="ex-card-accent" style="background:<?= $accentColor ?>;"></div>
        <div class="ex-card-top">
            <div class="ex-card-header">
                <div class="ex-card-icon" style="background:<?= $bgIcon ?>;color:<?= $colorIcon ?>;">
                    <i class="ti <?= $iconName ?>"></i>
                </div>
                <div style="min-width:0;">
                    <div class="ex-card-title"><?= htmlspecialchars($ex->titulo ?? 'Examen') ?></div>
                    <?php if (!empty($ex->descripcion)): ?>
                    <div class="ex-card-desc"><?= htmlspecialchars($ex->descripcion) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ex-card-meta">
                <?php if ($totalPreg > 0): ?>
                <span class="ex-meta-tag">
                    <i class="ti ti-list-numbers" style="font-size:.8rem;"></i>
                    <?= $totalPreg ?> pregunta<?= $totalPreg !== 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
                <?php if ($fechaDesde): ?>
                <span class="ex-meta-tag">
                    <i class="ti ti-calendar-event" style="font-size:.8rem;"></i>
                    Desde <?= $fechaDesde ?>
                </span>
                <?php endif; ?>
                <?php if ($fechaHasta): ?>
                <span class="ex-meta-tag">
                    <i class="ti ti-calendar-x" style="font-size:.8rem;"></i>
                    Hasta <?= $fechaHasta ?>
                </span>
                <?php endif; ?>
                <?php if (!$fechaDesde && !$fechaHasta): ?>
                <span class="ex-meta-tag" style="background:#ecfdf5;border-color:#6ee7b7;color:#065f46;">
                    <i class="ti ti-infinity" style="font-size:.8rem;"></i>
                    Sin límite de fecha
                </span>
                <?php endif; ?>
            </div>

            <?php if ($completado): ?>
            <!-- ── resultado panel ── -->
            <div class="ex-result-panel">
                <!-- zona 1: encabezado -->
                <div class="ex-result-header">
                    <span class="ex-result-header-lbl">
                        <i class="ti ti-report-analytics" style="font-size:.8rem;"></i>
                        Tu resultado
                    </span>
                    <?php if ($ptsMx > 0): ?>
                    <span style="font-size:.7rem;color:#7c3aed;font-weight:600;"><?= $totalPreg ?> preguntas</span>
                    <?php endif; ?>
                </div>

                <!-- zona 2: puntuación + barra -->
                <div class="ex-result-body">
                    <div class="ex-result-score-row">
                        <span class="ex-result-pct" style="color:<?= $pctColor ?>;">
                            <?= number_format($pct, 1) ?>%
                        </span>
                        <?php if ($ptsMx > 0): ?>
                        <div class="ex-result-pts">
                            <div style="font-size:1.05rem;font-weight:800;color:#374151;line-height:1;"><?= number_format($ptsObt, 1) ?></div>
                            <div>de <?= number_format($ptsMx, 1) ?> pts</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ex-result-bar">
                        <div class="ex-result-fill" style="width:<?= min(100, $pct) ?>%;background:<?= $pctBg ?>;"></div>
                    </div>

                    <!-- zona 3: badges de estado -->
                    <div class="ex-result-badges">
                        <span class="ex-result-badge" style="background:<?= $badgeBg ?>;color:<?= $badgeFg ?>;">
                            <i class="ti <?= $badgeIcon ?>" style="font-size:.85rem;"></i>
                            <?= $badgeLabel ?>
                        </span>
                        <?php if (!empty($ex->revisado_at)): ?>
                        <span class="ex-result-badge" style="background:#e0f2fe;color:#0369a1;">
                            <i class="ti ti-eye-check" style="font-size:.85rem;"></i>
                            Revisado
                        </span>
                        <?php else: ?>
                        <span class="ex-result-badge" style="background:#fef9c3;color:#713f12;">
                            <i class="ti ti-hourglass" style="font-size:.85rem;"></i>
                            Pendiente revisión
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- zona 4: timestamp -->
                <div class="ex-result-ts">
                    <i class="ti ti-send" style="font-size:.8rem;"></i>
                    Enviado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($ex->enviado_at)) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="ex-card-footer">
            <?php if (!$completado): ?>
            <a href="<?= URLROOT ?>/pasante/tomarExamen/<?= (int)$ex->id ?>"
               class="ex-btn"
               style="background:#059669;color:#fff;width:100%;justify-content:center;">
                <i class="ti ti-pencil" style="font-size:.95rem;"></i>
                Tomar Examen
            </a>
            <?php else: ?>
            <div style="display:flex;align-items:center;gap:8px;justify-content:center;color:#7c3aed;font-size:.82rem;font-weight:600;">
                <i class="ti ti-circle-check" style="font-size:1rem;"></i>
                Examen completado
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- info footer -->
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #059669;border-radius:12px;padding:13px 18px;display:flex;align-items:flex-start;gap:12px;animation:exFadeUp .7s ease both;">
    <i class="ti ti-info-circle" style="color:#059669;font-size:1rem;margin-top:2px;flex-shrink:0;"></i>
    <p style="margin:0;font-size:.82rem;color:#14532d;line-height:1.6;">
        <strong>Nota:</strong> Una vez que envíes un examen no podrás modificar tus respuestas.
        La nota de aprobación es <strong>60%</strong>. Asegúrate de responder todas las preguntas antes de enviar.
    </p>
</div>

</div><!-- /ex-wrap -->
