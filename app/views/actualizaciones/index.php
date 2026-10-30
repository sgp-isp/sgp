<div class="sgp-updates-wrapper" style="padding: 24px 28px 60px; width: 100%; max-width: 100%; box-sizing: border-box; margin: 0;">
    
    <!-- ========================================================
         1. HERO BANNER INSTITUCIONAL SGP / ISPEB
         ======================================================== -->
    <div class="sgp-hero-banner">
        <div class="sgp-hero-content">
            <div class="sgp-hero-left">
                <div class="sgp-hero-icon">
                    <i class="ti ti-refresh-dot"></i>
                </div>
                <div class="sgp-hero-texts">
                    <div class="sgp-hero-tags">
                        <span class="sgp-tag-ispeb"><i class="ti ti-building-hospital"></i> ISPEB &bull; SALUD BOLÍVAR</span>
                        <span class="sgp-tag-status">
                            <span class="status-dot"></span> Red Local En Línea
                        </span>
                    </div>
                    <h1 class="sgp-hero-title">Actualizaciones del Sistema</h1>
                    <p class="sgp-hero-desc">
                        Despliegue y sincronización de código y base de datos en caliente a través de la red local, con respaldo automático preventivo.
                    </p>
                </div>
            </div>

            <div class="sgp-hero-right">
                <a href="<?= URLROOT ?>/actualizaciones/exportarBd" class="sgp-hero-btn secondary" title="Descarga una copia completa de seguridad de la base de datos de este equipo">
                    <i class="ti ti-database-export" style="color: #60a5fa;"></i>
                    <div class="btn-text">
                        <span class="main-txt">Respaldar BD (.SQL)</span>
                        <span class="sub-txt">Copia rápida preventiva</span>
                    </div>
                </a>
                <a href="<?= URLROOT ?>/actualizaciones/empaquetar" class="sgp-hero-btn" title="Genera un paquete ZIP limpio para enviar a otras máquinas">
                    <i class="ti ti-package-export"></i>
                    <div class="btn-text">
                        <span class="main-txt">Empaquetar Código (.ZIP)</span>
                        <span class="sub-txt">Código fuente limpio</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================
         2. ALERTAS DEL SISTEMA (ZIPARCHIVE Y FLASH)
         ======================================================== -->
    <?php if (!$zip_enabled): ?>
        <div class="sgp-alert sgp-alert-danger">
            <div class="alert-icon"><i class="ti ti-alert-triangle-filled"></i></div>
            <div class="alert-body">
                <h4 class="alert-title">Acción Requerida en la PC Servidor: Extensión ZipArchive desactivada</h4>
                <p class="alert-desc">
                    El servidor web en esta máquina tiene deshabilitado el módulo <code>zip</code> de PHP. Para habilitarlo:
                </p>
                <div class="alert-steps">
                    <span>1. En la PC abre <code>C:\xampp\php\php.ini</code></span>
                    <span>2. Busca <code>;extension=zip</code> y quita el punto y coma (<code>extension=zip</code>)</span>
                    <span>3. En el panel de XAMPP haz clic en <strong>Stop</strong> y luego <strong>Start</strong> en Apache</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('error')): ?>
        <div class="sgp-alert sgp-alert-danger">
            <div class="alert-icon"><i class="ti ti-circle-x-filled"></i></div>
            <div class="alert-body">
                <h4 class="alert-title">Aviso del Sistema</h4>
                <p class="alert-desc"><?= Session::getFlash('error') ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (Session::hasFlash('success')): ?>
        <div class="sgp-alert sgp-alert-success">
            <div class="alert-icon"><i class="ti ti-circle-check-filled"></i></div>
            <div class="alert-body">
                <h4 class="alert-title">Operación Completada</h4>
                <p class="alert-desc"><?= Session::getFlash('success') ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================================
         3. TARJETAS KPI INSTITUCIONALES (DIAGNÓSTICO RÁPIDO)
         ======================================================== -->
    <div class="sgp-kpi-grid">
        <!-- KPI 1: Motor PHP -->
        <div class="sgp-kpi-card kp-blue">
            <div class="kpi-top">
                <span class="kpi-label">Motor de Ejecución</span>
                <div class="kpi-icon"><i class="ti ti-brand-php"></i></div>
            </div>
            <div class="kpi-value">PHP <?= htmlspecialchars($php_version) ?></div>
            <div class="kpi-sub"><i class="ti ti-shield-check"></i> Entorno Apache XAMPP</div>
        </div>

        <!-- KPI 2: Módulo Zip -->
        <div class="sgp-kpi-card <?= $zip_enabled ? 'kp-green' : 'kp-red' ?>">
            <div class="kpi-top">
                <span class="kpi-label">Módulo ZipArchive</span>
                <div class="kpi-icon"><i class="ti ti-file-zip"></i></div>
            </div>
            <div class="kpi-value"><?= $zip_enabled ? 'Disponible' : 'Desactivado' ?></div>
            <div class="kpi-sub">
                <?= $zip_enabled ? '<i class="ti ti-check"></i> Listo para descomprimir' : '<i class="ti ti-x"></i> Habilitar en php.ini' ?>
            </div>
        </div>

        <!-- KPI 3: IP del Servidor -->
        <div class="sgp-kpi-card kp-indigo">
            <div class="kpi-top">
                <span class="kpi-label">Dirección IP Servidor</span>
                <div class="kpi-icon"><i class="ti ti-network"></i></div>
            </div>
            <div class="kpi-value ip-val"><?= htmlspecialchars($server_ip) ?></div>
            <div class="kpi-sub"><i class="ti ti-wifi"></i> Conexión Red Institucional</div>
        </div>

        <!-- KPI 4: Almacenamiento -->
        <div class="sgp-kpi-card kp-amber">
            <div class="kpi-top">
                <span class="kpi-label">Espacio en Disco</span>
                <div class="kpi-icon"><i class="ti ti-device-floppy"></i></div>
            </div>
            <div class="kpi-value"><?= htmlspecialchars($espacio_libre) ?></div>
            <div class="kpi-sub">Libres de <?= htmlspecialchars($espacio_total) ?> (<?= 100 - (int)$espacio_porcentaje ?>% disp.)</div>
            <div class="kpi-progress">
                <div class="kpi-progress-fill" style="width: <?= min(100, max(5, (int)$espacio_porcentaje)) ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- ========================================================
         4. SECCIÓN PRINCIPAL: DROPZONE + ÚLTIMO ESTADO
         ======================================================== -->
    <div class="sgp-layout-grid">

        <!-- LADO IZQUIERDO: ZONA DE DESPLIEGUE -->
        <div class="sgp-card dropzone-card">
            <div class="card-head">
                <div class="head-icon blue">
                    <i class="ti ti-cloud-upload"></i>
                </div>
                <div class="head-text">
                    <h3 class="head-title">Cargar Paquete de Actualización</h3>
                    <p class="head-sub">Sube el archivo <code>actualizacion_SGP.zip</code> generado desde tu laptop</p>
                </div>
            </div>

            <form id="formActualizar" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="_csrf" value="<?= $csrf_token ?>">
                
                <div id="dropzoneContainer" class="sgp-dropzone <?= !$zip_enabled ? 'is-disabled' : '' ?>" style="cursor: pointer;" onclick="if(!event.target.closest('#btnQuitarArchivo') && <?= $zip_enabled ? 'true' : 'false' ?>) { var inp = document.getElementById('inputZip'); if(inp) inp.click(); }">
                    <input type="file" id="inputZip" name="paquete_zip" accept=".zip" style="display: none;" <?= !$zip_enabled ? 'disabled' : '' ?>>
                    
                    <div class="dz-icon" id="dzIconWrapper">
                        <i class="ti ti-file-zip"></i>
                    </div>

                    <h4 id="dzTitle" class="dz-title">Arrastra tu archivo .ZIP aquí</h4>
                    <p id="dzSubtitle" class="dz-sub">
                        o haz clic para examinarlo en tu laptop.
                    </p>

                    <div class="dz-pills">
                        <span class="dz-pill"><i class="ti ti-file-check"></i> Formato: .ZIP</span>
                        <span class="dz-pill"><i class="ti ti-upload"></i> Límite máx: <?= htmlspecialchars($max_upload_size) ?></span>
                    </div>

                    <div id="fileInfoBadge" class="file-loaded-box" style="display: none;">
                        <div class="fl-icon"><i class="ti ti-circle-check-filled"></i></div>
                        <div class="fl-details">
                            <span id="fileNameText" class="fl-name">actualizacion_SGP.zip</span>
                            <span class="fl-msg">Paquete verificado &bull; Listo para desplegar</span>
                        </div>
                        <button type="button" id="btnQuitarArchivo" class="btn-clear-file" title="Quitar archivo">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>

                <!-- GARANTÍAS DE DESPLIEGUE -->
                <div class="sgp-safe-box">
                    <div class="safe-item">
                        <i class="ti ti-shield-check"></i>
                        <span><strong>Rollback Preventivo:</strong> El sistema empaqueta automáticamente una copia de seguridad antes de sustituir archivos.</span>
                    </div>
                    <div class="safe-item">
                        <i class="ti ti-database-cog"></i>
                        <span><strong>Migración SQL:</strong> Ejecuta consultas de base de datos incluidas en <code>update.sql</code> sin tocar tus datos actuales.</span>
                    </div>
                </div>

                <!-- BOTÓN PRINCIPAL -->
                <button type="submit" id="btnInstalar" class="btn-sgp-deploy" disabled>
                    <i class="ti ti-rocket" id="btnIconRocket"></i>
                    <span id="btnTextDeploy">Instalar y Desplegar Actualización</span>
                </button>
            </form>
        </div>

        <!-- LADO DERECHO: ESTADO DEL SISTEMA / ÚLTIMO DESPLIEGUE -->
        <div class="sgp-card status-card">
            <div class="card-head">
                <div class="head-icon amber">
                    <i class="ti ti-history"></i>
                </div>
                <div class="head-text">
                    <h3 class="head-title">Estado de la Instalación</h3>
                    <p class="head-sub">Información de la última actualización aplicada</p>
                </div>
            </div>

            <?php if ($ultima_actualizacion): ?>
                <div class="deployed-status-banner">
                    <div class="dsb-header">
                        <span class="badge-active"><i class="ti ti-circle-check-filled"></i> Versión Activa</span>
                        <span class="dsb-time"><?= htmlspecialchars($ultima_actualizacion['fecha']) ?></span>
                    </div>
                    <div class="dsb-package">
                        <i class="ti ti-package"></i>
                        <span><?= htmlspecialchars($ultima_actualizacion['archivo_paquete']) ?></span>
                    </div>
                    <div class="dsb-grid">
                        <div class="dsb-item">
                            <span class="lbl">Archivos Modificados</span>
                            <span class="val"><?= (int)$ultima_actualizacion['archivos_actualizados_count'] ?></span>
                        </div>
                        <div class="dsb-item">
                            <span class="lbl">Tamaño Paquete</span>
                            <span class="val"><?= htmlspecialchars($ultima_actualizacion['tamano_kb']) ?> KB</span>
                        </div>
                        <div class="dsb-item">
                            <span class="lbl">Base de Datos</span>
                            <span class="val <?= !empty($ultima_actualizacion['sql_ejecutado']) ? 'text-success' : '' ?>">
                                <?= !empty($ultima_actualizacion['sql_ejecutado']) ? 'Actualizada' : 'Sin cambios' ?>
                            </span>
                        </div>
                        <div class="dsb-item">
                            <span class="lbl">Responsable</span>
                            <span class="val"><?= htmlspecialchars($ultima_actualizacion['usuario_responsable'] ?? 'Admin') ?></span>
                        </div>
                    </div>
                    <div class="dsb-rollback">
                        <span class="lbl"><i class="ti ti-lifebuoy"></i> Respaldo de Seguridad:</span>
                        <code><?= htmlspecialchars($ultima_actualizacion['backup_previo']) ?></code>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-deployment-state">
                    <div class="empty-icon"><i class="ti ti-sparkles"></i></div>
                    <h4>Servidor Base Preparado</h4>
                    <p>Aún no se han desplegado paquetes ZIP en este equipo. El primer despliegue que envíes desde tu laptop aparecerá registrado aquí.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- ========================================================
         5. HISTORIAL DE ACTUALIZACIONES (TABLA COMPLETA)
         ======================================================== -->
    <div class="sgp-card history-card" style="margin-top: 24px;">
        <div class="card-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="head-icon indigo">
                    <i class="ti ti-list-details"></i>
                </div>
                <div class="head-text">
                    <h3 class="head-title">Registro Histórico de Despliegues</h3>
                    <p class="head-sub">Auditoría completa de paquetes, fechas y puntos de restauración en este servidor</p>
                </div>
            </div>
            <?php if (!empty($historial) && count($historial) > 1): ?>
                <button type="button" class="btn-purge-backups" onclick="purgarRespaldos()" title="Mantiene las 5 versiones más recientes y purga las más viejas para liberar disco">
                    <i class="ti ti-trash-x"></i> Purgar Respaldos Viejos
                </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($historial)): ?>
            <div class="table-responsive">
                <table class="sgp-table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Paquete ZIP</th>
                            <th>Tamaño</th>
                            <th>Archivos</th>
                            <th>Base de Datos</th>
                            <th>Responsable</th>
                            <th>Punto de Rollback</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $item): 
                            $backupName = $item['backup_previo'] ?? '';
                            $esRollbackValido = !empty($backupName) && strpos($backupName, 'rollback_') === 0;
                        ?>
                            <tr id="row-<?= htmlspecialchars($item['id']) ?>">
                                <td class="td-date">
                                    <i class="ti ti-calendar"></i> <?= htmlspecialchars($item['fecha']) ?>
                                </td>
                                <td class="td-package">
                                    <div class="package-cell">
                                        <i class="ti ti-file-zip"></i>
                                        <span><?= htmlspecialchars($item['archivo_paquete']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($item['tamano_kb']) ?> KB</td>
                                <td>
                                    <span class="pill-badge pill-blue">
                                        <?= (int)$item['archivos_actualizados_count'] ?> archivos
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($item['sql_ejecutado'])): ?>
                                        <span class="pill-badge pill-green"><i class="ti ti-check"></i> Aplicado</span>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.82rem;">Sin cambios SQL</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($item['usuario_responsable'] ?? 'Admin') ?></td>
                                <td>
                                    <code class="code-rollback"><?= htmlspecialchars($item['backup_previo']) ?></code>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div class="row-actions-group">
                                        <?php if ($esRollbackValido): ?>
                                            <button type="button" class="btn-row-action btn-row-revert" 
                                                    onclick="revertirVersion('<?= htmlspecialchars($backupName) ?>')" 
                                                    title="Revertir el sistema a este punto exacto">
                                                <i class="ti ti-rotate-clockwise"></i> Revertir
                                            </button>
                                            <a href="<?= URLROOT ?>/actualizaciones/descargarBackup/<?= htmlspecialchars($backupName) ?>" 
                                               class="btn-row-action btn-row-download" 
                                               title="Descargar este archivo ZIP de respaldo">
                                                <i class="ti ti-download"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn-row-action btn-row-delete" 
                                                onclick="eliminarRegistro('<?= htmlspecialchars($item['id']) ?>')" 
                                                title="Eliminar este registro y su archivo de respaldo">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-history-wrapper">
                <div class="empty-art"><i class="ti ti-archive"></i></div>
                <h4>Sin historial de actualizaciones</h4>
                <p>Cada vez que envíes una actualización desde tu laptop quedará registrada con su fecha y archivos modificados.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ========================================================
     ESTILOS CSS INSTITUCIONALES (LIGHT + DARK + MOBILE)
     ======================================================== -->
