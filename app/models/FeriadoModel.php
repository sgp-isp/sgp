<?php
/**
 * FeriadoModel — Gestión de Días Feriados
 *
 * PROPÓSITO:
 *   Permite al administrador gestionar los días no laborables del año
 *   para que el sistema de asistencias los excluya del auto-fill de ausencias.
 *
 * REGLAS DE NEGOCIO:
 *   - Tabla: dias_feriados (id, fecha, nombre, tipo, aplica_departamento_id, created_at)
 *   - tipo ENUM: 'Nacional', 'Regional', 'Institucional'
 *   - No se permiten fechas duplicadas
 *   - Los feriados pasados no se eliminan (protección de auditoría histórica)
 *
 * @version 1.1
 */
declare(strict_types=1);

class FeriadoModel
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // =========================================================
    // CONSULTAS DE LECTURA
    // =========================================================

    /**
     * Retorna todos los feriados ordenados por fecha ascendente.
     * Opcionalmente filtra solo los del año indicado.
     */
    public function obtenerTodos(?int $anio = null): array
    {
        if ($anio !== null) {
            $this->db->query("
                SELECT id, fecha, nombre, tipo, es_laborable, created_at
                FROM   dias_feriados
                WHERE  YEAR(fecha) = :anio
                ORDER  BY fecha ASC
            ");
            $this->db->bind(':anio', $anio);
        } else {
            $this->db->query("
                SELECT id, fecha, nombre, tipo, es_laborable, created_at
                FROM   dias_feriados
                ORDER  BY fecha ASC
            ");
        }
        return $this->db->resultSet() ?: [];
    }

    /**
     * Obtiene los feriados del año actual y el siguiente
     * para mostrar en la vista de configuración.
     */
    public function obtenerVigentes(): array
    {
        $anioActual = (int) date('Y');
        $this->db->query("
            SELECT id, fecha, nombre, tipo, es_laborable, created_at
            FROM   dias_feriados
            WHERE  YEAR(fecha) >= :anio
            ORDER  BY fecha ASC
            LIMIT  200
        ");
        $this->db->bind(':anio', $anioActual);
        return $this->db->resultSet() ?: [];
    }

    /**
     * Verifica si una fecha ya está registrada como feriado.
     */
    public function existeFecha(string $fecha): bool
    {
        $this->db->query("SELECT COUNT(*) AS total FROM dias_feriados WHERE fecha = :fecha");
        $this->db->bind(':fecha', $fecha);
        $row = $this->db->single();
        return (int)($row->total ?? 0) > 0;
    }

    /**
     * Total de feriados registrados en el año indicado.
     */
    public function contarPorAnio(int $anio): int
    {
        $this->db->query("SELECT COUNT(*) AS total FROM dias_feriados WHERE YEAR(fecha) = :anio");
        $this->db->bind(':anio', $anio);
        $row = $this->db->single();
        return (int)($row->total ?? 0);
    }

    // =========================================================
    // OPERACIONES DE ESCRITURA
    // =========================================================

    /**
     * Registra un nuevo feriado.
     *
     * @param string $fecha   Formato Y-m-d
     * @param string $nombre  Descripción corta (ej. "Día de la Independencia")
     * @param string $tipo    Nacional | Regional | Institucional
     * @return bool
     */
    public function crear(string $fecha, string $nombre, string $tipo = 'Nacional', int $esLaborable = 0): bool
    {
        $tiposValidos = ['Nacional', 'Regional', 'Institucional'];
        if (!in_array($tipo, $tiposValidos, true)) {
            $tipo = 'Nacional';
        }

        $this->db->query("
            INSERT INTO dias_feriados (fecha, nombre, tipo, es_laborable, created_at)
            VALUES (:fecha, :nombre, :tipo, :es_lab, NOW())
        ");
        $this->db->bind(':fecha',  $fecha);
        $this->db->bind(':nombre', $nombre);
        $this->db->bind(':tipo',   $tipo);
        $this->db->bind(':es_lab', $esLaborable ? 1 : 0, PDO::PARAM_INT);
        return $this->db->execute();
    }

    /**
     * Elimina un feriado por ID.
     * Solo se permite eliminar feriados futuros (protección de auditoría histórica).
     *
     * @return bool  true si se eliminó, false si era pasado o no existía
     */
    public function eliminar(int $id): bool
    {
        $this->db->query("DELETE FROM dias_feriados WHERE id = :id AND fecha >= CURDATE()");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Devuelve un feriado específico por ID.
     */
    public function obtenerPorId(int $id)
    {
        $this->db->query("SELECT * FROM dias_feriados WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Sincroniza los registros de asistencia existentes cuando se registra o modifica un feriado.
     *
     * - Si el feriado es NO laborable (es_laborable=0):
     *     · Convierte los auto-fill 'Ausente' de esa fecha → 'Justificado' (el pasante recibe crédito)
     * - Si el feriado pasa a ser laborable (es_laborable=1):
     *     · Revierte los auto-fill 'Justificado' de sistema de esa fecha → 'Ausente'
     * - Los registros manuales (es_auto_fill=0) nunca se tocan.
     *
     * @return array ['corregidos' => int, 'conflictos_manuales' => int]
     *               corregidos          = registros auto-fill actualizados
     *               conflictos_manuales = registros manuales Presente en feriado no laborable (requieren revisión)
     */
    public function sincronizarAsistencias(string $fecha, int $esLaborable): array
    {
        $corregidos = 0;

        if ($esLaborable === 0) {
            // Feriado NO laborable: auto-fill Ausente → Justificado
            $this->db->query("
                UPDATE asistencias
                SET    estado  = 'Justificado',
                       metodo  = 'Sistema',
                       motivo_justificacion = 'Feriado no laborable registrado retroactivamente'
                WHERE  fecha       = :fecha
                  AND  es_auto_fill = 1
                  AND  estado       = 'Ausente'
            ");
            $this->db->bind(':fecha', $fecha);
            $this->db->execute();
            $corregidos = $this->db->rowCount();

            // Contar registros manuales Presente en ese feriado (conflicto potencial)
            $this->db->query("
                SELECT COUNT(*) AS total FROM asistencias
                WHERE fecha = :fecha AND es_auto_fill = 0 AND estado = 'Presente'
            ");
            $this->db->bind(':fecha', $fecha);
            $conflictos = (int)($this->db->single()->total ?? 0);
        } else {
            // Feriado laborable: revertir auto-fill Justificado de sistema → Ausente
            $this->db->query("
                UPDATE asistencias
                SET    estado  = 'Ausente',
                       motivo_justificacion = NULL
                WHERE  fecha       = :fecha
                  AND  es_auto_fill = 1
                  AND  estado       = 'Justificado'
                  AND  metodo       = 'Sistema'
            ");
            $this->db->bind(':fecha', $fecha);
            $this->db->execute();
            $corregidos = $this->db->rowCount();
            $conflictos = 0;
        }

        return ['corregidos' => $corregidos, 'conflictos_manuales' => $conflictos];
    }

    /**
     * Actualiza el nombre y tipo de un feriado (la fecha no se modifica).
     * Aplica tanto a feriados pasados como futuros.
     */
    public function actualizar(int $id, string $nombre, string $tipo, int $esLaborable = 0): bool
    {
        $tiposValidos = ['Nacional', 'Regional', 'Institucional'];
        if (!in_array($tipo, $tiposValidos, true)) {
            $tipo = 'Nacional';
        }

        $this->db->query("
            UPDATE dias_feriados
            SET    nombre      = :nombre,
                   tipo        = :tipo,
                   es_laborable= :es_lab
            WHERE  id          = :id
        ");
        $this->db->bind(':nombre', $nombre);
        $this->db->bind(':tipo',   $tipo);
        $this->db->bind(':es_lab', $esLaborable ? 1 : 0, PDO::PARAM_INT);
        $this->db->bind(':id',     $id);
        return $this->db->execute();
    }
}
