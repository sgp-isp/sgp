<?php
declare(strict_types=1);

/**
 * SoporteController
 * Controlador para la Mesa de Ayuda, Gestión de Casos y Chat de Soporte Técnico.
 */
class SoporteController extends Controller
{
    private SoporteModel $soporteModel;
    private Database $db;

    public function __construct()
    {
        require_once APPROOT . '/models/SoporteModel.php';
        $this->db = Database::getInstance();
        $this->soporteModel = new SoporteModel($this->db);
    }

    /**
     * Vista Principal: Dirige a pasante o administración según el rol de sesión
     */
    public function index(): void
    {
        Session::start();
        AuthMiddleware::require();

        $rolId     = (int)(Session::get('role_id') ?? 99);
        $usuarioId = (int)(Session::get('user_id') ?? 0);

        if ($rolId === 3) {
            // Vista de Casos / Chat para Pasantes
            $tickets = $this->soporteModel->getTicketsUsuario($usuarioId);
            $selectedId = (int)($_GET['id'] ?? $_GET['ticket_id'] ?? 0);
            $selectedTicket = null;
            $mensajes = [];

            if ($selectedId > 0) {
                $ticketCandidate = $this->soporteModel->getTicketPorId($selectedId);
                if ($ticketCandidate && (int)$ticketCandidate->usuario_id === $usuarioId) {
                    $selectedTicket = $ticketCandidate;
                    $mensajes = $this->soporteModel->getMensajes($selectedId);
                }
            }

            if (!$selectedTicket && !empty($tickets)) {
                $selectedTicket = $tickets[0];
                $mensajes = $this->soporteModel->getMensajes((int)$selectedTicket->id);
            }

            $this->view('soporte/pasante', [
                'title'          => 'Mesa de Ayuda &bull; Mis Casos',
                'tickets'        => $tickets,
                'selectedTicket' => $selectedTicket,
                'mensajes'       => $mensajes
            ]);
        } else {
            // Vista de Mesa de Ayuda Administrativa (SuperAdmin, Admin, Tutor)
            $estado    = trim((string)($_GET['estado'] ?? 'Todos'));
            $prioridad = trim((string)($_GET['prioridad'] ?? 'Todas'));
            $busqueda  = trim((string)($_GET['q'] ?? ''));

            $kpis    = $this->soporteModel->getKpis();
            $tickets = $this->soporteModel->getTicketsAdmin($estado, $prioridad, $busqueda);

            $selectedId = (int)($_GET['id'] ?? $_GET['ticket_id'] ?? 0);
            $selectedTicket = null;
            $mensajes = [];

            if ($selectedId > 0) {
                $selectedTicket = $this->soporteModel->getTicketPorId($selectedId);
                if ($selectedTicket) {
                    $mensajes = $this->soporteModel->getMensajes($selectedId);
                }
            }

            if (!$selectedTicket && !empty($tickets)) {
                $selectedTicket = $this->soporteModel->getTicketPorId((int)$tickets[0]->id);
                $mensajes = $this->soporteModel->getMensajes((int)$tickets[0]->id);
            }

            $this->view('soporte/admin', [
                'title'          => 'Mesa de Ayuda &bull; Soporte Técnico',
                'kpis'           => $kpis,
                'tickets'        => $tickets,
                'selectedTicket' => $selectedTicket,
                'mensajes'       => $mensajes,
                'filtros'        => [
                    'estado'    => $estado,
                    'prioridad' => $prioridad,
                    'q'         => $busqueda
                ]
            ]);
        }
    }

