<?php
/**
 * Vista: Analíticas del Sistema — Bento Premium
 * Datos reales desde AnaliticasController
 */

// jsJson(): doble protección contra </script> en datos de BD incrustados en <script>.
// JSON_HEX_TAG codifica < y > como </>; str_replace añade <\/ como barrera extra.
function jsJson($data, $extra = 0): string {
    $out = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | $extra);
    return $out === false ? 'null' : str_replace('</', '<\/', $out);
}

$jsPresentes    = jsJson($seriesPresentes    ?? array_fill(0,12,0));
$jsJustificados = jsJson($seriesJustificados ?? array_fill(0,12,0));
$jsAusentes     = jsJson($seriesAusentes     ?? array_fill(0,12,0));
$jsHorasMes     = jsJson($seriesHorasMes     ?? array_fill(0,12,0));
$jsHorasSemana  = jsJson($seriesHorasSemana  ?? []);
$jsDailyByMonth = jsJson($dailyByMonth       ?? []);
$jsHeatmapCal   = jsJson($heatmapCalData     ?? []);
$jsAllCalData   = jsJson($allCalData          ?? []);
$jsMesesSinPeriodo = jsJson($mesesSinPeriodo  ?? []); // [SGP-FEATURE-CAL-SIN-PERIODO]

// Calendario: mes actualmente seleccionado (por defecto: mes actual)
$mesActualNum = (int)date('n');
$anioActual   = (int)date('Y');
// Función helper para calcular metadatos de cualquier mes
function calMeta(int $m, int $y): array {
    return [
        'dias'     => (int)date('t', mktime(0,0,0,$m,1,$y)),
        'primerDow'=> (int)date('N', mktime(0,0,0,$m,1,$y)),
    ];
}
// Precomputar metadatos de los 12 meses para usarlos en JS
$calMetas12 = [];
for ($cm = 1; $cm <= 12; $cm++) {
    $calMetas12[$cm] = calMeta($cm, $anioActual);
}
$jsCalMetas12 = jsJson($calMetas12);
// Mes actual para render PHP inicial
$diasEnMes    = $calMetas12[$mesActualNum]['dias'];
$primerDiaDow = $calMetas12[$mesActualNum]['primerDow'];
$mesesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// Departamentos para donut
$deptLabels = [];
$deptData   = [];
foreach ($porDepartamento ?? [] as $d) {
    $deptLabels[] = $d->departamento ?? 'N/A';
    $deptData[]   = (int)($d->total ?? 0);
}
$jsDeptLabels = jsJson($deptLabels);
$jsDeptData   = jsJson($deptData);

// Pasantes con evaluaciones (para Top 5 filtrable por departamento)
$allPasantesData = [];
foreach ($progresoPorPasante ?? [] as $p) {
    $allPasantesData[] = [
        'nombre'       => mb_substr(trim($p->nombre ?? '—'), 0, 25),
        'progreso'     => (float)($p->progreso_pct ?? 0),
        'horas'        => (int)($p->horas_acumuladas ?? 0),
        'meta'         => (int)($p->horas_meta ?? 1440),
        'departamento' => $p->departamento ?? 'Sin depto',
        'prom_eval'    => (float)($p->prom_eval ?? 0),
        'num_eval'     => (int)($p->num_eval ?? 0),
    ];
}
$jsAllPasantes = jsJson($allPasantesData);
// Departamentos únicos desde distribución (todos los departamentos con pasantes)
$deptosUnicos = array_values(array_filter(
    array_map(fn($d) => $d->departamento ?? '', $porDepartamento ?? []),
    fn($d) => !empty($d)
));
sort($deptosUnicos);

// Gauge: Índice de Salud de Asistencia
$kpiTasaVal  = (float)($kpiTasa ?? 0);
$gaugeColor  = $kpiTasaVal >= 86 ? '#22c55e' : ($kpiTasaVal >= 61 ? '#f59e0b' : '#ef4444');
$gaugeLabel  = $kpiTasaVal >= 86 ? 'Óptimo'  : ($kpiTasaVal >= 61 ? 'Regular'  : 'Crítico');
$gaugeBg     = $kpiTasaVal >= 86 ? '#dcfce7' : ($kpiTasaVal >= 61 ? '#fef3c7'  : '#fee2e2');
$gaugeClrTxt = $kpiTasaVal >= 86 ? '#16a34a' : ($kpiTasaVal >= 61 ? '#d97706'  : '#dc2626');

// Gauge: Índice de Rendimiento Global (compuesto)
$mTasa     = round($kpiTasaVal, 1);
$mActivos  = round(min(100, (($kpiActivos ?? 0) / 50) * 100), 1);
$mProgreso = round((float)($kpiProgreso ?? 0), 1);
$mHoras    = round(min(100, (($kpiHoras ?? 0) / 5000) * 100), 1);
$mEval     = round(min(100, (($kpiEval ?? 0) / 5) * 100), 1);
$rendIndex = round(($mTasa + $mActivos + $mProgreso + $mHoras + $mEval) / 5, 1);
$rendColor = $rendIndex >= 71 ? '#22c55e' : ($rendIndex >= 41 ? '#f59e0b' : '#ef4444');
$rendLabel = $rendIndex >= 71 ? 'Excelente' : ($rendIndex >= 41 ? 'Regular' : 'Bajo');
$rendBg    = $rendIndex >= 71 ? '#dcfce7'  : ($rendIndex >= 41 ? '#fef3c7'  : '#fee2e2');
$rendTxt   = $rendIndex >= 71 ? '#16a34a'  : ($rendIndex >= 41 ? '#d97706'  : '#dc2626');
$metricas  = [
    ['Asistencia', $mTasa,    '#22c55e', '% días presentes (últ. 30 d.)'],
    ['Capacidad',  $mActivos, '#3b82f6', 'Pasantes activos vs. cupo'],
    ['Progreso',   $mProgreso,'#f59e0b', '% horas acumuladas / meta'],
    ['Horas',      $mHoras,   '#6366f1', 'Horas totales vs. referencia'],
    ['Evaluación', $mEval,    '#ec4899', 'Puntuación evaluaciones / 5'],
];
?>

