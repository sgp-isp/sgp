<?php
/**
 * VISTA: CENTRO DE REPORTES - REDISEÑO PREMIUM (ANILLOS SÓLIDOS + GRID 4x2)
 * Arquitectura UI/UX Premium - Sistema SGP
 */
$rolId = (int)($data['rolId'] ?? Session::get('role_id') ?? 0);
$esTutor = ($rolId === 2);
?>

<style>
:root {
    --p-surface:    #FFFFFF;
    --p-surface-2:  #F8FAFD;
    --p-border:     #DDE2F0;
    --p-ink:        #0D1424;
    --p-ink-2:      #3A4768;
    --p-ink-3:      #7480A0;
    --p-blue:       #1D4ED8;
    --p-blue-dim:   rgba(29, 78, 216, 0.07);
    --p-green:      #059669;
    --p-green-dim:  rgba(5, 150, 105, 0.08);
    --p-red:        #dc2626;
    --p-red-dim:    rgba(220, 38, 38, 0.08);
    --p-amber:      #f59e0b;
    --p-amber-dim:  rgba(245, 158, 11, 0.08);
    --p-purple:     #8b5cf6;
    --p-purple-dim: rgba(139, 92, 246, 0.08);
    --p-pink:       #ec4899;
    --p-pink-dim:   rgba(236, 72, 153, 0.08);
    --p-radius:     20px;
    --p-shadow:     0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.05);
    --p-font:       'Geist', sans-serif;
}

.muro-premium { font-family: var(--p-font); width: 100%; margin: 0 auto; padding: 20px; }

/* ── BANNER PREMIUM ── */
.p-banner {
    background: linear-gradient(135deg, var(--color-primary, #162660) 0%, var(--gradient-mid, #1e3a8a) 50%, var(--color-accent-brand, #2563eb) 100%);
    border-radius: 24px; padding: 30px 40px; margin-bottom: 30px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 10px 30px rgba(23,37,84,0.15);
}
.btn-ejecutivo {
    background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3);
    padding: 10px 20px; border-radius: 12px; font-weight: 700; font-family: var(--p-font);
    cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;
}
.btn-ejecutivo:hover { background: white; color: #1e3a8a; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

/* ── GRID DE 8 CARTAS - CONFIGURACIÓN 4x2 ── */
.p-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
    gap: 24px; 
}

@media (min-width: 1300px) {
    .p-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.p-card {
    background: var(--p-surface); border: 1px solid var(--p-border); border-radius: var(--p-radius);
    padding: 24px; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); position: relative;
    overflow: hidden; display: flex; flex-direction: column; align-items: center;
    text-align: center; box-shadow: var(--p-shadow);
}
.p-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border-color: var(--p-blue); }

.p-card-header { width: 100%; display: flex; justify-content: space-between; margin-bottom: 15px; }
.p-badge { padding: 6px 14px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; letter-spacing: 0.3px; }
.p-badge-active { background: var(--p-blue-dim); color: var(--p-blue); }
.p-badge-final  { background: var(--p-green-dim); color: var(--p-green); }

.p-card-title { font-size: 1.15rem; font-weight: 800; color: var(--p-ink); margin: 0 0 4px 0; }
.p-card-subtitle { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 8px 0; }
.p-card-desc { font-size: 0.8rem; color: var(--p-ink-3); line-height: 1.4; margin-bottom: 20px; flex-grow: 1; max-width: 90%; }

