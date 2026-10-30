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
    max-width: 540px; 
    border-radius: 28px; 
    border: 1px solid rgba(255, 255, 255, 0.7);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    background: #FFFFFF;
}

.p-modal-premium-header {
    padding: 30px 32px 10px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    position: relative;
    border-left: 5px solid var(--p-blue);
    margin-top: 20px;
}

.r-form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
.r-form-group label { font-size: 0.72rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px; margin-left: 4px; }
.r-form-control { width: 100%; padding: 14px 18px; border: 2px solid #f1f5f9; border-radius: 16px; font-size: 0.95rem; font-family: var(--p-font); outline: none; background: #f8fafc; color: var(--p-ink); transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.r-form-control:focus { border-color: var(--p-blue); background: #fff; box-shadow: 0 0 0 5px var(--p-blue-dim); }

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
            <div class="p-card-footer">
                <button class="btn-p-soft btn-soft-pdf" onclick="abrirModal('constancias', 'pdf')"><i class="ti ti-file-type-pdf"></i> EMITIR CARTA</button>
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

<script>
var departamentos = <?= json_encode($data['departamentos'] ?? [], JSON_HEX_TAG | JSON_HEX_QUOT) ?>;
var instituciones = <?= json_encode($data['instituciones'] ?? [], JSON_HEX_TAG | JSON_HEX_QUOT) ?>;
var pasantes = <?= json_encode($data['pasantes'] ?? [], JSON_HEX_TAG | JSON_HEX_QUOT) ?>;

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
        case 'constancias':
            htmlFiltros = `
                <div class="r-form-group">
                    <label>Tipo de Documento</label>
                    <select name="tipo_constancia" class="r-form-control">
                        <option value="culminacion">Carta de Culminación</option>
                        <option value="servicio">Constancia de Servicio</option>
                    </select>
                </div>`;
            mFechas.style.display = 'none';
            break;

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
    } else {
        // Asegurar que el buscador quede limpio en otros módulos
        limpiarPasante();
        const box = document.getElementById('boxBuscadorPasante');
        if (box) box.style.display = 'none';
    }

    document.getElementById('modalConfig').classList.add('active');

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

    // ── Validar pasante seleccionado en módulos con buscador ─────────────────
    const modulosConPasante = ['evaluaciones', 'constancias'];
    if (modulosConPasante.includes(modulo)) {
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

    // ── Spinner en el botón ──────────────────────────────────────────────
    const htmlOriginal = btn.innerHTML;
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