<style>
/* ── Variables de Integración con el Tema SGP ── */
:root {
    --upd-primary: #162660;
    --upd-primary-hover: #1e3a8a;
    --upd-accent: #2563eb;
    --upd-bg-card: #ffffff;
    --upd-border: #e2e8f0;
    --upd-text-title: #0f172a;
    --upd-text-body: #475569;
    --upd-text-muted: #64748b;
    --upd-subtle-bg: #f8fafc;
    --upd-radius: 20px;
}

/* ── Compatibilidad Nativa con Modo Oscuro de SGP [data-theme="dark"] ── */
[data-theme="dark"] {
    --upd-bg-card: #1e293b;
    --upd-border: #334155;
    --upd-text-title: #f8fafc;
    --upd-text-body: #cbd5e1;
    --upd-text-muted: #94a3b8;
    --upd-subtle-bg: #0f172a;
}

[data-theme="dark"] .sgp-card {
    background: #1e293b !important;
    border-color: #334155 !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25) !important;
}

[data-theme="dark"] .sgp-dropzone {
    background: #0f172a !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .sgp-dropzone:hover {
    border-color: #3b82f6 !important;
    background: #1e293b !important;
}

[data-theme="dark"] .sgp-safe-box,
[data-theme="dark"] .deployed-status-banner,
[data-theme="dark"] .alert-steps {
    background: #0f172a !important;
    border-color: #334155 !important;
}

