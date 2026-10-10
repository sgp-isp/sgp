<?php
/**
 * Vista: Bitácora de Auditoría — SGP Lifecycle 
 */
$kpis = $data['kpis'] ?? [];
$totalActivos = $kpis['total_activos'] ?? $total_logs;
$totalHist = $kpis['total_historico'] ?? 0;
$hoy = $kpis['hoy'] ?? 0;
$semana = $kpis['semana'] ?? 0;
$ultimaPurga = $kpis['ultima_purga'] ?? null;
$proximaPurga = $ultimaPurga
    ? date('d/m/Y', strtotime($ultimaPurga . ' +7 days'))
    : 'Sin registro';

function traducir_accion($accion)
{
    // [SGP-FIX-BITACORA-TRADUCCION] Lista ampliada para cubrir los códigos
    // que el sistema realmente registra (antes solo traducía ~20 de los
    // más de 35 códigos distintos usados en los controladores, dejando el
    // resto en SNAKE_CASE crudo en el filtro y en la tabla).
    static $traducciones_accion = [
    // Autenticación
    'LOGIN'                          => 'Inicio de Sesión',
    'LOGOUT'                         => 'Cierre de Sesión',
    'RESET_PASSWORD'                 => 'Contraseña Restablecida (Admin)',
    'CHANGE_PASSWORD'                => 'Contraseña Cambiada',
    'RECUPERACION_PASSWORD_EXITOSA'  => 'Recuperación de Contraseña Exitosa',
    'RESET_PIN'                      => 'PIN de Asistencia Restablecido',
    'RESET_PIN_KIOSCO'               => 'PIN de Kiosco Restablecido',
    'UPDATE_SECURITY_QUESTIONS'      => 'Preguntas de Seguridad Actualizadas',
    // Usuarios
    'CREATE_USER'                    => 'Usuario Creado',
    'UPDATE_USER'                    => 'Usuario Modificado',
    'DELETE_USER'                    => 'Usuario Eliminado',
    'DELETE_USER_PERMANENT'          => 'Usuario Eliminado Permanentemente',
    'TOGGLE_USER_STATUS'             => 'Estado de Usuario Alternado',
    'UPDATE_PROFILE'                 => 'Perfil Actualizado',
    // Pasantes
    'CREATE_PASANTE'                 => 'Pasante Registrado',
    'UPDATE_PASANTE'                 => 'Pasante Modificado',
    'UPDATE_PASANTE_DATA'            => 'Datos de Pasante Actualizados',
    'DELETE_PASANTE'                 => 'Pasante Eliminado',
    'CHANGE_PASANTE_STATUS'          => 'Estado de Pasantía Actualizado',
    // Asistencias
    'MARCAR_ASISTENCIA_KIOSCO'       => 'Asistencia Marcada (Kiosco)',
    'REGISTRAR_ASISTENCIA_MANUAL'    => 'Asistencia Registrada Manualmente',
    'ACTUALIZAR_ASISTENCIA_MANUAL'   => 'Asistencia Manual Actualizada',
    'REGISTRAR_ASISTENCIA_MASIVA'    => 'Asistencia Masiva Registrada',
    'ANULAR_ASISTENCIA'              => 'Asistencia Anulada',
    // Evaluaciones
    'CREATE_EVALUACION'              => 'Evaluación Creada',
    'UPDATE_EVALUACION'              => 'Evaluación Modificada',
    'DELETE_EVALUACION'              => 'Evaluación Eliminada',
    'CREAR_EVALUACION'               => 'Evaluación Creada',
    'ACTUALIZAR_EVALUACION'          => 'Evaluación Actualizada',
    'ELIMINAR_EVALUACION'            => 'Evaluación Eliminada',
    // Exámenes Rápidos
    'CREATE_EXAMEN'                  => 'Examen Creado',
    'TOGGLE_EXAMEN'                  => 'Estado de Examen Alternado',
    'DELETE_EXAMEN'                  => 'Examen Eliminado',
    'ENVIO_EXAMEN'                   => 'Examen Enviado (Pasante)',
    'REVISAR_EXAMEN'                 => 'Examen Revisado',
    'EDITAR_PUNTOS_PREGUNTA'         => 'Puntos de Pregunta Editados',
    'DELETE_INTENTO'                 => 'Intento de Examen Eliminado',
    // Permisos
    'PERMISO_MODIFICADO'             => 'Permiso de Usuario Modificado',
    'PERMISOS_RESET'                 => 'Permisos Restablecidos al Rol',
    // Configuración / Feriados
    'AGREGAR_FERIADO'                => 'Feriado Agregado',
    'EDITAR_FERIADO'                 => 'Feriado Editado',
    'ELIMINAR_FERIADO'               => 'Feriado Eliminado',
    'CONFIG_LABORABLES'              => 'Configuración de Días Laborables',
    'MANTENIMIENTO_SESIONES'         => 'Limpieza de Sesiones',
    'MANTENIMIENTO_BITACORA'         => 'Purga de Bitácora',
    // Sistema
    'AUDIT_PURGE'                    => 'Purga de Bitácora Ejecutada',
    'EXPORT_CSV'                     => 'Exportación de Datos CSV',
    'UPDATE_CONFIG'                  => 'Configuración del Sistema Actualizada',
    ];
    return $traducciones_accion[strtoupper($accion)] ?? $accion;
}

function traducir_tabla($tabla)
{
    static $traducciones_tabla = [
    'usuarios' => 'Usuarios',
    'datos_personales' => 'Datos de Usuario',
    'pasantes' => 'Pasantes',
    'datos_pasante' => 'Datos de Pasante',
    'bitacora' => 'Bitácora',
    'evaluaciones' => 'Evaluaciones',
    'asistencias' => 'Asistencias',
    'configuracion' => 'Configuración',
    'asignaciones' => 'Asignaciones'
    ];
    return $traducciones_tabla[strtolower($tabla)] ?? $tabla;
}
?>

<!-- Link al CSS específico de bitácora -->
<link rel="stylesheet" href="<?= URLROOT ?>/css/bitacora.css">

