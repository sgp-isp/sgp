<?php
// Calculate total size
$totalBytes = 0;
foreach ($backups as $backup) {
    $filepath = APPROOT . '/storage/backups/' . $backup['filename'];
    if (file_exists($filepath)) {
        $totalBytes += filesize($filepath);
    }
}

// Format total size (inline)
$units = ['B', 'KB', 'MB', 'GB'];
$bytes = max($totalBytes, 0);
$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
$pow = min($pow, count($units) - 1);
$bytes /= pow(1024, $pow);
$totalSizeFormatted = round($bytes, 2) . ' ' . $units[$pow];
?>


<style>
    /* ==================== STATS CARDS ==================== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #F3F4F6;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .stat-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 28px;
        flex-shrink: 0;
    }

    .stat-content {
        flex: 1;
    }

    .stat-content h3 {
        font-size: 2rem;
        font-weight: 700;
        color: #162660;
        margin: 0 0 4px 0;
        line-height: 1;
    }

    .stat-content p {
        color: #6B7280;
        margin: 0;
        font-size: 0.875rem;
        font-weight: 500;
    }

    /* ==================== TABLE CONTAINER ==================== */
    .table-container {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        border: 1px solid #F3F4F6;
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
    }

    .table-container thead th {
        background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
        color: #162660;
        font-weight: 600;
        padding: 16px;
        text-align: left;
        font-size: 14px;
        border-bottom: 2px solid #E5E7EB;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-container tbody tr {
        border-bottom: 1px solid #F3F4F6;
        transition: all 0.2s ease;
    }

    .table-container tbody tr:hover {
        background: #F9FAFB;
        transform: scale(1.01);
    }

    .table-container tbody td {
        padding: 18px 16px;
        font-size: 14px;
        color: #374151;
    }

    /* ==================== ACTION BUTTONS ==================== */
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        margin: 0 4px;
        font-size: 18px;
    }

    /* === SGP Bento Mobile Responsive Grid para Tablas === */
    @media (max-width: 768px) {
        .table-container table { display: block; width: 100%; border: none; }
        .table-container thead { display: none; }
        .table-container tbody { display: block; width: 100%; }
        .table-container tbody tr {
            display: flex;
            flex-direction: column;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .table-container tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
            text-align: right;
            font-size: 0.88rem;
        }
        .table-container tbody td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            gap: 10px;
        }
        .table-container tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            text-align: left;
            margin-right: auto;
        }
    }

    .btn-download {
        background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }

    .btn-download:hover {
        background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    .btn-restore {
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .btn-restore:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    .btn-delete {
        background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    .btn-delete:hover {
        background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }

    /* ==================== BANNER MODERNO (Estilo Usuario) ==================== */
    .welcome-banner {
        background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%);
        border-radius: 20px;
        padding: 32px 40px;
        margin-bottom: 32px;
        box-shadow: 0 10px 30px rgba(22, 38, 96, 0.15);
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    /* Círculo decorativo */
    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        pointer-events: none;
        z-index: 0;
    }
    
    /* Franja decorativa lateral — oculta para diseño limpio con gradiente índigo */
    .welcome-banner::after {
        display: none;
    }
    
    .welcome-content {
        display: flex;
        align-items: center;
        gap: 20px;
        z-index: 2;
    }
    
    .welcome-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .welcome-text h1.welcome-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 6px 0;
        color: white;
    }
    
    .welcome-text p.welcome-subtitle {
        font-size: 1.05rem;
        opacity: 0.9;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .subtitle-separator {
        opacity: 0.5;
        margin: 0 4px;
    }
    
    .welcome-meta {
        /* [SGP-FIX-BACKUP-BANNER-ACTIONS-OVERLAP] Es redundante con la tarjeta
           "Respaldos Totales" de abajo. OJO: usamos visibility:hidden (no display:none)
           a propósito — display:none lo saca del flujo flex y, como welcome-banner usa
           justify-content:space-between, .welcome-content (el título) pasaba a ser el
           último elemento en flujo y quedaba empujado hasta el borde derecho, tapando
           invisible los botones y bloqueando sus clics. visibility:hidden oculta el
           texto pero conserva su espacio reservado, sin mover nada más. */
        visibility: hidden;
        z-index: 2;
    }
    
    .welcome-stats {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .welcome-stats i {
        font-size: 24px;
    }
    
    .btn-stitch-gold {
        background-color: #ffffff !important;
        color: #162660 !important;
        font-weight: 700;
        border: none;
        padding: 12px 24px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        transition: all 0.2s ease;
        cursor: pointer;
        z-index: 10; /* Por encima de todo */
    }
    
    .btn-stitch-gold:hover {
        background-color: #f8fafc !important;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
    }

    /* [SGP-FEATURE-BACKUP-IMPORTAR] Botón secundario (outline) para "Importar desde
       archivo", pensado para convivir junto a btn-stitch-gold dentro del banner sin
       competir visualmente con la acción principal (Crear Nuevo Backup). */
    .btn-stitch-outline {
        background-color: transparent !important;
        color: #ffffff !important;
        font-weight: 700;
        border: 1.5px solid rgba(255, 255, 255, 0.6) !important;
        padding: 12px 24px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        z-index: 10;
    }

    .btn-stitch-outline:hover {
        background-color: rgba(255, 255, 255, 0.15) !important;
        border-color: #ffffff !important;
        transform: translateY(-2px);
    }

    /* ==================== EMPTY STATE ==================== */
    .empty-state {
        text-align: center;
        padding: 60px 40px;
        color: #6B7280;
    }

    .empty-state i {
        font-size: 64px;
        display: block;
        margin-bottom: 16px;
        opacity: 0.3;
        color: #162660;
    }

    .empty-state p {
        font-size: 1.1rem;
        margin: 0;
    }

    /* ==================== FILE ICON ==================== */
    .file-icon {
        margin-right: 12px;
        color: #162660;
        font-size: 20px;
    }

    /* ==================== BADGES ==================== */
    .badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-manual {
        background: #E0E7FF;
        color: #4338CA;
        border: 1px solid #C7D2FE;
    }

    .badge-safety {
        background: #FEF3C7;
        color: #92400E;
        border: 1px solid #FDE68A;
    }

    .tr-safety {
        background-color: #FFFBEB !important;
    }

    /* [SGP-FEATURE-BACKUP-IMPORTAR] Dropzone dentro del modal de importación */
    .sgp-dropzone {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 22px 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8fafc;
        margin-bottom: 14px;
    }
    .sgp-dropzone:hover {
        border-color: #94a3b8;
        background: #f1f5f9;
    }
    .sgp-dropzone.drag-active {
        border-color: #162660;
        background: #eef2ff;
        transform: scale(1.01);
    }
    .sgp-dropzone i {
        font-size: 2rem;
        color: #94a3b8;
        display: block;
        margin-bottom: 6px;
    }
    .sgp-dropzone.drag-active i {
        color: #162660;
    }

    /* Overlay de arrastre a nivel de página completa */
    #sgpDropOverlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 99999;
        background: rgba(22, 38, 96, 0.88);
        backdrop-filter: blur(2px);
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #fff;
        text-align: center;
        pointer-events: none; /* el drop real lo captura window, no el overlay */
    }
    #sgpDropOverlay .sgp-drop-frame {
        border: 3px dashed rgba(255,255,255,0.75);
        border-radius: 24px;
        padding: 48px 64px;
        animation: sgpDropPulse 1.4s ease-in-out infinite;
    }
    @keyframes sgpDropPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.75; }
    }
    @keyframes sgpSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>