[data-theme="dark"] .sgp-kpi-card {
    background: #1e293b !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .kpi-progress {
    background: #334155 !important;
}
[data-theme="dark"] .sgp-table thead tr {
    background: #0f172a !important;
}
[data-theme="dark"] .sgp-table th {
    color: #94a3b8 !important;
    border-color: #334155 !important;
}
[data-theme="dark"] .sgp-table td {
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
[data-theme="dark"] .sgp-table tbody tr:hover {
    background: #0f172a !important;
}
[data-theme="dark"] .code-rollback,
[data-theme="dark"] .alert-steps code {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #38bdf8 !important;
}

/* ── 1. Banner Hero Institucional SGP ── */
.sgp-hero-banner {
    background: linear-gradient(135deg, #162660 0%, #1e3a8a 50%, #2563eb 100%);
    border-radius: var(--upd-radius);
    padding: 26px 32px;
    color: #ffffff;
    box-shadow: 0 12px 35px -5px rgba(22, 38, 96, 0.35);
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.sgp-hero-banner::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 85% 15%, rgba(56, 189, 248, 0.22) 0%, transparent 60%);
    pointer-events: none;
}
.sgp-hero-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 2;
    flex-wrap: wrap;
    gap: 20px;
}
.sgp-hero-left {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
    min-width: 280px;
}
.sgp-hero-right {
    flex-shrink: 0;
}
.sgp-hero-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #ffffff;
    flex-shrink: 0;
    backdrop-filter: blur(8px);
}
.sgp-hero-tags {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}
.sgp-tag-ispeb {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    padding: 3px 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: 1px solid rgba(255, 255, 255, 0.25);
}
.sgp-tag-status {
    background: rgba(16, 185, 129, 0.25);
    color: #a7f3d0;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid rgba(16, 185, 129, 0.4);
}
.status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 8px #10b981;
}
.sgp-hero-title {
    font-size: 1.85rem;
    font-weight: 800;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
    color: #ffffff;
}
.sgp-hero-desc {
    margin: 0;
    font-size: 0.92rem;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.45;
}

