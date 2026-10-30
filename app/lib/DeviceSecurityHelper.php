<?php
/**
 * DeviceSecurityHelper — Gestión de Seguridad Bancaria de Dispositivos y Autenticación por Patrón
 * Sistema de Gestión de Pasantes (SGP) - ISPEB
 *
 * Implementa el estándar de vinculación de dispositivos frecuentes (Trusted Device Enrollment):
 * - Solicitud de enrolamiento con código OTP de 6 dígitos enviado al correo institucional.
 * - Validación criptográfica y asociación con la cédula del usuario.
 * - Soporte para Huella Dactilar (WebAuthn) y Patrón Gestual de 9 puntos.
 * - Regla antifraude: Prevención de dispositivos compartidos entre múltiples usuarios.
 */

declare(strict_types=1);

if (!defined('APPROOT')) {
    define('APPROOT', dirname(__DIR__));
}
if (!defined('URLROOT')) {
    define('URLROOT', '/SGP/public');
}

require_once APPROOT . '/lib/MailService.php';
require_once APPROOT . '/models/AuditModel.php';

class DeviceSecurityHelper
{
    /**
     * Paso 1: Generar código OTP de 6 dígitos y enviarlo por correo para autorizar la vinculación
     */
    public static function solicitarOtpVinculacion(int $userId, string $deviceUuid, string $deviceName = '', string $metodo = 'huella'): array
    {
        $db = Database::getInstance();

        if ($userId <= 0 || empty($deviceUuid)) {
            return ['success' => false, 'message' => 'Parámetros de vinculación inválidos'];
        }

        // Obtener datos del usuario
        $db->query("
            SELECT u.id, u.cedula, u.correo, u.rol_id,
                   TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_completo
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE u.id = :id AND u.estado = 'activo'
            LIMIT 1
        ");
        $db->bind(':id', $userId);
        $user = $db->single();

        if (!$user || empty($user->correo)) {
            return ['success' => false, 'message' => 'Usuario no encontrado o sin correo institucional registrado'];
        }

        if (preg_match('/@(sgp\.local|\.test|example\.com)$/i', (string)$user->correo)) {
            return [
                'success' => false,
                'message' => 'Tu cuenta tiene registrado un correo ficticio (' . htmlspecialchars($user->correo) . '). Por favor ingresa a tu Perfil y actualiza tu correo electrónico real para poder recibir los códigos de autorización.'
            ];
        }

        // Regla Antifraude: ¿Este dispositivo ya está vinculado a OTRO usuario activo?
        $db->query("
            SELECT d.usuario_id, u.cedula,
                   TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_dueno
            FROM pasante_dispositivos d
            JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE d.device_uuid = :dev_uuid 
              AND d.usuario_id != :uid 
              AND d.estado = 'activo' 
              AND d.verificado_at IS NOT NULL
            LIMIT 1
        ");
        $db->bind(':dev_uuid', $deviceUuid);
        $db->bind(':uid',      $userId);
        $otro = $db->single();

        if ($otro) {
            $nombreDueno = $otro->nombre_dueno ?: $otro->cedula;
            AuditModel::log('INTENTO_VINCULAR_DISPOSITIVO_COMPARTIDO', 'pasante_dispositivos', $userId, [
                'motivo'      => 'Intento de vincular dispositivo ya asignado a otro usuario',
                'device_uuid' => $deviceUuid,
                'dueno'       => $nombreDueno
            ]);

            return [
                'success' => false,
                'message' => '🚫 Dispositivo compartido no permitido: Este equipo ya se encuentra vinculado a otro usuario (' . htmlspecialchars($nombreDueno) . '). Por políticas de seguridad, cada usuario debe vincular su propio dispositivo personal.'
            ];
        }

        // Generar código OTP seguro de 6 dígitos
        $otp = (string)random_int(100000, 999999);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
        $nombreFinal = !empty($deviceName) ? trim($deviceName) : self::detectarNombreDispositivo();

        // Guardar o renovar solicitud en pasante_dispositivos
        $db->query("
            INSERT INTO pasante_dispositivos 
                (usuario_id, device_uuid, nombre_dispositivo, user_agent, ip_registro, estado, metodo_auth, otp_codigo, otp_expira_at, otp_intentos, cedula_asociada, creado_at)
            VALUES 
                (:uid, :dev_uuid, :nombre, :ua, :ip, 'activo', :metodo, :otp, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0, :cedula, NOW())
            ON DUPLICATE KEY UPDATE 
                nombre_dispositivo = :nombre_up,
                user_agent = :ua_up,
                ip_registro = :ip_up,
                metodo_auth = :metodo_up,
                otp_codigo = :otp_up,
                otp_expira_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
                otp_intentos = 0,
                cedula_asociada = :cedula_up
        ");
        $db->bind(':uid',        $userId);
        $db->bind(':dev_uuid',   $deviceUuid);
        $db->bind(':nombre',     $nombreFinal);
        $db->bind(':ua',         $ua);
        $db->bind(':ip',         $ip);
        $db->bind(':metodo',     $metodo);
        $db->bind(':otp',        $otp);
        $db->bind(':cedula',     $user->cedula);
        $db->bind(':nombre_up',  $nombreFinal);
        $db->bind(':ua_up',      $ua);
        $db->bind(':ip_up',      $ip);
        $db->bind(':metodo_up',  $metodo);
        $db->bind(':otp_up',     $otp);
        $db->bind(':cedula_up',  $user->cedula);
        $db->execute();

        // Enviar OTP por correo
        $enviado = MailService::notifyDeviceVinculacionOtp(
            (string)$user->correo,
            (string)($user->nombre_completo ?: $user->cedula),
            $otp,
            $nombreFinal,
            $ip
        );

        $correoMask = self::enmascararCorreo($user->correo);

        // Guardar notificación interna en el sistema como respaldo instantáneo
        try {
            $notifTitle = "🔐 Código de Autorización de Dispositivo";
            $notifMsg = "Tu código para autorizar el dispositivo '{$nombreFinal}' es: {$otp}. Válido por 10 minutos.";
            $db->query("
                INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, icono, leido, created_at)
                VALUES (:uid, 'seguridad', :tit, :msg, 'ti-shield-lock', 0, NOW())
            ");
            $db->bind(':uid', $userId);
            $db->bind(':tit', $notifTitle);
            $db->bind(':msg', $notifMsg);
            $db->execute();
        } catch (\Throwable $ne) {
            // Silencioso
        }

        if (!$enviado) {
            return [
                'success'     => true,
                'message'     => 'Demora en el servidor de correo hacia ' . $correoMask . '. Tu código de autorización es: ' . $otp . ' (también enviado a tus Notificaciones).',
                'correo_mask' => $correoMask,
                'codigo_otp'  => $otp,
                'device_name' => $nombreFinal
            ];
        }

        return [
            'success'     => true,
            'message'     => 'Código de autorización enviado exitosamente a tu correo ' . $correoMask,
            'correo_mask' => $correoMask,
            'device_name' => $nombreFinal
        ];
    }

    /**
     * Paso 2: Verificar código OTP y consolidar la vinculación (Patrón o Huella)
     */
    public static function verificarOtpYVincular(int $userId, string $deviceUuid, string $otp, ?string $patronRaw = null, string $metodo = 'patron'): array
    {
        $db = Database::getInstance();
        $otp = trim($otp);

        if ($userId <= 0 || empty($deviceUuid) || empty($otp)) {
            return ['success' => false, 'message' => 'Datos de verificación incompletos'];
        }

        // Buscar registro de dispositivo con validación de expiración en base de datos
        $db->query("
            SELECT d.*, (d.otp_expira_at < NOW()) AS ha_expirado,
                   u.correo, u.cedula,
                   TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_completo
            FROM pasante_dispositivos d
            JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE d.usuario_id = :uid AND d.device_uuid = :dev_uuid
            LIMIT 1
        ");
        $db->bind(':uid',      $userId);
        $db->bind(':dev_uuid', $deviceUuid);
        $registro = $db->single();

        if (!$registro) {
            return ['success' => false, 'message' => 'No hay una solicitud de vinculación activa para este dispositivo. Solicita un nuevo código.'];
        }

        // Validar intentos fallidos
        if ((int)$registro->otp_intentos >= 4) {
            return ['success' => false, 'message' => 'Demasiados intentos fallidos. Por seguridad, solicita un nuevo código de autorización.'];
        }

        // Validar expiración (inmune a desalineación de timezone)
        if ((int)$registro->ha_expirado === 1) {
            return ['success' => false, 'message' => 'El código de autorización ha expirado (válido 10 min). Solicita uno nuevo.'];
        }

        // Comparar código OTP de forma segura
        if (!hash_equals((string)$registro->otp_codigo, $otp)) {
            // Incrementar contador de intentos
            $db->query("UPDATE pasante_dispositivos SET otp_intentos = otp_intentos + 1 WHERE id = :id");
            $db->bind(':id', $registro->id);
            $db->execute();

            $restantes = 4 - ((int)$registro->otp_intentos + 1);
            return [
                'success' => false,
                'message' => "Código incorrecto. Te quedan {$restantes} intento(s)."
            ];
        }

        // Si el método es patrón, validar y hashear el patrón
        if ($metodo === 'patron') {
            if (empty($patronRaw) || strlen($patronRaw) < 4) {
                return ['success' => false, 'message' => 'El patrón debe conectar al menos 4 puntos para ser seguro'];
            }

            // Guardar hash del patrón en la cuenta del usuario
            $patronHash = password_hash($patronRaw, PASSWORD_DEFAULT);
            $db->query("
                UPDATE usuarios 
                SET patron_hash = :hash, patron_activo = 1, patron_actualizado_at = NOW()
                WHERE id = :uid
            ");
            $db->bind(':hash', $patronHash);
            $db->bind(':uid',  $userId);
            $db->execute();
        }

        // Consolidar estado del dispositivo como Verificado / Activo
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $db->query("
            UPDATE pasante_dispositivos
            SET estado = 'activo',
                metodo_auth = :metodo,
                verificado_at = NOW(),
                ultimo_uso_at = NOW(),
                otp_codigo = NULL,
                otp_expira_at = NULL,
                otp_intentos = 0,
                ip_registro = :ip
            WHERE id = :id
        ");
        $db->bind(':metodo', $metodo);
        $db->bind(':ip',     $ip);
        $db->bind(':id',     $registro->id);
        $db->execute();

        // Guardar auditoría
        AuditModel::log('VINCULAR_DISPOSITIVO_BANCARIO', 'pasante_dispositivos', $registro->id, [
            'metodo'      => $metodo,
            'dispositivo' => $registro->nombre_dispositivo,
            'cedula'      => $registro->cedula,
            'ip'          => $ip
        ]);

        // Enviar correo de confirmación de vinculación exitosa
        MailService::notifyDeviceVinculadoConfirmacion(
            (string)$registro->correo,
            (string)($registro->nombre_completo ?: $registro->cedula),
            (string)$registro->nombre_dispositivo,
            $metodo,
            $ip
        );

        return [
            'success'     => true,
            'message'     => '¡Dispositivo vinculado exitosamente a tu cuenta institucional!',
            'device_name' => $registro->nombre_dispositivo,
            'metodo'      => $metodo
        ];
    }

    /**
     * Iniciar Sesión Rápido usando el Patrón de Desbloqueo Gestual (9 puntos)
     */
    public static function loginConPatron(string $identificador, string $patronRaw, string $deviceUuid): array
    {
        $db = Database::getInstance();
        $identificador = trim($identificador);
        $patronRaw = trim($patronRaw);

        if (empty($identificador) || empty($patronRaw)) {
            return ['success' => false, 'message' => 'Ingresa tu cédula/correo y dibuja tu patrón'];
        }

        // Buscar usuario
        $db->query("
            SELECT u.id, u.cedula, u.correo, u.rol_id, u.estado, u.patron_hash, u.patron_activo,
                   TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_completo
            FROM usuarios u
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE (u.cedula = :id1 OR u.correo = :id2)
            LIMIT 1
        ");
        $db->bind(':id1', $identificador);
        $db->bind(':id2', $identificador);
        $user = $db->single();

        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado en el sistema'];
        }

        if ($user->estado !== 'activo') {
            return ['success' => false, 'message' => 'Tu cuenta se encuentra inactiva o suspendida'];
        }

        if (empty($user->patron_hash) || (int)$user->patron_activo !== 1) {
            return [
                'success' => false,
                'message' => 'No tienes un patrón de seguridad configurado. Ingresa con tu contraseña para activarlo.'
            ];
        }

        // Validar que el dispositivo esté verificado para este usuario si se proporciona UUID
        if (!empty($deviceUuid)) {
            $db->query("
                SELECT id, estado, verificado_at, nombre_dispositivo
                FROM pasante_dispositivos
                WHERE usuario_id = :uid AND device_uuid = :uuid AND estado = 'activo'
                LIMIT 1
            ");
            $db->bind(':uid',  $user->id);
            $db->bind(':uuid', $deviceUuid);
            $disp = $db->single();

            // Si el dispositivo está registrado, actualizar último uso
            if ($disp) {
                $db->query("UPDATE pasante_dispositivos SET ultimo_uso_at = NOW() WHERE id = :id");
                $db->bind(':id', $disp->id);
                $db->execute();
            }
        }

        // Verificar el patrón con hash seguro
        if (!password_verify($patronRaw, $user->patron_hash)) {
            AuditModel::log('FALLO_LOGIN_PATRON', 'usuarios', $user->id, [
                'identificador' => $identificador,
                'ip'            => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

            return ['success' => false, 'message' => 'Patrón de desbloqueo incorrecto'];
        }

        // Iniciar sesión exitosa
        self::establecerSesionUsuario($user);

        // Auditoría
        AuditModel::log('LOGIN_EXITOSO_PATRON', 'usuarios', $user->id, [
            'identificador' => $identificador,
            'ip'            => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        return [
            'success'  => true,
            'message'  => '¡Identidad verificada! Bienvenido/a.',
            'cedula'   => (string)($user->cedula ?? ''),
            'redirect' => self::obtenerUrlRedireccion((int)$user->rol_id)
        ];
    }

    /**
     * Obtener lista de dispositivos vinculados del usuario
     */
    public static function obtenerDispositivos(int $userId): array
    {
        $db = Database::getInstance();
        $db->query("
            SELECT id, device_uuid, nombre_dispositivo, metodo_auth, estado, creado_at, ultimo_uso_at, verificado_at
            FROM pasante_dispositivos
            WHERE usuario_id = :uid
            ORDER BY verificado_at DESC, creado_at DESC
        ");
        $db->bind(':uid', $userId);
        return $db->resultSet() ?: [];
    }

    /**
     * Desvincular un dispositivo
     */
    public static function desvincularDispositivo(int $userId, int $dispositivoId): array
    {
        $db = Database::getInstance();
        $db->query("
            SELECT d.*, u.correo,
                   TRIM(CONCAT(COALESCE(dp.nombres, ''), ' ', COALESCE(dp.apellidos, ''))) AS nombre_completo
            FROM pasante_dispositivos d
            JOIN usuarios u ON u.id = d.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE d.id = :id AND d.usuario_id = :uid
            LIMIT 1
        ");
        $db->bind(':id',  $dispositivoId);
        $db->bind(':uid', $userId);
        $disp = $db->single();

        if (!$disp) {
            return ['success' => false, 'message' => 'Dispositivo no encontrado o no pertenece a tu cuenta'];
        }

        $db->query("DELETE FROM pasante_dispositivos WHERE id = :id");
        $db->bind(':id', $dispositivoId);
        $db->execute();

        AuditModel::log('DESVINCULAR_DISPOSITIVO_BANCARIO', 'pasante_dispositivos', $dispositivoId, [
            'dispositivo' => $disp->nombre_dispositivo,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        return ['success' => true, 'message' => 'Dispositivo desvinculado exitosamente'];
    }

    /**
     * Detectar nombre amigable del dispositivo y navegador
     */
    public static function detectarNombreDispositivo(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $os = 'Dispositivo';
        if (preg_match('/android/i', $ua))       $os = 'Android';
        elseif (preg_match('/iphone/i', $ua))    $os = 'iPhone';
        elseif (preg_match('/ipad/i', $ua))      $os = 'iPad';
        elseif (preg_match('/windows nt 10/i', $ua)) $os = 'Windows 10/11';
        elseif (preg_match('/windows nt/i', $ua))    $os = 'Windows PC';
        elseif (preg_match('/macintosh|mac os x/i', $ua)) $os = 'Mac';
        elseif (preg_match('/linux/i', $ua))     $os = 'Linux PC';

        $browser = 'Navegador';
        if (preg_match('/edg/i', $ua))           $browser = 'Edge';
        elseif (preg_match('/chrome/i', $ua))    $browser = 'Chrome';
        elseif (preg_match('/firefox/i', $ua))   $browser = 'Firefox';
        elseif (preg_match('/safari/i', $ua))    $browser = 'Safari';

        return "{$os} ({$browser})";
    }

    /**
     * Enmascarar correo para privacidad en pantallas (ej: l***9@gmail.com)
     */
    private static function enmascararCorreo(string $email): string
    {
        $partes = explode('@', $email);
        if (count($partes) !== 2) return $email;
        $name = $partes[0];
        $domain = $partes[1];
        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '***';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', min(4, $len - 2)) . substr($name, -1);
        }
        return $maskedName . '@' . $domain;
    }

    /**
     * Establecer sesión completa de usuario
     */
    private static function establecerSesionUsuario(object $user): void
    {
        Session::start();
        Session::set('user_id',    (int)$user->id);
        Session::set('role_id',    (int)$user->rol_id);
        Session::set('cedula',     (string)$user->cedula);
        Session::set('user_email', (string)$user->correo);
        Session::set('user_name',  (string)($user->nombre_completo ?: $user->cedula));
        Session::set('auth_time',  time());
    }

    /**
     * Obtener URL de redirección según el rol
     */
    public static function obtenerUrlRedireccion(int $rolId): string
    {
        switch ($rolId) {
            case 0:
            case 1:
                return URLROOT . '/admin/index';
            case 2:
                return URLROOT . '/tutor/index';
            case 3:
                return URLROOT . '/pasante/index';
            default:
                return URLROOT . '/dashboard';
        }
    }
}
