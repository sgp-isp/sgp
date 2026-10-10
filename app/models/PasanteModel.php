<?php
/**
 * PasanteModel - Gestión de Datos de Pasantes
 *
 * ARQUITECTURA NORMALIZADA (3NF):
 *   - `usuarios`        : autenticación (correo, password, pin, rol, estado)
 *   - `datos_personales`: biografía (cedula, nombres, apellidos, cargo, teléfono)
 *   - `datos_pasante`   : lógica académica (horas, estado_pasantia, departamento)
 *
 * @version 4.0 — Arquitectura Normalizada con JOINs
 */
class PasanteModel
{
    private $db;

    public function __construct()
    {
        $config = require '../app/config/config.php';
        $this->db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        // [OPT-01] Cargar helper centralizado de fechas
        require_once APPROOT . '/helpers/DateHelper.php';
    }

    // ────────────────────────────────────────────────────────────────
    // CÁLCULO DE FECHAS — DÍAS HÁBILES
    // ────────────────────────────────────────────────────────────────

    /**
     * Calcular Fecha Fin Estimada saltando Sabados y Domingos.
     * [OPT-01] Ahora delega al DateHelper centralizado.
     *
     * @param string $fecha_inicio  Formato Y-m-d
     * @param int    $dias_habiles  Por defecto 180 (aprox 9 meses laborales)
     * @return string Fecha fin en formato Y-m-d
     */
    public function calcularFechaFin(string $fecha_inicio, int $dias_habiles = 180): string
    {
        return DateHelper::calcularFechaFin($fecha_inicio, $dias_habiles);
    }

    // ────────────────────────────────────────────────────────────────
    // CONSULTAS
    // ────────────────────────────────────────────────────────────────

