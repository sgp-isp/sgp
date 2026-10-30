<?php
/**
 * BiometriaController — Módulo de Autenticación Biométrica Móvil (WebAuthn / Passkeys)
 * Sistema de Gestión de Pasantes (SGP) — ISPEB
 *
 * Permite a los usuarios acceder de forma instantánea y segura usando huella dactilar,
 * Face ID o patrón de desbloqueo en sus teléfonos móviles.
 */

declare(strict_types=1);

require_once APPROOT . '/lib/WebAuthnHelper.php';
require_once APPROOT . '/lib/MailService.php';
require_once APPROOT . '/models/AuditModel.php';

class BiometriaController extends Controller
{
    private $db;

    public function __construct()
    {
        Session::start();
        $this->db = Database::getInstance();
    }

    /**
     * POST /biometria/registroDesafio — Genera el desafío para registrar huella/móvil
     */
    public function registroDesafio(): void
    {
        header('Content-Type: application/json');

        $userId = (int)(Session::get('user_id') ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
            return;
        }

        try {
            $this->db->query("
                SELECT u.id, u.correo AS email, u.cedula,
                       CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_completo
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :id LIMIT 1
            ");
            $this->db->bind(':id', $userId);
            $user = $this->db->single();

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                return;
            }

            $challenge = WebAuthnHelper::generateChallenge();
            Session::set('webauthn_reg_challenge', $challenge);

            $rpId = WebAuthnHelper::getRpId();
            $displayName = trim($user->nombre_completo) !== '' ? trim($user->nombre_completo) : ($user->email ?? 'Usuario SGP');

            echo json_encode([
                'success'   => true,
                'challenge' => $challenge,
                'rp' => [
                    'name' => 'SGP - Instituto de Salud Pública',
                    'id'   => $rpId
                ],
                'user' => [
                    'id'          => WebAuthnHelper::base64UrlEncode((string)$user->id),
                    'name'        => (string)($user->email ?? $user->cedula),
                    'displayName' => $displayName
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],   // ES256 (Android / iOS standard)
                    ['type' => 'public-key', 'alg' => -257]  // RS256
                ],
                'authenticatorSelection' => [
                    'authenticatorAttachment' => 'platform',
                    'userVerification'        => 'preferred',
                    'residentKey'             => 'preferred'
                ],
                'timeout' => 60000
            ]);

        } catch (\Throwable $e) {
            error_log('[SGP-BIOMETRIA] registroDesafio: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al preparar registro biométrico']);
        }
    }

    /**
     * POST /biometria/registrar — Guarda la credencial pública del dispositivo
     */
    public function registrar(): void
    {
        header('Content-Type: application/json');

        $userId = (int)(Session::get('user_id') ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data || empty($data['credential_id'])) {
            echo json_encode(['success' => false, 'message' => 'Datos de credencial incompletos']);
            return;
        }

        $expectedChallenge = Session::get('webauthn_reg_challenge');
        if (empty($expectedChallenge)) {
            echo json_encode(['success' => false, 'message' => 'Desafío no encontrado o expirado. Intente de nuevo.']);
            return;
        }

        $credentialId = trim($data['credential_id']);
        $publicKeyDer = $data['public_key_der'] ?? '';
        $clientDataJsonRaw = !empty($data['client_data_json']) ? WebAuthnHelper::base64UrlDecode($data['client_data_json']) : '';

        // Validar que el desafío en clientDataJSON coincida
        if (!empty($clientDataJsonRaw)) {
            $clientData = json_decode($clientDataJsonRaw, true);
            if (!isset($clientData['challenge']) || !hash_equals($expectedChallenge, $clientData['challenge'])) {
                echo json_encode(['success' => false, 'message' => 'Validación criptográfica fallida: desafío no coincide']);
                return;
            }
        }

        // Construir clave pública PEM
        $pemKey = '';
        if (!empty($publicKeyDer)) {
            $derBinary = WebAuthnHelper::base64UrlDecode($publicKeyDer);
            $pemKey = WebAuthnHelper::convertDerToPem($derBinary);
        } else {
            // Intentar extraer de authData/attestationObject
            $attestationBinary = !empty($data['attestation_object']) ? WebAuthnHelper::base64UrlDecode($data['attestation_object']) : '';
            $authData = WebAuthnHelper::extractAuthDataFromAttestation($attestationBinary);
            if ($authData) {
                $extracted = WebAuthnHelper::extractFromAuthData($authData);
                if ($extracted && !empty($extracted['public_key_pem'])) {
                    $pemKey = $extracted['public_key_pem'];
                }
            }
        }

        if (empty($pemKey)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo procesar la clave criptográfica del dispositivo']);
            return;
        }

        $deviceName = !empty($data['device_name']) ? trim($data['device_name']) : WebAuthnHelper::detectDeviceName();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';

        // A. Regla Antifraude: ¿Esta credencial biométrica ya está vinculada a OTRO usuario?
        $this->db->query("
            SELECT b.usuario_id, u.cedula, dp.nombres, dp.apellidos
            FROM usuarios_biometria b
            JOIN usuarios u ON u.id = b.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE b.credential_id = :cid AND b.usuario_id != :uid
            LIMIT 1
        ");
        $this->db->bind(':cid', $credentialId);
        $this->db->bind(':uid', $userId);
        $otroUsuario = $this->db->single();

        if ($otroUsuario) {
            $nombreDueno = trim(($otroUsuario->nombres ?? '') . ' ' . ($otroUsuario->apellidos ?? ''));
            AuditModel::log('INTENTO_SUPLANTACION_BIOMETRICA', 'usuarios_biometria', $userId, [
                'motivo'           => 'Intento de afiliar una huella/dispositivo ya registrado por otro usuario',
                'usuario_intento'  => $userId,
                'dueno_registrado' => $nombreDueno ?: $otroUsuario->cedula,
                'cedula_dueno'     => $otroUsuario->cedula,
                'dispositivo'      => $deviceName,
                'ip'               => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            echo json_encode([
                'success' => false,
                'message' => '🚫 Dispositivo compartido no permitido: Este lector/teléfono ya está afiliado a otro usuario en el sistema. Cada pasante o funcionario debe utilizar su propio equipo personal.'
            ]);
            return;
        }

        try {
            // Guardar o actualizar en base de datos
            $this->db->query("
                INSERT INTO usuarios_biometria (usuario_id, credential_id, public_key, counter, device_name, user_agent, creado_at)
                VALUES (:uid, :cid, :pub, 0, :dev, :ua, NOW())
                ON DUPLICATE KEY UPDATE public_key = :pub_up, device_name = :dev_up, user_agent = :ua_up
            ");
            $this->db->bind(':uid',    $userId);
            $this->db->bind(':cid',    $credentialId);
            $this->db->bind(':pub',    $pemKey);
            $this->db->bind(':dev',    $deviceName);
            $this->db->bind(':ua',     $userAgent);
            $this->db->bind(':pub_up', $pemKey);
            $this->db->bind(':dev_up', $deviceName);
            $this->db->bind(':ua_up',  $userAgent);
            $this->db->execute();

            Session::delete('webauthn_reg_challenge');

            AuditModel::log('ENROLL_BIOMETRIA', 'usuarios_biometria', $userId, [
                'dispositivo' => $deviceName,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            $response = json_encode([
                'success' => true,
                'message' => '¡Dispositivo biométrico (' . htmlspecialchars($deviceName) . ') registrado con éxito!'
            ]);

            // Responder inmediatamente al navegador para que la alerta salga al instante
            if (function_exists('fastcgi_finish_request')) {
                echo $response;
                fastcgi_finish_request();
            } else {
                header('Content-Type: application/json');
                header('Content-Length: ' . strlen($response));
                header('Connection: close');
                echo $response;
                @ob_end_flush();
                @ob_flush();
                @flush();
            }

            // Alerta de confirmación por correo en segundo plano
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                MailService::notifyBiometricEnrolled($userId, $deviceName, $ip);
            } catch (\Throwable $mErr) {
                error_log('[SGP-BIOMETRIA] Error enviando correo de afiliación: ' . $mErr->getMessage());
            }
            return;

        } catch (\Throwable $e) {
            error_log('[SGP-BIOMETRIA] registrar error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error de base de datos al guardar credencial']);
        }
    }

    /**
     * POST /biometria/loginDesafio — Genera el desafío de login biométrico
     */
    public function loginDesafio(): void
    {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true) ?: [];

            $identifier = trim($data['identifier'] ?? '');
            $challenge = WebAuthnHelper::generateChallenge();
            Session::set('webauthn_login_challenge', $challenge);

            $allowCredentials = [];

            // Si el usuario ingresó cédula o correo antes de pulsar huella, filtramos sus credenciales
            if ($identifier !== '') {
                $this->db->query("
                    SELECT b.credential_id
                    FROM usuarios_biometria b
                    JOIN usuarios u ON u.id = b.usuario_id
                    WHERE (u.cedula = :id1 OR u.correo = :id2) AND u.estado = 'activo'
                ");
                $this->db->bind(':id1', $identifier);
                $this->db->bind(':id2', $identifier);
                $rows = $this->db->resultSet() ?: [];
                foreach ($rows as $r) {
                    $allowCredentials[] = [
                        'type' => 'public-key',
                        'id'   => $r->credential_id
                    ];
                }
            }

            echo json_encode([
                'success'          => true,
                'challenge'        => $challenge,
                'rpId'             => WebAuthnHelper::getRpId(),
                'allowCredentials' => $allowCredentials,
                'userVerification' => 'preferred',
                'timeout'          => 60000
            ]);

        } catch (\Throwable $e) {
            error_log('[SGP-BIOMETRIA] loginDesafio: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al preparar inicio biométrico']);
        }
    }

    /**
     * POST /biometria/verificar — Verifica la firma y establece la sesión del usuario
     */
    public function verificar(): void
    {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            if (!$data || empty($data['credential_id'])) {
                echo json_encode(['success' => false, 'message' => 'Credencial biométrica no proporcionada']);
                return;
            }

            $expectedChallenge = Session::get('webauthn_login_challenge');
            if (empty($expectedChallenge)) {
                echo json_encode(['success' => false, 'message' => 'Desafío expirado. Por favor, intente nuevamente.']);
                return;
            }

            $credentialId = trim($data['credential_id']);
            $clientDataJsonRaw = WebAuthnHelper::base64UrlDecode($data['client_data_json'] ?? '');
            $authenticatorDataRaw = WebAuthnHelper::base64UrlDecode($data['authenticator_data'] ?? '');
            $signatureDerRaw = WebAuthnHelper::base64UrlDecode($data['signature_der'] ?? '');

            // Buscar la credencial en la base de datos
            $this->db->query("
                SELECT b.id AS bio_id, b.public_key, b.counter, b.device_name,
                       u.id AS user_id, u.correo AS email, u.cedula, u.estado, u.rol_id, u.requiere_cambio_clave,
                       dp.nombres, dp.apellidos
                FROM usuarios_biometria b
                JOIN usuarios u ON u.id = b.usuario_id
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE b.credential_id = :cid LIMIT 1
            ");
            $this->db->bind(':cid', $credentialId);
            $record = $this->db->single();

            if (!$record) {
                echo json_encode(['success' => false, 'message' => 'Dispositivo biométrico no reconocido en el sistema']);
                return;
            }

            if ($record->estado !== 'activo') {
                echo json_encode(['success' => false, 'message' => 'La cuenta institucional asociada se encuentra inactiva o suspendida']);
                return;
            }

            // Validar firma criptográfica
            $rpId = WebAuthnHelper::getRpId();
            $verification = WebAuthnHelper::verifyAssertion(
                $record->public_key,
                $clientDataJsonRaw,
                $authenticatorDataRaw,
                $signatureDerRaw,
                $expectedChallenge,
                $rpId
            );

            if (empty($verification['success'])) {
                echo json_encode(['success' => false, 'message' => $verification['message'] ?? 'Fallo de verificación de huella']);
                return;
            }

            // Actualizar contador y último uso
            $newCounter = (int)($verification['counter'] ?? ($record->counter + 1));
            $this->db->query("
                UPDATE usuarios_biometria 
                SET counter = :cnt, ultimo_uso_at = NOW() 
                WHERE id = :bid
            ");
            $this->db->bind(':cnt', $newCounter);
            $this->db->bind(':bid', $record->bio_id);
            $this->db->execute();

            Session::delete('webauthn_login_challenge');

            // ── Iniciar sesión del usuario (Idéntico a AuthController) ──
            Session::regenerate();
            Session::set('user_id', (int)$record->user_id);
            Session::set('role_id', (int)$record->rol_id);

            $fullName = trim(($record->nombres ?? '') . ' ' . ($record->apellidos ?? ''));
            if ($fullName === '') $fullName = $record->cedula ?? 'Usuario';
            Session::set('user_name', $fullName);
            Session::set('requiere_cambio_clave', (int)$record->requiere_cambio_clave);
            Session::set('last_activity', time());

            // Cargar permisos en sesión
            require_once APPROOT . '/models/PermisosModel.php';
            $permisosModel = new PermisosModel($this->db);
            $permisos = $permisosModel->getPermisosUsuario((int)$record->user_id, (int)$record->rol_id);
            Session::set('permisos', $permisos);
            Session::set('permisos_ts', time());

            // Verificar si perfil completado
            require_once APPROOT . '/models/UserModel.php';
            $userModel = new UserModel($this->db);
            $perfilCompletado = $userModel->verificarPerfilCompleto((int)$record->user_id);
            Session::set('perfil_completado', $perfilCompletado);

            // Bitácora
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            AuditModel::log('LOGIN_BIOMETRIA', 'usuarios', (int)$record->user_id, [
                'dispositivo' => $record->device_name,
                'ip'          => $ip
            ]);

            // Determinar URL de redirección
            $redirect = URLROOT . '/dashboard';
            if ((int)$record->requiere_cambio_clave === 1 || !$perfilCompletado) {
                $redirect = URLROOT . '/wizard/index';
            } else {
                switch ((int)$record->rol_id) {
                    case 0:
                        $redirect = URLROOT . '/superadmin';
                        break;
                    case 1:
                        $redirect = URLROOT . '/admin';
                        break;
                    case 2:
                        $redirect = URLROOT . '/tutor';
                        break;
                    case 3:
                        $redirect = URLROOT . '/pasante';
                        break;
                }
            }

            $response = json_encode([
                'success'   => true,
                'user_name' => $fullName,
                'cedula'    => (string)($record->cedula ?? ''),
                'redirect'  => $redirect
            ]);

            // Responder inmediatamente al navegador para login ultra-rápido
            if (function_exists('fastcgi_finish_request')) {
                echo $response;
                fastcgi_finish_request();
            } else {
                header('Content-Type: application/json');
                header('Content-Length: ' . strlen($response));
                header('Connection: close');
                echo $response;
                @ob_end_flush();
                @ob_flush();
                @flush();
            }

            // Alerta de seguridad por correo institucional en background
            try {
                MailService::notifySecurityLogin(
                    (int)$record->user_id,
                    'Huella Digital / Biometría Móvil',
                    (string)$record->device_name,
                    $ip
                );
            } catch (\Throwable $te) {
                error_log('[SGP-BIOMETRIA] mail-alert error: ' . $te->getMessage());
            }
            return;

        } catch (\Throwable $e) {
            error_log('[SGP-BIOMETRIA] verificar(): ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error de autenticación biométrica']);
        }
    }

    /**
     * GET /biometria/dispositivos — Lista de dispositivos biométricos del usuario
     */
    public function dispositivos(): void
    {
        header('Content-Type: application/json');

        $userId = (int)(Session::get('user_id') ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'devices' => []]);
            return;
        }

        try {
            $this->db->query("
                SELECT id, device_name, creado_at, ultimo_uso_at
                FROM usuarios_biometria
                WHERE usuario_id = :uid
                ORDER BY creado_at DESC
            ");
            $this->db->bind(':uid', $userId);
            $devices = $this->db->resultSet() ?: [];

            echo json_encode(['success' => true, 'devices' => $devices]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'devices' => []]);
        }
    }

    /**
     * POST /biometria/eliminar — Desvincula un dispositivo biométrico
     */
    public function eliminar(): void
    {
        header('Content-Type: application/json');

        $userId = (int)(Session::get('user_id') ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador no válido']);
            return;
        }

        try {
            // Obtener datos del dispositivo antes de eliminarlo
            $this->db->query("SELECT device_name FROM usuarios_biometria WHERE id = :id AND usuario_id = :uid LIMIT 1");
            $this->db->bind(':id',  $id);
            $this->db->bind(':uid', $userId);
            $devRow = $this->db->single();
            $deviceName = $devRow ? ($devRow->device_name ?? 'Dispositivo Móvil') : 'Dispositivo Móvil';

            $this->db->query("DELETE FROM usuarios_biometria WHERE id = :id AND usuario_id = :uid");
            $this->db->bind(':id',  $id);
            $this->db->bind(':uid', $userId);
            $this->db->execute();

            AuditModel::log('REMOVE_BIOMETRIA', 'usuarios_biometria', $id, ['usuario_id' => $userId, 'dispositivo' => $deviceName]);

            // Alerta por correo institucional
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                MailService::notifyBiometricRemoved($userId, $deviceName, $ip);
            } catch (\Throwable $mErr) {
                error_log('[SGP-BIOMETRIA] Error enviando correo de desafiliación: ' . $mErr->getMessage());
            }

            echo json_encode(['success' => true, 'message' => 'Dispositivo biométrico desvinculado con éxito']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al desvincular dispositivo']);
        }
    }
}
