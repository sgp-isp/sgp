<?php
/**
 * AsignacionModel - Gestión de Asignaciones de Pasantes
 * 
 * Encapsula la lógica relacionada con asignar pasantes a departamentos y tutores,
 * actuando sobre las tablas de `datos_pasante` y relacionadas.
 */
class AsignacionModel {
    private $db;

    public function __construct() {
        if (defined('APPROOT')) {
            require_once APPROOT . '/config/config.php';
        } else {
            require_once __DIR__ . '/../config/config.php';
        }
        $this->db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
    }

    /**
     * Obtener el período académico activo actual.
     * @return object|null
     */
    public function getPeriodoActivo(): ?object
    {
        $this->db->query("
            SELECT id, nombre, estado, fecha_inicio, fecha_fin
            FROM periodos_academicos
            WHERE estado = 'Activo'
            ORDER BY fecha_inicio DESC
            LIMIT 1
        ");
        $result = $this->db->single();
        return $result ?: null;
    }

    /**
     * Obtener asignaciones filtradas por período activo/planificado.
     *
     * [BUG-01 FIX] La versión anterior devolvía TODOS los pasantes de todos los
     * períodos (incluyendo cerrados), mezclando cohortes antiguas con la actual.
     * Ahora filtra exclusivamente por pasantes cuyo periodo_id corresponda a un
     * período en estado 'Activo' o 'Planificado'. Los pasantes sin período
     * asignado (dpa.periodo_id IS NULL) también se incluyen para que puedan
     * ser gestionados desde este módulo.
     *
     * @param int $periodoId  0 = usar el período activo automáticamente
     */
    public function getAll(int $periodoId = 0): array
    {
        // Si no se especifica período, buscar el activo automáticamente
        if ($periodoId <= 0) {
            $periodoActivo = $this->getPeriodoActivo();
            $periodoId     = (int)($periodoActivo->id ?? 0);
        }

        // Condición de filtro: período específico O sin período asignado
        if ($periodoId > 0) {
            $periodoCondicion = "AND (dpa.periodo_id = :periodo_id OR dpa.periodo_id IS NULL)";
        } else {
            // Sin períodos activos: mostrar solo los sin asignar (periodo_id IS NULL)
            $periodoCondicion = "AND dpa.periodo_id IS NULL";
        }

        $this->db->query("
            SELECT
                u.id                AS pasante_id,
                u.cedula,
                dp.nombres,
                dp.apellidos,
                COALESCE(inst.nombre, NULLIF(dpa.institucion_procedencia, ''), 'No registrada') AS institucion_procedencia,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar')       AS estado_pasantia,
                COALESCE((
                    SELECT COUNT(*) * 8
                    FROM asistencias a
                    WHERE a.pasante_id = u.id
                      AND a.estado != 'Anulado'
                      AND a.fecha BETWEEN COALESCE(pa.fecha_inicio, '1970-01-01')
                                      AND COALESCE(pa.fecha_fin,    CURDATE())
                ), 0)                                               AS horas_acumuladas,
                COALESCE(dpa.horas_meta, 1440)                     AS horas_meta,
                dpa.fecha_inicio_pasantia,
                dpa.fecha_fin_estimada,
                dpa.periodo_id,
                pa.nombre                                          AS periodo_nombre,
                d.id                                               AS departamento_id,
                d.nombre                                           AS departamento_nombre,
                tu.id                                              AS tutor_id,
                tup.nombres                                        AS tutor_nombres,
                tup.apellidos                                      AS tutor_apellidos
            FROM usuarios u
            LEFT JOIN datos_personales  dp   ON dp.usuario_id   = u.id
            LEFT JOIN datos_pasante     dpa  ON dpa.usuario_id  = u.id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos     d    ON d.id  = dpa.departamento_asignado_id
            LEFT JOIN usuarios          tu   ON tu.id = dpa.tutor_id
            LEFT JOIN datos_personales  tup  ON tup.usuario_id  = tu.id
            LEFT JOIN instituciones     inst ON (dpa.institucion_id = inst.id OR dpa.institucion_procedencia = inst.nombre)
            WHERE u.rol_id = 3
              AND COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
              {$periodoCondicion}
            ORDER BY
                FIELD(COALESCE(dpa.estado_pasantia,'Sin Asignar'),
                      'Activo','Pendiente','Sin Asignar','Finalizado','Retirado'),
                IFNULL(dp.apellidos, u.correo) ASC
        ");

        if ($periodoId > 0) {
            $this->db->bind(':periodo_id', $periodoId);
        }

        return $this->db->resultSet() ?: [];
    }

    /**
     * Asegura de manera idempotente que la tabla datos_pasante tenga la clave única
     * compuesta (usuario_id, periodo_id) para habilitar el historial multi-período [BUG-04].
     */
    private function asegurarEsquemaMultiPeriodo(): void
    {
        try {
            $this->db->query("
                SELECT COUNT(*) AS total
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'datos_pasante'
                  AND INDEX_NAME = 'uq_pasante_periodo'
            ");
            $hasUq = (int)($this->db->single()->total ?? 0);

            if ($hasUq === 0) {
                // 1. Agregar un índice regular en usuario_id para que la Foreign Key fk_pasante_usuario
                //    no impida eliminar el índice UNIQUE viejo (MySQL error 1553).
                $this->db->query("
                    SELECT COUNT(*) AS total
                    FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'datos_pasante'
                      AND INDEX_NAME = 'idx_usuario_id_tmp'
                ");
                $hasTmpIdx = (int)($this->db->single()->total ?? 0);
                if ($hasTmpIdx === 0) {
                    $this->db->query("ALTER TABLE `datos_pasante` ADD KEY `idx_usuario_id_tmp` (`usuario_id`)");
                    $this->db->execute();
                }

                // 2. Eliminar el índice UNIQUE previo sobre usuario_id (si existe)
                $this->db->query("
                    SELECT COUNT(*) AS total
                    FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'datos_pasante'
                      AND INDEX_NAME = 'usuario_id'
                ");
                $hasOldIdx = (int)($this->db->single()->total ?? 0);

                if ($hasOldIdx > 0) {
                    $this->db->query("ALTER TABLE `datos_pasante` DROP INDEX `usuario_id`");
                    $this->db->execute();
                }

                // 3. Crear el nuevo índice compuesto UNIQUE para el historial multi-período
                $this->db->query("ALTER TABLE `datos_pasante` ADD UNIQUE KEY `uq_pasante_periodo` (`usuario_id`, `periodo_id`)");
                $this->db->execute();

                // 4. Retirar el índice temporal (uq_pasante_periodo ya cubre usuario_id por ser su primera columna)
                $this->db->query("ALTER TABLE `datos_pasante` DROP INDEX `idx_usuario_id_tmp`");
                $this->db->execute();
            }
        } catch (\Throwable $e) {
            // Silencioso: no abortar el flujo normal de la aplicación
            error_log('[SGP-BUG-04] Auto-migración multi-período: ' . $e->getMessage());
        }
    }

    /**
     * Obtener una asignación específica por pasante_id (opcionalmente filtrada por período)
     */
    public function getById(int $pasanteId, int $periodoId = 0): ?object
    {
        $periodoFilter = '';
        if ($periodoId > 0) {
            $periodoFilter = "AND dpa.periodo_id = :pid";
        }

        $this->db->query("
            SELECT
                u.id                AS pasante_id,
                u.cedula,
                dp.nombres,
                dp.apellidos,
                COALESCE(inst.nombre, dpa.institucion_procedencia) AS institucion_procedencia,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia,
                COALESCE((
                    SELECT COUNT(*) * 8
                    FROM asistencias a
                    WHERE a.pasante_id = u.id
                      AND a.estado != 'Anulado'
                      AND a.fecha BETWEEN COALESCE(pa.fecha_inicio, '1970-01-01')
                                      AND COALESCE(pa.fecha_fin, CURDATE())
                ), 0)                                        AS horas_acumuladas,
                COALESCE(dpa.horas_meta, 1440)               AS horas_meta,
                dpa.fecha_inicio_pasantia,
                dpa.fecha_fin_estimada,
                dpa.observaciones,
                dpa.periodo_id,
                pa.nombre                                    AS periodo_nombre,
                d.nombre           AS departamento_nombre,
                tup.nombres        AS tutor_nombres,
                tup.apellidos      AS tutor_apellidos
            FROM usuarios u
            LEFT JOIN datos_personales dp   ON dp.usuario_id  = u.id
            LEFT JOIN datos_pasante    dpa  ON dpa.usuario_id = u.id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos    d    ON d.id = dpa.departamento_asignado_id
            LEFT JOIN usuarios         tu   ON tu.id = dpa.tutor_id
            LEFT JOIN datos_personales tup  ON tup.usuario_id = tu.id
            LEFT JOIN instituciones    inst ON dpa.institucion_procedencia = inst.id
            WHERE u.id = :uid
            {$periodoFilter}
            ORDER BY
                FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC,
                dpa.id DESC
            LIMIT 1
        ");
        $this->db->bind(':uid', $pasanteId);
        if ($periodoId > 0) {
            $this->db->bind(':pid', $periodoId);
        }
        $result = $this->db->single();
        return $result ?: null;
    }

    /**
     * Guardar/Actualizar una asignación [BUG-04 FIX Senior Architecture]
     *
     * Previene sobrescribir el historial de un pasante que ya cursó períodos previos:
     * 1. Verifica si existe registro en `datos_pasante` para la tupla (usuario_id, periodo_id).
     * 2. Si existe para ese período -> ACTUALIZA ese registro específico.
     * 3. Si no existe pero hay un registro huérfano (periodo_id IS NULL) -> Lo asocia al período.
     * 4. Si el pasante solo tiene registros de períodos anteriores (ej. Finalizado en cohorte previa)
     *    -> INSERTA una nueva fila vinculada al nuevo período, preservando el registro histórico anterior.
     * 5. Reactiva la cuenta en `usuarios` si estaba inactiva por cierre de cohorte anterior.
     */
    public function guardar($pasanteId, $departamentoId, $tutorIdVal, $horasMeta, $fechaInicio, $fechaFin, $observaciones = '', $periodoId = 0): bool
    {
        $this->asegurarEsquemaMultiPeriodo();

        $nuevoEstado = ($tutorIdVal && $departamentoId && $fechaInicio) ? 'Activo' : 'Pendiente';

        // Si no se pasó período explícito, resolver al período activo actual
        if ($periodoId <= 0) {
            $periodoActivo = $this->getPeriodoActivo();
            $periodoId     = (int)($periodoActivo->id ?? 0);
        }
        $periodoIdVal = $periodoId > 0 ? (int)$periodoId : null;

        // 1. Verificar si ya existe registro para ESTE período específico
        $registroPeriodo = null;
        if ($periodoIdVal !== null) {
            $this->db->query("SELECT id FROM datos_pasante WHERE usuario_id = :uid AND periodo_id = :pid LIMIT 1");
            $this->db->bind(':uid', $pasanteId);
            $this->db->bind(':pid', $periodoIdVal);
            $registroPeriodo = $this->db->single();
        }

        // 2. Verificar si existe registro sin período (periodo_id IS NULL)
        $this->db->query("SELECT id, institucion_id, institucion_procedencia FROM datos_pasante WHERE usuario_id = :uid AND periodo_id IS NULL LIMIT 1");
        $this->db->bind(':uid', $pasanteId);
        $registroSinPeriodo = $this->db->single();

        // 3. Obtener datos institucionales previos (para no perderlos al crear registro en nuevo período)
        $this->db->query("SELECT institucion_id, institucion_procedencia FROM datos_pasante WHERE usuario_id = :uid ORDER BY id DESC LIMIT 1");
        $this->db->bind(':uid', $pasanteId);
        $datosPrevios = $this->db->single();
        $instIdPrevio   = $datosPrevios->institucion_id ?? null;
        $instProcPrevia = $datosPrevios->institucion_procedencia ?? 'Sin Institución';

        $resultado = false;

        if ($registroPeriodo) {
            // CASO A: Actualizar registro existente de este mismo período
            $this->db->query("
                UPDATE datos_pasante
                SET departamento_asignado_id = :dept_id,
                    tutor_id                 = :tutor_id,
                    horas_meta               = :horas_meta,
                    fecha_inicio_pasantia    = :fecha_inicio,
                    fecha_fin_estimada       = :fecha_fin,
                    estado_pasantia          = :estado,
                    observaciones            = :obs
                WHERE id = :id
            ");
            $this->db->bind(':dept_id',      $departamentoId);
            $this->db->bind(':tutor_id',     $tutorIdVal);
            $this->db->bind(':horas_meta',   $horasMeta);
            $this->db->bind(':fecha_inicio', $fechaInicio);
            $this->db->bind(':fecha_fin',    $fechaFin);
            $this->db->bind(':estado',       $nuevoEstado);
            $this->db->bind(':obs',          $observaciones);
            $this->db->bind(':id',           $registroPeriodo->id);
            $resultado = $this->db->execute();

        } elseif ($registroSinPeriodo) {
            // CASO B: Vincular registro huérfano sin período asignado
            $this->db->query("
                UPDATE datos_pasante
                SET departamento_asignado_id = :dept_id,
                    tutor_id                 = :tutor_id,
                    horas_meta               = :horas_meta,
                    fecha_inicio_pasantia    = :fecha_inicio,
                    fecha_fin_estimada       = :fecha_fin,
                    estado_pasantia          = :estado,
                    observaciones            = :obs,
                    periodo_id               = :periodo_id
                WHERE id = :id
            ");
            $this->db->bind(':dept_id',      $departamentoId);
            $this->db->bind(':tutor_id',     $tutorIdVal);
            $this->db->bind(':horas_meta',   $horasMeta);
            $this->db->bind(':fecha_inicio', $fechaInicio);
            $this->db->bind(':fecha_fin',    $fechaFin);
            $this->db->bind(':estado',       $nuevoEstado);
            $this->db->bind(':obs',          $observaciones);
            $this->db->bind(':periodo_id',   $periodoIdVal);
            $this->db->bind(':id',           $registroSinPeriodo->id);
            $resultado = $this->db->execute();

        } else {
            // CASO C: Pasante nuevo o que cursó un período previo.
            // Insertamos nuevo registro en datos_pasante preservando el historial previo intacto.
            $this->db->query("
                INSERT INTO datos_pasante
                    (usuario_id, tipo_pasantia, departamento_asignado_id, tutor_id, horas_meta,
                     fecha_inicio_pasantia, fecha_fin_estimada, estado_pasantia, observaciones,
                     periodo_id, institucion_id, institucion_procedencia)
                VALUES
                    (:uid, 'Regular', :dept_id, :tutor_id, :horas_meta,
                     :fecha_inicio, :fecha_fin, :estado, :obs,
                     :periodo_id, :inst_id, :inst_proc)
            ");
            $this->db->bind(':uid',          $pasanteId);
            $this->db->bind(':dept_id',      $departamentoId);
            $this->db->bind(':tutor_id',     $tutorIdVal);
            $this->db->bind(':horas_meta',   $horasMeta);
            $this->db->bind(':fecha_inicio', $fechaInicio);
            $this->db->bind(':fecha_fin',    $fechaFin);
            $this->db->bind(':estado',       $nuevoEstado);
            $this->db->bind(':obs',          $observaciones);
            $this->db->bind(':periodo_id',   $periodoIdVal);
            $this->db->bind(':inst_id',      $instIdPrevio);
            $this->db->bind(':inst_proc',    $instProcPrevia);
            $resultado = $this->db->execute();
        }

        // Si la asignación se guardó con éxito, reactivar la cuenta de usuario
        // por si venía desactivada de un período académico anterior cerrado.
        if ($resultado) {
            $this->db->query("UPDATE usuarios SET estado = 'activo' WHERE id = :uid AND estado != 'activo'");
            $this->db->bind(':uid', $pasanteId);
            $this->db->execute();
        }

        return $resultado;
    }

    /**
     * Activar una pasantía
     */
    public function activar(int $pasanteId, int $periodoId = 0): bool
    {
        if ($periodoId > 0) {
            $where = "WHERE usuario_id = :uid AND periodo_id = :pid";
        } else {
            $where = "WHERE usuario_id = :uid AND estado_pasantia != 'Finalizado'";
        }
        $this->db->query("UPDATE datos_pasante SET estado_pasantia = 'Activo' {$where}");
        $this->db->bind(':uid', $pasanteId);
        if ($periodoId > 0) {
            $this->db->bind(':pid', $periodoId);
        }
        return $this->db->execute();
    }

    /**
     * Finalizar una pasantía
     */
    public function finalizar(int $pasanteId, int $periodoId = 0): bool
    {
        if ($periodoId > 0) {
            $where = "WHERE usuario_id = :uid AND periodo_id = :pid";
        } else {
            $where = "WHERE usuario_id = :uid AND estado_pasantia != 'Finalizado'";
        }
        $this->db->query("UPDATE datos_pasante SET estado_pasantia = 'Finalizado' {$where}");
        $this->db->bind(':uid', $pasanteId);
        if ($periodoId > 0) {
            $this->db->bind(':pid', $periodoId);
        }
        return $this->db->execute();
    }

    /**
     * Eliminar una asignación (resetear a 'Sin Asignar' sin borrar períodos finalizados previos)
     */
    public function eliminar(int $pasanteId, int $periodoId = 0): bool
    {
        if ($periodoId > 0) {
            $where = "WHERE usuario_id = :uid AND periodo_id = :pid";
        } else {
            $where = "WHERE usuario_id = :uid AND estado_pasantia != 'Finalizado'";
        }
        $this->db->query("
            UPDATE datos_pasante
            SET departamento_asignado_id = NULL,
                tutor_id                 = NULL,
                estado_pasantia          = 'Sin Asignar',
                fecha_inicio_pasantia    = NULL,
                fecha_fin_estimada       = NULL
            {$where}
        ");
        $this->db->bind(':uid', $pasanteId);
        if ($periodoId > 0) {
            $this->db->bind(':pid', $periodoId);
        }
        return $this->db->execute();
    }
}
