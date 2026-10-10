<?php
/**
 * Vista: Gestión de Pasantes (Admin)
 * URL: /pasantes  —  Cargada por PasantesController::index()
 */
?>

<style>
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,0.7); backdrop-filter: blur(6px);
    z-index: 9999; align-items: center; justify-content: center;
    animation: fadeIn 0.2s ease;
}
.modal-overlay.active { display: flex; }
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
@keyframes slideUp { from { transform:translateY(24px);opacity:0; } to { transform:translateY(0);opacity:1; } }

/* Modal box: flexbox para que header sea fijo y body haga scroll */
.modal-box {
    background: white;
    border-radius: 24px;
    width: 90%;
    max-width: 580px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 32px 80px rgba(15,23,42,0.3);
    animation: slideUp 0.3s ease;
}
.modal-head {
    background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
    padding: 28px 32px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
    color: white;
}
.modal-head h2 { font-size:1.3rem; font-weight:700; margin:0; color:white !important; }
.modal-head p  { font-size:0.85rem; margin:4px 0 0; color:rgba(255,255,255,0.8) !important; }
.modal-head * { color: white !important; }
.btn-close-modal {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white !important;
    width: 36px; height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
    flex-shrink: 0;
}
.btn-close-modal:hover { background: rgba(255,255,255,0.35); }
.btn-close-modal i { color: white !important; }
.modal-body {
    padding: 28px 32px;
    overflow-y: auto;
    flex: 1;
}

.form-group { margin-bottom: 20px; }
.form-label {
    display:block; font-size:0.82rem; font-weight:700; color:#374151;
    margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;
}
.form-input {
    width:100%; padding:12px 16px; border:2px solid #e5e7eb; border-radius:12px;
    font-size:0.95rem; color:#1e293b; transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing:border-box; background:#fafafa;
}
.form-input:focus {
    outline:none; border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(79,70,229,0.1); background:white;
}

