<?php
/**
 * TutorController — Panel del Tutor
 *
 * RUTAS:
 *   GET  /tutor                    → index()           Dashboard del tutor
 *   GET  /tutor/pasantes           → pasantes()        Lista de mis pasantes
 *   GET  /tutor/perfil/{id}        → perfil($id)       Ficha individual de pasante
 *   GET  /tutor/asistencias        → asistencias()     Asistencias de mis pasantes (diaria/semanal)
 *   POST /tutor/resetPin           → resetPin()        Resetear PIN de un pasante (AJAX)
 *
 * ACCESO: Solo Tutor (rol 2)
 */

class TutorController extends Controller {

    private $db;

    public function __construct() {
        CacheControl::noCache();
        Session::start();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();
        RoleMiddleware::requireRole(2);

        $config = require '../app/config/config.php';
        $this->db = Database::getInstance();
    }

    // ────────────────────────────────────────────────────────────
    // Dashboard del Tutor
    // ────────────────────────────────────────────────────────────
    public function index(): void {
        $tutorId = (int)Session::get('user_id');

        // Departamento del tutor (base para todos los filtros)
        $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :tid LIMIT 1");
        $this->db->bind(':tid', $tutorId);
        $deptId = (int)($this->db->single()->departamento_id ?? 0);

        // KPI 1: Total pasantes del departamento
        $this->db->query("SELECT COUNT(*) AS total FROM datos_pasante WHERE departamento_asignado_id = :dept_id");
        $this->db->bind(':dept_id', $deptId);
        $totalPasantes = (int)($this->db->single()->total ?? 0);

        // KPI 2: Pasantes activos del departamento
        $this->db->query("SELECT COUNT(*) AS total FROM datos_pasante WHERE departamento_asignado_id = :dept_id AND estado_pasantia = 'Activo'");
        $this->db->bind(':dept_id', $deptId);
        $pasantesActivos = (int)($this->db->single()->total ?? 0);

        // KPI 3: Pasantes activos sin evaluación
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM datos_pasante dpa
            LEFT JOIN evaluaciones e ON e.pasante_id = dpa.usuario_id
            WHERE dpa.departamento_asignado_id = :dept_id AND dpa.estado_pasantia = 'Activo' AND e.id IS NULL
        ");
        $this->db->bind(':dept_id', $deptId);
        $evaluacionesPendientes = (int)($this->db->single()->total ?? 0);

        // KPI 4: Horas supervisadas totales (solo dentro del período oficial de pasantía)
        $this->db->query("
            SELECT COALESCE(SUM(horas_validas), 0) AS total
            FROM (
                SELECT a.pasante_id,
                       COALESCE(SUM(COALESCE(NULLIF(a.horas_calculadas,0),8)),0) AS horas_validas
                FROM asistencias a
                INNER JOIN datos_pasante dpa ON dpa.usuario_id = a.pasante_id
                WHERE dpa.departamento_asignado_id = :dept_id
                  AND a.estado IN ('Presente', 'Justificado')
                  AND a.fecha >= COALESCE(dpa.fecha_inicio_pasantia, '2000-01-01')
                  AND a.fecha <= LEAST(COALESCE(dpa.fecha_fin_estimada, CURDATE()), CURDATE())
                GROUP BY a.pasante_id
            ) AS sub
        ");
        $this->db->bind(':dept_id', $deptId);
        $horasSupervisadas = (int)($this->db->single()->total ?? 0);

        // KPI Nuevo: Asistencia HOY
        $this->db->query("
            SELECT
                SUM(CASE WHEN a.estado = 'Presente'    THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN a.estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados,
                SUM(CASE WHEN a.estado = 'Ausente'     THEN 1 ELSE 0 END) AS ausentes
            FROM asistencias a
            INNER JOIN datos_pasante dpa ON dpa.usuario_id = a.pasante_id
            WHERE dpa.departamento_asignado_id = :dept_id
              AND a.fecha = CURDATE()
              AND dpa.estado_pasantia = 'Activo'
        ");
        $this->db->bind(':dept_id', $deptId);
        $hoyRow        = $this->db->single();
        $presentesHoy  = (int)($hoyRow->presentes    ?? 0);
        $justifHoy     = (int)($hoyRow->justificados ?? 0);
        $sinMarcarHoy  = max(0, $pasantesActivos - $presentesHoy - $justifHoy);

        // KPI Nuevo: Próximos a vencer (fecha_fin en 30 días)
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM datos_pasante
            WHERE departamento_asignado_id = :dept_id
              AND estado_pasantia = 'Activo'
              AND fecha_fin_estimada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
        $this->db->bind(':dept_id', $deptId);
        $proximosVencer = (int)($this->db->single()->total ?? 0);

        // Tendencia semanal: últimas 4 semanas (L-V)
        $this->db->query("
            SELECT
                YEARWEEK(a.fecha, 1) AS yw,
                MIN(a.fecha)         AS inicio_semana,
                COUNT(DISTINCT CASE WHEN a.estado IN ('Presente','Justificado') THEN CONCAT(a.pasante_id,'-',a.fecha) END) AS dias_asistidos,
                COUNT(DISTINCT CONCAT(a.pasante_id,'-',a.fecha)) AS dias_totales
            FROM asistencias a
            INNER JOIN datos_pasante dpa ON dpa.usuario_id = a.pasante_id
            WHERE dpa.departamento_asignado_id = :dept_id
              AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
              AND DAYOFWEEK(a.fecha) NOT IN (1,7)
            GROUP BY YEARWEEK(a.fecha, 1)
            ORDER BY yw ASC
            LIMIT 4
        ");
        $this->db->bind(':dept_id', $deptId);
        $tendencia = $this->db->resultSet();

        // Lista de pasantes con progreso + porcentaje calendario para detectar riesgo
        $this->db->query("
            SELECT
                dpa.usuario_id              AS pasante_id,
                dp.nombres,
                dp.apellidos,
                u.cedula,
                d.nombre                    AS departamento,
                LEAST(COALESCE(prog.horas_validas, 0), dpa.horas_meta) AS horas_acumuladas,
                dpa.horas_meta,
                ROUND(LEAST(100, (COALESCE(prog.horas_validas, 0) / NULLIF(dpa.horas_meta, 0)) * 100), 1) AS progreso_pct,
                ROUND(LEAST(100, GREATEST(0,
                    DATEDIFF(LEAST(CURDATE(), COALESCE(dpa.fecha_fin_estimada, CURDATE())),
                             COALESCE(dpa.fecha_inicio_pasantia, CURDATE()))
                    / NULLIF(DATEDIFF(COALESCE(dpa.fecha_fin_estimada, CURDATE()),
                                      COALESCE(dpa.fecha_inicio_pasantia, CURDATE())), 0)
                ) * 100), 1) AS progreso_cal_pct,
                dpa.estado_pasantia,
                dpa.fecha_inicio_pasantia   AS fecha_inicio,
                dpa.fecha_fin_estimada      AS fecha_fin,
                ROUND(AVG(e.promedio_final), 2) AS promedio_eval,
                COUNT(DISTINCT e.id)            AS total_eval
            FROM datos_pasante dpa
            INNER JOIN usuarios          u  ON u.id  = dpa.usuario_id
            LEFT  JOIN datos_personales  dp ON dp.usuario_id = dpa.usuario_id
            LEFT  JOIN departamentos     d  ON d.id  = dpa.departamento_asignado_id
            LEFT  JOIN evaluaciones      e  ON e.pasante_id  = dpa.usuario_id
            LEFT  JOIN (
                SELECT a.pasante_id,
                       COALESCE(SUM(COALESCE(NULLIF(a.horas_calculadas,0),8)),0) AS horas_validas
                FROM asistencias a
                INNER JOIN datos_pasante dp2 ON dp2.usuario_id = a.pasante_id
                WHERE a.estado IN ('Presente', 'Justificado')
                  AND a.fecha >= COALESCE(dp2.fecha_inicio_pasantia, '2000-01-01')
                  AND a.fecha <= LEAST(COALESCE(dp2.fecha_fin_estimada, CURDATE()), CURDATE())
                GROUP BY a.pasante_id
            ) AS prog ON prog.pasante_id = dpa.usuario_id
            WHERE dpa.departamento_asignado_id = :dept_id
            GROUP BY dpa.usuario_id
            ORDER BY
                FIELD(dpa.estado_pasantia,'Activo','Pendiente','Sin Asignar','Finalizado','Retirado'),
                dp.apellidos ASC
            LIMIT 20
        ");
        $this->db->bind(':dept_id', $deptId);
        $misPasantes = $this->db->resultSet();

        // Construir alertas inteligentes en PHP
        $alertas = [];
        foreach ($misPasantes as $p) {
            if (($p->estado_pasantia ?? '') !== 'Activo') continue;
            $brecha = (float)($p->progreso_cal_pct ?? 0) - (float)($p->progreso_pct ?? 0);
            // Riesgo: calendario avanza >20pts más que progreso real
            if ($brecha > 20) {
                $alertas[] = [
                    'tipo'    => 'riesgo',
                    'nombre'  => trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')),
                    'id'      => $p->pasante_id,
                    'detalle' => 'Progreso ' . $p->progreso_pct . '% — calendario ' . $p->progreso_cal_pct . '% · riesgo alto',
                ];
            }
            // Sin evaluar
            if ((int)($p->total_eval ?? 0) === 0) {
                $alertas[] = [
                    'tipo'    => 'sin_eval',
                    'nombre'  => trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')),
                    'id'      => $p->pasante_id,
                    'detalle' => 'Sin ninguna evaluación registrada',
                ];
            }
            // Próximo a vencer (≤30 días)
            if (!empty($p->fecha_fin)) {
                $diasRestantes = (int)((strtotime($p->fecha_fin) - time()) / 86400);
                if ($diasRestantes >= 0 && $diasRestantes <= 30) {
                    $alertas[] = [
                        'tipo'    => 'vence',
                        'nombre'  => trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')),
                        'id'      => $p->pasante_id,
                        'detalle' => 'Pasantía vence en ' . $diasRestantes . ' día' . ($diasRestantes !== 1 ? 's' : ''),
                    ];
                }
            }
        }
        // Contar en riesgo
        $enRiesgo = count(array_filter($alertas, fn($a) => $a['tipo'] === 'riesgo'));

        $this->view('tutor/dashboard', [
            'title'                  => 'Panel de Tutor',
            'role'                   => 'Tutor',
            'user_name'              => Session::get('user_name'),
            'totalPasantes'          => $totalPasantes,
            'pasantesActivos'        => $pasantesActivos,
            'evaluacionesPendientes' => $evaluacionesPendientes,
            'horasSupervisadas'      => $horasSupervisadas,
            'presentesHoy'           => $presentesHoy,
            'sinMarcarHoy'           => $sinMarcarHoy,
            'proximosVencer'         => $proximosVencer,
            'enRiesgo'               => $enRiesgo,
            'tendencia'              => $tendencia,
            'alertas'                => array_slice($alertas, 0, 5),
            'misPasantes'            => $misPasantes,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Lista completa de mis pasantes
    // GET /tutor/pasantes
    // ────────────────────────────────────────────────────────────
    public function pasantes(): void {
        $tutorId = (int)Session::get('user_id');

        // Departamento del tutor
        $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :tid LIMIT 1");
        $this->db->bind(':tid', $tutorId);
        $deptId = (int)($this->db->single()->departamento_id ?? 0);

        $this->db->query("
            SELECT
                dpa.usuario_id              AS pasante_id,
                dp.nombres,
                dp.apellidos,
                u.cedula,
                d.nombre                    AS departamento,
                COALESCE(prog.dias_validos, 0)         AS dias_acumulados,
                LEAST(COALESCE(prog.horas_validas, 0), dpa.horas_meta) AS horas_acumuladas,
                dpa.horas_meta,
                ROUND(LEAST(100, (COALESCE(prog.horas_validas, 0) / NULLIF(dpa.horas_meta, 0)) * 100), 1) AS progreso_pct,
                dpa.estado_pasantia,
                dpa.fecha_inicio_pasantia   AS fecha_inicio,
                dpa.fecha_fin_estimada      AS fecha_fin,
                COALESCE(i.nombre, dpa.institucion_procedencia, 'No especificada') AS institucion,
                COALESCE(ult.ultima_asistencia, '—') AS ultima_asistencia,
                COALESCE(ult.ultimo_estado, '—')     AS ultimo_estado,
                ROUND(AVG(e.promedio_final), 2)       AS promedio_eval,
                COUNT(DISTINCT e.id)                  AS total_evaluaciones
            FROM datos_pasante dpa
            INNER JOIN usuarios u ON u.id = dpa.usuario_id
            LEFT  JOIN datos_personales dp ON dp.usuario_id = dpa.usuario_id
            LEFT  JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            LEFT  JOIN instituciones i ON i.id = dpa.institucion_id
            LEFT  JOIN evaluaciones e ON e.pasante_id = dpa.usuario_id
            LEFT  JOIN (
                SELECT a.pasante_id,
                       COUNT(*) AS dias_validos,
                       COALESCE(SUM(COALESCE(NULLIF(a.horas_calculadas,0),8)),0) AS horas_validas
                FROM asistencias a
                INNER JOIN datos_pasante dp2 ON dp2.usuario_id = a.pasante_id
                WHERE a.estado IN ('Presente', 'Justificado')
                  AND a.fecha >= COALESCE(dp2.fecha_inicio_pasantia, '2000-01-01')
                  AND a.fecha <= LEAST(COALESCE(dp2.fecha_fin_estimada, CURDATE()), CURDATE())
                GROUP BY a.pasante_id
            ) AS prog ON prog.pasante_id = dpa.usuario_id
            LEFT  JOIN (
                SELECT pasante_id,
                       MAX(fecha) AS ultima_asistencia,
                       SUBSTRING_INDEX(GROUP_CONCAT(estado ORDER BY fecha DESC, id DESC SEPARATOR '|'), '|', 1) AS ultimo_estado
                FROM asistencias
                GROUP BY pasante_id
            ) AS ult ON ult.pasante_id = dpa.usuario_id
            WHERE dpa.departamento_asignado_id = :dept_id
            GROUP BY dpa.usuario_id
            ORDER BY
                FIELD(dpa.estado_pasantia, 'Activo','Pendiente','Sin Asignar','Finalizado','Retirado'),
                dp.apellidos ASC
        ");
        $this->db->bind(':dept_id', $deptId);
        $pasantes = $this->db->resultSet();

        // KPI extra: activos sin ninguna evaluación
        $this->db->query("
            SELECT COUNT(*) AS total
            FROM datos_pasante dpa
            LEFT JOIN evaluaciones e ON e.pasante_id = dpa.usuario_id
            WHERE dpa.departamento_asignado_id = :dept_id AND dpa.estado_pasantia = 'Activo' AND e.id IS NULL
        ");
        $this->db->bind(':dept_id', $deptId);
        $pendientesEval = (int)($this->db->single()->total ?? 0);

        // KPI extra: % puntualidad del mes actual (llegadas a tiempo vs total presente)
        $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN TIME(COALESCE(a.hora_entrada, a.hora_registro)) <= '08:05:00' THEN 1 ELSE 0 END) AS a_tiempo
            FROM asistencias a
            INNER JOIN datos_pasante dpa ON dpa.usuario_id = a.pasante_id
            WHERE dpa.departamento_asignado_id = :dept_id
              AND a.estado IN ('Presente','Justificado')
              AND a.fecha >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $this->db->bind(':dept_id', $deptId);
        $puntRow = $this->db->single();
        $pctPuntualidad = ($puntRow && $puntRow->total > 0)
            ? (int)round($puntRow->a_tiempo / $puntRow->total * 100)
            : 0;

        $this->view('tutor/mis_pasantes', [
            'title'           => 'Mis Pasantes',
            'pasantes'        => $pasantes,
            'total'           => count($pasantes),
            'activos'         => count(array_filter($pasantes, fn($p) => $p->estado_pasantia === 'Activo')),
            'pendientesEval'  => $pendientesEval,
            'pctPuntualidad'  => $pctPuntualidad,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Ficha individual de un pasante
    // GET /tutor/perfil/{id}
    // ────────────────────────────────────────────────────────────
    public function perfil($pasanteId = null): void {
        $tutorId   = (int)Session::get('user_id');
        $pasanteId = (int)($pasanteId ?? 0);

        if ($pasanteId <= 0) {
            $this->redirect('tutor/pasantes');
            return;
        }

        // Departamento del tutor
        $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :tid LIMIT 1");
        $this->db->bind(':tid', $tutorId);
        $deptId = (int)($this->db->single()->departamento_id ?? 0);

        // Verificar que el pasante pertenece al mismo departamento
        $this->db->query("
            SELECT
                u.id, u.cedula, dp.nombres, dp.apellidos, dp.telefono,
                d.nombre AS departamento,
                COALESCE(i.nombre, dpa.institucion_procedencia, 'No especificada') AS institucion,
                dpa.estado_pasantia,
                dpa.fecha_inicio_pasantia    AS fecha_inicio,
                dpa.fecha_fin_estimada       AS fecha_fin,
                dpa.horas_meta,
                dpa.tutor_id,
                dpa.departamento_asignado_id AS dept_pasante_id
            FROM usuarios u
            INNER JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT  JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            LEFT  JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            LEFT  JOIN instituciones i ON i.id = dpa.institucion_id
            WHERE u.id = :pid AND u.rol_id = 3
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $pasante = $this->db->single();

        // Acceso permitido si el pasante es del mismo departamento
        if (!$pasante || (int)($pasante->dept_pasante_id ?? 0) !== $deptId) {
            $this->redirect('tutor/pasantes');
            return;
        }

        // Progreso pro-rata
        require_once APPROOT . '/models/AsistenciaModel.php';
        $asistenciaModel = new AsistenciaModel($this->db);
        $progreso = $asistenciaModel->calcularProgresoProRata($pasanteId, (int)($pasante->horas_meta ?? 1440));
        $calendario = null;
        if ($pasante->fecha_inicio) {
            $calendario = $asistenciaModel->calcularProgresoPorCalendario($pasante->fecha_inicio, (int)($pasante->horas_meta ?? 1440));
        }

        // Historial de asistencias (últimos 60 registros)
        $historial = $asistenciaModel->obtenerHistorialCompletoPasante($pasanteId, 60);

        // Evaluaciones de este pasante hechas por este tutor
        $this->db->query("
            SELECT id, fecha_evaluacion, lapso_academico, promedio_final, observaciones
            FROM evaluaciones
            WHERE pasante_id = :pid
            ORDER BY fecha_evaluacion DESC
            LIMIT 10
        ");
        $this->db->bind(':pid', $pasanteId);
        $evaluaciones = $this->db->resultSet();

        $this->view('tutor/perfil_pasante', [
            'title'      => 'Perfil — ' . trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? '')),
            'pasante'    => $pasante,
            'progreso'   => $progreso,
            'calendario' => $calendario,
            'historial'  => $historial,
            'evaluaciones' => $evaluaciones,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Asistencias de mis pasantes (diaria/semanal/mensual/total)
    // GET /tutor/asistencias
    // ────────────────────────────────────────────────────────────
    public function asistencias(): void {
        $tutorId = (int)Session::get('user_id');
        $hoy     = date('Y-m-d');
        $vista   = $_GET['vista'] ?? 'diaria';
        if (!in_array($vista, ['diaria','semanal','mensual','total'])) $vista = 'diaria';

        require_once APPROOT . '/models/AsistenciaModel.php';
        $asistenciaModel = new AsistenciaModel($this->db);
        $asistenciaModel->autoFinalizarVencidos();

        // Departamento del tutor
        $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :tid LIMIT 1");
        $this->db->bind(':tid', $tutorId);
        $deptId = (int)($this->db->single()->departamento_id ?? 0);

        $fechaInicio = $hoy;
        $fechaFin    = $hoy;

        $mesNum  = (int)($_GET['mes']  ?? date('m'));
        $anioNum = (int)($_GET['anio'] ?? date('Y'));
        if ($mesNum < 1 || $mesNum > 12) $mesNum = (int)date('m');

        $paramsUrl = [
            'fecha'  => $_GET['fecha']  ?? $hoy,
            'semana' => $_GET['semana'] ?? date('W'),
            'mes'    => str_pad($mesNum, 2, '0', STR_PAD_LEFT),
            'anio'   => $anioNum,
        ];

        $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
                  '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
        $nombreMes = $meses[$paramsUrl['mes']] ?? '';

        // Navegación mensual
        $mesAnt  = $mesNum === 1  ? 12 : $mesNum - 1;
        $anioAnt = $mesNum === 1  ? $anioNum - 1 : $anioNum;
        $mesSig  = $mesNum === 12 ? 1  : $mesNum + 1;
        $anioSig = $mesNum === 12 ? $anioNum + 1 : $anioNum;
        $urlMesAnt = URLROOT . '/tutor/asistencias?vista=mensual&mes=' . str_pad($mesAnt,2,'0',STR_PAD_LEFT) . '&anio=' . $anioAnt;
        $urlMesSig = URLROOT . '/tutor/asistencias?vista=mensual&mes=' . str_pad($mesSig,2,'0',STR_PAD_LEFT) . '&anio=' . $anioSig;

        if ($vista === 'diaria') {
            $fechaInicio = $paramsUrl['fecha'];
            $fechaFin    = $paramsUrl['fecha'];
        } elseif ($vista === 'semanal') {
            $dto = new DateTime();
            $dto->setISODate((int)$paramsUrl['anio'], (int)$paramsUrl['semana']);
            $fechaInicio = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $fechaFin = $dto->format('Y-m-d');
        } elseif ($vista === 'mensual') {
            $fechaInicio = $paramsUrl['anio'] . '-' . $paramsUrl['mes'] . '-01';
            $fechaFin    = date('Y-m-t', strtotime($fechaInicio));
        } elseif ($vista === 'total') {
            $periodoId = (int)($_GET['periodo_id'] ?? 0);
            if ($periodoId > 0) {
                $this->db->query("SELECT fecha_inicio, fecha_fin FROM periodos_academicos WHERE id = :id LIMIT 1");
                $this->db->bind(':id', $periodoId);
                $p = $this->db->single();
                if ($p) {
                    $fechaInicio = $p->fecha_inicio;
                    $fechaFin    = $p->fecha_fin;
                } else {
                    $fechaInicio = '2000-01-01';
                    $fechaFin    = $hoy;
                }
            } else {
                $fechaInicio = '2000-01-01';
                $fechaFin    = $hoy;
            }
        }

        // Registros de asistencia para todos mis pasantes en el rango
        $this->db->query("
            SELECT
                a.id, a.fecha, a.hora_registro, a.estado, a.metodo,
                a.motivo_justificacion,
                u.id   AS pasante_id,
                u.cedula,
                dp.nombres, dp.apellidos,
                d.nombre AS departamento_nombre
            FROM asistencias a
            INNER JOIN usuarios u ON u.id = a.pasante_id
            LEFT  JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT  JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            LEFT  JOIN departamentos d ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            WHERE dpa.departamento_asignado_id = :dept_id
              AND a.fecha >= :fi AND a.fecha <= :ff
            ORDER BY a.fecha DESC, a.hora_registro DESC
        ");
        $this->db->bind(':dept_id', $deptId);
        $this->db->bind(':fi', $fechaInicio);
        $this->db->bind(':ff', $fechaFin);
        $registros = $this->db->resultSet();

        // Todos mis pasantes activos (para saber quién no marcó)
        $this->db->query("
            SELECT u.id, u.cedula, dp.nombres, dp.apellidos, d.nombre AS departamento_nombre
            FROM datos_pasante dpa
            INNER JOIN usuarios u ON u.id = dpa.usuario_id
            LEFT  JOIN datos_personales dp ON dp.usuario_id = dpa.usuario_id
            LEFT  JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            WHERE dpa.departamento_asignado_id = :dept_id AND dpa.estado_pasantia = 'Activo'
            ORDER BY dp.apellidos ASC
        ");
        $this->db->bind(':dept_id', $deptId);
        $misPasantes = $this->db->resultSet();

        // Calcular KPIs diarios
        $presentes    = count(array_filter($registros, fn($r) => $r->estado === 'Presente'));
        $ausentes     = count(array_filter($registros, fn($r) => $r->estado === 'Ausente'));
        $justificados = count(array_filter($registros, fn($r) => $r->estado === 'Justificado'));
        $sinMarcar    = [];

        if ($vista === 'diaria') {
            $marcadosIds = array_map(fn($r) => (int)$r->pasante_id, $registros);
            $sinMarcar   = array_filter($misPasantes, fn($p) => !in_array((int)$p->id, $marcadosIds));
            $ausentes    += count($sinMarcar);
        }

        // ── Inicializar variables de vistas avanzadas ──
        $bentoMensual     = [];
        $chartSemanasJson = '[]';
        $chartPctsJson    = '[]';
        $healthIndex      = 100;
        $resumenTotal     = [];

        // Datos semanales (mapa de calor)
        $datosSemanales = [];
        $navSemana = [];
        if ($vista === 'semanal') {
            foreach ($misPasantes as $p) {
                $depto = $p->departamento_nombre ?? 'Sin Asignar';
                if (!isset($datosSemanales[$depto])) $datosSemanales[$depto] = [];
                $datosSemanales[$depto][$p->id] = [
                    'nombre'  => trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')),
                    'dias'    => [1 => '-', 2 => '-', 3 => '-', 4 => '-', 5 => '-'],
                    'motivos' => [1 => '', 2 => '', 3 => '', 4 => '', 5 => ''],
                    'totales' => ['P' => 0, 'A' => 0, 'J' => 0],
                ];
            }
            foreach ($registros as $reg) {
                $depto     = $reg->departamento_nombre ?? 'Sin Asignar';
                $pid       = $reg->pasante_id;
                $diaSemana = date('N', strtotime($reg->fecha));
                if ($diaSemana <= 5 && isset($datosSemanales[$depto][$pid])) {
                    if (stripos($reg->estado, 'Presente') !== false) {
                        $datosSemanales[$depto][$pid]['dias'][$diaSemana] = 'P';
                        $datosSemanales[$depto][$pid]['totales']['P']++;
                    } elseif (stripos($reg->estado, 'Ausente') !== false) {
                        $datosSemanales[$depto][$pid]['dias'][$diaSemana] = 'A';
                        $datosSemanales[$depto][$pid]['totales']['A']++;
                    } elseif (stripos($reg->estado, 'Justificado') !== false) {
                        $datosSemanales[$depto][$pid]['dias'][$diaSemana] = 'J';
                        $datosSemanales[$depto][$pid]['totales']['J']++;
                    }
                    $datosSemanales[$depto][$pid]['motivos'][$diaSemana] = $reg->motivo_justificacion ?? '';
                }
            }
            $fechaBase = new DateTime();
            $fechaBase->setISODate((int)$paramsUrl['anio'], (int)$paramsUrl['semana']);
            $ant = clone $fechaBase; $ant->modify('-1 week');
            $sig = clone $fechaBase; $sig->modify('+1 week');
            $navSemana = [
                'ant_url' => URLROOT . "/tutor/asistencias?vista=semanal&semana={$ant->format('W')}&anio={$ant->format('Y')}",
                'sig_url' => URLROOT . "/tutor/asistencias?vista=semanal&semana={$sig->format('W')}&anio={$sig->format('Y')}",
                'texto'   => "Semana {$paramsUrl['semana']} — {$paramsUrl['anio']}",
            ];
        }

        // ── PROCESADOR MENSUAL ──
        if ($vista === 'mensual') {
            foreach ($registros as $reg) {
                $depto = $reg->departamento_nombre ?? 'Sin Asignar';
                $pid   = $reg->pasante_id;
                $nombre = trim(($reg->apellidos ?? '') . ', ' . ($reg->nombres ?? ''));
                $ini    = strtoupper(substr($reg->apellidos ?? 'P', 0, 1) . substr($reg->nombres ?? 'P', 0, 1));
                if (!isset($bentoMensual[$depto])) $bentoMensual[$depto] = [];
                if (!isset($bentoMensual[$depto][$pid])) {
                    $bentoMensual[$depto][$pid] = [
                        'nombre'       => $nombre,
                        'cedula'       => $reg->cedula ?? '',
                        'iniciales'    => $ini,
                        'pasante_id'   => (int)$pid,
                        'presentes'    => 0,
                        'ausentes'     => 0,
                        'justificados' => 0,
                    ];
                }
                $est = strtolower($reg->estado ?? '');
                if (str_contains($est, 'presente'))    $bentoMensual[$depto][$pid]['presentes']++;
                elseif (str_contains($est, 'ausente')) $bentoMensual[$depto][$pid]['ausentes']++;
                else                                   $bentoMensual[$depto][$pid]['justificados']++;
            }
            // Chart semanal del mes
            $semData = [];
            foreach ($registros as $reg) {
                $dia = (int)date('j', strtotime($reg->fecha));
                $sw  = (int)ceil($dia / 7);
                if (!isset($semData[$sw])) $semData[$sw] = ['p'=>0,'t'=>0];
                $semData[$sw]['t']++;
                if (str_contains(strtolower($reg->estado ?? ''), 'presente')) $semData[$sw]['p']++;
            }
            $numSems   = (int)ceil((int)date('t', strtotime($fechaInicio)) / 7);
            $semLabels = []; $semPcts = [];
            for ($s = 1; $s <= $numSems; $s++) {
                $semLabels[] = 'Sem ' . $s;
                $d = $semData[$s] ?? null;
                $semPcts[] = ($d && $d['t'] > 0) ? round($d['p'] / $d['t'] * 100) : 0;
            }
            $totalEv   = count($registros);
            $totalPres = count(array_filter($registros, fn($r) => str_contains(strtolower($r->estado ?? ''), 'presente')));
            $healthIndex = $totalEv > 0 ? round($totalPres / $totalEv * 100) : 100;
            $chartSemanasJson = json_encode($semLabels);
            $chartPctsJson    = json_encode($semPcts);
        }

        // ── Períodos disponibles (para vista total) ──
        $periodosFiltro      = [];
        $periodoSeleccionado = null;
        $periodoIdSel        = 0;

        if ($vista === 'total') {
            $this->db->query("
                SELECT pa.id, pa.nombre, pa.fecha_inicio, pa.fecha_fin, pa.estado
                FROM periodos_academicos pa
                WHERE EXISTS (
                    SELECT 1 FROM datos_pasante dpa
                    WHERE dpa.periodo_id = pa.id
                      AND dpa.departamento_asignado_id = :dept_id
                )
                ORDER BY pa.fecha_inicio DESC
            ");
            $this->db->bind(':dept_id', $deptId);
            $periodosFiltro = $this->db->resultSet();

            $periodoId = (int)($_GET['periodo_id'] ?? 0);
            if ($periodoId > 0) {
                foreach ($periodosFiltro as $pf) {
                    if ($pf->id == $periodoId) { $periodoSeleccionado = $pf; $periodoIdSel = $pf->id; break; }
                }
            }
            if (!$periodoSeleccionado && !empty($periodosFiltro)) {
                foreach ($periodosFiltro as $pf) {
                    if ($pf->estado === 'Activo') { $periodoSeleccionado = $pf; $periodoIdSel = $pf->id; break; }
                }
                if (!$periodoSeleccionado) { $periodoSeleccionado = $periodosFiltro[0]; $periodoIdSel = $periodosFiltro[0]->id; }
            }

            // Mapa horas_meta y estado por pasante
            $this->db->query("
                SELECT dpa.usuario_id,
                       COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                       COALESCE(dpa.estado_pasantia, 'Activo') AS estado_pasantia
                FROM datos_pasante dpa
                WHERE dpa.departamento_asignado_id = :dept_id
            ");
            $this->db->bind(':dept_id', $deptId);
            $metaRows = $this->db->resultSet();
            $metaMap = [];
            foreach ($metaRows as $mr) {
                $metaMap[$mr->usuario_id] = ['horas_meta' => (int)$mr->horas_meta, 'estado_pasantia' => $mr->estado_pasantia];
            }
        }

        // ── PROCESADOR TOTAL (histórico) ──
        if ($vista === 'total') {
            foreach ($registros as $reg) {
                $pid    = $reg->pasante_id;
                $nombre = trim(($reg->apellidos ?? '') . ', ' . ($reg->nombres ?? ''));
                $ini    = strtoupper(substr($reg->apellidos ?? 'P', 0, 1) . substr($reg->nombres ?? 'P', 0, 1));
                if (!isset($resumenTotal[$pid])) {
                    $meta = $metaMap[$pid] ?? ['horas_meta' => 1440, 'estado_pasantia' => 'Activo'];
                    $resumenTotal[$pid] = [
                        'nombre'          => $nombre,
                        'cedula'          => $reg->cedula ?? '',
                        'iniciales'       => $ini,
                        'pasante_id'      => (int)$pid,
                        'departamento'    => $reg->departamento_nombre ?? '—',
                        'horas_meta'      => $meta['horas_meta'],
                        'estado_pasantia' => $meta['estado_pasantia'],
                        'presentes'       => 0,
                        'ausentes'        => 0,
                        'justificados'    => 0,
                    ];
                }
                $est = strtolower($reg->estado ?? '');
                if (str_contains($est, 'presente'))    $resumenTotal[$pid]['presentes']++;
                elseif (str_contains($est, 'ausente')) $resumenTotal[$pid]['ausentes']++;
                else                                   $resumenTotal[$pid]['justificados']++;
            }
            usort($resumenTotal, fn($a,$b) => strcmp($a['nombre'], $b['nombre']));
        }

        $this->view('tutor/asistencias', [
            'title'           => 'Asistencias de Mis Pasantes',
            'vista'           => $vista,
            'paramsUrl'       => $paramsUrl,
            'fechaInicio'     => $fechaInicio,
            'fechaFin'        => $fechaFin,
            'hoy'             => $hoy,
            'registros'       => $registros,
            'misPasantes'     => $misPasantes,
            'sinMarcar'       => array_values($sinMarcar),
            'presentes'       => $presentes,
            'ausentes'        => $ausentes,
            'justificados'    => $justificados,
            'totalActivos'    => count($misPasantes),
            'datosSemanales'  => $datosSemanales,
            'navSemana'       => $navSemana,
            // Mensual
            'nombreMes'       => $nombreMes,
            'urlMesAnt'       => $urlMesAnt,
            'urlMesSig'       => $urlMesSig,
            'bentoMensual'    => $bentoMensual,
            'chartSemanasJson'=> $chartSemanasJson,
            'chartPctsJson'   => $chartPctsJson,
            'healthIndex'     => $healthIndex,
            // Total
            'resumenTotal'        => $resumenTotal,
            'periodosFiltro'      => $periodosFiltro,
            'periodoSeleccionado' => $periodoSeleccionado,
            'periodoIdSel'        => $periodoIdSel,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Dashboard de Puntualidad
    // GET /tutor/puntualidad?rango=mes|semana|hoy|todo
    // ────────────────────────────────────────────────────────────
    public function puntualidad(): void {
        $tutorId = (int)Session::get('user_id');

        // Departamento del tutor
        $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :tid LIMIT 1");
        $this->db->bind(':tid', $tutorId);
        $deptId = (int)($this->db->single()->departamento_id ?? 0);

        require_once APPROOT . '/helpers/RetardoHelper.php';

        // Rango de fechas
        $rangosValidos = ['hoy', 'semana', 'mes', 'todo'];
        $rango = in_array($_GET['rango'] ?? '', $rangosValidos) ? $_GET['rango'] : 'mes';
        $hoy   = date('Y-m-d');

        switch ($rango) {
            case 'hoy':
                $fi = $hoy; $ff = $hoy;
                $rangoLabel = 'Hoy (' . date('d/m/Y') . ')';
                break;
            case 'semana':
                $lunes = date('Y-m-d', strtotime('monday this week'));
                $fi = $lunes; $ff = date('Y-m-d', strtotime('sunday this week'));
                $rangoLabel = 'Esta semana';
                break;
            case 'todo':
                $fi = '2000-01-01'; $ff = $hoy;
                $rangoLabel = 'Todo el período';
                break;
            default: // mes
                $fi = date('Y-m-01'); $ff = date('Y-m-t');
                $rangoLabel = 'Este mes (' . date('F Y') . ')';
                break;
        }

        // Registros de asistencia con hora programada (JOIN con asignaciones)
        $this->db->query("
            SELECT
                a.fecha,
                a.estado,
                COALESCE(a.hora_entrada, a.hora_registro) AS hora_entrada,
                COALESCE(asig.hora_entrada, '08:00:00')   AS hora_asignacion,
                u.id       AS pasante_id,
                dp.nombres,
                dp.apellidos,
                d.nombre   AS departamento
            FROM asistencias a
            INNER JOIN usuarios         u    ON u.id            = a.pasante_id
            LEFT  JOIN datos_personales dp   ON dp.usuario_id   = u.id
            LEFT  JOIN datos_pasante    dpa  ON dpa.usuario_id  = u.id
            LEFT  JOIN departamentos    d    ON d.id = dpa.departamento_asignado_id
            LEFT  JOIN asignaciones     asig ON asig.pasante_id = a.pasante_id
                                           AND asig.estado = 'activo'
            WHERE dpa.departamento_asignado_id = :dept_id
              AND a.fecha BETWEEN :fi AND :ff
            ORDER BY dp.apellidos ASC, a.fecha DESC
        ");
        $this->db->bind(':dept_id', $deptId);
        $this->db->bind(':fi',  $fi);
        $this->db->bind(':ff',  $ff);
        $todosRegistros = $this->db->resultSet();

        // Agrupar por pasante y calcular resumen con RetardoHelper
        $porPasante = [];
        foreach ($todosRegistros as $r) {
            $pid = $r->pasante_id;
            if (!isset($porPasante[$pid])) {
                $porPasante[$pid] = [
                    'id'          => $pid,
                    'nombre'      => trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? '')),
                    'departamento'=> $r->departamento ?? 'Sin asignar',
                    'registros'   => [],
                ];
            }
            $porPasante[$pid]['registros'][] = $r;
        }

        $ranking = [];
        foreach ($porPasante as $pid => $datos) {
            $res = RetardoHelper::resumen($datos['registros']);
            $ranking[] = [
                'pasante_id'   => $pid,
                'nombre'       => $datos['nombre'],
                'departamento' => $datos['departamento'],
                'total'        => $res['total'],
                'a_tiempo'     => $res['a_tiempo'],
                'leve'         => $res['leve'],
                'severo'       => $res['severo'],
                'ausente'      => $res['ausente'],
                'pct_puntual'  => $res['pct_puntual'],
                'retraso_prom' => $res['retraso_prom'],
            ];
        }

        // Ordenar por % puntualidad ascendente (los más problemáticos primero)
        usort($ranking, fn($a, $b) => $a['pct_puntual'] <=> $b['pct_puntual']);

        // KPIs globales del tutor en el rango
        $totalRegistros = count($todosRegistros);
        $globalATiempo  = array_sum(array_column($ranking, 'a_tiempo'));
        $globalLeve     = array_sum(array_column($ranking, 'leve'));
        $globalSevero   = array_sum(array_column($ranking, 'severo'));
        $globalAusente  = array_sum(array_column($ranking, 'ausente'));
        $globalPct      = $totalRegistros > 0
            ? round(($globalATiempo / $totalRegistros) * 100, 1) : 0;

        // Historial reciente de retardos (para la tabla detalle)
        $historialRetardos = array_values(array_filter($todosRegistros, function($r) {
            $hora     = $r->hora_entrada ?? null;
            $horaAsig = $r->hora_asignacion ?? '08:00:00';
            $estado   = RetardoHelper::clasificar($hora, $horaAsig);
            return in_array($estado, [RetardoHelper::ESTADO_LEVE, RetardoHelper::ESTADO_SEVERO]);
        }));
        // Limitar a los 50 más recientes
        $historialRetardos = array_slice($historialRetardos, 0, 50);

        $this->view('tutor/puntualidad', [
            'title'            => 'Dashboard de Puntualidad',
            'rango'            => $rango,
            'rangoLabel'       => $rangoLabel,
            'fi'               => $fi,
            'ff'               => $ff,
            'ranking'          => $ranking,
            'historialRetardos'=> $historialRetardos,
            'kpis'             => [
                'total'       => $totalRegistros,
                'a_tiempo'    => $globalATiempo,
                'leve'        => $globalLeve,
                'severo'      => $globalSevero,
                'ausente'     => $globalAusente,
                'pct_puntual' => $globalPct,
            ],
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // Resetear PIN de un pasante asignado a este tutor
    // POST /tutor/resetPin  (AJAX → JSON)
    // ────────────────────────────────────────────────────────────
    public function resetPin(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF — el frontend ya enviaba el header, solo faltaba
        // que el backend lo comprobara.
        $this->verifyCsrf();

        $tutorId   = (int)Session::get('user_id');
        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        $nuevoPin  = trim($_POST['nuevo_pin'] ?? '');

        if (!$pasanteId || !preg_match('/^\d{4}$/', $nuevoPin)) {
            echo json_encode(['success' => false, 'message' => 'Pasante o PIN inválido (PIN debe tener 4 dígitos).']);
            exit;
        }

        // Verificar que el pasante sea del mismo departamento del tutor
        $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :tid LIMIT 1");
        $this->db->bind(':tid', $tutorId);
        $deptId = (int)($this->db->single()->departamento_id ?? 0);

        $this->db->query("
            SELECT dpa.departamento_asignado_id, CONCAT(dp.nombres,' ',dp.apellidos) AS nombre
            FROM datos_pasante dpa
            LEFT JOIN datos_personales dp ON dp.usuario_id = dpa.usuario_id
            WHERE dpa.usuario_id = :pid LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $dpa = $this->db->single();

        if (!$dpa || (int)($dpa->departamento_asignado_id ?? 0) !== $deptId) {
            echo json_encode(['success' => false, 'message' => 'Este pasante no pertenece a tu departamento.']);
            exit;
        }
        $nombrePas = trim($dpa->nombre ?? "ID:$pasanteId");

        // Actualizar el PIN con bcrypt
        $pinHash = password_hash($nuevoPin, PASSWORD_BCRYPT);

        $this->db->query("UPDATE usuarios SET pin_asistencia = :pin WHERE id = :id");
        $this->db->bind(':pin', $pinHash);
        $this->db->bind(':id', $pasanteId);
        $ok = $this->db->execute();

        // Log en auditoría
        if ($ok) {
            $this->db->query("INSERT INTO bitacora (usuario_id, accion, ip_address, detalles) VALUES (:uid, 'RESET_PIN', :ip, :det)");
            $this->db->bind(':uid', $tutorId);
            $this->db->bind(':ip',  $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            $this->db->bind(':det', json_encode(['pasante' => $nombrePas]));
            $this->db->execute();
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? '✅ PIN actualizado correctamente.' : 'Error al actualizar el PIN.',
        ]);
        exit;
    }
}
