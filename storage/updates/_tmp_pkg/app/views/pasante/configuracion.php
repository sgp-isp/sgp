<?php
// Configuración de Paleta de Colores y Apariencia — Pasante SGP Bento v3
$user_name = $data['user_name'] ?? 'Pasante';
$primerNombre = explode(' ', trim($user_name))[0];
?>
<style>
/* ─── BASE ANIMATION & LAYOUT ───────────────────────── */
@keyframes cfgFadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.cfg-wrap {
    display: flex;
    flex-direction: column;
    gap: 22px;
    animation: cfgFadeUp 0.4s ease both;
}

/* ─── HERO BANNER ───────────────────────────────────── */
.cfg-hero {
    background: linear-gradient(135deg, var(--color-primary, #162660) 0%, var(--gradient-mid, #1e3a8a) 50%, var(--color-accent-brand, #2563eb) 100%);
    border-radius: 22px;
    padding: 28px 36px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 10px 30px rgba(37, 99, 235, 0.2);
    transition: background 0.4s ease;
}
.cfg-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 220px; height: 220px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    pointer-events: none;
}
.cfg-hero::after {
    content: '';
    position: absolute;
    bottom: -30px; left: 25%;
    width: 140px; height: 140px;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 50%;
    pointer-events: none;
}
.cfg-hero-left {
    display: flex;
    align-items: center;
    gap: 18px;
    z-index: 1;
}
.cfg-hero-icon {
    width: 56px; height: 56px;
    background: rgba(255, 255, 255, 0.18);
    border: 2px solid rgba(255, 255, 255, 0.28);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; color: #fff;
    flex-shrink: 0;
}
.cfg-hero-badge {
    display: inline-flex;
    align-items: center; gap: 6px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.95);
    border-radius: 50px;
    padding: 4px 14px;
    font-size: 0.76rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
    margin-bottom: 6px;
}
.cfg-hero-title {
    color: #fff;
    font-size: 1.7rem;
    font-weight: 800;
    margin: 0;
    line-height: 1.2;
}

