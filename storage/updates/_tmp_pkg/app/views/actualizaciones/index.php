<div class="content-wrapper updates-bento-container" style="padding: 28px 24px 60px; max-width: 1380px; margin: 0 auto; font-family: inherit;">
    
    <!-- ========================================================
         1. HERO HEADER BENTO
         ======================================================== -->
    <div class="bento-header-hero">
        <div class="header-left">
            <div class="status-pill-pulse">
                <span class="pulse-indicator">
                    <span class="ping"></span>
                    <span class="dot"></span>
                </span>
                <span class="pill-text">Centro de Despliegue en Red Local &bull; En Línea</span>
            </div>
            
            <h1 class="header-title">
                Actualizaciones del Sistema
            </h1>
            <p class="header-subtitle">
                Despliegue de código, vistas y migraciones SQL en caliente directamente a través de tu red Wi-Fi o Cable local, con respaldo automático preventivo.
            </p>
        </div>

        <div class="header-actions">
            <a href="<?= URLROOT ?>/actualizaciones/empaquetar" class="btn-action-hero btn-package" title="Genera un archivo ZIP con el código fuente limpio de este equipo">
                <div class="btn-icon-wrapper">
                    <i class="ti ti-package-export"></i>
                </div>
                <div class="btn-text-wrapper">
                    <span class="btn-label">Empaquetar Código (.ZIP)</span>
                    <span class="btn-subtext">Genera paquete descargable</span>
                </div>
            </a>
        </div>
    </div>

    <!-- ========================================================
         2. ALERTAS DEL SISTEMA & NOTIFICACIONES (ZIPARCHIVE CHECK)
         ======================================================== -->
    <?php if (!$zip_enabled): ?>
        <div class="bento-alert bento-alert-danger">
            <div class="alert-icon-wrap">
                <i class="ti ti-alert-triangle-filled"></i>
            </div>
            <div class="alert-content">
                <h4 class="alert-title">Acción requerida: Extensión ZipArchive no disponible en PHP</h4>
                <p class="alert-desc">
                    El servidor web en esta máquina tiene desactivada la extensión <code>zip</code> de PHP. Para poder descomprimir e instalar actualizaciones automáticamente:
                </p>
                <div class="alert-code-steps">
                    <span>1. Abre <code>C:\xampp\php\php.ini</code></span>
                    <span>2. Busca la línea <code>;extension=zip</code> y quítale el punto y coma inicial: <code>extension=zip</code></span>
                    <span>3. Reinicia <strong>Apache</strong> en el Panel de Control de XAMPP.</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('error')): ?>
        <div class="bento-alert bento-alert-danger">
            <div class="alert-icon-wrap"><i class="ti ti-circle-x-filled"></i></div>
            <div class="alert-content">
                <h4 class="alert-title">Error en el proceso</h4>
                <p class="alert-desc"><?= Session::getFlash('error') ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('success')): ?>
        <div class="bento-alert bento-alert-success">
            <div class="alert-icon-wrap"><i class="ti ti-circle-check-filled"></i></div>
            <div class="alert-content">
                <h4 class="alert-title">Operación Exitosa</h4>
                <p class="alert-desc"><?= Session::getFlash('success') ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================================
         3. GRID PRINCIPAL BENTO UI
         ======================================================== -->
    <div class="bento-main-grid">

        <!-- TARJETA 1: ZONA PRINCIPAL DE CARGA Y ACTUALIZACIÓN (7 COLUMNAS) -->
        <div class="bento-card card-dropzone-section">
            <div class="card-inner-header">
                <div class="icon-avatar gradient-blue">
                    <i class="ti ti-cloud-upload"></i>
                </div>
                <div>
                    <h3 class="card-title">Cargar Paquete de Actualización</h3>
                    <p class="card-desc">Sube el archivo <code>actualizacion_SGP.zip</code> generado desde tu laptop</p>
                </div>
            </div>

            <!-- FORMULARIO DROPZONE -->
            <form id="formActualizar" enctype="multipart/form-data" class="dropzone-form">
                <input type="hidden" name="_csrf" value="<?= $csrf_token ?>">
                
                <div id="dropzoneContainer" class="dropzone-zone <?= !$zip_enabled ? 'dropzone-disabled' : '' ?>">
                    <input type="file" id="inputZip" name="paquete_zip" accept=".zip" style="display: none;" <?= !$zip_enabled ? 'disabled' : '' ?>>
                    
                    <div class="dz-floating-icon" id="dzIconWrapper">
                        <i class="ti ti-file-zip"></i>
                    </div>

                    <h4 id="dzTitle" class="dz-primary-title">
                        Arrastra tu archivo .ZIP aquí
                    </h4>
                    <p id="dzSubtitle" class="dz-secondary-subtitle">
                        o haz clic en cualquier lugar de este recuadro para seleccionarlo de tu laptop.
                    </p>

                    <div class="dz-meta-tags">
                        <span class="meta-tag"><i class="ti ti-file-check"></i> Formato: .ZIP</span>
                        <span class="meta-tag"><i class="ti ti-arrows-maximize"></i> Límite: <?= $max_upload_size ?></span>
                    </div>

                    <div id="fileInfoBadge" class="file-loaded-badge" style="display: none;">
                        <div class="badge-icon"><i class="ti ti-circle-check-filled"></i></div>
                        <div class="badge-info">
                            <span id="fileNameText" class="badge-filename">archivo.zip</span>
                            <span class="badge-status">Listo para verificación y despliegue</span>
                        </div>
                        <button type="button" id="btnQuitarArchivo" class="btn-remove-file" title="Quitar archivo">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>

                <!-- CARACTERÍSTICAS DE SEGURIDAD / CHECKLIST -->
                <div class="security-guarantee-card">
                    <div class="sec-item">
                        <div class="sec-icon"><i class="ti ti-shield-check"></i></div>
                        <div class="sec-text">
                            <strong>Respaldo Automático (Rollback):</strong> Se genera una copia completa de los archivos a reemplazar antes de sobreescribir.
                        </div>
                    </div>
                    <div class="sec-item">
                        <div class="sec-icon"><i class="ti ti-database"></i></div>
                        <div class="sec-text">
                            <strong>Migración SQL Inteligente:</strong> Detecta y ejecuta scripts de base de datos incluidos en el paquete automáticamente.
                        </div>
                    </div>
                </div>

                <!-- BOTÓN PRINCIPAL DE ACCIÓN -->
                <button type="submit" id="btnInstalar" class="btn-deploy-submit" <?= !$zip_enabled ? 'disabled' : 'disabled' ?>>
                    <span class="btn-spinner" style="display: none;"><i class="ti ti-loader-2 spin"></i></span>
                    <i class="ti ti-rocket" id="btnIconRocket"></i>
                    <span id="btnTextDeploy">Instalar y Desplegar Actualización</span>
                </button>
            </form>
        </div>

        <!-- TARJETA 2 Y 3: DIAGNÓSTICO DEL SERVIDOR Y ESTADO (5 COLUMNAS) -->
        <div class="bento-sidebar-cards">

            <!-- Diagnóstico del Servidor -->
            <div class="bento-card card-diagnostics">
                <div class="card-inner-header compact">
                    <div class="icon-avatar gradient-purple">
                        <i class="ti ti-server-cog"></i>
                    </div>
                    <div>
                        <h3 class="card-title">Salud del Servidor Local</h3>
                        <p class="card-desc">Parámetros del entorno institucional</p>
                    </div>
                </div>

                <div class="diagnostics-list">
                    <!-- Fila 1: Motor PHP -->
                    <div class="diag-row">
                        <div class="diag-meta">
                            <i class="ti ti-brand-php diag-icon"></i>
                            <span class="diag-name">Motor de Ejecución</span>
                        </div>
                        <span class="pill-chip chip-php">
                            <i class="ti ti-shield-lock"></i> PHP <?= $php_version ?>
                        </span>
                    </div>

                    <!-- Fila 2: ZipArchive -->
                    <div class="diag-row">
                        <div class="diag-meta">
                            <i class="ti ti-file-zip diag-icon"></i>
                            <span class="diag-name">Módulo ZipArchive</span>
                        </div>
                        <?php if ($zip_enabled): ?>
                            <span class="pill-chip chip-success">
                                <i class="ti ti-check"></i> Activo
                            </span>
                        <?php else: ?>
                            <span class="pill-chip chip-danger" title="Debes habilitar extension=zip en php.ini">
                                <i class="ti ti-x"></i> Deshabilitado
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Fila 3: IP del Servidor -->
                    <div class="diag-row">
                        <div class="diag-meta">
                            <i class="ti ti-network diag-icon"></i>
                            <span class="diag-name">Dirección IP Servidor</span>
                        </div>
                        <span class="pill-chip chip-ip">
                            <code><?= htmlspecialchars($server_ip) ?></code>
                        </span>
                    </div>

                    <!-- Fila 4: Almacenamiento en Disco con Barra -->
                    <div class="diag-storage-box">
                        <div class="storage-header">
                            <span class="storage-label"><i class="ti ti-device-floppy"></i> Espacio en Disco Servidor</span>
                            <span class="storage-val"><strong><?= $espacio_libre ?></strong> libres de <?= $espacio_total ?></span>
                        </div>
                        <div class="storage-progress-track">
                            <div class="storage-progress-fill" style="width: <?= min(100, max(5, $espacio_porcentaje)) ?>%;"></div>
                        </div>
                        <div class="storage-footer">
                            <span><?= $espacio_usado ?> ocupados (<?= $espacio_porcentaje ?>%)</span>
                            <span><?= 100 - $espacio_porcentaje ?>% disponible</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bento Mini: Estado del Despliegue -->
            <div class="bento-card card-last-status">
                <div class="card-inner-header compact">
                    <div class="icon-avatar gradient-amber">
                        <i class="ti ti-history"></i>
                    </div>
                    <div>
                        <h3 class="card-title">Último Despliegue</h3>
                        <p class="card-desc">Estado de la versión instalada</p>
                    </div>
                </div>

                <?php if ($ultima_actualizacion): ?>
                    <div class="last-update-banner">
                        <div class="lub-top">
                            <span class="pill-chip chip-success">
                                <i class="ti ti-circle-check-filled"></i> Versión Activa
                            </span>
                            <span class="lub-date"><?= $ultima_actualizacion['fecha'] ?></span>
                        </div>
                        <div class="lub-package-name">
                            <i class="ti ti-package"></i>
                            <span><?= htmlspecialchars($ultima_actualizacion['archivo_paquete']) ?></span>
                        </div>
                        <div class="lub-details">
                            <span class="lub-stat"><i class="ti ti-files"></i> <strong><?= $ultima_actualizacion['archivos_actualizados_count'] ?></strong> archivos</span>
                            <span class="lub-stat"><i class="ti ti-user"></i> <?= htmlspecialchars($ultima_actualizacion['usuario_responsable'] ?? 'Admin') ?></span>
                            <?php if (!empty($ultima_actualizacion['sql_ejecutado'])): ?>
                                <span class="lub-stat stat-sql"><i class="ti ti-database"></i> BD actualizada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state-card">
                        <div class="empty-icon-wrapper">
                            <i class="ti ti-sparkles"></i>
                        </div>
                        <h5 class="empty-title">Servidor Base Listo</h5>
                        <p class="empty-text">No se han aplicado paquetes ZIP todavía en este equipo. El primer despliegue que realices quedará registrado aquí.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- TARJETA 4: HISTORIAL DE ACTUALIZACIONES (12 COLUMNAS) -->
        <div class="bento-card card-history-section">
            <div class="card-inner-header">
                <div class="icon-avatar gradient-indigo">
                    <i class="ti ti-list-details"></i>
                </div>
                <div>
                    <h3 class="card-title">Registro Histórico de Despliegues</h3>
                    <p class="card-desc">Auditoría completa de versiones, archivos sustituidos y puntos de restauración</p>
                </div>
            </div>

            <?php if (!empty($historial)): ?>
                <div class="table-responsive-wrapper">
                    <table class="bento-table">
                        <thead>
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Paquete ZIP</th>
                                <th>Tamaño</th>
                                <th>Archivos</th>
                                <th>Base de Datos</th>
                                <th>Responsable</th>
                                <th>Punto de Rollback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as $item): ?>
                                <tr>
                                    <td class="cell-date">
                                        <i class="ti ti-calendar"></i>
                                        <span><?= $item['fecha'] ?></span>
                                    </td>
                                    <td class="cell-filename">
                                        <div class="filename-pill">
                                            <i class="ti ti-file-zip"></i>
                                            <span><?= htmlspecialchars($item['archivo_paquete']) ?></span>
                                        </div>
                                    </td>
                                    <td class="cell-size">
                                        <?= $item['tamano_kb'] ?> KB
                                    </td>
                                    <td class="cell-files">
                                        <span class="pill-chip chip-files">
                                            <?= $item['archivos_actualizados_count'] ?> archivos
                                        </span>
                                    </td>
                                    <td class="cell-sql">
                                        <?php if (!empty($item['sql_ejecutado'])): ?>
                                            <span class="pill-chip chip-success">
                                                <i class="ti ti-database-check"></i> Aplicado
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted-tag">Sin cambios</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cell-author">
                                        <span class="author-name"><?= htmlspecialchars($item['usuario_responsable'] ?? 'Admin') ?></span>
                                    </td>
                                    <td class="cell-backup">
                                        <code class="backup-code" title="Punto de restauración para revertir cambios"><?= htmlspecialchars($item['backup_previo']) ?></code>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-history-box">
                    <div class="empty-icon"><i class="ti ti-archive"></i></div>
                    <h4>Sin historial registrado</h4>
                    <p>Los despliegues que apliques desde tu laptop aparecerán ordenados cronológicamente en esta tabla.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ========================================================
     ESTILOS CSS PREMIUM - BENTO UI 2026
     ======================================================== -->
