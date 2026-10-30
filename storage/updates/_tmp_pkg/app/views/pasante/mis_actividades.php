<?php
/* ══════════════════════════════════════════════════════
   Mis Actividades — Bento UI v1
   Variables: $pasante, $actividades[]
   Campos actividad: id, fecha, titulo, descripcion,
                     fecha_fmt, mes_label, created_at
   ══════════════════════════════════════════════════════ */
$pasante     = $data['pasante']     ?? null;
$actividades = $data['actividades'] ?? [];

$total      = count($actividades);
$esteMes    = date('Y-m');
$esteMesCnt = count(array_filter($actividades, fn($a) => str_starts_with($a->fecha ?? '', $esteMes)));
$ultima     = $actividades[0] ?? null;

// Agrupar por mes para el timeline
$porMes = [];
foreach ($actividades as $act) {
    $key = $act->mes_label ?? date('M Y', strtotime($act->fecha));
    $porMes[$key][] = $act;
}
?>
<style>
/* ── keyframes ── */
@keyframes actFadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes actPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.04)}}

/* ── layout ── */
.act-wrap{display:flex;flex-direction:column;gap:22px;animation:actFadeUp .45s ease both}

/* ── hero ── */
.act-hero{
    background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 45%,#2563eb 100%);
    border-radius:24px;padding:32px 36px;position:relative;overflow:hidden;
    display:flex;align-items:center;gap:20px;flex-wrap:wrap;
    box-shadow:0 8px 32px rgba(15,23,42,.35);
}
.act-hero::before{
    content:'';position:absolute;top:-60px;right:-60px;
    width:260px;height:260px;border-radius:50%;
    background:rgba(255,255,255,.05);
    pointer-events:none;
}
.act-hero::after{
    content:'';position:absolute;bottom:-40px;left:30%;
    width:160px;height:160px;border-radius:50%;
    background:rgba(255,255,255,.03);
    pointer-events:none;
}
.act-hero-icon{
    background:rgba(255,255,255,.12);backdrop-filter:blur(8px);
    border:2px solid rgba(255,255,255,.2);border-radius:18px;
    padding:16px;z-index:1;animation:actPulse 3s ease-in-out infinite;
    flex-shrink:0;
}
.act-hero-badge{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);
    color:rgba(255,255,255,.9);border-radius:999px;
    padding:4px 12px;font-size:.75rem;font-weight:700;
    letter-spacing:.4px;text-transform:uppercase;margin-bottom:8px;
    backdrop-filter:blur(8px);
}

/* ── KPI row ── */
.act-kpi-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
@media(max-width:640px){.act-kpi-row{grid-template-columns:1fr}}

@media(max-width:768px){
    .act-hero {
        padding: 20px 18px;
        border-radius: 18px;
        flex-direction: column;
        align-items: flex-start;
        gap: 14px;
    }
    .act-hero > div:last-child {
        width: 100%;
        box-sizing: border-box;
    }
    .act-action-bar {
        padding: 14px 16px;
        border-radius: 14px;
        flex-direction: column;
        align-items: stretch;
    }
    .act-btn-add {
        width: 100%;
        justify-content: center;
    }
}
@media(max-width:480px){
    .act-hero-icon {
        padding: 12px;
        border-radius: 14px;
    }
    .act-hero-icon i {
        font-size: 24px !important;
    }
    .act-kpi {
        padding: 14px 16px;
        border-radius: 14px;
    }
    .act-kpi-val {
        font-size: 1.6rem;
    }
}

