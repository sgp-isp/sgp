<?php
/**
 * Vista: Evaluaciones — Bento UI Seamless Slide
 * Panel A: Dashboard (Pendientes + Historial) | Panel B: Formulario + Gauge en vivo
 */

$evaluaciones  = $data['evaluaciones']  ?? [];
$pasantes      = $data['pasantes']      ?? [];
$tutores       = $data['tutores']       ?? [];
$tutorActualId = $data['tutorActualId'] ?? null;
$total         = $data['total']         ?? 0;
$abrir_modal   = $data['abrir_modal']   ?? false;
$hayPeriodoActivo = $data['hayPeriodoActivo'] ?? true;
$periodoActivo   = $data['periodoActivo']   ?? null;

// ─── KPIs ──────────────────────────────────────────────────────────────────
$promedioGlobal = 0;
if (!empty($evaluaciones)) {
    $suma = array_sum(array_map(fn($e) => (float)($e->promedio_final ?? 0), $evaluaciones));
    $promedioGlobal = round($suma / count($evaluaciones), 2);
}
$totalEvaluados  = count(array_unique(array_map(fn($e) => (int)($e->pasante_id ?? 0), $evaluaciones)));
$sinEvaluar      = array_values(array_filter($pasantes, fn($p) => (int)($p->total_evaluaciones ?? 0) === 0));
$conEvaluacion   = array_values(array_filter($pasantes, fn($p) => (int)($p->total_evaluaciones ?? 0) > 0));

// ─── Serializar evaluaciones para JS ─────────────────────────────────────
$evalsJs = json_encode(array_values(array_map(fn($e) => [
    'id'    => (int)$e->id,
    'pid'   => (int)$e->pasante_id,
    'pNom'  => trim(($e->pasante_nombres ?? '') . ' ' . ($e->pasante_apellidos ?? '')),
    'tNom'  => trim(($e->tutor_nombres   ?? '') . ' ' . ($e->tutor_apellidos   ?? '')),
    'fecha' => $e->fecha_evaluacion ?? '',
    'lapso' => $e->lapso_academico  ?? '',
    'prom'  => (float)($e->promedio_final ?? 0),
], $evaluaciones)));

// ─── Tutores para JS ──────────────────────────────────────────────────────
$tutoresJs = json_encode(array_values(array_map(fn($t) => [
    'id'   => (int)$t->id,
    'nombre' => trim(($t->nombres ?? '') . ' ' . ($t->apellidos ?? '')),
], $tutores)));

// ─── Categorías de evaluación ─────────────────────────────────────────────
$categorias = [
    ['label' => 'Actitudes',    'col' => 'col-md-4 col-lg-4',
     'color' => '#3b82f6', 'bg' => '#eff6ff', 'icon' => 'ti-mood-happy', 'items' => [
        ['key' => 'criterio_iniciativa', 'label' => 'Iniciativa', 'icon' => 'ti-bulb'],
        ['key' => 'criterio_interes',    'label' => 'Interés',    'icon' => 'ti-heart'],
    ]],
    ['label' => 'Valores',      'col' => 'col-md-4 col-lg-4',
     'color' => '#d97706', 'bg' => '#fffbeb', 'icon' => 'ti-shield-check', 'items' => [
        ['key' => 'criterio_companerismo', 'label' => 'Compañerismo', 'icon' => 'ti-users'],
        ['key' => 'criterio_cooperacion',  'label' => 'Cooperación',  'icon' => 'ti-hand-stop'],
    ]],
    ['label' => 'Disciplina',   'col' => 'col-md-4 col-lg-4',
     'color' => '#7c3aed', 'bg' => '#f5f3ff', 'icon' => 'ti-calendar-check', 'items' => [
        ['key' => 'criterio_puntualidad',  'label' => 'Puntualidad',  'icon' => 'ti-clock'],
        ['key' => 'criterio_presentacion', 'label' => 'Presentación', 'icon' => 'ti-shirt'],
    ]],
    ['label' => 'Competencias', 'col' => 'col-12',
     'color' => '#059669', 'bg' => '#f0fdf4', 'icon' => 'ti-brain', 'items' => [
        ['key' => 'criterio_conocimiento', 'label' => 'Conocimiento', 'icon' => 'ti-book'],
        ['key' => 'criterio_analisis',     'label' => 'Análisis',     'icon' => 'ti-chart-dots'],
        ['key' => 'criterio_comunicacion', 'label' => 'Comunicación', 'icon' => 'ti-message-dots'],
        ['key' => 'criterio_aprendizaje',  'label' => 'Aprendizaje',  'icon' => 'ti-school'],
    ]],
    ['label' => 'Desempeño',    'col' => 'col-12',
     'color' => '#1e3a8a', 'bg' => '#f0f4ff', 'icon' => 'ti-trophy', 'items' => [
        ['key' => 'criterio_desarrollo',    'label' => 'Desarrollo',    'icon' => 'ti-code'],
        ['key' => 'criterio_analisis_res',  'label' => 'Análisis Res.', 'icon' => 'ti-chart-bar'],
        ['key' => 'criterio_conclusiones',  'label' => 'Conclusiones',  'icon' => 'ti-clipboard-check'],
        ['key' => 'criterio_recomendacion', 'label' => 'Recomendac.',   'icon' => 'ti-star'],
    ]],
];
$totalCriterios = array_sum(array_map(fn($c) => count($c['items']), $categorias));
?>

<div class="dashboard-container" style="width:100%;max-width:100%;padding:0;">

<!-- ═══════════════════════════════════════════════════════════════════
     ESTILOS RESPONSIVOS — un único bloque, cascada limpia
