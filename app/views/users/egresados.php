<?php
/**
 * Vista de Pasantes Egresados - Archivo Histórico
 * 
 * PROPÓSITO:
 * Muestra exclusivamente los pasantes que culminaron su período o cuyas cohortes
 * académicas ya están cerradas, manteniéndolos separados del DataTable de usuarios
 * activos cotidianos pero preservando todas las acciones de gestión, auditoría y kardex.
 */
?>

<style>
/* ── BANNER HERO SGP ── */
.eg-banner {
    background: linear-gradient(135deg, var(--color-primary, #172554) 0%, var(--gradient-mid, #1e3a8a) 50%, var(--color-accent-brand, #2563eb) 100%);
    border-radius: 20px;
    padding: 32px 40px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
    box-shadow: 0 8px 32px rgba(15, 23, 42, .25);
}
.eg-banner::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 260px;
    height: 260px;
    background: radial-gradient(circle, rgba(255, 255, 255, .12) 0%, rgba(255, 255, 255, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
}
.eg-banner-main {
    display: flex;
    align-items: center;
    gap: 18px;
    z-index: 1;
}
.eg-banner-icon {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    background: rgba(255, 255, 255, .18);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
    border: 1px solid rgba(255, 255, 255, .25);
}
.eg-banner-title {
    font-size: 1.85rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.5px;
    color: #fff;
}
.eg-banner-sub {
    font-size: 0.92rem;
    opacity: 0.88;
    margin: 5px 0 0;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.eg-banner-actions {
    display: flex;
    gap: 12px;
    z-index: 1;
    align-items: center;
    flex-wrap: wrap;
}
.eg-btn-hero {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, .18);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, .3);
    color: #fff;
    padding: 10px 18px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    text-decoration: none;
    transition: all .2s;
    white-space: nowrap;
}
.eg-btn-hero:hover {
    background: rgba(255, 255, 255, .28);
    color: #fff;
    transform: translateY(-2px);
}

/* ── KPI GRID TIPO EXÁMENES RÁPIDOS ── */
.eg-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.eg-kpi-card {
    background: #fff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 2px 14px rgba(0, 0, 0, .07);
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
    overflow: hidden;
    transition: transform .25s ease, box-shadow .25s ease;
    cursor: default;
}
.eg-kpi-card:hover { transform: translateY(-4px); }
.eg-kpi-card::after {
    content: '';
    position: absolute;
    bottom: -20px;
    right: -20px;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    opacity: .07;
    pointer-events: none;
}
.eg-kpi-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
.eg-kpi-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
.eg-kpi-val { font-size: 2.5rem; font-weight: 900; line-height: 1; margin-top: 2px; }
.eg-kpi-sub { font-size: .74rem; color: #94a3b8; font-weight: 500; }
.eg-kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }

.eg-kpi-card.c-blue   { border-top: 3px solid #2563eb; } .eg-kpi-card.c-blue::after   { background: #2563eb; }
.eg-kpi-card.c-amber  { border-top: 3px solid #d97706; } .eg-kpi-card.c-amber::after  { background: #d97706; }
.eg-kpi-card.c-green  { border-top: 3px solid #059669; } .eg-kpi-card.c-green::after  { background: #059669; }
.eg-kpi-card.c-purple { border-top: 3px solid #7c3aed; } .eg-kpi-card.c-purple::after { background: #7c3aed; }

.eg-kpi-card:hover.c-blue   { box-shadow: 0 12px 22px -6px rgba(37, 99, 235, .55) !important; }
.eg-kpi-card:hover.c-amber  { box-shadow: 0 12px 22px -6px rgba(217, 119, 6, .55) !important; }
.eg-kpi-card:hover.c-green  { box-shadow: 0 12px 22px -6px rgba(5, 150, 105, .55) !important; }
.eg-kpi-card:hover.c-purple { box-shadow: 0 12px 22px -6px rgba(124, 58, 237, .55) !important; }

/* ── TOOLBAR DE FILTROS ── */
.eg-filter-bar {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    background: white;
    padding: 16px 22px;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    flex-wrap: wrap;
}
.eg-search-wrap {
    position: relative;
    flex: 1 1 240px;
    max-width: 340px;
}
.eg-search-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
    pointer-events: none;
}
.eg-search-input {
    width: 100%;
    height: 40px;
    padding: 0 36px 0 40px;
    border: 1.5px solid #e2e8f0;
    border-radius: 50px;
    background: #f8fafc;
    color: #0d1424;
    font-size: 0.85rem;
    font-weight: 500;
    outline: none;
    transition: all .2s;
}
.eg-search-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    background: #fff;
}
.eg-select-pill {
    height: 40px;
    padding: 0 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 50px;
    background: #f8fafc;
    color: #334155;
    font-size: 0.83rem;
    font-weight: 600;
    outline: none;
    cursor: pointer;
    transition: all .2s;
}
.eg-select-pill:focus {
    border-color: #2563eb;
    background: #fff;
}

/* ── TABLA DATATABLE ── */
.eg-table-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 2px 14px rgba(0,0,0,0.04);
    border: 1px solid #e2e8f0;
}
.dt-name-cell { display: flex; align-items: center; gap: 12px; }
.dt-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.88rem;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(37,99,235,0.25);
}
.dt-cell-primary { font-weight: 700; color: #1e293b; font-size: 0.9rem; }
.dt-cell-secondary { color: #64748b; font-size: 0.76rem; font-weight: 500; }

/* ── MODAL EDICIÓN Y ESTILOS DE ACCIONES ── */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,0.7); backdrop-filter: blur(6px);
    z-index: 9999; align-items: center; justify-content: center;
    animation: fadeIn 0.2s ease;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: white; border-radius: 20px; width: 100%; max-width: 580px;
    max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 25px 60px rgba(0,0,0,0.25);
}
.modal-head {
    background: linear-gradient(135deg, #172554 0%, #1e3a8a 100%);
    padding: 24px 28px; display: flex; align-items: center; justify-content: space-between;
    color: white;
}
.modal-body-scroll { padding: 26px 28px; overflow-y: auto; flex: 1; }
.modal-close {
    background: rgba(255,255,255,0.15); border: none; color: white;
    width: 36px; height: 36px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center; cursor: pointer;
    font-size: 1.1rem; transition: background .2s;
}
.modal-close:hover { background: rgba(255,255,255,0.3); }

/* Responsive móvil */
@media (max-width: 991px) {
    .eg-banner { flex-direction: column; align-items: flex-start; padding: 22px 20px; gap: 16px; border-radius: 18px; }
    .eg-banner-actions { width: 100%; }
    .eg-banner-actions .eg-btn-hero { flex: 1 1 auto; justify-content: center; }
    .eg-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px; }
    .eg-kpi-card { padding: 16px 14px !important; border-radius: 16px !important; }
    .eg-kpi-val  { font-size: 1.8rem !important; }
    .eg-kpi-icon { width: 38px !important; height: 38px !important; font-size: 1.15rem !important; }
    .eg-filter-bar { padding: 14px 16px; }
    .eg-search-wrap { max-width: 100%; }
}
@media (max-width: 480px) {
    .eg-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 10px !important; }
    .eg-kpi-card { padding: 14px 12px !important; }
    .eg-kpi-val  { font-size: 1.6rem !important; }
    .eg-kpi-label { font-size: 0.68rem !important; }
    .eg-kpi-sub  { font-size: 0.68rem !important; }
}

/* Modo oscuro */
[data-theme="dark"] .eg-banner {
    background: linear-gradient(135deg, #090d16 0%, #0f1c3f 50%, #1e3a8a 100%) !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6) !important;
}
[data-theme="dark"] .eg-kpi-card {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3) !important;
}
[data-theme="dark"] .eg-kpi-card.c-blue   { border-top: 3px solid #3b82f6 !important; }
[data-theme="dark"] .eg-kpi-card.c-amber  { border-top: 3px solid #f59e0b !important; }
[data-theme="dark"] .eg-kpi-card.c-green  { border-top: 3px solid #10b981 !important; }
[data-theme="dark"] .eg-kpi-card.c-purple { border-top: 3px solid #a855f7 !important; }
[data-theme="dark"] .eg-kpi-card:hover.c-blue   { box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 25px rgba(59, 130, 246, 0.5) !important; }
[data-theme="dark"] .eg-kpi-card:hover.c-amber  { box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 25px rgba(245, 158, 11, 0.5) !important; }
[data-theme="dark"] .eg-kpi-card:hover.c-green  { box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 25px rgba(16, 185, 129, 0.5) !important; }
[data-theme="dark"] .eg-kpi-card:hover.c-purple { box-shadow: 0 12px 30px rgba(0,0,0,0.6), 0 0 25px rgba(168, 85, 247, 0.5) !important; }
[data-theme="dark"] .eg-kpi-val { color: #f8fafc !important; }
[data-theme="dark"] .eg-filter-bar,
[data-theme="dark"] .eg-table-card { background: #111a2e !important; border-color: #1e293b !important; }
[data-theme="dark"] .eg-search-input,
[data-theme="dark"] .eg-select-pill { background: #0b1120 !important; border-color: #1e293b !important; color: #f8fafc !important; }
</style>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">

    <!-- ===== BANNER HERO SGP ===== -->
    <div class="eg-banner">
        <div class="eg-banner-main">
            <div class="eg-banner-icon">
                <i class="ti ti-school"></i>
            </div>
            <div>
                <h1 class="eg-banner-title">Pasantes Egresados</h1>
                <p class="eg-banner-sub">
                    <i class="ti ti-archive"></i>
                    <span>Archivo Histórico de Cohortes Culminadas</span>
                    <span style="background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.25);border-radius:50px;padding:3px 12px;color:white;font-weight:700;font-size:0.78rem;">
                        <?= count($egresados) ?> registros archivados
                    </span>
                </p>
            </div>
        </div>
        <div class="eg-banner-actions">
            <a href="<?= URLROOT ?>/users" class="eg-btn-hero">
                <i class="ti ti-arrow-left"></i> Volver a Usuarios Activos
            </a>
            <a href="<?= URLROOT ?>/asistencias?vista=anual" class="eg-btn-hero">
                <i class="ti ti-chart-dots"></i> Asistencias Total
            </a>
        </div>
    </div>

    <?php
    $totalEgresados = count($egresados);
    $periodosUnicos = array_unique(array_filter(array_map(fn($e) => $e['periodo_nombre'] ?? null, $egresados)));
    $institucionesUnicas = array_unique(array_filter(array_map(fn($e) => $e['institucion_nombre'] ?? null, $egresados)));
    $horasTotales = array_sum(array_map(fn($e) => (int)($e['horas_acumuladas'] ?? 0), $egresados));
    ?>

    <!-- ===== TARJETAS KPI (ESTILO EXÁMENES RÁPIDOS) ===== -->
    <div class="eg-kpi-grid">
        <!-- Total Egresados -->
        <div class="eg-kpi-card c-blue">
            <div class="eg-kpi-top">
                <div>
                    <div class="eg-kpi-label">Total Egresados</div>
                    <div class="eg-kpi-val" style="color:#2563eb;"><?= $totalEgresados ?></div>
                    <div class="eg-kpi-sub">expedientes archivados</div>
                </div>
                <div class="eg-kpi-icon" style="background:#eff6ff;">
                    <i class="ti ti-school" style="color:#2563eb;"></i>
                </div>
            </div>
        </div>

        <!-- Cohortes Culminadas -->
        <div class="eg-kpi-card c-amber">
            <div class="eg-kpi-top">
                <div>
                    <div class="eg-kpi-label">Cohortes / Períodos</div>
                    <div class="eg-kpi-val" style="color:#d97706;"><?= count($periodosUnicos) ?></div>
                    <div class="eg-kpi-sub">períodos académicos</div>
                </div>
                <div class="eg-kpi-icon" style="background:#fffbeb;">
                    <i class="ti ti-calendar-check" style="color:#d97706;"></i>
                </div>
            </div>
        </div>

        <!-- Horas Cumplidas Totales -->
        <div class="eg-kpi-card c-green">
            <div class="eg-kpi-top">
                <div>
                    <div class="eg-kpi-label">Horas Cumplidas</div>
                    <div class="eg-kpi-val" style="color:#059669;"><?= number_format($horasTotales) ?></div>
                    <div class="eg-kpi-sub">horas certificadas</div>
                </div>
                <div class="eg-kpi-icon" style="background:#ecfdf5;">
                    <i class="ti ti-clock-check" style="color:#059669;"></i>
                </div>
            </div>
        </div>

        <!-- Instituciones Vinculadas -->
        <div class="eg-kpi-card c-purple">
            <div class="eg-kpi-top">
                <div>
                    <div class="eg-kpi-label">Instituciones</div>
                    <div class="eg-kpi-val" style="color:#7c3aed;"><?= count($institucionesUnicas) ?></div>
                    <div class="eg-kpi-sub">casas de estudio</div>
                </div>
                <div class="eg-kpi-icon" style="background:#f5f3ff;">
                    <i class="ti ti-building" style="color:#7c3aed;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== TOOLBAR DE FILTROS ===== -->
    <div class="eg-filter-bar">
        <!-- Buscador en tiempo real -->
        <div class="eg-search-wrap">
            <i class="ti ti-search"></i>
            <input type="text" id="egSearch" class="eg-search-input" placeholder="Buscar por nombre, CI o correo…" autocomplete="off">
        </div>

        <!-- Filtro por Período -->
        <select id="egFiltroPeriodo" class="eg-select-pill">
            <option value="">Todos los períodos</option>
            <?php foreach ($periodosUnicos as $pNom): ?>
                <option value="<?= htmlspecialchars($pNom) ?>"><?= htmlspecialchars($pNom) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Filtro por Institución -->
        <select id="egFiltroInst" class="eg-select-pill">
            <option value="">Todas las instituciones</option>
            <?php foreach ($institucionesUnicas as $iNom): ?>
                <option value="<?= htmlspecialchars($iNom) ?>"><?= htmlspecialchars($iNom) ?></option>
            <?php endforeach; ?>
        </select>

        <button onclick="limpiarFiltrosEgresados()" style="background:#f1f5f9;color:#64748b;border:none;padding:8px 14px;border-radius:50px;font-size:0.8rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
            <i class="ti ti-rotate"></i> Restablecer
        </button>
    </div>

    <!-- ===== TABLA DATATABLE DE EGRESADOS (DESKTOP) ===== -->
    <div class="eg-table-card sgp-solo-desktop">
        <div class="table-responsive">
            <table id="egresadosTable" class="table table-hover align-middle mb-0" style="width:100%;">
                <thead class="bg-light text-uppercase text-muted small fw-bold">
                    <tr>
                        <th class="px-4 py-3 border-0">Pasante</th>
                        <th class="px-4 py-3 border-0">Período / Cohorte</th>
                        <th class="px-4 py-3 border-0">Institución & Depto</th>
                        <th class="px-4 py-3 border-0">Carga Horaria</th>
                        <th class="px-4 py-3 border-0">Estado</th>
                        <th class="px-4 py-3 border-0 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($egresados)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ti ti-school-off fs-1 d-block mb-2 text-slate-300"></i>
                            <strong>No hay pasantes egresados registrados</strong>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($egresados as $e):
                        $inicial = strtoupper(mb_substr($e['name'] ?? 'P', 0, 1));
                        $hAcum   = (int)($e['horas_acumuladas'] ?? 0);
                        $hMeta   = (int)($e['horas_meta'] ?? 1440);
                        $pct     = $hMeta > 0 ? min(100, round(($hAcum / $hMeta) * 100)) : 100;
                    ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="dt-name-cell">
                                <div class="dt-avatar" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6);"><?= $inicial ?></div>
                                <div>
                                    <div class="dt-cell-primary"><?= htmlspecialchars($e['name']) ?></div>
                                    <div class="dt-cell-secondary">
                                        <i class="ti ti-id"></i> <?= htmlspecialchars($e['cedula'] ?? '—') ?> &nbsp;·&nbsp; <?= htmlspecialchars($e['email']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge" style="background:#eff6ff;color:#1d4ed8;padding:6px 12px;border-radius:8px;font-weight:700;font-size:0.83rem;display:inline-flex;align-items:center;gap:4px;">
                                <i class="ti ti-calendar"></i> <?= htmlspecialchars($e['periodo_nombre'] ?? 'Sin Período') ?>
                            </span>
                            <?php if (($e['periodo_estado'] ?? '') === 'Cerrado'): ?>
                                <span class="badge" style="background:#f1f5f9;color:#64748b;padding:3px 8px;border-radius:6px;font-size:0.7rem;margin-left:4px;">Cerrado</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div style="font-weight:600;color:#1e293b;font-size:0.85rem;">
                                <i class="ti ti-building text-muted me-1"></i> <?= htmlspecialchars($e['institucion_nombre'] ?? 'Sin institución') ?>
                            </div>
                            <div style="color:#64748b;font-size:0.75rem;">
                                <i class="ti ti-hierarchy-2 me-1"></i> <?= htmlspecialchars($e['departamento_nombre'] ?? 'General') ?>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div style="display:flex;align-items:center;gap:8px;min-width:140px;">
                                <div style="flex:1;background:#f1f5f9;border-radius:10px;height:8px;overflow:hidden;">
                                    <div style="width:<?= $pct ?>%;background:<?= $pct >= 100 ? '#10b981' : '#f59e0b' ?>;height:100%;border-radius:10px;"></div>
                                </div>
                                <span style="font-size:0.78rem;font-weight:800;color:<?= $pct >= 100 ? '#059669' : '#d97706' ?>;">
                                    <?= $hAcum ?> / <?= $hMeta ?>h
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge" style="background:#ecfdf5;color:#059669;padding:6px 12px;font-weight:800;border-radius:8px;font-size:0.8rem;display:inline-flex;align-items:center;gap:4px;">
                                <i class="ti ti-circle-check"></i> Egresado
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center align-middle">
                            <div class="d-flex justify-content-center" style="gap: 8px;">
                                <!-- Ver Perfil Universal -->
                                <button class="btn btn-sm border-0 shadow-sm" data-bs-toggle="tooltip" title="Ver Expediente" onclick="SGPModal.verUsuario(<?= $e['id'] ?>)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #2563eb; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-eye fs-5 text-white"></i>
                                </button>

                                <!-- Editar Usuario -->
                                <button class="btn btn-sm border-0 shadow-sm" data-bs-toggle="tooltip" title="Editar Datos" onclick="editUser('<?= UrlSecurity::encrypt($e['id']) ?>')" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #f59e0b; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-pencil fs-5 text-white"></i>
                                </button>

                                <!-- Restablecer Clave -->
                                <button class="btn btn-sm border-0 shadow-sm" data-bs-toggle="tooltip" title="Blanquear Clave" onclick="resetUser('<?= UrlSecurity::encrypt($e['id']) ?>')" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #64748b; color: #ffffff; border-radius: 6px !important;">
                                    <i class="ti ti-key fs-5 text-white"></i>
                                </button>

                                <!-- Reactivar Usuario si es necesario -->
                                <?php if($e['estado'] !== 'activo'): ?>
                                    <button class="btn btn-sm border-0 shadow-sm" data-bs-toggle="tooltip" title="Reactivar Usuario" onclick="toggleUser('<?= UrlSecurity::encrypt($e['id']) ?>', 'activar')" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #16a34a; color: #ffffff; border-radius: 6px !important;">
                                        <i class="ti ti-user-check fs-5 text-white"></i>
                                    </button>
                                <?php endif; ?>

                                <!-- Almanaque / Asistencias -->
                                <a href="<?= URLROOT ?>/asistencias/almanaque/<?= $e['id'] ?>" class="btn btn-sm border-0 shadow-sm" data-bs-toggle="tooltip" title="Ver Historial de Asistencia" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #0284c7; color: #ffffff; border-radius: 6px !important; text-decoration: none;">
                                    <i class="ti ti-calendar-stats fs-5 text-white"></i>
                                </a>

                                <!-- Eliminar Permanente -->
                                <button class="btn btn-sm border-0 shadow-sm" data-bs-toggle="tooltip" title="Eliminar Definitivamente" onclick="confirmarEliminarUsuario('<?= UrlSecurity::encrypt($e['id']) ?>', '<?= addslashes(htmlspecialchars(($e['nombres'] ?? '') . ' ' . ($e['apellidos'] ?? $e['email'] ?? ''))) ?>', 3)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px !important;">
                                    <i class="ti ti-trash fs-5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===== CARD VIEW (MÓVIL < 992px) ===== -->
    <div class="sgp-solo-mobile gap-3 pb-3" id="cardsEgresados">
        <?php foreach ($egresados as $e):
            $nombre = htmlspecialchars($e['name'] ?? '');
            $email  = htmlspecialchars($e['email'] ?? '');
            $ci     = htmlspecialchars($e['cedula'] ?? '—');
            $periodo = htmlspecialchars($e['periodo_nombre'] ?? 'Sin período');
            $ini    = strtoupper(mb_substr($nombre, 0, 1));
        ?>
        <div class="bcu-card" data-nombre="<?= strtolower($nombre) ?>" data-ci="<?= strtolower($ci) ?>" data-periodo="<?= strtolower($periodo) ?>" data-inst="<?= strtolower($e['institucion_nombre'] ?? '') ?>" style="background:white;border:1px solid #e2e8f0;border-radius:16px;padding:16px;margin-bottom:12px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:white;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;flex-shrink:0;">
                    <?= $ini ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <h4 style="margin:0;font-size:0.95rem;font-weight:800;color:#1e293b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $nombre ?></h4>
                    <p style="margin:2px 0 0;font-size:0.75rem;color:#64748b;"><?= $ci ?> · <?= $email ?></p>
                </div>
                <span style="background:#ecfdf5;color:#059669;padding:3px 8px;border-radius:6px;font-size:0.7rem;font-weight:800;">Egresado</span>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:10px;margin-bottom:12px;font-size:0.78rem;color:#475569;display:flex;flex-direction:column;gap:4px;">
                <div><i class="ti ti-calendar text-muted me-1"></i> <strong>Período:</strong> <?= $periodo ?></div>
                <div><i class="ti ti-building text-muted me-1"></i> <strong>Institución:</strong> <?= htmlspecialchars($e['institucion_nombre'] ?? 'Sin asignar') ?></div>
                <div><i class="ti ti-clock-check text-muted me-1"></i> <strong>Horas:</strong> <?= (int)($e['horas_acumuladas'] ?? 0) ?> / <?= (int)($e['horas_meta'] ?? 1440) ?>h</div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button onclick="SGPModal.verUsuario(<?= $e['id'] ?>)" class="btn btn-sm btn-primary" style="padding:6px 12px;border-radius:8px;font-size:0.78rem;">
                    <i class="ti ti-eye"></i> Ver Expediente
                </button>
                <a href="<?= URLROOT ?>/asistencias/almanaque/<?= $e['id'] ?>" class="btn btn-sm btn-outline-secondary" style="padding:6px 12px;border-radius:8px;font-size:0.78rem;">
                    <i class="ti ti-calendar-stats"></i> Asistencias
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Modal Editar Usuario (Reutilizado para modificar datos si es necesario) -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <div>
                <h2 style="margin:0;font-size:1.25rem;font-weight:800;color:white;">Modificar Usuario Egresado</h2>
                <p style="margin:2px 0 0;font-size:0.8rem;color:rgba(255,255,255,0.75);">Ajustar información del perfil</p>
            </div>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="modal-body-scroll">
            <form id="editUserForm">
                <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                <input type="hidden" name="id" id="edit_id">
                
                <div style="margin-bottom:14px;">
                    <label style="font-weight:700;font-size:0.85rem;color:#1e293b;display:block;margin-bottom:6px;">Nombre y Apellido</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <input type="text" id="edit_nombres" name="nombres" required class="form-control" placeholder="Nombres">
                        <input type="text" id="edit_apellidos" name="apellidos" required class="form-control" placeholder="Apellidos">
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-weight:700;font-size:0.85rem;color:#1e293b;display:block;margin-bottom:6px;">Cédula de Identidad</label>
                    <input type="text" id="edit_cedula" name="cedula" required class="form-control">
                </div>

                <div style="margin-bottom:14px;">
                    <label style="font-weight:700;font-size:0.85rem;color:#1e293b;display:block;margin-bottom:6px;">Correo Electrónico</label>
                    <input type="email" id="edit_correo" name="email" required class="form-control">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarEdit">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable de Egresados
    let table = null;
    if (typeof $.fn.DataTable !== 'undefined') {
        table = $('#egresadosTable').DataTable({
            language: {
                url: '<?= URLROOT ?>/js/datatables-es.json',
                emptyTable: "No hay pasantes egresados registrados"
            },
            responsive: true,
            pageLength: 15,
            order: [[1, 'desc']], // Ordenar por período más reciente
            dom: '<"row"<"col-sm-12"tr>><"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });
    }

    // Buscador interactivo
    const searchInput = document.getElementById('egSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (table) table.search(val).draw();

            // Filtrar cards móvil
            const q = val.toLowerCase();
            document.querySelectorAll('#cardsEgresados .bcu-card').forEach(card => {
                const nombre = card.dataset.nombre || '';
                const ci     = card.dataset.ci || '';
                const match  = !q || nombre.includes(q) || ci.includes(q);
                card.style.display = match ? '' : 'none';
            });
        });
    }

    // Filtro por Período
    const filtroPeriodo = document.getElementById('egFiltroPeriodo');
    if (filtroPeriodo) {
        filtroPeriodo.addEventListener('change', function() {
            const val = this.value;
            if (table) table.column(1).search(val ? '^' + val : '', true, false).draw();

            // Cards móvil
            const q = val.toLowerCase();
            document.querySelectorAll('#cardsEgresados .bcu-card').forEach(card => {
                const p = card.dataset.periodo || '';
                const match = !q || p.includes(q);
                card.style.display = match ? '' : 'none';
            });
        });
    }

    // Filtro por Institución
    const filtroInst = document.getElementById('egFiltroInst');
    if (filtroInst) {
        filtroInst.addEventListener('change', function() {
            const val = this.value;
            if (table) table.column(2).search(val ? val : '', true, false).draw();
        });
    }
});

function limpiarFiltrosEgresados() {
    document.getElementById('egSearch').value = '';
    document.getElementById('egFiltroPeriodo').value = '';
    document.getElementById('egFiltroInst').value = '';
    if (typeof $.fn.DataTable !== 'undefined') {
        const table = $('#egresadosTable').DataTable();
        table.search('').columns().search('').draw();
    }
    document.querySelectorAll('#cardsEgresados .bcu-card').forEach(c => c.style.display = '');
}

// ── Editar Usuario Modal ──
function editUser(encryptedId) {
    fetch('<?= URLROOT ?>/users/get/' + encryptedId)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.user) {
                const u = data.user;
                document.getElementById('edit_id').value = encryptedId;
                document.getElementById('edit_nombres').value = u.nombres || '';
                document.getElementById('edit_apellidos').value = u.apellidos || '';
                document.getElementById('edit_cedula').value = u.cedula || '';
                document.getElementById('edit_correo').value = u.correo || u.email || '';
                document.getElementById('editModal').classList.add('active');
            } else {
                NotificationService.error(data.message || 'Error al cargar datos');
            }
        });
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

document.getElementById('editUserForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = document.getElementById('edit_id').value;
    const btn = document.getElementById('btnGuardarEdit');
    btn.disabled = true;
    btn.innerText = 'Guardando...';

    fetch('<?= URLROOT ?>/users/update/' + id, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = 'Guardar Cambios';
        if (data.success) {
            closeEditModal();
            NotificationService.success(data.message || 'Actualizado con éxito');
            setTimeout(() => location.reload(), 1200);
        } else {
            NotificationService.error(data.message || 'Error al actualizar');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerText = 'Guardar Cambios';
        NotificationService.error('Error de conexión');
    });
});

// ── Blanquear / Restablecer Clave ──
function resetUser(encryptedId) {
    Swal.fire({
        title: '<i class="ti ti-lock"></i> Restablecer credenciales',
        html: '¿Estás seguro de blanquear el acceso de este usuario egresado?<br>La contraseña volverá a ser:<br><br>' +
              '<div style="font-size: 1.3rem; font-weight: 800; color: #db2777; background-color: #fdf2f8; padding: .8rem 1.2rem; border-radius: 12px; border: 2px dashed #fbcfe8; display: inline-block;">Sgp.[Cédula]</div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, restablecer',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            fetch('<?= URLROOT ?>/users/reset/' + encryptedId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Restablecido!', text: data.message });
                } else {
                    NotificationService.error(data.message);
                }
            });
        }
    });
}

