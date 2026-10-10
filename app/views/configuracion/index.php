<?php
// Vista Configuración del Sistema — Bento UI
$flashSuccess = Session::getFlash('success');
$flashError = Session::getFlash('error');
$instituciones   = $data['instituciones'] ?? [];
$departamentos   = $data['departamentos'] ?? [];
$kioscoActivo    = (int) ($data['kioscoActivo'] ?? 1);
$feriados        = $data['feriados'] ?? [];
$totalInst       = count($instituciones);
$totalDepto      = count($departamentos);
$totalFeriados   = count($feriados);
$anioActual      = (int) date('Y');
$hoy             = date('Y-m-d');
?>
<style>
    /* ── ESTILOS COMPARTIDOS ────────────────────────── */
    .cfg-edit-btn {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #2563eb;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .2s;
    }
    .cfg-edit-btn:hover {
        background: #dbeafe;
    }

    /* ── BENTO GRID ─────────────────────────────── */
    .cfg-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 22px;
        margin-bottom: 22px;
    }

    .cfg-card {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.04);
        border: 1.5px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        min-width: 0;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .cfg-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12), 0 4px 8px rgba(15, 23, 42, 0.06);
        border-color: #cbd5e1;
    }

    .cg-3 {
        grid-column: span 3;
    }

    .cg-4 {
        grid-column: span 4;
    }

    .cg-5 {
        grid-column: span 5;
    }

    .cg-6 {
        grid-column: span 6;
    }

    .cg-7 {
        grid-column: span 7;
    }

    .cg-8 {
        grid-column: span 8;
    }

    .cg-12 {
        grid-column: span 12;
    }

    /* ── KPI CARDS ──────────────────────────────── */
    .cfg-kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 14px;
    }

    .cfg-kpi-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 5px;
    }

    .cfg-kpi-label {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
    }

    /* ── CARD HEADERS ───────────────────────────── */
    .cfg-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .cfg-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
    }

    .cfg-icon-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .cfg-badge {
        font-size: 0.72rem;
        padding: 3px 10px;
        border-radius: 20px;
        font-weight: 700;
    }

    /* ── LIST ITEMS ──────────────────────────────── */
    .cfg-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f8fafc;
    }

    .cfg-list-item:last-child {
        border-bottom: none;
    }

    .cfg-list-avatar {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cfg-list-name {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.88rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cfg-list-sub {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 260px;
    }

    .cfg-delete-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: #fef2f2;
        color: #ef4444;
        cursor: pointer;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .cfg-delete-btn:hover {
        background: #fee2e2;
        transform: scale(1.08);
    }

    /* ── ADD FORM (inline) ───────────────────────── */
    .cfg-add-form {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 2px dashed #e2e8f0;
    }

    .cfg-field-label {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 6px;
    }

    .cfg-input {
        width: 100%;
        padding: 10px 13px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.88rem;
        color: #1e293b;
        background: white;
        box-sizing: border-box;
        transition: border-color 0.2s;
        font-family: inherit;
    }

    .cfg-input:focus {
        outline: none;
        border-color: #2563eb;
    }

    .cfg-btn-primary {
        width: 100%;
        padding: 11px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        background: linear-gradient(135deg, #172554 0%, #2563eb 100%);
        color: white;
        font-size: 0.9rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        font-family: inherit;
    }

    .cfg-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
    }

    /* ── READONLY FIELD ──────────────────────────── */
    .cfg-readonly {
        width: 100%;
        padding: 10px 13px;
        border: 2px solid #f1f5f9;
        border-radius: 10px;
        font-size: 0.88rem;
        color: #64748b;
        background: #f8fafc;
        box-sizing: border-box;
        font-family: inherit;
    }

    /* ── TOGGLE SWITCH ───────────────────────────── */
    .cfg-toggle-wrap {
        position: relative;
        display: inline-block;
        width: 54px;
        height: 30px;
        cursor: pointer;
        flex-shrink: 0;
    }

    .cfg-toggle-wrap input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .cfg-toggle-track {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #e2e8f0;
        border-radius: 30px;
        transition: 0.3s;
    }

    .cfg-toggle-dot {
        position: absolute;
        top: 4px;
        left: 4px;
        width: 22px;
        height: 22px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.18);
    }

    .cfg-toggle-wrap input:checked~.cfg-toggle-track {
        background: #22c55e;
    }

    .cfg-toggle-wrap input:checked~.cfg-toggle-dot {
        left: 28px;
    }

    /* ── INFO ROW (datos ISP) ────────────────────── */
    .cfg-info-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        background: #f8fafc;
        border-radius: 10px;
        margin-bottom: 8px;
        border: 1px solid #f1f5f9;
    }

    .cfg-info-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cfg-info-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .cfg-info-value {
        font-size: 0.88rem;
        font-weight: 600;
        color: #1e293b;
        margin-top: 1px;
    }

    /* ── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 1200px) {
        .cg-3 {
            grid-column: span 6;
        }

        .cg-4 {
            grid-column: span 6;
        }

        .cg-5 {
            grid-column: span 12;
        }

        .cg-7 {
            grid-column: span 12;
        }

        .cg-8 {
            grid-column: span 12;
        }
    }

    @media (max-width: 760px) {

        .cg-3,
        .cg-4,
        .cg-6 {
            grid-column: span 12;
        }
    }
</style>

<div style="width:100%;">

    <!-- ===== BANNER ===== -->
    <style>
        @media (max-width: 991px) {
            .dashboard-banner {
                flex-direction: column !important;
                align-items: flex-start !important;
                padding: 24px 20px !important;
                gap: 20px !important;
            }

            .dashboard-banner>div:first-child,
            .dashboard-banner>div:nth-child(2) {
                display: none !important;
            }

            .dashboard-banner>div:last-child {
                width: 100% !important;
                text-align: left !important;
            }
        }
    </style>
    <div class="dashboard-banner"
        style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:24px;padding:38px 40px;margin-bottom:28px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;box-shadow:0 10px 30px rgba(23,37,84,0.15);">
        <div
            style="position:absolute;top:-40px;right:-40px;width:220px;height:220px;background:rgba(255,255,255,0.05);border-radius:50%;">
        </div>
        <div
            style="position:absolute;bottom:-50px;left:-30px;width:180px;height:180px;background:rgba(255,255,255,0.04);border-radius:50%;">
        </div>
        <div style="display:flex;align-items:center;gap:16px;z-index:1;">
            <div style="background:rgba(255,255,255,0.15);border-radius:16px;padding:14px;">
                <i class="ti ti-settings-2" style="font-size:32px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:white;font-size:1.85rem;font-weight:800;margin:0;letter-spacing:-0.5px;">
                    Configuración del Sistema
                </h1>
                <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:0.9rem;">
                    <i class="ti ti-building-community"></i> <?= $totalDepto ?>
                    departamento<?= $totalDepto !== 1 ? 's' : '' ?>
                    &nbsp;·&nbsp; <i class="ti ti-school"></i> <?= $totalInst ?>
                    institución<?= $totalInst !== 1 ? 'es' : '' ?>
                </p>
            </div>
        </div>
        <div style="z-index:1;text-align:right;">
            <div
                style="background:rgba(255,255,255,0.12);border-radius:12px;padding:10px 18px;color:rgba(255,255,255,0.9);font-size:0.82rem;font-weight:600;">
                <i class="ti ti-building-hospital"></i> Instituto de Salud Pública<br>
                <span style="opacity:0.7;font-weight:400;">SGP · Sistema de Gestión</span>
            </div>
        </div>
    </div>

    <!-- ===== FILA KPIs ===== -->
    <div class="cfg-grid">

        <!-- KPI: Departamentos -->
        <div class="cfg-card cg-3" style="border-left:4px solid #059669;">
            <div class="cfg-kpi-icon" style="background:#f0fdf4;color:#059669;">
                <i class="ti ti-building-community"></i>
            </div>
            <div class="cfg-kpi-value" style="color:#059669;"><?= $totalDepto ?></div>
            <div class="cfg-kpi-label">Departamentos activos</div>
        </div>

        <!-- KPI: Instituciones -->
        <div class="cfg-card cg-3" style="border-left:4px solid #2563eb;">
            <div class="cfg-kpi-icon" style="background:#eff6ff;color:#2563eb;">
                <i class="ti ti-school"></i>
            </div>
            <div class="cfg-kpi-value" style="color:#2563eb;"><?= $totalInst ?></div>
            <div class="cfg-kpi-label">Escuelas técnicas aliadas</div>
        </div>

        <!-- KPI: Kiosco -->
        <div class="cfg-card cg-3" style="border-left:4px solid <?= $kioscoActivo ? '#22c55e' : '#dc2626' ?>;"
            id="kpi-kiosco-card">
            <div class="cfg-kpi-icon"
                style="background:<?= $kioscoActivo ? '#dcfce7' : '#fee2e2' ?>;color:<?= $kioscoActivo ? '#16a34a' : '#dc2626' ?>;"
                id="kpi-kiosco-icon-box">
                <i class="ti ti-device-desktop"></i>
            </div>
            <div class="cfg-kpi-value"
                style="color:<?= $kioscoActivo ? '#16a34a' : '#dc2626' ?>;font-size:1.3rem;padding-top:4px;"
                id="kpi-kiosco-label"><?= $kioscoActivo ? 'Activo' : 'Inactivo' ?></div>
            <div class="cfg-kpi-label">Estado del kiosco</div>
        </div>

        <!-- KPI: Meta de horas -->
        <div class="cfg-card cg-3" style="border-left:4px solid #f59e0b;">
            <div class="cfg-kpi-icon" style="background:#fef3c7;color:#d97706;">
                <i class="ti ti-clock-hour-4"></i>
            </div>
            <div class="cfg-kpi-value" style="color:#d97706;font-size:1.3rem;padding-top:4px;">Dinámica</div>
            <div class="cfg-kpi-label">Horas meta por pasante</div>
        </div>

    </div><!-- /KPIs -->

    <!-- ===== FILA 2: Departamentos + Instituciones ===== -->
    <div class="cfg-grid">

        <!-- CARD: Departamentos -->
        <div class="cfg-card cg-6">
            <div class="cfg-card-header">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#f0fdf4;">
                        <i class="ti ti-building-community" style="font-size:1.1rem;color:#059669;"></i>
                    </div>
                    <div>
                        <div>Departamentos</div>
                        <div style="font-size:0.72rem;color:#94a3b8;font-weight:500;margin-top:1px;">Áreas internas del
                            ISP</div>
                    </div>
                </div>
                <span class="cfg-badge" style="background:#f0fdf4;color:#059669;"><?= $totalDepto ?>
                    activo<?= $totalDepto !== 1 ? 's' : '' ?></span>
            </div>

            <!-- Lista -->
            <div style="flex:1;overflow-y:auto;max-height:300px;">
                <?php if (empty($departamentos)): ?>
                    <div style="text-align:center;padding:36px 20px;color:#94a3b8;">
                        <i class="ti ti-building-community"
                            style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:0.5;"></i>
                        <p style="margin:0;font-size:0.88rem;">No hay departamentos registrados.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($departamentos as $depto): ?>
                        <div class="cfg-list-item">
                            <div class="cfg-list-avatar" style="background:linear-gradient(135deg,#172554,#1e3a8a);">
                                <i class="ti ti-building-community" style="font-size:1rem;color:white;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="cfg-list-name"><?= htmlspecialchars($depto['nombre']) ?></div>
                                <?php if (!empty($depto['descripcion'])): ?>
                                    <div class="cfg-list-sub"><?= htmlspecialchars($depto['descripcion']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;gap:4px;align-items:center;">
                                <button type="button" class="cfg-edit-btn"
                                    onclick="abrirEditarDepto(<?= (int) $depto['id'] ?>, '<?= htmlspecialchars($depto['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($depto['descripcion'] ?? '', ENT_QUOTES) ?>')"
                                    title="Editar departamento">
                                    <i class="ti ti-pencil" style="font-size:0.95rem;"></i>
                                </button>
                                <form method="POST" action="<?= URLROOT ?>/configuracion" style="margin:0;">
                                    <input type="hidden" name="accion" value="eliminar_departamento">
                                    <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">
                                    <input type="hidden" name="id" value="<?= (int) $depto['id'] ?>">
                                    <button type="button" class="cfg-delete-btn"
                                        onclick="confirmarEliminar(this, 'departamento', '<?= htmlspecialchars($depto['nombre'], ENT_QUOTES) ?>')"
                                        title="Eliminar departamento">
                                        <i class="ti ti-trash" style="font-size:0.95rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Botón abrir modal -->
            <div class="cfg-add-form" style="padding:16px;">
                <button type="button" onclick="abrirModalDepto()" class="cfg-btn-primary"
                    style="width:100%;padding:11px;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="ti ti-circle-plus"></i> Nuevo Departamento
                </button>
            </div>
        </div>

        <!-- CARD: Instituciones / Escuelas Técnicas -->
        <div class="cfg-card cg-6">
            <div class="cfg-card-header">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#eff6ff;">
                        <i class="ti ti-school" style="font-size:1.1rem;color:#2563eb;"></i>
                    </div>
                    <div>
                        <div>Escuelas Técnicas Aliadas</div>
                        <div style="font-size:0.72rem;color:#94a3b8;font-weight:500;margin-top:1px;">Origen de los
                            pasantes</div>
                    </div>
                </div>
                <span class="cfg-badge" style="background:#eff6ff;color:#2563eb;"><?= $totalInst ?>
                    institución<?= $totalInst !== 1 ? 'es' : '' ?></span>
            </div>

            <!-- Lista -->
            <div style="flex:1;overflow-y:auto;max-height:300px;">
                <?php if (empty($instituciones)): ?>
                    <div style="text-align:center;padding:36px 20px;color:#94a3b8;">
                        <i class="ti ti-school" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:0.5;"></i>
                        <p style="margin:0;font-size:0.88rem;">No hay escuelas registradas aún.</p>
                        <p style="margin:6px 0 0;font-size:0.78rem;">Usa el formulario para agregar la primera.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($instituciones as $inst): ?>
                        <div class="cfg-list-item">
                            <div class="cfg-list-avatar" style="background:linear-gradient(135deg,#1e40af,#3b82f6);">
                                <i class="ti ti-building-factory-2" style="font-size:1rem;color:white;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="cfg-list-name"><?= htmlspecialchars($inst['nombre']) ?></div>
                                <div class="cfg-list-sub">
                                    <i class="ti ti-map-pin"
                                        style="margin-right:2px;"></i><?= htmlspecialchars($inst['direccion'] ?? '—') ?>
                                </div>
                                <?php if (!empty($inst['representante_nombre'])): ?>
                                    <div
                                        style="margin-top:4px;font-size:0.75rem;color:#4f46e5;display:flex;align-items:center;gap:4px;">
                                        <i class="ti ti-user-check" style="font-size:0.8rem;"></i>
                                        <span><?= htmlspecialchars($inst['representante_nombre']) ?></span>
                                        <?php if (!empty($inst['representante_cargo'])): ?>
                                            <span style="color:#94a3b8;">· <?= htmlspecialchars($inst['representante_cargo']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;gap:4px;align-items:center;">
                                <button type="button" class="cfg-edit-btn"
                                    onclick="abrirEditarInstitucion(<?= (int) $inst['id'] ?>, <?= htmlspecialchars(json_encode($inst), ENT_QUOTES) ?>)"
                                    title="Editar institución">
                                    <i class="ti ti-pencil" style="font-size:0.95rem;"></i>
                                </button>
                                <form method="POST" action="<?= URLROOT ?>/configuracion" style="margin:0;">
                                    <input type="hidden" name="accion" value="eliminar_institucion">
                                    <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">
                                    <input type="hidden" name="id" value="<?= (int) $inst['id'] ?>">
                                    <button type="button" class="cfg-delete-btn"
                                        onclick="confirmarEliminar(this, 'institución', '<?= htmlspecialchars($inst['nombre'], ENT_QUOTES) ?>')"
                                        title="Eliminar institución">
                                        <i class="ti ti-trash" style="font-size:0.95rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Botón abrir modal -->
            <div class="cfg-add-form" style="padding:16px;">
                <button type="button" onclick="abrirModalInstitucion()" class="cfg-btn-primary"
                    style="width:100%;padding:11px;background:linear-gradient(135deg,#1e40af,#3b82f6);box-shadow:0 4px 12px rgba(59,130,246,0.2);display:flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="ti ti-circle-plus"></i> Nueva Institución
                </button>
            </div>
        </div>

    </div><!-- /Fila 2 -->

    <!-- ===== FILA 3: Datos ISP + Kiosco ===== -->
    <div class="cfg-grid">

        <!-- CARD: Datos de la Institución (Editable) -->
        <div class="cfg-card cg-4">
            <div class="cfg-card-header">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#fef3c7;">
                        <i class="ti ti-building-hospital" style="font-size:1.1rem;color:#d97706;"></i>
                    </div>
                    <div>
                        <div>Datos de la Institución</div>
                        <div style="font-size:0.72rem;color:#94a3b8;font-weight:500;margin-top:1px;">Información del ISP
                        </div>
                    </div>
                </div>
                <button type="button" onclick="abrirModalISP()" style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:4px;transition:all 0.2s;" onmouseover="this.style.background='#dbeafe';" onmouseout="this.style.background='#eff6ff';">
                    <i class="ti ti-edit"></i> Editar
                </button>
            </div>

            <?php
            $isp = $data['ispInfo'] ?? [];
            $infoCampos = [
                ['icon' => 'ti-building-hospital', 'bg' => '#fef3c7', 'clr' => '#d97706', 'label' => 'Nombre', 'value' => $isp['nombre'] ?? 'Instituto de Salud Pública del Estado Bolívar (ISP)'],
                ['icon' => 'ti-id-badge', 'bg' => '#eff6ff', 'clr' => '#2563eb', 'label' => 'RIF', 'value' => $isp['rif'] ?? 'G-20000366-9'],
                ['icon' => 'ti-map', 'bg' => '#f0fdf4', 'clr' => '#059669', 'label' => 'Estado', 'value' => $isp['estado'] ?? 'Bolívar'],
                ['icon' => 'ti-map-pin', 'bg' => '#fdf2f8', 'clr' => '#9333ea', 'label' => 'Ciudad', 'value' => $isp['ciudad'] ?? 'Ciudad Bolívar'],
                ['icon' => 'ti-home', 'bg' => '#f8fafc', 'clr' => '#64748b', 'label' => 'Dirección', 'value' => $isp['direccion'] ?? 'Paseo Meneses, Torre ISP, Piso 3'],
            ];
            foreach ($infoCampos as $c): ?>
                <div class="cfg-info-row">
                    <div class="cfg-info-icon" style="background:<?= $c['bg'] ?>;">
                        <i class="ti <?= $c['icon'] ?>" style="font-size:0.95rem;color:<?= $c['clr'] ?>;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="cfg-info-label"><?= $c['label'] ?></div>
                        <div class="cfg-info-value" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($c['value']) ?>">
                            <?= htmlspecialchars($c['value']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CARD: Kiosco -->
        <div class="cfg-card cg-4">
            <div class="cfg-card-header">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#fef3c7;">
                        <i class="ti ti-device-desktop" style="font-size:1.1rem;color:#d97706;"></i>
                    </div>
                    <div>
                        <div>Configuración del Kiosco</div>
                        <div style="font-size:0.72rem;color:#94a3b8;font-weight:500;margin-top:1px;">Registro de
                            asistencia de pasantes</div>
                    </div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">
                <!-- Toggle Estado -->
                <div
                    style="background:#f8fafc;border-radius:14px;padding:16px;border:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <p style="margin:0;font-weight:700;color:#1e293b;font-size:0.92rem;">Estado del Kiosco</p>
                        <p style="margin:3px 0 0;color:#64748b;font-size:0.78rem;">Habilitar o deshabilitar el acceso
                        </p>
                    </div>
                    <label class="cfg-toggle-wrap">
                        <input type="checkbox" id="kioscoToggle" <?= $kioscoActivo ? 'checked' : '' ?>>
                        <span class="cfg-toggle-track"></span>
                        <span class="cfg-toggle-dot"></span>
                    </label>
                </div>

                <div id="kioscoStatusBadge"
                    style="background:#d1fae5;border-radius:10px;padding:11px 14px;display:flex;align-items:center;gap:9px;">
                    <i class="ti ti-circle-check" id="kioscoStatusIcon"
                        style="color:#059669;font-size:1.15rem;flex-shrink:0;"></i>
                    <div>
                        <p style="margin:0;font-size:0.82rem;font-weight:700;" id="kioscoStatusText">Kiosco activo y
                            operativo</p>
                        <p style="margin:2px 0 0;font-size:0.73rem;color:#065f46;" id="kioscoStatusSub">Los pasantes
                            pueden registrar asistencia</p>
                    </div>
                </div>

                <div
                    style="background:#f1f5f9;border-radius:9px;padding:10px 13px;display:flex;align-items:flex-start;gap:8px;">
                    <i class="ti ti-info-circle" style="color:#64748b;font-size:1rem;flex-shrink:0;margin-top:1px;"></i>
                    <span style="font-size:0.76rem;color:#64748b;line-height:1.5;">
                        El kiosco usa PIN de 4 dígitos para registrar entradas y salidas. El estado se mantiene durante
                        la sesión actual.
                    </span>
                </div>
            </div>
        </div>

        <!-- CARD: Reset PIN -->
        <div class="cfg-card cg-4">
            <div class="cfg-card-header">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#fef3c7;">
                        <i class="ti ti-key" style="font-size:1.1rem;color:#d97706;"></i>
                    </div>
                    <div>
                        <div>Restablecer PIN</div>
                        <div style="font-size:0.72rem;color:#94a3b8;font-weight:500;margin-top:1px;">De Pasante</div>
                    </div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;">
                <button onclick="SGPModal.buscar()"
                    style="width:100%;padding:12px;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;border-radius:10px;font-size:0.9rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.2s;box-shadow:0 4px 12px rgba(245,158,11,0.3);font-family:inherit;">
                    <i class="ti ti-key" style="font-size:1.1rem;"></i> Buscar Pasante
                </button>

                <div
                    style="background:#fef3c7;border-radius:10px;padding:10px 13px;display:flex;align-items:flex-start;gap:8px;">
                    <i class="ti ti-info-circle" style="color:#92400e;font-size:1rem;flex-shrink:0;margin-top:2px;"></i>
                    <span style="font-size:0.76rem;color:#92400e;font-weight:500;line-height:1.4;">
                        Genera un nuevo PIN aleatorio de 4 dígitos. Se mostrará una sola vez.
                    </span>
                </div>

                <!-- Quick links -->
                <div style="display:flex;gap:8px;flex-direction:column;margin-top:4px;">
                    <a href="<?= URLROOT ?>/kiosco" target="_blank"
                        style="padding:10px;border:2px solid #fef3c7;border-radius:9px;text-align:center;font-size:0.78rem;font-weight:700;color:#d97706;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:5px;transition:all 0.2s;"
                        onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='transparent'">
                        <i class="ti ti-external-link"></i> Abrir Kiosco
                    </a>
                    <a href="<?= URLROOT ?>/asistencias"
                        style="padding:10px;border:2px solid #e0e7ff;border-radius:9px;text-align:center;font-size:0.78rem;font-weight:700;color:#6366f1;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:5px;transition:all 0.2s;"
                        onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='transparent'">
                        <i class="ti ti-clipboard-list"></i> Ver Asistencias
                    </a>
                </div>
            </div>
        </div>

    </div><!-- /Fila 3 -->

    <!-- ===== FILA 4: Días Feriados + Mantenimiento ===== -->
    <div class="cfg-grid">

        <!-- CARD: Días Feriados (Premium Redesign) -->
        <div class="cfg-card cg-8">
            <?php
            $meses_es    = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            $diasSemanaF = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
            $tipoPalF    = [
                'Nacional'      => ['gradient'=>'linear-gradient(135deg,#7c3aed,#a78bfa)', 'bg'=>'#f5f3ff', 'color'=>'#7c3aed', 'dot'=>'#8b5cf6', 'border'=>'#ddd6fe'],
                'Regional'      => ['gradient'=>'linear-gradient(135deg,#16a34a,#22c55e)', 'bg'=>'#f0fdf4', 'color'=>'#16a34a', 'dot'=>'#22c55e', 'border'=>'#bbf7d0'],
                'Institucional' => ['gradient'=>'linear-gradient(135deg,#1e3a8a,#2563eb)', 'bg'=>'#eff6ff', 'color'=>'#2563eb', 'dot'=>'#3b82f6', 'border'=>'#bfdbfe'],
            ];
            $feriadosObj = array_map(fn($f) => (object)$f, $feriados);

            // Próximo feriado
            $proximoF  = null; $diasProx = 0;
            foreach ($feriadosObj as $f) {
                if ($f->fecha >= $hoy) { $proximoF = $f; $diasProx = (int)round((strtotime($f->fecha) - strtotime($hoy)) / 86400); break; }
            }
            // Agrupar por mes
            $porMes = [];
            foreach ($feriadosObj as $f) { $porMes[date('Y-m', strtotime($f->fecha))][] = $f; }
            ?>

            <!-- Header -->
            <div class="cfg-card-header">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#f5f3ff;">
                        <i class="ti ti-calendar-event" style="font-size:1.1rem;color:#7c3aed;"></i>
                    </div>
                    <div>
                        <div>Días Feriados</div>
                        <div style="font-size:0.72rem;color:#94a3b8;font-weight:500;margin-top:1px;">Días no laborables — excluidos del auto-fill de asistencias</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span class="cfg-badge" style="background:#f5f3ff;color:#7c3aed;"><?= $totalFeriados ?> en <?= $anioActual ?></span>
                    <!-- Botón de sincronización API -->
                    <div style="display:flex;align-items:center;gap:6px;">
                        <button type="button" id="btnSyncFeriados" onclick="sincronizarFeriados(<?= date('Y') ?>)"
                            style="background:linear-gradient(135deg,#059669,#10b981);color:white;padding:7px 13px;border:none;border-radius:10px;font-size:0.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .2s;box-shadow:0 4px 10px rgba(5,150,105,0.3);"
                            onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 14px rgba(5,150,105,0.4)'"
                            onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 10px rgba(5,150,105,0.3)'">
                            <i class="ti ti-refresh" id="iconSyncFeriados"></i> Sincronizar
                        </button>
                    </div>
                    <a href="<?= URLROOT ?>/configuracion/calendario_feriados" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);color:white;padding:7px 12px;border-radius:10px;font-size:0.8rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:5px;transition:all .2s;box-shadow:0 4px 10px rgba(124,58,237,0.3);" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                        <i class="ti ti-calendar-stats"></i> Almanaque
                    </a>
                </div>
            </div>

            <?php if (empty($feriados)): ?>
            <!-- Estado vacío -->
            <div style="text-align:center;padding:44px 20px;color:#94a3b8;flex:1;">
                <i class="ti ti-calendar-off" style="font-size:3rem;display:block;margin-bottom:12px;opacity:.4;"></i>
                <p style="margin:0;font-size:.88rem;font-weight:600;">No hay días feriados registrados</p>
                <p style="margin:6px 0 0;font-size:.78rem;">Agrega feriados para excluirlos del auto-fill de asistencias.</p>
            </div>

            <?php else: ?>

            <!-- ── Banner: Próximo Feriado ── -->
            <?php if ($proximoF):
                $palP = $tipoPalF[$proximoF->tipo] ?? $tipoPalF['Nacional'];
                $tsP  = strtotime($proximoF->fecha);
                $countdownLabel = $diasProx === 0 ? '¡Hoy!' : ($diasProx === 1 ? 'Mañana' : "En {$diasProx} días");
            ?>
            <div style="background:<?= $palP['bg'] ?>;border:1px solid <?= $palP['border'] ?>;border-radius:14px;padding:13px 16px;margin-bottom:16px;display:flex;align-items:center;gap:14px;">
                <div style="background:<?= $palP['gradient'] ?>;border-radius:10px;width:46px;min-width:46px;text-align:center;padding:7px 0;box-shadow:0 4px 10px rgba(0,0,0,.15);flex-shrink:0;">
                    <div style="font-size:1.2rem;font-weight:900;color:white;line-height:1;"><?= date('d',$tsP) ?></div>
                    <div style="font-size:.58rem;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.6px;"><?= strtoupper(substr($meses_es[(int)date('n',$tsP)],0,3)) ?></div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:<?= $palP['color'] ?>;margin-bottom:2px;">Próximo feriado</div>
                    <div style="font-size:.9rem;font-weight:800;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($proximoF->nombre) ?></div>
                    <div style="font-size:.7rem;color:#94a3b8;margin-top:2px;"><?= $diasSemanaF[(int)date('w',$tsP)] ?> <?= date('d',$tsP) ?> de <?= $meses_es[(int)date('n',$tsP)] ?></div>
                </div>
                <div style="background:<?= $palP['gradient'] ?>;color:white;border-radius:20px;padding:6px 13px;font-size:.75rem;font-weight:800;flex-shrink:0;white-space:nowrap;box-shadow:0 3px 8px rgba(0,0,0,.15);">
                    <?= $countdownLabel ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ── Lista agrupada por mes ── -->
            <div style="flex:1;overflow-y:auto;max-height:280px;padding-right:2px;">

                <?php
                $hayHistoricos = array_reduce($feriadosObj, fn($c,$f) => $c || $f->fecha < $hoy, false);
                if ($hayHistoricos): ?>
                <div style="display:flex;align-items:flex-start;gap:7px;padding:8px 10px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;margin-bottom:12px;">
                    <i class="ti ti-info-circle" style="font-size:.88rem;color:#94a3b8;flex-shrink:0;margin-top:1px;"></i>
                    <span style="font-size:.72rem;color:#94a3b8;line-height:1.45;">Los feriados <strong>en gris</strong> ya transcurrieron — su fecha y eliminación están bloqueadas para proteger el historial, pero puedes <strong>editar su nombre y tipo</strong> con el <i class="ti ti-pencil" style="font-size:.7rem;"></i>.</span>
                </div>
                <?php endif; ?>

                <?php foreach ($porMes as $mesKey => $feriadosMes):
                    $mesNum  = (int)date('n', strtotime($mesKey.'-01'));
                    $anioMes = date('Y', strtotime($mesKey.'-01'));
                    $hayProx = array_reduce($feriadosMes, fn($c,$f) => $c || $f->fecha >= $hoy, false);
                ?>
                <!-- Separador de mes -->
                <div style="display:flex;align-items:center;gap:8px;margin:0 0 8px;padding-top:4px;">
                    <span style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:<?= $hayProx ? '#7c3aed' : '#94a3b8' ?>;white-space:nowrap;"><?= $meses_es[$mesNum] ?> <?= $anioMes ?></span>
                    <div style="flex:1;height:1px;background:<?= $hayProx ? '#ddd6fe' : '#f1f5f9' ?>;"></div>
                    <span style="font-size:.65rem;font-weight:700;color:<?= $hayProx ? '#a78bfa' : '#cbd5e1' ?>;background:<?= $hayProx ? '#f5f3ff' : '#f8fafc' ?>;border-radius:20px;padding:1px 7px;"><?= count($feriadosMes) ?></span>
                </div>

                <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:12px;">
                <?php foreach ($feriadosMes as $f):
                    $esPasado = $f->fecha < $hoy;
                    $esHoy    = $f->fecha === $hoy;
                    $ts       = strtotime($f->fecha);
                    $diasR    = (int)round((strtotime($f->fecha) - strtotime($hoy)) / 86400);
                    $pal      = $tipoPalF[$f->tipo] ?? $tipoPalF['Nacional'];
                    $diaNom   = $diasSemanaF[(int)date('w',$ts)];
                    $numDia   = date('d',$ts);
                    $mesNom   = $meses_es[(int)date('n',$ts)];
                ?>
                <div style="display:flex;align-items:center;gap:10px;padding:9px 11px;background:<?= $esPasado ? '#fafafa' : 'white' ?>;border-radius:12px;border:1px solid <?= $esPasado ? '#f1f5f9' : $pal['border'] ?>;transition:border-color .15s,background .15s;"
                     onmouseover="this.style.background='<?= $esPasado ? '#f8fafc' : $pal['bg'] ?>';this.style.borderColor='<?= $esPasado ? '#e2e8f0' : $pal['dot'] ?>'"
                     onmouseout="this.style.background='<?= $esPasado ? '#fafafa' : 'white' ?>';this.style.borderColor='<?= $esPasado ? '#f1f5f9' : $pal['border'] ?>'">

                    <!-- Bloque fecha -->
                    <div style="background:<?= $esPasado ? '#e2e8f0' : $pal['gradient'] ?>;border-radius:9px;width:40px;min-width:40px;text-align:center;padding:6px 0;flex-shrink:0;<?= $esPasado ? '' : 'box-shadow:0 3px 8px rgba(0,0,0,.12);' ?>">
                        <div style="font-size:1rem;font-weight:900;color:<?= $esPasado ? '#94a3b8' : 'white' ?>;line-height:1;"><?= $numDia ?></div>
                        <div style="font-size:.54rem;font-weight:700;color:<?= $esPasado ? '#94a3b8' : 'rgba(255,255,255,.85)' ?>;text-transform:uppercase;letter-spacing:.5px;"><?= strtoupper(substr($mesNom,0,3)) ?></div>
                    </div>

                    <!-- Info -->
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:.83rem;font-weight:700;color:<?= $esPasado ? '#94a3b8' : '#1e293b' ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($f->nombre) ?></div>
                        <div style="display:flex;align-items:center;gap:5px;margin-top:3px;">
                            <span style="font-size:.68rem;color:#94a3b8;"><?= $diaNom ?></span>
                            <span style="font-size:.65rem;color:#cbd5e1;">·</span>
                            <?php if (!$esPasado): ?>
                            <span style="display:inline-flex;align-items:center;gap:3px;background:<?= $pal['bg'] ?>;color:<?= $pal['color'] ?>;font-size:.62rem;font-weight:700;padding:1px 7px;border-radius:20px;">
                                <span style="width:5px;height:5px;border-radius:50%;background:<?= $pal['dot'] ?>;flex-shrink:0;display:inline-block;"></span>
                                <?= htmlspecialchars($f->tipo) ?>
                            </span>
                            <?php else: ?>
                            <span style="display:inline-flex;align-items:center;gap:3px;background:#f1f5f9;color:#94a3b8;font-size:.62rem;font-weight:700;padding:1px 7px;border-radius:20px;">
                                <i class="ti ti-history" style="font-size:.6rem;"></i> Histórico
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Derecha: countdown / editar / trash -->
                    <?php if ($esPasado): ?>
                        <button type="button" class="cfg-delete-btn"
                            style="background:#f5f3ff;color:#7c3aed;flex-shrink:0;"
                            onclick="abrirEditarFeriado(<?= (int)$f->id ?>, '<?= htmlspecialchars($f->nombre, ENT_QUOTES) ?>', '<?= htmlspecialchars($f->tipo, ENT_QUOTES) ?>', '<?= $f->fecha ?>')"
                            title="Editar nombre y tipo">
                            <i class="ti ti-pencil" style="font-size:.9rem;"></i>
                        </button>
                    <?php else: ?>
                        <span style="background:<?= $pal['bg'] ?>;color:<?= $pal['color'] ?>;border-radius:20px;padding:3px 9px;font-size:.68rem;font-weight:800;flex-shrink:0;white-space:nowrap;">
                            <?= $esHoy ? '¡Hoy!' : ($diasR === 1 ? 'Mañana' : "+{$diasR}d") ?>
                        </span>
                        <form method="POST" action="<?= URLROOT ?>/configuracion" style="margin:0;flex-shrink:0;">
                            <input type="hidden" name="accion" value="eliminar_feriado">
                            <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">
                            <input type="hidden" name="id"     value="<?= (int)$f->id ?>">
                            <button type="button" class="cfg-delete-btn"
                                onclick="confirmarEliminarFeriado(this, '<?= htmlspecialchars($f->nombre, ENT_QUOTES) ?>')"
                                title="Eliminar feriado">
                                <i class="ti ti-trash" style="font-size:.9rem;"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>

            <!-- Botón agregar -->
            <div class="cfg-add-form" style="padding-top:16px;">
                <button type="button" onclick="abrirModalFeriado()" class="cfg-btn-primary">
                    <i class="ti ti-circle-plus"></i> Registrar Día Feriado
                </button>
            </div>
        </div>

        <!-- CARD: Mantenimiento del Sistema -->
        <div class="cfg-card cg-4">
            <div class="cfg-card-header">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#f0fdf4;">
                        <i class="ti ti-tool" style="font-size:1.1rem;color:#059669;"></i>
                    </div>
                    <div>
                        <div>Mantenimiento</div>
                        <div style="font-size:0.72rem;color:#94a3b8;font-weight:500;margin-top:1px;">Estado y limpieza del sistema</div>
                    </div>
                </div>
                <span class="cfg-badge" style="background:#f0fdf4;color:#059669;" id="bdg-estado-bd">
                    <i class="ti ti-circle" style="font-size:0.55rem;margin-right:2px;"></i>Sin verificar
                </span>
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;">

                <!-- Verificar BD -->
                <div style="background:#eff6ff;border-radius:12px;padding:14px;border:1px solid #bfdbfe;transition:all 0.2s;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <div>
                            <p style="margin:0;font-size:0.85rem;font-weight:800;color:#1e3a8a;">Base de Datos</p>
                            <p style="margin:2px 0 0;font-size:0.73rem;color:#3b82f6;" id="txt-bd-sub">Verificar conexión y estado</p>
                        </div>
                        <div style="width:36px;height:36px;background:rgba(59,130,246,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="ti ti-database" style="font-size:1.3rem;color:#2563eb;"></i>
                        </div>
                    </div>
                    <button type="button" onclick="verificarBD(this)"
                        style="width:100%;padding:9px;border:none;border-radius:9px;background:white;color:#2563eb;font-size:0.85rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;font-family:inherit;box-shadow:0 2px 6px rgba(59,130,246,0.1);"
                        onmouseover="this.style.background='#dbeafe';this.style.transform='translateY(-1px)'" onmouseout="this.style.background='white';this.style.transform='none'">
                        <i class="ti ti-plug-connected"></i> <span id="txt-btn-bd">Verificar Conexión</span>
                    </button>
                </div>

                <!-- Limpiar sesiones -->
                <div style="background:#fff1f2;border-radius:12px;padding:14px;border:1px solid #fecdd3;transition:all 0.2s;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <div>
                            <p style="margin:0;font-size:0.85rem;font-weight:800;color:#881337;">Sesiones Expiradas</p>
                            <p style="margin:2px 0 0;font-size:0.73rem;color:#f43f5e;">Limpiar tokens de sesión vencidos</p>
                        </div>
                        <div style="width:36px;height:36px;background:rgba(244,63,94,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="ti ti-eraser" style="font-size:1.3rem;color:#e11d48;"></i>
                        </div>
                    </div>
                    <button type="button" onclick="limpiarSesiones(this)"
                        style="width:100%;padding:9px;border:none;border-radius:9px;background:white;color:#e11d48;font-size:0.85rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s;font-family:inherit;box-shadow:0 2px 6px rgba(244,63,94,0.1);"
                        onmouseover="this.style.background='#ffe4e6';this.style.transform='translateY(-1px)'" onmouseout="this.style.background='white';this.style.transform='none'">
                        <i class="ti ti-trash-x"></i> <span>Limpiar Sesiones</span>
                    </button>
                </div>

                <!-- Reloj Personalizado: Ventana de Asistencia -->
                <style>
                /* ── Dígitos del reloj (green) ──────────────────── */
                .mfc-digit {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: clamp(24px, 2.8vw, 36px);
                    height: clamp(36px, 4vw, 54px);
                    background: #4c1d95;
                    border-radius: 8px;
                    box-shadow: 0 3px 10px rgba(124,58,237,0.3);
                    flex-shrink: 1;
                    transition: transform 0.08s ease;
                }
                .mfc-digit::before {
                    content: attr(data-v);
                    font-family: 'Courier New', Courier, monospace !important;
                    font-size: clamp(1.2rem, 2.2vw, 2rem);
                    font-weight: 900;
                    color: #ede9fe;
                    line-height: 1;
                }
                .mfc-digit.flip { transform: scaleY(0.82); }
                .mfc-sep-char {
                    font-size: clamp(1.1rem, 2vw, 1.8rem);
                    font-weight: 900;
                    color: #4c1d95;
                    line-height: 1;
                    padding: 0 1px;
                    align-self: center;
                    margin-top: 0;
                }
                .mfc-label {
                    font-size: clamp(0.55rem, 0.8vw, 0.65rem);
                    font-weight: 800;
                    color: #7c3aed;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    margin-top: 5px;
                    text-align: center;
                }
                #mfc-wrap {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: clamp(2px, 0.7vw, 6px);
                    margin: 8px 0 14px;
                    flex-wrap: nowrap;
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
                    overflow: hidden;
                }
                @keyframes mfcPulse{0%,100%{box-shadow:0 2px 8px rgba(220,38,38,0.2);}50%{box-shadow:0 2px 18px rgba(220,38,38,0.55);}}
                /* ── Mobile ─────────────────────────────────────── */
                @media (max-width: 480px) {
                    .mfc-digit { width: 22px; height: 34px; border-radius: 6px; }
                    .mfc-digit::before { font-size: 1.15rem; }
                    .mfc-sep-char { font-size: 1.1rem; }
                    .mfc-period-badge { font-size: 0.6rem; padding: 2px 4px; }
                }
                </style>

                <div style="background:#f5f3ff;border-radius:12px;padding:16px;border:1px solid #ddd6fe;">
                    <!-- Encabezado -->
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <?php
                            $mesesEs = ['1'=>'Ene','2'=>'Feb','3'=>'Mar','4'=>'Abr','5'=>'May','6'=>'Jun','7'=>'Jul','8'=>'Ago','9'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
                            $fechaFormateada = date('d') . ' ' . $mesesEs[date('n')] . ' ' . date('Y');
                        ?>
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <p style="margin:0;font-size:0.85rem;font-weight:800;color:#4c1d95;">Ventana de Asistencia</p>
                                <span style="background:#ddd6fe;color:#5b21b6;padding:2px 8px;border-radius:12px;font-size:0.65rem;font-weight:700;display:inline-flex;align-items:center;gap:3px;" title="Fecha del servidor">
                                    <i class="ti ti-calendar-event"></i> <?= $fechaFormateada ?>
                                </span>
                            </div>
                            <p id="mfc-subtitle" style="margin:4px 0 0;font-size:0.72rem;color:#8b5cf6;">Tiempo restante hoy — cierre a las 00:00</p>
                        </div>
                        <button onclick="mostrarLeyendaReloj()"
                            title="Ver cómo funciona este módulo"
                            style="width:34px;height:34px;background:rgba(139,92,246,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;border:none;transition:all .2s;flex-shrink:0;"
                            onmouseover="this.style.background='rgba(139,92,246,0.28)'"
                            onmouseout="this.style.background='rgba(139,92,246,0.15)'">
                            <i class="ti ti-info-circle" style="font-size:1.25rem;color:#7c3aed;"></i>
                        </button>
                    </div>

                    <!-- Estado fin de semana (oculto por defecto, JS lo muestra si aplica) -->
                    <div id="mfc-weekend" style="display:none;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:16px 10px;margin:8px 0 14px;background:#f8fafc;border-radius:12px;border:1px dashed #cbd5e1;">
                        <i class="ti ti-calendar-off" style="font-size:2rem;color:#94a3b8;"></i>
                        <span id="mfc-weekend-label" style="font-size:0.82rem;font-weight:700;color:#64748b;text-align:center;">Fin de semana — sin asistencia</span>
                        <span style="font-size:0.72rem;color:#94a3b8;text-align:center;">El sistema retoma el lunes</span>
                    </div>

                    <!-- Dígitos del reloj -->
                    <div id="mfc-wrap" style="display:flex;align-items:flex-end;justify-content:center;gap:5px;margin:8px 0 14px;flex-wrap:nowrap;overflow:hidden;">
                        <!-- Horas -->
                        <div style="display:flex;flex-direction:column;align-items:center;">
                            <div style="display:flex;gap:3px;">
                                <span class="mfc-digit" id="mfc-h1" data-v="0"></span>
                                <span class="mfc-digit" id="mfc-h2" data-v="0"></span>
                            </div>
                            <span class="mfc-label">Horas</span>
                        </div>
                        <span class="mfc-sep-char">:</span>
                        <!-- Minutos -->
                        <div style="display:flex;flex-direction:column;align-items:center;">
                            <div style="display:flex;gap:3px;">
                                <span class="mfc-digit" id="mfc-m1" data-v="0"></span>
                                <span class="mfc-digit" id="mfc-m2" data-v="0"></span>
                            </div>
                            <span class="mfc-label">Min</span>
                        </div>
                        <span class="mfc-sep-char">:</span>
                        <!-- Segundos -->
                        <div style="display:flex;flex-direction:column;align-items:center;">
                            <div style="display:flex;gap:3px;">
                                <span class="mfc-digit" id="mfc-s1" data-v="0"></span>
                                <span class="mfc-digit" id="mfc-s2" data-v="0"></span>
                            </div>
                            <span class="mfc-label">Seg</span>
                        </div>
                    </div>

                    <!-- Barra de progreso del día -->
                    <div id="mfc-bar-wrap" style="width:100%;margin-bottom:10px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
                            <span style="font-size:0.65rem;font-weight:700;color:#8b5cf6;">Día transcurrido</span>
                            <span id="mfc-pct-label" style="font-size:0.65rem;font-weight:700;color:#8b5cf6;">0%</span>
                        </div>
                        <div style="width:100%;background:#ede9fe;border-radius:50rem;height:6px;overflow:hidden;">
                            <div id="mfc-daybar" style="height:100%;border-radius:50rem;background:linear-gradient(90deg,#16a34a,#22c55e);transition:width 1s linear,background .8s ease;width:0%;"></div>
                        </div>
                    </div>

                    <!-- Badge sin marcar -->
                    <div id="mfc-sinmarcar-box" style="width:100%;padding:8px 12px;border-radius:9px;background:white;box-shadow:0 2px 8px rgba(139,92,246,0.14);display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span id="mfc-badge-label" style="display:flex;align-items:center;gap:5px;font-size:0.75rem;font-weight:700;color:#6d28d9;">
                            <i class="ti ti-users" style="font-size:1rem;"></i>
                            Pasantes sin marcar hoy
                        </span>
                        <span style="display:flex;align-items:center;gap:4px;">
                            <span id="mfc-count" style="font-size:1.1rem;font-weight:900;color:#7c3aed;"><?= (int)($data['statsDB']['sin_marcar_hoy'] ?? 0) ?></span>
                            <i id="mfc-count-icon" class="ti ti-circle-check" style="font-size:1rem;color:#10b981;"></i>
                        </span>
                    </div>

                    <!-- Link bitácora -->
                    <a href="<?= URLROOT ?>/bitacora"
                        style="width:100%;padding:7px;border-radius:9px;background:white;color:#7c3aed;font-size:0.8rem;font-weight:800;display:flex;align-items:center;justify-content:center;gap:5px;text-decoration:none;box-shadow:0 2px 6px rgba(139,92,246,0.1);transition:all .2s;"
                        onmouseover="this.style.background='#ede9fe';this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.background='white';this.style.transform='none'">
                        <i class="ti ti-clipboard-list"></i> Ver Bitácora del Sistema
                    </a>
                </div>

                <script>
                (function(){
                    // ── Detectar fin de semana (0=Dom, 6=Sáb) ──────────
                    var hoyDow = new Date().getDay();
                    var esFinDeSemana = (hoyDow === 0 || hoyDow === 6);

                    if (esFinDeSemana) {
                        var nombreDia = hoyDow === 6 ? 'Sábado' : 'Domingo';
                        document.getElementById('mfc-weekend-label').textContent = nombreDia + ' — sin asistencia';
                        document.getElementById('mfc-weekend').style.display  = 'flex';
                        document.getElementById('mfc-wrap').style.display     = 'none';
                        document.getElementById('mfc-bar-wrap').style.display = 'none';
                        document.getElementById('mfc-sinmarcar-box').style.display = 'none';
                        document.getElementById('mfc-subtitle').textContent   = 'Día no hábil — el sistema retoma el lunes';
                        document.getElementById('mfc-subtitle').style.color   = '#94a3b8';
                        return; // No iniciar el reloj
                    }

                    function setDigit(id, val) {
                        var el = document.getElementById(id);
                        if (!el) return;
                        var v = String(val);
                        if (el.dataset.v === v) return;
                        el.dataset.v = v;
                        el.classList.add('flip');
                        setTimeout(function(){ el.classList.remove('flip'); }, 80);
                    }

                    var _mfcTimer;
                    function updateClock() {
                        // Si el elemento raíz ya no existe (navegación PJAX), detener el intervalo
                        if (!document.getElementById('mfc-count')) {
                            clearInterval(_mfcTimer); return;
                        }
                        var now = new Date(), mid = new Date(now);
                        mid.setHours(24, 0, 0, 0);
                        var rem = Math.max(0, Math.floor((mid - now) / 1000));

                        var h24  = Math.floor(rem / 3600);
                        var mins = Math.floor((rem % 3600) / 60);
                        var secs = rem % 60;

                        setDigit('mfc-h1', Math.floor(h24 / 10));
                        setDigit('mfc-h2', h24 % 10);
                        setDigit('mfc-m1', Math.floor(mins / 10));
                        setDigit('mfc-m2', mins % 10);
                        setDigit('mfc-s1', Math.floor(secs / 10));
                        setDigit('mfc-s2', secs % 10);

                        /* Progress bar */
                        var pct    = Math.round((86400 - rem) / 864);
                        var bar    = document.getElementById('mfc-daybar');
                        var pctLbl = document.getElementById('mfc-pct-label');
                        if (bar)    bar.style.width = pct + '%';
                        if (pctLbl) pctLbl.textContent = pct + '%';

                        /* Urgency — solo la barra cambia de color; reloj permanece morado */
                        var barColor, barTextColor;
                        if (rem > 14400) {
                            barColor = 'linear-gradient(90deg,#16a34a,#22c55e)'; barTextColor = '#16a34a'; // Verde
                        } else if (rem > 3600) {
                            barColor = 'linear-gradient(90deg,#d97706,#f59e0b)'; barTextColor = '#d97706'; // Amarillo
                        } else {
                            barColor = 'linear-gradient(90deg,#dc2626,#ef4444)'; barTextColor = '#dc2626'; // Rojo
                        }
                        if (pctLbl) pctLbl.style.color = barTextColor;
                        if (bar)    bar.style.background = barColor;

                        /* Auto-fill al cruzar medianoche */
                        if (rem === 0 && !window._mfcAutoFillFired) {
                            window._mfcAutoFillFired = true;
                            var csrf = document.querySelector('meta[name="csrf-token"]');
                            if (csrf) {
                                var fd = new FormData();
                                fd.append('csrf_token', csrf.content);
                                fetch(URLROOT + '/daemon/autoFill', { method:'POST', body:fd })
                                    .then(function(r){ return r.json(); })
                                    .then(function(d){
                                        if (d.success) {
                                            var n = d.stats ? d.stats.dias_rellenos : 0;
                                            if (n > 0 && typeof Swal !== 'undefined') {
                                                Swal.fire({ icon:'info', title:'Cierre del día', text: n + ' registros de ausencia generados automáticamente.', confirmButtonColor:'#4c1d95', timer:5000, timerProgressBar:true });
                                            }
                                        }
                                    })
                                    .catch(function(){});
                            }
                        }

                        /* Badge sin marcar */
                        var sinMarcar = parseInt(document.getElementById('mfc-count').textContent) || 0;
                        var box = document.getElementById('mfc-sinmarcar-box');
                        var lbl = document.getElementById('mfc-badge-label');
                        var ico = document.getElementById('mfc-count-icon');
                        if (sinMarcar > 0 && rem < 7200) {
                            if (box) { box.style.animation = 'mfcPulse 1.4s ease-in-out infinite'; box.style.border = '1px solid rgba(220,38,38,0.25)'; }
                            if (lbl) lbl.style.color = '#dc2626';
                            if (ico) { ico.className = 'ti ti-alert-triangle'; ico.style.color = '#dc2626'; }
                        } else if (sinMarcar > 0) {
                            if (box) { box.style.animation = ''; box.style.border = 'none'; }
                            if (lbl) lbl.style.color = '#d97706';
                            if (ico) { ico.className = 'ti ti-alert-circle'; ico.style.color = '#d97706'; }
                        } else {
                            if (box) { box.style.animation = ''; box.style.border = 'none'; }
                            if (lbl) lbl.style.color = '#059669';
                            if (ico) { ico.className = 'ti ti-circle-check'; ico.style.color = '#10b981'; }
                        }
                    }

                    updateClock();
                    _mfcTimer = setInterval(updateClock, 1000);
                })();

                function mostrarLeyendaReloj(){
                    Swal.fire({
                        title: 'Ventana de Asistencia',
                        icon: 'info',
                        html: '<div style="text-align:left;font-size:0.82rem;line-height:1.55;max-height:55vh;overflow-y:auto;padding-right:4px;">'
                            + '<p style="margin:0 0 8px;color:#475569;">Muestra el <b style="color:#1e293b;">tiempo restante del día</b> hasta las 00:00 en formato 12h. Al llegar a cero, los pasantes sin asistencia quedan como <b style="color:#1e293b;">Ausentes</b> automáticamente.</p>'
                            + '<hr style="border:none;border-top:1px solid #f1f5f9;margin:8px 0;">'
                            + '<p style="font-weight:800;color:#1e293b;margin:0 0 6px;font-size:0.78rem;text-transform:uppercase;letter-spacing:.4px;">Urgencia — Barra de Progreso</p>'
                            + '<p style="margin:0 0 8px;font-size:0.76rem;color:#64748b;">El reloj permanece morado. Solo la barra cambia de color según el tiempo restante.</p>'
                            + '<div style="display:flex;flex-direction:column;gap:5px;margin-bottom:10px;">'
                            +   '<div style="display:flex;align-items:center;gap:8px;padding:5px 10px;background:#f0fdf4;border-radius:7px;border-left:3px solid #16a34a;">'
                            +     '<span style="width:8px;height:8px;border-radius:50%;background:#16a34a;flex-shrink:0;"></span>'
                            +     '<span><b style="color:#16a34a;">Verde</b> &mdash; Más de 4 horas (tiempo suficiente)</span>'
                            +   '</div>'
                            +   '<div style="display:flex;align-items:center;gap:8px;padding:5px 10px;background:#fffbeb;border-radius:7px;border-left:3px solid #d97706;">'
                            +     '<span style="width:8px;height:8px;border-radius:50%;background:#d97706;flex-shrink:0;"></span>'
                            +     '<span><b style="color:#d97706;">Amarillo</b> &mdash; Entre 1 y 4 horas (poco tiempo)</span>'
                            +   '</div>'
                            +   '<div style="display:flex;align-items:center;gap:8px;padding:5px 10px;background:#fef2f2;border-radius:7px;border-left:3px solid #dc2626;">'
                            +     '<span style="width:8px;height:8px;border-radius:50%;background:#dc2626;flex-shrink:0;"></span>'
                            +     '<span><b style="color:#dc2626;">Rojo</b> &mdash; Menos de 1 hora (urgente)</span>'
                            +   '</div>'
                            + '</div>'
                            + '<div style="display:flex;align-items:flex-start;gap:8px;padding:7px 10px;background:#fffbeb;border-radius:7px;border:1px solid #fde68a;">'
                            +   '<i class="ti ti-calendar-off" style="color:#b45309;font-size:1rem;flex-shrink:0;margin-top:1px;"></i>'
                            +   '<span style="color:#92400e;font-size:0.78rem;"><b>Solo días hábiles.</b> Sábados, domingos y feriados registrados no generan asistencia automática.</span>'
                            + '</div>'
                            + '</div>',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#7c3aed',
                        width: 400
                    });
                }
                </script>

            </div>
        </div>

    </div><!-- /Fila 4 -->

    <!-- ===== FILA 5: Sistema Inteligente de Notificaciones (Fase 4) ===== -->
    <div class="cfg-grid">
        <div class="cfg-card cg-12" style="border-top:4px solid #3b82f6;">
            <?php
            $notifStats = $data['notifAnalytics'] ?? [];
            $notifActivo = !empty($notifStats['global_activo']);
            $totalEnviadas = $notifStats['total_enviadas'] ?? 0;
            $tasaLectura = $notifStats['tasa_lectura'] ?? 0;
            $tasaClicks = $notifStats['tasa_clicks'] ?? 0;
            $totalLeidas = $notifStats['total_leidas'] ?? 0;
            $totalClicks = $notifStats['total_clickeadas'] ?? 0;
            $notifUsers = $data['notifUsuarios'] ?? [];
            $ultimasNotifs = $notifStats['ultimas'] ?? [];
            ?>
            <div class="cfg-card-header" style="flex-wrap:wrap;gap:14px;border-bottom:1px solid #f1f5f9;padding-bottom:18px;">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#eff6ff;">
                        <i class="ti ti-bell-ringing" style="font-size:1.25rem;color:#2563eb;"></i>
                    </div>
                    <div>
                        <div style="font-size:1.1rem;font-weight:800;color:#1e293b;">Sistema Inteligente de Notificaciones</div>
                        <div style="font-size:0.76rem;color:#64748b;font-weight:500;margin-top:2px;">
                            Control administrativo global, preferencias por usuario y métricas de lectura y clics
                        </div>
                    </div>
                </div>

                <!-- Controles y Toggle Global -->
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <div style="display:flex;align-items:center;gap:10px;background:#f8fafc;padding:6px 14px;border-radius:12px;border:1px solid #e2e8f0;">
                        <span style="font-size:0.8rem;font-weight:700;color:#334155;">Switch Global:</span>
                        <label class="cfg-toggle-wrap" style="margin:0;">
                            <input type="checkbox" id="notifGlobalToggle" <?= $notifActivo ? 'checked' : '' ?>>
                            <span class="cfg-toggle-track"></span>
                            <span class="cfg-toggle-dot"></span>
                        </label>
                        <span id="notifGlobalBadge" class="cfg-badge" style="background:<?= $notifActivo ? '#dcfce7' : '#fee2e2' ?>;color:<?= $notifActivo ? '#16a34a' : '#dc2626' ?>;font-weight:700;">
                            <?= $notifActivo ? 'Activas' : 'Pausadas' ?>
                        </span>
                    </div>

                    <button type="button" onclick="abrirModalNotifTrazabilidad()" style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:8px 14px;border-radius:10px;font-size:0.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .2s;">
                        <i class="ti ti-chart-dots-3"></i> Trazabilidad de Clics
                    </button>

                    <button type="button" onclick="abrirModalNotifUsuarios()" style="background:#f8fafc;color:#475569;border:1px solid #cbd5e1;padding:8px 14px;border-radius:10px;font-size:0.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .2s;">
                        <i class="ti ti-users"></i> Gestión por Usuario
                    </button>
                </div>
            </div>

            <!-- Bento Sub-Grid KPIs -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin-top:20px;">
                <div style="background:#f8fafc;border-radius:14px;padding:16px;border:1px solid #e2e8f0;display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                        <i class="ti ti-send"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:#1e293b;"><?= number_format($totalEnviadas) ?></div>
                        <div style="font-size:0.74rem;color:#64748b;font-weight:600;text-transform:uppercase;">Total Enviadas</div>
                    </div>
                </div>

                <div style="background:#f8fafc;border-radius:14px;padding:16px;border:1px solid #e2e8f0;display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:12px;background:#f0fdf4;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                        <i class="ti ti-eye-check"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:#16a34a;"><?= $tasaLectura ?>%</div>
                        <div style="font-size:0.74rem;color:#64748b;font-weight:600;text-transform:uppercase;">Lectura (<?= number_format($totalLeidas) ?> leídas)</div>
                    </div>
                </div>

                <div style="background:#f8fafc;border-radius:14px;padding:16px;border:1px solid #e2e8f0;display:flex;align-items:center;gap:14px;">
                    <div style="width:44px;height:44px;border-radius:12px;background:#faf5ff;color:#9333ea;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                        <i class="ti ti-pointer"></i>
                    </div>
                    <div>
                        <div style="font-size:1.4rem;font-weight:800;color:#9333ea;"><?= $tasaClicks ?>%</div>
                        <div style="font-size:0.74rem;color:#64748b;font-weight:600;text-transform:uppercase;">Clics / Interacción (<?= number_format($totalClicks) ?> clics)</div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /Fila 5 -->

    <!-- ===== FILA 6: Personalización Institucional y Branding (Fase 5) ===== -->
    <div class="cfg-grid">

        <!-- CARD: Paleta de Colores y Modo Visual -->
        <div class="cfg-card cg-6" style="border-top:4px solid var(--color-primary, #162660);">
            <div class="cfg-card-header" style="flex-wrap:wrap;gap:12px;">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#f1f5f9;">
                        <i class="ti ti-palette" style="font-size:1.2rem;color:var(--color-primary, #162660);"></i>
                    </div>
                    <div>
                        <div style="font-weight:800;font-size:1.05rem;color:#1e293b;">Paleta de Colores y Tema</div>
                        <div style="font-size:0.75rem;color:#64748b;font-weight:500;margin-top:2px;">
                            Personaliza la identidad visual y modo de contraste del sistema
                        </div>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:8px;">
                    <button type="button" id="cfgBtnToggleTheme" onclick="alternarTemaVisual()"
                        style="background:#f8fafc;border:1px solid #cbd5e1;padding:6px 12px;border-radius:10px;font-size:0.78rem;font-weight:700;color:#334155;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .2s;">
                        <i class="ti ti-moon" id="cfgThemeIcon"></i>
                        <span id="cfgThemeLabel">Modo Oscuro</span>
                    </button>
                </div>
            </div>

            <form method="POST" action="<?= URLROOT ?>/configuracion" id="formBrandingTema" style="margin-top:14px;">
                <input type="hidden" name="accion" value="guardar_tema">
                <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">

                <!-- Selectores de color -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                    <!-- Color Primario -->
                    <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;">
                        <label class="cfg-field-label" style="display:flex;justify-content:space-between;align-items:center;">
                            <span>Color Primario (Barras / Headers)</span>
                            <span id="hexPrimarioDisplay" style="font-family:monospace;font-weight:700;color:#2563eb;"><?= htmlspecialchars($data['colorPrimario'] ?? '#162660') ?></span>
                        </label>
                        <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
                            <input type="color" id="pickerPrimario" value="<?= htmlspecialchars($data['colorPrimario'] ?? '#162660') ?>"
                                oninput="sincronizarColor('primario', this.value)"
                                style="width:44px;height:40px;padding:2px;border:1px solid #cbd5e1;border-radius:8px;cursor:pointer;background:white;">
                            <input type="text" name="color_primario" id="inputPrimario" value="<?= htmlspecialchars($data['colorPrimario'] ?? '#162660') ?>"
                                maxlength="7" class="cfg-input" style="flex:1;font-family:monospace;font-weight:700;"
                                oninput="sincronizarColor('primario', this.value)">
                        </div>
                    </div>

                    <!-- Color de Acento -->
                    <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid #e2e8f0;">
                        <label class="cfg-field-label" style="display:flex;justify-content:space-between;align-items:center;">
                            <span>Color de Acento (Botones / KPIs)</span>
                            <span id="hexAcentoDisplay" style="font-family:monospace;font-weight:700;color:#2563eb;"><?= htmlspecialchars($data['colorAcento'] ?? '#2563eb') ?></span>
                        </label>
                        <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
                            <input type="color" id="pickerAcento" value="<?= htmlspecialchars($data['colorAcento'] ?? '#2563eb') ?>"
                                oninput="sincronizarColor('acento', this.value)"
                                style="width:44px;height:40px;padding:2px;border:1px solid #cbd5e1;border-radius:8px;cursor:pointer;background:white;">
                            <input type="text" name="color_acento" id="inputAcento" value="<?= htmlspecialchars($data['colorAcento'] ?? '#2563eb') ?>"
                                maxlength="7" class="cfg-input" style="flex:1;font-family:monospace;font-weight:700;"
                                oninput="sincronizarColor('acento', this.value)">
                        </div>
                    </div>
                </div>

                <!-- Presets Rápidos -->
                <div style="margin-bottom:18px;">
                    <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:8px;letter-spacing:0.5px;">
                        Paletas Predefinidas Recomendadas
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" onclick="aplicarPresetColor('#162660', '#2563eb')" class="preset-theme-btn"
                            style="background:#f8fafc;border:1.5px solid #e2e8f0;padding:6px 12px;border-radius:9px;font-size:0.76rem;font-weight:700;color:#1e293b;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s;">
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#162660;"></span>
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#2563eb;margin-left:-4px;"></span>
                            Azul ISP Institucional
                        </button>
                        <button type="button" onclick="aplicarPresetColor('#065f46', '#059669')" class="preset-theme-btn"
                            style="background:#f8fafc;border:1.5px solid #e2e8f0;padding:6px 12px;border-radius:9px;font-size:0.76rem;font-weight:700;color:#1e293b;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s;">
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#065f46;"></span>
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#059669;margin-left:-4px;"></span>
                            Salud Pública Esmeralda
                        </button>
                        <button type="button" onclick="aplicarPresetColor('#312e81', '#6366f1')" class="preset-theme-btn"
                            style="background:#f8fafc;border:1.5px solid #e2e8f0;padding:6px 12px;border-radius:9px;font-size:0.76rem;font-weight:700;color:#1e293b;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s;">
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#312e81;"></span>
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#6366f1;margin-left:-4px;"></span>
                            Índigo Ejecutivo
                        </button>
                        <button type="button" onclick="aplicarPresetColor('#0f172a', '#0284c7')" class="preset-theme-btn"
                            style="background:#f8fafc;border:1.5px solid #e2e8f0;padding:6px 12px;border-radius:9px;font-size:0.76rem;font-weight:700;color:#1e293b;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s;">
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#0f172a;"></span>
                            <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#0284c7;margin-left:-4px;"></span>
                            Pizarra / Zafiro
                        </button>
                    </div>
                </div>

                <!-- Previsualizador Dinámico de Muestra -->
                <div id="previewCardBrand"
                    style="background:linear-gradient(135deg, <?= htmlspecialchars($data['colorPrimario'] ?? '#162660') ?>, <?= htmlspecialchars($data['colorAcento'] ?? '#2563eb') ?>);padding:14px 18px;border-radius:12px;color:white;display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;box-shadow:0 4px 14px rgba(0,0,0,0.12);transition:all .2s;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;">
                            <i class="ti ti-sparkles" style="font-size:1.1rem;color:white;"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:0.88rem;">Vista Previa de Interfaz</div>
                            <div style="font-size:0.72rem;opacity:0.85;">Los elementos de la barra y botones adoptarán este estilo</div>
                        </div>
                    </div>
                    <span id="previewBrandBadge" style="background:rgba(255,255,255,0.25);padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:700;backdrop-filter:blur(4px);">
                        Muestra Activa
                    </span>
                </div>

                <!-- Acciones -->
                <div style="display:flex;align-items:center;gap:10px;">
                    <button type="submit" class="cfg-btn-primary"
                        style="padding:10px 18px;display:flex;align-items:center;gap:7px;background:linear-gradient(135deg, #1e3a8a, #2563eb);">
                        <i class="ti ti-device-floppy"></i> Guardar Paleta
                    </button>
                    <button type="button" onclick="confirmarRestablecerTema()"
                        style="padding:10px 16px;border:1.5px solid #e2e8f0;background:white;color:#64748b;border-radius:10px;font-size:0.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s;">
                        <i class="ti ti-rotate"></i> Restablecer Valores
                    </button>
                </div>
            </form>

            <!-- Form oculto para restablecer -->
            <form id="formRestablecerTema" method="POST" action="<?= URLROOT ?>/configuracion" style="display:none;">
                <input type="hidden" name="accion" value="restablecer_tema">
                <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">
            </form>
        </div>

        <!-- CARD: Logos y Emblemas Institucionales -->
        <div class="cfg-card cg-6" style="border-top:4px solid #2563eb;">
            <div class="cfg-card-header" style="flex-wrap:wrap;gap:12px;">
                <div class="cfg-card-title">
                    <div class="cfg-icon-box" style="background:#eff6ff;">
                        <i class="ti ti-photo-star" style="font-size:1.2rem;color:#2563eb;"></i>
                    </div>
                    <div>
                        <div style="font-weight:800;font-size:1.05rem;color:#1e293b;">Logos y Emblemas Institucionales</div>
                        <div style="font-size:0.75rem;color:#64748b;font-weight:500;margin-top:2px;">
                            Actualiza los logos del sistema y cintillos de reportes PDF sin editar código
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Emblemas -->
            <div style="display:flex;flex-direction:column;gap:12px;margin-top:14px;">

                <!-- 1. Logo Principal -->
                <div style="display:flex;align-items:center;gap:14px;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;transition:all .15s;">
                    <div style="width:68px;height:52px;background:white;border:1px solid #cbd5e1;border-radius:8px;padding:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        <img src="<?= URLROOT ?>/img/logo.png?v=<?= time() ?>" alt="Logo Principal" style="max-width:100%;max-height:100%;object-fit:contain;">
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:800;font-size:0.88rem;color:#1e293b;">Logo Principal SGP / ISP</div>
                        <div style="font-size:0.74rem;color:#64748b;margin-top:2px;">Visible en la cabecera, sidebar y pantalla de inicio de sesión</div>
                        <div style="font-size:0.68rem;color:#94a3b8;margin-top:2px;">Archivo: <code>logo.png</code> (PNG con fondo transparente recomendado)</div>
                    </div>
                    <button type="button" onclick="abrirModalLogo('logo_principal')"
                        style="padding:8px 14px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:9px;font-size:0.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;flex-shrink:0;transition:all .15s;"
                        onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                        <i class="ti ti-upload"></i> Cambiar
                    </button>
                </div>

                <!-- 2. Cintillo Oficial PDF -->
                <div style="display:flex;align-items:center;gap:14px;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;transition:all .15s;">
                    <div style="width:68px;height:52px;background:white;border:1px solid #cbd5e1;border-radius:8px;padding:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        <img src="<?= URLROOT ?>/img/cintillo-salud.png?v=<?= time() ?>" alt="Cintillo Oficial" style="max-width:100%;max-height:100%;object-fit:contain;">
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:800;font-size:0.88rem;color:#1e293b;">Cintillo Oficial para Reportes</div>
                        <div style="font-size:0.74rem;color:#64748b;margin-top:2px;">Encabezado ministerial en reportes, constancias y planillas PDF</div>
                        <div style="font-size:0.68rem;color:#94a3b8;margin-top:2px;">Archivo: <code>cintillo-salud.png</code> (Proporción panorámica)</div>
                    </div>
                    <button type="button" onclick="abrirModalLogo('cintillo')"
                        style="padding:8px 14px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:9px;font-size:0.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;flex-shrink:0;transition:all .15s;"
                        onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                        <i class="ti ti-upload"></i> Cambiar
                    </button>
                </div>

                <!-- 3. Logo Gobernación -->
                <div style="display:flex;align-items:center;gap:14px;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;transition:all .15s;">
                    <div style="width:68px;height:52px;background:white;border:1px solid #cbd5e1;border-radius:8px;padding:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        <img src="<?= URLROOT ?>/img/gobe.png?v=<?= time() ?>" alt="Gobernación" style="max-width:100%;max-height:100%;object-fit:contain;">
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:800;font-size:0.88rem;color:#1e293b;">Logo de la Gobernación de Bolívar</div>
                        <div style="font-size:0.74rem;color:#64748b;margin-top:2px;">Emblema gubernamental del estado aplicado en documentos institucionales</div>
                        <div style="font-size:0.68rem;color:#94a3b8;margin-top:2px;">Archivo: <code>gobe.png</code> (PNG transparente)</div>
                    </div>
                    <button type="button" onclick="abrirModalLogo('gobernacion')"
                        style="padding:8px 14px;background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;border-radius:9px;font-size:0.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;flex-shrink:0;transition:all .15s;"
                        onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                        <i class="ti ti-upload"></i> Cambiar
                    </button>
                </div>

            </div>
        </div>

    </div><!-- /Fila 6 -->

</div><!-- /container -->

<!-- ═══════════════════════════════════════════════════════
     MODAL: Nuevo Feriado (SGP Premium)
     ═══════════════════════════════════════════════════════ -->
<div id="modalFeriado"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(5px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:22px;width:100%;max-width:480px;margin:16px;box-shadow:0 28px 70px rgba(0,0,0,.22);overflow:hidden;animation:feriadoSlideIn .25s cubic-bezier(.34,1.56,.64,1);">

        <!-- Header: azul institucional -->
        <div style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 55%,#2563eb 100%);padding:22px 26px;display:flex;align-items:center;justify-content:space-between;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-30px;right:-30px;width:130px;height:130px;background:rgba(255,255,255,.05);border-radius:50%;pointer-events:none;"></div>
            <div style="display:flex;align-items:center;gap:13px;z-index:1;">
                <div style="width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ti ti-calendar-plus" style="color:white;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:800;font-size:1.02rem;letter-spacing:-.2px;">Registrar Día Feriado</div>
                    <div style="color:rgba(255,255,255,.65);font-size:0.76rem;margin-top:2px;"><i class="ti ti-shield-check" style="font-size:.7rem;"></i> Excluido del auto-fill de asistencias</div>
                </div>
            </div>
            <button onclick="cerrarModalFeriado()" type="button"
                style="z-index:1;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:.95rem;display:flex;align-items:center;justify-content:center;transition:background .15s;flex-shrink:0;"
                onmouseover="this.style.background='rgba(255,255,255,.22)'"
                onmouseout="this.style.background='rgba(255,255,255,.12)'">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Preview de fecha (aparece dinámicamente) -->
        <div id="feriadoPreview" style="display:none;background:#f5f3ff;border-bottom:1px solid #ddd6fe;padding:11px 26px;align-items:center;gap:12px;">
            <div id="fpDateBlock" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);border-radius:9px;width:42px;text-align:center;padding:6px 0;flex-shrink:0;box-shadow:0 3px 8px rgba(124,58,237,.25);">
                <div id="fpDia"  style="font-size:1rem;font-weight:900;color:white;line-height:1;">—</div>
                <div id="fpMes"  style="font-size:.55rem;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.5px;">—</div>
            </div>
            <div>
                <div id="fpNombre" style="font-size:.83rem;font-weight:700;color:#1e293b;">Selecciona una fecha…</div>
                <div id="fpDiaNom" style="font-size:.7rem;color:#94a3b8;margin-top:2px;"></div>
            </div>
        </div>

        <!-- Body -->
        <form method="POST" action="<?= URLROOT ?>/configuracion" style="padding:22px 26px;">
            <input type="hidden" name="accion" value="agregar_feriado">
            <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">

            <!-- Fecha + Tipo -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label class="cfg-field-label">Fecha *</label>
                    <input type="date" name="fecha" id="inputFeriadoFecha" class="cfg-input" required style="width:100%;" oninput="actualizarPreviewFeriado()">
                </div>
                <div>
                    <label class="cfg-field-label">Tipo *</label>
                    <select name="tipo" id="inputFeriadoTipo" class="cfg-input" style="width:100%;cursor:pointer;" onchange="actualizarPreviewFeriado()">
                        <option value="Nacional">🟣 Nacional</option>
                        <option value="Regional">🟢 Regional</option>
                        <option value="Institucional">🔵 Institucional</option>
                    </select>
                </div>
            </div>

            <!-- Nombre -->
            <div style="margin-bottom:18px;">
                <label class="cfg-field-label">Nombre del feriado *</label>
                <input type="text" name="nombre" id="inputFeriadoNombre" class="cfg-input" placeholder="Ej: Día de la Independencia" required style="width:100%;" oninput="actualizarPreviewFeriado()">
            </div>

            <!-- Nota informativa -->
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:10px 13px;display:flex;align-items:flex-start;gap:9px;margin-bottom:20px;">
                <i class="ti ti-info-circle" style="color:#2563eb;font-size:1rem;flex-shrink:0;margin-top:2px;"></i>
                <span style="font-size:0.76rem;color:#1e40af;font-weight:500;line-height:1.5;">
                    El sistema no generará ausencias automáticas en días feriados. Los feriados pasados no se pueden eliminar una vez registrados.
                </span>
            </div>

            <!-- Acciones -->
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="cerrarModalFeriado()"
                    style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:11px;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:.88rem;font-family:inherit;transition:all .15s;"
                    onmouseover="this.style.background='#f8fafc';this.style.borderColor='#cbd5e1'"
                    onmouseout="this.style.background='white';this.style.borderColor='#e2e8f0'">
                    Cancelar
                </button>
                <button type="submit"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,#172554,#2563eb);border:none;border-radius:11px;color:white;font-weight:700;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(37,99,235,.3);font-family:inherit;transition:all .15s;"
                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 18px rgba(37,99,235,.4)'"
                    onmouseout="this.style.transform='';this.style.boxShadow='0 4px 14px rgba(37,99,235,.3)'">
                    <i class="ti ti-circle-check"></i> Registrar Feriado
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes feriadoSlideIn {
    from { opacity:0; transform:translateY(24px) scale(.97); }
    to   { opacity:1; transform:translateY(0)    scale(1);   }
}
</style>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Editar Feriado (nombre y tipo — fecha bloqueada)
     ═══════════════════════════════════════════════════════ -->
<div id="modalEditarFeriado"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(5px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:22px;width:100%;max-width:440px;margin:16px;box-shadow:0 28px 70px rgba(0,0,0,.22);overflow:hidden;animation:feriadoSlideIn .25s cubic-bezier(.34,1.56,.64,1);">

        <!-- Header -->
        <div style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 55%,#2563eb 100%);padding:22px 26px;display:flex;align-items:center;justify-content:space-between;position:relative;overflow:hidden;">
            <div style="position:absolute;top:-30px;right:-30px;width:130px;height:130px;background:rgba(255,255,255,.05);border-radius:50%;pointer-events:none;"></div>
            <div style="display:flex;align-items:center;gap:13px;z-index:1;">
                <div style="width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ti ti-pencil" style="color:white;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:800;font-size:1.02rem;">Editar Feriado</div>
                    <div style="color:rgba(255,255,255,.65);font-size:0.76rem;margin-top:2px;"><i class="ti ti-lock" style="font-size:.7rem;"></i> La fecha no puede modificarse</div>
                </div>
            </div>
            <button onclick="cerrarEditarFeriado()" type="button"
                style="z-index:1;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:.95rem;display:flex;align-items:center;justify-content:center;transition:background .15s;flex-shrink:0;"
                onmouseover="this.style.background='rgba(255,255,255,.22)'"
                onmouseout="this.style.background='rgba(255,255,255,.12)'">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <form method="POST" action="<?= URLROOT ?>/configuracion" style="padding:22px 26px;">
            <input type="hidden" name="accion" value="editar_feriado">
            <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">
            <input type="hidden" name="id" id="editFeriadoId">

            <!-- Fecha (solo lectura — visual) -->
            <div style="margin-bottom:16px;">
                <label class="cfg-field-label">Fecha <span style="color:#94a3b8;font-size:.68rem;font-weight:500;text-transform:none;letter-spacing:0;">(no editable)</span></label>
                <div id="editFeriadoFechaDisplay"
                    style="display:flex;align-items:center;gap:10px;padding:9px 13px;background:#f8fafc;border:2px solid #f1f5f9;border-radius:10px;">
                    <div id="editFeriadoDateBlock"
                        style="background:#e2e8f0;border-radius:8px;width:38px;text-align:center;padding:5px 0;flex-shrink:0;">
                        <div id="editFDia" style="font-size:.95rem;font-weight:900;color:#94a3b8;line-height:1;">—</div>
                        <div id="editFMes" style="font-size:.5rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;">—</div>
                    </div>
                    <div>
                        <div id="editFNomFecha" style="font-size:.83rem;font-weight:600;color:#64748b;">—</div>
                        <div style="display:flex;align-items:center;gap:4px;margin-top:2px;">
                            <i class="ti ti-lock" style="font-size:.68rem;color:#cbd5e1;"></i>
                            <span style="font-size:.68rem;color:#cbd5e1;">Fecha bloqueada</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nombre -->
            <div style="margin-bottom:16px;">
                <label class="cfg-field-label">Nombre del feriado *</label>
                <input type="text" name="nombre" id="editFeriadoNombre" class="cfg-input" required style="width:100%;" placeholder="Ej: Día de la Independencia">
            </div>

            <!-- Tipo -->
            <div style="margin-bottom:20px;">
                <label class="cfg-field-label">Tipo *</label>
                <select name="tipo" id="editFeriadoTipo" class="cfg-input" style="width:100%;cursor:pointer;">
                    <option value="Nacional">🟣 Nacional</option>
                    <option value="Regional">🟢 Regional</option>
                    <option value="Institucional">🔵 Institucional</option>
                </select>
            </div>

            <!-- Acciones -->
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="cerrarEditarFeriado()"
                    style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:11px;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:.88rem;font-family:inherit;transition:all .15s;"
                    onmouseover="this.style.background='#f8fafc';this.style.borderColor='#cbd5e1'"
                    onmouseout="this.style.background='white';this.style.borderColor='#e2e8f0'">
                    Cancelar
                </button>
                <button type="submit"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,#172554,#2563eb);border:none;border-radius:11px;color:white;font-weight:700;cursor:pointer;font-size:.88rem;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(37,99,235,.3);font-family:inherit;transition:all .15s;"
                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 18px rgba(37,99,235,.4)'"
                    onmouseout="this.style.transform='';this.style.boxShadow='0 4px 14px rgba(37,99,235,.3)'">
                    <i class="ti ti-device-floppy"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── Modal Feriado ──────────────────────────────────────
    // [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
    var _mesesEs  = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    var _diasEs   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    var _tipoPal  = {
        'Nacional':      { gradient:'linear-gradient(135deg,#7c3aed,#a78bfa)', bg:'#f5f3ff' },
        'Regional':      { gradient:'linear-gradient(135deg,#16a34a,#22c55e)', bg:'#f0fdf4' },
        'Institucional': { gradient:'linear-gradient(135deg,#1e3a8a,#2563eb)', bg:'#eff6ff' },
    };

    function abrirModalFeriado() {
        const m = document.getElementById('modalFeriado');
        // Reset form
        m.querySelector('input[name="fecha"]').value  = '';
        m.querySelector('input[name="nombre"]').value = '';
        m.querySelector('select[name="tipo"]').value  = 'Nacional';
        document.getElementById('feriadoPreview').style.display = 'none';
        m.style.display = 'flex';
        setTimeout(() => m.querySelector('input[name="fecha"]').focus(), 100);
    }
    function cerrarModalFeriado() {
        document.getElementById('modalFeriado').style.display = 'none';
    }
    document.getElementById('modalFeriado').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalFeriado();
    });

    function actualizarPreviewFeriado() {
        const fechaVal  = document.getElementById('inputFeriadoFecha').value;
        const nombreVal = document.getElementById('inputFeriadoNombre').value.trim();
        const tipoVal   = document.getElementById('inputFeriadoTipo').value;
        const preview   = document.getElementById('feriadoPreview');
        if (!fechaVal) { preview.style.display = 'none'; return; }

        const d    = new Date(fechaVal + 'T00:00:00');
        const pal  = _tipoPal[tipoVal] || _tipoPal['Nacional'];
        const dia  = String(d.getDate()).padStart(2,'0');
        const mes  = _mesesEs[d.getMonth() + 1];
        const diaN = _diasEs[d.getDay()];

        document.getElementById('fpDia').textContent    = dia;
        document.getElementById('fpMes').textContent    = mes.slice(0,3).toUpperCase();
        document.getElementById('fpDateBlock').style.background = pal.gradient;
        document.getElementById('fpNombre').textContent = nombreVal || 'Sin nombre aún…';
        document.getElementById('fpDiaNom').textContent = diaN + ' · ' + dia + ' de ' + mes + ' ' + d.getFullYear();
        preview.style.background = pal.bg;
        preview.style.borderBottomColor = tipoVal === 'Nacional' ? '#ddd6fe' : tipoVal === 'Regional' ? '#bbf7d0' : '#bfdbfe';
        preview.style.display    = 'flex';
    }

    // ── Modal Editar Feriado ───────────────────────────────
    function abrirEditarFeriado(id, nombre, tipo, fecha) {
        document.getElementById('editFeriadoId').value     = id;
        document.getElementById('editFeriadoNombre').value = nombre;
        document.getElementById('editFeriadoTipo').value   = tipo;

        // Construir display de fecha (bloqueada)
        const d    = new Date(fecha + 'T00:00:00');
        const mes  = _mesesEs[d.getMonth() + 1];
        const diaN = _diasEs[d.getDay()];
        const diaNum = String(d.getDate()).padStart(2,'0');

        document.getElementById('editFDia').textContent     = diaNum;
        document.getElementById('editFMes').textContent     = mes.slice(0,3).toUpperCase();
        document.getElementById('editFNomFecha').textContent = diaN + ' ' + diaNum + ' de ' + mes + ' ' + d.getFullYear();

        document.getElementById('modalEditarFeriado').style.display = 'flex';
        setTimeout(() => document.getElementById('editFeriadoNombre').select(), 100);
    }
    function cerrarEditarFeriado() {
        document.getElementById('modalEditarFeriado').style.display = 'none';
    }
    document.getElementById('modalEditarFeriado').addEventListener('click', function(e) {
        if (e.target === this) cerrarEditarFeriado();
    });

    // ── Confirmar eliminar feriado ─────────────────────────
    function confirmarEliminarFeriado(btn, nombre) {
        Swal.fire({
            icon: 'warning',
            title: '¿Eliminar feriado?',
            html: '<strong>' + nombre + '</strong><br><small style="color:#64748b;">Esta acción no se puede deshacer.</small>',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="ti ti-trash" style="margin-right:4px;"></i>Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) { btn.closest('form').submit(); }
        });
    }

    // ── Verificar conexión BD ─────────────────────────────
    function verificarBD(btn) {
        const span = btn.querySelector('#txt-btn-bd');
        const badge = document.getElementById('bdg-estado-bd');
        const sub = document.getElementById('txt-bd-sub');
        span.textContent = 'Verificando…';
        btn.disabled = true;

        fetch('<?= URLROOT ?>/configuracion/verificarConexionBD', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                badge.innerHTML = '<i class="ti ti-circle-filled" style="font-size:.55rem;margin-right:2px;color:#22c55e;"></i>Online';
                badge.style.background = '#dcfce7'; badge.style.color = '#16a34a';
                sub.textContent = 'MySQL ' + (data.version || '') + ' · ' + (data.latencia_ms || 0) + ' ms';
                NotificationService.success('Conexión exitosa · ' + (data.latencia_ms || 0) + ' ms');
            } else {
                badge.innerHTML = '<i class="ti ti-circle-filled" style="font-size:.55rem;margin-right:2px;color:#ef4444;"></i>Error';
                badge.style.background = '#fee2e2'; badge.style.color = '#dc2626';
                NotificationService.error('Error de conexión a la base de datos');
            }
        })
        .catch(() => { NotificationService.error('No se pudo contactar el servidor'); })
        .finally(() => { span.textContent = 'Verificar Conexión'; btn.disabled = false; });
    }

    // ── Sincronizar Feriados vía API Nager.Date ────────────
    function sincronizarFeriados(anioParam) {
        const anio = anioParam || <?= date('Y') ?>;
        const btn  = document.getElementById('btnSyncFeriados');
        const icon = document.getElementById('iconSyncFeriados');

        // Estado: cargando
        btn.disabled = true;
        icon.className = 'ti ti-loader-2';
        icon.style.animation = 'spin 1s linear infinite';

        fetch('<?= URLROOT ?>/configuracion/sincronizarFeriados', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'anio=' + encodeURIComponent(anio)
                + '&csrf_token=<?= Session::generateCsrfToken() ?>'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const insertados = data.insertados ?? 0;
                const yaExistian = data.ya_existian ?? 0;
                const totalApi   = data.total_api   ?? 0;

                let html = '<div style="text-align:left;font-size:0.88rem;line-height:1.8;">';
                html += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;">'
                      + '<span style="color:#64748b;">Consultados en la API</span>'
                      + '<strong>' + totalApi + '</strong></div>';
                html += '<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;">'
                      + '<span style="color:#059669;">✅ Nuevos insertados</span>'
                      + '<strong style="color:#059669;">' + insertados + '</strong></div>';
                html += '<div style="display:flex;justify-content:space-between;padding:6px 0;">'
                      + '<span style="color:#94a3b8;">⏭ Ya existían (omitidos)</span>'
                      + '<strong style="color:#94a3b8;">' + yaExistian + '</strong></div>';
                html += '</div>';
                if (data.nota) {
                    html += '<div style="margin-top:10px;font-size:0.78rem;background:#fef9c3;border-radius:8px;padding:8px 12px;color:#854d0e;text-align:left;">'
                          + '⚠️ ' + data.nota + '</div>';
                }

                Swal.fire({
                    icon: insertados > 0 ? 'success' : 'info',
                    title: insertados > 0 ? '¡Feriados Sincronizados!' : 'Todo al día',
                    html: html,
                    confirmButtonColor: '#7c3aed',
                    confirmButtonText: insertados > 0 ? 'Ver cambios' : 'Entendido'
                }).then(r => { if (r.isConfirmed && insertados > 0) location.reload(); });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Sincronización',
                    text: data.message || 'No se pudo conectar con la API de feriados.',
                    footer: '<small>Verifica que el equipo tenga conexión a internet.</small>',
                    confirmButtonColor: '#dc2626'
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Sin conexión',
                text: 'No se pudo contactar la API. Verifica la conexión a internet.',
                confirmButtonColor: '#dc2626'
            });
        })
        .finally(() => {
            btn.disabled = false;
            icon.className = 'ti ti-refresh';
            icon.style.animation = '';
        });
    }

    // ── Limpiar sesiones expiradas ─────────────────────────
    function limpiarSesiones(btn) {
        Swal.fire({
            icon: 'question',
            title: 'Limpiar sesiones',
            text: '¿Eliminar todos los tokens de sesión expirados del sistema?',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            btn.disabled = true;
            btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite;"></i> Limpiando…';

            fetch('<?= URLROOT ?>/configuracion/limpiarSesiones', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    NotificationService.success(data.message || 'Sesiones limpiadas correctamente');
                } else {
                    NotificationService.error(data.message || 'Error al limpiar sesiones');
                }
            })
            .catch(() => { NotificationService.error('Error de conexión'); })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-trash-x"></i> <span>Limpiar Sesiones</span>';
            });
        });
    }

    // ── Purgar bitácora ────────────────────────────────────
    function purgarBitacora(btn) {
        const meses = document.getElementById('sel-purga-meses').value;
        Swal.fire({
            icon: 'warning',
            title: '¿Purgar bitácora?',
            html: `<strong>Se eliminarán logs de más de ${meses} meses.</strong><br><small style="color:#64748b;">Esta acción es irreversible.</small>`,
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="ti ti-flame" style="margin-right:4px;"></i>Purgar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            btn.disabled = true;
            btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite;"></i>';

            fetch('<?= URLROOT ?>/configuracion/purgarBitacora', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'meses=' + meses + '&csrf_token=<?= $_SESSION["csrf_token"] ?? "" ?>'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    NotificationService.success(data.message || 'Bitácora purgada correctamente');
                } else {
                    NotificationService.error(data.message || 'Error al purgar bitácora');
                }
            })
            .catch(() => { NotificationService.error('Error de conexión'); })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-flame"></i> Purgar';
            });
        });
    }

    // ── Spin animation ─────────────────────────────────────
    const styleEl = document.createElement('style');
    styleEl.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
    document.head.appendChild(styleEl);
