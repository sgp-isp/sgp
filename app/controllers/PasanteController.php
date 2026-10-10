<?php
/**
 * PasanteController — Vistas del rol Pasante + Vistas Admin Legacy
 *
 * Arquitectura basada en 3NF (usuarios -> datos_personales -> datos_pasante)
 *
 * RUTAS:
 *   GET  /pasante/index       → index()      Lista admin (legacy)
 *   GET  /pasante/show/{id}   → show()       Kardex individual (admin)
 *   GET  /pasante/dashboard   → dashboard()  Panel personal del pasante
 *   GET  /pasante/asistencia  → asistencia() Historial personal
 *
 * NOTA: asignar() y finalizar_pasantia() eliminados (VULN-08).
 *       Esa lógica es responsabilidad exclusiva de AsignacionesController.
 *
 * @version 4.0 — Limpieza DRY
 */

declare(strict_types=1);

class PasanteController extends Controller
{
    private $db;
    private $pasanteModel;

    public function __construct()
    {
        Session::start();

        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();

        $config = require '../app/config/config.php';
        $this->db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado

        require_once '../app/models/PasanteModel.php';
        require_once '../app/models/AsistenciaModel.php';
        require_once '../app/models/NotificationModel.php';
        require_once '../app/models/AuditModel.php';
        $this->pasanteModel = new PasanteModel();
    }

    // ────────────────────────────────────────────────────────────────
    // VISTAS ADMINISTRATIVAS
    // ────────────────────────────────────────────────────────────────

