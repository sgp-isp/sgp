<?php
/**
 * Vista: Gestión de Asignaciones — Admin
 * Módulo de asignación de pasantes a tutores y departamentos
 */

$asignaciones  = $data['asignaciones']  ?? [];
$departamentos = $data['departamentos'] ?? [];
$tutores       = $data['tutores']       ?? [];
$periodos      = $data['periodos']      ?? [];
$total         = $data['total']         ?? 0;
$activos       = $data['activos']       ?? 0;
$pendientes    = $data['pendientes']    ?? 0;
$sinAsignar    = $data['sinAsignar']    ?? 0;
$finalizados   = $data['finalizados']   ?? 0;

$estadoConfig = [
    'Sin Asignar' => ['bg' => '#fef9c3', 'color' => '#ca8a04', 'icon' => 'ti-clock'],
    'Pendiente'   => ['bg' => '#fed7aa', 'color' => '#ea580c', 'icon' => 'ti-hourglass'],
    'Activo'      => ['bg' => '#dcfce7', 'color' => '#16a34a', 'icon' => 'ti-check'],
    'Finalizado'  => ['bg' => '#ede9fe', 'color' => '#7c3aed', 'icon' => 'ti-award'],
    'Retirado'    => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'ti-user-off'],
    'cancelado'   => ['bg' => '#fee2e2', 'color' => '#dc2626', 'icon' => 'ti-x'],
    'activo'      => ['bg' => '#dcfce7', 'color' => '#16a34a', 'icon' => 'ti-check'],
    'finalizado'  => ['bg' => '#ede9fe', 'color' => '#7c3aed', 'icon' => 'ti-award'],
];
?>

<style>
/* ===== RESPONSIVE ===== */
@media (max-width: 1200px) {
    .asig-stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 768px) {
    .asig-stats-grid { grid-template-columns: 1fr !important; }
}