</script>

<script>
    // ── Flash messages ──────────────────────────────────────────
    <?php if ($flashSuccess): ?>
        NotificationService.success('<?= addslashes($flashSuccess) ?>');
    <?php endif; ?>
    <?php if ($flashError): ?>
        Swal.fire({
            icon: 'warning',
            title: 'Aviso',
            text: '<?= addslashes($flashError) ?>',
            confirmButtonColor: '#162660',
            confirmButtonText: 'Entendido'
        });
    <?php endif; ?>

    // ── Confirmar eliminación ───────────────────────────────────
    function confirmarEliminar(btn, tipo, nombre) {
        Swal.fire({
            icon: 'warning',
            title: '¿Eliminar ' + tipo + '?',
            html: '<strong>' + nombre + '</strong><br><small style="color:#64748b;">Esta acción no se puede deshacer fácilmente.</small>',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="ti ti-trash" style="margin-right:4px;"></i>Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (result.isConfirmed) { btn.closest('form').submit(); }
        });
    }

    // ── Kiosco toggle ───────────────────────────────────────────
    (function () {
        const toggle = document.getElementById('kioscoToggle');
        const badge = document.getElementById('kioscoStatusBadge');
        const icon = document.getElementById('kioscoStatusIcon');
        const text = document.getElementById('kioscoStatusText');
        const sub = document.getElementById('kioscoStatusSub');
        const kpiLbl = document.getElementById('kpi-kiosco-label');
        const kpiCard = document.getElementById('kpi-kiosco-card');
        const kpiIcon = document.getElementById('kpi-kiosco-icon-box');

        function applyState(active) {
            if (active) {
                badge.style.background = '#d1fae5';
                icon.className = 'ti ti-circle-check';
                icon.style.color = '#059669';
                text.style.color = '#065f46';
                text.textContent = 'Kiosco activo y operativo';
                sub.textContent = 'Los pasantes pueden registrar asistencia';
                if (kpiLbl) { kpiLbl.textContent = 'Activo'; kpiLbl.style.color = '#16a34a'; }
                if (kpiCard) { kpiCard.style.borderLeftColor = '#22c55e'; }
                if (kpiIcon) { kpiIcon.style.background = '#dcfce7'; kpiIcon.style.color = '#16a34a'; }
            } else {
                badge.style.background = '#fee2e2';
                icon.className = 'ti ti-circle-x';
                icon.style.color = '#dc2626';
                text.style.color = '#7f1d1d';
                text.textContent = 'Kiosco deshabilitado';
                sub.textContent = 'El acceso de pasantes está bloqueado';
                if (kpiLbl) { kpiLbl.textContent = 'Inactivo'; kpiLbl.style.color = '#dc2626'; }
                if (kpiCard) { kpiCard.style.borderLeftColor = '#dc2626'; }
                if (kpiIcon) { kpiIcon.style.background = '#fee2e2'; kpiIcon.style.color = '#dc2626'; }
            }
        }

        if (toggle) {
            toggle.addEventListener('change', function () {
                const nuevoEstado = this.checked ? 1 : 0;

                fetch('<?= URLROOT ?>/configuracion/toggleKiosco', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'activo=' + nuevoEstado
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            applyState(nuevoEstado === 1);
                            NotificationService.success(data.message);
                        } else {
                            // Revertir el toggle si falló
                            toggle.checked = !toggle.checked;
                            NotificationService.error(data.message || 'Error al cambiar estado del kiosco');
                        }
                    })
                    .catch(() => {
                        toggle.checked = !toggle.checked;
                        NotificationService.error('Error de conexión. Intenta de nuevo.');
                    });
            });

            applyState(toggle.checked); // estado inicial visual
        }
    })();