<style>
/* ── Variables de color y tokens ── */
:root {
    --bento-bg: #f8fafc;
    --bento-card-bg: #ffffff;
    --bento-border: #e2e8f0;
    --bento-border-hover: #cbd5e1;
    --bento-text-main: #0f172a;
    --bento-text-muted: #64748b;
    --bento-primary: #2563eb;
    --bento-primary-hover: #1d4ed8;
    --bento-primary-glow: rgba(37, 99, 235, 0.25);
    --bento-radius-lg: 24px;
    --bento-radius-md: 16px;
    --bento-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.04), 0 0 0 1px rgba(226, 232, 240, 0.7);
    --bento-shadow-hover: 0 20px 40px -10px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(59, 130, 246, 0.3);
}

.updates-bento-container {
    animation: fadeInBento 0.35s ease-out;
}

@keyframes fadeInBento {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Hero Header ── */
.bento-header-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 20px;
}

.status-pill-pulse {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.25);
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 12px;
}

.pulse-indicator {
    position: relative;
    width: 8px;
    height: 8px;
    display: flex;
}
.pulse-indicator .ping {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background-color: #10b981;
    animation: pulsePing 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
    opacity: 0.75;
}
.pulse-indicator .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #10b981;
}

@keyframes pulsePing {
    75%, 100% {
        transform: scale(2.3);
        opacity: 0;
    }
}