.act-kpi{
    background:#fff;border-radius:18px;padding:20px 22px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);border:1px solid #f1f5f9;
    position:relative;overflow:hidden;
    animation:actFadeUp .5s ease both;
    transition:transform .2s,box-shadow .2s;
}
.act-kpi:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
.act-kpi-accent{position:absolute;top:0;left:0;right:0;height:3px;border-radius:18px 18px 0 0;}
.act-kpi-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:6px}
.act-kpi-val{font-size:1.9rem;font-weight:900;line-height:1;margin-bottom:4px}
.act-kpi-sub{font-size:.75rem;color:#94a3b8}

/* ── action bar ── */
.act-action-bar{
    display:flex;align-items:center;justify-content:space-between;
    background:#fff;border-radius:18px;padding:16px 24px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);border:1px solid #f1f5f9;
    gap:12px;flex-wrap:wrap;
}

/* ── filter bar ── */
.act-filter-row{
    display:flex;align-items:center;gap:10px;flex-wrap:wrap;
    padding-top:14px;border-top:1px solid #f1f5f9;margin-top:14px;
}
.act-search-wrap{
    flex:1;min-width:180px;position:relative;
}
.act-search-wrap i{
    position:absolute;left:11px;top:50%;transform:translateY(-50%);
    color:#94a3b8;font-size:.9rem;pointer-events:none;
}
.act-search-input{
    width:100%;border:1.5px solid #e2e8f0;border-radius:10px;
    padding:8px 12px 8px 34px;font-size:.88rem;color:#1e293b;
    outline:none;background:#f8fafc;transition:border-color .2s,box-shadow .2s;
    box-sizing:border-box;
}
.act-search-input:focus{border-color:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.1);background:#fff;}
.act-filter-select{
    border:1.5px solid #e2e8f0;border-radius:10px;padding:8px 12px;
    font-size:.85rem;color:#475569;background:#f8fafc;cursor:pointer;outline:none;
    transition:border-color .2s;
}
.act-filter-select:focus{border-color:#059669;}
.act-filter-reset{
    background:#f1f5f9;color:#64748b;border:none;border-radius:10px;
    padding:8px 14px;font-size:.83rem;font-weight:600;cursor:pointer;
    display:flex;align-items:center;gap:5px;transition:background .15s;
    white-space:nowrap;
}
.act-filter-reset:hover{background:#e2e8f0;}
.act-results-count{
    font-size:.75rem;font-weight:700;color:#94a3b8;
    white-space:nowrap;margin-left:auto;
}
.act-no-results{
    background:#fff;border-radius:16px;padding:48px 24px;text-align:center;
    border:1px dashed #e2e8f0;display:none;
}
.act-no-results i{font-size:2.2rem;color:#cbd5e1;display:block;margin-bottom:12px;}
.act-no-results p{margin:0;color:#94a3b8;font-size:.9rem;}
.act-btn-add{
    display:inline-flex;align-items:center;gap:8px;
    background:linear-gradient(135deg,#059669,#10b981);
    color:#fff;border:none;border-radius:12px;
    padding:10px 20px;font-size:.9rem;font-weight:700;
    cursor:pointer;transition:transform .15s,box-shadow .15s;
    box-shadow:0 4px 14px rgba(5,150,105,.35);
}
.act-btn-add:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(5,150,105,.45)}

/* ── timeline ── */
.act-timeline{display:flex;flex-direction:column;gap:0}
.act-month-group{margin-bottom:24px}
.act-month-label{
    display:inline-flex;align-items:center;gap:8px;
    font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;
    color:#059669;background:#f0fdf4;border:1px solid #bbf7d0;
    border-radius:999px;padding:5px 14px;margin-bottom:14px;
}

.act-card{
    background:#fff;border-radius:16px;padding:0;
    box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid #f1f5f9;
    display:flex;align-items:stretch;gap:0;margin-bottom:12px;overflow:hidden;
    transition:transform .18s,box-shadow .18s;
    animation:actFadeUp .5s ease both;
}
.act-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.09)}
.act-card-side{
    width:70px;flex-shrink:0;
    background:linear-gradient(160deg,#064e3b,#059669);
    display:flex;flex-direction:column;align-items:center;
    justify-content:center;gap:2px;padding:14px 8px;color:#fff;
}
.act-card-day{font-size:1.6rem;font-weight:900;line-height:1}
.act-card-dow{font-size:.65rem;font-weight:700;opacity:.75;text-transform:uppercase;letter-spacing:.3px}
.act-card-body{flex:1;padding:16px 20px;min-width:0}
.act-card-title{
    font-size:.95rem;font-weight:700;color:#1e293b;
    margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.act-card-desc{
    font-size:.83rem;color:#64748b;line-height:1.6;
    display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;
}
.act-card-meta{
    display:flex;align-items:center;gap:12px;margin-top:10px;
    font-size:.73rem;color:#94a3b8;flex-wrap:wrap;
}
.act-card-actions{
    display:flex;align-items:center;padding:12px;flex-shrink:0;gap:4px;
}
.act-btn-del{
    background:#fef2f2;color:#ef4444;border:none;border-radius:9px;
    width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;
    font-size:.95rem;transition:background .15s,transform .15s;
}
.act-btn-del:hover{background:#fee2e2;transform:scale(1.1)}
.act-btn-edit{
    background:#eff6ff;color:#2563eb;border:none;border-radius:9px;
    width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;
    font-size:.95rem;transition:background .15s,transform .15s;
}
.act-btn-edit:hover{background:#dbeafe;transform:scale(1.1)}

/* ── empty state ── */
.act-empty{
    background:#fff;border-radius:20px;padding:72px 24px;
    box-shadow:0 2px 14px rgba(0,0,0,.06);border:1px solid #f1f5f9;
    text-align:center;animation:actFadeUp .5s ease both;
}
.act-empty-icon{
    width:80px;height:80px;border-radius:50%;
    background:linear-gradient(135deg,#d1fae5,#a7f3d0);
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 18px;font-size:2.2rem;color:#059669;
}

/* ── modal ── */
.act-modal-overlay{
    display:none;position:fixed;inset:0;z-index:9000;
    background:rgba(0,0,0,.55);backdrop-filter:blur(4px);
    align-items:center;justify-content:center;padding:16px;
}
.act-modal-overlay.active{display:flex}
.act-modal{
    background:#fff;border-radius:24px;width:100%;max-width:540px;
    box-shadow:0 24px 80px rgba(0,0,0,.2);
    animation:actFadeUp .3s ease both;overflow:hidden;
}
.act-modal-header{
    background:linear-gradient(135deg,#064e3b,#059669);
    padding:24px 28px;display:flex;align-items:center;gap:14px;
}
.act-modal-body{padding:28px}
.act-form-group{margin-bottom:18px}
.act-form-label{
    display:block;font-size:.8rem;font-weight:700;
    color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;
}
.act-form-input,.act-form-textarea{
    width:100%;border:1.5px solid #e2e8f0;border-radius:10px;
    padding:10px 14px;font-size:.9rem;color:#1e293b;
    outline:none;transition:border-color .2s,box-shadow .2s;
    font-family:inherit;box-sizing:border-box;
}
.act-form-input:focus,.act-form-textarea:focus{
    border-color:#059669;
    box-shadow:0 0 0 3px rgba(5,150,105,.12);
}
.act-form-textarea{resize:vertical;min-height:120px;line-height:1.6}
.act-modal-footer{
    padding:0 28px 24px;display:flex;gap:10px;justify-content:flex-end;
}
.act-btn-cancel{
    background:#f1f5f9;color:#64748b;border:none;border-radius:10px;
    padding:10px 20px;font-size:.88rem;font-weight:600;cursor:pointer;
    transition:background .15s;
}
.act-btn-cancel:hover{background:#e2e8f0}
.act-btn-save{
    background:linear-gradient(135deg,#059669,#10b981);color:#fff;
    border:none;border-radius:10px;padding:10px 22px;
    font-size:.88rem;font-weight:700;cursor:pointer;
    box-shadow:0 4px 12px rgba(5,150,105,.3);transition:transform .15s,box-shadow .15s;
}
.act-btn-save:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(5,150,105,.4)}
.act-btn-save:disabled{opacity:.6;cursor:not-allowed;transform:none}

/* ─── DARK MODE SUPPORT (MIS ACTIVIDADES) ────────────── */
[data-theme="dark"] .act-hero {
    background: linear-gradient(135deg, #090d16 0%, #064e3b 45%, #059669 100%) !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6) !important;
}
[data-theme="dark"] .act-kpi {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-theme="dark"] .act-kpi-val {
    color: #f8fafc !important;
}
[data-theme="dark"] .act-kpi-label,
[data-theme="dark"] .act-kpi-sub {
    color: #94a3b8 !important;
}
[data-theme="dark"] .act-action-bar {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-theme="dark"] .act-filter-row {
    border-top-color: #1e293b !important;
}
[data-theme="dark"] .act-search-input {
    background: #0d1526 !important;
    border-color: #1e293b !important;
    color: #f8fafc !important;
}
[data-theme="dark"] .act-search-input:focus {
    border-color: #10b981 !important;
    background: #111a2e !important;
}
[data-theme="dark"] .act-filter-select {
    background: #0d1526 !important;
    border-color: #1e293b !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .act-filter-reset {
    background: #1e293b !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .act-filter-reset:hover {
    background: #334155 !important;
    color: #f8fafc !important;
}
[data-theme="dark"] .act-results-count {
    color: #94a3b8 !important;
}
[data-theme="dark"] .act-no-results {
    background: #111a2e !important;
    border-color: #1e293b !important;
}
[data-theme="dark"] .act-card {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-theme="dark"] .act-card-title {
    color: #f8fafc !important;
}
[data-theme="dark"] .act-card-desc {
    color: #94a3b8 !important;
}
[data-theme="dark"] .act-card-meta {
    color: #64748b !important;
}
[data-theme="dark"] .act-month-label {
    background: rgba(5, 150, 105, 0.15) !important;
    border-color: rgba(5, 150, 105, 0.3) !important;
    color: #34d399 !important;
}
[data-theme="dark"] .act-btn-del {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #f87171 !important;
}
[data-theme="dark"] .act-btn-del:hover {
    background: rgba(239, 68, 68, 0.25) !important;
}
[data-theme="dark"] .act-btn-edit {
    background: rgba(37, 99, 235, 0.15) !important;
    color: #60a5fa !important;
}
[data-theme="dark"] .act-btn-edit:hover {
    background: rgba(37, 99, 235, 0.25) !important;
}
[data-theme="dark"] .act-empty {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .act-modal {
    background: #0f172a !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.7) !important;
}
[data-theme="dark"] .act-form-label {
    color: #cbd5e1 !important;
}
[data-theme="dark"] .act-form-input,
[data-theme="dark"] .act-form-textarea {
    background: #111a2e !important;
    border-color: #1e293b !important;
    color: #f8fafc !important;
}
[data-theme="dark"] .act-form-input:focus,
[data-theme="dark"] .act-form-textarea:focus {
    border-color: #10b981 !important;
}
[data-theme="dark"] .act-btn-cancel {
    background: #1e293b !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .act-btn-cancel:hover {
    background: #334155 !important;
    color: #f8fafc !important;
}
</style>

<div class="act-wrap">

<!-- ══════════════════════ HERO ══════════════════════ -->
<div class="act-hero">
    <div class="act-hero-icon">
        <i class="ti ti-writing" style="font-size:32px;color:rgba(255,255,255,.9);"></i>
    </div>
    <div style="z-index:1;flex:1;">
        <div class="act-hero-badge">
            <i class="ti ti-calendar-event" style="font-size:.75rem;"></i>
            Registro Diario
        </div>
        <h1 style="color:#fff;font-size:1.65rem;font-weight:800;margin:0 0 4px;text-shadow:0 2px 8px rgba(0,0,0,.15);">
            Mis Actividades
        </h1>
        <p style="color:rgba(255,255,255,.75);margin:0;font-size:.88rem;">
            <?php if ($total > 0): ?>
                <?= $total ?> actividad<?= $total > 1 ? 'es' : '' ?> registrada<?= $total > 1 ? 's' : '' ?>
                &nbsp;·&nbsp; <?= $esteMesCnt ?> este mes
            <?php else: ?>
                Registra el resumen de lo que haces cada día en tu pasantía
            <?php endif; ?>
        </p>
    </div>
    <?php if ($ultima): ?>
    <div style="z-index:1;text-align:center;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:16px;padding:12px 18px;">
        <div style="font-size:.65rem;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:.5px;font-weight:700;margin-bottom:4px;">Última</div>
        <div style="font-size:1.1rem;font-weight:800;color:#93c5fd;line-height:1;"><?= htmlspecialchars($ultima->fecha_fmt) ?></div>
        <div style="font-size:.72rem;color:rgba(255,255,255,.7);margin-top:2px;max-width:110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            <?= htmlspecialchars($ultima->titulo) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════════════ KPI ROW ══════════════════════ -->
<div class="act-kpi-row">
    <?php
    $kpis = [
        ['label'=>'Total',       'val'=>$total,        'sub'=>'actividades registradas', 'color'=>'#059669', 'icon'=>'ti-clipboard-list'],
        ['label'=>'Este mes',    'val'=>$esteMesCnt,   'sub'=>date('F Y'),               'color'=>'#0ea5e9', 'icon'=>'ti-calendar-month'],
        ['label'=>'Meses activos','val'=>count($porMes),'sub'=>'con al menos 1 actividad','color'=>'#8b5cf6','icon'=>'ti-chart-bar'],
    ];
    foreach ($kpis as $i => $k): ?>
    <div class="act-kpi" style="animation-delay:<?= $i * 0.07 ?>s">
        <div class="act-kpi-accent" style="background:<?= $k['color'] ?>;"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;margin-top:8px;">
            <span class="act-kpi-label"><?= $k['label'] ?></span>
            <span style="width:32px;height:32px;border-radius:10px;background:<?= $k['color'] ?>18;display:flex;align-items:center;justify-content:center;color:<?= $k['color'] ?>;">
                <i class="ti <?= $k['icon'] ?>" style="font-size:1rem;"></i>
            </span>
        </div>
        <div class="act-kpi-val" style="color:<?= $k['color'] ?>"><?= $k['val'] ?></div>
        <div class="act-kpi-sub"><?= htmlspecialchars($k['sub']) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ══════════════════════ ACTION BAR + FILTROS ══════════════════════ -->
<div class="act-action-bar" style="flex-direction:column;align-items:stretch;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <div style="font-size:.78rem;font-weight:700;color:#374151;">Historial de actividades</div>
            <div style="font-size:.75rem;color:#94a3b8;margin-top:2px;">
                Documenta lo que realizas cada día durante tu pasantía
            </div>
        </div>
        <button class="act-btn-add" onclick="abrirModalActividad()">
            <i class="ti ti-plus"></i> Nueva Actividad
        </button>
    </div>

    <?php if ($total > 0): ?>
    <div class="act-filter-row">
        <!-- Búsqueda por texto -->
        <div class="act-search-wrap">
            <i class="ti ti-search"></i>
            <input type="text" id="actSearch" class="act-search-input"
                   placeholder="Buscar por título o descripción…"
                   oninput="filtrarActividades()">
        </div>

        <!-- Filtro por mes -->
        <select id="actMesFilter" class="act-filter-select" onchange="filtrarActividades()">
            <option value="">Todos los meses</option>
            <?php foreach (array_keys($porMes) as $ml): ?>
            <option value="<?= htmlspecialchars($ml) ?>"><?= htmlspecialchars($ml) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Limpiar -->
        <button class="act-filter-reset" onclick="resetFiltros()" title="Limpiar filtros">
            <i class="ti ti-refresh"></i> Limpiar
        </button>

        <!-- Contador de resultados -->
        <span class="act-results-count" id="actContador"><?= $total ?> actividad<?= $total !== 1 ? 'es' : '' ?></span>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════════════ TIMELINE / EMPTY ══════════════════════ -->
<!-- Sin resultados de búsqueda -->
<div class="act-no-results" id="actNoResults">
    <i class="ti ti-search-off"></i>
    <p>No se encontraron actividades con esos criterios.<br>
       <span style="font-size:.8rem;">Intenta con otro término o cambia el mes.</span>
    </p>
</div>

<?php if ($total === 0): ?>
<div class="act-empty">
    <div class="act-empty-icon">
        <i class="ti ti-writing"></i>
    </div>
    <p style="font-size:1.1rem;font-weight:700;color:#1e293b;margin:0 0 8px;">Aún no hay actividades registradas</p>
    <p style="font-size:.88rem;color:#94a3b8;max-width:380px;margin:0 auto 22px;line-height:1.65;">
        Registra un breve resumen de lo que realizas cada día. Es un diario personal de tu pasantía.
    </p>
    <button class="act-btn-add" onclick="abrirModalActividad()" style="margin:0 auto;">
        <i class="ti ti-plus"></i>
        Registrar primera actividad
    </button>
</div>

<?php else: ?>
<div class="act-timeline">
    <?php foreach ($porMes as $mesLabel => $actsMes): ?>
    <div class="act-month-group" data-grupo="<?= htmlspecialchars($mesLabel) ?>">
        <div class="act-month-label" data-mes="<?= htmlspecialchars($mesLabel) ?>">
            <i class="ti ti-calendar" style="font-size:.8rem;"></i>
            <?= htmlspecialchars($mesLabel) ?>
            <span style="background:#059669;color:#fff;border-radius:999px;padding:1px 8px;font-size:.68rem;"><?= count($actsMes) ?></span>
        </div>

        <?php foreach ($actsMes as $idx => $act): ?>
        <?php
            $ts  = strtotime($act->fecha);
            $dia = date('d', $ts);
            $dow = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'][date('w', $ts)];
        ?>
        <div class="act-card" style="animation-delay:<?= $idx * 0.05 ?>s" id="act-card-<?= $act->id ?>"
             data-titulo="<?= htmlspecialchars(mb_strtolower($act->titulo ?? '')) ?>"
             data-desc="<?= htmlspecialchars(mb_strtolower($act->descripcion ?? '')) ?>"
             data-mes="<?= htmlspecialchars($mesLabel) ?>">
            <div class="act-card-side">
                <div class="act-card-day"><?= $dia ?></div>
                <div class="act-card-dow"><?= $dow ?></div>
            </div>
            <div class="act-card-body">
                <div class="act-card-title"><?= htmlspecialchars($act->titulo) ?></div>
                <div class="act-card-desc"><?= nl2br(htmlspecialchars($act->descripcion)) ?></div>
                <div class="act-card-meta">
                    <span><i class="ti ti-clock" style="margin-right:3px;"></i><?= htmlspecialchars($act->fecha_fmt) ?></span>
                    <?php if (!empty($act->correccion)): ?>
                    <span style="background:#fef3c7;color:#92400e;border-radius:999px;padding:2px 9px;font-weight:700;font-size:.68rem;display:inline-flex;align-items:center;gap:4px;">
                        <i class="ti ti-edit" style="font-size:.72rem;"></i>Tiene corrección
                    </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($act->correccion)): ?>
                <div style="margin-top:10px;background:#fffbeb;border:1px solid #fde68a;border-left:3px solid #d97706;border-radius:8px;padding:9px 12px;">
                    <div style="font-size:.68rem;font-weight:800;color:#92400e;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;display:flex;align-items:center;gap:4px;">
                        <i class="ti ti-edit" style="font-size:.75rem;"></i>
                        Corrección<?php if (!empty($act->corrector_nombre)): ?> de <?= htmlspecialchars($act->corrector_nombre) ?><?php endif; ?>
                    </div>
                    <div style="font-size:.78rem;color:#78350f;line-height:1.55;"><?= nl2br(htmlspecialchars($act->correccion)) ?></div>
                </div>
                <?php endif; ?>
            </div>
            <div class="act-card-actions">
                <button class="act-btn-edit" title="Editar actividad"
                        onclick="abrirEditarActividad(<?= $act->id ?>,<?= htmlspecialchars(json_encode($act->titulo ?? ''), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($act->descripcion ?? ''), ENT_QUOTES) ?>,<?= htmlspecialchars(json_encode($act->fecha ?? ''), ENT_QUOTES) ?>)">
                    <i class="ti ti-pencil"></i>
                </button>
                <button class="act-btn-del" title="Eliminar" onclick="eliminarActividad(<?= $act->id ?>, this)">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══════════════════════ NOTA INFORMATIVA ══════════════════════ -->
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-left:4px solid #059669;border-radius:12px;padding:14px 18px;display:flex;align-items:flex-start;gap:12px;">
    <i class="ti ti-info-circle" style="color:#059669;font-size:1rem;margin-top:2px;flex-shrink:0;"></i>
    <p style="margin:0;font-size:.82rem;color:#065f46;line-height:1.6;">
        <strong>Tu diario personal:</strong>
        Registra aquí un resumen de las actividades que realizaste cada día. Solo tú puedes ver y gestionar tus entradas.
        No está vinculado al registro de asistencia — es un complemento para documentar tu experiencia.
    </p>
</div>

</div><!-- /act-wrap -->

<!-- ══════════════════════ MODAL NUEVA ACTIVIDAD ══════════════════════ -->
<div class="act-modal-overlay" id="actModalOverlay" onclick="cerrarModalActividad(event)">
    <div class="act-modal" onclick="event.stopPropagation()">
        <div class="act-modal-header">
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:10px;">
                <i class="ti ti-writing" style="font-size:1.5rem;color:#fff;"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:.72rem;color:rgba(255,255,255,.7);font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Registro</div>
                <div style="font-size:1.1rem;font-weight:800;color:#fff;">Nueva Actividad</div>
            </div>
            <button onclick="cerrarModalActividad()" style="background:rgba(255,255,255,.15);border:none;border-radius:10px;width:36px;height:36px;cursor:pointer;color:#fff;font-size:1.1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="act-modal-body">
            <div class="act-form-group">
                <label class="act-form-label" for="actFecha">
                    <i class="ti ti-calendar" style="margin-right:4px;"></i>Fecha
                </label>
                <input type="date" id="actFecha" class="act-form-input"
                       max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="act-form-group">
                <label class="act-form-label" for="actTitulo">
                    <i class="ti ti-tag" style="margin-right:4px;"></i>Título / Resumen breve
                </label>
                <input type="text" id="actTitulo" class="act-form-input"
                       placeholder="Ej: Revisión de documentación del módulo X"
                       maxlength="150">
                <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;text-align:right;">
                    <span id="actTituloLen">0</span>/150
                </div>
            </div>
            <div class="act-form-group">
                <label class="act-form-label" for="actDesc">
                    <i class="ti ti-align-left" style="margin-right:4px;"></i>Descripción detallada
                </label>
                <textarea id="actDesc" class="act-form-textarea"
                          placeholder="Describe las actividades realizadas, avances, dificultades encontradas..."></textarea>
            </div>
        </div>
        <!-- Aviso: sin asistencia en la fecha seleccionada -->
        <div id="actAvisoSinAsistencia"
             style="display:none;align-items:center;gap:10px;background:#fef3c7;border:1px solid #fde68a;
                    border-left:4px solid #d97706;border-radius:10px;padding:10px 14px;margin:0 28px 16px;">
            <i class="ti ti-calendar-off" style="color:#d97706;font-size:1.1rem;flex-shrink:0;"></i>
            <p style="margin:0;font-size:.8rem;color:#92400e;line-height:1.5;">
                <strong>No asististe ese día.</strong> Solo puedes registrar actividades en fechas con asistencia marcada.
            </p>
        </div>
        <div class="act-modal-footer">
            <button class="act-btn-cancel" onclick="cerrarModalActividad()">Cancelar</button>
            <button class="act-btn-save" id="actBtnGuardar" onclick="guardarActividad()">
                <i class="ti ti-device-floppy" style="margin-right:6px;"></i>Guardar
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════ MODAL EDITAR ACTIVIDAD ══════════════════════ -->
<div class="act-modal-overlay" id="editActModalOverlay" onclick="cerrarEditarActividad(event)">
    <div class="act-modal" onclick="event.stopPropagation()">
        <div class="act-modal-header" style="background:linear-gradient(135deg,#1e3a8a,#2563eb);">
            <div style="background:rgba(255,255,255,.15);border-radius:12px;padding:10px;">
                <i class="ti ti-pencil" style="font-size:1.5rem;color:#fff;"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:.72rem;color:rgba(255,255,255,.7);font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Edición</div>
                <div style="font-size:1.1rem;font-weight:800;color:#fff;">Editar Actividad</div>
            </div>
            <button onclick="cerrarEditarActividad()" style="background:rgba(255,255,255,.15);border:none;border-radius:10px;width:36px;height:36px;cursor:pointer;color:#fff;font-size:1.1rem;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="act-modal-body">
            <input type="hidden" id="editActId">
            <div class="act-form-group">
                <label class="act-form-label" for="editActFecha">
                    <i class="ti ti-calendar" style="margin-right:4px;"></i>Fecha
                </label>
                <input type="date" id="editActFecha" class="act-form-input"
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="act-form-group">
                <label class="act-form-label" for="editActTitulo">
                    <i class="ti ti-tag" style="margin-right:4px;"></i>Título / Resumen breve
                </label>
                <input type="text" id="editActTitulo" class="act-form-input"
                       placeholder="Ej: Revisión de documentación del módulo X"
                       maxlength="150">
                <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;text-align:right;">
                    <span id="editActTituloLen">0</span>/150
                </div>
            </div>
            <div class="act-form-group">
                <label class="act-form-label" for="editActDesc">
                    <i class="ti ti-align-left" style="margin-right:4px;"></i>Descripción detallada
                </label>
                <textarea id="editActDesc" class="act-form-textarea"
                          placeholder="Describe las actividades realizadas, avances, dificultades encontradas..."></textarea>
            </div>
        </div>
        <!-- Aviso: sin asistencia en fecha editada -->
        <div id="editActAvisoSinAsistencia"
             style="display:none;align-items:center;gap:10px;background:#fef3c7;border:1px solid #fde68a;
                    border-left:4px solid #d97706;border-radius:10px;padding:10px 14px;margin:0 28px 16px;">
            <i class="ti ti-calendar-off" style="color:#d97706;font-size:1.1rem;flex-shrink:0;"></i>
            <p style="margin:0;font-size:.8rem;color:#92400e;line-height:1.5;">
                <strong>No asististe ese día.</strong> Solo puedes registrar actividades en fechas con asistencia marcada.
            </p>
        </div>
        <div class="act-modal-footer">
            <button class="act-btn-cancel" onclick="cerrarEditarActividad()">Cancelar</button>
            <button class="act-btn-save" id="editActBtnGuardar"
                    style="background:linear-gradient(135deg,#1e3a8a,#2563eb);box-shadow:0 4px 12px rgba(37,99,235,.3);"
                    onclick="guardarEdicionActividad()">
                <i class="ti ti-device-floppy" style="margin-right:6px;"></i>Guardar cambios
            </button>
        </div>
    </div>
</div>

<script>
// ── Verificación de asistencia por fecha ────────────────────────────────────
function verificarAsistenciaFecha(fecha, btnId, avisoId) {
    const btn   = document.getElementById(btnId);
    const aviso = document.getElementById(avisoId);
    if (!fecha) return;

    fetch('<?= URLROOT ?>/pasante/verificarAsistencia?fecha=' + encodeURIComponent(fecha))
        .then(r => r.json())
        .then(data => {
            if (data.asiste) {
                if (aviso) aviso.style.display = 'none';
                if (btn)   btn.disabled = false;
            } else {
                if (aviso) aviso.style.display = 'flex';
                if (btn)   btn.disabled = true;
            }
        })
        .catch(() => { /* sin conexión: no bloquear */ });
}

function abrirModalActividad() {
    document.getElementById('actModalOverlay').classList.add('active');
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('actFecha').value  = hoy;
    document.getElementById('actTitulo').value = '';
    document.getElementById('actDesc').value   = '';
    document.getElementById('actTituloLen').textContent = '0';
    document.getElementById('actBtnGuardar').disabled = false;
    verificarAsistenciaFecha(hoy, 'actBtnGuardar', 'actAvisoSinAsistencia');
    setTimeout(() => document.getElementById('actTitulo').focus(), 100);
}

function cerrarModalActividad(e) {
    if (e && e.target !== document.getElementById('actModalOverlay')) return;
    document.getElementById('actModalOverlay').classList.remove('active');
}

document.getElementById('actFecha').addEventListener('change', function() {
    verificarAsistenciaFecha(this.value, 'actBtnGuardar', 'actAvisoSinAsistencia');
});

document.getElementById('actTitulo').addEventListener('input', function() {
    document.getElementById('actTituloLen').textContent = this.value.length;
});

function guardarActividad() {
    const fecha       = document.getElementById('actFecha').value.trim();
    const titulo      = document.getElementById('actTitulo').value.trim();
    const descripcion = document.getElementById('actDesc').value.trim();

    if (!fecha || !titulo || !descripcion) {
        Swal.fire({ icon:'warning', title:'Campos incompletos', text:'Completa todos los campos antes de guardar.', confirmButtonColor:'#059669' });
        return;
    }

    const btn = document.getElementById('actBtnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2" style="margin-right:6px;animation:spin 1s linear infinite;"></i>Guardando...';

    fetch('<?= URLROOT ?>/pasante/guardarActividad', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fecha, titulo, descripcion })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('actModalOverlay').classList.remove('active');
            Swal.fire({
                icon: 'success', title: '¡Registrada!',
                text: data.message,
                confirmButtonColor: '#059669',
                timer: 1800, showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire({ icon:'error', title:'Error', text: data.message, confirmButtonColor:'#059669' });
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy" style="margin-right:6px;"></i>Guardar';
        }
    })
    .catch(() => {
        Swal.fire({ icon:'error', title:'Error de red', text:'No se pudo conectar. Intenta de nuevo.', confirmButtonColor:'#059669' });
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-device-floppy" style="margin-right:6px;"></i>Guardar';
    });
}

function filtrarActividades() {
    const search = document.getElementById('actSearch')?.value.toLowerCase().trim() ?? '';
    const mes    = document.getElementById('actMesFilter')?.value ?? '';
    let visibles = 0;

    document.querySelectorAll('.act-month-group').forEach(group => {
        const grupoMes = group.dataset.grupo ?? '';
        let grupoVis   = false;

        group.querySelectorAll('.act-card').forEach(card => {
            const titulo  = card.dataset.titulo ?? '';
            const desc    = card.dataset.desc   ?? '';
            const cardMes = card.dataset.mes    ?? '';

            const okSearch = !search || titulo.includes(search) || desc.includes(search);
            const okMes    = !mes    || cardMes === mes;

            if (okSearch && okMes) {
                card.style.display = '';
                grupoVis = true;
                visibles++;
            } else {
                card.style.display = 'none';
            }
        });

        group.style.display = grupoVis ? '' : 'none';
    });

    const noRes  = document.getElementById('actNoResults');
    const cnt    = document.getElementById('actContador');
    const hayAct = document.querySelectorAll('.act-month-group').length > 0;

    if (noRes) noRes.style.display = (hayAct && visibles === 0) ? 'block' : 'none';
    if (cnt)   cnt.textContent = visibles + ' actividad' + (visibles !== 1 ? 'es' : '');
}

function resetFiltros() {
    const s = document.getElementById('actSearch');
    const m = document.getElementById('actMesFilter');
    if (s) s.value = '';
    if (m) m.value = '';
    filtrarActividades();
}

function eliminarActividad(id, btn) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar actividad?',
        text: 'Esta acción no se puede deshacer.',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b'
    }).then(result => {
        if (!result.isConfirmed) return;

        btn.disabled = true;
        fetch('<?= URLROOT ?>/pasante/eliminarActividad', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const card = document.getElementById('act-card-' + id);
                if (card) {
                    card.style.transition = 'opacity .3s,transform .3s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        card.remove();
                        // Recalcular total visible
                        const remaining = document.querySelectorAll('[id^="act-card-"]').length;
                        if (remaining === 0) location.reload();
                    }, 300);
                }
            } else {
                Swal.fire({ icon:'error', title:'Error', text: data.message, confirmButtonColor:'#ef4444' });
                btn.disabled = false;
            }
        })
        .catch(() => {
            Swal.fire({ icon:'error', title:'Error de red', text:'No se pudo conectar.', confirmButtonColor:'#ef4444' });
            btn.disabled = false;
        });
    });
}

function abrirEditarActividad(id, titulo, descripcion, fecha) {
    document.getElementById('editActId').value          = id;
    document.getElementById('editActFecha').value       = fecha   ?? '';
    document.getElementById('editActTitulo').value      = titulo  ?? '';
    document.getElementById('editActDesc').value        = descripcion ?? '';
    document.getElementById('editActTituloLen').textContent = (titulo ?? '').length;
    document.getElementById('editActBtnGuardar').disabled   = false;
    document.getElementById('editActBtnGuardar').innerHTML  =
        '<i class="ti ti-device-floppy" style="margin-right:6px;"></i>Guardar cambios';

    document.getElementById('editActModalOverlay').classList.add('active');
    verificarAsistenciaFecha(fecha, 'editActBtnGuardar', 'editActAvisoSinAsistencia');
    setTimeout(() => document.getElementById('editActTitulo').focus(), 100);
}

function cerrarEditarActividad(e) {
    if (e && e.target !== document.getElementById('editActModalOverlay')) return;
    document.getElementById('editActModalOverlay').classList.remove('active');
}

document.getElementById('editActFecha').addEventListener('change', function () {
    verificarAsistenciaFecha(this.value, 'editActBtnGuardar', 'editActAvisoSinAsistencia');
});

document.getElementById('editActTitulo').addEventListener('input', function () {
    document.getElementById('editActTituloLen').textContent = this.value.length;
});

function guardarEdicionActividad() {
    const id          = document.getElementById('editActId').value;
    const fecha       = document.getElementById('editActFecha').value.trim();
    const titulo      = document.getElementById('editActTitulo').value.trim();
    const descripcion = document.getElementById('editActDesc').value.trim();

    if (!fecha || !titulo || !descripcion) {
        Swal.fire({ icon:'warning', title:'Campos incompletos', text:'Completa todos los campos.', confirmButtonColor:'#2563eb' });
        return;
    }

    const btn = document.getElementById('editActBtnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2" style="margin-right:6px;animation:spin 1s linear infinite;"></i>Guardando...';

    fetch('<?= URLROOT ?>/pasante/editarActividad', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: parseInt(id), fecha, titulo, descripcion })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editActModalOverlay').classList.remove('active');
            Swal.fire({
                icon: 'success', title: '¡Actualizada!',
                text: data.message,
                confirmButtonColor: '#2563eb',
                timer: 1600, showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire({ icon:'error', title:'Error', text: data.message, confirmButtonColor:'#2563eb' });
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-device-floppy" style="margin-right:6px;"></i>Guardar cambios';
        }
    })
    .catch(() => {
        Swal.fire({ icon:'error', title:'Error de red', text:'No se pudo conectar.', confirmButtonColor:'#2563eb' });
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-device-floppy" style="margin-right:6px;"></i>Guardar cambios';
    });
}
</script>