/* ===== ASIG-KPI-CARD (Clase Aislada — evita conflictos de cascada kpi-card) ===== */
.asig-kpi-card {
    background: #fff;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(22,38,96,0.05);
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.asig-kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(22,38,96,0.1);
}
.asig-kpi-card .kpi-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.asig-kpi-card .kpi-label {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}
.asig-kpi-card .kpi-value {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.1;
    margin: 4px 0 0;
}
.asig-kpi-card .kpi-sub {
    font-size: 0.78rem;
    color: #94a3b8;
    margin: 2px 0 0;
}
.asig-kpi-card .kpi-icon-box {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
@media(max-width:1100px){
    /* [SGP-FIX-ASIG-BANNER] Antes solo se activaba a 768px: entre ~769px y
       ~1100px (p.ej. con DevTools abierto o pantallas más angostas) el
       banner seguía en fila única sin wrap y los botones quedaban
       encimados/cortados contra el título. Se adelanta el quiebre. */
    .asig-banner { flex-wrap:wrap !important; padding:22px 20px !important; gap:14px !important; }
    .asig-banner-actions { width:100%;flex-wrap:wrap; }
    .asig-banner-actions button { flex:1;min-width:140px;justify-content:center;font-size:.82rem !important;padding:10px 14px !important; }
}
@media(max-width:480px){
    .asig-banner-actions { flex-direction:column; }
    .asig-banner-actions button { width:100%; }
}
</style>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">

    <!-- ===== BANNER ===== -->
    <div class="asig-banner" style="background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%); border-radius: 20px; padding: 32px 40px; margin-bottom: 28px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between;">
        <div style="position: absolute; top: -40px; right: -40px; width: 220px; height: 220px; background: rgba(255,255,255,0.04); border-radius: 50%;"></div>
        <div style="position: absolute; bottom: -60px; left: 30%; width: 160px; height: 160px; background: rgba(255,255,255,0.03); border-radius: 50%;"></div>
        <div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: rgba(255,255,255,0.15); border-radius: 12px; padding: 10px;">
                    <i class="ti ti-link" style="font-size: 28px; color: white;"></i>
                </div>
                <div>
                    <h1 style="color: white; font-size: 1.8rem; font-weight: 700; margin: 0;">Gestión de Asignaciones</h1>
                    <p style="color: rgba(255,255,255,0.7); margin: 0; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                        <i class="ti ti-arrows-exchange"></i>
                        Asignación de Pasantes a Tutores y Departamentos
                        <span style="background: rgba(255,255,255,0.2); color: white; padding: 2px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                            <?= $total ?> registros
                        </span>
                    </p>
                </div>
            </div>
        </div>
        <div class="asig-banner-actions" style="display: flex; gap: 12px; z-index: 1;">
            <button onclick="document.getElementById('modalCalcHoras').style.display='flex'"
                style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); color: #fff; border: 1px solid rgba(255,255,255,0.3); padding: 12px 20px; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; transition: all 0.25s; z-index: 1; flex-shrink: 0;"
                onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <i class="ti ti-calculator" style="font-size: 1.1rem;"></i>
                Calculadora de Horas
            </button>
            <button onclick="abrirModalAsignacion()"
                style="background: white; color: #162660; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.25s; z-index: 1; flex-shrink: 0;"
                onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.3)'"
                onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)'">
                <i class="ti ti-plus" style="font-size: 1.1rem;"></i>
                Nueva Asignación
            </button>
        </div>
    </div>

    <!-- ===== PREMIUM SYSTEM TIP (UX) ===== -->
    <div style="background: linear-gradient(to right, #f8fafc, #edf2f9); border: 1px solid #e2e8f0; border-left: 4px solid #2563eb; border-radius: 12px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        <div style="background: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(37,99,235,0.15); flex-shrink: 0;">
            <i class="ti ti-bulb" style="color: #2563eb; font-size: 1.3rem;"></i>
        </div>
        <div>
            <h4 style="margin: 0 0 4px 0; color: #1e293b; font-size: 0.95rem; font-weight: 700;">Tip del Sistema SGP</h4>
            <p style="margin: 0; color: #64748b; font-size: 0.85rem; line-height: 1.5;">
                Recuerda que solo los pasantes con estado <strong style="color: #10b981; background: #dcfce7; padding: 2px 6px; border-radius: 4px;">Activo</strong> podrán registrar sus horas en el módulo de <b style="color: #1e293b;">Asistencias</b>. Asegúrate de configurar correctamente la fecha de inicio y el horario asignado.
            </p>
        </div>
    </div>

    <?php if (empty($data['periodoActivo'])): ?>
    <!-- BANNER PERÍODO EN RECESO (Coherencia Multi-Período) -->
    <div style="background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%); border: 1.5px solid #fde68a; border-radius: 16px; padding: 18px 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                <i class="ti ti-calendar-off"></i>
            </div>
            <div>
                <h4 style="margin: 0; color: #92400e; font-size: 0.95rem; font-weight: 800;">Receso Académico — Sin Período Activo</h4>
                <p style="margin: 2px 0 0; color: #b45309; font-size: 0.82rem;">
                    Actualmente no hay un período académico activo. Las cohortes anteriores concluyeron sus pasantías y sus expedientes están resguardados en el módulo de Períodos.
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="<?= URLROOT ?>/periodos" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; background: #d97706; color: white; border-radius: 10px; font-weight: 700; font-size: 0.82rem; text-decoration: none; box-shadow: 0 4px 10px rgba(217,119,6,0.25);">
                <i class="ti ti-calendar-event"></i> Gestionar Períodos
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== KPI CARDS (Asig Standard — Clase Aislada) ===== -->
    <div class="asig-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px;">
        <?php foreach ([
            ['label' => 'Total Pasantes', 'value' => $total,       'sub' => 'registrados', 'color' => '#1e3a8a', 'icon' => 'ti-users',        'filter' => ''],
            ['label' => 'Activos',        'value' => $activos,     'sub' => 'en pasantía', 'color' => '#16a34a', 'icon' => 'ti-circle-check', 'filter' => 'Activo'],
            ['label' => 'Sin Asignar',    'value' => $pendientes,  'sub' => 'sin tutor/depto.', 'color' => '#f59e0b', 'icon' => 'ti-user-exclamation', 'filter' => 'Sin Asignar'],
            ['label' => 'Finalizados',    'value' => $finalizados, 'sub' => 'completados', 'color' => '#7c3aed', 'icon' => 'ti-award',        'filter' => 'Finalizado'],
        ] as $s): ?>
        <div class="asig-kpi-card"
             onclick="filtrarPorEstado('<?= $s['filter'] ?>')"
             style="border-left: 4px solid <?= $s['color'] ?>;">
            <div class="kpi-info">
                <p class="kpi-label"><?= $s['label'] ?></p>
                <h2 class="kpi-value" style="color: <?= $s['color'] ?>;"><?= $s['value'] ?></h2>
                <p class="kpi-sub"><?= $s['sub'] ?></p>
            </div>
            <div class="kpi-icon-box" style="background: <?= $s['color'] ?>18;">
                <i class="ti <?= $s['icon'] ?>" style="color: <?= $s['color'] ?>; font-size: 1.4rem;"></i>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros Rápidos y Buscador -->
    <div style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; background: white; padding: 16px 24px; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">
        
        <div style="display: flex; align-items: center; gap: 12px;">
            <p style="font-size: 0.9rem; font-weight: 700; color: #64748b; margin: 0; display:flex; align-items:center; gap:6px;">
                <i class="ti ti-filter" style="font-size:1.1rem; color:#2563eb;"></i> Filtrar:
            </p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;" id="ventoFilterPillsAsignaciones">
                <button class="vento-pill active" onclick="filtrarPorEstado('')" style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                    Todos
                </button>
                <button class="vento-pill" onclick="filtrarPorEstado('Activo')" style="background: #f1f5f9; color: #475569; border: none; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="if(!this.classList.contains('active')) {this.style.background='#e2e8f0'}" onmouseout="if(!this.classList.contains('active')) {this.style.background='#f1f5f9'}">
                    Activos
                </button>
                <button class="vento-pill" onclick="filtrarPorEstado('Pendiente')" style="background: #f1f5f9; color: #475569; border: none; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="if(!this.classList.contains('active')) {this.style.background='#e2e8f0'}" onmouseout="if(!this.classList.contains('active')) {this.style.background='#f1f5f9'}">
                    Pendientes
                </button>
                <button class="vento-pill" onclick="filtrarPorEstado('Sin Asignar')" style="background: #f1f5f9; color: #475569; border: none; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="if(!this.classList.contains('active')) {this.style.background='#e2e8f0'}" onmouseout="if(!this.classList.contains('active')) {this.style.background='#f1f5f9'}">
                    Sin Asignar
                </button>
                <button class="vento-pill" onclick="filtrarPorEstado('Finalizado')" style="background: #f1f5f9; color: #475569; border: none; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="if(!this.classList.contains('active')) {this.style.background='#e2e8f0'}" onmouseout="if(!this.classList.contains('active')) {this.style.background='#f1f5f9'}">
                    Finalizados
                </button>
            </div>
        </div>

        <div style="position: relative;">
            <i class="ti ti-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; pointer-events: none;"></i>
            <input type="text" id="searchAsignaciones" placeholder="Buscar por Cédula o Nombre..."
                style="padding: 10px 16px 10px 42px; border: 1.5px solid #e5e7eb; border-radius: 12px; font-size: 0.9rem; color: #1e293b; outline: none; width: 280px; transition: all 0.2s; background: #f8fafc;"
                onfocus="this.style.borderColor='#2563eb';this.style.background='white';this.style.boxShadow='0 0 0 4px rgba(37,99,235,0.1)'"
                onblur="this.style.borderColor='#e5e7eb';this.style.background='#f8fafc';this.style.boxShadow='none'">
        </div>
    </div>

    <!-- ===== TABLA DE ASIGNACIONES (Boxy SaaS) ===== -->
    <?php if (empty($asignaciones)): ?>
    <!-- EMPTY STATE INTELIGENTE (Cero errores DataTables TN/18) -->
    <div class="sgp-solo-desktop" style="background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 56px 30px; text-align: center; border: 0;">
        <div style="width: 76px; height: 76px; border-radius: 22px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 8px 24px rgba(37,99,235,0.12);">
            <i class="ti ti-link-off" style="font-size: 2.3rem;"></i>
        </div>
        <h3 style="font-size: 1.3rem; font-weight: 800; color: #1e293b; margin: 0 0 10px;">
            <?= empty($data['periodoActivo']) ? 'No hay asignaciones para el período actual' : 'No hay asignaciones registradas en este período' ?>
        </h3>
        <p style="font-size: 0.92rem; color: #64748b; line-height: 1.6; max-width: 580px; margin: 0 auto 26px;">
            <?= empty($data['periodoActivo'])
                ? 'Las cohortes anteriores ya culminaron sus pasantías exitosamente. Para comenzar a asignar nuevos pasantes a sus tutores y departamentos, activa un nuevo período o pulsa el botón a continuación.'
                : 'Aún no se han asignado pasantes a tutores y departamentos en este período académico. Comienza formalizando la primera asignación.' ?>
        </p>
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <button onclick="abrirModalAsignacion()"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 26px; background: #2563eb; color: #fff; border: none; border-radius: 12px; font-weight: 700; font-size: 0.92rem; cursor: pointer; box-shadow: 0 4px 14px rgba(37,99,235,0.25); transition: transform 0.2s;"
                onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                <i class="ti ti-plus"></i> Nueva Asignación
            </button>
            <?php if (empty($data['periodoActivo'])): ?>
            <a href="<?= URLROOT ?>/periodos"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; background: #f8fafc; color: #334155; border: 1.5px solid #e2e8f0; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.92rem; transition: background 0.2s;"
                onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                <i class="ti ti-calendar-event"></i> Ver Historial de Períodos
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="sgp-solo-desktop" style="background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; border: 0;">
        <div class="table-responsive">
            <table id="tablaAsignaciones" class="table table-hover align-middle mb-0" style="width: 100%;">
                <thead class="bg-light text-uppercase text-muted small fw-bold">
                    <tr>
                        <?php foreach (['Pasante', 'Cédula', 'Tutor Asignado', 'Departamento', 'Horario', 'Progreso', 'Estado', 'Acciones'] as $h): ?>
                        <th class="px-4 py-3 border-0"><?= $h ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php foreach ($asignaciones as $a):
                        $estado    = $a->estado_pasantia ?? 'Sin Asignar';
                        $cfg       = $estadoConfig[$estado] ?? $estadoConfig['Sin Asignar'];
                        $hMeta     = (int)($a->horas_meta ?? 480);
                        $hCumpl    = (int)($a->horas_acumuladas ?? 0);
                        $progreso  = $hMeta > 0 ? min(100, round(($hCumpl / $hMeta) * 100)) : 0;
                        $pColor    = $progreso >= 80 ? '#10b981' : ($progreso >= 50 ? '#f59e0b' : '#ef4444');
                        $iniciales = strtoupper(substr($a->nombres ?? '?', 0, 1) . substr($a->apellidos ?? '', 0, 1));
                        $nombre    = htmlspecialchars(($a->nombres ?? '') . ' ' . ($a->apellidos ?? ''));
                        $tutorNom  = trim(($a->tutor_nombres ?? '') . ' ' . ($a->tutor_apellidos ?? ''));
                        $tutorNom  = !empty(trim($tutorNom)) ? htmlspecialchars($tutorNom) : '<span style="color:#94a3b8;">Sin tutor</span>';
                    ?>
                    <tr class="fila-asignacion" data-estado="<?= htmlspecialchars($estado) ?>" data-nombre="<?= strtolower($nombre) ?>"
                        style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s;"
                        onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <td style="padding: 16px 20px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #162660, #3b82f6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.85rem; flex-shrink: 0;">
                                    <?= $iniciales ?>
                                </div>
                                <span style="font-weight: 600; color: #1e293b; font-size: 0.9rem;"><?= $nombre ?></span>
                            </div>
                        </td>
                        <td style="padding: 16px 20px; color: #64748b; font-size: 0.85rem;"><?= htmlspecialchars($a->cedula ?? '—') ?></td>
                        <td style="padding: 16px 20px; font-size: 0.85rem; font-weight: 500; color: #1e293b;"><?= $tutorNom ?></td>
                        <td style="padding: 16px 20px; color: #64748b; font-size: 0.85rem;"><?= htmlspecialchars($a->departamento_nombre ?? '—') ?></td>
                        <td style="padding: 16px 20px; color: #64748b; font-size: 0.85rem;">
                            <?php if (!empty($a->fecha_inicio_pasantia)): ?>
                                <span style="font-size: 0.8rem;"><?= date('d/m/Y', strtotime($a->fecha_inicio_pasantia)) ?></span>
                                <?php if (!empty($a->fecha_fin_estimada)): ?>
                                    <br><span style="font-size: 0.75rem; color: #94a3b8;">al <?= date('d/m/Y', strtotime($a->fecha_fin_estimada)) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px 20px; min-width: 140px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                    <div style="width: <?= $progreso ?>%; height: 100%; background: <?= $pColor ?>; border-radius: 4px; transition: width 0.5s;"></div>
                                </div>
                                <span style="font-size: 0.8rem; font-weight: 700; color: <?= $pColor ?>; min-width: 35px;"><?= $progreso ?>%</span>
                            </div>
                            <span style="font-size: 0.7rem; color: #94a3b8;"><?= $hCumpl ?>/<?= $hMeta ?> hrs</span>
                        </td>
                        <td style="padding: 16px 20px;" data-search="<?= htmlspecialchars($estado) ?>" data-order="<?= htmlspecialchars($estado) ?>">
                            <span style="background: <?= $cfg['bg'] ?>; color: <?= $cfg['color'] ?>; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="ti <?= $cfg['icon'] ?>" style="font-size: 0.85rem;"></i>
                                <?= htmlspecialchars($estado) ?>
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="d-flex justify-content-center" style="gap: 12px;">
                                <button onclick="verDetalleAsignacion(<?= (int)$a->pasante_id ?>)"
                                        class="btn btn-sm border-0 shadow-sm transition-all" 
                                        data-bs-toggle="tooltip" title="Ver detalles" 
                                        style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #2563eb; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-eye fs-5 text-white"></i>
                                </button>
                                
                                <?php if (in_array($estado, ['Sin Asignar', 'Pendiente'])): 
                                    $instAsig = htmlspecialchars($a->institucion_procedencia ?? 'No registrada');
                                ?>
                                <button onclick="editarAsignacion(<?= (int)$a->pasante_id ?>, '<?= addslashes($nombre) ?>', '<?= addslashes($a->cedula) ?>', '<?= addslashes($instAsig) ?>')"
                                        class="btn btn-sm border-0 shadow-sm transition-all" 
                                        data-bs-toggle="tooltip" title="Editar asignación" 
                                        style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #f59e0b; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-pencil fs-5 text-white"></i>
                                </button>
                                <?php endif; ?>

                                <?php if ($estado === 'Activo'): ?>
                                <button onclick="finalizarAsignacion(<?= (int)$a->pasante_id ?>, '<?= addslashes($nombre) ?>')"
                                        class="btn btn-sm border-0 shadow-sm transition-all" 
                                        data-bs-toggle="tooltip" title="Finalizar pasantía" 
                                        style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #7c3aed; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-flag fs-5 text-white"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- CARD VIEW — solo en móvil (< 992px) -->
    <div class="sgp-solo-mobile gap-3 px-1 pb-3" id="cardsAsignaciones">
    <?php if (empty($asignaciones)): ?>
        <div style="background: white; border-radius: 16px; padding: 32px 20px; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <div style="width: 56px; height: 56px; border-radius: 16px; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                <i class="ti ti-link-off" style="font-size: 1.8rem;"></i>
            </div>
            <p style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 6px;">No hay asignaciones registradas</p>
            <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 18px;">
                <?= empty($data['periodoActivo']) ? 'El período académico actual está cerrado o en receso.' : 'Aún no se han asignado pasantes en este período.' ?>
            </p>
            <button onclick="abrirModalAsignacion()"
                style="padding: 10px 20px; background: #2563eb; color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 0.85rem;">
                <i class="ti ti-plus"></i> Nueva Asignación
            </button>
        </div>
    <?php else: ?>
    <?php foreach ($asignaciones as $a):
        $estado    = $a->estado_pasantia ?? 'Sin Asignar';
        $cfg       = $estadoConfig[$estado] ?? $estadoConfig['Sin Asignar'];
        $hMeta     = (int)($a->horas_meta ?? 480);
        $hCumpl    = (int)($a->horas_acumuladas ?? 0);
        $progreso  = $hMeta > 0 ? min(100, round(($hCumpl / $hMeta) * 100)) : 0;
        $pColor    = $progreso >= 80 ? '#10b981' : ($progreso >= 50 ? '#f59e0b' : '#ef4444');
        $iniciales = strtoupper(substr($a->nombres ?? '?', 0, 1) . substr($a->apellidos ?? '', 0, 1));
        $nombre    = htmlspecialchars(($a->nombres ?? '') . ' ' . ($a->apellidos ?? ''));
        $tutorNom  = trim(($a->tutor_nombres ?? '') . ' ' . ($a->tutor_apellidos ?? ''));
        $tutorNom  = !empty(trim($tutorNom)) ? htmlspecialchars($tutorNom) : 'Sin tutor';
    ?>
    <div class="bca-card">

        <!-- Encabezado: avatar + nombre + badge estado -->
        <div class="bca-header">
            <div class="bca-avatar"><?= $iniciales ?></div>
            <div class="bca-info">
                <span class="bca-nombre"><?= $nombre ?></span>
                <span class="bca-cedula">C.I: <?= htmlspecialchars($a->cedula ?? '—') ?></span>
            </div>
            <span class="bca-badge"
                  style="background:<?= $cfg['bg'] ?>; color:<?= $cfg['color'] ?>;">
                <i class="ti <?= $cfg['icon'] ?>" style="font-size:0.7rem; margin-right:2px;"></i>
                <?= htmlspecialchars($estado) ?>
            </span>
        </div>

        <!-- Cuerpo: datos secundarios -->
        <div class="bca-body">
            <div class="bca-row">
                <span class="bca-label">Tutor</span>
                <span class="bca-value"><?= $tutorNom ?></span>
            </div>
            <div class="bca-row">
                <span class="bca-label">Departamento</span>
                <span class="bca-value"><?= htmlspecialchars($a->departamento_nombre ?? '—') ?></span>
            </div>
            <?php if (!empty($a->fecha_inicio_pasantia)): ?>
            <div class="bca-row">
                <span class="bca-label">Inicio</span>
                <span class="bca-value"><?= date('d/m/Y', strtotime($a->fecha_inicio_pasantia)) ?><?php if (!empty($a->fecha_fin_estimada)): ?> — <?= date('d/m/Y', strtotime($a->fecha_fin_estimada)) ?><?php endif; ?></span>
            </div>
            <?php endif; ?>
            <div class="bca-row">
                <span class="bca-label">Progreso</span>
                <div class="bca-progress-wrap">
                    <div class="bca-progress-bar">
                        <div class="bca-progress-fill" style="width:<?= $progreso ?>%; background:<?= $pColor ?>;"></div>
                    </div>
                    <span class="bca-progress-pct" style="color:<?= $pColor ?>;"><?= $progreso ?>%</span>
                </div>
            </div>
            <div class="bca-row">
                <span class="bca-label">Horas</span>
                <span class="bca-value"><?= $hCumpl ?> / <?= $hMeta ?> hrs</span>
            </div>
        </div>

        <!-- Acciones — mismas funciones JS que la tabla -->
        <div class="bca-actions">
            <button class="bca-btn bca-btn-view"
                    onclick="verDetalleAsignacion(<?= (int)$a->pasante_id ?>)"
                    title="Ver detalles">
                <i class="ti ti-eye"></i> Ver
            </button>
            <?php if (in_array($estado, ['Sin Asignar', 'Pendiente'])):
                $instAsig = htmlspecialchars($a->institucion_procedencia ?? 'No registrada'); ?>
            <button class="bca-btn bca-btn-edit"
                    onclick="editarAsignacion(<?= (int)$a->pasante_id ?>, '<?= addslashes($nombre) ?>', '<?= addslashes($a->cedula) ?>', '<?= addslashes($instAsig) ?>')"
                    title="Editar asignación">
                <i class="ti ti-pencil"></i> Editar
            </button>
            <?php endif; ?>
            <?php if ($estado === 'Activo'): ?>
            <button class="bca-btn bca-btn-fin"
                    onclick="finalizarAsignacion(<?= (int)$a->pasante_id ?>, '<?= addslashes($nombre) ?>')"
                    title="Finalizar pasantía">
                <i class="ti ti-flag"></i> Finalizar
            </button>
            <?php endif; ?>
        </div>

    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    </div>

