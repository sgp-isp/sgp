<?php
/**
 * AsignacionesController — Gestión de Asignaciones de Pasantes
 *
 * Maneja la URL /asignaciones — exclusiva para Administradores.
 * Permite crear, editar, activar/finalizar y eliminar asignaciones
 * de pasantes a departamentos y tutores.
 *
 * ENDPOINTS:
 *   GET  /asignaciones           → index()    : listado completo
 *   POST /asignaciones/guardar   → guardar()  : crear nueva asignación (AJAX→JSON)
 *   POST /asignaciones/activar   → activar()  : cambiar estado a Activo (AJAX→JSON)
 *   POST /asignaciones/finalizar → finalizar(): cambiar estado a Finalizado (AJAX→JSON)
 *   POST /asignaciones/eliminar  → eliminar() : borrar registro (AJAX→JSON)
 */

declare(strict_types=1);

class AsignacionesController extends Controller
{
    private Database $db;
    private $asignacionModel;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();

        if (!RoleMiddleware::hasAnyRole([0, 1])) {
            RoleMiddleware::redirectToRoleDashboard(Session::get('role_id'));
        }

        $config    = require APPROOT . '/config/config.php';
        $this->db  = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        
        $this->asignacionModel = $this->model('Asignacion');
    }

    // ─────────────────────────────────────────────────────────
    // GET /asignaciones  — Listado
    // ─────────────────────────────────────────────────────────
    public function index(): void
    {
        // [BUG-01 FIX] Respetar el período en contexto actual (sesión o activo por defecto)
        $periodoId     = PeriodoService::getIdContexto();
        $asignaciones  = $this->asignacionModel->getAll($periodoId);

        // Período activo actual (para mostrarlo en la vista)
        $periodoActivo = PeriodoService::getActivo();

        // Departamentos para el select del modal
        $this->db->query("SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
        $departamentos = $this->db->resultSet();

        // Tutores y administradores activos para el select del modal
        $this->db->query("
            SELECT u.id, dp.nombres, dp.apellidos,
                   d.nombre AS departamento_nombre,
                   r.nombre AS rol_nombre
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN departamentos    d  ON d.id = u.departamento_id
            LEFT JOIN roles            r  ON r.id = u.rol_id
            WHERE u.rol_id IN (1, 2) AND u.estado = 'activo'
            ORDER BY u.rol_id ASC, IFNULL(dp.apellidos, u.correo) ASC
        ");
        $tutores = $this->db->resultSet();

        // [BUG-06 FIX] Períodos para el modal: solo Activo/Planificado (nunca Cerrado)
        $this->db->query("
            SELECT id, nombre, estado, fecha_inicio, fecha_fin, tipo
            FROM periodos_academicos
            WHERE estado IN ('Activo', 'Planificado')
            ORDER BY fecha_inicio DESC
        ");
        $periodos = $this->db->resultSet();

        // [BUG-05 FIX] KPIs corregidos — pendientes ≠ sinAsignar:
        //   'Sin Asignar' = nunca tuvo asignación
        //   'Pendiente'   = tiene departamento pero le falta tutor o fecha de inicio
        $activos     = count(array_filter($asignaciones, fn($a) => $a->estado_pasantia === 'Activo'));
        $pendientes  = count(array_filter($asignaciones, fn($a) => $a->estado_pasantia === 'Pendiente'));
        $sinAsignar  = count(array_filter($asignaciones, fn($a) => $a->estado_pasantia === 'Sin Asignar'));
        $finalizados = count(array_filter($asignaciones, fn($a) => $a->estado_pasantia === 'Finalizado'));

        $this->view('asignaciones/index', [
            'asignaciones'  => $asignaciones,
            'departamentos' => $departamentos,
            'tutores'       => $tutores,
            'periodos'      => $periodos,
            'periodoActivo' => $periodoActivo,
            'activos'       => $activos,
            'pendientes'    => $pendientes,
            'sinAsignar'    => $sinAsignar,
            'finalizados'   => $finalizados,
            'total'         => count($asignaciones),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // POST /asignaciones/guardar  — Crear/Actualizar asignación (AJAX)
    // ─────────────────────────────────────────────────────────
    public function guardar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $pasanteId      = (int)($_POST['pasante_id']      ?? 0);
        $departamentoId = (int)($_POST['departamento_id'] ?? 0);
        $tutorId        = (int)($_POST['tutor_id']        ?? 0);
        $horasMeta      = (int)($_POST['horas_meta']      ?? 1440);
        $fechaInicio    = trim($_POST['fecha_inicio']     ?? '');
        $observaciones  = trim($_POST['observaciones']    ?? '');
        $periodoId      = (int)($_POST['periodo_id']      ?? 0);

        if (!$pasanteId || !$departamentoId || !$fechaInicio || !$periodoId) {
            echo json_encode(['success' => false, 'message' => 'Pasante, departamento, periodo y fecha de inicio son obligatorios.']);
            exit;
        }

        // [FIX-V-5] Sin tope, un valor extremo (ej. 999999999) hace que el bucle de
        // cálculo de fecha_fin más abajo itere prácticamente sin fin, bloqueando el
        // request. Se acota a un rango razonable de una pasantía real.
        if ($horasMeta < 8 || $horasMeta > 4000) {
            echo json_encode(['success' => false, 'message' => 'La meta de horas debe estar entre 8 y 4000.']);
            exit;
        }

        // Calcular fecha fin: días hábiles L-V (feriados SÍ cuentan como hábil, suman 8h)
        try {
            $inicio    = new DateTime($fechaInicio);
            $diasTotal = (int)ceil($horasMeta / 8);

            $fin    = clone $inicio;
            $conteo = 0;
            while ($conteo < $diasTotal) {
                $fin->modify('+1 day');
                $dow = (int)$fin->format('N');
                if ($dow <= 5) $conteo++;
            }
            $fechaFin = $fin->format('Y-m-d');
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Fecha de inicio inválida.']);
            exit;
        }

        $tutorIdVal = $tutorId > 0 ? $tutorId : null;
        $autoRellenar = isset($_POST['auto_rellenar']) && $_POST['auto_rellenar'] == '1';

        if ($this->asignacionModel->guardar($pasanteId, $departamentoId, $tutorIdVal, $horasMeta, $fechaInicio, $fechaFin, $observaciones, $periodoId)) {
            
            // -------------------------------------------------------------
            // FASE 1: AUTO-RELLENADO PARA PASANTES TARDÍOS
            // -------------------------------------------------------------
            if ($autoRellenar) {
                // Se usa date() en vez de 'new DateTime(hoy)' o 'date('Y-m-d')' para poder parsear
                $fechaActual = date('Y-m-d');
                if ($fechaInicio < $fechaActual) {
                    try {
                        $inicioObj = new DateTime($fechaInicio);
                        $hoyObj    = new DateTime($fechaActual);

                        // Precargar feriados no laborables del rango para evitar N queries
                        $feriados = [];
                        $this->db->query("
                            SELECT fecha FROM dias_feriados
                            WHERE fecha BETWEEN :ini AND :fin AND es_laborable = 0
                        ");
                        $this->db->bind(':ini', $fechaInicio);
                        $this->db->bind(':fin', date('Y-m-d', strtotime('-1 day')));
                        foreach ($this->db->resultSet() as $f) {
                            $feriados[$f->fecha] = true;
                        }

                        // Límite de días según horas_meta (default 1440h = 180 días)
                        $metaHoras  = $horasMeta > 0 ? $horasMeta : 1440;
                        $maxDias    = (int)floor($metaHoras / 8);

                        // Contar registros válidos ya existentes para este pasante
                        $this->db->query("
                            SELECT COUNT(*) AS cnt FROM asistencias
                            WHERE pasante_id = :pid
                              AND fecha >= :inicio AND fecha < CURDATE()
                        ");
                        $this->db->bind(':pid',    $pasanteId);
                        $this->db->bind(':inicio', $fechaInicio);
                        $diasContados = (int)($this->db->single()->cnt ?? 0);

                        while ($inicioObj < $hoyObj) {
                            if ($diasContados >= $maxDias) break; // límite alcanzado

                            $diaSemana = (int)$inicioObj->format('N');
                            if ($diaSemana >= 1 && $diaSemana <= 5) { // Lunes a Viernes
                                $fechaLoop = $inicioObj->format('Y-m-d');
                                $esFeriado = isset($feriados[$fechaLoop]);

                                // Verificar que no exista registro previo
                                $this->db->query("SELECT id FROM asistencias WHERE pasante_id = :pid AND fecha = :fecha LIMIT 1");
                                $this->db->bind(':pid',   $pasanteId);
                                $this->db->bind(':fecha', $fechaLoop);

                                if (!$this->db->single()) {
                                    if ($esFeriado) {
                                        $this->db->query("
                                            INSERT INTO asistencias
                                                (pasante_id, fecha, hora_registro, estado, metodo, motivo_justificacion, es_auto_fill, horas_calculadas)
                                            VALUES (:pid, :fecha, '00:00:00', 'Justificado', 'AutoFill', 'Feriado no laborable', 1, 8.00)
                                        ");
                                    } else {
                                        $this->db->query("
                                            INSERT INTO asistencias
                                                (pasante_id, fecha, hora_registro, estado, metodo, motivo_justificacion, es_auto_fill, horas_calculadas)
                                            VALUES (:pid, :fecha, '08:00:00', 'Presente', 'AutoFill', 'Llenado automático retroactivo', 1, 8.00)
                                        ");
                                    }
                                    $this->db->bind(':pid',   $pasanteId);
                                    $this->db->bind(':fecha', $fechaLoop);
                                    $this->db->execute();
                                }
                                $diasContados++;
                            }
                            $inicioObj->modify('+1 day');
                        }
                    } catch (Exception $e) {
                        // Ignorar cualquier fallo del loop y continuar
                    }
                }
            }
            
            // --- Notificar al Pasante ---
            require_once APPROOT . '/models/NotificationModel.php';
            $notificationModel = new NotificationModel($this->db);
            $this->db->query("SELECT nombre FROM departamentos WHERE id = :did");
            $this->db->bind(':did', $departamentoId);
            $deptInfo = $this->db->single();
            $deptNombre = $deptInfo ? $deptInfo->nombre : 'un departamento';
            
            $notificationModel->create(
                $pasanteId,
                'info',
                'Nueva Asignación de Pasantía',
                "Has sido asignado a {$deptNombre}. Fecha de inicio: " . date('d/m/Y', strtotime($fechaInicio)) . ".",
                URLROOT . '/perfil'
            );

            // 📧 Notificar por correo al pasante y al tutor asignado
            try {
                require_once APPROOT . '/lib/MailService.php';
                MailService::notifyAssignment((int)$pasanteId, $deptNombre, $fechaInicio, $fechaFin, (int)$horasMeta, $tutorIdVal ? (int)$tutorIdVal : null);
            } catch (\Throwable $me) {
                error_log('[SGP-ASIG] Error notificando asignacion por correo: ' . $me->getMessage());
            }

            echo json_encode([
                'success'   => true,
                'message'   => 'Asignación guardada' . ($autoRellenar ? ' y rellenada' : '') . ' correctamente.',
                'fecha_fin' => $fechaFin,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la asignación en la base de datos.']);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // POST /asignaciones/activar  — Activar pasantía (AJAX)
    // ─────────────────────────────────────────────────────────
    public function activar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        if (!$pasanteId) {
            echo json_encode(['success' => false, 'message' => 'ID de pasante inválido.']);
            exit;
        }

        if ($this->asignacionModel->activar($pasanteId)) {
            echo json_encode(['success' => true, 'message' => 'Pasantía activada. El pasante puede registrar asistencia en el Kiosco.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo activar la pasantía.']);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // POST /asignaciones/finalizar  — Finalizar pasantía (AJAX)
    // ─────────────────────────────────────────────────────────
    public function finalizar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        if (!$pasanteId) {
            echo json_encode(['success' => false, 'message' => 'ID de pasante inválido.']);
            exit;
        }

        if ($this->asignacionModel->finalizar($pasanteId)) {
            echo json_encode(['success' => true, 'message' => 'Pasantía marcada como Finalizada.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo finalizar la pasantía.']);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // POST /asignaciones/eliminar  — Eliminar asignación (AJAX)
    // ─────────────────────────────────────────────────────────
    public function eliminar(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // [FIX-CS-1] Verificación CSRF
        $this->verifyCsrf();

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        if (!$pasanteId) {
            echo json_encode(['success' => false, 'message' => 'ID de pasante inválido.']);
            exit;
        }

        if ($this->asignacionModel->eliminar($pasanteId)) {
            echo json_encode(['success' => true, 'message' => 'Asignación eliminada. El pasante queda sin asignar.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la asignación.']);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // POST /asignaciones/buscarPasanteAjax  — (Buscador AJAX Modal)
    // ─────────────────────────────────────────────────────────
    public function buscarPasanteAjax(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([]);
            exit;
        }

        $query = trim($_POST['query'] ?? '');

        $sql = "
            SELECT u.id AS pasante_id, u.cedula, dp.nombres, dp.apellidos, 
                   COALESCE(inst.nombre, dpa.institucion_procedencia, 'No especificada') AS institucion_procedencia, 
                   COALESCE(NULLIF(dpa.estado_pasantia, ''), 'Sin Asignar') AS estado_pasantia
            FROM usuarios u
            JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN datos_pasante dpa ON u.id = dpa.usuario_id
            LEFT JOIN instituciones inst ON (dpa.institucion_id = inst.id OR dpa.institucion_procedencia = inst.id)
            WHERE u.rol_id = 3
              AND u.estado = 'activo'
              AND COALESCE(dpa.tipo_pasantia, 'Regular') = 'Regular'
              AND (
                  dpa.estado_pasantia IN ('Pendiente', 'Sin Asignar', '') 
                  OR dpa.estado_pasantia IS NULL 
                  OR dpa.departamento_asignado_id IS NULL
              )
        ";

        if ($query !== '') {
            $sql .= " AND (u.cedula LIKE :q OR dp.nombres LIKE :q OR dp.apellidos LIKE :q OR CONCAT(dp.nombres, ' ', dp.apellidos) LIKE :q)";
        }

        $sql .= " ORDER BY dp.apellidos ASC, dp.nombres ASC LIMIT 20";

        $this->db->query($sql);
        if ($query !== '') {
            $this->db->bind(':q', "%{$query}%");
        }
        
        echo json_encode($this->db->resultSet());
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // POST /asignaciones/getDetalleAjax  — (Detalle de Modal)
    // ─────────────────────────────────────────────────────────
    public function getDetalleAjax(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Método no permitido']);
            exit;
        }

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        if (!$pasanteId) {
            echo json_encode(['error' => 'ID de pasante inválido']);
            exit;
        }

        $detalle = $this->asignacionModel->getById($pasanteId);
        if ($detalle) {
            echo json_encode($detalle);
        } else {
            echo json_encode(['error' => 'No se encontraron detalles para este pasante']);
        }
        exit;
    }

    public function feriadosJson(): void
    {
        header('Content-Type: application/json');
        $this->db->query("SELECT fecha, nombre, tipo FROM dias_feriados WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) ORDER BY fecha ASC");
        echo json_encode($this->db->resultSet());
        exit;
    }

    // ─────────────────────────────────────────────────────────
    // POST /asignaciones/descartar — Descartar solicitud de pasante
    // ─────────────────────────────────────────────────────────
    public function descartar(): void
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $this->verifyCsrf();

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);
        $motivo    = trim($_POST['motivo'] ?? 'No figura en el listado oficial de pasantes de la institución');
        $accion    = trim($_POST['accion'] ?? 'bloquear'); // 'bloquear' o 'eliminar'

        if (!$pasanteId) {
            echo json_encode(['success' => false, 'message' => 'ID de pasante inválido.']);
            exit;
        }

        try {
            $this->db->query("
                SELECT u.id, u.correo, u.cedula, dp.nombres, dp.apellidos 
                FROM usuarios u 
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id 
                WHERE u.id = :id AND u.rol_id = 3
            ");
            $this->db->bind(':id', $pasanteId);
            $usuario = $this->db->single();

            if (!$usuario) {
                echo json_encode(['success' => false, 'message' => 'Pasante no encontrado.']);
                exit;
            }

            $nombreCompleto = trim(($usuario->nombres ?? '') . ' ' . ($usuario->apellidos ?? ''));

            if ($accion === 'eliminar') {
                // Eliminar completamente registros de prueba/spam
                $this->db->query("DELETE FROM asistencias WHERE pasante_id = :id");
                $this->db->bind(':id', $pasanteId);
                $this->db->execute();

                $this->db->query("DELETE FROM actividades WHERE pasante_id = :id");
                $this->db->bind(':id', $pasanteId);
                $this->db->execute();

                $this->db->query("DELETE FROM evaluaciones WHERE pasante_id = :id");
                $this->db->bind(':id', $pasanteId);
                $this->db->execute();

                $this->db->query("DELETE FROM datos_pasante WHERE usuario_id = :id");
                $this->db->bind(':id', $pasanteId);
                $this->db->execute();

                $this->db->query("DELETE FROM datos_personales WHERE usuario_id = :id");
                $this->db->bind(':id', $pasanteId);
                $this->db->execute();

                $this->db->query("DELETE FROM respuestas_seguridad WHERE usuario_id = :id");
                $this->db->bind(':id', $pasanteId);
                $this->db->execute();

                $this->db->query("DELETE FROM usuarios WHERE id = :id AND rol_id = 3");
                $this->db->bind(':id', $pasanteId);
                $this->db->execute();

                if (class_exists('AuditModel')) {
                    AuditModel::log('DESCARTE_PASANTE_ELIMINADO', "Se eliminó definitivamente el registro de {$nombreCompleto} (C.I: {$usuario->cedula}).");
                }

                echo json_encode(['success' => true, 'message' => "El registro de {$nombreCompleto} ha sido eliminado permanentemente."]);
                exit;
            }

            // Bloquear / Desactivar con motivo institucional para que no pueda entrar
            $this->db->query("UPDATE usuarios SET estado = 'inactivo' WHERE id = :id AND rol_id = 3");
            $this->db->bind(':id', $pasanteId);
            $this->db->execute();

            $this->db->query("
                INSERT INTO datos_pasante (usuario_id, estado_pasantia, tipo_pasantia, horas_meta, horas_acumuladas, observaciones, created_at, updated_at)
                VALUES (:uid, 'Retirado', 'Regular', 1440, 0, :obs, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    estado_pasantia = 'Retirado',
                    observaciones = :obs_upd,
                    updated_at = NOW()
            ");
            $obsTexto = 'Registro descartado: ' . $motivo;
            $this->db->bind(':uid', $pasanteId);
            $this->db->bind(':obs', $obsTexto);
            $this->db->bind(':obs_upd', $obsTexto);
            $this->db->execute();

            if (class_exists('AuditModel')) {
                AuditModel::log('DESCARTE_PASANTE_BLOQUEADO', "Se descartó y bloqueó el registro de {$nombreCompleto} (C.I: {$usuario->cedula}). Motivo: {$motivo}");
            }

            echo json_encode(['success' => true, 'message' => "La solicitud de {$nombreCompleto} ha sido descartada y su acceso ha sido bloqueado."]);
            exit;

        } catch (\Throwable $e) {
            error_log('[SGP-DESCARTAR-ERROR] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al procesar el descarte: ' . $e->getMessage()]);
            exit;
        }
    }
}