/* ─── LIVE PREVIEW CARD ─────────────────────────────── */
.cfg-preview-banner {
    background: linear-gradient(135deg, var(--color-primary, #162660), var(--color-accent-brand, #2563eb));
    border-radius: 18px;
    padding: 22px 28px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transition: background 0.35s ease;
}
.cfg-preview-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.cfg-preview-avatar {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
}
.cfg-preview-chips {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.cfg-preview-btn {
    background: #ffffff;
    color: var(--color-primary, #162660);
    font-weight: 700;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* ─── BENTO CARDS ───────────────────────────────────── */
.cfg-card {
    background: #fff;
    border-radius: 20px;
    padding: 26px 28px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #f1f5f9;
}
.cfg-card-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}
.cfg-card-title i {
    color: var(--color-accent-brand, #2563eb);
    font-size: 1.35rem;
}
.cfg-card-subtitle {
    font-size: 0.84rem;
    color: #64748b;
    margin-bottom: 22px;
}

/* ─── PRESET PALETTES GRID ──────────────────────────── */
.palettes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
}
.palette-card {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.22s ease;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #ffffff;
    user-select: none;
    position: relative;
}
.palette-card:hover {
    transform: translateY(-3px);
    border-color: #94a3b8;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
}
.palette-card.active {
    border-color: var(--color-accent-brand, #2563eb);
    background: #f8fafc;
    box-shadow: 0 6px 24px rgba(37, 99, 235, 0.18);
}
.palette-gradient-bar {
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    color: #fff;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.15);
}
.palette-dots {
    display: flex;
    align-items: center;
    gap: 6px;
}
.palette-dot {
    width: 14px; height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.palette-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.palette-name {
    font-size: 0.92rem;
    font-weight: 700;
    color: #0f172a;
}
.palette-sub {
    font-size: 0.72rem;
    color: #64748b;
    margin-top: 2px;
}
.palette-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 50px;
    font-size: 0.68rem;
    font-weight: 700;
}
.palette-card.active .palette-badge {
    background: #dcfce7;
    color: #15803d;
}
.palette-card:not(.active) .palette-badge {
    background: #f1f5f9;
    color: #94a3b8;
}

/* ─── CUSTOM PICKERS PANEL ──────────────────────────── */
.custom-pickers-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 18px;
}
.picker-box {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 18px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.picker-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    display: flex;
    justify-content: space-between;
}
.picker-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}
.picker-color-input {
    width: 44px; height: 40px;
    padding: 2px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    cursor: pointer;
    background: #ffffff;
}
.picker-hex-input {
    flex: 1;
    padding: 9px 14px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-family: monospace;
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition: border-color 0.2s;
}
.picker-hex-input:focus {
    border-color: var(--color-accent-brand, #2563eb);
}

/* ─── ACTION BUTTONS ────────────────────────────────── */
.cfg-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #f1f5f9;
}
.btn-cfg-custom {
    background: var(--color-accent-brand, #2563eb);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    padding: 10px 22px;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-cfg-custom:hover {
    opacity: 0.92;
    transform: translateY(-1px);
}
.btn-cfg-reset {
    background: #f8fafc;
    color: #64748b;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 18px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-cfg-reset:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #dc2626;
}

/* ─── RESPONSIVE ─────────────────────────────────────── */
@media (max-width: 768px) {
    .cfg-hero {
        padding: 20px 18px !important;
        border-radius: 18px !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 14px !important;
    }
    .cfg-hero-left {
        width: 100% !important;
        gap: 14px !important;
    }
    .cfg-hero-title {
        font-size: 1.35rem !important;
    }
    .cfg-hero-icon {
        width: 46px !important;
        height: 46px !important;
        font-size: 1.3rem !important;
        border-radius: 14px !important;
    }
    .palettes-grid {
        grid-template-columns: 1fr !important;
    }
    .custom-pickers-row {
        grid-template-columns: 1fr !important;
    }
    .cfg-card {
        padding: 20px 18px !important;
        border-radius: 18px !important;
    }
    .cfg-preview-banner {
        padding: 16px 18px !important;
    }
}

/* ─── DARK MODE SUPPORT ─────────────────────────────── */
[data-theme="dark"] .cfg-card {
    background: #111a2e !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35) !important;
}
[data-theme="dark"] .cfg-card-title {
    color: #f8fafc !important;
}
[data-theme="dark"] .cfg-card-subtitle {
    color: #94a3b8 !important;
}
[data-theme="dark"] .palette-card {
    background: #0d1526 !important;
    border-color: #1e293b !important;
}
[data-theme="dark"] .palette-card:hover {
    border-color: #38bdf8 !important;
}
[data-theme="dark"] .palette-card.active {
    border-color: #38bdf8 !important;
    background: #162238 !important;
    box-shadow: 0 0 20px rgba(56, 189, 248, 0.2) !important;
}
[data-theme="dark"] .palette-name {
    color: #f8fafc !important;
}
[data-theme="dark"] .palette-sub {
    color: #94a3b8 !important;
}
[data-theme="dark"] .palette-card:not(.active) .palette-badge {
    background: #1e293b !important;
    color: #64748b !important;
}
[data-theme="dark"] .picker-box {
    background: #0d1526 !important;
    border-color: #1e293b !important;
}
[data-theme="dark"] .picker-label {
    color: #94a3b8 !important;
}
[data-theme="dark"] .picker-hex-input {
    background: #111a2e !important;
    border-color: #1e293b !important;
    color: #f8fafc !important;
}
[data-theme="dark"] .picker-color-input {
    background: #111a2e !important;
    border-color: #1e293b !important;
}
[data-theme="dark"] .btn-cfg-reset {
    background: #0d1526 !important;
    border-color: #1e293b !important;
    color: #94a3b8 !important;
}
[data-theme="dark"] .cfg-actions-bar {
    border-top-color: #1e293b !important;
}
</style>

<div class="cfg-wrap">

    <!-- ══════════ HERO BANNER ══════════ -->
    <div class="cfg-hero">
        <div class="cfg-hero-left">
            <div class="cfg-hero-icon">
                <i class="ti ti-palette"></i>
            </div>
            <div>
                <div class="cfg-hero-badge">
                    <i class="ti ti-user-cog"></i> Configuración · Pasante
                </div>
                <h1 class="cfg-hero-title">Paleta de Colores y Apariencia</h1>
            </div>
        </div>
        <div style="z-index:1;">
            <div style="display:flex;align-items:center;gap:8px;background:rgba(0,0,0,.22);border:1px solid rgba(255,255,255,.12);border-radius:50px;padding:8px 18px;color:#fff;font-size:.82rem;backdrop-filter:blur(10px);">
                <i class="ti ti-sparkles" style="color:#60a5fa;"></i>
                <span>Paleta activa: <strong id="lblActivePaletteName">Azul ISP Institucional</strong></span>
            </div>
        </div>
    </div>

    <!-- ══════════ LIVE PREVIEW DEMO ══════════ -->
    <div class="cfg-preview-banner" id="cfgPreviewBox">
        <div class="cfg-preview-left">
            <div class="cfg-preview-avatar">
                <i class="ti ti-dashboard"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.5px;opacity:0.85;font-weight:700;">Previsualización Dinámica</div>
                <div style="font-size:1.15rem;font-weight:800;letter-spacing:-0.2px;">Panel Personal de <?= htmlspecialchars($primerNombre) ?></div>
            </div>
        </div>
        <div class="cfg-preview-chips">
            <div class="cfg-preview-btn">
                <i class="ti ti-check"></i> Botón de Acento
            </div>
            <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,0.18);padding:7px 14px;border-radius:10px;font-size:0.8rem;font-weight:600;backdrop-filter:blur(6px);">
                <i class="ti ti-badge"></i> Indicador
            </div>
        </div>
    </div>

    <!-- ══════════ PALETAS RECOMENDADAS ══════════ -->
    <div class="cfg-card">
        <div class="cfg-card-title">
            <i class="ti ti-color-swatch"></i> Paletas Cromáticas Predefinidas
        </div>
        <div class="cfg-card-subtitle">
            Selecciona una combinación diseñada armoniosamente para cambiar el estilo de banners, botones, acentos y gráficos.
        </div>

        <div class="palettes-grid" id="palettesContainer">
            <!-- Paletas renderizadas dinámicamente vía JS -->
        </div>
    </div>

    <!-- ══════════ SELECTOR PERSONALIZADO ══════════ -->
    <div class="cfg-card">
        <div class="cfg-card-title">
            <i class="ti ti-adjustments-alt"></i> Personalización de Colores a Medida
        </div>
        <div class="cfg-card-subtitle">
            Si deseas una identidad única, puedes seleccionar manualmente los tonos exactos en formato HEX.
        </div>

        <div class="custom-pickers-row">
            <!-- Color Primario -->
            <div class="picker-box">
                <div class="picker-label">
                    <span>Color Primario (Banners / Headers)</span>
                    <span id="hexPrimaryText" style="font-family:monospace;font-weight:800;">#162660</span>
                </div>
                <div class="picker-controls">
                    <input type="color" id="customColorPrimary" value="#162660" class="picker-color-input" oninput="onCustomColorInput('primary', this.value)">
                    <input type="text" id="customHexPrimary" value="#162660" maxlength="7" class="picker-hex-input" oninput="onCustomHexInput('primary', this.value)">
                </div>
            </div>

            <!-- Color Acento -->
            <div class="picker-box">
                <div class="picker-label">
                    <span>Color de Acento (Botones / KPIs)</span>
                    <span id="hexAccentText" style="font-family:monospace;font-weight:800;">#2563eb</span>
                </div>
                <div class="picker-controls">
                    <input type="color" id="customColorAccent" value="#2563eb" class="picker-color-input" oninput="onCustomColorInput('accent', this.value)">
                    <input type="text" id="customHexAccent" value="#2563eb" maxlength="7" class="picker-hex-input" oninput="onCustomHexInput('accent', this.value)">
                </div>
            </div>
        </div>

        <div class="cfg-actions-bar">
            <button type="button" class="btn-cfg-custom" onclick="applyCustomPalette()">
                <i class="ti ti-check"></i> Aplicar Paleta Personalizada
            </button>
            <button type="button" class="btn-cfg-reset" onclick="resetToDefaultPalette()">
                <i class="ti ti-rotate"></i> Restablecer Paleta Oficial
            </button>
        </div>
    </div>

</div>

<script>
// Lista de paletas recomendadas
var PRESET_PALETTES = [
    { id: 'isp',      name: 'Azul ISP Institucional',  sub: 'Azul corporativo y clásico', primary: '#162660', accent: '#2563eb' },
    { id: 'salud',    name: 'Salud Pública Esmeralda', sub: 'Verde fresco y clínico',     primary: '#065f46', accent: '#059669' },
    { id: 'morado',   name: 'Púrpura / Amatista Real', sub: 'Creativo y distinguido',     primary: '#4c1d95', accent: '#7c3aed' },
    { id: 'pizarra',  name: 'Pizarra & Zafiro',        sub: 'Moderno y ergonómico',       primary: '#0f172a', accent: '#0284c7' },
    { id: 'rubi',     name: 'Rubí / Carmesí',          sub: 'Enérgico y contrastado',     primary: '#881337', accent: '#e11d48' },
    { id: 'ambar',    name: 'Ámbar / Ocre Cálido',     sub: 'Cálido y dinámico',          primary: '#78350f', accent: '#d97706' },
    { id: 'teal',     name: 'Océano Teal / Turquesa',  sub: 'Sereno y equilibrado',       primary: '#134e4a', accent: '#0d9488' },
    { id: 'obsidian', name: 'Obsidian Tech',           sub: 'Minimalismo ultramoderno',   primary: '#090d16', accent: '#3b82f6' }
];

function renderPalettes() {
    var container = document.getElementById('palettesContainer');
    if (!container) return;

    var cur = getActivePalette();
    container.innerHTML = '';

    PRESET_PALETTES.forEach(function(p) {
        var isActive = (cur.primary.toLowerCase() === p.primary.toLowerCase() &&
                        cur.accent.toLowerCase() === p.accent.toLowerCase());

        var card = document.createElement('div');
        card.className = 'palette-card' + (isActive ? ' active' : '');
        card.onclick = function() { selectPresetPalette(p); };

        card.innerHTML = `
            <div class="palette-gradient-bar" style="background: linear-gradient(135deg, ${p.primary}, ${p.accent});">
                <div class="palette-dots">
                    <span class="palette-dot" style="background:${p.primary};"></span>
                    <span class="palette-dot" style="background:${p.accent}; margin-left:-6px;"></span>
                </div>
                <i class="ti ${isActive ? 'ti-circle-check' : 'ti-circle'}" style="font-size:1.1rem;"></i>
            </div>
            <div class="palette-info">
                <div>
                    <div class="palette-name">${p.name}</div>
                    <div class="palette-sub">${p.sub}</div>
                </div>
                <span class="palette-badge">${isActive ? 'Activo' : 'Elegir'}</span>
            </div>
        `;
        container.appendChild(card);
    });

    updatePreviewCard(cur.primary, cur.accent, cur.name);
}

function getActivePalette() {
    try {
        var saved = JSON.parse(localStorage.getItem('sgp_user_palette'));
        if (saved && saved.primary && saved.accent) return saved;
    } catch(e) {}
    return { primary: '#162660', accent: '#2563eb', name: 'Azul ISP Institucional' };
}

function selectPresetPalette(p) {
    if (typeof applySgpPalette === 'function') {
        applySgpPalette(p.primary, p.accent, p.name);
    }
    syncInputs(p.primary, p.accent);
    renderPalettes();

    if (typeof NotificationService !== 'undefined' && NotificationService.success) {
        NotificationService.success('Paleta "' + p.name + '" aplicada con éxito');
    }
}

function updatePreviewCard(primary, accent, name) {
    var preview = document.getElementById('cfgPreviewBox');
    var lbl = document.getElementById('lblActivePaletteName');
    if (preview) {
        preview.style.background = 'linear-gradient(135deg, ' + primary + ', ' + accent + ')';
    }
    if (lbl && name) {
        lbl.textContent = name;
    }
}

function syncInputs(primary, accent) {
    var cp = document.getElementById('customColorPrimary');
    var ca = document.getElementById('customColorAccent');
    var hp = document.getElementById('customHexPrimary');
    var ha = document.getElementById('customHexAccent');
    var tp = document.getElementById('hexPrimaryText');
    var ta = document.getElementById('hexAccentText');

    if (cp) cp.value = primary;
    if (ca) ca.value = accent;
    if (hp) hp.value = primary;
    if (ha) ha.value = accent;
    if (tp) tp.textContent = primary;
    if (ta) ta.textContent = accent;
}

function onCustomColorInput(type, val) {
    if (type === 'primary') {
        document.getElementById('customHexPrimary').value = val;
        document.getElementById('hexPrimaryText').textContent = val;
    } else {
        document.getElementById('customHexAccent').value = val;
        document.getElementById('hexAccentText').textContent = val;
    }
    liveUpdatePreviewFromInputs();
}

function onCustomHexInput(type, val) {
    if (/^#[0-9A-F]{6}$/i.test(val)) {
        if (type === 'primary') {
            document.getElementById('customColorPrimary').value = val;
            document.getElementById('hexPrimaryText').textContent = val;
        } else {
            document.getElementById('customColorAccent').value = val;
            document.getElementById('hexAccentText').textContent = val;
        }
        liveUpdatePreviewFromInputs();
    }
}

function liveUpdatePreviewFromInputs() {
    var p = document.getElementById('customColorPrimary')?.value || '#162660';
    var a = document.getElementById('customColorAccent')?.value || '#2563eb';
    updatePreviewCard(p, a, 'Personalizada');
}

function applyCustomPalette() {
    var p = document.getElementById('customColorPrimary')?.value || '#162660';
    var a = document.getElementById('customColorAccent')?.value || '#2563eb';

    if (typeof applySgpPalette === 'function') {
        applySgpPalette(p, a, 'Personalizada');
    }
    renderPalettes();

    if (typeof NotificationService !== 'undefined' && NotificationService.success) {
        NotificationService.success('Paleta personalizada aplicada con éxito');
    }
}

function resetToDefaultPalette() {
    try {
        localStorage.removeItem('sgp_user_palette');
    } catch(e) {}

    var el = document.getElementById('sgp-palette-dynamic-override');
    if (el) el.remove();

    var defPrimary = '#162660';
    var defAccent  = '#2563eb';

    if (typeof applySgpPalette === 'function') {
        applySgpPalette(defPrimary, defAccent, 'Azul ISP Institucional');
    }
    syncInputs(defPrimary, defAccent);
    renderPalettes();

    if (typeof NotificationService !== 'undefined' && NotificationService.info) {
        NotificationService.info('Paleta restablecida a valores por defecto');
    }
}

// Inicializar al cargar
(function() {
    var cur = getActivePalette();
    syncInputs(cur.primary, cur.accent);
    renderPalettes();
})();

document.addEventListener('pjax:complete', function() {
    var cur = getActivePalette();
    syncInputs(cur.primary, cur.accent);
    renderPalettes();
});
</script>
