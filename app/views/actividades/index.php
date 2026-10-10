<?php
/**
 * Vista: Actividades Extras — Hub Central
 */
$previewInstituciones = $data['previewInstituciones'] ?? [];
$previewPasantes      = $data['previewPasantes']      ?? [];
$previewActividades   = $data['previewActividades']   ?? [];
$distribucionTipos    = $data['distribucionTipos']    ?? [];
$kpiCortosActivos     = $data['kpiCortosActivos']     ?? 0;
$kpiPasantesCortos    = $data['kpiPasantesCortos']    ?? 0;
$kpiActividadesCom    = $data['kpiActividadesCom']    ?? 0;
$kpiInstituciones     = $data['kpiInstituciones']     ?? 0;
$periodoCortoActivo   = $data['periodoCortoActivo']   ?? null;
?>
<style>
@keyframes hub-fadeUp { from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)} }
@keyframes hub-pulse  { 0%,100%{opacity:1}50%{opacity:.5} }

.hub-grid-3 { display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:24px; }
.hub-grid-2 { display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:24px; }
.hub-card   { background:white;border-radius:20px;padding:22px;box-shadow:0 4px 20px rgba(0,0,0,0.05);border:1px solid rgba(0,0,0,0.05);animation:hub-fadeUp .4s ease both; }

/* KPI */
.hub-kpi { display:flex;justify-content:space-between;align-items:center;border-radius:16px;padding:22px;background:white;box-shadow:0 2px 12px rgba(0,0,0,0.06);transition:all .3s cubic-bezier(.4,0,.2,1); }
.hub-kpi:hover { transform:translateY(-4px); }

/* Nav buttons glass */
.hub-nav-btn {
    display:flex;align-items:center;gap:12px;
    background:rgba(255,255,255,0.12);
    border:1px solid rgba(255,255,255,0.25);
    backdrop-filter:blur(12px);
    color:white;padding:12px 22px;border-radius:12px;
    font-weight:700;font-size:.88rem;cursor:pointer;
    transition:all .2s;text-decoration:none;
}
.hub-nav-btn:hover { background:rgba(255,255,255,0.22);color:white;transform:translateY(-2px); }
.hub-nav-btn i { font-size:1.2rem; }

