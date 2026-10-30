<?php
/**
 * ReportesController - Gestión de Reportes y Exportaciones
 * 
 * Este controlador centraliza la generación de informes técnicos y administrativos,
 * manejando tanto formatos genéricos como plantillas institucionales del ISP.
 */

class ReportesController extends Controller
{
    private $db;
    private $pdf;

    public function __construct()
    {
        Session::start();
        if (!Session::get('user_id') || Session::get('role_id') > 2) {
            header('Location: ' . URLROOT . '/dashboard');
            exit;
        }

        $this->db = Database::getInstance();
        require_once '../app/lib/PdfGenerator.php';
        $this->pdf = new PdfGenerator();
    }

    /**
     * Vista Principal de Informes
     */
    public function index()
    {
        // Cargar datos para filtros dinámicos
        $this->db->query("SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
        $departamentos = $this->db->resultSet();

        $this->db->query("SELECT id, nombre FROM instituciones ORDER BY nombre ASC");
        $instituciones = $this->db->resultSet();

        // MULTI-TENANT: Si el usuario es Tutor (role=2), solo ve sus propios pasantes asignados.
        $rolId  = (int)Session::get('role_id');
        $userId = (int)Session::get('user_id');

        if ($rolId === 2) {
            // Pasantes del mismo departamento que el tutor autenticado
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $userId);
            $deptId = (int)($this->db->single()->departamento_id ?? 0);

            $this->db->query("
                SELECT u.id, CONCAT(dp.nombres, ' ', dp.apellidos) as nombre 
                FROM usuarios u 
                JOIN datos_personales dp ON u.id = dp.usuario_id 
                JOIN datos_pasante dpa   ON dpa.usuario_id = u.id
                WHERE u.rol_id = 3 
                  AND u.estado = 'activo'
                  AND dpa.departamento_asignado_id = :dept_id
                ORDER BY dp.nombres ASC
            ");
            $this->db->bind(':dept_id', $deptId);
        } else {
            // Admin — todos los pasantes activos
            $this->db->query("
                SELECT u.id, CONCAT(dp.nombres, ' ', dp.apellidos) as nombre 
                FROM usuarios u 
                JOIN datos_personales dp ON u.id = dp.usuario_id 
                WHERE u.rol_id = 3 AND u.estado = 'activo'
                ORDER BY dp.nombres ASC
            ");
        }
        // Técnicos y Tutores del sistema para firmas y asignación en constancias
        $this->db->query("
            SELECT u.id, u.cedula,
                   CONCAT(dp.nombres, ' ', dp.apellidos) as nombre,
                   COALESCE(NULLIF(dp.cargo, ''), 'Técnico') as cargo,
                   COALESCE(d.nombre, 'Soporte Técnico') as departamento
            FROM usuarios u
            JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN departamentos d ON d.id = u.departamento_id
            WHERE u.rol_id IN (1, 2) AND u.estado = 'activo'
            ORDER BY dp.nombres ASC
        ");
        $tutores = $this->db->resultSet() ?: [];

        $this->view('reportes/index', [
            'title'         => 'Centro de Reportes',
            'departamentos' => $departamentos,
            'instituciones' => $instituciones,
            'pasantes'      => $pasantes,
            'tutores'       => $tutores,
            'rolId'         => $rolId,   // ← para filtrar tarjetas admin-only en la vista
        ]);
    }

    /**
     * GET /reportes/ejecutivo — Resumen Ejecutivo directo (histórico, sin filtros)
     */
    public function ejecutivo(): void
    {
        // [FIX-AC-3] Módulo admin-only (mismo criterio que exportar()'s $modulosAdminOnly) —
        // antes un Tutor podía llamar esta ruta directo y saltarse esa restricción por
        // completo, obteniendo el resumen ejecutivo institucional de todos los departamentos.
        if ((int)Session::get('role_id') === 2) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }
        $this->generarResumenEjecutivo(['tipo_periodo' => 'historico']);
    }

    /**
     * GET /reportes/pdfKardex/{id} — Kardex individual de asistencias de un pasante
     */
    public function pdfKardex($pasanteId = null): void
    {
        $pasanteId = (int)($pasanteId ?? $_GET['id'] ?? 0);

        if (!$pasanteId) {
            $this->redirect('/reportes');
            return;
        }

        // RBAC: tutor solo puede exportar pasantes de su departamento
        $rolId    = (int)Session::get('role_id');
        $myUserId = (int)Session::get('user_id');
        if ($rolId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', $myUserId);
            $_deptId = (int)($this->db->single()->departamento_id ?? 0);

            $this->db->query("SELECT departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
            $this->db->bind(':pid', $pasanteId);
            $chk = $this->db->single();
            if (!$chk || (int)($chk->departamento_asignado_id ?? 0) !== $_deptId) {
                http_response_code(403);
                echo 'Acceso denegado.';
                exit;
            }
        }

        // Datos del pasante (incluye horas_meta y fechas individuales)
        $this->db->query("
            SELECT u.id, u.cedula, dp.nombres, dp.apellidos,
                   d.nombre                               AS departamento,
                   COALESCE(inst.nombre, '—')             AS institucion_nombre,
                   COALESCE(NULLIF(dpa.horas_meta, 0), 1440)      AS horas_meta,
                   COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio,
                   COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin,
                   dpa.estado_pasantia,
                   COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas
            FROM   usuarios u
            LEFT JOIN datos_personales dp    ON dp.usuario_id  = u.id
            LEFT JOIN datos_pasante    dpa   ON dpa.usuario_id = u.id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos    d     ON d.id = COALESCE(dpa.departamento_asignado_id, u.departamento_id)
            LEFT JOIN instituciones    inst  ON inst.id = dpa.institucion_id
            WHERE u.id = :pid AND u.rol_id = 3
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $pasante = $this->db->single();

        if (!$pasante) {
            $this->redirect('/reportes');
            return;
        }

        // Historial de asistencias: solo los primeros floor(horas_meta/8) días válidos
        // (evita que un período con 181 días L-V muestre un registro extra más allá de la meta)
        $diasMax = max(1, (int)floor((float)$pasante->horas_meta / 8));
        $this->db->query("
            SELECT fecha, hora_entrada, hora_salida, estado, metodo, motivo, feriado_nombre
            FROM (
                SELECT a.fecha,
                       COALESCE(a.hora_entrada, '08:00:00') AS hora_entrada,
                       COALESCE(a.hora_salida,  '16:00:00') AS hora_salida,
                       a.estado, a.metodo,
                       COALESCE(NULLIF(a.motivo_justificacion,''), NULLIF(a.observacion,'')) AS motivo,
                       df.nombre AS feriado_nombre
                FROM asistencias a
                LEFT JOIN dias_feriados df ON df.fecha = a.fecha
                WHERE a.pasante_id = :pid
                ORDER BY a.fecha ASC
                LIMIT {$diasMax}
            ) t
            ORDER BY fecha DESC
        ");
        $this->db->bind(':pid', $pasanteId);
        $asistencias = $this->db->resultSet();

        $subtitulo_pdf = htmlspecialchars(
            ($pasante->apellidos ?? '') . ', ' . ($pasante->nombres ?? '')
            . ' — C.I. ' . ($pasante->cedula ?? '—')
        );

        ob_start();
        include '../app/views/reportes/pdf_asistencia_individual.php';
        $html = ob_get_clean();

        $this->pdf->renderDomPdf($html, 'Ficha_Personal_' . ($pasante->cedula ?? $pasanteId), false);
    }

    /**
     * Procesar Solicitud de Exportación
     */
    public function exportar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/reportes');
            return;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Token inválido. Recarga la página e intenta de nuevo.']);
            exit;
        }

        $modulo  = $_POST['modulo'] ?? '';
        $tipo    = $_POST['tipo'] ?? 'pdf'; // pdf o excel
        $filtros = $_POST;

        // ── RBAC: AISLAMIENTO POR TUTOR ────────────────────────────────────────
        // Si es Tutor (role=2) y el módulo exporta datos de un pasante específico,
        // se verifica que ese pasante pertenezca al tutor autenticado.
        // Esto previene que un Tutor altere el DOM o la URL para exportar datos ajenos.
        $rolId    = (int)Session::get('role_id');
        $myUserId = (int)Session::get('user_id');

        if ($rolId === 2) {
            $pasanteIdPost = isset($_POST['pasante_id']) ? (int)$_POST['pasante_id'] : null;

            // Si el módulo no es de pasantes individuales, bloquear módulos sensibles
            $modulosAdminOnly = ['ejecutivo', 'usuarios'];
            if (in_array($modulo, $modulosAdminOnly)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Módulo restringido para su rol.']);
                exit;
            }

            // Si viene un pasante_id explícito, verificar que sea del mismo departamento
            if ($pasanteIdPost > 0) {
                $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
                $this->db->bind(':uid', $myUserId);
                $_deptId = (int)($this->db->single()->departamento_id ?? 0);

                $this->db->query("SELECT departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
                $this->db->bind(':pid', $pasanteIdPost);
                $chk = $this->db->single();
                if (!$chk || (int)($chk->departamento_asignado_id ?? 0) !== $_deptId) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Acceso denegado: este pasante no pertenece a su departamento.',
                    ]);
                    exit;
                }
            }
        }
        // ── Fin RBAC ────────────────────────────────────────────────────────────

        // Lógica de Enrutamiento
        switch ($modulo) {
            case 'ejecutivo':
                $this->generarResumenEjecutivo($filtros);
                break;

            case 'usuarios':
                $this->generarDirectorioUsuarios($filtros);
                break;

            case 'pasantes':
                $this->generarRegistroPasantes($filtros);
                break;

            case 'evaluaciones':
                if (isset($filtros['formato_isp'])) {
                    $this->generarEvaluacionISP($filtros);
                } else {
                    $this->generarEvaluacionISP($filtros); // siempre usa planilla ISP
                }
                break;

            case 'asistencias':
                if (isset($filtros['formato_trimestral'])) {
                    $this->generarAsistenciaTrimestralISP($filtros);
                } else {
                    $this->generarReporteAsistencias($filtros);
                }
                break;

            case 'bitacora':
                $this->generarBitacora($filtros);
                break;

            case 'asignaciones':
                $this->generarAsignaciones($filtros);
                break;

            case 'diaria':
                $this->generarFichaDiaria($filtros);
                break;

            case 'constancias':
                $this->generarConstancias($filtros);
                break;

            default:
                $this->generarReporteGenerico(ucfirst($modulo), $filtros);
                break;
        }
    }

    /**
     * Reporte Generico usando wrapHtml
     */
    private function generarReporteGenerico($titulo, $filtros)
    {
        // Esta es una implementación simplificada para el centro de informes
        // En un caso real, cada módulo tendría su propia lógica de consulta
        $html = "<h1>Reporte de $titulo</h1>";
        $html .= "<p>Generado el: " . date('d/m/Y H:i') . "</p>";
        $html .= "<table border='1' width='100%' style='border-collapse: collapse;'>";
        $html .= "<thead><tr><th>Filtros Aplicados</th></tr></thead>";
        $html .= "<tbody>";
        foreach ($filtros as $key => $val) {
            if ($key != 'modulo' && $key != 'tipo') {
                $html .= "<tr><td><strong>$key:</strong> $val</td></tr>";
            }
        }
        $html .= "</tbody></table>";

        $wrappedHtml = $this->pdf->wrapHtml($html, "Reporte_SGP_$titulo");
        $this->pdf->renderDomPdf($wrappedHtml, "Reporte_$titulo" . "_" . date('Ymd'), false);
    }

    /**
     * Hito 3: Plantilla Institucional de Evaluación ISP
     */
    private function generarEvaluacionISP($filtros)
    {
        $pasante_id = $filtros['pasante_id'] ?? null;
        if (!$pasante_id) {
            $this->renderErrorPage('Datos incompletos', 'Debe seleccionar un pasante para generar la planilla.');
        }

        $this->db->query("
            SELECT e.*,
                   dp.nombres, dp.apellidos, u.cedula,
                   COALESCE(inst.nombre, dpa.institucion_procedencia, 'N/D') AS inst_nombre,
                   COALESCE(d.nombre, 'N/D') AS departamento,
                   dpa.fecha_inicio_pasantia, dpa.fecha_fin_estimada, dpa.horas_meta,
                   t.nombres AS tutor_nombres, t.apellidos AS tutor_apellidos,
                   adm.nombres AS jefe_nombres, adm.apellidos AS jefe_apellidos,
                   adm.cargo AS jefe_cargo, adm.telefono AS jefe_tel
            FROM evaluaciones e
            JOIN usuarios          u   ON u.id = e.pasante_id
            JOIN datos_personales  dp  ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            LEFT JOIN instituciones inst ON inst.id = dpa.institucion_id
            LEFT JOIN departamentos d   ON d.id = dpa.departamento_asignado_id
            LEFT JOIN datos_personales t ON t.usuario_id = e.tutor_id
            LEFT JOIN (
                SELECT dp2.nombres, dp2.apellidos, dp2.cargo, dp2.telefono
                FROM datos_personales dp2
                JOIN usuarios u2 ON u2.id = dp2.usuario_id
                WHERE u2.rol_id = 1 AND u2.estado = 'activo'
                ORDER BY u2.id ASC LIMIT 1
            ) AS adm ON 1=1
            WHERE e.pasante_id = :pid
            ORDER BY e.created_at DESC LIMIT 1
        ");
        $this->db->bind(':pid', $pasante_id);
        $evaluacion = $this->db->single();

        if (!$evaluacion) {
            $this->renderErrorPage(
                'Sin evaluación registrada',
                'El pasante seleccionado aún no tiene una evaluación registrada en el sistema. Debe completar la evaluación antes de imprimir la planilla.'
            );
        }

        // Cargar vista como variable (buffer)
        ob_start();
        $eval = $evaluacion;
        include '../app/views/reportes/pdf_evaluacion_isp.php';
        $html = ob_get_clean();

        // Renderizar SIN wrapHtml y en modo INLINE (false)
        $this->pdf->renderDomPdf($html, "Evaluacion_ISP_" . $evaluacion->cedula, false);
    }

    private function renderErrorPage(string $titulo, string $mensaje): never
    {
        http_response_code(400); // Changed from 500 to prevent global server error handler takeover
        echo '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body { font-family: Helvetica, Arial, sans-serif; font-size: 13px;
       color: #333; display: flex; align-items: center;
       justify-content: center; min-height: 100vh; margin: 0; }
.box { text-align: center; max-width: 420px; padding: 40px; }
.box h2 { color: #162660; font-size: 16px; margin-bottom: 12px; }
.box p  { color: #555; line-height: 1.6; margin-bottom: 24px; }
.btn { display: inline-block; padding: 8px 20px; background: #162660;
       color: #fff; border-radius: 6px; text-decoration: none;
       font-size: 12px; cursor: pointer; border: none; }
</style>
</head>
<body>
<div class="box">
  <h2>' . htmlspecialchars($titulo) . '</h2>
  <p>'  . htmlspecialchars($mensaje) . '</p>
  <button class="btn" onclick="window.close()">Cerrar</button>
</div>
</body>
</html>';
        exit;
    }

    /**
     * Hito 4: Planilla Trimestral de Asistencias ISP
     */
    private function generarAsistenciaTrimestralISP(array $filtros): void
    {
        $pasante_id    = $filtros['pasante_id'] ?? null;
        $trimestre_raw = (int)($filtros['trimestre'] ?? 1);
        $todos         = ($trimestre_raw === 0);
        $trimestres    = $todos ? [1, 2, 3] : [max(1, min(3, $trimestre_raw))];

        if (!$pasante_id) {
            $this->renderErrorPage(
                'Datos incompletos',
                'Debe seleccionar un pasante para generar la planilla.'
            );
        }

        try {

            // ── 1. Lapso del pasante — individual primero, período como fallback ──
            $this->db->query("
                SELECT
                    COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio_pasantia,
                    COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin_estimada
                FROM datos_pasante dpa
                LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
                WHERE dpa.usuario_id = :pid
            ");
            $this->db->bind(':pid', $pasante_id);
            $lapso = $this->db->single();

            if (!$lapso || !$lapso->fecha_inicio_pasantia) {
                $this->renderErrorPage(
                    'Pasante sin fechas',
                    'No se encontraron fechas de inicio/fin para este pasante. Asegúrese de que esté asignado a un período activo.'
                );
            }

            // ── 2. Datos del pasante — fechas individuales con fallback al período ──
            $this->db->query("
                SELECT
                    dp.nombres,
                    dp.apellidos,
                    u.cedula,
                    d.nombre                                                    AS departamento,
                    COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio)        AS fecha_inicio_pasantia,
                    COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)            AS fecha_fin_estimada,
                    COALESCE(NULLIF(dpa.horas_meta, 0),
                             ROUND(DATEDIFF(COALESCE(dpa.fecha_fin_estimada, pa.fecha_fin),
                                           COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio)) * 5/7) * 8,
                             1440)                                              AS horas_meta,
                    CONCAT(dpt.nombres, ' ', dpt.apellidos)                     AS tutor_nombre,
                    dpt.cargo                                                   AS tutor_cargo
                FROM usuarios u
                JOIN datos_personales dp       ON dp.usuario_id   = u.id
                JOIN datos_pasante dpa         ON dpa.usuario_id  = u.id
                LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
                LEFT JOIN departamentos d      ON d.id = dpa.departamento_asignado_id
                LEFT JOIN usuarios ut          ON ut.id  = dpa.tutor_id
                LEFT JOIN datos_personales dpt ON dpt.usuario_id = dpa.tutor_id
                WHERE u.id = :pid AND u.rol_id = 3
            ");
            $this->db->bind(':pid', $pasante_id);
            $pasante = $this->db->single();

            if (!$pasante) {
                $this->renderErrorPage(
                    'Pasante no encontrado',
                    'No se encontraron datos para el pasante seleccionado.'
                );
            }

            // ── 3. Generar una página HTML por cada trimestre ──
            $paginas_html = [];

            foreach ($trimestres as $trimestre) {
                // Trimestre 1 = semanas  1-14, Trimestre 2 = 15-28, Trimestre 3 = 29-42
                // El anclaje usa la fecha individual del pasante (con fallback al período ya resuelto arriba)
                $inicio_lapso = new DateTime($lapso->fecha_inicio_pasantia);
                $dias_offset  = ($trimestre - 1) * 98;
                $inicio_trim  = (clone $inicio_lapso)->modify("+{$dias_offset} days");
                $fin_trim     = (clone $inicio_trim)->modify('+97 days');

                $fecha_inicio = $inicio_trim->format('Y-m-d');
                $fecha_fin    = $fin_trim->format('Y-m-d');

                // Extender rango a la semana calendario completa (Dom–Sáb)
                $dow_ini        = (int)$inicio_trim->format('w');
                $q_inicio       = (clone $inicio_trim)->modify("-{$dow_ini} days");
                $dow_fin_v      = (int)$fin_trim->format('w');
                $dias_hasta_sab = (6 - $dow_fin_v + 7) % 7;
                $q_fin          = (clone $fin_trim)->modify("+{$dias_hasta_sab} days");

                $this->db->query("
                    SELECT fecha, hora_entrada, hora_salida, estado
                    FROM asistencias
                    WHERE pasante_id = :pid
                      AND fecha BETWEEN :inicio AND :fin
                    ORDER BY fecha ASC
                ");
                $this->db->bind(':pid',    $pasante_id);
                $this->db->bind(':inicio', $q_inicio->format('Y-m-d'));
                $this->db->bind(':fin',    $q_fin->format('Y-m-d'));
                $asistencias_raw = $this->db->resultSet();

                // Indexar por fecha y día-de-semana usando DateTime (timezone-safe)
                $asistencias_procesadas = [];
                foreach ($asistencias_raw as $as) {
                    $fkey = $as->fecha; // 'Y-m-d' from DB
                    $dtAs = new DateTime($as->fecha);
                    $dow  = (int)$dtAs->format('w'); // 0=Dom, 1=Lun ... 6=Sab
                    $asistencias_procesadas[$fkey][$dow] = $as;
                }

                ob_start();
                include '../app/views/reportes/pdf_asistencia_trimestral.php';
                $paginas_html[] = ob_get_clean();
            }

            // ── 4. Combinar páginas o enviar individual ──
            if ($todos) {
                // Extraer el CSS de la primera página
                preg_match('/<style[^>]*>(.*?)<\/style>/is', $paginas_html[0], $styleMatch);
                $css = $styleMatch[1] ?? '';

                // Extraer solo el <body> de cada página
                $cuerpos = [];
                foreach ($paginas_html as $pag) {
                    preg_match('/<body[^>]*>(.*?)<\/body>/is', $pag, $bodyMatch);
                    $cuerpos[] = $bodyMatch[1] ?? $pag;
                }

                $separador = '<div style="page-break-after:always;"></div>';
                $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
                      . '<style>' . $css . '</style></head><body>'
                      . implode($separador, $cuerpos)
                      . '</body></html>';

                $this->pdf->renderDomPdf(
                    $html,
                    'Asistencia_Trimestral_Completa_' . $pasante->cedula,
                    false
                );
            } else {
                $this->pdf->renderDomPdf(
                    $paginas_html[0],
                    'Asistencia_Trimestral_' . $pasante->cedula,
                    false
                );
            }

        } catch (\RuntimeException $e) {
            error_log('[SGP Planilla Trimestral] ' . $e->getMessage());
            $this->renderErrorPage(
                'Error al generar la planilla',
                'No se pudo acceder a los datos de asistencia. Intente nuevamente.'
            );
        } catch (\Exception $e) {
            error_log('[SGP Planilla Trimestral] Excepción: ' . $e->getMessage());
            $this->renderErrorPage(
                'Error inesperado',
                'Ocurrió un error al procesar la planilla trimestral.'
            );
        }
    }

    /**
     * Pre-flight AJAX — Verifica si hay datos antes de generar el PDF.
     * Recibe: modulo, fecha_inicio (opcional), fecha_fin (opcional)
     * Devuelve: JSON { success: bool, count: int, message: string }
     */
    public function validarDatos()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'count' => 0, 'message' => 'Método no permitido.']);
            exit;
        }

