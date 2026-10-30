<?php
/**
 * NotificationModel
 * Handles database operations for notifications
 */
class NotificationModel
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Tipos exclusivos solo para Admin (rol 1 / superadmin 0).
     * Ni tutores ni pasantes los ven.
     */
    private const ADMIN_ONLY_TYPES = [
        'usuario_creado',
        'perfil_actualizado',
        'alerta_sistema',
        'solicitud_recovery',
    ];

    /**
     * Tipos que los pasantes (rol 3) NO ven.
     * Los tutores sí pueden verlos (solo lectura).
     */
    private const PASANTE_EXCLUDED_TYPES = [
        'usuario_creado',
        'perfil_actualizado',
        'alerta_sistema',
        'solicitud_recovery',
        'solicitud_pin',
        'evaluacion_pendiente',
    ];

    /**
     * Construye la cláusula NOT IN con parámetros nombrados.
     */
    private function buildTypeFilter(array $types, string $prefix = 'ftype'): array
    {
        $named = [];
        $binds = [];
        foreach ($types as $i => $tipo) {
            $key = ":{$prefix}{$i}";
            $named[] = $key;
            $binds[$key] = $tipo;
        }
        return [
            'clause' => 'AND tipo NOT IN (' . implode(',', $named) . ')',
            'binds'  => $binds,
        ];
    }

    /**
     * Devuelve el filtro de tipo según el rol del usuario.
     * rol 0/1 = Admin/SuperAdmin → sin filtro
     * rol 2   = Tutor           → excluye admin_only (NO solicitud_pin)
     * rol 3   = Pasante         → excluye todo lo administrativo
     */
    private function getFilterForRole(int $role_id): array
    {
        if ($role_id <= 1) {
            return ['clause' => '', 'binds' => []]; // Admin ve todo
        }
        if ($role_id === 2) {
            return $this->buildTypeFilter(self::ADMIN_ONLY_TYPES, 'at'); // Tutor: sin admin_only
        }
        return $this->buildTypeFilter(self::PASANTE_EXCLUDED_TYPES, 'pt'); // Pasante: sin nada admin
    }


    /**
     * Comprueba si las notificaciones están activas globalmente en el sistema.
     */
    public function isGloballyActive(): bool
    {
        try {
            $this->db->query("SELECT valor FROM configuracion WHERE clave = 'notificaciones_activas' LIMIT 1");
            $row = $this->db->single();
            return $row ? ((int)$row->valor === 1) : true;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Comprueba si un usuario específico tiene las notificaciones habilitadas.
     */
    public function isUserEnabled(int $userId): bool
    {
        try {
            $this->db->query("SELECT notificaciones_habilitadas FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $row = $this->db->single();
            return $row ? ((int)$row->notificaciones_habilitadas === 1) : true;
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * Alternar estado global de notificaciones.
     */
    public function toggleGlobalNotifications(int $active): bool
    {
        $this->db->query("
            INSERT INTO configuracion (clave, valor, descripcion)
            VALUES ('notificaciones_activas', :val, 'Habilita o pausa globalmente el sistema de notificaciones')
            ON DUPLICATE KEY UPDATE valor = :val2, updated_at = NOW()
        ");
        $val = $active ? '1' : '0';
        $this->db->bind(':val', $val);
        $this->db->bind(':val2', $val);
        return $this->db->execute();
    }

    /**
     * Alternar estado de notificaciones para un usuario específico.
     */
    public function toggleUserNotifications(int $userId, int $enabled): bool
    {
        $this->db->query("UPDATE usuarios SET notificaciones_habilitadas = :hab WHERE id = :uid");
        $this->db->bind(':hab', $enabled ? 1 : 0, PDO::PARAM_INT);
        $this->db->bind(':uid', $userId, PDO::PARAM_INT);
        return $this->db->execute();
    }

    /**
     * Get unread notifications for a user, filtered by role.
     * @param int $user_id
     * @param int $role_id  1=Admin, 2=Tutor, 3=Pasante
     * @param int $limit
     */
    public function getUnreadByUser($user_id, $role_id = 1, $limit = 10)
    {
        if (!$this->isGloballyActive() || !$this->isUserEnabled((int)$user_id)) {
            return [];
        }

        $filter = $this->getFilterForRole((int)$role_id);

        $this->db->query("
            SELECT id, tipo, titulo, mensaje, url, created_at, referencia_id
            FROM notificaciones
            WHERE usuario_id = :user_id
              AND leida = 0
              {$filter['clause']}
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        foreach ($filter['binds'] as $key => $val) {
            $this->db->bind($key, $val);
        }
        return $this->db->resultSet();
    }

    /**
     * Get count of unread notifications, filtered by role.
     */
    public function getCountUnread($user_id, $role_id = 1)
    {
        if (!$this->isGloballyActive() || !$this->isUserEnabled((int)$user_id)) {
            return 0;
        }

        $filter = $this->getFilterForRole((int)$role_id);

        $this->db->query("
            SELECT COUNT(*) as count
            FROM notificaciones
            WHERE usuario_id = :user_id
              AND leida = 0
              {$filter['clause']}
        ");
        $this->db->bind(':user_id', $user_id);
        foreach ($filter['binds'] as $key => $val) {
            $this->db->bind($key, $val);
        }
        $result = $this->db->single();
        return $result ? $result->count : 0;
    }


    /**
     * Mark notification as read and track click interaction
     */
    public function markAsRead($id, $user_id, ?string $ip = null, ?string $dispositivo = null)
    {
        $this->db->query("
            UPDATE notificaciones
            SET leida = 1,
                leido_at = COALESCE(leido_at, NOW()),
                clickeado_at = NOW(),
                ip_click = COALESCE(:ip, ip_click),
                dispositivo = COALESCE(:dispositivo, dispositivo)
            WHERE id = :id AND usuario_id = :user_id
        ");
        
        $this->db->bind(':id', $id);
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':ip', $ip);
        $this->db->bind(':dispositivo', $dispositivo);
        
        return $this->db->execute();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id)
    {
        $this->db->query("
            UPDATE notificaciones
            SET leida = 1,
                leido_at = COALESCE(leido_at, NOW())
            WHERE usuario_id = :user_id AND leida = 0
        ");
        
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->execute();
    }

    /**
     * Create a new notification (sin referencia_id)
     */
    public function create($usuario_id, $tipo, $titulo, $mensaje, $url = '#')
    {
        return $this->createWithRef($usuario_id, $tipo, $titulo, $mensaje, $url, null);
    }

    /**
     * Create a notification con referencia_id opcional.
     * Respeta switch global y preferencia granular del usuario.
     */
    public function createWithRef($usuario_id, $tipo, $titulo, $mensaje, $url = '#', $referencia_id = null)
    {
        if (!$this->isGloballyActive() || !$this->isUserEnabled((int)$usuario_id)) {
            return false;
        }

        $this->db->query("
            INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, url, referencia_id)
            VALUES (:usuario_id, :tipo, :titulo, :mensaje, :url, :ref_id)
        ");
        $this->db->bind(':usuario_id',  $usuario_id);
        $this->db->bind(':tipo',        $tipo);
        $this->db->bind(':titulo',      $titulo);
        $this->db->bind(':mensaje',     $mensaje);
        $this->db->bind(':url',         $url);
        $this->db->bind(':ref_id',      $referencia_id);
        return $this->db->execute();
    }

    /**
     * Marcar como leídas TODAS las notificaciones de un evento específico
     * (por tipo + referencia_id) para todos los usuarios destinatarios.
     * Se llama cuando el admin resuelve el evento (ej: resetea PIN).
     *
     * @param string $tipo         Tipo de notificación (ej: 'solicitud_pin')
     * @param int    $referencia_id ID del pasante u objeto del evento
     */
    public function resolverPorReferencia(string $tipo, int $referencia_id): bool
    {
        $this->db->query("
            UPDATE notificaciones
            SET leida = 1
            WHERE tipo = :tipo
              AND referencia_id = :ref_id
              AND leida = 0
        ");
        $this->db->bind(':tipo',   $tipo);
        $this->db->bind(':ref_id', $referencia_id);
        return $this->db->execute();
    }

    /**
     * Verificar si ya existe notificación del mismo tipo+referencia para un usuario HOY.
     * Evita duplicados en notificaciones de feriado por login múltiple.
     */
    public function existeHoy(int $usuario_id, string $tipo, ?int $referencia_id = null): bool
    {
        $this->db->query("
            SELECT id FROM notificaciones
            WHERE usuario_id = :uid
              AND tipo = :tipo
              AND DATE(created_at) = CURDATE()
              AND (:ref IS NULL OR referencia_id = :ref)
            LIMIT 1
        ");
        $this->db->bind(':uid',  $usuario_id);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':ref',  $referencia_id);
        return (bool)$this->db->single();
    }

    /**
     * Delete old read notifications (cleanup)
     */
    public function deleteOldRead($days = 30)
    {
        $this->db->query("
            DELETE FROM notificaciones
            WHERE leida = 1 AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $this->db->bind(':days', $days);
        return $this->db->execute();
    }

    /**
     * Obtener estadísticas globales y trazabilidad de interacciones para el Administrador.
     */
    public function getAnalytics(): array
    {
        $stats = [
            'total_enviadas'    => 0,
            'total_leidas'      => 0,
            'total_clickeadas'  => 0,
            'tasa_lectura'      => 0.0,
            'tasa_clicks'       => 0.0,
            'global_activo'     => $this->isGloballyActive(),
            'ultimas'           => []
        ];

        try {
            $this->db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN leida = 1 THEN 1 ELSE 0 END) AS leidas,
                    SUM(CASE WHEN clickeado_at IS NOT NULL THEN 1 ELSE 0 END) AS clickeadas
                FROM notificaciones
            ");
            $res = $this->db->single();
            if ($res) {
                $stats['total_enviadas']   = (int)($res->total ?? 0);
                $stats['total_leidas']     = (int)($res->leidas ?? 0);
                $stats['total_clickeadas'] = (int)($res->clickeadas ?? 0);
                if ($stats['total_enviadas'] > 0) {
                    $stats['tasa_lectura'] = round(($stats['total_leidas'] / $stats['total_enviadas']) * 100, 1);
                    $stats['tasa_clicks']  = round(($stats['total_clickeadas'] / $stats['total_enviadas']) * 100, 1);
                }
            }

            // Trazabilidad de últimas interacciones (con usuario, rol, IP y dispositivo)
            $this->db->query("
                SELECT 
                    n.id, n.tipo, n.titulo, n.leida, n.created_at, n.leido_at, n.clickeado_at, n.ip_click, n.dispositivo,
                    u.correo, r.nombre AS rol_nombre,
                    CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS usuario_nombre
                FROM notificaciones n
                LEFT JOIN usuarios u ON n.usuario_id = u.id
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
                ORDER BY n.created_at DESC
                LIMIT 50
            ");
            $rows = $this->db->resultSet();
            $stats['ultimas'] = $rows ? array_map(fn($r) => (array)$r, $rows) : [];
        } catch (\Throwable $e) {
            error_log('[SGP-NOTIF-ANALYTICS] ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * Enviar notificación a todos los administradores activos (rol 0 y 1).
     */
    public function notifyAdmins(string $tipo, string $titulo, string $mensaje, string $url = '#', ?int $referencia_id = null): int
    {
        try {
            $this->db->query("SELECT id FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo'");
            $admins = $this->db->resultSet() ?: [];
            $enviadas = 0;
            foreach ($admins as $admin) {
                if ($this->createWithRef((int)$admin->id, $tipo, $titulo, $mensaje, $url, $referencia_id)) {
                    $enviadas++;
                }
            }
            return $enviadas;
        } catch (\Throwable $e) {
            error_log('[NotificationModel::notifyAdmins] Error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verifica si existen pasantes en estado 'Sin Asignar' y emite una alerta a los administradores
     * si no se ha notificado hoy para evitar spam.
     */
    public function verificarYNotificarPasantesSinAsignar(): int
    {
        try {
            // Contar pasantes sin asignar
            $this->db->query("
                SELECT COUNT(*) AS total
                FROM usuarios u
                JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                WHERE u.rol_id = 3 
                  AND u.estado = 'activo'
                  AND COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
                  AND (dpa.estado_pasantia = 'Sin Asignar' OR dpa.estado_pasantia IS NULL OR dpa.departamento_asignado_id IS NULL)
            ");
            $totalSinAsignar = (int)($this->db->single()->total ?? 0);

            if ($totalSinAsignar <= 0) {
                return 0;
            }

            // Obtener administradores
            $this->db->query("SELECT id FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo'");
            $admins = $this->db->resultSet() ?: [];
            $enviadas = 0;

            foreach ($admins as $admin) {
                $adminId = (int)$admin->id;
                // Verificar si ya se alertó hoy a este admin
                if (!$this->existeHoy($adminId, 'pasantes_sin_asignar')) {
                    $titulo = "⚠️ {$totalSinAsignar} Pasante(s) Sin Asignar";
                    $mensaje = "Hay {$totalSinAsignar} pasante(s) registrado(s) en espera de asignación de departamento o tutor institucional. Pulsa aquí para gestionarlos.";
                    $url = defined('URLROOT') ? (URLROOT . '/asignaciones') : '/asignaciones';
                    
                    if ($this->createWithRef($adminId, 'pasantes_sin_asignar', $titulo, $mensaje, $url)) {
                        $enviadas++;
                    }
                }
            }

            return $enviadas;
        } catch (\Throwable $e) {
            error_log('[NotificationModel::verificarYNotificarPasantesSinAsignar] ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener listado de usuarios con estado de notificaciones para gestión granular.
     */
    public function getUsersList(): array
    {
        try {
            $this->db->query("
                SELECT 
                    u.id, u.correo, u.rol_id, u.notificaciones_habilitadas, u.estado,
                    r.nombre AS rol_nombre,
                    CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_completo
                FROM usuarios u
                LEFT JOIN roles r ON u.rol_id = r.id
                LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
                WHERE u.estado = 'activo'
                ORDER BY u.rol_id ASC, nombre_completo ASC
            ");
            $rows = $this->db->resultSet();
            return $rows ? array_map(fn($r) => (array)$r, $rows) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
