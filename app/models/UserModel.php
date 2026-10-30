<?php
/**
 * UserModel - Modelo de Usuarios
 * 
 * PROPÓSITO EDUCATIVO:
 * Este modelo implementa el patrón MVC separando la lógica de acceso a datos (Model)
 * de la lógica de negocio (Controller) y la presentación (View).
 * 
 * RESPONSABILIDADES:
 * - Interactuar con la base de datos (tabla usuarios y relacionadas)
 * - Encapsular queries SQL complejas
 * - Proporcionar métodos reutilizables para operaciones CRUD
 * - Manejar la seguridad de contraseñas (hashing)
 * 
 * SEGURIDAD:
 * - Todas las queries usan prepared statements (prevención SQL Injection)
 * - Las contraseñas se hashean con BCRYPT (nunca se almacenan en texto plano)
 * - Las respuestas de seguridad también se hashean
 * 
 * @author Sistema SGP
 * @version 2.0 - Refactorización Educativa
 */

declare(strict_types=1);

class UserModel
{
    private Database $db;

    /**
     * Constructor del Modelo
     * 
     * @param Database $db Instancia de conexión a la base de datos
     * 
     * RAZÓN TÉCNICA:
     * Recibimos la conexión por parámetro (Dependency Injection) en lugar de crearla aquí.
     * Esto facilita testing y reutilización.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Buscar Usuario por Correo Electrónico
     * 
     * PROPÓSITO:
     * Método principal para autenticación. Busca un usuario por su email.
     * 
     * FLUJO DE DATOS:
     * Input: $email (string) - Correo del usuario
     * Proceso: Query con LEFT JOIN a datos_personales
     * Output: Array con datos del usuario o NULL si no existe
     * 
     * RAZÓN DEL LEFT JOIN:
     * Usuarios recién creados pueden NO tener registro en datos_personales todavía.
     * LEFT JOIN permite que estos usuarios también se autentiquen.
     * 
     * COALESCE:
     * Si no hay datos_personales, usamos el correo como nombre temporal.
     * Ejemplo: COALESCE(CONCAT('Juan', 'Pérez'), 'juan@sgp.local') → 'Juan Pérez'
     *          COALESCE(NULL, 'juan@sgp.local') → 'juan@sgp.local'
     * 
     * @param string $email Correo electrónico del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function findByEmail(string $email): ?array
    {
        /**
         * Query con prepared statement
         * 
         * SEGURIDAD:
         * Usamos :email como placeholder en lugar de concatenar directamente.
         * Esto previene SQL Injection.
         * 
         * MAL:  "SELECT * FROM usuarios WHERE correo = '$email'"
         * BIEN: prepare() + bind() (lo que hacemos aquí)
         */
        $this->db->query("
            SELECT
                u.id,
                u.correo,
                u.password,
                u.rol_id as role_id,
                u.requiere_cambio_clave,
                u.departamento_id,
                u.estado,
                u.avatar,
                dp.nombres,
                dp.apellidos,
                COALESCE(CONCAT(dp.nombres, ' ', dp.apellidos), u.correo) as name
            FROM usuarios u
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            WHERE u.correo = :email
            LIMIT 1
        ");
        
        // Vincular el parámetro :email con el valor recibido
        $this->db->bind(':email', $email);
        
        // Ejecutar query y obtener un solo resultado
        $row = $this->db->single();
        
        // Convertir objeto a array (o retornar null si no existe)
        return $row ? (array) $row : null;
    }