        if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'count' => 0, 'message' => 'Token inválido. Recarga la página e intenta de nuevo.']);
            exit;
        }

        $modulo       = $_POST['modulo']       ?? '';
        $fecha_inicio = !empty($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : null;
        $fecha_fin    = !empty($_POST['fecha_fin'])    ? trim($_POST['fecha_fin'])    : $fecha_inicio;

        $sqlFiltroFecha = $fecha_inicio
            ? 'AND dpa.fecha_inicio_pasantia BETWEEN :fi AND :ff'
            : '';

        $count = 0;

        switch ($modulo) {
            case 'ejecutivo':
                $this->db->query("
                    SELECT COUNT(*) AS total
                    FROM usuarios u
                    JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                    WHERE u.rol_id = 3
                    {$sqlFiltroFecha}
                ");
                if ($fecha_inicio) {
                    $this->db->bind(':fi', $fecha_inicio);
                    $this->db->bind(':ff', $fecha_fin);
                }
                $count = (int)($this->db->single()->total ?? 0);
                break;

            case 'pasantes':
                $pInstitucion = $_POST['institucion_id'] ?? 'todas';
                $pEstado      = $_POST['estado']         ?? 'todos';

                $pWhere = ['u.rol_id = 3'];
                $pBinds = [];

                if ($fecha_inicio) {
                    $pWhere[] = 'dpa.fecha_inicio_pasantia BETWEEN :fi AND :ff';
                    $pBinds[':fi'] = $fecha_inicio;
                    $pBinds[':ff'] = $fecha_fin;
                }
                if ($pInstitucion !== 'todas') {
                    $pWhere[] = 'dpa.institucion_procedencia = :inst';
                    $pBinds[':inst'] = $pInstitucion;
                }
                if ($pEstado !== 'todos') {
                    $pWhere[] = 'dpa.estado_pasantia = :estado';
                    $pBinds[':estado'] = ucfirst($pEstado);
                }

                $this->db->query("
                    SELECT COUNT(*) AS total
                    FROM usuarios u
                    JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                    WHERE " . implode(' AND ', $pWhere)
                );
                foreach ($pBinds as $p => $v) {
                    $this->db->bind($p, $v);
                }
                $count = (int)($this->db->single()->total ?? 0);
                break;

            case 'asistencias':
                $tipo_reporte = $_POST['tipo_reporte'] ?? 'diario';

                if ($tipo_reporte === 'planilla_isp') {
                    $pasante_id = (int)($_POST['pasante_id'] ?? 0);
                    if (!$pasante_id) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Seleccione un pasante'
                        ], JSON_HEX_TAG | JSON_HEX_QUOT);
                        exit;
                    }

                    $this->db->query("
                        SELECT fecha_inicio_pasantia
                        FROM datos_pasante
                        WHERE usuario_id = :pid
                    ");
                    $this->db->bind(':pid', $pasante_id);
                    $lapso = $this->db->single();

                    if (!$lapso || !$lapso->fecha_inicio_pasantia) {
                        echo json_encode([
                            'success'                => false,
                            'message'                => 'Pasante sin fecha de inicio registrada',
                            'trimestres_disponibles' => []
                        ], JSON_HEX_TAG | JSON_HEX_QUOT);
                        exit;
                    }

                    $inicio      = new DateTime($lapso->fecha_inicio_pasantia);
                    $hoy         = new DateTime();
                    $disponibles = [];

                    for ($t = 1; $t <= 3; $t++) {
                        $offset   = ($t - 1) * 98;
                        $ini_trim = (clone $inicio)->modify("+{$offset} days");
                        $fin_trim = (clone $ini_trim)->modify('+97 days');

                        if ($ini_trim <= $hoy) {
                            $this->db->query("
                                SELECT COUNT(*) AS total
                                FROM asistencias
                                WHERE pasante_id = :pid
                                  AND fecha BETWEEN :ini AND :fin
                            ");
                            $this->db->bind(':pid', $pasante_id);
                            $this->db->bind(':ini', $ini_trim->format('Y-m-d'));
                            $this->db->bind(':fin', $fin_trim->format('Y-m-d'));
                            $cnt = $this->db->single();

                            if ($cnt && $cnt->total > 0) {
                                $disponibles[] = $t;
                            }
                        }
                    }

                    echo json_encode([
                        'success'                => true,
                        'trimestres_disponibles' => $disponibles,
                        'message'                => count($disponibles) > 0
                            ? count($disponibles) . ' trimestre(s) disponibles'
                            : 'Sin asistencias registradas'
                    ], JSON_HEX_TAG | JSON_HEX_QUOT);
                    exit;
                }

                // [SGP-FIX-REPORTES-ASISTENCIA-500] Sin esto, un reporte grupal
                // "Diario/Semanal/Mensual" sin fecha seleccionada caía en
                // WHERE 1=1 y contaba TODO el histórico (siempre > 0), así que
                // el preflight decía "success" y habilitaba un PDF sin filtro
                // — eso era lo que agotaba memoria/tiempo en DomPDF y tumbaba
                // la petición con un 500 en blanco. "Total Consolidado" es el
                // único tipo pensado para ir sin fecha.
                if ($tipo_reporte !== 'total' && !$fecha_inicio) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Selecciona una fecha (o rango) antes de generar el reporte. Si necesitas todo el histórico, usa el tipo "Total Consolidado".'
                    ], JSON_HEX_TAG | JSON_HEX_QUOT);
                    exit;
                }

                // Flujo grupal — contar registros con filtros
                $aDepto = $_POST['departamento'] ?? 'todos';
                $aWhere = ['1=1'];
                $aBinds = [];

                if ($fecha_inicio) {
                    $aWhere[] = 'a.fecha BETWEEN :fi AND :ff';
                    $aBinds[':fi'] = $fecha_inicio;
                    $aBinds[':ff'] = $fecha_fin;
                }
                if ($aDepto !== 'todos') {
                    $aWhere[] = 'u.departamento_id = :depto';
                    $aBinds[':depto'] = (int)$aDepto;
                }

                $this->db->query("
                    SELECT COUNT(*) AS total
                    FROM asistencias a
                    JOIN usuarios u ON u.id = a.pasante_id
                    WHERE " . implode(' AND ', $aWhere)
                );
                foreach ($aBinds as $p => $v) {
                    $this->db->bind($p, $v);
                }
                $count = (int)($this->db->single()->total ?? 0);

                // ── Sin registros: distinguir "no hay período activo" de "no hay datos" ──
                // Las pasantías duran ~9 meses repartidos en dos años calendario
                // (p.ej. oct-dic de un año + ene-jun del siguiente), así que hay
                // meses intermedios sin ningún período de pasantía activo. Si el
                // usuario filtra justo en ese hueco, es más claro decírselo que
                // mostrar el genérico "no hay registros".
                if ($count === 0 && $fecha_inicio) {
                    $pWhereGap = ['1=1'];
                    $pBindsGap = [':fi' => $fecha_inicio, ':ff' => $fecha_fin];
                    if ($aDepto !== 'todos') {
                        $pWhereGap[] = 'dpa.departamento_asignado_id = :depto';
                        $pBindsGap[':depto'] = (int)$aDepto;
                    }
                    $this->db->query("
                        SELECT COUNT(*) AS total
                        FROM datos_pasante dpa
                        LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
                        WHERE COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) <= :ff
                          AND COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    >= :fi
                          AND " . implode(' AND ', $pWhereGap)
                    );
                    foreach ($pBindsGap as $p => $v) {
                        $this->db->bind($p, $v);
                    }
                    $periodoActivo = (int)($this->db->single()->total ?? 0);

                    if ($periodoActivo === 0) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'No hay ningún período de pasantías activo en las fechas seleccionadas (las pasantías duran ~9 meses con huecos entre cohortes). Prueba con otro rango.'
                        ], JSON_HEX_TAG | JSON_HEX_QUOT);
                        exit;
                    }
                }
                break;

            case 'usuarios':
                $uRol    = $_POST['rol']            ?? 'todos';
                $uDepto  = $_POST['departamento']   ?? 'todos';
                $uEstado = $_POST['estado_usuario'] ?? '';

                $uWhere = ['u.rol_id <= 2'];
                $uBinds = [];

                if ($uRol !== 'todos') {
                    $uWhere[] = 'r.nombre = :rol';
                    $uBinds[':rol'] = $uRol;
                }
                if ($uDepto !== 'todos') {
                    $uWhere[] = 'u.departamento_id = :depto';
                    $uBinds[':depto'] = (int)$uDepto;
                }
                if ($uEstado !== '') {
                    $uWhere[] = 'u.estado = :estado';
                    $uBinds[':estado'] = $uEstado;
                }

                $this->db->query("
                    SELECT COUNT(*) AS total
                    FROM usuarios u
                    LEFT JOIN roles r ON r.id = u.rol_id
                    WHERE " . implode(' AND ', $uWhere)
                );
                foreach ($uBinds as $p => $v) {
                    $this->db->bind($p, $v);
                }
                $count = (int)($this->db->single()->total ?? 0);
                break;

            case 'asignaciones':
                $this->db->query("SELECT COUNT(*) AS total FROM asignaciones");
                $count = (int)($this->db->single()->total ?? 0);
                break;

            case 'evaluaciones':
                // Verificar que el pasante tenga al menos una evaluación registrada
                $ePid = (int)($_POST['pasante_id'] ?? 0);
                if (!$ePid) {
                    echo json_encode(['success' => false, 'message' => 'Seleccione un pasante.']);
                    exit;
                }
                $this->db->query("SELECT COUNT(*) AS total FROM evaluaciones WHERE pasante_id = :pid");
                $this->db->bind(':pid', $ePid);
                $eCount = (int)($this->db->single()->total ?? 0);
                if ($eCount === 0) {
                    echo json_encode(['success' => false, 'message' => 'Este pasante aún no tiene evaluaciones registradas.']);
                    exit;
                }
                echo json_encode(['success' => true, 'count' => $eCount, 'message' => 'Evaluación disponible.']);
                exit;

            case 'constancias':
                $modoEmision = $_POST['modo_emision'] ?? 'registrado';
                if ($modoEmision === 'manual' || !empty($_POST['es_manual'])) {
                    $mNombreCompleto = trim($_POST['manual_nombre_completo'] ?? '');
                    if ($mNombreCompleto === '') {
                        $mNombreCompleto = trim(($_POST['manual_nombres'] ?? '') . ' ' . ($_POST['manual_apellidos'] ?? ''));
                    }
                    $mCedula = trim($_POST['manual_cedula'] ?? '');
                    if (empty($mNombreCompleto) || empty($mCedula)) {
                        echo json_encode(['success' => false, 'message' => 'Por favor ingresa el nombre y la cédula del pasante para generar el documento.']);
                        exit;
                    }
                    echo json_encode(['success' => true, 'count' => 1, 'message' => 'Documento manual listo para emitir.']);
                    exit;
                }

                // Verificar que el pasante exista y tenga datos completos (modo registrado)
                $cPid  = (int)($_POST['pasante_id'] ?? 0);
                $cTipo = $_POST['tipo_constancia'] ?? 'servicio';
                if (!$cPid) {
                    echo json_encode(['success' => false, 'message' => 'Seleccione un pasante.']);
                    exit;
                }
                $this->db->query("
                    SELECT dpa.estado_pasantia, dpa.horas_meta,
                           COALESCE((SELECT COUNT(*) * 8
                                     FROM asistencias a WHERE a.pasante_id = dpa.usuario_id
                                     AND a.estado != 'Anulado' AND a.fecha <= CURDATE()), 0) AS horas_acumuladas
                    FROM datos_pasante dpa WHERE dpa.usuario_id = :pid LIMIT 1
                ");
                $this->db->bind(':pid', $cPid);
                $cRow = $this->db->single();
                if (!$cRow) {
                    echo json_encode(['success' => false, 'message' => 'Pasante no encontrado.']);
                    exit;
                }
                if ($cTipo === 'culminacion' && $cRow->estado_pasantia !== 'Finalizado') {
                    echo json_encode(['success' => false, 'message' => 'La carta de culminación solo se puede emitir para pasantes con estado “Finalizado”.']);
                    exit;
                }
                echo json_encode(['success' => true, 'count' => 1, 'message' => 'OK']);
                exit;

            case 'bitacora':
                $bWhere = ['1=1'];
                $bBinds = [];
                if ($fecha_inicio) {
                    $bWhere[] = 'DATE(b.created_at) BETWEEN :fi AND :ff';
                    $bBinds[':fi'] = $fecha_inicio;
                    $bBinds[':ff'] = $fecha_fin;
                }
                $this->db->query("SELECT COUNT(*) AS total FROM bitacora b WHERE " . implode(' AND ', $bWhere));
                foreach ($bBinds as $p => $v) { $this->db->bind($p, $v); }
                $count = (int)($this->db->single()->total ?? 0);
                break;

            case 'diaria':
                $fechaRaw = trim($_POST['fecha_diaria'] ?? '');
                $fecha    = ($fechaRaw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRaw)) ? $fechaRaw : date('Y-m-d');
                if ($fecha > date('Y-m-d')) $fecha = date('Y-m-d');

                $diaSemana = (int)date('N', strtotime($fecha));
                if ($diaSemana >= 6) {
                    $diaLabel = $diaSemana === 6 ? 'Sábado' : 'Domingo';
                    echo json_encode(['success' => false, 'message' => "La ficha diaria solo refleja días laborables. La fecha seleccionada es $diaLabel, por lo que no hay jornada activa."]);
                    exit;
                }

                $this->db->query("SELECT nombre FROM dias_feriados WHERE fecha = :hoy AND es_laborable = 0 LIMIT 1");
                $this->db->bind(':hoy', $fecha);
                $feriado = $this->db->single();
                if ($feriado) {
                    echo json_encode(['success' => false, 'message' => "La fecha seleccionada es un día feriado no laborable (" . htmlspecialchars($feriado->nombre) . "). No hay jornada activa — el día no cuenta en el cómputo de horas de pasantía."]);
                    exit;
                }

                $where = ['a.fecha = :fecha', "a.estado != 'Anulado'"];
                $binds = [':fecha' => $fecha];
                $deptoId = $_POST['departamento'] ?? 'todos';
                
                if ($deptoId !== 'todos') {
                    $where[] = 'dpa.departamento_asignado_id = :depto';
                    $binds[':depto'] = (int)$deptoId;
                }

                $this->db->query("
                    SELECT COUNT(*) AS total
                    FROM asistencias a
                    JOIN usuarios u ON u.id = a.pasante_id
                    LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
                    WHERE " . implode(' AND ', $where) . "
                ");
                foreach ($binds as $p => $v) { $this->db->bind($p, $v); }
                $count = (int)($this->db->single()->total ?? 0);
                
                if ($count === 0) {
                    echo json_encode(['success' => false, 'message' => "No se encontraron registros de asistencia para la fecha " . date('d/m/Y', strtotime($fecha)) . "."]);
                    exit;
                }
                
                echo json_encode(['success' => true, 'count' => $count, 'message' => 'OK']);
                exit;

            default:
                // Siempre pasa
                echo json_encode(['success' => true, 'count' => 1, 'message' => 'OK']);
                exit;
        }

        if ($count > 0) {
            echo json_encode(['success' => true,  'count' => $count, 'message' => "Se encontraron {$count} registros."]);
        } else {
            echo json_encode(['success' => false, 'count' => 0,      'message' => 'No hay registros para el período seleccionado.']);
        }
        exit;
    }

    /**
     * Hito 6: Resumen Ejecutivo Administrativo
     *
     * Variables que pasa a la vista:
     *   $stats        — Array: total, activo, finalizado, retirado, pendiente
     *   $departamentos — Array de objetos: top 3 depto con más pasantes
     *   $instituciones — Array de objetos: pasantes agrupados por institución de procedencia
     *   $fecha_inicio / $fecha_fin — Strings para mostrar el período en el PDF
     */
    private function generarResumenEjecutivo($filtros)
    {
        $fecha_inicio = !empty($filtros['fecha_inicio']) ? trim($filtros['fecha_inicio']) : null;
        $fecha_fin    = !empty($filtros['fecha_fin'])    ? trim($filtros['fecha_fin'])    : $fecha_inicio;
        $tipo_periodo = $filtros['tipo_periodo'] ?? 'historico';

        // ── Subtítulo semántico para el PDF ──────────────────────────────────
        $meses_es = ['', 'Enero','Febrero','Marzo','Abril','Mayo','Junio',
                     'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $subtitulo_pdf = match ($tipo_periodo) {
            'hoy'   => 'Reporte del día: ' . date('d/m/Y'),
            'mes'   => 'Mes Actual: ' . $meses_es[(int)date('n')] . ' ' . date('Y'),
            'rango' => 'Rango Personalizado: '
                       . ($fecha_inicio ? date('d/m/Y', strtotime($fecha_inicio)) : '—')
                       . ' al '
                       . ($fecha_fin    ? date('d/m/Y', strtotime($fecha_fin))    : '—'),
            default => 'Histórico Total — Todos los períodos',
        };

        // Cláusula de filtro por fecha reutilizable (se inyecta en el SQL como literal
        // porque es una cadena fija — los valores reales van por :fi y :ff con bind)
        $sqlFiltroFecha = $fecha_inicio
            ? 'AND dpa.fecha_inicio_pasantia BETWEEN :fi AND :ff'
            : '';

        // ── 1. Conteo de pasantes por estado ─────────────────────────────────
        $this->db->query("
            SELECT
                COUNT(*)                                                        AS total,
                SUM(CASE WHEN dpa.estado_pasantia = 'Activo'     THEN 1 ELSE 0 END) AS activo,
                SUM(CASE WHEN dpa.estado_pasantia = 'Finalizado' THEN 1 ELSE 0 END) AS finalizado,
                SUM(CASE WHEN dpa.estado_pasantia = 'Retirado'   THEN 1 ELSE 0 END) AS retirado,
                SUM(CASE WHEN dpa.estado_pasantia = 'Pendiente'  THEN 1 ELSE 0 END) AS pendiente
            FROM usuarios u
            JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            WHERE u.rol_id = 3
            {$sqlFiltroFecha}
        ");
        if ($fecha_inicio) {
            $this->db->bind(':fi', $fecha_inicio);
            $this->db->bind(':ff', $fecha_fin);
        }
        $row   = $this->db->single();
        $stats = [
            'total'      => (int)($row->total      ?? 0),
            'activo'     => (int)($row->activo      ?? 0),
            'finalizado' => (int)($row->finalizado  ?? 0),
            'retirado'   => (int)($row->retirado    ?? 0),
            'pendiente'  => (int)($row->pendiente   ?? 0),
        ];

        // ── 2. Top 3 departamentos con más pasantes ───────────────────────────
        $this->db->query("
            SELECT d.nombre, COUNT(dpa.usuario_id) AS total
            FROM departamentos d
            JOIN datos_pasante dpa ON dpa.departamento_asignado_id = d.id
            JOIN usuarios      u   ON u.id = dpa.usuario_id
            WHERE u.rol_id = 3
              AND d.activo = 1
            {$sqlFiltroFecha}
            GROUP BY d.id, d.nombre
            ORDER BY total DESC
            LIMIT 3
        ");
        if ($fecha_inicio) {
            $this->db->bind(':fi', $fecha_inicio);
            $this->db->bind(':ff', $fecha_fin);
        }
        $departamentos = $this->db->resultSet();

        // ── 3. Pasantes por institución de procedencia ────────────────────────
        // El campo VARCHAR(150) puede contener texto libre o un ID numérico heredado.
        // CASE: si el valor es numérico y existe en la tabla maestra → usa el nombre oficial;
        //       si es texto libre → úsalo tal cual; vacío/nulo → 'Sin especificar'.
        // GROUP BY sobre el nombre resuelto para evitar filas duplicadas.
        $this->db->query("
            SELECT
                COALESCE(
                    NULLIF(TRIM(
                        CASE
                            WHEN inst.id IS NOT NULL THEN inst.nombre
                            ELSE dpa.institucion_procedencia
                        END
                    ), ''),
                    'Sin especificar'
                ) AS nombre,
                COUNT(*) AS total
            FROM datos_pasante dpa
            JOIN  usuarios     u    ON u.id   = dpa.usuario_id
            LEFT JOIN instituciones inst ON inst.id = dpa.institucion_id
            WHERE u.rol_id = 3
            {$sqlFiltroFecha}
            GROUP BY nombre
            ORDER BY total DESC
            LIMIT 10
        ");
        if ($fecha_inicio) {
            $this->db->bind(':fi', $fecha_inicio);
            $this->db->bind(':ff', $fecha_fin);
        }
        $instituciones = $this->db->resultSet();

        // ── Renderizar vista como buffer y pasar a DomPDF ─────────────────────
        ob_start();
        include '../app/views/reportes/pdf_ejecutivo.php';
        $html = ob_get_clean();

        $this->pdf->renderDomPdf($html, 'Resumen_Ejecutivo_' . date('Ymd'), false);
    }

    /**
     * Directorio de Usuarios del Sistema (PDF institucional ultra-ligero)
     *
     * Variables que pasa a la vista:
     *   $usuarios      — Array de objetos con datos del personal
     *   $subtitulo_pdf — String descriptivo del filtro aplicado
     */
    private function generarDirectorioUsuarios($filtros)
    {
        $rol           = $filtros['rol']            ?? 'todos';
        $departamento  = $filtros['departamento']   ?? 'todos';
        $estado        = $filtros['estado_usuario'] ?? '';

        // ── Subtítulo semántico ───────────────────────────────────────────────
        $partes = [];
        if ($rol          !== 'todos') $partes[] = 'Rol: ' . htmlspecialchars($rol);
        if ($departamento !== 'todos') $partes[] = 'Depto. ID: ' . (int)$departamento;
        if ($estado       !== '')      $partes[] = 'Estado: ' . htmlspecialchars(ucfirst($estado));
        $subtitulo_pdf = $partes ? implode(' | ', $partes) : 'Directorio Completo — Todo el Personal';

        // ── Cláusulas WHERE dinámicas (sin interpolación de valores) ─────────
        $where  = ['u.rol_id <= 2'];   // solo admin y tutores
        $binds  = [];

        if ($rol !== 'todos') {
            $where[] = 'r.nombre = :rol';
            $binds[':rol'] = $rol;
        }
        if ($departamento !== 'todos') {
            $where[] = 'u.departamento_id = :depto';
            $binds[':depto'] = (int)$departamento;
        }
        if ($estado !== '') {
            $where[] = 'u.estado = :estado';
            $binds[':estado'] = $estado;
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);

        $this->db->query("
            SELECT
                u.id,
                u.cedula,
                COALESCE(dp.nombres,   '') AS nombres,
                COALESCE(dp.apellidos, '') AS apellidos,
                u.correo,
                COALESCE(r.nombre, 'SuperAdmin') AS rol,
                d.nombre          AS departamento_nombre,
                u.estado
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN roles            r  ON r.id          = u.rol_id
            LEFT JOIN departamentos    d  ON d.id          = u.departamento_id
            {$sqlWhere}
            ORDER BY dp.apellidos ASC, dp.nombres ASC
        ");
        foreach ($binds as $param => $value) {
            $this->db->bind($param, $value);
        }
        $usuarios = $this->db->resultSet();

        // ── Render ────────────────────────────────────────────────────────────
        $tipo = $filtros['tipo'] ?? 'pdf';

        if ($tipo === 'excel') {
            $filename = 'Directorio_Usuarios_' . date('Ymd') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache');

            $out = fopen('php://output', 'w');
            // BOM para compatibilidad UTF-8 en Excel
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, ['N°', 'Cédula', 'Nombre y Apellido', 'Correo / Usuario', 'Rol', 'Departamento', 'Estado'], ';');

            foreach ($usuarios as $i => $u) {
                $nombre    = trim(($u->apellidos ?? '') . ', ' . ($u->nombres ?? ''));
                $estadoTxt = ($u->estado === 'activo' || $u->estado == 1) ? 'Activo' : 'Inactivo';
                fputcsv($out, [
                    $i + 1,
                    $u->cedula            ?? '—',
                    $nombre,
                    $u->correo            ?? '—',
                    $u->rol               ?? '—',
                    $u->departamento_nombre ?? 'Sin asignar',
                    $estadoTxt,
                ], ';');
            }
            fclose($out);
            exit;
        }

        ob_start();
        include '../app/views/reportes/pdf_directorio_usuarios.php';
        $html = ob_get_clean();

        $this->pdf->renderDomPdf($html, 'Directorio_Usuarios_' . date('Ymd'), false);
    }

    /**
     * Registro General de Pasantes (PDF/CSV institucional)
     *
     * Variables que pasa a la vista:
     *   $pasantes      — Array de objetos con datos del pasante
     *   $subtitulo_pdf — String descriptivo del filtro aplicado
     */
    private function generarRegistroPasantes($filtros)
    {
        $institucion_id = $filtros['institucion_id'] ?? 'todas';
        $estado         = $filtros['estado']         ?? 'todos';
        $fecha_inicio   = !empty($filtros['fecha_inicio']) ? trim($filtros['fecha_inicio']) : null;
        $fecha_fin      = !empty($filtros['fecha_fin'])    ? trim($filtros['fecha_fin'])    : $fecha_inicio;

        // ── Subtítulo semántico ───────────────────────────────────────────────
        $partes = [];
        if ($institucion_id !== 'todas') $partes[] = 'Institución ID: ' . (int)$institucion_id;
        if ($estado         !== 'todos') $partes[] = 'Estado: ' . ucfirst($estado);
        if ($fecha_inicio)               $partes[] = date('d/m/Y', strtotime($fecha_inicio))
                                                    . ' al ' . date('d/m/Y', strtotime($fecha_fin));
        $subtitulo_pdf = $partes ? implode(' | ', $partes) : 'Registro Completo — Todos los Períodos';

        // ── WHERE dinámico ────────────────────────────────────────────────────
        $where = ['u.rol_id = 3'];
        $binds = [];

        if ($fecha_inicio) {
            $where[] = 'dpa.fecha_inicio_pasantia BETWEEN :fi AND :ff';
            $binds[':fi'] = $fecha_inicio;
            $binds[':ff'] = $fecha_fin;
        }
        if ($institucion_id !== 'todas') {
            $where[] = 'dpa.institucion_procedencia = :inst';
            $binds[':inst'] = (string)$institucion_id;
        }
        if ($estado !== 'todos') {
            $where[] = 'dpa.estado_pasantia = :estado';
            $binds[':estado'] = ucfirst($estado);
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);

        $this->db->query("
            SELECT
                u.cedula,
                dp.nombres,
                dp.apellidos,
                COALESCE(
                    NULLIF(TRIM(
                        CASE
                            WHEN inst.id IS NOT NULL THEN inst.nombre
                            ELSE dpa.institucion_procedencia
                        END
                    ), ''),
                    'Sin especificar'
                )                          AS institucion,
                d.nombre                   AS departamento,
                dpa.fecha_inicio_pasantia  AS fecha_inicio,
                dpa.estado_pasantia        AS estado
            FROM usuarios u
            JOIN datos_personales dp  ON dp.usuario_id  = u.id
            JOIN datos_pasante    dpa ON dpa.usuario_id = u.id
            LEFT JOIN instituciones inst ON inst.id = dpa.institucion_id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            {$sqlWhere}
            ORDER BY dp.apellidos ASC, dp.nombres ASC
        ");
        foreach ($binds as $param => $value) {
            $this->db->bind($param, $value);
        }
        $pasantes = $this->db->resultSet();

        // ── Render ────────────────────────────────────────────────────────────
        $tipo = $filtros['tipo'] ?? 'pdf';

        if ($tipo === 'excel') {
            $filename = 'Registro_Pasantes_' . date('Ymd') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache');

            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['N°', 'Cédula', 'Nombres y Apellidos', 'Institución', 'Departamento', 'F. Inicio', 'Estado'], ';');

            foreach ($pasantes as $i => $p) {
                $nombre = trim(($p->apellidos ?? '') . ', ' . ($p->nombres ?? ''));
                fputcsv($out, [
                    $i + 1,
                    $p->cedula       ?? '—',
                    $nombre,
                    $p->institucion  ?? 'Sin especificar',
                    $p->departamento ?? 'Sin asignar',
                    $p->fecha_inicio ? date('d/m/Y', strtotime($p->fecha_inicio)) : '—',
                    $p->estado       ?? '—',
                ], ';');
            }
            fclose($out);
            exit;
        }

        ob_start();
        include '../app/views/reportes/pdf_registro_pasantes.php';
        $html = ob_get_clean();

        $this->pdf->renderDomPdf($html, 'Registro_Pasantes_' . date('Ymd'), false);
    }

    /**
     * Control General de Asistencias (PDF/CSV institucional)
     *
     * Variables que pasa a la vista:
     *   $asistencias   — Array de objetos con registros de asistencia
     *   $subtitulo_pdf — String descriptivo del filtro aplicado
     */
    private function generarReporteAsistencias($filtros)
    {
        // [SGP-FIX-REPORTES-ASISTENCIA-500] Este método no tenía try/catch:
        // cualquier fallo (o un dataset grande sin filtro de fecha, pesado
        // para DomPDF) terminaba en un "HTTP ERROR 500" en blanco en vez de
        // la página de error propia del sistema. Se envuelve todo el cuerpo
        // y además se sube el límite de memoria/tiempo solo para esta
        // petición, para dar margen a DomPDF con listados largos.
        try {
            $this->generarReporteAsistenciasInterno($filtros);
        } catch (\Throwable $e) {
            error_log('[SGP Control Asistencias] ' . $e->getMessage());
            $this->renderErrorPage(
                'Error al generar el reporte',
                'No se pudo generar el listado de asistencias. Si el rango de fechas es muy amplio, intente acotarlo (por ejemplo, un mes) y vuelva a intentarlo.'
            );
        }
    }

    private function generarReporteAsistenciasInterno($filtros)
    {
        $departamento = $filtros['departamento']  ?? 'todos';
        $tipo_reporte = $filtros['tipo_reporte']  ?? 'diario';
        $fecha_inicio = !empty($filtros['fecha_inicio']) ? trim($filtros['fecha_inicio']) : null;
        $fecha_fin    = !empty($filtros['fecha_fin'])    ? trim($filtros['fecha_fin'])    : $fecha_inicio;

        // [SGP-FIX-REPORTES-ASISTENCIA-500] Defensa en profundidad: el
        // preflight (validarDatos) ya bloquea esto, pero si por algún motivo
        // se llega aquí sin fecha (y sin pedir explícitamente el "Total
        // Consolidado"), no se genera un PDF sin filtro sobre todo el
        // histórico — se corta con un mensaje claro en vez de arriesgar
        // memoria/tiempo de DomPDF.
        if ($tipo_reporte !== 'total' && !$fecha_inicio) {
            $this->renderErrorPage(
                'Falta la fecha del reporte',
                'Debes indicar una fecha (o rango) para este tipo de reporte. Si necesitas todo el histórico, selecciona "Total Consolidado".'
            );
        }

        // ── Subtítulo semántico ───────────────────────────────────────────────
        $tipoLabel = [
            'diario'       => 'Diario',
            'semanal'      => 'Semanal',
            'mensual'      => 'Mensual',
            'total'        => 'Total Consolidado',
            'planilla_isp' => 'Planilla ISP Trimestral',
        ][$tipo_reporte] ?? ucfirst($tipo_reporte);

        $partes = ['Tipo: ' . $tipoLabel];
        if ($departamento !== 'todos') $partes[] = 'Depto. ID: ' . (int)$departamento;
        if ($fecha_inicio)             $partes[] = date('d/m/Y', strtotime($fecha_inicio))
                                                  . ' al ' . date('d/m/Y', strtotime($fecha_fin));
        $subtitulo_pdf = implode(' | ', $partes);

        // ── WHERE dinámico ────────────────────────────────────────────────────
        $where = ['1=1'];
        $binds = [];

        if ($fecha_inicio) {
            $where[] = 'a.fecha BETWEEN :fi AND :ff';
            $binds[':fi'] = $fecha_inicio;
            $binds[':ff'] = $fecha_fin;
        }
        if ($departamento !== 'todos') {
            $where[] = 'u.departamento_id = :depto';
            $binds[':depto'] = (int)$departamento;
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);

        $this->db->query("
            SELECT
                u.cedula,
                dp.nombres,
                dp.apellidos,
                a.fecha,
                a.hora_entrada,
                a.hora_salida,
                COALESCE(NULLIF(a.motivo_justificacion, ''), a.estado) AS observacion
            FROM asistencias a
            JOIN usuarios         u  ON u.id  = a.pasante_id
            JOIN datos_personales dp ON dp.usuario_id = u.id
            {$sqlWhere}
            ORDER BY a.fecha DESC, dp.apellidos ASC
        ");
        foreach ($binds as $param => $value) {
            $this->db->bind($param, $value);
        }
        $asistencias = $this->db->resultSet();

        // ── Render ────────────────────────────────────────────────────────────
        $tipo = $filtros['tipo'] ?? 'pdf';

        if ($tipo === 'excel') {
            $filename = 'Control_Asistencias_' . date('Ymd') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache');

            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['N°', 'Cédula', 'Pasante', 'Fecha', 'Hora Entrada', 'Hora Salida', 'Observación'], ';');

            foreach ($asistencias as $i => $a) {
                $nombre = ucwords(strtolower(trim(($a->apellidos ?? '') . ', ' . ($a->nombres ?? ''))));
                fputcsv($out, [
                    $i + 1,
                    $a->cedula      ?? '—',
                    $nombre,
                    $a->fecha       ? date('d/m/Y', strtotime($a->fecha)) : '—',
                    $a->hora_entrada ?? '—',
                    $a->hora_salida  ?? '—',
                    $a->observacion  ?? '—',
                ], ';');
            }
            fclose($out);
            exit;
        }

        // [SGP-FIX-REPORTES-ASISTENCIA-500-v2] Subir memory_limit/time_limit
        // no fue suficiente: DomPDF construye todo el árbol HTML/CSS en
        // memoria y con "Total Consolidado" (histórico completo, sin tope)
        // eso sigue agotando los recursos y tumbando la petición con 500.
        // Para listados grandes se usa TCPDF en su lugar — dibuja fila por
        // fila con Cell(), sin el overhead de DomPDF, y es justo para lo que
        // ya estaba pensado en PdfGenerator ("listados de asistencia
        // completos"). Los listados normales (filtrados por fecha) siguen
        // usando el diseño DomPDF, más prolijo visualmente.
        $UMBRAL_TCPDF = 400;

        if (count($asistencias) > $UMBRAL_TCPDF) {
            $columnas = ['N°', 'Cédula', 'Pasante', 'Fecha', 'H. Entrada', 'H. Salida', 'Observación'];
            $filas = [];
            foreach ($asistencias as $i => $a) {
                $nombre = ucwords(strtolower(trim(($a->apellidos ?? '') . ', ' . ($a->nombres ?? ''))));
                $filas[] = [
                    $i + 1,
                    $a->cedula       ?? '—',
                    $nombre,
                    $a->fecha        ? date('d/m/Y', strtotime($a->fecha)) : '—',
                    $a->hora_entrada ?? '—',
                    $a->hora_salida  ?? '—',
                    $a->observacion  ?? '—',
                ];
            }

            $this->pdf->renderTcpdf(
                'Control General de Asistencias',
                $columnas,
                $filas,
                'Control_Asistencias_' . date('Ymd'),
                $subtitulo_pdf . ' | Total de registros: ' . count($asistencias),
                false,
                // Anchos en mm (suman 180) — Pasante y Observación son las
                // columnas con texto más largo, por eso llevan más espacio.
                [10, 22, 50, 20, 20, 20, 38]
            );
            return;
        }

        ob_start();
        include '../app/views/reportes/pdf_control_asistencias.php';
        $html = ob_get_clean();

        // Margen extra por si acaso, aunque bajo el umbral no debería hacer falta.
        @ini_set('memory_limit', '256M');
        @set_time_limit(90);

        $this->pdf->renderDomPdf($html, 'Control_Asistencias_' . date('Ymd'), false);
    }

    /**
     * Nómina anual de pasantes con resumen de asistencias.
     * GET /reportes/pdfNomina?download=0|1&anio=YYYY
     */
    public function pdfNomina(): void
    {
        $download = (int)($_GET['download'] ?? 0) === 1;
        $anio     = (int)($_GET['anio'] ?? date('Y'));

        // [FIX-AC-3] Aislamiento por departamento — mismo criterio que exportar().
        // Antes esta ruta devolvía la nómina de TODOS los departamentos a cualquier Tutor.
        $rolId       = (int)Session::get('role_id');
        $deptWhere   = '';
        if ($rolId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $_deptId   = (int)($this->db->single()->departamento_id ?? 0);
            $deptWhere = 'AND dpa.departamento_asignado_id = :dept_id';
        }

        // Nómina: pasantes activos con totales del año indicado (acotado por departamento si es Tutor)
        $this->db->query("
            SELECT
                u.id,
                u.cedula,
                dp.nombres,
                dp.apellidos,
                dep.nombre                                              AS departamento,
                inst.nombre                                             AS institucion,
                dpa.estado_pasantia,
                dpa.fecha_inicio_pasantia                               AS fecha_inicio,
                dpa.fecha_fin_estimada                                  AS fecha_fin,
                COUNT(a.id)                                             AS total_dias,
                SUM(CASE WHEN a.estado = 'Presente'    THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN a.estado = 'Ausente'     THEN 1 ELSE 0 END) AS ausentes,
                SUM(CASE WHEN a.estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados
            FROM usuarios u
            JOIN datos_personales dp   ON dp.usuario_id   = u.id
            JOIN datos_pasante    dpa  ON dpa.usuario_id  = u.id
            LEFT JOIN departamentos dep  ON dep.id  = dpa.departamento_asignado_id
            LEFT JOIN instituciones inst ON inst.id = dpa.institucion_id
            LEFT JOIN asistencias   a    ON a.pasante_id  = u.id
                                        AND YEAR(a.fecha) = :anio
            WHERE u.rol_id = 3
              AND COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
              {$deptWhere}
            GROUP BY u.id, u.cedula, dp.nombres, dp.apellidos,
                     dep.nombre, inst.nombre, dpa.estado_pasantia,
                     dpa.fecha_inicio_pasantia, dpa.fecha_fin_estimada
            ORDER BY dep.nombre ASC, dp.apellidos ASC, dp.nombres ASC
        ");
        $this->db->bind(':anio', $anio);
        if ($rolId === 2) {
            $this->db->bind(':dept_id', $_deptId);
        }
        $pasantes = $this->db->resultSet();

        $subtitulo_pdf = 'Nómina General de Pasantes — Año ' . $anio;

        ob_start();
        include '../app/views/reportes/pdf_nomina_pasantes.php';
        $html = ob_get_clean();

        $this->pdf->renderDomPdf($html, 'Nomina_Pasantes_' . $anio, $download);
    }
    // ══════════════════════════════════════════════════════════════
    // NUEVOS MÓDULOS: Auditóría, Asignaciones, Ficha Diaria, Constancias
    // ══════════════════════════════════════════════════════════════

    /**
     * Auditóría — Reporte de la bitácora del sistema con filtros de fecha y módulo
     */
    private function generarBitacora(array $filtros): void
    {
        $moduloLog  = $filtros['modulo_log']   ?? 'todos';
        $fecha_ini  = !empty($filtros['fecha_inicio']) ? trim($filtros['fecha_inicio']) : date('Y-m-01');
        $fecha_fin  = !empty($filtros['fecha_fin'])    ? trim($filtros['fecha_fin'])    : date('Y-m-d');
        $tipo       = $filtros['tipo'] ?? 'pdf';

        // ── Esquema real de bitacora: created_at (no 'fecha'), detalles (no 'descripcion') ──
        $where = ['DATE(b.created_at) BETWEEN :fi AND :ff'];
        $binds = [':fi' => $fecha_ini, ':ff' => $fecha_fin];

        if ($moduloLog !== 'todos') {
            $where[] = 'b.accion LIKE :mod';
            $binds[':mod'] = '%' . $moduloLog . '%';
        }

        $this->db->query("
            SELECT
                b.created_at                                                AS fecha,
                b.accion,
                b.tabla_afectada                                            AS modulo,
                b.detalles                                                  AS descripcion,
                COALESCE(CONCAT(dp.nombres,' ',dp.apellidos), u.correo)    AS usuario,
                u.cedula
            FROM bitacora b
            LEFT JOIN usuarios         u  ON u.id = b.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = b.usuario_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY b.created_at DESC
            LIMIT 500
        ");
        foreach ($binds as $p => $v) { $this->db->bind($p, $v); }
        $registros = $this->db->resultSet();

        // ── Validación: sin registros → mostrar error amigable ────────────────
        if (empty($registros)) {
            $this->renderErrorPage(
                'Sin registros de auditoría',
                'No se encontraron registros en el período del '
                    . date('d/m/Y', strtotime($fecha_ini))
                    . ' al ' . date('d/m/Y', strtotime($fecha_fin))
                    . '. Amplíe el rango de fechas o seleccione otro módulo.'
            );
            return;
        }

        $subtitulo_pdf = 'Auditoría del sistema | '
            . date('d/m/Y', strtotime($fecha_ini))
            . ' al ' . date('d/m/Y', strtotime($fecha_fin));

        if ($tipo === 'excel') {
            $filename = 'Auditoria_' . date('Ymd') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache');
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['N°', 'Fecha/Hora', 'Usuario', 'Cédula', 'Módulo', 'Acción', 'Descripción'], ';');
            foreach ($registros as $i => $r) {
                fputcsv($out, [
                    $i + 1,
                    $r->fecha       ?? '—',
                    $r->usuario     ?? '—',
                    $r->cedula      ?? '—',
                    $r->modulo      ?? '—',
                    $r->accion      ?? '—',
                    $r->descripcion ?? '—',
                ], ';');
            }
            fclose($out);
            exit;
        }

        ob_start();
        include '../app/views/reportes/pdf_auditoria.php';
        $html = ob_get_clean();
        $this->pdf->renderDomPdf($html, 'Auditoria_' . date('Ymd'), false);
    }

    /**
     * Asignaciones — Lista de pasantes por departamento
     */
    private function generarAsignaciones(array $filtros): void
    {
        $deptoId   = $filtros['departamento_id'] ?? 'todos';
        $tipo      = $filtros['tipo'] ?? 'pdf';

        $where = ['a.estado = \'activo\''];
        $binds = [];

        if ($deptoId !== 'todos') {
            $where[] = 'dpa.departamento_asignado_id = :depto';
            $binds[':depto'] = (int)$deptoId;
        }

        $this->db->query("
            SELECT
                dp.apellidos, dp.nombres, u.cedula,
                d.nombre                                      AS departamento,
                CONCAT(tp.nombres, ' ', tp.apellidos)         AS tutor,
                COALESCE(inst.nombre, dpa.institucion_procedencia, 'N/D') AS institucion,
                a.fecha_inicio, a.estado
            FROM asignaciones a
            JOIN usuarios          u    ON u.id   = a.pasante_id
            JOIN datos_personales  dp   ON dp.usuario_id = u.id
            JOIN datos_pasante     dpa  ON dpa.usuario_id = u.id
            LEFT JOIN departamentos d   ON d.id   = dpa.departamento_asignado_id
            LEFT JOIN usuarios      tu  ON tu.id  = dpa.tutor_id
            LEFT JOIN datos_personales tp ON tp.usuario_id = dpa.tutor_id
            LEFT JOIN instituciones    inst ON inst.id = dpa.institucion_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.nombre ASC, dp.apellidos ASC
        ");
        foreach ($binds as $p => $v) { $this->db->bind($p, $v); }
        $asignaciones = $this->db->resultSet();

        $subtitulo_pdf = $deptoId !== 'todos'
            ? 'Departamento ID: ' . (int)$deptoId
            : 'Todos los Departamentos';

        if ($tipo === 'excel') {
            $filename = 'Asignaciones_' . date('Ymd') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache');
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['N\u00b0', 'C\u00e9dula', 'Pasante', 'Departamento', 'Tutor', 'Instituci\u00f3n', 'F. Inicio', 'Estado'], ';');
            foreach ($asignaciones as $i => $a) {
                $nombre = trim(($a->apellidos ?? '') . ', ' . ($a->nombres ?? ''));
                fputcsv($out, [
                    $i + 1,
                    $a->cedula       ?? '\u2014',
                    $nombre,
                    $a->departamento ?? 'Sin asignar',
                    $a->tutor        ?? '\u2014',
                    $a->institucion  ?? 'Sin especificar',
                    $a->fecha_inicio ? date('d/m/Y', strtotime($a->fecha_inicio)) : '\u2014',
                    $a->estado       ?? '\u2014',
                ], ';');
            }
            fclose($out);
            exit;
        }

        ob_start();
        include '../app/views/reportes/pdf_asignaciones.php';
        $html = ob_get_clean();
        $this->pdf->renderDomPdf($html, 'Asignaciones_' . date('Ymd'), false);
    }

    /**
     * Ficha Diaria — Asistencias del día por departamento.
     * Valida fin de semana, feriados y horario laboral antes de generar el PDF.
     */
    private function generarFichaDiaria(array $filtros): void
    {
        $deptoId    = $filtros['departamento'] ?? 'todos';

        // Usar la fecha elegida por el usuario; fallback = hoy
        $fechaRaw   = trim($filtros['fecha_diaria'] ?? '');
        $fecha      = ($fechaRaw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRaw))
                        ? $fechaRaw
                        : date('Y-m-d');

        // Asegurar que no sea fecha futura
        if ($fecha > date('Y-m-d')) {
            $fecha = date('Y-m-d');
        }

        $diaSemana  = (int)date('N', strtotime($fecha));  // 1=Lun … 7=Dom
        $horaActual = (int)date('H');

        // ── 1. Validar fin de semana ──────────────────────────────────────────
        if ($diaSemana >= 6) {
            $diaLabel = $diaSemana === 6 ? 'Sábado' : 'Domingo';
            $this->renderErrorPage(
                'Día no hábil — ' . $diaLabel . ' ' . date('d/m/Y'),
                'La Ficha Diaria de Actividad solo refleja días laborables (lunes a viernes). '
                . 'Hoy es ' . $diaLabel . ', por lo que no hay jornada activa y no se genera el reporte.'
            );
            return;
        }

        // ── 2. Validar feriado no-laborable (los laborables se procesan normalmente) ──
        $this->db->query("SELECT nombre FROM dias_feriados WHERE fecha = :hoy AND es_laborable = 0 LIMIT 1");
        $this->db->bind(':hoy', $fecha);
        $feriado = $this->db->single();

        if ($feriado) {
            $this->renderErrorPage(
                'Día Feriado — ' . htmlspecialchars($feriado->nombre),
                'Hoy ' . date('d/m/Y') . ' es "' . htmlspecialchars($feriado->nombre)
                . '", un día feriado no laborable. No se requiere asistencia y el día no cuenta en el cómputo de horas de pasantía.'
            );
            return;
        }

        // ── 3. Construir consulta ─────────────────────────────────────────────
        $where = ['a.fecha = :fecha', "a.estado != 'Anulado'"];
        $binds = [':fecha' => $fecha];

        if ($deptoId !== 'todos') {
            $where[] = 'dpa.departamento_asignado_id = :depto';
            $binds[':depto'] = (int)$deptoId;
        }

        $this->db->query("
            SELECT
                dp.apellidos, dp.nombres, u.cedula,
                d.nombre         AS departamento,
                a.fecha,
                a.estado,
                a.hora_registro,
                a.metodo
            FROM asistencias a
            JOIN usuarios          u    ON u.id   = a.pasante_id
            JOIN datos_personales  dp   ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            LEFT JOIN departamentos d   ON d.id   = dpa.departamento_asignado_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.nombre ASC, dp.apellidos ASC
        ");
        foreach ($binds as $p => $v) { $this->db->bind($p, $v); }
        $registros = $this->db->resultSet();

        // Agrupar por departamento para el PDF
        $porDepto = [];
        foreach ($registros as $r) {
            $dep = $r->departamento ?? 'Sin Departamento';
            $porDepto[$dep][] = $r;
        }

        // ── 4. Variables de contexto para la vista ───────────────────────────
        $esDiaHabil      = true;
        
        // Si el reporte es de un día pasado, la jornada ya cerró, así que los datos son definitivos (no muestra aviso).
        $esHorarioLaboral = ($fecha < date('Y-m-d')) ? true : ($horaActual >= 7 && $horaActual < 17);
        
        $subtitulo_pdf    = 'Ficha Diaria | ' . date('d/m/Y', strtotime($fecha));

        ob_start();
        include '../app/views/reportes/pdf_ficha_diaria.php';
        $html = ob_get_clean();
        $this->pdf->renderDomPdf($html, 'Ficha_Diaria_' . date('Ymd'), false);
    }

    /**
     * Constancias Administrativas — Carta de Culminación / Constancia de Servicio
     */
    // ══════════════════════════════════════════════════════════════
    // GET /reportes/constanciaGrupal — PDF con lista de pasantes
    // ══════════════════════════════════════════════════════════════
    public function constanciaGrupal(): void
    {
        $tipo       = trim($_GET['tipo']             ?? 'servicio');
        $filtro     = trim($_GET['filtro']           ?? 'todos');
        $deptId     = (int)($_GET['departamento_id'] ?? 0);
        $instId     = (int)($_GET['institucion_id']  ?? 0);
        $estado     = trim($_GET['estado']           ?? 'Finalizado');
        $periodoId  = (int)($_GET['periodo_id']      ?? 0);

        // Construir WHERE dinámico
        $where  = ["u.rol_id = 3", "u.estado IN ('activo','inactivo')"];
        $binds  = [];

        if ($estado !== 'todos') {
            $where[]          = "dpa.estado_pasantia = :estado";
            $binds[':estado'] = $estado;
        }
        if ($filtro === 'departamento' && $deptId > 0) {
            $where[]          = "dpa.departamento_asignado_id = :dept";
            $binds[':dept']   = $deptId;
        }
        if ($filtro === 'institucion' && $instId > 0) {
            $where[]          = "dpa.institucion_id = :inst";
            $binds[':inst']   = $instId;
        }
        if ($periodoId > 0) {
            $where[]           = "dpa.periodo_id = :periodo";
            $binds[':periodo'] = $periodoId;
        }

        // [FIX-AC-3] Aislamiento por departamento — antes un Tutor podía elegir
        // cualquier departamento_id por GET (o filtro=todos/institucion) y generar
        // constancias grupales de pasantes ajenos. Se fuerza siempre su propio
        // departamento, sin importar qué haya mandado el cliente.
        if ((int)Session::get('role_id') === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $deptTutor         = (int)($this->db->single()->departamento_id ?? 0);
            $where[]           = "dpa.departamento_asignado_id = :dept_tutor";
            $binds[':dept_tutor'] = $deptTutor;
        }

        $whereStr = 'WHERE ' . implode(' AND ', $where);

        $this->db->query("
            SELECT u.id, u.cedula, dp.nombres, dp.apellidos,
                   dpa.estado_pasantia,
                   COALESCE(dpa.horas_meta, 1440) AS horas_meta,
                   COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas,
                   COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio,
                   COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin,
                   d.nombre  AS departamento,
                   COALESCE(inst.nombre, dpa.institucion_procedencia, 'N/D') AS institucion,
                   pa.nombre AS periodo_nombre,
                   CONCAT(COALESCE(tp.nombres,''),' ',COALESCE(tp.apellidos,'')) AS tutor_nombre
            FROM usuarios u
            JOIN datos_personales dp     ON dp.usuario_id  = u.id
            JOIN datos_pasante    dpa    ON dpa.usuario_id = u.id
            LEFT JOIN periodos_academicos pa  ON pa.id  = dpa.periodo_id
            LEFT JOIN departamentos       d   ON d.id   = dpa.departamento_asignado_id
            LEFT JOIN instituciones       inst ON inst.id = dpa.institucion_id
            LEFT JOIN datos_personales    tp  ON tp.usuario_id = dpa.tutor_id
            $whereStr
            ORDER BY d.nombre ASC, dp.apellidos ASC, dp.nombres ASC
        ");
        foreach ($binds as $k => $v) { $this->db->bind($k, $v); }
        $pasantes = $this->db->resultSet();

        if (empty($pasantes)) {
            $this->renderErrorPage('Sin resultados', 'No se encontraron pasantes con los filtros seleccionados. Vuelve atrás y ajusta los filtros.');
            return;
        }

        // Obtener jefe para firma
        $this->db->query("
            SELECT dp.nombres, dp.apellidos, dp.cargo
            FROM datos_personales dp JOIN usuarios u ON u.id=dp.usuario_id
            WHERE u.rol_id=1 AND u.estado='activo' ORDER BY u.id ASC LIMIT 1
        ");
        $jefe = $this->db->single();

        // Contexto del filtro para el encabezado del PDF
        $contexto = '';
        if ($filtro === 'departamento' && $deptId > 0) {
            $this->db->query("SELECT nombre FROM departamentos WHERE id=:id LIMIT 1");
            $this->db->bind(':id', $deptId);
            $dep = $this->db->single();
            $contexto = 'Departamento: ' . ($dep->nombre ?? '—');
        } elseif ($filtro === 'institucion' && $instId > 0) {
            $this->db->query("SELECT nombre FROM instituciones WHERE id=:id LIMIT 1");
            $this->db->bind(':id', $instId);
            $ins = $this->db->single();
            $contexto = 'Institución: ' . ($ins->nombre ?? '—');
        } else {
            $contexto = 'Todos los pasantes';
        }

        $tipoLabel = $tipo === 'culminacion' ? 'Carta de Culminación' : 'Constancia de Servicio';

        $autoload = dirname(APPROOT) . '/vendor/autoload.php';
        if (!file_exists($autoload)) { $this->renderErrorPage('Error de dependencia', 'Composer autoloader no encontrado.'); return; }
        require_once $autoload;

        $opts = new \Dompdf\Options();
        $opts->set('isRemoteEnabled', true);
        $opts->set('isHtml5ParserEnabled', true);
        $opts->setDefaultFont('Helvetica');
        $dompdf = new \Dompdf\Dompdf($opts);

        ob_start();
        require APPROOT . '/views/reportes/pdf_constancia_grupal.php';
        $html = ob_get_clean();

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Constancia_Grupal_' . date('Ymd') . '.pdf', ['Attachment' => false]);
        exit;
    }

    private function generarConstancias(array $filtros): void
    {
        $tipo        = $filtros['tipo_constancia'] ?? 'servicio';
        $modoEmision = $filtros['modo_emision'] ?? 'registrado';

        // ══════════════════════════════════════════════════════════════
        // MODO MANUAL / HISTÓRICO (Egresados de períodos anteriores)
        // ══════════════════════════════════════════════════════════════
        if ($modoEmision === 'manual' || !empty($filtros['es_manual'])) {
            $tipo = $filtros['tipo_constancia_manual'] ?? $filtros['tipo_constancia'] ?? $tipo;
            
            $nombreCompletoInput = trim($filtros['manual_nombre_completo'] ?? '');
            if ($nombreCompletoInput === '') {
                $nombreCompletoInput = trim(($filtros['manual_nombres'] ?? '') . ' ' . ($filtros['manual_apellidos'] ?? ''));
            }
            $cedula = trim($filtros['manual_cedula'] ?? '');
            
            if (empty($nombreCompletoInput) || empty($cedula)) {
                $this->renderErrorPage('Datos incompletos', 'Debe indicar el nombre y cédula del pasante.');
                return;
            }

            // Duración por períodos (3, 6, 9 meses)
            $duracionMeses = (int)($filtros['manual_duracion_meses'] ?? 6);
            if (!in_array($duracionMeses, [3, 6, 9, 12])) {
                $duracionMeses = 6;
            }

            // Horas según la duración solicitada
            $mapaHoras = [
                3  => 480,
                6  => 960,
                9  => 1440,
                12 => 1920
            ];
            $horasMeta = $mapaHoras[$duracionMeses] ?? 960;
            $horasAcum = $horasMeta;

            $anioInicio = trim($filtros['manual_anio_inicio'] ?? $filtros['manual_anio_egreso'] ?? (date('Y') - 1));
            $anioFin    = trim($filtros['manual_anio_fin'] ?? $anioInicio);
            
            if (!empty($filtros['manual_periodo_texto'])) {
                $periodoTexto = "{$duracionMeses} Meses (" . trim($filtros['manual_periodo_texto']) . ")";
            } elseif (!empty($anioInicio) && !empty($anioFin) && $anioInicio !== $anioFin) {
                $periodoTexto = "{$duracionMeses} Meses (Período {$anioInicio} – {$anioFin})";
            } elseif (!empty($anioInicio)) {
                $periodoTexto = "{$duracionMeses} Meses (Año {$anioInicio})";
            } else {
                $periodoTexto = "{$duracionMeses} Meses";
            }

            // Departamento
            $deptoInput   = trim($filtros['manual_departamento'] ?? '');
            if ($deptoInput === 'otro' && !empty($filtros['manual_departamento_otro'])) {
                $departamento = trim($filtros['manual_departamento_otro']);
            } else {
                $departamento = $deptoInput ?: 'Informática y Sistemas';
            }

            // Institución
            $instInput = trim($filtros['manual_institucion'] ?? '');
            if (($instInput === 'otra' || $instInput === '__OTRA__') && !empty($filtros['manual_institucion_otra'])) {
                $institucion = trim($filtros['manual_institucion_otra']);
            } else {
                $institucion = ($instInput !== '__OTRA__') ? $instInput : 'N/D';
                $institucion = $institucion ?: 'N/D';
            }

            // Tutor empresarial / Técnico del sistema
            $tutorId = (int)($filtros['manual_tutor_id'] ?? 0);
            $tutorNombre = 'Tutor Empresarial';
            $tutorCedula = '';
            $tutorCargo  = 'Tutor Empresarial';

            if ($tutorId > 0) {
                $this->db->query("
                    SELECT u.cedula,
                           CONCAT(dp.nombres, ' ', dp.apellidos) AS nombre,
                           COALESCE(NULLIF(dp.cargo, ''), 'Técnico') AS cargo
                    FROM usuarios u
                    JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE u.id = :tid LIMIT 1
                ");
                $this->db->bind(':tid', $tutorId);
                $tut = $this->db->single();
                if ($tut) {
                    $tutorNombre = $tut->nombre;
                    $tutorCedula = $tut->cedula ?? '';
                    $tutorCargo  = $tut->cargo;
                }
            } elseif (!empty($filtros['manual_tutor_nombre'])) {
                $tutorNombre = trim($filtros['manual_tutor_nombre']);
                $tutorCedula = trim($filtros['manual_tutor_cedula'] ?? '');
                $tutorCargo  = trim($filtros['manual_tutor_cargo'] ?? 'Tutor Empresarial');
            }

            // Si vienen fechas opcionales, se conservan, si no quedan nulas (el PDF se adaptará al período)
            $fechaInicio  = !empty($filtros['manual_fecha_inicio']) ? $filtros['manual_fecha_inicio'] : null;
            $fechaFin     = !empty($filtros['manual_fecha_fin']) ? $filtros['manual_fecha_fin'] : null;
            $rawEmision   = !empty($filtros['manual_fecha_emision']) ? $filtros['manual_fecha_emision'] : null;

            // Datos institucionales del jefe / admin para firma
            $this->db->query("
                SELECT dp2.nombres, dp2.apellidos, dp2.cargo
                FROM datos_personales dp2
                JOIN usuarios u2 ON u2.id = dp2.usuario_id
                WHERE u2.rol_id = 1 AND u2.estado = 'activo'
                ORDER BY u2.id ASC LIMIT 1
            ");
            $adm = $this->db->single();

            $pasante = (object)[
                'nombre_completo'  => $nombreCompletoInput,
                'nombres'          => $nombreCompletoInput,
                'apellidos'        => '',
                'cedula'           => preg_replace('/[^0-9]/', '', $cedula),
                'departamento'     => $departamento,
                'institucion'      => $institucion,
                'estado_pasantia'  => ($tipo === 'culminacion' ? 'Finalizado' : 'Activo'),
                'horas_acumuladas' => $horasAcum,
                'horas_meta'       => $horasMeta,
                'duracion_meses'   => $duracionMeses,
                'periodo_texto'    => $periodoTexto,
                'anio_egreso'      => $anioEgreso,
                'fecha_inicio'     => $fechaInicio,
                'fecha_fin'        => $fechaFin,
                'tutor_nombre'     => $tutorNombre,
                'tutor_cedula'     => $tutorCedula,
                'tutor_cargo'      => $tutorCargo,
                'jefe_nombres'     => $adm->nombres ?? '',
                'jefe_apellidos'   => $adm->apellidos ?? '',
                'jefe_cargo'       => $adm->cargo ?? ''
            ];

            $meses = ['', 'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            if ($rawEmision) {
                $ts = strtotime($rawEmision);
                $fechaEmision = date('d', $ts) . ' de ' . ($meses[(int)date('n', $ts)] ?? date('F', $ts)) . ' de ' . date('Y', $ts);
                $diaEmision   = date('d', $ts);
                $mesEmision   = $meses[(int)date('n', $ts)] ?? date('F', $ts);
                $anioEmision  = date('Y', $ts);
            } else {
                $fechaEmision = date('d') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');
                $diaEmision   = date('d');
                $mesEmision   = $meses[(int)date('n')];
                $anioEmision  = date('Y');
            }

            $jefeNombre = mb_strtoupper(
                trim(($pasante->jefe_nombres ?? '') . ' ' . ($pasante->jefe_apellidos ?? '')),
                'UTF-8'
            );

            // Respaldo oficial de la constancia emitida en base de datos y auditoría
            $codigoConstancia = $this->registrarRespaldoConstancia(
                $tipo,
                'manual',
                null,
                $cedula,
                $nombreCompletoInput,
                $institucion,
                $departamento,
                $tutorId,
                $tutorNombre,
                'Jefe de la División de Soporte Técnico de la Dirección de Telemática',
                $duracionMeses,
                $periodoTexto,
                $horasMeta
            );

            ob_start();
            include '../app/views/reportes/pdf_constancia.php';
            $html = ob_get_clean();

            $nombreArchivo = ($tipo === 'culminacion' ? 'Carta_Culminacion' : 'Constancia_Servicio')
                . '_' . ($pasante->cedula ?? 'Historico') . '_' . $codigoConstancia;
            $this->pdf->renderDomPdf($html, $nombreArchivo, false);
            return;
        }

        // ══════════════════════════════════════════════════════════════
        // MODO REGISTRADO (Pasante existente en la base de datos)
        // ══════════════════════════════════════════════════════════════
        $pasanteId = (int)($filtros['pasante_id'] ?? 0);

        if (!$pasanteId) {
            $this->renderErrorPage('Datos incompletos', 'Seleccione un pasante.');
            return;
        }

        // Obtener datos completos del pasante
        $this->db->query("
            SELECT
                dp.nombres, dp.apellidos, u.cedula,
                d.nombre                                      AS departamento,
                COALESCE(inst.nombre, dpa.institucion_procedencia, 'N/D') AS institucion,
                dpa.estado_pasantia,
                COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas,
                dpa.horas_meta,
                -- Fechas: primero datos_pasante, fallback a fechas del período académico
                COALESCE(dpa.fecha_inicio_pasantia, pa.fecha_inicio) AS fecha_inicio,
                COALESCE(dpa.fecha_fin_estimada,    pa.fecha_fin)    AS fecha_fin,
                CONCAT(tp.nombres,' ',tp.apellidos)           AS tutor_nombre,
                tu.cedula                                     AS tutor_cedula,
                tp.cargo                                      AS tutor_cargo,
                adm.nombres                                   AS jefe_nombres,
                adm.apellidos                                 AS jefe_apellidos,
                adm.cargo                                     AS jefe_cargo
            FROM usuarios u
            JOIN datos_personales  dp   ON dp.usuario_id  = u.id
            JOIN datos_pasante     dpa  ON dpa.usuario_id = u.id
            LEFT JOIN periodos_academicos pa ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos d   ON d.id   = dpa.departamento_asignado_id
            LEFT JOIN instituciones inst ON inst.id = dpa.institucion_id
            LEFT JOIN usuarios      tu  ON tu.id   = dpa.tutor_id
            LEFT JOIN datos_personales tp ON tp.usuario_id = dpa.tutor_id
            LEFT JOIN (
                SELECT dp2.nombres, dp2.apellidos, dp2.cargo
                FROM datos_personales dp2
                JOIN usuarios u2 ON u2.id = dp2.usuario_id
                WHERE u2.rol_id = 1 AND u2.estado = 'activo'
                ORDER BY u2.id ASC LIMIT 1
            ) AS adm ON 1=1
            WHERE u.id = :pid AND u.rol_id = 3
            LIMIT 1
        ");
        $this->db->bind(':pid', $pasanteId);
        $pasante = $this->db->single();

        if (!$pasante) {
            $this->renderErrorPage('Pasante no encontrado', 'No se encontraron datos para este pasante.');
        }

        // Validación de culminación
        if ($tipo === 'culminacion' && ($pasante->estado_pasantia ?? '') !== 'Finalizado') {
            $this->renderErrorPage(
                'Carta no disponible',
                'La carta de culminación solo se genera cuando el pasante ha finalizado su pasant\u00eda.'
            );
        }

        $fechaEmision = date('d')  . ' de '
            . ['', 'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][(int)date('n')]
            . ' de ' . date('Y');

        $jefeNombre = mb_strtoupper(
            trim(($pasante->jefe_nombres ?? '') . ' ' . ($pasante->jefe_apellidos ?? '')),
            'UTF-8'
        );

        $nombreCompletoReg = trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''));
        // Respaldo oficial de la constancia emitida en base de datos y auditoría
        $codigoConstancia = $this->registrarRespaldoConstancia(
            $tipo,
            'registrado',
            $pasanteId,
            $pasante->cedula ?? '',
            $nombreCompletoReg,
            $pasante->institucion ?? 'N/D',
            $pasante->departamento ?? 'N/D',
            null,
            $pasante->tutor_nombre ?? 'Tutor de División',
            'Jefe de la División de Soporte Técnico de la Dirección de Telemática',
            6,
            $pasante->periodo_texto ?? null,
            (int)($pasante->horas_meta ?? 960)
        );

        ob_start();
        include '../app/views/reportes/pdf_constancia.php';
        $html = ob_get_clean();

        $nombreArchivo = ($tipo === 'culminacion' ? 'Carta_Culminacion' : 'Constancia_Servicio')
            . '_' . ($pasante->cedula ?? $pasanteId) . '_' . $codigoConstancia;
        $this->pdf->renderDomPdf($html, $nombreArchivo, false);
    }

    /**
     * Registra el respaldo oficial de una constancia emitida en la base de datos y auditoría.
     */
    private function registrarRespaldoConstancia(
        string $tipoConstancia,
        string $modoEmision,
        ?int $pasanteId,
        string $cedula,
        string $nombreCompleto,
        string $institucion,
        string $departamento,
        ?int $tutorId,
        string $tutorNombre,
        string $tutorCargo,
        int $duracionMeses,
        ?string $periodoTexto,
        int $horasMeta
    ): string {
        try {
            $anioActual = date('Y');
            $this->db->query("SELECT COUNT(*) AS total FROM constancias_emitidas WHERE YEAR(created_at) = :anio");
            $this->db->bind(':anio', $anioActual);
            $rowCont = $this->db->single();
            $numeroSecuencia = ((int)($rowCont->total ?? 0)) + 1;
            $codigoConstancia = sprintf("CST-%s-%04d", $anioActual, $numeroSecuencia);

            $this->db->query("
                INSERT INTO constancias_emitidas (
                    codigo_constancia, tipo_constancia, modo_emision, pasante_id,
                    cedula, nombre_completo, institucion, departamento,
                    tutor_id, tutor_nombre, tutor_cargo,
                    duracion_meses, periodo_texto, horas_meta, emitido_por
                ) VALUES (
                    :codigo, :tipo, :modo, :pasante_id,
                    :cedula, :nombre, :institucion, :departamento,
                    :tutor_id, :tutor_nombre, :tutor_cargo,
                    :duracion, :periodo_texto, :horas, :emitido_por
                )
            ");
            $this->db->bind(':codigo', $codigoConstancia);
            $this->db->bind(':tipo', $tipoConstancia);
            $this->db->bind(':modo', $modoEmision);
            $this->db->bind(':pasante_id', $pasanteId ?: null);
            $this->db->bind(':cedula', $cedula);
            $this->db->bind(':nombre', $nombreCompleto);
            $this->db->bind(':institucion', $institucion);
            $this->db->bind(':departamento', $departamento);
            $this->db->bind(':tutor_id', $tutorId ?: null);
            $this->db->bind(':tutor_nombre', $tutorNombre);
            $this->db->bind(':tutor_cargo', $tutorCargo);
            $this->db->bind(':duracion', $duracionMeses);
            $this->db->bind(':periodo_texto', $periodoTexto);
            $this->db->bind(':horas', $horasMeta);
            $this->db->bind(':emitido_por', (int)Session::get('user_id'));
            $this->db->execute();
            $regId = $this->db->lastInsertId();

            // Registro en bitácora de auditoría del sistema
            $this->db->query("
                INSERT INTO bitacora (usuario_id, accion, tabla_afectada, registro_id, ip_address, detalles)
                VALUES (:uid, 'EMISION_CONSTANCIA', 'constancias_emitidas', :rid, :ip, :detalles)
            ");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $this->db->bind(':rid', $regId);
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $this->db->bind(':detalles', sprintf(
                "Emisión de %s (%s) para %s (C.I. %s) - %s",
                $tipoConstancia === 'culminacion' ? 'Carta de Culminación' : 'Constancia de Servicio',
                $codigoConstancia,
                $nombreCompleto,
                $cedula,
                $periodoTexto ?: 'Carga Horaria Estándar'
            ));
            $this->db->execute();

            return $codigoConstancia;
        } catch (\Throwable $e) {
            error_log("[SGP-CONSTANCIAS-RESPALDO] Error al guardar constancia: " . $e->getMessage());
            return sprintf("CST-%s-%s", date('Y'), strtoupper(substr(md5(uniqid()), 0, 6)));
        }
    }

    /**
     * GET /reportes/historialConstancias
     * Devuelve en JSON el listado de constancias respaldadas en la base de datos.
     */
    public function historialConstancias(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $this->db->query("
                SELECT c.*, 
                       COALESCE(CONCAT(dp.nombres, ' ', dp.apellidos), u.correo, 'Sistema') AS emitido_por_nombre
                FROM constancias_emitidas c
                LEFT JOIN usuarios u ON u.id = c.emitido_por
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                ORDER BY c.id DESC
                LIMIT 150
            ");
            $data = $this->db->resultSet() ?: [];
            echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET /reportes/reimprimirConstancia?id=X o ?codigo=CST-2026-XXXX
     * Abre y regenera el PDF respaldado de la constancia con su número de control original.
     */
    public function reimprimirConstancia(): void
    {
        $id     = (int)($_GET['id'] ?? 0);
        $codigo = trim($_GET['codigo'] ?? '');

        if ($id > 0) {
            $this->db->query("SELECT * FROM constancias_emitidas WHERE id = :id LIMIT 1");
            $this->db->bind(':id', $id);
        } elseif (!empty($codigo)) {
            $this->db->query("SELECT * FROM constancias_emitidas WHERE codigo_constancia = :cod LIMIT 1");
            $this->db->bind(':cod', $codigo);
        } else {
            $this->renderErrorPage('Parámetros inválidos', 'Debe especificar el ID o código de la constancia.');
            return;
        }

        $row = $this->db->single();
        if (!$row) {
            $this->renderErrorPage('Constancia no encontrada', 'El registro solicitado no existe en el respaldo.');
            return;
        }

        $tipo = $row->tipo_constancia;
        $codigoConstancia = $row->codigo_constancia;
        $pasante = (object)[
            'nombre_completo'      => $row->nombre_completo,
            'nombres'              => $row->nombre_completo,
            'apellidos'            => '',
            'cedula'               => $row->cedula,
            'departamento'         => $row->departamento,
            'institucion'          => $row->institucion,
            'estado_pasantia'      => ($tipo === 'culminacion' ? 'Finalizado' : 'Activo'),
            'horas_acumuladas'     => (int)$row->horas_meta,
            'horas_meta'           => (int)$row->horas_meta,
            'duracion_meses'       => (int)$row->duracion_meses,
            'periodo_texto'        => $row->periodo_texto,
            'cuerpo_personalizado' => $row->cuerpo_personalizado ?? null,
            'tutor_nombre'         => $row->tutor_nombre,
            'tutor_cedula'         => '',
            'tutor_cargo'          => $row->tutor_cargo ?: 'Jefe de la División de Soporte Técnico de la Dirección de Telemática',
        ];

        $ts = strtotime($row->created_at);
        $meses = ['', 'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $fechaEmision = date('d', $ts) . ' de ' . ($meses[(int)date('n', $ts)] ?? date('F', $ts)) . ' de ' . date('Y', $ts);
        $diaEmision   = date('d', $ts);
        $mesEmision   = $meses[(int)date('n', $ts)] ?? date('F', $ts);
        $anioEmision  = date('Y', $ts);

        $jefeNombre = mb_strtoupper($row->tutor_nombre ?: 'RESPONSABLE DE SOPORTE TÉCNICO', 'UTF-8');

        ob_start();
        include '../app/views/reportes/pdf_constancia.php';
        $html = ob_get_clean();

        $download = (!empty($_GET['download']) && $_GET['download'] == 1);
        $nombreArchivo = ($tipo === 'culminacion' ? 'Carta_Culminacion' : 'Constancia_Servicio')
            . '_' . ($pasante->cedula ?? 'Historico') . '_' . $codigoConstancia;
        $this->pdf->renderDomPdf($html, $nombreArchivo, $download);
    }

    /**
     * POST /reportes/actualizarConstancia
     * Actualiza los datos y cuerpo personalizado de una constancia respaldada.
     */
    public function actualizarConstancia(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID de constancia inválido']);
                exit;
            }

            $nombre              = trim($_POST['nombre_completo'] ?? '');
            $cedula              = trim($_POST['cedula'] ?? '');
            $institucion         = trim($_POST['institucion'] ?? '');
            $departamento        = trim($_POST['departamento'] ?? '');
            $periodoTexto        = trim($_POST['periodo_texto'] ?? '');
            $duracionMeses       = (int)($_POST['duracion_meses'] ?? 6);
            $horasMeta           = (int)($_POST['horas_meta'] ?? 960);
            $tutorNombre         = trim($_POST['tutor_nombre'] ?? '');
            $tutorCargo          = trim($_POST['tutor_cargo'] ?? '');
            $cuerpoPersonalizado = trim($_POST['cuerpo_personalizado'] ?? '');

            if (empty($nombre) || empty($cedula)) {
                echo json_encode(['success' => false, 'error' => 'El nombre y cédula no pueden estar vacíos']);
                exit;
            }

            $this->db->query("
                UPDATE constancias_emitidas SET
                    nombre_completo      = :nombre,
                    cedula               = :cedula,
                    institucion          = :institucion,
                    departamento         = :departamento,
                    periodo_texto        = :periodo_texto,
                    duracion_meses       = :duracion_meses,
                    horas_meta           = :horas_meta,
                    tutor_nombre         = :tutor_nombre,
                    tutor_cargo          = :tutor_cargo,
                    cuerpo_personalizado = :cuerpo
                WHERE id = :id
            ");
            $this->db->bind(':nombre', $nombre);
            $this->db->bind(':cedula', $cedula);
            $this->db->bind(':institucion', $institucion);
            $this->db->bind(':departamento', $departamento);
            $this->db->bind(':periodo_texto', $periodoTexto);
            $this->db->bind(':duracion_meses', $duracionMeses);
            $this->db->bind(':horas_meta', $horasMeta);
            $this->db->bind(':tutor_nombre', $tutorNombre);
            $this->db->bind(':tutor_cargo', $tutorCargo ?: 'Jefe de la División de Soporte Técnico de la Dirección de Telemática');
            $this->db->bind(':cuerpo', !empty($cuerpoPersonalizado) ? $cuerpoPersonalizado : null);
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Auditoría
            $this->db->query("
                INSERT INTO bitacora (usuario_id, accion, tabla_afectada, registro_id, ip_address, detalles)
                VALUES (:uid, 'EDITAR_CONSTANCIA', 'constancias_emitidas', :rid, :ip, :detalles)
            ");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $this->db->bind(':rid', $id);
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $this->db->bind(':detalles', sprintf("Modificación de constancia ID #%d para %s (C.I. %s)", $id, $nombre, $cedula));
            $this->db->execute();

            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /reportes/eliminarConstancia
     * Elimina un registro de constancia respaldada.
     */
    public function eliminarConstancia(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                exit;
            }

            // Datos previos para auditoría
            $this->db->query("SELECT * FROM constancias_emitidas WHERE id = :id LIMIT 1");
            $this->db->bind(':id', $id);
            $c = $this->db->single();

            if (!$c) {
                echo json_encode(['success' => false, 'error' => 'Constancia no encontrada']);
                exit;
            }

            $this->db->query("DELETE FROM constancias_emitidas WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            // Auditoría
            $this->db->query("
                INSERT INTO bitacora (usuario_id, accion, tabla_afectada, registro_id, ip_address, detalles)
                VALUES (:uid, 'ELIMINAR_CONSTANCIA', 'constancias_emitidas', :rid, :ip, :detalles)
            ");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $this->db->bind(':rid', $id);
            $this->db->bind(':ip', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $this->db->bind(':detalles', sprintf(
                "Eliminación de constancia %s de %s (C.I. %s)",
                $c->codigo_constancia,
                $c->nombre_completo,
                $c->cedula
            ));
            $this->db->execute();

            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════
    // VISTA ANUAL — PDF Individual y Exportaciones Excel
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /reportes/pdfIndividual?id=X&download=0|1
     * PDF de kardex individual desde la vista anual (alias de pdfKardex).
     */
    public function pdfIndividual(): void
    {
        $pasanteId = (int)($_GET['id'] ?? 0);
        if (!$pasanteId) {
            App::renderError(404);
            return;
        }
        // Reutilizar la lógica del kardex
        $this->pdfKardex($pasanteId);
    }

    /**
     * GET /reportes/exportarExcel?id=X
     * Descarga CSV de asistencias de un pasante individual.
     */
    public function exportarExcel(): void
    {
        $pasanteId = (int)($_GET['id'] ?? 0);
        if (!$pasanteId) {
            App::renderError(404);
            return;
        }

        // [FIX-AC-3] IDOR directo — antes cualquier Tutor podía cambiar ?id= en la URL
        // y descargar las asistencias de un pasante de otro departamento.
        if ((int)Session::get('role_id') === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $_deptId = (int)($this->db->single()->departamento_id ?? 0);

            $this->db->query("SELECT departamento_asignado_id FROM datos_pasante WHERE usuario_id = :pid LIMIT 1");
            $this->db->bind(':pid', $pasanteId);
            $chk = $this->db->single();
            if (!$chk || (int)($chk->departamento_asignado_id ?? 0) !== $_deptId) {
                http_response_code(403);
                echo 'Acceso denegado.';
                exit;
            }
        }

        // Datos del pasante
        $this->db->query("
            SELECT dp.nombres, dp.apellidos, u.cedula
            FROM   usuarios u
            JOIN   datos_personales dp ON dp.usuario_id = u.id
            WHERE  u.id = :pid AND u.rol_id = 3
            LIMIT  1
        ");
        $this->db->bind(':pid', $pasanteId);
        $pasante = $this->db->single();

        if (!$pasante) {
            App::renderError(404);
            return;
        }

        // Asistencias
        $this->db->query("
            SELECT fecha, estado, hora_entrada, hora_salida, observacion
            FROM   asistencias
            WHERE  pasante_id = :pid
            ORDER  BY fecha DESC
        ");
        $this->db->bind(':pid', $pasanteId);
        $registros = $this->db->resultSet();

        $nombre   = trim(($pasante->apellidos ?? '') . ', ' . ($pasante->nombres ?? ''));
        $filename = 'Asistencias_' . ($pasante->cedula ?? $pasanteId) . '_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Pasante', 'Cédula', 'Fecha', 'Estado', 'Hora Entrada', 'Hora Salida', 'Observación'], ';');

        foreach ($registros as $r) {
            fputcsv($out, [
                $nombre,
                $pasante->cedula ?? '—',
                $r->fecha         ?? '',
                $r->estado        ?? '',
                $r->hora_entrada  ?? '',
                $r->hora_salida   ?? '',
                $r->observacion   ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }

    /**
     * GET /reportes/excelAnual?anio=YYYY
     * Descarga CSV con nómina global de pasantes del año.
     */
    public function excelAnual(): void
    {
        $anio = (int)($_GET['anio'] ?? date('Y'));

        // [FIX-AC-3] Aislamiento por departamento — mismo criterio que pdfNomina().
        $rolId     = (int)Session::get('role_id');
        $deptWhere = '';
        if ($rolId === 2) {
            $this->db->query("SELECT departamento_id FROM usuarios WHERE id = :uid LIMIT 1");
            $this->db->bind(':uid', (int)Session::get('user_id'));
            $_deptId   = (int)($this->db->single()->departamento_id ?? 0);
            $deptWhere = 'AND dpa.departamento_asignado_id = :dept_id';
        }

        $this->db->query("
            SELECT
                u.cedula,
                dp.nombres,
                dp.apellidos,
                dep.nombre                                              AS departamento,
                COALESCE(inst.nombre, dpa.institucion_procedencia, 'N/D') AS institucion,
                dpa.estado_pasantia,
                dpa.fecha_inicio_pasantia                               AS fecha_inicio,
                dpa.fecha_fin_estimada                                  AS fecha_fin,
                COUNT(a.id)                                             AS total_dias,
                SUM(CASE WHEN a.estado = 'Presente'    THEN 1 ELSE 0 END) AS presentes,
                SUM(CASE WHEN a.estado = 'Ausente'     THEN 1 ELSE 0 END) AS ausentes,
                SUM(CASE WHEN a.estado = 'Justificado' THEN 1 ELSE 0 END) AS justificados
            FROM usuarios u
            JOIN datos_personales dp   ON dp.usuario_id   = u.id
            JOIN datos_pasante    dpa  ON dpa.usuario_id  = u.id
            LEFT JOIN departamentos dep  ON dep.id = dpa.departamento_asignado_id
            LEFT JOIN instituciones inst ON inst.id = dpa.institucion_id
            LEFT JOIN asistencias   a    ON a.pasante_id  = u.id
                                        AND YEAR(a.fecha) = :anio
            WHERE u.rol_id = 3
              AND COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
              {$deptWhere}
            GROUP BY u.id, u.cedula, dp.nombres, dp.apellidos,
                     dep.nombre, inst.nombre, dpa.institucion_procedencia, dpa.estado_pasantia,
                     dpa.fecha_inicio_pasantia, dpa.fecha_fin_estimada
            ORDER BY dep.nombre ASC, dp.apellidos ASC, dp.nombres ASC
        ");
        $this->db->bind(':anio', $anio);
        if ($rolId === 2) {
            $this->db->bind(':dept_id', $_deptId);
        }
        $pasantes = $this->db->resultSet();

        $filename = 'Nomina_Pasantes_' . $anio . '_' . date('Ymd') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'N°', 'Cédula', 'Apellidos', 'Nombres', 'Departamento',
            'Institución', 'Estado', 'F. Inicio', 'F. Fin',
            'Total Días', 'Presentes', 'Ausentes', 'Justificados', '% Asistencia'
        ], ';');

        foreach ($pasantes as $i => $p) {
            $total = (int)($p->total_dias ?? 0);
            $pres  = (int)($p->presentes  ?? 0);
            $pct   = $total > 0 ? round(($pres / $total) * 100) : 0;
            fputcsv($out, [
                $i + 1,
                $p->cedula           ?? '—',
                $p->apellidos        ?? '',
                $p->nombres          ?? '',
                $p->departamento     ?? 'Sin asignar',
                $p->institucion      ?? 'N/D',
                $p->estado_pasantia  ?? '',
                $p->fecha_inicio     ?? '',
                $p->fecha_fin        ?? '',
                $total,
                $pres,
                (int)($p->ausentes   ?? 0),
                (int)($p->justificados ?? 0),
                $pct . '%',
            ], ';');
        }
        fclose($out);
        exit;
    }
}
