<?php
/**
 * SuperAdmin — Diseñador & Gestor Centralizado de Plantillas de Reportes PDF
 * Interfaz Visual Tipo Hoja de Word (Visual Paper Canvas) & Arquitectura Bento
 */
?>

<style>
/* ── Contenedor General y Layout Bento ── */
.sgp-tpl-container {
    width: 100%;
    max-width: 100%;
    padding: 0;
    margin: 0;
    font-family: inherit;
}

/* Hero Banner Bento */
.sgp-tpl-hero {
    background: linear-gradient(135deg, #09153a 0%, #162660 50%, #1e40af 100%);
    border-radius: 20px;
    padding: 24px 30px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
}
.sgp-tpl-hero::before {
    content: '';
    position: absolute;
    top: -40px;
    right: -40px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.sgp-tpl-hero-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    z-index: 1;
}
.sgp-tpl-hero-brand {
    display: flex;
    align-items: center;
    gap: 16px;
}
.sgp-tpl-hero-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(8px);
}
.sgp-tpl-hero-title {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;
}
.sgp-tpl-hero-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.86rem;
    margin: 4px 0 0 0;
}
.sgp-tpl-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 12px;
    color: #ffffff;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 700;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
}
.sgp-tpl-hero-btn:hover {
    background: rgba(255, 255, 255, 0.24);
    color: #ffffff;
    transform: translateY(-1px);
}

/* ── Workspace Grid: Inspector (Izquierda) + Hoja Virtual (Derecha) ── */
.sgp-tpl-workspace {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 22px;
    align-items: start;
}

/* ── Panel Inspector (Izquierda) ── */
.sgp-tpl-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.sgp-tpl-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}
.sgp-tpl-card-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.sgp-tpl-card-title i {
    color: #2563eb;
    font-size: 1.1rem;
}

/* Selector de Categorías */
.sgp-tpl-cat-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 14px;
}
.sgp-tpl-cat-chip {
    padding: 5px 12px;
    border-radius: 50px;
    font-size: 0.74rem;
    font-weight: 700;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
}
.sgp-tpl-cat-chip:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    color: #0f172a;
}
.sgp-tpl-cat-chip.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
}

/* Lista Selector de Plantillas */
.sgp-tpl-select-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 280px;
    overflow-y: auto;
    padding-right: 4px;
}
.sgp-tpl-item-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s ease;
}
.sgp-tpl-item-btn:hover {
    border-color: #93c5fd;
    background: #f8fafc;
}
.sgp-tpl-item-btn.selected {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
}
.sgp-tpl-item-name {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.25;
}
.sgp-tpl-item-badge {
    font-size: 0.65rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 50px;
    flex-shrink: 0;
}
.sgp-tpl-badge-custom {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.sgp-tpl-badge-default {
    background: #f1f5f9;
    color: #64748b;
}

/* Banco de Variables Inteligentes */
.sgp-tpl-vars-box {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}
.sgp-tpl-var-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 8px;
    background: #f1f5f9;
    border: 1px dashed #cbd5e1;
    color: #2563eb;
    font-size: 0.73rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.sgp-tpl-var-pill:hover {
    background: #eff6ff;
    border-color: #2563eb;
    transform: translateY(-1px);
}
.sgp-tpl-var-pill i {
    font-size: 0.8rem;
}

/* Control de Cintillo y Firmas */
.sgp-tpl-cintillo-preview-box {
    width: 100%;
    height: 60px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}
.sgp-tpl-cintillo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.sgp-tpl-input-label {
    font-size: 0.74rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 4px;
    display: block;
}
.sgp-tpl-input {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.82rem;
    color: #1e293b;
    outline: none;
    transition: all 0.2s;
    background: #f8fafc;
    box-sizing: border-box;
    margin-bottom: 12px;
}
.sgp-tpl-input:focus {
    border-color: #2563eb;
    background: #ffffff;
}

/* ── Panel Central (Lienzo Hoja de Word) ── */
.sgp-tpl-canvas-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    background: #e2e8f0;
    border-radius: 20px;
    padding: 24px 20px 40px;
    border: 1px solid #cbd5e1;
}

/* Barra de Herramientas Word Toolbar */
.sgp-word-toolbar {
    width: 100%;
    max-width: 820px;
    background: #ffffff;
    border-radius: 14px;
    padding: 8px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
    border: 1px solid #cbd5e1;
    position: sticky;
    top: 15px;
    z-index: 10;
}
.sgp-word-toolbar-group {
    display: flex;
    align-items: center;
    gap: 4px;
}
.sgp-word-tool-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: transparent;
    color: #475569;
    font-size: 1rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}
.sgp-word-tool-btn:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #0f172a;
}
.sgp-word-tool-btn:active {
    background: #e2e8f0;
}
.sgp-word-tool-sep {
    width: 1px;
    height: 22px;
    background: #e2e8f0;
    margin: 0 4px;
}

/* ── La Hoja Física Virtual (Sheet Canvas) ── */
.sgp-word-sheet {
    width: 100%;
    max-width: 816px; /* Ancho estándar Carta 8.5in a 96dpi */
    min-height: 1056px; /* Altura estándar Carta 11in a 96dpi */
    background: #ffffff;
    border-radius: 4px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.08);
    padding: 55px 60px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #0d1424;
    line-height: 1.8;
}