</script>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Editar Institución (ISP)
     ═══════════════════════════════════════════════════════ -->
<div id="modalISP"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div
        style="background:#fff;border-radius:20px;width:100%;max-width:540px;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);overflow:hidden;">

        <!-- Header -->
        <div style="background:linear-gradient(135deg,#d97706,#b45309);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;">
                    <i class="ti ti-building-hospital" style="font-size:1.4rem;"></i>
                </div>
                <div>
                    <h3 style="margin:0;color:#fff;font-size:1.15rem;font-weight:700;">Datos de la Institución</h3>
                    <span style="color:rgba(255,255,255,.8);font-size:0.8rem;font-weight:500;">Editar información general</span>
                </div>
            </div>
            <button onclick="cerrarModalISP()" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;opacity:.7;transition:opacity .2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.7">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Body -->
        <div style="padding:24px;">
            <form action="<?= URLROOT ?>/configuracion" method="POST" id="formISP">
                <input type="hidden" name="accion" value="guardar_isp">
                <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">

                <div class="cfg-form-group">
                    <label class="cfg-label">Nombre de la Institución <span style="color:#ef4444">*</span></label>
                    <input type="text" name="isp_nombre" class="cfg-input" value="<?= htmlspecialchars($isp['nombre'] ?? '') ?>" required>
                </div>
                <div class="cfg-form-group">
                    <label class="cfg-label">RIF <span style="color:#ef4444">*</span></label>
                    <input type="text" name="isp_rif" class="cfg-input" value="<?= htmlspecialchars($isp['rif'] ?? '') ?>" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="cfg-form-group">
                        <label class="cfg-label">Estado <span style="color:#ef4444">*</span></label>
                        <input type="text" name="isp_estado" class="cfg-input" value="<?= htmlspecialchars($isp['estado'] ?? '') ?>" required>
                    </div>
                    <div class="cfg-form-group">
                        <label class="cfg-label">Ciudad <span style="color:#ef4444">*</span></label>
                        <input type="text" name="isp_ciudad" class="cfg-input" value="<?= htmlspecialchars($isp['ciudad'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="cfg-form-group">
                    <label class="cfg-label">Dirección <span style="color:#ef4444">*</span></label>
                    <textarea name="isp_direccion" class="cfg-input" rows="2" required><?= htmlspecialchars($isp['direccion'] ?? '') ?></textarea>
                </div>

                <div style="margin-top:24px;display:flex;justify-content:flex-end;gap:12px;">
                    <button type="button" onclick="cerrarModalISP()" style="padding:10px 20px;border-radius:12px;font-weight:600;font-size:0.9rem;border:1px solid #e2e8f0;background:#f8fafc;color:#64748b;cursor:pointer;transition:all .2s;">Cancelar</button>
                    <button type="submit" style="padding:10px 20px;border-radius:12px;font-weight:600;font-size:0.9rem;border:none;background:linear-gradient(135deg,#d97706,#b45309);color:#fff;cursor:pointer;box-shadow:0 4px 12px rgba(217,119,6,.25);transition:all .2s;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalISP() {
        const modal = document.getElementById('modalISP');
        modal.style.display = 'flex';
        // Animación sencilla
        const dialog = modal.querySelector('div');
        dialog.style.opacity = '0';
        dialog.style.transform = 'translateY(15px)';
        requestAnimationFrame(() => {
            dialog.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            dialog.style.opacity = '1';
            dialog.style.transform = 'translateY(0)';
        });
    }

    function cerrarModalISP() {
        const modal = document.getElementById('modalISP');
        const dialog = modal.querySelector('div');
        dialog.style.opacity = '0';
        dialog.style.transform = 'translateY(15px)';
        setTimeout(() => { modal.style.display = 'none'; }, 200);
    }