<style>
/* ── BENTO GRID ──────────────────────────────────── */
.bento-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: min-content;
    gap: 20px;
    margin-bottom: 24px;
}
.bento-card {
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 10px 20px rgba(0,0,0,0.02);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.bento-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.08);
}
/* Animations */
.slide-up {
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes slideUp {
    to { opacity: 1; transform: translateY(0); }
}
/* Span helpers */
.col-3 { grid-column: span 3; }
.col-4 { grid-column: span 4; }
.col-5 { grid-column: span 5; }
.col-6 { grid-column: span 6; }
.col-7 { grid-column: span 7; }
.col-8 { grid-column: span 8; }
.col-12 { grid-column: span 12; }

/* ── KPI CARD ─────────────────────────────────────── */
.kpi-bento-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    margin-bottom: 16px;
}
.kpi-bento-value {
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 6px;
}
.kpi-bento-label {
    font-size: 0.82rem;
    color: #64748b;
    font-weight: 500;
    margin-bottom: 10px;
}
.kpi-bento-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
}
.trend-up   { background: #dcfce7; color: #16a34a; }
.trend-down { background: #fee2e2; color: #dc2626; }
.trend-neu  { background: #f1f5f9; color: #64748b; }

/* Accent lines */
.accent-green  { border-left: 4px solid #22c55e; }
.accent-blue   { border-left: 4px solid #162660; }
.accent-amber  { border-left: 4px solid #f59e0b; }
.accent-indigo { border-left: 4px solid #6366f1; }

/* ── CARD HEADER ──────────────────────────────────── */
.bento-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.bento-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
}
.bento-card-title i { font-size: 1.1rem; }
.chart-badge {
    font-size: 0.72rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
    background: #f1f5f9;
    color: #64748b;
}

/* ── TOP 5 RANKING ─────────────────────────────────── */
.ranking-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}
.ranking-item:last-child { border-bottom: none; }
.rank-number {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem;
    font-weight: 900;
    flex-shrink: 0;
}
.rank-1 { background: linear-gradient(135deg,#fbbf24,#f59e0b); color: white; }
.rank-2 { background: linear-gradient(135deg,#94a3b8,#64748b); color: white; }
.rank-3 { background: linear-gradient(135deg,#d97706,#b45309); color: white; }
.rank-other { background: #f1f5f9; color: #475569; }
.rank-avatar {
    width: 40px; height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #162660, #3b82f6);
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; font-size: 0.85rem;
    flex-shrink: 0;
}
.rank-info { flex: 1; min-width: 0; }
.rank-name { font-weight: 700; font-size: 0.88rem; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rank-sub  { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }
.rank-score {
    font-weight: 800;
    font-size: 1rem;
    color: #162660;
    flex-shrink: 0;
}
.rank-progress-bar {
    height: 4px;
    background: #f1f5f9;
    border-radius: 99px;
    margin-top: 6px;
    overflow: hidden;
}
.rank-progress-fill {
    height: 100%;
    border-radius: 99px;
    background: linear-gradient(90deg, #162660, #3b82f6);
    transition: width 1s ease;
}

/* ── RESPONSIVE & LAYOUT FIXES ────────────────────── */
@media (max-width: 1400px) {
    .col-3 { grid-column: span 6; }
    .col-4 { grid-column: span 12; }
    .col-8 { grid-column: span 12; }
    .col-7 { grid-column: span 12; }
    .col-5 { grid-column: span 12; }
}

.dashboard-container {
    width: 100%;
    display: block;
}

.bento-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    grid-auto-rows: min-content;
    gap: 24px;
    margin-bottom: 24px;
    width: 100%;
}

.bento-card {
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    min-width: 0;
    transition: transform 0.3s ease;
}

.bento-card:hover {
    transform: translateY(-5px);
}

.chart-container-bento {
    width: 100%;
    min-height: 250px;
    position: relative;
    overflow: visible;
}

.b-span-3 { grid-column: span 3; }
.b-span-4 { grid-column: span 4; }
.b-span-5 { grid-column: span 5; }
.b-span-7 { grid-column: span 7; }
.b-span-8 { grid-column: span 8; }
.b-span-12 { grid-column: span 12; }

/* [SGP-FIX-KPI-RESPONSIVE-v2] Se deja en 1400px, ahora igualado al mismo
   breakpoint que .dashboard-kpi-grid / .kpi-users-grid / .kpi-pasantes-grid
   en style.css, para que los 4 módulos cambien de 4 a 2 columnas en el
   mismo punto exacto. */
@media (max-width: 1400px) {
    .b-span-3 { grid-column: span 6; }
}
@media (max-width: 900px) {
    .b-span-3, .b-span-4, .b-span-5, .b-span-7, .b-span-8 { grid-column: span 12; }
}
</style>

<div class="dashboard-container slide-up">

    <!-- ===== BANNER ===== -->
    <style>
    @media (max-width: 991px) {
        .dashboard-banner {
            flex-direction: column !important;
            align-items: flex-start !important;
            padding: 24px 20px !important;
            gap: 20px !important;
        }
        .dashboard-banner > div:first-child, .dashboard-banner > div:nth-child(2) {
            display: none !important;
        }
        .dashboard-banner > div:last-child {
            width: 100% !important;
            text-align: left !important;
        }
    }
    </style>
    <div class="dashboard-banner" style="background:linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);border-radius:24px;padding:40px;margin-bottom:32px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;box-shadow: 0 10px 30px rgba(23,37,84,0.15);">
        <div style="position:absolute;top:-40px;right:-40px;width:220px;height:220px;background:rgba(255,255,255,0.05);border-radius:50%;"></div>
        <div style="position:absolute;bottom:-50px;left:-30px;width:180px;height:180px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>
        <div style="display:flex;align-items:center;gap:16px;z-index:1;">
            <div style="background:rgba(255,255,255,0.15);border-radius:16px;padding:14px;">
                <i class="ti ti-chart-dots-3" style="font-size:34px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:white;font-size:1.9rem;font-weight:800;margin:0;letter-spacing:-0.5px;">
                    <?= htmlspecialchars($title ?? 'Analíticas') ?>
                </h1>
                <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:0.9rem;">
                    <i class="ti ti-clock"></i> <?= date('d M Y, H:i') ?> · Datos en tiempo real
                    <?php if (!($isAdmin ?? true)): ?>
                    · <i class="ti ti-filter"></i> Vista de tutor
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div style="z-index:1;text-align:right;">
            <div style="background:rgba(255,255,255,0.12);border-radius:12px;padding:10px 18px;color:rgba(255,255,255,0.9);font-size:0.82rem;font-weight:600;">
                <i class="ti ti-database"></i> <?= number_format($kpiHoras ?? 0) ?> horas<br>
                <span style="opacity:0.7;"><?= ($isAdmin ?? true) ? 'acumuladas en el sistema' : 'de tus pasantes' ?></span>
            </div>
        </div>
    </div>

    <!-- ===== KPIs ROW ===== -->
    <div class="bento-grid">

        <!-- KPI: Tasa de Asistencia -->
        <div class="bento-card b-span-3 accent-green">
            <div class="kpi-bento-icon" style="background:#dcfce7;color:#16a34a;">
                <i class="ti ti-trending-up"></i>
            </div>
            <div class="kpi-bento-value" style="color:#16a34a;" data-kpi-value="<?= $kpiTasa ?? 0 ?>">0<span style="font-size:1.1rem;">%</span></div>
            <div class="kpi-bento-label">Tasa de Asistencia</div>
            <span class="kpi-bento-trend <?= ($kpiTasa??0) >= 80 ? 'trend-up' : (($kpiTasa??0) >= 60 ? 'trend-neu' : 'trend-down') ?>">
                <i class="ti ti-<?= ($kpiTasa??0) >= 80 ? 'arrow-up' : 'minus' ?>"></i> Últimos 30 días
            </span>
        </div>

        <!-- KPI: Pasantes Activos -->
        <div class="bento-card b-span-3 accent-blue">
            <div class="kpi-bento-icon" style="background:#dbeafe;color:#162660;">
                <i class="ti ti-users"></i>
            </div>
            <div class="kpi-bento-value" style="color:#162660;" data-kpi-value="<?= $kpiActivos ?? 0 ?>">0</div>
            <div class="kpi-bento-label">Pasantes Activos</div>
            <span class="kpi-bento-trend trend-neu"><i class="ti ti-activity"></i> En curso</span>
        </div>

        <!-- KPI: Horas Acumuladas -->
        <div class="bento-card b-span-3 accent-indigo">
            <div class="kpi-bento-icon" style="background:#e0e7ff;color:#6366f1;">
                <i class="ti ti-clock-hour-4"></i>
            </div>
            <div class="kpi-bento-value" style="color:#6366f1;" data-kpi-value="<?= $kpiHoras ?? 0 ?>">0</div>
            <div class="kpi-bento-label">Horas Acumuladas</div>
            <span class="kpi-bento-trend trend-up"><i class="ti ti-bolt"></i> Total real</span>
        </div>

        <!-- KPI: Progreso Promedio -->
        <div class="bento-card b-span-3 accent-amber">
            <div class="kpi-bento-icon" style="background:#fef3c7;color:#f59e0b;">
                <i class="ti ti-chart-pie"></i>
            </div>
            <div class="kpi-bento-value" style="color:#f59e0b;" data-kpi-value="<?= $kpiProgreso ?? 0 ?>">0<span style="font-size:1.1rem;">%</span></div>
            <div class="kpi-bento-label">Progreso Promedio</div>
            <span class="kpi-bento-trend trend-neu"><i class="ti ti-target"></i> Sobre meta</span>
        </div>

    </div><!-- /KPIs -->

    <!-- ===== FILA 2: Área + Donut ===== -->
    <div class="bento-grid">

        <!-- Gráfica Área: Asistencias por Mes -->
        <div class="bento-card b-span-8">
            <div class="bento-card-header">
                <div>
                    <div class="bento-card-title">
                        <i class="ti ti-chart-area" style="color:#22c55e;"></i>
                        <span id="asistTitleLabel">Registros de Asistencia <?= date('Y') ?></span>
                    </div>
                    <p style="font-size:0.72rem;color:#94a3b8;margin:2px 0 0;font-weight:500;">
                        <i class="ti ti-info-circle"></i> El eje vertical muestra el número de registros de asistencia del mes. Selecciona un mes para ver el desglose diario.
                    </p>
                </div>
                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                    <button onclick="filtrarAsistencias('anual')" id="btnAsistAnual"
                        style="font-size:0.72rem;padding:3px 10px;border-radius:20px;border:none;cursor:pointer;background:#162660;color:#fff;font-weight:600;">
                        Anual
                    </button>
                    <?php
                    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                    foreach ($meses as $mi => $mn):
                        $mn1    = $mi + 1;
                        $activo = ($mn1 == $mesActualNum)
                            ? 'background:#e0f2fe;color:#0284c7;'
                            : 'background:#f1f5f9;color:#64748b;';
                    ?>
                    <button onclick="filtrarAsistencias(<?= $mn1 ?>)" id="btnAsist<?= $mn1 ?>"
                        style="font-size:0.72rem;padding:3px 10px;border-radius:20px;border:none;cursor:pointer;<?= $activo ?>font-weight:600;">
                        <?= $mn ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="chart-container-bento">
                <div id="chart-area-asistencias"></div>
            </div>
        </div>

        <!-- Gauge: Índice de Salud de Asistencia -->
        <div class="bento-card b-span-4">
            <div class="bento-card-header">
                <div>
                    <div class="bento-card-title">
                        <i class="ti ti-gauge" style="color:<?= $gaugeColor ?>;"></i>
                        Índice de Asistencia
                    </div>
                    <p style="font-size:0.7rem;color:#94a3b8;margin:2px 0 0;">
                        % de días asistidos (últimos 30 días)
                    </p>
                </div>
                <span style="background:<?= $gaugeBg ?>;color:<?= $gaugeClrTxt ?>;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                    <?= $gaugeLabel ?>
                </span>
            </div>
            <div id="chart-gauge-asistencia"></div>
            <!-- Zonas de referencia -->
            <div style="display:flex;justify-content:space-between;margin:-4px 8px 0;font-size:0.68rem;font-weight:700;">
                <span style="color:#ef4444;">🔴 Crítico<br><span style="font-weight:400;opacity:0.8;">0 – 60%</span></span>
                <span style="color:#f59e0b;text-align:center;">🟡 Regular<br><span style="font-weight:400;opacity:0.8;">61 – 85%</span></span>
                <span style="color:#22c55e;text-align:right;">🟢 Óptimo<br><span style="font-weight:400;opacity:0.8;">86 – 100%</span></span>
            </div>
            <p style="text-align:center;font-size:0.7rem;color:#94a3b8;margin-top:10px;">
                <i class="ti ti-info-circle"></i> Valor 100% = todos los pasantes asistieron cada día registrado
            </p>
        </div>

    </div><!-- /Fila 2 -->

    <!-- ===== FILA 3: Departamento + Calendario ===== -->
    <div class="bento-grid">

        <!-- Barras: Por Departamento -->
        <div class="bento-card b-span-4">
            <div class="bento-card-header">
                <div>
                    <div class="bento-card-title">
                        <i class="ti ti-building" style="color:#162660;"></i>
                        Pasantes por Departamento
                    </div>
                    <p style="font-size:0.7rem;color:#94a3b8;margin:2px 0 0;">Pasantes activos y pendientes por área</p>
                </div>
            </div>
            <div style="min-height:180px;">
                <div id="chart-depto-bar"></div>
            </div>
        </div>

        <!-- Calendario de Asistencias (mes actual) -->
        <div class="bento-card b-span-8">
            <div class="bento-card-header">
                <div>
                    <div class="bento-card-title">
                        <i class="ti ti-calendar-stats" style="color:#22c55e;"></i>
                        <span id="cal-title-label">Asistencia — <?= $mesesNombres[$mesActualNum-1] ?></span>
                        <span id="cal-sin-periodo-badge" style="display:none;font-size:0.62rem;font-weight:700;color:#b45309;background:#fef3c7;padding:2px 8px;border-radius:20px;gap:4px;align-items:center;">
                            <i class="ti ti-calendar-off"></i> Sin período activo
                        </span>
                    </div>
                    <p style="font-size:0.7rem;color:#94a3b8;margin:2px 0 0;">
                        <i class="ti ti-info-circle"></i> Cada celda muestra cuántos pasantes registraron asistencia ese día. Selecciona un mes para navegar. Color más intenso = más asistencias.
                    </p>
                </div>
            </div>
            <!-- Selector de mes -->
            <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px;">
                <?php foreach($mesesNombres as $cmi => $cmn):
                    $cmNum = $cmi + 1;
                    $actCal = ($cmNum === $mesActualNum) ? 'background:#162660;color:#fff;' : 'background:#f1f5f9;color:#64748b;';
                ?>
                <button onclick="cambiarMesCal(<?= $cmNum ?>)" id="calBtn<?= $cmNum ?>"
                    style="font-size:0.65rem;padding:2px 7px;border-radius:20px;border:none;cursor:pointer;font-weight:600;<?= $actCal ?>">
                    <?= mb_substr($cmn, 0, 3) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <!-- Grid calendario (renderizado desde JS, inicializado con mes actual) -->
            <div id="cal-header-row" style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:4px;">
                <?php foreach(['L','M','X','J','V','S','D'] as $hd): ?>
                <div style="text-align:center;font-size:0.65rem;font-weight:700;color:#94a3b8;"><?= $hd ?></div>
                <?php endforeach; ?>
            </div>
            <div id="cal-grid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px;"></div>
            <!-- Leyenda -->
            <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;align-items:center;">
                <span style="font-size:0.68rem;color:#94a3b8;font-weight:600;">Asistencias:</span>
                <?php foreach([['#f1f5f9','Sin datos'],['#bbf7d0','1-3'],['#4ade80','4-8'],['#16a34a','>8']] as [$lb,$lt]): ?>
                <span style="display:flex;align-items:center;gap:3px;font-size:0.68rem;color:#64748b;">
                    <span style="width:11px;height:11px;border-radius:3px;background:<?= $lb ?>;display:inline-block;border:1px solid rgba(0,0,0,0.06);"></span><?= $lt ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>


    </div><!-- /Fila 3 -->

    <!-- ===== FILA 4: Por Institución + Tendencia Horas ===== -->
    <div class="bento-grid">

        <!-- Por Institución -->
        <div class="bento-card b-span-4">
            <div class="bento-card-header">
                <div>
                    <div class="bento-card-title">
                        <i class="ti ti-school" style="color:#f59e0b;"></i>
                        Por Institución
                    </div>
                    <p style="font-size:0.7rem;color:#94a3b8;margin:2px 0 0;">Distribución de pasantes por institución de origen</p>
                </div>
                <span class="chart-badge"><?= count($porInstitucion ?? []) ?> institución<?= count($porInstitucion ?? []) !== 1 ? 'es' : '' ?></span>
            </div>
            <?php if (!empty($porInstitucion)): ?>
            <div style="display:flex;flex-direction:column;gap:8px;flex:1;overflow-y:auto;max-height:340px;">
                <?php foreach ($porInstitucion as $inst):
                    $instNombre = $inst->institucion ?? '—';
                    $total      = (int)($inst->total_pasantes ?? 0);
                    $tasa       = $inst->tasa_asistencia ?? null;
                    $isSinEsp   = ($instNombre === 'Sin especificar');
                    $tasaBg     = ($tasa !== null) ? (($tasa >= 80) ? '#dcfce7' : (($tasa >= 60) ? '#fef3c7' : '#fee2e2')) : '#f1f5f9';
                    $tasaClr    = ($tasa !== null) ? (($tasa >= 80) ? '#16a34a' : (($tasa >= 60) ? '#d97706' : '#dc2626')) : '#94a3b8';
                ?>
                <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:12px;border:1px solid #f1f5f9;">
                    <div style="width:36px;height:36px;border-radius:10px;background:<?= $isSinEsp ? '#f1f5f9' : '#fef3c7' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="ti ti-<?= $isSinEsp ? 'question-mark' : 'building-bank' ?>" style="font-size:1.1rem;color:<?= $isSinEsp ? '#94a3b8' : '#f59e0b' ?>;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.82rem;font-weight:<?= $isSinEsp ? '400' : '600' ?>;color:<?= $isSinEsp ? '#94a3b8' : '#1e293b' ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;<?= $isSinEsp ? 'font-style:italic;' : '' ?>">
                            <?= htmlspecialchars($instNombre) ?>
                        </div>
                        <?php if ($tasa !== null): ?>
                        <div style="font-size:0.7rem;color:#94a3b8;margin-top:2px;">
                            Asistencia: <span style="font-weight:700;background:<?= $tasaBg ?>;color:<?= $tasaClr ?>;padding:1px 6px;border-radius:20px;"><?= $tasa ?>%</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;flex-shrink:0;">
                        <span style="font-size:1.1rem;font-weight:800;color:#162660;"><?= $total ?></span>
                        <span style="font-size:0.65rem;color:#94a3b8;">pasante<?= $total !== 1 ? 's' : '' ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (array_filter((array)$porInstitucion, fn($i) => ($i->institucion ?? '') === 'Sin especificar')): ?>
            <p style="font-size:0.68rem;color:#94a3b8;margin:8px 0 0;font-style:italic;">
                * Sin institución asignada en el perfil del pasante.
            </p>
            <?php endif; ?>
            <?php else: ?>
            <div style="flex:1;display:flex;align-items:center;justify-content:center;">
                <p style="color:#94a3b8;font-size:0.85rem;text-align:center;"><i class="ti ti-building-bank" style="display:block;font-size:2rem;margin-bottom:8px;"></i>Sin datos de instituciones</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tendencia Horas por Mes / Semana -->
        <div class="bento-card b-span-8">
            <div class="bento-card-header">
                <div class="bento-card-title">
                    <i class="ti ti-chart-line" style="color:#6366f1;"></i>
                    <span id="horasTendTitle">Tendencia de Horas — <?= date('Y') ?></span>
                </div>
                <div style="display:flex;gap:6px;align-items:center;">
                    <button onclick="toggleHorasTend('mes')" id="btnHorasMes"
                        style="font-size:0.72rem;padding:3px 10px;border-radius:20px;border:none;cursor:pointer;background:#162660;color:#fff;font-weight:600;">
                        Mensual
                    </button>
                    <button onclick="toggleHorasTend('semana')" id="btnHorasSem"
                        style="font-size:0.72rem;padding:3px 10px;border-radius:20px;border:none;cursor:pointer;background:#f1f5f9;color:#64748b;font-weight:600;">
                        Semanal
                    </button>
                </div>
            </div>
            <div class="chart-container-bento">
                <div id="chart-horas-tendencia"></div>
            </div>
        </div>

    </div><!-- /Fila 4 -->

    <!-- ===== FILA 5: TOP 5 + Radar ===== -->
    <div class="bento-grid">

        <!-- TOP 5 Pasantes Destacados -->
        <div class="bento-card b-span-7">
            <div class="bento-card-header">
                <div>
                    <div class="bento-card-title">
                        <i class="ti ti-trophy" style="color:#f59e0b;font-size:1.3rem;"></i>
                        <span id="top5-title">Top 5 Mejor Evaluados</span>
                    </div>
                    <p style="font-size:0.7rem;color:#94a3b8;margin:2px 0 0;">
                        <i class="ti ti-star"></i> Clasificados por promedio de evaluaciones. Filtra por departamento con los botones.
                    </p>
                </div>
            </div>
            <!-- Filtro por departamento -->
            <div id="top5-filters" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;">
                <button onclick="filtrarTop5('__todos__')" data-top5-depto="__todos__"
                    style="font-size:0.72rem;padding:3px 10px;border-radius:20px;border:none;cursor:pointer;background:#162660;color:#fff;font-weight:600;">
                    Todos
                </button>
                <?php foreach ($deptosUnicos as $dep): if (!$dep) continue; ?>
                <button onclick="filtrarTop5(<?= htmlspecialchars(json_encode($dep, JSON_HEX_TAG), ENT_QUOTES) ?>)" data-top5-depto="<?= htmlspecialchars($dep) ?>"
                    style="font-size:0.72rem;padding:3px 10px;border-radius:20px;border:none;cursor:pointer;background:#f1f5f9;color:#64748b;font-weight:600;">
                    <?= htmlspecialchars($dep) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <!-- Lista renderizada por JS -->
            <div id="top5-lista"></div>
            <!-- Paginación Top 5 -->
            <div id="top5-paginacion" style="display:none;align-items:center;justify-content:space-between;margin-top:10px;padding-top:8px;border-top:1px solid #f1f5f9;gap:8px;"></div>
        </div>

        <!-- Gauge: Índice de Rendimiento Global -->
        <?php $hayPasantesActivos = (int)($kpiActivos ?? 0) > 0; ?>
        <div class="bento-card b-span-5">
            <div class="bento-card-header">
                <div class="bento-card-title">
                    <i class="ti ti-gauge" style="color:<?= $hayPasantesActivos ? $rendColor : '#94a3b8' ?>;"></i>
                    Índice de Rendimiento
                </div>
                <?php if ($hayPasantesActivos): ?>
                <span style="background:<?= $rendBg ?>;color:<?= $rendTxt ?>;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                    <?= $rendLabel ?>
                </span>
                <?php else: ?>
                <span style="background:#f1f5f9;color:#64748b;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                    Sin cohorte activa
                </span>
                <?php endif; ?>
            </div>

            <?php if ($hayPasantesActivos): ?>
            <!-- Velocímetro ECharts (canvas sin texto interno) -->
            <div id="echarts-rend-gauge" style="width:100%;height:185px;margin-bottom:0;"></div>

            <!-- Texto del valor FUERA del canvas -->
            <div style="text-align:center;margin:-18px 0 12px;line-height:1.2;">
                <div id="rend-valor-txt"
                     style="font-size:2rem;font-weight:800;font-family:'Inter',sans-serif;color:<?= $rendColor ?>;">
                    <?= $rendIndex ?>%
                </div>
                <div style="font-size:0.68rem;font-weight:700;color:#94a3b8;letter-spacing:0.08em;text-transform:uppercase;margin-top:2px;">
                    Rendimiento Global
                </div>
            </div>

            <!-- Divisor -->
            <div style="height:1px;background:#f1f5f9;margin:0 0 12px;"></div>

            <!-- Métricas individuales -->
            <?php foreach ($metricas as [$mNombre, $mVal, $mClr, $mDesc]): ?>
            <div style="margin-bottom:8px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;">
                    <span style="font-size:0.72rem;color:#1e293b;font-weight:600;"><?= $mNombre ?></span>
                    <span style="font-size:0.7rem;font-weight:700;color:<?= $mClr ?>;font-variant-numeric:tabular-nums;"><?= $mVal ?>%</span>
                </div>
                <div style="height:5px;background:#f1f5f9;border-radius:10px;overflow:hidden;">
                    <div style="width:<?= min(100,$mVal) ?>%;height:100%;background:<?= $mClr ?>;border-radius:10px;"></div>
                </div>
                <div style="font-size:0.62rem;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars($mDesc) ?></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <!-- [SGP-FEATURE-RENDIMIENTO-SIN-COHORTE] Sin pasantes activos: el índice
                 compuesto siempre daría 0% / "Bajo", lo cual sugiere erróneamente un
                 mal desempeño en vez de la ausencia real de una cohorte en curso. -->
            <div style="text-align:center;padding:40px 12px;color:#94a3b8;">
                <i class="ti ti-users-group" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.4;"></i>
                <p style="font-weight:700;color:#475569;font-size:0.9rem;margin:0 0 6px;">Sin pasantes activos</p>
                <p style="font-size:0.75rem;margin:0;max-width:260px;margin-inline:auto;">No hay una cohorte de pasantías en curso en este momento. El índice se calculará automáticamente cuando se activen pasantes.</p>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /Fila 5 -->

</div><!-- /dashboard-container -->

<script>
(function initAnaliticasCharts() {
    // PJAX-safe: runs immediately when the script is executed by execInlineScripts(),
    // but wraps in setTimeout(0) to ensure DOM elements are in the live document.
    // Use polling to ensure scripts (ApexCharts/echarts) loaded in the footer are ready.
    if (typeof ApexCharts === 'undefined' || typeof echarts === 'undefined') {
        setTimeout(initAnaliticasCharts, 50);
        return;
    }

    setTimeout(function () {

    // Delay para asegurar dimensiones del contenedor
    setTimeout(() => {
        window.dispatchEvent(new Event('resize'));
    }, 300);

    const PALETTE = ['#162660','#3b82f6','#22c55e','#f59e0b','#ef4444','#6366f1','#ec4899','#14b8a6'];
    const FONT    = 'Inter, system-ui, sans-serif';
    const GRID_CLR = '#f1f5f9';

    const chartOptions = {
        fontFamily: FONT,
        chart: {
            toolbar: { show: false },
            animations: { enabled: true, speed: 400, animateGradually: { enabled: true, delay: 10 } }
        },
        grid: { borderColor: GRID_CLR, strokeDashArray: 4 }
    };

    // ── 1. GAUGE: Índice de Salud de Asistencia ──────────────────
    const gaugeVal   = Number(<?= $kpiTasaVal ?>);
    const gaugeColor = gaugeVal >= 86 ? '#22c55e' : gaugeVal >= 61 ? '#f59e0b' : '#ef4444';
    new ApexCharts(document.getElementById('chart-gauge-asistencia'), {
        chart: {
            type: 'radialBar',
            height: 240,
            toolbar: { show: false },
            animations: { enabled: true, speed: 900, easing: 'easeinout' }
        },
        series: [gaugeVal],
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                hollow: { size: '62%', background: 'transparent' },
                track: {
                    background: '#f1f5f9',
                    strokeWidth: '100%',
                    dropShadow: { enabled: false }
                },
                dataLabels: {
                    name: {
                        show: true,
                        offsetY: -8,
                        color: '#94a3b8',
                        fontSize: '11px',
                        fontFamily: FONT,
                        fontWeight: 600
                    },
                    value: {
                        show: true,
                        offsetY: 6,
                        color: gaugeColor,
                        fontSize: '2rem',
                        fontWeight: 800,
                        fontFamily: FONT,
                        formatter: v => v + '%'
                    }
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                type: 'horizontal',
                gradientToColors: [gaugeVal >= 86 ? '#16a34a' : gaugeVal >= 61 ? '#d97706' : '#b91c1c'],
                stops: [0, 100]
            }
        },
        stroke: { lineCap: 'round' },
        labels: ['Tasa de Asistencia'],
        colors: [gaugeColor]
    }).render();

    // ── 2. BARRAS: Pasantes por Departamento ─────────────────────
    const deptLabels   = <?= $jsDeptLabels ?>;
    const deptData     = <?= $jsDeptData ?>;
    const deptEl       = document.getElementById('chart-depto-bar');
    if (deptLabels.length > 0 && deptEl) {
        const deptTotal  = deptData.reduce((a, b) => a + b, 0);
        const deptMax    = Math.max(...deptData.filter(v => v > 0), 1);
        // Barras fantasma: 30% del máximo para departamentos sin pasantes (efecto visual)
        const phantom    = Math.max(1, Math.round(deptMax * 0.30));
        const chartVals  = deptData.map(v => v === 0 ? phantom : v);
        const isPhantom  = deptData.map(v => v === 0);

        new ApexCharts(deptEl, {
            ...chartOptions,
            chart: { ...chartOptions.chart, type: 'bar', height: Math.max(200, deptLabels.length * 50) },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '58%',
                    borderRadius: 8,
                    distributed: true,
                    dataLabels: { position: 'center' }
                }
            },
            series: [{ name: 'Pasantes', data: deptLabels.map((l, i) => ({ x: l, y: chartVals[i] })) }],
            colors: isPhantom.map((ph, i) => ph
                ? (PALETTE[i % PALETTE.length] + '40')   // color propio al 25% opacidad (hex alpha)
                : PALETTE[i % PALETTE.length]
            ),
            dataLabels: {
                enabled: true,
                formatter: (v, { dataPointIndex }) => {
                    if (isPhantom[dataPointIndex]) return 'Sin pasantes';
                    const real = deptData[dataPointIndex];
                    const pct  = deptTotal > 0 ? Math.round(real / deptTotal * 100) : 0;
                    return `${real}  (${pct}%)`;
                },
                style: {
                    fontSize: '11px', fontFamily: FONT, fontWeight: 700,
                    colors: isPhantom.map(ph => ph ? '#94a3b8' : '#fff')
                }
            },
            xaxis: {
                max: deptMax + 1,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { labels: { style: {
                colors: isPhantom.map(ph => ph ? '#94a3b8' : '#1e293b'),
                fontSize: '12px', fontFamily: FONT, fontWeight: 700
            }}},
            legend: { show: false },
            grid: { show: false },
            tooltip: {
                theme: 'light',
                y: { formatter: (v, { dataPointIndex }) => {
                    if (isPhantom[dataPointIndex]) return 'Sin pasantes asignados';
                    const real = deptData[dataPointIndex];
                    const pct  = deptTotal > 0 ? Math.round(real / deptTotal * 100) : 0;
                    return `${real} pasante${real !== 1 ? 's' : ''} · ${pct}% del total`;
                }}
            }
        }).render();
    } else if (deptEl) {
        deptEl.innerHTML = '<p style="text-align:center;padding:30px;color:#94a3b8;font-size:0.85rem;">Sin departamentos registrados</p>';
    }

    // ── 3. ÁREA filtrable: Asistencias por mes/día ───────────────
    const dailyByMonth = <?= $jsDailyByMonth ?>;
    const MESES_LABEL  = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    const asistSeriesAnual = [
        { name: 'Presentes',    data: Object.values(<?= $jsPresentes ?>).map(v => Number(v)||0) },
        { name: 'Justificados', data: Object.values(<?= $jsJustificados ?>).map(v => Number(v)||0) },
        { name: 'Ausentes',     data: Object.values(<?= $jsAusentes ?>).map(v => Number(v)||0) }
    ];

    let chartAsist = new ApexCharts(document.getElementById('chart-area-asistencias'), {
        ...chartOptions,
        chart: { ...chartOptions.chart, type: 'area', height: 290 },
        series: asistSeriesAnual,
        colors: ['#22c55e','#f59e0b','#ef4444'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.5, opacityTo: 0.1 } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: {
            categories: MESES_LABEL,
            axisBorder: { show: false },
            axisTicks:  { show: false },
            labels: { style: { colors: '#94a3b8', fontFamily: FONT } }
        },
        yaxis: { labels: { style: { colors: '#94a3b8', fontFamily: FONT } } },
        legend: { position: 'top', horizontalAlign: 'right', fontFamily: FONT },
        tooltip: { theme: 'light', x: { show: true } }
    });
    chartAsist.render();

    window.filtrarAsistencias = function(mes) {
        // Highlight active button
        document.querySelectorAll('[id^="btnAsist"]').forEach(b => {
            b.style.background = '#f1f5f9'; b.style.color = '#64748b';
        });
        const activeBtn = mes === 'anual' ? document.getElementById('btnAsistAnual') : document.getElementById('btnAsist' + mes);
        if (activeBtn) { activeBtn.style.background = '#162660'; activeBtn.style.color = '#fff'; }

        if (mes === 'anual') {
            document.getElementById('asistTitleLabel').textContent = 'Asistencias <?= date('Y') ?> — Vista anual';
            chartAsist.updateOptions({ xaxis: { categories: MESES_LABEL } });
            chartAsist.updateSeries(asistSeriesAnual);
            return;
        }
        const m = String(mes);
        const md = dailyByMonth[m] || { dias: [], presentes: [], justificados: [], ausentes: [] };
        document.getElementById('asistTitleLabel').textContent =
            'Asistencias — ' + MESES_LABEL[parseInt(m)-1] + ' (días)';
        chartAsist.updateOptions({ xaxis: { categories: (md.dias || []).map(String) } });
        chartAsist.updateSeries([
            { name: 'Presentes',    data: md.presentes    || [] },
            { name: 'Justificados', data: md.justificados || [] },
            { name: 'Ausentes',     data: md.ausentes     || [] }
        ]);
    };

    // ── 4. LÍNEA: Tendencia Horas (mes/semana toggle) ─────────────
    const horasMesData   = { cats: MESES_LABEL, vals: <?= $jsHorasMes ?> };
    const horasSemData   = <?= $jsHorasSemana ?>;
    const horasSemCats   = horasSemData.map(r => r.semana);
    const horasSemVals   = horasSemData.map(r => r.horas);

    const horasBaseOpts = {
        ...chartOptions,
        chart: { ...chartOptions.chart, type: 'line', height: 240 },
        colors: ['#6366f1'],
        stroke: { curve: 'smooth', width: 4 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0 } },
        markers: { size: 4, colors: ['#6366f1'], strokeColors: '#fff', strokeWidth: 2 },
        dataLabels: { enabled: false },
        yaxis: {
            labels: { style: { colors: '#94a3b8', fontSize: '11px', fontFamily: FONT }, formatter: v => v.toLocaleString() }
        },
        tooltip: { theme: 'light', y: { formatter: v => v.toLocaleString() + ' hrs' } }
    };

    let chartHoras = new ApexCharts(document.getElementById('chart-horas-tendencia'), {
        ...horasBaseOpts,
        series: [{ name: 'Horas', data: horasMesData.vals }],
        xaxis: {
            categories: horasMesData.cats,
            axisBorder: { show: false }, axisTicks: { show: false },
            labels: { style: { colors: '#94a3b8', fontSize: '12px', fontFamily: FONT } }
        }
    });
    chartHoras.render();

    window.toggleHorasTend = function(modo) {
        document.getElementById('btnHorasMes').style.background = modo === 'mes' ? '#162660' : '#f1f5f9';
        document.getElementById('btnHorasMes').style.color      = modo === 'mes' ? '#fff' : '#64748b';
        document.getElementById('btnHorasSem').style.background = modo === 'semana' ? '#162660' : '#f1f5f9';
        document.getElementById('btnHorasSem').style.color      = modo === 'semana' ? '#fff' : '#64748b';
        if (modo === 'mes') {
            document.getElementById('horasTendTitle').textContent = 'Tendencia de Horas — <?= date('Y') ?>';
            chartHoras.updateOptions({ xaxis: { categories: horasMesData.cats } });
            chartHoras.updateSeries([{ name: 'Horas', data: horasMesData.vals }]);
        } else {
            document.getElementById('horasTendTitle').textContent = 'Tendencia Horas por Semana';
            chartHoras.updateOptions({ xaxis: { categories: horasSemCats } });
            chartHoras.updateSeries([{ name: 'Horas', data: horasSemVals }]);
        }
    };

    // ── 5. DATOS: Pasantes con evaluaciones (para Top 5) ─────────
    const allPasantes = <?= $jsAllPasantes ?>;
    const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    // ── 6. VELOCÍMETRO ECharts: Índice de Rendimiento Global ──────
    const rendVal    = Number(<?= $rendIndex ?>);
    const rendValClr = rendVal >= 71 ? '#10b981' : rendVal >= 41 ? '#f59e0b' : '#ef4444';
    const rendGaugeEl = document.getElementById('echarts-rend-gauge');
    if (rendGaugeEl && typeof echarts !== 'undefined') {
        const rendChart = echarts.init(rendGaugeEl);
        rendChart.setOption({
            backgroundColor: 'transparent',
            series: [
                // ── Pista de fondo (track) ──
                {
                    type: 'gauge',
                    startAngle: 210, endAngle: -30,
                    min: 0, max: 100,
                    radius: '80%', center: ['50%', '58%'],
                    splitNumber: 0,
                    axisLine: {
                        lineStyle: {
                            width: 12,
                            color: [[1, '#e2e8f0']]
                        }
                    },
                    axisTick:  { show: false },
                    splitLine: { show: false },
                    axisLabel: { show: false },
                    pointer:   { show: false },
                    anchor:    { show: false },
                    detail:    { show: false },
                    title:     { show: false }
                },
                // ── Arco de progreso con zonas ──
                {
                    type: 'gauge',
                    startAngle: 210, endAngle: -30,
                    min: 0, max: 100,
                    radius: '80%', center: ['50%', '58%'],
                    splitNumber: 10,
                    axisLine: {
                        lineStyle: {
                            width: 12,
                            color: [
                                [0.40, '#ef4444'],
                                [0.70, '#f59e0b'],
                                [1.00, '#10b981']
                            ]
                        }
                    },
                    axisTick: {
                        show: true,
                        distance: -16,
                        length: 4,
                        lineStyle: { color: 'rgba(255,255,255,0.7)', width: 1.5 }
                    },
                    splitLine: {
                        show: true,
                        distance: -18,
                        length: 10,
                        lineStyle: { color: 'rgba(255,255,255,0.9)', width: 2.5 }
                    },
                    axisLabel: {
                        show: true,
                        distance: 20,
                        color: '#94a3b8',
                        fontSize: 10,
                        fontFamily: 'Inter, sans-serif',
                        fontWeight: 600,
                        formatter: v => (v === 0 || v === 40 || v === 70 || v === 100) ? v : ''
                    },
                    pointer: {
                        show: true,
                        length: '62%',
                        width: 4,
                        offsetCenter: [0, 0],
                        itemStyle: {
                            color: '#1e293b',
                            shadowColor: 'rgba(0,0,0,0.25)',
                            shadowBlur: 8,
                            shadowOffsetY: 4
                        }
                    },
                    anchor: {
                        show: true,
                        showAbove: true,
                        size: 14,
                        itemStyle: {
                            color: '#1e293b',
                            borderColor: '#fff',
                            borderWidth: 3,
                            shadowColor: 'rgba(0,0,0,0.2)',
                            shadowBlur: 6
                        }
                    },
                    title:  { show: false },
                    detail: { show: false },
                    data: [{ value: rendVal }]
                }
            ]
        });
        // Colorear el texto HTML con el color de la zona
        const rendTxtEl = document.getElementById('rend-valor-txt');
        if (rendTxtEl) rendTxtEl.style.color = rendValClr;

        window.addEventListener('resize', () => rendChart.resize());
    }

    // ── 8. CALENDARIO: renderizado dinámico por mes ──────────────
    const allCalData  = <?= $jsAllCalData ?>;
    const calMetas12  = <?= $jsCalMetas12 ?>;
    const CAL_COLORS  = ['#f1f5f9','#bbf7d0','#4ade80','#16a34a'];
    const MESES_ES    = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                         'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    // [SGP-FEATURE-CAL-SIN-PERIODO] Meses (1-12) del año actual sin ningún
    // período de pasantía activo — se muestran con badge + estado vacío
    // en lugar de una cuadrícula en blanco que parece un error.
    const MESES_SIN_PERIODO = <?= $jsMesesSinPeriodo ?>;

    function renderCalGrid(mes) {
        const grid     = document.getElementById('cal-grid');
        if (!grid) return;
        const headerRow = document.getElementById('cal-header-row');
        const badge     = document.getElementById('cal-sin-periodo-badge');
        const sinPeriodo = MESES_SIN_PERIODO.includes(mes);

        if (badge) badge.style.display = sinPeriodo ? 'inline-flex' : 'none';
        if (headerRow) headerRow.style.display = sinPeriodo ? 'none' : 'grid';

        if (sinPeriodo) {
            grid.style.display = 'block';
            grid.innerHTML = `
                <div style="text-align:center;padding:28px 12px;color:#94a3b8;">
                    <i class="ti ti-calendar-off" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4;"></i>
                    <p style="font-weight:700;color:#475569;font-size:0.82rem;margin:0 0 4px;">Sin período de pasantías activo</p>
                    <p style="font-size:0.72rem;margin:0;">No hubo cohortes cursando en ${MESES_ES[mes - 1]}. Esto es normal entre períodos académicos.</p>
                </div>`;
            return;
        }
        grid.style.display = 'grid';

        const meta     = calMetas12[String(mes)] || { dias: 31, primerDow: 1 };
        const diasMes  = meta.dias;
        const offset   = meta.primerDow - 1;   // 0=Lun … 6=Dom
        const dayData  = allCalData[String(mes)] || {};
        let html = '';
        // Celdas vacías hasta el primer día
        for (let i = 0; i < offset; i++) {
            html += '<div style="height:22px;"></div>';
        }
        for (let d = 1; d <= diasMes; d++) {
            const info  = dayData[d];
            const total = info ? info.total : 0;
            const col   = total > 8 ? CAL_COLORS[3] : total > 3 ? CAL_COLORS[2] : total > 0 ? CAL_COLORS[1] : CAL_COLORS[0];
            const title = total > 0 ? `${total} asistencia${total > 1 ? 's' : ''}` : 'Sin asistencias';
            html += `<div title="${title}" style="height:22px;border-radius:4px;background:${col};display:flex;align-items:center;justify-content:center;font-size:0.62rem;font-weight:600;color:${total>3?'#fff':'#64748b'};cursor:default;border:1px solid rgba(0,0,0,0.04);">${d}</div>`;
        }
        grid.innerHTML = html;
    }

    window.cambiarMesCal = function(mes) {
        // Actualizar botones
        for (let m = 1; m <= 12; m++) {
            const btn = document.getElementById('calBtn' + m);
            if (btn) { btn.style.background = '#f1f5f9'; btn.style.color = '#64748b'; }
        }
        const activeBtn = document.getElementById('calBtn' + mes);
        if (activeBtn) { activeBtn.style.background = '#162660'; activeBtn.style.color = '#fff'; }
        // Actualizar título
        const titleEl = document.getElementById('cal-title-label');
        if (titleEl) titleEl.textContent = 'Asistencia — ' + MESES_ES[mes - 1];
        renderCalGrid(mes);
    };

    // Render inicial del calendario con el mes actual
    renderCalGrid(<?= $mesActualNum ?>);

    // ── 9. TOP 5 por Evaluación, filtrable por departamento ───────
    const RANK_GRADS = [
        'linear-gradient(135deg,#162660,#3b82f6)',
        'linear-gradient(135deg,#6366f1,#8b5cf6)',
        'linear-gradient(135deg,#059669,#10b981)',
        'linear-gradient(135deg,#f59e0b,#ef4444)',
        'linear-gradient(135deg,#64748b,#94a3b8)'
    ];
    const RANK_CLASSES = ['rank-1','rank-2','rank-3','rank-other','rank-other'];
    const TOP_PAGE_SIZE = 5;

    // Estado de paginación: persiste entre llamadas para soportar navegar páginas
    let _top5State = { depto: '__todos__', page: 0 };

    function renderTop5(depto, page) {
        // Al cambiar de filtro siempre volver a página 0
        if (depto !== _top5State.depto) page = 0;
        _top5State = { depto, page: page || 0 };

        // Resaltar botón de filtro activo
        document.querySelectorAll('[data-top5-depto]').forEach(b => {
            const active = b.dataset.top5Depto === depto;
            b.style.background = active ? '#162660' : '#f1f5f9';
            b.style.color      = active ? '#fff'    : '#64748b';
        });

        // Dataset filtrado y ordenado completo (sin recortar aún)
        const todos = (depto === '__todos__' ? allPasantes : allPasantes.filter(p => p.departamento === depto))
            .filter(p => p.num_eval > 0)
            .sort((a, b) => b.prom_eval - a.prom_eval || b.progreso - a.progreso);

        const totalItems = todos.length;
        const totalPages = Math.max(1, Math.ceil(totalItems / TOP_PAGE_SIZE));
        const safePage   = Math.min(_top5State.page, totalPages - 1);
        _top5State.page  = safePage;

        const offset = safePage * TOP_PAGE_SIZE;
        const lista  = todos.slice(offset, offset + TOP_PAGE_SIZE);

        // Título dinámico
        const titleEl = document.getElementById('top5-title');
        if (titleEl) {
            const label = depto === '__todos__' ? 'Todos' : depto;
            titleEl.textContent = totalItems > 0
                ? `Top · ${label} (${totalItems} evaluado${totalItems !== 1 ? 's' : ''})`
                : `Top 5 · ${label}`;
        }

        const container = document.getElementById('top5-lista');
        const pagEl     = document.getElementById('top5-paginacion');
        if (!container) return;

        // Estado vacío
        if (!lista.length) {
            if (pagEl) pagEl.style.display = 'none';
            const hayEvals = allPasantes.some(p => p.num_eval > 0);
            container.innerHTML = hayEvals
                ? '<div style="text-align:center;padding:40px;color:#94a3b8;"><i class="ti ti-trophy-off" style="font-size:32px;display:block;margin-bottom:10px;"></i>Sin pasantes evaluados en este departamento.</div>'
                : `<div style="text-align:center;padding:48px 24px;">
                    <div style="display:inline-flex;align-items:center;justify-content:center;width:60px;height:60px;border-radius:50%;background:#eff6ff;border:2px solid #bfdbfe;margin-bottom:14px;">
                        <i class="ti ti-clipboard-off" style="font-size:1.8rem;color:#2563eb;"></i>
                    </div>
                    <div style="font-size:0.95rem;font-weight:700;color:#1e3a8a;margin-bottom:6px;">Sin evaluaciones registradas</div>
                    <p style="color:#94a3b8;font-size:0.82rem;margin:0;max-width:280px;margin-inline:auto;line-height:1.5;">
                        El ranking se activará cuando los pasantes sean evaluados por sus tutores.
                    </p>
                </div>`;
            return;
        }

        // Renderizar items de la página actual (rank global continuo)
        container.innerHTML = lista.map((p, i) => {
            const rank      = offset + i + 1;
            const gradIdx   = i < RANK_GRADS.length ? i : RANK_GRADS.length - 1;
            const grad      = RANK_GRADS[gradIdx];
            const rankCls   = rank <= 3 ? `rank-${rank}` : 'rank-other';
            const partes    = p.nombre.trim().split(/\s+/);
            const iniciales = ((partes[0] || 'P')[0] + (partes[1] ? partes[1][0] : '')).toUpperCase();
            const evalStr   = p.prom_eval.toFixed(1);
            const evalSub   = `${p.num_eval} eval.`;
            const stars     = Math.round(p.prom_eval * 2) / 2;
            let starsHtml   = '';
            for (let s = 1; s <= 5; s++) {
                if (s <= Math.floor(stars))
                    starsHtml += '<i class="ti ti-star-filled" style="font-size:11px;color:#f59e0b;"></i>';
                else if ((s - 0.5) === stars)
                    starsHtml += '<i class="ti ti-star-half-filled" style="font-size:11px;color:#f59e0b;"></i>';
                else
                    starsHtml += '<i class="ti ti-star" style="font-size:11px;color:#e2e8f0;"></i>';
            }
            return `
            <div class="ranking-item">
                <div class="rank-number ${rankCls}">${rank}</div>
                <div class="rank-avatar" style="background:${grad};">${iniciales}</div>
                <div class="rank-info">
                    <div class="rank-name">${esc(p.nombre)}</div>
                    <div class="rank-sub">${esc(p.departamento)} · ${evalSub}</div>
                    <div style="display:flex;align-items:center;gap:2px;margin-top:5px;">${starsHtml}</div>
                </div>
                <div style="text-align:right;flex-shrink:0;min-width:44px;">
                    <div style="font-size:1.2rem;font-weight:800;background:${grad};-webkit-background-clip:text;-webkit-text-fill-color:transparent;">${evalStr}</div>
                    <div style="font-size:0.65rem;color:#94a3b8;margin-top:1px;">/ 5</div>
                </div>
            </div>`;
        }).join('');

        // Paginación: mostrar sólo si hay más de una página
        if (!pagEl) return;
        if (totalPages <= 1) {
            pagEl.style.display = 'none';
            return;
        }
        const btnBase = 'border:none;cursor:pointer;border-radius:8px;padding:4px 10px;font-size:0.75rem;font-weight:600;transition:all 0.2s;';
        const btnAct  = btnBase + 'background:#162660;color:#fff;';
        const btnDis  = btnBase + 'background:#f1f5f9;color:#cbd5e1;cursor:not-allowed;';
        const btnNorm = btnBase + 'background:#f1f5f9;color:#64748b;';
        pagEl.style.display = 'flex';
        pagEl.innerHTML = `
            <button onclick="window.top5Page(${safePage - 1})"
                style="${safePage === 0 ? btnDis : btnNorm}" ${safePage === 0 ? 'disabled' : ''}>
                <i class="ti ti-chevron-left"></i>
            </button>
            <span style="font-size:0.72rem;color:#64748b;font-weight:600;flex:1;text-align:center;line-height:2;">
                Pág.&nbsp;${safePage + 1}&nbsp;/&nbsp;${totalPages}
                &nbsp;·&nbsp;${totalItems}&nbsp;pasante${totalItems !== 1 ? 's' : ''}
            </span>
            <button onclick="window.top5Page(${safePage + 1})"
                style="${safePage >= totalPages - 1 ? btnDis : btnNorm}" ${safePage >= totalPages - 1 ? 'disabled' : ''}>
                <i class="ti ti-chevron-right"></i>
            </button>`;
    }

    window.filtrarTop5  = function(depto) { renderTop5(depto, 0); };
    window.top5Page     = function(page)  { renderTop5(_top5State.depto, page); };

    // Render inicial
    renderTop5('__todos__');

    }, 0); // setTimeout 0ms: PJAX-safe — runs after current call stack clears
})();
</script>
