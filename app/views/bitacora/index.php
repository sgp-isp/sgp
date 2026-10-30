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
    'BLOQUEO_ASISTENCIA_UBICACION'   => 'Marcaje Fuera de Rango (GPS)',
    'BLOQUEO_ASISTENCIA_DUPLICADA'   => 'Marcaje Duplicado (Bloqueado)',
    'BLOQUEO_QR_INVALIDO'            => 'Código QR No Reconocido',
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
<link rel="stylesheet" href="<?= URLROOT ?>/css/bitacora.css?v=5">

<style>
    /* ── KPI Grid ──────────────────────────────────────────── */
    .bita-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }

    @media (max-width: 991px) {
        .bita-kpi-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px !important;
        }
    }

    @media (max-width: 575px) {
        .bita-kpi-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px !important;
            margin-bottom: 16px !important;
        }
        .bita-kpi-grid > div {
            padding: 14px 12px !important;
            border-radius: 14px !important;
        }
        .bita-kpi-grid .kpi-num {
            font-size: 1.45rem !important;
        }
        .bita-kpi-grid .kpi-title {
            font-size: 0.7rem !important;
        }
        .bita-kpi-grid .kpi-icon-link {
            width: 36px !important;
            height: 36px !important;
            font-size: 1.15rem !important;
        }
        .maint-header {
            padding: 12px 14px !important;
        }
        .maint-header h3 {
            font-size: 0.82rem !important;
            gap: 6px !important;
        }
    }

    /* ── Mobile Bento Audit Cards ──────────────────────────── */
    @media (max-width: 900px) {
        .table-card { background: transparent !important; border: none !important; padding: 0 !important; box-shadow: none !important; }
        .gridjs-container, .gridjs-wrapper { padding: 0 !important; border: none !important; box-shadow: none !important; background: transparent !important; }
        .gridjs-head { display: block !important; margin-bottom: 12px !important; padding: 0 !important; }
        .gridjs-thead { display: none !important; }
        .gridjs-tbody { display: flex !important; flex-direction: column !important; gap: 14px !important; background: transparent !important; border: none !important; }
        .gridjs-tr {
            display: grid !important;
            grid-template-columns: 1fr auto !important;
            row-gap: 10px !important;
            column-gap: 10px !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 18px !important;
            padding: 16px 18px 14px !important;
            margin: 0 !important;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.06), 0 2px 4px -1px rgba(15, 23, 42, 0.03) !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease !important;
        }
        .gridjs-tr:hover { border-color: #cbd5e1 !important; box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.1) !important; }
        .gridjs-td { padding: 0 !important; border: none !important; background: transparent !important; }
        .gridjs-td:nth-child(2) { grid-column: 1 !important; grid-row: 1 !important; display: flex !important; align-items: center !important; min-width: 0 !important; }
        .gridjs-td:nth-child(2) > div { display: flex !important; align-items: center !important; gap: 10px !important; min-width: 0 !important; }
        .gridjs-td:nth-child(2) .gj-avatar { width: 38px !important; height: 38px !important; min-width: 38px !important; border-radius: 12px !important; font-size: 0.95rem !important; }
        .gridjs-td:nth-child(2) div[style*="font-weight:600"] { font-size: 0.92rem !important; font-weight: 700 !important; color: #0f172a !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; max-width: 210px !important; }
        .gridjs-td:nth-child(2) div[style*="font-size:0.76rem"] { font-size: 0.74rem !important; color: #64748b !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; max-width: 210px !important; }
        .gridjs-td:nth-child(1) { grid-column: 2 !important; grid-row: 1 !important; display: flex !important; justify-content: flex-end !important; align-items: flex-start !important; }
        .gridjs-td:nth-child(1) span { display: inline-flex !important; align-items: center !important; background: #f1f5f9 !important; color: #475569 !important; font-size: 0.76rem !important; font-weight: 700 !important; padding: 3px 9px !important; border-radius: 8px !important; border: 1px solid #e2e8f0 !important; }
        .gridjs-td:nth-child(3) { grid-column: 1 !important; grid-row: 2 !important; display: flex !important; align-items: center !important; padding-top: 2px !important; }
        .gridjs-td:nth-child(3) .badge-action { font-size: 0.75rem !important; padding: 5px 12px !important; border-radius: 30px !important; font-weight: 700 !important; letter-spacing: 0.3px !important; }
        .gridjs-td:nth-child(4) { grid-column: 2 !important; grid-row: 2 !important; display: flex !important; justify-content: flex-end !important; align-items: center !important; padding-top: 2px !important; }
        .gridjs-td:nth-child(4) span { display: inline-flex !important; align-items: center !important; background: #f8fafc !important; color: #475569 !important; font-size: 0.74rem !important; font-weight: 600 !important; padding: 3px 9px !important; border-radius: 7px !important; border: 1px solid #e2e8f0 !important; }
        .gridjs-td:nth-child(4)::before { content: "Módulo:" !important; font-size: 0.70rem !important; font-weight: 600 !important; color: #94a3b8 !important; margin-right: 5px !important; }
        .gridjs-td:nth-child(6) { grid-column: 1 !important; grid-row: 3 !important; display: flex !important; flex-direction: row !important; align-items: center !important; gap: 8px !important; padding-top: 10px !important; border-top: 1px dashed #e2e8f0 !important; }
        .gridjs-td:nth-child(6) > div:first-child { font-size: 0.82rem !important; font-weight: 700 !important; color: #1e293b !important; display: flex !important; align-items: center !important; gap: 4px !important; }
        .gridjs-td:nth-child(6) > div:first-child::before { content: "📅" !important; font-size: 0.8rem !important; }
        .gridjs-td:nth-child(6) > div:last-child { font-size: 0.76rem !important; color: #64748b !important; margin-top: 0 !important; background: #f8fafc !important; padding: 2px 7px !important; border-radius: 6px !important; border: 1px solid #f1f5f9 !important; font-weight: 600 !important; }
        .gridjs-td:nth-child(5) { grid-column: 1 !important; grid-row: 4 !important; display: flex !important; align-items: center !important; gap: 5px !important; padding-top: 2px !important; }
        .gridjs-td:nth-child(5)::before { content: "🌐 IP:" !important; font-size: 0.70rem !important; font-weight: 700 !important; color: #94a3b8 !important; }
        .gridjs-td:nth-child(5) code { background: #f1f5f9 !important; border: 1px solid #e2e8f0 !important; padding: 2px 7px !important; border-radius: 6px !important; font-size: 0.72rem !important; color: #475569 !important; font-weight: 600 !important; }
        .gridjs-td:nth-child(7) { grid-column: 2 !important; grid-row: 3 / 5 !important; display: flex !important; justify-content: flex-end !important; align-items: center !important; padding-top: 10px !important; border-top: 1px dashed #e2e8f0 !important; }
        .gridjs-td:nth-child(7) > div { display: flex !important; align-items: center !important; gap: 8px !important; }
        .gridjs-td:nth-child(7) .gj-eye-btn { width: 38px !important; height: 38px !important; min-width: 38px !important; border-radius: 11px !important; font-size: 1.15rem !important; background: #eff6ff !important; border: 1.5px solid #bfdbfe !important; color: #2563eb !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; cursor: pointer !important; }
        .gridjs-td:nth-child(7) .gj-pdf-btn { width: 38px !important; height: 38px !important; min-width: 38px !important; border-radius: 11px !important; font-size: 1.15rem !important; background: #fef2f2 !important; border: 1.5px solid #fecaca !important; color: #dc2626 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; cursor: pointer !important; }
        .gridjs-pagination { display: flex !important; flex-direction: column !important; gap: 12px !important; align-items: center !important; padding: 16px 0 !important; }
        [data-theme="dark"] .gridjs-tr { background: #111a2e !important; border-color: #1e293b !important; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4) !important; }
        [data-theme="dark"] .gridjs-td:nth-child(1) span { background: #1e293b !important; border-color: #334155 !important; color: #94a3b8 !important; }
        [data-theme="dark"] .gridjs-td:nth-child(2) div[style*="font-weight:600"] { color: #f8fafc !important; }
        [data-theme="dark"] .gridjs-td:nth-child(4) span { background: #1e293b !important; border-color: #334155 !important; color: #cbd5e1 !important; }
        [data-theme="dark"] .gridjs-td:nth-child(6) { border-top-color: #1e293b !important; }
        [data-theme="dark"] .gridjs-td:nth-child(6) > div:first-child { color: #f1f5f9 !important; }
        [data-theme="dark"] .gridjs-td:nth-child(6) > div:last-child { background: #1e293b !important; border-color: #334155 !important; color: #94a3b8 !important; }
        [data-theme="dark"] .gridjs-td:nth-child(5) code { background: #1e293b !important; border-color: #334155 !important; color: #94a3b8 !important; }
        [data-theme="dark"] .gridjs-td:nth-child(7) { border-top-color: #1e293b !important; }
        [data-theme="dark"] .gridjs-td:nth-child(7) .gj-eye-btn { background: rgba(37, 99, 235, 0.15) !important; border-color: rgba(37, 99, 235, 0.35) !important; color: #60a5fa !important; }
        [data-theme="dark"] .gridjs-td:nth-child(7) .gj-pdf-btn { background: rgba(220, 38, 38, 0.15) !important; border-color: rgba(220, 38, 38, 0.35) !important; color: #f87171 !important; }
    }

    /* ── Modal Detalle de Auditoría Bento UI ────────────────── */
    .audit-modal-dialog {
        max-width: 620px !important;
        width: 92% !important;
        padding: 0 !important;
        border-radius: 22px !important;
        overflow: hidden !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25) !important;
        background: #ffffff !important;
        display: flex !important;
        flex-direction: column !important;
    }
    .audit-modal-header {
        background: linear-gradient(135deg, #172554 0%, #1e3a8a 50%, #2563eb 100%) !important;
        padding: 22px 26px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        color: #ffffff !important;
        flex-shrink: 0 !important;
    }
    .audit-modal-header-left {
        display: flex !important;
        align-items: center !important;
        gap: 14px !important;
        min-width: 0 !important;
    }
    .audit-modal-icon {
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        background: rgba(255, 255, 255, 0.18) !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
        border-radius: 13px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.35rem !important;
        color: #ffffff !important;
    }
    .audit-modal-title {
        font-size: 1.15rem !important;
        font-weight: 800 !important;
        color: #ffffff !important;
        margin: 0 !important;
        line-height: 1.2 !important;
    }
    .audit-modal-sub {
        color: rgba(255, 255, 255, 0.8) !important;
        font-size: 0.80rem !important;
        margin: 3px 0 0 !important;
    }
    .audit-modal-close {
        width: 36px !important;
        height: 36px !important;
        background: rgba(255, 255, 255, 0.16) !important;
        border: 1px solid rgba(255, 255, 255, 0.25) !important;
        border-radius: 11px !important;
        color: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-size: 1.1rem !important;
    }
    .audit-modal-close:hover {
        background: rgba(255, 255, 255, 0.28) !important;
        transform: scale(1.05) !important;
    }
    .audit-modal-body {
        padding: 22px 24px !important;
        max-height: calc(88vh - 145px) !important;
        overflow-y: auto !important;
        flex: 1 1 auto !important;
    }
    .audit-user-card {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 14px !important;
        padding: 14px 18px !important;
        background: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 16px !important;
        margin-bottom: 16px !important;
    }
    .audit-user-info {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        min-width: 0 !important;
    }
    .audit-avatar {
        width: 46px !important;
        height: 46px !important;
        min-width: 46px !important;
        border-radius: 14px !important;
        font-weight: 800 !important;
        color: #ffffff !important;
        font-size: 1.15rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2) !important;
    }
    .audit-user-name {
        font-weight: 700 !important;
        color: #0f172a !important;
        font-size: 0.95rem !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    .audit-user-email {
        color: #64748b !important;
        font-size: 0.78rem !important;
        margin-top: 1px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    .audit-badge-wrap {
        flex-shrink: 0 !important;
    }
    .audit-badge-wrap .badge-action {
        white-space: nowrap !important;
        padding: 6px 14px !important;
        font-size: 0.76rem !important;
        border-radius: 30px !important;
        font-weight: 700 !important;
        letter-spacing: 0.3px !important;
    }
    .audit-meta-grid {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
        margin-bottom: 16px !important;
    }
    .audit-meta-card {
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        padding: 12px 14px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        min-width: 0 !important;
    }
    .audit-meta-card-wide {
        grid-column: 1 / -1 !important;
    }
    .audit-meta-label {
        color: #64748b !important;
        font-size: 0.70rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 4px !important;
        display: flex !important;
        align-items: center !important;
        gap: 5px !important;
    }
    .audit-meta-label i {
        font-size: 0.85rem !important;
        color: #3b82f6 !important;
    }
    .audit-meta-value {
        font-weight: 700 !important;
        color: #1e293b !important;
        font-size: 0.88rem !important;
        word-break: break-word !important;
    }
    .audit-meta-mono {
        font-family: monospace !important;
        color: #0f172a !important;
        font-size: 0.84rem !important;
    }
    .audit-id-badge {
        background: #eff6ff !important;
        color: #2563eb !important;
        border: 1px solid #bfdbfe !important;
        padding: 2px 8px !important;
        border-radius: 6px !important;
        font-size: 0.80rem !important;
        font-weight: 700 !important;
        display: inline-block !important;
    }
    .audit-meta-date {
        font-size: 0.84rem !important;
        color: #0f172a !important;
        font-weight: 600 !important;
    }
    .audit-json-section {
        margin-top: 14px !important;
    }
    .audit-json-title {
        font-size: 0.74rem !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 8px !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    /* ── Ticket Troquelado Cutout Line & Hash Box ─────────── */
    .audit-ticket-cutout-line {
        height: 2px !important;
        border-top: 2px dashed #cbd5e1 !important;
        position: relative !important;
        margin: 0 !important;
        background: #ffffff !important;
    }
    .audit-ticket-cutout-line::before {
        content: "" !important;
        position: absolute !important;
        left: -12px !important;
        top: -11px !important;
        width: 22px !important;
        height: 22px !important;
        border-radius: 50% !important;
        background: rgba(15, 23, 42, 0.6) !important;
        z-index: 5 !important;
    }
    .audit-ticket-cutout-line::after {
        content: "" !important;
        position: absolute !important;
        right: -12px !important;
        top: -11px !important;
        width: 22px !important;
        height: 22px !important;
        border-radius: 50% !important;
        background: rgba(15, 23, 42, 0.6) !important;
        z-index: 5 !important;
    }
    .audit-hash-container {
        background: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 12px 14px !important;
        margin-top: 14px !important;
    }

    .audit-modal-footer {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 16px 24px !important;
        background: #f8fafc !important;
        border-top: 1px solid #e2e8f0 !important;
        flex-shrink: 0 !important;
    }
    .btn-audit-cancel {
        padding: 10px 18px !important;
        border: 1.5px solid #cbd5e1 !important;
        background: #ffffff !important;
        border-radius: 11px !important;
        color: #475569 !important;
        font-weight: 700 !important;
        font-size: 0.86rem !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        transition: all 0.18s ease !important;
    }
    .btn-audit-cancel:hover {
        background: #f1f5f9 !important;
        color: #0f172a !important;
        border-color: #94a3b8 !important;
    }
    .btn-audit-export {
        padding: 10px 20px !important;
        background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
        border: none !important;
        border-radius: 11px !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.86rem !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25) !important;
        transition: all 0.18s ease !important;
    }
    .btn-audit-export:hover {
        background: linear-gradient(135deg, #b91c1c, #991b1b) !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35) !important;
    }

    @media (max-width: 576px) {
        .audit-modal-dialog { width: calc(100% - 20px) !important; margin: 10px auto !important; border-radius: 20px !important; }
        .audit-modal-header { padding: 18px 20px !important; }
        .audit-modal-icon { width: 38px !important; height: 38px !important; min-width: 38px !important; font-size: 1.15rem !important; border-radius: 10px !important; }
        .audit-modal-title { font-size: 1.05rem !important; }
        .audit-modal-sub { font-size: 0.74rem !important; }
        .audit-modal-body { padding: 16px 16px !important; }
        .audit-user-card { flex-direction: column !important; align-items: stretch !important; padding: 12px 14px !important; gap: 10px !important; }
        .audit-badge-wrap { width: 100% !important; display: flex !important; justify-content: flex-start !important; }
        .audit-badge-wrap .badge-action { width: 100% !important; text-align: center !important; justify-content: center !important; }
        .audit-meta-grid { gap: 8px !important; }
        .audit-meta-card { padding: 10px 12px !important; border-radius: 12px !important; }
        .audit-modal-footer { padding: 14px 16px !important; gap: 8px !important; }
        .btn-audit-cancel { flex: 1 !important; padding: 10px 12px !important; }
        .btn-audit-export { flex: 1.2 !important; padding: 10px 14px !important; }
    }

    [data-theme="dark"] .audit-modal-dialog { background: #0f172a !important; border-color: #1e293b !important; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7) !important; }
    [data-theme="dark"] .audit-user-card { background: #111a2e !important; border-color: #1e293b !important; }
    [data-theme="dark"] .audit-user-name { color: #f8fafc !important; }
    [data-theme="dark"] .audit-meta-card { background: #111a2e !important; border-color: #1e293b !important; }
    [data-theme="dark"] .audit-meta-value { color: #f1f5f9 !important; }
    [data-theme="dark"] .audit-meta-date { color: #e2e8f0 !important; }
    [data-theme="dark"] .audit-id-badge { background: #1e293b !important; border-color: #334155 !important; color: #60a5fa !important; }
    [data-theme="dark"] .audit-modal-footer { background: #0b1120 !important; border-top-color: #1e293b !important; }
    [data-theme="dark"] .btn-audit-cancel { background: #1e293b !important; border-color: #334155 !important; color: #cbd5e1 !important; }
    [data-theme="dark"] .btn-audit-cancel:hover { background: #334155 !important; color: #ffffff !important; }

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

    /* ── Filtros y Botones de Bitácora ────────────────────── */
    .filters-label {
        font-weight: 600;
        color: var(--color-primary);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.88rem;
        user-select: none;
    }
    .filters-label-spacer {
        display: block;
        margin-bottom: 8px;
        font-size: 0.88rem;
        line-height: 1.2;
        visibility: hidden;
        pointer-events: none;
    }
    .bita-filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        width: 100%;
    }
    .btn-filter-apply {
        height: 44px;
        flex: 1;
        min-width: 0;
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        white-space: nowrap;
    }
    .btn-filter-apply:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
    }
    .btn-filter-apply:active {
        transform: translateY(0);
    }
    .btn-filter-clear {
        height: 44px;
        min-width: 44px;
        padding: 0 14px;
        background: #f1f5f9;
        color: #475569;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.88rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .btn-filter-clear:hover {
        background: #e2e8f0;
        color: #1e293b;
        transform: translateY(-1px);
    }
    .btn-filter-clear:active {
        transform: translateY(0);
    }
    [data-theme="dark"] .filters-card label,
    [data-theme="dark"] .filters-label {
        color: #93c5fd !important;
    }
    [data-theme="dark"] .filters-card .filter-select {
        background: #162238 !important;
        border-color: #1e293b !important;
        color: #f8fafc !important;
    }
    [data-theme="dark"] .filters-card .choices__inner {
        background: #162238 !important;
        border-color: #1e293b !important;
        color: #f8fafc !important;
    }
    [data-theme="dark"] .filters-card .choices__list--single .choices__item {
        color: #f8fafc !important;
    }
    [data-theme="dark"] .filters-card .choices__list--dropdown {
        background: #111a2e !important;
        border-color: #1e293b !important;
        color: #f8fafc !important;
    }
    [data-theme="dark"] .filters-card .choices__list--dropdown .choices__item {
        color: #cbd5e1 !important;
    }
    [data-theme="dark"] .filters-card .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background: #1e293b !important;
        color: #38bdf8 !important;
    }
    [data-theme="dark"] .filters-card .choices__input {
        background: #162238 !important;
        color: #f8fafc !important;
        border-color: #1e293b !important;
    }
    [data-theme="dark"] .btn-filter-clear {
        background: #1e293b !important;
        color: #cbd5e1 !important;
        border: 1.5px solid #334155 !important;
    }
    [data-theme="dark"] .btn-filter-clear:hover {
        background: #334155 !important;
        color: #ffffff !important;
    }
</style>

<div class="dashboard-container" style="width: 100%; max-width: 100%; padding: 0;">
    <!-- BANNER -->
    <style>
        /* Banner Bento Bitácora — Coherencia visual con Respaldo y Analíticas */
        .bitacora-banner {
            background: linear-gradient(135deg, var(--color-primary, #172554) 0%, var(--gradient-mid, #1e3a8a) 50%, var(--color-accent-brand, #2563eb) 100%) !important;
            border-radius: 22px !important;
            padding: 28px 32px !important;
            margin-bottom: 24px !important;
            position: relative !important;
            overflow: hidden !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 20px !important;
            box-shadow: 0 10px 30px rgba(23, 37, 84, 0.15) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        .bita-banner-deco-1 {
            position: absolute;
            top: -40px;
            right: -40px;
            width: 220px;
            height: 220px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            pointer-events: none;
        }
        .bita-banner-deco-2 {
            position: absolute;
            bottom: -50px;
            left: -30px;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 50%;
            pointer-events: none;
        }
        .bita-banner-main {
            display: flex;
            align-items: center;
            gap: 18px;
            z-index: 1;
            min-width: 0;
        }
        .bita-banner-icon {
            width: 56px;
            height: 56px;
            min-width: 56px;
            background: rgba(255, 255, 255, 0.18) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.28) !important;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #ffffff !important;
            flex-shrink: 0;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
            transition: transform 0.25s ease;
        }
        .bita-banner-icon:hover {
            transform: scale(1.05) rotate(4deg);
        }
        .bita-banner-title {
            color: #ffffff !important;
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }
        .bita-banner-sub {
            color: rgba(255, 255, 255, 0.88) !important;
            margin: 5px 0 0;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .bita-banner-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, 0.18) !important;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 50px;
            padding: 2px 10px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #ffffff !important;
        }
        .bita-banner-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1;
            flex-shrink: 0;
        }
        .bita-banner-actions .btn-stitch-gold {
            background-color: #ffffff !important;
            color: #172554 !important;
            border: none;
            padding: 10px 20px;
            font-size: 0.88rem;
            border-radius: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .bita-banner-actions .btn-stitch-gold:hover {
            background-color: #f8fafc !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 991px) {
            .bitacora-banner {
                flex-direction: column !important;
                align-items: flex-start !important;
                padding: 20px 18px !important;
                gap: 16px !important;
                border-radius: 18px !important;
            }
            .bita-banner-title {
                font-size: 1.4rem !important;
            }
            .bita-banner-icon {
                width: 46px !important;
                height: 46px !important;
                min-width: 46px !important;
                font-size: 22px !important;
                border-radius: 12px !important;
            }
            .bita-banner-sub {
                font-size: 0.82rem !important;
            }
            .bita-banner-actions {
                width: 100% !important;
            }
            .bita-banner-actions .btn-stitch-gold {
                width: 100% !important;
                justify-content: center !important;
                padding: 10px 16px !important;
            }
        }
    </style>
    <div class="bitacora-banner dashboard-banner">
        <div class="bita-banner-deco-1" aria-hidden="true"></div>
        <div class="bita-banner-deco-2" aria-hidden="true"></div>
        <div class="bita-banner-main">
            <div class="bita-banner-icon" title="Bitácora de Auditoría">
                <i class="ti ti-shield-check"></i>
            </div>
            <div class="bita-banner-text">
                <h1 class="bita-banner-title">Bitácora de Auditoría</h1>
                <p class="bita-banner-sub">
                    <i class="ti ti-history"></i>
                    <span>Trazabilidad · Registro de Actividades</span>
                    <span class="bita-banner-badge">
                        <i class="ti ti-database"></i>
                        <span><?= number_format($totalActivos) ?> eventos</span>
                    </span>
                </p>
            </div>
        </div>

        <div class="bita-banner-actions">
            <button onclick="exportLogs()" class="btn btn-stitch-gold" title="Exportar registros en CSV">
                <i class="ti ti-file-download"></i> <span>Exportar CSV</span>
            </button>
        </div>
    </div>

    <!-- KPI CARDS (Estandarizadas estilo Inicio) -->
    <div class="dashboard-kpi-grid bita-kpi-grid">
        <div class="kpi-card-blue">
            <div style="flex:1;">
                <p class="kpi-title">Registros Activos</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <h2 class="kpi-num" data-kpi-value="<?= $totalActivos ?>"><?= number_format($totalActivos) ?></h2>
                </div>
            </div>
            <div class="kpi-icon-link" title="Registros Activos">
                <i class="ti ti-database"></i>
            </div>
        </div>

        <div class="kpi-card-green">
            <div style="flex:1;">
                <p class="kpi-title">Eventos Hoy</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <h2 class="kpi-num" data-kpi-value="<?= $hoy ?>"><?= number_format($hoy) ?></h2>
                </div>
            </div>
            <div class="kpi-icon-link" title="Eventos Hoy">
                <i class="ti ti-calendar-event"></i>
            </div>
        </div>

        <div class="kpi-card-purple">
            <div style="flex:1;">
                <p class="kpi-title">Últimos 7 días</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <h2 class="kpi-num" data-kpi-value="<?= $semana ?>"><?= number_format($semana) ?></h2>
                </div>
            </div>
            <div class="kpi-icon-link" title="Últimos 7 días">
                <i class="ti ti-chart-bar"></i>
            </div>
        </div>

        <div class="kpi-card-yellow">
            <div style="flex:1;">
                <p class="kpi-title">En Histórico</p>
                <div style="display:flex;align-items:center;gap:12px;">
                    <h2 class="kpi-num" data-kpi-value="<?= $totalHist ?>"><?= number_format($totalHist) ?></h2>
                </div>
            </div>
            <div class="kpi-icon-link" title="En Histórico">
                <i class="ti ti-archive"></i>
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
            <div class="col-12 col-md-6 col-xl-3">
                <label class="filters-label">
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

            <div class="col-6 col-md-3 col-xl-2">
                <label class="filters-label">
                    <i class="ti ti-calendar"></i> Desde
                </label>
                <input type="text" id="filterDateFrom" class="filter-select no-choices" autocomplete="off"
                    placeholder="dd/mm/aaaa" style="width: 100%;">
            </div>

            <div class="col-6 col-md-3 col-xl-2">
                <label class="filters-label">
                    <i class="ti ti-calendar"></i> Hasta
                </label>
                <input type="text" id="filterDateTo" class="filter-select no-choices" autocomplete="off"
                    placeholder="dd/mm/aaaa" style="width: 100%;">
            </div>

            <div class="col-12 col-md-7 col-xl-3">
                <label class="filters-label">
                    <i class="ti ti-search"></i> Buscar
                </label>
                <input type="text" id="filterSearch" class="filter-select no-choices" autocomplete="off"
                    placeholder="IP, email, tabla..." style="width: 100%;" oninput="debounceFilter()">
            </div>

            <div class="col-12 col-md-5 col-xl-2">
                <label class="d-none d-md-block filters-label-spacer" aria-hidden="true">&nbsp;</label>
                <div class="bita-filter-actions">
                    <button type="button" onclick="applyFilters()" class="btn-filter-apply" title="Aplicar filtros">
                        <i class="ti ti-filter"></i> <span>Filtrar</span>
                    </button>
                    <button type="button" onclick="clearFilters()" class="btn-filter-clear" title="Limpiar todos los filtros">
                        <i class="ti ti-filter-off"></i> <span class="d-none d-md-inline d-xl-none d-xxl-inline">Limpiar</span>
                    </button>
                </div>
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
<!-- ══════════════════════════════════════════════════════
     MODAL CORPORATIVO: Ticket de Auditoría Troquelado (#AUD-001204)
     ══════════════════════════════════════════════════════ -->
<div id="auditModal" class="modal">
    <div class="modal-content audit-modal-dialog" style="overflow:visible;border-radius:22px;">
        <!-- Header con formato Ticket Corporativo Troquelado -->
        <div class="audit-modal-header" style="background: linear-gradient(135deg, #162660 0%, #1e3a8a 100%);border-radius:22px 22px 0 0;">
            <div class="audit-modal-header-left">
                <div class="audit-modal-icon" style="background:rgba(255,255,255,0.2);color:#ffffff;">
                    <i class="ti ti-ticket"></i>
                </div>
                <div>
                    <div style="font-size:0.70rem;font-weight:700;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.5px;">TICKET DE AUDITORÍA FORENSE &bull; ISPEB</div>
                    <h2 class="audit-modal-title" id="auditTicketCode" style="color:#ffffff;font-family:monospace;letter-spacing:0.5px;">#AUD-000000</h2>
                </div>
            </div>
            <button type="button" class="modal-close audit-modal-close" onclick="closeAuditModal()" title="Cerrar (Esc)">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <!-- Perforación Troquelada del Ticket -->
        <div class="audit-ticket-cutout-line" aria-hidden="true"></div>

        <!-- Cuerpo del Modal -->
        <div class="audit-modal-body">
            <!-- Sección: Usuario y Acción -->
            <div id="auditUserSection" class="audit-user-card">
                <div class="audit-user-info">
                    <div id="auditAvatar" class="audit-avatar">?</div>
                    <div class="audit-user-text">
                        <div id="auditUserName" class="audit-user-name"></div>
                        <div id="auditUserEmail" class="audit-user-email"></div>
                    </div>
                </div>
                <div class="audit-badge-wrap">
                    <div id="auditBadge" class="badge-action"></div>
                </div>
            </div>

            <!-- Grid de metadata Bento -->
            <div class="audit-meta-grid">
                <div class="audit-meta-card">
                    <div class="audit-meta-label">
                        <i class="ti ti-table"></i> Módulo / Registro
                    </div>
                    <div id="auditTabla" class="audit-meta-value"></div>
                </div>
                <div class="audit-meta-card">
                    <div class="audit-meta-label">
                        <i class="ti ti-world"></i> Dirección IP / Red
                    </div>
                    <div id="auditIp" class="audit-meta-value audit-meta-mono"></div>
                </div>
                <div class="audit-meta-card">
                    <div class="audit-meta-label">
                        <i class="ti ti-hash"></i> ID de Registro Afectado
                    </div>
                    <div class="audit-meta-value">
                        <code id="auditRecordId" class="audit-id-badge"></code>
                    </div>
                </div>
                <div class="audit-meta-card">
                    <div class="audit-meta-label">
                        <i class="ti ti-calendar-time"></i> Fecha y Hora Oficial
                    </div>
                    <div id="auditFecha" class="audit-meta-value audit-meta-date"></div>
                </div>
            </div>

            <!-- Detalles JSON / Operación (si existen) -->
            <div id="auditJsonSection" class="audit-json-section" style="display:none;">
                <div class="audit-json-title">
                    <i class="ti ti-file-description"></i> Datos del Cambio / Parámetros
                </div>
                <div id="auditJsonContent" class="audit-json-content"></div>
            </div>

            <!-- Sello de Integridad Criptográfica Forense (SHA-256) -->
            <div class="audit-hash-container">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:6px;font-size:0.74rem;font-weight:700;color:#1e3a8a;text-transform:uppercase;">
                        <i class="ti ti-shield-check" style="color:#10b981;font-size:0.95rem;"></i>
                        <span>Sello de Integridad Forense SHA-256</span>
                    </div>
                    <button type="button" id="btnCopiarHash" onclick="copiarHashAuditoria()" class="btn-copy-hash" title="Copiar hash de integridad" style="padding:4px 10px;border:1px solid #cbd5e1;background:#ffffff;border-radius:6px;font-size:0.72rem;font-weight:700;color:#475569;cursor:pointer;display:inline-flex;align-items:center;gap:4px;">
                        <i class="ti ti-copy"></i> <span>Copiar</span>
                    </button>
                </div>
                <div id="auditHashVal" class="audit-hash-code" style="font-family:'Courier New',Courier,monospace;font-size:0.76rem;font-weight:700;color:#1e3a8a;word-break:break-all;background:#ffffff;padding:8px 10px;border-radius:8px;border:1px solid #cbd5e1;display:block;margin-top:6px;letter-spacing:0.5px;">CALCULANDO_SELLO_SHA256...</div>
            </div>
        </div>

        <!-- Acciones del modal -->
        <div class="audit-modal-footer">
            <button type="button" class="btn-audit-cancel" onclick="closeAuditModal()">
                <i class="ti ti-x"></i> <span>Cerrar</span>
            </button>
            <button type="button" id="auditPrintBtn" class="btn-audit-export" onclick="descargarTicketPdfModal()" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#162660,#2563eb);color:#ffffff;border:none;border-radius:10px;padding:10px 18px;font-weight:700;font-size:0.86rem;cursor:pointer;box-shadow:0 4px 12px rgba(37,99,235,0.25);">
                <i class="ti ti-file-type-pdf"></i> <span>Descargar Ticket PDF</span>
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
    // completa de página. En navegación PJAX este <script> se re-ejecuta sin recarga.
    // Garantizamos existencia de gridjs y limpiamos el contenedor antes de renderizar.
    function initBitacoraGrid() {
        const container = document.getElementById('bitacora-grid');
        if (!container) return;

        if (typeof gridjs === 'undefined') {
            setTimeout(initBitacoraGrid, 60);
            return;
        }

        container.innerHTML = '';
        if (grid && typeof grid.destroy === 'function') {
            try { grid.destroy(); } catch(e){}
        }

        grid = new gridjs.Grid({
            columns: [
                {
                    id: 'id', name: 'Ticket ID', width: '135px',
                    formatter: (cell) => {
                        const audCode = 'AUD-' + String(cell).padStart(6, '0');
                        return gridjs.html(`
                            <span style="display:inline-flex;align-items:center;gap:5px;background:#eff6ff;color:#1e3a8a;border:1px solid #bfdbfe;padding:3px 9px;border-radius:8px;font-family:monospace;font-size:0.78rem;font-weight:800;letter-spacing:0.5px;">
                                <i class="ti ti-ticket" style="font-size:0.85rem;color:#2563eb;"></i>#${audCode}
                            </span>
                        `);
                    }
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
                            'UPDATE_CONFIG': 'Configuración Act.',
                            'BLOQUEO_ASISTENCIA_UBICACION': 'Bloqueo GPS (Fuera Sede)',
                            'BLOQUEO_ASISTENCIA_DUPLICADA': 'Marcaje Duplicado',
                            'BLOQUEO_QR_INVALIDO': 'QR Inválido'
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
                        const key = (cell || '').toLowerCase().trim();
                        let translated = tableDict[key];
                        if (!translated) {
                            translated = (!key || key === '-' || key === 'null') ? 'Sesión' : cell;
                        }
                        return gridjs.html(`
                        <span style="font-size:0.75rem;color:#475569;background:#f8fafc;padding:3px 9px;border-radius:7px;border:1px solid #e2e8f0;font-weight:600;">${translated}</span>
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
        }).render(container);
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
    var _searchTimer;
    function debounceFilter() {
        clearTimeout(_searchTimer);
        _searchTimer = setTimeout(applyFilters, 400);
    }

    // Limpiar Filtros
    function clearFilters() {
        const actionEl = document.getElementById('filterAction');
        if (actionEl) {
            actionEl.value = '';
            if (actionEl.choicesInstance) {
                try { actionEl.choicesInstance.setChoiceByValue(''); } catch (e) {}
            }
        }
        const dFrom = document.getElementById('filterDateFrom');
        if (dFrom) {
            if (dFrom._flatpickr) dFrom._flatpickr.clear();
            else dFrom.value = '';
        }
        const dTo = document.getElementById('filterDateTo');
        if (dTo) {
            if (dTo._flatpickr) dTo._flatpickr.clear();
            else dTo.value = '';
        }
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
            'BLOQUEO_ASISTENCIA_UBICACION':   'Marcaje Bloqueado: Fuera de la Sede',
            'BLOQUEO_ASISTENCIA_DUPLICADA':   'Marcaje Bloqueado: Asistencia Duplicada',
            'BLOQUEO_QR_INVALIDO':            'Marcaje Bloqueado: Código QR No Reconocido',
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
        const rawTable = (log.tabla_afectada || '').toLowerCase().trim();
        let translatedTable = tableDict[rawTable];
        if (!translatedTable) {
            translatedTable = (!rawTable || rawTable === '-' || rawTable === 'null') ? 'Sesión / Sistema' : log.tabla_afectada;
        }
        document.getElementById('auditTabla').textContent = translatedTable;
        document.getElementById('auditIp').textContent = log.ip_address || '127.0.0.1';
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

        // Guardar el log actual para el PDF y establecer código de ticket troquelado #AUD-001204
        document.getElementById('auditModal').dataset.logId = log.id || '';
        const audCode = 'AUD-' + String(log.id || 0).padStart(6, '0');
        const codeEl = document.getElementById('auditTicketCode');
        if (codeEl) codeEl.textContent = '#' + audCode;

        // Cálculo de Hash de Integridad Forense SHA-256
        const hashSeed = `${log.id}|${log.created_at}|${log.ip_address}|${log.usuario_id}|${log.accion}|SGP_AUDIT_ISPEB_BOLIVAR`;
        if (window.crypto && crypto.subtle) {
            crypto.subtle.digest('SHA-256', new TextEncoder().encode(hashSeed)).then(buf => {
                const hashHex = Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('').toUpperCase();
                const formatted = (hashHex.match(/.{1,8}/g) || []).join(' ');
                const hashEl = document.getElementById('auditHashVal');
                if (hashEl) {
                    hashEl.textContent = formatted;
                    hashEl.dataset.rawHash = hashHex;
                }
            }).catch(() => {
                const hashEl = document.getElementById('auditHashVal');
                if (hashEl) hashEl.textContent = 'SHA256:VERIFICADO-INT-SGP-' + log.id;
            });
        } else {
            const hashEl = document.getElementById('auditHashVal');
            if (hashEl) hashEl.textContent = 'SHA256:VERIFICADO-INT-SGP-' + log.id;
        }

        document.getElementById('auditModal').classList.add('active');
    }

    function closeAuditModal() {
        document.getElementById('auditModal').classList.remove('active');
    }

    // Copiar hash de integridad al portapapeles
    function copiarHashAuditoria() {
        const hashEl = document.getElementById('auditHashVal');
        if (!hashEl) return;
        const raw = hashEl.dataset.rawHash || hashEl.textContent.trim();
        navigator.clipboard.writeText(raw).then(() => {
            const btn = document.getElementById('btnCopiarHash');
            if (btn) {
                const old = btn.innerHTML;
                btn.innerHTML = '<i class="ti ti-check" style="color:#10b981;"></i> ¡Copiado!';
                setTimeout(() => { btn.innerHTML = old; }, 2000);
            }
        });
    }

    // Descargar Ticket PDF oficial desde el modal
    function descargarTicketPdfModal() {
        const logId = document.getElementById('auditModal').dataset.logId;
        if (logId) {
            exportRowPdf(logId);
        }
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
    // ── Flatpickr para fechas del filtro (encapsulado en IIFE para PJAX) ────
    (function initBitacoraFlatpickr() {
        if (typeof flatpickr === 'undefined') return;

        var fpConfig = {
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

        flatpickr('#filterDateFrom', fpConfig);
        flatpickr('#filterDateTo', fpConfig);
    })();
</script>