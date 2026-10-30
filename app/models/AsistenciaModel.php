<?php
/**
 * AsistenciaModel — Consultas SQL para el módulo de Asistencias
 *
 * ARQUITECTURA NORMALIZADA (3NF):
 *   - `usuarios`          : autenticación (correo, password, pin, rol, estado)
 *   - `datos_personales`  : biografía (cedula, nombres, apellidos)
 *   - `datos_pasante`     : lógica académica (horas, estado_pasantia, departamento)
 *   - `departamentos`     : catálogo de departamentos
 *   - `asistencias`       : registros de marcaje (fecha, hora, estado, método)
 *
 * @version 1.0 — Módulo de Consulta Rápida (Auditoría)
 */
class AsistenciaModel
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // ==========================================
    // CONSULTAS SQL PARA EL MODAL DE AUDITORÍA
    // ==========================================

    /**
     * Busca pasantes activos en tiempo real (Autocompletado).
     * Filtra por cédula, nombres o apellidos.
     *
     * @param string $query Término de búsqueda del usuario
     * @return array Lista de coincidencias (máx. 5)
     */
    public function buscarPasanteLive(string $query, int $tutorId = 0): array
    {
        $whereTutor = $tutorId > 0 ? " AND dpa.tutor_id = :tutor_id " : "";

        $this->db->query("
            SELECT u.id AS pasante_id, u.cedula, dp.nombres, dp.apellidos,
                   d.nombre AS departamento_nombre,
                   COALESCE(inst.nombre, dpa.institucion_procedencia, 'Sin especificar') AS institucion_nombre,
                   inst.representante_nombre,
                   pa.nombre AS periodo_nombre
            FROM   usuarios u
            INNER JOIN datos_personales dp  ON dp.usuario_id  = u.id
            LEFT  JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            LEFT  JOIN departamentos    d   ON d.id = dpa.departamento_asignado_id
            LEFT  JOIN instituciones   inst ON inst.id = dpa.institucion_id
            LEFT  JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE  u.rol_id = 3
              $whereTutor
              AND  (u.cedula LIKE :busqueda OR dp.nombres LIKE :busqueda OR dp.apellidos LIKE :busqueda)
            GROUP BY u.id
            ORDER BY CASE WHEN dpa.estado_pasantia = 'Activo' THEN 0 ELSE 1 END, IFNULL(dp.apellidos, u.correo) ASC
            LIMIT 5
        ");

        $this->db->bind(':busqueda', '%' . $query . '%');
        if ($tutorId > 0) {
            $this->db->bind(':tutor_id', $tutorId);
        }
        return $this->db->resultSet();
    }

    /**
     * Extrae los datos base del pasante y sus fechas de pasantía
     * para el Anillo de Progreso del Modal.
     *
     * @param int $id ID del usuario/pasante
     * @return object|false Perfil del pasante o false si no existe
     */
    public function obtenerPerfilParaAuditoria(int $id)
    {
        $this->db->query("
            SELECT u.id AS pasante_id, u.cedula,
                   dp.nombres, dp.apellidos, dp.telefono,
                   d.nombre AS departamento_nombre,
                   COALESCE(
                       NULLIF(TRIM(
                           CASE WHEN dpa.institucion_procedencia REGEXP '^[0-9]+$'
                                THEN COALESCE(inst.nombre, '')
                                ELSE COALESCE(dpa.institucion_procedencia, '')
                           END
                       ), ''),
                       'Sin especificar'
                   ) AS institucion,
                   inst.representante_nombre,
                   pa.nombre AS periodo_nombre,
                   CONCAT(COALESCE(td.nombres, ''), ' ', COALESCE(td.apellidos, '')) AS tutor_nombre,
                   dpa.fecha_inicio_pasantia AS fecha_inicio,
                   dpa.fecha_fin_estimada    AS fecha_fin,
                   COALESCE(
                       NULLIF(dpa.horas_meta, 0),
                       ROUND(DATEDIFF(pa.fecha_fin, pa.fecha_inicio) * 5/7) * 8,
                       1440
                   ) AS horas_meta
            FROM   usuarios u
            INNER JOIN datos_personales dp  ON dp.usuario_id  = u.id
            LEFT  JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            LEFT  JOIN departamentos    d   ON d.id = dpa.departamento_asignado_id
            LEFT  JOIN instituciones   inst ON inst.id = dpa.institucion_id
            LEFT  JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT  JOIN usuarios         t   ON t.id  = dpa.tutor_id
            LEFT  JOIN datos_personales td  ON td.usuario_id = t.id
            WHERE  u.id = :id
            ORDER  BY CASE WHEN dpa.estado_pasantia = 'Activo' THEN 0 ELSE 1 END, dpa.id DESC
            LIMIT  1
        ");

        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Extrae todo el historial de marcajes de un pasante específico,
     * ordenado por fecha descendente para la línea de tiempo.
     *
     * @param int $id ID del usuario/pasante
     * @return array Lista completa de registros de asistencia
     */
    public function obtenerHistorialCompletoPasante(int $id): array
    {
        $this->db->query("
            SELECT id, fecha, hora_registro, estado, metodo, motivo_justificacion
            FROM   asistencias
            WHERE  pasante_id = :id
            ORDER  BY fecha DESC, hora_registro DESC
        ");

        $this->db->bind(':id', $id);
        return $this->db->resultSet();
    }

    // ==========================================
    // MATEMÁTICA PRO-RATA (Opción B — Cálculo Dinámico)
    // Fuente de Verdad: tabla 'asistencias'. Jornada: 8h/día. Meta: 1440h.
    // ==========================================

    /**
     * Calcula el progreso de un pasante individual basado en días hábiles (Lun-Vie)
     * transcurridos desde su fecha de inicio hasta hoy (o su fecha de fin si ya terminó).
     * Regla: cada día hábil = 8h, sin importar el estado del registro de asistencia.
     *
     * @param int         $pasanteId ID del usuario/pasante
     * @param int         $horasMeta Meta en horas (default: 1440 = 180 días × 8h)
     * @param string|null $fechaFin  Fecha de fin efectiva (si no se pasa, se consulta la BD)
     * @return object
     */
    public function calcularProgresoProRata(int $pasanteId, int $horasMeta = 1440, ?string $fechaFin = null): object
    {
        $this->db->query("
            SELECT dpa.fecha_inicio_pasantia,
                   COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin) AS fecha_fin_efectiva
            FROM   datos_pasante dpa
            LEFT   JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE  dpa.usuario_id = :pid LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $info = $this->db->single();

        $fechaInicio = $info->fecha_inicio_pasantia ?? null;
        if ($fechaFin === null) {
            $fechaFin = $info->fecha_fin_efectiva ?? null;
        }

        $where = "pasante_id = :pid AND estado != 'Anulado'";
        if ($fechaInicio) $where .= " AND fecha >= :fi";
        if ($fechaFin)    $where .= " AND fecha <= :ff";

        $this->db->query("SELECT COUNT(*) AS dias_con_registro FROM asistencias WHERE {$where}");
        $this->db->bind(':pid', $pasanteId);
        if ($fechaInicio) $this->db->bind(':fi', $fechaInicio);
        if ($fechaFin)    $this->db->bind(':ff', $fechaFin);
        $row = $this->db->single();

        $dias  = (int)($row->dias_con_registro ?? 0);
        $horas = min($dias * 8, $horasMeta);
        $pct   = $horasMeta > 0 ? min(100, round(($horas / $horasMeta) * 100, 1)) : 0;

        return (object)[
            'dias_presentes'  => $dias,
            'horas_mostradas' => $horas,
            'horas_meta'      => $horasMeta,
            'porcentaje'      => $pct,
        ];
    }

    /**
     * Calcula el progreso basado en días hábiles para TODOS los pasantes activos.
     *
     * @return array Keyed by pasante_id → { dias_presentes, horas_mostradas, horas_meta, porcentaje }
     */
    public function getProgresoTodosActivos(): array
    {
        $this->db->query("
            SELECT dpa.usuario_id AS pasante_id,
                   COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                   COUNT(a.id) AS dias_con_registro
            FROM datos_pasante dpa
            INNER JOIN usuarios u ON u.id = dpa.usuario_id AND u.rol_id = 3
            LEFT JOIN asistencias a ON a.pasante_id = dpa.usuario_id AND a.estado != 'Anulado'
            WHERE dpa.estado_pasantia = 'Activo'
            GROUP BY dpa.usuario_id, dpa.horas_meta
        ");
        $filas = $this->db->resultSet();

        $mapa = [];
        foreach ($filas as $fila) {
            $meta  = (int)$fila->horas_meta;
            $dias  = (int)$fila->dias_con_registro;
            $horas = min($dias * 8, $meta);
            $pct   = $meta > 0 ? min(100, round(($horas / $meta) * 100, 1)) : 0;

            $mapa[(int)$fila->pasante_id] = (object)[
                'dias_presentes'  => $dias,
                'horas_mostradas' => $horas,
                'horas_meta'      => $meta,
                'porcentaje'      => $pct,
            ];
        }
        return $mapa;
    }

    private function _contarDiasHabiles(string $fechaInicio, string $fechaLimite): int
    {
        $cur   = new DateTime($fechaInicio);
        $fin   = new DateTime($fechaLimite);
        $count = 0;
        while ($cur <= $fin) {
            if ((int)$cur->format('N') <= 5) { // 1=Lun … 5=Vie
                $count++;
            }
            $cur->modify('+1 day');
        }
        return $count;
    }

    // ==========================================
    // FASE 2: AUTO-FILL + RETARDOS + FERIADOS
    // ==========================================

    /**
     * Obtiene los feriados (fechas no laborables) dentro de un rango.
     * Retorna un array asociativo [ 'YYYY-MM-DD' => 'Descripción del feriado' ].
     *
     * @param string $fechaInicio Inicio del rango (Y-m-d)
     * @param string $fechaFin    Fin del rango (Y-m-d)
     * @return array  Mapa fecha => descripción
     */
    public function getFeriadosEnRango(string $fechaInicio, string $fechaFin): array
    {
        $this->db->query("
            SELECT fecha, nombre AS descripcion
            FROM   dias_feriados
            WHERE  fecha >= :inicio AND fecha <= :fin
              AND  es_laborable = 0
            ORDER  BY fecha ASC
        ");
        $this->db->bind(':inicio', $fechaInicio);
        $this->db->bind(':fin',    $fechaFin);
        $filas = $this->db->resultSet();

        $mapa = [];
        foreach ($filas as $fila) {
            $mapa[$fila->fecha] = $fila->descripcion;
        }
        return $mapa;
    }

    /**
     * Auto-fill silencioso de días vacíos como "Ausente".
     *
     * Por cada pasante activo (estado_pasantia = 'Activo'), recorre los días
     * hábiles (L-V) desde su fecha_inicio_pasantia hasta AYER (nunca hoy ni
     * el futuro) y genera un INSERT IGNORE con estado='Ausente', es_auto_fill=1
     * para cada día sin registro existente, siempre que el día NO sea feriado.
     *
     * REGLAS DE NEGOCIO CRÍTICAS:
     *   - Solo genera registros para días pasados (< hoy).
     *   - Solo días L-V (N < 6 en DateTime::format('N')).
     *   - INSERT IGNORE garantiza idempotencia absoluta.
     *   - es_auto_fill = 1 → excluidos del cálculo Pro-Rata.
     *   - Nunca sobreescribe registros existentes (INSERT IGNORE).
     *   - Los feriados se saltan silenciosamente.
     *
     * @return int Cantidad de filas insertadas
     */
    public function rellenarDiasVacios(): int
    {
        // Obtener todos los pasantes activos con sus fechas de pasantía.
        // COALESCE garantiza que si el pasante no tiene fechas individuales,
        // se usa la fecha del período académico asignado (fuente única de verdad).
        $this->db->query("
            SELECT u.id AS pasante_id,
                   COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio,
                   COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin
            FROM   usuarios u
            INNER JOIN datos_pasante       dpa ON dpa.usuario_id = u.id
            LEFT  JOIN periodos_academicos pa  ON pa.id = dpa.periodo_id
            WHERE  u.estado = 'activo'
              AND  dpa.estado_pasantia = 'Activo'
              AND  COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) IS NOT NULL
        ");
        $pasantes = $this->db->resultSet();

        if (empty($pasantes)) {
            return 0;
        }

        // Obtener feriados en el rango global (inicio más antiguo → ayer)
        $fechaMin = null;
        foreach ($pasantes as $p) {
            if (!$fechaMin || $p->fecha_inicio < $fechaMin) {
                $fechaMin = $p->fecha_inicio;
            }
        }

        $ayer = date('Y-m-d', strtotime('-1 day'));
        $feriados = $this->getFeriadosEnRango($fechaMin, $ayer);

        $totalInsertados = 0;

        foreach ($pasantes as $pasante) {
            $pid = (int)$pasante->pasante_id;

            try {
                $fInicio = new DateTime($pasante->fecha_inicio);
                // El límite superior es el mínimo entre ayer y fecha_fin
                $limSup = $ayer;
                if (!empty($pasante->fecha_fin) && $pasante->fecha_fin < $ayer) {
                    $limSup = $pasante->fecha_fin;
                }
                $fFin = new DateTime($limSup);
            } catch (\Exception $e) {
                continue; // Fecha inválida, saltar
            }

            // Si la pasantía aún no comenzó o límite <= inicio, nada que rellenar
            if ($fInicio > $fFin) {
                continue;
            }

            // Obtener fechas que YA tienen registro para este pasante en el rango
            $this->db->query("
                SELECT fecha FROM asistencias
                WHERE  pasante_id = :pid
                  AND  fecha >= :inicio AND fecha <= :fin
            ");
            $this->db->bind(':pid',    $pid);
            $this->db->bind(':inicio', $pasante->fecha_inicio);
            $this->db->bind(':fin',    $limSup);
            $filas = $this->db->resultSet();

            $fechasExistentes = [];
            foreach ($filas as $f) {
                $fechasExistentes[$f->fecha] = true;
            }

            // Preparar la query de inserción (reutilizada en el loop)
            $cursor = clone $fInicio;

            while ($cursor <= $fFin) {
                $fechaStr = $cursor->format('Y-m-d');
                $dow      = (int)$cursor->format('N'); // 1=Lun … 7=Dom

                // Solo días L-V, no existentes
                if ($dow < 6 && !isset($fechasExistentes[$fechaStr])) {
                    if (isset($feriados[$fechaStr])) {
                        // Feriado no laborable: Justificado con nombre real del feriado
                        $this->db->query("
                            INSERT IGNORE INTO asistencias
                                (pasante_id, fecha, hora_registro, estado, metodo, motivo_justificacion, es_auto_fill)
                            VALUES
                                (:pid, :fecha, '00:00:00', 'Justificado', 'Sistema', :motivo, 1)
                        ");
                        $this->db->bind(':motivo', $feriados[$fechaStr]);
                    } else {
                        // Día hábil sin registro: Presente (supuesto base — si faltó el admin lo corrige)
                        $this->db->query("
                            INSERT IGNORE INTO asistencias
                                (pasante_id, fecha, hora_registro, estado, metodo, es_auto_fill)
                            VALUES
                                (:pid, :fecha, '00:00:00', 'Presente', 'Sistema', 1)
                        ");
                    }
                    
                    $this->db->bind(':pid',   $pid);
                    $this->db->bind(':fecha', $fechaStr);
                    if ($this->db->execute()) {
                        $totalInsertados++;
                    }
                }

                $cursor->modify('+1 day');
            }
        }

        return $totalInsertados;
    }

    // ==========================================
    // FASE 2: MATEMÁTICA DE CALENDARIO (Cohortes)
    // ==========================================

    /**
     * Calcula las "horas esperadas" según el calendario transcurrido desde la fecha de inicio.
     * Ignora sábados y domingos.
     *
     * @param string $fechaInicio Fecha de inicio (Y-m-d)
     * @param int $horasMeta Total de horas (ej. 1440)
     * @return object Data con días transcurridos y horas calendario.
     */
    public function calcularProgresoPorCalendario(string $fechaInicio, int $horasMeta = 1440): object
    {
        try {
            $fInicio = new DateTime($fechaInicio);
            $fHoy    = new DateTime(date('Y-m-d')); // Día de hoy puntual sin horas
        } catch (Exception $e) {
            return (object)[
                'dias_habiles_transcurridos' => 0,
                'horas_calendario'           => 0,
                'porcentaje_calendario'      => 0,
                'dias_habiles_restantes'     => 0
            ];
        }
        
        // Si la pasantía aún no empieza (en el futuro), todo está en 0
        if ($fInicio > $fHoy) {
            return (object)[
                'dias_habiles_transcurridos' => 0,
                'horas_calendario'           => 0,
                'porcentaje_calendario'      => 0,
                'dias_habiles_restantes'     => max(0, $horasMeta / 8)
            ];
        }
        
        $diasHabiles = 0;
        $fechaCursor = clone $fInicio;
        
        while ($fechaCursor <= $fHoy) {
            $dow = (int)$fechaCursor->format('N');
            if ($dow < 6) { // Lunes a Viernes
                $diasHabiles++;
            }
            $fechaCursor->modify('+1 day');
        }
        
        $horasCalendario = $diasHabiles * 8;
        if ($horasCalendario > $horasMeta) {
            $horasCalendario = $horasMeta;
        }
        
        $porcentaje = ($horasMeta > 0) ? min(100, round(($horasCalendario / $horasMeta) * 100, 1)) : 0;
        
        return (object)[
            'dias_habiles_transcurridos' => $diasHabiles,
            'horas_calendario'           => $horasCalendario,
            'porcentaje_calendario'      => $porcentaje,
            'dias_habiles_restantes'     => max(0, ($horasMeta / 8) - $diasHabiles)
        ];
    }

    /**
     * Auto-finaliza pasantes cuya fecha_fin ya pasó y aún están "Activo".
     * Regla: si CURDATE() > COALESCE(fecha_fin_estimada, periodo.fecha_fin), → Finalizado.
     * También desactiva la cuenta si no tiene otra pasantía activa.
     *
     * @return int Cantidad de pasantes finalizados
     */
    public function autoFinalizarVencidos(): int
    {
        $this->db->query("
            SELECT dpa.usuario_id
            FROM datos_pasante dpa
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE dpa.estado_pasantia = 'Activo'
              AND COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin) IS NOT NULL
              AND COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin) < CURDATE()
        ");
        $pendientes = $this->db->resultSet();

        if (empty($pendientes)) return 0;

        $ids = array_map(fn($p) => (int)$p->usuario_id, $pendientes);

        $this->db->query("
            UPDATE datos_pasante
            SET estado_pasantia = 'Finalizado'
            WHERE usuario_id IN (" . implode(',', $ids) . ")
              AND estado_pasantia = 'Activo'
        ");
        $this->db->execute();
        $finalizados = $this->db->rowCount();

        if ($finalizados > 0) {
            $this->db->query("
                UPDATE usuarios u
                SET u.estado = 'inactivo'
                WHERE u.rol_id = 3
                  AND u.estado = 'activo'
                  AND NOT EXISTS (
                      SELECT 1 FROM datos_pasante dpa2
                      WHERE dpa2.usuario_id = u.id AND dpa2.estado_pasantia = 'Activo'
                  )
            ");
            $this->db->execute();

            $this->db->query("
                INSERT INTO bitacora (usuario_id, accion, ip_address, detalles)
                VALUES (0, 'AUTO_FINALIZAR_PASANTES', :ip, :det)
            ");
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $this->db->bind(':det', json_encode(['finalizados' => $finalizados, 'ids' => $ids]));
            $this->db->execute();
        }

        return $finalizados;
    }
}