</div><!-- /dashboard-container -->

<!-- COMPONENTE: MODAL DE ASIGNACIONES -->
<?php require APPROOT . '/views/inc/modal_asignacion.php'; ?>

<script>
// ── DataTables & Filtros ──────────────────────────────────
// Suprimir popups intrusivos alert() de DataTables (TN/18 y derivados)
if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable && $.fn.dataTable.ext) {
    $.fn.dataTable.ext.errMode = 'none';
}

$(document).ready(function() {
    var $tablaAsignaciones = $('#tablaAsignaciones');
    var dt = null;

    // SGP-FIX: Solo iniciar DataTables si el elemento existe en el DOM y tiene filas válidas
    if ($tablaAsignaciones.length && $tablaAsignaciones.find('tbody tr.fila-asignacion').length > 0) {
        if (!$.fn.DataTable.isDataTable($tablaAsignaciones)) {
            dt = $tablaAsignaciones.DataTable({
                language: {
                    url: '<?= URLROOT ?>/assets/libs/datatables/es-ES.json'
                },
                pageLength: 10,
                responsive: true,
                dom: '<"top"f>rt<"bottom"ip><"clear">',
                buttons: [
                    {
                        extend: 'excel',
                        className: 'btn btn-light btn-sm border rounded-3 me-2',
                        text: '<i class="ti ti-file-spreadsheet text-success me-1"></i> Excel'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-light btn-sm border rounded-3',
                        text: '<i class="ti ti-file-type-pdf text-danger me-1"></i> PDF'
                    }
                ],
                columnDefs: [
                    { orderable: false, targets: 7 } // Desactivar ordenamiento de Acciones
                ],
                initComplete: function(settings, json) {
                    $(this.api().table().node()).css('opacity', '1');
                }
            });
        } else {
            dt = $tablaAsignaciones.DataTable();
        }
    }

    // Conectar inputs de búsqueda visuales (Premium SGP) al core de DataTables
    $('#searchAsignaciones').on('input', function() {
        if (dt) dt.search(this.value).draw();
    });

    $('#filterEstado').on('change', function() {
        if (!dt) return;
        var term = this.value;
        dt.column(6).search(term ? '^' + term + '$' : '', true, false).draw();
    });

    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // SGP-FIX: recalcular columnas al pasar de móvil a desktop con verificación estricta de DataTable
    var _dtAsigAdjusted = false;
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992 && !_dtAsigAdjusted) {
            if ($('#tablaAsignaciones').length && $.fn.DataTable.isDataTable('#tablaAsignaciones')) {
                var _dt = $('#tablaAsignaciones').DataTable();
                if (_dt) { _dt.columns.adjust().draw(false); }
            }
            _dtAsigAdjusted = true;
        }
        if (window.innerWidth < 992) { _dtAsigAdjusted = false; }
    });
});