</script>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Nueva Institución
     ═══════════════════════════════════════════════════════ -->
<div id="modalInstitucion"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div
        style="background:#fff;border-radius:20px;width:100%;max-width:540px;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);overflow:hidden;">

        <!-- Header -->
        <div
            style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div
                    style="width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-school" style="color:white;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:700;font-size:1rem;">Nueva Institución</div>
                    <div style="color:rgba(255,255,255,.7);font-size:0.78rem;">Escuela técnica aliada</div>
                </div>
            </div>
            <button onclick="cerrarModalInstitucion()"
                style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:1rem;display:flex;align-items:center;justify-content:center;transition:background .2s;"
                onmouseover="this.style.background='rgba(255,255,255,.25)'"
                onmouseout="this.style.background='rgba(255,255,255,.15)'">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Body -->
        <form method="POST" action="<?= URLROOT ?>/configuracion" style="padding:24px;">
            <input type="hidden" name="accion" value="agregar_institucion">
            <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">

            <!-- Institución -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Nombre
                        *</label>
                    <input type="text" name="nombre" class="cfg-input" placeholder="Ej: E.T.C Juan Bautista" required
                        style="width:100%;">
                </div>
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Dirección
                        / Ciudad *</label>
                    <input type="text" name="direccion" class="cfg-input" placeholder="Ciudad Bolivar" required
                        style="width:100%;">
                </div>
            </div>

            <!-- Divisor representante -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <div style="flex:1;height:1px;background:#e2e8f0;"></div>
                <span
                    style="font-size:0.73rem;font-weight:700;color:#0369a1;display:flex;align-items:center;gap:5px;white-space:nowrap;">
                    <i class="ti ti-user-check"></i> Representante de la Institución
                </span>
                <div style="flex:1;height:1px;background:#e2e8f0;"></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Nombre</label>
                    <input type="text" name="representante_nombre" class="cfg-input"
                        placeholder="Nombre del representante" style="width:100%;">
                </div>
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Cargo</label>
                    <input type="text" name="representante_cargo" class="cfg-input"
                        placeholder="Coordinador de Pasantias" style="width:100%;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:22px;">
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Correo</label>
                    <input type="email" name="representante_correo" class="cfg-input"
                        placeholder="correo@institucion.edu" style="width:100%;">
                </div>
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Teléfono</label>
                    <input type="text" name="representante_telefono" class="cfg-input" placeholder="0412-0000000"
                        style="width:100%;">
                </div>
            </div>

            <!-- Acciones -->
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="cerrarModalInstitucion()"
                    style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:0.9rem;transition:background .2s;"
                    onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                    Cancelar
                </button>
                <button type="submit"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,#1e40af,#3b82f6);border:none;border-radius:10px;color:white;font-weight:700;cursor:pointer;font-size:0.9rem;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(59,130,246,.3);">
                    <i class="ti ti-circle-check"></i> Registrar Institución
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Nuevo Departamento
     ═══════════════════════════════════════════════════════ -->