.header-title {
    font-size: 2.1rem;
    font-weight: 800;
    color: var(--bento-text-main);
    letter-spacing: -0.03em;
    margin: 0 0 8px 0;
    line-height: 1.15;
}

.header-subtitle {
    color: var(--bento-text-muted);
    font-size: 0.98rem;
    margin: 0;
    max-width: 780px;
    line-height: 1.55;
}

/* ── Botón Empaquetar Hero ── */
.btn-action-hero {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: var(--bento-radius-md);
    text-decoration: none;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.btn-action-hero:hover {
    background: #1e293b;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.22);
}
.btn-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(56, 189, 248, 0.15);
    color: #38bdf8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}
.btn-text-wrapper {
    display: flex;
    flex-direction: column;
}
.btn-label {
    font-size: 0.92rem;
    font-weight: 700;
    line-height: 1.2;
}
.btn-subtext {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* ── Alertas Bento ── */
.bento-alert {
    display: flex;
    gap: 16px;
    padding: 18px 22px;
    border-radius: var(--bento-radius-md);
    margin-bottom: 24px;
    align-items: flex-start;
    animation: slideDown 0.3s ease;
}
.bento-alert-danger {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    color: #9f1239;
}
.bento-alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}
.alert-icon-wrap {
    font-size: 1.6rem;
    flex-shrink: 0;
    line-height: 1;
}
.alert-title {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 4px 0;
}
.alert-desc {
    font-size: 0.9rem;
    margin: 0;
    line-height: 1.5;
}
.alert-code-steps {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 10px;
    font-size: 0.85rem;
    background: rgba(255, 255, 255, 0.7);
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid rgba(225, 29, 72, 0.2);
}
.alert-code-steps code {
    background: #ffffff;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
    border: 1px solid #fda4af;
}

/* ── Grid Principal Bento ── */
.bento-main-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 24px;
}

