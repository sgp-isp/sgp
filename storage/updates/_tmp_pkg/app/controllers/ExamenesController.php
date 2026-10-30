<?php
/**
 * ExamenesController — Módulo de Exámenes Rápidos (Quiz)
 *
 * Gestión de exámenes de opción múltiple y verdadero/falso.
 * Roles: Admin (1), Tutor (2) pueden crear/gestionar.
 *        Pasante (3) es redirigido a /pasante/misExamenes.
 *
 * RUTAS:
 *   GET  /examenes                  → index()           Dashboard + listado
 *   GET  /examenes/nuevo            → nuevo()           Formulario de creación (legacy)
 *   POST /examenes/guardar          → guardar()         AJAX — crear examen con preguntas
 *   GET  /examenes/ver/{id}         → ver()             Detalle + resultados
 *   POST /examenes/publicar         → publicar()        AJAX — toggle activo
 *   POST /examenes/eliminar         → eliminar()        AJAX — eliminar examen
 *   GET  /examenes/detalleIntento   → detalleIntento()   AJAX — respuestas del intento
 *   POST /examenes/marcarRevisado   → marcarRevisado()   AJAX — marcar intento revisado
 *   POST /examenes/actualizarPuntos → actualizarPuntos() AJAX — editar puntos de pregunta
 *   POST /examenes/eliminarIntento  → eliminarIntento()  AJAX — borrar un intento del ranking
 */

declare(strict_types=1);

class ExamenesController extends Controller
{
    private $db;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();