    /**
     * Lista de pasantes para el Administrador.
     * Solo rol_id = 1 puede acceder.
     */
    public function index(): void
    {
        if (!in_array((int)Session::get('role_id'), [0, 1])) {
            Session::setFlash('error', 'No tienes permisos para esta sección.');
            $this->redirect('/dashboard');
            return;
        }

        // Pasantes: tabla única usuarios
        $pasantes = $this->pasanteModel->getAll();

        // Departamentos activos para el select del modal
        $this->db->query("SELECT id, nombre, descripcion FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
        $departamentos = $this->db->resultSet();

        $this->view('admin/pasantes/index', [
            'title'         => 'Gestión de Pasantes',
            'pasantes'      => $pasantes,
            'departamentos' => $departamentos,
        ]);
    }

    /**
     * Kardex individual del pasante.
     *
     * @param int $id ID del usuario pasante
     */
    public function show(int $id): void
    {
        if (!in_array((int)Session::get('role_id'), [0, 1])) {
            $this->redirect('/dashboard');
            return;
        }

        $pasante = $this->pasanteModel->getByUsuarioId($id);

        if (!$pasante) {
            Session::setFlash('error', 'Pasante no encontrado.');
            $this->redirect('/pasantes');
            return;
        }

        $this->view('admin/pasantes/show', [
            'title'   => 'Reporte de Pasantía — ' . ($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''),
            'pasante' => $pasante,
        ]);
    }

    /**
     * Dashboard del pasante (su propia vista — rol_id = 3).
     */
    public function dashboard(): void
    {
        if (Session::get('role_id') != 3) {
            Session::setFlash('error', 'No tienes permisos para esta sección.');
            $this->redirect('/dashboard');
            return;
        }

        $userId  = (int)Session::get('user_id');
        $pasante = $this->pasanteModel->getByUsuarioId($userId);

        // Auto-fill silencioso: asegura que días pasados sin registro queden como Ausente
        $asistenciaModel = new AsistenciaModel($this->db);
        $asistenciaModel->rellenarDiasVacios();

        // PRO-RATA: Calcular horas dinámicamente desde asistencias
        $horasMeta = (int)(($pasante->horas_meta ?? 0) > 0 ? $pasante->horas_meta : 1440);
        $proRata   = $asistenciaModel->calcularProgresoProRata($userId, $horasMeta);

        // Calendario (tiempo transcurrido)
        if (!empty($pasante->fecha_inicio_pasantia)) {
            $calendario = $asistenciaModel->calcularProgresoPorCalendario($pasante->fecha_inicio_pasantia, $horasMeta);
            if ($calendario) {
                $proRata->porcentaje_calendario = $calendario->porcentaje_calendario ?? 0;
            }
        }

        // Sobreescribir horas_acumuladas con el valor Pro-Rata real
        if ($pasante) {
            $pasante->horas_acumuladas = $proRata->horas_mostradas;
            $pasante->horas_meta       = $proRata->horas_meta;
        }

        // Feed de actividades recientes (últimas 10)
        $this->db->query("
            SELECT a.*, d.nombre as departamento_nombre
            FROM asistencias a
            JOIN datos_pasante dp ON a.pasante_id = dp.usuario_id
            LEFT JOIN departamentos d ON dp.departamento_asignado_id = d.id
            WHERE a.pasante_id = :uid
            GROUP BY a.id
            ORDER BY a.fecha DESC, a.hora_registro DESC
            LIMIT 10
        ");
        $this->db->bind(':uid', $userId);
        $actividades = $this->db->resultSet();

        // Asistencias del mes actual → calendario mensual del dashboard
        $this->db->query("SELECT fecha, estado FROM asistencias
            WHERE pasante_id = :uid AND fecha BETWEEN :inicio AND :fin ORDER BY fecha ASC");
        $this->db->bind(':uid', $userId);
        $this->db->bind(':inicio', date('Y-m-01'));
        $this->db->bind(':fin', date('Y-m-t'));
        $asistenciasMes = $this->db->resultSet() ?: [];

        // Asistencia de hoy
        $this->db->query("SELECT estado, hora_registro, metodo FROM asistencias
            WHERE pasante_id = :uid AND fecha = CURDATE() LIMIT 1");
        $this->db->bind(':uid', $userId);
        $asistenciaHoy = $this->db->single() ?: null;

        // Actividades registradas hoy
        $this->db->query("SELECT titulo, descripcion FROM actividades_pasante
            WHERE pasante_id = :uid AND fecha = CURDATE() ORDER BY created_at ASC");
        $this->db->bind(':uid', $userId);
        $actividadesHoy = $this->db->resultSet() ?: [];

        // Feriados del mes actual → mapa fecha => es_laborable (0|1)
        $feriadosMap = [];
        try {
            $this->db->query("SELECT fecha, es_laborable FROM dias_feriados
                WHERE fecha BETWEEN :desde AND :hasta");
            $this->db->bind(':desde', date('Y-m-01'));
            $this->db->bind(':hasta', date('Y-m-t'));
            $ferRows = $this->db->resultSet();
            foreach ($ferRows ?: [] as $f) {
                $feriadosMap[$f->fecha] = (int)$f->es_laborable;
            }
        } catch (\Throwable $e) { /* tabla puede no existir */ }

        $this->view('pasante/dashboard', [
            'title'          => 'Mi Panel',
            'pasante'        => $pasante,
            'actividades'    => $actividades,
            'asistenciasMes' => $asistenciasMes,
            'asistenciaHoy'  => $asistenciaHoy,
            'actividadesHoy' => $actividadesHoy,
            'user_name'      => Session::get('user_name') ?? 'Pasante',
            'proRata'        => $proRata,
            'feriadosMap'    => $feriadosMap,
        ]);

    }

    /**
     * Mi Asistencia — Historial personal del pasante.
     */
    public function asistencia(): void
    {
        if (Session::get('role_id') != 3) {
            Session::setFlash('error', 'No tienes permisos para esta sección.');
            $this->redirect('/dashboard');
            return;
        }

        $userId = (int)Session::get('user_id');
        $pasante = $this->pasanteModel->getByUsuarioId($userId);

        // Auto-fill silencioso: marca días pasados sin registro como Ausente.
        // INSERT IGNORE garantiza idempotencia; seguro llamarlo en cada visita.
        $asistenciaModel = new AsistenciaModel($this->db);
        $asistenciaModel->rellenarDiasVacios();

        // Pro-rata progress (needed for progress bars in view)
        $horasMeta = (int)(($pasante->horas_meta ?? 0) > 0 ? $pasante->horas_meta : 1440);
        $proRata   = $asistenciaModel->calcularProgresoProRata($userId, $horasMeta);
        $calendario = null;
        if (!empty($pasante->fecha_inicio_pasantia)) {
            $calendario = $asistenciaModel->calcularProgresoPorCalendario($pasante->fecha_inicio_pasantia, $horasMeta);
            if ($calendario) {
                $proRata->porcentaje_calendario = $calendario->porcentaje_calendario ?? 0;
            }
        }

        $this->db->query("
            SELECT * FROM asistencias
            WHERE pasante_id = :uid
            ORDER BY fecha DESC, hora_registro DESC
        ");
        $this->db->bind(':uid', $userId);
        $asistencias = $this->db->resultSet();

        // Feriados del mes actual para el calendario Mon-Fri
        $fechaIniMes = date('Y-m') . '-01';
        $fechaFinMes = date('Y-m-t');
        $feriados = $asistenciaModel->getFeriadosEnRango($fechaIniMes, $fechaFinMes);

        $this->view('pasante/asistencia', [
            'title'      => 'Mi Asistencia',
            'pasante'    => $pasante,
            'asistencias'=> $asistencias,
            'proRata'    => $proRata,
            'feriados'   => $feriados,
        ]);

    }

    /**
     * Endpoint AJAX para verificar el estado actual de la pasantía.
     * Utilizado para el polling de actualización automática.
     */
    public function getStatusAjax(): void
    {
        if (Session::get('role_id') != 3) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autorizado']);
            return;
        }

        $userId = (int)Session::get('user_id');
        $pasante = $this->pasanteModel->getByUsuarioId($userId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'estado'  => $pasante->estado_pasantia ?? 'Sin Asignar'
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // MÓDULOS PERSONALES DEL PASANTE
    // ────────────────────────────────────────────────────────────────

    /**
     * GET /pasante/analiticas
     * Gráfica personal de asistencia del pasante autenticado.
     */
    public function analiticas(): void
    {
        if (Session::get('role_id') != 3) {
            $this->redirect('/dashboard');
            return;
        }

        $userId  = (int)Session::get('user_id');
        $pasante = $this->pasanteModel->getByUsuarioId($userId);

        // Asistencias agrupadas por mes (últimos 6 meses)
        $this->db->query("
            SELECT
                DATE_FORMAT(fecha, '%Y-%m')                                    AS mes,
                DATE_FORMAT(fecha, '%b %Y')                                    AS mes_label,
                COUNT(CASE WHEN estado = 'Presente'    THEN 1 END)             AS presentes,
                COUNT(CASE WHEN estado = 'Ausente'     THEN 1 END)             AS ausentes,
                COUNT(CASE WHEN estado = 'Justificado' THEN 1 END)             AS justificados,
                COUNT(*)                                                        AS total
            FROM asistencias
            WHERE pasante_id = :uid
              AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(fecha, '%Y-%m'), DATE_FORMAT(fecha, '%b %Y')
            ORDER BY mes ASC
        ");
        $this->db->bind(':uid', $userId);
        $porMes = $this->db->resultSet();

        // Totales globales
        $this->db->query("
            SELECT
                COUNT(CASE WHEN estado = 'Presente'    THEN 1 END) AS presentes,
                COUNT(CASE WHEN estado = 'Ausente'     THEN 1 END) AS ausentes,
                COUNT(CASE WHEN estado = 'Justificado' THEN 1 END) AS justificados,
                COUNT(*)                                            AS total
            FROM asistencias
            WHERE pasante_id = :uid
        ");
        $this->db->bind(':uid', $userId);
        $totales = $this->db->single();

        // Progreso pro-rata
        $asistenciaModel = new AsistenciaModel($this->db);
        $horasMeta = (int)(($pasante->horas_meta ?? 0) > 0 ? $pasante->horas_meta : 1440);
        $proRata   = $asistenciaModel->calcularProgresoProRata($userId, $horasMeta);

        $this->view('pasante/analiticas', [
            'title'   => 'Mis Analíticas',
            'pasante' => $pasante,
            'porMes'  => $porMes,
            'totales' => $totales,
            'proRata' => $proRata,
        ]);
    }

    /**
     * GET /pasante/misEvaluaciones
     * Evaluaciones recibidas por el pasante (solo lectura).
     */
    public function misEvaluaciones(): void
    {
        if (Session::get('role_id') != 3) {
            $this->redirect('/dashboard');
            return;
        }

        $userId  = (int)Session::get('user_id');
        $pasante = $this->pasanteModel->getByUsuarioId($userId);

        $this->db->query("
            SELECT
                e.id,
                e.lapso_academico,
                e.promedio_final,
                e.observaciones,
                DATE_FORMAT(e.fecha_evaluacion, '%d/%m/%Y') AS fecha_formateada,
                CONCAT(tp.nombres, ' ', tp.apellidos)       AS tutor_nombre
            FROM evaluaciones e
            LEFT JOIN datos_personales tp ON tp.usuario_id = e.tutor_id
            WHERE e.pasante_id = :uid
            ORDER BY e.fecha_evaluacion DESC
        ");
        $this->db->bind(':uid', $userId);
        $evaluaciones = $this->db->resultSet();

        $this->view('pasante/mis_evaluaciones', [
            'title'       => 'Mis Evaluaciones',
            'pasante'     => $pasante,
            'evaluaciones'=> $evaluaciones,
        ]);
    }

    /**
     * GET /pasante/constancia
     * Vista de constancia — muestra info y botón de descarga.
     * Si no está asignado, la vista muestra SweetAlert de aviso.
     */
    public function constancia(): void
    {
        if (Session::get('role_id') != 3) {
            $this->redirect('/dashboard');
            return;
        }

        $userId  = (int)Session::get('user_id');
        $pasante = $this->_getConstanciaData($userId);

        $this->view('pasante/constancia', [
            'title'   => 'Mi Constancia',
            'pasante' => $pasante,
        ]);
    }

    /**
     * GET /pasante/descargarConstancia
     * Genera y descarga el PDF de constancia.
     */
    public function descargarConstancia(): void
    {
        if (Session::get('role_id') != 3) {
            $this->redirect('/dashboard');
            return;
        }

        $userId  = (int)Session::get('user_id');
        $pasante = $this->_getConstanciaData($userId);

        if (!$pasante || in_array($pasante->estado_pasantia ?? '', ['Sin Asignar', 'Pendiente', ''])) {
            http_response_code(403);
            exit('No autorizado');
        }

        require_once '../app/lib/PdfGenerator.php';
        $pdf = new PdfGenerator();

        // Determinar tipo de constancia a generar automáticamente
        $tipo = (strtolower($pasante->estado_pasantia ?? '') === 'finalizado') ? 'culminacion' : 'servicio';

        ob_start();
        include '../app/views/reportes/pdf_constancia.php';
        $html = ob_get_clean();

        $cedula = $pasante->cedula ?? $userId;
        $nombreArchivo = ($tipo === 'culminacion' ? 'Carta_Culminacion_' : 'Constancia_Servicio_') . $cedula;
        
        $pdf->renderDomPdf($html, $nombreArchivo, false);
    }

    // ────────────────────────────────────────────────────────────────
    // MIS ACTIVIDADES
    // ────────────────────────────────────────────────────────────────

    /**
     * GET /pasante/misActividades
     * Listado de actividades diarias registradas por el pasante.
     */
    public function misActividades(): void
    {
        if (Session::get('role_id') != 3) {
            $this->redirect('/dashboard');
            return;
        }

        $userId  = (int)Session::get('user_id');
        $pasante = $this->pasanteModel->getByUsuarioId($userId);

        $this->db->query("
            SELECT ap.id, ap.fecha, ap.titulo, ap.descripcion,
                   ap.correccion, ap.correccion_at,
                   DATE_FORMAT(ap.fecha, '%d/%m/%Y') AS fecha_fmt,
                   DATE_FORMAT(ap.fecha, '%b %Y')    AS mes_label,
                   ap.created_at,
                   CONCAT(dp.nombres, ' ', dp.apellidos) AS corrector_nombre
            FROM actividades_pasante ap
            LEFT JOIN datos_personales dp ON dp.usuario_id = ap.corrector_id
            WHERE ap.pasante_id = :uid
            ORDER BY ap.fecha DESC, ap.created_at DESC
        ");
        $this->db->bind(':uid', $userId);
        $actividades = $this->db->resultSet();

        $this->view('pasante/mis_actividades', [
            'title'      => 'Mis Actividades',
            'pasante'    => $pasante,
            'actividades'=> $actividades,
        ]);
    }

    /**
     * GET /pasante/verificarAsistencia?fecha=YYYY-MM-DD
     * Comprueba si el pasante tiene asistencia (Presente|Retardo) en la fecha indicada.
     */
    public function verificarAsistencia(): void
    {
        header('Content-Type: application/json');

        if (Session::get('role_id') != 3) {
            echo json_encode(['asiste' => false]);
            return;
        }

        $userId = (int)Session::get('user_id');
        $fecha  = trim($_GET['fecha'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(['asiste' => false]);
            return;
        }

        $this->db->query("
            SELECT id FROM asistencias
            WHERE pasante_id = :pid AND fecha = :fecha
              AND estado IN ('Presente', 'Retardo')
            LIMIT 1
        ");
        $this->db->bind(':pid',   $userId);
        $this->db->bind(':fecha', $fecha);

        echo json_encode(['asiste' => (bool)$this->db->single()]);
    }

    /**
     * POST /pasante/guardarActividad  (AJAX JSON)
     * Guarda una nueva actividad diaria del pasante.
     */
    public function guardarActividad(): void
    {
        header('Content-Type: application/json');

        if (Session::get('role_id') != 3) {
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        $userId = (int)Session::get('user_id');
        $json   = file_get_contents('php://input');
        $data   = json_decode($json, true);

        $fecha       = trim($data['fecha']       ?? '');
        $titulo      = trim($data['titulo']      ?? '');
        $descripcion = trim($data['descripcion'] ?? '');

        if (!$fecha || !$titulo || !$descripcion) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos.']);
            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(['success' => false, 'message' => 'Fecha inválida.']);
            return;
        }

        if (strlen($titulo) > 150) {
            echo json_encode(['success' => false, 'message' => 'El título no puede superar 150 caracteres.']);
            return;
        }

        // Verificar que el pasante haya asistido ese día
        $this->db->query("
            SELECT id FROM asistencias
            WHERE pasante_id = :pid AND fecha = :fecha
              AND estado IN ('Presente', 'Retardo')
            LIMIT 1
        ");
        $this->db->bind(':pid',   $userId);
        $this->db->bind(':fecha', $fecha);
        if (!$this->db->single()) {
            echo json_encode([
                'success'      => false,
                'sin_asistencia' => true,
                'message'      => 'No puedes registrar una actividad en una fecha en la que no asististe. Verifica tu registro de asistencia.',
            ]);
            return;
        }

        $this->db->query("
            INSERT INTO actividades_pasante (pasante_id, fecha, titulo, descripcion)
            VALUES (:pid, :fecha, :titulo, :desc)
        ");
        $this->db->bind(':pid',   $userId);
        $this->db->bind(':fecha', $fecha);
        $this->db->bind(':titulo', $titulo);
        $this->db->bind(':desc',  $descripcion);

        if ($this->db->execute()) {
            // Notificar al tutor asignado y a los admins
            try {
                $notifModel = new NotificationModel($this->db);

                // Tutor asignado
                $this->db->query("SELECT tutor_id FROM datos_pasante WHERE usuario_id = :uid AND tutor_id IS NOT NULL LIMIT 1");
                $this->db->bind(':uid', $userId);
                $rowTutor = $this->db->single();

                // Nombre del pasante para el mensaje
                $this->db->query("SELECT nombres, apellidos FROM datos_personales WHERE usuario_id = :uid LIMIT 1");
                $this->db->bind(':uid', $userId);
                $rowPas = $this->db->single();
                $nomPas = $rowPas ? trim($rowPas->nombres . ' ' . $rowPas->apellidos) : 'Un pasante';
                $urlAct = URLROOT . '/asistencias/almanaque/' . $userId;

                if ($rowTutor && $rowTutor->tutor_id) {
                    $notifModel->createWithRef(
                        $rowTutor->tutor_id,
                        'actividad_nueva',
                        'Nueva actividad registrada',
                        "{$nomPas} registró una nueva actividad: {$titulo}",
                        $urlAct,
                        $userId
                    );
                }

                // Admins (rol_id = 1)
                $this->db->query("SELECT id FROM usuarios WHERE rol_id = 1 AND estado = 'activo'");
                $admins = $this->db->resultSet() ?: [];
                foreach ($admins as $admin) {
                    $notifModel->createWithRef(
                        $admin->id,
                        'actividad_nueva',
                        'Nueva actividad registrada',
                        "{$nomPas} registró una nueva actividad: {$titulo}",
                        $urlAct,
                        $userId
                    );
                }
            } catch (\Throwable $e) {
                error_log('[SGP-PASANTE] notif actividad error: ' . $e->getMessage());
            }

            echo json_encode(['success' => true, 'message' => 'Actividad registrada correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar. Intenta de nuevo.']);
        }
    }

    /**
     * POST /pasante/editarActividad  (AJAX JSON)
     * El pasante edita título, descripción o fecha de su propia actividad.
     */
    public function editarActividad(): void
    {
        header('Content-Type: application/json');

        if (Session::get('role_id') != 3) {
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        $userId = (int)Session::get('user_id');
        $json   = file_get_contents('php://input');
        $data   = json_decode($json, true);

        $id          = (int)($data['id']          ?? 0);
        $fecha       = trim($data['fecha']         ?? '');
        $titulo      = trim($data['titulo']        ?? '');
        $descripcion = trim($data['descripcion']   ?? '');

        if (!$id || !$fecha || !$titulo || !$descripcion) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos.']);
            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(['success' => false, 'message' => 'Fecha inválida.']);
            return;
        }

        if (strlen($titulo) > 150) {
            echo json_encode(['success' => false, 'message' => 'El título no puede superar 150 caracteres.']);
            return;
        }

        // Verificar que el pasante haya asistido en la nueva fecha
        $this->db->query("
            SELECT id FROM asistencias
            WHERE pasante_id = :pid AND fecha = :fecha
              AND estado IN ('Presente', 'Retardo')
            LIMIT 1
        ");
        $this->db->bind(':pid',   $userId);
        $this->db->bind(':fecha', $fecha);
        if (!$this->db->single()) {
            echo json_encode([
                'success'        => false,
                'sin_asistencia' => true,
                'message'        => 'No puedes mover la actividad a una fecha en la que no asististe.',
            ]);
            return;
        }

        // WHERE pasante_id garantiza que solo edite sus propias actividades
        $this->db->query("
            UPDATE actividades_pasante
               SET fecha = :fecha, titulo = :titulo, descripcion = :desc
             WHERE id = :id AND pasante_id = :pid
        ");
        $this->db->bind(':fecha',  $fecha);
        $this->db->bind(':titulo', $titulo);
        $this->db->bind(':desc',   $descripcion);
        $this->db->bind(':id',     $id);
        $this->db->bind(':pid',    $userId);

        if ($this->db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Actividad actualizada correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar. Intenta de nuevo.']);
        }
    }

    /**
     * POST /pasante/eliminarActividad  (AJAX JSON)
     * Elimina una actividad propia del pasante.
     */
    public function eliminarActividad(): void
    {
        header('Content-Type: application/json');

        if (Session::get('role_id') != 3) {
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        $userId = (int)Session::get('user_id');
        $json   = file_get_contents('php://input');
        $data   = json_decode($json, true);
        $id     = (int)($data['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        // WHERE pasante_id asegura que solo elimine sus propias actividades
        $this->db->query("DELETE FROM actividades_pasante WHERE id = :id AND pasante_id = :pid");
        $this->db->bind(':id',  $id);
        $this->db->bind(':pid', $userId);

        if ($this->db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Actividad eliminada.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
        }
    }

    /**
     * POST /pasante/corregirActividad  (AJAX JSON)
     * Admin o tutor agrega/edita una corrección sobre la actividad de un pasante.
     */
    public function corregirActividad(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            return;
        }

        $roleId = (int)Session::get('role_id');
        if (!in_array($roleId, [0, 1, 2])) {
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        // [FIX] Verificación CSRF — antes ausente en este endpoint que mutaba datos.
        $this->verifyCsrf();

        $correctorId = (int)Session::get('user_id');
        $json        = file_get_contents('php://input');
        $data        = json_decode($json, true);
        $id          = (int)($data['id'] ?? 0);
        $correccion  = trim($data['correccion'] ?? '');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }
        if (strlen($correccion) > 1000) {
            echo json_encode(['success' => false, 'message' => 'La corrección no puede superar 1000 caracteres.']);
            return;
        }

        // Obtener pasante_id de la actividad para notificarle
        $this->db->query("SELECT pasante_id, titulo FROM actividades_pasante WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        $actRow = $this->db->single();

        if (!$actRow) {
            echo json_encode(['success' => false, 'message' => 'Actividad no encontrada.']);
            return;
        }

        // [FIX-AC-5] Aislamiento por departamento — un Tutor solo puede corregir
        // actividades de pasantes de SU departamento, igual que el resto de la app.
        if ($roleId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $correctorId);
            $_deptId = (int)($this->db->single()->departamento_id ?? 0);

            $this->db->query("SELECT departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
            $this->db->bind(':pid', $actRow->pasante_id);
            $chk = $this->db->single();
            if (!$chk || (int)($chk->departamento_asignado_id ?? 0) !== $_deptId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Acceso denegado: este pasante no pertenece a su departamento.']);
                return;
            }
        }

        if ($correccion === '') {
            // Limpiar corrección
            $this->db->query("UPDATE actividades_pasante SET correccion = NULL, corrector_id = NULL, correccion_at = NULL WHERE id = :id");
            $this->db->bind(':id', $id);
        } else {
            $this->db->query("UPDATE actividades_pasante SET correccion = :c, corrector_id = :cid, correccion_at = NOW() WHERE id = :id");
            $this->db->bind(':c',   $correccion);
            $this->db->bind(':cid', $correctorId);
            $this->db->bind(':id',  $id);
        }

        if ($this->db->execute()) {
            // Notificar al pasante si hay corrección nueva
            if ($correccion !== '') {
                try {
                    $notifModel = new NotificationModel($this->db);
                    $this->db->query("SELECT nombres, apellidos FROM datos_personales WHERE usuario_id = :uid LIMIT 1");
                    $this->db->bind(':uid', $correctorId);
                    $rowCorr = $this->db->single();
                    $nomCorr = $rowCorr ? trim($rowCorr->nombres . ' ' . $rowCorr->apellidos) : 'Tu tutor';

                    $notifModel->createWithRef(
                        $actRow->pasante_id,
                        'actividad_corregida',
                        'Tu actividad tiene una corrección',
                        "{$nomCorr} agregó una corrección a tu actividad: {$actRow->titulo}",
                        URLROOT . '/pasante/misActividades',
                        $id
                    );
                } catch (\Throwable $e) {
                    error_log('[SGP-PASANTE] notif correccion error: ' . $e->getMessage());
                }
            }
            echo json_encode(['success' => true, 'message' => 'Corrección guardada.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la corrección.']);
        }
    }

    /**
     * POST /pasante/eliminarActividadAdmin  (AJAX JSON)
     * Admin o superadmin elimina cualquier actividad de un pasante.
     */
    public function eliminarActividadAdmin(): void
    {
        header('Content-Type: application/json');

        $roleId = (int)Session::get('role_id');
        if (!in_array($roleId, [0, 1])) {
            echo json_encode(['success' => false, 'message' => 'No autorizado.']);
            return;
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $id   = (int)($data['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        $this->db->query("DELETE FROM actividades_pasante WHERE id = :id");
        $this->db->bind(':id', $id);

        if ($this->db->execute()) {
            echo json_encode(['success' => true, 'message' => 'Actividad eliminada.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
        }
    }

    /**
     * Query compartida entre constancia() y descargarConstancia().
     */
    private function _getConstanciaData(int $userId): ?object
    {
        $this->db->query("
            SELECT
                dp.nombres, dp.apellidos, dp.telefono,
                u.cedula, u.correo AS email,
                COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio_pasantia,
                COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin_estimada,
                dpa.horas_meta,
                COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas,
                dpa.estado_pasantia,
                COALESCE(inst.nombre, dpa.institucion_procedencia) AS institucion,
                d.nombre  AS departamento,
                CONCAT(tp.nombres, ' ', tp.apellidos) AS tutor_nombre,
                tp.cargo  AS tutor_cargo,
                tu.cedula AS tutor_cedula
            FROM usuarios u
            JOIN  datos_personales dp  ON dp.usuario_id  = u.id
            LEFT JOIN datos_pasante        dpa  ON dpa.usuario_id = u.id
            LEFT JOIN periodos_academicos  pa   ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos        d    ON d.id   = dpa.departamento_asignado_id
            LEFT JOIN instituciones        inst ON inst.id = dpa.institucion_procedencia
            LEFT JOIN datos_personales     tp   ON tp.usuario_id  = dpa.tutor_id
            LEFT JOIN usuarios             tu   ON tu.id = dpa.tutor_id
            WHERE u.id = :uid AND u.rol_id = 3
            ORDER BY FIELD(COALESCE(dpa.estado_pasantia, 'Sin Asignar'), 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, dpa.id DESC
            LIMIT 1
        ");
        $this->db->bind(':uid', $userId);
        return $this->db->single() ?: null;
    }

    // ────────────────────────────────────────────────────────────────
    // MÓDULO DE EXÁMENES DEL PASANTE
    // ────────────────────────────────────────────────────────────────

    /**
     * GET /pasante/misExamenes
     * Lista de exámenes disponibles y completados para el pasante.
     */
    public function misExamenes(): void
    {
        if (Session::get('role_id') != 3) {
            $this->redirect('/auth/login');
            return;
        }

        $userId = (int)Session::get('user_id');

        // Verificar que la pasantía esté activa
        $this->db->query("
            SELECT estado_pasantia FROM datos_pasante
            WHERE usuario_id = :uid
            ORDER BY FIELD(estado_pasantia, 'Activo', 'Pendiente', 'Sin Asignar', 'Finalizado', 'Retirado') ASC, id DESC
            LIMIT 1
        ");
        $this->db->bind(':uid', $userId);
        $dpRow = $this->db->single();

        if (!$dpRow || ($dpRow->estado_pasantia ?? '') !== 'Activo') {
            Session::setFlash('error', 'Solo los pasantes con pasantía activa pueden ver los exámenes.');
            $this->redirect('/pasante/dashboard');
            return;
        }

        $pasanteInfo = $this->pasanteModel->getByUsuarioId($userId);

        // Exámenes activos dentro del rango de fechas + estado de intento del pasante
        $this->db->query("
            SELECT
                e.id,
                e.titulo,
                e.descripcion,
                e.fecha_inicio,
                e.fecha_fin,
                e.intentos_permitidos,
                (SELECT COUNT(*) FROM examen_preguntas ep WHERE ep.examen_id = e.id) AS total_preguntas,
                ei.id          AS intento_id,
                ei.puntaje_obtenido,
                ei.puntaje_maximo,
                ei.porcentaje,
                ei.enviado_at,
                ei.revisado_at
            FROM examenes e
            LEFT JOIN examen_intentos ei
                ON ei.examen_id  = e.id
               AND ei.pasante_id = :uid
               AND ei.enviado_at IS NOT NULL
            WHERE e.activo = 1
              AND (
                    (e.fecha_inicio IS NULL AND e.fecha_fin IS NULL)
                 OR (CURDATE() BETWEEN e.fecha_inicio AND e.fecha_fin)
              )
            ORDER BY e.created_at DESC
        ");
        $this->db->bind(':uid', $userId);
        $examenes = $this->db->resultSet();

        $this->view('pasante/mis_examenes', [
            'examenes' => $examenes,
            'pasante'  => $pasanteInfo,
        ]);
    }

    /**
     * GET /pasante/tomarExamen/{id}
     * Carga la interfaz de quiz paso a paso para el pasante.
     */
    public function tomarExamen($id = null): void
    {
        if (Session::get('role_id') != 3) {
            $this->redirect('/auth/login');
            return;
        }

        $id     = (int)($id ?? 0);
        $userId = (int)Session::get('user_id');

        if ($id <= 0) {
            $this->redirect('/pasante/misExamenes');
            return;
        }

        // Cargar examen (activo y dentro de rango)
        $this->db->query("
            SELECT * FROM examenes
            WHERE id = :id AND activo = 1
              AND (
                    (fecha_inicio IS NULL AND fecha_fin IS NULL)
                 OR (CURDATE() BETWEEN fecha_inicio AND fecha_fin)
              )
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        $examen = $this->db->single();

        if (!$examen) {
            Session::setFlash('error', 'El examen no está disponible.');
            $this->redirect('/pasante/misExamenes');
            return;
        }

        // Verificar si ya completó el examen
        $this->db->query("
            SELECT id FROM examen_intentos
            WHERE examen_id = :eid AND pasante_id = :uid AND enviado_at IS NOT NULL
            LIMIT 1
        ");
        $this->db->bind(':eid', $id);
        $this->db->bind(':uid', $userId);
        $completado = $this->db->single();

        if ($completado) {
            Session::setFlash('error', 'Ya completaste este examen.');
            $this->redirect('/pasante/misExamenes');
            return;
        }

        // Buscar intento en curso o crear uno nuevo
        $this->db->query("
            SELECT id FROM examen_intentos
            WHERE examen_id = :eid AND pasante_id = :uid AND enviado_at IS NULL
            LIMIT 1
        ");
        $this->db->bind(':eid', $id);
        $this->db->bind(':uid', $userId);
        $intentoExistente = $this->db->single();

        if ($intentoExistente) {
            $intentoId = (int)$intentoExistente->id;
        } else {
            $this->db->query("
                INSERT INTO examen_intentos (examen_id, pasante_id, iniciado_at, enviado_at, puntaje_obtenido, puntaje_maximo, porcentaje)
                VALUES (:eid, :uid, NOW(), NULL, 0, 0, 0)
            ");
            $this->db->bind(':eid', $id);
            $this->db->bind(':uid', $userId);
            $this->db->execute();
            $intentoId = (int)$this->db->lastInsertId();
        }

        // Cargar preguntas con opciones (es_correcta incluido para scoring server-side)
        $this->db->query("
            SELECT id, orden, enunciado, tipo, puntos
            FROM examen_preguntas
            WHERE examen_id = :eid
            ORDER BY orden ASC
        ");
        $this->db->bind(':eid', $id);
        $preguntas = $this->db->resultSet();

        foreach ($preguntas as $preg) {
            $this->db->query("SELECT id, texto, es_correcta FROM examen_opciones WHERE pregunta_id = :pid");
            $this->db->bind(':pid', $preg->id);
            $opciones = $this->db->resultSet();
            shuffle($opciones);
            $preg->opciones = $opciones;
        }

        $this->view('pasante/tomar_examen', [
            'examen'    => $examen,
            'preguntas' => $preguntas,
            'intentoId' => $intentoId,
        ]);
    }

    /**
     * POST /pasante/enviarExamen  (AJAX JSON)
     * Recibe las respuestas, califica y guarda el resultado.
     */
    public function enviarExamen(): void
    {
        header('Content-Type: application/json');

        if (Session::get('role_id') != 3) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $userId     = (int)Session::get('user_id');
        $body       = json_decode(file_get_contents('php://input'), true);
        $intentoId  = (int)($body['intento_id'] ?? 0);
        $respuestas = $body['respuestas'] ?? [];

        if ($intentoId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Intento inválido']);
            return;
        }

        // Verificar que el intento pertenece a este pasante y no ha sido enviado
        $this->db->query("
            SELECT ei.id, ei.examen_id
            FROM examen_intentos ei
            WHERE ei.id = :iid AND ei.pasante_id = :uid AND ei.enviado_at IS NULL
            LIMIT 1
        ");
        $this->db->bind(':iid', $intentoId);
        $this->db->bind(':uid', $userId);
        $intento = $this->db->single();

        if (!$intento) {
            echo json_encode(['success' => false, 'error' => 'Intento no válido o ya enviado']);
            return;
        }

        // [FIX-N-9] "Reclamo" atómico ANTES de insertar nada — el SELECT de arriba no
        // basta contra un doble-submit (dos requests casi simultáneos pueden pasar
        // ambos el SELECT antes de que cualquiera actualice enviado_at). Este UPDATE
        // con WHERE enviado_at IS NULL solo puede afectar una fila una sola vez; si
        // rowCount() da 0, otra request ya se adelantó y esta debe abortar sin tocar
        // examen_respuestas.
        $this->db->query("
            UPDATE examen_intentos
            SET enviado_at = NOW()
            WHERE id = :iid AND pasante_id = :uid AND enviado_at IS NULL
        ");
        $this->db->bind(':iid', $intentoId);
        $this->db->bind(':uid', $userId);
        $this->db->execute();
        if ($this->db->rowCount() === 0) {
            echo json_encode(['success' => false, 'error' => 'Este intento ya fue enviado.']);
            return;
        }

        $examenId = (int)$intento->examen_id;

        // Cargar preguntas del examen con opciones correctas
        $this->db->query("
            SELECT ep.id AS pregunta_id, ep.puntos,
                   eo.id AS opcion_id, eo.es_correcta, eo.texto
            FROM examen_preguntas ep
            JOIN examen_opciones eo ON eo.pregunta_id = ep.id
            WHERE ep.examen_id = :eid
        ");
        $this->db->bind(':eid', $examenId);
        $rows = $this->db->resultSet();

        // Organizar por pregunta
        $preguntasMap = [];
        foreach ($rows as $row) {
            $pid = (int)$row->pregunta_id;
            if (!isset($preguntasMap[$pid])) {
                $preguntasMap[$pid] = ['puntos' => (float)$row->puntos, 'opciones' => []];
            }
            $preguntasMap[$pid]['opciones'][(int)$row->opcion_id] = [
                'es_correcta' => (bool)$row->es_correcta,
                'texto'       => $row->texto,
            ];
        }

        // Mapear respuestas enviadas
        $respMap = [];
        foreach ($respuestas as $r) {
            $pid = (int)($r['pregunta_id'] ?? 0);
            $oid = (int)($r['opcion_id']   ?? 0);
            if ($pid > 0) $respMap[$pid] = $oid;
        }

        // Calificar y guardar respuestas
        $puntajeObtenido = 0.0;
        $puntajeMaximo   = 0.0;
        $resultados      = [];

        foreach ($preguntasMap as $pid => $pregData) {
            $puntajeMaximo  += $pregData['puntos'];
            $opcionElegidaId = $respMap[$pid] ?? 0;
            $esCorrecta      = false;
            $opcionCorrectaId = 0;
            $textoCorrecta   = '';

            foreach ($pregData['opciones'] as $oid => $opData) {
                if ($opData['es_correcta']) {
                    $opcionCorrectaId = $oid;
                    $textoCorrecta    = $opData['texto'];
                }
            }

            if ($opcionElegidaId > 0 && isset($pregData['opciones'][$opcionElegidaId])) {
                $esCorrecta = $pregData['opciones'][$opcionElegidaId]['es_correcta'];
            }

            if ($esCorrecta) {
                $puntajeObtenido += $pregData['puntos'];
            }

            if ($opcionElegidaId > 0) {
                $this->db->query("
                    INSERT INTO examen_respuestas (intento_id, pregunta_id, opcion_id_elegida)
                    VALUES (:iid, :pid, :oid)
                ");
                $this->db->bind(':iid', $intentoId);
                $this->db->bind(':pid', $pid);
                $this->db->bind(':oid', $opcionElegidaId);
                $this->db->execute();
            }

            $resultados[] = [
                'pregunta_id'       => $pid,
                'opcion_elegida_id' => $opcionElegidaId,
                'es_correcta'       => $esCorrecta,
                'opcion_correcta_id'=> $opcionCorrectaId,
                'texto_correcto'    => $textoCorrecta,
            ];
        }

        $porcentaje = $puntajeMaximo > 0
            ? round(($puntajeObtenido / $puntajeMaximo) * 100, 2)
            : 0.0;

        // [FIX-N-9] enviado_at ya quedó fijado por el reclamo atómico de arriba —
        // este UPDATE solo completa los puntajes.
        $this->db->query("
            UPDATE examen_intentos
            SET puntaje_obtenido = :po,
                puntaje_maximo   = :pm,
                porcentaje       = :pct
            WHERE id = :iid
        ");
        $this->db->bind(':po',  $puntajeObtenido);
        $this->db->bind(':pm',  $puntajeMaximo);
        $this->db->bind(':pct', $porcentaje);
        $this->db->bind(':iid', $intentoId);
        $this->db->execute();

        AuditModel::log('ENVIO_EXAMEN', 'examen_intentos', $intentoId, [
            'examen_id'       => $examenId,
            'puntaje_obtenido'=> $puntajeObtenido,
            'puntaje_maximo'  => $puntajeMaximo,
            'porcentaje'      => $porcentaje,
            'aprobado'        => $porcentaje >= 60,
        ]);

        // Notificar al tutor asignado y a los admins
        try {
            $notifModel = new NotificationModel($this->db);

            $this->db->query("SELECT nombres, apellidos FROM datos_personales WHERE usuario_id = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $rowPas = $this->db->single();
            $nomPas = $rowPas ? trim($rowPas->nombres . ' ' . $rowPas->apellidos) : 'Un pasante';

            $this->db->query("SELECT titulo, creado_por FROM examenes WHERE id = :eid LIMIT 1");
            $this->db->bind(':eid', $examenId);
            $exRow        = $this->db->single();
            $tituloExamen = $exRow ? $exRow->titulo : 'Examen';
            $creadoPor    = $exRow ? (int)$exRow->creado_por : 0;

            $urlRes = URLROOT . '/examenes/ver/' . $examenId;
            $pctFmt = number_format($porcentaje, 1);
            $msg    = "{$nomPas} completó el examen \"{$tituloExamen}\" con un {$pctFmt}%.";

            // Notificar al creador del examen
            if ($creadoPor > 0) {
                $notifModel->create($creadoPor, 'examen_enviado', 'Examen completado', $msg, $urlRes);
            }

            // Notificar a todos los Admins (rol 1) y SuperAdmins (rol 0)
            $this->db->query("SELECT id, rol_id AS role_id FROM usuarios WHERE rol_id IN (0,1) AND estado = 'activo'");
            foreach (($this->db->resultSet() ?: []) as $admin) {
                if ((int)$admin->id !== $creadoPor) {
                    $notifModel->create((int)$admin->id, 'examen_enviado', 'Examen completado', $msg, $urlRes);
                }
            }

            // Notificar al tutor asignado al pasante (rol 2)
            $this->db->query("
                SELECT tutor_id FROM datos_pasante
                WHERE usuario_id = :uid AND tutor_id IS NOT NULL
                LIMIT 1
            ");
            $this->db->bind(':uid', $userId);
            $rowTutor = $this->db->single();
            if ($rowTutor && (int)$rowTutor->tutor_id > 0) {
                $tutorId = (int)$rowTutor->tutor_id;
                if ($tutorId !== $creadoPor) {
                    $notifModel->create($tutorId, 'examen_enviado', 'Examen completado', $msg, $urlRes);
                }
            }
        } catch (\Throwable $e) {
            error_log('[SGP-EXAMENES] notify-enviar: ' . $e->getMessage());
        }

        echo json_encode([
            'success'          => true,
            'puntaje_obtenido' => $puntajeObtenido,
            'puntaje_maximo'   => $puntajeMaximo,
            'porcentaje'       => $porcentaje,
            'aprobado'         => $porcentaje >= 60,
            'resultados'       => $resultados,
        ]);
    }
}
