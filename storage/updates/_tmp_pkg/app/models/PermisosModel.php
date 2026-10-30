<?php
/**
 * PermisosModel — Modelo de Permisos Granulares (Data-Driven RBAC)
 *
 * Gestiona las tablas: modulos_sistema, acciones_modulo, permisos_rol, usuario_permisos
 *
 * Lógica de resolución:
 *   1. SuperAdmin (rol 0) → siempre permitido (no consulta BD)
 *   2. usuario_permisos   → override individual (prevalece sobre el default)
 *   3. permisos_rol       → default del rol (si no hay override)
 */
class PermisosModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtiene los permisos de un usuario como array plano clave => bool.
     * Resultado pensado para almacenar en sesión: $_SESSION['permisos'].
     *
     * @param  int   $userId
     * @param  int   $rolId
     * @return array ['ver_usuarios' => true, 'crear_backup' => false, ...]
     */
    public function getPermisosUsuario(int $userId, int $rolId): array
    {
        // SuperAdmin nunca necesita consulta — tiene acceso total
        if ($rolId === 0) return [];

        $db = $this->db;

        // 1. Defaults del rol
        $db->query(
            'SELECT a.clave, pr.habilitado
             FROM permisos_rol pr
             JOIN acciones_modulo a ON pr.accion_id = a.id
             WHERE pr.rol_id = :rol'
        );
        $db->bind(':rol', $rolId);
        $rows = $db->resultSet();

        $permisos = [];
        foreach ($rows as $row) {
            $permisos[$row->clave] = (bool)$row->habilitado;
        }

        // 2. Overrides individuales (sobreescriben defaults)
        $db->query(
            'SELECT a.clave, up.habilitado
             FROM usuario_permisos up
             JOIN acciones_modulo a ON up.accion_id = a.id
             WHERE up.usuario_id = :uid'
        );
        $db->bind(':uid', $userId);
        $overrides = $db->resultSet();

        foreach ($overrides as $row) {
            $permisos[$row->clave] = (bool)$row->habilitado;
        }

        return $permisos;
    }

    /**
     * Obtiene la matriz completa: todos los usuarios × todas las acciones.
     * Usada en la vista de gestión del SuperAdmin.
     *
     * @param  int|null $rolFiltro  Filtrar por rol (null = todos)
     * @return array
     */
    public function getMatrizPermisos(?int $rolFiltro = null): array
    {
        $db = $this->db;

        // Obtener usuarios (excluir SuperAdmin y Pasantes de la gestión)
        $whereRol = $rolFiltro ? 'AND u.rol_id = :rol' : 'AND u.rol_id IN (1,2)';
        $db->query(
            "SELECT u.id, u.correo, u.rol_id, u.departamento_id,
                    CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_completo,
                    r.nombre AS rol_nombre
             FROM usuarios u
             LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
             LEFT JOIN roles r ON r.id = u.rol_id
             WHERE u.estado = 'activo' {$whereRol}
             ORDER BY u.rol_id, nombre_completo"
        );
        if ($rolFiltro) $db->bind(':rol', $rolFiltro);
        $usuarios = $db->resultSet();

        // Obtener módulos con sus acciones agrupados
        $db->query(
            'SELECT m.id AS modulo_id, m.nombre AS modulo_nombre, m.grupo, m.rol_base,
                    a.id AS accion_id, a.clave, a.nombre AS accion_nombre, a.tipo
             FROM modulos_sistema m
             JOIN acciones_modulo a ON a.modulo_id = m.id
             WHERE m.activo = 1
             ORDER BY m.orden, a.tipo'
        );
        $acciones = $db->resultSet();

        // Obtener todos los permisos (defaults + overrides) para los usuarios
        $db->query(
            'SELECT up.usuario_id, a.clave, up.habilitado, 1 AS es_override
             FROM usuario_permisos up
             JOIN acciones_modulo a ON up.accion_id = a.id
             UNION ALL
             SELECT NULL AS usuario_id, a.clave, pr.habilitado, 0 AS es_override
             FROM permisos_rol pr
             JOIN acciones_modulo a ON pr.accion_id = a.id'
        );
        $todosPermisos = $db->resultSet();

        return [
            'usuarios'       => $usuarios,
            'acciones'       => $acciones,
            'todos_permisos' => $todosPermisos,
        ];
    }

    /**
     * Obtiene módulos con sus acciones agrupados por grupo (para la vista).
     *
     * @return array
     */
    public function getModulosConAcciones(?int $rolId = null): array
    {
        $db = $this->db;
        $whereRol = $rolId !== null ? 'AND m.rol_base = :rolId' : '';
        $db->query(
            "SELECT m.id AS modulo_id, m.clave AS modulo_clave, m.nombre AS modulo_nombre,
                    m.icono, m.grupo, m.rol_base,
                    a.id AS accion_id, a.clave, a.nombre AS accion_nombre, a.tipo
             FROM modulos_sistema m
             JOIN acciones_modulo a ON a.modulo_id = m.id
             WHERE m.activo = 1 {$whereRol}
             ORDER BY m.orden, a.id"
        );
        if ($rolId !== null) {
            $db->bind(':rolId', $rolId);
        }
        $rows = $db->resultSet();

        $agrupados = [];
        foreach ($rows as $row) {
            $agrupados[$row->grupo][$row->modulo_id][] = $row;
        }
        return $agrupados;
    }

    /**
     * Guarda un permiso individual para un usuario (INSERT o UPDATE).
     *
     * @param  int  $userId
     * @param  string $clave  Clave de la acción (ej: 'ver_usuarios')
     * @param  bool $habilitado
     * @param  int  $otorgadoPor  ID del SuperAdmin
     * @return bool
     */
    public function savePermiso(int $userId, string $clave, bool $habilitado, int $otorgadoPor): bool
    {
        $db = $this->db;

        // Obtener ID de la acción por su clave
        $db->query('SELECT id FROM acciones_modulo WHERE clave = :clave LIMIT 1');
        $db->bind(':clave', $clave);
        $accion = $db->single();

        if (!$accion) return false;

        $db->query(
            'INSERT INTO usuario_permisos (usuario_id, accion_id, habilitado, otorgado_por)
             VALUES (:uid, :aid, :hab, :por)
             ON DUPLICATE KEY UPDATE habilitado = :hab2, otorgado_por = :por2, updated_at = NOW()'
        );
        $db->bind(':uid',  $userId);
        $db->bind(':aid',  $accion->id);
        $db->bind(':hab',  (int)$habilitado);
        $db->bind(':por',  $otorgadoPor);
        $db->bind(':hab2', (int)$habilitado);
        $db->bind(':por2', $otorgadoPor);

        return $db->execute();
    }

    /**
     * Guarda múltiples permisos de un usuario de una sola vez.
     * Recibe array ['clave' => bool, ...]
     *
     * @param  int   $userId
     * @param  array $permisos  ['ver_usuarios' => true, 'crear_backup' => false]
     * @param  int   $otorgadoPor
     * @return bool
     */
    public function savePermisosLote(int $userId, array $permisos, int $otorgadoPor): bool
    {
        foreach ($permisos as $clave => $habilitado) {
            if (!$this->savePermiso($userId, $clave, (bool)$habilitado, $otorgadoPor)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Elimina todos los overrides de un usuario (vuelve a los defaults del rol).
     *
     * @param  int $userId
     * @return bool
     */
    public function resetPermisos(int $userId): bool
    {
        $db = $this->db;
        $db->query('DELETE FROM usuario_permisos WHERE usuario_id = :uid');
        $db->bind(':uid', $userId);
        return $db->execute();
    }

    /**
     * Obtiene los permisos actuales de un usuario para mostrar en la vista
     * de edición (SuperAdmin). Incluye si es override o default.
     *
     * @param  int $userId
     * @param  int $rolId
     * @return array  ['clave' => ['habilitado' => bool, 'es_override' => bool], ...]
     */
    public function getPermisosDetalleUsuario(int $userId, int $rolId): array
    {
        $db = $this->db;

        // Defaults del rol
        $db->query(
            'SELECT a.clave, pr.habilitado
             FROM permisos_rol pr
             JOIN acciones_modulo a ON pr.accion_id = a.id
             WHERE pr.rol_id = :rol'
        );
        $db->bind(':rol', $rolId);
        $defaults = $db->resultSet();

        $resultado = [];
        foreach ($defaults as $row) {
            $resultado[$row->clave] = ['habilitado' => (bool)$row->habilitado, 'es_override' => false];
        }

        // Overrides del usuario
        $db->query(
            'SELECT a.clave, up.habilitado
             FROM usuario_permisos up
             JOIN acciones_modulo a ON up.accion_id = a.id
             WHERE up.usuario_id = :uid'
        );
        $db->bind(':uid', $userId);
        $overrides = $db->resultSet();

        foreach ($overrides as $row) {
            $resultado[$row->clave] = ['habilitado' => (bool)$row->habilitado, 'es_override' => true];
        }

        return $resultado;
    }

    /**
     * KPIs para el dashboard del SuperAdmin.
     *
     * @return array
     */
    public function getKpis(): array
    {
        $db = $this->db;

        $db->query("SELECT rol_id, COUNT(*) AS total FROM usuarios WHERE rol_id IN (1,2,3) AND estado='activo' GROUP BY rol_id");
        $conteosPorRol = $db->resultSet() ?: [];
        $roleMap = [];
        foreach ($conteosPorRol as $row) {
            $roleMap[(int)$row->rol_id] = (int)$row->total;
        }

        $db->query('SELECT COUNT(*) AS total FROM modulos_sistema WHERE activo = 1');
        $totalModulos = $db->single()->total ?? 0;

        $db->query('SELECT COUNT(DISTINCT usuario_id) AS total FROM usuario_permisos');
        $conOverride = $db->single()->total ?? 0;

        $db->query('SELECT MAX(updated_at) AS ultima FROM usuario_permisos');
        $ultimaModif = $db->single()->ultima ?? 'Nunca';

        return [
            'total_admins'       => $roleMap[1] ?? 0,
            'total_tutores'      => $roleMap[2] ?? 0,
            'total_pasantes'     => $roleMap[3] ?? 0,
            'total_gestionables' => array_sum($roleMap),
            'total_modulos'      => $totalModulos,
            'con_override'       => $conOverride,
            'ultima_modificacion'=> $ultimaModif,
        ];
    }
}
