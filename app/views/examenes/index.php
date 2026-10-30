<?php
/**
 * Vista: Exámenes — Dashboard Analytics + Modal Builder
 * URL: /examenes
 * Variables: $examenes[], $periodos[], $totalActivos, $totalEvaluados,
 *            $avgScore, $tasaAprobacion, $dist, $top5[], $examenesActivos[], $recientes[]
 */

$examenes        = $data['examenes']        ?? [];
$periodos        = $data['periodos']        ?? [];
$totalActivos    = $data['totalActivos']    ?? 0;
$totalEvaluados  = $data['totalEvaluados']  ?? 0;
$avgScore        = $data['avgScore']        ?? 0;
$tasaAprobacion  = $data['tasaAprobacion']  ?? 0;
$dist            = $data['dist']            ?? null;
$top5            = $data['top5']            ?? [];
$examenesActivos = $data['examenesActivos'] ?? [];
$recientes       = $data['recientes']       ?? [];
$totalExamenes   = count($examenes);
$aprobados       = (int)($dist->r60 ?? 0) + (int)($dist->r80 ?? 0);
$reprobados      = (int)($dist->r0  ?? 0) + (int)($dist->r20 ?? 0) + (int)($dist->r40 ?? 0);
$avgColor        = (float)$avgScore  >= 60 ? '#059669' : '#dc2626';
$tasaColor       = (float)$tasaAprobacion >= 60 ? '#059669' : '#dc2626';
?>
<style>
@keyframes ex-fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

.ex-wrap{padding-bottom:56px;animation:ex-fadeUp .45s ease both}

