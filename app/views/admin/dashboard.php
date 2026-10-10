<?php
// Vista exclusiva para Administradores — SGP Premium Dashboard
$user_name          = $data['user_name']          ?? 'Administrador';
$role               = $data['role']               ?? 'Administrador';
$totalActivos       = $data['totalActivos']       ?? 0;
$pendientesAsignar  = $data['pendientesAsignar']  ?? 0;
$asistenciasHoy     = $data['asistenciasHoy']     ?? 0;
$faltasHoy          = $data['faltasHoy']          ?? 0;
$actividadReciente  = $data['actividadReciente']  ?? [];
$alertas_pendientes = $data['alertas_pendientes'] ?? [];
$ausentesMes              = $data['ausentesMes']              ?? [];
$periodosRecientementeC   = $data['periodosRecientementeC']  ?? [];
$cortosResumen           = $data['cortosResumen']            ?? (object)['total'=>0,'activos'=>0,'finalizados'=>0,'pct_promedio'=>0];
$alertasPeriodosProx     = $data['alertasPeriodosProx']     ?? [];
$alertasPeriodosVencidos = $data['alertasPeriodosVencidos'] ?? [];
$departamentos      = $data['departamentos']      ?? [];
$tutores            = $data['tutores']            ?? [];
$periodos           = $data['periodos']           ?? [];
?>

<style>
/* === SGP BENTO UI & STRUCTURAL FIXES === */
.dashboard-bento { display: flex !important; flex-direction: column !important; gap: 24px; width: 100%; }
/* [SGP-FIX-KPI-RESPONSIVE-v3] .dashboard-kpi-grid y sus media queries se
   quitaron de acá: esta vista redefinía la clase por su cuenta, duplicando
   (y en algunos anchos contradiciendo) las reglas de main_layout.php
   ("SGP Global Responsive Layer" — la fuente única declarada para estos
   grids) y de style.css. Tener 3 definiciones distintas del mismo selector
   en 3 archivos era la causa de que las tarjetas no reflowaran de forma
   predecible. Fuente única ahora: style.css (base) + main_layout.php
   (breakpoints con !important). */
.charts-grid-50 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; width: 100%; align-items: stretch; }
.bottom-grid-60-40 { display: grid; grid-template-columns: 6fr 4fr; gap: 20px; width: 100%; align-items: stretch; }