.bento-card {
    background: var(--bento-card-bg);
    border-radius: var(--bento-radius-lg);
    padding: 28px;
    box-shadow: var(--bento-shadow);
    transition: all 0.25s ease;
    border: 1px solid rgba(226, 232, 240, 0.8);
    position: relative;
}
.bento-card:hover {
    box-shadow: var(--bento-shadow-hover);
}

.card-dropzone-section {
    grid-column: span 7;
    display: flex;
    flex-direction: column;
}
.bento-sidebar-cards {
    grid-column: span 5;
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.card-history-section {
    grid-column: span 12;
}

/* ── Card Header ── */
.card-inner-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 22px;
}
.card-inner-header.compact {
    margin-bottom: 16px;
}
.card-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--bento-text-main);
    margin: 0;
    letter-spacing: -0.01em;
}
.card-desc {
    font-size: 0.85rem;
    color: var(--bento-text-muted);
    margin: 2px 0 0 0;
}

.icon-avatar {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45rem;
    color: #ffffff;
    flex-shrink: 0;
}
.gradient-blue { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
.gradient-purple { background: linear-gradient(135deg, #6366f1, #4f46e5); }
.gradient-amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
.gradient-indigo { background: linear-gradient(135deg, #3b82f6, #6366f1); }

/* ── Zona Dropzone ── */
.dropzone-form {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.dropzone-zone {
    border: 2px dashed #cbd5e1;
    border-radius: 20px;
    padding: 44px 24px;
    text-align: center;
    cursor: pointer;
    background: #f8fafc;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}

.dropzone-zone:hover {
    border-color: #3b82f6;
    background: #eff6ff;
    transform: translateY(-2px);
}

.dropzone-zone.dragover {
    border-color: #2563eb !important;
    background: #dbeafe !important;
    box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.15);
    transform: scale(1.01);
}

.dropzone-zone.dropzone-disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #f1f5f9;
}

.dz-floating-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin-bottom: 16px;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 8px 16px rgba(2, 132, 199, 0.12);
}

.dropzone-zone:hover .dz-floating-icon,
.dropzone-zone.dragover .dz-floating-icon {
    transform: scale(1.15) rotate(-6deg);
    background: #bae6fd;
    color: #0369a1;
}

.dz-primary-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--bento-text-main);
    margin: 0 0 8px 0;
}
.dz-secondary-subtitle {
    font-size: 0.88rem;
    color: var(--bento-text-muted);
    margin: 0 0 18px 0;
    max-width: 360px;
    line-height: 1.45;
}

.dz-meta-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}
.meta-tag {
    font-size: 0.76rem;
    font-weight: 700;
    background: #ffffff;
    color: #475569;
    padding: 4px 10px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Badge de archivo cargado */
.file-loaded-badge {
    background: #ecfdf5;
    border: 1px solid #6ee7b7;
    color: #065f46;
    padding: 12px 18px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 14px;
    max-width: 90%;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
    animation: popIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes popIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.badge-icon {
    font-size: 1.6rem;
    color: #10b981;
    line-height: 1;
}
.badge-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}
.badge-filename {
    font-weight: 800;
    font-size: 0.95rem;
    color: #047857;
    word-break: break-all;
}
.badge-status {
    font-size: 0.78rem;
    color: #059669;
    font-weight: 600;
}
.btn-remove-file {
    background: none;
    border: none;
    color: #059669;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    font-size: 1.1rem;
    line-height: 1;
    margin-left: 8px;
    transition: background 0.15s;
}
.btn-remove-file:hover {
    background: rgba(16, 185, 129, 0.2);
    color: #064e3b;
}

/* ── Garantía de seguridad ── */
.security-guarantee-card {
    background: #f8fafc;
    border-radius: var(--bento-radius-md);
    padding: 16px;
    margin-bottom: 22px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.sec-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.84rem;
    color: #475569;
    line-height: 1.45;
}
.sec-icon {
    color: #10b981;
    font-size: 1.15rem;
    line-height: 1;
    flex-shrink: 0;
    margin-top: 2px;
}

/* ── Botón Desplegar ── */
.btn-deploy-submit {
    width: 100%;
    padding: 16px 24px;
    border-radius: var(--bento-radius-md);
    border: none;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #ffffff;
    font-weight: 800;
    font-size: 1.05rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    box-shadow: 0 10px 25px -5px var(--bento-primary-glow);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.btn-deploy-submit:not(:disabled):hover {
    background: linear-gradient(135deg, #1d4ed8, #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 14px 30px -4px rgba(37, 99, 235, 0.4);
}
.btn-deploy-submit:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

/* ── Diagnósticos ── */
.diagnostics-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.diag-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.diag-meta {
    display: flex;
    align-items: center;
    gap: 10px;
}
.diag-icon {
    color: #64748b;
    font-size: 1.15rem;
}
.diag-name {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--bento-text-muted);
}

.pill-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
}
.chip-php {
    background: #e0e7ff;
    color: #4338ca;
}
.chip-success {
    background: #dcfce7;
    color: #15803d;
}
.chip-danger {
    background: #fee2e2;
    color: #b91c1c;
}
.chip-ip code {
    background: #f1f5f9;
    color: #0f172a;
    padding: 2px 6px;
    border-radius: 6px;
    font-weight: 700;
    border: 1px solid #e2e8f0;
}
.chip-files {
    background: #e0f2fe;
    color: #0284c7;
}

/* ── Almacenamiento barra ── */
.diag-storage-box {
    background: #f8fafc;
    border-radius: var(--bento-radius-md);
    padding: 14px 16px;
    border: 1px solid #e2e8f0;
}
.storage-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.82rem;
    margin-bottom: 8px;
}
.storage-label {
    font-weight: 700;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 6px;
}
.storage-val {
    color: #64748b;
}
.storage-progress-track {
    width: 100%;
    height: 9px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 6px;
}
.storage-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #6366f1);
    border-radius: 999px;
    transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.storage-footer {
    display: flex;
    justify-content: space-between;
    font-size: 0.74rem;
    color: #94a3b8;
    font-weight: 600;
}

/* ── Estado Último Despliegue ── */
.last-update-banner {
    background: #f8fafc;
    border-radius: var(--bento-radius-md);
    padding: 16px;
    border: 1px solid #e2e8f0;
}
.lub-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.lub-date {
    font-size: 0.76rem;
    color: #94a3b8;
    font-weight: 600;
}
.lub-package-name {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--bento-text-main);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    word-break: break-all;
}
.lub-package-name i {
    color: #2563eb;
    font-size: 1.15rem;
}
.lub-details {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 0.8rem;
    color: #64748b;
}
.lub-stat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.stat-sql {
    color: #16a34a;
    font-weight: 700;
}