<!-- [SGP-FEATURE-BACKUP-IMPORTAR] Overlay de arrastre a nivel de página -->
<div id="sgpDropOverlay">
    <div class="sgp-drop-frame">
        <i class="ti ti-file-upload" style="font-size:3.5rem; display:block; margin-bottom:14px;"></i>
        <h2 style="font-size:1.5rem; font-weight:800; margin:0 0 6px;">Suelta tu archivo .sql aquí</h2>
        <p style="opacity:.85; margin:0; font-size:0.95rem;">Se abrirá el asistente de importación y restauración</p>
    </div>
</div>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">
    <!-- BANNER MODERNO (Estilo Usuario) -->
    <style>
    @media (max-width: 1300px) and (min-width: 992px) {
        /* [SGP-FIX-BACKUP-BANNER-v3] El botón conserva su posición
           (position:absolute; right:20px; top:50%) intacta. Solo se
           reduce un poco su tamaño (padding/tipografía/ícono) en el
           tramo de ancho donde chocaba con el título, manteniendo texto
           e ícono visibles (mejor que ocultar el texto, por legibilidad
           y accesibilidad) y una altura de toque aún cómoda (~38px). */
        .dashboard-banner .btn-stitch-gold,
        .dashboard-banner .btn-stitch-outline {
            padding: 9px 16px !important;
            font-size: 0.85rem !important;
            gap: 6px !important;
        }
        .dashboard-banner .btn-stitch-gold i,
        .dashboard-banner .btn-stitch-outline i {
            font-size: 1rem !important;
        }
    }
    @media (max-width: 991px) {
        .dashboard-banner {
            flex-direction: column !important;
            align-items: flex-start !important;
            padding: 24px 20px !important;
            gap: 20px !important;
            height: auto !important;
        }
        .dashboard-banner .welcome-meta {
            visibility: hidden !important;
        }
        /* [SGP-FEATURE-BACKUP-IMPORTAR] El contenedor de los 2 botones deja de flotar
           (position:absolute) y pasa a ocupar todo el ancho, apilando los botones. */
        .dashboard-banner .dashboard-banner-actions {
            position: relative !important;
            top: 0 !important;
            right: 0 !important;
            transform: none !important;
            width: 100% !important;
            flex-direction: column !important;
            gap: 10px !important;
            margin-top: 10px !important;
        }
        .dashboard-banner button {
            position: relative !important;
            top: 0 !important;
            right: 0 !important;
            transform: none !important;
            width: 100% !important;
        }
    }
    @media (max-width: 576px) {
        /* [SGP-FIX-BACKUP-BANNER-MOBILE] Mejora del banner en móvil: el
           ícono dejaba una fila propia arriba (mucho espacio vacío) y el
           subtítulo se partía dejando el guión "-" huérfano al envolver.
           Se pone ícono + título en una misma fila (patrón estándar de
           tarjeta/banner) y el botón pasa a su propia línea completa. */
        .dashboard-banner.welcome-banner-compact {
            flex-direction: row !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            padding: 18px 18px !important;
            gap: 14px !important;
            min-height: unset !important;
        }
        .dashboard-banner .welcome-icon {
            width: 44px !important;
            height: 44px !important;
            font-size: 20px !important;
            border-radius: 12px !important;
        }
        .dashboard-banner .welcome-content {
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }
        .dashboard-banner .welcome-title {
            font-size: 1.15rem !important;
            line-height: 1.25 !important;
            margin-bottom: 2px !important;
        }
        .dashboard-banner .welcome-subtitle {
            font-size: 0.78rem !important;
            flex-wrap: wrap !important;
            row-gap: 2px !important;
        }
        .dashboard-banner .subtitle-separator {
            display: none !important;
        }
        .dashboard-banner .dashboard-banner-actions {
            flex: 1 1 100% !important;
            flex-direction: column !important;
            gap: 8px !important;
            width: 100% !important;
        }
        .dashboard-banner button.btn-stitch-gold,
        .dashboard-banner button.btn-stitch-outline {
            flex: 1 1 100% !important;
            justify-content: center !important;
            padding: 12px 16px !important;
            font-size: 0.9rem !important;
        }
    }
    </style>
    <div class="welcome-banner welcome-banner-compact dashboard-banner mb-4">
        <div class="welcome-icon">
            <i class="ti ti-database"></i>
        </div>
        
        <div class="welcome-content">
            <div class="welcome-text">
                <h1 class="welcome-title">Respaldo de Base de Datos</h1>
                <p class="welcome-subtitle">
                    <i class="ti ti-shield-check"></i>
                    <span>Gestión de Respaldos</span>
                    <span class="subtitle-separator">-</span>
                    <span>Restauraciones</span>
                </p>
            </div>
        </div>
        
        <div class="welcome-meta">
            <div class="welcome-stats">
                <i class="ti ti-file-database"></i>
                <span><?= count($backups) ?></span> respaldos
            </div>
        </div>
        
        <!-- [SGP-FEATURE-BACKUP-IMPORTAR] Envolvemos ambos botones en un solo contenedor
             que hereda EXACTAMENTE la misma position:absolute;right:20px;top:50% que ya
             tenía el botón original, para no repetir el choque de layout que costó dos
             iteraciones corregir antes. -->
        <div class="dashboard-banner-actions" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); display: flex; align-items: center; gap: 10px; z-index: 20;">
            <button onclick="importarBackup()" class="btn btn-stitch-outline shadow-sm">
                <i class="ti ti-upload"></i>
                Importar
            </button>
            <button onclick="createBackup()" class="btn btn-stitch-gold shadow-sm">
                <i class="ti ti-plus"></i>
                Crear Nuevo Backup
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (Session::hasFlash('success')): ?>
    <div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
        <p style="color: #059669; margin: 0; font-weight: 500;">
            <i class="ti ti-circle-check"></i> <?= Session::getFlash('success') ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('error')): ?>
    <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
        <p style="color: #DC2626; margin: 0; font-weight: 500;">
            <i class="ti ti-alert-circle"></i> <?= Session::getFlash('error') ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                <i class="ti ti-database"></i>
            </div>
            <div class="stat-content">
                <h3><?= count($backups) ?></h3>
                <p>Respaldos Totales</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                <i class="ti ti-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?= !empty($backups) ? date('d/m/Y', $backups[0]['timestamp']) : 'N/A' ?></h3>
                <p>Hora: <?= !empty($backups) ? date('H:i:s', $backups[0]['timestamp']) : '--:--' ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                <i class="ti ti-file-database"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalSizeFormatted ?></h3>
                <p>Tamaño Total</p>
            </div>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><i class="ti ti-file-text" style="margin-right: 8px;"></i>Nombre del Archivo</th>
                    <th><i class="ti ti-tag" style="margin-right: 8px;"></i>Tipo</th>
                    <th><i class="ti ti-calendar" style="margin-right: 8px;"></i>Fecha de Creación</th>
                    <th><i class="ti ti-database-export" style="margin-right: 8px;"></i>Tamaño</th>
                    <th style="text-align: center;"><i class="ti ti-settings" style="margin-right: 8px;"></i>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($backups)): ?>
                <tr>
                    <td colspan="4" class="empty-state">
                        <i class="ti ti-database-off"></i>
                        <p>No hay respaldos disponibles. Crea tu primer backup para comenzar.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                    <tr class="<?= $backup['is_safety'] ? 'tr-safety' : '' ?>">
                        <td data-label="Archivo">
                            <i class="ti ti-file-database file-icon"></i>
                            <strong><?= htmlspecialchars($backup['filename']) ?></strong>
                        </td>
                        <td data-label="Tipo">
                            <?php if($backup['is_safety']): ?>
                                <span class="badge badge-safety" title="Generado automáticamente por el sistema">Seguridad</span>
                            <?php else: ?>
                                <span class="badge badge-manual" title="Generado manualmente">Manual</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Creacin"><?= htmlspecialchars($backup['date']) ?></td>
                        <td data-label="Tamao"><strong><?= htmlspecialchars($backup['size']) ?></strong></td>
                        <td data-label="Acciones" style="text-align: center;">
                            <button onclick="downloadBackup('<?= htmlspecialchars($backup['filename']) ?>')" 
                                    class="btn-action btn-download" 
                                    title="Descargar respaldo">
                                <i class="ti ti-download"></i>
                            </button>
                            <button onclick="restoreBackup('<?= htmlspecialchars($backup['filename']) ?>')" 
                                    class="btn-action btn-restore" 
                                    title="Restaurar base de datos">
                                <i class="ti ti-refresh"></i>
                            </button>
                            <button onclick="deleteBackup('<?= htmlspecialchars($backup['filename']) ?>')" 
                                    class="btn-action btn-delete" 
                                    title="Eliminar respaldo">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="<?= URLROOT ?>/js/notyf.min.js"></script>