/* Botón Hero Empaquetar */
.sgp-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 12px 20px;
    border-radius: 14px;
    color: #ffffff;
    text-decoration: none;
    backdrop-filter: blur(10px);
    transition: all 0.25s ease;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}
.sgp-hero-btn i {
    font-size: 1.5rem;
    color: #38bdf8;
}
.sgp-hero-btn .btn-text {
    display: flex;
    flex-direction: column;
}
.sgp-hero-btn .main-txt {
    font-size: 0.92rem;
    font-weight: 800;
}
.sgp-hero-btn .sub-txt {
    font-size: 0.74rem;
    color: rgba(255, 255, 255, 0.7);
}
.sgp-hero-btn:hover {
    background: rgba(255, 255, 255, 0.22);
    color: #ffffff;
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.4);
}

/* ── 2. Alertas ── */
.sgp-alert {
    display: flex;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 22px;
    align-items: flex-start;
}
.sgp-alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
.sgp-alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
}
.alert-icon { font-size: 1.5rem; line-height: 1; flex-shrink: 0; }
.alert-title { font-size: 0.98rem; font-weight: 800; margin: 0 0 4px 0; }
.alert-desc { font-size: 0.88rem; margin: 0; line-height: 1.45; }
.alert-steps {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 10px;
    font-size: 0.84rem;
    background: rgba(255, 255, 255, 0.8);
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #fca5a5;
}
.alert-steps code {
    background: #ffffff;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
    border: 1px solid #fca5a5;
}

