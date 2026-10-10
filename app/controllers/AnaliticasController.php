<?php
/**
 * AnaliticasController — Métricas y Estadísticas del Sistema
 *
 * Provee datos reales desde la DB para el módulo de Analíticas.
 * Acceso: Administrador (rol 1) — vista global
 *         Tutor      (rol 2) — vista filtrada a sus pasantes
 */

declare(strict_types=1);

class AnaliticasController extends Controller
{
    private Database $db;
    private int      $rolId;
    private ?int     $tutorId;

    public function __construct()
    {
        Session::start();
        CacheControl::noCache();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();

        if (!RoleMiddleware::hasAnyRole([0, 1, 2])) {
            RoleMiddleware::redirectToRoleDashboard(Session::get('role_id'));
        }

        $this->db      = Database::getInstance();
        $this->rolId   = (int)(Session::get('role_id') ?? 0);
        $this->tutorId = $this->rolId === 2 ? (int)(Session::get('user_id') ?? 0) : null;
    }

    public function index(): void
    {
        $isAdmin    = in_array($this->rolId, [0, 1]);
        $tutorId    = $this->tutorId;

        // Para tutores: obtener el departamento_id y filtrar por él
        $deptId = null;
        if (!$isAdmin && $tutorId) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $tutorId);
            $deptId = (int)($this->db->single()->departamento_id ?? 0);
        }

        // Filtros SQL dinámicos según rol (por departamento, no por tutor_id)
        $tutorJoin  = (!$isAdmin && $deptId)
            ? "INNER JOIN datos_pasante _dp2 ON _dp2.usuario_id = a.pasante_id AND _dp2.departamento_asignado_id = {$deptId}"
            : '';
        $tutorWhere = (!$isAdmin && $deptId)
            ? "AND dpa.departamento_asignado_id = {$deptId}"
            : '';

        $periodoActivo    = PeriodoService::getActivo();
        $hayPeriodoActivo = ($periodoActivo !== null);
        $periodoActivoWhere = $hayPeriodoActivo ? "AND dpa.periodo_id = {$periodoActivo->id}" : "AND 1=0";


        // ────────────────────────────────────────────────────────────────
        // KPIs
        // ────────────────────────────────────────────────────────────────

        // KPI 1: Tasa de asistencia últimos 30 días
        $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN a.estado = 'Presente'    THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN a.estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados,
                SUM(CASE WHEN a.estado = 'Ausente'     THEN 1 ELSE 0 END) AS ausentes
            FROM asistencias a
            {$tutorJoin}
            WHERE a.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $asist            = $this->db->single();
        $totalAsistencias = (int)($asist->total ?? 0);
        $kpiTasa          = $totalAsistencias > 0
            ? round(($asist->presentes / $totalAsistencias) * 100, 1)
            : 0;

        // KPI 2: Pasantes activos en el periodo
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM datos_pasante dpa
            WHERE dpa.estado_pasantia = 'Activo' {$tutorWhere} {$periodoActivoWhere}
        ");
        $kpiActivos = (int)($this->db->single()->total ?? 0);

        // KPI 3: Horas acumuladas (usando horas_calculadas reales, default 8h/día)
        $this->db->query("
            SELECT COALESCE(SUM(horas_validas), 0) AS total
            FROM (
                SELECT a.pasante_id,
                       COALESCE(SUM(COALESCE(NULLIF(a.horas_calculadas,0),8)),0) AS horas_validas
                FROM asistencias a
                INNER JOIN datos_pasante dpa ON dpa.usuario_id = a.pasante_id
                WHERE a.estado IN ('Presente', 'Justificado', 'Ausente')
                  AND dpa.estado_pasantia = 'Activo'
                  {$tutorWhere}
                  {$periodoActivoWhere}
                GROUP BY a.pasante_id
            ) AS sub
        ");
        $kpiHoras = (int)($this->db->single()->total ?? 0);

        // KPI 4: Progreso promedio (horas reales / horas_meta individual)
        $this->db->query("
            SELECT ROUND(AVG(LEAST(100, (COALESCE(prog.horas_validas, 0) / NULLIF(dpa.horas_meta, 0)) * 100)), 1) AS promedio
            FROM datos_pasante dpa
            LEFT JOIN (
                SELECT pasante_id,
                       COALESCE(SUM(COALESCE(NULLIF(horas_calculadas,0),8)),0) AS horas_validas
                FROM asistencias
                WHERE estado IN ('Presente', 'Justificado', 'Ausente')
                GROUP BY pasante_id
            ) AS prog ON prog.pasante_id = dpa.usuario_id
            WHERE dpa.estado_pasantia = 'Activo'
              AND dpa.horas_meta > 0
              {$tutorWhere}
              {$periodoActivoWhere}
        ");
        $kpiProgreso = (float)($this->db->single()->promedio ?? 0);

        // KPI 5: Promedio evaluaciones del periodo
        $this->db->query("
            SELECT ROUND(AVG(e.promedio_final), 2) AS promedio
            FROM evaluaciones e
            INNER JOIN datos_pasante dpa ON dpa.usuario_id = e.pasante_id
            WHERE dpa.estado_pasantia = 'Activo' {$tutorWhere} {$periodoActivoWhere}
        ");
        $kpiEval = (float)($this->db->single()->promedio ?? 0);

        // ────────────────────────────────────────────────────────────────
        // SERIES TEMPORALES
        // ────────────────────────────────────────────────────────────────

        // Asistencias por mes (año actual)
        $this->db->query("
            SELECT
                MONTH(a.fecha) AS mes,
                SUM(CASE WHEN a.estado = 'Presente'    THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN a.estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados,
                SUM(CASE WHEN a.estado = 'Ausente'     THEN 1 ELSE 0 END) AS ausentes
            FROM asistencias a
            {$tutorJoin}
            WHERE YEAR(a.fecha) = YEAR(CURDATE())
            GROUP BY MONTH(a.fecha)
            ORDER BY mes
        ");
        $asistencias_mes    = $this->db->resultSet();
        $seriesPresentes    = array_fill(0, 12, 0);
        $seriesJustificados = array_fill(0, 12, 0);
        $seriesAusentes     = array_fill(0, 12, 0);
        foreach ($asistencias_mes as $row) {
            $idx = (int)$row->mes - 1;
            $seriesPresentes[$idx]    = (int)$row->presentes;
            $seriesJustificados[$idx] = (int)$row->justificados;
            $seriesAusentes[$idx]     = (int)$row->ausentes;
        }

        // Tendencia de horas por mes (horas_calculadas reales)
        $this->db->query("
            SELECT
                MONTH(a.fecha) AS mes,
                ROUND(SUM(COALESCE(a.horas_calculadas, 8)), 1) AS horas_mes
            FROM asistencias a
            {$tutorJoin}
            WHERE YEAR(a.fecha) = YEAR(CURDATE())
              AND a.estado IN ('Presente', 'Justificado', 'Ausente')
            GROUP BY MONTH(a.fecha)
            ORDER BY mes
        ");
        $tendenciaHoras = $this->db->resultSet();
        $seriesHorasMes = array_fill(0, 12, 0);
        foreach ($tendenciaHoras as $row) {
            $seriesHorasMes[(int)$row->mes - 1] = (float)$row->horas_mes;
        }

        // Tendencia de horas por semana (año actual, para toggle semanal)
        $this->db->query("
            SELECT
                WEEK(a.fecha, 1) AS semana,
                MIN(a.fecha)     AS inicio_semana,
                ROUND(SUM(COALESCE(a.horas_calculadas, 8)), 1) AS horas_semana
            FROM asistencias a
            {$tutorJoin}
            WHERE YEAR(a.fecha) = YEAR(CURDATE())
              AND a.estado IN ('Presente', 'Justificado', 'Ausente')
            GROUP BY WEEK(a.fecha, 1)
            ORDER BY semana
        ");
        $tendenciaHorasSemana = $this->db->resultSet();
        $seriesHorasSemana    = [];
        foreach ($tendenciaHorasSemana as $row) {
            $seriesHorasSemana[] = [
                'semana' => 'S' . (int)$row->semana,
                'horas'  => (float)$row->horas_semana,
            ];
        }

        // Asistencias diarias por cada mes (para filtro click en botón de mes)
        $dailyByMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $dailyByMonth[$m] = ['presentes' => [], 'justificados' => [], 'ausentes' => [], 'dias' => []];
        }
        $this->db->query("
            SELECT
                MONTH(a.fecha)  AS mes,
                DAY(a.fecha)    AS dia,
                SUM(CASE WHEN a.estado = 'Presente'    THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN a.estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados,
                SUM(CASE WHEN a.estado = 'Ausente'     THEN 1 ELSE 0 END) AS ausentes
            FROM asistencias a
            {$tutorJoin}
            WHERE YEAR(a.fecha) = YEAR(CURDATE())
            GROUP BY MONTH(a.fecha), DAY(a.fecha)
            ORDER BY mes, dia
        ");
        foreach ($this->db->resultSet() as $row) {
            $m = (int)$row->mes;
            $dailyByMonth[$m]['dias'][]         = (int)$row->dia;
            $dailyByMonth[$m]['presentes'][]    = (int)$row->presentes;
            $dailyByMonth[$m]['justificados'][] = (int)$row->justificados;
            $dailyByMonth[$m]['ausentes'][]     = (int)$row->ausentes;
        }

        // Calendario: asistencias por día para TODOS los meses del año actual
        $this->db->query("
            SELECT
                MONTH(a.fecha)      AS mes,
                DAY(a.fecha)        AS dia,
                DAYOFWEEK(a.fecha)  AS dow,
                COUNT(*)            AS total
            FROM asistencias a
            {$tutorJoin}
            WHERE YEAR(a.fecha) = YEAR(CURDATE())
            GROUP BY MONTH(a.fecha), DAY(a.fecha), DAYOFWEEK(a.fecha)
            ORDER BY mes, dia
        ");
        $allCalData = [];
        foreach ($this->db->resultSet() as $h) {
            $allCalData[(int)$h->mes][(int)$h->dia] = [
                'total' => (int)$h->total,
                'dow'   => (int)$h->dow,
            ];
        }
        // Mantener compatibilidad: mes actual sigue en $heatmapCalData
        $heatmapCalData = $allCalData[(int)date('n')] ?? [];

        // [SGP-FEATURE-CAL-SIN-PERIODO] Detectar meses del año actual sin
        // ningún período de pasantía activo (las pasantías duran ~9 meses
        // repartidos en dos años calendario, así que quedan huecos entre
        // cohortes). Un calendario en blanco en esos meses no es un error:
        // simplemente no había pasantes cursando. Se calcula aquí para
        // mostrar un aviso en vez de dejarlo parecer un fallo del sistema.
        $this->db->query("
            SELECT
                COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS ini,
                COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fin
            FROM datos_pasante dpa
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE 1=1 {$tutorWhere}
        ");
        $lapsos = $this->db->resultSet();

        $anioActualCal   = (int)date('Y');
        $mesesSinPeriodo = [];
        for ($m = 1; $m <= 12; $m++) {
            $inicioMes = sprintf('%04d-%02d-01', $anioActualCal, $m);
            $finMes    = date('Y-m-t', strtotime($inicioMes));
            $activo    = false;
            foreach ($lapsos as $l) {
                if (empty($l->ini) || empty($l->fin)) continue;
                if ($l->ini <= $finMes && $l->fin >= $inicioMes) { $activo = true; break; }
            }
            if (!$activo) $mesesSinPeriodo[] = $m;
        }

        // ────────────────────────────────────────────────────────────────
        // DISTRIBUCIÓN
        // ────────────────────────────────────────────────────────────────

        // Pasantes por departamento (LEFT JOIN para incluir todos los activos, aunque tengan 0)
        $this->db->query("
            SELECT d.nombre AS departamento, COUNT(dpa.usuario_id) AS total
            FROM departamentos d
            LEFT JOIN datos_pasante dpa
                ON d.id = dpa.departamento_asignado_id
               AND dpa.estado_pasantia IN ('Activo', 'Pendiente') {$tutorWhere}
            WHERE d.activo = 1
            GROUP BY d.id
            ORDER BY total DESC, d.nombre ASC
        ");
        $porDepartamento = $this->db->resultSet();

        // NUEVO: Pasantes por institución con tasa de asistencia
        // institucion_procedencia almacena el ID numérico → JOIN con instituciones
        $this->db->query("
            SELECT
                COALESCE(i.nombre, dpa.institucion_procedencia) AS institucion,
                COUNT(DISTINCT dpa.usuario_id) AS total_pasantes,
                SUM(CASE WHEN dpa.estado_pasantia = 'Activo' THEN 1 ELSE 0 END) AS activos,
                ROUND(
                    AVG(CASE WHEN a.estado IN ('Presente','Justificado') THEN 1
                             WHEN a.estado = 'Ausente' THEN 0
                             ELSE NULL END) * 100
                , 1) AS tasa_asistencia
            FROM datos_pasante dpa
            LEFT JOIN instituciones i ON i.id = dpa.institucion_id
            LEFT JOIN asistencias a ON a.pasante_id = dpa.usuario_id
                AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            WHERE dpa.estado_pasantia IN ('Activo', 'Pendiente', 'Finalizado')
              {$tutorWhere}
            GROUP BY COALESCE(dpa.institucion_id, dpa.institucion_procedencia)
            ORDER BY total_pasantes DESC
        ");
        $porInstitucion = $this->db->resultSet();

        // Heatmap: asistencias por día del mes actual
        $this->db->query("
            SELECT DAY(a.fecha) AS dia, COUNT(*) AS total
            FROM asistencias a
            {$tutorJoin}
            WHERE YEAR(a.fecha) = YEAR(CURDATE()) AND MONTH(a.fecha) = MONTH(CURDATE())
            GROUP BY DAY(a.fecha)
            ORDER BY dia
        ");
        $heatmapData = $this->db->resultSet();
        $heatmap     = array_fill(1, 31, 0);
        foreach ($heatmapData as $h) {
            $heatmap[(int)$h->dia] = (int)$h->total;
        }

        // ────────────────────────────────────────────────────────────────
        // RENDIMIENTO INDIVIDUAL
        // ────────────────────────────────────────────────────────────────

        // NUEVO: Progreso por pasante (barras horizontales) — pro-rata desde asistencias
        $this->db->query("
            SELECT
                CONCAT(dp.nombres, ' ', dp.apellidos) AS nombre,
                COALESCE(prog.horas_validas, 0) AS horas_acumuladas,
                dpa.horas_meta,
                ROUND(LEAST(100, COALESCE(prog.horas_validas, 0) / NULLIF(dpa.horas_meta, 0) * 100), 1) AS progreso_pct,
                d.nombre AS departamento,
                ROUND(AVG(e.promedio_final), 2) AS prom_eval,
                COUNT(DISTINCT e.id) AS num_eval
            FROM datos_pasante dpa
            JOIN datos_personales dp ON dp.usuario_id = dpa.usuario_id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            LEFT JOIN evaluaciones e ON e.pasante_id = dpa.usuario_id
            LEFT JOIN (
                SELECT pasante_id,
                       COALESCE(SUM(COALESCE(NULLIF(horas_calculadas,0),8)),0) AS horas_validas
                FROM asistencias
                WHERE estado IN ('Presente', 'Justificado', 'Ausente')
                GROUP BY pasante_id
            ) AS prog ON prog.pasante_id = dpa.usuario_id
            WHERE dpa.estado_pasantia = 'Activo'
              {$tutorWhere}
            GROUP BY dpa.usuario_id
            ORDER BY prom_eval DESC, progreso_pct DESC
        ");
        $progresoPorPasante = $this->db->resultSet();

        // ────────────────────────────────────────────────────────────────
        // ALERTAS ACCIONABLES
        // ────────────────────────────────────────────────────────────────

        // NUEVO: En riesgo de no cumplir meta (pro-rata: tiempo transcurrido vs días registrados)
        $this->db->query("
            SELECT
                CONCAT(dp.nombres, ' ', dp.apellidos) AS nombre,
                COALESCE(prog.horas_validas, 0) AS horas_acumuladas,
                dpa.horas_meta,
                (dpa.horas_meta - COALESCE(prog.horas_validas, 0)) AS horas_faltantes,
                DATEDIFF(dpa.fecha_fin_estimada, CURDATE()) AS dias_restantes,
                ROUND(LEAST(100, (COALESCE(prog.horas_validas, 0) / NULLIF(dpa.horas_meta, 0)) * 100), 1) AS progreso_pct,
                ROUND(
                    DATEDIFF(CURDATE(), dpa.fecha_inicio_pasantia) /
                    NULLIF(DATEDIFF(dpa.fecha_fin_estimada, dpa.fecha_inicio_pasantia), 0) * 100
                , 1) AS tiempo_pct,
                d.nombre AS departamento
            FROM datos_pasante dpa
            JOIN datos_personales dp ON dp.usuario_id = dpa.usuario_id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            LEFT JOIN (
                SELECT pasante_id,
                       COALESCE(SUM(COALESCE(NULLIF(horas_calculadas,0),8)),0) AS horas_validas
                FROM asistencias
                WHERE estado IN ('Presente', 'Justificado', 'Ausente')
                GROUP BY pasante_id
            ) AS prog ON prog.pasante_id = dpa.usuario_id
            WHERE dpa.estado_pasantia = 'Activo'
              AND dpa.horas_meta > 0
              AND dpa.fecha_fin_estimada IS NOT NULL
              AND dpa.fecha_inicio_pasantia IS NOT NULL
              AND DATEDIFF(dpa.fecha_fin_estimada, CURDATE()) > 0
              {$tutorWhere}
            HAVING (tiempo_pct - progreso_pct) > 20
            ORDER BY (tiempo_pct - progreso_pct) DESC
        ");
        $enRiesgo = $this->db->resultSet();

        // NUEVO: Ausencias sin justificar (últimos 7 días)
        $this->db->query("
            SELECT
                CONCAT(dp.nombres, ' ', dp.apellidos) AS nombre,
                COUNT(*) AS total_ausencias,
                MAX(a.fecha) AS ultima_ausencia,
                d.nombre AS departamento
            FROM asistencias a
            JOIN datos_pasante dpa ON dpa.usuario_id = a.pasante_id
            JOIN datos_personales dp ON dp.usuario_id = a.pasante_id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            WHERE a.estado = 'Ausente'
              AND (a.motivo_justificacion IS NULL OR a.motivo_justificacion = '')
              AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              {$tutorWhere}
            GROUP BY a.pasante_id
            ORDER BY total_ausencias DESC
        ");
        $ausenciasSinJustificar = $this->db->resultSet();

        // NUEVO: Sin evaluación en los últimos 30 días (o nunca evaluados)
        $this->db->query("
            SELECT
                CONCAT(dp.nombres, ' ', dp.apellidos) AS nombre,
                MAX(e.fecha_evaluacion) AS ultima_evaluacion,
                IFNULL(DATEDIFF(CURDATE(), MAX(e.fecha_evaluacion)), 9999) AS dias_sin_eval,
                d.nombre AS departamento
            FROM datos_pasante dpa
            JOIN datos_personales dp ON dp.usuario_id = dpa.usuario_id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            LEFT JOIN evaluaciones e ON e.pasante_id = dpa.usuario_id
            WHERE dpa.estado_pasantia = 'Activo'
              {$tutorWhere}
            GROUP BY dpa.usuario_id
            HAVING ultima_evaluacion IS NULL OR dias_sin_eval > 30
            ORDER BY dias_sin_eval DESC
        ");
        $sinEvaluacion = $this->db->resultSet();

        // Distribución por estado de pasantía en el periodo
        $this->db->query("
            SELECT dpa.estado_pasantia, COUNT(*) AS total
            FROM datos_pasante dpa
            WHERE 1=1 {$tutorWhere}
            GROUP BY dpa.estado_pasantia
        ");
        $estadosPasantia = $this->db->resultSet();

        $this->view('analiticas/index', [
            'title'                  => $isAdmin ? 'Analíticas del Sistema' : 'Mis Analíticas',
            'isAdmin'                => $isAdmin,
            // KPIs
            'kpiTasa'                => $kpiTasa,
            'kpiActivos'             => $kpiActivos,
            'kpiHoras'               => $kpiHoras,
            'kpiProgreso'            => $kpiProgreso,
            'kpiEval'                => $kpiEval,
            // Series temporales
            'seriesPresentes'        => $seriesPresentes,
            'seriesJustificados'     => $seriesJustificados,
            'seriesAusentes'         => $seriesAusentes,
            'seriesHorasMes'         => $seriesHorasMes,
            'seriesHorasSemana'      => $seriesHorasSemana,
            'dailyByMonth'           => $dailyByMonth,
            'totalAsistencias'       => $totalAsistencias,
            // Distribución
            'porDepartamento'        => $porDepartamento,
            'porInstitucion'         => $porInstitucion,
            'heatmap'                => array_values($heatmap),
            'heatmapCalData'         => $heatmapCalData,
            'allCalData'             => $allCalData,
            'mesesSinPeriodo'        => $mesesSinPeriodo, // [SGP-FEATURE-CAL-SIN-PERIODO]
            'hayPeriodoActivo'       => $hayPeriodoActivo,
            'periodoActivo'          => $periodoActivo,
            'estadosPasantia'        => $estadosPasantia,
            // Rendimiento individual
            'progresoPorPasante'     => $progresoPorPasante,
            // Alertas
            'enRiesgo'               => $enRiesgo,
            'ausenciasSinJustificar' => $ausenciasSinJustificar,
            'sinEvaluacion'          => $sinEvaluacion,
        ]);
    }
}