    /**
     * Obtener todos los pasantes (rol_id = 3) con datos de las 3 tablas.
     *
     * JOIN con datos_personales (cedula, nombres, apellidos)
     * JOIN con datos_pasante    (horas_acumuladas, estado_pasantia)
     *
     * @return array Lista de pasantes como objetos stdClass
     */
    public function getAll(int $periodoId = 0): array
    {
        // [BUG-02 FIX] Si no se pasa periodoId, detectar automáticamente el período activo.
        // Sin este fix, periodoId=0 devolvía TODOS los pasantes de toda la historia.
        if ($periodoId <= 0) {
            $this->db->query("
                SELECT id FROM periodos_academicos
                WHERE estado = 'Activo'
                ORDER BY fecha_inicio DESC LIMIT 1
            ");
            $pActivo   = $this->db->single();
            $periodoId = (int)($pActivo->id ?? 0);
        }

        // Con período conocido: filtrar solo ese período (+ sin asignar)
        // Sin período activo: mostrar únicamente los sin asignar para que el admin los gestione
        if ($periodoId > 0) {
            $periodoWhere = 'AND (dpa.periodo_id = :periodo_id OR dpa.periodo_id IS NULL)';
        } else {
            $periodoWhere = 'AND dpa.periodo_id IS NULL';
        }

        // [OPT-01] Usar DateHelper centralizado en lugar de macro SQL inline duplicada
        $diasHabilesSQL = DateHelper::diasHabilesHastaHoySQL();

        $this->db->query("
            SELECT t.*,
                ROUND(t.horas_acumuladas / NULLIF(t.horas_meta, 0) * 100, 2) AS progreso_porcentaje
            FROM (
                SELECT
                    u.id,
                    u.cedula,
                    dp.nombres,
                    dp.apellidos,
                    dp.cargo,
                    u.correo,
                    dp.telefono                                          AS telefono,
                    u.estado                                             AS activo,
                    u.pin_asistencia,
                    COALESCE(dpa.estado_pasantia, 'Sin Asignar')        AS estado_pasantia,
                    CASE WHEN dpa.fecha_inicio_pasantia IS NOT NULL THEN
                        LEAST(
                            {$diasHabilesSQL} * 8,
                            COALESCE(NULLIF(dpa.horas_meta, 0), ROUND(DATEDIFF(pa.fecha_fin, pa.fecha_inicio) * 5/7) * 8, 1440)
                        )
                    ELSE 0 END                                           AS horas_acumuladas,
                    COALESCE(NULLIF(dpa.horas_meta, 0), ROUND(DATEDIFF(pa.fecha_fin, pa.fecha_inicio) * 5/7) * 8, 1440) AS horas_meta,
                    dpa.fecha_inicio_pasantia                            AS fecha_inicio,
                    dpa.fecha_fin_estimada                               AS fecha_fin_estimada,
                    dpa.institucion_procedencia,
                    u.created_at                                         AS fecha_registro,
                    d.nombre                                             AS departamento_nombre,
                    COALESCE(inst.nombre, dpa.institucion_procedencia)   AS institucion_nombre,
                    inst.representante_nombre                            AS institucion_representante,
                    pa.nombre                                            AS periodo_nombre
                FROM usuarios u
                LEFT JOIN datos_personales   dp   ON dp.usuario_id  = u.id
                LEFT JOIN datos_pasante      dpa  ON dpa.usuario_id = u.id
                LEFT JOIN departamentos      d    ON d.id = dpa.departamento_asignado_id
                LEFT JOIN instituciones      inst ON inst.id = dpa.institucion_id
                LEFT JOIN periodos_academicos pa  ON pa.id = dpa.periodo_id
                WHERE u.rol_id = 3 {$periodoWhere}
            ) AS t
            ORDER BY
                FIELD(t.estado_pasantia, 'Sin Asignar', 'Activo', 'Finalizado'),
                IFNULL(t.apellidos, t.correo) ASC
        ");

        if ($periodoId > 0) {
            $this->db->bind(':periodo_id', $periodoId);
        }

        return $this->db->resultSet() ?: [];
    }

    /**
     * Obtener un pasante por su ID de usuario.
     *
     * @param int $usuarioId
     * @return object|null stdClass con todos los datos o null
     */
    public function getByUsuarioId(int $usuarioId, int $periodoId = 0): ?object
    {
        // [OPT-01] Usar DateHelper centralizado — elimina duplicacion de formula SQL
        $diasHabilesSQL = DateHelper::diasHabilesHastaHoySQL();

        $periodoFilter = '';
        if ($periodoId > 0) {
            $periodoFilter = 'AND dpa.periodo_id = :pid';
        }

        $this->db->query("
            SELECT t.*,
                ROUND(t.horas_acumuladas / NULLIF(t.horas_meta, 0) * 100, 2) AS progreso_porcentaje
            FROM (
                SELECT
                    u.id,
                    u.cedula,
                    dp.nombres,
                    dp.apellidos,
                    dp.cargo,
                    u.correo,
                    dp.telefono          AS telefono,
                    dp.genero            AS genero,
                    dp.fecha_nacimiento,
                    u.estado             AS activo,
                    u.pin_asistencia,
                    COALESCE(dpa.estado_pasantia, 'Sin Asignar')      AS estado_pasantia,
                    CASE WHEN dpa.fecha_inicio_pasantia IS NOT NULL THEN
                        LEAST(
                            {$diasHabilesSQL} * 8,
                            COALESCE(NULLIF(dpa.horas_meta, 0), ROUND(DATEDIFF(pa.fecha_fin, pa.fecha_inicio) * 5/7) * 8, 1440)
                        )
                    ELSE 0 END                                         AS horas_acumuladas,
                    COALESCE(NULLIF(dpa.horas_meta, 0), ROUND(DATEDIFF(pa.fecha_fin, pa.fecha_inicio) * 5/7) * 8, 1440) AS horas_meta,
                    dpa.fecha_inicio_pasantia                          AS fecha_inicio,
                    dpa.fecha_fin_estimada                             AS fecha_fin_estimada,
                    dpa.institucion_procedencia,
                    u.created_at  AS fecha_registro,
                    d.nombre      AS departamento_nombre,
                    COALESCE(inst.nombre, dpa.institucion_procedencia) AS institucion_nombre,
                    inst.representante_nombre                          AS institucion_representante,
                    pa.nombre     AS periodo_nombre,
                    dpa.periodo_id
                FROM usuarios u
                LEFT JOIN datos_personales   dp   ON dp.usuario_id  = u.id
                LEFT JOIN datos_pasante      dpa  ON dpa.usuario_id = u.id
                LEFT JOIN departamentos      d    ON d.id = dpa.departamento_asignado_id
                LEFT JOIN instituciones      inst ON inst.id = dpa.institucion_id
                LEFT JOIN periodos_academicos pa  ON pa.id = dpa.periodo_id
                WHERE u.id = :uid AND u.rol_id = 3
                {$periodoFilter}
                ORDER BY
                    FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC,
                    dpa.id DESC
            ) AS t
            LIMIT 1
        ");

        $this->db->bind(':uid', $usuarioId);
        if ($periodoId > 0) {
            $this->db->bind(':pid', $periodoId);
        }
        $result = $this->db->single();

        return $result ?: null;
    }

    // ────────────────────────────────────────────────────────────────
    // ASIGNACIÓN
    // ────────────────────────────────────────────────────────────────

    /**
     * Asignar pasante a un departamento y marcar la pasantía como Activa.
     *
     * ESTRATEGIA: Actualiza (o crea) el registro en datos_pasante.
     * Si el pasante aún no tiene fila en datos_pasante, la inserta (UPSERT).
     *
     * @param int    $pasanteId
     * @param int    $departamentoId
     * @param string $fechaInicio     Y-m-d
     * @return bool
     */
    public function asignar(int $pasanteId, int $departamentoId, string $fechaInicio): bool
    {
        $fechaFin = $this->calcularFechaFin($fechaInicio, 180);

        // UPSERT: si ya existe fila en datos_pasante → UPDATE, si no → INSERT
        $this->db->query("
            INSERT INTO datos_pasante
                (usuario_id, departamento_asignado_id, fecha_inicio_pasantia, fecha_fin_estimada, estado_pasantia)
            VALUES
                (:uid, :dept_id, :fecha_inicio, :fecha_fin, 'Activo')
            ON DUPLICATE KEY UPDATE
                departamento_asignado_id = VALUES(departamento_asignado_id),
                fecha_inicio_pasantia    = VALUES(fecha_inicio_pasantia),
                fecha_fin_estimada       = VALUES(fecha_fin_estimada),
                estado_pasantia          = 'Activo'
        ");

        $this->db->bind(':uid',          $pasanteId);
        $this->db->bind(':dept_id',      $departamentoId);
        $this->db->bind(':fecha_inicio', $fechaInicio);
        $this->db->bind(':fecha_fin',    $fechaFin);

        return $this->db->execute();
    }

    /**
     * Sumar horas acumuladas a un pasante.
     *
     * @param int $pasanteId
     * @param int $horas
     * @return bool
     */
    public function sumarHoras(int $pasanteId, int $horas): bool
    {
        $this->db->query("
            UPDATE datos_pasante
            SET horas_acumuladas = horas_acumuladas + :horas
            WHERE usuario_id = :pasante_id
        ");

        $this->db->bind(':horas',      $horas);
        $this->db->bind(':pasante_id', $pasanteId);

        return $this->db->execute();
    }

    /**
     * Marcar pasantía como Finalizada.
     *
     * @param int $pasanteId
     * @return bool
     */
    public function finalizar(int $pasanteId): bool
    {
        $this->db->query("
            UPDATE datos_pasante
            SET estado_pasantia = 'Finalizado'
            WHERE usuario_id = :pasante_id
        ");

        $this->db->bind(':pasante_id', $pasanteId);

        return $this->db->execute();
    }
}