    /**
     * Endpoint JSON para crear un nuevo caso / ticket
     */
    public function crear(): void
    {
        Session::start();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        try {
            $rawInput = file_get_contents('php://input');
            $input = (!empty($rawInput) ? json_decode($rawInput, true) : null) ?? $_POST;

            $asunto    = trim((string)($input['asunto'] ?? ''));
            $categoria = trim((string)($input['categoria'] ?? 'Error del Sistema'));
            $prioridad = trim((string)($input['prioridad'] ?? 'Media'));
            $mensaje   = trim((string)($input['mensaje'] ?? $input['descripcion'] ?? ''));

            if (empty($asunto) || empty($mensaje)) {
                echo json_encode(['success' => false, 'message' => 'Por favor indica el asunto y la descripción del caso.']);
                exit;
            }

            // Normalización estricta para ENUMs de base de datos
            $categoriasValidas = ['Error del Sistema', 'Problema de Acceso', 'Duda Operativa', 'Sugerencia', 'Otro'];
            if (!in_array($categoria, $categoriasValidas, true)) {
                if (mb_stripos($categoria, 'Acceso') !== false || mb_stripos($categoria, 'PIN') !== false) {
                    $categoria = 'Problema de Acceso';
                } elseif (mb_stripos($categoria, 'Duda') !== false || mb_stripos($categoria, 'Operat') !== false) {
                    $categoria = 'Duda Operativa';
                } elseif (mb_stripos($categoria, 'Suger') !== false) {
                    $categoria = 'Sugerencia';
                } else {
                    $categoria = 'Error del Sistema';
                }
            }

            $prioridadesValidas = ['Baja', 'Media', 'Alta', 'Urgente'];
            if (!in_array($prioridad, $prioridadesValidas, true)) {
                $prioridad = 'Media';
            }

            $usuarioId = Session::get('user_id') ? (int)Session::get('user_id') : null;
            $nombre    = Session::get('user_name') ?? Session::get('nombres') ?? ($input['nombre'] ?? 'Usuario');
            $correo    = Session::get('user_email') ?? ($input['correo'] ?? 'sgp.ispeb@gmail.com');
            $telefono  = null;

            // Si tenemos usuario_id, enriquecer con datos_personales
            if ($usuarioId) {
                try {
                    $this->db->query("
                        SELECT u.correo, CONCAT_WS(' ', dp.nombres, dp.apellidos) AS nombre_completo, dp.telefono
                        FROM usuarios u
                        LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                        WHERE u.id = :uid
                    ");
                    $this->db->bind(':uid', $usuarioId);
                    $uRow = $this->db->single();
                    if ($uRow) {
                        if (!empty($uRow->correo)) $correo = $uRow->correo;
                        if (!empty(trim((string)$uRow->nombre_completo))) $nombre = trim((string)$uRow->nombre_completo);
                        $telefono = $uRow->telefono ?? null;
                    }
                } catch (\Throwable $ue) {
                    error_log('[SGP-SOPORTE] Error consultando datos personales: ' . $ue->getMessage());
                }
            }

            $nuevoTicket = $this->soporteModel->crearTicket([
                'usuario_id' => $usuarioId,
                'nombre'     => $nombre,
                'correo'     => $correo,
                'telefono'   => $telefono,
                'categoria'  => $categoria,
                'prioridad'  => $prioridad,
                'asunto'     => $asunto,
                'mensaje'    => $mensaje
            ]);

            $ticketId = $nuevoTicket['id'];
            $codigo   = $nuevoTicket['codigo'];

            // Notificaciones internas para Administradores
            try {
                require_once APPROOT . '/models/NotificationModel.php';
                $notifModel = new NotificationModel($this->db);
                $this->db->query("SELECT id FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo'");
                $admins = $this->db->resultSet() ?: [];
                foreach ($admins as $adm) {
                    $notifModel->create(
                        (int)$adm->id,
                        'warning',
                        "Nuevo Ticket {$codigo}",
                        "{$nombre} reportó: {$asunto}",
                        URLROOT . "/soporte?id={$ticketId}"
                    );
                }
            } catch (\Throwable $e) {
                error_log('[SGP-SOPORTE] Error creando notificacion interna: ' . $e->getMessage());
            }

            // Notificación por correo electrónico (sin bloquear)
            try {
                require_once APPROOT . '/lib/MailService.php';
                MailService::notifyNewSupportTicket($ticketId);
            } catch (\Throwable $e) {
                error_log('[SGP-SOPORTE] Error enviando correo: ' . $e->getMessage());
            }

            echo json_encode([
                'success'   => true,
                'message'   => "Ticket {$codigo} generado exitosamente. Nuestro equipo técnico responderá a la brevedad.",
                'ticket_id' => $ticketId,
                'codigo'    => $codigo
            ]);
            exit;

        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE-CREAR-FATAL] ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo registrar el ticket: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Endpoint JSON para enviar un mensaje en el chat del ticket
     */
    public function mensaje(): void
    {
        Session::start();
        AuthMiddleware::require();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $ticketId = (int)($input['ticket_id'] ?? 0);
        $mensaje  = trim((string)($input['mensaje'] ?? ''));

        if ($ticketId <= 0 || empty($mensaje)) {
            echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío.']);
            exit;
        }

        $ticket = $this->soporteModel->getTicketPorId($ticketId);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket no encontrado.']);
            exit;
        }

        $usuarioId = (int)(Session::get('user_id') ?? 0);
        $rolId     = (int)(Session::get('role_id') ?? 99);
        $nombre    = Session::get('user_name') ?? Session::get('nombres') ?? 'Usuario';

        $remitenteTipo = in_array($rolId, [0, 1, 2], true) ? 'soporte' : 'usuario';

        // Si es pasante, verificar que el ticket le pertenezca
        if ($rolId === 3 && (int)$ticket->usuario_id !== $usuarioId) {
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para responder en este ticket.']);
            exit;
        }

        $msgId = $this->soporteModel->agregarMensaje(
            $ticketId,
            $usuarioId,
            $remitenteTipo,
            $nombre,
            $mensaje
        );

        // Notificaciones internas y por correo
        try {
            require_once APPROOT . '/models/NotificationModel.php';
            $notifModel = new NotificationModel($this->db);

            if ($remitenteTipo === 'soporte') {
                // Notificar al usuario solicitante
                if (!empty($ticket->usuario_id)) {
                    $notifModel->create(
                        (int)$ticket->usuario_id,
                        'info',
                        "Respuesta en caso {$ticket->codigo}",
                        "{$nombre} ha respondido: " . mb_substr($mensaje, 0, 80) . "...",
                        URLROOT . "/soporte?id={$ticketId}"
                    );
                }
                // Correo al usuario
                require_once APPROOT . '/lib/MailService.php';
                MailService::notifySupportReply($ticketId, $mensaje, $nombre);
            } else {
                // Notificar a los administradores en App
                $this->db->query("SELECT id FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo'");
                $admins = $this->db->resultSet() ?: [];
                foreach ($admins as $adm) {
                    $notifModel->create(
                        (int)$adm->id,
                        'info',
                        "Nuevo mensaje en {$ticket->codigo}",
                        "{$nombre}: " . mb_substr($mensaje, 0, 80) . "...",
                        URLROOT . "/soporte?id={$ticketId}"
                    );
                }
                // Correo a administradores
                require_once APPROOT . '/lib/MailService.php';
                MailService::notifySupportUserReply($ticketId, $mensaje, $nombre);
            }
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] Error en notificaciones de mensaje: ' . $e->getMessage());
        }

        echo json_encode([
            'success'          => true,
            'message'          => 'Mensaje enviado correctamente.',
            'msg_id'           => $msgId,
            'remitente_tipo'   => $remitenteTipo,
            'remitente_nombre' => $nombre,
            'created_at'       => date('d/m H:i'),
            'hora'             => date('H:i')
        ]);
        exit;
    }