/* Cintillo superior */
.sgp-sheet-cintillo {
    width: 100%;
    margin-bottom: 24px;
    text-align: center;
    line-height: 0;
}
.sgp-sheet-cintillo img {
    width: 100%;
    max-height: 85px;
    object-fit: contain;
    display: block;
}

/* Barra de control y fecha */
.sgp-sheet-ref-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 6px;
    margin-bottom: 24px;
}
.sgp-sheet-ref-ctrl {
    font-weight: 800;
    color: #162660;
    letter-spacing: 0.5px;
}

/* Título Editable */
.sgp-sheet-title {
    font-size: 15px;
    font-weight: 800;
    color: #162660;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    border-bottom: 2px solid #162660;
    padding-bottom: 8px;
    margin-bottom: 22px;
    outline: none;
    transition: background 0.2s;
}
.sgp-sheet-title:focus {
    background: #eff6ff;
    border-radius: 4px;
}

/* Cuerpo Editable WYSIWYG */
.sgp-sheet-body {
    flex: 1;
    font-size: 12.5px;
    color: #0d1424;
    text-align: justify;
    line-height: 1.85;
    outline: none;
    min-height: 280px;
    padding: 8px 12px;
    border: 1px dashed transparent;
    border-radius: 8px;
    transition: all 0.2s;
}
.sgp-sheet-body:focus {
    border-color: #93c5fd;
    background: rgba(239, 246, 255, 0.4);
}
.sgp-sheet-body p {
    margin-bottom: 14px;
}

/* Bloque de Firmas y Sello */
/* Bloque de Firmas y Sello */
.sgp-sheet-signatures {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    margin-top: 50px;
    padding-top: 20px;
    position: relative;
}
.sgp-sheet-firma-col {
    text-align: center;
    width: 300px;
}
.sgp-sheet-antefirma {
    font-size: 11px;
    color: #475569;
    font-style: italic;
    margin-bottom: 45px;
    outline: none;
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background 0.15s;
}
.sgp-sheet-antefirma:hover,
.sgp-sheet-editable-inline:hover {
    background: rgba(239, 246, 255, 0.7);
    box-shadow: 0 0 0 1px #bfdbfe;
}
.sgp-sheet-antefirma:focus,
.sgp-sheet-editable-inline:focus {
    background: #eff6ff;
    box-shadow: 0 0 0 2px #3b82f6;
}
.sgp-sheet-firma-line {
    border-top: 1px solid #0d1424;
    padding-top: 6px;
    font-size: 11px;
    font-weight: 800;
    color: #0f172a;
}
.sgp-sheet-editable-inline {
    outline: none;
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    transition: all 0.15s;
    min-width: 60px;
}
.sgp-sheet-firma-cargo {
    font-size: 9.5px;
    font-weight: 700;
    color: #162660;
    text-transform: uppercase;
    margin-top: 2px;
}

