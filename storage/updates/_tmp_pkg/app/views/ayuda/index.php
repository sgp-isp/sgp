<?php
/**
 * Vista: Manual de Usuario — SGP (Diseño SPA anti-scroll)
 */
$rolId    = Session::get('role_id') ?? Session::get('rol_id') ?? 0;
$esAdmin   = $rolId == 1 || $rolId == 0;
$esTutor   = $rolId == 2;
$esPasante = $rolId == 3 || $rolId == 0; // El SuperAdmin ve TODO

// Manual independiente (HTML) correspondiente al rol de la sesión actual
$manualesPorRol = [
    0 => 'manual_superadministrador.html',
    1 => 'manual_administrador.html',
    2 => 'manual_tutor.html',
    3 => 'manual_pasante.html',
];
$manualArchivo = $manualesPorRol[$rolId] ?? 'manual_pasante.html';
?>
<style>
/* ══ Manual SGP — Diseño SPA ═══════════════════════════════════ */
.man-wrap { width:100%; padding:20px 24px; }

/* Banner Principal — Estilo Bento Moderno */
.man-banner {
    background: linear-gradient(135deg, var(--color-primary, #172554) 0%, var(--gradient-mid, #1e3a8a) 50%, var(--color-accent-brand, #2563eb) 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 24px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(15, 23, 42, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

/* Efectos visuales de fondo (Ambient Glow suave) */
.man-banner::before {
    content: '';
    position: absolute;
    top: -60px;
    right: -60px;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
}
.man-banner::after {
    content: '';
    position: absolute;
    bottom: -80px;
    left: 28%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.07) 0%, rgba(255, 255, 255, 0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

/* Bloque Principal */
.man-banner-main {
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    z-index: 1;
    min-width: 0;
}

/* Caja de Ícono Frosted Glass */
.man-banner-icon-box {
    width: 60px;
    height: 60px;
    min-width: 60px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 1.9rem;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    transition: transform 0.25s ease;
    flex-shrink: 0;
}
.man-banner-icon-box:hover {
    transform: scale(1.05) rotate(4deg);
}

/* Textos y Jerarquía Visual */
.man-banner-text {
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
}

/* Badge contextual superior */
.man-banner-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    align-self: flex-start;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.95);
    margin-bottom: 6px;
}
.man-banner-badge i {
    font-size: 0.85rem;
    color: #93c5fd;
}
.man-banner-badge-sep {
    opacity: 0.5;
    font-size: 0.65rem;
}

/* Título */
.man-banner-title {
    color: #ffffff;
    font-size: 1.85rem;
    font-weight: 800;
    margin: 0 0 5px;
    letter-spacing: -0.5px;
    line-height: 1.2;
}

/* Subtítulo */
.man-banner-sub {
    color: rgba(255, 255, 255, 0.82);
    margin: 0;
    font-size: 0.92rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 7px;
    line-height: 1.4;
}
.man-banner-sub i {
    color: #60a5fa;
    font-size: 1rem;
    flex-shrink: 0;
}

/* Acciones / Botones */
.man-banner-actions {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
    flex-shrink: 0;
}

.man-btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    color: #ffffff;
    padding: 11px 22px;
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    text-decoration: none;
    font-family: inherit;
}
.man-btn-action:hover {
    transform: translateY(-2px);
}
.man-btn-action:active {
    transform: translateY(0);
}

.man-btn-pdf {
    background: #ffffff;
    color: var(--color-primary, #1e3a8a);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.9);
    font-weight: 800;
}
.man-btn-pdf:hover {
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
    color: var(--color-primary, #1e3a8a);
}
.man-btn-pdf i {
    font-size: 1.15rem;
    color: #dc2626;
    transition: transform 0.2s ease;
}
.man-btn-pdf:hover i {
    transform: scale(1.15);
}

.man-btn-manual {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    color: #ffffff;
}
.man-btn-manual:hover {
    background: rgba(255, 255, 255, 0.22);
    border-color: rgba(255, 255, 255, 0.38);
    color: #ffffff;
}
.man-btn-manual i {
    font-size: 1.15rem;
}

/* ── RESPONSIVE VISTA MÓVIL ────────────────────────────────── */
@media (max-width: 860px) {
    .man-wrap {
        padding: 16px 18px;
    }
    .man-banner {
        flex-direction: column;
        align-items: stretch;
        padding: 22px 20px;
        gap: 16px;
    }
    .man-banner-actions {
        width: 100%;
        justify-content: stretch;
    }
    .man-banner-actions .man-btn-action {
        flex: 1;
    }
}

@media (max-width: 576px) {
    .man-wrap {
        padding: 12px 14px;
    }
    .man-banner {
        padding: 18px 16px;
        border-radius: 18px;
        margin-bottom: 16px;
        gap: 14px;
    }
    .man-banner-main {
        gap: 12px;
    }
    .man-banner-icon-box {
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: 13px;
        font-size: 22px;
    }
    .man-banner-title {
        font-size: 1.35rem;
        line-height: 1.25;
    }
    .man-banner-sub {
        font-size: 0.8rem;
        margin-top: 4px;
    }
    .man-banner-actions {
        gap: 8px;
    }
    .man-btn-action {
        padding: 9px 14px;
        font-size: 0.82rem;
        min-height: 40px;
    }
    .man-search {
        margin-bottom: 15px;
    }
}

/* Optimización para pantallas ultra estrechas (320px - 380px) */
@media (max-width: 380px) {
    .man-wrap {
        padding: 10px 10px;
    }
    .man-banner {
        padding: 14px 12px;
        border-radius: 16px;
        gap: 12px;
    }
    .man-banner-main {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .man-banner-icon-box {
        width: 38px;
        height: 38px;
        min-width: 38px;
        font-size: 20px;
        border-radius: 11px;
    }
    .man-banner-badge {
        font-size: 0.65rem;
        padding: 2px 8px;
        margin-bottom: 4px;
    }
    .man-banner-title {
        font-size: 1.22rem;
        letter-spacing: -0.2px;
    }
    .man-banner-sub {
        font-size: 0.75rem;
        line-height: 1.35;
    }
    .man-banner-actions {
        flex-direction: column;
        width: 100%;
        gap: 7px;
    }
    .man-banner-actions .man-btn-action {
        width: 100%;
    }
}

/* ── MODO OSCURO (DATA-THEME="DARK") ────────────────────────── */
[data-theme="dark"] .man-banner {
    background: linear-gradient(135deg, #090d16 0%, #0f1c3f 50%, #1e3a8a 100%) !important;
    border: 1px solid #1e293b !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6) !important;
}
[data-theme="dark"] .man-banner-icon-box {
    background: rgba(255, 255, 255, 0.16) !important;
    border-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2) !important;
}
[data-theme="dark"] .man-banner-badge {
    background: rgba(255, 255, 255, 0.12) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: rgba(255, 255, 255, 0.95) !important;
}
[data-theme="dark"] .man-banner-badge i {
    color: #93c5fd !important;
}
[data-theme="dark"] .man-banner-title {
    color: #ffffff !important;
}
[data-theme="dark"] .man-banner-sub {
    color: rgba(255, 255, 255, 0.85) !important;
}
[data-theme="dark"] .man-banner-sub i {
    color: #60a5fa !important;
}
[data-theme="dark"] .man-btn-pdf {
    background: rgba(255, 255, 255, 0.16) !important;
    border: 1px solid rgba(255, 255, 255, 0.28) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3) !important;
}
[data-theme="dark"] .man-btn-pdf:hover {
    background: rgba(255, 255, 255, 0.26) !important;
    border-color: rgba(255, 255, 255, 0.45) !important;
    color: #ffffff !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4) !important;
}
[data-theme="dark"] .man-btn-manual {
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.22) !important;
    color: #ffffff !important;
}
[data-theme="dark"] .man-btn-manual:hover {
    background: rgba(255, 255, 255, 0.22) !important;
    border-color: rgba(255, 255, 255, 0.35) !important;
    color: #ffffff !important;
}

/* Modal "Manuales" (SuperAdmin) */
.man-modal-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.man-modal-btn {
    display:flex; flex-direction:column; align-items:flex-start; gap:4px;
    background:#f8fafc; border:2px solid #e2e8f0; border-radius:14px;
    padding:14px 16px; cursor:pointer; transition:all .2s; text-align:left;
    font-family:inherit;
}
.man-modal-btn:hover { border-color:#2563eb; background:#eff6ff; transform:translateY(-2px); }
.man-modal-btn i { font-size:1.4rem; color:#2563eb; margin-bottom:2px; }
.man-modal-btn strong { font-size:.95rem; color:#1e293b; font-weight:700; }
.man-modal-btn small { font-size:.78rem; color:#64748b; }
.man-modal-master { grid-column:1 / -1; background:#eff6ff; border-color:#bfdbfe; }

/* Search */
.man-search { position:relative; margin-bottom:20px; }
.man-search input {
    width:100%; padding:12px 44px 12px 44px; border:2px solid #e2e8f0;
    border-radius:14px; font-size:.95rem; background:#f8fafc;
    color:#0d1424; outline:none; transition:all .2s; font-family:inherit; box-sizing:border-box;
}
.man-search input:focus { border-color:#2563eb; background:white; box-shadow:0 0 0 4px rgba(37,99,235,.07); }
.man-search .si { position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.man-search .sc { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; display:none; font-size:1.1rem; }

/* Layout 2 columnas */
.man-layout { display:grid; grid-template-columns:230px 1fr; gap:20px; align-items:start; }
@media(max-width:860px){ .man-layout{ grid-template-columns:1fr; } }

/* TOC */
.man-toc { background:white; border-radius:18px; border:1px solid #e2e8f0; padding:16px; position:sticky; top:75px; }
.man-toc h4 { font-size:.68rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.8px; margin:0 0 10px; display:flex; align-items:center; gap:6px; }
.toc-link { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:10px; color:#475569; font-size:.82rem; font-weight:600; text-decoration:none; transition:all .2s; cursor:pointer; }
.toc-link:hover { background:#f1f5f9; color:#1e293b; }
.toc-link.activo { background:#eff6ff; color:#2563eb; }
.toc-link i { font-size:.95rem; flex-shrink:0; width:18px; }
.toc-sep { height:1px; background:#f1f5f9; margin:6px 0; }

/* Panel de contenido */
.man-panel { min-width:0; }

/* Secciones SPA — una a la vez */
.man-sec { display:none; background:white; border-radius:20px; border:1px solid #e2e8f0; overflow:hidden; }
.man-sec.active { display:block; animation:manIn .22s ease; }
@keyframes manIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

/* Cabecera de sección */
.man-sec-hd { padding:22px 26px 18px; display:flex; align-items:center; gap:14px; border-bottom:1px solid #f1f5f9; }
.man-sec-icon { width:44px; height:44px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:1.25rem; flex-shrink:0; }
.man-sec-title { font-size:1.1rem; font-weight:800; color:#0d1424; margin:0; }
.man-sec-sub { font-size:.78rem; color:#64748b; margin:3px 0 0; }

/* Cuerpo de sección */
.man-sec-body { padding:22px 26px; }

/* Elementos de contenido */
.man-p { color:#475569; font-size:.9rem; line-height:1.75; margin:0 0 14px; }
.man-h3 { font-size:.8rem; font-weight:800; color:#1e293b; text-transform:uppercase; letter-spacing:.5px; margin:18px 0 10px; display:flex; align-items:center; gap:7px; }
.man-h3 i { color:#2563eb; }
.man-steps { counter-reset:step; display:flex; flex-direction:column; gap:10px; margin-bottom:16px; }
.man-step { display:flex; gap:12px; align-items:flex-start; }
.man-step::before { counter-increment:step; content:counter(step); width:24px; height:24px; min-width:24px; background:#2563eb; color:white; border-radius:50%; font-size:.75rem; font-weight:800; display:flex; align-items:center; justify-content:center; margin-top:2px; }
.man-step p { color:#475569; font-size:.88rem; line-height:1.6; margin:0; }
.man-step strong { color:#1e293b; }
.man-tip { background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:12px 16px; margin-bottom:14px; display:flex; gap:10px; align-items:flex-start; }
.man-tip i { color:#2563eb; font-size:1.1rem; margin-top:2px; flex-shrink:0; }
.man-tip p { margin:0; font-size:.85rem; color:#1e40af; line-height:1.6; }
.man-warn { background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:12px 16px; margin-bottom:14px; display:flex; gap:10px; align-items:flex-start; }
.man-warn i { color:#d97706; font-size:1.1rem; margin-top:2px; flex-shrink:0; }
.man-warn p { margin:0; font-size:.85rem; color:#92400e; line-height:1.6; }
.man-table { width:100%; border-collapse:collapse; font-size:.85rem; margin-bottom:16px; }
.man-table th { background:#f8fafc; padding:9px 13px; text-align:left; font-size:.72rem; text-transform:uppercase; letter-spacing:.5px; color:#64748b; font-weight:700; border-bottom:2px solid #f1f5f9; }
.man-table td { padding:9px 13px; border-bottom:1px solid #f8fafc; color:#475569; }
.man-table tr:last-child td { border-bottom:none; }
.man-badge { display:inline-block; padding:2px 9px; border-radius:100px; font-size:.72rem; font-weight:700; }
.mb-green { background:#dcfce7; color:#166534; }
.mb-blue  { background:#dbeafe; color:#1e40af; }
.mb-amber { background:#fef3c7; color:#92400e; }
.mb-slate { background:#f1f5f9; color:#475569; }
.man-kbd { display:inline-block; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:5px; padding:2px 8px; font-size:.78rem; font-family:monospace; color:#374151; }

/* Navegación prev/next */
.man-nav { display:flex; justify-content:space-between; align-items:center; margin-top:16px; gap:12px; }
.man-nav-btn { display:flex; align-items:center; gap:8px; padding:10px 18px; border-radius:12px; border:1.5px solid #e2e8f0; background:white; color:#475569; font-weight:600; font-size:.85rem; cursor:pointer; transition:all .2s; }
.man-nav-btn:hover:not(:disabled) { border-color:#2563eb; color:#2563eb; background:#eff6ff; }
.man-nav-btn:disabled { opacity:.3; cursor:default; pointer-events:none; }
.man-nav-info { font-size:.78rem; color:#94a3b8; text-align:center; flex:1; }

/* Sin resultados */
#manNoResults { display:none; text-align:center; padding:60px 20px; color:#94a3b8; background:white; border-radius:18px; border:1px solid #f1f5f9; }

/* Modo búsqueda: muestra todas las secciones y ocupa el grid completo */
.man-panel.search-mode { grid-column: 1 / -1; }
.man-panel.search-mode .man-sec { display:block; margin-bottom:16px; }
.man-panel.search-mode .man-sec:last-child { margin-bottom:0; }
.man-panel.search-mode .man-nav { display:none; }

/* ══ Print / PDF ═══════════════════════════════════════════════ */
@media print {
    .man-sec { display:block !important; margin-bottom:28px; break-inside:avoid; }
    .man-banner { background:#172554 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .man-btn-pdf, .man-nav, .man-search, .man-toc,
    .main-sidebar, .topbar, #sidebarOverlay, .mobile-dock { display:none !important; }
    .man-layout { display:block !important; }
    .man-wrap { padding:0 !important; }
    .man-sec-body { overflow:visible !important; }
    .man-table th, .man-tip, .man-warn { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>

<div class="man-wrap">

<!-- BANNER -->
<div class="module-banner man-banner">
    <div class="man-banner-main">
        <div class="man-banner-icon-box" title="SGP — Manual y Guías">
            <i class="ti ti-lifebuoy"></i>
        </div>
        <div class="man-banner-text">
            <div class="man-banner-badge">
                <i class="ti ti-bookmarks"></i>
                <span>Documentación Oficial</span>
                <span class="man-banner-badge-sep">•</span>
                <span>SGP v2.0</span>
            </div>
            <h1 class="man-banner-title">Manual de Usuario</h1>
            <p class="man-banner-sub">
                <i class="ti ti-shield-check"></i>
                <span>Registro y Control de Asistencias — ISP Bolívar</span>
            </p>
        </div>
    </div>

    <div class="man-banner-actions">
        <?php if ($rolId == 0): ?>
        <button type="button" class="man-btn-action man-btn-manual" onclick="abrirModalManuales()" title="Ver todos los manuales del sistema">
            <i class="ti ti-files"></i>
            <span>Manuales</span>
        </button>
        <?php endif; ?>
        <button type="button" class="man-btn-action man-btn-pdf" onclick="window.open('<?= URLROOT ?>/manuales/<?= $manualArchivo ?>','_blank')" title="Descargar manual en PDF">
            <i class="ti ti-file-type-pdf"></i>
            <span>Descargar PDF</span>
        </button>
    </div>
</div>

<!-- BÚSQUEDA -->
<div class="man-search">
    <i class="ti ti-search si"></i>
    <input type="text" id="manSearch" placeholder="Buscar en el manual… Ej: crear pasante, asistencia, constancia" oninput="filtrarManual(this.value)">
    <button class="sc" id="manSearchClear" onclick="limpiarBusqueda()"><i class="ti ti-x"></i></button>
</div>
<div id="manNoResults">
    <i class="ti ti-search" style="font-size:3rem;display:block;margin-bottom:12px;opacity:.3;"></i>
    <div style="font-weight:700;">Sin resultados para esa búsqueda</div>
    <div style="font-size:.85rem;margin-top:6px;">Intenta con otra palabra clave</div>
</div>

<div class="man-layout">

<!-- ── TABLA DE CONTENIDO ──────────────────────────────────── -->
<div class="man-toc" id="manToc">
    <h4><i class="ti ti-list"></i> Contenido</h4>

    <a class="toc-link" data-sec="sec-inicio" onclick="mostrarSeccion('sec-inicio')"><i class="ti ti-home"></i> Inicio / Dashboard</a>
    <a class="toc-link" data-sec="sec-recuperacion" onclick="mostrarSeccion('sec-recuperacion')"><i class="ti ti-lock-question"></i> Recuperar Contraseña</a>
    <a class="toc-link" data-sec="sec-perfil" onclick="mostrarSeccion('sec-perfil')"><i class="ti ti-user-circle"></i> Mi Perfil</a>
    <?php if ($esAdmin || $esTutor): ?>
    <a class="toc-link" data-sec="sec-pasantes" onclick="mostrarSeccion('sec-pasantes')"><i class="ti ti-user-check"></i> Pasantes</a>
    <a class="toc-link" data-sec="sec-asistencias" onclick="mostrarSeccion('sec-asistencias')"><i class="ti ti-calendar-stats"></i> Asistencias</a>
    <a class="toc-link" data-sec="sec-puntualidad" onclick="mostrarSeccion('sec-puntualidad')"><i class="ti ti-clock-bolt"></i> Puntualidad</a>
    <a class="toc-link" data-sec="sec-evaluaciones" onclick="mostrarSeccion('sec-evaluaciones')"><i class="ti ti-star"></i> Evaluaciones</a>
    <a class="toc-link" data-sec="sec-examenes" onclick="mostrarSeccion('sec-examenes')"><i class="ti ti-pencil-question"></i> Exámenes</a>
    <a class="toc-link" data-sec="sec-reportes" onclick="mostrarSeccion('sec-reportes')"><i class="ti ti-printer"></i> Reportes</a>
    <a class="toc-link" data-sec="sec-analiticas" onclick="mostrarSeccion('sec-analiticas')"><i class="ti ti-chart-dots"></i> Analíticas</a>
    <?php endif; ?>
    <?php if ($esAdmin): ?>
    <a class="toc-link" data-sec="sec-usuarios" onclick="mostrarSeccion('sec-usuarios')"><i class="ti ti-users"></i> Usuarios</a>
    <a class="toc-link" data-sec="sec-periodos" onclick="mostrarSeccion('sec-periodos')"><i class="ti ti-calendar-month"></i> Períodos</a>
    <a class="toc-link" data-sec="sec-asignaciones" onclick="mostrarSeccion('sec-asignaciones')"><i class="ti ti-link"></i> Asignaciones</a>
    <a class="toc-link" data-sec="sec-actividades" onclick="mostrarSeccion('sec-actividades')"><i class="ti ti-briefcase"></i> Act. Extras</a>
    <a class="toc-link" data-sec="sec-respaldos" onclick="mostrarSeccion('sec-respaldos')"><i class="ti ti-database"></i> Respaldos</a>
    <a class="toc-link" data-sec="sec-bitacora" onclick="mostrarSeccion('sec-bitacora')"><i class="ti ti-clipboard-list"></i> Bitácora</a>
    <a class="toc-link" data-sec="sec-configuracion" onclick="mostrarSeccion('sec-configuracion')"><i class="ti ti-settings"></i> Configuración</a>
    <a class="toc-link" data-sec="sec-notificaciones" onclick="mostrarSeccion('sec-notificaciones')"><i class="ti ti-bell-ringing"></i> Notificaciones</a>
    <a class="toc-link" data-sec="sec-kiosco" onclick="mostrarSeccion('sec-kiosco')"><i class="ti ti-device-desktop"></i> Kiosco de Asistencia</a>
    <?php endif; ?>
    <?php if ($rolId == 0): ?>
    <a class="toc-link" data-sec="sec-permisos" onclick="mostrarSeccion('sec-permisos')"><i class="ti ti-shield-cog"></i> Permisos</a>
    <a class="toc-link" data-sec="sec-superadmin-ctrl" onclick="mostrarSeccion('sec-superadmin-ctrl')"><i class="ti ti-radar"></i> Sala de Control</a>
    <?php endif; ?>
    <?php if ($esPasante): ?>
    <div class="toc-sep"></div>
    <a class="toc-link" data-sec="sec-pasante-asist" onclick="mostrarSeccion('sec-pasante-asist')"><i class="ti ti-clock-check"></i> Mi Asistencia</a>
    <a class="toc-link" data-sec="sec-pasante-eval" onclick="mostrarSeccion('sec-pasante-eval')"><i class="ti ti-star"></i> Mis Evaluaciones</a>
    <a class="toc-link" data-sec="sec-pasante-examenes" onclick="mostrarSeccion('sec-pasante-examenes')"><i class="ti ti-pencil-check"></i> Mis Exámenes</a>
    <a class="toc-link" data-sec="sec-pasante-analiticas" onclick="mostrarSeccion('sec-pasante-analiticas')"><i class="ti ti-chart-line"></i> Mis Analíticas</a>
    <a class="toc-link" data-sec="sec-pasante-actividades" onclick="mostrarSeccion('sec-pasante-actividades')"><i class="ti ti-activity"></i> Mis Actividades</a>
    <a class="toc-link" data-sec="sec-pasante-const" onclick="mostrarSeccion('sec-pasante-const')"><i class="ti ti-file-certificate"></i> Mi Constancia</a>
    <?php endif; ?>
    <div class="toc-sep"></div>
    <a class="toc-link" data-sec="sec-tooltips" onclick="mostrarSeccion('sec-tooltips')"><i class="ti ti-help-circle"></i> Íconos de Ayuda</a>
    <a class="toc-link" data-sec="sec-accesos" onclick="mostrarSeccion('sec-accesos')"><i class="ti ti-keyboard"></i> Atajos y Accesos</a>
</div>

<!-- ── PANEL PRINCIPAL ──────────────────────────────────────── -->
<div class="man-panel" id="manPanel">

<!-- ═══ INICIO / DASHBOARD ═══ -->
<div class="man-sec" id="sec-inicio" data-keywords="inicio dashboard panel principal kpi estadísticas resumen">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#eff6ff;color:#2563eb;"><i class="ti ti-home"></i></div>
        <div><p class="man-sec-title">Inicio / Dashboard</p><p class="man-sec-sub">Panel principal con indicadores del sistema</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El dashboard es la primera pantalla que verás al iniciar sesión. Muestra un resumen del estado general del sistema en tiempo real.</p>
        <div class="man-h3"><i class="ti ti-layout-dashboard"></i> Indicadores (KPIs)</div>
        <table class="man-table">
            <thead><tr><th>Indicador</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Pasantes Activos</strong></td><td>Total de pasantes con pasantía en curso en el período actual</td></tr>
                <tr><td><strong>Asistencia Hoy</strong></td><td>Porcentaje de presentes registrados en el día</td></tr>
                <tr><td><strong>Período Activo</strong></td><td>Nombre y progreso del período académico vigente</td></tr>
                <tr><td><strong>Evaluaciones Pendientes</strong></td><td>Pasantes sin evaluación completada</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Los datos del dashboard se actualizan en tiempo real. Si no ves cambios, recarga la página con <span class="man-kbd">F5</span>.</p></div>
    </div>
</div>

<!-- ═══ RECUPERACIÓN DE CONTRASEÑA ═══ -->
<div class="man-sec" id="sec-recuperacion" data-keywords="recuperar contraseña olvidé preguntas seguridad reset correo verificación acceso bloqueado solicitar ayuda administrador">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fef2f2;color:#dc2626;"><i class="ti ti-lock-question"></i></div>
        <div><p class="man-sec-title">Recuperar Contraseña</p><p class="man-sec-sub">Flujo de 3 pasos con preguntas de seguridad</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Si olvidaste tu contraseña puedes recuperarla sin intervención del administrador, siempre que hayas configurado tus preguntas de seguridad en el primer acceso.</p>

        <div class="man-h3"><i class="ti ti-circle-number-1"></i> Paso 1 — Verificar correo</div>
        <div class="man-steps">
            <div class="man-step"><p>En la pantalla de inicio de sesión haz clic en <strong>¿Olvidaste tu contraseña?</strong></p></div>
            <div class="man-step"><p>Ingresa el correo electrónico con el que te registraste.</p></div>
            <div class="man-step"><p>Si el correo existe en el sistema, avanzarás automáticamente al paso 2.</p></div>
        </div>

        <div class="man-h3"><i class="ti ti-circle-number-2"></i> Paso 2 — Preguntas de seguridad</div>
        <div class="man-steps">
            <div class="man-step"><p>Responde las <strong>3 preguntas de seguridad</strong> que configuraste en tu primer acceso.</p></div>
            <div class="man-step"><p>Las respuestas deben coincidir exactamente con las que ingresaste originalmente.</p></div>
            <div class="man-step"><p>Si las 3 respuestas son correctas, avanzarás al paso 3.</p></div>
        </div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Si no recuerdas las respuestas, haz clic en <strong>"Solicitar ayuda al administrador"</strong>. El administrador recibirá la solicitud y podrá restablecer tu acceso manualmente.</p></div>

        <div class="man-h3"><i class="ti ti-circle-number-3"></i> Paso 3 — Nueva contraseña</div>
        <div class="man-steps">
            <div class="man-step"><p>Escribe tu nueva contraseña (mínimo 8 caracteres).</p></div>
            <div class="man-step"><p>Confírmala en el segundo campo.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Guardar</strong>. Serás redirigido al login.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Por seguridad, la página de preguntas no se puede navegar hacia atrás ni recargar sin empezar desde el paso 1.</p></div>
    </div>
</div>

<!-- ═══ MI PERFIL / PRIMER ACCESO ═══ -->
<div class="man-sec" id="sec-perfil" data-keywords="perfil usuario foto avatar cambiar imagen datos personales teléfono cargo preguntas seguridad cuenta wizard primer acceso PIN contraseña temporal">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f0fdf4;color:#059669;"><i class="ti ti-user-circle"></i></div>
        <div><p class="man-sec-title">Mi Perfil y Primer Acceso</p><p class="man-sec-sub">Datos personales, foto, seguridad y wizard de configuración inicial</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Desde el menú de perfil (esquina superior derecha → tu nombre) puedes ver y actualizar tu información personal, cambiar tu foto y gestionar las preguntas de seguridad.</p>

        <div class="man-h3"><i class="ti ti-camera"></i> Cambiar foto de perfil</div>
        <div class="man-steps">
            <div class="man-step"><p>Haz clic en tu nombre/avatar en la barra superior → <strong>Mi Perfil</strong>.</p></div>
            <div class="man-step"><p>Haz clic en el ícono de cámara que aparece sobre tu foto.</p></div>
            <div class="man-step"><p>Selecciona una imagen desde tu dispositivo (JPG o PNG).</p></div>
            <div class="man-step"><p>La foto se actualiza inmediatamente en toda la sesión.</p></div>
        </div>

        <div class="man-h3"><i class="ti ti-shield-lock"></i> Actualizar preguntas de seguridad</div>
        <p class="man-p">Puedes cambiar tus preguntas de seguridad en cualquier momento desde tu perfil. Es recomendable actualizarlas si crees que alguien más las conoce.</p>

        <div class="man-h3"><i class="ti ti-door-enter"></i> Wizard de primer acceso (usuarios nuevos)</div>
        <p class="man-p">Todos los usuarios nuevos completan un asistente de <strong>4 pasos</strong> antes de entrar al sistema por primera vez:</p>
        <table class="man-table">
            <thead><tr><th>Paso</th><th>Qué se configura</th><th>¿Quién lo ve?</th></tr></thead>
            <tbody>
                <tr><td><strong>1 — Nueva contraseña</strong></td><td>Cambiar la contraseña temporal asignada por el administrador</td><td>Usuarios creados por el admin</td></tr>
                <tr><td><strong>2 — Preguntas de seguridad</strong></td><td>Seleccionar y responder 3 preguntas para recuperación futura</td><td>Usuarios creados por el admin</td></tr>
                <tr><td><strong>3 — Datos personales</strong></td><td>Teléfono, fecha de nacimiento, género y cargo/área</td><td>Todos los roles</td></tr>
                <tr><td><strong>4 — Asignación</strong></td><td><strong>Admin/Tutor:</strong> departamento asignado. <strong>Pasante:</strong> institución de procedencia + PIN de 4 dígitos para el kiosco</td><td>Todos los roles</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si te registraste tú mismo (auto-registro), los pasos 1 y 2 aparecen como completados y el wizard empieza directamente en el paso 3.</p></div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>El wizard es obligatorio. No puedes saltarte ningún paso ni acceder al panel principal hasta completarlo.</p></div>
    </div>
</div>

<?php if ($esAdmin || $esTutor): ?>
<!-- ═══ PASANTES ═══ -->
<div class="man-sec" id="sec-pasantes" data-keywords="pasantes crear nuevo registro cédula carrera institución horas asignar departamento">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fef3c7;color:#d97706;"><i class="ti ti-user-check"></i></div>
        <div><p class="man-sec-title">Pasantes / Mis Pasantes</p><p class="man-sec-sub">Gestión de estudiantes en pasantía</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Este módulo centraliza la información académica y personal de cada estudiante en práctica.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p><strong>Alcance por rol:</strong> Los <strong>Tutores</strong> solo ven los pasantes que les han sido asignados por el Administrador en su departamento (sección <em>Mis Pasantes</em>). Si un pasante no aparece en tu listado, contacta al Administrador para que verifique la asignación.</p></div>
        <?php if ($esAdmin): ?>
        <div class="man-h3"><i class="ti ti-user-plus"></i> Registrar un pasante (Administrador)</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Pasantes</strong> y haz clic en <strong>+ Nuevo Pasante</strong>.</p></div>
            <div class="man-step"><p>Completa los datos personales: cédula, nombres, apellidos, correo y teléfono.</p></div>
            <div class="man-step"><p>Los campos <strong>correo</strong> y <strong>cédula</strong> se verifican en tiempo real — si ya existen, aparece un indicador rojo.</p></div>
            <div class="man-step"><p>Completa los datos académicos: institución, carrera, tutor asignado, departamento y horas meta.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Registrar Pasante</strong>.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Las horas meta por defecto son <strong>1440 horas</strong> para pasantía Regular y <strong>360 horas</strong> para Corta. Puedes ajustarlas en la ficha del pasante.</p></div>
        <?php endif; ?>
        <div class="man-h3"><i class="ti ti-eye"></i> Ficha del pasante</div>
        <p class="man-p">Al hacer clic en un pasante accedes a su ficha completa: datos personales, estado de horas, historial de asistencias, evaluaciones y acciones rápidas.</p>
    </div>
</div>

<!-- ═══ ASISTENCIAS ═══ -->
<div class="man-sec" id="sec-asistencias" data-keywords="asistencia registrar presente ausente tardanza almanaque justificado hora entrada manual marcar todos anular eliminar cambiar estado historial calendario kiosco auto-fill relleno automático horas completadas editar celda clickeable">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#ecfdf5;color:#059669;"><i class="ti ti-calendar-stats"></i></div>
        <div><p class="man-sec-title">Asistencias</p><p class="man-sec-sub">Registro diario, almanaque y gestión de estados</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El módulo de asistencias es el núcleo operativo del sistema. Registra la asistencia diaria de cada pasante y calcula las horas acumuladas automáticamente.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p><strong>Alcance por rol:</strong> Como <strong>Tutor</strong> solo puedes registrar y consultar la asistencia de los pasantes asignados a tu departamento. No verás pasantes de otros tutores ni de otros departamentos.</p></div>

        <div class="man-h3"><i class="ti ti-check"></i> Registrar asistencia del día</div>
        <div class="man-steps">
            <div class="man-step"><p>Abre el módulo <strong>Asistencias</strong>.</p></div>
            <div class="man-step"><p>Selecciona la fecha (por defecto es el día de hoy).</p></div>
            <div class="man-step"><p>Para cada pasante marca el estado: <span class="man-badge mb-green">Presente</span> <span class="man-badge mb-amber">Tardanza</span> <span class="man-badge mb-slate">Ausente</span> <span class="man-badge mb-blue">Justificado</span>.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Guardar</strong>.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>El sistema cuenta <strong>8 horas</strong> por cada día marcado como Presente o Tardanza. Las tardanzas no penalizan el conteo de horas.</p></div>

        <div class="man-h3"><i class="ti ti-checks"></i> Marcar Todos</div>
        <p class="man-p">El botón <strong>Marcar Todos</strong> (disponible en la tabla de asistencias del día) permite aplicar el mismo estado a todos los pasantes de la lista con un solo clic. Es útil para días en que el grupo completo asistió o estuvo ausente.</p>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Después de usar <em>Marcar Todos</em> puedes corregir individualmente los pasantes que tengan un estado diferente antes de guardar.</p></div>

        <div class="man-h3"><i class="ti ti-edit"></i> Registro manual de asistencia</div>
        <p class="man-p">El formulario de <strong>Registro Manual</strong> permite registrar o actualizar la asistencia de un pasante específico para cualquier fecha (incluyendo días anteriores). Campos disponibles:</p>
        <table class="man-table">
            <thead><tr><th>Campo</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Pasante</strong></td><td>Selector con búsqueda. Solo muestra pasantes del departamento.</td></tr>
                <tr><td><strong>Fecha</strong></td><td>Selector de calendario. Puede ser cualquier día hábil.</td></tr>
                <tr><td><strong>Estado</strong></td><td>Presente, Tardanza, Ausente o Justificado.</td></tr>
                <tr><td><strong>Motivo</strong></td><td>Campo opcional de texto para justificaciones.</td></tr>
                <tr><td><strong>Es retardo</strong></td><td>Checkbox para marcar que la entrada fue tardía (no afecta el conteo de horas).</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si el pasante ya tiene asistencia registrada en esa fecha, el formulario la <strong>actualiza</strong>; si no existe, la <strong>crea</strong>. No se generan duplicados.</p></div>

        <div class="man-h3"><i class="ti ti-ban"></i> Anular / Eliminar primer registro del día</div>
        <p class="man-p">Cada fila de la tabla de asistencias tiene un menú de opciones (ícono <i class="ti ti-dots-vertical" style="font-size:.9rem;"></i>). Desde ahí puedes:</p>
        <table class="man-table">
            <thead><tr><th>Acción</th><th>Efecto</th></tr></thead>
            <tbody>
                <tr><td><strong>Cambiar estado</strong></td><td>Abre un selector rápido para cambiar entre Presente / Tardanza / Ausente / Justificado sin reabrir el formulario completo.</td></tr>
                <tr><td><strong>Anular registro</strong></td><td>Marca el registro como anulado. El día deja de contar como asistencia y se descuentan las 8 horas asociadas.</td></tr>
            </tbody>
        </table>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>La anulación es reversible solo si vuelves a registrar la asistencia manualmente para esa fecha. La acción queda registrada en la Bitácora de Auditoría.</p></div>

        <div class="man-h3"><i class="ti ti-calendar-month"></i> Almanaque — Historial individual</div>
        <p class="man-p">El <strong>Almanaque</strong> es una vista de calendario mensual que muestra el historial completo de asistencias de un pasante específico. Para acceder:</p>
        <div class="man-steps">
            <div class="man-step"><p>En el módulo <strong>Asistencias</strong> o en la ficha del pasante, haz clic en el botón <strong>Almanaque</strong>.</p></div>
            <div class="man-step"><p>Navega entre meses con las flechas del calendario.</p></div>
            <div class="man-step"><p>Cada día muestra su estado con un color: <span class="man-badge mb-green">Verde</span> Presente, <span class="man-badge mb-amber">Amarillo</span> Tardanza, <span class="man-badge mb-slate">Gris</span> Ausente, <span class="man-badge mb-blue">Azul</span> Justificado.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Los días feriados registrados en el sistema aparecen resaltados en el almanaque. Los fines de semana no se contabilizan como días laborales.</p></div>

        <div class="man-h3"><i class="ti ti-pencil"></i> Justificar ausencia</div>
        <p class="man-p">Al marcar a un pasante como <strong>Justificado</strong>, puedes agregar un motivo en el campo de observaciones. Este motivo aparece en el reporte de asistencias y en la bitácora.</p>

        <div class="man-h3"><i class="ti ti-user-check"></i> Panel "Sin Marcar Hoy"</div>
        <p class="man-p">La sección <strong>Sin Marcar Hoy</strong> muestra únicamente los pasantes que todavía necesitan registro para el día actual. El sistema <strong>excluye automáticamente</strong> a quienes ya completaron su meta de horas de pasantía, evitando registros innecesarios.</p>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si un pasante desaparece de esta lista sin que hayas marcado su asistencia, es porque ya alcanzó las horas requeridas y su pasantía está completa.</p></div>

        <div class="man-h3"><i class="ti ti-wand"></i> Relleno automático de días (Auto-fill)</div>
        <p class="man-p">El sistema rellena automáticamente los días hábiles pasados que no tengan registro en el kiosco. La filosofía es <strong>asumir presencia</strong> por defecto:</p>
        <table class="man-table">
            <thead><tr><th>Situación</th><th>Estado auto-generado</th></tr></thead>
            <tbody>
                <tr><td>Día hábil sin marcaje en kiosco</td><td><span class="man-badge mb-green">Presente</span> (auto-fill)</td></tr>
                <tr><td>Día feriado no laborable</td><td><span class="man-badge mb-blue">Justificado</span> — Feriado no laborable</td></tr>
                <tr><td>Fin de semana</td><td>No se genera ningún registro</td></tr>
            </tbody>
        </table>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p><strong>Importante:</strong> Los registros auto-generados <strong>NO cuentan</strong> para el cálculo de horas acumuladas. Solo los marcajes reales del kiosco o registros manuales suman horas. El auto-fill sirve únicamente para mantener el historial visual completo.</p></div>

        <div class="man-h3"><i class="ti ti-pencil-minus"></i> Editar asistencia desde el Almanaque</div>
        <p class="man-p">En el Almanaque individual de un pasante (vista de calendario anual), los administradores pueden <strong>hacer clic en cualquier día pasado</strong> para editar su estado directamente sin abrir otro formulario:</p>
        <div class="man-steps">
            <div class="man-step"><p>Abre el <strong>Almanaque</strong> del pasante.</p></div>
            <div class="man-step"><p>Pasa el cursor sobre un día pasado — aparecerá el texto <strong>✏ Clic para editar</strong> en el tooltip.</p></div>
            <div class="man-step"><p>Haz clic en la celda. Se abre un modal con tres botones: <span class="man-badge mb-green">Presente</span> <span class="man-badge" style="background:#fee2e2;color:#b91c1c;">Ausente</span> <span class="man-badge mb-blue">Justificado</span>.</p></div>
            <div class="man-step"><p>Selecciona el estado deseado. Si eliges <strong>Justificado</strong>, escribe el motivo en el campo que aparece.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Guardar</strong>. La celda cambia de color inmediatamente.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Esta función está disponible solo para <strong>Admin</strong> y <strong>SuperAdmin</strong>. Los días futuros, fines de semana y celdas fuera del período no son editables.</p></div>
    </div>
</div>

<!-- ═══ PUNTUALIDAD ═══ -->
<div class="man-sec" id="sec-puntualidad" data-keywords="puntualidad retardos tardanza presente ausente justificado indicadores comportamiento reloj">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fff7ed;color:#ea580c;"><i class="ti ti-clock-bolt"></i></div>
        <div><p class="man-sec-title">Puntualidad</p><p class="man-sec-sub">Seguimiento del comportamiento de asistencia</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El módulo de puntualidad consolida el historial de asistencia de cada pasante mostrando días presentes, retardos, ausencias y justificaciones con sus marcas de tiempo exactas.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p><strong>Alcance por rol:</strong> Los <strong>Tutores</strong> solo pueden consultar la puntualidad de los pasantes asignados a su departamento.</p></div>
        <div class="man-h3"><i class="ti ti-chart-bar"></i> Indicadores de puntualidad</div>
        <table class="man-table">
            <thead><tr><th>Indicador</th><th>Color</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Presente</strong></td><td><span class="man-badge mb-green">Verde</span></td><td>El pasante llegó a tiempo</td></tr>
                <tr><td><strong>Tardanza / Retardo</strong></td><td><span class="man-badge mb-amber">Amarillo</span></td><td>Llegó después de la hora límite establecida</td></tr>
                <tr><td><strong>Ausente</strong></td><td><span class="man-badge mb-slate">Gris</span></td><td>No se registró asistencia ese día</td></tr>
                <tr><td><strong>Justificado</strong></td><td><span class="man-badge mb-blue">Azul</span></td><td>Ausencia con motivo registrado</td></tr>
            </tbody>
        </table>
        <div class="man-h3"><i class="ti ti-clock-hour-4"></i> Ver retardos con hora exacta</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve al módulo <strong>Puntualidad</strong>.</p></div>
            <div class="man-step"><p>Selecciona el pasante o usa el filtro por departamento.</p></div>
            <div class="man-step"><p>Haz clic en el contador de <strong>Retardos</strong> para ver el desglose con la hora exacta de cada entrada tardía.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>El módulo de puntualidad es de solo lectura. Para corregir un registro, ve a <strong>Asistencias</strong> y edita la entrada correspondiente.</p></div>
    </div>
</div>
<?php endif; ?>

<?php if ($esAdmin): ?>
<!-- ═══ USUARIOS ═══ -->
<div class="man-sec" id="sec-usuarios" data-keywords="usuarios crear usuario contraseña rol admin tutor pasante registro cuenta">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f0fdf4;color:#059669;"><i class="ti ti-users"></i></div>
        <div><p class="man-sec-title">Gestión de Usuarios</p><p class="man-sec-sub">Cuentas, roles y accesos al sistema</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Desde este módulo el administrador puede crear, editar y desactivar cuentas del sistema. Existen tres roles: <span class="man-badge mb-blue">Administrador</span> <span class="man-badge mb-green">Tutor</span> <span class="man-badge mb-amber">Pasante</span></p>
        <div class="man-h3"><i class="ti ti-user-plus"></i> Crear un nuevo usuario</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Usuarios</strong> en el menú lateral.</p></div>
            <div class="man-step"><p>Haz clic en el botón <strong>+ Nuevo Usuario</strong>.</p></div>
            <div class="man-step"><p>Completa: cédula, nombre, apellido, correo, rol y contraseña temporal.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Guardar</strong>. El usuario recibirá un correo si el sistema tiene SMTP configurado.</p></div>
        </div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>La cédula y el correo deben ser únicos en el sistema. Si ya existen, verás un mensaje de error en rojo.</p></div>
        <div class="man-h3"><i class="ti ti-lock"></i> Roles y permisos</div>
        <table class="man-table">
            <thead><tr><th>Rol</th><th>Acceso</th></tr></thead>
            <tbody>
                <tr><td><span class="man-badge mb-blue">Administrador</span></td><td>Acceso total al sistema: usuarios, pasantes, períodos, reportes, configuración</td></tr>
                <tr><td><span class="man-badge mb-green">Tutor</span></td><td>Asistencias, evaluaciones, sus pasantes asignados y reportes básicos</td></tr>
                <tr><td><span class="man-badge mb-amber">Pasante</span></td><td>Solo su perfil, asistencia personal, evaluaciones recibidas y constancia</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ PERÍODOS ═══ -->
<div class="man-sec" id="sec-periodos" data-keywords="período cohorte académico crear activo planificado cerrar activar pasantes asignar">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fdf4ff;color:#7c3aed;"><i class="ti ti-calendar-month"></i></div>
        <div><p class="man-sec-title">Períodos Académicos</p><p class="man-sec-sub">Gestión de cohortes y ciclos de pasantía</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Los períodos agrupan a los pasantes en cohortes. Cada período tiene un ciclo de vida: <span class="man-badge mb-amber">Planificado</span> → <span class="man-badge mb-green">Activo</span> → <span class="man-badge mb-slate">Cerrado</span></p>
        <div class="man-h3"><i class="ti ti-plus"></i> Crear un período</div>
        <div class="man-steps">
            <div class="man-step"><p>Haz clic en <strong>+ Nuevo Período</strong>.</p></div>
            <div class="man-step"><p>Selecciona el tipo: <strong>Regular</strong> (9 meses) o <strong>Corto</strong> (3 meses / Actividades Extras).</p></div>
            <div class="man-step"><p>Ingresa nombre y fecha de inicio (la fecha de fin se calcula automáticamente).</p></div>
            <div class="man-step"><p>Elige el estado inicial: <strong>Planificado</strong> o <strong>Activo</strong>.</p></div>
        </div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Solo puede existir un período Activo por tipo al mismo tiempo. Activar uno cerrará automáticamente el anterior del mismo tipo.</p></div>
        <div class="man-h3"><i class="ti ti-lock"></i> Cerrar un período</div>
        <p class="man-p">Al cerrar un período se marcan todos los pasantes como <strong>Finalizados</strong>. Solo se puede cerrar desde la vista de detalle del período.</p>
        <div class="man-h3"><i class="ti ti-file-certificate"></i> Constancia de culminación</div>
        <p class="man-p">El botón <strong>Constancia</strong> en la tarjeta de cada pasante genera el PDF oficial. Solo está disponible cuando el período está <span class="man-badge mb-slate">Cerrado</span> y el pasante tiene el 100% de sus horas.</p>
    </div>
</div>

<!-- ═══ ASIGNACIONES ═══ -->
<div class="man-sec" id="sec-asignaciones" data-keywords="asignaciones tutor departamento pasante asignar vincular">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="ti ti-link"></i></div>
        <div><p class="man-sec-title">Asignaciones</p><p class="man-sec-sub">Vincular pasantes con tutores y departamentos</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Las asignaciones definen la relación Pasante ↔ Tutor ↔ Departamento. Un pasante debe estar asignado para que su tutor pueda verlo.</p>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Asignaciones</strong>.</p></div>
            <div class="man-step"><p>Usa el buscador para encontrar al pasante.</p></div>
            <div class="man-step"><p>Selecciona el <strong>tutor</strong> y el <strong>departamento</strong> destino.</p></div>
            <div class="man-step"><p>Guarda. El tutor verá al pasante en su módulo desde ese momento.</p></div>
        </div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Un pasante sin asignación no aparece en el listado de ningún tutor, aunque sí exista en el sistema.</p></div>
    </div>
</div>

<!-- ═══ ACTIVIDADES EXTRAS ═══ -->
<div class="man-sec" id="sec-actividades" data-keywords="actividades extras servicio comunitario pasantía corta participantes crear almanaque asistencia horas feriados evaluación evidencia inasistencias chip selector wizard">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f0fdf4;color:#059669;"><i class="ti ti-briefcase"></i></div>
        <div><p class="man-sec-title">Actividades Extras</p><p class="man-sec-sub">Módulo autónomo: Servicio Comunitario y Pasantías Cortas</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Este módulo agrupa dos sub-módulos <strong>completamente independientes</strong> del flujo de pasantías regulares: <strong>Servicio Comunitario</strong> (brigadas, mantenimiento, actividades grupales) y <strong>Pasantías Cortas</strong> (pasantes de ~3 meses con cuenta propia). Ninguno de los dos aparece en los módulos de Asistencias, Evaluaciones ni Reportes regulares.</p>

        <!-- ── SERVICIO COMUNITARIO ── -->
        <div class="man-h3"><i class="ti ti-hearts"></i> Sub-módulo: Servicio Comunitario</div>
        <p class="man-p">Registra actividades grupales donde estudiantes de instituciones externas realizan brigadas de servicio. Los participantes <strong>no necesitan cuenta en el sistema</strong>.</p>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-plus"></i> Crear una nueva actividad (modal de 2 pasos)</div>
        <div class="man-steps">
            <div class="man-step"><p><strong>Paso 1:</strong> Clic en <strong>+ Nueva Actividad</strong>. Completa: nombre, tipo (Servicio Comunitario / Mantenimiento / Otro), institución de origen y fechas.</p></div>
            <div class="man-step"><p><strong>Paso 2 (deslizante):</strong> El modal avanza automáticamente a la pantalla de <strong>integrantes del grupo</strong>. Haz clic en <strong>Agregar</strong> para incluir participantes con nombre, apellido y cédula. La cédula se verifica en tiempo real (si ya existe en el sistema aparece un aviso en amarillo).</p></div>
            <div class="man-step"><p>Clic en <strong>Crear Actividad</strong>. Los integrantes se registran automáticamente junto con la actividad.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>También puedes agregar integrantes <strong>después</strong> de crear la actividad desde el botón <span class="man-kbd">Agregar</span> en la vista de detalle.</p></div>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-layout-list"></i> Gestión de integrantes en la vista de detalle</div>
        <p class="man-p">Al entrar al detalle de una actividad (<strong>tab Participantes</strong>) verás las tarjetas de cada integrante con botones de acción:</p>
        <table class="man-table">
            <thead><tr><th>Botón</th><th>Función</th></tr></thead>
            <tbody>
                <tr><td><i class="ti ti-pencil"></i> <strong>Editar</strong></td><td>Modifica nombre, apellidos, carrera y teléfono del integrante.</td></tr>
                <tr><td><i class="ti ti-trash"></i> <strong>Eliminar</strong></td><td>Elimina el integrante de esta actividad (pide confirmación).</td></tr>
                <tr><td><i class="ti ti-photo-up"></i> <strong>Subir Evidencia</strong></td><td>Adjunta fotos (JPG/PNG) o documentos PDF como respaldo de la actividad.</td></tr>
            </tbody>
        </table>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-photo"></i> Tab Evidencias</div>
        <p class="man-p">El tab <strong>Evidencias</strong> muestra todas las fotos y PDFs subidos. Las imágenes se ven como galería en miniatura; los PDFs muestran un ícono de archivo. Haz clic para abrirlos en nueva pestaña. Tamaño máximo: <strong>5 MB por archivo</strong>.</p>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-calendar-check"></i> Tab Control de Asistencia</div>
        <p class="man-p">Selecciona la fecha del día y marca el estado (Presente / Ausente / Justificado) para cada integrante. La asistencia se registra por fecha y participante.</p>

        <!-- ── PASANTÍAS CORTAS ── -->
        <div class="man-h3"><i class="ti ti-user-check"></i> Sub-módulo: Pasantías Cortas</div>
        <p class="man-p">Los pasantes cortos tienen <strong>cuenta propia</strong> en el sistema (usuario + contraseña), control de asistencia con gráficas, evaluaciones ISP y PDF de historial. Solo aparecen en el módulo Act. Extras.</p>
        <table class="man-table">
            <thead><tr><th>Característica</th><th>Pasante Corto</th><th>Participante Comunitario</th></tr></thead>
            <tbody>
                <tr><td>Cuenta en el sistema</td><td>✓ Sí (rol pasante)</td><td>✗ No</td></tr>
                <tr><td>Control de asistencia</td><td>✓ Almanaque + gráficas</td><td>✓ Por fecha</td></tr>
                <tr><td>Evaluación ISP</td><td>✓ 14 criterios</td><td>✗ No aplica</td></tr>
                <tr><td>PDF de historial</td><td>✓ Kardex completo</td><td>✗ No</td></tr>
                <tr><td>Aparece en módulos globales</td><td>✗ Solo Act. Extras</td><td>✗ Solo Act. Extras</td></tr>
            </tbody>
        </table>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-user-plus"></i> Registrar un Pasante Corto</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Act. Extras → Pasantías Cortas</strong> y clic en <strong>+ Nuevo Pasante</strong>.</p></div>
            <div class="man-step"><p>Completa: nombre, cédula, correo, institución, tutor, departamento, fecha de inicio y horas meta (default 480h).</p></div>
            <div class="man-step"><p>Al guardar, el sistema crea la cuenta automáticamente y muestra las <strong>credenciales temporales</strong>. Anótalas para entregarlas al pasante. La contraseña inicial es la <strong>cédula</strong>.</p></div>
        </div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>El pasante deberá cambiar su contraseña en el primer inicio de sesión. Si olvidó su contraseña, un administrador puede restablecerla desde el módulo de Usuarios.</p></div>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-grid-dots"></i> Selector de pasante (chips)</div>
        <p class="man-p">En la sección de <strong>Asistencias</strong>, los pasantes cortos se muestran como <strong>tarjetas interactivas</strong> (chips). Haz clic en una tarjeta para seleccionar al pasante y ver su asistencia. Cada tarjeta muestra el nombre, departamento y barra de progreso de horas. Clic en <span class="man-kbd">×</span> para deseleccionar.</p>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-calendar-check"></i> Marcar asistencia</div>
        <div class="man-steps">
            <div class="man-step"><p>Selecciona el pasante haciendo clic en su tarjeta chip.</p></div>
            <div class="man-step"><p>Elige la vista: <strong>Diaria</strong>, <strong>Semanal</strong> o <strong>Mensual</strong> (gráficas del período completo).</p></div>
            <div class="man-step"><p>Clic en <span class="man-kbd">+ Marcar</span>, selecciona fecha y estado: <strong>Presente</strong> (8h), <strong>Justificado</strong> (8h), <strong>Retardo</strong> (8h), <strong>Ausente</strong> (0h).</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Los días sin registro se rellenan automáticamente como <strong>Ausente</strong> al ejecutar el cierre diario. Los feriados no laborables registrados en Configuración se marcan como <strong>Justificado</strong> automáticamente.</p></div>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-clipboard-check"></i> Evaluación ISP del Pasante Corto</div>
        <p class="man-p">Desde el almanaque individual (<span class="man-kbd"><i class="ti ti-eye"></i></span> en la tabla), ve al tab <strong>Evaluaciones</strong>:</p>
        <div class="man-steps">
            <div class="man-step"><p>Clic en <strong>Registrar Evaluación</strong>.</p></div>
            <div class="man-step"><p>Califica los 14 criterios ISP usando estrellas (1-5). Un badge de color indica la columna que se marcará en el reporte: <strong style="color:#15803d;">★★★★★ Excelente</strong> · <strong style="color:#059669;">★★★★ Sobre Promedio</strong> · <strong style="color:#b45309;">★★★ Promedio</strong> · <strong style="color:#dc2626;">★★ Bajo Promedio</strong> · <strong style="color:#b91c1c;">★ Deficiente</strong>.</p></div>
            <div class="man-step"><p>Clic en <strong>Guardar Evaluación</strong>. El promedio aparece en el historial del tab.</p></div>
        </div>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-file-type-pdf"></i> PDF de Historial</div>
        <p class="man-p">En el almanaque individual, haz clic en el botón <span class="man-kbd"><i class="ti ti-file-type-pdf"></i> Descargar PDF</span> para obtener un resumen completo: datos del pasante, progreso de horas, estadísticas de asistencia, evaluaciones registradas e historial de los últimos 50 días.</p>

        <div class="man-h3" style="font-size:.8rem;padding-left:12px;"><i class="ti ti-user-x"></i> Panel de Inasistencias del Mes</div>
        <p class="man-p">Al final de la vista de Pasantías Cortas aparece el panel <strong>Inasistencias — [mes actual]</strong> con la lista de pasantes cortos activos que tienen al menos una ausencia este mes. Desde este panel puedes:</p>
        <table class="man-table">
            <thead><tr><th>Botón</th><th>Acción</th></tr></thead>
            <tbody>
                <tr><td><span class="man-kbd"><i class="ti ti-calendar-plus"></i> Marcar</span></td><td>Selecciona al pasante y abre el modal de marcado de asistencia directamente.</td></tr>
                <tr><td><span class="man-kbd"><i class="ti ti-eye"></i> Ver</span></td><td>Abre el almanaque individual del pasante.</td></tr>
            </tbody>
        </table>

        <div class="man-h3"><i class="ti ti-building-bank"></i> Instituciones</div>
        <p class="man-p">Gestiona el catálogo de instituciones externas desde <strong>Act. Extras → Instituciones</strong>. Aparecen como opciones al crear actividades de Servicio Comunitario o registrar pasantes cortos.</p>
    </div>
</div>

<!-- ═══ RESPALDOS ═══ -->
<div class="man-sec" id="sec-respaldos" data-keywords="respaldo backup restaurar base de datos exportar SQL archivo">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f1f5f9;color:#475569;"><i class="ti ti-database"></i></div>
        <div><p class="man-sec-title">Respaldos</p><p class="man-sec-sub">Copias de seguridad de la base de datos</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El módulo de respaldos permite crear y restaurar copias de seguridad completas de la base de datos en formato SQL.</p>
        <div class="man-h3"><i class="ti ti-download"></i> Crear respaldo</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Respaldos</strong>.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Crear Respaldo Ahora</strong>.</p></div>
            <div class="man-step"><p>El archivo .sql se genera y guarda en el servidor. También puedes descargarlo.</p></div>
        </div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Al <strong>restaurar</strong> un respaldo se sobrescribirán todos los datos actuales. Esta acción es irreversible. El sistema genera un respaldo previo automático antes de restaurar.</p></div>
    </div>
</div>

<!-- ═══ BITÁCORA ═══ -->
<div class="man-sec" id="sec-bitacora" data-keywords="bitácora auditoría historial acciones logs eventos seguridad">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f1f5f9;color:#64748b;"><i class="ti ti-clipboard-list"></i></div>
        <div><p class="man-sec-title">Bitácora de Auditoría</p><p class="man-sec-sub">Historial completo de acciones del sistema</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">La bitácora registra automáticamente cada acción relevante: inicios de sesión, creaciones, modificaciones y eliminaciones. Es de solo lectura.</p>
        <table class="man-table">
            <thead><tr><th>Campo</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Fecha/Hora</strong></td><td>Cuándo ocurrió la acción</td></tr>
                <tr><td><strong>Usuario</strong></td><td>Quién realizó la acción</td></tr>
                <tr><td><strong>Acción</strong></td><td>Tipo de evento (login, crear, editar, eliminar)</td></tr>
                <tr><td><strong>Módulo</strong></td><td>En qué sección del sistema ocurrió</td></tr>
                <tr><td><strong>Detalle</strong></td><td>Descripción específica del cambio</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ CONFIGURACIÓN ═══ -->
<div class="man-sec" id="sec-configuracion" data-keywords="configuración sistema institución nombre SMTP correo ajustes feriados festivos días no laborables calendario laborable">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f8fafc;color:#475569;"><i class="ti ti-settings"></i></div>
        <div><p class="man-sec-title">Configuración</p><p class="man-sec-sub">Ajustes generales y calendario de feriados</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Permite personalizar el comportamiento general del SGP: nombre de la institución, logo, configuración de correo SMTP, parámetros de pasantía y el calendario de días feriados.</p>
        <table class="man-table">
            <thead><tr><th>Ajuste</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Nombre institución</strong></td><td>Aparece en PDFs y encabezados del sistema</td></tr>
                <tr><td><strong>Horas por defecto</strong></td><td>Horas meta asignadas automáticamente a nuevos pasantes</td></tr>
                <tr><td><strong>SMTP</strong></td><td>Servidor de correo para notificaciones automáticas</td></tr>
            </tbody>
        </table>

        <div class="man-h3"><i class="ti ti-calendar-off"></i> Calendario de Días Feriados</div>
        <p class="man-p">El sistema incluye un calendario para registrar los días festivos nacionales e institucionales. Los días feriados afectan directamente el módulo de asistencias y el almanaque:</p>
        <table class="man-table">
            <thead><tr><th>Tipo de día</th><th>Efecto en asistencias</th></tr></thead>
            <tbody>
                <tr><td><span class="man-badge mb-slate">No laborable</span></td><td>El sistema no permite registrar asistencia ese día. Aparece marcado en el almanaque como día feriado.</td></tr>
                <tr><td><span class="man-badge mb-green">Laborable</span></td><td>El sistema permite registrar asistencia ese día aunque sea festivo (p. ej., jornadas especiales o recuperación de clases).</td></tr>
            </tbody>
        </table>
        <div class="man-h3"><i class="ti ti-plus"></i> Agregar un feriado</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Configuración → Calendario de Feriados</strong>.</p></div>
            <div class="man-step"><p>Haz clic en <strong>+ Agregar Feriado</strong>.</p></div>
            <div class="man-step"><p>Selecciona la fecha y escribe el nombre del feriado (p. ej., "Día de la Independencia").</p></div>
            <div class="man-step"><p>Elige si el día será <strong>Laborable</strong> (se puede marcar asistencia) o <strong>No laborable</strong> (no se puede).</p></div>
            <div class="man-step"><p>Guarda. El feriado aparece inmediatamente en el almanaque de todos los pasantes.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si se acerca un feriado no laborable, el sistema enviará una <strong>notificación automática</strong> al Administrador cuando inicie sesión, avisando con antelación.</p></div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Eliminar un feriado que ya tenía asistencias registradas no elimina esas asistencias — solo deja de marcarlo como día especial en el almanaque.</p></div>
    </div>
</div>
<?php endif; ?>

<?php if ($esAdmin || $esTutor): ?>
<!-- ═══ EVALUACIONES ═══ -->
<div class="man-sec" id="sec-evaluaciones" data-keywords="evaluaciones calificar rendimiento planilla ISP ítems criterios estrellas equivalencia excelente promedio deficiente columna reporte">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fdf2f8;color:#ec4899;"><i class="ti ti-star"></i></div>
        <div><p class="man-sec-title">Evaluaciones</p><p class="man-sec-sub">Planilla institucional de 14 criterios ISP con escala de equivalencias</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El módulo de evaluaciones usa la planilla oficial del ISP Bolívar con <strong>14 criterios de desempeño</strong> calificados con estrellas del 1 al 5. Cada calificación se traduce automáticamente a una columna específica del reporte PDF oficial.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p><strong>Alcance por rol:</strong> Los <strong>Tutores</strong> solo evalúan a pasantes de su departamento. Los <strong>Administradores</strong> ven todas las evaluaciones del sistema.</p></div>

        <div class="man-h3"><i class="ti ti-star-filled"></i> Equivalencia estrellas → Columna del reporte ISP</div>
        <p class="man-p">Al calificar cada criterio con estrellas, un <strong>badge de color</strong> aparece debajo indicando la columna exacta que se marcará con «X» en el reporte impreso:</p>
        <table class="man-table">
            <thead><tr><th>Estrellas</th><th>Columna en el reporte</th><th>Color indicador</th></tr></thead>
            <tbody>
                <tr><td>★★★★★ (5)</td><td><strong>Excelente</strong></td><td style="color:#15803d;font-weight:700;">● Verde oscuro</td></tr>
                <tr><td>★★★★☆ (4)</td><td><strong>Sobre Promedio</strong></td><td style="color:#059669;font-weight:700;">● Verde</td></tr>
                <tr><td>★★★☆☆ (3)</td><td><strong>Promedio</strong></td><td style="color:#b45309;font-weight:700;">● Amarillo</td></tr>
                <tr><td>★★☆☆☆ (2)</td><td><strong>Bajo Promedio</strong></td><td style="color:#dc2626;font-weight:700;">● Rojo claro</td></tr>
                <tr><td>★☆☆☆☆ (1)</td><td><strong>Deficiente</strong></td><td style="color:#b91c1c;font-weight:700;">● Rojo oscuro</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>La leyenda completa de equivalencias también aparece en la parte superior del formulario de evaluación para referencia rápida.</p></div>

        <div class="man-h3"><i class="ti ti-circle-number-1"></i> Registrar una evaluación</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Evaluaciones</strong>.</p></div>
            <div class="man-step"><p>Selecciona al pasante a evaluar del listado (solo los de tu departamento).</p></div>
            <div class="man-step"><p>Califica los 14 criterios con estrellas. El badge de color bajo cada criterio confirma qué columna quedará marcada en el reporte.</p></div>
            <div class="man-step"><p>Opcionalmente usa el botón <span class="man-kbd"><i class="ti ti-stars"></i> Excelente a Todo</span> para marcar los 14 criterios en 5 estrellas de un solo clic.</p></div>
            <div class="man-step"><p>Clic en <strong>Guardar Evaluación</strong>. El promedio final se calcula automáticamente y queda en el historial del pasante.</p></div>
        </div>

        <div class="man-h3"><i class="ti ti-file-type-pdf"></i> Reporte PDF de evaluación</div>
        <p class="man-p">El reporte PDF oficial (planilla ISP) puede generarse desde <strong>Reportes → Evaluaciones</strong>. La columna marcada con «X» en el reporte corresponde exactamente a la calificación ingresada según la tabla de equivalencias.</p>

        <div class="man-h3"><i class="ti ti-clipboard-check"></i> Evaluaciones para Pasantes Cortos</div>
        <p class="man-p">Los pasantes cortos tienen su propio flujo de evaluación dentro del módulo <strong>Act. Extras → Pasantías Cortas → Almanaque individual → Tab Evaluaciones</strong>. Usan los mismos 14 criterios ISP y la misma escala de equivalencias.</p>
    </div>
</div>

<!-- ═══ EXÁMENES ═══ -->
<div class="man-sec" id="sec-examenes" data-keywords="exámenes quiz preguntas opción múltiple verdadero falso borrador publicado cerrado resultados ranking medalla intentos eliminar puntaje revisar respuestas correctas incorrectas detalle intento">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f0f9ff;color:#0369a1;"><i class="ti ti-pencil-question"></i></div>
        <div><p class="man-sec-title">Exámenes Rápidos</p><p class="man-sec-sub">Creación, publicación y revisión de evaluaciones en línea</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El módulo de exámenes permite a Administradores y Tutores crear evaluaciones rápidas para los pasantes directamente desde el sistema, con corrección automática y resultados en tiempo real.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p><strong>Alcance por rol:</strong> Los <strong>Tutores</strong> solo ven y gestionan los exámenes que ellos mismos han creado. Los <strong>Administradores</strong> tienen visibilidad sobre todos los exámenes del sistema.</p></div>

        <div class="man-h3"><i class="ti ti-circle-number-1"></i> Crear un examen</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Evaluaciones → Exámenes</strong> y haz clic en <strong>+ Nuevo Examen</strong>.</p></div>
            <div class="man-step"><p>Escribe el título del examen e indica el pasante o grupo al que va dirigido.</p></div>
            <div class="man-step"><p>Agrega las preguntas. El sistema soporta dos tipos:</p></div>
        </div>
        <table class="man-table">
            <thead><tr><th>Tipo de pregunta</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><span class="man-badge mb-blue">Opción Múltiple</span></td><td>4 opciones, una respuesta correcta. El sistema corrige automáticamente.</td></tr>
                <tr><td><span class="man-badge mb-amber">Verdadero / Falso</span></td><td>Dos opciones. También se corrige automáticamente.</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Puedes ajustar los <strong>puntos por pregunta</strong> individualmente desde la vista de detalle del examen después de crearlo.</p></div>

        <div class="man-h3"><i class="ti ti-traffic-lights"></i> Ciclo de vida del examen (3 estados)</div>
        <table class="man-table">
            <thead><tr><th>Estado</th><th>Descripción</th><th>Qué puede hacer el pasante</th></tr></thead>
            <tbody>
                <tr><td><span class="man-badge mb-amber">Borrador</span></td><td>Examen en preparación, no visible para los pasantes.</td><td>Nada — no lo ve.</td></tr>
                <tr><td><span class="man-badge mb-green">Publicado</span></td><td>El pasante puede acceder y responder el examen.</td><td>Verlo y enviarlo.</td></tr>
                <tr><td><span class="man-badge mb-slate">Cerrado</span></td><td>Ya no se aceptan respuestas. Solo consulta de resultados.</td><td>Solo ver su resultado.</td></tr>
            </tbody>
        </table>
        <p class="man-p">Para cambiar el estado usa el botón <strong>Publicar / Cerrar</strong> en la tarjeta o en la vista de detalle del examen.</p>

        <div class="man-h3"><i class="ti ti-chart-pie"></i> Ver resultados</div>
        <p class="man-p">Al abrir un examen publicado o cerrado, la pestaña <strong>Resultados</strong> muestra:</p>
        <table class="man-table">
            <thead><tr><th>Elemento</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Medidor (gauge)</strong></td><td>Promedio general del grupo en el examen.</td></tr>
                <tr><td><strong>Ranking</strong></td><td>Posición de cada pasante con su puntaje y estado (Aprobado / Reprobado).</td></tr>
                <tr><td><strong>Historial de intentos</strong></td><td>Cada intento enviado con hora, puntaje y porcentaje.</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Un pasante <strong>aprueba</strong> con 60% o más del puntaje máximo posible.</p></div>

        <div class="man-h3"><i class="ti ti-eye-check"></i> Revisar un intento en detalle</div>
        <p class="man-p">Al hacer clic en el botón <strong>Revisar</strong> de un intento, se abre un panel que muestra el desempeño completo pregunta por pregunta:</p>
        <table class="man-table">
            <thead><tr><th>Elemento visual</th><th>Significado</th></tr></thead>
            <tbody>
                <tr><td><span class="man-badge mb-green">Fondo verde</span></td><td>El pasante respondió correctamente esa pregunta.</td></tr>
                <tr><td><span class="man-badge" style="background:#fee2e2;color:#b91c1c;">Fondo rojo</span></td><td>El pasante respondió incorrectamente.</td></tr>
                <tr><td><strong>Tu respuesta ✓</strong></td><td>La opción que eligió el pasante (correcta).</td></tr>
                <tr><td><strong>Tu respuesta ✗</strong></td><td>La opción que eligió el pasante (incorrecta).</td></tr>
                <tr><td><strong>Correcta</strong></td><td>La opción que debió elegir (se muestra aunque no la haya seleccionado).</td></tr>
            </tbody>
        </table>
        <p class="man-p">En la parte superior del panel aparece el resumen: nombre del pasante, título del examen, puntaje obtenido y cantidad de respuestas correctas.</p>
        <div class="man-steps">
            <div class="man-step"><p>Abre el examen y busca el intento en la tabla de resultados.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Revisar</strong> (el botón cambia a amarillo si el intento aún no fue revisado).</p></div>
            <div class="man-step"><p>Examina el detalle pregunta por pregunta.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Confirmar revisión</strong> para marcar el intento como revisado. El botón cambia a <span class="man-badge mb-green">Revisado</span>.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si el intento ya fue revisado previamente, el panel se abre en modo <strong>solo lectura</strong> con un indicador <em>✓ Ya fue marcado como revisado</em> al pie. No hay opción de confirmar nuevamente.</p></div>

        <div class="man-h3"><i class="ti ti-trash"></i> Eliminar intentos</div>
        <p class="man-p">Desde el historial de intentos puedes eliminar un intento específico de un pasante (por ejemplo, si fue un envío accidental). Esta acción queda registrada en la Bitácora de Auditoría.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Eliminar un intento es irreversible. El pasante podrá volver a responder el examen si este aún está <span class="man-badge mb-green">Publicado</span>.</p></div>
    </div>
</div>

<!-- ═══ REPORTES ═══ -->
<div class="man-sec" id="sec-reportes" data-keywords="reportes PDF excel exportar imprimir constancia evaluación asistencia nómina ficha personal">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fef2f2;color:#dc2626;"><i class="ti ti-printer"></i></div>
        <div><p class="man-sec-title">Centro de Reportes</p><p class="man-sec-sub">Generación de documentos PDF y Excel</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El centro de reportes genera documentos oficiales del ISP. Los reportes se abren en una nueva pestaña para previsualización antes de imprimir.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p><strong>Alcance por rol:</strong> Los <strong>Tutores</strong> solo ven los reportes marcados como <span class="man-badge mb-green">Tutor</span>. Los reportes <span class="man-badge mb-blue">Admin</span> solo están disponibles para Administradores.</p></div>
        <table class="man-table">
            <thead><tr><th>Reporte</th><th>Formato</th><th>Descripción</th><th>Acceso</th></tr></thead>
            <tbody>
                <tr><td>Control Asistencia</td><td>PDF / Excel</td><td>Planilla trimestral individual (formato ISP)</td><td><span class="man-badge mb-green">Tutor</span></td></tr>
                <tr><td>Evaluaciones</td><td>PDF</td><td>Planilla oficial de 14 criterios</td><td><span class="man-badge mb-green">Tutor</span></td></tr>
                <tr><td>Constancias</td><td>PDF</td><td>Cartas de culminación y servicio</td><td><span class="man-badge mb-green">Tutor</span></td></tr>
                <tr><td>Pasantes</td><td>PDF / Excel</td><td>Ficha general e instituciones de procedencia</td><td><span class="man-badge mb-blue">Admin</span></td></tr>
                <tr><td>Usuarios</td><td>PDF / Excel</td><td>Listado de personal administrativo y tutores</td><td><span class="man-badge mb-blue">Admin</span></td></tr>
                <tr><td>Asignaciones</td><td>PDF / Excel</td><td>Relación Pasante - Tutor - Departamento</td><td><span class="man-badge mb-blue">Admin</span></td></tr>
                <tr><td>Auditoría</td><td>PDF / Excel</td><td>Historial de acciones del sistema</td><td><span class="man-badge mb-blue">Admin</span></td></tr>
                <tr><td>Ficha Diaria</td><td>PDF</td><td>Actividad grupal del día por departamento</td><td><span class="man-badge mb-blue">Admin</span></td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Al abrir un reporte PDF, usa <span class="man-kbd">Ctrl + P</span> para imprimir o el ícono de descarga del visor.</p></div>
    </div>
</div>

<!-- ═══ ANALÍTICAS ═══ -->
<div class="man-sec" id="sec-analiticas" data-keywords="analíticas gráficas estadísticas tendencias dashboard charts">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#eff6ff;color:#2563eb;"><i class="ti ti-chart-dots"></i></div>
        <div><p class="man-sec-title">Analíticas</p><p class="man-sec-sub">Visualización de tendencias y estadísticas</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El módulo de analíticas presenta gráficas interactivas sobre el comportamiento del sistema: asistencia por departamento, evolución de horas acumuladas, distribución por institución, entre otros.</p>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Pasa el cursor sobre las gráficas para ver valores exactos. Usa los filtros de período para comparar cohortes.</p></div>
    </div>
</div>
<?php endif; ?>

<?php if ($rolId == 0): ?>
<!-- ═══ PERMISOS (SUPERADMIN) ═══ -->
<div class="man-sec" id="sec-permisos" data-keywords="permisos superadmin acceso granular rbac módulos toggles roles tutor admin bitácora respaldo acceso elevado">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="ti ti-shield-cog"></i></div>
        <div><p class="man-sec-title">Gestión de Permisos</p><p class="man-sec-sub">Control granular de accesos por usuario — exclusivo SuperAdmin</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Este módulo te permite definir exactamente qué puede ver y hacer cada usuario del sistema, de forma individual. Se basa en un modelo de <strong>permisos granulares por rol con sobrescritura individual</strong>.</p>

        <p class="man-h3"><i class="ti ti-layers-intersect"></i> Cómo funciona el modelo de permisos</p>
        <p class="man-p">Cada acción del sistema tiene un <strong>valor por defecto según el rol</strong> (Administrador o Tutor). Encima de ese default puedes aplicar un <strong>override individual</strong> para cualquier usuario específico — habilitando o deshabilitando permisos que normalmente no tendría.</p>
        <table class="man-table">
            <thead><tr><th>Capa</th><th>Descripción</th><th>Prioridad</th></tr></thead>
            <tbody>
                <tr><td><strong>Default de rol</strong></td><td>Permisos base asignados a todos los usuarios del mismo rol</td><td><span class="man-badge mb-slate">Base</span></td></tr>
                <tr><td><strong>Override individual</strong></td><td>Permiso personalizado para un usuario específico</td><td><span class="man-badge mb-blue">Prevalece</span></td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>El punto <strong style="color:#a855f7;">●</strong> naranja junto a un toggle o en la lista de usuarios indica que ese permiso tiene un override activo — difiere del default de su rol.</p></div>

        <p class="man-h3"><i class="ti ti-user-cog"></i> Pasos para editar permisos de un usuario</p>
        <div class="man-steps">
            <div class="man-step"><p>Entra a <strong>SuperAdmin → Gestión de Permisos</strong>.</p></div>
            <div class="man-step"><p>Selecciona la tarjeta del rol: <strong>Administrador</strong> o <strong>Tutor</strong>.</p></div>
            <div class="man-step"><p>En la lista de la izquierda, haz clic en el usuario que deseas configurar.</p></div>
            <div class="man-step"><p>En el panel derecho verás los módulos del sistema organizados en tarjetas. Cada tarjeta tiene:<br>
                • Un <strong>toggle principal "Menú"</strong> — controla si el módulo aparece en el menú lateral del usuario.<br>
                • <strong>Toggles de función</strong> (crear, editar, exportar, etc.) — controlan acciones dentro del módulo.
            </p></div>
            <div class="man-step"><p>Activa o desactiva el toggle deseado. El cambio se guarda automáticamente en tiempo real — no hay botón de guardar.</p></div>
        </div>

        <p class="man-h3"><i class="ti ti-shield-half-filled"></i> Acceso elevado para Tutores</p>
        <p class="man-p">Los tutores por defecto solo tienen acceso a sus módulos propios. Sin embargo, puedes concederle a un tutor acceso a módulos de administrador (como Bitácora o Respaldo) habilitando el toggle correspondiente. Estos módulos aparecen marcados con la etiqueta naranja <strong style="color:#c2410c;">Acceso elevado</strong>.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Otorgar acceso elevado a un tutor le da visibilidad real sobre esos módulos. Úsalo solo cuando sea estrictamente necesario y revisa periódicamente los permisos activos.</p></div>

        <p class="man-h3"><i class="ti ti-refresh"></i> Restablecer permisos</p>
        <p class="man-p">El botón <strong>Restablecer</strong> (rojo, en la cabecera del panel de permisos) elimina todos los overrides del usuario y lo devuelve a los defaults de su rol. Esta acción es inmediata e irreversible.</p>

        <p class="man-h3"><i class="ti ti-eye-off"></i> ¿Por qué no aparece el SuperAdmin en la lista de usuarios?</p>
        <p class="man-p">La cuenta SuperAdmin no aparece en el módulo de usuarios ni en la gestión de permisos — esto es intencional por seguridad. El SuperAdmin tiene acceso total al sistema sin restricciones y su cuenta no puede ser editada, desactivada ni restringida por ningún otro usuario.</p>

        <div class="man-h3"><i class="ti ti-shield-check"></i> Cobertura de permisos del SuperAdmin</div>
        <p class="man-p">El SuperAdmin (rol 0) tiene habilitadas <strong>todas las acciones</strong> de todos los módulos del sistema (incluyendo Actividades Extras, Exámenes y módulos académicos). Esta cobertura se mantiene automáticamente cuando se agregan nuevos módulos al sistema mediante los scripts de migración.</p>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si tras una actualización del sistema el SuperAdmin no puede acceder a una funcionalidad nueva, un administrador de base de datos puede ejecutar: <code>INSERT IGNORE INTO permisos_rol (rol_id, accion_id, habilitado) SELECT 0, id, 1 FROM acciones_modulo;</code></p></div>
    </div>
</div>

<!-- ═══ SALA DE CONTROL (SUPERADMIN) ═══ -->
<div class="man-sec" id="sec-superadmin-ctrl" data-keywords="sala control superadmin dashboard auditoría gráficas tendencias actividad usuarios acciones semana donut ranking bitácora global">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="ti ti-radar"></i></div>
        <div><p class="man-sec-title">Sala de Control — SuperAdmin</p><p class="man-sec-sub">Panel de supervisión global con métricas de actividad del sistema</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Al iniciar sesión como SuperAdmin, la pantalla de inicio muestra la <strong>Sala de Control</strong>: un dashboard exclusivo con métricas globales de actividad, complementario a la Gestión de Permisos.</p>

        <div class="man-h3"><i class="ti ti-chart-dots"></i> Gráficas disponibles</div>
        <table class="man-table">
            <thead><tr><th>Gráfica</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Actividad últimos 7 días</strong></td><td>Línea con el total de acciones registradas por día en la última semana.</td></tr>
                <tr><td><strong>Actividad últimos 30 días</strong></td><td>Misma línea ampliada para ver la tendencia mensual completa.</td></tr>
                <tr><td><strong>Distribución de acciones</strong></td><td>Donut que muestra qué tipos de acciones ocurren más: logins, asistencias, creaciones, evaluaciones, etc.</td></tr>
                <tr><td><strong>Actividad por usuario</strong></td><td>Ranking de los usuarios más activos del sistema con conteo de acciones.</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Estos datos provienen directamente de la Bitácora de Auditoría. Para ver el detalle de cada acción individual, ve al módulo <strong>Bitácora</strong>.</p></div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>La Sala de Control es exclusiva del SuperAdmin. Ningún otro rol tiene acceso a este panel de supervisión global, ni siquiera con permisos elevados.</p></div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ NOTIFICACIONES DE ESCRITORIO ═══ -->
<div class="man-sec" id="sec-notificaciones" data-keywords="notificaciones escritorio windows navegador desktop campana aviso asistencia feriado pasante sin asignar">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fff7ed;color:#ea580c;"><i class="ti ti-bell-ringing"></i></div>
        <div><p class="man-sec-title">Notificaciones de Escritorio</p><p class="man-sec-sub">Avisos automáticos en el escritorio de Windows vía navegador</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El sistema puede mostrar notificaciones emergentes en el escritorio de Windows (esquina inferior derecha) directamente desde el navegador, sin necesidad de instalar ninguna aplicación adicional. Solo funciona mientras el sistema esté abierto con sesión iniciada como <strong>Administrador</strong> o <strong>SuperAdministrador</strong>.</p>

        <p class="man-h3"><i class="ti ti-toggle-right"></i> Activar las notificaciones</p>
        <div class="man-steps">
            <div class="man-step"><p>Al iniciar sesión como Administrador, el navegador mostrará automáticamente un cuadro de diálogo preguntando <strong>"¿Permitir notificaciones de localhost?"</strong>.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Permitir</strong>. Esta acción solo se solicita una vez por navegador.</p></div>
            <div class="man-step"><p>Listo — el sistema comenzará a enviar notificaciones automáticamente mientras la sesión esté activa.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si accidentalmente bloqueaste las notificaciones, ve al ícono del candado en la barra de direcciones del navegador → <strong>Configuración del sitio</strong> → <strong>Notificaciones</strong> → cámbialo a <strong>Permitir</strong>.</p></div>

        <p class="man-h3"><i class="ti ti-list-details"></i> Tipos de notificaciones</p>
        <table class="man-table">
            <thead><tr><th>Notificación</th><th>Cuándo aparece</th><th>Ejemplo</th></tr></thead>
            <tbody>
                <tr>
                    <td><strong>Asistencia registrada</strong></td>
                    <td>En tiempo real cuando un pasante marca asistencia desde el Kiosco público</td>
                    <td><em>"Juan Pérez — 08:32 AM"</em></td>
                </tr>
                <tr>
                    <td><strong>Pasantes sin asignar</strong></td>
                    <td>A las 12:00 PM si hay pasantes activos sin tutor asignado</td>
                    <td><em>"Tienes 3 pasantes sin tutor asignado"</em></td>
                </tr>
                <tr>
                    <td><strong>Próximo día feriado</strong></td>
                    <td>Al iniciar sesión, si hay un feriado dentro de los próximos 7 días</td>
                    <td><em>"En 3 días: Día de las Madres (10/05/2026). No habrá asistencia ese día."</em></td>
                </tr>
            </tbody>
        </table>

        <p class="man-h3"><i class="ti ti-clock-off"></i> Cuándo NO aparecen notificaciones</p>
        <ul class="man-list">
            <li>Los <strong>sábados y domingos</strong> — el sistema detecta automáticamente el fin de semana.</li>
            <li>Los <strong>días feriados</strong> registrados en el sistema.</li>
            <li>Cuando la <strong>sesión está cerrada</strong> o el navegador no está abierto.</li>
            <li>Para usuarios con rol <strong>Tutor</strong> o <strong>Pasante</strong> — solo están disponibles para Administrador y SuperAdministrador.</li>
        </ul>

        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Las notificaciones de escritorio requieren que el sistema esté abierto en el navegador. Si la sesión se cierra por inactividad (25 minutos), las notificaciones se detienen hasta que vuelvas a iniciar sesión.</p></div>

        <p class="man-h3"><i class="ti ti-help-circle"></i> ¿No aparecen las notificaciones?</p>
        <ul class="man-list">
            <li>Verifica que el permiso del navegador esté en <strong>Permitir</strong> (ícono del candado en la URL).</li>
            <li>Revisa que las notificaciones de Chrome/Edge estén habilitadas en la <strong>Configuración de Windows → Notificaciones y acciones</strong>.</li>
            <li>Asegúrate de estar en modo <strong>No molestar</strong> desactivado en Windows.</li>
            <li>La notificación de asistencia solo aparece cuando el pasante marca desde el <strong>Kiosco público</strong>, no desde el panel de administración.</li>
        </ul>
    </div>
</div>

<!-- ═══ KIOSCO DE ASISTENCIA ═══ -->
<div class="man-sec" id="sec-kiosco" data-keywords="kiosco terminal público asistencia PIN cédula marcaje reloj dispositivo pantalla tablet TV fuera de servicio mantenimiento">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#ecfdf5;color:#059669;"><i class="ti ti-device-desktop"></i></div>
        <div><p class="man-sec-title">Kiosco Público de Asistencia</p><p class="man-sec-sub">Terminal autónoma de marcaje por PIN para pasantes</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El Kiosco es una pantalla pública que permite a los pasantes registrar su asistencia de forma autónoma con cédula y PIN, sin necesidad de que intervenga el tutor o el administrador.</p>

        <div class="man-h3"><i class="ti ti-map-pin"></i> Cómo acceder al Kiosco</div>
        <p class="man-p">Abre cualquier navegador en el dispositivo dedicado (PC, tablet, televisor) y navega a la URL del sistema seguida de <strong>/kiosco</strong>. No requiere iniciar sesión — es una pantalla completamente pública.</p>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Lo ideal es dejar el Kiosco abierto en un dispositivo fijo en la entrada del área de pasantías, visible para los pasantes al llegar cada mañana.</p></div>

        <div class="man-h3"><i class="ti ti-layout-dashboard"></i> Elementos de la pantalla</div>
        <table class="man-table">
            <thead><tr><th>Elemento</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Reloj digital</strong></td><td>Hora actual en tiempo real (actualización cada segundo). Sirve de referencia de puntualidad para el pasante.</td></tr>
                <tr><td><strong>Campo Cédula</strong></td><td>El pasante ingresa su número de cédula para que el sistema lo identifique.</td></tr>
                <tr><td><strong>Campo PIN</strong></td><td>Código de 4 dígitos personal configurado en el wizard de primer acceso.</td></tr>
                <tr><td><strong>Botón Registrar</strong></td><td>Envía la asistencia con la hora exacta del servidor.</td></tr>
            </tbody>
        </table>

        <div class="man-h3"><i class="ti ti-steps"></i> Cómo marca el pasante</div>
        <div class="man-steps">
            <div class="man-step"><p>El pasante se acerca al dispositivo al llegar.</p></div>
            <div class="man-step"><p>Ingresa su <strong>número de cédula</strong>.</p></div>
            <div class="man-step"><p>Ingresa su <strong>PIN de 4 dígitos</strong>.</p></div>
            <div class="man-step"><p>Presiona <strong>Registrar Asistencia</strong>.</p></div>
            <div class="man-step"><p>Aparece una pantalla de confirmación con su nombre y la hora exacta de entrada. El administrador recibe una notificación de escritorio en ese momento.</p></div>
        </div>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si un pasante olvidó su PIN o nunca lo configuró, el tutor puede asignárselo desde <strong>Mis Pasantes → ficha del pasante → Cambiar PIN</strong>.</p></div>

        <div class="man-h3"><i class="ti ti-plug-off"></i> Pantalla "Fuera de servicio"</div>
        <p class="man-p">Si el Kiosco está deshabilitado (días feriados, mantenimiento), la pantalla muestra un aviso de <strong>Terminal fuera de servicio</strong> y no acepta registros. En ese caso la asistencia debe registrarse manualmente desde el panel de administración.</p>
    </div>
</div>

<?php if ($esPasante): ?>
<!-- ═══ MI ASISTENCIA ═══ -->
<div class="man-sec" id="sec-pasante-asist" data-keywords="mi asistencia ver historial días horas estado">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#ecfdf5;color:#059669;"><i class="ti ti-clock-check"></i></div>
        <div><p class="man-sec-title">Mi Asistencia</p><p class="man-sec-sub">Consulta tu historial de asistencias</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Puedes ver tu historial de asistencias diarias, el total de horas acumuladas y tu progreso hacia las horas requeridas.</p>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si detectas un error en tu registro de asistencia, contacta a tu tutor o al administrador para que lo corrija.</p></div>
    </div>
</div>

<!-- ═══ MIS EVALUACIONES ═══ -->
<div class="man-sec" id="sec-pasante-eval" data-keywords="evaluaciones calificación nota criterios rendimiento ver">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fdf2f8;color:#ec4899;"><i class="ti ti-star"></i></div>
        <div><p class="man-sec-title">Mis Evaluaciones</p><p class="man-sec-sub">Consulta las evaluaciones recibidas</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Aquí verás las evaluaciones que tu tutor ha completado para ti. Cada evaluación muestra los 14 criterios con su puntuación y la calificación global.</p>
    </div>
</div>

<!-- ═══ MIS EXÁMENES (PASANTE) ═══ -->
<div class="man-sec" id="sec-pasante-examenes" data-keywords="mis exámenes quiz responder enviar resultado puntaje aprobado reprobado notificación pendiente tiempo">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f0f9ff;color:#0369a1;"><i class="ti ti-pencil-check"></i></div>
        <div><p class="man-sec-title">Mis Exámenes</p><p class="man-sec-sub">Responde exámenes asignados y consulta tus resultados</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Cuando tu tutor o el administrador publique un examen para ti, recibirás una <strong>notificación</strong> y el examen aparecerá en tu sección <strong>Mis Exámenes</strong>.</p>

        <div class="man-h3"><i class="ti ti-bell"></i> Recibir la notificación</div>
        <p class="man-p">El sistema te avisa automáticamente cuando un examen está disponible. La notificación aparece en el <strong>ícono de campana</strong> (esquina superior derecha). Haz clic en ella para ir directamente al examen.</p>

        <div class="man-h3"><i class="ti ti-pencil"></i> Responder un examen</div>
        <div class="man-steps">
            <div class="man-step"><p>Ve a <strong>Mis Exámenes</strong> en el menú lateral.</p></div>
            <div class="man-step"><p>Verás las tarjetas de exámenes disponibles. Los marcados como <span class="man-badge mb-green">Disponible</span> pueden responderse.</p></div>
            <div class="man-step"><p>Haz clic en <strong>Iniciar Examen</strong>.</p></div>
            <div class="man-step"><p>Responde cada pregunta: selecciona la opción correcta o elige Verdadero / Falso.</p></div>
            <div class="man-step"><p>Al terminar, haz clic en <strong>Enviar Examen</strong>. Verás tu puntaje inmediatamente.</p></div>
        </div>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Una vez enviado el examen <strong>no puedes modificarlo</strong>. Asegúrate de haber respondido todas las preguntas antes de enviar.</p></div>

        <div class="man-h3"><i class="ti ti-chart-bar"></i> Ver tu resultado</div>
        <p class="man-p">Después de enviar verás:</p>
        <table class="man-table">
            <thead><tr><th>Dato</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Puntaje obtenido</strong></td><td>Puntos que lograste sobre el máximo posible.</td></tr>
                <tr><td><strong>Porcentaje</strong></td><td>Tu calificación como porcentaje del total.</td></tr>
                <tr><td><strong>Estado</strong></td><td><span class="man-badge mb-green">Aprobado</span> (≥ 60%) o <span class="man-badge" style="background:#fee2e2;color:#991b1b;">Reprobado</span> (< 60%).</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si el examen ya está <span class="man-badge mb-slate">Cerrado</span>, aún puedes consultar tu resultado anterior pero no puedes volver a responderlo.</p></div>
    </div>
</div>

<!-- ═══ MIS ANALÍTICAS (PASANTE) ═══ -->
<div class="man-sec" id="sec-pasante-analiticas" data-keywords="mis analíticas gráficas estadísticas horas acumuladas asistencia mensual porcentaje tendencia progreso pasante personal">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="ti ti-chart-line"></i></div>
        <div><p class="man-sec-title">Mis Analíticas</p><p class="man-sec-sub">Gráficas de tu progreso y comportamiento de asistencia</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Tu módulo personal de analíticas muestra, en gráficas interactivas, cómo ha sido tu asistencia y cuánto has avanzado en tus horas de pasantía.</p>

        <div class="man-h3"><i class="ti ti-layout-grid"></i> Indicadores principales (KPIs)</div>
        <table class="man-table">
            <thead><tr><th>Indicador</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Horas acumuladas</strong></td><td>Total de horas contabilizadas hasta hoy vs. tu meta total.</td></tr>
                <tr><td><strong>% de asistencia</strong></td><td>Días presentes + justificados sobre el total de días hábiles registrados.</td></tr>
                <tr><td><strong>Total días</strong></td><td>Suma de todos los registros (presentes, ausentes, justificados, tardanzas).</td></tr>
                <tr><td><strong>Ausencias</strong></td><td>Días marcados como ausente en tu historial.</td></tr>
            </tbody>
        </table>

        <div class="man-h3"><i class="ti ti-chart-bar"></i> Gráficas disponibles</div>
        <table class="man-table">
            <thead><tr><th>Gráfica</th><th>Qué muestra</th></tr></thead>
            <tbody>
                <tr><td><strong>Asistencia mensual</strong></td><td>Barras apiladas con presentes, ausentes y justificados por mes.</td></tr>
                <tr><td><strong>Tasa de asistencia</strong></td><td>Línea con el porcentaje de asistencia efectiva mes a mes.</td></tr>
                <tr><td><strong>Progreso de horas</strong></td><td>Barra de progreso visual comparando horas acumuladas vs. tu meta.</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Pasa el cursor sobre las barras o puntos del gráfico para ver los valores exactos de ese período.</p></div>
    </div>
</div>

<!-- ═══ MIS ACTIVIDADES (PASANTE) ═══ -->
<div class="man-sec" id="sec-pasante-actividades" data-keywords="mis actividades extras servicio comunitario participación historial timeline pasantía corta brigada">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#fef3c7;color:#d97706;"><i class="ti ti-activity"></i></div>
        <div><p class="man-sec-title">Mis Actividades</p><p class="man-sec-sub">Historial personal de actividades extras en las que participaste</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Si participaste en actividades de Servicio Comunitario, brigadas o pasantías cortas organizadas por el ISP, puedes ver tu historial personal aquí ordenado en una línea de tiempo.</p>
        <table class="man-table">
            <thead><tr><th>Dato</th><th>Descripción</th></tr></thead>
            <tbody>
                <tr><td><strong>Total de actividades</strong></td><td>Cuántas actividades llevas registradas en total.</td></tr>
                <tr><td><strong>Este mes</strong></td><td>Actividades registradas en el mes calendario actual.</td></tr>
                <tr><td><strong>Última actividad</strong></td><td>Fecha y nombre de la actividad más reciente.</td></tr>
            </tbody>
        </table>
        <p class="man-p">Las actividades se presentan en una <strong>línea de tiempo agrupada por mes</strong>, con el nombre, descripción y fecha de cada una.</p>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Si participaste en una actividad pero no aparece en tu listado, contacta al administrador para que verifique el registro de participantes de esa actividad.</p></div>
    </div>
</div>

<!-- ═══ MI CONSTANCIA ═══ -->
<div class="man-sec" id="sec-pasante-const" data-keywords="constancia carta culminación PDF descargar horas completadas">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f0fdf4;color:#059669;"><i class="ti ti-file-certificate"></i></div>
        <div><p class="man-sec-title">Mi Constancia</p><p class="man-sec-sub">Carta oficial de culminación de pasantías</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">Tu constancia de culminación se habilita automáticamente cuando el período académico ha sido <strong>Cerrado</strong> y has completado el <strong>100%</strong> de tus horas requeridas.</p>
        <div class="man-warn"><i class="ti ti-alert-triangle"></i><p>Si el período aún está activo o no has completado tus horas, el documento no estará disponible. Consulta con el administrador.</p></div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ ÍCONOS DE AYUDA ═══ -->
<div class="man-sec" id="sec-tooltips" data-keywords="tooltips ayuda iconos formularios ayuda en linea requerimientos campo sgp-tip help-circle cédula pin período actividad tutor asistencia">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#eff6ff;color:#2563eb;"><i class="ti ti-help-circle"></i></div>
        <div><p class="man-sec-title">Íconos de Ayuda en Formularios</p><p class="man-sec-sub">Dónde aparecen y qué significan</p></div>
    </div>
    <div class="man-sec-body">
        <p class="man-p">El sistema tiene dos tipos de íconos de ayuda en línea. Al pasar el cursor sobre ellos aparece una explicación del campo sin necesidad de abrir ningún modal.</p>

        <p class="man-h3"><i class="ti ti-circle-dot"></i> Tipo 1 — Burbuja <span style="background:#e0e7ff;color:#4338ca;border-radius:50%;padding:1px 8px;font-size:.75rem;font-weight:800;">?</span></p>
        <p class="man-p">Aparece junto al <strong>título del campo</strong>. Al hacer hover muestra una descripción detallada del formato esperado o la función del campo.</p>

        <table class="man-table">
            <thead><tr><th>Módulo</th><th>Formulario</th><th>Campos con <span style="background:#e0e7ff;color:#4338ca;border-radius:50%;padding:0 5px;font-size:.72rem;font-weight:800;">?</span></th></tr></thead>
            <tbody>
                <tr>
                    <td><strong>Actividades Extras</strong></td>
                    <td>Crear / Editar Actividad de Servicio</td>
                    <td>Nombre/Título, Tipo, Institución, Fecha Inicio, Fecha Fin</td>
                </tr>
                <tr>
                    <td><strong>Actividades Extras</strong></td>
                    <td>Registrar Pasante en Actividad</td>
                    <td>Cédula, Correo, Carrera, Horas Meta</td>
                </tr>
                <tr>
                    <td><strong>Períodos</strong></td>
                    <td>Crear / Editar Período Académico</td>
                    <td>Nombre del Período/Cohorte, Descripción de Referencia, Fecha de Inicio</td>
                </tr>
            </tbody>
        </table>

        <p class="man-h3"><i class="ti ti-help-circle"></i> Tipo 2 — Ícono <i class="ti ti-help-circle" style="color:#2563eb;font-size:1rem;vertical-align:middle;"></i> azul</p>
        <p class="man-p">Aparece <strong>dentro del label</strong> o al final del campo de entrada. Al hacer hover muestra una pista breve sobre qué ingresar.</p>

        <table class="man-table">
            <thead><tr><th>Módulo</th><th>Formulario</th><th>Campos con <i class="ti ti-help-circle" style="color:#2563eb;font-size:.9rem;vertical-align:middle;"></i></th></tr></thead>
            <tbody>
                <tr>
                    <td><strong>Tutor — Mis Pasantes</strong></td>
                    <td>Buscador de pasantes</td>
                    <td>Campo de búsqueda por nombre</td>
                </tr>
                <tr>
                    <td><strong>Tutor — Mis Pasantes</strong></td>
                    <td>Cambiar PIN de asistencia</td>
                    <td>Nuevo PIN (4 dígitos), Confirmar PIN</td>
                </tr>
                <tr>
                    <td><strong>Tutor — Asistencias</strong></td>
                    <td>Registrar asistencia manual</td>
                    <td>Selector de fecha, Pasante, Estado, Motivo de Justificación</td>
                </tr>
                <tr>
                    <td><strong>Tutor — Asistencias</strong></td>
                    <td>Filtro del almanaque</td>
                    <td>Selector de fecha a consultar</td>
                </tr>
            </tbody>
        </table>

        <p class="man-h3"><i class="ti ti-input-check"></i> Validación en tiempo real</p>
        <p class="man-p">En los campos de <strong>cédula</strong> y <strong>correo</strong> de formularios de registro, el sistema verifica en tiempo real si el valor ya existe:</p>
        <table class="man-table">
            <thead><tr><th>Indicador</th><th>Significado</th></tr></thead>
            <tbody>
                <tr><td><span style="color:#059669;font-weight:700;">✔ Disponible</span></td><td>El valor no existe en el sistema — puedes usarlo</td></tr>
                <tr><td><span style="color:#dc2626;font-weight:700;">✖ Ya registrado</span></td><td>El valor ya existe — debes usar otro</td></tr>
                <tr><td><span style="color:#d97706;font-weight:700;">⏳ Verificando…</span></td><td>El sistema está comprobando en tiempo real</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-info-circle"></i><p>Los campos con validación en tiempo real son: <strong>Cédula</strong> y <strong>Correo</strong> en el formulario de registro de usuario y en el wizard de primer acceso.</p></div>
    </div>
</div>

<!-- ═══ ATAJOS Y ACCESOS ═══ -->
<div class="man-sec" id="sec-accesos" data-keywords="atajos teclado keyboard escape cerrar modal accesos rápidos">
    <div class="man-sec-hd">
        <div class="man-sec-icon" style="background:#f1f5f9;color:#475569;"><i class="ti ti-keyboard"></i></div>
        <div><p class="man-sec-title">Atajos y Accesos Rápidos</p><p class="man-sec-sub">Gestos y teclas que agilizan el uso del sistema</p></div>
    </div>
    <div class="man-sec-body">
        <table class="man-table">
            <thead><tr><th>Tecla / Acción</th><th>Efecto</th></tr></thead>
            <tbody>
                <tr><td><span class="man-kbd">Esc</span></td><td>Cierra el modal o panel abierto</td></tr>
                <tr><td><span class="man-kbd">Ctrl + P</span></td><td>Imprime el PDF abierto en el visor</td></tr>
                <tr><td>Clic fuera del modal</td><td>Cierra el modal actual</td></tr>
                <tr><td>Hover sobre <span style="background:#e0e7ff;color:#4338ca;border-radius:50%;padding:1px 6px;font-size:.72rem;font-weight:800;">?</span></td><td>Muestra la ayuda del campo</td></tr>
            </tbody>
        </table>
        <div class="man-tip"><i class="ti ti-device-mobile"></i><p>En dispositivos móviles, el menú lateral se convierte en un <strong>dock inferior</strong> estilo iOS. Toca el botón <strong>Más</strong> para acceder a secciones adicionales.</p></div>
    </div>
</div>

<!-- ── NAVEGACIÓN ──────────────────────────────────────────── -->
<div class="man-nav" id="manNav">
    <button class="man-nav-btn" id="manNavPrev" onclick="navPrev()">
        <i class="ti ti-arrow-left"></i>
        <span id="manNavPrevLabel">Anterior</span>
    </button>
    <span class="man-nav-info" id="manNavInfo"></span>
    <button class="man-nav-btn" id="manNavNext" onclick="navNext()">
        <span id="manNavNextLabel">Siguiente</span>
        <i class="ti ti-arrow-right"></i>
    </button>
</div>

</div><!-- /man-panel -->
</div><!-- /man-layout -->
</div><!-- /man-wrap -->

<script>
(function(){
    const panel  = document.getElementById('manPanel');
    const nav    = document.getElementById('manNav');
    const noRes  = document.getElementById('manNoResults');
    const tocDiv = document.getElementById('manToc');

    // Construir lista de secciones visibles en el DOM
    const SECS = Array.from(document.querySelectorAll('#manPanel .man-sec')).map(s => s.id);
    let current = 0;

    function mostrarSeccion(id) {
        const idx = SECS.indexOf(id);
        if (idx === -1) return;
        current = idx;

        document.querySelectorAll('#manPanel .man-sec').forEach(s => s.classList.remove('active'));
        const el = document.getElementById(id);
        if (el) el.classList.add('active');

        // TOC activo
        document.querySelectorAll('.toc-link').forEach(l => {
            l.classList.toggle('activo', l.getAttribute('data-sec') === id);
        });

        actualizarNav();
    }

    function actualizarNav() {
        const prev  = document.getElementById('manNavPrev');
        const next  = document.getElementById('manNavNext');
        const info  = document.getElementById('manNavInfo');
        const pLbl  = document.getElementById('manNavPrevLabel');
        const nLbl  = document.getElementById('manNavNextLabel');

        prev.disabled = current <= 0;
        next.disabled = current >= SECS.length - 1;

        info.textContent = (current + 1) + ' / ' + SECS.length;

        if (current > 0) {
            const prevTitle = document.getElementById(SECS[current-1])?.querySelector('.man-sec-title')?.textContent || 'Anterior';
            pLbl.textContent = prevTitle.length > 22 ? prevTitle.substring(0,20)+'…' : prevTitle;
        }
        if (current < SECS.length - 1) {
            const nextTitle = document.getElementById(SECS[current+1])?.querySelector('.man-sec-title')?.textContent || 'Siguiente';
            nLbl.textContent = nextTitle.length > 22 ? nextTitle.substring(0,20)+'…' : nextTitle;
        }
    }

    function navPrev() { if (current > 0) mostrarSeccion(SECS[current-1]); }
    function navNext() { if (current < SECS.length-1) mostrarSeccion(SECS[current+1]); }

    // Exponer globalmente
    window.mostrarSeccion = mostrarSeccion;
    window.navPrev = navPrev;
    window.navNext = navNext;

    // Búsqueda
    window.filtrarManual = function(q) {
        const term  = q.toLowerCase().trim();
        const clear = document.getElementById('manSearchClear');
        clear.style.display = term ? 'block' : 'none';

        if (!term) {
            // Limpiar inline styles que la búsqueda haya aplicado antes de restaurar clases CSS
            document.querySelectorAll('#manPanel .man-sec').forEach(s => { s.style.display = ''; });
            panel.classList.remove('search-mode');
            nav.style.display = '';
            tocDiv.style.display = '';
            noRes.style.display = 'none';
            mostrarSeccion(SECS[current]);
            return;
        }

        // Modo búsqueda: ocultar/mostrar vía inline style (mayor especificidad que las clases CSS)
        panel.classList.add('search-mode');
        nav.style.display = 'none';
        tocDiv.style.display = 'none';

        let found = 0;
        document.querySelectorAll('#manPanel .man-sec').forEach(s => {
            const kw   = (s.dataset.keywords || '').toLowerCase();
            const txt  = s.textContent.toLowerCase();
            const match = kw.includes(term) || txt.includes(term);
            s.style.display = match ? 'block' : 'none';
            if (match) found++;
        });
        noRes.style.display = found === 0 ? 'block' : 'none';
    };

    window.limpiarBusqueda = function() {
        document.getElementById('manSearch').value = '';
        filtrarManual('');
    };

    // Modal "Manuales" (solo SuperAdmin): elegir y abrir el manual de cualquier rol
    window.abrirModalManuales = function() {
        const base = '<?= URLROOT ?>/manuales/';
        Swal.fire({
            title: 'Manuales de Usuario',
            html: `
                <div class="man-modal-grid">
                    <button class="man-modal-btn man-modal-master" onclick="window.open('${base}manual_maestro.html','_blank')">
                        <i class="ti ti-file-description"></i>
                        <strong>Manual Maestro</strong>
                        <small>Todos los roles y módulos en un solo documento</small>
                    </button>
                    <button class="man-modal-btn" onclick="window.open('${base}manual_superadministrador.html','_blank')">
                        <i class="ti ti-shield-lock"></i>
                        <strong>SuperAdministrador</strong>
                    </button>
                    <button class="man-modal-btn" onclick="window.open('${base}manual_administrador.html','_blank')">
                        <i class="ti ti-settings"></i>
                        <strong>Administrador</strong>
                    </button>
                    <button class="man-modal-btn" onclick="window.open('${base}manual_tutor.html','_blank')">
                        <i class="ti ti-user-check"></i>
                        <strong>Tutor</strong>
                    </button>
                    <button class="man-modal-btn" onclick="window.open('${base}manual_pasante.html','_blank')">
                        <i class="ti ti-user"></i>
                        <strong>Pasante</strong>
                    </button>
                </div>
            `,
            showConfirmButton: false,
            showCloseButton: true,
            width: 560,
        });
    };

    window.imprimirManual = function() {
        // Antes de imprimir, asegura que todas las secciones sean visibles
        const orig = document.querySelectorAll('#manPanel .man-sec');
        orig.forEach(s => s.style.display = 'block');
        window.print();
        // Restaurar después de imprimir
        setTimeout(() => {
            orig.forEach(s => s.style.display = '');
            panel.classList.remove('search-mode');
            mostrarSeccion(SECS[current]);
        }, 500);
    };

    // Init: mostrar primera sección
    if (SECS.length > 0) mostrarSeccion(SECS[0]);
})();
</script>