    /**
     * Endpoint JSON para actualizar el estado o prioridad de un ticket
     */
    public function actualizarEstado(): void
    {
        Session::start();
        AuthMiddleware::require();
        header('Content-Type: application/json; charset=utf-8');

        $rolId = (int)(Session::get('role_id') ?? 99);
        if (!in_array($rolId, [0, 1, 2], true)) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $ticketId       = (int)($input['ticket_id'] ?? 0);
        $nuevoEstado    = trim((string)($input['estado'] ?? ''));
        $nuevaPrioridad = !empty($input['prioridad']) ? trim((string)$input['prioridad']) : null;
        $respuesta      = !empty($input['respuesta_soporte']) ? trim((string)$input['respuesta_soporte']) : null;

        $estadosValidos = ['Abierto', 'En Proceso', 'Resuelto', 'Cerrado'];
        if ($ticketId <= 0 || !in_array($nuevoEstado, $estadosValidos, true)) {
            echo json_encode(['success' => false, 'message' => 'Estado inválido.']);
            exit;
        }

        $adminId = (int)(Session::get('user_id') ?? 0);
        $adminNombre = Session::get('user_name') ?? 'Administrador';

        $ok = $this->soporteModel->actualizarEstado(
            $ticketId,
            $nuevoEstado,
            $nuevaPrioridad,
            $adminId,
            $respuesta
        );

        if ($ok && !empty($respuesta)) {
            // Guardar también como mensaje en el chat
            $this->soporteModel->agregarMensaje(
                $ticketId,
                $adminId,
                'soporte',
                $adminNombre,
                $respuesta
            );
        }

        // Notificar al usuario solicitante si el ticket fue actualizado
        if ($ok) {
            $ticket = $this->soporteModel->getTicketPorId($ticketId);
            if ($ticket && !empty($ticket->usuario_id)) {
                try {
                    require_once APPROOT . '/models/NotificationModel.php';
                    $notifModel = new NotificationModel($this->db);
                    $notifModel->create(
                        (int)$ticket->usuario_id,
                        $nuevoEstado === 'Resuelto' ? 'success' : 'info',
                        "Caso {$ticket->codigo}: Estado {$nuevoEstado}",
                        "Tu caso ha sido actualizado a estado: {$nuevoEstado} por {$adminNombre}.",
                        URLROOT . "/soporte?id={$ticketId}"
                    );

                    require_once APPROOT . '/lib/MailService.php';
                    MailService::notifySupportStatusChange($ticketId, $nuevoEstado, $adminNombre, $respuesta);
                } catch (\Throwable $e) {
                    error_log('[SGP-SOPORTE] Error notificando cambio de estado: ' . $e->getMessage());
                }
            }
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? "Ticket actualizado a estado '{$nuevoEstado}'." : 'Error al actualizar ticket.'
        ]);
        exit;
    }

    /**
     * Endpoint JSON para obtener datos y mensajes de un ticket en vivo
     */
    public function detalle(string $id = ''): void
    {
        Session::start();
        AuthMiddleware::require();
        header('Content-Type: application/json; charset=utf-8');

        $ticketId = (int)$id;
        if ($ticketId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            exit;
        }

        $ticket = $this->soporteModel->getTicketPorId($ticketId);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket no encontrado.']);
            exit;
        }

        $rolId     = (int)(Session::get('role_id') ?? 99);
        $usuarioId = (int)(Session::get('user_id') ?? 0);

        if ($rolId === 3 && (int)$ticket->usuario_id !== $usuarioId) {
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            exit;
        }

        $mensajes = $this->soporteModel->getMensajes($ticketId);

        echo json_encode([
            'success'  => true,
            'ticket'   => $ticket,
            'mensajes' => $mensajes
        ]);
        exit;
    }

    /**
     * Endpoint JSON para polling de mensajes en vivo estilo Steam / Messenger
     */
    public function mensajesNuevos(): void
    {
        Session::start();
        AuthMiddleware::require();
        header('Content-Type: application/json; charset=utf-8');

        $ticketId = (int)($_GET['ticket_id'] ?? 0);
        $afterId  = (int)($_GET['after_id'] ?? 0);

        if ($ticketId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ticket inválido.']);
            exit;
        }

        $ticket = $this->soporteModel->getTicketPorId($ticketId);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket no encontrado.']);
            exit;
        }

        $rolId     = (int)(Session::get('role_id') ?? 99);
        $usuarioId = (int)(Session::get('user_id') ?? 0);

        if ($rolId === 3 && (int)$ticket->usuario_id !== $usuarioId) {
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            exit;
        }

        $nuevos = $this->soporteModel->getMensajesNuevos($ticketId, $afterId);

        echo json_encode([
            'success'   => true,
            'estado'    => $ticket->estado,
            'prioridad' => $ticket->prioridad,
            'mensajes'  => $nuevos
        ]);
        exit;
    }

    /**
     * Endpoint público para identificar usuario por Cédula o Correo desde el Login
     */
    public function identificarGuest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $identificador = trim((string)($input['identificador'] ?? ''));

        if (empty($identificador)) {
            echo json_encode(['success' => false, 'message' => 'Por favor ingresa tu cédula o correo institucional.']);
            exit;
        }

        $usuario = $this->soporteModel->buscarUsuarioPorIdentificador($identificador);

        if (!$usuario) {
            echo json_encode([
                'success'    => true,
                'encontrado' => false,
                'mensaje'    => 'No encontramos una cuenta registrada con esa identificación. Puedes ingresar tus datos para recibir asistencia técnica.'
            ]);
            exit;
        }

        $ticketActivo = $this->soporteModel->getTicketActivoPorUsuario((int)$usuario->id);
        $token = null;
        if ($ticketActivo) {
            $token = $this->soporteModel->asignarGuestToken((int)$ticketActivo->id);
        }

        $nombreCompleto = trim($usuario->nombres . ' ' . $usuario->apellidos);

        echo json_encode([
            'success'    => true,
            'encontrado' => true,
            'usuario'    => [
                'id'           => (int)$usuario->id,
                'cedula'       => $usuario->cedula,
                'nombre'       => $nombreCompleto ?: $usuario->cedula,
                'correo'       => $usuario->correo,
                'telefono'     => $usuario->telefono ?? '',
                'departamento' => $usuario->departamento_nombre ?? 'General'
            ],
            'ticket_activo' => $ticketActivo ? [
                'id'          => (int)$ticketActivo->id,
                'codigo'      => $ticketActivo->codigo,
                'asunto'      => $ticketActivo->asunto,
                'estado'      => $ticketActivo->estado,
                'guest_token' => $token,
                'created_at'  => $ticketActivo->created_at
            ] : null
        ]);
        exit;
    }

    /**
     * Endpoint público para crear un caso de soporte desde el Login
     */
    public function crearGuest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $asunto    = trim((string)($input['asunto'] ?? ''));
            $categoria = trim((string)($input['categoria'] ?? 'Problema de Acceso'));
            $prioridad = trim((string)($input['prioridad'] ?? 'Media'));
            $mensaje   = trim((string)($input['mensaje'] ?? ''));
            $nombre    = trim((string)($input['nombre'] ?? ''));
            $correo    = trim((string)($input['correo'] ?? ''));
            $telefono  = trim((string)($input['telefono'] ?? ''));
            $usuarioId = !empty($input['usuario_id']) ? (int)$input['usuario_id'] : null;

            if (empty($nombre) || empty($correo) || empty($asunto) || empty($mensaje)) {
                echo json_encode(['success' => false, 'message' => 'Por favor completa todos los campos requeridos (nombre, correo, asunto y mensaje).']);
                exit;
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'El correo electrónico no tiene un formato válido.']);
                exit;
            }

            // Normalización de categoría
            $categoriasValidas = ['Error del Sistema', 'Problema de Acceso', 'Duda Operativa', 'Sugerencia', 'Otro'];
            if (!in_array($categoria, $categoriasValidas, true)) {
                $categoria = 'Problema de Acceso';
            }

            $guestToken = bin2hex(random_bytes(16));

            $nuevoTicket = $this->soporteModel->crearTicket([
                'usuario_id'  => $usuarioId,
                'nombre'      => $nombre,
                'correo'      => $correo,
                'telefono'    => !empty($telefono) ? $telefono : null,
                'categoria'   => $categoria,
                'prioridad'   => $prioridad,
                'asunto'      => $asunto,
                'mensaje'     => $mensaje,
                'guest_token' => $guestToken
            ]);

            $ticketId = $nuevoTicket['id'];
            $codigo   = $nuevoTicket['codigo'];

            // Notificación interna a administradores
            try {
                require_once APPROOT . '/models/NotificationModel.php';
                $notifModel = new NotificationModel($this->db);
                $this->db->query("SELECT id FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo'");
                $admins = $this->db->resultSet() ?: [];
                foreach ($admins as $adm) {
                    $notifModel->create(
                        (int)$adm->id,
                        'warning',
                        "Nuevo Ticket Login {$codigo}",
                        "{$nombre} reportó desde el login: {$asunto}",
                        URLROOT . "/soporte?id={$ticketId}"
                    );
                }
            } catch (\Throwable $ne) {
                error_log('[SGP-SOPORTE-GUEST] Error notificacion interna: ' . $ne->getMessage());
            }

            // Notificación por correo a administradores
            try {
                require_once APPROOT . '/lib/MailService.php';
                MailService::notifyNewSupportTicket($ticketId);
            } catch (\Throwable $me) {
                error_log('[SGP-SOPORTE-GUEST] Error enviando correo: ' . $me->getMessage());
            }

            echo json_encode([
                'success'     => true,
                'message'     => "Caso {$codigo} registrado exitosamente. Un técnico responderá en vivo en este chat.",
                'ticket_id'   => $ticketId,
                'codigo'      => $codigo,
                'guest_token' => $guestToken
            ]);
            exit;

        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE-GUEST-FATAL] ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Ocurrió un error al procesar el caso: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Endpoint público para cargar el historial de mensajes de un ticket con guest_token
     */
    public function mensajesGuest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $ticketId = (int)($_REQUEST['ticket_id'] ?? 0);
        $token    = trim((string)($_REQUEST['token'] ?? ''));

        if ($ticketId <= 0 || empty($token)) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
            exit;
        }

        $ticket = $this->soporteModel->getTicketPorIdYToken($ticketId, $token);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Sesión de soporte no válida o ticket no encontrado.']);
            exit;
        }

        $mensajes = $this->soporteModel->getMensajes($ticketId);

        echo json_encode([
            'success'  => true,
            'ticket'   => $ticket,
            'mensajes' => $mensajes
        ]);
        exit;
    }

    /**
     * Endpoint público para enviar mensaje desde el chat del Login usando guest_token
     */
    public function mensajeGuest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $input    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $ticketId = (int)($input['ticket_id'] ?? 0);
        $token    = trim((string)($input['token'] ?? ''));
        $mensaje  = trim((string)($input['mensaje'] ?? ''));

        if ($ticketId <= 0 || empty($token) || empty($mensaje)) {
            echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío.']);
            exit;
        }

        $ticket = $this->soporteModel->getTicketPorIdYToken($ticketId, $token);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Sesión no autorizada o ticket cerrado.']);
            exit;
        }

        $msgId = $this->soporteModel->agregarMensaje(
            $ticketId,
            !empty($ticket->usuario_id) ? (int)$ticket->usuario_id : null,
            'usuario',
            $ticket->nombre,
            $mensaje
        );

        // Notificaciones internas y correo a los administradores
        try {
            require_once APPROOT . '/models/NotificationModel.php';
            $notifModel = new NotificationModel($this->db);
            $this->db->query("SELECT id FROM usuarios WHERE rol_id IN (0, 1) AND estado = 'activo'");
            $admins = $this->db->resultSet() ?: [];
            foreach ($admins as $adm) {
                $notifModel->create(
                    (int)$adm->id,
                    'info',
                    "Mensaje en caso {$ticket->codigo}",
                    "{$ticket->nombre}: " . mb_substr($mensaje, 0, 80) . "...",
                    URLROOT . "/soporte?id={$ticketId}"
                );
            }
            require_once APPROOT . '/lib/MailService.php';
            MailService::notifySupportUserReply($ticketId, $mensaje, $ticket->nombre);
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE-GUEST] Error notificacion mensaje: ' . $e->getMessage());
        }

        echo json_encode([
            'success'    => true,
            'message_id' => $msgId
        ]);
        exit;
    }

    /**
     * Endpoint público para polling de mensajes en vivo en el chat del Login
     */
    public function nuevosMensajesGuest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $ticketId = (int)($_GET['ticket_id'] ?? 0);
        $token    = trim((string)($_GET['token'] ?? ''));
        $afterId  = (int)($_GET['after_id'] ?? 0);

        if ($ticketId <= 0 || empty($token)) {
            echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
            exit;
        }

        $ticket = $this->soporteModel->getTicketPorIdYToken($ticketId, $token);
        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Sesión inválida.']);
            exit;
        }

        $nuevos = $this->soporteModel->getMensajesNuevos($ticketId, $afterId);

        echo json_encode([
            'success'   => true,
            'estado'    => $ticket->estado,
            'prioridad' => $ticket->prioridad,
            'mensajes'  => $nuevos
        ]);
        exit;
    }
}