/* Empty states */
.empty-state-card {
    text-align: center;
    padding: 24px 16px;
    color: var(--bento-text-muted);
}
.empty-icon-wrapper {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin: 0 auto 12px;
}
.empty-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #334155;
    margin: 0 0 4px 0;
}
.empty-text {
    font-size: 0.82rem;
    line-height: 1.45;
    margin: 0;
}

/* ── Historial Tabla ── */
.table-responsive-wrapper {
    overflow-x: auto;
}
.bento-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.bento-table th {
    padding: 12px 16px;
    text-align: left;
    font-size: 0.74rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid #f1f5f9;
}
.bento-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.bento-table tbody tr:hover {
    background: #f8fafc;
}
.cell-date {
    font-weight: 600;
    color: #334155;
    white-space: nowrap;
}
.cell-date i {
    color: #94a3b8;
    margin-right: 4px;
}
.filename-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    color: var(--bento-text-main);
}
.filename-pill i {
    color: #0284c7;
}
.backup-code {
    font-family: monospace;
    font-size: 0.78rem;
    background: #f1f5f9;
    padding: 3px 8px;
    border-radius: 6px;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.text-muted-tag {
    color: #94a3b8;
    font-size: 0.8rem;
}

.empty-history-box {
    text-align: center;
    padding: 48px 20px;
    color: #94a3b8;
}
.empty-history-box .empty-icon {
    font-size: 2.8rem;
    color: #cbd5e1;
    margin-bottom: 10px;
}
.empty-history-box h4 {
    font-size: 1.05rem;
    font-weight: 700;
    color: #334155;
    margin: 0 0 6px 0;
}
.empty-history-box p {
    font-size: 0.88rem;
    margin: 0;
}

.spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}
@keyframes spin {
    100% { transform: rotate(360deg); }
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .card-dropzone-section { grid-column: span 12; }
    .bento-sidebar-cards { grid-column: span 12; }
}
</style>

