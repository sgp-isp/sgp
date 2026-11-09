<?php
declare(strict_types=1);

/**
 * SoporteModel
 * Manejo de tickets, casos y chat de soporte técnico (HelpDesk ISPEB).
 */
class SoporteModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->checkSchema();
    }

    /**
     * Asegura que las tablas de soporte existan en la base de datos (auto-migración)
     */
    private function checkSchema(): void
    {
        try {
            // Tabla principal de tickets
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `soporte_tickets` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `codigo` varchar(30) NOT NULL,
                    `usuario_id` int(11) DEFAULT NULL,
                    `nombre` varchar(150) NOT NULL,
                    `correo` varchar(150) NOT NULL,
                    `telefono` varchar(50) DEFAULT NULL,
                    `categoria` enum('Error del Sistema','Problema de Acceso','Duda Operativa','Sugerencia','Otro') NOT NULL DEFAULT 'Error del Sistema',
                    `prioridad` enum('Baja','Media','Alta','Urgente') NOT NULL DEFAULT 'Media',
                    `asunto` varchar(200) NOT NULL,
                    `mensaje` text NOT NULL,
                    `estado` enum('Abierto','En Proceso','Resuelto','Cerrado') NOT NULL DEFAULT 'Abierto',
                    `respuesta_soporte` text DEFAULT NULL,
                    `respondido_por` int(11) DEFAULT NULL,
                    `respondido_at` datetime DEFAULT NULL,
                    `ip` varchar(45) DEFAULT NULL,
                    `guest_token` varchar(64) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `codigo` (`codigo`),
                    KEY `idx_estado` (`estado`),
                    KEY `idx_usuario` (`usuario_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
            $this->db->execute();

            // Migración suave si la columna guest_token aún no existe
            try {
                $this->db->query("SELECT `guest_token` FROM `soporte_tickets` LIMIT 1");
                $this->db->execute();
            } catch (\Throwable $mEx) {
                try {
                    $this->db->query("ALTER TABLE `soporte_tickets` ADD COLUMN `guest_token` varchar(64) DEFAULT NULL");
                    $this->db->execute();
                } catch (\Throwable $mEx2) {
                    // Ya existe o error DDL
                }
            }

            try {
                $this->db->query("ALTER TABLE `soporte_tickets` ADD INDEX `idx_guest_token` (`guest_token`)");
                $this->db->execute();
            } catch (\Throwable $mEx3) {
                // Ya existe el índice
            }

            // Tabla de mensajes del chat
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `soporte_ticket_mensajes` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `ticket_id` int(11) NOT NULL,
                    `usuario_id` int(11) DEFAULT NULL,
                    `remitente_tipo` ENUM('usuario', 'soporte', 'sistema') NOT NULL DEFAULT 'usuario',
                    `remitente_nombre` varchar(150) NOT NULL,
                    `mensaje` text NOT NULL,
                    `adjunto` varchar(255) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_ticket_id` (`ticket_id`),
                    KEY `idx_usuario_id` (`usuario_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
            $this->db->execute();
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE-SCHEMA] ' . $e->getMessage());
        }
    }

    /**
     * Obtener métricas / KPIs globales de tickets
     */
    public function getKpis(): object
    {
        try {
            $this->db->query("
                SELECT 
                    COUNT(*) AS total_tickets,
                    SUM(CASE WHEN estado = 'Abierto' THEN 1 ELSE 0 END) AS abiertos,
                    SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) AS en_proceso,
                    SUM(CASE WHEN estado = 'Resuelto' THEN 1 ELSE 0 END) AS resueltos,
                    SUM(CASE WHEN estado = 'Cerrado' THEN 1 ELSE 0 END) AS cerrados
                FROM soporte_tickets
            ");
            return $this->db->single() ?: (object)[
                'total_tickets' => 0,
                'abiertos'      => 0,
                'en_proceso'    => 0,
                'resueltos'     => 0,
                'cerrados'      => 0
            ];
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] getKpis error: ' . $e->getMessage());
            return (object)[
                'total_tickets' => 0,
                'abiertos'      => 0,
                'en_proceso'    => 0,
                'resueltos'     => 0,
                'cerrados'      => 0
            ];
        }
    }

    /**
     * Obtener lista de tickets para administración con filtros
     */
    public function getTicketsAdmin(?string $estado = null, ?string $prioridad = null, ?string $busqueda = null): array
    {
        try {
            $sql = "
                SELECT 
                    t.*,
                    COALESCE(dp.nombres, u.cedula, t.nombre) AS solicitante_nombres,
                    COALESCE(dp.apellidos, '') AS solicitante_apellidos,
                    u.cedula AS solicitante_cedula,
                    u.rol_id AS solicitante_rol_id,
                    d.nombre AS departamento_nombre,
                    COALESCE(admin_p.nombres, 'Equipo Soporte') AS respondido_por_nombre,
                    TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_transcurridas,
                    (SELECT COUNT(*) FROM soporte_ticket_mensajes m WHERE m.ticket_id = t.id) AS total_mensajes
                FROM soporte_tickets t
                LEFT JOIN usuarios u ON u.id = t.usuario_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                LEFT JOIN departamentos d ON d.id = u.departamento_id
                LEFT JOIN usuarios admin_u ON admin_u.id = t.respondido_por
                LEFT JOIN datos_personales admin_p ON admin_p.usuario_id = admin_u.id
                WHERE 1=1
            ";

            $params = [];

            if (!empty($estado) && $estado !== 'Todos') {
                if ($estado === 'Activos') {
                    $sql .= " AND t.estado IN ('Abierto', 'En Proceso')";
                } else {
                    $sql .= " AND t.estado = :estado";
                    $params[':estado'] = $estado;
                }
            }

            if (!empty($prioridad) && $prioridad !== 'Todas') {
                $sql .= " AND t.prioridad = :prioridad";
                $params[':prioridad'] = $prioridad;
            }

            if (!empty($busqueda)) {
                $sql .= " AND (
                    t.codigo LIKE :q 
                    OR t.asunto LIKE :q 
                    OR t.nombre LIKE :q 
                    OR t.correo LIKE :q 
                    OR u.cedula LIKE :q
                )";
                $params[':q'] = "%{$busqueda}%";
            }

            $sql .= " ORDER BY FIELD(t.estado, 'Abierto', 'En Proceso', 'Resuelto', 'Cerrado') ASC, t.created_at DESC";

            $this->db->query($sql);
            foreach ($params as $k => $v) {
                $this->db->bind($k, $v);
            }

            return $this->db->resultSet() ?: [];
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] getTicketsAdmin error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tickets creados por un usuario específico (para pasantes y usuarios regulares)
     */
    public function getTicketsUsuario(int $usuarioId): array
    {
        try {
            $this->db->query("
                SELECT 
                    t.*,
                    TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_transcurridas,
                    (SELECT COUNT(*) FROM soporte_ticket_mensajes m WHERE m.ticket_id = t.id) AS total_mensajes
                FROM soporte_tickets t
                WHERE t.usuario_id = :uid
                ORDER BY t.created_at DESC
            ");
            $this->db->bind(':uid', $usuarioId);
            return $this->db->resultSet() ?: [];
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] getTicketsUsuario error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener un ticket por ID con datos completos del usuario
     */
    public function getTicketPorId(int $id): ?object
    {
        try {
            $this->db->query("
                SELECT 
                    t.*,
                    COALESCE(dp.nombres, t.nombre) AS solicitante_nombres,
                    COALESCE(dp.apellidos, '') AS solicitante_apellidos,
                    u.cedula AS solicitante_cedula,
                    u.rol_id AS solicitante_rol_id,
                    COALESCE(u.correo, t.correo) AS solicitante_email,
                    d.nombre AS departamento_nombre,
                    COALESCE(admin_p.nombres, 'Equipo Soporte') AS respondido_por_nombre,
                    TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_transcurridas
                FROM soporte_tickets t
                LEFT JOIN usuarios u ON u.id = t.usuario_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                LEFT JOIN departamentos d ON d.id = u.departamento_id
                LEFT JOIN usuarios admin_u ON admin_u.id = t.respondido_por
                LEFT JOIN datos_personales admin_p ON admin_p.usuario_id = admin_u.id
                WHERE t.id = :id
                LIMIT 1
            ");
            $this->db->bind(':id', $id);
            $res = $this->db->single();
            return $res ?: null;
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] getTicketPorId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener hilo cronológico de mensajes del chat de un ticket
     */
    public function getMensajes(int $ticketId): array
    {
        try {
            $this->db->query("
                SELECT 
                    m.*,
                    u.rol_id AS remitente_rol_id
                FROM soporte_ticket_mensajes m
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.ticket_id = :tid
                ORDER BY m.created_at ASC, m.id ASC
            ");
            $this->db->bind(':tid', $ticketId);
            return $this->db->resultSet() ?: [];
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] getMensajes error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener mensajes nuevos posteriores a un ID (para polling en tiempo real estilo Messenger/Steam)
     */
    public function getMensajesNuevos(int $ticketId, int $afterId): array
    {
        try {
            $this->db->query("
                SELECT 
                    m.*,
                    u.rol_id AS remitente_rol_id
                FROM soporte_ticket_mensajes m
                LEFT JOIN usuarios u ON u.id = m.usuario_id
                WHERE m.ticket_id = :tid AND m.id > :after_id
                ORDER BY m.created_at ASC, m.id ASC
            ");
            $this->db->bind(':tid', $ticketId);
            $this->db->bind(':after_id', $afterId);
            return $this->db->resultSet() ?: [];
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] getMensajesNuevos error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear un nuevo ticket de soporte y registrar el primer mensaje
     */
    public function crearTicket(array $data): array
    {
        $codigo = '#TCK-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $guestToken = !empty($data['guest_token']) ? (string)$data['guest_token'] : bin2hex(random_bytes(16));

        // Asegurar que las tablas existan
        $this->checkSchema();

        // Comprobar de forma segura si la columna guest_token existe
        $colExiste = false;
        try {
            $this->db->query("SELECT `guest_token` FROM `soporte_tickets` LIMIT 1");
            $this->db->execute();
            $colExiste = true;
        } catch (\Throwable $e) {
            try {
                $this->db->query("ALTER TABLE `soporte_tickets` ADD COLUMN `guest_token` varchar(64) DEFAULT NULL");
                $this->db->execute();
                $colExiste = true;
            } catch (\Throwable $e2) {
                $colExiste = false;
            }
        }

        if ($colExiste) {
            $this->db->query("
                INSERT INTO soporte_tickets 
                    (codigo, usuario_id, nombre, correo, telefono, categoria, prioridad, asunto, mensaje, estado, ip, guest_token, created_at)
                VALUES 
                    (:codigo, :usuario_id, :nombre, :correo, :telefono, :categoria, :prioridad, :asunto, :mensaje, 'Abierto', :ip, :guest_token, NOW())
            ");
            $this->db->bind(':guest_token', $guestToken);
        } else {
            $this->db->query("
                INSERT INTO soporte_tickets 
                    (codigo, usuario_id, nombre, correo, telefono, categoria, prioridad, asunto, mensaje, estado, ip, created_at)
                VALUES 
                    (:codigo, :usuario_id, :nombre, :correo, :telefono, :categoria, :prioridad, :asunto, :mensaje, 'Abierto', :ip, NOW())
            ");
        }

        $this->db->bind(':codigo',       $codigo);
        $this->db->bind(':usuario_id',   !empty($data['usuario_id']) ? (int)$data['usuario_id'] : null);
        $this->db->bind(':nombre',       $data['nombre'] ?? 'Usuario');
        $this->db->bind(':correo',       $data['correo'] ?? '');
        $this->db->bind(':telefono',     $data['telefono'] ?? null);
        $this->db->bind(':categoria',    $data['categoria'] ?? 'Error del Sistema');
        $this->db->bind(':prioridad',    $data['prioridad'] ?? 'Media');
        $this->db->bind(':asunto',       $data['asunto'] ?? 'Sin Asunto');
        $this->db->bind(':mensaje',      $data['mensaje'] ?? '');
        $this->db->bind(':ip',           $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        try {
            $this->db->execute();
            $ticketId = (int)$this->db->lastInsertId();
        } catch (\Throwable $insEx) {
            error_log('[SGP-SOPORTE-INSERT-FAIL] ' . $insEx->getMessage());
            // Fallback de emergencia sin guest_token si falló la inserción principal
            if ($colExiste) {
                $this->db->query("
                    INSERT INTO soporte_tickets 
                        (codigo, usuario_id, nombre, correo, telefono, categoria, prioridad, asunto, mensaje, estado, ip, created_at)
                    VALUES 
                        (:codigo, :usuario_id, :nombre, :correo, :telefono, :categoria, :prioridad, :asunto, :mensaje, 'Abierto', :ip, NOW())
                ");
                $this->db->bind(':codigo',       $codigo);
                $this->db->bind(':usuario_id',   !empty($data['usuario_id']) ? (int)$data['usuario_id'] : null);
                $this->db->bind(':nombre',       $data['nombre'] ?? 'Usuario');
                $this->db->bind(':correo',       $data['correo'] ?? '');
                $this->db->bind(':telefono',     $data['telefono'] ?? null);
                $this->db->bind(':categoria',    $data['categoria'] ?? 'Error del Sistema');
                $this->db->bind(':prioridad',    $data['prioridad'] ?? 'Media');
                $this->db->bind(':asunto',       $data['asunto'] ?? 'Sin Asunto');
                $this->db->bind(':mensaje',      $data['mensaje'] ?? '');
                $this->db->bind(':ip',           $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                $this->db->execute();
                $ticketId = (int)$this->db->lastInsertId();
            } else {
                throw $insEx;
            }
        }

        // Registrar el primer mensaje en el historial del chat
        if ($ticketId > 0 && !empty($data['mensaje'])) {
            $this->agregarMensaje(
                $ticketId,
                !empty($data['usuario_id']) ? (int)$data['usuario_id'] : null,
                'usuario',
                $data['nombre'] ?? 'Usuario',
                $data['mensaje']
            );
        }

        return [
            'id'          => $ticketId,
            'codigo'      => $codigo,
            'guest_token' => $guestToken
        ];
    }

    /**
     * Agregar un mensaje al chat del ticket
     */
    public function agregarMensaje(
        int $ticketId,
        ?int $usuarioId,
        string $remitenteTipo,
        string $remitenteNombre,
        string $mensaje,
        ?string $adjunto = null
    ): int {
        try {
            $this->db->query("
                INSERT INTO soporte_ticket_mensajes
                    (ticket_id, usuario_id, remitente_tipo, remitente_nombre, mensaje, adjunto, created_at)
                VALUES
                    (:tid, :uid, :tipo, :nombre, :msg, :adj, NOW())
            ");
            $this->db->bind(':tid',    $ticketId);
            $this->db->bind(':uid',    $usuarioId);
            $this->db->bind(':tipo',   $remitenteTipo);
            $this->db->bind(':nombre', $remitenteNombre);
            $this->db->bind(':msg',    $mensaje);
            $this->db->bind(':adj',    $adjunto);
            $this->db->execute();
            $msgId = (int)$this->db->lastInsertId();

            // Si el remitente es soporte técnico, actualizar respuesta_soporte y respondido_por en la cabecera
            if ($remitenteTipo === 'soporte') {
                $this->db->query("
                    UPDATE soporte_tickets
                    SET respuesta_soporte = :resp,
                        respondido_por = :admin_id,
                        respondido_at = NOW(),
                        estado = CASE WHEN estado = 'Abierto' THEN 'En Proceso' ELSE estado END
                    WHERE id = :tid
                ");
                $this->db->bind(':resp',     $mensaje);
                $this->db->bind(':admin_id', $usuarioId);
                $this->db->bind(':tid',      $ticketId);
                $this->db->execute();
            } else {
                // Si el usuario envió un mensaje en un ticket resuelto, se reabre a En Proceso
                $this->db->query("
                    UPDATE soporte_tickets
                    SET estado = CASE WHEN estado IN ('Resuelto', 'Cerrado') THEN 'En Proceso' ELSE estado END
                    WHERE id = :tid
                ");
                $this->db->bind(':tid', $ticketId);
                $this->db->execute();
            }

            return $msgId;
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] agregarMensaje error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualizar estado o prioridad de un ticket
     */
    public function actualizarEstado(
        int $ticketId,
        string $nuevoEstado,
        ?string $nuevaPrioridad = null,
        ?int $respondidoPor = null,
        ?string $respuestaSoporte = null
    ): bool {
        try {
            $sql = "UPDATE soporte_tickets SET estado = :estado";
            $params = [':estado' => $nuevoEstado, ':id' => $ticketId];

            if ($nuevaPrioridad !== null) {
                $sql .= ", prioridad = :prioridad";
                $params[':prioridad'] = $nuevaPrioridad;
            }

            if ($respondidoPor !== null) {
                $sql .= ", respondido_por = :admin_id, respondido_at = NOW()";
                $params[':admin_id'] = $respondidoPor;
            }

            if ($respuestaSoporte !== null) {
                $sql .= ", respuesta_soporte = :resp";
                $params[':resp'] = $respuestaSoporte;
            }

            $sql .= " WHERE id = :id";

            $this->db->query($sql);
            foreach ($params as $k => $v) {
                $this->db->bind($k, $v);
            }

            return $this->db->execute();
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] actualizarEstado error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar un usuario por Cédula o Correo para identificación de soporte guest
     */
    public function buscarUsuarioPorIdentificador(string $identificador): ?object
    {
        try {
            $limpio = trim($identificador);
            if (empty($limpio)) return null;

            // Normalizar cédula si viene con prefijos o solo números
            $cedulaPura = preg_replace('/[^0-9]/', '', $limpio);

            $sql = "
                SELECT 
                    u.id,
                    u.cedula,
                    u.correo,
                    u.rol_id,
                    u.estado,
                    COALESCE(dp.nombres, u.cedula) AS nombres,
                    COALESCE(dp.apellidos, '') AS apellidos,
                    dp.telefono,
                    d.nombre AS departamento_nombre
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                LEFT JOIN departamentos d ON d.id = u.departamento_id
                WHERE LOWER(u.correo) = LOWER(:ident)
                   OR u.cedula = :ident2
            ";
            if (!empty($cedulaPura)) {
                $sql .= " OR REPLACE(REPLACE(u.cedula, 'V-', ''), 'E-', '') = :cedulaPura ";
            }
            $sql .= " LIMIT 1";

            $this->db->query($sql);
            $this->db->bind(':ident', $limpio);
            $this->db->bind(':ident2', $limpio);
            if (!empty($cedulaPura)) {
                $this->db->bind(':cedulaPura', $cedulaPura);
            }

            $user = $this->db->single();
            return $user ?: null;
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] buscarUsuarioPorIdentificador error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener el ticket activo (Abierto o En Proceso) más reciente de un usuario
     */
    public function getTicketActivoPorUsuario(int $usuarioId): ?object
    {
        try {
            $this->db->query("
                SELECT 
                    t.*,
                    TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_transcurridas,
                    (SELECT COUNT(*) FROM soporte_ticket_mensajes m WHERE m.ticket_id = t.id) AS total_mensajes
                FROM soporte_tickets t
                WHERE t.usuario_id = :uid
                  AND t.estado IN ('Abierto', 'En Proceso')
                ORDER BY t.created_at DESC
                LIMIT 1
            ");
            $this->db->bind(':uid', $usuarioId);
            $res = $this->db->single();
            return $res ?: null;
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] getTicketActivoPorUsuario error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener ticket por ID y guest_token para sesiones no autenticadas
     */
    public function getTicketPorIdYToken(int $ticketId, string $token): ?object
    {
        try {
            $token = trim($token);
            if ($ticketId <= 0 || empty($token)) return null;

            $this->db->query("
                SELECT 
                    t.*,
                    COALESCE(dp.nombres, t.nombre) AS solicitante_nombres,
                    COALESCE(dp.apellidos, '') AS solicitante_apellidos,
                    u.cedula AS solicitante_cedula,
                    TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_transcurridas
                FROM soporte_tickets t
                LEFT JOIN usuarios u ON u.id = t.usuario_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE t.id = :id AND t.guest_token = :token
                LIMIT 1
            ");
            $this->db->bind(':id', $ticketId);
            $this->db->bind(':token', $token);
            $res = $this->db->single();
            return $res ?: null;
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] getTicketPorIdYToken error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Asignar o regenerar guest_token a un ticket existente
     */
    public function asignarGuestToken(int $ticketId, ?string $token = null): string
    {
        try {
            $newToken = $token ?: bin2hex(random_bytes(16));
            $this->db->query("
                UPDATE soporte_tickets
                SET guest_token = :token
                WHERE id = :id AND (guest_token IS NULL OR guest_token = '')
            ");
            $this->db->bind(':token', $newToken);
            $this->db->bind(':id', $ticketId);
            $this->db->execute();

            // Si ya tenía un token, obtener el que tiene actualmente
            $this->db->query("SELECT guest_token FROM soporte_tickets WHERE id = :id LIMIT 1");
            $this->db->bind(':id', $ticketId);
            $row = $this->db->single();
            return !empty($row->guest_token) ? (string)$row->guest_token : $newToken;
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] asignarGuestToken error: ' . $e->getMessage());
            return $token ?: bin2hex(random_bytes(16));
        }
    }
}