        $this->db = Database::getInstance();
        require_once APPROOT . '/models/NotificationModel.php';
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /examenes — Listado principal
    // ─────────────────────────────────────────────────────────────────
    public function index(): void
    {
        $rolId  = (int)(Session::get('role_id') ?? 0);
        $userId = (int)(Session::get('user_id') ?? 0);

        // Pasantes son redirigidos a su propia vista
        if ($rolId === 3) {
            $this->redirect('/pasante/misExamenes');
            return;
        }

        $baseQuery = "
            SELECT e.*,
                   CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS creador_nombre,
                   COALESCE(pa.nombre, 'Sin período') AS periodo_nombre,
                   (SELECT COUNT(*) FROM examen_preguntas ep WHERE ep.examen_id = e.id) AS total_preguntas,
                   (SELECT COUNT(*) FROM examen_intentos ei WHERE ei.examen_id = e.id AND ei.enviado_at IS NOT NULL) AS total_respondieron
            FROM examenes e
            LEFT JOIN usuarios u ON u.id = e.creado_por
            LEFT JOIN datos_personales dp ON dp.usuario_id = e.creado_por
            LEFT JOIN periodos_academicos pa ON pa.id = e.periodo_id
        ";

        if ($rolId === 2) {
            // Tutor: solo ve sus propios exámenes
            $this->db->query($baseQuery . " WHERE e.creado_por = :uid ORDER BY e.created_at DESC");
            $this->db->bind(':uid', $userId);
        } else {
            // Admin/SuperAdmin: ve todos
            $this->db->query($baseQuery . " ORDER BY e.created_at DESC");
        }
        $examenes = $this->db->resultSet();

        // Períodos para el selector del modal
        $this->db->query("SELECT id, nombre, COALESCE(tipo,'Regular') AS tipo FROM periodos_academicos ORDER BY created_at DESC");
        $periodos = $this->db->resultSet();

        // ── Analytics ────────────────────────────────────────────────────────

        $this->db->query("SELECT COUNT(*) AS cnt FROM examenes WHERE activo = 1");
        $totalActivos = (int)($this->db->single()->cnt ?? 0);

        $this->db->query("SELECT COUNT(DISTINCT pasante_id) AS cnt FROM examen_intentos WHERE enviado_at IS NOT NULL");
        $totalEvaluados = (int)($this->db->single()->cnt ?? 0);

        $this->db->query("SELECT COALESCE(AVG(porcentaje), 0) AS avg FROM examen_intentos WHERE enviado_at IS NOT NULL");
        $avgScore = round((float)($this->db->single()->avg ?? 0), 1);

        $this->db->query("
            SELECT COUNT(*) AS total,
                   SUM(CASE WHEN porcentaje >= 60 THEN 1 ELSE 0 END) AS aprobados
            FROM examen_intentos WHERE enviado_at IS NOT NULL
        ");
        $tmpKpi = $this->db->single();
        $tasaAprobacion = ($tmpKpi && (int)$tmpKpi->total > 0)
            ? round(((int)$tmpKpi->aprobados / (int)$tmpKpi->total) * 100, 1)
            : 0.0;

        // Distribución de puntajes en 5 rangos
        $this->db->query("
            SELECT
                SUM(CASE WHEN porcentaje <  20 THEN 1 ELSE 0 END) AS r0,
                SUM(CASE WHEN porcentaje >= 20 AND porcentaje < 40 THEN 1 ELSE 0 END) AS r20,
                SUM(CASE WHEN porcentaje >= 40 AND porcentaje < 60 THEN 1 ELSE 0 END) AS r40,
                SUM(CASE WHEN porcentaje >= 60 AND porcentaje < 80 THEN 1 ELSE 0 END) AS r60,
                SUM(CASE WHEN porcentaje >= 80 THEN 1 ELSE 0 END) AS r80
            FROM examen_intentos WHERE enviado_at IS NOT NULL
        ");
        $dist = $this->db->single();

        // Top 5 pasantes por mejor puntaje
        $this->db->query("
            SELECT CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS pasante_nombre,
                   u.cedula,
                   MAX(ei.porcentaje) AS mejor_pct,
                   COUNT(ei.id) AS total_intentos
            FROM examen_intentos ei
            INNER JOIN usuarios u ON u.id = ei.pasante_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = ei.pasante_id
            WHERE ei.enviado_at IS NOT NULL
            GROUP BY ei.pasante_id, dp.nombres, dp.apellidos, u.cedula
            ORDER BY mejor_pct DESC
            LIMIT 5
        ");
        $top5 = $this->db->resultSet();

        // Exámenes activos con progreso
        $this->db->query("
            SELECT e.id, e.titulo,
                   (SELECT COUNT(*) FROM examen_intentos ei
                    WHERE ei.examen_id = e.id AND ei.enviado_at IS NOT NULL) AS respondieron
            FROM examenes e
            WHERE e.activo = 1
            ORDER BY e.created_at DESC
            LIMIT 5
        ");
        $examenesActivos = $this->db->resultSet();

        // Envíos recientes
        $this->db->query("
            SELECT ei.enviado_at, ei.porcentaje, ei.puntaje_obtenido, ei.puntaje_maximo,
                   CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS pasante_nombre,
                   ex.titulo AS examen_titulo
            FROM examen_intentos ei
            INNER JOIN examenes ex ON ex.id = ei.examen_id
            INNER JOIN usuarios u ON u.id = ei.pasante_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = ei.pasante_id
            WHERE ei.enviado_at IS NOT NULL
            ORDER BY ei.enviado_at DESC
            LIMIT 8
        ");
        $recientes = $this->db->resultSet();

        $this->view('examenes/index', [
            'examenes'        => $examenes,
            'periodos'        => $periodos,
            'totalActivos'    => $totalActivos,
            'totalEvaluados'  => $totalEvaluados,
            'avgScore'        => $avgScore,
            'tasaAprobacion'  => $tasaAprobacion,
            'dist'            => $dist,
            'top5'            => $top5,
            'examenesActivos' => $examenesActivos,
            'recientes'       => $recientes,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /examenes/nuevo — Formulario de creación
    // ─────────────────────────────────────────────────────────────────
    public function nuevo(): void
    {
        $rolId = (int)(Session::get('role_id') ?? 0);

        if ($rolId === 3) {
            $this->redirect('/pasante/misExamenes');
            return;
        }

        $this->db->query("SELECT id, nombre, COALESCE(tipo,'Regular') AS tipo FROM periodos_academicos ORDER BY created_at DESC");
        $periodos = $this->db->resultSet();

        $this->view('examenes/nuevo', [
            'periodos' => $periodos,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /examenes/guardar — AJAX: crear examen con preguntas
    // ─────────────────────────────────────────────────────────────────
    public function guardar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $rolId  = (int)(Session::get('role_id') ?? 0);
        $userId = (int)(Session::get('user_id') ?? 0);

        if ($rolId === 3) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso para crear exámenes']);
            return;
        }

        // Leer JSON body
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);

        if (!$body) {
            echo json_encode(['success' => false, 'message' => 'Cuerpo de la solicitud inválido']);
            return;
        }

        $titulo             = trim($body['titulo'] ?? '');
        $descripcion        = trim($body['descripcion'] ?? '');
        $periodoId          = !empty($body['periodo_id']) ? (int)$body['periodo_id'] : null;
        $fechaInicio        = !empty($body['fecha_inicio']) ? $body['fecha_inicio'] : null;
        $fechaFin           = !empty($body['fecha_fin']) ? $body['fecha_fin'] : null;
        // [FIX-N-11] examen_intentos tiene UNIQUE(examen_id, pasante_id): solo existe un
        // intento por pasante, así que "intentos permitidos" > 1 nunca podía cumplirse.
        // Se fija en 1 en servidor sin importar lo que envíe el cliente.
        $intentosPermitidos = 1;
        $preguntas          = $body['preguntas'] ?? [];
        $publicar           = !empty($body['publicar']) ? 1 : 0;

        // ── Validaciones ────────────────────────────────────────────
        if ($titulo === '') {
            echo json_encode(['success' => false, 'message' => 'El título es obligatorio']);
            return;
        }

        if (empty($preguntas)) {
            echo json_encode(['success' => false, 'message' => 'El examen debe tener al menos una pregunta']);
            return;
        }

        foreach ($preguntas as $idx => $preg) {
            $num = $idx + 1;

            if (empty(trim($preg['enunciado'] ?? ''))) {
                echo json_encode(['success' => false, 'message' => "La pregunta #{$num} no tiene enunciado"]);
                return;
            }

            $opciones = $preg['opciones'] ?? [];
            if (count($opciones) < 2) {
                echo json_encode(['success' => false, 'message' => "La pregunta #{$num} debe tener al menos 2 opciones"]);
                return;
            }

            $correctas = array_filter($opciones, fn($o) => !empty($o['es_correcta']));
            if (count($correctas) !== 1) {
                echo json_encode(['success' => false, 'message' => "La pregunta #{$num} debe tener exactamente 1 opción correcta"]);
                return;
            }
        }

        // ── INSERT examenes ──────────────────────────────────────────
        try {
            $this->db->query("
                INSERT INTO examenes (titulo, descripcion, periodo_id, creado_por, fecha_inicio, fecha_fin, intentos_permitidos, activo)
                VALUES (:titulo, :descripcion, :periodo_id, :creado_por, :fecha_inicio, :fecha_fin, :intentos_permitidos, :activo)
            ");
            $this->db->bind(':titulo',              $titulo);
            $this->db->bind(':descripcion',         $descripcion ?: null);
            $this->db->bind(':periodo_id',          $periodoId);
            $this->db->bind(':creado_por',          $userId);
            $this->db->bind(':fecha_inicio',        $fechaInicio);
            $this->db->bind(':fecha_fin',           $fechaFin);
            $this->db->bind(':intentos_permitidos', $intentosPermitidos);
            $this->db->bind(':activo',              $publicar);
            $this->db->execute();

            $examenId = (int)$this->db->lastInsertId();

            // ── INSERT preguntas y opciones ──────────────────────────
            foreach ($preguntas as $idx => $preg) {
                $orden     = $idx + 1;
                $enunciado = trim($preg['enunciado']);
                $tipo      = in_array($preg['tipo'] ?? '', ['opcion_multiple', 'verdadero_falso'])
                             ? $preg['tipo']
                             : 'opcion_multiple';
                // [FIX-V-10] Solo tenía piso (min 1); actualizarPuntos() sí limita a 100
                // como techo — se iguala aquí para que la creación no permita más que la edición.
                $puntos    = max(1, min(100, (int)($preg['puntos'] ?? 1)));

                $this->db->query("
                    INSERT INTO examen_preguntas (examen_id, orden, enunciado, tipo, puntos)
                    VALUES (:examen_id, :orden, :enunciado, :tipo, :puntos)
                ");
                $this->db->bind(':examen_id', $examenId);
                $this->db->bind(':orden',     $orden);
                $this->db->bind(':enunciado', $enunciado);
                $this->db->bind(':tipo',      $tipo);
                $this->db->bind(':puntos',    $puntos);
                $this->db->execute();

                $preguntaId = (int)$this->db->lastInsertId();

                foreach ($preg['opciones'] as $opcion) {
                    $textoOpc  = trim($opcion['texto'] ?? '');
                    $esCorrecta = !empty($opcion['es_correcta']) ? 1 : 0;

                    if ($textoOpc === '') continue;

                    $this->db->query("
                        INSERT INTO examen_opciones (pregunta_id, texto, es_correcta)
                        VALUES (:pregunta_id, :texto, :es_correcta)
                    ");
                    $this->db->bind(':pregunta_id', $preguntaId);
                    $this->db->bind(':texto',       $textoOpc);
                    $this->db->bind(':es_correcta', $esCorrecta);
                    $this->db->execute();
                }
            }

            // ── Auditoría ────────────────────────────────────────────
            AuditModel::log('CREATE_EXAMEN', 'examenes', $examenId, [
                'titulo'  => $titulo,
                'activo'  => $publicar,
                'preguntas' => count($preguntas),
            ]);

            // ── Notificar pasantes si se publica de inmediato ────────
            if ($publicar === 1) {
                try {
                    $notif   = new NotificationModel($this->db);
                    $urlDest = URLROOT . '/pasante/misExamenes';
                    $this->db->query("
                        SELECT u.id 
                        FROM usuarios u
                        JOIN datos_pasante dp ON dp.usuario_id = u.id
                        WHERE u.rol_id = 3 AND u.estado = 'activo' AND dp.estado_pasantia = 'Activo'
                    ");
                    foreach (($this->db->resultSet() ?: []) as $pas) {
                        $notif->create(
                            (int)$pas->id,
                            'examen_publicado',
                            'Nuevo examen disponible',
                            "Se publicó el examen \"{$titulo}\". ¡Complétalo antes de que cierre!",
                            $urlDest
                        );
                    }

                    // Enviar alerta por correo institucional / móvil a pasantes
                    require_once APPROOT . '/lib/MailService.php';
                    MailService::notifyNewExam($titulo, $fechaFin, $urlDest);
                } catch (\Throwable $ne) {
                    error_log('[SGP-EXAMENES] guardar-notif: ' . $ne->getMessage());
                }
            }

            echo json_encode([
                'success'   => true,
                'examen_id' => $examenId,
                'redirect'  => URLROOT . '/examenes/ver/' . $examenId,
            ]);

        } catch (\Throwable $e) {
            error_log('[SGP-EXAMENES] guardar(): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno al guardar el examen']);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /examenes/ver/{id} — Detalle + resultados
    // ─────────────────────────────────────────────────────────────────
    public function ver($id = null): void
    {
        $rolId  = (int)(Session::get('role_id') ?? 0);
        $userId = (int)(Session::get('user_id') ?? 0);
        $id     = (int)($id ?? 0);

        if ($rolId === 3) {
            $this->redirect('/pasante/misExamenes');
            return;
        }

        if ($id <= 0) {
            $this->redirect('/examenes');
            return;
        }

        $examen = $this->_getExamen($id);
        if (!$examen) {
            Session::setFlash('error', 'Examen no encontrado.');
            $this->redirect('/examenes');
            return;
        }

        // Tutor: solo puede ver sus propios exámenes
        if ($rolId === 2 && (int)$examen->creado_por !== $userId) {
            Session::setFlash('error', 'No tienes permiso para ver este examen.');
            $this->redirect('/examenes');
            return;
        }

        $preguntas = $this->_getPreguntasConOpciones($id);

        // ── Intentos completados con datos del pasante ───────────────
        $this->db->query("
            SELECT ei.*,
                   CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS pasante_nombre,
                   u.cedula,
                   COALESCE(inst.nombre, dpa.institucion_procedencia, 'Sin institución') AS institucion_nombre,
                   TIMESTAMPDIFF(MINUTE, ei.iniciado_at, ei.enviado_at) AS minutos_usados
            FROM examen_intentos ei
            INNER JOIN usuarios u ON u.id = ei.pasante_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = ei.pasante_id
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id = ei.pasante_id
            LEFT JOIN instituciones inst ON inst.id = dpa.institucion_id
            WHERE ei.examen_id = :examen_id
              AND ei.enviado_at IS NOT NULL
            ORDER BY ei.porcentaje DESC
            LIMIT 100
        ");
        $this->db->bind(':examen_id', $id);
        $intentos = $this->db->resultSet();

        $this->view('examenes/ver', [
            'examen'    => $examen,
            'preguntas' => $preguntas,
            'intentos'  => $intentos,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /examenes/publicar — AJAX: toggle activo
    // ─────────────────────────────────────────────────────────────────
    public function publicar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $rolId  = (int)(Session::get('role_id') ?? 0);
        $userId = (int)(Session::get('user_id') ?? 0);

        if ($rolId === 3) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso']);
            return;
        }

        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);

        $id     = (int)($body['id'] ?? 0);
        $activo = (int)(!empty($body['activo']) ? 1 : 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        // Tutor: verificar propiedad
        if ($rolId === 2) {
            $examen = $this->_getExamen($id);
            if (!$examen || (int)$examen->creado_por !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permiso para modificar este examen']);
                return;
            }
        }

        try {
            // Verificar estado previo para notificar solo al habilitar (no al deshabilitar)
            $this->db->query("SELECT activo, titulo FROM examenes WHERE id = :id LIMIT 1");
            $this->db->bind(':id', $id);
            $prev = $this->db->single();
            $eraInactivo = $prev && (int)$prev->activo === 0;

            $this->db->query("UPDATE examenes SET activo = :activo WHERE id = :id");
            $this->db->bind(':activo', $activo);
            $this->db->bind(':id',     $id);
            $this->db->execute();

            AuditModel::log('TOGGLE_EXAMEN', 'examenes', $id, ['activo' => $activo]);

            // Notificar a todos los pasantes activos cuando el examen se publica por primera vez
            if ($activo === 1 && $eraInactivo && $prev) {
                try {
                    $notif    = new NotificationModel($this->db);
                    $titulo   = $prev->titulo;
                    $urlDest  = URLROOT . '/pasante/misExamenes';
                    $this->db->query("
                        SELECT u.id 
                        FROM usuarios u
                        JOIN datos_pasante dp ON dp.usuario_id = u.id
                        WHERE u.rol_id = 3 AND u.estado = 'activo' AND dp.estado_pasantia = 'Activo'
                    ");
                    foreach (($this->db->resultSet() ?: []) as $pas) {
                        $notif->create(
                            (int)$pas->id,
                            'examen_publicado',
                            'Nuevo examen disponible',
                            "Se publicó el examen \"{$titulo}\". ¡Complétalo antes de que cierre!",
                            $urlDest
                        );
                    }

                    // Enviar alerta por correo institucional / móvil a pasantes
                    require_once APPROOT . '/lib/MailService.php';
                    MailService::notifyNewExam($titulo, null, $urlDest);
                } catch (\Throwable $ne) {
                    error_log('[SGP-EXAMENES] publicar-notif: ' . $ne->getMessage());
                }
            }

            echo json_encode(['success' => true, 'activo' => $activo]);

        } catch (\Throwable $e) {
            error_log('[SGP-EXAMENES] publicar(): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /examenes/eliminar — AJAX: eliminar examen
    // ─────────────────────────────────────────────────────────────────
    public function eliminar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $rolId  = (int)(Session::get('role_id') ?? 0);
        $userId = (int)(Session::get('user_id') ?? 0);

        if ($rolId === 3) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso']);
            return;
        }

        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $id   = (int)($body['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        // Tutor: verificar propiedad
        if ($rolId === 2) {
            $examen = $this->_getExamen($id);
            if (!$examen || (int)$examen->creado_por !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permiso para eliminar este examen']);
                return;
            }
        }

        try {
            $this->db->query("DELETE FROM examenes WHERE id = :id");
            $this->db->bind(':id', $id);
            $this->db->execute();

            AuditModel::log('DELETE_EXAMEN', 'examenes', $id, []);

            echo json_encode(['success' => true]);

        } catch (\Throwable $e) {
            error_log('[SGP-EXAMENES] eliminar(): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el examen']);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /examenes/detalleIntento?intento_id=X — AJAX: respuestas del intento
    // ─────────────────────────────────────────────────────────────────
    public function detalleIntento(): void
    {
        header('Content-Type: application/json');

        $rolId = (int)(Session::get('role_id') ?? 0);
        if ($rolId === 3) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso']);
            return;
        }

        $intentoId = (int)($_GET['intento_id'] ?? 0);
        if ($intentoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        try {
            $this->db->query("
                SELECT ei.*,
                       CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS pasante_nombre,
                       e.titulo AS examen_titulo, e.creado_por AS examen_creado_por
                FROM examen_intentos ei
                INNER JOIN examenes e ON e.id = ei.examen_id
                INNER JOIN usuarios u ON u.id = ei.pasante_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = ei.pasante_id
                WHERE ei.id = :id
                LIMIT 1
            ");
            $this->db->bind(':id', $intentoId);
            $intento = $this->db->single();

            if (!$intento) {
                echo json_encode(['success' => false, 'message' => 'Intento no encontrado']);
                return;
            }

            // [FIX-AC-4] IDOR horizontal — un Tutor solo puede ver intentos de
            // exámenes que él mismo creó, igual que ya exige publicar()/eliminar().
            if ($rolId === 2 && (int)$intento->examen_creado_por !== (int)Session::get('user_id')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permiso para ver este intento']);
                return;
            }

            $preguntas = $this->_getPreguntasConOpciones((int)$intento->examen_id);

            $this->db->query("
                SELECT pregunta_id, opcion_id_elegida
                FROM examen_respuestas
                WHERE intento_id = :intento_id
            ");
            $this->db->bind(':intento_id', $intentoId);
            $respRows = $this->db->resultSet();

            $respMap = [];
            foreach ($respRows as $r) {
                $respMap[(int)$r->pregunta_id] = (int)$r->opcion_id_elegida;
            }

            $preguntasOut = [];
            foreach ($preguntas as $preg) {
                $opcionElegida  = $respMap[(int)$preg->id] ?? null;
                $opcionCorrecta = null;
                $opciones = [];
                foreach ($preg->opciones as $op) {
                    $esCor = (int)($op->es_correcta ?? 0) === 1;
                    if ($esCor) $opcionCorrecta = (int)$op->id;
                    $opciones[] = [
                        'id'          => (int)$op->id,
                        'texto'       => $op->texto ?? '',
                        'es_correcta' => $esCor,
                        'elegida'     => (int)$op->id === $opcionElegida,
                    ];
                }
                $preguntasOut[] = [
                    'id'         => (int)$preg->id,
                    'enunciado'  => $preg->enunciado ?? '',
                    'puntos'     => (int)($preg->puntos ?? 1),
                    'opciones'   => $opciones,
                    'acierto'    => $opcionElegida !== null && $opcionElegida === $opcionCorrecta,
                    'respondida' => $opcionElegida !== null,
                ];
            }

            echo json_encode([
                'success'   => true,
                'intento'   => [
                    'id'               => (int)$intento->id,
                    'pasante_nombre'   => $intento->pasante_nombre,
                    'examen_titulo'    => $intento->examen_titulo,
                    'puntaje_obtenido' => (float)($intento->puntaje_obtenido ?? 0),
                    'puntaje_maximo'   => (float)($intento->puntaje_maximo ?? 0),
                    'porcentaje'       => (float)($intento->porcentaje ?? 0),
                    'revisado_at'      => $intento->revisado_at ?? null,
                ],
                'preguntas' => $preguntasOut,
            ]);

        } catch (\Throwable $e) {
            error_log('[SGP-EXAMENES] detalleIntento(): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener el detalle']);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /examenes/marcarRevisado — AJAX: marcar intento como revisado
    // ─────────────────────────────────────────────────────────────────
    public function marcarRevisado(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $rolId = (int)(Session::get('role_id') ?? 0);
        if ($rolId === 3) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso']);
            return;
        }

        $body      = json_decode(file_get_contents('php://input'), true);
        $intentoId = (int)($body['intento_id'] ?? 0);

        if ($intentoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        try {
            $this->db->query("
                SELECT ei.pasante_id, ex.titulo AS examen_titulo, ei.porcentaje, ex.creado_por AS examen_creado_por
                FROM examen_intentos ei
                JOIN examenes ex ON ex.id = ei.examen_id
                WHERE ei.id = :id LIMIT 1
            ");
            $this->db->bind(':id', $intentoId);
            $intento = $this->db->single();

            if (!$intento) {
                echo json_encode(['success' => false, 'message' => 'Intento no encontrado']);
                return;
            }

            // [FIX-AC-4] IDOR horizontal
            if ($rolId === 2 && (int)$intento->examen_creado_por !== (int)Session::get('user_id')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permiso para revisar este intento']);
                return;
            }

            $this->db->query("UPDATE examen_intentos SET revisado_at = NOW() WHERE id = :id AND revisado_at IS NULL");
            $this->db->bind(':id', $intentoId);
            $this->db->execute();

            // Notificar al pasante
            $notif = new NotificationModel($this->db);
            $pct   = number_format((float)$intento->porcentaje, 1);
            $notif->create(
                (int)$intento->pasante_id,
                'examen_revisado',
                'Examen revisado',
                "Tu examen \"{$intento->examen_titulo}\" ha sido revisado. Obtuviste {$pct}%.",
                URLROOT . '/pasante/misExamenes'
            );

            AuditModel::log('REVISAR_EXAMEN', 'examen_intentos', $intentoId, []);

            echo json_encode(['success' => true]);

        } catch (\Throwable $e) {
            error_log('[SGP-EXAMENES] marcarRevisado(): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al marcar como revisado']);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /examenes/actualizarPuntos — AJAX: cambiar puntos de pregunta
    // ─────────────────────────────────────────────────────────────────
    public function actualizarPuntos(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $rolId = (int)(Session::get('role_id') ?? 0);
        if ($rolId === 3) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso']);
            return;
        }

        $body      = json_decode(file_get_contents('php://input'), true);
        $pregId    = (int)($body['pregunta_id'] ?? 0);
        $puntos    = (int)($body['puntos']      ?? 0);

        if ($pregId <= 0 || $puntos < 1 || $puntos > 100) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            return;
        }

        try {
            // [FIX-AC-4] IDOR horizontal — verificar propiedad del examen antes de tocar la pregunta
            if ($rolId === 2) {
                $this->db->query("
                    SELECT ex.creado_por FROM examen_preguntas ep
                    JOIN examenes ex ON ex.id = ep.examen_id
                    WHERE ep.id = :id LIMIT 1
                ");
                $this->db->bind(':id', $pregId);
                $preg = $this->db->single();
                if (!$preg || (int)$preg->creado_por !== (int)Session::get('user_id')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Sin permiso para modificar esta pregunta']);
                    return;
                }
            }

            $this->db->query("UPDATE examen_preguntas SET puntos = :pts WHERE id = :id");
            $this->db->bind(':pts', $puntos);
            $this->db->bind(':id',  $pregId);
            $this->db->execute();

            AuditModel::log('EDITAR_PUNTOS_PREGUNTA', 'examen_preguntas', $pregId, ['puntos' => $puntos]);

            echo json_encode(['success' => true]);

        } catch (\Throwable $e) {
            error_log('[SGP-EXAMENES] actualizarPuntos(): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /examenes/eliminarIntento — AJAX: borrar un intento del ranking
    // ─────────────────────────────────────────────────────────────────
    public function eliminarIntento(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $rolId = (int)(Session::get('role_id') ?? 0);
        if ($rolId === 3) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Sin permiso']);
            return;
        }

        $body      = json_decode(file_get_contents('php://input'), true);
        $intentoId = (int)($body['intento_id'] ?? 0);

        if ($intentoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        try {
            $this->db->query("
                SELECT ei.id, ex.creado_por AS examen_creado_por
                FROM examen_intentos ei
                JOIN examenes ex ON ex.id = ei.examen_id
                WHERE ei.id = :id LIMIT 1
            ");
            $this->db->bind(':id', $intentoId);
            $intentoChk = $this->db->single();
            if (!$intentoChk) {
                echo json_encode(['success' => false, 'message' => 'Intento no encontrado']);
                return;
            }

            // [FIX-AC-4] IDOR horizontal
            if ($rolId === 2 && (int)$intentoChk->examen_creado_por !== (int)Session::get('user_id')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Sin permiso para eliminar este intento']);
                return;
            }

            // Borrar respuestas (FK) antes del intento
            $this->db->query("DELETE FROM examen_respuestas WHERE intento_id = :id");
            $this->db->bind(':id', $intentoId);
            $this->db->execute();

            $this->db->query("DELETE FROM examen_intentos WHERE id = :id");
            $this->db->bind(':id', $intentoId);
            $this->db->execute();

            AuditModel::log('DELETE_INTENTO', 'examen_intentos', $intentoId, []);

            echo json_encode(['success' => true]);

        } catch (\Throwable $e) {
            error_log('[SGP-EXAMENES] eliminarIntento(): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el intento']);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Carga un examen con datos del creador y período.
     */
    private function _getExamen(int $id): ?object
    {
        $this->db->query("
            SELECT e.*,
                   CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS creador_nombre,
                   COALESCE(pa.nombre, 'Sin período') AS periodo_nombre
            FROM examenes e
            LEFT JOIN usuarios u ON u.id = e.creado_por
            LEFT JOIN datos_personales dp ON dp.usuario_id = e.creado_por
            LEFT JOIN periodos_academicos pa ON pa.id = e.periodo_id
            WHERE e.id = :id
            LIMIT 1
        ");
        $this->db->bind(':id', $id);
        $result = $this->db->single();
        return $result ?: null;
    }

    /**
     * Carga las preguntas de un examen con sus opciones anidadas.
     *
     * @return array Array de stdClass con propiedad ->opciones[]
     */
    private function _getPreguntasConOpciones(int $examenId): array
    {
        $this->db->query("
            SELECT * FROM examen_preguntas
            WHERE examen_id = :examen_id
            ORDER BY orden ASC
        ");
        $this->db->bind(':examen_id', $examenId);
        $preguntas = $this->db->resultSet();

        foreach ($preguntas as &$preg) {
            $this->db->query("
                SELECT * FROM examen_opciones
                WHERE pregunta_id = :pregunta_id
                ORDER BY id ASC
            ");
            $this->db->bind(':pregunta_id', (int)$preg->id);
            $preg->opciones = $this->db->resultSet();
        }
        unset($preg);

        return $preguntas;
    }
}
