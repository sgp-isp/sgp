<?php
// Dashboard Premium — Pasante SGP v3 Bento
$user_name   = $data['user_name']  ?? 'Pasante';
$pasante        = $data['pasante']        ?? null;
$actividades    = $data['actividades']    ?? [];
$asistenciasMes = $data['asistenciasMes'] ?? [];
$proRata        = $data['proRata']        ?? null;

$estadoPasantia  = $pasante->estado_pasantia ?? 'Pendiente';
$sinAsignar      = in_array($estadoPasantia, ['Sin Asignar', 'Pendiente', '']);
$esFinalizado    = ($estadoPasantia === 'Finalizado');

$diasValidos = (int)($proRata->dias_presentes  ?? $pasante->horas_acumuladas / 8 ?? 0);
$horasAcum   = (int)($proRata->horas_mostradas ?? $pasante->horas_acumuladas   ?? 0);
$horasMeta   = (int)($proRata->horas_meta      ?? $pasante->horas_meta         ?? 1440);
$pct         = $horasMeta > 0 ? min(100, round($horasAcum / $horasMeta * 100)) : 0;
$pctCal      = (float)($proRata->porcentaje_calendario ?? 0);
$completoHoras = $esFinalizado && $horasAcum >= $horasMeta;

$estesMes = count($asistenciasMes);
$presentes  = 0; $ausentes = 0; $justificados = 0;
foreach ($actividades as $a) {
    $est = $a->estado ?? '';
    if ($est === 'Presente') $presentes++;
    elseif ($est === 'Ausente') $ausentes++;
    elseif ($est === 'Justificado') $justificados++;
}

// Mapa fecha → estado para el calendario mensual
$actMap = [];
foreach ($asistenciasMes as $a) {
    if (!empty($a->fecha)) $actMap[$a->fecha] = $a->estado ?? 'Presente';
}

// Mapa de feriados (pasado desde el controlador)
$feriadosMap = $data['feriadosMap'] ?? [];
$hoy         = new DateTime();
$hoyStr      = $hoy->format('Y-m-d');

// Días faltantes = meta - acumulados
$diasMeta    = (int)ceil($horasMeta / 8);
$diasFaltan  = max(0, $diasMeta - $diasValidos);

$primerNombre = explode(' ', trim($user_name))[0];
$iniciales    = strtoupper(mb_substr(trim($user_name), 0, 1));

// ── Gamificación: Racha Consecutiva (Estilo TikTok / Duolingo 🔥) ──
$rachaDias = (int)($data['rachaActual'] ?? 0);
if ($rachaDias <= 0) {
    $flamaColor   = '#94a3b8';
    $flamaBg      = 'linear-gradient(135deg, #64748b, #94a3b8)';
    $flamaSombra  = 'rgba(100, 116, 139, 0.25)';
    $rachaNivel   = 'Sin Racha Activa';
    $rachaFrase   = 'Marca tu asistencia hoy para encender tu flama y comenzar tu racha.';
} elseif ($rachaDias >= 1 && $rachaDias <= 2) {
    $flamaColor   = '#f97316';
    $flamaBg      = 'linear-gradient(135deg, #ea580c, #f97316)';
    $flamaSombra  = 'rgba(234, 88, 12, 0.4)';
    $rachaNivel   = 'Flama Inicial 🔥';
    $rachaFrase   = '¡Gran comienzo! La constancia es el secreto del éxito profesional.';
} elseif ($rachaDias >= 3 && $rachaDias <= 6) {
    $flamaColor   = '#ef4444';
    $flamaBg      = 'linear-gradient(135deg, #dc2626, #ef4444)';
    $flamaSombra  = 'rgba(239, 68, 68, 0.45)';
    $rachaNivel   = 'Flama Roja Activa 🔥🔥';
    $rachaFrase   = '¡Estás imparable! Cada día consecutivo te acerca más a tu titulación.';
} elseif ($rachaDias >= 7 && $rachaDias <= 14) {
    $flamaColor   = '#8b5cf6';
    $flamaBg      = 'linear-gradient(135deg, #7c3aed, #a855f7)';
    $flamaSombra  = 'rgba(139, 92, 246, 0.5)';
    $rachaNivel   = 'Flama Púrpura Élite ⚡';
    $rachaFrase   = '¡Racha legendaria! Una semana completa de puntualidad y compromiso.';
} else {
    $flamaColor   = '#06b6d4';
    $flamaBg      = 'linear-gradient(135deg, #0891b2, #06b6d4, #3b82f6)';
    $flamaSombra  = 'rgba(6, 182, 212, 0.55)';
    $rachaNivel   = 'Flama Azul Mítica 💎';
    $rachaFrase   = '¡Nivel Superior! Tu disciplina impecable es un ejemplo en la institución.';
}
?>
<style>
/* ─── RESET / BASE ──────────────────────────────────── */
.pb-wrap { display:flex; flex-direction:column; gap:20px; }

/* ─── BENTO GRID ────────────────────────────────────── */
.pb-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}
.pb-grid-kpi {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@keyframes fadeUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}
.pb-wrap > * { animation: fadeUp .45s ease both; }
.pb-wrap > *:nth-child(1){ animation-delay:.05s; }
.pb-wrap > *:nth-child(2){ animation-delay:.12s; }
.pb-wrap > *:nth-child(3){ animation-delay:.18s; }
.pb-wrap > *:nth-child(4){ animation-delay:.24s; }
.pb-wrap > *:nth-child(5){ animation-delay:.30s; }
.pb-wrap > *:nth-child(6){ animation-delay:.36s; }

/* ─── HERO BANNER ───────────────────────────────────── */
.pb-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #2563eb 100%);
    border-radius: 22px;
    padding: 30px 36px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}
.pb-hero::before {
    content:'';
    position:absolute; top:-60px; right:-60px;
    width:260px; height:260px;
    background:rgba(255,255,255,.05);
    border-radius:50%;
}
.pb-hero::after {
    content:'';
    position:absolute; bottom:-40px; left:30%;
    width:160px; height:160px;
    background:rgba(255,255,255,.03);
    border-radius:50%;
}
.pb-avatar {
    width: 56px; height: 56px;
    background: rgba(255,255,255,.2);
    border: 2px solid rgba(255,255,255,.3);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 900; color: #fff;
    flex-shrink: 0;
}
.pb-status-pill {
    display: inline-flex;
    align-items: center; gap: 6px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 50px;
    padding: 6px 16px;
    color: rgba(255,255,255,.9);
    font-size: .8rem; font-weight: 600;
    backdrop-filter: blur(8px);
}
.pb-clock-box {
    display: flex; align-items: center; gap: 18px;
    background: rgba(0,0,0,.18);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 50px;
    padding: 10px 22px;
    color: #fff;
    z-index: 1;
}