@media (max-width: 1200px) {
    .charts-grid-50, .bottom-grid-60-40 { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .figma-card .chart-wrapper { height: 220px !important; min-height: 220px !important; max-height: 220px !important; }
}
@media (max-width: 480px) {
    .kpi-card { padding: 14px 16px; }
    .kpi-value { font-size: 1.5rem !important; }
}

/* === KPI CARDS WITH HOVER RIBBON === */
.kpi-card { display: flex; justify-content: space-between; align-items: center; padding: 22px; background: #fff; border-radius: 16px; box-shadow: 0 4px 15px rgba(22, 38, 96, 0.05); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 5px solid var(--kpi-color, transparent); }
.kpi-card:hover { transform: translateY(-4px); box-shadow: 0 12px 25px var(--kpi-glow, rgba(0,0,0,0.1)); }
.kpi-blue { --kpi-color: #2563eb; --kpi-glow: rgba(37,99,235,0.55); }
.kpi-orange { --kpi-color: #F59E0B; --kpi-glow: rgba(245,158,11,0.55); }
.kpi-green { --kpi-color: #059669; --kpi-glow: rgba(5,150,105,0.55); }
.kpi-red { --kpi-color: #DC2626; --kpi-glow: rgba(220,38,38,0.55); }

.kpi-left { display: flex; align-items: center; gap: 15px; }
.kpi-label { display: block; font-size: 0.8rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.kpi-value { margin: 4px 0 0; font-size: 1.8rem; font-weight: 800; line-height: 1; }

.btn-bento-action { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 12px 18px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-decoration: none; transition: all 0.3s ease; border: 1px solid transparent; min-width: 80px; }
.btn-bento-action i { font-size: 1.4rem; }
.btn-bento-action:hover { transform: scale(1.05); }

/* === CHARTS STABILITY (NO JUMPING) === */
.figma-card .chart-wrapper { height: 320px !important; min-height: 320px !important; max-height: 320px !important; width: 100%; position: relative; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }
#chart-main-history, #chart-asistencia-depto, #chart-gauge-container { width: 100%; height: 100%; }
.figma-card { min-height: 440px; display: flex; flex-direction: column; }

/* === SCROLLABLE PANELS === */
.table-container-scroll, .activity-list-scroll { max-height: 350px; overflow-y: auto; padding-right: 6px; }
.table-container-scroll::-webkit-scrollbar, .activity-list-scroll::-webkit-scrollbar { width: 6px; }
.table-container-scroll::-webkit-scrollbar-thumb, .activity-list-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

/* === MICRO BUTTONS === */
.btn-micro { background: transparent; border: none; padding: 6px; border-radius: 6px; cursor: pointer; color: #64748b; transition: all 0.2s; }
.btn-micro:hover { background: #e2e8f0; color: #1e293b; transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.btn-micro.active { background: #fff; color: #2563eb; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.btn-action { transition: all 0.2s; border: none; cursor: pointer; }
.btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.08); }
.btn-action.btn-view:hover { background: #e0f2fe !important; color: #0369a1 !important; }
.btn-action.btn-edit:hover { background: #dbeafe !important; color: #1d4ed8 !important; }

/* === GAUGE LEGEND === */
.gauge-legend { display: none; justify-content: center; flex-wrap: wrap; gap: 8px; padding: 10px 0 0; }
.gauge-legend.active { display: flex; }
.gauge-legend-chip { display: flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; }
.gauge-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

/* === MÓVIL (SGP Bento Mobile) === */
@media (max-width: 991px) {
    .admin-banner {
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 20px !important;
        gap: 14px !important;
    }
    .admin-banner > div:first-child {
        display: none !important;
    }
    /* Widget fecha/hora: ocultar en móvil */
    .admin-banner > div:last-child { display: none !important; }
}
</style>

<div class="dashboard-bento">
    
    <!-- BANNER ESTANDARIZADO SGP -->
    <div class="admin-banner" style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:20px;padding:32px 40px;margin-bottom:28px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;">
        <div style="position:absolute;top:-30px;right:-30px;width:200px;height:200px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
        <div style="display:flex;align-items:center;gap:16px;z-index:1;">
            <div style="background:rgba(255,255,255,0.15);border-radius:14px;padding:14px;">
                <i class="ti ti-sparkles" style="font-size:32px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:white;font-size:1.8rem;font-weight:700;margin:0;">¡Bienvenido de nuevo, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>!</h1>
                <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:0.9rem;">
                    <i class="ti ti-layout-dashboard"></i> Panel de Control · <?= $role ?>
                </p>
            </div>
        </div>
        <div style="display:flex; z-index:1; align-items:center;">
            <div style="background: rgba(0, 0, 0, 0.15); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 50px; padding: 10px 24px; display: flex; align-items: center; gap: 20px; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="ti ti-calendar" style="color: rgba(255,255,255,0.8); font-size: 1.1rem;"></i> 
                    <span id="currentDate" style="font-size: 0.9rem; font-weight: 500; letter-spacing: 0.5px;"></span>
                </div>
                <div style="width: 1px; height: 24px; background: rgba(255, 255, 255, 0.2);"></div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="ti ti-clock" style="color: rgba(255,255,255,0.8); font-size: 1.2rem;"></i> 
                    <span id="currentTime" style="font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px;"></span>
                </div>
            </div>
        </div>
    </div>
    <!-- ══ ALERTA: Períodos vencidos sin cerrar ══ -->
    <?php if (!empty($alertasPeriodosVencidos)): ?>
    <div style="background:white;border:1.5px solid #fecaca;border-left:4px solid #dc2626;
                border-radius:14px;padding:14px 20px;margin-bottom:18px;
                box-shadow:0 2px 10px rgba(220,38,38,.08);">
        <!-- Cabecera compacta -->
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <div style="width:30px;height:30px;border-radius:8px;background:#fee2e2;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="ti ti-alarm" style="color:#dc2626;font-size:.9rem;"></i>
            </div>
            <div>
                <span style="font-size:.7rem;font-weight:800;color:#dc2626;text-transform:uppercase;letter-spacing:.5px;">Acción requerida</span>
                <div style="font-size:.86rem;font-weight:700;color:#1e293b;">
                    <?= count($alertasPeriodosVencidos) === 1
                        ? 'Hay 1 período vencido sin cerrar'
                        : 'Hay ' . count($alertasPeriodosVencidos) . ' períodos vencidos sin cerrar' ?>
                </div>
            </div>
        </div>
        <!-- Lista compacta -->
        <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($alertasPeriodosVencidos as $pv):
            $diasV    = (int)($pv->dias_vencido ?? 0);
            $activosP = (int)($pv->activos_pendientes ?? 0);
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    flex-wrap:wrap;gap:10px;padding:10px 14px;
                    background:#fff5f5;border-radius:10px;border:1px solid #fee2e2;">
            <div>
                <div style="font-size:.84rem;font-weight:700;color:#1e293b;">
                    <?= htmlspecialchars($pv->nombre) ?>
                </div>
                <div style="font-size:.73rem;color:#94a3b8;margin-top:2px;display:flex;gap:10px;flex-wrap:wrap;">
                    <span><i class="ti ti-calendar-x" style="color:#dc2626;"></i>
                        Venció el <?= date('d/m/Y', strtotime($pv->fecha_fin)) ?>
                        (hace <?= $diasV ?> día<?= $diasV!==1?'s':'' ?>)</span>
                    <span>·</span>
                    <span><i class="ti ti-users" style="color:#64748b;"></i>
                        <?= $activosP ?> pasante<?= $activosP!==1?'s':'' ?> activo<?= $activosP!==1?'s':'' ?></span>
                </div>
            </div>
            <button onclick="cerrarPeriodoDesdeDashboard(<?= (int)$pv->id ?>, '<?= htmlspecialchars(addslashes($pv->nombre)) ?>', <?= $activosP ?>)"
                    style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;
                           border-radius:8px;border:1.5px solid #fca5a5;cursor:pointer;
                           font-family:inherit;font-size:.78rem;font-weight:700;white-space:nowrap;
                           background:white;color:#dc2626;transition:all .2s;"
                    onmouseover="this.style.background='#dc2626';this.style.color='white';this.style.borderColor='#dc2626'"
                    onmouseout="this.style.background='white';this.style.color='#dc2626';this.style.borderColor='#fca5a5'">
                <i class="ti ti-lock"></i> Cerrar y finalizar
            </button>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Alertas de períodos próximos a vencer ── -->
    <?php foreach ($alertasPeriodosProx as $alp):
        $diasR = (int)($alp->dias_restantes ?? 0);
        $urgente = $diasR <= 2;
        $bgColor = $urgente ? '#fef2f2' : '#fff7ed';
        $bdColor = $urgente ? '#fecaca' : '#fed7aa';
        $icColor = $urgente ? '#dc2626' : '#d97706';
        $icBg    = $urgente ? '#fee2e2' : '#ffedd5';
        $icono   = $urgente ? 'ti-alarm' : 'ti-clock-exclamation';
        $label   = $diasR === 0 ? '¡Termina HOY!' : ($diasR === 1 ? 'Termina mañana' : "Termina en {$diasR} días");
    ?>
    <div style="background:<?= $bgColor ?>;border:1.5px solid <?= $bdColor ?>;border-radius:14px;
                padding:14px 20px;margin-bottom:12px;display:flex;align-items:center;
                justify-content:space-between;flex-wrap:wrap;gap:12px;animation:fadeIn .4s ease;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:38px;height:38px;border-radius:10px;background:<?= $icBg ?>;
                        display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:<?= $icColor ?>;">
                <i class="ti <?= $icono ?>"></i>
            </div>
            <div>
                <div style="font-size:.82rem;font-weight:800;color:<?= $icColor ?>;"><?= $label ?></div>
                <div style="font-size:.78rem;color:#475569;font-weight:600;">
                    Período <strong><?= htmlspecialchars($alp->nombre) ?></strong> ·
                    <?= date('d/m/Y', strtotime($alp->fecha_fin_real)) ?> ·
                    <?= (int)$alp->activos ?> pasante<?= (int)$alp->activos !== 1 ? 's' : '' ?> activo<?= (int)$alp->activos !== 1 ? 's' : '' ?>
                </div>
            </div>
        </div>
        <a href="<?= URLROOT ?>/periodos/ver/<?= (int)$alp->id ?>"
           style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:8px;
                  background:<?= $icColor ?>;color:white;font-size:.76rem;font-weight:700;
                  text-decoration:none;transition:opacity .2s;"
           onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            Gestionar <i class="ti ti-arrow-right"></i>
        </a>
    </div>
    <?php endforeach; ?>

    <div class="dashboard-kpi-grid">
        <div class="kpi-card-blue" style="background:white;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid #2563eb;display:flex;justify-content:space-between;align-items:center;">
            <div style="flex:1;">
                <p style="color:#64748b;font-size:0.82rem;margin:0 0 8px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Activos</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <h2 style="font-size:2.4rem;font-weight:800;color:#2563eb;margin:0;line-height:1;"><?= $totalActivos ?></h2>
                </div>
            </div>
            <a href="<?= URLROOT ?>/pasantes" style="background:#eff6ff;color:#2563eb;width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='none'" title="Directorio">
                <i class="ti ti-users"></i>
            </a>
        </div>

        <div class="kpi-card-yellow" style="background:white;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid #F59E0B;display:flex;justify-content:space-between;align-items:center;">
            <div style="flex:1;">
                <p style="color:#64748b;font-size:0.82rem;margin:0 0 8px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Por Asignar</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <h2 style="font-size:2.4rem;font-weight:800;color:#F59E0B;margin:0;line-height:1;"><?= $pendientesAsignar ?></h2>
                </div>
            </div>
            <a href="<?= URLROOT ?>/asignaciones" style="background:#FFFBEB;color:#D97706;width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='none'" title="Asignar">
                <i class="ti ti-link"></i>
            </a>
        </div>

        <div class="kpi-card-green" style="background:white;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid #059669;display:flex;justify-content:space-between;align-items:center;">
            <div style="flex:1;">
                <p style="color:#64748b;font-size:0.82rem;margin:0 0 8px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">En Sitio Hoy</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <h2 style="font-size:2.4rem;font-weight:800;color:#059669;margin:0;line-height:1;"><?= $asistenciasHoy ?></h2>
                </div>
            </div>
            <a href="<?= URLROOT ?>/asistencias" style="background:#F0FDF4;color:#059669;width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='none'" title="Registros">
                <i class="ti ti-fingerprint"></i>
            </a>
        </div>

        <div class="kpi-card-red" style="background:white;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid #DC2626;display:flex;justify-content:space-between;align-items:center;">
            <div style="flex:1;">
                <p style="color:#64748b;font-size:0.82rem;margin:0 0 8px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Faltas Hoy</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <h2 style="font-size:2.4rem;font-weight:800;color:#DC2626;margin:0;line-height:1;"><?= $faltasHoy ?></h2>
                </div>
            </div>
            <a href="<?= URLROOT ?>/asistencias" style="background:#FEF2F2;color:#DC2626;width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='none'" title="Justificar">
                <i class="ti ti-alert-triangle"></i>
            </a>
        </div>
    </div>

    <div class="charts-grid-50">
        <div class="card figma-card" style="padding: 24px; background: #fff; border-radius: 16px;">
            <div class="card-header-compact" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div>
                    <h3 style="margin: 0; color: #162660; font-size: 1.1rem;">Monitor de Asistencia</h3>
                    <span style="font-size: 0.8rem; color: #94A3B8;">Análisis de flujo de pasantes</span>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <div class="time-toggles" style="display: flex; background: #F8FAFC; padding: 4px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <button id="btnTimeDay" class="btn-micro active" style="font-size:0.75rem;">Diario</button>
                        <button id="btnTimeWeek" class="btn-micro" style="font-size:0.75rem;">Semanal</button>
                        <button id="btnTimeMonth" class="btn-micro" style="font-size:0.75rem;">Mensual</button>
                    </div>
                </div>
            </div>
            <div class="chart-wrapper">
                <div id="chart-main-history"></div>
            </div>
        </div>

        <div class="card figma-card" style="padding: 24px; background: #fff; border-radius: 16px;">
            <div class="card-header-compact" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 style="margin: 0; color: #162660; font-size: 1.1rem;">Desempeño Departamental</h3>
                    <span style="font-size: 0.8rem; color: #94A3B8;">Rendimiento de asistencia</span>
                </div>
            </div>
            <div class="chart-wrapper pt-3">
                <?php
                $deptData   = $data['metricas_graficos']['asistenciaDepartamento'] ?? ['labels' => [], 'series' => [], 'pasantes' => []];
                $labels     = $deptData['labels']   ?? [];
                $series     = $deptData['series']   ?? [];
                $pasantes   = $deptData['pasantes'] ?? [];
                ?>
                <?php if (empty($labels)): ?>
                    <p class="text-center text-muted p-4" style="font-size:0.85rem;">No hay departamentos registrados</p>
                <?php else: ?>
                    <style>
                        @keyframes fillProgressAnim { from { width: 0%; } }
                        .progress-bar-animated { animation: fillProgressAnim 1.2s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
                        .kpi-card-blue, .kpi-card-yellow, .kpi-card-green, .kpi-card-red {
                            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
                        }
                        .kpi-card-blue:hover { transform: translateY(-4px) !important; box-shadow: 0 12px 20px -8px rgba(37, 99, 235, 0.5) !important; }
                        .kpi-card-yellow:hover { transform: translateY(-4px) !important; box-shadow: 0 12px 20px -8px rgba(245, 158, 11, 0.5) !important; }
                        .kpi-card-green:hover { transform: translateY(-4px) !important; box-shadow: 0 12px 20px -8px rgba(16, 185, 129, 0.5) !important; }
                        .kpi-card-red:hover { transform: translateY(-4px) !important; box-shadow: 0 12px 20px -8px rgba(239, 68, 68, 0.5) !important; }
                        .dept-progress-item {
                            padding: 8px;
                            border-radius: 8px;
                            transition: background-color 0.2s ease;
                        }
                        .dept-progress-item:hover { background-color: #f8fafc; }
                        .dept-percentage {
                            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
                        }
                        .dept-progress-item:hover .dept-percentage {
                            transform: scale(1.15);
                            color: #1e293b !important;
                        }
                        .dept-empty-badge {
                            font-size: 0.68rem;
                            color: #94a3b8;
                            background: #f1f5f9;
                            border-radius: 20px;
                            padding: 1px 8px;
                            font-weight: 600;
                        }
                    </style>
                    <?php
                    $gradients = [
                        ['full' => 'linear-gradient(90deg, #3b82f6, #8b5cf6)', 'muted' => 'linear-gradient(90deg, rgba(59,130,246,0.22), rgba(139,92,246,0.14))',  'pill_bg' => 'rgba(59,130,246,0.12)',  'pill_border' => 'rgba(59,130,246,0.35)',  'pill_color' => '#2563eb'],
                        ['full' => 'linear-gradient(90deg, #10b981, #34d399)', 'muted' => 'linear-gradient(90deg, rgba(16,185,129,0.22), rgba(52,211,153,0.14))',  'pill_bg' => 'rgba(16,185,129,0.12)',  'pill_border' => 'rgba(16,185,129,0.35)',  'pill_color' => '#059669'],
                        ['full' => 'linear-gradient(90deg, #f59e0b, #fbbf24)', 'muted' => 'linear-gradient(90deg, rgba(245,158,11,0.22), rgba(251,191,36,0.14))',  'pill_bg' => 'rgba(245,158,11,0.12)',  'pill_border' => 'rgba(245,158,11,0.35)',  'pill_color' => '#d97706'],
                        ['full' => 'linear-gradient(90deg, #ec4899, #f43f5e)', 'muted' => 'linear-gradient(90deg, rgba(236,72,153,0.22), rgba(244,63,94,0.14))',   'pill_bg' => 'rgba(236,72,153,0.12)',  'pill_border' => 'rgba(236,72,153,0.35)',  'pill_color' => '#db2777'],
                        ['full' => 'linear-gradient(90deg, #6366f1, #a855f7)', 'muted' => 'linear-gradient(90deg, rgba(99,102,241,0.22), rgba(168,85,247,0.14))',  'pill_bg' => 'rgba(99,102,241,0.12)',  'pill_border' => 'rgba(99,102,241,0.35)',  'pill_color' => '#4f46e5'],
                        ['full' => 'linear-gradient(90deg, #06b6d4, #3b82f6)', 'muted' => 'linear-gradient(90deg, rgba(6,182,212,0.22), rgba(59,130,246,0.14))',   'pill_bg' => 'rgba(6,182,212,0.12)',   'pill_border' => 'rgba(6,182,212,0.35)',   'pill_color' => '#0891b2'],
                    ];
                    $colorIndex = 0;
                    ?>
                    <?php foreach ($labels as $index => $label):
                        $porcentaje    = $series[$index] ?? 0;
                        $totalPasantes = $pasantes[$index] ?? 0;
                        $sinDatos      = $totalPasantes === 0;
                        $g             = $gradients[$colorIndex % count($gradients)];
                        $colorIndex++;
                    ?>
                        <div class="dept-progress-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark" style="font-size: 0.83rem; <?= $sinDatos ? 'opacity:0.65;' : '' ?>">
                                    <?= htmlspecialchars($label) ?>
                                </span>
                                <?php if ($sinDatos): ?>
                                    <span style="font-size:0.68rem;font-weight:700;padding:2px 9px;border-radius:20px;border:1px solid <?= $g['pill_border'] ?>;background:<?= $g['pill_bg'] ?>;color:<?= $g['pill_color'] ?>;">
                                        Sin pasantes
                                    </span>
                                <?php else: ?>
                                    <span class="fw-bold dept-percentage" style="font-size: 0.85rem; color: #475569;"><?= $porcentaje ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 50rem; background-color: #f1f5f9; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                                <?php if (!$sinDatos): ?>
                                <div class="progress-bar progress-bar-animated" role="progressbar"
                                     style="width: <?= $porcentaje ?>%; border-radius: 50rem; background: <?= $g['full'] ?>;"
                                     aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                <?php else: ?>
                                <div role="progressbar"
                                     style="width:55%;border-radius:50rem;background:<?= $g['muted'] ?>;opacity:0.85;"
                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bottom-grid-60-40">
        
        <!-- PANEL IZQUIERDO: Feed de Actividad (Movido de la derecha) -->
        <div class="card figma-card activity-panel" style="padding: 24px; background: #fff; border-radius: 16px; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #162660; font-size: 1.1rem;">Feed de Actividad</h3>
                <a href="<?= URLROOT ?>/asistencias" style="font-size: 0.8rem; font-weight: 600; color: #2563eb; text-decoration: none;">Ver historial completo</a>
            </div>
            <div class="activity-list-scroll" style="overflow-y: auto; max-height: 380px; scrollbar-width: thin; padding-right: 8px;">
                <?php if (empty($actividadReciente)): ?>
                    <p style="text-align:center;color:#94a3b8;padding:24px;font-size:0.85rem;">Esperando actividad...</p>
                <?php else: ?>
                    <?php foreach (array_slice($actividadReciente, 0, 8) as $a): 
                        $metodo = $a->metodo ?? 'Kiosco'; 
                        $iconMetodo = $metodo === 'Kiosco' ? 'device-desktop' : 'hand-click';
                        $estadoBg = ['Presente' => '#dcfce7', 'Justificado' => '#fef9c3', 'Ausente' => '#fee2e2'];
                        $estadoTxt = ['Presente' => '#16a34a', 'Justificado' => '#ca8a04', 'Ausente' => '#dc2626'];
                        $est = $a->estado ?? 'Presente';
                    ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #F1F5F9;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: #EDE9FE; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                <?= strtoupper(substr(trim($a->nombres ?? 'U'), 0, 1)) ?>
                            </div>
                            <div style="line-height: 1.3;">
                                <div style="font-size: 0.85rem; font-weight: 600; color: #1e293b;"><?= htmlspecialchars(trim(($a->nombres ?? '') . ' ' . ($a->apellidos ?? ''))) ?></div>
                                <div style="font-size: 0.7rem; color: #64748b; margin-top: 2px;">
                                    <span style="background:<?= $estadoBg[$est] ?? '#f1f5f9' ?>;color:<?= $estadoTxt[$est] ?? '#64748b' ?>;padding:2px 8px;border-radius:6px;font-weight:700;font-size:0.65rem;"><?= htmlspecialchars($est) ?></span>
                                    · <i class="ti ti-<?= $iconMetodo ?>"></i> <?= htmlspecialchars($metodo) ?>
                                    · <span style="color:#94a3b8;"><?= htmlspecialchars($a->hora_registro ?? '') ?></span>
                                </div>
                            </div>
                        </div>
                        <button class="btn-micro" title="Ver" onclick="SGPModal.verUsuario(<?= $a->usuario_id ?? $a->id ?? 0 ?>)"><i class="ti ti-eye"></i></button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- PANEL DERECHO: Centro de Acción (Nuevo) -->
        <div class="card figma-card action-center-panel" style="padding: 24px; background: #fff; border-radius: 16px; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #162660; font-size: 1.1rem;">Centro de Acción</h3>
                <?php if (!empty($alertas_pendientes)): ?>
                <span class="badge" style="background: #FEF2F2; color: #DC2626; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">+<?= count($alertas_pendientes ?? []) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="activity-list-scroll" style="overflow-y: auto; max-height: 380px; scrollbar-width: thin; padding-right: 8px;">
                <?php if (empty($alertas_pendientes)): ?>
                    <div style="text-align:center; padding: 40px 20px;">
                        <div style="width: 50px; height: 50px; background: #F0FDF4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                            <i class="ti ti-check" style="font-size: 1.5rem; color: #059669;"></i>
                        </div>
                        <p style="margin: 0; color: #1E293B; font-weight: 700; font-size: 0.95rem;">Todo está al día</p>
                        <p style="margin: 5px 0 0; color: #64748B; font-size: 0.8rem;">No hay pasantes pendientes ni próximos a finalizar. ¡Buen trabajo!</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($alertas_pendientes as $alerta): 
                        $isSinAsignar = $alerta->estado === 'Sin Asignar' || empty($alerta->estado);
                        $icon = $isSinAsignar ? 'user-exclamation' : 'clock-exclamation';
                        $iconColor = $isSinAsignar ? '#D97706' : '#DC2626';
                        $iconBg = $isSinAsignar ? '#FEF3C7' : '#FEE2E2';
                        $title = htmlspecialchars(trim(($alerta->nombres ?? '') . ' ' . ($alerta->apellidos ?? '')));
                        $desc = $isSinAsignar ? 'Requiere asignación de depto.' : 'Vence pronto: ' . htmlspecialchars($alerta->fecha_fin_estimada);
                        $btnLink = URLROOT . ($isSinAsignar ? '/asignaciones' : '/pasantes');
                    ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; transition: all 0.2s;" onmouseover="this.style.borderColor='<?= $iconColor ?>'" onmouseout="this.style.borderColor='#E2E8F0'">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: <?= $iconBg ?>; color: <?= $iconColor ?>; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                    <i class="ti ti-<?= $icon ?>"></i>
                                </div>
                                <div style="line-height: 1.3;">
                                    <div style="font-size: 0.85rem; font-weight: 700; color: #1e293b;"><?= $title ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 2px;">
                                        <?= $desc ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($isSinAsignar): 
                                $instMsg = htmlspecialchars($alerta->institucion_procedencia ?? 'No registrada');
                            ?>
                            <button onclick="editarAsignacion(<?= (int)$alerta->usuario_id ?>, '<?= addslashes($title) ?>', '<?= addslashes($alerta->cedula) ?>', '<?= addslashes($instMsg) ?>')" style="background: #fff; color: <?= $iconColor ?>; border: 1px solid <?= $iconColor ?>; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);" onmouseover="this.style.background='<?= $iconColor ?>'; this.style.color='#fff';" onmouseout="this.style.background='#fff'; this.style.color='<?= $iconColor ?>';">
                                <i class="ti ti-check"></i> Asignar
                            </button>
                            <?php else: ?>
                            <a href="<?= $btnLink ?>" title="Resolver" style="background: #fff; color: <?= $iconColor ?>; border: 1px solid #E2E8F0; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; transition: all 0.2s; text-decoration: none; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" onmouseover="this.style.background='<?= $iconColor ?>'; this.style.color='#fff';" onmouseout="this.style.background='#fff'; this.style.color='<?= $iconColor ?>';">
                                <i class="ti ti-arrow-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<!-- ── Strip: Pasantías Cortas (flujo paralelo) ──────────── -->
<?php if ((int)($cortosResumen->total ?? 0) > 0): ?>
<a href="<?= URLROOT ?>/actividades/pasantias" style="text-decoration:none;display:block;margin-top:24px;margin-bottom:18px;">
<div style="background:linear-gradient(135deg,#f5f3ff 0%,#ede9fe 100%);
            border:1.5px solid #ddd6fe;border-radius:16px;
            padding:14px 22px;display:flex;align-items:center;
            justify-content:space-between;flex-wrap:wrap;gap:12px;
            transition:box-shadow .2s,transform .2s;cursor:pointer;"
     onmouseover="this.style.boxShadow='0 6px 20px rgba(124,58,237,.15)';this.style.transform='translateY(-1px)'"
     onmouseout="this.style.boxShadow='';this.style.transform=''">
    <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:40px;height:40px;border-radius:11px;
                    background:linear-gradient(135deg,#7c3aed,#a78bfa);
                    display:flex;align-items:center;justify-content:center;
                    font-size:1.1rem;color:white;flex-shrink:0;
                    box-shadow:0 3px 8px rgba(124,58,237,.3);">
            <i class="ti ti-clock-bolt"></i>
        </div>
        <div>
            <div style="font-size:.72rem;font-weight:800;color:#7c3aed;text-transform:uppercase;letter-spacing:.5px;">Flujo Paralelo</div>
            <div style="font-size:.92rem;font-weight:800;color:#3b0764;">Pasantías Cortas &amp; Act. Extras</div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
        <div style="text-align:center;">
            <div style="font-size:1.6rem;font-weight:900;color:#7c3aed;line-height:1;"><?= (int)($cortosResumen->activos ?? 0) ?></div>
            <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-top:2px;">Activos</div>
        </div>
        <div style="width:1px;height:32px;background:#ddd6fe;"></div>
        <div style="text-align:center;">
            <div style="font-size:1.6rem;font-weight:900;color:#059669;line-height:1;"><?= (int)($cortosResumen->finalizados ?? 0) ?></div>
            <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-top:2px;">Finalizados</div>
        </div>
        <div style="width:1px;height:32px;background:#ddd6fe;"></div>
        <div style="text-align:center;">
            <?php $pp=(float)($cortosResumen->pct_promedio??0); $ppC=$pp>=80?'#059669':($pp>=50?'#d97706':'#7c3aed'); ?>
            <div style="font-size:1.6rem;font-weight:900;color:<?= $ppC ?>;line-height:1;"><?= $pp ?>%</div>
            <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-top:2px;">Progreso prom.</div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:6px;
                background:linear-gradient(135deg,#7c3aed,#a78bfa);
                color:white;padding:8px 16px;border-radius:10px;
                font-size:.8rem;font-weight:700;white-space:nowrap;
                box-shadow:0 3px 10px rgba(124,58,237,.3);">
        Gestionar <i class="ti ti-arrow-right" style="font-size:.85rem;"></i>
    </div>
</div>
</a>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     AUSENTES DEL MES ACTUAL
     ══════════════════════════════════════════════════════ -->
<?php $mesActual = date('F Y', strtotime('first day of this month'));
$nombresMes = ['January'=>'enero','February'=>'febrero','March'=>'marzo','April'=>'abril',
               'May'=>'mayo','June'=>'junio','July'=>'julio','August'=>'agosto',
               'September'=>'septiembre','October'=>'octubre','November'=>'noviembre','December'=>'diciembre'];
$mesLabel = $nombresMes[date('F')] . ' ' . date('Y');
?>
<div style="background:white;border-radius:18px;padding:22px 28px;
            box-shadow:0 2px 12px rgba(0,0,0,.06);margin-top:10px;margin-bottom:24px;border:1px solid #f1f5f9;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:10px;background:#fee2e2;
                        display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#dc2626;">
                <i class="ti ti-user-x"></i>
            </div>
            <div>
                <div style="font-size:1rem;font-weight:800;color:#0f172a;">Ausentes — <?= $mesLabel ?></div>
                <div style="font-size:.74rem;color:#94a3b8;margin-top:1px;">Pasantes regulares activos con al menos 1 ausencia este mes</div>
            </div>
        </div>
        <?php if(!empty($ausentesMes)): ?>
        <span style="background:#fee2e2;color:#dc2626;padding:4px 12px;border-radius:20px;
                     font-size:.74rem;font-weight:700;">
            <?= count($ausentesMes) ?> pasante<?= count($ausentesMes)!==1?'s':'' ?> con ausencias
        </span>
        <?php endif; ?>
    </div>

    <?php if(empty($ausentesMes)): ?>
        <div style="display:flex;flex-direction:column;align-items:center;padding:32px 16px;
                    border:2px dashed #e2e8f0;border-radius:14px;text-align:center;gap:10px;">
            <div style="width:52px;height:52px;border-radius:50%;background:#f0fdf4;
                        display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#059669;">
                <i class="ti ti-calendar-check"></i>
            </div>
            <div style="font-weight:700;color:#059669;">¡Sin ausencias este mes!</div>
            <div style="font-size:.78rem;color:#94a3b8;">Todos los pasantes activos tienen asistencia en <?= $mesLabel ?>.</div>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:10px 14px;font-size:.68rem;font-weight:800;
                               color:#64748b;text-transform:uppercase;letter-spacing:.5px;border-radius:8px 0 0 8px;">Pasante</th>
                    <th style="text-align:left;padding:10px 12px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Departamento</th>
                    <th style="text-align:center;padding:10px 12px;font-size:.68rem;font-weight:800;color:#dc2626;text-transform:uppercase;letter-spacing:.5px;">Ausencias</th>
                    <th style="text-align:center;padding:10px 12px;font-size:.68rem;font-weight:800;color:#059669;text-transform:uppercase;letter-spacing:.5px;">Presentes</th>
                    <th style="text-align:center;padding:10px 12px;font-size:.68rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">% Asist.</th>
                    <th style="padding:10px 14px;border-radius:0 8px 8px 0;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($ausentesMes as $au):
                $pct = (float)($au->pct_asistencia ?? 0);
                $pctColor = $pct >= 80 ? '#059669' : ($pct >= 60 ? '#d97706' : '#dc2626');
                $pctBg    = $pct >= 80 ? '#dcfce7' : ($pct >= 60 ? '#fef3c7' : '#fee2e2');
                $ini = mb_strtoupper(mb_substr($au->nombres??'?',0,1).mb_substr($au->apellidos??'',0,1));
                $nombre = htmlspecialchars(trim(($au->nombres??'').' '.($au->apellidos??'')));
            ?>
                <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;"
                    onmouseover="this.style.background='#fafcff'" onmouseout="this.style.background=''">
                    <td style="padding:12px 14px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#172554,#2563eb);
                                        color:white;display:flex;align-items:center;justify-content:center;
                                        font-size:.75rem;font-weight:800;flex-shrink:0;"><?= $ini ?></div>
                            <div>
                                <div style="font-weight:700;color:#0f172a;"><?= $nombre ?></div>
                                <div style="font-size:.7rem;color:#94a3b8;">V-<?= htmlspecialchars($au->cedula??'') ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:12px;color:#475569;font-weight:500;"><?= htmlspecialchars($au->departamento??'—') ?></td>
                    <td style="padding:12px;text-align:center;">
                        <span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:20px;font-weight:800;font-size:.8rem;">
                            <?= (int)$au->total_ausentes ?>
                        </span>
                    </td>
                    <td style="padding:12px;text-align:center;">
                        <span style="background:#dcfce7;color:#059669;padding:3px 10px;border-radius:20px;font-weight:700;font-size:.8rem;">
                            <?= (int)$au->total_presentes ?>
                        </span>
                    </td>
                    <td style="padding:12px;text-align:center;">
                        <span style="background:<?= $pctBg ?>;color:<?= $pctColor ?>;padding:3px 10px;
                                     border-radius:20px;font-weight:800;font-size:.8rem;">
                            <?= $pct ?>%
                        </span>
                    </td>
                    <td style="padding:12px 14px;text-align:right;">
                        <a href="<?= URLROOT ?>/asistencias?vista=mensual&pasante_id=<?= (int)$au->id ?>"
                           style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;
                                  border-radius:8px;border:1.5px solid #e2e8f0;background:white;
                                  color:#2563eb;font-size:.74rem;font-weight:700;text-decoration:none;
                                  transition:all .2s;"
                           onmouseover="this.style.background='#2563eb';this.style.color='white';this.style.borderColor='#2563eb'"
                           onmouseout="this.style.background='white';this.style.color='#2563eb';this.style.borderColor='#e2e8f0'">
                            <i class="ti ti-calendar-stats"></i> Ver
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require APPROOT . '/views/inc/modal_asignacion.php'; ?>

<script>
function initAdminMonitorAsistencia() { // [SGP-FIX-PJAX-DOMCONTENTLOADED] función nombrada + ejecución inmediata si el DOM ya cargó (ver abajo)

    // ========================================
    // 2. MONITOR PRINCIPAL (Paleta Analógica)
    // ========================================
    const DashboardData = <?= json_encode($data['metricas_graficos'] ?? []) ?>;

    const ObjectDiaria  = DashboardData.asistenciaDiaria  || { cat:['Lun','Mar','Mié','Jue','Vie'], p:[0,0,0,0,0], f:[0,0,0,0,0], j:[0,0,0,0,0], motivos:[[],[],[],[],[]] };
    const ObjectSemanal = DashboardData.asistenciaSemanal || { cat:['Sem 1','Sem 2','Sem 3','Sem 4'], p:[0,0,0,0], f:[0,0,0,0], j:[0,0,0,0], motivos:[[],[],[],[]] };
    const ObjectMensual = DashboardData.asistenciaMensual || { cat:['Ene','Feb','Mar','Abr','May','Jun'], p:[0,0,0,0,0,0], f:[0,0,0,0,0,0], j:[0,0,0,0,0,0], motivos:[[],[],[],[],[],[]] };

    // Dataset activo (cambia al hacer toggle)
    let activeDataset = ObjectDiaria;

    function buildTooltip({ series, seriesIndex, dataPointIndex, w }) {
        const label    = (activeDataset.cat || [])[dataPointIndex] || '';
        const presentes = (activeDataset.p || [])[dataPointIndex] || 0;
        const justif    = (activeDataset.j || [])[dataPointIndex] || 0;
        const ausentes  = (activeDataset.f || [])[dataPointIndex] || 0;
        const total     = presentes + justif + ausentes;
        const motivos   = (activeDataset.motivos || [])[dataPointIndex] || [];

        // Porcentaje de asistencia efectiva (P + J)
        const pct = total > 0 ? Math.round(((presentes + justif) / total) * 100) : 0;

        let motivoHtml = '';
        if (justif > 0 && motivos.length > 0) {
            const items = motivos.map(m =>
                `<div style="margin-top:3px;padding:3px 8px;background:#fef3c7;border-radius:5px;font-size:0.68rem;color:#78350f;">📋 ${m}</div>`
            ).join('');
            motivoHtml = `<div style="margin-top:7px;padding-top:6px;border-top:1px solid #e2e8f0;">
                <div style="font-size:0.68rem;font-weight:700;color:#92400e;margin-bottom:2px;">Motivo(s) de justificación:</div>
                ${items}
            </div>`;
        }

        return `<div style="padding:12px 14px;min-width:160px;font-family:'Plus Jakarta Sans',sans-serif;border-radius:10px;background:#fff;box-shadow:0 4px 16px rgba(0,0,0,0.12);border:1px solid #e2e8f0;">
            <div style="font-weight:800;font-size:0.82rem;color:#1e293b;margin-bottom:8px;padding-bottom:6px;border-bottom:1px solid #f1f5f9;">${label} · ${pct}% asistencia</div>
            <div style="display:flex;flex-direction:column;gap:4px;font-size:0.78rem;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:10px;height:10px;border-radius:3px;background:#10B981;display:inline-block;flex-shrink:0;"></span>
                    <span style="color:#475569;font-weight:600;">Presentes:</span>
                    <span style="font-weight:800;color:#10B981;margin-left:auto;">${presentes}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:10px;height:10px;border-radius:3px;background:#F59E0B;display:inline-block;flex-shrink:0;"></span>
                    <span style="color:#475569;font-weight:600;">Justificados:</span>
                    <span style="font-weight:800;color:#F59E0B;margin-left:auto;">${justif}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="width:10px;height:10px;border-radius:3px;background:#EF4444;display:inline-block;flex-shrink:0;"></span>
                    <span style="color:#475569;font-weight:600;">Ausentes:</span>
                    <span style="font-weight:800;color:#EF4444;margin-left:auto;">${ausentes}</span>
                </div>
            </div>
            ${motivoHtml}
        </div>`;
    }

    const chartHistEl = document.querySelector("#chart-main-history");
    let histChart;
    if (chartHistEl) {
        histChart = new ApexCharts(chartHistEl, {
            series: [
                { name: 'Presentes',    data: ObjectDiaria.p },
                { name: 'Justificados', data: ObjectDiaria.j },
                { name: 'Ausentes',     data: ObjectDiaria.f }
            ],
            chart: { height: 300, type: 'bar', stacked: false, toolbar: { show: false }, fontFamily: 'Plus Jakarta Sans, sans-serif' },
            plotOptions: { bar: { horizontal: false, columnWidth: '45%', borderRadius: 4, borderRadiusApplication: 'end' } },
            dataLabels: { enabled: false },
            colors: ['#10B981', '#F59E0B', '#EF4444'],
            grid: { borderColor: '#F1F5F9', strokeDashArray: 4 },
            xaxis: { categories: ObjectDiaria.cat, axisBorder: { show: false }, axisTicks: { show: false } },
            legend: { position: 'top', horizontalAlign: 'right' },
            tooltip: { custom: buildTooltip }
        });
        histChart.render();

        // Toggles con actualización del dataset activo
        const switchTo = (btnId, dataObj) => {
            document.getElementById(btnId).addEventListener('click', (e) => {
                activeDataset = dataObj;
                histChart.updateOptions({ xaxis: { categories: dataObj.cat } });
                histChart.updateSeries([
                    { name: 'Presentes',    data: dataObj.p },
                    { name: 'Justificados', data: dataObj.j },
                    { name: 'Ausentes',     data: dataObj.f }
                ]);
                document.querySelectorAll('.time-toggles .btn-micro').forEach(b => b.classList.remove('active'));
                e.currentTarget.classList.add('active');
            });
        };
        switchTo('btnTimeDay',   ObjectDiaria);
        switchTo('btnTimeWeek',  ObjectSemanal);
        switchTo('btnTimeMonth', ObjectMensual);
    }



    // ========================================
    // ACTUALIZAR RELOJ Y FECHA
    // ========================================
    function updateDateTime() {
        const now = new Date();
        const dateStr = now.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        const timeStr = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        const d = document.getElementById('currentDate');
        const t = document.getElementById('currentTime');
        if (d) d.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
        if (t) t.textContent = timeStr.toUpperCase();
    }
    updateDateTime(); setInterval(updateDateTime, 1000);

    // ========================================
    // ALERTAS DE ÉXITO/ERROR (NOTIFICACIONES)
    // ========================================
    // Las notificaciones usan Session::getFlash y se muestran vía Notyf
    // [SGP-FIX-PJAX-DOMCONTENTLOADED] ejecución directa: para cuando esta función corre, el DOM ya está listo (ver invocación al final del archivo)
    <?php if ($success = Session::getFlash('success')): ?>
        if (typeof NotificationService !== 'undefined') { NotificationService.success('<?= addslashes($success) ?>'); }
    <?php endif; ?>
    <?php if ($error = Session::getFlash('error')): ?>
        if (typeof NotificationService !== 'undefined') { NotificationService.error('<?= addslashes($error) ?>'); }
    <?php endif; ?>
    // También mantenemos los nombres flash anteriores por retrocompatibilidad
    <?php if (isset($_SESSION['flash_success'])): ?>
        if (typeof NotificationService !== 'undefined') { NotificationService.success('<?= addslashes($_SESSION['flash_success']) ?>'); }
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        if (typeof NotificationService !== 'undefined') { NotificationService.error('<?= addslashes($_SESSION['flash_error']) ?>'); }
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
}
if (document.readyState !== 'loading') { initAdminMonitorAsistencia(); } else { document.addEventListener('DOMContentLoaded', initAdminMonitorAsistencia); } // [SGP-FIX-PJAX-DOMCONTENTLOADED]

// ── Cerrar período vencido desde el dashboard ────────────────
// [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
var _ADMIN_CSRF = '<?= Session::generateCsrfToken() ?>';

// ── Reactivar período cerrado ──────────────────────────────────
function reactivarPeriodoDashboard(periodoId, periodoNombre) {
    Swal.fire({
        title: 'Reabrir periodo',
        html: `<p style="color:#475569;font-size:.9rem;">¿Deseas reabrir el período <strong>${periodoNombre}</strong>?<br>
               Los pasantes volverán a estado <strong style="color:#059669;">Activo</strong> y podrás evaluarlos.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-lock-open"></i> Sí, reabrir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        showLoaderOnConfirm: true,
        preConfirm: () => fetch(URLROOT + '/admin/reactivarPeriodo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _ADMIN_CSRF },
            body: JSON.stringify({ periodo_id: periodoId, csrf_token: _ADMIN_CSRF })
        }).then(r => r.json()).then(res => {
            if (!res.success) Swal.showValidationMessage(res.message);
            return res;
        }).catch(err => Swal.showValidationMessage('Error: ' + err.message)),
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (result.isConfirmed && result.value?.success) {
            Swal.fire({
                icon: 'success', title: 'Periodo reactivado',
                html: `<p style="color:#475569;">${result.value.message}<br>
                       <strong style="color:#059669;">${result.value.pasantes_reactivados} pasante(s)</strong> reactivados. Ya pueden ser evaluados.</p>`,
                confirmButtonColor: '#059669', confirmButtonText: 'Entendido'
            }).then(() => location.reload());
        }
    });
}

async function cerrarPeriodoDesdeDashboard(periodoId, periodoNombre, activosPendientes) {
    // 1. Verificar evaluaciones pendientes — BLOQUEA el cierre si hay alguna
    try {
        const info = await fetch(`${URLROOT}/admin/infoPreCierre?periodo_id=${periodoId}`).then(r => r.json());
        // BLOQUEO 1: pasantes que aún no han llegado a su fecha_fin_estimada
        if (info.success && info.no_terminaron > 0) {
            const detalle = (info.no_terminaron_det || []).slice(0, 5).map(p =>
                `<li style="padding:4px 0;border-bottom:1px solid #bfdbfe;color:#1e3a8a;font-weight:600;">
                    ${p.nombre} — termina el <strong>${p.fecha_fin}</strong>
                    <span style="color:#3b82f6;font-size:.75rem;"> (${p.dias_restantes} día${p.dias_restantes!==1?'s':''} restante${p.dias_restantes!==1?'s':''})</span>
                 </li>`
            ).join('');
            const mas = info.no_terminaron > 5 ? `<li style="color:#94a3b8;font-size:.76rem;">...y ${info.no_terminaron - 5} más</li>` : '';

            Swal.fire({
                title: 'No se puede cerrar aún',
                html: `<div style="text-align:left;">
                           <p style="color:#475569;font-size:.9rem;margin:0 0 12px;">
                               <strong>${info.no_terminaron} pasante(s)</strong> de <strong>${periodoNombre}</strong>
                               aún no han llegado a su fecha de finalización individual.
                           </p>
                           <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:12px;">
                               <div style="font-size:.78rem;font-weight:800;color:#1e3a8a;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                                   <i class="ti ti-calendar-clock"></i> Pasantes con período vigente:
                               </div>
                               <ul style="margin:0;padding-left:4px;list-style:none;font-size:.81rem;">${detalle}${mas}</ul>
                           </div>
                           <div style="background:#f0fdf4;border-radius:10px;padding:10px 14px;font-size:.8rem;color:#15803d;display:flex;align-items:center;gap:8px;">
                               <i class="ti ti-info-circle" style="flex-shrink:0;"></i>
                               El sistema los finalizará automáticamente cuando llegue su fecha de cierre individual.
                           </div>
                       </div>`,
                icon: 'warning',
                iconColor: '#2563eb',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#2563eb',
            });
            return; // BLOQUEO
        }

        // BLOQUEO 2: pasantes sin evaluación registrada
        if (info.success && info.sin_evaluacion > 0) {
            const nombres = info.nombres.slice(0, 8).map(n =>
                `<li style="padding:4px 0;border-bottom:1px solid #fde68a;color:#92400e;font-weight:600;">${n}</li>`
            ).join('');
            const mas = info.nombres.length > 8
                ? `<li style="color:#94a3b8;font-size:.76rem;">...y ${info.nombres.length - 8} más</li>` : '';

            Swal.fire({
                title: 'No se puede cerrar el periodo',
                html: `<div style="text-align:left;">
                           <p style="color:#475569;font-size:.9rem;margin:0 0 12px;">
                               El período <strong>${periodoNombre}</strong> tiene
                               <strong style="color:#dc2626;">${info.sin_evaluacion} pasante(s) sin evaluación</strong>.
                               Debes registrar la evaluación de todos antes de cerrar.
                           </p>
                           <div style="background:#fef9c3;border:1.5px solid #fde68a;border-radius:10px;padding:12px 16px;margin-bottom:12px;">
                               <div style="font-size:.78rem;font-weight:800;color:#b45309;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                                   <i class="ti ti-clipboard-x"></i> Pendientes por evaluar:
                               </div>
                               <ul style="margin:0;padding-left:4px;list-style:none;font-size:.82rem;">${nombres}${mas}</ul>
                           </div>
                           <div style="background:#eff6ff;border-radius:10px;padding:10px 14px;font-size:.8rem;color:#1e3a8a;display:flex;align-items:center;gap:8px;">
                               <i class="ti ti-info-circle" style="font-size:1rem;flex-shrink:0;"></i>
                               Ve al módulo <strong>Evaluaciones</strong>, registra las evaluaciones faltantes y vuelve a cerrar el período.
                           </div>
                       </div>`,
                icon: 'error',
                iconColor: '#dc2626',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-star"></i> Ir a Evaluaciones',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
            }).then(result => {
                if (result.isConfirmed) window.location.href = URLROOT + '/evaluaciones';
            });
            return; // BLOQUEO
        }
    } catch(e) { /* si falla la consulta, continuar con el flujo normal */ }

    // 2. Todos evaluados — mostrar confirmación de cierre normal
    Swal.fire({
        title: 'Cerrar periodo y finalizar pasantes?',
        html: `<div style="text-align:left;font-size:.9rem;color:#475569;line-height:1.7;">
                   <p style="margin:0 0 10px;">Estas a punto de cerrar <strong style="color:#0f172a;">${periodoNombre}</strong>.</p>
                   <div style="background:#f0fdf4;border-radius:10px;padding:10px 14px;border:1px solid #bbf7d0;margin-bottom:10px;">
                       <div style="display:flex;align-items:center;gap:7px;color:#15803d;font-size:.82rem;font-weight:700;">
                           <i class="ti ti-circle-check"></i> Todos los pasantes tienen evaluacion registrada
                       </div>
                   </div>
                   <div style="background:#f8fafc;border-radius:10px;padding:12px 16px;border:1px solid #e2e8f0;margin-bottom:10px;">
                       <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                           <i class="ti ti-users" style="color:#2563eb;"></i>
                           <strong>${activosPendientes} pasante(s)</strong> cambiaran a <strong style="color:#7c3aed;">Finalizado</strong>
                       </div>
                       <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                           <i class="ti ti-bell" style="color:#059669;"></i>
                           Recibiran notificacion de culminacion automaticamente
                       </div>
                       <div style="display:flex;align-items:center;gap:8px;">
                           <i class="ti ti-file-type-pdf" style="color:#dc2626;"></i>
                           Podran generar su carta de culminacion inmediatamente
                       </div>
                   </div>
                   <p style="margin:0;font-size:.78rem;color:#94a3b8;">Esta accion quedara registrada en la bitacora.</p>
               </div>`,
        icon: 'warning',
        iconColor: '#dc2626',
        showCancelButton: true,
        confirmButtonText: '<i class="ti ti-lock"></i> Si, cerrar periodo',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return fetch(URLROOT + '/admin/cerrarPeriodo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': _ADMIN_CSRF
                },
                body: JSON.stringify({ periodo_id: periodoId, csrf_token: _ADMIN_CSRF })
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(res => {
                if (!res.success) Swal.showValidationMessage(res.message || 'Error al cerrar el período.');
                return res;
            })
            .catch(err => Swal.showValidationMessage('Error: ' + err.message));
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (result.isConfirmed && result.value?.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Período cerrado!',
                html: `<p style="color:#475569;">${result.value.message}<br>
                       <strong style="color:#7c3aed;">${result.value.pasantes_finalizados} pasante(s)</strong> finalizados y notificados.</p>`,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Entendido'
            }).then(() => location.reload());
        }
    });
}
</script>