/* ── 3. Tarjetas KPI ── */
.sgp-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.sgp-kpi-card {
    background: var(--upd-bg-card);
    border: 1px solid var(--upd-border);
    border-radius: 16px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}
.sgp-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
}
.kpi-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.kpi-label {
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--upd-text-muted);
}
.kpi-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}
.kp-blue .kpi-icon { background: #e0f2fe; color: #0284c7; }
.kp-green .kpi-icon { background: #dcfce7; color: #16a34a; }
.kp-red .kpi-icon { background: #fee2e2; color: #dc2626; }
.kp-indigo .kpi-icon { background: #e0e7ff; color: #4f46e5; }
.kp-amber .kpi-icon { background: #fef3c7; color: #d97706; }

.kpi-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--upd-text-title);
    margin-bottom: 4px;
}
.kpi-value.ip-val {
    font-family: monospace;
    font-size: 1.15rem;
}
.kpi-sub {
    font-size: 0.78rem;
    color: var(--upd-text-muted);
    display: flex;
    align-items: center;
    gap: 4px;
}
.kpi-progress {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 999px;
    margin-top: 10px;
    overflow: hidden;
}
.kpi-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f59e0b, #ef4444);
    border-radius: 999px;
}

/* ── 4. Layout Grid Principal ── */
.sgp-layout-grid {
    display: grid;
    grid-template-columns: 7fr 5fr;
    gap: 24px;
}
.sgp-card {
    background: var(--upd-bg-card);
    border: 1px solid var(--upd-border);
    border-radius: var(--upd-radius);
    padding: 26px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
}
.card-head {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}
.head-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    color: #ffffff;
    flex-shrink: 0;
}
.head-icon.blue { background: linear-gradient(135deg, #162660, #2563eb); }
.head-icon.amber { background: linear-gradient(135deg, #d97706, #f59e0b); }
.head-icon.indigo { background: linear-gradient(135deg, #4f46e5, #6366f1); }
.head-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--upd-text-title);
    margin: 0;
}
.head-sub {
    font-size: 0.84rem;
    color: var(--upd-text-muted);
    margin: 2px 0 0 0;
}

/* ── Dropzone ── */
.upload-form { display: flex; flex-direction: column; }
.sgp-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 16px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    background: var(--upd-subtle-bg);
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
}
.sgp-dropzone:hover {
    border-color: #2563eb;
    background: rgba(37, 99, 235, 0.04);
    transform: translateY(-2px);
}
.sgp-dropzone.dragover {
    border-color: #2563eb !important;
    background: rgba(37, 99, 235, 0.08) !important;
    box-shadow: 0 0 0 6px rgba(37, 99, 235, 0.15);
}
.sgp-dropzone.is-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.dz-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #e0f2fe;
    color: #0284c7;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    margin-bottom: 14px;
    transition: transform 0.25s;
}
.sgp-dropzone:hover .dz-icon,
.sgp-dropzone.dragover .dz-icon {
    transform: scale(1.12) rotate(-5deg);
}
.dz-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--upd-text-title);
    margin: 0 0 6px 0;
}
.dz-sub {
    font-size: 0.86rem;
    color: var(--upd-text-muted);
    margin: 0 0 14px 0;
}
.dz-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}
.dz-pill {
    font-size: 0.74rem;
    font-weight: 700;
    background: var(--upd-bg-card);
    border: 1px solid var(--upd-border);
    color: var(--upd-text-muted);
    padding: 3px 10px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Badge de archivo seleccionado */
.file-loaded-box {
    background: #ecfdf5;
    border: 1px solid #6ee7b7;
    color: #065f46;
    padding: 12px 18px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 14px;
    max-width: 92%;
    animation: popIn 0.25s ease-out;
}
@keyframes popIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.fl-icon { font-size: 1.5rem; color: #10b981; line-height: 1; }
.fl-details { display: flex; flex-direction: column; text-align: left; }
.fl-name { font-weight: 800; font-size: 0.92rem; color: #065f46; word-break: break-all; }
.fl-msg { font-size: 0.76rem; color: #059669; }
.btn-clear-file {
    background: none;
    border: none;
    color: #059669;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 4px;
    margin-left: 6px;
    border-radius: 6px;
}
.btn-clear-file:hover { background: rgba(16, 185, 129, 0.2); }

/* Caja de seguridad */
.sgp-safe-box {
    background: var(--upd-subtle-bg);
    border: 1px solid var(--upd-border);
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.safe-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 0.83rem;
    color: var(--upd-text-body);
    line-height: 1.45;
}
.safe-item i {
    color: #10b981;
    font-size: 1.15rem;
    margin-top: 1px;
    flex-shrink: 0;
}

/* Botón de Despliegue */
.btn-sgp-deploy {
    width: 100%;
    padding: 15px 24px;
    border-radius: 14px;
    border: none;
    background: linear-gradient(135deg, #162660 0%, #2563eb 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 1.02rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.25s ease;
    box-shadow: 0 8px 22px rgba(22, 38, 96, 0.25);
}
.btn-sgp-deploy:not(:disabled):hover {
    background: linear-gradient(135deg, #101c48 0%, #1d4ed8 100%);
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(22, 38, 96, 0.35);
}
.btn-sgp-deploy:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

/* ── Panel de Estado de Último Despliegue ── */
.deployed-status-banner {
    background: var(--upd-subtle-bg);
    border: 1px solid var(--upd-border);
    border-radius: 14px;
    padding: 18px;
}
.dsb-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.badge-active {
    background: #dcfce7;
    color: #15803d;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.dsb-time { font-size: 0.78rem; color: var(--upd-text-muted); font-weight: 600; }
.dsb-package {
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--upd-text-title);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    word-break: break-all;
}
.dsb-package i { color: #2563eb; font-size: 1.25rem; }
.dsb-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 14px;
}
.dsb-item {
    background: var(--upd-bg-card);
    border: 1px solid var(--upd-border);
    padding: 10px 12px;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
}
.dsb-item .lbl { font-size: 0.7rem; color: var(--upd-text-muted); font-weight: 700; text-transform: uppercase; }
.dsb-item .val { font-size: 0.9rem; font-weight: 800; color: var(--upd-text-title); margin-top: 2px; }
.dsb-item .val.text-success { color: #16a34a; }
.dsb-rollback {
    font-size: 0.78rem;
    color: var(--upd-text-muted);
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.dsb-rollback code {
    background: var(--upd-bg-card);
    border: 1px solid var(--upd-border);
    padding: 4px 8px;
    border-radius: 6px;
    font-family: monospace;
    font-size: 0.76rem;
    color: #475569;
}

/* Empty States */
.empty-deployment-state {
    text-align: center;
    padding: 30px 20px;
    color: var(--upd-text-muted);
}
.empty-deployment-state .empty-icon {
    font-size: 2.2rem;
    color: #cbd5e1;
    margin-bottom: 8px;
}
.empty-deployment-state h4 {
    font-size: 1rem;
    font-weight: 800;
    color: var(--upd-text-title);
    margin: 0 0 6px 0;
}
.empty-deployment-state p { font-size: 0.85rem; margin: 0; line-height: 1.45; }

/* ── 5. Tabla de Historial ── */
.table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.sgp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}
.sgp-table th {
    padding: 12px 16px;
    text-align: left;
    font-size: 0.74rem;
    font-weight: 800;
    color: var(--upd-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 2px solid var(--upd-border);
}
.sgp-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--upd-border);
    color: var(--upd-text-body);
    vertical-align: middle;
}
.sgp-table tbody tr:hover { background: var(--upd-subtle-bg); }
.td-date { font-weight: 700; color: var(--upd-text-title); white-space: nowrap; }
.td-date i { color: var(--upd-text-muted); margin-right: 4px; }
.package-cell {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    color: var(--upd-text-title);
}
.package-cell i { color: #0284c7; }
.pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 700;
}
.pill-blue { background: #e0f2fe; color: #0369a1; }
.pill-green { background: #dcfce7; color: #15803d; }
.code-rollback {
    font-family: monospace;
    font-size: 0.76rem;
    background: var(--upd-subtle-bg);
    border: 1px solid var(--upd-border);
    padding: 3px 8px;
    border-radius: 6px;
    color: var(--upd-text-muted);
}
.empty-history-wrapper {
    text-align: center;
    padding: 44px 20px;
    color: var(--upd-text-muted);
}
.empty-history-wrapper .empty-art { font-size: 2.8rem; color: #cbd5e1; margin-bottom: 8px; }
.empty-history-wrapper h4 { font-size: 1.05rem; font-weight: 800; color: var(--upd-text-title); margin: 0 0 4px 0; }
.empty-history-wrapper p { font-size: 0.88rem; margin: 0; }

.spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}
@keyframes spin { 100% { transform: rotate(360deg); } }

/* ── Responsive / Vista Móvil y Tablets ── */
@media (max-width: 1024px) {
    .sgp-layout-grid { grid-template-columns: 1fr; }
    .sgp-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .sgp-updates-wrapper { padding: 14px 12px 50px !important; }
    .sgp-hero-banner { padding: 20px; border-radius: 16px; }
    .sgp-hero-content { flex-direction: column; align-items: stretch; gap: 16px; }
    .sgp-hero-left { flex-direction: column; align-items: flex-start; gap: 14px; }
    .sgp-hero-title { font-size: 1.5rem; }
    .sgp-hero-desc { font-size: 0.86rem; }
    .sgp-hero-btn { justify-content: center; width: 100%; }
    .sgp-kpi-grid { grid-template-columns: 1fr; gap: 12px; }
    .sgp-card { padding: 18px; border-radius: 16px; }
    .sgp-dropzone { padding: 30px 14px; }
}

/* ── Botones de Acción en Historial y Header ── */
.sgp-hero-btn.secondary {
    background: rgba(255, 255, 255, 0.08);
}
.sgp-hero-btn.secondary:hover {
    background: rgba(255, 255, 255, 0.18);
}
.btn-purge-backups {
    background: var(--upd-subtle-bg);
    border: 1px solid var(--upd-border);
    color: var(--upd-text-muted);
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-purge-backups:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #dc2626;
}
.row-actions-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-row-action {
    border: 1px solid var(--upd-border);
    background: var(--upd-bg-card);
    padding: 6px 10px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    transition: all 0.15s;
    line-height: 1;
}
.btn-row-revert {
    color: #b91c1c;
    border-color: #fecaca;
    background: #fff1f2;
}
.btn-row-revert:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}
.btn-row-download {
    color: #0284c7;
    border-color: #bae6fd;
    background: #f0f9ff;
}
.btn-row-download:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
}
.btn-row-delete {
    color: #64748b;
    border-color: var(--upd-border);
}
.btn-row-delete:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
}
</style>

<!-- ========================================================
     JAVASCRIPT: DRAG & DROP, VALIDACIÓN Y ENVÍO AJAX
     ======================================================== -->
<script>
(function initActualizaciones() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActualizaciones);
        return;
    }
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

    // Click para abrir explorador
    dropzone.addEventListener('click', (e) => {
        if (e.target.closest('#btnQuitarArchivo')) return;
        inputZip.click();
    });

    // Drag & Drop
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
            procesarArchivo(files[0]);
        }
    });

    inputZip.addEventListener('change', function() {
        if (this.files.length > 0) {
            procesarArchivo(this.files[0]);
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
        dzSubtitle.textContent = 'o haz clic para examinarlo en tu laptop.';
        btnInstalar.disabled = true;
    }

    function procesarArchivo(file) {
        if (!file.name.toLowerCase().endsWith('.zip')) {
            Swal.fire({
                icon: 'warning',
                title: 'Formato no válido',
                text: 'El archivo seleccionado debe ser un paquete comprimido .ZIP de actualización.',
                confirmButtonColor: '#162660'
            });
            resetDropzone();
            return;
        }

        // Límite de subida en bytes
        const maxAllowedMb = <?= (int)filter_var(ini_get('upload_max_filesize'), FILTER_SANITIZE_NUMBER_INT) ?: 40 ?>;
        const maxAllowedBytes = maxAllowedMb * 1024 * 1024;

        if (file.size > maxAllowedBytes) {
            const sizeInMb = (file.size / (1024 * 1024)).toFixed(1);
            Swal.fire({
                icon: 'error',
                title: 'Archivo demasiado pesado',
                html: `El paquete seleccionado pesa <b>${sizeInMb} MB</b> y supera el límite de subida del servidor (<b>${maxAllowedMb} MB</b>).`,
                confirmButtonColor: '#162660'
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
        dzSubtitle.textContent = 'Presiona el botón inferior para instalar la actualización en caliente.';

        btnInstalar.disabled = false;
    }

    // Envío AJAX con SweetAlert2
    formActualizar.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!inputZip.files || inputZip.files.length === 0) return;

        const file = inputZip.files[0];

        Swal.fire({
            title: '¿Instalar actualización?',
            html: `
                <div style="text-align: left; font-size: 0.9rem; background: rgba(0,0,0,0.03); padding: 14px 18px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.08); margin-top: 10px;">
                    <div style="margin-bottom: 6px;"><b>📦 Paquete:</b> ${file.name}</div>
                    <div style="margin-bottom: 6px;"><b>🛡️ Respaldo:</b> Se creará un punto de rollback automático</div>
                    <div><b>⚡ Despliegue:</b> En caliente, sin interrumpir el servicio</div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#162660',
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
                        <div><i class="ti ti-loader-2 spin" style="color:#2563eb;"></i> Reemplazando archivos y ejecutando migraciones SQL...</div>
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
                    confirmButtonColor: '#162660',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo aplicar la actualización',
                    text: data.message || 'Ocurrió un error inesperado al procesar el archivo ZIP.',
                    confirmButtonColor: '#162660'
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Error de comunicación',
                text: 'Hubo una falla de red al enviar el paquete al servidor. Verifica que el archivo no supere el tamaño permitido.',
                confirmButtonColor: '#162660'
            });
        });
    }

    // CSRF Token para acciones AJAX
    const csrfToken = document.querySelector('input[name="_csrf"]')?.value || '<?= $csrf_token ?>';

    // ── ACCIÓN: REVERTIR A UNA VERSIÓN ANTERIOR (ROLLBACK) ──
    window.revertirVersion = function(backupName) {
        if (!backupName) return;

        Swal.fire({
            title: '¿Revertir a esta versión anterior?',
            html: `
                <div style="text-align: left; font-size: 0.9rem; background: #fff1f2; border: 1px solid #fecaca; color: #991b1b; padding: 14px 18px; border-radius: 12px; margin-top: 10px;">
                    <div style="margin-bottom: 6px;"><i class="ti ti-alert-triangle"></i> <b>Atención:</b> Los archivos del sistema serán restaurados al estado exacto guardado en este respaldo.</div>
                    <div><b>Punto de rollback:</b> <code>${backupName}</code></div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ti ti-rotate-clockwise"></i> Sí, revertir ahora',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Restaurando versión...',
                    text: 'Extrayendo copia de seguridad y aplicando cambios en caliente...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData();
                formData.append('_csrf', csrfToken);
                formData.append('backup_name', backupName);

                fetch('<?= URLROOT ?>/actualizaciones/revertir', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Restauración Completada!',
                            text: data.message,
                            confirmButtonColor: '#162660'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al revertir',
                            text: data.message || 'No se pudo completar la restauración.',
                            confirmButtonColor: '#162660'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'Falla al comunicarse con el servidor.',
                        confirmButtonColor: '#162660'
                    });
                });
            }
        });
    };

    // ── ACCIÓN: ELIMINAR REGISTRO Y ARCHIVO DE RESPALDO FÍSICO ──
    window.eliminarRegistro = function(id) {
        if (!id) return;

        Swal.fire({
            title: '¿Eliminar registro?',
            text: 'Se eliminará la entrada del historial y su archivo ZIP de respaldo físico en el servidor.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('_csrf', csrfToken);
                formData.append('id', id);

                fetch('<?= URLROOT ?>/actualizaciones/eliminar', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const tr = document.getElementById('row-' + id);
                        if (tr) {
                            tr.style.transition = 'all 0.3s ease';
                            tr.style.opacity = '0';
                            tr.style.transform = 'translateX(20px)';
                            setTimeout(() => tr.remove(), 300);
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#162660'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al procesar la solicitud.' });
                });
            }
        });
    };

    // ── ACCIÓN: PURGAR RESPALDOS ANTIGUOS ──
    window.purgarRespaldos = function() {
        Swal.fire({
            title: '¿Purgar respaldos antiguos?',
            text: 'Se conservarán los 5 despliegues más recientes y se eliminarán los archivos ZIP de respaldo más antiguos para liberar espacio en disco.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#162660',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ti ti-trash-x"></i> Sí, purgar ahora',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Purgando archivos...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData();
                formData.append('_csrf', csrfToken);

                fetch('<?= URLROOT ?>/actualizaciones/purgarRespaldos', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Respaldos purgados!',
                            text: data.message,
                            confirmButtonColor: '#162660'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#162660'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al contactar con el servidor.' });
                });
            }
        });
    };
})();
</script>
