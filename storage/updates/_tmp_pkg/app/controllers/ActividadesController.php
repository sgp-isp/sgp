<?php
/**
 * ActividadesController - Módulo Actividades Extras
 *
 * Gestión de grupos/brigadas de servicio comunitario, pasantías cortas
 * y mantenimiento provenientes de instituciones universitarias externas.
 * URL base: /actividades  —  exclusiva para Administradores (rol_id = 1).
 */

declare(strict_types=1);

class ActividadesController extends Controller
{
    private Database $db;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();

        if (!RoleMiddleware::hasAnyRole([0, 1, 2])) {
            RoleMiddleware::redirectToRoleDashboard(Session::get('role_id'));
        }

        $this->db = Database::getInstance();
    }

    // =========================================================
    // Helper: Merge JSON body into $_POST for fetch() AJAX calls
    // =========================================================
    private function parsePostBody(): void
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($ct, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $_POST = array_merge($_POST, $json);
                }
            }
        }
    }

    // =========================================================
    // Helper: queries reutilizables
    // =========================================================
    private function getKpis(): array
    {
        $this->db->query("SELECT COUNT(*) AS total FROM datos_pasante WHERE tipo_pasantia = 'Corta'");
        $kpiPasantesCortos = (int)($this->db->single()->total ?? 0);

        $this->db->query("SELECT COUNT(*) AS total FROM datos_pasante WHERE tipo_pasantia = 'Corta' AND estado_pasantia = 'Activo'");
        $kpiCortosActivos = (int)($this->db->single()->total ?? 0);

        $this->db->query("SELECT COUNT(*) AS total FROM actividades_extras WHERE tipo != 'Pasantía Corta'");
        $kpiActividadesCom = (int)($this->db->single()->total ?? 0);

        $this->db->query("SELECT COUNT(*) AS total FROM instituciones WHERE categoria = 'actividad' AND activo = 1");
        $kpiInstituciones = (int)($this->db->single()->total ?? 0);

        $this->db->query("SELECT id, nombre, estado, fecha_inicio, fecha_fin FROM periodos_academicos WHERE tipo = 'Corto' AND estado = 'Activo' LIMIT 1");
        $periodoCortoActivo = $this->db->single();

        return compact('kpiPasantesCortos', 'kpiCortosActivos', 'kpiActividadesCom', 'kpiInstituciones', 'periodoCortoActivo');
    }

    // =========================================================
    // GET /actividades — Hub central con previews + KPIs
    // =========================================================
    public function index(): void
    {
        $kpis = $this->getKpis();

        // Preview: últimas 3 instituciones
        $this->db->query("SELECT id, nombre, tipo, contacto FROM instituciones WHERE categoria = 'actividad' ORDER BY id DESC LIMIT 3");
        $previewInstituciones = $this->db->resultSet();

        // Preview: últimos 3 pasantes cortos
        $this->db->query("
            SELECT u.id, dp.nombres, dp.apellidos,
                   dpa.estado_pasantia, dpa.horas_meta,
                   ie.nombre AS institucion_nombre,
                   COALESCE((SELECT COUNT(*) * 8
                             FROM asistencias a WHERE a.pasante_id = u.id
                             AND a.estado != 'Anulado' AND a.fecha <= CURDATE()), 0) AS horas_acumuladas
            FROM datos_pasante dpa
            JOIN usuarios u ON u.id = dpa.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN instituciones ie ON ie.id = dpa.institucion_id
            WHERE dpa.tipo_pasantia = 'Corta'
            ORDER BY dpa.id DESC LIMIT 3
        ");
        $previewPasantes = $this->db->resultSet();

        // Preview: últimas 2 actividades SC
        $this->db->query("
            SELECT a.id, a.nombre, a.estado, a.fecha_inicio,
                   ie.nombre AS institucion_nombre,
                   COUNT(DISTINCT ap.id) AS total_participantes
            FROM actividades_extras a
            LEFT JOIN instituciones ie ON ie.id = a.institucion_id
            LEFT JOIN actividad_participantes ap ON ap.actividad_id = a.id
            WHERE a.tipo != 'Pasantía Corta'
            GROUP BY a.id ORDER BY a.id DESC LIMIT 2
        ");
        $previewActividades = $this->db->resultSet();

        // Distribución por tipo para donut chart
        $this->db->query("SELECT tipo, COUNT(*) AS total FROM actividades_extras GROUP BY tipo");
        $distribucionTipos = $this->db->resultSet();

        $this->view('actividades/index', array_merge($kpis, [
            'previewInstituciones' => $previewInstituciones,
            'previewPasantes'      => $previewPasantes,
            'previewActividades'   => $previewActividades,
            'distribucionTipos'    => $distribucionTipos,
        ]));
    }

    // =========================================================
    // GET /actividades/instituciones — Vista dedicada
    // =========================================================
    public function instituciones(): void
    {
        $kpis = $this->getKpis();

        $this->db->query("
            SELECT ie.*,
                COUNT(DISTINCT dpa.usuario_id) AS pasantes_activos
            FROM instituciones ie
            LEFT JOIN datos_pasante dpa ON dpa.institucion_id = ie.id AND dpa.estado_pasantia = 'Activo'
            WHERE ie.categoria = 'actividad'
            GROUP BY ie.id
            ORDER BY ie.nombre ASC
        ");
        $instituciones = $this->db->resultSet();

        // Conteo por tipo para donut
        $this->db->query("SELECT tipo, COUNT(*) AS total FROM instituciones WHERE categoria = 'actividad' GROUP BY tipo");
        $porTipo = $this->db->resultSet();

        $csrfToken = Session::generateCsrfToken();

        $this->view('actividades/instituciones', array_merge($kpis, [
            'instituciones' => $instituciones,
            'porTipo'       => $porTipo,
            'csrfToken'     => $csrfToken,
        ]));
    }

    // =========================================================
    // GET /actividades/pasantias — Vista dedicada pasantías cortas
    // =========================================================
    public function pasantias(): void
    {
        $kpis = $this->getKpis();

        // Pasantes cortos completos
        $this->db->query("
            SELECT
                u.id, u.correo, u.cedula, u.estado,
                dp.nombres, dp.apellidos, dp.cargo,
                dpa.estado_pasantia, dpa.periodo_id,
                COALESCE(NULLIF(dpa.horas_meta, 0), 1440) AS horas_meta,
                COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio_pasantia,
                COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin_estimada,
                d.nombre  AS departamento_nombre,
                ie.nombre AS institucion_nombre,
                CONCAT(COALESCE(tp.nombres,''), ' ', COALESCE(tp.apellidos,'')) AS tutor_nombre,
                pa.nombre AS periodo_nombre,
                COALESCE((SELECT COUNT(*) * 8
                          FROM asistencias a WHERE a.pasante_id = u.id
                          AND a.estado != 'Anulado' AND a.fecha <= CURDATE()), 0) AS horas_acumuladas
            FROM datos_pasante dpa
            JOIN usuarios u ON u.id = dpa.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            LEFT JOIN instituciones ie ON ie.id = dpa.institucion_id
            LEFT JOIN usuarios tu ON tu.id = dpa.tutor_id
            LEFT JOIN datos_personales tp ON tp.usuario_id = tu.id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            WHERE dpa.tipo_pasantia = 'Corta'
            ORDER BY dp.apellidos ASC
        ");
        $pasantesCortos = $this->db->resultSet();

        // Instituciones activas para modal nuevo pasante
        $this->db->query("SELECT id, nombre FROM instituciones WHERE categoria = 'actividad' AND activo = 1 ORDER BY nombre ASC");
        $instituciones = $this->db->resultSet();

        $this->db->query("
            SELECT u.id, CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_completo
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE u.rol_id IN (1, 2) AND u.estado = 'activo'
            ORDER BY COALESCE(dp.apellidos, u.correo) ASC
        ");
        $tutores = $this->db->resultSet();

        $this->db->query("SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
        $departamentos = $this->db->resultSet();

        // Todos los períodos Cortos (incluye Cerrados para edición de pasantes finalizados)
        $this->db->query("SELECT id, nombre, estado, fecha_inicio, fecha_fin FROM periodos_academicos WHERE tipo = 'Corto' ORDER BY fecha_inicio DESC");
        $periodos = $this->db->resultSet();

        // Estadísticas por estado para los KPIs de la vista
        $activos     = count(array_filter($pasantesCortos, fn($p) => ($p->estado_pasantia ?? '') === 'Activo'));
        $finalizados = count(array_filter($pasantesCortos, fn($p) => ($p->estado_pasantia ?? '') === 'Finalizado'));
        $totalH      = array_sum(array_map(fn($p) => (int)($p->horas_acumuladas ?? 0), $pasantesCortos));
        $metaH       = array_sum(array_map(fn($p) => max(1, (int)($p->horas_meta ?? 480)), $pasantesCortos));
        $promPct     = $metaH > 0 ? round($totalH / $metaH * 100) : 0;

        // Inasistencias del mes actual entre pasantes cortos activos
        $this->db->query("
            SELECT u.id, dp.nombres, dp.apellidos, u.cedula,
                   d.nombre AS departamento,
                   COUNT(a.id) AS total_ausentes,
                   (SELECT COUNT(*) FROM asistencias a2
                    WHERE a2.pasante_id=u.id AND a2.estado='Presente'
                      AND YEAR(a2.fecha)=YEAR(CURDATE()) AND MONTH(a2.fecha)=MONTH(CURDATE())) AS total_presentes,
                   ROUND(
                       (SELECT COUNT(*) FROM asistencias a2
                        WHERE a2.pasante_id=u.id AND a2.estado='Presente'
                          AND YEAR(a2.fecha)=YEAR(CURDATE()) AND MONTH(a2.fecha)=MONTH(CURDATE()))
                       / NULLIF(
                           (SELECT COUNT(*) FROM asistencias a3
                            WHERE a3.pasante_id=u.id
                              AND YEAR(a3.fecha)=YEAR(CURDATE()) AND MONTH(a3.fecha)=MONTH(CURDATE())), 0
                       ) * 100, 1) AS pct_asistencia
            FROM asistencias a
            JOIN usuarios u ON u.id = a.pasante_id
            JOIN datos_pasante dpa ON dpa.usuario_id = u.id AND dpa.tipo_pasantia = 'Corta' AND dpa.estado_pasantia = 'Activo'
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            WHERE a.estado = 'Ausente'
              AND YEAR(a.fecha) = YEAR(CURDATE()) AND MONTH(a.fecha) = MONTH(CURDATE())
            GROUP BY u.id, dp.nombres, dp.apellidos, u.cedula, d.nombre
            ORDER BY total_ausentes DESC, dp.apellidos ASC
        ");
        $ausentesMesCortos = $this->db->resultSet() ?: [];

        $csrfToken = Session::generateCsrfToken();

        // ── Datos de asistencia (redirigir a asistenciasCortas si hay ?vista=) ──
        $vistaAsist = $_GET['vista'] ?? null;
        $asistData  = [];
        if ($vistaAsist && in_array($vistaAsist, ['diaria','semanal','mensual','total'])) {
            $asistData = $this->_calcularAsistenciasCortas($vistaAsist);
        }

        $this->view('actividades/pasantias', array_merge($kpis, $asistData, [
            'pasantesCortos'    => $pasantesCortos,
            'instituciones'     => $instituciones,
            'tutores'           => $tutores,
            'departamentos'     => $departamentos,
            'periodos'          => $periodos,
            'statActivos'       => $activos,
            'statFinalizados'   => $finalizados,
            'statPromPct'       => $promPct,
            'ausentesMesCortos' => $ausentesMesCortos,
            'csrfToken'         => $csrfToken,
            'vistaAsist'        => $vistaAsist,
        ]));
    }

    // =========================================================
    // GET /actividades/servicio — Vista dedicada servicio comunitario
    // =========================================================
    public function servicio(): void
    {
        $kpis = $this->getKpis();

        $this->db->query("
            SELECT
                a.id, a.nombre, a.tipo, a.estado, a.fecha_inicio, a.fecha_fin, a.descripcion,
                ie.nombre AS institucion_nombre,
                COUNT(DISTINCT ap.id) AS total_participantes
            FROM actividades_extras a
            LEFT JOIN instituciones ie ON ie.id = a.institucion_id
            LEFT JOIN actividad_participantes ap ON ap.actividad_id = a.id
            WHERE a.tipo != 'Pasantía Corta'
            GROUP BY a.id
            ORDER BY a.fecha_inicio DESC
        ");
        $actividades = $this->db->resultSet();

        $this->db->query("SELECT id, nombre FROM instituciones WHERE categoria = 'actividad' AND activo = 1 ORDER BY nombre ASC");
        $instituciones = $this->db->resultSet();

        $statActivas    = count(array_filter($actividades, fn($a) => ($a->estado ?? '') === 'Activa'));
        $statFinalizadas= count(array_filter($actividades, fn($a) => ($a->estado ?? '') === 'Finalizada'));
        $statParticip   = array_sum(array_map(fn($a) => (int)($a->total_participantes ?? 0), $actividades));

        $csrfToken = Session::generateCsrfToken();

        $this->view('actividades/servicio', array_merge($kpis, [
            'actividades'     => $actividades,
            'instituciones'   => $instituciones,
            'statActivas'     => $statActivas,
            'statFinalizadas' => $statFinalizadas,
            'statParticip'    => $statParticip,
            'csrfToken'       => $csrfToken,
        ]));
    }

    // =========================================================
    // GET /actividades/ver/{id} — Detalle de actividad
    // =========================================================
    public function ver(int $id): void
    {
        // Actividad principal
        $this->db->query("
            SELECT
                a.*,
                ie.nombre   AS institucion_nombre,
                ie.tipo     AS institucion_tipo,
                CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS supervisor_nombre
            FROM actividades_extras a
            LEFT JOIN instituciones ie ON ie.id = a.institucion_id
            LEFT JOIN datos_personales dp       ON dp.usuario_id = a.supervisor_id
            WHERE a.id = :id
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        $actividad = $this->db->single();

        if (!$actividad) {
            Session::setFlash('error', 'Actividad no encontrada.');
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }

        // Participantes
        $this->db->query("
            SELECT * FROM actividad_participantes
            WHERE actividad_id = :id
            ORDER BY apellidos ASC, nombres ASC
        ");
        $this->db->bind(':id', $id);
        $participantes = $this->db->resultSet();

        // Asistencias del mes actual (para precarga)
        $mesActual = date('Y-m');
        $this->db->query("
            SELECT
                aa.participante_id,
                aa.fecha,
                aa.estado,
                aa.notas
            FROM actividad_asistencias aa
            WHERE aa.actividad_id = :id
              AND DATE_FORMAT(aa.fecha, '%Y-%m') = :mes
            ORDER BY aa.fecha ASC
        ");
        $this->db->bind(':id', $id);
        $this->db->bind(':mes', $mesActual);
        $asistenciasMes = $this->db->resultSet();

        // Asistencias indexadas por participante_id + fecha para JS
        $asistenciasMap = [];
        foreach ($asistenciasMes as $row) {
            $asistenciasMap[$row->participante_id][$row->fecha] = $row->estado;
        }

        // KPI asistencia global
        $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(estado = 'Presente')    AS presentes,
                SUM(estado = 'Ausente')     AS ausentes,
                SUM(estado = 'Justificado') AS justificados
            FROM actividad_asistencias
            WHERE actividad_id = :id
        ");
        $this->db->bind(':id', $id);
        $statsAsistencia = $this->db->single();

        // Días activa
        $diasActiva = 0;
        if ($actividad->fecha_inicio) {
            $fechaFin = $actividad->fecha_fin ? new DateTime($actividad->fecha_fin) : new DateTime();
            $diasActiva = (int) (new DateTime($actividad->fecha_inicio))->diff($fechaFin)->days;
        }

        // % asistencia
        $pctAsistencia = 0;
        if (!empty($statsAsistencia->total) && (int)$statsAsistencia->total > 0) {
            $pctAsistencia = round(((int)$statsAsistencia->presentes / (int)$statsAsistencia->total) * 100);
        }

        // Instituciones para edición
        $this->db->query("SELECT id, nombre FROM instituciones WHERE categoria = 'actividad' AND activo = 1 ORDER BY nombre ASC");
        $instituciones = $this->db->resultSet();

        // Supervisores para edición
        $this->db->query("
            SELECT u.id, CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_completo
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE u.rol_id IN (1, 2) AND u.estado = 'activo'
            ORDER BY COALESCE(dp.apellidos, u.correo) ASC
        ");
        $supervisores = $this->db->resultSet();

        // Evidencias fotográficas
        $this->db->query("
            SELECT id, ruta, nombre_orig, descripcion, created_at
            FROM actividad_evidencias
            WHERE actividad_id = :id
            ORDER BY created_at DESC
        ");
        $this->db->bind(':id', $id);
        $evidencias = $this->db->resultSet();

        $this->view('actividades/ver', [
            'actividad'      => $actividad,
            'participantes'  => $participantes,
            'asistenciasMap' => $asistenciasMap,
            'statsAsistencia'=> $statsAsistencia,
            'diasActiva'     => $diasActiva,
            'pctAsistencia'  => $pctAsistencia,
            'instituciones'  => $instituciones,
            'supervisores'   => $supervisores,
            'evidencias'     => $evidencias,
        ]);
    }

    // =========================================================
    // POST /actividades/crear
    // =========================================================
    public function crear(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido. Intenta de nuevo.');
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }

        $nombre       = trim($_POST['nombre'] ?? '');
        $tipo         = trim($_POST['tipo'] ?? 'Servicio Comunitario');
        $instId       = !empty($_POST['institucion_id']) ? (int)$_POST['institucion_id'] : null;
        $supervisorId = !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null;
        $descripcion  = trim($_POST['descripcion'] ?? '');
        $fechaInicio  = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin     = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;

        if (empty($nombre) || empty($fechaInicio)) {
            Session::setFlash('error', 'El nombre y la fecha de inicio son obligatorios.');
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }

        // [FIX-V-9] Antes solo se comprobaba que fecha_inicio no viniera vacía; cualquier
        // string no vacío (p.ej. "hola") se insertaba tal cual en una columna DATE.
        $fiObj = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        if (!$fiObj || $fiObj->format('Y-m-d') !== $fechaInicio) {
            Session::setFlash('error', 'La fecha de inicio no es válida.');
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }
        if ($fechaFin !== null) {
            $ffObj = DateTime::createFromFormat('Y-m-d', $fechaFin);
            if (!$ffObj || $ffObj->format('Y-m-d') !== $fechaFin) {
                Session::setFlash('error', 'La fecha de fin no es válida.');
                header('Location: ' . URLROOT . '/actividades');
                exit;
            }
            if ($fechaFin < $fechaInicio) {
                Session::setFlash('error', 'La fecha de fin no puede ser anterior a la fecha de inicio.');
                header('Location: ' . URLROOT . '/actividades');
                exit;
            }
        }

        $tiposValidos = ['Servicio Comunitario', 'Pasantía Corta', 'Mantenimiento', 'Otro'];
        if (!in_array($tipo, $tiposValidos)) {
            $tipo = 'Servicio Comunitario';
        }

        $this->db->query("
            INSERT INTO actividades_extras
                (nombre, tipo, institucion_id, supervisor_id, descripcion, fecha_inicio, fecha_fin, estado)
            VALUES
                (:nombre, :tipo, :inst_id, :sup_id, :desc, :fi, :ff, 'Activa')
        ");
        $this->db->bind(':nombre',  $nombre);
        $this->db->bind(':tipo',    $tipo);
        $this->db->bind(':inst_id', $instId);
        $this->db->bind(':sup_id',  $supervisorId);
        $this->db->bind(':desc',    $descripcion ?: null);
        $this->db->bind(':fi',      $fechaInicio);
        $this->db->bind(':ff',      $fechaFin);
        $this->db->execute();
        
        $actividadId = $this->db->lastInsertId();

        // Inserción automática de Pasante para Pasantías Cortas (Flujo Unified)
        if ($tipo === 'Pasantía Corta' && !empty($_POST['pasante_cedula']) && !empty($_POST['pasante_nombres'])) {
            $pCedula = trim($_POST['pasante_cedula']);
            $pNombres = trim($_POST['pasante_nombres']);
            $pApellidos = trim($_POST['pasante_apellidos'] ?? '');
            $pCarrera = trim($_POST['pasante_carrera'] ?? '');
            $this->db->query("
                INSERT INTO actividad_participantes
                    (actividad_id, nombres, apellidos, cedula, carrera)
                VALUES
                    (:aid, :nombres, :apellidos, :cedula, :carrera)
            ");
            $this->db->bind(':aid', $actividadId);
            $this->db->bind(':nombres', $pNombres);
            $this->db->bind(':apellidos', $pApellidos);
            $this->db->bind(':cedula', $pCedula);
            $this->db->bind(':carrera', $pCarrera ?: null);
            $this->db->execute();
        }

        // Insertar participantes enviados desde el modal (array de integrantes del grupo)
        $participantes = $_POST['participantes'] ?? [];
        $countPart = 0;
        foreach ($participantes as $part) {
            $pNombres   = trim($part['nombres']   ?? '');
            $pApellidos = trim($part['apellidos'] ?? '');
            $pCedula    = trim($part['cedula']    ?? '');
            if ($pNombres && $pApellidos && $pCedula) {
                $this->db->query("
                    INSERT IGNORE INTO actividad_participantes
                        (actividad_id, nombres, apellidos, cedula)
                    VALUES (:aid, :n, :a, :c)
                ");
                $this->db->bind(':aid', $actividadId);
                $this->db->bind(':n',   $pNombres);
                $this->db->bind(':a',   $pApellidos);
                $this->db->bind(':c',   $pCedula);
                $this->db->execute();
                $countPart++;
            }
        }

        $msgPart = $countPart > 0 ? " Se registraron {$countPart} integrante(s)." : ' Puedes agregar participantes desde el detalle.';
        Session::setFlash('success', "Actividad \"{$nombre}\" creada.{$msgPart}");
        if ($tipo === 'Pasantía Corta') {
            header('Location: ' . URLROOT . '/actividades/pasantias');
        } else {
            // Redirigir al detalle para agregar participantes de inmediato
            header('Location: ' . URLROOT . '/actividades/ver/' . $actividadId);
        }
        exit;
    }

    // =========================================================
    // POST /actividades/editar
    // =========================================================
    public function editar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido. Intenta de nuevo.');
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }

        $id           = (int)($_POST['id'] ?? 0);
        $nombre       = trim($_POST['nombre'] ?? '');
        $tipo         = trim($_POST['tipo'] ?? 'Servicio Comunitario');
        $instId       = !empty($_POST['institucion_id']) ? (int)$_POST['institucion_id'] : null;
        $supervisorId = !empty($_POST['supervisor_id']) ? (int)$_POST['supervisor_id'] : null;
        $descripcion  = trim($_POST['descripcion'] ?? '');
        $fechaInicio  = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin     = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
        $estado       = trim($_POST['estado'] ?? 'Activa');

        if ($id <= 0 || empty($nombre) || empty($fechaInicio)) {
            Session::setFlash('error', 'Datos incompletos.');
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }

        // [FIX-V-9] Misma validación de formato de fechas que crear()
        $fiObj = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        if (!$fiObj || $fiObj->format('Y-m-d') !== $fechaInicio) {
            Session::setFlash('error', 'La fecha de inicio no es válida.');
            header('Location: ' . URLROOT . '/actividades/ver/' . $id);
            exit;
        }
        if ($fechaFin !== null) {
            $ffObj = DateTime::createFromFormat('Y-m-d', $fechaFin);
            if (!$ffObj || $ffObj->format('Y-m-d') !== $fechaFin) {
                Session::setFlash('error', 'La fecha de fin no es válida.');
                header('Location: ' . URLROOT . '/actividades/ver/' . $id);
                exit;
            }
            if ($fechaFin < $fechaInicio) {
                Session::setFlash('error', 'La fecha de fin no puede ser anterior a la fecha de inicio.');
                header('Location: ' . URLROOT . '/actividades/ver/' . $id);
                exit;
            }
        }

        $estadosValidos = ['Activa', 'Finalizada', 'Cancelada'];
        if (!in_array($estado, $estadosValidos)) $estado = 'Activa';

        $this->db->query("
            UPDATE actividades_extras
            SET nombre = :nombre, tipo = :tipo, institucion_id = :inst_id,
                supervisor_id = :sup_id, descripcion = :desc,
                fecha_inicio = :fi, fecha_fin = :ff, estado = :estado
            WHERE id = :id
        ");
        $this->db->bind(':nombre',  $nombre);
        $this->db->bind(':tipo',    $tipo);
        $this->db->bind(':inst_id', $instId);
        $this->db->bind(':sup_id',  $supervisorId);
        $this->db->bind(':desc',    $descripcion ?: null);
        $this->db->bind(':fi',      $fechaInicio);
        $this->db->bind(':ff',      $fechaFin);
        $this->db->bind(':estado',  $estado);
        $this->db->bind(':id',      $id);
        $this->db->execute();

        Session::setFlash('success', 'Actividad actualizada correctamente.');
        header('Location: ' . URLROOT . '/actividades/ver/' . $id);
        exit;
    }

    // =========================================================
    // POST /actividades/agregarParticipante  (AJAX → JSON)
    // =========================================================
    public function agregarParticipante(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        // CSRF desde header X-CSRF-TOKEN o campo POST
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $actividadId = (int)($_POST['actividad_id'] ?? 0);
        $nombres     = trim($_POST['nombres'] ?? '');
        $apellidos   = trim($_POST['apellidos'] ?? '');
        $cedula      = trim($_POST['cedula'] ?? '');
        $carrera     = trim($_POST['carrera'] ?? '');
        $telefono    = trim($_POST['telefono'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($actividadId <= 0 || empty($nombres) || empty($apellidos) || empty($cedula)) {
            echo json_encode(['success' => false, 'message' => 'Nombres, apellidos y cédula son obligatorios.']);
            exit;
        }

        // [FIX-V-14] Antes se aceptaba cualquier string no vacío como cédula (incluso
        // letras o longitud arbitraria). Los participantes pueden venir de
        // instituciones externas, así que se admite un formato algo más flexible
        // que el de usuarios internos, pero siempre alfanumérico y acotado en longitud.
        if (!preg_match('/^[A-Za-z0-9\-]{5,15}$/', $cedula)) {
            echo json_encode(['success' => false, 'message' => 'La cédula debe tener entre 5 y 15 caracteres alfanuméricos.']);
            exit;
        }
        if (strlen($nombres) > 100 || strlen($apellidos) > 100) {
            echo json_encode(['success' => false, 'message' => 'Nombres/apellidos no pueden superar 100 caracteres.']);
            exit;
        }

        // Verificar que la actividad existe
        $this->db->query("SELECT id FROM actividades_extras WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $actividadId);
        if (!$this->db->single()) {
            echo json_encode(['success' => false, 'message' => 'Actividad no encontrada.']);
            exit;
        }

        // Cédula duplicada en la misma actividad
        $this->db->query("
            SELECT id FROM actividad_participantes
            WHERE actividad_id = :aid AND cedula = :cedula LIMIT 1
        ");
        $this->db->bind(':aid',    $actividadId);
        $this->db->bind(':cedula', $cedula);
        if ($this->db->single()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un participante con esa cédula en esta actividad.']);
            exit;
        }

        $this->db->query("
            INSERT INTO actividad_participantes
                (actividad_id, nombres, apellidos, cedula, carrera, telefono, observaciones)
            VALUES
                (:aid, :nombres, :apellidos, :cedula, :carrera, :tel, :obs)
        ");
        $this->db->bind(':aid',      $actividadId);
        $this->db->bind(':nombres',  $nombres);
        $this->db->bind(':apellidos',$apellidos);
        $this->db->bind(':cedula',   $cedula);
        $this->db->bind(':carrera',  $carrera ?: null);
        $this->db->bind(':tel',      $telefono ?: null);
        $this->db->bind(':obs',      $observaciones ?: null);
        $this->db->execute();

        // Devolver el nuevo participante para actualizar la tabla sin recargar
        $this->db->query("
            SELECT * FROM actividad_participantes
            WHERE actividad_id = :aid AND cedula = :cedula ORDER BY id DESC LIMIT 1
        ");
        $this->db->bind(':aid',    $actividadId);
        $this->db->bind(':cedula', $cedula);
        $nuevo = $this->db->single();

        echo json_encode([
            'success'      => true,
            'message'      => 'Participante agregado correctamente.',
            'participante' => $nuevo,
        ]);
        exit;
    }

    // =========================================================
    // POST /actividades/eliminarParticipante  (AJAX → JSON)
    // =========================================================
    // =========================================================
    // GET /actividades/verificarCedula?cedula=X[&actividad_id=Y]
    // Verifica si la cédula ya existe en actividad_participantes
    // =========================================================
    public function verificarCedula(): void
    {
        header('Content-Type: application/json');
        $cedula      = trim($_GET['cedula']      ?? '');
        $actividadId = (int)($_GET['actividad_id'] ?? 0);

        if (empty($cedula)) {
            echo json_encode(['existe' => false]); exit;
        }

        if ($actividadId > 0) {
            // Verificar en la actividad específica (desde la vista detalle)
            $this->db->query("
                SELECT id, nombres, apellidos, actividad_id
                FROM actividad_participantes
                WHERE cedula = :ced AND actividad_id = :aid LIMIT 1
            ");
            $this->db->bind(':ced', $cedula);
            $this->db->bind(':aid', $actividadId);
        } else {
            // Verificar en cualquier actividad (desde el modal de creación)
            $this->db->query("
                SELECT ap.id, ap.nombres, ap.apellidos, ae.nombre AS actividad_nombre
                FROM actividad_participantes ap
                JOIN actividades_extras ae ON ae.id = ap.actividad_id
                WHERE ap.cedula = :ced LIMIT 1
            ");
            $this->db->bind(':ced', $cedula);
        }

        $row = $this->db->single();
        if ($row) {
            $msg = $actividadId > 0
                ? "Ya registrado en esta actividad."
                : "Ya registrado en: " . ($row->actividad_nombre ?? 'otra actividad') . ".";
            echo json_encode(['existe' => true, 'mensaje' => $msg,
                              'nombre' => ($row->nombres ?? '') . ' ' . ($row->apellidos ?? '')]);
        } else {
            echo json_encode(['existe' => false]);
        }
        exit;
    }

    public function eliminarParticipante(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
        }
        $partId = (int)($_POST['participante_id'] ?? 0);
        if ($partId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de participante inválido.']); exit;
        }
        $this->db->query("DELETE FROM actividad_participantes WHERE id = :id");
        $this->db->bind(':id', $partId);
        $this->db->execute();
        echo json_encode(['success' => true, 'message' => 'Participante eliminado.']);
        exit;
    }

    // =========================================================
    // POST /actividades/editarParticipante  (AJAX → JSON)
    // =========================================================
    public function editarParticipante(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
        }
        $partId    = (int)($_POST['participante_id'] ?? 0);
        $nombres   = trim($_POST['nombres']   ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $carrera   = trim($_POST['carrera']   ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        if ($partId <= 0 || empty($nombres) || empty($apellidos)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']); exit;
        }
        $this->db->query("
            UPDATE actividad_participantes
            SET nombres = :n, apellidos = :a, carrera = :c, telefono = :t
            WHERE id = :id
        ");
        $this->db->bind(':n',  $nombres);
        $this->db->bind(':a',  $apellidos);
        $this->db->bind(':c',  $carrera   ?: null);
        $this->db->bind(':t',  $telefono  ?: null);
        $this->db->bind(':id', $partId);
        $this->db->execute();
        echo json_encode(['success' => true, 'message' => 'Participante actualizado.']);
        exit;
    }

    // =========================================================
    // POST /actividades/subirEvidencia  (AJAX multipart → JSON)
    // =========================================================
    public function subirEvidencia(): void
    {
        header('Content-Type: application/json');
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
        }
        $actividadId = (int)($_POST['actividad_id'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        if ($actividadId <= 0 || empty($_FILES['archivo']['tmp_name'])) {
            echo json_encode(['success' => false, 'message' => 'Actividad o archivo no especificado.']); exit;
        }
        $file     = $_FILES['archivo'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','gif','webp','pdf'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Formato no permitido. Usa JPG, PNG o PDF.']); exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'El archivo supera los 5 MB.']); exit;
        }
        // [FIX-V-4] Validar el tipo MIME real (no confiar en la extensión del nombre).
        $finfoAct = new finfo(FILEINFO_MIME_TYPE);
        $mimeAct  = $finfoAct->file($file['tmp_name']);
        $mimesPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (!in_array($mimeAct, $mimesPermitidos, true)) {
            echo json_encode(['success' => false, 'message' => 'El contenido del archivo no corresponde a una imagen o PDF válido.']); exit;
        }
        // [FIX-ED-2] Fuera de public/ — antes cualquiera con la URL podía ver estas
        // fotos/documentos sin sesión. Ahora se sirven solo vía verEvidencia().
        $dir = APPROOT . '/storage/uploads/evidencias_actividad/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $nombre = 'act' . $actividadId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $nombre)) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo.']); exit;
        }
        $this->db->query("
            INSERT INTO actividad_evidencias (actividad_id, ruta, nombre_orig, descripcion)
            VALUES (:aid, :ruta, :orig, :desc)
        ");
        $this->db->bind(':aid',  $actividadId);
        // Se guarda solo el nombre — filas antiguas con prefijo "storage/evidencias/"
        // siguen resolviendo bien porque verEvidencia() y la vista usan basename().
        $this->db->bind(':ruta', $nombre);
        $this->db->bind(':orig', $file['name']);
        $this->db->bind(':desc', $descripcion ?: null);
        $this->db->execute();
        echo json_encode(['success' => true, 'message' => 'Evidencia subida correctamente.']);
        exit;
    }

    // ────────────────────────────────────────────────────────────────
    // [FIX-ED-2] GET /actividades/verEvidencia/{filename}
    // Sirve una evidencia de actividad solo a Admin/Tutor/SuperAdmin
    // autenticados (mismo criterio de rol que el resto del módulo —
    // ver nota de diseño de AC-6: este módulo no aísla por departamento
    // a propósito, es gestión compartida de brigadas institucionales).
    // ────────────────────────────────────────────────────────────────
    public function verEvidencia(string $filename = ''): void
    {
        $filename = basename($filename);
        if (!preg_match('/^[A-Za-z0-9_\-]+\.(jpg|jpeg|png|gif|webp|pdf)$/i', $filename)) {
            App::renderError(404);
            return;
        }

        $this->db->query("
            SELECT id FROM actividad_evidencias
            WHERE ruta = :f1 OR ruta LIKE :f2
            LIMIT 1
        ");
        $this->db->bind(':f1', $filename);
        $this->db->bind(':f2', '%/' . $filename);
        if (!$this->db->single()) {
            App::renderError(404);
            return;
        }

        $filepath = APPROOT . '/storage/uploads/evidencias_actividad/' . $filename;
        if (!file_exists($filepath)) {
            App::renderError(404);
            return;
        }

        $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                  'gif' => 'image/gif', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, no-store');
        readfile($filepath);
        exit;
    }

    // =========================================================
    // POST /actividades/registrarAsistencia  (AJAX → JSON)
    // =========================================================
    public function registrarAsistencia(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $actividadId    = (int)($_POST['actividad_id'] ?? 0);
        $participanteId = (int)($_POST['participante_id'] ?? 0);
        $fecha          = trim($_POST['fecha'] ?? '');
        $estado         = trim($_POST['estado'] ?? 'Presente');
        $notas          = trim($_POST['notas'] ?? '');

        if ($actividadId <= 0 || $participanteId <= 0 || empty($fecha)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            exit;
        }

        $estadosValidos = ['Presente', 'Ausente', 'Justificado'];
        if (!in_array($estado, $estadosValidos)) {
            echo json_encode(['success' => false, 'message' => 'Estado de asistencia inválido.']);
            exit;
        }

        // Validar formato de fecha
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt || $dt->format('Y-m-d') !== $fecha) {
            echo json_encode(['success' => false, 'message' => 'Fecha inválida.']);
            exit;
        }

        // INSERT ... ON DUPLICATE KEY UPDATE
        $this->db->query("
            INSERT INTO actividad_asistencias
                (actividad_id, participante_id, fecha, estado, notas)
            VALUES
                (:aid, :pid, :fecha, :estado, :notas)
            ON DUPLICATE KEY UPDATE
                estado = VALUES(estado),
                notas  = VALUES(notas)
        ");
        $this->db->bind(':aid',    $actividadId);
        $this->db->bind(':pid',    $participanteId);
        $this->db->bind(':fecha',  $fecha);
        $this->db->bind(':estado', $estado);
        $this->db->bind(':notas',  $notas ?: null);
        $this->db->execute();

        echo json_encode([
            'success' => true,
            'message' => "Asistencia registrada: {$estado}",
            'estado'  => $estado,
            'fecha'   => $fecha,
        ]);
        exit;
    }

    // =========================================================
    // POST /actividades/crearInstitucion
    // =========================================================
    public function crearInstitucion(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $nombre   = trim($_POST['nombre'] ?? '');
        $tipo     = trim($_POST['tipo'] ?? 'Universidad');
        $contacto = trim($_POST['contacto'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre es obligatorio.']);
            exit;
        }

        $tiposValidos = ['Universidad', 'Instituto', 'Colegio Técnico', 'Otro'];
        if (!in_array($tipo, $tiposValidos)) $tipo = 'Universidad';

        $this->db->query("
            INSERT INTO instituciones (nombre, tipo, contacto, telefono, activo, categoria)
            VALUES (:nombre, :tipo, :contacto, :telefono, 1, 'actividad')
        ");
        $this->db->bind(':nombre',   $nombre);
        $this->db->bind(':tipo',     $tipo);
        $this->db->bind(':contacto', $contacto ?: null);
        $this->db->bind(':telefono', $telefono ?: null);
        $this->db->execute();

        $id = $this->db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => "Institución \"{$nombre}\" registrada correctamente.",
            'institucion' => [
                'id'       => $id,
                'nombre'   => $nombre,
                'tipo'     => $tipo,
                'contacto' => $contacto,
                'telefono' => $telefono,
                'activo'   => 1,
            ]
        ]);
        exit;
    }

    // =========================================================
    // POST /actividades/editarInstitucion (AJAX)
    // =========================================================
    public function editarInstitucion(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $id       = (int)($_POST['id'] ?? 0);
        $nombre   = trim($_POST['nombre'] ?? '');
        $tipo     = trim($_POST['tipo'] ?? 'Universidad');
        $contacto = trim($_POST['contacto'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if ($id <= 0 || empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos o ID inválido.']);
            exit;
        }

        $tiposValidos = ['Universidad', 'Instituto', 'Colegio Técnico', 'Otro'];
        if (!in_array($tipo, $tiposValidos)) $tipo = 'Universidad';

        $this->db->query("
            UPDATE instituciones
            SET nombre = :nombre, tipo = :tipo, contacto = :contacto, telefono = :telefono
            WHERE id = :id AND categoria = 'actividad'
        ");
        $this->db->bind(':id',       $id);
        $this->db->bind(':nombre',   $nombre);
        $this->db->bind(':tipo',     $tipo);
        $this->db->bind(':contacto', $contacto ?: null);
        $this->db->bind(':telefono', $telefono ?: null);
        
        if ($this->db->execute()) {
            echo json_encode([
                'success' => true,
                'institucion' => [
                    'id'       => $id,
                    'nombre'   => $nombre,
                    'tipo'     => $tipo,
                    'contacto' => $contacto,
                    'telefono' => $telefono
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar en la base de datos.']);
        }
        exit;
    }

    // =========================================================
    // POST /actividades/toggleInstitucion  (AJAX → JSON)
    // =========================================================
    public function toggleInstitucion(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $this->db->query("SELECT activo FROM instituciones WHERE id = :id AND categoria = 'actividad' LIMIT 1");
        $this->db->bind(':id', $id);
        $inst = $this->db->single();

        if (!$inst) {
            echo json_encode(['success' => false, 'message' => 'Institución no encontrada.']);
            exit;
        }

        $nuevoEstado = $inst->activo ? 0 : 1;
        $this->db->query("UPDATE instituciones SET activo = :estado WHERE id = :id AND categoria = 'actividad'");
        $this->db->bind(':estado', $nuevoEstado);
        $this->db->bind(':id',     $id);
        $this->db->execute();

        echo json_encode([
            'success' => true,
            'activo'  => $nuevoEstado,
            'message' => $nuevoEstado ? 'Institución activada.' : 'Institución desactivada.',
        ]);
        exit;
    }

    // =========================================================
    // GET /actividades/verPasante/{userId} — Almanaque pasante corto
    // =========================================================
    public function verPasante(int $userId): void
    {
        $this->db->query("
            SELECT
                u.id, u.cedula, u.correo,
                dp.nombres, dp.apellidos, dp.cargo AS carrera,
                dpa.estado_pasantia,
                COALESCE(NULLIF(dpa.horas_meta, 0),
                         ROUND(DATEDIFF(COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin),
                                        COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio)) * 5/7) * 8,
                         1440) AS horas_meta,
                COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio_pasantia,
                COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin_estimada,
                d.nombre   AS departamento_nombre,
                ie.nombre  AS institucion_nombre,
                pa.nombre  AS periodo_nombre,
                CONCAT(COALESCE(tp.nombres,''), ' ', COALESCE(tp.apellidos,'')) AS tutor_nombre,
                COALESCE((SELECT COUNT(*) * 8
                          FROM asistencias a WHERE a.pasante_id = u.id
                          AND a.estado != 'Anulado' AND a.fecha <= CURDATE()), 0) AS horas_acumuladas
            FROM usuarios u
            LEFT JOIN datos_personales dp  ON dp.usuario_id  = u.id
            LEFT JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            LEFT JOIN departamentos    d   ON d.id = dpa.departamento_asignado_id
            LEFT JOIN instituciones    ie  ON ie.id = dpa.institucion_id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT JOIN usuarios         tu  ON tu.id = dpa.tutor_id
            LEFT JOIN datos_personales tp  ON tp.usuario_id = tu.id
            WHERE u.id = :uid AND dpa.tipo_pasantia = 'Corta'
            LIMIT 1
        ");
        $this->db->bind(':uid', $userId);
        $pasante = $this->db->single();

        if (!$pasante) {
            Session::setFlash('error', 'Pasante corto no encontrado.');
            header('Location: ' . URLROOT . '/actividades/pasantias');
            exit;
        }

        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

        $this->db->query("
            SELECT fecha, hora_registro, estado, motivo_justificacion, metodo, es_auto_fill
            FROM asistencias
            WHERE pasante_id = :pid AND YEAR(fecha) = :anio
            ORDER BY fecha ASC
        ");
        $this->db->bind(':pid',  $userId);
        $this->db->bind(':anio', $anio);
        $registros = $this->db->resultSet();

        $registroMap = [];
        foreach ($registros as $r) {
            $registroMap[$r->fecha] = $r;
        }

        $stats = ['P' => 0, 'A' => 0, 'J' => 0, 'laborables' => 0];
        foreach ($registros as $r) {
            $dn = (int)date('N', strtotime($r->fecha));
            if ($dn <= 5) {
                $stats['laborables']++;
                if     ($r->estado === 'Presente')    $stats['P']++;
                elseif ($r->estado === 'Ausente')     $stats['A']++;
                elseif ($r->estado === 'Justificado') $stats['J']++;
            }
        }
        $pct = $stats['laborables'] > 0 ? round($stats['P'] / $stats['laborables'] * 100, 1) : 0.0;

        $semOffset = isset($_GET['sem']) ? (int)$_GET['sem'] : null;
        if ($semOffset === null && $pasante->estado_pasantia === 'Finalizado' && $pasante->fecha_fin_estimada) {
            $lunesFin = new DateTime($pasante->fecha_fin_estimada);
            $lunesFin->modify('monday this week');
            $lunesHoy = new DateTime('monday this week');
            $semOffset = -(int)floor($lunesHoy->diff($lunesFin)->days / 7);
            if ($lunesFin < $lunesHoy) $semOffset = -abs($semOffset);
        }
        $semOffset = $semOffset ?? 0;
        $lunes = new DateTime('monday this week');
        if ($semOffset !== 0) $lunes->modify("{$semOffset} week");

        $diasSemana = [];
        for ($i = 0; $i < 5; $i++) {
            $d    = clone $lunes;
            $d->modify("+{$i} day");
            $fStr = $d->format('Y-m-d');
            $diasSemana[] = [
                'fecha'  => $fStr,
                'diaNom' => $d->format('l'),
                'diaNum' => $d->format('d/m'),
                'estado' => $registroMap[$fStr]->estado ?? null,
                'notas'  => $registroMap[$fStr]->notas  ?? null,
            ];
        }
        $viernesLabel = (clone $lunes)->modify('+4 day');
        $baseUrl = URLROOT . '/actividades/verPasante/' . $userId;

        $fInicio = $pasante->fecha_inicio_pasantia ?? null;
        $fFin    = $pasante->fecha_fin_estimada ?? null;
        $lunesAnt = (clone $lunes)->modify('-1 week');
        $viernesSig = (clone $lunes)->modify('+1 week +4 days');
        $antDisabled = ($fInicio && $lunesAnt->format('Y-m-d') < $fInicio);
        $sigDisabled = ($fFin && $viernesSig->format('Y-m-d') > $fFin);

        $navSemana = [
            'ant_url'  => $antDisabled ? '#' : $baseUrl . '?anio=' . $anio . '&sem=' . ($semOffset - 1),
            'sig_url'  => $sigDisabled ? '#' : $baseUrl . '?anio=' . $anio . '&sem=' . ($semOffset + 1),
            'semLabel' => $lunes->format('d/m') . ' – ' . $viernesLabel->format('d/m/Y'),
            'esActual' => $semOffset === 0,
        ];

        $mesInicioPas = $fInicio ? (int)(new DateTime($fInicio))->format('n') : 1;
        $mesFinPas    = $fFin    ? (int)(new DateTime($fFin))->format('n')    : 12;
        $anioInicio   = $fInicio ? (int)(new DateTime($fInicio))->format('Y') : $anio;
        $anioFin      = $fFin    ? (int)(new DateTime($fFin))->format('Y')    : $anio;
        $mDesde = ($anio === $anioInicio) ? $mesInicioPas : 1;
        $mHasta = ($anio === $anioFin)    ? $mesFinPas    : 12;

        $grillaMesMeta = [];
        for ($m = $mDesde; $m <= $mHasta; $m++) {
            $primerDia = new DateTime("{$anio}-{$m}-01");
            $ultimoDia = (int)$primerDia->format('t');
            $inicioSem = (int)$primerDia->format('N') - 1;
            $celdas    = array_fill(0, $inicioSem, null);
            $mStats    = ['P' => 0, 'A' => 0, 'J' => 0];

            for ($d = 1; $d <= $ultimoDia; $d++) {
                $fStr = sprintf('%04d-%02d-%02d', $anio, $m, $d);
                $dn   = (int)(new DateTime($fStr))->format('N');
                $est  = $registroMap[$fStr]->estado ?? null;
                $celdas[] = ['fecha' => $fStr, 'dia' => $d, 'estado' => $est, 'esFinde' => $dn >= 6];
                if ($est) { $k = substr($est, 0, 1); if (isset($mStats[$k])) $mStats[$k]++; }
            }

            $grillaMesMeta[$m] = [
                'nombre' => $primerDia->format('F'),
                'dias'   => $celdas,
                'stats'  => $mStats,
                'tiene'  => array_sum($mStats) > 0,
            ];
        }

        $this->db->query("SELECT DISTINCT YEAR(fecha) AS anio FROM asistencias WHERE pasante_id = :pid ORDER BY anio DESC");
        $this->db->bind(':pid', $userId);
        $aniosRows = $this->db->resultSet();
        $anios = array_map(fn($r) => (int)$r->anio, $aniosRows);
        if (!in_array($anio, $anios)) $anios[] = $anio;
        rsort($anios);

        $this->db->query("SELECT fecha, hora_registro, estado, motivo_justificacion FROM asistencias WHERE pasante_id = :pid ORDER BY fecha DESC LIMIT 100");
        $this->db->bind(':pid', $userId);
        $historial = $this->db->resultSet();

        // Evaluaciones registradas para este pasante corto
        $this->db->query("
            SELECT e.id, e.fecha_evaluacion, e.lapso_academico, e.promedio_final, e.observaciones,
                   CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS tutor_nombre
            FROM evaluaciones e
            LEFT JOIN datos_personales dp ON dp.usuario_id = e.tutor_id
            WHERE e.pasante_id = :pid
            ORDER BY e.fecha_evaluacion DESC
        ");
        $this->db->bind(':pid', $userId);
        $evaluaciones = $this->db->resultSet();

        // Inasistencias del mes actual
        $this->db->query("
            SELECT COUNT(*) AS total FROM asistencias
            WHERE pasante_id = :pid AND estado = 'Ausente'
              AND YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE())
        ");
        $this->db->bind(':pid', $userId);
        $ausentesMes = (int)($this->db->single()->total ?? 0);

        $this->view('actividades/ver_pasante', [
            'pasante'       => $pasante,
            'anio'          => $anio,
            'anios'         => $anios,
            'grillaMesMeta' => $grillaMesMeta,
            'diasSemana'    => $diasSemana,
            'navSemana'     => $navSemana,
            'stats'         => $stats,
            'pct'           => $pct,
            'historial'     => $historial,
            'evaluaciones'  => $evaluaciones,
            'ausentesMes'   => $ausentesMes,
        ]);
    }

    // =========================================================
    // POST /actividades/registrarEvaluacionCorta  (AJAX → JSON)
    // =========================================================
    public function registrarEvaluacionCorta(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
        }

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        $tutorId   = (int)Session::get('user_id');
        $fecha     = trim($_POST['fecha']  ?? date('Y-m-d'));
        $lapso     = trim($_POST['lapso']  ?? '');
        $obs       = trim($_POST['observaciones'] ?? '');

        if (!$pasanteId) {
            echo json_encode(['success' => false, 'message' => 'Pasante no especificado.']); exit;
        }

        // Verificar que es pasante corto
        $this->db->query("SELECT id, departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid AND tipo_pasantia = 'Corta' LIMIT 1");
        $this->db->bind(':pid', $pasanteId);
        $dpCorta = $this->db->single();
        if (!$dpCorta) {
            echo json_encode(['success' => false, 'message' => 'Pasante no encontrado.']); exit;
        }

        // Tutor: solo puede evaluar pasantes de su departamento
        if ((int)Session::get('role_id') === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $tutorId);
            $tutorDept = (int)($this->db->single()->departamento_id ?? 0);
            if ($tutorDept !== (int)($dpCorta->departamento_asignado_id ?? -1)) {
                echo json_encode(['success' => false, 'message' => 'No puede evaluar pasantes fuera de su departamento.']); exit;
            }
        }

        // Verificar duplicados: solo una evaluación por pasante por lapso
        if ($lapso) {
            $this->db->query("SELECT id FROM evaluaciones WHERE pasante_id = :pid AND lapso_academico = :lapso LIMIT 1");
            $this->db->bind(':pid', $pasanteId);
            $this->db->bind(':lapso', $lapso);
            if ($this->db->single()) {
                echo json_encode(['success' => false, 'message' => "Ya existe una evaluación para este pasante en el lapso \"$lapso\". Edite la existente."]); exit;
            }
        }

        $criterios = [
            'criterio_iniciativa','criterio_interes','criterio_conocimiento','criterio_analisis',
            'criterio_comunicacion','criterio_aprendizaje','criterio_companerismo','criterio_cooperacion',
            'criterio_puntualidad','criterio_presentacion','criterio_desarrollo','criterio_analisis_res',
            'criterio_conclusiones','criterio_recomendacion',
        ];
        $vals = []; $suma = 0;
        foreach ($criterios as $c) {
            $v = (int)($_POST[$c] ?? 0);
            if ($v < 1 || $v > 5) {
                echo json_encode(['success' => false, 'message' => "Criterio inválido: $c (1-5)"]); exit;
            }
            $vals[$c] = $v; $suma += $v;
        }
        $promedio = round($suma / count($criterios), 2);

        $cols  = implode(',', $criterios);
        $marks = ':' . implode(',:', $criterios);
        $this->db->query("
            INSERT INTO evaluaciones (pasante_id, tutor_id, fecha_evaluacion, lapso_academico,
                promedio_final, observaciones, $cols)
            VALUES (:pid, :tid, :fecha, :lapso, :prom, :obs, $marks)
        ");
        $this->db->bind(':pid',   $pasanteId);
        $this->db->bind(':tid',   $tutorId);
        $this->db->bind(':fecha', $fecha);
        $this->db->bind(':lapso', $lapso ?: null);
        $this->db->bind(':prom',  $promedio);
        $this->db->bind(':obs',   $obs ?: null);
        foreach ($vals as $k => $v) $this->db->bind(':' . $k, $v, PDO::PARAM_INT);

        if ($this->db->execute()) {
            echo json_encode(['success' => true, 'message' => "Evaluación registrada. Promedio: $promedio / 5.00", 'promedio' => $promedio]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la evaluación.']);
        }
        exit;
    }

    // =========================================================
    // GET /actividades/pdfPasanteCorto/{id}
    // =========================================================
    public function pdfPasanteCorto(int $userId = 0): void
    {
        if (!$userId) { header('Location: ' . URLROOT . '/actividades/pasantias'); exit; }

        $this->db->query("
            SELECT u.id, u.cedula, dp.nombres, dp.apellidos, dp.cargo AS carrera,
                   dpa.horas_meta, dpa.fecha_inicio_pasantia AS fecha_inicio, dpa.fecha_fin_estimada AS fecha_fin,
                   dpa.estado_pasantia, d.nombre AS departamento,
                   ie.nombre AS institucion, pa.nombre AS periodo,
                   CONCAT(COALESCE(tp.nombres,''),' ',COALESCE(tp.apellidos,'')) AS tutor_nombre,
                   COALESCE((SELECT COUNT(*) * 8
                             FROM asistencias a WHERE a.pasante_id=u.id
                             AND a.estado != 'Anulado' AND a.fecha <= CURDATE()),0) AS horas_acumuladas
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id=u.id
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id=u.id
            LEFT JOIN departamentos d ON d.id=dpa.departamento_asignado_id
            LEFT JOIN instituciones ie ON ie.id=dpa.institucion_id
            LEFT JOIN periodos_academicos pa ON pa.id=dpa.periodo_id
            LEFT JOIN usuarios tu ON tu.id=dpa.tutor_id
            LEFT JOIN datos_personales tp ON tp.usuario_id=tu.id
            WHERE u.id=:uid AND dpa.tipo_pasantia='Corta' LIMIT 1
        ");
        $this->db->bind(':uid', $userId);
        $pasante = $this->db->single();
        if (!$pasante) { header('Location: ' . URLROOT . '/actividades/pasantias'); exit; }

        // Asistencias completas
        $this->db->query("
            SELECT fecha, hora_registro, estado, motivo_justificacion, es_auto_fill
            FROM asistencias WHERE pasante_id=:pid ORDER BY fecha ASC
        ");
        $this->db->bind(':pid', $userId);
        $asistencias = $this->db->resultSet();

        // Evaluaciones
        $this->db->query("
            SELECT e.fecha_evaluacion, e.lapso_academico, e.promedio_final,
                   CONCAT(COALESCE(dp.nombres,''),' ',COALESCE(dp.apellidos,'')) AS tutor_nombre
            FROM evaluaciones e
            LEFT JOIN datos_personales dp ON dp.usuario_id=e.tutor_id
            WHERE e.pasante_id=:pid ORDER BY e.fecha_evaluacion DESC
        ");
        $this->db->bind(':pid', $userId);
        $evaluaciones = $this->db->resultSet();

        require_once APPROOT . '/../vendor/autoload.php';
        $opts = new \Dompdf\Options();
        $opts->set('isHtml5ParserEnabled', true);
        $opts->setDefaultFont('Helvetica');
        $pdf = new \Dompdf\Dompdf($opts);
        ob_start();
        require APPROOT . '/views/actividades/pdf_pasante_corto.php';
        $html = ob_get_clean();
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $pdf->stream('Pasante_Corto_' . ($pasante->cedula ?? $userId) . '.pdf', ['Attachment' => false]);
        exit;
    }

    // =========================================================
    // GET /actividades/participante/{id} — Vista individual pasante
    // =========================================================
    public function participante(int $id): void
    {
        $this->db->query("
            SELECT
                ap.*,
                ae.nombre   AS actividad_nombre,
                ae.tipo     AS actividad_tipo,
                ae.estado   AS actividad_estado,
                ae.id       AS actividad_id,
                ie.nombre   AS institucion_nombre
            FROM actividad_participantes ap
            JOIN actividades_extras ae       ON ae.id = ap.actividad_id
            LEFT JOIN instituciones ie ON ie.id = ae.institucion_id
            WHERE ap.id = :id
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        $participante = $this->db->single();

        if (!$participante) {
            Session::setFlash('error', 'Participante no encontrado.');
            header('Location: ' . URLROOT . '/actividades');
            exit;
        }

        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

        $this->db->query("
            SELECT fecha, estado, notas
            FROM actividad_asistencias
            WHERE participante_id = :pid
              AND YEAR(fecha) = :anio
            ORDER BY fecha ASC
        ");
        $this->db->bind(':pid', $id);
        $this->db->bind(':anio', $anio);
        $registros = $this->db->resultSet();

        $registroMap = [];
        foreach ($registros as $r) {
            $registroMap[$r->fecha] = $r;
        }

        // Stats del año
        $stats = ['P' => 0, 'A' => 0, 'J' => 0, 'laborables' => 0];
        foreach ($registros as $r) {
            $dn = (int)date('N', strtotime($r->fecha));
            if ($dn <= 5) {
                $stats['laborables']++;
                if     ($r->estado === 'Presente')    $stats['P']++;
                elseif ($r->estado === 'Ausente')     $stats['A']++;
                elseif ($r->estado === 'Justificado') $stats['J']++;
            }
        }
        $pct = $stats['laborables'] > 0
            ? round($stats['P'] / $stats['laborables'] * 100, 1)
            : 0.0;

        // Semana (offset 0 = semana actual)
        $semOffset = isset($_GET['sem']) ? (int)$_GET['sem'] : 0;
        $lunes = new DateTime('monday this week');
        if ($semOffset !== 0) $lunes->modify("{$semOffset} week");

        $diasSemana = [];
        for ($i = 0; $i < 5; $i++) {
            $d    = clone $lunes;
            $d->modify("+{$i} day");
            $fStr = $d->format('Y-m-d');
            $diasSemana[] = [
                'fecha'  => $fStr,
                'diaNom' => $d->format('l'),
                'diaNum' => $d->format('d/m'),
                'estado' => $registroMap[$fStr]->estado ?? null,
                'notas'  => $registroMap[$fStr]->notas  ?? null,
            ];
        }
        $viernesLabel = (clone $lunes)->modify('+4 day');
        $baseUrl = URLROOT . '/actividades/participante/' . $id;
        $navSemana = [
            'ant'      => $semOffset - 1,
            'sig'      => $semOffset + 1,
            'ant_url'  => $baseUrl . '?anio=' . $anio . '&sem=' . ($semOffset - 1),
            'sig_url'  => $baseUrl . '?anio=' . $anio . '&sem=' . ($semOffset + 1),
            'semLabel' => $lunes->format('d/m') . ' – ' . $viernesLabel->format('d/m/Y'),
            'label'    => $lunes->format('d/m') . ' – ' . $viernesLabel->format('d/m/Y'),
            'esActual' => $semOffset === 0,
        ];

        // Almanaque anual
        // $grillaMeses[$m][$d] = estado string  (para compatibilidad con la vista desglose mensual)
        // $grillaMesMeta[$m]   = ['nombre','dias'(array objetos),'stats','tiene']  (para el calendario)
        $grillaMeses    = [];   // [m][d] => estado|null  (indexado por día numérico)
        $grillaMesMeta  = [];   // [m] => {nombre, dias[], stats, tiene}
        for ($m = 1; $m <= 12; $m++) {
            $primerDia = new DateTime("{$anio}-{$m}-01");
            $ultimoDia = (int)$primerDia->format('t');
            $inicioSem = (int)$primerDia->format('N') - 1; // 0=Lun

            // Array de celdas para el calendario (incluye huecos)
            $celdas = array_fill(0, $inicioSem, null);
            $mStats = ['P' => 0, 'A' => 0, 'J' => 0];

            for ($d = 1; $d <= $ultimoDia; $d++) {
                $fStr = sprintf('%04d-%02d-%02d', $anio, $m, $d);
                $dn   = (int) (new DateTime($fStr))->format('N');
                $est  = $registroMap[$fStr]->estado ?? null;
                $notas= $registroMap[$fStr]->notas  ?? null;

                $celdas[] = [
                    'fecha'   => $fStr,
                    'dia'     => $d,
                    'estado'  => $est,
                    'notas'   => $notas,
                    'esFinde' => $dn >= 6,
                ];

                // Mapa simple [día] => estado  (para la vista desglose)
                $grillaMeses[$m][$d] = $est;

                if ($est) {
                    $k = substr($est, 0, 1);
                    if (isset($mStats[$k])) $mStats[$k]++;
                }
            }

            $grillaMesMeta[$m] = [
                'nombre' => $primerDia->format('F'),
                'dias'   => $celdas,
                'stats'  => $mStats,
                'tiene'  => array_sum($mStats) > 0,
            ];
        }

        // Años con registros
        $this->db->query("
            SELECT DISTINCT YEAR(fecha) AS anio
            FROM actividad_asistencias
            WHERE participante_id = :pid
            ORDER BY anio DESC
        ");
        $this->db->bind(':pid', $id);
        $aniosRows = $this->db->resultSet();
        $anios = array_map(fn($r) => (int)$r->anio, $aniosRows);
        if (!in_array($anio, $anios)) $anios[] = $anio;
        rsort($anios);

        // Historial completo
        $this->db->query("
            SELECT fecha, estado, notas
            FROM actividad_asistencias
            WHERE participante_id = :pid
            ORDER BY fecha DESC
            LIMIT 100
        ");
        $this->db->bind(':pid', $id);
        $historial = $this->db->resultSet();

        $this->view('actividades/participante', [
            'participante'  => $participante,
            'anio'          => $anio,
            'anios'         => $anios,
            'grillaMeses'   => $grillaMeses,   // [m][d] => estado (para desglose)
            'grillaMesMeta' => $grillaMesMeta, // [m] => {nombre,dias,stats,tiene} (para calendario)
            'stats'         => $stats,
            'pct'           => $pct,
            'diasSemana'    => $diasSemana,
            'navSemana'     => $navSemana,
            'historial'     => $historial,
        ]);
    }

    // =========================================================
    // GET /actividades/asistenciasFecha  (AJAX → JSON)
    // Devuelve las asistencias de una actividad en una fecha dada
    // =========================================================
    public function asistenciasFecha(): void
    {
        header('Content-Type: application/json');

        $actividadId = (int)($_GET['actividad_id'] ?? 0);
        $fecha       = trim($_GET['fecha'] ?? '');

        if ($actividadId <= 0 || empty($fecha)) {
            echo json_encode(['success' => false, 'message' => 'Parámetros faltantes.']);
            exit;
        }

        $this->db->query("
            SELECT participante_id, estado, notas
            FROM actividad_asistencias
            WHERE actividad_id = :aid AND fecha = :fecha
        ");
        $this->db->bind(':aid',   $actividadId);
        $this->db->bind(':fecha', $fecha);
        $rows = $this->db->resultSet();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->participante_id] = ['estado' => $r->estado, 'notas' => $r->notas];
        }

        echo json_encode(['success' => true, 'data' => $map]);
        exit;
    }

    // =========================================================
    // POST /actividades/eliminarInstitucion  (AJAX → JSON)
    // =========================================================
    public function eliminarInstitucion(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        // Verificar que no tenga actividades asociadas
        $this->db->query("SELECT COUNT(*) AS total FROM actividades_extras WHERE institucion_id = :id");
        $this->db->bind(':id', $id);
        $uso = (int)($this->db->single()->total ?? 0);
        if ($uso > 0) {
            echo json_encode(['success' => false, 'message' => "No se puede eliminar: tiene {$uso} actividad(es) asociada(s)."]);
            exit;
        }

        $this->db->query("DELETE FROM instituciones WHERE id = :id AND categoria = 'actividad'");
        $this->db->bind(':id', $id);
        $this->db->execute();

        echo json_encode(['success' => true, 'message' => 'Institución eliminada.']);
        exit;
    }

    // =========================================================
    // POST /actividades/eliminarPasanteCorto  (AJAX → JSON)
    // =========================================================
    public function eliminarPasanteCorto(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']); exit;
        }
        $uid = (int)($_POST['usuario_id'] ?? 0);
        if (!$uid) {
            echo json_encode(['success' => false, 'message' => 'Usuario no especificado.']); exit;
        }
        // Verificar que es pasante corto
        $this->db->query("SELECT id FROM datos_pasante WHERE usuario_id = :uid AND tipo_pasantia = 'Corta' LIMIT 1");
        $this->db->bind(':uid', $uid);
        if (!$this->db->single()) {
            echo json_encode(['success' => false, 'message' => 'Pasante no encontrado.']); exit;
        }
        // Eliminar asistencias, evaluaciones, datos_pasante y usuario
        $this->db->query("DELETE FROM asistencias WHERE pasante_id = :uid");
        $this->db->bind(':uid', $uid); $this->db->execute();
        $this->db->query("DELETE FROM evaluaciones WHERE pasante_id = :uid");
        $this->db->bind(':uid', $uid); $this->db->execute();
        $this->db->query("DELETE FROM datos_pasante WHERE usuario_id = :uid");
        $this->db->bind(':uid', $uid); $this->db->execute();
        $this->db->query("DELETE FROM datos_personales WHERE usuario_id = :uid");
        $this->db->bind(':uid', $uid); $this->db->execute();
        $this->db->query("DELETE FROM usuarios WHERE id = :uid AND rol_id = 3");
        $this->db->bind(':uid', $uid); $this->db->execute();
        echo json_encode(['success' => true, 'message' => 'Pasante eliminado correctamente.']);
        exit;
    }

    // =========================================================
    // POST /actividades/crearPasanteCorto  (AJAX → JSON)
    // =========================================================
    public function crearPasanteCorto(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $nombres      = trim($_POST['nombres']      ?? '');
        $apellidos    = trim($_POST['apellidos']    ?? '');
        $cedula       = trim($_POST['cedula']       ?? '');
        $correo       = trim($_POST['correo']       ?? '');
        $carrera      = trim($_POST['carrera']      ?? '');
        $instId       = !empty($_POST['institucion_id'])  ? (int)$_POST['institucion_id']  : null;
        $deptoId      = !empty($_POST['departamento_id']) ? (int)$_POST['departamento_id'] : null;
        $tutorId      = !empty($_POST['tutor_id'])        ? (int)$_POST['tutor_id']        : null;
        $periodoId    = !empty($_POST['periodo_id'])      ? (int)$_POST['periodo_id']      : null;
        $fechaInicio  = trim($_POST['fecha_inicio'] ?? '');
        $horasMeta    = !empty($_POST['horas_meta']) ? (int)$_POST['horas_meta'] : 480;
        $fechaFin     = !empty($_POST['fecha_fin'])  ? trim($_POST['fecha_fin'])  : null;

        if (empty($nombres) || empty($apellidos) || empty($cedula) || empty($correo) || empty($fechaInicio)) {
            echo json_encode(['success' => false, 'message' => 'Nombres, apellidos, cédula, correo y fecha de inicio son obligatorios.']);
            exit;
        }

        // [FIX-V-15] Este endpoint crea una cuenta real en `usuarios` (rol_id=3) —
        // antes cédula/correo/fecha solo se comprobaban con empty(), a diferencia de
        // UsersController, que sí exige estos formatos para cualquier otra alta.
        if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
            echo json_encode(['success' => false, 'message' => 'La cédula debe tener entre 7 y 8 dígitos numéricos.']);
            exit;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Formato de correo inválido.']);
            exit;
        }
        $fechaInicioDt = DateTime::createFromFormat('Y-m-d', $fechaInicio);
        if (!$fechaInicioDt || $fechaInicioDt->format('Y-m-d') !== $fechaInicio) {
            echo json_encode(['success' => false, 'message' => 'Formato de fecha de inicio inválido.']);
            exit;
        }

        // Verificar duplicados
        $this->db->query("SELECT id FROM usuarios WHERE correo = :correo OR cedula = :cedula LIMIT 1");
        $this->db->bind(':correo', $correo);
        $this->db->bind(':cedula', $cedula);
        if ($this->db->single()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un usuario con ese correo o cédula.']);
            exit;
        }

        $tempPass = 'Sgp.' . $cedula;
        $hash     = password_hash($tempPass, PASSWORD_DEFAULT);

        try {
            $this->db->beginTransaction();

            $this->db->query("
                INSERT INTO usuarios (correo, password, cedula, rol_id, estado, requiere_cambio_clave)
                VALUES (:correo, :pass, :cedula, 3, 'activo', 1)
            ");
            $this->db->bind(':correo', $correo);
            $this->db->bind(':pass',   $hash);
            $this->db->bind(':cedula', $cedula);
            $this->db->execute();
            $userId = $this->db->lastInsertId();

            $this->db->query("
                INSERT INTO datos_personales (usuario_id, nombres, apellidos, cargo)
                VALUES (:uid, :nombres, :apellidos, :carrera)
            ");
            $this->db->bind(':uid',      $userId);
            $this->db->bind(':nombres',  $nombres);
            $this->db->bind(':apellidos',$apellidos);
            $this->db->bind(':carrera',  $carrera ?: null);
            $this->db->execute();

            $this->db->query("
                INSERT INTO datos_pasante
                    (usuario_id, tipo_pasantia, estado_pasantia, departamento_asignado_id,
                     tutor_id, periodo_id, institucion_id, institucion_procedencia,
                     horas_meta, fecha_inicio_pasantia, fecha_fin_estimada)
                VALUES
                    (:uid, 'Corta', 'Activo', :depto, :tutor, :periodo, :inst, '', :hmeta, :fi, :ff)
            ");
            $this->db->bind(':uid',    $userId);
            $this->db->bind(':depto',  $deptoId);
            $this->db->bind(':tutor',  $tutorId);
            $this->db->bind(':periodo',$periodoId);
            $this->db->bind(':inst',   $instId);
            $this->db->bind(':hmeta',  $horasMeta);
            $this->db->bind(':fi',     $fechaInicio);
            $this->db->bind(':ff',     $fechaFin);
            $this->db->execute();

            // Generar PIN de kiosco (4 dígitos aleatorios) para que el pasante
            // pueda registrar asistencia desde el primer día.
            $pinKiosco     = str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $pinHasheado   = password_hash($pinKiosco, PASSWORD_BCRYPT);
            $this->db->query("UPDATE usuarios SET pin_asistencia = :pin WHERE id = :uid");
            $this->db->bind(':pin', $pinHasheado);
            $this->db->bind(':uid', $userId);
            $this->db->execute();

            $this->db->commit();

            echo json_encode([
                'success'        => true,
                'message'        => "Pasante {$nombres} {$apellidos} registrado correctamente.",
                'temp_password'  => $tempPass,
                'pin_kiosco'     => $pinKiosco,
                'usuario_id'     => $userId,
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            // [FIX-ED-5] Mensaje genérico al cliente — detalle solo en log
            $detail = $e->getPrevious() ? $e->getPrevious()->getMessage() : $e->getMessage();
            error_log('[SGP-ACTIVIDADES] crearPasanteCorto error: ' . $detail);
            echo json_encode(['success' => false, 'message' => 'Error al registrar el pasante. Intente de nuevo.']);
        }
        exit;
    }

    // =========================================================
    // POST /actividades/editarPasanteCorto  (AJAX → JSON)
    // =========================================================
    public function editarPasanteCorto(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $userId    = (int)($_POST['usuario_id']      ?? 0);
        $nombres   = trim($_POST['nombres']           ?? '');
        $apellidos = trim($_POST['apellidos']         ?? '');
        $carrera   = trim($_POST['carrera']           ?? '');
        $instId    = !empty($_POST['institucion_id'])  ? (int)$_POST['institucion_id']  : null;
        $deptoId   = !empty($_POST['departamento_id']) ? (int)$_POST['departamento_id'] : null;
        $tutorId   = !empty($_POST['tutor_id'])        ? (int)$_POST['tutor_id']        : null;
        $periodoId = !empty($_POST['periodo_id'])      ? (int)$_POST['periodo_id']      : null;
        $fechaInicio = trim($_POST['fecha_inicio']    ?? '');
        $horasMeta   = !empty($_POST['horas_meta'])   ? (int)$_POST['horas_meta'] : 480;
        $fechaFin    = !empty($_POST['fecha_fin'])    ? trim($_POST['fecha_fin'])  : null;
        $estado      = trim($_POST['estado_pasantia'] ?? 'Activo');

        if ($userId <= 0 || empty($nombres) || empty($apellidos)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            exit;
        }

        $estadosValidos = ['Activo', 'Finalizado', 'Retirado', 'Pendiente'];
        if (!in_array($estado, $estadosValidos)) $estado = 'Activo';

        $this->db->query("
            UPDATE datos_personales SET nombres = :nombres, apellidos = :apellidos, cargo = :carrera
            WHERE usuario_id = :uid
        ");
        $this->db->bind(':nombres',  $nombres);
        $this->db->bind(':apellidos',$apellidos);
        $this->db->bind(':carrera',  $carrera ?: null);
        $this->db->bind(':uid',      $userId);
        $this->db->execute();

        $this->db->query("
            UPDATE datos_pasante
            SET departamento_asignado_id = :depto, tutor_id = :tutor, periodo_id = :periodo,
                institucion_id = :inst, horas_meta = :hmeta,
                fecha_inicio_pasantia = :fi, fecha_fin_estimada = :ff, estado_pasantia = :estado
            WHERE usuario_id = :uid AND tipo_pasantia = 'Corta'
        ");
        $this->db->bind(':depto',  $deptoId);
        $this->db->bind(':tutor',  $tutorId);
        $this->db->bind(':periodo',$periodoId);
        $this->db->bind(':inst',   $instId);
        $this->db->bind(':hmeta',  $horasMeta);
        $this->db->bind(':fi',     $fechaInicio ?: null);
        $this->db->bind(':ff',     $fechaFin);
        $this->db->bind(':estado', $estado);
        $this->db->bind(':uid',    $userId);
        $this->db->execute();

        echo json_encode(['success' => true, 'message' => 'Pasante actualizado correctamente.']);
        exit;
    }

    // =========================================================
    // POST /actividades/marcarAsistenciaCorto  (AJAX → JSON)
    // =========================================================
    public function marcarAsistenciaCorto(): void
    {
        header('Content-Type: application/json');
        $this->parsePostBody();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        $fecha     = trim($_POST['fecha']        ?? date('Y-m-d'));
        $estado    = trim($_POST['estado']       ?? 'Presente');
        $motivo    = trim($_POST['motivo']       ?? '');

        if ($pasanteId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Pasante no especificado.']);
            exit;
        }

        $estadosValidos = ['Presente', 'Ausente', 'Justificado', 'Retardo'];
        if (!in_array($estado, $estadosValidos)) {
            echo json_encode(['success' => false, 'message' => 'Estado inválido.']);
            exit;
        }

        // Verificar que es pasante corto
        $this->db->query("SELECT id FROM datos_pasante WHERE usuario_id = :uid AND tipo_pasantia = 'Corta' LIMIT 1");
        $this->db->bind(':uid', $pasanteId);
        if (!$this->db->single()) {
            echo json_encode(['success' => false, 'message' => 'Pasante no encontrado o no es pasantía corta.']);
            exit;
        }

        // horas_calculadas: Presente/Justificado/Retardo = 8h, Ausente = 0h
        $horasCalc = in_array($estado, ['Presente', 'Justificado', 'Retardo']) ? 8.00 : 0.00;

        $this->db->query("
            INSERT INTO asistencias (pasante_id, fecha, hora_registro, estado, metodo, motivo_justificacion, horas_calculadas)
            VALUES (:pid, :fecha, NOW(), :estado, 'Manual', :motivo, :horas)
            ON DUPLICATE KEY UPDATE
                estado               = VALUES(estado),
                motivo_justificacion = VALUES(motivo_justificacion),
                hora_registro        = VALUES(hora_registro),
                horas_calculadas     = VALUES(horas_calculadas)
        ");
        $this->db->bind(':pid',    $pasanteId);
        $this->db->bind(':fecha',  $fecha);
        $this->db->bind(':estado', $estado);
        $this->db->bind(':motivo', $motivo ?: null);
        $this->db->bind(':horas',  $horasCalc);

        if (!$this->db->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => "Asistencia registrada: {$estado}",
            'estado'  => $estado,
            'fecha'   => $fecha,
        ]);
        exit;
    }

    // =========================================================
    // GET /actividades/asistenciaCorto  (AJAX → JSON)
    // Devuelve asistencias de un pasante corto en rango dado
    // =========================================================
    public function asistenciaCorto(): void
    {
        header('Content-Type: application/json');

        $pasanteId = (int)($_GET['pasante_id'] ?? 0);
        $modo      = trim($_GET['modo'] ?? 'semanal');
        $fecha     = trim($_GET['fecha'] ?? date('Y-m-d'));

        if ($pasanteId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Pasante no especificado.']);
            exit;
        }

        if ($modo === 'diaria') {
            $lunes  = date('Y-m-d', strtotime('monday this week', strtotime($fecha)));
            $inicio = $lunes;
            $fin    = date('Y-m-d', strtotime('+4 days', strtotime($lunes)));
        } elseif ($modo === 'semanal') {
            $lunes  = date('Y-m-d', strtotime('monday this week', strtotime($fecha)));
            $inicio = $lunes;
            $fin    = date('Y-m-d', strtotime('+4 days', strtotime($lunes)));
        } elseif ($modo === 'periodo') {
            $this->db->query("SELECT fecha_inicio_pasantia, fecha_fin_estimada FROM datos_pasante WHERE usuario_id = :pid AND tipo_pasantia = 'Corta' LIMIT 1");
            $this->db->bind(':pid', $pasanteId);
            $period = $this->db->single();
            $inicio = $period->fecha_inicio_pasantia ?? date('Y-m-01');
            $fin    = $period->fecha_fin_estimada    ?? date('Y-m-t');
        } else {
            $inicio = date('Y-m-01', strtotime($fecha));
            $fin    = date('Y-m-t',  strtotime($fecha));
        }

        $this->db->query("
            SELECT fecha, hora_registro, estado, motivo_justificacion, metodo, es_auto_fill
            FROM asistencias
            WHERE pasante_id = :pid AND fecha BETWEEN :inicio AND :fin
            ORDER BY fecha ASC
        ");
        $this->db->bind(':pid',    $pasanteId);
        $this->db->bind(':inicio', $inicio);
        $this->db->bind(':fin',    $fin);
        $registros = $this->db->resultSet();

        $this->db->query("SELECT fecha FROM dias_feriados WHERE fecha BETWEEN :inicio AND :fin AND es_laborable = 0");
        $this->db->bind(':inicio', $inicio);
        $this->db->bind(':fin',    $fin);
        $feriadosRows = $this->db->resultSet();
        $feriados = array_map(fn($r) => $r->fecha, $feriadosRows);

        $map = [];
        foreach ($registros as $r) {
            $map[$r->fecha] = [
                'estado'  => $r->estado,
                'hora'    => $r->hora_registro,
                'metodo'  => $r->metodo,
                'motivo'  => $r->motivo_justificacion,
                'autofill'=> (bool)$r->es_auto_fill,
            ];
        }

        echo json_encode([
            'success'  => true,
            'inicio'   => $inicio,
            'fin'      => $fin,
            'modo'     => $modo,
            'registros'=> $map,
            'feriados' => $feriados,
        ]);
        exit;
    }

    // =========================================================
    // GET /actividades/verificarCampo  (AJAX → JSON)
    // Verifica si correo, cédula o teléfono ya existen en BD
    // =========================================================
    public function verificarCampo(): void
    {
        header('Content-Type: application/json');

        $campoRaw = trim($_GET['campo'] ?? '');
        $valor    = trim($_GET['valor']  ?? '');

        // Mapeo explícito de columnas permitidas → tabla (evita concatenación directa en SQL)
        $campoMap = [
            'correo'   => ['tabla' => 'usuarios',        'col' => 'correo'],
            'cedula'   => ['tabla' => 'usuarios',        'col' => 'cedula'],
            'telefono' => ['tabla' => 'datos_personales','col' => 'telefono'],
        ];

        if (empty($valor) || !isset($campoMap[$campoRaw])) {
            echo json_encode(['disponible' => true]);
            exit;
        }

        $map = $campoMap[$campoRaw];
        $this->db->query("SELECT COUNT(*) AS total FROM {$map['tabla']} WHERE {$map['col']} = :valor LIMIT 1");
        $this->db->bind(':valor', $valor);

        $res = $this->db->single();
        $existe = (int)($res->total ?? 0) > 0;

        echo json_encode([
            'disponible' => !$existe,
            'campo'      => $campoRaw,
            'mensaje'    => $existe ? "Este {$campoRaw} ya está registrado en el sistema." : '',
        ]);
        exit;
    }

    // =========================================================
    // POST /actividades/eliminar/ID
    // =========================================================
    public function eliminar(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/actividades/servicio');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido.');
            header('Location: ' . URLROOT . '/actividades/servicio');
            exit;
        }

        if ($id <= 0) {
            Session::setFlash('error', 'ID de actividad inválido.');
            header('Location: ' . URLROOT . '/actividades/servicio');
            exit;
        }

        // Verificar que existe
        $this->db->query("SELECT id, nombre FROM actividades_extras WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        $act = $this->db->single();

        if (!$act) {
            Session::setFlash('error', 'La actividad no existe.');
            header('Location: ' . URLROOT . '/actividades/servicio');
            exit;
        }

        // Eliminar participantes primero (FK constraint)
        $this->db->query("DELETE FROM actividad_participantes WHERE actividad_id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();

        // Eliminar la actividad
        $this->db->query("DELETE FROM actividades_extras WHERE id = :id");
        $this->db->bind(':id', $id);
        $this->db->execute();

        Session::setFlash('success', 'Actividad «' . $act->nombre . '» eliminada correctamente.');
        header('Location: ' . URLROOT . '/actividades/servicio');
        exit;
    }

    // =========================================================
    // Helper: calcula datos de asistencia para pasantes cortos
    // =========================================================
    private function _calcularAsistenciasCortas(string $vista): array
    {
        $hoy   = date('Y-m-d');
        $vista = $_GET['vista'] ?? 'diaria';
        if (!in_array($vista, ['diaria','semanal','mensual','total'])) $vista = 'diaria';

        $rolId = (int)Session::get('role_id');

        // Filtro departamento para tutor
        $whereDept = "";
        $deptId    = 0;
        if ($rolId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $deptId = (int)($this->db->single()->departamento_id ?? 0);
            $whereDept = " AND dpa.departamento_asignado_id = {$deptId} ";
        }

        $paramsUrl = [
            'fecha'  => $_GET['fecha']  ?? $hoy,
            'semana' => $_GET['semana'] ?? date('W'),
            'mes'    => str_pad($_GET['mes'] ?? date('m'), 2, '0', STR_PAD_LEFT),
            'anio'   => $_GET['anio']   ?? date('Y'),
        ];

        // Rango de fechas según vista
        if ($vista === 'diaria') {
            $fechaInicio = $paramsUrl['fecha'];
            $fechaFin    = $paramsUrl['fecha'];
        } elseif ($vista === 'semanal') {
            $dto = new \DateTime();
            $dto->setISODate((int)$paramsUrl['anio'], (int)$paramsUrl['semana']);
            $fechaInicio = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $fechaFin = $dto->format('Y-m-d');
        } elseif ($vista === 'mensual') {
            $fechaInicio = $paramsUrl['anio'] . '-' . $paramsUrl['mes'] . '-01';
            $fechaFin    = date('Y-m-t', strtotime($fechaInicio));
        } else {
            $fechaInicio = '2000-01-01';
            $fechaFin    = $hoy;
        }

        // Registros de asistencia de pasantes cortos
        $this->db->query("
            SELECT a.id, a.fecha, a.hora_registro, a.estado, a.metodo, a.motivo_justificacion,
                   u.id AS pasante_id, u.cedula,
                   dp.nombres, dp.apellidos,
                   d.nombre AS departamento_nombre
            FROM asistencias a
            INNER JOIN usuarios u ON u.id = a.pasante_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            LEFT JOIN departamentos d ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            WHERE dpa.tipo_pasantia = 'Corta'
              AND a.estado != 'Anulado'
              AND a.fecha >= :fi AND a.fecha <= :ff
              {$whereDept}
            ORDER BY a.fecha DESC, a.hora_registro DESC
        ");
        $this->db->bind(':fi', $fechaInicio);
        $this->db->bind(':ff', $fechaFin);
        $registros = $this->db->resultSet();

        // Pasantes cortos activos
        $this->db->query("
            SELECT u.id, u.cedula, dp.nombres, dp.apellidos, d.nombre AS departamento_nombre
            FROM datos_pasante dpa
            INNER JOIN usuarios u ON u.id = dpa.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = dpa.usuario_id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            WHERE dpa.tipo_pasantia = 'Corta' AND dpa.estado_pasantia = 'Activo'
              {$whereDept}
            ORDER BY dp.apellidos ASC
        ");
        $misPasantes = $this->db->resultSet();

        // KPIs diarios
        $presentes    = 0;
        $ausentes     = 0;
        $justificados = 0;
        if ($vista === 'diaria') {
            foreach ($registros as $r) {
                $est = strtolower($r->estado ?? '');
                if (str_contains($est, 'presente'))    $presentes++;
                elseif (str_contains($est, 'ausente')) $ausentes++;
                else                                   $justificados++;
            }
        }

        // Sin marcar (diaria)
        $sinMarcar = [];
        if ($vista === 'diaria') {
            $marcados = array_map(fn($r) => (int)$r->pasante_id, $registros);
            foreach ($misPasantes as $p) {
                if (!in_array((int)$p->id, $marcados)) $sinMarcar[] = $p;
            }
        }

        // Datos semanales (heatmap)
        $datosSemanales = [];
        $navSemana = ['ant_url' => '#', 'sig_url' => '#', 'texto' => ''];
        if ($vista === 'semanal') {
            $semNum  = (int)$paramsUrl['semana'];
            $anioNum = (int)$paramsUrl['anio'];
            $dto = new \DateTime();
            $dto->setISODate($anioNum, $semNum);
            $lunesTxt = $dto->format('d/m');
            $dto->modify('+4 days');
            $viernesTxt = $dto->format('d/m/Y');
            $navSemana['texto'] = "Semana {$semNum} — {$lunesTxt} al {$viernesTxt}";

            $semAnt = $semNum - 1; $anioAnt = $anioNum;
            if ($semAnt < 1) { $anioAnt--; $semAnt = (int)(new \DateTime("{$anioAnt}-12-28"))->format('W'); }
            $semSig = $semNum + 1; $anioSig = $anioNum;
            $maxSem = (int)(new \DateTime("{$anioNum}-12-28"))->format('W');
            if ($semSig > $maxSem) { $anioSig++; $semSig = 1; }
            $base = URLROOT . '/actividades/pasantias?vista=semanal';
            $navSemana['ant_url'] = "{$base}&semana={$semAnt}&anio={$anioAnt}";
            $navSemana['sig_url'] = "{$base}&semana={$semSig}&anio={$anioSig}";

            foreach ($registros as $r) {
                $depto = $r->departamento_nombre ?: 'Sin Departamento';
                $pid   = (int)$r->pasante_id;
                $dow   = (int)(new \DateTime($r->fecha))->format('N');
                if ($dow > 5) continue;
                if (!isset($datosSemanales[$depto][$pid])) {
                    $datosSemanales[$depto][$pid] = [
                        'nombre'  => trim(($r->apellidos ?? '') . ', ' . ($r->nombres ?? '')),
                        'dias'    => [], 'motivos' => [],
                        'totales' => ['P' => 0, 'A' => 0, 'J' => 0],
                    ];
                }
                $letra = 'P';
                if (str_contains(strtolower($r->estado), 'ausente'))     $letra = 'A';
                elseif (str_contains(strtolower($r->estado), 'justificado')) $letra = 'J';
                $datosSemanales[$depto][$pid]['dias'][$dow] = $letra;
                $datosSemanales[$depto][$pid]['motivos'][$dow] = $r->motivo_justificacion ?? '';
                $datosSemanales[$depto][$pid]['totales'][$letra]++;
            }
        }

        // Mensual
        $bentoMensual     = [];
        $chartSemanasJson = '[]';
        $chartPctsJson    = '[]';
        $healthIndex      = 100;
        $nombreMes        = '';
        $urlMesAnt        = '#';
        $urlMesSig        = '#';
        if ($vista === 'mensual') {
            $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            $mesNum  = (int)$paramsUrl['mes'];
            $anioNum = (int)$paramsUrl['anio'];
            $nombreMes = $meses[$mesNum] ?? '';
            $mesAnt = $mesNum - 1; $anioMesAnt = $anioNum;
            if ($mesAnt < 1) { $mesAnt = 12; $anioMesAnt--; }
            $mesSig = $mesNum + 1; $anioMesSig = $anioNum;
            if ($mesSig > 12) { $mesSig = 1; $anioMesSig++; }
            $base = URLROOT . '/actividades/pasantias?vista=mensual';
            $urlMesAnt = "{$base}&mes=" . str_pad((string)$mesAnt, 2, '0', STR_PAD_LEFT) . "&anio={$anioMesAnt}";
            $urlMesSig = "{$base}&mes=" . str_pad((string)$mesSig, 2, '0', STR_PAD_LEFT) . "&anio={$anioMesSig}";

            $porDepto = [];
            foreach ($registros as $r) {
                $depto = $r->departamento_nombre ?: 'Sin Departamento';
                $pid   = (int)$r->pasante_id;
                $nombre = trim(($r->apellidos ?? '') . ', ' . ($r->nombres ?? ''));
                $ini    = strtoupper(substr($r->apellidos ?? 'P', 0, 1) . substr($r->nombres ?? 'P', 0, 1));
                if (!isset($porDepto[$depto][$pid])) {
                    $porDepto[$depto][$pid] = ['nombre' => $nombre, 'cedula' => $r->cedula ?? '', 'iniciales' => $ini, 'presentes' => 0, 'ausentes' => 0, 'justificados' => 0];
                }
                $est = strtolower($r->estado ?? '');
                if (str_contains($est, 'presente'))    $porDepto[$depto][$pid]['presentes']++;
                elseif (str_contains($est, 'ausente')) $porDepto[$depto][$pid]['ausentes']++;
                else                                   $porDepto[$depto][$pid]['justificados']++;
            }
            $bentoMensual = $porDepto;

            $totalP = 0; $totalR = 0;
            foreach ($porDepto as $pasantes) {
                foreach ($pasantes as $p) {
                    $t = $p['presentes'] + $p['ausentes'] + $p['justificados'];
                    $totalP += $p['presentes'] + $p['justificados'];
                    $totalR += $t;
                }
            }
            $healthIndex = $totalR > 0 ? round($totalP / $totalR * 100) : 100;
        }

        // Total
        $resumenTotal        = [];
        $periodosFiltro      = [];
        $periodoSeleccionado = null;
        $periodoIdSel        = 0;
        if ($vista === 'total') {
            $this->db->query("
                SELECT pa.id, pa.nombre, pa.fecha_inicio, pa.fecha_fin, pa.estado
                FROM periodos_academicos pa
                WHERE pa.tipo = 'Corto'
                ORDER BY pa.fecha_inicio DESC
            ");
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

            // horas_meta y estado por pasante
            $this->db->query("
                SELECT dpa.usuario_id,
                       COALESCE(NULLIF(dpa.horas_meta, 0), 480) AS horas_meta,
                       COALESCE(dpa.estado_pasantia, 'Activo') AS estado_pasantia
                FROM datos_pasante dpa
                WHERE dpa.tipo_pasantia = 'Corta' {$whereDept}
            ");
            $metaRows = $this->db->resultSet();
            $metaMap = [];
            foreach ($metaRows as $mr) {
                $metaMap[$mr->usuario_id] = ['horas_meta' => (int)$mr->horas_meta, 'estado_pasantia' => $mr->estado_pasantia];
            }

            foreach ($registros as $reg) {
                $pid    = $reg->pasante_id;
                $nombre = trim(($reg->apellidos ?? '') . ', ' . ($reg->nombres ?? ''));
                $ini    = strtoupper(substr($reg->apellidos ?? 'P', 0, 1) . substr($reg->nombres ?? 'P', 0, 1));
                if (!isset($resumenTotal[$pid])) {
                    $meta = $metaMap[$pid] ?? ['horas_meta' => 480, 'estado_pasantia' => 'Activo'];
                    $resumenTotal[$pid] = [
                        'nombre' => $nombre, 'cedula' => $reg->cedula ?? '', 'iniciales' => $ini,
                        'pasante_id' => (int)$pid, 'departamento' => $reg->departamento_nombre ?? '—',
                        'horas_meta' => $meta['horas_meta'], 'estado_pasantia' => $meta['estado_pasantia'],
                        'presentes' => 0, 'ausentes' => 0, 'justificados' => 0,
                    ];
                }
                $est = strtolower($reg->estado ?? '');
                if (str_contains($est, 'presente'))    $resumenTotal[$pid]['presentes']++;
                elseif (str_contains($est, 'ausente')) $resumenTotal[$pid]['ausentes']++;
                else                                   $resumenTotal[$pid]['justificados']++;
            }
            usort($resumenTotal, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));
        }

        return [
            'paramsUrl'           => $paramsUrl,
            'hoyAsist'            => $hoy,
            'registrosAsist'      => $registros,
            'misPasantesAsist'    => $misPasantes,
            'sinMarcarAsist'      => array_values($sinMarcar),
            'presentesAsist'      => $presentes,
            'ausentesAsist'       => $ausentes,
            'justificadosAsist'   => $justificados,
            'totalActivosAsist'   => count($misPasantes),
            'datosSemanales'      => $datosSemanales,
            'navSemana'           => $navSemana,
            'nombreMes'           => $nombreMes,
            'urlMesAnt'           => $urlMesAnt,
            'urlMesSig'           => $urlMesSig,
            'bentoMensual'        => $bentoMensual,
            'chartSemanasJson'    => $chartSemanasJson,
            'chartPctsJson'       => $chartPctsJson,
            'healthIndex'         => $healthIndex,
            'resumenTotal'        => $resumenTotal,
            'periodosFiltroAsist' => $periodosFiltro,
            'periodoSeleccionado' => $periodoSeleccionado,
            'periodoIdSel'        => $periodoIdSel,
        ];
    }
}