<div id="modalDepto"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div
        style="background:#fff;border-radius:20px;width:100%;max-width:460px;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);overflow:hidden;">
        <div
            style="background:linear-gradient(135deg,#172554,#1e3a8a);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div
                    style="width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-building-community" style="color:white;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:700;font-size:1rem;">Nuevo Departamento</div>
                    <div style="color:rgba(255,255,255,.7);font-size:0.78rem;">Área interna del ISP</div>
                </div>
            </div>
            <button onclick="cerrarModalDepto()"
                style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <form method="POST" action="<?= URLROOT ?>/configuracion" style="padding:24px;">
            <input type="hidden" name="accion" value="agregar_departamento">
            <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:22px;">
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Nombre
                        *</label>
                    <input type="text" name="nombre" class="cfg-input" placeholder="Ej: Informatica" required
                        style="width:100%;">
                </div>
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Descripción</label>
                    <input type="text" name="descripcion" class="cfg-input" placeholder="Opcional" style="width:100%;">
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="cerrarModalDepto()"
                    style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:0.9rem;">Cancelar</button>
                <button type="submit"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,#172554,#1e3a8a);border:none;border-radius:10px;color:white;font-weight:700;cursor:pointer;font-size:0.9rem;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(30,58,138,.3);">
                    <i class="ti ti-circle-check"></i> Crear Departamento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Editar Departamento
     ═══════════════════════════════════════════════════════ -->