/* Preview cards */
.prev-item { display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f8fafc; }
.prev-item:last-child { border-bottom:none; }
.prev-avatar { width:36px;height:36px;border-radius:10px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1rem; }
.prev-name  { font-weight:700;color:#1e293b;font-size:.85rem; }
.prev-sub   { font-size:.73rem;color:#94a3b8;margin-top:2px; }
.prev-more  { display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#172554,#2563eb);color:white;padding:8px 16px;border-radius:9px;font-size:.8rem;font-weight:700;text-decoration:none;margin-top:14px;transition:all .2s; }
.prev-more:hover { opacity:.9;color:white; }

/* Período badge */
.per-badge-activo { display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#059669;border-radius:100px;padding:5px 14px;font-size:.78rem;font-weight:700; }
.per-badge-none   { display:inline-flex;align-items:center;gap:6px;background:#fef2f2;color:#ef4444;border-radius:100px;padding:5px 14px;font-size:.78rem;font-weight:700; }

/* Donut chart container */
.donut-wrap { display:flex;align-items:center;gap:20px; }
.donut-legend { display:flex;flex-direction:column;gap:8px;flex:1; }
.donut-leg-item { display:flex;align-items:center;gap:8px;font-size:.8rem; }
.donut-dot { width:10px;height:10px;border-radius:50%;flex-shrink:0; }

@media(max-width:1024px){ .hub-grid-3{grid-template-columns:1fr 1fr;} }
@media(max-width:640px) {
    .hub-grid-3 { grid-template-columns:1fr; }
    .hub-grid-2 { grid-template-columns:1fr; }
}

/* ── KPIs responsivos ── */
@media(max-width:767px) {
    /* Banner compacto */
    .hub-banner-wrap {
        padding: 16px 14px !important;
        margin-bottom: 14px !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
    }
    /* Ocultar botones de texto del banner — se sustituyen por iconos */
    .hub-nav-btn span { display: none !important; }
    .hub-nav-btn { padding: 9px 12px !important; }
    .hub-nav-btn i { font-size: 1.1rem !important; margin: 0 !important; }

    /* KPI grid: 2 columnas en móvil */
    .hub-kpi-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
        margin-bottom: 14px !important;
    }
    .hub-kpi {
        padding: 14px 12px !important;
        border-radius: 14px !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px !important;
    }
    .hub-kpi > div:first-child { width: 100% !important; }
    .hub-kpi p:first-child {
        font-size: 0.62rem !important;
        letter-spacing: 0.3px !important;
        margin-bottom: 4px !important;
        white-space: normal !important;
        line-height: 1.2 !important;
    }
    .hub-kpi h2 { font-size: 1.75rem !important; }
    .hub-kpi p:last-child { font-size: 0.62rem !important; }
    /* Icono KPI: más pequeño y alineado abajo */
    .hub-kpi > div:last-child {
        width: 36px !important; height: 36px !important;
        border-radius: 9px !important;
        font-size: 1.1rem !important;
        align-self: flex-end !important;
        margin-top: -28px !important;
    }

    /* Preview cards en 1 columna */
    .hub-grid-3 { grid-template-columns: 1fr !important; gap: 12px !important; }
    .hub-grid-2 { grid-template-columns: 1fr !important; gap: 12px !important; }
    .hub-card { padding: 14px !important; border-radius: 14px !important; }

    /* Donut: apilar verticalmente */
    .donut-wrap { flex-direction: column !important; align-items: center !important; gap: 14px !important; }
}
</style>

<div style="width:100%;">

<!-- ══ BANNER ══════════════════════════════════════════════════ -->
<div style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:20px;padding:32px 40px;margin-bottom:28px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">
    <div style="position:absolute;top:-40px;right:-40px;width:220px;height:220px;background:rgba(255,255,255,0.04);border-radius:50%;pointer-events:none;"></div>
    <div style="display:flex;align-items:center;gap:16px;z-index:1;">
        <div style="background:rgba(255,255,255,0.15);border-radius:14px;padding:14px;">
            <i class="ti ti-stack-2" style="font-size:32px;color:white;"></i>
        </div>
        <div>
            <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0;line-height:1.1;">Actividades Extras</h1>
            <p style="color:rgba(255,255,255,0.7);margin:5px 0 0;font-size:.88rem;">
                Módulo independiente · Pasantías Cortas &amp; Servicio Comunitario
            </p>
        </div>
    </div>
    <!-- Botones de navegación glass -->
    <div style="display:flex;gap:12px;z-index:1;flex-wrap:wrap;">
        <a href="<?= URLROOT ?>/actividades/instituciones" class="hub-nav-btn">
            <i class="ti ti-building-bank"></i> Instituciones
        </a>
        <a href="<?= URLROOT ?>/actividades/pasantias" class="hub-nav-btn">
            <i class="ti ti-user-star"></i> Pasantías Cortas
        </a>
        <a href="<?= URLROOT ?>/actividades/servicio" class="hub-nav-btn">
            <i class="ti ti-hearts"></i> Servicio Comunitario
        </a>
    </div>
</div>

<div class="hub-kpi-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="hub-kpi" style="border-left:4px solid #2563eb;"
         onmouseover="this.style.boxShadow='0 12px 25px rgba(37,99,235,0.2)'"
         onmouseout="this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'">
        <div>
            <p style="color:#64748b;font-size:.78rem;margin:0 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Pasantes Cortos Activos</p>
            <h2 style="font-size:2.2rem;font-weight:800;color:#2563eb;margin:0;line-height:1;"><?= $kpiCortosActivos ?></h2>
            <p style="color:#94a3b8;font-size:.72rem;margin:3px 0 0;"><?= $kpiPasantesCortos ?> total registrados</p>
        </div>
        <div style="background:#eff6ff;color:#2563eb;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;"><i class="ti ti-user-check"></i></div>
    </div>
    <div class="hub-kpi" style="border-left:4px solid #059669;"
         onmouseover="this.style.boxShadow='0 12px 25px rgba(5,150,105,0.2)'"
         onmouseout="this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'">
        <div>
            <p style="color:#64748b;font-size:.78rem;margin:0 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Actividades SC</p>
            <h2 style="font-size:2.2rem;font-weight:800;color:#059669;margin:0;line-height:1;"><?= $kpiActividadesCom ?></h2>
        </div>
        <div style="background:#f0fdf4;color:#059669;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;"><i class="ti ti-hearts"></i></div>
    </div>
    <div class="hub-kpi" style="border-left:4px solid #7c3aed;"
         onmouseover="this.style.boxShadow='0 12px 25px rgba(124,58,237,0.2)'"
         onmouseout="this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'">
        <div>
            <p style="color:#64748b;font-size:.78rem;margin:0 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Instituciones Aliadas</p>
            <h2 style="font-size:2.2rem;font-weight:800;color:#7c3aed;margin:0;line-height:1;"><?= $kpiInstituciones ?></h2>
        </div>
        <div style="background:#fdf4ff;color:#7c3aed;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;"><i class="ti ti-building-bank"></i></div>
    </div>
    <div class="hub-kpi" style="border-left:4px solid #d97706;"
         onmouseover="this.style.boxShadow='0 12px 25px rgba(217,119,6,0.2)'"
         onmouseout="this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'">
        <div>
            <p style="color:#64748b;font-size:.78rem;margin:0 0 6px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Período Corto</p>
            <?php if ($periodoCortoActivo): ?>
                <div class="per-badge-activo"><span style="width:7px;height:7px;background:#10b981;border-radius:50%;animation:hub-pulse 2s infinite;"></span><?= htmlspecialchars($periodoCortoActivo->nombre) ?></div>
            <?php else: ?>
                <div class="per-badge-none"><i class="ti ti-alert-circle"></i> Sin período activo</div>
            <?php endif; ?>
        </div>
        <div style="background:#fffbeb;color:#d97706;width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;"><i class="ti ti-calendar-event"></i></div>
    </div>
</div>

<!-- ══ PREVIEW GRID ═════════════════════════════════════════════ -->
<div class="hub-grid-3">

    <!-- Card Instituciones -->
    <div class="hub-card" style="animation-delay:.05s">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-building-bank" style="color:#2563eb;"></i>
                </div>
                <span style="font-weight:700;color:#1e293b;font-size:.9rem;">Instituciones Aliadas</span>
            </div>
            <span style="background:#eff6ff;color:#2563eb;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;"><?= $kpiInstituciones ?> total</span>
        </div>
        <?php if (empty($previewInstituciones)): ?>
        <div style="text-align:center;padding:30px 10px;color:#94a3b8;font-size:.85rem;">Sin instituciones registradas</div>
        <?php else: ?>
        <?php foreach ($previewInstituciones as $inst): ?>
        <div class="prev-item">
            <div class="prev-avatar" style="background:linear-gradient(135deg,#1e40af,#3b82f6);">
                <i class="ti ti-building-factory-2" style="color:white;font-size:.9rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="prev-name"><?= htmlspecialchars($inst->nombre) ?></div>
                <div class="prev-sub"><?= htmlspecialchars($inst->tipo) ?><?= $inst->contacto ? ' · ' . htmlspecialchars($inst->contacto) : '' ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <a href="<?= URLROOT ?>/actividades/instituciones" class="prev-more">Ver todas <i class="ti ti-arrow-right"></i></a>
    </div>

    <!-- Card Pasantes Cortos -->
    <div class="hub-card" style="animation-delay:.1s">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-user-star" style="color:#2563eb;"></i>
                </div>
                <span style="font-weight:700;color:#1e293b;font-size:.9rem;">Pasantías Cortas</span>
            </div>
            <span style="background:#dcfce7;color:#059669;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;"><?= $kpiCortosActivos ?> activos</span>
        </div>
        <?php if (empty($previewPasantes)): ?>
        <div style="text-align:center;padding:30px 10px;color:#94a3b8;font-size:.85rem;">Sin pasantes registrados</div>
        <?php else: ?>
        <?php foreach ($previewPasantes as $pc):
            $hMeta = max(1,(int)($pc->horas_meta??480));
            $hAcum = (int)($pc->horas_acumuladas??0);
            $pct   = min(100,round($hAcum/$hMeta*100));
        ?>
        <div class="prev-item">
            <div class="prev-avatar" style="background:linear-gradient(135deg,#1e40af,#6366f1);">
                <span style="color:white;font-weight:700;font-size:.78rem;"><?= strtoupper(substr($pc->nombres??'?',0,1).substr($pc->apellidos??'',0,1)) ?></span>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="prev-name"><?= htmlspecialchars(($pc->nombres??'').' '.($pc->apellidos??'')) ?></div>
                <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                    <div style="flex:1;height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                        <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,#2563eb,#10b981);border-radius:3px;"></div>
                    </div>
                    <span style="font-size:.7rem;font-weight:700;color:#64748b;"><?= $pct ?>%</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <a href="<?= URLROOT ?>/actividades/pasantias" class="prev-more">Ver todos <i class="ti ti-arrow-right"></i></a>
    </div>

    <!-- Card Servicio Comunitario + Donut -->
    <div class="hub-card" style="animation-delay:.15s">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-hearts" style="color:#059669;"></i>
                </div>
                <span style="font-weight:700;color:#1e293b;font-size:.9rem;">Servicio Comunitario</span>
            </div>
            <span style="background:#f0fdf4;color:#059669;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;"><?= $kpiActividadesCom ?> actividades</span>
        </div>
        <?php if (empty($previewActividades)): ?>
        <div style="text-align:center;padding:24px 10px;color:#94a3b8;font-size:.85rem;">Sin actividades registradas</div>
        <?php else: ?>
        <?php foreach ($previewActividades as $act): ?>
        <div class="prev-item">
            <div class="prev-avatar" style="background:<?= $act->estado === 'Activa' ? '#dcfce7' : '#f1f5f9' ?>;">
                <i class="ti ti-heart" style="color:<?= $act->estado === 'Activa' ? '#059669' : '#94a3b8' ?>;font-size:.9rem;"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="prev-name"><?= htmlspecialchars($act->nombre) ?></div>
                <div class="prev-sub"><?= (int)$act->total_participantes ?> participantes · <?= date('d/m/Y',strtotime($act->fecha_inicio)) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <a href="<?= URLROOT ?>/actividades/servicio" class="prev-more">Ver todas <i class="ti ti-arrow-right"></i></a>
    </div>
</div>

<!-- ══ DONUT CHART + INFO PERÍODO ══════════════════════════════ -->
<div class="hub-grid-2">

    <!-- Distribución por tipo (donut) -->
    <div class="hub-card" style="animation-delay:.2s">
        <div style="font-size:.85rem;font-weight:700;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <i class="ti ti-chart-donut" style="color:#7c3aed;"></i> Distribución de Actividades por Tipo
        </div>
        <div class="donut-wrap">
            <canvas id="donutChart" width="140" height="140" style="flex-shrink:0;"></canvas>
            <div class="donut-legend" id="donutLegend"></div>
        </div>
    </div>

    <!-- Info período activo corto -->
    <div class="hub-card" style="animation-delay:.25s;display:flex;flex-direction:column;justify-content:space-between;">
        <div style="font-size:.85rem;font-weight:700;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
            <i class="ti ti-calendar-stats" style="color:#d97706;"></i> Estado del Período Corto
        </div>
        <?php if ($periodoCortoActivo): ?>
        <?php
            $pInicio = new DateTime($periodoCortoActivo->fecha_inicio ?? 'today');
            $pFin    = new DateTime($periodoCortoActivo->fecha_fin    ?? 'today');
            $hoy     = new DateTime();
            $totalD  = max(1, $pFin->diff($pInicio)->days);
            $transD  = max(0, min($totalD, $hoy->diff($pInicio)->days));
            if ($hoy < $pInicio) $transD = 0;
            if ($hoy > $pFin)    $transD = $totalD;
            $ppct = round($transD / $totalD * 100);
        ?>
        <div style="background:linear-gradient(135deg,#172554,#1e3a8a);border-radius:14px;padding:20px;color:white;">
            <div style="font-size:.72rem;font-weight:800;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Período Activo</div>
            <div style="font-size:1.1rem;font-weight:800;margin-bottom:14px;"><?= htmlspecialchars($periodoCortoActivo->nombre) ?></div>
            <div style="background:rgba(255,255,255,.15);border-radius:100px;height:8px;margin-bottom:8px;overflow:hidden;">
                <div style="width:<?= $ppct ?>%;height:100%;background:linear-gradient(90deg,#34d399,#10b981);border-radius:100px;transition:width 1s ease;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.75rem;color:rgba(255,255,255,.7);">
                <span><?= date('d/m/Y', strtotime($periodoCortoActivo->fecha_inicio)) ?></span>
                <span style="font-weight:700;color:white;"><?= $ppct ?>% transcurrido</span>
                <span><?= date('d/m/Y', strtotime($periodoCortoActivo->fecha_fin)) ?></span>
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:10px;">
            <div style="flex:1;background:#f8fafc;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:1.4rem;font-weight:800;color:#2563eb;"><?= $transD ?></div>
                <div style="font-size:.72rem;color:#94a3b8;font-weight:600;">Días transcurridos</div>
            </div>
            <div style="flex:1;background:#f8fafc;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:1.4rem;font-weight:800;color:#059669;"><?= max(0,$totalD-$transD) ?></div>
                <div style="font-size:.72rem;color:#94a3b8;font-weight:600;">Días restantes</div>
            </div>
            <div style="flex:1;background:#f8fafc;border-radius:12px;padding:12px;text-align:center;">
                <div style="font-size:1.4rem;font-weight:800;color:#7c3aed;"><?= $kpiCortosActivos ?></div>
                <div style="font-size:.72rem;color:#94a3b8;font-weight:600;">Pasantes activos</div>
            </div>
        </div>
        <?php else: ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:20px;">
            <div style="width:60px;height:60px;background:#fef9c3;border-radius:16px;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-calendar-off" style="font-size:2rem;color:#d97706;"></i>
            </div>
            <div style="text-align:center;">
                <div style="font-weight:700;color:#1e293b;margin-bottom:4px;">Sin período corto activo</div>
                <div style="font-size:.8rem;color:#94a3b8;">Ve a Configuración → Períodos para crear uno de tipo "Corto"</div>
            </div>
            <a href="<?= URLROOT ?>/periodos" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#172554,#2563eb);color:white;padding:9px 18px;border-radius:10px;font-size:.82rem;font-weight:700;text-decoration:none;">
                <i class="ti ti-settings"></i> Ir a Configuración
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>

<script>
(function() {
    // Donut chart con Canvas API puro (sin dependencias)
    const tipos = <?= json_encode(array_map(fn($t) => ['tipo' => $t->tipo, 'total' => (int)$t->total], $distribucionTipos)) ?>;
    const colors = ['#2563eb','#059669','#7c3aed','#d97706','#ef4444','#0891b2'];
    const canvas  = document.getElementById('donutChart');
    if (!canvas || tipos.length === 0) return;
    const ctx  = canvas.getContext('2d');
    const cx   = 70, cy = 70, r = 55, ri = 32;
    const total = tipos.reduce((s, t) => s + t.total, 0) || 1;

    let startAngle = -Math.PI / 2;
    tipos.forEach((t, i) => {
        const slice = (t.total / total) * 2 * Math.PI;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.arc(cx, cy, r, startAngle, startAngle + slice);
        ctx.closePath();
        ctx.fillStyle = colors[i % colors.length];
        ctx.fill();
        startAngle += slice;
    });

    // Hueco central
    ctx.beginPath();
    ctx.arc(cx, cy, ri, 0, 2 * Math.PI);
    ctx.fillStyle = 'white';
    ctx.fill();

    // Texto central
    ctx.fillStyle = '#1e293b';
    ctx.font = 'bold 18px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(total, cx, cy);

    // Leyenda
    const leg = document.getElementById('donutLegend');
    if (leg) {
        leg.innerHTML = tipos.map((t, i) =>
            `<div class="donut-leg-item">
                <div class="donut-dot" style="background:${colors[i % colors.length]}"></div>
                <span style="color:#64748b;">${t.tipo}</span>
                <span style="font-weight:700;color:#1e293b;margin-left:auto;">${t.total}</span>
             </div>`
        ).join('');
    }
})();
</script>
