<?php
/**
 * Tutor — Perfil Individual de Pasante
 */
$pct         = (float)($progreso->porcentaje    ?? 0);
$diasValidos = (int)  ($progreso->dias_presentes ?? 0);
$horasAcum   = (int)  ($progreso->horas_mostradas ?? 0);
$horasMeta   = (int)  ($progreso->horas_meta      ?? 1440);
$pctCal      = isset($calendario) ? (float)($calendario->porcentaje_calendario ?? 0) : 0;
$nombre      = trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''));
$iniciales   = strtoupper(substr($pasante->nombres ?? 'P', 0, 1) . substr($pasante->apellidos ?? 'A', 0, 1));

$estadoClass = match($pasante->estado_pasantia ?? '') {
    'Activo'     => '#10b981',
    'Pendiente'  => '#f59e0b',
    'Finalizado' => '#6366f1',
    default      => '#64748b',
};
?>
<style>
.tpp { padding:1.5rem 2rem; }
/* Banner */
.tpp-banner {
    background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);
    border-radius:20px; padding:22px 30px; margin-bottom:1.5rem;
    display:flex; align-items:center; justify-content:space-between;
    position:relative; overflow:hidden; gap:16px;
}
.tpp-banner::before {
    content:''; position:absolute; top:-40px; right:-40px;
    width:180px; height:180px; background:rgba(255,255,255,0.05);
    border-radius:50%; pointer-events:none;
}
.tpp-banner-left { display:flex; align-items:center; gap:14px; z-index:1; }
.tpp-banner-icon { background:rgba(255,255,255,0.15); border-radius:12px; padding:11px; }
.tpp-banner-title { color:#fff; font-size:1.3rem; font-weight:800; margin:0; }
.tpp-banner-sub   { color:rgba(255,255,255,0.7); font-size:.83rem; margin:3px 0 0; }
.tpp-back {
    z-index:1; display:inline-flex; align-items:center; gap:.4rem;
    font-size:.82rem; font-weight:600; color:rgba(255,255,255,0.85);
    text-decoration:none; background:rgba(255,255,255,0.15);
    border:1px solid rgba(255,255,255,0.2); border-radius:10px;
    padding:8px 14px; transition:all .15s; flex-shrink:0;
}
.tpp-back:hover { background:rgba(255,255,255,0.25); color:#fff; }
.tpp-grid { display:grid; grid-template-columns:340px 1fr; gap:1.5rem; }
@media(max-width:900px){ .tpp-grid{grid-template-columns:1fr;} }
.tpp-card { background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:1.5rem; }
.tpp-avatar-wrap { display:flex; justify-content:center; margin-bottom:1.25rem; }
.tpp-avatar { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#8b5cf6); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.6rem; color:#fff; }
.tpp-name { text-align:center; font-size:1.2rem; font-weight:700; color:#1e293b; margin:0 0 .25rem; }
.tpp-cedula { text-align:center; font-size:.85rem; color:#64748b; margin:0 0 .75rem; }
.tpp-status { text-align:center; margin-bottom:1.25rem; }
.tpp-badge { display:inline-block; padding:.25rem .85rem; border-radius:999px; font-size:.78rem; font-weight:600; }
.tpp-info-list { list-style:none; padding:0; margin:0; }
.tpp-info-list li { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #e2e8f0; font-size:.85rem; gap:.5rem; }
.tpp-info-list li:last-child { border-bottom:none; }
.tpp-info-list li span:first-child { color:#64748b; flex-shrink:0; }
.tpp-info-list li span:last-child { color:#1e293b; font-weight:500; text-align:right; }
/* Progress donut */
.tpp-donut-wrap { display:flex; flex-direction:column; align-items:center; margin:1.25rem 0; }
.tpp-donut-pct { font-size:1.6rem; font-weight:700; color:#1e293b; }
.tpp-donut-sub { font-size:.78rem; color:#64748b; }
.tpp-progress-bar { height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin:.4rem 0; }
.tpp-progress-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,#6366f1,#8b5cf6); transition:width .6s ease; }
.tpp-progress-fill.cal { background:linear-gradient(90deg,#f59e0b,#ef4444); }
/* Right panel tabs */
.tpp-tabs { display:flex; gap:.5rem; margin-bottom:1.25rem; background:#e2e8f0; padding:4px; border-radius:10px; }
.tpp-tab { flex:1; padding:.5rem; text-align:center; font-size:.83rem; font-weight:600; border-radius:8px; cursor:pointer; color:#64748b; transition:all .15s; border:none; background:none; }
.tpp-tab.active { background:#ffffff; color:#6366f1; box-shadow:0 1px 4px rgba(0,0,0,.1); }
.tpp-panel { display:none; }
.tpp-panel.active { display:block; }
/* Historial timeline */
.tpp-timeline { list-style:none; padding:0; margin:0; max-height:400px; overflow-y:auto; }
.tpp-tl-item { display:flex; align-items:center; gap:.85rem; padding:.6rem 0; border-bottom:1px solid #e2e8f0; }
.tpp-tl-item:last-child { border-bottom:none; }
.tpp-tl-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.tpp-tl-dot.presente { background:#10b981; }
.tpp-tl-dot.ausente { background:#ef4444; }
.tpp-tl-dot.justificado { background:#f59e0b; }
.tpp-tl-date { font-size:.82rem; color:#64748b; min-width:90px; }
.tpp-tl-estado { font-size:.82rem; font-weight:600; }
.tpp-tl-hora { font-size:.78rem; color:#64748b; margin-left:auto; }
/* Evaluaciones */
.tpp-eval-card { background:rgba(99,102,241,.05); border:1px solid rgba(99,102,241,.2); border-radius:10px; padding:.9rem 1rem; margin-bottom:.75rem; }
.tpp-eval-top { display:flex; justify-content:space-between; align-items:flex-start; }
.tpp-eval-score { font-size:1.4rem; font-weight:700; color:#6366f1; }
.tpp-eval-date { font-size:.78rem; color:#64748b; }
.tpp-eval-lapso { font-size:.82rem; font-weight:500; color:#1e293b; margin-top:.2rem; }
.tpp-eval-obs { font-size:.8rem; color:#64748b; margin-top:.4rem; font-style:italic; }
</style>

<div class="tpp">
    <!-- BANNER -->
    <div class="tpp-banner">
        <div class="tpp-banner-left">
            <div class="tpp-banner-icon">
                <i class="ti ti-user-circle" style="font-size:24px;color:white;"></i>
            </div>
            <div>
                <h1 class="tpp-banner-title"><?= htmlspecialchars($nombre) ?></h1>
                <p class="tpp-banner-sub"><i class="ti ti-id"></i> V-<?= htmlspecialchars($pasante->cedula ?? '—') ?> &nbsp;·&nbsp; <?= htmlspecialchars($pasante->estado_pasantia ?? 'Sin estado') ?></p>
            </div>
        </div>
        <a href="<?= URLROOT ?>/tutor/pasantes" class="tpp-back">
            <i class="ti ti-arrow-left"></i> Mis Pasantes
        </a>
    </div>

    <div class="tpp-grid">
        <!-- COLUMNA IZQUIERDA: Ficha del pasante -->
        <div>
            <div class="tpp-card" style="margin-bottom:1rem;">
                <div class="tpp-avatar-wrap">
                    <div class="tpp-avatar"><?= $iniciales ?></div>
                </div>
                <h2 class="tpp-name"><?= htmlspecialchars($nombre) ?></h2>
                <p class="tpp-cedula">V-<?= htmlspecialchars($pasante->cedula ?? '—') ?></p>
                <div class="tpp-status">
                    <span class="tpp-badge" style="background:<?= $estadoClass ?>22; color:<?= $estadoClass ?>;">
                        <?= htmlspecialchars($pasante->estado_pasantia ?? 'N/A') ?>
                    </span>
                </div>
                <ul class="tpp-info-list">
                    <li><span>Departamento</span><span><?= htmlspecialchars($pasante->departamento ?? '—') ?></span></li>
                    <li><span>Institución</span><span><?= htmlspecialchars($pasante->institucion ?? '—') ?></span></li>
                    <li><span>Inicio</span><span><?= $pasante->fecha_inicio ? date('d/m/Y', strtotime($pasante->fecha_inicio)) : '—' ?></span></li>
                    <li><span>Fin estimado</span><span><?= $pasante->fecha_fin ? date('d/m/Y', strtotime($pasante->fecha_fin)) : '—' ?></span></li>
                    <li><span>Meta</span><span><?= number_format($horasMeta) ?> horas</span></li>
                </ul>
            </div>

            <!-- Progreso Card -->
            <div class="tpp-card">
                <div style="font-size:.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-bottom:.75rem;">Progreso de Pasantía</div>
                <!-- Pro-rata (asistencias) -->
                <div style="margin-bottom:1rem;">
                    <div style="display:flex; justify-content:space-between; font-size:.8rem; color:#64748b; margin-bottom:.25rem;">
                        <span>🗓 Días asistidos</span><span><?= $pct ?>%</span>
                    </div>
                    <div class="tpp-progress-bar">
                        <div class="tpp-progress-fill" style="width:<?= $pct ?>%;"></div>
                    </div>
                    <div style="font-size:.78rem; color:#64748b;"><?= $diasValidos ?> días válidos → <?= $horasAcum ?>h acumuladas</div>
                </div>
                <!-- Calendario (tiempo transcurrido) -->
                <?php if ($calendario): ?>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:.8rem; color:#64748b; margin-bottom:.25rem;">
                        <span>⏱ Tiempo transcurrido</span><span><?= $pctCal ?>%</span>
                    </div>
                    <div class="tpp-progress-bar">
                        <div class="tpp-progress-fill cal" style="width:<?= $pctCal ?>%;"></div>
                    </div>
                    <div style="font-size:.78rem; color:#64748b;"><?= $calendario->dias_habiles_transcurridos ?> días hábiles desde el inicio</div>
                </div>
                <?php endif; ?>

                <?php
                $brecha = $pctCal - $pct;
                if ($brecha > 20): ?>
                <div style="margin-top:.85rem; padding:.6rem .85rem; background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.25); border-radius:8px; font-size:.8rem; color:#ef4444;">
                    ⚠️ <strong>En riesgo:</strong> el tiempo avanza <?= round($brecha, 1) ?> puntos más rápido que el progreso.
                </div>
                <?php endif; ?>

                <!-- Acciones rápidas -->
                <div style="display:flex; gap:.5rem; margin-top:1rem;">
                    <a href="<?= URLROOT ?>/evaluaciones/nueva/<?= $pasante->id ?>" class="tp-btn tp-btn-primary" style="flex:1; justify-content:center; padding:.55rem; font-size:.82rem;">
                        <i class="ti ti-star"></i> Evaluar
                    </a>
                    <a href="<?= URLROOT ?>/tutor/asistencias?vista=diaria" class="tp-btn tp-btn-secondary" style="flex:1; justify-content:center; padding:.55rem; font-size:.82rem;">
                        <i class="ti ti-calendar"></i> Asistencia
                    </a>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: Tabs Historial / Evaluaciones -->
        <div class="tpp-card">
            <div class="tpp-tabs">
                <button class="tpp-tab active" onclick="switchTab('historial')" id="tab-historial">
                    <i class="ti ti-history"></i> Historial Asistencias
                </button>
                <button class="tpp-tab" onclick="switchTab('evaluaciones')" id="tab-evaluaciones">
                    <i class="ti ti-star"></i> Evaluaciones (<?= count($evaluaciones) ?>)
                </button>
            </div>

            <!-- Panel Historial -->
            <div class="tpp-panel active" id="panel-historial">
                <?php if (empty($historial)): ?>
                    <p style="color:#64748b; text-align:center; padding:2rem;">Sin registros de asistencia.</p>
                <?php else: ?>
                <ul class="tpp-timeline">
                    <?php foreach ($historial as $h):
                        $est = strtolower($h->estado ?? '');
                        $dotClass = 'presente';
                        if (str_contains($est, 'ausente')) $dotClass = 'ausente';
                        elseif (str_contains($est, 'justificado')) $dotClass = 'justificado';
                    ?>
                    <li class="tpp-tl-item">
                        <div class="tpp-tl-dot <?= $dotClass ?>"></div>
                        <span class="tpp-tl-date"><?= date('d/m/Y', strtotime($h->fecha)) ?></span>
                        <span class="tpp-tl-estado" style="color:<?= $dotClass === 'presente' ? '#10b981' : ($dotClass === 'ausente' ? '#ef4444' : '#f59e0b') ?>">
                            <?= ucfirst($h->estado ?? '') ?>
                        </span>
                        <?php if (!empty($h->motivo_justificacion)): ?>
                        <span style="font-size:.75rem; color:#64748b; flex:1;"><?= htmlspecialchars(substr($h->motivo_justificacion, 0, 60)) ?></span>
                        <?php endif; ?>
                        <span class="tpp-tl-hora"><?= $h->hora_registro ? date('g:i A', strtotime($h->hora_registro)) : '—' ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Panel Evaluaciones -->
            <div class="tpp-panel" id="panel-evaluaciones">
                <div style="margin-bottom:1rem;">
                    <a href="<?= URLROOT ?>/evaluaciones/nueva/<?= $pasante->id ?>" class="tp-btn tp-btn-primary" style="font-size:.83rem;">
                        <i class="ti ti-plus"></i> Nueva Evaluación
                    </a>
                </div>
                <?php if (empty($evaluaciones)): ?>
                    <p style="color:#64748b; text-align:center; padding:2rem;">Aún no hay evaluaciones para este pasante.</p>
                <?php else: ?>
                    <?php foreach ($evaluaciones as $ev): ?>
                    <div class="tpp-eval-card">
                        <div class="tpp-eval-top">
                            <div>
                                <div class="tpp-eval-lapso"><?= htmlspecialchars($ev->lapso_academico ?? 'Sin lapso') ?></div>
                                <div class="tpp-eval-date"><?= date('d/m/Y', strtotime($ev->fecha_evaluacion)) ?></div>
                            </div>
                            <div class="tpp-eval-score"><?= number_format($ev->promedio_final ?? 0, 2) ?><span style="font-size:.9rem; color:#64748b;">/5</span></div>
                        </div>
                        <?php if (!empty($ev->observaciones)): ?>
                        <div class="tpp-eval-obs">«<?= htmlspecialchars(substr($ev->observaciones, 0, 120)) ?>»</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tpp-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tpp-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('tab-' + tab)?.classList.add('active');
    document.getElementById('panel-' + tab)?.classList.add('active');
}
// Cargar estilos que dependen de .tp-btn (reutilizados)
document.head.insertAdjacentHTML('beforeend', `<style>
.tp-btn{padding:.45rem .9rem;border-radius:8px;font-size:.8rem;font-weight:600;border:none;cursor:pointer;transition:background .15s;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;}
.tp-btn-primary{background:#6366f1;color:#fff;}.tp-btn-primary:hover{background:#4f46e5;}
.tp-btn-secondary{background:#e2e8f0;color:#1e293b;}
</style>`);
</script>