<div id="modalEditarDepto"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div
        style="background:#fff;border-radius:20px;width:100%;max-width:460px;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);overflow:hidden;">
        <div
            style="background:linear-gradient(135deg,#172554,#1e3a8a);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div
                    style="width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-pencil" style="color:white;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:700;font-size:1rem;">Editar Departamento</div>
                    <div style="color:rgba(255,255,255,.7);font-size:0.78rem;">Modifica el nombre o descripción</div>
                </div>
            </div>
            <button onclick="cerrarModalEditarDepto()"
                style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <form method="POST" action="<?= URLROOT ?>/configuracion" style="padding:24px;">
            <input type="hidden" name="accion" value="editar_departamento">
            <input type="hidden" name="id" id="editDeptoId">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:22px;">
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Nombre
                        *</label>
                    <input type="text" name="nombre" id="editDeptoNombre" class="cfg-input" required
                        style="width:100%;">
                </div>
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Descripción</label>
                    <input type="text" name="descripcion" id="editDeptoDesc" class="cfg-input" style="width:100%;">
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="cerrarModalEditarDepto()"
                    style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:0.9rem;">Cancelar</button>
                <button type="submit"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,#172554,#1e3a8a);border:none;border-radius:10px;color:white;font-weight:700;cursor:pointer;font-size:0.9rem;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(30,58,138,.3);">
                    <i class="ti ti-device-floppy"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Editar Institución
     ═══════════════════════════════════════════════════════ -->
