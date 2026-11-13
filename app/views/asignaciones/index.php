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
/* ===== ASIG-KPI-CARD (Idéntico a Exámenes Rápidos) ===== */
.asig-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 1100px) {
    .asig-stats-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 16px !important; }
}
@media (max-width: 768px) {
    .asig-stats-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; margin-bottom: 20px !important; }
}
@media (max-width: 480px) {
    .asig-stats-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 10px !important; }
}

.asig-kpi-card {
    background: #fff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 2px 14px rgba(0,0,0,.07);
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
    overflow: hidden;
    transition: transform .25s ease, box-shadow .25s ease;
    cursor: pointer;
}
.asig-kpi-card:hover { transform: translateY(-4px); }
.asig-kpi-card::after {
    content: '';
    position: absolute;
    bottom: -20px;
    right: -20px;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    opacity: .07;
}

.asig-kpi-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.asig-kpi-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
.asig-kpi-val { font-size: 2.5rem; font-weight: 900; line-height: 1; margin-top: 2px; }
.asig-kpi-sub { font-size: .74rem; color: #94a3b8; font-weight: 500; }
.asig-kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }

.asig-kpi-card.c-blue   { border-top: 3px solid #2563eb; } .asig-kpi-card.c-blue::after   { background: #2563eb; }
.asig-kpi-card.c-green  { border-top: 3px solid #059669; } .asig-kpi-card.c-green::after  { background: #059669; }
.asig-kpi-card.c-amber  { border-top: 3px solid #d97706; } .asig-kpi-card.c-amber::after  { background: #d97706; }
.asig-kpi-card.c-purple { border-top: 3px solid #7c3aed; } .asig-kpi-card.c-purple::after { background: #7c3aed; }

.asig-kpi-card:hover.c-blue   { box-shadow: 0 12px 22px -6px rgba(37, 99, 235, .55) !important; }
.asig-kpi-card:hover.c-green  { box-shadow: 0 12px 22px -6px rgba(5, 150, 105, .55) !important; }
.asig-kpi-card:hover.c-amber  { box-shadow: 0 12px 22px -6px rgba(217, 119, 6, .55) !important; }
.asig-kpi-card:hover.c-purple { box-shadow: 0 12px 22px -6px rgba(124, 58, 237, .55) !important; }

/* Responsive móvil */
@media (max-width: 768px) {
    .asig-kpi-card { padding: 16px 14px !important; border-radius: 16px !important; }
    .asig-kpi-val  { font-size: 1.8rem !important; }
    .asig-kpi-icon { width: 38px !important; height: 38px !important; font-size: 1.15rem !important; }
}
@media (max-width: 480px) {
    .asig-kpi-card { padding: 14px 12px !important; }
    .asig-kpi-val  { font-size: 1.6rem !important; }
    .asig-kpi-label { font-size: 0.68rem !important; }
    .asig-kpi-sub  { font-size: 0.68rem !important; }
}

/* Modo oscuro */
[data-theme="dark"] .asig-kpi-card {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
}
[data-theme="dark"] .asig-kpi-card.c-blue   { border-top: 3px solid #3b82f6 !important; }
[data-theme="dark"] .asig-kpi-card.c-green  { border-top: 3px solid #10b981 !important; }
[data-theme="dark"] .asig-kpi-card.c-amber  { border-top: 3px solid #f59e0b !important; }
[data-theme="dark"] .asig-kpi-card.c-purple { border-top: 3px solid #a855f7 !important; }

[data-theme="dark"] .asig-kpi-card:hover.c-blue   { box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 25px rgba(59, 130, 246, 0.5) !important; border-top-color: #60a5fa !important; }
[data-theme="dark"] .asig-kpi-card:hover.c-green  { box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 25px rgba(16, 185, 129, 0.5) !important; border-top-color: #34d399 !important; }
[data-theme="dark"] .asig-kpi-card:hover.c-amber  { box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 25px rgba(245, 158, 11, 0.5) !important; border-top-color: #fbbf24 !important; }
[data-theme="dark"] .asig-kpi-card:hover.c-purple { box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 25px rgba(168, 85, 247, 0.5) !important; border-top-color: #c084fc !important; }

[data-theme="dark"] .asig-kpi-val { color: #f8fafc !important; }
[data-theme="dark"] .asig-kpi-label,
[data-theme="dark"] .asig-kpi-sub { color: #94a3b8 !important; }
@media(max-width:1100px){
    .asig-banner { flex-direction: column !important; align-items: flex-start !important; padding: 22px 20px !important; gap: 16px !important; border-radius: 18px !important; }
    .asig-banner > div:first-of-type { width: 100% !important; }
    .asig-banner-actions { width: 100% !important; flex-wrap: wrap !important; gap: 10px !important; }
    .asig-banner-actions button { flex: 1 1 150px !important; justify-content: center !important; font-size: 0.85rem !important; padding: 11px 16px !important; }
}
@media(max-width:480px){
    .asig-banner { padding: 16px 14px !important; border-radius: 16px !important; margin-bottom: 20px !important; }
    .asig-banner-actions { flex-direction: column !important; gap: 8px !important; }
    .asig-banner-actions button { width: 100% !important; flex: 1 1 100% !important; }
}

/* ===== FILTRO Y BUSCADOR RESPONSIVE ===== */
.asig-filter-bar {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: #ffffff;
    padding: 16px 24px;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.asig-filter-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.asig-filter-pills-track {
    display: flex;
    gap: 8px;
    align-items: center;
}
.asig-filter-pills-track .vento-pill {
    white-space: nowrap;
    flex-shrink: 0;
}
.asig-search-wrap {
    position: relative;
    flex-shrink: 0;
}
.asig-search-input {
    padding: 10px 16px 10px 42px;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.9rem;
    color: #1e293b;
    outline: none;
    width: 280px;
    transition: all 0.2s;
    background: #f8fafc;
}

[data-theme="dark"] .asig-filter-bar {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
}
[data-theme="dark"] .asig-search-input {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}

@media (max-width: 768px) {
    .asig-filter-bar {
        flex-direction: column;
        align-items: stretch;
        padding: 14px;
        gap: 12px;
    }
    .asig-filter-left {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .asig-filter-label {
        display: none !important;
    }
    .asig-filter-pills-track {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding: 4px 2px 8px;
        scrollbar-width: none;
        width: 100%;
    }
    .asig-filter-pills-track::-webkit-scrollbar {
        display: none;
    }
    .asig-search-wrap {
        width: 100%;
    }
    .asig-search-input {
        width: 100% !important;
        box-sizing: border-box;
    }
}
</style>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">

    <!-- ===== BANNER ===== -->
    <div class="asig-banner" style="background: linear-gradient(135deg, var(--color-primary, #172554) 0%, var(--gradient-mid, #1e3a8a) 50%, var(--color-accent-brand, #2563eb) 100%); border-radius: 20px; padding: 32px 40px; margin-bottom: 28px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between;">
        <div style="position: absolute; top: -40px; right: -40px; width: 220px; height: 220px; background: rgba(255,255,255,0.04); border-radius: 50%;"></div>
        <div style="position: absolute; bottom: -60px; left: 30%; width: 160px; height: 160px; background: rgba(255,255,255,0.03); border-radius: 50%;"></div>
        <div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 54px; height: 54px; min-width: 54px; background: rgba(255,255,255,0.16); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.25); border-radius: 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                    <i class="ti ti-link" style="font-size: 28px; color: white;"></i>
                </div>
                <div>
                    <h1 style="color: white; font-size: 1.8rem; font-weight: 700; margin: 0; line-height: 1.2;">Gestión de Asignaciones</h1>
                    <p style="color: rgba(255,255,255,0.7); margin: 4px 0 0; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <i class="ti ti-arrows-exchange"></i>
                        <span>Asignación de Pasantes a Tutores y Departamentos</span>
                        <span style="background: rgba(255,255,255,0.2); color: white; padding: 2px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                            <?= $total ?> registros
                        </span>
                    </p>
                </div>
            </div>
        </div>
        <div class="asig-banner-actions" style="display: flex; gap: 12px; z-index: 1;">
            <button onclick="abrirCalculadoraHoras()"
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

    <!-- ===== KPI CARDS (Idéntico a Exámenes Rápidos) ===== -->
    <div class="asig-stats-grid">
        <?php foreach ([
            ['label' => 'Total Pasantes', 'value' => $total,       'sub' => 'registrados',       'color' => '#2563eb', 'bg' => '#eff6ff', 'icon' => 'ti-users',            'filter' => '',            'type' => 'blue'],
            ['label' => 'Activos',        'value' => $activos,     'sub' => 'en pasantía',       'color' => '#059669', 'bg' => '#f0fdf4', 'icon' => 'ti-circle-check',     'filter' => 'Activo',      'type' => 'green'],
            ['label' => 'Sin Asignar',    'value' => $pendientes,  'sub' => 'sin tutor/depto.',  'color' => '#d97706', 'bg' => '#fef3c7', 'icon' => 'ti-user-exclamation', 'filter' => 'Sin Asignar', 'type' => 'amber'],
            ['label' => 'Finalizados',    'value' => $finalizados, 'sub' => 'completados',       'color' => '#7c3aed', 'bg' => '#f5f3ff', 'icon' => 'ti-award',            'filter' => 'Finalizado',  'type' => 'purple'],
        ] as $s): ?>
        <div class="asig-kpi-card c-<?= $s['type'] ?>"
             onclick="filtrarPorEstado('<?= $s['filter'] ?>')">
            <div class="asig-kpi-top">
                <div>
                    <div class="asig-kpi-label"><?= $s['label'] ?></div>
                    <div class="asig-kpi-val" style="color: <?= $s['color'] ?>;"><?= $s['value'] ?></div>
                    <div class="asig-kpi-sub"><?= $s['sub'] ?></div>
                </div>
                <div class="asig-kpi-icon" style="background: <?= $s['bg'] ?>; color: <?= $s['color'] ?>;">
                    <i class="ti <?= $s['icon'] ?>"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros Rápidos y Buscador -->
    <div class="asig-filter-bar">
        
        <div class="asig-filter-left">
            <p class="asig-filter-label" style="font-size: 0.9rem; font-weight: 700; color: #64748b; margin: 0; display:flex; align-items:center; gap:6px; flex-shrink: 0;">
                <i class="ti ti-filter" style="font-size:1.1rem; color:#2563eb;"></i> Filtrar:
            </p>
            <div class="asig-filter-pills-track" id="ventoFilterPillsAsignaciones">
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

        <div class="asig-search-wrap">
            <i class="ti ti-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; pointer-events: none;"></i>
            <input type="text" id="searchAsignaciones" class="asig-search-input" placeholder="Buscar por Cédula o Nombre..."
                onfocus="this.style.borderColor='#2563eb';this.style.background='white';this.style.boxShadow='0 0 0 4px rgba(37,99,235,0.1)'"
                onblur="this.style.borderColor='#e5e7eb';this.style.background='#f8fafc';this.style.boxShadow='none'">
        </div>
    </div>

    <!-- ===== TABLA DE ASIGNACIONES (Boxy SaaS) ===== -->
    <?php if (empty($asignaciones)): ?>
    <!-- EMPTY STATE INTELIGENTE (Cero errores DataTables TN/18) -->
    <div class="sgp-solo-desktop asig-empty-state" style="border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 56px 30px; text-align: center; border: 0;">
        <div class="asig-empty-icon-wrap" style="width: 76px; height: 76px; border-radius: 22px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 8px 24px rgba(37,99,235,0.12);">
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
        <div class="table-responsive" style="overflow-x: hidden;">
            <table id="tablaAsignaciones" class="table table-hover align-middle mb-0" style="width: 100% !important; table-layout: fixed;">
                <colgroup>
                    <col style="width: 23%;">
                    <col style="width: 16%;">
                    <col style="width: 14%;">
                    <col style="width: 12%;">
                    <col style="width: 12%;">
                    <col style="width: 10%;">
                    <col style="width: 13%;">
                </colgroup>
                <thead class="bg-light text-uppercase text-muted small fw-bold" style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <tr>
                        <th class="px-3 py-3 border-0">Pasante / Cédula</th>
                        <th class="px-3 py-3 border-0">Tutor Asignado</th>
                        <th class="px-3 py-3 border-0">Departamento</th>
                        <th class="px-3 py-3 border-0">Período</th>
                        <th class="px-3 py-3 border-0">Progreso</th>
                        <th class="px-3 py-3 border-0">Estado</th>
                        <th class="px-3 py-3 border-0 text-center">Acciones</th>
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
                        <td class="px-3 py-2.5">
                            <div class="dt-name-cell">
                                <div class="dt-avatar av-pasante"><?= $iniciales ?></div>
                                <div style="min-width: 0; overflow: hidden; flex: 1;">
                                    <div class="dt-cell-primary text-truncate" title="<?= $nombre ?>"><?= $nombre ?></div>
                                    <div class="dt-cell-sub">
                                        <span class="dt-cedula-badge" title="Cédula: <?= htmlspecialchars($a->cedula ?? '') ?>">
                                            <i class="ti ti-id"></i> <?= htmlspecialchars($a->cedula ?? '—') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5">
                            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">
                                    <i class="ti ti-briefcase"></i>
                                </div>
                                <div class="d-inline-block text-truncate" style="max-width: 100%; font-size: 0.84rem; font-weight: 600; color: #1e293b;" title="<?= strip_tags($tutorNom) ?>">
                                    <?= $tutorNom ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2.5" style="color: #64748b; font-size: 0.84rem;">
                            <span class="d-inline-block text-truncate" style="max-width: 100%;" title="<?= htmlspecialchars($a->departamento_nombre ?? '—') ?>">
                                <?= htmlspecialchars($a->departamento_nombre ?? '—') ?>
                            </span>
                        </td>
                        <td class="px-3 py-2.5" style="color: #64748b; font-size: 0.82rem;">
                            <?php if (!empty($a->fecha_inicio_pasantia)): ?>
                                <span style="font-weight: 600; color: #334155;"><?= date('d/m/Y', strtotime($a->fecha_inicio_pasantia)) ?></span>
                                <?php if (!empty($a->fecha_fin_estimada)): ?>
                                    <br><span style="font-size: 0.72rem; color: #94a3b8;">al <?= date('d/m/Y', strtotime($a->fecha_fin_estimada)) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2.5">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; height: 6px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                    <div style="width: <?= $progreso ?>%; height: 100%; background: <?= $pColor ?>; border-radius: 4px; transition: width 0.5s;"></div>
                                </div>
                                <span style="font-size: 0.76rem; font-weight: 700; color: <?= $pColor ?>;"><?= $progreso ?>%</span>
                            </div>
                            <span style="font-size: 0.68rem; color: #94a3b8;"><?= $hCumpl ?>/<?= $hMeta ?> hrs</span>
                        </td>
                        <td class="px-3 py-2.5" data-search="<?= htmlspecialchars($estado) ?>" data-order="<?= htmlspecialchars($estado) ?>">
                            <span style="background: <?= $cfg['bg'] ?>; color: <?= $cfg['color'] ?>; padding: 4px 8px; border-radius: 20px; font-size: 0.76rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="ti <?= $cfg['icon'] ?>" style="font-size: 0.75rem;"></i>
                                <?= htmlspecialchars($estado) ?>
                            </span>
                        </td>

                        <td class="px-3 py-2.5 text-center">
                            <div class="sgp-action-capsule">
                                <button type="button" onclick="verDetalleAsignacion(<?= (int)$a->pasante_id ?>)"
                                        class="sgp-act-btn act-blue" 
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Ver detalles de asignación">
                                    <i class="ti ti-eye"></i>
                                </button>
                                
                                <?php if (in_array($estado, ['Sin Asignar', 'Pendiente'])): 
                                    $instAsig = $a->institucion_procedencia ?: 'No registrada';
                                ?>
                                <button type="button"
                                        data-id="<?= (int)$a->pasante_id ?>"
                                        data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                                        data-cedula="<?= htmlspecialchars($a->cedula ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-institucion="<?= htmlspecialchars($instAsig, ENT_QUOTES, 'UTF-8') ?>"
                                        onclick="window.abrirAsignacionSegura(this)"
                                        class="sgp-act-btn act-amber" 
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Editar asignación">
                                    <i class="ti ti-pencil"></i>
                                </button>
                                <button type="button"
                                        data-id="<?= (int)$a->pasante_id ?>"
                                        data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                                        data-cedula="<?= htmlspecialchars($a->cedula ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        onclick="window.confirmarDescartarPasante(this)"
                                        class="sgp-act-btn act-danger-subtle" 
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Descartar solicitud">
                                    <i class="ti ti-trash"></i>
                                </button>
                                <?php endif; ?>

                                <?php if ($estado === 'Activo'): ?>
                                <button type="button" onclick="finalizarAsignacion(<?= (int)$a->pasante_id ?>, '<?= addslashes($nombre) ?>')"
                                        class="sgp-act-btn act-green" 
                                        data-bs-toggle="tooltip" data-bs-placement="top" title="Finalizar pasantía">
                                    <i class="ti ti-flag"></i>
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
        <div class="asig-empty-mobile" style="border-radius: 16px; padding: 32px 20px; text-align: center; border: 1px solid #f1f5f9; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
            <div class="asig-empty-icon-wrap" style="width: 56px; height: 56px; border-radius: 16px; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px;">
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
                $instAsig = $a->institucion_procedencia ?: 'No registrada'; ?>
            <button class="bca-btn bca-btn-edit"
                    data-id="<?= (int)$a->pasante_id ?>"
                    data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                    data-cedula="<?= htmlspecialchars($a->cedula ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-institucion="<?= htmlspecialchars($instAsig, ENT_QUOTES, 'UTF-8') ?>"
                    onclick="window.abrirAsignacionSegura(this)"
                    title="Editar asignación">
                <i class="ti ti-pencil"></i> Editar
            </button>
            <button class="bca-btn"
                    data-id="<?= (int)$a->pasante_id ?>"
                    data-nombre="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
                    data-cedula="<?= htmlspecialchars($a->cedula ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    onclick="window.confirmarDescartarPasante(this)"
                    style="color: #ef4444; border-color: #fee2e2; background: #fff;"
                    title="Descartar solicitud">
                <i class="ti ti-trash"></i> Descartar
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
                    { orderable: false, targets: -1 } // Desactivar ordenamiento de Acciones
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
        dt.column(5).search(term ? '^' + term + '$' : '', true, false).draw();
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

<?php require_once (defined('APPROOT') ? APPROOT . '/views/inc/modal_calculadora_horas.php' : __DIR__ . '/../inc/modal_calculadora_horas.php'); ?>

<!-- Modal: Nueva Asignacion -->
<?php require_once (defined('APPROOT') ? APPROOT . '/views/inc/modal_asignacion.php' : __DIR__ . '/../inc/modal_asignacion.php'); ?>
