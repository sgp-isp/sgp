<?php
/**
 * EvaluacionModel - Gestión de Evaluaciones de Pasantes
 * 
 * Encapsula la lógica para interactuar con la tabla `evaluaciones`.
 */
class EvaluacionModel {
    private $db;

    public function __construct() {
        $config = require '../app/config/config.php';
        $this->db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
    }

    /**
     * Obtener todas las evaluaciones
     */
    public function getAll() {
        $this->db->query("
            SELECT 
                e.*,
                dp.nombres   AS pasante_nombres,
                dp.apellidos AS pasante_apellidos,
                u.cedula     AS pasante_cedula,
                tp.nombres   AS tutor_nombres,
                tp.apellidos AS tutor_apellidos
            FROM evaluaciones e
            LEFT JOIN datos_personales dp ON dp.usuario_id = e.pasante_id
            LEFT JOIN usuarios         u  ON u.id = e.pasante_id
            LEFT JOIN datos_personales tp ON tp.usuario_id = e.tutor_id
            ORDER BY e.created_at DESC
        ");
        return $this->db->resultSet();
    }

    /**
     * Obtener evaluaciones de los pasantes de un tutor específico
     */
    public function getByTutor(int $tutorId) {
        $this->db->query("
            SELECT
                e.*,
                dp.nombres   AS pasante_nombres,
                dp.apellidos AS pasante_apellidos,
                u.cedula     AS pasante_cedula,
                tp.nombres   AS tutor_nombres,
                tp.apellidos AS tutor_apellidos
            FROM evaluaciones e
            LEFT JOIN datos_personales dp ON dp.usuario_id = e.pasante_id
            LEFT JOIN usuarios         u  ON u.id = e.pasante_id
            LEFT JOIN datos_personales tp ON tp.usuario_id = e.tutor_id
            WHERE e.tutor_id = :tutor_id
            ORDER BY e.created_at DESC
        ");
        $this->db->bind(':tutor_id', $tutorId);
        return $this->db->resultSet();
    }

    /**
     * Guardar una nueva evaluación
     */
    public function guardar($pasanteId, $tutorId, $fecha, $lapso, $promedio, $obs, $valores, $criterios) {
        $this->db->query("
            INSERT INTO evaluaciones 
                (pasante_id, tutor_id, fecha_evaluacion, lapso_academico,
                 criterio_iniciativa, criterio_interes, criterio_conocimiento,
                 criterio_analisis, criterio_comunicacion, criterio_aprendizaje,
                 criterio_companerismo, criterio_cooperacion, criterio_puntualidad,
                 criterio_presentacion, criterio_desarrollo, criterio_analisis_res,
                 criterio_conclusiones, criterio_recomendacion,
                 promedio_final, observaciones)
            VALUES 
                (:pasante, :tutor, :fecha, :lapso,
                 :c1, :c2, :c3, :c4, :c5, :c6, :c7, :c8, :c9, :c10, :c11, :c12, :c13, :c14,
                 :promedio, :obs)
        ");

        $this->db->bind(':pasante', $pasanteId);
        $this->db->bind(':tutor',   $tutorId);
        $this->db->bind(':fecha',   $fecha);
        $this->db->bind(':lapso',   $lapso ?: null);
        
        $i = 1;
        foreach ($criterios as $c) {
            $this->db->bind(":c{$i}", $valores[$c]);
            $i++;
        }
        
        $this->db->bind(':promedio', $promedio);
        $this->db->bind(':obs',      $obs ?: null);

        return $this->db->execute();
    }

    /**
     * Actualizar una evaluación existente
     */
    public function actualizar(int $id, string $fecha, float $promedio, string $obs, array $valores, array $criterios): bool {
        $this->db->query("
            UPDATE evaluaciones 
            SET fecha_evaluacion = :fecha,
                criterio_iniciativa = :c1, criterio_interes = :c2, criterio_conocimiento = :c3,
                criterio_analisis = :c4, criterio_comunicacion = :c5, criterio_aprendizaje = :c6,
                criterio_companerismo = :c7, criterio_cooperacion = :c8, criterio_puntualidad = :c9,
                criterio_presentacion = :c10, criterio_desarrollo = :c11, criterio_analisis_res = :c12,
                criterio_conclusiones = :c13, criterio_recomendacion = :c14,
                promedio_final = :promedio, observaciones = :obs
            WHERE id = :id
        ");

        $this->db->bind(':id', $id);
        $this->db->bind(':fecha', $fecha);
        
        $i = 1;
        foreach ($criterios as $c) {
            $this->db->bind(":c{$i}", $valores[$c]);
            $i++;
        }
        
        $this->db->bind(':promedio', $promedio);
        $this->db->bind(':obs',      $obs ?: null);

        return $this->db->execute();
    }

    /**
     * Verificar si un pasante ya tiene evaluación registrada
     */
    public function getByPasante(int $pasanteId) {
        $this->db->query("
            SELECT id, promedio_final, fecha_evaluacion
            FROM evaluaciones
            WHERE pasante_id = :pid
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        return $this->db->single();
    }

    /**
     * Eliminar una evaluación por ID
     */
    public function eliminar(int $id): bool {
        $this->db->query("DELETE FROM evaluaciones WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Obtener una evaluación por ID con todos sus criterios y nombres
     */
    public function getById(int $id) {
        $this->db->query("
            SELECT
                e.*,
                CONCAT(dp.nombres, ' ', dp.apellidos)  AS pasante_nombre,
                u.cedula                               AS pasante_cedula,
                CONCAT(tp.nombres, ' ', tp.apellidos)  AS tutor_nombre,
                COALESCE(d.nombre, '')                  AS departamento,
                COALESCE(inst.nombre, dpa.institucion_procedencia, '') AS institucion_nombre,
                DATE_FORMAT(e.fecha_evaluacion, '%d/%m/%Y') AS fecha_formateada,
                e.promedio_final AS promedio
            FROM evaluaciones e
            LEFT JOIN datos_personales dp  ON dp.usuario_id = e.pasante_id
            LEFT JOIN usuarios         u   ON u.id = e.pasante_id
            LEFT JOIN datos_pasante    dpa ON dpa.usuario_id = e.pasante_id
            LEFT JOIN departamentos    d   ON d.id = dpa.departamento_asignado_id
            LEFT JOIN instituciones    inst ON inst.id = dpa.institucion_id
            LEFT JOIN datos_personales tp  ON tp.usuario_id = dpa.tutor_id
            WHERE e.id = :id
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
}
