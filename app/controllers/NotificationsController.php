<?php
/**
 * NotificationsController
 * Handles notification API endpoints
 */
class NotificationsController extends Controller
{
    private $notificationModel;

    public function __construct()
    {
        // Start output buffering to catch any PHP warnings/notices
        ob_start();
        
        // Security - Check authentication FIRST
        Session::start();
        AuthMiddleware::require();
        
        // ✅ REMOVED: AuthMiddleware::verificarEstado() 
        // RAZÓN: Este endpoint es API JSON, no debe redirigir a HTML
        // La verificación de estado se hace en páginas normales, no en APIs
        
        // Then send cache control headers
        CacheControl::noCache();
        
        // Load model
        $config = require '../app/config/config.php';
        $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        $this->notificationModel = new NotificationModel($db);
    }

    /**
     * Default endpoint to prevent router crashes
     */
    public function index()
    {
        $this->jsonResponse(false, 'Endpoint no válido');
    }

    /**
     * Get unread notifications (AJAX)
     */
    public function getUnread()
    {
        try {
            $user_id = Session::get('user_id');
            $role_id = Session::get('role_id');
            
            if (!$user_id) {
                $this->jsonResponse(false, 'Usuario no autenticado');
            }

            $notifications = $this->notificationModel->getUnreadByUser($user_id, $role_id);
            $count = $this->notificationModel->getCountUnread($user_id, $role_id);

            // Format timestamps
            if ($notifications) {
                foreach ($notifications as &$notification) {
                    $notification->time_ago = $this->timeAgo($notification->created_at);
                }
            } else {
                $notifications = [];
            }

            // [REMOVED] Dynamic system alerts were moved to Dashboard (Event-Driven only architecture)

            $this->jsonResponse(true, 'Notificaciones obtenidas', [
                'count' => $count,
                'notifications' => $notifications
            ]);
        } catch (Exception $e) {
            // Log error for debugging
            // [FIX-C2] Detalle de excepción solo en log — mensaje genérico al cliente
            error_log('[SGP-NOTIF] getUnread() Error: ' . $e->getMessage());
            $this->jsonResponse(false, 'Error al obtener notificaciones.');
        }
    }