.horas-wrapper { display:flex; gap:10px; align-items:center; }
.horas-wrapper .form-input { flex:1; }
.btn-reset-horas {
    white-space:nowrap; padding:10px 14px; background:#eff6ff;
    color:#2563eb; border:2px solid #bfdbfe; border-radius:10px;
    font-size:0.8rem; font-weight:700; cursor:pointer; transition:all 0.2s;
    display:flex; align-items:center; gap:6px;
}
.btn-reset-horas:hover { background:#2563eb; color:white; border-color:#2563eb; }

.jornada-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.jornada-option {
    border:2px solid #e5e7eb; border-radius:12px; padding:14px;
    cursor:pointer; transition:all 0.2s; text-align:center; background:#fafafa;
}
.jornada-option:hover { border-color:#2563eb; background:#eff6ff; }
.jornada-option.selected {
    border-color:#2563eb; background:linear-gradient(135deg,#eff6ff,#dbeafe);
    box-shadow:0 0 0 3px rgba(79,70,229,0.15);
}
.jornada-option input[type="radio"] { display:none; }
.jornada-icon  { font-size:1.6rem; display:block; margin-bottom:6px; }
.jornada-label { font-weight:700; color:#1e293b; font-size:0.9rem; display:block; }
.jornada-sub   { color:#6b7280; font-size:0.78rem; }

.proyeccion-card {
    display:none; background:linear-gradient(135deg,#eff6ff,#dbeafe);
    border:1.5px solid #bfdbfe; border-radius:14px; padding:18px 20px;
    margin-top:16px; animation:slideUp 0.3s ease;
}
.proyeccion-card.visible { display:block; }
.proy-title {
    font-size:0.78rem; font-weight:700; color:#2563eb; text-transform:uppercase;
    letter-spacing:0.5px; margin-bottom:10px; display:flex; align-items:center; gap:6px;
}
.proy-dato {
    display:flex; justify-content:space-between; align-items:center;
    padding:6px 0; border-bottom:1px solid rgba(79,70,229,0.1); font-size:0.88rem;
}
.proy-dato:last-child { border-bottom:none; }
.proy-dato span:first-child { color:#6b7280; }
.proy-dato strong { color:#1e3a8a; font-weight:700; }
.proy-highlight {
    background:#2563eb; color:white; padding:10px 14px; border-radius:10px;
    margin-top:10px; font-size:0.9rem; text-align:center; font-weight:600;
}
.modal-divider { border:none; border-top:1px solid #f1f5f9; margin:24px 0; }
.btn-submit {
    width:100%; padding:14px;
    background:linear-gradient(135deg,#172554 0%,#2563eb 100%);
    color:white; border:none; border-radius:12px; font-size:1rem;
    font-weight:700; cursor:pointer; display:flex; align-items:center;
    justify-content:center; gap:10px; transition:all 0.2s;
    box-shadow:0 4px 16px rgba(79,70,229,0.35);
}
.btn-submit:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(79,70,229,0.45); }

.progress-wrap { min-width:140px; }
.progress-track { background:#f1f5f9; border-radius:100px; height:7px; overflow:hidden; margin-top:5px; }
.progress-fill  { height:100%; border-radius:100px; background:linear-gradient(90deg,#2563eb,#1d4ed8); transition:width 0.5s ease; }
.progress-label { font-size:0.78rem; color:#64748b; font-weight:600; }
.progress-pct   { float:right; font-weight:700; color:#1e3a8a; }

/* AJUSTE MAESTRO: Forzar que el select flote sobre el modal sin estirarlo */
#modalCambiarEstado .modal-box,
#modalCambiarEstado .modal-body {
    overflow: visible !important;
}

/* Recuperar bordes redondos de la cabecera azul */
#modalCambiarEstado .modal-head {
    border-top-left-radius: 20px !important;
    border-top-right-radius: 20px !important;
}

#modalCambiarEstado .choices {
    position: relative !important;
}

#modalCambiarEstado .choices__list--dropdown {
    position: absolute !important;
    top: 100% !important;
    left: 0 !important;
    width: 100% !important;
    z-index: 999999 !important;
    background-color: #ffffff !important;
    border-radius: 0 0 12px 12px !important;
    box-shadow: 0px 10px 15px rgba(0,0,0,0.1) !important;
}

/* Buscador inteligente y filtros */
.search-input-wrapper {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 4px 14px;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
}
.search-input-wrapper:focus-within {
    background-color: #fff;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.search-input-wrapper input::placeholder { color: #94a3b8; }

/* Ocultar search default de DataTables */
#tablaPasantes_filter { display: none !important; }

/* [SGP-FIX-PASANTES-ANCHO-TABLA] Anchos fijos por columna: sin esto, table-layout:auto
   recalcula el ancho de cada columna según el contenido de las filas visibles en ese
   momento (10 filas sin filtrar vs 1-2 filtradas), y la tabla podía terminar más ancha
   que su contenedor, recortando el último botón de Acciones por el overflow-x. */
#tablaPasantes { table-layout: fixed; }
#tablaPasantes th:nth-child(1) { width: 20%; }
#tablaPasantes th:nth-child(2) { width: 9%; }
#tablaPasantes th:nth-child(3) { width: 15%; }
#tablaPasantes th:nth-child(4) { width: 12%; }
#tablaPasantes th:nth-child(5) { width: 15%; }
#tablaPasantes th:nth-child(6) { width: 9%; }
#tablaPasantes th:nth-child(7) { width: 20%; }

@media (max-width: 1200px) {
    .kpi-pasantes-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    .pasantes-filter-bar {
        flex-direction: column;
        align-items: stretch !important;
    }
    .pasantes-filter-bar .choices {
        width: 100% !important;
        margin-bottom: 10px;
    }
    #filterDepto, #filterInst { width: 100% !important; }
    .pasantes-filter-bar > div { flex: 1 1 100%; width: 100%; }
}
@media (max-width: 768px) {
    .kpi-pasantes-grid {
        grid-template-columns: 1fr 1fr !important;
    }
}
@media (max-width: 640px) {
    .kpi-pasantes-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
    }
    /* Barra de filtros: contenedor interior en columna */
    .pasantes-filter-bar {
        padding: 14px 16px !important;
        gap: 10px !important;
    }
    .pasantes-filter-bar > div:first-child {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 8px !important;
    }
    .pasantes-filter-bar > div:first-child > p {
        margin-bottom: 0 !important;
    }
    /* Selectores: ancho completo */
    #filterDepto, #filterInst {
        width: 100% !important;
        box-sizing: border-box !important;
        min-width: 0 !important;
    }
    /* Buscador: ancho completo */
    .pasantes-filter-bar > div:last-child {
        flex: 1 1 100% !important;
        min-width: 0 !important;
    }
}
</style>

<?php
// Preservar lista completa (con IDs) antes de transformar para filtros
$deptos_lista = $departamentos ?? [];

// Extraer Departamentos e Instituciones únicas para los filtros
$departamentos = [];
$filtro_inst_nombres = [];
foreach($pasantes as $p) {
    if(!empty($p->departamento_nombre)) $departamentos[$p->departamento_nombre] = true;
    if(!empty($p->institucion_nombre)) $filtro_inst_nombres[$p->institucion_nombre] = true;
}
$departamentos = array_keys($departamentos);
$filtro_inst_nombres = array_keys($filtro_inst_nombres);
sort($departamentos);
sort($filtro_inst_nombres);
?>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">

    <!-- BANNER ESTANDARIZADO SGP -->
    <div class="pasantes-banner" style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:20px;padding:32px 40px;margin-bottom:28px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;">
        <div style="position:absolute;top:-30px;right:-30px;width:200px;height:200px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
        <div style="display:flex;align-items:center;gap:16px;z-index:1;">
            <div style="background:rgba(255,255,255,0.15);border-radius:14px;padding:14px;">
                <i class="ti ti-user-check" style="font-size:32px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:white;font-size:1.8rem;font-weight:700;margin:0;">Gestión de Pasantes</h1>
                <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:0.9rem;display:flex;align-items:center;">
                    <span>Registro, seguimiento y proyección de pasantías</span>
                    <span style="display:inline-block; background:rgba(255,255,255,0.15); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.1); border-radius:50px; padding:4px 14px; margin-left:12px; color:white; font-weight:700; font-size:0.8rem; box-shadow:0 4px 6px rgba(0,0,0,0.05); white-space:nowrap;">
                        <?= $total ?> pasantes
                    </span>
                </p>
            </div>
        </div>
        <div class="pasantes-banner-actions" style="display:flex; z-index:1; align-items:center;">
            <div style="background: rgba(0, 0, 0, 0.15); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 14px; padding: 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <button onclick="SGPModal.buscar({rol: 3})" style="background:white;color:#1e3a8a;border:none;padding:12px 24px;border-radius:10px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:8px;font-size:0.95rem;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <i class="ti ti-search" style="font-size: 1.1rem;"></i> Consulta Rápida
                </button>
            </div>
        </div>
    </div>


    <!-- KPI CARDS ESTANDARIZADAS -->
    <div class="kpi-pasantes-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px;">
        <?php
        $kpis = [
            ['label' => 'Total Pasantes',  'val' => $total,      'color' => '#2563eb', 'boxShadow' => 'rgba(37,99,235,0.15)', 'icon' => 'ti-users',        'sub' => 'registrados', 'filtro' => ''],
            ['label' => 'En Curso',        'val' => $enCurso,    'color' => '#10b981', 'boxShadow' => 'rgba(16,185,129,0.15)', 'icon' => 'ti-player-play',  'sub' => 'pasantías activas', 'filtro' => 'Activo'],
            ['label' => 'Pendientes',      'val' => $pendientes, 'color' => '#f59e0b', 'boxShadow' => 'rgba(245,158,11,0.15)', 'icon' => 'ti-clock',        'sub' => 'por formalizar', 'filtro' => 'Pendiente|Sin Asignar'],
            ['label' => 'Culminados',      'val' => $culminados, 'color' => '#8b5cf6', 'boxShadow' => 'rgba(139,92,246,0.15)', 'icon' => 'ti-medal',        'sub' => 'este período', 'filtro' => 'Finalizado'],
        ];
        foreach ($kpis as $k): ?>
        <div style="background:white;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid <?= $k['color'] ?>;transition:all 0.3s;<?= $k['filtro'] ? 'cursor:pointer;' : '' ?>" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 25px <?= $k['boxShadow'] ?>'" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'" <?= $k['filtro'] ? "onclick=\"filtrarEstadoKPI('{$k['filtro']}')\" title=\"Ver {$k['label']}\"" : '' ?>>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <p style="color:#64748b;font-size:0.82rem;margin:0 0 8px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;"><?= $k['label'] ?></p>
                <i class="ti <?= $k['icon'] ?>" style="color:<?= $k['color'] ?>;font-size:1.4rem;opacity:0.7;"></i>
            </div>
            <h2 style="font-size:2.4rem;font-weight:800;color:<?= $k['color'] ?>;margin:0;" data-kpi-value="<?= $k['val'] ?>">0</h2>
            <p style="color:#94a3b8;font-size:0.8rem;margin:4px 0 0;"><?= $k['sub'] ?><?= $k['filtro'] ? ' <span style="font-size:0.7rem;opacity:0.7;">↗ ver</span>' : '' ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros Inteligentes (Bento UI) -->
    <div class="pasantes-filter-bar" style="margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; background: white; padding: 18px 24px; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.04);">
        <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; flex: 1;">
            <p style="font-size: 0.9rem; font-weight: 700; color: #64748b; margin: 0; display:flex; align-items:center; gap:6px;">
                <i class="ti ti-filter" style="font-size:1.1rem; color:#2563eb;"></i> Filtros:
            </p>

            <select id="filterDepto" class="form-input" style="width: 200px; padding: 8px 12px; border-radius: 10px; font-weight: 600; color: #334155; height: 42px;" onchange="aplicarFiltrosPasantes()">
                <option value="">Cualquier Departamento</option>
                <?php foreach($departamentos as $d): ?>
                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>

            <select id="filterInst" class="form-input" style="width: 220px; padding: 8px 12px; border-radius: 10px; font-weight: 600; color: #334155; height: 42px;" onchange="aplicarFiltrosPasantes()">
                <option value="">Cualquier Institución</option>
                <?php foreach($filtro_inst_nombres as $i): ?>
                <option value="<?= htmlspecialchars($i) ?>"><?= htmlspecialchars($i) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Buscador Inteligente -->
        <div style="flex: 0 1 300px; min-width: 200px;">
            <div style="position: relative; width: 100%;">
                <i class="ti ti-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #1D4ED8; font-size: 1.2rem; z-index: 2; pointer-events: none;"></i>
                <input type="text" id="customSearchPasantes" placeholder="Buscar pasante, cédula..."
                       style="width: 100%; height: 42px; padding: 0 16px 0 48px; border: 1.5px solid #DDE2F0; border-radius: 50px; background: white; color: #0D1424; font-weight: 600; font-size: 0.88rem; outline: none; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.02);"
                       onfocus="this.style.borderColor='#1D4ED8'; this.style.boxShadow='0 0 0 4px rgba(29, 78, 216, 0.07)'"
                       onblur="this.style.borderColor='#DDE2F0'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.02)'">
            </div>
        </div>
    </div>

    <!-- TABLA -->
    <div style="background:white;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden;">
        <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">
                <i class="ti ti-list-check" style="color:#2563eb;margin-right:6px;"></i>Pasantes Registrados
            </h3>
            <span style="background:#eff6ff;color:#2563eb;padding:4px 14px;border-radius:20px;font-size:0.8rem;font-weight:600;"><?= $total ?> pasantes</span>
        </div>

        <?php if (empty($pasantes)): ?>
            <?php if (($totalHistoricos ?? 0) > 0): ?>
            <!-- EMPTY STATE INTELIGENTE: Hay pasantes en el sistema pero no activos en el período actual -->
            <div style="padding: 50px 30px; text-align: center; max-width: 650px; margin: 0 auto;">
                <div style="width: 72px; height: 72px; border-radius: 20px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 8px 20px rgba(37,99,235,0.12);">
                    <i class="ti ti-school" style="font-size: 2.2rem;"></i>
                </div>
                <h4 style="font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0 0 10px;">
                    No hay pasantes activos en el período actual
                </h4>
                <p style="font-size: 0.92rem; color: #64748b; line-height: 1.6; margin: 0 0 24px;">
                    Los <?= (int)$totalHistoricos ?> pasantes registrados en el sistema ya culminaron sus pasantías o corresponden a cohortes académicas anteriores. Puedes consultar sus expedientes, evaluaciones y asistencias desde el módulo de Períodos.
                </p>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="<?= URLROOT ?>/periodos" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: #2563eb; color: #fff; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; box-shadow: 0 4px 14px rgba(37,99,235,0.25); transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                        <i class="ti ti-calendar-event"></i> Ver Historial de Períodos
                    </a>
                    <a href="<?= URLROOT ?>/reportes" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #f8fafc; color: #334155; border: 1.5px solid #e2e8f0; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                        <i class="ti ti-file-analytics"></i> Centro de Reportes
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- EMPTY STATE: Sistema totalmente nuevo sin pasantes creados aún -->
            <div style="padding: 60px 30px; text-align: center; color: #94a3b8;">
                <div style="width: 64px; height: 64px; border-radius: 18px; background: #f8fafc; color: #94a3b8; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                    <i class="ti ti-users-off" style="font-size: 2.2rem;"></i>
                </div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: #475569; margin: 0 0 6px;">No hay pasantes registrados aún</h4>
                <p style="font-size: 0.88rem; color: #94a3b8; margin: 0 0 20px;">Comienza creando pasantes o asignándolos al período académico vigente.</p>
                <button type="button" class="btn btn-primary" onclick="SGPModal.crearPasante()" style="border-radius: 12px; padding: 10px 22px; font-weight: 700;">
                    <i class="ti ti-user-plus"></i> Registrar Primer Pasante
                </button>
            </div>
            <?php endif; ?>
        <?php else: ?>
        <!-- TABLA — solo en pantallas grandes (≥ 992px) -->
        <div class="table-responsive sgp-solo-desktop" style="padding: 20px;">
            <table id="tablaPasantes" class="table table-hover align-middle mb-0" style="width:100%; opacity: 0; transition: opacity 0.4s ease-in-out;">
                <thead class="bg-light text-uppercase text-muted small fw-bold">
                    <tr>
                        <?php foreach (['Pasante','Cédula','Institución','Departamento','Progreso (Días)','Estado','Acciones'] as $th): ?>
                        <th class="px-4 py-3 border-0"><?= $th ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php /** @var object $p */
                foreach ($pasantes as $p):
                    $nombres  = htmlspecialchars(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''));
                    $cedula   = htmlspecialchars($p->cedula ?? '—');
                    $inst     = htmlspecialchars($p->institucion_nombre ?? '—');
                    $instRep  = $p->institucion_representante ? htmlspecialchars($p->institucion_representante) : null;
                    $depto    = htmlspecialchars($p->departamento_nombre ?? 'Sin asignar');
                    $estado   = $p->estado_pasantia ?? 'Pendiente';
                    $horasAcum = (int)($p->horas_acumuladas ?? 0);
                    $horasMeta = (int)($p->horas_meta ?? 1440);
                    $diasAcum = (int)ceil($horasAcum / 8);
                    $diasTotal= (int)ceil($horasMeta / 8);
                    $pct = $horasMeta > 0 ? min(100, round(($horasAcum / $horasMeta) * 100)) : 0;

                    $estadoMap = [
                        'Activo'     => ['bg' => '#eff6ff', 'color' => '#2563eb'],
                        'Pendiente'  => ['bg' => '#fef9c3', 'color' => '#ca8a04'],
                        'Finalizado' => ['bg' => '#dcfce7', 'color' => '#16a34a'],
                        'Retirado'   => ['bg' => '#fee2e2', 'color' => '#dc2626'],
                    ];
                    $cfg = $estadoMap[$estado] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];
                    $inicial = strtoupper(substr($p->nombres ?? 'P', 0, 1));
                ?>
                <tr>
                    <td class="px-4 py-3">
                        <div class="dt-name-cell">
                            <div class="dt-avatar"><?= $inicial ?></div>
                            <div>
                                <span class="dt-cell-primary dt-cell-truncate" title="<?= $nombres ?>"><?= $nombres ?></span><!-- [SGP-FIX-PASANTES-ALTURA-FILA] truncar a 1 línea para igualar altura de fila (mismo patrón que Institución) -->
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted"><?= $cedula ?></td>
                    <td class="px-4 py-3 text-muted">
                        <span class="dt-cell-truncate" title="<?= $inst ?>"><?= $inst ?></span>
                        <?php if($instRep): ?><div style="font-size:0.7rem; color:#94a3b8; margin-top:2px; line-height:1;"><i class="ti ti-user" style="font-size:0.7rem;"></i> Rep: <?= $instRep ?></div><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-muted" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= $depto !== 'Sin asignar' ? $depto : '' ?>"><!-- [SGP-FIX-PASANTES-ANCHO-TABLA] evitar que un depto largo rompa la altura de fila con table-layout:fixed -->
                        <?php if ($depto === 'Sin asignar'): ?>
                            <span onclick="cambiarEstado('<?= UrlSecurity::encrypt($p->id) ?>', '<?= addslashes($nombres) ?>')" title="Sin departamento – clic para gestionar" style="display:inline-flex;align-items:center;gap:4px;background:#fff7ed;color:#ea580c;border:1.5px dashed #fb923c;border-radius:8px;padding:3px 10px;font-size:0.8rem;font-weight:700;cursor:pointer;transition:all 0.2s;" onmouseover="this.style.background='#ea580c';this.style.color='white'" onmouseout="this.style.background='#fff7ed';this.style.color='#ea580c'">
                                <i class="ti ti-map-pin-off" style="font-size:0.85rem;"></i> Sin asignar
                            </span>
                        <?php else: ?>
                            <?= $depto ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <div class="progress-wrap">
                            <div class="progress-label">
                                Día <?= $diasAcum ?> / <?= $diasTotal ?>
                                <span class="progress-pct"><?= $pct ?>%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= $pct ?>%;"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <?php $esAccion = in_array($estado, ['Pendiente','Sin asignar']); ?>
                        <span class="badge badge-<?= strtolower($estado) ?>" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;<?= $esAccion ? 'cursor:pointer;border:1.5px dashed currentColor;' : '' ?>"
                              <?= $esAccion ? "onclick=\"cambiarEstado('" . UrlSecurity::encrypt($p->id) . "', '" . addslashes($nombres) . "')\" title=\"Clic para cambiar estado\"" : '' ?>>
                            <?= $esAccion ? '<i class="ti ti-click" style="font-size:0.75rem;margin-right:2px;"></i>' : '' ?>
                            <?= $estado ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="d-flex justify-content-center gap-2 flex-nowrap dt-row-actions">
                            <button onclick="SGPModal.verUsuario(<?= $p->id ?>)" class="btn-action btn-view" title="Ver perfil">
                                <i class="ti ti-eye"></i>
                            </button>
                            <a href="<?= URLROOT ?>/asistencias/almanaque/<?= $p->id ?>" class="btn-action" style="color:#0ea5e9;background:#e0f2fe;text-decoration:none;" title="Ver Almanaque">
                                <i class="ti ti-calendar-stats"></i>
                            </a>
                            <button onclick="window.abrirModalEditarPasante(<?= $p->id ?>)" class="btn-action btn-edit" title="Editar Datos Personales">
                                <i class="ti ti-edit"></i>
                            </button>
                            <button onclick="cambiarEstado('<?= UrlSecurity::encrypt($p->id) ?>', '<?= $nombres ?>')" class="btn-action btn-config" title="Cambiar Estado">
                                <i class="ti ti-settings"></i>
                            </button>
                            <button onclick="confirmarEliminarPasante(<?= $p->id ?>, '<?= addslashes($nombres) ?>', '<?= addslashes($cedula) ?>')" class="btn-action" style="color:#dc2626;background:#fef2f2;" title="Eliminar Pasante">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- CARD VIEW — solo en móvil (< 992px) -->
        <div class="sgp-solo-mobile gap-3 px-3 pb-3" id="cardsPasantes">
        <?php foreach ($pasantes as $p):
            $nombres   = htmlspecialchars(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''));
            $cedula    = htmlspecialchars($p->cedula ?? '—');
            $inst      = htmlspecialchars($p->institucion_nombre ?? '—');
            $instRep   = $p->institucion_representante ? htmlspecialchars($p->institucion_representante) : null;
            $depto     = htmlspecialchars($p->departamento_nombre ?? 'Sin asignar');
            $estado    = $p->estado_pasantia ?? 'Pendiente';
            $horasAcum = (int)($p->horas_acumuladas ?? 0);
            $horasMeta = (int)($p->horas_meta ?? 1440);
            $diasAcum  = (int)ceil($horasAcum / 8);
            $diasTotal = (int)ceil($horasMeta / 8);
            $pct       = $horasMeta > 0 ? min(100, round(($horasAcum / $horasMeta) * 100)) : 0;
            $estadoMap = [
                'Activo'     => ['bg' => '#eff6ff', 'color' => '#2563eb'],
                'Pendiente'  => ['bg' => '#fef9c3', 'color' => '#ca8a04'],
                'Finalizado' => ['bg' => '#dcfce7', 'color' => '#16a34a'],
                'Retirado'   => ['bg' => '#fee2e2', 'color' => '#dc2626'],
            ];
            $cfg     = $estadoMap[$estado] ?? ['bg' => '#f1f5f9', 'color' => '#64748b'];
            $inicial = strtoupper(substr($p->nombres ?? 'P', 0, 1));
            $pctColor = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
        ?>
        <div class="bento-card-pasante mobile-card-item" data-depto="<?= $depto ?>" data-inst="<?= $inst ?>" data-search="<?= strtolower($nombres . ' ' . $cedula) ?>" data-estado="<?= strtolower($estado) ?>">

            <div class="bcp-header">
                <div class="bcp-avatar"><?= $inicial ?></div>
                <div class="bcp-info">
                    <span class="bcp-nombre"><?= $nombres ?></span>
                    <span class="bcp-cedula">C.I: <?= $cedula ?></span>
                </div>
                <?php $esAccionM = in_array($estado, ['Pendiente','Sin asignar']); ?>
                <span class="bcp-badge" style="background:<?= $cfg['bg'] ?>; color:<?= $cfg['color'] ?>;<?= $esAccionM ? 'cursor:pointer;border:1.5px dashed currentColor;' : '' ?>"
                      <?= $esAccionM ? "onclick=\"cambiarEstado('" . UrlSecurity::encrypt($p->id) . "', '" . addslashes($nombres) . "')\" title=\"Clic para gestionar\"" : '' ?>>
                    <?= $esAccionM ? '<i class="ti ti-click" style="font-size:0.72rem;margin-right:2px;"></i>' : '' ?>
                    <?= $estado ?>
                </span>
            </div>

            <div class="bcp-body">
                <div class="bcp-row">
                    <span class="bcp-label">Institución</span>
                    <span class="bcp-value">
                        <?= $inst ?>
                        <?php if($instRep): ?><div style="font-size:0.7rem;color:#94a3b8;font-weight:500;margin-top:2px;line-height:1;"><i class="ti ti-user"></i> Rep: <?= $instRep ?></div><?php endif; ?>
                    </span>
                </div>
                <div class="bcp-row">
                    <span class="bcp-label">Departamento</span>
                    <span class="bcp-value">
                        <?php if ($depto === 'Sin asignar'): ?>
                            <span onclick="cambiarEstado('<?= UrlSecurity::encrypt($p->id) ?>', '<?= addslashes($nombres) ?>')" style="display:inline-flex;align-items:center;gap:3px;background:#fff7ed;color:#ea580c;border:1.5px dashed #fb923c;border-radius:7px;padding:2px 8px;font-size:0.78rem;font-weight:700;cursor:pointer;" title="Clic para gestionar">
                                <i class="ti ti-map-pin-off" style="font-size:0.8rem;"></i> Sin asignar
                            </span>
                        <?php else: ?>
                            <?= $depto ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="bcp-row">
                    <span class="bcp-label">Progreso</span>
                    <div class="progress bcp-progress" role="progressbar"
                         aria-valuenow="<?= $pct ?>"
                         aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar"
                             style="width:<?= $pct ?>%; background:<?= $pctColor ?>;"></div>
                    </div>
                    <span class="bcp-value-sm">
                        <?= $pct ?>% &nbsp;·&nbsp; Día <?= $diasAcum ?>/<?= $diasTotal ?>
                    </span>
                </div>
            </div>

            <div class="bcp-actions">
                <button class="bcp-btn bcp-btn-outline"
                        onclick="SGPModal.verUsuario(<?= $p->id ?>)"
                        title="Ver perfil">
                    <i class="ti ti-eye"></i> Ver
                </button>
                <a href="<?= URLROOT ?>/asistencias/almanaque/<?= $p->id ?>" class="bcp-btn bcp-btn-outline" style="color:#0ea5e9;border-color:#0ea5e9;text-decoration:none;" title="Almanaque">
                    <i class="ti ti-calendar-stats"></i> Almanaque
                </a>
                <button class="bcp-btn bcp-btn-primary"
                        onclick="window.abrirModalEditarPasante(<?= $p->id ?>)"
                        title="Editar pasante">
                    <i class="ti ti-edit"></i> Editar
                </button>
                <button class="bcp-btn bcp-btn-config bcp-btn-icon"
                        onclick="cambiarEstado('<?= UrlSecurity::encrypt($p->id) ?>', '<?= $nombres ?>')"
                        title="Cambiar estado">
                    <i class="ti ti-settings"></i>
                </button>
                <button class="bcp-btn bcp-btn-icon" style="color:#dc2626;border-color:#fecaca;background:#fef2f2;"
                        onclick="confirmarEliminarPasante(<?= $p->id ?>, '<?= addslashes($nombres) ?>', '<?= addslashes($cedula) ?>')"
                        title="Eliminar pasante">
                    <i class="ti ti-trash"></i>
                </button>
            </div>

        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /dashboard-container -->

<!-- Formulario oculto para eliminar pasante -->
<form id="formEliminarPasante" method="POST" style="display:none;">
    <?php echo CsrfHelper::field(); ?>
</form>

<!-- ======= MODAL: CAMBIAR ESTADO ======= -->
<div id="modalCambiarEstado" class="modal-overlay">
    <div class="modal-box" style="max-width:480px;min-height:auto;">
        <div class="modal-head">
            <div>
                <h2><i class="ti ti-settings" style="margin-right:8px;"></i>Cambiar Estado</h2>
                <p id="txt-nombre-pasante">—</p>
            </div>
            <button class="btn-close-modal" onclick="cerrarModalEstado()"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body" style="padding:24px 32px 32px;">
            <input type="hidden" id="inp-pasante-id">

            <!-- Estado actual badge -->
            <div style="margin-bottom:18px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:.78rem;color:#64748b;font-weight:600;">Estado actual:</span>
                <span id="lbl-estado-actual" style="font-size:.78rem;font-weight:800;padding:3px 12px;border-radius:20px;background:#f1f5f9;color:#334155;">—</span>
            </div>

            <!-- Mensaje estado terminal -->
            <div id="estado-terminal-msg" style="display:none;background:linear-gradient(to right,#f8fafc,#edf2f9);border:1px solid #e2e8f0;border-left:3px solid #94a3b8;border-radius:10px;padding:12px 16px;margin-bottom:18px;display:none;align-items:flex-start;gap:10px;">
                <div style="background:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.08);flex-shrink:0;">
                    <i class="ti ti-lock" style="color:#64748b;font-size:.95rem;"></i>
                </div>
                <p style="margin:0;font-size:.78rem;color:#64748b;line-height:1.55;">
                    Este estado es <strong style="color:#334155;">definitivo</strong>. Una vez que un pasante está
                    <strong>Finalizado</strong> o <strong>Retirado</strong>, no es posible modificar su estado.
                </p>
            </div>

            <!-- Selector de estado -->
            <div id="estado-form-group" class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="font-size:.85rem;color:#1e3a8a;">Nuevo Estado</label>
                <select class="form-input no-choices" id="inp-nuevo-estado" onchange="onEstadoChange(this.value)">
                    <option value="">Seleccione un estado...</option>
                </select>
            </div>

            <!-- Motivo de retiro (solo para Retirado) -->
            <div id="grupo-motivo-retiro" style="display:none;margin-bottom:20px;">
                <div style="background:#fef2f2;border:1px solid #fecaca;border-left:3px solid #dc2626;border-radius:10px;padding:10px 14px;margin-bottom:10px;display:flex;align-items:flex-start;gap:8px;">
                    <i class="ti ti-alert-triangle" style="color:#dc2626;font-size:1rem;margin-top:1px;flex-shrink:0;"></i>
                    <p style="margin:0;font-size:.74rem;color:#b91c1c;line-height:1.5;">
                        <strong>Acción irreversible.</strong> El pasante quedará retirado definitivamente y no podrá registrar asistencias.
                    </p>
                </div>
                <label class="form-label" style="font-size:.85rem;color:#dc2626;">Motivo de Retiro <span style="color:#dc2626;">*</span></label>
                <textarea id="inp-motivo-retiro" class="form-input" rows="3"
                    placeholder="Describe el motivo del retiro..."
                    style="resize:vertical;font-size:.87rem;"></textarea>
            </div>

            <button id="btn-guardar-estado" class="btn-submit" onclick="confirmarCambioEstado()"
                style="padding:12px 20px;font-size:.9rem;width:100%;display:flex;justify-content:center;box-shadow:0 4px 12px rgba(37,99,235,0.2);">
                <i class="ti ti-device-floppy"></i> Guardar Estado
            </button>
        </div>
    </div>
</div>

<!-- ======= MODAL: EDITAR DATOS PERSONALES ======= -->
<div id="modalEditarPasante" class="modal-overlay">
    <div class="modal-box" style="max-width: 520px;">
        <div class="modal-head">
            <div>
                <h2><i class="ti ti-edit" style="margin-right:8px;"></i>Editar Datos Personales</h2>
                <p>Modificar información básica del pasante</p>
            </div>
            <button class="btn-close-modal" onclick="cerrarModalEditarPasante()"><i class="ti ti-x"></i></button>
        </div>
        <div class="modal-body" style="padding: 20px 30px 30px;">
            <input type="hidden" id="edit-pasante-id">

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Nombres</label>
                <input type="text" class="form-input" id="edit-nombres" placeholder="Nombres del pasante" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Apellidos</label>
                <input type="text" class="form-input" id="edit-apellidos" placeholder="Apellidos del pasante" required>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Teléfono</label>
                <input type="text" class="form-input" id="edit-telefono"
                       placeholder="Ej: 0414-7001921"
                       maxlength="12"
                       oninput="validarTelefonoEditar(this)"
                       autocomplete="off">
                <div id="edit-telefono-msg" style="font-size:.74rem;margin-top:4px;min-height:16px;font-weight:600;transition:color .2s;"></div>
            </div>
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Institución de Procedencia</label>
                <select class="form-input no-choices" id="edit-institucion">
                    <option value="">Seleccione una institución</option>
                    <?php if(!empty($instituciones)): foreach($instituciones as $inst): ?>
                        <option value="<?= $inst->id ?>"><?= htmlspecialchars($inst->nombre) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" style="font-size: 0.85rem; color: #1e3a8a;">Departamento asignado</label>
                <select class="form-input no-choices" id="edit-departamento">
                    <option value="">Sin departamento asignado</option>
                    <?php foreach($deptos_lista as $dep): ?>
                        <option value="<?= $dep->id ?>"><?= htmlspecialchars($dep->nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn-submit" onclick="guardarDatosPasante()" style="padding: 12px 20px; font-size: 0.9rem; width: 100%; display: flex; box-shadow: 0 4px 12px rgba(37,99,235,0.2);">
                <i class="ti ti-device-floppy"></i> Guardar Cambios
            </button>
        </div>
    </div>
</div>

<script>
// ── Filtrar por estado desde KPI card ──
function filtrarEstadoKPI(estado) {
    // DataTable
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#tablaPasantes')) {
        var dt = $('#tablaPasantes').DataTable();
        // Si el estado contiene |, es una búsqueda por múltiples estados (regex)
        var busqueda = estado.includes('|') ? estado : '^' + $.fn.dataTable.util.escapeRegex(estado) + '$';
        dt.column(5).search(busqueda, true, false).draw();
    }
    // Mobile cards
    document.querySelectorAll('.mobile-card-item').forEach(function(card) {
        var cardEstado = (card.getAttribute('data-estado') || '').toLowerCase();
        if (estado.includes('|')) {
            var estadosPermitidos = estado.toLowerCase().split('|');
            card.style.display = estadosPermitidos.includes(cardEstado) ? '' : 'none';
        } else {
            card.style.display = (cardEstado === estado.toLowerCase()) ? '' : 'none';
        }
    });
    // Scroll a la tabla
    setTimeout(function() {
        var el = document.querySelector('.table-responsive') || document.getElementById('cardsPasantes');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 150);
}

// ── Filtros y Búsqueda General ──
function aplicarFiltrosPasantes() {
    var depto = document.getElementById('filterDepto') ? document.getElementById('filterDepto').value : '';
    var inst = document.getElementById('filterInst') ? document.getElementById('filterInst').value : '';
    var search = document.getElementById('customSearchPasantes') ? document.getElementById('customSearchPasantes').value : '';

    if ($.fn.DataTable.isDataTable('#tablaPasantes')) {
        var dt = $('#tablaPasantes').DataTable();
        dt.column(3).search(depto ? '^' + $.fn.dataTable.util.escapeRegex(depto) + '$' : '', true, false);
        dt.column(2).search(inst ? '^' + $.fn.dataTable.util.escapeRegex(inst) + '$' : '', true, false);
        dt.search(search);
        dt.draw();
    }

    search = search.toLowerCase();
    depto = depto.toLowerCase();
    inst = inst.toLowerCase();

    document.querySelectorAll('.mobile-card-item').forEach(function(card) {
        var cardDepto = card.getAttribute('data-depto') ? card.getAttribute('data-depto').toLowerCase() : '';
        var cardInst = card.getAttribute('data-inst') ? card.getAttribute('data-inst').toLowerCase() : '';
        var cardSearch = card.getAttribute('data-search') ? card.getAttribute('data-search').toLowerCase() : '';

        var matchDepto = (depto === '' || cardDepto === depto);
        var matchInst = (inst === '' || cardInst === inst);
        var matchSearch = (search === '' || cardSearch.includes(search));

        card.style.display = (matchDepto && matchInst && matchSearch) ? '' : 'none';
    });
}

// ── Editar Datos Personales (modal inline) ──
window.abrirModalEditarPasante = async function(pasanteId) {
    try {
        Swal.fire({ title: 'Cargando datos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const resp = await fetch(URLROOT + '/pasantes/obtenerDatosPersonales/' + pasanteId);
        const json = await resp.json();
        Swal.close();

        if (json.success) {
            const data = json.data;
            document.getElementById('edit-pasante-id').value   = pasanteId;
            document.getElementById('edit-nombres').value      = data.nombres    || '';
            document.getElementById('edit-apellidos').value    = data.apellidos  || '';
            const telInput = document.getElementById('edit-telefono');
            telInput.value = data.telefono || '';
            telInput.style.borderColor = '';
            const telMsg = document.getElementById('edit-telefono-msg');
            if (telMsg) { telMsg.textContent = ''; }
            // Validar el teléfono existente para dar feedback visual
            if (data.telefono) validarTelefonoEditar(telInput);
            document.getElementById('edit-institucion').value  = data.institucion_id          || '';
            document.getElementById('edit-departamento').value = data.departamento_asignado_id || '';
            document.getElementById('modalEditarPasante').classList.add('active');
        } else {
            Swal.fire('Error', json.message, 'error');
        }
    } catch (e) {
        Swal.close();
        Swal.fire('Error', 'Error de conexión', 'error');
    }
};

function cerrarModalEditarPasante() {
    document.getElementById('modalEditarPasante').classList.remove('active');
}

// ── Validación de teléfono venezolano ──────────────────────────
function validarTelefonoEditar(input) {
    const msg  = document.getElementById('edit-telefono-msg');
    const val  = input.value.trim();
    if (!val) {
        input.style.borderColor = '';
        if (msg) { msg.textContent = ''; msg.style.color = ''; }
        return true;
    }
    // Formato aceptado: 04XX-XXXXXXX / 04XXXXXXXXX / 02XX-XXXXXXX / 02XXXXXXXXX
    const soloDigitos = val.replace(/-/g, '');
    const regex = /^(0(4(1[246]|2[46])|[2][0-9]{2}))\d{7}$/;
    const valido = regex.test(soloDigitos) && (soloDigitos.length === 11);

    if (valido) {
        input.style.borderColor = '#10b981';
        if (msg) { msg.textContent = '✓ Número válido'; msg.style.color = '#059669'; }
    } else {
        input.style.borderColor = '#ef4444';
        if (msg) { msg.textContent = 'Formato inválido. Ej: 0414-7001921 (11 dígitos)'; msg.style.color = '#dc2626'; }
    }
    return valido;
}

async function guardarDatosPasante() {
    const pasanteId = document.getElementById('edit-pasante-id').value;
    const nombres   = document.getElementById('edit-nombres').value.trim();
    const apellidos = document.getElementById('edit-apellidos').value.trim();
    const telefono  = document.getElementById('edit-telefono').value.trim();
    const instId    = document.getElementById('edit-institucion').value;
    const deptoId   = document.getElementById('edit-departamento').value;

    if (!nombres || !apellidos) {
        Swal.fire('Campos requeridos', 'Nombres y apellidos son obligatorios.', 'warning');
        return;
    }

    // Validar teléfono si fue ingresado
    if (telefono && !validarTelefonoEditar(document.getElementById('edit-telefono'))) {
        Swal.fire({
            icon: 'warning',
            title: 'Teléfono inválido',
            text: 'El número debe tener 11 dígitos venezolanos. Ej: 0414-7001921',
            confirmButtonColor: '#2563eb'
        });
        document.getElementById('edit-telefono').focus();
        return;
    }

    try {
        const fd = new FormData();
        fd.append('id',               pasanteId);
        fd.append('nombres',          nombres);
        fd.append('apellidos',        apellidos);
        fd.append('telefono',         telefono);
        fd.append('institucion_id',   instId);
        fd.append('departamento_id',  deptoId);

        const resp = await fetch(URLROOT + '/pasantes/actualizarDatos', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await resp.json();

        if (json.success) {
            cerrarModalEditarPasante();
            if (typeof NotificationService !== 'undefined') {
                NotificationService.success('Datos actualizados', json.message || 'Cambios guardados.');
            } else {
                await Swal.fire({ icon: 'success', title: 'Actualizado', text: json.message, confirmButtonColor: '#2563eb' });
            }
            window.location.reload();
        } else {
            Swal.fire('Error', json.message || 'No se pudieron guardar los cambios.', 'error');
        }
    } catch (e) {
        Swal.fire('Error de conexión', 'Intenta de nuevo.', 'error');
    }
}

// Variable global para Choices.js del modal de estado
// choicesEstado eliminado — select nativo con opciones dinámicas habilitadas/deshabilitadas

// ── Cambiar Estado del Pasante ──────────────────────────────────────────
var ESTADO_ICONS = { 'Pendiente':'⏳', 'Activo':'✅', 'Finalizado':'🏆', 'Retirado':'❌' };
var ESTADO_COLORES = {
    'Sin Asignar': { bg:'#f1f5f9', color:'#475569' },
    'Pendiente':   { bg:'#fef9c3', color:'#854d0e' },
    'Activo':      { bg:'#dcfce7', color:'#166534' },
    'Finalizado':  { bg:'#ede9fe', color:'#5b21b6' },
    'Retirado':    { bg:'#fee2e2', color:'#991b1b' },
};

function onEstadoChange(val) {
    document.getElementById('grupo-motivo-retiro').style.display = val === 'Retirado' ? '' : 'none';
    if (val !== 'Retirado') document.getElementById('inp-motivo-retiro').value = '';
}

window.cambiarEstado = async function(encryptedId, nombre) {
    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const resp = await fetch(URLROOT + '/pasantes/obtenerInfoEstado/' + encryptedId);
        const json = await resp.json();
        Swal.close();

        if (!json.success) { Swal.fire('Error', json.message, 'error'); return; }

        // Rellenar cabecera
        document.getElementById('inp-pasante-id').value = encryptedId;
        document.getElementById('txt-nombre-pasante').textContent = 'Pasante: ' + (nombre || '—');

        // Badge estado actual
        const lbl = document.getElementById('lbl-estado-actual');
        const cfg = ESTADO_COLORES[json.estado_actual] || ESTADO_COLORES['Sin Asignar'];
        lbl.textContent = json.estado_actual;
        lbl.style.background = cfg.bg;
        lbl.style.color = cfg.color;

        const terminalMsg  = document.getElementById('estado-terminal-msg');
        const formGroup    = document.getElementById('estado-form-group');
        const btnGuardar   = document.getElementById('btn-guardar-estado');
        const grupoMotivo  = document.getElementById('grupo-motivo-retiro');
        const select       = document.getElementById('inp-nuevo-estado');

        // Resetear
        select.value = '';
        grupoMotivo.style.display = 'none';
        document.getElementById('inp-motivo-retiro').value = '';

        if (json.es_terminal) {
            terminalMsg.style.display = 'flex';
            formGroup.style.display   = 'none';
            btnGuardar.style.display  = 'none';
        } else {
            terminalMsg.style.display = 'none';
            formGroup.style.display   = '';
            btnGuardar.style.display  = '';

            // Solo agregar las transiciones permitidas — sin opciones deshabilitadas
            select.innerHTML = '<option value="">Seleccione un estado...</option>';
            json.transiciones.forEach(function(st) {
                const opt       = document.createElement('option');
                opt.value       = st;
                opt.textContent = (ESTADO_ICONS[st] || '') + ' ' + st;
                select.appendChild(opt);
            });
        }

        document.getElementById('modalCambiarEstado').classList.add('active');

    } catch(e) {
        Swal.close();
        Swal.fire('Error de conexión', 'Intenta de nuevo.', 'error');
    }
};

function cerrarModalEstado() {
    document.getElementById('modalCambiarEstado').classList.remove('active');
}

async function confirmarCambioEstado() {
    const pasanteId   = document.getElementById('inp-pasante-id').value;
    const nuevoEstado = document.getElementById('inp-nuevo-estado').value;
    const motivo      = document.getElementById('inp-motivo-retiro').value.trim();

    if (!nuevoEstado) {
        Swal.fire('Selecciona un estado', 'Elige el nuevo estado antes de guardar.', 'warning');
        return;
    }

    // Confirmación extra para Retirado
    if (nuevoEstado === 'Retirado') {
        if (!motivo) {
            Swal.fire('Motivo requerido', 'Debes indicar el motivo del retiro.', 'warning');
            document.getElementById('inp-motivo-retiro').focus();
            return;
        }
        const confirm = await Swal.fire({
            icon: 'warning',
            title: '¿Confirmar retiro?',
            html: '<p style="color:#475569;font-size:.9rem;line-height:1.6;">Esta acción es <strong style="color:#dc2626;">irreversible</strong>.<br>El pasante quedará retirado definitivamente.</p>',
            showCancelButton: true,
            confirmButtonText: 'Sí, retirar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
        });
        if (!confirm.isConfirmed) return;
    }

    try {
        const fd = new FormData();
        fd.append('pasante_id', pasanteId);
        fd.append('estado',     nuevoEstado);
        fd.append('motivo',     motivo);

        const resp = await fetch(URLROOT + '/pasantes/cambiarEstado', {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await resp.json();

        if (json.success) {
            cerrarModalEstado();
            if (typeof NotificationService !== 'undefined') {
                NotificationService.success('Estado actualizado', json.message);
            } else {
                await Swal.fire({ icon: 'success', title: 'Estado actualizado', text: json.message, confirmButtonColor: '#2563eb' });
            }
            window.location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'No se pudo cambiar', text: json.message, confirmButtonColor: '#2563eb' });
        }
    } catch(err) {
        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'Intenta de nuevo.', confirmButtonColor: '#2563eb' });
    }
}

// ── Eliminación permanente de pasante (doble confirmación) ──
function confirmarEliminarPasante(id, nombre, cedula) {
    Swal.fire({
        title: 'Eliminar pasante',
        html: `<p style="color:#475569;font-size:.95rem;line-height:1.6;">
                 Vas a eliminar permanentemente a <strong>${nombre}</strong><br>
                 <span style="font-size:.85rem;color:#64748b;">C.I: ${cedula}</span>
               </p>
               <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:.83rem;color:#b91c1c;margin-top:12px;text-align:left;">
                 <strong>Se eliminarán todos sus registros:</strong><br>
                 Asistencias · Evaluaciones · Datos académicos · Cuenta de usuario
               </div>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({
            title: 'Confirmación final',
            html: '<p style="color:#475569;font-size:.9rem;">Escribe <strong style="color:#dc2626;">ELIMINAR</strong> para confirmar la eliminación permanente:</p>',
            input: 'text',
            inputPlaceholder: 'ELIMINAR',
            showCancelButton: true,
            confirmButtonText: 'Eliminar definitivamente',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            inputValidator: v => v !== 'ELIMINAR' ? 'Escribe exactamente: ELIMINAR' : null
        }).then(r2 => {
            if (!r2.isConfirmed) return;
            var form = document.getElementById('formEliminarPasante');
            form.action = URLROOT + '/pasantes/eliminar/' + id;
            form.submit();
        });
    });
}

$(document).ready(function() {
    var $tablaPasantes = $('#tablaPasantes');
    if ($tablaPasantes.length && !$.fn.DataTable.isDataTable($tablaPasantes)) {
        $tablaPasantes.DataTable({
            language: {
                url: '<?= URLROOT ?>/assets/libs/datatables/es-ES.json'
            },
            pageLength: 10,
            order: [[0, 'asc']],
            responsive: true,
            dom: '<"top"f>rt<"bottom"ip><"clear">',
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: -1 },
                { responsivePriority: 3, targets: 5 },
                { responsivePriority: 4, targets: 4 },
                { responsivePriority: 8, targets: 3 },
                { responsivePriority: 9, targets: 2 },
                { responsivePriority: 10, targets: 1 },
                { orderable: false, targets: 6 },
                // [SGP-FIX-PASANTES-FILTRO] La columna Institución a veces
                // incluye una segunda línea "Rep: <nombre>" debajo del
                // nombre de la institución. DataTables concatena TODO el
                // texto de la celda para buscar/filtrar, así que esa línea
                // se pegaba al nombre de la institución (ej. "...Velaz"Rep:
                // Ana Gomez") y el filtro por institución (que compara con
                // el nombre exacto) dejaba de encontrar coincidencias — por
                // eso "no funcionaba" el filtro. Aquí se define que, para
                // buscar/ordenar, solo se use el texto del nombre de la
                // institución (el <span class="dt-cell-truncate">),
                // ignorando la línea del representante. La vista (columna
                // visible) no cambia.
                {
                    targets: 2,
                    render: function (data, type, row, meta) {
                        if (type === 'display') return data;
                        var tmp = document.createElement('div');
                        tmp.innerHTML = data;
                        var span = tmp.querySelector('.dt-cell-truncate');
                        return (span ? span.textContent : tmp.textContent).trim();
                    }
                }
            ],
            initComplete: function(settings, json) {
                $(this.api().table().node()).css('opacity', '1');
            }
        });
    } else if ($tablaPasantes.length && $.fn.DataTable.isDataTable($tablaPasantes)) {
        $tablaPasantes.DataTable().draw(false);
    }

    var _dtPasAdjusted = false;
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992 && !_dtPasAdjusted) {
            var dt = $('#tablaPasantes').DataTable();
            if (dt) { dt.columns.adjust().draw(false); }
            _dtPasAdjusted = true;
        }
        if (window.innerWidth < 992) { _dtPasAdjusted = false; }
    });

    var searchInput = document.getElementById('customSearchPasantes');
    if (searchInput) {
        searchInput.addEventListener('input', aplicarFiltrosPasantes);
    }

    document.querySelectorAll('[data-kpi-value]').forEach(function(el) {
        var target = parseInt(el.getAttribute('data-kpi-value')) || 0;
        var current = 0;
        var inc = Math.max(1, Math.floor(target / 15));
        var interval = setInterval(function() {
            current += inc;
            if (current >= target) {
                current = target;
                clearInterval(interval);
            }
            el.textContent = current;
        }, 40);
        if (target === 0) el.textContent = 0;
    });
});
</script>
