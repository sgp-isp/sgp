<?php
/**
 * AuditModel - Modelo de Auditoría
 * 
 * PROPÓSITO EDUCATIVO:
 * Este modelo implementa un sistema de trazabilidad (Audit Log) que registra
 * todas las acciones críticas del sistema para fines de seguridad y auditoría.
 * 
 * RESPONSABILIDADES:
 * - Registrar acciones de usuarios (login, updates, deletes)
 * - Almacenar IP, user agent y detalles adicionales
 * - Proveer consultas para el panel de administración
 * 
 * SEGURIDAD:
 * - Método estático para fácil integración
 * - Captura automática de IP y user agent
 * - Almacenamiento de detalles en formato JSON
 * 
 * @author Sistema SGP
 * @version 1.0
 */

declare(strict_types=1);

class AuditModel
{
    private $db;

    /**
     * Constructor del Modelo
     * 
     * @param Database $db Instancia de conexión a la base de datos
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Registrar Acción en Bitácora (Método Estático)
     * 
     * PROPÓSITO:
     * Método estático para registrar acciones desde cualquier parte del sistema
     * sin necesidad de instanciar el modelo.
     * 
     * FLUJO:
     * Input: $accion, $tabla (opcional), $registroId (opcional), $detalles (opcional)
     * Proceso: Captura IP, user agent, usuario actual
     * Output: Registro en tabla bitacora
     * 
     * EJEMPLOS DE USO:
     * AuditModel::log('LOGIN');
     * AuditModel::log('UPDATE_PROFILE', 'datos_personales', $userId);
     * AuditModel::log('FORMALIZE_INTERN', 'datos_pasante', $pasanteId, ['departamento' => 'IT']);
     * 
     * @param string $accion Acción realizada (LOGIN, UPDATE, DELETE, etc.)
     * @param string|null $tabla Tabla afectada (opcional)
     * @param int|null $registroId ID del registro afectado (opcional)
     * @param array|null $detalles Datos adicionales en formato array (opcional)
     * @return void
     */
    public static function log(
        string $accion, 
        ?string $tabla = null, 
        ?int $registroId = null, 
        ?array $detalles = null
    ): void {
        try {
            // Crear instancia de Database con configuración
            $config = require dirname(dirname(__DIR__)) . '/app/config/config.php';
            $db = new Database($config['db']);
            
            // Obtener datos del contexto actual
            $userId = Session::get('user_id') ?? 0; // 0 = Sistema/Anónimo
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Convertir detalles a JSON si existen
            $detallesJson = $detalles ? json_encode($detalles, JSON_UNESCAPED_UNICODE) : null;
            
            // Preparar query
            $db->query("
                INSERT INTO bitacora (
                    usuario_id, 
                    accion, 
                    tabla_afectada, 
                    registro_id, 
                    ip_address, 
                    user_agent, 
                    detalles
                )
                VALUES (
                    :usuario_id, 
                    :accion, 
                    :tabla, 
                    :registro_id, 
                    :ip, 
                    :user_agent, 
                    :detalles
                )
            ");
            
            // Bind de parámetros
            $db->bind(':usuario_id', $userId);
            $db->bind(':accion', $accion);
            $db->bind(':tabla', $tabla);
            $db->bind(':registro_id', $registroId);
            $db->bind(':ip', $ip);
            $db->bind(':user_agent', $userAgent);
            $db->bind(':detalles', $detallesJson);
            
            // Ejecutar
            $db->execute();
            
        } catch (Exception $e) {
            // En caso de error, no interrumpir el flujo principal
            // Solo registrar en log de errores
            error_log("AuditModel::log() Error: " . $e->getMessage());
        }
    }

    /**
     * Obtener Todos los Registros de Bitácora
     * 
     * PROPÓSITO:
     * Consultar los registros de auditoría para el panel de administración.
     * 
     * FLUJO:
     * Input: $limit (cantidad de registros)
     * Proceso: JOIN con usuarios y datos_personales para obtener nombres
     * Output: Array con registros ordenados por fecha descendente
     * 
     * @param int $limit Cantidad máxima de registros a retornar
     * @return array Array de registros de bitácora
     */
    public function getAll(int $limit = 100): array
    {
        $this->db->query("
            SELECT 
                b.id,
                b.usuario_id,
                b.accion,
                b.tabla_afectada,
                b.registro_id,
                b.ip_address,
                b.user_agent,
                b.detalles,
                b.created_at,
                u.correo as usuario_email,
                CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, '')) as usuario_nombre
            FROM bitacora b
            LEFT JOIN usuarios u ON b.usuario_id = u.id
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            ORDER BY b.created_at DESC
            LIMIT :limit
        ");
        
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    /**
     * Obtener Registros por Usuario
     * 
     * PROPÓSITO:
     * Consultar las acciones de un usuario específico.
     * 
     * @param int $userId ID del usuario
     * @param int $limit Cantidad máxima de registros
     * @return array Array de registros del usuario
     */
    public function getByUser(int $userId, int $limit = 50): array
    {
        $this->db->query("
            SELECT 
                b.*,
                u.correo as usuario_email
            FROM bitacora b
            LEFT JOIN usuarios u ON b.usuario_id = u.id
            WHERE b.usuario_id = :user_id
            ORDER BY b.created_at DESC
            LIMIT :limit
        ");
        
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    /**
     * Obtener Registros por Acción
     * 
     * PROPÓSITO:
     * Filtrar registros por tipo de acción (ej: todos los LOGIN).
     * 
     * @param string $accion Tipo de acción a filtrar
     * @param int $limit Cantidad máxima de registros
     * @return array Array de registros filtrados
     */
    public function getByAction(string $accion, int $limit = 50): array
    {
        $this->db->query("
            SELECT 
                b.*,
                u.correo as usuario_email,
                CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, '')) as usuario_nombre
            FROM bitacora b
            LEFT JOIN usuarios u ON b.usuario_id = u.id
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            WHERE b.accion = :accion
            ORDER BY b.created_at DESC
            LIMIT :limit
        ");
        
        $this->db->bind(':accion', $accion);
        $this->db->bind(':limit', $limit);
        
        return $this->db->resultSet();
    }

    /**
     * Obtener Estadísticas de Acciones
     * 
     * PROPÓSITO:
     * Generar resumen de acciones para dashboard de administración.
     * 
     * @return array Array con conteo de acciones por tipo
     */
    public function getActionStats(): array
    {
        $this->db->query("
            SELECT 
                accion,
                COUNT(*) as total,
                DATE(created_at) as fecha
            FROM bitacora
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY accion, DATE(created_at)
            ORDER BY fecha DESC, total DESC
        ");
        
        return $this->db->resultSet();
    }

    /**
     * Obtener Instancia de Database
     * 
     * @return Database Instancia de la conexión
     */
    public function getDb(): Database
    {
        return $this->db;
    }

    // ============================================================
    // MÉTODOS DE CICLO DE VIDA — SGP Lifecycle v2
    // ============================================================

    /**
     * KPIs del header de Bitácora
     *
     * Devuelve en una sola consulta:
     *   - total_activos : registros en tabla bitacora (activos)
     *   - total_historico: registros archivados en bitacora_historico
     *   - hoy            : eventos registrados hoy
     *   - semana         : eventos de los últimos 7 días
     *   - ultima_purga   : fecha de la última acción AUDIT_PURGE
     *
     * @return array
     */
    public function getKPIs(): array
    {
        // Contadores de bitacora activa
        $this->db->query("
            SELECT
                COUNT(*)                                                        AS total_activos,
                SUM(DATE(created_at) = CURDATE())                              AS hoy,
                SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY))             AS semana,
                MAX(CASE WHEN accion = 'AUDIT_PURGE' THEN created_at END)      AS ultima_purga
            FROM bitacora
        ");
        $row = $this->db->single();

        // Total archivado (tabla cold)
        $this->db->query("SELECT COUNT(*) AS total FROM bitacora_historico");
        $hist = $this->db->single();

        return [
            'total_activos'   => (int)($row->total_activos   ?? 0),
            'hoy'             => (int)($row->hoy             ?? 0),
            'semana'          => (int)($row->semana          ?? 0),
            'ultima_purga'    => $row->ultima_purga           ?? null,
            'total_historico' => (int)($hist->total          ?? 0),
        ];
    }

    /**
     * Ejecutar el ciclo de purga (llama al Stored Procedure spPurgarBitacora)
     *
     * @param int $diasCriticos  Días de retención para acciones críticas (default 365)
     * @param int $diasOperacion Días de retención para acciones operacionales (default 90)
     * @param int $ejecutadoPor  ID del admin que ejecuta la purga (0 = automático)
     * @return array ['archivados' => int, 'purgados' => int]
     */
    public function purgar(int $diasCriticos = 365, int $diasOperacion = 90, int $ejecutadoPor = 0): array
    {
        $this->db->query("CALL spPurgarBitacora(:dias_c, :dias_o, :uid)");
        $this->db->bind(':dias_c', $diasCriticos);
        $this->db->bind(':dias_o', $diasOperacion);
        $this->db->bind(':uid',    $ejecutadoPor);
        $result = $this->db->single();

        return [
            'archivados' => (int)($result->archivados ?? 0),
            'purgados'   => (int)($result->purgados   ?? 0),
        ];
    }

    /**
     * Obtener registros del histórico (tabla cold) con paginación y filtros
     *
     * @param int         $limit
     * @param int         $offset
     * @param string|null $accion
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array ['data' => array, 'total' => int]
     */
    public function getHistorico(
        int $limit    = 25,
        int $offset   = 0,
        ?string $accion    = null,
        ?string $dateFrom  = null,
        ?string $dateTo    = null
    ): array {
        $where  = 'WHERE 1=1';
        $params = [];

        if ($accion) {
            $where .= ' AND h.accion = :accion';
            $params[':accion'] = $accion;
        }
        if ($dateFrom) {
            $where .= ' AND DATE(h.created_at) >= :fecha_desde';
            $params[':fecha_desde'] = $dateFrom;
        }
        if ($dateTo) {
            $where .= ' AND DATE(h.created_at) <= :fecha_hasta';
            $params[':fecha_hasta'] = $dateTo;
        }

        // Count total
        $this->db->query("SELECT COUNT(*) AS total FROM bitacora_historico h $where");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $countRow = $this->db->single();
        $total = (int)($countRow->total ?? 0);

        // Rows paginadas
        $this->db->query("
            SELECT h.*, u.correo AS usuario_email,
                   CONCAT(COALESCE(dp.nombres,''),' ',COALESCE(dp.apellidos,'')) AS usuario_nombre
            FROM bitacora_historico h
            LEFT JOIN usuarios u        ON h.usuario_id = u.id
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            $where
            ORDER BY h.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $this->db->bind($k, $v);
        $this->db->bind(':limit',  $limit,  \PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, \PDO::PARAM_INT);
        $rows = $this->db->resultSet();

        return ['data' => $rows, 'total' => $total];
    }
}

