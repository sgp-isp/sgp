<?php
/**
 * PeriodosController — Gestión de Períodos Académicos
 *
 * URL base: /periodos  — exclusiva para Administradores (rol_id = 1).
 * Permite crear, editar, cerrar períodos y asignar pasantes a cohortes.
 */

declare(strict_types=1);

class PeriodosController extends Controller
{
    private Database $db;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();

        if (!RoleMiddleware::hasAnyRole([0, 1])) {
            RoleMiddleware::redirectToRoleDashboard(Session::get('role_id'));
        }

        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /periodos — lista todos los períodos con conteo de pasantes
    // ─────────────────────────────────────────────────────────────────
    public function index(): void
    {
        $this->db->query("
            SELECT
                pa.*,
                COUNT(dpa.usuario_id)                                             AS total_pasantes,
                SUM(dpa.estado_pasantia = 'Activo')                               AS pasantes_activos
            FROM periodos_academicos pa
            LEFT JOIN datos_pasante dpa ON dpa.periodo_id = pa.id
            GROUP BY pa.id
            ORDER BY pa.fecha_inicio DESC
        ");
        $periodos = $this->db->resultSet();

        // KPI aggregates
        $totalPeriodos    = count($periodos);
        $totalActivos     = 0;
        $totalCerrados    = 0;
        $totalPasantesSum = 0;

        foreach ($periodos as $p) {
            if ($p->estado === 'Activo')   $totalActivos++;
            if ($p->estado === 'Cerrado')  $totalCerrados++;
            $totalPasantesSum += (int)($p->total_pasantes ?? 0);
        }

        $csrf = Session::generateCsrfToken();

        $this->view('periodos/index', [
            'periodos'         => $periodos,
            'totalPeriodos'    => $totalPeriodos,
            'totalActivos'     => $totalActivos,
            'totalCerrados'    => $totalCerrados,
            'totalPasantesSum' => $totalPasantesSum,
            'csrf'             => $csrf,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /periodos/ver/ID — detalle con pasantes del período
    // ─────────────────────────────────────────────────────────────────
    public function ver(int $id = 0): void
    {
        if ($id <= 0) {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Datos del período
        $this->db->query("SELECT * FROM periodos_academicos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        $periodo = $this->db->single();

        if (!$periodo) {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Pasantes asignados al período con sus datos y conteo de asistencias
        $this->db->query("
            SELECT
                u.id,
                u.correo,
                u.estado                                        AS estado_usuario,
                dp.nombres,
                dp.apellidos,
                u.cedula,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar')   AS estado_pasantia,
                COALESCE((
                    -- [MEJORA-01 FIX] Filtrar asistencias dentro del rango del período actual.
                    -- La versión anterior acumulaba TODAS las asistencias del pasante en cualquier
                    -- período (AND a.fecha <= CURDATE()), sumando horas de cohortes pasadas.
                    SELECT COUNT(*) * 8 FROM asistencias a2
                    WHERE a2.pasante_id = u.id
                      AND a2.estado != 'Anulado'
                      AND a2.fecha BETWEEN pa.fecha_inicio AND LEAST(pa.fecha_fin, CURDATE())
                ), 0)                                                   AS horas_acumuladas,
                COALESCE(dpa.horas_meta, 0)                    AS horas_meta,
                dpa.fecha_inicio_pasantia,
                dpa.fecha_fin_estimada,
                dept.nombre                                     AS departamento,
                inst.nombre                                     AS institucion,
                COALESCE(
                    NULLIF(dpa.institucion_id, 0),
                    CASE WHEN dpa.institucion_procedencia REGEXP '^[0-9]+$'
                         THEN CAST(dpa.institucion_procedencia AS UNSIGNED)
                         ELSE NULL END
                )                                               AS institucion_id,
                -- Días hábiles (L-V) transcurridos desde inicio hasta hoy/fin
                CASE WHEN dpa.fecha_inicio_pasantia IS NOT NULL THEN
                    GREATEST(0,
                        DATEDIFF(LEAST(COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin), CURDATE()), dpa.fecha_inicio_pasantia) + 1
                        - FLOOR((DATEDIFF(LEAST(COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin), CURDATE()), dpa.fecha_inicio_pasantia) + DAYOFWEEK(dpa.fecha_inicio_pasantia)) / 7)
                        - FLOOR((DATEDIFF(LEAST(COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin), CURDATE()), dpa.fecha_inicio_pasantia) + DAYOFWEEK(dpa.fecha_inicio_pasantia) - 1) / 7)
                    )
                ELSE 0 END                                      AS dias_meta,
                -- Días con asistencia física (solo informativo)
                COUNT(CASE
                    WHEN a.estado IN ('Presente','Tardanza','Retardo')
                     AND a.fecha <= CURDATE() THEN 1
                END)                                            AS dias_presentes,
                -- Días justificados (feriados, solo informativo)
                COUNT(CASE
                    WHEN a.estado = 'Justificado'
                     AND a.fecha <= CURDATE() THEN 1
                END)                                            AS dias_justificados,
                -- Días ausentes (solo informativo)
                COUNT(CASE
                    WHEN a.estado = 'Ausente'
                     AND a.fecha <= CURDATE() THEN 1
                END)                                            AS dias_ausentes,
                -- Total registros para tasa de asistencia
                COUNT(CASE WHEN a.fecha <= CURDATE() THEN 1 END) AS total_dias_registrados
            FROM usuarios u
            LEFT JOIN datos_personales   dp   ON dp.usuario_id   = u.id
            LEFT JOIN datos_pasante      dpa  ON dpa.usuario_id  = u.id
            LEFT JOIN periodos_academicos pa  ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos      dept ON dept.id = dpa.departamento_asignado_id
            LEFT JOIN instituciones      inst ON inst.id = COALESCE(
                                                 NULLIF(dpa.institucion_id, 0),
                                                 CASE WHEN dpa.institucion_procedencia REGEXP '^[0-9]+$'
                                                      THEN CAST(dpa.institucion_procedencia AS UNSIGNED)
                                                      ELSE NULL END
                                             )
            LEFT JOIN asistencias        a    ON a.pasante_id    = u.id
            WHERE dpa.periodo_id = :pid AND u.rol_id = 3
            GROUP BY u.id
            ORDER BY dp.apellidos ASC, dp.nombres ASC
        ");
        $this->db->bind(':pid', $id);
        $pasantes = $this->db->resultSet();

        // Todas las instituciones disponibles (para el modal "Ajustar por Institución")
        $this->db->query("SELECT id, nombre FROM instituciones ORDER BY nombre");
        $institucionesPeriodo = $this->db->resultSet();

        // KPIs del período
        $kpiPasantes   = count($pasantes);
        $kpiActivos    = 0;
        $kpiFinalizados = 0;
        $sumaAsistencia = 0;

        foreach ($pasantes as $p) {
            if ($p->estado_pasantia === 'Activo')     $kpiActivos++;
            if ($p->estado_pasantia === 'Finalizado') $kpiFinalizados++;
            if ($p->total_dias_registrados > 0) {
                $sumaAsistencia += round(($p->dias_presentes / $p->total_dias_registrados) * 100);
            }
        }
        $promAsistencia = $kpiPasantes > 0
            ? round($sumaAsistencia / $kpiPasantes)
            : 0;

        // Pasantes sin período asignado (para el modal de asignación)
        $this->db->query("
            SELECT u.id, dp.nombres, dp.apellidos, u.cedula
            FROM usuarios u
            LEFT JOIN datos_personales dp  ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            WHERE u.rol_id = 3 AND u.estado = 'activo'
              AND (dpa.periodo_id IS NULL OR dpa.periodo_id = 0)
            ORDER BY dp.apellidos ASC
        ");
        $pasantesSinPeriodo = $this->db->resultSet();

        // Agrupar pasantes por departamento para la vista en acordeón
        $pasantesPorDepto = [];
        foreach ($pasantes as $p) {
            $depto = $p->departamento ?? 'Sin Departamento';
            $pasantesPorDepto[$depto][] = $p;
        }
        ksort($pasantesPorDepto);

        $csrf = Session::generateCsrfToken();

        $this->view('periodos/ver', [
            'periodo'              => $periodo,
            'pasantesPorDepto'     => $pasantesPorDepto,
            'kpiPasantes'          => $kpiPasantes,
            'kpiActivos'           => $kpiActivos,
            'kpiFinalizados'       => $kpiFinalizados,
            'promAsistencia'       => $promAsistencia,
            'pasantesSinPeriodo'   => $pasantesSinPeriodo,
            'csrf'                 => $csrf,
            'institucionesPeriodo' => $institucionesPeriodo,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /periodos/crear
    // ─────────────────────────────────────────────────────────────────
    public function crear(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido. Recarga la página.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin    = trim($_POST['fecha_fin']    ?? '');
        $tipo        = in_array($_POST['tipo'] ?? '', ['Regular', 'Corto']) ? $_POST['tipo'] : 'Regular';

        if (empty($nombre) || empty($fechaInicio) || empty($fechaFin)) {
            Session::setFlash('error', 'Nombre, fecha de inicio y fecha fin son obligatorios.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if ($fechaFin <= $fechaInicio) {
            Session::setFlash('error', 'La fecha de fin debe ser posterior a la de inicio.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Estado inicial: Planificado o Activo (si el admin quiere activarlo de inmediato)
        $estadoInicial = ($_POST['estado_inicial'] ?? '') === 'Activo' ? 'Activo' : 'Planificado';

        // Validar unicidad: no puede haber otro período del mismo tipo en estado Planificado o Activo
        $this->db->query("
            SELECT COUNT(*) AS total FROM periodos_academicos
            WHERE tipo = :tipo AND estado IN ('Planificado','Activo')
        ");
        $this->db->bind(':tipo', $tipo);
        $existe = $this->db->single();
        if ((int)($existe->total ?? 0) > 0) {
            Session::setFlash('error', "Ya existe un período de tipo «{$tipo}» activo o planificado. Ciérralo antes de crear uno nuevo.");
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // [FIX-N-12] Evitar solapamiento de fechas con OTRO período del mismo tipo,
        // incluyendo los ya Cerrados: dos períodos del mismo tipo cubriendo las mismas
        // fechas genera ambigüedad en reportes/históricos que agrupan por período.
        $this->db->query("
            SELECT COUNT(*) AS total FROM periodos_academicos
            WHERE tipo = :tipo
              AND fecha_inicio <= :fecha_fin AND fecha_fin >= :fecha_inicio
        ");
        $this->db->bind(':tipo',         $tipo);
        $this->db->bind(':fecha_fin',    $fechaFin);
        $this->db->bind(':fecha_inicio', $fechaInicio);
        $solapa = $this->db->single();
        if ((int)($solapa->total ?? 0) > 0) {
            Session::setFlash('error', "Las fechas ingresadas se solapan con otro período de tipo «{$tipo}» ya existente.");
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Si se activa de inmediato, cerrar cualquier activo del mismo tipo primero
        if ($estadoInicial === 'Activo') {
            $this->db->query("UPDATE periodos_academicos SET estado = 'Cerrado' WHERE tipo = :tipo AND estado = 'Activo'");
            $this->db->bind(':tipo', $tipo);
            $this->db->execute();
        }

        $this->db->query("
            INSERT INTO periodos_academicos
                (nombre, tipo, descripcion, fecha_inicio, fecha_fin, estado)
            VALUES
                (:nombre, :tipo, :descripcion, :fecha_inicio, :fecha_fin, :estado)
        ");
        $this->db->bind(':nombre',       $nombre);
        $this->db->bind(':tipo',         $tipo);
        $this->db->bind(':descripcion',  $descripcion ?: null);
        $this->db->bind(':fecha_inicio', $fechaInicio);
        $this->db->bind(':fecha_fin',    $fechaFin);
        $this->db->bind(':estado',       $estadoInicial);

        if ($this->db->execute()) {
            $msg = $estadoInicial === 'Activo'
                ? 'Período académico creado y activado correctamente.'
                : 'Período académico creado en estado Planificado.';
            Session::setFlash('success', $msg);
        } else {
            Session::setFlash('error', 'Error al crear el período. Intenta de nuevo.');
        }

        header('Location: ' . URLROOT . '/periodos');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /periodos/editar
    // ─────────────────────────────────────────────────────────────────
    // ─────────────────────────────────────────────────────────────────
    // GET /periodos/impactoCambio  (AJAX → JSON)
    // Calcula el impacto de cambiar fechas de un período SIN guardar
    // ─────────────────────────────────────────────────────────────────
    public function impactoCambio(): void
    {
        header('Content-Type: application/json');
        $periodoId  = (int)($_GET['periodo_id']  ?? 0);
        $fechaInicio = trim($_GET['fecha_inicio'] ?? '');
        $fechaFin    = trim($_GET['fecha_fin']    ?? '');

        if (!$periodoId || !$fechaInicio || !$fechaFin || $fechaFin <= $fechaInicio) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            exit;
        }

        // Período actual
        $this->db->query("SELECT nombre, fecha_inicio, fecha_fin FROM periodos_academicos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $periodoId);
        $periodo = $this->db->single();
        if (!$periodo) { echo json_encode(['success' => false, 'message' => 'Período no encontrado.']); exit; }

        // Calcular horas_meta nuevas: contar días L-V desde el día siguiente al inicio
        $cur = new DateTime($fechaInicio); $cur->modify('+1 day');
        $end = new DateTime($fechaFin); $lv = 0;
        while ($cur <= $end) { if ((int)$cur->format('N') < 6) $lv++; $cur->modify('+1 day'); }
        $nuevasHoras = max(160, $lv * 8);

        // Pasantes afectados con su horas_meta actual
        $this->db->query("
            SELECT COUNT(*) AS total,
                   ROUND(AVG(COALESCE(NULLIF(dpa.horas_meta,0), 1440))) AS horas_actuales
            FROM datos_pasante dpa
            WHERE dpa.periodo_id = :pid
        ");
        $this->db->bind(':pid', $periodoId);
        $impacto = $this->db->single();

        $diasActuales = (int)(($impacto->horas_actuales ?? 1440) / 8);
        $diasNuevos   = $nuevasHoras / 8;
        $diferencia   = $diasNuevos - $diasActuales;
        $signo        = $diferencia > 0 ? '+' : '';

        echo json_encode([
            'success'         => true,
            'periodo_nombre'  => $periodo->nombre,
            'fecha_ini_ant'   => date('d/m/Y', strtotime($periodo->fecha_inicio)),
            'fecha_fin_ant'   => date('d/m/Y', strtotime($periodo->fecha_fin)),
            'fecha_ini_nueva' => date('d/m/Y', strtotime($fechaInicio)),
            'fecha_fin_nueva' => date('d/m/Y', strtotime($fechaFin)),
            'pasantes_afect'  => (int)($impacto->total ?? 0),
            'horas_actuales'  => (int)($impacto->horas_actuales ?? 1440),
            'horas_nuevas'    => $nuevasHoras,
            'dias_actuales'   => $diasActuales,
            'dias_nuevos'     => $diasNuevos,
            'diferencia_dias' => ($signo . $diferencia),
        ]);
        exit;
    }

    public function editar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $id          = (int)($_POST['periodo_id'] ?? 0);
        $nombre      = trim($_POST['nombre']       ?? '');
        $descripcion = trim($_POST['descripcion']  ?? '');
        $fechaInicio = trim($_POST['fecha_inicio']  ?? '');
        $fechaFin    = trim($_POST['fecha_fin']     ?? '');

        if ($id <= 0 || empty($nombre) || empty($fechaInicio) || empty($fechaFin)) {
            Session::setFlash('error', 'Datos incompletos para editar el período.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Solo editar si NO está Cerrado
        $this->db->query("SELECT tipo, estado FROM periodos_academicos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        $check = $this->db->single();

        if (!$check || strtolower($check->estado) === 'cerrado') {
            Session::setFlash('error', 'No se puede editar un período cerrado.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if ($fechaFin <= $fechaInicio) {
            Session::setFlash('error', 'La fecha de fin debe ser posterior a la de inicio.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // [FIX-N-12] Evitar solapamiento con OTRO período del mismo tipo (incluye Cerrados)
        $this->db->query("
            SELECT COUNT(*) AS total FROM periodos_academicos
            WHERE tipo = :tipo AND id != :id
              AND fecha_inicio <= :fecha_fin AND fecha_fin >= :fecha_inicio
        ");
        $this->db->bind(':tipo',         $check->tipo);
        $this->db->bind(':id',           $id);
        $this->db->bind(':fecha_fin',    $fechaFin);
        $this->db->bind(':fecha_inicio', $fechaInicio);
        $solapa = $this->db->single();
        if ((int)($solapa->total ?? 0) > 0) {
            Session::setFlash('error', "Las fechas ingresadas se solapan con otro período de tipo «{$check->tipo}» ya existente.");
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $this->db->query("
            UPDATE periodos_academicos
            SET nombre       = :nombre,
                descripcion  = :descripcion,
                fecha_inicio = :fecha_inicio,
                fecha_fin    = :fecha_fin
            WHERE id = :id AND estado != 'Cerrado'
        ");
        $this->db->bind(':nombre',       $nombre);
        $this->db->bind(':descripcion',  $descripcion ?: null);
        $this->db->bind(':fecha_inicio', $fechaInicio);
        $this->db->bind(':fecha_fin',    $fechaFin);
        $this->db->bind(':id',           $id);

        if (!$this->db->execute()) {
            Session::setFlash('error', 'Error al actualizar el período.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Recalcular horas_meta: total días L-V × 8h (feriados cuentan como Justificado)
        $cur = new DateTime($fechaInicio); $end = new DateTime($fechaFin); $lv = 0;
        while ($cur <= $end) { if ((int)$cur->format('N') < 6) $lv++; $cur->modify('+1 day'); }
        $nuevasHoras = max(160, $lv * 8);

        // ── Cascada selectiva ─────────────────────────────────────────────
        // Solo se actualiza horas_meta en pasantes SIN fechas individuales
        // (fecha_inicio_pasantia IS NULL): ellos heredan las fechas del período
        // vía COALESCE y su meta debe reflejar el período actualizado.
        // Pasantes con fechas individuales explícitas (caso instituciones con
        // fechas distintas) NO se tocan — su horas_meta fue calculada sobre su
        // propio rango y debe preservarse.
        $this->db->query("
            SELECT usuario_id FROM datos_pasante
            WHERE periodo_id = :pid AND fecha_inicio_pasantia IS NULL
        ");
        $this->db->bind(':pid', $id);
        $pasantesAfect = $this->db->resultSet();

        $actualizados = 0;
        if (!empty($pasantesAfect)) {
            $this->db->query("
                UPDATE datos_pasante SET horas_meta = :horas
                WHERE periodo_id = :pid AND fecha_inicio_pasantia IS NULL
            ");
            $this->db->bind(':horas', $nuevasHoras);
            $this->db->bind(':pid',   $id);
            $this->db->execute();
            $actualizados = $this->db->rowCount();
        }

        // Notificar a cada pasante afectado por la cascada
        if ($actualizados > 0) {
            require_once APPROOT . '/models/NotificationModel.php';
            $notifModel = new NotificationModel($this->db);
            foreach ($pasantesAfect as $p) {
                $notifModel->create(
                    (int)$p->usuario_id,
                    'periodo_actualizado',
                    'Período académico actualizado',
                    "Las fechas de tu período han sido modificadas. Tu nueva meta de horas es <strong>{$nuevasHoras}h</strong>.",
                    URLROOT . '/pasante/dashboard'
                );
            }
        }

        $msg = "Período actualizado correctamente.";
        if ($actualizados > 0) {
            $msg .= " Se recalculó la meta de horas a <strong>{$nuevasHoras}h</strong> para {$actualizados} pasante(s) sin fechas individuales.";
        }
        Session::setFlash('success', $msg);
        header('Location: ' . URLROOT . '/periodos');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /periodos/cerrar — cierra el período y desactiva usuarios
    // ─────────────────────────────────────────────────────────────────
    public function cerrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $id = (int)($_POST['periodo_id'] ?? 0);

        if ($id <= 0) {
            Session::setFlash('error', 'ID de período inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Verificar que existe y no está ya cerrado
        $this->db->query("SELECT id, nombre, estado FROM periodos_academicos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        $periodo = $this->db->single();

        if (!$periodo || $periodo->estado === 'Cerrado') {
            Session::setFlash('error', 'El período ya está cerrado o no existe.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $forzar = ($_POST['forzar'] ?? '') === '1';

        // Verificar si hay pasantes cuya fecha_fin_estimada individual aún no llegó
        $this->db->query("
            SELECT
                COUNT(*) AS total_activos,
                SUM(CASE WHEN COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin) > CURDATE() THEN 1 ELSE 0 END) AS pendientes
            FROM datos_pasante dpa
            JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE dpa.periodo_id = :pid AND dpa.estado_pasantia = 'Activo'
        ");
        $this->db->bind(':pid', $id);
        $check        = $this->db->single();
        $totalActivos = (int)($check->total_activos ?? 0);
        $pendientes   = (int)($check->pendientes   ?? 0);
        $listos       = $totalActivos - $pendientes;

        // Solo bloquear si NINGÚN pasante ha llegado a su fecha (nada que hacer hoy)
        if ($listos === 0 && !$forzar) {
            Session::setFlash('error', "El período «{$periodo->nombre}» tiene {$pendientes} pasante(s) cuya fecha de culminación individual aún no ha llegado. No hay pasantes listos para finalizar hoy.");
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // ── 0. Auto-fill final: sellar días laborables abiertos hasta fecha_fin ──
        require_once APPROOT . '/helpers/AutoFillService.php';
        $feriados = AutoFillService::cargarFeriados($this->db);
        $ayer = date('Y-m-d', strtotime('-1 day'));

        $this->db->query("
            SELECT dpa.usuario_id AS pasante_id,
                   COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio,
                   COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin
            FROM   datos_pasante dpa
            JOIN   periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE  dpa.periodo_id = :pid
              AND  COALESCE(dpa.estado_pasantia, 'Sin Asignar') = 'Activo'
              AND  COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) IS NOT NULL
        ");
        $this->db->bind(':pid', $id);
        $pasantesParaFill = $this->db->resultSet();

        $diasRellenos = 0;
        foreach ($pasantesParaFill as $pf) {
            // Rellenar hasta el menor de: fecha_fin del pasante o ayer
            $limite = ($pf->fecha_fin < $ayer) ? $pf->fecha_fin : $ayer;
            if ($pf->fecha_inicio > $limite) continue;

            $this->db->query("
                SELECT fecha FROM asistencias
                WHERE  pasante_id = :pid AND fecha BETWEEN :fi AND :ff
            ");
            $this->db->bind(':pid', $pf->pasante_id);
            $this->db->bind(':fi',  $pf->fecha_inicio);
            $this->db->bind(':ff',  $limite);
            $existentes = array_flip(array_column($this->db->resultSet() ?: [], 'fecha'));

            // Días laborables normales → Presente
            $diasLaborables = AutoFillService::generarDiasLaborables($pf->fecha_inicio, $limite, $feriados);
            foreach ($diasLaborables as $dia) {
                if (isset($existentes[$dia])) continue;
                $this->db->query("
                    INSERT IGNORE INTO asistencias
                        (pasante_id, fecha, hora_registro, hora_entrada, hora_salida,
                         horas_calculadas, estado, metodo, es_auto_fill, observacion, created_at)
                    VALUES
                        (:pid, :fecha, '23:59:00', '08:00:00', '16:00:00',
                         8.00, 'Presente', 'AutoFill', 1,
                         'Cierre de período — completado automáticamente', :created)
                ");
                $this->db->bind(':pid',     $pf->pasante_id);
                $this->db->bind(':fecha',   $dia);
                $this->db->bind(':created', $dia . ' 23:59:00');
                $this->db->execute();
                $diasRellenos++;
            }

            // Feriados no laborables en L-V → Justificado con nombre del feriado
            foreach ($feriados as $fFecha => $fNombre) {
                if ($fFecha < $pf->fecha_inicio || $fFecha > $limite) continue;
                if ((int)(new DateTime($fFecha))->format('N') >= 6) continue; // solo L-V
                if (isset($existentes[$fFecha])) continue;
                $this->db->query("
                    INSERT IGNORE INTO asistencias
                        (pasante_id, fecha, hora_registro, hora_entrada, hora_salida,
                         horas_calculadas, estado, metodo, es_auto_fill,
                         motivo_justificacion, observacion, created_at)
                    VALUES
                        (:pid, :fecha, '23:59:00', '08:00:00', '16:00:00',
                         8.00, 'Justificado', 'AutoFill', 1,
                         :motivo, 'Feriado — cierre de período', :created)
                ");
                $this->db->bind(':pid',     $pf->pasante_id);
                $this->db->bind(':fecha',   $fFecha);
                $this->db->bind(':motivo',  $fNombre);
                $this->db->bind(':created', $fFecha . ' 23:59:00');
                $this->db->execute();
                $diasRellenos++;
            }
        }

        // Pre-obtener datos de los pasantes que serán finalizados ahora (para notificaciones)
        if ($forzar) {
            $this->db->query("
                SELECT dp.usuario_id, dp.horas_acumuladas, dp.horas_meta,
                       CONCAT(dpn.nombres,' ',dpn.apellidos) AS nombre_completo
                FROM datos_pasante dp
                LEFT JOIN datos_personales dpn ON dpn.usuario_id = dp.usuario_id
                WHERE dp.periodo_id = :pid AND dp.estado_pasantia = 'Activo'
            ");
        } else {
            $this->db->query("
                SELECT dp.usuario_id, dp.horas_acumuladas, dp.horas_meta,
                       CONCAT(dpn.nombres,' ',dpn.apellidos) AS nombre_completo
                FROM datos_pasante dp
                JOIN periodos_academicos pa ON pa.id = dp.periodo_id
                LEFT JOIN datos_personales dpn ON dpn.usuario_id = dp.usuario_id
                WHERE dp.periodo_id = :pid AND dp.estado_pasantia = 'Activo'
                  AND COALESCE(dp.fecha_fin_estimada, pa.fecha_fin) <= CURDATE()
            ");
        }
        $this->db->bind(':pid', $id);
        $pasantesCerrados = $this->db->resultSet();

        // 1. Finalizar solo los pasantes cuya fecha individual ya llegó (o todos si forzar)
        if ($forzar) {
            $this->db->query("
                UPDATE datos_pasante SET estado_pasantia = 'Finalizado'
                WHERE periodo_id = :pid AND estado_pasantia = 'Activo'
            ");
            $this->db->bind(':pid', $id);
            $this->db->execute();
        } else {
            $this->db->query("
                UPDATE datos_pasante dpa
                JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
                SET dpa.estado_pasantia = 'Finalizado'
                WHERE dpa.periodo_id = :pid
                  AND dpa.estado_pasantia = 'Activo'
                  AND COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin) <= CURDATE()
            ");
            $this->db->bind(':pid', $id);
            $this->db->execute();
        }

        // 2. Deshabilitar cuentas de pasantes recién finalizados sin otra pasantía activa
        $this->db->query("
            UPDATE usuarios u
            JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                AND dpa.periodo_id = :pid AND dpa.estado_pasantia = 'Finalizado'
            LEFT JOIN datos_pasante dp_act ON dp_act.usuario_id = u.id
                AND dp_act.estado_pasantia = 'Activo'
            SET u.estado = 'inactivo'
            WHERE u.rol_id = 3 AND dp_act.usuario_id IS NULL
        ");
        $this->db->bind(':pid', $id);
        $this->db->execute();

        // 3. Cerrar el período solo si ya no quedan pasantes activos
        $this->db->query("
            SELECT COUNT(*) AS restantes FROM datos_pasante
            WHERE periodo_id = :pid AND estado_pasantia = 'Activo'
        ");
        $this->db->bind(':pid', $id);
        $restantes = (int)($this->db->single()->restantes ?? 0);
        if ($restantes === 0) {
            $this->db->query("UPDATE periodos_academicos SET estado = 'Cerrado' WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();
        }

        // 4. Enviar notificaciones a los pasantes recién finalizados
        require_once APPROOT . '/models/NotificationModel.php';
        $notifModel = new NotificationModel($this->db);

        foreach ($pasantesCerrados as $p) {
            $hAcum = (int)($p->horas_acumuladas ?? 0);
            $hMeta = max(1, (int)($p->horas_meta ?? 1440));
            $pct   = min(100, round($hAcum / $hMeta * 100));
            $completado = $hAcum >= $hMeta;

            if ($completado) {
                $notifModel->createWithRef(
                    (int)$p->usuario_id,
                    'pasantia_completada',
                    '🎓 ¡Felicidades! Pasantía completada',
                    "Has completado exitosamente tu pasantía del período «{$periodo->nombre}» con {$hAcum}h acumuladas ({$pct}% de la meta). ¡Excelente desempeño!",
                    URLROOT . '/pasante/dashboard',
                    $id
                );
            } else {
                $notifModel->createWithRef(
                    (int)$p->usuario_id,
                    'periodo_cerrado',
                    'Período académico cerrado',
                    "El período «{$periodo->nombre}» ha sido cerrado. Acumulaste {$hAcum}h de {$hMeta}h ({$pct}%). Tu cuenta ha sido desactivada.",
                    URLROOT . '/pasante/dashboard',
                    $id
                );
            }
        }

        $msgFill = $diasRellenos > 0 ? " Se completaron {$diasRellenos} día(s) faltante(s) antes del cierre." : '';
        if ($restantes > 0) {
            Session::setFlash('success', "Se finalizaron " . count($pasantesCerrados) . " pasante(s) cuya fecha individual ya llegó.{$msgFill} Quedan {$restantes} pasante(s) con fecha futura — el período continúa Activo.");
        } else {
            // [OPT-03 FIX] Período cerrado completamente: limpiar la sesión de filtro
            // para que las vistas de Pasantes y Asignaciones no sigan mostrando
            // datos del período recién cerrado en la próxima carga.
            Session::unset('selected_periodo_id');
            Session::setFlash('success', "Período «{$periodo->nombre}» cerrado correctamente.{$msgFill} Se notificó a " . count($pasantesCerrados) . " pasante(s).");
        }
        header('Location: ' . URLROOT . '/periodos');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /periodos/asignarPasante — asigna un pasante al período
    // ─────────────────────────────────────────────────────────────────
    public function asignarPasante(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $periodoId = (int)($_POST['periodo_id'] ?? 0);
        $usuarioId = (int)($_POST['usuario_id'] ?? 0);

        if ($periodoId <= 0 || $usuarioId <= 0) {
            Session::setFlash('error', 'Datos inválidos para la asignación.');
            header('Location: ' . URLROOT . '/periodos/ver/' . $periodoId);
            exit;
        }

        // Verificar que el período existe y está Activo o Planificado
        $this->db->query("SELECT estado FROM periodos_academicos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $periodoId);
        $periodo = $this->db->single();

        if (!$periodo || strtolower($periodo->estado) === 'cerrado') {
            Session::setFlash('error', 'No se puede asignar pasantes a un período cerrado.');
            header('Location: ' . URLROOT . '/periodos/ver/' . $periodoId);
            exit;
        }

        // Calcular horas_meta: total días L-V × 8h (feriados cuentan como Justificado)
        $this->db->query("SELECT fecha_inicio, fecha_fin FROM periodos_academicos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $periodoId);
        $periodoFechas = $this->db->single();

        if ($periodoFechas && $periodoFechas->fecha_inicio && $periodoFechas->fecha_fin) {
            $cur = new DateTime($periodoFechas->fecha_inicio);
            $end = new DateTime($periodoFechas->fecha_fin);
            $diasHabiles = 0;
            while ($cur <= $end) {
                if ((int)$cur->format('N') < 6) $diasHabiles++;
                $cur->modify('+1 day');
            }
            $horasMeta = max(160, $diasHabiles * 8);
        } else {
            $horasMeta = 1440;
        }

        // [BUG-04 FIX] Manejo de asignación respetando historial multi-período:
        // 1. Si ya tiene registro en este mismo período -> actualizamos horas_meta
        // 2. Si tiene registro sin período (periodo_id IS NULL) -> lo asociamos a este período
        // 3. Si solo tiene registros de períodos previos -> insertamos nuevo registro para este período
        $this->db->query("SELECT id FROM datos_pasante WHERE usuario_id = :uid AND periodo_id = :pid LIMIT 1");
        $this->db->bind(':uid', $usuarioId);
        $this->db->bind(':pid', $periodoId);
        $regPeriodo = $this->db->single();

        $this->db->query("SELECT id FROM datos_pasante WHERE usuario_id = :uid AND periodo_id IS NULL LIMIT 1");
        $this->db->bind(':uid', $usuarioId);
        $regSinPeriodo = $this->db->single();

        $ok = false;
        if ($regPeriodo) {
            $this->db->query("UPDATE datos_pasante SET horas_meta = :horas_meta WHERE id = :id");
            $this->db->bind(':horas_meta', $horasMeta);
            $this->db->bind(':id', $regPeriodo->id);
            $ok = $this->db->execute();
        } elseif ($regSinPeriodo) {
            $this->db->query("UPDATE datos_pasante SET periodo_id = :pid, horas_meta = :horas_meta WHERE id = :id");
            $this->db->bind(':pid', $periodoId);
            $this->db->bind(':horas_meta', $horasMeta);
            $this->db->bind(':id', $regSinPeriodo->id);
            $ok = $this->db->execute();
        } else {
            // Traer datos institucionales previos si existen
            $this->db->query("SELECT institucion_id, institucion_procedencia FROM datos_pasante WHERE usuario_id = :uid ORDER BY id DESC LIMIT 1");
            $this->db->bind(':uid', $usuarioId);
            $prev = $this->db->single();

            $this->db->query("
                INSERT INTO datos_pasante (usuario_id, periodo_id, horas_meta, estado_pasantia, institucion_id, institucion_procedencia)
                VALUES (:uid, :pid, :horas_meta, 'Pendiente', :inst_id, :inst_proc)
                ON DUPLICATE KEY UPDATE horas_meta = VALUES(horas_meta)
            ");
            $this->db->bind(':uid',       $usuarioId);
            $this->db->bind(':pid',       $periodoId);
            $this->db->bind(':horas_meta', $horasMeta);
            $this->db->bind(':inst_id',   $prev->institucion_id ?? null);
            $this->db->bind(':inst_proc', $prev->institucion_procedencia ?? 'Sin Institución');
            $ok = $this->db->execute();
        }

        // Reactivar usuario si venía inactivo de un período previo cerrado
        $this->db->query("UPDATE usuarios SET estado = 'activo' WHERE id = :uid AND estado != 'activo'");
        $this->db->bind(':uid', $usuarioId);
        $this->db->execute();

        if ($ok) {
            Session::setFlash('success', "Pasante asignado al período correctamente ({$horasMeta} hrs calculadas).");
        } else {
            Session::setFlash('error', 'Error al asignar el pasante.');
        }

        header('Location: ' . URLROOT . '/periodos/ver/' . $periodoId);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /periodos/cartaCulminacion/PASANTE_ID — PDF carta formal
    // ─────────────────────────────────────────────────────────────────
    public function cartaCulminacion(int $pasanteId = 0): void
    {
        if ($pasanteId <= 0) {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Datos del pasante
        $this->db->query("
            SELECT
                u.id,
                dp.nombres,
                dp.apellidos,
                u.cedula,
                dept.nombre                         AS departamento,
                inst.nombre                         AS institucion,
                dpa.fecha_inicio_pasantia           AS fecha_inicio,
                dpa.fecha_fin_estimada              AS fecha_fin,
                dpa.periodo_id,
                COALESCE(dpa.estado_pasantia, '')   AS estado_pasantia
            FROM usuarios u
            LEFT JOIN datos_personales dp  ON dp.usuario_id  = u.id
            LEFT JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            LEFT JOIN departamentos    dept ON dept.id = dpa.departamento_asignado_id
            LEFT JOIN instituciones    inst ON inst.id = dpa.institucion_id
            WHERE u.id = :uid AND u.rol_id = 3
            LIMIT 1
        ");
        $this->db->bind(':uid', $pasanteId);
        $pasante = $this->db->single();

        if (!$pasante) {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Datos del período asociado
        $periodo = null;
        if (!empty($pasante->periodo_id)) {
            $this->db->query("SELECT * FROM periodos_academicos WHERE id = :pid LIMIT 1");
            $this->db->bind(':pid', $pasante->periodo_id);
            $periodo = $this->db->single();
        }

        // Total de días presentes y horas reales acumuladas
        $this->db->query("
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(COALESCE(NULLIF(horas_calculadas,0),8)),0) AS total_horas
            FROM asistencias
            WHERE pasante_id = :uid AND estado IN ('Presente','Tardanza')
        ");
        $this->db->bind(':uid', $pasanteId);
        $row = $this->db->single();
        $totalPresentes       = (int)($row->total       ?? 0);
        $totalHorasCalculadas = (int)($row->total_horas ?? 0);

        require_once '../app/lib/PdfGenerator.php';
        $pdf = new PdfGenerator();

        ob_start();
        include APPROOT . '/views/periodos/pdf_carta_culminacion.php';
        $html = ob_get_clean();

        $pdf->renderDomPdf(
            $html,
            'Carta_Culminacion_' . ($pasante->cedula ?? $pasanteId),
            true
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /periodos/desactivarPasante/ID — Finaliza a un pasante
    // ─────────────────────────────────────────────────────────────────
    public function desactivarPasante(int $pasanteId = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $periodoId = (int)($_POST['periodo_id'] ?? 0);

        if ($pasanteId <= 0) {
            Session::setFlash('error', 'ID de pasante inválido.');
            header('Location: ' . URLROOT . '/periodos/ver/' . $periodoId);
            exit;
        }

        // Marcar finalizado y desactivar su usuario
        $this->db->query("UPDATE datos_pasante SET estado_pasantia = 'Finalizado' WHERE usuario_id = :uid");
        $this->db->bind(':uid', $pasanteId);
        $this->db->execute();

        $this->db->query("UPDATE usuarios SET estado = 'inactivo' WHERE id = :uid AND rol_id = 3");
        $this->db->bind(':uid', $pasanteId);
        $this->db->execute();

        Session::setFlash('success', 'El pasante ha sido desactivado y su pasantía marcada como finalizada.');
        header('Location: ' . URLROOT . '/periodos/ver/' . $periodoId);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /periodos/informeGeneral/ID — PDF reporte global del periodo
    // ─────────────────────────────────────────────────────────────────
    public function informeGeneral(int $id = 0): void
    {
        if ($id <= 0) {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Obtener datos del período
        $this->db->query("SELECT * FROM periodos WHERE id = :id");
        $this->db->bind(':id', $id);
        $periodo = $this->db->single();

        if (!$periodo) {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Obtener pasantes
        $this->db->query("
            SELECT
                dp.nombres,
                dp.apellidos,
                u.cedula,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar')   AS estado_pasantia,
                COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas,
                COALESCE(dpa.horas_meta, 0)                    AS horas_meta,
                dept.nombre                                     AS departamento,
                COUNT(CASE WHEN a.estado IN ('Presente','Tardanza','Retardo') AND a.fecha <= CURDATE() THEN 1 END) AS dias_presentes
            FROM usuarios u
            LEFT JOIN datos_personales    dp   ON dp.usuario_id   = u.id
            LEFT JOIN datos_pasante       dpa  ON dpa.usuario_id  = u.id
            LEFT JOIN periodos_academicos pa   ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos       dept ON dept.id = dpa.departamento_asignado_id
            LEFT JOIN asistencias         a    ON a.pasante_id    = u.id
            WHERE dpa.periodo_id = :pid AND u.rol_id = 3
            GROUP BY u.id
            ORDER BY dept.nombre ASC, dp.apellidos ASC
        ");
        $this->db->bind(':pid', $id);
        $pasantes = $this->db->resultSet();

        require_once '../app/lib/PdfGenerator.php';
        $pdf = new PdfGenerator();

        $html = '<h1 style="text-align:center; color:#1e3a8a;">Informe de Cohorte: ' . htmlspecialchars($periodo->nombre) . '</h1>';
        $html .= '<p style="text-align:center;">Estado: ' . $periodo->estado . ' | Fechas: ' . date('d/m/Y', strtotime($periodo->fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($periodo->fecha_fin)) . '</p>';
        
        $html .= '<table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: sans-serif; font-size: 12px;">';
        $html .= '<thead style="background-color: #f1f5f9;"><tr>';
        $html .= '<th>Cédula</th><th>Apellidos y Nombres</th><th>Departamento</th><th>Estado</th><th>Horas</th><th>Asistencias</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($pasantes as $p) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($p->cedula ?? '') . '</td>';
            $html .= '<td>' . htmlspecialchars(($p->apellidos ?? '') . ', ' . ($p->nombres ?? '')) . '</td>';
            $html .= '<td>' . htmlspecialchars($p->departamento ?? 'Sin Asignar') . '</td>';
            $html .= '<td>' . htmlspecialchars($p->estado_pasantia) . '</td>';
            $html .= '<td>' . (int)$p->horas_acumuladas . ' / ' . (int)$p->horas_meta . '</td>';
            $html .= '<td>' . (int)$p->dias_presentes . ' días</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $pdf->renderDomPdf($html, 'Informe_Cohorte_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $periodo->nombre), true);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /periodos/reporteAsistencia/PASANTE_ID?desde=Y-m-d&hasta=Y-m-d
    // ─────────────────────────────────────────────────────────────────
    public function reporteAsistencia(int $pasanteId = 0): void
    {
        if ($pasanteId <= 0) {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $this->db->query("
            SELECT
                dp.nombres, dp.apellidos, u.cedula,
                d.nombre  AS departamento,
                inst.nombre AS institucion,
                dpa.horas_meta,
                COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas,
                dpa.estado_pasantia,
                pa.nombre AS periodo_nombre,
                pa.fecha_inicio AS periodo_inicio,
                pa.fecha_fin    AS periodo_fin
            FROM usuarios u
            LEFT JOIN datos_personales  dp  ON dp.usuario_id  = u.id
            LEFT JOIN datos_pasante     dpa ON dpa.usuario_id = u.id
            LEFT JOIN departamentos     d   ON d.id  = dpa.departamento_asignado_id
            LEFT JOIN instituciones     inst ON inst.id = dpa.institucion_id
            LEFT JOIN periodos_academicos pa ON pa.id  = dpa.periodo_id
            WHERE u.id = :uid AND u.rol_id = 3
            LIMIT 1
        ");
        $this->db->bind(':uid', $pasanteId);
        $pasante = $this->db->single();

        if (!$pasante) {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Rango de fechas (GET params, con fallback al período completo)
        $desde = trim($_GET['desde'] ?? '');
        $hasta = trim($_GET['hasta'] ?? '');

        if (empty($desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $desde = $pasante->periodo_inicio ?? date('Y-01-01');
        }
        if (empty($hasta) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $hasta = $pasante->periodo_fin ?? date('Y-12-31');
        }

        $this->db->query("
            SELECT fecha, hora_registro, estado, metodo, motivo_justificacion
            FROM asistencias
            WHERE pasante_id = :uid
              AND fecha BETWEEN :desde AND :hasta
            ORDER BY fecha ASC, hora_registro ASC
        ");
        $this->db->bind(':uid',   $pasanteId);
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $asistencias = $this->db->resultSet();

        // Estadísticas del rango
        $totalRegistros = count($asistencias);
        $totalPresentes = 0;
        $totalAusentes  = 0;
        $totalJustif    = 0;
        foreach ($asistencias as $a) {
            if ($a->estado === 'Presente' || $a->estado === 'Tardanza') $totalPresentes++;
            elseif ($a->estado === 'Justificado') $totalJustif++;
            else $totalAusentes++;
        }
        $pctAsist = $totalRegistros > 0 ? round(($totalPresentes / $totalRegistros) * 100) : 0;

        require_once '../app/lib/PdfGenerator.php';
        $pdf = new PdfGenerator();

        $baseImgDir = defined('APPROOT') ? APPROOT . '/../public/img/' : dirname(dirname(__DIR__)) . '/public/img/';
        $cintilloCandidates = [
            $baseImgDir . 'cintillo.png',
            $baseImgDir . 'cintillo_isp_bolivar.jpg',
            $baseImgDir . 'cintillo_pdf.png',
            $baseImgDir . 'cintillo-salud.png'
        ];
        $b64 = '';
        foreach ($cintilloCandidates as $cp) {
            if (file_exists($cp)) {
                $ext = strtolower(pathinfo($cp, PATHINFO_EXTENSION));
                $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
                $b64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($cp));
                break;
            }
        }

        $nombreCompleto = htmlspecialchars(trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? '')));
        $desdeF = date('d/m/Y', strtotime($desde));
        $hastaF = date('d/m/Y', strtotime($hasta));

        $meses = ['','enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];

        $estadoColors = [
            'Presente'    => ['bg' => '#dcfce7', 'color' => '#166534'],
            'Tardanza'    => ['bg' => '#fef3c7', 'color' => '#92400e'],
            'Ausente'     => ['bg' => '#fee2e2', 'color' => '#991b1b'],
            'Justificado' => ['bg' => '#e0e7ff', 'color' => '#3730a3'],
        ];

        $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
        <style>
            * { margin:0; padding:0; box-sizing:border-box; }
            body { font-family: Helvetica,Arial,sans-serif; font-size:11px; color:#1a1a1a; line-height:1.5; }
            .linea-navy { width:100%; height:3px; background:#162660; margin-bottom:18px; }
            .contenido  { padding:0 28px 28px; }
            .titulo-doc { text-align:center; font-size:13px; font-weight:bold; color:#162660; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; }
            .subtitulo  { text-align:center; font-size:10px; color:#555; margin-bottom:20px; }
            .lugar-fecha { text-align:right; font-size:10.5px; color:#444; margin-bottom:18px; }
            .info-grid  { display:table; width:100%; border-collapse:collapse; margin-bottom:16px; }
            .info-cell  { display:table-cell; vertical-align:top; width:50%; padding:6px 10px; }
            .info-cell strong { color:#162660; display:block; font-size:9px; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px; }
            .kpi-row    { display:table; width:100%; border-collapse:separate; border-spacing:8px; margin-bottom:16px; }
            .kpi-box    { display:table-cell; text-align:center; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:8px 4px; }
            .kpi-val    { font-size:18px; font-weight:bold; color:#162660; display:block; }
            .kpi-lbl    { font-size:8.5px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; }
            table.asist { width:100%; border-collapse:collapse; font-size:10.5px; }
            table.asist th { background:#162660; color:white; padding:6px 8px; text-align:left; font-size:9.5px; text-transform:uppercase; letter-spacing:0.4px; }
            table.asist td { padding:5px 8px; border-bottom:1px solid #e2e8f0; vertical-align:middle; }
            table.asist tr:nth-child(even) td { background:#f8fafc; }
            .badge { display:inline-block; padding:2px 8px; border-radius:12px; font-size:9px; font-weight:bold; }
            .pie   { margin-top:24px; border-top:1px solid #cbd5e1; padding-top:8px; font-size:8.5px; color:#9ca3af; text-align:center; }
        </style></head><body>';

        if ($b64) {
            $html .= '<div style="width:100%;line-height:0;"><img src="' . $b64 . '" style="width:100%;height:auto;display:block;" alt=""></div>';
        } else {
            $html .= '<div style="width:100%;padding:10px 20px;background:#162660;color:white;font-weight:bold;">Instituto de Salud Pública de Bolívar | SGP</div>';
        }

        $html .= '<div class="linea-navy"></div>';
        $html .= '<div class="contenido">';
        $html .= '<p class="lugar-fecha">Ciudad Bolívar, ' . date('d') . ' de ' . $meses[(int)date('m')] . ' de ' . date('Y') . '</p>';
        $html .= '<p class="titulo-doc">Reporte Individual de Asistencias</p>';
        $html .= '<p class="subtitulo">Período: ' . htmlspecialchars($pasante->periodo_nombre ?? 'N/D') . ' &nbsp;|&nbsp; ' . $desdeF . ' al ' . $hastaF . '</p>';

        $html .= '<table style="width:100%;border-collapse:collapse;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:16px;font-size:10.5px;"><tbody>';
        $html .= '<tr><td style="padding:7px 12px;background:#f1f5f9;font-weight:bold;color:#374151;width:30%;">Nombre</td><td style="padding:7px 12px;">' . $nombreCompleto . '</td><td style="padding:7px 12px;background:#f1f5f9;font-weight:bold;color:#374151;width:25%;">Cédula</td><td style="padding:7px 12px;">V-' . htmlspecialchars($pasante->cedula ?? '—') . '</td></tr>';
        $html .= '<tr><td style="padding:7px 12px;background:#f1f5f9;font-weight:bold;color:#374151;">Departamento</td><td style="padding:7px 12px;">' . htmlspecialchars($pasante->departamento ?? '—') . '</td><td style="padding:7px 12px;background:#f1f5f9;font-weight:bold;color:#374151;">Estado</td><td style="padding:7px 12px;">' . htmlspecialchars($pasante->estado_pasantia ?? '—') . '</td></tr>';
        $html .= '<tr><td style="padding:7px 12px;background:#f1f5f9;font-weight:bold;color:#374151;">Institución</td><td style="padding:7px 12px;" colspan="3">' . htmlspecialchars($pasante->institucion ?? '—') . '</td></tr>';
        $html .= '</tbody></table>';

        // KPIs
        $html .= '<table style="width:100%;border-collapse:separate;border-spacing:8px 0;margin-bottom:16px;"><tr>';
        $html .= '<td style="text-align:center;background:#dcfce7;border:1px solid #bbf7d0;border-radius:6px;padding:8px;"><span style="font-size:18px;font-weight:bold;color:#166534;display:block;">' . $totalPresentes . '</span><span style="font-size:8.5px;color:#166534;text-transform:uppercase;">Días Asistidos</span></td>';
        $html .= '<td style="text-align:center;background:#fee2e2;border:1px solid #fecaca;border-radius:6px;padding:8px;"><span style="font-size:18px;font-weight:bold;color:#991b1b;display:block;">' . $totalAusentes . '</span><span style="font-size:8.5px;color:#991b1b;text-transform:uppercase;">Ausencias</span></td>';
        $html .= '<td style="text-align:center;background:#e0e7ff;border:1px solid #c7d2fe;border-radius:6px;padding:8px;"><span style="font-size:18px;font-weight:bold;color:#3730a3;display:block;">' . $totalJustif . '</span><span style="font-size:8.5px;color:#3730a3;text-transform:uppercase;">Justificados</span></td>';
        $html .= '<td style="text-align:center;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:8px;"><span style="font-size:18px;font-weight:bold;color:#1e3a8a;display:block;">' . $pctAsist . '%</span><span style="font-size:8.5px;color:#64748b;text-transform:uppercase;">Tasa Asist.</span></td>';
        $html .= '</tr></table>';

        // Tabla de registros
        $html .= '<table class="asist"><thead><tr><th>#</th><th>Fecha</th><th>Día</th><th>Hora</th><th>Estado</th><th>Método</th><th>Observación</th></tr></thead><tbody>';

        $diasES = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        $rowNum = 0;
        foreach ($asistencias as $a) {
            $rowNum++;
            $ts   = strtotime($a->fecha);
            $dia  = $diasES[(int)date('w', $ts)];
            $colores = $estadoColors[$a->estado] ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
            $badge = '<span style="background:' . $colores['bg'] . ';color:' . $colores['color'] . ';padding:2px 8px;border-radius:12px;font-size:9px;font-weight:bold;">' . htmlspecialchars($a->estado) . '</span>';
            $html .= '<tr>';
            $html .= '<td style="color:#94a3b8;font-size:9.5px;">' . $rowNum . '</td>';
            $html .= '<td>' . date('d/m/Y', $ts) . '</td>';
            $html .= '<td style="color:#64748b;">' . $dia . '</td>';
            $html .= '<td>' . ($a->hora_registro ? date('h:i A', strtotime($a->hora_registro)) : '—') . '</td>';
            $html .= '<td>' . $badge . '</td>';
            $html .= '<td style="color:#64748b;">' . htmlspecialchars($a->metodo ?? '—') . '</td>';
            $html .= '<td style="color:#64748b;">' . htmlspecialchars($a->motivo_justificacion ?? '') . '</td>';
            $html .= '</tr>';
        }

        if ($rowNum === 0) {
            $html .= '<tr><td colspan="7" style="text-align:center;padding:20px;color:#94a3b8;font-style:italic;">No hay registros de asistencia en el rango seleccionado.</td></tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div class="pie">Instituto de Salud Pública del Estado Bolívar &nbsp;|&nbsp; Sistema de Gestión de Pasantías (SGP) &nbsp;|&nbsp; Generado el ' . date('d/m/Y H:i') . '</div>';
        $html .= '</div></body></html>';

        $nombreSafe = preg_replace('/[^A-Za-z0-9_\-]/', '_', trim(($pasante->nombres ?? '') . '_' . ($pasante->apellidos ?? '')));
        $pdf->renderDomPdf($html, 'Asistencias_' . $nombreSafe . '_' . str_replace('-', '', $desde) . '-' . str_replace('-', '', $hasta), false);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /periodos/activar
    // ─────────────────────────────────────────────────────────────────
    public function activar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $id = (int)($_POST['periodo_id'] ?? 0);
        if ($id <= 0) {
            Session::setFlash('error', 'ID de período inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        try {
            $this->db->query("SELECT id FROM periodos_academicos WHERE id = :id AND LOWER(estado) = 'planificado'");
            $this->db->bind(':id', $id);
            if (!$this->db->single()) {
                Session::setFlash('error', 'El período no existe o no está en estado Planificado.');
                header('Location: ' . URLROOT . '/periodos');
                exit;
            }

            // Obtener tipo del período a activar
            $this->db->query("SELECT tipo FROM periodos_academicos WHERE id = :id LIMIT 1");
            $this->db->bind(':id', $id);
            $pTipo = $this->db->single();
            $tipo  = $pTipo->tipo ?? 'Regular';

            // Cerrar solo el período activo del MISMO tipo
            $this->db->query("UPDATE periodos_academicos SET estado = 'Cerrado' WHERE LOWER(estado) = 'activo' AND tipo = :tipo");
            $this->db->bind(':tipo', $tipo);
            $this->db->execute();

            // Activar el período seleccionado
            $this->db->query("UPDATE periodos_academicos SET estado = 'Activo' WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            Session::setFlash('success', '¡Cohorte iniciada! El nuevo período ahora está Activo.');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Ocurrió un error en la base de datos al activar el período.');
        }

        header('Location: ' . URLROOT . '/periodos');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /periodos/eliminar/ID — solo períodos Planificados
    // ─────────────────────────────────────────────────────────────────
    public function eliminar(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if ($id <= 0) {
            Session::setFlash('error', 'ID de período inválido.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        $this->db->query("SELECT id, nombre, estado FROM periodos_academicos WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        $periodo = $this->db->single();

        if (!$periodo) {
            Session::setFlash('error', 'El período no existe.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        if (!in_array($periodo->estado, ['Planificado', 'Activo', 'Cerrado'], true)) {
            Session::setFlash('error', 'Solo se pueden eliminar períodos en estado Planificado, Activo o Cerrado.');
            header('Location: ' . URLROOT . '/periodos');
            exit;
        }

        // Desvincular pasantes del período antes de eliminarlo
        $this->db->query("UPDATE datos_pasante SET periodo_id = NULL WHERE periodo_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();

        $this->db->query("DELETE FROM periodos_academicos WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();

        Session::setFlash('success', 'Período «' . $periodo->nombre . '» eliminado correctamente.');
        header('Location: ' . URLROOT . '/periodos');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /periodos/buscarGlobal (AJAX)
    // ─────────────────────────────────────────────────────────────────
    public function buscarGlobal(): void
    {
        header('Content-Type: application/json');
        
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }

        $this->db->query("
            SELECT
                u.id, u.cedula, dp.nombres, dp.apellidos,
                pa.nombre AS periodo_nombre, pa.estado AS periodo_estado,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia,
                COALESCE(
                    NULLIF(dpa.horas_meta, 0),
                    NULLIF(ROUND(DATEDIFF(pa.fecha_fin, pa.fecha_inicio) * 5/7) * 8, 0),
                    1440
                ) AS horas_meta,
                COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas,
                dept.nombre AS departamento
            FROM usuarios u
            LEFT JOIN datos_personales   dp   ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante      dpa  ON dpa.usuario_id = u.id
            LEFT JOIN periodos_academicos pa  ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos      dept ON dept.id = dpa.departamento_asignado_id
            WHERE u.rol_id = 3
            AND (u.cedula LIKE :q OR CONCAT(dp.nombres, ' ', dp.apellidos) LIKE :q)
            GROUP BY u.id
            LIMIT 15
        ");
        $this->db->bind(':q', "%$q%");
        $resultados = $this->db->resultSet();

        echo json_encode($resultados);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /periodos/reporteHistoricoPasante/ID
    // ─────────────────────────────────────────────────────────────────
    public function reporteHistoricoPasante(int $id = 0): void
    {
        if ($id <= 0) { App::renderError(400); return; }

        // ── Datos del pasante ───────────────────────────────────────
        $this->db->query("
            SELECT u.cedula, dp.nombres, dp.apellidos, dp.telefono,
                   COALESCE(inst.nombre, dpa.institucion_procedencia, 'N/D') AS institucion_procedencia,
                   pa.nombre AS periodo_nombre, pa.estado AS periodo_estado,
                   pa.fecha_inicio, pa.fecha_fin,
                   COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia,
                   COALESCE(
                       NULLIF(dpa.horas_meta, 0),
                       NULLIF(ROUND(DATEDIFF(pa.fecha_fin, pa.fecha_inicio) * 5/7) * 8, 0),
                       1440
                   ) AS horas_meta,
                   COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas,
                   dept.nombre AS departamento,
                   CONCAT(COALESCE(tut_dp.nombres,''), ' ', COALESCE(tut_dp.apellidos,'')) AS tutor_nombre
            FROM usuarios u
            LEFT JOIN datos_personales dp    ON dp.usuario_id   = u.id
            LEFT JOIN datos_pasante    dpa   ON dpa.usuario_id  = u.id
            LEFT JOIN periodos_academicos pa  ON pa.id           = dpa.periodo_id
            LEFT JOIN departamentos    dept  ON dept.id          = dpa.departamento_asignado_id
            LEFT JOIN instituciones    inst  ON inst.id          = dpa.institucion_procedencia
            LEFT JOIN usuarios         tut   ON tut.id           = dpa.tutor_id
            LEFT JOIN datos_personales tut_dp ON tut_dp.usuario_id = tut.id
            WHERE u.id = :id AND u.rol_id = 3
            ORDER BY FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dpa.id DESC
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        $p = $this->db->single();
        if (!$p) { App::renderError(404); return; }

        // ── Métricas de asistencia ──────────────────────────────────
        $this->db->query("
            SELECT
                COUNT(id) AS total,
                SUM(CASE WHEN estado = 'Presente'   THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN estado = 'Ausente'    THEN 1 ELSE 0 END) AS ausentes,
                SUM(CASE WHEN estado = 'Tardanza'   THEN 1 ELSE 0 END) AS tardanzas,
                SUM(CASE WHEN estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados,
                MIN(fecha) AS primera_fecha,
                MAX(fecha) AS ultima_fecha
            FROM asistencias WHERE pasante_id = :id
        ");
        $this->db->bind(':id', $id);
        $asist = $this->db->single();

        // ── Cálculos derivados ──────────────────────────────────────
        $hMeta     = (int)$p->horas_meta;
        $hAcum     = (int)$p->horas_acumuladas;
        $hFaltan   = max(0, $hMeta - $hAcum);
        $pct       = $hMeta > 0 ? min(100, round($hAcum / $hMeta * 100)) : 0;
        $pctBar    = $pct;

        $presentes   = (int)($asist->presentes   ?? 0);
        $ausentes    = (int)($asist->ausentes    ?? 0);
        $tardanzas   = (int)($asist->tardanzas   ?? 0);
        $justificados= (int)($asist->justificados?? 0);
        $totalDias   = (int)($asist->total       ?? 0);
        $diasEfect   = $presentes + $tardanzas;
        $pctAsist    = $totalDias > 0 ? round($diasEfect / $totalDias * 100, 1) : 0;

        $fmtFecha = fn($f) => $f ? date('d/m/Y', strtotime($f)) : '—';
        $nombreCompleto = trim(($p->nombres ?? '') . ' ' . ($p->apellidos ?? ''));

        // ── Color de estado ─────────────────────────────────────────
        $estadoColor = match(strtolower($p->estado_pasantia ?? '')) {
            'activo'     => ['bg' => '#dcfce7', 'txt' => '#166534', 'dot' => '#16a34a'],
            'finalizado' => ['bg' => '#ede9fe', 'txt' => '#5b21b6', 'dot' => '#7c3aed'],
            'pendiente'  => ['bg' => '#fef3c7', 'txt' => '#92400e', 'dot' => '#d97706'],
            default      => ['bg' => '#f1f5f9', 'txt' => '#475569', 'dot' => '#94a3b8'],
        };
        $pctColor = $pct >= 80 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626');

        // ── Cintillo institucional ──────────────────────────────────
        $baseImgDir = defined('APPROOT') ? APPROOT . '/../public/img/' : dirname(dirname(__DIR__)) . '/public/img/';
        $cintilloCandidates = [
            $baseImgDir . 'cintillo.png',
            $baseImgDir . 'cintillo_isp_bolivar.jpg',
            $baseImgDir . 'cintillo_pdf.png',
            $baseImgDir . 'cintillo-salud.png'
        ];
        $bCintillo = '';
        foreach ($cintilloCandidates as $cp) {
            if (file_exists($cp)) {
                $ext = strtolower(pathinfo($cp, PATHINFO_EXTENSION));
                $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
                $bCintillo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($cp));
                break;
            }
        }

        // ── HTML ────────────────────────────────────────────────────
        $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
        @page { size: letter portrait; margin: 0.8cm 1.2cm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 0; line-height: 1.4; }

        /* Cintillo */
        .cintillo { text-align:center; margin-bottom: 6px; }
        .cintillo img { height: 14mm; max-width: 100%; object-fit: contain; }

        /* Títulos */
        .doc-title { text-align:center; margin: 4px 0 2px; font-size: 14px; font-weight: 800; color: #172554; letter-spacing: 0.5px; }
        .doc-sub   { text-align:center; font-size: 9px; color: #64748b; margin-bottom: 10px; }

        /* Banda de identidad del pasante */
        .id-band { background: linear-gradient(135deg, #172554 0%, #1e3a8a 60%, #2563eb 100%);
                   border-radius: 10px; padding: 12px 16px; margin-bottom: 10px;
                   display: flex; justify-content: space-between; align-items: center; }
        .id-band .name  { font-size: 13px; font-weight: 800; color: #ffffff; letter-spacing: 0.3px; }
        .id-band .meta  { font-size: 9px; color: rgba(255,255,255,0.75); margin-top: 3px; }
        .id-band .badge { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
                          border-radius: 20px; padding: 4px 12px; font-size: 9px;
                          color: white; font-weight: 700; white-space: nowrap; }

        /* Grid de 2 columnas */
        .grid2 { display: table; width: 100%; border-spacing: 8px; margin-bottom: 8px; }
        .grid2 .col { display: table-cell; vertical-align: top; }
        .grid2 .col-l { width: 55%; padding-right: 4px; }
        .grid2 .col-r { width: 45%; padding-left: 4px; }

        /* Tarjetas sección */
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;
                padding: 10px 12px; margin-bottom: 8px; }
        .card-hd { font-size: 8px; font-weight: 800; color: #64748b; text-transform: uppercase;
                   letter-spacing: 0.8px; margin-bottom: 8px; padding-bottom: 5px;
                   border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 5px; }
        .card-hd-icon { width: 18px; height: 18px; border-radius: 5px;
                        display: inline-flex; align-items: center; justify-content: center;
                        font-size: 10px; font-weight: 700; color: white; }

        /* Fila de dato */
        .row-d { margin-bottom: 5px; font-size: 9.5px; }
        .row-d .lbl { color: #64748b; font-weight: 700; display: inline; }
        .row-d .val { color: #1e293b; display: inline; }

        /* Badge estado */
        .est-badge { display: inline-block; border-radius: 20px; padding: 2px 10px;
                     font-size: 8.5px; font-weight: 800; }

        /* Barra de progreso */
        .prog-wrap { margin: 6px 0 4px; }
        .prog-lbl { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .prog-lbl span { font-size: 9px; color: #64748b; }
        .prog-lbl strong { font-size: 9px; color: #1e293b; }
        .prog-track { height: 10px; background: #f1f5f9; border-radius: 20px; overflow: hidden; }
        .prog-fill  { height: 100%; border-radius: 20px; }
        .prog-big   { font-size: 20px; font-weight: 800; line-height: 1; }
        .prog-detail{ font-size: 8.5px; color: #64748b; margin-top: 3px; }

        /* Grid de estadísticas */
        .stats-grid { display: table; width: 100%; border-spacing: 6px; }
        .stat-cell  { display: table-cell; text-align: center; padding: 8px 4px;
                      background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }
        .stat-val   { font-size: 16px; font-weight: 800; line-height: 1; }
        .stat-lbl   { font-size: 7.5px; color: #64748b; font-weight: 700; text-transform: uppercase;
                      letter-spacing: 0.5px; margin-top: 3px; }

        /* Tabla de resumen */
        table.sum { width: 100%; border-collapse: collapse; font-size: 9px; }
        table.sum th { background: #f1f5f9; color: #64748b; font-weight: 800; text-transform: uppercase;
                       font-size: 8px; letter-spacing: 0.5px; padding: 5px 8px; text-align: center;
                       border: 1px solid #e2e8f0; }
        table.sum td { padding: 5px 8px; text-align: center; border: 1px solid #e2e8f0; color: #1e293b; }
        table.sum tr:nth-child(even) td { background: #f8fafc; }

        /* Footer */
        .footer { margin-top: 12px; padding-top: 6px; border-top: 1px solid #e2e8f0;
                  text-align: center; font-size: 8px; color: #94a3b8; }
        </style></head><body>';

        // ── Cintillo ────────────────────────────────────────────────
        if ($bCintillo) {
            $html .= '<div class="cintillo"><img src="' . $bCintillo . '" alt="ISP Bolívar"></div>';
        }
        $html .= '<p class="doc-title">Expediente Histórico del Pasante</p>';
        $html .= '<p class="doc-sub">Sistema de Gestión de Pasantías — Instituto de Salud Pública del Estado Bolívar</p>';

        // ── Banda de identidad ──────────────────────────────────────
        $html .= '<div class="id-band">';
        $html .= '<div>';
        $html .= '<div class="name">' . htmlspecialchars(strtoupper($nombreCompleto)) . '</div>';
        $html .= '<div class="meta">C.I.: V-' . htmlspecialchars($p->cedula ?? 'N/A') . '&nbsp;&nbsp;|&nbsp;&nbsp;' . htmlspecialchars($p->departamento ?? 'Sin Departamento') . '</div>';
        $html .= '</div>';
        $html .= '<div class="badge">' . htmlspecialchars($p->estado_pasantia) . '</div>';
        $html .= '</div>';

        // ── Grid 2 columnas: Datos + Progreso ───────────────────────
        $html .= '<div class="grid2"><div class="col col-l">';

        // Tarjeta: Vinculación Académica
        $html .= '<div class="card">';
        $html .= '<div class="card-hd"><span class="card-hd-icon" style="background:#2563eb;">&#9679;</span> Vinculación Académica</div>';
        $html .= '<div class="row-d"><span class="lbl">Período: </span><span class="val">' . htmlspecialchars($p->periodo_nombre ?? 'No asignado') . '</span></div>';
        $html .= '<div class="row-d"><span class="lbl">Estado Período: </span><span class="val">' . htmlspecialchars($p->periodo_estado ?? '—') . '</span></div>';
        $html .= '<div class="row-d"><span class="lbl">Inicio: </span><span class="val">' . $fmtFecha($p->fecha_inicio) . '&nbsp;&nbsp;</span><span class="lbl">Fin Est.: </span><span class="val">' . $fmtFecha($p->fecha_fin) . '</span></div>';
        $html .= '<div class="row-d"><span class="lbl">Departamento: </span><span class="val">' . htmlspecialchars($p->departamento ?? '—') . '</span></div>';
        $html .= '<div class="row-d"><span class="lbl">Tutor: </span><span class="val">' . htmlspecialchars(trim($p->tutor_nombre ?? '') ?: '—') . '</span></div>';
        if (!empty($p->institucion_procedencia)) {
            $html .= '<div class="row-d"><span class="lbl">Institución: </span><span class="val">' . htmlspecialchars($p->institucion_procedencia) . '</span></div>';
        }
        $html .= '</div>';

        // Tarjeta: Resumen de Asistencia (tabla)
        $html .= '<div class="card">';
        $html .= '<div class="card-hd"><span class="card-hd-icon" style="background:#0891b2;">&#9632;</span> Resumen de Asistencia</div>';
        $html .= '<table class="sum"><thead><tr>';
        $html .= '<th>Presentes</th><th>Tardanzas</th><th>Justificados</th><th>Ausentes</th><th>Total Días</th><th>% Asistencia</th>';
        $html .= '</tr></thead><tbody><tr>';
        $html .= '<td style="color:#16a34a;font-weight:800;">' . $presentes . '</td>';
        $html .= '<td style="color:#d97706;font-weight:800;">' . $tardanzas . '</td>';
        $html .= '<td style="color:#2563eb;font-weight:800;">' . $justificados . '</td>';
        $html .= '<td style="color:#dc2626;font-weight:800;">' . $ausentes . '</td>';
        $html .= '<td>' . $totalDias . '</td>';
        $html .= '<td style="font-weight:800;">' . $pctAsist . '%</td>';
        $html .= '</tr></tbody></table>';
        $html .= '</div>';

        $html .= '</div><div class="col col-r">';

        // Tarjeta: Progreso de Horas
        $html .= '<div class="card">';
        $html .= '<div class="card-hd"><span class="card-hd-icon" style="background:' . $pctColor . ';">&#9650;</span> Progreso de Horas</div>';
        $html .= '<div style="text-align:center; margin-bottom:8px;">';
        $html .= '<div class="prog-big" style="color:' . $pctColor . ';">' . $pct . '%</div>';
        $html .= '<div class="prog-detail">completado de la pasantía</div>';
        $html .= '</div>';
        $html .= '<div class="prog-wrap">';
        $html .= '<div class="prog-lbl"><span>0h</span><strong>' . $hMeta . 'h meta</strong></div>';
        $html .= '<div class="prog-track"><div class="prog-fill" style="width:' . $pctBar . '%; background:' . $pctColor . ';"></div></div>';
        $html .= '</div>';
        $html .= '<div class="stats-grid" style="margin-top:8px;">';
        $html .= '<div class="stat-cell"><div class="stat-val" style="color:' . $pctColor . ';">' . $hAcum . '</div><div class="stat-lbl">Hrs Acum.</div></div>';
        $html .= '<div class="stat-cell"><div class="stat-val" style="color:#64748b;">' . $hFaltan . '</div><div class="stat-lbl">Hrs Faltan</div></div>';
        $html .= '</div>';
        $html .= '</div>';

        // Tarjeta: KPIs rápidos
        $html .= '<div class="card">';
        $html .= '<div class="card-hd"><span class="card-hd-icon" style="background:#7c3aed;">&#9670;</span> Indicadores Clave</div>';
        $html .= '<div class="stats-grid">';
        $html .= '<div class="stat-cell"><div class="stat-val" style="color:#16a34a;">' . $diasEfect . '</div><div class="stat-lbl">Días Efectivos</div></div>';
        $html .= '<div class="stat-cell"><div class="stat-val" style="color:#dc2626;">' . $ausentes . '</div><div class="stat-lbl">Ausencias</div></div>';
        $html .= '</div>';
        $html .= '<div style="margin-top:8px; padding:6px 8px; background:#f0fdf4; border-radius:6px; font-size:8.5px; color:#166534; text-align:center;">';
        if ($hFaltan <= 0) {
            $html .= '&#10003; Horas de pasantía completadas';
        } elseif ($hFaltan <= 80) {
            $dFaltan = (int)ceil($hFaltan / 8);
            $html .= 'Faltan aprox. <strong>' . $dFaltan . ' días hábiles</strong> para culminar';
        } else {
            $dFaltan = (int)ceil($hFaltan / 8);
            $html .= 'Pendientes <strong>' . $dFaltan . ' días hábiles</strong> (' . $hFaltan . 'h)';
        }
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div></div>'; // cierre grid2

        // ── Footer ──────────────────────────────────────────────────
        $html .= '<div class="footer">Reporte generado por el Sistema de Gestión de Pasantías (SGP) &mdash; ' . date('d/m/Y H:i:s') . '</div>';
        $html .= '</body></html>';

        require_once '../app/lib/PdfGenerator.php';
        $pdf = new PdfGenerator();
        $pdf->renderDomPdf(
            $html,
            'Expediente_SGP_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', strtoupper($nombreCompleto)),
            true
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /periodos/ajustarFechasPasante  (AJAX → JSON)
    // Edita fecha_inicio_pasantia y fecha_fin_estimada individualmente
    // y recalcula horas_meta con días L-V reales menos feriados.
    // ─────────────────────────────────────────────────────────────────
    public function ajustarFechasPasante(): void
    {
        header('Content-Type: application/json');
        $raw   = file_get_contents('php://input');
        $json  = json_decode($raw, true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($json['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalido.']); exit;
        }
        $pasanteId   = (int)($json['pasante_id']   ?? 0);
        $fechaInicio = trim($json['fecha_inicio']   ?? '');
        $fechaFin    = trim($json['fecha_fin']      ?? '');

        if (!$pasanteId || !$fechaInicio || !$fechaFin || $fechaFin <= $fechaInicio) {
            echo json_encode(['success' => false, 'message' => 'Datos invalidos.']); exit;
        }

        // Calcular días L-V reales entre las fechas individuales del pasante
        $cur = new DateTime($fechaInicio);
        $end = new DateTime($fechaFin);
        $lv  = 0;
        while ($cur <= $end) {
            if ((int)$cur->format('N') < 6) $lv++;
            $cur->modify('+1 day');
        }
        // Restar feriados no laborables que caigan en L-V en ese rango individual
        $this->db->query("
            SELECT COUNT(*) AS total FROM dias_feriados
            WHERE fecha BETWEEN :ini AND :fin
              AND es_laborable = 0 AND DAYOFWEEK(fecha) BETWEEN 2 AND 6
        ");
        $this->db->bind(':ini', $fechaInicio);
        $this->db->bind(':fin', $fechaFin);
        $lv -= (int)($this->db->single()->total ?? 0);
        $nuevasHoras = max(160, $lv * 8);

        // Actualizar datos_pasante
        $this->db->query("
            UPDATE datos_pasante
            SET fecha_inicio_pasantia = :fi,
                fecha_fin_estimada    = :ff,
                horas_meta            = :hm
            WHERE usuario_id = :uid
        ");
        $this->db->bind(':fi',  $fechaInicio);
        $this->db->bind(':ff',  $fechaFin);
        $this->db->bind(':hm',  $nuevasHoras);
        $this->db->bind(':uid', $pasanteId);

        if (!$this->db->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar.']); exit;
        }

        // Notificar al pasante del cambio
        require_once APPROOT . '/models/NotificationModel.php';
        $notifModel = new NotificationModel($this->db);
        $notifModel->create(
            $pasanteId, 'periodo_actualizado',
            'Tus fechas de pasantia fueron actualizadas',
            "Inicio: " . date('d/m/Y', strtotime($fechaInicio)) .
            " — Fin estimado: " . date('d/m/Y', strtotime($fechaFin)) .
            " — Nueva meta: {$nuevasHoras}h.",
            URLROOT . '/pasante/dashboard'
        );

        echo json_encode([
            'success'         => true,
            'horas_meta'      => $nuevasHoras,
            'fecha_inicio_fmt'=> date('d/m/Y', strtotime($fechaInicio)),
            'fecha_fin_fmt'   => date('d/m/Y', strtotime($fechaFin)),
        ]);
        exit;
    }

    /**
     * POST /periodos/ajustarTodosA1440  (AJAX → JSON)
     * Para cada pasante del período, calcula la fecha_fin que resulta en
     * exactamente 180 días L-V (1440h) desde su fecha_inicio individual
     * y actualiza horas_meta y fecha_fin_estimada.
     */
    public function ajustarTodosA1440(): void
    {
        header('Content-Type: application/json');
        $raw   = file_get_contents('php://input');
        $json  = json_decode($raw, true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($json['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalido.']); exit;
        }
        $periodoId = (int)($json['periodo_id'] ?? 0);
        if ($periodoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalido.']); exit;
        }

        // Pasantes del período con su fecha de inicio individual
        $this->db->query("
            SELECT dpa.usuario_id,
                   COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio,
                   pa.fecha_fin AS periodo_fecha_fin
            FROM datos_pasante dpa
            JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE dpa.periodo_id = :pid
              AND dpa.estado_pasantia IN ('Activo','Finalizado')
        ");
        $this->db->bind(':pid', $periodoId);
        $pasantes = $this->db->resultSet();

        if (!$pasantes) {
            echo json_encode(['success' => false, 'message' => 'No hay pasantes en este periodo.']); exit;
        }

        // Feriados NO se descuentan — solo L-V calendario puro
        $TARGET = 180; // 180 días L-V × 8h = 1440h
        $actualizados = 0;
        $resultados   = [];

        foreach ($pasantes as $p) {
            $cur       = new DateTime($p->fecha_inicio);
            $lv        = 0;
            $fechaFin  = clone $cur;

            while ($lv < $TARGET) {
                if ((int)$cur->format('N') < 6) {
                    $lv++;
                    $fechaFin = clone $cur;
                }
                if ($lv < $TARGET) $cur->modify('+1 day');
            }

            $nuevaFechaFin = $fechaFin->format('Y-m-d');
            // Respetar la culminación oficial del período si es posterior
            if (!empty($p->periodo_fecha_fin) && $p->periodo_fecha_fin >= $nuevaFechaFin) {
                $nuevaFechaFin = $p->periodo_fecha_fin;
            }

            $this->db->query("
                UPDATE datos_pasante
                SET fecha_fin_estimada = :ff, horas_meta = 1440
                WHERE usuario_id = :uid AND periodo_id = :pid
            ");
            $this->db->bind(':ff',  $nuevaFechaFin);
            $this->db->bind(':uid', (int)$p->usuario_id);
            $this->db->bind(':pid', $periodoId);
            $this->db->execute();
            $actualizados++;

            $resultados[] = [
                'inicio' => date('d/m/Y', strtotime($p->fecha_inicio)),
                'fin'    => date('d/m/Y', strtotime($nuevaFechaFin)),
            ];
        }

        echo json_encode([
            'success'      => true,
            'actualizados' => $actualizados,
            'resultados'   => $resultados,
        ]);
        exit;
    }

    /**
     * GET /periodos/calcularFechaFin?inicio=YYYY-MM-DD&horas=1440  (AJAX → JSON)
     * Calcula la fecha_fin que resulta en exactamente N horas (días L-V - feriados)
     * desde una fecha de inicio dada. Usado por el modal de asignación.
     */
    /**
     * GET /periodos/calcularDiasHabiles?inicio=YYYY-MM-DD&fin=YYYY-MM-DD  (AJAX → JSON)
     * Cuenta los días L-V reales entre dos fechas, excluyendo feriados no laborables.
     */
    /**
     * GET /periodos/calcularDiasHabiles?inicio=YYYY-MM-DD&fin=YYYY-MM-DD
     * Cuenta días L-V entre dos fechas. Los feriados NO se descuentan —
     * solo se usan para visualización/reportes, no para el conteo de horas.
     */
    public function calcularDiasHabiles(): void
    {
        header('Content-Type: application/json');
        $inicio = trim($_GET['inicio'] ?? '');
        $fin    = trim($_GET['fin']    ?? '');
        if (!$inicio || !$fin || !strtotime($inicio) || !strtotime($fin) || $fin <= $inicio) {
            echo json_encode(['success' => false, 'message' => 'Fechas inválidas.']); exit;
        }

        $cur = new DateTime($inicio);
        $end = new DateTime($fin);
        $lv  = 0;
        while ($cur <= $end) {
            if ((int)$cur->format('N') < 6) $lv++;
            $cur->modify('+1 day');
        }

        echo json_encode(['success' => true, 'dias_lv' => $lv, 'horas' => $lv * 8]);
        exit;
    }

    public function calcularFechaFin(): void
    {
        header('Content-Type: application/json');
        $inicio = trim($_GET['inicio'] ?? '');
        $horas  = max(1, (int)($_GET['horas'] ?? 1440));

        if (!$inicio || !strtotime($inicio)) {
            echo json_encode(['success' => false, 'message' => 'Fecha de inicio inválida.']); exit;
        }

        $targetDias = (int)ceil($horas / 8);

        // Cargar feriados no laborables reales
        $this->db->query("SELECT fecha FROM dias_feriados WHERE es_laborable = 0");
        $feriadosRows = $this->db->resultSet();
        $feriados = array_column($feriadosRows, 'fecha');

        $cur      = new DateTime($inicio);
        $lv       = 0;
        $fechaFin = clone $cur;

        while ($lv < $targetDias) {
            $dow  = (int)$cur->format('N');
            // Días hábiles calendario L-V (los feriados no postergan la fecha fin, se computan como Justificado)
            if ($dow < 6) {
                $lv++;
                $fechaFin = clone $cur;
            }
            if ($lv < $targetDias) $cur->modify('+1 day');
        }

        echo json_encode([
            'success'    => true,
            'fecha_fin'  => $fechaFin->format('Y-m-d'),
            'fecha_fmt'  => $fechaFin->format('d/m/Y'),
            'dias_lv'    => $lv,
            'horas_meta' => $horas,
        ]);
        exit;
    }

    /**
     * POST /periodos/ajustarInstitucionA1440  (AJAX → JSON)
     * Actualiza fecha_inicio_pasantia, fecha_fin_estimada y horas_meta=1440
     * para todos los pasantes de una institución específica dentro de un período,
     * calculando fecha_fin real (L-V - feriados) desde la nueva fecha de inicio.
     */
    public function ajustarInstitucionA1440(): void
    {
        header('Content-Type: application/json');
        $raw   = file_get_contents('php://input');
        $json  = json_decode($raw, true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($json['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
        }

        $periodoId     = (int)($json['periodo_id']      ?? 0);
        $institucionId = (int)($json['institucion_id']  ?? 0);
        $fechaInicio   = trim($json['fecha_inicio']     ?? '');

        if (!$periodoId || !$institucionId || !$fechaInicio || !strtotime($fechaInicio)) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos.']); exit;
        }

        // Feriados NO se descuentan — solo L-V calendario puro
        $cur      = new DateTime($fechaInicio);
        $lv       = 0;
        $fechaFin = clone $cur;
        while ($lv < 180) {
            if ((int)$cur->format('N') < 6) {
                $lv++;
                $fechaFin = clone $cur;
            }
            if ($lv < 180) $cur->modify('+1 day');
        }
        $nuevaFechaFin = $fechaFin->format('Y-m-d');
        // Respetar la culminación oficial del período si es posterior
        $this->db->query("SELECT fecha_fin FROM periodos_academicos WHERE id = :pid LIMIT 1");
        $this->db->bind(':pid', $periodoId);
        $perObj = $this->db->single();
        if ($perObj && !empty($perObj->fecha_fin) && $perObj->fecha_fin >= $nuevaFechaFin) {
            $nuevaFechaFin = $perObj->fecha_fin;
        }

        // Obtener pasantes de esa institución en el período
        // Soporta tanto institucion_id (FK) como institucion_procedencia (numérico texto)
        $this->db->query("
            SELECT dpa.usuario_id,
                   CONCAT(dp.nombres, ' ', dp.apellidos) AS nombre
            FROM datos_pasante dpa
            LEFT JOIN datos_personales dp ON dp.usuario_id = dpa.usuario_id
            WHERE dpa.periodo_id = :pid
              AND dpa.estado_pasantia IN ('Activo', 'Finalizado')
              AND (
                  dpa.institucion_id = :inst
                  OR (dpa.institucion_procedencia REGEXP '^[0-9]+$'
                      AND CAST(dpa.institucion_procedencia AS UNSIGNED) = :inst2)
              )
        ");
        $this->db->bind(':pid',   $periodoId);
        $this->db->bind(':inst',  $institucionId);
        $this->db->bind(':inst2', $institucionId);
        $pasantes = $this->db->resultSet();

        if (!$pasantes) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron pasantes de esa institución en el período.']); exit;
        }

        $actualizados = 0;
        foreach ($pasantes as $p) {
            $this->db->query("
                UPDATE datos_pasante
                SET fecha_inicio_pasantia = :fi,
                    fecha_fin_estimada    = :ff,
                    horas_meta            = 1440
                WHERE usuario_id = :uid AND periodo_id = :pid
            ");
            $this->db->bind(':fi',  $fechaInicio);
            $this->db->bind(':ff',  $nuevaFechaFin);
            $this->db->bind(':uid', (int)$p->usuario_id);
            $this->db->bind(':pid', $periodoId);
            $this->db->execute();
            $actualizados++;
        }

        // Bitácora
        $this->db->query("INSERT INTO bitacora (usuario_id, accion, ip_address, detalles) VALUES (:uid, 'AJUSTE_INST_1440', :ip, :det)");
        $this->db->bind(':uid', (int)Session::get('user_id'));
        $this->db->bind(':ip',  $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $this->db->bind(':det', json_encode(['institucion_id' => $institucionId, 'periodo_id' => $periodoId, 'inicio' => $fechaInicio, 'fin' => $nuevaFechaFin, 'actualizados' => $actualizados]));
        $this->db->execute();

        echo json_encode([
            'success'      => true,
            'actualizados' => $actualizados,
            'fecha_inicio' => date('d/m/Y', strtotime($fechaInicio)),
            'fecha_fin'    => date('d/m/Y', strtotime($nuevaFechaFin)),
            'pasantes'     => array_map(fn($p) => $p->nombre, $pasantes),
        ]);
        exit;
    }
}