<!-- ========================================================
     JAVASCRIPT INTERACTIVO - DRAG & DROP Y AJAX
     ======================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzoneContainer');
    const inputZip = document.getElementById('inputZip');
    const btnInstalar = document.getElementById('btnInstalar');
    const btnQuitarArchivo = document.getElementById('btnQuitarArchivo');
    const fileNameText = document.getElementById('fileNameText');
    const fileInfoBadge = document.getElementById('fileInfoBadge');
    const dzTitle = document.getElementById('dzTitle');
    const dzSubtitle = document.getElementById('dzSubtitle');
    const formActualizar = document.getElementById('formActualizar');
    const zipEnabled = <?= $zip_enabled ? 'true' : 'false' ?>;

    if (!zipEnabled) {
        btnInstalar.disabled = true;
        btnInstalar.title = "La extensión ZipArchive de PHP está deshabilitada en este servidor.";
        return;
    }

    // Click en la zona para abrir el explorador de archivos
    dropzone.addEventListener('click', (e) => {
        if (e.target.closest('#btnQuitarArchivo')) return;
        inputZip.click();
    });

    // Drag & Drop visual
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            inputZip.files = files;
            procesarArchivoSeleccionado(files[0]);
        }
    });

    inputZip.addEventListener('change', function() {
        if (this.files.length > 0) {
            procesarArchivoSeleccionado(this.files[0]);
        }
    });

    if (btnQuitarArchivo) {
        btnQuitarArchivo.addEventListener('click', function(e) {
            e.stopPropagation();
            resetDropzone();
        });
    }

    function resetDropzone() {
        inputZip.value = '';
        fileInfoBadge.style.display = 'none';
        dzTitle.textContent = 'Arrastra tu archivo .ZIP aquí';
        dzSubtitle.textContent = 'o haz clic en cualquier lugar de este recuadro para seleccionarlo de tu laptop.';
        btnInstalar.disabled = true;
    }

    function procesarArchivoSeleccionado(file) {
        if (!file.name.toLowerCase().endsWith('.zip')) {
            Swal.fire({
                icon: 'warning',
                title: 'Formato no válido',
                text: 'El archivo seleccionado debe ser un paquete comprimido .ZIP de actualización.',
                confirmButtonColor: '#2563eb'
            });
            resetDropzone();
            return;
        }

        const sizeFormatted = file.size > 1024 * 1024 
            ? (file.size / (1024 * 1024)).toFixed(2) + ' MB'
            : (file.size / 1024).toFixed(1) + ' KB';

        fileNameText.textContent = `${file.name} (${sizeFormatted})`;
        fileInfoBadge.style.display = 'flex';
        dzTitle.textContent = '¡Paquete listo para desplegar!';
        dzSubtitle.textContent = 'Presiona el botón inferior para iniciar el proceso de instalación en caliente.';

        btnInstalar.disabled = false;
    }

    // Envío AJAX del formulario con SweetAlert2
    formActualizar.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!inputZip.files || inputZip.files.length === 0) return;

        const file = inputZip.files[0];

        Swal.fire({
            title: '¿Instalar actualización?',
            html: `
                <div style="text-align: left; font-size: 0.9rem; background: #f8fafc; padding: 14px 18px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 10px;">
                    <div style="margin-bottom: 6px;"><b>📦 Paquete:</b> ${file.name}</div>
                    <div style="margin-bottom: 6px;"><b>🛡️ Respaldo:</b> Se creará un punto de rollback automático</div>
                    <div><b>⚡ Tipo:</b> Despliegue en caliente sin interrumpir el servicio</div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ti ti-rocket"></i> Sí, desplegar ahora',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                ejecutarInstalacion();
            }
        });
    });

    function ejecutarInstalacion() {
        const formData = new FormData(formActualizar);

        Swal.fire({
            title: 'Desplegando actualización...',
            html: `
                <div style="padding: 10px 0;">
                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 16px;">
                        Respaldando archivos y aplicando cambios en caliente en el servidor.
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 8px; text-align: left; font-size: 0.85rem; color: #475569;">
                        <div><i class="ti ti-check" style="color:#10b981;"></i> Verificando integridad del paquete...</div>
                        <div><i class="ti ti-loader-2 spin" style="color:#2563eb;"></i> Reemplazando archivos y ejecutando migraciones...</div>
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('<?= URLROOT ?>/actualizaciones/aplicar', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let extraHtml = `
                    <div style="text-align: left; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 14px 16px; border-radius: 12px; margin-top: 10px; font-size: 0.88rem;">
                        <div style="margin-bottom: 6px;"><i class="ti ti-files"></i> <b>Archivos actualizados:</b> ${data.archivos_actualizados_count}</div>
                        <div style="margin-bottom: 6px;"><i class="ti ti-shield-check"></i> <b>Rollback guardado:</b> <code>${data.backup_rollback}</code></div>
                `;

                if (data.sql_ejecutado) {
                    extraHtml += `<div style="color: #15803d; font-weight: 700; margin-top: 6px;"><i class="ti ti-database-check"></i> Base de datos actualizada con éxito.</div>`;
                }

                extraHtml += `</div>`;

                Swal.fire({
                    icon: 'success',
                    title: '¡Despliegue Exitoso!',
                    html: extraHtml,
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo aplicar la actualización',
                    text: data.message || 'Ocurrió un error inesperado al procesar el archivo ZIP.',
                    confirmButtonColor: '#2563eb'
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Error de comunicación',
                text: 'Hubo una falla de red al enviar el paquete al servidor.',
                confirmButtonColor: '#2563eb'
            });
        });
    }
});
</script>
