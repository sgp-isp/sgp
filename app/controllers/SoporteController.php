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

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $asunto    = trim((string)($input['asunto'] ?? ''));
        $categoria = trim((string)($input['categoria'] ?? 'Error del Sistema'));
        $prioridad = trim((string)($input['prioridad'] ?? 'Media'));
        $mensaje   = trim((string)($input['mensaje'] ?? $input['descripcion'] ?? ''));

        if (empty($asunto) || empty($mensaje)) {
            echo json_encode(['success' => false, 'message' => 'Por favor indica el asunto y la descripción del caso.']);
            exit;
        }

        $usuarioId = Session::get('user_id') ? (int)Session::get('user_id') : null;
        $nombre    = Session::get('user_name') ?? Session::get('nombres') ?? ($input['nombre'] ?? 'Usuario');
        $correo    = Session::get('user_email') ?? ($input['correo'] ?? 'sgp.ispeb@gmail.com');

        // Si tenemos usuario_id, enriquecer con datos_personales
        if ($usuarioId) {
            $this->db->query("
                SELECT u.email, CONCAT_WS(' ', dp.nombres, dp.apellidos) AS nombre_completo, dp.telefono
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :uid
            ");
            $this->db->bind(':uid', $usuarioId);
            $uRow = $this->db->single();
            if ($uRow) {
                if (!empty($uRow->email)) $correo = $uRow->email;
                if (!empty(trim((string)$uRow->nombre_completo))) $nombre = trim((string)$uRow->nombre_completo);
                $telefono = $uRow->telefono ?? null;
            }
        }

        $nuevoTicket = $this->soporteModel->crearTicket([
            'usuario_id' => $usuarioId,
            'nombre'     => $nombre,
            'correo'     => $correo,
            'telefono'   => $telefono ?? null,
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

        // Notificación por correo electrónico
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
                // Notificar a los administradores
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
            }
        } catch (\Throwable $e) {
            error_log('[SGP-SOPORTE] Error en notificaciones de mensaje: ' . $e->getMessage());
        }

        echo json_encode([
            'success'   => true,
            'message'   => 'Mensaje enviado correctamente.',
            'msg_id'    => $msgId,
            'created_at'=> date('d/m/Y H:i')
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

        // Notificar al usuario solicitante si el ticket fue resuelto o cerrado
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

                    if (!empty($respuesta)) {
                        require_once APPROOT . '/lib/MailService.php';
                        MailService::notifySupportReply($ticketId, $respuesta, $adminNombre);
                    }
                } catch (\Throwable $e) {}
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
}

