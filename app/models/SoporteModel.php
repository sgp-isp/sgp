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
    }

    /**
     * Obtener métricas / KPIs globales de tickets
     */
    public function getKpis(): object
    {
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
    }

    /**
     * Obtener lista de tickets para administración con filtros
     */
    public function getTicketsAdmin(?string $estado = null, ?string $prioridad = null, ?string $busqueda = null): array
    {
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
            $sql .= " AND t.estado = :estado";
            $params[':estado'] = $estado;
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
    }

    /**
     * Obtener tickets creados por un usuario específico (para pasantes y usuarios regulares)
     */
    public function getTicketsUsuario(int $usuarioId): array
    {
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
    }

    /**
     * Obtener un ticket por ID con datos completos del usuario
     */
    public function getTicketPorId(int $id): ?object
    {
        $this->db->query("
            SELECT 
                t.*,
                COALESCE(dp.nombres, t.nombre) AS solicitante_nombres,
                COALESCE(dp.apellidos, '') AS solicitante_apellidos,
                u.cedula AS solicitante_cedula,
                u.rol_id AS solicitante_rol_id,
                u.email AS solicitante_email,
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
    }

    /**
     * Obtener hilo cronológico de mensajes del chat de un ticket
     */
    public function getMensajes(int $ticketId): array
    {
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
    }

    /**
     * Crear un nuevo ticket de soporte y registrar el primer mensaje
     */
    public function crearTicket(array $data): array
    {
        $codigo = '#TCK-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $this->db->query("
            INSERT INTO soporte_tickets 
                (codigo, usuario_id, nombre, correo, telefono, categoria, prioridad, asunto, mensaje, estado, ip, created_at)
            VALUES 
                (:codigo, :usuario_id, :nombre, :correo, :telefono, :categoria, :prioridad, :asunto, :mensaje, 'Abierto', :ip, NOW())
        ");

        $this->db->bind(':codigo',     $codigo);
        $this->db->bind(':usuario_id', !empty($data['usuario_id']) ? (int)$data['usuario_id'] : null);
        $this->db->bind(':nombre',     $data['nombre'] ?? 'Usuario');
        $this->db->bind(':correo',     $data['correo'] ?? '');
        $this->db->bind(':telefono',   $data['telefono'] ?? null);
        $this->db->bind(':categoria',  $data['categoria'] ?? 'Error del Sistema');
        $this->db->bind(':prioridad',  $data['prioridad'] ?? 'Media');
        $this->db->bind(':asunto',     $data['asunto'] ?? 'Sin Asunto');
        $this->db->bind(':mensaje',    $data['mensaje'] ?? '');
        $this->db->bind(':ip',         $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        $this->db->execute();
        $ticketId = (int)$this->db->lastInsertId();

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
            'id'     => $ticketId,
            'codigo' => $codigo
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
    }
}