<div id="modalEditarInstitucion"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div
        style="background:#fff;border-radius:20px;width:100%;max-width:540px;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);overflow:hidden;">
        <div
            style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div
                    style="width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-pencil" style="color:white;font-size:1.2rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:700;font-size:1rem;">Editar Institución</div>
                    <div style="color:rgba(255,255,255,.7);font-size:0.78rem;">Modifica datos y representante</div>
                </div>
            </div>
            <button onclick="cerrarModalEditarInstitucion()"
                style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <form method="POST" action="<?= URLROOT ?>/configuracion" style="padding:24px;">
            <input type="hidden" name="accion" value="editar_institucion">
            <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">
            <input type="hidden" name="id" id="editInstId">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Nombre
                        *</label>
                    <input type="text" name="nombre" id="editInstNombre" class="cfg-input" required style="width:100%;">
                </div>
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Dirección
                        / Ciudad *</label>
                    <input type="text" name="direccion" id="editInstDireccion" class="cfg-input" required
                        style="width:100%;">
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <div style="flex:1;height:1px;background:#e2e8f0;"></div>
                <span
                    style="font-size:0.73rem;font-weight:700;color:#0369a1;display:flex;align-items:center;gap:5px;white-space:nowrap;">
                    <i class="ti ti-user-check"></i> Representante
                </span>
                <div style="flex:1;height:1px;background:#e2e8f0;"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Nombre</label>
                    <input type="text" name="representante_nombre" id="editInstRepNombre" class="cfg-input"
                        style="width:100%;">
                </div>
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Cargo</label>
                    <input type="text" name="representante_cargo" id="editInstRepCargo" class="cfg-input"
                        style="width:100%;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:22px;">
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Correo</label>
                    <input type="email" name="representante_correo" id="editInstRepCorreo" class="cfg-input"
                        style="width:100%;">
                </div>
                <div>
                    <label
                        style="display:block;font-size:0.78rem;font-weight:700;color:#374151;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px;">Teléfono</label>
                    <input type="text" name="representante_telefono" id="editInstRepTel" class="cfg-input"
                        style="width:100%;">
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="button" onclick="cerrarModalEditarInstitucion()"
                    style="flex:1;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:600;cursor:pointer;font-size:0.9rem;">Cancelar</button>
                <button type="submit"
                    style="flex:2;padding:11px;background:linear-gradient(135deg,#1e40af,#3b82f6);border:none;border-radius:10px;color:white;font-weight:700;cursor:pointer;font-size:0.9rem;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 14px rgba(59,130,246,.3);">
                    <i class="ti ti-device-floppy"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── Modal Nueva Institución ───────────────────────────
    function abrirModalInstitucion() {
        const m = document.getElementById('modalInstitucion');
        m.style.display = 'flex';
        setTimeout(() => m.querySelector('input[name="nombre"]').focus(), 100);
    }
    function cerrarModalInstitucion() {
        document.getElementById('modalInstitucion').style.display = 'none';
    }

    // ── Modal Nuevo Departamento ──────────────────────────
    function abrirModalDepto() {
        const m = document.getElementById('modalDepto');
        m.style.display = 'flex';
        setTimeout(() => m.querySelector('input[name="nombre"]').focus(), 100);
    }
    function cerrarModalDepto() {
        document.getElementById('modalDepto').style.display = 'none';
    }

    // ── Modal Editar Departamento ─────────────────────────
    function abrirEditarDepto(id, nombre, descripcion) {
        document.getElementById('editDeptoId').value = id;
        document.getElementById('editDeptoNombre').value = nombre;
        document.getElementById('editDeptoDesc').value = descripcion;
        const m = document.getElementById('modalEditarDepto');
        m.style.display = 'flex';
        setTimeout(() => document.getElementById('editDeptoNombre').focus(), 100);
    }
    function cerrarModalEditarDepto() {
        document.getElementById('modalEditarDepto').style.display = 'none';
    }

    // ── Modal Editar Institución ──────────────────────────
    function abrirEditarInstitucion(id, inst) {
        document.getElementById('editInstId').value = id;
        document.getElementById('editInstNombre').value = inst.nombre || '';
        document.getElementById('editInstDireccion').value = inst.direccion || '';
        document.getElementById('editInstRepNombre').value = inst.representante_nombre || '';
        document.getElementById('editInstRepCargo').value = inst.representante_cargo || '';
        document.getElementById('editInstRepCorreo').value = inst.representante_correo || '';
        document.getElementById('editInstRepTel').value = inst.representante_telefono || '';
        const m = document.getElementById('modalEditarInstitucion');
        m.style.display = 'flex';
        setTimeout(() => document.getElementById('editInstNombre').focus(), 100);
    }
    function cerrarModalEditarInstitucion() {
        document.getElementById('modalEditarInstitucion').style.display = 'none';
    }

    // ── Cerrar cualquier modal con Escape o clic en fondo ─
    ['modalInstitucion', 'modalDepto', 'modalEditarDepto', 'modalEditarInstitucion', 'modalNotifTrazabilidad', 'modalNotifUsuarios'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('click', e => { if (e.target === el) el.style.display = 'none'; });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            ['modalInstitucion', 'modalDepto', 'modalEditarDepto', 'modalEditarInstitucion', 'modalNotifTrazabilidad', 'modalNotifUsuarios'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
        }
    });

    // ── Notificaciones: Switch Global ──────────────────────
    (function () {
        const toggle = document.getElementById('notifGlobalToggle');
        const badge = document.getElementById('notifGlobalBadge');

        if (toggle) {
            toggle.addEventListener('change', function () {
                const nuevoEstado = this.checked ? 1 : 0;
                fetch('<?= URLROOT ?>/configuracion/toggleNotificaciones', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'activo=' + nuevoEstado
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (badge) {
                            badge.textContent = nuevoEstado === 1 ? 'Activas' : 'Pausadas';
                            badge.style.background = nuevoEstado === 1 ? '#dcfce7' : '#fee2e2';
                            badge.style.color = nuevoEstado === 1 ? '#16a34a' : '#dc2626';
                        }
                        NotificationService.success(data.message);
                    } else {
                        toggle.checked = !toggle.checked;
                        NotificationService.error(data.message || 'Error al cambiar estado');
                    }
                })
                .catch(() => {
                    toggle.checked = !toggle.checked;
                    NotificationService.error('Error de red al actualizar estado global');
                });
            });
        }
    })();

    // ── Notificaciones: Modales ────────────────────────────
    function abrirModalNotifTrazabilidad() {
        const m = document.getElementById('modalNotifTrazabilidad');
        if (m) m.style.display = 'flex';
    }
    function cerrarModalNotifTrazabilidad() {
        const m = document.getElementById('modalNotifTrazabilidad');
        if (m) m.style.display = 'none';
    }

    function abrirModalNotifUsuarios() {
        const m = document.getElementById('modalNotifUsuarios');
        if (m) m.style.display = 'flex';
    }
    function cerrarModalNotifUsuarios() {
        const m = document.getElementById('modalNotifUsuarios');
        if (m) m.style.display = 'none';
    }

    // ── Notificaciones: Toggle por Usuario ─────────────────
    function toggleUserNotif(userId, checkbox) {
        const habilitado = checkbox.checked ? 1 : 0;
        fetch('<?= URLROOT ?>/configuracion/toggleUsuarioNotificaciones', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'usuario_id=' + userId + '&habilitado=' + habilitado
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                NotificationService.success(data.message);
            } else {
                checkbox.checked = !checkbox.checked;
                NotificationService.error(data.message || 'Error al actualizar preferencia');
            }
        })
        .catch(() => {
            checkbox.checked = !checkbox.checked;
            NotificationService.error('Error de conexión al servidor');
        });
    }

    // ── Filtro rápido de usuarios en modal ─────────────────
    function filtrarUsuariosNotif(input) {
        const query = (input.value || '').toLowerCase().trim();
        const filas = document.querySelectorAll('.user-notif-row');
        filas.forEach(f => {
            const txt = (f.textContent || '').toLowerCase();
            f.style.display = txt.includes(query) ? '' : 'none';
        });
    }

    // ── Branding y Personalización de Tema (Fase 5) ──────────
    function sincronizarColor(tipo, val) {
        if (!val) return;
        val = val.trim();
        if (tipo === 'primario') {
            const picker = document.getElementById('pickerPrimario');
            const input = document.getElementById('inputPrimario');
            const disp = document.getElementById('hexPrimarioDisplay');
            if (picker && picker.value !== val) picker.value = val;
            if (input && input.value !== val) input.value = val;
            if (disp) disp.textContent = val;
        } else if (tipo === 'acento') {
            const picker = document.getElementById('pickerAcento');
            const input = document.getElementById('inputAcento');
            const disp = document.getElementById('hexAcentoDisplay');
            if (picker && picker.value !== val) picker.value = val;
            if (input && input.value !== val) input.value = val;
            if (disp) disp.textContent = val;
        }
        actualizarPreviewTema();
    }

    function aplicarPresetColor(primario, acento) {
        sincronizarColor('primario', primario);
        sincronizarColor('acento', acento);
    }

    function actualizarPreviewTema() {
        const cp = (document.getElementById('inputPrimario')?.value || '#162660').trim();
        const ca = (document.getElementById('inputAcento')?.value || '#2563eb').trim();
        const prevCard = document.getElementById('previewCardBrand');
        if (prevCard) {
            prevCard.style.background = 'linear-gradient(135deg, ' + cp + ', ' + ca + ')';
        }
    }

    function alternarTemaVisual() {
        if (typeof toggleDarkMode === 'function') {
            toggleDarkMode();
        } else {
            const curr = document.documentElement.getAttribute('data-theme') || 'light';
            const next = curr === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            if (next === 'dark') document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
            localStorage.setItem('sgp_theme', next);
        }
        sincronizarBotonTemaConfig();
    }

    function sincronizarBotonTemaConfig() {
        const theme = localStorage.getItem('sgp_theme') || 'light';
        const icon = document.getElementById('cfgThemeIcon');
        const label = document.getElementById('cfgThemeLabel');
        const btn = document.getElementById('cfgBtnToggleTheme');
        if (theme === 'dark') {
            if (icon) icon.className = 'ti ti-sun';
            if (label) label.textContent = 'Modo Claro';
            if (btn) { btn.style.background = '#1e293b'; btn.style.color = '#f8fafc'; btn.style.borderColor = '#334155'; }
        } else {
            if (icon) icon.className = 'ti ti-moon';
            if (label) label.textContent = 'Modo Oscuro';
            if (btn) { btn.style.background = '#f8fafc'; btn.style.color = '#334155'; btn.style.borderColor = '#cbd5e1'; }
        }
    }

    function confirmarRestablecerTema() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Restablecer tema?',
                text: 'Se volverán a aplicar los colores institucionales originales del ISP (#162660 y #2563eb).',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, restablecer',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#162660'
            }).then(r => {
                if (r.isConfirmed) {
                    document.getElementById('formRestablecerTema')?.submit();
                }
            });
        } else {
            if (confirm('¿Restablecer los colores del tema por defecto?')) {
                document.getElementById('formRestablecerTema')?.submit();
            }
        }
    }

    // ── Modal Subir Logo Institucional ─────────────────────
    function abrirModalLogo(tipo) {
        const m = document.getElementById('modalSubirLogo');
        if (!m) return;
        const sel = document.getElementById('selectTipoLogo');
        if (sel && tipo) sel.value = tipo;
        actualizarInfoLogoModal();
        const prev = document.getElementById('previewImgLogo');
        if (prev) { prev.style.display = 'none'; prev.src = ''; }
        const fileInp = document.getElementById('archivoLogoInput');
        if (fileInp) fileInp.value = '';
        m.style.display = 'flex';
    }

    function cerrarModalLogo() {
        const m = document.getElementById('modalSubirLogo');
        if (m) m.style.display = 'none';
    }

    function actualizarInfoLogoModal() {
        const sel = document.getElementById('selectTipoLogo');
        const info = document.getElementById('infoLogoModal');
        if (!sel || !info) return;
        const val = sel.value;
        if (val === 'logo_principal') {
            info.innerHTML = '<strong>Logo Principal:</strong> Se recomienda imagen PNG con fondo transparente de al menos 400x120px.';
        } else if (val === 'cintillo') {
            info.innerHTML = '<strong>Cintillo Oficial:</strong> Proporción horizontal/panorámica (ej. 1200x180px) para reportes y constancias PDF.';
        } else if (val === 'gobernacion') {
            info.innerHTML = '<strong>Logo de Gobernación:</strong> Emblema del estado en PNG transparente.';
        }
    }

    function previewArchivoLogo(input) {
        const file = input.files && input.files[0];
        const preview = document.getElementById('previewImgLogo');
        if (!file || !preview) return;

        if (file.size > 3 * 1024 * 1024) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Archivo muy grande', text: 'El archivo no debe superar los 3 MB.' });
            } else {
                alert('El archivo no debe superar los 3 MB.');
            }
            input.value = '';
            preview.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Sincronizar botón de tema al cargar
    setTimeout(sincronizarBotonTemaConfig, 200);