/* ── EL NUEVO ICON RING (SÓLIDO) ── */
.p-icon-ring {
    width: 90px; height: 90px; border-radius: 50%;
    border: 3px solid #F1F5F9;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 24px; transition: all 0.4s ease;
    background: var(--p-surface-2);
}
.p-card:hover .p-icon-ring {
    transform: scale(1.1);
    border-color: currentColor;
    background: white;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.p-card-footer { width: 100%; display: flex; gap: 12px; border-top: 1px solid var(--p-border); padding-top: 16px; margin-top: auto; }
.btn-p-soft { flex: 1; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important; border: 1.5px solid transparent; font-weight: 700 !important; font-family: var(--p-font); background: transparent; padding: 10px; border-radius: 12px; display: flex; justify-content: center; align-items: center; gap: 6px; cursor: pointer; font-size: 0.85rem; }
.btn-soft-pdf { color: var(--p-red) !important; border-color: var(--p-red-dim) !important; background: var(--p-red-dim); }
.btn-soft-pdf:hover { background: var(--p-red) !important; color: white !important; }
.btn-soft-excel { color: var(--p-green) !important; border-color: var(--p-green-dim) !important; background: var(--p-green-dim); }
.btn-soft-excel:hover { background: var(--p-green) !important; color: white !important; }
.btn-soft-excel:hover i { color: white !important; }
.btn-soft-indigo { color: #4f46e5 !important; border-color: #e0e7ff !important; background: #eef2ff; }
.btn-soft-indigo:hover { background: #4f46e5 !important; color: white !important; }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ── MODAL ULTRA PREMIUM (Glassmorphism & Minimalism) ── */
.sgp-modal-overlay { 
    backdrop-filter: blur(8px) saturate(180%);
    -webkit-backdrop-filter: blur(8px) saturate(180%);
    background: rgba(15, 23, 42, 0.4);
    transition: all 0.3s ease;
}

.sgp-modal-view-report { 
    max-width: 580px; 
    width: 95%;
    border-radius: 24px; 
    border: 1px solid rgba(255, 255, 255, 0.7);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    background: #FFFFFF;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

#formReportes {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

.sgp-modal-view-report .sgp-modal-body {
    overflow-y: auto !important;
    flex: 1;
    padding: 20px 24px !important;
}

.sgp-modal-view-report .sgp-modal-actions {
    flex-shrink: 0;
    padding: 16px 24px 20px !important;
    border-top: 1px solid #f1f5f9;
    background: #ffffff;
}

.p-modal-premium-header {
    padding: 22px 26px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    border-left: none;
    margin-top: 0;
    flex-shrink: 0;
}

.r-form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
.r-form-group label { font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.6px; margin-left: 2px; }
.r-form-control { width: 100%; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 12px; font-size: 0.88rem; font-family: var(--p-font); outline: none; background: #ffffff; color: var(--p-ink); transition: all 0.2s ease; font-weight: 500; }
.r-form-control:focus { border-color: var(--p-blue); background: #fff; box-shadow: 0 0 0 3px var(--p-blue-dim); }

/* Estilos de inputs y selects modernos Bento */
.sgp-input-box { display: flex; flex-direction: column; gap: 5px; margin-bottom: 10px; }
.sgp-input-box label { font-size: 0.72rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.6px; display: flex; align-items: center; gap: 4px; }
.sgp-input-modern, .sgp-select-modern {
    width: 100%; height: 42px; padding: 0 12px; border: 1.5px solid #cbd5e1; border-radius: 12px;
    font-size: 0.88rem; font-weight: 600; color: #0f172a; background: #ffffff; outline: none;
    transition: all 0.2s ease; box-sizing: border-box; font-family: var(--p-font);
}
.sgp-input-modern:focus, .sgp-select-modern:focus {
    border-color: #2563eb; background: #ffffff; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}
.sgp-input-modern::placeholder { color: #94a3b8; font-weight: 400; }

/* ── Pill Toggle — período ejecutivo ── */
.ej-periodo-grupo { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 6px; }
.ej-pill { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 12px; border-radius: 50px; border: 1.5px solid #dde2f0; background: #f8fafc; color: var(--p-ink-2); font-size: 0.85rem; font-weight: 600; font-family: var(--p-font); cursor: pointer; user-select: none; text-align: center; width: 100%; transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease; }
.ej-pill input[type="radio"] { display: none; }
.ej-pill:hover { border-color: var(--p-blue); color: var(--p-blue); background: var(--p-blue-dim); }
.ej-pill:has(input[type="radio"]:checked) { background: #162660; border-color: #162660; color: #ffffff; box-shadow: 0 4px 12px rgba(22, 38, 96, 0.25); }

/* ── Botón cerrar Ghost Red ── */
.btn-close-ghost-red { background: transparent; border: none; color: #b91c1c; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s ease, color 0.2s ease; }
.btn-close-ghost-red:hover { background: rgba(220, 38, 38, 0.1); color: #dc2626; }

/* ── Botones de trimestre ── */
.trim-btn-sgp {
    border-radius: 14px;
    border: 2px solid #e2e8f0;
    background: white;
    padding: 12px 6px;
    text-align: center;
    cursor: pointer;
    transition: all .22s cubic-bezier(.4,0,.2,1);
    width: 100%;
    color: #64748b;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    position: relative;
    overflow: hidden;
}
.trim-btn-sgp:hover:not(.bloqueado) {
    border-color: #2563eb;
    background: #eff6ff;
    color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37,99,235,.15);
}
/* T1 activo — Azul (coherente con el header del modal) */
.trim-btn-sgp[data-trim="1"].activo {
    border-color: #2563eb;
    background: linear-gradient(135deg,#172554,#2563eb);
    color: white;
    box-shadow: 0 4px 14px rgba(37,99,235,.4);
    transform: translateY(-2px);
}
/* T2 activo — Índigo */
.trim-btn-sgp[data-trim="2"].activo {
    border-color: #4f46e5;
    background: linear-gradient(135deg,#312e81,#4f46e5);
    color: white;
    box-shadow: 0 4px 14px rgba(79,70,229,.4);
    transform: translateY(-2px);
}
/* T3 activo — Violeta */
.trim-btn-sgp[data-trim="3"].activo {
    border-color: #7c3aed;
    background: linear-gradient(135deg,#4c1d95,#7c3aed);
    color: white;
    box-shadow: 0 4px 14px rgba(124,58,237,.4);
    transform: translateY(-2px);
}
/* sub-label blanco cuando activo */
.trim-btn-sgp.activo div:last-child { color: rgba(255,255,255,.75) !important; }
.trim-btn-sgp.bloqueado {
    opacity: .35;
    cursor: not-allowed;
    pointer-events: none;
}

/* ── SweetAlert2 — Popup Bento Premium ── */
.swal2-bento-popup {
    border-radius: 24px !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15) !important;
    padding: 28px 24px !important;
    font-family: 'Geist', sans-serif !important;
}
.swal2-bento-popup .swal2-html-container {
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}
.swal2-bento-popup .swal2-actions {
    margin-top: 20px !important;
}
.swal2-bento-popup .swal2-confirm {
    border-radius: 12px !important;
    font-weight: 700 !important;
    font-size: 0.9rem !important;
    padding: 10px 28px !important;
    letter-spacing: 0.3px !important;
}
</style>

<div class="muro-premium">
    <!-- BANNER PREMIUM -->
    <style>
    @media (max-width: 991px) {
        .dashboard-banner {
            flex-direction: column !important;
            align-items: flex-start !important;
            padding: 24px 20px !important;
            gap: 20px !important;
        }
        .dashboard-banner .btn-ejecutivo {
            width: 100% !important;
            justify-content: center !important;
        }
    }
    </style>
    <div class="p-banner dashboard-banner">
        <div style="display:flex; align-items:center; gap:20px;">
            <div style="background:rgba(255,255,255,0.15);border-radius:16px;padding:14px;backdrop-filter:blur(4px);">
                <i class="ti ti-printer" style="font-size:34px;color:white;"></i>
            </div>
            <div>
                <h1 style="color:white;font-size:1.8rem;font-weight:800;margin:0;">Centro de Reportes</h1>
                <p style="color:rgba(255,255,255,0.8);margin:4px 0 0;font-size:0.9rem;font-weight:500;">Generación de formatos institucionales y sábanas de datos.</p>
            </div>
        </div>
        <?php if (!$esTutor): ?>
        <button onclick="abrirModal('ejecutivo', 'pdf')" class="btn-ejecutivo">
            <i class="ti ti-bolt" style="font-size:1.2rem;"></i> Resumen Ejecutivo
        </button>
        <?php endif; ?>
    </div>

    <!-- GRID DE 8 CARTAS PERFECTA (4x2) -->
    <div class="p-grid">
        
        <!-- 1. Usuarios — Solo Admin -->
        <?php if (!$esTutor): ?>
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge p-badge-active"><i class="ti ti-check"></i> DISPONIBLE</span></div>
            <h3 class="p-card-title">Usuarios</h3><h4 class="p-card-subtitle" style="color:var(--p-blue);">SISTEMA</h4>
            <p class="p-card-desc">Personal administrativo y tutores.</p>
            <div class="p-icon-ring" style="color: var(--p-blue);">
                <i class="ti ti-users" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('usuarios', 'pdf')"><i class="ti ti-file-type-pdf"></i> PDF</button>
                <button class="btn-p-soft btn-soft-excel" onclick="abrirModal('usuarios', 'excel')"><i class="ti ti-file-spreadsheet"></i> EXCEL</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- 2. Pasantes (Admin: todos / Tutor: solo sus asignados) -->
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge p-badge-final"><i class="ti ti-check"></i> DISPONIBLE</span></div>
            <h3 class="p-card-title"><?= $esTutor ? 'Mis Pasantes' : 'Pasantes' ?></h3>
            <h4 class="p-card-subtitle" style="color:var(--p-green);">GESTIÓN ACADÉMICA</h4>
            <p class="p-card-desc"><?= $esTutor ? 'Ficha de tus pasantes asignados.' : 'Ficha general e instituciones.' ?></p>
            <div class="p-icon-ring" style="color: var(--p-green);">
                <i class="ti ti-id" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('pasantes', 'pdf')"><i class="ti ti-file-type-pdf"></i> PDF</button>
                <button class="btn-p-soft btn-soft-excel" onclick="abrirModal('pasantes', 'excel')"><i class="ti ti-file-spreadsheet"></i> EXCEL</button>
            </div>
        </div>

        <!-- 3. Asistencias -->
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge" style="background:var(--p-amber-dim); color:var(--p-amber);"><i class="ti ti-check"></i> DISPONIBLE</span></div>
            <h3 class="p-card-title">Control Asistencia</h3><h4 class="p-card-subtitle" style="color:var(--p-amber);">MONITOREO</h4>
            <p class="p-card-desc">Listados y planilla individual ISP.</p>
            <div class="p-icon-ring" style="color: var(--p-amber);">
                <i class="ti ti-calendar-stats" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('asistencias', 'pdf')"><i class="ti ti-file-type-pdf"></i> PDF</button>
                <button class="btn-p-soft btn-soft-excel" onclick="abrirModal('asistencias', 'excel')"><i class="ti ti-file-spreadsheet"></i> EXCEL</button>
            </div>
        </div>

        <!-- 4. Evaluaciones -->
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge" style="background:var(--p-pink-dim); color:var(--p-pink);"><i class="ti ti-lock"></i> ISP OFICIAL</span></div>
            <h3 class="p-card-title">Evaluaciones</h3><h4 class="p-card-subtitle" style="color:var(--p-pink);">RENDIMIENTO</h4>
            <p class="p-card-desc">Plantilla institucional (14 ítems).</p>
            <div class="p-icon-ring" style="color: var(--p-pink);">
                <i class="ti ti-clipboard-data" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('evaluaciones', 'pdf')"><i class="ti ti-file-type-pdf"></i> PLANILLA PDF</button>
            </div>
        </div>

        <!-- 5. Asignaciones — Solo Admin -->
        <?php if (!$esTutor): ?>
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge" style="background:var(--p-purple-dim); color:var(--p-purple);"><i class="ti ti-check"></i> DISPONIBLE</span></div>
            <h3 class="p-card-title">Asignaciones</h3><h4 class="p-card-subtitle" style="color:var(--p-purple);">OPERATIVO</h4>
            <p class="p-card-desc">Relación Pasante - Tutor - Depto.</p>
            <div class="p-icon-ring" style="color: var(--p-purple);">
                <i class="ti ti-link" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('asignaciones', 'pdf')"><i class="ti ti-file-type-pdf"></i> PDF</button>
                <button class="btn-p-soft btn-soft-excel" onclick="abrirModal('asignaciones', 'excel')"><i class="ti ti-file-spreadsheet"></i> EXCEL</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- 6. Bitácora — Solo Admin -->
        <?php if (!$esTutor): ?>
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge" style="background:rgba(116, 128, 160, 0.1); color:var(--p-ink-3);"><i class="ti ti-check"></i> DISPONIBLE</span></div>
            <h3 class="p-card-title">Auditoría</h3><h4 class="p-card-subtitle" style="color:var(--p-ink-3);">SEGURIDAD</h4>
            <p class="p-card-desc">Historial de acciones del sistema.</p>
            <div class="p-icon-ring" style="color: var(--p-ink-3);">
                <i class="ti ti-history" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('bitacora', 'pdf')"><i class="ti ti-file-type-pdf"></i> PDF</button>
                <button class="btn-p-soft btn-soft-excel" onclick="abrirModal('bitacora', 'excel')"><i class="ti ti-file-spreadsheet"></i> EXCEL</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- 7. Ficha Diaria — Solo Admin (es reporte grupal de todo el sistema) -->
        <?php if (!$esTutor): ?>
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge" style="background:rgba(13,20,36,0.1); color:var(--p-ink);"><i class="ti ti-activity"></i> MONITOREO</span></div>
            <h3 class="p-card-title">Ficha Diaria</h3><h4 class="p-card-subtitle" style="color:var(--p-ink);">SUPERVISIÓN</h4>
            <p class="p-card-desc">Actividad grupal del día por departamento.</p>
            <div class="p-icon-ring" style="color: var(--p-ink);">
                <i class="ti ti-sun" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('diaria', 'pdf')"><i class="ti ti-file-type-pdf"></i> PDF</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- 8. Constancias individuales -->
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge p-badge-final"><i class="ti ti-certificate"></i> OFICIAL</span></div>
            <h3 class="p-card-title">Constancias</h3><h4 class="p-card-subtitle" style="color:var(--p-green);">INDIVIDUAL</h4>
            <p class="p-card-desc">Carta de culminación o servicio por pasante.</p>
            <div class="p-icon-ring" style="color: var(--p-green);">
                <i class="ti ti-award" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer" style="gap:8px;">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('constancias', 'pdf')" style="flex:1.2;">
                    <i class="ti ti-file-type-pdf"></i> EMITIR CARTA
                </button>
                <button type="button" class="btn-p-soft" onclick="abrirModalHistorialConstancias()" style="flex:1; background:rgba(22,38,96,0.06); color:#162660; border-color:rgba(22,38,96,0.15);" title="Ver constancias emitidas y respaldadas">
                    <i class="ti ti-archive"></i> HISTORIAL
                </button>
            </div>
        </div>

        <!-- 9. Constancia Grupal (nueva) -->
        <?php if (!$esTutor): ?>
        <div class="p-card">
            <div class="p-card-header"><span class="p-badge p-badge-final"><i class="ti ti-files"></i> NUEVO</span></div>
            <h3 class="p-card-title">Constancia Grupal</h3><h4 class="p-card-subtitle" style="color:#7c3aed;">COLECTIVO</h4>
            <p class="p-card-desc">Acta con todos los pasantes — por departamento o institución.</p>
            <div class="p-icon-ring" style="color:#7c3aed;">
                <i class="ti ti-users-group" style="font-size: 2.5rem;"></i>
            </div>
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" style="color:#7c3aed!important;border-color:#ede9fe!important;background:#f5f3ff;"
                        onclick="abrirModal('constancias_grupal', 'pdf')">
                    <i class="ti ti-file-type-pdf"></i> GENERAR ACTA
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── MODAL DE EXPORTACIÓN (ULTRA PREMIUM REDESIGN) ── -->
<div class="sgp-modal-overlay" id="modalConfig" onclick="if(event.target===this)cerrarModal()">
    <div class="sgp-modal sgp-modal-view-report">
        <div class="p-modal-premium-header" style="background: linear-gradient(135deg, #162660 0%, #1e3a8a 60%, #2563eb 100%); border-radius: 20px 20px 0 0; margin-top: 0; border-left: none; padding: 24px 28px;">
            <div>
                <h3 id="mTitle" style="color:#ffffff; font-weight: 800; font-size: 1.25rem; margin: 0;">Configurar Documento</h3>
                <p id="mSubtitle" style="color:rgba(255,255,255,0.7); font-size: 0.85rem; margin: 5px 0 0;">Indique los parámetros del reporte</p>
            </div>
            <button class="btn-close-ghost-red" onclick="cerrarModal()" style="color:rgba(255,255,255,0.7);" onmouseenter="this.style.background='rgba(255,255,255,0.15)';this.style.color='#fff';" onmouseleave="this.style.background='transparent';this.style.color='rgba(255,255,255,0.7)';">
                <i class="ti ti-x" style="font-size:1.2rem;"></i>
            </button>
        </div>
        
        <form id="formReportes" action="<?= URLROOT ?>/reportes/exportar" method="POST" target="_blank" onsubmit="return validarGeneracion(event)">
            <div class="sgp-modal-body" style="padding: 24px 28px; position:relative; z-index:20;">
                <input type="hidden" name="modulo" id="mModulo">
                <input type="hidden" name="tipo" id="mFormato">
                <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">

                <div id="mFiltros" style="display:flex; flex-direction:column; gap:12px;"></div>

                <!-- BUSCADOR PERMANENTE — se muestra/oculta por JS -->
                <div id="boxBuscadorPasante" style="display:none; margin-bottom:14px;">

                    <!-- Input de búsqueda — se oculta al seleccionar -->
                    <div id="contenedorInputPasante" style="position:relative;">
                        <div class="r-form-group" style="margin-bottom:6px;">
                            <label>Buscar Pasante</label>
                        </div>
                        <div style="position:relative;">
                            <i class="ti ti-search"
                               style="position:absolute; left:14px; top:13px;
                                      color:#94a3b8; font-size:1rem; pointer-events:none;"></i>
                            <input type="text"
                                   id="pasanteBusqueda"
                                   class="r-form-control"
                                   placeholder="Cédula o apellidos..."
                                   autocomplete="off"
                                   style="padding-left:42px;">
                        </div>
                        <div id="pasanteResultados"
                             style="display:none; position:relative; width:100%;
                                    background:#fff; border:1px solid #e2e8f0;
                                    border-radius:14px; max-height:200px; overflow-y:auto;
                                    box-shadow:0 8px 24px rgba(15,23,42,.06);
                                    margin-top:8px; margin-bottom:8px;">
                        </div>
                    </div>

                    <!-- Bento pasante seleccionado — oculto hasta selección -->
                    <div id="bentoPasanteReportes"
                         style="display:none; background:#f8fafc;
                                border:1.5px solid #e2e8f0; border-radius:12px;
                                padding:14px; margin-top:4px;">
                        <div style="display:flex; justify-content:space-between;
                                    align-items:center; margin-bottom:10px;">
                            <div style="display:flex; gap:10px; align-items:center;">
                                <div id="bentoAvatarReportes"
                                     style="width:42px; height:42px; border-radius:10px;
                                            background:linear-gradient(135deg,#10b981,#059669);
                                            color:#fff; display:flex; align-items:center;
                                            justify-content:center; font-weight:800;
                                            font-size:1rem;
                                            box-shadow:0 4px 10px rgba(16,185,129,.2);
                                            flex-shrink:0;">
                                </div>
                                <div>
                                    <div id="bentoNombreReportes"
                                         style="font-size:.95rem; font-weight:800;
                                                color:#1e293b; margin:0;">—</div>
                                    <div style="font-size:.78rem; color:#64748b; margin-top:2px;">
                                        C.I: <span id="bentoCedulaReportes"
                                                   style="font-weight:700; color:#475569;">—</span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" onclick="limpiarBentoPasante()"
                                    style="background:#fff; border:1px solid #fee2e2;
                                           color:#ef4444; cursor:pointer; font-size:.72rem;
                                           font-weight:700; padding:6px 10px;
                                           display:flex; align-items:center; gap:4px;
                                           border-radius:8px; transition:all .15s;"
                                    onmouseover="this.style.background='#fee2e2'"
                                    onmouseout="this.style.background='#fff'">
                                <i class="ti ti-exchange" style="font-size:.85rem;"></i>
                                Cambiar
                            </button>
                        </div>
                        <div style="background:#eff6ff; padding:10px 12px;
                                    border-radius:8px; border:1px dashed #bfdbfe;">
                            <div style="font-size:.62rem; font-weight:800; color:#3b82f6;
                                        text-transform:uppercase; letter-spacing:.6px;
                                        margin-bottom:2px;">
                                Institución de Procedencia</div>
                            <div id="bentoInstReportes"
                                 style="font-size:.82rem; color:#1e3a8a; font-weight:700;">
                                —</div>
                        </div>
                    </div>

                    <input type="hidden" name="pasante_id" id="pasanteIdHidden" value="">
                </div>

                <!-- SELECTOR DE TRIMESTRE PERMANENTE -->
                <div id="boxSelectorTrimestre"
                     style="display:none; margin-bottom:14px;">
                    <div class="r-form-group" style="margin-bottom:8px;">
                        <label>Seleccionar Trimestre</label>
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(3,1fr);
                                gap:8px; margin-bottom:10px;" id="trimBotones">
                        <button type="button" class="trim-btn-sgp"
                                data-trim="1" onclick="seleccionarTrimestre(1)">
                            <div style="font-size:16px;font-weight:900;letter-spacing:-.3px;">T1</div>
                            <div style="font-size:9px;margin-top:3px;font-weight:600;">Trimestre 1</div>
                        </button>
                        <button type="button" class="trim-btn-sgp"
                                data-trim="2" onclick="seleccionarTrimestre(2)">
                            <div style="font-size:16px;font-weight:900;letter-spacing:-.3px;">T2</div>
                            <div style="font-size:9px;margin-top:3px;font-weight:600;">Trimestre 2</div>
                        </button>
                        <button type="button" class="trim-btn-sgp"
                                data-trim="3" onclick="seleccionarTrimestre(3)">
                            <div style="font-size:16px;font-weight:900;letter-spacing:-.3px;">T3</div>
                            <div style="font-size:9px;margin-top:3px;font-weight:600;">Trimestre 3</div>
                        </button>
                    </div>
                    <input type="hidden" name="trimestre" id="trimestreHidden" value="1">
                    <div id="alertaTrimestre" style="display:none;
                         background:#FAEEDA; border-radius:14px; padding:10px 14px;
                         font-size:11px; color:#854F0B; line-height:1.5;">
                    </div>
                </div>

                <div id="mRangoFechas" style="display: flex; gap: 12px; margin-top: 16px;">
                    <div class="r-form-group" style="flex: 1;">
                        <label>Desde</label>
                        <input type="text" id="fechaInicioInput" name="fecha_inicio" class="r-form-control" placeholder="YYYY-MM-DD">
                    </div>
                    <div class="r-form-group" id="boxFechaFin" style="flex: 1;">
                        <label>Hasta</label>
                        <input type="text" id="fechaFinInput" name="fecha_fin" class="r-form-control" placeholder="YYYY-MM-DD">
                    </div>
                </div>
            </div>
            
            <div class="sgp-modal-actions" style="padding: 0 28px 24px; display: flex; gap: 12px;">
                <button type="button" class="sgp-btn-action sgp-btn-close-action" style="flex:0.6" onclick="cerrarModal()">Cancelar</button>
                <button type="button" class="btn-p-soft btn-soft-excel" id="mBtnExcel" onclick="submitConFormato('excel', this)" style="flex:1;">
                    <i class="ti ti-file-spreadsheet"></i> Excel
                </button>
                <button type="button" class="btn-p-soft btn-soft-indigo" id="mBtnTodosTrimestres"
                        onclick="generarTodosTrimestres()" style="display:none; flex:1.2;">
                    <i class="ti ti-files"></i> T1+T2+T3
                </button>
                <button type="button" class="btn-p-soft btn-soft-pdf" id="mBtnPdf" onclick="submitConFormato('pdf', this)" style="flex:1.2;">
                    <i class="ti ti-file-type-pdf"></i> Generar PDF
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL HISTORIAL DE CONSTANCIAS EMITIDAS (RESPALDO INSTITUCIONAL) ── -->
<div class="sgp-modal-overlay" id="modalHistorialConstancias" onclick="if(event.target===this)cerrarModalHistorialConstancias()">
    <div class="sgp-modal" style="max-width: 980px; width: 95%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);">
        <!-- Encabezado Bento Premium -->
        <div style="background: linear-gradient(135deg, #162660 0%, #1e3a8a 60%, #2563eb 100%); padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:42px; height:42px; border-radius:12px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; color:#fff;">
                    <i class="ti ti-archive" style="font-size:1.4rem;"></i>
                </div>
                <div>
                    <h3 style="color:#ffffff; font-weight: 800; font-size: 1.25rem; margin: 0; display:flex; align-items:center; gap:8px;">
                        Libro de Registro de Constancias
                        <span id="badgeTotalHistorial" style="font-size:0.72rem; background:rgba(255,255,255,0.2); padding:2px 8px; border-radius:20px; font-weight:700;">0 guardadas</span>
                    </h3>
                    <p style="color:rgba(255,255,255,0.75); font-size: 0.82rem; margin: 3px 0 0;">Respaldos oficiales de cartas de culminación y constancias de servicio emitidas</p>
                </div>
            </div>
            <button class="btn-close-ghost-red" onclick="cerrarModalHistorialConstancias()" style="color:rgba(255,255,255,0.85);" onmouseenter="this.style.background='rgba(255,255,255,0.15)';this.style.color='#fff';" onmouseleave="this.style.background='transparent';this.style.color='rgba(255,255,255,0.85)';">
                <i class="ti ti-x" style="font-size:1.3rem;"></i>
            </button>
        </div>

        <!-- Barra de Filtros y Búsqueda Rápida -->
        <div style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:12px 28px; display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; flex-shrink:0;">
            <div style="position:relative; flex:1; min-width:240px; max-width:400px;">
                <i class="ti ti-search" style="position:absolute; left:12px; top:11px; color:#94a3b8; font-size:1rem; pointer-events:none;"></i>
                <input type="text" id="filtroHistorialTexto" class="sgp-input-modern" placeholder="Buscar por pasante, cédula o código..." style="padding-left:36px; height:38px; font-size:0.83rem;" oninput="filtrarHistorialConstanciasLocal()">
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <select id="filtroHistorialTipo" class="sgp-select-modern" style="height:38px; padding:0 12px; font-size:0.82rem; width:auto;" onchange="filtrarHistorialConstanciasLocal()">
                    <option value="">Todos los tipos</option>
                    <option value="culminacion">Carta de Culminación</option>
                    <option value="servicio">Constancia de Servicio</option>
                </select>
                <button type="button" onclick="cargarHistorialConstancias(true)" class="btn-p-soft" style="height:38px; padding:0 14px; background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; font-size:0.82rem; font-weight:700;">
                    <i class="ti ti-refresh"></i> Actualizar
                </button>
            </div>
        </div>

        <!-- Cuerpo del Historial con Scroll -->
        <div class="sgp-modal-body" style="padding: 16px 28px; overflow-y:auto; flex:1; min-height:300px; max-height:calc(90vh - 150px);" id="contenedorTablaHistorial">
            <div style="text-align:center; padding:40px 20px; color:#64748b;">
                <i class="ti ti-loader-2" style="font-size:2rem; animation:spin 1s linear infinite; display:inline-block; margin-bottom:8px; color:#2563eb;"></i>
                <p style="font-size:0.9rem; margin:0; font-weight:600;">Cargando libro de constancias respaldadas...</p>
            </div>
        </div>

        <!-- Pie del Modal -->
        <div style="background:#ffffff; border-top:1px solid #f1f5f9; padding:12px 28px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
            <div style="font-size:0.75rem; color:#64748b;">
                <i class="ti ti-shield-check" style="color:#059669;"></i> Cada constancia cuenta con respaldo institucional y número de control único verificable.
            </div>
            <button type="button" class="sgp-btn-action sgp-btn-close-action" onclick="cerrarModalHistorialConstancias()" style="padding:8px 20px;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- ── MODAL DE EDICIÓN EN VIVO DE CONSTANCIA ── -->
<div class="sgp-modal-overlay" id="modalEditarConstancia" onclick="if(event.target===this)cerrarEditorConstancia()">
    <div class="sgp-modal" style="max-width: 960px; width: 95%; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);">
        <!-- Encabezado -->
        <div style="background: linear-gradient(135deg, #162660 0%, #1e3a8a 60%, #2563eb 100%); padding: 18px 28px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:12px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; color:#fff;">
                    <i class="ti ti-edit" style="font-size:1.3rem;"></i>
                </div>
                <div>
                    <h3 style="color:#ffffff; font-weight: 800; font-size: 1.2rem; margin: 0; display:flex; align-items:center; gap:10px;">
                        Editor en Vivo de Constancia
                        <span id="editBadgeCodigo" style="font-family:monospace; font-size:0.75rem; background:rgba(255,255,255,0.2); padding:3px 10px; border-radius:8px; font-weight:800; letter-spacing:0.5px;">CST-XXXX</span>
                    </h3>
                    <p style="color:rgba(255,255,255,0.75); font-size: 0.8rem; margin: 2px 0 0;">Modifique los datos y el texto del documento. Los cambios se reflejarán en el PDF oficial.</p>
                </div>
            </div>
            <button class="btn-close-ghost-red" onclick="cerrarEditorConstancia()" style="color:rgba(255,255,255,0.85);" onmouseenter="this.style.background='rgba(255,255,255,0.15)';this.style.color='#fff';" onmouseleave="this.style.background='transparent';this.style.color='rgba(255,255,255,0.85)';">
                <i class="ti ti-x" style="font-size:1.3rem;"></i>
            </button>
        </div>

        <form id="formEditarConstancia" onsubmit="guardarEdicionConstancia(event)" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
            <input type="hidden" name="id" id="editConstanciaId">
            <input type="hidden" name="tipo_constancia" id="editTipoConstancia">

            <!-- Cuerpo con scroll -->
            <div class="sgp-modal-body" style="padding: 20px 28px; overflow-y:auto; flex:1;">
                
                <!-- Bento Grid de Datos Principales -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:14px; margin-bottom:16px;">
                    <div style="font-size:0.75rem; font-weight:800; color:#1e3a8a; text-transform:uppercase; letter-spacing:0.6px; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                        <i class="ti ti-id"></i> Datos del Pasante e Institución
                    </div>
                    
                    <div style="display:grid; grid-template-columns: 1.4fr 1fr 1fr; gap:10px; margin-bottom:10px;">
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-user"></i> Nombres y Apellidos</label>
                            <input type="text" id="editNombreCompleto" name="nombre_completo" class="sgp-input-modern" required>
                        </div>
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-id"></i> Cédula</label>
                            <input type="text" id="editCedula" name="cedula" class="sgp-input-modern" required>
                        </div>
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-clock"></i> Período / Duración</label>
                            <input type="text" id="editPeriodoTexto" name="periodo_texto" class="sgp-input-modern" placeholder="Ej. 6 Meses (Período 2024 – 2025)">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1.2fr 1fr 1fr; gap:10px;">
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-school"></i> Institución</label>
                            <input type="text" id="editInstitucion" name="institucion" class="sgp-input-modern">
                        </div>
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-building-community"></i> Departamento</label>
                            <input type="text" id="editDepartamento" name="departamento" class="sgp-input-modern">
                        </div>
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-hourglass"></i> Carga Horaria (Horas)</label>
                            <input type="number" id="editHorasMeta" name="horas_meta" class="sgp-input-modern" value="960">
                        </div>
                    </div>
                </div>

                <!-- Firmante y Cargo -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:14px; margin-bottom:16px;">
                    <div style="font-size:0.75rem; font-weight:800; color:#1e3a8a; text-transform:uppercase; letter-spacing:0.6px; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                        <i class="ti ti-signature"></i> Responsable Firmante
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1.5fr; gap:10px;">
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-user-check"></i> Nombre del Firmante</label>
                            <input type="text" id="editTutorNombre" name="tutor_nombre" class="sgp-input-modern" required>
                        </div>
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-badge"></i> Cargo Oficial</label>
                            <input type="text" id="editTutorCargo" name="tutor_cargo" class="sgp-input-modern" value="Jefe de la División de Soporte Técnico de la Dirección de Telemática" required>
                        </div>
                    </div>
                </div>

                <!-- Editor de Párrafos / Redacción en Vivo -->
                <div style="margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label class="sgp-label-modern" style="margin:0; font-size:0.82rem;">
                            <i class="ti ti-file-text" style="color:var(--p-blue);"></i> Redacción del Documento (Texto Oficial Editable)
                        </label>
                        <button type="button" onclick="regenerarTextoPredeterminado()" style="background:none; border:none; color:#1D4ED8; font-weight:700; font-size:0.75rem; cursor:pointer; display:flex; align-items:center; gap:4px; text-decoration:underline;">
                            <i class="ti ti-refresh"></i> Regenerar redacción base
                        </button>
                    </div>

                    <div style="background:#ffffff; border:1.5px solid #cbd5e1; border-radius:16px; padding:16px; box-shadow:0 4px 12px rgba(0,0,0,0.03);">
                        <p style="font-size:0.72rem; color:#64748b; margin:0 0 8px; line-height:1.4;">
                            <i class="ti ti-info-circle"></i> Puede modificar libremente el texto de los párrafos a continuación. Si deja el campo con su redacción personalizada, el PDF se generará exactamente con este contenido.
                        </p>
                        <textarea id="editCuerpoPersonalizado" name="cuerpo_personalizado" rows="7" 
                                  style="width:100%; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; font-family:Helvetica, Arial, sans-serif; font-size:0.86rem; line-height:1.7; color:#0f172a; resize:vertical; outline:none; box-sizing:border-box;"
                                  placeholder="Escriba o modifique aquí la redacción de la constancia..."></textarea>
                    </div>
                </div>

            </div>

            <!-- Footer de Acciones -->
            <div style="background:#ffffff; border-top:1px solid #f1f5f9; padding:14px 28px; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
                <button type="button" class="sgp-btn-action sgp-btn-close-action" onclick="cerrarEditorConstancia()" style="padding:10px 20px;">
                    Cancelar
                </button>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn-p-soft btn-soft-excel" id="btnGuardarYDescargar" onclick="guardarYDescargarPdf()" style="padding:10px 18px;">
                        <i class="ti ti-download"></i> Guardar y Descargar PDF
                    </button>
                    <button type="submit" class="btn-p-soft btn-soft-pdf" id="btnGuardarCambios" style="padding:10px 22px;">
                        <i class="ti ti-device-floppy"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
var departamentos = <?= json_encode($data['departamentos'] ?? [], JSON_HEX_TAG | JSON_HEX_QUOT) ?>;
var instituciones = <?= json_encode($data['instituciones'] ?? [], JSON_HEX_TAG | JSON_HEX_QUOT) ?>;
var pasantes = <?= json_encode($data['pasantes'] ?? [], JSON_HEX_TAG | JSON_HEX_QUOT) ?>;
var tutores = <?= json_encode($data['tutores'] ?? [], JSON_HEX_TAG | JSON_HEX_QUOT) ?>;

function abrirModal(modulo, formato) {
    document.getElementById('mModulo').value = modulo;
    document.getElementById('mFormato').value = formato;
    
    const mTitle = document.getElementById('mTitle');
    const mBtnPdf = document.getElementById('mBtnPdf');
    const mBtnExcel = document.getElementById('mBtnExcel');
    const mFechas = document.getElementById('mRangoFechas');
    const mSubtitle = document.getElementById('mSubtitle');
    const boxFechaFin = document.getElementById('boxFechaFin');
    
    const nombres = {
        'ejecutivo': 'Resumen Ejecutivo Administrativo',
        'usuarios': 'Directorio de Usuarios', 'pasantes': 'Registro de Pasantes',
        'asistencias': 'Asistencias', 'evaluaciones': 'Evaluaciones (ISP)',
        'asignaciones': 'Matriz de Asignaciones', 'bitacora': 'Auditoría',
        'diaria': 'Ficha Diaria de Actividad', 'constancias': 'Emisión de Constancias',
        'constancias_grupal': 'Constancia Grupal de Pasantes'
    };
    
    // Reset de visual de fechas
    mFechas.style.display = 'flex';
    boxFechaFin.style.opacity = '1';
    boxFechaFin.style.pointerEvents = 'auto';
    document.getElementById('fechaFinInput').disabled = false;

    mTitle.innerHTML = `<i class="ti ti-settings" style="color:var(--p-blue); margin-right:8px;"></i> ${nombres[modulo]}`;
    mSubtitle.innerText = "Complete los filtros para generar el reporte";

    // Mostrar/Ocultar botones según disponibilidad del módulo
    const modulosSoloPdf = ['evaluaciones', 'diaria', 'constancias', 'constancias_grupal', 'ejecutivo'];
    mBtnExcel.style.display = modulosSoloPdf.includes(modulo) ? 'none' : 'flex';

    let htmlFiltros = '';
    mFechas.style.display = 'flex'; 
    
    switch(modulo) {
        case 'ejecutivo':
            mFechas.style.display = 'none'; // los inputs de fecha los gestionamos internamente
            htmlFiltros = `
                <div style="background:var(--p-surface-2); padding:12px; border-radius:12px; font-size:0.85rem; color:var(--p-ink-2); line-height:1.4; margin-bottom:14px;">
                    Este reporte consolida las estadísticas generales de pasantes y la distribución por departamentos.
                </div>
                <div class="r-form-group" style="margin-bottom:6px;">
                    <label>Período del Reporte</label>
                    <div class="ej-periodo-grupo">
                        <label class="ej-pill">
                            <input type="radio" name="tipo_periodo" value="historico" checked>
                            <span><i class="ti ti-infinity"></i> Histórico Total</span>
                        </label>
                        <label class="ej-pill">
                            <input type="radio" name="tipo_periodo" value="hoy">
                            <span><i class="ti ti-calendar-event"></i> Hoy</span>
                        </label>
                        <label class="ej-pill">
                            <input type="radio" name="tipo_periodo" value="mes">
                            <span><i class="ti ti-calendar-month"></i> Mes Actual</span>
                        </label>
                        <label class="ej-pill">
                            <input type="radio" name="tipo_periodo" value="rango">
                            <span><i class="ti ti-calendar-search"></i> Rango Personalizado</span>
                        </label>
                    </div>
                </div>
                <div id="ejRangoFechas" style="display:none; gap:12px;">
                    <div class="r-form-group" style="flex:1; margin-bottom:0;">
                        <label>Desde</label>
                        <input type="text" id="ejFechaInicio" class="r-form-control" placeholder="YYYY-MM-DD" readonly>
                    </div>
                    <div class="r-form-group" style="flex:1; margin-bottom:0;">
                        <label>Hasta</label>
                        <input type="text" id="ejFechaFin" class="r-form-control" placeholder="YYYY-MM-DD" readonly>
                    </div>
                </div>`;
            break;
        case 'usuarios':
            mFechas.style.display = 'none'; // directorio estático, sin filtro de fechas
            htmlFiltros = `
                <div class="r-form-group"><label>Rol</label><select name="rol" class="r-form-control"><option value="todos">Todos</option><option value="Administrador">Administradores</option><option value="Tutor">Tutores</option></select></div>
                <div class="r-form-group"><label>Departamento</label><select name="departamento" class="r-form-control"><option value="todos">Todos</option>${departamentos.map(d => `<option value="${d.id}">${d.nombre}</option>`).join('')}</select></div>
                <div class="r-form-group"><label>Estado</label><select name="estado_usuario" class="r-form-control"><option value="">Todos</option><option value="activo">Activos</option><option value="inactivo">Inactivos</option></select></div>`;
            break;
        case 'pasantes':
            htmlFiltros = `
                <div class="r-form-group"><label>Institución</label><select name="institucion_id" class="r-form-control"><option value="todas">Todas</option>${instituciones.map(i => `<option value="${i.id}">${i.nombre}</option>`).join('')}</select></div>
                <div class="r-form-group"><label>Estado</label><select name="estado" class="r-form-control"><option value="todos">Todos</option><option value="activo">En Curso</option><option value="finalizado">Culminado</option></select></div>`;
            break;
        case 'asistencias':
            mFechas.style.display = 'none';
            htmlFiltros = `
                <div style="display:grid; grid-template-columns:1fr 1fr;
                            gap:10px; margin-bottom:16px;">

                    <div id="modoGrupal"
                         onclick="activarModoAsistencia('grupal')"
                         style="border-radius:16px; border:2px solid #f1f5f9;
                                background:#f8fafc; padding:14px 10px;
                                text-align:center; cursor:pointer;
                                transition:all .2s;">
                        <div style="width:44px; height:44px; border-radius:12px;
                                    background:linear-gradient(135deg,#162660,#2563eb);
                                    margin:0 auto 8px; display:flex;
                                    align-items:center; justify-content:center;">
                            <i class="ti ti-users"
                               style="color:#fff; font-size:1.3rem;"></i>
                        </div>
                        <div style="font-size:12px; font-weight:800;
                                    color:#3A4768; letter-spacing:.2px;">
                            Reporte Grupal</div>
                        <div style="font-size:10px; color:#7480A0;
                                    margin-top:2px;">Por departamento</div>
                    </div>

                    <div id="modoIndividual"
                         onclick="activarModoAsistencia('individual')"
                         style="border-radius:16px; border:2px solid #f1f5f9;
                                background:#f8fafc; padding:14px 10px;
                                text-align:center; cursor:pointer;
                                transition:all .2s;">
                        <div style="width:44px; height:44px; border-radius:12px;
                                    background:linear-gradient(135deg,#059669,#10b981);
                                    margin:0 auto 8px; display:flex;
                                    align-items:center; justify-content:center;">
                            <i class="ti ti-user-check"
                               style="color:#fff; font-size:1.3rem;"></i>
                        </div>
                        <div style="font-size:12px; font-weight:800;
                                    color:#3A4768; letter-spacing:.2px;">
                            Reporte Individual</div>
                        <div style="font-size:10px; color:#7480A0;
                                    margin-top:2px;">Por pasante</div>
                    </div>

                </div>
                <div style="height:1px; background:#f1f5f9;
                            margin-bottom:16px;"></div>
                <div id="contenidoModoAsistencia"></div>
            `;
            break;
        case 'evaluaciones':
            htmlFiltros = '';
            mFechas.style.display = 'none';
            break;
        case 'asignaciones':
            htmlFiltros = `<div class="r-form-group"><label>Departamento / Área</label><select name="departamento_id" class="r-form-control"><option value="todos">Todos</option>${departamentos.map(d => `<option value="${d.id}">${d.nombre}</option>`).join('')}</select></div>`;
            break;
        case 'bitacora':
            htmlFiltros = `<div class="r-form-group"><label>Módulo</label><select name="modulo_log" class="r-form-control"><option value="todos">Todos</option><option value="Login">Accesos</option><option value="Pasantes">Pasantes</option></select></div>`;
            break;
        case 'diaria': {
            // Fecha de hoy como valor por defecto
            const hoyD = new Date();
            const yyyy = hoyD.getFullYear();
            const mm   = String(hoyD.getMonth() + 1).padStart(2, '0');
            const dd   = String(hoyD.getDate()).padStart(2, '0');
            const hoyISO = `${yyyy}-${mm}-${dd}`;

            const diasSemana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
            const meses = ['enero','febrero','marzo','abril','mayo','junio','julio',
                           'agosto','septiembre','octubre','noviembre','diciembre'];

            htmlFiltros = `
                <div class="r-form-group">
                    <label>Departamento</label>
                    <select name="departamento" class="r-form-control">
                        <option value="todos">Todos los departamentos</option>
                        ${departamentos.map(d => `<option value="${d.id}">${d.nombre}</option>`).join('')}
                    </select>
                </div>
                <div class="r-form-group" style="margin-top:8px;">
                    <label style="display:flex; align-items:center; gap:6px;">
                        <i class="ti ti-calendar-search" style="color:#2563eb;"></i>
                        Fecha de la Ficha
                    </label>
                    <input type="text"
                           id="fichaDiariaFecha"
                           name="fecha_diaria"
                           class="r-form-control"
                           value="${hoyISO}"
                           placeholder="YYYY-MM-DD"
                           autocomplete="off"
                           style="font-weight:600; color:#1e3a8a;">
                    <p style="font-size:0.72rem; color:#64748b; margin:4px 0 0 2px;">
                        Por defecto muestra el día actual. Puedes seleccionar cualquier fecha.
                    </p>
                </div>`;

            // Ocultar bloque de fechas genérico — usamos el input propio de arriba
            mFechas.style.display = 'none';

            // Inicializar flatpickr en el input específico de la ficha diaria
            setTimeout(() => {
                const inputFecha = document.getElementById('fichaDiariaFecha');
                if (inputFecha && typeof flatpickr !== 'undefined') {
                    flatpickr(inputFecha, {
                        dateFormat: 'Y-m-d',
                        locale: 'es',
                        defaultDate: hoyISO,
                        maxDate: hoyISO,   // no permite fechas futuras
                        allowInput: true,
                        disableMobile: true, // [SGP-FIX-REPORTES-FECHA-MOBILE]
                        onChange: function(selectedDates, dateStr) {
                            // Actualizar etiqueta descriptiva
                            if (selectedDates.length > 0) {
                                const d = selectedDates[0];
                                const lbl = `${diasSemana[d.getDay()]}, ${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;
                                const p = inputFecha.nextElementSibling;
                                if (p) p.textContent = lbl;
                            }
                        }
                    });
                }
            }, 80);
            break;
        }
        case 'constancias': {
            const currY = new Date().getFullYear();
            let aniosInicioOpts = '';
            let aniosFinOpts = '';
            for (let y = currY; y >= currY - 8; y--) {
                const selIni = (y === currY - 2) ? 'selected' : '';
                const selFin = (y === currY - 1) ? 'selected' : '';
                aniosInicioOpts += `<option value="${y}" ${selIni}>${y}</option>`;
                aniosFinOpts    += `<option value="${y}" ${selFin}>${y}</option>`;
            }

            htmlFiltros = `
                <!-- Switcher de Modo de Emisión (Bento Selector) -->
                <div class="sgp-input-box" style="margin-bottom:12px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label class="sgp-label-modern" style="margin:0;"><i class="ti ti-layers-subtract" style="color:var(--p-blue);"></i> Modo de Emisión</label>
                        <button type="button" onclick="cerrarModal(); abrirModalHistorialConstancias();" style="background:none; border:none; color:#1D4ED8; font-weight:800; font-size:0.75rem; cursor:pointer; display:flex; align-items:center; gap:4px; padding:0; text-decoration:underline;">
                            <i class="ti ti-archive"></i> Ver constancias guardadas
                        </button>
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <button type="button" id="btnModoRegistrado" onclick="cambiarModoConstancia('registrado')"
                                style="padding:10px 12px; border-radius:14px; border:2px solid #1D4ED8; background:rgba(29,78,216,0.06); text-align:left; cursor:pointer; transition:all 0.2s ease;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:34px; height:34px; border-radius:10px; background:rgba(29,78,216,0.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="ti ti-user-check" style="color:#1D4ED8; font-size:1.15rem;"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800; font-size:0.84rem; color:#1D4ED8;">Pasante Registrado</div>
                                    <div style="font-size:0.71rem; color:#64748b;">Cohorte activa en el sistema</div>
                                </div>
                            </div>
                        </button>
                        <button type="button" id="btnModoManual" onclick="cambiarModoConstancia('manual')"
                                style="padding:10px 12px; border-radius:14px; border:1.5px solid #e2e8f0; background:#f8fafc; text-align:left; cursor:pointer; transition:all 0.2s ease;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="width:34px; height:34px; border-radius:10px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="ti ti-history" style="color:#64748b; font-size:1.15rem;"></i>
                                </div>
                                <div>
                                    <div style="font-weight:800; font-size:0.84rem; color:#475569;">Emisión Histórica / Manual</div>
                                    <div style="font-size:0.71rem; color:#64748b;">Egresados o períodos anteriores</div>
                                </div>
                            </div>
                        </button>
                    </div>
                    <input type="hidden" name="modo_emision" id="modoEmisionConstancia" value="registrado">
                </div>

                <!-- MODO 1: REGISTRADO -->
                <div id="boxConstRegistrado">
                    <div class="sgp-input-box" style="margin-bottom:12px;">
                        <label class="sgp-label-modern"><i class="ti ti-file-certificate" style="color:var(--p-blue);"></i> Tipo de Documento</label>
                        <select name="tipo_constancia" id="selTipoConstanciaReg" class="sgp-select-modern">
                            <option value="culminacion">Carta de Culminación de Pasantías</option>
                            <option value="servicio">Constancia de Prestación de Servicio</option>
                        </select>
                    </div>
                </div>

                <!-- MODO 2: MANUAL / HISTÓRICO CON DATOS HEREDADOS -->
                <div id="boxConstManual" style="display:none;">
                    <div style="background:linear-gradient(135deg, rgba(29,78,216,0.06), rgba(37,99,235,0.02)); border:1px solid #bfdbfe; border-radius:14px; padding:10px 14px; margin-bottom:12px; display:flex; align-items:center; gap:12px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:#1D4ED8; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#fff;">
                            <i class="ti ti-certificate" style="font-size:1.25rem;"></i>
                        </div>
                        <div style="font-size:0.77rem; color:#1e3a8a; line-height:1.4;">
                            <strong>Constancia Rápida con Datos Heredados:</strong> Seleccione el lapso temporal y herede automáticamente los técnicos del sistema e instituciones sin necesidad de crear períodos pasados.
                        </div>
                    </div>

                    <!-- Tipo de Documento -->
                    <div class="sgp-input-box" style="margin-bottom:10px;">
                        <label class="sgp-label-modern"><i class="ti ti-file-text" style="color:var(--p-blue);"></i> Tipo de Constancia a Emitir</label>
                        <select name="manual_tipo_constancia" id="selTipoConstanciaMan" class="sgp-select-modern" onchange="document.getElementById('selTipoConstanciaReg').value = this.value;">
                            <option value="culminacion">Carta de Culminación de Pasantías</option>
                            <option value="servicio">Constancia de Prestación de Servicio</option>
                        </select>
                    </div>

                    <!-- Datos del Pasante (Nombre y Cédula) en Bento Grid -->
                    <div style="display:grid; grid-template-columns: 1.35fr 1fr; gap:10px; margin-bottom:10px;">
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-user" style="color:var(--p-blue);"></i> Nombres y Apellidos <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="manualNombreCompleto" name="manual_nombre_completo" class="sgp-input-modern" placeholder="Ej. Juan Carlos Pérez" autocomplete="off">
                        </div>
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-id" style="color:var(--p-blue);"></i> Cédula de Identidad <span style="color:#dc2626;">*</span></label>
                            <input type="text" id="manualCedula" name="manual_cedula" class="sgp-input-modern" placeholder="Ej. V-25.123.456" autocomplete="off">
                        </div>
                    </div>

                    <!-- SECCIÓN PERÍODO Y DURACIÓN (SOPORTE 2024-2025) -->
                    <div style="background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:14px; padding:12px; margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <label class="sgp-label-modern" style="margin:0;"><i class="ti ti-clock-hour-4" style="color:var(--p-blue);"></i> Duración y Lapso del Período</label>
                            <span id="badgePeriodoVisual" style="font-size:0.73rem; color:#1D4ED8; font-weight:800; background:rgba(29,78,216,0.08); padding:3px 10px; border-radius:8px; border:1px solid rgba(29,78,216,0.2);">
                                6 Meses (Período 2024 – 2025)
                            </span>
                        </div>

                        <!-- Botones 3, 6, 9 meses -->
                        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:8px; margin-bottom:10px;" id="gridDuracionMeses">
                            <button type="button" class="btn-duracion-mes" data-meses="3" onclick="seleccionarDuracionMeses(3, this)"
                                    style="padding:8px 6px; border-radius:10px; border:1.5px solid #e2e8f0; background:#ffffff; font-weight:700; font-size:0.82rem; color:#475569; cursor:pointer; text-align:center; transition:all 0.2s;">
                                <i class="ti ti-clock" style="display:block; font-size:1.15rem; margin-bottom:2px; color:#64748b;"></i>
                                3 Meses
                                <div style="font-size:0.67rem; font-weight:500; color:#94a3b8; margin-top:2px;">480 Horas</div>
                            </button>
                            <button type="button" class="btn-duracion-mes activo" data-meses="6" onclick="seleccionarDuracionMeses(6, this)"
                                    style="padding:8px 6px; border-radius:10px; border:2px solid #1D4ED8; background:rgba(29,78,216,0.06); font-weight:800; font-size:0.82rem; color:#1D4ED8; cursor:pointer; text-align:center; transition:all 0.2s;">
                                <i class="ti ti-clock-play" style="display:block; font-size:1.15rem; margin-bottom:2px; color:#1D4ED8;"></i>
                                6 Meses
                                <div style="font-size:0.67rem; font-weight:700; color:#2563eb; margin-top:2px;">960 Horas</div>
                            </button>
                            <button type="button" class="btn-duracion-mes" data-meses="9" onclick="seleccionarDuracionMeses(9, this)"
                                    style="padding:8px 6px; border-radius:10px; border:1.5px solid #e2e8f0; background:#ffffff; font-weight:700; font-size:0.82rem; color:#475569; cursor:pointer; text-align:center; transition:all 0.2s;">
                                <i class="ti ti-clock-check" style="display:block; font-size:1.15rem; margin-bottom:2px; color:#64748b;"></i>
                                9 Meses
                                <div style="font-size:0.67rem; font-weight:500; color:#94a3b8; margin-top:2px;">1440 Horas</div>
                            </button>
                        </div>
                        <input type="hidden" name="manual_duracion_meses" id="manualDuracionMeses" value="6">

                        <!-- Selectores de Año (Desde - Hasta) para períodos como 2024 a 2025 -->
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; background:#ffffff; padding:10px; border-radius:10px; border:1px solid #e2e8f0;">
                            <div>
                                <label style="font-size:0.72rem; font-weight:700; color:#64748b; margin-bottom:4px; display:block;">Desde el año:</label>
                                <select name="manual_anio_inicio" id="manualAnioInicio" class="sgp-select-modern" style="padding:6px 10px; font-size:0.82rem;" onchange="actualizarPrevisualizacionPeriodo()">
                                    ${aniosInicioOpts}
                                </select>
                            </div>
                            <div>
                                <label style="font-size:0.72rem; font-weight:700; color:#64748b; margin-bottom:4px; display:block;">Hasta el año:</label>
                                <select name="manual_anio_fin" id="manualAnioFin" class="sgp-select-modern" style="padding:6px 10px; font-size:0.82rem;" onchange="actualizarPrevisualizacionPeriodo()">
                                    ${aniosFinOpts}
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Institución Heredada + Departamento -->
                    <div style="display:grid; grid-template-columns: 1.25fr 1fr; gap:10px; margin-bottom:10px;">
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-school" style="color:var(--p-blue);"></i> Institución Universitaria</label>
                            <select name="manual_institucion" id="manualInstitucion" class="sgp-select-modern" onchange="cambiarInstitucionManual(this.value)">
                                <option value="">— Seleccionar del sistema —</option>
                                ${instituciones.map(i => `<option value="${i.nombre}">${i.nombre} (${i.categoria || i.tipo || 'Institución'})</option>`).join('')}
                                <option value="__OTRA__">+ Otra institución (Escribir)...</option>
                            </select>
                            <input type="text" id="manualInstitucionOtra" name="manual_institucion_otra" class="sgp-input-modern" placeholder="Especifique el nombre de la institución" style="display:none; margin-top:6px;" autocomplete="off">
                        </div>
                        <div class="sgp-input-box" style="margin-bottom:0;">
                            <label class="sgp-label-modern"><i class="ti ti-building-community" style="color:var(--p-blue);"></i> Departamento Asignado</label>
                            <select name="manual_departamento" id="manualDepartamento" class="sgp-select-modern">
                                ${departamentos.map(d => `<option value="${d.nombre}">${d.nombre}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <!-- Tutor / Técnico Heredado del Sistema -->
                    <div class="sgp-input-box" style="margin-bottom:6px;">
                        <label class="sgp-label-modern">
                            <i class="ti ti-user-star" style="color:var(--p-blue);"></i> Tutor Asignado (Técnicos del Sistema)
                            <span style="font-size:0.68rem; color:#64748b; font-weight:500; margin-left:auto;">Hereda cédula y cargo oficial</span>
                        </label>
                        <select name="manual_tutor_id" id="selTutorManual" class="sgp-select-modern">
                            <option value="0">— Tutor de División / Soporte Técnico —</option>
                            ${tutores.map(t => `<option value="${t.id}">${t.nombre} — ${t.cargo} (C.I. ${t.cedula})</option>`).join('')}
                        </select>
                    </div>
                </div>`;
            mFechas.style.display = 'none';
            setTimeout(() => {
                actualizarPrevisualizacionPeriodo();
            }, 60);
            break;
        }

        case 'constancias_grupal': {
            const deptosOpts = <?= json_encode(array_map(fn($d) => ['id'=>$d->id,'nombre'=>$d->nombre], $data['departamentos'] ?? []), JSON_HEX_TAG) ?>;
            const instOpts   = <?= json_encode(array_map(fn($i) => ['id'=>$i->id,'nombre'=>$i->nombre], $data['instituciones'] ?? []), JSON_HEX_TAG) ?>;
            const periodosOpts = <?= json_encode(array_map(fn($p) => ['id'=>$p->id,'nombre'=>$p->nombre], $data['periodos'] ?? []), JSON_HEX_TAG) ?>;

            htmlFiltros = `
                <div class="r-form-group">
                    <label>Tipo de Acta</label>
                    <select name="tipo_constancia_grupal" class="r-form-control" id="cg-tipo">
                        <option value="servicio">Constancia de Servicio</option>
                        <option value="culminacion">Carta de Culminación</option>
                    </select>
                </div>
                <div class="r-form-group">
                    <label>Filtrar por</label>
                    <select class="r-form-control" id="cg-filtro-tipo" onchange="cgCambiarFiltro(this.value)">
                        <option value="todos">Todos los pasantes</option>
                        <option value="departamento">Por Departamento</option>
                        <option value="institucion">Por Institución</option>
                    </select>
                </div>
                <div class="r-form-group" id="cg-dept-box" style="display:none;">
                    <label>Departamento</label>
                    <select name="departamento_id" class="r-form-control" id="cg-dept">
                        <option value="">— Selecciona —</option>
                        ${deptosOpts.map(d => `<option value="${d.id}">${d.nombre}</option>`).join('')}
                    </select>
                </div>
                <div class="r-form-group" id="cg-inst-box" style="display:none;">
                    <label>Institución</label>
                    <select name="institucion_id" class="r-form-control" id="cg-inst">
                        <option value="">— Selecciona —</option>
                        ${instOpts.map(i => `<option value="${i.id}">${i.nombre}</option>`).join('')}
                    </select>
                </div>
                <div class="r-form-group">
                    <label>Estado de pasantía</label>
                    <select name="estado_pasantia" class="r-form-control">
                        <option value="Finalizado">Solo Finalizados</option>
                        <option value="Activo">Solo Activos</option>
                        <option value="todos">Todos (Activos + Finalizados)</option>
                    </select>
                </div>
                <div class="r-form-group">
                    <label>Período Académico</label>
                    <select name="periodo_id_grupal" class="r-form-control">
                        <option value="">— Todos los períodos —</option>
                        ${periodosOpts.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('')}
                    </select>
                </div>`;
            mFechas.style.display = 'none';

            // Ocultar buscador individual y botón Excel
            const boxBusc = document.getElementById('boxBuscadorPasante');
            if (boxBusc) boxBusc.style.display = 'none';
            const btnExcel = document.getElementById('mBtnExcel');
            if (btnExcel) btnExcel.style.display = 'none';
            break;
        }
    }

    document.getElementById('mFiltros').innerHTML = htmlFiltros;

    // Activar buscador AJAX para módulos que seleccionan pasante individual
    const modulosConBuscador = ['evaluaciones', 'constancias'];
    if (modulosConBuscador.includes(modulo)) {
        evaluarBusqueda(modulo === 'evaluaciones' ? 'evaluaciones_isp' : 'constancias');
        if (modulo === 'constancias') {
            cambiarModoConstancia('registrado');
        }
    } else {
        // Asegurar que el buscador quede limpio en otros módulos
        limpiarPasante();
        const box = document.getElementById('boxBuscadorPasante');
        if (box) box.style.display = 'none';
    }

    document.getElementById('modalConfig').classList.add('active');
    const mBody = document.querySelector('.sgp-modal-body');
    if (mBody) mBody.scrollTop = 0;

    inicializarPlugins();
}

function inicializarPlugins() {
    if (typeof flatpickr !== 'undefined') {
        const config = {
            dateFormat: "Y-m-d",
            locale: "es",
            allowInput: true,
            // [SGP-FIX-REPORTES-FECHA-MOBILE] Sin esto, en viewport táctil
            // (móvil / emulador) Flatpickr delega al <input type="date">
            // nativo del navegador, cuyo ancho mínimo no encaja en la
            // columna del modal y provocaba scroll horizontal. El resto
            // de los date-pickers del sistema (flatpickr-init.js) ya usan
            // disableMobile:true, así que esto además unifica el criterio.
            disableMobile: true
        };
        flatpickr("#fechaInicioInput", config);
        flatpickr("#fechaFinInput", config);
    }

    if (typeof jQuery !== 'undefined' && jQuery().select2) {
        $('.r-form-control').select2({
            dropdownParent: $('#modalConfig'),
            width: '100%'
        });
    }

    const inputPasante = document.getElementById('pasanteBusqueda');
    if (inputPasante) {
        let _timerBusqueda = null;

        inputPasante.addEventListener('input', function () {
            const q = this.value.trim();
            const resultados = document.getElementById('pasanteResultados');

            // Limpiar selección previa si el usuario borra el texto
            if (q.length === 0) {
                limpiarPasante();
                resultados.style.display = 'none';
                return;
            }

            // Esperar mínimo 2 caracteres
            if (q.length < 2) return;

            // Debounce: esperar 300ms sin escribir antes de disparar
            clearTimeout(_timerBusqueda);
            _timerBusqueda = setTimeout(() => buscarPasante(q), 300);
        });

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function (e) {
            const box = document.getElementById('boxBuscadorPasante');
            if (box && !box.contains(e.target)) {
                const res = document.getElementById('pasanteResultados');
                if (res) res.style.display = 'none';
            }
        });
    }

    // ── Pill radios del período ejecutivo ────────────────────────────────
    const radiosEj = document.querySelectorAll('input[name="tipo_periodo"]');
    if (radiosEj.length) {
        // Inicializar Flatpickr en los inputs de rango personalizado
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#ejFechaInicio', { dateFormat: 'Y-m-d', locale: 'es', allowInput: false, disableMobile: true });
            flatpickr('#ejFechaFin',    { dateFormat: 'Y-m-d', locale: 'es', allowInput: false, disableMobile: true });
        }

        radiosEj.forEach(function(r) {
            r.addEventListener('change', function() {
                const rangoBox = document.getElementById('ejRangoFechas');
                const esRango  = this.value === 'rango';
                rangoBox.style.display = esRango ? 'flex' : 'none';

                const ejFI = document.getElementById('ejFechaInicio');
                const ejFF = document.getElementById('ejFechaFin');
                if (!esRango) {
                    // Limpiar fechas al cambiar de opción
                    if (ejFI && ejFI._flatpickr) ejFI._flatpickr.clear();
                    if (ejFF && ejFF._flatpickr) ejFF._flatpickr.clear();
                }
                if (ejFI) ejFI.required = esRango;
                if (ejFF) ejFF.required = esRango;
            });
        });
    }
}

function evaluarBusqueda(tipo) {
    const box = document.getElementById('boxBuscadorPasante');
    const necesitaPasante = ['planilla_isp', 'total', 'evaluaciones_isp', 'constancias'];
    if (box) {
        if (necesitaPasante.includes(tipo)) {
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
            limpiarPasante();
        }
    }
}

function cambiarModoConstancia(modo) {
    const btnReg = document.getElementById('btnModoRegistrado');
    const btnMan = document.getElementById('btnModoManual');
    const boxReg = document.getElementById('boxConstRegistrado');
    const boxMan = document.getElementById('boxConstManual');
    const boxBusc = document.getElementById('boxBuscadorPasante');
    const hiddenModo = document.getElementById('modoEmisionConstancia');

    if (hiddenModo) hiddenModo.value = modo;

    if (modo === 'registrado') {
        if (btnReg) {
            btnReg.style.borderColor = '#1D4ED8';
            btnReg.style.background = 'rgba(29,78,216,0.06)';
            const ico = btnReg.querySelector('.ti');
            if (ico) ico.style.color = '#1D4ED8';
            const tit = btnReg.querySelector('div > div:first-child');
            if (tit) tit.style.color = '#1D4ED8';
        }
        if (btnMan) {
            btnMan.style.borderColor = '#e2e8f0';
            btnMan.style.background = '#f8fafc';
            const ico = btnMan.querySelector('.ti');
            if (ico) ico.style.color = '#64748b';
            const tit = btnMan.querySelector('div > div:first-child');
            if (tit) tit.style.color = '#475569';
        }
        if (boxReg) boxReg.style.display = 'block';
        if (boxMan) boxMan.style.display = 'none';
        if (boxBusc) boxBusc.style.display = 'block';
    } else {
        if (btnMan) {
            btnMan.style.borderColor = '#1D4ED8';
            btnMan.style.background = 'rgba(29,78,216,0.06)';
            const ico = btnMan.querySelector('.ti');
            if (ico) ico.style.color = '#1D4ED8';
            const tit = btnMan.querySelector('div > div:first-child');
            if (tit) tit.style.color = '#1D4ED8';
        }
        if (btnReg) {
            btnReg.style.borderColor = '#e2e8f0';
            btnReg.style.background = '#f8fafc';
            const ico = btnReg.querySelector('.ti');
            if (ico) ico.style.color = '#64748b';
            const tit = btnReg.querySelector('div > div:first-child');
            if (tit) tit.style.color = '#475569';
        }
        if (boxReg) boxReg.style.display = 'none';
        if (boxMan) boxMan.style.display = 'block';
        if (boxBusc) boxBusc.style.display = 'none';

    }
}

function seleccionarDuracionMeses(meses, btn) {
    const input = document.getElementById('manualDuracionMeses');
    if (input) input.value = meses;

    const lbl = document.getElementById('labelHorasCalculadas');
    const horasMap = { 3: '480 Horas', 6: '960 Horas (Estándar)', 9: '1440 Horas (Completo)' };
    if (lbl) lbl.textContent = horasMap[meses] || (meses * 160 + ' Horas');

    document.querySelectorAll('.btn-duracion-mes').forEach(b => {
        b.style.borderColor = '#e2e8f0';
        b.style.background = '#f8fafc';
        b.style.color = '#475569';
        b.style.fontWeight = '700';
        const ico = b.querySelector('.ti');
        if (ico) ico.style.color = '#64748b';
        const sub = b.querySelector('div');
        if (sub) { sub.style.color = '#94a3b8'; sub.style.fontWeight = '500'; }
    });

    if (btn) {
        btn.style.borderColor = '#1D4ED8';
        btn.style.background = 'rgba(29,78,216,0.06)';
        btn.style.color = '#1D4ED8';
        btn.style.fontWeight = '800';
        const ico = btn.querySelector('.ti');
        if (ico) ico.style.color = '#1D4ED8';
        const sub = btn.querySelector('div');
        if (sub) { sub.style.color = '#2563eb'; sub.style.fontWeight = '700'; }
    }

    actualizarPrevisualizacionPeriodo();
}

function actualizarPrevisualizacionPeriodo() {
    const inputMeses = document.getElementById('manualDuracionMeses');
    const selInicio  = document.getElementById('manualAnioInicio');
    const selFin     = document.getElementById('manualAnioFin');
    const badge      = document.getElementById('badgePeriodoVisual');
    if (!badge || !selInicio || !selFin) return;

    const meses = inputMeses ? (parseInt(inputMeses.value) || 6) : 6;
    const aIni  = parseInt(selInicio.value) || new Date().getFullYear();
    const aFin  = parseInt(selFin.value) || aIni;

    let texto = '';
    if (aIni !== aFin) {
        texto = `${meses} Meses (Período ${aIni} – ${aFin})`;
    } else {
        texto = `${meses} Meses (Año ${aIni})`;
    }
    badge.textContent = texto;
}

function cambiarInstitucionManual(val) {
    const inputOtra = document.getElementById('manualInstitucionOtra');
    if (!inputOtra) return;
    if (val === '__OTRA__') {
        inputOtra.style.display = 'block';
        inputOtra.focus();
    } else {
        inputOtra.style.display = 'none';
        inputOtra.value = '';
    }
}

// ══════════════════════════════════════════════════════════════
// HISTORIAL Y LIBRO DE REGISTRO DE CONSTANCIAS RESPALDADAS
// ══════════════════════════════════════════════════════════════
window._historialConstanciasData = [];

function abrirModalHistorialConstancias() {
    const modal = document.getElementById('modalHistorialConstancias');
    if (!modal) return;
    modal.classList.add('active');
    cargarHistorialConstancias(true);
}

function cerrarModalHistorialConstancias() {
    const modal = document.getElementById('modalHistorialConstancias');
    if (modal) modal.classList.remove('active');
}

function cargarHistorialConstancias(mostrarSpinner = false) {
    const contenedor = document.getElementById('contenedorTablaHistorial');
    if (mostrarSpinner && contenedor) {
        contenedor.innerHTML = `
            <div style="text-align:center; padding:50px 20px; color:#64748b;">
                <i class="ti ti-loader-2" style="font-size:2rem; animation:spin 1s linear infinite; display:inline-block; margin-bottom:10px; color:#2563eb;"></i>
                <p style="font-size:0.9rem; margin:0; font-weight:700; color:#1e293b;">Cargando libro de constancias emitidas...</p>
                <span style="font-size:0.75rem; color:#94a3b8;">Recuperando registros oficiales de la base de datos</span>
            </div>`;
    }

    fetch(URLROOT + '/reportes/historialConstancias')
        .then(r => r.json())
        .then(res => {
            if (res.success && Array.isArray(res.data)) {
                window._historialConstanciasData = res.data;
                const badge = document.getElementById('badgeTotalHistorial');
                if (badge) badge.textContent = `${res.data.length} guardadas`;
                filtrarHistorialConstanciasLocal();
            } else {
                if (contenedor) {
                    contenedor.innerHTML = `
                        <div style="text-align:center; padding:40px 20px; color:#dc2626;">
                            <i class="ti ti-alert-triangle" style="font-size:2rem; margin-bottom:8px;"></i>
                            <p style="font-weight:700;">No se pudo cargar el historial.</p>
                        </div>`;
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (contenedor) {
                contenedor.innerHTML = `
                    <div style="text-align:center; padding:40px 20px; color:#dc2626;">
                        <i class="ti ti-alert-circle" style="font-size:2rem; margin-bottom:8px;"></i>
                        <p style="font-weight:700;">Error de comunicación al consultar los respaldos.</p>
                    </div>`;
            }
        });
}

function filtrarHistorialConstanciasLocal() {
    const texto = (document.getElementById('filtroHistorialTexto')?.value || '').toLowerCase().trim();
    const tipoFiltro = (document.getElementById('filtroHistorialTipo')?.value || '').toLowerCase().trim();

    let filtrados = window._historialConstanciasData || [];

    if (tipoFiltro) {
        filtrados = filtrados.filter(item => (item.tipo_constancia || '').toLowerCase() === tipoFiltro);
    }

    if (texto) {
        filtrados = filtrados.filter(item => {
            const nom = (item.nombre_completo || '').toLowerCase();
            const ced = (item.cedula || '').toLowerCase();
            const cod = (item.codigo_constancia || '').toLowerCase();
            const inst = (item.institucion || '').toLowerCase();
            return nom.includes(texto) || ced.includes(texto) || cod.includes(texto) || inst.includes(texto);
        });
    }

    renderizarHistorialConstancias(filtrados);
}

function renderizarHistorialConstancias(lista) {
    const contenedor = document.getElementById('contenedorTablaHistorial');
    if (!contenedor) return;

    if (!lista || lista.length === 0) {
        contenedor.innerHTML = `
            <div style="text-align:center; padding:50px 20px; background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:18px; margin:10px 0;">
                <div style="width:60px; height:60px; border-radius:16px; background:#eff6ff; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; color:#2563eb;">
                    <i class="ti ti-folder-off" style="font-size:2rem;"></i>
                </div>
                <h4 style="font-size:1.05rem; font-weight:800; color:#1e293b; margin:0 0 6px;">No hay constancias registradas</h4>
                <p style="font-size:0.83rem; color:#64748b; max-width:440px; margin:0 auto 14px; line-height:1.5;">
                    Cada vez que generes una Carta de Culminación o Constancia de Servicio (sea de un pasante actual o histórico), se respaldará automáticamente en este libro.
                </p>
                <button type="button" class="btn-p-soft btn-soft-pdf" onclick="cerrarModalHistorialConstancias(); abrirModal('constancias', 'pdf');" style="display:inline-flex; width:auto; padding:10px 22px;">
                    <i class="ti ti-plus"></i> Emitir Primera Constancia
                </button>
            </div>`;
        return;
    }

    let html = `
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:separate; border-spacing:0 8px; font-size:0.84rem;">
                <thead>
                    <tr style="color:#64748b; font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:0.6px; text-align:left;">
                        <th style="padding:8px 12px;">N° Control</th>
                        <th style="padding:8px 12px;">Fecha Emisión</th>
                        <th style="padding:8px 12px;">Pasante</th>
                        <th style="padding:8px 12px;">Tipo / Modo</th>
                        <th style="padding:8px 12px;">Período / Carga</th>
                        <th style="padding:8px 12px;">Emitido Por</th>
                        <th style="padding:8px 12px; text-align:center;">Acción</th>
                    </tr>
                </thead>
                <tbody>`;

    lista.forEach(r => {
        const esCulm = (r.tipo_constancia === 'culminacion');
        const badgeTipo = esCulm
            ? `<span style="background:rgba(5,150,105,0.08); color:#059669; font-weight:800; font-size:0.72rem; padding:3px 8px; border-radius:6px; border:1px solid rgba(5,150,105,0.2); display:inline-flex; align-items:center; gap:4px;"><i class="ti ti-certificate"></i> Culminación</span>`
            : `<span style="background:rgba(29,78,216,0.08); color:#1d4ed8; font-weight:800; font-size:0.72rem; padding:3px 8px; border-radius:6px; border:1px solid rgba(29,78,216,0.2); display:inline-flex; align-items:center; gap:4px;"><i class="ti ti-file-text"></i> Servicio</span>`;

        const badgeModo = (r.modo_emision === 'manual')
            ? `<span style="background:#f1f5f9; color:#475569; font-size:0.68rem; font-weight:700; padding:2px 6px; border-radius:4px; margin-left:4px;">Histórico</span>`
            : `<span style="background:#eff6ff; color:#2563eb; font-size:0.68rem; font-weight:700; padding:2px 6px; border-radius:4px; margin-left:4px;">Sistema</span>`;

        const f = new Date(r.created_at.replace(' ', 'T'));
        const fStr = isNaN(f) ? r.created_at : f.toLocaleDateString('es-VE', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });

        const urlVer = URLROOT + '/reportes/reimprimirConstancia?codigo=' + encodeURIComponent(r.codigo_constancia);

        html += `
            <tr style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.02); transition:all 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                <td style="padding:12px; font-weight:800; color:#1e3a8a; border-radius:12px 0 0 12px; border-left:3px solid #2563eb;">
                    <div style="font-family:monospace; font-size:0.83rem;">${_esc(r.codigo_constancia)}</div>
                </td>
                <td style="padding:12px; color:#475569; font-size:0.78rem;">
                    <i class="ti ti-calendar" style="color:#94a3b8;"></i> ${fStr}
                </td>
                <td style="padding:12px;">
                    <div style="font-weight:800; color:#0f172a;">${_esc(r.nombre_completo)}</div>
                    <div style="font-size:0.72rem; color:#64748b;">C.I. ${_esc(r.cedula)} · <span style="color:#94a3b8;">${_esc(r.institucion || 'N/D')}</span></div>
                </td>
                <td style="padding:12px;">
                    ${badgeTipo} ${badgeModo}
                </td>
                <td style="padding:12px;">
                    <div style="font-weight:700; color:#1e293b; font-size:0.78rem;">${_esc(r.periodo_texto || (r.duracion_meses + ' Meses'))}</div>
                    <div style="font-size:0.70rem; color:#64748b;">${r.horas_meta || 960} Horas</div>
                </td>
                <td style="padding:12px; color:#64748b; font-size:0.75rem;">
                    <i class="ti ti-user-check" style="color:#10b981;"></i> ${_esc(r.emitido_por_nombre || 'Admin')}
                </td>
                <td style="padding:10px 12px; text-align:center; border-radius:0 12px 12px 0; white-space:nowrap;">
                    <div style="display:inline-flex; gap:5px; justify-content:center; align-items:center;">
                        <a href="${urlVer}" target="_blank" class="btn-p-soft" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; border-radius:8px; text-decoration:none; transition:all 0.15s ease;" title="Ver documento en visor PDF" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 3px 8px rgba(21,128,61,0.2)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">
                            <i class="ti ti-eye" style="font-size:1.05rem;"></i>
                        </a>
                        <a href="${urlVer}&download=1" target="_blank" class="btn-p-soft" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:8px; text-decoration:none; transition:all 0.15s ease;" title="Descargar archivo PDF directamente" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 3px 8px rgba(29,78,216,0.2)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">
                            <i class="ti ti-download" style="font-size:1.05rem;"></i>
                        </a>
                        <button type="button" onclick="abrirEditorConstancia(${r.id})" class="btn-p-soft" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:#fffbeb; color:#b45309; border:1px solid #fde68a; border-radius:8px; cursor:pointer; transition:all 0.15s ease;" title="Editar texto y datos del documento" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 3px 8px rgba(180,83,9,0.2)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">
                            <i class="ti ti-edit" style="font-size:1.05rem;"></i>
                        </button>
                        <button type="button" onclick="eliminarConstancia(${r.id}, '${_esc(r.codigo_constancia)}', '${_esc(r.nombre_completo)}')" class="btn-p-soft" style="width:32px; height:32px; padding:0; display:inline-flex; align-items:center; justify-content:center; background:#fef2f2; color:#dc2626; border:1px solid #fecaca; border-radius:8px; cursor:pointer; transition:all 0.15s ease;" title="Eliminar constancia respaldada" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 3px 8px rgba(220,38,38,0.2)';" onmouseout="this.style.transform='none';this.style.boxShadow='none';">
                            <i class="ti ti-trash" style="font-size:1.05rem;"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    });

    html += `</tbody></table></div>`;
    contenedor.innerHTML = html;
}

// ══════════════════════════════════════════════════════════════
// EDICIÓN EN VIVO DE CONSTANCIAS RESPALDADAS
// ══════════════════════════════════════════════════════════════
function abrirEditorConstancia(id) {
    const item = (window._historialConstanciasData || []).find(x => parseInt(x.id) === parseInt(id));
    if (!item) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se encontraron los datos de la constancia.' });
        return;
    }

    document.getElementById('editConstanciaId').value = item.id;
    document.getElementById('editTipoConstancia').value = item.tipo_constancia;
    document.getElementById('editBadgeCodigo').textContent = item.codigo_constancia;

    document.getElementById('editNombreCompleto').value = item.nombre_completo || '';
    document.getElementById('editCedula').value = item.cedula || '';
    document.getElementById('editPeriodoTexto').value = item.periodo_texto || '';
    document.getElementById('editInstitucion').value = item.institucion || '';
    document.getElementById('editDepartamento').value = item.departamento || '';
    document.getElementById('editHorasMeta').value = item.horas_meta || 960;
    document.getElementById('editTutorNombre').value = item.tutor_nombre || 'Joel Acosta';
    document.getElementById('editTutorCargo').value = item.tutor_cargo || 'Jefe de la División de Soporte Técnico de la Dirección de Telemática';

    const areaTexto = document.getElementById('editCuerpoPersonalizado');
    if (item.cuerpo_personalizado && item.cuerpo_personalizado.trim().length > 0) {
        areaTexto.value = item.cuerpo_personalizado;
    } else {
        areaTexto.value = construirRedaccionBase(item);
    }

    const modal = document.getElementById('modalEditarConstancia');
    if (modal) modal.classList.add('active');
}

function cerrarEditorConstancia() {
    const modal = document.getElementById('modalEditarConstancia');
    if (modal) modal.classList.remove('active');
}

function regenerarTextoPredeterminado() {
    const datos = {
        tipo_constancia: document.getElementById('editTipoConstancia').value,
        nombre_completo: document.getElementById('editNombreCompleto').value,
        cedula: document.getElementById('editCedula').value,
        departamento: document.getElementById('editDepartamento').value,
        periodo_texto: document.getElementById('editPeriodoTexto').value,
        horas_meta: document.getElementById('editHorasMeta').value
    };
    document.getElementById('editCuerpoPersonalizado').value = construirRedaccionBase(datos);
}

function construirRedaccionBase(data) {
    const esCulm = (data.tipo_constancia === 'culminacion');
    const nom = (data.nombre_completo || 'NOMBRE DEL PASANTE').toUpperCase();
    const ciRaw = data.cedula || '';
    const ci = (ciRaw.startsWith('V-') || ciRaw.startsWith('E-')) ? ciRaw : ('V-' + ciRaw);
    const depto = data.departamento || 'Soporte Técnico';
    const horas = data.horas_meta || 960;
    const periodo = data.periodo_texto ? `durante un período de ${data.periodo_texto}, ` : '';

    const hoy = new Date();
    const mesesEs = ['', 'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    const dia = hoy.getDate();
    const mes = mesesEs[hoy.getMonth() + 1];
    const anio = hoy.getFullYear();

    if (esCulm) {
        return `Quien suscribe, en su carácter de responsable del ${depto}, adscrito a la División de Soporte Técnico de la Dirección de Telemática del Instituto de Salud Pública del Estado Bolívar, certifica por medio de la presente que el/la ciudadano/a ${nom}, titular de la Cédula de Identidad Nro. ${ci}, ha culminado satisfactoriamente sus Pasantías Profesionales en la institución bajo la supervisión del área de ${depto}, ${periodo}completando una carga horaria de ${horas} horas cumpliendo con las ${horas} horas requeridas.\n\nSe expide la presente carta a petición de la parte interesada, a los ${dia} días del mes de ${mes} de ${anio}, en Ciudad Bolívar, Estado Bolívar.`;
    } else {
        return `Quien suscribe, en su carácter de responsable del ${depto}, adscrito a la División de Soporte Técnico de la Dirección de Telemática del Instituto de Salud Pública del Estado Bolívar, hace constar por medio de la presente que el/la ciudadano/a ${nom}, titular de la Cédula de Identidad Nro. ${ci}, se encuentra realizando pasantías en la institución bajo la supervisión del área de ${depto}, cumpliendo una carga horaria de ${horas} horas correspondiente a un período de ${data.periodo_texto || '6 Meses'}.\n\nLa presente constancia se expide a petición de la parte interesada, a los ${dia} días del mes de ${mes} de ${anio}, en Ciudad Bolívar, Estado Bolívar.`;
    }
}

function guardarEdicionConstancia(event, callback) {
    if (event) event.preventDefault();

    const form = document.getElementById('formEditarConstancia');
    const fd = new FormData(form);
    const btnGuardar = document.getElementById('btnGuardarCambios');
    const htmlOrig = btnGuardar ? btnGuardar.innerHTML : '';
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite;"></i> Guardando...';
    }

    fetch(URLROOT + '/reportes/actualizarConstancia', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = htmlOrig;
        }

        if (res.success) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Constancia Actualizada',
                    text: 'Los cambios y la redacción personalizada fueron guardados correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            cerrarEditorConstancia();
            cargarHistorialConstancias(false);
            if (typeof callback === 'function') callback();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.error || 'No se pudieron guardar los cambios.' });
        }
    })
    .catch(err => {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = htmlOrig;
        }
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al guardar los cambios.' });
    });
}

function guardarYDescargarPdf() {
    const id = document.getElementById('editConstanciaId').value;
    guardarEdicionConstancia(null, function() {
        window.open(URLROOT + '/reportes/reimprimirConstancia?id=' + encodeURIComponent(id) + '&download=1', '_blank');
    });
}

function eliminarConstancia(id, codigo, pasante) {
    Swal.fire({
        title: '¿Eliminar constancia?',
        html: `¿Estás seguro de eliminar el registro <strong>${codigo}</strong> de <strong>${pasante}</strong>?<br><small style="color:#64748b;">Esta acción no se puede deshacer.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('id', id);

            fetch(URLROOT + '/reportes/eliminarConstancia', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminada',
                        text: 'El registro de constancia ha sido eliminado del libro oficial.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                    cargarHistorialConstancias(false);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.error || 'No se pudo eliminar.' });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error al procesar la eliminación.' });
            });
        }
    });
}



function activarModoAsistencia(modo) {
    const btnGrupal     = document.getElementById('modoGrupal');
    const btnIndividual = document.getElementById('modoIndividual');
    const contenido     = document.getElementById('contenidoModoAsistencia');
    const buscador      = document.getElementById('boxBuscadorPasante');
    const trimestre     = document.getElementById('boxSelectorTrimestre');
    const fechas        = document.getElementById('mRangoFechas');
    const btnExcel      = document.getElementById('mBtnExcel');

    // Reset estilos ambos botones
    [btnGrupal, btnIndividual].forEach(b => {
        if (!b) return;
        b.style.borderColor = '#f1f5f9';
        b.style.background  = '#f8fafc';
        const titulo = b.querySelector('div:nth-child(2)');
        if (titulo) titulo.style.color = '#3A4768';
    });

    if (modo === 'grupal') {
        if (btnGrupal) {
            btnGrupal.style.borderColor = '#1D4ED8';
            btnGrupal.style.background  = 'rgba(29,78,216,.06)';
            const t = btnGrupal.querySelector('div:nth-child(2)');
            if (t) t.style.color = '#1D4ED8';
        }
        buscador.style.display  = 'none';
        trimestre.style.display = 'none';
        btnExcel.style.display  = 'flex';
        limpiarBentoPasante();

        contenido.innerHTML = `
            <div class="r-form-group" style="margin-bottom:10px;">
                <label>Tipo de Reporte</label>
                <div style="display:flex; flex-direction:column; gap:6px;"
                     id="tiposPillsGrupal">
                    ${[
                        {v:'diario',  l:'Diario',
                         s:'Asistencia de un día específico'},
                        {v:'semanal', l:'Semanal',
                         s:'Asistencia de una semana'},
                        {v:'mensual', l:'Mensual',
                         s:'Asistencia de un mes completo'},
                        {v:'total',   l:'Total Consolidado',
                         s:'Todo el historial disponible'}
                    ].map((t, i) => `
                        <label onclick="seleccionarTipoGrupal('${t.v}', this)"
                               style="display:flex; align-items:center; gap:10px;
                                      border-radius:50px; border:1.5px solid
                                      ${i===0?'#162660':'#dde2f0'};
                                      background:${i===0?'rgba(22,38,96,.05)':'#f8fafc'};
                                      padding:9px 14px; cursor:pointer;
                                      transition:all .15s;">
                            <input type="radio" name="tipo_reporte"
                                   value="${t.v}" ${i===0?'checked':''}
                                   style="display:none;">
                            <div style="width:14px; height:14px;
                                        border-radius:50%;
                                        border:2px solid ${i===0?'#162660':'#dde2f0'};
                                        background:${i===0?'#162660':'transparent'};
                                        flex-shrink:0; display:flex;
                                        align-items:center;
                                        justify-content:center;">
                                ${i===0?'<div style="width:5px;height:5px;border-radius:50%;background:#fff;"></div>':''}
                            </div>
                            <div>
                                <div style="font-size:12px; font-weight:700;
                                            color:${i===0?'#162660':'#0D1424'};">
                                    ${t.l}</div>
                                <div style="font-size:10px; color:#7480A0;">
                                    ${t.s}</div>
                            </div>
                        </label>
                    `).join('')}
                </div>
            </div>
            <div class="r-form-group">
                <label>Departamento</label>
                <select name="departamento" class="r-form-control">
                    <option value="todos">Todos los departamentos</option>
                    ${typeof departamentos !== 'undefined'
                        ? departamentos.map(d =>
                            `<option value="${d.id}">${d.nombre}</option>`
                          ).join('')
                        : ''}
                </select>
            </div>
            <div id="fechaGrupalContainer"></div>
        `;
        actualizarFechaGrupal('diario');

    } else {
        // MODO INDIVIDUAL
        if (btnIndividual) {
            btnIndividual.style.borderColor = '#059669';
            btnIndividual.style.background  = 'rgba(5,150,105,.06)';
            const t = btnIndividual.querySelector('div:nth-child(2)');
            if (t) t.style.color = '#059669';
        }
        fechas.style.display    = 'none';
        trimestre.style.display = 'none';
        btnExcel.style.display  = 'none';

        contenido.innerHTML = `
            <input type="hidden" name="tipo_reporte" value="planilla_isp">
            <input type="hidden" name="formato_trimestral" value="1">
        `;

        // Mostrar buscador o bento según estado actual
        const idActual = document.getElementById('pasanteIdHidden').value;
        if (idActual) {
            buscador.style.display  = 'none';
            trimestre.style.display = 'block';
        } else {
            buscador.style.display  = 'block';
        }
    }
}

function seleccionarTipoGrupal(tipo, labelEl) {
    document.querySelectorAll('#tiposPillsGrupal label').forEach(l => {
        l.style.borderColor = '#dde2f0';
        l.style.background  = '#f8fafc';
        const dot = l.querySelector('div');
        if (dot) { dot.style.borderColor = '#dde2f0'; dot.style.background = 'transparent'; dot.innerHTML = ''; }
        const txt = l.querySelector('div:last-child div:first-child');
        if (txt) txt.style.color = '#0D1424';
    });

    labelEl.style.borderColor = '#162660';
    labelEl.style.background  = 'rgba(22,38,96,.04)';
    const dot = labelEl.querySelector('div');
    if (dot) {
        dot.style.borderColor = '#162660';
        dot.style.background  = '#162660';
        dot.innerHTML = '<div style="width:5px;height:5px;border-radius:50%;background:#fff;"></div>';
    }
    const txt = labelEl.querySelector('div:last-child div:first-child');
    if (txt) txt.style.color = '#162660';

    labelEl.querySelector('input[type=radio]').checked = true;
    actualizarFechaGrupal(tipo);
}

function actualizarFechaGrupal(tipo) {
    const cont   = document.getElementById('fechaGrupalContainer');
    const fechas = document.getElementById('mRangoFechas');
    const finBox = document.getElementById('boxFechaFin');

    if (tipo === 'total') {
        fechas.style.display = 'none';
        if (cont) cont.innerHTML = `
            <div style="background:#f8fafc; border-radius:14px;
                        padding:9px 14px; font-size:11px;
                        color:#7480A0; text-align:center;
                        border:1.5px solid #f1f5f9;">
                Sin filtro de fecha — incluye todo el historial disponible
            </div>`;
    } else if (tipo === 'diario') {
        fechas.style.display = 'flex';
        if (finBox) finBox.style.display = 'none';
        if (cont)   cont.innerHTML = '';
    } else {
        fechas.style.display = 'flex';
        if (finBox) finBox.style.display = 'block';
        if (cont)   cont.innerHTML = '';
    }
}

function seleccionarTrimestre(n) {
    const btn = document.querySelector(`.trim-btn-sgp[data-trim="${n}"]`);
    if (!btn || btn.classList.contains('bloqueado')) return;

    document.querySelectorAll('.trim-btn-sgp').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
    document.getElementById('trimestreHidden').value = n;
}

// [FIX-XS-3] Escape de HTML para datos de usuario insertados vía innerHTML/atributos.
function _esc(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

// ── Buscar pasante via AJAX ──────────────────────────────
function buscarPasante(q) {
    const resultados = document.getElementById('pasanteResultados');
    resultados.innerHTML =
        '<div style="padding:10px; text-align:center; ' +
        'font-size:12px; color:#888;">Buscando...</div>';
    resultados.style.display = 'block';

    fetch(URLROOT + '/asistencias/buscar_pasantes?q=' + encodeURIComponent(q), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success || !res.data || res.data.length === 0) {
            resultados.innerHTML =
                '<div style="padding:10px; text-align:center; ' +
                'font-size:12px; color:#888;">Sin resultados</div>';
            return;
        }
        resultados.innerHTML = res.data.map(p => {
            const ini = ((p.nombres?.[0] ?? '') +
                         (p.apellidos?.[0] ?? '')).toUpperCase();
            
            // [FIX-XS-3] Escapado en origen: cubre tanto el texto visible como los
            // atributos data-* de abajo (antes rompían el atributo con comillas/HTML).
            const nombreCompleto = _esc((p.nombres + ' ' + p.apellidos).trim());
            // Institución: el endpoint /asistencias/buscar_pasantes devuelve institucion_nombre
            const instNombre = _esc((p.institucion_nombre || p.institucion_procedencia || '').trim());

            return '<div class="pasante-opcion" ' +
                'data-id="'          + p.id + '" ' +
                'data-nombre="'      + nombreCompleto + '" ' +
                'data-cedula="'      + _esc(p.cedula ?? '') + '" ' +
                'data-institucion="' + instNombre + '" ' +
                'style="display:flex; align-items:center; gap:12px; padding:12px 16px; cursor:pointer; background:#fff; border-bottom:1px solid #f1f5f9; transition:all 0.2s; border-radius:8px; margin-bottom:4px;" ' +
                'onmouseover="this.style.background=\'#f8fafc\'; this.style.transform=\'translateY(-1px)\'" ' +
                'onmouseout="this.style.background=\'#fff\'; this.style.transform=\'none\'">' +
                
                // Avatar verde institucional
                '<div style="width:38px; height:38px; border-radius:10px; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.9rem; flex-shrink:0;">' +
                    ini + 
                '</div>' +
                
                // Info (Nombre en minúscula y metadata abajo)
                '<div style="flex:1; min-width:0;">' +
                    '<div style="font-weight:700; color:#0f172a; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; text-transform:lowercase; line-height:1.2;">' +
                        nombreCompleto + 
                    '</div>' +
                    '<div style="font-size:0.72rem; color:#64748b; margin-top:2px; display:flex; align-items:center; gap:5px;">' +
                        '<i class="ti ti-id"></i> ' + _esc(p.cedula || '\u2014') +
                        ' <span style="opacity:0.4;">\u2022</span> ' +
                        '<i class="ti ti-building"></i> ' + _esc(p.departamento_nombre || 'No asignado') +
                    '</div>' +
                    (instNombre ? '<div style="font-size:0.72rem; color:#3b82f6; margin-top:2px; display:flex; align-items:center; gap:4px;"><i class="ti ti-school"></i> ' + instNombre + '</div>' : '') +
                '</div>' +
                
                // Badge de pasante a la derecha
                '<div style="margin-left:auto; display:flex; align-items:center; gap:6px; flex-shrink:0;">' +
                    '<span style="background:#d1fae5; color:#059669; font-size:0.65rem; padding:4px 8px; border-radius:6px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">Pasante</span>' +
                '</div>' +
                
                '</div>';
        }).join('');

        // Evento click en cada opción
        resultados.querySelectorAll('.pasante-opcion').forEach(el => {
            el.addEventListener('click', function () {
                seleccionarPasante(
                    this.dataset.id,
                    this.dataset.nombre,
                    this.dataset.cedula,
                    this.dataset.institucion
                );
            });
        });
    })
    .catch(() => {
        resultados.innerHTML =
            '<div style="padding:10px; text-align:center; ' +
            'font-size:12px; color:#E24B4A;">Error al buscar</div>';
    });
}

// ── Confirmar selección de pasante ───────────────────────
function seleccionarPasante(id, nombre, cedula, institucion) {
    // Guardar ID
    document.getElementById('pasanteIdHidden').value = id;

    // Ocultar input, mostrar bento
    document.getElementById('contenedorInputPasante').style.display = 'none';
    document.getElementById('pasanteResultados').style.display      = 'none';

    // Poblar bento
    const partes = nombre.split(' ');
    const ini    = ((partes[0]?.[0] ?? '') +
                    (partes[1]?.[0] ?? '')).toUpperCase();

    document.getElementById('bentoAvatarReportes').textContent  = ini;
    document.getElementById('bentoNombreReportes').textContent  = nombre;
    document.getElementById('bentoCedulaReportes').textContent  = cedula ?? '—';
    document.getElementById('bentoInstReportes').textContent    = institucion || 'No especificada';
    document.getElementById('bentoPasanteReportes').style.display       = 'flex';
    document.getElementById('bentoPasanteReportes').style.flexDirection = 'column';

    // ── Selector de trimestre SOLO para asistencias-individual ──
    // Evaluaciones y constancias NO usan trimestres — tienen su propio flujo.
    const moduloActual = document.getElementById('mModulo').value;
    const boxTrim = document.getElementById('boxSelectorTrimestre');
    const btnTodos = document.getElementById('mBtnTodosTrimestres');
    if (boxTrim) {
        if (moduloActual === 'asistencias') {
            boxTrim.style.display = 'block';
            if (btnTodos) btnTodos.style.display = 'flex';
            seleccionarTrimestre(1);
            verificarTrimestresDisponibles(id);
        } else {
            // Evaluaciones / Constancias: asegurar que el selector quede oculto
            boxTrim.style.display = 'none';
            if (btnTodos) btnTodos.style.display = 'none';
        }
    }
}

// ── Limpiar bento pasante ────────────────────────────────
function limpiarBentoPasante() {
    document.getElementById('pasanteIdHidden').value = '';
    document.getElementById('pasanteBusqueda').value = '';
    document.getElementById('pasanteResultados').style.display = 'none';

    // Ocultar bento, mostrar input
    document.getElementById('bentoPasanteReportes').style.display   = 'none';
    document.getElementById('contenedorInputPasante').style.display = 'block';

    // Ocultar trimestres y limpiar alerta
    const boxTrim = document.getElementById('boxSelectorTrimestre');
    if (boxTrim) boxTrim.style.display = 'none';
    const btnTodosLimpiar = document.getElementById('mBtnTodosTrimestres');
    if (btnTodosLimpiar) btnTodosLimpiar.style.display = 'none';

    const alerta = document.getElementById('alertaTrimestre');
    if (alerta) alerta.style.display = 'none';

    document.querySelectorAll('.trim-btn-sgp').forEach(b => {
        b.classList.remove('activo', 'bloqueado');
    });

    // Focus al input para nueva búsqueda
    const inp = document.getElementById('pasanteBusqueda');
    if (inp) inp.focus();
}

// Alias para compatibilidad con llamadas existentes
function limpiarPasante() { limpiarBentoPasante(); }

function generarTodosTrimestres() {
    document.getElementById('trimestreHidden').value = '0';
    submitConFormato('pdf', document.getElementById('mBtnTodosTrimestres'));
}

function verificarTrimestresDisponibles(pasanteId) {
    const fd = new FormData();
    fd.append('modulo',      'asistencias');
    fd.append('pasante_id',  pasanteId);
    fd.append('tipo_reporte','planilla_isp');
    fd.append('csrf_token',
        document.querySelector('input[name=csrf_token]').value);

    fetch(URLROOT + '/reportes/validarDatos', {
        method: 'POST', body: fd,
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(r => r.json())
    .then(res => {
        if (!res.trimestres_disponibles) return;

        const disponibles = res.trimestres_disponibles;
        const alerta      = document.getElementById('alertaTrimestre');

        document.querySelectorAll('.trim-btn-sgp').forEach(btn => {
            const n = parseInt(btn.dataset.trim);
            if (disponibles.includes(n)) {
                btn.classList.remove('bloqueado');
            } else {
                btn.classList.add('bloqueado');
                btn.classList.remove('activo');
            }
        });

        const faltantes = [1,2,3].filter(n => !disponibles.includes(n));
        if (faltantes.length > 0 && faltantes.length < 3) {
            const nombres = faltantes.map(n => 'Trimestre ' + n).join(' y ');
            alerta.textContent =
                `${nombres} aún no ${faltantes.length === 1 ? 'está' : 'están'} ` +
                `disponible${faltantes.length === 1 ? '' : 's'} para este pasante.`;
            alerta.style.display = 'block';
        } else {
            alerta.style.display = 'none';
        }

        if (disponibles.length > 0) {
            seleccionarTrimestre(disponibles[0]);
        }
    })
    .catch(() => {
        document.querySelectorAll('.trim-btn-sgp').forEach(btn => {
            btn.classList.remove('bloqueado');
        });
    });
}

async function submitConFormato(formato, btn) {
    const modulo = document.getElementById('mModulo').value;

    // ── Constancia Grupal — apertura directa en nueva pestaña ──
    if (modulo === 'constancias_grupal') {
        const filtroTipo  = document.getElementById('cg-filtro-tipo')?.value || 'todos';
        const tipoDoc     = document.getElementById('cg-tipo')?.value || 'servicio';
        const deptId      = document.getElementById('cg-dept')?.value || '';
        const instId      = document.getElementById('cg-inst')?.value || '';
        const estadoPas   = document.querySelector('[name="estado_pasantia"]')?.value || 'Finalizado';
        const periodoId   = document.querySelector('[name="periodo_id_grupal"]')?.value || '';

        if (filtroTipo === 'departamento' && !deptId) {
            Swal.fire({ icon:'warning', title:'Selecciona un departamento', confirmButtonColor:'#7c3aed' }); return;
        }
        if (filtroTipo === 'institucion' && !instId) {
            Swal.fire({ icon:'warning', title:'Selecciona una institución', confirmButtonColor:'#7c3aed' }); return;
        }

        const params = new URLSearchParams({
            tipo: tipoDoc, filtro: filtroTipo,
            departamento_id: deptId, institucion_id: instId,
            estado: estadoPas, periodo_id: periodoId
        });
        window.open(URLROOT + '/reportes/constanciaGrupal?' + params.toString(), '_blank');
        return;
    }

    // ── Resolver fechas para el módulo ejecutivo según radio seleccionado ──
    if (modulo === 'ejecutivo') {
        const tipoPeriodo = (document.querySelector('input[name="tipo_periodo"]:checked') || {}).value || 'historico';
        const hoy = new Date();
        const pad = function(n) { return String(n).padStart(2, '0'); };
        const fmt = function(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); };

        let fi = '', ff = '';
        if (tipoPeriodo === 'hoy') {
            fi = ff = fmt(hoy);
        } else if (tipoPeriodo === 'mes') {
            fi = fmt(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
            ff = fmt(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0));
        } else if (tipoPeriodo === 'rango') {
            fi = (document.getElementById('ejFechaInicio') || {}).value || '';
            ff = (document.getElementById('ejFechaFin')    || {}).value || '';
            if (!fi || !ff) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Fechas incompletas', text: 'Indique el rango de fechas personalizado.', confirmButtonColor: '#1D4ED8' });
                } else { alert('Indique el rango de fechas.'); }
                return;
            }
        }
        // fi y ff quedan vacíos para 'historico' — el backend devuelve todo
        document.getElementById('fechaInicioInput').value = fi;
        document.getElementById('fechaFinInput').value    = ff;
    }

    const fechaInicio = document.getElementById('fechaInicioInput').value.trim();
    const fechaFin    = (document.getElementById('fechaFinInput').disabled ? fechaInicio : document.getElementById('fechaFinInput').value.trim()) || fechaInicio;

    const htmlOriginal = btn.innerHTML;

    // ── Validar pasante seleccionado en módulos con buscador ─────────────────
    const modulosConPasante = ['evaluaciones', 'constancias'];
    if (modulosConPasante.includes(modulo)) {
        const modoConstancia = (document.getElementById('modoEmisionConstancia') || {}).value;
        if (modulo === 'constancias' && modoConstancia === 'manual') {
            const nom = (document.getElementById('manualNombreCompleto') || {}).value || '';
            const ced = (document.getElementById('manualCedula') || {}).value || '';
            if (!nom.trim() || !ced.trim()) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Datos requeridos',
                        text: 'Por favor ingrese el Nombre y la Cédula del pasante para emitir la constancia.',
                        confirmButtonColor: '#162660'
                    });
                } else {
                    alert('Debe ingresar Nombre y Cédula del pasante.');
                }
                btn.disabled = false;
                btn.innerHTML = htmlOriginal;
                return;
            }
        } else {
            const pid = document.getElementById('pasanteIdHidden').value;
            if (!pid) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        html: `
                            <div style="text-align:center; padding: 8px 0;">
                                <div style="width:64px;height:64px;border-radius:16px;
                                            background:rgba(245,158,11,0.1);
                                            display:flex;align-items:center;justify-content:center;
                                            margin:0 auto 14px;">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="8" x2="12" y2="12"/>
                                        <circle cx="12" cy="16" r="0.5" fill="#f59e0b"/>
                                    </svg>
                                </div>
                                <div style="font-size:1rem;font-weight:800;color:#0D1424;margin-bottom:6px;">
                                    Seleccionar Pasante
                                </div>
                                <div style="font-size:0.83rem;color:#64748b;line-height:1.5;">
                                    Debe buscar y seleccionar un pasante antes de generar el documento.
                                </div>
                            </div>`,
                        showConfirmButton: true,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#162660',
                        customClass: { popup: 'swal2-bento-popup' },
                        background: '#ffffff',
                        border: '1px solid #e2e8f0',
                        borderRadius: '20px'
                    });
                }
                btn.disabled = false;
                btn.innerHTML = htmlOriginal;
                return;
            }
        }
    }

    // ── Spinner en el botón ──────────────────────────────────────────────
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite;"></i> Verificando...';

    try {
        // Captura todos los campos del formulario (incluye los selects dinámicos
        // de rol/departamento/estado_usuario generados en mFiltros)
        const body = new FormData(document.getElementById('formReportes'));
        body.set('modulo', modulo);
        body.set('fecha_inicio', fechaInicio);
        body.set('fecha_fin', fechaFin);

        const res  = await fetch('<?= URLROOT ?>/reportes/validarDatos', { method: 'POST', body });
        const json = await res.json();

        if (json.success) {
            document.getElementById('mFormato').value = formato;
            document.getElementById('formReportes').submit();
            setTimeout(cerrarModal, 400);
        } else {
            if (typeof Swal !== 'undefined') {
                // ── SweetAlert Bento Premium ──
                // El color/icóno varía según el contexto del módulo
                const esEval  = modulo === 'evaluaciones';
                const esConst = modulo === 'constancias';
                
                // Detectar si el mensaje es por día no hábil/feriado en ficha diaria
                const esNoHabil = json.message && (json.message.includes('no hábil') || json.message.includes('feriado') || json.message.includes('no laborable'));

                const color   = esEval ? '#ec4899' : (esConst ? '#059669' : (esNoHabil ? '#ea580c' : '#162660'));
                const bgColor = esEval ? 'rgba(236,72,153,0.08)'
                              : (esConst ? 'rgba(5,150,105,0.08)' : (esNoHabil ? 'rgba(234,88,12,0.08)' : 'rgba(22,38,96,0.07)'));
                
                let svgPath = '<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'; // warning
                if (esEval) {
                    svgPath = '<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'; // doc
                }

                let tituloAlert = 'Sin registros';
                if (esEval) tituloAlert = 'Sin Evaluación';
                else if (esConst) tituloAlert = 'No disponible';
                else if (esNoHabil) tituloAlert = 'Día no laborable';

                Swal.fire({
                    html: `
                        <div style="text-align:center; padding:8px 0;">
                            <div style="width:64px;height:64px;border-radius:16px;
                                        background:${bgColor};
                                        display:flex;align-items:center;justify-content:center;
                                        margin:0 auto 14px;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
                                     stroke="${color}" stroke-width="2" stroke-linecap="round">${svgPath}</svg>
                            </div>
                            <div style="font-size:1rem;font-weight:800;color:#0D1424;margin-bottom:6px;">
                                ${tituloAlert}
                            </div>
                            <div style="font-size:0.83rem;color:#475569;line-height:1.5;max-width:280px;margin:0 auto;">
                                ${json.message || 'No hay datos para el período seleccionado.'}
                            </div>
                        </div>`,
                    showConfirmButton: true,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: color,
                    background: '#ffffff',
                    customClass: { popup: 'swal2-bento-popup' }
                });
            } else {
                alert(json.message || 'No hay datos para el período seleccionado.');
            }
        }
    } catch (err) {
        console.error('[SGP] Pre-flight error:', err);
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo verificar los datos. Intenta de nuevo.', confirmButtonColor: '#1D4ED8' });
        } else {
            alert('Error de conexión al verificar datos. Intenta de nuevo.');
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = htmlOriginal;
    }
}

function cerrarModal() { document.getElementById('modalConfig').classList.remove('active'); }

// ── Constancia Grupal: mostrar/ocultar filtro secundario ───────
function cgCambiarFiltro(val) {
    document.getElementById('cg-dept-box').style.display = val === 'departamento' ? '' : 'none';
    document.getElementById('cg-inst-box').style.display = val === 'institucion'  ? '' : 'none';
}

function validarGeneracion(event) {
    const modulo = document.getElementById('mModulo').value;
    if (modulo === 'asistencias') {
        const tipoReporte = document.getElementById('selectTipoAsistencia').value;
        const estadoPasante = document.getElementById('estado_pasante_temp') ? document.getElementById('estado_pasante_temp').value : 'activo';

        if (tipoReporte === 'total' && estadoPasante !== 'finalizado') {
            event.preventDefault(); 
            alert("Atención: El pasante no ha completado su pasantía para generar el consolidado total.");
            return false;
        }
    }
    setTimeout(cerrarModal, 600); 
    return true; 
}

function generarReporteEjecutivo() {
    window.open('<?= URLROOT ?>/reportes/ejecutivo', '_blank');
}
</script>