/* Sello Draggable (Arrastrable libremente con mouse) */
.sgp-sheet-sello-col {
    position: relative;
    user-select: none;
}
.sgp-sheet-sello-box {
    width: 140px;
    height: 94px;
    border: 2px dashed #94a3b8;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 9px;
    font-weight: 700;
    text-align: center;
    line-height: 1.3;
    background: rgba(248, 250, 252, 0.9);
    cursor: grab;
    position: relative;
    transition: box-shadow 0.2s, border-color 0.2s, background 0.2s;
    touch-action: none;
}
.sgp-sheet-sello-box:hover {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.18);
}
.sgp-sheet-sello-box.is-dragging {
    cursor: grabbing;
    opacity: 0.92;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.25);
    border-color: #2563eb;
    z-index: 999;
}
.sgp-sello-drag-handle {
    position: absolute;
    top: 4px;
    right: 6px;
    font-size: 0.85rem;
    color: #94a3b8;
    cursor: grab;
    pointer-events: none;
}
.sgp-sello-pos-badge {
    position: absolute;
    bottom: -19px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 8px;
    font-weight: 700;
    color: #475569;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    padding: 1px 7px;
    border-radius: 4px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.sgp-sheet-sello-box:hover .sgp-sello-pos-badge,
.sgp-sheet-sello-box.is-dragging .sgp-sello-pos-badge {
    opacity: 1;
}

/* Pie de página */
.sgp-sheet-footer {
    text-align: center;
    font-size: 9px;
    color: #94a3b8;
    border-top: 1px solid #e2e8f0;
    padding-top: 10px;
    margin-top: 35px;
    outline: none;
}
.sgp-sheet-footer:focus {
    background: #eff6ff;
}

/* ── Botonera Inferior de Acciones ── */
.sgp-tpl-action-bar {
    width: 100%;
    max-width: 820px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.sgp-tpl-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 12px;
    background: linear-gradient(135deg, #162660, #2563eb);
    border: none;
    color: #ffffff;
    font-size: 0.88rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
    transition: all 0.2s ease;
}
.sgp-tpl-btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
}
.sgp-tpl-btn-preview {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 18px;
    border-radius: 12px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    color: #1e293b;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.sgp-tpl-btn-preview:hover {
    background: #f8fafc;
    border-color: #94a3b8;
}
.sgp-tpl-btn-reset {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 12px;
    background: #fef2f2;
    border: 1.5px solid #fecaca;
    color: #dc2626;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
}
.sgp-tpl-btn-reset:hover {
    background: #fee2e2;
}

/* Responsive */
@media (max-width: 1080px) {
    .sgp-tpl-workspace {
        grid-template-columns: 1fr;
    }
    .sgp-word-sheet {
        padding: 35px 25px;
    }
}
</style>

<div class="sgp-tpl-container">

    <!-- 1. Hero Banner Bento -->
    <div class="sgp-tpl-hero">
        <div class="sgp-tpl-hero-content">
            <div class="sgp-tpl-hero-brand">
                <div class="sgp-tpl-hero-icon">
                    <i class="ti ti-file-pencil"></i>
                </div>
                <div>
                    <h1 class="sgp-tpl-hero-title">Diseñador & Gestor de Plantillas PDF</h1>
                    <p class="sgp-tpl-hero-subtitle">Edición en lienzo interactivo estilo Word, cintillo oficial y variables de automatización (SuperAdmin)</p>
                </div>
            </div>
            <a href="<?= URLROOT ?>/superadmin" class="sgp-tpl-hero-btn">
                <i class="ti ti-arrow-left"></i>
                <span>Volver al Dashboard</span>
            </a>
        </div>
    </div>

    <!-- 2. Workspace: Inspector + Hoja Virtual -->
    <div class="sgp-tpl-workspace">

        <!-- PANEL IZQUIERDO: Selector & Parámetros -->
        <aside class="sgp-tpl-sidebar">

            <!-- Card Catálogo de Plantillas -->
            <div class="sgp-tpl-card">
                <div class="sgp-tpl-card-title">
                    <span>Catálogo de Documentos</span>
                    <i class="ti ti-folders"></i>
                </div>

                <!-- Chips Categorías -->
                <div class="sgp-tpl-cat-chips">
                    <button type="button" class="sgp-tpl-cat-chip active" onclick="sgpFilterCat('ALL', this)">Todas</button>
                    <button type="button" class="sgp-tpl-cat-chip" onclick="sgpFilterCat('Constancias', this)">Constancias</button>
                    <button type="button" class="sgp-tpl-cat-chip" onclick="sgpFilterCat('Asistencias', this)">Asistencias</button>
                    <button type="button" class="sgp-tpl-cat-chip" onclick="sgpFilterCat('Evaluaciones', this)">Evaluaciones</button>
                    <button type="button" class="sgp-tpl-cat-chip" onclick="sgpFilterCat('Auditoría', this)">Auditoría</button>
                </div>

                <!-- Lista de Documentos -->
                <div class="sgp-tpl-select-list" id="sgpTplList">
                    <?php foreach ($data['plantillas'] as $i => $tpl): ?>
                    <button type="button" class="sgp-tpl-item-btn <?= $i === 0 ? 'selected' : '' ?>" 
                            data-codigo="<?= htmlspecialchars($tpl['codigo']) ?>"
                            data-cat="<?= htmlspecialchars($tpl['categoria']) ?>"
                            onclick="sgpSelectTemplate('<?= htmlspecialchars($tpl['codigo']) ?>', this)">
                        <span class="sgp-tpl-item-name"><?= htmlspecialchars($tpl['nombre']) ?></span>
                        <span class="sgp-tpl-item-badge <?= !empty($tpl['es_personalizada']) ? 'sgp-tpl-badge-custom' : 'sgp-tpl-badge-default' ?>">
                            <?= !empty($tpl['es_personalizada']) ? 'Personalizada' : 'Original' ?>
                        </span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Card Cintillo & Membrete Institucional -->
            <div class="sgp-tpl-card">
                <div class="sgp-tpl-card-title">
                    <span>Cintillo & Membrete</span>
                    <i class="ti ti-photo"></i>
                </div>

                <div class="sgp-tpl-cintillo-preview-box" id="sgpCintilloBox">
                    <img src="<?= URLROOT ?>/img/cintillo.png" alt="Cintillo" class="sgp-tpl-cintillo-img" id="sgpCintilloImg">
                </div>

                <div style="display:flex;gap:8px;">
                    <label class="sgp-tpl-btn-preview" style="flex:1;justify-content:center;font-size:0.75rem;padding:7px 10px;cursor:pointer;">
                        <i class="ti ti-upload"></i> Subir Cintillo
                        <input type="file" id="sgpCintilloInput" accept="image/png,image/jpeg" style="display:none;" onchange="sgpUploadCintillo(this)">
                    </label>
                    <button type="button" class="sgp-tpl-btn-preview" style="font-size:0.75rem;padding:7px 10px;" onclick="sgpResetCintillo()" title="Restablecer cintillo estándar">
                        <i class="ti ti-rotate-clockwise"></i>
                    </button>
                </div>
            </div>

            <!-- Card Parámetros de Firmas & Sello -->
            <div class="sgp-tpl-card">
                <div class="sgp-tpl-card-title">
                    <span>Firmas & Sello Oficial</span>
                    <i class="ti ti-certificate"></i>
                </div>

                <label class="sgp-tpl-input-label">Nombre del Firmante</label>
                <input type="text" id="sgpFirmaNombreInput" class="sgp-tpl-input" 
                       placeholder="ej: {TUTOR_NOMBRE} o Nombre completo"
                       oninput="sgpSyncFirmas()">

                <label class="sgp-tpl-input-label">Cargo Institucional</label>
                <input type="text" id="sgpFirmaCargoInput" class="sgp-tpl-input" 
                       placeholder="ej: Jefe de la División de Soporte Técnico..."
                       oninput="sgpSyncFirmas()">

                <label class="sgp-tpl-input-label">Leyenda de Antefirma</label>
                <input type="text" id="sgpFirmaAntefirmaInput" class="sgp-tpl-input" 
                       placeholder="ej: Atentamente,"
                       oninput="sgpSyncFirmas()">

                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                    <span style="font-size:0.82rem;font-weight:700;color:#1e293b;">Mostrar Sello Oficial</span>
                    <label class="sgp-ios-switch sm">
                        <input type="checkbox" id="sgpSelloToggle" checked onchange="sgpSyncFirmas()">
                        <span class="sgp-ios-slider"></span>
                    </label>
                </div>

                <div style="margin-top:12px;padding-top:10px;border-top:1px solid #f1f5f9;">
                    <span class="sgp-tpl-input-label" style="display:flex;align-items:center;justify-content:space-between;">
                        <span>Posición Rápida del Sello</span>
                        <small style="color:#2563eb;text-transform:none;font-weight:600;"><i class="ti ti-arrows-move"></i> También arrastrable</small>
                    </span>
                    <div style="display:flex;gap:5px;margin-top:4px;">
                        <button type="button" class="sgp-tpl-cat-chip" style="flex:1;padding:5px 2px;text-align:center;" onclick="sgpSetSelloPos('left')" title="Mover a la izquierda"><i class="ti ti-layout-align-left"></i> Izq</button>
                        <button type="button" class="sgp-tpl-cat-chip" style="flex:1;padding:5px 2px;text-align:center;" onclick="sgpSetSelloPos('center')" title="Centrar sobre firma"><i class="ti ti-layout-align-center"></i> Centro</button>
                        <button type="button" class="sgp-tpl-cat-chip" style="flex:1;padding:5px 2px;text-align:center;" onclick="sgpSetSelloPos('right')" title="Mover a la derecha"><i class="ti ti-layout-align-right"></i> Der</button>
                        <button type="button" class="sgp-tpl-cat-chip" style="padding:5px 8px;" onclick="sgpResetSelloPos()" title="Reiniciar posición"><i class="ti ti-rotate-clockwise"></i></button>
                    </div>
                </div>
            </div>

            <!-- Card Banco de Variables Inteligentes -->
            <div class="sgp-tpl-card">
                <div class="sgp-tpl-card-title">
                    <span>Variables Inteligentes</span>
                    <i class="ti ti-code"></i>
                </div>
                <p style="font-size:0.75rem;color:#64748b;margin:0 0 8px;">
                    Haz clic en cualquier variable para insertarla en el texto de la hoja:
                </p>
                <div class="sgp-tpl-vars-box" id="sgpVarsBox">
                    <!-- Se inyecta dinámicamente -->
                </div>
            </div>

        </aside>

        <!-- PANEL DERECHO: El Lienzo / Hoja de Word Virtual -->
        <main class="sgp-tpl-canvas-area">

            <!-- Barra de Herramientas de Formato (Word Toolbar) -->
            <div class="sgp-word-toolbar">
                <div class="sgp-word-toolbar-group">
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('bold',false,null)" title="Negrita (Ctrl+B)">
                        <i class="ti ti-bold"></i>
                    </button>
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('italic',false,null)" title="Cursiva (Ctrl+I)">
                        <i class="ti ti-italic"></i>
                    </button>
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('underline',false,null)" title="Subrayado (Ctrl+U)">
                        <i class="ti ti-underline"></i>
                    </button>
                    <div class="sgp-word-tool-sep"></div>
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('justifyLeft',false,null)" title="Alinear a la izquierda">
                        <i class="ti ti-align-left"></i>
                    </button>
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('justifyCenter',false,null)" title="Centrar">
                        <i class="ti ti-align-center"></i>
                    </button>
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('justifyRight',false,null)" title="Alinear a la derecha">
                        <i class="ti ti-align-right"></i>
                    </button>
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('justifyFull',false,null)" title="Justificar">
                        <i class="ti ti-align-justified"></i>
                    </button>
                    <div class="sgp-word-tool-sep"></div>
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('insertUnorderedList',false,null)" title="Viñetas">
                        <i class="ti ti-list"></i>
                    </button>
                    <button type="button" class="sgp-word-tool-btn" onclick="document.execCommand('removeFormat',false,null)" title="Limpiar formato">
                        <i class="ti ti-clear-formatting"></i>
                    </button>
                </div>

                <div class="sgp-word-toolbar-group">
                    <span style="font-size:0.75rem;font-weight:700;color:#64748b;" id="sgpFormatPaperLabel">Tamaño: Carta (Letter)</span>
                </div>
            </div>

            <!-- ── LA HOJA FÍSICA VIRTUAL (PAPER SHEET) ── -->
            <div class="sgp-word-sheet" id="sgpWordSheet">

                <!-- 1. Cintillo Superior -->
                <div class="sgp-sheet-cintillo">
                    <img src="<?= URLROOT ?>/img/cintillo.png" alt="Cintillo Oficial" id="sgpSheetCintilloImg">
                </div>

                <!-- 2. Barra de Control y Fecha -->
                <div class="sgp-sheet-ref-bar">
                    <div class="sgp-sheet-ref-ctrl">N° DE CONTROL: {NUMERO_CONTROL}</div>
                    <div>Ciudad Bolívar, {FECHA_EMISION}</div>
                </div>

                <!-- 3. Título del Documento Editable -->
                <div class="sgp-sheet-title" id="sgpSheetTitle" contenteditable="true" spellcheck="false">
                    CARTA DE CULMINACIÓN DE PASANTÍAS PROFESIONALES
                </div>

                <!-- 4. Cuerpo de Texto Editable WYSIWYG -->
                <div class="sgp-sheet-body" id="sgpSheetBody" contenteditable="true" spellcheck="false">
                    <!-- Se inyecta dinámicamente -->
                </div>

                <!-- 5. Bloque de Firmas y Sellos -->
                <div class="sgp-sheet-signatures" id="sgpSheetSignatures">
                    <div class="sgp-sheet-firma-col" id="sgpSheetFirmaCol">
                        <div class="sgp-sheet-antefirma" id="sgpSheetAntefirma" contenteditable="true" spellcheck="false" title="Haz clic para editar la antefirma">Atentamente,</div>
                        <div class="sgp-sheet-firma-line">
                            <span class="sgp-sheet-editable-inline" id="sgpSheetFirmaNombre" contenteditable="true" spellcheck="false" title="Haz clic para editar el nombre del firmante">ING. LUIS YÁNEZ</span><br>
                            <span class="sgp-sheet-firma-cargo sgp-sheet-editable-inline" id="sgpSheetFirmaCargo" contenteditable="true" spellcheck="false" title="Haz clic para editar el cargo institucional">Jefe de la División de Soporte Técnico de la Dirección de Telemática</span>
                        </div>
                    </div>

                    <div class="sgp-sheet-sello-col" id="sgpSheetSelloCol">
                        <div class="sgp-sheet-sello-box" id="sgpSheetSelloBox" title="Haz clic y arrastra con el ratón para mover el sello libremente en la hoja">
                            <i class="ti ti-arrows-move sgp-sello-drag-handle"></i>
                            <span class="sgp-sheet-editable-inline" id="sgpSheetSelloText" contenteditable="true" spellcheck="false" title="Haz clic para editar el texto del sello">Sello Oficial</span>
                            <small style="color:#94a3b8;margin-top:2px;">ISP Bolívar</small>
                            <span class="sgp-sello-pos-badge"><i class="ti ti-hand-grab"></i> Arrastrar sello</span>
                        </div>
                    </div>
                </div>

                <!-- 6. Pie de Página Editable -->
                <div class="sgp-sheet-footer" id="sgpSheetFooter" contenteditable="true" spellcheck="false">
                    Documento emitido por el Sistema de Gestión de Pasantías (SGP) | Instituto de Salud Pública del Estado Bolívar
                </div>

            </div><!-- /.sgp-word-sheet -->

            <!-- Botonera Inferior Flotante -->
            <div class="sgp-tpl-action-bar">
                <button type="button" class="sgp-tpl-btn-reset" onclick="sgpResetTemplate()">
                    <i class="ti ti-rotate-clockwise"></i>
                    <span>Restablecer Original de Fábrica</span>
                </button>

                <div style="display:flex;gap:10px;">
                    <button type="button" class="sgp-tpl-btn-preview" onclick="sgpPreviewPdf()">
                        <i class="ti ti-printer"></i>
                        <span>Vista Previa PDF Real</span>
                    </button>
                    <button type="button" class="sgp-tpl-btn-save" onclick="sgpSaveTemplate()">
                        <i class="ti ti-device-floppy"></i>
                        <span>Guardar Plantilla Maestra</span>
                    </button>
                </div>
            </div>

        </main>
    </div>

</div>

<script>
(function () {
    'use strict';

    const URLROOT = '<?= URLROOT ?>';
    let currentCode = 'constancia_culminacion';
    let currentTemplateData = null;

    // ── 1. Cargar Plantilla Seleccionada ───────────────────
    window.sgpSelectTemplate = function (codigo, btn) {
        currentCode = codigo;

        document.querySelectorAll('.sgp-tpl-item-btn').forEach(function (b) {
            b.classList.remove('selected');
        });
        if (btn) btn.classList.add('selected');

        fetch(URLROOT + '/superadmin/getPlantillaAjax?codigo=' + encodeURIComponent(codigo))
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) {
                    if (window.NotificationService) window.NotificationService.error(res.message);
                    return;
                }
                renderTemplateInSheet(res.plantilla);
            })
            .catch(function (err) {
                console.error(err);
                if (window.NotificationService) window.NotificationService.error('Error al cargar la plantilla');
            });
    };

    // ── 2. Renderizar Datos en la Hoja Virtual ─────────────
    function renderTemplateInSheet(p) {
        currentTemplateData = p;

        // Título y cuerpo
        document.getElementById('sgpSheetTitle').innerText = p.titulo_documento || 'DOCUMENTO INSTITUCIONAL';
        document.getElementById('sgpSheetBody').innerHTML   = p.cuerpo_html || '<p>Redacta aquí el contenido del documento...</p>';
        document.getElementById('sgpSheetFooter').innerText = p.pie_pagina || '';

        // Cintillo
        const cintilloImg = document.getElementById('sgpSheetCintilloImg');
        const cintilloSide = document.getElementById('sgpCintilloImg');
        const imgUrl = p.cintillo_url ? (URLROOT + '/' + p.cintillo_url.replace(/^\/+/, '')) : (URLROOT + '/img/cintillo.png');
        cintilloImg.src = imgUrl;
        cintilloSide.src = imgUrl;

        // Firmas y sello
        const firmas = p.firmas || {};
        const antefirmaVal = firmas.antefirma || 'Atentamente,';
        const nombreVal    = firmas.firmante_nombre || '{TUTOR_NOMBRE}';
        const cargoVal     = firmas.firmante_cargo  || 'Jefe de la División de Soporte Técnico de la Dirección de Telemática';
        const selloTxtVal  = firmas.sello_texto     || 'Sello Oficial';

        document.getElementById('sgpFirmaNombreInput').value    = nombreVal;
        document.getElementById('sgpFirmaCargoInput').value     = cargoVal;
        document.getElementById('sgpFirmaAntefirmaInput').value = antefirmaVal;
        document.getElementById('sgpSelloToggle').checked       = firmas.mostrar_sello !== false;

        document.getElementById('sgpSheetAntefirma').innerText   = antefirmaVal;
        document.getElementById('sgpSheetFirmaNombre').innerText = nombreVal;
        document.getElementById('sgpSheetFirmaCargo').innerText  = cargoVal;
        document.getElementById('sgpSheetSelloText').innerText   = selloTxtVal;

        if (firmas.sello_pos && typeof firmas.sello_pos.x === 'number') {
            applySelloPos(firmas.sello_pos.x, firmas.sello_pos.y);
        } else {
            applySelloPos(0, 0);
        }

        sgpSyncFirmas();

        // Variables chips
        const varsBox = document.getElementById('sgpVarsBox');
        const vars = p.variables || {};
        if (Object.keys(vars).length > 0) {
            varsBox.innerHTML = Object.keys(vars).map(function (k) {
                const desc = vars[k] || '';
                return '<div class="sgp-tpl-var-pill" onclick="sgpInsertVariable(\'' + k + '\')" title="' + desc + '">' +
                       '    <i class="ti ti-plus"></i>' +
                       '    <span>' + k + '</span>' +
                       '</div>';
            }).join('');
        } else {
            varsBox.innerHTML = '<span style="font-size:0.75rem;color:#94a3b8;font-style:italic;">Sin variables dinámicas</span>';
        }
    }

    // ── 3. Sincronizar Firmas (Panel Lateral -> Hoja) ─────
    window.sgpSyncFirmas = function () {
        const nombre    = document.getElementById('sgpFirmaNombreInput').value || 'FIRMANTE RESPONSABLE';
        const cargo     = document.getElementById('sgpFirmaCargoInput').value || 'CARGO INSTITUCIONAL';
        const antefirma = document.getElementById('sgpFirmaAntefirmaInput').value || 'Atentamente,';
        const sello     = document.getElementById('sgpSelloToggle').checked;

        document.getElementById('sgpSheetAntefirma').innerText   = antefirma;
        document.getElementById('sgpSheetFirmaNombre').innerText = nombre;
        document.getElementById('sgpSheetFirmaCargo').innerText  = cargo;
        document.getElementById('sgpSheetSelloCol').style.display = sello ? 'block' : 'none';
    };

    // ── Sincronización Inversa (Hoja In-line -> Panel Lateral) ──
    const sheetFirmaNombre = document.getElementById('sgpSheetFirmaNombre');
    const sheetFirmaCargo  = document.getElementById('sgpSheetFirmaCargo');
    const sheetAntefirma   = document.getElementById('sgpSheetAntefirma');

    if (sheetFirmaNombre) {
        sheetFirmaNombre.addEventListener('input', function () {
            document.getElementById('sgpFirmaNombreInput').value = this.innerText.trim();
        });
    }
    if (sheetFirmaCargo) {
        sheetFirmaCargo.addEventListener('input', function () {
            document.getElementById('sgpFirmaCargoInput').value = this.innerText.trim();
        });
    }
    if (sheetAntefirma) {
        sheetAntefirma.addEventListener('input', function () {
            document.getElementById('sgpFirmaAntefirmaInput').value = this.innerText.trim();
        });
    }

    // ── DRAG ENGINE: SELLO OFICIAL ARRASTRABLE LIBREMENTE ──
    let selloTranslateX = 0;
    let selloTranslateY = 0;
    const selloBox = document.getElementById('sgpSheetSelloBox');
    const selloBadge = selloBox ? selloBox.querySelector('.sgp-sello-pos-badge') : null;

    function applySelloPos(x, y) {
        selloTranslateX = Math.round(x);
        selloTranslateY = Math.round(y);
        if (selloBox) {
            selloBox.style.transform = 'translate(' + selloTranslateX + 'px, ' + selloTranslateY + 'px)';
            if (selloBadge) {
                if (selloTranslateX === 0 && selloTranslateY === 0) {
                    selloBadge.innerHTML = '<i class="ti ti-hand-grab"></i> Arrastrar sello';
                } else {
                    selloBadge.innerHTML = '<i class="ti ti-arrows-move"></i> X: ' + selloTranslateX + 'px | Y: ' + selloTranslateY + 'px';
                }
            }
        }
    }

    window.sgpSetSelloPos = function (pos) {
        if (pos === 'left') {
            applySelloPos(-240, 0);
        } else if (pos === 'center') {
            applySelloPos(-120, -15);
        } else if (pos === 'right') {
            applySelloPos(0, 0);
        }
    };

    window.sgpResetSelloPos = function () {
        applySelloPos(0, 0);
    };

    if (selloBox) {
        let isDragging = false;
        let startClientX = 0;
        let startClientY = 0;
        let initialTranslateX = 0;
        let initialTranslateY = 0;

        function startDrag(e) {
            // Si el usuario hace click directamente dentro del texto editable para tipear, no iniciamos arrastre inmediato
            if (e.target && e.target.id === 'sgpSheetSelloText' && document.activeElement === e.target) {
                return;
            }

            isDragging = true;
            selloBox.classList.add('is-dragging');

            const point = e.touches ? e.touches[0] : e;
            startClientX = point.clientX;
            startClientY = point.clientY;
            initialTranslateX = selloTranslateX;
            initialTranslateY = selloTranslateY;

            document.addEventListener('mousemove', onDragMove, { passive: false });
            document.addEventListener('mouseup', endDrag);
            document.addEventListener('touchmove', onDragMove, { passive: false });
            document.addEventListener('touchend', endDrag);
        }

        function onDragMove(e) {
            if (!isDragging) return;
            e.preventDefault();

            const point = e.touches ? e.touches[0] : e;
            const deltaX = point.clientX - startClientX;
            const deltaY = point.clientY - startClientY;

            applySelloPos(initialTranslateX + deltaX, initialTranslateY + deltaY);
        }

        function endDrag() {
            if (!isDragging) return;
            isDragging = false;
            selloBox.classList.remove('is-dragging');

            document.removeEventListener('mousemove', onDragMove);
            document.removeEventListener('mouseup', endDrag);
            document.removeEventListener('touchmove', onDragMove);
            document.removeEventListener('touchend', endDrag);
        }

        selloBox.addEventListener('mousedown', startDrag);
        selloBox.addEventListener('touchstart', startDrag, { passive: true });
    }

    // ── 4. Insertar Variable en la posición del cursor ─────
    window.sgpInsertVariable = function (varName) {
        const sheetBody = document.getElementById('sgpSheetBody');
        sheetBody.focus();

        if (document.queryCommandSupported('insertText')) {
            document.execCommand('insertText', false, varName);
        } else {
            const sel = window.getSelection();
            if (sel.getRangeAt && sel.rangeCount) {
                const range = sel.getRangeAt(0);
                range.deleteContents();
                const node = document.createTextNode(varName);
                range.insertNode(node);
                range.setStartAfter(node);
                range.collapse(true);
                sel.removeAllRanges();
                sel.addRange(range);
            } else {
                sheetBody.innerHTML += ' ' + varName;
            }
        }
    };

    // ── 5. Filtrar Plantillas por Categoría ────────────────
    window.sgpFilterCat = function (cat, btn) {
        document.querySelectorAll('.sgp-tpl-cat-chip').forEach(function (c) {
            c.classList.remove('active');
        });
        if (btn) btn.classList.add('active');

        document.querySelectorAll('.sgp-tpl-item-btn').forEach(function (item) {
            if (cat === 'ALL' || item.dataset.cat === cat) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    };

    // ── 6. Subir Nuevo Cintillo ────────────────────────────
    window.sgpUploadCintillo = function (input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const formData = new FormData();
        formData.append('cintillo_file', file);

        fetch(URLROOT + '/superadmin/subirCintilloAjax', {
            method: 'POST',
            body: formData
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                if (window.NotificationService) window.NotificationService.success(res.message);
                const newUrl = URLROOT + '/' + res.url.replace(/^\/+/, '');
                document.getElementById('sgpSheetCintilloImg').src = newUrl;
                document.getElementById('sgpCintilloImg').src      = newUrl;
                if (currentTemplateData) {
                    currentTemplateData.cintillo_url = res.url;
                }
            } else {
                if (window.NotificationService) window.NotificationService.error(res.message);
            }
            input.value = '';
        })
        .catch(function (err) {
            console.error(err);
            if (window.NotificationService) window.NotificationService.error('Error al subir la imagen del cintillo');
            input.value = '';
        });
    };

    window.sgpResetCintillo = function () {
        const defaultUrl = 'img/cintillo.png';
        document.getElementById('sgpSheetCintilloImg').src = URLROOT + '/' + defaultUrl;
        document.getElementById('sgpCintilloImg').src      = URLROOT + '/' + defaultUrl;
        if (currentTemplateData) {
            currentTemplateData.cintillo_url = defaultUrl;
        }
        if (window.NotificationService) window.NotificationService.info('Cintillo restablecido al oficial predeterminado');
    };

    // ── 7. Guardar Plantilla Maestra ───────────────────────
    window.sgpSaveTemplate = function () {
        const titulo = document.getElementById('sgpSheetTitle').innerText.trim();
        const cuerpo = document.getElementById('sgpSheetBody').innerHTML;
        const pie    = document.getElementById('sgpSheetFooter').innerText.trim();
        const cintillo = (currentTemplateData && currentTemplateData.cintillo_url) ? currentTemplateData.cintillo_url : 'img/cintillo.png';

        const firmas = {
            firmante_nombre: document.getElementById('sgpFirmaNombreInput').value.trim() || document.getElementById('sgpSheetFirmaNombre').innerText.trim(),
            firmante_cargo:  document.getElementById('sgpFirmaCargoInput').value.trim() || document.getElementById('sgpSheetFirmaCargo').innerText.trim(),
            antefirma:       document.getElementById('sgpFirmaAntefirmaInput').value.trim() || document.getElementById('sgpSheetAntefirma').innerText.trim(),
            institucion:     'Instituto de Salud Pública del Estado Bolívar',
            mostrar_sello:   document.getElementById('sgpSelloToggle').checked,
            sello_texto:     document.getElementById('sgpSheetSelloText') ? document.getElementById('sgpSheetSelloText').innerText.trim() : 'Sello Oficial',
            sello_pos:       { x: Math.round(selloTranslateX), y: Math.round(selloTranslateY) }
        };

        const payload = {
            codigo:              currentCode,
            titulo_documento:    titulo,
            subtitulo_documento: currentTemplateData ? currentTemplateData.subtitulo_documento : '',
            cuerpo_html:         cuerpo,
            cintillo_url:        cintillo,
            firmas:              firmas,
            pie_pagina:          pie,
            orientacion:         (currentTemplateData && currentTemplateData.orientacion) ? currentTemplateData.orientacion : 'portrait',
            tamano_papel:        'letter'
        };

        fetch(URLROOT + '/superadmin/guardarPlantillaAjax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                if (window.NotificationService) window.NotificationService.success(res.message);
                // Marcar como personalizada en la lista
                const selBtn = document.querySelector('.sgp-tpl-item-btn[data-codigo="' + currentCode + '"]');
                if (selBtn) {
                    const badge = selBtn.querySelector('.sgp-tpl-item-badge');
                    if (badge) {
                        badge.className = 'sgp-tpl-item-badge sgp-tpl-badge-custom';
                        badge.textContent = 'Personalizada';
                    }
                }
            } else {
                if (window.NotificationService) window.NotificationService.error(res.message);
            }
        })
        .catch(function (err) {
            console.error(err);
            if (window.NotificationService) window.NotificationService.error('Error de red al guardar la plantilla');
        });
    };

    // ── 8. Vista Previa PDF Real ───────────────────────────
    window.sgpPreviewPdf = function () {
        window.open(URLROOT + '/superadmin/previewPlantillaPdf?codigo=' + encodeURIComponent(currentCode), '_blank');
    };

    // ── 9. Restablecer a Valores de Fábrica ────────────────
    window.sgpResetTemplate = function () {
        const doReset = function () {
            fetch(URLROOT + '/superadmin/resetPlantillaAjax', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ codigo: currentCode })
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    if (window.NotificationService) window.NotificationService.success(res.message);
                    sgpSelectTemplate(currentCode);
                    const selBtn = document.querySelector('.sgp-tpl-item-btn[data-codigo="' + currentCode + '"]');
                    if (selBtn) {
                        const badge = selBtn.querySelector('.sgp-tpl-item-badge');
                        if (badge) {
                            badge.className = 'sgp-tpl-item-badge sgp-tpl-badge-default';
                            badge.textContent = 'Original';
                        }
                    }
                } else {
                    if (window.NotificationService) window.NotificationService.error(res.message);
                }
            })
            .catch(function (err) {
                console.error(err);
                if (window.NotificationService) window.NotificationService.error('Error al restablecer plantilla');
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Restablecer al original?',
                text: 'Esta plantilla volverá a los textos, membretes y firmas oficiales de fábrica.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, restablecer',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb'
            }).then(function (res) {
                if (res.isConfirmed) doReset();
            });
        } else {
            if (confirm('¿Restablecer esta plantilla a su redacción original de fábrica?')) {
                doReset();
            }
        }
    };

    // Carga inicial de la primera plantilla
    const firstBtn = document.querySelector('.sgp-tpl-item-btn');
    if (firstBtn) {
        sgpSelectTemplate(firstBtn.dataset.codigo, firstBtn);
    }

})();
</script>