// ── Filtros interactivos de KPIs ───────────────────────────────────
window.filtrarPorEstado = function(estado) {
    if ($('#tablaAsignaciones').length && $.fn.DataTable.isDataTable('#tablaAsignaciones')) {
        var dt = $('#tablaAsignaciones').DataTable();
        if (estado === '') {
            dt.column(6).search('').draw();
        } else {
            dt.column(6).search('^' + estado + '$', true, false).draw();
        }
    }
    
    // Sincronizar Vento Pills Visualmente
    const pills = document.querySelectorAll('#ventoFilterPillsAsignaciones .vento-pill');
    if (pills.length > 0) {
        pills.forEach(p => {
            p.style.background = '#f1f5f9';
            p.style.color = '#475569';
            p.style.boxShadow = 'none';
            p.classList.remove('active');
            
            const isMatch = (estado === '' && p.innerText.includes('Todos')) || 
                            (estado === 'Activo' && p.innerText.includes('Activos')) ||
                            (estado === 'Pendiente' && p.innerText.includes('Pendientes')) ||
                            (estado === 'Sin Asignar' && p.innerText.includes('Sin Asignar')) ||
                            (estado === 'Finalizado' && p.innerText.includes('Finalizados'));
            if(isMatch) {
                p.classList.add('active');
                p.style.background = '#2563eb';
                p.style.color = 'white';
                p.style.boxShadow = '0 4px 12px rgba(37,99,235,0.25)';
            }
        });
    }

    // Filtrado silencioso — sin notificaciones Toast (Silencio Operativo SGP)
}
</script>

