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
     * Get unread notifications for a user, filtered by role.
     * @param int $user_id
     * @param int $role_id  1=Admin, 2=Tutor, 3=Pasante
     * @param int $limit
     */
    public function getUnreadByUser($user_id, $role_id = 1, $limit = 10)
    {
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
     * Mark notification as read
     */
    public function markAsRead($id, $user_id)
    {
        $this->db->query("
            UPDATE notificaciones
            SET leida = 1
            WHERE id = :id AND usuario_id = :user_id
        ");
        
        $this->db->bind(':id', $id);
        $this->db->bind(':user_id', $user_id);
        
        return $this->db->execute();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id)
    {
        $this->db->query("
            UPDATE notificaciones
            SET leida = 1
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
     * Usar para eventos rastreables (PIN reset, etc.) que deben
     * resolverse en todos los destinatarios simultáneamente.
     */
    public function createWithRef($usuario_id, $tipo, $titulo, $mensaje, $url = '#', $referencia_id = null)
    {
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
}