/* ─── KPI CARD ──────────────────────────────────────── */
.pb-kpi {
    background: #fff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 2px 14px rgba(0,0,0,.07);
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: relative;
    overflow: hidden;
    transition: transform .25s, box-shadow .25s;
    cursor: default;
}
.pb-kpi:hover { transform: translateY(-4px); }
.pb-kpi-top  { display: flex; justify-content: space-between; align-items: flex-start; }
.pb-kpi-label{ font-size: .73rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; }
.pb-kpi-val  { font-size: 2.6rem; font-weight: 900; line-height: 1; margin-top: 2px; }
.pb-kpi-sub  { font-size: .75rem; color: #94a3b8; font-weight: 500; }
.pb-kpi-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.pb-kpi::after {
    content:'';
    position:absolute; bottom:-20px; right:-20px;
    width:80px; height:80px;
    border-radius:50%;
    opacity:.06;
}
.pb-kpi.c-teal   { border-top: 3px solid #0d9488; } .pb-kpi.c-teal::after  { background:#0d9488; }
.pb-kpi.c-blue   { border-top: 3px solid var(--color-accent-brand, #2563eb); } .pb-kpi.c-blue::after  { background:var(--color-accent-brand, #2563eb); }
.pb-kpi.c-amber  { border-top: 3px solid #f59e0b; } .pb-kpi.c-amber::after { background:#f59e0b; }
.pb-kpi.c-indigo { border-top: 3px solid #6366f1; } .pb-kpi.c-indigo::after{ background:#6366f1; }
.pb-kpi:hover.c-teal   { box-shadow: 0 12px 28px rgba(13,148,136,.2); }
.pb-kpi:hover.c-blue   { box-shadow: 0 12px 28px rgba(37,99,235,.2); }
.pb-kpi:hover.c-amber  { box-shadow: 0 12px 28px rgba(245,158,11,.2); }
.pb-kpi:hover.c-indigo { box-shadow: 0 12px 28px rgba(99,102,241,.2); }

/* ─── CARD BASE ─────────────────────────────────────── */
.pb-card {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 2px 14px rgba(0,0,0,.07);
}
.pb-card-title {
    font-size: 1rem; font-weight: 700; color: #0f172a;
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 18px;
}
.pb-card-title i { color: var(--color-accent-brand, #2563eb); font-size: 1.15rem; }

/* ─── ALMANAQUE MENSUAL (idéntico a /asistencias/almanaque) ── */
.alm-week-header {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 4px;
    margin-bottom: 8px;
}
.alm-header-cell {
    font-size: .7rem; font-weight: 700; color: #94a3b8; text-align: center;
}
.alm-month-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 4px;
}
.alm-cell {
    aspect-ratio: 1;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 700;
    cursor: default;
    transition: transform .1s, box-shadow .1s;
}
.alm-cell:hover:not(.is-empty) { transform: scale(1.15); z-index: 10; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
.alm-cell.is-empty { cursor: default; pointer-events: none; }
.alm-cell[data-e="ghost"]    { background: transparent; }
.alm-cell[data-e="futuro"]   { background: #fff; color: #cbd5e1; border: 1px solid #e2e8f0; }
.alm-cell[data-e="sin_dato"] { background: #e2e8f0; color: #64748b; }
.alm-cell[data-e="P"]        { background: #16a34a; color: #fff; }
.alm-cell[data-e="J"]        { background: #2563eb; color: #fff; }
.alm-cell[data-e="A"]        { background: #dc2626; color: #fff; }
.alm-cell[data-e="feriado"]  { background: #f59e0b; color: #fff; }
.alm-cell[data-e="feriado_lab"] { background: #f59e0b; color: #fff; border: 2px dashed #92400e; }
.alm-cell.today { outline: 2px solid #0f172a; outline-offset: 1px; }
.alm-legend { display: flex; gap: 10px; font-size: .7rem; font-weight: 600; color: #64748b; flex-wrap: wrap; margin-top: 14px; }
.alm-legend-item { display: flex; align-items: center; gap: 5px; }
.alm-legend-dot  { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }

/* ─── PROGRESS RING ─────────────────────────────────── */
.pb-ring-wrap { display: flex; flex-direction: column; align-items: center; gap: 14px; }
.pb-ring { position: relative; width: 140px; height: 140px; flex-shrink: 0; }
.pb-ring svg { transform: rotate(-90deg); }
.pb-ring-text {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    text-align: center;
}
.pb-ring-pct   { font-size: 2rem; font-weight: 900; color: #0f172a; line-height: 1; }
.pb-ring-label { font-size: .65rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }

/* ─── INFO PILLS ────────────────────────────────────── */
.pb-info-pill {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 8px;
    font-size: .82rem;
}
.pb-info-pill i { color: var(--color-accent-brand, #2563eb); font-size: 1rem; flex-shrink: 0; }
.pb-info-pill strong { color: #0f172a; font-weight: 700; }
.pb-info-pill span   { color: #64748b; }

/* ─── PROGRESS BAR ──────────────────────────────────── */
.pb-bar { height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin: .3rem 0; }
.pb-bar-fill { height: 100%; border-radius: 999px; transition: width .8s cubic-bezier(.4,0,.2,1); }

/* ─── TIMELINE ──────────────────────────────────────── */
.pb-timeline { display: flex; flex-direction: column; }
.pb-tl-item {
    display: flex; gap: 14px; align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
}
.pb-tl-item:last-child { border-bottom: none; }
.pb-tl-dot {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.pb-tl-date { font-size: .82rem; font-weight: 700; color: #0f172a; }
.pb-tl-meta { font-size: .73rem; color: #94a3b8; margin-top: 1px; }
.pb-tl-badge{
    margin-left: auto; flex-shrink: 0;
    padding: 3px 10px; border-radius: 6px;
    font-size: .72rem; font-weight: 700;
}

/* ─── EMPTY STATE ───────────────────────────────────── */
.pb-empty {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; text-align: center;
    padding: 60px 30px;
    background: #fff;
    border-radius: 22px;
    border: 2px dashed #e2e8f0;
    gap: 16px;
}
.pb-empty-icon {
    width: 90px; height: 90px;
    background: linear-gradient(135deg, #dbeafe, #eff6ff);
    border-radius: 50%; display: flex; align-items: center;
    justify-content: center; font-size: 2.8rem; color: #3b82f6;
}
@keyframes spin { to { transform: rotate(360deg); } }
.pb-spin { animation: spin 2s linear infinite; }

/* ─── GRID 3 COLUMNAS ────────────────────────────────── */
.pb-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* ─── HOY CARD ───────────────────────────────────────── */
.hoy-estado-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 50px;
    font-size: .8rem; font-weight: 800;
}
.hoy-actividad {
    padding: 10px 12px;
    background: #f8fafc;
    border-left: 3px solid #2563eb;
    border-radius: 0 8px 8px 0;
    margin-bottom: 8px;
}
.hoy-actividad:last-child { margin-bottom: 0; }
.hoy-actividad-titulo { font-size: .82rem; font-weight: 700; color: #0f172a; }
.hoy-actividad-desc   { font-size: .75rem; color: #64748b; margin-top: 2px; line-height: 1.4; }

/* ─── RESPONSIVE ─────────────────────────────────────── */
@media (max-width: 1100px) {
    .pb-grid   { grid-template-columns: 1fr !important; }
    .pb-grid-3 { grid-template-columns: 1fr !important; width: 100% !important; gap: 20px !important; }
    .pb-grid-kpi { grid-template-columns: repeat(2, 1fr) !important; }
}
@media (max-width: 768px) {
    .pb-hero {
        padding: 20px 18px;
        border-radius: 18px;
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }
    .pb-hero-left {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .pb-hero-left h1 {
        font-size: 1.35rem !important;
        line-height: 1.25;
    }
    .pb-avatar {
        width: 48px;
        height: 48px;
        font-size: 1.3rem;
        border-radius: 14px;
    }
    .pb-clock-box {
        display: flex !important;
        width: 100%;
        box-sizing: border-box;
        justify-content: space-between;
        align-items: center;
        padding: 8px 16px;
        border-radius: 14px;
        font-size: 0.8rem;
    }
    .pb-clock-box #pb-date {
        font-size: 0.76rem;
    }
    .pb-clock-box #pb-time {
        font-size: 0.95rem !important;
    }
    .pb-card {
        padding: 20px 18px;
        border-radius: 18px;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .pb-grid-3 {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        gap: 20px !important;
    }
    .alm-month-grid {
        gap: 6px !important;
    }
    .alm-week-header {
        gap: 6px !important;
        margin-bottom: 10px !important;
    }
    .alm-cell {
        min-height: 42px !important;
        font-size: 0.85rem !important;
        border-radius: 8px !important;
    }
    .alm-header-cell {
        font-size: 0.78rem !important;
        font-weight: 700 !important;
    }
}
@media (max-width: 540px) {
    .pb-grid-kpi {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    .pb-kpi {
        padding: 14px 12px;
        border-radius: 14px;
        gap: 8px;
    }
    .pb-kpi-val {
        font-size: 1.8rem;
    }
    .pb-kpi-label {
        font-size: 0.65rem;
    }
    .pb-kpi-sub {
        font-size: 0.68rem;
    }
    .pb-kpi-icon {
        width: 34px;
        height: 34px;
        font-size: 1rem;
        border-radius: 10px;
    }
    .alm-month-grid { gap: 6px !important; }
    .alm-week-header { gap: 6px !important; }
    .alm-cell { font-size: 0.82rem !important; min-height: 38px !important; border-radius: 7px !important; }
    .alm-legend { gap: 8px 12px !important; font-size: 0.72rem !important; }
    .pb-tl-item { gap: 10px; }
    .pb-tl-dot { width: 32px; height: 32px; font-size: 0.85rem; }
    .pb-tl-date { font-size: 0.78rem; }
    .pb-tl-meta { font-size: 0.68rem; }
    .pb-tl-badge { font-size: 0.66rem; padding: 2px 7px; }
}

/* ─── DARK MODE SUPPORT (PASANTE DASHBOARD) ─────────── */
[data-theme="dark"] .pb-hero {
    background: linear-gradient(135deg, #090d16 0%, #0f1c3f 50%, #1e3a8a 100%) !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6) !important;
}
[data-theme="dark"] .pb-card,
[data-theme="dark"] .pb-kpi {
    background: #111a2e !important;
    border: 1px solid #1e293b !important;
    color: #cbd5e1 !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-theme="dark"] .pb-card-title {
    color: #f8fafc !important;
}
[data-theme="dark"] .pb-card-title i {
    color: #38bdf8 !important;
}
[data-theme="dark"] .pb-info-pill {
    background: #0d1526 !important;
    border: 1px solid #1e293b !important;
}
[data-theme="dark"] .pb-info-pill strong {
    color: #f8fafc !important;
}
[data-theme="dark"] .pb-info-pill span {
    color: #94a3b8 !important;
}
[data-theme="dark"] .pb-info-pill i {
    color: #38bdf8 !important;
}
[data-theme="dark"] .pb-bar {
    background: #1e293b !important;
}
[data-theme="dark"] .pb-ring circle:first-child {
    stroke: #1e293b !important;
}
[data-theme="dark"] .pb-ring-pct {
    color: #f8fafc !important;
}
[data-theme="dark"] .alm-cell[data-e="futuro"] {
    background: #0d1526 !important;
    border-color: #1e293b !important;
    color: #475569 !important;
}
[data-theme="dark"] .alm-cell[data-e="sin_dato"] {
    background: #1e293b !important;
    color: #94a3b8 !important;
}
[data-theme="dark"] .alm-cell.today {
    outline: 2px solid #38bdf8 !important;
}
[data-theme="dark"] .hoy-actividad {
    background: #0d1526 !important;
    border-left-color: #38bdf8 !important;
}
[data-theme="dark"] .hoy-actividad-titulo {
    color: #f8fafc !important;
}
[data-theme="dark"] .hoy-actividad-desc {
    color: #94a3b8 !important;
}
[data-theme="dark"] .pb-tl-item {
    border-bottom-color: #1e293b !important;
}
[data-theme="dark"] .pb-tl-date {
    color: #f8fafc !important;
}
[data-theme="dark"] .pb-empty {
    background: #111a2e !important;
    border-color: #1e293b !important;
}
[data-theme="dark"] .pb-empty h2 {
    color: #f8fafc !important;
}
[data-theme="dark"] .pb-empty p {
    color: #94a3b8 !important;
}
[data-theme="dark"] .pb-ring-wrap strong {
    color: #f8fafc !important;
}
[data-theme="dark"] .pb-btn-actividad {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #38bdf8 !important;
}
[data-theme="dark"] .pb-btn-actividad:hover {
    background: #25334d !important;
}
[data-theme="dark"] .pb-kpi-sub,
[data-theme="dark"] .pb-kpi-label {
    color: #94a3b8 !important;
}
[data-theme="dark"] .alm-header-cell {
    color: #94a3b8 !important;
}
[data-theme="dark"] .alm-cell[data-e="ghost"] {
    background: transparent !important;
}
[data-theme="dark"] .alm-legend,
[data-theme="dark"] .alm-legend-item {
    color: #94a3b8 !important;
}
[data-theme="dark"] .pb-link-asistencia {
    color: #38bdf8 !important;
}
[data-theme="dark"] .pb-tl-meta {
    color: #94a3b8 !important;
}
[data-theme="dark"] .pb-kpi-icon {
    background: rgba(255, 255, 255, 0.08) !important;
}
[data-theme="dark"] .pb-clock-box {
    background: rgba(15, 23, 42, 0.6) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
}
</style>

<div class="pb-wrap">

<!-- ══════════ HERO BANNER ══════════ -->
<div class="pb-hero">
    <div class="pb-hero-left" style="z-index:1;">
        <div class="pb-avatar"><?= $iniciales ?></div>
        <div>
            <div style="display:inline-flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                <div class="pb-status-pill">
                    <i class="ti ti-layout-dashboard" style="color:#93c5fd;"></i> Mi Panel · Pasante
                    <?php if (!$sinAsignar): ?>
                    <span style="opacity:.35;margin:0 2px;">|</span>
                    <span style="color:#34d399;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                        <i class="ti ti-circle-check"></i> <?= htmlspecialchars($estadoPasantia) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <h1 style="color:#fff;font-size:1.75rem;font-weight:800;margin:0;letter-spacing:-.3px;line-height:1.2;">
                ¡Bienvenido, <?= htmlspecialchars($primerNombre) ?>!
            </h1>
        </div>
    </div>
    <div class="pb-clock-box">
        <div style="display:flex;align-items:center;gap:8px;font-size:.85rem;">
            <i class="ti ti-calendar" style="opacity:.8;"></i>
            <span id="pb-date"></span>
        </div>
        <div style="width:1px;height:22px;background:rgba(255,255,255,.2);"></div>
        <div style="display:flex;align-items:center;gap:8px;">
            <i class="ti ti-clock" style="opacity:.8;"></i>
            <span id="pb-time" style="font-size:1.1rem;font-weight:700;font-variant-numeric:tabular-nums;"></span>
        </div>
    </div>
</div>

<?php if ($esFinalizado): ?>
<!-- ══════════ BANNER FINALIZACIÓN ══════════ -->
<div style="border-radius:22px;padding:36px 40px;margin-bottom:24px;position:relative;overflow:hidden;text-align:center;
            background:<?= $completoHoras ? 'linear-gradient(135deg,#052e16,#14532d,#16a34a)' : 'linear-gradient(135deg,#0c1a3a,#1e3a8a,#2563eb)' ?>;">
    <!-- Decoración -->
    <div style="position:absolute;top:-60px;left:-60px;width:200px;height:200px;background:rgba(255,255,255,.05);border-radius:50%;"></div>
    <div style="position:absolute;bottom:-40px;right:-40px;width:160px;height:160px;background:rgba(255,255,255,.05);border-radius:50%;"></div>

    <div style="position:relative;z-index:1;">
        <?php if ($completoHoras): ?>
        <!-- Completó horas — FELICITACIONES -->
        <div style="font-size:3.5rem;margin-bottom:12px;">🎓</div>
        <h2 style="font-size:1.8rem;font-weight:900;color:white;margin:0 0 10px;letter-spacing:-.3px;">
            ¡Felicidades, pasantía completada!
        </h2>
        <p style="color:rgba(255,255,255,.8);max-width:500px;margin:0 auto 20px;line-height:1.7;font-size:.95rem;">
            Has culminado exitosamente tu período de pasantía acumulando
            <strong style="color:#86efac;"><?= $horasAcum ?>h</strong> de
            <strong style="color:#86efac;"><?= $horasMeta ?>h</strong> requeridas.
            ¡Un logro que merece reconocimiento!
        </p>
        <div style="display:inline-flex;align-items:center;gap:10px;background:rgba(255,255,255,.15);
                    border:1px solid rgba(255,255,255,.3);backdrop-filter:blur(8px);
                    border-radius:50px;padding:10px 24px;color:white;font-weight:700;font-size:.88rem;margin-bottom:18px;">
            <i class="ti ti-trophy" style="color:#fde047;font-size:1.1rem;"></i>
            <?= round($pct) ?>% completado · <?= $diasValidos ?> días asistidos
        </div>
        <?php else: ?>
        <!-- Finalizó sin completar horas -->
        <div style="font-size:3rem;margin-bottom:12px;">📋</div>
        <h2 style="font-size:1.6rem;font-weight:900;color:white;margin:0 0 10px;">
            Período académico finalizado
        </h2>
        <p style="color:rgba(255,255,255,.75);max-width:480px;margin:0 auto 18px;line-height:1.7;font-size:.9rem;">
            Tu período de pasantía ha concluido. Acumulaste
            <strong style="color:#93c5fd;"><?= $horasAcum ?>h</strong> de
            <strong style="color:#93c5fd;"><?= $horasMeta ?>h</strong> requeridas (<?= round($pct) ?>%).
        </p>
        <?php endif; ?>

        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
            <a href="<?= URLROOT ?>/pasante/asistencia"
               style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:10px;
                      background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.35);
                      color:white;font-weight:700;font-size:.85rem;text-decoration:none;
                      transition:background .2s;"
               onmouseover="this.style.background='rgba(255,255,255,.3)'"
               onmouseout="this.style.background='rgba(255,255,255,.2)'">
                <i class="ti ti-calendar-stats"></i> Ver mi historial
            </a>
            <a href="<?= URLROOT ?>/pasante/constancia"
               style="display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:10px;
                      background:white;color:<?= $completoHoras ? '#15803d' : '#1e3a8a' ?>;
                      font-weight:800;font-size:.85rem;text-decoration:none;
                      box-shadow:0 4px 12px rgba(0,0,0,.2);transition:opacity .2s;"
               onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                <i class="ti ti-file-type-pdf"></i> Descargar constancia
            </a>
        </div>
    </div>
</div>

<?php elseif ($sinAsignar): ?>
<!-- ══════════ EMPTY STATE ══════════ -->
<div class="pb-empty">
    <div class="pb-empty-icon">
        <i class="ti ti-user-scan pb-spin" style="animation-duration:3s;"></i>
    </div>
    <h2 style="font-size:1.6rem;font-weight:800;color:#0f172a;margin:0;">Perfil en Revisión</h2>
    <p style="color:#64748b;max-width:480px;line-height:1.7;margin:0;">
        Tu cuenta está activa. El equipo de <strong>Telemática</strong> te asignará pronto
        un departamento y tutor para que puedas comenzar a registrar asistencias.
    </p>
    <div style="display:flex;align-items:center;gap:8px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:50px;padding:10px 22px;color:#15803d;font-weight:600;font-size:.85rem;">
        <i class="ti ti-loader pb-spin"></i> Esperando asignación…
    </div>
</div>

<?php else: ?>

<!-- ══════════ SECCIÓN RACHA DE ASISTENCIA (GAMIFICACIÓN TIKTOK STYLE 🔥) ══════════ -->
<div class="pb-racha-banner" style="
    background: <?= $rachaDias > 0 ? 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%)' : 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)' ?>;
    border: 2px solid <?= $rachaDias > 0 ? $flamaColor . '44' : '#e2e8f0' ?>;
    border-radius: 20px;
    padding: 20px 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    box-shadow: <?= $rachaDias > 0 ? '0 12px 30px ' . $flamaSombra : '0 4px 16px rgba(0,0,0,0.03)' ?>;
    position: relative;
    overflow: hidden;
    flex-wrap: wrap;
">
    <!-- Resplandor ambiental de fondo -->
    <?php if ($rachaDias > 0): ?>
    <div style="
        position: absolute;
        top: -60px;
        right: 15%;
        width: 180px;
        height: 180px;
        background: <?= $flamaColor ?>;
        filter: blur(65px);
        opacity: 0.22;
        pointer-events: none;
        border-radius: 50%;
    "></div>
    <?php endif; ?>

    <!-- Lado Izquierdo: Ícono de Flama Dinámica -->
    <div style="display:flex;align-items:center;gap:18px;z-index:1;">
        <div class="pb-flama-badge" style="
            width: 66px;
            height: 66px;
            border-radius: 18px;
            background: <?= $flamaBg ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px <?= $flamaSombra ?>;
            flex-shrink: 0;
            animation: <?= $rachaDias > 0 ? 'flameTikTokPulse 1.4s infinite alternate cubic-bezier(0.4, 0, 0.2, 1)' : 'none' ?>;
        ">
            <span style="font-size: 2.3rem; line-height: 1; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2));">🔥</span>
        </div>

        <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;flex-wrap:wrap;">
                <span style="
                    font-size: 0.72rem;
                    font-weight: 900;
                    letter-spacing: 1.2px;
                    text-transform: uppercase;
                    padding: 3px 10px;
                    border-radius: 20px;
                    background: <?= $rachaDias > 0 ? $flamaColor . '25' : '#e2e8f0' ?>;
                    color: <?= $rachaDias > 0 ? $flamaColor : '#64748b' ?>;
                    border: 1px solid <?= $rachaDias > 0 ? $flamaColor . '55' : '#cbd5e1' ?>;
                ">
                    <?= $rachaNivel ?>
                </span>
                <?php if ($rachaDias > 0): ?>
                <span style="font-size:0.75rem;color:<?= $flamaColor ?>;font-weight:700;display:inline-flex;align-items:center;gap:3px;">
                    <i class="ti ti-flame"></i> Racha Encendida
                </span>
                <?php endif; ?>
            </div>

            <div style="display:flex;align-items:baseline;gap:8px;">
                <span style="
                    font-size: 2.2rem;
                    font-weight: 900;
                    line-height: 1.1;
                    color: <?= $rachaDias > 0 ? '#ffffff' : '#0f172a' ?>;
                    letter-spacing: -0.5px;
                ">
                    <?= $rachaDias ?>
                </span>
                <span style="
                    font-size: 1.05rem;
                    font-weight: 800;
                    color: <?= $rachaDias > 0 ? '#94a3b8' : '#64748b' ?>;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                ">
                    <?= $rachaDias === 1 ? 'Día de racha' : 'Días consecutivos' ?>
                </span>
            </div>

            <p style="
                margin: 4px 0 0;
                font-size: 0.84rem;
                color: <?= $rachaDias > 0 ? '#cbd5e1' : '#64748b' ?>;
                font-weight: 500;
                max-width: 520px;
                line-height: 1.4;
            ">
                <?= $rachaFrase ?>
            </p>
        </div>
    </div>

    <!-- Lado Derecho: Puntos de Racha Semanal -->
    <div style="
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 8px;
        z-index: 1;
    ">
        <div style="font-size:0.72rem;font-weight:800;text-transform:uppercase;letter-spacing:0.8px;color:<?= $rachaDias > 0 ? '#94a3b8' : '#64748b' ?>;">
            Asistencia Semanal
        </div>
        <div style="display:flex;gap:7px;align-items:center;">
            <?php
            $diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'];
            $hoyNum = (int)date('N'); // 1 = Lun, 5 = Vie
            foreach ($diasSemana as $idx => $dNombre):
                $diaIdx = $idx + 1;
                $esHoy = ($diaIdx === $hoyNum);
                $asistido = false;
                // Verificar si asistió en ese día de la semana actual
                $fechaDia = date('Y-m-d', strtotime('monday this week +' . $idx . ' days'));
                if (isset($actMap[$fechaDia]) && in_array($actMap[$fechaDia], ['Presente', 'Justificado'])) {
                    $asistido = true;
                }
            ?>
            <div style="
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
            ">
                <div style="
                    width: 32px;
                    height: 32px;
                    border-radius: 10px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 0.9rem;
                    font-weight: 800;
                    background: <?= $asistido ? $flamaBg : ($rachaDias > 0 ? 'rgba(255,255,255,0.06)' : '#ffffff') ?>;
                    color: <?= $asistido ? '#ffffff' : ($rachaDias > 0 ? '#64748b' : '#94a3b8') ?>;
                    border: 1.5px solid <?= $esHoy ? $flamaColor : ($asistido ? 'transparent' : ($rachaDias > 0 ? '#334155' : '#e2e8f0')) ?>;
                    box-shadow: <?= $asistido ? '0 4px 10px ' . $flamaSombra : 'none' ?>;
                    transition: transform 0.2s;
                ">
                    <?php if ($asistido): ?>
                        <i class="ti ti-flame" style="font-size:1.05rem;"></i>
                    <?php else: ?>
                        <span style="font-size:0.75rem;"><?= mb_substr($dNombre, 0, 1) ?></span>
                    <?php endif; ?>
                </div>
                <span style="
                    font-size: 0.65rem;
                    font-weight: 700;
                    color: <?= $esHoy ? ($rachaDias > 0 ? '#f8fafc' : '#0f172a') : ($rachaDias > 0 ? '#64748b' : '#94a3b8') ?>;
                "><?= $dNombre ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
@keyframes flameTikTokPulse {
    0% { transform: scale(1) rotate(-3deg); }
    50% { transform: scale(1.12) rotate(3deg); }
    100% { transform: scale(1.05) rotate(-2deg); }
}
@media (max-width: 768px) {
    .pb-racha-banner {
        padding: 16px;
        flex-direction: column;
        align-items: flex-start;
        gap: 14px;
    }
    .pb-flama-badge {
        width: 52px !important;
        height: 52px !important;
    }
    .pb-flama-badge span {
        font-size: 1.8rem !important;
    }
}
</style>

<!-- ══════════ KPI BENTO ══════════ -->
<div class="pb-grid-kpi">
    <?php
    $kpis = [
        ['label'=>'Días Acumulados',  'val'=>$diasValidos, 'sub'=>"de ~$diasMeta días meta", 'icon'=>'ti-calendar-check', 'cls'=>'c-teal',   'color'=>'#0d9488', 'ibg'=>'#ccfbf1', 'link'=>'/pasante/asistencia'],
        ['label'=>'Horas Acumuladas', 'val'=>$horasAcum,   'sub'=>"de $horasMeta h meta",    'icon'=>'ti-clock',          'cls'=>'c-blue',   'color'=>'#2563eb', 'ibg'=>'#eff6ff', 'link'=>null],
        ['label'=>'Este Mes',         'val'=>$estesMes,    'sub'=>date('F Y'),                'icon'=>'ti-calendar-stats', 'cls'=>'c-amber',  'color'=>'#d97706', 'ibg'=>'#fef3c7', 'link'=>null],
        ['label'=>'Progreso',         'val'=>(int)$pct,    'sub'=>'% de la meta',             'icon'=>'ti-chart-pie',      'cls'=>'c-indigo', 'color'=>'#6366f1', 'ibg'=>'#ede9fe', 'link'=>'/pasante/analiticas'],
    ];
    foreach ($kpis as $k): ?>
    <div class="pb-kpi <?= $k['cls'] ?>">
        <div class="pb-kpi-top">
            <div>
                <div class="pb-kpi-label"><?= $k['label'] ?></div>
                <div class="pb-kpi-val" style="color:<?= $k['color'] ?>;" data-kpi-value="<?= is_int($k['val']) ? $k['val'] : '' ?>"><?= $k['val'] ?></div>
                <div class="pb-kpi-sub"><?= $k['sub'] ?></div>
            </div>
            <?php if ($k['link']): ?>
            <a href="<?= URLROOT ?><?= $k['link'] ?>" class="pb-kpi-icon" style="background:<?= $k['ibg'] ?>;color:<?= $k['color'] ?>;" title="Ver detalle">
                <i class="ti <?= $k['icon'] ?>"></i>
            </a>
            <?php else: ?>
            <div class="pb-kpi-icon" style="background:<?= $k['ibg'] ?>;color:<?= $k['color'] ?>;">
                <i class="ti <?= $k['icon'] ?>"></i>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ══════════ FILA 2: PROGRESO + ANILLO ══════════ -->
<div class="pb-grid">

    <!-- Progreso visual -->
    <div class="pb-card">
            <div class="pb-card-title">
                <i class="ti ti-trending-up"></i> Mi Progreso de Pasantía
                <span style="margin-left:auto;font-size:.78rem;color:#94a3b8;font-weight:500;">Meta: <?= number_format($horasMeta) ?> h</span>
            </div>

            <!-- Horas -->
            <div style="margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#64748b;margin-bottom:5px;">
                    <span><i class="ti ti-clock" style="color:var(--color-accent-brand, #2563eb);"></i> Horas acumuladas</span>
                    <strong style="color:var(--color-accent-brand, #2563eb);"><?= $horasAcum ?> / <?= $horasMeta ?>h</strong>
                </div>
                <div class="pb-bar">
                    <div class="pb-bar-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,var(--color-accent-brand, #2563eb),#60a5fa);"></div>
                </div>
            </div>

            <!-- Días -->
            <div style="margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#64748b;margin-bottom:5px;">
                    <span><i class="ti ti-calendar-check" style="color:#0d9488;"></i> Días asistidos</span>
                    <strong style="color:#0d9488;"><?= $diasValidos ?> / <?= $diasMeta ?> días</strong>
                </div>
                <div class="pb-bar">
                    <div class="pb-bar-fill" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#0d9488,#34d399);"></div>
                </div>
            </div>

            <?php if ($pctCal > 0): ?>
            <!-- Tiempo transcurrido -->
            <div style="margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#64748b;margin-bottom:5px;">
                    <span><i class="ti ti-hourglass" style="color:#f59e0b;"></i> Tiempo transcurrido</span>
                    <strong style="color:#f59e0b;"><?= $pctCal ?>%</strong>
                </div>
                <div class="pb-bar">
                    <div class="pb-bar-fill" style="width:<?= $pctCal ?>%;background:linear-gradient(90deg,#f59e0b,#ef4444);"></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($diasFaltan > 0): ?>
            <div style="margin-top:4px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;font-size:.78rem;color:#15803d;display:flex;gap:8px;align-items:center;">
                <i class="ti ti-info-circle"></i>
                Faltan <strong><?= $diasFaltan ?> días</strong> para completar la pasantía
            </div>
            <?php else: ?>
            <div style="margin-top:4px;padding:10px 14px;background:#dcfce7;border:1px solid #86efac;border-radius:10px;font-size:.78rem;color:#15803d;font-weight:700;display:flex;gap:8px;align-items:center;">
                <i class="ti ti-circle-check"></i> ¡Meta de horas completada!
            </div>
            <?php endif; ?>

            <?php if ($pctCal - $pct > 20): ?>
            <div style="margin-top:10px;padding:10px 14px;background:#fff1f2;border:1px solid #fecdd3;border-radius:10px;font-size:.78rem;color:#dc2626;display:flex;gap:8px;align-items:center;">
                <i class="ti ti-alert-triangle"></i>
                El tiempo avanza <strong><?= round($pctCal - $pct, 1) ?> puntos</strong> más rápido que tu asistencia. Habla con tu tutor.
            </div>
            <?php endif; ?>
    </div>

    <!-- Anillo de progreso -->
    <div class="pb-card" style="align-items:center;text-align:center;">
        <div class="pb-card-title" style="justify-content:center;">
            <i class="ti ti-chart-donut"></i> Completado
        </div>
        <div class="pb-ring-wrap">
            <div class="pb-ring">
                <?php
                $r = 58; $cx = 70; $cy = 70;
                $circ = 2 * M_PI * $r;
                $dash = $circ * ($pct / 100);
                $gap  = $circ - $dash;
                ?>
                <svg width="140" height="140" viewBox="0 0 140 140">
                    <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none" stroke="#e2e8f0" stroke-width="12"/>
                    <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="<?= $r ?>" fill="none"
                        stroke="url(#pb-grad)" stroke-width="12"
                        stroke-linecap="round"
                        stroke-dasharray="<?= round($dash,2) ?> <?= round($gap,2) ?>"/>
                    <defs>
                        <linearGradient id="pb-grad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%"   stop-color="#2563eb"/>
                            <stop offset="100%" stop-color="#0d9488"/>
                        </linearGradient>
                    </defs>
                </svg>
                <div class="pb-ring-text">
                    <div class="pb-ring-pct"><?= $pct ?>%</div>
                    <div class="pb-ring-label">de la meta</div>
                </div>
            </div>
            <div style="font-size:.82rem;color:#64748b;line-height:1.6;text-align:center;">
                <strong style="color:#0f172a;"><?= $horasAcum ?>h</strong> acumuladas<br>
                de <strong style="color:#0f172a;"><?= $horasMeta ?>h</strong> requeridas
            </div>
        </div>
    </div>
</div>

<!-- ══════════ FILA 3: CALENDARIO | MI PASANTÍA | MI DÍA ══════════ -->
<?php
$asistenciaHoy  = $data['asistenciaHoy']  ?? null;
$actividadesHoy = $data['actividadesHoy'] ?? [];
$estadoHoyCfg = [
    'Presente'    => ['bg'=>'#dcfce7','color'=>'#16a34a','icon'=>'ti-check','label'=>'Presente'],
    'Ausente'     => ['bg'=>'#fee2e2','color'=>'#dc2626','icon'=>'ti-x',    'label'=>'Ausente'],
    'Justificado' => ['bg'=>'#dbeafe','color'=>'#1d4ed8','icon'=>'ti-file-description','label'=>'Justificado'],
];
?>
<div class="pb-grid-3">

    <!-- Calendario Mensual — estilo almanaque -->
    <div class="pb-card">
        <div class="pb-card-title">
            <i class="ti ti-calendar-month"></i>
                <?php
                    $mesesEs = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                                'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
                    echo $mesesEs[(int)date('m')] . ' ' . date('Y');
                ?>
                <span style="margin-left:auto;font-size:.72rem;color:#94a3b8;font-weight:500;">Asistencia mensual</span>
            </div>

            <!-- Cabecera L M M J V -->
            <div class="alm-week-header">
                <?php foreach (['L','M','M','J','V'] as $dh): ?>
                <div class="alm-header-cell"><?= $dh ?></div>
                <?php endforeach; ?>
            </div>

            <div class="alm-month-grid">
                <?php
                $almPrimer = new DateTime(date('Y-m-01'));
                $almUltimo = new DateTime(date('Y-m-t'));
                $almDow    = (int)$almPrimer->format('N'); // 1=Lun … 7=Dom
                // Celdas fantasma para alinear el primer día laborable
                $almEspacios = ($almDow <= 5) ? ($almDow - 1) : 0;
                for ($g = 0; $g < $almEspacios; $g++) {
                    echo '<div class="alm-cell is-empty" data-e="ghost"></div>';
                }
                $almDiasEnMes = (int)$almUltimo->format('d');
                for ($d = 1; $d <= $almDiasEnMes; $d++):
                    $almCur  = new DateTime(date('Y-m-') . sprintf('%02d', $d));
                    $fStr    = $almCur->format('Y-m-d');
                    $dow     = (int)$almCur->format('N');
                    if ($dow > 5) continue; // Sólo Lun–Vie

                    $esHoy  = $fStr === $hoyStr;
                    $esFut  = $fStr > $hoyStr;
                    $est    = $actMap[$fStr] ?? null;
                    $esFer  = array_key_exists($fStr, $feriadosMap);
                    $esLab  = $esFer && $feriadosMap[$fStr] === 1;

                    if ($est === 'Presente') {
                        $dataE = 'P'; $tip = 'Presente';
                    } elseif ($est === 'Justificado') {
                        $dataE = 'J'; $tip = 'Justificado';
                    } elseif ($est === 'Ausente') {
                        $dataE = 'A'; $tip = 'Ausente';
                    } elseif ($esFer && $esLab) {
                        $dataE = 'feriado_lab'; $tip = 'Feriado laborable';
                    } elseif ($esFer) {
                        $dataE = 'feriado'; $tip = 'Feriado';
                    } elseif ($esFut) {
                        $dataE = 'futuro'; $tip = '';
                    } else {
                        $dataE = 'sin_dato'; $tip = 'Sin registro';
                    }

                    $todayCls = $esHoy ? ' today' : '';
                    $emptyCls = ($dataE === 'futuro' || $dataE === 'ghost') ? ' is-empty' : '';
                    $titleStr = $almCur->format('d/m') . ($tip ? ' — ' . $tip : '');
                    echo "<div class=\"alm-cell{$todayCls}{$emptyCls}\" data-e=\"{$dataE}\" title=\"" . htmlspecialchars($titleStr) . "\">{$d}</div>";
                endfor; ?>
            </div>

            <div class="alm-legend">
                <span class="alm-legend-item"><span class="alm-legend-dot" style="background:#16a34a;"></span>Presente</span>
                <span class="alm-legend-item"><span class="alm-legend-dot" style="background:#dc2626;"></span>Ausente</span>
                <span class="alm-legend-item"><span class="alm-legend-dot" style="background:#2563eb;"></span>Justificado</span>
                <span class="alm-legend-item"><span class="alm-legend-dot" style="background:#f59e0b;"></span>Feriado</span>
                <span class="alm-legend-item"><span class="alm-legend-dot" style="background:#e2e8f0;"></span>Sin registro</span>
            </div>
        </div>

    <!-- Mi Pasantía -->
    <div class="pb-card">
        <div class="pb-card-title"><i class="ti ti-id-badge"></i> Mi Pasantía</div>

        <?php if (!empty($pasante->departamento_nombre)): ?>
        <div class="pb-info-pill">
            <i class="ti ti-building-community"></i>
            <div><span>Departamento</span><br><strong><?= htmlspecialchars($pasante->departamento_nombre) ?></strong></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($pasante->fecha_inicio)): ?>
        <div class="pb-info-pill">
            <i class="ti ti-calendar-event"></i>
            <div><span>Inicio</span><br><strong><?= date('d/m/Y', strtotime($pasante->fecha_inicio)) ?></strong></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($pasante->fecha_fin_estimada)): ?>
        <div class="pb-info-pill">
            <i class="ti ti-calendar-due"></i>
            <div><span>Fin estimado</span><br><strong><?= date('d/m/Y', strtotime($pasante->fecha_fin_estimada)) ?></strong></div>
        </div>
        <?php endif; ?>

        <a href="<?= URLROOT ?>/pasante/constancia"
           class="pb-btn-constancia"
           style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:14px;
                  padding:10px 16px;background:linear-gradient(135deg,var(--color-primary, #172554),var(--color-accent-brand, #2563eb));
                  color:#fff;border-radius:12px;font-size:.83rem;font-weight:700;text-decoration:none;
                  transition:opacity .2s;"
           onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            <i class="ti ti-file-certificate"></i> Ver Constancia
        </a>
    </div>

    <!-- Mi Día de Hoy -->
    <div class="pb-card">
        <div class="pb-card-title">
            <i class="ti ti-sun"></i> Mi Día de Hoy
            <span style="margin-left:auto;font-size:.72rem;color:#94a3b8;font-weight:500;">
                <?= date('d/m/Y') ?>
            </span>
        </div>

        <!-- Estado de asistencia -->
        <?php if ($asistenciaHoy): ?>
            <?php $hc = $estadoHoyCfg[$asistenciaHoy->estado] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','icon'=>'ti-circle','label'=>$asistenciaHoy->estado]; ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <span class="hoy-estado-badge" style="background:<?= $hc['bg'] ?>;color:<?= $hc['color'] ?>;">
                    <i class="ti <?= $hc['icon'] ?>"></i> <?= $hc['label'] ?>
                </span>
                <?php if (!empty($asistenciaHoy->hora_registro)): ?>
                <span style="font-size:.75rem;color:#94a3b8;font-weight:600;">
                    <i class="ti ti-clock"></i> <?= date('g:i A', strtotime($asistenciaHoy->hora_registro)) ?>
                    &nbsp;·&nbsp; <?= htmlspecialchars($asistenciaHoy->metodo ?? 'Kiosco') ?>
                </span>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <span class="hoy-estado-badge" style="background:#f1f5f9;color:#94a3b8;">
                    <i class="ti ti-minus"></i> Sin registro
                </span>
            </div>
        <?php endif; ?>

        <!-- Actividades del día -->
        <div style="font-size:.72rem;font-weight:800;text-transform:uppercase;color:#94a3b8;letter-spacing:.05em;margin-bottom:8px;">
            <i class="ti ti-pencil"></i> Actividades registradas
        </div>

        <?php if (!empty($actividadesHoy)): ?>
            <?php foreach ($actividadesHoy as $act): ?>
            <div class="hoy-actividad">
                <div class="hoy-actividad-titulo"><?= htmlspecialchars($act->titulo) ?></div>
                <?php if (!empty($act->descripcion)): ?>
                <div class="hoy-actividad-desc"><?= htmlspecialchars(mb_strimwidth($act->descripcion, 0, 90, '…')) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center;padding:20px 10px;color:#94a3b8;">
                <i class="ti ti-note-off" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.4;"></i>
                <span style="font-size:.78rem;">Sin actividades registradas hoy</span>
            </div>
            <a href="<?= URLROOT ?>/pasante/misActividades"
               class="pb-btn-actividad"
               style="display:flex;align-items:center;justify-content:center;gap:6px;
                      padding:9px 14px;background:#f8fafc;border:1.5px solid #e2e8f0;
                      color:var(--color-accent-brand, #2563eb);border-radius:10px;font-size:.8rem;font-weight:700;
                      text-decoration:none;transition:background .2s;margin-top:8px;"
               onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='#f8fafc'">
                <i class="ti ti-plus"></i> Registrar actividad
            </a>
        <?php endif; ?>
    </div>

</div>

<!-- ══════════ TIMELINE ACTIVIDAD ══════════ -->
<div class="pb-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div class="pb-card-title" style="margin-bottom:0;">
            <i class="ti ti-activity"></i> Mis Últimas Asistencias
        </div>
        <a href="<?= URLROOT ?>/pasante/asistencia"
           class="pb-link-asistencia"
           style="font-size:.8rem;font-weight:700;color:var(--color-accent-brand, #2563eb);text-decoration:none;display:flex;align-items:center;gap:4px;">
            Ver todo <i class="ti ti-arrow-right"></i>
        </a>
    </div>

    <?php if (empty($actividades)): ?>
    <div style="text-align:center;padding:40px;color:#94a3b8;">
        <i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.4;"></i>
        Aún no tienes asistencias registradas
    </div>
    <?php else: ?>
    <div class="pb-timeline">
        <?php
        $cfg = [
            'Presente'    => ['bg'=>'#dcfce7', 'color'=>'#16a34a', 'icon'=>'ti-check',           'badge_bg'=>'#f0fdf4'],
            'Ausente'     => ['bg'=>'#fee2e2', 'color'=>'#dc2626', 'icon'=>'ti-x',               'badge_bg'=>'#fff1f2'],
            'Justificado' => ['bg'=>'#dbeafe', 'color'=>'#1d4ed8', 'icon'=>'ti-file-description', 'badge_bg'=>'#eff6ff'],
        ];
        foreach ($actividades as $a):
            $est = $a->estado ?? 'Presente';
            $c   = $cfg[$est] ?? $cfg['Presente'];
            $fecha = !empty($a->fecha) ? date('d/m/Y', strtotime($a->fecha)) : '—';
            $hora  = !empty($a->hora_registro) ? date('g:i A', strtotime($a->hora_registro)) : '—';
        ?>
        <div class="pb-tl-item">
            <div class="pb-tl-dot" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>;">
                <i class="ti <?= $c['icon'] ?>"></i>
            </div>
            <div>
                <div class="pb-tl-date"><?= $fecha ?></div>
                <div class="pb-tl-meta">
                    <i class="ti ti-clock" style="font-size:.7rem;"></i> <?= $hora ?>
                    &nbsp;·&nbsp;
                    <i class="ti ti-device-tablet" style="font-size:.7rem;"></i> <?= htmlspecialchars($a->metodo ?? 'Kiosco') ?>
                </div>
            </div>
            <div class="pb-tl-badge" style="background:<?= $c['badge_bg'] ?>;color:<?= $c['color'] ?>;">
                <?= $est ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
</div><!-- /pb-wrap -->

<script>
(function(){
    function tick() {
        const now = new Date();
        const d = document.getElementById('pb-date');
        const t = document.getElementById('pb-time');
        if (d) {
            const s = now.toLocaleDateString('es-ES', {weekday:'long', day:'numeric', month:'long', year:'numeric'});
            d.textContent = s.charAt(0).toUpperCase() + s.slice(1);
        }
        if (t) {
            t.textContent = now.toLocaleTimeString('es-ES', {hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true}).toUpperCase();
        }
    }
    tick(); setInterval(tick, 1000);
})();

<?php if ($sinAsignar): ?>
var _checks = 0;
var _poll = setInterval(async () => {
    try {
        const r = await fetch('<?= URLROOT ?>/pasante/getStatusAjax');
        const d = await r.json();
        if (d.success && d.estado !== '<?= $estadoPasantia ?>' && !['Sin Asignar','Pendiente',''].includes(d.estado)) {
            clearInterval(_poll); location.reload();
        }
        if (++_checks > 360) clearInterval(_poll);
    } catch(e) {}
}, 5000);
<?php endif; ?>
</script>