<!-- ═══════════ MODAL CALCULADORA DE HORAS — PREMIUM ═══════════ -->
<style>
@keyframes hc-fadeIn { from{opacity:0;transform:scale(.96) translateY(12px)} to{opacity:1;transform:scale(1) translateY(0)} }
@keyframes hc-slideUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes hc-pulse { 0%,100%{opacity:1}50%{opacity:.6} }
@keyframes hc-spin { to{transform:rotate(360deg)} }
@keyframes hc-gradient { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }

.hc-overlay { display:none;position:fixed;inset:0;background:rgba(2,6,23,.7);backdrop-filter:blur(8px);z-index:1060;align-items:center;justify-content:center; }
.hc-box { background:#fff;border-radius:24px;width:100%;max-width:700px;margin:16px;box-shadow:0 32px 100px rgba(0,0,0,.35),0 0 0 1px rgba(255,255,255,.1);overflow:hidden;max-height:92vh;overflow-y:auto;animation:hc-fadeIn .3s ease; }

/* Header */
.hc-head { background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 40%,#3b82f6 100%);padding:28px 32px;position:relative;overflow:hidden; }
.hc-head::before { content:'';position:absolute;inset:0;background-image:radial-gradient(circle at 20% 50%,rgba(99,102,241,.2) 0%,transparent 50%),radial-gradient(circle at 80% 20%,rgba(16,185,129,.12) 0%,transparent 40%); }
.hc-head::after { content:'';position:absolute;top:-60px;right:-60px;width:250px;height:250px;border-radius:50%;border:1px solid rgba(255,255,255,.06);box-shadow:0 0 0 50px rgba(255,255,255,.02),0 0 0 100px rgba(255,255,255,.01); }
.hc-head-inner { position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between; }
.hc-head-left { display:flex;align-items:center;gap:16px; }
.hc-head-icon { width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,rgba(255,255,255,.2),rgba(255,255,255,.08));border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(0,0,0,.15);backdrop-filter:blur(10px); }
.hc-head-tag { display:inline-flex;align-items:center;gap:4px;background:rgba(16,185,129,.2);border:1px solid rgba(16,185,129,.35);color:#6ee7b7;font-size:.68rem;font-weight:700;letter-spacing:.03em;padding:3px 10px;border-radius:20px;margin-bottom:6px; }
.hc-head-title { color:#fff;font-size:1.5rem;font-weight:900;letter-spacing:-.02em;line-height:1.1; }
.hc-head-sub { color:rgba(255,255,255,.55);font-size:.8rem;margin-top:3px; }
.hc-close { width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);cursor:pointer;color:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:all .15s; }
.hc-close:hover { background:rgba(255,255,255,.2);color:#fff; }

/* Body */
.hc-body { padding:28px 32px; }

/* Input cards */
.hc-input-row { display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px; }
.hc-input-card { background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:16px 18px;transition:all .2s; }
.hc-input-card:focus-within { border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);background:#fff; }
.hc-input-label { display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px; }
.hc-input-label i { color:#3b82f6;font-size:.9rem; }
.hc-input { width:100%;padding:0;border:none;background:transparent;font-size:1.4rem;font-weight:800;color:#0f172a;outline:none;font-family:inherit;box-sizing:border-box; }
.hc-input::placeholder { color:#cbd5e1;font-weight:600; }

/* Presets */
.hc-presets { display:flex;align-items:center;gap:6px;margin-bottom:24px;flex-wrap:wrap; }
.hc-preset { padding:7px 16px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .18s;display:flex;align-items:center;gap:5px; }
.hc-preset:hover { border-color:#93c5fd;background:#eff6ff;color:#2563eb; }
.hc-preset.active { background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border-color:#2563eb;box-shadow:0 4px 12px rgba(37,99,235,.3); }
.hc-preset i { font-size:.85rem; }

/* Divider */
.hc-divider { height:1px;background:linear-gradient(90deg,transparent,#e2e8f0,transparent);margin:4px 0 24px; }

/* Result hero */
.hc-result { animation:hc-slideUp .4s ease; }
.hc-hero { background:linear-gradient(135deg,#0f172a,#1e3a8a);border-radius:18px;padding:24px 28px;margin-bottom:20px;position:relative;overflow:hidden; }
.hc-hero::before { content:'';position:absolute;inset:0;background-image:radial-gradient(circle at 30% 40%,rgba(59,130,246,.2) 0%,transparent 50%),radial-gradient(circle at 80% 70%,rgba(16,185,129,.12) 0%,transparent 40%); }
.hc-hero-inner { position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px; }
.hc-hero-left { display:flex;align-items:center;gap:16px; }
.hc-hero-icon { width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center; }
.hc-hero-date { font-size:1.8rem;font-weight:900;color:#fff;line-height:1.1; }
.hc-hero-label { font-size:.7rem;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.06em; }
.hc-hero-dur { text-align:right; }
.hc-hero-dur-val { font-size:1.15rem;font-weight:800;color:#93c5fd; }
.hc-hero-dur-label { font-size:.7rem;color:rgba(255,255,255,.45);margin-top:2px; }

/* Donut + KPIs */
.hc-stats { display:grid;grid-template-columns:140px 1fr;gap:20px;margin-bottom:20px;align-items:center; }
.hc-donut-wrap { display:flex;align-items:center;justify-content:center; }
.hc-kpi-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
.hc-kpi { border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:12px;transition:transform .15s; }
.hc-kpi:hover { transform:translateY(-2px); }
.hc-kpi-icon { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0; }
.hc-kpi-val { font-size:1.4rem;font-weight:900;line-height:1; }
.hc-kpi-label { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-top:2px; }

/* Feriados accordion */
.hc-fer-wrap { margin-bottom:20px;border:1px solid #fde68a;border-radius:14px;overflow:hidden; }
.hc-fer-head { display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#fffbeb;cursor:pointer;user-select:none;transition:background .15s; }
.hc-fer-head:hover { background:#fef3c7; }
.hc-fer-body { max-height:180px;overflow-y:auto;padding:0 16px 12px; }
.hc-fer-item { display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #fef3c7; }
.hc-fer-item:last-child { border-bottom:none; }

/* Timeline */
.hc-tl-wrap { background:#f8fafc;border-radius:14px;padding:18px 20px;border:1px solid #e2e8f0; }
.hc-tl-title { font-size:.78rem;font-weight:700;color:#1e293b;margin-bottom:14px;display:flex;align-items:center;gap:6px; }
.hc-tl-row { display:flex;align-items:center;gap:12px;margin-bottom:8px; }
.hc-tl-row:last-child { margin-bottom:0; }
.hc-tl-month { min-width:52px;font-size:.75rem;font-weight:700;color:#475569;text-align:right; }
.hc-tl-bar-wrap { flex:1;height:28px;background:#e2e8f0;border-radius:8px;overflow:hidden;position:relative; }
.hc-tl-bar { height:100%;border-radius:8px;background:linear-gradient(90deg,#3b82f6,#6366f1);transition:width .6s ease;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;min-width:30px; }
.hc-tl-bar-text { font-size:.68rem;font-weight:800;color:#fff;white-space:nowrap; }
.hc-tl-info { min-width:70px;font-size:.72rem;font-weight:600;color:#64748b;text-align:right; }

/* Loading */
.hc-loading { text-align:center;padding:40px 20px;color:#94a3b8; }
.hc-loading i { font-size:2.2rem;display:block;margin-bottom:10px;animation:hc-spin .8s linear infinite; }

@media(max-width:640px){
    .hc-input-row { grid-template-columns:1fr; }
    .hc-stats { grid-template-columns:1fr; }
    .hc-kpi-grid { grid-template-columns:1fr 1fr; }
    .hc-hero-inner { flex-direction:column;text-align:center; }
    .hc-hero-left { flex-direction:column; }
    .hc-hero-dur { text-align:center; }
}
@media(max-width:400px){ .hc-kpi-grid { grid-template-columns:1fr; } }
</style>

<div class="hc-overlay" id="modalCalcHoras" onclick="if(event.target===this)this.style.display='none'">
<div class="hc-box">
    <div class="hc-head">
        <div class="hc-head-inner">
            <div class="hc-head-left">
                <div class="hc-head-icon"><i class="ti ti-calculator" style="font-size:1.6rem;color:#fff;"></i></div>
                <div>
                    <div class="hc-head-tag"><i class="ti ti-sparkles" style="font-size:.75rem;"></i> Herramienta Rápida</div>
                    <div class="hc-head-title">Calculadora de Horas</div>
                    <div class="hc-head-sub">Estimación inteligente de fechas y distribución</div>
                </div>
            </div>
            <button class="hc-close" onclick="document.getElementById('modalCalcHoras').style.display='none'"><i class="ti ti-x"></i></button>
        </div>
    </div>

    <div class="hc-body">
        <!-- Inputs -->
        <div class="hc-input-row">
            <div class="hc-input-card">
                <div class="hc-input-label"><i class="ti ti-calendar-event"></i> Fecha de Inicio</div>
                <input type="date" class="hc-input" id="calcFechaInicio" value="<?= date('Y-m-d') ?>" onchange="calcularHoras()">
            </div>
            <div class="hc-input-card">
                <div class="hc-input-label"><i class="ti ti-hourglass"></i> Horas Meta</div>
                <input type="number" class="hc-input" id="calcHorasMeta" value="1440" min="8" step="8" onchange="calcularHoras()" oninput="calcularHoras()" placeholder="1440">
            </div>
        </div>

        <!-- Presets -->
        <div class="hc-presets">
            <button class="hc-preset" onclick="setPreset(this,240)"><i class="ti ti-bolt"></i> 240h</button>
            <button class="hc-preset" onclick="setPreset(this,480)"><i class="ti ti-clock-2"></i> 480h</button>
            <button class="hc-preset" onclick="setPreset(this,960)"><i class="ti ti-clock-hour-6"></i> 960h</button>
            <button class="hc-preset active" onclick="setPreset(this,1440)"><i class="ti ti-clock-hour-9"></i> 1440h</button>
            <button class="hc-preset" onclick="setPreset(this,1920)"><i class="ti ti-clock-hour-12"></i> 1920h</button>
        </div>

        <div class="hc-divider"></div>

        <!-- Loading -->
        <div class="hc-loading" id="calcLoading" style="display:none;"><i class="ti ti-loader-2"></i><div style="font-size:.88rem;font-weight:600;">Calculando distribución...</div></div>

        <!-- Resultado -->
        <div id="calcResultado" style="display:none;" class="hc-result">
            <!-- Hero fecha fin -->
            <div class="hc-hero">
                <div class="hc-hero-inner">
                    <div class="hc-hero-left">
                        <div class="hc-hero-icon"><i class="ti ti-calendar-check" style="font-size:1.6rem;color:#60a5fa;"></i></div>
                        <div>
                            <div class="hc-hero-label">Fecha Fin Estimada</div>
                            <div class="hc-hero-date" id="calcFechaFin"></div>
                        </div>
                    </div>
                    <div class="hc-hero-dur">
                        <div class="hc-hero-dur-val" id="calcDuracion"></div>
                        <div class="hc-hero-dur-label">duración total</div>
                    </div>
                </div>
            </div>

            <!-- Donut + KPIs -->
            <div class="hc-stats">
                <div class="hc-donut-wrap">
                    <svg id="calcDonut" width="130" height="130" viewBox="0 0 130 130"></svg>
                </div>
                <div class="hc-kpi-grid">
                    <div class="hc-kpi" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div class="hc-kpi-icon" style="background:#dcfce7;color:#15803d;"><i class="ti ti-briefcase"></i></div>
                        <div><div class="hc-kpi-val" style="color:#15803d;" id="calcDiasHabiles"></div><div class="hc-kpi-label" style="color:#15803d;">Días Hábiles</div></div>
                    </div>
                    <div class="hc-kpi" style="background:#f1f5f9;border:1px solid #e2e8f0;">
                        <div class="hc-kpi-icon" style="background:#e2e8f0;color:#475569;"><i class="ti ti-sun-off"></i></div>
                        <div><div class="hc-kpi-val" style="color:#475569;" id="calcFinSemana"></div><div class="hc-kpi-label" style="color:#64748b;">Fines de Semana</div></div>
                    </div>
                    <div class="hc-kpi" style="background:#fffbeb;border:1px solid #fde68a;">
                        <div class="hc-kpi-icon" style="background:#fef3c7;color:#b45309;"><i class="ti ti-calendar-off"></i></div>
                        <div><div class="hc-kpi-val" style="color:#b45309;" id="calcFeriados"></div><div class="hc-kpi-label" style="color:#b45309;">Feriados</div></div>
                    </div>
                    <div class="hc-kpi" style="background:#eff6ff;border:1px solid #bfdbfe;">
                        <div class="hc-kpi-icon" style="background:#dbeafe;color:#2563eb;"><i class="ti ti-calendar-stats"></i></div>
                        <div><div class="hc-kpi-val" style="color:#2563eb;" id="calcTotalDias"></div><div class="hc-kpi-label" style="color:#2563eb;">Días Totales</div></div>
                    </div>
                </div>
            </div>

            <!-- Feriados accordion -->
            <div id="calcListaFeriados" style="display:none;" class="hc-fer-wrap">
                <div class="hc-fer-head" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none';this.querySelector('.hc-fer-chevron').style.transform=this.nextElementSibling.style.display==='none'?'rotate(-90deg)':''">
                    <span style="font-size:.82rem;font-weight:700;color:#92400e;display:flex;align-items:center;gap:6px;"><i class="ti ti-calendar-off"></i> Feriados en el período <span id="calcFeriadosBadge" style="background:#fde68a;color:#92400e;padding:1px 8px;border-radius:12px;font-size:.7rem;"></span></span>
                    <i class="ti ti-chevron-down hc-fer-chevron" style="color:#b45309;transition:transform .2s;"></i>
                </div>
                <div class="hc-fer-body" id="calcFeriadosLista"></div>
            </div>

            <!-- Timeline -->
            <div class="hc-tl-wrap">
                <div class="hc-tl-title"><i class="ti ti-chart-bar" style="color:#3b82f6;"></i> Distribución Mensual de Horas</div>
                <div id="calcTimeline"></div>
            </div>
        </div>

        <!-- Estado vacío -->
        <div id="calcEmpty" style="text-align:center;padding:40px 20px;">
            <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#eff6ff,#dbeafe);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><i class="ti ti-calculator" style="font-size:2rem;color:#3b82f6;"></i></div>
            <div style="font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:4px;">Configura los parámetros</div>
            <div style="font-size:.85rem;color:#94a3b8;">Selecciona una fecha y las horas meta para calcular</div>
        </div>
    </div>
</div>
</div>

<script>
(function(){
    let feriadosCache = null;
    const MESES_FULL = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const DIAS  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

    window.setPreset = function(btn, val) {
        document.querySelectorAll('.hc-preset').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('calcHorasMeta').value = val;
        calcularHoras();
    };

    async function cargarFeriados() {
        if (feriadosCache) return feriadosCache;
        try { const r = await fetch(`${URLROOT}/asignaciones/feriadosJson`); feriadosCache = await r.json(); }
        catch(e) { feriadosCache = []; }
        return feriadosCache;
    }

    function drawDonut(habiles, finde, feriados) {
        const svg = document.getElementById('calcDonut');
        const total = habiles + finde + feriados || 1;
        const r = 50, cx = 65, cy = 65, sw = 16;
        const circum = 2 * Math.PI * r;
        const segments = [
            { val: habiles, color: '#22c55e', label: 'Hábiles' },
            { val: finde,   color: '#94a3b8', label: 'Fines sem.' },
            { val: feriados, color: '#f59e0b', label: 'Feriados' },
        ];
        let offset = 0;
        let html = '';
        segments.forEach(s => {
            if (s.val <= 0) return;
            const pct = s.val / total;
            const dash = pct * circum;
            const gap = circum - dash;
            html += `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${s.color}" stroke-width="${sw}" stroke-dasharray="${dash} ${gap}" stroke-dashoffset="${-offset}" transform="rotate(-90 ${cx} ${cy})" style="transition:stroke-dasharray .6s,stroke-dashoffset .6s;"/>`;
            offset += dash;
        });
        const pctH = Math.round(habiles / total * 100);
        html += `<text x="${cx}" y="${cy-4}" text-anchor="middle" style="font-size:18px;font-weight:900;fill:#0f172a;">${pctH}%</text>`;
        html += `<text x="${cx}" y="${cy+12}" text-anchor="middle" style="font-size:8px;font-weight:600;fill:#94a3b8;">HÁBILES</text>`;
        svg.innerHTML = html;
    }

    window.calcularHoras = async function() {
        const inicio = document.getElementById('calcFechaInicio').value;
        const horas  = parseInt(document.getElementById('calcHorasMeta').value) || 0;
        if (!inicio || horas < 8) { document.getElementById('calcEmpty').style.display='block'; document.getElementById('calcResultado').style.display='none'; return; }

        document.getElementById('calcEmpty').style.display = 'none';
        document.getElementById('calcResultado').style.display = 'none';
        document.getElementById('calcLoading').style.display = 'block';

        const feriados = await cargarFeriados();
        const feriadoMap = {};
        feriados.forEach(f => { feriadoMap[f.fecha] = f.nombre; });

        const diasNecesarios = Math.ceil(horas / 8);
        const dInicio = new Date(inicio + 'T00:00:00');
        let diasHabiles = 0, finSemana = 0, feriadosEnRango = [], totalDias = 0, mesMapa = {};
        let cur = new Date(dInicio), fechaFin = null;

        while (diasHabiles < diasNecesarios) {
            const dow = cur.getDay();
            const fStr = cur.toISOString().slice(0, 10);
            const mes = cur.getFullYear() + '-' + String(cur.getMonth()+1).padStart(2,'0');
            if (!mesMapa[mes]) mesMapa[mes] = { habiles:0, finde:0, feriados:0 };
            if (dow === 0 || dow === 6) { finSemana++; mesMapa[mes].finde++; }
            else { diasHabiles++; mesMapa[mes].habiles++; if(feriadoMap[fStr]){ feriadosEnRango.push({fecha:fStr,nombre:feriadoMap[fStr]}); mesMapa[mes].feriados++; } }
            totalDias++; fechaFin = new Date(cur); cur.setDate(cur.getDate()+1);
        }

        const [fy,fm,fd] = fechaFin.toISOString().slice(0,10).split('-');
        document.getElementById('calcFechaFin').textContent = `${fd} de ${MESES_FULL[parseInt(fm)-1]}, ${fy}`;
        document.getElementById('calcDuracion').textContent = `${totalDias} días · ~${Math.round(totalDias/30.44)} meses`;

        document.getElementById('calcDiasHabiles').textContent = diasHabiles;
        document.getElementById('calcFinSemana').textContent = finSemana;
        document.getElementById('calcFeriados').textContent = feriadosEnRango.length;
        document.getElementById('calcTotalDias').textContent = totalDias;

        drawDonut(diasHabiles, finSemana, feriadosEnRango.length);

        const listaEl = document.getElementById('calcFeriadosLista');
        const listaWrap = document.getElementById('calcListaFeriados');
        if (feriadosEnRango.length > 0) {
            document.getElementById('calcFeriadosBadge').textContent = feriadosEnRango.length;
            const esc = s => { const t=document.createElement('span'); t.textContent=s; return t.innerHTML; };
            listaEl.innerHTML = feriadosEnRango.map(f => {
                const d = new Date(f.fecha+'T00:00:00');
                return `<div class="hc-fer-item"><i class="ti ti-flag-filled" style="color:#f59e0b;font-size:.85rem;flex-shrink:0;"></i><span style="font-size:.78rem;font-weight:700;color:#1e293b;min-width:90px;">${DIAS[d.getDay()]} ${esc(f.fecha)}</span><span style="font-size:.78rem;color:#92400e;flex:1;">${esc(f.nombre)}</span></div>`;
            }).join('');
            listaWrap.style.display = 'block';
        } else { listaWrap.style.display = 'none'; }

        const timelineEl = document.getElementById('calcTimeline');
        const mKeys = Object.keys(mesMapa).sort();
        const maxH = Math.max(...mKeys.map(m => mesMapa[m].habiles), 1);
        timelineEl.innerHTML = mKeys.map(m => {
            const d = mesMapa[m];
            const [my,mm] = m.split('-');
            const pct = Math.round(d.habiles/maxH*100);
            const h = d.habiles * 8;
            return `<div class="hc-tl-row"><span class="hc-tl-month">${MESES[parseInt(mm)-1]} ${my.slice(2)}</span><div class="hc-tl-bar-wrap"><div class="hc-tl-bar" style="width:${pct}%;"><span class="hc-tl-bar-text">${d.habiles}d</span></div></div><span class="hc-tl-info">${h}h${d.feriados?' · <span style=color:#f59e0b>'+d.feriados+'<svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b" style="vertical-align:middle;margin-left:2px;"><path d="M4 5a1 1 0 0 1 .3-.714 8 8 0 0 1 3.7-.786c1.929 0 3.603.57 5.03 1.149A16 16 0 0 0 16 5.97c.34.06.6.2.77.394.17.195.23.439.23.636v8a1 1 0 0 1-.3.714 8 8 0 0 1-3.7.786c-1.929 0-3.603-.57-5.03-1.149A16 16 0 0 0 5 14.03V21a1 1 0 1 1-2 0V5z"/></svg></span>':''}</span></div>`;
        }).join('');

        document.getElementById('calcLoading').style.display = 'none';
        document.getElementById('calcResultado').style.display = 'block';
    };

    document.addEventListener('DOMContentLoaded', () => { document.getElementById('calcLoading').style.display='none'; });
})();
</script>