<script>
    // Initialize Notyf — [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta sin reload completo.
    var notyf = new Notyf({
        duration: 3000,
        position: { x: 'right', y: 'top' },
        ripple: true
    });
    
    // Create Backup
    function createBackup() {
        Swal.fire({
            title: '¿Crear Respaldo?',
            text: 'Se creará un respaldo completo de la base de datos',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#162660',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="ti ti-check"></i> Sí, crear respaldo',
            cancelButtonText: '<i class="ti ti-x"></i> Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                showLoading('Creando respaldo...');
                
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                return fetch('<?= URLROOT ?>/backup/create', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfMeta ? csrfMeta.getAttribute('content') : ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (!data.success) {
                        throw new Error(data.message);
                    }
                    return data;
                })
                .catch(error => {
                    hideLoading();
                    Swal.showValidationMessage(`Error: ${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Respaldo Creado!',
                    text: result.value.message,
                    confirmButtonColor: '#162660'
                }).then(() => {
                    location.reload();
                });
            }
        });
    }
    
    // Download Backup
    function downloadBackup(filename) {
        window.location.href = '<?= URLROOT ?>/backup/download/' + encodeURIComponent(filename);
    }
    
    // Restore Backup
    function restoreBackup(filename) {
        Swal.fire({
            title: '⚠️ ¿Restaurar Base de Datos?',
            html: `
                <div style="text-align: left; margin: 20px 0;">
                    <p style="margin-bottom: 16px; font-size: 1.05rem;">Esta acción sobrescribirá <strong>TODOS los datos actuales</strong> de la base de datos.</p>
                    <div style="background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 16px; border-radius: 8px;">
                        <strong style="color: #D97706; display: block; margin-bottom: 8px;">⚠️ ADVERTENCIA CRÍTICA:</strong>
                        <ul style="margin: 0; padding-left: 20px; color: #92400E;">
                            <li>Se perderán todos los cambios recientes</li>
                            <li>Esta acción NO se puede deshacer</li>
                            <li>Se recomienda crear un respaldo antes</li>
                            <li>Todos los usuarios serán desconectados</li>
                        </ul>
                    </div>
                    <p style="margin-top: 16px; font-weight: 600; background: #F3F4F6; padding: 12px; border-radius: 8px;">
                        📁 Archivo: <code style="color: #162660;">${filename}</code>
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="ti ti-refresh"></i> Sí, restaurar ahora',
            cancelButtonText: '<i class="ti ti-x"></i> Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                showLoading('Restaurando base de datos...');
                
                const csrfMetaRestore = document.querySelector('meta[name="csrf-token"]');
                const formData = new FormData();
                formData.append('confirmacion_texto', filename);
                return fetch(`<?= URLROOT ?>/backup/restore/${encodeURIComponent(filename)}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfMetaRestore ? csrfMetaRestore.getAttribute('content') : ''
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (!data.success) {
                        throw new Error(data.message);
                    }
                    return data;
                })
                .catch(error => {
                    hideLoading();
                    Swal.showValidationMessage(`Error: ${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Base de Datos Restaurada!',
                    html: `
                        <p>${result.value.message}</p>
                        <div style="background: #DBEAFE; padding: 12px; border-radius: 8px; margin-top: 16px;">
                            <i class="ti ti-info-circle" style="color: #3B82F6;"></i>
                            <span style="color: #1E40AF;">La página se recargará automáticamente</span>
                        </div>
                    `,
                    confirmButtonColor: '#162660',
                    timer: 3000
                }).then(() => {
                    location.reload();
                });
            }
        });
    }
    
    // [SGP-FEATURE-BACKUP-IMPORTAR] Importar y restaurar desde un archivo .sql subido.
    // Acepta un archivo ya seleccionado (archivoPrecargado) cuando se abre desde el
    // overlay de arrastrar-y-soltar a nivel de página.
    function importarBackup(archivoPrecargado) {
        Swal.fire({
            title: '📥 Importar y Restaurar desde Archivo',
            html: `
                <div style="text-align: left; margin: 16px 0;">
                    <p style="margin-bottom: 14px;">Selecciona un archivo <strong>.sql</strong> (por ejemplo, un respaldo descargado previamente) para restaurar la base de datos con su contenido.</p>
                    <div style="background: #FEF3C7; border-left: 4px solid #F59E0B; padding: 14px; border-radius: 8px; margin-bottom: 16px;">
                        <strong style="color: #D97706; display:block; margin-bottom:6px;">⚠️ ADVERTENCIA CRÍTICA:</strong>
                        <ul style="margin: 0; padding-left: 20px; color: #92400E; font-size: 0.9rem;">
                            <li>Sobrescribirá TODOS los datos actuales de la base de datos</li>
                            <li>Esta acción NO se puede deshacer</li>
                            <li>Se creará un respaldo de seguridad automático antes de continuar</li>
                        </ul>
                    </div>

                    <div id="swalDropzone" class="sgp-dropzone">
                        <i class="ti ti-cloud-upload"></i>
                        <div id="swalDropzoneText" style="font-weight:600; color:#475569; font-size:0.92rem;">Arrastra tu archivo .sql aquí o haz clic para seleccionar</div>
                        <div style="font-size:0.75rem; color:#94a3b8; margin-top:4px;">Tamaño máximo: 100 MB</div>
                    </div>
                    <input type="file" id="swalImportFile" accept=".sql" style="display:none;">

                    <div id="swalAnalisisStatus" style="display:none; padding:12px 14px; border-radius:8px; margin-bottom:14px; font-size:0.85rem;"></div>

                    <label style="display:block; font-weight:600; margin-bottom:6px; font-size:0.9rem;">Escribe <code>IMPORTAR Y RESTAURAR</code> para confirmar</label>
                    <input type="text" id="swalImportConfirm" placeholder="IMPORTAR Y RESTAURAR" style="width:100%; padding:8px; border:1.5px solid #cbd5e1; border-radius:8px; box-sizing:border-box;">
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="ti ti-upload"></i> Importar y restaurar',
            cancelButtonText: '<i class="ti ti-x"></i> Cancelar',
            showLoaderOnConfirm: true,
            didOpen: () => {
                const dropzone     = document.getElementById('swalDropzone');
                const dropzoneText = document.getElementById('swalDropzoneText');
                const fileInput    = document.getElementById('swalImportFile');
                const statusBox    = document.getElementById('swalAnalisisStatus');

                window.__sgpImportAnalisisOk = false;

                const asignarArchivo = (archivo) => {
                    if (!archivo) return;
                    const dt = new DataTransfer();
                    dt.items.add(archivo);
                    fileInput.files = dt.files;
                    dropzoneText.textContent = archivo.name;
                    analizarArchivoCliente(archivo, statusBox);
                };

                dropzone.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', () => asignarArchivo(fileInput.files[0]));

                // [SGP-FIX-IMPORTAR-DROPZONE-BUBBLING] stopPropagation() es clave aquí:
                // sin esto, el drag dentro del modal también burbujea hasta window y
                // dispara el overlay de página completa (z-index más alto) encima del
                // modal, y en el drop reabre importarBackup() por segunda vez.
                dropzone.addEventListener('dragenter', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('drag-active');
                });
                dropzone.addEventListener('dragleave', (e) => {
                    e.stopPropagation();
                    dropzone.classList.remove('drag-active');
                });
                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('drag-active');
                    const archivo = e.dataTransfer.files && e.dataTransfer.files[0];
                    if (archivo) asignarArchivo(archivo);
                });

                if (archivoPrecargado) {
                    asignarArchivo(archivoPrecargado);
                }
            },
            preConfirm: () => {
                const fileInput    = document.getElementById('swalImportFile');
                const confirmInput = document.getElementById('swalImportConfirm');
                const archivo      = fileInput.files[0];
                const confirmacion = (confirmInput.value || '').trim();

                if (!archivo) {
                    Swal.showValidationMessage('Selecciona un archivo .sql');
                    return false;
                }
                if (!archivo.name.toLowerCase().endsWith('.sql')) {
                    Swal.showValidationMessage('El archivo debe tener extensión .sql');
                    return false;
                }
                if (!window.__sgpImportAnalisisOk) {
                    Swal.showValidationMessage('Espera a que termine el análisis del archivo, o selecciona un archivo válido.');
                    return false;
                }
                if (confirmacion !== 'IMPORTAR Y RESTAURAR') {
                    Swal.showValidationMessage('Debes escribir exactamente "IMPORTAR Y RESTAURAR"');
                    return false;
                }

                showLoading('Importando y restaurando base de datos...');

                const csrfMetaImport = document.querySelector('meta[name="csrf-token"]');
                const formData = new FormData();
                formData.append('archivo_sql', archivo);
                formData.append('confirmacion_texto', confirmacion);

                return fetch('<?= URLROOT ?>/backup/importar', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfMetaImport ? csrfMetaImport.getAttribute('content') : ''
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (!data.success) {
                        throw new Error(data.message);
                    }
                    return data;
                })
                .catch(error => {
                    hideLoading();
                    Swal.showValidationMessage(`Error: ${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Base de Datos Restaurada!',
                    html: `
                        <p>${result.value.message}</p>
                        <div style="background: #DBEAFE; padding: 12px; border-radius: 8px; margin-top: 16px;">
                            <i class="ti ti-info-circle" style="color: #3B82F6;"></i>
                            <span style="color: #1E40AF;">La página se recargará automáticamente</span>
                        </div>
                    `,
                    confirmButtonColor: '#162660',
                    timer: 3000
                }).then(() => {
                    location.reload();
                });
            }
        });
    }

    // [SGP-FEATURE-BACKUP-IMPORTAR] Análisis previo del archivo en el navegador: valida
    // tamaño y busca patrones típicos de un dump SQL en los primeros bytes, ANTES de
    // enviarlo al servidor (defensa en profundidad — BackupController::importar() vuelve
    // a validar todo de forma autoritativa en el backend).
    function analizarArchivoCliente(archivo, statusBox) {
        window.__sgpImportAnalisisOk = false;
        statusBox.style.display = 'block';
        statusBox.style.background = '#EFF6FF';
        statusBox.style.color = '#1E40AF';
        statusBox.innerHTML = '<i class="ti ti-loader-2" style="display:inline-block;animation:sgpSpin 1s linear infinite;"></i> Analizando archivo...';

        const tamanoMB = (archivo.size / (1024 * 1024)).toFixed(2);

        if (archivo.size === 0) {
            statusBox.style.background = '#FEF2F2';
            statusBox.style.color = '#B91C1C';
            statusBox.innerHTML = '<i class="ti ti-x"></i> El archivo está vacío.';
            return;
        }
        if (archivo.size > 100 * 1024 * 1024) {
            statusBox.style.background = '#FEF2F2';
            statusBox.style.color = '#B91C1C';
            statusBox.innerHTML = '<i class="ti ti-x"></i> El archivo supera el límite de 100 MB.';
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            setTimeout(() => {
                const muestra = String(reader.result || '');
                const pareceSql = /\b(CREATE\s+TABLE|INSERT\s+INTO|SET\s+NAMES|DROP\s+TABLE|-- MySQL dump)\b/i.test(muestra);
                if (pareceSql) {
                    window.__sgpImportAnalisisOk = true;
                    statusBox.style.background = '#F0FDF4';
                    statusBox.style.color = '#15803D';
                    statusBox.innerHTML = `<i class="ti ti-circle-check"></i> Archivo válido (${tamanoMB} MB) — listo para importar.`;
                } else {
                    statusBox.style.background = '#FEF2F2';
                    statusBox.style.color = '#B91C1C';
                    statusBox.innerHTML = '<i class="ti ti-x"></i> El archivo no parece ser un dump SQL válido.';
                }
            }, 400); // pequeña pausa para que el estado "Analizando..." sea perceptible
        };
        reader.onerror = () => {
            statusBox.style.background = '#FEF2F2';
            statusBox.style.color = '#B91C1C';
            statusBox.innerHTML = '<i class="ti ti-x"></i> No se pudo leer el archivo.';
        };
        reader.readAsText(archivo.slice(0, 8192));
    }

    // [SGP-FEATURE-BACKUP-IMPORTAR] Arrastrar-y-soltar a nivel de página completa: al
    // arrastrar un archivo sobre cualquier parte del módulo de Respaldos, se muestra un
    // overlay a pantalla completa; al soltarlo, se abre el asistente de importación con
    // ese archivo ya seleccionado. Se registra una sola vez por carga de página (guardia
    // en window) porque PJAX re-ejecuta este <script> en cada navegación sin recarga, y
    // addEventListener acumularía listeners duplicados en visitas repetidas a esta vista.
    if (!window.__sgpBackupDragDropInit) {
        window.__sgpBackupDragDropInit = true;
        var sgpDragCounter = 0;

        var sgpEsArrastreDeArchivo = function (e) {
            return e.dataTransfer && Array.prototype.slice.call(e.dataTransfer.types || []).indexOf('Files') !== -1;
        };

        // [SGP-FIX-IMPORTAR-DROPZONE-BUBBLING] Defensa adicional: si el modal de
        // importación ya está abierto (Swal.isVisible()), el overlay de página completa
        // no debe activarse — el dropzone del propio modal ya maneja su arrastre.
        window.addEventListener('dragenter', function (e) {
            if (!sgpEsArrastreDeArchivo(e) || (window.Swal && Swal.isVisible())) return;
            e.preventDefault();
            sgpDragCounter++;
            var overlay = document.getElementById('sgpDropOverlay');
            if (overlay) overlay.style.display = 'flex';
        });

        window.addEventListener('dragover', function (e) {
            if (sgpEsArrastreDeArchivo(e) && !(window.Swal && Swal.isVisible())) e.preventDefault();
        });

        window.addEventListener('dragleave', function (e) {
            if (!sgpEsArrastreDeArchivo(e)) return;
            sgpDragCounter = Math.max(0, sgpDragCounter - 1);
            if (sgpDragCounter === 0) {
                var overlay = document.getElementById('sgpDropOverlay');
                if (overlay) overlay.style.display = 'none';
            }
        });

        window.addEventListener('drop', function (e) {
            if (!sgpEsArrastreDeArchivo(e) || (window.Swal && Swal.isVisible())) return;
            e.preventDefault();
            sgpDragCounter = 0;
            var overlay = document.getElementById('sgpDropOverlay');
            if (overlay) overlay.style.display = 'none';
            var archivo = e.dataTransfer.files && e.dataTransfer.files[0];
            if (archivo && typeof importarBackup === 'function') {
                importarBackup(archivo);
            }
        });
    }

    // Delete Backup
    function deleteBackup(filename) {
        Swal.fire({
            title: '¿Eliminar Respaldo?',
            html: `
                <p>Esta acción no se puede deshacer</p>
                <p style="margin-top: 12px; background: #F3F4F6; padding: 12px; border-radius: 8px;">
                    <strong>Archivo:</strong> <code>${filename}</code>
                </p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="ti ti-trash"></i> Sí, eliminar',
            cancelButtonText: '<i class="ti ti-x"></i> Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading('Eliminando respaldo...');
                
                var csrfMetaDelete = document.querySelector('meta[name="csrf-token"]');
                fetch('<?= URLROOT ?>/backup/delete/' + encodeURIComponent(filename), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfMetaDelete ? csrfMetaDelete.getAttribute('content') : ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        NotificationService.success(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        NotificationService.error(data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    NotificationService.error('Error al eliminar el respaldo');
                });
            }
        });
    }
</script>