// ── Reactivar / Desactivar ──
function toggleUser(encryptedId, accion) {
    const esActivar = accion === 'activar';
    Swal.fire({
        title: esActivar ? '¿Reactivar usuario?' : '¿Desactivar usuario?',
        text: esActivar ? 'La cuenta volverá a tener acceso activo en el sistema.' : 'La cuenta quedará bloqueada.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: esActivar ? '#16a34a' : '#ef4444',
        confirmButtonText: esActivar ? 'Sí, reactivar' : 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            fetch('<?= URLROOT ?>/users/toggleStatus/' + encryptedId, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    NotificationService.success(data.message);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    NotificationService.error(data.message);
                }
            });
        }
    });
}

// ── Eliminar Permanente ──
function confirmarEliminarUsuario(encryptedId, nombre, roleId) {
    Swal.fire({
        title: 'Eliminar expediente egresado',
        html: `<p style="color:#475569;font-size:.95rem;">Vas a eliminar permanentemente el registro de <strong>${nombre}</strong>.</p>
               <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px;font-size:.82rem;color:#b91c1c;margin-top:10px;">
                 <strong>Advertencia:</strong> Esta acción borrará permanentemente sus datos de usuario y registros de pasantía archivados.
               </div>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626'
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({
            title: 'Confirmación final',
            html: '<p style="color:#475569;font-size:.9rem;">Escribe <strong style="color:#dc2626;">ELIMINAR</strong> para confirmar:</p>',
            input: 'text',
            inputPlaceholder: 'ELIMINAR',
            showCancelButton: true,
            confirmButtonText: 'Eliminar definitivamente',
            confirmButtonColor: '#dc2626',
            inputValidator: v => v !== 'ELIMINAR' ? 'Escribe exactamente: ELIMINAR' : null
        }).then(r2 => {
            if (r2.isConfirmed) {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                fetch('<?= URLROOT ?>/users/eliminarPermanente/' + encryptedId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : '',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Eliminado', text: data.message, timer: 2000, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                });
            }
        });
    });
}
</script>
