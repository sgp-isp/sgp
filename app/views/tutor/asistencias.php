<?php
/**
 * Tutor — Asistencias de Mis Pasantes (Diaria / Semanal)
 * REDISEÑO PREMIUM v2
 */
$esDiaria   = $vista === 'diaria';
$esSemanal  = $vista === 'semanal';
$esMensual  = $vista === 'mensual';
$esTotal    = $vista === 'total';
$totalActivos = $totalActivos ?? 0;
$bentoMensual     = $bentoMensual     ?? [];
$resumenTotal     = $resumenTotal     ?? [];
$chartSemanasJson = $chartSemanasJson ?? '[]';
$chartPctsJson    = $chartPctsJson    ?? '[]';
$healthIndex      = $healthIndex      ?? 100;
$nombreMes        = $nombreMes        ?? '';
$urlMesAnt        = $urlMesAnt        ?? '#';
$urlMesSig        = $urlMesSig        ?? '#';
$presentes    = $presentes    ?? 0;
$ausentes     = $ausentes     ?? 0;
$justificados = $justificados ?? 0;
$sinMarcarCount = count($sinMarcar ?? []);
$porcAsistencia = $totalActivos > 0 ? round(($presentes + $justificados) / $totalActivos * 100) : 0;
?>
<style>
/* ═══════════════════════════════════════════
   ASISTENCIAS PREMIUM — Tutor View v2
═══════════════════════════════════════════ */

/* ── Base ── */
.ta-wrap { display:flex; flex-direction:column; gap:22px; width:100%; }

/* ═══ BANNER ═══ */
.ta-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 45%, #3b82f6 100%);
    border-radius: 24px;
    padding: 0;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(30,58,138,.30);
}
/* Mesh background pattern */
.ta-hero::before {
    content:'';
    position:absolute; inset:0;
    background-image:
        radial-gradient(circle at 20% 50%, rgba(99,102,241,.25) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(16,185,129,.15) 0%, transparent 40%),
        radial-gradient(circle at 90% 80%, rgba(59,130,246,.20) 0%, transparent 40%);
}
/* Decorative rings */
.ta-hero::after {
    content:'';
    position:absolute; top:-80px; right:-80px;
    width:320px; height:320px;
    border-radius:50%;
    border:1px solid rgba(255,255,255,.06);
    box-shadow: 0 0 0 60px rgba(255,255,255,.03), 0 0 0 120px rgba(255,255,255,.02);
}
.ta-hero-inner {
    position:relative; z-index:2;
    display:flex; align-items:stretch; justify-content:space-between;
    flex-wrap:wrap; gap:0;
}
/* Left section */
.ta-hero-left {
    display:flex; align-items:center; gap:18px;
    padding: 30px 36px;
    flex:1; min-width:260px;
}
.ta-hero-icon-wrap {
    width:64px; height:64px; flex-shrink:0;
    border-radius:18px;
    background: linear-gradient(135deg, rgba(255,255,255,.2), rgba(255,255,255,.08));
    border: 1px solid rgba(255,255,255,.18);
    display:flex; align-items:center; justify-content:center;
    box-shadow: 0 8px 24px rgba(0,0,0,.15);
    backdrop-filter: blur(10px);
}
.ta-hero-tag {
    display:inline-flex; align-items:center; gap:5px;
    background: rgba(16,185,129,.2);
    border: 1px solid rgba(16,185,129,.35);
    color: #6ee7b7;
    font-size:.72rem; font-weight:700; letter-spacing:.04em;
    padding:3px 10px; border-radius:20px; margin-bottom:6px;
}
.ta-hero-title {
    color:#fff; font-size:1.85rem; font-weight:800;
    margin:0 0 3px; letter-spacing:-.02em; line-height:1.1;
}
.ta-hero-sub { color:rgba(255,255,255,.6); font-size:.85rem; margin:0; }

