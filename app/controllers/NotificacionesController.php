<?php
/**
 * NotificacionesController — Módulo de Gestión de Alertas y Notificaciones por Correo
 * Sistema de Gestión de Pasantes (SGP) — ISPEB
 *
 * Permite a los Administradores y Superadministradores:
 * - Habilitar/pausar reglas y tipos de correo individuales.
 * - Gestionar roles destinatarios (Pasantes, Tutores, Administradores, Superadministradores).
 * - Definir listas de usuarios incluidos y excluidos específicamente.
 * - Enviar correos de prueba de cada plantilla.
 * - Auditar el historial de despachos de correo en tiempo real.
 */

declare(strict_types=1);

require_once APPROOT . '/lib/MailService.php';

class NotificacionesController extends Controller
{
    private $db;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();
        RoleMiddleware::authorize([0, 1]); // Solo Administrador y Superadministrador

        $this->db = Database::getInstance();
    }

    /**
     * Vista principal: Dashboard y Reglas de Correo
     */
    public function index(): void
    {
        $this->correos();
    }

    /**
     * Módulo de Gestión de Notificaciones y Reglas por Correo
     */
    public function correos(): void
    {
        // 1. Asegurar columna exclusiones_activas (resiliente para entornos locales y Render/Cloud)
        try {
            $this->db->query("SHOW COLUMNS FROM mail_reglas LIKE 'exclusiones_activas'");
            if (!$this->db->single()) {
                $this->db->query("ALTER TABLE mail_reglas ADD COLUMN exclusiones_activas TINYINT(1) NOT NULL DEFAULT 1 AFTER usuarios_excluidos");
                $this->db->execute();
            }
        } catch (\Throwable $e) {
            error_log('[SGP-MAIL] Error asegurando columna exclusiones_activas: ' . $e->getMessage());
        }

        // Obtener estado global de exclusiones
        $globalExclusiones = true;
        try {
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'mail_exclusiones_activas' LIMIT 1");
            $cfgRow = $this->db->single();
            if ($cfgRow && (string)$cfgRow->valor === '0') {
                $globalExclusiones = false;
            }
        } catch (\Throwable $e) {}

        // Asegurar reglas de soporte técnico
        $soporteRules = [
            ['SOPORTE_NUEVO_TICKET', 'Nuevo Ticket en Mesa de Ayuda', 'Notificación a administradores cuando un usuario o pasante reporta una nueva incidencia', 'sistema', 1, '0,1', 1],
            ['SOPORTE_RESPUESTA', 'Respuesta en Chat de Soporte Técnico', 'Notificación enviada al solicitante o administradores al recibirse un nuevo mensaje en el chat', 'sistema', 1, '0,1,2,3', 1],
            ['SOPORTE_ESTADO', 'Actualización de Estado / Resolución de Caso', 'Notificación enviada al solicitante cuando su ticket cambia de estado (Resuelto, En Proceso)', 'sistema', 1, '0,1,2,3', 1]
        ];
        foreach ($soporteRules as $sr) {
            try {
                $this->db->query("SELECT id FROM mail_reglas WHERE codigo_evento = :cod");
                $this->db->bind(':cod', $sr[0]);
                if (!$this->db->single()) {
                    $this->db->query("
                        INSERT INTO mail_reglas (codigo_evento, titulo, descripcion, categoria, activo, enviar_a_roles, exclusiones_activas)
                        VALUES (:cod, :tit, :des, :cat, :act, :rol, :exc)
                    ");
                    $this->db->bind(':cod', $sr[0]);
                    $this->db->bind(':tit', $sr[1]);
                    $this->db->bind(':des', $sr[2]);
                    $this->db->bind(':cat', $sr[3]);
                    $this->db->bind(':act', $sr[4]);
                    $this->db->bind(':rol', $sr[5]);
                    $this->db->bind(':exc', $sr[6]);
                    $this->db->execute();
                }
            } catch (\Throwable $e) {}
        }

        // Obtener todas las reglas de eventos
        $this->db->query("SELECT * FROM mail_reglas ORDER BY FIELD(categoria, 'seguridad', 'asistencia', 'academico', 'sistema') ASC, id ASC");
        $reglas = $this->db->resultSet() ?: [];

        // Decodificar JSON de inclusiones y exclusiones
        foreach ($reglas as $r) {
            $r->usuarios_incluidos_arr = !empty($r->usuarios_incluidos) ? (json_decode($r->usuarios_incluidos, true) ?: []) : [];
            $r->usuarios_excluidos_arr = !empty($r->usuarios_excluidos) ? (json_decode($r->usuarios_excluidos, true) ?: []) : [];
            $r->roles_arr = !empty($r->enviar_a_roles) ? explode(',', $r->enviar_a_roles) : [];
            $r->exclusiones_activas = isset($r->exclusiones_activas) ? (int)$r->exclusiones_activas : 1;
        }

        // 2. Obtener lista completa de usuarios para los selectores de destinatarios
        $this->db->query("
            SELECT u.id, u.cedula, u.correo, u.rol_id,
                   CASE WHEN u.rol_id = 0 THEN 'Super Administrador' ELSE r.nombre END as rol_nombre,
                   TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_completo
            FROM usuarios u
            LEFT JOIN roles r ON r.id = u.rol_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE u.estado = 'activo'
            ORDER BY u.rol_id ASC, nombre_completo ASC
        ");
        $usuarios = $this->db->resultSet() ?: [];

        // 3. Obtener últimos 50 registros del historial de envíos
        $this->db->query("
            SELECT h.*, u.cedula,
                   TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS usuario_nombre
            FROM mail_historial h
            LEFT JOIN usuarios u ON u.id = h.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            ORDER BY h.id DESC
            LIMIT 50
        ");
        $historial = $this->db->resultSet() ?: [];

        // 4. Métricas / KPIs del módulo
        $smtpConfig = MailService::getConfig();
        $totalReglas = count($reglas);
        $activasReglas = 0;
        foreach ($reglas as $r) {
            if (!empty($r->activo)) $activasReglas++;
        }

        $this->db->query("SELECT COUNT(*) AS total_hoy FROM mail_historial WHERE DATE(created_at) = CURDATE()");
        $rowHoy = $this->db->single();
        $totalHoy = (int)($rowHoy->total_hoy ?? 0);

        $this->db->query("SELECT COUNT(*) AS total_fallidos FROM mail_historial WHERE estado = 'fallido' AND DATE(created_at) = CURDATE()");
        $rowFallidos = $this->db->single();
        $totalFallidos = (int)($rowFallidos->total_fallidos ?? 0);

        $data = [
            'title'             => 'Gestión de Alertas y Notificaciones por Correo',
            'reglas'            => $reglas,
            'usuarios'          => $usuarios,
            'historial'         => $historial,
            'smtpConfig'        => $smtpConfig,
            'totalReglas'       => $totalReglas,
            'activasReglas'     => $activasReglas,
            'totalHoy'          => $totalHoy,
            'totalFallidos'     => $totalFallidos,
            'globalExclusiones' => $globalExclusiones
        ];

        $this->view('notificaciones/correos', $data);
    }

    /**
     * AJAX POST: Activar o Pausar una Regla de Envío
     */
    public function toggleRegla(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $activo = (int)($_POST['activo'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador de regla no válido']);
            return;
        }

        try {
            $this->db->query("UPDATE mail_reglas SET activo = :act WHERE id = :id");
            $this->db->bind(':act', $activo);
            $this->db->bind(':id',  $id);
            $this->db->execute();

            $msg = $activo === 1 ? 'Notificación activada para envíos' : 'Notificación pausada globalmente';
            echo json_encode(['success' => true, 'activo' => $activo, 'message' => $msg]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar regla: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX POST: Activar o Pausar el filtro de exclusiones para una regla específica
     */
    public function toggleExclusionesRegla(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $activo = (int)($_POST['activo'] ?? 1);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador de regla no válido']);
            return;
        }

        try {
            $this->db->query("UPDATE mail_reglas SET exclusiones_activas = :act WHERE id = :id");
            $this->db->bind(':act', $activo);
            $this->db->bind(':id',  $id);
            $this->db->execute();

            $msg = $activo === 1 
                ? 'Filtro de exclusiones activado para esta notificación' 
                : 'Filtro de exclusiones pausado (los usuarios excluidos sí recibirán el correo)';
            echo json_encode(['success' => true, 'id' => $id, 'activo' => $activo, 'message' => $msg]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar exclusiones: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX POST: Activar o Pausar el sistema global de exclusiones de correo
     */
    public function toggleExclusionesGlobal(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $activo = (int)($_POST['activo'] ?? 1);

        try {
            $this->db->query("
                INSERT INTO configuracion (clave, valor, descripcion)
                VALUES ('mail_exclusiones_activas', :val, 'Habilita o pausa el bloqueo para los usuarios en listas de exclusión de correo')
                ON DUPLICATE KEY UPDATE valor = :val2, updated_at = NOW()
            ");
            $this->db->bind(':val', (string)$activo);
            $this->db->bind(':val2', (string)$activo);
            $this->db->execute();

            $msg = $activo === 1 
                ? 'Sistema de exclusiones activado globalmente (se respetarán las listas de exclusión)' 
                : 'Sistema de exclusiones pausado (todos los usuarios recibirán correos según su rol)';
            echo json_encode(['success' => true, 'activo' => $activo, 'message' => $msg]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar configuración: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX POST: Guardar configuración de Destinatarios, Roles y Exclusiones
     */
    public function guardarDestinatarios(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Regla no válida']);
            return;
        }

        $roles = $_POST['roles'] ?? [];
        $rolesStr = is_array($roles) ? implode(',', array_map('intval', $roles)) : '';

        $incluidos = $_POST['incluidos'] ?? [];
        $incluidosJson = !empty($incluidos) && is_array($incluidos)
            ? json_encode(array_values(array_unique(array_map('intval', $incluidos))))
            : null;

        $excluidos = $_POST['excluidos'] ?? [];
        $excluidosJson = !empty($excluidos) && is_array($excluidos)
            ? json_encode(array_values(array_unique(array_map('intval', $excluidos))))
            : null;

        $exclusionesActivas = isset($_POST['exclusiones_activas']) ? (int)$_POST['exclusiones_activas'] : 1;

        try {
            $this->db->query("
                UPDATE mail_reglas
                SET enviar_a_roles = :roles,
                    usuarios_incluidos = :inc,
                    usuarios_excluidos = :exc,
                    exclusiones_activas = :exc_act
                WHERE id = :id
            ");
            $this->db->bind(':roles',   $rolesStr);
            $this->db->bind(':inc',     $incluidosJson);
            $this->db->bind(':exc',     $excluidosJson);
            $this->db->bind(':exc_act', $exclusionesActivas);
            $this->db->bind(':id',      $id);
            $this->db->execute();

            echo json_encode(['success' => true, 'message' => 'Destinatarios y reglas guardadas correctamente']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar destinatarios: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX POST: Enviar Correo de Prueba de una Plantilla Específica
     */
    public function probarPlantilla(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $codigo = trim($_POST['codigo_evento'] ?? '');
        $emailDestino = trim($_POST['email_destino'] ?? '');

        if ($emailDestino === '' || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Ingresa una dirección de correo válida para la prueba']);
            return;
        }

        $nombre = Session::get('user_name') ?: 'Administrador SGP';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $subject = '📧 Prueba de Plantilla: ' . $codigo;
        $content = '';
        $badge = 'Diagnóstico';
        $badgeColor = '#2563eb';

        switch ($codigo) {
            case 'NUEVO_EXAMEN':
                $badge = 'Evaluación Rápida';
                $badgeColor = '#16a34a';
                $subject = '📝 [PRUEBA] Nuevo Examen Asignado: Evaluación de Seguridad Informática';
                $content = "
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Esta es una muestra del correo que reciben los <strong>pasantes</strong> cuando la Coordinación de Docencia publica un nuevo examen:</p>
                    <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 16px; font-weight: 800; color: #166534;'>📝 Evaluación de Diagnóstico ISPEB 2026</div>
                        <div style='font-size: 13px; color: #15803d; margin-top: 4px;'><strong>Preguntas:</strong> 10 reactivos &nbsp;|&nbsp; <strong>Límite:</strong> 24 horas</div>
                    </div>
                ";
                break;

            case 'ASISTENCIA_INDIVIDUAL':
                $badge = 'Asistencia en Vivo';
                $badgeColor = '#059669';
                $subject = '⏰ [PRUEBA] Marcaje de Asistencia: Pasante Prueba';
                $content = "
                    <p>Estimado(a) Tutor/Administrador,</p>
                    <p>Le informamos que se ha registrado una nueva marcación en el quiosco institucional:</p>
                    <div style='background-color: #ecfdf5; border: 1.5px solid #a7f3d0; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 13px; color: #065f46;'><strong>Pasante:</strong> Carlos Rodríguez (C.I. 25.123.456)</div>
                        <div style='font-size: 13px; color: #065f46;'><strong>Departamento:</strong> Soporte Técnico</div>
                        <div style='font-size: 13px; color: #065f46;'><strong>Hora de Entrada:</strong> " . date('h:i:s A') . " &nbsp;[Puntual]</div>
                        <div style='font-size: 13px; color: #065f46;'><strong>Método:</strong> Quiosco QR Oficial</div>
                    </div>
                ";
                break;

            case 'ASISTENCIA_RESUMEN_DIARIO':
                $badge = 'Resumen Diario';
                $badgeColor = '#4f46e5';
                $subject = '📊 [PRUEBA] Consolidado Diario de Asistencias — ISPEB (' . date('d/m/Y') . ')';
                $content = "
                    <p>Estimado(a) Director(a) / Administrador,</p>
                    <p>Este es el consolidado de asistencia de pasantes al cierre de la jornada de hoy:</p>
                    <div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 18px 0;'>
                        <div style='background: #eff6ff; padding: 12px; border-radius: 10px; text-align: center; border: 1px solid #bfdbfe;'>
                            <div style='font-size: 20px; font-weight: 800; color: #1e3a8a;'>24</div>
                            <div style='font-size: 11px; color: #60a5fa; font-weight: 700;'>ASISTIERON</div>
                        </div>
                        <div style='background: #fef3c7; padding: 12px; border-radius: 10px; text-align: center; border: 1px solid #fde68a;'>
                            <div style='font-size: 20px; font-weight: 800; color: #92400e;'>2</div>
                            <div style='font-size: 11px; color: #f59e0b; font-weight: 700;'>RETARDOS</div>
                        </div>
                        <div style='background: #fee2e2; padding: 12px; border-radius: 10px; text-align: center; border: 1px solid #fecaca;'>
                            <div style='font-size: 20px; font-weight: 800; color: #991b1b;'>1</div>
                            <div style='font-size: 11px; color: #ef4444; font-weight: 700;'>FALTAS</div>
                        </div>
                    </div>
                ";
                break;

            case 'NUEVA_ASIGNACION':
                $badge = 'Asignación Formal';
                $badgeColor = '#2563eb';
                $subject = '📋 [PRUEBA] Confirmación de Asignación de Pasantía — Soporte Técnico';
                $content = "
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Muestra del correo que recibe el pasante y su tutor cuando se formaliza su asignación de departamento:</p>
                    <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Departamento:</strong> División de Informática y Telecomunicaciones</div>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Tutor Asignado:</strong> Lcdo. Luis Yánez</div>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Fecha de Inicio:</strong> " . date('d/m/Y') . "</div>
                        <div style='font-size: 14px; color: #1e3a8a;'><strong>Meta de Horas:</strong> 1440 horas académicas</div>
                    </div>
                ";
                break;

            case 'EVALUACION_REGISTRADA':
                $badge = 'Evaluación de Desempeño';
                $badgeColor = '#0284c7';
                $subject = '📊 [PRUEBA] Calificación Registrada: 4.85/5 (Lapso II)';
                $content = "
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Muestra del informe de calificación que recibe el pasante al ser evaluado por su tutor:</p>
                    <div style='background-color: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='font-size: 14px; color: #334155; margin-bottom: 6px;'><strong>Etapa Evaluada:</strong> Lapso II — Competencias Técnicas</div>
                        <div style='font-size: 18px; font-weight: 800; color: #0284c7; margin-top: 8px;'>
                            Nota Promedio: <span style='font-size: 22px; color: #0369a1;'>4.85 / 5.00</span>
                        </div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-top: 8px;'><strong>Observaciones del Tutor:</strong> <em>Excelente puntualidad, disposición al trabajo en equipo y dominio de protocolos de salud.</em></div>
                    </div>
                ";
                break;

            case 'PERIODO_CERRADO':
                $badge = '¡Pasantía Culminada!';
                $badgeColor = '#16a34a';
                $subject = '🎓 [PRUEBA] ¡Felicidades! Pasantía Culminada — Período 2026-2027';
                $content = "
                    <p>Estimado(a) <strong>{$nombre}</strong>,</p>
                    <p>Muestra de la notificación que recibe el pasante al completar formalmente su cohorte académica:</p>
                    <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='font-size: 15px; font-weight: 700; color: #166534; margin-bottom: 6px;'>Balance Final de Pasantía</div>
                        <div style='font-size: 14px; color: #15803d; margin-bottom: 4px;'><strong>Horas Cumplidas:</strong> 1440 / 1440 hrs (100%)</div>
                        <div style='font-size: 14px; color: #15803d;'><strong>Estado Académico:</strong> Culminado con Éxito</div>
                    </div>
                ";
                break;

            case 'BLOQUEO_INTENTO_FALLIDO':
                $badge = 'Alerta Crítica';
                $badgeColor = '#dc2626';
                $subject = '🚨 [PRUEBA] Alerta de Seguridad: Bloqueo de Intentos Fallidos';
                $content = "
                    <p>Estimada Administración,</p>
                    <p>Muestra de alerta enviada a administradores cuando se bloquea un acceso o marcaje tras múltiples intentos incorrectos:</p>
                    <div style='background-color: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='font-size: 13.5px; color: #b91c1c; margin-bottom: 4px;'><strong>Origen / Módulo:</strong> Quiosco de Asistencia</div>
                        <div style='font-size: 13.5px; color: #b91c1c; margin-bottom: 4px;'><strong>Identificador / C.I.:</strong> 26.890.123</div>
                        <div style='font-size: 13.5px; color: #b91c1c; margin-bottom: 4px;'><strong>Motivo:</strong> Bloqueo tras 5 intentos fallidos de PIN</div>
                        <div style='font-size: 13.5px; color: #b91c1c;'><strong>Dirección IP:</strong> {$ip}</div>
                    </div>
                ";
                break;

            case 'SOLICITUD_RESET_PIN':
                $badge = 'Soporte de Acceso';
                $badgeColor = '#2563eb';
                $subject = '🔑 [PRUEBA] Solicitud de Restablecimiento de PIN: Pasante Prueba';
                $content = "
                    <p>Estimado(a) Tutor/Administrador,</p>
                    <p>El pasante ha solicitado desde el quiosco el restablecimiento de su PIN de marcaje:</p>
                    <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Pasante:</strong> Carlos Rodríguez &nbsp;|&nbsp; <strong>C.I.:</strong> 25.123.456</div>
                        <div style='font-size: 14px; color: #1e3a8a; margin-bottom: 5px;'><strong>Departamento:</strong> Soporte Técnico</div>
                        <div style='font-size: 14px; color: #1e3a8a;'><strong>Fecha:</strong> " . date('d/m/Y h:i A') . "</div>
                    </div>
                ";
                break;

            case 'BIOMETRIA_VINCULADA':
                $badge = 'Seguridad Bancaria';
                $badgeColor = '#16a34a';
                $subject = '🔐 [PRUEBA] Alerta de Seguridad: Dispositivo Afiliado con Huella (Xiaomi Poco X5 Pro)';
                $refCode = 'BIO-ENROLL-' . date('Ymd') . '-A94F2B10';
                $urlPerfil = (defined('URLROOT') ? rtrim(URLROOT, '/') : '') . '/perfil/ver';
                $urlSoporte = (defined('URLROOT') ? rtrim(URLROOT, '/') : '') . '/soporte';
                $content = "
                    <p style='color: #1e293b; font-size: 15px;'>Estimado(a) <strong>{$nombre}</strong>,</p>
                    <p style='color: #334155; line-height: 1.55;'>
                        Te notificamos que se ha registrado con éxito la <strong>afiliación de un nuevo dispositivo móvil con Acceso Biométrico (Huella Dactilar / Face ID)</strong> en tu cuenta del <strong>Sistema de Gestión de Pasantes (SGP)</strong> del ISPEB:
                    </p>

                    <!-- Ficha Técnica Estilo Bancario -->
                    <div style='background-color: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 14px; padding: 20px; margin: 20px 0;'>
                        <div style='font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;'>
                            <span style='display:inline-block; width:9px; height:9px; border-radius:50%; background:#10b981;'></span>
                            Ficha Técnica del Dispositivo Afiliado
                        </div>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #334155;'>
                            <tr style='border-bottom: 1px solid #e2e8f0;'>
                                <td style='padding: 8px 0; color: #64748b; font-weight: 600; width: 38%;'>📱 Equipo / Modelo:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #0f172a;'>Xiaomi Poco X5 Pro 5G</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e2e8f0;'>
                                <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>💻 Sistema Operativo:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #0f172a;'>Android 14 (HyperOS)</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e2e8f0;'>
                                <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>🌐 Navegador Web:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #0f172a;'>Chrome Mobile (v130.0)</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e2e8f0;'>
                                <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>🔐 Tecnología de Enrolamiento:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #166534;'>
                                    <span style='display:inline-block; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:6px; font-size:12px; font-weight:800;'>Huella Dactilar (WebAuthn / Passkey FIDO2)</span>
                                </td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e2e8f0;'>
                                <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>🌐 Dirección IP de Registro:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #0f172a; font-family: monospace;'>{$ip}</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #e2e8f0;'>
                                <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>📅 Fecha y Hora Oficial:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #0f172a;'>" . date('d/m/Y h:i:s A') . " (Hora VET)</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #64748b; font-weight: 600;'>🔖 Nro. de Referencia:</td>
                                <td style='padding: 8px 0; font-weight: 800; color: #2563eb; font-family: monospace;'>{$refCode}</td>
                            </tr>
                        </table>
                    </div>

                    <p style='color: #334155; line-height: 1.5;'>
                        A partir de este momento puedes iniciar sesión de forma instantánea pulsando el botón <strong>\"Ingresar con Huella Dactilar\"</strong> o registrar tu jornada en el quiosco institucional directamente desde tu celular.
                    </p>

                    <div style='background-color: #fff1f2; border: 1.5px solid #fecdd3; border-radius: 12px; padding: 16px; margin: 22px 0;'>
                        <div style='font-weight: 800; color: #9f1239; font-size: 13.5px; margin-bottom: 6px;'>
                            🛡️ Medidas de Seguridad Bancaria
                        </div>
                        <p style='margin: 0 0 8px; font-size: 12.5px; color: #9f1239; line-height: 1.45;'>
                            <strong>¿Fuiste tú?</strong> Si tú mismo realizaste esta afiliación desde tu teléfono, este correo sirve como comprobante digital oficial. No necesitas responder a este mensaje.
                        </p>
                        <p style='margin: 0; font-size: 12.5px; color: #9f1239; line-height: 1.45;'>
                            <strong>¿NO reconoces este dispositivo?</strong> Si no realizaste esta acción o sospechas de una actividad fraudulenta, tu cuenta puede estar en riesgo. Pulsa inmediatamente para <strong>Desvincular el Dispositivo</strong> desde tu perfil o reporta la incidencia a Soporte Técnico.
                        </p>
                    </div>

                    <div style='margin-top: 22px; text-align: center; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;'>
                        <a href='{$urlPerfil}' style='display:inline-block; background:#162660; color:#ffffff; font-weight:800; font-size:13px; padding:11px 22px; border-radius:10px; text-decoration:none;'>
                            🔐 Administrar Mis Dispositivos
                        </a>
                        <a href='{$urlSoporte}' style='display:inline-block; background:#ffffff; color:#dc2626; border:1.5px solid #fca5a5; font-weight:700; font-size:13px; padding:10px 18px; border-radius:10px; text-decoration:none;'>
                            🚨 Reportar a Soporte
                        </a>
                    </div>
                ";
                break;

            case 'BIOMETRIA_DESAFILIADA':
                $badge = 'Seguridad';
                $badgeColor = '#ea580c';
                $subject = '⚠️ [PRUEBA] Alerta de Seguridad: Dispositivo Biométrico Desvinculado';
                $refCode = 'BIO-REVOKE-' . date('Ymd') . '-C81B43D9';
                $urlPerfil = (defined('URLROOT') ? rtrim(URLROOT, '/') : '') . '/perfil/ver';
                $content = "
                    <p style='color: #1e293b; font-size: 15px;'>Estimado(a) <strong>{$nombre}</strong>,</p>
                    <p style='color: #334155; line-height: 1.55;'>
                        Te notificamos que se ha procesado con éxito la <strong>desvinculación y revocación de acceso biométrico</strong> de un dispositivo móvil en tu cuenta institucional del ISPEB:
                    </p>

                    <div style='background-color: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 14px; padding: 20px; margin: 20px 0;'>
                        <div style='font-size: 12px; font-weight: 800; color: #9a3412; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 14px;'>
                            Detalles de la Desafiliación
                        </div>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #334155;'>
                            <tr style='border-bottom: 1px solid #ffedd5;'>
                                <td style='padding: 8px 0; color: #9a3412; font-weight: 600; width: 38%;'>📱 Equipo Revocado:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #0f172a;'>Xiaomi Poco X5 Pro 5G</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #ffedd5;'>
                                <td style='padding: 8px 0; color: #9a3412; font-weight: 600;'>🌐 Dirección IP:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #0f172a; font-family: monospace;'>{$ip}</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #ffedd5;'>
                                <td style='padding: 8px 0; color: #9a3412; font-weight: 600;'>📅 Fecha de Revocación:</td>
                                <td style='padding: 8px 0; font-weight: 700; color: #0f172a;'>" . date('d/m/Y h:i:s A') . " (VET)</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #9a3412; font-weight: 600;'>🔖 Nro. de Anulación:</td>
                                <td style='padding: 8px 0; font-weight: 800; color: #c2410c; font-family: monospace;'>{$refCode}</td>
                            </tr>
                        </table>
                    </div>

                    <p style='color: #334155; line-height: 1.5;'>
                        A partir de este momento, el sensor biométrico de este equipo ha dejado de tener acceso directo a tu cuenta. Para volver a iniciar sesión deberás utilizar tu correo y tu contraseña.
                    </p>
                ";
                break;

            case 'VINCULACION_DISPOSITIVO_OTP':
                $badge = 'Autorización Bancaria';
                $badgeColor = '#2563eb';
                $subject = '🔐 [PRUEBA] Código de Autorización: 849 201 — Vinculación de Dispositivo';
                $content = "
                    <p style='color: #1e293b; font-size: 15px;'>Hola <strong>{$nombre}</strong>,</p>
                    <p style='color: #334155;'>Hemos recibido una solicitud para <strong>vincular un nuevo dispositivo móvil de confianza</strong> a tu cuenta institucional en el Sistema de Gestión de Pasantes (SGP):</p>
                    
                    <div style='background: #eff6ff; border: 2px dashed #3b82f6; border-radius: 14px; padding: 22px; margin: 22px 0; text-align: center;'>
                        <div style='font-size: 12px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px;'>Código de Autorización Único</div>
                        <div style='font-family: monospace, monospace; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #1d4ed8; margin: 6px 0;'>849 201</div>
                        <div style='font-size: 12px; color: #3b82f6; font-weight: 600; margin-top: 8px;'>⏱️ Válido durante 10 minutos (1 solo uso)</div>
                    </div>

                    <div style='background-color: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 12px; padding: 16px; margin-bottom: 20px;'>
                        <div style='font-size: 12px; font-weight: 800; color: #475569; margin-bottom: 8px; text-transform: uppercase;'>Detalles del Dispositivo:</div>
                        <div style='font-size: 13px; color: #334155; margin-bottom: 4px;'>📱 <strong>Dispositivo:</strong> Xiaomi Poco X5 Pro</div>
                        <div style='font-size: 13px; color: #334155; margin-bottom: 4px;'>🌐 <strong>Dirección IP:</strong> {$ip}</div>
                        <div style='font-size: 13px; color: #334155;'>📅 <strong>Fecha:</strong> " . date('d/m/Y h:i:s A') . "</div>
                    </div>
                ";
                break;

            case 'DISPOSITIVO_VINCULADO':
                $badge = 'Dispositivo Vinculado';
                $badgeColor = '#16a34a';
                $subject = '🛡️ [PRUEBA] Dispositivo Vinculado Exitosamente: Xiaomi Poco X5 Pro';
                $content = "
                    <p style='color: #1e293b; font-size: 15px;'>Hola <strong>{$nombre}</strong>,</p>
                    <p style='color: #334155;'>Te confirmamos que el siguiente dispositivo ha sido <strong>vinculado con éxito</strong> a tu cuenta institucional en el Sistema de Gestión de Pasantes (SGP):</p>
                    
                    <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 14px; padding: 20px; margin: 20px 0;'>
                        <div style='font-size: 13px; font-weight: 800; color: #166534; margin-bottom: 10px; text-transform: uppercase;'>
                            ✅ Dispositivo Frecuente Autorizado
                        </div>
                        <div style='font-size: 13px; color: #166534; margin-bottom: 5px;'>📱 <strong>Dispositivo:</strong> Xiaomi Poco X5 Pro</div>
                        <div style='font-size: 13px; color: #166534; margin-bottom: 5px;'>🔑 <strong>Método:</strong> Huella Dactilar / Biometría WebAuthn</div>
                        <div style='font-size: 13px; color: #166534; margin-bottom: 5px;'>🌐 <strong>Dirección IP:</strong> {$ip}</div>
                        <div style='font-size: 13px; color: #166534;'>📅 <strong>Fecha y Hora:</strong> " . date('d/m/Y h:i:s A') . "</div>
                    </div>
                ";
                break;

            case 'SOPORTE_NUEVO_TICKET':
                $badge = 'Mesa de Ayuda';
                $badgeColor = '#2563eb';
                $subject = '🎧 [PRUEBA] Nuevo Caso #TCK-261106-A101: Error al marcar asistencia';
                $content = "
                    <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                    <p>Se ha generado una nueva incidencia en la <strong>Mesa de Ayuda y Soporte Técnico</strong>:</p>
                    <div style='background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='display:flex; justify-content:space-between; margin-bottom:8px;'>
                            <strong style='color:#1e3a8a;'>Caso: #TCK-261106-A101</strong>
                            <span style='color:#dc2626; font-weight:700;'>Prioridad: Alta</span>
                        </div>
                        <div style='font-size:14px; margin-bottom:6px;'><strong>Solicitante:</strong> Carlos Rodríguez (carlos.pasante@gmail.com)</div>
                        <div style='font-size:14px; margin-bottom:10px;'><strong>Categoría:</strong> Error del Sistema</div>
                        <div style='font-size:13px; color:#475569; background:#fff; padding:12px; border-radius:8px; border:1px solid #cbd5e1;'>
                            No me permite registrar la entrada en el quiosco desde mi teléfono móvil. Muestra alerta de dispositivo bloqueado.
                        </div>
                    </div>
                    <p>Puedes responder y atender este caso directamente en el panel de soporte.</p>
                ";
                break;

            case 'SOPORTE_RESPUESTA':
                $badge = 'Respuesta de Soporte';
                $badgeColor = '#16a34a';
                $subject = '💬 [PRUEBA] Respuesta en Caso #TCK-261106-A101: Error al marcar asistencia';
                $content = "
                    <p>Estimado(a) <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                    <p>Has recibido una nueva respuesta técnica en tu caso <strong>#TCK-261106-A101</strong>:</p>
                    <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='font-size:13px; font-weight:700; color:#1e40af; margin-bottom:8px;'>
                            Atendido por: Soporte ISPEB &bull; Estado actual: En Proceso
                        </div>
                        <div style='font-size:14px; color:#1e293b; background:#fff; padding:14px; border-radius:8px; border:1px solid #93c5fd;'>
                            Hemos verificado el registro de tu dispositivo en la base de datos y restablecido la vinculación biométrica. Por favor intenta escanear nuevamente.
                        </div>
                    </div>
                    <p>Puedes dialogar con el equipo técnico directamente desde la sección de Soporte.</p>
                ";
                break;

            case 'SOPORTE_ESTADO':
                $badge = 'Estado de Soporte';
                $badgeColor = '#059669';
                $subject = '🔔 [PRUEBA] Caso #TCK-261106-A101 Actualizado: Resuelto';
                $content = "
                    <p>Estimado(a) <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                    <p>Tu reporte de soporte técnico <strong>#TCK-261106-A101</strong> ha sido actualizado:</p>
                    <div style='background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 18px; margin: 18px 0;'>
                        <div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;'>
                            <strong style='color:#1e3a8a;'>Caso: #TCK-261106-A101</strong>
                            <span style='background:#059669; color:#fff; font-weight:800; font-size:12px; padding:3px 10px; border-radius:999px;'>
                                Resuelto
                            </span>
                        </div>
                        <div style='font-size:14px; margin-bottom:6px;'><strong>Asunto:</strong> Error al marcar asistencia</div>
                        <div style='font-size:13px; color:#64748b; margin-bottom:10px;'>Actualizado por: División de Telemática</div>
                        <div style='font-size:13px; color:#1e293b; background:#fff; padding:12px; border-radius:8px; border:1px solid #cbd5e1;'>
                            <strong>Nota de resolución:</strong><br>El dispositivo fue reactivado y validado en la sede central con éxito. Caso cerrado.
                        </div>
                    </div>
                ";
                break;

            case 'SEGURIDAD_LOGIN':
            default:
                $badge = 'Alerta de Acceso';
                $badgeColor = '#2563eb';
                $subject = '🛡️ [PRUEBA] Alerta de Seguridad: Inicio de Sesión (Huella Digital)';
                $content = "
                    <p style='color: #1e293b; font-size: 15px;'>Hola <strong>{$nombre}</strong>,</p>
                    <p style='color: #334155; line-height: 1.5;'>Detectamos un inicio de sesión en tu cuenta del Sistema de Gestión de Pasantes (SGP):</p>
                    <div style='background-color: #eff6ff; border: 1.5px solid #bfdbfe; border-radius: 14px; padding: 18px; margin: 18px 0;'>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 5px;'><strong>🔑 Método:</strong> Huella Digital / Biometría Móvil</div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 5px;'><strong>📱 Dispositivo:</strong> Teléfono Android (Sensor Biométrico)</div>
                        <div style='font-size: 13px; color: #1e3a8a; margin-bottom: 5px;'><strong>📅 Fecha y Hora:</strong> " . date('d/m/Y h:i:s A') . "</div>
                        <div style='font-size: 13px; color: #1e3a8a;'><strong>🌐 Dirección IP:</strong> {$ip}</div>
                    </div>
                    <p style='color: #64748b; font-size: 13px;'>Si fuiste tú, puedes ignorar este mensaje. Si no reconoces este acceso, por favor comunícate de inmediato con la administración del sistema o cambia tu contraseña.</p>
                ";
                break;
        }

        $html = MailService::renderBentoTemplate($badge, $badgeColor, $subject, $content, '', '', 'Mensaje de diagnóstico para previsualizar plantilla.');
        $res = MailService::sendDirect($emailDestino, $nombre, $subject, $html, $codigo, (int)Session::get('user_id'));

        if (!empty($res['success'])) {
            echo json_encode([
                'success'   => true,
                'simulated' => !empty($res['simulated']),
                'message'   => !empty($res['simulated'])
                    ? 'Prueba registrada en bitácora (El servidor SMTP está en modo simulación/pendiente de credenciales).'
                    : '¡Correo de prueba enviado con éxito a ' . $emailDestino . '!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $res['message'] ?? 'Error desconocido al enviar el correo de prueba'
            ]);
        }
    }

    /**
     * POST: Guardar Configuración Servidor SMTP / API REST (Administradores y Super Administrador)
     */
    public function guardarSmtp(): void
    {
        $currentRole = (int)Session::get('role_id');
        if (!in_array($currentRole, [0, 1], true)) {
            Session::setFlash('error', '⛔ Acceso denegado: Se requieren permisos administrativos para configurar el servidor de correo institucional.');
            header('Location: ' . URLROOT . '/notificaciones/correos');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/notificaciones/correos');
            exit;
        }

        // Validar CSRF
        if (class_exists('CsrfHelper') && !CsrfHelper::validate($_POST['_csrf'] ?? '')) {
            Session::setFlash('error', '⚠️ Token de seguridad inválido o expirado.');
            header('Location: ' . URLROOT . '/notificaciones/correos');
            exit;
        }

        $smtpActivo    = isset($_POST['smtp_activo']) ? 1 : 0;
        $smtpHost      = trim($_POST['smtp_host'] ?? '');
        $smtpPort      = (int)($_POST['smtp_port'] ?? 587);
        $smtpUser      = trim($_POST['smtp_user'] ?? '');
        $smtpPass      = trim($_POST['smtp_pass'] ?? '');
        $smtpSecure    = trim($_POST['smtp_secure'] ?? 'tls');
        $mailFromName  = trim($_POST['mail_from_name'] ?? 'SGP - Instituto de Salud Pública');
        $mailFromEmail = trim($_POST['mail_from_email'] ?? 'sgp.ispeb@gmail.com');

        $campos = [
            'smtp_activo'     => [$smtpActivo, 'Habilita o pausa el envío de correos SMTP (1=activo, 0=inactivo)'],
            'smtp_host'       => [$smtpHost, 'Host o servidor SMTP para notificaciones'],
            'smtp_port'       => [$smtpPort, 'Puerto del servidor SMTP'],
            'smtp_user'       => [$smtpUser, 'Usuario o correo autenticado SMTP'],
            'smtp_secure'     => [$smtpSecure, 'Cifrado SSL/TLS para SMTP'],
            'mail_from_name'  => [$mailFromName, 'Nombre del remitente institucional'],
            'mail_from_email' => [$mailFromEmail, 'Correo del remitente institucional']
        ];

        if ($smtpPass !== '') {
            $campos['smtp_pass'] = [$smtpPass, 'Contraseña de autenticación SMTP / API Key'];
            if (strpos($smtpPass, 'xkeysib-') === 0) {
                $campos['brevo_api_key'] = [$smtpPass, 'Brevo REST API Key (HTTPS :443)'];
            } elseif (strpos($smtpPass, 're_') === 0) {
                $campos['resend_api_key'] = [$smtpPass, 'Resend REST API Key (HTTPS :443)'];
            }
        }

        try {
            foreach ($campos as $clave => [$val, $desc]) {
                $this->db->query("
                    INSERT INTO configuracion (clave, valor, descripcion)
                    VALUES (:clave, :valor, :desc)
                    ON DUPLICATE KEY UPDATE valor = :valor_up, updated_at = NOW()
                ");
                $this->db->bind(':clave',    $clave);
                $this->db->bind(':valor',    (string)$val);
                $this->db->bind(':desc',     $desc);
                $this->db->bind(':valor_up', (string)$val);
                $this->db->execute();
            }

            if (class_exists('AuditModel')) {
                AuditModel::log('UPDATE_CONFIG_SMTP', 'configuracion', null, ['host' => $smtpHost, 'activo' => $smtpActivo, 'user' => $smtpUser]);
            }

            Session::setFlash('success', '✅ Configuración del servidor de correo SMTP guardada exitosamente.');
        } catch (\Throwable $e) {
            error_log('[SGP-NOTIF] guardarSmtp error: ' . $e->getMessage());
            Session::setFlash('error', '❌ Error al guardar la configuración SMTP: ' . $e->getMessage());
        }

        header('Location: ' . URLROOT . '/notificaciones/correos#smtp');
        exit;
    }

    /**
     * AJAX POST: Probar Conexión SMTP y Plantilla Oficial
     */
    public function probarSmtp(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $destinatario = trim($_POST['email_destino'] ?? '');

        if ($destinatario === '') {
            $uid = (int)Session::get('user_id');
            $this->db->query("SELECT correo FROM usuarios WHERE id = :id LIMIT 1");
            $this->db->bind(':id', $uid);
            $u = $this->db->single();
            $destinatario = $u ? ($u->correo ?? '') : '';
        }

        if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Ingresa una dirección de correo válida para la prueba.']);
            return;
        }

        $nombre = Session::get('user_name') ?: 'Administrador SGP';
        $fecha = date('d/m/Y h:i:s A');

        $htmlContent = "
            <p>Hola <strong>{$nombre}</strong>,</p>
            <p>Este es un <strong>correo de diagnóstico y prueba institucional</strong> emitido por el Sistema de Gestión de Pasantes (SGP) del Instituto de Salud Pública del Estado Bolívar.</p>
            <div style='background-color: #f0fdf4; border: 1.5px solid #86efac; border-radius: 12px; padding: 16px; margin: 18px 0;'>
                <div style='font-size: 14px; font-weight: 800; color: #166534; margin-bottom: 6px;'>✅ Conexión SMTP Exitosa</div>
                <div style='font-size: 13px; color: #15803d;'>El servidor de correo institucional se encuentra correctamente configurado y listo para despachar notificaciones automáticas con banners y logos oficiales.</div>
                <div style='font-size: 12px; color: #166534; margin-top: 8px;'><strong>Fecha y Hora de Emisión:</strong> {$fecha}</div>
            </div>
            <p style='color: #64748b; font-size: 13px;'>Si recibiste este mensaje, la autenticación SMTP y los protocolos de entrega funcionan óptimamente.</p>
        ";

        $html = MailService::renderBentoTemplate(
            'Prueba de Conexión',
            '#16a34a',
            'Diagnóstico SMTP — SGP',
            $htmlContent,
            '',
            '',
            'Mensaje emitido para validar el canal de comunicación SMTP.'
        );

        $res = MailService::send($destinatario, $nombre, '🧪 Prueba de Conexión SMTP — SGP', $html, '', 'TEST');

        if (!empty($res['success'])) {
            $isSimulated = !empty($res['simulated']);
            echo json_encode([
                'success' => true,
                'simulated' => $isSimulated,
                'message' => $isSimulated
                    ? 'Modo Simulación: El servicio SMTP no está activo o faltan credenciales en la base de datos (revisa el log).'
                    : "¡Excelente! Correo de prueba enviado con éxito a: {$destinatario}."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fallo al transmitir vía SMTP: ' . ($res['message'] ?? 'Error desconocido')
            ]);
        }
    }

    /**
     * POST /notificaciones/reintentar
     * Reintenta el despacho individual de un correo fallido
     */
    public function reintentar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de correo inválido']);
            return;
        }

        $res = MailService::reintentarDespacho($id);
        echo json_encode($res);
    }

    /**
     * POST /notificaciones/reintentarTodosFallidos
     * Reintenta en lote todos los correos fallidos de las últimas 48 horas
     */
    public function reintentarTodosFallidos(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        try {
            $this->db->query("
                SELECT id FROM mail_historial 
                WHERE estado = 'fallido' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                ORDER BY id DESC 
                LIMIT 20
            ");
            $fallidos = $this->db->resultSet() ?: [];

            if (empty($fallidos)) {
                echo json_encode(['success' => true, 'message' => 'No hay correos fallidos recientes para reintentar.', 'total' => 0]);
                return;
            }

            $exitosos = 0;
            $errores = 0;
            $mensajesError = [];

            foreach ($fallidos as $f) {
                $res = MailService::reintentarDespacho((int)$f->id);
                if (!empty($res['success'])) {
                    $exitosos++;
                } else {
                    $errores++;
                    if (count($mensajesError) < 2) {
                        $mensajesError[] = $res['message'] ?? 'Error de conexión';
                    }
                }
            }

            $msg = "Reintento finalizado: {$exitosos} correo(s) enviado(s) con éxito.";
            if ($errores > 0) {
                $msg .= " {$errores} fallaron (" . implode(', ', $mensajesError) . ").";
            }

            echo json_encode([
                'success' => $exitosos > 0,
                'exitosos' => $exitosos,
                'errores' => $errores,
                'message' => $msg
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al procesar reintentos: ' . $e->getMessage()]);
        }
    }
}