═══════════════════════════════════════════════════════════════════════ -->
<style>
/* ── BASE desktop ──────────────────────────────────────────────────── */
.ev-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.ev-banner {
    background: linear-gradient(135deg,#172554 0%,#1e3a8a 50%,#2563eb 100%);
    border-radius: 20px; padding: 28px 36px; margin-bottom: 24px;
    position: relative; overflow: hidden;
    display: flex; align-items: center; justify-content: space-between;
}
/* El slide ocupa el 50% del slider (200% de ancho total) */
#evalOuter          { height: calc(100vh - 310px); min-height: 420px; }
#vistaDashboard     { width: 50%; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; height: 100%; box-sizing: border-box; padding-bottom: 16px; }
#vistaDashboard > div { height: 100%; box-sizing: border-box; }
#vistaEvaluacion    { width: 50%; display: flex; flex-direction: column; overflow-y: auto; }
.ev-list-scroll     { flex: 1; min-height: 0; overflow-y: auto; overflow-x: hidden; }
.ev-panel-b-grid { display: grid; grid-template-columns: 1fr 290px; gap: 16px; align-items: start; }
.ev-sidebar      { position: sticky; top: 86px; display: flex; flex-direction: column; gap: 14px; }

/* ── TABLET ≤ 991px ────────────────────────────────────────────────── */
@media (max-width: 991px) {
    .ev-banner {
        flex-direction: column;
        align-items: flex-start;
        padding: 20px 18px;
        gap: 14px;
    }
    .ev-banner-deco   { display: none !important; }
    .ev-banner-btn    { width: 100%; }
    .ev-banner-btn button { width: 100%; justify-content: center; }
    .ev-kpi-grid      { grid-template-columns: 1fr 1fr; gap: 12px; }
    #evalOuter        { height: calc(100vh - 460px); min-height: 320px; }
}

/* ── MÓVIL ≤ 767px — Navegación push nativa ─────────────────────── */
@media (max-width: 767px) {

    /* ── Panel A ── */
    .ev-kpi-grid { grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px; }
    .ev-kpi-grid .ev-kpi-card { padding: 14px 12px !important; }
    .ev-kpi-grid .ev-kpi-val  { font-size: 1.5rem !important; }
    #vistaDashboard { grid-template-columns: 1fr !important; gap: 12px !important; width: 100% !important; height: auto !important; padding-bottom: 0 !important; }
    #vistaDashboard > div { height: auto !important; }
    .ev-list-scroll { height: 280px !important; min-height: 160px !important; flex: none !important; }
    #evalOuter  { height: auto !important; overflow: visible !important; }
    #evalSlider { display: block !important; width: 100% !important; transform: none !important; transition: none !important; }

    /* ════ PANEL B — full-screen push desde la derecha ════ */
    #vistaEvaluacion {
        position: fixed !important;
        top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        width: 100% !important;
        z-index: 3200 !important;
        background: #eef2f7 !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch !important;
        overscroll-behavior: contain !important;
        transform: translateX(100%) !important;
        transition: transform .38s cubic-bezier(.4,0,.2,1) !important;
        display: flex !important;
        flex-direction: column !important;
        padding-bottom: 88px !important;
    }
    #vistaEvaluacion.ev-panel-visible { transform: translateX(0) !important; }

    /* ── Header sticky ── */
    .ev-fb-header {
        position: sticky !important; top: 0 !important; z-index: 10 !important;
        border-radius: 0 !important; margin-bottom: 0 !important;
        padding: 10px 12px 8px !important; gap: 6px !important;
        flex-wrap: wrap !important;
    }
    /* Primera fila: Volver + Avatar + Nombre */
    .ev-fb-header > button:first-child { order: 1 !important; flex-shrink: 0 !important; }
    #fbAvatar { order: 2 !important; width: 30px !important; height: 30px !important; border-radius: 8px !important; font-size: 0.75rem !important; flex-shrink: 0 !important; }
    .ev-fb-nombre { order: 3 !important; flex: 1 !important; min-width: 0 !important; }
    #fbNombre { font-size: 0.8rem !important; }
    #fbMeta   { font-size: 0.63rem !important; }
    .ev-fb-criterios { display: none !important; }
    /* Botón Excelente a Todo — segunda fila, ancho completo */
    #btnMarcarTodoGlobal {
        order: 10 !important;
        width: 100% !important;
        justify-content: center !important;
        padding: 8px 12px !important;
        font-size: 0.8rem !important;
        gap: 6px !important; border-radius: 10px !important;
    }
    #btnMarcarTodoGlobal i { font-size: 0.9rem !important; }
    #btnMarcarTodoGlobal span { display: inline !important; }

    /* ── Mini donut: reemplazado por gauge ring en tarjeta ── */
    .ev-mob-donut { display: none !important; }
    /* Mostrar el gauge ring real */
    .ev-gauge-wrap { display: inline-flex !important; margin-bottom: 6px !important; }

    /* ── Layout del body ── */
    .ev-panel-b-grid {
        display: flex !important; flex-direction: column !important;
        gap: 0 !important; padding: 0 !important;
    }

    /* ══ SIDEBAR: DOS TARJETAS — misma altura ══ */
    .ev-sidebar {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 8px !important;
        padding: 10px 10px 4px !important;
        order: -1 !important;
        position: static !important;
        align-items: stretch !important;   /* ambas tarjetas siempre igual de altas */
    }

    /* ── Tarjeta Izquierda: Gauge Ring ── */
    .ev-sidebar > div:first-child {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: space-between !important;
        border-radius: 16px !important;
        padding: 12px 10px !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
        text-align: center !important;
        box-sizing: border-box !important;
    }
    /* Título */
    .ev-sidebar > div:first-child > p {
        font-size: 0.58rem !important;
        margin: 0 0 4px !important;
        letter-spacing: .3px !important;
        flex-shrink: 0 !important;
        align-self: center !important;
    }
    /* Anillo: ocupa el espacio libre, centra el SVG */
    .ev-gauge-wrap {
        flex: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin-bottom: 4px !important;
    }
    .ev-gauge-wrap svg { width: 124px !important; height: 124px !important; }
    #gaugeValue { font-size: 1.5rem !important; }
    #gaugeDenom { font-size: 0.68rem !important; }
    #gaugeLabel { font-size: 0.58rem !important; margin-top: 2px !important; }
    /* Contador pegado al fondo */
    .ev-gauge-counter {
        width: 100% !important; padding: 6px 8px !important;
        border-radius: 10px !important; box-sizing: border-box !important;
        flex-shrink: 0 !important;
    }
    .ev-gauge-counter span:first-child { font-size: 0.56rem !important; }
    #gaugeCount { font-size: 0.62rem !important; padding: 1px 6px !important; }
    /* Ocultar leyenda ★ */
    .ev-gauge-legend { display: none !important; }

    /* ── Tarjeta Derecha: Controles ── */
    /* SIN height explícito → align-items:stretch controla la altura,
       ambas tarjetas siempre tienen exactamente la misma altura */
    .ev-sidebar > div:last-child {
        border-radius: 16px !important;
        border: 1px solid #DDE2F0 !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
    }
    /* Cuerpo de controles */
    #mobConfigBody {
        padding: 10px 10px !important;
        display: flex !important; flex-direction: column !important; gap: 8px !important;
        height: 100% !important; box-sizing: border-box !important;
    }
    /* Ocultar botón guardar del desktop */
    #btnGuardar { display: none !important; }
    /* Labels */
    #mobConfigBody label { font-size: 0.62rem !important; margin-bottom: 3px !important; }
    /* Campos read-only — altura auto en movil para no cortar el texto */
    .ev-field-ro {
        padding: 5px 8px !important; font-size: 0.72rem !important;
        border-radius: 9px !important;
        height: auto !important; min-height: 30px !important; max-height: none !important;
        overflow: visible !important; box-sizing: border-box !important;
        flex-wrap: wrap !important; align-items: flex-start !important;
    }
    /* Badge Auto: fuente minima */
    .ev-auto-badge { font-size: 0.52rem !important; padding: 1px 5px !important; flex-shrink: 0 !important; }
    /* Select tutor */
    #fTutorId {
        font-size: 0.74rem !important; padding: 0 8px !important;
        border-radius: 9px !important;
        height: 34px !important; min-height: 34px !important; max-height: 34px !important;
        box-sizing: border-box !important; width: 100% !important;
        overflow: hidden !important; text-overflow: ellipsis !important;
    }
    /* Fecha + Periodo: siempre columna única */
    .ev-meta-grid { grid-template-columns: 1fr !important; gap: 5px !important; }
    /* Span de texto dentro del campo: permite truncar con ellipsis */
    .ev-field-ro > span { white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; min-width: 0 !important; flex: 1 !important; }
    /* Textarea observaciones — altura fija */
    #fObs {
        font-size: 0.74rem !important; padding: 6px 8px !important;
        border-radius: 9px !important;
        height: 48px !important; min-height: 48px !important; max-height: 48px !important;
        resize: none !important; box-sizing: border-box !important;
    }

    /* ── Criterios bento ── */
    .ev-panel-b-grid > div:first-child { padding: 10px 10px 6px !important; }
    .eval-cat-wrapper .row { --bs-gutter-x: 0.45rem !important; --bs-gutter-y: 0.45rem !important; }
    .eval-cat-wrapper .row > [class*="col-"] { flex: 0 0 50% !important; max-width: 50% !important; }

    /* Sección de categoría — tarjeta bento blanca */
    .eval-cat-wrapper {
        background: rgba(255,255,255,0.75) !important;
        border-radius: 16px !important; padding: 10px !important;
        margin-bottom: 8px !important;
        border: 1px solid rgba(0,0,0,0.06) !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05) !important;
    }
    .eval-cat-wrapper > div > div:first-child {
        margin-bottom: 8px !important; padding-bottom: 0 !important; border-bottom: none !important;
    }

    /* Card criterio base bento */
    .criterio-card {
        flex-direction: column !important; align-items: center !important;
        text-align: center !important; padding: 12px 6px 10px !important;
        border-radius: 14px !important; border-width: 1.5px !important;
        gap: 0 !important; height: 100% !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06) !important;
        transition: transform .15s ease, box-shadow .15s ease !important;
    }
    .criterio-card:active { transform: scale(0.95) !important; }

    /* Fondos tintados por categoría */
    .criterio-card[data-cat-color="#3b82f6"] { background:#eff6ff !important; border-color:rgba(59,130,246,.25) !important; }
    .criterio-card[data-cat-color="#d97706"] { background:#fffbeb !important; border-color:rgba(217,119,6,.25) !important; }
    .criterio-card[data-cat-color="#7c3aed"] { background:#f5f3ff !important; border-color:rgba(124,58,237,.25) !important; }
    .criterio-card[data-cat-color="#059669"] { background:#f0fdf4 !important; border-color:rgba(5,150,105,.25) !important; }
    .criterio-card[data-cat-color="#1e3a8a"] { background:#f0f4ff !important; border-color:rgba(30,58,138,.25) !important; }

    /* Icon rings tintados */
    .criterio-card[data-cat-color="#3b82f6"] .ev-icon-ring { background:rgba(59,130,246,.15) !important; border-color:rgba(59,130,246,.3) !important; }
    .criterio-card[data-cat-color="#d97706"] .ev-icon-ring { background:rgba(217,119,6,.15) !important;  border-color:rgba(217,119,6,.3) !important; }
    .criterio-card[data-cat-color="#7c3aed"] .ev-icon-ring { background:rgba(124,58,237,.15) !important; border-color:rgba(124,58,237,.3) !important; }
    .criterio-card[data-cat-color="#059669"] .ev-icon-ring { background:rgba(5,150,105,.15) !important;  border-color:rgba(5,150,105,.3) !important; }
    .criterio-card[data-cat-color="#1e3a8a"] .ev-icon-ring { background:rgba(30,58,138,.15) !important;  border-color:rgba(30,58,138,.3) !important; }

    .criterio-card .ev-icon-ring { width:30px !important; height:30px !important; margin:0 auto 7px !important; min-width:unset !important; }
    .criterio-card .ev-icon-ring i { font-size:0.82rem !important; }
    .criterio-card p { font-size:0.67rem !important; font-weight:800 !important; margin:0 0 7px !important; line-height:1.2 !important; flex:unset !important; color:#0f172a !important; }
    .criterio-card .star-group { gap:0 !important; flex-wrap:nowrap !important; }
    .criterio-card .star-btn {
        font-size:1.1rem !important; padding:2px 1px !important;
        min-height:36px !important; min-width:22px !important;
        display:inline-flex !important; align-items:center !important; justify-content:center !important;
    }

    /* Barra Guardar fija — oculta por defecto, visible solo al abrir panel B */
    .ev-save-bar {
        display: none !important;
    }
    .ev-save-bar.ev-save-active {
        display: block !important; position: fixed !important;
        bottom: 0 !important; left: 0 !important; right: 0 !important;
        padding: 10px 14px 20px !important;
        background: rgba(255,255,255,0.97) !important;
        backdrop-filter: blur(16px) !important; -webkit-backdrop-filter: blur(16px) !important;
        border-top: 1px solid #e2e8f0 !important;
        box-shadow: 0 -4px 24px rgba(0,0,0,0.08) !important;
        z-index: 3300 !important;
    }
    .ev-btn-volver-mobile { display: none !important; }

    /* ── Historial de Evaluaciones: fila en 2 líneas en móvil ─────────
       Antes iba todo en una sola fila (avatar + nombre + calificación +
       4 botones), lo que obligaba a truncar el nombre casi por completo
       ("ELIF...") y dejaba los íconos apretados. Se reparte en 2 líneas:
       arriba avatar + nombre/meta con espacio real; abajo, alineados a
       la derecha, la calificación y las acciones. */
    .ev-hist-row {
        flex-wrap: wrap !important;
        padding: 12px 14px !important;
        row-gap: 8px !important;
    }
    .ev-hist-info {
        flex: 1 1 calc(100% - 46px) !important;
    }
    .ev-hist-info > div:first-child {
        font-size: 0.88rem !important;
        white-space: normal !important;
        overflow-wrap: break-word !important;
    }
    .ev-hist-actions {
        flex: 1 1 100% !important;
        justify-content: flex-end !important;
        padding-left: 46px !important;
        gap: 6px !important;
    }
    .ev-hist-actions button {
        padding: 5px 8px !important;
        font-size: 0.75rem !important;
    }
}

@media (min-width: 768px) {
    .ev-mob-donut    { display: none !important; }
    .ev-save-bar     { display: none !important; }
    .ev-btn-volver-mobile { display: none !important; }
}

/* ── SweetAlert2 Bento Premium — Evaluaciones ─────────────────────── */
.swal-eval-popup {
    border-radius: 24px !important;
    padding: 28px 24px 24px !important;
    box-shadow: 0 25px 60px rgba(0,0,0,0.15) !important;
    border: 1px solid rgba(0,0,0,0.06) !important;
    font-family: inherit !important;
    max-width: 380px !important;
}
.swal-eval-success { background: #fff !important; }
.swal-eval-popup .swal2-title {
    font-size: 1.2rem !important; font-weight: 800 !important;
    color: #0f172a !important; padding: 0 !important;
    margin: 0 0 10px !important; line-height: 1.3 !important;
}
.swal-eval-btn-confirm {
    border-radius: 12px !important; font-weight: 700 !important;
    font-size: 0.88rem !important; padding: 11px 24px !important;
    display: inline-flex !important; align-items: center !important;
    gap: 6px !important; transition: all .2s !important;
}
.swal-eval-btn-cancel {
    border-radius: 12px !important; font-weight: 700 !important;
    font-size: 0.88rem !important; padding: 11px 24px !important;
    display: inline-flex !important; align-items: center !important;
    gap: 6px !important;
}
.swal2-actions { gap: 8px !important; margin-top: 20px !important; }
</style>

<!-- ═══ BANNER ════════════════════════════════════════════════════════════════ -->
<div class="ev-banner">
    <div class="ev-banner-deco" style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,0.04);border-radius:50%;pointer-events:none;"></div>
    <div style="display:flex;align-items:center;gap:16px;z-index:1;">
        <div style="background:rgba(255,255,255,0.15);border-radius:14px;padding:12px;flex-shrink:0;">
            <i class="ti ti-star" style="font-size:28px;color:white;"></i>
        </div>
        <div>
            <h1 style="color:white;font-size:1.65rem;font-weight:700;margin:0;">Evaluaciones de Pasantes</h1>
            <p style="color:rgba(255,255,255,0.7);margin:4px 0 0;font-size:0.85rem;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <i class="ti ti-clipboard-check"></i>
                <span>Gestión y Seguimiento Académico</span>
                <?php if ($total > 0): ?>
                <span style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.1);border-radius:50px;padding:3px 12px;color:white;font-weight:700;font-size:0.78rem;"><?= $total ?> registradas</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="ev-banner-btn" style="background:rgba(0,0,0,0.15);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:14px;padding:5px;z-index:1;">
        <button onclick="EvalApp.nuevaDesdeBoton()" style="background:white;color:#1e3a8a;border:none;padding:11px 22px;border-radius:10px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;font-size:0.9rem;transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
            <i class="ti ti-plus"></i> Nueva Evaluación
        </button>
    </div>
</div>

<!-- ═══ BANNER RECESO PERÍODO ═══════════════════════════════════════════════════ -->
<?php if (!$hayPeriodoActivo): ?>
<div style="background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border: 1.5px solid #bfdbfe; border-radius: 16px; padding: 20px 26px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
    <div style="display: flex; align-items: center; gap: 14px;">
        <div style="width: 44px; height: 44px; border-radius: 12px; background: #dbeafe; display: flex; align-items: center; justify-content: center; color: #1d4ed8; font-size: 1.4rem; flex-shrink: 0;">
            <i class="ti ti-calendar-off"></i>
        </div>
        <div>
            <h4 style="margin: 0; font-size: 1rem; font-weight: 800; color: #1e3a8a;">Período Académico en Receso o Cerrado</h4>
            <p style="margin: 3px 0 0; font-size: 0.83rem; color: #475569;">
                No hay un período académico activo actualmente. Las evaluaciones operacionales se habilitan cuando hay una cohorte en curso.
            </p>
        </div>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="<?= URLROOT ?>/periodos" style="background: #1d4ed8; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.82rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            <i class="ti ti-calendar-plus"></i> Gestionar Períodos
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ═══ KPIs ═════════════════════════════════════════════════════════════════ -->
<div class="ev-kpi-grid">
<?php foreach ([
    ['label' => 'Total Evaluaciones', 'value' => $total,              'sub' => 'realizadas',       'color' => '#2563eb', 'icon' => 'ti-file-analytics'],
    ['label' => 'Promedio Global',    'value' => $promedioGlobal.'/5','sub' => 'rendimiento',      'color' => '#f59e0b', 'icon' => 'ti-star'],
    ['label' => 'Evaluados',          'value' => $totalEvaluados,     'sub' => 'con evaluación',   'color' => '#10b981', 'icon' => 'ti-circle-check'],
    ['label' => 'Pendientes',         'value' => count($sinEvaluar),  'sub' => 'sin evaluar',      'color' => '#ef4444', 'icon' => 'ti-alert-circle'],
] as $k): ?>
<div class="ev-kpi-card" style="background:white;border-radius:16px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);border-left:4px solid <?= $k['color'] ?>;transition:all .25s;"
     onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 24px rgba(0,0,0,.09)'"
     onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 12px rgba(0,0,0,0.06)'">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
        <p style="color:#64748b;font-size:0.75rem;margin:0 0 6px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><?= $k['label'] ?></p>
        <i class="ti <?= $k['icon'] ?>" style="color:<?= $k['color'] ?>;font-size:1.3rem;opacity:0.65;"></i>
    </div>
    <h2 class="ev-kpi-val" style="font-size:2.1rem;font-weight:800;color:<?= $k['color'] ?>;margin:0 0 2px;"><?= $k['value'] ?></h2>
    <p style="color:#94a3b8;font-size:0.75rem;margin:0;"><?= $k['sub'] ?></p>
</div>
<?php endforeach; ?>
</div>

<!-- ═══ BOTÓN VOLVER (solo móvil, Panel B activo) ═══════════════════════════ -->
<button class="ev-btn-volver-mobile" id="btnVolverMobile"
    style="display:none;align-items:center;gap:8px;background:white;border:1.5px solid #e2e8f0;border-radius:12px;padding:10px 16px;font-size:0.85rem;font-weight:600;color:#334155;cursor:pointer;margin-bottom:10px;"
    onclick="EvalApp.volver()">
    <i class="ti ti-arrow-left"></i> Volver a la lista
</button>

<!-- ═══ SLIDER CONTAINER ═══════════════════════════════════════════════════════ -->
<div id="evalOuter" style="overflow:hidden;position:relative;">
<div id="evalSlider" style="display:flex;width:200%;transition:transform .48s cubic-bezier(.4,0,.2,1);height:100%;">

    <!-- ══ PANEL A — DASHBOARD: Pendientes | Historial ══════════════════════ -->
    <div id="vistaDashboard">

        <!-- ─── Card Pendientes por Evaluar ─────────────────────────────── -->
        <div style="background:white;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.04);overflow:hidden;border:1px solid #f1f5f9;display:flex;flex-direction:column;min-height:280px;">
            <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
                <h3 style="font-size:0.95rem;font-weight:700;color:#1e293b;margin:0;">
                    <i class="ti ti-alert-circle" style="color:#ef4444;margin-right:8px;"></i>
                    Pendientes por Evaluar
                </h3>
                <span style="background:#fef2f2;color:#dc2626;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;flex-shrink:0;">
                    <?= count($sinEvaluar) ?> pasante<?= count($sinEvaluar) !== 1 ? 's' : '' ?>
                </span>
            </div>

            <div class="ev-list-scroll" style="min-height:220px;">
                <?php if (empty($sinEvaluar)): ?>
                    <?php if (!$hayPeriodoActivo): ?>
                    <div style="height:100%;min-height:200px;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:12px;padding:32px;text-align:center;">
                        <div style="width:60px;height:60px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#3b82f6;font-size:1.6rem;flex-shrink:0;">
                            <i class="ti ti-calendar-off"></i>
                        </div>
                        <div>
                            <p style="font-weight:700;color:#1e293b;font-size:0.92rem;margin:0 0 4px;">Sin cohorte activa</p>
                            <p style="color:#64748b;font-size:0.8rem;margin:0;line-height:1.4;">No hay pasantes en curso en este momento.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="height:100%;min-height:200px;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:16px;padding:32px;">
                        <div style="width:72px;height:72px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <div style="text-align:center;">
                            <p style="font-weight:700;color:#15803d;font-size:0.95rem;margin:0 0 6px;">¡Excelente trabajo!</p>
                            <p style="color:#94a3b8;font-size:0.82rem;margin:0;line-height:1.5;">Todos los pasantes activos cuentan<br>con su evaluación al día.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                <?php foreach ($sinEvaluar as $p):
                    $ini = strtoupper(substr($p->nombres ?? '?', 0, 1) . substr($p->apellidos ?? '', 0, 1));
                    $tutNom = trim($p->tutor_nombre ?? '');
                    $metaLabel = $tutNom
                        ? $tutNom
                        : ($p->departamento && $p->departamento !== 'Sin departamento'
                            ? $p->departamento
                            : ($p->institucion_nombre ?? 'Sin información'));
                    $pj  = htmlspecialchars(json_encode([
                        'id'              => (int)$p->id,
                        'nombre'          => trim(($p->nombres ?? '') . ' ' . ($p->apellidos ?? '')),
                        'cedula'          => $p->cedula ?? '',
                        'depto'           => $p->departamento ?? '',
                        'tutor_id'        => $p->tutor_id ?? null,
                        'tutor_nombre'    => $tutNom,
                        'institucion'     => $p->institucion_nombre ?? '',
                        'periodo_nombre'  => $p->periodo_nombre ?? '',
                    ]), ENT_QUOTES);
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid #f8fafc;transition:background .15s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='white'">
                    <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#162660,#2563eb);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.78rem;flex-shrink:0;"><?= $ini ?></div>
                        <div style="min-width:0;">
                            <div style="font-weight:600;color:#1e293b;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''))) ?></div>
                            <div style="font-size:0.73rem;color:#64748b;display:flex;align-items:center;gap:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <i class="ti ti-user-check" style="color:#2563eb;font-size:0.7rem;flex-shrink:0;"></i>
                                <?= htmlspecialchars($metaLabel) ?>
                            </div>
                        </div>
                    </div>
                    <button onclick="EvalApp.abrirFormulario(<?= $pj ?>)"
                        style="background:#eff6ff;color:#162660;border:1.5px solid #bfdbfe;padding:6px 14px;border-radius:8px;font-size:0.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .15s;white-space:nowrap;flex-shrink:0;margin-left:8px;"
                        onmouseover="this.style.background='#dbeafe';this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#eff6ff';this.style.transform='none'">
                        <i class="ti ti-clipboard-check"></i> Evaluar
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Card Historial de Evaluaciones ──────────────────────────── -->
        <div style="background:white;border-radius:16px;box-shadow:0 4px 6px rgba(0,0,0,0.04);overflow:hidden;border:1px solid #f1f5f9;display:flex;flex-direction:column;min-height:280px;">
            <div style="padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
                <h3 style="font-size:0.95rem;font-weight:700;color:#1e293b;margin:0;">
                    <i class="ti ti-list-details" style="color:#2563eb;margin-right:8px;"></i>
                    Historial de Evaluaciones
                </h3>
                <span style="background:#eff6ff;color:#162660;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:700;flex-shrink:0;">
                    <?= $total ?> registro<?= $total !== 1 ? 's' : '' ?>
                </span>
            </div>

            <div class="ev-list-scroll" style="min-height:220px;">
                <?php if (empty($evaluaciones)): ?>
                <div style="height:100%;min-height:200px;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:16px;padding:32px;">
                    <div style="width:72px;height:72px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                            <line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/>
                        </svg>
                    </div>
                    <div style="text-align:center;">
                        <p style="font-weight:700;color:#475569;font-size:0.95rem;margin:0 0 6px;">Sin evaluaciones aún</p>
                        <p style="color:#94a3b8;font-size:0.82rem;margin:0;line-height:1.5;">Las evaluaciones registradas<br>aparecerán aquí.</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($evaluaciones as $ev):
                    $pNom = trim(($ev->pasante_nombres ?? '') . ' ' . ($ev->pasante_apellidos ?? ''));
                    $tNom = trim(($ev->tutor_nombres   ?? '') . ' ' . ($ev->tutor_apellidos   ?? ''));
                    $prom = (float)($ev->promedio_final ?? 0);
                    $ini  = strtoupper(substr($ev->pasante_nombres ?? '?', 0, 1) . substr($ev->pasante_apellidos ?? '', 0, 1));
                    $pc   = $prom >= 4 ? '#10b981' : ($prom >= 3 ? '#d97706' : ($prom >= 2 ? '#ea580c' : '#ef4444'));
                    $pb   = $prom >= 4 ? '#dcfce7'  : ($prom >= 3 ? '#fef3c7' : ($prom >= 2 ? '#ffedd5' : '#fee2e2'));
                ?>
                <div class="ev-hist-row" style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #f8fafc;transition:background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                    <div class="ev-hist-avatar" style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#162660,#2563eb);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:0.78rem;flex-shrink:0;"><?= $ini ?></div>
                    <div class="ev-hist-info" style="flex:1;min-width:0;">
                        <div style="font-weight:600;color:#1e293b;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($pNom) ?></div>
                        <div style="font-size:0.73rem;color:#94a3b8;margin-top:1px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <i class="ti ti-user-check" style="color:#2563eb;"></i>
                            <span><?= htmlspecialchars($tNom ?: '—') ?></span>
                            <span style="opacity:.5;">·</span>
                            <span><?= date('d/m/Y', strtotime($ev->fecha_evaluacion)) ?></span>
                        </div>
                    </div>
                    <div class="ev-hist-actions" style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                        <span style="background:<?= $pb ?>;color:<?= $pc ?>;padding:4px 10px;border-radius:20px;font-size:0.82rem;font-weight:800;"><?= number_format($prom, 1) ?>/5</span>
                        <button onclick="verEvaluacion(<?= (int)$ev->id ?>)"
                            style="background:#f1f5f9;border:none;border-radius:8px;padding:5px 9px;cursor:pointer;color:#64748b;font-size:0.8rem;transition:background .15s;"
                            onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'"
                            title="Ver evaluación">
                            <i class="ti ti-eye"></i>
                        </button>
                        <button onclick="EvalApp.editarEvaluacion(<?= (int)$ev->id ?>)"
                            style="background:#f0fdf4;border:none;border-radius:8px;padding:5px 9px;cursor:pointer;color:#16a34a;font-size:0.8rem;transition:background .15s;margin-left:4px;"
                            onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'"
                            title="Editar evaluación">
                            <i class="ti ti-pencil"></i>
                        </button>
                        <form method="POST" action="<?= URLROOT ?>/reportes/exportar" target="_blank" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
                            <input type="hidden" name="modulo" value="evaluaciones">
                            <input type="hidden" name="pasante_id" value="<?= (int)$ev->pasante_id ?>">
                            <button type="submit"
                                style="background:#fff7ed;border:none;border-radius:8px;padding:5px 9px;cursor:pointer;color:#ea580c;font-size:0.8rem;transition:background .15s;margin-left:4px;"
                                onmouseover="this.style.background='#ffedd5'" onmouseout="this.style.background='#fff7ed'"
                                title="Exportar evaluación PDF">
                                <i class="ti ti-file-type-pdf"></i>
                            </button>
                        </form>
                        <button onclick="EvalApp.eliminarEvaluacion(<?= (int)$ev->id ?>, <?= htmlspecialchars(json_encode(trim(($ev->pasante_nombres ?? '') . ' ' . ($ev->pasante_apellidos ?? ''))), ENT_QUOTES) ?>)"
                            style="background:#fef2f2;border:none;border-radius:8px;padding:5px 9px;cursor:pointer;color:#dc2626;font-size:0.8rem;transition:background .15s;margin-left:4px;"
                            onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'"
                            title="Eliminar evaluación">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /vistaDashboard -->


    <!-- ══ PANEL B — BENTO UI: Criterios + Anillo Gauge en vivo ════════════ -->
    <div id="vistaEvaluacion">

        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div style="background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 55%,#2563eb 100%);padding:16px 20px;border-radius:20px;margin-bottom:16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;" class="ev-fb-header">
            <button onclick="EvalApp.volver()"
                style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.18);border-radius:10px;padding:8px 14px;color:#fff;cursor:pointer;font-size:0.83rem;font-weight:600;display:flex;align-items:center;gap:6px;transition:background .15s;white-space:nowrap;flex-shrink:0;"
                onmouseover="this.style.background='rgba(255,255,255,.22)'" onmouseout="this.style.background='rgba(255,255,255,.12)'">
                <i class="ti ti-arrow-left"></i> Volver
            </button>
            <div id="fbAvatar"
                style="width:40px;height:40px;border-radius:11px;background:rgba(255,255,255,0.18);border:2px solid rgba(255,255,255,0.22);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:0.95rem;flex-shrink:0;">?</div>
            <div class="ev-fb-nombre" style="flex:1;min-width:0;">
                <div id="fbNombre" style="color:#fff;font-weight:700;font-size:0.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">—</div>
                <div id="fbMeta" style="color:rgba(255,255,255,.6);font-size:0.72rem;margin-top:1px;"></div>
            </div>
            <span class="ev-fb-criterios" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:8px;padding:5px 11px;color:rgba(255,255,255,.9);font-size:0.72rem;font-weight:700;white-space:nowrap;flex-shrink:0;">
                <i class="ti ti-list-check"></i> <?= $totalCriterios ?> criterios
            </span>
            <button id="btnMarcarTodoGlobal" data-marcado="0"
                onclick="EvalApp.toggleMarcarTodo(this)"
                style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);border:none;color:#fff;border-radius:10px;padding:8px 14px;font-size:0.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 4px 15px rgba(16,185,129,0.3);transition:all .3s;white-space:nowrap;flex-shrink:0;"
                onmouseover="this.style.filter='brightness(1.1)';this.style.transform='translateY(-1px)'"
                onmouseout="this.style.filter='none';this.style.transform='none'">
                <i class="ti ti-stars" style="font-size:1rem;"></i>
                <span>Excelente a Todo</span>
            </button>
        </div>

        <!-- ── Mobile: mini donut + meta (oculto en desktop) ── -->
        <div class="ev-mob-donut" style="display:none;background:white;border-bottom:1px solid #e2e8f0;padding:12px 16px;align-items:center;gap:14px;">
            <!-- Donut SVG reutiliza mismo path que el gauge desktop -->
            <div style="position:relative;width:72px;height:72px;flex-shrink:0;">
                <svg viewBox="0 0 36 36" width="72" height="72" style="transform:rotate(-90deg);">
                    <path fill="none" stroke="#f1f5f9" stroke-width="4"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path id="mobGaugeArc" fill="none" stroke="#e2e8f0" stroke-width="4"
                        stroke-linecap="round" stroke-dasharray="0 100"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        style="transition:stroke-dasharray .5s cubic-bezier(.4,0,.2,1),stroke .4s ease;"/>
                </svg>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                    <div style="display:flex;align-items:baseline;gap:1px;">
                        <span id="mobGaugeVal" style="font-size:1.0rem;font-weight:900;color:#162660;line-height:1;transition:color .3s;">—</span>
                        <span id="mobGaugeDenom" style="font-size:0.55rem;color:#94a3b8;font-weight:700;display:none;">/5</span>
                    </div>
                </div>
            </div>
            <!-- Meta derecha -->
            <div style="flex:1;min-width:0;">
                <span id="mobGaugeLabel" style="display:inline-block;font-size:0.7rem;font-weight:700;color:#94a3b8;padding:3px 10px;border-radius:20px;background:#f1f5f9;margin-bottom:9px;transition:all .3s;">Sin calificar</span>
                <div style="display:flex;align-items:center;gap:7px;">
                    <div style="flex:1;background:#e2e8f0;border-radius:8px;height:7px;overflow:hidden;">
                        <div id="mobGaugeProgress" style="height:100%;background:#e2e8f0;border-radius:8px;width:0%;transition:width .4s ease,background .4s ease;"></div>
                    </div>
                    <span id="mobGaugeCount" style="font-size:0.7rem;font-weight:800;color:#1D4ED8;white-space:nowrap;flex-shrink:0;">0/<?= $totalCriterios ?></span>
                </div>
            </div>
        </div>

        <!-- ── Body Bento ──────────────────────────────────────────────── -->
        <form id="formEvaluacion" onsubmit="return false;">
            <?= CsrfHelper::field() ?>
            <input type="hidden" name="pasante_id" id="fPasanteId" value="">
            <input type="hidden" name="tutor_id" id="fTutorId" value="">
            <input type="hidden" name="evaluacion_id" id="fEvaluacionId" value="0">
            <div class="ev-panel-b-grid">

                <!-- ── Col izquierda: Criterios ── -->
                <div>
                    <div class="row g-3">
                    <?php foreach ($categorias as $cat):
                        $isSmall = count($cat['items']) <= 2;
                        $itemCol = $isSmall ? 'col-6 col-md-12' : 'col-6 col-md-6 col-xl-3';
                    ?>
                    <div class="<?= $cat['col'] ?><?= !$isSmall ? ' mt-3' : '' ?>">
                        <div class="eval-cat-wrapper" style="height:100%;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;<?= !$isSmall ? 'padding-bottom:12px;border-bottom:1px solid #DDE2F0;' : '' ?>">
                                <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 13px;border-radius:50px;background:<?= $cat['bg'] ?>;color:<?= $cat['color'] ?>;font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;">
                                    <i class="ti <?= $cat['icon'] ?>" style="font-size:0.85rem;"></i>
                                    <?= $cat['label'] ?>
                                </span>
                                <!-- Switch: marcar toda la categoría como Excelente -->
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;user-select:none;" title="Marcar todos como Excelente">
                                    <span style="font-size:0.63rem;color:#7480A0;font-weight:700;white-space:nowrap;"><?= count($cat['items']) ?> ítems</span>
                                    <div style="width:30px;height:18px;border-radius:9px;background:#DDE2F0;position:relative;cursor:pointer;transition:background .3s;flex-shrink:0;">
                                        <div class="cat-switch-knob" style="position:absolute;top:2px;left:2px;width:14px;height:14px;border-radius:50%;background:white;box-shadow:0 1px 3px rgba(0,0,0,0.2);transition:transform .3s;"></div>
                                    </div>
                                    <input type="checkbox" class="cat-switch-input" style="display:none;" onchange="EvalApp.toggleCategoria(this)">
                                </label>
                            </div>
                            <div class="row g-3">
                                <?php foreach ($cat['items'] as $item): ?>
                                <div class="<?= $itemCol ?>">
                                    <div class="criterio-card eval-criterio-tile"
                                         data-cat-color="<?= $cat['color'] ?>"
                                         style="background:white;border:1px solid #DDE2F0;border-radius:20px;padding:22px 16px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05),0 2px 4px -2px rgba(0,0,0,0.05);display:flex;flex-direction:column;align-items:center;text-align:center;cursor:pointer;transition:transform .4s cubic-bezier(0.16,1,0.3,1),box-shadow .4s cubic-bezier(0.16,1,0.3,1),border-color .3s;height:100%;"
                                         onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='0 20px 25px -5px rgba(0,0,0,.1),0 8px 10px -6px rgba(0,0,0,.04)';this.style.borderColor='<?= $cat['color'] ?>'"
                                         onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.05),0 2px 4px -2px rgba(0,0,0,0.05)';this.style.borderColor='#DDE2F0'">
                                        <div class="ev-icon-ring" style="width:54px;height:54px;border-radius:50%;border:2.5px solid #F1F5F9;background:#F8FAFD;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;transition:all .4s ease;"
                                             onmouseover="this.style.borderColor='<?= $cat['color'] ?>';this.style.transform='scale(1.1)'"
                                             onmouseout="this.style.borderColor='#F1F5F9';this.style.transform='none'">
                                            <i class="ti <?= $item['icon'] ?>" style="color:<?= $cat['color'] ?>;font-size:1.35rem;"></i>
                                        </div>
                                        <p style="font-size:0.76rem;font-weight:700;color:#0D1424;margin:0 0 12px;line-height:1.3;"><?= $item['label'] ?></p>
                                        <div class="star-group d-flex justify-content-center" data-criterio="<?= $item['key'] ?>" style="gap:2px;">
                                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <button type="button" class="star-btn" data-val="<?= $s ?>"
                                                title="<?= $s ?> — <?= ['','Deficiente','Bajo Promedio','Promedio','Sobre Promedio','Excelente'][$s] ?>"
                                                style="background:none;border:none;cursor:pointer;font-size:1.4rem;color:#e2e8f0;padding:0;line-height:1;transition:color .1s,transform .12s;">★</button>
                                            <?php endfor; ?>
                                            <input type="hidden" class="star-input" name="<?= $item['key'] ?>" value="0">
                                        </div>
                                        <div style="height:18px;margin-top:3px;text-align:center;">
                                            <span class="equiv-badge-isp" style="display:none;font-size:.62rem;font-weight:800;padding:1px 8px;border-radius:20px;transition:all .2s;"></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div><!-- /row g-3 criterios -->
                </div><!-- /col criterios wrapper -->

                <!-- ── Col derecha: Anillo + Controles ── -->
                <div class="ev-sidebar">

                    <!-- Anillo Gauge -->
                    <div style="background:white;border-radius:20px;padding:24px 20px 20px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05),0 2px 4px -2px rgba(0,0,0,0.05);border:1px solid #DDE2F0;text-align:center;">
                        <p style="font-size:0.72rem;font-weight:800;color:#7480A0;text-transform:uppercase;letter-spacing:.5px;margin:0 0 14px;">Promedio en Vivo</p>
                        <div class="ev-gauge-wrap" style="position:relative;display:inline-flex;align-items:center;justify-content:center;margin-bottom:10px;">
                            <svg viewBox="0 0 36 36" width="160" height="160" style="transform:rotate(-90deg);">
                                <path fill="none" stroke="#f1f5f9" stroke-width="3.5"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path fill="none" stroke="#fee2e2" stroke-width="2" opacity=".7"
                                    stroke-dasharray="40 100"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path id="gaugeArc" fill="none" stroke="#e2e8f0" stroke-width="3.5"
                                    stroke-linecap="round" stroke-dasharray="0 100"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    style="transition:stroke-dasharray .5s cubic-bezier(.4,0,.2,1),stroke .4s ease;"/>
                            </svg>
                            <div style="position:absolute;text-align:center;pointer-events:none;">
                                <div style="display:flex;align-items:baseline;justify-content:center;gap:2px;line-height:1;">
                                    <span id="gaugeValue" style="font-size:1.9rem;font-weight:900;color:#162660;transition:color .3s;">—</span>
                                    <span id="gaugeDenom" style="font-size:0.85rem;color:#94a3b8;font-weight:700;display:none;">/5</span>
                                </div>
                                <div id="gaugeLabel" style="font-size:0.7rem;font-weight:700;color:#94a3b8;margin-top:3px;transition:color .3s;">Sin calificar</div>
                            </div>
                        </div>
                        <div class="ev-gauge-counter" style="background:#F8FAFD;border:1px solid #DDE2F0;border-radius:12px;padding:10px 12px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <span style="font-size:0.68rem;color:#7480A0;font-weight:700;text-transform:uppercase;letter-spacing:.4px;">Calificados</span>
                                <span id="gaugeCount" style="font-size:0.72rem;color:#1D4ED8;font-weight:800;background:rgba(29,78,216,0.07);padding:2px 9px;border-radius:20px;">0 / <?= $totalCriterios ?></span>
                            </div>
                            <div style="background:#DDE2F0;border-radius:8px;height:5px;overflow:hidden;">
                                <div id="gaugeProgress" style="height:100%;background:#1D4ED8;border-radius:8px;width:0%;transition:width .4s ease;"></div>
                            </div>
                        </div>
                        <div class="ev-gauge-legend" style="display:flex;flex-wrap:wrap;gap:4px;padding:0 2px;margin-top:6px;justify-content:center;">
                            <span style="font-size:.6rem;font-weight:800;padding:2px 7px;border-radius:20px;background:#fecaca;color:#b91c1c;">★ Deficiente</span>
                            <span style="font-size:.6rem;font-weight:800;padding:2px 7px;border-radius:20px;background:#fee2e2;color:#dc2626;">★★ Bajo Promedio</span>
                            <span style="font-size:.6rem;font-weight:800;padding:2px 7px;border-radius:20px;background:#fef3c7;color:#b45309;">★★★ Promedio</span>
                            <span style="font-size:.6rem;font-weight:800;padding:2px 7px;border-radius:20px;background:#d1fae5;color:#059669;">★★★★ Sobre Prom.</span>
                            <span style="font-size:.6rem;font-weight:800;padding:2px 7px;border-radius:20px;background:#dcfce7;color:#15803d;">★★★★★ Excelente</span>
                        </div>
                    </div>

                    <!-- Controles -->
                    <div style="background:white;border-radius:20px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05),0 2px 4px -2px rgba(0,0,0,0.05);border:1px solid #DDE2F0;overflow:hidden;">

                        <!-- Cuerpo de controles (siempre visible) -->
                        <div id="mobConfigBody" style="display:flex;flex-direction:column;gap:14px;padding:20px;">

                        <!-- Tutor — lógica de roles del proyecto actual -->
                        <div>
                            <label style="font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.8px;display:flex;align-items:center;gap:5px;margin-bottom:7px;">
                                <i class="ti ti-user-check" style="color:#1D4ED8;font-size:0.85rem;"></i> Tutor Evaluador *
                            </label>
                            <?php
                            $rolIdVista = (int)(Session::get('role_id') ?? 0);
                            if ($rolIdVista === 1): ?>
                                <select name="tutor_id" id="fTutorId" required
                                    style="width:100%;padding:9px 12px;border:2px solid #f1f5f9;border-radius:12px;font-size:0.83rem;color:#0D1424;background:#F8FAFD;transition:all .3s;outline:none;"
                                    onfocus="this.style.borderColor='#1D4ED8';this.style.background='#fff';this.style.boxShadow='0 0 0 4px rgba(29,78,216,0.07)'" onblur="this.style.borderColor='#f1f5f9';this.style.background='#F8FAFD';this.style.boxShadow='none'">
                                    <option value="">— Automático (Tutor Asignado) —</option>
                                    <?php foreach ($tutores as $t): ?>
                                    <option value="<?= (int)$t->id ?>" <?= ($tutorActualId && (int)$t->id === (int)$tutorActualId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(trim(($t->nombres ?? '') . ' ' . ($t->apellidos ?? ''))) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <select name="tutor_id" id="fTutorId" required
                                    style="width:100%;padding:9px 12px;border:2px solid #f1f5f9;border-radius:12px;font-size:0.83rem;color:#0D1424;background:#F8FAFD;transition:all .3s;outline:none;"
                                    onfocus="this.style.borderColor='#1D4ED8';this.style.background='#fff';this.style.boxShadow='0 0 0 4px rgba(29,78,216,0.07)'" onblur="this.style.borderColor='#f1f5f9';this.style.background='#F8FAFD';this.style.boxShadow='none'">
                                    <option value="">— Automático (Tutor Asignado) —</option>
                                    <?php foreach ($tutores as $t): ?>
                                    <option value="<?= (int)$t->id ?>" <?= ($tutorActualId && (int)$t->id === (int)$tutorActualId) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(trim(($t->nombres ?? '') . ' ' . ($t->apellidos ?? ''))) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color:#94a3b8;font-size:0.68rem;margin-top:4px;display:block;">
                                    <i class="ti ti-info-circle"></i> Por defecto: tutor asignado al pasante.
                                </small>
                            <?php endif; ?>
                        </div>

                        <!-- Fecha + Periodo — automáticos (read-only), apilados -->
                        <div class="ev-meta-grid" style="display:grid;grid-template-columns:1fr;gap:10px;">
                            <div>
                                <label style="font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.8px;display:flex;align-items:center;gap:5px;margin-bottom:7px;">
                                    <i class="ti ti-calendar-event" style="color:#1D4ED8;font-size:0.8rem;"></i> Fecha
                                </label>
                                <div class="ev-field-ro" style="width:100%;padding:8px 10px;border:2px solid #f1f5f9;border-radius:12px;font-size:0.8rem;color:#0D1424;background:#F8FAFD;box-sizing:border-box;display:flex;align-items:center;gap:6px;">
                                    <i class="ti ti-check" style="color:#10b981;font-size:0.85rem;flex-shrink:0;"></i>
                                    <span id="dispFecha" style="flex:1;font-weight:600;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= date('d/m/Y') ?></span>
                                    <span class="ev-auto-badge" style="background:#dcfce7;color:#15803d;font-size:0.6rem;font-weight:700;padding:2px 7px;border-radius:20px;text-transform:uppercase;white-space:nowrap;flex-shrink:0;">Auto</span>
                                </div>
                                <input type="hidden" name="fecha_evaluacion" id="fFecha" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label style="font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.8px;display:flex;align-items:center;gap:5px;margin-bottom:7px;">
                                    <i class="ti ti-school" style="color:#1D4ED8;font-size:0.8rem;"></i> Periodo
                                </label>
                                <div class="ev-field-ro" style="width:100%;padding:8px 10px;border:2px solid #f1f5f9;border-radius:12px;font-size:0.8rem;color:#0D1424;background:#F8FAFD;box-sizing:border-box;display:flex;align-items:center;gap:6px;">
                                    <i class="ti ti-check" style="color:#10b981;font-size:0.85rem;flex-shrink:0;"></i>
                                    <span id="dispLapso" style="flex:1;font-weight:600;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">—</span>
                                    <span class="ev-auto-badge" style="background:#dcfce7;color:#15803d;font-size:0.6rem;font-weight:700;padding:2px 7px;border-radius:20px;text-transform:uppercase;white-space:nowrap;flex-shrink:0;">Auto</span>
                                </div>
                                <input type="hidden" name="lapso_academico" id="fLapso" value="">
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div>
                            <label style="font-size:0.72rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.8px;display:flex;align-items:center;gap:5px;margin-bottom:7px;">
                                <i class="ti ti-notes" style="color:#1D4ED8;font-size:0.8rem;"></i> Observaciones
                            </label>
                            <textarea name="observaciones" id="fObs" rows="2" placeholder="Fortalezas, áreas de mejora..."
                                style="width:100%;padding:9px 12px;border:2px solid #f1f5f9;border-radius:12px;font-size:0.8rem;color:#0D1424;background:#F8FAFD;resize:none;box-sizing:border-box;transition:all .3s;font-family:inherit;outline:none;"
                                onfocus="this.style.borderColor='#1D4ED8';this.style.background='#fff';this.style.boxShadow='0 0 0 4px rgba(29,78,216,0.07)'" onblur="this.style.borderColor='#f1f5f9';this.style.background='#F8FAFD';this.style.boxShadow='none'"></textarea>
                        </div>

                        <!-- Error -->
                        <div id="panelError" style="display:none;background:#fef2f2;color:#dc2626;padding:9px 12px;border-radius:10px;font-size:0.8rem;font-weight:600;align-items:center;gap:7px;border:1px solid #fecaca;">
                            <i class="ti ti-alert-triangle"></i> <span id="panelErrorTxt"></span>
                        </div>

                        <!-- Guardar (desktop) -->
                        <button type="button" id="btnGuardar" onclick="EvalApp.guardar()"
                            style="width:100%;padding:13px;border:none;border-radius:12px;background:#1D4ED8;color:#fff;font-weight:700;font-size:0.88rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 14px rgba(29,78,216,.3);transition:all .3s;"
                            onmouseover="this.style.background='#1e40af';this.style.transform='translateY(-2px)';this.style.boxShadow='0 10px 20px rgba(29,78,216,.35)'"
                            onmouseout="this.style.background='#1D4ED8';this.style.transform='none';this.style.boxShadow='0 4px 14px rgba(29,78,216,.3)'">
                            <i class="ti ti-check" style="font-size:1rem;"></i> Guardar Evaluación
                        </button>

                        </div><!-- /mobConfigBody -->
                    </div><!-- /controles card -->

                </div><!-- /ev-sidebar -->
            </div><!-- /ev-panel-b-grid -->
        </form>

    </div><!-- /vistaEvaluacion -->

</div><!-- /evalSlider -->
</div><!-- /evalOuter -->

<!-- Barra Guardar fija (solo móvil) -->
<div class="ev-save-bar" style="display:none;">
    <button type="button" onclick="EvalApp.guardar()"
        style="width:100%;padding:14px;border:none;border-radius:14px;background:linear-gradient(135deg,#1D4ED8,#2563eb);color:#fff;font-weight:700;font-size:0.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 18px rgba(29,78,216,.35);">
        <i class="ti ti-device-floppy" style="font-size:1.1rem;"></i> Guardar Evaluación
    </button>
</div>

</div><!-- /dashboard-container -->


<!-- ═══ MODAL: DETALLE (read-only) ═══════════════════════════════════════════ -->
<div id="modalDetalleEval" class="sgp-modal-overlay">
    <div class="sgp-modal" style="max-width:650px;border-radius:20px;">
        <div class="sgp-modal-header" style="padding:22px 24px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="background:rgba(255,255,255,0.15);border-radius:12px;padding:10px;">
                    <i class="ti ti-file-analytics" style="font-size:1.4rem;color:white;"></i>
                </div>
                <div>
                    <h3 style="margin:0;font-size:1.1rem;">Detalle de Evaluación</h3>
                    <p id="detalleEvalSubtitulo" style="margin:2px 0 0;font-size:0.78rem;opacity:.8;">Cargando...</p>
                </div>
            </div>
            <button class="sgp-modal-close" onclick="cerrarModalDetalle()"><i class="ti ti-x"></i></button>
        </div>
        <div class="sgp-modal-body" id="bodyDetalleEval" style="padding:22px 24px;max-height:70vh;overflow-y:auto;">
            <div style="text-align:center;padding:40px;"><i class="ti ti-loader sgp-spin" style="font-size:2rem;color:#1e3a8a;"></i></div>
        </div>
        <div style="padding:14px 24px;border-top:1px solid #f1f5f9;display:flex;justify-content:flex-end;background:#f8fafc;border-bottom-left-radius:20px;border-bottom-right-radius:20px;">
            <button onclick="cerrarModalDetalle()" style="padding:9px 20px;border:1.5px solid #e2e8f0;border-radius:9px;background:white;color:#64748b;font-weight:600;font-size:0.87rem;cursor:pointer;">Cerrar</button>
        </div>
    </div>
</div>


<script>
var EVALS_DATA      = <?= $evalsJs ?>;
var TUTORES_DATA    = <?= $tutoresJs ?>;
var TOTAL_CRITERIOS = <?= $totalCriterios ?>;
</script>
<script src="<?= URLROOT ?>/js/evaluaciones.js?v=<?= time() ?>"></script>
<script>
/* ── Móvil: navegación push nativa (panel B entra desde la derecha) ── */
(function () {
    var panelB  = document.getElementById('vistaEvaluacion');
    var saveBar = document.querySelector('.ev-save-bar');

    function esMobile() { return window.innerWidth <= 767; }

    function mostrarPanelB() {
        if (!esMobile()) return;
        panelB.classList.add('ev-panel-visible');
        if (saveBar) saveBar.classList.add('ev-save-active');
        panelB.scrollTop = 0;
        document.body.style.overflow = 'hidden';
    }
    function ocultarPanelB() {
        panelB.classList.remove('ev-panel-visible');
        if (saveBar) saveBar.classList.remove('ev-save-active');
        document.body.style.overflow = '';
    }

    /* Esperar a que EvalApp cargue — sin límite de intentos */
    var chk = setInterval(function () {
        if (typeof EvalApp === 'undefined' || typeof EvalApp.abrirFormulario === 'undefined') return;
        clearInterval(chk);

        /* Solo parchear una vez */
        if (EvalApp.__mobilePatchDone) return;
        EvalApp.__mobilePatchDone = true;

        var _abrir = EvalApp.abrirFormulario.bind(EvalApp);
        var _nueva = EvalApp.nuevaDesdeBoton ? EvalApp.nuevaDesdeBoton.bind(EvalApp) : null;

        EvalApp.abrirFormulario = function (p) {
            _abrir(p);
            mostrarPanelB();
        };

        /* Volver: confirmar si hay datos calificados */
        EvalApp.volver = function () {
            var inputs  = Array.from(document.querySelectorAll('#formEvaluacion .star-input'));
            var hasData = inputs.some(function(i) { return parseInt(i.value) > 0; });

            if (!hasData) {
                if (esMobile()) {
                    ocultarPanelB();
                } else {
                    document.getElementById('evalSlider').style.transform = 'translateX(0)';
                    document.body.style.overflow = '';
                    if (typeof EvalApp !== 'undefined') EvalApp._restaurarPanelA();
                }
                return;
            }

            Swal.fire({
                title: '¿Salir de la evaluación?',
                html: '<p style="color:#475569;font-size:0.9rem;margin:0;">Los criterios ya calificados <strong>se perderán</strong> si vuelves atrás.</p>',
                icon: 'warning',
                iconColor: '#f59e0b',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="ti ti-arrow-left"></i> Sí, volver',
                cancelButtonText: '<i class="ti ti-pencil"></i> Seguir evaluando',
                reverseButtons: true,
                customClass: {
                    popup:         'swal-eval-popup',
                    title:         'swal-eval-title',
                    confirmButton: 'swal-eval-btn-confirm',
                    cancelButton:  'swal-eval-btn-cancel',
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    if (esMobile()) {
                        ocultarPanelB();
                    } else {
                        document.getElementById('evalSlider').style.transform = 'translateX(0)';
                        document.body.style.overflow = '';
                        if (typeof EvalApp !== 'undefined') EvalApp._restaurarPanelA();
                    }
                }
            });
        };

        if (_nueva) {
            EvalApp.nuevaDesdeBoton = function () { _nueva(); mostrarPanelB(); };
        }

    }, 30);
})();
</script>
<?php if (!empty($abrir_modal)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var pasanteId = <?= (int)($_GET['pasante_id'] ?? 0) ?>;
    setTimeout(function () {
        // Abrir el formulario de nueva evaluación
        if (typeof EvalApp !== 'undefined' && typeof EvalApp.nuevaDesdeBoton === 'function') {
            EvalApp.nuevaDesdeBoton();
        } else if (typeof mostrarPanelB === 'function') {
            mostrarPanelB();
        }
        // Preseleccionar el pasante si viene en la URL
        if (pasanteId > 0) {
            setTimeout(function () {
                var sel = document.getElementById('selectPasanteEval') ||
                          document.querySelector('select[name="pasante_id"]');
                if (sel) {
                    sel.value = pasanteId;
                    sel.dispatchEvent(new Event('change'));
                }
                // También intentar click en la card del pasante si EvalApp lo soporta
                if (typeof EvalApp !== 'undefined' && typeof EvalApp.abrirFormulario === 'function') {
                    EvalApp.abrirFormulario(pasanteId);
                }
            }, 300);
        }
    }, 400);
});
</script>
<?php endif; ?>
