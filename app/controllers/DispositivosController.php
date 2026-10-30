<?php
/**
 * DispositivosController — Controlador para Vinculación Bancaria de Dispositivos y Login por Patrón
 * Sistema de Gestión de Pasantes (SGP) - ISPEB
 */

declare(strict_types=1);

require_once APPROOT . '/lib/DeviceSecurityHelper.php';

class DispositivosController extends Controller
{
    public function __construct()
    {
        Session::start();
    }

    /**
     * POST /dispositivos/solicitarOtp — Solicitar código OTP para vincular dispositivo
     */
    public function solicitarOtp(): void
    {
        header('Content-Type: application/json');

        $userId = (int)(Session::get('user_id') ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $deviceUuid = trim((string)($input['device_uuid'] ?? ''));
        $deviceName = trim((string)($input['device_name'] ?? ''));
        $metodo     = trim((string)($input['metodo'] ?? 'patron'));

        if (empty($deviceUuid)) {
            echo json_encode(['success' => false, 'message' => 'Identificador de dispositivo no recibido']);
            return;
        }

        $res = DeviceSecurityHelper::solicitarOtpVinculacion($userId, $deviceUuid, $deviceName, $metodo);
        echo json_encode($res);
    }

    /**
     * POST /dispositivos/verificarYVincular — Validar OTP y registrar dispositivo / patrón
     */
    public function verificarYVincular(): void
    {
        header('Content-Type: application/json');

        $userId = (int)(Session::get('user_id') ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $deviceUuid = trim((string)($input['device_uuid'] ?? ''));
        $otp        = trim((string)($input['otp'] ?? ''));
        $patron     = isset($input['patron']) ? trim((string)$input['patron']) : null;
        $metodo     = trim((string)($input['metodo'] ?? 'patron'));

        if (empty($deviceUuid) || empty($otp)) {
            echo json_encode(['success' => false, 'message' => 'Código de autorización o dispositivo faltante']);
            return;
        }

        $res = DeviceSecurityHelper::verificarOtpYVincular($userId, $deviceUuid, $otp, $patron, $metodo);
        echo json_encode($res);
    }

    /**
     * POST /dispositivos/loginPatron — Iniciar sesión con Patrón de Desbloqueo Gestual
     */
    public function loginPatron(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $identificador = trim((string)($input['identificador'] ?? ''));
        $patron        = trim((string)($input['patron'] ?? ''));
        $deviceUuid    = trim((string)($input['device_uuid'] ?? ''));

        if (empty($identificador) || empty($patron)) {
            echo json_encode(['success' => false, 'message' => 'Ingresa tu cédula o correo y dibuja tu patrón de seguridad']);
            return;
        }

        $res = DeviceSecurityHelper::loginConPatron($identificador, $patron, $deviceUuid);
        echo json_encode($res);
    }

    /**
     * POST /dispositivos/desvincular — Desvincular un dispositivo
     */
    public function desvincular(): void
    {
        header('Content-Type: application/json');

        $userId = (int)(Session::get('user_id') ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $dispositivoId = (int)($input['dispositivo_id'] ?? 0);

        if ($dispositivoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de dispositivo inválido']);
            return;
        }

        $res = DeviceSecurityHelper::desvincularDispositivo($userId, $dispositivoId);
        echo json_encode($res);
    }

    /**
     * GET /dispositivos/listar — Lista de dispositivos del usuario
     */
    public function listar(): void
    {
        header('Content-Type: application/json');

        $userId = (int)(Session::get('user_id') ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
            return;
        }

        $dispositivos = DeviceSecurityHelper::obtenerDispositivos($userId);
        echo json_encode(['success' => true, 'dispositivos' => $dispositivos]);
    }

    /**
     * POST /dispositivos/estadoDispositivo — Comprobar si un device_uuid está registrado y activo
     */
    public function estadoDispositivo(): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $deviceUuid = trim((string)($input['device_uuid'] ?? ''));

        if (empty($deviceUuid)) {
            echo json_encode(['success' => true, 'vinculado' => false]);
            return;
        }

        $db = Database::getInstance();
        $db->query("
            SELECT d.metodo_auth, d.estado, d.nombre_dispositivo,
                   u.cedula, u.correo
            FROM pasante_dispositivos d
            JOIN usuarios u ON u.id = d.usuario_id
            WHERE d.device_uuid = :uuid 
              AND d.estado = 'activo' 
              AND d.verificado_at IS NOT NULL
              AND u.estado = 'activo'
            LIMIT 1
        ");
        $db->bind(':uuid', $deviceUuid);
        $row = $db->single();

        if ($row) {
            echo json_encode([
                'success'     => true,
                'vinculado'   => true,
                'metodo'      => $row->metodo_auth ?: 'huella',
                'dispositivo' => $row->nombre_dispositivo,
                'cedula'      => $row->cedula
            ]);
            return;
        }

        echo json_encode(['success' => true, 'vinculado' => false]);
    }
}