/* Right section — controls pill */
.ta-hero-right {
    display:flex; align-items:center;
    padding:22px 28px;
    border-left:1px solid rgba(255,255,255,.08);
}
.ta-controls-pill {
    display:flex; align-items:center; gap:10px;
    background: rgba(0,0,0,.2);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 16px;
    padding: 10px 14px;
    flex-wrap:wrap;
}
.ta-toggle-group {
    display:flex; gap:3px;
    background: rgba(0,0,0,.25);
    border-radius:10px; padding:3px;
}
.ta-toggle-btn {
    display:flex; align-items:center; gap:5px;
    padding:.42rem 1rem; border-radius:8px;
    font-size:.8rem; font-weight:600;
    border:none; cursor:pointer;
    background:transparent; color:rgba(255,255,255,.65);
    transition:all .18s; text-decoration:none;
}
.ta-toggle-btn.active {
    background: rgba(255,255,255,.18);
    color:#fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.2);
}
.ta-toggle-btn:hover:not(.active) { color:#fff; background:rgba(255,255,255,.08); }
.ta-sep { width:1px; height:28px; background:rgba(255,255,255,.12); flex-shrink:0; }
.ta-reg-btn {
    display:flex; align-items:center; gap:6px;
    padding:.48rem 1.1rem; border-radius:10px;
    font-size:.8rem; font-weight:700;
    border:none; cursor:pointer;
    background: linear-gradient(135deg,#10b981,#059669);
    color:#fff;
    box-shadow: 0 4px 12px rgba(16,185,129,.35);
    transition:all .18s;
}
.ta-reg-btn:hover { transform:translateY(-1px); box-shadow: 0 6px 18px rgba(16,185,129,.45); }

/* ═══ KPI GRID ═══ */
.ta-kpi-grid {
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:14px;
}
@media(max-width:900px){ .ta-kpi-grid{ grid-template-columns:repeat(2,1fr); } }
@media(max-width:500px){ .ta-kpi-grid{ grid-template-columns:1fr; } }

.ta-kpi-card {
    border-radius:18px; padding:20px 22px;
    position:relative; overflow:hidden;
    display:flex; flex-direction:column; gap:14px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    transition: transform .2s, box-shadow .2s;
    cursor:default;
}
.ta-kpi-card:hover { transform:translateY(-4px); }
.ta-kpi-card.kp-green { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1px solid #bbf7d0; }
.ta-kpi-card.kp-red   { background:linear-gradient(135deg,#fff1f2,#fee2e2); border:1px solid #fecaca; }
.ta-kpi-card.kp-amber { background:linear-gradient(135deg,#fffbeb,#fef3c7); border:1px solid #fde68a; }
.ta-kpi-card.kp-indigo{ background:linear-gradient(135deg,#f5f3ff,#ede9fe); border:1px solid #c4b5fd; }
.ta-kpi-card:hover.kp-green  { box-shadow: 0 12px 32px rgba(16,185,129,.18); }
.ta-kpi-card:hover.kp-red    { box-shadow: 0 12px 32px rgba(239,68,68,.15); }
.ta-kpi-card:hover.kp-amber  { box-shadow: 0 12px 32px rgba(245,158,11,.15); }
.ta-kpi-card:hover.kp-indigo { box-shadow: 0 12px 32px rgba(99,102,241,.18); }

/* Decorative circle */
.ta-kpi-card::before {
    content:''; position:absolute;
    width:100px; height:100px; border-radius:50%;
    top:-20px; right:-20px; opacity:.4;
}
.kp-green::before  { background:#86efac; }
.kp-red::before    { background:#fca5a5; }
.kp-amber::before  { background:#fcd34d; }
.kp-indigo::before { background:#a5b4fc; }

.ta-kpi-top { display:flex; align-items:center; justify-content:space-between; }
.ta-kpi-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
.kp-green  .ta-kpi-label { color:#15803d; }
.kp-red    .ta-kpi-label { color:#b91c1c; }
.kp-amber  .ta-kpi-label { color:#b45309; }
.kp-indigo .ta-kpi-label { color:#4338ca; }

.ta-kpi-badge {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem;
}
.kp-green  .ta-kpi-badge { background:rgba(16,185,129,.15); color:#10b981; }
.kp-red    .ta-kpi-badge { background:rgba(239,68,68,.15);  color:#ef4444; }
.kp-amber  .ta-kpi-badge { background:rgba(245,158,11,.15); color:#f59e0b; }
.kp-indigo .ta-kpi-badge { background:rgba(99,102,241,.15); color:#6366f1; }

.ta-kpi-val {
    font-size:2.6rem; font-weight:900; line-height:1;
    letter-spacing:-.04em;
}
.kp-green  .ta-kpi-val { color:#15803d; }
.kp-red    .ta-kpi-val { color:#dc2626; }
.kp-amber  .ta-kpi-val { color:#b45309; }
.kp-indigo .ta-kpi-val { color:#4338ca; }

/* Progress bar for % */
.ta-kpi-bar-track {
    height:5px; border-radius:99px;
    background:rgba(0,0,0,.08); overflow:hidden;
}
.ta-kpi-bar-fill { height:100%; border-radius:99px; transition:width .6s ease; }
.kp-green  .ta-kpi-bar-fill { background:linear-gradient(90deg,#34d399,#10b981); }
.kp-red    .ta-kpi-bar-fill { background:linear-gradient(90deg,#f87171,#ef4444); }
.kp-amber  .ta-kpi-bar-fill { background:linear-gradient(90deg,#fbbf24,#f59e0b); }
.kp-indigo .ta-kpi-bar-fill { background:linear-gradient(90deg,#818cf8,#6366f1); }

/* ═══ PANEL CARD ═══ */
.ta-panel {
    background:#fff; border-radius:20px;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    border:1px solid #f1f5f9;
    overflow:hidden;
}
.ta-panel-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:18px 24px;
    border-bottom:1px solid #f1f5f9;
    background:#fafbfd;
    flex-wrap:wrap; gap:10px;
}
.ta-panel-title {
    font-size:.95rem; font-weight:700; color:#1e293b;
    display:flex; align-items:center; gap:8px;
}
.ta-panel-title i { color:#6366f1; }
.ta-panel-body { padding:20px 24px; }

/* Date picker toolbar */
.ta-toolbar {
    display:flex; align-items:center; gap:8px; flex-wrap:wrap;
}
/* ── Date Navigator Premium ── */
.ta-date-nav {
    display:flex; align-items:center; gap:0;
    background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:12px;
    overflow:hidden; transition:border-color .15s;
}
.ta-date-nav:focus-within { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.ta-nav-arrow {
    width:34px; height:38px;
    display:flex; align-items:center; justify-content:center;
    background:transparent; border:none; cursor:pointer;
    color:#64748b; font-size:1rem;
    transition:all .15s;
}
.ta-nav-arrow:hover { background:#ede9fe; color:#6366f1; }
.ta-nav-arrow:first-child { border-right:1px solid #e2e8f0; }
.ta-nav-arrow:last-child  { border-left:1px solid #e2e8f0; }
.ta-date-display {
    display:flex; align-items:center; gap:8px;
    padding:0 12px; cursor:pointer; position:relative;
}
.ta-date-display i { color:#6366f1; font-size:.95rem; flex-shrink:0; }
.ta-date-label {
    display:flex; flex-direction:column; line-height:1.2;
}
.ta-date-label-day  { font-size:.72rem; font-weight:600; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; }
.ta-date-label-full { font-size:.88rem; font-weight:700; color:#1e293b; white-space:nowrap; }
/* Hidden real input — cubre todo el área del display para recibir click directo */
.ta-date-hidden {
    position:absolute; inset:0;
    opacity:0; width:100%; height:100%;
    cursor:pointer; z-index:2;
}
/* Today chip */
.ta-today-btn {
    display:flex; align-items:center; gap:4px;
    padding:.38rem .8rem; border-radius:10px;
    background:#ede9fe; color:#6366f1; border:none; cursor:pointer;
    font-size:.78rem; font-weight:700; transition:all .15s;
    white-space:nowrap;
}
.ta-today-btn:hover { background:#6366f1; color:#fff; }
.ta-apply-btn {
    display:flex; align-items:center; gap:6px;
    padding:.45rem 1.1rem; border-radius:10px;
    background: linear-gradient(135deg,#6366f1,#4f46e5);
    color:#fff; font-size:.82rem; font-weight:700;
    border:none; cursor:pointer;
    box-shadow: 0 3px 10px rgba(99,102,241,.3);
    transition:all .15s;
}
.ta-apply-btn:hover { transform:translateY(-1px); box-shadow:0 5px 14px rgba(99,102,241,.4); }

/* Count chip */
.ta-count-chip {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 10px; border-radius:20px;
    background:#ede9fe; color:#6366f1;
    font-size:.74rem; font-weight:700;
}

/* ═══ TABLE ═══ */
.ta-table-wrap { overflow-x:auto; }
.ta-table {
    width:100%; border-collapse:collapse;
    min-width:600px;
}
.ta-table thead tr {
    background:#f8fafc;
    border-bottom:1px solid #e2e8f0;
}
.ta-table th {
    padding:.7rem 1.1rem;
    font-size:.72rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.06em;
    color:#94a3b8; text-align:left;
}
.ta-table tbody tr {
    border-bottom:1px solid #f1f5f9;
    transition:background .12s;
}
.ta-table tbody tr:last-child { border-bottom:none; }
.ta-table tbody tr:hover { background:#f8faff; }
.ta-table td { padding:.75rem 1.1rem; color:#1e293b; font-size:.87rem; vertical-align:middle; }

/* Avatar + name */
.ta-pax-cell { display:flex; align-items:center; gap:11px; }
.ta-pax-link { text-decoration:none; display:flex; align-items:center; gap:11px; }
.ta-pax-link:hover .ta-pax-name { color:#6366f1; text-decoration:underline; }
.ta-pax-link:hover .ta-avatar { box-shadow: 0 4px 12px rgba(99,102,241,.4); transform:scale(1.06); }
.ta-avatar {
    width:36px; height:36px; border-radius:10px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.78rem; font-weight:800; color:#fff;
    background: linear-gradient(135deg,#6366f1,#4f46e5);
    box-shadow: 0 3px 8px rgba(99,102,241,.25);
    transition: all .18s;
}
.ta-pax-name { font-weight:600; color:#1e293b; line-height:1.3; font-size:.87rem; transition:color .15s; }
.ta-pax-id   { font-size:.75rem; color:#94a3b8; font-weight:500; }

/* Hour chip */
.ta-hour {
    display:inline-flex; align-items:center; gap:4px;
    background:#f1f5f9; color:#475569;
    padding:3px 9px; border-radius:8px; font-size:.8rem; font-weight:600;
}

/* Estado badges */
.ta-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:.22rem .75rem; border-radius:999px;
    font-size:.74rem; font-weight:700; white-space:nowrap;
}
.ta-badge.presente    { background:rgba(16,185,129,.12); color:#059669; border:1px solid rgba(16,185,129,.2); }
.ta-badge.ausente     { background:rgba(239,68,68,.10);  color:#dc2626; border:1px solid rgba(239,68,68,.18); }
.ta-badge.justificado { background:rgba(245,158,11,.12); color:#b45309; border:1px solid rgba(245,158,11,.2); }
.ta-badge.sinmarcar   { background:rgba(148,163,184,.12);color:#64748b; border:1px solid rgba(148,163,184,.2); }

.ta-badge::before { content:''; width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.ta-badge.presente::before    { background:#10b981; }
.ta-badge.ausente::before     { background:#ef4444; }
.ta-badge.justificado::before { background:#f59e0b; }
.ta-badge.sinmarcar::before   { background:#94a3b8; }

/* Método chip */
.ta-metodo {
    font-size:.78rem; color:#64748b;
    display:inline-flex; align-items:center; gap:4px;
}

/* Mark button */
.ta-btn-marcar {
    display:inline-flex; align-items:center; gap:5px;
    padding:.3rem .8rem; border-radius:8px;
    font-size:.77rem; font-weight:700;
    border:1.5px solid #c4b5fd; background:#f5f3ff; color:#6366f1;
    cursor:pointer; transition:all .15s;
}
.ta-btn-marcar:hover {
    background:linear-gradient(135deg,#6366f1,#4f46e5);
    border-color:#6366f1; color:#fff;
    box-shadow:0 4px 10px rgba(99,102,241,.3);
}

/* Empty state */
.ta-empty {
    text-align:center; padding:3.5rem 2rem; color:#94a3b8;
}
.ta-empty i { font-size:2.8rem; opacity:.35; display:block; margin-bottom:10px; }
.ta-empty p { margin:0; font-size:.9rem; }

/* ═══ SEMANAL ═══ */
.ta-week-nav {
    display:flex; align-items:center; gap:12px;
}
.ta-week-nav-btn {
    display:flex; align-items:center; gap:5px;
    padding:.4rem .9rem; border-radius:10px;
    border:1.5px solid #e2e8f0; background:#fff;
    color:#475569; font-size:.82rem; font-weight:600;
    text-decoration:none; transition:all .15s;
}
.ta-week-nav-btn:hover { border-color:#6366f1; color:#6366f1; background:#f5f3ff; }
.ta-week-label {
    flex:1; text-align:center;
    font-weight:700; color:#1e293b; font-size:.92rem;
}
.ta-week-label i { color:#6366f1; margin-right:5px; }

/* Department group */
.ta-dept-group { margin-bottom:16px; border-radius:14px; overflow:hidden; border:1px solid #e2e8f0; }
.ta-dept-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:12px 16px;
    background:linear-gradient(135deg,#f8faff,#f1f5f9);
    cursor:pointer; user-select:none;
    transition:background .15s;
}
.ta-dept-header:hover { background:linear-gradient(135deg,#ede9fe,#e0e7ff); }
.ta-dept-header-left { display:flex; align-items:center; gap:10px; }
.ta-dept-dot { width:10px; height:10px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#4f46e5); flex-shrink:0; }
.ta-dept-name { font-size:.87rem; font-weight:700; color:#1e293b; }
.ta-dept-count { font-size:.73rem; font-weight:700; color:#6366f1; background:#ede9fe; padding:2px 8px; border-radius:20px; }
.ta-dept-chevron { color:#6366f1; font-size:.9rem; transition:transform .2s; }
.ta-dept-group.collapsed .ta-dept-chevron { transform:rotate(-90deg); }
.ta-dept-group.collapsed .ta-dept-body { display:none; }
.ta-dept-body { overflow-x:auto; }

/* Heat table */
.ta-heat-tbl { width:100%; border-collapse:collapse; min-width:520px; }
.ta-heat-tbl th {
    padding:.55rem .8rem;
    font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    color:#64748b; background:#f8fafc;
    border-bottom:1px solid #e2e8f0;
    text-align:center;
}
.ta-heat-tbl th:first-child { text-align:left; padding-left:16px; }
.ta-heat-tbl td {
    padding:.52rem .8rem; text-align:center;
    font-size:.83rem; border-bottom:1px solid #f1f5f9; color:#1e293b;
}
.ta-heat-tbl td:first-child { text-align:left; padding-left:16px; font-weight:500; }
.ta-heat-tbl tr:last-child td { border-bottom:none; }
.ta-heat-tbl tr:hover td { background:#fafbff; }

/* Heat cells */
.hc { display:inline-flex; align-items:center; justify-content:center;
       width:30px; height:24px; border-radius:6px; font-size:.78rem; font-weight:700; }
.hc-p { background:rgba(16,185,129,.14); color:#059669; }
.hc-a { background:rgba(239,68,68,.12);  color:#dc2626; }
.hc-j { background:rgba(245,158,11,.13); color:#b45309; }
.hc-x { color:#cbd5e1; }

.hc-total-p { font-weight:800; color:#059669; }
.hc-total-a { font-weight:800; color:#dc2626; }
.hc-total-j { font-weight:800; color:#b45309; }

/* ═══ MODAL ═══ */
.ta-modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(15,23,42,.6); backdrop-filter:blur(4px);
    z-index:1050; align-items:center; justify-content:center;
}
.ta-modal-overlay.open { display:flex; }
.ta-modal-box {
    background:#fff; border-radius:20px;
    width:100%; max-width:460px; margin:16px;
    box-shadow: 0 24px 80px rgba(0,0,0,.25);
    overflow:hidden;
}
.ta-modal-head {
    background:linear-gradient(135deg,#1e3a8a,#3b82f6);
    padding:20px 24px;
    display:flex; align-items:center; justify-content:space-between;
}
.ta-modal-head-title {
    display:flex; align-items:center; gap:10px;
    color:#fff; font-size:1.05rem; font-weight:700;
}
.ta-modal-head-icon {
    width:36px; height:36px; border-radius:10px;
    background:rgba(255,255,255,.15);
    display:flex; align-items:center; justify-content:center;
    color:#fff;
}
.ta-modal-close {
    width:30px; height:30px; border-radius:8px;
    background:rgba(255,255,255,.12); border:none; cursor:pointer;
    color:rgba(255,255,255,.8); display:flex; align-items:center; justify-content:center;
    transition:background .15s; font-size:1.05rem;
}
.ta-modal-close:hover { background:rgba(255,255,255,.22); color:#fff; }
.ta-modal-body { padding:22px 24px; }
.ta-field { margin-bottom:14px; }
.ta-field label {
    display:flex; align-items:center; gap:5px;
    font-size:.78rem; font-weight:700; color:#475569;
    text-transform:uppercase; letter-spacing:.04em;
    margin-bottom:5px;
}
.ta-field select,
.ta-field input[type=date],
.ta-field textarea {
    width:100%; padding:.6rem 1rem;
    border:1.5px solid #e2e8f0; border-radius:10px;
    background:#fafbfd; color:#1e293b;
    font-size:.9rem; transition:border-color .15s;
    box-sizing:border-box;
}
.ta-field select:focus,
.ta-field input[type=date]:focus,
.ta-field textarea:focus {
    outline:none; border-color:#6366f1;
    background:#fff; box-shadow:0 0 0 3px rgba(99,102,241,.1);
}
.ta-field textarea { resize:vertical; min-height:72px; }
.ta-modal-footer {
    display:flex; gap:10px;
    padding:0 24px 22px;
}
.ta-modal-footer button { flex:1; padding:.55rem; border-radius:10px; font-size:.88rem; font-weight:700; border:none; cursor:pointer; transition:all .15s; }
.ta-btn-sec { background:#f1f5f9; color:#475569; }
.ta-btn-sec:hover { background:#e2e8f0; }
.ta-btn-pri {
    background:linear-gradient(135deg,#10b981,#059669);
    color:#fff;
    box-shadow:0 4px 12px rgba(16,185,129,.3);
}
.ta-btn-pri:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(16,185,129,.4); }

@media(max-width:640px){
    .ta-hero-left  { padding:22px 20px; }
    .ta-hero-right { padding:16px 20px; border-left:none; border-top:1px solid rgba(255,255,255,.08); width:100%; }
    .ta-controls-pill { width:100%; flex-wrap:wrap; }
    .ta-panel-body { padding:16px; }
}
</style>

<div class="ta-wrap">

<!-- ══════════════════════════════════
     HERO BANNER
══════════════════════════════════ -->
<div class="ta-hero">
    <div class="ta-hero-inner">
        <!-- Izquierda: icono + título + tabs pill -->
        <div class="ta-hero-left" style="flex-direction:column;align-items:flex-start;gap:14px;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div class="ta-hero-icon-wrap">
                    <i class="ti ti-clock-check" style="font-size:30px;color:#fff;"></i>
                </div>
                <div>
                    <div class="ta-hero-tag">
                        <i class="ti ti-shield-check" style="font-size:.9rem;"></i>
                        Control de Asistencia · Tutor
                    </div>
                    <h1 class="ta-hero-title">Asistencias</h1>
                </div>
            </div>
            <!-- Tabs como pills (igual que admin) -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <?php
                $tabs = [
                    ['key'=>'diaria',  'label'=>'Diaria'],
                    ['key'=>'semanal', 'label'=>'Semanal'],
                    ['key'=>'mensual', 'label'=>'Mensual'],
                    ['key'=>'total',   'label'=>'Total'],
                ];
                $hrefs = [
                    'diaria'  => URLROOT.'/tutor/asistencias?vista=diaria',
                    'semanal' => URLROOT.'/tutor/asistencias?vista=semanal&semana='.$paramsUrl['semana'].'&anio='.$paramsUrl['anio'],
                    'mensual' => URLROOT.'/tutor/asistencias?vista=mensual&mes='.$paramsUrl['mes'].'&anio='.$paramsUrl['anio'],
                    'total'   => URLROOT.'/tutor/asistencias?vista=total',
                ];
                foreach ($tabs as $t):
                    $active = ($vista === $t['key']);
                    $s = $active
                        ? "background:rgba(255,255,255,.28);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.4);border-radius:50px;padding:6px 18px;color:#fff;font-size:.84rem;font-weight:700;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,.12);"
                        : "background:rgba(255,255,255,.10);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.15);border-radius:50px;padding:6px 18px;color:rgba(255,255,255,.85);font-size:.84rem;font-weight:600;text-decoration:none;";
                ?>
                <a href="<?= $hrefs[$t['key']] ?>" style="<?= $s ?>"
                   onmouseover="this.style.background='rgba(255,255,255,.35)'"
                   onmouseout="this.style.background='<?= $active ? 'rgba(255,255,255,.28)' : 'rgba(255,255,255,.10)' ?>'">
                    <?= $t['label'] ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Derecha: botones Consulta Rápida + Registro Manual -->
        <div class="ta-hero-right" style="align-items:center;gap:10px;display:flex;">
            <button onclick="document.getElementById('modalConsulta').classList.add('open')"
                style="background:rgba(255,255,255,.15);backdrop-filter:blur(10px);color:#fff;border:1px solid rgba(255,255,255,.3);padding:10px 20px;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all .2s;"
                onmouseover="this.style.background='rgba(255,255,255,.25)'"
                onmouseout="this.style.background='rgba(255,255,255,.15)'">
                <i class="ti ti-search" style="font-size:1.1rem;"></i> Consulta Rápida
            </button>
            <button onclick="document.getElementById('modalManual').classList.add('open')"
                style="background:#fff;color:#1e3a8a;border:none;padding:10px 22px;border-radius:10px;font-weight:700;font-size:.9rem;cursor:pointer;display:flex;align-items:center;gap:8px;box-shadow:0 4px 10px rgba(0,0,0,.1);transition:all .2s;"
                onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 20px rgba(0,0,0,.15)'"
                onmouseout="this.style.transform='';this.style.boxShadow='0 4px 10px rgba(0,0,0,.1)'">
                <i class="ti ti-plus" style="font-size:1.1rem;"></i> Registrar Manual
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════
     KPI CARDS
══════════════════════════════════ -->
<div class="ta-kpi-grid">

    <div class="ta-kpi-card kp-green">
        <div class="ta-kpi-top">
            <span class="ta-kpi-label">Presentes</span>
            <div class="ta-kpi-badge"><i class="ti ti-user-check"></i></div>
        </div>
        <div class="ta-kpi-val"><?= $presentes ?></div>
        <div class="ta-kpi-bar-track">
            <div class="ta-kpi-bar-fill" style="width:<?= $totalActivos > 0 ? round($presentes/$totalActivos*100) : 0 ?>%"></div>
        </div>
    </div>

    <div class="ta-kpi-card kp-red">
        <div class="ta-kpi-top">
            <span class="ta-kpi-label">Ausentes</span>
            <div class="ta-kpi-badge"><i class="ti ti-user-x"></i></div>
        </div>
        <div class="ta-kpi-val"><?= $ausentes ?></div>
        <div class="ta-kpi-bar-track">
            <div class="ta-kpi-bar-fill" style="width:<?= $totalActivos > 0 ? round($ausentes/$totalActivos*100) : 0 ?>%"></div>
        </div>
    </div>

    <div class="ta-kpi-card kp-amber">
        <div class="ta-kpi-top">
            <span class="ta-kpi-label">Justificados</span>
            <div class="ta-kpi-badge"><i class="ti ti-file-check"></i></div>
        </div>
        <div class="ta-kpi-val"><?= $justificados ?></div>
        <div class="ta-kpi-bar-track">
            <div class="ta-kpi-bar-fill" style="width:<?= $totalActivos > 0 ? round($justificados/$totalActivos*100) : 0 ?>%"></div>
        </div>
    </div>

    <div class="ta-kpi-card kp-indigo">
        <div class="ta-kpi-top">
            <span class="ta-kpi-label">% Asistencia</span>
            <div class="ta-kpi-badge"><i class="ti ti-chart-donut"></i></div>
        </div>
        <div class="ta-kpi-val"><?= $porcAsistencia ?>%</div>
        <div class="ta-kpi-bar-track">
            <div class="ta-kpi-bar-fill" style="width:<?= $porcAsistencia ?>%"></div>
        </div>
    </div>

</div>

<?php if ($esDiaria): ?>
<!-- ══════════════════════════════════
     VISTA DIARIA — Navegador de fecha
══════════════════════════════════ -->
<div class="ta-panel" style="padding:16px 24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div style="font-size:.88rem;color:#64748b;font-weight:500;">
            <i class="ti ti-calendar-event" style="color:#6366f1;margin-right:4px;"></i>
            Selecciona una fecha para ver los registros
        </div>
        <div class="ta-toolbar">
            <div class="ta-date-nav">
                <button class="ta-nav-arrow" onclick="moverFecha(-1)" title="Día anterior">
                    <i class="ti ti-chevron-left"></i>
                </button>
                <label for="filtroFecha" class="ta-date-display" style="cursor:pointer;">
                    <i class="ti ti-calendar-event"></i>
                    <div class="ta-date-label">
                        <span class="ta-date-label-day" id="fechaDiaNombre"></span>
                        <span class="ta-date-label-full" id="fechaTexto"></span>
                    </div>
                    <input type="date" id="filtroFecha" class="ta-date-hidden"
                           value="<?= htmlspecialchars($paramsUrl['fecha']) ?>"
                           max="<?= $hoy ?>"
                           onchange="sincronizarFecha()">
                </label>
                <button class="ta-nav-arrow" onclick="moverFecha(1)" title="Día siguiente">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>
            <button class="ta-today-btn" onclick="irHoy()">
                <i class="ti ti-calendar-check"></i> Hoy
            </button>
            <button class="ta-apply-btn" onclick="filtrarFecha()">
                <i class="ti ti-arrow-right"></i> Ir
            </button>
        </div>
    </div>
</div>

<!-- 2 columnas: Marcaron | Sin Marcar (igual organización que admin) -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;" class="ta-diaria-grid">

    <!-- ── Columna 1: Marcaron Hoy ── -->
    <div style="background:#fff;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden;border:1px solid #e2e8f0;border-top:4px solid #10b981;">
        <div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h3 style="font-size:.95rem;font-weight:800;color:#1e293b;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="ti ti-check" style="color:#10b981;font-size:1.2rem;"></i> Marcaron Hoy
            </h3>
            <span style="background:#dcfce7;color:#15803d;padding:4px 14px;border-radius:20px;font-size:.76rem;font-weight:700;border:1px solid #bbf7d0;">
                <?= count($registros) ?> registros
            </span>
        </div>
        <?php if (empty($registros)): ?>
        <div style="padding:3rem 1.5rem;text-align:center;color:#94a3b8;">
            <i class="ti ti-calendar-off" style="font-size:3rem;display:block;margin-bottom:10px;opacity:.3;"></i>
            <p style="font-size:.9rem;font-weight:600;">Ningún pasante ha marcado aún</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;max-height:420px;overflow-y:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead style="position:sticky;top:0;z-index:5;">
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:12px 18px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;">Pasante</th>
                    <th style="padding:12px 14px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;">Hora</th>
                    <th style="padding:12px 14px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;">Estado</th>
                    <th style="padding:12px 14px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;">Método</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($registros as $r):
                $fullName = ($r->apellidos ?? '') . ', ' . ($r->nombres ?? '');
                $initials = mb_strtoupper(mb_substr($r->apellidos ?? 'P', 0, 1) . mb_substr($r->nombres ?? 'P', 0, 1));
                $est = strtolower($r->estado ?? '');
                if (str_contains($est, 'presente'))    { $estClass='presente';    $estIco='ti-user-check'; }
                elseif (str_contains($est, 'ausente')) { $estClass='ausente';     $estIco='ti-user-x'; }
                else                                   { $estClass='justificado'; $estIco='ti-file-check'; }
                $perfilUrl = URLROOT . '/tutor/perfil/' . ($r->pasante_id ?? 0);
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;" onmouseover="this.style.background='#f8faff'" onmouseout="this.style.background=''">
                <td style="padding:14px 18px;">
                    <a href="<?= $perfilUrl ?>" class="ta-pax-link" title="Ver perfil">
                        <div class="ta-avatar"><?= $initials ?></div>
                        <div>
                            <div class="ta-pax-name"><?= htmlspecialchars($fullName) ?></div>
                            <div class="ta-pax-id">V-<?= htmlspecialchars($r->cedula ?? '') ?></div>
                        </div>
                    </a>
                </td>
                <td style="padding:14px;">
                    <?php if ($r->hora_registro && $r->hora_registro !== '00:00:00'): ?>
                    <span class="ta-hour"><i class="ti ti-clock" style="font-size:.78rem;"></i> <?= date('g:i A', strtotime($r->hora_registro)) ?></span>
                    <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                </td>
                <td style="padding:14px;">
                    <span class="ta-badge <?= $estClass ?>">
                        <i class="ti <?= $estIco ?>" style="font-size:.78rem;"></i>
                        <?= ucfirst($r->estado ?? '') ?>
                    </span>
                </td>
                <td style="padding:14px;">
                    <span class="ta-metodo"><i class="ti ti-fingerprint" style="font-size:.85rem;color:#c4b5fd;"></i> <?= htmlspecialchars($r->metodo ?? '—') ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Columna 2: Sin Marcar ── -->
    <div style="background:#fff;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden;border:1px solid #e2e8f0;border-top:4px solid #ef4444;">
        <div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <h3 style="font-size:.95rem;font-weight:800;color:#1e293b;margin:0;display:flex;align-items:center;gap:8px;">
                <i class="ti ti-alert-circle" style="color:#ef4444;font-size:1.2rem;"></i> Sin Marcar Hoy
            </h3>
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="background:#fee2e2;color:#b91c1c;padding:4px 14px;border-radius:20px;font-size:.76rem;font-weight:700;border:1px solid #fecaca;">
                    <?= $sinMarcarCount ?> pasantes
                </span>
                <?php if ($sinMarcarCount > 0 && (int)date('N') <= 5): ?>
                <button onclick="marcarTodosDiaria()" style="display:flex;align-items:center;gap:5px;padding:5px 14px;border-radius:10px;border:1.5px solid #c4b5fd;background:#f5f3ff;color:#6366f1;font-size:.76rem;font-weight:700;cursor:pointer;transition:all .15s;white-space:nowrap;"
                    onmouseover="this.style.background='linear-gradient(135deg,#6366f1,#4f46e5)';this.style.color='#fff';this.style.borderColor='#6366f1'"
                    onmouseout="this.style.background='#f5f3ff';this.style.color='#6366f1';this.style.borderColor='#c4b5fd'">
                    <i class="ti ti-checks" style="font-size:.9rem;"></i> Marcar todos (Presente)
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php if (empty($sinMarcar)): ?>
        <div style="padding:3rem 1.5rem;text-align:center;">
            <i class="ti ti-circle-check" style="font-size:3rem;color:#10b981;display:block;margin-bottom:10px;opacity:.7;"></i>
            <p style="color:#10b981;font-size:.95rem;font-weight:700;">¡Todos marcaron hoy! 🎉</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;max-height:420px;overflow-y:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead style="position:sticky;top:0;z-index:5;">
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:12px 18px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:left;">Pasante</th>
                    <th style="padding:12px 14px;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#64748b;text-align:center;">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sinMarcar as $sm):
                $fullName = ($sm->apellidos ?? '') . ', ' . ($sm->nombres ?? '');
                $initials = mb_strtoupper(mb_substr($sm->apellidos ?? 'P', 0, 1) . mb_substr($sm->nombres ?? 'P', 0, 1));
                $perfilUrl = URLROOT . '/tutor/perfil/' . ($sm->id ?? 0);
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;" onmouseover="this.style.background='#fff5f5'" onmouseout="this.style.background=''">
                <td style="padding:14px 18px;">
                    <a href="<?= $perfilUrl ?>" class="ta-pax-link" title="Ver perfil">
                        <div class="ta-avatar" style="background:linear-gradient(135deg,#94a3b8,#64748b);"><?= $initials ?></div>
                        <div>
                            <div class="ta-pax-name"><?= htmlspecialchars($fullName) ?></div>
                            <div class="ta-pax-id">V-<?= htmlspecialchars($sm->cedula ?? '') ?></div>
                        </div>
                    </a>
                </td>
                <td style="padding:14px;text-align:center;">
                    <button class="ta-btn-marcar"
                        onclick="abrirRegistroRapido(<?= $sm->id ?>, '<?= htmlspecialchars(($sm->nombres ?? '') . ' ' . ($sm->apellidos ?? '')) ?>')">
                        <i class="ti ti-check"></i> Marcar
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<style>
@media(max-width:700px){ .ta-diaria-grid{ grid-template-columns:1fr !important; } }
</style>

<?php elseif ($esSemanal): ?>
<!-- ══════════════════════════════════
     VISTA SEMANAL
══════════════════════════════════ -->
<div class="ta-panel">
    <div class="ta-panel-header">
        <div class="ta-panel-title">
            <i class="ti ti-calendar-week"></i>
            Resumen Semanal
        </div>
        <div class="ta-week-nav">
            <a href="<?= $navSemana['ant_url'] ?? '#' ?>" class="ta-week-nav-btn">
                <i class="ti ti-chevron-left"></i> Anterior
            </a>
            <span class="ta-week-label">
                <i class="ti ti-calendar-event"></i>
                <?= htmlspecialchars($navSemana['texto'] ?? '') ?>
            </span>
            <a href="<?= $navSemana['sig_url'] ?? '#' ?>" class="ta-week-nav-btn">
                Siguiente <i class="ti ti-chevron-right"></i>
            </a>
        </div>
    </div>
    <div class="ta-panel-body">

    <?php if (empty($datosSemanales)): ?>
        <div class="ta-empty">
            <i class="ti ti-calendar-off"></i>
            <p>Sin datos para esta semana.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($datosSemanales as $depto => $pasantesDepto): ?>
    <div class="ta-dept-group" onclick="taToggle(this)">
        <div class="ta-dept-header">
            <div class="ta-dept-header-left">
                <div class="ta-dept-dot"></div>
                <span class="ta-dept-name"><?= htmlspecialchars($depto) ?></span>
                <span class="ta-dept-count"><?= count($pasantesDepto) ?> pasantes</span>
            </div>
            <i class="ti ti-chevron-down ta-dept-chevron"></i>
        </div>
        <div class="ta-dept-body">
            <table class="ta-heat-tbl">
                <thead>
                    <tr>
                        <th style="min-width:160px;">Pasante</th>
                        <th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th>
                        <th><span style="color:#059669;">P</span></th>
                        <th><span style="color:#dc2626;">A</span></th>
                        <th><span style="color:#b45309;">J</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pasantesDepto as $pid => $pd): ?>
                    <tr>
                        <td>
                            <a href="<?= URLROOT ?>/tutor/perfil/<?= (int)$pid ?>"
                               style="color:#1e293b; text-decoration:none; font-weight:500;"
                               onmouseover="this.style.color='#6366f1'"
                               onmouseout="this.style.color='#1e293b'">
                                <?= htmlspecialchars($pd['nombre']) ?>
                            </a>
                        </td>
                        <?php for ($d=1; $d<=5; $d++):
                            $letra  = $pd['dias'][$d] ?? '-';
                            $motivo = $pd['motivos'][$d] ?? '';
                            if ($letra === 'P')      $cls = 'hc hc-p';
                            elseif ($letra === 'A')  $cls = 'hc hc-a';
                            elseif ($letra === 'J')  $cls = 'hc hc-j';
                            else                     $cls = 'hc hc-x';
                            $tip = ['P'=>'Presente','A'=>'Ausente','J'=>'Justificado'][$letra] ?? 'Sin datos';
                            if ($letra === 'J' && !empty(trim($motivo))) $tip = 'Justificado: ' . htmlspecialchars(trim($motivo));
                        ?>
                        <td><span class="<?= $cls ?>" title="<?= $tip ?>"><?= htmlspecialchars($letra) ?></span></td>
                        <?php endfor; ?>
                        <td class="hc-total-p"><?= $pd['totales']['P'] ?></td>
                        <td class="hc-total-a"><?= $pd['totales']['A'] ?></td>
                        <td class="hc-total-j"><?= $pd['totales']['J'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    </div>
</div>
<?php endif; ?>

<?php if ($esMensual): ?>
<!-- ══════════════════════════════════
     VISTA MENSUAL
══════════════════════════════════ -->
<div class="ta-panel">
    <div class="ta-panel-header">
        <div class="ta-panel-title">
            <i class="ti ti-calendar-month"></i>
            Resumen Mensual
            <span class="ta-count-chip"><i class="ti ti-calendar"></i> <?= $nombreMes ?> <?= $paramsUrl['anio'] ?></span>
        </div>
        <div class="ta-toolbar">
            <a href="<?= $urlMesAnt ?>" class="ta-nav-arrow" title="Mes anterior" style="width:34px;height:38px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#475569;text-decoration:none;">
                <i class="ti ti-chevron-left"></i>
            </a>
            <span style="font-size:.88rem;font-weight:600;color:#1e293b;padding:0 6px;"><?= $nombreMes ?> <?= $paramsUrl['anio'] ?></span>
            <a href="<?= $urlMesSig ?>" class="ta-nav-arrow" title="Mes siguiente" style="width:34px;height:38px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;color:#475569;text-decoration:none;">
                <i class="ti ti-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Chart.js semanal del mes -->
    <div style="padding:18px 24px 0;">
        <div style="display:grid;grid-template-columns:1fr auto;align-items:center;gap:12px;margin-bottom:10px;">
            <div style="font-size:.8rem;color:#64748b;">Asistencia por semana del mes</div>
            <div style="background:<?= $healthIndex >= 80 ? '#dcfce7' : ($healthIndex >= 60 ? '#fef3c7' : '#fee2e2') ?>;color:<?= $healthIndex >= 80 ? '#15803d' : ($healthIndex >= 60 ? '#b45309' : '#b91c1c') ?>;padding:4px 12px;border-radius:20px;font-size:.8rem;font-weight:700;">
                <?= $healthIndex ?>% global
            </div>
        </div>
        <div style="position:relative;height:140px;">
            <canvas id="chartMensual"></canvas>
        </div>
    </div>

    <!-- Tabla por pasante -->
    <div style="padding:18px 0 0;">
        <?php if (empty($bentoMensual)): ?>
        <div style="text-align:center;padding:2.5rem;color:#94a3b8;">
            <i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;"></i>
            <p>Sin registros en <?= $nombreMes ?>.</p>
        </div>
        <?php else: ?>
        <?php foreach ($bentoMensual as $depto => $pasantes): ?>
        <div style="padding:0 24px 16px;">
            <div style="font-size:.75rem;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <i class="ti ti-building"></i> <?= htmlspecialchars($depto) ?>
                <span style="background:#ede9fe;color:#6366f1;padding:2px 8px;border-radius:12px;"><?= count($pasantes) ?></span>
            </div>
            <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:500px;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;text-align:left;">Pasante</th>
                        <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#15803d;text-align:center;">P</th>
                        <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b91c1c;text-align:center;">A</th>
                        <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#b45309;text-align:center;">J</th>
                        <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;text-align:center;">%</th>
                        <th style="padding:.6rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;text-align:left;">Tendencia</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pasantes as $rm):
                    $total = $rm['presentes'] + $rm['ausentes'] + $rm['justificados'];
                    $pct   = $total > 0 ? round(($rm['presentes'] + $rm['justificados']) / $total * 100) : 0;
                    $barColor = $pct >= 80 ? '#10b981' : ($pct >= 60 ? '#f59e0b' : '#ef4444');
                    $ini  = htmlspecialchars($rm['iniciales']);
                ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:.65rem 1rem;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#fff;flex-shrink:0;"><?= $ini ?></div>
                            <div>
                                <div style="font-size:.84rem;font-weight:600;color:#1e293b;"><?= htmlspecialchars($rm['nombre']) ?></div>
                                <div style="font-size:.73rem;color:#94a3b8;">V-<?= htmlspecialchars($rm['cedula']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="text-align:center;font-weight:700;color:#15803d;"><?= $rm['presentes'] ?></td>
                    <td style="text-align:center;font-weight:700;color:#b91c1c;"><?= $rm['ausentes'] ?></td>
                    <td style="text-align:center;font-weight:700;color:#b45309;"><?= $rm['justificados'] ?></td>
                    <td style="text-align:center;font-weight:800;color:<?= $barColor ?>;"><?= $pct ?>%</td>
                    <td style="padding:.65rem 1rem;min-width:100px;">
                        <div style="height:6px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
                            <div style="height:100%;width:<?= $pct ?>%;background:<?= $barColor ?>;border-radius:99px;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function(){
    const labels = <?= $chartSemanasJson ?>;
    const pcts   = <?= $chartPctsJson ?>;
    const bgC    = pcts.map(v => v >= 80 ? '#10b981cc' : (v >= 60 ? '#f59e0bcc' : '#ef4444cc'));
    const bdC    = pcts.map(v => v >= 80 ? '#10b981'   : (v >= 60 ? '#f59e0b'   : '#ef4444'));
    function init(){
        if(typeof Chart==='undefined'){ setTimeout(init,100); return; }
        const ctx = document.getElementById('chartMensual');
        if(!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { type:'bar',  label:'Asistencia %', data:pcts, backgroundColor:bgC, borderColor:bdC, borderWidth:2, borderRadius:8, order:2 },
                    { type:'line', label:'Tendencia',     data:pcts, borderColor:'#6366f1', borderWidth:2.5, pointBackgroundColor:'#6366f1', pointRadius:5, tension:0.35, fill:false, order:1 }
                ]
            },
            options: {
                responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: c=>' '+c.parsed.y+'%' }, backgroundColor:'#1e293b', bodyColor:'#fff', cornerRadius:8, padding:10 } },
                scales:{
                    y:{ min:0, max:100, ticks:{ callback:v=>v+'%', color:'#94a3b8', stepSize:25, font:{size:11} }, grid:{ color:'#f1f5f9', drawBorder:false } },
                    x:{ ticks:{ color:'#64748b', font:{size:11} }, grid:{display:false} }
                }
            }
        });
    }
    init();
})();
</script>
<?php endif; ?>

<?php if ($esTotal): ?>
<!-- ══════════════════════════════════
     VISTA TOTAL — Muro de Pasantes + Navegación de Períodos
══════════════════════════════════ -->
<?php
$periodosFiltro      = $periodosFiltro      ?? [];
$periodoSeleccionado = $periodoSeleccionado ?? null;
$periodoIdSel        = $periodoIdSel        ?? 0;
$tituloVista         = $periodoSeleccionado->nombre ?? 'Todos los Períodos';

$idxActual = -1;
foreach ($periodosFiltro as $i => $pf) {
    if ($pf->id == $periodoIdSel) { $idxActual = $i; break; }
}
$urlAnt = '#'; $urlSig = '#';
if ($idxActual !== -1) {
    if (isset($periodosFiltro[$idxActual + 1])) $urlAnt = URLROOT.'/tutor/asistencias?vista=total&periodo_id='.$periodosFiltro[$idxActual + 1]->id;
    if (isset($periodosFiltro[$idxActual - 1])) $urlSig = URLROOT.'/tutor/asistencias?vista=total&periodo_id='.$periodosFiltro[$idxActual - 1]->id;
}
?>
<style>
.ta-total-nav { display:flex;align-items:center;gap:12px;flex-wrap:wrap; }
.ta-total-nav-btn { display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#475569;text-decoration:none;transition:all .15s; }
.ta-total-nav-btn:hover { border-color:#6366f1;color:#6366f1;background:#f5f3ff; }
.ta-total-nav-btn.disabled { opacity:.3;pointer-events:none; }
.ta-total-period-name { font-size:.92rem;font-weight:700;color:#1e293b; }
.ta-card-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;padding:20px 24px; }
.ta-pcard {
    background:#fff;border-radius:18px;border:1px solid #e2e8f0;padding:20px;
    display:flex;flex-direction:column;align-items:center;gap:14px;
    box-shadow:0 2px 12px rgba(0,0,0,.05);transition:all .2s;position:relative;overflow:hidden;
}
.ta-pcard:hover { transform:translateY(-4px);box-shadow:0 12px 32px rgba(99,102,241,.15);border-color:#c4b5fd; }
.ta-pcard-avatar {
    width:50px;height:50px;border-radius:14px;
    background:linear-gradient(135deg,#6366f1,#4f46e5);
    display:flex;align-items:center;justify-content:center;
    font-size:.95rem;font-weight:800;color:#fff;
    box-shadow:0 4px 12px rgba(99,102,241,.3);
}
.ta-pcard-name { font-size:.88rem;font-weight:700;color:#1e293b;text-align:center;line-height:1.3; }
.ta-pcard-ci { font-size:.75rem;color:#94a3b8;font-weight:500; }
.ta-pcard-badge {
    display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;
    font-size:.7rem;font-weight:700;
}
.ta-pcard-badge.activo { background:#dcfce7;color:#15803d;border:1px solid #bbf7d0; }
.ta-pcard-badge.finalizado { background:#ede9fe;color:#6366f1;border:1px solid #c4b5fd; }
.ta-pcard-gauge { width:100px;height:100px; }
.ta-pcard-gauge-bg { fill:none;stroke:#f1f5f9;stroke-width:8; }
.ta-pcard-gauge-fg { fill:none;stroke-width:8;stroke-linecap:round;transform:rotate(-90deg);transform-origin:50% 50%;transition:stroke-dashoffset .8s ease; }
.ta-pcard-gauge-pct { font-size:14px;font-weight:900;fill:#1e293b; }
.ta-pcard-gauge-hrs { font-size:8px;font-weight:600;fill:#64748b; }
.ta-pcard-stats { display:flex;gap:12px;font-size:.75rem;font-weight:700; }
.ta-pcard-stat-p { color:#10b981; }
.ta-pcard-stat-a { color:#ef4444; }
.ta-pcard-stat-j { color:#f59e0b; }
.ta-pcard-actions {
    display:flex;gap:6px;opacity:0;transition:opacity .2s;position:absolute;top:10px;right:10px;
}
.ta-pcard:hover .ta-pcard-actions { opacity:1; }
.ta-pcard-btn {
    width:30px;height:30px;border-radius:8px;border:none;cursor:pointer;
    display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .15s;
}
.ta-pcard-btn.pdf { background:#fee2e2;color:#dc2626; }
.ta-pcard-btn.pdf:hover { background:#dc2626;color:#fff; }
.ta-pcard-btn.xls { background:#dcfce7;color:#15803d; }
.ta-pcard-btn.xls:hover { background:#15803d;color:#fff; }
.ta-search-total {
    padding:.5rem 1rem .5rem 2.2rem;border:1.5px solid #e2e8f0;border-radius:10px;
    background:#f8fafc;font-size:.85rem;color:#1e293b;width:220px;transition:all .15s;
}
.ta-search-total:focus { outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.1);background:#fff; }
.ta-chips { display:flex;gap:6px;flex-wrap:wrap; }
.ta-chip {
    padding:5px 14px;border-radius:20px;border:1.5px solid #e2e8f0;background:#fff;
    font-size:.78rem;font-weight:600;color:#475569;cursor:pointer;transition:all .15s;
}
.ta-chip:hover,.ta-chip.active { background:#6366f1;color:#fff;border-color:#6366f1; }
</style>

<div class="ta-panel">
    <div class="ta-panel-header">
        <div class="ta-panel-title">
            <i class="ti ti-chart-bar"></i>
            Histórico Total
            <span class="ta-count-chip"><i class="ti ti-users"></i> <?= count($resumenTotal) ?> pasantes</span>
        </div>
        <div class="ta-total-nav">
            <a href="<?= $urlAnt ?>" class="ta-total-nav-btn <?= $urlAnt === '#' ? 'disabled' : '' ?>" title="Período anterior">
                <i class="ti ti-chevron-left"></i>
            </a>
            <span class="ta-total-period-name"><i class="ti ti-calendar" style="color:#6366f1;margin-right:4px;"></i> <?= htmlspecialchars($tituloVista) ?></span>
            <a href="<?= $urlSig ?>" class="ta-total-nav-btn <?= $urlSig === '#' ? 'disabled' : '' ?>" title="Período siguiente">
                <i class="ti ti-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Barra de filtros -->
    <div style="padding:14px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div class="ta-chips">
            <button class="ta-chip active" onclick="filterCards('all',this)">Todos</button>
            <button class="ta-chip" onclick="filterCards('Activo',this)">Activos</button>
            <button class="ta-chip" onclick="filterCards('Finalizado',this)">Finalizados</button>
        </div>
        <div style="position:relative;">
            <i class="ti ti-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.9rem;"></i>
            <input type="text" class="ta-search-total" placeholder="Buscar pasante..." oninput="searchCards(this.value)">
        </div>
    </div>

    <?php if (empty($resumenTotal)): ?>
    <div style="text-align:center;padding:3rem;color:#94a3b8;">
        <i class="ti ti-database-off" style="font-size:2.5rem;display:block;margin-bottom:10px;opacity:.3;"></i>
        <p>Sin registros de asistencia en este período.</p>
    </div>
    <?php else: ?>
    <div class="ta-card-grid" id="taCardGrid">
        <?php foreach ($resumenTotal as $rm):
            $horasCalc = ($rm['presentes'] + $rm['ausentes'] + $rm['justificados']) * 8;
            $meta      = $rm['horas_meta'] ?? 1440;
            $pctHoras  = $meta > 0 ? min(100, round(($horasCalc / $meta) * 100)) : 0;
            $circum    = 2 * 3.14159 * 42;
            $offset    = $circum - ($pctHoras / 100) * $circum;
            $strokeC   = $pctHoras >= 80 ? '#10b981' : ($pctHoras >= 50 ? '#f59e0b' : '#6366f1');
            $estado    = $rm['estado_pasantia'] ?? 'Activo';
            $estadoCls = strtolower($estado) === 'finalizado' ? 'finalizado' : 'activo';
        ?>
        <div class="ta-pcard" data-search="<?= htmlspecialchars($rm['nombre'].' '.$rm['cedula']) ?>" data-estado="<?= htmlspecialchars($estado) ?>">
            <div class="ta-pcard-actions">
                <button class="ta-pcard-btn pdf" onclick="window.open('<?= URLROOT ?>/reportes/pdfIndividual?id=<?= $rm['pasante_id'] ?>&download=0','_blank')" title="PDF">
                    <i class="ti ti-file-type-pdf"></i>
                </button>
                <button class="ta-pcard-btn xls" onclick="window.location.href='<?= URLROOT ?>/reportes/exportarExcel?id=<?= $rm['pasante_id'] ?>'" title="Excel">
                    <i class="ti ti-file-spreadsheet"></i>
                </button>
            </div>
            <div class="ta-pcard-avatar"><?= htmlspecialchars($rm['iniciales']) ?></div>
            <div>
                <a href="<?= URLROOT ?>/tutor/perfil/<?= $rm['pasante_id'] ?>" class="ta-pcard-name" style="text-decoration:none;color:#1e293b;" onmouseover="this.style.color='#6366f1'" onmouseout="this.style.color='#1e293b'">
                    <?= htmlspecialchars($rm['nombre']) ?>
                </a>
                <div class="ta-pcard-ci">V-<?= htmlspecialchars($rm['cedula']) ?></div>
            </div>
            <span class="ta-pcard-badge <?= $estadoCls ?>"><?= htmlspecialchars($estado) ?></span>
            <svg class="ta-pcard-gauge" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="42" class="ta-pcard-gauge-bg"></circle>
                <circle cx="50" cy="50" r="42" class="ta-pcard-gauge-fg" stroke="<?= $strokeC ?>" stroke-dasharray="<?= round($circum) ?>" stroke-dashoffset="<?= round($offset) ?>"></circle>
                <text x="50" y="46" text-anchor="middle" class="ta-pcard-gauge-pct"><?= $pctHoras ?>%</text>
                <text x="50" y="60" text-anchor="middle" class="ta-pcard-gauge-hrs"><?= number_format($horasCalc) ?> / <?= number_format($meta) ?> hrs</text>
            </svg>
            <div class="ta-pcard-stats">
                <span class="ta-pcard-stat-p"><i class="ti ti-check" style="font-size:.8rem;"></i> <?= $rm['presentes'] ?>P</span>
                <span class="ta-pcard-stat-a"><i class="ti ti-x" style="font-size:.8rem;"></i> <?= $rm['ausentes'] ?>A</span>
                <span class="ta-pcard-stat-j"><i class="ti ti-file-check" style="font-size:.8rem;"></i> <?= $rm['justificados'] ?>J</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function filterCards(estado, btn) {
    document.querySelectorAll('.ta-chips .ta-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.ta-pcard').forEach(card => {
        if (estado === 'all' || card.dataset.estado === estado) card.style.display = '';
        else card.style.display = 'none';
    });
}
function searchCards(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.ta-pcard').forEach(card => {
        card.style.display = card.dataset.search.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
<?php endif; ?>

</div><!-- /.ta-wrap -->

<!-- ══════════════════════════════════
     MODAL REGISTRO MANUAL
══════════════════════════════════ -->
<div class="ta-modal-overlay" id="modalManual">
    <div class="ta-modal-box">
        <div class="ta-modal-head">
            <div class="ta-modal-head-title">
                <div class="ta-modal-head-icon"><i class="ti ti-clipboard-plus"></i></div>
                Registrar Asistencia Manual
            </div>
            <button class="ta-modal-close" onclick="document.getElementById('modalManual').classList.remove('open')">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="ta-modal-body">
            <div class="ta-field">
                <label><i class="ti ti-user" style="color:#6366f1;"></i> Pasante</label>
                <select id="selectPasante">
                    <option value="">Seleccionar pasante…</option>
                    <?php foreach ($misPasantes as $p): ?>
                    <option value="<?= $p->id ?>"><?= htmlspecialchars(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')) ?> — V-<?= $p->cedula ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ta-field">
                <label><i class="ti ti-calendar" style="color:#6366f1;"></i> Fecha</label>
                <input type="date" id="manualFecha" value="<?= $hoy ?>" max="<?= $hoy ?>">
            </div>
            <div class="ta-field">
                <label><i class="ti ti-toggle-left" style="color:#6366f1;"></i> Estado</label>
                <select id="manualEstado" onchange="toggleMotivo()">
                    <option value="Presente">✅ Presente</option>
                    <option value="Ausente">❌ Ausente</option>
                    <option value="Justificado">📋 Justificado</option>
                </select>
            </div>
            <div class="ta-field" id="motivoRow" style="display:none;">
                <label><i class="ti ti-notes" style="color:#6366f1;"></i> Motivo de Justificación</label>
                <textarea id="manualMotivo" placeholder="Ej: Cita médica, diligencia personal…"></textarea>
            </div>
        </div>
        <div class="ta-modal-footer">
            <button class="ta-btn-sec" onclick="document.getElementById('modalManual').classList.remove('open')">
                <i class="ti ti-x"></i> Cancelar
            </button>
            <button class="ta-btn-pri" id="btnGuardarManual" onclick="guardarManual()">
                <i class="ti ti-check"></i> Guardar Registro
            </button>
        </div>
    </div>
</div>

<script>
/* ── Date Navigator ── — [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta sin reload completo. */
var _DIAS   = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
var _MESES  = ['Enero','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

function _parseFecha(str) {
    const [y,m,d] = str.split('-').map(Number);
    return new Date(y, m-1, d);
}
function _formatFecha(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth()+1).padStart(2,'0');
    const d = String(date.getDate()).padStart(2,'0');
    return `${y}-${m}-${d}`;
}
function sincronizarFecha() {
    const val = document.getElementById('filtroFecha').value;
    if (!val) return;
    const d = _parseFecha(val);
    document.getElementById('fechaDiaNombre').textContent = _DIAS[d.getDay()];
    document.getElementById('fechaTexto').textContent =
        `${String(d.getDate()).padStart(2,'0')} ${_MESES[d.getMonth()]} ${d.getFullYear()}`;
}
function moverFecha(delta) {
    const inp = document.getElementById('filtroFecha');
    const hoy = inp.max || new Date().toISOString().slice(0,10);
    const d   = _parseFecha(inp.value || hoy);
    d.setDate(d.getDate() + delta);
    const nueva = _formatFecha(d);
    if (nueva > hoy) return; // no avanzar más allá de hoy
    inp.value = nueva;
    sincronizarFecha();
}
function irHoy() {
    const inp = document.getElementById('filtroFecha');
    inp.value = inp.max;
    sincronizarFecha();
}
function filtrarFecha() {
    const f = document.getElementById('filtroFecha').value;
    if (f) window.location.href = `${URLROOT}/tutor/asistencias?vista=diaria&fecha=${f}`;
}
// Inicializar display al cargar
document.addEventListener('DOMContentLoaded', sincronizarFecha);
function toggleMotivo() {
    const e = document.getElementById('manualEstado').value;
    document.getElementById('motivoRow').style.display = e === 'Justificado' ? 'block' : 'none';
}
function abrirRegistroRapido(pid, nombre) {
    document.getElementById('selectPasante').value = pid;
    document.getElementById('modalManual').classList.add('open');
}
async function guardarManual() {
    const pid    = document.getElementById('selectPasante').value;
    const fecha  = document.getElementById('manualFecha').value;
    const estado = document.getElementById('manualEstado').value;
    const motivo = document.getElementById('manualMotivo').value.trim();
    if (!pid)   return Swal.fire('', 'Selecciona un pasante.', 'warning');
    if (!fecha) return Swal.fire('', 'Selecciona una fecha.', 'warning');
    if (estado === 'Justificado' && !motivo) return Swal.fire('', 'El motivo es obligatorio.', 'warning');
    const btn  = document.getElementById('btnGuardarManual');
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin .8s linear infinite;"></i> Guardando…';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const fd   = new FormData();
    fd.append('pasante_id', pid);
    fd.append('fecha', fecha);
    fd.append('estado', estado);
    fd.append('motivo_justificacion', motivo);
    fd.append('csrf_token', csrf);
    try {
        const res  = await fetch(`${URLROOT}/asistencias/registro_manual`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf}, body:fd });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon:'success', title:'Registrado', text:data.message, confirmButtonColor:'#10b981' }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch(e) { Swal.fire('Error', 'Error de red.', 'error'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check"></i> Guardar Registro';
    }
}
document.getElementById('modalManual').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});
function taToggle(el) {
    el.classList.toggle('collapsed');
}
/* Spin animation for loader */
const _ss = document.createElement('style');
_ss.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
document.head.appendChild(_ss);

/* ── Marcar todos (Presente) ── */
window.marcarTodosDiaria = async function() {
    const pasantes = <?= json_encode(array_values(array_map(function($p){
        return ['id'=>$p->id, 'nombre'=>trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''))];
    }, $sinMarcar ?? []))) ?>;

    if (!pasantes || !pasantes.length) return;

    const { isConfirmed } = await Swal.fire({
        title: `¿Marcar ${pasantes.length} pasantes como Presente?`,
        text: 'Se registrará asistencia como Presente para hoy.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, marcar todos',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#64748b'
    });
    if (!isConfirmed) return;

    Swal.fire({ title: 'Procesando...', html: '<span id="ta-prog">0</span> de ' + pasantes.length, allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    let ok = 0, err = 0;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    for (let i = 0; i < pasantes.length; i++) {
        const fd = new FormData();
        fd.append('pasante_id', pasantes[i].id);
        fd.append('fecha', '<?= $hoy ?>');
        fd.append('estado', 'Presente');
        fd.append('observacion', 'Marcado masivo (Tutor)');
        fd.append('csrf_token', csrf);
        try {
            const r = await fetch(`${URLROOT}/asistencias/registro_manual`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf}, body:fd });
            const d = await r.json();
            d.success ? ok++ : err++;
        } catch(e) { err++; }
        const prog = document.getElementById('ta-prog');
        if (prog) prog.textContent = ok + err;
    }

    Swal.fire({
        icon: err > 0 ? 'warning' : 'success',
        title: 'Proceso completado',
        text: `${ok} marcados correctamente` + (err > 0 ? `, ${err} con error` : ''),
        confirmButtonColor: '#6366f1'
    }).then(() => location.reload());
};
</script>

<!-- ══════════════════════════════════
     MODAL CONSULTA RÁPIDA
══════════════════════════════════ -->
<div class="ta-modal-overlay" id="modalConsulta">
    <div class="ta-modal-box" style="max-width:560px;">
        <div class="ta-modal-head">
            <div class="ta-modal-head-title">
                <div class="ta-modal-head-icon"><i class="ti ti-user-search"></i></div>
                <div>
                    <span style="font-size:1.05rem;">Consulta Rápida</span>
                    <div style="font-size:.72rem;color:rgba(255,255,255,.6);margin-top:2px;">Buscar por nombre o cédula</div>
                </div>
            </div>
            <button class="ta-modal-close" onclick="document.getElementById('modalConsulta').classList.remove('open')">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="ta-modal-body" style="padding:16px 24px;">
            <!-- Buscador -->
            <div style="position:relative;margin-bottom:12px;">
                <i class="ti ti-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:1rem;"></i>
                <input type="text" id="cqBuscar" placeholder="Escriba al menos 2 caracteres..." autocomplete="off"
                    style="width:100%;padding:.6rem 1rem .6rem 2.6rem;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;font-size:.88rem;color:#1e293b;transition:all .15s;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#6366f1';this.style.boxShadow='0 0 0 3px rgba(99,102,241,.1)'"
                    onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
            </div>
            <!-- Lista de sugerencias -->
            <div id="cqSugerencias" style="display:none;max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:12px;"></div>
            <!-- Estado vacío -->
            <div id="cqVacio" style="text-align:center;padding:20px 0;color:#94a3b8;">
                <i class="ti ti-users-group" style="font-size:2rem;display:block;margin-bottom:6px;opacity:.4;"></i>
                <p style="font-size:.82rem;margin:0;">Escriba para buscar un pasante</p>
            </div>
            <!-- Zona de resultado -->
            <div id="cqResultado" style="display:none;">
                <!-- Cápsula perfil -->
                <div style="display:flex;align-items:center;gap:14px;padding:14px;background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;margin-bottom:14px;">
                    <div id="cqAvatar" style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:#fff;flex-shrink:0;"></div>
                    <div style="flex:1;min-width:0;">
                        <div id="cqNombre" style="font-size:.92rem;font-weight:700;color:#1e293b;"></div>
                        <div id="cqCedula" style="font-size:.78rem;color:#94a3b8;"></div>
                        <div id="cqDepto" style="font-size:.75rem;color:#6366f1;margin-top:2px;"></div>
                    </div>
                </div>
                <!-- KPIs rápidos -->
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:14px;">
                    <div style="text-align:center;padding:10px 6px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;">
                        <div id="cqKpiP" style="font-size:1.3rem;font-weight:900;color:#15803d;">0</div>
                        <div style="font-size:.65rem;font-weight:700;color:#15803d;text-transform:uppercase;">Presentes</div>
                    </div>
                    <div style="text-align:center;padding:10px 6px;background:#fef2f2;border-radius:10px;border:1px solid #fecaca;">
                        <div id="cqKpiA" style="font-size:1.3rem;font-weight:900;color:#dc2626;">0</div>
                        <div style="font-size:.65rem;font-weight:700;color:#dc2626;text-transform:uppercase;">Ausentes</div>
                    </div>
                    <div style="text-align:center;padding:10px 6px;background:#fffbeb;border-radius:10px;border:1px solid #fde68a;">
                        <div id="cqKpiJ" style="font-size:1.3rem;font-weight:900;color:#b45309;">0</div>
                        <div style="font-size:.65rem;font-weight:700;color:#b45309;text-transform:uppercase;">Justificados</div>
                    </div>
                    <div style="text-align:center;padding:10px 6px;background:#f5f3ff;border-radius:10px;border:1px solid #c4b5fd;">
                        <div id="cqKpiPct" style="font-size:1.3rem;font-weight:900;color:#6366f1;">0%</div>
                        <div style="font-size:.65rem;font-weight:700;color:#6366f1;text-transform:uppercase;">Asistencia</div>
                    </div>
                </div>
                <!-- Progreso radial + info -->
                <div style="display:flex;gap:16px;align-items:center;padding:14px;background:#f8fafc;border-radius:14px;border:1px solid #e2e8f0;">
                    <div id="cqRadial" style="width:110px;height:110px;flex-shrink:0;"></div>
                    <div style="flex:1;">
                        <div style="font-size:.78rem;font-weight:700;color:#475569;margin-bottom:8px;">Progreso de Horas</div>
                        <div id="cqHorasInfo" style="font-size:.82rem;color:#64748b;line-height:1.6;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function(){
    const inp = document.getElementById('cqBuscar');
    if (!inp) return;
    const lista = document.getElementById('cqSugerencias');
    const vacio = document.getElementById('cqVacio');
    const resultado = document.getElementById('cqResultado');
    let timer = null;
    let chartRadial = null;

    document.getElementById('modalConsulta').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });

    inp.addEventListener('input', function() {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { lista.style.display = 'none'; return; }
        timer = setTimeout(() => {
            const fd = new FormData();
            fd.append('query', q);
            fetch(`${URLROOT}/asistencias/buscarPasanteAjax`, { method:'POST', body:fd })
                .then(r => r.json())
                .then(data => {
                    lista.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(p => {
                            const d = document.createElement('div');
                            d.style.cssText = 'padding:10px 14px;cursor:pointer;display:flex;align-items:center;gap:10px;border-bottom:1px solid #f1f5f9;transition:background .1s;';
                            d.onmouseover = () => d.style.background='#f5f3ff';
                            d.onmouseout = () => d.style.background='';
                            const ini = ((p.apellidos||'P')[0] + (p.nombres||'P')[0]).toUpperCase();
                            const esc = s => { const t = document.createElement('span'); t.textContent = s; return t.innerHTML; };
                            d.innerHTML = `<div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0;">${esc(ini)}</div><div><div style="font-size:.84rem;font-weight:600;color:#1e293b;">${esc(p.apellidos||'')}, ${esc(p.nombres||'')}</div><div style="font-size:.72rem;color:#94a3b8;">V-${esc(p.cedula||'')} · ${esc(p.departamento||'')}</div></div>`;
                            d.onclick = () => cargarAuditoria(p.pasante_id, p);
                            lista.appendChild(d);
                        });
                        lista.style.display = 'block';
                    } else {
                        lista.innerHTML = '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:.82rem;">Sin resultados</div>';
                        lista.style.display = 'block';
                    }
                });
        }, 300);
    });

    function cargarAuditoria(id, datosBasicos) {
        lista.style.display = 'none';
        vacio.style.display = 'none';
        resultado.style.display = 'none';
        inp.value = `${datosBasicos.apellidos||''}, ${datosBasicos.nombres||''}`;

        const fd = new FormData();
        fd.append('id_pasante', id);
        fetch(`${URLROOT}/asistencias/obtenerDatosAuditoriaAjax`, { method:'POST', body:fd })
            .then(r => r.json())
            .then(data => {
                if (data.status !== 'success') return;
                const p = data.perfil;
                const pr = data.pro_rata;
                const cal = data.calendario;
                const ini = ((p.apellidos||'P')[0] + (p.nombres||'P')[0]).toUpperCase();

                document.getElementById('cqAvatar').textContent = ini;
                document.getElementById('cqNombre').textContent = `${p.nombres} ${p.apellidos}`;
                document.getElementById('cqCedula').textContent = `V-${p.cedula}`;
                const cqDeptoEl = document.getElementById('cqDepto');
                cqDeptoEl.textContent = '';
                const iDepto = document.createElement('i');
                iDepto.className = 'ti ti-building';
                iDepto.style.fontSize = '.8rem';
                cqDeptoEl.appendChild(iDepto);
                cqDeptoEl.appendChild(document.createTextNode(' ' + (p.departamento || '—')));

                document.getElementById('cqKpiP').textContent = pr.dias_presentes || 0;
                const totalDias = (pr.dias_presentes||0) + (data.historial ? data.historial.filter(h=>h.estado==='Ausente').length : 0);
                const ausentes = data.historial ? data.historial.filter(h=>h.estado==='Ausente').length : 0;
                const justificados = data.historial ? data.historial.filter(h=>h.estado==='Justificado').length : 0;
                document.getElementById('cqKpiA').textContent = ausentes;
                document.getElementById('cqKpiJ').textContent = justificados;
                const pctAsis = totalDias > 0 ? Math.round(((pr.dias_presentes||0) + justificados) / (totalDias + justificados) * 100) : 0;
                document.getElementById('cqKpiPct').textContent = (pr.porcentaje || pctAsis) + '%';

                const pctCal = cal ? Math.round(cal.porcentaje_calendario) : 0;
                const hCal = cal ? cal.horas_calendario : 0;
                const dTrans = cal ? cal.dias_habiles_transcurridos : 0;
                const dRest = cal ? cal.dias_habiles_restantes : 0;
                document.getElementById('cqHorasInfo').innerHTML =
                    `<div><strong>${pr.horas_mostradas || 0}</strong> / <strong>${pr.horas_meta || 1440}</strong> horas</div>` +
                    `<div>${dTrans} días hábiles transcurridos</div>` +
                    `<div>${dRest} días hábiles restantes</div>`;

                if (chartRadial) chartRadial.destroy();
                const color = pctCal >= 80 ? '#10b981' : (pctCal >= 50 ? '#f59e0b' : '#6366f1');
                chartRadial = new ApexCharts(document.getElementById('cqRadial'), {
                    series: [pctCal],
                    chart: { height: 110, type: 'radialBar', sparkline: { enabled: true } },
                    plotOptions: {
                        radialBar: {
                            hollow: { size: '55%' },
                            track: { background: '#f1f5f9' },
                            dataLabels: { value: { show:true, fontSize:'1rem', fontWeight:800, color:'#1e293b', formatter: v => v+'%' } }
                        }
                    },
                    colors: [color]
                });
                chartRadial.render();

                resultado.style.display = 'block';
            });
    }
})();
</script>