    /**
     * Mark notification as read (AJAX)
     */
    public function markAsRead($id = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $user_id = Session::get('user_id');
        
        if (!$id) {
            $this->jsonResponse(false, 'ID de notificación requerido');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $dispositivo = $this->parseUserDevice();

        if ($this->notificationModel->markAsRead($id, $user_id, $ip, $dispositivo)) {
            $this->jsonResponse(true, 'Notificación marcada como leída');
        } else {
            $this->jsonResponse(false, 'Error al marcar notificación');
        }
    }

    /**
     * Identificar tipo de dispositivo desde User-Agent
     */
    private function parseUserDevice(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($ua)) return 'Desconocido';
        if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) {
            return 'Móvil';
        } elseif (stripos($ua, 'Tablet') !== false || stripos($ua, 'iPad') !== false) {
            return 'Tablet';
        } else {
            return 'Escritorio';
        }
    }

    /**
     * Métricas de entrega, lectura y tracking de clics (solo Administrador/SuperAdmin)
     * GET /notifications/analytics
     */
    public function analytics(): void
    {
        $role_id = (int)Session::get('role_id');
        if (!in_array($role_id, [0, 1])) {
            $this->jsonResponse(false, 'Sin permiso');
        }

        $stats = $this->notificationModel->getAnalytics();
        $this->jsonResponse(true, 'ok', ['analytics' => $stats]);
    }

    /**
     * Alternar estado global de notificaciones
     * POST /notifications/toggleGlobal
     */
    public function toggleGlobal(): void
    {
        $role_id = (int)Session::get('role_id');
        if (!in_array($role_id, [0, 1])) {
            $this->jsonResponse(false, 'Sin permiso');
        }

        $this->verifyCsrf();
        $activo = isset($_POST['activo']) && (int)$_POST['activo'] === 1 ? 1 : 0;
        $res = $this->notificationModel->toggleGlobalNotifications($activo);

        if (class_exists('AuditModel')) {
            AuditModel::log('UPDATE_CONFIG', "Notificaciones del sistema " . ($activo ? 'reanudadas' : 'pausadas globalmente'));
        }

        $this->jsonResponse($res, $res ? 'Estado de notificaciones actualizado' : 'Error al actualizar estado', [
            'activo' => $activo
        ]);
    }

    /**
     * Alternar estado de notificaciones para un usuario específico
     * POST /notifications/toggleUser
     */
    public function toggleUser(): void
    {
        $role_id = (int)Session::get('role_id');
        if (!in_array($role_id, [0, 1])) {
            $this->jsonResponse(false, 'Sin permiso');
        }

        $this->verifyCsrf();
        $userId = (int)($_POST['usuario_id'] ?? 0);
        $habilitado = isset($_POST['habilitado']) && (int)$_POST['habilitado'] === 1 ? 1 : 0;

        if ($userId <= 0) {
            $this->jsonResponse(false, 'ID de usuario requerido');
        }

        $res = $this->notificationModel->toggleUserNotifications($userId, $habilitado);

        if (class_exists('AuditModel')) {
            AuditModel::log('UPDATE_CONFIG', "Notificaciones para usuario ID $userId " . ($habilitado ? 'habilitadas' : 'silenciadas'));
        }

        $this->jsonResponse($res, $res ? 'Preferencia de usuario actualizada' : 'Error al actualizar preferencia');
    }

    /**
     * Mark all notifications as read (AJAX)
     */
    public function markAllAsRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $user_id = Session::get('user_id');

        if ($this->notificationModel->markAllAsRead($user_id)) {
            $this->jsonResponse(true, 'Todas las notificaciones marcadas como leídas');
        } else {
            $this->jsonResponse(false, 'Error al marcar notificaciones');
        }
    }

    /**
     * Datos para notificaciones de escritorio (solo Administrador/SuperAdmin)
     * GET /notifications/getDesktopData?ultimo_id=<int>
     */
    public function getDesktopData()
    {
        $role_id = (int) Session::get('role_id');
        if (!in_array($role_id, [0, 1])) {
            $this->jsonResponse(false, 'Sin permiso');
        }

        try {
            $db  = Database::getInstance();
            $hoy = date('Y-m-d');

            // ── ¿Es día hábil? (no feriado no-laborable, no fin de semana) ────────
            $diaSemana  = (int) date('N'); // 6=Sábado, 7=Domingo
            $esFeriado  = false;
            if ($diaSemana < 6) {
                $db->query("SELECT COUNT(*) AS cnt FROM dias_feriados WHERE fecha = :fecha AND es_laborable = 0");
                $db->bind(':fecha', $hoy);
                $rowF      = $db->single();
                $esFeriado = $rowF && (int)$rowF->cnt > 0;
            }
            $esDiaHabil = ($diaSemana < 6) && !$esFeriado;

            // ── Nuevas asistencias vía Kiosco ───────────────────────────────────
            $ultimoId = max(0, (int) ($_GET['ultimo_id'] ?? 0));
            $nuevas   = [];
            if ($esDiaHabil && $ultimoId >= 0) {
                $db->query("
                    SELECT a.id,
                           CONCAT(dp.nombres, ' ', dp.apellidos) AS nombre,
                           TIME_FORMAT(a.hora_registro, '%h:%i %p') AS hora
                    FROM   asistencias a
                    JOIN   datos_personales dp ON a.pasante_id = dp.usuario_id
                    WHERE  a.id > :uid
                      AND  a.metodo = 'Kiosco'
                      AND  a.fecha  = :fecha
                    ORDER  BY a.id ASC
                    LIMIT  10
                ");
                $db->bind(':uid',   $ultimoId, PDO::PARAM_INT);
                $db->bind(':fecha', $hoy);
                $rows = $db->resultSet();
                $nuevas = $rows ? array_map(fn($r) => (array)$r, $rows) : [];
            }

            // ── Pasantes sin tutor asignado (estado Activo) ─────────────────────
            $db->query("SELECT COUNT(*) AS cnt FROM datos_pasante WHERE tutor_id IS NULL AND estado_pasantia = 'Activo'");
            $rowP       = $db->single();
            $sinAsignar = $rowP ? (int)$rowP->cnt : 0;

            // ── Próximo feriado no-laborable ─────────────────────────────────────
            $db->query("
                SELECT fecha, nombre,
                       DATEDIFF(fecha, CURDATE()) AS dias_restantes
                FROM   dias_feriados
                WHERE  fecha >= CURDATE()
                  AND  es_laborable = 0
                ORDER  BY fecha ASC
                LIMIT  1
            ");
            $rowH           = $db->single();
            $proximoFeriado = $rowH ? (array)$rowH : null;

            $this->jsonResponse(true, 'ok', [
                'es_dia_habil'        => $esDiaHabil,
                'nuevas_asistencias'  => $nuevas,
                'pasantes_sin_asignar'=> $sinAsignar,
                'proximo_feriado'     => $proximoFeriado,
            ]);
        } catch (Exception $e) {
            error_log('[SGP-DESKTOP-NOTIF] ' . $e->getMessage());
            $this->jsonResponse(false, 'Error interno');
        }
    }

    /**
     * Próximo feriado — para cualquier usuario autenticado.
     * GET /notifications/getNextHoliday
     */
    public function getNextHoliday(): void
    {
        try {
            $db = Database::getInstance();
            $db->query("
                SELECT fecha, nombre,
                       DATEDIFF(fecha, CURDATE()) AS dias_restantes
                FROM   dias_feriados
                WHERE  fecha >= CURDATE()
                  AND  es_laborable = 0
                ORDER  BY fecha ASC
                LIMIT  1
            ");
            $row = $db->single();
            $this->jsonResponse(true, 'ok', [
                'proximo_feriado' => $row ? (array)$row : null,
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(false, 'Error interno');
        }
    }

    /**
     * Helper: Convert timestamp to "time ago" format
     */
    private function timeAgo($timestamp)
    {
        $time = strtotime($timestamp);
        $diff = time() - $time;

        if ($diff < 60) {
            return 'Hace ' . $diff . ' segundos';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return 'Hace ' . $mins . ($mins == 1 ? ' minuto' : ' minutos');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return 'Hace ' . $hours . ($hours == 1 ? ' hora' : ' horas');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return 'Hace ' . $days . ($days == 1 ? ' día' : ' días');
        } else {
            return date('d/m/Y H:i', $time);
        }
    }

    /**
     * JSON response helper
     */
    private function jsonResponse($success, $message, $data = null)
    {
        // Clear any buffered output (warnings, notices, etc.)
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response = array_merge($response, $data);
        }
        
        echo json_encode($response);
        exit;
    }
}
