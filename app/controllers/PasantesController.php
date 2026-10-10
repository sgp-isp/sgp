<?php
/**
 * PasantesController - Vista Admin: Gestión de Pasantes
 *
 * Maneja la URL /pasantes — exclusiva para Administradores.
 * No confundir con PasanteController (rol pasante, URL /pasante).
 */

declare(strict_types=1);

class PasantesController extends Controller
{
    private $pasanteModel;
    private Database $db;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();

        // Solo administradores
        if (!RoleMiddleware::hasAnyRole([0, 1])) {
            RoleMiddleware::redirectToRoleDashboard(Session::get('role_id'));
        }

        require_once APPROOT . '/models/PasanteModel.php';
        $this->pasanteModel = new PasanteModel();

        $config   = require APPROOT . '/config/config.php';
        $this->db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
    }

    /**
     * GET /pasantes
     * Lista todos los pasantes del sistema para el administrador.
     */
    public function index(): void
    {
        // Usar PeriodoService centralizado para contexto y período activo
        $periodoId       = PeriodoService::getIdContexto();
        $pasantes        = $this->pasanteModel->getAll($periodoId);
        $periodoActivo   = PeriodoService::getActivo();
        $totalHistoricos = PeriodoService::totalPasantesHistoricos();

        // Contadores para KPI cards
        $total      = count($pasantes);
        $enCurso    = count(array_filter($pasantes, fn($p) => ($p->estado_pasantia ?? '') === 'Activo'));
        $pendientes = count(array_filter($pasantes, fn($p) => in_array($p->estado_pasantia ?? '', ['Pendiente', 'Sin Asignar'])));
        $culminados = count(array_filter($pasantes, fn($p) => ($p->estado_pasantia ?? '') === 'Finalizado'));

        // Departamentos activos para el modal de asignación
        $this->db->query("SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre ASC");
        $departamentos = $this->db->resultSet();

        // [BUG-06 FIX] Períodos para el modal: solo Activo/Planificado (nunca Cerrado)
        // Versión anterior filtraba solo por tipo='Regular' sin validar el estado,
        // permitiendo asignar pasantes a períodos ya cerrados.
        $this->db->query("
            SELECT id, nombre, estado
            FROM periodos_academicos
            WHERE tipo = 'Regular' AND estado IN ('Activo','Planificado')
            ORDER BY fecha_inicio DESC
        ");
        $periodos = $this->db->resultSet();

        // Tutores activos para el modal
        $this->db->query("
            SELECT u.id, dp.nombres, dp.apellidos
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE u.rol_id = 2 AND u.estado = 'activo'
            ORDER BY IFNULL(dp.apellidos, u.correo) ASC
        ");
        $tutores = $this->db->resultSet();

        // Instituciones
        $this->db->query("SELECT id, nombre FROM instituciones ORDER BY nombre ASC");
        $instituciones = $this->db->resultSet();

        $this->view('pasantes/index', [
            'pasantes'        => $pasantes,
            'periodoActivo'   => $periodoActivo,
            'totalHistoricos' => $totalHistoricos,
            'total'           => $total,
            'enCurso'       => $enCurso,
            'pendientes'    => $pendientes,
            'culminados'    => $culminados,
            'departamentos' => $departamentos,
            'tutores'       => $tutores,
            'periodos'      => $periodos,
            'instituciones' => $instituciones,
        ]);
    }

    /**
     * AJAX: Consulta Rápida de Pasante (Bento Box UI)
     * GET /pasantes/consultaRapida?q=query
     */
    public function consultaRapida(): void
    {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');

        if (strlen($q) < 3) {
            echo json_encode(['success' => false, 'message' => 'Escribe al menos 3 caracteres.']);
            exit;
        }

        $this->db->query("
            SELECT
                u.id, u.cedula, u.correo, u.rol_id,
                dp.nombres, dp.apellidos, dp.telefono,
                dpa.institucion_procedencia,
                dpa.departamento_asignado_id, dpa.tutor_id,
                COALESCE(dpa.estado_pasantia, 'Sin Asignar') AS estado_pasantia,
                COALESCE((SELECT COUNT(*) * 8 FROM asistencias a2 WHERE a2.pasante_id = u.id AND a2.estado != 'Anulado'), 0) AS horas_acumuladas,
                COALESCE(NULLIF(dpa.horas_meta, 0), 1440)    AS horas_meta,
                dpa.fecha_inicio_pasantia                    AS fecha_inicio,
                dpa.fecha_fin_estimada,
                d.nombre                                     AS departamento_nombre,
                CONCAT(tup.nombres, ' ', tup.apellidos)      AS tutor_nombre,
                u.estado AS estado_usuario
            FROM usuarios u
            LEFT JOIN datos_personales   dp   ON dp.usuario_id  = u.id
            LEFT JOIN datos_pasante      dpa  ON dpa.usuario_id = u.id
            LEFT JOIN periodos_academicos pa  ON pa.id = dpa.periodo_id
            LEFT JOIN departamentos      d    ON d.id = dpa.departamento_asignado_id
            LEFT JOIN usuarios           tu   ON tu.id = dpa.tutor_id
            LEFT JOIN datos_personales   tup  ON tup.usuario_id = tu.id
            WHERE u.rol_id = 3 AND (u.cedula LIKE :q OR dp.nombres LIKE :q OR dp.apellidos LIKE :q)
            LIMIT 1
        ");
        $this->db->bind(':q', "%$q%");
        $result = $this->db->single();

        echo json_encode(['success' => !!$result, 'data' => $result]);
        exit;
    }

    /**
     * AJAX: Resetear PIN del Pasante para el Kiosco
     * POST /pasantes/resetPin
     */
    public function resetPin(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $pasanteId = (int)($_POST['pasante_id'] ?? 0);

        if (!$pasanteId) {
            echo json_encode(['success' => false, 'message' => 'ID de pasante inválido.']);
            exit;
        }

        // Generar un nuevo PIN aleatorio de 4 dígitos
        $nuevoPin = str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // SEGURIDAD: Hashear el PIN antes de almacenarlo (nunca guardar en texto plano)
        $pinHasheado = password_hash($nuevoPin, PASSWORD_BCRYPT);
        $this->db->query("UPDATE usuarios SET pin_asistencia = :pin WHERE id = :id AND rol_id = 3");
        $this->db->bind(':pin', $pinHasheado);
        $this->db->bind(':id', $pasanteId);
        
        if ($this->db->execute()) {
            $this->db->query("SELECT CONCAT(dp.nombres,' ',dp.apellidos) AS nombre FROM datos_personales dp WHERE dp.usuario_id = :pid LIMIT 1");
            $this->db->bind(':pid', $pasanteId);
            $nombrePas = trim($this->db->single()->nombre ?? "ID:$pasanteId");
            AuditModel::log('RESET_PIN', "PIN reseteado — $nombrePas");
            echo json_encode([
                'success' => true, 
                'message' => 'PIN reseteado correctamente.',
                'nuevo_pin' => $nuevoPin
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Error al resetear el PIN en la base de datos.'
            ]);
        }
        exit;
    }

    /**
     * AJAX: Obtener estado actual y transiciones permitidas
     * GET /pasantes/obtenerInfoEstado/{encryptedId}
     */
    public function obtenerInfoEstado($encryptedId = ''): void
    {
        header('Content-Type: application/json');

        $pasanteId = (int)UrlSecurity::decrypt($encryptedId);
        if (!$pasanteId) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $this->db->query("
            SELECT dpa.estado_pasantia, dpa.departamento_asignado_id,
                   dpa.tutor_id, dpa.fecha_inicio_pasantia
            FROM datos_pasante dpa
            WHERE dpa.usuario_id = :id
        ");
        $this->db->bind(':id', $pasanteId);
        $info = $this->db->single();

        $this->db->query("SELECT COUNT(*) AS total FROM asistencias
                          WHERE pasante_id = :id AND estado != 'Anulado'");
        $this->db->bind(':id', $pasanteId);
        $conteo = $this->db->single();

        $estadoActual    = $info->estado_pasantia ?? 'Sin Asignar';
        $tieneAsistencias = (int)($conteo->total ?? 0) > 0;

        $mapa = [
            'Sin Asignar' => ['Pendiente'],
            'Pendiente'   => ['Activo', 'Retirado'],
            'Activo'      => ['Finalizado', 'Retirado'],
            'Finalizado'  => [],
            'Retirado'    => [],
        ];

        $permitidas = $mapa[$estadoActual] ?? [];
        if ($tieneAsistencias) {
            $permitidas = array_values(array_filter($permitidas, fn($e) => $e !== 'Pendiente'));
        }

        echo json_encode([
            'success'          => true,
            'estado_actual'    => $estadoActual,
            'tiene_asistencias'=> $tieneAsistencias,
            'transiciones'     => $permitidas,
            'es_terminal'      => empty($permitidas),
        ]);
        exit;
    }

    /**
     * AJAX: Cambiar Estado de Pasantía
     * POST /pasantes/cambiarEstado
     */
    public function cambiarEstado(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $encryptedId = trim($_POST['pasante_id'] ?? '');
        $estado      = trim($_POST['estado']     ?? '');
        $motivo      = trim($_POST['motivo']     ?? '');

        $pasanteId = (int)UrlSecurity::decrypt($encryptedId);
        if (!$pasanteId) {
            echo json_encode(['success' => false, 'message' => 'ID de pasante inválido.']);
            exit;
        }

        $estadosPermitidos = ['Pendiente', 'Activo', 'Finalizado', 'Retirado'];
        if (!in_array($estado, $estadosPermitidos)) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido.']);
            exit;
        }

        // ── Obtener estado actual ─────────────────────────────────────
        $this->db->query("SELECT estado_pasantia, departamento_asignado_id,
                                 tutor_id, fecha_inicio_pasantia
                          FROM datos_pasante WHERE usuario_id = :id");
        $this->db->bind(':id', $pasanteId);
        $actual = $this->db->single();

        if (!$actual) {
            echo json_encode(['success' => false, 'message' => 'Pasante no encontrado.']);
            exit;
        }

        $estadoActual = $actual->estado_pasantia ?? 'Sin Asignar';

        // ── Estados terminales — no modificables ─────────────────────
        if (in_array($estadoActual, ['Finalizado', 'Retirado'])) {
            echo json_encode(['success' => false, 'message' => "El estado '{$estadoActual}' es definitivo y no puede modificarse."]);
            exit;
        }

        // ── Máquina de estados ────────────────────────────────────────
        $mapa = [
            'Sin Asignar' => ['Pendiente'],
            'Pendiente'   => ['Activo', 'Retirado'],
            'Activo'      => ['Finalizado', 'Retirado'],
        ];
        $permitidas = $mapa[$estadoActual] ?? [];

        // Si ya tiene asistencias registradas no puede volver a Pendiente
        $this->db->query("SELECT COUNT(*) AS total FROM asistencias
                          WHERE pasante_id = :id AND estado != 'Anulado'");
        $this->db->bind(':id', $pasanteId);
        $conteo = $this->db->single();
        if ((int)($conteo->total ?? 0) > 0) {
            $permitidas = array_values(array_filter($permitidas, fn($e) => $e !== 'Pendiente'));
        }

        if (!in_array($estado, $permitidas)) {
            echo json_encode(['success' => false, 'message' => "Transición no permitida: de '{$estadoActual}' a '{$estado}'."]);
            exit;
        }

        // ── Prerequisitos para Activo ─────────────────────────────────
        if ($estado === 'Activo') {
            if (!$actual->departamento_asignado_id || !$actual->fecha_inicio_pasantia) {
                echo json_encode(['success' => false, 'message' => 'No se puede activar: falta departamento asignado o fecha de inicio.']);
                exit;
            }
        }

        // ── Motivo obligatorio para Retirado ──────────────────────────
        if ($estado === 'Retirado' && empty($motivo)) {
            echo json_encode(['success' => false, 'message' => 'El motivo de retiro es obligatorio.']);
            exit;
        }

        // ── Ejecutar cambio ───────────────────────────────────────────
        try {
            $this->db->beginTransaction();

            if ($estado === 'Retirado') {
                $this->db->query("UPDATE datos_pasante
                                  SET estado_pasantia = :estado,
                                      observaciones   = :obs
                                  WHERE usuario_id = :id");
                $this->db->bind(':obs', 'Motivo de retiro: ' . $motivo);
            } else {
                $this->db->query("UPDATE datos_pasante SET estado_pasantia = :estado WHERE usuario_id = :id");
            }
            $this->db->bind(':estado', $estado);
            $this->db->bind(':id',     $pasanteId);
            $this->db->execute();

            $this->db->commit();

            require_once APPROOT . '/models/NotificationModel.php';
            $notificationModel = new NotificationModel($this->db);
            $notificationModel->create(
                $pasanteId, 'info',
                'Actualización de Estado',
                "El estado de tu pasantía ha cambiado a: {$estado}.",
                URLROOT . '/perfil'
            );

            AuditModel::log('CHANGE_PASANTE_STATUS',
                "Pasante ID $pasanteId: {$estadoActual} → {$estado}" .
                ($motivo ? " | Motivo: {$motivo}" : ''));

            echo json_encode(['success' => true, 'message' => "Estado actualizado a '{$estado}'."]);

        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error interno al actualizar el estado.']);
        }
        exit;
    }

    /**
     * AJAX: Obtener datos básicos de pasante
     * GET /pasantes/obtenerDatosPersonales/{id}
     */
    public function obtenerDatosPersonales($id = 0): void
    {
        header('Content-Type: application/json');
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }

        $this->db->query("
            SELECT dp.nombres, dp.apellidos, dp.telefono,
                   dpa.institucion_procedencia,
                   dpa.institucion_id,
                   dpa.departamento_asignado_id
            FROM datos_personales dp
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id = dp.usuario_id
            WHERE dp.usuario_id = :id
        ");
        $this->db->bind(':id', $id);
        $data = $this->db->single();

        $this->db->query("SELECT id, nombre FROM instituciones ORDER BY nombre ASC");
        $instituciones = $this->db->resultSet();

        if ($data) {
            $data->instituciones_lista = $instituciones;
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontraron datos']);
        }
        exit;
    }

    /**
     * AJAX: Actualizar datos básicos de pasante
     * POST /pasantes/actualizarDatos
     */
    public function actualizarDatos(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        // Requiere admin o tutor
        $roleId = (int)Session::get('role_id');
        if (!in_array($roleId, [0, 1, 2])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }

        $id           = filter_input(INPUT_POST, 'id',            FILTER_VALIDATE_INT);
        $nombres      = trim($_POST['nombres']   ?? '');
        $apellidos    = trim($_POST['apellidos'] ?? '');
        $telefono     = trim($_POST['telefono']  ?? '');
        $institucion  = filter_input(INPUT_POST, 'institucion_id',   FILTER_VALIDATE_INT);
        $departamento = filter_input(INPUT_POST, 'departamento_id',  FILTER_VALIDATE_INT);

        if (!$id || empty($nombres) || empty($apellidos)) {
            echo json_encode(['success' => false, 'message' => 'Nombres y apellidos son obligatorios.']);
            exit;
        }

        // [SGP-VAL-TEL] Antes solo lo filtraba el JS del formulario (fácil de saltar
        // con una petición directa). Mismo patrón que WizardController/PerfilController.
        if (!empty($telefono) && !preg_match('/^[0-9\-]{7,20}$/', $telefono)) {
            echo json_encode(['success' => false, 'message' => 'El teléfono solo debe contener números y guiones (7 a 20 caracteres).']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            // 1. Actualizar datos_personales
            $this->db->query("
                UPDATE datos_personales
                SET nombres = :nom, apellidos = :ape, telefono = :tel
                WHERE usuario_id = :id
            ");
            $this->db->bind(':nom', $nombres);
            $this->db->bind(':ape', $apellidos);
            $this->db->bind(':tel', $telefono);
            $this->db->bind(':id', $id);
            // Execute returns bool, if row count is 0 it's still true.
            $this->db->execute(); 

            // 2. Actualizar institucion y departamento en datos_pasante
            $this->db->query("
                UPDATE datos_pasante
                SET institucion_id            = COALESCE(:inst, institucion_id),
                    departamento_asignado_id  = COALESCE(:depto, departamento_asignado_id)
                WHERE usuario_id = :id
            ");
            $this->db->bind(':inst',  $institucion  ?: null);
            $this->db->bind(':depto', $departamento ?: null);
            $this->db->bind(':id',    $id);
            $this->db->execute();

            $this->db->commit();
            AuditModel::log('UPDATE_PASANTE_DATA', "Pasante ID $id");

            echo json_encode(['success' => true, 'message' => 'Datos actualizados correctamente.']);
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[SGP-PASANTES] Error en actualizarDatos: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno al actualizar datos.']);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /pasantes/eliminar/ID — eliminación permanente con cascade
    // ─────────────────────────────────────────────────────────────────
    public function eliminar(int $id = 0): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . URLROOT . '/pasantes');
            exit;
        }

        if (!Session::validateCsrfToken($_POST['_csrf'] ?? '')) {
            Session::setFlash('error', 'Token de seguridad inválido.');
            header('Location: ' . URLROOT . '/pasantes');
            exit;
        }

        if ($id <= 0) {
            Session::setFlash('error', 'ID de pasante inválido.');
            header('Location: ' . URLROOT . '/pasantes');
            exit;
        }

        $this->db->query("
            SELECT u.id, u.cedula, dp.nombres, dp.apellidos
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE u.id = :id AND u.rol_id = 3 LIMIT 1
        ");
        $this->db->bind(':id', $id);
        $pasante = $this->db->single();

        if (!$pasante) {
            Session::setFlash('error', 'El pasante no existe.');
            header('Location: ' . URLROOT . '/pasantes');
            exit;
        }

        $nombre = trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''));

        // Cascade delete
        foreach ([
            "DELETE FROM asistencias      WHERE pasante_id = :id",
            "DELETE FROM evaluaciones     WHERE pasante_id = :id",
            "DELETE FROM datos_pasante    WHERE usuario_id = :id",
            "DELETE FROM datos_personales WHERE usuario_id = :id",
            "DELETE FROM usuarios         WHERE id = :id AND rol_id = 3",
        ] as $sql) {
            $this->db->query($sql);
            $this->db->bind(':id', $id);
            $this->db->execute();
        }

        AuditModel::log('DELETE_PASANTE', 'usuarios', $id, ['nombre' => $nombre, 'tipo' => 'hard_delete']);
        Session::setFlash('success', "Pasante \"{$nombre}\" eliminado permanentemente del sistema.");
        header('Location: ' . URLROOT . '/pasantes');
        exit;
    }
}