</script>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Trazabilidad de Clics e Interacciones (Fase 4)
     ═══════════════════════════════════════════════════════ -->
<div id="modalNotifTrazabilidad"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:22px;width:100%;max-width:920px;max-height:88vh;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);display:flex;flex-direction:column;overflow:hidden;">
        <!-- Header -->
        <div style="background:linear-gradient(135deg,#1e3a8a,#2563eb);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;">
                    <i class="ti ti-chart-dots-3" style="font-size:1.25rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:800;font-size:1.05rem;">Trazabilidad de Clics y Lectura</div>
                    <div style="color:rgba(255,255,255,.75);font-size:0.78rem;">Registro de interacciones de las últimas 50 alertas enviadas</div>
                </div>
            </div>
            <button onclick="cerrarModalNotifTrazabilidad()" style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:1.1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Tabla -->
        <div style="padding:20px;overflow-y:auto;flex:1;">
            <?php if (empty($ultimasNotifs)): ?>
                <div style="text-align:center;padding:40px;color:#94a3b8;">
                    <i class="ti ti-bell-off" style="font-size:3rem;opacity:0.4;display:block;margin-bottom:10px;"></i>
                    <p style="font-size:0.95rem;margin:0;">No hay notificaciones registradas para auditar.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.83rem;">
                        <thead>
                            <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;color:#64748b;text-align:left;">
                                <th style="padding:10px 12px;font-weight:700;">Destinatario</th>
                                <th style="padding:10px 12px;font-weight:700;">Alerta</th>
                                <th style="padding:10px 12px;font-weight:700;">Envío</th>
                                <th style="padding:10px 12px;font-weight:700;">Lectura</th>
                                <th style="padding:10px 12px;font-weight:700;">Clic / Interacción</th>
                                <th style="padding:10px 12px;font-weight:700;">Dispositivo & IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasNotifs as $nt): ?>
                                <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                    <td style="padding:12px;">
                                        <div style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($nt['usuario_nombre'] ?: 'Usuario') ?></div>
                                        <div style="font-size:0.73rem;color:#94a3b8;"><?= htmlspecialchars($nt['correo'] ?? '—') ?></div>
                                    </td>
                                    <td style="padding:12px;max-width:200px;">
                                        <div style="font-weight:600;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($nt['titulo']) ?>">
                                            <?= htmlspecialchars($nt['titulo']) ?>
                                        </div>
                                        <span class="cfg-badge" style="font-size:0.68rem;padding:2px 6px;background:#eff6ff;color:#2563eb;">
                                            <?= htmlspecialchars($nt['tipo']) ?>
                                        </span>
                                    </td>
                                    <td style="padding:12px;color:#64748b;white-space:nowrap;">
                                        <?= date('d/m/y H:i', strtotime($nt['created_at'])) ?>
                                    </td>
                                    <td style="padding:12px;white-space:nowrap;">
                                        <?php if (!empty($nt['leido_at'])): ?>
                                            <span style="color:#059669;font-weight:600;display:flex;align-items:center;gap:4px;">
                                                <i class="ti ti-check" style="font-size:0.9rem;"></i> <?= date('d/m H:i', strtotime($nt['leido_at'])) ?>
                                            </span>
                                        <?php elseif (!empty($nt['leida'])): ?>
                                            <span style="color:#059669;font-weight:600;">✅ Leída</span>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;font-size:0.75rem;">Sin leer</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px;white-space:nowrap;">
                                        <?php if (!empty($nt['clickeado_at'])): ?>
                                            <span style="color:#9333ea;font-weight:700;display:flex;align-items:center;gap:4px;">
                                                <i class="ti ti-pointer" style="font-size:0.9rem;"></i> <?= date('d/m H:i', strtotime($nt['clickeado_at'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#cbd5e1;font-size:0.75rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px;">
                                        <div style="color:#475569;font-weight:600;font-size:0.78rem;">
                                            <?= htmlspecialchars($nt['dispositivo'] ?: 'Escritorio') ?>
                                        </div>
                                        <?php if (!empty($nt['ip_click'])): ?>
                                            <code style="font-size:0.7rem;color:#64748b;background:#f1f5f9;padding:2px 5px;border-radius:4px;"><?= htmlspecialchars($nt['ip_click']) ?></code>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Gestión de Notificaciones por Usuario (Fase 4)
     ═══════════════════════════════════════════════════════ -->
<div id="modalNotifUsuarios"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:22px;width:100%;max-width:740px;max-height:85vh;margin:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);display:flex;flex-direction:column;overflow:hidden;">
        <!-- Header -->
        <div style="background:linear-gradient(135deg,#0f172a,#334155);padding:18px 24px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;">
                    <i class="ti ti-users" style="font-size:1.25rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:800;font-size:1.05rem;">Preferencias de Notificaciones por Usuario</div>
                    <div style="color:rgba(255,255,255,.7);font-size:0.78rem;">Activar o silenciar el envío de alertas para usuarios activos</div>
                </div>
            </div>
            <button onclick="cerrarModalNotifUsuarios()" style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:1.1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Buscador -->
        <div style="padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;flex-shrink:0;">
            <div style="position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" class="cfg-input" placeholder="Buscar por nombre, correo o rol..." style="width:100%;padding-left:36px;" oninput="filtrarUsuariosNotif(this)">
            </div>
        </div>

        <!-- Lista -->
        <div style="padding:16px 20px;overflow-y:auto;flex:1;">
            <?php if (empty($notifUsers)): ?>
                <div style="text-align:center;padding:30px;color:#94a3b8;">No se encontraron usuarios.</div>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <?php foreach ($notifUsers as $u): ?>
                        <div class="user-notif-row" style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:white;border:1px solid #e2e8f0;border-radius:12px;transition:box-shadow .15s;">
                            <div style="display:flex;align-items:center;gap:12px;">
                                <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;">
                                    <?= strtoupper(substr($u['nombre_completo'] ?: $u['correo'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;color:#1e293b;font-size:0.88rem;"><?= htmlspecialchars($u['nombre_completo'] ?: 'Sin nombre') ?></div>
                                    <div style="font-size:0.75rem;color:#64748b;"><?= htmlspecialchars($u['correo']) ?></div>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:16px;">
                                <span class="cfg-badge" style="background:#f1f5f9;color:#475569;font-size:0.72rem;">
                                    <?= htmlspecialchars($u['rol_nombre'] ?? 'Usuario') ?>
                                </span>
                                <label class="cfg-toggle-wrap" style="margin:0;" title="Habilitar/Silenciar alertas">
                                    <input type="checkbox" <?= (int)($u['notificaciones_habilitadas'] ?? 1) === 1 ? 'checked' : '' ?> onchange="toggleUserNotif(<?= (int)$u['id'] ?>, this)">
                                    <span class="cfg-toggle-track"></span>
                                    <span class="cfg-toggle-dot"></span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MODAL: Subir Logo Institucional (Fase 5 - Branding)
     ═══════════════════════════════════════════════════════ -->
<div id="modalSubirLogo"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(5px);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:22px;width:100%;max-width:540px;margin:16px;box-shadow:0 28px 70px rgba(0,0,0,.22);overflow:hidden;">

        <!-- Header -->
        <div style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 55%,#2563eb 100%);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:40px;height:40px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;">
                    <i class="ti ti-upload" style="font-size:1.25rem;"></i>
                </div>
                <div>
                    <div style="color:white;font-weight:800;font-size:1.05rem;">Actualizar Emblema Institucional</div>
                    <div style="color:rgba(255,255,255,.75);font-size:0.76rem;">Reemplazo seguro de archivo de imagen del sistema</div>
                </div>
            </div>
            <button onclick="cerrarModalLogo()" type="button"
                style="background:rgba(255,255,255,.15);border:none;border-radius:8px;width:32px;height:32px;cursor:pointer;color:white;font-size:1.1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Formulario -->
        <form method="POST" action="<?= URLROOT ?>/configuracion" enctype="multipart/form-data" style="padding:22px 24px;">
            <input type="hidden" name="accion" value="subir_logo">
            <input type="hidden" name="_csrf" value="<?= CsrfHelper::generate() ?>">

            <!-- Tipo de Logo -->
            <div style="margin-bottom:16px;">
                <label class="cfg-field-label">Selecciona el Emblema a Actualizar *</label>
                <select name="tipo_logo" id="selectTipoLogo" class="cfg-input" style="width:100%;cursor:pointer;" onchange="actualizarInfoLogoModal()">
                    <option value="logo_principal">Logo Principal SGP / ISP (logo.png)</option>
                    <option value="cintillo">Cintillo Oficial para Reportes (cintillo-salud.png)</option>
                    <option value="gobernacion">Logo de Gobernación de Bolívar (gobe.png)</option>
                </select>
                <div id="infoLogoModal" style="font-size:0.74rem;color:#64748b;margin-top:6px;line-height:1.4;">
                    <strong>Logo Principal:</strong> Se recomienda imagen PNG con fondo transparente de al menos 400x120px.
                </div>
            </div>

            <!-- Zona de Subida / File Input -->
            <div style="margin-bottom:18px;">
                <label class="cfg-field-label">Seleccionar Archivo de Imagen *</label>
                <div style="border:2px dashed #cbd5e1;border-radius:12px;padding:20px;text-align:center;background:#f8fafc;cursor:pointer;transition:all .2s;"
                    onclick="document.getElementById('archivoLogoInput').click()"
                    onmouseover="this.style.borderColor='#3b82f6';this.style.background='#eff6ff';"
                    onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc';">
                    <i class="ti ti-cloud-upload" style="font-size:2.2rem;color:#3b82f6;display:block;margin-bottom:6px;"></i>
                    <div style="font-weight:700;font-size:0.88rem;color:#1e293b;">Haz clic para buscar o arrastra la imagen aquí</div>
                    <div style="font-size:0.75rem;color:#94a3b8;margin-top:4px;">Formatos soportados: PNG, JPG, JPEG (máximo 3 MB)</div>
                    <input type="file" name="archivo_logo" id="archivoLogoInput" accept="image/png,image/jpeg" style="display:none;" required onchange="previewArchivoLogo(this)">
                </div>

                <!-- Preview antes de subir -->
                <div style="text-align:center;margin-top:12px;">
                    <img id="previewImgLogo" src="" alt="Vista Previa"
                        style="display:none;max-height:110px;max-width:100%;margin:0 auto;border:1px solid #e2e8f0;border-radius:8px;padding:4px;background:white;box-shadow:0 4px 10px rgba(0,0,0,0.06);">
                </div>
            </div>

            <!-- Acciones -->
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="cerrarModalLogo()"
                    style="padding:10px 18px;border:1.5px solid #e2e8f0;border-radius:10px;background:white;color:#64748b;font-weight:600;font-size:0.85rem;cursor:pointer;">
                    Cancelar
                </button>
                <button type="submit" class="cfg-btn-primary"
                    style="padding:10px 20px;background:linear-gradient(135deg,#1e3a8a,#2563eb);border:none;border-radius:10px;color:white;font-weight:700;font-size:0.85rem;display:flex;align-items:center;gap:6px;">
                    <i class="ti ti-upload"></i> Subir y Guardar Emblema
                </button>
            </div>
        </form>

    </div>
</div>