    /**
     * Buscar Usuario por Cédula
     * 
     * PROPÓSITO:
     * Verificar si una cédula ya está registrada en el sistema.
     * 
     * FLUJO:
     * Input: $cedula (string) - Número de cédula
     * Proceso: JOIN con datos_personales para buscar la cédula
     * Output: Array con datos del usuario o null si no existe
     * 
     * @param string $cedula Cédula de identidad
     * @return array|null Datos del usuario o null si no existe
     */
    public function findByCedula(string $cedula): ?array
    {
        $this->db->query("
            SELECT u.id, u.correo, u.cedula, dp.nombres, dp.apellidos
            FROM usuarios u
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            WHERE u.cedula = :cedula
            LIMIT 1
        ");
        
        $this->db->bind(':cedula', $cedula);
        
        $row = $this->db->single();
        
        return $row ? (array) $row : null;
    }

    /**
     * Obtener usuarios filtrados por roles (para panel SuperAdmin).
     * Excluye al propio SuperAdmin (rol 0) y Pasantes (rol 3) del listado.
     *
     * @param  array $roles  Ej: [1, 2] para Admin y Tutor
     * @return array
     */
    public function getUsersByRoles(array $roles): array
    {
        if (empty($roles)) return [];

        // Generar placeholders nombrados compatibles con el wrapper Database del proyecto
        $placeholders = [];
        foreach (array_values($roles) as $i => $rolId) {
            $placeholders[] = ':rol' . $i;
        }
        $inClause = implode(',', $placeholders);

        $this->db->query("
            SELECT u.id, u.correo, u.rol_id, u.departamento_id, u.estado,
                   CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_completo,
                   r.nombre AS rol_nombre,
                   d.nombre AS departamento_nombre
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN roles r ON r.id = u.rol_id
            LEFT JOIN departamentos d ON d.id = u.departamento_id
            WHERE u.rol_id IN ({$inClause})
              AND u.estado = 'activo'
            ORDER BY u.rol_id, nombre_completo
        ");

        foreach (array_values($roles) as $i => $rolId) {
            $this->db->bind(':rol' . $i, (int)$rolId);
        }

        $rows = $this->db->resultSet();
        return $rows ? array_map(fn($r) => (array)$r, $rows) : [];
    }

    /**
     * Buscar Usuario por ID
     * 
     * PROPÓSITO:
     * Obtener datos de un usuario específico por su ID.
     * 
     * DIFERENCIA CON findByEmail():
     * - findByEmail() se usa para LOGIN (busca por correo)
     * - findById() se usa para OPERACIONES INTERNAS (busca por ID)
     * 
     * @param int $id ID del usuario
     * @return array|null Datos del usuario o null si no existe
     */
    public function findById(int $id): ?array
    {
        $this->db->query("
            SELECT 
                u.*, 
                dp.nombres, 
                dp.apellidos, 
                u.cedula as account_cedula,
                dp.telefono,
                dp.genero,
                dp.fecha_nacimiento
            FROM usuarios u 
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id 
            WHERE u.id = :id 
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        return $row ? (array) $row : null;
    }

    /**
     * Crear Nuevo Usuario
     * 
     * PROPÓSITO:
     * Registrar un nuevo usuario en el sistema.
     * 
     * FLUJO:
     * Input: Array con datos del usuario (email, password, role_id, etc.)
     * Proceso: INSERT en tabla usuarios con contraseña hasheada
     * Output: true si se creó correctamente, false si hubo error
     * 
     * SEGURIDAD:
     * - La contraseña se hashea con password_hash() antes de guardar
     * - NUNCA se almacena la contraseña en texto plano
     * 
     * CAMPO requiere_cambio_clave:
     * - 0 = Usuario se registró públicamente (eligió su contraseña)
     * - 1 = Usuario fue creado por admin (tiene contraseña temporal)
     * 
     * @param array $data Datos del usuario ['email', 'password', 'role_id', 'requiere_cambio_clave', 'departamento_id']
     * @return bool true si se creó correctamente
     */
    public function create(array $data): bool
    {
        /**
         * Hashear contraseña con BCRYPT
         * 
         * RAZÓN TÉCNICA:
         * password_hash() usa el algoritmo BCRYPT por defecto (PASSWORD_DEFAULT).
         * BCRYPT está diseñado para ser LENTO intencionalmente, lo que dificulta
         * ataques de fuerza bruta.
         * 
         * El hash generado incluye:
         * - Algoritmo usado ($2y$ = BCRYPT)
         * - Costo computacional (10 por defecto)
         * - Salt aleatorio (generado automáticamente)
         * - Hash de la contraseña
         * 
         * Ejemplo de hash: $2y$10$xwCcDMHXaKGfAOpi2puiaPfzia9NGBGghfyiq4c2
         */
        $this->db->query("
            INSERT INTO usuarios (correo, password, cedula, rol_id, estado, requiere_cambio_clave, departamento_id) 
            VALUES (:email, :pass, :cedula, :role, 'activo', :req_cambio, :depto)
        ");
        
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':pass', password_hash($data['password'], PASSWORD_DEFAULT));
        $this->db->bind(':cedula', $data['cedula'] ?? null);
        $this->db->bind(':role', $data['role_id']);
        $this->db->bind(':req_cambio', $data['requiere_cambio_clave'] ?? 1); // Default 1 para admin-created
        $this->db->bind(':depto', $data['departamento_id'] ?? null);
        
        return $this->db->execute();
    }
    
    /**
     * Obtener ID del Último Registro Insertado
     * 
     * PROPÓSITO:
     * Después de crear un usuario, necesitamos su ID para crear registros relacionados
     * (ej: datos_personales, usuarios_respuestas).
     * 
     * @return int ID del último registro insertado
     */
    public function getLastId(): int 
    {
         return (int) $this->db->lastInsertId();
    }

    /**
     * Obtener TODAS las Preguntas de Seguridad Activas
     * 
     * PROPÓSITO:
     * Obtener todas las preguntas de seguridad disponibles para que el usuario
     * pueda ELEGIR 3 de ellas en el registro o wizard.
     * 
     * CAMBIO IMPORTANTE (2026-01-30):
     * - ANTES: Traía solo 3 preguntas aleatorias (LIMIT 3)
     * - AHORA: Trae TODAS las preguntas activas (sin LIMIT)
     * 
     * RAZÓN DEL CAMBIO:
     * Si solo traemos 3 preguntas, el usuario NO puede elegir.
     * Con 10 preguntas disponibles, el usuario puede seleccionar
     * las 3 que más le convengan (mejor UX y seguridad).
     * 
     * ORDER BY id ASC:
     * - Ordenamiento consistente (siempre en el mismo orden)
     * - Mejor UX: el usuario ve las mismas opciones en el mismo orden
     * 
     * @return array Array de preguntas ['id', 'pregunta', 'activa', 'created_at']
     */
    public function getSecurityQuestions(): array
    {
        try {
            /**
             * Query SIN LIMIT para traer todas las preguntas
             * 
             * ANTES: ORDER BY RAND() LIMIT 3 (aleatorio, solo 3)
             * AHORA: ORDER BY id ASC (todas, ordenadas)
             */
            $this->db->query("SELECT * FROM preguntas_seguridad WHERE activa = 1 ORDER BY id ASC");
            $results = $this->db->resultSet();
            
            // Devolver objetos PDO directamente (la vista espera objetos)
            return $results ?: [];
        } catch (\Throwable $e) {
            error_log('[SGP] getSecurityQuestions falló: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Guardar Respuesta de Seguridad
     * 
     * PROPÓSITO:
     * Almacenar la respuesta a una pregunta de seguridad (hasheada).
     * 
     * NORMALIZACIÓN:
     * - strtolower(): Convertir a minúsculas ("CHOCOLATE" → "chocolate")
     * - trim(): Eliminar espacios extra ("  chocolate  " → "chocolate")
     * 
     * RAZÓN:
     * Al recuperar contraseña, el usuario podría escribir "Chocolate" o "chocolate".
     * Normalizamos para que ambas sean válidas.
     * 
     * SEGURIDAD:
     * La respuesta se hashea con password_hash() para que no sea legible en la BD.
     * 
     * @param int $userId ID del usuario
     * @param int $questionId ID de la pregunta
     * @param string $answer Respuesta en texto plano
     * @return bool true si se guardó correctamente
     */
    public function saveSecurityAnswer(int $userId, int $questionId, string $answer): bool
    {
        /**
         * Normalizar y hashear respuesta
         * 
         * FLUJO:
         * 1. trim($answer) → Eliminar espacios: "  chocolate  " → "chocolate"
         * 2. strtolower() → Convertir a minúsculas: "Chocolate" → "chocolate"
         * 3. password_hash() → Hashear: "chocolate" → "$2y$10$..."
         */
        $hash = password_hash(strtolower(trim($answer)), PASSWORD_DEFAULT);
        
        $this->db->query("INSERT INTO usuarios_respuestas (usuario_id, pregunta_id, respuesta_hash) VALUES (:uid, :qid, :hash)");
        $this->db->bind(':uid', $userId);
        $this->db->bind(':qid', $questionId);
        $this->db->bind(':hash', $hash);
        
        return $this->db->execute();
    }

    /**
     * Obtener Respuestas de Seguridad de un Usuario
     * 
     * PROPÓSITO:
     * Recuperar las preguntas y respuestas hasheadas de un usuario
     * para el proceso de recuperación de contraseña.
     * 
     * FLUJO:
     * Input: $userId
     * Proceso: JOIN entre usuarios_respuestas y preguntas_seguridad
     * Output: Array con ['question', 'answer_hash', 'question_id']
     * 
     * @param int $userId ID del usuario
     * @return array Array de respuestas con sus preguntas
     */
    public function getUserAnswers(int $userId): array
    {
         $sql = "SELECT q.pregunta as question, a.respuesta_hash as answer_hash, a.pregunta_id as question_id 
                 FROM usuarios_respuestas a 
                 JOIN preguntas_seguridad q ON a.pregunta_id = q.id 
                 WHERE a.usuario_id = :uid";
         $this->db->query($sql);
         $this->db->bind(':uid', $userId);
         $results = $this->db->resultSet();
         return array_map(fn($item) => (array) $item, $results);
    }

    /**
     * Actualizar Contraseña de Usuario
     * 
     * PROPÓSITO:
     * Cambiar la contraseña de un usuario y quitar el flag de cambio obligatorio.
     * 
     * FLUJO:
     * Input: $userId, $newPassword (texto plano)
     * Proceso: Hashear contraseña y UPDATE en tabla usuarios
     * Output: true si se actualizó correctamente
     * 
     * SEGURIDAD:
     * - La contraseña se hashea antes de guardar
     * - Se actualiza requiere_cambio_clave a 0 (usuario ya cambió su clave)
     * 
     * @param int $userId ID del usuario
     * @param string $newPassword Nueva contraseña en texto plano
     * @return bool true si se actualizó correctamente
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        // SGP Hard Lock: Limpiar intentos fallidos previos
        // FIX [Collation Err]: Consultar el correo primero en vez de SUB-QUERY
        $this->db->query("SELECT correo FROM usuarios WHERE id = :uid");
        $this->db->bind(':uid', $userId);
        $uInfo = $this->db->single();
        
        if ($uInfo && !empty($uInfo->correo)) {
            $this->db->query("DELETE FROM intentos_acceso WHERE correo = :correo");
            $this->db->bind(':correo', $uInfo->correo);
            $this->db->execute();
        }

        // Actualizar password y forzar estado activo (Auto-Recuperación)
        $this->db->query("UPDATE usuarios SET password = :pass, requiere_cambio_clave = 0, estado = 'activo' WHERE id = :uid");
        $this->db->bind(':pass', password_hash($newPassword, PASSWORD_DEFAULT));
        $this->db->bind(':uid', $userId);
        return $this->db->execute();
    }

    /**
     * Obtener Todos los Usuarios del Sistema
     * 
     * PROPÓSITO:
     * Listar todos los usuarios para el panel de administración.
     * 
     * COALESCE:
     * Si el usuario no tiene datos_personales, mostramos su correo como nombre.
     * 
     * CASE WHEN:
     * Calculamos si el perfil está completo (1 = completo, 0 = incompleto).
     * 
     * @return array Array de usuarios con sus datos básicos
     */
    public function getAllUsers(bool $incluirEgresados = false): array
    {
        $filtroEgresados = $incluirEgresados ? "" : "
            AND NOT (u.rol_id = 3 AND (COALESCE(pa.estado, '') = 'Cerrado' OR COALESCE(dpa.estado_pasantia, '') = 'Finalizado' OR u.estado = 'inactivo'))
        ";

        $this->db->query("
            SELECT 
                u.id, 
                COALESCE(CONCAT(dp.nombres, ' ', dp.apellidos), u.correo) as name,
                dp.nombres,
                dp.apellidos,
                u.cedula,
                u.correo as email, 
                u.rol_id as role_id, 
                r.nombre as role_name, 
                u.created_at,
                u.estado,
                d.nombre as departamento_nombre,
                inst.nombre as institucion_nombre,
                pa.nombre as periodo_nombre,
                pa.estado as periodo_estado,
                dpa.estado_pasantia,
                CASE WHEN dp.id IS NOT NULL THEN 1 ELSE 0 END as perfil_completado,
                CASE 
                    WHEN u.rol_id = 3 AND (COALESCE(pa.estado, '') = 'Cerrado' OR COALESCE(dpa.estado_pasantia, '') = 'Finalizado' OR u.estado = 'inactivo') THEN 1 
                    ELSE 0 
                END as es_egresado
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            LEFT JOIN departamentos d ON u.departamento_id = d.id
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN datos_pasante dpa ON u.id = dpa.usuario_id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT JOIN instituciones inst ON inst.id = COALESCE(
                NULLIF(dpa.institucion_id, 0),
                CASE WHEN dpa.institucion_procedencia REGEXP '^[0-9]+$'
                     THEN CAST(dpa.institucion_procedencia AS UNSIGNED)
                     ELSE NULL END
            )
            WHERE u.rol_id > 0
            {$filtroEgresados}
            ORDER BY u.created_at DESC
        ");
        $results = $this->db->resultSet();
        return array_map(fn($item) => (array) $item, $results);
    }

    /**
     * Obtener exclusivamente los Pasantes Egresados (cohorte o período cerrado / finalizados)
     *
     * @return array
     */
    public function getEgresadosUsers(): array
    {
        $this->db->query("
            SELECT 
                u.id, 
                COALESCE(CONCAT(dp.nombres, ' ', dp.apellidos), u.correo) as name,
                dp.nombres,
                dp.apellidos,
                u.cedula,
                u.correo as email, 
                u.rol_id as role_id, 
                r.nombre as role_name, 
                u.created_at,
                u.estado,
                d.nombre as departamento_nombre,
                inst.nombre as institucion_nombre,
                pa.id as periodo_id,
                pa.nombre as periodo_nombre,
                pa.estado as periodo_estado,
                dpa.fecha_inicio_pasantia,
                dpa.fecha_fin_estimada,
                COALESCE(dpa.estado_pasantia, 'Finalizado') as estado_pasantia,
                COALESCE(NULLIF(dpa.horas_meta, 0), 1440) as horas_meta,
                COALESCE((
                    SELECT COUNT(*) * 8
                    FROM asistencias a
                    WHERE a.pasante_id = u.id AND a.estado IN ('Presente', 'Justificado')
                ), 0) as horas_acumuladas,
                CASE WHEN dp.id IS NOT NULL THEN 1 ELSE 0 END as perfil_completado,
                1 as es_egresado
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            LEFT JOIN departamentos d ON u.departamento_id = d.id
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN datos_pasante dpa ON u.id = dpa.usuario_id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT JOIN instituciones inst ON inst.id = COALESCE(
                NULLIF(dpa.institucion_id, 0),
                CASE WHEN dpa.institucion_procedencia REGEXP '^[0-9]+$'
                     THEN CAST(dpa.institucion_procedencia AS UNSIGNED)
                     ELSE NULL END
            )
            WHERE u.rol_id = 3 
              AND (COALESCE(pa.estado, '') = 'Cerrado' OR COALESCE(dpa.estado_pasantia, '') = 'Finalizado' OR u.estado = 'inactivo')
            ORDER BY pa.fecha_fin DESC, u.created_at DESC
        ");
        $results = $this->db->resultSet();
        return array_map(fn($item) => (array) $item, $results);
    }

    /**
     * Obtener KPIs de Usuarios en una sola consulta SQL
     * Distingue entre personal/pasantes vigentes y egresados
     */
    public function getUsersKPIs(): array
    {
        $this->db->query("
            SELECT 
                COUNT(*) as total_general,
                SUM(CASE WHEN u.rol_id IN (1, 2) OR (u.rol_id = 3 AND COALESCE(pa.estado, '') != 'Cerrado' AND COALESCE(dpa.estado_pasantia, '') != 'Finalizado' AND u.estado = 'activo') THEN 1 ELSE 0 END) as total,
                SUM(CASE WHEN u.estado = 'activo' AND NOT (u.rol_id = 3 AND (COALESCE(pa.estado, '') = 'Cerrado' OR COALESCE(dpa.estado_pasantia, '') = 'Finalizado')) THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN u.rol_id = 3 AND (COALESCE(pa.estado, '') = 'Cerrado' OR COALESCE(dpa.estado_pasantia, '') = 'Finalizado' OR u.estado = 'inactivo') THEN 1 ELSE 0 END) as egresados,
                SUM(CASE WHEN u.estado = 'inactivo' AND u.rol_id != 3 THEN 1 ELSE 0 END) as inactivos,
                SUM(CASE WHEN u.rol_id = 1 THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN u.rol_id = 2 THEN 1 ELSE 0 END) as tutores,
                SUM(CASE WHEN u.rol_id = 3 AND NOT (COALESCE(pa.estado, '') = 'Cerrado' OR COALESCE(dpa.estado_pasantia, '') = 'Finalizado' OR u.estado = 'inactivo') THEN 1 ELSE 0 END) as pasantes
            FROM usuarios u
            LEFT JOIN datos_pasante dpa ON u.id = dpa.usuario_id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE u.rol_id > 0
        ");
        $row = $this->db->single();
        return $row ? (array) $row : ['total' => 0, 'activos' => 0, 'egresados' => 0, 'inactivos' => 0, 'pasantes' => 0, 'admins' => 0, 'tutores' => 0];
    }

    /**
     * Actualizar Datos de Usuario
     * 
     * PROPÓSITO:
     * Actualizar email, rol, departamento y opcionalmente contraseña.
     * 
     * LÓGICA CONDICIONAL:
     * - Si se proporciona contraseña → UPDATE con password
     * - Si NO se proporciona → UPDATE sin password
     * 
     * @param array $data Datos a actualizar ['id', 'email', 'role_id', 'departamento_id', 'password' (opcional)]
     * @return bool true si se actualizó correctamente
     */
    public function update(array $data): bool
    {
        if (!empty($data['password'])) {
            // Actualizar con nueva contraseña
            $this->db->query("
                UPDATE usuarios 
                SET correo = :email, rol_id = :role, password = :pass, departamento_id = :depto 
                WHERE id = :id
            ");
            $this->db->bind(':pass', password_hash($data['password'], PASSWORD_DEFAULT));
        } else {
            // Actualizar sin cambiar contraseña
            $this->db->query("
                UPDATE usuarios 
                SET correo = :email, rol_id = :role, departamento_id = :depto 
                WHERE id = :id
            ");
        }
        
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':role', $data['role_id']);
        $this->db->bind(':depto', $data['departamento_id'] ?? null);
        $this->db->bind(':id', $data['id']);
        
        return $this->db->execute();
    }

    /**
     * Eliminar Usuario
     * 
     * PROPÓSITO:
     * Eliminar un usuario del sistema.
     * 
     * CASCADE:
     * La BD tiene configurado ON DELETE CASCADE, por lo que al eliminar el usuario
     * también se eliminan automáticamente:
     * - datos_personales
     * - usuarios_respuestas
     * - datos_pasante / datos_tutor (si existen)
     * 
     * @param int $id ID del usuario a eliminar
     * @return bool true si se eliminó correctamente
     */
    public function delete(int $id): bool
    {
        $this->db->query("DELETE FROM usuarios WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Verificar si Usuario Tiene Perfil Completo (Simple)
     * 
     * @param int $user_id ID del usuario
     * @return bool true si tiene perfil, false si no
     */
    public function verificarPerfilCompleto(int $user_id): bool
    {
        // No basta con que exista la fila, deben estar los campos clave del wizard
        $this->db->query("
            SELECT COUNT(*) as total 
            FROM datos_personales 
            WHERE usuario_id = :uid 
            AND telefono IS NOT NULL 
            AND genero IS NOT NULL 
            AND fecha_nacimiento IS NOT NULL
        ");
        $this->db->bind(':uid', $user_id);
        $result = $this->db->single();
        return $result && $result->total > 0;
    }

    /**
     * Verificar si Usuario Tiene Perfil Completo
     * 
     * PROPÓSITO:
     * Determinar si un usuario ya completó sus datos personales.
     * 
     * USO:
     * - Middleware para forzar completar perfil
     * - Validaciones en controllers
     * 
     * @param int $usuario_id ID del usuario
     * @return bool true si tiene perfil, false si no
     */
    public function tienePerfil(int $usuario_id): bool
    {
        $this->db->query("SELECT COUNT(*) as total FROM datos_personales WHERE usuario_id = :uid");
        $this->db->bind(':uid', $usuario_id);
        $result = $this->db->single();
        return $result && $result->total > 0;
    }

    /**
     * Registrar Datos Personales de Usuario
     * 
     * PROPÓSITO:
     * Crear el registro inicial de datos personales (usado en registro público).
     * 
     * @param array $datos Datos personales ['usuario_id', 'cedula', 'nombres', 'apellidos', 'telefono', 'direccion', 'genero', 'fecha_nacimiento']
     * @return bool true si se registró correctamente
     */
    public function registrarPerfil(array $datos): bool
    {
        $this->db->query("
            INSERT INTO datos_personales 
            (usuario_id, nombres, apellidos, telefono, genero, fecha_nacimiento) 
            VALUES 
            (:usuario_id, :nombres, :apellidos, :telefono, :genero, :fecha_nacimiento)
        ");
        
        $this->db->bind(':usuario_id', $datos['usuario_id']);
        $this->db->bind(':nombres', $datos['nombres']);
        $this->db->bind(':apellidos', $datos['apellidos']);
        $this->db->bind(':telefono', $datos['telefono']);
        $this->db->bind(':genero', $datos['genero']);
        $this->db->bind(':fecha_nacimiento', $datos['fecha_nacimiento']);
        
        return $this->db->execute();
    }


    /**
     * Verificar si Pasante Tiene Datos Completos
     * 
     * @param int $usuario_id ID del usuario
     * @return bool true si tiene datos de pasante
     */
    public function tieneDatosPasante(int $usuario_id): bool
    {
        $this->db->query("SELECT COUNT(*) as total FROM datos_pasante WHERE usuario_id = :uid");
        $this->db->bind(':uid', $usuario_id);
        $result = $this->db->single();
        return $result && $result->total > 0;
    }

    /**
     * Generar Contraseña Temporal para Usuarios Creados por Admin
     * 
     * PROPÓSITO:
     * Crear una contraseña predecible para usuarios creados internamente.
     * 
     * FORMATO:
     * Sgp.{cedula}
     * 
     * EJEMPLOS:
     * - Cedula 12345678 → Contraseña: Sgp.12345678
     * - Cedula 98765432 → Contraseña: Sgp.98765432
     * 
     * SEGURIDAD:
     * Esta contraseña es TEMPORAL. El usuario DEBE cambiarla en el primer login
     * (requiere_cambio_clave = 1).
     * 
     * @param string $cedula Cédula del usuario
     * @return string Contraseña temporal
     */
    public function generateTempPassword(string $cedula): string
    {
        return 'Sgp.' . $cedula;
    }

    /**
     * Crear Usuario con Contraseña Temporal (Función de Administrador)
     * 
     * PROPÓSITO:
     * Cuando un admin crea un usuario, este método genera la contraseña temporal
     * y marca requiere_cambio_clave = 1.
     * 
     * @param array $data Datos del usuario ['correo', 'cedula', 'rol_id']
     * @return bool true si se creó correctamente
     */
    public function createWithTempPassword(array $data): bool
    {
        $tempPassword = $this->generateTempPassword($data['cedula']);
        
        $deptoId = !empty($data['departamento_id']) ? (int)$data['departamento_id'] : null;

        $this->db->query("
            INSERT INTO usuarios (correo, password, rol_id, cedula, departamento_id, estado, requiere_cambio_clave)
            VALUES (:email, :pass, :role, :cedula, :depto, 'activo', 1)
        ");
        $this->db->bind(':email',  $data['correo']);
        $this->db->bind(':pass',   password_hash($tempPassword, PASSWORD_DEFAULT));
        $this->db->bind(':role',   $data['rol_id']);
        $this->db->bind(':cedula', $data['cedula']);
        $this->db->bind(':depto',  $deptoId);
        
        return $this->db->execute();
    }

    /**
     * Resetear Contraseña a Temporal (Función de Administrador)
     * 
     * PROPÓSITO:
     * Permitir al admin resetear la contraseña de un usuario a la temporal.
     * 
     * USO:
     * - Usuario olvidó su contraseña
     * - Admin necesita dar acceso temporal
     * 
     * @param int $userId ID del usuario
     * @param string $cedula Cédula del usuario (para generar contraseña temporal)
     * @return bool true si se reseteó correctamente
     */
    public function resetToTempPassword(int $userId, string $cedula): bool
    {
        $tempPassword = $this->generateTempPassword($cedula);
        
        // SGP Hard Lock: Limpieza manual de intentos ordenado por el Admin
        // FIX [Collation Err]: Extraer correo primero para prevenir "Illegal mix of collations"
        $this->db->query("SELECT correo FROM usuarios WHERE id = :uid");
        $this->db->bind(':uid', $userId);
        $uInfo = $this->db->single();
        
        if ($uInfo && !empty($uInfo->correo)) {
            $this->db->query("DELETE FROM intentos_acceso WHERE correo = :correo");
            $this->db->bind(':correo', $uInfo->correo);
            $this->db->execute();
        }
        
        $this->db->query("
            UPDATE usuarios 
            SET password = :pass, requiere_cambio_clave = 1, estado = 'activo'
            WHERE id = :uid
        ");
        $this->db->bind(':pass', password_hash($tempPassword, PASSWORD_DEFAULT));
        $this->db->bind(':uid', $userId);
        
        return $this->db->execute();
    }

    /**
     * Obtener Datos Completos del Perfil
     * 
     * PROPÓSITO:
     * Retornar toda la información del usuario para mostrar en su perfil.
     * Combina datos de usuarios y datos_personales.
     * 
     * @param int $id ID del usuario
     * @return array|null Datos completos del perfil
     */
    public function getProfileData(int $id): ?array
    {
        $this->db->query("
            SELECT 
                u.id,
                u.correo,
                u.role_id,
                u.departamento_id,
                u.avatar,
                u.activo,
                u.created_at,
                u.cedula,
                dp.nombres,
                dp.apellidos,
                dp.telefono,
                dp.genero,
                dp.fecha_nacimiento,
                r.nombre as rol_nombre,
                dept.nombre as departamento_nombre
            FROM usuarios u
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN departamentos dept ON u.departamento_id = dept.id
            WHERE u.id = :id
            LIMIT 1
        ");
        
        $this->db->bind(':id', $id);
        
        $row = $this->db->single();
        
        return $row ? (array) $row : null;
    }

    /**
     * Verificar Completitud del Perfil
     * 
     * PROPÓSITO:
     * Determinar si el usuario ha completado todos los campos obligatorios.
     * 
     * CAMPOS OBLIGATORIOS:
     * - Cédula
     * - Nombres
     * - Apellidos
     * - Teléfono
     * 
     * @param int $id ID del usuario
     * @return array ['complete' => bool, 'missing' => array, 'percentage' => int]
     */
    public function checkProfileCompletion(int $id): array
    {
        $profile = $this->getProfileData($id);
        
        if (!$profile) {
            return [
                'complete' => false,
                'missing' => ['Todos los datos'],
                'percentage' => 0
            ];
        }
        
        $requiredFields = [
            'cedula' => 'Cédula',
            'nombres' => 'Nombres',
            'apellidos' => 'Apellidos',
            'telefono' => 'Teléfono'
        ];
        
        $missing = [];
        $completed = 0;
        
        foreach ($requiredFields as $field => $label) {
            if (!empty($profile[$field])) {
                $completed++;
            } else {
                $missing[] = $label;
            }
        }
        
        $percentage = (int) (($completed / count($requiredFields)) * 100);
        
        return [
            'complete' => count($missing) === 0,
            'missing' => $missing,
            'percentage' => $percentage
        ];
    }

    /**
     * Actualizar Datos del Perfil
     * 
     * @param int $id ID del usuario
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó exitosamente
     */
    public function updateProfile(int $id, array $data): bool
    {
        $this->db->query("
            UPDATE datos_personales
            SET 
                nombres = :nombres,
                apellidos = :apellidos,
                telefono = :telefono,
                genero = :genero,
                fecha_nacimiento = :fecha_nacimiento
            WHERE usuario_id = :usuario_id
        ");
        
        $this->db->bind(':nombres', $data['nombres'] ?? null);
        $this->db->bind(':apellidos', $data['apellidos'] ?? null);
        $this->db->bind(':telefono', $data['telefono'] ?? null);
        $this->db->bind(':genero', $data['genero'] ?? null);
        $this->db->bind(':fecha_nacimiento', $data['fecha_nacimiento'] ?? null);
        $this->db->bind(':usuario_id', $id);
        
        return $this->db->execute();
    }

    // ============================================
    // MÉTODOS DE TRANSACCIÓN (ACID)
    // ============================================
    
    /**
     * Iniciar Transacción
     * 
     * PROPÓSITO:
     * Iniciar una transacción de base de datos para operaciones atómicas.
     * 
     * ACID:
     * - Atomicidad: Todo o nada
     * - Consistencia: Los datos quedan en estado válido
     * - Aislamiento: Las transacciones no interfieren entre sí
     * - Durabilidad: Los cambios confirmados persisten
     * 
     * @return bool true si se inició correctamente
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Confirmar Transacción
     * 
     * PROPÓSITO:
     * Confirmar todos los cambios realizados en la transacción.
     * 
     * @return bool true si se confirmó correctamente
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Revertir Transacción
     * 
     * PROPÓSITO:
     * Revertir todos los cambios realizados en la transacción.
     * 
     * USO:
     * Se llama en el bloque catch cuando algo falla.
     * 
     * @return bool true si se revirtió correctamente
     */
    public function rollBack(): bool
    {
        return $this->db->rollBack();
    }

    /**
     * Obtener Datos Completos de Usuario (Universal View)
     * Extraído del UsersController para seguir Principio "Fat Model, Thin Controller"
     * 
     * @param int $id
     * @return object|null
     */
    public function getDatosCompletos(int $id): ?object
    {
        $this->db->query("
            SELECT 
                u.id, u.cedula, u.correo, u.rol_id, u.estado,
                u.departamento_id,
                COALESCE((
                    SELECT SUM(COALESCE(NULLIF(a.horas_calculadas, 0), 8))
                    FROM asistencias a
                    WHERE a.pasante_id = u.id
                      AND a.estado IN ('Presente','Justificado','Ausente')
                      AND a.fecha <= CURDATE()
                ), 0) AS horas_acumuladas,
                dp.nombres, dp.apellidos, dp.telefono, dp.genero, dp.fecha_nacimiento,
                dp.cargo,
                CASE WHEN u.rol_id = 0 THEN 'Super Administrador' ELSE r.nombre END AS rol_nombre,
                d.nombre AS departamento,
                COALESCE(
                    inst.nombre,
                    NULLIF(TRIM(dpas.institucion_procedencia), ''),
                    'Sin especificar'
                ) AS institucion,
                inst.representante_nombre   AS inst_rep_nombre,
                inst.representante_cargo    AS inst_rep_cargo,
                inst.representante_correo   AS inst_rep_correo,
                inst.representante_telefono AS inst_rep_telefono,
                u.avatar,
                COALESCE(NULLIF(dpas.horas_meta, 0), 1440) AS horas_requeridas,
                dpas.fecha_inicio_pasantia AS fecha_inicio,
                COALESCE(dpas.fecha_fin_estimada, pa.fecha_fin) AS fecha_fin,
                dpas.estado_pasantia,
                COALESCE(
                    CONCAT(tutor_asig_dp.nombres, ' ', tutor_asig_dp.apellidos),
                    CONCAT(tutor_dp.nombres, ' ', tutor_dp.apellidos)
                ) AS tutor_nombre
            FROM usuarios u
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN roles r ON u.rol_id = r.id
            LEFT JOIN datos_pasante dpas ON u.id = dpas.usuario_id
            LEFT JOIN periodos_academicos pa ON pa.id = dpas.periodo_id
            LEFT JOIN departamentos d ON d.id = COALESCE(dpas.departamento_asignado_id, u.departamento_id)
            LEFT JOIN instituciones inst
                       ON inst.id = COALESCE(
                           NULLIF(dpas.institucion_id, 0),
                           CASE WHEN dpas.institucion_procedencia REGEXP '^[0-9]+$'
                                THEN CAST(dpas.institucion_procedencia AS UNSIGNED) END
                       )
            LEFT JOIN datos_personales tutor_dp ON dpas.tutor_id = tutor_dp.usuario_id
            LEFT JOIN asignaciones asig ON u.id = asig.pasante_id AND asig.estado = 'activo'
            LEFT JOIN datos_personales tutor_asig_dp ON asig.tutor_id = tutor_asig_dp.usuario_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        return $this->db->single() ?: null;
    }

    /**
     * Buscar Pasante por Cédula (Kardex)
     * Extraído del UsersController
     * 
     * @param string $cedula
     * @return object|null
     */
    public function findPasanteByCedula(string $cedula): ?object
    {
        $this->db->query("
            SELECT
                u.id, u.cedula, u.correo,
                dp.nombres, dp.apellidos,
                dpa.estado_pasantia,
                dpa.horas_acumuladas,
                dpa.horas_meta,
                d.nombre AS departamento
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            LEFT JOIN departamentos    d   ON d.id = dpa.departamento_asignado_id
            WHERE u.cedula = :cedula AND u.rol_id = 3
            LIMIT 1
        ");
        $this->db->bind(':cedula', $cedula);
        return $this->db->single() ?: null;
    }
}