/* ── Hero ─────────────────────────────────────────────── */
.ex-hero{
    background: linear-gradient(135deg, var(--color-primary, #172554) 0%, var(--gradient-mid, #1e3a8a) 50%, var(--color-accent-brand, #2563eb) 100%) !important;
    border-radius:20px;padding:32px 36px;margin-bottom:24px;color:#fff;
    position:relative;overflow:hidden;box-shadow:0 8px 32px rgba(15,23,42,.35);
}
.ex-hero::before{content:'';position:absolute;top:-60px;right:-60px;width:320px;height:320px;
    background:radial-gradient(circle,rgba(255,255,255,.12) 0%,rgba(255,255,255,0) 70%);
    border-radius:50%;pointer-events:none}
.ex-hero::after{content:'';position:absolute;bottom:-80px;left:30%;width:200px;height:200px;
    background:radial-gradient(circle,rgba(255,255,255,.07) 0%,rgba(255,255,255,0) 70%);
    border-radius:50%;pointer-events:none}
.ex-hero-inner{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.ex-hero-left{display:flex;align-items:center;gap:20px;min-width:0}
.ex-hero-icon{width:60px;height:60px;border-radius:16px;background:rgba(255,255,255,.18);backdrop-filter:blur(10px);
    display:flex;align-items:center;justify-content:center;font-size:1.9rem;flex-shrink:0;border:1px solid rgba(255,255,255,.25)}
.ex-hero-title{font-size:1.9rem;font-weight:800;margin:0 0 5px;letter-spacing:-.5px;color:#fff}
.ex-hero-sub{font-size:.95rem;opacity:.82;margin:0;color:#fff}
.ex-btn-new-hero{
    display:inline-flex;align-items:center;gap:8px;
    background:rgba(255,255,255,.18);backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.35);color:#fff;
    padding:11px 22px;border-radius:12px;font-weight:700;font-size:.88rem;
    cursor:pointer;transition:all .2s;white-space:nowrap;flex-shrink:0;
}
.ex-btn-new-hero:hover{background:rgba(255,255,255,.28);transform:translateY(-2px)}

/* ── KPI Row — réplica pb-kpi dashboard ──────────────── */
.ex-kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
@media(max-width:1100px){.ex-kpi-row{grid-template-columns:repeat(2,1fr)}}
@media(max-width:520px){.ex-kpi-row{grid-template-columns:1fr}}
.ex-kpi{
    background:#fff;border-radius:18px;padding:22px;
    box-shadow:0 2px 14px rgba(0,0,0,.07);
    display:flex;flex-direction:column;gap:12px;
    position:relative;overflow:hidden;
    transition:transform .25s,box-shadow .25s;cursor:default;
}
.ex-kpi:hover{transform:translateY(-4px);}
.ex-kpi::after{
    content:'';position:absolute;bottom:-20px;right:-20px;
    width:80px;height:80px;border-radius:50%;opacity:.07;
}
.ex-kpi-top  {display:flex;justify-content:space-between;align-items:flex-start;}
.ex-kpi-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;}
.ex-kpi-val  {font-size:2.6rem;font-weight:900;line-height:1;margin-top:2px;}
.ex-kpi-sub  {font-size:.74rem;color:#94a3b8;font-weight:500;}
.ex-kpi-icon {width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}

.ex-kpi.c-blue   {border-top:3px solid #2563eb;} .ex-kpi.c-blue::after   {background:#2563eb;}
.ex-kpi.c-teal   {border-top:3px solid #0d9488;} .ex-kpi.c-teal::after   {background:#0d9488;}
.ex-kpi.c-green  {border-top:3px solid #059669;} .ex-kpi.c-green::after  {background:#059669;}
.ex-kpi.c-red    {border-top:3px solid #dc2626;} .ex-kpi.c-red::after    {background:#dc2626;}
.ex-kpi.c-amber  {border-top:3px solid #d97706;} .ex-kpi.c-amber::after  {background:#d97706;}
.ex-kpi.c-purple {border-top:3px solid #7c3aed;} .ex-kpi.c-purple::after {background:#7c3aed;}
.ex-kpi.c-rose   {border-top:3px solid #e11d48;} .ex-kpi.c-rose::after   {background:#e11d48;}
.ex-kpi:hover.c-blue   {box-shadow:0 12px 22px -6px rgba(37,99,235,.55) !important;}
.ex-kpi:hover.c-teal   {box-shadow:0 12px 22px -6px rgba(13,148,136,.55) !important;}
.ex-kpi:hover.c-green  {box-shadow:0 12px 22px -6px rgba(5,150,105,.55) !important;}
.ex-kpi:hover.c-red    {box-shadow:0 12px 22px -6px rgba(220,38,38,.55) !important;}
.ex-kpi:hover.c-amber  {box-shadow:0 12px 22px -6px rgba(217,119,6,.55) !important;}
.ex-kpi:hover.c-purple {box-shadow:0 12px 22px -6px rgba(124,58,237,.55) !important;}
.ex-kpi:hover.c-rose   {box-shadow:0 12px 22px -6px rgba(225,29,72,.55) !important;}

/* ── Chips Bento de Distribución por Rangos ───────────────── */
.ex-range-pills-wrap {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-top: 14px;
}
@media(max-width: 480px) {
    .ex-range-pills-wrap {
        grid-template-columns: repeat(2, 1fr);
    }
}
.ex-range-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 8px 6px;
    border-radius: 12px;
    font-size: 0.72rem;
    font-weight: 700;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: default;
    border: 1px solid transparent;
    text-align: center;
    position: relative;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.ex-range-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
}
.ex-range-pill .range-pct-tooltip {
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%) translateY(4px);
    background: #0f172a;
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.18);
    z-index: 20;
}
.ex-range-pill .range-pct-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #0f172a;
}
.ex-range-pill:hover .range-pct-tooltip {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.ex-range-pill .range-header {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 4px;
}
.ex-range-pill .range-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}
.ex-range-pill .range-badge {
    font-size: 1.05rem;
    font-weight: 900;
    line-height: 1;
}

.ex-range-pill.r-red {
    background: linear-gradient(180deg, #ffffff 0%, #fef2f2 100%);
    border-color: #fee2e2;
    color: #991b1b;
}
.ex-range-pill.r-red .range-dot { background: #ef4444; }
.ex-range-pill.r-red .range-badge { color: #dc2626; }
.ex-range-pill.r-red:hover { border-color: #fca5a5; }

.ex-range-pill.r-amber {
    background: linear-gradient(180deg, #ffffff 0%, #fffbeb 100%);
    border-color: #fef3c7;
    color: #92400e;
}
.ex-range-pill.r-amber .range-dot { background: #f59e0b; }
.ex-range-pill.r-amber .range-badge { color: #d97706; }
.ex-range-pill.r-amber:hover { border-color: #fde68a; }

.ex-range-pill.r-blue {
    background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
    border-color: #dbeafe;
    color: #1e40af;
}
.ex-range-pill.r-blue .range-dot { background: #3b82f6; }
.ex-range-pill.r-blue .range-badge { color: #2563eb; }
.ex-range-pill.r-blue:hover { border-color: #bfdbfe; }

.ex-range-pill.r-green {
    background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
    border-color: #dcfce7;
    color: #166534;
}
.ex-range-pill.r-green .range-dot { background: #10b981; }
.ex-range-pill.r-green .range-badge { color: #059669; }
.ex-range-pill.r-green:hover { border-color: #bbf7d0; }

/* Switch Segmentado Minimalista */
.ex-switch-pill {
    display: inline-flex;
    align-items: center;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 10px;
    gap: 3px;
    border: 1px solid #e2e8f0;
}
.ex-switch-btn {
    border: none;
    background: transparent;
    padding: 5px 12px;
    border-radius: 7px;
    font-size: 0.74rem;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
}
.ex-switch-btn.active {
    background: #ffffff;
    color: #7c3aed;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.ex-switch-btn:hover:not(.active) {
    color: #1e293b;
    background: rgba(255,255,255,0.5);
}

.ex-inst-select-clean {
    border: none;
    background: transparent;
    font-size: 0.74rem;
    font-weight: 700;
    color: #64748b;
    outline: none;
    cursor: pointer;
    padding: 2px 4px;
}
.ex-inst-select-clean:focus {
    color: #7c3aed;
}

.ex-inst-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 8px 14px;
    margin-bottom: 12px;
    font-size: 0.77rem;
}
.ex-inst-badge-avg {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    font-weight: 800;
    color: #4338ca;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

/* ── Medals CSS (sin emojis) ──────────────────────────── */
.ex-medal{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;
    font-size:.72rem;font-weight:900;flex-shrink:0;line-height:1}
.ex-medal-1{background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;box-shadow:0 2px 8px rgba(245,158,11,.35)}
.ex-medal-2{background:linear-gradient(135deg,#9ca3af,#d1d5db);color:#fff}
.ex-medal-3{background:linear-gradient(135deg,#b45309,#d97706);color:#fff}
.ex-medal-n{background:#f1f5f9;color:#64748b}

/* ── Avatar iniciales ─────────────────────────────────── */
.ex-avatar{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;
    font-size:.68rem;font-weight:800;color:#fff;flex-shrink:0;letter-spacing:0}

/* ── Bento Grid — 3 × 2 uniforme ─────────────────────── */
/* breakpoints calculados sobre viewport - sidebar(260px) - padding(48px) = contenido real */
.ex-bento{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px}
@media(max-width:1200px){.ex-bento{grid-template-columns:repeat(2,1fr)}}
@media(max-width:640px){.ex-bento{grid-template-columns:1fr}}

.ex-card{background:#fff;border-radius:18px;padding:22px 24px;min-width:0;
    box-shadow:0 2px 14px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.04)}
.ex-card-title{font-size:.88rem;font-weight:700;color:#1e293b;margin:0 0 16px;
    display:flex;align-items:center;gap:7px}
.ex-card-title i{font-size:1rem}

/* ── Chart ───────────────────────────────────────────── */
.ex-chart-wrap{position:relative;height:200px}

/* ── Top 5 ───────────────────────────────────────────── */
.ex-top-row{display:flex;align-items:center;gap:9px;padding:10px 0;border-bottom:1px solid #f8fafc}
.ex-top-row:last-child{border-bottom:none}
.ex-top-name{flex:1;min-width:0;font-size:.82rem;font-weight:700;color:#1e293b;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ex-top-ced{font-size:.72rem;color:#94a3b8;font-weight:400}
.ex-top-pct{font-size:.83rem;font-weight:900;flex-shrink:0;min-width:48px;text-align:right}
.ex-top-bar{height:4px;background:#f1f5f9;border-radius:2px;overflow:hidden;margin-top:3px}
.ex-top-bar-fill{height:100%;border-radius:2px;transition:width .5s}

/* ── Active exams (sin barra falsa) ──────────────────── */
.ex-prog-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f8fafc}
.ex-prog-row:last-child{border-bottom:none}
.ex-prog-name{flex:1;min-width:0;font-size:.82rem;font-weight:600;color:#1e293b;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ex-prog-badge{display:inline-flex;align-items:center;gap:4px;background:#eff6ff;
    color:#2563eb;border-radius:20px;padding:3px 9px;font-size:.72rem;font-weight:700;flex-shrink:0}

/* ── Recent submissions ───────────────────────────────── */
.ex-table{width:100%;border-collapse:collapse;font-size:.8rem}
.ex-table th{font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;
    font-size:.68rem;padding:0 8px 10px;text-align:left;border-bottom:1px solid #f1f5f9}
.ex-table td{padding:9px 8px;border-bottom:1px solid #f8fafc;color:#1e293b;vertical-align:middle}
.ex-table tr:last-child td{border-bottom:none}
.ex-pct-badge{display:inline-flex;padding:3px 9px;border-radius:20px;font-weight:700;font-size:.75rem}
.ex-pct-ok{background:#d1fae5;color:#065f46}
.ex-pct-fail{background:#fee2e2;color:#b91c1c}

/* ── Exams grid section ───────────────────────────────── */
.ex-section-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:12px;flex-wrap:wrap}
.ex-section-title{font-size:1.05rem;font-weight:800;color:#1e293b;margin:0;display:flex;align-items:center;gap:8px}
.ex-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}
@media(max-width:768px){.ex-grid{grid-template-columns:1fr}}

.ex-exam-card{background:#fff;border-radius:18px;box-shadow:0 2px 14px rgba(0,0,0,.06);
    border:1px solid rgba(0,0,0,.04);overflow:hidden;transition:transform .2s,box-shadow .2s;display:flex}
.ex-exam-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,.1)}
.ex-exam-accent{width:5px;flex-shrink:0}
.ex-exam-body{padding:18px 20px;flex:1;min-width:0}
.ex-exam-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px}
.ex-exam-title{font-size:.95rem;font-weight:700;color:#1e293b;margin:0 0 3px;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px}
.ex-exam-desc{font-size:.79rem;color:#64748b;margin:0;display:-webkit-box;
    -webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ex-badge{padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap;flex-shrink:0}
.ex-badge-pub{background:#d1fae5;color:#065f46}
.ex-badge-draf{background:#fef3c7;color:#92400e}
.ex-badge-cerr{background:#f1f5f9;color:#475569}
.ex-btn-cerrar{background:#fee2e2!important;color:#ef4444!important;border-color:#fecaca!important}
.ex-exam-meta{font-size:.75rem;color:#94a3b8;margin:7px 0 10px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.ex-exam-stats{display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap}
.ex-exam-stat{display:flex;align-items:center;gap:5px;font-size:.75rem;color:#64748b;font-weight:600}
.ex-exam-actions{display:flex;gap:7px;flex-wrap:wrap}
.ex-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:10px;
    font-size:.75rem;font-weight:700;border:none;cursor:pointer;text-decoration:none;transition:all .18s}
.ex-btn-ver{background:#ede9fe;color:#6d28d9}
.ex-btn-ver:hover{background:#ddd6fe;color:#4c1d95}
.ex-btn-icon{background:#f1f5f9;color:#64748b;padding:6px 9px}
.ex-btn-icon:hover{background:#e2e8f0;color:#334155}
.ex-btn-del{background:#fef2f2;color:#ef4444;padding:6px 9px}
.ex-btn-del:hover{background:#fee2e2;color:#b91c1c}

.ex-empty{grid-column:1/-1;text-align:center;padding:50px 20px;background:#fff;
    border-radius:18px;box-shadow:0 2px 14px rgba(0,0,0,.06)}
.ex-empty i{font-size:2.8rem;color:#c4b5fd;display:block;margin-bottom:14px}
.ex-empty p{color:#94a3b8;font-size:.95rem;margin:0 0 18px}

/* ── Modal overlay ────────────────────────────────────── */
.exm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
    z-index:9000;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(3px)}
.exm-overlay.open{display:flex}
.exm-modal{background:#fff;border-radius:22px;width:100%;max-width:780px;
    max-height:90vh;display:flex;flex-direction:column;
    box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden}
.exm-header{padding:22px 28px 0;flex-shrink:0}
.exm-title{font-size:1.2rem;font-weight:800;color:#1e293b;margin:0 0 18px;
    display:flex;align-items:center;gap:10px}
.exm-title i{color:#2563eb}
.exm-close{position:absolute;top:16px;right:20px;background:none;border:none;
    font-size:1.4rem;cursor:pointer;color:#94a3b8;line-height:1;z-index:1}
.exm-header{position:relative}

/* Tabs */
.exm-tabs{display:flex;gap:4px;background:#f1f5f9;border-radius:12px;
    padding:4px;margin-bottom:0}
.exm-tab{flex:1;padding:9px 12px;border-radius:9px;border:none;background:none;
    font-size:.82rem;font-weight:700;color:#64748b;cursor:pointer;transition:all .18s;text-align:center}
.exm-tab.active{background:#fff;color:#2563eb;box-shadow:0 1px 6px rgba(0,0,0,.1)}

/* Body */
.exm-body{flex:1;overflow-y:auto;padding:20px 28px 24px}

/* Section ── Config */
.exm-label{font-size:.8rem;font-weight:700;color:#475569;margin-bottom:5px;display:block}
.exm-input,.exm-select,.exm-textarea{
    width:100%;padding:10px 13px;border:2px solid #e2e8f0;border-radius:11px;
    font-size:.88rem;color:#1e293b;background:#f8fafc;transition:all .2s;
    box-sizing:border-box;font-family:inherit}
.exm-input:focus,.exm-select:focus,.exm-textarea:focus{outline:none;border-color:#2563eb;background:#fff;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.exm-textarea{resize:vertical;min-height:68px}
.exm-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.exm-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.exm-field{margin-bottom:14px}
@media(max-width:560px){.exm-row,.exm-row-3{grid-template-columns:1fr}}

/* Section ── Banco */
.exm-bank-grid{display:flex;flex-direction:column;gap:8px}
.exm-bank-item{display:flex;align-items:flex-start;gap:12px;padding:11px 14px;
    border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:all .18s;background:#fafafa}
.exm-bank-item.selected{border-color:#2563eb;background:#eff6ff}
.exm-bank-item input[type=checkbox]{margin-top:2px;flex-shrink:0;accent-color:#2563eb;width:16px;height:16px}
.exm-bank-q{font-size:.83rem;font-weight:600;color:#1e293b;line-height:1.4}
.exm-bank-hint{font-size:.73rem;color:#94a3b8;margin-top:3px}
.exm-bank-sel-all{display:flex;align-items:center;gap:8px;margin-bottom:12px;
    font-size:.8rem;font-weight:700;color:#2563eb;cursor:pointer;background:none;border:none;padding:0}
.exm-bank-sel-all:hover{color:#1d4ed8}

/* Section ── Constructor */
.exm-preg-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:12px}
.exm-preg-head{display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap}
.exm-preg-num{background:#2563eb;color:#fff;font-size:.72rem;font-weight:800;
    width:24px;height:24px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.exm-preg-meta{display:grid;grid-template-columns:auto 100px 80px;gap:8px;align-items:center;flex:1;min-width:0}
@media(max-width:500px){.exm-preg-meta{grid-template-columns:1fr}}
.exm-preg-actions{display:flex;gap:5px;flex-shrink:0}
.exm-preg-btn{background:#e2e8f0;color:#64748b;border:none;border-radius:8px;
    width:28px;height:28px;display:flex;align-items:center;justify-content:center;
    cursor:pointer;font-size:.85rem;transition:all .15s}
.exm-preg-btn:hover{background:#cbd5e1;color:#334155}
.exm-preg-btn.del:hover{background:#fee2e2;color:#ef4444}
.exm-opcion-row{display:flex;align-items:center;gap:8px;margin-bottom:7px}
.exm-opcion-ltr{width:26px;height:26px;border-radius:8px;background:#e2e8f0;
    display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;
    color:#475569;flex-shrink:0}
.exm-opcion-input{flex:1;padding:8px 11px;border:2px solid #e2e8f0;border-radius:9px;
    font-size:.83rem;color:#1e293b;background:#fff;transition:border-color .18s;box-sizing:border-box}
.exm-opcion-input:focus{outline:none;border-color:#2563eb}
.exm-opcion-cb{accent-color:#059669;width:16px;height:16px;flex-shrink:0}
.exm-opcion-del{background:none;border:none;color:#ef4444;cursor:pointer;font-size:.9rem;flex-shrink:0;line-height:1}
.exm-opcion-del:hover{color:#b91c1c}
.exm-add-op{background:none;border:2px dashed #cbd5e1;border-radius:9px;color:#94a3b8;
    font-size:.78rem;font-weight:600;padding:6px 12px;cursor:pointer;width:100%;transition:all .18s;margin-top:4px}
.exm-add-op:hover{border-color:#2563eb;color:#2563eb}
.exm-add-preg-btn{width:100%;padding:11px;background:#fff;border:2px dashed #2563eb;
    border-radius:12px;color:#2563eb;font-weight:700;font-size:.85rem;cursor:pointer;transition:all .18s;margin-top:4px}
.exm-add-preg-btn:hover{background:#eff6ff}
.exm-empty-preg{text-align:center;padding:28px;color:#94a3b8;font-size:.85rem;background:#fff;
    border:2px dashed #e2e8f0;border-radius:12px}

/* Footer */
.exm-footer{padding:16px 28px;border-top:1px solid #f1f5f9;display:flex;gap:10px;justify-content:flex-end;flex-shrink:0;flex-wrap:wrap}
.exm-btn-save{display:inline-flex;align-items:center;gap:7px;padding:11px 22px;
    border-radius:11px;font-weight:700;font-size:.88rem;border:none;cursor:pointer;transition:all .2s}
.exm-btn-draft{background:#f1f5f9;color:#475569}
.exm-btn-draft:hover{background:#e2e8f0}
.exm-btn-publish{background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.exm-btn-publish:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,.4)}
.exm-btn-cancel{background:#f8fafc;color:#94a3b8;border:1px solid #e2e8f0}
.exm-btn-cancel:hover{background:#f1f5f9}

/* ── Mobile ≤ 767px ─────────────────────────────────────────── */
@media(max-width:767px){
    .ex-hero{padding:20px 18px;gap:16px;border-radius:18px;}
    .ex-hero-inner{flex-direction:column;align-items:flex-start;gap:16px;}
    .ex-hero-left{width:100%;gap:14px;}
    .ex-hero-icon{width:48px;height:48px;font-size:1.4rem;flex-shrink:0;border-radius:14px;}
    .ex-hero-title{font-size:1.4rem;line-height:1.25;}
    .ex-hero-sub{font-size:.82rem;}
    .ex-btn-new-hero{width:100%;justify-content:center;padding:12px 16px;min-height:42px;}
    .ex-kpi-row{grid-template-columns:repeat(2,1fr)}
    .ex-bento{grid-template-columns:1fr}
    .ex-grid{grid-template-columns:1fr}
    /* Modal full-screen */
    .exm-modal{width:100%;max-width:100%;height:100%;max-height:100%;border-radius:0;margin:0}
    .exm-header{padding:16px 18px}
    .exm-body{padding:16px 18px}
    .exm-footer{padding:12px 18px}
    .exm-btn-save{padding:9px 16px;font-size:.82rem}
    /* Constructor de preguntas */
    .exm-preg-meta{grid-template-columns:1fr}
    .exm-row,.exm-row-3{grid-template-columns:1fr}
    .exm-tab{padding:7px 8px;font-size:.78rem}
}
</style>

<div class="ex-wrap">

<!-- ═══════════════════ HERO ═══════════════════ -->
<div class="ex-hero">
    <div class="ex-hero-inner">
        <div class="ex-hero-left">
            <div class="ex-hero-icon"><i class="ti ti-notebook"></i></div>
            <div>
                <h1 class="ex-hero-title">Exámenes Rápidos</h1>
                <p class="ex-hero-sub">
                    <?= $totalActivos ?> activo<?= $totalActivos !== 1 ? 's' : '' ?> &bull;
                    <?= $totalEvaluados ?> pasante<?= $totalEvaluados !== 1 ? 's' : '' ?> evaluado<?= $totalEvaluados !== 1 ? 's' : '' ?>
                </p>
            </div>
        </div>
        <button class="ex-btn-new-hero" onclick="abrirModalExamen()">
            <i class="ti ti-plus"></i> Nuevo Examen
        </button>
    </div>
</div>

<!-- ═══════════════════ KPI ROW ═══════════════════ -->
<?php
$avgClass  = (float)$avgScore       >= 60 ? 'c-green' : 'c-red';
$tasaClass = (float)$tasaAprobacion >= 60 ? 'c-green' : 'c-red';
$avgBg     = (float)$avgScore       >= 60 ? '#d1fae5' : '#fee2e2';
$tasaBg    = (float)$tasaAprobacion >= 60 ? '#d1fae5' : '#fee2e2';
?>
<div class="ex-kpi-row">

    <div class="ex-kpi c-blue">
        <div class="ex-kpi-top">
            <div>
                <div class="ex-kpi-label">Exámenes activos</div>
                <div class="ex-kpi-val" style="color:#2563eb;"><?= $totalActivos ?></div>
                <div class="ex-kpi-sub"><?= $totalActivos === 1 ? '1 examen publicado' : "$totalActivos exámenes publicados" ?></div>
            </div>
            <div class="ex-kpi-icon" style="background:#eff6ff;">
                <i class="ti ti-notebook" style="color:#2563eb;"></i>
            </div>
        </div>
    </div>

    <div class="ex-kpi c-teal">
        <div class="ex-kpi-top">
            <div>
                <div class="ex-kpi-label">Pasantes evaluados</div>
                <div class="ex-kpi-val" style="color:#0d9488;"><?= $totalEvaluados ?></div>
                <div class="ex-kpi-sub"><?= $totalEvaluados === 1 ? '1 respuesta enviada' : "$totalEvaluados respuestas enviadas" ?></div>
            </div>
            <div class="ex-kpi-icon" style="background:#ccfbf1;">
                <i class="ti ti-users" style="color:#0d9488;"></i>
            </div>
        </div>
    </div>

    <div class="ex-kpi <?= $avgClass ?>">
        <div class="ex-kpi-top">
            <div>
                <div class="ex-kpi-label">Promedio general</div>
                <div class="ex-kpi-val" style="color:<?= $avgColor ?>;"><?= $avgScore ?>%</div>
                <div class="ex-kpi-sub"><?= (float)$avgScore >= 60 ? 'Por encima del mínimo' : 'Por debajo del mínimo' ?></div>
            </div>
            <div class="ex-kpi-icon" style="background:<?= $avgBg ?>;">
                <i class="ti ti-chart-bar" style="color:<?= $avgColor ?>;"></i>
            </div>
        </div>
    </div>

    <div class="ex-kpi <?= $tasaClass ?>">
        <div class="ex-kpi-top">
            <div>
                <div class="ex-kpi-label">Tasa de aprobación</div>
                <div class="ex-kpi-val" style="color:<?= $tasaColor ?>;"><?= $tasaAprobacion ?>%</div>
                <div class="ex-kpi-sub"><?= (float)$tasaAprobacion >= 60 ? 'Rendimiento positivo' : 'Requiere atención' ?></div>
            </div>
            <div class="ex-kpi-icon" style="background:<?= $tasaBg ?>;">
                <i class="ti ti-rosette" style="color:<?= $tasaColor ?>;"></i>
            </div>
        </div>
    </div>

</div>

<!-- ═══════════════════ BENTO ANALYTICS  3 × 2 ═══════════════════ -->
<?php
$totalBorradores = $totalExamenes - $totalActivos;
$avatarColors    = ['#2563eb','#7c3aed','#059669','#d97706','#0891b2'];
$medalClasses    = ['ex-medal-1','ex-medal-2','ex-medal-3','ex-medal-n','ex-medal-n'];
?>
<div class="ex-bento">

    <!-- ── Card 1: Dona aprobados/reprobados ── -->
    <div class="ex-card" style="display:flex;flex-direction:column;">
        <p class="ex-card-title"><i class="ti ti-chart-donut" style="color:#2563eb;"></i> Aprobados vs Reprobados</p>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;">
            <div style="width:160px;height:160px;position:relative;flex-shrink:0;">
                <canvas id="exDonutChart" width="160" height="160"></canvas>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;
                    align-items:center;justify-content:center;pointer-events:none;">
                    <?php $total_intentos_dist = $aprobados + $reprobados; ?>
                    <?php if ($total_intentos_dist > 0): ?>
                    <span style="font-size:1.55rem;font-weight:900;color:<?= $aprobados >= $reprobados ? '#059669' : '#dc2626' ?>;">
                        <?= round(($aprobados / $total_intentos_dist) * 100) ?>%
                    </span>
                    <span style="font-size:.67rem;color:#94a3b8;font-weight:600;margin-top:2px;">aprobación</span>
                    <?php else: ?>
                    <span style="font-size:.8rem;color:#94a3b8;">Sin datos</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;justify-content:center;">
                <span style="display:flex;align-items:center;gap:6px;font-size:.74rem;color:#64748b;font-weight:600;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#059669;display:inline-block;flex-shrink:0;"></span>
                    Aprobados (<?= $aprobados ?>)
                </span>
                <span style="display:flex;align-items:center;gap:6px;font-size:.74rem;color:#64748b;font-weight:600;">
                    <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;flex-shrink:0;"></span>
                    Reprobados (<?= $reprobados ?>)
                </span>
            </div>
        </div>
    </div>

    <!-- ── Card 2: Rendimiento Académico por Institución (Barras) ── -->
    <div class="ex-card" style="display:flex;flex-direction:column;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
            <p class="ex-card-title" style="margin:0;"><i class="ti ti-chart-bar" style="color:#7c3aed;"></i> Rendimiento Académico</p>
            
            <!-- Switch Segmentado Minimalista -->
            <div class="ex-switch-pill">
                <button type="button" class="ex-switch-btn active" id="btnSwitchGlobal" onclick="setVistaInstitucion('global')">
                    <i class="ti ti-world"></i> Global
                </button>
                <?php if (count($institucionesStats ?? []) === 1): ?>
                    <?php $unicaInst = $institucionesStats[0]; ?>
                    <button type="button" class="ex-switch-btn" id="btnSwitchInst" onclick="setVistaInstitucion('<?= (int)$unicaInst->institucion_id ?>')">
                        <i class="ti ti-school"></i> <?= htmlspecialchars($unicaInst->institucion_nombre) ?>
                    </button>
                <?php elseif (count($institucionesStats ?? []) > 1): ?>
                    <div style="display:inline-flex;align-items:center;padding:0 6px;">
                        <i class="ti ti-school" style="font-size:0.85rem;color:#7c3aed;margin-right:3px;"></i>
                        <select id="selInstitucionChart" class="ex-inst-select-clean" onchange="setVistaInstitucion(this.value)">
                            <option value="" disabled selected>Por Escuela...</option>
                            <?php foreach ($institucionesStats ?? [] as $inst): ?>
                                <option value="<?= (int)$inst->institucion_id ?>">
                                    <?= htmlspecialchars($inst->institucion_nombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <button type="button" class="ex-switch-btn" disabled style="opacity:0.5;cursor:not-allowed;">
                        <i class="ti ti-school"></i> Sin escuelas
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Banner de promedio interactivo -->
        <div id="instKpiBanner" class="ex-inst-banner">
            <span id="instSelectedLabel" style="color:#475569;font-weight:700;display:flex;align-items:center;gap:6px;">
                <i class="ti ti-users" style="color:#6366f1;font-size:1rem;"></i> Mostrando promedio global
            </span>
            <span class="ex-inst-badge-avg" id="instSelectedAvg">
                <i class="ti ti-chart-arrows-vertical" style="font-size:0.85rem;"></i> Promedio: <?= $avgScore ?>%
            </span>
        </div>

        <!-- Canvas Gráfico de Barras con altura óptima -->
        <div style="position:relative;height:185px;width:100%;">
            <canvas id="exBarChart" style="display:block;"></canvas>
        </div>

        <!-- Grid de Niveles Cualitativos -->
        <div class="ex-range-pills-wrap">
            <div class="ex-range-pill r-red">
                <span class="range-pct-tooltip">Rango: 0% – 39%</span>
                <div class="range-header">
                    <span class="range-dot"></span>
                    <span>Deficiente</span>
                </div>
                <span class="range-badge" id="badgeDeficiente"><?= (int)($dist->r0 ?? 0) + (int)($dist->r20 ?? 0) ?></span>
            </div>
            <div class="ex-range-pill r-amber">
                <span class="range-pct-tooltip">Rango: 40% – 59%</span>
                <div class="range-header">
                    <span class="range-dot"></span>
                    <span>Regular</span>
                </div>
                <span class="range-badge" id="badgeRegular"><?= (int)($dist->r40 ?? 0) ?></span>
            </div>
            <div class="ex-range-pill r-blue">
                <span class="range-pct-tooltip">Rango: 60% – 79%</span>
                <div class="range-header">
                    <span class="range-dot"></span>
                    <span>Bueno</span>
                </div>
                <span class="range-badge" id="badgeBueno"><?= (int)($dist->r60 ?? 0) ?></span>
            </div>
            <div class="ex-range-pill r-green">
                <span class="range-pct-tooltip">Rango: 80% – 100%</span>
                <div class="range-header">
                    <span class="range-dot"></span>
                    <span>Excelente</span>
                </div>
                <span class="range-badge" id="badgeExcelente"><?= (int)($dist->r80 ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- ── Card 3: Top 5 Pasantes ── -->
    <div class="ex-card" style="display:flex;flex-direction:column;">
        <p class="ex-card-title"><i class="ti ti-trophy" style="color:#d97706;"></i> Top 5 Pasantes</p>
        <?php if (empty($top5)): ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;font-size:.83rem;">
            <i class="ti ti-users" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.4;"></i>
            Sin datos aún
        </div>
        <?php else: ?>
        <div style="flex:1;">
        <?php foreach ($top5 as $i => $p):
            $pct      = (float)($p->mejor_pct ?? 0);
            $pctColor = $pct >= 60 ? '#059669' : '#dc2626';
            $nom      = htmlspecialchars($p->pasante_nombre ?? '');
            $parts    = array_filter(explode(' ', trim($p->pasante_nombre ?? '')));
            $initials = strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice($parts, 0, 2))));
            $aColor   = $avatarColors[$i % count($avatarColors)];
        ?>
        <div class="ex-top-row">
            <div class="ex-medal <?= $medalClasses[$i] ?? 'ex-medal-n' ?>"><?= $i + 1 ?></div>
            <div class="ex-avatar" style="background:<?= $aColor ?>;"><?= $initials ?: '?' ?></div>
            <div style="flex:1;min-width:0;">
                <div class="ex-top-name"><?= $nom ?></div>
                <div class="ex-top-bar">
                    <div class="ex-top-bar-fill" style="width:<?= min(100,$pct) ?>%;background:<?= $pctColor ?>;"></div>
                </div>
            </div>
            <div class="ex-top-pct" style="color:<?= $pctColor ?>;"><?= number_format($pct, 1) ?>%</div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Card 4: Exámenes Activos ── -->
    <div class="ex-card" style="display:flex;flex-direction:column;">
        <p class="ex-card-title"><i class="ti ti-activity" style="color:#059669;"></i> Exámenes Activos</p>
        <?php if (empty($examenesActivos)): ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;font-size:.83rem;">
            <i class="ti ti-notebook" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.4;"></i>
            Sin activos
        </div>
        <?php else: ?>
        <div style="flex:1;">
        <?php foreach ($examenesActivos as $ae):
            $res = (int)($ae->respondieron ?? 0);
        ?>
        <div class="ex-prog-row">
            <div class="ex-prog-name" title="<?= htmlspecialchars($ae->titulo ?? '') ?>">
                <a href="<?= URLROOT ?>/examenes/ver/<?= (int)$ae->id ?>"
                   style="color:#1e293b;text-decoration:none;">
                    <?= htmlspecialchars($ae->titulo ?? '') ?>
                </a>
            </div>
            <div class="ex-prog-badge">
                <i class="ti ti-users" style="font-size:.75rem;"></i> <?= $res ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Card 5: Envíos Recientes (lista compacta) ── -->
    <div class="ex-card" style="display:flex;flex-direction:column;">
        <p class="ex-card-title"><i class="ti ti-clock" style="color:#2563eb;"></i> Envíos Recientes</p>
        <?php if (empty($recientes)): ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;font-size:.83rem;">
            <i class="ti ti-inbox" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.4;"></i>
            Sin envíos aún
        </div>
        <?php else: ?>
        <div style="flex:1;">
        <?php foreach (array_slice($recientes, 0, 5) as $r):
            $pct     = (float)($r->porcentaje ?? 0);
            $aprobado = $pct >= 60;
            $rNom    = $r->pasante_nombre ?? '';
            $rParts  = array_filter(explode(' ', trim($rNom)));
            $rInits  = strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice($rParts, 0, 2))));
        ?>
        <div class="ex-top-row">
            <div class="ex-avatar" style="background:#2563eb;font-size:.62rem;"><?= $rInits ?: '?' ?></div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.8rem;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($rNom) ?>
                </div>
                <div style="font-size:.7rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($r->examen_titulo ?? '') ?>
                </div>
            </div>
            <span class="ex-pct-badge <?= $aprobado ? 'ex-pct-ok' : 'ex-pct-fail' ?>" style="flex-shrink:0;">
                <?= number_format($pct, 1) ?>%
            </span>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Card 6: Borradores ── -->
    <?php $borrAdorClr = $totalBorradores > 0 ? '#d97706' : '#94a3b8'; ?>
    <div class="ex-card" style="display:flex;flex-direction:column;">
        <p class="ex-card-title"><i class="ti ti-pencil" style="color:#d97706;"></i> Borradores</p>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;text-align:center;">
            <div style="width:64px;height:64px;border-radius:18px;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-file-pencil" style="font-size:1.8rem;color:#d97706;"></i>
            </div>
            <div style="font-size:2.8rem;font-weight:900;line-height:1;color:<?= $borrAdorClr ?>;">
                <?= $totalBorradores ?>
            </div>
            <div style="font-size:.78rem;color:#94a3b8;font-weight:500;">
                <?= $totalBorradores === 1 ? 'examen sin publicar' : 'exámenes sin publicar' ?>
            </div>
            <?php if ($totalBorradores > 0): ?>
            <button onclick="abrirModalExamen()" style="background:#fef3c7;border:none;border-radius:10px;
                padding:7px 16px;font-size:.75rem;font-weight:700;color:#92400e;cursor:pointer;">
                <i class="ti ti-plus"></i> Publicar ahora
            </button>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ═══════════════════ EXAM LIST ═══════════════════ -->
<div class="ex-section-hd">
    <h2 class="ex-section-title">
        <i class="ti ti-notebook" style="color:#2563eb;"></i>
        Exámenes Registrados
        <span style="background:#eff6ff;color:#2563eb;font-size:.72rem;padding:2px 8px;border-radius:20px;font-weight:700;">
            <?= $totalExamenes ?>
        </span>
    </h2>
</div>

<div class="ex-grid">
    <?php if (empty($examenes)): ?>
    <div class="ex-empty">
        <i class="ti ti-notebook"></i>
        <p>No hay exámenes registrados aún.</p>
        <button class="ex-btn-new-hero" style="display:inline-flex;background:linear-gradient(135deg,#1d4ed8,#2563eb);border:none;"
                onclick="abrirModalExamen()">
            <i class="ti ti-plus"></i> Crear primer examen
        </button>
    </div>
    <?php else: ?>
    <?php foreach ($examenes as $ex):
        $activo    = (int)($ex->activo ?? 0);
        $respond   = (int)($ex->total_respondieron ?? 0);
        $estado    = $activo ? 'publicado' : ($respond > 0 ? 'cerrado' : 'borrador');
        $accentClr = $activo ? '#059669'   : ($respond > 0 ? '#64748b' : '#d97706');
    ?>
    <div class="ex-exam-card" id="ex-card-<?= (int)$ex->id ?>">
        <div class="ex-exam-accent" style="background:<?= $accentClr ?>;"></div>
        <div class="ex-exam-body">
            <div class="ex-exam-head">
                <div style="min-width:0;">
                    <p class="ex-exam-title" title="<?= htmlspecialchars($ex->titulo ?? '') ?>">
                        <?= htmlspecialchars($ex->titulo ?? '') ?>
                    </p>
                    <?php if (!empty($ex->descripcion)): ?>
                    <p class="ex-exam-desc"><?= htmlspecialchars($ex->descripcion) ?></p>
                    <?php endif; ?>
                </div>
                <span class="ex-badge <?= $estado === 'publicado' ? 'ex-badge-pub' : ($estado === 'cerrado' ? 'ex-badge-cerr' : 'ex-badge-draf') ?>">
                    <?= $estado === 'publicado' ? 'Publicado' : ($estado === 'cerrado' ? 'Cerrado' : 'Borrador') ?>
                </span>
            </div>

            <div class="ex-exam-meta">
                <i class="ti ti-calendar"></i>
                <?php if (!empty($ex->fecha_inicio) || !empty($ex->fecha_fin)): ?>
                    <?= !empty($ex->fecha_inicio) ? date('d/m/Y', strtotime($ex->fecha_inicio)) : '?' ?>
                    &mdash;
                    <?= !empty($ex->fecha_fin)    ? date('d/m/Y', strtotime($ex->fecha_fin))    : '?' ?>
                <?php else: ?>
                    Sin restricción de fecha
                <?php endif; ?>
                &bull;
                <i class="ti ti-refresh"></i>
                <?= (int)($ex->intentos_permitidos ?? 1) ?> intento<?= (int)($ex->intentos_permitidos ?? 1) !== 1 ? 's' : '' ?>
            </div>

            <div class="ex-exam-stats">
                <span class="ex-exam-stat">
                    <i class="ti ti-help-circle" style="color:#7c3aed;"></i>
                    <?= (int)($ex->total_preguntas ?? 0) ?> pregunta<?= (int)($ex->total_preguntas ?? 0) !== 1 ? 's' : '' ?>
                </span>
                <span class="ex-exam-stat">
                    <i class="ti ti-users" style="color:#2563eb;"></i>
                    <?= (int)($ex->total_respondieron ?? 0) ?> respondieron
                </span>
            </div>

            <div class="ex-exam-actions">
                <a href="<?= URLROOT ?>/examenes/ver/<?= (int)$ex->id ?>" class="ex-btn ex-btn-ver">
                    <i class="ti ti-chart-bar"></i> Ver resultados
                </a>
                <button class="ex-btn ex-btn-icon <?= $activo ? 'ex-btn-cerrar' : '' ?>"
                        title="<?= $activo ? 'Cerrar examen' : ($respond > 0 ? 'Reabrir examen' : 'Publicar') ?>"
                        onclick="togglePublicar(<?= (int)$ex->id ?>, <?= $activo ?>, <?= $respond ?>)"
                        id="btn-pub-<?= (int)$ex->id ?>">
                    <i class="ti <?= $activo ? 'ti-lock' : ($respond > 0 ? 'ti-lock-open' : 'ti-eye') ?>"></i>
                </button>
                <button class="ex-btn ex-btn-del"
                        title="Eliminar"
                        onclick="eliminarExamen(<?= (int)$ex->id ?>)">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

</div><!-- /ex-wrap -->

<!-- ═══════════════════════════════════════════════════════
     MODAL NUEVO EXAMEN
════════════════════════════════════════════════════════ -->
<div class="exm-overlay" id="exmOverlay" onclick="if(event.target===this)cerrarModalExamen()">
<div class="exm-modal">

    <div class="exm-header">
        <p class="exm-title"><i class="ti ti-notebook"></i> Nuevo Examen</p>
        <button class="exm-close" onclick="cerrarModalExamen()" title="Cerrar">&times;</button>

        <div class="exm-tabs">
            <button class="exm-tab active" id="tab-config" onclick="cambiarTab('config')">
                <i class="ti ti-settings" style="font-size:.85rem;"></i> Configuración
            </button>
            <button class="exm-tab" id="tab-banco" onclick="cambiarTab('banco')">
                <i class="ti ti-library" style="font-size:.85rem;"></i> Banco de preguntas
            </button>
            <button class="exm-tab" id="tab-constructor" onclick="cambiarTab('constructor')">
                <i class="ti ti-tool" style="font-size:.85rem;"></i> Constructor
                <span id="exm-preg-count" style="background:#dbeafe;color:#1d4ed8;border-radius:20px;padding:1px 7px;font-size:.7rem;margin-left:4px;">0</span>
            </button>
        </div>
    </div>

    <div class="exm-body">

        <!-- Tab: Config -->
        <div id="exm-pane-config">
            <div class="exm-field">
                <label class="exm-label">Título del examen *</label>
                <input type="text" class="exm-input" id="exm-titulo" placeholder="Ej. Evaluación de soporte técnico básico">
            </div>
            <div class="exm-field">
                <label class="exm-label">Descripción (opcional)</label>
                <textarea class="exm-textarea" id="exm-desc" placeholder="Instrucciones o descripción del examen..."></textarea>
            </div>
            <div class="exm-row">
                <div class="exm-field">
                    <label class="exm-label">Período académico</label>
                    <select class="exm-select" id="exm-periodo">
                        <option value="">— Sin período —</option>
                        <?php foreach ($periodos as $p):
                            $tipo = $p->tipo ?? 'Regular';
                            $tipoLabel = $tipo === 'Corto' ? ' · Corto' : ' · Regular';
                        ?>
                        <option value="<?= (int)$p->id ?>"><?= htmlspecialchars($p->nombre) ?><?= $tipoLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="exm-row">
                <div class="exm-field">
                    <label class="exm-label">Fecha de apertura</label>
                    <input type="date" class="exm-input" id="exm-fecha-inicio">
                </div>
                <div class="exm-field">
                    <label class="exm-label">Fecha de cierre</label>
                    <input type="date" class="exm-input" id="exm-fecha-fin">
                </div>
            </div>
        </div>

        <!-- Tab: Banco -->
        <div id="exm-pane-banco" style="display:none;">
            <div style="margin-bottom:14px;padding:10px 14px;background:#eff6ff;border-radius:10px;border-left:3px solid #2563eb;">
                <p style="margin:0;font-size:.8rem;color:#1e40af;line-height:1.55;">
                    <i class="ti ti-info-circle"></i>
                    Selecciona las preguntas que quieras incluir en el examen.
                    Las preguntas seleccionadas se agregan automáticamente al constructor.
                </p>
            </div>
            <button class="exm-bank-sel-all" onclick="toggleSelAll()">
                <i class="ti ti-checkbox"></i> <span id="exm-sel-all-txt">Seleccionar todas</span>
            </button>
            <div class="exm-bank-grid" id="exm-bank-grid">
                <!-- rendered by JS -->
            </div>
        </div>

        <!-- Tab: Constructor -->
        <div id="exm-pane-constructor" style="display:none;">
            <div id="exm-preguntas-list">
                <!-- rendered by renderConstructor() -->
            </div>
            <button class="exm-add-preg-btn" onclick="agregarPregunta()">
                <i class="ti ti-plus"></i> Agregar pregunta manual
            </button>
        </div>

    </div><!-- /exm-body -->

    <div class="exm-footer">
        <button class="exm-btn-save exm-btn-cancel" onclick="cerrarModalExamen()">Cancelar</button>
        <button class="exm-btn-save exm-btn-draft"   onclick="guardarExamen(false)" id="exm-btn-draft">
            <i class="ti ti-device-floppy"></i> Guardar borrador
        </button>
        <button class="exm-btn-save exm-btn-publish" onclick="guardarExamen(true)" id="exm-btn-pub">
            <i class="ti ti-send"></i> Publicar examen
        </button>
    </div>

</div><!-- /exm-modal -->
</div><!-- /exm-overlay -->


<script>
// URLROOT ya disponible desde main_layout.php

// ─────────────────────────────────────────────────────────────
// Charts: donut (aprobados/reprobados) + barras horizontales
// ─────────────────────────────────────────────────────────────
(function () {
    var aprobados  = <?= $aprobados ?>;
    var reprobados = <?= $reprobados ?>;
    var r0  = <?= (int)($dist->r0  ?? 0) ?>;
    var r20 = <?= (int)($dist->r20 ?? 0) ?>;
    var r40 = <?= (int)($dist->r40 ?? 0) ?>;
    var r60 = <?= (int)($dist->r60 ?? 0) ?>;
    var r80 = <?= (int)($dist->r80 ?? 0) ?>;

    // Dona: Aprobados vs Reprobados
    var ctxD = document.getElementById('exDonutChart');
    if (ctxD) {
        var noData = aprobados === 0 && reprobados === 0;
        new Chart(ctxD, {
            type: 'doughnut',
            data: {
                labels: noData ? ['Sin datos'] : ['Aprobados', 'Reprobados'],
                datasets: [{
                    data: noData ? [1] : [aprobados, reprobados],
                    backgroundColor: noData ? ['#e2e8f0'] : ['#059669', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: noData ? 0 : 8,
                }]
            },
            options: {
                responsive: false, cutout: '72%',
                animation: { duration: 900, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: !noData,
                        callbacks: {
                            label: function(c) { return ' ' + c.label + ': ' + c.parsed + ' pasante' + (c.parsed !== 1 ? 's' : ''); }
                        }
                    }
                }
            }
        });
    }

    // Barras: Distribución cualitativa por rangos y por institución
    var instStatsData = <?= json_encode($institucionesStats ?? []) ?>;
    var globalDistData = {
        deficiente: (r0 + r20),
        regular:    r40,
        bueno:      r60,
        excelente:  r80,
        promedio:   <?= (float)($avgScore ?? 0) ?>
    };

    var barChartInstance = null;
    var ctxB = document.getElementById('exBarChart');
    if (ctxB) {
        var canvasCtx = ctxB.getContext('2d');
        
        // Crear degradados verticales para cada nivel
        var gradRed = canvasCtx.createLinearGradient(0, 0, 0, 180);
        gradRed.addColorStop(0, 'rgba(239, 68, 68, 0.95)');
        gradRed.addColorStop(1, 'rgba(239, 68, 68, 0.35)');

        var gradAmber = canvasCtx.createLinearGradient(0, 0, 0, 180);
        gradAmber.addColorStop(0, 'rgba(245, 158, 11, 0.95)');
        gradAmber.addColorStop(1, 'rgba(245, 158, 11, 0.35)');

        var gradBlue = canvasCtx.createLinearGradient(0, 0, 0, 180);
        gradBlue.addColorStop(0, 'rgba(59, 130, 246, 0.95)');
        gradBlue.addColorStop(1, 'rgba(59, 130, 246, 0.35)');

        var gradGreen = canvasCtx.createLinearGradient(0, 0, 0, 180);
        gradGreen.addColorStop(0, 'rgba(16, 185, 129, 0.95)');
        gradGreen.addColorStop(1, 'rgba(16, 185, 129, 0.35)');

        barChartInstance = new Chart(ctxB, {
            type: 'bar',
            data: {
                labels: ['Deficiente', 'Regular', 'Bueno', 'Excelente'],
                datasets: [{
                    label: 'Pasantes',
                    data: [globalDistData.deficiente, globalDistData.regular, globalDistData.bueno, globalDistData.excelente],
                    backgroundColor: [gradRed, gradAmber, gradBlue, gradGreen],
                    borderColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981'],
                    borderWidth: 1.5,
                    borderRadius: 10,
                    borderSkipped: false,
                    maxBarThickness: 46
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 600, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 11, weight: '600' },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            title: function(items) {
                                var rangos = ['0–39%', '40–59%', '60–79%', '80–100%'];
                                return items[0].label + ' (' + (rangos[items[0].dataIndex] || '') + ')';
                            },
                            label: function(c) {
                                var total = c.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var pct = total > 0 ? Math.round((c.parsed.y / total) * 100) : 0;
                                return ' ' + c.parsed.y + ' pasante' + (c.parsed.y !== 1 ? 's' : '') + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10.5, weight: '600' }, color: '#94a3b8' },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    x: {
                        ticks: { display: false },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // Switch/filtro interactivo de institución
    window.setVistaInstitucion = function(val) {
        if (!barChartInstance) return;

        // Actualizar estados visuales de los botones switch
        var btnGlobal = document.getElementById('btnSwitchGlobal');
        var btnInst   = document.getElementById('btnSwitchInst');
        var selClean  = document.getElementById('selInstitucionChart');

        if (btnGlobal) btnGlobal.classList.toggle('active', val === 'global');
        if (btnInst)   btnInst.classList.toggle('active', val !== 'global');
        if (val === 'global' && selClean) {
            selClean.value = '';
        }

        var newData = [];
        var avgLabel = '';
        var selectedText = '';

        if (val === 'global') {
            newData = [globalDistData.deficiente, globalDistData.regular, globalDistData.bueno, globalDistData.excelente];
            avgLabel = 'Promedio: ' + globalDistData.promedio + '%';
            selectedText = '<i class="ti ti-users" style="color:#6366f1;font-size:1rem;"></i> Mostrando promedio global';
        } else {
            var found = instStatsData.find(function(it) { return it.institucion_id == val; });
            if (found) {
                newData = [parseInt(found.deficiente || 0), parseInt(found.regular || 0), parseInt(found.bueno || 0), parseInt(found.excelente || 0)];
                avgLabel = 'Promedio: ' + found.promedio_pct + '%';
                selectedText = '<i class="ti ti-school" style="color:#7c3aed;font-size:1rem;"></i> ' + found.institucion_nombre;
            } else {
                newData = [0, 0, 0, 0];
                avgLabel = 'Promedio: 0%';
                selectedText = 'Sin registros para esta institución';
            }
        }

        // Actualizar gráfico de barras
        barChartInstance.data.datasets[0].data = newData;
        barChartInstance.update();

        // Actualizar badges numéricos
        var bDef = document.getElementById('badgeDeficiente');
        var bReg = document.getElementById('badgeRegular');
        var bBue = document.getElementById('badgeBueno');
        var bExc = document.getElementById('badgeExcelente');
        if (bDef) bDef.textContent = newData[0];
        if (bReg) bReg.textContent = newData[1];
        if (bBue) bBue.textContent = newData[2];
        if (bExc) bExc.textContent = newData[3];

        // Actualizar banner
        var lLabel = document.getElementById('instSelectedLabel');
        var lAvg   = document.getElementById('instSelectedAvg');
        if (lLabel) lLabel.innerHTML = selectedText;
        if (lAvg)   lAvg.innerHTML   = '<i class="ti ti-chart-arrows-vertical" style="font-size:0.85rem;"></i> ' + avgLabel;
    };

    window.cambiarFiltroInstitucion = window.setVistaInstitucion;
})();

// ─────────────────────────────────────────────────────────────
// Tiempo relativo para columnas de fecha
// ─────────────────────────────────────────────────────────────
function timeAgo(dateStr) {
    if (!dateStr) return '—';
    var diff = Date.now() - new Date(dateStr.replace(' ', 'T')).getTime();
    var mins = Math.floor(diff / 60000);
    if (mins < 1)  return 'Ahora';
    if (mins < 60) return 'Hace ' + mins + 'min';
    var hrs = Math.floor(mins / 60);
    if (hrs < 24)  return 'Hace ' + hrs + 'h';
    if (hrs < 48)  return 'Ayer';
    var days = Math.floor(hrs / 24);
    if (days < 7)  return 'Hace ' + days + 'd';
    return new Date(dateStr).toLocaleDateString('es-VE', {day:'2-digit', month:'2-digit'});
}
document.querySelectorAll('[data-ts]').forEach(function(el) {
    var ts = el.getAttribute('data-ts');
    if (ts) el.textContent = timeAgo(ts);
});

// ─────────────────────────────────────────────────────────────
// Exam list actions
// ─────────────────────────────────────────────────────────────
async function togglePublicar(id, activoActual, respondieron) {
    respondieron = respondieron || 0;
    var nuevoActivo = activoActual ? 0 : 1;

    // Confirmación contextual
    if (activoActual) {
        var conf = await Swal.fire({
            title: '¿Cerrar este examen?',
            html: 'Los pasantes <b>ya no podrán enviar</b> respuestas.' +
                  (respondieron > 0 ? '<br>Los <b>' + respondieron + '</b> resultado(s) registrado(s) se conservarán.' : ''),
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="ti ti-lock"></i> Cerrar examen',
            cancelButtonText: 'Cancelar'
        });
        if (!conf.isConfirmed) return;
    } else {
        var conf = await Swal.fire({
            title: respondieron > 0 ? '¿Reabrir este examen?' : '¿Publicar este examen?',
            text:  respondieron > 0
                ? 'Los pasantes podrán volver a enviar respuestas.'
                : 'Los pasantes recibirán una notificación.',
            icon: 'question', showCancelButton: true,
            confirmButtonColor: '#059669', cancelButtonColor: '#6b7280',
            confirmButtonText: respondieron > 0 ? 'Sí, reabrir' : 'Sí, publicar',
            cancelButtonText: 'Cancelar'
        });
        if (!conf.isConfirmed) return;
    }

    try {
        var csrfMetaPub = document.querySelector('meta[name="csrf-token"]');
        var res  = await fetch(URLROOT + '/examenes/publicar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMetaPub ? csrfMetaPub.getAttribute('content') : ''},
            body: JSON.stringify({id: id, activo: nuevoActivo})
        });
        var data = await res.json();
        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo actualizar' });
            return;
        }

        var card   = document.getElementById('ex-card-' + id);
        var btn    = document.getElementById('btn-pub-' + id);
        var accent = card && card.querySelector('.ex-exam-accent');
        var badge  = card && card.querySelector('.ex-badge');

        if (nuevoActivo) {
            // → Publicado
            if (accent) accent.style.background = '#059669';
            if (badge)  { badge.className = 'ex-badge ex-badge-pub'; badge.textContent = 'Publicado'; }
            if (btn) {
                btn.className = btn.className.replace('ex-btn-cerrar','').trim() + ' ex-btn-cerrar';
                btn.title = 'Cerrar examen';
                btn.querySelector('i').className = 'ti ti-lock';
            }
        } else {
            // → Cerrado o Borrador
            var esCerrado = respondieron > 0;
            if (accent) accent.style.background = esCerrado ? '#64748b' : '#d97706';
            if (badge) {
                badge.className = 'ex-badge ' + (esCerrado ? 'ex-badge-cerr' : 'ex-badge-draf');
                badge.textContent = esCerrado ? 'Cerrado' : 'Borrador';
            }
            if (btn) {
                btn.className = btn.className.replace('ex-btn-cerrar','').trim();
                btn.title = esCerrado ? 'Reabrir examen' : 'Publicar';
                btn.querySelector('i').className = 'ti ' + (esCerrado ? 'ti-lock-open' : 'ti-eye');
            }
        }
        if (btn) btn.setAttribute('onclick', 'togglePublicar(' + id + ',' + nuevoActivo + ',' + respondieron + ')');

    } catch(e) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
}

async function eliminarExamen(id) {
    var ok = await Swal.fire({
        title: '¿Eliminar examen?',
        text: 'Se eliminarán también todas las preguntas e intentos asociados.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
    });
    if (!ok.isConfirmed) return;
    try {
        var csrfMetaDel = document.querySelector('meta[name="csrf-token"]');
        var res  = await fetch(URLROOT + '/examenes/eliminar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMetaDel ? csrfMetaDel.getAttribute('content') : ''},
            body: JSON.stringify({id: id})
        });
        var data = await res.json();
        if (!data.success) { alert('Error: ' + (data.message || 'No se pudo eliminar')); return; }

        var card = document.getElementById('ex-card-' + id);
        if (card) {
            card.style.transition = 'opacity .3s,transform .3s';
            card.style.opacity    = '0';
            card.style.transform  = 'scale(0.95)';
            setTimeout(function() { card.remove(); }, 320);
        }
    } catch(e) { alert('Error de conexión.'); }
}

// ─────────────────────────────────────────────────────────────
// Modal: estado global
// ─────────────────────────────────────────────────────────────
var exPreguntas = [];  // [{ enunciado, tipo, puntos, opciones:[{texto,es_correcta}], fromBank }]
var exTabActual = 'config';
var exBankSeleccionadas = {};  // { bankIdx: true/false }

var BANCO_PREGUNTAS = [
    {
        enunciado: "¿Qué es un Sistema Operativo?",
        opciones: [
            { texto: "El hardware principal de una computadora", es_correcta: false },
            { texto: "Software que gestiona los recursos de hardware y provee servicios a los programas", es_correcta: true },
            { texto: "Un programa de ofimática para crear documentos", es_correcta: false },
            { texto: "La memoria RAM del sistema", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Cuál es la función principal de la CPU?",
        opciones: [
            { texto: "Almacenar datos permanentemente", es_correcta: false },
            { texto: "Conectar todos los dispositivos externos", es_correcta: false },
            { texto: "Ejecutar instrucciones y procesar datos del sistema", es_correcta: true },
            { texto: "Mostrar imágenes en el monitor", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué voltaje proporciona el cable AMARILLO de la fuente de alimentación ATX?",
        opciones: [
            { texto: "+5V", es_correcta: false },
            { texto: "+3.3V", es_correcta: false },
            { texto: "+12V", es_correcta: true },
            { texto: "-12V", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué voltaje proporciona el cable ROJO de la fuente de alimentación ATX?",
        opciones: [
            { texto: "+12V", es_correcta: false },
            { texto: "+5V", es_correcta: true },
            { texto: "+3.3V", es_correcta: false },
            { texto: "-5V", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué voltaje proporciona el cable NARANJA de la fuente de alimentación ATX?",
        opciones: [
            { texto: "+5V", es_correcta: false },
            { texto: "+12V", es_correcta: false },
            { texto: "+3.3V", es_correcta: true },
            { texto: "+1.5V", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué voltaje proporciona el cable AZUL de la fuente de alimentación ATX?",
        opciones: [
            { texto: "-5V", es_correcta: false },
            { texto: "+5V", es_correcta: false },
            { texto: "+12V", es_correcta: false },
            { texto: "-12V", es_correcta: true }
        ]
    },
    {
        enunciado: "¿Cuál es la función del cable VERDE (PS_ON) en un conector ATX?",
        opciones: [
            { texto: "Proveer +12V al procesador", es_correcta: false },
            { texto: "Encender la fuente al ser conectado a tierra (GND)", es_correcta: true },
            { texto: "Señal de sensor de temperatura", es_correcta: false },
            { texto: "Proveer -12V al sistema", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué es la RAM (Memoria de Acceso Aleatorio)?",
        opciones: [
            { texto: "Almacenamiento permanente para el sistema operativo", es_correcta: false },
            { texto: "Memoria volátil de trabajo donde se ejecutan los programas activos", es_correcta: true },
            { texto: "Procesador gráfico del sistema", es_correcta: false },
            { texto: "Fuente de alimentación secundaria", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué voltaje estándar suministra un puerto USB tipo A?",
        opciones: [
            { texto: "+12V", es_correcta: false },
            { texto: "+3.3V", es_correcta: false },
            { texto: "+5V", es_correcta: true },
            { texto: "+9V", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué significa POST en el contexto del arranque de una computadora?",
        opciones: [
            { texto: "Power Off Self Test", es_correcta: false },
            { texto: "Power-On Self-Test", es_correcta: true },
            { texto: "Processor Operating System Transfer", es_correcta: false },
            { texto: "Primary Output Storage Terminal", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Cuál es la principal diferencia entre un HDD y un SSD?",
        opciones: [
            { texto: "El SSD usa platos magnéticos giratorios; el HDD usa memoria flash", es_correcta: false },
            { texto: "El HDD usa memoria flash; el SSD usa platos magnéticos", es_correcta: false },
            { texto: "El SSD usa memoria flash sin partes móviles, siendo más rápido", es_correcta: true },
            { texto: "No hay diferencia, son equivalentes en velocidad", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Cuál es la función del BIOS/UEFI en una computadora?",
        opciones: [
            { texto: "Es el sistema operativo principal de la PC", es_correcta: false },
            { texto: "Firmware que inicializa hardware y arranca el sistema operativo", es_correcta: true },
            { texto: "Software de administración de archivos", es_correcta: false },
            { texto: "Controlador de red inalámbrica", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Cuál es la diferencia entre hardware y software?",
        opciones: [
            { texto: "El hardware son los programas, el software son los componentes físicos", es_correcta: false },
            { texto: "Ambos son lo mismo, solo difieren en nombre", es_correcta: false },
            { texto: "El hardware son los componentes físicos, el software son los programas", es_correcta: true },
            { texto: "El hardware es la pantalla, el software es el teclado", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué función cumple la tarjeta madre (motherboard) en una PC?",
        opciones: [
            { texto: "Procesar imágenes y vídeos en alta resolución", es_correcta: false },
            { texto: "Almacenar todos los datos del usuario", es_correcta: false },
            { texto: "Placa principal que interconecta todos los componentes del sistema", es_correcta: true },
            { texto: "Proveer energía eléctrica a todos los componentes", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué es una dirección IP?",
        opciones: [
            { texto: "Identificador físico grabado en la tarjeta de red (MAC)", es_correcta: false },
            { texto: "Dirección lógica que identifica un dispositivo en una red", es_correcta: true },
            { texto: "Protocolo de encriptación de datos", es_correcta: false },
            { texto: "Velocidad de conexión a Internet en Mbps", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Qué es una carpeta compartida en una red local (LAN)?",
        opciones: [
            { texto: "Un directorio local inaccesible para otros equipos", es_correcta: false },
            { texto: "Una carpeta accesible por múltiples usuarios autorizados a través de la red", es_correcta: true },
            { texto: "Una partición oculta del disco duro para copias de seguridad", es_correcta: false },
            { texto: "Un archivo comprimido en formato ZIP", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Cuál es la forma estándar de acceder a una carpeta compartida en Windows mediante la ruta de red (UNC)?",
        opciones: [
            { texto: "http://nombre-equipo/carpeta", es_correcta: false },
            { texto: "C:\\Red\\nombre-equipo\\carpeta", es_correcta: false },
            { texto: "\\\\nombre-equipo\\nombre-carpeta (o \\\\IP\\nombre-carpeta)", es_correcta: true },
            { texto: "ftp://carpeta@redlocal", es_correcta: false }
        ]
    },
    {
        enunciado: "¿Cuál es la diferencia principal entre conectar una impresora por USB vs. por Red (Ethernet/Wi-Fi)?",
        opciones: [
            { texto: "Por USB no requiere controladores; por Red sí", es_correcta: false },
            { texto: "Por USB solo se conecta a un equipo directo; por Red tiene su propia IP y es accesible por toda la red sin depender de una PC anfitriona", es_correcta: true },
            { texto: "La conexión USB permite imprimir a mayor distancia que la red Ethernet", es_correcta: false },
            { texto: "No hay ninguna diferencia en su funcionamiento", es_correcta: false }
        ]
    },
    {
        enunciado: "Para compartir en red una impresora conectada directamente por cable USB a una PC, ¿qué condición es necesaria?",
        opciones: [
            { texto: "La PC anfitriona debe permanecer encendida, conectada a la red y con el uso compartido de impresoras habilitado", es_correcta: true },
            { texto: "La impresora debe desconectarse del cable USB", es_correcta: false },
            { texto: "Todos los equipos deben usar exactamente la misma versión del sistema operativo", es_correcta: false },
            { texto: "La PC anfitriona debe estar apagada para no generar conflicto de puertos", es_correcta: false }
        ]
    }
];

// ─────────────────────────────────────────────────────────────
// Modal: abrir/cerrar/tabs
// ─────────────────────────────────────────────────────────────
function abrirModalExamen() {
    exPreguntas          = [];
    exBankSeleccionadas  = {};
    exTabActual          = 'config';

    document.getElementById('exm-titulo').value       = '';
    document.getElementById('exm-desc').value         = '';
    document.getElementById('exm-periodo').value      = '';
    document.getElementById('exm-fecha-inicio').value = '';
    document.getElementById('exm-fecha-fin').value    = '';

    renderBanco();
    renderConstructor();
    actualizarContador();
    cambiarTab('config');

    document.getElementById('exmOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function cerrarModalExamen() {
    document.getElementById('exmOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

function cambiarTab(tab) {
    exTabActual = tab;
    ['config','banco','constructor'].forEach(function(t) {
        document.getElementById('exm-pane-' + t).style.display  = (t === tab) ? '' : 'none';
        var btn = document.getElementById('tab-' + t);
        if (btn) btn.classList.toggle('active', t === tab);
    });
    if (tab === 'constructor') renderConstructor();
    if (tab === 'banco')       renderBanco();
}

function actualizarContador() {
    var el = document.getElementById('exm-preg-count');
    if (el) el.textContent = exPreguntas.length;
}

// ─────────────────────────────────────────────────────────────
// Banco de preguntas
// ─────────────────────────────────────────────────────────────
function renderBanco() {
    var grid = document.getElementById('exm-bank-grid');
    if (!grid) return;
    grid.innerHTML = '';

    BANCO_PREGUNTAS.forEach(function(q, idx) {
        var sel  = !!exBankSeleccionadas[idx];
        var item = document.createElement('label');
        item.className = 'exm-bank-item' + (sel ? ' selected' : '');
        item.innerHTML =
            '<input type="checkbox" class="exm-opcion-cb"' + (sel ? ' checked' : '') + '>' +
            '<div>' +
              '<div class="exm-bank-q">' + escapeHtml(q.enunciado) + '</div>' +
              '<div class="exm-bank-hint">' + q.opciones.length + ' opciones &bull; Opción múltiple</div>' +
            '</div>';
        item.querySelector('input').addEventListener('change', function() {
            toggleBankQ(idx, this.checked);
        });
        grid.appendChild(item);
    });
}

function toggleBankQ(idx, checked) {
    exBankSeleccionadas[idx] = checked;

    if (checked) {
        var bq = BANCO_PREGUNTAS[idx];
        exPreguntas.push({
            enunciado: bq.enunciado,
            tipo:      'opcion_multiple',
            puntos:    1,
            opciones:  bq.opciones.map(function(o) { return { texto: o.texto, es_correcta: o.es_correcta }; }),
            bankIdx:   idx
        });
    } else {
        exPreguntas = exPreguntas.filter(function(p) { return p.bankIdx !== idx; });
    }

    actualizarContador();
    var item = document.querySelectorAll('.exm-bank-item')[idx];
    if (item) item.classList.toggle('selected', checked);
}

function toggleSelAll() {
    var total    = BANCO_PREGUNTAS.length;
    var selCount = Object.values(exBankSeleccionadas).filter(Boolean).length;
    var selAll   = selCount < total;

    BANCO_PREGUNTAS.forEach(function(_, idx) {
        if (selAll !== !!exBankSeleccionadas[idx]) {
            toggleBankQ(idx, selAll);
        }
    });

    var txt = document.getElementById('exm-sel-all-txt');
    if (txt) txt.textContent = selAll ? 'Deseleccionar todas' : 'Seleccionar todas';
    renderBanco();
}

// ─────────────────────────────────────────────────────────────
// Constructor de preguntas
// ─────────────────────────────────────────────────────────────
var LETRAS = ['A','B','C','D','E','F'];

function renderConstructor() {
    var container = document.getElementById('exm-preguntas-list');
    if (!container) return;
    container.innerHTML = '';

    if (exPreguntas.length === 0) {
        container.innerHTML = '<div class="exm-empty-preg"><i class="ti ti-clipboard-list" style="font-size:2rem;display:block;margin-bottom:8px;color:#c4b5fd;"></i>Selecciona preguntas del banco o agrega las tuyas manualmente.</div>';
        return;
    }

    exPreguntas.forEach(function(preg, pi) {
        var card = document.createElement('div');
        card.className = 'exm-preg-card';
        card.id = 'exm-preg-' + pi;

        var opcionesHtml = '';
        (preg.opciones || []).forEach(function(op, oi) {
            opcionesHtml +=
                '<div class="exm-opcion-row" id="exm-op-' + pi + '-' + oi + '">' +
                  '<span class="exm-opcion-ltr">' + (LETRAS[oi] || oi + 1) + '</span>' +
                  '<input class="exm-opcion-input" type="text" placeholder="Texto de la opción" value="' + escapeAttr(op.texto) + '"' +
                         ' onchange="syncOpcion(' + pi + ',' + oi + ',this.value)">' +
                  '<input type="checkbox" class="exm-opcion-cb" title="Correcta" ' + (op.es_correcta ? 'checked' : '') +
                         ' onchange="selCorrect(' + pi + ',' + oi + ')">' +
                  '<button class="exm-opcion-del" onclick="eliminarOpcion(' + pi + ',' + oi + ')" title="Quitar opción"><i class="ti ti-x"></i></button>' +
                '</div>';
        });

        card.innerHTML =
            '<div class="exm-preg-head">' +
              '<div class="exm-preg-num">' + (pi + 1) + '</div>' +
              '<div class="exm-preg-meta" style="flex:1;min-width:0;">' +
                '<input class="exm-input" type="text" placeholder="Enunciado de la pregunta..." value="' + escapeAttr(preg.enunciado) + '"' +
                       ' onchange="syncPregunta(' + pi + ',\'enunciado\',this.value)">' +
                '<select class="exm-select" onchange="syncPregunta(' + pi + ',\'tipo\',this.value)">' +
                  '<option value="opcion_multiple"' + (preg.tipo === 'opcion_multiple' ? ' selected' : '') + '>Opción múltiple</option>' +
                  '<option value="verdadero_falso"' + (preg.tipo === 'verdadero_falso'  ? ' selected' : '') + '>V/F</option>' +
                '</select>' +
                '<input class="exm-input" type="number" value="' + (preg.puntos || 1) + '" min="1" max="10" title="Puntos"' +
                       ' onchange="syncPregunta(' + pi + ',\'puntos\',+this.value)">' +
              '</div>' +
              '<div class="exm-preg-actions">' +
                '<button class="exm-preg-btn" onclick="reorderUp(' + pi + ')" title="Subir"><i class="ti ti-arrow-up"></i></button>' +
                '<button class="exm-preg-btn" onclick="reorderDown(' + pi + ')" title="Bajar"><i class="ti ti-arrow-down"></i></button>' +
                '<button class="exm-preg-btn del" onclick="eliminarPregunta(' + pi + ')" title="Eliminar"><i class="ti ti-trash"></i></button>' +
              '</div>' +
            '</div>' +
            opcionesHtml +
            '<button class="exm-add-op" onclick="agregarOpcion(' + pi + ')"><i class="ti ti-plus"></i> Agregar opción</button>';

        container.appendChild(card);
    });
}

function agregarPregunta() {
    exPreguntas.push({
        enunciado: '', tipo: 'opcion_multiple', puntos: 1,
        opciones: [
            { texto: '', es_correcta: true  },
            { texto: '', es_correcta: false }
        ]
    });
    actualizarContador();
    renderConstructor();
    cambiarTab('constructor');
}

function eliminarPregunta(pi) {
    var preg = exPreguntas[pi];
    if (preg && preg.bankIdx !== undefined) {
        exBankSeleccionadas[preg.bankIdx] = false;
    }
    exPreguntas.splice(pi, 1);
    actualizarContador();
    renderConstructor();
}

function agregarOpcion(pi) {
    if (!exPreguntas[pi]) return;
    exPreguntas[pi].opciones.push({ texto: '', es_correcta: false });
    renderConstructor();
}

function eliminarOpcion(pi, oi) {
    if (!exPreguntas[pi]) return;
    exPreguntas[pi].opciones.splice(oi, 1);
    renderConstructor();
}

function selCorrect(pi, oi) {
    if (!exPreguntas[pi]) return;
    exPreguntas[pi].opciones.forEach(function(o, i) { o.es_correcta = (i === oi); });
    renderConstructor();
}

function syncOpcion(pi, oi, val) {
    if (exPreguntas[pi] && exPreguntas[pi].opciones[oi]) {
        exPreguntas[pi].opciones[oi].texto = val;
    }
}

function syncPregunta(pi, field, val) {
    if (exPreguntas[pi]) exPreguntas[pi][field] = val;
}

function reorderUp(pi) {
    if (pi <= 0) return;
    var tmp = exPreguntas[pi - 1];
    exPreguntas[pi - 1] = exPreguntas[pi];
    exPreguntas[pi]     = tmp;
    renderConstructor();
}

function reorderDown(pi) {
    if (pi >= exPreguntas.length - 1) return;
    var tmp = exPreguntas[pi + 1];
    exPreguntas[pi + 1] = exPreguntas[pi];
    exPreguntas[pi]     = tmp;
    renderConstructor();
}

// ─────────────────────────────────────────────────────────────
// Guardar examen
// ─────────────────────────────────────────────────────────────
function buildPayload(publicar) {
    return {
        titulo:              document.getElementById('exm-titulo').value.trim(),
        descripcion:         document.getElementById('exm-desc').value.trim(),
        periodo_id:          document.getElementById('exm-periodo').value || null,
        intentos_permitidos: 1, // [FIX-N-11] cada examen admite un único intento (examen_intentos.UNIQUE(examen_id, pasante_id))
        fecha_inicio:        document.getElementById('exm-fecha-inicio').value || null,
        fecha_fin:           document.getElementById('exm-fecha-fin').value || null,
        publicar:            publicar,
        preguntas:           exPreguntas.map(function(p) {
            return {
                enunciado: p.enunciado,
                tipo:      p.tipo,
                puntos:    p.puntos || 1,
                opciones:  (p.opciones || []).map(function(o) {
                    return { texto: o.texto, es_correcta: !!o.es_correcta };
                })
            };
        })
    };
}

function validarPayload(payload) {
    if (!payload.titulo) return 'El título es obligatorio.';
    if (!payload.preguntas.length) return 'Debes agregar al menos una pregunta.';
    for (var i = 0; i < payload.preguntas.length; i++) {
        var p   = payload.preguntas[i];
        var num = i + 1;
        if (!p.enunciado.trim()) return 'La pregunta #' + num + ' no tiene enunciado.';
        if (p.opciones.length < 2) return 'La pregunta #' + num + ' necesita al menos 2 opciones.';
        var correctas = p.opciones.filter(function(o) { return o.es_correcta; }).length;
        if (correctas !== 1) return 'La pregunta #' + num + ' debe tener exactamente 1 opción correcta.';
        for (var j = 0; j < p.opciones.length; j++) {
            if (!p.opciones[j].texto.trim()) return 'La opción ' + (j + 1) + ' de la pregunta #' + num + ' está vacía.';
        }
    }
    return null;
}

async function guardarExamen(publicar) {
    var payload = buildPayload(publicar);
    var error   = validarPayload(payload);
    if (error) {
        Swal.fire({ icon: 'warning', title: 'Validación', text: error });
        return;
    }

    var btnId  = publicar ? 'exm-btn-pub' : 'exm-btn-draft';
    var btn    = document.getElementById(btnId);
    var txtOri = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader-2"></i> Guardando...'; }

    try {
        var csrfMetaGuardar = document.querySelector('meta[name="csrf-token"]');
        var res  = await fetch(URLROOT + '/examenes/guardar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfMetaGuardar ? csrfMetaGuardar.getAttribute('content') : ''},
            body: JSON.stringify(payload)
        });
        var data = await res.json();

        if (!data.success) {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo guardar el examen.' });
            return;
        }

        await Swal.fire({
            icon: 'success',
            title: publicar ? '¡Examen publicado!' : 'Borrador guardado',
            text: 'Redirigiendo a los resultados...',
            timer: 1500, showConfirmButton: false
        });

        window.location.href = data.redirect || URLROOT + '/examenes';

    } catch(e) {
        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo contactar al servidor.' });
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = txtOri; }
    }
}

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────
function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escapeAttr(str) {
    return String(str).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
</script>