<style>
    /* ── KPI Grid ──────────────────────────────────────────── */
    .bita-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 18px;
    }

    @media (max-width: 900px) {
        .bita-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 540px) {
        .bita-kpi-grid {
            grid-template-columns: 1fr;
        }
    }

    .bita-kpi {
        background: #fff;
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 1px 6px rgba(30, 58, 138, 0.07);
        border: 1px solid #e8eef6;
        transition: transform 0.18s, box-shadow 0.18s;
    }

    .bita-kpi:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30, 58, 138, 0.1);
    }

    .bita-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .bita-kpi-icon.blue {
        background: #eff6ff;
        color: #2563eb;
    }

    .bita-kpi-icon.violet {
        background: #f5f3ff;
        color: #7c3aed;
    }

    .bita-kpi-icon.green {
        background: #f0fdf4;
        color: #16a34a;
    }

    .bita-kpi-icon.amber {
        background: #fffbeb;
        color: #d97706;
    }

    .bita-kpi-icon.slate {
        background: #f8fafc;
        color: #475569;
    }

    .bita-kpi-val {
        font-size: 1.55rem;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
    }

    .bita-kpi-label {
        font-size: 0.74rem;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 3px;
    }

    /* ── Mantenimiento Panel ───────────────────────────────── */
    .maint-panel {
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        margin-bottom: 18px;
        overflow: hidden;
    }

    .maint-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        cursor: pointer;
        user-select: none;
        background: linear-gradient(135deg, #172554, #1e3a8a);
    }

    .maint-header:hover {
        background: linear-gradient(135deg, #1e3a8a, #2563eb);
    }

    .maint-header h3 {
        color: #fff;
        font-size: 0.9rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .maint-chevron {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1rem;
        transition: transform 0.2s;
    }

    .maint-body {
        display: none;
        padding: 20px;
    }

    .maint-body.open {
        display: block;
    }

    .maint-grid {
        display: grid;
        grid-template-columns: 1fr 1fr auto auto;
        gap: 12px;
        align-items: end;
    }

    @media (max-width: 700px) {
        .maint-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .maint-field label {
        display: block;
        font-size: 0.74rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .maint-field input[type=number] {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
        transition: border-color 0.15s;
    }

    .maint-field input[type=number]:focus {
        outline: none;
        border-color: #2563eb;
    }

    .btn-purge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 18px;
        border: none;
        border-radius: 9px;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.85rem;
        transition: all 0.18s;
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: #fff;
        box-shadow: 0 3px 10px rgba(220, 38, 38, 0.25);
    }

    .btn-purge:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(220, 38, 38, 0.3);
    }

    .btn-export-hist {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 10px 18px;
        border: none;
        border-radius: 9px;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.85rem;
        transition: all 0.18s;
        background: #f1f5f9;
        color: #475569;
        border: 1.5px solid #e2e8f0;
    }

    .btn-export-hist:hover {
        background: #e2e8f0;
    }

    .maint-info {
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .btn-period-purge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 9px;
        background: #ffffff;
        color: #374151;
        font-size: 0.81rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        transition: all .18s;
    }
    .btn-period-purge:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }
    .btn-period-purge--warn {
        border-color: #fecdd3;
        color: #e11d48;
        background: #fff1f2;
    }
    .btn-period-purge--warn:hover {
        background: #ffe4e6;
        border-color: #fda4af;
    }
</style>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">
    <!-- BANNER -->
    <style>
        /* Estilos responsivos del banner Bitácora */
        @media (max-width: 991px) {
            .dashboard-banner {
                flex-direction: column !important;
                align-items: flex-start !important;
                padding: 24px 20px !important;
                gap: 16px !important;
                height: auto !important;
            }

            .dashboard-banner .welcome-stats {
                display: none !important;
            }

            .dashboard-banner .banner-actions {
                width: 100% !important;
                margin-left: 0 !important;
            }

            .dashboard-banner button {
                width: 100% !important;
                justify-content: center !important;
            }
        }

        .banner-actions {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: auto;
        }
    </style>
    <div class="welcome-banner dashboard-banner">
        <div class="welcome-icon">
            <i class="ti ti-file-analytics"></i>
        </div>
        <div class="welcome-content">
            <div class="welcome-text">
                <h1 class="welcome-title">Bitácora de Auditoría</h1>
                <p class="welcome-subtitle">
                    <i class="ti ti-shield-lock"></i>
                    <span>Registro de Actividades</span>
                    <span class="subtitle-separator">-</span>
                    <span>Ciclo de Vida v2</span>
                </p>
            </div>
        </div>

        <div class="banner-actions">
            <!-- Estadística (Se oculta en móvil) -->
            <div class="welcome-stats" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="ti ti-database" style="font-size: 1.1rem; opacity: 0.8;"></i>
                <span><?= number_format($totalActivos) ?></span> registros activos
            </div>
            <!-- Botón Exportar alineado al final -->
            <button onclick="exportLogs()" class="btn btn-stitch-gold"
                style="white-space: nowrap; display: flex; align-items: center; gap: 8px;">
                <i class="ti ti-file-download"></i> Exportar CSV
            </button>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="bita-kpi-grid">
        <div class="bita-kpi">
            <div class="bita-kpi-icon blue"><i class="ti ti-database"></i></div>
            <div>
                <div class="bita-kpi-val"><?= number_format($totalActivos) ?></div>
                <div class="bita-kpi-label">Registros Activos</div>
            </div>
        </div>
        <div class="bita-kpi">
            <div class="bita-kpi-icon green"><i class="ti ti-calendar-event"></i></div>
            <div>
                <div class="bita-kpi-val"><?= number_format($hoy) ?></div>
                <div class="bita-kpi-label">Eventos Hoy</div>
            </div>
        </div>
        <div class="bita-kpi">
            <div class="bita-kpi-icon violet"><i class="ti ti-chart-bar"></i></div>
            <div>
                <div class="bita-kpi-val"><?= number_format($semana) ?></div>
                <div class="bita-kpi-label">Últimos 7 días</div>
            </div>
        </div>
        <div class="bita-kpi">
            <div class="bita-kpi-icon amber"><i class="ti ti-archive"></i></div>
            <div>
                <div class="bita-kpi-val"><?= number_format($totalHist) ?></div>
                <div class="bita-kpi-label">En Histórico</div>
            </div>
        </div>
    </div>

    <!-- PANEL DE MANTENIMIENTO -->
    <div class="maint-panel">
        <div class="maint-header" onclick="toggleMaint(this)">
            <h3><i class="ti ti-settings-automation"></i> Panel de Mantenimiento — Ciclo de Vida</h3>
            <i class="ti ti-chevron-down maint-chevron"></i>
        </div>
        <div class="maint-body">
            <div class="maint-grid">
                <div class="maint-field">
                    <label><i class="ti ti-shield-lock"></i> Retención crítica (días)</label>
                    <input type="number" id="diasCriticos" value="365" min="30" max="3650"
                        title="LOGIN, LOGOUT, RESET_PASSWORD, CREATE_USER">
                </div>
                <div class="maint-field">
                    <label><i class="ti ti-activity"></i> Retención operacional (días)</label>
                    <input type="number" id="diasOperacion" value="90" min="7" max="365"
                        title="Resto de acciones del sistema">
                </div>
                <button class="btn-purge" onclick="ejecutarMantenimiento()">
                    <i class="ti ti-trash"></i> Ejecutar Mantenimiento
                </button>
                <button class="btn-export-hist" onclick="exportHistorico()">
                    <i class="ti ti-file-download"></i> Exportar Histórico
                </button>
            </div>
            <div class="maint-info">
                <i class="ti ti-info-circle"></i>
                Última purga:
                <strong><?= $ultimaPurga ? date('d/m/Y H:i', strtotime($ultimaPurga)) : 'Nunca ejecutada' ?></strong>
                &nbsp;·&nbsp;
                Próxima purga automática (MySQL EVENT):
                <strong><?= $proximaPurga ?></strong>
                &nbsp;·&nbsp;
                Los registros se mueven a <code>bitacora_historico</code> antes de eliminarse — nunca se pierden datos.
            </div>
            <!-- Botones de purga rápida por período -->
            <div style="margin-top:14px;">
                <p style="margin:0 0 10px;font-size:0.82rem;font-weight:700;color:#475569;">
                    <i class="ti ti-clock" style="margin-right:5px;"></i>Purga rápida por período
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <button class="btn-period-purge" onclick="purgarPeriodo(1)" title="Purgar registros operacionales del último mes">
                        <i class="ti ti-calendar-minus"></i> Último mes
                    </button>
                    <button class="btn-period-purge" onclick="purgarPeriodo(3)" title="Purgar registros operacionales de los últimos 3 meses">
                        <i class="ti ti-calendar-minus"></i> 3 meses
                    </button>
                    <button class="btn-period-purge" onclick="purgarPeriodo(6)" title="Purgar registros operacionales de los últimos 6 meses">
                        <i class="ti ti-calendar-minus"></i> 6 meses
                    </button>
                    <button class="btn-period-purge btn-period-purge--warn" onclick="purgarPeriodo(12)" title="Purgar registros operacionales del último año">
                        <i class="ti ti-calendar-minus"></i> 1 año
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="filters-card">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label style="font-weight: 600; color: var(--color-primary); margin-bottom: 8px; display: block;">
                    <i class="ti ti-activity"></i> Acción
                </label>
                <select id="filterAction" class="filter-select" style="width: 100%;">
                    <option value="">Todas las acciones</option>
                    <?php
                    $actionsFromController = $data['actions'] ?? [];
                    foreach ($actionsFromController as $action):
                        ?>
                        <option value="<?= htmlspecialchars($action) ?>"><?= htmlspecialchars(traducir_accion($action)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label style="font-weight: 600; color: var(--color-primary); margin-bottom: 8px; display: block;">
                    <i class="ti ti-calendar"></i> Desde
                </label>
                <input type="text" id="filterDateFrom" class="filter-select no-choices" autocomplete="off"
                    placeholder="dd/mm/aaaa" style="width: 100%;">
            </div>

            <div class="col-md-2">
                <label style="font-weight: 600; color: var(--color-primary); margin-bottom: 8px; display: block;">
                    <i class="ti ti-calendar"></i> Hasta
                </label>
                <input type="text" id="filterDateTo" class="filter-select no-choices" autocomplete="off"
                    placeholder="dd/mm/aaaa" style="width: 100%;">
            </div>

            <div class="col-md-3">
                <label style="font-weight: 600; color: var(--color-primary); margin-bottom: 8px; display: block;">
                    <i class="ti ti-search"></i> Buscar
                </label>
                <input type="text" id="filterSearch" class="filter-select no-choices" autocomplete="off"
                    placeholder="IP, email, tabla..." style="width: 100%;" oninput="debounceFilter()">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button onclick="applyFilters()" class="btn-export" style="flex: 1;">
                    <i class="ti ti-filter"></i> Filtrar
                </button>
                <button onclick="clearFilters()" class="btn-clear">
                    <i class="ti ti-filter-off"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="table-card">
        <div id="bitacora-grid"></div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════
     MODAL PREMIUM: Detalle de Registro de Auditoría
     ══════════════════════════════════════════════════════ -->
<div id="auditModal" class="modal">
    <div class="modal-content" style="max-width: 680px;">
        <!-- Header con gradiente -->
        <div class="modal-header"
            style="background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%); margin: -36px -36px 28px -36px; padding: 24px 32px; border-radius: 20px 20px 0 0;">
            <div style="display:flex; align-items:center; gap:14px;">
                <div
                    style="width:44px;height:44px;background:rgba(255,255,255,0.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:white;">
                    <i class="ti ti-shield-check"></i>
                </div>
                <div>
                    <h2 style="font-size:1.2rem;font-weight:700;color:white;margin:0;">Detalle de Auditoría</h2>
                    <p style="color:rgba(255,255,255,0.7);font-size:0.82rem;margin:3px 0 0;">Registro de actividad del
                        sistema</p>
                </div>
            </div>
            <button class="modal-close" onclick="closeAuditModal()"
                style="background:rgba(255,255,255,0.15);color:white;border:none;">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Sección: Info del Usuario -->
        <div id="auditUserSection"
            style="display:flex;align-items:center;gap:16px;padding:18px;background:#f8fafc;border-radius:14px;margin-bottom:20px;">
            <div id="auditAvatar"
                style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#1e3a8a,#3b82f6);display:flex;align-items:center;justify-content:center;font-weight:800;color:white;font-size:1.2rem;flex-shrink:0;box-shadow:0 4px 12px rgba(30,58,138,0.25);">
                ?</div>
            <div style="flex:1;">
                <div id="auditUserName" style="font-weight:700;color:#1e293b;font-size:1rem;"></div>
                <div id="auditUserEmail" style="color:#94a3b8;font-size:0.82rem;margin-top:2px;"></div>
            </div>
            <div id="auditBadge" class="badge-action"></div>
        </div>

        <!-- Grid de metadata -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px;">
            <div style="background:#f8fafc;border-radius:12px;padding:14px;">
                <div
                    style="color:#94a3b8;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:6px;">
                    <i class="ti ti-table"></i> Tabla
                </div>
                <div id="auditTabla" style="font-weight:600;color:#1e293b;font-size:0.88rem;font-family:monospace;">
                </div>
            </div>
            <div style="background:#f8fafc;border-radius:12px;padding:14px;">
                <div
                    style="color:#94a3b8;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:6px;">
                    <i class="ti ti-world"></i> IP
                </div>
                <div id="auditIp" style="font-weight:600;color:#1e293b;font-size:0.88rem;font-family:monospace;"></div>
            </div>
            <div style="background:#f8fafc;border-radius:12px;padding:14px;">
                <div
                    style="color:#94a3b8;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:6px;">
                    <i class="ti ti-calendar"></i> Fecha
                </div>
                <div id="auditFecha" style="font-weight:600;color:#1e293b;font-size:0.84rem;"></div>
            </div>
        </div>

        <!-- ID del registro -->
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <span
                style="font-size:0.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Registro
                ID:</span>
            <code id="auditRecordId"
                style="background:#eff6ff;color:#2563eb;padding:3px 10px;border-radius:6px;font-size:0.82rem;font-weight:700;"></code>
        </div>

        <!-- Detalles JSON (si existen) -->
        <div id="auditJsonSection" style="display:none;">
            <div
                style="font-size:0.8rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">
                <i class="ti ti-braces"></i> Datos del Cambio
            </div>
            <div id="auditJsonContent" class="json-viewer"
                style="max-height:200px;background:#0f172a;color:#e2e8f0;border-radius:12px;padding:16px;font-size:0.8rem;line-height:1.6;">
            </div>
        </div>

        <!-- Acciones del modal -->
        <div
            style="display:flex;justify-content:flex-end;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9;">
            <button onclick="closeAuditModal()"
                style="padding:10px 20px;border:1.5px solid #e2e8f0;background:white;border-radius:10px;color:#64748b;font-weight:600;font-size:0.88rem;cursor:pointer;transition:all 0.15s;">
                <i class="ti ti-x"></i> Cerrar
            </button>
            <button id="auditPrintBtn" onclick="printAuditModal()"
                style="padding:10px 20px;background:linear-gradient(135deg,#dc2626,#b91c1c);border:none;border-radius:10px;color:white;font-weight:600;font-size:0.88rem;cursor:pointer;transition:all 0.15s;box-shadow:0 4px 12px rgba(220,38,38,0.25);display:flex;align-items:center;gap:8px;">
                <i class="ti ti-file-type-pdf"></i> Exportar PDF
            </button>
        </div>
    </div>
</div>

<!-- Grid.js CSS & JS locales (estilos en bitacora.css) -->
<link rel="stylesheet" href="<?= URLROOT ?>/css/gridjs-mermaid.min.css">
<script src="<?= URLROOT ?>/js/gridjs.umd.js"></script>

<script>
    // [SGP-FIX-PJAX-REDECL] var: PJAX re-inyecta este script sin reload completo.
    var grid;

    // [SGP-FIX-PJAX-BITACORA-GRID] DOMContentLoaded solo se dispara una vez por carga
    // completa de página. En navegación PJAX este <script> se re-ejecuta sin recarga
    // (execInlineScripts), así que el listener nunca se vuelve a disparar y la tabla
    // queda vacía. Se invoca directamente cuando el DOM ya está listo (que es el caso
    // en PJAX, porque el HTML nuevo ya fue inyectado antes de re-ejecutar el script).
    function initBitacoraGrid() {
        grid = new gridjs.Grid({
            columns: [
                {
                    id: 'id', name: 'ID', width: '70px',
                    formatter: (cell) => gridjs.html(`<span style="color:#94a3b8; font-size:0.8rem; font-weight:600;">#${cell}</span>`)
                },
                {
                    id: 'usuario',
                    name: 'Usuario',
                    formatter: (cell, row) => {
                        const nombre = row.cells[1].data.nombre;
                        const email = row.cells[1].data.email;
                        const initial = nombre.charAt(0).toUpperCase();
                        // Usar colores distintos basados en inicial para diversidad visual
                        const colors = {
                            A: '#1e3a8a,#3b82f6', B: '#5b21b6,#8b5cf6', C: '#065f46,#10b981',
                            D: '#92400e,#f59e0b', E: '#7c3aed,#a78bfa', F: '#b91c1c,#f87171',
                            G: '#0e7490,#22d3ee', H: '#1e3a8a,#60a5fa', default: '#1e3a8a,#3b82f6'
                        };
                        const grad = colors[initial] || colors.default;
                        return gridjs.html(`
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div class="gj-avatar" style="background:linear-gradient(135deg,${grad});">${initial}</div>
                            <div>
                                <div style="font-weight:600;color:#1e293b;font-size:0.88rem;">${nombre}</div>
                                <div style="font-size:0.76rem;color:#94a3b8;margin-top:1px;">${email}</div>
                            </div>
                        </div>
                    `);
                    }
                },
                {
                    id: 'accion',
                    name: 'Acción',
                    formatter: (cell) => {
                        // [SGP-FIX-BITACORA-TRADUCCION] Mismo diccionario ampliado
                        // que traducir_accion() en PHP, para que la columna de la
                        // tabla no muestre códigos SNAKE_CASE crudos.
                        const actionDict = {
                            'LOGIN': 'Inicio de Sesión',
                            'LOGOUT': 'Cierre de Sesión',
                            'RESET_PASSWORD': 'Contraseña Rest. (Admin)',
                            'CHANGE_PASSWORD': 'Contraseña Cambiada',
                            'RECUPERACION_PASSWORD_EXITOSA': 'Recuperación Exitosa',
                            'RESET_PIN': 'PIN Restablecido',
                            'RESET_PIN_KIOSCO': 'PIN Kiosco Restablecido',
                            'UPDATE_SECURITY_QUESTIONS': 'Act. Seg. Preguntas',
                            'CREATE_USER': 'Usuario Creado',
                            'UPDATE_USER': 'Usuario Modificado',
                            'DELETE_USER': 'Usuario Eliminado',
                            'DELETE_USER_PERMANENT': 'Usuario Elim. Permanente',
                            'TOGGLE_USER_STATUS': 'Estado Usuario Act.',
                            'UPDATE_PROFILE': 'Perfil Actualizado',
                            'CREATE_PASANTE': 'Pasante Creado',
                            'UPDATE_PASANTE': 'Pasante Modificado',
                            'UPDATE_PASANTE_DATA': 'Datos Pasante Act.',
                            'DELETE_PASANTE': 'Pasante Eliminado',
                            'CHANGE_PASANTE_STATUS': 'Estado Pasante Act.',
                            'MARCAR_ASISTENCIA_KIOSCO': 'Asistencia (Kiosco)',
                            'REGISTRAR_ASISTENCIA_MANUAL': 'Asistencia Manual Reg.',
                            'ACTUALIZAR_ASISTENCIA_MANUAL': 'Asistencia Manual Act.',
                            'REGISTRAR_ASISTENCIA_MASIVA': 'Asistencia Masiva Reg.',
                            'ANULAR_ASISTENCIA': 'Asistencia Anulada',
                            'CREATE_EVALUACION': 'Evaluación Creada',
                            'UPDATE_EVALUACION': 'Evaluación Act.',
                            'DELETE_EVALUACION': 'Evaluación Elim.',
                            'CREAR_EVALUACION': 'Evaluación Creada',
                            'ACTUALIZAR_EVALUACION': 'Evaluación Act.',
                            'ELIMINAR_EVALUACION': 'Evaluación Elim.',
                            'CREATE_EXAMEN': 'Examen Creado',
                            'TOGGLE_EXAMEN': 'Estado Examen Act.',
                            'DELETE_EXAMEN': 'Examen Eliminado',
                            'ENVIO_EXAMEN': 'Examen Enviado',
                            'REVISAR_EXAMEN': 'Examen Revisado',
                            'EDITAR_PUNTOS_PREGUNTA': 'Puntos Pregunta Edit.',
                            'DELETE_INTENTO': 'Intento Examen Elim.',
                            'PERMISO_MODIFICADO': 'Permiso Modificado',
                            'PERMISOS_RESET': 'Permisos Restablecidos',
                            'AGREGAR_FERIADO': 'Feriado Agregado',
                            'EDITAR_FERIADO': 'Feriado Editado',
                            'ELIMINAR_FERIADO': 'Feriado Eliminado',
                            'CONFIG_LABORABLES': 'Config. Laborables',
                            'MANTENIMIENTO_SESIONES': 'Limpieza Sesiones',
                            'MANTENIMIENTO_BITACORA': 'Purga Bitácora',
                            'AUDIT_PURGE': 'Limpieza Ejecutada',
                            'EXPORT_CSV': 'CSV Exportado',
                            'UPDATE_CONFIG': 'Configuración Act.'
                        };
                        const cssClass = `badge-action badge-${cell}`;
                        const translated = actionDict[cell.toUpperCase()] || cell;
                        return gridjs.html(`<span class="${cssClass}">${translated}</span>`);
                    }
                },
                {
                    id: 'tabla_afectada',
                    name: 'Tabla',
                    formatter: (cell) => {
                        const tableDict = {
                            'usuarios': 'Usuarios',
                            'datos_personales': 'Perfil',
                            'pasantes': 'Pasantes',
                            'datos_pasante': 'Detalle Pasante',
                            'bitacora': 'Bitácora',
                            'evaluaciones': 'Evaluación',
                            'asistencias': 'Asistencia',
                            'configuracion': 'Config.',
                            'asignaciones': 'Asignación'
                        };
                        const translated = tableDict[cell.toLowerCase()] || cell;
                        return gridjs.html(`
                        <span style="font-size:0.82rem;color:#64748b;background:#f8fafc;padding:4px 10px;border-radius:6px;font-family:monospace;">${translated || '—'}</span>
                    `);
                    }
                },
                {
                    id: 'ip_address',
                    name: 'Red / IP',
                    formatter: (cell) => gridjs.html(`
                    <code style="background:#f1f5f9;padding:5px 10px;border-radius:6px;color:#475569;font-size:0.78rem;letter-spacing:0.3px;">${cell}</code>
                `)
                },
                {
                    id: 'fecha',
                    name: 'Fecha',
                    formatter: (cell) => {
                        if (!cell) return gridjs.html('<span style="color:#cbd5e1">—</span>');
                        const date = new Date(cell);
                        const d = date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
                        const t = date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                        return gridjs.html(`
                        <div style="font-size:0.87rem;color:#334155;font-weight:600;">${d}</div>
                        <div style="font-size:0.74rem;color:#94a3b8;margin-top:2px;">${t}</div>
                    `);
                    }
                },
                {
                    id: 'detalles',
                    name: 'Acciones',
                    sort: false,
                    width: '110px',
                    formatter: (cell) => {
                        if (!cell) return gridjs.html('<span style="color:#e2e8f0;">—</span>');
                        // Escapar el JSON del log completo para el atributo onclick
                        const safe = cell.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        let logObj;
                        try { logObj = JSON.parse(cell); } catch (e) { logObj = {}; }
                        const logId = logObj.id || '';
                        return gridjs.html(`
                        <div style="display:flex;align-items:center;gap:6px;">
                            <button class="gj-eye-btn" onclick="openAuditModal('${safe}')" title="Ver detalle completo">
                                <i class="ti ti-eye"></i>
                            </button>
                            <button class="gj-pdf-btn" onclick="exportRowPdf(${logId})" title="Exportar como PDF">
                                <i class="ti ti-file-type-pdf"></i>
                            </button>
                        </div>
                    `);
                    }
                }
            ],
            pagination: {
                enabled: true,
                limit: 10,
                server: {
                    // prev = el string estático de server.url, que Grid.js pasa correctamente aquí
                    url: (prev, page, limit) => `${prev}&limit=${limit}&offset=${page * limit}`
                }
            },
            search: false,
            sort: true,
            language: {
                'search': { placeholder: 'Buscar...' },
                'pagination': {
                    previous: 'Anterior',
                    next: 'Siguiente',
                    showing: 'Mostrando',
                    results: () => 'registros'
                },
                loading: 'Cargando...',
                noRecordsFound: 'No se encontraron registros',
                error: 'Ocurrió un error obteniendo los datos'
            },
            server: {
                // IMPORTANTE: server.url DEBE ser un string estático en esta versión de Grid.js.
                // Si es una función, Grid.js la convierte a string y la usa como URL (bug conocido).
                url: `<?= URLROOT ?>/bitacora/apiGrid?accion=&fecha_desde=&fecha_hasta=`,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                then: (data) => {
                    if (!data || !Array.isArray(data.data)) return [];
                    return data.data.map(log => [
                        log.id,
                        { nombre: log.usuario_nombre || 'Sistema', email: log.usuario_email || 'N/A' },
                        log.accion,
                        log.tabla_afectada || '-',
                        log.ip_address,
                        log.created_at,
                        // Pasamos el objeto completo del log como JSON para los botones de acción
                        JSON.stringify(log)
                    ]);
                },
                total: data => data.total || 0
            }
        }).render(document.getElementById('bitacora-grid'));
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBitacoraGrid);
    } else {
        initBitacoraGrid();
    }

    // ── Helpers compartidos para reconstruir la URL del grid ──
    function buildGridUrl() {
        const action = encodeURIComponent(document.getElementById('filterAction').value || '');
        const dfrom = encodeURIComponent(document.getElementById('filterDateFrom').value || '');
        const dto = encodeURIComponent(document.getElementById('filterDateTo').value || '');
        const q = encodeURIComponent(document.getElementById('filterSearch')?.value || '');
        return `<?= URLROOT ?>/bitacora/apiGrid?accion=${action}&fecha_desde=${dfrom}&fecha_hasta=${dto}&q=${q}`;
    }

    function buildServerConfig(url) {
        return {
            url,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            then: (data) => {
                if (!data || !Array.isArray(data.data)) return [];
                return data.data.map(log => [
                    log.id,
                    { nombre: log.usuario_nombre || 'Sistema', email: log.usuario_email || 'N/A' },
                    log.accion,
                    log.tabla_afectada || '-',
                    log.ip_address,
                    log.created_at,
                    JSON.stringify(log)   // objeto completo → modal + PDF
                ]);
            },
            total: data => data.total || 0
        };
    }

    // Aplicar Filtros
    function applyFilters() {
        grid.updateConfig({
            server: buildServerConfig(buildGridUrl()),
            pagination: {
                enabled: true, limit: 10,
                server: { url: (prev, page, limit) => `${prev}&limit=${limit}&offset=${page * limit}` }
            }
        }).forceRender();
    }

    // Debounce para el campo de búsqueda (dispara filtro 400ms después de dejar de escribir)
    let _searchTimer;
    function debounceFilter() {
        clearTimeout(_searchTimer);
        _searchTimer = setTimeout(applyFilters, 400);
    }

    // Limpiar Filtros
    function clearFilters() {
        document.getElementById('filterAction').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        const fs = document.getElementById('filterSearch');
        if (fs) fs.value = '';
        applyFilters();
    }

    // Exportar activo a CSV (respeta filtros actuales)
    function exportLogs() {
        const action = document.getElementById('filterAction').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        window.location.href = `<?= URLROOT ?>/bitacora/export?` +
            new URLSearchParams({ accion: action, fecha_desde: dateFrom, fecha_hasta: dateTo }).toString();
    }

    // ── Ciclo de vida: funciones del panel de mantenimiento ──

    function toggleMaint(header) {
        const body = header.nextElementSibling;
        const chevron = header.querySelector('.maint-chevron');
        body.classList.toggle('open');
        chevron.style.transform = body.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
    }

    function ejecutarMantenimiento(skipConfirm = false) {
        const diasC = parseInt(document.getElementById('diasCriticos').value) || 365;
        const diasO = parseInt(document.getElementById('diasOperacion').value) || 90;

        const realizarAccion = () => {
            const btn = document.querySelector('.btn-purge');
            btn.disabled = true;
            btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 1s linear infinite"></i> Procesando...';

            const csrfMetaMant = document.querySelector('meta[name="csrf-token"]');
            fetch(`<?= URLROOT ?>/bitacora/mantenimiento`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfMetaMant ? csrfMetaMant.getAttribute('content') : ''
                },
                body: new URLSearchParams({ dias_criticos: diasC, dias_operacion: diasO })
            })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-trash"></i> Ejecutar Mantenimiento';
                    if (data.success) {
                        Swal.fire({ 
                            icon: 'success', 
                            title: '¡Ciclo Completado!', 
                            html: `
                                <div style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-top: 10px;">
                                    ${data.message}
                                    <div style="margin-top: 15px; padding: 10px; background: #f0fdf4; border-radius: 10px; color: #16a34a; font-size: 0.85rem; font-weight: 600;">
                                        <i class="ti ti-check"></i> Registros optimizados correctamente
                                    </div>
                                </div>
                            `,
                            timer: 4000, 
                            showConfirmButton: false,
                            timerProgressBar: true,
                            customClass: { popup: 'sgp-swal-inactividad' },
                            didOpen: () => { 
                                const p = Swal.getPopup(); 
                                if(p){ 
                                    p.style.borderRadius='20px';
                                    p.style.padding = '32px 28px 28px';
                                    p.style.boxShadow = '0 25px 60px rgba(0,0,0,0.18), 0 8px 24px rgba(0,0,0,0.12)';
                                } 
                            }
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({ 
                            icon: 'error', 
                            title: 'Error de Ejecución', 
                            html: `
                                <div style="color: #475569; font-size: 0.95rem; line-height: 1.6; margin-top: 10px;">
                                    ${data.message}
                                    <div style="margin-top: 15px; padding: 12px; background: #fff1f2; border-radius: 10px; color: #e11d48; font-size: 0.82rem; border: 1px solid rgba(225,29,72,0.1);">
                                        <i class="ti ti-alert-circle"></i> Verifique que las tablas de histórico estén inicializadas en la base de datos.
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#1d4ed8',
                            customClass: { popup: 'sgp-swal-inactividad' },
                            didOpen: () => { 
                                const p = Swal.getPopup(); 
                                if(p){ 
                                    p.style.borderRadius='20px';
                                    p.style.padding = '32px 28px 28px';
                                    p.style.boxShadow = '0 25px 60px rgba(0,0,0,0.18)';
                                } 
                            }
                        });
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-trash"></i> Ejecutar Mantenimiento';
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Error de Red', 
                        html: '<div style="color: #475569; font-size: 0.95rem;">No se pudo establecer conexión con el servidor de mantenimiento.</div>',
                        confirmButtonText: 'Cerrar',
                        confirmButtonColor: '#64748b',
                        customClass: { popup: 'sgp-swal-inactividad' },
                        didOpen: () => { 
                            const p = Swal.getPopup(); 
                            if(p){ p.style.borderRadius='20px'; p.style.padding = '32px 28px 28px'; } 
                        }
                    });
                });
        };

        if (skipConfirm) {
            realizarAccion();
            return;
        }

        Swal.fire({
            title: '¿Ejecutar Mantenimiento?',
            html: `
                <div style="text-align:left; font-size:0.92rem; color:#475569; line-height:1.6;">
                    <p style="margin-bottom:12px;">Se procesará el ciclo de vida de los registros según los parámetros:</p>
                    <ul style="list-style:none; padding-left:0; margin-bottom:18px;">
                        <li style="margin-bottom:10px; display:flex; align-items:center; gap:12px;">
                            <div style="width:32px; height:32px; background:#eff6ff; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#2563eb; flex-shrink:0;">
                                <i class="ti ti-shield-check" style="font-size:1.1rem;"></i>
                            </div>
                            <span>Acciones críticas: retener <strong>${diasC} días</strong></span>
                        </li>
                        <li style="display:flex; align-items:center; gap:12px;">
                            <div style="width:32px; height:32px; background:#f0fdf4; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#16a34a; flex-shrink:0;">
                                <i class="ti ti-activity" style="font-size:1.1rem;"></i>
                            </div>
                            <span>Acciones operacionales: retener <strong>${diasO} días</strong></span>
                        </li>
                    </ul>
                    <div style="background:#f8fafc; padding:14px; border-radius:12px; border:1px solid #e2e8f0; font-size:0.82rem; color:#64748b; display:flex; gap:10px; align-items:flex-start;">
                        <i class="ti ti-info-circle" style="color:#3b82f6; font-size:1.2rem; margin-top:2px;"></i>
                        <span>Los registros que superen estos períodos se moverán a la tabla de histórico antes de ser depurados de la tabla activa.</span>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1d4ed8',
            cancelButtonColor: '#f1f5f9',
            confirmButtonText: '✓ Ejecutar Ciclo de Vida',
            cancelButtonText: 'Cancelar',
            customClass: { 
                popup: 'sgp-swal-inactividad',
                confirmButton: 'sgp-swal-btn-confirm',
                cancelButton: 'sgp-swal-btn-cancel'
            },
            didOpen: () => { 
                const p = Swal.getPopup(); 
                if(!p) return;
                p.style.borderRadius='20px'; 
                p.style.padding = '32px 28px 28px';
                p.style.boxShadow = '0 25px 60px rgba(0,0,0,0.18)';
                
                // Estilizar botón cancelar igual que en el layout
                const cB = Swal.getCancelButton();
                if(cB) {
                    cB.style.color = '#475569';
                    cB.style.fontWeight = '500';
                    cB.style.border = '1px solid #cbd5e1';
                }
            }
        }).then(result => {
            if (result.isConfirmed) realizarAccion();
        });
    }

    function exportHistorico() {
        window.location.href = `<?= URLROOT ?>/bitacora/exportHistorico`;
    }

    function purgarPeriodo(meses) {
        const etiquetas = {1:'el último mes', 3:'los últimos 3 meses', 6:'los últimos 6 meses', 12:'el último año'};
        const label = etiquetas[meses] || (meses + ' meses');
        Swal.fire({
            title: '¿Purgar registros?',
            html: `Se eliminarán registros <strong>operacionales</strong> de <strong>${label}</strong>.<br><span style="font-size:0.85rem;color:#64748b;">Los registros críticos (login, accesos) se conservan. Los datos se mueven al histórico antes de eliminarse.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="ti ti-trash"></i> Purgar',
            cancelButtonText: 'Cancelar',
            customClass: { popup: 'sgp-swal-inactividad' },
            didOpen: () => { const p = Swal.getPopup(); if(p){ p.style.borderRadius='16px'; } }
        }).then(result => {
            if (!result.isConfirmed) return;
            // Calcular días
            const diasAntiguedad = meses * 30;
            document.getElementById('diasOperacion').value = diasAntiguedad;
            ejecutarMantenimiento(true); // Ejecutar directamente sin segundo modal
        });
    }


    // ── Modal Premium de Auditoría ────────────────────────
    function openAuditModal(rawJson) {
        let log;
        try {
            // rawJson viene con &quot; en lugar de " por el HTML escaping
            const decoded = rawJson.replace(/&quot;/g, '"').replace(/&#39;|&apos;/g, "'");
            log = JSON.parse(decoded);
        } catch (e) {
            console.error('Error al parsear log:', e);
            return;
        }

        // ── Avatar con inicial e inicial colorida ──
        const nombre = log.usuario_nombre || 'Sistema';
        const email = log.usuario_email || 'N/A';
        const initial = nombre.charAt(0).toUpperCase();
        const colors = {
            A: '#1e3a8a,#3b82f6', B: '#5b21b6,#8b5cf6', C: '#065f46,#10b981',
            D: '#92400e,#f59e0b', E: '#7c3aed,#a78bfa', F: '#b91c1c,#f87171',
            G: '#0e7490,#22d3ee', default: '#1e3a8a,#3b82f6'
        };
        const grad = colors[initial] || colors.default;

        document.getElementById('auditAvatar').textContent = initial;
        document.getElementById('auditAvatar').style.background = `linear-gradient(135deg,${grad})`;
        document.getElementById('auditUserName').textContent = nombre;
        document.getElementById('auditUserEmail').textContent = email;

        // ── Badge de acción ──
        const badge = document.getElementById('auditBadge');
        // [SGP-FIX-BITACORA-TRADUCCION] Mismo diccionario ampliado que traducir_accion() en PHP.
        const actionDict = {
            'LOGIN':                         'Inicio de Sesión',
            'LOGOUT':                        'Cierre de Sesión',
            'RESET_PASSWORD':                'Contraseña Restablecida (Admin)',
            'CHANGE_PASSWORD':               'Contraseña Cambiada',
            'RECUPERACION_PASSWORD_EXITOSA': 'Recuperación de Contraseña Exitosa',
            'RESET_PIN':                     'PIN de Asistencia Restablecido',
            'RESET_PIN_KIOSCO':              'PIN de Kiosco Restablecido',
            'UPDATE_SECURITY_QUESTIONS':     'Preguntas de Seguridad Actualizadas',
            'CREATE_USER':                   'Usuario Creado',
            'UPDATE_USER':                   'Usuario Modificado',
            'DELETE_USER':                   'Usuario Eliminado',
            'DELETE_USER_PERMANENT':         'Usuario Eliminado Permanentemente',
            'TOGGLE_USER_STATUS':            'Estado de Usuario Alternado',
            'UPDATE_PROFILE':                'Perfil Actualizado',
            'CREATE_PASANTE':                'Pasante Registrado',
            'UPDATE_PASANTE':                'Pasante Modificado',
            'UPDATE_PASANTE_DATA':           'Datos de Pasante Actualizados',
            'DELETE_PASANTE':                'Pasante Eliminado',
            'CHANGE_PASANTE_STATUS':         'Estado de Pasantía Actualizado',
            'MARCAR_ASISTENCIA_KIOSCO':      'Asistencia Marcada (Kiosco)',
            'REGISTRAR_ASISTENCIA_MANUAL':   'Asistencia Registrada Manualmente',
            'ACTUALIZAR_ASISTENCIA_MANUAL':  'Asistencia Manual Actualizada',
            'REGISTRAR_ASISTENCIA_MASIVA':   'Asistencia Masiva Registrada',
            'ANULAR_ASISTENCIA':             'Asistencia Anulada',
            'CREATE_EVALUACION':             'Evaluación Creada',
            'UPDATE_EVALUACION':             'Evaluación Modificada',
            'DELETE_EVALUACION':             'Evaluación Eliminada',
            'CREAR_EVALUACION':              'Evaluación Creada',
            'ACTUALIZAR_EVALUACION':         'Evaluación Actualizada',
            'ELIMINAR_EVALUACION':           'Evaluación Eliminada',
            'CREATE_EXAMEN':                 'Examen Creado',
            'TOGGLE_EXAMEN':                 'Estado de Examen Alternado',
            'DELETE_EXAMEN':                 'Examen Eliminado',
            'ENVIO_EXAMEN':                  'Examen Enviado (Pasante)',
            'REVISAR_EXAMEN':                'Examen Revisado',
            'EDITAR_PUNTOS_PREGUNTA':        'Puntos de Pregunta Editados',
            'DELETE_INTENTO':                'Intento de Examen Eliminado',
            'PERMISO_MODIFICADO':            'Permiso de Usuario Modificado',
            'PERMISOS_RESET':                'Permisos Restablecidos al Rol',
            'AGREGAR_FERIADO':               'Feriado Agregado',
            'EDITAR_FERIADO':                'Feriado Editado',
            'ELIMINAR_FERIADO':              'Feriado Eliminado',
            'CONFIG_LABORABLES':             'Configuración de Días Laborables',
            'MANTENIMIENTO_SESIONES':        'Limpieza de Sesiones',
            'MANTENIMIENTO_BITACORA':        'Purga de Bitácora',
            'AUDIT_PURGE':                   'Purga de Bitácora Ejecutada',
            'EXPORT_CSV':                    'Exportación de Datos CSV',
            'UPDATE_CONFIG':                 'Configuración del Sistema Actualizada',
        };
        const tableDict = {
            'usuarios':        'Usuarios del Sistema',
            'datos_personales':'Perfil de Usuario',
            'pasantes':        'Pasantes',
            'datos_pasante':   'Datos de Pasante',
            'bitacora':        'Bitácora de Auditoría',
            'evaluaciones':    'Evaluaciones',
            'asistencias':     'Asistencias',
            'configuracion':   'Configuración del Sistema',
            'asignaciones':    'Asignaciones',
            'usuario_permisos':'Permisos de Usuario',
        };
        badge.textContent = actionDict[log.accion?.toUpperCase()] || (log.accion || '-');
        badge.className = `badge-action badge-${log.accion}`;

        // ── Metadata ──
        document.getElementById('auditTabla').textContent = tableDict[log.tabla_afectada?.toLowerCase()] || (log.tabla_afectada || '—');
        document.getElementById('auditIp').textContent = log.ip_address || '—';
        document.getElementById('auditRecordId').textContent = `#${log.registro_id || log.id || '—'}`;

        if (log.created_at) {
            const d = new Date(log.created_at);
            document.getElementById('auditFecha').textContent =
                d.toLocaleDateString('es-ES', { weekday: 'short', day: '2-digit', month: 'long', year: 'numeric' })
                + ' — ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        }

        // ── Sección de Detalles — legible en español, no JSON crudo ──
        const jsonSection = document.getElementById('auditJsonSection');
        if (log.detalles) {
            let detallesObj = {};
            try { detallesObj = JSON.parse(log.detalles); } catch { detallesObj = { valor: log.detalles }; }

            // Mapas de claves técnicas → etiquetas legibles
            const labelMap = {
                pasante:       'Pasante',
                cedula:        'Cédula',
                hora:          'Hora de Registro',
                retardo:       'Llegada con Retardo',
                metodo:        'Método de Marcado',
                clave:         'Clave del Permiso',
                habilitado:    'Estado del Permiso',
                usuario:       'Usuario Afectado',
                email:         'Correo del Usuario',
                accion:        'Acción Realizada',
                tabla:         'Módulo Afectado',
                campo:         'Campo Modificado',
                valor_anterior:'Valor Anterior',
                valor_nuevo:   'Valor Nuevo',
                motivo:        'Motivo',
                ip:            'Dirección IP',
            };

            let html = '<div style="display:flex;flex-direction:column;gap:8px;">';
            for (const [key, val] of Object.entries(detallesObj)) {
                const label = labelMap[key] || key.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
                let displayVal = val;
                if (typeof val === 'boolean') displayVal = val ? '✅ Sí' : '❌ No';
                else if (val === null || val === undefined || val === '') displayVal = '—';
                html += `
                    <div style="display:flex;align-items:baseline;gap:10px;padding:8px 12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
                        <span style="font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;min-width:130px;flex-shrink:0;">${label}</span>
                        <span style="font-size:0.85rem;color:#1e293b;font-weight:600;word-break:break-all;">${displayVal}</span>
                    </div>`;
            }
            html += '</div>';
            document.getElementById('auditJsonContent').innerHTML = html;
            document.getElementById('auditJsonContent').style.background = 'transparent';
            document.getElementById('auditJsonContent').style.color = 'inherit';
            document.getElementById('auditJsonContent').style.padding = '0';
            jsonSection.style.display = 'block';
        } else {
            jsonSection.style.display = 'none';
        }

        // Guardar el log actual para el PDF
        document.getElementById('auditModal').dataset.logId = log.id || '';

        document.getElementById('auditModal').classList.add('active');
    }

    function closeAuditModal() {
        document.getElementById('auditModal').classList.remove('active');
    }

    // Cerrar al click en overlay
    document.getElementById('auditModal').addEventListener('click', function (e) {
        if (e.target === this) closeAuditModal();
    });

    // Imprimir / PDF desde el modal (Print Dialog del navegador)
    function printAuditModal() {
        window.print();
    }

    // Exportar PDF individual de una fila (endpoint server-side)
    function exportRowPdf(logId) {
        if (!logId) return;
        window.open(`<?= URLROOT ?>/bitacora/exportPdfRow/${logId}`, '_blank');
    }


    // Helpers
    function formatDate(datetime) {
        const date = new Date(datetime);
        return date.toLocaleDateString('es-ES');
    }

    function formatTime(datetime) {
        const date = new Date(datetime);
        return date.toLocaleTimeString('es-ES');
    }
    // ── Flatpickr para fechas del filtro ────────────────
    const fpConfig = {
        locale: 'es',
        dateFormat: 'Y-m-d',          // Lo que readeas con .value (para el backend)
        altInput: true,                // Muestra un input formateado al usuario
        altFormat: 'd/m/Y',           // Formato visual: 05/03/2026
        allowInput: true,
        monthSelectorType: 'dropdown', // Selector de mes como dropdown (no flechas)
        disableMobile: true,           // Forzar siempre el calendario de Flatpickr
        onReady: function (_, __, fp) {
            // Aplicar clase no-choices al altInput generado para que Choices no lo toque
            if (fp.altInput) {
                fp.altInput.classList.add('no-choices');
                fp.altInput.setAttribute('autocomplete', 'off');
            }
        }
    };

    // Necesitamos que flatpickr esté disponible (cargado en main_layout.php)
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#filterDateFrom', fpConfig);
        flatpickr('#filterDateTo', fpConfig);
    }
</script>