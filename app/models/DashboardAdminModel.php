<?php
/**
 * DashboardAdminModel - Lógica de datos para el Dashboard del Administrador
 * 
 * Contiene todas las consultas para extraer las métricas y KPIs del Dashboard,
 * aislando completamente el SQL del Controlador.
 */
class DashboardAdminModel
{
    private $db;

    public function __construct()
    {
        // SGP-FIX-v2 [6/2.1] aplicado — Singleton
        $this->db = Database::getInstance();
    }

    /**
     * Obtener métricas principales en una sola consulta (KPIs)
     * Optimizada para reducir latencia y carga en BD.
     */
    public function getKpiTotales(): object
    {
        $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM datos_pasante WHERE estado_pasantia = 'Activo') AS totalActivos,
                (SELECT COUNT(*) FROM usuarios u 
                 LEFT JOIN datos_pasante dp ON u.id = dp.usuario_id 
                 WHERE u.rol_id = 3 AND (dp.estado_pasantia = 'Sin Asignar' OR dp.estado_pasantia IS NULL OR dp.estado_pasantia = '')) AS pendientesAsignar,
                (SELECT COUNT(*) FROM usuarios WHERE rol_id = 2 AND estado = 'activo') AS totalTutores,
                (SELECT COUNT(*) FROM asistencias a
                 JOIN datos_pasante dp ON dp.usuario_id = a.pasante_id
                 WHERE a.fecha = CURDATE() AND a.estado = 'Presente'
                   AND COALESCE(dp.tipo_pasantia,'Regular') = 'Regular') AS asistenciasHoy,
                (SELECT COUNT(*) FROM asistencias a
                 JOIN datos_pasante dp ON dp.usuario_id = a.pasante_id
                 WHERE a.fecha = CURDATE() AND a.estado = 'Ausente'
                   AND COALESCE(dp.tipo_pasantia,'Regular') = 'Regular') AS faltasHoy,
                (SELECT COUNT(DISTINCT institucion_procedencia) FROM datos_pasante WHERE institucion_procedencia IS NOT NULL AND institucion_procedencia != '') AS totalInstituciones
        ");
        return $this->db->single();
    }

    public function getTotalActivos(): int
    {
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM datos_pasante
            WHERE estado_pasantia = 'Activo'
        ");
        return (int)($this->db->single()->total ?? 0);
    }

    public function getPendientesAsignar(): int
    {
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM usuarios u
            LEFT JOIN datos_pasante dp ON u.id = dp.usuario_id
            WHERE u.rol_id = 3 AND (dp.estado_pasantia = 'Sin Asignar' OR dp.estado_pasantia IS NULL OR dp.estado_pasantia = '')
        ");
        return (int)($this->db->single()->total ?? 0);
    }

    public function getTotalTutores(): int
    {
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM usuarios
            WHERE rol_id = 2 AND estado = 'activo'
        ");
        return (int)($this->db->single()->total ?? 0);
    }

    public function getAsistenciasHoy(): int
    {
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM asistencias
            WHERE fecha = CURDATE() AND estado = 'Presente'
        ");
        return (int)($this->db->single()->total ?? 0);
    }

    public function getFaltasHoy(): int
    {
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM asistencias
            WHERE fecha = CURDATE() AND estado = 'Ausente'
        ");
        return (int)($this->db->single()->total ?? 0);
    }

    public function getTotalInstituciones(): int
    {
        $this->db->query("
            SELECT COUNT(DISTINCT institucion_procedencia) AS total
            FROM datos_pasante
            WHERE institucion_procedencia IS NOT NULL AND institucion_procedencia != ''
        ");
        return (int)($this->db->single()->total ?? 0);
    }

    public function getActividadReciente(int $limit = 5): array
    {
        $periodoActivo = PeriodoService::getActivo();
        if (!$periodoActivo) {
            return [];
        }

        $this->db->query("
            SELECT
                a.id,
                a.fecha,
                a.hora_registro,
                a.estado,
                a.metodo,
                dp.nombres,
                dp.apellidos,
                u.cedula,
                u.id AS usuario_id
            FROM asistencias a
            INNER JOIN usuarios u  ON u.id  = a.pasante_id
            LEFT  JOIN datos_personales dp ON dp.usuario_id = a.pasante_id
            INNER JOIN datos_pasante dpa ON dpa.usuario_id = a.pasante_id
                       AND dpa.estado_pasantia = 'Activo'
                       AND dpa.periodo_id = :pid
            WHERE a.fecha BETWEEN :fi AND LEAST(:ff, CURDATE())
            ORDER BY a.fecha DESC, a.hora_registro DESC
            LIMIT :limit
        ");
        $this->db->bind(':pid',   $periodoActivo->id);
        $this->db->bind(':fi',    $periodoActivo->fecha_inicio);
        $this->db->bind(':ff',    $periodoActivo->fecha_fin);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet() ?: [];
    }

    public function getEstadosPasantes(): array
    {
        $this->db->query("
            SELECT COALESCE(NULLIF(estado_pasantia, ''), 'Sin Asignar') AS estado_pasantia, COUNT(*) AS cantidad
            FROM datos_pasante
            GROUP BY COALESCE(NULLIF(estado_pasantia, ''), 'Sin Asignar')
        ");
        return $this->db->resultSet();
    }

    public function getRegistrosMensuales(): array
    {
        $this->db->query("
            SELECT MONTH(u.created_at) AS mes, COUNT(*) AS cantidad
            FROM usuarios u
            WHERE u.rol_id = 3 AND YEAR(u.created_at) = YEAR(CURDATE())
            GROUP BY MONTH(u.created_at)
            ORDER BY mes ASC
        ");
        
        $registros = $this->db->resultSet();
        $datosMensuales = array_fill(1, 12, 0);
        
        foreach ($registros as $r) {
            $datosMensuales[(int)$r->mes] = (int)$r->cantidad;
        }
        
        return array_values($datosMensuales); // Retorna array 0-indexado
    }

    // ==========================================
    // MÉTRICAS PARA GRÁFICAS (Data Binding Real)
    // ==========================================

    public function getAsistenciaDiaria(): array
    {
        // Lunes y viernes de la semana ACTUAL (ISO)
        $lunes   = date('Y-m-d', strtotime('monday this week'));
        $viernes = date('Y-m-d', strtotime('friday this week'));

        // ── Conteos por día ──────────────────────────────────────────
        $this->db->query("
            SELECT
                fecha,
                SUM(estado = 'Presente') AS p,
                SUM(estado IN ('Ausente','Falta')) AS f,
                SUM(estado = 'Justificado') AS j
            FROM asistencias
            WHERE fecha BETWEEN :lun AND :vie
            GROUP BY fecha
            ORDER BY fecha ASC
        ");
        $this->db->bind(':lun', $lunes);
        $this->db->bind(':vie', $viernes);
        $resultados = $this->db->resultSet();

        // ── Motivos únicos de justificación por día ──────────────────
        $this->db->query("
            SELECT
                a.fecha,
                COALESCE(
                    df.nombre,
                    NULLIF(TRIM(a.motivo_justificacion), ''),
                    'Justificado'
                ) AS motivo
            FROM asistencias a
            LEFT JOIN dias_feriados df ON df.fecha = a.fecha
            WHERE a.fecha BETWEEN :lun AND :vie
              AND a.estado = 'Justificado'
            ORDER BY a.fecha ASC
        ");
        $this->db->bind(':lun', $lunes);
        $this->db->bind(':vie', $viernes);
        $motivosRaw = $this->db->resultSet();

        $motivosPorFecha = [];
        foreach ($motivosRaw as $m) {
            $v = trim($m->motivo);
            if (!isset($motivosPorFecha[$m->fecha])) $motivosPorFecha[$m->fecha] = [];
            if (!in_array($v, $motivosPorFecha[$m->fecha])) $motivosPorFecha[$m->fecha][] = $v;
        }

        // Indexar resultados por fecha
        $porFecha = [];
        foreach ($resultados as $r) { $porFecha[$r->fecha] = $r; }

        // ── Generar slots Lun–Vie en orden fijo ─────────────────────
        $nombresDia = ['1'=>'Lun','2'=>'Mar','3'=>'Mié','4'=>'Jue','5'=>'Vie'];
        $data = ['cat'=>[],'p'=>[],'f'=>[],'j'=>[],'motivos'=>[]];

        for ($i = 0; $i < 5; $i++) {
            $fecha = date('Y-m-d', strtotime($lunes . " +{$i} days"));
            $dow   = date('N', strtotime($fecha)); // ISO 1=Lun…5=Vie
            $r     = $porFecha[$fecha] ?? null;

            $data['cat'][]     = $nombresDia[$dow] ?? 'Día';
            $data['p'][]       = $r ? (int)$r->p : 0;
            $data['f'][]       = $r ? (int)$r->f : 0;
            $data['j'][]       = $r ? (int)$r->j : 0;
            $data['motivos'][] = $motivosPorFecha[$fecha] ?? [];
        }

        return $data;
    }

    public function getAsistenciaSemanal(): array
    {
        // Últimas 4 semanas ISO — solo pasantes regulares
        $this->db->query("
            SELECT
                WEEK(a.fecha, 1) AS semana_num,
                DATE_SUB(MIN(a.fecha), INTERVAL WEEKDAY(MIN(a.fecha)) DAY) AS lunes_semana,
                SUM(a.estado = 'Presente') AS p,
                SUM(a.estado IN ('Ausente','Falta')) AS f,
                SUM(a.estado = 'Justificado') AS j
            FROM asistencias a
            JOIN datos_pasante dp ON dp.usuario_id = a.pasante_id
            WHERE a.fecha >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
              AND COALESCE(dp.tipo_pasantia,'Regular') = 'Regular'
            GROUP BY WEEK(a.fecha, 1)
            ORDER BY semana_num ASC
            LIMIT 4
        ");
        $results = $this->db->resultSet();
        $data = ['cat'=>[],'p'=>[],'f'=>[],'j'=>[],'motivos'=>[]];

        foreach ($results as $r) {
            $data['cat'][]     = date('d/m', strtotime($r->lunes_semana));
            $data['p'][]       = (int)$r->p;
            $data['f'][]       = (int)$r->f;
            $data['j'][]       = (int)$r->j;
            $data['motivos'][] = [];
        }

        if (empty($data['cat'])) {
            $data = ['cat'=>['Sem 1','Sem 2','Sem 3','Sem 4'],'p'=>[0,0,0,0],'f'=>[0,0,0,0],'j'=>[0,0,0,0],'motivos'=>[[],[],[],[]]];
        }
        return $data;
    }

    public function getAsistenciaMensual(): array
    {
        // Meses del año actual — solo pasantes regulares
        $this->db->query("
            SELECT
                MONTH(a.fecha) AS mes_num,
                SUM(a.estado = 'Presente') AS p,
                SUM(a.estado IN ('Ausente','Falta')) AS f,
                SUM(a.estado = 'Justificado') AS j
            FROM asistencias a
            JOIN datos_pasante dp ON dp.usuario_id = a.pasante_id
            WHERE YEAR(a.fecha) = YEAR(CURDATE())
              AND COALESCE(dp.tipo_pasantia,'Regular') = 'Regular'
            GROUP BY MONTH(a.fecha)
            ORDER BY mes_num ASC
            LIMIT 6
        ");
        $results = $this->db->resultSet();
        $mapaMeses = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic'];
        $data = ['cat'=>[],'p'=>[],'f'=>[],'j'=>[],'motivos'=>[]];

        foreach ($results as $r) {
            $data['cat'][]     = $mapaMeses[$r->mes_num] ?? '?';
            $data['p'][]       = (int)$r->p;
            $data['f'][]       = (int)$r->f;
            $data['j'][]       = (int)$r->j;
            $data['motivos'][] = [];
        }

        if (empty($data['cat'])) {
            $data = ['cat'=>['Ene','Feb','Mar','Abr','May'],'p'=>[0,0,0,0,0],'f'=>[0,0,0,0,0],'j'=>[0,0,0,0,0],'motivos'=>[[],[],[],[],[]]];
        }
        return $data;
    }

    public function getAsistenciaPorDepartamento(): array
    {
        // LEFT JOIN desde departamentos para incluir todos (activos),
        // aunque no tengan pasantes asignados (aparecerán con 0%)
        $this->db->query("
            SELECT
                d.nombre AS departamento,
                COUNT(a.id)                      AS total_registros,
                COALESCE(SUM(a.estado = 'Presente'), 0) AS total_presentes,
                COUNT(DISTINCT dpa.usuario_id)   AS total_pasantes
            FROM departamentos d
            LEFT JOIN datos_pasante dpa ON d.id = dpa.departamento_asignado_id AND dpa.estado_pasantia = 'Activo'
            LEFT JOIN asistencias a     ON a.pasante_id = dpa.usuario_id
            WHERE d.activo = 1
            GROUP BY d.id, d.nombre
            ORDER BY total_presentes DESC, d.nombre ASC
            LIMIT 6
        ");

        $results = $this->db->resultSet();
        $data = ['labels' => [], 'series' => [], 'pasantes' => []];

        foreach ($results as $r) {
            $data['labels'][]   = htmlspecialchars($r->departamento);
            $porcentaje = ($r->total_registros > 0)
                ? (int) round(($r->total_presentes / $r->total_registros) * 100)
                : 0;
            $data['series'][]   = $porcentaje;
            $data['pasantes'][] = (int)$r->total_pasantes;
        }

        return $data;
    }

    public function getAsistenciaPorInstitucion(): array
    {
        $data = ['labels' => [], 'series' => [], 'pasantes' => [], 'tipos' => []];
        try {
            $this->db->query("
                SELECT
                    COALESCE(i.nombre, dpa.institucion_procedencia, 'Sin Institución') AS institucion,
                    COALESCE(i.tipo, 'Institución')                                   AS tipo,
                    COUNT(a.id)                                                      AS total_registros,
                    COALESCE(SUM(a.estado = 'Presente'), 0)                          AS total_presentes,
                    COUNT(DISTINCT dpa.usuario_id)                                   AS total_pasantes
                FROM instituciones i
                INNER JOIN datos_pasante dpa ON (dpa.institucion_id = i.id OR dpa.institucion_procedencia = i.nombre) AND dpa.estado_pasantia = 'Activo'
                LEFT JOIN asistencias a      ON a.pasante_id = dpa.usuario_id
                WHERE i.activo = 1
                GROUP BY i.id, i.nombre, i.tipo
                HAVING total_pasantes > 0
                ORDER BY total_presentes DESC, total_pasantes DESC, i.nombre ASC
                LIMIT 6
            ");

            $results = $this->db->resultSet();
            if ($results) {
                foreach ($results as $r) {
                    $data['labels'][]   = htmlspecialchars($r->institucion);
                    $porcentaje = ($r->total_registros > 0)
                        ? (int) round(($r->total_presentes / $r->total_registros) * 100)
                        : 0;
                    $data['series'][]   = $porcentaje;
                    $data['pasantes'][] = (int)$r->total_pasantes;
                    $data['tipos'][]    = $r->tipo;
                }
            }
        } catch (\Throwable $e) {
            error_log('[SGP-DASHBOARD-INSTITUCION] ' . $e->getMessage());
        }

        return $data;
    }

    public function getAlertasPendientes(int $limit = 5): array
    {
        // Pasantes sin asignar o pasantías a 7 días o menos de vencer
        $this->db->query("
            SELECT 
                u.id AS usuario_id,
                u.cedula,
                dp.nombres,
                dp.apellidos,
                COALESCE(NULLIF(dpa.estado_pasantia, ''), 'Sin Asignar') AS estado,
                dpa.fecha_fin_estimada,
                COALESCE(inst.nombre, NULLIF(dpa.institucion_procedencia, ''), 'No registrada') AS institucion_procedencia
            FROM usuarios u
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN datos_pasante dpa ON u.id = dpa.usuario_id
            LEFT JOIN instituciones inst ON (dpa.institucion_id = inst.id OR dpa.institucion_procedencia = inst.nombre)
            WHERE u.rol_id = 3 
              AND u.estado = 'activo'
              AND (
                  dpa.estado_pasantia = 'Sin Asignar' 
                  OR dpa.estado_pasantia IS NULL
                  OR dpa.estado_pasantia = ''
                  OR (dpa.estado_pasantia = 'Activo' AND dpa.fecha_fin_estimada <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
              )
            ORDER BY 
                CASE WHEN dpa.estado_pasantia = 'Sin Asignar' OR dpa.estado_pasantia IS NULL OR dpa.estado_pasantia = '' THEN 1 ELSE 2 END ASC,
                dpa.fecha_fin_estimada ASC,
                u.created_at DESC
            LIMIT :limit
        ");
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getDepartamentosParaAsignacion(): array
    {
        $this->db->query("SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
        return $this->db->resultSet();
    }

    public function getTutoresParaAsignacion(): array
    {
        $this->db->query("
            SELECT u.id, dp.nombres, dp.apellidos, d.nombre AS departamento_nombre
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN departamentos    d  ON d.id = u.departamento_id
            WHERE u.rol_id IN (1, 2) AND u.estado = 'activo'
            ORDER BY u.rol_id ASC, IFNULL(dp.apellidos, u.correo) ASC
        ");
        return $this->db->resultSet();
    }

    /**
     * Períodos activos cuya fecha_fin ya pasó (vencidos sin cerrar).
     * Son candidatos inmediatos de cierre masivo.
     */
    public function getAlertasPeriodosVencidos(): array
    {
        $this->db->query("
            SELECT pa.id, pa.nombre, pa.fecha_fin, pa.estado,
                   DATEDIFF(CURDATE(), MAX(COALESCE(dp.fecha_fin_estimada, pa.fecha_fin))) AS dias_vencido,
                   COUNT(dp.usuario_id) AS total_pasantes,
                   SUM(CASE WHEN dp.estado_pasantia='Activo' THEN 1 ELSE 0 END) AS activos_pendientes
            FROM periodos_academicos pa
            LEFT JOIN datos_pasante dp ON dp.periodo_id = pa.id
            WHERE pa.estado = 'Activo'
            GROUP BY pa.id, pa.nombre, pa.fecha_fin, pa.estado
            HAVING MAX(COALESCE(dp.fecha_fin_estimada, pa.fecha_fin)) < CURDATE()
            ORDER BY MAX(COALESCE(dp.fecha_fin_estimada, pa.fecha_fin)) ASC
        ");
        return $this->db->resultSet() ?: [];
    }

    /**
     * Períodos cerrados en los últimos N días (para mostrar botón "Reabrir").
     */
    public function getPeriodosRecientementeCerrados(int $dias = 30): array
    {
        $this->db->query("
            SELECT pa.id, pa.nombre, pa.fecha_fin,
                   COUNT(dp.usuario_id) AS total_pasantes,
                   SUM(CASE WHEN dp.estado_pasantia='Finalizado' THEN 1 ELSE 0 END) AS finalizados,
                   SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM evaluaciones e WHERE e.pasante_id=dp.usuario_id) THEN 1 ELSE 0 END) AS sin_evaluacion
            FROM periodos_academicos pa
            LEFT JOIN datos_pasante dp ON dp.periodo_id = pa.id
            WHERE pa.estado = 'Cerrado'
              AND pa.fecha_fin >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
            GROUP BY pa.id, pa.nombre, pa.fecha_fin
            ORDER BY pa.fecha_fin DESC
        ");
        $this->db->bind(':dias', $dias, PDO::PARAM_INT);
        return $this->db->resultSet() ?: [];
    }

    /**
     * Períodos académicos activos que vencen en los próximos N días.
     */
    public function getAlertasPeriodosProximos(int $dias = 7): array
    {
        $this->db->query("
            SELECT pa.id, pa.nombre, pa.fecha_fin, pa.estado,
                   MAX(COALESCE(dp.fecha_fin_estimada, pa.fecha_fin)) AS fecha_fin_real,
                   DATEDIFF(MAX(COALESCE(dp.fecha_fin_estimada, pa.fecha_fin)), CURDATE()) AS dias_restantes,
                   COUNT(dp.usuario_id) AS total_pasantes,
                   SUM(CASE WHEN dp.estado_pasantia='Activo' THEN 1 ELSE 0 END) AS activos
            FROM periodos_academicos pa
            LEFT JOIN datos_pasante dp ON dp.periodo_id = pa.id
            WHERE pa.estado = 'Activo'
            GROUP BY pa.id, pa.nombre, pa.fecha_fin, pa.estado
            HAVING MAX(COALESCE(dp.fecha_fin_estimada, pa.fecha_fin)) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
            ORDER BY MAX(COALESCE(dp.fecha_fin_estimada, pa.fecha_fin)) ASC
        ");
        $this->db->bind(':dias', $dias, PDO::PARAM_INT);
        return $this->db->resultSet() ?: [];
    }

    public function getCortosResumen(): object
    {
        $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(dpa.estado_pasantia = 'Activo')     AS activos,
                SUM(dpa.estado_pasantia = 'Finalizado') AS finalizados,
                ROUND(
                    AVG(LEAST(100,
                        COALESCE((SELECT SUM(COALESCE(NULLIF(a.horas_calculadas,0),8))
                                  FROM asistencias a
                                  WHERE a.pasante_id = dpa.usuario_id
                                    AND a.estado IN ('Presente','Justificado','Retardo')
                                    AND a.fecha <= CURDATE()), 0)
                        / NULLIF(dpa.horas_meta, 0) * 100
                    )), 1) AS pct_promedio
            FROM datos_pasante dpa
            WHERE dpa.tipo_pasantia = 'Corta'
        ");
        return $this->db->single() ?? (object)['total'=>0,'activos'=>0,'finalizados'=>0,'pct_promedio'=>0];
    }

    public function getPeriodosAcademicos(): array
    {
        $this->db->query("
            SELECT id, nombre, estado, fecha_inicio, fecha_fin, tipo 
            FROM periodos_academicos 
            ORDER BY FIELD(estado, 'Activo', 'Planificado', 'Cerrado'), fecha_inicio DESC
        ");
        return $this->db->resultSet();
    }

    /**
     * Pasantes regulares con al menos 1 ausente en el mes actual,
     * con el conteo de ausencias y el porcentaje de asistencia del mes.
     */
    public function getAusentesMes(): array
    {
        $this->db->query("
            SELECT
                u.id,
                dp.nombres, dp.apellidos, u.cedula,
                d.nombre AS departamento,
                SUM(a.estado = 'Ausente')    AS total_ausentes,
                SUM(a.estado = 'Presente')   AS total_presentes,
                SUM(a.estado = 'Justificado') AS total_justificados,
                COUNT(a.id) AS total_dias,
                ROUND(SUM(a.estado = 'Presente') / NULLIF(COUNT(a.id),0) * 100, 1) AS pct_asistencia
            FROM asistencias a
            JOIN usuarios u ON u.id = a.pasante_id
            JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            WHERE YEAR(a.fecha) = YEAR(CURDATE())
              AND MONTH(a.fecha) = MONTH(CURDATE())
              AND COALESCE(dpa.tipo_pasantia,'Regular') = 'Regular'
              AND dpa.estado_pasantia = 'Activo'
            GROUP BY u.id, dp.nombres, dp.apellidos, u.cedula, d.nombre
            HAVING total_ausentes > 0
            ORDER BY total_ausentes DESC, dp.apellidos ASC
        ");
        return $this->db->resultSet() ?: [];
    }
}
